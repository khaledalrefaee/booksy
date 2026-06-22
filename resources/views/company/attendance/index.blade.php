@extends('company.dashboard')

@push('company-styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
</style>
@endpush

@section('content')
<div class="page-content">

@php $avatarColors = ['#C9A227','#667eea','#22c55e','#ef4444','#f59e0b','#a78bfa','#fb923c','#06b6d4']; @endphp

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
            <div class="att-chip">
                <div class="att-chip-num" style="color:#667eea;">{{ $stats['pct'] }}%</div>
                <div class="att-chip-lbl">{{ __('Attendance %') }}</div>
            </div>
        </div>
    </div>
</div>

@include('company.partials.flash')

{{-- Employee list --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        @forelse($employeeData as $idx => $item)
        @php
            $emp      = $item['employee'];
            $record   = $item['record'];
            $schedule = $item['schedule'];
            $isWork   = $item['is_working_day'];
            $color    = $avatarColors[$emp->id % count($avatarColors)];
        @endphp
        <div class="att-row">
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
                <div class="att-name">{{ $emp->name_ar ?: $emp->name_en }}</div>
                <div class="att-schedule">
                    @if($schedule && $isWork)
                        🕐 {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} — {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                    @elseif($schedule && !$isWork)
                        {{ __('Day Off') }}
                    @else
                        {{ __('No schedule') }}
                    @endif
                </div>
            </div>

            {{-- Check-in time --}}
            <div class="text-center" style="min-width:70px;">
                @if($record && $record->check_in)
                    <div class="att-time" style="color:#22c55e;">{{ $record->check_in->format('h:i A') }}</div>
                    <div style="font-size:9px;opacity:.4;">{{ __('Check In') }}</div>
                @else
                    <div class="att-time" style="opacity:.2;">---</div>
                @endif
            </div>

            {{-- Check-out time --}}
            <div class="text-center" style="min-width:70px;">
                @if($record && $record->check_out)
                    <div class="att-time" style="color:#667eea;">{{ $record->check_out->format('h:i A') }}</div>
                    <div style="font-size:9px;opacity:.4;">{{ __('Check Out') }}</div>
                @else
                    <div class="att-time" style="opacity:.2;">---</div>
                @endif
            </div>

            {{-- Status badge --}}
            <div style="min-width:80px;text-align:center;">
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
                @elseif(!$isWork)
                    <span class="att-badge day_off">{{ __('Day Off') }}</span>
                @else
                    <span class="att-badge none">—</span>
                @endif
            </div>

            {{-- Location badge --}}
            <div style="min-width:80px;text-align:center;">
                @if($record && $record->location_status)
                    <span class="att-loc {{ $record->location_status }}" style="cursor:pointer;"
                          onclick="showMap({{ $record->check_in_lat }}, {{ $record->check_in_lng }}, {{ $branch->latitude ?? 0 }}, {{ $branch->longitude ?? 0 }}, '{{ addslashes($emp->name_ar ?: $emp->name_en) }}', {{ $record->check_in_distance }})">
                        ● {{ __($record->location_status === 'inside' ? 'Inside' : ($record->location_status === 'nearby' ? 'Nearby' : 'Outside')) }}
                    </span>
                    <div style="font-size:9px;opacity:.35;margin-top:1px;">{{ number_format($record->check_in_distance) }}m</div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-1" style="min-width:120px;justify-content:flex-end;">
                @if(!$record && $isWork)
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
                    <span style="font-size:10px;opacity:.3;">{{ __('Day Off') }}</span>
                @endif
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

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
