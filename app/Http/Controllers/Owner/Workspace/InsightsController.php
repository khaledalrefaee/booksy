<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\BranchPayment;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Company Workspace — Insights tab (P&L + employee performance + activity log).
 * Read-only. P&L is derived from the cash register (BranchPayment income/expense)
 * over a selectable month; performance from completed appointments.
 */
class InsightsController extends Controller
{
    use ScopesCompany;

    public function index(Company $company, Request $request)
    {
        if (! $company->hasFeature('reports')) {
            return $this->tab('insights', $company, ['locked' => true]);
        }

        $tz    = config('app.timezone');
        $month = Carbon::createFromFormat('Y-m', $request->input('month', Carbon::now($tz)->format('Y-m')), $tz)
            ?: Carbon::now($tz);
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $branchIds = $company->branches()->pluck('id');

        $payments = BranchPayment::query()
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$start, $end]);

        $income  = (float) (clone $payments)->whereIn('type', ['income', 'tip', 'other_income'])->sum('amount');
        $expense = (float) (clone $payments)->whereIn('type', ['expense', 'refund'])->sum('amount');
        $net     = $income - $expense;

        $performance = Appointment::query()
            ->where('company_id', $company->id)
            ->where('status', 'completed')
            ->whereBetween('start_time', [$start, $end])
            ->whereNotNull('employee_id')
            ->selectRaw('employee_id, count(*) as jobs, sum(total_price) as revenue')
            ->groupBy('employee_id')
            ->with('employee')
            ->orderByDesc('revenue')
            ->get();

        $activity = ActivityLog::query()
            ->where('causer_type', 'company')
            ->where('causer_id', $company->id)
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return $this->tab('insights', $company, [
            'locked'      => false,
            'income'      => $income,
            'expense'     => $expense,
            'net'         => $net,
            'performance' => $performance,
            'activity'    => $activity,
            'monthValue'  => $month->format('Y-m'),
            'monthLabel'  => $month->translatedFormat('F Y'),
        ]);
    }
}
