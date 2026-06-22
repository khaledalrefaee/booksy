<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send WhatsApp reminders for appointments starting within the configured window';

    public function handle(WhatsappService $whatsapp): int
    {
        if (!$whatsapp->isConnected()) {
            $this->warn('WhatsApp service is not connected. Skipping reminders.');
            return self::SUCCESS;
        }

        $minutes = config('booksy.whatsapp.reminder_minutes', 60);
        $windowStart = now()->addMinutes($minutes - 5);
        $windowEnd   = now()->addMinutes($minutes + 5);

        $appointments = Appointment::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->whereNotNull('customer_phone')
            ->with(['branch', 'service'])
            ->get();

        $sent = 0;
        foreach ($appointments as $appt) {
            if ($whatsapp->sendReminder($appt)) {
                $sent++;
            }
        }

        $this->info("Sent {$sent} reminder(s) out of {$appointments->count()} appointment(s).");
        return self::SUCCESS;
    }
}
