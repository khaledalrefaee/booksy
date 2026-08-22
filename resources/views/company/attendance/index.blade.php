@extends('company.dashboard')

@push('company-styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<style>
.att-hero {
    background:linear-gradient(135deg,#0f3460 0%,#16213e 50%,#1a1a2e 100%);
    border-radius:22px; padding:28px 30px 22px; margin-bottom:20px;
    position:relative; overflow:hidden; color:#fff;
}
.att-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(34,197,94,.08); pointer-events:none;
}
.att-chip {
    background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08);
    border-radius:14px; padding:14px 18px; text-align:center; min-width:100px;
}
.att-chip-num { font-size:26px; font-weight:900; font-family:'Poppins',sans-serif; }
.att-chip-lbl { font-size:10px; opacity:.45; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }
.att-row {
    display:flex; align-items:center; gap:14px;
    padding:14px 18px; border-radius:14px; transition:background .12s;
    border-bottom:1px solid rgba(255,255,255,.04);
}
.att-row:hover { background:rgba(255,255,255,.03); }
.bk-theme-light .att-row:hover { background:rgba(0,0,0,.02); }
.att-avatar {
    width:40px; height:40px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:15px; flex-shrink:0; color:#fff;
}
.att-name { font-size:14px; font-weight:700; }
.att-schedule { font-size:11px; opacity:.4; }
.att-time { font-size:13px; font-weight:700; }
.att-badge {
    font-size:10px; font-weight:700; padding:3px 10px;
    border-radius:20px; white-space:nowrap;
}
.att-badge.on_time  { background:rgba(34,197,94,.12); color:#22c55e; }
.att-badge.late     { background:rgba(245,158,11,.12); color:#f59e0b; }
.att-badge.absent   { background:rgba(239,68,68,.12); color:#ef4444; }
.att-badge.day_off  { background:rgba(100,116,139,.12); color:#94a3b8; }
.att-badge.on_leave { background:rgba(240,147,251,.12); color:#f093fb; }
.att-badge.none     { background:rgba(255,255,255,.06); color:rgba(255,255,255,.3); }
.att-loc {
    font-size:10px; font-weight:700; padding:2px 8px;
    border-radius:12px; display:inline-flex; align-items:center; gap:3px;
}
.att-loc.inside  { background:rgba(34,197,94,.1); color:#22c55e; }
.att-loc.nearby  { background:rgba(245,158,11,.1); color:#f59e0b; }
.att-loc.outside { background:rgba(239,68,68,.1); color:#ef4444; }
.att-btn {
    font-size:11px; font-weight:700; padding:5px 14px;
    border-radius:20px; border:none; cursor:pointer; transition:all .12s;
}
.att-btn:disabled { opacity:.4; cursor:not-allowed; }
.att-btn-checkin  { background:rgba(34,197,94,.15); color:#22c55e; }
.att-btn-checkin:hover:not(:disabled) { background:rgba(34,197,94,.25); }
.att-btn-checkout { background:rgba(102,126,234,.15); color:#667eea; }
.att-btn-checkout:hover:not(:disabled) { background:rgba(102,126,234,.25); }
.att-btn-absent   { background:rgba(239,68,68,.1); color:#ef4444; }
.att-btn-absent:hover:not(:disabled) { background:rgba(239,68,68,.2); }

.att-row-main { display:flex; align-items:center; gap:14px; flex:1 1 200px; min-width:0; }
.att-row-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

@media (max-width: 576px) {
    .att-hero { padding:20px 16px 16px; border-radius:16px; }
    .att-chip { min-width:0; flex:1 1 calc(50% - 6px); padding:10px 12px; }
    .att-row { flex-wrap:wrap; padding:12px 14px; }
    .att-row-meta {
        flex:1 1 100%; justify-content:space-between;
        margin-top:10px; padding-top:10px;
        border-top:1px dashed rgba(255,255,255,.08);
    }
    .att-time, .att-badge, .att-loc { font-size:11px; }
}
</style>
@endpush

@section('content')
<div class="page-content">

@include('company.partials.team-nav')

@php $avatarColors = ['#5C7038','#667eea','#22c55e','#ef4444','#f59e0b','#a78bfa','#fb923c','#06b6d4']; @endphp

{{-- Hero --}}
<div class="att-hero">
    <div class="position-relative" style="z-index:1;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">📋 {{ __('Attendance') }}</h3>
                <div style="font-size:12px;opacity:.5;">{{ $dateObj->translatedFormat('l، d F Y') }}</div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span style="font-size:14px;">🏪</span>
                    <select onchange="location.href='?branch_id='+this.value+'&date={{ $date }}'"
                            style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:150px;">
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }} style="background:#1a1f2e;color:#fff;">{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span style="font-size:14px;">📅</span>
                    <input type="date" value="{{ $date }}"
                           onchange="location.href='?branch_id={{ $branchId }}&date='+this.value"
                           style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:140px;">
                </div>
                <a href="{{ route('company.attendance.report', ['branch_id' => $branchId]) }}"
                   class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.12);font-size:12px;font-weight:600;">
                    📊 {{ __('Report') }}
                </a>
            </div>
        </div>

        <div class="d-flex gap-3 flex-wrap">
            <div class="att-chip">
                <div class="att-chip-num" style="color:#22c55e;">{{ $stats['present'] }}</div>
                <div class="att-chip-lbl">{{ __('present_count') }}</div>
            </div>
            <div class="att-chip">
                <div class="att-chip-num" style="color:#f59e0b;">{{ $stats['late'] }}</div>
                <div class="att-chip-lbl">{{ __('late_count') }}</div>
            </div>
            <div class="att-chip">
                <div class="att-chip-num" style="color:#ef4444;">{{ $stats['absent'] }}</div>
                <div class="att-chip-lbl">{{ __('absent_count') }}</div>
            </div>
            @if($stats['on_leave'] > 0)
            <div class="att-chip">
                <div class="att-chip-num" style="color:#f093fb;">{{ $stats['on_leave'] }}</div>
                <div class="att-chip-lbl">{{ __('On leave') }}</div>
            </div>
            @endif
            <div class="att-chip">
                <div class="att-chip-num" style="color:#667eea;">{{ $stats['pct'] }}%</div>
                <div class="att-chip-lbl">{{ __('Attendance %') }}</div>
            </div>
        </div>
    </div>
</div>

@include('company.partials.flash')

{{-- Public holiday banner --}}
@if($holiday)
<div class="d-flex align-items-center gap-3 px-4 py-3 rounded-4 mb-3" style="background:rgba(240,147,251,.08);border:1.5px solid rgba(240,147,251,.25);">
    <span style="font-size:22px;">🎉</span>
    <div style="flex:1;">
        <div class="fw-bold tx-13" style="color:#f093fb;">{{ __('Public holiday') }}: {{ $holiday->name }}</div>
        <div class="tx-11 text-muted">
            {{ $holiday->start_date->format('d/m/Y') }}@if(!$holiday->start_date->isSameDay($holiday->end_date)) — {{ $holiday->end_date->format('d/m/Y') }}@endif
            · {{ $holiday->is_paid ? __('Paid holiday') : __('Unpaid holiday') }}
        </div>
    </div>
    <a href="{{ route('company.holidays.index') }}" class="btn btn-sm rounded-pill px-3" style="font-size:11px;border:1px solid rgba(240,147,251,.4);color:#f093fb;">
        {{ __('Manage holidays') }}
    </a>
</div>
@endif

{{-- Behavioral alerts: repeated lateness --}}
@if($lateAlerts->isNotEmpty())
<div class="mb-3 d-flex flex-column gap-2">
    @foreach($lateAlerts as $alert)
    <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-4" style="background:rgba(245,158,11,.08);border:1.5px solid rgba(245,158,11,.25);">
        <span style="font-size:20px;">⏰</span>
        <div style="flex:1;min-width:0;">
            <div class="fw-bold tx-13" style="color:#f59e0b;">{{ __('Repeated lateness') }}</div>
            <div class="tx-12 text-muted">
                {{ $alert->employee->localizedName() }}
                — {{ __('late :times times in the last 7 days', ['times' => $alert->times]) }}
                ({{ __('avg') }} {{ $alert->avg_late }} {{ __('min') }})
            </div>
        </div>
        <a href="{{ route('company.employees.show', $alert->employee) }}" class="btn btn-sm btn-outline-warning rounded-pill px-3" style="font-size:11px;flex-shrink:0;">
            {{ __('View profile') }}
        </a>
    </div>
    @endforeach
</div>
@endif

{{-- Employee list --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        @forelse($employeeData as $idx => $item)
        @php
            $emp      = $item['employee'];
            $record   = $item['record'];
            $schedule = $item['schedule'];
            $shifts   = $item['shifts'] ?? collect();
            $isWork   = $item['is_working_day'];
            $leave    = $item['leave'] ?? null;
            $onLeave  = $item['on_leave_all_day'] ?? false;
            $color    = $avatarColors[$emp->id % count($avatarColors)];
        @endphp
        <div class="att-row">
            <div class="att-row-main">
                {{-- Avatar --}}
                @if($emp->image)
                    <img src="{{ asset('storage/'.$emp->image) }}" class="att-avatar" style="object-fit:cover;">
                @else
                    <div class="att-avatar" style="background:{{ $color }}20;color:{{ $color }};">
                        {{ mb_substr($emp->name_ar ?: $emp->name_en, 0, 1) }}
                    </div>
                @endif

                {{-- Name + Schedule --}}
                <div style="flex:1;min-width:0;">
                    <div class="att-name">
                        <a href="{{ route('company.employees.show', $emp) }}"
                           style="color:inherit;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $emp->name_ar ?: $emp->name_en }}</a>
                        <a href="{{ route('company.employees.edit', $emp) }}" title="{{ __('Edit') }}"
                           style="opacity:.35;margin-inline-start:4px;color:inherit;">
                            <i data-feather="edit-2" style="width:11px;height:11px;"></i>
                        </a>
                    </div>
                    <div class="att-schedule">
                        @if($isWork && $shifts->isNotEmpty())
                            🕐
                            @foreach($shifts as $sh)
                                {{ \Carbon\Carbon::parse($sh->start_time)->format('h:i A') }} — {{ \Carbon\Carbon::parse($sh->end_time)->format('h:i A') }}@if(!$loop->last) <span style="opacity:.5;">·</span> @endif
                            @endforeach
                        @elseif($schedule && !$isWork)
                            {{ __('Day Off') }}
                        @else
                            {{ __('No schedule') }}
                        @endif
                        @if($leave && $leave->is_hourly)
                            <span style="color:#4facfe;">· ⏱️ {{ __('Hourly permission') }} {{ substr($leave->start_hour, 0, 5) }}–{{ substr($leave->end_hour, 0, 5) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="att-row-meta">
                {{-- Check-in time --}}
                <div class="text-center" style="min-width:60px;">
                    @if($record && $record->check_in)
                        <div class="att-time" style="color:#22c55e;">{{ $record->check_in->format('h:i A') }}</div>
                        <div style="font-size:9px;opacity:.4;">{{ __('Check In') }}</div>
                    @else
                        <div class="att-time" style="opacity:.2;">---</div>
                    @endif
                </div>

                {{-- Check-out time --}}
                <div class="text-center" style="min-width:60px;">
                    @if($record && $record->check_out)
                        <div class="att-time" style="color:#667eea;">{{ $record->check_out->format('h:i A') }}</div>
                        <div style="font-size:9px;opacity:.4;">{{ __('Check Out') }}</div>
                        @if($record->overtime_minutes > 0)
                            <div style="font-size:9px;color:#22c55e;font-weight:700;">⚡ +{{ $record->overtime_minutes }} {{ __('min') }} {{ __('overtime') }}</div>
                        @elseif($record->early_leave_minutes > 0)
                            <div style="font-size:9px;color:#f59e0b;font-weight:700;">🏃 -{{ $record->early_leave_minutes }} {{ __('min') }} {{ __('early') }}</div>
                        @endif
                    @else
                        <div class="att-time" style="opacity:.2;">---</div>
                    @endif
                </div>

                {{-- Status badge --}}
                <div class="text-center" style="min-width:70px;">
                    @if($record)
                        <span class="att-badge {{ $record->status }}">
                            {{ __($record->status === 'on_time' ? 'On Time' : ($record->status === 'late' ? 'Late' : ($record->status === 'absent' ? 'Absent' : 'Day Off'))) }}
                        </span>
                        @if($record->status === 'late' && $record->late_minutes > 0)
                            <div style="font-size:9px;color:#f59e0b;margin-top:2px;">
                                @if($record->late_minutes >= 60)
                                    {{ intdiv($record->late_minutes, 60) }} {{ __('hr') }} {{ $record->late_minutes % 60 }} {{ __('min') }}
                                @else
                                    {{ $record->late_minutes }} {{ __('min') }}
                                @endif
                            </div>
                        @endif
                        @if($item['suggested_deduction'] ?? null)
                            @php
                                $sug = $item['suggested_deduction'];
                                $sugSym = config("booksy.currencies.{$sug['currency']}.symbol", $sug['currency']);
                            @endphp
                            <button type="button" class="att-btn att-btn-absent mt-1" style="font-size:9px;padding:3px 8px;"
                                    title="{{ __('Auto-calculated from base salary') }}"
                                    onclick='openDeductModal({{ json_encode([
                                        'record_id'   => $record->id,
                                        'name'        => $emp->name_ar ?: $emp->name_en,
                                        'type'        => $sug['type'],
                                        'amount'      => (float) $sug['amount'],
                                        'symbol'      => $sugSym,
                                        'daily_rate'  => (float) ($sug['daily_rate'] ?? 0),
                                        'daily_hours' => (float) ($sug['daily_hours'] ?? 0),
                                        'hourly_rate' => (float) ($sug['hourly_rate'] ?? 0),
                                        'pay_period'  => $sug['pay_period'] ?? 'monthly',
                                        'late_min'    => (int) ($sug['late_minutes'] ?? 0),
                                    ], JSON_UNESCAPED_UNICODE) }})'>
                                💸 {{ __('Deduct') }} {{ number_format($sug['amount'], 0) }} {{ $sugSym }}
                            </button>
                        @elseif($item['already_deducted'] ?? false)
                            <div style="font-size:9px;color:#22c55e;margin-top:2px;">✓ {{ __('Deducted') }}</div>
                        @endif
                    @elseif($onLeave)
                        <span class="att-badge on_leave" title="{{ $leave->reason }}">
                            {{ ($leave->typeMeta())['icon'] }} {{ __('On leave') }}
                        </span>
                        <div style="font-size:9px;color:#f093fb;margin-top:2px;opacity:.8;">{{ __($leave->typeMeta()['label_key']) }} · {{ __('until') }} {{ $leave->end_date->format('d/m') }}</div>
                    @elseif(!$isWork)
                        <span class="att-badge day_off">{{ $holiday ? '🎉 ' . __('Holiday') : __('Day Off') }}</span>
                    @else
                        <span class="att-badge none">—</span>
                    @endif
                </div>

                {{-- Location badge --}}
                <div class="text-center" style="min-width:70px;">
                    @if($record && $record->location_status)
                        <span class="att-loc {{ $record->location_status }}" style="cursor:pointer;"
                              onclick="showMap({{ $record->check_in_lat }}, {{ $record->check_in_lng }}, {{ $branch->latitude ?? 0 }}, {{ $branch->longitude ?? 0 }}, '{{ addslashes($emp->name_ar ?: $emp->name_en) }}', {{ $record->check_in_distance }})">
                            ● {{ __($record->location_status === 'inside' ? 'Inside' : ($record->location_status === 'nearby' ? 'Nearby' : 'Outside')) }}
                        </span>
                        <div style="font-size:9px;opacity:.35;margin-top:1px;">{{ number_format($record->check_in_distance) }}m</div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-1 align-items-center" style="min-width:100px;justify-content:flex-end;">
                    @if(!$record && $onLeave)
                        <span style="font-size:10px;opacity:.4;">🏖️</span>
                    @elseif(!$record && $isWork)
                        {{-- Check-in --}}
                        <form method="POST" action="{{ route('company.attendance.store') }}" id="checkin-form-{{ $emp->id }}">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                            <input type="hidden" name="latitude" id="lat-{{ $emp->id }}">
                            <input type="hidden" name="longitude" id="lng-{{ $emp->id }}">
                            <button type="button" class="att-btn att-btn-checkin" onclick="gpsCheckin({{ $emp->id }})">
                                📍 {{ __('Check In') }}
                            </button>
                        </form>
                        <button type="button" class="att-btn att-btn-absent"
                                onclick="openAbsentModal({{ $emp->id }}, '{{ addslashes($emp->name_ar ?: $emp->name_en) }}')">
                            ✗
                        </button>
                    @elseif($record && $record->check_in && !$record->check_out)
                        {{-- Check-out --}}
                        <form method="POST" action="{{ route('company.attendance.checkout', $record) }}" id="checkout-form-{{ $record->id }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="latitude" id="co-lat-{{ $record->id }}">
                            <input type="hidden" name="longitude" id="co-lng-{{ $record->id }}">
                            <button type="button" class="att-btn att-btn-checkout" onclick="gpsCheckout({{ $record->id }})">
                                🚪 {{ __('Check Out') }}
                            </button>
                        </form>
                    @elseif(!$record && !$isWork)
                        <span style="font-size:10px;opacity:.3;">{{ $holiday ? '🎉' : __('Day Off') }}</span>
                    @endif

                    {{-- Correct record (forgot check-out, wrong time...) --}}
                    @if($record)
                    <button type="button" class="att-btn" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.55);padding:5px 9px;"
                            title="{{ __('Correct record') }}"
                            onclick="openFixModal({{ $record->id }}, '{{ addslashes($emp->name_ar ?: $emp->name_en) }}', '{{ $record->check_in?->format('H:i') }}', '{{ $record->check_out?->format('H:i') }}', '{{ addslashes($record->notes ?? '') }}')">
                        ✏️
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bk-empty py-5">
            <div class="bk-empty-ic mb-3"><i data-feather="users" style="width:24px;height:24px;"></i></div>
            <p>{{ __('No employees found for this branch.') }}</p>
        </div>
        @endforelse
    </div>
</div>
{{-- Map Modal --}}
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content" style="border-radius:18px;background:var(--card-bg, #1a1f2e);overflow:hidden;">
            <div class="modal-header border-0 pb-0 px-4 pt-3">
                <h6 class="modal-title fw-bold" id="mapTitle">📍 {{ __('Check-in Location') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="mapContainer" style="width:100%;height:300px;border-radius:14px;overflow:hidden;background:#1e293b;"></div>
                <div class="d-flex justify-content-between mt-2 px-1">
                    <span class="tx-11 text-muted" id="mapDistance"></span>
                    <span class="tx-11 text-muted" id="mapCoords"></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirm Deduction Modal --}}
<div class="modal fade" id="deductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;background:var(--card-bg, #1a1f2e);">
            <form method="POST" id="deductForm">
                @csrf
                <div class="modal-body text-center p-4">
                    <div style="font-size:42px;margin-bottom:10px;">💸</div>
                    <h6 class="fw-bold mb-1" id="deduct-title"></h6>
                    <p class="text-muted small mb-3" id="deduct-emp"></p>

                    {{-- Calculation basis --}}
                    <div class="p-3 rounded-3 mb-3 text-start tx-12" style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.18);line-height:2;">
                        <div class="fw-bold mb-1" style="color:#ef4444;">🧮 {{ __('How it is calculated') }}</div>
                        <div id="deduct-breakdown" class="text-muted"></div>
                        <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:1px dashed rgba(255,255,255,.1);">
                            <span class="fw-bold">{{ __('Deduction') }}</span>
                            <span class="fw-bold" style="color:#ef4444;font-size:15px;" id="deduct-amount"></span>
                        </div>
                    </div>

                    <div class="tx-11 text-muted mb-3" style="opacity:.7;">
                        ℹ️ {{ __('The deduction is recorded on the employee and appears automatically in this month\'s payroll.') }}
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">💸 {{ __('Confirm deduction') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Correct Record Modal --}}
<div class="modal fade" id="fixModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content" style="border-radius:16px;background:var(--card-bg, #1a1f2e);">
            <form method="POST" id="fixForm">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0 px-4 pt-3">
                    <h6 class="modal-title fw-bold">✏️ {{ __('Correct attendance record') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <p class="text-muted small mb-3" id="fix-emp-name"></p>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('Check In') }}</label>
                            <input type="time" name="check_in" id="fix-check-in" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('Check Out') }}</label>
                            <input type="time" name="check_out" id="fix-check-out" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold tx-12">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" id="fix-notes" class="form-control form-control-sm" placeholder="{{ __('e.g. forgot to check out') }}">
                    </div>
                    <div class="tx-11 text-muted mb-3" style="opacity:.7;">
                        ℹ️ {{ __('Lateness, overtime and early-leave are recalculated automatically from the shift schedule.') }}
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Absent Modal --}}
<div class="modal fade" id="absentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;background:var(--card-bg, #1a1f2e);">
            <form method="POST" action="{{ route('company.attendance.mark-absent') }}" id="absentForm">
                @csrf
                <input type="hidden" name="employee_id" id="absent-emp-id">
                <div class="modal-body text-center p-4">
                    <div style="font-size:40px;margin-bottom:12px;">❌</div>
                    <h6 class="fw-bold mb-1">{{ __('Mark as absent?') }}</h6>
                    <p class="text-muted small mb-3" id="absent-emp-name"></p>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold tx-12">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('e.g. sick leave, no show...') }}">
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">{{ __('Mark Absent') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function openDeductModal(d) {
    var periodLabel = { monthly: '{{ __("Monthly salary") }} ÷ 26', weekly: '{{ __("Weekly salary") }} ÷ 6', daily: '{{ __("Daily wage") }}' }[d.pay_period] || '';
    var fmt = function (n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 }); };
    var rows = '';

    if (d.type === 'tardiness') {
        var lateHours = Math.round(d.late_min / 60 * 100) / 100;
        rows =
            '<div class="d-flex justify-content-between"><span>📅 {{ __("Day rate") }} (' + periodLabel + ')</span><strong>' + fmt(d.daily_rate) + ' ' + d.symbol + '</strong></div>' +
            '<div class="d-flex justify-content-between"><span>🕐 {{ __("Scheduled shift hours") }}</span><strong>' + fmt(d.daily_hours) + ' {{ __("hr") }}</strong></div>' +
            '<div class="d-flex justify-content-between"><span>⏱️ {{ __("Hour rate") }} (' + fmt(d.daily_rate) + ' ÷ ' + fmt(d.daily_hours) + ')</span><strong>' + fmt(d.hourly_rate) + ' ' + d.symbol + '</strong></div>' +
            '<div class="d-flex justify-content-between"><span>⏰ {{ __("Lateness") }}</span><strong>' + d.late_min + ' {{ __("min") }} (' + fmt(lateHours) + ' {{ __("hr") }})</strong></div>' +
            '<div class="d-flex justify-content-between"><span>= ' + fmt(d.hourly_rate) + ' × ' + fmt(lateHours) + '</span><span></span></div>';
        document.getElementById('deduct-title').textContent = '{{ __("Tardiness deduction") }}';
    } else {
        rows =
            '<div class="d-flex justify-content-between"><span>📅 {{ __("Day rate") }} (' + periodLabel + ')</span><strong>' + fmt(d.daily_rate) + ' ' + d.symbol + '</strong></div>' +
            '<div class="d-flex justify-content-between"><span>🚫 {{ __("Absence") }}</span><strong>{{ __("Full day") }}</strong></div>';
        document.getElementById('deduct-title').textContent = '{{ __("Absence deduction") }}';
    }

    document.getElementById('deductForm').action = '{{ url('company/attendance') }}/' + d.record_id + '/suggest-deduction';
    document.getElementById('deduct-emp').textContent = d.name;
    document.getElementById('deduct-breakdown').innerHTML = rows;
    document.getElementById('deduct-amount').textContent = fmt(d.amount) + ' ' + d.symbol;
    new bootstrap.Modal(document.getElementById('deductModal')).show();
}

function openFixModal(recordId, empName, checkIn, checkOut, notes) {
    document.getElementById('fixForm').action = '{{ url('company/attendance') }}/' + recordId;
    document.getElementById('fix-emp-name').textContent = empName;
    document.getElementById('fix-check-in').value = checkIn || '';
    document.getElementById('fix-check-out').value = checkOut || '';
    document.getElementById('fix-notes').value = notes || '';
    new bootstrap.Modal(document.getElementById('fixModal')).show();
}

function openAbsentModal(empId, empName) {
    document.getElementById('absent-emp-id').value = empId;
    document.getElementById('absent-emp-name').textContent = empName;
    new bootstrap.Modal(document.getElementById('absentModal')).show();
}

function gpsCheckin(empId) {
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = '{{ __("Getting GPS...") }}';

    if (!navigator.geolocation) {
        alert('{{ __("GPS not supported") }}');
        btn.disabled = false;
        btn.textContent = '📍 {{ __("Check In") }}';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            document.getElementById('lat-' + empId).value = pos.coords.latitude;
            document.getElementById('lng-' + empId).value = pos.coords.longitude;
            document.getElementById('checkin-form-' + empId).submit();
        },
        function(err) {
            alert('{{ __("GPS error") }}: ' + err.message);
            btn.disabled = false;
            btn.textContent = '📍 {{ __("Check In") }}';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

function gpsCheckout(recordId) {
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = '{{ __("Getting GPS...") }}';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            document.getElementById('co-lat-' + recordId).value = pos.coords.latitude;
            document.getElementById('co-lng-' + recordId).value = pos.coords.longitude;
            document.getElementById('checkout-form-' + recordId).submit();
        },
        function(err) {
            alert('{{ __("GPS error") }}: ' + err.message);
            btn.disabled = false;
            btn.textContent = '🚪 {{ __("Check Out") }}';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

var mapInstance = null;
function showMap(empLat, empLng, brLat, brLng, empName, distance) {
    document.getElementById('mapTitle').textContent = '📍 ' + empName;
    document.getElementById('mapDistance').textContent = '{{ __("Distance") }}: ' + distance.toLocaleString() + 'm';
    document.getElementById('mapCoords').textContent = empLat.toFixed(5) + ', ' + empLng.toFixed(5);

    var modal = new bootstrap.Modal(document.getElementById('mapModal'));
    modal.show();

    setTimeout(function() {
        if (mapInstance) { mapInstance.remove(); mapInstance = null; }

        mapInstance = L.map('mapContainer').setView([empLat, empLng], 15);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap, &copy; CARTO'
        }).addTo(mapInstance);

        // Employee check-in marker (red)
        L.marker([empLat, empLng], {
            icon: L.divIcon({
                className: '',
                html: '<div style="background:#ef4444;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.4);"></div>',
                iconSize: [14, 14],
                iconAnchor: [7, 7],
            })
        }).addTo(mapInstance).bindPopup('<b>' + empName + '</b><br>📍 {{ __("Check-in Location") }}');

        // Branch marker (green)
        if (brLat && brLng) {
            L.marker([brLat, brLng], {
                icon: L.divIcon({
                    className: '',
                    html: '<div style="background:#22c55e;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.4);"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                })
            }).addTo(mapInstance).bindPopup('<b>🏪 {{ __("Branch") }}</b>');

            // 200m radius circle
            L.circle([brLat, brLng], {
                radius: 200,
                color: '#22c55e',
                fillColor: '#22c55e',
                fillOpacity: 0.08,
                weight: 2,
                dashArray: '6,4',
            }).addTo(mapInstance);

            // Line between employee and branch
            L.polyline([[empLat, empLng], [brLat, brLng]], {
                color: '#f59e0b',
                weight: 2,
                dashArray: '8,6',
                opacity: 0.6,
            }).addTo(mapInstance);

            // Fit both markers
            mapInstance.fitBounds([[empLat, empLng], [brLat, brLng]], { padding: [40, 40] });
        }
    }, 300);
}
</script>
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@endpush
