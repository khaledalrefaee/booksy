<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired once per visit when the customer/venue moves it to a new time. */
class AppointmentRescheduled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(public Appointment $appointment, public string $oldStart)
    {
        $a = $appointment->loadMissing(['service', 'employee', 'branch']);
        $this->payload = [
            'id'         => $a->id,
            'branch_id'  => $a->branch_id,
            'employee_id'=> $a->employee_id,
            'old_start'  => $oldStart,
            'new_start'  => $a->start_time->toDateTimeString(),
            'new_display'=> $a->start_time->format('d/m — g:i A'),
            'status'     => $a->status->value,
        ];
    }

    public function broadcastOn(): array
    {
        $ch = [new PrivateChannel('branch.' . $this->appointment->branch_id)];
        if ($this->appointment->employee_id) {
            $ch[] = new PrivateChannel('employee.' . $this->appointment->employee_id);
        }
        return $ch;
    }

    public function broadcastAs(): string
    {
        return 'appointment.rescheduled';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
