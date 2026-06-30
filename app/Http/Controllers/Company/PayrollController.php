<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchPayment;
use App\Models\Employee;
use App\Models\PayrollPayment;
use App\Support\Auditor;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    private function authoriseEmployee(Employee $employee): void
    {
        abort_unless($employee->company_id === $this->company()->id, 403);
    }

    public function index(Request $request): View
    {
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $branchId = $request->input('branch_id');

        $query = $company->employees()
            ->with(['compensation', 'branch', 'serviceCommissions'])
            ->where('is_active', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $employees = $query->orderBy('name_en')->get();

        $paidMap = PayrollPayment::where('company_id', $company->id)
            ->where('month', $month)
            ->where('year', $year)
            ->pluck('paid_at', 'employee_id');

        $rows = $employees->map(function ($emp) use ($start, $end, $paidMap) {
            $data = $this->calcPayroll($emp, $start, $end);
            $data['isPaid'] = $paidMap->has($emp->id);
            $data['paidAt'] = $paidMap->get($emp->id);
            return $data;
        });

        return view('company.payroll.index', compact('rows', 'month', 'year', 'branches', 'branchId'));
    }

    public function show(Employee $employee, Request $request): View
    {
        $this->authoriseEmployee($employee);

        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $result    = $this->calcPayroll($employee, $start, $end);
        $payPeriod = $employee->compensation?->pay_period ?? 'monthly';

        $payrollPayments = PayrollPayment::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $periods = $this->buildPeriods($year, $month, $payPeriod, $payrollPayments, $result);

        return view('company.payroll.show', array_merge($result, [
            'employee'        => $employee,
            'month'           => $month,
            'year'            => $year,
            'start'           => $start,
            'end'             => $end,
            'payPeriod'       => $payPeriod,
            'periods'         => $periods,
            'payrollPayments' => $payrollPayments,
            'payrollPayment'  => $payrollPayments->first(),
        ]));
    }

    private function buildPeriods(int $year, int $month, string $payPeriod, $payments, array $result): array
    {
        $monthStart    = Carbon::create($year, $month, 1);
        $monthEnd      = $monthStart->copy()->endOfMonth();
        $appointments  = $result['appointments'];
        $basePerPeriod = $result['basePerPeriod'];
        $salaryCurrency = $result['salaryCurrency'];
        $deductions    = $result['deductions'];
        $periods       = [];

        if ($payPeriod === 'weekly') {
            $cursor = $monthStart->copy();
            $week   = 1;
            while ($cursor->lte($monthEnd) && $week <= 5) {
                $weekStart = $cursor->copy();
                $weekEnd   = $cursor->copy()->addDays(6);
                if ($weekEnd->gt($monthEnd)) $weekEnd = $monthEnd->copy();

                $weekAppts = $appointments->filter(fn($a) => $a->start_time->between($weekStart, $weekEnd->endOfDay()));
                $weekCommByCurrency = $weekAppts->filter(fn($a) => $a->commission_earned > 0)
                    ->groupBy('commission_currency')
                    ->map(fn($g) => round($g->sum('commission_earned'), 2));
                $weekCommSalary = (float) ($weekCommByCurrency[$salaryCurrency] ?? 0);
                $weekDed   = $deductions->filter(fn($d) => $d->deduction_date->between($weekStart, $weekEnd) && ($d->currency ?: $salaryCurrency) === $salaryCurrency)->sum('amount');
                $weekNet   = round($basePerPeriod + $weekCommSalary - $weekDed, 2);

                $paid = $payments->firstWhere('week_number', $week);
                $periods[] = [
                    'label'      => __('Week :n', ['n' => $week]) . ' (' . $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m') . ')',
                    'key'        => $week,
                    'type'       => 'week',
                    'paid'       => (bool) $paid,
                    'paidAt'     => $paid?->paid_at,
                    'amount'     => $paid?->net_amount ?? $weekNet,
                    'base'       => $basePerPeriod,
                    'commission' => $weekCommSalary,
                    'commissionsByCurrency' => $weekCommByCurrency,
                    'deduction'  => round($weekDed, 2),
                    'netAmount'  => $weekNet,
                    'apptsCount' => $weekAppts->count(),
                    'start'      => $weekStart,
                    'end'        => $weekEnd,
                ];
                $cursor->addDays(7);
                $week++;
            }
        } elseif ($payPeriod === 'daily') {
            $cursor = $monthStart->copy();
            $today  = now()->endOfDay();
            while ($cursor->lte($monthEnd) && $cursor->lte($today)) {
                $dayNum  = $cursor->day;
                $dayAppts = $appointments->filter(fn($a) => $a->start_time->isSameDay($cursor));
                $dayCommByCurrency = $dayAppts->filter(fn($a) => $a->commission_earned > 0)
                    ->groupBy('commission_currency')
                    ->map(fn($g) => round($g->sum('commission_earned'), 2));
                $dayCommSalary = (float) ($dayCommByCurrency[$salaryCurrency] ?? 0);
                $dayDed   = $deductions->filter(fn($d) => $d->deduction_date->isSameDay($cursor) && ($d->currency ?: $salaryCurrency) === $salaryCurrency)->sum('amount');
                $dayNet   = round($basePerPeriod + $dayCommSalary - $dayDed, 2);

                $paid = $payments->firstWhere('day', $dayNum);
                $periods[] = [
                    'label'      => $cursor->translatedFormat('l d/m'),
                    'key'        => $dayNum,
                    'type'       => 'day',
                    'paid'       => (bool) $paid,
                    'paidAt'     => $paid?->paid_at,
                    'amount'     => $paid?->net_amount ?? $dayNet,
                    'base'       => $basePerPeriod,
                    'commission' => $dayCommSalary,
                    'commissionsByCurrency' => $dayCommByCurrency,
                    'deduction'  => round($dayDed, 2),
                    'netAmount'  => $dayNet,
                    'apptsCount' => $dayAppts->count(),
                    'start'      => $cursor->copy(),
                    'end'        => $cursor->copy()->endOfDay(),
                ];
                $cursor->addDay();
            }
        } else {
            $paid = $payments->whereNull('week_number')->whereNull('day')->first();
            $periods[] = [
                'label'      => __('Monthly salary'),
                'key'        => null,
                'type'       => 'month',
                'paid'       => (bool) $paid,
                'paidAt'     => $paid?->paid_at,
                'amount'     => $paid?->net_amount ?? $result['netPay'],
                'base'       => $result['baseSalary'],
                'commission' => $result['commInSalaryCurrency'],
                'commissionsByCurrency' => $result['commissionsByCurrency'],
                'deduction'  => $result['deductedInSalaryCurrency'],
                'netAmount'  => $result['netPay'],
                'apptsCount' => $appointments->count(),
                'start'      => $monthStart,
                'end'        => $monthEnd,
            ];
        }

        return $periods;
    }

    public function markAsPaid(Employee $employee, Request $request): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'month'          => ['required', 'integer', 'min:1', 'max:12'],
            'year'           => ['required', 'integer', 'min:2020'],
            'week_number'    => ['nullable', 'integer', 'min:1', 'max:5'],
            'day'            => ['nullable', 'integer', 'min:1', 'max:31'],
            'pay_period'     => ['required', 'in:monthly,weekly,daily'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $month      = $data['month'];
        $year       = $data['year'];
        $weekNumber = $data['week_number'] ?? null;
        $day        = $data['day'] ?? null;
        $payPeriod  = $data['pay_period'];

        $existingQuery = PayrollPayment::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year);

        if ($payPeriod === 'weekly') {
            $existingQuery->where('week_number', $weekNumber);
        } elseif ($payPeriod === 'daily') {
            $existingQuery->where('day', $day);
        } else {
            $existingQuery->whereNull('week_number')->whereNull('day');
        }

        if ($existingQuery->exists()) {
            return back()->with('error', __('Salary already paid for this period.'));
        }

        $start  = Carbon::create($year, $month, 1)->startOfDay();
        $end    = $start->copy()->endOfMonth()->endOfDay();
        $result = $this->calcPayroll($employee, $start, $end);

        $currency = $result['salaryCurrency'];

        $payrollPayments = PayrollPayment::where('employee_id', $employee->id)
            ->where('month', $month)->where('year', $year)->get();

        $periods = $this->buildPeriods($year, $month, $payPeriod, $payrollPayments, $result);

        $currentPeriod = collect($periods)->first(function ($p) use ($payPeriod, $weekNumber, $day) {
            if ($payPeriod === 'weekly') return $p['key'] === $weekNumber;
            if ($payPeriod === 'daily')  return $p['key'] === $day;
            return $p['type'] === 'month';
        });

        $netPay = $currentPeriod ? $currentPeriod['netAmount'] : $result['netPay'];
        $periodBase = $currentPeriod['base'] ?? $result['baseSalary'];
        $periodComm = $currentPeriod['commission'] ?? 0;
        $periodDed  = $currentPeriod['deduction'] ?? 0;

        if ($netPay <= 0) {
            return back()->with('error', __('Net pay is zero or negative.'));
        }

        $periodLabel = match($payPeriod) {
            'weekly' => __('Week :n', ['n' => $weekNumber]),
            'daily'  => Carbon::create($year, $month, $day)->format('d/m/Y'),
            default  => __('Monthly'),
        };

        DB::beginTransaction();
        try {
            $branchPayment = BranchPayment::create([
                'company_id'     => $this->company()->id,
                'branch_id'      => $employee->branch_id,
                'type'           => 'expense',
                'category'       => 'salary',
                'amount'         => $netPay,
                'currency'       => $currency,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes'          => __('Salary for :name — :period :month/:year', [
                    'name'   => $employee->localizedName(),
                    'period' => $periodLabel,
                    'month'  => $month,
                    'year'   => $year,
                ]),
                'paid_at' => now(),
            ]);

            PayrollPayment::create([
                'company_id'        => $this->company()->id,
                'employee_id'       => $employee->id,
                'branch_id'         => $employee->branch_id,
                'branch_payment_id' => $branchPayment->id,
                'month'             => $month,
                'year'              => $year,
                'week_number'       => $weekNumber,
                'day'               => $day,
                'pay_period'        => $payPeriod,
                'base_salary'       => $periodBase,
                'commissions'       => $periodComm,
                'deductions'        => $periodDed,
                'net_amount'        => $netPay,
                'currency'          => $currency,
                'payment_method'    => $data['payment_method'] ?? 'cash',
                'notes'             => $data['notes'] ?? null,
                'paid_at'           => now(),
            ]);

            DB::commit();

            Auditor::log("Paid salary for {$employee->localizedName()} — {$netPay} {$currency} ({$periodLabel} {$month}/{$year})", $employee);

            return back()->with('success', __('Salary paid successfully — :amount :currency', [
                'amount'   => number_format($netPay, 0),
                'currency' => $currency,
            ]));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Payment failed: :msg', ['msg' => $e->getMessage()]));
        }
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $company  = $this->company();
        $branchId = $request->input('branch_id');

        $query = $company->employees()
            ->with(['compensation', 'branch', 'serviceCommissions'])
            ->where('is_active', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $employees = $query->orderBy('name_en')->get();
        $rows = $employees->map(fn($emp) => $this->calcPayroll($emp, $start, $end));

        $filename = "payroll-{$year}-{$month}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                __('Employee'), __('Branch'), __('Base Salary'),
                __('Commissions'), __('Deductions'), __('Net Pay'), __('Currency'),
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['employee']->localizedName(),
                    $row['employee']->branch->localizedName(),
                    $row['baseSalary'],
                    $row['commInSalaryCurrency'],
                    $row['totalDeducted'],
                    $row['netPay'],
                    $row['salaryCurrency'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function weeksInMonth(int $year, int $month): int
    {
        $days = Carbon::create($year, $month, 1)->daysInMonth;
        return (int) ceil($days / 7);
    }

    private function workingDaysInMonth(Employee $employee, int $year, int $month): int
    {
        $workingHours = $employee->workingHours()->get()->keyBy('day_of_week');
        $cursor = Carbon::create($year, $month, 1);
        $end    = $cursor->copy()->endOfMonth();
        $count  = 0;

        while ($cursor->lte($end)) {
            $wh = $workingHours->get($cursor->dayOfWeek);
            if ($wh && $wh->is_working) $count++;
            $cursor->addDay();
        }

        return $count ?: (int) Carbon::create($year, $month, 1)->daysInMonth;
    }

    private function calcPayroll(Employee $employee, Carbon $start, Carbon $end): array
    {
        $compensation = $employee->compensation;

        $appointments = $employee->appointments()
            ->whereBetween('start_time', [$start, $end])
            ->where('status', 'completed')
            ->with(['service', 'customer'])
            ->orderBy('start_time')
            ->get();

        $serviceRates = [];
        if ($compensation && $compensation->commission_type === 'per_service') {
            $serviceRates = $employee->serviceCommissions()
                ->pluck('rate', 'service_id')
                ->toArray();
        }

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

        $basePerPeriod = 0;
        if ($compensation && in_array($compensation->type, ['salary', 'mixed'])) {
            $basePerPeriod = (float) ($compensation->base_amount ?? 0);
        }

        $payPeriod = $compensation?->pay_period ?? 'monthly';
        $periodsInMonth = match($payPeriod) {
            'weekly' => $this->weeksInMonth($start->year, $start->month),
            'daily'  => $this->workingDaysInMonth($employee, $start->year, $start->month),
            default  => 1,
        };
        $baseSalary = round($basePerPeriod * $periodsInMonth, 2);

        $deductions = $employee->deductions()
            ->whereBetween('deduction_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_sick_leave', false)
            ->orderBy('deduction_date')
            ->get();

        $commissionsByCurrency = $appointments
            ->filter(fn($a) => $a->commission_earned > 0)
            ->groupBy('commission_currency')
            ->map(fn($group) => round($group->sum('commission_earned'), 2));

        $commInSalaryCurrency = (float) ($commissionsByCurrency[$salaryCurrency] ?? 0);

        $otherCommissions = $commissionsByCurrency->filter(
            fn($amount, $currency) => $currency !== $salaryCurrency && $amount > 0
        );

        $deductionsByCurrency = $deductions
            ->groupBy(fn($d) => $d->currency ?: $salaryCurrency)
            ->map(fn($group) => round($group->sum('amount'), 2));

        $deductedInSalaryCurrency = (float) ($deductionsByCurrency[$salaryCurrency] ?? 0);

        $otherDeductions = $deductionsByCurrency->filter(
            fn($amount, $currency) => $currency !== $salaryCurrency && $amount > 0
        );

        $totalDeducted = $deductedInSalaryCurrency;
        $grossPay      = round($baseSalary + $commInSalaryCurrency, 2);
        $netPay        = round($grossPay - $deductedInSalaryCurrency, 2);

        return compact(
            'employee', 'compensation', 'appointments', 'deductions',
            'salaryCurrency', 'baseSalary', 'basePerPeriod', 'periodsInMonth',
            'commissionsByCurrency', 'commInSalaryCurrency', 'otherCommissions',
            'deductionsByCurrency', 'deductedInSalaryCurrency', 'otherDeductions',
            'totalDeducted', 'grossPay', 'netPay'
        );
    }
}
