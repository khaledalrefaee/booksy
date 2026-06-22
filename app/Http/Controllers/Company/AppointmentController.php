<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\BranchPayment;
use App\Models\Customer;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authorise(Appointment $appointment): void
    {
        abort_unless($appointment->company_id === $this->company()->id, 403);
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $query = Appointment::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'customer', 'service', 'employee'])
            ->orderByDesc('start_time');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $appointments = $query->paginate(20)->withQueryString();
        $branches     = $company->branches()->orderBy('sort_order')->get();

        return view('company.appointments.index', compact('appointments', 'branches'));
    }

    public function create(Request $request): View
    {
        $company  = $this->company();
        $branches = $company->branches()->with('services.serviceCategory', 'employees.role')->orderBy('sort_order')->get();

        $selectedBranchId = $request->input('branch_id');

        return view('company.appointments.create', compact('company', 'branches', 'selectedBranchId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'branch_id'        => ['required', 'exists:branches,id'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'payment_status'   => ['nullable', 'in:pending,paid,partial'],
            // Persons: each person has name, phone, and services array
            'persons'          => ['required', 'array', 'min:1'],
            'persons.*.name'   => ['required', 'string', 'max:255'],
            'persons.*.phone'  => ['nullable', 'string', 'max:30'],
            'persons.*.age'    => ['nullable', 'integer', 'min:1', 'max:150'],
            'persons.*.services'             => ['required', 'array', 'min:1'],
            'persons.*.services.*.service_id'=> ['required', 'exists:services,id'],
            'persons.*.services.*.employee_id'=> ['nullable', 'exists:employees,id'],
            'persons.*.services.*.start_time' => ['required', 'date'],
        ]);

        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        $groupId = count($data['persons']) > 1 ? Appointment::newGroupId() : null;

        DB::transaction(function () use ($data, $company, $groupId) {
            foreach ($data['persons'] as $personIndex => $person) {
                // Resolve or create customer record
                $customer = null;
                if (! empty($person['phone'])) {
                    $customer = Customer::firstOrCreate(
                        ['phone' => $person['phone']],
                        ['name'  => $person['name'], 'age' => $person['age'] ?? null]
                    );
                    $updates = [];
                    if ($customer->name !== $person['name']) $updates['name'] = $person['name'];
                    if (! empty($person['age'])) $updates['age'] = $person['age'];
                    if ($updates) $customer->update($updates);
                }

                // Primary (first) service drives the appointment's own fields
                $firstSvc  = \App\Models\Service::findOrFail($person['services'][0]['service_id']);
                $firstStart= Carbon::parse($person['services'][0]['start_time']);
                $firstEnd  = $firstStart->copy()->addMinutes($firstSvc->duration_minutes);

                // Calculate total across all services
                $totalPrice = 0;
                $lastEnd    = $firstEnd->copy();

                foreach ($person['services'] as $svcData) {
                    $svc        = \App\Models\Service::findOrFail($svcData['service_id']);
                    $totalPrice += $svc->finalPrice();
                    $sEnd       = Carbon::parse($svcData['start_time'])->addMinutes($svc->duration_minutes);
                    if ($sEnd->gt($lastEnd)) $lastEnd = $sEnd;
                }

                $appointment = Appointment::create([
                    'booking_group_id'=> $groupId,
                    'company_id'      => $company->id,
                    'branch_id'       => $data['branch_id'],
                    'service_id'      => $firstSvc->id,
                    'employee_id'     => $person['services'][0]['employee_id'] ?? null,
                    'customer_id'     => $customer?->id,
                    'customer_name'   => $person['name'],
                    'customer_phone'  => $person['phone'] ?? null,
                    'start_time'      => $firstStart,
                    'end_time'        => $lastEnd,
                    'status'          => 'pending',
                    'total_price'     => $totalPrice,
                    'payment_status'  => $data['payment_status'] ?? 'pending',
                    'notes'           => $personIndex === 0 ? ($data['notes'] ?? null) : null,
                ]);

                // Save all services to pivot table
                foreach ($person['services'] as $sortOrder => $svcData) {
                    $svc   = \App\Models\Service::findOrFail($svcData['service_id']);
                    $start = Carbon::parse($svcData['start_time']);
                    $end   = $start->copy()->addMinutes($svc->duration_minutes);

                    AppointmentService::create([
                        'appointment_id' => $appointment->id,
                        'service_id'     => $svc->id,
                        'employee_id'    => $svcData['employee_id'] ?? null,
                        'price'          => $svc->finalPrice(),
                        'currency'       => $svc->currency ?? config('booksy.default_currency', 'SYP'),
                        'start_time'     => $start,
                        'end_time'       => $end,
                        'sort_order'     => $sortOrder,
                    ]);
                }

                Auditor::log("Created appointment #{$appointment->id} for {$appointment->customer_name}", $appointment);
            }
        });

        return redirect()
            ->route('company.appointments.index')
            ->with('success', __('Appointment created successfully.'));
    }

    public function show(Appointment $appointment): View
    {
        $this->authorise($appointment);

        $appointment->load(['branch', 'customer', 'service', 'employee', 'service.serviceCategory', 'handledBy', 'review', 'appointmentServices.service', 'appointmentServices.employee', 'invoice']);

        return view('company.appointments.show', compact('appointment'));
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorise($appointment);

        $data = $request->validate([
            'status'           => ['required', 'in:confirmed,completed,cancelled,rejected,no_show'],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
            // Payment fields (only when completing)
            'paid_amount'      => ['nullable', 'numeric', 'min:0'],
            'payment_method'   => ['nullable', 'in:cash,card,later'],
            'pay_notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $company        = $this->company();
        $previousStatus = $appointment->status;
        $previousPayment= $appointment->payment_status;

        // ── Determine payment_status ────────────────────────────────────────
        $paymentStatus = $appointment->payment_status;
        if ($data['status'] === 'completed' && $request->filled('paid_amount')) {
            $charged = (float) $appointment->total_price;
            $paid    = (float) $data['paid_amount'];
            $paymentStatus = $paid <= 0 ? 'pending'
                : ($paid >= $charged ? 'paid' : 'partial');
        }

        $appointment->update([
            'status'                  => $data['status'],
            'payment_status'          => $paymentStatus,
            'rejection_reason'        => $data['status'] === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
            'handled_at'              => now(),
            'status_previous'         => $previousStatus,
            'status_changed_by_type'  => 'company',
            'status_changed_by_id'    => $company->id,
            'status_changed_by_name'  => $company->localizedName(),
            'status_changed_at'       => now(),
        ]);

        // ── Auto-record payment in cash register when completing ─────────────
        if ($data['status'] === 'completed' && $request->filled('paid_amount')) {
            $charged  = (float) $appointment->total_price;
            $paid     = (float) $data['paid_amount'];
            $currency = $appointment->service?->currency
                        ?? config('booksy.default_currency', 'SYP');
            $method   = $data['payment_method'] ?? 'cash';

            // Main payment record
            if ($paid > 0) {
                BranchPayment::create([
                    'company_id'              => $company->id,
                    'branch_id'               => $appointment->branch_id,
                    'appointment_id'          => $appointment->id,
                    'type'                    => 'income',
                    'category'                => 'appointment',
                    'amount'                  => min($paid, $charged),
                    'currency'                => $currency,
                    'payment_method'          => $method,
                    'notes'                   => $data['pay_notes'] ?? null,
                    'recorded_by_employee_id' => null,
                    'paid_at'                 => now(),
                ]);
            }

            // Handle difference
            $diff = round($paid - $charged, 2);
            if ($diff > 0) {
                // Overpayment
                $branch      = $appointment->branch;
                $overpayTo   = $branch?->overpayment_to ?? 'treasury';
                BranchPayment::create([
                    'company_id'     => $company->id,
                    'branch_id'      => $appointment->branch_id,
                    'appointment_id' => $appointment->id,
                    'type'           => 'income',
                    'category'       => $overpayTo === 'employee' ? 'tip' : 'other_income',
                    'amount'         => $diff,
                    'currency'       => $currency,
                    'payment_method' => $method,
                    'notes'          => $overpayTo === 'employee'
                        ? __('Overpayment — tip to employee')
                        : __('Overpayment — added to treasury'),
                    'paid_at'        => now(),
                ]);
            } elseif ($diff < 0) {
                // Underpayment — record as debt note
                BranchPayment::create([
                    'company_id'     => $company->id,
                    'branch_id'      => $appointment->branch_id,
                    'appointment_id' => $appointment->id,
                    'type'           => 'adjustment',
                    'category'       => 'other_expense',
                    'amount'         => abs($diff),
                    'currency'       => $currency,
                    'payment_method' => $method,
                    'notes'          => __('Underpayment — customer owes :amount', [
                        'amount' => number_format(abs($diff), 2) . ' ' . $currency,
                    ]),
                    'paid_at'        => now(),
                ]);
            }
        }

        // ── Audit log ──────────────────────────────────────────────────────────
        Auditor::logChange(
            "Appointment #{$appointment->id} status changed",
            $appointment,
            ['status' => $previousStatus, 'payment_status' => $previousPayment],
            ['status' => $appointment->status, 'payment_status' => $appointment->payment_status],
        );

        // ── Auto-create invoice when completing ────────────────────────────
        if ($data['status'] === 'completed' && ! $appointment->invoice()->exists()) {
            try {
                $appointment->load(['service', 'employee', 'appointmentServices.service', 'appointmentServices.employee']);
                \App\Http\Controllers\Company\InvoiceController::buildInvoiceFromAppointment($appointment, $company);
            } catch (\Throwable $e) {
                // Invoice creation is non-critical
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $appointment->status]);
        }

        return redirect()
            ->route('company.appointments.show', $appointment)
            ->with('success', __('Appointment status updated.'));
    }

    /**
     * Ajax: return appointments as FullCalendar events JSON + working hours as background events.
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $company  = $this->company();
        $branchId = $request->input('branch_id');

        /* ── appointments ── */
        $query = Appointment::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'customer', 'service', 'employee'])
            ->whereNotNull('start_time');

        if ($request->filled('start')) {
            $query->where('start_time', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->where('start_time', '<=', $request->input('end'));
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $colorMap = [
            'pending'   => ['bg' => '#f59e0b', 'text' => '#ffffff'],
            'confirmed' => ['bg' => '#10b981', 'text' => '#ffffff'],
            'completed' => ['bg' => '#6366f1', 'text' => '#ffffff'],
            'cancelled' => ['bg' => '#6b7280', 'text' => '#ffffff'],
            'rejected'  => ['bg' => '#ef4444', 'text' => '#ffffff'],
            'no_show'   => ['bg' => '#94a3b8', 'text' => '#ffffff'],
        ];

        $tz     = config('app.timezone');
        $events = $query->get()->map(function (Appointment $appt) use ($colorMap, $tz) {
            $colors = $colorMap[$appt->status] ?? ['bg' => '#94a3b8', 'text' => '#fff'];
            $title  = trim(
                ($appt->customer?->name ?? __('Customer')) . ' · ' .
                ($appt->service?->localizedName() ?? '')
            );

            return [
                'id'              => $appt->id,
                'title'           => $title,
                'start'           => $appt->start_time?->format('Y-m-d\TH:i:s'),
                'end'             => $appt->end_time?->format('Y-m-d\TH:i:s'),
                'url'             => route('company.appointments.show', $appt->id),
                'backgroundColor' => $colors['bg'],
                'borderColor'     => $colors['bg'],
                'textColor'       => $colors['text'],
                'extendedProps'   => [
                    'type'       => 'appointment',
                    'status'     => $appt->status,
                    'branch'     => $appt->branch?->localizedName() ?? '—',
                    'service'    => $appt->service?->localizedName() ?? '—',
                    'employee'   => $appt->employee?->localizedName() ?? '—',
                    'employeeId'    => $appt->employee_id,
                    'employeeImage' => $appt->employee?->image ? asset('storage/' . $appt->employee->image) : null,
                    'price'         => number_format((float) $appt->total_price, 2),
                    'currency'      => $appt->service?->currency ?? config('booksy.default_currency', 'SYP'),
                    'customerPhone' => $appt->customer?->phone,
                    'updateUrl'     => route('company.appointments.update-status', $appt->id),
                    'showUrl'    => route('company.appointments.show', $appt->id),
                    'changedBy'  => $appt->status_changed_by_name,
                    'changedAt'  => $appt->status_changed_at?->format('Y-m-d\TH:i:s'),
                    'prevStatus' => $appt->status_previous,
                ],
            ];
        });

        /* ── working hours as background events (closed slots = blocked) ── */
        $rangeStart = $request->filled('start') ? \Carbon\Carbon::parse($request->input('start')) : now()->startOfWeek();
        $rangeEnd   = $request->filled('end')   ? \Carbon\Carbon::parse($request->input('end'))   : now()->endOfWeek();

        // Get branches to check
        $branchIds = $branchId
            ? [$branchId]
            : $company->branches()->pluck('id')->toArray();

        $workingHours = \DB::table('branch_working_hours')
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->groupBy('day_of_week');

        $bgEvents  = collect();
        $cursor    = $rangeStart->copy()->startOfDay();
        $rangeEndD = $rangeEnd->copy()->endOfDay();

        while ($cursor->lte($rangeEndD)) {
            $dow  = $cursor->dayOfWeek; // 0=Sun
            $rows = $workingHours->get($dow, collect());

            // Collect open shifts for this day
            $openShifts = $rows->where('is_open', 1)
                ->filter(fn($r) => $r->open_time && $r->close_time)
                ->values();

            if ($openShifts->isEmpty()) {
                // Whole day closed → background block
                $bgEvents->push([
                    'start'           => $cursor->toDateString(),
                    'end'             => $cursor->copy()->addDay()->toDateString(),
                    'display'         => 'background',
                    'backgroundColor' => 'rgba(102,126,234,.13)',
                    'extendedProps'   => ['type' => 'closed'],
                ]);
            } else {
                // Block before first shift and after last shift
                $firstOpen = $openShifts->min('open_time');
                $lastClose = $openShifts->max('close_time');

                if ($firstOpen > '00:00:00') {
                    $bgEvents->push([
                        'start'           => $cursor->toDateString() . 'T00:00:00',
                        'end'             => $cursor->toDateString() . 'T' . $firstOpen,
                        'display'         => 'background',
                        'backgroundColor' => 'rgba(102,126,234,.13)',
                        'extendedProps'   => ['type' => 'outside-hours'],
                    ]);
                }
                if ($lastClose < '23:59:00') {
                    $bgEvents->push([
                        'start'           => $cursor->toDateString() . 'T' . $lastClose,
                        'end'             => $cursor->toDateString() . 'T23:59:59',
                        'display'         => 'background',
                        'backgroundColor' => 'rgba(102,126,234,.13)',
                        'extendedProps'   => ['type' => 'outside-hours'],
                    ]);
                }
            }

            $cursor->addDay();
        }

        return response()->json($events->merge($bgEvents)->values());
    }

    /**
     * Ajax: return staff + their appointments for a specific date (staff view).
     */
    public function staffEvents(Request $request): JsonResponse
    {
        $company = $this->company();
        $date    = $request->input('date', now()->toDateString());
        $branchId = $request->input('branch_id');

        // Get employees
        $empQuery = \App\Models\Employee::query()
            ->whereHas('branch', fn($q) => $q->where('company_id', $company->id))
            ->where('is_active', true)
            ->orderBy('name_en');

        if ($branchId) {
            $empQuery->where('branch_id', $branchId);
        }

        $parsedDate = \Carbon\Carbon::parse($date);
        $dow = $parsedDate->dayOfWeek; // 0=Sun

        // Get working hours for branches
        $branchIdsForWH = $branchId
            ? [$branchId]
            : $company->branches()->pluck('id')->toArray();

        $workingHoursRows = \DB::table('branch_working_hours')
            ->whereIn('branch_id', $branchIdsForWH)
            ->where('day_of_week', $dow)
            ->get();

        // Build closed slots (minutes) for the day
        $openShifts = $workingHoursRows->where('is_open', 1)
            ->filter(fn($r) => $r->open_time && $r->close_time);

        $closedSlots = [];
        if ($openShifts->isEmpty()) {
            // Full day closed
            $closedSlots = [['from' => 0, 'to' => 1440]];
        } else {
            $firstOpenMin = $openShifts->min(fn($r) => $this->timeToMin($r->open_time));
            $lastCloseMin = $openShifts->max(fn($r) => $this->timeToMin($r->close_time));
            if ($firstOpenMin > 0)    $closedSlots[] = ['from' => 0,             'to' => $firstOpenMin];
            if ($lastCloseMin < 1440) $closedSlots[] = ['from' => $lastCloseMin, 'to' => 1440];
        }

        $employees = $empQuery->get()->map(function ($emp) use ($closedSlots) {
            $initials = collect(explode(' ', trim($emp->localizedName())))
                ->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
            return [
                'id'          => $emp->id,
                'name'        => $emp->localizedName(),
                'initials'    => $initials,
                'image'       => $emp->image ? asset('storage/' . $emp->image) : null,
                'closedSlots' => $closedSlots,
            ];
        });

        // Walk-in slot
        $slots = collect([[
            'id' => 0, 'name' => __('Walk-in'), 'initials' => 'WI', 'closedSlots' => $closedSlots,
        ]])->merge($employees);

        // Get appointments for that date (full UTC day range)
        $dayStart = $parsedDate->copy()->startOfDay();
        $dayEnd   = $parsedDate->copy()->endOfDay();

        $apptQuery = Appointment::query()
            ->where('company_id', $company->id)
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->with(['customer', 'service', 'employee', 'branch']);

        if ($branchId) {
            $apptQuery->where('branch_id', $branchId);
        }

        $colorMap = [
            'pending'   => '#f59e0b', 'confirmed' => '#10b981',
            'completed' => '#6366f1', 'cancelled'  => '#9ca3af',
            'rejected'  => '#ef4444', 'no_show'    => '#cbd5e1',
        ];

        $tz = config('app.timezone');
        $appointments = $apptQuery->get()->map(function (Appointment $a) use ($colorMap, $tz) {
            // Convert to local browser-equivalent: send as local wall-clock minutes
            // We send start_time as-is; JavaScript will compute minutes from ISO string in browser TZ
            return [
                'id'          => $a->id,
                'employeeId'  => $a->employee_id ?? 0,
                'customer'    => $a->customer?->name ?? __('Customer'),
                'service'     => $a->service?->localizedName() ?? '—',
                'branch'      => $a->branch?->localizedName() ?? '—',
                'employee'    => $a->employee?->localizedName() ?? __('Walk-in'),
                'status'      => $a->status,
                'color'       => $colorMap[$a->status] ?? '#9ca3af',
                'price'       => number_format((float) $a->total_price, 2),
                'startIso'    => $a->start_time?->format('Y-m-d\TH:i:s'),
                'endIso'      => $a->end_time?->format('Y-m-d\TH:i:s'),
                'startLabel'  => $a->start_time?->format('h:i A'),
                'endLabel'    => $a->end_time?->format('h:i A'),
                'changedBy'   => $a->status_changed_by_name,
                'showUrl'     => route('company.appointments.show', $a->id),
            ];
        });

        return response()->json(['staff' => $slots->values(), 'appointments' => $appointments->values()]);
    }

    private function timeToMin(string $time): int
    {
        [$h, $m] = explode(':', $time);
        return (int)$h * 60 + (int)$m;
    }

    /**
     * Ajax: return services + employees for a branch.
     */
    public function branchData(Request $request): \Illuminate\Http\JsonResponse
    {
        $company  = $this->company();
        $branchId = $request->input('branch_id');

        $branch = $company->branches()->where('id', $branchId)->firstOrFail();

        $services  = $branch->services()->where('is_active', true)->with('serviceCategory')->orderBy('name_en')->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->localizedName(), 'price' => $s->price, 'duration' => $s->duration_minutes]);

        $employees = $branch->employees()->where('is_active', true)->with('role')->orderBy('name_en')->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->localizedName()]);

        return response()->json(compact('services', 'employees'));
    }
}
