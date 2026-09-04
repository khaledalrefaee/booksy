<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\SmsAutomationSetting;
use App\Services\Sms\SmsService;
use Illuminate\Console\Command;

class SmsSendReminders extends Command
{
    protected $signature = 'sms:send-reminders';
    protected $description = 'Send credit-tracked SMS reminders for branches that opted in, at each branch\'s configured lead time';

    public function handle(SmsService $sms): int
    {
        // Each branch chooses its own lead time (reminder_offset_minutes). Run
        // every 10 min with a ±6 min window so nothing is missed or repeated;
        // SmsService de-dupes per booking group via dedupe_key.
        $settings = SmsAutomationSetting::where('reminder_enabled', true)->get();
        $sent = 0;

        foreach ($settings as $setting) {
            $offset      = max(1, (int) $setting->reminder_offset_minutes);
            $windowStart = now()->addMinutes($offset - 6);
            $windowEnd   = now()->addMinutes($offset + 6);

            $appointments = Appointment::query()
                ->where('branch_id', $setting->branch_id)
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
                ->whereBetween('start_time', [$windowStart, $windowEnd])
                ->where(function ($q) {
                    $q->whereNotNull('customer_phone')
                      ->orWhereHas('customer', fn ($c) => $c->whereNotNull('phone'));
                })
                ->with(['branch', 'service', 'customer'])
                ->orderBy('id')
                ->get();

            $seenGroups = [];
            foreach ($appointments as $appt) {
                if ($appt->booking_group_id) {
                    if (isset($seenGroups[$appt->booking_group_id])) continue;
                    $seenGroups[$appt->booking_group_id] = true;
                }
                if ($sms->reminder($appt)) {
                    $sent++;
                }
            }
        }

        $this->info("Queued {$sent} SMS reminder(s).");
        return self::SUCCESS;
    }
}
