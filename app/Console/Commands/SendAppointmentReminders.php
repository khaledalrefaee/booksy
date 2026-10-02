<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\SmsAutomationSetting;
use App\Services\Sms\SmsService;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send each branch\'s customer reminder (SMS or WhatsApp) at its chosen lead time';

    public function handle(SmsService $sms, WhatsappService $whatsapp): int
    {
        // The ONE reminder job, driven by the branch's "Customer messages"
        // settings. Runs every minute, so a reminder set for 10 minutes goes
        // out 10 minutes before — not up to a scheduler tick early or late.
        //
        // Window = [now, now + lead time]: the lower bound is "now" so a
        // booking made inside the lead time (14:00 for 14:30 with a 60-min
        // reminder) is still reminded once. Both channels de-dupe per visit +
        // start time, so nothing repeats and a moved booking is reminded again.
        $saved = SmsAutomationSetting::all()->keyBy('branch_id');
        $sent  = 0;

        foreach (Branch::query()->orderBy('id')->get() as $branch) {
            $settings = $saved[$branch->id]
                ?? new SmsAutomationSetting(SmsAutomationSetting::defaults());
            if (! $settings->reminder_enabled) {
                continue;
            }

            // Appointment times are the branch's wall clock — measure from its "now".
            $now    = $branch->localNow();
            $offset = max(1, (int) $settings->reminder_offset_minutes);

            // App bookings keep the phone on the customer record, not the
            // denormalised customer_phone column — accept EITHER.
            $appointments = Appointment::query()
                ->where('branch_id', $branch->id)
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value, AppointmentStatus::Upcoming->value])
                ->whereBetween('start_time', [$now, $now->copy()->addMinutes($offset)])
                ->where(function ($q) {
                    $q->whereNotNull('customer_phone')
                      ->orWhereHas('customer', fn ($c) => $c->whereNotNull('phone'));
                })
                ->with(['branch', 'service', 'company', 'customer', 'employee'])
                ->orderBy('id')
                ->get();

            // Once per visit: a grouped booking is reminded through its first row.
            $seenGroups = [];
            foreach ($appointments as $appt) {
                if ($appt->booking_group_id) {
                    if (isset($seenGroups[$appt->booking_group_id])) continue;
                    $seenGroups[$appt->booking_group_id] = true;
                }

                $phone = $appt->customer_phone ?: $appt->customer?->phone;
                $ok = $sms->routesOverSms($phone)
                    ? (bool) $sms->reminder($appt)      // SMS: branch credits
                    : $whatsapp->sendReminder($appt);   // everyone else
                if ($ok) {
                    $sent++;
                }
            }
        }

        $this->info("Sent {$sent} reminder(s).");
        return self::SUCCESS;
    }
}
