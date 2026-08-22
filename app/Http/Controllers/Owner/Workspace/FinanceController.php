<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\BranchPayment;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\RecurringExpense;
use Illuminate\Support\Carbon;

/**
 * Company Workspace — Finance tab (cash register + invoices + recurring expenses).
 * Monitoring view; cash/invoice mutations that move money defer to the full editor.
 */
class FinanceController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        if (! $company->hasFeature('finance')) {
            return $this->tab('finance', $company, ['locked' => true]);
        }

        $tz        = config('app.timezone');
        $start     = Carbon::now($tz)->startOfMonth();
        $branchIds = $company->branches()->pluck('id');

        $monthPayments = BranchPayment::query()->whereIn('branch_id', $branchIds)->where('created_at', '>=', $start);
        $income  = (float) (clone $monthPayments)->whereIn('type', ['income', 'tip', 'other_income'])->sum('amount');
        $expense = (float) (clone $monthPayments)->whereIn('type', ['expense', 'refund'])->sum('amount');

        $cashEntries = BranchPayment::query()
            ->whereIn('branch_id', $branchIds)
            ->with('branch')
            ->latest('created_at')
            ->limit(40)
            ->get();

        $invoices = Invoice::query()
            ->where('company_id', $company->id)
            ->with('branch')
            ->latest('issued_at')->latest('id')
            ->limit(50)
            ->get();

        $expenses = RecurringExpense::query()
            ->where('company_id', $company->id)
            ->with('branch')
            ->orderBy('next_due_date')
            ->get();

        return $this->tab('finance', $company, [
            'locked'      => false,
            'income'      => $income,
            'expense'     => $expense,
            'cashEntries' => $cashEntries,
            'invoices'    => $invoices,
            'expenses'    => $expenses,
        ]);
    }
}
