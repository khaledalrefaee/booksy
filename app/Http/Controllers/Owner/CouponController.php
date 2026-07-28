<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\PlatformCoupon;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = PlatformCoupon::query()->latest('id')->get();

        $companies = Company::query()
            ->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar)")
            ->get(['id', 'name_en', 'name_ar']);

        $plans = Plan::query()->orderBy('sort_order')->orderBy('price')->get();

        return view('owner.coupons.index', compact('coupons', 'companies', 'plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $coupon = PlatformCoupon::create($this->validated($request));

        OwnerAudit::record('coupon.create', $coupon, new: $coupon->only(['code', 'type', 'value']), label: $coupon->code);

        return redirect()->route('owner.coupons.index')
            ->with('success', __('Coupon created successfully.'));
    }

    public function update(Request $request, PlatformCoupon $coupon): RedirectResponse
    {
        $coupon->fill($this->validated($request, $coupon));

        OwnerAudit::recordChanges('coupon.update', $coupon, label: $coupon->code);
        $coupon->save();

        return redirect()->route('owner.coupons.index')
            ->with('success', __('Coupon updated successfully.'));
    }

    public function destroy(PlatformCoupon $coupon): RedirectResponse
    {
        if ($coupon->used_count > 0) {
            $coupon->update(['is_active' => false]);

            OwnerAudit::record('coupon.deactivate', $coupon, label: $coupon->code);

            return redirect()->route('owner.coupons.index')
                ->with('warning', __('This coupon was already used, so it was deactivated instead of deleted.'));
        }

        OwnerAudit::record('coupon.delete', $coupon, old: $coupon->only(['code', 'type', 'value']), label: $coupon->code);

        $coupon->delete();

        return redirect()->route('owner.coupons.index')
            ->with('success', __('Coupon deleted successfully.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?PlatformCoupon $coupon = null): array
    {
        $data = $request->validate([
            'code'          => ['required', 'string', 'max:40', Rule::unique('platform_coupons', 'code')->ignore($coupon?->id)],
            'description'   => ['nullable', 'string', 'max:255'],
            'type'          => ['required', Rule::in(['percent', 'fixed'])],
            'value'         => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'currency'      => ['required_if:type,fixed', 'nullable', 'string', 'max:10'],
            'company_ids'   => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'plan_ids'      => ['nullable', 'array'],
            'plan_ids.*'    => ['integer', 'exists:plans,id'],
            'max_uses'      => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_at'    => ['nullable', 'date'],
        ]);

        if ($data['type'] === 'percent') {
            $request->validate(['value' => ['numeric', 'max:100']]);
            $data['currency'] = null;
        }

        $data['code']        = strtoupper(trim($data['code']));
        $data['company_ids'] = ($data['company_ids'] ?? []) ?: null;
        $data['plan_ids']    = ($data['plan_ids'] ?? []) ?: null;
        $data['is_active']   = $request->boolean('is_active');

        return $data;
    }
}
