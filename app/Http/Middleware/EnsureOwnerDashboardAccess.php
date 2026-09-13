<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the full /owner admin panel. Staff without 'owner-dashboard.view'
 * (e.g. field reps) are bounced to their own home instead of the admin UI,
 * so they can never reach owner pages by typing a URL.
 */
class EnsureOwnerDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $owner = Auth::guard('owner')->user();

        if (! $owner) {
            return redirect()->route('owner.login');
        }

        if (! $owner->canAccessOwnerDashboard()) {
            $home = $owner->homeRoute();

            // Avoid a redirect loop if their home is itself under this gate.
            if ($home === 'owner.dashboard') {
                abort(403);
            }

            return redirect()->route($home);
        }

        return $next($request);
    }
}
