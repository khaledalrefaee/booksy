<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('owner.can:billing.view') */
class EnsureOwnerPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $owner = Auth::guard('owner')->user();

        if (! $owner || ! $owner->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
