@extends('company.dashboard')

@push('company-styles')
<style>
.bk-i { width:1.05em; height:1.05em; vertical-align:-.17em; stroke-width:2.2; flex-shrink:0; }
.bk-pill-ic { display:inline-flex; align-items:center; font-size:15px; opacity:.9; padding-inline-start:6px; }
.rpt-hero h3 { color:#fff !important; }
.rpt-hero h3 .bk-i { width:1.15em; height:1.15em; margin-inline-end:4px; color:#E4C588; }

/* phones: header row disappears, each employee becomes a labelled card */
@media (max-width:767.98px) {
    .rpt-hdr { display:none; }
    .rpt-row { padding:14px 16px !important; }
    .rpt-row > .row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px 8px; margin:0; }
    .rpt-row > .row > [class*="col"] { width:auto; max-width:none; flex:none; padding:0; margin:0; }
    .rpt-row .rpt-c-name { grid-column:1 / -1; }
    .rpt-row .rpt-c-pct { grid-column:1 / -1; order:2; text-align:start !important; }
    .rpt-row .rpt-c-pct > div { justify-content:flex-start !important; }
    .rpt-row .rpt-bar-bg { flex:1; width:auto; max-width:none; height:8px; }
    .rpt-row .rpt-c-name .d-flex { gap:12px !important; }
    .rpt-row .rpt-c-name a { font-size:15px; }
    .rpt-row .rpt-c, .rpt-row .rpt-c-pct {
        background:rgba(128,128,128,.10); border-radius:12px; padding:8px 6px !important; text-align:center;
    }
    .rpt-row .rpt-c::before, .rpt-row .rpt-c-pct::before {
        content:attr(data-label); display:block; font-size:11px; font-weight:600; opacity:.75; margin-bottom:3px; letter-spacing:0;
    }
    .rpt-row .rpt-c-pct { padding:8px 12px !important; }
    .rpt-row .rpt-c-pct::before { text-align:start; }
}
.rpt-hero {
    background:linear-gradient(135deg, #5C7038 0%, #3C4B29 100%);
    border-radius:22px; padding:28px 30px 22px; margin-bottom:20px;
    color:#fff; position:relative; overflow:hidden;
}
.rpt-bar-bg { background:rgba(255,255,255,.08); border-radius:6px; height:8px; width:120px; }
.rpt-bar-fill { border-radius:6px; height:100%; transition:width .4s ease; }
</style>
@endpush

@section('content')
<div class="page-content">

@include('company.partials.team-nav')

@php $avatarColors = ['#5C7038','#5C7038','#22c55e','#ef4444','#f59e0b','#a78bfa','#fb923c','#06b6d4']; @endphp

{{-- Hero --}}
<div class="rpt-hero">
    <div class="position-relative" style="z-index:1;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;"><i data-feather="bar-chart-2" class="bk-i"></i> {{ __('Attendance Report') }}</h3>
                <div style="font-size:12px;opacity:.5;">{{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span class="bk-pill-ic"><i data-feather="map-pin" class="bk-i"></i></span>
                    <select onchange="location.href='?branch_id='+this.value+'&month={{ $month }}'"
                            style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:150px;">
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }} style="background:#1a1f2e;color:#fff;">{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span class="bk-pill-ic"><i data-feather="calendar" class="bk-i"></i></span>
                    <input type="month" value="{{ $month }}"
                           onchange="location.href='?branch_id={{ $branchId }}&month='+this.value"
                           style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:150px;">
                </div>
                <a href="{{ route('company.attendance.index', ['branch_id' => $branchId]) }}"
                   class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.12);font-size:12px;font-weight:600;">
                    <i data-feather="clipboard" class="bk-i"></i> {{ __('Daily') }}
                </a>
            </div>
        </div>
    </div>
</div>

@include('company.partials.flash')

{{-- Report table --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">

        {{-- Header --}}
        <div class="px-4 py-2 border-bottom rpt-hdr" style="border-color:rgba(255,255,255,.06)!important;">
            <div class="row gx-3 align-items-center">
                <div class="col-3"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Employee') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase">{{ __('Days') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#22c55e;">{{ __('Present') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#f59e0b;">{{ __('Late') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#ef4444;">{{ __('Absent') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#f093fb;">{{ __('Leave') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#22c55e;"><i data-feather="trending-up" class="bk-i"></i> {{ __('OT') }}</span></div>
                <div class="col-2 text-center"><span class="tx-11 fw-bold text-muted text-uppercase">{{ __('Attendance %') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase">{{ __('Avg Late') }}</span></div>
            </div>
        </div>

        @forelse($report as $r)
        @php
            $emp   = $r['employee'];
            $color = $avatarColors[$emp->id % count($avatarColors)];
            $pctColor = $r['pct'] >= 90 ? '#22c55e' : ($r['pct'] >= 70 ? '#f59e0b' : '#ef4444');
        @endphp
        <div class="px-4 py-3 rpt-row" style="border-bottom:1px solid rgba(255,255,255,.04);">
            <div class="row gx-3 align-items-center">
                <div class="col-3 rpt-c-name">
                    <div class="d-flex align-items-center gap-3">
                        @if($emp->image)
                            <img src="{{ asset('storage/'.$emp->image) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                        @else
                            <div style="width:36px;height:36px;border-radius:50%;background:{{ $color }}20;color:{{ $color }};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;">
                                {{ mb_substr($emp->name_ar ?: $emp->name_en, 0, 1) }}
                            </div>
                        @endif
                        <a href="{{ route('company.employees.show', $emp) }}" class="fw-bold tx-13"
                           style="color:inherit;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $emp->name_ar ?: $emp->name_en }}</a>
                    </div>
                </div>
                <div class="col-1 text-center fw-bold tx-13 rpt-c" data-label="{{ __('Days') }}">{{ $r['working_days'] }}</div>
                <div class="col-1 text-center fw-bold tx-13 rpt-c" data-label="{{ __('Present') }}" style="color:#22c55e;">{{ $r['present'] }}</div>
                <div class="col-1 text-center fw-bold tx-13 rpt-c" data-label="{{ __('Late') }}" style="color:#f59e0b;">{{ $r['late'] }}</div>
                <div class="col-1 text-center fw-bold tx-13 rpt-c" data-label="{{ __('Absent') }}" style="color:#ef4444;">{{ $r['absent'] }}</div>
                <div class="col-1 text-center fw-bold tx-13 rpt-c" data-label="{{ __('Leave') }}" style="color:#f093fb;">{{ $r['on_leave'] ?: '—' }}</div>
                <div class="col-1 text-center rpt-c" data-label="{{ __('OT') }}">
                    @if($r['overtime_min'] > 0)
                        <span class="tx-12 fw-bold" style="color:#22c55e;">{{ round($r['overtime_min'] / 60, 1) }} {{ __('hr') }}</span>
                    @else
                        <span class="tx-12" style="opacity:.3;">—</span>
                    @endif
                </div>
                <div class="col-2 text-center rpt-c-pct" data-label="{{ __('Attendance %') }}">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <div class="rpt-bar-bg">
                            <div class="rpt-bar-fill" style="width:{{ $r['pct'] }}%;background:{{ $pctColor }};"></div>
                        </div>
                        <span class="fw-bold tx-12" style="color:{{ $pctColor }};">{{ $r['pct'] }}%</span>
                    </div>
                </div>
                <div class="col-1 text-center rpt-c" data-label="{{ __('Avg Late') }}">
                    @if($r['avg_late'] > 0)
                        <span class="tx-12 fw-bold" style="color:#f59e0b;">{{ $r['avg_late'] }} {{ __('min') }}</span>
                    @else
                        <span class="tx-12" style="opacity:.3;">—</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bk-empty py-5">
            <div class="bk-empty-ic mb-3"><i data-feather="bar-chart-2" style="width:24px;height:24px;"></i></div>
            <p>{{ __('No attendance data for this period.') }}</p>
        </div>
        @endforelse
    </div>
</div>

{{-- How the numbers are computed --}}
<div class="tx-11 text-muted mt-3 px-2" style="line-height:2;opacity:.75;">
    <i data-feather="info" class="bk-i"></i> <strong>{{ __('How these numbers are computed') }}:</strong>
    <span style="opacity:.9;">
        {{ __('Days = scheduled working days elapsed this month, excluding public holidays and approved leave days.') }}
        · {{ __('Absent = days marked absent + past working days with no check-in.') }}
        · {{ __('Attendance % = present days ÷ working days.') }}
        · {{ __('Avg late = average lateness minutes on late days only.') }}
        · {{ __('OT = total overtime hours recorded after shift end.') }}
    </span>
</div>
</div>
@endsection
