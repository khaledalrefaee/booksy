<?php

namespace App\Support\Platform;

/** International phone numbers: stored as "+<digits>" (E.164), shown/linked from that. */
class PlatformPhone
{
    /** Arabic-Indic and Persian digits → Latin, so "٠٠٩٦٣…" is accepted. */
    private const DIGITS = [
        "\u{0660}" => '0', "\u{0661}" => '1', "\u{0662}" => '2', "\u{0663}" => '3', "\u{0664}" => '4',
        "\u{0665}" => '5', "\u{0666}" => '6', "\u{0667}" => '7', "\u{0668}" => '8', "\u{0669}" => '9',
        "\u{06F0}" => '0', "\u{06F1}" => '1', "\u{06F2}" => '2', "\u{06F3}" => '3', "\u{06F4}" => '4',
        "\u{06F5}" => '5', "\u{06F6}" => '6', "\u{06F7}" => '7', "\u{06F8}" => '8', "\u{06F9}" => '9',
    ];

    /** "+963 944-123 456" / "00963944123456" → "+963944123456"; null when it is not a valid international number. */
    public static function normalize(?string $raw): ?string
    {
        $s = trim(strtr((string) $raw, self::DIGITS));
        if ($s === '' || ! preg_match('/^\+?[0-9\s\-().]+$/', $s)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $s) ?? '';
        if (! str_starts_with($s, '+')) {
            if (! str_starts_with($digits, '00')) {
                return null; // a number without a country code is ambiguous — refuse it
            }
            $digits = substr($digits, 2);
        }

        return preg_match('/^[1-9][0-9]{6,14}$/', $digits) ? '+'.$digits : null;
    }

    public static function digits(?string $e164): string
    {
        return preg_replace('/\D/', '', (string) $e164) ?? '';
    }

    public static function telHref(?string $e164): ?string
    {
        $n = self::normalize($e164);

        return $n ? 'tel:'.$n : null;
    }

    public static function whatsappUrl(?string $e164, ?string $text = null): ?string
    {
        $n = self::normalize($e164);

        return $n ? 'https://wa.me/'.self::digits($n).($text ? '?text='.rawurlencode($text) : '') : null;
    }
}
