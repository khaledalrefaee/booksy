<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentTransition;
use App\Services\ResourceAllocator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Move a whole visit (a booking_group, or a single appointment) to a new start
 * time, keeping the same services and staff. Re-runs the full availability check
 * under a row lock — working hours, leaves, existing appointments, and the
 * room/device — so a reschedule can never create a conflict or a double-booking,
 * and records the change on the timeline. Status is NOT changed here.
 *
 * @return array{ok: bool, message?: string, appointment?: Appointment}
 */
class RescheduleAppointment
{
    /** Statuses a customer/venue may still move. Terminal + in-progress are frozen. */
    private const MOVABLE = [
        AppointmentStatus::Pending,
        AppointmentStatus::Confirmed,
    ];

    public function __invoke(Appointment $primary, Carbon $newStart, ?string $actorType = null, array $meta = []): array
    {
        $ar = app()->getLocale() === 'ar';

        if (! in_array($primary->status, self::MOVABLE, true)) {
            return ['ok' => false, 'message' => $ar ? 'لا يمكن تغيير موعد بهذه الحالة.' : 'This appointment can no longer be rescheduled.'];
        }
        if ($newStart->lte(now())) {
            return ['ok' => false, 'message' => $ar ? 'هذا الوقت مضى بالفعل. اختر وقتاً لاحقاً.' : 'This time has already passed. Please pick a later slot.'];
        }

        $allocator = app(ResourceAllocator::class);

        $actor = match ($actorType) {
            'customer'           => \App\Enums\TransitionActor::Customer,
            'company', 'business'=> \App\Enums\TransitionActor::Company,
            'employee'           => \App\Enums\TransitionActor::Employee,
            default              => \App\Enums\TransitionActor::System,
        };

        try {
            $result = DB::transaction(function () use ($primary, $newStart, $allocator, $ar, $meta, $actor) {
                // The whole visit moves by the same delta as the primary row.
                $rows = $primary->booking_group_id
                    ? Appointment::where('booking_group_id', $primary->booking_group_id)
                        ->lockForUpdate()->orderBy('start_time')->get()
                    : Appointment::whereKey($primary->id)->lockForUpdate()->get();

                $anchor = $rows->firstWhere('id', $primary->id) ?? $rows->first();
                $delta  = $anchor->start_time->diffInMinutes($newStart, false); // signed minutes
                $ownIds = $rows->pluck('id')->all();

                foreach ($rows as $row) {
                    $rowStart = $row->start_time->copy()->addMinutes($delta);
                    $rowEnd   = ($row->end_time ?? $row->start_time->copy()->addMinutes((int) ($row->service?->duration_minutes ?? 30)))
                                    ->copy()->addMinutes($delta);

                    if ($rowStart->lte(now())) {
                        throw new \RuntimeException($ar ? 'الوقت الجديد في الماضي.' : 'The new time is in the past.');
                    }
                    if ($row->employee_id && ! $this->employeeFree($row->employee_id, $rowStart, $rowEnd, $ownIds)) {
                        throw new \RuntimeException($ar ? 'الموظف غير متاح في الوقت الجديد.' : 'The staff member is not free at the new time.');
                    }
                    // Room/device must be free for the new window (excluding this row).
                    if ($row->service && $allocator->requiresResource($row->service)) {
                        $res = $allocator->findFree($row->service, $rowStart, $rowEnd, $row->id, true);
                        if (! $res) {
                            throw new \RuntimeException($allocator->conflictMessage($row->service, $rowStart, $rowEnd));
                        }
                        $row->resource_id = $res->id;
                    }

                    $row->original_start_time = $row->original_start_time ?? $row->start_time;
                    $oldStart = $row->start_time->copy();
                    $row->start_time = $rowStart;
                    $row->end_time   = $rowEnd;
                    $row->reschedule_count = (int) $row->reschedule_count + 1;
                    $row->rescheduled_at = now();
                    $row->saveQuietly(); // time move, not a status change — don't trip the status observer

                    // Timeline entry so support can see every move.
                    AppointmentTransition::create([
                        'appointment_id' => $row->id,
                        'company_id'     => $row->company_id,
                        'from_status'    => $row->status,
                        'to_status'      => $row->status,
                        'actor_type'     => $actor,
                        'actor_id'       => $meta['actorId'] ?? null,
                        'actor_name'     => $meta['actorName'] ?? null,
                        'reason'         => ($ar ? 'إعادة جدولة: ' : 'Rescheduled: ')
                                            . $oldStart->format('d/m g:i A') . ' → ' . $rowStart->format('d/m g:i A'),
                        'automatic'      => false,
                        'meta'           => ['event' => 'rescheduled', 'old_start' => $oldStart->toDateTimeString(), 'new_start' => $rowStart->toDateTimeString()] + $meta,
                    ]);
                }

                return $anchor->fresh();
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => true, 'appointment' => $result];
    }

    /** Is the employee free for [$start,$end], ignoring the visit's own rows? */
    private function employeeFree(int $employeeId, Carbon $start, Carbon $end, array $ignoreIds): bool
    {
        $employee = \App\Models\Employee::with(['workingHours', 'leaves'])->find($employeeId);
        if (! $employee) return false;

        $dow = (int) $start->dayOfWeek;
        $inShift = false;
        foreach ($employee->workingHours->where('day_of_week', $dow)->where('is_working', true) as $sh) {
            if (! $sh->start_time || ! $sh->end_time) continue;
            $ws = Carbon::parse($start->toDateString() . ' ' . $sh->start_time);
            $we = Carbon::parse($start->toDateString() . ' ' . $sh->end_time);
            if ($start->gte($ws) && $end->lte($we)) { $inShift = true; break; }
        }
        if (! $inShift) return false;

        foreach ($employee->leaves->where('status', 'approved') as $l) {
            $ls = Carbon::parse($l->start_date)->startOfDay();
            $le = Carbon::parse($l->end_date)->endOfDay();
            if ($start->betweenIncluded($ls, $le)) {
                if (! $l->is_hourly) return false;
                if ($l->start_hour && $l->end_hour) {
                    $hs = Carbon::parse($start->toDateString() . ' ' . $l->start_hour);
                    $he = Carbon::parse($start->toDateString() . ' ' . $l->end_hour);
                    if ($hs->lt($end) && $he->gt($start)) return false;
                }
            }
        }

        return ! Appointment::where('employee_id', $employeeId)
            ->whereNotIn('id', $ignoreIds)
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->lockForUpdate()
            ->exists();
    }
}
