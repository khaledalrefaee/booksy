@extends('company.dashboard')

@section('content')
@php
    $totalAppt     = (int)($stats['appointments_total']    ?? 0);
    $pendingAppt   = (int)($stats['appointments_pending']  ?? 0);
    $confirmedAppt = (int)($stats['appointments_confirmed'] ?? 0);
    $completedAppt = (int)($stats['appointments_completed'] ?? 0);
    $branchesCount = (int)($stats['branches']              ?? 0);
    $servicesCount = (int)($stats['services']              ?? 0);
    $waitlistWaiting=(int)($stats['waitlist_waiting']      ?? 0);
    $isAr = app()->getLocale() === 'ar';
@endphp

<div class="page-content">

{{-- ════ HEADER ════ --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 grid-margin bk-a1">
    <div>
        <h4 class="mb-1" style="font-weight:800;">{{ __('Dashboard') }}</h4>
        <p class="text-muted tx-13 mb-0">
            <i data-feather="briefcase" style="width:13px;height:13px;margin-right:3px;"></i>
            {{ $company->localizedName() }}
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('front.show', $company) }}" target="_blank"
           class="btn btn-sm btn-outline-secondary rounded-pill d-flex align-items-center gap-1">
            <i data-feather="external-link" style="width:13px;height:13px;"></i>
            {{ __('Public page') }}
        </a>
        <a href="{{ route('company.appointments.create') }}"
           class="btn btn-primary rounded-pill px-4 fw-bold d-flex align-items-center gap-1">
            <i data-feather="plus" style="width:14px;height:14px;"></i>
            {{ __('New booking') }}
        </a>
    </div>
</div>

@include('company.partials.flash')

{{-- ════ STAT CARDS — horizontal ════ --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-3 bk-a1">
        <div class="bk-stat" data-accent="gold">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-gold">
                    <i class="feather icon-calendar bk-ic"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Total Bookings') }}</div>
                    <div class="bk-stat-sub">{{ $pendingAppt }} {{ __('pending') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $totalAppt }}">{{ number_format($totalAppt) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:100%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-lg-3 bk-a2">
        <div class="bk-stat" data-accent="orange">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-orange">
                    <i class="feather icon-clock bk-ic"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Pending') }}</div>
                    <div class="bk-stat-sub">{{ __('awaiting action') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $pendingAppt }}"
                 style="{{ $pendingAppt > 0 ? 'animation:bk-pulse 2s ease infinite;' : '' }}">
                {{ number_format($pendingAppt) }}
            </div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ $totalAppt>0?round($pendingAppt/$totalAppt*100):0 }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-lg-3 bk-a3">
        <div class="bk-stat" data-accent="green">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-green">
                    <i class="feather icon-check-circle bk-ic"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Confirmed') }}</div>
                    <div class="bk-stat-sub">{{ $completedAppt }} {{ __('completed') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $confirmedAppt }}">{{ number_format($confirmedAppt) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ $totalAppt>0?round($confirmedAppt/$totalAppt*100):0 }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-lg-3 bk-a4">
        <div class="bk-stat" data-accent="blue">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-blue">
                    <i class="feather icon-map-pin bk-ic"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Branches') }}</div>
                    <div class="bk-stat-sub">{{ $servicesCount }} {{ __('services') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $branchesCount }}">{{ number_format($branchesCount) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($branchesCount*15,100) }}%;"></div></div>
        </div>
    </div>

</div>

{{-- ════ QUICK ACTIONS ════ --}}
<div class="card shadow-sm mb-4 bk-a2">
    <div class="card-body py-3">
        <div class="bk-sh mb-3">
            <span class="bk-sh-title">{{ __('Quick Actions') }}</span>
        </div>
        <div class="bk-qa-grid">
            <a href="{{ route('company.appointments.create') }}" class="bk-qa">
                <div class="bk-qa-ic"><i class="feather icon-plus-circle bk-ic-qa"></i></div>
                <span class="bk-qa-lbl">{{ __('New booking') }}</span>
            </a>
            @if($company->branches->first())
            <a href="{{ route('company.branches.employees.index', $company->branches->first()) }}" class="bk-qa">
                <div class="bk-qa-ic"><i class="feather icon-user-plus bk-ic-qa"></i></div>
                <span class="bk-qa-lbl">{{ __('Add employee') }}</span>
            </a>
            <a href="{{ route('company.branches.services.create', $company->branches->first()) }}" class="bk-qa">
                <div class="bk-qa-ic"><i class="feather icon-scissors bk-ic-qa"></i></div>
                <span class="bk-qa-lbl">{{ __('Add service') }}</span>
            </a>
            @endif
            <a href="{{ route('company.branches.create') }}" class="bk-qa">
                <div class="bk-qa-ic"><i class="feather icon-map-pin bk-ic-qa"></i></div>
                <span class="bk-qa-lbl">{{ __('New branch') }}</span>
            </a>
            <a href="{{ route('company.appointments.index', ['status'=>'pending']) }}" class="bk-qa">
                <div class="bk-qa-ic" style="position:relative;">
                    <i class="feather icon-bell bk-ic-qa"></i>
                    @if($pendingAppt > 0)<span class="bk-qa-dot"></span>@endif
                </div>
                <span class="bk-qa-lbl">
                    {{ __('Pending') }}@if($pendingAppt > 0) ({{ $pendingAppt }})@endif
                </span>
            </a>
            <a href="{{ route('company.service-categories.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i class="feather icon-tag bk-ic-qa"></i></div>
                <span class="bk-qa-lbl">{{ __('Categories') }}</span>
            </a>
        </div>
    </div>
</div>

{{-- ════ CHARTS ════ --}}
<div class="row g-4">

    {{-- Activity Chart (large) + filter --}}
    <div class="col-lg-8 bk-a3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Booking Activity') }}</span>
                    {{-- Filter tabs --}}
                    <div class="bk-filter-tabs" id="bk-chart-filter">
                        <button class="bk-filter-tab" data-range="today">{{ __('Today') }}</button>
                        <button class="bk-filter-tab" data-range="week">{{ __('Week') }}</button>
                        <button class="bk-filter-tab active" data-range="month">{{ __('Month') }}</button>
                        <button class="bk-filter-tab" data-range="year">{{ __('Year') }}</button>
                    </div>
                </div>
                <div id="bk-activity-chart" style="min-height:280px;"></div>
            </div>
        </div>
    </div>

    {{-- Pie / Donut --}}
    <div class="col-lg-4 bk-a4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('By Status') }}</span>
                </div>
                <div id="storageChart"></div>
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="rounded-3 p-2 text-center"
                             style="background:rgba(244,166,66,.08);border:1px solid rgba(244,166,66,.15);">
                            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;color:#f4a642;letter-spacing:1px;">{{ __('Pending') }}</div>
                            <div style="font-size:1.4rem;font-weight:900;color:#f4a642;font-family:'Poppins',sans-serif;">{{ $pendingAppt }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-3 p-2 text-center"
                             style="background:rgba(43,207,126,.07);border:1px solid rgba(43,207,126,.15);">
                            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;color:#2bcf7e;letter-spacing:1px;">{{ __('Waitlist') }}</div>
                            <div style="font-size:1.4rem;font-weight:900;color:#2bcf7e;font-family:'Poppins',sans-serif;">{{ $waitlistWaiting }}</div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('company.appointments.index') }}"
                   class="btn btn-primary w-100 rounded-pill mt-3 fw-bold">
                    {{ __('Manage Appointments') }}
                </a>
            </div>
        </div>
    </div>

</div>

{{-- ════ RECENT APPOINTMENTS ════ --}}
<div class="row g-4 mt-0">
    <div class="col-12 bk-a5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Recent Appointments') }}</span>
                    <a href="{{ route('company.appointments.index') }}" class="bk-sh-link">
                        {{ __('View all') }} <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                    </a>
                </div>

                @if($recentAppointments->count())
                {{-- Color bar --}}
                @php
                    $byStatus = $recentAppointments->countBy('status');
                    $scColors = ['pending'=>'#f4a642','confirmed'=>'#2bcf7e','completed'=>'#3dbbd4','cancelled'=>'rgba(255,255,255,.08)','rejected'=>'rgba(255,255,255,.08)','no_show'=>'rgba(255,255,255,.08)'];
                @endphp
                <div class="bk-color-bar">
                    @foreach($byStatus as $st => $cnt)
                    <span style="flex:{{ $cnt }};background:{{ $scColors[$st] ?? '#888' }};"></span>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Service') }}</th>
                                <th>{{ __('Date & Time') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAppointments as $row)
                            @php
                                $initial = strtoupper(substr($row->customer?->name ?? 'C', 0, 1));
                                $icColors = ['pending'=>'#f4a642','confirmed'=>'#2bcf7e','completed'=>'#3dbbd4','cancelled'=>'#555','rejected'=>'#555','no_show'=>'#555'];
                                $ic = $icColors[$row->status] ?? '#888';
                                $bc = 'bk-badge-'.($row->status ?? 'cancelled');
                            @endphp
                            <tr class="bk-table-row" onclick="location.href='{{ route('company.appointments.show', $row) }}'">
                                <td class="text-muted tx-12 fw-semibold">#{{ $row->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:{{ $ic }}22;color:{{ $ic }};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0;">{{ $initial }}</div>
                                        <span class="fw-semibold tx-13">{{ $row->customer?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="tx-13 text-muted">
                                    <div>{{ $row->service?->localizedName() ?? '—' }}</div>
                                    @if($row->branch)<small class="opacity-50">{{ $row->branch->localizedName() }}</small>@endif
                                </td>
                                <td class="text-muted tx-12 text-nowrap">
                                    <div>{{ $row->start_time?->format('d M Y') }}</div>
                                    <small class="opacity-50">{{ $row->start_time?->format('H:i') }}</small>
                                </td>
                                <td><span class="bk-badge {{ $bc }}">{{ __($row->status ?? '') }}</span></td>
                                <td>
                                    <a href="{{ route('company.appointments.show', $row) }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill px-3 tx-11"
                                       onclick="event.stopPropagation()">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="bk-empty">
                    <div class="bk-empty-ic"><i data-feather="calendar" style="width:26px;height:26px;"></i></div>
                    <p>{{ __('No appointments yet.') }}</p>
                    <a href="{{ route('company.appointments.create') }}" class="btn btn-primary rounded-pill px-4">
                        {{ __('Create first booking') }}
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

</div>{{-- .page-content --}}

@push('company-after-template')
@php
    $booksyPayload = [
        'theme'  => request()->cookie('company_theme','dark'),
        'rtl'    => $isAr,
        'charts' => $chartData,
        'labels' => [
            'appointments' => __('Appointments'),
            'revenue'      => __('Revenue'),
            'count'        => __('Count'),
            'total'        => __('Total'),
            'noData'       => __('No data yet.'),
            'currency'     => config('app.currency','SAR'),
        ],
    ];
@endphp
<script>window.booksyDashboard = @json($booksyPayload);</script>
<script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function(){
'use strict';
var p = window.booksyDashboard || {};
var isDark = p.theme !== 'light';
var isRtl  = p.rtl === true;
var charts  = p.charts || {};
var labels  = p.labels || {};

var gold = '#C9A227';
var c = isDark
    ? {text:'#b8c3d9', grid:'rgba(255,255,255,.06)', card:'#0c1427', muted:'#7987a1'}
    : {text:'#333',    grid:'rgba(0,0,0,.07)',       card:'#fff',    muted:'#888'};

/* ── Counter animation ── */
function runCounters(){
    document.querySelectorAll('.bk-counter[data-target]').forEach(function(el){
        var to = parseInt(el.dataset.target)||0;
        if(!to) return;
        var cur = 0, step = to/(1400/16);
        var t = setInterval(function(){
            cur = Math.min(cur+step, to);
            el.textContent = Math.floor(cur).toLocaleString();
            if(cur>=to) clearInterval(t);
        }, 16);
    });
}
setTimeout(runCounters, 250);

/* ── Progress bars ── */
document.querySelectorAll('.bk-stat-bar-fill').forEach(function(el){
    var w = el.style.width; el.style.width='0';
    setTimeout(function(){ el.style.width=w; }, 400);
});

/* ── Activity chart (filterable bar) ── */
var activityChart = null;

function getSeriesForRange(range){
    // map tab name → chartData key
    var key = { today:'today', week:'week', month:'month', year:'year' }[range] || 'month';
    var d = charts[key] || charts.month || charts.daily || {};
    return {
        labels : d.labels || [],
        data   : d.total  || [],
        name   : labels.appointments || 'Appointments'
    };
}

function renderActivityChart(range){
    range = range || 'month';
    var s    = getSeriesForRange(range);
    var node = document.getElementById('bk-activity-chart');
    if (!node || typeof ApexCharts === 'undefined') return;

    var rotateAlways = s.labels.length > 8;

    if (activityChart) {
        activityChart.updateOptions({
            series  : [{ name: s.name, data: s.data }],
            xaxis   : {
                categories  : s.labels,
                labels      : {
                    rotate      : isRtl ? 30 : -30,
                    rotateAlways: rotateAlways,
                    style       : { fontSize:'11px', colors: c.muted }
                }
            },
            noData  : { text: labels.noData || 'No data yet.' }
        }, true, true);
        return;
    }

    activityChart = new ApexCharts(node, {
        chart:{
            type:'bar', height:280, background:'transparent',
            toolbar:{show:false}, fontFamily:"'Roboto',sans-serif",
            foreColor: c.text,
            animations:{ enabled:true, easing:'easeinout', speed:500 }
        },
        plotOptions:{ bar:{ columnWidth:'55%', borderRadius:4 } },
        dataLabels:{ enabled:false },
        colors:[gold],
        series:[{ name: s.name, data: s.data }],
        xaxis:{
            categories: s.labels,
            labels:{
                style:{ fontSize:'11px', colors:c.muted },
                rotate: isRtl ? 30 : -30,
                rotateAlways: rotateAlways
            },
            axisBorder:{ color:c.grid },
            axisTicks :{ color:c.grid }
        },
        yaxis:{
            min:0, forceNiceScale:true,
            labels:{ formatter:function(v){ return Math.round(v); }, style:{ colors:c.muted } }
        },
        grid :{ borderColor:c.grid, xaxis:{ lines:{ show:false } } },
        noData:{ text: labels.noData || 'No data yet.', style:{ color:c.muted } },
        tooltip:{ theme: isDark?'dark':'light' },
        theme  :{ mode: isDark?'dark':'light' },
    });
    activityChart.render();
}

/* ── Filter tabs ── */
document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(b){
            b.classList.remove('active');
        });
        this.classList.add('active');
        renderActivityChart(this.dataset.range);
    });
});

/* ── Donut (status) ── */
function renderDonut(){
    var node = document.getElementById('storageChart');
    if(!node || typeof ApexCharts === 'undefined') return;
    var st = charts.by_status || {};
    var pending   = st.pending   || 0;
    var confirmed = st.confirmed || 0;
    var completed = st.completed || 0;
    var other     = (st.cancelled || 0) + (st.rejected || 0) + (st.no_show || 0);
    var realTotal = pending + confirmed + completed + other;

    /* If truly empty show a neutral placeholder */
    var isEmpty   = realTotal === 0;
    var series    = isEmpty ? [1] : [pending, confirmed, completed, other];
    var clrs      = isEmpty
        ? [isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.07)']
        : ['#f4a642','#2bcf7e','#3dbbd4', isDark ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.08)'];
    var lbls      = isEmpty ? ['—'] : ['Pending','Confirmed','Completed','Other'];

    new ApexCharts(node, {
        chart:{
            type:'donut', height:200,
            background:'transparent', toolbar:{show:false}
        },
        series  : series,
        labels  : lbls,
        colors  : clrs,
        legend  : { show:false },
        plotOptions:{ pie:{ donut:{
            size:'68%',
            labels:{
                show: !isEmpty,
                total:{
                    show     : true,
                    label    : 'Total',
                    color    : c.muted,
                    fontSize : '11px',
                    formatter: function(){ return isEmpty ? '0' : String(realTotal); }
                },
                value:{
                    color    : isDark ? '#ffffff' : '#333',
                    fontSize : '18px',
                    fontWeight: 700,
                    formatter: function(v){ return String(parseInt(v)||0); }
                }
            }
        }}},
        dataLabels:{ enabled:false },
        stroke    :{ width:0 },
        theme     :{ mode: isDark?'dark':'light' },
        tooltip   :{ theme: isDark?'dark':'light',
                     y:{ formatter:function(v){ return isEmpty?'0':String(v); } } },
    }).render();
}

renderActivityChart('month');
renderDonut();

/* Re-run feather after charts render (icons inside cards need a second pass) */
setTimeout(function(){
    if(typeof feather !== 'undefined') feather.replace();
}, 50);

})();
</script>
@endpush
@endsection
