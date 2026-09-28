<?php

namespace App\Support;

/**
 * The one rule for turning whatever was typed into an international number.
 * Reception often types local numbers ("0949 863 373"); without the country
 * code they never match a channel's dial code and providers can't route them.
 */
final class PhoneNumber
{
    /**
     * Digits only, with the country code:
     * "0949863373" → "963949863373" (platform default dial code),
     * "00963…" / "+963…" → "963…". Already-international numbers pass through.
     */
    public static function international(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $cc = preg_replace('/\D+/', '', (string) config('booksy.default_dial_code', '+963'));

            return $cc . substr($digits, 1);
        }

        return $digits;
    }
}
