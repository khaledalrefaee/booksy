<?php

namespace App\Services\Platform;

use App\Models\PlatformLink;
use App\Models\PlatformSetting;
use App\Support\Platform\PlatformPhone;
use App\Support\Platform\PlatformUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The one door to GlowRez platform settings.
 *
 *  - Reads are served from a single cached snapshot (one key, forgotten on every
 *    save), memoised per request, so a public page costs zero queries once warm.
 *  - If the tables are missing/unreachable (deploy not migrated yet) every reader
 *    falls back to schema defaults: the public site must never go down because a
 *    settings lookup failed.
 *  - "public*" methods are the ONLY data public pages and the public API may use:
 *    they apply visibility switches and URL safety, and never return the
 *    internal (team-only) support fields.
 */
class PlatformSettings
{
    private ?array $raw = null;
    private ?array $linkRows = null;

    /* ───────────────────────── schema ───────────────────────── */

    public static function schema(): array
    {
        return config('platform-settings.fields', []);
    }

    public static function def(string $key): ?array
    {
        return self::schema()[$key] ?? null;
    }

    /** Keys of one group, e.g. "support" → ['support.email', …]. */
    public static function keysOf(string $group): array
    {
        return array_values(array_filter(array_keys(self::schema()), fn ($k) => str_starts_with($k, $group.'.')));
    }

    /* ───────────────────────── reading ───────────────────────── */

    /** Raw stored strings, keyed "group.key". */
    private function raw(): array
    {
        if ($this->raw !== null) {
            return $this->raw;
        }

        try {
            return $this->raw = Cache::rememberForever(config('platform-settings.cache_key'), function () {
                return PlatformSetting::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable $e) {
            // Not migrated yet / DB hiccup: serve defaults, never break a page.
            Log::warning('Platform settings unavailable, using defaults: '.$e->getMessage());

            return $this->raw = [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $def = self::def($key);
        if ($def === null) {
            return $default;
        }

        $raw = $this->raw()[$key] ?? null;
        if ($raw === null || $raw === '') {
            return $def['default'] ?? $default;
        }

        return self::cast($def, (string) $raw);
    }

    public function string(string $key): ?string
    {
        $v = $this->get($key);

        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key, false);
    }

    /** "general.name" → general.name_ar / general.name_en for the current locale, falling back to the other language. */
    public function localized(string $base, ?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $order  = $locale === 'ar' ? ['ar', 'en'] : ['en', 'ar'];

        foreach ($order as $l) {
            $v = $this->string($base.'_'.$l);
            if ($v !== null) {
                return $v;
            }
        }

        return null;
    }

    /** Every known key with its current (or default) value — what the settings forms pre-fill from. */
    public function all(): array
    {
        $out = [];
        foreach (array_keys(self::schema()) as $key) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }

    private static function cast(array $def, string $raw): mixed
    {
        return match ($def['type']) {
            'bool' => $raw === '1',
            'int'  => (int) $raw,
            'days' => array_values(array_filter(explode(',', $raw))),
            default => $raw,
        };
    }

    /* ───────────────────────── writing ───────────────────────── */

    /** Make a submitted value safe and canonical for its type. Idempotent. */
    public static function sanitize(string $key, mixed $value): mixed
    {
        $def = self::def($key);
        if ($def === null) {
            return null;
        }

        if ($def['type'] === 'bool') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        if ($def['type'] === 'days') {
            $valid = config('platform-settings.weekdays', []);

            return array_values(array_intersect($valid, array_map('strval', (array) $value)));
        }

        if ($value === null) {
            return null;
        }
        $v = is_scalar($value) ? (string) $value : '';

        $v = match ($def['type']) {
            'string' => trim(preg_replace('/\s+/u', ' ', self::stripControl(strip_tags($v))) ?? ''),
            'text'   => self::cleanMultiline($v),
            'phone'  => PlatformPhone::normalize($v) ?? trim($v),
            default  => trim(self::stripControl($v)),
        };

        return $v === '' ? null : $v;
    }

    private static function stripControl(string $v): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    }

    private static function cleanMultiline(string $v): string
    {
        $v = self::stripControl(strip_tags(str_replace(["\r\n", "\r"], "\n", $v)));
        $v = preg_replace("/[ \t]+/u", ' ', $v) ?? '';
        $v = preg_replace("/\n{3,}/", "\n\n", $v) ?? '';

        return trim($v);
    }

    /**
     * Persist already-validated values (only known keys are written).
     *
     * @param  array<string,mixed> $values "group.key" => value; null/'' removes the row
     * @return array<string,array{0:mixed,1:mixed}> key => [old, new] for the keys that actually changed
     */
    public function save(array $values, ?int $ownerId = null): array
    {
        $changes = [];

        DB::transaction(function () use ($values, $ownerId, &$changes) {
            foreach ($values as $key => $value) {
                if (self::def($key) === null) {
                    continue;
                }

                $new = self::sanitize($key, $value);
                $new = is_array($new) ? ($new === [] ? null : implode(',', $new)) : $new;
                $old = $this->raw()[$key] ?? null;
                $old = ($old === '') ? null : $old;

                if ($old === $new) {
                    continue;
                }
                $changes[$key] = [$old, $new];

                if ($new === null) {
                    PlatformSetting::where('key', $key)->delete();
                } else {
                    PlatformSetting::updateOrCreate(
                        ['key' => $key],
                        ['group' => strstr($key, '.', true), 'value' => $new, 'updated_by' => $ownerId],
                    );
                }
            }
        });

        if ($changes) {
            $this->forget();
        }

        return $changes;
    }

    public function forget(): void
    {
        Cache::forget(config('platform-settings.cache_key'));
        Cache::forget(config('platform-settings.cache_key').'.links');
        $this->raw = null;
        $this->linkRows = null;
    }

    /* ───────────────────────── links ───────────────────────── */

    /** @return Collection<int,PlatformLink> every link, in display order (admin view). */
    public function links(): Collection
    {
        return collect($this->linkData())->map(fn ($r) => (new PlatformLink)->forceFill($r)->syncOriginal());
    }

    private function linkData(): array
    {
        if ($this->linkRows !== null) {
            return $this->linkRows;
        }

        try {
            return $this->linkRows = Cache::rememberForever(config('platform-settings.cache_key').'.links', function () {
                return PlatformLink::query()->orderBy('sort_order')->orderBy('id')->get()->map->getAttributes()->all();
            });
        } catch (\Throwable $e) {
            Log::warning('Platform links unavailable: '.$e->getMessage());

            return $this->linkRows = [];
        }
    }

    /**
     * Make the table match $rows (the form's complete list, already validated),
     * in that order: rows with an id are updated, rows without are created, ids
     * not submitted are deleted.
     *
     * @param  array<int,array<string,mixed>> $rows
     * @return array{created:int,updated:int,deleted:int}
     */
    public function syncLinks(array $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        DB::transaction(function () use ($rows, &$stats) {
            $keep = [];
            foreach (array_values($rows) as $i => $row) {
                $payload = [
                    'kind'        => $row['kind'],
                    'label'       => $row['label'],
                    'url'         => $row['url'],
                    'icon'        => $row['kind'] === 'custom' ? ($row['icon'] ?? 'link') : null,
                    'is_active'   => (bool) ($row['is_active'] ?? false),
                    'show_public' => (bool) ($row['show_public'] ?? false),
                    'sort_order'  => $i,
                ];

                $model = ! empty($row['id']) ? PlatformLink::find($row['id']) : null;
                if ($model) {
                    $model->fill($payload);
                    if ($model->isDirty()) {
                        $model->save();
                        $stats['updated']++;
                    }
                } else {
                    $model = PlatformLink::create($payload);
                    $stats['created']++;
                }
                $keep[] = $model->id;
            }

            $stats['deleted'] = PlatformLink::query()->whereNotIn('id', $keep)->delete();
        });

        $this->forget();

        return $stats;
    }

    /* ───────────────────────── logo ───────────────────────── */

    public function logoUrl(): ?string
    {
        $path = $this->string('general.logo');

        return $path && Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null;
    }

    /* ───────────────────── public (safe) views ───────────────────── */

    /** Social / custom links the public site may show: active, enabled for the site, safe URL, in order. */
    public function publicLinks(): array
    {
        if (! $this->bool('visibility.show_social')) {
            return [];
        }

        $out = [];
        foreach ($this->links() as $l) {
            if (! $l->isPubliclyVisible()) {
                continue;
            }
            $out[] = [
                'kind'  => $l->kind,
                'label' => $l->label,
                'url'   => (string) $l->url,
                'icon'  => $l->iconKey(),
            ];
        }

        return $out;
    }

    /** GlowRez's official email/phone for the footer, honouring the Visibility switches. */
    public function officialContact(): array
    {
        $email = $this->bool('visibility.show_official_email') ? $this->string('general.email') : null;
        $phone = $this->bool('visibility.show_official_phone') ? PlatformPhone::normalize($this->string('general.phone')) : null;

        return [
            'email'      => $email,
            'email_href' => $email ? 'mailto:'.$email : null,
            'phone'      => $phone,
            'phone_href' => $phone ? 'tel:'.$phone : null,
        ];
    }

    /**
     * Customer-facing support channels that are switched on (master switch AND the
     * channel's own switch) and actually have a value. Internal fields are never here.
     *
     * @return array<string,array{key:string,label:string,value:string,href:string,icon:string,external:bool}>
     */
    public function supportChannels(): array
    {
        if (! $this->bool('visibility.show_support_links')) {
            return [];
        }

        $out = [];
        $add = function (string $key, string $label, ?string $value, ?string $href, string $icon) use (&$out) {
            if ($value !== null && $href !== null) {
                $out[$key] = [
                    'key' => $key, 'label' => $label, 'value' => $value, 'href' => $href, 'icon' => $icon,
                    'external' => str_starts_with($href, 'http') && PlatformUrl::isExternal($href),
                ];
            }
        };

        if ($this->bool('support.email_visible') && ($v = $this->string('support.email'))) {
            $add('email', __('Email'), $v, 'mailto:'.$v, 'mail');
        }
        if ($this->bool('support.phone_visible') && ($v = PlatformPhone::normalize($this->string('support.phone')))) {
            $add('phone', __('Phone'), $v, 'tel:'.$v, 'phone');
        }
        if ($this->bool('support.whatsapp_visible')) {
            $num  = PlatformPhone::normalize($this->string('support.whatsapp'));
            $link = PlatformUrl::safeOrNull($this->string('support.whatsapp_link'), ['https' => true]) ?: PlatformPhone::whatsappUrl($num);
            if ($link) {
                $add('whatsapp', 'WhatsApp', $num ?: __('Chat on WhatsApp'), $link, 'whatsapp');
            }
        }
        foreach ([
            'help_center' => [__('Help center'), 'help-circle'],
            'faq'         => [__('FAQ'), 'book-open'],
            'contact'     => [__('Contact the GlowRez team'), 'message-circle'],
        ] as $key => [$label, $icon]) {
            if ($this->bool("support.{$key}_visible")) {
                $href = PlatformUrl::href($this->string("support.{$key}_url"));
                // Show the host for outside links, the path for pages on this site.
                $shown = null;
                if ($href) {
                    $p = parse_url($href);
                    $shown = PlatformUrl::isExternal($href)
                        ? ($p['host'] ?? $href)
                        : (($p['path'] ?? '/').(isset($p['fragment']) ? '#'.$p['fragment'] : ''));
                }
                $add($key, $label, $shown, $href, $icon);
            }
        }

        return $out;
    }

    /** "09:00 – 17:00 · Sat Sun Mon" for the public contact page, or null when hours are not set. */
    public function supportHoursLabel(): ?string
    {
        $from = $this->string('support.hours_from');
        $to   = $this->string('support.hours_to');
        if (! $from || ! $to) {
            return null;
        }

        $days = $this->get('support.days', []);
        $names = $days ? implode(' · ', array_map(fn ($d) => __(ucfirst($d)), $days)) : null;

        return $from.' – '.$to.($names ? ' · '.$names : '');
    }

    /** Footer content; every text is null when unset so the layout keeps its built-in wording. */
    public function footer(): array
    {
        $year = (string) now()->year;
        $copy = $this->localized('visibility.copyright');

        return [
            'tagline'     => $this->localized('visibility.footer_tagline'),
            'legal'       => $this->localized('visibility.footer_legal'),
            'copyright'   => $copy ? str_ireplace('{year}', $year, $copy) : null,
            'privacy_url' => PlatformUrl::href($this->string('visibility.privacy_url')),
            'terms_url'   => PlatformUrl::href($this->string('visibility.terms_url')),
            'contact_on'  => $this->bool('visibility.contact_link_enabled'),
            'contact_cta' => $this->localized('visibility.contact_cta'),
            'version'     => $this->bool('system.show_version') ? $this->string('release.web_version') : null,
        ];
    }

    /* ───────────────────────── maintenance ───────────────────────── */

    /**
     * @return array{mode:string,active:bool,scope:string,message:?string,status_url:?string}
     */
    public function maintenance(): array
    {
        $mode = (string) $this->get('system.mode', 'normal');

        return [
            'mode'       => $mode,
            'active'     => $mode !== 'normal',
            'scope'      => (string) $this->get('system.scope', 'public'),
            'message'    => $this->localized('system.message'),
            'status_url' => PlatformUrl::href($this->string('system.status_page_url')),
        ];
    }

    /* ───────────────────────── releases (public API) ───────────────────────── */

    /**
     * Release record for one product, as the apps may see it. Store links are only
     * offered while the product is "available" — an in-development app never
     * exposes a download button.
     */
    public function releaseFor(string $product, ?string $clientVersion = null): array
    {
        $locale = app()->getLocale();
        $g = fn (string $k) => $this->get("release.{$product}_{$k}");

        $out = [
            'version'      => $g('version'),
            'min_version'  => $g('min_version'),
            'updated_on'   => $g('updated_on'),
            'release_notes'=> $this->localized("release.{$product}_notes", $locale),
        ];

        if ($product !== 'web') {
            $available = $g('status') === 'available';
            $out['status'] = $g('status');
            $out['build']  = $g('build');
            $links = $product === 'android'
                ? ['google_play_url' => $g('play_url'), 'apk_url' => $g('apk_url')]
                : ['app_store_url' => $g('store_url')];
            foreach ($links as $k => $v) {
                $out[$k] = $available ? PlatformUrl::safeOrNull($v, ['https' => true]) : null;
            }
        }

        if ($clientVersion !== null && preg_match('/^\d+(\.\d+){0,3}/', $clientVersion)) {
            $latest = $out['version'];
            $min    = $out['min_version'];
            $out['update_required']  = $min ? version_compare($clientVersion, $min, '<') : false;
            $out['update_available'] = $this->bool('system.update_notice') && $latest
                ? version_compare($clientVersion, $latest, '<') : false;
        }

        return $out;
    }

    /** Detected facts about the running deployment (NOT editable, NOT the recorded release). */
    public static function runtime(): array
    {
        return [
            'release'  => config('app.release') ?: null,
            'laravel'  => app()->version(),
            'php'      => PHP_VERSION,
            'env'      => app()->environment(),
            'debug'    => (bool) config('app.debug'),
        ];
    }
}
