@extends('company.dashboard')

@push('company-styles')
<style>
.cal-hero {
    background: linear-gradient(135deg, #4facfe 0%, #667eea 100%);
    border-radius: 20px; padding: 24px 28px; margin-bottom: 20px;
    color: #fff; position: relative; overflow: hidden;
}
.cal-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
[dir="rtl"] .cal-hero::before { right: auto; left: -50px; }

.month-nav { display: flex; align-items: center; gap: 8px; }
.month-nav a {
    width: 32px; height: 32px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center;
    background: rgba(255,255,255,.15); color: #fff; text-decoration: none;
    transition: background .15s;
}
.month-nav a:hover { background: rgba(255,255,255,.3); }

.cal-grid {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;
}
.cal-dow {
    text-align: center; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px; opacity: .4;
    padding: 6px 0 10px;
}
.cal-cell {
    min-height: 92px; border-radius: 12px; padding: 7px 8px;
    background: rgba(255,255,255,.025); border: 1px solid rgba(255,255,255,.05);
    display: flex; flex-direction: column; gap: 4px; overflow: hidden;
}
.bk-theme-light .cal-cell { background: #fafbfc; border-color: #eef0f3; }
.cal-cell.other-month { opacity: .3; }
.cal-cell.today { border-color: rgba(102,126,234,.55); background: rgba(102,126,234,.07); }
.cal-cell.weekend-off { background: rgba(255,255,255,.012); }
.cal-daynum { font-size: 12px; font-weight: 800; opacity: .55; }
.cal-cell.today .cal-daynum { color: #a5b4fd; opacity: 1; }

.cal-chip {
    display: flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 700; line-height: 1.2;
    padding: 3px 7px; border-radius: 7px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    text-decoration: none; transition: transform .1s;
}
.cal-chip:hover { transform: scale(1.03); }
.cal-chip.pending { border: 1.5px dashed currentColor; background: transparent !important; }

.cal-more { font-size: 10px; font-weight: 700; opacity: .45; padding-inline-start: 4px; }

.cal-legend {
    display: flex; gap: 14px; flex-wrap: wrap; align-items: center;
    font-size: 11px; font-weight: 600; opacity: .8;
}
.cal-legend .dot { width: 9px; height: 9px; border-radius: 3px; display: inline-block; margin-inline-end: 4px; }

@media (max-width: 768px) {
    .cal-grid { gap: 3px; }
    .cal-cell { min-height: 64px; padding: 4px 5px; border-radius: 8px; }
    .cal-chip { font-size: 8px; padding: 2px 4px; }
    .cal-daynum { font-size: 10px; }
}
</style>
@endpush

@section('content')
<div class="page-content">

    @include('company.partials.team-nav')

    @php
        $monthName = $monthStart->translatedFormat('F Y');
        $prev = $monthStart->copy()->subMonth();
        $next = $monthStart->copy()->addMonth();
        $onLeaveToday = collect($byDay[today()->toDateString()] ?? [])->where('status', 'approved')->count();
        $types = \App\Models\EmployeeLeave::LEAVE_TYPES;
    @endphp

    {{-- Hero --}}
    <div class="cal-hero bk-a1">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">🗓️ {{ __('Team Calendar') }}</h3>
                <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:13px;">
                    @if($onLeaveToday > 0)
                        <span style="color:#fbbf24;font-weight:700;">{{ $onLeaveToday }}</span> {{ __('employee(s) on leave today') }}
                    @else
                        {{ __('Everyone is available today') }} ✨
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                {{-- Branch filter --}}
                <div class="d-flex align-items-center gap-1" style="background:rgba(255,255,255,.12);border-radius:20px;padding:2px 12px 2px 4px;">
                    <span style="font-size:14px;">🏪</span>
                    <select onchange="location.href='{{ route('company.team-calendar') }}?month={{ $month }}&year={{ $year }}&branch_id='+this.value"
                            style="background:transparent;border:none;color:#fff;font-size:12px;font-weight:600;outline:none;cursor:pointer;max-width:150px;">
                        <option value="" style="background:#1a1f2e;color:#fff;" {{ !$branchId ? 'selected' : '' }}>{{ __('All branches') }}</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" style="background:#1a1f2e;color:#fff;" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Month nav --}}
                <div class="month-nav">
                    <a href="{{ route('company.team-calendar', ['month' => $prev->month, 'year' => $prev->year, 'branch_id' => $branchId]) }}">
                        <i data-feather="chevron-right" style="width:14px;height:14px;"></i>
                    </a>
                    <span style="font-weight:700;font-size:15px;min-width:130px;text-align:center;">{{ $monthName }}</span>
                    <a href="{{ route('company.team-calendar', ['month' => $next->month, 'year' => $next->year, 'branch_id' => $branchId]) }}">
                        <i data-feather="chevron-left" style="width:14px;height:14px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- Legend --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 px-1">
        <div class="cal-legend">
            @foreach($types as $key => $meta)
            <span><span class="dot" style="background:{{ $meta['color'] }};"></span>{{ __($meta['label_key']) }}</span>
            @endforeach
            <span><span class="dot" style="background:transparent;border:1.5px dashed rgba(255,255,255,.5);"></span>{{ __('Pending') }}</span>
        </div>
        <a href="{{ route('company.employee-leaves.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size:11px;">
            {{ __('Manage requests') }} ←
        </a>
    </div>

    {{-- Calendar --}}
    <div class="card border-0 shadow-sm rounded-4 bk-a2">
        <div class="card-body p-3">
            @php
                $locale   = app()->getLocale();
                $dayNames = \App\Models\EmployeeWorkingHour::$dayNames;
                $gridStart = $monthStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                $gridEnd   = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
            @endphp

            <div class="cal-grid">
                @foreach($dayNames as $names)
                    <div class="cal-dow">{{ $locale === 'ar' ? $names['ar'] : $names['en'] }}</div>
                @endforeach

                @for($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay())
                @php
                    $key       = $d->toDateString();
                    $dayLeaves = collect($byDay[$key] ?? []);
                    $isToday   = $d->isToday();
                    $inMonth   = $d->month === $monthStart->month;
                    $shown     = $dayLeaves->take(3);
                @endphp
                <div class="cal-cell {{ $isToday ? 'today' : '' }} {{ !$inMonth ? 'other-month' : '' }}">
                    <div class="cal-daynum">{{ $d->day }}</div>
                    @foreach($shown as $leave)
                    @php $meta = $leave->typeMeta(); @endphp
                    <a href="{{ route('company.employees.show', $leave->employee) }}"
                       class="cal-chip {{ $leave->status === 'pending' ? 'pending' : '' }}"
                       style="background:{{ $meta['color'] }}22;color:{{ $meta['color'] }};"
                       title="{{ $leave->employee->localizedName() }} — {{ __($meta['label_key']) }} {{ $leave->is_hourly ? '(' . substr($leave->start_hour, 0, 5) . '–' . substr($leave->end_hour, 0, 5) . ')' : '(' . $leave->start_date->format('d/m') . ' → ' . $leave->end_date->format('d/m') . ')' }}{{ $leave->status === 'pending' ? ' · ' . __('Pending') : '' }}">
                        {{ $leave->is_hourly ? '⏱️' : $meta['icon'] }} {{ $leave->employee->localizedName() }}
                    </a>
                    @endforeach
                    @if($dayLeaves->count() > 3)
                    <span class="cal-more">+{{ $dayLeaves->count() - 3 }}</span>
                    @endif
                </div>
                @endfor
            </div>
        </div>
    </div>

    @if($leaves->isEmpty())
    <div class="text-center py-4" style="opacity:.45;">
        <div style="font-size:28px;margin-bottom:6px;">🏖️</div>
        <div style="font-size:13px;">{{ __('No leaves scheduled this month.') }}</div>
    </div>
    @endif

</div>
@endsection
