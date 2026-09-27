<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the mobile company API — the company twin of AuthenticateCustomerApi.
 *
 * Companies authenticate with a bearer token issued by verify / login (see
 * Api\Companies\AuthController). The token travels in the standard
 *   Authorization: Bearer <token>
 * header; only its SHA-256 hash is stored on the company, so the lookup hashes
 * the incoming value and matches that. Always returns JSON.
 */
class AuthenticateCompanyApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $company = $token
            ? Company::where('api_token', hash('sha256', $token))->first()
            : null;

        if (! $company) {
            return response()->json([
                'status'  => false,
                'message' => __('Please sign in to continue.'),
                'data'    => null,
            ], 401);
        }

        if ($company->isSuspended()) {
            return response()->json([
                'status'  => false,
                'message' => $company->suspendedNotice(),
                'data'    => null,
            ], 403);
        }

        // Share the resolved company with the controllers for this request.
        $request->attributes->set('company', $company);
        app()->instance('current_company', $company);

        return $next($request);
    }
}
