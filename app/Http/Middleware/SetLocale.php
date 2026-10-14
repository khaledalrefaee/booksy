<?php

namespace App\Http\Middleware;

use App\Services\Platform\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const AVAILABLE = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        // A visitor's own choice wins; otherwise the default set in Owner → Settings → General,
        // then the application default.
        $locale = session('locale') ?: app(PlatformSettings::class)->string('general.default_locale') ?: config('app.locale');
        $locale = in_array($locale, self::AVAILABLE, true) ? $locale : config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
