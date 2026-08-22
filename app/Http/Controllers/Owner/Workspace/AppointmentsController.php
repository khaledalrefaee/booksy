<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Appointment;
use App\Models\BlockedTime;
use App\Models\Company;
use App\Models\WaitlistEntry;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Company Workspace — Appointments tab (appointments + waitlist + blocked times).
 * Monitoring plus safe inline status changes via the existing TransitionAppointment
 * state machine. Complex checkout/payment flows defer to the full editor.
 */
class AppointmentsController extends Controller
{
    use ScopesCompany;

    /** Status changes an owner may apply inline without touching payment/checkout. */
    private const SAFE_TRANSITIONS = ['confirmed', 'arrived', 'no_show', 'cancelled_by_customer'];

    public function index(Company $company)
    {
        $appointments = Appointment::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'customer', 'employee', 'service'])
            ->orderByDesc('start_time')
            ->limit(100)
            ->get();

        $waitlist = WaitlistEntry::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'customer', 'service'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $blockedTimes = BlockedTime::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'employee'])
            ->where('end_time', '>=', now()->subDay())
            ->orderBy('start_time')
            ->limit(50)
            ->get();

        return $this->tab('appointments', $company, compact('appointments', 'waitlist', 'blockedTimes'));
    }

    public function detail(Company $company, Appointment $appointment)
    {
        abort_unless($appointment->company_id === $company->id, 404);
        $appointment->load(['branch', 'customer', 'employee', 'service']);

        return $this->drawer('appointment-detail', $company, [
            'appointment'      => $appointment,
            'safeTransitions'  => self::SAFE_TRANSITIONS,
        ]);
    }

    public function updateStatus(Company $company, Appointment $appointment, Request $request, TransitionAppointment $transition)
    {
        abort_unless($appointment->company_id === $company->id, 404);
        abort_unless(Gate::allows('owner-can', 'appointments.manage'), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::SAFE_TRANSITIONS)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $from = $appointment->status?->value ?? (string) $appointment->status;

        try {
            $transition($appointment, AppointmentStatus::from($validated['status']), null, [
                'reason' => $validated['reason'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return $this->actionFail(__('This status change is not allowed for this appointment.'));
        }

        OwnerAudit::record('company.appointment.status-update', $appointment,
            old: ['status' => $from], new: ['status' => $validated['status']], reason: $validated['reason'] ?? null);

        return $this->actionOk(__('Appointment updated.'));
    }

    public function resolveWaitlist(Company $company, WaitlistEntry $waitlistEntry, Request $request)
    {
        abort_unless($waitlistEntry->company_id === $company->id, 404);
        abort_unless(Gate::allows('owner-can', 'appointments.manage'), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:booked,cancelled,contacted'],
        ]);

        $waitlistEntry->update(['status' => $validated['status']]);
        OwnerAudit::record('company.waitlist.resolve', $waitlistEntry, new: ['status' => $validated['status']]);

        return $this->actionOk(__('Waitlist entry updated.'));
    }

    public function destroyBlocked(Company $company, BlockedTime $blockedTime)
    {
        abort_unless($blockedTime->company_id === $company->id, 404);
        abort_unless(Gate::allows('owner-can', 'appointments.manage'), 403);

        OwnerAudit::record('company.blocked-time.delete', $blockedTime,
            old: ['start_time' => (string) $blockedTime->start_time]);
        $blockedTime->delete();

        return $this->actionOk(__('Blocked time removed.'));
    }
}
