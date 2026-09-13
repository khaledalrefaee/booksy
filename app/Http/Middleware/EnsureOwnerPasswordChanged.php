<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-login forced password change for owner-guard accounts. Employees are
 * created with a manager-set temporary password; until they set their own
 * they can only reach the change-password screen (and logout).
 */
class EnsureOwnerPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $owner = Auth::guard('owner')->user();

        if ($owner && $owner->must_change_password) {
            return redirect()->route('owner.password.change');
        }

        return $next($request);
    }
}
