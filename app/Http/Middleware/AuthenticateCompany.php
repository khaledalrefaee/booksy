<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('company')->check()) {
            return redirect()->route('company.login');
        }

        /** @var \App\Models\Company $company */
        $company = Auth::guard('company')->user();

        // A live session must not outlive a suspension: the next request the
        // company makes logs it straight out and bounces it to the login screen
        // with the reason — no page of the panel stays reachable.
        //
        // Only the company guard is logged out — NOT session()->invalidate(),
        // which would wipe the whole shared session and sign out any owner
        // authenticated in the same browser too.
        if ($company->isSuspended()) {
            $notice = $company->suspendedNotice();

            Auth::guard('company')->logout();

            return redirect()->route('company.login')
                ->withErrors(['email' => $notice]);
        }

        return $next($request);
    }
}
