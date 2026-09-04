<?php

namespace App\Http\Controllers\Company;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Exceptions\IllegalStatusTransition;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\BlockedTime;
use App\Models\BranchPayment;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\WaitlistEntry;
use App\States\AppointmentStateMachine;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use \App\Http\Controllers\Company\Concerns\ChecksBookingConflicts;
    use \App\Http\Controllers\Company\Concerns\HandlesIdempotentBookings;

    protected function company(): \App\Models\Company
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

        // Auto-select when the company has a single branch, so the customer /
        // service fields appear immediately instead of forcing an extra click.
        if (! $selectedBranchId && $branches->count() === 1) {
            $selectedBranchId = $branches->first()->id;
        }

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

        $groupId   = count($data['persons']) > 1 ? Appointment::newGroupId() : null;
        $allocator = app(\App\Services\ResourceAllocator::class);

        DB::transaction(function () use ($data, $company, $groupId, $allocator) {
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

                // Booking must not land inside a blocked-time window
                $personEmployeeId = $person['services'][0]['employee_id'] ?? null;
                if ($block = $this->blockedConflict((int) $data['branch_id'], $personEmployeeId ? (int) $personEmployeeId : null, $firstStart, $lastEnd)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'persons' => $this->blockedMessage($block) . ' — ' . $person['name'],
                    ]);
                }

                // A required room/device must be free for the person's whole window
                $resourceId = null;
                if ($allocator->requiresResource($firstSvc)) {
                    $resource = $allocator->findFree($firstSvc, $firstStart, $lastEnd, null, true);
                    if (! $resource) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'persons' => $allocator->conflictMessage($firstSvc, $firstStart, $lastEnd)
                                         . ' — ' . $person['name'],
                        ]);
                    }
                    $resourceId = $resource->id;
                }

                $appointment = Appointment::create([
                    'booking_group_id'=> $groupId,
                    'company_id'      => $company->id,
                    'branch_id'       => $data['branch_id'],
                    'service_id'      => $firstSvc->id,
                    'employee_id'     => $person['services'][0]['employee_id'] ?? null,
                    'resource_id'     => $resourceId,
                    'customer_id'     => $customer?->id,
                    'customer_name'   => $person['name'],
                    'customer_phone'  => $person['phone'] ?? null,
                    'start_time'      => $firstStart,
                    'end_time'        => $lastEnd,
                    'status'          => AppointmentStatus::Pending,
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

        $appointment->load(['branch', 'customer', 'service', 'employee', 'resource', 'service.serviceCategory', 'handledBy', 'review', 'appointmentServices.service', 'appointmentServices.employee', 'invoice', 'transitions']);

        return view('company.appointments.show', compact('appointment'));
    }

    /** Record / update the tip left for the employee on a completed appointment. */
    public function updateTip(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorise($appointment);

        if ($appointment->status !== AppointmentStatus::Completed) {
            return back()->with('error', __('Tips can only be recorded on completed appointments.'));
        }

        $data = $request->validate([
            'tip_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $appointment->update(['tip_amount' => $data['tip_amount']]);

        return back()->with('success', __('Tip saved.'));
    }

    /**
     * Ajax: full appointment details for the calendar detail drawer.
     */
    public function detailsJson(Appointment $appointment): JsonResponse
    {
        $this->authorise($appointment);
        $appointment->load(['customer', 'service', 'employee', 'branch', 'resource', 'appointmentServices.service', 'appointmentServices.employee']);

        $currency = $appointment->service?->currency ?? config('booksy.default_currency', 'SYP');
        $fallbackEmp = $appointment->employee?->localizedName() ?? __('Walk-in');

        $services = $appointment->appointmentServices->map(fn ($as) => [
            'name'     => $as->service?->localizedName() ?? '—',
            'employee' => $as->employee?->localizedName() ?? $fallbackEmp,
            'start'    => $as->start_time?->format('H:i'),
            'duration' => $as->start_time && $as->end_time ? (int) round($as->start_time->diffInMinutes($as->end_time)) : null,
            'price'    => (float) $as->price,
        ])->values();

        if ($services->isEmpty()) {
            $services = collect([[
                'name'     => $appointment->service?->localizedName() ?? '—',
                'employee' => $fallbackEmp,
                'start'    => $appointment->start_time?->format('H:i'),
                'duration' => $appointment->start_time && $appointment->end_time
                    ? (int) round($appointment->start_time->diffInMinutes($appointment->end_time)) : null,
                'price'    => (float) $appointment->total_price,
            ]]);
        }

        return response()->json([
            'ok'            => true,
            'id'            => $appointment->id,
            'status'        => $appointment->status,
            'paymentStatus' => $appointment->payment_status,
            'branchId'      => $appointment->branch_id,
            'employee'      => $fallbackEmp,
            'employeeId'    => $appointment->employee_id,
            'resource'      => $appointment->resource?->localizedName(),
            'dateIso'       => $appointment->start_time?->format('Y-m-d'),
            'startLabel'    => $appointment->start_time?->format('H:i'),
            'endLabel'      => $appointment->end_time?->format('H:i'),
            'customer'      => [
                'id'    => $appointment->customer_id,
                'name'  => $appointment->customer?->name ?? $appointment->customer_name ?? __('Walk-in'),
                'phone' => $appointment->customer?->phone ?? $appointment->customer_phone,
                'url'   => $appointment->customer_id ? route('company.customers.show', $appointment->customer_id) : null,
            ],
            'services'    => $services,
            'total'       => (float) $appointment->total_price,
            'durationMin' => (int) $services->sum('duration'),
            'currency'    => $currency,
            'tip'         => (float) ($appointment->tip_amount ?? 0),
            'showUrl'     => route('company.appointments.show', $appointment->id),
        ]);
    }

    /**
     * Ajax: cart data for checkout — sellable products + the customer's other unpaid appointments.
     */
    public function checkoutData(Request $request): JsonResponse
    {
        $company    = $this->company();
        $branchId   = $request->input('branch_id');
        $customerId = $request->input('customer_id');
        $exclude    = $request->input('exclude');

        $products = \App\Models\Product::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('is_display_only', false)
            ->when($branchId, fn ($q) => $q->where(fn ($w) => $w->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->with(['branchStocks' => fn ($q) => $q->when($branchId, fn ($w) => $w->where('branch_id', $branchId))])
            ->orderBy('name_en')->limit(150)->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'name'     => $p->localizedName(),
                'price'    => (float) $p->price,
                'currency' => $p->currency ?? config('booksy.default_currency', 'SYP'),
                'stock'    => $p->track_stock ? (int) $p->branchStocks->sum('quantity') : null,
            ]);

        $appointments = collect();
        if ($customerId) {
            $appointments = Appointment::query()
                ->where('company_id', $company->id)
                ->where('customer_id', $customerId)
                ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))
                ->whereIn('status', [
                    AppointmentStatus::Pending->value,
                    AppointmentStatus::Confirmed->value,
                    AppointmentStatus::Arrived->value,
                    AppointmentStatus::InProgress->value,
                    AppointmentStatus::Paused->value,
                    AppointmentStatus::AwaitingPayment->value,
                ])
                ->where('payment_status', '!=', 'paid')
                ->where('start_time', '>=', now()->subDays(30))
                ->with('service')
                ->orderBy('start_time')->limit(10)->get()
                ->map(fn ($a) => [
                    'id'       => $a->id,
                    'name'     => ($a->service?->localizedName() ?? '—') . ' · ' . $a->start_time?->format('d/m H:i'),
                    'price'    => (float) $a->total_price,
                    'currency' => $a->service?->currency ?? config('booksy.default_currency', 'SYP'),
                ]);
        }

        return response()->json(['products' => $products, 'appointments' => $appointments->values()]);
    }

    /**
     * Ajax: checkout — complete & pay the appointment, with optional tip,
     * extra services, product sales, and other appointments in the same sale.
     */
    public function checkout(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorise($appointment);
        $company = $this->company();

        /* ── double-checkout guard (fast path, re-checked under lock inside the tx) ── */
        if ($appointment->payment_status === 'paid') {
            return response()->json([
                'ok'      => false,
                'code'    => 'already_paid',
                'message' => __('This appointment has already been checked out and paid.'),
            ], 409);
        }

        $data = $request->validate([
            'payment_method'          => ['required', 'in:cash,card'],
            'tip_amount'              => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'extra_service_ids'       => ['nullable', 'array'],
            'extra_service_ids.*'     => ['exists:services,id'],
            'products'                => ['nullable', 'array'],
            'products.*.id'           => ['required', 'exists:products,id'],
            'products.*.qty'          => ['required', 'integer', 'min:1', 'max:999'],
            'other_appointment_ids'   => ['nullable', 'array'],
            'other_appointment_ids.*' => ['integer'],
        ]);

        $method   = $data['payment_method'];
        $tip      = (float) ($data['tip_amount'] ?? 0);
        $currency = $appointment->service?->currency ?? config('booksy.default_currency', 'SYP');
        $operator = $company->localizedName();

        try {
            DB::transaction(function () use ($appointment, $company, $data, $method, $tip, $currency, $operator) {

                /* ── race guard: reload under lock — a second clerk may have paid it already ── */
                $fresh = Appointment::lockForUpdate()->findOrFail($appointment->id);
                if ($fresh->payment_status === 'paid') {
                    abort(409, __('This appointment has already been checked out and paid.'));
                }

                /* ── stock pre-validation with product names (before any money moves) ── */
                foreach ($data['products'] ?? [] as $line) {
                    $product = \App\Models\Product::where('company_id', $company->id)->findOrFail($line['id']);
                    if (! $product->track_stock) continue;
                    $available = (int) \App\Models\BranchStock::where('branch_id', $appointment->branch_id)
                        ->where('product_id', $product->id)->lockForUpdate()->sum('quantity');
                    if ($available < (int) $line['qty']) {
                        abort(422, __('Insufficient stock for ":name" — available: :qty', [
                            'name' => $product->localizedName(),
                            'qty'  => $available,
                        ]));
                    }
                }

                /* ── append extra services (sold without their own booking) ── */
                $extraIds = $data['extra_service_ids'] ?? [];
                if ($extraIds) {
                    $found  = \App\Models\Service::findMany($extraIds)->keyBy('id');
                    $cursor = $appointment->end_time?->copy() ?? now();
                    $sort   = $appointment->appointmentServices()->count();
                    $total  = (float) $appointment->total_price;
                    foreach ($extraIds as $sid) {
                        $svc = $found[$sid] ?? null;
                        if (! $svc) continue;
                        $svcEnd = $cursor->copy()->addMinutes($svc->duration_minutes);
                        AppointmentService::create([
                            'appointment_id' => $appointment->id,
                            'service_id'     => $svc->id,
                            'employee_id'    => $appointment->employee_id,
                            'price'          => $svc->finalPrice(),
                            'currency'       => $svc->currency ?? $currency,
                            'start_time'     => $cursor,
                            'end_time'       => $svcEnd,
                            'sort_order'     => $sort++,
                        ]);
                        $total += $svc->finalPrice();
                        $cursor = $svcEnd;
                    }
                    $appointment->update(['total_price' => $total, 'end_time' => $cursor]);
                }

                /* ── complete + mark paid ── */
                $appointment->update([
                    'payment_status' => 'paid',
                    'tip_amount'     => $tip > 0 ? $tip : $appointment->tip_amount,
                ]);

                app(TransitionAppointment::class)(
                    $appointment,
                    AppointmentStatus::Completed,
                    TransitionActor::Company,
                    [
                        'actorId'   => $company->id,
                        'actorName' => $operator,
                        'meta'      => ['source' => 'checkout', 'method' => $method],
                    ],
                );

                BranchPayment::create([
                    'company_id'     => $company->id,
                    'branch_id'      => $appointment->branch_id,
                    'appointment_id' => $appointment->id,
                    'type'           => 'income',
                    'category'       => 'appointment',
                    'amount'         => (float) $appointment->total_price,
                    'currency'       => $currency,
                    'payment_method' => $method,
                    'notes'          => __('Checkout') . " #{$appointment->id}",
                    'paid_at'        => now(),
                ]);

                /* ── tip → the employee ── */
                if ($tip > 0) {
                    BranchPayment::create([
                        'company_id'              => $company->id,
                        'branch_id'               => $appointment->branch_id,
                        'appointment_id'          => $appointment->id,
                        'type'                    => 'income',
                        'category'                => 'tip',
                        'amount'                  => $tip,
                        'currency'                => $currency,
                        'payment_method'          => $method,
                        'recorded_by_employee_id' => $appointment->employee_id,
                        'notes'                   => __('Tip for') . ' ' . ($appointment->employee?->localizedName() ?? '—'),
                        'paid_at'                 => now(),
                    ]);
                }

                /* ── product sales (stock + income) ── */
                foreach ($data['products'] ?? [] as $line) {
                    $product = \App\Models\Product::where('company_id', $company->id)->findOrFail($line['id']);
                    $qty     = (int) $line['qty'];
                    if ($product->track_stock) {
                        app(\App\Services\InventoryService::class)->removeStock(
                            $product, $appointment->branch_id, $qty,
                            'sale', "checkout:{$appointment->id}", null,
                            __('Checkout') . " #{$appointment->id}", $operator
                        );
                    }
                    BranchPayment::create([
                        'company_id'     => $company->id,
                        'branch_id'      => $appointment->branch_id,
                        'appointment_id' => $appointment->id,
                        'type'           => 'income',
                        'category'       => 'product_sale',
                        'amount'         => (float) $product->price * $qty,
                        'currency'       => $product->currency ?? $currency,
                        'payment_method' => $method,
                        'notes'          => $product->localizedName() . ' × ' . $qty,
                        'paid_at'        => now(),
                    ]);
                }

                /* ── pay other appointments in the same sale ── */
                foreach ($data['other_appointment_ids'] ?? [] as $oid) {
                    $other = Appointment::where('company_id', $company->id)->lockForUpdate()->find($oid);
                    if (! $other || $other->id === $appointment->id
                        || $other->status === AppointmentStatus::Completed
                        || $other->payment_status === 'paid') continue;

                    $other->update(['payment_status' => 'paid']);

                    // attempt(): a co-paid appointment may sit in a status that
                    // cannot close directly (a still-pending one, say). Skipping
                    // its status change is better than failing the whole sale —
                    // the payment below is recorded either way.
                    app(TransitionAppointment::class)->attempt(
                        $other,
                        AppointmentStatus::Completed,
                        TransitionActor::Company,
                        [
                            'actorId'   => $company->id,
                            'actorName' => $operator,
                            'meta'      => ['source' => 'checkout', 'paid_with' => $appointment->id],
                        ],
                    );
                    BranchPayment::create([
                        'company_id'     => $company->id,
                        'branch_id'      => $other->branch_id,
                        'appointment_id' => $other->id,
                        'type'           => 'income',
                        'category'       => 'appointment',
                        'amount'         => (float) $other->total_price,
                        'currency'       => $other->service?->currency ?? $currency,
                        'payment_method' => $method,
                        'notes'          => __('Paid within checkout') . " #{$appointment->id}",
                        'paid_at'        => now(),
                    ]);
                    if (! $other->invoice()->exists()) {
                        try {
                            $other->load(['service', 'employee', 'appointmentServices.service', 'appointmentServices.employee']);
                            \App\Http\Controllers\Company\InvoiceController::buildInvoiceFromAppointment($other, $company);
                        } catch (\Throwable $e) { /* non-critical */ }
                    }
                }
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $status = $e->getStatusCode() >= 400 ? $e->getStatusCode() : 422;
            return response()->json([
                'ok'      => false,
                'code'    => $status === 409 ? 'already_paid' : 'validation',
                'message' => $e->getMessage(),
            ], $status);
        }

        /* invoice for the main appointment */
        if (! $appointment->invoice()->exists()) {
            try {
                $appointment->load(['service', 'employee', 'appointmentServices.service', 'appointmentServices.employee']);
                \App\Http\Controllers\Company\InvoiceController::buildInvoiceFromAppointment($appointment, $company);
            } catch (\Throwable $e) { /* non-critical */ }
        }

        Auditor::log("Checkout completed for appointment #{$appointment->id} ({$method})", $appointment);

        $invoice = $appointment->invoice()->first();

        return response()->json([
            'ok'         => true,
            'invoiceId'  => $invoice?->id,
            'invoiceUrl' => $invoice ? route('company.invoices.show', $invoice->id) : null,
            'printUrl'   => $invoice ? route('company.invoices.print', $invoice->id) : null,
        ]);
    }

    /**
     * Ajax: quick-book an appointment from the calendar drawer (Fresha-style).
     */
    public function quickStore(Request $request): JsonResponse
    {
        /* replay-safe: an offline retry with the same key returns the first result */
        return $this->idempotent($request->input('idempotency_key'), fn () => $this->quickStoreInner($request));
    }

    private function quickStoreInner(Request $request): JsonResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'branch_id'      => ['required', 'exists:branches,id'],
            'service_ids'    => ['required', 'array', 'min:1'],
            'service_ids.*'  => ['required', 'exists:services,id'],
            /* optional per-item overrides (parallel to service_ids) */
            'prices'         => ['nullable', 'array'],
            'prices.*'       => ['nullable', 'numeric', 'min:0'],
            'durations'      => ['nullable', 'array'],
            'durations.*'    => ['nullable', 'integer', 'min:5', 'max:1440'],
            'employee_ids'   => ['nullable', 'array'],
            'employee_ids.*' => ['nullable', 'integer'],
            'employee_id'    => ['nullable', 'integer'],
            'customer_id'    => ['nullable', 'exists:customers,id'],
            'customer_name'  => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'start_time'     => ['required', 'date'],
        ]);

        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        $employeeId = ($data['employee_id'] ?? null) ?: null;
        if ($employeeId) {
            abort_unless($this->employeeBelongsToCompany($employeeId), 403);
        }

        // Services in the order they were picked (may repeat)
        $found    = \App\Models\Service::findMany($data['service_ids'])->keyBy('id');
        $services = collect($data['service_ids'])->map(fn ($id) => $found[$id] ?? null)->filter()->values();
        abort_if($services->isEmpty(), 422);

        $start = Carbon::parse($data['start_time']);

        // Resolve customer: by id, or create from name/phone, or walk-in (null)
        $customer = null;
        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        } elseif (! empty($data['customer_phone'])) {
            $customer = Customer::firstOrCreate(
                ['phone' => $data['customer_phone']],
                ['name'  => ($data['customer_name'] ?? null) ?: __('Customer')]
            );
        }

        /* per-item rows: service + optional price/duration/employee override */
        $rows = $services->values()->map(function ($svc, $i) use ($data, $employeeId) {
            $empId = $data['employee_ids'][$i] ?? $employeeId;
            $empId = $empId ? (int) $empId : null;
            if ($empId && $empId !== $employeeId) {
                abort_unless($this->employeeBelongsToCompany($empId), 403);
            }
            return [
                'svc'      => $svc,
                'price'    => isset($data['prices'][$i]) && $data['prices'][$i] !== null && $data['prices'][$i] !== ''
                                ? (float) $data['prices'][$i] : $svc->finalPrice(),
                'duration' => isset($data['durations'][$i]) && $data['durations'][$i]
                                ? (int) $data['durations'][$i] : $svc->duration_minutes,
                'empId'    => $empId,
            ];
        });

        $allocator = app(\App\Services\ResourceAllocator::class);

        $appointment = DB::transaction(function () use ($data, $rows, $start, $company, $employeeId, $customer, $allocator) {
            $totalPrice = $rows->sum('price');
            $totalMins  = $rows->sum('duration');
            $end        = $start->copy()->addMinutes($totalMins);

            // Booking must not land inside a blocked-time window
            $quickEmpId = $rows->first()['empId'] ?? $employeeId;
            if ($block = $this->blockedConflict((int) $data['branch_id'], $quickEmpId ? (int) $quickEmpId : null, $start, $end)) {
                throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                    'ok'      => false,
                    'message' => $this->blockedMessage($block),
                ], 422));
            }

            // Each assigned employee must be free for their own service sub-window
            $probe = $start->copy();
            foreach ($rows as $row) {
                $probeEnd = $probe->copy()->addMinutes($row['duration']);
                if ($row['empId'] && ($conflict = $this->employeeConflict((int) $row['empId'], $probe, $probeEnd))) {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                        'ok'      => false,
                        'code'    => 'employee_busy',
                        'message' => $this->employeeConflictMessage($conflict) . ' — ' . $row['svc']->localizedName(),
                    ], 422));
                }
                $probe = $probeEnd;
            }

            // A required room/device must be free for the whole window
            $resourceId = null;
            $primarySvc = $rows->first()['svc'];
            if ($allocator->requiresResource($primarySvc)) {
                $resource = $allocator->findFree($primarySvc, $start, $end, null, true);
                if (! $resource) {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                        'ok'      => false,
                        'message' => $allocator->conflictMessage($primarySvc, $start, $end),
                    ], 422));
                }
                $resourceId = $resource->id;
            }

            $appointment = Appointment::create([
                'company_id'     => $company->id,
                'branch_id'      => $data['branch_id'],
                'service_id'     => $rows->first()['svc']->id,
                'employee_id'    => $rows->first()['empId'] ?? $employeeId,
                'resource_id'    => $resourceId,
                'customer_id'    => $customer?->id,
                'customer_name'  => $customer?->name ?? (($data['customer_name'] ?? null) ?: __('Walk-in')),
                'customer_phone' => $customer?->phone ?? ($data['customer_phone'] ?? null),
                'start_time'     => $start,
                'end_time'       => $end,
                'status'         => AppointmentStatus::Confirmed,
                'total_price'    => $totalPrice,
                'payment_status' => 'pending',
            ]);

            // Services run back-to-back starting at the picked slot
            $cursor = $start->copy();
            foreach ($rows as $i => $row) {
                $svcEnd = $cursor->copy()->addMinutes($row['duration']);
                AppointmentService::create([
                    'appointment_id' => $appointment->id,
                    'service_id'     => $row['svc']->id,
                    'employee_id'    => $row['empId'],
                    'price'          => $row['price'],
                    'currency'       => $row['svc']->currency ?? config('booksy.default_currency', 'SYP'),
                    'start_time'     => $cursor,
                    'end_time'       => $svcEnd,
                    'sort_order'     => $i,
                ]);
                $cursor = $svcEnd;
            }

            return $appointment;
        });

        Auditor::log("Quick-booked appointment #{$appointment->id} for {$appointment->customer_name}", $appointment);

        return response()->json([
            'ok'      => true,
            'id'      => $appointment->id,
            'showUrl' => route('company.appointments.show', $appointment->id),
        ]);
    }

    /**
     * Ajax: book several guests together in one shot (group appointment).
     *
     * A party is ONE booking with several attendees, not several independent
     * bookings that fight each other for the same staff.
     *
     * Guests run in parallel when they can (different stylists → everyone starts
     * together) and are laid out one after another when they genuinely contend —
     * two guests cannot share one stylist, or one laser, at the same moment. So
     * the party member who contends is placed at the first free time after their
     * party-mate instead of being rejected as "the employee is busy": that
     * rejection was the engine treating attendees as rival bookings.
     *
     * The requested start still belongs to the first guest; if *that* is taken
     * the party is refused, exactly like a single booking would be.
     *
     * All rows share a booking_group_id and are created in one transaction, so
     * the whole party either books or nothing does.
     */
    public function quickGroupStore(Request $request): JsonResponse
    {
        /* replay-safe: the whole party books once, however many times this arrives */
        return $this->idempotent($request->input('idempotency_key'), fn () => $this->quickGroupStoreInner($request));
    }

    private function quickGroupStoreInner(Request $request): JsonResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'branch_id'  => ['required', 'exists:branches,id'],
            'start_time' => ['required', 'date'],
            'guests'                          => ['required', 'array', 'min:1', 'max:12'],
            'guests.*.customer_id'            => ['nullable', 'exists:customers,id'],
            'guests.*.name'                   => ['nullable', 'string', 'max:255'],
            'guests.*.phone'                  => ['nullable', 'string', 'max:30'],
            'guests.*.services'               => ['required', 'array', 'min:1'],
            'guests.*.services.*.service_id'  => ['required', 'exists:services,id'],
            'guests.*.services.*.employee_id' => ['nullable', 'integer'],
            'guests.*.services.*.price'       => ['nullable', 'numeric', 'min:0'],
            'guests.*.services.*.duration'    => ['nullable', 'integer', 'min:5', 'max:1440'],
        ]);

        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        $branchId = (int) $data['branch_id'];
        $start    = Carbon::parse($data['start_time']);
        $groupId  = count($data['guests']) > 1 ? Appointment::newGroupId() : null;

        /* every service must belong to this branch — never trust posted ids */
        $svcIds   = collect($data['guests'])->pluck('services')->flatten(1)->pluck('service_id')->unique();
        $services = \App\Models\Service::whereIn('id', $svcIds)->where('branch_id', $branchId)->get()->keyBy('id');
        $missing  = $svcIds->reject(fn ($id) => $services->has($id));
        if ($missing->isNotEmpty()) {
            return response()->json([
                'ok'      => false,
                'message' => __('That service is not offered at this branch.'),
            ], 422);
        }

        $allocator = app(\App\Services\ResourceAllocator::class);
        $currency  = config('booksy.default_currency', 'SYP');

        try {
            $created = DB::transaction(function () use ($data, $company, $branchId, $start, $groupId, $services, $allocator, $currency) {
                $ids = [];

                foreach ($data['guests'] as $gi => $guest) {
                    /* rows for this guest, in the order picked */
                    $rows = collect($guest['services'])->map(function ($s) use ($services) {
                        $svc = $services[$s['service_id']];
                        return [
                            'svc'      => $svc,
                            'price'    => isset($s['price']) && $s['price'] !== null && $s['price'] !== ''
                                            ? (float) $s['price'] : $svc->finalPrice(),
                            'duration' => ! empty($s['duration']) ? (int) $s['duration'] : $svc->duration_minutes,
                            'empId'    => ! empty($s['employee_id']) ? (int) $s['employee_id'] : null,
                        ];
                    });

                    foreach ($rows as $row) {
                        if ($row['empId'] && ! $this->employeeBelongsToCompany($row['empId'])) {
                            abort(403);
                        }
                    }

                    $guestName = trim($guest['name'] ?? '') ?: __('Walk-in');
                    $totalMins = $rows->sum('duration');

                    /* Place this guest. The first guest owns the requested slot; a later
                       guest slides past whatever blocks them — usually their own
                       party-mate holding the shared stylist or machine. */
                    $gStart = $start->copy();
                    $isFirst = $gi === 0;
                    $placed  = false;

                    for ($attempt = 0; $attempt < 60; $attempt++) {
                        $gEnd    = $gStart->copy()->addMinutes($totalMins);
                        $blocker = $this->firstBlockerEnd($rows, $branchId, $gStart, $gEnd, $allocator);

                        if ($blocker === null) { $placed = true; break; }

                        /* the requested slot itself is taken — that's a real refusal,
                           same as a single booking would give */
                        if ($isFirst) {
                            abort(422, $this->placementMessage($rows, $branchId, $gStart, $gEnd, $allocator, $guestName));
                        }

                        /* never drift into another day looking for a gap */
                        if ($blocker->gt($start->copy()->endOfDay())) break;

                        $gStart = $blocker;
                    }

                    if (! $placed) {
                        $gEnd = $gStart->copy()->addMinutes($totalMins);
                        abort(422, $this->placementMessage($rows, $branchId, $gStart, $gEnd, $allocator, $guestName));
                    }

                    $end = $gStart->copy()->addMinutes($totalMins);

                    /* claim the room/device for the window we settled on */
                    $resourceId = null;
                    $primary    = $rows->first()['svc'];
                    if ($allocator->requiresResource($primary)) {
                        $resource   = $allocator->findFree($primary, $gStart, $end, null, true);
                        $resourceId = $resource?->id;
                    }

                    /* resolve / create the CRM customer */
                    $customer = null;
                    if (! empty($guest['customer_id'])) {
                        $customer = Customer::find($guest['customer_id']);
                    } elseif (! empty($guest['phone'])) {
                        $customer = Customer::firstOrCreate(
                            ['phone' => $guest['phone']],
                            ['name'  => $guestName]
                        );
                    }

                    $appointment = Appointment::create([
                        'booking_group_id' => $groupId,
                        'company_id'       => $company->id,
                        'branch_id'        => $branchId,
                        'service_id'       => $primary->id,
                        'employee_id'      => $rows->first()['empId'],
                        'resource_id'      => $resourceId,
                        'customer_id'      => $customer?->id,
                        'customer_name'    => $customer?->name ?? $guestName,
                        'customer_phone'   => $customer?->phone ?? ($guest['phone'] ?? null),
                        'start_time'       => $gStart,
                        'end_time'         => $end,
                        'status'           => AppointmentStatus::Confirmed,
                        'total_price'      => $rows->sum('price'),
                        'payment_status'   => 'pending',
                    ]);

                    $cursor = $gStart->copy();
                    foreach ($rows as $i => $row) {
                        $svcEnd = $cursor->copy()->addMinutes($row['duration']);
                        AppointmentService::create([
                            'appointment_id' => $appointment->id,
                            'service_id'     => $row['svc']->id,
                            'employee_id'    => $row['empId'],
                            'price'          => $row['price'],
                            'currency'       => $row['svc']->currency ?? $currency,
                            'start_time'     => $cursor,
                            'end_time'       => $svcEnd,
                            'sort_order'     => $i,
                        ]);
                        $cursor = $svcEnd;
                    }

                    $ids[] = [
                        'id'    => $appointment->id,
                        'name'  => $appointment->customer_name,
                        'start' => $gStart->format('Y-m-d\TH:i:s'),
                        'end'   => $end->format('Y-m-d\TH:i:s'),
                        /* true when this guest had to follow a party-mate rather
                           than start with them — the UI should say so */
                        'moved' => ! $gStart->equalTo($start),
                    ];
                }

                return $ids;
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode() >= 400 ? $e->getStatusCode() : 422);
        }

        Auditor::log(
            'Group booking created: ' . count($created) . ' guest(s) at ' . $start->format('Y-m-d H:i'),
            Appointment::find($created[0]['id'])
        );

        return response()->json([
            'ok'      => true,
            'ids'     => collect($created)->pluck('id')->all(),
            'guests'  => $created,   /* per-guest placement, incl. anyone shifted later */
            'groupId' => $groupId,
            'count'   => count($created),
        ]);
    }

    /**
     * When is this guest's window blocked until, or null when it is free?
     *
     * Returns the moment the blocker releases, so the caller can slide the guest
     * to the next realistic start instead of giving up. Checks the same three
     * things a single booking checks: blocked time, the assigned employee, and
     * the room/device.
     */
    private function firstBlockerEnd(
        \Illuminate\Support\Collection $rows,
        int $branchId,
        Carbon $start,
        Carbon $end,
        \App\Services\ResourceAllocator $allocator,
    ): ?Carbon {
        $until = null;
        $bump  = function (?Carbon $candidate) use (&$until) {
            if ($candidate && (! $until || $candidate->gt($until))) {
                $until = $candidate->copy();
            }
        };

        /* blocked-time window over the guest's whole run */
        if ($block = $this->blockedConflict($branchId, $rows->first()['empId'], $start, $end)) {
            $bump($block->end_time);
        }

        /* each assigned employee, over their own sub-window */
        $probe = $start->copy();
        foreach ($rows as $row) {
            $probeEnd = $probe->copy()->addMinutes($row['duration']);
            if ($row['empId'] && ($c = $this->employeeConflict($row['empId'], $probe, $probeEnd))) {
                $bump($c->end_time);
            }
            $probe = $probeEnd;
        }

        /* the room/device for the whole window */
        $primary = $rows->first()['svc'];
        if ($allocator->requiresResource($primary) && ! $allocator->findFree($primary, $start, $end, null, true)) {
            /* nextFreeAt can be null when every candidate is inactive/absent —
               fall back to the end of this window so the loop still advances */
            $bump($allocator->nextFreeAt($primary, $start, $end) ?? $end);
        }

        return $until;
    }

    /** Why a guest could not be placed — reuses the same wording as single booking. */
    private function placementMessage(
        \Illuminate\Support\Collection $rows,
        int $branchId,
        Carbon $start,
        Carbon $end,
        \App\Services\ResourceAllocator $allocator,
        string $guestName,
    ): string {
        if ($block = $this->blockedConflict($branchId, $rows->first()['empId'], $start, $end)) {
            return $this->blockedMessage($block) . ' — ' . $guestName;
        }

        $probe = $start->copy();
        foreach ($rows as $row) {
            $probeEnd = $probe->copy()->addMinutes($row['duration']);
            if ($row['empId'] && ($c = $this->employeeConflict($row['empId'], $probe, $probeEnd))) {
                return $this->employeeConflictMessage($c) . ' — ' . $guestName;
            }
            $probe = $probeEnd;
        }

        $primary = $rows->first()['svc'];
        if ($allocator->requiresResource($primary)) {
            return $allocator->conflictMessage($primary, $start, $end) . ' — ' . $guestName;
        }

        return __('This time is not available.') . ' — ' . $guestName;
    }

    /**
     * Ajax: move an appointment to another employee and/or another start time (drag & drop).
     */
    public function reschedule(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorise($appointment);

        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'start_time'  => ['required', 'date'],
            'duration'    => ['nullable', 'integer', 'min:5', 'max:1440'],
        ]);

        $newEmployeeId = $data['employee_id'] ?: null;
        if ($newEmployeeId) {
            abort_unless($this->employeeBelongsToCompany($newEmployeeId), 403);
        }

        $oldEmployeeId = $appointment->employee_id;
        $oldStart      = $appointment->start_time->copy();
        $newStart      = Carbon::parse($data['start_time']);
        $deltaMinutes  = (int) round($oldStart->diffInMinutes($newStart, false));
        $duration      = isset($data['duration']) ? (int) $data['duration'] : null;

        $allocator = app(\App\Services\ResourceAllocator::class);

        DB::transaction(function () use ($appointment, $newEmployeeId, $oldEmployeeId, $newStart, $deltaMinutes, $duration, $allocator) {
            $newEnd = $duration
                ? $newStart->copy()->addMinutes($duration)
                : $appointment->end_time?->copy()->addMinutes($deltaMinutes);

            // The new slot must not land inside a blocked-time window
            if ($newEnd && ($block = $this->blockedConflict((int) $appointment->branch_id, $newEmployeeId ? (int) $newEmployeeId : null, $newStart, $newEnd))) {
                throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                    'ok'      => false,
                    'message' => $this->blockedMessage($block),
                ], 422));
            }

            // Re-allocate the room/device for the new window (may pick a different free one)
            $resourceId = $appointment->resource_id;
            $service    = $appointment->service;
            if ($service && $newEnd && $allocator->requiresResource($service)) {
                $resource = $allocator->findFree($service, $newStart, $newEnd, $appointment->id, true);
                if (! $resource) {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                        'ok'      => false,
                        'message' => $allocator->conflictMessage($service, $newStart, $newEnd),
                    ], 422));
                }
                $resourceId = $resource->id;
            }

            $appointment->update([
                'employee_id' => $newEmployeeId,
                'resource_id' => $resourceId,
                'start_time'  => $newStart,
                'end_time'    => $newEnd,
            ]);

            // Shift pivot services by the same delta and follow the employee change
            $services = $appointment->appointmentServices;
            foreach ($services as $svc) {
                $svc->update([
                    'start_time'  => $svc->start_time?->copy()->addMinutes($deltaMinutes),
                    'end_time'    => $svc->end_time?->copy()->addMinutes($deltaMinutes),
                    'employee_id' => $svc->employee_id == $oldEmployeeId ? $newEmployeeId : $svc->employee_id,
                ]);
            }
            // Resize: when the appointment has a single service, keep it in sync
            if ($duration && $services->count() === 1) {
                $only = $services->first();
                $only->update(['end_time' => $only->start_time?->copy()->addMinutes($duration)]);
            }
        });

        Auditor::logChange(
            "Appointment #{$appointment->id} rescheduled",
            $appointment,
            ['employee_id' => $oldEmployeeId, 'start_time' => $oldStart->format('Y-m-d H:i')],
            ['employee_id' => $newEmployeeId, 'start_time' => $newStart->format('Y-m-d H:i')],
        );

        return response()->json([
            'ok'    => true,
            'start' => $appointment->start_time->format('Y-m-d\TH:i:s'),
            'end'   => $appointment->end_time?->format('Y-m-d\TH:i:s'),
        ]);
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $this->authorise($appointment);

        $data = $request->validate([
            'status'           => ['required', Rule::enum(AppointmentStatus::class)],
            // Which moves need a reason is the state machine's call, not a
            // validation rule's — TransitionAppointment enforces it.
            'reason'           => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            // Payment fields (only meaningful when completing)
            'paid_amount'      => ['nullable', 'numeric', 'min:0'],
            'payment_method'   => ['nullable', 'in:cash,card,later'],
            'pay_notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $company = $this->company();
        $target  = AppointmentStatus::from($data['status']);
        $reason  = $data['reason'] ?? $data['rejection_reason'] ?? null;

        $settlePayment = $target === AppointmentStatus::Completed && $request->filled('paid_amount');

        try {
            // One transaction over the status change AND the money it moves.
            // These used to run unwrapped: a failure halfway through left the
            // appointment closed with the cash register out of balance.
            DB::transaction(function () use ($appointment, $target, $company, $reason, $data, $settlePayment) {
                if ($settlePayment) {
                    $appointment->update([
                        'payment_status' => $this->paymentStatusFor(
                            (float) $appointment->total_price,
                            (float) $data['paid_amount'],
                        ),
                    ]);
                }

                app(TransitionAppointment::class)(
                    $appointment,
                    $target,
                    TransitionActor::Company,
                    [
                        'actorId'   => $company->id,
                        'actorName' => $company->localizedName(),
                        'reason'    => $reason,
                        'meta'      => $settlePayment
                            ? ['paid' => (float) $data['paid_amount'], 'method' => $data['payment_method'] ?? 'cash']
                            : null,
                    ],
                );

                if ($settlePayment) {
                    $this->recordSettlement($appointment, $company, $data);
                }
            });
        } catch (IllegalStatusTransition $e) {
            $message = __('That status change is not allowed from the appointment\'s current state.');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message, 'detail' => $e->getMessage()], 422);
            }

            return back()->with('error', $message);
        }

        // Invoicing stays outside the transaction: a failure here must not undo
        // a completed appointment or its payment. It is logged, not swallowed —
        // the previous empty catch made missing invoices invisible.
        if ($target === AppointmentStatus::Completed && ! $appointment->invoice()->exists()) {
            try {
                $appointment->load(['service', 'employee', 'appointmentServices.service', 'appointmentServices.employee']);
                InvoiceController::buildInvoiceFromAppointment($appointment, $company);
            } catch (\Throwable $e) {
                Log::error('Invoice generation failed for appointment', [
                    'appointment_id' => $appointment->id,
                    'exception'      => $e,
                ]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'status'  => $appointment->status->value,
                'allowed' => array_map(
                    fn (AppointmentStatus $s) => $s->value,
                    AppointmentStateMachine::allowedFor($appointment->status, TransitionActor::Company),
                ),
            ]);
        }

        return redirect()
            ->route('company.appointments.show', $appointment)
            ->with('success', __('Appointment status updated.'));
    }

    /** paid vs charged → the payment_status the appointment should carry. */
    private function paymentStatusFor(float $charged, float $paid): string
    {
        return match (true) {
            $paid <= 0       => 'pending',
            $paid >= $charged => 'paid',
            default          => 'partial',
        };
    }

    /**
     * Write the cash-register entries for a completed appointment: the payment
     * itself, plus whichever side the difference falls on.
     *
     * Caller must already be inside a transaction.
     */
    private function recordSettlement(Appointment $appointment, \App\Models\Company $company, array $data): void
    {
        $charged  = (float) $appointment->total_price;
        $paid     = (float) $data['paid_amount'];
        $currency = $appointment->service?->currency ?? config('booksy.default_currency', 'SYP');
        $method   = $data['payment_method'] ?? 'cash';

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

        $diff = round($paid - $charged, 2);

        if ($diff > 0) {
            $overpayTo = $appointment->branch?->overpayment_to ?? 'treasury';

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

            return;
        }

        if ($diff < 0 && $appointment->customer_id) {
            CustomerDebt::create([
                'company_id'      => $company->id,
                'branch_id'       => $appointment->branch_id,
                'customer_id'     => $appointment->customer_id,
                'appointment_id'  => $appointment->id,
                'original_amount' => abs($diff),
                'currency'        => $currency,
                'status'          => 'unpaid',
                'notes'           => __('Underpayment — customer debt recorded'),
                'created_by_name' => $company->localizedName(),
            ]);
        }
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

        /* Status filtering — the UI sends its active pills as a comma list
           (present-but-empty means every pill is off → return nothing) */
        if ($request->has('statuses')) {
            $valid = array_column(AppointmentStatus::cases(), 'value');
            $list  = array_values(array_intersect(explode(',', (string) $request->input('statuses')), $valid));
            $query->whereIn('status', $list ?: ['__none__']);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        /* ── month view: one aggregate count per day instead of individual events ── */
        if ($request->boolean('aggregate')) {
            $rows = (clone $query)
                ->selectRaw('DATE(start_time) as day, status, COUNT(*) as total')
                ->groupBy('day', 'status')
                ->get();

            $events = $rows->toBase()->groupBy('day')->map(function ($group, $day) {
                return [
                    'id'              => 'daycount-' . $day,
                    'start'           => $day,
                    'allDay'          => true,
                    'display'         => 'block',
                    'title'           => (string) $group->sum('total'),
                    'backgroundColor' => 'transparent',
                    'borderColor'     => 'transparent',
                    'extendedProps'   => [
                        'type'     => 'day-count',
                        'count'    => (int) $group->sum('total'),
                        'byStatus' => $group->pluck('total', 'status')->map(fn ($v) => (int) $v),
                        'day'      => $day,
                    ],
                ];
            })->values();
        } else {
        /* ── day/week views: full events, only the columns the calendar renders ── */
        $query->select([
                'id', 'branch_id', 'customer_id', 'employee_id', 'service_id', 'resource_id',
                'start_time', 'end_time', 'status', 'total_price', 'booking_group_id',
                'status_changed_by_name', 'status_changed_at', 'status_previous',
            ])
            ->with([
                'branch:id,name_en,name_ar',
                'customer:id,name,phone',
                'service:id,name_en,name_ar,currency',
                'employee:id,name_en,name_ar,image',
                'resource:id,name_en,name_ar',
            ]);

        /* route() is expensive at thousands of rows — build URLs from templates */
        $showTpl   = route('company.appointments.show', '__ID__');
        $updateTpl = route('company.appointments.update-status', '__ID__');
        $defaultCurrency = config('booksy.default_currency', 'SYP');

        $events = $query->get()->toBase()->map(function (Appointment $appt) use ($showTpl, $updateTpl, $defaultCurrency) {
            $colors  = ['bg' => $appt->status->color(), 'text' => '#ffffff'];
            $showUrl = str_replace('__ID__', (string) $appt->id, $showTpl);
            $title   = trim(
                ($appt->customer?->name ?? __('Customer')) . ' · ' .
                ($appt->service?->localizedName() ?? '')
            );

            return [
                'id'              => $appt->id,
                'title'           => $title,
                'start'           => $appt->start_time?->format('Y-m-d\TH:i:s'),
                'end'             => $appt->end_time?->format('Y-m-d\TH:i:s'),
                'url'             => $showUrl,
                'backgroundColor' => $colors['bg'],
                'borderColor'     => $colors['bg'],
                'textColor'       => $colors['text'],
                'extendedProps'   => [
                    'type'       => 'appointment',
                    'group'      => (bool) $appt->booking_group_id,
                    'status'     => $appt->status,
                    'branch'     => $appt->branch?->localizedName() ?? '—',
                    'service'    => $appt->service?->localizedName() ?? '—',
                    'employee'   => $appt->employee?->localizedName() ?? '—',
                    'resource'   => $appt->resource?->localizedName(),
                    'employeeId'    => $appt->employee_id,
                    'employeeImage' => $appt->employee?->image ? asset('storage/' . $appt->employee->image) : null,
                    'price'         => number_format((float) $appt->total_price, 2),
                    'currency'      => $appt->service?->currency ?? $defaultCurrency,
                    'customerPhone' => $appt->customer?->phone,
                    'updateUrl'     => str_replace('__ID__', (string) $appt->id, $updateTpl),
                    'showUrl'    => $showUrl,
                    'changedBy'  => $appt->status_changed_by_name,
                    'changedAt'  => $appt->status_changed_at?->format('Y-m-d\TH:i:s'),
                    'prevStatus' => $appt->status_previous,
                ],
            ];
        });
        }

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

        /* ── blocked-time windows as first-class (deletable) events ── */
        $blockedEvents = BlockedTime::query()
            ->where('company_id', $company->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->overlapping($rangeStart, $rangeEndD)
            ->with(['employee:id,name_en,name_ar', 'branch:id,name_en,name_ar'])
            ->get()
            ->map(fn (BlockedTime $b) => [
                'id'              => 'blocked-' . $b->id,
                'title'           => $b->reason ?: __('Blocked time'),
                'start'           => $b->start_time->format('Y-m-d\TH:i:s'),
                'end'             => $b->end_time->format('Y-m-d\TH:i:s'),
                'backgroundColor' => 'rgba(100,116,139,.30)',
                'borderColor'     => 'rgba(148,163,184,.55)',
                'textColor'       => '#cbd5e1',
                'editable'        => false,
                'extendedProps'   => [
                    'type'     => 'blocked',
                    'blockId'  => $b->id,
                    'reason'   => $b->reason,
                    'employee' => $b->employee?->localizedName(),
                    'branch'   => $b->branch?->localizedName(),
                ],
            ]);

        return response()->json($events->merge($bgEvents)->merge($blockedEvents)->values());
    }

    /** Ajax: create a blocked-time window (whole branch when employee_id is empty). */
    public function storeBlockedTime(Request $request): JsonResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'branch_id'   => ['required', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'date'        => ['required', 'date_format:Y-m-d'],
            'from'        => ['required', 'date_format:H:i'],
            'to'          => ['required', 'date_format:H:i', 'after:from'],
            'reason'      => ['nullable', 'string', 'max:190'],
        ]);

        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);
        if (! empty($data['employee_id'])) {
            abort_unless($this->employeeBelongsToCompany((int) $data['employee_id']), 403);
        }

        $block = BlockedTime::create([
            'company_id'  => $company->id,
            'branch_id'   => (int) $data['branch_id'],
            'employee_id' => $data['employee_id'] ?: null,
            'start_time'  => Carbon::parse($data['date'] . ' ' . $data['from']),
            'end_time'    => Carbon::parse($data['date'] . ' ' . $data['to']),
            'reason'      => $data['reason'] ?? null,
        ]);

        Auditor::log("Blocked time #{$block->id} ({$block->start_time->format('Y-m-d H:i')} → {$block->end_time->format('H:i')})", $block);

        return response()->json(['ok' => true, 'id' => $block->id]);
    }

    /** Ajax: blocked-time windows for one day (block-time modal list). */
    public function listBlockedTimes(Request $request): JsonResponse
    {
        $company = $this->company();
        $date    = Carbon::parse($request->input('date', now()->toDateString()));

        $rows = BlockedTime::query()
            ->where('company_id', $company->id)
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->overlapping($date->copy()->startOfDay(), $date->copy()->endOfDay())
            ->with(['employee:id,name_en,name_ar', 'branch:id,name_en,name_ar'])
            ->orderBy('start_time')
            ->get()
            ->map(fn (BlockedTime $b) => [
                'id'       => $b->id,
                'from'     => $b->start_time->format('H:i'),
                'to'       => $b->end_time->format('H:i'),
                'reason'   => $b->reason,
                'employee' => $b->employee?->localizedName(),
                'branch'   => $b->branch?->localizedName(),
            ]);

        return response()->json(['ok' => true, 'blocks' => $rows]);
    }

    /** Ajax: remove a blocked-time window. */
    public function destroyBlockedTime(BlockedTime $blockedTime): JsonResponse
    {
        abort_unless($blockedTime->company_id === $this->company()->id, 403);

        Auditor::log("Removed blocked time #{$blockedTime->id}", $blockedTime);
        $blockedTime->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Ajax: live appointment statistics for the V2 stats deck.
     *
     * Buckets update with the chosen window (today | week | month | custom),
     * except "ongoing" (right now) and "waitlist" (current queue) which are
     * always live. Counts come from one grouped query plus a couple of cheap
     * index-backed counts so the deck stays fast on large datasets.
     */
    public function stats(Request $request): JsonResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'range'     => ['nullable', 'in:today,week,month,custom'],
            'from'      => ['nullable', 'date_format:Y-m-d'],
            'to'        => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $range    = $data['range'] ?? 'today';
        $branchId = $data['branch_id'] ?? null;

        // ── resolve the window ───────────────────────────────────────────
        $now = now();
        switch ($range) {
            case 'week':
                $from = $now->copy()->startOfWeek();
                $to   = $now->copy()->endOfWeek();
                break;
            case 'month':
                $from = $now->copy()->startOfMonth();
                $to   = $now->copy()->endOfMonth();
                break;
            case 'custom':
                $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $now->copy()->startOfDay();
                $to   = isset($data['to'])   ? Carbon::parse($data['to'])->endOfDay()     : $from->copy()->endOfDay();
                if ($to->lt($from)) { [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()]; }
                break;
            default: // today
                $from = $now->copy()->startOfDay();
                $to   = $now->copy()->endOfDay();
                $range = 'today';
        }

        $base = fn () => Appointment::query()
            ->where('company_id', $company->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        // ── status counts + expected revenue within the window (1 query) ──
        $rows = $base()
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('status, COUNT(*) AS c, SUM(total_price) AS revenue')
            ->groupBy('status')
            ->get();

        $byStatus = $rows->pluck('c', 'status');
        $total    = (int) $rows->sum('c');

        // revenue counts only the statuses that still hold their slot —
        // cancellations and no-shows earn nothing
        $revenue = (float) $rows->whereIn('status', AppointmentStatus::blockingValues())->sum('revenue');

        // upcoming = confirmed and still in the future, inside the window
        $upcoming = (int) $base()
            ->whereBetween('start_time', [$from, $to])
            ->where('status', AppointmentStatus::Confirmed->value)
            ->where('start_time', '>', $now)
            ->count();

        // ongoing = the clock says it is happening now (window-independent)
        $ongoing = (int) $base()
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->where('start_time', '<=', $now)
            ->where('end_time', '>', $now)
            ->count();

        // on site = the customer is actually in the salon, per check-in.
        // Unlike $ongoing this is a fact reception recorded, not a guess from
        // the clock — it is what the "who is here now" board should show.
        $onSite = (int) $base()
            ->whereIn('status', array_map(
                fn (AppointmentStatus $s) => $s->value,
                array_filter(AppointmentStatus::cases(), fn (AppointmentStatus $s) => $s->isOnSite()),
            ))
            ->count();

        // today's total (window-independent quick glance)
        $todayCount = $range === 'today'
            ? $total
            : (int) $base()
                ->whereBetween('start_time', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
                ->count();

        // current waiting queue (window-independent) — null when the plan lacks the module
        $waitlist = $company->hasFeature('waitlist')
            ? (int) WaitlistEntry::query()
                ->where('company_id', $company->id)
                ->where('status', 'waiting')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->count()
            : null;

        return response()->json([
            'ok'    => true,
            'range' => $range,
            'from'  => $from->format('Y-m-d'),
            'to'    => $to->format('Y-m-d'),
            'fromLabel' => $from->isoFormat('D MMM'),
            'toLabel'   => $to->isoFormat('D MMM'),
            'currency'  => config('booksy.default_currency', 'SYP'),
            'hasWaitlist' => $waitlist !== null,
            'stats' => [
                'total'     => $total,
                'today'     => $todayCount,
                'pending'   => (int) ($byStatus[AppointmentStatus::Pending->value] ?? 0),
                'upcoming'  => $upcoming,
                'ongoing'   => $ongoing,
                'onSite'    => $onSite,
                'awaitingPayment' => (int) ($byStatus[AppointmentStatus::AwaitingPayment->value] ?? 0),
                'completed' => (int) ($byStatus[AppointmentStatus::Completed->value] ?? 0),
                'noShow'    => (int) ($byStatus[AppointmentStatus::NoShow->value] ?? 0),
                'cancelled' => (int) collect(AppointmentStatus::cancelled())
                    ->sum(fn (AppointmentStatus $s) => (int) ($byStatus[$s->value] ?? 0)),
                'waitlist'  => $waitlist,
                'revenue'   => $revenue,
            ],
        ]);
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
            /* company staff incl. owners without a branch */
            ->where(function ($q) use ($company) {
                $q->where('company_id', $company->id)
                  ->orWhereHas('branch', fn ($b) => $b->where('company_id', $company->id));
            })
            ->where('is_active', true)
            ->where('is_bookable', true) /* only service providers take appointments */
            ->orderBy('name_en');

        if ($branchId) {
            /* branch staff + company-wide providers (owners with no branch) */
            $empQuery->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
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

        $employeesRaw = $empQuery->get();

        /* per-employee shifts for this weekday (falls back to branch hours) */
        $empHours = \DB::table('employee_working_hours')
            ->whereIn('employee_id', $employeesRaw->pluck('id'))
            ->where('day_of_week', $dow)
            ->get()
            ->groupBy('employee_id');

        /* approved leaves covering this date (full-day or hourly) */
        $leaves = \DB::table('employee_leaves')
            ->whereIn('employee_id', $employeesRaw->pluck('id'))
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $parsedDate->toDateString())
            ->whereDate('end_date', '>=', $parsedDate->toDateString())
            ->get()
            ->groupBy('employee_id');

        /* blocked-time windows overlapping this date → rendered as unavailable stripes */
        $dayStartRef = $parsedDate->copy()->startOfDay();
        $dayBlocks = BlockedTime::query()
            ->where('company_id', $company->id)
            ->whereIn('branch_id', $branchIdsForWH)
            ->overlapping($dayStartRef, $parsedDate->copy()->endOfDay())
            ->get()
            ->map(function (BlockedTime $b) use ($dayStartRef) {
                $from = max(0, (int) $dayStartRef->diffInMinutes($b->start_time, false));
                $to   = min(1440, (int) $dayStartRef->diffInMinutes($b->end_time, false));
                return ['from' => $from, 'to' => $to, 'empId' => $b->employee_id, 'branchId' => $b->branch_id];
            })
            ->filter(fn ($r) => $r['to'] > $r['from'])
            ->values();

        $employees = $employeesRaw->map(function ($emp) use ($closedSlots, $empHours, $leaves, $dayBlocks) {
            $rows  = $empHours->get($emp->id);
            $slots = $rows && $rows->count() ? $this->closedFromShifts($rows) : $closedSlots;

            foreach ($leaves->get($emp->id, collect()) as $lv) {
                if ($lv->is_hourly && $lv->start_hour && $lv->end_hour) {
                    $slots[] = ['from' => $this->timeToMin($lv->start_hour), 'to' => $this->timeToMin($lv->end_hour)];
                } else {
                    $slots = [['from' => 0, 'to' => 1440]]; // full-day leave
                    break;
                }
            }

            /* blocked-time windows: employee-specific, or branch-wide (empId null) */
            foreach ($dayBlocks as $blk) {
                $applies = $blk['empId']
                    ? (int) $blk['empId'] === (int) $emp->id
                    : ($emp->branch_id === null || (int) $emp->branch_id === (int) $blk['branchId']);
                if ($applies) {
                    $slots[] = ['from' => $blk['from'], 'to' => $blk['to']];
                }
            }

            $initials = collect(explode(' ', trim($emp->localizedName())))
                ->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
            return [
                'id'          => $emp->id,
                'name'        => $emp->localizedName(),
                'initials'    => $initials,
                'image'       => $emp->image ? asset('storage/' . $emp->image) : null,
                'branchId'    => $emp->branch_id,
                'closedSlots' => array_values($slots),
            ];
        });

        // Walk-in slot — last, so real providers come first on narrow screens
        $walkInClosed = $closedSlots;
        foreach ($dayBlocks as $blk) {
            if (! $blk['empId']) {
                $walkInClosed[] = ['from' => $blk['from'], 'to' => $blk['to']];
            }
        }
        $slots = $employees->concat([[
            'id' => 0, 'name' => __('Walk-in'), 'initials' => 'WI', 'closedSlots' => array_values($walkInClosed),
        ]]);

        // Get appointments for that date (full UTC day range)
        $dayStart = $parsedDate->copy()->startOfDay();
        $dayEnd   = $parsedDate->copy()->endOfDay();

        $apptQuery = Appointment::query()
            ->where('company_id', $company->id)
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->select([
                'id', 'branch_id', 'customer_id', 'employee_id', 'service_id',
                'start_time', 'end_time', 'status', 'total_price', 'status_changed_by_name',
            ])
            ->with([
                'customer:id,name',
                'service:id,name_en,name_ar',
                'employee:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
            ]);

        if ($branchId) {
            $apptQuery->where('branch_id', $branchId);
        }

        $showTpl = route('company.appointments.show', '__ID__');

        $appointments = $apptQuery->get()->map(function (Appointment $a) use ($showTpl) {
            // Convert to local browser-equivalent: send as local wall-clock minutes
            // We send start_time as-is; JavaScript will compute minutes from ISO string in browser TZ
            return [
                'id'          => $a->id,
                'employeeId'  => $a->employee_id ?? 0,
                'customer'    => $a->customer?->name ?? __('Customer'),
                'service'     => $a->service?->localizedName() ?? '—',
                'branch'      => $a->branch?->localizedName() ?? '—',
                'employee'    => $a->employee?->localizedName() ?? __('Walk-in'),
                'status'      => $a->status->value,
                'color'       => $a->status->color(),
                'price'       => number_format((float) $a->total_price, 2),
                'startIso'    => $a->start_time?->format('Y-m-d\TH:i:s'),
                'endIso'      => $a->end_time?->format('Y-m-d\TH:i:s'),
                'startLabel'  => $a->start_time?->format('h:i A'),
                'endLabel'    => $a->end_time?->format('h:i A'),
                'changedBy'   => $a->status_changed_by_name,
                'showUrl'     => str_replace('__ID__', (string) $a->id, $showTpl),
            ];
        });

        return response()->json([
            'staff'           => $slots->values(),
            'appointments'    => $appointments->values(),
            'defaultBranchId' => $branchId ?: ($branchIdsForWH[0] ?? null),
        ]);
    }

    private function timeToMin(string $time): int
    {
        [$h, $m] = explode(':', $time);
        return (int)$h * 60 + (int)$m;
    }

    /** Closed minute-ranges of a day = complement of the employee's working shifts. */
    private function closedFromShifts($rows): array
    {
        $spans = collect($rows)
            ->filter(fn ($r) => $r->is_working && $r->start_time && $r->end_time)
            ->map(fn ($r) => ['from' => $this->timeToMin($r->start_time), 'to' => $this->timeToMin($r->end_time)])
            ->filter(fn ($s) => $s['to'] > $s['from'])
            ->sortBy('from')->values();

        if ($spans->isEmpty()) {
            return [['from' => 0, 'to' => 1440]]; // day off
        }

        // merge overlapping shifts
        $merged = [];
        foreach ($spans as $s) {
            $last = count($merged) - 1;
            if ($last >= 0 && $s['from'] <= $merged[$last]['to']) {
                $merged[$last]['to'] = max($merged[$last]['to'], $s['to']);
            } else {
                $merged[] = $s;
            }
        }

        // complement of shifts = closed zones (includes gaps between shifts)
        $closed = [];
        $cursor = 0;
        foreach ($merged as $s) {
            if ($s['from'] > $cursor) $closed[] = ['from' => $cursor, 'to' => $s['from']];
            $cursor = max($cursor, $s['to']);
        }
        if ($cursor < 1440) $closed[] = ['from' => $cursor, 'to' => 1440];

        return $closed;
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
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->localizedName(),
                'price'    => $s->price,
                'duration' => $s->duration_minutes,
                'category' => $s->serviceCategory?->localizedName(),
                'currency' => $s->currency ?? config('booksy.default_currency', 'SYP'),
            ]);

        $employees = \App\Models\Employee::query()
            ->where('is_active', true)
            ->where('is_bookable', true)
            /* branch staff + company-wide providers (owners with no branch) */
            ->where(function ($q) use ($company, $branch) {
                $q->where('branch_id', $branch->id)
                  ->orWhere(fn ($w) => $w->whereNull('branch_id')->where('company_id', $company->id));
            })
            ->orderBy('name_en')->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->localizedName()]);

        return response()->json(compact('services', 'employees'));
    }
}
