<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
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
        $windowStart   = now()->addMinutes($minutesBefore - 6);
        $windowEnd     = now()->addMinutes($minutesBefore + 6);

        $appointments = Appointment::query()
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->whereNotNull('customer_phone')
            ->with(['branch', 'service', 'company'])
            ->orderBy('id')
            ->get();

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
