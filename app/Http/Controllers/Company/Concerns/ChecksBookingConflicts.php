<?php

namespace App\Http\Controllers\Company\Concerns;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedTime;
use Illuminate\Support\Carbon;

/**
 * Shared booking guards: a slot must not sit inside a blocked-time window,
 * and an assigned employee must be free for it.
 *
 * Consumers must expose a company(): \App\Models\Company method.
 */
trait ChecksBookingConflicts
{
    /** First blocked-time window overlapping the booking, or null when free. */
    protected function blockedConflict(int $branchId, ?int $employeeId, Carbon $start, Carbon $end): ?BlockedTime
    {
        return BlockedTime::query()
            ->where('branch_id', $branchId)
            ->overlapping($start, $end)
            ->forEmployee($employeeId)
            ->first();
    }

    protected function blockedMessage(BlockedTime $block): string
    {
        $window = $block->start_time->format('H:i') . '–' . $block->end_time->format('H:i');

        return __('This time is blocked and unavailable for booking.')
            . ' (' . $window . ($block->reason ? ' · ' . $block->reason : '') . ')';
    }

    /** First active appointment overlapping the window for this employee, or null when free. */
    protected function employeeConflict(int $employeeId, Carbon $start, Carbon $end, ?int $excludeId = null): ?Appointment
    {
        return Appointment::query()
            ->where('company_id', $this->company()->id)
            ->where('employee_id', $employeeId)
            // Every status that still occupies the slot — a customer who has
            // arrived or is mid-service blocks it just as much as a pending one.
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->orderBy('start_time')
            ->first();
    }

    protected function employeeConflictMessage(Appointment $conflict): string
    {
        return __('The employee already has an appointment at this time.')
            . ' (' . $conflict->start_time->format('H:i') . '–' . $conflict->end_time->format('H:i')
            . ' · ' . ($conflict->customer?->name ?? $conflict->customer_name ?? __('Customer')) . ')';
    }

    /** Employee belongs to this company either via its branch or directly (e.g. owners with no branch). */
    protected function employeeBelongsToCompany(int $employeeId): bool
    {
        $company = $this->company();

        return \App\Models\Employee::where('id', $employeeId)
            ->where(function ($q) use ($company) {
                $q->where('company_id', $company->id)
                  ->orWhereHas('branch', fn ($b) => $b->where('company_id', $company->id));
            })
            ->exists();
    }
}
