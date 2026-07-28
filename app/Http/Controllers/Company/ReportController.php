<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BranchPayment;
use App\Models\Appointment;
use App\Models\PayrollPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    // ── Profit & Loss ──────────────────────────────────────────────────────
    public function profitLoss(Request $request): View
    {
        $company = $this->company();
        $branchId = $request->input('branch_id');
        [$month, $year] = $this->safeMonthYear($request);

        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth()->endOfDay();

        $prevFrom = $from->copy()->subMonth()->startOfMonth();
        $prevTo = $prevFrom->copy()->endOfMonth()->endOfDay();

        $branches = $company->branches()->orderBy('sort_order')->get();

        $currentData = $this->calcPL($company->id, $branchId, $from, $to);
        $prevData = $this->calcPL($company->id, $branchId, $prevFrom, $prevTo);

        // Top services
        $topServicesQuery = Appointment::where('company_id', $company->id)
            ->where('status', 'completed')
            ->whereBetween('start_time', [$from, $to])
            ->with('service');
        if ($branchId) $topServicesQuery->where('branch_id', $branchId);

        $topServices = $topServicesQuery
            ->select('service_id')
            ->selectRaw('COUNT(*) as cnt, SUM(total_price) as revenue')
            ->groupBy('service_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'name' => $r->service?->localizedName() ?? '—',
                'count' => $r->cnt,
                'revenue' => round((float) $r->revenue, 2),
            ]);

        // Top employees
        $topEmployeesQuery = Appointment::where('company_id', $company->id)
            ->where('status', 'completed')
            ->whereBetween('start_time', [$from, $to])
            ->whereNotNull('employee_id')
            ->with('employee');
        if ($branchId) $topEmployeesQuery->where('branch_id', $branchId);

        $topEmployees = $topEmployeesQuery
            ->select('employee_id')
            ->selectRaw('COUNT(*) as cnt, SUM(total_price) as revenue')
            ->groupBy('employee_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'name' => $r->employee?->localizedName() ?? '—',
                'count' => $r->cnt,
                'revenue' => round((float) $r->revenue, 2),
            ]);

        // Daily chart
        $dailyQuery = BranchPayment::where('company_id', $company->id)
            ->whereBetween('paid_at', [$from, $to]);
        if ($branchId) $dailyQuery->where('branch_id', $branchId);

        $cats = BranchPayment::CATEGORIES;
        $inCats = collect($cats)->filter(fn($c) => $c['type'] === 'income')->keys()->toArray();
        $exCats = collect($cats)->filter(fn($c) => $c['type'] === 'expense')->keys()->toArray();
        $inP = implode(',', array_fill(0, count($inCats), '?'));
        $exP = implode(',', array_fill(0, count($exCats), '?'));

        $dailyData = (clone $dailyQuery)
            ->selectRaw("DATE(paid_at) as dt")
            ->selectRaw("SUM(CASE WHEN category IN ({$inP}) THEN amount ELSE 0 END) as income", $inCats)
            ->selectRaw("SUM(CASE WHEN category IN ({$exP}) THEN amount ELSE 0 END) as expense", $exCats)
            ->groupByRaw("DATE(paid_at)")
            ->orderBy('dt')
            ->get();

        $chartLabels = [];
        $chartIncome = [];
        $chartExpense = [];
        $chartProfit = [];

        $totalDays = $from->daysInMonth;
        $dailyMap = $dailyData->keyBy('dt');

        for ($d = 0; $d < $totalDays; $d++) {
            $day = $from->copy()->addDays($d)->toDateString();
            $r = $dailyMap->get($day);
            $inc = $r ? (float) $r->income : 0;
            $exp = $r ? (float) $r->expense : 0;
            $chartLabels[] = $d + 1;
            $chartIncome[] = $inc;
            $chartExpense[] = $exp;
            $chartProfit[] = $inc - $exp;
        }

        return view('company.reports.profit-loss', compact(
            'branches', 'branchId', 'month', 'year', 'from', 'to',
            'currentData', 'prevData',
            'topServices', 'topEmployees',
            'chartLabels', 'chartIncome', 'chartExpense', 'chartProfit'
        ));
    }

    private function calcPL(int $companyId, ?int $branchId, Carbon $from, Carbon $to): array
    {
        $cats = BranchPayment::CATEGORIES;
        $inCats = collect($cats)->filter(fn($c) => $c['type'] === 'income')->keys()->toArray();
        $exCats = collect($cats)->filter(fn($c) => $c['type'] === 'expense')->keys()->toArray();
        $inP = implode(',', array_fill(0, count($inCats), '?'));
        $exP = implode(',', array_fill(0, count($exCats), '?'));

        $base = BranchPayment::where('company_id', $companyId)
            ->whereBetween('paid_at', [$from, $to]);
        if ($branchId) $base->where('branch_id', $branchId);

        $summary = (clone $base)
            ->selectRaw("SUM(CASE WHEN category IN ({$inP}) THEN amount ELSE 0 END) as income", $inCats)
            ->selectRaw("SUM(CASE WHEN category IN ({$exP}) THEN amount ELSE 0 END) as expense", $exCats)
            ->first();

        $income = round((float) ($summary->income ?? 0), 2);
        $expense = round((float) ($summary->expense ?? 0), 2);
        $net = round($income - $expense, 2);
        $margin = $income > 0 ? round(($net / $income) * 100, 1) : 0;

        // By category breakdown
        $byCategory = (clone $base)
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn($v) => round((float) $v, 2));

        // Appointment count
        $apptQuery = Appointment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('start_time', [$from, $to]);
        if ($branchId) $apptQuery->where('branch_id', $branchId);
        $appointmentCount = $apptQuery->count();

        // Payroll total
        $payrollQuery = PayrollPayment::where('company_id', $companyId)
            ->where('month', $from->month)
            ->where('year', $from->year);
        if ($branchId) $payrollQuery->where('branch_id', $branchId);
        $payrollTotal = round((float) $payrollQuery->sum('net_amount'), 2);

        return compact('income', 'expense', 'net', 'margin', 'byCategory', 'appointmentCount', 'payrollTotal');
    }

    // ── Employee Performance ────────────────────────────────────────────────
    public function employeePerformance(Request $request): View
    {
        $company = $this->company();
        [$month, $year] = $this->safeMonthYear($request);
        $branchId = $request->input('branch_id');

        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth()->endOfDay();

        $branches = $company->branches()->orderBy('sort_order')->get();

        $query = Appointment::where('company_id', $company->id)
            ->where('status', 'completed')
            ->whereBetween('start_time', [$from, $to])
            ->whereNotNull('employee_id');
        if ($branchId) $query->where('branch_id', $branchId);

        $employees = $query
            ->select('employee_id')
            ->selectRaw('COUNT(*) as appt_count, SUM(total_price) as revenue')
            ->groupBy('employee_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($r) {
                $emp = \App\Models\Employee::find($r->employee_id);
                return [
                    'employee' => $emp,
                    'name' => $emp?->localizedName() ?? '—',
                    'branch' => $emp?->branch?->localizedName() ?? '—',
                    'appointments' => $r->appt_count,
                    'revenue' => round((float) $r->revenue, 2),
                    'avg' => $r->appt_count > 0 ? round((float) $r->revenue / $r->appt_count, 2) : 0,
                ];
            });

        return view('company.reports.employee-performance', compact(
            'employees', 'branches', 'branchId', 'month', 'year'
        ));
    }
}
