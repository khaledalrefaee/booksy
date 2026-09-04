<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LoyaltyReward;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    private function authoriseReward(LoyaltyReward $reward): void
    {
        abort_unless($reward->branch->company_id === $this->company()->id, 403);
    }

    /** The loyalty program for one branch: earn settings + rewards catalog. */
    public function index(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $rewards  = $branch->loyaltyRewards()->with('service')->get();
        $services = $branch->services()->where('is_active', true)
            ->orderBy('name_ar')->orderBy('name_en')->get();

        return view('company.loyalty.index', compact('branch', 'rewards', 'services'));
    }

    /** Save how points are earned at this branch. */
    public function updateSettings(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'loyalty_points_per_visit'         => ['required', 'integer', 'min:0', 'max:9999'],
            'loyalty_points_per_currency_unit' => ['required', 'integer', 'min:0'],
        ]);

        $branch->update($data);
        Auditor::log("Updated loyalty earn settings for branch {$branch->localizedName()}", $branch);

        return back()->with('success', __('Loyalty settings saved.'));
    }

    /** Add a reward to this branch's catalog. */
    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:free_service,percent_all,percent_service'],
            'points_cost'      => ['required', 'integer', 'min:1', 'max:1000000'],
            'service_id'       => ['nullable', 'required_if:type,free_service,percent_service', 'integer'],
            'discount_percent' => ['nullable', 'required_if:type,percent_all,percent_service', 'integer', 'min:1', 'max:100'],
        ], [
            'service_id.required_if'       => __('Please choose the service for this reward.'),
            'discount_percent.required_if' => __('Please enter the discount percentage.'),
        ]);

        // A service-bound reward must reference a service in THIS branch.
        if (in_array($data['type'], ['free_service', 'percent_service'], true)) {
            abort_unless(
                $branch->services()->whereKey($data['service_id'])->exists(),
                422
            );
        } else {
            $data['service_id'] = null;
        }

        if ($data['type'] === 'free_service') {
            $data['discount_percent'] = null;
        }

        $data['sort_order'] = (int) $branch->loyaltyRewards()->max('sort_order') + 1;

        $branch->loyaltyRewards()->create($data);
        Auditor::log("Added loyalty reward '{$data['name']}' to branch {$branch->localizedName()}", $branch);

        return back()->with('success', __('Reward added.'));
    }

    public function destroy(LoyaltyReward $reward): RedirectResponse
    {
        $this->authoriseReward($reward);

        $name = $reward->name;
        $reward->delete();
        Auditor::log("Removed loyalty reward '{$name}'", $reward->branch);

        return back()->with('success', __('Reward removed.'));
    }
}
