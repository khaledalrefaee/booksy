<?php

namespace App\Services\Leads;

use App\Models\LeadVisit;
use App\Support\LeadCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Works out — and remembers — where a visitor came from, so the lead form never
 * has to ask. Reads ?source / ?campaign / ?ref / ?utm_* (and the Referer header
 * as a fallback), keeps the result in the session AND a 30-day cookie, and logs one
 * LeadVisit per visitor per funnel page for conversion-rate reporting.
 *
 * An explicit tracking link always wins over what was stored (the visitor just
 * clicked a newer campaign); a plain link keeps the first-touch attribution.
 */
class LeadAttribution
{
    public const SESSION_KEY = 'lead_attr';
    public const COOKIE      = 'gr_attr';

    private const FIELDS = [
        'source', 'campaign', 'landing_page', 'referral',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
    ];

    /** Resolve + persist attribution for this request. Call on funnel GET pages. */
    public function capture(Request $request): array
    {
        $explicit = $this->fromQuery($request);

        if ($explicit !== null) {
            $attr = $explicit;
        } else {
            $attr = $this->stored($request) ?? $this->inferred($request);
        }

        session([self::SESSION_KEY => $attr]);
        Cookie::queue(self::COOKIE, json_encode($attr, JSON_UNESCAPED_UNICODE), 60 * 24 * (int) config('leads.attribution_days', 30));

        return $attr;
    }

    /** What we already know for this visitor (used by the POST that saves the lead). */
    public function current(Request $request): array
    {
        return $this->stored($request) ?? $this->inferred($request);
    }

    /** One row per visitor per funnel page. Never throws: tracking must not break a page. */
    public function recordVisit(Request $request, string $page, array $attr): void
    {
        if ($this->isBot($request)) {
            return;
        }

        try {
            $visitor = session('lead_visitor');
            if (! $visitor) {
                $visitor = Str::random(32);
                session(['lead_visitor' => $visitor]);
            }

            LeadVisit::query()->insertOrIgnore([[
                'visitor_id'    => $visitor,
                'page'          => $page,
                'source'        => $attr['source'] ?? 'direct',
                'campaign'      => $attr['campaign'] ?? null,
                'utm_source'    => $attr['utm_source'] ?? null,
                'utm_medium'    => $attr['utm_medium'] ?? null,
                'utm_campaign'  => $attr['utm_campaign'] ?? null,
                'utm_content'   => $attr['utm_content'] ?? null,
                'landing_page'  => $attr['landing_page'] ?? null,
                'referral_host' => $this->refererHost($request),
                'device'        => $this->device($request),
                'locale'        => app()->getLocale(),
                'created_at'    => now(),
            ]]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function isBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return $ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|headless|lighthouse|pingdom|uptime|curl|wget|python|monitor|embedly|skype|discord|linkedin|pinterest|google-read|bytespider/i', $ua) === 1;
    }

    /* ── internals ──────────────────────────────────────────────────────── */

    private function fromQuery(Request $request): ?array
    {
        $q = $request->query();
        $has = collect(['source', 'campaign', 'ref', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content'])
            ->contains(fn ($k) => is_string($q[$k] ?? null) && trim($q[$k]) !== '');

        if (! $has) {
            return null;
        }

        $str = fn (string $k) => is_string($q[$k] ?? null) ? $q[$k] : null;

        $utmSource = LeadCatalog::slug($str('utm_source'), 100);
        $ref       = LeadCatalog::slug($str('ref'));

        $source = LeadCatalog::normalizeSource($str('source'))
            ?? LeadCatalog::normalizeSource($utmSource)
            ?? ($ref ? 'referral' : null)
            ?? $this->sourceFromReferer($request)
            ?? 'direct';

        return [
            'source'       => $source,
            'campaign'     => LeadCatalog::slug($str('campaign'), 80) ?? LeadCatalog::slug($str('utm_campaign'), 80),
            'landing_page' => $this->landingPage($request),
            'referral'     => $ref ?? $this->refererHost($request),
            'utm_source'   => $utmSource,
            'utm_medium'   => LeadCatalog::slug($str('utm_medium'), 100),
            'utm_campaign' => LeadCatalog::slug($str('utm_campaign'), 100),
            'utm_content'  => LeadCatalog::slug($str('utm_content'), 100),
        ];
    }

    private function stored(Request $request): ?array
    {
        $attr = session(self::SESSION_KEY);

        if (! is_array($attr)) {
            $raw = $request->cookie(self::COOKIE);
            $attr = is_string($raw) ? json_decode($raw, true) : null;
        }

        if (! is_array($attr) || empty($attr['source'])) {
            return null;
        }

        // Cookies are user-controlled: re-clean everything we take back.
        return [
            'source'       => LeadCatalog::normalizeSource($attr['source']) ?? 'direct',
            'campaign'     => LeadCatalog::slug($attr['campaign'] ?? null, 80),
            'landing_page' => isset($attr['landing_page']) ? Str::limit(preg_replace('/[^\x20-\x7E]/', '', (string) $attr['landing_page']), 255, '') : null,
            'referral'     => LeadCatalog::slug($attr['referral'] ?? null, 120),
            'utm_source'   => LeadCatalog::slug($attr['utm_source'] ?? null, 100),
            'utm_medium'   => LeadCatalog::slug($attr['utm_medium'] ?? null, 100),
            'utm_campaign' => LeadCatalog::slug($attr['utm_campaign'] ?? null, 100),
            'utm_content'  => LeadCatalog::slug($attr['utm_content'] ?? null, 100),
        ];
    }

    private function inferred(Request $request): array
    {
        return [
            'source'       => $this->sourceFromReferer($request) ?? 'direct',
            'campaign'     => null,
            'landing_page' => $this->landingPage($request),
            'referral'     => $this->refererHost($request),
            'utm_source'   => null,
            'utm_medium'   => null,
            'utm_campaign' => null,
            'utm_content'  => null,
        ];
    }

    private function landingPage(Request $request): string
    {
        return '/'.ltrim($request->path(), '/');
    }

    /** External referer host (null for same-site navigation or when absent). */
    private function refererHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! is_string($host) || $host === '' || strcasecmp($host, $request->getHost()) === 0) {
            return null;
        }

        return Str::limit(strtolower($host), 120, '');
    }

    private function sourceFromReferer(Request $request): ?string
    {
        $host = $this->refererHost($request);
        if ($host === null) {
            return null;
        }

        return match (true) {
            str_contains($host, 'instagram.com') => 'instagram',
            str_contains($host, 'whatsapp.com'), $host === 'wa.me' => 'whatsapp',
            str_contains($host, 'facebook.com'), str_contains($host, 'fb.com'), str_contains($host, 'fb.me') => 'facebook',
            default => 'website',
        };
    }

    private function device(Request $request): string
    {
        $ua = strtolower((string) $request->userAgent());

        return match (true) {
            str_contains($ua, 'ipad'), str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobi'), str_contains($ua, 'android'), str_contains($ua, 'iphone') => 'mobile',
            default => 'desktop',
        };
    }
}
