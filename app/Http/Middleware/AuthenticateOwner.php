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

        return $next($request);
    }
}
