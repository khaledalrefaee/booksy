<?php

namespace App\Enums;

/**
 * How well the salon knows a customer — the badge reception sees before
 * deciding how to treat them.
 *
 * Mostly DERIVED, not entered. The customers.tag column exists but is empty on
 * almost every row, so a UI that only read the tag would show nothing useful.
 * Instead the tier falls back to visit history, which every customer has for
 * free: {@see \App\Models\Customer::tier()}.
 *
 * The manual tag still wins when set — a salon owner marking someone VIP must
 * outrank whatever the visit count says.
 */
enum CustomerTier: string
{
    case Vip     = 'vip';
    case Loyal   = 'loyal';
    case Regular = 'regular';
    case New     = 'new';

    /** Visits at which a customer stops being "new" and starts being "loyal". */
    public const LOYAL_AT = 5;

    public function label(): string
    {
        return __(match ($this) {
            self::Vip     => 'VIP',
            self::Loyal   => 'Loyal',
            self::Regular => 'Regular',
            self::New     => 'New',
        });
    }

    public function color(): string
    {
        return match ($this) {
            self::Vip     => '#C9A227',
            self::Loyal   => '#a78bfa',
            self::Regular => '#0C6E74',
            self::New     => '#22c55e',
        };
    }

    /**
     * Inline SVG path data, 24×24 stroke icons.
     *
     * Returned as markup rather than an icon-font name because the drawer
     * builds its rows as HTML strings and the page has no feather runtime to
     * hydrate <i data-feather> after the fact. Emoji are deliberately not used:
     * they render differently per platform and cannot be themed.
     */
    public function iconPath(): string
    {
        return match ($this) {
            // star
            self::Vip => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            // gem
            self::Loyal => '<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20"/><path d="M12 3 8 9l4 12 4-12-4-6"/>',
            // user
            self::Regular => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            // sparkle / plus-circle
            self::New => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
        };
    }

    /** Complete <svg> element at the given pixel size. */
    public function svg(int $size = 14): string
    {
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none"'
            . ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $this->iconPath() . '</svg>';
    }

    /**
     * Derive a tier. An explicit tag always wins; otherwise visit count decides.
     *
     * @param  string|null  $tag     customers.tag, when the salon set one
     * @param  int          $visits  completed appointments with this company
     */
    public static function resolve(?string $tag, int $visits): self
    {
        if ($tag !== null && ($explicit = self::tryFrom($tag)) !== null) {
            return $explicit;
        }

        return match (true) {
            $visits === 0            => self::New,
            $visits >= self::LOYAL_AT => self::Loyal,
            default                  => self::Regular,
        };
    }

    /** @return array<string, array{value:string,label:string,color:string,icon:string}> */
    public static function forFrontend(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[$case->value] = [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
                'icon'  => $case->iconPath(),
            ];
        }

        return $out;
    }
}
