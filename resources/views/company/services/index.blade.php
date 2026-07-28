@extends('company.dashboard')

@push('company-styles')
<style>
/* ═══════════════════════════════════════════════════════════════════════
   SERVICES WORKBENCH
   ═══════════════════════════════════════════════════════════════════════ */
.wb { --wb-line: rgba(255,255,255,.08); --wb-soft: rgba(255,255,255,.04);
      --wb-hover: rgba(255,255,255,.05); --wb-muted: rgba(255,255,255,.55); }
.bk-theme-light .wb { --wb-line: rgba(0,0,0,.09); --wb-soft: rgba(0,0,0,.02);
      --wb-hover: rgba(0,0,0,.03); --wb-muted: rgba(0,0,0,.5); }

/* ── Toolbar ─────────────────────────────────────────────────────────── */
.wb-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:18px; }
.wb-search { position:relative; flex:1 1 220px; max-width:340px; min-width:160px; }
.wb-search input { padding-inline-start:36px; }
.wb-search svg { position:absolute; inset-inline-start:12px; top:50%; transform:translateY(-50%);
      width:15px; height:15px; opacity:.5; pointer-events:none; }
.wb-viewtoggle { display:inline-flex; border:1px solid var(--wb-line); border-radius:10px; overflow:hidden; }
.wb-viewtoggle button { border:0; background:transparent; padding:7px 11px; color:var(--wb-muted);
      display:flex; align-items:center; cursor:pointer; transition:.15s; }
.wb-viewtoggle button.active { background:var(--bk-accent); color:#fff; }

/* ── Layout: rail + main ─────────────────────────────────────────────── */
.wb-grid { display:grid; grid-template-columns:238px 1fr; gap:20px; align-items:start; }
@media (max-width:900px){ .wb-grid { grid-template-columns:1fr; } .wb-rail { display:none; } }

.wb-rail { position:sticky; top:12px; border:1px solid var(--wb-line); border-radius:16px;
      padding:10px; background:var(--wb-soft); }
.wb-rail-head { display:flex; align-items:center; justify-content:space-between; padding:4px 6px 8px;
      font-size:11px; font-weight:800; letter-spacing:.5px; text-transform:uppercase; color:var(--wb-muted); }
.wb-cat { display:flex; align-items:center; gap:9px; padding:8px 10px; border-radius:10px; cursor:pointer;
      font-size:13px; transition:background .14s; user-select:none; }
.wb-cat:hover { background:var(--wb-hover); }
.wb-cat.active { background:rgba(75,93,52,.14); color:var(--bk-accent); font-weight:700; }
.wb-cat .dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
.wb-cat .nm { flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wb-cat .ct { font-size:11px; font-weight:700; padding:1px 8px; border-radius:20px;
      background:var(--wb-hover); color:var(--wb-muted); }
.wb-cat .grip { opacity:0; cursor:grab; width:13px; height:13px; }
.wb-cat:hover .grip { opacity:.4; }
.wb-cat-add { display:flex; align-items:center; gap:7px; padding:8px 10px; margin-top:4px; font-size:12.5px;
      color:var(--bk-accent); cursor:pointer; border-radius:10px; font-weight:600; }
.wb-cat-add:hover { background:var(--wb-hover); }

/* ── List (dense) ────────────────────────────────────────────────────── */
.wb-catblock { margin-bottom:18px; }
.wb-cathead { display:flex; align-items:center; gap:10px; padding:6px 4px 10px; }
.wb-cathead .caret { cursor:pointer; display:flex; transition:transform .18s; opacity:.6; }
.wb-cathead.collapsed .caret { transform:rotate(-90deg); }
.wb-cathead .dot { width:10px; height:10px; border-radius:50%; }
.wb-cathead .nm { font-weight:800; font-size:14px; }
.wb-cathead .ct { font-size:11px; font-weight:700; padding:1px 8px; border-radius:20px;
      background:var(--wb-hover); color:var(--wb-muted); }

.wb-rows { display:flex; flex-direction:column; gap:6px; }
.wb-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap;
      padding:10px 12px; border:1px solid var(--wb-line); border-radius:12px;
      background:var(--wb-soft); transition:background .14s, box-shadow .14s, opacity .2s, transform .2s; }
.wb-row .c-check, .wb-row .c-grip, .wb-row .c-active, .wb-row .c-actions { flex:0 0 auto; }
.wb-row .c-name { flex:1 1 190px; min-width:0; }
.wb-row .c-cat  { flex:0 0 122px; }
.wb-row .c-price{ flex:0 0 116px; text-align:end; }
.wb-row .c-dur  { flex:0 0 82px; }
.wb-row .c-actions { margin-inline-start:auto; }
@media (max-width:640px){
    .wb-row { gap:6px 10px; }
    .wb-row .c-grip { display:none; }
    .wb-row .c-name { flex:1 1 100%; order:1; }
    .wb-row .c-check { order:0; }
    .wb-row .c-active { order:0; margin-inline-start:auto; }
    .wb-row .c-actions { order:0; margin-inline-start:4px; }
    .wb-row .c-cat  { flex:0 0 auto; order:2; }
    .wb-row .c-price{ flex:0 0 auto; order:2; text-align:start; }
    .wb-row .c-dur  { flex:0 0 auto; order:2; margin-inline-start:auto; }
}
.wb-row:hover { background:var(--wb-hover); }
.wb-row.sel { border-color:var(--bk-accent); background:rgba(75,93,52,.09); }
.wb-row.flash { animation:wbflash .9s ease; }
@keyframes wbflash { 0%{ background:rgba(43,207,126,.28);} 100%{ background:var(--wb-soft);} }
.wb-row .grip { cursor:grab; opacity:.35; display:flex; justify-content:center; }
.wb-row .grip:active { cursor:grabbing; }

.wb-name { min-width:0; }
.wb-name .t1 { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.wb-name .nm { font-weight:700; font-size:13.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
.wb-name .ar { font-size:11.5px; color:var(--wb-muted); direction:rtl; }
.wb-badges { display:inline-flex; gap:5px; flex-wrap:wrap; }
.wb-tag { font-size:9.5px; font-weight:800; letter-spacing:.3px; text-transform:uppercase;
      padding:2px 7px; border-radius:20px; display:inline-flex; align-items:center; gap:3px; }
.tag-type { background:var(--wb-hover); color:var(--wb-muted); }
.tag-pkg  { background:rgba(139,92,246,.16); color:#a78bfa; }
.tag-mem  { background:rgba(59,187,212,.16); color:#3dbbd4; }
.tag-add  { background:rgba(244,166,66,.16); color:#f4a642; }
.tag-con  { background:rgba(43,207,126,.16); color:#2bcf7e; }
.tag-pop  { background:rgba(236,72,153,.15); color:#ec4899; }
.tag-rec  { background:rgba(75,93,52,.16); color:var(--bk-accent); }
.tag-off  { background:rgba(255,255,255,.06); color:var(--wb-muted); }
.bk-theme-light .tag-off { background:rgba(0,0,0,.05); }
.tag-sale { background:rgba(229,57,53,.15); color:#e53935; }

.wb-cat-cell { font-size:12px; color:var(--wb-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wb-price { font-weight:800; font-size:13.5px; white-space:nowrap; }
.wb-price .cur { font-size:10px; font-weight:600; opacity:.7; }
.wb-price .orig { display:block; font-size:10.5px; text-decoration:line-through; opacity:.45; font-weight:600; }
.wb-price.sale { color:#e53935; }
.wb-dur { font-size:12px; color:var(--wb-muted); white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
.wb-cell-edit { cursor:text; border-radius:6px; padding:2px 5px; margin:-2px -5px; transition:background .12s; }
.wb-cell-edit:hover { background:var(--wb-hover); box-shadow:inset 0 0 0 1px var(--wb-line); }
.wb-inline-input { width:100%; border:1px solid var(--bk-accent); border-radius:6px; padding:3px 6px;
      font-size:13px; background:var(--bs-body-bg,#1e2130); color:inherit; }

/* action menu */
.wb-actions { position:relative; display:flex; justify-content:flex-end; }
.wb-iconbtn { border:0; background:transparent; color:var(--wb-muted); cursor:pointer; padding:5px;
      border-radius:8px; display:flex; }
.wb-iconbtn:hover { background:var(--wb-hover); color:inherit; }

/* checkbox + toggle */
.wb-check { width:17px; height:17px; cursor:pointer; accent-color:var(--bk-accent); }
.wb-row .form-switch { margin:0; min-height:auto; padding-inline-start:2.4em; }

/* ── Card view ───────────────────────────────────────────────────────── */
.wb-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:14px; }
.wb-card { border:1px solid var(--wb-line); border-radius:16px; overflow:hidden; background:var(--wb-soft);
      display:flex; flex-direction:column; transition:transform .18s, box-shadow .18s; }
.wb-card:hover { transform:translateY(-3px); box-shadow:0 12px 30px rgba(0,0,0,.22); }
.wb-card.sel { border-color:var(--bk-accent); }
.wb-card-icon { height:104px; position:relative; display:flex; align-items:center; justify-content:center; }
.wb-card-body { padding:13px 14px; display:flex; flex-direction:column; gap:9px; flex:1; }
.wb-card-body .nm { font-weight:700; font-size:14px; }
/* Type-icon tile that replaces per-service images in the list */
.wb-typeicon { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px;
      border-radius:9px; flex-shrink:0; }
.wb-row .c-name .t1 { display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
.wb-row .c-name .ar { padding-inline-start:39px; }

/* ── Bulk bar ────────────────────────────────────────────────────────── */
.wb-bulkbar { position:fixed; inset-inline:0; bottom:0; z-index:1045; display:flex; justify-content:center;
      padding:0 14px 18px; pointer-events:none; transform:translateY(140%); transition:transform .28s cubic-bezier(.22,1,.36,1); }
.wb-bulkbar.show { transform:translateY(0); }
.wb-bulkbar-inner { pointer-events:auto; display:flex; align-items:center; gap:6px; flex-wrap:wrap; justify-content:center;
      background:var(--bs-body-bg,#20232f); border:1px solid var(--wb-line); box-shadow:0 18px 50px rgba(0,0,0,.4);
      border-radius:16px; padding:10px 12px; max-width:calc(100vw - 24px); }
.bk-theme-light .wb-bulkbar-inner { background:#fff; }
.wb-bulkbar .cnt { font-weight:800; font-size:13px; padding:5px 12px; border-radius:20px;
      background:rgba(75,93,52,.15); color:var(--bk-accent); white-space:nowrap; }
.wb-bulkbar .btn { font-size:12.5px; }

/* ── Drawer ──────────────────────────────────────────────────────────── */
/* The app's compiled Bootstrap build omits the .offcanvas component styles,
   so we ship a self-contained, RTL-aware offcanvas here. Without this the
   drawer renders inline in the page flow (visible on load, under the sidebar). */
#wb-drawer {
    position: fixed; top: 0; bottom: 0; inset-inline-end: 0;
    width: 560px; max-width: 100vw;
    display: flex; flex-direction: column;
    background: var(--bs-body-bg, #1e2130); color: var(--bs-body-color, #e0e0e0);
    box-shadow: 0 0 60px rgba(0,0,0,.5);
    z-index: 1090; visibility: hidden;
    transform: translateX(100%);
    /* visibility flips to hidden only AFTER the slide-out finishes */
    transition: transform .32s cubic-bezier(.22,1,.36,1), visibility 0s .32s;
    outline: 0;
}
[dir="rtl"] #wb-drawer { transform: translateX(-100%); }        /* inline-end = left in RTL */
/* Driven by our own .wb-open class (not Bootstrap's offcanvas JS, which the app build lacks).
   RTL-scoped + !important so it decisively beats [dir="rtl"] #wb-drawer and any app CSS. */
#wb-drawer.wb-open,
[dir="rtl"] #wb-drawer.wb-open {
    visibility: visible !important; transform: none !important;
    transition: transform .32s cubic-bezier(.22,1,.36,1), visibility 0s 0s;
}
.bk-theme-light #wb-drawer { background:#fff; color:#212529; }

#wb-drawer .wb-drawer-header { display:flex; align-items:center; justify-content:space-between;
    gap:12px; padding:16px 20px; flex:0 0 auto; }
#wb-drawer .wb-drawer-title { margin:0; font-size:1.05rem; line-height:1.4; }
#wb-drawer .wb-drawer-body { flex:1 1 auto; min-height:0; display:flex; padding:0; overflow:hidden; }
#wb-form { display:flex; flex-direction:column; flex:1 1 auto; min-height:0; width:100%; }

/* Own backdrop (the app's Bootstrap build ships no offcanvas backdrop styles) */
#wb-drawer-backdrop { position:fixed; inset:0; z-index:1085; background:#000; opacity:0;
    pointer-events:none; transition:opacity .32s ease; }
#wb-drawer-backdrop.wb-open { opacity:.5; pointer-events:auto; }

.wb-drawer-scroll { flex:1 1 auto; min-height:0; overflow-y:auto; padding:20px 22px 24px; }
.wb-typechooser { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
@media(max-width:520px){ .wb-typechooser{ grid-template-columns:repeat(2,1fr);} }
.wb-typeopt { border:1.5px solid var(--wb-line); border-radius:12px; padding:11px 8px; text-align:center;
      cursor:pointer; transition:.15s; display:flex; flex-direction:column; align-items:center; gap:5px; }
.wb-typeopt:hover { border-color:var(--bk-accent); }
.wb-typeopt.sel { border-color:var(--bk-accent); background:rgba(75,93,52,.1); }
.wb-typeopt .ic { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center;
      background:var(--wb-hover); }
.wb-typeopt .lb { font-size:11.5px; font-weight:700; }
.wb-seg { display:inline-flex; border:1px solid var(--wb-line); border-radius:10px; overflow:hidden; }
.wb-seg button { border:0; background:transparent; padding:8px 14px; font-size:12.5px; color:var(--wb-muted);
      cursor:pointer; font-weight:600; }
.wb-seg button.active { background:var(--bk-accent); color:#fff; }
.wb-drawer-foot { flex:0 0 auto; display:flex; gap:10px; padding:14px 22px;
      border-top:1px solid var(--wb-line); background:var(--bs-body-bg,#1e2130); }
.bk-theme-light .wb-drawer-foot { background:#fff; }
.wb-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 11px; border-radius:20px; cursor:pointer;
      border:1.5px solid var(--wb-line); font-size:12.5px; transition:.14s; user-select:none; }
.wb-chip.sel { border-color:var(--bk-accent); background:rgba(75,93,52,.1); color:var(--bk-accent); }
.wb-chips { display:flex; flex-wrap:wrap; gap:7px; }
.wb-label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--wb-muted);
      margin-bottom:8px; display:block; }
.wb-sec { padding:16px 0; border-top:1px solid var(--wb-line); }
.wb-sec:first-of-type { border-top:0; padding-top:4px; }

/* package builder */
.wb-pkgitem { display:flex; align-items:center; gap:9px; padding:8px 10px; border:1px solid var(--wb-line);
      border-radius:10px; background:var(--wb-soft); }
.wb-pkgitem .nm { flex:1; font-size:12.5px; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wb-pkgsum { display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:var(--wb-muted);
      background:var(--wb-hover); border-radius:10px; padding:9px 12px; margin-top:10px; }
.wb-pkgsum b { color:inherit; }

/* misc */
.wb-empty { text-align:center; padding:56px 20px; color:var(--wb-muted); }
.wb-empty svg { width:30px; height:30px; margin-bottom:12px; opacity:.5; }
.wb-hide { display:none !important; }
.wb-sortghost { opacity:.4; }
.wb-drop { padding:8px 10px; }
[dir="rtl"] .wb-cathead .caret { transform:scaleX(-1); }
[dir="rtl"] .wb-cathead.collapsed .caret { transform:scaleX(-1) rotate(-90deg); }
</style>
@endpush

@section('content')
<div class="page-content wb">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 grid-margin">
        <div>
            <h4 class="mb-1">{{ __('Services') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-light rounded-pill btn-icon-text dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="btn-icon-prepend" data-feather="upload"></i>{{ __('Import / Export') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#" id="wb-import-open"><i data-feather="upload" style="width:14px;height:14px;" class="me-2"></i>{{ __('Import (CSV / Excel)') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('company.branches.services.export', $branch) }}"><i data-feather="download" style="width:14px;height:14px;" class="me-2"></i>{{ __('Export (CSV / Excel)') }}</a></li>
                </ul>
            </div>
            <div class="dropdown">
                <button class="btn btn-primary rounded-pill btn-icon-text dropdown-toggle" id="wb-add-btn" data-bs-toggle="dropdown">
                    <i class="btn-icon-prepend" data-feather="plus"></i>{{ __('Add service') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" id="wb-add-menu">
                    <li><a class="dropdown-item wb-add-type" href="#" data-type="standard"><i data-feather="scissors" style="width:14px;height:14px;" class="me-2"></i>{{ __('Standard service') }}</a></li>
                    <li><a class="dropdown-item wb-add-type" href="#" data-type="package"><i data-feather="gift" style="width:14px;height:14px;" class="me-2"></i>{{ __('Package / Bundle') }}</a></li>
                    <li><a class="dropdown-item wb-add-type" href="#" data-type="membership"><i data-feather="award" style="width:14px;height:14px;" class="me-2"></i>{{ __('Membership') }}</a></li>
                    <li><a class="dropdown-item wb-add-type" href="#" data-type="addon"><i data-feather="plus-circle" style="width:14px;height:14px;" class="me-2"></i>{{ __('Add-on / Extra') }}</a></li>
                    <li><a class="dropdown-item wb-add-type" href="#" data-type="consultation"><i data-feather="message-circle" style="width:14px;height:14px;" class="me-2"></i>{{ __('Consultation') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- Stat cards --}}
    <div class="row g-3 grid-margin">
        <div class="col-6 col-xl-3">
            <div class="bk-stat" data-accent="gold">
                <div class="bk-stat-left"><div class="bk-stat-icon bk-icon-gold"><i data-feather="scissors" style="width:22px;height:22px;"></i></div>
                    <div class="bk-stat-info"><div class="bk-stat-label">{{ __('Total services') }}</div></div></div>
                <div class="bk-stat-num" id="wb-stat-total">0</div>
                <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:100%"></div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="bk-stat" data-accent="green">
                <div class="bk-stat-left"><div class="bk-stat-icon bk-icon-green"><i data-feather="check-circle" style="width:22px;height:22px;"></i></div>
                    <div class="bk-stat-info"><div class="bk-stat-label">{{ __('Active') }}</div></div></div>
                <div class="bk-stat-num" id="wb-stat-active">0</div>
                <div class="bk-stat-bar"><div class="bk-stat-bar-fill" id="wb-stat-active-bar" style="width:0%"></div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="bk-stat" data-accent="blue">
                <div class="bk-stat-left"><div class="bk-stat-icon bk-icon-blue"><i data-feather="globe" style="width:22px;height:22px;"></i></div>
                    <div class="bk-stat-info"><div class="bk-stat-label">{{ __('Online') }}</div></div></div>
                <div class="bk-stat-num" id="wb-stat-online">0</div>
                <div class="bk-stat-bar"><div class="bk-stat-bar-fill" id="wb-stat-online-bar" style="width:0%"></div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="bk-stat" data-accent="red">
                <div class="bk-stat-left"><div class="bk-stat-icon bk-icon-red"><i data-feather="tag" style="width:22px;height:22px;"></i></div>
                    <div class="bk-stat-info"><div class="bk-stat-label">{{ __('On sale') }}</div></div></div>
                <div class="bk-stat-num" id="wb-stat-sale">0</div>
                <div class="bk-stat-bar"><div class="bk-stat-bar-fill" id="wb-stat-sale-bar" style="width:0%"></div></div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="wb-toolbar">
        <div class="wb-search">
            <i data-feather="search"></i>
            <input type="search" id="wb-search" class="form-control rounded-pill" placeholder="{{ __('Search name, price, duration…') }}" autocomplete="off">
        </div>
        <select id="wb-type-filter" class="form-select rounded-pill" style="width:auto;min-width:140px;">
            <option value="">{{ __('All types') }}</option>
            <option value="standard">{{ __('Standard') }}</option>
            <option value="package">{{ __('Package') }}</option>
            <option value="membership">{{ __('Membership') }}</option>
            <option value="addon">{{ __('Add-on') }}</option>
            <option value="consultation">{{ __('Consultation') }}</option>
        </select>
        <div class="bk-filter-tabs">
            <button class="bk-filter-tab active" data-status="">{{ __('All') }}</button>
            <button class="bk-filter-tab" data-status="active">{{ __('Active') }}</button>
            <button class="bk-filter-tab" data-status="inactive">{{ __('Inactive') }}</button>
            <button class="bk-filter-tab" data-status="online">{{ __('Online') }}</button>
            <button class="bk-filter-tab" data-status="sale">🏷 {{ __('Sale') }}</button>
        </div>
        <div class="wb-viewtoggle ms-auto" role="group" aria-label="{{ __('View') }}">
            <button id="wb-view-list" class="active" title="{{ __('List') }}"><i data-feather="list" style="width:16px;height:16px;"></i></button>
            <button id="wb-view-cards" title="{{ __('Cards') }}"><i data-feather="grid" style="width:16px;height:16px;"></i></button>
        </div>
    </div>

    {{-- Rail + main --}}
    <div class="wb-grid">
        <aside class="wb-rail">
            <div class="wb-rail-head"><span>{{ __('Categories') }}</span></div>
            <div id="wb-rail-list"></div>
            <div class="wb-cat-add" id="wb-cat-add"><i data-feather="plus" style="width:14px;height:14px;"></i>{{ __('New category') }}</div>
        </aside>
        <main class="min-w-0">
            <div id="wb-list"></div>
            <div id="wb-cards" class="wb-cards wb-hide"></div>
            <div id="wb-empty" class="wb-empty wb-hide">
                <i data-feather="search"></i>
                <p class="mb-2">{{ __('No services match your filters.') }}</p>
            </div>
        </main>
    </div>

    {{-- Bulk action bar --}}
    <div class="wb-bulkbar" id="wb-bulkbar">
        <div class="wb-bulkbar-inner">
            <span class="cnt"><span id="wb-bulk-count">0</span> {{ __('selected') }}</span>
            <button class="btn btn-sm btn-light rounded-pill" data-bulk="activate"><i data-feather="check-circle" style="width:14px;height:14px;" class="me-1"></i>{{ __('Activate') }}</button>
            <button class="btn btn-sm btn-light rounded-pill" data-bulk="deactivate">{{ __('Deactivate') }}</button>
            <button class="btn btn-sm btn-primary rounded-pill" data-bulk="copy"><i data-feather="copy" style="width:14px;height:14px;" class="me-1"></i>{{ __('Copy to branches') }}</button>
            <div class="dropdown">
                <button class="btn btn-sm btn-light rounded-pill dropdown-toggle" data-bs-toggle="dropdown">{{ __('More') }}</button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#" data-bulk="show_online"><i data-feather="globe" style="width:14px;height:14px;" class="me-2"></i>{{ __('Show in online booking') }}</a></li>
                    <li><a class="dropdown-item" href="#" data-bulk="hide_online"><i data-feather="eye-off" style="width:14px;height:14px;" class="me-2"></i>{{ __('Make internal only') }}</a></li>
                    <li><a class="dropdown-item" href="#" data-bulk="duplicate"><i data-feather="copy" style="width:14px;height:14px;" class="me-2"></i>{{ __('Duplicate') }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-bulk="move"><i data-feather="folder" style="width:14px;height:14px;" class="me-2"></i>{{ __('Move to category…') }}</a></li>
                    <li><a class="dropdown-item" href="#" data-bulk="price"><i data-feather="dollar-sign" style="width:14px;height:14px;" class="me-2"></i>{{ __('Change price…') }}</a></li>
                    <li><a class="dropdown-item" href="#" data-bulk="duration"><i data-feather="clock" style="width:14px;height:14px;" class="me-2"></i>{{ __('Change duration…') }}</a></li>
                </ul>
            </div>
            <button class="btn btn-sm btn-outline-danger rounded-pill" data-bulk="delete"><i data-feather="trash-2" style="width:14px;height:14px;"></i></button>
            <button class="wb-iconbtn" id="wb-bulk-clear" title="{{ __('Clear selection') }}"><i data-feather="x" style="width:16px;height:16px;"></i></button>
        </div>
    </div>
</div>
@endsection

@push('company-after-template')
@include('company.services.partials.workbench-drawer')
@include('company.services.partials.workbench-modals')

{{-- Toast --}}
<div id="wb-toast" style="position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:1200;min-width:220px;pointer-events:none;opacity:0;transition:opacity .25s;">
    <div id="wb-toast-inner" class="d-flex align-items-center gap-2 px-4 py-3 rounded-pill shadow-lg" style="pointer-events:auto;">
        <i id="wb-toast-icon" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span id="wb-toast-msg" class="fw-semibold" style="font-size:14px;"></span>
        <button id="wb-toast-undo" class="btn btn-sm btn-light rounded-pill ms-1 py-0 px-2 wb-hide" style="font-size:12px;">{{ __('Undo') }}</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
window.WB_BOOT = {
    branchId: {{ $branch->id }},
    csrf: @json(csrf_token()),
    locale: @json(app()->getLocale()),
    defaultCurrency: @json(config('booksy.default_currency')),
    currencies: @json(config('booksy.currencies')),
    services: @json($servicesData),
    categories: @json($serviceCategories->map(fn($c) => ['id' => $c->id, 'name' => $c->localizedName(), 'sort' => $c->sort_order])->values()),
    employees: @json($branchEmployees->map(fn($e) => ['id' => $e->id, 'name' => $e->localizedName()])->values()),
    resources: @json($branchResources->map(fn($r) => ['id' => $r->id, 'name' => $r->localizedName(), 'type' => $r->typeLabel()])->values()),
    branches: @json($siblingBranches->map(fn($b) => ['id' => $b->id, 'name' => $b->localizedName()])->values()),
    urls: {
        store:        @json(route('company.branches.services.store', $branch)),
        updateBase:   @json(url('company/services')),
        bulk:         @json(route('company.branches.services.bulk', $branch)),
        copy:         @json(route('company.branches.services.copy', $branch)),
        reorder:      @json(route('company.branches.services.reorder', $branch)),
        import:       @json(route('company.branches.services.import', $branch)),
        catStore:     @json(route('company.service-categories.store')),
        catBase:      @json(url('company/service-categories')),
        catReorder:   @json(route('company.service-categories.reorder')),
    },
    t: {
        // rows / badges
        internal: @json(__('Internal')), popular: @json(__('Popular')), recommended: @json(__('Rec')),
        sale: @json(__('Sale')), optional: @json(__('Optional')), remove: @json(__('Remove')),
        // service types
        standard: @json(__('Standard')), package: @json(__('Package')), membership: @json(__('Membership')),
        addon: @json(__('Add-on')), consultation: @json(__('Consultation')),
        // row actions
        edit: @json(__('Edit')), duplicate: @json(__('Duplicate')), del: @json(__('Delete')),
        copyTo: @json(__('Copy to branches…')),
        showOnline: @json(__('Show in online booking')), hideOnline: @json(__('Make internal only')),
        // units
        h: @json(__('h')), m: @json(__('m')), min: @json(__('min')), from: @json(__('from')),
        // general
        all: @json(__('All')), uncategorized: @json(__('Uncategorized')),
        services: @json(__('services')), service: @json(__('service')), selected: @json(__('selected')),
        // drawer
        addService: @json(__('Add service')), editService: @json(__('Edit service')),
        saveService: @json(__('Save service')), saveChanges: @json(__('Save changes')),
        // notifications
        created: @json(__('Service created.')), updated: @json(__('Service updated.')),
        deleted: @json(__('Service deleted.')), duplicated: @json(__('Service duplicated.')),
        undone: @json(__('Restored.')), changesApplied: @json(__('Changes applied.')),
        genericError: @json(__('Something went wrong. Please try again.')),
        confirmDelete: @json(__('Delete this service?')),
        // inline prompts
        pickBranch: @json(__('Pick at least one branch')), chooseFile: @json(__('Choose a file')),
        categorySaved: @json(__('Category saved')), nameRequired: @json(__('Name is required')),
        noMatches: @json(__('No matches')),
        newCategory: @json(__('New category')), editCategory: @json(__('Edit category')),
        moveToCategory: @json(__('Move to category')), changePrice: @json(__('Change price')),
        changeDuration: @json(__('Change duration')),
        // copy / import summary fragments
        copied: @json(__('copied')), added: @json(__('added')), refreshed: @json(__('refreshed')),
        skipped: @json(__('skipped')), refreshing: @json(__('Refreshing…')),
        copyFailed: @json(__('Copy failed. Refresh the page and try again.')),
        nothingCopied: @json(__('Nothing new to copy')),
        // badge labels
        mostRequested: @json(__('Most requested')), badgeNew: @json(__('New')),
        specialOffer: @json(__('Special offer')), premium: @json(__('Premium')),
    },
};
</script>
<script src="{{ asset('backend/assets/vendors/sortable.min.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/services-workbench.js') }}?v=4"></script>
@endpush
