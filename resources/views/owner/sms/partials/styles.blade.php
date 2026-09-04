{{-- Fraunces display serif — the GlowRez editorial face, self-hosted --}}
<link href="{{ asset('fonts/fraunces.css') }}" rel="stylesheet">
<style>
/* ═══════════════ GlowRez SMS — Luxury Editorial SaaS ═══════════════ */
/* Built on the shared --bk-* theme tokens (olive + gold + charcoal), with a
   swappable display face. Works in light/dark and RTL/LTR out of the box.     */
.sx {
    --sx-display: 'Fraunces', 'Tajawal', Georgia, serif;
    --sx-radius: 18px;
    --sx-olive: var(--bk-accent, #4B5D34);
    --sx-gold: var(--bk-gold-strong, #B08D3F);
}
.sx a { text-decoration: none; }

/* Reveal — subtle staggered entrance */
@keyframes sxReveal { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
.sx-reveal { animation: sxReveal .5s cubic-bezier(.2,.7,.3,1) both; }
.sx-reveal:nth-of-type(2){ animation-delay:.04s; }
.sx-reveal:nth-of-type(3){ animation-delay:.08s; }
.sx-reveal:nth-of-type(4){ animation-delay:.12s; }
.sx-reveal:nth-of-type(5){ animation-delay:.16s; }

/* ── Header (asymmetric) ── */
.sx-head { display:flex; justify-content:space-between; align-items:flex-end; gap:22px; flex-wrap:wrap; margin-bottom:26px; }
.sx-eyebrow { font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; color:var(--sx-gold); font-weight:600; margin-bottom:9px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.sx-eyebrow a { color:var(--sx-gold); }
.sx-eyebrow a:hover { color:var(--bk-gold); }
.sx-title { font-family:var(--sx-display) !important; font-size:2.3rem; font-weight:600; color:var(--bk-text); line-height:1.02; margin:0; letter-spacing:-.02em; }
.sx-subtitle { color:var(--bk-text-muted); font-size:.94rem; margin:9px 0 0; max-width:62ch; line-height:1.55; }
.sx-head-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

/* ── Buttons ── */
.sx-btn { display:inline-flex; align-items:center; gap:8px; height:44px; padding:0 18px; border-radius:12px;
    font-size:.87rem; font-weight:600; border:1px solid transparent; cursor:pointer; white-space:nowrap; background:none;
    transition:background .18s, border-color .18s, color .18s, transform .18s, box-shadow .18s; }
.sx-btn i, .sx-btn svg { width:16px; height:16px; stroke-width:2; }
.sx-btn-primary { background:var(--bk-accent); color:var(--bk-accent-ink); box-shadow:var(--bk-shadow); }
.sx-btn-primary:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); transform:translateY(-1px); box-shadow:var(--bk-shadow-lg); }
.sx-btn-ghost { background:var(--bk-surface); color:var(--bk-text-soft); border-color:var(--bk-border); }
.sx-btn-ghost:hover { border-color:var(--bk-gold); color:var(--bk-text); }
.sx-btn-gold { background:var(--bk-gold-soft); color:var(--bk-gold-strong); border-color:color-mix(in srgb, var(--bk-gold-strong) 26%, transparent); }
.sx-btn-gold:hover { border-color:var(--bk-gold-strong); }
.sx-btn-danger { background:var(--bk-surface); color:var(--bk-danger); border-color:var(--bk-border); }
.sx-btn-danger:hover { border-color:var(--bk-danger); background:var(--bk-danger-bg); }
.sx-btn-sm { height:36px; padding:0 13px; font-size:.82rem; border-radius:10px; }

/* ── Stat cards (hero metrics) ── */
.sx-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.sx-stat { position:relative; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--sx-radius);
    padding:18px 20px; overflow:hidden; box-shadow:var(--bk-shadow); }
.sx-stat::before { content:''; position:absolute; inset-inline-start:0; top:0; bottom:0; width:4px; background:var(--accent,var(--sx-olive)); }
.sx-stat-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.sx-stat-label { font-size:.71rem; text-transform:uppercase; letter-spacing:.08em; color:var(--bk-text-muted); font-weight:600; }
.sx-stat-ic { width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent); flex-shrink:0; }
.sx-stat-ic i, .sx-stat-ic svg { width:17px; height:17px; stroke-width:2; }
.sx-stat-ic.is-gold { background:var(--bk-gold-soft); color:var(--bk-gold-strong); }
.sx-stat-ic.is-success { background:var(--bk-success-bg); color:var(--bk-success); }
.sx-stat-ic.is-danger { background:var(--bk-danger-bg); color:var(--bk-danger); }
.sx-stat-value { display:block; margin-top:12px; font-family:var(--sx-display) !important; font-size:2rem; font-weight:600;
    color:var(--bk-text); line-height:1.05; font-variant-numeric:tabular-nums; letter-spacing:-.01em; }
.sx-stat-sub { display:block; margin-top:5px; font-size:.78rem; color:var(--bk-text-muted); }

/* ── Cards / panels ── */
.sx-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--sx-radius); box-shadow:var(--bk-shadow); overflow:hidden; }
.sx-card-pad { padding:20px 22px; }
.sx-card-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px 22px; border-bottom:1px solid var(--bk-border); flex-wrap:wrap; }
.sx-card-title { font-family:var(--sx-display) !important; font-size:1.18rem; font-weight:600; color:var(--bk-text); margin:0; letter-spacing:-.01em; }
.sx-card-note { font-size:.8rem; color:var(--bk-text-muted); margin:3px 0 0; }
.sx-grid { display:grid; gap:16px; }
.sx-grid-2 { grid-template-columns:1.5fr 1fr; }
.sx-grid-2eq { grid-template-columns:1fr 1fr; }

/* ── Meter (allocated / used / remaining) ── */
.sx-meter { height:12px; border-radius:999px; background:var(--bk-bg); border:1px solid var(--bk-border); overflow:hidden; display:flex; }
.sx-meter-fill { height:100%; background:linear-gradient(90deg, var(--sx-olive), color-mix(in srgb, var(--sx-olive) 55%, var(--sx-gold))); transition:width .6s cubic-bezier(.2,.7,.3,1); }
.sx-meter-fill.is-gold { background:linear-gradient(90deg, var(--sx-gold), color-mix(in srgb,var(--sx-gold) 60%, #E7C67A)); }
.sx-meter-legend { display:flex; gap:18px; flex-wrap:wrap; margin-top:12px; }
.sx-legend { display:flex; align-items:center; gap:7px; font-size:.8rem; color:var(--bk-text-soft); }
.sx-dot { width:9px; height:9px; border-radius:3px; flex-shrink:0; }

/* ── Chips / pills ── */
.sx-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; background:var(--bk-bg);
    border:1px solid var(--bk-border); color:var(--bk-text-soft); font-size:.78rem; font-weight:600; white-space:nowrap; }
.sx-chip i, .sx-chip svg { width:13px; height:13px; }
.sx-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:999px; font-size:.76rem; font-weight:700; white-space:nowrap; letter-spacing:.02em; }
.sx-pill-sent    { color:var(--bk-success); background:var(--bk-success-bg); }
.sx-pill-failed  { color:var(--bk-danger);  background:var(--bk-danger-bg); }
.sx-pill-skipped { color:var(--bk-warning); background:var(--bk-warning-bg); }
.sx-pill-queued  { color:var(--bk-text-soft); background:var(--bk-bg); border:1px solid var(--bk-border); }
.sx-type { display:inline-flex; align-items:center; gap:6px; font-size:.8rem; font-weight:600; color:var(--bk-text-soft); }
.sx-type i, .sx-type svg { width:13px; height:13px; }
.sx-type-confirmation { color:var(--bk-success); }
.sx-type-reminder     { color:var(--bk-gold-strong); }
.sx-type-followup     { color:var(--bk-accent); }

/* ── Tables ── */
.sx-table-scroll { overflow-x:auto; }
.sx-table { width:100%; border-collapse:collapse; min-width:640px; }
.sx-table thead th { font-size:.71rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted);
    font-weight:600; text-align:start; padding:13px 20px; background:var(--bk-bg); border-bottom:1px solid var(--bk-border); white-space:nowrap; }
.sx-table thead th.num, .sx-table tbody td.num { text-align:end; font-variant-numeric:tabular-nums; }
.sx-table tbody td { padding:14px 20px; border-bottom:1px solid var(--bk-border); vertical-align:middle; color:var(--bk-text); font-size:.9rem; }
.sx-table tbody tr { transition:background .15s; }
.sx-table tbody tr:hover { background:var(--bk-accent-wash); }
.sx-table tbody tr:last-child td { border-bottom:none; }
.sx-name { font-weight:600; color:var(--bk-text); }
.sx-sub { font-size:.78rem; color:var(--bk-text-muted); margin-top:2px; }
.sx-mono { font-variant-numeric:tabular-nums; font-feature-settings:"tnum"; }

/* Mini avatar */
.sx-ava { width:38px; height:38px; border-radius:11px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    font-family:var(--sx-display) !important; font-weight:600; font-size:1rem; color:var(--bk-accent);
    background:var(--bk-accent-wash); border:1px solid var(--bk-border); }
.sx-idcell { display:flex; align-items:center; gap:12px; }

/* Usage bar inside a table cell */
.sx-ubar { height:7px; border-radius:999px; background:var(--bk-bg); border:1px solid var(--bk-border); overflow:hidden; min-width:80px; margin-top:6px; }
.sx-ubar > span { display:block; height:100%; background:var(--sx-olive); }

/* ── Provider (Rassel) panel — reference only, visually distinct ── */
.sx-provider { border:1px solid color-mix(in srgb, var(--bk-gold-strong) 30%, var(--bk-border)); border-radius:var(--sx-radius);
    background:linear-gradient(180deg, var(--bk-gold-soft), var(--bk-surface)); box-shadow:var(--bk-shadow); overflow:hidden; }
.sx-provider .sx-card-head { border-bottom-color:color-mix(in srgb, var(--bk-gold-strong) 22%, var(--bk-border)); }
.sx-ref-tag { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:.68rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.08em; color:var(--bk-gold-strong); background:var(--bk-surface); border:1px solid color-mix(in srgb, var(--bk-gold-strong) 30%, transparent); }
.sx-prov-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1px; background:var(--bk-border); }
.sx-prov-cell { background:var(--bk-surface); padding:16px 18px; }
.sx-prov-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted); font-weight:600; }
.sx-prov-value { display:block; margin-top:6px; font-family:var(--sx-display) !important; font-size:1.5rem; font-weight:600; color:var(--bk-text); font-variant-numeric:tabular-nums; }
.sx-prov-value small { font-family:inherit; font-size:.8rem; color:var(--bk-text-muted); font-weight:500; }

/* ── Toolbar / filters ── */
.sx-toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.sx-search { position:relative; flex:1 1 260px; min-width:180px; display:flex; align-items:center; }
.sx-search i, .sx-search svg { position:absolute; inset-inline-start:14px; width:16px; height:16px; color:var(--bk-text-muted); pointer-events:none; }
.sx-search input { width:100%; height:44px; padding-inline:42px 14px; border-radius:12px; border:1px solid var(--bk-border);
    background:var(--bk-surface); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.sx-search input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.sx-select { height:44px; padding-inline:14px 34px; border-radius:12px; border:1px solid var(--bk-border);
    background:var(--bk-surface); color:var(--bk-text); font-size:.87rem; cursor:pointer; outline:none; transition:border-color .15s, box-shadow .15s; }
.sx-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }

/* ── Form fields ── */
.sx-field { display:block; margin-bottom:14px; }
.sx-field > label { display:block; font-size:.74rem; font-weight:600; color:var(--bk-text-muted); margin-bottom:7px; text-transform:uppercase; letter-spacing:.05em; }
.sx-input { width:100%; height:46px; padding:0 14px; border-radius:12px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.92rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.sx-input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
textarea.sx-input { height:auto; min-height:96px; padding:12px 14px; line-height:1.6; resize:vertical; }
.sx-hint { font-size:.76rem; color:var(--bk-text-muted); margin-top:6px; }
.sx-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

/* ── Empty / loading / error states ── */
.sx-empty { display:flex; flex-direction:column; align-items:center; gap:14px; padding:60px 20px; text-align:center; }
.sx-empty-ic { width:64px; height:64px; border-radius:20px; display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent); }
.sx-empty-ic i, .sx-empty-ic svg { width:28px; height:28px; stroke-width:1.6; }
.sx-empty-title { margin:0; font-family:var(--sx-display) !important; font-size:1.2rem; color:var(--bk-text); font-weight:600; }
.sx-empty-text { margin:0; color:var(--bk-text-muted); font-size:.9rem; max-width:40ch; }
.sx-note { display:flex; align-items:flex-start; gap:10px; padding:14px 16px; border-radius:12px; font-size:.85rem; line-height:1.5; }
.sx-note i, .sx-note svg { width:17px; height:17px; flex-shrink:0; margin-top:1px; }
.sx-note-warn { background:var(--bk-warning-bg); color:var(--bk-warning); border:1px solid color-mix(in srgb,var(--bk-warning) 26%, transparent); }
.sx-note-danger { background:var(--bk-danger-bg); color:var(--bk-danger); border:1px solid color-mix(in srgb,var(--bk-danger) 26%, transparent); }
.sx-note-info { background:var(--bk-accent-wash); color:var(--bk-text-soft); border:1px solid var(--bk-border); }

/* ── Pagination ── */
.sx-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:14px 20px; border-top:1px solid var(--bk-border); }
.sx-pagination .pagination { margin:0; }
.sx-pagination .page-link { color:var(--bk-text-soft); border-color:var(--bk-border); background:var(--bk-surface); }
.sx-pagination .page-item.active .page-link { background:var(--bk-accent); border-color:var(--bk-accent); color:var(--bk-accent-ink); }
.sx-pagination .page-link:hover { background:var(--bk-accent-wash); color:var(--bk-text); }
.sx-pagination-info { font-size:.8rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }

/* ── Charts (dependency-free) ── */
.sx-bars { display:flex; align-items:flex-end; gap:3px; height:180px; padding-top:10px; }
.sx-bars-col { flex:1; display:flex; flex-direction:column; justify-content:flex-end; gap:2px; min-width:0; height:100%; }
.sx-bars-stack { display:flex; flex-direction:column-reverse; gap:2px; border-radius:5px 5px 0 0; overflow:hidden; }
.sx-bar { width:100%; transition:height .5s cubic-bezier(.2,.7,.3,1); }
.sx-bar-confirmation { background:var(--bk-success); }
.sx-bar-reminder { background:var(--bk-gold-strong); }
.sx-bar-followup { background:var(--bk-accent); }
.sx-bar-manual { background:var(--bk-text-muted); }
.sx-bars-x { display:flex; gap:3px; margin-top:8px; }
.sx-bars-x span { flex:1; text-align:center; font-size:.62rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }

/* Donut via conic-gradient */
.sx-donut { width:150px; height:150px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; position:relative; }
.sx-donut::after { content:''; position:absolute; inset:26px; background:var(--bk-surface); border-radius:50%; }
.sx-donut-center { position:relative; z-index:1; text-align:center; }
.sx-donut-center strong { display:block; font-family:var(--sx-display) !important; font-size:1.5rem; color:var(--bk-text); font-variant-numeric:tabular-nums; }
.sx-donut-center span { font-size:.68rem; color:var(--bk-text-muted); text-transform:uppercase; letter-spacing:.06em; }

/* ── Toggle switch ── */
.sx-switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
.sx-switch input { opacity:0; width:0; height:0; position:absolute; }
.sx-switch .sx-slider { position:absolute; inset:0; cursor:pointer; background:var(--bk-bg); border:1px solid var(--bk-border);
    border-radius:999px; transition:background .2s, border-color .2s; }
.sx-switch .sx-slider::before { content:''; position:absolute; height:18px; width:18px; inset-inline-start:3px; top:50%; transform:translateY(-50%);
    background:var(--bk-text-muted); border-radius:50%; transition:inset-inline-start .2s, background .2s; }
.sx-switch input:checked + .sx-slider { background:var(--bk-accent); border-color:var(--bk-accent); }
.sx-switch input:checked + .sx-slider::before { inset-inline-start:23px; background:var(--bk-accent-ink); }
.sx-switch input:focus-visible + .sx-slider { box-shadow:0 0 0 3px var(--bk-accent-wash); }

.sx-auto-row { display:flex; align-items:flex-start; gap:14px; padding:16px 0; border-bottom:1px solid var(--bk-border); }
.sx-auto-row:last-of-type { border-bottom:none; }
.sx-auto-ic { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
    background:var(--bk-accent-wash); color:var(--bk-accent); }
.sx-auto-ic i, .sx-auto-ic svg { width:18px; height:18px; }
.sx-auto-body { flex:1; min-width:0; }
.sx-auto-title { font-weight:600; color:var(--bk-text); font-size:.94rem; }
.sx-auto-desc { font-size:.82rem; color:var(--bk-text-muted); margin-top:3px; line-height:1.5; }
.sx-auto-field { display:flex; align-items:center; gap:8px; margin-top:10px; }
.sx-auto-field input { width:90px; height:38px; padding:0 12px; border-radius:10px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.88rem; outline:none; font-variant-numeric:tabular-nums; }
.sx-auto-field input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.sx-auto-field label { font-size:.82rem; color:var(--bk-text-soft); }

/* Variable chips (template editor) */
.sx-var { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:8px; cursor:pointer;
    background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid transparent; font-size:.8rem; font-weight:600;
    font-family:ui-monospace, monospace; transition:border-color .15s, background .15s; }
.sx-var:hover { border-color:var(--bk-accent); }
.sx-counter { display:flex; gap:16px; flex-wrap:wrap; align-items:center; margin-top:10px; font-size:.82rem; color:var(--bk-text-muted); }
.sx-counter strong { color:var(--bk-text); font-variant-numeric:tabular-nums; }
.sx-counter .seg-pill { padding:3px 10px; border-radius:999px; background:var(--bk-accent-wash); color:var(--bk-accent); font-weight:700; }

/* ── Responsive ── */
@media (max-width:1100px){ .sx-stats { grid-template-columns:repeat(2,1fr); } .sx-grid-2, .sx-grid-2eq { grid-template-columns:1fr; } }
@media (max-width:768px){
    .sx-title { font-size:1.8rem; }
    .sx-head-actions { width:100%; }
    .sx-head-actions .sx-btn { flex:1 1 auto; justify-content:center; }
    .sx-table thead th, .sx-table tbody td { padding:12px 14px; }
    .sx-row { grid-template-columns:1fr; }
}
@media (max-width:520px){ .sx-stats { grid-template-columns:1fr; } }

@media (prefers-reduced-motion:reduce){
    .sx-reveal { animation:none; }
    .sx-btn, .sx-table tbody tr, .sx-meter-fill, .sx-bar { transition:none; }
}
</style>
