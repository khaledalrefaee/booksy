<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCommunication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerCommunicationController extends Controller
{
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'type'      => ['required', 'in:' . implode(',', array_keys(CustomerCommunication::TYPES))],
            'direction' => ['required', 'in:' . implode(',', array_keys(CustomerCommunication::DIRECTIONS))],
            'message'   => ['nullable', 'string', 'max:2000'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $company = Auth::guard('company')->user();

        CustomerCommunication::create([
            'customer_id'     => $customer->id,
            'branch_id'       => $data['branch_id'] ?? null,
            'type'            => $data['type'],
            'direction'       => $data['direction'],
            'message'         => $data['message'] ?? null,
            'created_by_name' => $company->localizedName(),
            'created_at'      => now(),
        ]);

        return back()->with('success', __('Communication logged.'));
    }
}
