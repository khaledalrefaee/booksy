<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send the 1h confirm/cancel reminder for upcoming appointments';

    public function handle(WhatsappService $whatsapp): int
    {
        // A single actionable reminder ~1h before the visit. No WhatsApp-connection
        // gate here: Syrian numbers go out over SMS, which is independent of the
        // WhatsApp node — sendReminder() routes each one by country and logs
        // failures. The scheduler runs every 10 min; a ±6 min window covers it
        // without gaps, and the service de-dupes per group so nothing repeats.
        $minutesBefore = 60;

        // Appointment times are each branch's wall clock, so the window is
        // computed per timezone (one query per distinct zone — usually one).
        // App bookings keep the phone on the customer record, not the denormalised
        // customer_phone column — so accept EITHER, or the reminder never fires for
        // anyone who booked through the site.
        $appointments = collect();
        foreach (Branch::idsByTimezone() as $tz => $branchIds) {
            $now = Branch::wallNowIn($tz);
            $appointments = $appointments->concat(Appointment::query()
                ->whereIn('branch_id', $branchIds)
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
                ->whereBetween('start_time', [$now->copy()->addMinutes($minutesBefore - 6), $now->copy()->addMinutes($minutesBefore + 6)])
                ->where(function ($q) {
                    $q->whereNotNull('customer_phone')
                      ->orWhereHas('customer', fn ($c) => $c->whereNotNull('phone'));
                })
                ->with(['branch', 'service', 'company', 'customer', 'employee'])
                ->get());
        }
        $appointments = $appointments->sortBy('id')->values();

        // Send once per visit: for grouped bookings only the first row (lowest id)
        // triggers the consolidated reminder; siblings are skipped.
        $seenGroups = [];
        $sent = 0;
        foreach ($appointments as $appt) {
            if ($appt->booking_group_id) {
                if (isset($seenGroups[$appt->booking_group_id])) continue;
                $seenGroups[$appt->booking_group_id] = true;
            }
            if ($whatsapp->sendReminder($appt, '1h')) {
                $sent++;
            }
        }

        $this->info("Sent {$sent} one-hour reminder(s).");
        return self::SUCCESS;
    }
}
