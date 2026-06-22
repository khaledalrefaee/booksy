<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchPayment;
use App\Models\CashDrawerSession;
use App\Support\Auditor;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CashController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    public function index(Branch $branch, Request $request): View
    {
        $this->authoriseBranch($branch);

        // ── Period ───────────────────────────────────────────────────────────
        $period    = $request->get('period', 'month');
        $customFrom = $request->get('from');
        $customTo   = $request->get('to');

        [$from, $to] = match ($period) {
            'today'      => [now()->startOfDay(),  now()->endOfDay()],
            'week'       => [now()->startOfWeek(), now()->endOfWeek()],
            'year'       => [now()->startOfYear(), now()->endOfYear()],
            'custom'     => [
                Carbon::parse($customFrom)->startOfDay(),
                Carbon::parse($customTo)->endOfDay(),
            ],
            default      => [now()->startOfMonth(), now()->endOfMonth()], // month
        };

        // ── Transactions (full set for aggregations) ─────────────────────────
        $baseQuery = BranchPayment::where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$from, $to]);

        $transactions = (clone $baseQuery)
            ->with(['appointment.customer', 'recordedBy'])
            ->orderByDesc('paid_at')
            ->get();

        // ── Paginated for display ────────────────────────────────────────────
        $paginatedTx = (clone $baseQuery)
            ->with(['appointment.customer', 'recordedBy'])
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        // ── Totals per currency ───────────────────────────────────────────────
        $cats     = BranchPayment::CATEGORIES;
        $byGroup  = $transactions->groupBy('currency');

        $summary = $byGroup->map(function ($rows) use ($cats) {
            $income  = $rows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
            $expense = $rows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
            return [
                'income'  => round($income,  2),
                'expense' => round($expense, 2),
                'net'     => round($income - $expense, 2),
            ];
        });

        // ── Per-category totals (for breakdown) ───────────────────────────────
        $byCat = collect($cats)->mapWithKeys(function ($meta, $key) use ($transactions) {
            return [$key => $transactions->where('category', $key)->sum('amount')];
        });

        // ── Chart data — group by month for long periods, by day for short ──
        $totalDays = (int) $from->diffInDays($to) + 1;
        $chartData = [];

        if ($totalDays > 62) {
            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->endOfMonth();
            while ($cursor->lte($end)) {
                $mStart = $cursor->copy()->startOfMonth();
                $mEnd   = $cursor->copy()->endOfMonth();
                $monthTx = $transactions->filter(fn($r) => $r->paid_at->between($mStart, $mEnd));
                $inc = $monthTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
                $exp = $monthTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
                $chartData[] = ['date' => $cursor->format('Y-m-01'), 'income' => (float)$inc, 'expense' => (float)$exp, 'mode' => 'month'];
                $cursor->addMonth();
            }
        } else {
            for ($i = 0; $i < $totalDays; $i++) {
                $day  = $from->copy()->addDays($i)->toDateString();
                $dayTx = $transactions->filter(fn($r) => $r->paid_at->toDateString() === $day);
                $inc  = $dayTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
                $exp  = $dayTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
                $chartData[] = ['date' => $day, 'income' => (float)$inc, 'expense' => (float)$exp, 'mode' => 'day'];
            }
        }

        // ── Group paginated transactions by date for display ─────────────────
        $grouped = $paginatedTx->getCollection()->groupBy(fn($r) => $r->paid_at->toDateString());

        $currencies = config('booksy.currencies', []);
        $defaultCurrency = config('booksy.default_currency', 'SYP');

        $drawerSession = CashDrawerSession::where('branch_id', $branch->id)->open()->first();
        $recentDrawers = CashDrawerSession::where('branch_id', $branch->id)
            ->whereIn('status', ['closed', 'reconciled'])
            ->orderByDesc('closed_at')
            ->limit(5)
            ->get();

        // ── Archive months — months that have transactions ──────────────────
        $archiveMonths = BranchPayment::where('branch_id', $branch->id)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, COUNT(*) as cnt, MIN(paid_at) as first_at")
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->orderByDesc('ym')
            ->limit(24)
            ->get()
            ->map(fn($row) => [
                'ym'    => $row->ym,
                'label' => Carbon::parse($row->ym . '-01')->translatedFormat('F Y'),
                'count' => $row->cnt,
                'from'  => Carbon::parse($row->ym . '-01')->startOfMonth()->toDateString(),
                'to'    => Carbon::parse($row->ym . '-01')->endOfMonth()->toDateString(),
            ]);

        return view('company.cash.index', compact(
            'branch', 'transactions', 'paginatedTx', 'grouped', 'summary', 'byCat',
            'chartData', 'from', 'to', 'period', 'customFrom', 'customTo',
            'cats', 'currencies', 'defaultCurrency',
            'drawerSession', 'recentDrawers', 'archiveMonths'
        ));
    }

    public function globalIndex(Request $request): View
    {
        $company = $this->company();

        $period     = $request->get('period', 'month');
        $customFrom = $request->get('from');
        $customTo   = $request->get('to');
        $branchId   = $request->get('branch_id');

        [$from, $to] = match ($period) {
            'today'  => [now()->startOfDay(),  now()->endOfDay()],
            'week'   => [now()->startOfWeek(), now()->endOfWeek()],
            'year'   => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                Carbon::parse($customFrom)->startOfDay(),
                Carbon::parse($customTo)->endOfDay(),
            ],
            default  => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $branches = $company->branches()->orderBy('sort_order')->get();

        $baseQuery = BranchPayment::whereHas('branch', fn($q) => $q->where('company_id', $company->id))
            ->whereBetween('paid_at', [$from, $to]);

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        }

        $transactions = (clone $baseQuery)
            ->with(['branch', 'appointment.customer'])
            ->orderByDesc('paid_at')
            ->get();

        $paginatedTx = (clone $baseQuery)
            ->with(['branch', 'appointment.customer'])
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        $cats    = BranchPayment::CATEGORIES;
        $byGroup = $transactions->groupBy('currency');

        $summary = $byGroup->map(function ($rows) use ($cats) {
            $income  = $rows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
            $expense = $rows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
            return [
                'income'  => round($income,  2),
                'expense' => round($expense, 2),
                'net'     => round($income - $expense, 2),
            ];
        });

        // Per-branch balance cards
        $byBranch = $transactions->groupBy('branch_id')->map(function ($rows) use ($cats, $branches) {
            $branch  = $branches->firstWhere('id', $rows->first()->branch_id);
            $byCur   = $rows->groupBy('currency')->map(function ($crows) use ($cats) {
                $inc = $crows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
                $exp = $crows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
                return ['income' => round($inc, 2), 'expense' => round($exp, 2), 'net' => round($inc - $exp, 2)];
            });
            return ['branch' => $branch, 'byCurrency' => $byCur];
        })->values();

        $byCat = collect($cats)->mapWithKeys(function ($meta, $key) use ($transactions) {
            return [$key => $transactions->where('category', $key)->sum('amount')];
        });

        $totalDays = (int) $from->diffInDays($to) + 1;
        $chartData = [];

        if ($totalDays > 62) {
            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->endOfMonth();
            while ($cursor->lte($end)) {
                $mStart = $cursor->copy()->startOfMonth();
                $mEnd   = $cursor->copy()->endOfMonth();
                $monthTx = $transactions->filter(fn($r) => $r->paid_at->between($mStart, $mEnd));
                $inc = $monthTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
                $exp = $monthTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
                $chartData[] = ['date' => $cursor->format('Y-m-01'), 'income' => (float)$inc, 'expense' => (float)$exp, 'mode' => 'month'];
                $cursor->addMonth();
            }
        } else {
            for ($i = 0; $i < $totalDays; $i++) {
                $day   = $from->copy()->addDays($i)->toDateString();
                $dayTx = $transactions->filter(fn($r) => $r->paid_at->toDateString() === $day);
                $inc   = $dayTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
                $exp   = $dayTx->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
                $chartData[] = ['date' => $day, 'income' => (float)$inc, 'expense' => (float)$exp, 'mode' => 'day'];
            }
        }

        $grouped          = $paginatedTx->getCollection()->groupBy(fn($r) => $r->paid_at->toDateString());
        $currencies       = config('booksy.currencies', []);
        $defaultCurrency  = config('booksy.default_currency', 'SYP');
        $incomeCats       = collect($cats)->filter(fn($c) => $c['type'] === 'income');
        $expenseCats      = collect($cats)->filter(fn($c) => $c['type'] === 'expense');

        // ── Archive months ──────────────────────────────────────────────────
        $archiveQuery = BranchPayment::whereHas('branch', fn($q) => $q->where('company_id', $company->id));
        if ($branchId) {
            $archiveQuery->where('branch_id', $branchId);
        }
        $archiveMonths = $archiveQuery
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, COUNT(*) as cnt")
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->orderByDesc('ym')
            ->limit(24)
            ->get()
            ->map(fn($row) => [
                'ym'    => $row->ym,
                'label' => Carbon::parse($row->ym . '-01')->translatedFormat('F Y'),
                'count' => $row->cnt,
                'from'  => Carbon::parse($row->ym . '-01')->startOfMonth()->toDateString(),
                'to'    => Carbon::parse($row->ym . '-01')->endOfMonth()->toDateString(),
            ]);

        return view('company.cash.global', compact(
            'branches', 'branchId', 'transactions', 'paginatedTx', 'grouped', 'summary',
            'byBranch', 'byCat', 'chartData', 'from', 'to', 'period',
            'customFrom', 'customTo', 'cats', 'incomeCats', 'expenseCats',
            'currencies', 'defaultCurrency', 'archiveMonths'
        ));
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $cats = BranchPayment::CATEGORIES;

        $data = $request->validate([
            'category'       => ['required', 'in:' . implode(',', array_keys($cats))],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'currency'       => ['required', 'string', 'size:3'],
            'payment_method' => ['nullable', 'string', 'max:48'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'paid_at'        => ['required', 'date'],
            // Overpayment / underpayment extras
            'paid_amount'    => ['nullable', 'numeric', 'min:0'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
        ]);

        $type = $cats[$data['category']]['type'] === 'income' ? 'income' : 'expense';

        $payment = BranchPayment::create([
            'company_id'             => $this->company()->id,
            'branch_id'              => $branch->id,
            'appointment_id'         => $data['appointment_id'] ?? null,
            'type'                   => $type,
            'category'               => $data['category'],
            'amount'                 => $data['amount'],
            'currency'               => $data['currency'],
            'payment_method'         => $data['payment_method'] ?? 'cash',
            'notes'                  => $data['notes'] ?? null,
            'recorded_by_employee_id'=> Auth::guard('company')->id(),
            'paid_at'                => Carbon::parse($data['paid_at']),
        ]);

        $catLabel = __($cats[$data['category']]['label_key']);
        Auditor::log(
            "Cash {$type}: {$catLabel} — {$data['amount']} {$data['currency']} ({$branch->localizedName()})",
            $payment,
            ['category' => $data['category'], 'amount' => $data['amount'], 'currency' => $data['currency'], 'type' => $type]
        );

        // If overpayment/underpayment extra record needed
        if (!empty($data['paid_amount']) && !empty($data['appointment_id'])) {
            $charged = (float) $data['amount'];
            $paid    = (float) $data['paid_amount'];
            $diff    = round($paid - $charged, 2);

            if ($diff > 0) {
                // Overpayment
                $overpaymentTo = $branch->overpayment_to ?? 'treasury';
                BranchPayment::create([
                    'company_id'             => $this->company()->id,
                    'branch_id'              => $branch->id,
                    'appointment_id'         => $data['appointment_id'],
                    'type'                   => 'adjustment',
                    'category'               => $overpaymentTo === 'employee' ? 'tip' : 'other_income',
                    'amount'                 => $diff,
                    'currency'               => $data['currency'],
                    'notes'                  => $overpaymentTo === 'employee'
                        ? __('Overpayment — tip to employee')
                        : __('Overpayment — added to treasury'),
                    'recorded_by_employee_id'=> Auth::guard('company')->id(),
                    'paid_at'                => Carbon::parse($data['paid_at']),
                ]);
            } elseif ($diff < 0) {
                // Underpayment — record as negative note (debt)
                BranchPayment::create([
                    'company_id'             => $this->company()->id,
                    'branch_id'              => $branch->id,
                    'appointment_id'         => $data['appointment_id'],
                    'type'                   => 'adjustment',
                    'category'               => 'other_expense',
                    'amount'                 => abs($diff),
                    'currency'               => $data['currency'],
                    'notes'                  => __('Underpayment — customer owes :amount', ['amount' => number_format(abs($diff), 2) . ' ' . $data['currency']]),
                    'recorded_by_employee_id'=> Auth::guard('company')->id(),
                    'paid_at'                => Carbon::parse($data['paid_at']),
                ]);
            }
        }

        return redirect()
            ->route('company.branches.cash.index', $branch)
            ->with('success', __('Transaction recorded.'));
    }

    public function setOverpayment(Request $request, Branch $branch): \Illuminate\Http\JsonResponse
    {
        $this->authoriseBranch($branch);
        $val = in_array($request->input('overpayment_to'), ['employee', 'treasury'])
            ? $request->input('overpayment_to')
            : 'treasury';
        $branch->update(['overpayment_to' => $val]);
        return response()->json(['ok' => true]);
    }

    public function openDrawer(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $existing = CashDrawerSession::where('branch_id', $branch->id)->open()->first();
        if ($existing) {
            return back()->with('error', __('Drawer is already open.'));
        }

        $data = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'currency'        => ['required', 'string', 'size:3'],
        ]);

        $session = CashDrawerSession::create([
            'company_id'      => $this->company()->id,
            'branch_id'       => $branch->id,
            'opened_by'       => null,
            'opening_balance' => $data['opening_balance'],
            'currency'        => $data['currency'],
            'status'          => 'open',
            'opened_at'       => now(),
        ]);

        Auditor::log("Opened cash drawer with balance {$data['opening_balance']} {$data['currency']}", $session);

        return back()->with('success', __('Drawer opened.'));
    }

    public function closeDrawer(Request $request, Branch $branch, CashDrawerSession $session): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id && $session->isOpen(), 403);

        $data = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $cats = BranchPayment::CATEGORIES;
        $txs = BranchPayment::where('branch_id', $branch->id)
            ->where('currency', $session->currency)
            ->whereBetween('paid_at', [$session->opened_at, now()])
            ->get();

        $income  = $txs->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')
                       ->where('payment_method', 'cash')->sum('amount');
        $expense = $txs->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')
                       ->where('payment_method', 'cash')->sum('amount');

        $expected = round((float) $session->opening_balance + $income - $expense, 2);
        $variance = round((float) $data['closing_balance'] - $expected, 2);

        $session->update([
            'closing_balance'  => $data['closing_balance'],
            'expected_balance' => $expected,
            'variance'         => $variance,
            'status'           => 'closed',
            'closed_at'        => now(),
            'notes'            => $data['notes'] ?? null,
        ]);

        Auditor::log("Closed cash drawer. Expected: {$expected}, Actual: {$data['closing_balance']}, Variance: {$variance}", $session);

        return back()->with('success', __('Drawer closed.'));
    }

    public function reconcileDrawer(Request $request, Branch $branch, CashDrawerSession $session): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id && $session->isClosed(), 403);

        $reasons = array_keys(CashDrawerSession::RECONCILE_REASONS);

        $data = $request->validate([
            'reconcile_reason' => ['required', 'in:' . implode(',', $reasons)],
            'reconcile_notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $reasonLabel = __(CashDrawerSession::RECONCILE_REASONS[$data['reconcile_reason']]['label_key']);

        $session->update([
            'status'           => 'reconciled',
            'reconcile_reason' => $data['reconcile_reason'],
            'reconcile_notes'  => $data['reconcile_notes'] ?? null,
            'reconciled_at'    => now(),
        ]);

        Auditor::log(
            "Reconciled drawer #{$session->id} — Reason: {$reasonLabel}, Variance: {$session->variance} {$session->currency}",
            $session,
            ['reason' => $data['reconcile_reason'], 'notes' => $data['reconcile_notes'] ?? null, 'variance' => $session->variance]
        );

        return back()->with('success', __('Drawer reconciled.'));
    }

    private function getFilteredTransactions(Branch $branch, Request $request): array
    {
        $period    = $request->get('period', 'month');
        $customFrom = $request->get('from');
        $customTo   = $request->get('to');

        [$from, $to] = match ($period) {
            'today'  => [now()->startOfDay(),  now()->endOfDay()],
            'week'   => [now()->startOfWeek(), now()->endOfWeek()],
            'year'   => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [Carbon::parse($customFrom)->startOfDay(), Carbon::parse($customTo)->endOfDay()],
            default  => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $transactions = BranchPayment::where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$from, $to])
            ->with(['appointment.customer', 'recordedBy'])
            ->orderByDesc('paid_at')
            ->get();

        return [$transactions, $from, $to, $period];
    }

    public function exportCsv(Branch $branch, Request $request): StreamedResponse
    {
        $this->authoriseBranch($branch);
        [$transactions, $from, $to] = $this->getFilteredTransactions($branch, $request);
        $cats = BranchPayment::CATEGORIES;
        $pms  = BranchPayment::PAYMENT_METHODS;

        $filename = 'cash-' . $branch->slug . '-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($transactions, $cats, $pms) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [__('Date'), __('Category'), __('Type'), __('Amount'), __('Currency'), __('Payment method'), __('Notes'), __('Customer')]);

            foreach ($transactions as $tx) {
                $catMeta = $cats[$tx->category] ?? null;
                $pmMeta  = $pms[$tx->payment_method] ?? null;
                fputcsv($out, [
                    $tx->paid_at->format('Y-m-d H:i'),
                    $catMeta ? __($catMeta['label_key']) : $tx->category,
                    $catMeta ? __($catMeta['type'] === 'income' ? 'Income' : 'Expense') : $tx->type,
                    $tx->amount,
                    $tx->currency,
                    $pmMeta ? __($pmMeta['label_key']) : ($tx->payment_method ?? ''),
                    $tx->notes ?? '',
                    $tx->appointment?->customer?->name ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Branch $branch, Request $request)
    {
        $this->authoriseBranch($branch);
        [$transactions, $from, $to, $period] = $this->getFilteredTransactions($branch, $request);

        $cats = BranchPayment::CATEGORIES;
        $summary = $transactions->groupBy('currency')->map(function ($rows) use ($cats) {
            $income  = $rows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'income')->sum('amount');
            $expense = $rows->filter(fn($r) => isset($cats[$r->category]) && $cats[$r->category]['type'] === 'expense')->sum('amount');
            return ['income' => round($income, 2), 'expense' => round($expense, 2), 'net' => round($income - $expense, 2)];
        });

        $company = $this->company();
        $filename = 'cash-' . ($branch->slug ?? $branch->id) . '-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('company.cash.export-pdf', compact(
            'branch', 'company', 'transactions', 'summary', 'cats', 'from', 'to', 'period'
        ))
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download($filename);
    }

    public function update(Request $request, Branch $branch, BranchPayment $payment): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($payment->branch_id === $branch->id, 403);

        $cats = BranchPayment::CATEGORIES;

        $data = $request->validate([
            'category'       => ['required', 'in:' . implode(',', array_keys($cats))],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:48'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'paid_at'        => ['required', 'date'],
        ]);

        $old = $payment->only(['category', 'amount', 'payment_method', 'notes', 'paid_at']);

        $type = $cats[$data['category']]['type'] === 'income' ? 'income' : 'expense';

        $payment->update([
            'category'       => $data['category'],
            'type'           => $type,
            'amount'         => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'notes'          => $data['notes'] ?? null,
            'paid_at'        => Carbon::parse($data['paid_at']),
        ]);

        Auditor::logChange(
            "Edited cash transaction #{$payment->id} ({$branch->localizedName()})",
            $payment,
            $old,
            $payment->only(['category', 'amount', 'payment_method', 'notes', 'paid_at'])
        );

        return redirect()
            ->route('company.branches.cash.index', $branch)
            ->with('success', __('Transaction updated.'));
    }

    public function destroy(Branch $branch, BranchPayment $payment): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($payment->branch_id === $branch->id, 403);

        $cats = BranchPayment::CATEGORIES;
        $catLabel = isset($cats[$payment->category]) ? __($cats[$payment->category]['label_key']) : $payment->category;
        Auditor::log(
            "Deleted cash transaction: {$catLabel} — {$payment->amount} {$payment->currency} ({$branch->localizedName()})",
            $payment,
            ['category' => $payment->category, 'amount' => $payment->amount, 'currency' => $payment->currency, 'type' => $payment->type]
        );

        $payment->delete();

        return redirect()
            ->route('company.branches.cash.index', $branch)
            ->with('success', __('Transaction deleted.'));
    }
}
