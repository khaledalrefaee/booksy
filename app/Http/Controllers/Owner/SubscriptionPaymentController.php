<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\PlatformCoupon;
use App\Models\SubscriptionPayment;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filterCompanyId = $request->input('company_id', '');
        $filterMethod    = $request->input('method', '');
        $filterFrom      = $request->input('from', '');
        $filterTo        = $request->input('to', '');

        $query = SubscriptionPayment::query()->with(['company', 'plan', 'owner'])->latest('paid_at')->latest('id');

        if ($filterCompanyId !== '') {
            $query->where('company_id', (int) $filterCompanyId);
        }

        if ($filterMethod !== '') {
            $query->where('method', $filterMethod);
        }

        if ($filterFrom !== '') {
            $query->whereDate('paid_at', '>=', $filterFrom);
        }

        if ($filterTo !== '') {
            $query->whereDate('paid_at', '<=', $filterTo);
        }

        // Totals per currency for the current filter set — voided payments excluded.
        $totals = (clone $query)->active()->get()->groupBy('currency')->map(fn ($group) => $group->sum('amount'));

        $payments  = $query->paginate(20)->withQueryString();
        $companies = Company::query()
            ->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar)")
            ->get(['id', 'name_en', 'name_ar', 'plan_id', 'plan_expires_at']);
        $plans     = Plan::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();
        $methods   = SubscriptionPayment::methods();

        $modalCoupons = PlatformCoupon::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
            ->orderBy('code')
            ->get();

        return view('owner.subscription-payments.index', compact(
            'payments', 'totals', 'companies', 'plans', 'methods', 'modalCoupons',
            'filterCompanyId', 'filterMethod', 'filterFrom', 'filterTo',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'plan_id'    => ['required', 'integer', 'exists:plans,id'],
            'amount'     => ['required', 'numeric', 'min:0', 'max:999999999'],
            'currency'   => ['required', 'string', 'max:10'],
            'method'     => ['required', 'string', Rule::in(array_keys(SubscriptionPayment::methods()))],
            'reference'  => ['nullable', 'string', 'max:255'],
            'paid_at'    => ['required', 'date', 'before_or_equal:today'],
            'notes'      => ['nullable', 'string', 'max:500'],
            'coupon_id'  => ['nullable', 'integer', 'exists:platform_coupons,id'],
        ]);

        $company = Company::query()->findOrFail($validated['company_id']);
        $plan    = Plan::query()->findOrFail($validated['plan_id']);

        // Coupon: validated server-side; the final amount is computed here,
        // never trusted from the form.
        $coupon   = null;
        $discount = null;

        if (! empty($validated['coupon_id'])) {
            $coupon = PlatformCoupon::query()->findOrFail($validated['coupon_id']);

            if (! $coupon->isUsable() || ! $coupon->appliesTo($company, $plan)) {
                return redirect()->back()->withInput()
                    ->with('error', __('This coupon cannot be applied to the selected company and plan.'));
            }

            $discount = $coupon->discountFor($plan);
            $validated['amount']   = round((float) $plan->price - $discount, 2);
            $validated['currency'] = $plan->currency;
        }

        $expiresBefore = $company->plan_expires_at;

        // Renewal never eats remaining days: extend from expiry if still in the
        // future, from today otherwise.
        $base = $expiresBefore !== null && $expiresBefore->isFuture()
            ? $expiresBefore->copy()
            : Carbon::today();

        $expiresAfter = $base->addDays($plan->duration_days);

        $payment = SubscriptionPayment::create([
            'company_id'     => $company->id,
            'company_label'  => $company->localizedName(),
            'plan_id'        => $plan->id,
            'plan_label'     => $plan->localizedName(),
            'owner_id'       => Auth::guard('owner')->id(),
            'amount'         => $validated['amount'],
            'currency'       => $validated['currency'],
            'method'         => $validated['method'],
            'reference'      => $validated['reference'] ?? null,
            'paid_at'        => $validated['paid_at'],
            'expires_before' => $expiresBefore,
            'plan_id_before' => $company->plan_id,
            'expires_after'  => $expiresAfter,
            'notes'          => $validated['notes'] ?? null,
            'coupon_id'      => $coupon?->id,
            'coupon_code'    => $coupon?->code,
            'list_price'     => $coupon !== null ? $plan->price : null,
            'discount_amount' => $discount,
        ]);

        $coupon?->increment('used_count');

        $company->fill([
            'plan_id'         => $plan->id,
            'plan_expires_at' => $expiresAfter,
        ]);

        OwnerAudit::recordChanges('billing.payment-record', $company, sprintf(
            '%s %s (%s)',
            $payment->amount,
            $payment->currency,
            $payment->method,
        ));
        $company->save();

        return redirect()
            ->back()
            ->with('success', __('Payment recorded — subscription extended to :date.', ['date' => $expiresAfter->format('Y-m-d')]));
    }

    /**
     * Company and plan are intentionally not editable: they define the recorded
     * renewal. A wrong company/plan is a void + re-record, not an edit.
     */
    public function update(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        if ($payment->isVoided()) {
            return redirect()->back()->with('error', __('A voided payment cannot be edited.'));
        }

        $validated = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0', 'max:999999999'],
            'currency'  => ['required', 'string', 'max:10'],
            'method'    => ['required', 'string', Rule::in(array_keys(SubscriptionPayment::methods()))],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at'   => ['required', 'date', 'before_or_equal:today'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $payment->fill($validated);

        OwnerAudit::recordChanges('billing.payment-update', $payment, label: $payment->company_label);
        $payment->save();

        return redirect()->back()->with('success', __('Payment updated successfully.'));
    }

    public function void(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        if ($payment->isVoided()) {
            return redirect()->back()->with('error', __('This payment is already voided.'));
        }

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        $payment->update([
            'voided_at'   => now(),
            'void_reason' => $validated['void_reason'],
            'voided_by'   => Auth::guard('owner')->id(),
        ]);

        // Free the coupon use back up.
        if ($payment->coupon_id !== null) {
            PlatformCoupon::query()->whereKey($payment->coupon_id)
                ->where('used_count', '>', 0)
                ->decrement('used_count');
        }

        OwnerAudit::record(
            'billing.payment-void',
            $payment,
            old: ['amount' => $payment->amount, 'currency' => $payment->currency],
            reason: $validated['void_reason'],
            label: $payment->company_label,
        );

        // Roll back the extension only while it is still the company's current
        // expiry — a later payment on top of this one must not be disturbed.
        $company = $payment->company;
        $reverted = false;

        if (
            $company !== null
            && $payment->expires_after !== null
            && $company->plan_expires_at !== null
            && $company->plan_expires_at->equalTo($payment->expires_after)
        ) {
            $company->fill([
                'plan_expires_at' => $payment->expires_before,
                'plan_id'         => $payment->plan_id_before,
            ]);
            OwnerAudit::recordChanges('company.subscription-update', $company, __('Reverted by voided payment #:id', ['id' => $payment->id]));
            $company->save();
            $reverted = true;
        }

        return redirect()->back()->with(
            'success',
            $reverted
                ? __('Payment voided and the subscription expiry was reverted to :date.', ['date' => $payment->expires_before?->format('Y-m-d') ?? __('None')])
                : __('Payment voided. The subscription expiry was left unchanged.'),
        );
    }
}
