{{--
  Branch Management — shared "Luxury SaaS + Editorial" UI system (bm-*).
  Mirrors the cm-* Companies system so every Owner Dashboard management page
  reads as one family. Token-driven (booksy-custom.css --bk-* tokens), works in
  light + dark, RTL-aware, and respects prefers-reduced-motion.

  Included @once by every branch page (index / create / edit / working-hours),
  so the same visual language is reused with a single source of truth.
--}}
@once
@push('owner-styles')
{{-- Fraunces (editorial serif) + Hanken Grotesk — self-hosted, no external calls --}}
<link rel="stylesheet" href="{{ asset('fonts/glowrez-type.css') }}">
<style>
/* ═══════════════ Branch Management — bm-* editorial system ═══════════════ */
.bm-wrap { --bm-radius:16px; --bm-serif:'Fraunces', Georgia, 'Times New Roman', serif; }
.bm-wrap a { text-decoration:none; }

/* Reveal (respects reduced-motion below) */
@keyframes bmReveal { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
.bm-reveal { animation:bmReveal .45s cubic-bezier(.2,.7,.3,1) both; }
.bm-reveal:nth-of-type(2){ animation-delay:.05s; }
.bm-reveal:nth-of-type(3){ animation-delay:.1s; }
.bm-reveal:nth-of-type(4){ animation-delay:.15s; }

/* ── Header ── */
.bm-head { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; flex-wrap:wrap; margin-bottom:24px; }
.bm-eyebrow { font-size:.72rem; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.bm-eyebrow a { color:var(--bk-gold-strong); }
.bm-eyebrow a:hover { color:var(--bk-gold); }
.bm-title { font-family:var(--bm-serif); font-size:2.1rem; font-weight:600; color:var(--bk-text); line-height:1.05; margin:0; letter-spacing:-.015em; }
.bm-subtitle { color:var(--bk-text-muted); font-size:.92rem; margin:8px 0 0; max-width:60ch; line-height:1.5; }
.bm-head-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

/* ── Buttons ── */
.bm-btn { display:inline-flex; align-items:center; gap:8px; height:44px; padding:0 18px; border-radius:12px;
    font-size:.87rem; font-weight:600; border:1px solid transparent; cursor:pointer; white-space:nowrap;
    transition:background .18s, border-color .18s, color .18s, transform .18s, box-shadow .18s; }
.bm-btn i, .bm-btn svg { width:16px; height:16px; }
.bm-btn-primary { background:var(--bk-accent); color:var(--bk-accent-ink); box-shadow:var(--bk-shadow); }
.bm-btn-primary:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); transform:translateY(-1px); box-shadow:var(--bk-shadow-lg); }
.bm-btn-ghost { background:var(--bk-surface); color:var(--bk-text-soft); border-color:var(--bk-border); }
.bm-btn-ghost:hover { border-color:var(--bk-gold); color:var(--bk-text); }
.bm-btn-gold { background:var(--bk-gold-soft); color:var(--bk-gold-strong); border-color:color-mix(in srgb, var(--bk-gold) 40%, transparent); }
.bm-btn-gold:hover { border-color:var(--bk-gold); color:var(--bk-gold-strong); background:color-mix(in srgb, var(--bk-gold) 22%, var(--bk-surface)); }
.bm-btn-danger { background:var(--bk-surface); color:var(--bk-danger); border-color:var(--bk-border); }
.bm-btn-danger:hover { border-color:var(--bk-danger); background:var(--bk-danger-bg); color:var(--bk-danger); }
.bm-btn-block { width:100%; justify-content:center; }
.bm-btn:focus-visible { outline:none; box-shadow:0 0 0 3px var(--bk-accent-wash); }

.bm-clear { display:inline-flex; align-items:center; gap:5px; height:44px; padding:0 12px; border-radius:12px;
    color:var(--bk-danger); font-size:.84rem; font-weight:600; border:1px solid transparent; transition:background .15s; }
.bm-clear i, .bm-clear svg { width:14px; height:14px; stroke-width:2; }
.bm-clear:hover { background:var(--bk-danger-bg); color:var(--bk-danger); }

/* ── Overview stats ── */
.bm-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:22px; }
.bm-stat { position:relative; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px;
    padding:15px 16px 15px 20px; overflow:hidden; box-shadow:var(--bk-shadow); }
.bm-stat::before { content:''; position:absolute; inset-inline-start:0; top:0; bottom:0; width:4px; background:var(--accent, var(--bk-accent)); }
.bm-stat-label { display:flex; align-items:center; gap:7px; font-size:.71rem; text-transform:uppercase; letter-spacing:.07em; color:var(--bk-text-muted); font-weight:700; }
.bm-stat-label i, .bm-stat-label svg { width:14px; height:14px; color:var(--accent, var(--bk-accent)); stroke-width:2; }
.bm-stat-value { display:block; margin-top:6px; font-family:var(--bm-serif); font-size:1.75rem; font-weight:600;
    color:var(--bk-text); line-height:1.1; font-variant-numeric:tabular-nums; }

/* ── Toolbar & filters ── */
.bm-toolbar { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:12px; margin-bottom:16px; box-shadow:var(--bk-shadow); }
.bm-toolbar-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.bm-search { position:relative; flex:1 1 280px; min-width:200px; display:flex; align-items:center; }
.bm-search-btn { position:absolute; inset-inline-start:6px; top:50%; transform:translateY(-50%); width:30px; height:30px;
    display:inline-flex; align-items:center; justify-content:center; padding:0; border:none; background:transparent;
    color:var(--bk-text-muted); cursor:pointer; border-radius:8px; transition:color .15s, background .15s; }
.bm-search-btn:hover { color:var(--bk-accent); background:var(--bk-accent-wash); }
.bm-search-btn i, .bm-search-btn svg { width:16px; height:16px; stroke-width:2; pointer-events:none; }
.bm-search input { width:100%; height:44px; padding-inline:42px 14px; border-radius:11px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.bm-search input::placeholder { color:var(--bk-text-muted); }
.bm-search input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.bm-select { height:44px; padding-inline:14px 32px; border-radius:11px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.87rem; cursor:pointer; outline:none; transition:border-color .15s, box-shadow .15s; max-width:100%; }
.bm-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.bm-select option { background:var(--bk-surface); color:var(--bk-text); }

/* Sort direction toggle */
.bm-dir { width:44px; height:44px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center;
    border-radius:11px; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); cursor:pointer; transition:all .15s; }
.bm-dir i, .bm-dir svg { width:16px; height:16px; }
.bm-dir:hover { border-color:var(--bk-accent); color:var(--bk-accent); }

/* View toggle (table / card) */
.bm-viewtoggle { display:inline-flex; gap:3px; padding:4px; background:var(--bk-bg); border:1px solid var(--bk-border); border-radius:11px; flex-shrink:0; }
.bm-vt { width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; border:none; background:transparent;
    color:var(--bk-text-muted); border-radius:8px; cursor:pointer; transition:all .16s; }
.bm-vt i, .bm-vt svg { width:16px; height:16px; }
.bm-vt:hover { color:var(--bk-text); }
.bm-vt.is-active { background:var(--bk-accent); color:var(--bk-accent-ink); }

/* ── Card (table container) ── */
.bm-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bm-radius); overflow:hidden; box-shadow:var(--bk-shadow); }
.bm-table-scroll { overflow-x:auto; }
.bm-table { width:100%; border-collapse:collapse; min-width:720px; }
.bm-table thead th { font-size:.71rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted);
    font-weight:700; text-align:start; padding:13px 18px; background:var(--bk-bg); border-bottom:1px solid var(--bk-border); white-space:nowrap; }
.bm-table thead th.bm-end { text-align:end; }
.bm-table thead th.bm-center { text-align:center; }
.bm-sort { color:inherit; display:inline-flex; align-items:center; gap:5px; }
.bm-sort:hover { color:var(--bk-text); }
.bm-sort.is-active { color:var(--bk-accent); }
.bm-sort-caret { width:13px; height:13px; color:var(--bk-accent); }
.bm-table tbody td { padding:14px 18px; border-bottom:1px solid var(--bk-border); vertical-align:middle; }
.bm-table tbody tr { transition:background .15s; }
.bm-table tbody tr.bm-row-link { cursor:pointer; }
.bm-table tbody tr:hover { background:var(--bk-accent-wash); }
.bm-table tbody tr:last-child td { border-bottom:none; }
.bm-center { text-align:center; }
.bm-end { text-align:end; }

/* Branch identity cell */
.bm-branch { display:flex; align-items:center; gap:13px; }
.bm-avatar { width:46px; height:46px; border-radius:13px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    color:var(--bk-accent); background:var(--bk-accent-wash); border:1px solid var(--bk-border); }
.bm-avatar i, .bm-avatar svg { width:20px; height:20px; stroke-width:1.9; }
.bm-branch-name { font-weight:600; color:var(--bk-text); font-size:.94rem; line-height:1.25; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.bm-branch-ar { font-size:.82rem; color:var(--bk-text-soft); margin-top:1px; }
.bm-branch-meta { display:flex; flex-wrap:wrap; gap:4px 14px; margin-top:5px; }
.bm-meta-line { display:inline-flex; align-items:center; gap:6px; font-size:.78rem; color:var(--bk-text-muted); }
.bm-meta-line i, .bm-meta-line svg { width:13px; height:13px; opacity:.7; stroke-width:1.9; flex-shrink:0; }

/* Company chip */
.bm-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; background:var(--bk-bg);
    border:1px solid var(--bk-border); color:var(--bk-text-soft); font-size:.78rem; font-weight:500; white-space:nowrap; max-width:100%; }
.bm-chip i, .bm-chip svg { width:13px; height:13px; color:var(--bk-gold-strong); stroke-width:1.9; flex-shrink:0; }
.bm-chip span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* Count pill */
.bm-count { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-width:34px; height:26px; padding:0 9px;
    border-radius:999px; background:var(--bk-bg); border:1px solid var(--bk-border); color:var(--bk-text-soft);
    font-size:.78rem; font-weight:600; font-variant-numeric:tabular-nums; }
.bm-count i, .bm-count svg { width:12px; height:12px; opacity:.6; }
.bm-count.is-zero { color:var(--bk-text-muted); opacity:.6; }

/* Status + head-office badges (icon + text, never colour-only) */
.bm-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:999px; font-size:.75rem; font-weight:600; white-space:nowrap; border:1px solid transparent; }
.bm-badge i, .bm-badge svg { width:12px; height:12px; stroke-width:2.2; }
.bm-badge-active      { color:var(--bk-success); background:var(--bk-success-bg); border-color:color-mix(in srgb, var(--bk-success) 28%, transparent); }
.bm-badge-inactive    { color:var(--bk-text-muted); background:var(--bk-surface-2); border-color:var(--bk-border); }
.bm-badge-maintenance { color:var(--bk-warning); background:var(--bk-warning-bg); border-color:color-mix(in srgb, var(--bk-warning) 28%, transparent); }
.bm-badge-head        { color:var(--bk-gold-strong); background:var(--bk-gold-soft); border-color:color-mix(in srgb, var(--bk-gold) 34%, transparent); }
.bm-dash { color:var(--bk-text-muted); opacity:.5; }

/* Actions */
.bm-actions { display:flex; gap:6px; justify-content:flex-end; align-items:center; }
.bm-act { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:9px;
    border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-muted); cursor:pointer; transition:all .15s; }
.bm-act i, .bm-act svg { width:15px; height:15px; stroke-width:1.9; }
.bm-act:hover { border-color:var(--bk-gold); color:var(--bk-gold-strong); background:var(--bk-gold-soft); }
.bm-act-primary:hover { border-color:var(--bk-accent); color:var(--bk-accent); background:var(--bk-accent-wash); }
.bm-act-danger:hover { border-color:var(--bk-danger); color:var(--bk-danger); background:var(--bk-danger-bg); }
.bm-act.dropdown-toggle::after { display:none; }
/* Dropdown menu items (reuse themed .dropdown-menu from booksy-custom) */
.bm-menu { min-width:210px; padding:6px; border-radius:12px !important; }
.bm-menu .dropdown-item { display:flex; align-items:center; gap:10px; border-radius:8px; padding:9px 11px; font-size:.85rem; }
.bm-menu .dropdown-item i, .bm-menu .dropdown-item svg { width:15px; height:15px; stroke-width:1.9; }
.bm-menu .dropdown-item .bm-menu-count { margin-inline-start:auto; }

/* ── Card / grid view ── */
.bm-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px,1fr)); gap:16px; }
.bm-bcard { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bm-radius); overflow:hidden;
    box-shadow:var(--bk-shadow); display:flex; flex-direction:column; transition:border-color .2s, box-shadow .2s, transform .2s; }
.bm-bcard:hover { border-color:color-mix(in srgb, var(--bk-accent) 32%, var(--bk-border)); box-shadow:var(--bk-shadow-lg); transform:translateY(-3px); }
.bm-bcard-head { display:flex; align-items:flex-start; gap:13px; padding:18px 18px 14px; border-bottom:1px solid var(--bk-border);
    background:linear-gradient(135deg, var(--bk-accent-wash), transparent); }
.bm-bcard-id { flex:1; min-width:0; }
.bm-bcard-tabs { display:flex; gap:2px; padding:0 12px; border-bottom:1px solid var(--bk-border); overflow-x:auto; }
.bm-tab { display:flex; align-items:center; gap:6px; padding:11px 13px; font-size:.78rem; font-weight:600; color:var(--bk-text-muted);
    background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; white-space:nowrap; transition:color .16s, border-color .16s; }
.bm-tab i, .bm-tab svg { width:14px; height:14px; }
.bm-tab:hover { color:var(--bk-text-soft); }
.bm-tab.is-active { color:var(--bk-accent); border-bottom-color:var(--bk-accent); }
.bm-tab-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px;
    border-radius:999px; background:var(--bk-bg); border:1px solid var(--bk-border); font-size:.65rem; font-weight:700; font-variant-numeric:tabular-nums; }
.bm-tab.is-active .bm-tab-count { background:var(--bk-accent-wash); border-color:color-mix(in srgb, var(--bk-accent) 30%, transparent); color:var(--bk-accent); }
.bm-panel { display:none; padding:16px 18px; }
.bm-panel.is-active { display:block; }
.bm-mini { display:flex; align-items:center; gap:10px; padding:9px 11px; border-radius:10px; background:var(--bk-bg);
    border:1px solid var(--bk-border); margin-bottom:8px; }
.bm-mini:last-child { margin-bottom:0; }
.bm-mini-ic { width:32px; height:32px; border-radius:9px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent); font-size:.72rem; font-weight:700; }
.bm-mini-ic i, .bm-mini-ic svg { width:14px; height:14px; }
.bm-mini-body { flex:1; min-width:0; }
.bm-mini-name { font-size:.83rem; font-weight:600; color:var(--bk-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bm-mini-meta { font-size:.72rem; color:var(--bk-text-muted); margin-top:1px; }
.bm-panel-empty { display:flex; flex-direction:column; align-items:center; gap:9px; padding:22px 12px; text-align:center; color:var(--bk-text-muted); }
.bm-panel-empty i, .bm-panel-empty svg { width:22px; height:22px; opacity:.45; }
.bm-panel-empty span { font-size:.8rem; }
.bm-panel-add { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:999px; font-size:.78rem; font-weight:600;
    color:var(--bk-accent); background:var(--bk-accent-wash); border:1px dashed color-mix(in srgb, var(--bk-accent) 45%, transparent); transition:all .18s; }
.bm-panel-add:hover { background:color-mix(in srgb, var(--bk-accent) 14%, transparent); border-style:solid; color:var(--bk-accent); }
.bm-panel-add i, .bm-panel-add svg { width:14px; height:14px; }
.bm-panel-foot { margin-top:12px; }

/* Working-hours mini grid (card view) */
.bm-hours { display:grid; grid-template-columns:1fr; gap:6px; }
.bm-hrow { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-radius:9px;
    background:var(--bk-bg); border:1px solid var(--bk-border); }
.bm-hrow.is-open { border-color:color-mix(in srgb, var(--bk-success) 26%, transparent); background:color-mix(in srgb, var(--bk-success) 6%, var(--bk-surface)); }
.bm-hday { font-size:.78rem; font-weight:600; color:var(--bk-text); }
.bm-htime { font-size:.76rem; font-weight:600; color:var(--bk-success); font-variant-numeric:tabular-nums; direction:ltr; }
.bm-hclosed { font-size:.74rem; color:var(--bk-text-muted); }

/* ── Empty state ── */
.bm-empty { display:flex; flex-direction:column; align-items:center; gap:14px; padding:60px 20px; text-align:center; }
.bm-empty-ic { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent); }
.bm-empty-ic i, .bm-empty-ic svg { width:28px; height:28px; stroke-width:1.6; }
.bm-empty-title { margin:0; color:var(--bk-text); font-size:1.05rem; font-weight:600; font-family:var(--bm-serif); }
.bm-empty-sub { margin:0; color:var(--bk-text-muted); font-size:.88rem; max-width:42ch; }

/* ── Pagination ── */
.bm-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    padding:14px 18px; border-top:1px solid var(--bk-border); }
.bm-pagination-info { font-size:.8rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.bm-pagination .pagination { margin:0; }
.bm-pagination .page-link { color:var(--bk-text-soft); border-color:var(--bk-border); background:var(--bk-surface); }
.bm-pagination .page-item.active .page-link { background:var(--bk-accent); border-color:var(--bk-accent); color:var(--bk-accent-ink); }
.bm-pagination .page-link:hover { background:var(--bk-accent-wash); color:var(--bk-text); }

/* ═══════════════ Forms (create / edit / working-hours) ═══════════════ */
.bm-form-card { max-width:920px; margin-inline:auto; background:var(--bk-surface); border:1px solid var(--bk-border);
    border-radius:var(--bm-radius); box-shadow:var(--bk-shadow); overflow:hidden; }
.bm-form-body { padding:26px 28px; }
.bm-section { border-top:1px solid var(--bk-border); margin-top:26px; padding-top:22px; }
.bm-section:first-child { border-top:0; margin-top:0; padding-top:0; }
.bm-section-head { display:flex; align-items:center; gap:9px; margin-bottom:4px; }
.bm-section-head i, .bm-section-head svg { width:16px; height:16px; color:var(--bk-gold-strong); stroke-width:1.9; }
.bm-section-title { font-size:.95rem; font-weight:700; color:var(--bk-text); margin:0; }
.bm-section-sub { font-size:.8rem; color:var(--bk-text-muted); margin:0 0 16px; }
.bm-label { display:block; font-size:.8rem; font-weight:600; color:var(--bk-text-soft); margin-bottom:7px; }
.bm-label .bm-req { color:var(--bk-danger); }
.bm-help { font-size:.78rem; color:var(--bk-text-muted); margin-top:6px; }
.bm-form-foot { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:18px 28px; border-top:1px solid var(--bk-border);
    background:var(--bk-bg); }
.bm-form-foot .bm-spacer { margin-inline-start:auto; }

/* Wizard steps */
.bm-wizard { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:24px; }
.bm-step { display:flex; align-items:center; gap:9px; color:var(--bk-text-muted); font-size:.85rem; font-weight:600; }
.bm-step-num { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:.78rem; font-weight:700; background:var(--bk-surface-2); color:var(--bk-text-muted); border:1px solid var(--bk-border); flex-shrink:0; }
.bm-step.is-active { color:var(--bk-accent); }
.bm-step.is-active .bm-step-num { background:var(--bk-accent); color:var(--bk-accent-ink); border-color:var(--bk-accent); }
.bm-step.is-done .bm-step-num { background:var(--bk-accent-wash); color:var(--bk-accent); border-color:color-mix(in srgb, var(--bk-accent) 35%, transparent); }
.bm-wizard-sep { width:16px; height:16px; color:var(--bk-text-muted); opacity:.5; flex-shrink:0; }

/* Enhance native Bootstrap fields used inside branch forms (token-driven) */
.bm-form-card .form-control, .bm-form-card .form-select { border-radius:11px; min-height:44px; }
.bm-form-card .form-control:focus, .bm-form-card .form-select:focus {
    border-color:var(--bk-accent) !important; box-shadow:0 0 0 3px var(--bk-accent-wash) !important; }

/* ── Responsive ── */
@media (max-width:1200px){ .bm-col-address { display:none; } }
@media (max-width:992px){ .bm-col-phone { display:none; } .bm-stats { grid-template-columns:repeat(2,1fr); } }
@media (max-width:768px){
    .bm-title { font-size:1.7rem; }
    .bm-table { min-width:0; }
    .bm-table thead th, .bm-table tbody td { padding:12px 14px; }
    .bm-head-actions { width:100%; }
    .bm-head-actions .bm-btn { flex:1 1 auto; justify-content:center; }
    .bm-grid { grid-template-columns:1fr; }
    .bm-form-body, .bm-form-foot { padding-inline:18px; }
}
@media (max-width:520px){ .bm-stats { grid-template-columns:1fr 1fr; } .bm-col-count { display:none; } }

@media (prefers-reduced-motion:reduce){
    .bm-reveal { animation:none; }
    .bm-btn, .bm-table tbody tr, .bm-act, .bm-bcard, .bm-tab, .bm-panel-add { transition:none; }
    .bm-bcard:hover { transform:none; }
}
</style>
@endpush
@endonce
