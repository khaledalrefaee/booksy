<style>
/* ══════════════════════════════════════════════════════════════════
   SIDEBAR v4 — Branch-context model
   A single flat, grouped navigation with a branch context selector on
   top. Token-based, so light / dark / RTL are automatic.
══════════════════════════════════════════════════════════════════ */
.bk-sidebar-v4 { display:flex; flex-direction:column; }
.bk-sidebar-v4 .sidebar-header { justify-content:flex-start; flex-shrink:0; }
.bk-sidebar-v4 .sidebar-body.bk-sb4 {
    flex:1; min-height:0; display:flex; flex-direction:column;
    padding:0; overflow:hidden;
}

/* ── Branch context selector ─────────────────────────────────────── */
.bk-ctx { position:relative; padding:12px 12px 6px; flex-shrink:0; }
.bk-ctx-btn {
    width:100%; display:flex; align-items:center; gap:10px;
    background:var(--bk-surface-2); border:1px solid var(--bk-border);
    border-radius:12px; padding:8px 10px; cursor:pointer; text-align:start;
    color:var(--bk-text); transition:border-color .15s, background .15s;
}
.bk-ctx-btn:hover  { border-color:var(--bk-border-strong); }
.bk-ctx.open .bk-ctx-btn { border-color:var(--bk-accent); }
.bk-ctx-mark {
    width:32px; height:32px; border-radius:9px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent);
}
.bk-ctx-mark svg { width:16px; height:16px; }
.bk-ctx-text { flex:1; min-width:0; display:flex; flex-direction:column; line-height:1.25; }
.bk-ctx-co  { font-size:10px; color:var(--bk-text-muted); text-transform:uppercase; letter-spacing:.4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-ctx-cur { font-size:13px; font-weight:700; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-ctx-caret { width:15px; height:15px; flex-shrink:0; color:var(--bk-text-muted); transition:transform .18s; }
.bk-ctx.open .bk-ctx-caret { transform:rotate(180deg); }

.bk-ctx-menu {
    margin-top:6px; background:var(--bk-surface); border:1px solid var(--bk-border);
    border-radius:12px; box-shadow:var(--bk-shadow-xl, 0 12px 32px rgba(0,0,0,.18)); overflow:hidden;
}
.bk-ctx-search { display:flex; align-items:center; gap:8px; padding:9px 12px; border-bottom:1px solid var(--bk-border); }
.bk-ctx-search svg { width:15px; height:15px; color:var(--bk-text-muted); flex-shrink:0; }
.bk-ctx-search input { border:0; background:transparent; outline:none; width:100%; font-size:12.5px; color:var(--bk-text); }
.bk-ctx-list { max-height:min(46vh,320px); overflow-y:auto; padding:5px; }
.bk-ctx-row {
    display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:9px;
    text-decoration:none; color:var(--bk-text); font-size:12.5px; min-height:42px;
}
.bk-ctx-row:hover { background:var(--bk-surface-2); }
.bk-ctx-row.active { background:var(--bk-accent-wash); color:var(--bk-accent); font-weight:700; }
.bk-ctx-row-ic { width:26px; height:26px; border-radius:7px; display:flex; align-items:center; justify-content:center; background:var(--bk-surface-2); flex-shrink:0; }
.bk-ctx-row.active .bk-ctx-row-ic { background:color-mix(in srgb, var(--bk-accent) 18%, transparent); }
.bk-ctx-row-ic svg { width:14px; height:14px; }
.bk-ctx-row-name { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-ctx-check { width:15px; height:15px; color:var(--bk-accent); flex-shrink:0; }
.bk-ctx-empty { padding:14px; text-align:center; font-size:12px; color:var(--bk-text-muted); }
.bk-ctx-manage {
    display:flex; align-items:center; gap:8px; padding:10px 14px;
    border-top:1px solid var(--bk-border);
    font-size:12px; font-weight:600; color:var(--bk-text-soft); text-decoration:none;
}
.bk-ctx-manage:hover { background:var(--bk-surface-2); color:var(--bk-text); }
.bk-ctx-manage svg { width:14px; height:14px; }

/* ── Navigation ──────────────────────────────────────────────────── */
.bk-nav { flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; padding:2px 10px 20px; }
.bk-nav-group { margin-top:13px; }
.bk-nav-group:first-child { margin-top:4px; }
.bk-nav-title {
    font-size:.64rem; font-weight:700; letter-spacing:1.1px; text-transform:uppercase;
    color:var(--bk-text-muted); padding:6px 10px 5px;
}
.bk-nl {
    display:flex; align-items:center; gap:11px; padding:8px 10px; border-radius:9px;
    color:var(--bk-text-soft); text-decoration:none; font-size:13px; font-weight:500;
    position:relative; transition:background .13s, color .13s;
}
.bk-nl svg { width:16px; height:16px; flex-shrink:0; opacity:.82; }
.bk-nl span { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-nl:hover { background:var(--bk-sidebar-hover); color:var(--bk-text); }
.bk-nl.active { background:var(--bk-accent-wash); color:var(--bk-accent); font-weight:700; }
.bk-nl.active svg { opacity:1; }
.bk-nl.active::before {
    content:''; position:absolute; inset-inline-start:0; top:50%; transform:translateY(-50%);
    width:3px; height:18px; border-radius:0 3px 3px 0; background:var(--bk-gold);
}
[dir="rtl"] .bk-nl.active::before { border-radius:3px 0 0 3px; }
.bk-nl-badge {
    flex:0 0 auto; background:var(--bk-gold-soft); color:var(--bk-gold-strong);
    border-radius:999px; font-size:10.5px; font-weight:800; padding:2px 7px; line-height:1.4;
}
.bk-nl-muted { opacity:.7; }
.bk-nl-muted:hover { opacity:1; }

/* ── Folded desktop (icons only) ─────────────────────────────────── */
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-ctx-text,
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-ctx-caret,
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-nav-title,
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-nl span,
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-nl-badge,
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .sidebar-brand span { display:none; }
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-ctx { padding:12px 8px 6px; }
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-ctx-btn,
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-nl { justify-content:center; padding-inline:9px; }
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-nav { padding-inline:8px; }
.sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-v4 .bk-ctx-menu {
    position:absolute; inset-inline-start:8px; top:56px; width:236px; z-index:1040;
}

@media (prefers-reduced-motion:reduce){
    .bk-ctx-caret, .bk-nl, .bk-ctx-btn { transition:none; }
}
</style>
