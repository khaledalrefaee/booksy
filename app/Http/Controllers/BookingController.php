<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentBooked;
use App\Http\Controllers\CustomerAuthController;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * GET /api/booking/slots
     * Returns available time slots for an employee on a given date for a service.
     */
    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date_format:Y-m-d',
            'service_id'  => 'required|exists:services,id',
        ]);

        $employee = Employee::with(['workingHours', 'leaves'])->findOrFail($request->employee_id);
        $service  = Service::with('branch')->findOrFail($request->service_id);
        $branch   = $service->branch;
        $date     = Carbon::parse($request->date)->startOfDay();
        $dayOfWeek = (int) $date->dayOfWeek; // 0=Sun … 6=Sat

        // Branch booking rules (online on/off, same-day, booking window)
        if ($closed = $this->dayBlockReason($branch, $date)) {
            return response()->json([
                'available' => false,
                'reason'    => $closed,
                'message'   => $branch->bookingBlockMessage($closed),
                'slots'     => [],
                'next_date' => $closed === 'online_disabled' ? null : $this->nextAvailableDate($employee, $service),
            ]);
        }

        // Working hours (an employee may have multiple shifts per day). Staff
        // without their own schedule for this weekday work the branch hours —
        // the same rule the dashboard calendar uses.
        $branch->loadMissing('workingHours');
        $shifts = $employee->shiftsOn($dayOfWeek, $branch);

        if ($shifts->isEmpty()) {
            return response()->json([
                'available'    => false,
                'reason'       => 'not_working',
                'message'      => $branch->bookingBlockMessage('not_working'),
                'working_hours'=> null,
                'slots'        => [],
                'next_date'    => $this->nextAvailableDate($employee, $service),
            ]);
        }

        // Check approved leave — full-day leaves block the day, hourly permissions only block their window
        $dayLeaves = $employee->leaves()
            ->where('status', 'approved')
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date',   '>=', $date->toDateString())
            ->get();

        if ($dayLeaves->firstWhere('is_hourly', false)) {
            return response()->json([
                'available' => false,
                'reason'    => 'on_leave',
                'slots'     => [],
                'next_date' => $this->nextAvailableDate($employee, $service, $date->clone()->addDay()),
            ]);
        }

        $hourlyBlocks = $dayLeaves->where('is_hourly', true)
            ->filter(fn ($l) => $l->start_hour && $l->end_hour)
            ->map(fn ($l) => [
                'start' => Carbon::parse($date->toDateString() . ' ' . $l->start_hour),
                'end'   => Carbon::parse($date->toDateString() . ' ' . $l->end_hour),
            ])
            ->values();

        // Existing appointments on this day
        $booked = Appointment::where('employee_id', $employee->id)
            ->whereDate('start_time', $date->toDateString())
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->get(['start_time', 'end_time']);

        // Resource constraint: appointments holding one of the service's rooms/devices
        // (linked directly or through the service's category)
        $resourceIds  = app(\App\Services\ResourceAllocator::class)->candidatesFor($service)->pluck('id');
        $resourceBusy = $resourceIds->isEmpty() ? collect() : Appointment::query()
            ->whereIn('resource_id', $resourceIds)
            ->whereDate('start_time', $date->toDateString())
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->get(['resource_id', 'start_time', 'end_time']);

        // Offer a start every <branch interval> minutes within each shift — breaks
        // between shifts are excluded automatically. The interval only decides
        // WHEN a service may start; each slot still lasts the full service duration.
        $duration = $service->duration_minutes;
        $step     = $branch->bookingRules()['interval'];
        $slots    = [];
        $now      = $branch->localNow();              // branch wall clock
        $earliest = $branch->earliestBookableStart(); // … + minimum notice
        $anyAhead = false;

        foreach ($shifts as $shift) {
            $whStart = Carbon::parse($date->toDateString() . ' ' . $shift['start']);
            $whEnd   = Carbon::parse($date->toDateString() . ' ' . $shift['end']);
            $cursor  = $whStart->clone();

            while ($cursor->clone()->addMinutes($duration)->lte($whEnd)) {
                $slotEnd = $cursor->clone()->addMinutes($duration);

                // Hide slots that already started or fall inside the minimum notice
                if ($cursor->lte($now) || $cursor->lt($earliest)) { $cursor->addMinutes($step); continue; }
                $anyAhead = true;

                $overlaps = $booked->contains(
                    fn($a) => $a->start_time->lt($slotEnd) && $a->end_time->gt($cursor)
                ) || $hourlyBlocks->contains(
                    fn($b) => $b['start']->lt($slotEnd) && $b['end']->gt($cursor)
                );

                // All required rooms/devices taken during this slot?
                if (!$overlaps && $resourceIds->isNotEmpty()) {
                    $busyCount = $resourceBusy
                        ->filter(fn($a) => $a->start_time->lt($slotEnd) && $a->end_time->gt($cursor))
                        ->pluck('resource_id')->unique()->count();
                    $overlaps = $busyCount >= $resourceIds->count();
                }

                if (!$overlaps) {
                    $slots[] = [
                        'time'   => $cursor->format('H:i'),
                        'label'  => $branch->formatTime($cursor),
                        'start'  => $cursor->toDateTimeString(),
                        'end'    => $slotEnd->toDateTimeString(),
                    ];
                }

                $cursor->addMinutes($step);
            }
        }

        return response()->json([
            'available'     => count($slots) > 0,
            // fully_booked only when times were still ahead and all are taken
            'reason'        => count($slots) ? null : ($anyAhead ? 'fully_booked' : 'no_more_today'),
            'message'       => count($slots) ? null : $branch->bookingBlockMessage($anyAhead ? 'fully_booked' : 'no_more_today'),
            'working_hours' => [
                'start'  => $shifts->first()['start'],
                'end'    => $shifts->last()['end'],
                'shifts' => $shifts,
            ],
            'slots'         => $slots,
            'time_format'   => $branch->bookingRules()['time_format'],
            'employee'      => [
                'id'    => $employee->id,
                'name'  => app()->getLocale() === 'ar' ? ($employee->name_ar ?? $employee->name_en) : ($employee->name_en ?? $employee->name_ar),
                'image' => $employee->image ? asset('storage/' . $employee->image) : null,
            ],
        ]);
    }

    /**
     * POST /api/booking/book
     * Creates the appointment with a DB-level lock to prevent double-booking.
     */
    public function book(Request $request): JsonResponse
    {
        $customer = CustomerAuthController::authCustomer();
        if (!$customer) {
            return response()->json(['message' => 'Login required.'], 401);
        }

        $request->validate([
            'service_id'  => 'required|exists:services,id',
            'employee_id' => 'required|exists:employees,id',
            'start_time'  => 'required|date',
            'notes'       => 'nullable|string|max:500',
            'employee_requested' => 'nullable|boolean',
        ]);

        $service   = Service::with('branch.company')->findOrFail($request->service_id);
        $employee  = Employee::findOrFail($request->employee_id);
        $startTime = Carbon::parse($request->start_time);
        $endTime   = $startTime->clone()->addMinutes($service->duration_minutes);

        // Enforce the branch's booking rules on the server too — the slots
        // endpoint already hides disallowed times, but a stale tab or a direct
        // POST must be rejected. "Now" is the branch's own wall clock.
        if ($blocked = $service->branch->bookingBlockReason($startTime)) {
            return response()->json([
                'message'  => $service->branch->bookingBlockMessage($blocked),
                'reason'   => $blocked,
                'past'     => $blocked === 'past',
            ], 422);
        }

        $allocator = app(\App\Services\ResourceAllocator::class);

        // DB transaction + lock to prevent race condition
        // Confirmed straight away, or waiting for the business to approve it
        // (Booking & Cancellation Policy → online booking).
        $status = $service->branch->onlineBookingStatus($customer);

        $appointment = DB::transaction(function () use ($request, $service, $employee, $startTime, $endTime, $customer, $allocator, $status) {

            // Lock check: any overlapping active appointment for this employee?
            $conflict = Appointment::where('employee_id', $employee->id)
                ->whereIn('status', AppointmentStatus::blockingValues())
                ->where('start_time', '<', $endTime)
                ->where('end_time',   '>', $startTime)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                return null; // slot taken
            }

            // Lock check: a required room/device must be free for the window
            $resourceId = null;
            if ($allocator->requiresResource($service)) {
                $resource = $allocator->findFree($service, $startTime, $endTime, null, true);
                if (! $resource) {
                    return 'resource_busy';
                }
                $resourceId = $resource->id;
            }

            return Appointment::create([
                'company_id'   => $service->branch->company_id,
                'branch_id'    => $service->branch_id,
                'customer_id'  => $customer->id,
                'reference'    => Appointment::newReference(),
                'employee_id'  => $employee->id,
                'employee_requested' => (bool) ($request->boolean('employee_requested', true)),
                'resource_id'  => $resourceId,
                'service_id'   => $service->id,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'status'       => $status,
                'total_price'  => $service->price,
                'payment_status'=> 'pending',
                'notes'        => $request->notes,
                'booking_source' => $this->resolveBookingSource($service->branch_id),
            ]);
        });

        if (!$appointment || $appointment === 'resource_busy') {
            return response()->json([
                'message' => $appointment === 'resource_busy'
                    ? $allocator->conflictMessage($service, $startTime, $endTime)
                    : 'This slot was just taken. Please choose another time.',
                'conflict' => true,
            ], 409);
        }

        // Fire real-time event
        try {
            event(new AppointmentBooked($appointment));
        } catch (\Throwable $e) {
            // Broadcasting is optional; don't fail the booking if Reverb is offline
        }

        return response()->json([
            'booked'  => true,
            'confirmed' => $appointment->status === AppointmentStatus::Confirmed, // false = awaiting approval
            'appointment' => [
                'id'         => $appointment->id,
                'start_time' => $appointment->start_time->format('D, d M Y') . ' · ' . $service->branch->formatTime($appointment->start_time),
                'end_time'   => $service->branch->formatTime($appointment->end_time),
                'service'    => app()->getLocale() === 'ar' ? $service->name_ar : $service->name_en,
                'price'      => $appointment->total_price,
                'status'     => $appointment->status,
            ],
        ], 201);
    }

    /**
     * GET /api/booking/group-slots
     * Availability for a whole visit: one or more guests, each with their own
     * services + staff choice. `mode=one` books everything back-to-back on ONE
     * employee; `mode=split` runs guests in parallel with distinct employees.
     */
    public function groupSlots(Request $request): JsonResponse
    {
        $data   = $this->validateSpec($request, false);
        $date   = Carbon::parse($data['date'])->startOfDay();
        $branch = Branch::findOrFail($data['branch_id']);

        if ($closed = $this->dayBlockReason($branch, $date)) {
            return response()->json([
                'available' => false,
                'slots'     => [],
                'reason'    => $closed,
                'message'   => $branch->bookingBlockMessage($closed),
            ]);
        }

        [$employees, $empById, $services, $booked, $gridStart, $gridEnd, $guestBlocks]
            = $this->prepareSpec($data, $date);

        if (!$gridStart) {
            // Nobody works this day. If nobody works ANY day, the venue simply
            // hasn't set its hours yet — say so rather than "closed".
            $reason = $branch->openWeekdays() ? 'closed' : 'no_hours';
            return response()->json([
                'available' => false,
                'slots'     => [],
                'reason'    => $reason,
                'message'   => $branch->bookingBlockMessage($reason),
            ]);
        }

        $allocator = app(\App\Services\ResourceAllocator::class);
        $slots     = [];
        $anyAhead  = false; // was any start time still bookable (not past / too soon)?
        $step      = $branch->bookingRules()['interval'];
        $now       = $branch->localNow();              // branch wall clock
        $earliest  = $branch->earliestBookableStart(); // … + minimum notice

        for ($t = $gridStart->clone(); $t->lte($gridEnd); $t->addMinutes($step)) {
            if ($t->lte($now) || $t->lt($earliest)) continue;
            $anyAhead = true;
            if ($this->resolveAssignment($data['mode'], $guestBlocks, $employees, $empById, $booked, $date, $t, $allocator) !== null) {
                $slots[] = [
                    'time'  => $t->format('H:i'),
                    'label' => $branch->formatTime($t),
                    'start' => $date->toDateString() . ' ' . $t->format('H:i') . ':00',
                ];
            }
        }

        // Empty day: "fully_booked" only when there WERE times ahead and all are
        // taken — that is the one case the waitlist ("notify me") makes sense for.
        // If the day's remaining times have simply passed, say so instead.
        $reason = count($slots) ? null : ($anyAhead ? 'fully_booked' : 'no_more_today');

        return response()->json([
            'available'   => count($slots) > 0,
            'reason'      => $reason,
            'message'     => $reason ? $branch->bookingBlockMessage($reason) : null,
            'slots'       => $slots,
            'time_format' => $branch->bookingRules()['time_format'],
        ]);
    }

    /**
     * POST /api/booking/group-book
     * Atomically creates every appointment for the visit (all guests / services).
     * Re-checks availability under a lock; any conflict rolls the whole thing back.
     */
    public function groupBook(Request $request): JsonResponse
    {
        $customer = CustomerAuthController::authCustomer();
        if (!$customer) {
            return response()->json(['message' => 'Login required.'], 401);
        }

        $data = $this->validateSpec($request, true);
        $date = Carbon::parse($data['start_time'])->startOfDay();
        $start = Carbon::parse($data['start_time']);

        // Reject a visit the branch's booking rules don't allow (past, same-day
        // off, inside the minimum notice, beyond the window, online booking off)
        // — a stale slot or a direct POST. "Now" is the branch's wall clock.
        $branch = Branch::findOrFail($data['branch_id']);
        if ($blocked = $branch->bookingBlockReason($start)) {
            return response()->json([
                'message' => $branch->bookingBlockMessage($blocked),
                'reason'  => $blocked,
                'past'    => $blocked === 'past',
            ], 422);
        }

        // Idempotency: a double-submit / retry that carries the same key must not
        // create a second visit — return the one already made (last 15 min).
        $idemKey = $data['idempotency_key'] ?? null;
        if ($idemKey) {
            $existing = Appointment::where('idempotency_key', $idemKey)
                ->where('customer_id', $customer->id)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->orderBy('start_time')
                ->get();
            if ($existing->isNotEmpty()) {
                return response()->json([
                    'booked'    => true,
                    'duplicate' => true,
                    'confirmed' => $existing->first()->status === AppointmentStatus::Confirmed,
                    'count'     => $existing->count(),
                    'summary'   => [
                        'start' => $existing->first()->start_time->format('D, d M Y') . ' · ' . $existing->first()->branch?->formatTime($existing->first()->start_time),
                        'end'   => $existing->last()->branch?->formatTime($existing->last()->end_time),
                        'total' => (float) $existing->sum('total_price'),
                    ],
                ], 200);
            }
        }

        [$employees, $empById, $services, $booked, $gridStart, $gridEnd, $guestBlocks]
            = $this->prepareSpec($data, $date);

        $allocator = app(\App\Services\ResourceAllocator::class);

        // Confirmed straight away, or waiting for the business to approve it
        // (Booking & Cancellation Policy → online booking).
        $status = $branch->onlineBookingStatus($customer);

        try {
            $created = DB::transaction(function () use ($data, $guestBlocks, $employees, $empById, $date, $start, $allocator, $customer, $idemKey, $status) {
                // Fresh booked map under lock
                $lockedBooked = Appointment::whereIn('employee_id', $employees->pluck('id'))
                    ->whereDate('start_time', $date->toDateString())
                    ->whereIn('status', AppointmentStatus::blockingValues())
                    ->lockForUpdate()
                    ->get(['employee_id', 'start_time', 'end_time'])
                    ->groupBy('employee_id');

                $plan = $this->resolveAssignment($data['mode'], $guestBlocks, $employees, $empById, $lockedBooked, $date, $start, $allocator);
                if ($plan === null) {
                    throw new \RuntimeException('conflict');
                }

                // Link every row of a multi-service / multi-guest visit under one
                // group id so the customer gets a single consolidated message.
                $groupId   = count($plan) > 1 ? Appointment::newGroupId() : null;
                $reference = Appointment::newReference(); // shared human-readable id

                $appts = [];
                foreach ($plan as $job) { // each job: employee_id, service, start, end
                    $resourceId = null;
                    if ($allocator->requiresResource($job['service'])) {
                        $res = $allocator->findFree($job['service'], $job['start'], $job['end'], null, true);
                        if (!$res) throw new \RuntimeException('conflict');
                        $resourceId = $res->id;
                    }
                    $svc = $job['service'];
                        // Guest 0 is the account holder (name resolves from the customer
                    // record). Extra guests are labelled so every message and the
                    // staff board can tell the companions apart.
                    $guestIdx   = (int) ($job['guest'] ?? 0);
                    $guestLabel = $guestIdx > 0
                        ? (app()->getLocale() === 'ar' ? 'ضيف ' . ($guestIdx + 1) : 'Guest ' . ($guestIdx + 1))
                        : null;

                    $appts[] = Appointment::create([
                        'company_id'      => $svc->branch->company_id,
                        'branch_id'       => $svc->branch_id,
                        'customer_id'     => $customer->id,
                        'customer_name'   => $guestLabel,
                        'booking_group_id'=> $groupId,
                        'reference'       => $reference,
                        'idempotency_key' => $idemKey,
                        'employee_id'     => $job['employee_id'],
                        'employee_requested' => (bool) ($job['requested'] ?? false),
                        'resource_id'     => $resourceId,
                        'service_id'      => $svc->id,
                        'start_time'      => $job['start'],
                        'end_time'        => $job['end'],
                        'status'          => $status,
                        'total_price'     => $svc->price,
                        'payment_status'  => 'pending',
                        'notes'           => $data['notes'] ?? null,
                        'booking_source'  => $this->resolveBookingSource($svc->branch_id),
                    ]);
                }
                return $appts;
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'conflict' => true,
                'message'  => app()->getLocale() === 'ar'
                    ? 'تعذّر تأكيد بعض المواعيد في هذا الوقت. اختر وقتاً آخر.'
                    : 'Some services couldn’t be booked at this time. Please pick another slot.',
            ], 409);
        }

        foreach ($created as $appt) {
            try { event(new AppointmentBooked($appt)); } catch (\Throwable $e) {}
        }

        $first = $created[0];
        $last  = $created[count($created) - 1];
        return response()->json([
            'booked' => true,
            'confirmed' => $first->status === AppointmentStatus::Confirmed, // false = awaiting approval
            'count'  => count($created),
            'reference' => $first->reference,
            'summary' => [
                'start' => $first->start_time->format('D, d M Y') . ' · ' . $branch->formatTime($first->start_time),
                'end'   => $branch->formatTime($last->end_time),
                'total' => collect($created)->sum('total_price'),
                'reference' => $first->reference,
            ],
        ], 201);
    }

    // ── group-booking helpers ────────────────────────────────────────────────

    private function validateSpec(Request $request, bool $forBooking): array
    {
        return $request->validate([
            'branch_id'              => 'required|exists:branches,id',
            $forBooking ? 'start_time' : 'date' => $forBooking ? 'required|date' : 'required|date_format:Y-m-d',
            'mode'                   => 'required|in:one,split',
            'employee_id'            => 'nullable|exists:employees,id',
            'notes'                  => 'nullable|string|max:500',
            'guests'                 => 'required|array|min:1|max:8',
            'guests.*.service_ids'   => 'required|array|min:1',
            'guests.*.service_ids.*' => 'required|exists:services,id',
            'guests.*.employee_id'   => 'nullable|exists:employees,id',
            'idempotency_key'        => 'nullable|string|max:64',
        ]);
    }

    /** Load employees, services, the day's bookings, the time grid and per-guest blocks. */
    private function prepareSpec(array $data, Carbon $date): array
    {
        $branch    = Branch::with('workingHours')->findOrFail($data['branch_id']);
        $employees = Employee::with(['workingHours', 'serviceCategories', 'leaves'])
            ->where('branch_id', $data['branch_id'])
            ->where('is_active', true)
            ->get();
        // Staff without their own schedule fall back to the branch hours
        // (Employee::shiftsOn) — hand them the loaded branch, no per-row query.
        $employees->each(fn ($e) => $e->setRelation('branch', $branch));
        $empById = $employees->keyBy('id');

        $svcIds   = collect($data['guests'])->flatMap(fn ($g) => $g['service_ids'])->unique()->values();
        $services = Service::with('branch')->whereIn('id', $svcIds)->get()->keyBy('id');

        $booked = Appointment::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('start_time', $date->toDateString())
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->get(['employee_id', 'start_time', 'end_time'])
            ->groupBy('employee_id');

        $dow = (int) $date->dayOfWeek;
        $gridStart = null; $gridEnd = null;
        foreach ($employees as $e) {
            foreach ($e->shiftsOn($dow, $branch) as $sh) {
                $ws = Carbon::parse($date->toDateString() . ' ' . $sh['start']);
                $we = Carbon::parse($date->toDateString() . ' ' . $sh['end']);
                if (!$gridStart || $ws->lt($gridStart)) $gridStart = $ws;
                if (!$gridEnd   || $we->gt($gridEnd))   $gridEnd   = $we;
            }
        }

        $guestBlocks = [];
        foreach ($data['guests'] as $g) {
            $gsvcs = array_values(array_filter(array_map(fn ($id) => $services[$id] ?? null, $g['service_ids'])));
            $guestBlocks[] = [
                'services'    => $gsvcs,
                'dur'         => array_sum(array_map(fn ($s) => (int) $s->duration_minutes, $gsvcs)),
                'employee_id' => $g['employee_id'] ?? null,
            ];
        }

        return [$employees, $empById, $services, $booked, $gridStart, $gridEnd, $guestBlocks];
    }

    /**
     * Return a concrete plan (array of jobs: employee_id, service, start, end) if the
     * whole visit fits at $start, or null. `one` = everything sequential on one
     * employee; `split` = guests in parallel with distinct employees.
     */
    private function resolveAssignment(string $mode, array $guestBlocks, $employees, $empById, $booked, Carbon $date, Carbon $start, $allocator): ?array
    {
        if ($mode === 'one') {
            $allSvcs = collect($guestBlocks)->flatMap(fn ($b) => $b['services'])->all();
            // Keep each service tied to the guest it belongs to, so the visit can
            // be labelled per guest even when one professional serves everyone.
            $allItems = [];
            foreach ($guestBlocks as $gi => $b) {
                foreach ($b['services'] as $svc) $allItems[] = ['svc' => $svc, 'guest' => $gi];
            }
            $requested = false;
            $cands = ($id = $guestBlocks[0]['employee_id'] ?? null) && $empById->has($id)
                ? [$empById[$id]]
                : $employees->all();
            if (($guestBlocks[0]['employee_id'] ?? null) && $empById->has($guestBlocks[0]['employee_id'])) {
                $requested = true;
            }
            // A top-level employee_id (shared "one professional") wins if present.
            if (!empty($guestBlocks) && ($shared = $this->sharedEmployee($guestBlocks, $empById))) {
                $cands = [$shared];
                $requested = true;
            }
            foreach ($cands as $emp) {
                if (!$this->empQualifiedAll($emp, $allSvcs)) continue;
                $jobs = $this->blockJobs($emp, $booked[$emp->id] ?? collect(), $date, $start, $allItems, $allocator, $requested);
                if ($jobs !== null) return $jobs;
            }
            return null;
        }

        // split: feasible distinct employees per guest, all starting at $start
        $feasible = [];
        foreach ($guestBlocks as $gi => $b) {
            $set   = [];
            $items = array_map(fn ($svc) => ['svc' => $svc, 'guest' => $gi], $b['services']);
            $cands = ($b['employee_id'] && $empById->has($b['employee_id'])) ? [$empById[$b['employee_id']]] : $employees->all();
            foreach ($cands as $emp) {
                if (!$this->empQualifiedAll($emp, $b['services'])) continue;
                if ($this->blockJobs($emp, $booked[$emp->id] ?? collect(), $date, $start, $items, $allocator) !== null) {
                    $set[] = $emp->id;
                }
            }
            if (empty($set)) return null;
            $feasible[$gi] = $set;
        }
        $assign = $this->assignDistinct($feasible);
        if ($assign === null) return null;

        // Build jobs from the assignment
        $jobs = [];
        foreach ($guestBlocks as $gi => $b) {
            $emp       = $empById[$assign[$gi]];
            $requested = $b['employee_id'] !== null && $empById->has($b['employee_id']);
            $items     = array_map(fn ($svc) => ['svc' => $svc, 'guest' => $gi], $b['services']);
            $sub = $this->blockJobs($emp, $booked[$emp->id] ?? collect(), $date, $start, $items, $allocator, $requested);
            if ($sub === null) return null;
            $jobs = array_merge($jobs, $sub);
        }
        return $jobs;
    }

    /** If every guest requested the same specific employee, return it (shared "one professional"). */
    private function sharedEmployee(array $guestBlocks, $empById)
    {
        $ids = array_unique(array_map(fn ($b) => $b['employee_id'], $guestBlocks));
        if (count($ids) === 1 && $ids[0] && $empById->has($ids[0])) return $empById[$ids[0]];
        return null;
    }

    /**
     * Jobs for a consecutive block on ONE employee, or null if it doesn't fit/free.
     * Each $item: ['svc' => Service, 'guest' => int] — guest index labels the
     * companion the service is for.
     */
    private function blockJobs($emp, $bookedForEmp, Carbon $date, Carbon $start, array $items, $allocator, bool $requested = false): ?array
    {
        $totalDur = array_sum(array_map(fn ($i) => (int) $i['svc']->duration_minutes, $items));
        if ($totalDur <= 0) return null;
        if (!$this->empFreeFor($emp, $bookedForEmp, $date, $start, $totalDur)) return null;

        $jobs = [];
        $cursor = $start->clone();
        foreach ($items as $it) {
            $svc = $it['svc'];
            $end = $cursor->clone()->addMinutes((int) $svc->duration_minutes);
            if ($allocator->requiresResource($svc) && $allocator->findFree($svc, $cursor, $end, null, false) === null) {
                return null;
            }
            $jobs[] = ['employee_id' => $emp->id, 'service' => $svc, 'start' => $cursor->clone(), 'end' => $end->clone(), 'requested' => $requested, 'guest' => $it['guest']];
            $cursor = $end;
        }
        return $jobs;
    }

    private function empQualifiedAll($emp, array $services): bool
    {
        $catIds = $emp->serviceCategories->pluck('id');
        if ($catIds->isEmpty()) return true; // generalist — can do anything
        foreach ($services as $s) {
            if ($s->service_category_id && !$catIds->contains($s->service_category_id)) return false;
        }
        return true;
    }

    private function empFreeFor($emp, $bookedForEmp, Carbon $date, Carbon $start, int $dur): bool
    {
        $end = $start->clone()->addMinutes($dur);
        $dow = (int) $date->dayOfWeek;

        $inShift = false;
        foreach ($emp->shiftsOn($dow) as $sh) {
            $ws = Carbon::parse($date->toDateString() . ' ' . $sh['start']);
            $we = Carbon::parse($date->toDateString() . ' ' . $sh['end']);
            if ($start->gte($ws) && $end->lte($we)) { $inShift = true; break; }
        }
        if (!$inShift) return false;

        foreach ($emp->leaves->where('status', 'approved') as $l) {
            $ls = Carbon::parse($l->start_date)->startOfDay();
            $le = Carbon::parse($l->end_date)->endOfDay();
            if ($date->betweenIncluded($ls, $le)) {
                if (!$l->is_hourly) return false;
                if ($l->start_hour && $l->end_hour) {
                    $hs = Carbon::parse($date->toDateString() . ' ' . $l->start_hour);
                    $he = Carbon::parse($date->toDateString() . ' ' . $l->end_hour);
                    if ($hs->lt($end) && $he->gt($start)) return false;
                }
            }
        }

        foreach ($bookedForEmp ?? [] as $a) {
            if ($a->start_time->lt($end) && $a->end_time->gt($start)) return false;
        }
        return true;
    }

    /** Assign a distinct employee to each guest (backtracking). Returns [guestIdx => empId] or null. */
    private function assignDistinct(array $guestFeasible): ?array
    {
        $assign = []; $used = [];
        $keys = array_keys($guestFeasible);
        $solve = function ($i) use (&$solve, &$assign, &$used, $guestFeasible, $keys) {
            if ($i >= count($keys)) return true;
            $g = $keys[$i];
            foreach ($guestFeasible[$g] as $empId) {
                if (isset($used[$empId])) continue;
                $used[$empId] = true; $assign[$g] = $empId;
                if ($solve($i + 1)) return true;
                unset($used[$empId], $assign[$g]);
            }
            return false;
        };
        return $solve(0) ? $assign : null;
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * The booking source for an online booking at this branch: the source
     * captured from the tracking link the customer arrived through (kept in the
     * session, scoped per branch), or null for a direct/untracked booking.
     */
    private function resolveBookingSource(int $branchId): ?string
    {
        $stored = session('bkg_source.' . $branchId);

        return \App\Enums\BookingSource::tryFrom((string) $stored)?->value;
    }

    /**
     * Why a whole DAY is closed to online booking at this branch, or null.
     * Per-slot rules (minimum notice, already-started) are applied per slot.
     */
    private function dayBlockReason(Branch $branch, Carbon $date): ?string
    {
        $rules = $branch->bookingRules();
        $day   = $date->toDateString();

        return match (true) {
            ! $rules['online']                               => 'online_disabled',
            $day < $rules['today']                           => 'past',
            ! $rules['same_day'] && $day === $rules['today'] => 'same_day_disabled',
            $day > $rules['last_date']                       => 'too_far',
            default                                          => null,
        };
    }

    private function nextAvailableDate(Employee $employee, Service $service, ?Carbon $from = null): ?string
    {
        $branch = $service->branch;
        $branch->loadMissing('workingHours');
        $rules  = $branch->bookingRules();
        $step   = $rules['interval'];
        $cursor = ($from ?? $branch->localToday())->copy()->startOfDay();

        // Never suggest a day the branch's booking window excludes.
        if (! $rules['same_day'] && $cursor->toDateString() === $rules['today']) {
            $cursor->addDay();
        }

        for ($i = 0; $i < 60 && $cursor->toDateString() <= $rules['last_date']; $i++) {
            $dayOfWeek = (int) $cursor->dayOfWeek;
            $shifts = $employee->shiftsOn($dayOfWeek, $branch);

            if ($shifts->isNotEmpty()) {
                // Check leave (hourly permissions don't make the whole day unavailable)
                $onLeave = $employee->leaves()
                    ->where('status', 'approved')
                    ->where('is_hourly', false)
                    ->where('start_date', '<=', $cursor->toDateString())
                    ->where('end_date',   '>=', $cursor->toDateString())
                    ->exists();

                if (!$onLeave) {
                    // Check if at least one slot is free across all shifts
                    $duration = $service->duration_minutes;
                    $booked   = Appointment::where('employee_id', $employee->id)
                        ->whereDate('start_time', $cursor->toDateString())
                        ->whereIn('status', AppointmentStatus::blockingValues())
                        ->count();

                    $totalSlots = 0;
                    foreach ($shifts as $shift) {
                        $whStart = Carbon::parse($cursor->toDateString() . ' ' . $shift['start']);
                        $whEnd   = Carbon::parse($cursor->toDateString() . ' ' . $shift['end']);
                        $totalSlots += max(0, (int) floor(($whStart->diffInMinutes($whEnd) - $duration) / $step) + 1);
                    }

                    if ($booked < $totalSlots) {
                        return $cursor->toDateString();
                    }
                }
            }

            $cursor->addDay();
        }

        return null;
    }
}
