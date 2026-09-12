{{--
  Appointments Management — shared "Luxury SaaS + Editorial" UI system (am-*).
  Mirrors the bm-* (Branches) and cm-* (Companies) systems so every Owner
  Dashboard management page reads as one family. Token-driven
  (booksy-custom.css --bk-* tokens), light + dark, RTL-aware, and respects
  prefers-reduced-motion.

  Included @once by the appointments index. Adds the pieces the enterprise
  appointments view needs on top of the shared vocabulary: 5-up summary cards,
  date-range chips, a slide-in details drawer, an advanced-filters drawer, and a
  columns toggle menu.
--}}
@once
@push('owner-styles')
<link rel="stylesheet" href="{{ asset('fonts/glowrez-type.css') }}">
<style>
/* ═══════════════ Appointments Management — am-* editorial system ═══════════════ */
.am-wrap { --am-radius:16px; --am-serif:'Fraunces', Georgia, 'Times New Roman', serif; }
.am-wrap a { text-decoration:none; }

@keyframes amReveal { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
.am-reveal { animation:amReveal .45s cubic-bezier(.2,.7,.3,1) both; }
.am-reveal:nth-of-type(2){ animation-delay:.05s; }
.am-reveal:nth-of-type(3){ animation-delay:.1s; }
.am-reveal:nth-of-type(4){ animation-delay:.15s; }

/* ── Header ── */
/* Header sits in its own stacking layer ABOVE the summary cards / toolbar so the
   "Columns" dropdown (anchored in the header) is never painted behind them —
   the am-reveal transform on later siblings would otherwise cover it. */
.am-head { position:relative; z-index:30; display:flex; justify-content:space-between; align-items:flex-end; gap:20px; flex-wrap:wrap; margin-bottom:22px; }
.am-stats { position:relative; z-index:1; }
.am-toolbar { position:relative; z-index:2; }
.am-colmenu { z-index:1000; }
.am-eyebrow { font-size:.72rem; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.am-eyebrow a { color:var(--bk-gold-strong); }
.am-eyebrow a:hover { color:var(--bk-gold); }
.am-title { font-family:var(--am-serif); font-size:2.1rem; font-weight:600; color:var(--bk-text); line-height:1.05; margin:0; letter-spacing:-.015em; }
.am-subtitle { color:var(--bk-text-muted); font-size:.92rem; margin:8px 0 0; max-width:64ch; line-height:1.5; }
.am-head-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

/* ── Buttons ── */
.am-btn { display:inline-flex; align-items:center; gap:8px; height:44px; padding:0 18px; border-radius:12px;
    font-size:.87rem; font-weight:600; border:1px solid transparent; cursor:pointer; white-space:nowrap; background:none;
    transition:background .18s, border-color .18s, color .18s, transform .18s, box-shadow .18s; }
.am-btn i, .am-btn svg { width:16px; height:16px; }
.am-btn-primary { background:var(--bk-accent); color:var(--bk-accent-ink); box-shadow:var(--bk-shadow); }
.am-btn-primary:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); transform:translateY(-1px); box-shadow:var(--bk-shadow-lg); }
.am-btn-ghost { background:var(--bk-surface); color:var(--bk-text-soft); border-color:var(--bk-border); }
.am-btn-ghost:hover { border-color:var(--bk-gold); color:var(--bk-text); }
.am-btn-ghost.is-active { border-color:var(--bk-accent); color:var(--bk-accent); background:var(--bk-accent-wash); }
.am-btn:focus-visible { outline:none; box-shadow:0 0 0 3px var(--bk-accent-wash); }
.am-btn-sm { height:38px; padding:0 14px; font-size:.83rem; border-radius:10px; }

.am-clear { display:inline-flex; align-items:center; gap:5px; height:44px; padding:0 12px; border-radius:12px;
    color:var(--bk-danger); font-size:.84rem; font-weight:600; border:1px solid transparent; transition:background .15s; }
.am-clear i, .am-clear svg { width:14px; height:14px; stroke-width:2; }
.am-clear:hover { background:var(--bk-danger-bg); color:var(--bk-danger); }

/* ── Summary cards (5-up, clickable quick filters) ── */
.am-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
.am-stat { position:relative; display:block; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px;
    padding:15px 16px 15px 20px; overflow:hidden; box-shadow:var(--bk-shadow); color:inherit;
    transition:border-color .18s, box-shadow .18s, transform .18s; }
.am-stat::before { content:''; position:absolute; inset-inline-start:0; top:0; bottom:0; width:4px; background:var(--accent, var(--bk-accent)); }
.am-stat:hover { border-color:color-mix(in srgb, var(--accent, var(--bk-accent)) 40%, var(--bk-border)); box-shadow:var(--bk-shadow-lg); transform:translateY(-2px); }
.am-stat.is-active { border-color:var(--accent, var(--bk-accent)); background:color-mix(in srgb, var(--accent, var(--bk-accent)) 8%, var(--bk-surface)); }
.am-stat-label { display:flex; align-items:center; gap:7px; font-size:.71rem; text-transform:uppercase; letter-spacing:.07em; color:var(--bk-text-muted); font-weight:700; }
.am-stat-label i, .am-stat-label svg { width:14px; height:14px; color:var(--accent, var(--bk-accent)); stroke-width:2; }
.am-stat-value { display:block; margin-top:6px; font-family:var(--am-serif); font-size:1.75rem; font-weight:600;
    color:var(--bk-text); line-height:1.1; font-variant-numeric:tabular-nums; }

/* ── Toolbar & filters ── */
.am-toolbar { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:12px; margin-bottom:16px; box-shadow:var(--bk-shadow); }
.am-toolbar-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.am-toolbar-row + .am-toolbar-row { margin-top:10px; }
.am-search { position:relative; flex:1 1 260px; min-width:190px; display:flex; align-items:center; }
.am-search-btn { position:absolute; inset-inline-start:6px; top:50%; transform:translateY(-50%); width:30px; height:30px;
    display:inline-flex; align-items:center; justify-content:center; padding:0; border:none; background:transparent;
    color:var(--bk-text-muted); cursor:pointer; border-radius:8px; transition:color .15s, background .15s; }
.am-search-btn:hover { color:var(--bk-accent); background:var(--bk-accent-wash); }
.am-search-btn i, .am-search-btn svg { width:16px; height:16px; stroke-width:2; pointer-events:none; }
.am-search input { width:100%; height:44px; padding-inline:42px 14px; border-radius:11px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.am-search input::placeholder { color:var(--bk-text-muted); }
.am-search input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.am-select { height:44px; padding-inline:14px 32px; border-radius:11px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.87rem; cursor:pointer; outline:none; transition:border-color .15s, box-shadow .15s; max-width:100%; }
.am-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.am-select:disabled { opacity:.55; cursor:not-allowed; }
.am-select option { background:var(--bk-surface); color:var(--bk-text); }
.am-select-icon { position:relative; display:inline-flex; align-items:center; }
.am-select-icon > i, .am-select-icon > svg { position:absolute; inset-inline-start:12px; width:15px; height:15px; color:var(--bk-gold-strong); pointer-events:none; }
.am-select-icon .am-select { padding-inline-start:34px; }

/* Date range chips */
.am-chips { display:inline-flex; gap:3px; padding:4px; background:var(--bk-bg); border:1px solid var(--bk-border); border-radius:11px; flex-wrap:wrap; }
.am-chip { display:inline-flex; align-items:center; gap:6px; height:36px; padding:0 13px; font-size:.82rem; font-weight:600;
    color:var(--bk-text-muted); background:transparent; border:none; border-radius:8px; cursor:pointer; white-space:nowrap; transition:all .16s; }
.am-chip i, .am-chip svg { width:14px; height:14px; }
.am-chip:hover { color:var(--bk-text); }
.am-chip.is-active { background:var(--bk-accent); color:var(--bk-accent-ink); }

/* Sort direction toggle */
.am-dir { width:44px; height:44px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center;
    border-radius:11px; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); cursor:pointer; transition:all .15s; }
.am-dir i, .am-dir svg { width:16px; height:16px; }
.am-dir:hover { border-color:var(--bk-accent); color:var(--bk-accent); }

/* Active filter summary line */
.am-active { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.am-tag { display:inline-flex; align-items:center; gap:6px; padding:5px 10px 5px 12px; border-radius:999px; font-size:.78rem; font-weight:600;
    background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid color-mix(in srgb, var(--bk-accent) 26%, transparent); }
.am-tag a { display:inline-flex; color:inherit; opacity:.7; }
.am-tag a:hover { opacity:1; }
.am-tag i, .am-tag svg { width:12px; height:12px; stroke-width:2.4; }

/* ── Columns menu ── */
.am-colmenu { min-width:210px; padding:8px; border-radius:12px !important; }
.am-colmenu-head { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted); font-weight:700; padding:4px 8px 8px; }
.am-colrow { display:flex; align-items:center; gap:10px; padding:8px 9px; border-radius:8px; font-size:.85rem; color:var(--bk-text); cursor:pointer; }
.am-colrow:hover { background:var(--bk-accent-wash); }
.am-colrow input { accent-color:var(--bk-accent); width:15px; height:15px; }
.am-act.dropdown-toggle::after { display:none; }

/* ── Card (table container) ── */
.am-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--am-radius); overflow:hidden; box-shadow:var(--bk-shadow); }
.am-table-scroll { overflow-x:auto; }
.am-table { width:100%; border-collapse:collapse; min-width:900px; }
.am-table thead th { font-size:.71rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted);
    font-weight:700; text-align:start; padding:13px 18px; background:var(--bk-bg); border-bottom:1px solid var(--bk-border); white-space:nowrap; }
.am-table thead th.am-end { text-align:end; }
.am-table thead th.am-center { text-align:center; }
.am-sort { color:inherit; display:inline-flex; align-items:center; gap:5px; }
.am-sort:hover { color:var(--bk-text); }
.am-sort.is-active { color:var(--bk-accent); }
.am-sort-caret { width:13px; height:13px; color:var(--bk-accent); }
.am-table tbody td { padding:13px 18px; border-bottom:1px solid var(--bk-border); vertical-align:middle; }
.am-table tbody tr { transition:background .15s; cursor:pointer; }
.am-table tbody tr:hover { background:var(--bk-accent-wash); }
.am-table tbody tr:last-child td { border-bottom:none; }
.am-end { text-align:end; }
.am-center { text-align:center; }

/* Appointment reference cell */
.am-ref { display:flex; align-items:center; gap:12px; }
.am-ref-ic { width:42px; height:42px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    color:var(--bk-accent); background:var(--bk-accent-wash); border:1px solid var(--bk-border); }
.am-ref-ic i, .am-ref-ic svg { width:19px; height:19px; stroke-width:1.9; }
.am-ref-code { font-weight:600; color:var(--bk-text); font-size:.9rem; font-variant-numeric:tabular-nums; }
.am-ref-sub { font-size:.75rem; color:var(--bk-text-muted); margin-top:2px; }

/* Company / branch stacked cell */
.am-cb-company { font-weight:600; color:var(--bk-text); font-size:.87rem; display:inline-flex; align-items:center; gap:6px; }
.am-cb-company i, .am-cb-company svg { width:13px; height:13px; color:var(--bk-gold-strong); stroke-width:1.9; flex-shrink:0; }
.am-cb-branch { font-size:.78rem; color:var(--bk-text-muted); margin-top:3px; display:inline-flex; align-items:center; gap:6px; }
.am-cb-branch i, .am-cb-branch svg { width:12px; height:12px; opacity:.7; stroke-width:1.9; flex-shrink:0; }

.am-person { font-weight:500; color:var(--bk-text); font-size:.87rem; }
.am-person-sub { font-size:.75rem; color:var(--bk-text-muted); margin-top:2px; }
.am-muted { color:var(--bk-text-muted); }
.am-dash { color:var(--bk-text-muted); opacity:.5; }

/* When cell */
.am-when { font-size:.85rem; color:var(--bk-text); font-variant-numeric:tabular-nums; white-space:nowrap; }
.am-when-date { font-weight:600; }
.am-when-time { color:var(--bk-text-muted); }

/* Status pill (colour comes from the enum, inline) */
.am-status { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:999px; font-size:.75rem; font-weight:600;
    white-space:nowrap; border:1px solid; }
.am-status i, .am-status svg { width:12px; height:12px; stroke-width:2.2; }

/* Payment mini badge */
.am-pay { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:.73rem; font-weight:600; white-space:nowrap; border:1px solid transparent; }
.am-pay i, .am-pay svg { width:11px; height:11px; stroke-width:2.3; }
.am-pay-paid    { color:var(--bk-success); background:var(--bk-success-bg); border-color:color-mix(in srgb, var(--bk-success) 26%, transparent); }
.am-pay-pending { color:var(--bk-warning); background:var(--bk-warning-bg); border-color:color-mix(in srgb, var(--bk-warning) 26%, transparent); }
.am-pay-other   { color:var(--bk-text-muted); background:var(--bk-surface-2); border-color:var(--bk-border); }

/* Actions */
.am-actions { display:flex; gap:6px; justify-content:flex-end; align-items:center; }
.am-act { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:9px;
    border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-muted); cursor:pointer; transition:all .15s; }
.am-act i, .am-act svg { width:15px; height:15px; stroke-width:1.9; }
.am-act:hover { border-color:var(--bk-gold); color:var(--bk-gold-strong); background:var(--bk-gold-soft); }
.am-act-primary:hover { border-color:var(--bk-accent); color:var(--bk-accent); background:var(--bk-accent-wash); }

/* ── Empty state ── */
.am-empty { display:flex; flex-direction:column; align-items:center; gap:14px; padding:60px 20px; text-align:center; }
.am-empty-ic { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent); }
.am-empty-ic i, .am-empty-ic svg { width:28px; height:28px; stroke-width:1.6; }
.am-empty-title { margin:0; color:var(--bk-text); font-size:1.05rem; font-weight:600; font-family:var(--am-serif); }
.am-empty-sub { margin:0; color:var(--bk-text-muted); font-size:.88rem; max-width:44ch; }

/* ── Pagination ── */
.am-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    padding:14px 18px; border-top:1px solid var(--bk-border); }
.am-pagination-left { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.am-pagination-info { font-size:.8rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.am-perpage { display:inline-flex; align-items:center; gap:8px; font-size:.8rem; color:var(--bk-text-muted); font-weight:600; margin:0; }
.am-select-sm { height:36px; padding-inline:10px 28px; font-size:.82rem; border-radius:9px; }
.am-pagination .pagination { margin:0; }
.am-pagination .page-link { color:var(--bk-text-soft); border-color:var(--bk-border); background:var(--bk-surface); }
.am-pagination .page-item.active .page-link { background:var(--bk-accent); border-color:var(--bk-accent); color:var(--bk-accent-ink); }
.am-pagination .page-link:hover { background:var(--bk-accent-wash); color:var(--bk-text); }

/* ═══════════════ Drawers (details + advanced filters) ═══════════════ */
.am-scrim { position:fixed; inset:0; background:rgba(10,12,8,.55); backdrop-filter:blur(2px); opacity:0; visibility:hidden;
    transition:opacity .25s, visibility .25s; z-index:1080; }
.am-scrim.is-open { opacity:1; visibility:visible; }

.am-drawer { position:fixed; top:0; bottom:0; inset-inline-end:0; width:min(460px, 94vw); background:var(--bk-surface);
    border-inline-start:1px solid var(--bk-border); box-shadow:var(--bk-shadow-xl); z-index:1090; display:flex; flex-direction:column;
    transform:translateX(100%); transition:transform .3s cubic-bezier(.2,.7,.3,1); }
[dir="rtl"] .am-drawer { transform:translateX(-100%); }
.am-drawer.is-open { transform:translateX(0); }
.am-drawer-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:20px 22px 16px; border-bottom:1px solid var(--bk-border); }
.am-drawer-eyebrow { font-size:.7rem; letter-spacing:.12em; text-transform:uppercase; color:var(--bk-gold-strong); font-weight:700; }
.am-drawer-title { font-family:var(--am-serif); font-size:1.35rem; font-weight:600; color:var(--bk-text); margin:6px 0 0; line-height:1.15; }
.am-drawer-close { flex-shrink:0; width:38px; height:38px; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-bg);
    color:var(--bk-text-muted); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; }
.am-drawer-close:hover { border-color:var(--bk-danger); color:var(--bk-danger); background:var(--bk-danger-bg); }
.am-drawer-close i, .am-drawer-close svg { width:17px; height:17px; }
.am-drawer-body { flex:1; overflow-y:auto; padding:20px 22px; }
.am-drawer-foot { padding:16px 22px; border-top:1px solid var(--bk-border); background:var(--bk-bg); display:flex; gap:10px; flex-wrap:wrap; }
.am-drawer-foot .am-btn { flex:1 1 auto; justify-content:center; }

/* Drawer loading + detail content */
.am-drawer-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; height:100%; color:var(--bk-text-muted); }
.am-spinner { width:34px; height:34px; border:3px solid var(--bk-border); border-top-color:var(--bk-accent); border-radius:50%; animation:amSpin .7s linear infinite; }
@keyframes amSpin { to { transform:rotate(360deg); } }

.am-d-hero { display:flex; align-items:center; gap:14px; padding-bottom:18px; margin-bottom:18px; border-bottom:1px solid var(--bk-border); }
.am-d-hero-ic { width:52px; height:52px; border-radius:14px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid var(--bk-border); }
.am-d-hero-ic i, .am-d-hero-ic svg { width:24px; height:24px; }
.am-d-hero-ref { font-family:var(--am-serif); font-size:1.2rem; font-weight:600; color:var(--bk-text); }
.am-d-section { margin-bottom:20px; }
.am-d-section-title { display:flex; align-items:center; gap:7px; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:var(--bk-gold-strong); font-weight:700; margin-bottom:11px; }
.am-d-section-title i, .am-d-section-title svg { width:13px; height:13px; }
.am-d-list { display:grid; grid-template-columns:auto 1fr; gap:9px 16px; }
.am-d-dt { font-size:.8rem; color:var(--bk-text-muted); font-weight:500; }
.am-d-dd { font-size:.86rem; color:var(--bk-text); font-weight:500; text-align:end; }
.am-d-note { padding:12px 14px; border-radius:11px; background:var(--bk-bg); border:1px solid var(--bk-border); font-size:.85rem; color:var(--bk-text-soft); line-height:1.55; }
.am-d-note.is-danger { border-color:color-mix(in srgb, var(--bk-danger) 30%, transparent); background:var(--bk-danger-bg); color:var(--bk-danger); }

/* Advanced-filters drawer form fields */
.am-fform { display:flex; flex-direction:column; gap:16px; }
.am-field { display:flex; flex-direction:column; gap:7px; }
.am-field-label { font-size:.8rem; font-weight:600; color:var(--bk-text-soft); }
.am-field .am-select, .am-field input { width:100%; }
.am-field input[type="date"] { height:44px; padding-inline:12px; border-radius:11px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-size:.87rem; outline:none; }
.am-field input[type="date"]:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.am-field-2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }

/* ── Responsive ── */
@media (max-width:1200px){ .am-col-payment { display:none; } .am-stats { grid-template-columns:repeat(3,1fr); } }
@media (max-width:992px){ .am-col-staff { display:none; } .am-title { font-size:1.8rem; } }
@media (max-width:768px){
    .am-stats { grid-template-columns:repeat(2,1fr); }
    .am-table thead th, .am-table tbody td { padding:12px 14px; }
    .am-head-actions { width:100%; }
    .am-head-actions .am-btn { flex:1 1 auto; justify-content:center; }
}
@media (max-width:520px){ .am-stats { grid-template-columns:1fr 1fr; } .am-stat:nth-child(5){ grid-column:1 / -1; } }

@media (prefers-reduced-motion:reduce){
    .am-reveal { animation:none; }
    .am-btn, .am-table tbody tr, .am-act, .am-stat, .am-drawer, .am-scrim, .am-chip { transition:none; }
    .am-spinner { animation-duration:1.4s; }
}
</style>
@endpush
@endonce
