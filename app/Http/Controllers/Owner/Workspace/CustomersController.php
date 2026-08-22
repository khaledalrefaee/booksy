<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Company Workspace — Customers tab (customers + debts).
 * Scopes customers to those with appointments in the company's branches
 * (mirrors App\Http\Controllers\Company\CustomerController).
 */
class CustomersController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        $branchIds = $company->branches()->pluck('id');
        $branchScope = fn ($q) => $q->whereIn('branch_id', $branchIds);
        $q = $this->searchTerm(request());

        $customers = Customer::query()
            ->whereHas('appointments', $branchScope)
            ->withCount(['appointments as total_visits' => $branchScope])
            ->withSum(['appointments as total_spent' => fn ($qq) => $qq->whereIn('branch_id', $branchIds)->where('status', 'completed')], 'total_price')
            ->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->orderByDesc('total_visits')
            ->limit(100)
            ->get();

        $debts = CustomerDebt::query()
            ->where('company_id', $company->id)
            ->with(['customer', 'branch'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->latest('id')
            ->limit(60)
            ->get();

        return $this->tab('customers', $company, compact('customers', 'debts', 'q'));
    }

    public function profile(Company $company, Customer $customer)
    {
        $branchIds = $company->branches()->pluck('id');

        $recent = $customer->appointments()
            ->whereIn('branch_id', $branchIds)
            ->with(['branch', 'service'])
            ->orderByDesc('start_time')
            ->limit(10)
            ->get();

        $totalSpent = $customer->appointments()
            ->whereIn('branch_id', $branchIds)->where('status', 'completed')->sum('total_price');

        return $this->drawer('customer-profile', $company, compact('customer', 'recent', 'totalSpent'));
    }

    public function toggleBan(Company $company, Customer $customer, Request $request)
    {
        abort_unless(Gate::allows('owner-can', 'operations.view'), 403);

        if ($customer->is_banned) {
            $customer->update(['is_banned' => false, 'ban_reason' => null, 'banned_at' => null]);
            OwnerAudit::record('company.customer.unban', $customer, new: ['is_banned' => false]);

            return $this->actionOk(__('Customer unbanned.'));
        }

        $validated = $request->validate(['ban_reason' => ['required', 'string', 'max:255']]);
        $customer->update(['is_banned' => true, 'ban_reason' => $validated['ban_reason'], 'banned_at' => now()]);
        OwnerAudit::record('company.customer.ban', $customer, new: ['is_banned' => true], reason: $validated['ban_reason']);

        return $this->actionOk(__('Customer banned.'));
    }

    public function waiveDebt(Company $company, CustomerDebt $debt)
    {
        abort_unless($debt->company_id === $company->id, 404);
        abort_unless(Gate::allows('owner-can', 'finance.manage'), 403);
        abort_if($debt->isPaid() || $debt->status === 'waived', 422);

        $debt->update(['status' => 'waived']);
        OwnerAudit::record('company.debt.waive', $debt, new: ['status' => 'waived']);

        return $this->actionOk(__('Debt waived.'));
    }
}
