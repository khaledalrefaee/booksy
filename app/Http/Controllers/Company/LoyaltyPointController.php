<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyPointLog;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoyaltyPointController extends Controller
{
    public function adjust(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        LoyaltyPointLog::create([
            'customer_id' => $customer->id,
            'points'      => $data['points'],
            'reason'      => $data['reason'],
            'created_at'  => now(),
        ]);

        $customer->increment('loyalty_points', $data['points']);

        $action = $data['points'] > 0 ? 'Added' : 'Deducted';
        Auditor::log("{$action} {$data['points']} loyalty points for {$customer->name}: {$data['reason']}", $customer);

        return back()->with('success', __('Loyalty points updated.'));
    }
}
