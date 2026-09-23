<?php

namespace App\Console\Commands;

use App\Mail\DailyBusinessSummaryMail;
use App\Mail\HolidayGreetingMail;
use App\Models\Company;
use App\Models\DailySummaryLog;
use App\Services\DailyBusinessSummaryService;
use App\Services\DailySummaryScheduleResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Sends each active company its daily email, at a time derived from the
 * company's own working hours ({@see DailySummaryScheduleResolver}):
 *
 *   • Open today   → the stats summary, one hour after the latest branch close.
 *   • Closed today → a holiday greeting at midday (weekly day off or a
 *     company holiday).
 *   • No hours set → the stats summary at 21:00 (the safe default).
 *
 * Scheduled hourly (see bootstrap/app.php); each run mails only the companies
 * whose computed send-hour is the current hour in their own timezone
 * ({@see Company::timezone()}). The unique (company_id, summary_date) row in
 * daily_summary_logs guarantees one email per company per local day.
 */
class SendDailyBusinessSummary extends Command
{
    protected $signature = 'summary:daily-business
        {--company= : Only this company id (ignores the hour gate)}
        {--force : Send now regardless of the computed hour}
        {--resend : Send even if already sent today (testing)}';

    protected $description = 'Email each active company its daily summary (or a holiday greeting) at the right local hour';

    public function handle(DailyBusinessSummaryService $service, DailySummaryScheduleResolver $resolver): int
    {
        // Company-facing email: render in the platform's primary language.
        app()->setLocale(config('app.locale', 'ar'));

        $onlyId = $this->option('company');
        $force  = (bool) $this->option('force') || $onlyId !== null;
        $resend = (bool) $this->option('resend');
        $isAr   = app()->getLocale() === 'ar';

        if ($onlyId !== null) {
            // Explicit target (ops/testing): bypass the eligibility filters.
            $query = Company::query()->whereKey($onlyId);
        } else {
            // Scheduled run: only active, account-confirmed companies
            // (owner verified via OTP — {@see Company::isVerified()}).
            // Unconfirmed accounts never get mail.
            $query = Company::query()
                ->where('status', 'active')
                ->whereNotNull('phone_verified_at');
        }

        $sent = $skipped = 0;

        $query->orderBy('id')->chunkById(100, function ($companies) use ($service, $resolver, $force, $resend, $isAr, &$sent, &$skipped) {
            foreach ($companies as $company) {
                $tz       = $company->timezone();
                $localNow = Carbon::now($tz);
                $plan     = $resolver->resolve($company, $localNow);

                // Only fire in the company's own computed send-hour (unless forced).
                if (! $force && $localNow->hour !== $plan['hour']) {
                    continue;
                }

                $summaryDate = $localNow->toDateString();

                if (! $resend && $this->alreadySent($company->id, $summaryDate)) {
                    $skipped++;
                    continue;
                }

                if (blank($company->email)) {
                    $this->warn("Company #{$company->id} has no email — skipped.");
                    $skipped++;
                    continue;
                }

                $mailable = $plan['mode'] === DailySummaryScheduleResolver::MODE_GREETING
                    ? new HolidayGreetingMail($company, $isAr, $plan['holiday_name'])
                    : new DailyBusinessSummaryMail($service->build($company, $localNow));

                // Claim the day's slot *before* queueing, so a concurrent run
                // can't double-send. Roll it back if the dispatch itself fails.
                $log = DailySummaryLog::updateOrCreate(
                    ['company_id' => $company->id, 'summary_date' => $summaryDate],
                    ['sent_at' => now()],
                );

                try {
                    Mail::to($company->email)->queue($mailable);
                    $sent++;
                } catch (\Throwable $e) {
                    // Keep the day open for a retry; never let one company abort the batch.
                    if ($log->wasRecentlyCreated) {
                        $log->delete();
                    }
                    $this->error("Company #{$company->id} queue failed: {$e->getMessage()}");
                    $skipped++;
                }
            }
        });

        $this->info("Daily emails — queued {$sent}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function alreadySent(int $companyId, string $date): bool
    {
        return DailySummaryLog::where('company_id', $companyId)
            ->whereDate('summary_date', $date)
            ->exists();
    }
}
