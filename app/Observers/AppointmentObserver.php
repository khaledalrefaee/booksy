<?php

namespace App\Observers;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentTransition;
use App\Models\CustomerCommunication;
use App\Support\Auditor;
use App\Models\LoyaltyPointLog;
use App\Services\StaffNotificationService;
use App\Services\WhatsappService;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        // Opening entry of the timeline, so every appointment's history starts
        // at its birth rather than at its first status change.
        $actor = TransitionAppointment::currentActor();
        AppointmentTransition::create([
            'appointment_id' => $appointment->id,
            'company_id'     => $appointment->company_id,
            'from_status'    => null,
            'to_status'      => $appointment->status,
            'actor_type'     => $actor,
            'actor_id'       => Auditor::actor()['id'] ?: null,
            'actor_name'     => Auditor::actor()['name'],
            'automatic'      => false,
        ]);

        StaffNotificationService::appointmentBooked($appointment);

        dispatch(function () use ($appointment) {
            $appointment->load(['branch', 'service', 'customer']);
            app(WhatsappService::class)->sendAppointmentBooked($appointment);
        })->afterResponse();

        if ($appointment->customer_id) {
            $appointment->loadMissing(['service', 'branch']);
            $serviceName = $appointment->service?->localizedName() ?? __('Appointment');
            $branchName  = $appointment->branch?->localizedName() ?? '';
            CustomerCommunication::create([
                'customer_id'     => $appointment->customer_id,
                'branch_id'       => $appointment->branch_id,
                'type'            => 'booking',
                'direction'       => 'inbound',
                'message'         => $serviceName . ($branchName ? ' — ' . $branchName : ''),
                'created_by_name' => 'system',
                'created_at'      => now(),
            ]);
        }
    }

    public function updated(Appointment $appointment): void
    {
        if (!$appointment->wasChanged('status')) return;

        $newStatus = $appointment->status;
        $appointment->load(['branch', 'service', 'customer']);

        match ($newStatus) {
            AppointmentStatus::Confirmed => $this->onConfirmed($appointment),
            AppointmentStatus::Completed => $this->onCompleted($appointment),
            AppointmentStatus::CancelledByCustomer,
            AppointmentStatus::CancelledBySalon => $this->onCancelled($appointment),
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

        if ($appointment->customer_id) {
            CustomerCommunication::create([
                'customer_id'     => $appointment->customer_id,
                'branch_id'       => $appointment->branch_id,
                'type'            => 'confirmation',
                'direction'       => 'outbound',
                'message'         => $appointment->service?->localizedName() ?? __('Appointment'),
                'created_by_name' => 'system',
                'created_at'      => now(),
            ]);
        }
    }

    private function onCompleted(Appointment $appointment): void
    {
        if (!$appointment->customer_id) return;

        $customer = $appointment->customer;
        if (!$customer) return;

        // Loyalty points — read from branch settings
        $appointment->loadMissing('branch');
        $branch = $appointment->branch;

        $pointsPerVisit        = (int) ($branch->loyalty_points_per_visit ?? config('booksy.loyalty.points_per_visit', 10));
        $pointsPerExtraService = (int) ($branch->loyalty_points_per_extra_service ?? 5);
        $pointsPerUnit         = (int) ($branch->loyalty_points_per_currency_unit ?? config('booksy.loyalty.points_per_currency_unit', 10000));

        // Count services in this appointment
        $serviceCount = $appointment->appointmentServices()->count();
        $extraServices = max(0, $serviceCount - 1);

        $totalPoints = $pointsPerVisit + ($extraServices * $pointsPerExtraService);

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

        // Communication log
        CustomerCommunication::create([
            'customer_id'     => $customer->id,
            'branch_id'       => $appointment->branch_id,
            'type'            => 'completion',
            'direction'       => 'inbound',
            'message'         => ($appointment->service?->localizedName() ?? __('Appointment'))
                                 . ($totalPoints > 0 ? ' — +' . $totalPoints . ' ' . __('pts') : ''),
            'created_by_name' => 'system',
            'created_at'      => now(),
        ]);
    }

    private function onCancelled(Appointment $appointment): void
    {
        StaffNotificationService::appointmentCancelled($appointment);

        dispatch(function () use ($appointment) {
            app(WhatsappService::class)->sendAppointmentCancelled($appointment);
        })->afterResponse();

        if ($appointment->customer_id) {
            $changedBy = $appointment->status_changed_by_type ?? 'staff';
            CustomerCommunication::create([
                'customer_id'     => $appointment->customer_id,
                'branch_id'       => $appointment->branch_id,
                'type'            => 'cancellation',
                'direction'       => 'inbound',
                'message'         => ($appointment->service?->localizedName() ?? __('Appointment'))
                                     . ' — ' . __('Cancelled by :by', ['by' => __($changedBy)]),
                'created_by_name' => 'system',
                'created_at'      => now(),
            ]);
        }
    }
}
