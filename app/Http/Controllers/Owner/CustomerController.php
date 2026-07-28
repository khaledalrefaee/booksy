<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cross-tenant read-only customer directory. */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q            = trim($request->input('q', ''));
        $filterBanned = $request->input('banned', '');

        $query = Customer::query()->withCount('appointments');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($filterBanned === '1') {
            $query->where('is_banned', true);
        } elseif ($filterBanned === '0') {
            $query->where('is_banned', false);
        }

        $customers = $query->latest('id')->paginate(20)->withQueryString();

        $stats = [
            'total'    => Customer::query()->count(),
            'verified' => Customer::query()->whereNotNull('phone_verified_at')->count(),
            'banned'   => Customer::query()->where('is_banned', true)->count(),
        ];

        return view('owner.customers.index', compact('customers', 'stats', 'q', 'filterBanned'));
    }
}
