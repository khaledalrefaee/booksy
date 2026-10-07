@extends('company.dashboard')

@section('content')
@php
    $changeIncome  = $prevData['income']  > 0 ? round((($currentData['income']  - $prevData['income'])  / $prevData['income'])  * 100, 1) : null;
    $changeExpense = $prevData['expense'] > 0 ? round((($currentData['expense'] - $prevData['expense']) / $prevData['expense']) * 100, 1) : null;
    $changeNet     = $prevData['net'] != 0    ? round((($currentData['net']     - $prevData['net'])     / abs($prevData['net']))  * 100, 1) : null;

    // Month-over-month badge. $lowerIsBetter flips the colour for expenses.
    // Direction is carried by the arrow and the sign, never by colour alone.
    $delta = function (?float $pct, bool $lowerIsBetter = false) {
        if ($pct === null) return null;
        $good = $lowerIsBetter ? $pct <= 0 : $pct >= 0;
        return ['pct' => $pct, 'good' => $good, 'icon' => $pct >= 0 ? 'arrow-up-right' : 'arrow-down-right'];
    };
    $dIncome  = $delta($changeIncome);
    $dExpense = $delta($changeExpense, true);
    $dNet     = $delta($changeNet);
    $fmt = fn ($v) => number_format((float) $v, 0);
    $pct = fn ($v) => rtrim(rtrim(number_format(abs($v), 1), '0'), '.');

    $cats = \App\Models\BranchPayment::CATEGORIES;
    $incomeCats = $expenseCats = [];
    foreach ($currentData['byCategory'] as $key => $amount) {
        $meta = $cats[$key] ?? null;
        $row  = ['label' => $meta ? __($meta['label_key']) : $key, 'amount' => (float) $amount];
        if (($meta['type'] ?? 'expense') === 'income') $incomeCats[] = $row; else $expenseCats[] = $row;
    }
    $maxIn  = max(1, collect($incomeCats)->max('amount') ?: 1);
    $maxOut = max(1, collect($expenseCats)->max('amount') ?: 1);
    $maxSvc = max(1, (float) ($topServices->max('revenue') ?: 1));
    $maxEmp = max(1, (float) ($topEmployees->max('revenue') ?: 1));
@endphp

<div class="page-content">

    <x-reports.head :title="__('Profit & Loss Report')" active="profit-loss" :subtitle="$from->translatedFormat('F Y')" />

    @include('company.reports.partials.period')

    {{-- Ledger: income − expenses = net profit --}}
    <section class="rp-ledger" aria-label="{{ __('Profit & Loss Report') }}">
        <div class="rp-fig">
            <div class="rp-fig-label"><i data-feather="arrow-down-circle" aria-hidden="true"></i>{{ __('Total Income') }}</div>
            <div class="rp-fig-value">{{ $fmt($currentData['income']) }}</div>
            @if($dIncome)
                <div class="rp-delta {{ $dIncome['good'] ? 'good' : 'bad' }}">
                    <i data-feather="{{ $dIncome['icon'] }}" aria-hidden="true"></i>
                    <span dir="ltr">{{ $dIncome['pct'] >= 0 ? '+' : '−' }}{{ $pct($dIncome['pct']) }}%</span>
                    <small>{{ __('vs previous month') }}</small>
                </div>
            @endif
        </div>
        <div class="rp-fig">
            <div class="rp-fig-label"><i data-feather="arrow-up-circle" aria-hidden="true"></i>{{ __('Total Expenses') }}</div>
            <div class="rp-fig-value">{{ $fmt($currentData['expense']) }}</div>
            @if($dExpense)
                <div class="rp-delta {{ $dExpense['good'] ? 'good' : 'bad' }}">
                    <i data-feather="{{ $dExpense['icon'] }}" aria-hidden="true"></i>
                    <span dir="ltr">{{ $dExpense['pct'] >= 0 ? '+' : '−' }}{{ $pct($dExpense['pct']) }}%</span>
                    <small>{{ __('vs previous month') }}</small>
                </div>
            @endif
        </div>
        <div class="rp-fig is-net">
            <div class="rp-fig-label"><i data-feather="check-circle" aria-hidden="true"></i>{{ __('Net Profit') }}</div>
            <div class="rp-fig-value {{ $currentData['net'] < 0 ? 'is-neg' : '' }}">{{ $fmt($currentData['net']) }}</div>
            @if($dNet)
                <div class="rp-delta {{ $dNet['good'] ? 'good' : 'bad' }}">
                    <i data-feather="{{ $dNet['icon'] }}" aria-hidden="true"></i>
                    <span dir="ltr">{{ $dNet['pct'] >= 0 ? '+' : '−' }}{{ $pct($dNet['pct']) }}%</span>
                    <small>{{ __('vs previous month') }}</small>
                </div>
            @endif
        </div>
        <div class="rp-fig">
            <div class="rp-fig-label"><i data-feather="percent" aria-hidden="true"></i>{{ __('Profit Margin') }}</div>
            <div class="rp-fig-value">{{ $currentData['margin'] }}%</div>
            <div class="rp-sub">{{ $currentData['appointmentCount'] }} {{ __('appointments') }}</div>
        </div>
    </section>

    {{-- Daily chart --}}
    <div class="rp-panel mb-4" style="height:auto;">
        <div class="rp-panel-head"><h2 class="rp-panel-title">{{ __('Daily Breakdown') }}</h2></div>
        <div class="rp-panel-body">
            <div class="rp-chart-wrap" role="img"
                 aria-label="{{ __('Daily Breakdown') }}: {{ __('Income') }} {{ $fmt($currentData['income']) }}, {{ __('Expenses') }} {{ $fmt($currentData['expense']) }}, {{ __('Net Profit') }} {{ $fmt($currentData['net']) }}">
                <canvas id="plChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- By category --}}
        <div class="col-lg-4">
            <div class="rp-panel">
                <div class="rp-panel-head"><h2 class="rp-panel-title">{{ __('By Category') }}</h2></div>
                <div class="rp-panel-body">
                    @if($incomeCats)
                        <p class="rp-group">{{ __('Income') }}</p>
                        <ul class="rp-list">
                            @foreach($incomeCats as $r)
                                <li>
                                    <div class="rp-item-top"><span class="rp-name">{{ $r['label'] }}</span><span class="rp-amt">{{ $fmt($r['amount']) }}</span></div>
                                    <div class="rp-bar-track"><div class="rp-bar-fill" style="width:{{ max(2, round($r['amount'] / $maxIn * 100)) }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if($expenseCats)
                        <p class="rp-group">{{ __('Expenses') }}</p>
                        <ul class="rp-list">
                            @foreach($expenseCats as $r)
                                <li>
                                    <div class="rp-item-top"><span class="rp-name">{{ $r['label'] }}</span><span class="rp-amt">{{ $fmt($r['amount']) }}</span></div>
                                    <div class="rp-bar-track"><div class="rp-bar-fill is-out" style="width:{{ max(2, round($r['amount'] / $maxOut * 100)) }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @unless($incomeCats || $expenseCats)
                        <p class="rp-empty">{{ __('No data') }}</p>
                    @endunless
                    @if($currentData['payrollTotal'] > 0)
                        <div class="rp-total"><span>{{ __('Payroll Total') }}</span><span>{{ $fmt($currentData['payrollTotal']) }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top services --}}
        <div class="col-lg-4">
            <div class="rp-panel">
                <div class="rp-panel-head"><h2 class="rp-panel-title">{{ __('Top Services') }}</h2></div>
                <div class="rp-panel-body">
                    @forelse($topServices as $i => $svc)
                        @if($loop->first)<ul class="rp-list is-ranked">@endif
                            <li>
                                <div class="rp-item-top">
                                    <span class="rp-rank">{{ $i + 1 }}</span>
                                    <span class="rp-name" title="{{ $svc['name'] }}">{{ $svc['name'] }}<small>({{ $svc['count'] }})</small></span>
                                    <span class="rp-amt">{{ $fmt($svc['revenue']) }}</span>
                                </div>
                                <div class="rp-bar-track"><div class="rp-bar-fill" style="width:{{ max(2, round($svc['revenue'] / $maxSvc * 100)) }}%"></div></div>
                            </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <p class="rp-empty">{{ __('No data') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top employees --}}
        <div class="col-lg-4">
            <div class="rp-panel">
                <div class="rp-panel-head"><h2 class="rp-panel-title">{{ __('Top Employees') }}</h2></div>
                <div class="rp-panel-body">
                    @forelse($topEmployees as $i => $emp)
                        @if($loop->first)<ul class="rp-list is-ranked">@endif
                            <li>
                                <div class="rp-item-top">
                                    <span class="rp-rank">{{ $i + 1 }}</span>
                                    <span class="rp-name" title="{{ $emp['name'] }}">{{ $emp['name'] }}<small>({{ $emp['count'] }})</small></span>
                                    <span class="rp-amt">{{ $fmt($emp['revenue']) }}</span>
                                </div>
                                <div class="rp-bar-track"><div class="rp-bar-fill" style="width:{{ max(2, round($emp['revenue'] / $maxEmp * 100)) }}%"></div></div>
                            </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <p class="rp-empty">{{ __('No data') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Month-over-month --}}
    <div class="rp-panel" style="height:auto;">
        <div class="rp-panel-head"><h2 class="rp-panel-title">{{ __('Month-over-Month Comparison') }}</h2></div>
        <div class="rp-panel-body p-0 pt-3">
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th scope="col"><span class="visually-hidden">{{ __('Item') }}</span></th>
                            <th scope="col" class="num">{{ $from->copy()->subMonth()->translatedFormat('F Y') }}</th>
                            <th scope="col" class="num">{{ $from->translatedFormat('F Y') }}</th>
                            <th scope="col" class="num">{{ __('Change') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            [__('Income'),   $prevData['income'],  $currentData['income'],  $dIncome],
                            [__('Expenses'), $prevData['expense'], $currentData['expense'], $dExpense],
                        ] as [$label, $before, $now, $d])
                            <tr>
                                <th scope="row" class="text-start fw-semibold">{{ $label }}</th>
                                <td class="num rp-muted">{{ $fmt($before) }}</td>
                                <td class="num fw-bold">{{ $fmt($now) }}</td>
                                <td class="num">
                                    @if($d)<span class="{{ $d['good'] ? 'rp-pos' : 'rp-neg' }}" dir="ltr">{{ $d['pct'] >= 0 ? '+' : '−' }}{{ $pct($d['pct']) }}%</span>@else<span class="rp-muted">—</span>@endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="is-total">
                            <th scope="row" class="text-start">{{ __('Net Profit') }}</th>
                            <td class="num {{ $prevData['net'] < 0 ? 'rp-neg' : '' }}">{{ $fmt($prevData['net']) }}</td>
                            <td class="num {{ $currentData['net'] < 0 ? 'rp-neg' : '' }}">{{ $fmt($currentData['net']) }}</td>
                            <td class="num">
                                @if($dNet)<span class="{{ $dNet['good'] ? 'rp-pos' : 'rp-neg' }}" dir="ltr">{{ $dNet['pct'] >= 0 ? '+' : '−' }}{{ $pct($dNet['pct']) }}%</span>@else<span class="rp-muted">—</span>@endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="text-start fw-semibold">{{ __('Appointments') }}</th>
                            <td class="num rp-muted">{{ $prevData['appointmentCount'] }}</td>
                            <td class="num fw-bold">{{ $currentData['appointmentCount'] }}</td>
                            <td class="num"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('plChart');
    if (!el || typeof Chart === 'undefined') return;

    // Colours come from the theme tokens so the chart follows light / dark.
    var cs = getComputedStyle(document.documentElement);
    var themed = document.querySelector('.bk-theme-dark, .bk-theme-light') || document.body;
    var ts = getComputedStyle(themed);
    function tok(name, fallback) { return (ts.getPropertyValue(name) || cs.getPropertyValue(name) || '').trim() || fallback; }
    var cIncome = tok('--bk-accent-fill', '#4B5D34');
    var cOut    = tok('--bk-gold', '#C9A96A');
    var cNet    = tok('--bk-text', '#22251D');
    var cText   = tok('--bk-text-muted', '#6b6f63');
    var cGrid   = tok('--bk-border', 'rgba(0,0,0,.08)');
    var isRtl   = document.documentElement.dir === 'rtl';
    var nf = new Intl.NumberFormat(document.documentElement.lang || undefined, { maximumFractionDigits: 0 });

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = cText;

    new Chart(el.getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: @json(__('Income')),     data: @json($chartIncome),  backgroundColor: cIncome, borderRadius: 4, maxBarThickness: 18 },
                { label: @json(__('Expenses')),   data: @json($chartExpense), backgroundColor: cOut,    borderRadius: 4, maxBarThickness: 18 },
                { label: @json(__('Net Profit')), data: @json($chartProfit),  type: 'line', borderColor: cNet, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: .25, fill: false }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 8 } },
                y: { beginAtZero: true, grid: { color: cGrid }, border: { display: false }, ticks: { callback: function (v) { return nf.format(v); } } }
            },
            plugins: {
                legend: { position: 'top', align: 'end', rtl: isRtl, labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, padding: 16 } },
                tooltip: { rtl: isRtl, callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + nf.format(c.parsed.y); } } }
            }
        }
    });
});
</script>
@endpush
@endsection
