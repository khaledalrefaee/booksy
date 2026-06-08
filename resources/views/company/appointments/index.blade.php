@extends('company.dashboard')

@push('company-styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
/* ══════════════════════════════════════════
   CSS VARIABLES — dark (default) / light
══════════════════════════════════════════ */
:root {
    --cal-bg:           #151521;
    --cal-surface:      #1e1e2d;
    --cal-surface2:     #252535;
    --cal-border:       rgba(255,255,255,.06);
    --cal-border2:      rgba(255,255,255,.04);
    --cal-text:         #e2e8f0;
    --cal-text-muted:   #64748b;
    --cal-text-soft:    #94a3b8;
    --cal-accent:       #7c3aed;
    --cal-accent2:      #a78bfa;
    --cal-today-bg:     rgba(124,58,237,.08);
    --cal-now-color:    #f97316;
    --cal-toolbar-bg:   #1a1a2e;
    --cal-hdr-bg:       #16162a;
    --cal-shadow:       0 4px 24px rgba(0,0,0,.45);
    --cal-slot-border:  rgba(255,255,255,.04);
    --cal-pill-active:  rgba(124,58,237,.18);
    --cal-scroll:       rgba(255,255,255,.08);
    --cal-radius:       18px;
    --cal-ev-radius:    9px;
}

.bk-theme-light {
    --cal-bg:           #f4f6fb;
    --cal-surface:      #ffffff;
    --cal-surface2:     #f8f9fc;
    --cal-border:       rgba(0,0,0,.07);
    --cal-border2:      rgba(0,0,0,.04);
    --cal-text:         #1e293b;
    --cal-text-muted:   #94a3b8;
    --cal-text-soft:    #64748b;
    --cal-accent:       #6d28d9;
    --cal-accent2:      #7c3aed;
    --cal-today-bg:     rgba(109,40,217,.05);
    --cal-now-color:    #f97316;
    --cal-toolbar-bg:   #ffffff;
    --cal-hdr-bg:       #f8f9fc;
    --cal-shadow:       0 4px 20px rgba(0,0,0,.08);
    --cal-slot-border:  rgba(0,0,0,.05);
    --cal-pill-active:  rgba(109,40,217,.08);
    --cal-scroll:       rgba(0,0,0,.08);
}

/* ══════════════════════════════════════════
   PAGE WRAPPER
══════════════════════════════════════════ */
.bk-appt-page { padding-bottom: 40px; }

/* ══════════════════════════════════════════
   TOP BAR
══════════════════════════════════════════ */
.bk-topbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    background: var(--cal-surface);
    border-radius: 14px;
    padding: 11px 18px;
    margin-bottom: 18px;
    box-shadow: var(--cal-shadow);
    border: 1px solid var(--cal-border);
}
.bk-topbar select {
    background: transparent;
    border: none;
    font-size: .82rem;
    font-weight: 600;
    color: var(--cal-text);
    cursor: pointer;
    outline: none;
    min-width: 120px;
}
.bk-topbar select option { background: var(--cal-surface); }
.bk-divider { width: 1px; height: 22px; background: var(--cal-border); }

/* Status pills */
.bk-st-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 20px;
    font-size: .71rem;
    font-weight: 700;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all .15s;
    user-select: none;
}
.bk-st-pill .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.bk-st-pill.off  { opacity: .32; }

/* View tabs */
.bk-view-tabs {
    display: flex;
    gap: 3px;
    background: var(--cal-surface2);
    border-radius: 11px;
    padding: 3px;
    border: 1px solid var(--cal-border);
}
.bk-vtab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 9px;
    font-size: .78rem;
    font-weight: 700;
    color: var(--cal-text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all .18s;
    white-space: nowrap;
}
.bk-vtab.active {
    background: var(--cal-accent);
    color: #fff;
    box-shadow: 0 2px 10px rgba(124,58,237,.35);
}

/* ══════════════════════════════════════════
   CALENDAR WRAPPER
══════════════════════════════════════════ */
#bk-cal-shell {
    background: var(--cal-surface);
    border-radius: var(--cal-radius);
    overflow: hidden;
    box-shadow: var(--cal-shadow);
    border: 1px solid var(--cal-border);
}

/* Toolbar */
#bk-cal-shell .fc-toolbar.fc-header-toolbar {
    background: var(--cal-toolbar-bg);
    padding: 14px 20px 12px;
    border-bottom: 1px solid var(--cal-border);
    margin-bottom: 0 !important;
    flex-wrap: wrap;
    gap: 8px;
}
#bk-cal-shell .fc-toolbar-title {
    font-size: .98rem !important;
    font-weight: 800;
    color: var(--cal-text);
    letter-spacing: -.01em;
}

/* Toolbar buttons */
#bk-cal-shell .fc-button {
    background: var(--cal-surface2) !important;
    border: 1px solid var(--cal-border) !important;
    color: var(--cal-text-soft) !important;
    box-shadow: none !important;
    font-size: .76rem !important;
    font-weight: 700 !important;
    padding: .28rem .78rem !important;
    border-radius: 20px !important;
    transition: all .15s;
}
#bk-cal-shell .fc-button:hover {
    background: var(--cal-pill-active) !important;
    border-color: var(--cal-accent) !important;
    color: var(--cal-accent2) !important;
}
#bk-cal-shell .fc-button.fc-button-active,
#bk-cal-shell .fc-button-primary:not(:disabled):active {
    background: var(--cal-accent) !important;
    border-color: var(--cal-accent) !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(124,58,237,.4) !important;
}
#bk-cal-shell .fc-today-button {
    background: linear-gradient(135deg,#f97316,#ef4444) !important;
    border: none !important;
    color: #fff !important;
    font-weight: 800 !important;
    box-shadow: 0 2px 10px rgba(249,115,22,.4) !important;
}
#bk-cal-shell .fc-today-button:disabled { opacity: .45; }

/* Column headers */
#bk-cal-shell .fc-col-header { background: var(--cal-hdr-bg); }
#bk-cal-shell .fc-col-header-cell {
    padding: 10px 0 !important;
    border-color: var(--cal-border) !important;
}
#bk-cal-shell .fc-col-header-cell-cushion {
    display: flex; flex-direction: column; align-items: center;
    gap: 3px; padding: 0 !important; text-decoration: none !important;
}
#bk-cal-shell .fc-col-header-cell-cushion .d-abbr {
    font-size: .63rem; font-weight: 800; color: var(--cal-text-muted);
    text-transform: uppercase; letter-spacing: .06em;
}
#bk-cal-shell .fc-col-header-cell-cushion .d-num {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 800; color: var(--cal-text);
    transition: background .15s;
}
#bk-cal-shell .fc-day-today .d-num {
    background: linear-gradient(135deg,#f97316,#ef4444);
    color: #fff;
    box-shadow: 0 2px 10px rgba(249,115,22,.45);
}

/* Time grid */
#bk-cal-shell .fc-timegrid-slot       { height: 48px !important; border-color: var(--cal-slot-border) !important; }
#bk-cal-shell .fc-timegrid-slot-minor { border-top-style: dashed !important; border-color: var(--cal-border2) !important; }
#bk-cal-shell .fc-timegrid-slot-label { font-size: .67rem; font-weight: 700; color: var(--cal-text-muted); padding-right: 10px !important; vertical-align: top; padding-top: 4px !important; }
#bk-cal-shell .fc-timegrid-axis       { width: 58px !important; background: var(--cal-hdr-bg); }
#bk-cal-shell .fc-timegrid-col        { border-color: var(--cal-border) !important; }
#bk-cal-shell .fc-day-today.fc-timegrid-col { background: var(--cal-today-bg) !important; }
#bk-cal-shell .fc-scrollgrid          { border-color: var(--cal-border) !important; }
#bk-cal-shell .fc-scrollgrid-section > td { border-color: var(--cal-border) !important; }
#bk-cal-shell .fc-scrollgrid-sync-table td { border-color: var(--cal-border) !important; }

/* Now indicator */
#bk-cal-shell .fc-timegrid-now-indicator-line {
    border-color: var(--cal-now-color) !important;
    border-width: 2px !important;
    box-shadow: 0 0 6px rgba(249,115,22,.5);
}
#bk-cal-shell .fc-timegrid-now-indicator-arrow {
    border-top-color: var(--cal-now-color) !important;
    border-bottom-color: var(--cal-now-color) !important;
}

/* ── Event cards ── */
#bk-cal-shell .fc-timegrid-event {
    border-radius: var(--cal-ev-radius) !important;
    border: none !important;
    margin: 2px 3px !important;
    padding: 0 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,.25) !important;
    backdrop-filter: blur(4px);
    overflow: hidden;
}
#bk-cal-shell .fc-timegrid-event::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: rgba(255,255,255,.5);
    border-radius: 9px 0 0 9px;
}
#bk-cal-shell .fc-event-main { padding: 6px 8px 5px 11px !important; }
#bk-cal-shell .ev-name { font-size: .73rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#bk-cal-shell .ev-svc  { font-size: .65rem; opacity: .82; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
#bk-cal-shell .ev-emp  {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .6rem; margin-top: 3px;
    background: rgba(255,255,255,.18); border-radius: 8px;
    padding: 1px 6px; width: fit-content; max-width: 100%;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#bk-cal-shell .ev-emp-dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.7); flex-shrink: 0; }

/* DayGrid events */
#bk-cal-shell .fc-daygrid-event {
    border-radius: 6px !important; border: none !important;
    padding: 2px 7px !important; font-size: .73rem !important;
    font-weight: 700 !important; box-shadow: 0 1px 5px rgba(0,0,0,.2) !important;
}
#bk-cal-shell .fc-daygrid-day-number,
#bk-cal-shell .fc-daygrid-day-top { color: var(--cal-text) !important; text-decoration: none !important; }
#bk-cal-shell .fc-daygrid-day.fc-day-today { background: var(--cal-today-bg) !important; }
#bk-cal-shell .fc-daygrid-day { background: transparent; }
#bk-cal-shell td.fc-day { border-color: var(--cal-border) !important; }
#bk-cal-shell .fc-more-link { color: var(--cal-accent2) !important; font-weight: 700; }

/* List view inside FC */
#bk-cal-shell .fc-list { border-color: var(--cal-border) !important; }
#bk-cal-shell .fc-list-day-cushion { background: var(--cal-hdr-bg) !important; color: var(--cal-text) !important; }
#bk-cal-shell .fc-list-event:hover td { background: var(--cal-pill-active) !important; }
#bk-cal-shell .fc-list-event td { color: var(--cal-text) !important; border-color: var(--cal-border) !important; }
#bk-cal-shell .fc-list-empty { background: var(--cal-surface) !important; color: var(--cal-text-muted) !important; }

/* Scrollbars */
#bk-cal-shell ::-webkit-scrollbar { width: 4px; height: 4px; }
#bk-cal-shell ::-webkit-scrollbar-track { background: transparent; }
#bk-cal-shell ::-webkit-scrollbar-thumb { background: var(--cal-scroll); border-radius: 3px; }

/* ══════════════════════════════════════════
   LIST TABLE VIEW
══════════════════════════════════════════ */
.bk-list-card {
    background: var(--cal-surface);
    border-radius: var(--cal-radius);
    border: 1px solid var(--cal-border);
    box-shadow: var(--cal-shadow);
    overflow: hidden;
}
.bk-list-card table thead th {
    background: var(--cal-hdr-bg);
    color: var(--cal-text-muted);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
    padding: 13px 14px;
    border-color: var(--cal-border);
}
.bk-list-card table tbody td {
    color: var(--cal-text);
    border-color: var(--cal-border);
    font-size: .82rem;
    padding: 11px 14px;
    vertical-align: middle;
}
.bk-list-card table tbody tr:hover td {
    background: var(--cal-pill-active);
    cursor: pointer;
}

/* ══════════════════════════════════════════
   POPUP
══════════════════════════════════════════ */
#bk-popup {
    position: fixed;
    z-index: 9999;
    width: 292px;
    animation: bkPop .14s cubic-bezier(.22,1,.36,1);
    pointer-events: auto;
    filter: drop-shadow(0 8px 30px rgba(0,0,0,.4));
}
@keyframes bkPop {
    from { opacity:0; transform:translateY(-8px) scale(.95); }
    to   { opacity:1; transform:translateY(0)    scale(1); }
}
.bk-pp-hdr {
    border-radius: 14px 14px 0 0;
    padding: 13px 15px 11px;
    position: relative;
    overflow: hidden;
}
.bk-pp-hdr::after {
    content: '';
    position: absolute;
    right: -20px; top: -20px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
}
.bk-pp-body {
    background: var(--cal-surface);
    border-radius: 0 0 14px 14px;
    padding: 11px 15px 13px;
    border: 1px solid var(--cal-border);
    border-top: none;
}
.bk-pp-row {
    display: flex; align-items: center; gap: 9px;
    padding: 6px 0;
    border-bottom: 1px solid var(--cal-border);
    font-size: .8rem; color: var(--cal-text);
}
.bk-pp-row:last-child { border-bottom: none; }
.bk-pp-row .ico { color: var(--cal-text-muted); flex-shrink: 0; }

/* Employee row */
.bk-pp-emp {
    display: flex; align-items: center; gap: 10px;
    padding: 7px 0 8px;
    border-bottom: 1px solid var(--cal-border);
    margin-bottom: 2px;
}
.bk-pp-emp-av {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.bk-pp-emp-label { font-size: .67rem; color: var(--cal-text-muted); font-weight: 600; }
.bk-pp-emp-name  { font-size: .84rem; color: var(--cal-text); font-weight: 800; }

/* Status badge inside popup */
.bk-pp-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    background: rgba(255,255,255,.18);
    font-size: .68rem; font-weight: 800; color: #fff;
}
.bk-pp-close {
    background: rgba(255,255,255,.18); border: none; border-radius: 50%;
    width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: #fff; font-size: .82rem; line-height: 1;
    transition: background .15s;
}
.bk-pp-close:hover { background: rgba(255,255,255,.3); }

/* CTA button */
.bk-pp-btn {
    display: block; width: 100%; padding: 9px;
    margin-top: 10px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, var(--cal-accent), #5b21b6);
    color: #fff; font-size: .78rem; font-weight: 800;
    text-align: center; text-decoration: none;
    box-shadow: 0 3px 12px rgba(124,58,237,.4);
    transition: filter .15s, transform .1s;
}
.bk-pp-btn:hover { filter: brightness(1.1); transform: translateY(-1px); color: #fff; }
</style>
@endpush

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $statusDefs = [
        'pending'   => ['bg'=>'#f59e0b','label-ar'=>'معلّق',   'label-en'=>'Pending'],
        'confirmed' => ['bg'=>'#10b981','label-ar'=>'مؤكد',    'label-en'=>'Confirmed'],
        'completed' => ['bg'=>'#6366f1','label-ar'=>'مكتمل',   'label-en'=>'Completed'],
        'cancelled' => ['bg'=>'#6b7280','label-ar'=>'ملغى',    'label-en'=>'Cancelled'],
        'rejected'  => ['bg'=>'#ef4444','label-ar'=>'مرفوض',   'label-en'=>'Rejected'],
        'no_show'   => ['bg'=>'#94a3b8','label-ar'=>'لم يحضر', 'label-en'=>'No show'],
    ];
@endphp

<div class="page-content bk-appt-page">

    {{-- ── Page header ── --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-2">
        <h4 class="mb-0 fw-bold" style="color:var(--cal-text);">{{ __('Appointments') }}</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">

            {{-- View tabs --}}
            <div class="bk-view-tabs">
                <button class="bk-vtab active" id="tab-cal" onclick="switchView('cal')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ $isRtl ? 'التقويم' : 'Calendar' }}
                </button>
                <button class="bk-vtab" id="tab-staff" onclick="switchView('staff')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    {{ $isRtl ? 'الموظفون' : 'Staff' }}
                </button>
                <button class="bk-vtab" id="tab-list" onclick="switchView('list')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1.5" fill="currentColor"/><circle cx="3" cy="12" r="1.5" fill="currentColor"/><circle cx="3" cy="18" r="1.5" fill="currentColor"/></svg>
                    {{ $isRtl ? 'القائمة' : 'List' }}
                </button>
            </div>

            <a href="{{ route('company.appointments.create') }}"
               class="btn btn-sm rounded-pill fw-bold px-4"
               style="background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;box-shadow:0 3px 12px rgba(124,58,237,.4);border:none;">
                + {{ __('New appointment') }}
            </a>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- ── Filters bar ── --}}
    <div class="bk-topbar">

        {{-- Branch selector --}}
        <div class="d-flex align-items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--cal-text-muted)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <select id="filter-branch">
                <option value="">{{ __('All branches') }}</option>
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                        {{ $b->localizedName() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="bk-divider d-none d-md-block"></div>

        {{-- Status pills --}}
        <div class="d-flex flex-wrap gap-1" id="status-filters">
            @foreach($statusDefs as $st => $sc)
                @php $lbl = $isRtl ? $sc['label-ar'] : $sc['label-en']; @endphp
                <button class="bk-st-pill" data-status="{{ $st }}"
                    style="background:{{ $sc['bg'] }}22;color:{{ $sc['bg'] }};border-color:{{ $sc['bg'] }}44;">
                    <span class="dot" style="background:{{ $sc['bg'] }};"></span>
                    {{ $lbl }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══ STAFF VIEW ══ --}}
    <div id="view-staff" class="d-none">
        <div id="bk-staff-shell" style="background:var(--cal-surface);border-radius:var(--cal-radius);border:1px solid var(--cal-border);box-shadow:var(--cal-shadow);overflow:hidden;">

            {{-- Staff nav --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px 11px;border-bottom:1px solid var(--cal-border);background:var(--cal-toolbar-bg);flex-wrap:wrap;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <button id="sf-prev" style="background:var(--cal-surface2);border:1px solid var(--cal-border);border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--cal-text);font-size:1rem;">‹</button>
                    <span id="sf-title" style="font-size:.95rem;font-weight:800;color:var(--cal-text);"></span>
                    <button id="sf-next" style="background:var(--cal-surface2);border:1px solid var(--cal-border);border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--cal-text);font-size:1rem;">›</button>
                </div>
                <button id="sf-today" style="background:linear-gradient(135deg,#f97316,#ef4444);border:none;border-radius:18px;padding:5px 16px;font-size:.76rem;font-weight:800;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(249,115,22,.4);">
                    {{ $isRtl ? 'اليوم' : 'Today' }}
                </button>
            </div>

            {{-- Staff grid container --}}
            <div id="sf-grid-wrap" style="overflow:auto;max-height:680px;">
                <div id="sf-grid" style="display:flex;min-width:600px;">
                    <div class="text-center py-5 w-100" style="color:var(--cal-text-muted);">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ CALENDAR VIEW ══ --}}
    <div id="view-cal">
        <div id="bk-cal-shell">
            <div id="booksy-calendar"></div>
        </div>
    </div>

    {{-- ══ LIST VIEW ══ --}}
    <div id="view-list" class="d-none">
        <div class="bk-list-card">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Service') }}</th>
                        <th>{{ __('Employee') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Start') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="pe-4">{{ __('Price') }}</th>
                    </tr>
                </thead>
                <tbody id="list-tbody">
                    <tr><td colspan="8" class="text-center py-5" style="color:var(--cal-text-muted);">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        {{ __('Loading...') }}
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ── Popup ── --}}
<div id="bk-popup" class="d-none">
    <div class="bk-pp-hdr" id="bk-pp-hdr">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="bk-pp-badge" id="bk-pp-status"></span>
            <button class="bk-pp-close" id="bk-pp-close">✕</button>
        </div>
        <div style="font-size:.92rem;font-weight:800;color:#fff;" id="bk-pp-title"></div>
        <div style="font-size:.73rem;color:rgba(255,255,255,.78);margin-top:3px;" id="bk-pp-time"></div>
    </div>
    <div class="bk-pp-body">
        {{-- Employee (highlighted) --}}
        <div class="bk-pp-emp">
            <div class="bk-pp-emp-av" id="bk-pp-emp-av"></div>
            <div>
                <div class="bk-pp-emp-label">{{ __('Employee') }}</div>
                <div class="bk-pp-emp-name" id="bk-pp-emp-name"></div>
            </div>
        </div>
        <div class="bk-pp-row">
            <svg class="ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span id="bk-pp-service"></span>
        </div>
        <div class="bk-pp-row">
            <svg class="ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span id="bk-pp-branch"></span>
        </div>
        <div class="bk-pp-row">
            <svg class="ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span id="bk-pp-price" style="font-weight:800;"></span>
        </div>
        {{-- Audit trail row --}}
        <div id="bk-pp-audit" class="bk-pp-row" style="display:none;font-size:.75rem;color:var(--cal-text-soft);gap:6px;"></div>

        <a id="bk-pp-link" href="#" class="bk-pp-btn">{{ __('View details') }} ←</a>
    </div>
</div>
@endsection

@push('company-after-template')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
@php
    $fcLocale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $isRtlJs  = app()->getLocale() === 'ar' ? 'true' : 'false';
@endphp
<script>
(function () {
'use strict';

var EVENTS_URL = '{{ route("company.appointments.calendar-events") }}';
var IS_RTL     = {{ $isRtlJs }};
var FC_LOCALE  = '{{ $fcLocale }}';

/* ── status labels (translated) ── */
var STATUS_LABELS = {
    pending:   '{{ $isRtl ? "معلّق"   : "Pending" }}',
    confirmed: '{{ $isRtl ? "مؤكد"    : "Confirmed" }}',
    completed: '{{ $isRtl ? "مكتمل"   : "Completed" }}',
    cancelled: '{{ $isRtl ? "ملغى"    : "Cancelled" }}',
    rejected:  '{{ $isRtl ? "مرفوض"   : "Rejected" }}',
    no_show:   '{{ $isRtl ? "لم يحضر" : "No show" }}',
};
var EV_COLORS = {
    pending:'#f59e0b', confirmed:'#10b981', completed:'#6366f1',
    cancelled:'#6b7280', rejected:'#ef4444', no_show:'#94a3b8',
};
var EMP_COLORS = ['#7c3aed','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#f59e0b','#8b5cf6'];

var activeStatuses = Object.keys(STATUS_LABELS);
var activeBranch   = '';

/* ════════════════════════════════
   FULLCALENDAR
════════════════════════════════ */
var calEl    = document.getElementById('booksy-calendar');
var calendar = new FullCalendar.Calendar(calEl, {
    locale:       FC_LOCALE,
    direction:    IS_RTL ? 'rtl' : 'ltr',
    initialView:  'timeGridWeek',
    height:       'auto',
    firstDay:     IS_RTL ? 0 : 1,
    nowIndicator: true,
    navLinks:     true,
    dayMaxEvents: true,
    scrollTime:   '08:00:00',
    slotMinTime:  '00:00:00',
    slotMaxTime:  '24:00:00',
    slotDuration: '00:30:00',
    expandRows:   false,

    headerToolbar: {
        start:  'prev,next today',
        center: 'title',
        end:    'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
    },
    buttonText: {
        today: IS_RTL ? 'اليوم'    : 'Today',
        month: IS_RTL ? 'الشهر'    : 'Month',
        week:  IS_RTL ? 'الأسبوع'  : 'Week',
        day:   IS_RTL ? 'اليوم'    : 'Day',
        list:  IS_RTL ? 'القائمة'  : 'List',
    },

    /* Custom day headers */
    dayHeaderContent: function (arg) {
        var abbrs_en = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
        var abbrs_ar = ['أحد','اثن','ثلث','أرب','خمس','جمع','سبت'];
        var abbrs = IS_RTL ? abbrs_ar : abbrs_en;
        return {
            html: '<div class="d-abbr">' + abbrs[arg.date.getDay()] + '</div>'
                + '<div class="d-num">'  + arg.date.getDate() + '</div>'
        };
    },

    /* Custom slot labels */
    slotLabelContent: function (arg) {
        var h    = arg.date.getHours();
        var h12  = h % 12 || 12;
        var ampm = h < 12
            ? (IS_RTL ? 'ص' : 'AM')
            : (IS_RTL ? 'م' : 'PM');
        return { html: '<span>' + h12 + '<br><small style="font-size:.55rem">' + ampm + '</small></span>' };
    },

    /* Event card content */
    eventContent: function (arg) {
        var props = arg.event.extendedProps;
        if (props.type === 'closed' || props.type === 'outside-hours') return;

        var parts   = arg.event.title.split(' · ');
        var name    = parts[0] || '';
        var service = parts.slice(1).join(' · ') || props.service || '';
        var emp     = props.employee || '';

        return {
            html: '<div class="ev-name">' + _esc(name) + '</div>'
                + '<div class="ev-svc">'  + _esc(service) + '</div>'
                + (emp ? '<div class="ev-emp"><span class="ev-emp-dot"></span>' + _esc(emp) + '</div>' : '')
        };
    },

    /* Fetch events */
    events: function (info, ok, fail) {
        var p = new URLSearchParams({ start: info.startStr, end: info.endStr });
        if (activeBranch) p.set('branch_id', activeBranch);
        fetch(EVENTS_URL + '?' + p, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(function (data) {
                var filtered = data.filter(function (ev) {
                    var t = ev.extendedProps && ev.extendedProps.type;
                    if (t === 'closed' || t === 'outside-hours') return true; // always show background
                    return activeStatuses.includes(ev.extendedProps.status);
                });
                ok(filtered);
            })
            .catch(fail);
    },

    /* Click → popup (skip background events) */
    eventClick: function (info) {
        var t = info.event.extendedProps && info.event.extendedProps.type;
        if (t === 'closed' || t === 'outside-hours') return;
        info.jsEvent.preventDefault();

        var p    = info.event.extendedProps;
        var parts= info.event.title.split(' · ');
        showPopup({
            customer:   parts[0] || '',
            service:    p.service || parts.slice(1).join(' · '),
            branch:     p.branch,
            employee:   p.employee,
            status:     p.status,
            color:      info.event.backgroundColor,
            price:      p.price,
            showUrl:    p.showUrl,
            startLabel: _fmtTime(info.event.start),
            endLabel:   _fmtTime(info.event.end),
            changedBy:  p.changedBy,
            changedAt:  p.changedAt,
            prevStatus: p.prevStatus,
        }, info.jsEvent);
    },
});
calendar.render();

/* ════════════════════════════════
   POPUP
════════════════════════════════ */
var popup = document.getElementById('bk-popup');

function showPopup(a, ev) {
    var color   = a.color || '#7c3aed';
    var initials= _initials(a.employee);

    document.getElementById('bk-pp-hdr').style.background = color;
    document.getElementById('bk-pp-status').textContent   = STATUS_LABELS[a.status] || a.status;
    document.getElementById('bk-pp-title').textContent    = a.customer || '—';
    document.getElementById('bk-pp-time').textContent     = (a.startLabel || '') + ' – ' + (a.endLabel || '');
    document.getElementById('bk-pp-emp-av').textContent   = initials;
    document.getElementById('bk-pp-emp-av').style.background = color;
    document.getElementById('bk-pp-emp-name').textContent = a.employee || '—';
    document.getElementById('bk-pp-service').textContent  = a.service  || '—';
    document.getElementById('bk-pp-branch').textContent   = a.branch   || '—';
    document.getElementById('bk-pp-price').textContent    = (a.price || '0.00') + ' SAR';
    document.getElementById('bk-pp-link').href            = a.showUrl  || '#';

    /* audit row */
    var auditRow = document.getElementById('bk-pp-audit');
    if (a.changedBy) {
        var dt = a.changedAt ? new Date(a.changedAt).toLocaleString(IS_RTL ? 'ar-SA' : 'en-US', {
            month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12: true
        }) : '';
        var prev = a.prevStatus ? (STATUS_LABELS[a.prevStatus] || a.prevStatus) + ' → ' : '';
        auditRow.innerHTML = '<svg style="flex-shrink:0;color:#7c3aed;" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'
            + '<span>' + (IS_RTL ? 'غُيِّر بواسطة: ' : 'Changed by: ') + '<b>' + _esc(a.changedBy) + '</b>'
            + (dt ? ' · ' + dt : '')
            + (prev ? '<br><small style="color:var(--cal-text-muted);">' + prev + (STATUS_LABELS[a.status]||a.status) + '</small>' : '')
            + '</span>';
        auditRow.style.display = 'flex';
    } else {
        auditRow.style.display = 'none';
    }

    popup.classList.remove('d-none');

    var pw = 292, ph = 290;
    var wx = window.innerWidth, wy = window.innerHeight;
    var left = ev.clientX + 16;
    var top  = ev.clientY + 16;
    if (left + pw > wx - 10) left = ev.clientX - pw - 16;
    if (top  + ph > wy - 10) top  = ev.clientY - ph - 16;
    popup.style.left = left + 'px';
    popup.style.top  = top  + 'px';
}

document.getElementById('bk-pp-close').addEventListener('click', function () {
    popup.classList.add('d-none');
});
document.addEventListener('click', function (e) {
    if (!popup.contains(e.target) && !e.target.closest('.fc-event')) {
        popup.classList.add('d-none');
    }
});

/* ════════════════════════════════
   LIST VIEW
════════════════════════════════ */
var listLoaded = false;

function loadListView() {
    if (listLoaded) return;
    var tbody = document.getElementById('list-tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5" style="color:var(--cal-text-muted);"><div class="spinner-border spinner-border-sm me-2"></div>{{ $isRtl ? "جارٍ التحميل..." : "Loading..." }}</td></tr>';

    var p = new URLSearchParams();
    if (activeBranch) p.set('branch_id', activeBranch);

    fetch(EVENTS_URL + '?' + p, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            /* ── filter out background events (closed/outside-hours) and apply status filter ── */
            var appts = data.filter(function (ev) {
                var t = ev.extendedProps && ev.extendedProps.type;
                if (t === 'closed' || t === 'outside-hours') return false;
                return activeStatuses.includes(ev.extendedProps.status);
            });

            if (!appts.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5" style="color:var(--cal-text-muted);">{{ $isRtl ? "لم يُعثر على مواعيد." : "No appointments found." }}</td></tr>';
                listLoaded = true;
                return;
            }

            tbody.innerHTML = appts.map(function (ev) {
                var pr     = ev.extendedProps || {};
                var title  = ev.title || '';
                var pts    = title.split(' · ');
                var cust   = _esc(pts[0] || '—');
                var svc    = _esc(pts.slice(1).join(' · ') || pr.service || '—');
                var col    = EV_COLORS[pr.status] || '#94a3b8';
                var init   = _initials(pr.employee || '');
                var empIdx = Math.abs(_hashStr(pr.employee || '')) % EMP_COLORS.length;
                var empCol = EMP_COLORS[empIdx];
                var dt     = ev.start ? new Date(ev.start).toLocaleString(IS_RTL ? 'ar-SA' : 'en-US', {
                    year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12: true
                }) : '—';
                var endDt  = ev.end ? new Date(ev.end).toLocaleTimeString(IS_RTL ? 'ar-SA' : 'en-US', {
                    hour:'2-digit', minute:'2-digit', hour12: true
                }) : '';

                /* changed-by badge */
                var auditBadge = '';
                if (pr.changedBy) {
                    auditBadge = '<div style="font-size:.65rem;color:var(--cal-text-muted);margin-top:2px;">'
                        + '🔒 ' + _esc(pr.changedBy) + '</div>';
                }

                return '<tr style="cursor:pointer;" onclick="location.href=\'' + (pr.showUrl || '#') + '\'">'
                    /* # */
                    + '<td class="ps-4" style="color:var(--cal-text-muted);font-size:.76rem;font-weight:700;">#' + ev.id + '</td>'
                    /* Customer */
                    + '<td><div style="font-weight:800;font-size:.84rem;">' + cust + '</div>' + auditBadge + '</td>'
                    /* Service */
                    + '<td style="color:var(--cal-text-soft);font-size:.82rem;">' + svc + '</td>'
                    /* Employee */
                    + '<td><div style="display:flex;align-items:center;gap:7px;">'
                    +   '<div style="width:28px;height:28px;border-radius:50%;background:' + empCol + ';display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 2px 6px ' + empCol + '55;">' + _esc(init) + '</div>'
                    +   '<span style="font-size:.82rem;font-weight:600;">' + _esc(pr.employee || '—') + '</span>'
                    + '</div></td>'
                    /* Branch */
                    + '<td style="color:var(--cal-text-soft);font-size:.82rem;">' + _esc(pr.branch || '—') + '</td>'
                    /* Time */
                    + '<td style="font-size:.8rem;white-space:nowrap;">'
                    +   '<div style="font-weight:700;">' + dt + '</div>'
                    +   (endDt ? '<div style="color:var(--cal-text-muted);font-size:.72rem;">← ' + endDt + '</div>' : '')
                    + '</td>'
                    /* Status */
                    + '<td><span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;background:' + col + '20;color:' + col + ';font-size:.7rem;font-weight:800;border:1px solid ' + col + '40;">'
                    +   '<span style="width:6px;height:6px;border-radius:50%;background:' + col + ';display:inline-block;"></span>'
                    +   (STATUS_LABELS[pr.status] || pr.status)
                    + '</span></td>'
                    /* Price */
                    + '<td class="pe-4" style="font-weight:800;font-size:.88rem;">' + (pr.price || '0.00') + ' SAR</td>'
                    + '</tr>';
            }).join('');
            listLoaded = true;
        })
        .catch(function (err) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4" style="color:#ef4444;">⚠ {{ $isRtl ? "خطأ في تحميل البيانات" : "Error loading data" }}: ' + err.message + '</td></tr>';
        });
}

/* ════════════════════════════════
   FILTERS
════════════════════════════════ */
document.querySelectorAll('.bk-st-pill').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var st = this.dataset.status;
        if (activeStatuses.includes(st)) {
            activeStatuses = activeStatuses.filter(s => s !== st);
            this.classList.add('off');
        } else {
            activeStatuses.push(st);
            this.classList.remove('off');
        }
        calendar.refetchEvents();
    });
});

document.getElementById('filter-branch').addEventListener('change', function () {
    activeBranch = this.value;
    listLoaded = false;
    calendar.refetchEvents();
    if (document.getElementById('view-list').classList.contains('d-none') === false) {
        loadListView();
    }
});

/* ════════════════════════════════
   STAFF VIEW
════════════════════════════════ */
var STAFF_URL  = '{{ route("company.appointments.staff-events") }}';
var sfDate     = new Date();
var sfLoaded   = false;

var SF_EMP_COLORS = ['#7c3aed','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#f59e0b','#8b5cf6','#14b8a6','#a855f7'];
var HOUR_S = 0, HOUR_E = 24, SLOT_H = 52; /* px per hour */

var DAY_AR  = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
var DAY_EN  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
var MON_AR  = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
var MON_EN  = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function sfDateStr(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
function sfFmtTitle(d) {
    var dn = IS_RTL ? DAY_AR[d.getDay()] : DAY_EN[d.getDay()];
    var mn = IS_RTL ? MON_AR[d.getMonth()] : MON_EN[d.getMonth()];
    return dn + '، ' + d.getDate() + ' ' + mn + ' ' + d.getFullYear();
}
function sfIsToday(d) {
    var t = new Date();
    return d.getDate()===t.getDate() && d.getMonth()===t.getMonth() && d.getFullYear()===t.getFullYear();
}

function loadStaffView() {
    document.getElementById('sf-title').textContent = sfFmtTitle(sfDate);
    var grid = document.getElementById('sf-grid');
    grid.innerHTML = '<div class="text-center py-5 w-100" style="color:var(--cal-text-muted);"><div class="spinner-border spinner-border-sm"></div></div>';

    var p = new URLSearchParams({ date: sfDateStr(sfDate) });
    if (activeBranch) p.set('branch_id', activeBranch);

    fetch(STAFF_URL + '?' + p, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(function(data) { renderStaffGrid(data); })
        .catch(function(err) {
            grid.innerHTML = '<div class="text-center py-5 w-100" style="color:#ef4444;">⚠ ' + err.message + '</div>';
        });
}

function renderStaffGrid(data) {
    var staff = data.staff || [];
    var appts = (data.appointments || []).filter(function(a){ return activeStatuses.includes(a.status); });
    var totalH = HOUR_E - HOUR_S;
    var totalPx = totalH * SLOT_H;
    var html = '';

    /* ── Time column ── */
    html += '<div style="flex-shrink:0;width:52px;border-' + (IS_RTL?'left':'right') + ':1px solid var(--cal-border);">';
    html += '<div style="height:62px;border-bottom:1px solid var(--cal-border);background:var(--cal-hdr-bg);"></div>';
    for (var h = HOUR_S; h < HOUR_E; h++) {
        var h12 = h % 12 || 12;
        var ap  = h < 12 ? (IS_RTL?'ص':'AM') : (IS_RTL?'م':'PM');
        html += '<div style="height:' + SLOT_H + 'px;border-bottom:1px solid var(--cal-border);display:flex;align-items:flex-start;justify-content:center;padding-top:4px;">'
              + '<span style="font-size:.62rem;font-weight:700;color:var(--cal-text-muted);text-align:center;line-height:1.2;">' + h12 + '<br><small>' + ap + '</small></span>'
              + '</div>';
    }
    html += '</div>';

    /* ── Employee columns ── */
    if (staff.length === 0) {
        html += '<div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--cal-text-muted);font-size:.85rem;padding:40px;">'
              + (IS_RTL ? 'لا يوجد موظفون في هذا اليوم' : 'No staff found for this day') + '</div>';
    }

    staff.forEach(function(emp, idx) {
        var eColor   = SF_EMP_COLORS[idx % SF_EMP_COLORS.length];
        var empAppts = appts.filter(function(a){ return a.employeeId === emp.id; });

        /* Get working hours for this employee's day */
        var wh = emp.workingHours || null;

        html += '<div style="flex:1;min-width:140px;border-' + (IS_RTL?'left':'right') + ':1px solid var(--cal-border);position:relative;">';

        /* Header */
        html += '<div style="height:62px;border-bottom:1px solid var(--cal-border);background:var(--cal-hdr-bg);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:6px;position:sticky;top:0;z-index:3;">';
        html += '<div style="width:34px;height:34px;border-radius:50%;background:' + eColor + ';display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;box-shadow:0 3px 10px ' + eColor + '55;">' + _esc(emp.initials) + '</div>';
        html += '<div style="font-size:.68rem;font-weight:700;color:var(--cal-text);text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;">' + _esc(emp.name) + '</div>';
        html += '<div style="font-size:.6rem;color:var(--cal-text-muted);">' + empAppts.length + ' ' + (IS_RTL ? 'موعد' : 'appt') + '</div>';
        html += '</div>';

        /* Slots */
        html += '<div style="position:relative;height:' + totalPx + 'px;">';

        /* Hour lines */
        for (var hh = 0; hh < totalH; hh++) {
            html += '<div style="position:absolute;left:0;right:0;top:' + (hh*SLOT_H) + 'px;height:' + SLOT_H + 'px;border-bottom:1px solid var(--cal-border);"></div>';
            html += '<div style="position:absolute;left:0;right:0;top:' + (hh*SLOT_H + SLOT_H/2) + 'px;border-bottom:1px dashed var(--cal-border2);pointer-events:none;"></div>';
        }

        /* Closed / outside-hours shading from working hours */
        if (emp.closedSlots) {
            emp.closedSlots.forEach(function(cs) {
                var topPx = (cs.from - HOUR_S * 60) * (SLOT_H / 60);
                var htPx  = (cs.to - cs.from) * (SLOT_H / 60);
                if (topPx < 0) { htPx += topPx; topPx = 0; }
                if (htPx > 0 && topPx < totalPx) {
                    html += '<div style="position:absolute;left:0;right:0;top:' + topPx + 'px;height:' + htPx + 'px;background:repeating-linear-gradient(45deg,rgba(239,68,68,.04),rgba(239,68,68,.04) 4px,transparent 4px,transparent 12px);pointer-events:none;z-index:0;"></div>';
                }
            });
        }

        /* Appointment blocks — compute from ISO string in browser local TZ */
        empAppts.forEach(function(a) {
            var startD = a.startIso ? new Date(a.startIso) : null;
            var endD   = a.endIso   ? new Date(a.endIso)   : null;
            if (!startD) return;
            var startMin = startD.getHours() * 60 + startD.getMinutes();
            var endMin   = endD ? (endD.getHours() * 60 + endD.getMinutes()) : startMin + 30;
            /* update labels to browser local TZ */
            a.startLabel = startD.toLocaleTimeString(IS_RTL?'ar-SA':'en-US',{hour:'2-digit',minute:'2-digit',hour12:true});
            a.endLabel   = endD ? endD.toLocaleTimeString(IS_RTL?'ar-SA':'en-US',{hour:'2-digit',minute:'2-digit',hour12:true}) : '';
            var topMin = startMin - HOUR_S * 60;
            var durMin = Math.max(endMin - startMin, 20);
            var topPx2 = topMin * (SLOT_H / 60);
            var htPx2  = durMin * (SLOT_H / 60);
            if (topPx2 < 0 || topPx2 >= totalPx) return;
            htPx2 = Math.min(htPx2, totalPx - topPx2);

            var dataAttr = 'data-appt=\'' + JSON.stringify(a).replace(/\\/g,'\\\\').replace(/'/g,'&#39;') + '\'';

            html += '<div class="sf-appt-block" ' + dataAttr + ' onclick="sfShowPopup(this,event)"'
                  + ' style="position:absolute;left:3px;right:3px;top:' + topPx2 + 'px;height:' + htPx2 + 'px;'
                  + 'background:' + a.color + ';border-radius:9px;padding:5px 8px;cursor:pointer;'
                  + 'box-shadow:0 2px 8px ' + a.color + '55;overflow:hidden;z-index:1;'
                  + 'border-left:3px solid rgba(255,255,255,.4);">'
                  + '<div style="font-size:.72rem;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + _esc(a.customer) + '</div>';
            if (htPx2 > 38) {
                html += '<div style="font-size:.63rem;color:rgba(255,255,255,.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + _esc(a.service) + '</div>';
            }
            if (htPx2 > 55) {
                html += '<div style="font-size:.58rem;color:rgba(255,255,255,.7);margin-top:2px;">' + _esc(a.startLabel) + ' – ' + _esc(a.endLabel) + '</div>';
            }
            html += '</div>';
        });

        /* Now indicator */
        if (sfIsToday(sfDate)) {
            var now2 = new Date();
            var nowMin2 = now2.getHours() * 60 + now2.getMinutes() - HOUR_S * 60;
            if (nowMin2 > 0 && nowMin2 < totalH * 60) {
                var nowPx2 = nowMin2 * (SLOT_H / 60);
                html += '<div style="position:absolute;left:0;right:0;top:' + nowPx2 + 'px;height:2px;background:var(--cal-now-color);z-index:4;box-shadow:0 0 6px var(--cal-now-color);pointer-events:none;">'
                      + '<div style="position:absolute;left:-4px;top:-4px;width:10px;height:10px;border-radius:50%;background:var(--cal-now-color);"></div></div>';
            }
        }

        html += '</div></div>';
    });

    document.getElementById('sf-grid').innerHTML = html;

    /* Scroll to 8 AM or current time */
    var container = document.getElementById('sf-grid-wrap');
    var scrollTo = (8 - HOUR_S) * SLOT_H - 20;
    if (sfIsToday(sfDate)) {
        var cur = new Date();
        scrollTo = Math.max(0, (cur.getHours() - HOUR_S - 1) * SLOT_H);
    }
    container.scrollTop = scrollTo;
}

/* Popup from staff view */
window.sfShowPopup = function(el, ev) {
    ev.stopPropagation();
    try {
        var a = JSON.parse(el.dataset.appt.replace(/&#39;/g,"'"));
        showPopup({
            customer:   a.customer,
            service:    a.service,
            branch:     a.branch,
            employee:   a.employee,
            status:     a.status,
            color:      a.color,
            price:      a.price,
            showUrl:    a.showUrl,
            startLabel: a.startLabel,
            endLabel:   a.endLabel,
            changedBy:  a.changedBy || null,
            changedAt:  null,
            prevStatus: null,
        }, ev);
    } catch(e) { console.error(e); }
};

/* Nav buttons */
document.getElementById('sf-prev').addEventListener('click', function(){
    sfDate.setDate(sfDate.getDate()-1); loadStaffView();
});
document.getElementById('sf-next').addEventListener('click', function(){
    sfDate.setDate(sfDate.getDate()+1); loadStaffView();
});
document.getElementById('sf-today').addEventListener('click', function(){
    sfDate = new Date(); loadStaffView();
});

/* ════════════════════════════════
   VIEW SWITCHING
════════════════════════════════ */
function switchView(name) {
    var views = { cal: 'view-cal', staff: 'view-staff', list: 'view-list' };
    Object.keys(views).forEach(function (k) {
        document.getElementById(views[k]).classList.toggle('d-none', k !== name);
        document.getElementById('tab-' + k).classList.toggle('active', k === name);
    });
    if (name === 'list')  { listLoaded = false; loadListView(); }
    if (name === 'staff') loadStaffView();
    if (name === 'cal')   setTimeout(function () { calendar.updateSize(); }, 50);
}
window.switchView = switchView;

/* ════════════════════════════════
   HELPERS
════════════════════════════════ */
function _esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function _initials(n) {
    return (n || '').trim().split(/\s+/).map(w => w[0] || '').join('').toUpperCase().slice(0, 2);
}
function _fmtTime(d) {
    if (!d) return '';
    return d.toLocaleTimeString(IS_RTL ? 'ar-SA' : 'en-US', { hour:'2-digit', minute:'2-digit', hour12:true });
}
function _hashStr(s) {
    var h = 0;
    for (var i = 0; i < s.length; i++) h = (Math.imul(31, h) + s.charCodeAt(i)) | 0;
    return h;
}

})();
</script>
@endpush
