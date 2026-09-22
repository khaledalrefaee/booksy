<?php

namespace App\Enums;

/**
 * Where a booking came from — the single source of truth for the marketing
 * "Booking Sources" section.
 *
 * A source is attached to every appointment on creation:
 *   • Online bookings   → resolved from the branch tracking link the customer
 *                          arrived through (see Branch::trackingUrl / the
 *                          /branch/{slug}/{code} route), stored in the session
 *                          and read back in BookingController.
 *   • Reception         → any appointment created from inside the dashboard.
 *   • Other / null      → an online booking with no tracking link (direct).
 *
 * This is NOT the same thing as {@see \App\Models\Customer::SOURCES}, which is a
 * manual, per-customer acquisition field. Booking source is per-appointment and
 * captured automatically.
 */
enum BookingSource: string
{
    case Instagram = 'instagram';
    case Facebook  = 'facebook';
    case Whatsapp  = 'whatsapp';
    case Website   = 'website';
    case Reception = 'reception';
    case Other     = 'other';

    /**
     * Short tracking codes used in the shareable link
     * (/branch/{slug}/IN). Reception & Other have no public link.
     */
    public function code(): ?string
    {
        return match ($this) {
            self::Instagram => 'IN',
            self::Facebook  => 'FB',
            self::Whatsapp  => 'WA',
            self::Website   => 'WEB',
            default         => null,
        };
    }

    /** Resolve a link code (case-insensitive) back to its source, or null. */
    public static function fromCode(string $code): ?self
    {
        return match (strtoupper(trim($code))) {
            'IN', 'INSTAGRAM' => self::Instagram,
            'FB', 'FACEBOOK'  => self::Facebook,
            'WA', 'WHATSAPP'  => self::Whatsapp,
            'WEB', 'WEBSITE'  => self::Website,
            default           => null,
        };
    }

    /** Human label (translated). */
    public function label(): string
    {
        return __(match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook  => 'Facebook',
            self::Whatsapp  => 'WhatsApp',
            self::Website   => 'Website',
            self::Reception => 'Reception',
            self::Other     => 'Other',
        });
    }

    /** Icon key understood by the shared company.partials.source-icon partial. */
    public function iconKey(): string
    {
        return match ($this) {
            self::Instagram => 'instagram',
            self::Facebook  => 'facebook',
            self::Whatsapp  => 'whatsapp',
            self::Website   => 'website',
            self::Reception => 'reception',
            self::Other     => 'other',
        };
    }

    /** Accent colour for chips / bars. */
    public function color(): string
    {
        return match ($this) {
            self::Instagram => '#E4405F',
            self::Facebook  => '#1877F2',
            self::Whatsapp  => '#25D366',
            self::Website   => '#4B5D34',
            self::Reception => '#B08D57',
            self::Other     => '#64748b',
        };
    }

    /**
     * Sources that get a shareable tracking link on the dashboard
     * (everything except Reception, which is internal, and Other).
     *
     * @return self[]
     */
    public static function linkable(): array
    {
        return [self::Instagram, self::Facebook, self::Whatsapp, self::Website];
    }
}
