<?php

namespace App\Enums;

/**
 * Who is driving a status transition.
 *
 * Deliberately coarse. Fine-grained per-branch permissions ("this receptionist
 * may only touch branch 3") belong to the RBAC layer and are enforced on top of
 * this — the state machine answers "is this transition legal at all", not
 * "is this specific user allowed right now".
 */
enum TransitionActor: string
{
    /** Salon owner / manager acting in the company panel. */
    case Company = 'company';

    /** Staff member acting in the employee view. */
    case Employee = 'employee';

    /** The customer, via the booking app or a confirm/cancel email link. */
    case Customer = 'customer';

    /** The scheduler or an internal side effect — never a human. */
    case System = 'system';

    public function label(): string
    {
        return __(match ($this) {
            self::Company  => 'Salon',
            self::Employee => 'Employee',
            self::Customer => 'Customer',
            self::System   => 'System',
        });
    }
}
