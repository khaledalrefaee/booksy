<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * "Something about this branch's appointments changed" — sent over WebSocket
 * the instant an appointment is created, edited, status-changed or deleted, so
 * open dashboards / calendars refresh in place without polling. Deliberately
 * tiny (no personal data): the page re-reads what it shows from the server.
 *
 * ShouldBroadcastNow: goes straight to Reverb instead of waiting for a queue
 * worker, so it is instant. Callers dispatch it after the response.
 */
class AppointmentsChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $branchId,
        public int $appointmentId,
        public string $change,   // created | updated | deleted
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('branch.' . $this->branchId)];
    }

    public function broadcastAs(): string
    {
        return 'appointments.changed';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->appointmentId, 'change' => $this->change];
    }
}
