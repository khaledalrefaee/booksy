<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('owner')->check()) {
            return redirect()->route('owner.login');
        }

        // Disabled accounts are logged straight out (covers a mid-session
        // deactivation without waiting for the token to expire).
        $owner = Auth::guard('owner')->user();
        if ($owner->is_active === false) {
            Auth::guard('owner')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('owner.login')
                ->withErrors(['email' => __('Your account has been disabled.')]);
        }

        // "Last seen" — throttled to at most one write every 5 minutes.
        if (! $owner->last_activity_at || $owner->last_activity_at->lt(now()->subMinutes(5))) {
            $owner->forceFill(['last_activity_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
