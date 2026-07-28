<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\StaffNotification;
use Illuminate\Console\Command;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:expiry-reminders';
    protected $description = 'Notify companies (dashboard) about subscriptions expiring in 7/3/1 days';

    /** Reminder thresholds in days before expiry. */
    private const THRESHOLDS = [7, 3, 1];

    public function handle(): int
    {
        $sent = 0;

        foreach (self::THRESHOLDS as $days) {
            $companies = Company::query()
                ->whereNotNull('plan_id')
                ->whereDate('plan_expires_at', today()->addDays($days))
                ->with('plan')
                ->get();

            foreach ($companies as $company) {
                StaffNotification::create([
                    'company_id' => $company->id,
                    'type'       => 'subscription_expiry',
                    'title'      => __('Your :plan subscription expires in :days day(s)', [
                        'plan' => $company->plan?->localizedName() ?? __('subscription'),
                        'days' => $days,
                    ]),
                    'icon'  => '💳',
                    'color' => $days <= 3 ? '#ef4444' : '#f59e0b',
                    'link'  => route('company.dashboard'),
                ]);
                $sent++;
            }
        }

        $this->info("Subscription reminders processed — {$sent} notification(s) created.");

        return self::SUCCESS;
    }
}
