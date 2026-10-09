<style>
/* ══════════════════════════════════════════════════════════════════════
   OWNER SHELL — sidebar · header · phone tab bar
   One flat, grouped nav (no rail → panel hop), a quiet header that holds
   only what is used every day, and a phone layout built on a swipeable
   drawer + bottom tab bar. Token-driven: light / dark / RTL come for free.
══════════════════════════════════════════════════════════════════════ */
.main-wrapper { --bk-ease:cubic-bezier(.22,1,.36,1); }

/* Browser surfaces that belong to the palette */
.bk-theme-light ::selection, .bk-theme-dark ::selection {
    background:color-mix(in srgb, var(--bk-accent-fill) 38%, transparent);
}

/* Skip link */
.bk-skip {
    position:fixed; inset-block-start:8px; inset-inline-start:8px; z-index:2000;
    padding:10px 16px; border-radius:10px;
    background:var(--bk-accent-fill); color:var(--bk-accent-ink) !important;
    font-size:13px; font-weight:600; text-decoration:none;
    transform:translateY(-220%); transition:transform .2s var(--bk-ease);
}
.bk-skip:focus { transform:none; }

/* ════════════════════════ SIDEBAR ════════════════════════ */
.bk-sidebar-owner { display:flex; flex-direction:column; }
.bk-sidebar-owner .sidebar-header {
    position:relative; flex-shrink:0;
    width:100%; height:64px; padding:0 18px;
    display:flex; align-items:center; justify-content:flex-start;
}
.bk-sidebar-owner .sidebar-brand-logo { padding:0; }
.bk-sidebar-owner .sidebar-brand-logo .bk-sb-logo { height:34px; }
.bk-sidebar-owner .sidebar-body.bk-sbo {
    flex:1; min-height:0; height:auto; padding:0; overflow:hidden;
    display:flex; flex-direction:column;
}

/* compact mark (folded desktop only) */
.bk-sb-mark { display:none; align-items:center; justify-content:center; width:100%; }
.bk-sb-mark img { height:26px; width:auto; }
.bk-sb-mark--dark { display:none; }
.bk-theme-dark .bk-sb-mark--light { display:none; }
.bk-theme-dark .bk-sb-mark--dark  { display:block; }
.bk-sb-close { display:none; }

/* nav */
.bk-nav {
    flex:1; min-height:0; overflow-y:auto; overflow-x:hidden;
    padding:10px 12px 16px; overscroll-behavior:contain;
    scrollbar-width:thin; scrollbar-color:var(--bk-border-strong) transparent;
}
.bk-nav::-webkit-scrollbar { width:6px; }
.bk-nav::-webkit-scrollbar-thumb { background:var(--bk-border-strong); border-radius:6px; }
.bk-nav-group + .bk-nav-group { margin-top:4px; }

/* group header = the accordion trigger */
.bk-gh {
    display:flex; align-items:center; gap:8px; width:100%;
    min-height:36px; margin-top:8px; padding:6px 10px;
    -webkit-appearance:none; appearance:none;
    background:transparent !important; border:0; border-radius:9px;
    color:var(--bk-text-muted); text-align:start; cursor:pointer;
    font-size:11px; font-weight:700; letter-spacing:.9px; text-transform:uppercase;
    transition:color .15s, background .15s;
}
.bk-gh:hover { color:var(--bk-text); background:var(--bk-sidebar-hover) !important; }
.bk-gh-t { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-gh-dot {
    width:7px; height:7px; border-radius:50%; flex-shrink:0;
    background:var(--bk-gold); transition:opacity .2s;
}
.bk-nav-group.is-open .bk-gh-dot { opacity:0; }
.bk-gh-caret { width:14px; height:14px; flex-shrink:0; opacity:.6; }
.bk-nav-group.has-active .bk-gh { color:var(--bk-text-soft); }
[dir="rtl"] .bk-gh { letter-spacing:0; text-transform:none; font-size:12.5px; }

/* accordion body: grid rows 0fr → 1fr animates real height without measuring */
.bk-gb { display:grid; grid-template-rows:0fr; visibility:hidden; }
.bk-gb-in { min-height:0; overflow:hidden; }
.bk-nav-group.is-open .bk-gb,
.bk-nav-group:not(.is-collapsible) .bk-gb { grid-template-rows:1fr; visibility:visible; }
.bk-sidebar-owner.is-ready .bk-gb { transition:grid-template-rows .3s var(--bk-ease), visibility 0s linear .3s; }
.bk-sidebar-owner.is-ready .bk-nav-group.is-open .bk-gb { transition:grid-template-rows .3s var(--bk-ease), visibility 0s; }
.bk-sidebar-owner.is-ready .bk-gh-caret { transition:transform .3s var(--bk-ease); }
.bk-nav-group.is-collapsible.is-open .bk-gh-caret { transform:rotate(180deg); }

/* rows */
.bk-nl {
    position:relative; display:flex; align-items:center; gap:12px;
    min-height:40px; margin-bottom:2px; padding:8px 11px; border-radius:10px;
    color:var(--bk-text-soft); font-size:13.5px; font-weight:500; text-decoration:none;
    transition:background .14s, color .14s;
}
.bk-nl svg { width:17px; height:17px; flex-shrink:0; opacity:.8; transition:opacity .14s; }
.bk-nl > span:not(.bk-nl-badge) { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-nl:hover { background:var(--bk-sidebar-hover); color:var(--bk-text); }
.bk-nl:hover svg { opacity:1; }
.bk-nl.active { background:var(--bk-accent-wash); color:var(--bk-accent); font-weight:700; }
.bk-nl.active svg { opacity:1; }
.bk-nl:focus-visible, .bk-gh:focus-visible { outline:2px solid var(--bk-accent); outline-offset:-2px; }
.bk-nl-badge {
    flex:0 0 auto; min-width:22px; text-align:center;
    background:var(--bk-gold-soft); color:var(--bk-gold-strong);
    border-radius:999px; font-size:11px; font-weight:800; padding:2px 8px; line-height:1.4;
    font-variant-numeric:tabular-nums;
}
.bk-nl.active .bk-nl-badge { background:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.bk-nl-muted { opacity:.75; }
.bk-nl-muted:hover { opacity:1; }
.bk-sb-foot {
    flex-shrink:0; padding:8px 12px calc(10px + env(safe-area-inset-bottom, 0px));
    border-top:1px solid var(--bk-border);
}
.bk-sb-foot .bk-nl { margin-bottom:0; }

/* ── Folded desktop: icons only, hover to peek ── */
@media (min-width:992px) {
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .sidebar-header { padding:0; justify-content:center; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-sb-mark { display:flex; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-gh { display:none; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-gb { grid-template-rows:1fr !important; visibility:visible !important; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-nav { padding-inline:10px; scrollbar-width:none; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-nav::-webkit-scrollbar { display:none; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-nl { justify-content:center; padding-inline:0; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-nl > span:not(.bk-nl-badge) { display:none; }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-nl-badge {
        position:absolute; top:3px; inset-inline-end:3px; min-width:0; padding:1px 5px; font-size:9.5px;
    }
    .sidebar-folded:not(.open-sidebar-folded) .bk-sidebar-owner .bk-nav-group + .bk-nav-group {
        margin-top:8px; padding-top:8px; border-top:1px solid var(--bk-border);
    }
    /* peeking: core hides the brand while folded — bring the wordmark back */
    .sidebar-folded.open-sidebar-folded .bk-sidebar-owner .sidebar-header { width:100%; padding:0 18px; }
    .sidebar-folded.open-sidebar-folded .bk-sidebar-owner .sidebar-header .sidebar-brand { display:flex; }
    .sidebar-folded.open-sidebar-folded .bk-sidebar-owner { box-shadow:var(--bk-shadow-xl) !important; }
}

/* ════════════════════════ HEADER ════════════════════════ */
.navbar.bk-hd { height:64px; min-height:64px; padding:0 !important; }
.bk-hd-in { display:flex; align-items:center; gap:6px; width:100%; height:100%; padding:0 20px; }
.page-wrapper .navbar.bk-hd.is-scrolled { box-shadow:0 8px 24px -14px rgba(0,0,0,.35) !important; }
.navbar.bk-hd { transition:box-shadow .2s; }

.bk-hd-btn {
    position:relative; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    width:40px; height:40px; padding:0;
    -webkit-appearance:none; appearance:none;
    background:transparent; border:0; border-radius:12px;
    color:var(--bk-text-soft); text-decoration:none; cursor:pointer;
    transition:background .15s, color .15s, transform .12s;
}
.bk-hd-btn svg { width:19px; height:19px; }
/* The template styles .navbar .sidebar-toggler as a full-height tab with a divider — reset it to a normal icon button */
.navbar .bk-hd-btn.sidebar-toggler { height:40px; padding:0; border:0; display:inline-flex; }
.navbar .bk-hd-btn.sidebar-toggler svg { width:19px; height:19px; color:inherit; }
.bk-hd-btn:hover { background:var(--bk-surface-2); color:var(--bk-text); }
.bk-hd-btn:active { transform:scale(.95); }
.bk-hd-btn:focus-visible, .bk-hd-user:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }
.bk-hd-btn.show { background:var(--bk-surface-2); color:var(--bk-text); }
.bk-hd-badge {
    position:absolute; top:4px; inset-inline-end:3px;
    min-width:17px; height:17px; padding:0 4px; border-radius:9px;
    background:var(--bk-danger); color:var(--bk-hd-badge-ink, #fff);
    font-size:10.5px; font-weight:800; line-height:17px; text-align:center;
    font-variant-numeric:tabular-nums;
}
.bk-theme-dark { --bk-hd-badge-ink:#2a1214; }

.bk-hd-brand { display:none; align-items:center; margin-inline:4px; }
.bk-hd-brand .bk-sb-logo { height:28px; width:auto; display:block; }
.bk-hd-brand .bk-sb-logo--dark { display:none; }
.bk-theme-dark .bk-hd-brand .bk-sb-logo--light { display:none; }
.bk-theme-dark .bk-hd-brand .bk-sb-logo--dark  { display:block; }

/* search */
.bk-hd-search {
    --ic-x:13px;
    position:relative; flex:1 1 auto; max-width:440px; margin-inline-start:6px;
    display:flex; align-items:center; gap:8px;
}
.bk-hd-search input {
    flex:1; width:100%; min-width:0; height:40px;
    padding-block:0; padding-inline:calc(var(--ic-x) + 27px) 42px;
    -webkit-appearance:none; appearance:none;
    background:var(--bk-surface-2); color:var(--bk-text);
    border:1px solid var(--bk-border); border-radius:12px; outline:0;
    font-size:13.5px; caret-color:var(--bk-accent);
    transition:border-color .15s, box-shadow .15s, background .15s;
}
.bk-hd-search input::placeholder { color:var(--bk-text-muted); opacity:1; }
.bk-theme-light .bk-hd-search input::placeholder { color:#62634F; }
.bk-hd-search input::-webkit-search-cancel-button { -webkit-appearance:none; display:none; }
.bk-hd-search input:hover { border-color:var(--bk-border-strong); }
.bk-hd-search input:focus {
    border-color:var(--bk-accent); background:var(--bk-surface);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--bk-accent) 18%, transparent);
}
.bk-hd-search-ic {
    position:absolute; inset-inline-start:var(--ic-x); top:50%; margin-top:-8px;
    width:16px; height:16px; color:var(--bk-text-muted); pointer-events:none;
}
.bk-hd-kbd {
    position:absolute; inset-inline-end:10px; top:50%; transform:translateY(-50%);
    min-width:22px; padding:1px 6px; text-align:center;
    border:1px solid var(--bk-border-strong); border-radius:6px;
    background:var(--bk-surface); color:var(--bk-text-muted);
    font-family:inherit; font-size:11px; font-weight:600; line-height:18px;
    pointer-events:none; transition:opacity .15s;
}
.bk-hd-search:focus-within .bk-hd-kbd { opacity:0; }
@media (hover:none) { .bk-hd-kbd { display:none; } }
.bk-hd-search-x, .bk-hd-search-open { display:none; }

.bk-hd-end { margin-inline-start:auto; display:flex; align-items:center; gap:4px; }

/* account button */
.bk-hd-user {
    display:flex; align-items:center; gap:10px;
    height:48px; padding:4px 8px 4px 4px; margin-inline-start:4px;
    -webkit-appearance:none; appearance:none;
    background:transparent; border:0; border-radius:14px; text-align:start; cursor:pointer;
    color:var(--bk-text); transition:background .15s;
}
[dir="rtl"] .bk-hd-user { padding:4px 4px 4px 8px; }
.bk-hd-user:hover, .bk-hd-user.show { background:var(--bk-surface-2); }
.bk-hd-user-tx { display:flex; flex-direction:column; min-width:0; line-height:1.25; }
.bk-hd-user-name { max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:13px; font-weight:700; }
.bk-hd-user-role { max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:11.5px; color:var(--bk-text-muted); }
.bk-hd-user-caret { width:15px; height:15px; color:var(--bk-text-muted); flex-shrink:0; transition:transform .25s var(--bk-ease); }
.bk-hd-user.show .bk-hd-user-caret { transform:rotate(180deg); }

.bk-av {
    flex-shrink:0; width:36px; height:36px; border-radius:50%;
    display:grid; place-items:center;
    background:var(--bk-accent-fill); color:var(--bk-accent-ink);
    font-size:13px; font-weight:700; line-height:1;
    box-shadow:0 0 0 2px var(--bk-bg), 0 0 0 3.5px color-mix(in srgb, var(--bk-gold) 60%, transparent);
}
.bk-av-lg { width:44px; height:44px; font-size:15px; }

/* dropdown menus */
.dropdown-menu.bk-menu {
    padding:0; border-radius:16px; overflow:hidden; margin-top:8px !important;
    min-width:288px;
}
.dropdown-menu.bk-menu.show { animation:bk-menu-in .2s var(--bk-ease); }
@keyframes bk-menu-in { from { opacity:0; translate:0 -6px; } to { opacity:1; translate:0 0; } }
.bk-menu-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; border-bottom:1px solid var(--bk-border); }
.bk-menu-title { font-size:14px; font-weight:700; color:var(--bk-text); }
.bk-menu-link {
    -webkit-appearance:none; appearance:none; background:none; border:0;
    min-height:32px; padding:4px 6px; border-radius:8px;
    color:var(--bk-accent); font-size:12.5px; font-weight:600; cursor:pointer;
}
.bk-menu-link:hover { background:var(--bk-accent-wash); }
.bk-menu-foot {
    display:block; padding:13px; text-align:center; text-decoration:none;
    border-top:1px solid var(--bk-border); color:var(--bk-accent) !important;
    font-size:13px; font-weight:600;
}
.bk-menu-foot:hover { background:var(--bk-sidebar-hover); }

.bk-menu-notif { width:372px; max-width:calc(100vw - 20px); }
.bk-notif-list { max-height:min(62vh, 400px); overflow-y:auto; overscroll-behavior:contain; }
.bk-notif {
    position:relative; display:flex; gap:12px; align-items:flex-start;
    padding:12px 16px; border-bottom:1px solid var(--bk-border);
    color:var(--bk-text); text-decoration:none; transition:background .13s;
}
.bk-notif:last-child { border-bottom:0; }
.bk-notif:hover { background:var(--bk-sidebar-hover); color:var(--bk-text); }
.bk-notif.is-unread { background:color-mix(in srgb, var(--bk-accent) 7%, transparent); }
.bk-notif-ic {
    flex-shrink:0; width:36px; height:36px; border-radius:50%;
    display:grid; place-items:center; background:var(--bk-surface-2); font-size:16px; line-height:1;
}
.bk-notif-tx { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
.bk-notif-t { font-size:13px; font-weight:500; line-height:1.4; }
.bk-notif.is-unread .bk-notif-t { font-weight:700; }
.bk-notif-b {
    font-size:12.5px; color:var(--bk-text-soft); line-height:1.45;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.bk-notif-d { font-size:11.5px; color:var(--bk-text-muted); }
.bk-notif-dot { flex-shrink:0; width:8px; height:8px; margin-top:6px; border-radius:50%; background:var(--bk-accent); }
.bk-notif-empty { display:flex; flex-direction:column; align-items:center; gap:10px; padding:32px 16px; color:var(--bk-text-muted); font-size:13px; }
.bk-notif-empty svg { width:26px; height:26px; opacity:.7; }

.bk-menu-user { width:304px; max-width:calc(100vw - 20px); }
.bk-menu-id { display:flex; align-items:center; gap:12px; padding:16px; background:var(--bk-surface-2); }
.bk-menu-id-tx { display:flex; flex-direction:column; min-width:0; line-height:1.3; }
.bk-menu-id-name { font-size:14px; font-weight:700; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bk-menu-id-role { font-size:12px; color:var(--bk-text-muted); }
.bk-menu-sec { padding:6px; border-top:1px solid var(--bk-border); }
.bk-mi {
    display:flex; align-items:center; gap:12px; width:100%; min-height:42px; padding:8px 12px;
    -webkit-appearance:none; appearance:none; background:transparent; border:0; border-radius:10px;
    color:var(--bk-text-soft); font-size:13.5px; font-weight:500; text-align:start; text-decoration:none; cursor:pointer;
    transition:background .13s, color .13s;
}
.bk-mi svg { width:16px; height:16px; flex-shrink:0; opacity:.8; }
.bk-mi:hover { background:var(--bk-sidebar-hover); color:var(--bk-text); }
.bk-mi-danger { color:var(--bk-danger); }
.bk-mi-danger:hover { background:var(--bk-danger-bg); color:var(--bk-danger); }
.bk-mi:focus-visible, .bk-seg a:focus-visible, .bk-menu-link:focus-visible, .bk-menu-foot:focus-visible, .bk-notif:focus-visible {
    outline:2px solid var(--bk-accent); outline-offset:-2px;
}
.bk-seg-row { display:flex; align-items:center; justify-content:space-between; gap:12px; min-height:46px; padding:4px 6px 4px 12px; }
[dir="rtl"] .bk-seg-row { padding:4px 12px 4px 6px; }
.bk-seg-lbl { display:flex; align-items:center; gap:12px; font-size:13.5px; font-weight:500; color:var(--bk-text-soft); }
.bk-seg-lbl svg { width:16px; height:16px; opacity:.8; }
.bk-seg { display:inline-flex; padding:3px; gap:2px; background:var(--bk-surface-2); border:1px solid var(--bk-border); border-radius:11px; }
.bk-seg a {
    display:inline-flex; align-items:center; justify-content:center; min-height:30px; padding:4px 12px;
    border-radius:8px; color:var(--bk-text-soft); font-size:12.5px; font-weight:600; text-decoration:none;
    transition:background .15s, color .15s;
}
.bk-seg a:hover { color:var(--bk-text); }
.bk-seg a.is-on { background:var(--bk-accent-fill); color:var(--bk-accent-ink); }

/* ════════════════════════ PHONE TAB BAR ════════════════════════ */
.bk-bottom-nav--owner { z-index:970; }
.bk-bottom-nav--owner .bk-bn-item {
    position:relative; -webkit-appearance:none; appearance:none;
    background:transparent; border:0; padding:0; font-family:inherit;
    -webkit-tap-highlight-color:transparent; transition:color .15s, background .15s;
}
.bk-bottom-nav--owner .bk-bn-item:active { background:var(--bk-sidebar-hover); }
.bk-bottom-nav--owner .bk-bn-item.active { color:var(--bk-accent); font-weight:700; }
.bk-bottom-nav--owner .bk-bn-item.active::before {
    content:''; position:absolute; top:0; inset-inline:calc(50% - 15px);
    height:3px; border-radius:0 0 4px 4px; background:var(--bk-accent-fill);
}
.bk-bottom-nav--owner .bk-bn-item:focus-visible { outline:2px solid var(--bk-accent); outline-offset:-3px; }
.bk-bottom-nav--owner .bk-bn-badge { background:var(--bk-gold); color:var(--bk-gold-ink); }

/* ════════════════════════ TABLET + PHONE ════════════════════════ */
.bk-scrim {
    display:none; position:fixed; inset:0; z-index:990;
    background:var(--bk-scrim); opacity:0; pointer-events:none;
    transition:opacity .3s var(--bk-ease);
}

@media (max-width:991.98px) {
    /* the sidebar becomes a swipeable drawer */
    .bk-sidebar-owner {
        width:min(86vw, 320px) !important; margin-left:0 !important; margin-right:0 !important;
        visibility:hidden; transform:translateX(-100%); will-change:transform;
        transition:transform .34s var(--bk-ease), visibility 0s linear .34s;
        padding-bottom:env(safe-area-inset-bottom, 0px);
    }
    [dir="rtl"] .bk-sidebar-owner { transform:translateX(100%); }
    body.sidebar-open .bk-sidebar-owner {
        visibility:visible; transform:none;
        transition:transform .34s var(--bk-ease), visibility 0s;
        box-shadow:var(--bk-shadow-xl) !important;
    }
    .bk-sidebar-owner .sidebar-header { width:100% !important; padding:0 12px 0 18px; }
    [dir="rtl"] .bk-sidebar-owner .sidebar-header { padding:0 18px 0 12px; }
    .bk-sidebar-owner .sidebar-header .sidebar-brand {
        display:flex !important; opacity:1 !important; visibility:visible !important; width:auto !important;
    }
    .bk-sb-mark { display:none !important; }
    .bk-sb-close {
        display:inline-flex; align-items:center; justify-content:center;
        width:44px; height:44px; margin-inline-start:auto; padding:0;
        -webkit-appearance:none; appearance:none; background:transparent; border:0; border-radius:12px;
        color:var(--bk-text-soft); cursor:pointer;
    }
    .bk-sb-close:hover { background:var(--bk-sidebar-hover); color:var(--bk-text); }
    .bk-sb-close:focus-visible { outline:2px solid var(--bk-accent); outline-offset:-2px; }
    .bk-sb-close svg { width:20px; height:20px; }
    .bk-nl { min-height:46px; font-size:14px; }
    .bk-gh { min-height:42px; }
    .bk-sb-foot { padding-bottom:10px; }

    .bk-scrim { display:block; }
    body.sidebar-open .bk-scrim { opacity:1; pointer-events:auto; }
    body.sidebar-open { overflow:hidden; }
    .sidebar-open .main-wrapper::before, .settings-open .main-wrapper::before { display:none !important; }

    /* a folded-desktop preference must never shrink the mobile layout */
    body.sidebar-folded .page-wrapper { width:100% !important; margin-left:0 !important; margin-right:0 !important; }
    body.sidebar-folded .page-wrapper .navbar { width:100% !important; left:0 !important; right:0 !important; }

    .bk-hd-brand { display:flex; }
    .bk-hd-in { padding:0 10px; }
}

@media (min-width:992px) {
    .bk-hd-in { padding-inline:24px 20px; }
}
@media (max-width:991.98px) {
    .bk-hd-user-tx, .bk-hd-user-caret { display:none; }
    .bk-hd-user { padding:4px; height:44px; width:44px; justify-content:center; margin-inline-start:2px; }
}

/* phones */
@media (max-width:767.98px) {
    .bk-hd-search-open { display:inline-flex; }
    .bk-hd-search {
        --ic-x:25px;
        display:none; position:absolute; inset:0; z-index:3; max-width:none; margin:0; padding:0 10px 0 12px;
    }
    .bk-theme-light .bk-hd-search { background:var(--bk-surface); }
    .bk-theme-dark  .bk-hd-search { background:var(--bk-bg); }
    [dir="rtl"] .bk-hd-search { padding:0 12px 0 10px; }
    .bk-hd.is-searching .bk-hd-search { display:flex; animation:bk-search-in .22s var(--bk-ease); }
    @keyframes bk-search-in { from { opacity:0; translate:0 -4px; } to { opacity:1; translate:0 0; } }
    .bk-hd-search input { height:44px; font-size:16px; padding-inline-end:14px; }
    .bk-hd-kbd { display:none; }
    .bk-hd-search-x { display:inline-flex; width:44px; height:44px; }

    /* menus become full-width sheets under the bar */
    .bk-hd .dropdown-menu.bk-menu {
        position:fixed !important; inset-block-start:70px !important; inset-inline:10px !important;
        width:auto !important; min-width:0 !important; max-width:none !important;
        transform:none !important; margin:0 !important;
    }
    .bk-notif-list { max-height:calc(100dvh - 230px); }
}

@media (pointer:coarse) {
    .bk-hd-btn, .navbar .bk-hd-btn.sidebar-toggler { width:44px; height:44px; }
    .bk-mi { min-height:46px; }
}

@media (prefers-reduced-motion:reduce) {
    .bk-sidebar-owner, .bk-sidebar-owner * , .bk-scrim, .bk-hd, .bk-hd *, .bk-menu, .bk-skip {
        transition-duration:.01ms !important; animation-duration:.01ms !important;
    }
}
</style>
