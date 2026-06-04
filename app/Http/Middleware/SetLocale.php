<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const AVAILABLE = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));
        $locale = in_array($locale, self::AVAILABLE, true) ? $locale : config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
