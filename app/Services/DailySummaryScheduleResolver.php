<?php

namespace App\Services;

use App\Models\BranchWorkingHour;
use App\Models\Company;
use App\Models\CompanyHoliday;
use Illuminate\Support\Carbon;

/**
 * Decides, for one company on one local day, *when* the daily email goes out
 * and *which* email it is. Reuses the existing working-hours and holiday data;
 * no new configuration.
 *
 * Rules (in order):
 *   1. No working hours configured at all → always the stats email at 21:00.
 *   2. Today is a company holiday          → a holiday greeting at 12:00.
 *   3. Today every branch is closed (weekly day off) → greeting at 12:00.
 *   4. Otherwise (open today)              → the stats email one hour after the
 *      latest branch closing time (capped at 23:00 so it stays same-day).
 *
 * day_of_week follows the app convention 0=Sun … 6=Sat (same as Carbon's
 * dayOfWeek), matching how availability is read elsewhere.
 */
final class DailySummaryScheduleResolver
{
    public const MODE_STATS    = 'stats';
    public const MODE_GREETING = 'greeting';

    /** Fallback / greeting hours (company-local, 24h). */
    private const FALLBACK_HOUR = 21;
    private const GREETING_HOUR = 12;

    /**
     * @return array{mode:string, hour:int, holiday_name:?string}
     */
    public function resolve(Company $company, Carbon $localNow): array
    {
        $branchIds = $company->branches()->pluck('id');

        // 1. Not configured at all → the simple 9 PM stats email, every day.
        $hasAnyHours = BranchWorkingHour::whereIn('branch_id', $branchIds)->exists();
        if (! $hasAnyHours) {
            return $this->stats(self::FALLBACK_HOUR);
        }

        // 2. Company holiday covering today → greeting.
        $holiday = CompanyHoliday::coveringDate($company->id, $localNow);
        if ($holiday) {
            return $this->greeting($holiday->name);
        }

        // 3./4. Look at today's open shifts across all branches.
        $closeTimes = BranchWorkingHour::whereIn('branch_id', $branchIds)
            ->where('day_of_week', (int) $localNow->dayOfWeek)
            ->where('is_open', true)
            ->whereNotNull('close_time')
            ->pluck('close_time')
            ->filter();

        if ($closeTimes->isEmpty()) {
            return $this->greeting(null); // closed today — weekly day off
        }

        // One hour after the latest closing time; never spill past midnight.
        $closeHour = (int) substr((string) $closeTimes->max(), 0, 2);

        return $this->stats(min($closeHour + 1, 23));
    }

    private function stats(int $hour): array
    {
        return ['mode' => self::MODE_STATS, 'hour' => $hour, 'holiday_name' => null];
    }

    private function greeting(?string $name): array
    {
        return ['mode' => self::MODE_GREETING, 'hour' => self::GREETING_HOUR, 'holiday_name' => $name];
    }
}
