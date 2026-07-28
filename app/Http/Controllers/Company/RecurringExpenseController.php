<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BranchPayment;
use App\Models\RecurringExpense;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecurringExpenseController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    public function index(): View
    {
        $company = $this->company();

        $expenses = RecurringExpense::where('company_id', $company->id)
            ->with('branch')
            ->orderBy('next_due_date')
            ->get();

        $branches = $company->branches()->orderBy('sort_order')->get();
        $currencies = config('booksy.currencies', []);

        $monthlyTotal = $expenses->where('is_active', true)->where('frequency', 'monthly')->sum('amount');

        return view('company.recurring-expenses.index', compact('expenses', 'branches', 'currencies', 'monthlyTotal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company();

        $expenseCategories = collect(BranchPayment::CATEGORIES)->where('type', 'expense')->keys()->all();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'category' => ['required', 'string', 'in:' . implode(',', $expenseCategories)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'in:' . implode(',', array_keys(config('booksy.currencies', [])))],
            'frequency' => ['required', 'in:' . implode(',', RecurringExpense::FREQUENCIES)],
            'next_due_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $expense = RecurringExpense::create([
            'company_id' => $company->id,
            ...$data,
        ]);

        Auditor::log("Created recurring expense: {$expense->title} — {$expense->amount} {$expense->currency} ({$expense->frequency})", $expense);

        return back()->with('success', __('Recurring expense created.'));
    }

    public function toggle(RecurringExpense $recurringExpense): RedirectResponse
    {
        abort_unless($recurringExpense->company_id === $this->company()->id, 403);

        $recurringExpense->update(['is_active' => !$recurringExpense->is_active]);

        Auditor::log(
            ($recurringExpense->is_active ? 'Activated' : 'Deactivated') . " recurring expense: {$recurringExpense->title}",
            $recurringExpense
        );

        return back()->with('success', $recurringExpense->is_active ? __('Activate') : __('Deactivate'));
    }

    public function destroy(RecurringExpense $recurringExpense): RedirectResponse
    {
        abort_unless($recurringExpense->company_id === $this->company()->id, 403);

        Auditor::log("Deleted recurring expense: {$recurringExpense->title} — {$recurringExpense->amount} {$recurringExpense->currency}", $recurringExpense);
        $recurringExpense->delete();

        return back()->with('success', __('Recurring expense deleted.'));
    }
}
