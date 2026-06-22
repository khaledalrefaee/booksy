@extends('company.dashboard')

@push('company-styles')
@include('company.cash._styles')
@endpush

@section('content')
<div class="page-content">

@php
    $cats        = \App\Models\BranchPayment::CATEGORIES;
    $periodLabel = ['today'=>__('Today'),'week'=>__('This week'),'month'=>__('This month'),'year'=>__('This year'),'custom'=>__('Custom')][$period] ?? __('This month');
    $isRtl       = app()->getLocale() === 'ar';
    $baseUrl     = route('company.cash.global');
@endphp

{{-- ─── HERO ─────────────────────────────────────────────────────────── --}}
<div class="cash-hero">
<div class="position-relative" style="z-index:1;">

    {{-- Top row --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div style="font-size:11px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px;">
                {{ __('All Branches') }}
            </div>
            <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">
                🏦 {{ $isRtl ? 'الصندوق الكامل' : 'Global Cash Register' }}
            </h3>
        </div>
        <div class="period-tabs">
            @foreach(['today'=>__('Today'),'week'=>__('Week'),'month'=>__('Month'),'year'=>__('Year')] as $p=>$lbl)
            <a href="{{ $baseUrl }}?period={{ $p }}{{ $branchId ? '&branch_id='.$branchId : '' }}"
               class="period-tab {{ $period===$p ? 'active' : '' }}">{{ $lbl }}</a>
            @endforeach
            <button class="period-tab {{ $period==='custom' ? 'active' : '' }}"
                    onclick="document.getElementById('custom-date-row').classList.toggle('d-none')"
                    type="button">{{ __('Custom') }}</button>
        </div>
    </div>

    {{-- Custom date range --}}
    <form method="GET" action="{{ $baseUrl }}" id="custom-date-row"
          class="{{ $period==='custom' ? '' : 'd-none' }} mb-4">
        <input type="hidden" name="period" value="custom">
        @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="date" name="from" class="form-control form-control-sm rounded-pill"
                   style="width:150px;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff;"
                   value="{{ $customFrom ?? now()->startOfMonth()->toDateString() }}">
            <span style="opacity:.5;">—</span>
            <input type="date" name="to" class="form-control form-control-sm rounded-pill"
                   style="width:150px;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff;"
                   value="{{ $customTo ?? now()->toDateString() }}">
            <button class="btn btn-sm rounded-pill px-3"
                    style="background:#667eea;color:#fff;border:none;font-weight:600;">
                {{ __('Apply') }}
            </button>
        </div>
    </form>

    {{-- Branch filter --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ $baseUrl }}?period={{ $period }}{{ $period==='custom' ? '&from='.($customFrom ?? '').'&to='.($customTo ?? '') : '' }}"
           class="branch-pill {{ !$branchId ? 'active' : '' }}">
            🌐 {{ $isRtl ? 'الكل' : 'All' }}
        </a>
        @foreach($branches as $b)
        <a href="{{ $baseUrl }}?period={{ $period }}&branch_id={{ $b->id }}{{ $period==='custom' ? '&from='.($customFrom ?? '').'&to='.($customTo ?? '') : '' }}"
           class="branch-pill {{ $branchId == $b->id ? 'active' : '' }}">
            {{ $b->localizedName() }}
        </a>
        @endforeach
    </div>

    {{-- Total summary cards per currency --}}
    @if($summary->isEmpty())
    <div class="balance-card" style="text-align:center;opacity:.5;">
        <div style="font-size:32px;margin-bottom:8px;">💼</div>
        <div style="font-size:13px;">{{ __('No transactions yet for this period.') }}</div>
    </div>
    @else
    <div class="row g-3">
        @foreach($summary as $cur => $s)
        @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
        <div class="col-12 col-md-4">
            <div class="balance-card">
                <div class="balance-label">{{ __('Net balance') }} · {{ $cur }}</div>
                <div class="balance-value {{ $s['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $s['net'] >= 0 ? '+' : '' }}{{ number_format($s['net'], 0) }}
                    <span style="font-size:16px;opacity:.5;">{{ $sym }}</span>
                </div>
                <div class="d-flex gap-4 mt-3">
                    <div class="cash-stat">
                        <div class="cash-stat-icon" style="background:rgba(34,197,94,.15);">⬆️</div>
                        <div>
                            <div class="cash-stat-val" style="color:#22c55e;">{{ number_format($s['income'],0) }} {{ $sym }}</div>
                            <div class="cash-stat-lbl">{{ __('Income') }}</div>
                        </div>
                    </div>
                    <div class="cash-stat">
                        <div class="cash-stat-icon" style="background:rgba(239,68,68,.15);">⬇️</div>
                        <div>
                            <div class="cash-stat-val" style="color:#ef4444;">{{ number_format($s['expense'],0) }} {{ $sym }}</div>
                            <div class="cash-stat-lbl">{{ __('Expenses') }}</div>
                        </div>
                    </div>
                    <div class="cash-stat">
                        <div class="cash-stat-icon" style="background:rgba(100,116,139,.15);">📊</div>
                        <div>
                            <div class="cash-stat-val">{{ $transactions->count() }}</div>
                            <div class="cash-stat-lbl">{{ __('Transactions') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
</div>

@include('company.partials.flash')

<div class="row g-3">

    {{-- ─── LEFT: Chart + Branch breakdown + Transactions ──────────────── --}}
    <div class="col-12 col-xl-8">

        {{-- Chart --}}
        @if(count($chartData) > 1)
        <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
            <div class="card-body p-3">
                <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                    {{ __('Income vs Expenses') }}
                </div>
                <div id="cashChart"></div>
            </div>
        </div>
        @endif

        {{-- Per-branch balance cards (only when showing all branches) --}}
        @if(!$branchId && $byBranch->isNotEmpty())
        <div class="row g-2 mb-3">
            @foreach($byBranch as $item)
            @php $br = $item['branch']; @endphp
            <div class="col-12 col-sm-6">
                <a href="{{ route('company.branches.cash.index', $br) }}"
                   class="card border-0 shadow-sm text-decoration-none" style="border-radius:14px;display:block;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div style="font-size:13px;font-weight:800;color:var(--text-color);">
                                🏪 {{ $br?->localizedName() ?? '—' }}
                            </div>
                            <span style="font-size:10px;color:#667eea;font-weight:600;">
                                {{ __('View') }} →
                            </span>
                        </div>
                        @foreach($item['byCurrency'] as $cur => $s)
                        @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                        <div class="d-flex justify-content-between align-items-center" style="font-size:12px;margin-bottom:2px;">
                            <span style="opacity:.5;">{{ $cur }}</span>
                            <span style="font-weight:800;color:{{ $s['net'] >= 0 ? '#22c55e' : '#ef4444' }};">
                                {{ $s['net'] >= 0 ? '+' : '' }}{{ number_format($s['net'],0) }} {{ $sym }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Transactions grouped by date --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;">
                        {{ __('Transactions') }}
                    </div>
                    <span style="font-size:12px;font-weight:700;background:rgba(102,126,234,.1);color:#667eea;padding:3px 10px;border-radius:20px;">
                        {{ $paginatedTx->total() }}
                    </span>
                </div>

                @if($grouped->isEmpty())
                <div class="text-center py-5" style="opacity:.4;">
                    <div style="font-size:32px;margin-bottom:8px;">🧾</div>
                    <div style="font-size:13px;">{{ __('No transactions for this period.') }}</div>
                </div>
                @else
                @foreach($grouped as $date => $rows)
                <div class="tx-date-head">
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('l، d F Y') }}
                    <span style="margin-inline-start:8px;font-size:10px;opacity:.6;">({{ $rows->count() }})</span>
                </div>
                @foreach($rows as $tx)
                @php
                    $catMeta  = $cats[$tx->category] ?? ['icon'=>'💵','color'=>'#667eea','label_key'=>$tx->category,'type'=>'income'];
                    $isIncome = $catMeta['type'] === 'income';
                    $sym      = config("booksy.currencies.{$tx->currency}.symbol", $tx->currency);
                @endphp
                <div class="tx-row">
                    <div class="tx-icon" style="background:{{ $catMeta['color'] }}20;">
                        {{ $catMeta['icon'] }}
                    </div>
                    <div class="tx-meta">
                        <div class="tx-title">{{ __($catMeta['label_key']) }}</div>
                        <div class="tx-sub">
                            {{ $tx->paid_at->format('H:i') }}
                            @if($tx->notes) · {{ Str::limit($tx->notes, 45) }}@endif
                            @if($tx->appointment?->customer) · {{ $tx->appointment->customer->name }}@endif
                        </div>
                    </div>
                    {{-- Branch badge (shown when viewing all) --}}
                    @if(!$branchId)
                    <div class="tx-branch-badge">{{ $tx->branch?->localizedName() ?? '—' }}</div>
                    @endif
                    <div class="tx-amount" style="color:{{ $isIncome ? '#22c55e' : '#ef4444' }};">
                        {{ $isIncome ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                        <span style="font-size:10px;opacity:.5;">{{ $sym }}</span>
                    </div>
                    {{-- Link to branch cash page --}}
                    <a href="{{ route('company.branches.cash.index', $tx->branch_id) }}"
                       onclick="event.stopPropagation();"
                       title="{{ $isRtl ? 'فتح صندوق الفرع' : 'Open branch cash' }}"
                       style="opacity:0;transition:opacity .15s;color:var(--cal-text-muted, #94a3b8);padding:4px 6px;border-radius:6px;"
                       class="tx-branch-link">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                </div>
                @endforeach
                @endforeach

                {{-- Pagination --}}
                @if($paginatedTx->hasPages())
                <div class="mt-3">{{ $paginatedTx->links() }}</div>
                @endif
                @endif

            </div>
        </div>
    </div>

    {{-- ─── RIGHT: Category breakdown + quick links ────────────────────── --}}
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;position:sticky;top:80px;">
            <div class="card-body p-3">

                {{-- Breakdown --}}
                <div style="font-size:12px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;">
                    {{ __('Breakdown') }}
                </div>

                <div class="income-section">
                    <div style="font-size:10px;font-weight:700;color:#22c55e;opacity:.7;margin-bottom:8px;">⬆ {{ __('INCOME') }}</div>
                    @foreach($incomeCats as $key => $meta)
                    @php $total = $byCat[$key] ?? 0; @endphp
                    @if($total > 0)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:15px;">{{ $meta['icon'] }}</span>
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex justify-content-between" style="font-size:12px;font-weight:600;">
                                <span>{{ __($meta['label_key']) }}</span>
                                <span style="color:#22c55e;">{{ number_format($total,0) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>

                <div class="expense-section mt-3">
                    <div style="font-size:10px;font-weight:700;color:#ef4444;opacity:.7;margin-bottom:8px;">⬇ {{ __('EXPENSES') }}</div>
                    @foreach($expenseCats as $key => $meta)
                    @php $total = $byCat[$key] ?? 0; @endphp
                    @if($total > 0)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:15px;">{{ $meta['icon'] }}</span>
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex justify-content-between" style="font-size:12px;font-weight:600;">
                                <span>{{ __($meta['label_key']) }}</span>
                                <span style="color:#ef4444;">{{ number_format($total,0) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- Quick links to each branch cash --}}
                <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.07);">
                    <div style="font-size:11px;font-weight:700;opacity:.4;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
                        {{ $isRtl ? 'صناديق الأفرع' : 'Branch Registers' }}
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($branches as $b)
                        <a href="{{ route('company.branches.cash.index', $b) }}"
                           class="d-flex align-items-center justify-content-between text-decoration-none"
                           style="padding:8px 12px;border-radius:10px;background:rgba(102,126,234,.08);border:1px solid rgba(102,126,234,.15);transition:background .12s;"
                           onmouseover="this.style.background='rgba(102,126,234,.16)'"
                           onmouseout="this.style.background='rgba(102,126,234,.08)'">
                            <span style="font-size:12px;font-weight:700;color:var(--text-color);">🏪 {{ $b->localizedName() }}</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2.5"><polyline points="{{ $isRtl ? '15 18 9 12 15 6' : '9 18 15 12 9 6' }}"/></svg>
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Archive months --}}
                @if($archiveMonths->isNotEmpty())
                <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.07);">
                    <div style="font-size:11px;font-weight:700;opacity:.4;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
                        📅 {{ __('Archive') }}
                    </div>
                    <div class="d-flex flex-column gap-1" id="archive-list">
                        @foreach($archiveMonths->take(6) as $am)
                        @php
                            $isActive = $period === 'custom' && $customFrom === $am['from'] && $customTo === $am['to'];
                        @endphp
                        <a href="{{ $baseUrl }}?period=custom&from={{ $am['from'] }}&to={{ $am['to'] }}{{ $branchId ? '&branch_id='.$branchId : '' }}"
                           class="d-flex align-items-center justify-content-between text-decoration-none"
                           style="padding:6px 10px;border-radius:8px;transition:background .12s;{{ $isActive ? 'background:rgba(102,126,234,.15);' : '' }}"
                           onmouseover="this.style.background='rgba(102,126,234,.1)'"
                           onmouseout="this.style.background='{{ $isActive ? 'rgba(102,126,234,.15)' : 'transparent' }}'">
                            <span style="font-size:12px;font-weight:600;color:var(--text-color);{{ $isActive ? 'color:#667eea;' : '' }}">{{ $am['label'] }}</span>
                            <span style="font-size:10px;font-weight:700;opacity:.4;background:rgba(255,255,255,.06);padding:1px 7px;border-radius:10px;">{{ $am['count'] }}</span>
                        </a>
                        @endforeach
                    </div>
                    @if($archiveMonths->count() > 6)
                    <div id="archive-more" class="d-none">
                        @foreach($archiveMonths->skip(6) as $am)
                        @php
                            $isActive = $period === 'custom' && $customFrom === $am['from'] && $customTo === $am['to'];
                        @endphp
                        <a href="{{ $baseUrl }}?period=custom&from={{ $am['from'] }}&to={{ $am['to'] }}{{ $branchId ? '&branch_id='.$branchId : '' }}"
                           class="d-flex align-items-center justify-content-between text-decoration-none"
                           style="padding:6px 10px;border-radius:8px;transition:background .12s;{{ $isActive ? 'background:rgba(102,126,234,.15);' : '' }}"
                           onmouseover="this.style.background='rgba(102,126,234,.1)'"
                           onmouseout="this.style.background='{{ $isActive ? 'rgba(102,126,234,.15)' : 'transparent' }}'">
                            <span style="font-size:12px;font-weight:600;color:var(--text-color);{{ $isActive ? 'color:#667eea;' : '' }}">{{ $am['label'] }}</span>
                            <span style="font-size:10px;font-weight:700;opacity:.4;background:rgba(255,255,255,.06);padding:1px 7px;border-radius:10px;">{{ $am['count'] }}</span>
                        </a>
                        @endforeach
                    </div>
                    <button onclick="document.getElementById('archive-more').classList.remove('d-none');this.remove();"
                            class="btn btn-sm w-100 mt-1" style="font-size:10px;font-weight:700;opacity:.5;background:rgba(255,255,255,.04);border:none;border-radius:8px;">
                        {{ __('Show more') }} ({{ $archiveMonths->count() - 6 }})
                    </button>
                    @endif
                </div>
                @endif

            </div>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    var chartEl = document.getElementById('cashChart');
    if (!chartEl) return;

    var rawData = @json($chartData);
    var isDark  = !document.documentElement.classList.contains('bk-theme-light');

    var isAr = document.documentElement.getAttribute('dir') === 'rtl' || document.documentElement.lang === 'ar';
    var monthsAr = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    var monthsEn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var isMonthly = rawData.length > 0 && rawData[0].mode === 'month';

    var dates = rawData.map(function(d) {
        var parts = d.date.split('-');
        var m = parseInt(parts[1]) - 1;
        var day = parseInt(parts[2]);
        if (isMonthly) {
            return isAr ? monthsAr[m] : monthsEn[m];
        }
        return isAr ? (day + ' ' + monthsAr[m]) : (monthsEn[m] + ' ' + day);
    });

    var totalPoints = rawData.length;

    new ApexCharts(chartEl, {
        chart: {
            type: 'area', height: 200,
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, speed: 600, easing: 'easeinout' },
            zoom: { enabled: false },
            fontFamily: 'inherit',
        },
        series: [
            { name: '{{ __("Income") }}',   data: rawData.map(function(d){ return d.income;  }) },
            { name: '{{ __("Expenses") }}', data: rawData.map(function(d){ return d.expense; }) },
        ],
        xaxis: {
            categories: dates,
            labels: {
                rotate: -45,
                rotateAlways: totalPoints > 10,
                hideOverlappingLabels: true,
                maxHeight: 60,
                style: { fontSize: '9px', fontWeight: 600, colors: isDark ? '#64748b' : '#94a3b8' },
                trim: true,
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tickAmount: Math.min(totalPoints, 12),
            tooltip: { enabled: false },
        },
        yaxis: {
            labels: {
                style: { colors: isDark ? '#64748b' : '#94a3b8', fontSize: '10px', fontWeight: 600 },
                formatter: function(v) {
                    if (v >= 1000000) return (v/1000000).toFixed(1) + 'M';
                    if (v >= 1000) return (v/1000).toFixed(0) + 'K';
                    return v.toFixed(0);
                }
            },
        },
        colors: ['#22c55e', '#ef4444'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
        },
        stroke: { curve: 'smooth', width: 2.5 },
        dataLabels: { enabled: false },
        legend: {
            position: 'top',
            horizontalAlign: isAr ? 'right' : 'left',
            labels: { colors: isDark ? '#94a3b8' : '#64748b' },
            fontSize: '11px',
            fontWeight: 700,
            markers: { width: 8, height: 8, radius: 8 },
            itemMargin: { horizontal: 12 },
        },
        grid: {
            borderColor: isDark ? 'rgba(255,255,255,.04)' : 'rgba(0,0,0,.06)',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            padding: { left: 8, right: 8 },
        },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            shared: true,
            intersect: false,
            style: { fontSize: '12px', fontFamily: 'inherit' },
            y: { formatter: function(v) { return v ? v.toLocaleString() : '0'; } },
            marker: { show: true },
        },
        markers: {
            size: totalPoints <= 14 ? 4 : 0,
            strokeWidth: 2,
            strokeColors: isDark ? '#1e1e2d' : '#fff',
            hover: { size: 6 },
        },
        theme: { mode: isDark ? 'dark' : 'light' },
    }).render();
})();
</script>
@endpush
