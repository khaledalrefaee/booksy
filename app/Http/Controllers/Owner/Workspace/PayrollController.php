<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Company;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeDeduction;
use App\Models\PayrollPayment;
use Illuminate\Support\Carbon;

/**
 * Company Workspace — Payroll tab (payroll status + deductions + advances).
 * Read-focused: shows each employee's base pay and whether they're paid this
 * month, plus recent deductions/advances. Running payroll / marking paid (which
 * moves cash) defers to the full editor.
 */
class PayrollController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        if (! $company->hasFeature('payroll')) {
            return $this->tab('payroll', $company, ['locked' => true]);
        }

        $now   = Carbon::now(config('app.timezone'));
        $month = (int) $now->month;
        $year  = (int) $now->year;

        $employees = $company->employees()
            ->where('is_active', true)
            ->with(['compensation', 'branch'])
            ->orderByLocalizedName()
            ->get();

        $paidMap = PayrollPayment::query()
            ->where('company_id', $company->id)
            ->where('month', $month)->where('year', $year)
            ->pluck('net_amount', 'employee_id');

        $employeeIds = $employees->pluck('id');

        $deductions = EmployeeDeduction::query()
            ->whereIn('employee_id', $employeeIds)
            ->with('employee')
            ->latest('deduction_date')
            ->limit(40)
            ->get();

        $advances = EmployeeAdvance::query()
            ->where('company_id', $company->id)
            ->with('employee')
            ->latest('advance_date')
            ->limit(40)
            ->get();

        return $this->tab('payroll', $company, [
            'locked'     => false,
            'employees'  => $employees,
            'paidMap'    => $paidMap,
            'deductions' => $deductions,
            'advances'   => $advances,
            'periodLabel'=> $now->translatedFormat('F Y'),
        ]);
    }
}
