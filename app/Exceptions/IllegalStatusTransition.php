<?php

namespace App\Exceptions;

use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use RuntimeException;

class IllegalStatusTransition extends RuntimeException
{
    public static function move(AppointmentStatus $from, AppointmentStatus $to): self
    {
        return new self("Cannot move an appointment from '{$from->value}' to '{$to->value}'.");
    }

    public static function actor(AppointmentStatus $from, AppointmentStatus $to, TransitionActor $actor): self
    {
        return new self("A '{$actor->value}' actor may not move an appointment from '{$from->value}' to '{$to->value}'.");
    }

    public static function reasonRequired(AppointmentStatus $from, AppointmentStatus $to): self
    {
        return new self("Moving from '{$from->value}' to '{$to->value}' requires a reason.");
    }
}
