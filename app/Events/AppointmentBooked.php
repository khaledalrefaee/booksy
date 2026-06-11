<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentBooked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(public Appointment $appointment)
    {
        $appt    = $appointment->load(['service', 'employee', 'customer', 'branch']);
        $isAr    = false; // broadcast in both, frontend picks

        $this->payload = [
            'id'             => $appt->id,
            'branch_id'      => $appt->branch_id,
            'employee_id'    => $appt->employee_id,
            'start_time'     => $appt->start_time->toDateTimeString(),
            'end_time'       => $appt->end_time->toDateTimeString(),
            'start_display'  => $appt->start_time->format('D d M · H:i'),
            'service_name_ar'=> $appt->service?->name_ar,
            'service_name_en'=> $appt->service?->name_en,
            'service_duration'=> $appt->service?->duration_minutes,
            'price'          => $appt->total_price,
            'customer_name'  => $appt->customer?->name,
            'customer_phone' => $appt->customer?->phone,
            'employee_name_ar'=> $appt->employee?->name_ar,
            'employee_name_en'=> $appt->employee?->name_en,
            'branch_name_ar' => $appt->branch?->name_ar,
            'branch_name_en' => $appt->branch?->name_en,
            'status'         => $appt->status,
        ];
    }

    /** Broadcast on owner channel + employee channel */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('branch.' . $this->appointment->branch_id),
        ];

        if ($this->appointment->employee_id) {
            $channels[] = new PrivateChannel('employee.' . $this->appointment->employee_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'appointment.booked';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
