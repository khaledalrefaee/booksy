<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\StaffNotificationService;
use App\Services\WhatsappService;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        StaffNotificationService::appointmentBooked($appointment);

        dispatch(function () use ($appointment) {
            $appointment->load(['branch', 'service', 'customer']);
            app(WhatsappService::class)->sendAppointmentBooked($appointment);
        })->afterResponse();
    }

    public function updated(Appointment $appointment): void
    {
        if (!$appointment->wasChanged('status')) return;

        $newStatus = $appointment->status;
        $appointment->load(['branch', 'service', 'customer']);

        match ($newStatus) {
            'confirmed' => $this->onConfirmed($appointment),
            'cancelled', 'rejected' => $this->onCancelled($appointment),
            default => null,
        };
    }

    private function onConfirmed(Appointment $appointment): void
    {
        $changedBy = $appointment->status_changed_by_type ?? '';
        if ($changedBy === 'customer') return;

        StaffNotificationService::appointmentConfirmed($appointment);

        dispatch(function () use ($appointment) {
            app(WhatsappService::class)->sendAppointmentConfirmed($appointment);
        })->afterResponse();
    }

    private function onCancelled(Appointment $appointment): void
    {
        $changedBy = $appointment->status_changed_by_type ?? '';
        if ($changedBy === 'customer') return;

        StaffNotificationService::appointmentCancelled($appointment);

        dispatch(function () use ($appointment) {
            app(WhatsappService::class)->sendAppointmentCancelled($appointment);
        })->afterResponse();
    }
}
