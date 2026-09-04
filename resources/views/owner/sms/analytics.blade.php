@extends('owner.dashboard')
@section('content')

@php
    $totalSent = array_sum($statusBreakdown);
    $sentOnly  = (int) $statusBreakdown['sent'];
    // Daily totals across all types → chart scale.
    $dailyTotals = [];
    foreach ($days as $i => $d) {
        $sum = 0; foreach ($types as $t) { $sum += $series[$t][$i] ?? 0; }
        $dailyTotals[$i] = $sum;
    }
    $peak = max(1, max($dailyTotals ?: [0]));
    $totalSegments = array_sum(array_map(fn($t) => $byType[$t]['segments'], $types));

    // Donut segments (sent / failed / skipped / queued).
    $donut = [
        ['label' => __('Sent'),    'val' => $statusBreakdown['sent'],    'color' => 'var(--bk-success)'],
        ['label' => __('Failed'),  'val' => $statusBreakdown['failed'],  'color' => 'var(--bk-danger)'],
        ['label' => __('Skipped'), 'val' => $statusBreakdown['skipped'], 'color' => 'var(--bk-warning)'],
        ['label' => __('Queued'),  'val' => $statusBreakdown['queued'],  'color' => 'var(--bk-text-muted)'],
    ];
    $donutTotal = max(1, array_sum(array_column($donut, 'val')));
    $acc = 0; $stops = [];
    foreach ($donut as $seg) {
        $from = round($acc / $donutTotal * 100, 2);
        $acc += $seg['val'];
        $to = round($acc / $donutTotal * 100, 2);
        if ($seg['val'] > 0) $stops[] = "{$seg['color']} {$from}% {$to}%";
    }
    $donutCss = count($stops) ? implode(', ', $stops) : 'var(--bk-bg) 0% 100%';

    $typeMeta = [
        'confirmation' => ['label' => __('Confirmation'), 'icon' => 'check-circle', 'bar' => 'sx-bar-confirmation', 'color' => 'var(--bk-success)'],
        'reminder'     => ['label' => __('Reminder'),     'icon' => 'clock',        'bar' => 'sx-bar-reminder',     'color' => 'var(--bk-gold-strong)'],
        'followup'     => ['label' => __('Follow-up'),    'icon' => 'refresh-cw',   'bar' => 'sx-bar-followup',     'color' => 'var(--bk-accent)'],
        'manual'       => ['label' => __('Manual'),       'icon' => 'edit-3',       'bar' => 'sx-bar-manual',       'color' => 'var(--bk-text-muted)'],
    ];
@endphp

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Insights') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Analytics') }}</h1>
            <p class="sx-subtitle">{{ __('Delivery, segments and automation mix over the last 30 days — from the GlowRez message log.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('owner.sms.logs') }}" class="sx-btn sx-btn-ghost"><i data-feather="list"></i>{{ __('Message logs') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <section class="sx-stats sx-reveal">
        <div class="sx-stat" style="--accent:var(--bk-success)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Sent') }}</span><span class="sx-stat-ic is-success"><i data-feather="check-circle"></i></span></div>
            <span class="sx-stat-value">{{ number_format($sentOnly) }}</span>
            <span class="sx-stat-sub">{{ __('Delivered to provider') }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-gold-strong)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Segments') }}</span><span class="sx-stat-ic is-gold"><i data-feather="layers"></i></span></div>
            <span class="sx-stat-value">{{ number_format($totalSegments) }}</span>
            <span class="sx-stat-sub">{{ __('Credits consumed') }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-danger)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Failed') }}</span><span class="sx-stat-ic is-danger"><i data-feather="alert-triangle"></i></span></div>
            <span class="sx-stat-value">{{ number_format($statusBreakdown['failed']) }}</span>
            <span class="sx-stat-sub">{{ __('Refunded, not charged') }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-warning)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Skipped') }}</span><span class="sx-stat-ic"><i data-feather="slash"></i></span></div>
            <span class="sx-stat-value">{{ number_format($statusBreakdown['skipped']) }}</span>
            <span class="sx-stat-sub">{{ __('No credits / opted out') }}</span>
        </div>
    </section>

    <div class="sx-grid sx-grid-2 sx-reveal">
        {{-- Trend --}}
        <div class="sx-card">
            <div class="sx-card-head">
                <div>
                    <h2 class="sx-card-title">{{ __('30-day trend') }}</h2>
                    <p class="sx-card-note">{{ __('Sent messages per day, by automation') }}</p>
                </div>
                <div style="display:flex; gap:14px; flex-wrap:wrap;">
                    @foreach(['confirmation','reminder','followup'] as $t)
                        <span class="sx-legend"><span class="sx-dot" style="background:{{ $typeMeta[$t]['color'] }}"></span>{{ $typeMeta[$t]['label'] }}</span>
                    @endforeach
                </div>
            </div>
            <div class="sx-card-pad">
                @if($totalSent === 0)
                    <div class="sx-empty" style="padding:40px 20px;">
                        <span class="sx-empty-ic"><i data-feather="bar-chart-2"></i></span>
                        <h3 class="sx-empty-title">{{ __('No data yet') }}</h3>
                        <p class="sx-empty-text">{{ __('Sent SMS will chart here as automations run.') }}</p>
                    </div>
                @else
                    <div class="sx-bars">
                        @foreach($days as $i => $d)
                            <div class="sx-bars-col" title="{{ \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M') }} — {{ $dailyTotals[$i] }}">
                                <div class="sx-bars-stack" style="height:{{ $peak > 0 ? round($dailyTotals[$i] / $peak * 100) : 0 }}%;">
                                    @foreach(['confirmation','reminder','followup','manual'] as $t)
                                        @php $v = $series[$t][$i] ?? 0; @endphp
                                        @if($v > 0)
                                            <div class="sx-bar {{ $typeMeta[$t]['bar'] }}" style="height:{{ $dailyTotals[$i] > 0 ? round($v / $dailyTotals[$i] * 100) : 0 }}%"></div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="sx-bars-x">
                        @foreach($days as $i => $d)
                            <span>{{ $i % 5 === 0 ? \Illuminate\Support\Carbon::parse($d)->format('d/m') : '' }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Status donut --}}
        <div class="sx-card">
            <div class="sx-card-head"><h2 class="sx-card-title">{{ __('Delivery status') }}</h2></div>
            <div class="sx-card-pad" style="display:flex; align-items:center; gap:24px; flex-wrap:wrap; justify-content:center;">
                <div class="sx-donut" style="background:conic-gradient({{ $donutCss }});">
                    <div class="sx-donut-center">
                        <strong>{{ number_format($totalSent) }}</strong>
                        <span>{{ __('Total') }}</span>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach($donut as $seg)
                        <span class="sx-legend"><span class="sx-dot" style="background:{{ $seg['color'] }}"></span>{{ $seg['label'] }} — <strong class="sx-mono">&nbsp;{{ number_format($seg['val']) }}</strong></span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- By automation type --}}
    <section class="sx-stats sx-reveal" style="margin-top:22px; grid-template-columns:repeat(3,1fr);">
        @foreach(['confirmation','reminder','followup'] as $t)
            <div class="sx-stat" style="--accent:{{ $typeMeta[$t]['color'] }}">
                <div class="sx-stat-top">
                    <span class="sx-stat-label">{{ $typeMeta[$t]['label'] }}</span>
                    <span class="sx-stat-ic" style="background:color-mix(in srgb, {{ $typeMeta[$t]['color'] }} 14%, transparent); color:{{ $typeMeta[$t]['color'] }};"><i data-feather="{{ $typeMeta[$t]['icon'] }}"></i></span>
                </div>
                <span class="sx-stat-value">{{ number_format($byType[$t]['messages']) }}</span>
                <span class="sx-stat-sub">{{ __(':n segments', ['n' => number_format($byType[$t]['segments'])]) }}</span>
            </div>
        @endforeach
    </section>
</div>

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@endsection
