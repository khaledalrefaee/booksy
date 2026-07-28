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
use Illuminate\Support\Facades\Log;
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

    /** Branch to book the salary expense on. All-branches employees (branch_id NULL) fall back to the first branch. */
    private function paymentBranchId(Employee $employee): ?int
    {
        return $employee->branch_id
            ?? $this->company()->branches()->orderBy('sort_order')->value('id');
    }

    public function index(Request $request): View
    {
        [$month, $year] = $this->safeMonthYear($request);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();

        // Explicit param wins (empty string = "all branches"); otherwise use the
        // branch remembered from the other Team pages.
        if ($request->has('branch_id')) {
            $branchId = $request->input('branch_id') ?: null;
            session(['team.branch_id' => $branchId]);
        } else {
            $branchId = session('team.branch_id');
            if ($branchId && ! $branches->contains('id', $branchId)) {
                $branchId = null;
            }
        }

        // Active staff + anyone offboarded during this month (final settlement)
        $query = $company->employees()
            ->with(['compensation', 'branch', 'serviceCommissions'])
            ->where(fn ($q) => $q->where('is_active', true)
                ->orWhereBetween('termination_date', [$start->toDateString(), $end->toDateString()]));

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

        [$month, $year] = $this->safeMonthYear($request);

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

        // Offboarded: periods after the last working day are not payable
        $termDate = $result['employee']->termination_date;

        if ($payPeriod === 'weekly') {
            $cursor = $monthStart->copy();
            $week   = 1;
            while ($cursor->lte($monthEnd) && $week <= 5) {
                if ($termDate && $cursor->gt($termDate)) break;
                $weekStart = $cursor->copy();
                $weekEnd   = $cursor->copy()->addDays(6);
                if ($weekEnd->gt($monthEnd)) $weekEnd = $monthEnd->copy();

                // Terminated inside this week → base prorated by worked days
                $weekBase   = $basePerPeriod;
                $daysWorked = null;
                if ($termDate && $termDate->betweenIncluded($weekStart, $weekEnd)) {
                    $daysWorked = (int) $weekStart->diffInDays($termDate) + 1;
                    $weekBase   = round($basePerPeriod * $daysWorked / 7, 2);
                }

                $weekAppts = $appointments->filter(fn($a) => $a->start_time->between($weekStart, $weekEnd->endOfDay()));
                $weekCommByCurrency = $weekAppts->filter(fn($a) => $a->commission_earned > 0)
                    ->groupBy('commission_currency')
                    ->map(fn($g) => round($g->sum('commission_earned'), 2));
                $weekCommSalary = (float) ($weekCommByCurrency[$salaryCurrency] ?? 0);
                $weekDed   = $deductions->filter(fn($d) => $d->deduction_date->between($weekStart, $weekEnd) && ($d->currency ?: $salaryCurrency) === $salaryCurrency)->sum('amount');
                $weekNet   = round($weekBase + $weekCommSalary - $weekDed, 2);

                $paid = $payments->firstWhere('week_number', $week);
                $periods[] = [
                    'label'      => __('Week :n', ['n' => $week]) . ' (' . $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m') . ')'
                                    . ($daysWorked !== null ? ' · ' . __(':worked/:total day(s) worked', ['worked' => $daysWorked, 'total' => 7]) : ''),
                    'key'        => $week,
                    'type'       => 'week',
                    'paid'       => (bool) $paid,
                    'paidAt'     => $paid?->paid_at,
                    'paidMethod' => $paid?->payment_method,
                    'paidNotes'  => $paid?->notes,
                    'amount'     => $paid?->net_amount ?? $weekNet,
                    'base'       => $weekBase,
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
                if ($termDate && $cursor->gt($termDate)) break;
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
                    'paidMethod' => $paid?->payment_method,
                    'paidNotes'  => $paid?->notes,
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
                'paidMethod' => $paid?->payment_method,
                'paidNotes'  => $paid?->notes,
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

    /**
     * Pay Run: pay every eligible employee for the month in one action.
     * Eligible = active, monthly pay period, positive net pay, and no payment
     * recorded for this month yet. Weekly/daily employees keep individual flow.
     */
    public function payAll(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month'          => ['required', 'integer', 'min:1', 'max:12'],
            'year'           => ['required', 'integer', 'min:2020', 'max:2100'],
            'branch_id'      => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer'],
        ]);

        $company = $this->company();
        $month   = (int) $data['month'];
        $year    = (int) $data['year'];
        $start   = Carbon::create($year, $month, 1)->startOfDay();
        $end     = $start->copy()->endOfMonth()->endOfDay();

        $query = $company->employees()
            ->with(['compensation', 'branch', 'serviceCommissions'])
            ->where(fn ($q) => $q->where('is_active', true)
                ->orWhereBetween('termination_date', [$start->toDateString(), $end->toDateString()]));
        if (! empty($data['branch_id'])) {
            $query->where('branch_id', $data['branch_id']);
        }
        $employees = $query->get();

        $alreadyPaid = PayrollPayment::where('company_id', $company->id)
            ->where('month', $month)->where('year', $year)
            ->pluck('employee_id')
            ->flip();

        $paidCount = 0;
        $skipped   = [];
        $whatsapp  = app(\App\Services\WhatsappService::class);

        foreach ($employees as $employee) {
            if ($alreadyPaid->has($employee->id)) {
                continue; // has at least one payment this month — handled individually
            }

            $payPeriod = $employee->compensation?->pay_period ?? 'monthly';
            if ($payPeriod !== 'monthly') {
                $skipped[] = $employee->localizedName() . ' (' . __($payPeriod) . ')';
                continue;
            }

            $result = $this->calcPayroll($employee, $start, $end);
            $netPay = $result['netPay'];
            if ($netPay <= 0) {
                continue;
            }

            $currency = $result['salaryCurrency'];

            DB::beginTransaction();
            try {
                $branchPayment = BranchPayment::create([
                    'company_id'     => $company->id,
                    'branch_id'      => $this->paymentBranchId($employee),
                    'type'           => 'expense',
                    'category'       => 'salary',
                    'amount'         => $netPay,
                    'currency'       => $currency,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'notes'          => __('Salary for :name — :period :month/:year', [
                        'name' => $employee->localizedName(), 'period' => __('Monthly'),
                        'month' => $month, 'year' => $year,
                    ]) . ' (' . __('Pay Run') . ')',
                    'paid_at' => now(),
                ]);

                PayrollPayment::create([
                    'company_id'        => $company->id,
                    'employee_id'       => $employee->id,
                    'branch_id'         => $this->paymentBranchId($employee),
                    'branch_payment_id' => $branchPayment->id,
                    'month'             => $month,
                    'year'              => $year,
                    'pay_period'        => 'monthly',
                    'base_salary'       => $result['baseSalary'],
                    'commissions'       => $result['commInSalaryCurrency'],
                    'deductions'        => $result['deductedInSalaryCurrency'],
                    'net_amount'        => $netPay,
                    'currency'          => $currency,
                    'payment_method'    => $data['payment_method'] ?? 'cash',
                    'notes'             => __('Pay Run'),
                    'paid_at'           => now(),
                ]);

                DB::commit();
                $paidCount++;

                Auditor::log("Pay Run: paid salary for {$employee->localizedName()} — {$netPay} {$currency} ({$month}/{$year})", $employee);

                try {
                    $whatsapp->sendPayslip($employee, [
                        'period'      => Carbon::create($year, $month, 1)->translatedFormat('F Y'),
                        'base'        => (float) $result['baseSalary'],
                        'commissions' => (float) $result['commInSalaryCurrency'],
                        'deductions'  => (float) $result['deductedInSalaryCurrency'],
                        'net'         => (float) $netPay,
                        'currency'    => config("booksy.currencies.{$currency}.symbol", $currency),
                        'method'      => __(BranchPayment::PAYMENT_METHODS[$data['payment_method'] ?? 'cash']['label_key'] ?? 'cash'),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Pay Run payslip WhatsApp failed for employee {$employee->id}: {$e->getMessage()}");
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $skipped[] = $employee->localizedName() . ' (' . $e->getMessage() . ')';
            }
        }

        $msg = __(':count salaries paid successfully.', ['count' => $paidCount]);
        $redirect = back()->with('success', $msg);
        if ($skipped) {
            $redirect->with('warning', __('Skipped: :names', ['names' => implode('، ', array_slice($skipped, 0, 5))])
                . (count($skipped) > 5 ? ' +' . (count($skipped) - 5) : ''));
        }

        return $redirect;
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
            'amount'         => ['nullable', 'numeric', 'min:0.01'],
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

        $computedNet = $currentPeriod ? $currentPeriod['netAmount'] : $result['netPay'];
        $periodBase  = $currentPeriod['base'] ?? $result['baseSalary'];
        $periodComm  = $currentPeriod['commission'] ?? 0;
        $periodDed   = $currentPeriod['deduction'] ?? 0;

        // Manager can override the amount (full week, severance bonus, rounding…)
        $netPay     = isset($data['amount']) ? round((float) $data['amount'], 2) : $computedNet;
        $isOverride = abs($netPay - $computedNet) >= 0.01;
        if ($isOverride) {
            $overrideNote = __('Adjusted manually — computed: :amount', ['amount' => number_format($computedNet, 2)]);
            $data['notes'] = trim(($data['notes'] ?? '') . ' · ' . $overrideNote, ' ·');
        }

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
                'branch_id'      => $this->paymentBranchId($employee),
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
                'branch_id'         => $this->paymentBranchId($employee),
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

            // WhatsApp payslip — must never break the payment itself
            try {
                $methodMeta = BranchPayment::PAYMENT_METHODS[$data['payment_method'] ?? 'cash'] ?? null;
                app(\App\Services\WhatsappService::class)->sendPayslip($employee, [
                    'period'      => $periodLabel . ' — ' . Carbon::create($year, $month, 1)->translatedFormat('F Y'),
                    'base'        => (float) $periodBase,
                    'commissions' => (float) $periodComm,
                    'deductions'  => (float) $periodDed,
                    'net'         => (float) $netPay,
                    'currency'    => config("booksy.currencies.{$currency}.symbol", $currency),
                    'method'      => $methodMeta ? __($methodMeta['label_key']) : ($data['payment_method'] ?? 'cash'),
                ]);
            } catch (\Throwable $e) {
                Log::warning("Payslip WhatsApp failed: {$e->getMessage()}");
            }

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
        [$month, $year] = $this->safeMonthYear($request);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $company  = $this->company();
        $branchId = $request->input('branch_id');

        $query = $company->employees()
            ->with(['compensation', 'branch', 'serviceCommissions'])
            ->where(fn ($q) => $q->where('is_active', true)
                ->orWhereBetween('termination_date', [$start->toDateString(), $end->toDateString()]));

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
                    $row['employee']->branch?->localizedName() ?? __('All branches'),
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

    private function weeksInMonth(int $year, int $month, ?Carbon $until = null): int
    {
        $days = Carbon::create($year, $month, 1)->daysInMonth;
        if ($until && $until->year === $year && $until->month === $month) {
            $days = min($days, $until->day);
        }
        return (int) ceil($days / 7);
    }

    private function workingDaysInMonth(Employee $employee, int $year, int $month, ?Carbon $until = null): int
    {
        $workingHours = $employee->workingHours()->get()->keyBy('day_of_week');
        $cursor = Carbon::create($year, $month, 1);
        $end    = $cursor->copy()->endOfMonth();
        if ($until && $until->lt($end)) {
            $end = $until->copy()->endOfDay();
        }
        $count  = 0;

        // Unpaid public holidays don't count as working days for daily-paid staff
        $holidayDates = \App\Models\CompanyHoliday::where('company_id', $employee->company_id)
            ->where('is_paid', false)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $cursor->toDateString())
            ->get()
            ->flatMap(function ($h) use ($cursor, $end) {
                $dates = [];
                $s = $h->start_date->greaterThan($cursor) ? $h->start_date->copy() : $cursor->copy();
                $e = $h->end_date->lessThan($end) ? $h->end_date->copy() : $end->copy();
                for ($d = $s->copy(); $d->lte($e); $d->addDay()) $dates[] = $d->toDateString();
                return $dates;
            })
            ->flip();

        while ($cursor->lte($end)) {
            $wh = $workingHours->get($cursor->dayOfWeek);
            if ($wh && $wh->is_working && ! $holidayDates->has($cursor->toDateString())) $count++;
            $cursor->addDay();
        }

        $fallback = (int) Carbon::create($year, $month, 1)->daysInMonth;
        if ($until && $until->year === $year && $until->month === $month) {
            $fallback = min($fallback, $until->day);
        }

        return $count ?: $fallback;
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

        // Offboarded employees: the payable base stops at the last working day
        $termDate        = $employee->termination_date;
        $termBeforeMonth = $termDate && $termDate->lt($start);
        $termInMonth     = $termDate && ! $termBeforeMonth && $termDate->lte($end);
        $capDate         = $termInMonth ? $termDate : null;

        $periodsInMonth = match($payPeriod) {
            // Terminated mid-week → the last week is prorated by worked days (e.g. 6/7)
            'weekly' => $capDate
                ? round(intdiv($capDate->day - 1, 7) + ((($capDate->day - 1) % 7) + 1) / 7, 6)
                : $this->weeksInMonth($start->year, $start->month),
            'daily'  => $this->workingDaysInMonth($employee, $start->year, $start->month, $capDate),
            default  => 1,
        };
        if ($termBeforeMonth) {
            $periodsInMonth = 0;
        }

        $baseSalary = round($basePerPeriod * $periodsInMonth, 2);

        // Monthly salary is prorated by calendar days up to the last working day
        if ($payPeriod === 'monthly' && $termInMonth) {
            $daysInMonth = $start->daysInMonth;
            $baseSalary  = round($basePerPeriod * min($termDate->day, $daysInMonth) / $daysInMonth, 2);
        }

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

        // Tips collected on completed appointments in the period
        $tipsByCurrency = $appointments
            ->filter(fn($a) => (float) ($a->tip_amount ?? 0) > 0)
            ->groupBy('commission_currency')
            ->map(fn($group) => round($group->sum('tip_amount'), 2));

        $tipsInSalaryCurrency = (float) ($tipsByCurrency[$salaryCurrency] ?? 0);
        $otherTips = $tipsByCurrency->filter(
            fn($amount, $currency) => $currency !== $salaryCurrency && $amount > 0
        );

        // Product sales commission: sales attributed to this employee in the cash register
        $productCommissionRate = (float) ($compensation?->product_commission_rate ?? 0);
        $productCommByCurrency = collect();
        if ($productCommissionRate > 0) {
            $productCommByCurrency = BranchPayment::where('company_id', $employee->company_id)
                ->where('type', 'income')
                ->where('category', 'product_sale')
                ->where('recorded_by_employee_id', $employee->id)
                ->whereBetween('paid_at', [$start, $end])
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->map(fn($total) => round($total * $productCommissionRate / 100, 2))
                ->filter(fn($amount) => $amount > 0);
        }

        $productCommInSalaryCurrency = (float) ($productCommByCurrency[$salaryCurrency] ?? 0);
        $otherProductComm = $productCommByCurrency->filter(
            fn($amount, $currency) => $currency !== $salaryCurrency && $amount > 0
        );

        // Overtime pay: recorded overtime minutes × the hourly overtime rate
        $overtimeRate    = (float) ($compensation?->overtime_hourly_rate ?? 0);
        $overtimeMinutes = 0;
        $overtimePay     = 0.0;
        if ($overtimeRate > 0) {
            $overtimeMinutes = (int) $employee->attendanceRecords()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->sum('overtime_minutes');
            $overtimePay = round(($overtimeMinutes / 60) * $overtimeRate, 2);
        }

        $totalDeducted = $deductedInSalaryCurrency;
        $grossPay      = round($baseSalary + $commInSalaryCurrency + $tipsInSalaryCurrency + $productCommInSalaryCurrency + $overtimePay, 2);
        $netPay        = round($grossPay - $deductedInSalaryCurrency, 2);

        return compact(
            'employee', 'compensation', 'appointments', 'deductions',
            'salaryCurrency', 'baseSalary', 'basePerPeriod', 'periodsInMonth',
            'commissionsByCurrency', 'commInSalaryCurrency', 'otherCommissions',
            'deductionsByCurrency', 'deductedInSalaryCurrency', 'otherDeductions',
            'tipsByCurrency', 'tipsInSalaryCurrency', 'otherTips',
            'productCommByCurrency', 'productCommInSalaryCurrency', 'otherProductComm', 'productCommissionRate',
            'overtimeMinutes', 'overtimePay', 'overtimeRate',
            'totalDeducted', 'grossPay', 'netPay'
        );
    }
}
