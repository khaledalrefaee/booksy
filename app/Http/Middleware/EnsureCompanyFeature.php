<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyFeature
{
    /**
     * Usage: ->middleware('feature:payroll')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $company = Auth::guard('company')->user();

        if ($company && $company->hasFeature($feature)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('This feature is not included in your current plan.'),
            ], 403);
        }

        return redirect()
            ->route('company.dashboard')
            ->with('error', __('This feature is not included in your current plan.'));
    }
}
