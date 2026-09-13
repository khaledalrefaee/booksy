<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the mobile customer API.
 *
 * Customers authenticate with a bearer token issued by the verify-code step
 * (see Api\Customers\AuthController). The token travels in the standard
 *   Authorization: Bearer <token>
 * header; we store only its SHA-256 hash on the customer, so the lookup hashes
 * the incoming value and matches that. Always returns JSON — there is no page
 * to redirect a mobile client to.
 */
class AuthenticateCustomerApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $customer = $token
            ? Customer::where('api_token', hash('sha256', $token))->first()
            : null;

        if (! $customer) {
            return response()->json([
                'status'  => false,
                'message' => __('Please sign in to continue.'),
                'data'    => null,
            ], 401);
        }

        if ($customer->is_banned) {
            return response()->json([
                'status'  => false,
                'message' => __('This account has been suspended.'),
                'data'    => null,
            ], 403);
        }

        // Share the resolved customer with the controllers for this request.
        $request->attributes->set('customer', $customer);
        app()->instance('current_customer', $customer);

        return $next($request);
    }
}
