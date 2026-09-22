<?php

namespace App\Console\Commands;

use App\Mail\DailyBusinessSummaryMail;
use App\Models\Company;
use App\Models\DailySummaryLog;
use App\Services\DailyBusinessSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the Daily Business Summary email to each active company's owner.
 *
 * Scheduled hourly (see bootstrap/app.php). Each run only mails the companies
 * for which it is currently the send hour *in that company's own timezone*
 * ({@see Company::timezone()}) — so the report always lands at 9 PM local, and
 * the logic already works per-company the day a timezone column is added.
 *
 * The unique (company_id, summary_date) row in daily_summary_logs guarantees a
 * company is never mailed twice on the same local day.
 */
class SendDailyBusinessSummary extends Command
{
    protected $signature = 'summary:daily-business
        {--company= : Only this company id (ignores the hour gate)}
        {--force : Send now regardless of the local hour}
        {--resend : Send even if already sent today (testing)}';

    protected $description = 'Email each active company its Daily Business Summary at 9 PM company-local time';

    /** The company-local hour the summary is sent (24h). */
    private const SEND_HOUR = 21;

    public function handle(DailyBusinessSummaryService $service): int
    {
        // Company-facing email: render in the platform's primary language.
        app()->setLocale(config('app.locale', 'ar'));

        $onlyId  = $this->option('company');
        $force   = (bool) $this->option('force') || $onlyId !== null;
        $resend  = (bool) $this->option('resend');

        $query = Company::query()->where('status', 'active');
        if ($onlyId !== null) {
            $query->whereKey($onlyId);
        }

        $sent = $skipped = 0;

        $query->orderBy('id')->chunkById(100, function ($companies) use ($service, $force, $resend, &$sent, &$skipped) {
            foreach ($companies as $company) {
                $tz       = $company->timezone();
                $localNow = Carbon::now($tz);

                // Only fire in the company's own 9 PM hour (unless forced).
                if (! $force && $localNow->hour !== self::SEND_HOUR) {
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

                $report = $service->build($company, $localNow);

                if (! $service->hasReportableData($report)) {
                    $this->line("Company #{$company->id} has no reportable data — skipped.");
                    $skipped++;
                    continue;
                }

                // Claim the day's slot *before* queueing, so a concurrent run
                // can't double-send. Roll it back if the dispatch itself fails.
                $log = DailySummaryLog::updateOrCreate(
                    ['company_id' => $company->id, 'summary_date' => $summaryDate],
                    ['sent_at' => now()],
                );

                try {
                    Mail::to($company->email)->queue(new DailyBusinessSummaryMail($report));
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

        $this->info("Daily summaries — queued {$sent}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function alreadySent(int $companyId, string $date): bool
    {
        return DailySummaryLog::where('company_id', $companyId)
            ->whereDate('summary_date', $date)
            ->exists();
    }
}
