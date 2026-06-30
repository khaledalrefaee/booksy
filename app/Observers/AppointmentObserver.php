<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\LoyaltyPointLog;
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
            'completed' => $this->onCompleted($appointment),
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

    private function onCompleted(Appointment $appointment): void
    {
        if (!$appointment->customer_id) return;

        $customer = $appointment->customer;
        if (!$customer) return;

        $loyaltyConfig = config('booksy.loyalty', []);
        $pointsPerVisit = $loyaltyConfig['points_per_visit'] ?? 0;
        $pointsPerUnit  = $loyaltyConfig['points_per_currency_unit'] ?? 0;

        $totalPoints = $pointsPerVisit;

        if ($pointsPerUnit > 0 && $appointment->total_price > 0) {
            $totalPoints += (int) floor((float) $appointment->total_price / $pointsPerUnit);
        }

        if ($totalPoints > 0) {
            LoyaltyPointLog::create([
                'customer_id'    => $customer->id,
                'points'         => $totalPoints,
                'reason'         => __('Completed appointment'),
                'reference_type' => Appointment::class,
                'reference_id'   => $appointment->id,
                'created_at'     => now(),
            ]);

            $customer->increment('loyalty_points', $totalPoints);
        }
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
