<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BranchPayment;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Support\Auditor;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdvanceController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $advances = EmployeeAdvance::where('company_id', $company->id)
            ->with(['employee.branch', 'installments'])
            ->orderByDesc('advance_date')
            ->paginate(15)
            ->withQueryString();

        // Outstanding balance per currency across all advances (not just this page)
        $outstanding = EmployeeAdvance::where('company_id', $company->id)
            ->with('installments')
            ->get()
            ->groupBy('currency')
            ->map(fn ($group) => round($group->sum(fn ($a) => $a->remainingAmount()), 2))
            ->filter(fn ($amount) => $amount > 0);

        $employees = $company->employees()
            ->with('compensation')
            ->where('is_active', true)
            ->orderBy('name_en')
            ->get();

        $preselectedEmployeeId = $request->integer('employee_id') ?: null;

        return view('company.advances.index', compact(
            'advances', 'outstanding', 'employees', 'preselectedEmployeeId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'employee_id'        => ['required', 'integer'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'currency'           => ['required', 'in:' . implode(',', array_keys(config('booksy.currencies', [])))],
            'advance_date'       => ['required', 'date'],
            'installments_count' => ['required', 'integer', 'min:1', 'max:24'],
            'first_installment'  => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'payment_method'     => ['required', 'in:cash,card,bank_transfer'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::where('company_id', $company->id)->findOrFail($data['employee_id']);

        $amount       = round((float) $data['amount'], 2);
        $count        = (int) $data['installments_count'];
        $installment  = round($amount / $count, 2);
        $firstMonth   = Carbon::parse($data['first_installment'] . '-01');

        DB::beginTransaction();
        try {
            // Money physically leaves the branch cash box
            $branchPayment = BranchPayment::create([
                'company_id'     => $company->id,
                'branch_id'      => $employee->branch_id ?? $company->branches()->orderBy('sort_order')->value('id'),
                'type'           => 'expense',
                'category'       => 'advance',
                'amount'         => $amount,
                'currency'       => $data['currency'],
                'payment_method' => $data['payment_method'],
                'notes'          => __('Salary advance for :name (:count installments)', [
                    'name' => $employee->localizedName(), 'count' => $count,
                ]),
                'paid_at'        => Carbon::parse($data['advance_date']),
            ]);

            $advance = EmployeeAdvance::create([
                'company_id'         => $company->id,
                'employee_id'        => $employee->id,
                'branch_payment_id'  => $branchPayment->id,
                'amount'             => $amount,
                'currency'           => $data['currency'],
                'advance_date'       => $data['advance_date'],
                'installments_count' => $count,
                'installment_amount' => $installment,
                'payment_method'     => $data['payment_method'],
                'notes'              => $data['notes'] ?? null,
            ]);

            // Future installments land in payroll automatically as deductions.
            // The last installment absorbs the rounding remainder.
            $allocated = 0.0;
            for ($i = 1; $i <= $count; $i++) {
                $thisAmount = $i === $count ? round($amount - $allocated, 2) : $installment;
                $allocated  = round($allocated + $thisAmount, 2);

                $employee->deductions()->create([
                    'advance_id'     => $advance->id,
                    'type'           => 'advance',
                    'is_sick_leave'  => false,
                    'deduction_date' => $firstMonth->copy()->addMonths($i - 1)->toDateString(),
                    'amount'         => $thisAmount,
                    'currency'       => $data['currency'],
                    'notes'          => __('Advance installment :i/:n — advance of :date', [
                        'i' => $i, 'n' => $count,
                        'date' => Carbon::parse($data['advance_date'])->format('d/m/Y'),
                    ]),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Payment failed: :msg', ['msg' => $e->getMessage()]))->withInput();
        }

        Auditor::log(
            "Granted salary advance to {$employee->localizedName()} — {$amount} {$data['currency']} over {$count} installment(s)",
            $advance
        );

        // back() keeps the user on whichever page they granted from (payroll or advances)
        return back()
            ->with('success', __('Advance granted — :amount :currency, deducted as :count monthly installment(s).', [
                'amount'   => number_format($amount, 0),
                'currency' => config("booksy.currencies.{$data['currency']}.symbol", $data['currency']),
                'count'    => $count,
            ]));
    }

    public function destroy(Request $request, EmployeeAdvance $advance): RedirectResponse
    {
        abort_unless($advance->company_id === $this->company()->id, 403);

        // Optional: also remove the cash-box expense (e.g. advance entered by mistake).
        $deleteExpense = $request->boolean('delete_expense');

        Auditor::log(
            "Deleted salary advance of {$advance->employee->localizedName()} — {$advance->amount} {$advance->currency}"
            . ($deleteExpense ? ' (cash record removed)' : ' (cash record kept)'),
            $advance
        );

        if ($deleteExpense) {
            $advance->branchPayment?->delete();
        }

        // Installment deductions cascade-delete with the advance
        $advance->delete();

        return back()->with('success', $deleteExpense
            ? __('Advance deleted completely — installments and cash record removed.')
            : __('Advance deleted with its remaining installments. The cash expense record is kept.'));
    }
}
