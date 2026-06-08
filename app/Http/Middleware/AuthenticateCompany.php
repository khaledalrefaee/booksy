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

        return $next($request);
    }
}
