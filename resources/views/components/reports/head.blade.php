@props(['title', 'subtitle' => null, 'active' => 'profit-loss', 'actions' => null])

{{--
    Reports module header: the two report pages on top of the generic section-head
    component, plus the shared rp-* styles (period bar, ledger, ranked lists,
    tables) so both pages use one visual language. Token-driven (--bk-*),
    RTL-safe, both themes.
--}}

@once
@push('company-styles')
<style>
/* ── Period bar ── */
.rp-bar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:10px; margin-bottom:18px; box-shadow:var(--bk-shadow); }
.rp-step { display:inline-flex; align-items:center; gap:2px; }
.rp-step a { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text-muted); transition:color .15s, border-color .15s; }
.rp-step a:hover { color:var(--bk-accent); border-color:var(--bk-accent); }
.rp-step a svg { width:16px; height:16px; stroke-width:2.2; }
[dir="rtl"] .rp-step a svg { transform:scaleX(-1); }
.rp-period { min-width:150px; padding:0 14px; text-align:center; font-family:var(--bk-serif); font-size:1.05rem; font-weight:600; color:var(--bk-text); white-space:nowrap; }
.rp-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-inline-start:auto; }
.rp-select, .rp-year { height:44px; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.rp-select { padding-inline:12px 34px; min-width:120px; cursor:pointer; }
.rp-year { width:92px; padding-inline:12px; font-variant-numeric:tabular-nums; }
.rp-select:focus, .rp-year:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }

/* ── Panels ── */
.rp-panel { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; box-shadow:var(--bk-shadow); overflow:hidden; height:100%; }
.rp-panel-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:16px 20px 0; }
.rp-panel-title { margin:0; font-size:.95rem; font-weight:700; color:var(--bk-text); }
.rp-panel-body { padding:16px 20px 20px; }

/* ── Ledger: income − expenses = net ── */
.rp-ledger { display:grid; grid-template-columns:repeat(4,1fr); background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; box-shadow:var(--bk-shadow); margin-bottom:18px; overflow:hidden; }
.rp-fig { padding:18px 22px; min-width:0; }
.rp-fig + .rp-fig { border-inline-start:1px solid var(--bk-border); }
.rp-fig.is-net { background:var(--bk-accent-wash); }
.rp-fig-label { display:flex; align-items:center; gap:7px; font-size:.8rem; font-weight:600; color:var(--bk-text-muted); margin-bottom:8px; }
.rp-fig-label svg { width:15px; height:15px; stroke-width:2; }
.rp-fig-value { font-family:var(--bk-serif); font-size:1.85rem; font-weight:600; line-height:1.1; color:var(--bk-text); font-variant-numeric:tabular-nums lining-nums; white-space:nowrap; }
.rp-fig-value.is-neg { color:var(--bk-danger); }
.rp-fig.is-net .rp-fig-value { font-size:2.1rem; }
.rp-delta { display:inline-flex; align-items:center; gap:4px; margin-top:8px; font-size:.78rem; font-weight:700; font-variant-numeric:tabular-nums; }
.rp-delta svg { width:14px; height:14px; stroke-width:2.4; }
.rp-delta.good { color:var(--bk-success); }
.rp-delta.bad  { color:var(--bk-danger); }
.rp-delta small { font-weight:500; color:var(--bk-text-muted); }
.rp-sub { margin-top:8px; font-size:.78rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }

/* ── Ranked lists ── */
.rp-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:14px; }
.rp-item-top { display:flex; align-items:baseline; gap:10px; }
.rp-rank { width:18px; flex:0 0 auto; font-size:.78rem; font-weight:700; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.rp-name { flex:1; min-width:0; font-size:.9rem; font-weight:600; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rp-name small { font-weight:500; color:var(--bk-text-muted); margin-inline-start:4px; }
.rp-amt { font-size:.9rem; font-weight:700; color:var(--bk-text); font-variant-numeric:tabular-nums lining-nums; white-space:nowrap; }
.rp-bar-track { height:5px; border-radius:99px; background:var(--bk-surface-2); margin-top:7px; overflow:hidden; }
.rp-list.is-ranked .rp-bar-track { margin-inline-start:28px; }
.rp-bar-fill { height:100%; border-radius:99px; background:var(--bk-accent-fill); }
.rp-bar-fill.is-out { background:var(--bk-gold); }
.rp-group { margin:0 0 10px; font-size:.74rem; font-weight:700; color:var(--bk-text-muted); }
.rp-group + .rp-list { margin-bottom:18px; }
.rp-list:not(.is-ranked) .rp-rank { display:none; }
.rp-total { display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px solid var(--bk-border); font-size:.9rem; font-weight:600; }
.rp-empty { padding:28px 8px; text-align:center; color:var(--bk-text-muted); font-size:.88rem; }

/* ── Table ── */
.rp-table { width:100%; border-collapse:collapse; }
.rp-table th { padding:12px 18px; font-size:.78rem; font-weight:700; color:var(--bk-text-muted); text-align:start; background:var(--bk-surface-2); border-bottom:1px solid var(--bk-border); white-space:nowrap; }
.rp-table td { padding:14px 18px; border-bottom:1px solid var(--bk-border); font-size:.9rem; color:var(--bk-text); vertical-align:middle; font-variant-numeric:tabular-nums lining-nums; }
.rp-table tbody tr:last-child td { border-bottom:0; }
.rp-table tbody tr:hover { background:var(--bk-sidebar-hover); }
.rp-table .num { text-align:end; }
.rp-table tbody th { padding:14px 18px; background:none; font-size:.9rem; font-weight:600; color:var(--bk-text); text-align:start; border-bottom:1px solid var(--bk-border); }
.rp-table tbody tr:last-child th { border-bottom:0; }
.rp-table tr.is-total td, .rp-table tr.is-total th { font-weight:700; background:var(--bk-surface-2); }
.rp-pos { color:var(--bk-success); font-weight:700; }
.rp-neg { color:var(--bk-danger); font-weight:700; }
.rp-muted { color:var(--bk-text-muted); }
.rp-pill { display:inline-flex; align-items:center; justify-content:center; min-width:30px; padding:2px 10px; border-radius:20px; background:var(--bk-accent-wash); color:var(--bk-accent); font-size:.8rem; font-weight:700; }
.rp-chart-wrap { position:relative; height:300px; }

@media (max-width:991.98px) {
    .rp-ledger { grid-template-columns:1fr 1fr; }
    .rp-fig:nth-child(3) { border-inline-start:0; }
    .rp-fig:nth-child(n+3) { border-top:1px solid var(--bk-border); }
}
@media (max-width:575.98px) {
    .rp-fig { padding:14px 16px; }
    .rp-fig-value, .rp-fig.is-net .rp-fig-value { font-size:1.45rem; }
    .rp-form { margin-inline-start:0; width:100%; }
    .rp-form > * { flex:1 1 auto; }
    .rp-bar { gap:8px; }
    .rp-step { width:100%; justify-content:space-between; }
    .rp-period { flex:1; }
    .rp-table th, .rp-table td { padding:12px 12px; }
    .rp-chart-wrap { height:240px; }
}
</style>
@endpush
@endonce

@php
    $keep = request()->only(['month', 'year', 'branch_id']);
    $tabs = [
        'profit-loss'          => ['href' => route('company.reports.profit-loss', $keep),          'icon' => 'trending-up', 'label' => __('Profit & Loss')],
        'employee-performance' => ['href' => route('company.reports.employee-performance', $keep), 'icon' => 'users',       'label' => __('Employee Performance')],
    ];
@endphp

<x-section-head :title="$title" :subtitle="$subtitle" :tabs="$tabs" :active="$active" :actions="$actions" :nav-label="__('Report sections')" />
