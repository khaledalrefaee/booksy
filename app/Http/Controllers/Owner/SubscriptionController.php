<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $q            = trim($request->input('q', ''));
        $filterState  = $request->input('state', '');
        $filterPlanId = $request->input('plan_id', '');

        $today = Carbon::today();

        $companies = Company::query()
            ->with('plan')
            ->whereNotNull('plan_id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name_en', 'like', "%{$q}%")
                        ->orWhere('name_ar', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($filterPlanId !== '', fn ($query) => $query->where('plan_id', (int) $filterPlanId))
            ->orderByRaw('plan_expires_at IS NULL, plan_expires_at ASC')
            ->get()
            ->map(function (Company $company) use ($today) {
                $company->subscription_state = $this->stateFor($company, $today);

                return $company;
            });

        if ($filterState !== '') {
            $companies = $companies->where('subscription_state', $filterState)->values();
        }

        $stats = $this->stats($today);

        $plans   = Plan::query()->orderBy('sort_order')->orderBy('price')->get();
        $methods = SubscriptionPayment::methods();

        // Modal needs every company (a payment may start a first-ever subscription).
        $modalCompanies = Company::query()
            ->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar)")
            ->get(['id', 'name_en', 'name_ar', 'plan_id', 'plan_expires_at']);

        $modalCoupons = \App\Models\PlatformCoupon::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
            ->orderBy('code')
            ->get();

        return view('owner.subscriptions.index', compact(
            'companies', 'stats', 'plans', 'methods', 'q', 'filterState', 'filterPlanId', 'modalCompanies', 'modalCoupons',
        ));
    }

    /** none|expired|grace|expiring_soon|active */
    private function stateFor(Company $company, Carbon $today): string
    {
        if ($company->plan_id === null) {
            return 'none';
        }

        $expires = $company->plan_expires_at;

        if ($expires === null) {
            return 'active';
        }

        $graceEnd = $expires->copy()->addDays($company->plan?->grace_days ?? 0);

        if ($graceEnd->lt($today)) {
            return 'expired';
        }

        if ($expires->lt($today)) {
            return 'grace';
        }

        if ($expires->lte($today->copy()->addDays(7))) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /** @return array<string, mixed> */
    private function stats(Carbon $today): array
    {
        $subscribed = Company::query()->with('plan')->whereNotNull('plan_id')->get();

        $byState = $subscribed->groupBy(fn (Company $c) => $this->stateFor($c, $today));

        // MRR per currency: price normalised to 30 days, expired subscriptions excluded.
        $mrr = $subscribed
            ->filter(fn (Company $c) => in_array($this->stateFor($c, $today), ['active', 'expiring_soon'], true))
            ->groupBy(fn (Company $c) => $c->plan->currency)
            ->map(fn ($group) => $group->sum(
                fn (Company $c) => $c->plan->price * 30 / max($c->plan->duration_days, 1)
            ));

        $monthPayments = SubscriptionPayment::query()
            ->active()
            ->whereBetween('paid_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->get()
            ->groupBy('currency')
            ->map(fn ($group) => $group->sum('amount'));

        return [
            'mrr'            => $mrr,
            'month_payments' => $monthPayments,
            'active'         => ($byState['active'] ?? collect())->count() + ($byState['expiring_soon'] ?? collect())->count(),
            'expiring_soon'  => ($byState['expiring_soon'] ?? collect())->count(),
            'grace'          => ($byState['grace'] ?? collect())->count(),
            'expired'        => ($byState['expired'] ?? collect())->count(),
        ];
    }
}
