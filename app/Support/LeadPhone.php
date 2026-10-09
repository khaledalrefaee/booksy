<?php

namespace App\Support;

/**
 * Phone numbers arrive as "0944 123 456", "+963 944-123-456", "٠٩٤٤١٢٣٤٥٦",
 * "00963944123456"… Reduce them all to ONE canonical digit string so duplicate
 * detection and wa.me links work no matter how the visitor typed it.
 */
class LeadPhone
{
    private const ARABIC_DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /** Canonical international digits (no "+"), or null when it can't be a phone number. */
    public static function normalize(?string $raw, ?string $countryCode = null): ?string
    {
        if ($raw === null) {
            return null;
        }

        $cc = $countryCode ?? (string) config('leads.default_country_code', '963');

        $s = strtr(trim($raw), self::ARABIC_DIGITS);
        $international = str_starts_with($s, '+') || str_starts_with(preg_replace('/\D/', '', $s) ?? '', '00');
        $digits = preg_replace('/\D/', '', $s) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (! $international) {
            if (str_starts_with($digits, '0')) {
                $digits = $cc.ltrim($digits, '0');          // 0944… → 963944…
            } elseif (! str_starts_with($digits, $cc)) {
                $digits = $cc.$digits;                       // 944… → 963944…
            }
        }

        // E.164 allows 15 digits; anything shorter than country code + 6 is noise.
        return strlen($digits) >= strlen($cc) + 6 && strlen($digits) <= 15 ? $digits : null;
    }

    /** Latin digits only (Arabic-Indic converted, everything else dropped) — for partial-number searching. */
    public static function digits(?string $raw): string
    {
        return preg_replace('/\D/', '', strtr((string) $raw, self::ARABIC_DIGITS)) ?? '';
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** https://wa.me/… link, or null if the number is missing/invalid (so the button is hidden). */
    public static function whatsappUrl(?string $raw, ?string $text = null): ?string
    {
        $n = self::normalize($raw);
        if ($n === null) {
            return null;
        }

        return 'https://wa.me/'.$n.($text ? '?text='.rawurlencode($text) : '');
    }

    /** "+963 944 123 456"-style display for the owner UI (LTR, grouped). */
    public static function pretty(?string $raw): string
    {
        $n = self::normalize($raw);
        if ($n === null) {
            return (string) $raw;
        }

        return '+'.$n;
    }
}
