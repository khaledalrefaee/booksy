<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'branch_id'       => ['required', 'exists:branches,id'],
            'service_id'      => ['required', 'exists:services,id'],
            'employee_id'     => ['nullable', 'exists:employees,id'],
            'customer_name'   => ['required', 'string', 'max:255'],
            'customer_email'  => ['nullable', 'email', 'max:255'],
            'customer_phone'  => ['nullable', 'string', 'max:30'],
            'start_time'      => ['required', 'date'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'payment_status'  => ['nullable', 'in:pending,paid,partial'],
        ]);

        // Verify branch belongs to company
        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        // Find or create customer
        $customer = null;
        if (! empty($data['customer_email'])) {
            $customer = User::query()->firstOrCreate(
                ['email' => $data['customer_email']],
                [
                    'name'     => $data['customer_name'],
                    'password' => Hash::make(str()->random(16)),
                ]
            );
            if ($customer->name !== $data['customer_name']) {
                $customer->update(['name' => $data['customer_name']]);
            }
        } else {
            $customer = User::query()->create([
                'name'     => $data['customer_name'],
                'email'    => 'guest_' . time() . '_' . rand(1000, 9999) . '@booksy.local',
                'password' => Hash::make(str()->random(16)),
            ]);
        }

        // Calculate end_time from service duration
        $service   = \App\Models\Service::query()->findOrFail($data['service_id']);
        $startTime = \Illuminate\Support\Carbon::parse($data['start_time']);
        $endTime   = $startTime->copy()->addMinutes($service->duration_minutes);

        Appointment::query()->create([
            'company_id'     => $company->id,
            'branch_id'      => $data['branch_id'],
            'service_id'     => $data['service_id'],
            'employee_id'    => $data['employee_id'] ?? null,
            'customer_id'    => $customer->id,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'status'         => 'pending',
            'total_price'    => $service->price,
            'payment_status' => $data['payment_status'] ?? 'pending',
            'notes'          => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('company.appointments.index')
            ->with('success', __('Appointment created successfully.'));
    }

    public function show(Appointment $appointment): View
    {
        $this->authorise($appointment);

        $appointment->load(['branch', 'customer', 'service', 'employee', 'service.serviceCategory', 'handledBy', 'review']);

        return view('company.appointments.show', compact('appointment'));
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorise($appointment);

        $data = $request->validate([
            'status'           => ['required', 'in:confirmed,completed,cancelled,rejected,no_show'],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ]);

        $company        = $this->company();
        $previousStatus = $appointment->status;

        $appointment->update([
            'status'                  => $data['status'],
            'rejection_reason'        => $data['status'] === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
            'handled_by_employee_id'  => null,
            'handled_at'              => now(),
            'status_previous'         => $previousStatus,
            'status_changed_by_type'  => 'company',
            'status_changed_by_id'    => $company->id,
            'status_changed_by_name'  => $company->localizedName(),
            'status_changed_at'       => now(),
        ]);

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
                'start'           => $appt->start_time?->setTimezone($tz)->toIso8601String(),
                'end'             => $appt->end_time?->setTimezone($tz)->toIso8601String(),
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
                    'employeeId' => $appt->employee_id,
                    'price'      => number_format((float) $appt->total_price, 2),
                    'showUrl'    => route('company.appointments.show', $appt->id),
                    'changedBy'  => $appt->status_changed_by_name,
                    'changedAt'  => $appt->status_changed_at?->setTimezone($tz)->toIso8601String(),
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
                'id'           => $emp->id,
                'name'         => $emp->localizedName(),
                'initials'     => $initials,
                'closedSlots'  => $closedSlots,
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
            $start = $a->start_time?->setTimezone($tz);
            $end   = $a->end_time?->setTimezone($tz);
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
                'startIso'    => $a->start_time?->toIso8601String(),
                'endIso'      => $a->end_time?->toIso8601String(),
                'startLabel'  => $start?->format('h:i A'),
                'endLabel'    => $end?->format('h:i A'),
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
