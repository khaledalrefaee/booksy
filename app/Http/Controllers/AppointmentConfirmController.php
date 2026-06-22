<?php

namespace App\Http\Controllers;

use App\Models\AppointmentConfirmation;
use App\Services\StaffNotificationService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class AppointmentConfirmController extends Controller
{
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
            if ($appointment->status === 'pending') {
                $appointment->update(['status' => 'confirmed']);
            }
            $confirmation->update(['action' => 'confirm', 'acted_at' => now()]);
            StaffNotificationService::customerConfirmedViaWhatsApp($appointment);

            return $this->renderPage('✅', __('Appointment confirmed!'),
                __('Your appointment on :date at :time has been confirmed.', [
                    'date' => $appointment->start_time->translatedFormat('l d M Y'),
                    'time' => $appointment->start_time->format('h:i A'),
                ]), 'success');
        }

        if ($action === 'cancel') {
            if (in_array($appointment->status, ['pending', 'confirmed'])) {
                $appointment->update(['status' => 'cancelled']);
            }
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
