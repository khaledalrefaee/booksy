{{-- Income vs Expenses chart — one series pair per currency (amounts of different currencies are never mixed).
     Expects $chartData = [ 'SYP' => [ {date,income,expense,mode}, ... ], 'USD' => [...] ] --}}
@php
    $chartCurrencies = collect($chartData)->filter(fn($pts) => count($pts) > 0);
    $chartSymbols = $chartCurrencies->keys()->mapWithKeys(fn($c) => [$c => config("booksy.currencies.{$c}.symbol", $c)]);
@endphp
@if($chartCurrencies->isNotEmpty())
<div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div style="font-size:12px;font-weight:700;opacity:.55;text-transform:uppercase;letter-spacing:.5px;">
                {{ __('Income vs Expenses') }}
            </div>
            @if($chartCurrencies->count() > 1)
            <div class="d-flex gap-1" id="cashChartCurrencies">
                @foreach($chartCurrencies->keys() as $i => $code)
                <button type="button" data-cur="{{ $code }}" class="period-tab cash-chart-cur {{ $i === 0 ? 'active' : '' }}">{{ $chartSymbols[$code] }} {{ $code }}</button>
                @endforeach
            </div>
            @endif
        </div>
        <div id="cashChart"></div>
    </div>
</div>
<style>
    #cashChartCurrencies .period-tab { color:var(--text-color); border-color:rgba(128,128,128,.35); padding:3px 12px; }
    #cashChartCurrencies .period-tab.active { background:#5C7038; border-color:#5C7038; color:#fff; }
</style>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var chartEl = document.getElementById('cashChart');
    if (!chartEl || typeof ApexCharts === 'undefined') return;

    var dataByCur = @json($chartCurrencies);
    var symbols   = @json($chartSymbols);
    var current   = Object.keys(dataByCur)[0];
    var isDark    = !document.documentElement.classList.contains('bk-theme-light');
    var isAr      = document.documentElement.getAttribute('dir') === 'rtl' || document.documentElement.lang === 'ar';
    var monthsAr  = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    var monthsEn  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var txt       = isDark ? '#cbd5c0' : '#475569';

    function fmt(v) {
        v = Number(v) || 0;
        if (Math.abs(v) >= 1000000) return (v / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (Math.abs(v) >= 1000)    return (v / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        return v.toFixed(0);
    }

    function monthLabel(raw) {
        if (!raw.length) return '';
        var p = raw[0].date.split('-'), m = parseInt(p[1], 10) - 1;
        return (isAr ? monthsAr[m] : monthsEn[m]) + ' ' + p[0];
    }

    function build(cur) {
        var raw = dataByCur[cur] || [];
        var monthly = raw.length > 0 && raw[0].mode === 'month';
        var few = false;   // smooth area chart
        var dense = raw.length > 14;   // many days: show day numbers only, month name goes under the axis
        var labels = raw.map(function (d) {
            var p = d.date.split('-'), m = parseInt(p[1], 10) - 1, day = parseInt(p[2], 10);
            if (monthly) return isAr ? monthsAr[m] : monthsEn[m];
            if (dense) return String(day);
            return isAr ? (day + ' ' + monthsAr[m]) : (monthsEn[m] + ' ' + day);
        });
        var sym = symbols[cur] || cur;
        var fullLabels = labels.slice();
        var step = Math.max(1, Math.ceil(raw.length / (chartEl.clientWidth < 420 ? 8 : 16)));   // evenly spaced day labels
        var axisLabels = !dense ? labels : labels.map(function (l, i) {
            return (i % step === 0 || (i === labels.length - 1 && (labels.length - 1) % step > step / 2)) ? l : ' ';
        });
        return {
            chart: { type: few ? 'bar' : 'area', height: 240, toolbar: { show: false }, background: 'transparent',
                     animations: { enabled: true, speed: 500 }, zoom: { enabled: false }, fontFamily: 'inherit' },
            series: [
                { name: '{{ __("Income") }}',   data: raw.map(function (d) { return d.income;  }) },
                { name: '{{ __("Expenses") }}', data: raw.map(function (d) { return d.expense; }) },
            ],
            plotOptions: { bar: { columnWidth: dense ? '70%' : '45%', borderRadius: 5, dataLabels: { position: 'top' } } },
            xaxis: { categories: axisLabels, axisBorder: { show: false }, axisTicks: { show: false },
                     labels: { rotate: dense ? 0 : -45, rotateAlways: false, hideOverlappingLabels: false, trim: false,
                               style: { fontSize: '12px', fontWeight: 700, colors: txt } },
                     title: { text: dense ? monthLabel(raw) : undefined, style: { fontSize: '12px', fontWeight: 700, color: txt } } },
            yaxis: { labels: { formatter: fmt, style: { colors: txt, fontSize: '12px', fontWeight: 700 } } },
            colors: ['#22c55e', '#ef4444'],
            fill: few ? { opacity: 1 } : { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .05, stops: [0, 90, 100] } },
            stroke: few ? { show: false } : { curve: 'smooth', width: 2.5 },
            dataLabels: {
                enabled: few, offsetY: -18,
                formatter: function (v) { return v > 0 ? fmt(v) : ''; },
                style: { fontSize: '12px', fontWeight: 800, colors: [isDark ? '#ffffff' : '#1f2937'] },
                background: { enabled: false },
            },
            legend: { position: 'top', horizontalAlign: isAr ? 'right' : 'left', fontSize: '12px', fontWeight: 700,
                      labels: { colors: txt }, markers: { width: 9, height: 9, radius: 9 }, itemMargin: { horizontal: 12 } },
            grid: { borderColor: isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.08)', strokeDashArray: 4,
                    xaxis: { lines: { show: false } }, padding: { left: 8, right: 8 } },
            tooltip: { theme: isDark ? 'dark' : 'light', shared: true, intersect: false,
                       x: { formatter: function (v, o) { return o && o.dataPointIndex !== undefined ? fullLabels[o.dataPointIndex] : v; } },
                       y: { formatter: function (v) { return (Number(v) || 0).toLocaleString() + ' ' + sym; } } },
            markers: { size: raw.length <= 14 ? 4 : 0, discrete: raw.length <= 14 ? [] : [].concat.apply([], raw.map(function (d, i) { var r = []; if (d.income > 0) r.push({ seriesIndex: 0, dataPointIndex: i, size: 5, fillColor: '#22c55e', strokeColor: '#fff' }); if (d.expense > 0) r.push({ seriesIndex: 1, dataPointIndex: i, size: 5, fillColor: '#ef4444', strokeColor: '#fff' }); return r; })), strokeWidth: 2, strokeColors: isDark ? '#1e1e2d' : '#fff', hover: { size: 6 } },
            theme: { mode: isDark ? 'dark' : 'light' },
        };
    }

    var chart = new ApexCharts(chartEl, build(current));
    chart.render();

    document.querySelectorAll('.cash-chart-cur').forEach(function (btn) {
        btn.addEventListener('click', function () {
            current = btn.dataset.cur;
            document.querySelectorAll('.cash-chart-cur').forEach(function (b) { b.classList.toggle('active', b === btn); });
            chart.destroy();
            chart = new ApexCharts(chartEl, build(current));
            chart.render();
        });
    });
});
</script>
@endpush
@endif
