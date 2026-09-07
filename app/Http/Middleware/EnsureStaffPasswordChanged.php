<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPasswordChanged
{
    /**
     * Force a first-login password change: employees are created with a
     * manager-set password, so until they set their own they can only reach
     * the change-password screen (and logout).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = Auth::guard('staff')->user();

        if ($employee && $employee->must_change_password) {
            return redirect()->route('staff.password.change');
        }

        return $next($request);
    }
}
