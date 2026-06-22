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

{{-- ════ HERO HEADER ════ --}}
<div class="bk-hero bk-a1">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="bk-hero-title">{{ __('Business') }} <span>{{ __('Dashboard') }}</span></h2>
            <p class="bk-hero-sub">
                <i data-feather="briefcase" style="width:13px;height:13px;display:inline;margin-right:5px;"></i>
                {{ $company->localizedName() }}
            </p>
        </div>
        <div class="bk-hero-actions">
            <a href="{{ route('front.show', $company) }}" target="_blank"
               class="bk-navbar-action bk-navbar-action-ghost d-flex align-items-center gap-2">
                <i data-feather="external-link" style="width:14px;height:14px;"></i>
                {{ __('Public page') }}
            </a>
            <a href="{{ route('company.appointments.create') }}"
               class="bk-navbar-action bk-navbar-action-primary d-flex align-items-center gap-2">
                <i data-feather="plus" style="width:14px;height:14px;"></i>
                {{ __('New booking') }}
            </a>
        </div>
    </div>
</div>

@include('company.partials.flash')

{{-- ════ STAT CARDS — horizontal ════ --}}
<div class="row g-3 mb-4">

    <div class="col-12 col-sm-6 col-lg-3 bk-a1">
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

    <div class="col-12 col-sm-6 col-lg-3 bk-a2">
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

    <div class="col-12 col-sm-6 col-lg-3 bk-a3">
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

    <div class="col-12 col-sm-6 col-lg-3 bk-a4">
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
                <div class="bk-sh flex-wrap gap-2">
                    <span class="bk-sh-title">{{ __('Booking Activity') }}</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        {{-- Month picker (visible only on month tab) --}}
                        <select id="bk-month-picker" class="form-select form-select-sm"
                                style="display:none;width:auto;font-size:.78rem;padding:.25rem .6rem;">
                            @foreach($monthOptions as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                        {{-- Filter tabs --}}
                        <div class="bk-filter-tabs" id="bk-chart-filter">
                            <button class="bk-filter-tab" data-range="today">{{ __('Today') }}</button>
                            <button class="bk-filter-tab" data-range="week">{{ __('Week') }}</button>
                            <button class="bk-filter-tab active" data-range="month">{{ __('Month') }}</button>
                            <button class="bk-filter-tab" data-range="year">{{ __('Year') }}</button>
                        </div>
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
                            @foreach($recentAppointments as $i => $row)
                            @php
                                $initial = strtoupper(substr($row->customer?->name ?? 'C', 0, 1));
                                $icColors = ['pending'=>'#f4a642','confirmed'=>'#2bcf7e','completed'=>'#3dbbd4','cancelled'=>'#555','rejected'=>'#555','no_show'=>'#555'];
                                $ic = $icColors[$row->status] ?? '#888';
                                $bc = 'bk-badge-'.($row->status ?? 'cancelled');
                            @endphp
                            <tr class="bk-table-row" onclick="location.href='{{ route('company.appointments.show', $row) }}'">
                                <td class="text-muted tx-12 fw-semibold">{{ $i + 1 }}</td>
                                <td onclick="event.stopPropagation()">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:{{ $ic }}22;color:{{ $ic }};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0;">{{ $initial }}</div>
                                        <div>
                                            <div class="fw-semibold tx-13">{{ $row->customer?->name ?? '—' }}</div>
                                            @if($row->customer?->phone)
                                            @php $phone = preg_replace('/\D/', '', $row->customer->phone); @endphp
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <span class="text-muted" style="font-size:.7rem;">{{ $row->customer->phone }}</span>
                                                <a href="tel:{{ $row->customer->phone }}"
                                                   style="color:#2bcf7e;line-height:1;" title="{{ __('Call') }}">
                                                    <i data-feather="phone" style="width:11px;height:11px;"></i>
                                                </a>
                                                <a href="https://wa.me/{{ $phone }}" target="_blank"
                                                   style="color:#25d366;line-height:1;" title="WhatsApp">
                                                    <svg style="width:11px;height:11px;fill:currentColor;" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="tx-13 text-muted">
                                    <div>{{ $row->service?->localizedName() ?? '—' }}</div>
                                    @if($row->branch)<small class="opacity-50">{{ $row->branch->localizedName() }}</small>@endif
                                </td>
                                <td class="text-muted tx-12 text-nowrap">
                                    <div>{{ $row->start_time?->format('d/m/Y') }} <span class="opacity-50">{{ $row->start_time?->format('H:i') }}</span></div>
                                    <small class="bk-reltime opacity-75" data-ts="{{ $row->start_time?->format('Y-m-d\TH:i:s') }}" style="font-size:.68rem;"></small>
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
        'theme'       => request()->cookie('company_theme','dark'),
        'rtl'         => $isAr,
        'charts'      => $chartData,
        'monthChartUrl' => route('company.dashboard.chart.month'),
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
var monthPicker   = document.getElementById('bk-month-picker');
var monthChartUrl = p.monthChartUrl || '';

function getSeriesForRange(range, overrideData){
    if (overrideData) {
        return { labels: overrideData.labels || [], data: overrideData.total || [], name: labels.appointments || 'Appointments' };
    }
    var key = { today:'today', week:'week', month:'month', year:'year' }[range] || 'month';
    var d = charts[key] || charts.month || charts.daily || {};
    return { labels: d.labels || [], data: d.total || [], name: labels.appointments || 'Appointments' };
}

function applyChartSeries(s, range){
    var node = document.getElementById('bk-activity-chart');
    if (!node || typeof ApexCharts === 'undefined') return;

    var needsRotate  = range === 'today' || range === 'week';
    var rotate       = needsRotate ? (isRtl ? 30 : -30) : 0;
    var fontSize     = '10px';
    // Limit visible x-axis labels to avoid crowding
    var maxTicks     = { today: 12, week: 7, month: 10, year: 12 }[range] || 10;
    var tickAmount   = Math.min(s.labels.length, maxTicks);

    var xaxisOpts = {
        categories      : s.labels,
        tickAmount      : tickAmount,
        labels          : {
            rotate              : rotate,
            rotateAlways        : needsRotate,
            hideOverlappingLabels: true,
            style               : { fontSize: fontSize, colors: c.muted }
        },
        axisBorder: { color: c.grid },
        axisTicks : { color: c.grid }
    };

    if (activityChart) {
        activityChart.updateOptions({
            series : [{ name: s.name, data: s.data }],
            xaxis  : xaxisOpts,
            noData : { text: labels.noData || 'No data yet.' }
        }, true, true);
        return;
    }

    activityChart = new ApexCharts(node, {
        chart:{ type:'bar', height:280, background:'transparent', toolbar:{show:false}, fontFamily:"'Roboto',sans-serif", foreColor:c.text, animations:{enabled:true,easing:'easeinout',speed:500} },
        plotOptions:{ bar:{ columnWidth:'60%', borderRadius:3 } },
        dataLabels:{ enabled:false },
        colors:[gold],
        series:[{ name: s.name, data: s.data }],
        xaxis: xaxisOpts,
        yaxis:{ min:0, forceNiceScale:true, labels:{ formatter:function(v){ return Math.round(v); }, style:{colors:c.muted} } },
        grid :{ borderColor:c.grid, xaxis:{ lines:{show:false} } },
        noData:{ text: labels.noData || 'No data yet.', style:{color:c.muted} },
        tooltip:{ theme: isDark?'dark':'light' },
        theme  :{ mode: isDark?'dark':'light' },
    });
    activityChart.render();
}

function renderActivityChart(range, overrideData){
    range = range || 'month';
    applyChartSeries(getSeriesForRange(range, overrideData), range);
}

function loadMonthChart(yearMonth){
    if (!monthChartUrl) return;
    var parts = (yearMonth || '').split('-');
    var year  = parts[0] || '';
    var month = parts[1] || '';
    if (!year || !month) return;

    fetch(monthChartUrl + '?year=' + year + '&month=' + month, { headers:{ 'X-Requested-With':'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(data){ renderActivityChart('month', data); })
        .catch(function(){});
}

/* ── Month picker ── */
if (monthPicker) {
    monthPicker.addEventListener('change', function(){ loadMonthChart(this.value); });
}

/* ── Filter tabs ── */
document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(b){ b.classList.remove('active'); });
        this.classList.add('active');
        var range = this.dataset.range;

        // Show/hide month picker
        if (monthPicker) monthPicker.style.display = (range === 'month') ? '' : 'none';

        if (range === 'month') {
            loadMonthChart(monthPicker ? monthPicker.value : '');
        } else {
            renderActivityChart(range);
        }
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

// Show month picker on initial load (month tab is active by default)
if (monthPicker) monthPicker.style.display = '';
loadMonthChart(monthPicker ? monthPicker.value : '');
renderDonut();

/* Re-run feather after charts render (icons inside cards need a second pass) */
setTimeout(function(){
    if(typeof feather !== 'undefined') feather.replace();
}, 50);

/* ── Relative time (real-time) ── */
(function(){
    var ar = p.rtl === true;

    function relTime(ts){
        // ts is a local datetime string like "2025-06-15T20:00:00"
        // new Date(str) without timezone treats it as local time
        var apptMs = new Date(ts).getTime();
        var nowMs  = Date.now();
        var diffSec = Math.round((nowMs - apptMs) / 1000); // positive = past
        var diff = diffSec;
        var abs  = Math.abs(diff);
        var past = diff >= 0;

        var val, unit;
        if      (abs < 60)       { val = abs;                unit = ar ? ['ثانية','ثوانٍ','دقيقة'] : ['second','seconds','minute']; val = Math.max(val,1); unit = val===1?(ar?'ثانية':'second'):(ar?'ثوانٍ':'seconds'); }
        else if (abs < 3600)     { val = Math.round(abs/60);  unit = val===1?(ar?'دقيقة':'minute'):(ar?'دقائق':'minutes'); }
        else if (abs < 86400)    { val = Math.round(abs/3600); unit = val===1?(ar?'ساعة':'hour'):(ar?'ساعات':'hours'); }
        else if (abs < 2592000)  { val = Math.round(abs/86400); unit = val===1?(ar?'يوم':'day'):(ar?'أيام':'days'); }
        else if (abs < 31536000) { val = Math.round(abs/2592000); unit = val===1?(ar?'شهر':'month'):(ar?'أشهر':'months'); }
        else                     { val = Math.round(abs/31536000); unit = val===1?(ar?'سنة':'year'):(ar?'سنوات':'years'); }

        if(ar){
            return past
                ? 'مضى ' + val + ' ' + unit
                : 'باقي ' + val + ' ' + unit;
        } else {
            return past
                ? val + ' ' + unit + ' ago'
                : 'in ' + val + ' ' + unit;
        }
    }

    function updateAll(){
        document.querySelectorAll('.bk-reltime[data-ts]').forEach(function(el){
            var ts = el.dataset.ts;
            if(ts) el.textContent = relTime(ts);
        });
    }

    updateAll();
    setInterval(updateAll, 30000);
})();

})();
</script>
@endpush
@endsection
