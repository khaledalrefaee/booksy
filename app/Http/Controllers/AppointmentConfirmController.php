<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Models\AppointmentConfirmation;
use App\Services\StaffNotificationService;

class AppointmentConfirmController extends Controller
{
    public function __construct(private readonly TransitionAppointment $transition)
    {
    }

    public function handle(string $token, string $action)
    {
        $confirmation = AppointmentConfirmation::where('token', $token)->first();

        if (!$confirmation) {
            return $this->renderPage('❌', __('Invalid link'), __('This confirmation link is not valid.'), 'error');
        }

        if ($confirmation->isUsed()) {
            $doneAction = $confirmation->action === 'confirm' ? __('confirmed') : __('cancelled');
            return $this->renderPage('ℹ️', __('Already done'), __('This appointment was already :action.', ['action' => $doneAction]), 'info');
        }

        if ($confirmation->isExpired()) {
            return $this->renderPage('⏰', __('Link expired'), __('This confirmation link has expired.'), 'warning');
        }

        $appointment = $confirmation->appointment;
        if (!$appointment) {
            return $this->renderPage('❌', __('Not found'), __('Appointment not found.'), 'error');
        }

        if ($action === 'confirm') {
            // attempt(): the state machine already knows a confirm is only legal
            // from pending, so there is no status check to duplicate here.
            $this->transition->attempt(
                $appointment,
                AppointmentStatus::Confirmed,
                TransitionActor::Customer,
                ['meta' => ['source' => 'whatsapp_link']],
            );

            $confirmation->update(['action' => 'confirm', 'acted_at' => now()]);
            StaffNotificationService::customerConfirmedViaWhatsApp($appointment);

            return $this->renderPage('✅', __('Appointment confirmed!'),
                __('Your appointment on :date at :time has been confirmed.', [
                    'date' => $appointment->start_time->translatedFormat('l d M Y'),
                    'time' => $appointment->start_time->format('h:i A'),
                ]), 'success');
        }

        if ($action === 'cancel') {
            $this->transition->attempt(
                $appointment,
                AppointmentStatus::CancelledByCustomer,
                TransitionActor::Customer,
                ['meta' => ['source' => 'whatsapp_link']],
            );

            $confirmation->update(['action' => 'cancel', 'acted_at' => now()]);
            StaffNotificationService::customerCancelledViaWhatsApp($appointment);

            return $this->renderPage('⚠️', __('Appointment cancelled'),
                __('Your appointment has been cancelled. You can book a new one anytime.'), 'warning');
        }

        return $this->renderPage('❌', __('Invalid action'), __('Invalid action.'), 'error');
    }

    private function renderPage(string $icon, string $title, string $message, string $type)
    {
        $colors = [
            'success' => '#22c55e',
            'error'   => '#ef4444',
            'warning' => '#f59e0b',
            'info'    => '#667eea',
        ];
        $color = $colors[$type] ?? '#667eea';

        return response()->view('confirm-appointment', compact('icon', 'title', 'message', 'color'));
    }
}
