<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function growth(): View
    {
        $start = Carbon::today()->startOfMonth()->subMonths(11);

        // One row per month: signups, new customers, subscription revenue and GMV per currency.
        $months = collect(range(0, 11))->map(fn (int $i) => $start->copy()->addMonths($i));

        $signups = Company::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
            ->groupBy('ym')->pluck('cnt', 'ym');

        $customers = Customer::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
            ->groupBy('ym')->pluck('cnt', 'ym');

        $revenue = SubscriptionPayment::query()->active()
            ->where('paid_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, currency, SUM(amount) as total")
            ->groupBy('ym', 'currency')->get()
            ->groupBy('ym');

        $gmv = Invoice::query()
            ->where('issued_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(issued_at, '%Y-%m') as ym, currency, SUM(total) as total")
            ->groupBy('ym', 'currency')->get()
            ->groupBy('ym');

        $rows = $months->map(function (Carbon $month) use ($signups, $customers, $revenue, $gmv) {
            $ym = $month->format('Y-m');

            return [
                'month'     => $month,
                'signups'   => (int) ($signups[$ym] ?? 0),
                'customers' => (int) ($customers[$ym] ?? 0),
                'revenue'   => ($revenue[$ym] ?? collect())->mapWithKeys(fn ($r) => [$r->currency => (float) $r->total]),
                'gmv'       => ($gmv[$ym] ?? collect())->mapWithKeys(fn ($r) => [$r->currency => (float) $r->total]),
            ];
        });

        $maxSignups   = max(1, $rows->max('signups'));
        $maxCustomers = max(1, $rows->max('customers'));

        $summary = [
            'companies_total'  => Company::query()->count(),
            'signups_12mo'     => $rows->sum('signups'),
            'customers_12mo'   => $rows->sum('customers'),
            'revenue_12mo'     => SubscriptionPayment::query()->active()
                ->where('paid_at', '>=', $start)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')->pluck('total', 'currency'),
        ];

        return view('owner.reports.growth', compact('rows', 'summary', 'maxSignups', 'maxCustomers'));
    }

    public function revenue(): View
    {
        $start = Carbon::today()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->map(fn (int $i) => $start->copy()->addMonths($i));

        $byMonth = SubscriptionPayment::query()->active()
            ->where('paid_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, currency, SUM(amount) as total, COUNT(*) as cnt, SUM(discount_amount) as discount")
            ->groupBy('ym', 'currency')->get()
            ->groupBy('ym');

        $rows = $months->map(function (Carbon $month) use ($byMonth) {
            $ym = $month->format('Y-m');
            $r  = $byMonth[$ym] ?? collect();

            return [
                'month'    => $month,
                'payments' => (int) $r->sum('cnt'),
                'revenue'  => $r->mapWithKeys(fn ($x) => [$x->currency => (float) $x->total]),
                'discount' => $r->mapWithKeys(fn ($x) => [$x->currency => (float) $x->discount]),
            ];
        });

        $maxPayments = max(1, $rows->max('payments'));

        // Revenue per plan over the same window (labels survive plan deletion)
        $byPlan = SubscriptionPayment::query()->active()
            ->where('paid_at', '>=', $start)
            ->selectRaw('COALESCE(plan_label, "—") as plan, currency, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('plan', 'currency')->get()
            ->groupBy('plan')
            ->map(fn ($g) => [
                'payments' => (int) $g->sum('cnt'),
                'revenue'  => $g->mapWithKeys(fn ($x) => [$x->currency => (float) $x->total]),
            ])
            ->sortByDesc(fn ($p) => $p['revenue']->sum());

        // Top paying companies (label survives company deletion)
        $topCompanies = SubscriptionPayment::query()->active()
            ->where('paid_at', '>=', $start)
            ->selectRaw('company_id, COALESCE(company_label, "—") as company, currency, SUM(amount) as total, COUNT(*) as cnt, MAX(paid_at) as last_paid')
            ->groupBy('company_id', 'company', 'currency')->get()
            ->groupBy('company_id')
            ->map(fn ($g) => [
                'company_id' => $g->first()->company_id,
                'company'    => $g->first()->company,
                'payments'   => (int) $g->sum('cnt'),
                'last_paid'  => $g->max('last_paid'),
                'revenue'    => $g->mapWithKeys(fn ($x) => [$x->currency => (float) $x->total]),
            ])
            ->sortByDesc(fn ($c) => $c['revenue']->sum())
            ->take(10)
            ->values();

        $thisMonth = Carbon::today()->format('Y-m');
        $lastMonth = Carbon::today()->subMonthNoOverflow()->format('Y-m');

        $summary = [
            'revenue_12mo'   => SubscriptionPayment::query()->active()
                ->where('paid_at', '>=', $start)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')->pluck('total', 'currency'),
            'revenue_this'   => $rows->firstWhere(fn ($r) => $r['month']->format('Y-m') === $thisMonth)['revenue'] ?? collect(),
            'revenue_last'   => $rows->firstWhere(fn ($r) => $r['month']->format('Y-m') === $lastMonth)['revenue'] ?? collect(),
            'payments_12mo'  => $rows->sum('payments'),
            'active_subs'    => Company::query()->whereNotNull('plan_expires_at')
                ->where('plan_expires_at', '>=', Carbon::today())->count(),
        ];

        return view('owner.reports.revenue', compact('rows', 'summary', 'byPlan', 'topCompanies', 'maxPayments'));
    }
}
