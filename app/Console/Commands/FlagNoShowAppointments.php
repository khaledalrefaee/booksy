<?php

namespace App\Console\Commands;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Models\Appointment;
use Illuminate\Console\Command;

/**
 * Moves appointments nobody checked in to no_show once their grace period runs out.
 *
 * Without this the status existed but nothing ever set it, so the no-show rate —
 * the number a salon owner most wants from a booking system — was whatever staff
 * remembered to click. Only pending and confirmed are eligible: anything from
 * arrived onwards means the customer is demonstrably here.
 */
class FlagNoShowAppointments extends Command
{
    protected $signature = 'appointments:flag-no-shows {--grace= : Minutes to wait past the start time}';

    protected $description = 'Mark un-checked-in appointments as no-show once their grace period has passed';

    public function handle(TransitionAppointment $transition): int
    {
        $grace  = (int) ($this->option('grace') ?? config('booksy.no_show_grace_minutes', 20));
        $cutoff = now()->subMinutes($grace);

        $flagged = 0;
        $failed  = 0;

        Appointment::query()
            ->whereIn('status', [
                AppointmentStatus::Pending->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->where('start_time', '<', $cutoff)
            // Guard against a backlog sweeping up ancient rows the first time
            // this runs on an existing database.
            ->where('start_time', '>', now()->subDay())
            ->chunkById(200, function ($appointments) use ($transition, &$flagged, &$failed) {
                foreach ($appointments as $appointment) {
                    $ok = $transition->attempt(
                        $appointment,
                        AppointmentStatus::NoShow,
                        TransitionActor::System,
                        ['automatic' => true, 'meta' => ['reason' => 'grace_period_elapsed']],
                    );

                    $ok ? $flagged++ : $failed++;
                }
            });

        $this->info("Flagged {$flagged} appointment(s) as no-show (grace {$grace}m).");

        if ($failed > 0) {
            $this->warn("{$failed} appointment(s) could not be transitioned.");
        }

        return self::SUCCESS;
    }
}
