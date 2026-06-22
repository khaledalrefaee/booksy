<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayrollController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authoriseEmployee(Employee $employee): void
    {
        abort_unless($employee->company_id === $this->company()->id, 403);
    }

    /**
     * Global payroll: all employees summary for a given month.
     */
    public function index(Request $request): View
    {
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $company   = $this->company();
        $employees = $company->employees()
            ->with(['compensation', 'branch', 'serviceCommissions'])
            ->where('is_active', true)
            ->orderBy('name_en')
            ->get();

        $rows = $employees->map(fn($emp) => $this->calcPayroll($emp, $start, $end));

        return view('company.payroll.index', compact('rows', 'month', 'year'));
    }

    /**
     * Detailed payroll for one employee.
     */
    public function show(Employee $employee, Request $request): View
    {
        $this->authoriseEmployee($employee);

        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $result = $this->calcPayroll($employee, $start, $end);

        return view('company.payroll.show', array_merge($result, [
            'employee' => $employee,
            'month'    => $month,
            'year'     => $year,
            'start'    => $start,
            'end'      => $end,
        ]));
    }

    /**
     * Core calculation: returns array with all payroll data for one employee and period.
     */
    private function calcPayroll(Employee $employee, Carbon $start, Carbon $end): array
    {
        $compensation = $employee->compensation;

        // ── 1. Completed appointments in period ──────────────────────────────
        $appointments = $employee->appointments()
            ->whereBetween('start_time', [$start, $end])
            ->where('status', 'completed')
            ->with(['service', 'customer'])
            ->orderBy('start_time')
            ->get();

        // ── 2. Commission rates per service ──────────────────────────────────
        $serviceRates = [];
        if ($compensation && $compensation->commission_type === 'per_service') {
            $serviceRates = $employee->serviceCommissions()
                ->pluck('rate', 'service_id')
                ->toArray();
        }

        // ── 3. Commission per appointment (with currency) ────────────────────
        $defaultCurrency = config('booksy.default_currency', 'SYP');
        $salaryCurrency  = $compensation?->currency ?? $defaultCurrency;

        $appointments = $appointments->map(function ($appt) use ($compensation, $serviceRates, $defaultCurrency) {
            $rate     = 0;
            $earned   = 0;
            $currency = $appt->service?->currency ?? $defaultCurrency;

            if ($compensation && in_array($compensation->type, ['commission', 'mixed'])) {
                if ($compensation->commission_type === 'flat') {
                    $rate   = (float) ($compensation->commission_rate ?? 0);
                    $earned = round(($appt->total_price * $rate) / 100, 2);
                } elseif ($compensation->commission_type === 'per_service') {
                    $rate   = (float) ($serviceRates[$appt->service_id] ?? 0);
                    $earned = round(($appt->total_price * $rate) / 100, 2);
                }
            }

            $appt->commission_rate     = $rate;
            $appt->commission_earned   = $earned;
            $appt->commission_currency = $currency;
            return $appt;
        });

        // ── 4. Base salary ───────────────────────────────────────────────────
        $baseSalary = 0;
        if ($compensation && in_array($compensation->type, ['salary', 'mixed'])) {
            $baseSalary = (float) ($compensation->base_amount ?? 0);
        }

        // ── 5. Deductions in period ──────────────────────────────────────────
        $deductions = $employee->deductions()
            ->whereBetween('deduction_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_sick_leave', false)
            ->orderBy('deduction_date')
            ->get();

        // ── 6. Group commissions by currency ─────────────────────────────────
        // Each currency is summed independently — never mixed numerically.
        $commissionsByCurrency = $appointments
            ->filter(fn($a) => $a->commission_earned > 0)
            ->groupBy('commission_currency')
            ->map(fn($group) => round($group->sum('commission_earned'), 2));

        // Commissions in same currency as salary (can be combined with base)
        $commInSalaryCurrency = (float) ($commissionsByCurrency[$salaryCurrency] ?? 0);

        // Commissions in OTHER currencies (shown separately, never added to salary)
        $otherCommissions = $commissionsByCurrency->filter(
            fn($amount, $currency) => $currency !== $salaryCurrency && $amount > 0
        );

        $totalDeducted = round($deductions->sum('amount'), 2);
        $grossPay      = round($baseSalary + $commInSalaryCurrency, 2);
        $netPay        = round($grossPay - $totalDeducted, 2);

        return compact(
            'employee',
            'compensation',
            'appointments',
            'deductions',
            'salaryCurrency',
            'baseSalary',
            'commissionsByCurrency',
            'commInSalaryCurrency',
            'otherCommissions',
            'totalDeducted',
            'grossPay',
            'netPay'
        );
    }
}
