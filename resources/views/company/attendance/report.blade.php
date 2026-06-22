@extends('company.dashboard')

@push('company-styles')
<style>
.rpt-hero {
    background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);
    border-radius:22px; padding:28px 30px 22px; margin-bottom:20px;
    color:#fff; position:relative; overflow:hidden;
}
.rpt-bar-bg { background:rgba(255,255,255,.08); border-radius:6px; height:8px; width:120px; }
.rpt-bar-fill { border-radius:6px; height:100%; transition:width .4s ease; }
</style>
@endpush

@section('content')
<div class="page-content">

@php $avatarColors = ['#C9A227','#667eea','#22c55e','#ef4444','#f59e0b','#a78bfa','#fb923c','#06b6d4']; @endphp

{{-- Hero --}}
<div class="rpt-hero">
    <div class="position-relative" style="z-index:1;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">📊 {{ __('Attendance Report') }}</h3>
                <div style="font-size:12px;opacity:.5;">{{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span style="font-size:14px;">🏪</span>
                    <select onchange="location.href='?branch_id='+this.value+'&month={{ $month }}'"
                            style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:150px;">
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }} style="background:#1a1f2e;color:#fff;">{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span style="font-size:14px;">📅</span>
                    <input type="month" value="{{ $month }}"
                           onchange="location.href='?branch_id={{ $branchId }}&month='+this.value"
                           style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:150px;">
                </div>
                <a href="{{ route('company.attendance.index', ['branch_id' => $branchId]) }}"
                   class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.12);font-size:12px;font-weight:600;">
                    📋 {{ __('Daily') }}
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
        <div class="px-4 py-2 border-bottom" style="border-color:rgba(255,255,255,.06)!important;">
            <div class="row gx-3 align-items-center">
                <div class="col-3"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Employee') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase">{{ __('Days') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#22c55e;">{{ __('Present') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#f59e0b;">{{ __('Late') }}</span></div>
                <div class="col-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="color:#ef4444;">{{ __('Absent') }}</span></div>
                <div class="col-2 text-center"><span class="tx-11 fw-bold text-muted text-uppercase">{{ __('Attendance %') }}</span></div>
                <div class="col-2 text-center"><span class="tx-11 fw-bold text-muted text-uppercase">{{ __('Avg Late') }}</span></div>
            </div>
        </div>

        @forelse($report as $r)
        @php
            $emp   = $r['employee'];
            $color = $avatarColors[$emp->id % count($avatarColors)];
            $pctColor = $r['pct'] >= 90 ? '#22c55e' : ($r['pct'] >= 70 ? '#f59e0b' : '#ef4444');
        @endphp
        <div class="px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.04);">
            <div class="row gx-3 align-items-center">
                <div class="col-3">
                    <div class="d-flex align-items-center gap-3">
                        @if($emp->image)
                            <img src="{{ asset('storage/'.$emp->image) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                        @else
                            <div style="width:36px;height:36px;border-radius:50%;background:{{ $color }}20;color:{{ $color }};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;">
                                {{ mb_substr($emp->name_ar ?: $emp->name_en, 0, 1) }}
                            </div>
                        @endif
                        <div class="fw-bold tx-13">{{ $emp->name_ar ?: $emp->name_en }}</div>
                    </div>
                </div>
                <div class="col-1 text-center fw-bold tx-13">{{ $r['working_days'] }}</div>
                <div class="col-1 text-center fw-bold tx-13" style="color:#22c55e;">{{ $r['present'] }}</div>
                <div class="col-1 text-center fw-bold tx-13" style="color:#f59e0b;">{{ $r['late'] }}</div>
                <div class="col-1 text-center fw-bold tx-13" style="color:#ef4444;">{{ $r['absent'] }}</div>
                <div class="col-2 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <div class="rpt-bar-bg">
                            <div class="rpt-bar-fill" style="width:{{ $r['pct'] }}%;background:{{ $pctColor }};"></div>
                        </div>
                        <span class="fw-bold tx-12" style="color:{{ $pctColor }};">{{ $r['pct'] }}%</span>
                    </div>
                </div>
                <div class="col-2 text-center">
                    @if($r['avg_late'] >= 60)
                        <span class="tx-12 fw-bold" style="color:#f59e0b;">{{ intdiv($r['avg_late'], 60) }} {{ __('hr') }} {{ $r['avg_late'] % 60 }} {{ __('min') }}</span>
                    @elseif($r['avg_late'] > 0)
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
</div>
@endsection
