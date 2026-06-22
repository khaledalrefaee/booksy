@extends('company.dashboard')

@push('company-styles')
<link rel="stylesheet" href="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.css') }}">
@include('company.cash._styles')
<style>
/* ─── Page-specific: Add button ────────────────────────────────────────── */
.btn-add-tx {
    position:fixed; bottom:28px; inset-inline-end:28px; z-index:900;
    width:56px; height:56px; border-radius:50%;
    background:linear-gradient(135deg,#667eea,#764ba2);
    box-shadow:0 6px 24px rgba(102,126,234,.5);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:24px; cursor:pointer; border:none;
    transition:transform .15s, box-shadow .15s;
    text-decoration:none;
}
.btn-add-tx:hover { transform:scale(1.08); box-shadow:0 10px 32px rgba(102,126,234,.6); color:#fff; }

/* ─── Overpayment hint ──────────────────────────────────────────────────── */
#diff-hint {
    border-radius:10px; padding:8px 12px; font-size:12px; font-weight:600;
    margin-top:8px; display:none;
}
#diff-hint.over  { background:rgba(34,197,94,.1);  color:#22c55e; }
#diff-hint.under { background:rgba(239,68,68,.1);  color:#ef4444; }
</style>
@endpush

@section('content')
<div class="page-content">

@php
    $cats          = \App\Models\BranchPayment::CATEGORIES;
    $incomeCats    = collect($cats)->filter(fn($c) => $c['type'] === 'income');
    $expenseCats   = collect($cats)->filter(fn($c) => $c['type'] === 'expense');
    $periodLabel   = ['today'=>__('Today'),'week'=>__('This week'),'month'=>__('This month'),'year'=>__('This year'),'custom'=>__('Custom')][$period] ?? __('This month');
@endphp

{{-- ─── HERO ──────────────────────────────────────────────────────────────── --}}
<div class="cash-hero">
    <div class="position-relative" style="z-index:1;">

        {{-- Top row: title + period selector --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <div style="font-size:11px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px;">
                    {{ $branch->localizedName() }}
                </div>
                <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">
                    💰 {{ __('Cash Register') }}
                </h3>
            </div>
            <div class="period-tabs">
                @foreach(['today'=>__('Today'),'week'=>__('Week'),'month'=>__('Month'),'year'=>__('Year')] as $p=>$lbl)
                <a href="{{ route('company.branches.cash.index', [$branch,'period'=>$p]) }}"
                   class="period-tab {{ $period===$p ? 'active' : '' }}">{{ $lbl }}</a>
                @endforeach
                <button class="period-tab {{ $period==='custom' ? 'active' : '' }}"
                        onclick="document.getElementById('custom-date-row').classList.toggle('d-none')"
                        type="button">{{ __('Custom') }}</button>

                <div style="margin-inline-start:auto;display:flex;gap:4px;">
                    <a href="{{ route('company.branches.cash.export.csv', array_merge([$branch], request()->only('period','from','to'))) }}"
                       class="period-tab" style="text-decoration:none;" title="{{ __('Export') }} CSV">
                        📊 CSV
                    </a>
                    <a href="{{ route('company.branches.cash.export.pdf', array_merge([$branch], request()->only('period','from','to'))) }}"
                       class="period-tab" style="text-decoration:none;" title="{{ __('Export') }} PDF">
                        🖨️ PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Custom date form --}}
        <form method="GET" action="{{ route('company.branches.cash.index', $branch) }}"
              id="custom-date-row" class="{{ $period==='custom' ? '' : 'd-none' }} mb-4">
            <input type="hidden" name="period" value="custom">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="date" name="from" class="form-control form-control-sm rounded-pill"
                       style="width:150px;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff;"
                       value="{{ $customFrom ?? now()->startOfMonth()->toDateString() }}">
                <span style="opacity:.5;">—</span>
                <input type="date" name="to" class="form-control form-control-sm rounded-pill"
                       style="width:150px;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff;"
                       value="{{ $customTo ?? now()->toDateString() }}">
                <button class="btn btn-sm rounded-pill px-3"
                        style="background:#667eea;color:#fff;border:none;font-weight:600;">
                    {{ __('Apply') }}
                </button>
            </div>
        </form>

        {{-- Balance cards per currency --}}
        @if($summary->isEmpty())
        <div class="balance-card" style="text-align:center;opacity:.5;">
            <div style="font-size:28px;margin-bottom:6px;">💼</div>
            <div style="font-size:13px;">{{ __('No transactions yet for this period.') }}</div>
        </div>
        @else
        <div class="row g-3">
            @foreach($summary as $cur => $s)
            @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
            <div class="col-12 col-md-4">
                <div class="balance-card">
                    <div class="balance-label">{{ __('Net balance') }} · {{ $cur }}</div>
                    <div class="balance-value {{ $s['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $s['net'] >= 0 ? '+' : '' }}{{ number_format($s['net'], 0) }}
                        <span style="font-size:16px;opacity:.5;">{{ $sym }}</span>
                    </div>
                    <div class="d-flex gap-4 mt-3">
                        <div class="cash-stat">
                            <div class="cash-stat-icon" style="background:rgba(34,197,94,.15);">⬆️</div>
                            <div>
                                <div class="cash-stat-val" style="color:#22c55e;">{{ number_format($s['income'],0) }} {{ $sym }}</div>
                                <div class="cash-stat-lbl">{{ __('Income') }}</div>
                            </div>
                        </div>
                        <div class="cash-stat">
                            <div class="cash-stat-icon" style="background:rgba(239,68,68,.15);">⬇️</div>
                            <div>
                                <div class="cash-stat-val" style="color:#ef4444;">{{ number_format($s['expense'],0) }} {{ $sym }}</div>
                                <div class="cash-stat-lbl">{{ __('Expenses') }}</div>
                            </div>
                        </div>
                        <div class="cash-stat">
                            <div class="cash-stat-icon" style="background:rgba(100,116,139,.15);">📊</div>
                            <div>
                                <div class="cash-stat-val">{{ $transactions->count() }}</div>
                                <div class="cash-stat-lbl">{{ __('Transactions') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ─── DRAWER SESSION BANNER ──────────────────────────────────────────── --}}
@if($drawerSession)
<div class="drawer-banner drawer-open">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="drawer-status-dot open"></div>
        <div>
            <div class="fw-bold" style="font-size:.88rem;">{{ __('Drawer is open') }}</div>
            <div style="font-size:.72rem;opacity:.6;">
                {{ __('Opening balance') }}: <strong>{{ number_format($drawerSession->opening_balance, 2) }} {{ $drawerSession->currency }}</strong>
                · {{ $drawerSession->opened_at->diffForHumans() }}
            </div>
        </div>
        <button class="btn btn-sm rounded-pill px-4 ms-auto fw-bold"
                style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);"
                data-bs-toggle="modal" data-bs-target="#closeDrawerModal">
            🔒 {{ __('Close Drawer') }}
        </button>
    </div>
</div>
@else
<div class="drawer-banner drawer-closed">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="drawer-status-dot closed"></div>
        <div>
            <div class="fw-bold" style="font-size:.88rem;">{{ __('No open drawer session') }}</div>
            <div style="font-size:.72rem;opacity:.6;">{{ __('Open the drawer to start tracking cash flow') }}</div>
        </div>
        <button class="btn btn-sm rounded-pill px-4 ms-auto fw-bold"
                style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);"
                data-bs-toggle="modal" data-bs-target="#openDrawerModal">
            🔓 {{ __('Open Drawer') }}
        </button>
    </div>
</div>
@endif

{{-- Recent closed drawers --}}
@if($recentDrawers->isNotEmpty())
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
    <div class="card-body p-3">
        <div style="font-size:11px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
            {{ __('Recent drawer sessions') }}
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @foreach($recentDrawers as $ds)
            @php $v = (float) $ds->variance; @endphp
            <div class="drawer-history-card">
                <div style="font-size:.7rem;font-weight:700;opacity:.5;">{{ $ds->closed_at?->format('M d, H:i') }}</div>
                <div style="font-size:.82rem;font-weight:800;">
                    {{ number_format($ds->closing_balance, 0) }} {{ $ds->currency }}
                </div>
                <div style="font-size:.68rem;font-weight:700;color:{{ $v == 0 ? '#22c55e' : ($v > 0 ? '#f59e0b' : '#ef4444') }};">
                    @if($v == 0) ✓ {{ __('Exact') }}
                    @elseif($v > 0) ↑ +{{ number_format($v, 2) }} {{ __('Over') }}
                    @else ↓ {{ number_format($v, 2) }} {{ __('Short') }}
                    @endif
                </div>
                @if($ds->isClosed())
                <button class="btn btn-sm rounded-pill px-2 mt-1"
                        style="font-size:.6rem;font-weight:700;background:rgba(102,126,234,.1);color:#667eea;border:none;"
                        data-bs-toggle="modal" data-bs-target="#reconcileModal-{{ $ds->id }}">
                    {{ __('Reconcile') }}
                </button>
                @else
                @php $rMeta = \App\Models\CashDrawerSession::RECONCILE_REASONS[$ds->reconcile_reason] ?? null; @endphp
                <div style="font-size:.6rem;font-weight:700;color:#22c55e;margin-top:4px;">✓ {{ __('Reconciled') }}</div>
                @if($rMeta)
                <div style="font-size:.58rem;color:{{ $rMeta['color'] }};font-weight:600;margin-top:2px;">
                    {{ $rMeta['icon'] }} {{ __($rMeta['label_key']) }}
                </div>
                @endif
                @if($ds->reconcile_notes)
                <div style="font-size:.55rem;opacity:.5;margin-top:1px;">{{ Str::limit($ds->reconcile_notes, 30) }}</div>
                @endif
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Reconcile modals --}}
@foreach($recentDrawers->where('status', 'closed') as $ds)
<div class="modal fade tx-modal" id="reconcileModal-{{ $ds->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.reconcile', [$branch, $ds]) }}">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">📋 {{ __('Reconcile Drawer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    {{-- Variance summary --}}
                    @php $v = (float) $ds->variance; @endphp
                    <div class="p-3 rounded-3 mb-3 text-center" style="background:{{ $v == 0 ? 'rgba(34,197,94,.08)' : ($v > 0 ? 'rgba(245,158,11,.08)' : 'rgba(239,68,68,.08)') }};border:1px solid {{ $v == 0 ? 'rgba(34,197,94,.15)' : ($v > 0 ? 'rgba(245,158,11,.15)' : 'rgba(239,68,68,.15)') }};">
                        <div style="font-size:.72rem;opacity:.6;font-weight:600;">{{ __('Variance') }}</div>
                        <div style="font-size:1.4rem;font-weight:800;color:{{ $v == 0 ? '#22c55e' : ($v > 0 ? '#f59e0b' : '#ef4444') }};">
                            {{ $v > 0 ? '+' : '' }}{{ number_format($v, 2) }} {{ $ds->currency }}
                        </div>
                        <div style="font-size:.7rem;font-weight:700;color:{{ $v == 0 ? '#22c55e' : ($v > 0 ? '#f59e0b' : '#ef4444') }};">
                            @if($v == 0) ✓ {{ __('Exact') }}
                            @elseif($v > 0) {{ __('Over') }}
                            @else {{ __('Short') }}
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-1 px-1" style="font-size:.78rem;">
                        <span style="opacity:.5;">{{ __('Expected balance') }}</span>
                        <strong>{{ number_format($ds->expected_balance, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 px-1" style="font-size:.78rem;">
                        <span style="opacity:.5;">{{ __('Actual cash count') }}</span>
                        <strong>{{ number_format($ds->closing_balance, 2) }}</strong>
                    </div>

                    {{-- Reason picker --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(\App\Models\CashDrawerSession::RECONCILE_REASONS as $rKey => $rMeta)
                            <label style="cursor:pointer;">
                                <input type="radio" name="reconcile_reason" value="{{ $rKey }}" class="d-none reconcile-reason-radio" required>
                                <div class="pm-card" style="--pm-color:{{ $rMeta['color'] }};">
                                    <span>{{ $rMeta['icon'] }}</span>
                                    {{ __($rMeta['label_key']) }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-1">
                        <label class="f-label">{{ __('Notes') }} <span style="font-weight:400;opacity:.5;">({{ __('optional') }})</span></label>
                        <textarea name="reconcile_notes" class="f-input form-control" rows="2"
                                  placeholder="{{ __('Explain the variance...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;">
                        ✔ {{ __('Reconcile') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@include('company.partials.flash')

<div class="row g-3">

    {{-- ─── LEFT: Chart + Transactions ──────────────────────────────────────── --}}
    <div class="col-12 col-xl-8">

        {{-- Chart --}}
        @if(count($chartData) > 1)
        <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
            <div class="card-body p-3">
                <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                    {{ __('Income vs Expenses') }}
                </div>
                <div id="cashChart"></div>
            </div>
        </div>
        @endif

        {{-- Transactions grouped by date --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;">
                        {{ __('Transactions') }}
                    </div>
                    <span style="font-size:12px;font-weight:700;background:rgba(102,126,234,.1);color:#667eea;padding:3px 10px;border-radius:20px;">
                        {{ $paginatedTx->total() }}
                    </span>
                </div>

                @if($grouped->isEmpty())
                <div class="text-center py-5" style="opacity:.4;">
                    <div style="font-size:32px;margin-bottom:8px;">🧾</div>
                    <div style="font-size:13px;">{{ __('No transactions for this period.') }}</div>
                </div>
                @else
                @foreach($grouped as $date => $rows)
                <div class="tx-date-head">
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('l، d F Y') }}
                </div>
                @foreach($rows as $tx)
                @php
                    $catMeta = $cats[$tx->category] ?? ['icon'=>'💵','color'=>'#667eea','label_key'=>$tx->category,'type'=>'income'];
                    $isIncome = $catMeta['type'] === 'income';
                    $sym = config("booksy.currencies.{$tx->currency}.symbol", $tx->currency);
                @endphp
                <div class="tx-row">
                    <div class="tx-icon" style="background:{{ $catMeta['color'] }}20;">
                        {{ $catMeta['icon'] }}
                    </div>
                    <div class="tx-meta">
                        <div class="tx-title">
                            {{ __($catMeta['label_key']) }}
                            @php $pmMeta = \App\Models\BranchPayment::PAYMENT_METHODS[$tx->payment_method] ?? null; @endphp
                            @if($pmMeta)
                            <span class="pm-badge" style="background:{{ $pmMeta['color'] }}18;color:{{ $pmMeta['color'] }};">
                                {{ $pmMeta['icon'] }} {{ __($pmMeta['label_key']) }}
                            </span>
                            @endif
                        </div>
                        <div class="tx-sub">
                            {{ $tx->paid_at->format('H:i') }}
                            @if($tx->notes) · {{ Str::limit($tx->notes, 50) }}@endif
                            @if($tx->appointment?->customer) · {{ $tx->appointment->customer->name }}@endif
                        </div>
                    </div>
                    <div class="tx-amount" style="color:{{ $isIncome ? '#22c55e' : '#ef4444' }};">
                        {{ $isIncome ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                        <span style="font-size:10px;opacity:.5;">{{ $sym }}</span>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="tx-edit" title="{{ __('Edit') }}"
                                onclick="openEditModal({{ json_encode([
                                    'id' => $tx->id,
                                    'category' => $tx->category,
                                    'amount' => $tx->amount,
                                    'payment_method' => $tx->payment_method ?? 'cash',
                                    'notes' => $tx->notes,
                                    'paid_at' => $tx->paid_at->toDateString(),
                                ]) }})">
                            <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                        </button>
                        <button class="tx-del" title="{{ __('Delete') }}"
                                onclick="confirmDelete('{{ route('company.branches.cash.destroy', [$branch, $tx]) }}')">
                            <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                    </div>
                </div>
                @endforeach
                @endforeach

                {{-- Pagination --}}
                @if($paginatedTx->hasPages())
                <div class="mt-3">{{ $paginatedTx->links() }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ─── RIGHT: Category breakdown ─────────────────────────────────────── --}}
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;position:sticky;top:80px;">
            <div class="card-body p-3">
                <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;">
                    {{ __('Breakdown') }}
                </div>

                {{-- Income --}}
                <div class="income-section">
                    <div style="font-size:10px;font-weight:700;color:#22c55e;opacity:.7;margin-bottom:8px;">
                        ⬆ {{ __('INCOME') }}
                    </div>
                    @foreach($incomeCats as $key => $meta)
                    @php $total = $byCat[$key] ?? 0; @endphp
                    @if($total > 0)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:16px;">{{ $meta['icon'] }}</span>
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex justify-content-between" style="font-size:12px;font-weight:600;">
                                <span>{{ __($meta['label_key']) }}</span>
                                <span style="color:#22c55e;">{{ number_format($total,0) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- Expense --}}
                <div class="expense-section mt-3">
                    <div style="font-size:10px;font-weight:700;color:#ef4444;opacity:.7;margin-bottom:8px;">
                        ⬇ {{ __('EXPENSES') }}
                    </div>
                    @foreach($expenseCats as $key => $meta)
                    @php $total = $byCat[$key] ?? 0; @endphp
                    @if($total > 0)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:16px;">{{ $meta['icon'] }}</span>
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex justify-content-between" style="font-size:12px;font-weight:600;">
                                <span>{{ __($meta['label_key']) }}</span>
                                <span style="color:#ef4444;">{{ number_format($total,0) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- Overpayment setting --}}
                <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.07);">
                    <div style="font-size:11px;font-weight:700;opacity:.4;margin-bottom:8px;">⚙ {{ __('Overpayment goes to') }}</div>
                    <div class="d-flex gap-2">
                        @foreach(['treasury'=>['🏦',__('Treasury')],'employee'=>['👤',__('Employee tip')]] as $val=>[$ico,$lbl])
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="overpayment_to_ui" value="{{ $val }}"
                                   {{ ($branch->overpayment_to ?? 'treasury') === $val ? 'checked' : '' }}
                                   onchange="saveOverpaymentSetting('{{ $val }}')"
                                   class="d-none">
                            <div class="cat-card text-center {{ ($branch->overpayment_to ?? 'treasury') === $val ? 'active' : '' }}"
                                 style="--cat-color:#667eea;--cat-rgb:102,126,234;">
                                <span style="font-size:20px;display:block;margin-bottom:4px;">{{ $ico }}</span>
                                <div style="font-size:10px;font-weight:700;">{{ $lbl }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Archive months --}}
                @if($archiveMonths->isNotEmpty())
                <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.07);">
                    <div style="font-size:11px;font-weight:700;opacity:.4;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
                        📅 {{ __('Archive') }}
                    </div>
                    <div class="d-flex flex-column gap-1" id="archive-list">
                        @foreach($archiveMonths->take(6) as $am)
                        @php
                            $isActive = $period === 'custom' && $customFrom === $am['from'] && $customTo === $am['to'];
                        @endphp
                        <a href="{{ route('company.branches.cash.index', [$branch, 'period' => 'custom', 'from' => $am['from'], 'to' => $am['to']]) }}"
                           class="d-flex align-items-center justify-content-between text-decoration-none"
                           style="padding:6px 10px;border-radius:8px;transition:background .12s;{{ $isActive ? 'background:rgba(102,126,234,.15);' : '' }}"
                           onmouseover="this.style.background='rgba(102,126,234,.1)'"
                           onmouseout="this.style.background='{{ $isActive ? 'rgba(102,126,234,.15)' : 'transparent' }}'">
                            <span style="font-size:12px;font-weight:600;color:var(--text-color);{{ $isActive ? 'color:#667eea;' : '' }}">{{ $am['label'] }}</span>
                            <span style="font-size:10px;font-weight:700;opacity:.4;background:rgba(255,255,255,.06);padding:1px 7px;border-radius:10px;">{{ $am['count'] }}</span>
                        </a>
                        @endforeach
                    </div>
                    @if($archiveMonths->count() > 6)
                    <div id="archive-more" class="d-none">
                        @foreach($archiveMonths->skip(6) as $am)
                        @php
                            $isActive = $period === 'custom' && $customFrom === $am['from'] && $customTo === $am['to'];
                        @endphp
                        <a href="{{ route('company.branches.cash.index', [$branch, 'period' => 'custom', 'from' => $am['from'], 'to' => $am['to']]) }}"
                           class="d-flex align-items-center justify-content-between text-decoration-none"
                           style="padding:6px 10px;border-radius:8px;transition:background .12s;{{ $isActive ? 'background:rgba(102,126,234,.15);' : '' }}"
                           onmouseover="this.style.background='rgba(102,126,234,.1)'"
                           onmouseout="this.style.background='{{ $isActive ? 'rgba(102,126,234,.15)' : 'transparent' }}'">
                            <span style="font-size:12px;font-weight:600;color:var(--text-color);{{ $isActive ? 'color:#667eea;' : '' }}">{{ $am['label'] }}</span>
                            <span style="font-size:10px;font-weight:700;opacity:.4;background:rgba(255,255,255,.06);padding:1px 7px;border-radius:10px;">{{ $am['count'] }}</span>
                        </a>
                        @endforeach
                    </div>
                    <button onclick="document.getElementById('archive-more').classList.remove('d-none');this.remove();"
                            class="btn btn-sm w-100 mt-1" style="font-size:10px;font-weight:700;opacity:.5;background:rgba(255,255,255,.04);border:none;border-radius:8px;">
                        {{ __('Show more') }} ({{ $archiveMonths->count() - 6 }})
                    </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ─── FLOATING ADD BUTTON ────────────────────────────────────────────── --}}
<button class="btn-add-tx" data-bs-toggle="modal" data-bs-target="#addTxModal" title="{{ __('Add transaction') }}">
    <i data-feather="plus" style="width:24px;height:24px;"></i>
</button>

{{-- ─── DELETE CONFIRMATION MODAL ──────────────────────────────────────── --}}
<div class="modal fade" id="deleteTxModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;background:var(--card-bg, #1a1f2e);">
            <div class="modal-body text-center p-4">
                <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                <h6 class="fw-bold mb-2">{{ __('Delete this transaction?') }}</h6>
                <p class="text-muted small mb-3">{{ __('This action cannot be undone.') }}</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm rounded-pill px-4"
                            style="background:rgba(255,255,255,.07);font-weight:600;"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form id="deleteTxForm" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                            {{ __('Delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── ADD TRANSACTION MODAL ─────────────────────────────────────────── --}}
<div class="modal fade tx-modal" id="addTxModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.store', $branch) }}" id="txForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="font-family:'Poppins',sans-serif;">
                        ➕ {{ __('Add Transaction') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">

                    {{-- Category picker --}}
                    <div class="mb-3">
                        <div class="income-section mb-2">
                            <div style="font-size:10px;font-weight:700;color:#22c55e;margin-bottom:8px;">⬆ {{ __('INCOME') }}</div>
                            <div class="row g-2">
                                @foreach($incomeCats as $key => $meta)
                                <div class="col-4">
                                    <label style="cursor:pointer;display:block;">
                                        <input type="radio" name="category" value="{{ $key }}"
                                               class="d-none cat-radio"
                                               {{ $key === 'appointment' ? 'checked' : '' }}>
                                        <div class="cat-card {{ $key === 'appointment' ? 'active' : '' }}"
                                             style="--cat-color:{{ $meta['color'] }};">
                                            <span class="cat-emoji">{{ $meta['icon'] }}</span>
                                            {{ __($meta['label_key']) }}
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="expense-section">
                            <div style="font-size:10px;font-weight:700;color:#ef4444;margin-bottom:8px;">⬇ {{ __('EXPENSES') }}</div>
                            <div class="row g-2">
                                @foreach($expenseCats as $key => $meta)
                                <div class="col-4">
                                    <label style="cursor:pointer;display:block;">
                                        <input type="radio" name="category" value="{{ $key }}" class="d-none cat-radio">
                                        <div class="cat-card" style="--cat-color:{{ $meta['color'] }};">
                                            <span class="cat-emoji">{{ $meta['icon'] }}</span>
                                            {{ __($meta['label_key']) }}
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Amount + currency --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Amount') }}</label>
                        <div class="d-flex gap-2">
                            <select name="currency" class="f-input form-select" style="max-width:110px;flex-shrink:0;">
                                @foreach($currencies as $code => $cur)
                                <option value="{{ $code }}" {{ $code === $defaultCurrency ? 'selected' : '' }}>
                                    {{ $cur['symbol'] }} {{ $code }}
                                </option>
                                @endforeach
                            </select>
                            <input type="number" name="amount" id="tx-amount"
                                   class="f-input form-control" min="0.01" step="0.01"
                                   placeholder="0.00" required>
                        </div>
                    </div>

                    {{-- Paid amount (for overpay/underpay) - shown only for appointment --}}
                    <div id="paid-amount-row" class="mb-3">
                        <label class="f-label">
                            {{ __('Customer actually paid') }}
                            <span style="font-weight:400;opacity:.5;font-size:11px;">({{ __('leave blank if exact') }})</span>
                        </label>
                        <input type="number" name="paid_amount" id="tx-paid"
                               class="f-input form-control" min="0" step="0.01"
                               placeholder="{{ __('e.g. 1200 if price was 1000') }}">
                        <div id="diff-hint"></div>
                    </div>

                    {{-- Date + time --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Date') }}</label>
                        <input type="date" name="paid_at" class="f-input form-control"
                               value="{{ now()->toDateString() }}" required>
                    </div>

                    {{-- Payment method --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Payment method') }}</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(\App\Models\BranchPayment::PAYMENT_METHODS as $pmKey => $pmMeta)
                            <label style="cursor:pointer;">
                                <input type="radio" name="payment_method" value="{{ $pmKey }}"
                                       class="d-none pm-radio"
                                       {{ $pmKey === 'cash' ? 'checked' : '' }}>
                                <div class="pm-card {{ $pmKey === 'cash' ? 'active' : '' }}"
                                     style="--pm-color:{{ $pmMeta['color'] }};">
                                    <span>{{ $pmMeta['icon'] }}</span>
                                    {{ __($pmMeta['label_key']) }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-1">
                        <label class="f-label">{{ __('Notes') }} <span style="font-weight:400;opacity:.5;">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" class="f-input form-control"
                               placeholder="{{ __('e.g. paid for Rania — wax + facial') }}">
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4"
                            style="background:rgba(255,255,255,.07);font-weight:600;"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold"
                            style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;">
                        ✔ {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── EDIT TRANSACTION MODAL ─────────────────────────────────────────── --}}
<div class="modal fade tx-modal" id="editTxModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <form method="POST" id="editTxForm">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="font-family:'Poppins',sans-serif;">
                        ✏️ {{ __('Edit Transaction') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">

                    {{-- Category picker --}}
                    <div class="mb-3">
                        <div class="income-section mb-2">
                            <div style="font-size:10px;font-weight:700;color:#22c55e;margin-bottom:8px;">⬆ {{ __('INCOME') }}</div>
                            <div class="row g-2">
                                @foreach($incomeCats as $key => $meta)
                                <div class="col-4">
                                    <label style="cursor:pointer;display:block;">
                                        <input type="radio" name="category" value="{{ $key }}" class="d-none edit-cat-radio">
                                        <div class="cat-card" style="--cat-color:{{ $meta['color'] }};">
                                            <span class="cat-emoji">{{ $meta['icon'] }}</span>
                                            {{ __($meta['label_key']) }}
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="expense-section">
                            <div style="font-size:10px;font-weight:700;color:#ef4444;margin-bottom:8px;">⬇ {{ __('EXPENSES') }}</div>
                            <div class="row g-2">
                                @foreach($expenseCats as $key => $meta)
                                <div class="col-4">
                                    <label style="cursor:pointer;display:block;">
                                        <input type="radio" name="category" value="{{ $key }}" class="d-none edit-cat-radio">
                                        <div class="cat-card" style="--cat-color:{{ $meta['color'] }};">
                                            <span class="cat-emoji">{{ $meta['icon'] }}</span>
                                            {{ __($meta['label_key']) }}
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="edit-amount"
                               class="f-input form-control" min="0.01" step="0.01" required>
                    </div>

                    {{-- Payment method --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Payment method') }}</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(\App\Models\BranchPayment::PAYMENT_METHODS as $pmKey => $pmMeta)
                            <label style="cursor:pointer;">
                                <input type="radio" name="payment_method" value="{{ $pmKey }}" class="d-none edit-pm-radio">
                                <div class="pm-card" style="--pm-color:{{ $pmMeta['color'] }};">
                                    <span>{{ $pmMeta['icon'] }}</span>
                                    {{ __($pmMeta['label_key']) }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Date --}}
                    <div class="mb-3">
                        <label class="f-label">{{ __('Date') }}</label>
                        <input type="date" name="paid_at" id="edit-paid-at" class="f-input form-control" required>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-1">
                        <label class="f-label">{{ __('Notes') }} <span style="font-weight:400;opacity:.5;">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" id="edit-notes" class="f-input form-control">
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4"
                            style="background:rgba(255,255,255,.07);font-weight:600;"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold"
                            style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;">
                        ✔ {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── OPEN DRAWER MODAL ──────────────────────────────────────────────── --}}
<div class="modal fade tx-modal" id="openDrawerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.open', $branch) }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">🔓 {{ __('Open Drawer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-muted" style="font-size:.82rem;">{{ __('Enter the starting cash amount in the drawer.') }}</p>
                    <div class="mb-3">
                        <label class="f-label">{{ __('Opening balance') }}</label>
                        <div class="d-flex gap-2">
                            <select name="currency" class="f-input form-select" style="max-width:110px;">
                                @foreach($currencies as $code => $cur)
                                <option value="{{ $code }}" {{ $code === $defaultCurrency ? 'selected' : '' }}>
                                    {{ $cur['symbol'] }} {{ $code }}
                                </option>
                                @endforeach
                            </select>
                            <input type="number" name="opening_balance" class="f-input form-control"
                                   min="0" step="0.01" value="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold" style="background:#22c55e;color:#fff;border:none;">
                        🔓 {{ __('Open Drawer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── CLOSE DRAWER MODAL ─────────────────────────────────────────────── --}}
@if($drawerSession)
<div class="modal fade tx-modal" id="closeDrawerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.close', [$branch, $drawerSession]) }}">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">🔒 {{ __('Close Drawer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3 p-3 rounded-3" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                            <span style="opacity:.6;">{{ __('Opening balance') }}</span>
                            <strong>{{ number_format($drawerSession->opening_balance, 2) }} {{ $drawerSession->currency }}</strong>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:.78rem;">
                            <span style="opacity:.6;">{{ __('Opened') }}</span>
                            <span>{{ $drawerSession->opened_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="f-label">{{ __('Actual cash count') }}</label>
                        <input type="number" name="closing_balance" class="f-input form-control"
                               min="0" step="0.01" required placeholder="0.00"
                               style="font-size:1.2rem;font-weight:800;text-align:center;">
                    </div>
                    <div class="mb-2">
                        <label class="f-label">{{ __('Notes') }} <span style="font-weight:400;opacity:.5;">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" class="f-input form-control" placeholder="{{ __('e.g. counted by manager') }}">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold" style="background:#ef4444;color:#fff;border:none;">
                        🔒 {{ __('Close Drawer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

</div>
@endsection

@push('scripts')
<script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {

    // ── Delete confirmation ──────────────────────────────────────────────
    window.confirmDelete = function(action) {
        document.getElementById('deleteTxForm').action = action;
        new bootstrap.Modal(document.getElementById('deleteTxModal')).show();
    };

    // ── Category picker ────────────────────────────────────────────────────
    document.querySelectorAll('.cat-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('#addTxModal .cat-card').forEach(function (c) { c.classList.remove('active'); });
            this.closest('label').querySelector('.cat-card').classList.add('active');

            var isAppt = (this.value === 'appointment');
            document.getElementById('paid-amount-row').style.display = isAppt ? '' : 'none';
            if (!isAppt) {
                document.getElementById('tx-paid').value = '';
                document.getElementById('diff-hint').style.display = 'none';
            }
        });
    });

    // ── Payment method picker ─────────────────────────────────────────────
    document.querySelectorAll('.pm-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('#addTxModal .pm-card').forEach(function (c) { c.classList.remove('active'); });
            this.closest('label').querySelector('.pm-card').classList.add('active');
        });
    });

    // ── Edit modal pickers ────────────────────────────────────────────────
    document.querySelectorAll('.edit-cat-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('#editTxModal .cat-card').forEach(function (c) { c.classList.remove('active'); });
            this.closest('label').querySelector('.cat-card').classList.add('active');
        });
    });
    document.querySelectorAll('.edit-pm-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('#editTxModal .pm-card').forEach(function (c) { c.classList.remove('active'); });
            this.closest('label').querySelector('.pm-card').classList.add('active');
        });
    });

    // ── Reconcile reason picker ──────────────────────────────────────────
    document.querySelectorAll('.reconcile-reason-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var modal = this.closest('.modal');
            modal.querySelectorAll('.pm-card').forEach(function (c) { c.classList.remove('active'); });
            this.closest('label').querySelector('.pm-card').classList.add('active');
        });
    });

    // ── Open edit modal ───────────────────────────────────────────────────
    var EDIT_BASE = '{{ route("company.branches.cash.update", [$branch, "__ID__"]) }}';
    window.openEditModal = function(tx) {
        var form = document.getElementById('editTxForm');
        form.action = EDIT_BASE.replace('__ID__', tx.id);

        document.getElementById('edit-amount').value = tx.amount;
        document.getElementById('edit-paid-at').value = tx.paid_at;
        document.getElementById('edit-notes').value = tx.notes || '';

        // Set category
        document.querySelectorAll('#editTxModal .cat-card').forEach(function(c) { c.classList.remove('active'); });
        document.querySelectorAll('.edit-cat-radio').forEach(function(r) {
            r.checked = (r.value === tx.category);
            if (r.checked) r.closest('label').querySelector('.cat-card').classList.add('active');
        });

        // Set payment method
        document.querySelectorAll('#editTxModal .pm-card').forEach(function(c) { c.classList.remove('active'); });
        document.querySelectorAll('.edit-pm-radio').forEach(function(r) {
            r.checked = (r.value === tx.payment_method);
            if (r.checked) r.closest('label').querySelector('.pm-card').classList.add('active');
        });

        new bootstrap.Modal(document.getElementById('editTxModal')).show();
    };

    // Show paid-amount row only for appointment by default
    document.getElementById('paid-amount-row').style.display = '';

    // ── Overpay / underpay hint ────────────────────────────────────────────
    function updateDiffHint() {
        var amount = parseFloat(document.getElementById('tx-amount').value) || 0;
        var paid   = parseFloat(document.getElementById('tx-paid').value)   || 0;
        var hint   = document.getElementById('diff-hint');
        if (!paid || !amount) { hint.style.display = 'none'; return; }
        var diff = paid - amount;
        if (Math.abs(diff) < 0.01) { hint.style.display = 'none'; return; }
        hint.style.display = '';
        if (diff > 0) {
            hint.className = 'diff-hint over';
            hint.style.background = 'rgba(34,197,94,.1)';
            hint.style.color = '#22c55e';
            hint.style.borderRadius = '10px';
            hint.style.padding = '8px 12px';
            hint.style.fontSize = '12px';
            hint.style.fontWeight = '600';
            hint.style.marginTop = '8px';
            hint.textContent = '⬆ {{ __("Overpayment") }}: +' + diff.toFixed(2) + ' → {{ $branch->overpayment_to === "employee" ? __("goes to employee tip") : __("added to treasury") }}';
        } else {
            hint.className = 'diff-hint under';
            hint.style.background = 'rgba(239,68,68,.1)';
            hint.style.color = '#ef4444';
            hint.style.borderRadius = '10px';
            hint.style.padding = '8px 12px';
            hint.style.fontSize = '12px';
            hint.style.fontWeight = '600';
            hint.style.marginTop = '8px';
            hint.textContent = '⬇ {{ __("Underpayment") }}: ' + diff.toFixed(2) + ' {{ __("(debt recorded)") }}';
        }
    }
    document.getElementById('tx-amount').addEventListener('input', updateDiffHint);
    document.getElementById('tx-paid').addEventListener('input', updateDiffHint);

    // ── Overpayment routing setting (AJAX) ────────────────────────────────
    window.saveOverpaymentSetting = function(val) {
        fetch('{{ route("company.branches.cash.overpayment", $branch) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ overpayment_to: val })
        });
        document.querySelectorAll('[name="overpayment_to_ui"]').forEach(function(r) {
            r.closest('label').querySelector('.cat-card').classList.toggle('active', r.value === val);
        });
    };

    // ── ApexCharts ────────────────────────────────────────────────────────
    var chartEl = document.getElementById('cashChart');
    if (chartEl) {
        var rawData = @json($chartData);
        var isDark  = document.documentElement.classList.contains('bk-theme-dark') ||
                      !document.documentElement.classList.contains('bk-theme-light');

        var isAr = {{ $isRtl ?? 'false' }};
        var monthsAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
        var monthsEn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        var isMonthly = rawData.length > 0 && rawData[0].mode === 'month';

        var dates = rawData.map(function(d) {
            var parts = d.date.split('-');
            var m = parseInt(parts[1]) - 1;
            var day = parseInt(parts[2]);
            if (isMonthly) {
                return isAr ? monthsAr[m] : monthsEn[m];
            }
            return isAr ? (day + ' ' + monthsAr[m]) : (monthsEn[m] + ' ' + day);
        });

        var totalPoints = rawData.length;

        var options = {
            chart: {
                type: 'area', height: 200,
                toolbar: { show: false },
                background: 'transparent',
                animations: { enabled: true, speed: 600, easing: 'easeinout' },
                zoom: { enabled: false },
                fontFamily: 'inherit',
                sparkline: { enabled: false },
            },
            series: [
                { name: '{{ __("Income") }}',   data: rawData.map(function(d){ return d.income;  }) },
                { name: '{{ __("Expenses") }}',  data: rawData.map(function(d){ return d.expense; }) },
            ],
            xaxis: {
                categories: dates,
                labels: {
                    rotate: -45,
                    rotateAlways: totalPoints > 10,
                    hideOverlappingLabels: true,
                    maxHeight: 60,
                    style: { fontSize: '9px', fontWeight: 600, colors: isDark ? '#64748b' : '#94a3b8' },
                    trim: true,
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tickAmount: Math.min(totalPoints, 12),
                tooltip: { enabled: false },
            },
            yaxis: {
                labels: {
                    style: { colors: isDark ? '#64748b' : '#94a3b8', fontSize: '10px', fontWeight: 600 },
                    formatter: function(v) {
                        if (v >= 1000000) return (v/1000000).toFixed(1) + 'M';
                        if (v >= 1000) return (v/1000).toFixed(0) + 'K';
                        return v.toFixed(0);
                    }
                },
            },
            colors: ['#22c55e', '#ef4444'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            stroke: { curve: 'smooth', width: 2.5 },
            dataLabels: { enabled: false },
            legend: {
                position: 'top',
                horizontalAlign: isAr ? 'right' : 'left',
                labels: { colors: isDark ? '#94a3b8' : '#64748b' },
                fontSize: '11px',
                fontWeight: 700,
                markers: { width: 8, height: 8, radius: 8 },
                itemMargin: { horizontal: 12 },
            },
            grid: {
                borderColor: isDark ? 'rgba(255,255,255,.04)' : 'rgba(0,0,0,.06)',
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
                padding: { left: 8, right: 8 },
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light',
                shared: true,
                intersect: false,
                style: { fontSize: '12px', fontFamily: 'inherit' },
                y: {
                    formatter: function(v) { return v ? v.toLocaleString() : '0'; }
                },
                marker: { show: true },
            },
            markers: {
                size: totalPoints <= 14 ? 4 : 0,
                strokeWidth: 2,
                strokeColors: isDark ? '#1e1e2d' : '#fff',
                hover: { size: 6 },
            },
            theme: { mode: isDark ? 'dark' : 'light' },
        };
        new ApexCharts(chartEl, options).render();
    }

})();
</script>
@endpush
