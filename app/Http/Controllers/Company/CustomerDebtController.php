<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BranchPayment;
use App\Models\CustomerDebt;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerDebtController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $query = CustomerDebt::where('company_id', $company->id)
            ->with(['customer', 'branch', 'appointment'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->whereHas('customer', fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
            );
        }

        $debts = $query->paginate(20)->withQueryString();
        $branches = $company->branches()->orderBy('sort_order')->get();

        $totals = CustomerDebt::where('company_id', $company->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->select('currency')
            ->selectRaw('SUM(original_amount - paid_amount) as total_remaining')
            ->groupBy('currency')
            ->pluck('total_remaining', 'currency');

        return view('company.debts.index', compact('debts', 'branches', 'totals'));
    }

    public function show(CustomerDebt $debt): View
    {
        abort_unless($debt->company_id === $this->company()->id, 403);

        $debt->load(['customer', 'branch', 'appointment', 'invoice', 'payments']);

        return view('company.debts.show', compact('debt'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:500'],
            'due_date' => ['nullable', 'date'],
        ]);

        $company = $this->company();
        abort_unless($company->branches()->where('id', $data['branch_id'])->exists(), 403);

        $debt = CustomerDebt::create([
            'company_id' => $company->id,
            ...$data,
            'status' => 'unpaid',
            'created_by_name' => $company->localizedName(),
        ]);

        Auditor::log("Created debt of {$debt->original_amount} {$debt->currency} for customer", $debt);

        return back()->with('success', __('Debt created.'));
    }

    public function recordPayment(Request $request, CustomerDebt $debt): RedirectResponse
    {
        abort_unless($debt->company_id === $this->company()->id, 403);
        abort_if($debt->isPaid() || $debt->status === 'waived', 422);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $debt->remaining],
            'payment_method' => ['required', 'in:cash,card,bank_transfer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $this->company();

        DB::transaction(function () use ($debt, $data, $company) {
            $debt->payments()->create([
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'recorded_by_name' => $company->localizedName(),
            ]);

            $debt->recalculate();

            // Record as income in cash register
            BranchPayment::create([
                'company_id' => $company->id,
                'branch_id' => $debt->branch_id,
                'appointment_id' => $debt->appointment_id,
                'type' => 'income',
                'category' => 'appointment',
                'amount' => $data['amount'],
                'currency' => $debt->currency,
                'payment_method' => $data['payment_method'],
                'notes' => __('Customer owes :amount', ['amount' => $debt->customer?->name ?? '']),
                'paid_at' => now(),
            ]);
        });

        Auditor::log("Recorded debt payment of {$data['amount']} {$debt->currency}", $debt);

        return back()->with('success', __('Payment recorded.'));
    }

    public function waive(CustomerDebt $debt): RedirectResponse
    {
        abort_unless($debt->company_id === $this->company()->id, 403);
        abort_if($debt->isPaid(), 422);

        $debt->update(['status' => 'waived']);

        Auditor::log("Waived debt of {$debt->original_amount} {$debt->currency}", $debt);

        return back()->with('success', __('Debt waived.'));
    }
}
