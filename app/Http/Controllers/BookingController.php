<?php

namespace App\Http\Controllers;

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

        // Check working hours
        $wh = $employee->workingHours->firstWhere('day_of_week', $dayOfWeek);

        if (!$wh || !$wh->is_working) {
            return response()->json([
                'available'    => false,
                'reason'       => 'not_working',
                'working_hours'=> null,
                'slots'        => [],
                'next_date'    => $this->nextAvailableDate($employee, $service),
            ]);
        }

        // Check approved leave
        $onLeave = $employee->leaves()
            ->where('status', 'approved')
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date',   '>=', $date->toDateString())
            ->exists();

        if ($onLeave) {
            return response()->json([
                'available' => false,
                'reason'    => 'on_leave',
                'slots'     => [],
                'next_date' => $this->nextAvailableDate($employee, $service, $date->clone()->addDay()),
            ]);
        }

        // Existing appointments on this day
        $booked = Appointment::where('employee_id', $employee->id)
            ->whereDate('start_time', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get(['start_time', 'end_time']);

        // Generate slots every 15 min within working hours
        $duration  = $service->duration_minutes;
        $whStart   = Carbon::parse($date->toDateString() . ' ' . $wh->start_time);
        $whEnd     = Carbon::parse($date->toDateString() . ' ' . $wh->end_time);
        $cursor    = $whStart->clone();
        $slots     = [];

        while ($cursor->clone()->addMinutes($duration)->lte($whEnd)) {
            $slotEnd = $cursor->clone()->addMinutes($duration);

            $overlaps = $booked->contains(
                fn($a) => $a->start_time->lt($slotEnd) && $a->end_time->gt($cursor)
            );

            if (!$overlaps) {
                $slots[] = [
                    'time'   => $cursor->format('H:i'),
                    'start'  => $cursor->toDateTimeString(),
                    'end'    => $slotEnd->toDateTimeString(),
                ];
            }

            $cursor->addMinutes(15);
        }

        return response()->json([
            'available'     => count($slots) > 0,
            'reason'        => count($slots) === 0 ? 'fully_booked' : null,
            'working_hours' => ['start' => $wh->start_time, 'end' => $wh->end_time],
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

        // DB transaction + lock to prevent race condition
        $appointment = DB::transaction(function () use ($request, $service, $employee, $startTime, $endTime, $customer) {

            // Lock check: any overlapping active appointment for this employee?
            $conflict = Appointment::where('employee_id', $employee->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->where('start_time', '<', $endTime)
                ->where('end_time',   '>', $startTime)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                return null; // slot taken
            }

            return Appointment::create([
                'company_id'   => $service->branch->company_id,
                'branch_id'    => $service->branch_id,
                'customer_id'  => $customer->id,
                'employee_id'  => $employee->id,
                'service_id'   => $service->id,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'status'       => 'pending',
                'total_price'  => $service->price,
                'payment_status'=> 'pending',
                'notes'        => $request->notes,
            ]);
        });

        if (!$appointment) {
            return response()->json([
                'message' => 'This slot was just taken. Please choose another time.',
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
            $wh = $employee->workingHours->firstWhere('day_of_week', $dayOfWeek);

            if ($wh && $wh->is_working) {
                // Check leave
                $onLeave = $employee->leaves()
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $cursor->toDateString())
                    ->where('end_date',   '>=', $cursor->toDateString())
                    ->exists();

                if (!$onLeave) {
                    // Check if at least one slot is free
                    $duration = $service->duration_minutes;
                    $whStart  = Carbon::parse($cursor->toDateString() . ' ' . $wh->start_time);
                    $whEnd    = Carbon::parse($cursor->toDateString() . ' ' . $wh->end_time);
                    $booked   = Appointment::where('employee_id', $employee->id)
                        ->whereDate('start_time', $cursor->toDateString())
                        ->whereNotIn('status', ['cancelled', 'rejected'])
                        ->count();

                    $totalSlots = (int) floor($whStart->diffInMinutes($whEnd) / 15) - (int) ceil($duration / 15) + 1;

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
