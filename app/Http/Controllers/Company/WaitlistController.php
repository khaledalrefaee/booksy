<?php

namespace App\Http\Controllers\Company;

use App\Enums\AppointmentStatus;
use App\Enums\CustomerTier;
use App\Enums\WaitlistPriority;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WaitlistEntry;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WaitlistController extends Controller
{
    use \App\Http\Controllers\Company\Concerns\ChecksBookingConflicts;

    protected function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    /** Ajax: waiting entries (newest preferred time first). */
    public function index(Request $request): JsonResponse
    {
        $company = $this->company();

        $branchIds = $company->branches()->pluck('id');

        $entries = WaitlistEntry::query()
            ->where('company_id', $company->id)
            ->where('status', 'waiting')
            // Yesterday's queue must not linger on today's screen.
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->with([
                'branch:id,name_en,name_ar',
                'service:id,name_en,name_ar,duration_minutes',
                'preferredEmployee:id,name_en,name_ar',
                'customer' => fn ($c) => $c->select('id', 'name', 'phone', 'tag')
                    ->withCount(['appointments as visits_count' => fn ($a) => $a
                        ->whereIn('branch_id', $branchIds)
                        ->where('status', AppointmentStatus::Completed->value)]),
            ])
            // Urgent first, then whoever has waited longest. The old ordering
            // (preferred_start, nulls last) buried every walk-in with no
            // particular time in mind — the people actually standing there.
            ->orderBy('priority')
            ->orderBy('created_at')
            ->limit(100)
            ->get()
            ->map(function (WaitlistEntry $w) {
                $tier = $w->customer?->tier() ?? CustomerTier::New;

                return [
                    'id'        => $w->id,
                    'name'      => $w->displayName(),
                    'phone'     => $w->customer_phone ?? $w->customer?->phone,
                    'branch'    => $w->branch?->localizedName(),
                    'branchId'  => $w->branch_id,
                    'service'   => $w->service?->localizedName(),
                    'serviceId' => $w->service_id,
                    'employee'  => $w->preferredEmployee?->localizedName(),
                    'preferred' => $w->preferred_start?->format('Y-m-d H:i'),
                    'notes'     => $w->notes,
                    'priority'  => $w->priority->value,
                    'minutes'   => $w->estimated_minutes ?? $w->service?->duration_minutes,
                    'waited'    => $w->waitedMinutes(),
                    'waitingSince' => $w->created_at->diffForHumans(),
                    'tier'      => [
                        'value' => $tier->value,
                        'label' => $tier->label(),
                        'color' => $tier->color(),
                        'icon'  => $tier->iconPath(),
                    ],
                ];
            });

        return response()->json(['ok' => true, 'entries' => $entries]);
    }

    /** Ajax: add a walk-in / phone customer to the waitlist. */
    public function store(Request $request): JsonResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'branch_id'         => ['required', 'integer'],
            // Either an existing customer, or just a name for a walk-in.
            'customer_id'       => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'     => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_phone'    => ['nullable', 'string', 'max:30'],
            'service_id'        => ['nullable', 'integer', 'exists:services,id'],
            'preferred_employee_id' => ['nullable', 'integer'],
            'priority'          => ['nullable', Rule::enum(WaitlistPriority::class)],
            'estimated_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'preferred_start'   => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        if (! empty($data['preferred_employee_id'])) {
            abort_unless($this->employeeBelongsToCompany((int) $data['preferred_employee_id']), 403);
        }

        $customer = null;

        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        } elseif (! empty($data['customer_phone'])) {
            /* Quick-add: reception types a name and phone and moves on. Matching
               on phone keeps the CRM from filling up with duplicates of the same
               walk-in, and means their visit history — and therefore their tier —
               starts accumulating from the first entry. */
            $customer = Customer::firstOrCreate(
                ['phone' => $data['customer_phone']],
                ['name' => $data['customer_name'], 'source' => 'walk_in'],
            );
        }

        /* Default expiry: end of the preferred day, else end of today. A queue
           that never expires is a queue nobody trusts. */
        $preferred = isset($data['preferred_start']) ? Carbon::parse($data['preferred_start']) : null;

        $entry = WaitlistEntry::create([
            'company_id'            => $company->id,
            'branch_id'             => (int) $data['branch_id'],
            'customer_id'           => $customer?->id,
            'customer_name'         => $data['customer_name'] ?? $customer?->name,
            'customer_phone'        => $data['customer_phone'] ?? $customer?->phone,
            'service_id'            => $data['service_id'] ?? null,
            'preferred_employee_id' => $data['preferred_employee_id'] ?? null,
            'status'                => 'waiting',
            'priority'              => $data['priority'] ?? WaitlistPriority::default(),
            'estimated_minutes'     => $data['estimated_minutes'] ?? null,
            'preferred_start'       => $preferred,
            'expires_at'            => ($preferred ?? now())->copy()->endOfDay(),
            'notes'                 => $data['notes'] ?? null,
        ]);

        Auditor::log("Added {$entry->displayName()} to the waitlist", $entry);

        return response()->json([
            'ok' => true,
            'id' => $entry->id,
            // So the drawer can show the badge straight away without a refetch.
            'createdCustomer' => $customer && $customer->wasRecentlyCreated,
        ]);
    }

    /**
     * Ajax: promote a waiting client straight into a real booking.
     *
     * Runs the same blocked-time / employee-busy / resource guards as normal
     * booking, so a promotion can never double-book a slot. Marks the entry
     * booked and links it to the appointment it became.
     */
    public function promote(Request $request, WaitlistEntry $waitlistEntry): JsonResponse
    {
        $company = $this->company();
        abort_unless($waitlistEntry->company_id === $company->id, 403);

        if ($waitlistEntry->status !== 'waiting') {
            return response()->json([
                'ok'      => false,
                'code'    => 'already_resolved',
                'message' => __('This waiting-list entry has already been handled.'),
            ], 409);
        }

        $data = $request->validate([
            'start_time'  => ['required', 'date'],
            'service_id'  => ['nullable', 'integer', 'exists:services,id'],
            'employee_id' => ['nullable', 'integer'],
        ]);

        $serviceId = $data['service_id'] ?? $waitlistEntry->service_id;
        if (! $serviceId) {
            return response()->json([
                'ok'      => false,
                'message' => __('Pick a service before booking this client.'),
            ], 422);
        }

        /* the service must belong to this entry's branch — never trust the posted id */
        $service = \App\Models\Service::whereKey($serviceId)
            ->where('branch_id', $waitlistEntry->branch_id)
            ->first();

        if (! $service) {
            return response()->json([
                'ok'      => false,
                'message' => __('That service is not offered at this branch.'),
            ], 422);
        }

        $employeeId = ($data['employee_id'] ?? null) ?: $waitlistEntry->preferred_employee_id;
        if ($employeeId) {
            abort_unless($this->employeeBelongsToCompany((int) $employeeId), 403);
        }

        $start = Carbon::parse($data['start_time']);
        $end   = $start->copy()->addMinutes($service->duration_minutes);

        if ($block = $this->blockedConflict((int) $waitlistEntry->branch_id, $employeeId ? (int) $employeeId : null, $start, $end)) {
            return response()->json(['ok' => false, 'message' => $this->blockedMessage($block)], 422);
        }

        if ($employeeId && ($conflict = $this->employeeConflict((int) $employeeId, $start, $end))) {
            return response()->json([
                'ok'      => false,
                'code'    => 'employee_busy',
                'message' => $this->employeeConflictMessage($conflict),
            ], 422);
        }

        $allocator  = app(\App\Services\ResourceAllocator::class);
        $resourceId = null;
        if ($allocator->requiresResource($service)) {
            $resource = $allocator->findFree($service, $start, $end, null, true);
            if (! $resource) {
                return response()->json([
                    'ok'      => false,
                    'message' => $allocator->conflictMessage($service, $start, $end),
                ], 422);
            }
            $resourceId = $resource->id;
        }

        /* customer_id now points at `customers` (it used to be an FK to `users`,
           which is why this had to re-find people by phone). Fall back to the
           phone lookup only for entries created before that migration. */
        $customer = $waitlistEntry->customer;

        if (! $customer && $waitlistEntry->customer_phone) {
            $customer = Customer::firstOrCreate(
                ['phone' => $waitlistEntry->customer_phone],
                ['name'  => $waitlistEntry->displayName(), 'source' => 'walk_in']
            );
        }

        $appointment = \Illuminate\Support\Facades\DB::transaction(function () use ($waitlistEntry, $company, $service, $employeeId, $resourceId, $start, $end, $customer) {
            /* re-check under lock: two receptionists may promote the same person at once */
            $fresh = WaitlistEntry::lockForUpdate()->findOrFail($waitlistEntry->id);
            if ($fresh->status !== 'waiting') {
                abort(409, __('This waiting-list entry has already been handled.'));
            }

            $appointment = \App\Models\Appointment::create([
                'company_id'     => $company->id,
                'branch_id'      => $waitlistEntry->branch_id,
                'service_id'     => $service->id,
                'employee_id'    => $employeeId ?: null,
                'resource_id'    => $resourceId,
                'customer_id'    => $customer?->id,
                'customer_name'  => $waitlistEntry->displayName(),
                'customer_phone' => $waitlistEntry->customer_phone,
                'start_time'     => $start,
                'end_time'       => $end,
                'status'         => 'confirmed',
                'total_price'    => $service->finalPrice(),
                'payment_status' => 'pending',
                'notes'          => $waitlistEntry->notes,
            ]);

            \App\Models\AppointmentService::create([
                'appointment_id' => $appointment->id,
                'service_id'     => $service->id,
                'employee_id'    => $employeeId ?: null,
                'price'          => $service->finalPrice(),
                'currency'       => $service->currency ?? config('booksy.default_currency', 'SYP'),
                'start_time'     => $start,
                'end_time'       => $end,
                'sort_order'     => 0,
            ]);

            $fresh->update([
                'status'         => 'booked',
                'appointment_id' => $appointment->id,
            ]);

            return $appointment;
        });

        Auditor::log(
            "Promoted waitlist entry #{$waitlistEntry->id} ({$waitlistEntry->displayName()}) into appointment #{$appointment->id}",
            $appointment
        );

        return response()->json([
            'ok'      => true,
            'id'      => $appointment->id,
            'start'   => $appointment->start_time->format('Y-m-d\TH:i:s'),
            'showUrl' => route('company.appointments.show', $appointment->id),
        ]);
    }

    /** Ajax: resolve an entry — booked (promoted) or cancelled (removed). */
    public function resolve(Request $request, WaitlistEntry $waitlistEntry): JsonResponse
    {
        abort_unless($waitlistEntry->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:booked,cancelled,contacted'],
        ]);

        $waitlistEntry->update(['status' => $data['status']]);
        Auditor::log("Waitlist entry #{$waitlistEntry->id} marked {$data['status']}", $waitlistEntry);

        return response()->json(['ok' => true]);
    }
}
