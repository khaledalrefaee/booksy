<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\RecurringExpense;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $monthlyTotal = $expenses->where('is_active', true)->where('frequency', 'monthly')->sum('amount');

        return view('company.recurring-expenses.index', compact('expenses', 'branches', 'monthlyTotal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'category' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'frequency' => ['required', 'in:' . implode(',', RecurringExpense::FREQUENCIES)],
            'next_due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $this->company();
        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        RecurringExpense::create([
            'company_id' => $company->id,
            ...$data,
        ]);

        return back()->with('success', __('Recurring expense created.'));
    }

    public function toggle(RecurringExpense $recurringExpense): RedirectResponse
    {
        abort_unless($recurringExpense->company_id === $this->company()->id, 403);

        $recurringExpense->update(['is_active' => !$recurringExpense->is_active]);

        return back()->with('success', $recurringExpense->is_active ? __('Activate') : __('Deactivate'));
    }

    public function destroy(RecurringExpense $recurringExpense): RedirectResponse
    {
        abort_unless($recurringExpense->company_id === $this->company()->id, 403);

        $recurringExpense->delete();

        return back()->with('success', __('Recurring expense deleted.'));
    }
}
