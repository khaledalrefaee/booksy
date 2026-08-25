<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Models\Appointment;
use App\Models\AppointmentConfirmation;
use App\Services\StaffNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentConfirmController extends Controller
{
    public function __construct(private readonly TransitionAppointment $transition)
    {
        // These pages are reached from the SMS/WhatsApp message, which is written
        // in Arabic for our Syrian audience — so match it. A visitor who set a
        // session locale still wins.
        if (! session()->has('locale')) {
            app()->setLocale('ar');
        }
    }

    /** GET — confirm the whole visit immediately. */
    public function confirm(string $token)
    {
        $confirmation = AppointmentConfirmation::where('token', $token)->first();
        if ($guard = $this->guard($confirmation)) return $guard;

        $appointment = $confirmation->appointment;
        if (!$appointment) return $this->page('error', '❌', __('Not found'), __('Appointment not found.'));

        foreach ($this->visit($appointment) as $appt) {
            // The state machine only allows confirm from pending, so no status
            // check to duplicate here — illegal transitions are simply ignored.
            $this->transition->attempt(
                $appt,
                AppointmentStatus::Confirmed,
                TransitionActor::Customer,
                ['meta' => ['source' => 'reminder_link']],
            );
        }

        $confirmation->update(['action' => 'confirm', 'acted_at' => now()]);
        StaffNotificationService::customerConfirmedViaWhatsApp($appointment);

        return $this->page('success', '✅', __('Appointment confirmed!'),
            __('Thank you! Your appointment on :date at :time is confirmed. We look forward to seeing you. 💛', [
                'date' => $appointment->start_time->translatedFormat('l d/m'),
                'time' => $appointment->start_time->format('g:i A'),
            ]), $appointment);
    }

    /** GET — show the branded cancel page with a reason picker (no cancel yet). */
    public function cancelForm(string $token)
    {
        $confirmation = AppointmentConfirmation::where('token', $token)->first();
        if ($guard = $this->guard($confirmation)) return $guard;

        $appointment = $confirmation->appointment;
        if (!$appointment) return $this->page('error', '❌', __('Not found'), __('Appointment not found.'));

        return response()->view('appointment.cancel-form', [
            'token'       => $token,
            'appointment' => $appointment,
            'reasons'     => $this->cancelReasons(),
        ]);
    }

    /** POST — cancel the whole visit with the customer's reason. */
    public function cancel(Request $request, string $token)
    {
        $confirmation = AppointmentConfirmation::where('token', $token)->first();
        if ($guard = $this->guard($confirmation)) return $guard;

        $appointment = $confirmation->appointment;
        if (!$appointment) return $this->page('error', '❌', __('Not found'), __('Appointment not found.'));

        $reasons = $this->cancelReasons();
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys($reasons))],
            'note'   => ['nullable', 'string', 'max:400'],
        ]);

        $reason = $reasons[$data['reason']];
        if (!empty($data['note'])) {
            $reason .= ' — ' . trim($data['note']);
        }

        foreach ($this->visit($appointment) as $appt) {
            $this->transition->attempt(
                $appt,
                AppointmentStatus::CancelledByCustomer,
                TransitionActor::Customer,
                [
                    'reason' => $reason,   // stored in the transition's reason column + shown in history
                    'meta'   => ['source' => 'reminder_link', 'reason' => $reason],
                ],
            );
        }

        $confirmation->update(['action' => 'cancel', 'reason' => $reason, 'acted_at' => now()]);
        StaffNotificationService::customerCancelledViaWhatsApp($appointment);

        return $this->page('warning', '⚠️', __('Appointment cancelled'),
            __('Thank you for letting us know. Your appointment has been cancelled — you can book a new one anytime. 🙏'), $appointment);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** Self + every sibling of a grouped visit; a single booking is just itself. */
    private function visit(Appointment $appointment)
    {
        if (! $appointment->booking_group_id) {
            return collect([$appointment]);
        }

        return Appointment::where('booking_group_id', $appointment->booking_group_id)->get();
    }

    /** Shared early-exit pages for an invalid / used / expired link. */
    private function guard(?AppointmentConfirmation $confirmation)
    {
        if (!$confirmation) {
            return $this->page('error', '❌', __('Invalid link'), __('This confirmation link is not valid.'));
        }
        if ($confirmation->isUsed()) {
            $doneAction = $confirmation->action === 'confirm' ? __('confirmed') : __('cancelled');
            return $this->page('info', 'ℹ️', __('Already done'), __('This appointment was already :action.', ['action' => $doneAction]));
        }
        if ($confirmation->isExpired()) {
            return $this->page('warning', '⏰', __('Link expired'), __('This confirmation link has expired.'));
        }
        return null;
    }

    /** Preset cancellation reasons (value => translated label). */
    private function cancelReasons(): array
    {
        return [
            'emergency' => __('Emergency came up'),
            'conflict'  => __('Schedule conflict'),
            'changed'   => __('Changed my mind'),
            'found'     => __('Found another time'),
            'other'     => __('Other reason'),
        ];
    }

    private function page(string $type, string $icon, string $title, string $message, ?Appointment $appointment = null)
    {
        $colors = [
            'success' => '#6b8e23', // olive
            'error'   => '#ef4444',
            'warning' => '#f59e0b',
            'info'    => '#8a9a5b',
        ];
        $color = $colors[$type] ?? '#6b8e23';

        return response()->view('confirm-appointment', compact('icon', 'title', 'message', 'color', 'type', 'appointment'));
    }
}
