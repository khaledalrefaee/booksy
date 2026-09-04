<?php

namespace App\Services\Sms;

/**
 * Works out how a body will be billed by the carrier: which encoding it needs
 * (GSM-7 vs UCS-2/Unicode) and therefore how many 160/70-char segments — i.e.
 * how many credits — it costs. Arabic and emoji force Unicode, so a short
 * Arabic reminder can still be two segments. Reused by the sender to charge the
 * wallet and by the template editor's live character counter.
 */
class SmsSegment
{
    /**
     * Basic GSM 03.38 alphabet. Built from single-quoted literals (so the `$`
     * characters are not treated as interpolation) with the two control chars
     * spliced in as constant escapes.
     */
    private const GSM_BASIC =
        '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?'
        . '¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';

    /** GSM chars that occupy two septets (the extension table). */
    private const GSM_EXTENDED = ['^', '{', '}', '\\', '[', ']', '~', '|', '€'];

    /**
     * @return array{encoding: string, length: int, segments: int, per_segment: int}
     */
    public static function analyze(string $text): array
    {
        $unicode = ! self::isGsm7($text);

        if ($unicode) {
            $length     = self::ucs2Length($text);
            $single     = 70;
            $multi      = 67;
        } else {
            $length     = self::gsm7Length($text);
            $single     = 160;
            $multi      = 153;
        }

        if ($length === 0) {
            $segments = 0;
        } elseif ($length <= $single) {
            $segments = 1;
        } else {
            $segments = (int) ceil($length / $multi);
        }

        return [
            'encoding'    => $unicode ? 'unicode' : 'gsm7',
            'length'      => $length,
            'segments'    => $segments,
            'per_segment' => $length <= $single ? $single : $multi,
        ];
    }

    /** Number of credits (= segments) a body costs, minimum 1 for non-empty. */
    public static function credits(string $text): int
    {
        $segments = self::analyze($text)['segments'];

        return $segments === 0 ? 0 : max(1, $segments);
    }

    private static function isGsm7(string $text): bool
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($chars as $char) {
            if (! str_contains(self::GSM_BASIC, $char) && ! in_array($char, self::GSM_EXTENDED, true)) {
                return false;
            }
        }

        return true;
    }

    private static function gsm7Length(string $text): int
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $len   = 0;

        foreach ($chars as $char) {
            $len += in_array($char, self::GSM_EXTENDED, true) ? 2 : 1;
        }

        return $len;
    }

    /** UCS-2 counts astral characters (most emoji) as two code units. */
    private static function ucs2Length(string $text): int
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $len   = 0;

        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');
            $len += ($code !== false && $code > 0xFFFF) ? 2 : 1;
        }

        return $len;
    }
}
