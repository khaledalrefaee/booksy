<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use Illuminate\Support\Carbon;

/**
 * Company Workspace — Team tab (employees + attendance + leaves + holidays).
 * Read-focused monitoring; write actions with payroll/deduction side effects
 * defer to the full editor to keep balances consistent.
 */
class TeamController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        $employees = $company->employees()
            ->with(['branch', 'role'])
            ->orderByDesc('is_active')
            ->orderByLocalizedName()
            ->get();

        $today = Carbon::now(config('app.timezone'))->toDateString();
        $attendanceToday = collect();
        if ($company->hasFeature('attendance')) {
            $attendanceToday = AttendanceRecord::query()
                ->where('company_id', $company->id)
                ->whereDate('date', $today)
                ->with(['employee', 'branch'])
                ->get();
        }

        $leaves = collect();
        $holidays = collect();
        if ($company->hasFeature('leaves')) {
            $leaves = EmployeeLeave::query()
                ->where('company_id', $company->id)
                ->with('employee')
                ->orderByRaw("FIELD(status,'pending','approved','rejected')")
                ->orderByDesc('start_date')
                ->limit(50)
                ->get();
            $holidays = CompanyHoliday::query()
                ->where('company_id', $company->id)
                ->orderByDesc('start_date')
                ->limit(30)
                ->get();
        }

        return $this->tab('team', $company, compact('employees', 'attendanceToday', 'leaves', 'holidays'));
    }

    public function employee(Company $company, Employee $employee)
    {
        abort_unless($employee->company_id === $company->id, 404);
        $employee->load(['branch', 'role', 'compensation']);

        return $this->drawer('team-employee', $company, compact('employee'));
    }
}
