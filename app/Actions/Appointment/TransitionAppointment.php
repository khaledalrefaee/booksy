<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Exceptions\IllegalStatusTransition;
use App\Models\Appointment;
use App\Models\AppointmentTransition;
use App\States\AppointmentStateMachine;
use App\Support\Auditor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The only place in the codebase permitted to write Appointment::$status.
 *
 * Anything that changes a status goes through here, which is what makes the
 * state machine real rather than decorative: the rules cannot be bypassed by
 * a stray ->update(['status' => …]) somewhere in a controller.
 */
class TransitionAppointment
{
    /**
     * @param  array{reason?: string|null, automatic?: bool, meta?: array, actorId?: int|null, actorName?: string|null}  $options
     *
     * @throws IllegalStatusTransition
     */
    public function __invoke(
        Appointment $appointment,
        AppointmentStatus $to,
        ?TransitionActor $actor = null,
        array $options = [],
    ): Appointment {
        $actor ??= self::currentActor();
        $from   = $appointment->status;
        $reason = $options['reason'] ?? null;

        $this->guard($from, $to, $actor, $reason);

        return DB::transaction(function () use ($appointment, $from, $to, $actor, $reason, $options) {
            $identity = $this->identify($actor, $options);

            $appointment->update([
                'status'                 => $to,
                'status_previous'        => $from->value,
                'status_changed_by_type' => $actor->value,
                'status_changed_by_id'   => $identity['id'],
                'status_changed_by_name' => $identity['name'],
                'status_changed_at'      => now(),
                'handled_at'             => now(),
                // The legacy column only ever held salon-side refusals.
                'rejection_reason'       => $to === AppointmentStatus::CancelledBySalon ? $reason : null,
            ]);

            AppointmentTransition::create([
                'appointment_id' => $appointment->id,
                'company_id'     => $appointment->company_id,
                'from_status'    => $from,
                'to_status'      => $to,
                'actor_type'     => $actor,
                'actor_id'       => $identity['id'],
                'actor_name'     => $identity['name'],
                'reason'         => $reason,
                'automatic'      => $options['automatic'] ?? false,
                'meta'           => $options['meta'] ?? null,
            ]);

            Auditor::logChange(
                "Appointment #{$appointment->id} {$from->value} → {$to->value}",
                $appointment,
                ['status' => $from->value],
                ['status' => $to->value],
            );

            return $appointment;
        });
    }

    /** Same move, but returns false instead of throwing when it is not legal. */
    public function attempt(
        Appointment $appointment,
        AppointmentStatus $to,
        ?TransitionActor $actor = null,
        array $options = [],
    ): bool {
        try {
            $this($appointment, $to, $actor, $options);

            return true;
        } catch (IllegalStatusTransition) {
            return false;
        }
    }

    /** @throws IllegalStatusTransition */
    protected function guard(AppointmentStatus $from, AppointmentStatus $to, TransitionActor $actor, ?string $reason): void
    {
        if (! AppointmentStateMachine::can($from, $to)) {
            throw IllegalStatusTransition::move($from, $to);
        }

        if (! AppointmentStateMachine::canBy($from, $to, $actor)) {
            throw IllegalStatusTransition::actor($from, $to, $actor);
        }

        if (AppointmentStateMachine::requiresReason($from, $to) && blank($reason)) {
            throw IllegalStatusTransition::reasonRequired($from, $to);
        }
    }

    /** @return array{id: int|null, name: string|null} */
    protected function identify(TransitionActor $actor, array $options): array
    {
        if (array_key_exists('actorId', $options) || array_key_exists('actorName', $options)) {
            return [
                'id'   => $options['actorId'] ?? null,
                'name' => $options['actorName'] ?? null,
            ];
        }

        if ($actor === TransitionActor::System) {
            return ['id' => null, 'name' => null];
        }

        $resolved = Auditor::actor();

        return ['id' => $resolved['id'] ?: null, 'name' => $resolved['name']];
    }

    /** Best guess at who is acting, from the authenticated guard. */
    public static function currentActor(): TransitionActor
    {
        return match (true) {
            Auth::guard('company')->check() => TransitionActor::Company,
            Auth::guard('web')->check()     => TransitionActor::Customer,
            default                         => TransitionActor::System,
        };
    }
}
