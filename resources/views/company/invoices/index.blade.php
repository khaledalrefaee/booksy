@extends('company.dashboard')

@push('company-styles')
<style>
/* ═══════════════ Invoices — iv-* ledger ═══════════════
   Token-driven (--bk-*), light + dark, RTL-safe via logical properties. */
.iv-page { --iv-r:14px; }
.iv-page a { text-decoration:none; }

/* ── Header ── */
.iv-head { margin-bottom:22px; }
.iv-title { font-family:var(--bk-serif); font-size:2rem; font-weight:600; letter-spacing:-.01em; line-height:1.15; color:var(--bk-text); margin:0; }
.iv-sub { margin:6px 0 0; color:var(--bk-text-muted); font-size:.92rem; max-width:62ch; line-height:1.55; }

/* ── Ledger summary: one panel, three figures ── */
.iv-ledger { display:grid; grid-template-columns:repeat(3,1fr); background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--iv-r); box-shadow:var(--bk-shadow); margin-bottom:18px; }
.iv-fig { padding:16px 22px; min-width:0; }
.iv-fig + .iv-fig { border-inline-start:1px solid var(--bk-border); }
.iv-fig-label { display:flex; align-items:center; gap:7px; font-size:.78rem; font-weight:600; color:var(--bk-text-muted); margin-bottom:6px; }
.iv-fig-label svg { width:15px; height:15px; stroke-width:2; color:var(--bk-accent); }
.iv-fig.is-due .iv-fig-label svg { color:var(--bk-gold-strong); }
.iv-fig-value { font-family:var(--bk-serif); font-size:1.6rem; font-weight:600; line-height:1.1; color:var(--bk-text); font-variant-numeric:tabular-nums lining-nums; white-space:nowrap; }
.iv-fig-value small { font-family:inherit; font-size:.8rem; font-weight:500; color:var(--bk-text-muted); margin-inline-start:5px; letter-spacing:0; }

/* ── Status tabs ── */
.iv-tabs { display:flex; gap:6px; overflow-x:auto; padding-bottom:2px; margin-bottom:12px; scrollbar-width:none; }
.iv-tabs::-webkit-scrollbar { display:none; }
.iv-tab { flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 14px; border-radius:10px; border:1px solid transparent; color:var(--bk-text-muted); font-size:.86rem; font-weight:600; transition:background .15s, color .15s, border-color .15s; }
.iv-tab:hover { color:var(--bk-text); background:var(--bk-surface-2); }
.iv-tab.is-on { background:var(--bk-accent-wash); color:var(--bk-accent); border-color:color-mix(in srgb, var(--bk-accent) 28%, transparent); }
.iv-tab-n { font-size:.74rem; font-weight:700; font-variant-numeric:tabular-nums; min-width:20px; text-align:center; padding:1px 6px; border-radius:20px; background:var(--bk-surface-2); color:var(--bk-text-muted); }
.iv-tab.is-on .iv-tab-n { background:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.iv-tab:focus-visible, .iv-row-link:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }

/* ── Toolbar ── */
.iv-toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--iv-r); padding:10px; margin-bottom:14px; box-shadow:var(--bk-shadow); }
.iv-search { position:relative; flex:1 1 280px; min-width:200px; }
.iv-search svg { position:absolute; inset-inline-start:13px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:var(--bk-text-muted); pointer-events:none; }
.iv-search input, .iv-select { height:44px; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.iv-search input { width:100%; padding-inline:40px 14px; }
.iv-search input::placeholder { color:var(--bk-text-muted); opacity:1; }
.iv-select { padding-inline:12px 34px; min-width:170px; cursor:pointer; }
.iv-search input:focus, .iv-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.iv-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:44px; padding:0 20px; border-radius:10px; border:1px solid transparent; font-size:.88rem; font-weight:600; cursor:pointer; transition:background .15s, border-color .15s, color .15s; }
.iv-btn-primary { background:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.iv-btn-primary:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); }
.iv-btn-quiet { background:transparent; color:var(--bk-text-muted); border-color:var(--bk-border); }
.iv-btn-quiet:hover { color:var(--bk-danger); border-color:var(--bk-danger); }
.iv-btn svg { width:15px; height:15px; stroke-width:2; }
.iv-btn:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }

/* ── Table ── */
.iv-panel { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--iv-r); box-shadow:var(--bk-shadow); overflow:hidden; }
.iv-table { width:100%; border-collapse:collapse; }
.iv-table th { padding:12px 18px; font-size:.78rem; font-weight:700; color:var(--bk-text-muted); text-align:start; background:var(--bk-surface-2); border-bottom:1px solid var(--bk-border); white-space:nowrap; }
.iv-table td { padding:14px 18px; vertical-align:middle; border-bottom:1px solid var(--bk-border); color:var(--bk-text); font-size:.9rem; }
.iv-table tbody tr:last-child td { border-bottom:0; }
.iv-table tbody tr { position:relative; transition:background .15s; }
.iv-table tbody tr:hover { background:var(--bk-sidebar-hover); }
.iv-num { text-align:end !important; }
.iv-no { font-weight:700; color:var(--bk-accent); font-variant-numeric:tabular-nums; direction:ltr; unicode-bidi:isolate; display:inline-block; letter-spacing:.01em; }
.iv-row-link::after { content:''; position:absolute; inset:0; }
.iv-cust { font-weight:600; }
.iv-meta { display:block; margin-top:2px; font-size:.78rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; direction:ltr; unicode-bidi:isolate; text-align:start; }
.iv-muted { color:var(--bk-text-muted); }
.iv-amount { font-weight:700; font-variant-numeric:tabular-nums lining-nums; white-space:nowrap; }
.iv-amount small { font-size:.74rem; font-weight:500; color:var(--bk-text-muted); margin-inline-start:4px; }
.iv-date { font-variant-numeric:tabular-nums; white-space:nowrap; color:var(--bk-text-muted); font-size:.86rem; }

/* Status pill — text + dot, never colour alone */
.iv-status { display:inline-flex; align-items:center; gap:7px; padding:4px 12px 4px 10px; border-radius:20px; font-size:.78rem; font-weight:700; white-space:nowrap; }
[dir="rtl"] .iv-status { padding:4px 10px 4px 12px; }
.iv-status::before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; flex:0 0 auto; }

/* ── Empty ── */
.iv-empty { padding:56px 20px; text-align:center; }
.iv-empty-ic { width:52px; height:52px; border-radius:14px; margin:0 auto 14px; display:flex; align-items:center; justify-content:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.iv-empty-ic svg { width:24px; height:24px; stroke-width:1.75; }
.iv-empty h2 { font-family:var(--bk-serif); font-size:1.25rem; font-weight:600; margin:0 0 6px; color:var(--bk-text); }
.iv-empty p { margin:0 auto; max-width:46ch; color:var(--bk-text-muted); font-size:.88rem; line-height:1.6; }
.iv-empty .iv-btn { margin-top:16px; }

.iv-foot { margin-top:16px; }

/* ── Phones: rows become compact cards ── */
@media (max-width: 767.98px) {
    .iv-title { font-size:1.6rem; }
    .iv-ledger { grid-template-columns:1fr 1fr; }
    .iv-fig { padding:14px 16px; }
    .iv-fig:first-child { grid-column:1 / -1; border-bottom:1px solid var(--bk-border); }
    .iv-fig:nth-child(2) { border-inline-start:0; }
    .iv-fig-value { font-size:1.3rem; }
    .iv-table thead { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
    .iv-table, .iv-table tbody { display:block; }
    .iv-table tbody tr { display:grid; grid-template-columns:1fr auto; gap:4px 12px; padding:14px 16px; border-bottom:1px solid var(--bk-border); }
    .iv-table tbody tr:last-child { border-bottom:0; }
    .iv-table td { display:block; padding:0; border:0; }
    .iv-table td.c-no { grid-column:1; grid-row:1; }
    .iv-table td.c-status { grid-column:2; grid-row:1; justify-self:end; }
    .iv-table td.c-cust { grid-column:1 / -1; grid-row:2; }
    .iv-table td.c-branch { grid-column:1; grid-row:3; font-size:.8rem; }
    .iv-table td.c-total { grid-column:2; grid-row:3; grid-row-end:5; align-self:end; }
    .iv-table td.c-date { grid-column:1; grid-row:4; font-size:.78rem; }
    .iv-num { text-align:end !important; }
}
@media (prefers-reduced-motion: reduce) { .iv-tab, .iv-table tbody tr, .iv-btn { transition:none; } }
</style>
@endpush

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';

    $statusLabels = [
        'draft'    => __('Draft'),
        'issued'   => __('Issued'),
        'paid'     => __('Paid'),
        'partial'  => __('Partial'),
        'refunded' => __('Refunded'),
        'void'     => __('Void'),
    ];

    $currentStatus = request('status');
    $allCount      = $statusCounts->sum();
    $hasFilters    = request()->hasAny(['search', 'status'])
                  || (! ($branchContext ?? null) && request()->filled('branch_id'));

    // Tab links keep the search + branch scope, only the status changes.
    $tabUrl = fn (?string $status) => route('company.invoices.index', array_filter([
        'search'    => request('search'),
        'branch_id' => request('branch_id'),
        'status'    => $status,
    ], fn ($v) => filled($v)));

    $money = fn (float $v) => number_format($v, 2);
@endphp

<div class="page-content iv-page">

    {{-- Header --}}
    <div class="iv-head">
        <h1 class="iv-title">{{ __('Invoices') }}</h1>
        <p class="iv-sub">{{ __('All issued invoices for your business') }}</p>
    </div>

    @include('company.partials.flash')

    {{-- Ledger summary --}}
    @if($allCount > 0)
    <section class="iv-ledger" aria-label="{{ __('Invoices summary') }}">
        <div class="iv-fig">
            <div class="iv-fig-label"><i data-feather="file-text"></i>{{ __('Invoices') }}</div>
            <div class="iv-fig-value">{{ number_format($allCount) }}</div>
        </div>
        <div class="iv-fig">
            <div class="iv-fig-label"><i data-feather="check-circle"></i>{{ __('Collected') }}</div>
            <div class="iv-fig-value">{{ $money($summary['collected']) }}<small>{{ $summary['currency'] }}</small></div>
        </div>
        <div class="iv-fig is-due">
            <div class="iv-fig-label"><i data-feather="clock"></i>{{ __('Outstanding') }}</div>
            <div class="iv-fig-value">{{ $money($summary['outstanding']) }}<small>{{ $summary['currency'] }}</small></div>
        </div>
    </section>
    @endif

    {{-- Status tabs --}}
    <nav class="iv-tabs" aria-label="{{ __('Status') }}">
        <a href="{{ $tabUrl(null) }}" class="iv-tab {{ ! $currentStatus ? 'is-on' : '' }}" @if(! $currentStatus) aria-current="page" @endif>
            {{ __('All') }}<span class="iv-tab-n">{{ number_format($allCount) }}</span>
        </a>
        @foreach($statusLabels as $val => $lbl)
            @continue(($statusCounts[$val] ?? 0) === 0 && $currentStatus !== $val)
            <a href="{{ $tabUrl($val) }}" class="iv-tab {{ $currentStatus === $val ? 'is-on' : '' }}" @if($currentStatus === $val) aria-current="page" @endif>
                {{ $lbl }}<span class="iv-tab-n">{{ number_format($statusCounts[$val] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Search + branch --}}
    <form method="GET" class="iv-toolbar" data-filter-sheet="{{ __('Filters') }}" role="search">
        @if($currentStatus)
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        @endif
        <div class="iv-search">
            <i data-feather="search"></i>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('Search by invoice number, customer or phone') }}"
                   aria-label="{{ __('Search by invoice number, customer or phone') }}">
        </div>
        @if(! ($branchContext ?? null))
            <select name="branch_id" class="iv-select" aria-label="{{ __('Branch') }}">
                <option value="">{{ __('All branches') }}</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->localizedName() }}</option>
                @endforeach
            </select>
        @endif
        <button class="iv-btn iv-btn-primary">{{ __('Filter') }}</button>
        @if($hasFilters)
            <a href="{{ route('company.invoices.index') }}" class="iv-btn iv-btn-quiet">
                <i data-feather="x"></i>{{ __('Clear filters') }}
            </a>
        @endif
    </form>

    {{-- List --}}
    <div class="iv-panel">
        @if($invoices->count())
        <table class="iv-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('Invoice #') }}</th>
                    <th scope="col">{{ __('Customer') }}</th>
                    <th scope="col">{{ __('Branch') }}</th>
                    <th scope="col" class="iv-num">{{ __('Total') }}</th>
                    <th scope="col">{{ __('Status') }}</th>
                    <th scope="col">{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($invoices as $inv)
                <tr>
                    <td class="c-no">
                        <a href="{{ route('company.invoices.show', $inv) }}" class="iv-row-link iv-no">{{ $inv->invoice_number }}</a>
                    </td>
                    <td class="c-cust">
                        <span class="iv-cust">{{ $inv->customer_name ?: '—' }}</span>
                        @if($inv->customer_phone)
                            <span class="iv-meta">{{ $inv->customer_phone }}</span>
                        @endif
                    </td>
                    <td class="c-branch iv-muted">{{ $inv->branch?->localizedName() ?? '—' }}</td>
                    <td class="c-total iv-num">
                        <span class="iv-amount">{{ $money((float) $inv->total) }}<small>{{ $inv->currency }}</small></span>
                    </td>
                    <td class="c-status">
                        <span class="iv-status bk-inv-status-{{ $inv->status }}">{{ $statusLabels[$inv->status] ?? $inv->status }}</span>
                    </td>
                    <td class="c-date iv-date">{{ $inv->created_at->translatedFormat('d M Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="iv-empty">
            <div class="iv-empty-ic"><i data-feather="file-text"></i></div>
            @if($hasFilters)
                <h2>{{ __('No invoices match your filters.') }}</h2>
                <p>{{ __('Try a different search or clear the filters to see every invoice.') }}</p>
                <a href="{{ route('company.invoices.index') }}" class="iv-btn iv-btn-primary">{{ __('Clear filters') }}</a>
            @else
                <h2>{{ __('No invoices found.') }}</h2>
                <p>{{ __('Invoices are created automatically when an appointment is completed.') }}</p>
            @endif
        </div>
        @endif
    </div>

    <div class="iv-foot">{{ $invoices->links() }}</div>
</div>
@endsection
