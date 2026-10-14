{{--
  Platform settings — ps-* styles. Token-driven (--bk-*), light/dark via the shell
  theme classes, RTL through logical properties only. Scoped under .ps so nothing
  leaks into other owner pages.
--}}
@once
@push('owner-styles')
<link rel="stylesheet" href="{{ asset('fonts/glowrez-type.css') }}">
<style>
.ps { --ps-serif:'Fraunces','Tajawal',Georgia,serif; --ps-r:12px; --ps-gap:20px; max-width:1180px; margin-inline:auto; }
.ps a { text-decoration:none; }
.ps [hidden] { display:none !important; }
.ps *:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }

/* ── Header ── */
.ps-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:22px; }
.ps-title { font-family:var(--ps-serif); font-size:2rem; font-weight:600; letter-spacing:-.015em; line-height:1.1; color:var(--bk-text); margin:0; }
.ps-sub { color:var(--bk-text-muted); font-size:.9rem; margin:7px 0 0; max-width:62ch; line-height:1.55; }
.ps-head-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

/* ── Buttons ── */
.ps-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:0 18px; border-radius:11px;
    font-size:.86rem; font-weight:600; border:1px solid transparent; cursor:pointer; white-space:nowrap; font-family:inherit;
    transition:background .16s, border-color .16s, color .16s, box-shadow .16s, opacity .16s; }
.ps-btn svg, .ps-btn i { width:16px; height:16px; flex-shrink:0; }
.ps-btn-primary { background:var(--bk-accent-fill, var(--bk-accent)); color:#fff; }
.ps-btn-primary:hover:not(:disabled) { background:var(--bk-accent-hover); color:#fff; }
.ps-btn-ghost { background:var(--bk-surface); color:var(--bk-text-soft); border-color:var(--bk-border); }
.ps-btn-ghost:hover:not(:disabled) { border-color:var(--bk-gold); color:var(--bk-text); }
.ps-btn-danger { background:var(--bk-surface); color:var(--bk-danger); border-color:var(--bk-border); }
.ps-btn-danger:hover:not(:disabled) { background:var(--bk-danger-bg); border-color:var(--bk-danger); }
.ps-btn:disabled, .ps-btn[aria-disabled="true"] { opacity:.45; cursor:not-allowed; }
.ps-btn.is-loading { position:relative; color:transparent !important; pointer-events:none; }
.ps-btn.is-loading::after { content:''; position:absolute; width:16px; height:16px; border-radius:50%; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; animation:ps-spin .7s linear infinite; }
.ps-btn-sm { min-height:36px; padding:0 12px; font-size:.8rem; border-radius:9px; }
@keyframes ps-spin { to { transform:rotate(360deg); } }

/* Status pill (also used inline) */
.ps-pill { display:inline-flex; align-items:center; gap:7px; min-height:34px; padding:0 13px; border-radius:999px; font-size:.78rem; font-weight:600;
    border:1px solid transparent; white-space:nowrap; }
.ps-pill svg { width:13px; height:13px; }
.ps-pill-live  { color:var(--bk-success); background:var(--bk-success-bg); border-color:color-mix(in srgb,var(--bk-success) 28%,transparent); }
.ps-pill-maint { color:var(--bk-warning); background:var(--bk-warning-bg); border-color:color-mix(in srgb,var(--bk-warning) 30%,transparent); }
.ps-pill-pause { color:var(--bk-danger);  background:var(--bk-danger-bg);  border-color:color-mix(in srgb,var(--bk-danger) 28%,transparent); }
.ps-pill-muted { color:var(--bk-text-muted); background:var(--bk-surface-2); border-color:var(--bk-border); }
.ps-pill-gold  { color:var(--bk-gold-strong); background:var(--bk-gold-soft); border-color:color-mix(in srgb,var(--bk-gold) 34%,transparent); }
button.ps-pill { cursor:pointer; font-family:inherit; }
button.ps-pill:hover { filter:brightness(.97); }

/* ── Shell: side nav + panel ── */
.ps-shell { display:grid; grid-template-columns:248px minmax(0,1fr); gap:24px; align-items:start; }
.ps-nav { position:sticky; top:84px; display:flex; flex-direction:column; gap:2px; }
.ps-tab { position:relative; display:flex; align-items:center; gap:12px; width:100%; padding:11px 12px; border-radius:11px; border:0; background:transparent;
    color:var(--bk-text-soft); text-align:start; cursor:pointer; font-family:inherit; transition:background .15s, color .15s; }
.ps-tab:hover { background:var(--bk-accent-wash); color:var(--bk-text); }
.ps-tab[aria-selected="true"] { background:var(--bk-accent-wash); color:var(--bk-accent); }
.ps-tab-ic { width:34px; height:34px; border-radius:10px; flex-shrink:0; display:grid; place-items:center; background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-text-muted); transition:inherit; }
.ps-tab[aria-selected="true"] .ps-tab-ic { background:var(--bk-accent-fill, var(--bk-accent)); border-color:transparent; color:#fff; }
.ps-tab-ic svg { width:16px; height:16px; }
.ps-tab-txt { display:flex; flex-direction:column; min-width:0; line-height:1.25; }
.ps-tab-name { font-size:.9rem; font-weight:600; }
.ps-tab-desc { font-size:.74rem; color:var(--bk-text-muted); font-weight:400; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ps-dot { position:absolute; inset-inline-end:12px; top:50%; width:8px; height:8px; margin-top:-4px; border-radius:50%; background:var(--bk-gold); box-shadow:0 0 0 3px var(--bk-gold-soft); opacity:0; transform:scale(.4); transition:opacity .15s, transform .15s; }
.ps-tab.is-dirty .ps-dot { opacity:1; transform:none; }

/* ── Panel ── */
.ps-panel { container:psp / inline-size; display:none; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:16px; box-shadow:var(--bk-shadow); }
.ps-panel.is-active { display:block; animation:ps-in .28s cubic-bezier(.2,.7,.2,1) both; }
@keyframes ps-in { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
.ps-panel-head { padding:24px 28px 18px; border-bottom:1px solid var(--bk-border); }
.ps-panel-title { font-family:var(--ps-serif); font-size:1.4rem; font-weight:600; letter-spacing:-.01em; color:var(--bk-text); margin:0; }
.ps-panel-desc { color:var(--bk-text-muted); font-size:.87rem; margin:5px 0 0; max-width:70ch; line-height:1.55; }

.ps-sec { display:grid; grid-template-columns:230px minmax(0,1fr); gap:8px 32px; padding:26px 28px; border-bottom:1px solid var(--bk-border); }
.ps-sec:last-of-type { border-bottom:0; }
.ps-sec-title { font-size:.93rem; font-weight:700; color:var(--bk-text); margin:0; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.ps-sec-desc { font-size:.8rem; color:var(--bk-text-muted); margin:5px 0 0; line-height:1.55; }
.ps-sec-body { display:flex; flex-direction:column; gap:var(--ps-gap); min-width:0; }

.ps-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:var(--ps-gap); }
.ps-span-2 { grid-column:1 / -1; }

/* ── Fields ── */
.ps-field { display:flex; flex-direction:column; gap:7px; min-width:0; }
.ps-label { font-size:.8rem; font-weight:600; color:var(--bk-text-soft); display:flex; justify-content:space-between; gap:10px; align-items:baseline; }
.ps-count { font-size:.72rem; font-weight:500; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.ps-count.is-near { color:var(--bk-warning); }
.ps-input, .ps-select, .ps-textarea { width:100%; min-height:44px; padding:0 14px; border-radius:11px; border:1px solid var(--bk-border); background:var(--bk-bg);
    color:var(--bk-text); font-size:.9rem; font-family:inherit; transition:border-color .15s, box-shadow .15s, background .15s; }
.ps-textarea { padding:11px 14px; line-height:1.55; resize:vertical; min-height:92px; }
.ps-select { padding-inline-end:34px; cursor:pointer; }
.ps-input::placeholder, .ps-textarea::placeholder { color:var(--bk-text-muted); opacity:.8; }
.ps-input:hover, .ps-select:hover, .ps-textarea:hover { border-color:var(--bk-border-strong, var(--bk-border)); }
.ps-input:focus, .ps-select:focus, .ps-textarea:focus { outline:none; border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.ps-input:disabled, .ps-select:disabled, .ps-textarea:disabled { opacity:.55; cursor:not-allowed; }
.ps-input[dir="ltr"], .ps-textarea[dir="ltr"] { text-align:start; }
html[dir="rtl"] .ps-input[dir="ltr"]:not(:placeholder-shown) { text-align:left; }
/* The project forces direction:rtl on some input types (tel…); an explicit dir on the field must win. */
.ps .ps-input[dir="ltr"], .ps .ps-textarea[dir="ltr"] { direction:ltr !important; }
.ps .ps-input[dir="rtl"], .ps .ps-textarea[dir="rtl"] { direction:rtl !important; }
.ps-field.has-error .ps-input, .ps-field.has-error .ps-select, .ps-field.has-error .ps-textarea { border-color:var(--bk-danger); }
.ps-help { font-size:.76rem; color:var(--bk-text-muted); line-height:1.5; }
.ps-err { display:flex; gap:6px; align-items:flex-start; font-size:.78rem; color:var(--bk-danger); font-weight:600; line-height:1.45; }
.ps-err svg { width:14px; height:14px; flex-shrink:0; margin-top:2px; }

/* Link/phone/email live check */
.ps-check { display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-height:30px; }
.ps-check-state { display:inline-flex; align-items:center; gap:6px; font-size:.76rem; font-weight:600; color:var(--bk-text-muted); }
.ps-check-state svg { width:13px; height:13px; }
.ps-check-state.is-ok  { color:var(--bk-success); }
.ps-check-state.is-bad { color:var(--bk-danger); }
.ps-mini { display:inline-flex; align-items:center; gap:6px; min-height:30px; padding:0 10px; border-radius:8px; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); font-size:.76rem; font-weight:600; cursor:pointer; font-family:inherit; transition:border-color .15s, color .15s; }
.ps-mini svg { width:13px; height:13px; }
.ps-mini:hover:not([aria-disabled="true"]) { border-color:var(--bk-gold); color:var(--bk-text); }
.ps-mini[aria-disabled="true"] { opacity:.4; pointer-events:none; }

/* ── Switch row ── */
.ps-switches { display:flex; flex-direction:column; border:1px solid var(--bk-border); border-radius:var(--ps-r); overflow:hidden; background:var(--bk-bg); }
.ps-switch { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 16px; cursor:pointer; min-height:56px; }
.ps-switch + .ps-switch { border-top:1px solid var(--bk-border); }
.ps-switch:hover { background:var(--bk-accent-wash); }
.ps-switch-txt { min-width:0; }
.ps-switch-title { font-size:.88rem; font-weight:600; color:var(--bk-text); }
.ps-switch-desc { font-size:.77rem; color:var(--bk-text-muted); margin-top:2px; line-height:1.45; }
.ps-toggle { position:relative; flex-shrink:0; width:42px; height:24px; }
.ps-toggle input { position:absolute; inset:0; width:100%; height:100%; margin:0; opacity:0; cursor:pointer; z-index:1; }
.ps-toggle i { position:absolute; inset:0; border-radius:999px; background:var(--bk-border-strong, #cfc8b6); transition:background .18s; }
.ps-toggle i::after { content:''; position:absolute; top:3px; inset-inline-start:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.3); transition:transform .18s cubic-bezier(.2,.7,.2,1); }
.ps-toggle input:checked + i { background:var(--bk-accent-fill, var(--bk-accent)); }
.ps-toggle input:checked + i::after { transform:translateX(18px); }
html[dir="rtl"] .ps-toggle input:checked + i::after { transform:translateX(-18px); }
.ps-toggle input:focus-visible + i { outline:2px solid var(--bk-accent); outline-offset:2px; }
.ps-toggle input:disabled { cursor:not-allowed; }
.ps-toggle input:disabled + i { opacity:.5; }

/* Channel row: input + its own visibility switch */
.ps-channel { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:12px 18px; align-items:start; }
.ps-channel > .ps-chan-switch { padding-top:27px; }
.ps-inline-switch { display:inline-flex; align-items:center; gap:10px; font-size:.8rem; font-weight:600; color:var(--bk-text-soft); cursor:pointer; min-height:44px; white-space:nowrap; }

/* Chips (working days) */
.ps-chips { display:flex; flex-wrap:wrap; gap:8px; }
.ps-chip { position:relative; }
.ps-chip input { position:absolute; inset:0; opacity:0; margin:0; cursor:pointer; width:100%; height:100%; }
.ps-chip span { display:inline-flex; align-items:center; min-height:40px; padding:0 15px; border-radius:999px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text-soft); font-size:.83rem; font-weight:600; transition:all .15s; }
.ps-chip input:checked + span { background:var(--bk-accent-wash); border-color:color-mix(in srgb,var(--bk-accent) 55%,transparent); color:var(--bk-accent); }
.ps-chip input:focus-visible + span { outline:2px solid var(--bk-accent); outline-offset:2px; }

/* Choice cards (site mode, app status) */
.ps-choices { display:grid; gap:10px; }
.ps-choices.cols-3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
.ps-choice { position:relative; display:flex; gap:12px; align-items:flex-start; padding:14px 15px; border-radius:var(--ps-r); border:1px solid var(--bk-border); background:var(--bk-bg); cursor:pointer; transition:border-color .15s, background .15s; }
.ps-choice input { position:absolute; inset:0; opacity:0; margin:0; cursor:pointer; width:100%; height:100%; }
.ps-choice:hover { border-color:var(--bk-border-strong, var(--bk-border)); }
.ps-choice-ic { width:34px; height:34px; border-radius:10px; display:grid; place-items:center; flex-shrink:0; background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-text-muted); }
.ps-choice-ic svg { width:16px; height:16px; }
.ps-choice-t { font-size:.88rem; font-weight:700; color:var(--bk-text); line-height:1.3; }
.ps-choice-d { font-size:.77rem; color:var(--bk-text-muted); margin-top:3px; line-height:1.45; }
.ps-choice:has(input:checked) { border-color:var(--bk-accent); background:var(--bk-accent-wash); box-shadow:0 0 0 1px var(--bk-accent) inset; }
.ps-choice:has(input:checked) .ps-choice-ic { background:var(--bk-accent-fill, var(--bk-accent)); border-color:transparent; color:#fff; }
.ps-choice:has(input[value="maintenance"]:checked) { border-color:var(--bk-warning); background:var(--bk-warning-bg); box-shadow:0 0 0 1px var(--bk-warning) inset; }
.ps-choice:has(input[value="maintenance"]:checked) .ps-choice-ic { background:var(--bk-warning); }
.ps-choice:has(input[value="paused"]:checked) { border-color:var(--bk-danger); background:var(--bk-danger-bg); box-shadow:0 0 0 1px var(--bk-danger) inset; }
.ps-choice:has(input[value="paused"]:checked) .ps-choice-ic { background:var(--bk-danger); }
.ps-choice:has(input:focus-visible) { outline:2px solid var(--bk-accent); outline-offset:2px; }
.ps-choice:has(input:disabled) { opacity:.6; cursor:not-allowed; }

/* Notes / callouts (no side stripes) */
.ps-note { display:flex; gap:11px; align-items:flex-start; padding:12px 14px; border-radius:var(--ps-r); background:var(--bk-surface-2); border:1px solid var(--bk-border); font-size:.82rem; color:var(--bk-text-soft); line-height:1.55; }
.ps-note svg { width:16px; height:16px; flex-shrink:0; margin-top:2px; color:var(--bk-gold-strong); }
.ps-note b { color:var(--bk-text); }
.ps-note-warn { background:var(--bk-warning-bg); border-color:color-mix(in srgb,var(--bk-warning) 30%,transparent); }
.ps-note-warn svg { color:var(--bk-warning); }

/* Sub tabs (releases) */
.ps-seg { display:inline-flex; gap:3px; padding:4px; background:var(--bk-bg); border:1px solid var(--bk-border); border-radius:12px; max-width:100%; overflow-x:auto; }
.ps-seg button { display:inline-flex; align-items:center; gap:8px; min-height:38px; padding:0 16px; border:0; border-radius:9px; background:transparent; color:var(--bk-text-muted); font-size:.84rem; font-weight:600; cursor:pointer; font-family:inherit; white-space:nowrap; transition:all .15s; }
.ps-seg button svg { width:15px; height:15px; }
.ps-seg button:hover { color:var(--bk-text); }
.ps-seg button[aria-selected="true"] { background:var(--bk-accent-fill, var(--bk-accent)); color:#fff; }
.ps-sub-panel[hidden] { display:none; }

/* Recorded vs running */
.ps-twin { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); border:1px solid var(--bk-border); border-radius:var(--ps-r); overflow:hidden; background:var(--bk-bg); }
.ps-twin > div { padding:14px 16px; min-width:0; }
.ps-twin > div + div { border-inline-start:1px solid var(--bk-border); }
.ps-twin h4 { font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; color:var(--bk-text-muted); margin:0 0 8px; font-weight:700; }
html[lang="ar"] .ps-twin h4 { letter-spacing:0; }
.ps-kv { display:flex; justify-content:space-between; gap:12px; font-size:.83rem; padding:3px 0; }
.ps-kv span:first-child { color:var(--bk-text-muted); }
.ps-kv span:last-child { color:var(--bk-text); font-weight:600; font-variant-numeric:tabular-nums; text-align:end; overflow-wrap:anywhere; }

/* Logo */
.ps-logo { display:flex; gap:18px; align-items:flex-start; flex-wrap:wrap; }
.ps-logo-box { width:168px; height:96px; border-radius:var(--ps-r); border:1px dashed var(--bk-border-strong, var(--bk-border)); background:var(--bk-bg); display:grid; place-items:center; overflow:hidden; color:var(--bk-text-muted); flex-shrink:0; }
.ps-logo-box img { max-width:100%; max-height:100%; object-fit:contain; padding:8px; }
.ps-logo-box svg { width:26px; height:26px; opacity:.5; }
.ps-logo-ctl { display:flex; flex-direction:column; gap:10px; min-width:200px; flex:1; }
.ps-file { position:relative; display:inline-flex; }
.ps-file input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }

/* ── Links tab ── */
.ps-linklist { display:flex; flex-direction:column; border:1px solid var(--bk-border); border-radius:var(--ps-r); background:var(--bk-bg); }
.ps-link { display:grid; grid-template-columns:auto auto minmax(0,1fr) auto; gap:12px 14px; align-items:start; padding:16px; position:relative; }
.ps-link + .ps-link { border-top:1px solid var(--bk-border); }
.ps-link.is-dragging { opacity:.5; background:var(--bk-accent-wash); }
.ps-link.is-over { box-shadow:0 -2px 0 0 var(--bk-accent) inset; }
.ps-grip { width:28px; height:44px; display:grid; place-items:center; color:var(--bk-text-muted); cursor:grab; border:0; background:transparent; border-radius:8px; touch-action:none; }
.ps-grip:hover { color:var(--bk-text); background:var(--bk-surface-2); }
.ps-grip:active { cursor:grabbing; }
.ps-grip svg { width:16px; height:16px; }
.ps-link-ic { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-accent); flex-shrink:0; }
.ps-link-main { display:flex; flex-direction:column; gap:10px; min-width:0; }
.ps-link-name { font-size:.92rem; font-weight:700; color:var(--bk-text); min-height:22px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.ps-link-ctl { display:flex; align-items:center; gap:6px; }
.ps-link-opts { display:flex; flex-wrap:wrap; gap:6px 22px; align-items:center; }
.ps-link-vis { font-size:.76rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.ps-link-vis svg { width:13px; height:13px; }
.ps-link-vis.is-on { color:var(--bk-success); }
.ps-link-vis.is-off { color:var(--bk-text-muted); }
.ps-iconbtn { width:44px; height:44px; display:inline-grid; place-items:center; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-muted); cursor:pointer; transition:all .15s; }
.ps-iconbtn svg { width:16px; height:16px; }
.ps-iconbtn:hover:not(:disabled) { border-color:var(--bk-gold); color:var(--bk-text); }
.ps-iconbtn.is-danger:hover { border-color:var(--bk-danger); color:var(--bk-danger); background:var(--bk-danger-bg); }
.ps-iconbtn:disabled { opacity:.35; cursor:not-allowed; }
.ps-linkbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.ps-empty { display:flex; flex-direction:column; align-items:center; gap:10px; text-align:center; padding:34px 20px; color:var(--bk-text-muted); }
.ps-empty svg { width:30px; height:30px; opacity:.45; }
.ps-empty b { color:var(--bk-text); font-size:.95rem; }
.ps-empty p { margin:0; font-size:.83rem; max-width:44ch; line-height:1.55; }

.ps-add { position:relative; }
.ps-menu { position:absolute; z-index:30; inset-inline-end:0; top:calc(100% + 6px); min-width:240px; max-height:340px; overflow:auto; padding:6px; border-radius:12px; background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg); }
.ps-menu[hidden] { display:none; }
.ps-menu button { display:flex; align-items:center; gap:11px; width:100%; min-height:42px; padding:0 11px; border:0; border-radius:8px; background:transparent; color:var(--bk-text); font-size:.86rem; font-weight:600; cursor:pointer; text-align:start; font-family:inherit; }
.ps-menu button:hover:not(:disabled) { background:var(--bk-accent-wash); }
.ps-menu button:disabled { opacity:.4; cursor:not-allowed; }
.ps-menu button svg { width:17px; height:17px; color:var(--bk-accent); }
.ps-menu hr { margin:6px 4px; border:0; border-top:1px solid var(--bk-border); }
.ps-menu small { margin-inline-start:auto; font-size:.7rem; color:var(--bk-text-muted); font-weight:500; }

.ps-pages { display:flex; flex-direction:column; border:1px solid var(--bk-border); border-radius:var(--ps-r); background:var(--bk-bg); }
.ps-page { display:flex; align-items:center; gap:14px; padding:13px 16px; flex-wrap:wrap; }
.ps-page + .ps-page { border-top:1px solid var(--bk-border); }
.ps-page-t { font-size:.86rem; font-weight:600; color:var(--bk-text); }
.ps-page-v { font-size:.78rem; color:var(--bk-text-muted); overflow-wrap:anywhere; }
.ps-page > :last-child { margin-inline-start:auto; }

/* Footer preview */
.ps-preview { border:1px solid var(--bk-border); border-radius:var(--ps-r); background:var(--bk-bg); padding:18px; display:flex; flex-direction:column; gap:12px; }
.ps-preview-tag { font-size:.83rem; color:var(--bk-text-soft); line-height:1.6; }
.ps-preview-bar { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-top:12px; border-top:1px solid var(--bk-border); font-size:.78rem; color:var(--bk-text-muted); }
.ps-preview-bar a { color:var(--bk-text-soft); text-decoration:underline !important; text-underline-offset:3px; }

/* ── Save bar ── */
.ps-save { position:sticky; bottom:0; z-index:20; display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; padding:14px 28px;
    border-top:1px solid var(--bk-border); background:color-mix(in srgb,var(--bk-surface) 94%,transparent); backdrop-filter:blur(8px); border-radius:0 0 16px 16px; }
.ps-save-state { display:inline-flex; align-items:center; gap:8px; font-size:.82rem; font-weight:600; color:var(--bk-text-muted); }
.ps-save-state svg { width:15px; height:15px; }
.ps-save-state.is-dirty { color:var(--bk-gold-strong); }
.ps-save-state.is-saved { color:var(--bk-success); }
.ps-save-btns { display:flex; gap:10px; }

/* ── Responsive ──
   Layout inside a panel follows the PANEL's width (container queries), not the viewport:
   the settings nav takes a fixed column, so the panel is narrower than the screen. */
@container psp (max-width: 880px) {
    .ps-sec { grid-template-columns:1fr; gap:14px; }
    .ps-choices.cols-3 { grid-template-columns:1fr; }
}
@container psp (max-width: 600px) {
    .ps-panel-head { padding:20px 18px 14px; }
    .ps-sec { padding:22px 18px; }
    .ps-grid { grid-template-columns:1fr; }
    .ps-channel { grid-template-columns:1fr; }
    .ps-channel > .ps-chan-switch { padding-top:0; }
    .ps-twin { grid-template-columns:1fr; }
    .ps-twin > div + div { border-inline-start:0; border-top:1px solid var(--bk-border); }
    .ps-link { grid-template-columns:auto minmax(0,1fr); }
    .ps-link .ps-link-ic { grid-column:2; grid-row:1; }
    .ps-link .ps-grip { grid-column:1; grid-row:1; }
    .ps-link .ps-link-main { grid-column:1 / -1; }
    .ps-link .ps-link-ctl { grid-column:1 / -1; justify-content:flex-end; flex-wrap:wrap; }
    .ps-save { padding:12px 18px; }
    .ps-save-btns { width:100%; }
    .ps-save-btns .ps-btn { flex:1; }
}
@media (max-width: 991.98px) {
    .ps-shell { grid-template-columns:1fr; gap:16px; }
    .ps-nav { position:static; flex-direction:row; overflow-x:auto; gap:6px; padding:2px 2px 8px; margin-inline:-4px; scrollbar-width:thin; }
    .ps-tab { width:auto; flex-shrink:0; padding:8px 14px 8px 10px; border:1px solid var(--bk-border); background:var(--bk-surface); }
    .ps-tab-desc { display:none; }
    .ps-dot { inset-inline-end:6px; top:8px; margin:0; }
    .ps-title { font-size:1.65rem; }
}
@media (max-width: 767.98px) {
    .ps-save { bottom:calc(60px + env(safe-area-inset-bottom, 0px)); border-radius:0; }
    .page-content { padding-bottom:140px !important; }
}
@media (prefers-reduced-motion: reduce) {
    .ps-panel.is-active, .ps-toggle i::after, .ps-btn, .ps-tab { animation:none !important; transition:none !important; }
}
/* Browser surfaces follow the palette */
.ps ::selection { background:color-mix(in srgb,var(--bk-accent) 28%,transparent); }
.ps input, .ps textarea { caret-color:var(--bk-accent); }
.ps input[type="number"] { font-variant-numeric:tabular-nums; }
</style>
@endpush
@endonce
