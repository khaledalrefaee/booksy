<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the customer self-service area (/account/*).
 *
 * Customers authenticate through the phone+OTP modal, which stores
 * `customer_id` in the session (see CustomerAuthController). There is no
 * Laravel guard for them, so this middleware resolves that session value.
 *
 * - JSON/AJAX requests get a 401 so the frontend can pop the login modal.
 * - Normal page requests are sent home with ?login=1, which the front layout
 *   reads to auto-open the login modal and return the user afterwards.
 */
class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $id       = session('customer_id');
        $customer = $id ? Customer::find($id) : null;

        if (! $customer) {
            session()->forget('customer_id');

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Login required.'], 401);
            }

            return redirect()->route('front.index', [
                'login'   => 1,
                'return'  => $request->fullUrl(),
            ]);
        }

        // Share the resolved customer with controllers/views for this request.
        $request->attributes->set('customer', $customer);
        app()->instance('current_customer', $customer);

        return $next($request);
    }
}
