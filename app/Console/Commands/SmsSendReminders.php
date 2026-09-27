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
        // Each branch chooses its own lead time (reminder_offset_minutes). Runs
        // every 10 min and picks up everything that hasn't started yet and is
        // due within the lead time (+6 min so the 10-min cadence never skips
        // one). The lower bound is "now", not "now + offset", so a booking made
        // inside the lead time (booked at 14:00 for 14:30 with a 60-min
        // reminder) still gets one. SmsService de-dupes per booking + start
        // time, so nothing repeats — and a rescheduled booking is reminded again.
        $settings = SmsAutomationSetting::where('reminder_enabled', true)->with('branch')->get();
        $sent = 0;

        foreach ($settings as $setting) {
            $offset      = max(1, (int) $setting->reminder_offset_minutes);
            // Appointment times are the branch's wall clock — measure from its "now".
            $now         = $setting->branch?->localNow() ?? now();
            $windowStart = $now->copy();
            $windowEnd   = $now->copy()->addMinutes($offset + 6);

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
