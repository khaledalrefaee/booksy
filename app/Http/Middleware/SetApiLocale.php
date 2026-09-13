<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale resolution for the STATELESS mobile API.
 *
 * The web SetLocale middleware reads the language from the session — which a
 * token-based mobile client never has. So the app must state its language on
 * every request; this middleware reads it, in order of precedence:
 *
 *   1. an explicit `lang` (or `locale`) query/body parameter — e.g. ?lang=ar
 *   2. the `Accept-Language` header — e.g. "ar", "en-US,en;q=0.9"
 *   3. the app default (config app.locale)
 *
 * Only the languages the app actually ships (ar, en) are honoured; anything
 * else falls back to the default. Setting the locale here is what makes both
 * the OTP message body AND every JSON `__()` message come back in the caller's
 * language.
 */
class SetApiLocale
{
    private const AVAILABLE = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->input('lang')
            ?? $request->input('locale')
            ?? $this->fromHeader($request->header('Accept-Language'));

        $locale = in_array($requested, self::AVAILABLE, true)
            ? $requested
            : config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }

    /** Take the primary language subtag from an Accept-Language header. */
    private function fromHeader(?string $header): ?string
    {
        if (! $header) {
            return null;
        }

        // "ar-SY,ar;q=0.9,en;q=0.8" → "ar"
        $first = trim(explode(',', $header)[0]);
        $tag   = strtolower(explode('-', $first)[0]);

        return $tag !== '' ? $tag : null;
    }
}
