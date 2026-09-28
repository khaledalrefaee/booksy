<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\SmsAutomationSetting;
use App\Services\Sms\SmsService;
use Illuminate\Console\Command;

class SmsSendFollowups extends Command
{
    protected $signature = 'sms:send-followups';
    protected $description = 'Send the win-back message (SMS or WhatsApp) N days after a customer\'s last visit, per branch "Customer messages" settings';

    public function handle(SmsService $sms, \App\Services\WhatsappService $whatsapp): int
    {
        // "N days after the last visit": for each opted-in branch, look at the day
        // exactly followup_days ago and message customers whose most recent
        // completed visit fell on that day (so someone who has since returned is
        // not nudged). dedupe_key stops a second send for the same visit.
        $settings = SmsAutomationSetting::where('followup_enabled', true)->with('branch')->get();
        $sent = 0;

        foreach ($settings as $setting) {
            $days      = max(1, (int) $setting->followup_days);
            // Visit dates are the branch's wall clock — count days from its "today",
            // not the server's, or a branch in another timezone is a day off.
            $today     = $setting->branch?->localNow() ?? now();
            $targetDay = $today->copy()->subDays($days)->toDateString();

            $appointments = Appointment::query()
                ->where('branch_id', $setting->branch_id)
                ->where('status', AppointmentStatus::Completed->value)
                ->whereNotNull('customer_id')
                ->whereDate('start_time', $targetDay)
                ->where(function ($q) {
                    $q->whereNotNull('customer_phone')
                      ->orWhereHas('customer', fn ($c) => $c->whereNotNull('phone'));
                })
                ->with(['branch', 'service', 'customer'])
                ->orderBy('id')
                ->get();

            $seenCustomers = [];
            foreach ($appointments as $appt) {
                // One follow-up per customer per run.
                if (isset($seenCustomers[$appt->customer_id])) continue;

                // Skip if the customer has visited again since (this isn't their last visit).
                $hasLaterVisit = Appointment::where('branch_id', $setting->branch_id)
                    ->where('customer_id', $appt->customer_id)
                    ->where('status', AppointmentStatus::Completed->value)
                    ->whereDate('start_time', '>', $targetDay)
                    ->exists();
                if ($hasLaterVisit) continue;

                $seenCustomers[$appt->customer_id] = true;

                $phone = $appt->customer_phone ?: $appt->customer?->phone;
                $ok = $sms->routesOverSms($phone)
                    ? (bool) $sms->followup($appt)      // SMS: branch credits
                    : $whatsapp->sendFollowup($appt);   // everyone else
                if ($ok) {
                    $sent++;
                }
            }
        }

        $this->info("Queued {$sent} SMS follow-up(s).");
        return self::SUCCESS;
    }
}
