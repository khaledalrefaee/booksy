<?php

namespace App\Support\Platform;

/**
 * One definition of "a link we are willing to store and print in an href".
 * Used by the validation rule on the way in AND re-checked on the way out, so a
 * tampered database row can never become a javascript:/data: link on a page.
 */
class PlatformUrl
{
    public const MAX_LENGTH = 2048;

    /**
     * @param array{relative?:bool,https?:bool,hosts?:array<int,string>} $opts
     */
    public static function isSafe(?string $url, array $opts = []): bool
    {
        return self::reason($url, $opts) === null;
    }

    /** Null when fine, otherwise a short machine reason: empty|format|scheme|https|host|credentials|length. */
    public static function reason(?string $url, array $opts = []): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return 'empty';
        }
        if (strlen($url) > self::MAX_LENGTH) {
            return 'length';
        }
        // No whitespace, control characters or backslashes anywhere (blocks "java\nscript:" tricks).
        if (preg_match('/[\x00-\x20\x7F\\\\]/', $url)) {
            return 'format';
        }

        // Site-relative path ("/help"), but never protocol-relative ("//evil.test").
        if (str_starts_with($url, '/')) {
            return (! empty($opts['relative']) && ! str_starts_with($url, '//')) ? null : 'format';
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'format';
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return 'scheme';
        }
        if (! empty($opts['https']) && $scheme !== 'https') {
            return 'https';
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'credentials';
        }

        $host = strtolower($parts['host']);
        if (! preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host)) {
            return 'format';
        }

        if (! empty($opts['hosts']) && ! self::hostIn($host, $opts['hosts'])) {
            return 'host';
        }

        return null;
    }

    /** Suffix match: "m.facebook.com" is in ["facebook.com"]. */
    public static function hostIn(string $host, array $allowed): bool
    {
        $host = strtolower($host);
        foreach ($allowed as $h) {
            $h = strtolower($h);
            if ($host === $h || str_ends_with($host, '.'.$h)) {
                return true;
            }
        }

        return false;
    }

    public static function host(?string $url): ?string
    {
        $h = parse_url((string) $url, PHP_URL_HOST);

        return is_string($h) ? strtolower($h) : null;
    }

    /** The URL if safe, otherwise null — the only form views should print. */
    public static function safeOrNull(?string $url, array $opts = ['relative' => true]): ?string
    {
        $url = trim((string) $url);

        return self::isSafe($url, $opts) ? $url : null;
    }

    /** Browser-ready href for a stored URL (relative paths become absolute site URLs). */
    public static function href(?string $url): ?string
    {
        $url = self::safeOrNull($url);

        return $url === null ? null : (str_starts_with($url, '/') ? url($url) : $url);
    }

    /** Does this href leave the GlowRez site (so it should open in a new tab)? */
    public static function isExternal(string $href): bool
    {
        $host = self::host($href);

        return $host !== null && $host !== strtolower((string) parse_url(url('/'), PHP_URL_HOST));
    }
}
