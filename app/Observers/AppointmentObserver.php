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
use Illuminate\Support\Facades\Cache;

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
            // Group bookings create one row per service/guest but must reach the
            // customer as ONE message. The first row to run claims the group lock;
            // the rest bail so nothing double-sends. sendAppointmentBooked() itself
            // aggregates every sibling into a single message.
            if ($appointment->booking_group_id) {
                $lock = 'booked_msg_' . $appointment->booking_group_id;
                if (! Cache::add($lock, 1, 600)) return;
            }
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

        // Live-update the branch board / employee screen (optional; never block).
        try {
            $prev = $appointment->getOriginal('status');
            $prev = $prev instanceof \App\Enums\AppointmentStatus ? $prev->value : (is_string($prev) ? $prev : $appointment->status_previous);
            event(new \App\Events\AppointmentStatusChanged($appointment, $prev));
        } catch (\Throwable $e) {
            // Broadcasting is optional; a missing Reverb node must not fail the write.
        }

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

        // A salon-side cancellation straight out of "pending" is a rejection of the
        // request — send the dedicated "couldn't confirm" message with a rebook link.
        $isRejection = $appointment->status === AppointmentStatus::CancelledBySalon
            && $appointment->status_previous === AppointmentStatus::Pending->value;

        dispatch(function () use ($appointment, $isRejection) {
            $wa = app(WhatsappService::class);
            $isRejection ? $wa->sendAppointmentRejected($appointment) : $wa->sendAppointmentCancelled($appointment);

            // The freed slot may match someone on the online waitlist — tell them.
            try { app(\App\Services\WaitlistService::class)->notifyForFreedSlot($appointment); }
            catch (\Throwable $e) { /* waitlist is best-effort; never block the cancel */ }
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
