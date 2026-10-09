<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Read-only access to the option lists in config/leads.php (statuses, business
 * types, interests, sources, cities) with locale-aware labels. One place for
 * "what does this key mean" so the form, filters, table, email and CSV agree.
 */
class LeadCatalog
{
    public static function group(string $group): array
    {
        return (array) config("leads.$group", []);
    }

    public static function keys(string $group): array
    {
        return array_keys(self::group($group));
    }

    /** key ⇒ label for the current (or given) locale — handy for <select>s. */
    public static function options(string $group, ?string $locale = null): array
    {
        return collect(self::group($group))
            ->map(fn ($row, $key) => self::pick($row, $locale) ?? $key)
            ->all();
    }

    /**
     * Label for a key. Unknown keys (e.g. a free-text city) are returned as typed,
     * so nothing a visitor wrote is ever hidden.
     */
    public static function label(string $group, ?string $key, ?string $locale = null): string
    {
        if ($key === null || $key === '') {
            return '';
        }

        $row = config("leads.$group.$key");

        return $row ? (self::pick($row, $locale) ?? $key) : $key;
    }

    public static function tone(string $status): string
    {
        return (string) config("leads.statuses.$status.tone", 'muted');
    }

    public static function interestLabels(?array $keys, ?string $locale = null): array
    {
        return collect($keys ?? [])->map(fn ($k) => self::label('interests', $k, $locale))->filter()->values()->all();
    }

    /**
     * Normalise whatever arrived in ?source= to a known key ("IG" ⇒ instagram).
     * Unknown-but-clean values become "other" so a typo never forks a report.
     */
    public static function normalizeSource(?string $raw): ?string
    {
        $raw = self::slug($raw);
        if ($raw === null) {
            return null;
        }

        $aliases = (array) config('leads.source_aliases', []);

        return $aliases[$raw] ?? (array_key_exists($raw, config('leads.sources', [])) ? $raw : 'other');
    }

    /** Lower-case [a-z0-9_-.] token, max 60 chars — safe for storage, URLs and SQL LIKE. */
    public static function slug(?string $value, int $max = 60): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = Str::of($value)->lower()->replaceMatches('/[^a-z0-9_\-\.]/', '')->limit($max, '')->toString();

        return $clean === '' ? null : $clean;
    }

    private static function pick(array $row, ?string $locale): ?string
    {
        $locale ??= app()->getLocale();

        return $row[$locale] ?? $row['en'] ?? $row['ar'] ?? null;
    }
}
