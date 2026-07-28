<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->withCount('companies')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('owner.plans.index', [
            'plans'          => $plans,
            'featureCatalog' => Plan::featureCatalog(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = Plan::create($this->validated($request));

        OwnerAudit::record('plan.create', $plan, new: $plan->only(['name_en', 'price', 'currency', 'duration_days']));

        return redirect()->route('owner.plans.index')
            ->with('success', __('Plan created successfully.'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->fill($this->validated($request));

        OwnerAudit::recordChanges('plan.update', $plan);
        $plan->save();

        return redirect()->route('owner.plans.index')
            ->with('success', __('Plan updated successfully.'));
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->companies()->exists()) {
            return redirect()->route('owner.plans.index')
                ->with('error', __('This plan is assigned to companies and cannot be deleted.'));
        }

        OwnerAudit::record('plan.delete', $plan, old: $plan->only(['name_en', 'price', 'currency']));

        $plan->delete();

        return redirect()->route('owner.plans.index')
            ->with('success', __('Plan deleted successfully.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_en'       => ['required', 'string', 'max:255'],
            'name_ar'       => ['required', 'string', 'max:255'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:10'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'grace_days'    => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_branches'  => ['nullable', 'integer', 'min:1', 'max:999'],
            'max_employees' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'sort_order'    => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'     => ['nullable', 'boolean'],
            'features'      => ['nullable', 'array'],
            'features.*'    => ['string', 'in:'.implode(',', Plan::featureKeys())],
        ]);

        // Checkbox list → {key: bool} map covering every catalog key
        $selected = $data['features'] ?? [];
        $data['features'] = collect(Plan::featureKeys())
            ->mapWithKeys(fn ($key) => [$key => in_array($key, $selected, true)])
            ->all();

        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['grace_days'] = $data['grace_days'] ?? 0;

        return $data;
    }
}
