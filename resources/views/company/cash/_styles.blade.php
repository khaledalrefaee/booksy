{{-- Shared CSS for Cash Register pages --}}
<style>
/* ─── Hero ─────────────────────────────────────────────────────────────── */
.cash-hero {
    background: linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);
    border-radius: 22px; padding: 30px 32px 24px; margin-bottom: 24px;
    position: relative; overflow: hidden; color: #fff;
}
.cash-hero::before {
    content:''; position:absolute; top:-80px; left:-80px;
    width:260px; height:260px; border-radius:50%;
    background:rgba(102,126,234,.12); pointer-events:none;
}
.cash-hero::after {
    content:''; position:absolute; bottom:-60px; right:-40px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(250,112,154,.08); pointer-events:none;
}

/* ─── Balance card ──────────────────────────────────────────────────────── */
.balance-card {
    background: rgba(255,255,255,.06); border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 16px; padding: 18px 22px; backdrop-filter: blur(4px);
}
.balance-value { font-size: 30px; font-weight: 900; letter-spacing: -1px; font-family:'Poppins',sans-serif; }
.balance-label { font-size: 11px; opacity: .5; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 4px; }

/* ─── Stat pills ────────────────────────────────────────────────────────── */
.cash-stat { display:flex; align-items:center; gap:10px; }
.cash-stat-icon {
    width:36px; height:36px; border-radius:10px; display:flex;
    align-items:center; justify-content:center; font-size:16px; flex-shrink:0;
}
.cash-stat-val  { font-size:15px; font-weight:800; }
.cash-stat-lbl  { font-size:10px; opacity:.45; }

/* ─── Period pills ──────────────────────────────────────────────────────── */
.period-tabs { display:flex; gap:6px; flex-wrap:wrap; }
.period-tab {
    padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600;
    border: 1.5px solid rgba(255,255,255,.12); cursor:pointer;
    transition: all .15s; text-decoration:none; color:rgba(255,255,255,.55);
    background:transparent;
}
.period-tab:hover  { border-color:rgba(255,255,255,.3); color:#fff; }
.period-tab.active { background:#667eea; border-color:#667eea; color:#fff; }

/* ─── Transaction rows ──────────────────────────────────────────────────── */
.tx-date-head {
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.5px; opacity:.35; padding:10px 0 6px;
}
.tx-row {
    display:flex; align-items:center; gap:12px;
    padding:11px 14px; border-radius:12px; transition:background .12s;
    position:relative;
}
.tx-row:hover { background:rgba(255,255,255,.04); }
.bk-theme-light .tx-row:hover { background:rgba(0,0,0,.03); }
.tx-icon {
    width:38px; height:38px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
    font-size:17px; flex-shrink:0;
}
.tx-meta { flex:1; min-width:0; }
.tx-title { font-size:13px; font-weight:600; }
.tx-sub   { font-size:11px; opacity:.4; margin-top:1px; }
.tx-amount { font-size:15px; font-weight:800; white-space:nowrap; }
.tx-del, .tx-edit {
    opacity:0; transition:opacity .15s;
    background:none; border:none; cursor:pointer;
    padding:4px 6px; border-radius:6px;
}
.tx-del { color:#ef4444; }
.tx-edit { color:#667eea; }
.tx-row:hover .tx-del, .tx-row:hover .tx-edit { opacity:.6; }
.tx-del:hover { opacity:1 !important; background:rgba(239,68,68,.1); }
.tx-edit:hover { opacity:1 !important; background:rgba(102,126,234,.1); }

/* ─── Category breakdown ───────────────────────────────────────────────── */
.income-section  { border-inline-start:3px solid #22c55e; padding-inline-start:10px; margin-bottom:12px; }
.expense-section { border-inline-start:3px solid #ef4444; padding-inline-start:10px; }

/* ─── Branch badge (global view) ───────────────────────────────────────── */
.tx-branch-badge {
    font-size:10px; font-weight:700; padding:2px 8px; border-radius:8px;
    background:rgba(102,126,234,.15); color:#a78bfa; white-space:nowrap;
    flex-shrink:0;
}

/* ─── Branch filter pills (global view) ────────────────────────────────── */
.branch-pill {
    padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600;
    border: 1.5px solid rgba(255,255,255,.15); cursor:pointer;
    transition: all .15s; text-decoration:none; color:rgba(255,255,255,.65);
    white-space: nowrap;
}
.branch-pill:hover  { border-color:rgba(255,255,255,.35); color:#fff; }
.branch-pill.active { background:rgba(102,126,234,.35); border-color:#667eea; color:#fff; }

/* ─── Modal shared ─────────────────────────────────────────────────────── */
.tx-modal .modal-content {
    border:none; border-radius:20px;
    background:var(--card-bg, #1a1f2e);
}
.bk-theme-light .tx-modal .modal-content { background:#fff; }
.cat-card {
    border:2px solid rgba(255,255,255,.08); border-radius:12px;
    padding:10px 8px; text-align:center; cursor:pointer;
    transition:all .15s; font-size:11px; font-weight:600;
    background:rgba(255,255,255,.03); user-select:none;
}
.bk-theme-light .cat-card { border-color:#e2e8f0; background:#f8fafc; }
.cat-card:hover { transform:translateY(-2px); }
.cat-card.active { border-color:var(--cat-color); background:rgba(var(--cat-rgb),.12); }
.cat-card .cat-emoji { font-size:22px; display:block; margin-bottom:4px; }
.pm-card {
    border:2px solid rgba(255,255,255,.08); border-radius:10px;
    padding:6px 14px; cursor:pointer; transition:all .15s;
    font-size:11px; font-weight:600; background:rgba(255,255,255,.03);
    user-select:none; display:flex; align-items:center; gap:5px; white-space:nowrap;
}
.bk-theme-light .pm-card { border-color:#e2e8f0; background:#f8fafc; }
.pm-card:hover { transform:translateY(-1px); }
.pm-card.active { border-color:var(--pm-color); background:color-mix(in srgb, var(--pm-color) 12%, transparent); color:var(--pm-color); }
.pm-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:1px 7px; border-radius:6px; font-size:9px; font-weight:700;
    white-space:nowrap;
}

/* ─── Drawer banner ────────────────────────────────────────────────────── */
.drawer-banner {
    padding:14px 20px; border-radius:12px; margin-bottom:12px;
    border:1px solid rgba(255,255,255,.06);
}
.drawer-banner.drawer-open {
    background:linear-gradient(135deg, rgba(34,197,94,.06), rgba(34,197,94,.02));
    border-color:rgba(34,197,94,.15);
}
.drawer-banner.drawer-closed {
    background:linear-gradient(135deg, rgba(255,255,255,.03), transparent);
}
.drawer-status-dot {
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
}
.drawer-status-dot.open {
    background:#22c55e; box-shadow:0 0 8px rgba(34,197,94,.5);
    animation: drawerPulse 2s ease infinite;
}
.drawer-status-dot.closed { background:#64748b; }
@keyframes drawerPulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }
.drawer-history-card {
    padding:10px 14px; border-radius:10px;
    background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06);
    min-width:130px; text-align:center;
}
.bk-theme-light .drawer-banner.drawer-open { background:rgba(34,197,94,.04); border-color:rgba(34,197,94,.15); }
.bk-theme-light .drawer-banner.drawer-closed { background:#f9fafb; border-color:#e5e7eb; }
.bk-theme-light .drawer-history-card { background:#f9fafb; border-color:#e5e7eb; }

/* ─── Chart wrapper ────────────────────────────────────────────────────── */
#cashChart { min-height:160px; }

/* ─── Hover links ──────────────────────────────────────────────────────── */
.tx-row:hover .tx-branch-link { opacity:.5 !important; }
.tx-branch-link:hover { opacity:1 !important; background:rgba(102,126,234,.1); }
</style>
