<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentBooked;
use App\Http\Controllers\CustomerAuthController;
use App\Models\Appointment;
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
        $service  = Service::findOrFail($request->service_id);
        $date     = Carbon::parse($request->date)->startOfDay();
        $dayOfWeek = (int) $date->dayOfWeek; // 0=Sun … 6=Sat

        // Check working hours (an employee may have multiple shifts per day)
        $shifts = $employee->workingHours
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working', true)
            ->sortBy('shift_number')
            ->values();

        if ($shifts->isEmpty()) {
            return response()->json([
                'available'    => false,
                'reason'       => 'not_working',
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

        // Generate slots every 15 min within each shift — breaks between shifts are excluded automatically
        $duration = $service->duration_minutes;
        $slots    = [];
        $now      = now();
        $isToday  = $date->isToday();

        foreach ($shifts as $shift) {
            $whStart = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);
            $whEnd   = Carbon::parse($date->toDateString() . ' ' . $shift->end_time);
            $cursor  = $whStart->clone();

            while ($cursor->clone()->addMinutes($duration)->lte($whEnd)) {
                $slotEnd = $cursor->clone()->addMinutes($duration);

                // Hide slots that already started (today only)
                if ($isToday && $cursor->lt($now)) { $cursor->addMinutes(15); continue; }

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
                        'start'  => $cursor->toDateTimeString(),
                        'end'    => $slotEnd->toDateTimeString(),
                    ];
                }

                $cursor->addMinutes(15);
            }
        }

        return response()->json([
            'available'     => count($slots) > 0,
            'reason'        => count($slots) === 0 ? 'fully_booked' : null,
            'working_hours' => [
                'start'  => $shifts->first()->start_time,
                'end'    => $shifts->last()->end_time,
                'shifts' => $shifts->map(fn($s) => ['start' => $s->start_time, 'end' => $s->end_time])->values(),
            ],
            'slots'         => $slots,
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
        ]);

        $service   = Service::with('branch.company')->findOrFail($request->service_id);
        $employee  = Employee::findOrFail($request->employee_id);
        $startTime = Carbon::parse($request->start_time);
        $endTime   = $startTime->clone()->addMinutes($service->duration_minutes);

        $allocator = app(\App\Services\ResourceAllocator::class);

        // DB transaction + lock to prevent race condition
        $appointment = DB::transaction(function () use ($request, $service, $employee, $startTime, $endTime, $customer, $allocator) {

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
                'employee_id'  => $employee->id,
                'resource_id'  => $resourceId,
                'service_id'   => $service->id,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'status'       => AppointmentStatus::Pending,
                'total_price'  => $service->price,
                'payment_status'=> 'pending',
                'notes'        => $request->notes,
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
            'appointment' => [
                'id'         => $appointment->id,
                'start_time' => $appointment->start_time->format('D, d M Y · H:i'),
                'end_time'   => $appointment->end_time->format('H:i'),
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
        $data = $this->validateSpec($request, false);
        $date = Carbon::parse($data['date'])->startOfDay();
        $now  = now();
        $isToday = $date->isToday();

        [$employees, $empById, $services, $booked, $gridStart, $gridEnd, $guestBlocks]
            = $this->prepareSpec($data, $date);

        if (!$gridStart) {
            return response()->json(['available' => false, 'slots' => [], 'reason' => 'closed']);
        }

        $allocator = app(\App\Services\ResourceAllocator::class);
        $slots = [];

        for ($t = $gridStart->clone(); $t->lte($gridEnd); $t->addMinutes(15)) {
            if ($isToday && $t->lt($now)) continue;
            if ($this->resolveAssignment($data['mode'], $guestBlocks, $employees, $empById, $booked, $date, $t, $allocator) !== null) {
                $slots[] = ['time' => $t->format('H:i'), 'start' => $date->toDateString() . ' ' . $t->format('H:i') . ':00'];
            }
        }

        return response()->json([
            'available' => count($slots) > 0,
            'reason'    => count($slots) ? null : 'fully_booked',
            'slots'     => $slots,
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

        [$employees, $empById, $services, $booked, $gridStart, $gridEnd, $guestBlocks]
            = $this->prepareSpec($data, $date);

        $allocator = app(\App\Services\ResourceAllocator::class);

        try {
            $created = DB::transaction(function () use ($data, $guestBlocks, $employees, $empById, $date, $start, $allocator, $customer) {
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
                $groupId = count($plan) > 1 ? Appointment::newGroupId() : null;

                $appts = [];
                foreach ($plan as $job) { // each job: employee_id, service, start, end
                    $resourceId = null;
                    if ($allocator->requiresResource($job['service'])) {
                        $res = $allocator->findFree($job['service'], $job['start'], $job['end'], null, true);
                        if (!$res) throw new \RuntimeException('conflict');
                        $resourceId = $res->id;
                    }
                    $svc = $job['service'];
                    $appts[] = Appointment::create([
                        'company_id'      => $svc->branch->company_id,
                        'branch_id'       => $svc->branch_id,
                        'customer_id'     => $customer->id,
                        'booking_group_id'=> $groupId,
                        'employee_id'     => $job['employee_id'],
                        'resource_id'     => $resourceId,
                        'service_id'      => $svc->id,
                        'start_time'      => $job['start'],
                        'end_time'        => $job['end'],
                        'status'          => AppointmentStatus::Pending,
                        'total_price'     => $svc->price,
                        'payment_status'  => 'pending',
                        'notes'           => $data['notes'] ?? null,
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
            'count'  => count($created),
            'summary' => [
                'start' => $first->start_time->format('D, d M Y · H:i'),
                'end'   => $last->end_time->format('H:i'),
                'total' => collect($created)->sum('total_price'),
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
        ]);
    }

    /** Load employees, services, the day's bookings, the time grid and per-guest blocks. */
    private function prepareSpec(array $data, Carbon $date): array
    {
        $employees = Employee::with(['workingHours', 'serviceCategories', 'leaves'])
            ->where('branch_id', $data['branch_id'])
            ->where('is_active', true)
            ->get();
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
            foreach ($e->workingHours->where('day_of_week', $dow)->where('is_working', true) as $sh) {
                if (!$sh->start_time || !$sh->end_time) continue;
                $ws = Carbon::parse($date->toDateString() . ' ' . $sh->start_time);
                $we = Carbon::parse($date->toDateString() . ' ' . $sh->end_time);
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
            $cands = ($id = $guestBlocks[0]['employee_id'] ?? null) && $empById->has($id)
                ? [$empById[$id]]
                : $employees->all();
            // A top-level employee_id (shared "one professional") wins if present.
            if (!empty($guestBlocks) && ($shared = $this->sharedEmployee($guestBlocks, $empById))) {
                $cands = [$shared];
            }
            foreach ($cands as $emp) {
                if (!$this->empQualifiedAll($emp, $allSvcs)) continue;
                $jobs = $this->blockJobs($emp, $booked[$emp->id] ?? collect(), $date, $start, $allSvcs, $allocator);
                if ($jobs !== null) return $jobs;
            }
            return null;
        }

        // split: feasible distinct employees per guest, all starting at $start
        $feasible = [];
        foreach ($guestBlocks as $gi => $b) {
            $set = [];
            $cands = ($b['employee_id'] && $empById->has($b['employee_id'])) ? [$empById[$b['employee_id']]] : $employees->all();
            foreach ($cands as $emp) {
                if (!$this->empQualifiedAll($emp, $b['services'])) continue;
                if ($this->blockJobs($emp, $booked[$emp->id] ?? collect(), $date, $start, $b['services'], $allocator) !== null) {
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
            $emp = $empById[$assign[$gi]];
            $sub = $this->blockJobs($emp, $booked[$emp->id] ?? collect(), $date, $start, $b['services'], $allocator);
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

    /** Jobs for a consecutive block on ONE employee, or null if it doesn't fit/free. */
    private function blockJobs($emp, $bookedForEmp, Carbon $date, Carbon $start, array $services, $allocator): ?array
    {
        $totalDur = array_sum(array_map(fn ($s) => (int) $s->duration_minutes, $services));
        if ($totalDur <= 0) return null;
        if (!$this->empFreeFor($emp, $bookedForEmp, $date, $start, $totalDur)) return null;

        $jobs = [];
        $cursor = $start->clone();
        foreach ($services as $svc) {
            $end = $cursor->clone()->addMinutes((int) $svc->duration_minutes);
            if ($allocator->requiresResource($svc) && $allocator->findFree($svc, $cursor, $end, null, false) === null) {
                return null;
            }
            $jobs[] = ['employee_id' => $emp->id, 'service' => $svc, 'start' => $cursor->clone(), 'end' => $end->clone()];
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
        foreach ($emp->workingHours->where('day_of_week', $dow)->where('is_working', true) as $sh) {
            if (!$sh->start_time || !$sh->end_time) continue;
            $ws = Carbon::parse($date->toDateString() . ' ' . $sh->start_time);
            $we = Carbon::parse($date->toDateString() . ' ' . $sh->end_time);
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

    private function nextAvailableDate(Employee $employee, Service $service, ?Carbon $from = null): ?string
    {
        $cursor = ($from ?? now())->startOfDay();

        for ($i = 0; $i < 60; $i++) {
            $dayOfWeek = (int) $cursor->dayOfWeek;
            $shifts = $employee->workingHours
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working', true);

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
                        $whStart = Carbon::parse($cursor->toDateString() . ' ' . $shift->start_time);
                        $whEnd   = Carbon::parse($cursor->toDateString() . ' ' . $shift->end_time);
                        $totalSlots += max(0, (int) floor($whStart->diffInMinutes($whEnd) / 15) - (int) ceil($duration / 15) + 1);
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
