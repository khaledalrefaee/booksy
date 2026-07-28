<?php

namespace App\States;

use App\Enums\AppointmentStatus as S;
use App\Enums\TransitionActor as A;

/**
 * The legal moves of an appointment's life cycle.
 *
 * Every status change in the system must be checked against this class — see
 * App\Actions\Appointment\TransitionAppointment, which is the only place allowed
 * to write Appointment::$status.
 *
 * Design note — express transitions:
 * The granular path (confirmed → arrived → in_progress → awaiting_payment →
 * completed) is available but never forced. Plenty of salons will not track
 * check-in at all, and making them click through four states to close a
 * walk-in would be slower than what they have today. So confirmed and arrived
 * both offer a direct jump to awaiting_payment / completed.
 */
class AppointmentStateMachine
{
    /**
     * from => [ to => [actors, auto, requiresReason] ]
     *
     * `auto` marks a transition the system performs by itself; a human may
     * still trigger it if listed in `actors` (a receptionist can mark a no-show
     * before the scheduler gets to it).
     *
     * @return array<string, array<string, array{actors: A[], auto: bool, requiresReason: bool}>>
     */
    public static function map(): array
    {
        return [
            S::Draft->value => [
                S::Confirmed->value           => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::Pending->value             => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
                S::CancelledBySalon->value    => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
            ],

            S::Pending->value => [
                S::Confirmed->value           => ['actors' => [A::Company, A::Employee, A::Customer], 'auto' => false, 'requiresReason' => false],
                S::CancelledBySalon->value    => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => true],
                S::CancelledByCustomer->value => ['actors' => [A::Customer, A::Company],  'auto' => false, 'requiresReason' => false],
                S::NoShow->value              => ['actors' => [A::System, A::Company],    'auto' => true,  'requiresReason' => false],
            ],

            S::Confirmed->value => [
                S::Arrived->value             => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::CancelledBySalon->value    => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => true],
                S::CancelledByCustomer->value => ['actors' => [A::Customer, A::Company], 'auto' => false, 'requiresReason' => false],
                S::NoShow->value              => ['actors' => [A::System, A::Company],   'auto' => true,  'requiresReason' => false],
                // Express paths — see class docblock.
                S::AwaitingPayment->value     => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::Completed->value           => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
            ],

            S::Arrived->value => [
                S::InProgress->value          => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::CancelledBySalon->value    => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => true],
                S::AwaitingPayment->value     => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::Completed->value           => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
            ],

            S::InProgress->value => [
                S::Paused->value              => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::AwaitingPayment->value     => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                // Reception can settle the sale from the checkout screen without
                // first parking the appointment in awaiting_payment.
                S::Completed->value           => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
            ],

            S::Paused->value => [
                S::InProgress->value          => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::AwaitingPayment->value     => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
                S::Completed->value           => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
            ],

            S::AwaitingPayment->value => [
                S::Completed->value           => ['actors' => [A::Company, A::Employee], 'auto' => false, 'requiresReason' => false],
            ],

            // A no-show can be corrected: the customer turned up late, or
            // reception simply forgot to check them in.
            S::NoShow->value => [
                S::Arrived->value             => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
                S::CancelledByCustomer->value => ['actors' => [A::Company],              'auto' => false, 'requiresReason' => false],
            ],

            // Terminal.
            S::Completed->value           => [],
            S::CancelledByCustomer->value => [],
            S::CancelledBySalon->value    => [],
        ];
    }

    /** Is this move legal at all, regardless of who asks? */
    public static function can(S $from, S $to): bool
    {
        return isset(self::map()[$from->value][$to->value]);
    }

    /** Is this move legal for this kind of actor? */
    public static function canBy(S $from, S $to, A $actor): bool
    {
        $rule = self::map()[$from->value][$to->value] ?? null;

        return $rule !== null && in_array($actor, $rule['actors'], true);
    }

    public static function requiresReason(S $from, S $to): bool
    {
        return self::map()[$from->value][$to->value]['requiresReason'] ?? false;
    }

    /**
     * Statuses this actor may move to right now — this is what the UI renders
     * as action buttons, so the buttons can never offer an illegal move.
     *
     * @return S[]
     */
    public static function allowedFor(S $from, A $actor): array
    {
        $out = [];

        foreach (self::map()[$from->value] ?? [] as $to => $rule) {
            if (in_array($actor, $rule['actors'], true)) {
                $out[] = S::from($to);
            }
        }

        return $out;
    }

    /**
     * The same thing, shaped for the frontend so the JS never hardcodes a
     * transition table of its own.
     *
     * @return array<string, string[]>
     */
    public static function allowedMapFor(A $actor): array
    {
        $out = [];

        foreach (self::map() as $from => $_) {
            $out[$from] = array_map(
                fn (S $s) => $s->value,
                self::allowedFor(S::from($from), $actor)
            );
        }

        return $out;
    }
}
