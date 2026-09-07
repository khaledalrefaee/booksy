<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('staff')->check()) {
            return redirect()->route('company.login');
        }

        /** @var \App\Models\Employee $employee */
        $employee = Auth::guard('staff')->user();

        // A staff member deactivated mid-session is signed straight out — no page
        // of the portal stays reachable. Only the staff guard is logged out so a
        // company/owner signed in in the same browser keeps their session.
        if (! $employee->is_active) {
            Auth::guard('staff')->logout();

            return redirect()->route('company.login')
                ->withErrors(['email' => __('Your account is inactive. Contact your manager.')]);
        }

        return $next($request);
    }
}
