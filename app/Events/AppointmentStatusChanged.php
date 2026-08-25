<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an appointment's status changes (confirm, cancel, arrive, …).
 * Lets the branch board and the employee screen reflect the new state live,
 * without a refresh — the companion of {@see AppointmentBooked} for the rest of
 * the lifecycle.
 */
class AppointmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(public Appointment $appointment, public ?string $previous = null)
    {
        $appt = $appointment->loadMissing(['service', 'employee', 'branch']);

        $this->payload = [
            'id'          => $appt->id,
            'branch_id'   => $appt->branch_id,
            'employee_id' => $appt->employee_id,
            'status'      => $appt->status->value,
            'status_label'=> $appt->status->label(),
            'status_color'=> $appt->status->color(),
            'previous'    => $previous,
            'start_time'  => $appt->start_time->toDateTimeString(),
            'start_display'=> $appt->start_time->format('d/m — g:i A'),
        ];
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('branch.' . $this->appointment->branch_id)];

        if ($this->appointment->employee_id) {
            $channels[] = new PrivateChannel('employee.' . $this->appointment->employee_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'appointment.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
