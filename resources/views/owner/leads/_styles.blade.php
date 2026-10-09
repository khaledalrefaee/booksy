{{-- Leads CRM — ld-* styles. Builds on the shared bm-* editorial system (_ui). Token-driven: light + dark, RTL-aware. --}}
@include('owner.branches.partials._ui')
@once
@push('owner-styles')
<style>
/* ═══════════ Leads (ld-*) ═══════════ */
.ld-wrap a { text-decoration:none; }

/* Pipeline cards: six across, each one is a filter shortcut */
.ld-stats { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
.ld-stat { position:relative; display:block; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:14px 16px;
    box-shadow:var(--bk-shadow); transition:border-color .18s, transform .18s; color:inherit; }
a.ld-stat:hover { border-color:var(--bk-accent); transform:translateY(-1px); color:inherit; }
.ld-stat.is-active { border-color:var(--bk-accent); background:var(--bk-accent-wash); }
.ld-stat-label { display:flex; align-items:center; gap:7px; font-size:.72rem; font-weight:700; color:var(--bk-text-muted); }
.ld-stat-label i, .ld-stat-label svg { width:13px; height:13px; stroke-width:2; color:var(--accent, var(--bk-accent)); }
.ld-stat-value { display:block; margin-top:6px; font-family:var(--bm-serif); font-size:1.7rem; font-weight:600; color:var(--bk-text); line-height:1; font-variant-numeric:tabular-nums; }
.ld-stat-sub { display:block; margin-top:5px; font-size:.72rem; color:var(--bk-text-muted); }

.ld-funnel { display:flex; flex-wrap:wrap; gap:6px 22px; align-items:center; margin-bottom:22px; font-size:.82rem; color:var(--bk-text-soft); }
.ld-funnel b { color:var(--bk-text); font-variant-numeric:tabular-nums; }
.ld-funnel .ld-hint { color:var(--bk-text-muted); }

/* Insights */
.ld-panel { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bm-radius); box-shadow:var(--bk-shadow); margin-bottom:18px; }
.ld-panel > summary { list-style:none; display:flex; align-items:center; gap:10px; min-height:52px; padding:10px 18px; cursor:pointer; font-weight:700; font-size:.92rem; color:var(--bk-text); }
.ld-panel > summary::-webkit-details-marker { display:none; }
.ld-panel > summary .ld-sum-hint { font-weight:500; font-size:.76rem; color:var(--bk-text-muted); margin-inline-start:auto; }
.ld-panel > summary .ld-caret { transition:transform .2s; width:16px; height:16px; color:var(--bk-text-muted); }
.ld-panel[open] > summary .ld-caret { transform:rotate(180deg); }
.ld-panel-body { padding:4px 18px 18px; border-top:1px solid var(--bk-border); }
.ld-insights { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:22px 28px; padding-top:16px; }
.ld-ins h3 { margin:0 0 10px; font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--bk-text-muted); }
.ld-bars { list-style:none; margin:0; padding:0; display:grid; gap:9px; }
.ld-bar { display:grid; grid-template-columns:1fr auto; gap:3px 10px; align-items:baseline; font-size:.82rem; }
.ld-bar-name { color:var(--bk-text); font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ld-bar-num { color:var(--bk-text-soft); font-variant-numeric:tabular-nums; font-weight:600; }
.ld-bar-num small { color:var(--bk-text-muted); font-weight:500; margin-inline-start:6px; }
.ld-bar-track { grid-column:1 / -1; height:5px; border-radius:5px; background:var(--bk-surface-2); overflow:hidden; }
.ld-bar-fill { display:block; height:100%; border-radius:5px; background:var(--bk-accent); }
.ld-empty-line { font-size:.82rem; color:var(--bk-text-muted); margin:0; }

/* Tracking-link builder */
.ld-links { display:grid; gap:14px; padding-top:16px; }
.ld-link-top { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
.ld-link-top .bm-select, .ld-link-top input { flex:1 1 220px; }
.ld-linkrows { display:grid; gap:8px; }
.ld-linkrow { display:flex; align-items:center; gap:10px; padding:8px 10px 8px 14px; background:var(--bk-bg); border:1px solid var(--bk-border); border-radius:11px; }
.ld-linkrow-src { flex:0 0 96px; font-size:.78rem; font-weight:700; color:var(--bk-text-soft); }
.ld-linkrow code { flex:1; min-width:0; direction:ltr; text-align:start; font-size:.78rem; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; background:none; padding:0; }

/* Filters */
.ld-filters { display:none; margin-top:12px; padding-top:12px; border-top:1px dashed var(--bk-border); }
.ld-filters.is-open { display:grid; grid-template-columns:repeat(auto-fill,minmax(168px,1fr)); gap:10px; }
.ld-filters .bm-select { width:100%; }
.ld-filter-count { display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 6px; border-radius:999px; background:var(--bk-accent); color:var(--bk-accent-ink); font-size:.7rem; font-weight:700; }

/* Status control: a select that reads as a badge */
.ld-status { appearance:none; -webkit-appearance:none; border:1px solid transparent; border-radius:999px; min-height:34px; padding:0 30px 0 12px; font-size:.78rem; font-weight:700; cursor:pointer;
    background-repeat:no-repeat; background-position:right 10px center; background-size:12px; max-width:100%;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); }
[dir="rtl"] .ld-status { padding:0 12px 0 30px; background-position:left 10px center; }
.ld-status:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }
.ld-status[disabled] { cursor:progress; opacity:.65; }
.ld-tone-gold   { color:var(--bk-gold-strong); background-color:var(--bk-gold-soft); border-color:color-mix(in srgb, var(--bk-gold) 36%, transparent); }
.ld-tone-info   { color:var(--bk-info); background-color:var(--bk-info-bg); border-color:color-mix(in srgb, var(--bk-info) 28%, transparent); }
.ld-tone-active { color:var(--bk-accent); background-color:var(--bk-accent-wash); border-color:color-mix(in srgb, var(--bk-accent) 30%, transparent); }
.ld-tone-warn   { color:var(--bk-warning); background-color:var(--bk-warning-bg); border-color:color-mix(in srgb, var(--bk-warning) 28%, transparent); }
.ld-tone-success{ color:var(--bk-success); background-color:var(--bk-success-bg); border-color:color-mix(in srgb, var(--bk-success) 32%, transparent); }
.ld-tone-muted  { color:var(--bk-text-muted); background-color:var(--bk-surface-2); border-color:var(--bk-border); }
.ld-status option { color:var(--bk-text); background:var(--bk-surface); }

/* Row bits */
.ld-name { font-weight:600; color:var(--bk-text); font-size:.92rem; line-height:1.3; }
.ld-sub { font-size:.78rem; color:var(--bk-text-muted); margin-top:2px; }
.ld-ltr { direction:ltr; unicode-bidi:isolate; display:inline-block; font-variant-numeric:tabular-nums; }
.ld-biz { font-weight:600; font-size:.88rem; color:var(--bk-text); }
.ld-repeat { display:inline-flex; align-items:center; gap:4px; margin-inline-start:6px; padding:1px 8px; border-radius:999px; font-size:.68rem; font-weight:700; color:var(--bk-gold-strong); background:var(--bk-gold-soft); }
.ld-new-dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--bk-gold); margin-inline-end:8px; vertical-align:middle; }
.ld-date { font-size:.82rem; color:var(--bk-text-soft); font-variant-numeric:tabular-nums; white-space:nowrap; }
.ld-date small { display:block; color:var(--bk-text-muted); font-size:.72rem; }
.bm-act.ld-wa:hover { border-color:var(--bk-accent); color:var(--bk-accent); background:var(--bk-accent-wash); }
.bm-act[aria-disabled="true"] { opacity:.35; pointer-events:none; }

/* Cards replace the table below 1440px (the nine columns need ~1100px of content width) */
.ld-cards { display:none; padding:14px; gap:12px; grid-template-columns:repeat(auto-fill,minmax(min(100%,400px),1fr)); }
.ld-card { padding:14px 16px; border:1px solid var(--bk-border); border-radius:14px; background:var(--bk-surface); display:grid; gap:10px; align-content:start; }
.ld-card-top { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.ld-card-meta { display:flex; flex-wrap:wrap; gap:4px 14px; font-size:.78rem; color:var(--bk-text-muted); }
.ld-card-foot { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; padding-top:10px; border-top:1px solid var(--bk-border); }
.ld-card .bm-act { width:40px; height:40px; }
.ld-table th, .ld-table td { padding:12px 12px; }
.ld-table .bm-act { width:32px; height:32px; }
.ld-table .bm-actions { gap:4px; }
.ld-type { font-size:.84rem; color:var(--bk-text-soft); }
@media (max-width:1439.98px) {
    .ld-table-wrap { display:none; }
    .ld-cards { display:grid; }
}
@media (max-width:991.98px) { .ld-stats { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media (max-width:575.98px) { .ld-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .ld-stat-value { font-size:1.5rem; } .ld-cards { padding:10px; } }

/* ═══════════ Detail page ═══════════ */
.ld-detail { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(0,1fr); gap:18px; align-items:start; }
@media (max-width:991.98px) { .ld-detail { grid-template-columns:1fr; } }
.ld-box { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bm-radius); box-shadow:var(--bk-shadow); padding:18px 20px; margin-bottom:16px; }
.ld-box h2 { margin:0 0 12px; font-size:.78rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--bk-text-muted); display:flex; align-items:center; gap:8px; }
.ld-box h2 i, .ld-box h2 svg { width:14px; height:14px; stroke-width:2; color:var(--bk-gold-strong); }
.ld-dl { margin:0; display:grid; gap:0; }
.ld-dl > div { display:grid; grid-template-columns:minmax(110px,34%) 1fr; gap:12px; padding:10px 0; border-bottom:1px solid var(--bk-border); align-items:baseline; }
.ld-dl > div:last-child { border-bottom:0; }
.ld-dl dt { font-size:.8rem; color:var(--bk-text-muted); font-weight:500; }
.ld-dl dd { margin:0; font-size:.92rem; font-weight:600; color:var(--bk-text); overflow-wrap:anywhere; }
.ld-dl dd a { color:var(--bk-accent); font-weight:600; }
.ld-dl dd a:hover { text-decoration:underline; }
.ld-dl dd .ld-none { color:var(--bk-text-muted); font-weight:400; }
.ld-tags { display:flex; flex-wrap:wrap; gap:8px; }
.ld-tag { display:inline-flex; align-items:center; gap:6px; padding:6px 13px; border-radius:999px; font-size:.82rem; font-weight:600; color:var(--bk-accent); background:var(--bk-accent-wash); border:1px solid color-mix(in srgb, var(--bk-accent) 24%, transparent); }
.ld-hero-actions { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
.ld-hero-actions .bm-btn { height:46px; }
.ld-wa-btn { background:var(--bk-accent); color:var(--bk-accent-ink); }
.ld-meta-strip { display:flex; flex-wrap:wrap; gap:6px 22px; margin-top:12px; font-size:.82rem; color:var(--bk-text-muted); }
.ld-meta-strip b { color:var(--bk-text-soft); font-weight:600; }

/* Notes + timeline */
.ld-note-form textarea { width:100%; min-height:96px; resize:vertical; padding:12px 14px; border-radius:11px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; line-height:1.55; }
.ld-note-form textarea:focus { outline:none; border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.ld-note-form .ld-note-foot { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:10px; }
.ld-err { color:var(--bk-danger); font-size:.8rem; margin-top:6px; }
.ld-timeline { list-style:none; margin:0; padding:0; position:relative; }
.ld-tl { position:relative; display:grid; grid-template-columns:30px 1fr; gap:12px; padding-bottom:18px; }
.ld-tl:last-child { padding-bottom:0; }
.ld-tl::before { content:""; position:absolute; top:30px; bottom:0; inset-inline-start:14px; width:1px; background:var(--bk-border); }
.ld-tl:last-child::before { display:none; }
.ld-tl-ic { width:30px; height:30px; border-radius:50%; display:grid; place-items:center; background:var(--bk-bg); border:1px solid var(--bk-border); color:var(--bk-text-muted); z-index:1; }
.ld-tl-ic i, .ld-tl-ic svg { width:14px; height:14px; stroke-width:2; }
.ld-tl.t-created .ld-tl-ic, .ld-tl.t-resubmitted .ld-tl-ic { color:var(--bk-gold-strong); background:var(--bk-gold-soft); border-color:color-mix(in srgb, var(--bk-gold) 36%, transparent); }
.ld-tl.t-status_changed .ld-tl-ic { color:var(--bk-accent); background:var(--bk-accent-wash); border-color:color-mix(in srgb, var(--bk-accent) 30%, transparent); }
.ld-tl.t-note .ld-tl-ic { color:var(--bk-info); background:var(--bk-info-bg); border-color:color-mix(in srgb, var(--bk-info) 28%, transparent); }
.ld-tl-title { font-weight:700; font-size:.88rem; color:var(--bk-text); line-height:1.4; }
.ld-tl-body { margin-top:6px; padding:10px 12px; border-radius:10px; background:var(--bk-bg); border:1px solid var(--bk-border); font-size:.88rem; color:var(--bk-text); line-height:1.6; white-space:pre-wrap; overflow-wrap:anywhere; }
.ld-tl-sub { margin-top:4px; font-size:.78rem; color:var(--bk-text-muted); }
.ld-tl-changes { margin:6px 0 0; padding:0; list-style:none; font-size:.8rem; color:var(--bk-text-soft); display:grid; gap:2px; }
.ld-tl-changes s { color:var(--bk-text-muted); }

@media (prefers-reduced-motion:reduce) { .ld-stat, .ld-panel > summary .ld-caret { transition:none; } }
</style>
@endpush
@endonce
