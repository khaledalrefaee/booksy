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

        foreach ($shifts as $shift) {
            $whStart = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);
            $whEnd   = Carbon::parse($date->toDateString() . ' ' . $shift->end_time);
            $cursor  = $whStart->clone();

            while ($cursor->clone()->addMinutes($duration)->lte($whEnd)) {
                $slotEnd = $cursor->clone()->addMinutes($duration);

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
