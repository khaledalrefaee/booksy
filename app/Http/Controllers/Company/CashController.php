<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchPayment;
use App\Models\CashDrawerBalance;
use App\Models\CashDrawerSession;
use App\Support\Auditor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CashController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function operatorName(): string
    {
        return $this->company()->localizedName();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    // ── Period helper ────────────────────────────────────────────────────────
    private function resolvePeriod(Request $request): array
    {
        $period     = $request->get('period', 'month');
        $customFrom = $request->get('from');
        $customTo   = $request->get('to');

        [$from, $to] = match ($period) {
            'today'  => [now()->startOfDay(),  now()->endOfDay()],
            'week'   => [now()->startOfWeek(), now()->endOfWeek()],
            'year'   => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [Carbon::parse($customFrom)->startOfDay(), Carbon::parse($customTo)->endOfDay()],
            default  => [now()->startOfMonth(), now()->endOfMonth()],
        };

        return [$from, $to, $period, $customFrom, $customTo];
    }

    // ── SQL aggregation helpers ──────────────────────────────────────────────
    private function summaryBySQL($baseQuery): \Illuminate\Support\Collection
    {
        $cats = BranchPayment::CATEGORIES;
        $incomeCats  = collect($cats)->filter(fn($c) => $c['type'] === 'income')->keys()->toArray();
        $expenseCats = collect($cats)->filter(fn($c) => $c['type'] === 'expense')->keys()->toArray();

        $incomePlaceholders  = implode(',', array_fill(0, count($incomeCats), '?'));
        $expensePlaceholders = implode(',', array_fill(0, count($expenseCats), '?'));

        return (clone $baseQuery)
            ->select('currency')
            ->selectRaw("SUM(CASE WHEN category IN ({$incomePlaceholders}) THEN amount ELSE 0 END) as income", $incomeCats)
            ->selectRaw("SUM(CASE WHEN category IN ({$expensePlaceholders}) THEN amount ELSE 0 END) as expense", $expenseCats)
            ->groupBy('currency')
            ->get()
            ->keyBy('currency')
            ->map(fn($row) => [
                'income'  => round((float) $row->income, 2),
                'expense' => round((float) $row->expense, 2),
                'net'     => round((float) $row->income - (float) $row->expense, 2),
            ]);
    }

    private function byCategorySQL($baseQuery): \Illuminate\Support\Collection
    {
        return (clone $baseQuery)
            ->select('category')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');
    }

    private function chartDataSQL($baseQuery, Carbon $from, Carbon $to): array
    {
        $cats = BranchPayment::CATEGORIES;
        $incomeCats  = collect($cats)->filter(fn($c) => $c['type'] === 'income')->keys()->toArray();
        $expenseCats = collect($cats)->filter(fn($c) => $c['type'] === 'expense')->keys()->toArray();

        $totalDays = (int) $from->diffInDays($to) + 1;

        $incomePlaceholders  = implode(',', array_fill(0, count($incomeCats), '?'));
        $expensePlaceholders = implode(',', array_fill(0, count($expenseCats), '?'));

        if ($totalDays > 62) {
            $rows = (clone $baseQuery)
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m-01') as dt")
                ->selectRaw("SUM(CASE WHEN category IN ({$incomePlaceholders}) THEN amount ELSE 0 END) as income", $incomeCats)
                ->selectRaw("SUM(CASE WHEN category IN ({$expensePlaceholders}) THEN amount ELSE 0 END) as expense", $expenseCats)
                ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m-01')")
                ->orderBy('dt')
                ->get();

            return $rows->map(fn($r) => [
                'date' => $r->dt, 'income' => (float) $r->income, 'expense' => (float) $r->expense, 'mode' => 'month',
            ])->toArray();
        }

        $rows = (clone $baseQuery)
            ->selectRaw("DATE(paid_at) as dt")
            ->selectRaw("SUM(CASE WHEN category IN ({$incomePlaceholders}) THEN amount ELSE 0 END) as income", $incomeCats)
            ->selectRaw("SUM(CASE WHEN category IN ({$expensePlaceholders}) THEN amount ELSE 0 END) as expense", $expenseCats)
            ->groupByRaw("DATE(paid_at)")
            ->orderBy('dt')
            ->get()
            ->keyBy('dt');

        $chartData = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $r   = $rows->get($day);
            $chartData[] = [
                'date'    => $day,
                'income'  => $r ? (float) $r->income : 0,
                'expense' => $r ? (float) $r->expense : 0,
                'mode'    => 'day',
            ];
        }

        return $chartData;
    }

    // ── Expected balance per currency for a drawer session ────────────────
    private function computeExpected(Branch $branch, CashDrawerSession $session): \Illuminate\Support\Collection
    {
        $cats = BranchPayment::CATEGORIES;
        $incomeCats  = collect($cats)->filter(fn($c) => $c['type'] === 'income')->keys()->toArray();
        $expenseCats = collect($cats)->filter(fn($c) => $c['type'] === 'expense')->keys()->toArray();

        $inP  = implode(',', array_fill(0, count($incomeCats), '?'));
        $exP  = implode(',', array_fill(0, count($expenseCats), '?'));

        $txAgg = BranchPayment::where('branch_id', $branch->id)
            ->where('payment_method', 'cash')
            ->whereBetween('paid_at', [$session->opened_at, now()])
            ->select('currency')
            ->selectRaw("SUM(CASE WHEN category IN ({$inP}) THEN amount ELSE 0 END) as cash_income", $incomeCats)
            ->selectRaw("SUM(CASE WHEN category IN ({$exP}) THEN amount ELSE 0 END) as cash_expense", $expenseCats)
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        return $session->balances->map(function (CashDrawerBalance $bal) use ($txAgg) {
            $agg      = $txAgg->get($bal->currency);
            $income   = $agg ? (float) $agg->cash_income : 0;
            $expense  = $agg ? (float) $agg->cash_expense : 0;
            $expected = round((float) $bal->opening_amount + $income - $expense, 2);

            return [
                'currency'       => $bal->currency,
                'opening_amount' => (float) $bal->opening_amount,
                'cash_income'    => $income,
                'cash_expense'   => $expense,
                'expected'       => $expected,
            ];
        });
    }

    // =====================================================================
    // INDEX
    // =====================================================================
    public function index(Branch $branch, Request $request): View
    {
        $this->authoriseBranch($branch);

        [$from, $to, $period, $customFrom, $customTo] = $this->resolvePeriod($request);

        // Base query
        $baseQuery = BranchPayment::where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$from, $to]);

        // Filtering
        $filterType     = $request->get('filter_type');
        $filterCategory = $request->get('filter_category');

        if ($filterType) {
            $cats = BranchPayment::CATEGORIES;
            $filtered = collect($cats)->filter(fn($c) => $c['type'] === $filterType)->keys()->toArray();
            if ($filtered) {
                $baseQuery->whereIn('category', $filtered);
            }
        }
        if ($filterCategory) {
            $baseQuery->where('category', $filterCategory);
        }

        // SQL aggregation (performance improvement)
        $summary   = $this->summaryBySQL($baseQuery);
        $byCat     = $this->byCategorySQL($baseQuery);
        $chartData = $this->chartDataSQL($baseQuery, $from, $to);

        // Paginated transactions for display
        $paginatedTx = (clone $baseQuery)
            ->with(['appointment.customer', 'recordedBy'])
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        $grouped = $paginatedTx->getCollection()->groupBy(fn($r) => $r->paid_at->toDateString());

        $currencies      = config('booksy.currencies', []);
        $defaultCurrency = config('booksy.default_currency', 'SYP');

        $drawerSession = CashDrawerSession::where('branch_id', $branch->id)
            ->open()->with('balances')->first();

        $recentDrawers = CashDrawerSession::where('branch_id', $branch->id)
            ->whereIn('status', ['closed', 'reconciled'])
            ->with(['balances', 'openedBy', 'closedBy'])
            ->orderByDesc('closed_at')
            ->limit(5)
            ->get();

        $archiveMonths = BranchPayment::where('branch_id', $branch->id)
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

        // Total transaction count for the period (unfiltered)
        $totalTxCount = BranchPayment::where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$from, $to])
            ->count();

        $cats = BranchPayment::CATEGORIES;

        return view('company.cash.index', compact(
            'branch', 'paginatedTx', 'grouped', 'summary', 'byCat',
            'chartData', 'from', 'to', 'period', 'customFrom', 'customTo',
            'cats', 'currencies', 'defaultCurrency',
            'drawerSession', 'recentDrawers', 'archiveMonths',
            'filterType', 'filterCategory', 'totalTxCount'
        ));
    }

    // =====================================================================
    // GLOBAL INDEX
    // =====================================================================
    public function globalIndex(Request $request): View
    {
        $company = $this->company();

        [$from, $to, $period, $customFrom, $customTo] = $this->resolvePeriod($request);
        $branchId = $request->get('branch_id');

        $branches = $company->branches()->orderBy('sort_order')->get();

        $baseQuery = BranchPayment::whereHas('branch', fn($q) => $q->where('company_id', $company->id))
            ->whereBetween('paid_at', [$from, $to]);

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        }

        $summary   = $this->summaryBySQL($baseQuery);
        $byCat     = $this->byCategorySQL($baseQuery);
        $chartData = $this->chartDataSQL($baseQuery, $from, $to);

        $paginatedTx = (clone $baseQuery)
            ->with(['branch', 'appointment.customer'])
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        // Per-branch balance cards via SQL
        $cats = BranchPayment::CATEGORIES;
        $incomeCats  = collect($cats)->filter(fn($c) => $c['type'] === 'income')->keys()->toArray();
        $expenseCats = collect($cats)->filter(fn($c) => $c['type'] === 'expense')->keys()->toArray();
        $inP  = implode(',', array_fill(0, count($incomeCats), '?'));
        $exP  = implode(',', array_fill(0, count($expenseCats), '?'));

        $byBranchRaw = (clone $baseQuery)
            ->select('branch_id', 'currency')
            ->selectRaw("SUM(CASE WHEN category IN ({$inP}) THEN amount ELSE 0 END) as income", $incomeCats)
            ->selectRaw("SUM(CASE WHEN category IN ({$exP}) THEN amount ELSE 0 END) as expense", $expenseCats)
            ->groupBy('branch_id', 'currency')
            ->get();

        $byBranch = $byBranchRaw->groupBy('branch_id')->map(function ($rows) use ($branches) {
            $branch = $branches->firstWhere('id', $rows->first()->branch_id);
            $byCur  = $rows->keyBy('currency')->map(fn($r) => [
                'income'  => round((float) $r->income, 2),
                'expense' => round((float) $r->expense, 2),
                'net'     => round((float) $r->income - (float) $r->expense, 2),
            ]);
            return ['branch' => $branch, 'byCurrency' => $byCur];
        })->values();

        $grouped          = $paginatedTx->getCollection()->groupBy(fn($r) => $r->paid_at->toDateString());
        $currencies       = config('booksy.currencies', []);
        $defaultCurrency  = config('booksy.default_currency', 'SYP');
        $incomeCatsView   = collect($cats)->filter(fn($c) => $c['type'] === 'income');
        $expenseCatsView  = collect($cats)->filter(fn($c) => $c['type'] === 'expense');

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
            'branches', 'branchId', 'paginatedTx', 'grouped', 'summary',
            'byBranch', 'byCat', 'chartData', 'from', 'to', 'period',
            'customFrom', 'customTo', 'cats', 'incomeCatsView', 'expenseCatsView',
            'currencies', 'defaultCurrency', 'archiveMonths'
        ));
    }

    // =====================================================================
    // STORE TRANSACTION
    // =====================================================================
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

        // Overpayment / underpayment handling
        if (!empty($data['paid_amount']) && !empty($data['appointment_id'])) {
            $charged = (float) $data['amount'];
            $paid    = (float) $data['paid_amount'];
            $diff    = round($paid - $charged, 2);

            if ($diff > 0) {
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
            } elseif ($diff < 0 && !empty($data['appointment_id'])) {
                $appt = \App\Models\Appointment::find($data['appointment_id']);
                if ($appt && $appt->customer_id) {
                    \App\Models\CustomerDebt::create([
                        'company_id' => $this->company()->id,
                        'branch_id' => $branch->id,
                        'customer_id' => $appt->customer_id,
                        'appointment_id' => $appt->id,
                        'original_amount' => abs($diff),
                        'currency' => $data['currency'],
                        'status' => 'unpaid',
                        'notes' => __('Underpayment — customer debt recorded'),
                        'created_by_name' => $this->operatorName(),
                    ]);
                }
            }
        }

        return redirect()
            ->route('company.branches.cash.index', $branch)
            ->with('success', __('Transaction recorded.'));
    }

    // =====================================================================
    // OVERPAYMENT SETTING
    // =====================================================================
    public function setOverpayment(Request $request, Branch $branch): JsonResponse
    {
        $this->authoriseBranch($branch);
        $val = in_array($request->input('overpayment_to'), ['employee', 'treasury'])
            ? $request->input('overpayment_to')
            : 'treasury';
        $branch->update(['overpayment_to' => $val]);
        return response()->json(['ok' => true]);
    }

    // =====================================================================
    // OPEN DRAWER (multi-currency)
    // =====================================================================
    public function openDrawer(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $existing = CashDrawerSession::where('branch_id', $branch->id)->open()->first();
        if ($existing) {
            return back()->with('error', __('Drawer is already open.'));
        }

        $data = $request->validate([
            'balances'                  => ['required', 'array', 'min:1'],
            'balances.*.currency'       => ['required', 'string', 'size:3'],
            'balances.*.opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $session = CashDrawerSession::create([
            'company_id'      => $this->company()->id,
            'branch_id'       => $branch->id,
            'opened_by'       => Auth::guard('company')->id(),
            'opened_by_name'  => $this->operatorName(),
            'opening_balance' => $data['balances'][0]['opening_amount'],
            'currency'        => $data['balances'][0]['currency'],
            'status'          => 'open',
            'opened_at'       => now(),
        ]);

        foreach ($data['balances'] as $bal) {
            $session->balances()->create([
                'currency'       => $bal['currency'],
                'opening_amount' => $bal['opening_amount'],
            ]);
        }

        $balSummary = collect($data['balances'])->map(fn($b) => "{$b['opening_amount']} {$b['currency']}")->implode(', ');
        Auditor::log("Opened cash drawer: {$balSummary}", $session);

        return back()->with('success', __('Drawer opened.'));
    }

    // =====================================================================
    // CLOSE DRAWER (multi-currency)
    // =====================================================================
    public function closeDrawer(Request $request, Branch $branch, CashDrawerSession $session): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id && $session->isOpen(), 403);

        $data = $request->validate([
            'balances'                  => ['required', 'array', 'min:1'],
            'balances.*.currency'       => ['required', 'string', 'size:3'],
            'balances.*.closing_amount' => ['required', 'numeric', 'min:0'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
        ]);

        $expected = $this->computeExpected($branch, $session);

        foreach ($data['balances'] as $balInput) {
            $balance = $session->balances()->where('currency', $balInput['currency'])->first();
            if (!$balance) continue;

            $exp = $expected->firstWhere('currency', $balInput['currency']);
            $expectedAmount = $exp ? $exp['expected'] : (float) $balance->opening_amount;
            $variance = round((float) $balInput['closing_amount'] - $expectedAmount, 2);

            $balance->update([
                'closing_amount'  => $balInput['closing_amount'],
                'expected_amount' => $expectedAmount,
                'variance'        => $variance,
            ]);
        }

        // Legacy single-currency fields
        $firstBal = $session->balances()->first();
        $session->update([
            'closing_balance'  => $firstBal?->closing_amount,
            'expected_balance' => $firstBal?->expected_amount,
            'variance'         => $firstBal?->variance,
            'closed_by'        => Auth::guard('company')->id(),
            'closed_by_name'   => $this->operatorName(),
            'status'           => 'closed',
            'closed_at'        => now(),
            'notes'            => $data['notes'] ?? null,
        ]);

        Auditor::log("Closed cash drawer #{$session->id}", $session);

        return back()->with('success', __('Drawer closed.'));
    }

    // =====================================================================
    // EXPECTED BALANCE (AJAX)
    // =====================================================================
    public function expectedBalance(Branch $branch, CashDrawerSession $session): JsonResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id && $session->isOpen(), 403);

        $expected = $this->computeExpected($branch, $session);

        return response()->json(['balances' => $expected->values()]);
    }

    // =====================================================================
    // RECONCILE DRAWER
    // =====================================================================
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
            'reconciled_by'      => Auth::guard('company')->id(),
            'reconciled_by_name' => $this->operatorName(),
            'reconciled_at'    => now(),
        ]);

        Auditor::log(
            "Reconciled drawer #{$session->id} — Reason: {$reasonLabel}",
            $session,
            ['reason' => $data['reconcile_reason'], 'notes' => $data['reconcile_notes'] ?? null]
        );

        return back()->with('success', __('Drawer reconciled.'));
    }

    // =====================================================================
    // REOPEN DRAWER (undo close → back to open)
    // =====================================================================
    public function reopenDrawer(Request $request, Branch $branch, CashDrawerSession $session): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id, 403);
        abort_unless($session->isClosed() || $session->isReconciled(), 403);

        // Prevent reopen if another drawer is already open
        $existing = CashDrawerSession::where('branch_id', $branch->id)->open()->first();
        if ($existing) {
            return back()->with('error', __('Cannot reopen — another drawer is already open. Close it first.'));
        }

        $oldStatus = $session->status;

        // Reset closing data on balances
        $session->balances()->update([
            'closing_amount'  => null,
            'expected_amount' => null,
            'variance'        => null,
        ]);

        $session->update([
            'status'           => 'open',
            'closing_balance'  => null,
            'expected_balance' => null,
            'variance'         => null,
            'closed_by'          => null,
            'closed_by_name'     => null,
            'closed_at'          => null,
            'reconcile_reason'   => null,
            'reconcile_notes'    => null,
            'reconciled_by'      => null,
            'reconciled_by_name' => null,
            'reconciled_at'    => null,
        ]);

        Auditor::log(
            "Reopened cash drawer #{$session->id} (was {$oldStatus})",
            $session,
            ['previous_status' => $oldStatus]
        );

        return back()->with('success', __('Drawer reopened. You can now close it again with correct amounts.'));
    }

    // =====================================================================
    // VOID DRAWER (cancel — keeps record but excluded from reports)
    // =====================================================================
    public function voidDrawer(Request $request, Branch $branch, CashDrawerSession $session): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id, 403);
        abort_unless(!$session->isVoided(), 403);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $oldStatus = $session->status;

        $session->update([
            'status'      => 'voided',
            'voided_at'   => now(),
            'voided_by'      => Auth::guard('company')->id(),
            'voided_by_name' => $this->operatorName(),
            'void_reason'    => $data['void_reason'],
        ]);

        Auditor::log(
            "Voided cash drawer #{$session->id} — Reason: {$data['void_reason']}",
            $session,
            ['previous_status' => $oldStatus, 'void_reason' => $data['void_reason']]
        );

        return back()->with('success', __('Drawer session voided.'));
    }

    // =====================================================================
    // UPDATE DRAWER NOTES (edit notes/reconcile notes only)
    // =====================================================================
    public function updateDrawerNotes(Request $request, Branch $branch, CashDrawerSession $session): RedirectResponse
    {
        $this->authoriseBranch($branch);
        abort_unless($session->branch_id === $branch->id, 403);
        abort_unless(!$session->isVoided(), 403);

        $data = $request->validate([
            'notes'           => ['nullable', 'string', 'max:1000'],
            'reconcile_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $session->only(['notes', 'reconcile_notes']);

        $session->update(array_filter([
            'notes'           => $data['notes'] ?? $session->notes,
            'reconcile_notes' => $data['reconcile_notes'] ?? $session->reconcile_notes,
        ]));

        Auditor::logChange("Updated notes on drawer #{$session->id}", $session, $old, $session->only(['notes', 'reconcile_notes']));

        return back()->with('success', __('Notes updated.'));
    }

    // =====================================================================
    // DRAWER ARCHIVE
    // =====================================================================
    public function drawerArchive(Branch $branch, Request $request): View
    {
        $this->authoriseBranch($branch);

        $query = CashDrawerSession::where('branch_id', $branch->id)
            ->with(['balances', 'openedBy', 'closedBy', 'reconciledBy', 'voidedBy'])
            ->orderByDesc('opened_at');

        if ($request->filled('from')) {
            $query->where('opened_at', '>=', Carbon::parse($request->get('from'))->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('opened_at', '<=', Carbon::parse($request->get('to'))->endOfDay());
        }

        $sessions = $query->paginate(10)->withQueryString();

        return view('company.cash.drawer-archive', compact('branch', 'sessions'));
    }

    // =====================================================================
    // BULK DELETE
    // =====================================================================
    public function destroySelected(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $payments = BranchPayment::where('branch_id', $branch->id)
            ->whereIn('id', $data['ids'])
            ->get();

        foreach ($payments as $payment) {
            Auditor::log(
                "Deleted cash transaction: {$payment->category} — {$payment->amount} {$payment->currency}",
                $payment
            );
            $payment->delete();
        }

        return back()->with('success', __(':count transactions deleted.', ['count' => $payments->count()]));
    }

    public function destroyAll(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        [$from, $to] = $this->resolvePeriod($request);

        $count = BranchPayment::where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$from, $to])
            ->count();

        BranchPayment::where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$from, $to])
            ->delete();

        Auditor::log("Deleted all {$count} cash transactions for period ({$branch->localizedName()})");

        return back()->with('success', __(':count transactions deleted.', ['count' => $count]));
    }

    // =====================================================================
    // UPDATE TRANSACTION
    // =====================================================================
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

    // =====================================================================
    // DELETE SINGLE TRANSACTION
    // =====================================================================
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

    // =====================================================================
    // EXPORTS
    // =====================================================================
    private function getFilteredTransactions(Branch $branch, Request $request): array
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

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
}
