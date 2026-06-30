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

/* ─── Bulk action bar ──────────────────────────────────────────────────── */
.bulk-bar {
    position:sticky; top:60px; z-index:50;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:#fff; border-radius:12px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
    box-shadow:0 4px 20px rgba(102,126,234,.4);
}
.bulk-bar.show { display:flex; }
.bulk-bar .count { font-weight:800; font-size:14px; }
.bulk-bar .btn { font-size:12px; font-weight:700; border-radius:20px; padding:4px 14px; }

/* ─── Filter pills ─────────────────────────────────────────────────────── */
.filter-pill {
    font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px;
    text-decoration:none; border:1px solid rgba(255,255,255,.1);
    color:var(--text-color); transition:all .15s;
}
.filter-pill:hover { background:rgba(102,126,234,.1); color:#667eea; }
.filter-pill.active { background:#667eea; color:#fff; border-color:#667eea; }

/* ─── Multi-currency row ───────────────────────────────────────────────── */
.currency-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
.currency-row .remove-currency { background:none; border:none; color:#ef4444; cursor:pointer; font-size:18px; padding:0 4px; }
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
            @php $colSize = $summary->count() === 1 ? 'col-12' : ($summary->count() === 2 ? 'col-12 col-md-6' : 'col-12 col-md-4'); @endphp
            <div class="{{ $colSize }}">
                <div class="balance-card">
                    <div class="balance-label">{{ __('Net balance') }} · {{ $cur }}</div>
                    <div class="balance-value {{ $s['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $s['net'] >= 0 ? '+' : '' }}{{ number_format($s['net'], 0) }}
                        <span>{{ $sym }}</span>
                    </div>
                    <div class="d-flex gap-3 mt-3 flex-wrap">
                        <div class="cash-stat">
                            <div class="cash-stat-icon" style="background:rgba(34,197,94,.15);">⬆️</div>
                            <div style="min-width:0;">
                                <div class="cash-stat-val" style="color:#22c55e;">{{ number_format($s['income'],0) }} {{ $sym }}</div>
                                <div class="cash-stat-lbl">{{ __('Income') }}</div>
                            </div>
                        </div>
                        <div class="cash-stat">
                            <div class="cash-stat-icon" style="background:rgba(239,68,68,.15);">⬇️</div>
                            <div style="min-width:0;">
                                <div class="cash-stat-val" style="color:#ef4444;">{{ number_format($s['expense'],0) }} {{ $sym }}</div>
                                <div class="cash-stat-lbl">{{ __('Expenses') }}</div>
                            </div>
                        </div>
                        <div class="cash-stat">
                            <div class="cash-stat-icon" style="background:rgba(100,116,139,.15);">📊</div>
                            <div>
                                <div class="cash-stat-val">{{ $totalTxCount }}</div>
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
                @foreach($drawerSession->balances as $bal)
                <span class="me-2">{{ number_format($bal->opening_amount, 2) }} {{ $bal->currency }}</span>
                @endforeach
                · {{ $drawerSession->opened_at->diffForHumans() }}
                @if($drawerSession->opened_by_name)
                · <strong>{{ $drawerSession->opened_by_name }}</strong>
                @endif
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
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-size:11px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;">
                {{ __('Recent drawer sessions') }}
            </div>
            <a href="{{ route('company.branches.cash.drawer.archive', $branch) }}"
               style="font-size:11px;font-weight:700;color:#667eea;text-decoration:none;">
                {{ __('View all') }} →
            </a>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @foreach($recentDrawers as $ds)
            <div class="drawer-history-card">
                <div style="font-size:.7rem;font-weight:700;opacity:.5;margin-bottom:4px;">{{ $ds->closed_at?->format('M d, H:i') }}</div>
                @foreach($ds->balances as $bal)
                <div style="margin-bottom:2px;">
                    <div style="font-size:.78rem;font-weight:800;line-height:1.3;">
                        {{ number_format($bal->closing_amount ?? $bal->opening_amount, 0) }}
                        <span style="font-size:.65rem;opacity:.5;">{{ $bal->currency }}</span>
                    </div>
                    @if($bal->variance !== null && $bal->variance != 0)
                    <div style="font-size:.65rem;font-weight:700;color:{{ $bal->variance > 0 ? '#f59e0b' : '#ef4444' }};line-height:1;">
                        {{ $bal->variance > 0 ? '↑ +' : '↓ ' }}{{ number_format(abs($bal->variance), 0) }}
                    </div>
                    @elseif($bal->variance !== null)
                    <div style="font-size:.65rem;font-weight:700;color:#22c55e;line-height:1;">✓</div>
                    @endif
                </div>
                @endforeach
                @if($ds->closed_by_name || $ds->opened_by_name)
                <div style="font-size:.6rem;opacity:.4;margin-top:2px;">
                    👤 {{ $ds->closed_by_name ?? $ds->opened_by_name }}
                </div>
                @endif

                {{-- Status + Action buttons --}}
                <div class="d-flex gap-1 flex-wrap mt-2" style="justify-content:center;">
                    @if($ds->isClosed())
                    <button class="btn btn-sm px-2"
                            style="font-size:.58rem;font-weight:700;background:rgba(102,126,234,.1);color:#667eea;border:none;border-radius:6px;"
                            data-bs-toggle="modal" data-bs-target="#reconcileModal-{{ $ds->id }}">
                        📋 {{ __('Reconcile') }}
                    </button>
                    @elseif($ds->isReconciled())
                    @php $rMeta = \App\Models\CashDrawerSession::RECONCILE_REASONS[$ds->reconcile_reason] ?? null; @endphp
                    <div style="font-size:.58rem;font-weight:700;color:#22c55e;">✓ {{ __('Reconciled') }}</div>
                    @endif

                    @if($ds->isClosed() || $ds->isReconciled())
                    <button class="btn btn-sm px-2"
                            style="font-size:.58rem;font-weight:700;background:rgba(34,197,94,.1);color:#22c55e;border:none;border-radius:6px;"
                            data-bs-toggle="modal" data-bs-target="#reopenModal-{{ $ds->id }}">
                        🔓 {{ __('Reopen') }}
                    </button>
                    @endif

                    <button class="btn btn-sm px-2"
                            style="font-size:.58rem;font-weight:700;background:rgba(255,255,255,.05);color:var(--text-color);opacity:.6;border:none;border-radius:6px;"
                            data-bs-toggle="modal" data-bs-target="#editNotesModal-{{ $ds->id }}">
                        ✏️
                    </button>

                    <button class="btn btn-sm px-2"
                            style="font-size:.58rem;font-weight:700;background:rgba(239,68,68,.1);color:#ef4444;border:none;border-radius:6px;"
                            data-bs-toggle="modal" data-bs-target="#voidModal-{{ $ds->id }}">
                        🚫
                    </button>
                </div>
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
            <form method="POST" action="{{ route('company.branches.cash.drawer.reconcile', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">📋 {{ __('Reconcile Drawer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    @foreach($ds->balances as $bal)
                    @php $v = (float) ($bal->variance ?? 0); @endphp
                    <div class="p-3 rounded-3 mb-3 text-center" style="background:{{ $v == 0 ? 'rgba(34,197,94,.08)' : ($v > 0 ? 'rgba(245,158,11,.08)' : 'rgba(239,68,68,.08)') }};border:1px solid {{ $v == 0 ? 'rgba(34,197,94,.15)' : ($v > 0 ? 'rgba(245,158,11,.15)' : 'rgba(239,68,68,.15)') }};">
                        <div style="font-size:.72rem;opacity:.6;font-weight:600;">{{ __('Variance') }} · {{ $bal->currency }}</div>
                        <div style="font-size:1.4rem;font-weight:800;color:{{ $v == 0 ? '#22c55e' : ($v > 0 ? '#f59e0b' : '#ef4444') }};">
                            {{ $v > 0 ? '+' : '' }}{{ number_format($v, 2) }} {{ $bal->currency }}
                        </div>
                        <div class="d-flex justify-content-between mt-2 px-2" style="font-size:.78rem;">
                            <span style="opacity:.5;">{{ __('Expected') }}: {{ number_format($bal->expected_amount, 2) }}</span>
                            <span style="opacity:.5;">{{ __('Actual') }}: {{ number_format($bal->closing_amount, 2) }}</span>
                        </div>
                    </div>
                    @endforeach

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

{{-- ─── REOPEN / VOID / EDIT NOTES MODALS ──────────────────────────────── --}}
@foreach($recentDrawers as $ds)
@if($ds->isVoided()) @continue @endif

{{-- Reopen Modal --}}
@if($ds->isClosed() || $ds->isReconciled())
<div class="modal fade tx-modal" id="reopenModal-{{ $ds->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.reopen', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                @csrf @method('PUT')
                <div class="modal-body text-center p-4">
                    <div style="font-size:40px;margin-bottom:12px;">🔓</div>
                    <h6 class="fw-bold mb-2">{{ __('Reopen Drawer') }} #{{ $ds->id }}?</h6>
                    <p class="text-muted small mb-3">
                        {{ __('This will reset the closing data and set the drawer back to open.') }}
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm rounded-pill px-4"
                                style="background:rgba(255,255,255,.07);font-weight:600;"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm rounded-pill px-4 fw-bold"
                                style="background:#22c55e;color:#fff;border:none;">
                            🔓 {{ __('Reopen') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Void Modal --}}
<div class="modal fade tx-modal" id="voidModal-{{ $ds->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.void', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">🚫 {{ __('Void Drawer') }} #{{ $ds->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="p-3 rounded-3 mb-3" style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.12);font-size:.82rem;">
                        ⚠️ {{ __('This session will be marked as voided and excluded from reports. The record remains visible for audit.') }}
                    </div>
                    <div class="mb-1">
                        <label class="f-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                        <input type="text" name="void_reason" class="f-input form-control" required
                               placeholder="{{ __('e.g. Opened by mistake, duplicate session...') }}">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                        🚫 {{ __('Void') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Notes Modal --}}
<div class="modal fade tx-modal" id="editNotesModal-{{ $ds->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.update-notes', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">✏️ {{ __('Edit Notes') }} — #{{ $ds->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="f-label">{{ __('Session notes') }}</label>
                        <textarea name="notes" class="f-input form-control" rows="2"
                                  placeholder="{{ __('General notes...') }}">{{ $ds->notes }}</textarea>
                    </div>
                    @if($ds->isReconciled())
                    <div class="mb-1">
                        <label class="f-label">{{ __('Reconciliation notes') }}</label>
                        <textarea name="reconcile_notes" class="f-input form-control" rows="2">{{ $ds->reconcile_notes }}</textarea>
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;">
                        ✔ {{ __('Save') }}
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

        {{-- Filter pills --}}
        <div class="d-flex gap-2 flex-wrap mb-3">
            <a href="{{ route('company.branches.cash.index', array_merge([$branch], request()->only('period','from','to'))) }}"
               class="filter-pill {{ !$filterType && !$filterCategory ? 'active' : '' }}">
                {{ __('All') }}
            </a>
            <a href="{{ route('company.branches.cash.index', array_merge([$branch], request()->only('period','from','to'), ['filter_type'=>'income'])) }}"
               class="filter-pill {{ $filterType==='income' ? 'active' : '' }}" style="color:#22c55e;">
                ⬆ {{ __('Income') }}
            </a>
            <a href="{{ route('company.branches.cash.index', array_merge([$branch], request()->only('period','from','to'), ['filter_type'=>'expense'])) }}"
               class="filter-pill {{ $filterType==='expense' ? 'active' : '' }}" style="color:#ef4444;">
                ⬇ {{ __('Expenses') }}
            </a>
            @foreach($cats as $catKey => $catMeta)
            <a href="{{ route('company.branches.cash.index', array_merge([$branch], request()->only('period','from','to'), ['filter_category'=>$catKey])) }}"
               class="filter-pill {{ $filterCategory===$catKey ? 'active' : '' }}">
                {{ $catMeta['icon'] }} {{ __($catMeta['label_key']) }}
            </a>
            @endforeach
        </div>

        {{-- Bulk action bar --}}
        <div class="bulk-bar" id="bulkBar">
            <span class="count"><span id="selectedCount">0</span> {{ __('selected') }}</span>
            <button type="button" class="btn btn-light btn-sm" onclick="uncheckAll()">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSelectedModal">
                🗑 {{ __('Delete selected') }}
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                {{ __('Delete all') }} ({{ $totalTxCount }})
            </button>
        </div>

        {{-- Transactions grouped by date --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" id="selectAll" onchange="toggleAll(this.checked)" style="width:16px;height:16px;cursor:pointer;">
                        <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;">
                            {{ __('Transactions') }}
                        </div>
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
                    <input type="checkbox" class="tx-checkbox" value="{{ $tx->id }}" onchange="updateBulkBar()"
                           style="width:15px;height:15px;cursor:pointer;flex-shrink:0;">
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
                            @if($tx->recordedBy) · <span style="opacity:.7;">👤 {{ $tx->recordedBy->name }}</span>@endif
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
                    <form id="deleteTxForm" method="POST" onsubmit="disableSubmit(this)">
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

{{-- ─── DELETE SELECTED MODAL ────────────────────────────────────────── --}}
<div class="modal fade tx-modal" id="deleteSelectedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                <h6 class="fw-bold mb-2">{{ __('Delete selected transactions?') }}</h6>
                <p class="text-muted small mb-1">
                    {{ __('This will delete') }} <strong id="deleteSelectedCount">0</strong> {{ __('transactions.') }}
                </p>
                <p class="text-muted small mb-3">{{ __('This action cannot be undone.') }}</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm rounded-pill px-4"
                            style="background:rgba(255,255,255,.07);font-weight:600;"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form method="POST" action="{{ route('company.branches.cash.destroy-selected', $branch) }}" id="bulkDeleteForm" onsubmit="disableSubmit(this)">
                        @csrf @method('DELETE')
                        <input type="hidden" name="ids" id="bulkIds">
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                            🗑 {{ __('Delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── DELETE ALL MODAL ──────────────────────────────────────────────── --}}
<div class="modal fade tx-modal" id="deleteAllModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
                <h6 class="fw-bold mb-2">{{ __('Delete ALL transactions?') }}</h6>
                <p class="text-muted small mb-1">
                    {{ __('This will delete all') }} <strong>{{ $totalTxCount }}</strong> {{ __('transactions for this period.') }}
                </p>
                <p class="text-muted small mb-3" style="color:#ef4444 !important;">{{ __('This action cannot be undone.') }}</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm rounded-pill px-4"
                            style="background:rgba(255,255,255,.07);font-weight:600;"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form method="POST" action="{{ route('company.branches.cash.destroy-all', array_merge([$branch], request()->only('period','from','to'))) }}" onsubmit="disableSubmit(this)">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                            ⚠️ {{ __('Delete all') }}
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
            <form method="POST" action="{{ route('company.branches.cash.store', $branch) }}" id="txForm" onsubmit="disableSubmit(this)">
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

                    {{-- Paid amount (for overpay/underpay) --}}
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

                    {{-- Date --}}
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
            <form method="POST" id="editTxForm" onsubmit="disableSubmit(this)">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="font-family:'Poppins',sans-serif;">
                        ✏️ {{ __('Edit Transaction') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">

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

                    <div class="mb-3">
                        <label class="f-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="edit-amount"
                               class="f-input form-control" min="0.01" step="0.01" required>
                    </div>

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

                    <div class="mb-3">
                        <label class="f-label">{{ __('Date') }}</label>
                        <input type="date" name="paid_at" id="edit-paid-at" class="f-input form-control" required>
                    </div>

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

{{-- ─── OPEN DRAWER MODAL (multi-currency) ─────────────────────────────── --}}
<div class="modal fade tx-modal" id="openDrawerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.open', $branch) }}" onsubmit="disableSubmit(this)">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">🔓 {{ __('Open Drawer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-muted" style="font-size:.82rem;">{{ __('Enter the starting cash amount for each currency.') }}</p>

                    <div id="openBalances">
                        <div class="currency-row">
                            <select name="balances[0][currency]" class="f-input form-select" style="max-width:110px;">
                                @foreach($currencies as $code => $cur)
                                <option value="{{ $code }}" {{ $code === $defaultCurrency ? 'selected' : '' }}>{{ $cur['symbol'] }} {{ $code }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="balances[0][opening_amount]" class="f-input form-control"
                                   min="0" step="0.01" value="0" required placeholder="0.00">
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm mt-2"
                            style="font-size:11px;font-weight:700;color:#667eea;background:rgba(102,126,234,.1);border:none;border-radius:8px;padding:4px 12px;"
                            onclick="addCurrencyRow('openBalances','opening_amount')">
                        + {{ __('Add currency') }}
                    </button>
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

{{-- ─── CLOSE DRAWER MODAL (multi-currency) ───────────────────────────── --}}
@if($drawerSession)
<div class="modal fade tx-modal" id="closeDrawerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.branches.cash.drawer.close', [$branch, $drawerSession]) }}" onsubmit="disableSubmit(this)">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">🔒 {{ __('Close Drawer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3 p-3 rounded-3" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                            <span style="opacity:.6;">{{ __('Opened') }}</span>
                            <span>{{ $drawerSession->opened_at->diffForHumans() }}
                                @if($drawerSession->opened_by_name) · {{ $drawerSession->opened_by_name }} @endif
                            </span>
                        </div>
                    </div>

                    <div id="expectedBalances" class="mb-3" style="display:none;"></div>

                    @foreach($drawerSession->balances as $i => $bal)
                    <div class="mb-3 p-3 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);">
                        <div class="d-flex justify-content-between mb-2" style="font-size:.82rem;font-weight:700;">
                            <span>{{ $bal->currency }}</span>
                            <span style="opacity:.5;">{{ __('Opening') }}: {{ number_format($bal->opening_amount, 2) }}</span>
                        </div>
                        <div class="expected-line" data-currency="{{ $bal->currency }}" style="font-size:.78rem;color:#667eea;margin-bottom:8px;display:none;">
                            {{ __('Expected') }}: <strong class="expected-val">—</strong>
                        </div>
                        <input type="hidden" name="balances[{{ $i }}][currency]" value="{{ $bal->currency }}">
                        <label class="f-label">{{ __('Actual cash count') }}</label>
                        <input type="number" name="balances[{{ $i }}][closing_amount]" class="f-input form-control"
                               min="0" step="0.01" required placeholder="0.00"
                               style="font-size:1.1rem;font-weight:800;text-align:center;">
                    </div>
                    @endforeach

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

    // ── Double-submit guard ──────────────────────────────────────────────
    window.disableSubmit = function(form) {
        var btns = form.querySelectorAll('button[type="submit"]');
        btns.forEach(function(b) { b.disabled = true; b.style.opacity = '.5'; });
    };

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

        document.querySelectorAll('#editTxModal .cat-card').forEach(function(c) { c.classList.remove('active'); });
        document.querySelectorAll('.edit-cat-radio').forEach(function(r) {
            r.checked = (r.value === tx.category);
            if (r.checked) r.closest('label').querySelector('.cat-card').classList.add('active');
        });

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

    // ── Multi-currency repeater ───────────────────────────────────────────
    var currencyOptions = @json(collect($currencies)->map(fn($c, $code) => ['code' => $code, 'symbol' => $c['symbol']])->values());
    var rowCounters = { openBalances: 1 };

    window.addCurrencyRow = function(containerId, fieldName) {
        var container = document.getElementById(containerId);
        var idx = rowCounters[containerId]++;
        var div = document.createElement('div');
        div.className = 'currency-row';

        var select = '<select name="balances[' + idx + '][currency]" class="f-input form-select" style="max-width:110px;">';
        currencyOptions.forEach(function(c) {
            select += '<option value="' + c.code + '">' + c.symbol + ' ' + c.code + '</option>';
        });
        select += '</select>';

        div.innerHTML = select +
            '<input type="number" name="balances[' + idx + '][' + fieldName + ']" class="f-input form-control" min="0" step="0.01" value="0" required placeholder="0.00">' +
            '<button type="button" class="remove-currency" onclick="this.parentElement.remove()">✕</button>';
        container.appendChild(div);
    };

    // ── Bulk select ───────────────────────────────────────────────────────
    window.updateBulkBar = function() {
        var checked = document.querySelectorAll('.tx-checkbox:checked');
        var bar = document.getElementById('bulkBar');
        var count = document.getElementById('selectedCount');
        var modalCount = document.getElementById('deleteSelectedCount');
        if (checked.length > 0) {
            bar.classList.add('show');
            count.textContent = checked.length;
            if (modalCount) modalCount.textContent = checked.length;
            var ids = Array.from(checked).map(function(c) { return c.value; });
            document.getElementById('bulkIds').value = JSON.stringify(ids);
        } else {
            bar.classList.remove('show');
        }
    };

    window.toggleAll = function(checked) {
        document.querySelectorAll('.tx-checkbox').forEach(function(c) { c.checked = checked; });
        updateBulkBar();
    };

    window.uncheckAll = function() {
        document.getElementById('selectAll').checked = false;
        toggleAll(false);
    };

    // ── Load expected balance for close drawer ────────────────────────────
    @if($drawerSession)
    var closeModal = document.getElementById('closeDrawerModal');
    if (closeModal) {
        closeModal.addEventListener('show.bs.modal', function() {
            fetch('{{ route("company.branches.cash.drawer.expected", [$branch, $drawerSession]) }}')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    data.balances.forEach(function(b) {
                        var line = document.querySelector('.expected-line[data-currency="' + b.currency + '"]');
                        if (line) {
                            line.style.display = '';
                            line.querySelector('.expected-val').textContent = parseFloat(b.expected).toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    });
                });
        });
    }
    @endif

    // ── Bulk delete form fix: send ids as array ───────────────────────────
    document.getElementById('bulkDeleteForm').addEventListener('submit', function(e) {
        var idsField = document.getElementById('bulkIds');
        var ids = JSON.parse(idsField.value || '[]');
        idsField.remove();
        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            e.target.appendChild(input);
        });
    });

    // ── ApexCharts ────────────────────────────────────────────────────────
    var chartEl = document.getElementById('cashChart');
    if (chartEl) {
        var rawData = @json($chartData);
        var isDark  = document.documentElement.classList.contains('bk-theme-dark') ||
                      !document.documentElement.classList.contains('bk-theme-light');

        var isAr = {{ ($isRtl ?? false) ? 'true' : 'false' }};
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
