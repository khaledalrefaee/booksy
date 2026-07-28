<?php

namespace App\Enums;

/**
 * The single source of truth for appointment status: value, colour, icon and label.
 *
 * Before this enum the same status→colour map lived in four places
 * (AppointmentController twice, index.blade.php, index-v2.blade.php) and drifted.
 * Nothing may hardcode a status string any more — PHP reads the cases, the
 * frontend reads {@see self::forFrontend()}.
 *
 * Transitions between cases are NOT defined here: see AppointmentStateMachine.
 */
enum AppointmentStatus: string
{
    /** Reception started a booking but never finished it. */
    case Draft = 'draft';

    /** Customer booked through the app; the salon has not answered yet. */
    case Pending = 'pending';

    /** The salon accepted (or branch policy auto-accepts). */
    case Confirmed = 'confirmed';

    /** Customer physically checked in at reception. */
    case Arrived = 'arrived';

    /** The employee started working. */
    case InProgress = 'in_progress';

    /** Work interrupted — colour setting, customer stepped out, employee break. */
    case Paused = 'paused';

    /**
     * Service finished, money not collected yet.
     *
     * This case exists to close a real hole: previously "completed" meant both
     * "service done" and "customer paid", so a customer who walked out without
     * paying had no representation in the system at all.
     */
    case AwaitingPayment = 'awaiting_payment';

    /** Service delivered and settled (paid, or consciously written to debt). */
    case Completed = 'completed';

    /** Slot passed without a check-in. Set automatically by the scheduler. */
    case NoShow = 'no_show';

    /**
     * Cancellations are split by who caused them, because they are not
     * commercially equivalent: one may carry a fee for the customer, the other
     * belongs in the salon's own service-quality report.
     */
    case CancelledByCustomer = 'cancelled_by_customer';
    case CancelledBySalon    = 'cancelled_by_salon';

    /** Human-readable label; translated through lang/{locale}.json. */
    public function label(): string
    {
        return __(match ($this) {
            self::Draft               => 'Draft',
            self::Pending             => 'Pending confirmation',
            self::Confirmed           => 'Confirmed',
            self::Arrived             => 'Arrived',
            self::InProgress          => 'In progress',
            self::Paused              => 'Paused',
            self::AwaitingPayment     => 'Awaiting payment',
            self::Completed           => 'Completed',
            self::NoShow              => 'No show',
            self::CancelledByCustomer => 'Cancelled by customer',
            self::CancelledBySalon    => 'Cancelled by salon',
        });
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft               => '#94a3b8',
            self::Pending             => '#f59e0b',
            self::Confirmed           => '#10b981',
            self::Arrived             => '#06b6d4',
            self::InProgress          => '#3b82f6',
            self::Paused              => '#a855f7',
            self::AwaitingPayment     => '#ec4899',
            self::Completed           => '#6366f1',
            self::NoShow              => '#64748b',
            self::CancelledByCustomer => '#f97316',
            self::CancelledBySalon    => '#ef4444',
        };
    }

    /** Feather icon name — the icon set already used across the panel. */
    public function icon(): string
    {
        return match ($this) {
            self::Draft               => 'edit-3',
            self::Pending             => 'clock',
            self::Confirmed           => 'check-circle',
            self::Arrived             => 'log-in',
            self::InProgress          => 'play-circle',
            self::Paused              => 'pause-circle',
            self::AwaitingPayment     => 'credit-card',
            self::Completed           => 'check-square',
            self::NoShow              => 'user-x',
            self::CancelledByCustomer => 'x-circle',
            self::CancelledBySalon    => 'slash',
        };
    }

    /** No transition leads out of a terminal status. */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::CancelledByCustomer,
            self::CancelledBySalon,
        ], true);
    }

    /** The customer is in the salon right now — drives the "on site" board. */
    public function isOnSite(): bool
    {
        return in_array($this, [
            self::Arrived,
            self::InProgress,
            self::Paused,
            self::AwaitingPayment,
        ], true);
    }

    /**
     * Statuses that occupy their slot, so conflict checks must count them.
     * A draft holds nothing; cancellations and no-shows release the slot.
     */
    public static function blocking(): array
    {
        return [
            self::Pending,
            self::Confirmed,
            self::Arrived,
            self::InProgress,
            self::Paused,
            self::AwaitingPayment,
            self::Completed,
        ];
    }

    /** @return string[] blocking statuses as raw values, for whereIn(). */
    public static function blockingValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::blocking());
    }

    /** Cancelled either way — for reporting that does not care about the cause. */
    public static function cancelled(): array
    {
        return [self::CancelledByCustomer, self::CancelledBySalon];
    }

    /**
     * Everything the frontend needs, serialised once into the page.
     * Replaces the hand-maintained $statusDefs array in the Blade template.
     *
     * @return array<string, array{value:string,label:string,color:string,icon:string,terminal:bool,onSite:bool}>
     */
    public static function forFrontend(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[$case->value] = [
                'value'    => $case->value,
                'label'    => $case->label(),
                'color'    => $case->color(),
                'icon'     => $case->icon(),
                'terminal' => $case->isTerminal(),
                'onSite'   => $case->isOnSite(),
            ];
        }

        return $out;
    }

    /**
     * Pre-enum values → their replacement.
     * Used by the data migration; kept here so the mapping is documented
     * next to the cases rather than buried in a migration file.
     */
    public static function legacyMap(): array
    {
        return [
            'rejected'  => self::CancelledBySalon->value,
            // Plain 'cancelled' carried no cause. Customer cancellation is by far
            // the common case, so it becomes the customer variant; salon-side
            // cancellations were recorded as 'rejected'.
            'cancelled' => self::CancelledByCustomer->value,
        ];
    }
}
