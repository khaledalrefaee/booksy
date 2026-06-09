@extends('owner.dashboard')

@section('content')
@php
    $totalAppt      = (int)($stats['appointments_total']    ?? 0);
    $pendingAppt    = (int)($stats['appointments_pending']  ?? 0);
    $companiesCount = (int)($stats['companies']             ?? 0);
    $branchesCount  = (int)($stats['branches']              ?? 0);
    $servicesCount  = (int)($stats['services']              ?? 0);
    $waitlistWaiting= (int)($stats['waitlist_waiting']      ?? 0);
    $isAr = app()->getLocale() === 'ar';
@endphp

<div class="page-content">

{{-- ════ HERO HEADER ════ --}}
<div class="bk-hero bk-a1">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="bk-hero-title">{{ __('Platform') }} <span>{{ __('Dashboard') }}</span></h2>
            <p class="bk-hero-sub">
                <i data-feather="activity" style="width:13px;height:13px;display:inline;margin-right:5px;"></i>
                {{ __('Manage all companies, branches, and bookings from one place.') }}
            </p>
        </div>
        <div class="bk-hero-actions">
            <a href="{{ route('owner.companies.index') }}"
               class="bk-navbar-action bk-navbar-action-ghost d-flex align-items-center gap-2">
                <i data-feather="briefcase" style="width:14px;height:14px;"></i>
                {{ __('Companies') }}
            </a>
            <a href="{{ route('owner.appointments.index') }}"
               class="bk-navbar-action bk-navbar-action-primary d-flex align-items-center gap-2">
                <i data-feather="calendar" style="width:14px;height:14px;"></i>
                {{ __('Appointments') }}
            </a>
        </div>
    </div>
</div>

@include('owner.partials.flash')

{{-- ════ STAT CARDS — horizontal style ════ --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-xl bk-a1">
        <div class="bk-stat" data-accent="gold">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-gold">
                    <i data-feather="calendar" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Total Bookings') }}</div>
                    <div class="bk-stat-sub">{{ __('all time') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $totalAppt }}">{{ number_format($totalAppt) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:100%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a2">
        <div class="bk-stat" data-accent="orange">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-orange">
                    <i data-feather="clock" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Pending') }}</div>
                    <div class="bk-stat-sub">{{ __('need action') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $pendingAppt }}"
                 style="{{ $pendingAppt>0?'animation:bk-pulse 2s ease infinite;':'' }}">
                {{ number_format($pendingAppt) }}
            </div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ $totalAppt>0?round($pendingAppt/$totalAppt*100):0 }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a3">
        <div class="bk-stat" data-accent="green">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-green">
                    <i data-feather="briefcase" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Companies') }}</div>
                    <div class="bk-stat-sub">{{ $branchesCount }} {{ __('branches') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $companiesCount }}">{{ number_format($companiesCount) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($companiesCount*8,100) }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a4">
        <div class="bk-stat" data-accent="blue">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-blue">
                    <i data-feather="scissors" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Services') }}</div>
                    <div class="bk-stat-sub">{{ $servicesCount }} {{ __('total') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $servicesCount }}">{{ number_format($servicesCount) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($servicesCount*3,100) }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a5">
        <div class="bk-stat" data-accent="red">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-red">
                    <i data-feather="users" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Waitlist') }}</div>
                    <div class="bk-stat-sub">{{ __('waiting customers') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $waitlistWaiting }}">{{ number_format($waitlistWaiting) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($waitlistWaiting*10,100) }}%;"></div></div>
        </div>
    </div>

</div>

{{-- ════ QUICK ACTIONS ════ --}}
<div class="card shadow-sm mb-4 bk-a3">
    <div class="card-body py-3">
        <div class="bk-sh mb-3">
            <span class="bk-sh-title">{{ __('Quick Actions') }}</span>
        </div>
        <div class="bk-qa-grid">
            <a href="{{ route('owner.companies.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="briefcase" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Companies') }}</span>
            </a>
            <a href="{{ route('owner.branches.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="map-pin" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Branches') }}</span>
            </a>
            <a href="{{ route('owner.appointments.index') }}" class="bk-qa">
                <div class="bk-qa-ic">
                    <i data-feather="calendar" style="width:19px;height:19px;"></i>
                    @if($pendingAppt>0)<span class="bk-qa-dot"></span>@endif
                </div>
                <span class="bk-qa-lbl">{{ __('Appointments') }}</span>
            </a>
            <a href="{{ route('owner.categories.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="layers" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Categories') }}</span>
            </a>
            <a href="{{ route('owner.service-categories.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="tag" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Svc. Categories') }}</span>
            </a>
            <a href="{{ route('front.index') }}" target="_blank" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="globe" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('View Site') }}</span>
            </a>
        </div>
    </div>
</div>

{{-- ════ CHARTS ════ --}}
<div class="row g-4">

    {{-- Activity Chart + filter --}}
    <div class="col-lg-8 bk-a4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Booking Activity') }}</span>
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

    {{-- Donut + mini stats --}}
    <div class="col-lg-4 bk-a5">
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
                <a href="{{ route('owner.appointments.index') }}"
                   class="btn btn-primary w-100 rounded-pill mt-3 fw-bold">
                    {{ __('Manage Appointments') }}
                </a>
            </div>
        </div>
    </div>

</div>

{{-- ════ MONTHLY CHART ════ --}}
<div class="row g-4 mt-0">
    <div class="col-12 bk-a5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Monthly Appointments') }}</span>
                    <span style="font-size:.75rem;opacity:.35;">{{ __('Last 12 months — all companies') }}</span>
                </div>
                <div id="monthlySalesChart"></div>
            </div>
        </div>
    </div>
</div>

{{-- ════ RECENT APPOINTMENTS ════ --}}
<div class="row g-4 mt-0">

    {{-- Timeline --}}
    <div class="col-lg-4 bk-a5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Latest Activity') }}</span>
                    <a href="{{ route('owner.appointments.index') }}" class="bk-sh-link">
                        {{ __('All') }} <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                    </a>
                </div>
                @forelse($recentAppointments->take(7) as $row)
                @php
                    $ic = ['pending'=>'#f4a642','confirmed'=>'#2bcf7e','completed'=>'#3dbbd4'][$row->status] ?? '#555';
                    $ini = strtoupper(substr($row->customer?->name ?? 'C', 0, 1));
                    $bc  = 'bk-badge-'.($row->status ?? 'cancelled');
                @endphp
                <a href="{{ route('owner.appointments.show', $row) }}"
                   style="display:flex;align-items:flex-start;gap:12px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.05);text-decoration:none;transition:background .15s;"
                   class="bk-appt-row">
                    <div style="width:38px;height:38px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;background:{{ $ic }}22;color:{{ $ic }};">{{ $ini }}</div>
                    <div style="flex:1;overflow:hidden;">
                        <div style="font-size:.84rem;font-weight:600;color:rgba(255,255,255,.8);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row->customer?->name ?? __('Customer') }}</div>
                        <div style="font-size:.73rem;color:rgba(255,255,255,.4);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row->service?->localizedName() ?? '—' }} · {{ $row->branch?->localizedName() ?? '—' }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:.7rem;color:rgba(255,255,255,.3);">{{ $row->start_time?->format('M j H:i') }}</div>
                        <span class="bk-badge {{ $bc }} mt-1 d-inline-flex">{{ __($row->status ?? '') }}</span>
                    </div>
                </a>
                @empty
                <div class="bk-empty">
                    <div class="bk-empty-ic"><i data-feather="calendar" style="width:24px;height:24px;"></i></div>
                    <p>{{ __('No appointments yet.') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="col-lg-8 bk-a6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Upcoming & Recent') }}</span>
                    <a href="{{ route('owner.appointments.index') }}" class="bk-sh-link">
                        {{ __('Full list') }} <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                    </a>
                </div>

                @if($recentAppointments->count())
                @php
                    $byStatus = $recentAppointments->countBy('status');
                    $scColors = ['pending'=>'#f4a642','confirmed'=>'#2bcf7e','completed'=>'#3dbbd4','cancelled'=>'rgba(255,255,255,.07)','rejected'=>'rgba(255,255,255,.07)','no_show'=>'rgba(255,255,255,.07)'];
                @endphp
                <div class="bk-color-bar">
                    @foreach($byStatus as $st => $cnt)
                    <span style="flex:{{ $cnt }};background:{{ $scColors[$st] ?? '#888' }};"></span>
                    @endforeach
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Service / Branch') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Customer') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAppointments as $row)
                            @php $bc = 'bk-badge-'.($row->status ?? 'cancelled'); @endphp
                            <tr class="bk-table-row"
                                onclick="location.href='{{ route('owner.appointments.show', $row) }}'">
                                <td class="text-muted tx-12 fw-semibold">#{{ $row->id }}</td>
                                <td>
                                    <div class="fw-semibold tx-13">{{ $row->service?->localizedName() ?? '—' }}</div>
                                    <small class="text-muted opacity-50">{{ $row->branch?->localizedName() ?? '—' }}</small>
                                </td>
                                <td class="text-muted tx-12 text-nowrap">
                                    <div>{{ $row->start_time?->format('d M') }}</div>
                                    <small class="opacity-50">{{ $row->start_time?->format('H:i') }}</small>
                                </td>
                                <td><span class="bk-badge {{ $bc }}">{{ __($row->status ?? '') }}</span></td>
                                <td class="tx-13">{{ $row->customer?->name ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="bk-empty">
                                        <div class="bk-empty-ic"><i data-feather="calendar" style="width:24px;height:24px;"></i></div>
                                        <p>{{ __('No rows yet.') }}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

</div>{{-- .page-content --}}

@push('owner-after-template')
@php
    $booksyPayload = [
        'theme'  => request()->cookie('owner_theme','dark'),
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
<script src="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
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

/* ── Counters ── */
function runCounters(){
    document.querySelectorAll('.bk-counter[data-target]').forEach(function(el){
        var to=parseInt(el.dataset.target)||0;
        if(!to) return;
        var cur=0, step=to/(1400/16);
        var t=setInterval(function(){ cur=Math.min(cur+step,to); el.textContent=Math.floor(cur).toLocaleString(); if(cur>=to)clearInterval(t); },16);
    });
}
setTimeout(runCounters, 250);

/* ── Progress bars ── */
document.querySelectorAll('.bk-stat-bar-fill').forEach(function(el){
    var w=el.style.width; el.style.width='0';
    setTimeout(function(){ el.style.width=w; }, 400);
});

/* ── Activity Chart (filterable) ── */
var activityChart = null;

function getSeriesForRange(range){
    var key = { today:'today', week:'week', month:'month', year:'year' }[range] || 'month';
    var d = charts[key] || charts.month || charts.daily || {};
    return { labels: d.labels||[], data: d.total||[], name: labels.appointments||'Appointments' };
}

function renderActivity(range){
    range = range || 'month';
    var s    = getSeriesForRange(range);
    var node = document.getElementById('bk-activity-chart');
    if(!node || typeof ApexCharts === 'undefined') return;
    var rotateAlways = s.labels.length > 8;

    if(activityChart){
        activityChart.updateOptions({
            series:[{name:s.name, data:s.data}],
            xaxis:{categories:s.labels, labels:{rotate:isRtl?30:-30, rotateAlways:rotateAlways, style:{fontSize:'11px',colors:c.muted}}},
            noData:{text:labels.noData||'No data yet.'}
        }, true, true);
        return;
    }
    activityChart = new ApexCharts(node,{
        chart:{type:'bar',height:280,background:'transparent',toolbar:{show:false},foreColor:c.text,
               animations:{enabled:true,easing:'easeinout',speed:500}},
        plotOptions:{bar:{columnWidth:'55%',borderRadius:4}},
        dataLabels:{enabled:false},
        colors:[gold],
        series:[{name:s.name, data:s.data}],
        xaxis:{categories:s.labels, labels:{style:{fontSize:'11px',colors:c.muted},rotate:isRtl?30:-30,rotateAlways:rotateAlways}, axisBorder:{color:c.grid}, axisTicks:{color:c.grid}},
        yaxis:{min:0,forceNiceScale:true, labels:{formatter:function(v){return Math.round(v);},style:{colors:c.muted}}},
        grid:{borderColor:c.grid, xaxis:{lines:{show:false}}},
        noData:{text:labels.noData||'No data yet.',style:{color:c.muted}},
        tooltip:{theme:isDark?'dark':'light'},
        theme:{mode:isDark?'dark':'light'},
    });
    activityChart.render();
}

/* Filter tabs */
document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(b){b.classList.remove('active');});
        this.classList.add('active');
        renderActivity(this.dataset.range);
    });
});

/* ── Monthly chart ── */
function renderMonthly(){
    var node = document.getElementById('monthlySalesChart');
    if(!node || typeof ApexCharts==='undefined') return;
    var d = charts.monthly || {};
    new ApexCharts(node,{
        chart:{type:'bar',height:240,background:'transparent',toolbar:{show:false},foreColor:c.text},
        plotOptions:{bar:{columnWidth:'45%',borderRadius:4}},
        dataLabels:{enabled:false},
        colors:[gold],
        series:[{name:labels.appointments||'Appointments',data:d.total||[]}],
        xaxis:{categories:d.labels||[], labels:{style:{fontSize:'11px',colors:c.muted}}, axisBorder:{color:c.grid}, axisTicks:{color:c.grid}},
        yaxis:{min:0,forceNiceScale:true, labels:{formatter:function(v){return Math.round(v);},style:{colors:c.muted}}},
        grid:{borderColor:c.grid, xaxis:{lines:{show:false}}},
        tooltip:{theme:isDark?'dark':'light'},
        theme:{mode:isDark?'dark':'light'},
    }).render();
}

/* ── Donut ── */
function renderDonut(){
    var node = document.getElementById('storageChart');
    if(!node || typeof ApexCharts==='undefined') return;
    var st        = charts.by_status || {};
    var pending   = st.pending   || 0;
    var confirmed = st.confirmed || 0;
    var completed = st.completed || 0;
    var other     = (st.cancelled||0)+(st.rejected||0)+(st.no_show||0);
    var realTotal = pending + confirmed + completed + other;
    var isEmpty   = realTotal === 0;
    var series    = isEmpty ? [1] : [pending, confirmed, completed, other];
    var clrs      = isEmpty
        ? [isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.07)']
        : ['#f4a642','#2bcf7e','#3dbbd4', isDark ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.08)'];
    new ApexCharts(node,{
        chart:{type:'donut',height:200,background:'transparent',toolbar:{show:false}},
        series:series,
        labels: isEmpty ? ['—'] : ['Pending','Confirmed','Completed','Other'],
        colors:clrs,
        legend:{show:false},
        plotOptions:{pie:{donut:{size:'68%', labels:{show:!isEmpty, total:{
            show:true, label:'Total', color:c.muted, fontSize:'11px',
            formatter:function(){ return isEmpty?'0':String(realTotal); }
        }, value:{color:isDark?'#fff':'#333',fontSize:'18px',fontWeight:700,formatter:function(v){return String(parseInt(v)||0);}} }}}},
        dataLabels:{enabled:false},
        stroke:{width:0},
        theme:{mode:isDark?'dark':'light'},
        tooltip:{theme:isDark?'dark':'light', y:{formatter:function(v){return isEmpty?'0':String(v);}}},
    }).render();
}

renderActivity('month');
renderMonthly();
renderDonut();

setTimeout(function(){
    if(typeof feather !== 'undefined') feather.replace();
}, 50);

})();
</script>
@endpush
@endsection
