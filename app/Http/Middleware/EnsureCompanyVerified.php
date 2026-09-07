<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an unverified company out of the panel: until the OTP code is
 * confirmed, every protected page bounces back to the verification screen.
 * The verification routes themselves (and logout/theme) stay reachable so the
 * owner can actually complete — or abandon — the step.
 */
class EnsureCompanyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Company|null $company */
        $company = Auth::guard('company')->user();

        $exempt = $request->routeIs('company.verify.*')
            || $request->routeIs('company.logout')
            || $request->routeIs('company.theme');

        if ($company && ! $company->isVerified() && ! $exempt) {
            return redirect()->route('company.verify.notice');
        }

        return $next($request);
    }
}
