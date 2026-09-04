@extends('company.dashboard')
@section('content')

@php
    $anyLow  = collect($walletCards)->contains(fn ($c) => $c['wallet']->isLow());
    $anyZero = collect($walletCards)->contains(fn ($c) => $c['wallet']->remaining() <= 0 && $c['wallet']->total_purchased > 0);
    $usedPct = $totals['allocated'] > 0 ? min(100, round($totals['used'] / $totals['allocated'] * 100)) : 0;
@endphp

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('company.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('SMS') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Overview') }}</h1>
            <p class="sx-subtitle">{{ __('Your SMS balance, per branch. Credits are managed by GlowRez — enable automations to start using them.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('company.sms.automations') }}" class="sx-btn sx-btn-ghost"><i data-feather="zap"></i>{{ __('Automations') }}</a>
            <a href="{{ route('company.sms.purchase') }}" class="sx-btn sx-btn-primary"><i data-feather="shopping-bag"></i>{{ __('Purchase SMS') }}</a>
        </div>
    </header>

    @include('company.partials.flash')

    {{-- Zero / low balance banner --}}
    @if($anyZero)
        <div class="sx-note sx-note-danger sx-reveal" style="margin-bottom:18px; align-items:center; justify-content:space-between;">
            <span style="display:flex; align-items:center; gap:10px;"><i data-feather="alert-octagon"></i>{{ __('One or more branches have run out of SMS. Automations for them are paused.') }}</span>
            <a href="{{ route('company.sms.purchase') }}" class="sx-btn sx-btn-danger sx-btn-sm" style="background:var(--bk-surface);"><i data-feather="shopping-bag"></i>{{ __('Purchase SMS') }}</a>
        </div>
    @elseif($anyLow)
        <div class="sx-note sx-note-warn sx-reveal" style="margin-bottom:18px; align-items:center; justify-content:space-between;">
            <span style="display:flex; align-items:center; gap:10px;"><i data-feather="alert-triangle"></i>{{ __('A branch is running low on SMS credits.') }}</span>
            <a href="{{ route('company.sms.purchase') }}" class="sx-btn sx-btn-gold sx-btn-sm"><i data-feather="shopping-bag"></i>{{ __('Top up') }}</a>
        </div>
    @endif

    {{-- Company totals --}}
    <section class="sx-stats sx-reveal">
        <div class="sx-stat" style="--accent:var(--bk-success)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Remaining') }}</span><span class="sx-stat-ic is-success"><i data-feather="battery-charging"></i></span></div>
            <span class="sx-stat-value">{{ number_format($totals['remaining']) }}</span>
            <span class="sx-stat-sub">{{ __('SMS available now') }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-gold-strong)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Used') }}</span><span class="sx-stat-ic is-gold"><i data-feather="activity"></i></span></div>
            <span class="sx-stat-value">{{ number_format($totals['used']) }}</span>
            <span class="sx-stat-sub">{{ __(':pct% of allocated', ['pct' => $usedPct]) }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-accent)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Allocated') }}</span><span class="sx-stat-ic"><i data-feather="inbox"></i></span></div>
            <span class="sx-stat-value">{{ number_format($totals['allocated']) }}</span>
            <span class="sx-stat-sub">{{ __('Total credits received') }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-accent)">
            <div class="sx-stat-top"><span class="sx-stat-label">{{ __('Automation sends') }}</span><span class="sx-stat-ic"><i data-feather="send"></i></span></div>
            <span class="sx-stat-value">{{ number_format(array_sum($byType)) }}</span>
            <span class="sx-stat-sub">{{ __(':c conf · :r rem · :f follow', ['c' => $byType['confirmation'], 'r' => $byType['reminder'], 'f' => $byType['followup']]) }}</span>
        </div>
    </section>

    {{-- Per-branch balance cards --}}
    <h2 class="sx-card-title sx-reveal" style="margin:22px 0 14px;">{{ __('Balance by branch') }}</h2>
    <div class="sx-reveal" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px;">

        {{-- Company pool card --}}
        @include('company.sms.partials.wallet-card', ['branch' => null, 'wallet' => $pool])

        @foreach($walletCards as $c)
            @include('company.sms.partials.wallet-card', ['branch' => $c['branch'], 'wallet' => $c['wallet']])
        @endforeach
    </div>
</div>

@push('company-styles')
    @include('company.sms.partials.styles')
@endpush

@push('scripts')
<script>
(function () {
    // Toggle inline alert-settings form on a wallet card.
    document.querySelectorAll('[data-alert-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var box = document.getElementById(btn.getAttribute('data-alert-toggle'));
            if (box) box.style.display = box.style.display === 'none' ? 'block' : 'none';
        });
    });
})();
</script>
@endpush

@endsection
