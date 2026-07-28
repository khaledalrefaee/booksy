<?php

namespace App\Enums;

/**
 * How hard reception should try to fit this person in today.
 *
 * Backed by an int so the queue can ORDER BY priority, created_at and get the
 * obvious result: urgent first, then whoever has waited longest. The old list
 * ordered by preferred_start with nulls last, which buried every walk-in who
 * had no particular time in mind — the exact people standing in the salon.
 */
enum WaitlistPriority: int
{
    case Urgent   = 1;
    case Normal   = 2;
    case Flexible = 3;

    public function label(): string
    {
        return __(match ($this) {
            self::Urgent   => 'Urgent',
            self::Normal   => 'Normal',
            self::Flexible => 'Flexible',
        });
    }

    /** One-line explanation shown under the option, so the choice needs no training. */
    public function hint(): string
    {
        return __(match ($this) {
            self::Urgent   => 'Waiting in the salon right now',
            self::Normal   => 'Wants a slot today',
            self::Flexible => 'Any day this week is fine',
        });
    }

    public function color(): string
    {
        return match ($this) {
            self::Urgent   => '#ef4444',
            self::Normal   => '#0C6E74',
            self::Flexible => '#64748b',
        };
    }

    public static function default(): self
    {
        return self::Normal;
    }

    /** @return array<int, array{value:int,label:string,hint:string,color:string}> */
    public static function forFrontend(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[] = [
                'value' => $case->value,
                'label' => $case->label(),
                'hint'  => $case->hint(),
                'color' => $case->color(),
            ];
        }

        return $out;
    }
}
