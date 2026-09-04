@extends('owner.dashboard')
@section('content')

@php
    $distributed = (int) $totals['distributed'];
    $used        = (int) $totals['used'];
    $remaining   = (int) $totals['remaining'];
    $usedPct     = $distributed > 0 ? min(100, round($used / $distributed * 100)) : 0;
    $remPct      = $distributed > 0 ? max(0, 100 - $usedPct) : 0;
@endphp

<div class="page-content sx">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('SMS credits') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Overview') }}</h1>
            <p class="sx-subtitle">{{ __('GlowRez credits are the balance you distribute to companies and branches. They are tracked entirely inside GlowRez — separate from the Rassel provider balance shown for reference below.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('owner.sms.analytics') }}" class="sx-btn sx-btn-ghost">
                <i data-feather="trending-up"></i>{{ __('Analytics') }}
            </a>
            <button type="button" class="sx-btn sx-btn-primary" data-bs-toggle="modal" data-bs-target="#sxGrantModal">
                <i data-feather="plus"></i>{{ __('Add free SMS') }}
            </button>
        </div>
    </header>

    @include('owner.partials.flash')

    {{-- ═══════════ HERO STATS (GlowRez credits) ═══════════ --}}
    <section class="sx-stats sx-reveal">
        <div class="sx-stat" style="--accent:var(--bk-accent)">
            <div class="sx-stat-top">
                <span class="sx-stat-label">{{ __('Distributed') }}</span>
                <span class="sx-stat-ic"><i data-feather="send"></i></span>
            </div>
            <span class="sx-stat-value">{{ number_format($distributed) }}</span>
            <span class="sx-stat-sub">{{ __('Total credits allocated to companies') }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-gold-strong)">
            <div class="sx-stat-top">
                <span class="sx-stat-label">{{ __('Used') }}</span>
                <span class="sx-stat-ic is-gold"><i data-feather="activity"></i></span>
            </div>
            <span class="sx-stat-value">{{ number_format($used) }}</span>
            <span class="sx-stat-sub">{{ __(':pct% of distributed', ['pct' => $usedPct]) }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-success)">
            <div class="sx-stat-top">
                <span class="sx-stat-label">{{ __('Remaining') }}</span>
                <span class="sx-stat-ic is-success"><i data-feather="battery-charging"></i></span>
            </div>
            <span class="sx-stat-value">{{ number_format($remaining) }}</span>
            <span class="sx-stat-sub">{{ __(':n companies with credits', ['n' => $companiesWithCredits]) }}</span>
        </div>
        <div class="sx-stat" style="--accent:var(--bk-accent)">
            <div class="sx-stat-top">
                <span class="sx-stat-label">{{ __('Messages sent') }}</span>
                <span class="sx-stat-ic"><i data-feather="message-circle"></i></span>
            </div>
            <span class="sx-stat-value">{{ number_format($messageStats['sent']) }}</span>
            <span class="sx-stat-sub">{{ __(':n segments consumed', ['n' => number_format($messageStats['segments'])]) }}</span>
        </div>
    </section>

    {{-- ═══════════ CREDITS METER ═══════════ --}}
    <section class="sx-card sx-reveal" style="margin-bottom:22px;">
        <div class="sx-card-pad">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:14px;">
                <h2 class="sx-card-title">{{ __('GlowRez credit pool') }}</h2>
                <span class="sx-chip"><i data-feather="database"></i>{{ __('GlowRez ledger') }}</span>
            </div>
            <div class="sx-meter" role="img" aria-label="{{ __('Used :u of :d', ['u' => $used, 'd' => $distributed]) }}">
                <span class="sx-meter-fill" style="width:{{ $usedPct }}%"></span>
            </div>
            <div class="sx-meter-legend">
                <span class="sx-legend"><span class="sx-dot" style="background:var(--bk-accent)"></span>{{ __('Used') }} — <strong class="sx-mono">&nbsp;{{ number_format($used) }}</strong></span>
                <span class="sx-legend"><span class="sx-dot" style="background:var(--bk-bg); border:1px solid var(--bk-border)"></span>{{ __('Remaining') }} — <strong class="sx-mono">&nbsp;{{ number_format($remaining) }}</strong></span>
            </div>
        </div>
    </section>

    {{-- ═══════════ CAPACITY GUARD (Rassel real capacity vs distributed) ═══════════ --}}
    <section class="sx-card sx-reveal {{ ($capacityMeta['over'] ?? false) ? 'sx-cap-over' : '' }}" style="margin-bottom:22px; {{ ($capacityMeta['over'] ?? false) ? 'border-color:color-mix(in srgb,var(--bk-danger) 45%, var(--bk-border));' : '' }}">
        <div class="sx-card-head">
            <div>
                <h2 class="sx-card-title">{{ __('Sending capacity') }}</h2>
                <p class="sx-card-note">{{ __('What Rassel can actually deliver this cycle vs. the GlowRez credits companies still hold') }}</p>
            </div>
            @if($capacityMeta['available'] ?? false)
                @if($capacityMeta['over'])
                    <span class="sx-pill sx-pill-failed"><i data-feather="alert-triangle" style="width:13px;height:13px;"></i>{{ __('Over-distributed') }}</span>
                @else
                    <span class="sx-pill sx-pill-sent"><i data-feather="check" style="width:13px;height:13px;"></i>{{ __('Within capacity') }}</span>
                @endif
            @endif
        </div>

        @if(! ($capacityMeta['available'] ?? false))
            <div class="sx-card-pad">
                <div class="sx-note sx-note-warn"><i data-feather="wifi-off"></i><span>{{ __('Rassel capacity is unavailable right now, so it cannot be compared. GlowRez credits are unaffected.') }}</span></div>
            </div>
        @else
            <div class="sx-card-pad">
                <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:20px; align-items:center;">
                    {{-- Outstanding GlowRez --}}
                    <div>
                        <span class="sx-stat-label">{{ __('GlowRez credits outstanding') }}</span>
                        <span style="display:block; margin-top:6px; font-family:var(--sx-display); font-size:2rem; font-weight:600; color:var(--bk-text); font-variant-numeric:tabular-nums;">{{ number_format($capacityMeta['outstanding']) }}</span>
                        <span class="sx-sub">{{ __('Credits companies can still spend') }}</span>
                    </div>
                    <div style="font-family:var(--sx-display); font-size:1.5rem; color:var(--bk-text-muted);">{{ $capacityMeta['over'] ? '>' : '≤' }}</div>
                    {{-- Rassel capacity --}}
                    <div style="text-align:end;">
                        <span class="sx-stat-label">{{ __('Rassel real capacity') }}</span>
                        <span style="display:block; margin-top:6px; font-family:var(--sx-display); font-size:2rem; font-weight:600; color:var(--bk-gold-strong); font-variant-numeric:tabular-nums;">{{ number_format($capacityMeta['capacity']) }}</span>
                        <span class="sx-sub">{{ number_format($capacityMeta['plan_remaining']) }} {{ __('plan') }} + {{ number_format($capacityMeta['grant_remaining']) }} {{ __('grant') }} · {{ __('segments') }}</span>
                    </div>
                </div>

                {{-- Comparison bar --}}
                <div class="sx-meter" style="margin-top:16px; {{ $capacityMeta['over'] ? 'border-color:color-mix(in srgb,var(--bk-danger) 40%, var(--bk-border));' : '' }}">
                    <span class="sx-meter-fill {{ $capacityMeta['over'] ? '' : 'is-gold' }}" style="width:{{ $capacityMeta['used_pct'] }}%; {{ $capacityMeta['over'] ? 'background:linear-gradient(90deg,var(--bk-danger),color-mix(in srgb,var(--bk-danger) 60%, #E08A8A));' : '' }}"></span>
                </div>

                @if($capacityMeta['over'])
                    <div class="sx-note sx-note-danger" style="margin-top:14px;">
                        <i data-feather="alert-octagon"></i>
                        <span>{{ __('You have distributed :n more credits than Rassel can send this cycle. Some sends may fail until the cycle resets or you add provider capacity — avoid granting more for now.', ['n' => number_format($capacityMeta['over_by'])]) }}</span>
                    </div>
                @else
                    <div class="sx-note sx-note-info" style="margin-top:14px;">
                        <i data-feather="info"></i>
                        <span>{{ __('Rassel bills by segment (an Arabic SMS is often 2–3 segments), so distribute GlowRez credits with room to spare against this capacity.') }}</span>
                    </div>
                @endif
            </div>
        @endif
    </section>

    <div class="sx-grid sx-grid-2 sx-reveal">

        {{-- ─────── LEFT: top consumers + recent ledger ─────── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="sx-card">
                <div class="sx-card-head">
                    <div>
                        <h2 class="sx-card-title">{{ __('Top consumers') }}</h2>
                        <p class="sx-card-note">{{ __('Companies by credits used') }}</p>
                    </div>
                    <a href="{{ route('owner.sms.companies') }}" class="sx-chip"><i data-feather="arrow-right"></i>{{ __('All companies') }}</a>
                </div>
                @if($topConsumers->isEmpty())
                    <div class="sx-empty">
                        <span class="sx-empty-ic"><i data-feather="bar-chart-2"></i></span>
                        <h3 class="sx-empty-title">{{ __('No usage yet') }}</h3>
                        <p class="sx-empty-text">{{ __('Once companies start sending SMS, their consumption will appear here.') }}</p>
                    </div>
                @else
                    <div class="sx-table-scroll">
                        <table class="sx-table" style="min-width:0;">
                            <thead><tr>
                                <th>{{ __('Company') }}</th>
                                <th class="num">{{ __('Used') }}</th>
                            </tr></thead>
                            <tbody>
                            @php $maxUsed = max(1, (int) $topConsumers->max('used')); @endphp
                            @foreach($topConsumers as $c)
                                <tr>
                                    <td>
                                        <div class="sx-idcell">
                                            <span class="sx-ava">{{ mb_substr($c->localizedName() ?: 'G', 0, 1) }}</span>
                                            <div>
                                                <div class="sx-name">{{ $c->localizedName() }}</div>
                                                <div class="sx-ubar"><span style="width:{{ round($c->used / $maxUsed * 100) }}%"></span></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num sx-mono"><strong>{{ number_format((int) $c->used) }}</strong></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="sx-card">
                <div class="sx-card-head">
                    <div>
                        <h2 class="sx-card-title">{{ __('Recent ledger activity') }}</h2>
                        <p class="sx-card-note">{{ __('Latest credit movements') }}</p>
                    </div>
                    <a href="{{ route('owner.sms.transactions') }}" class="sx-chip"><i data-feather="arrow-right"></i>{{ __('Full ledger') }}</a>
                </div>
                @if($recentTx->isEmpty())
                    <div class="sx-empty">
                        <span class="sx-empty-ic"><i data-feather="repeat"></i></span>
                        <h3 class="sx-empty-title">{{ __('No transactions yet') }}</h3>
                        <p class="sx-empty-text">{{ __('Grant credits to a company to start the ledger.') }}</p>
                    </div>
                @else
                    <div class="sx-table-scroll">
                        <table class="sx-table" style="min-width:0;">
                            <tbody>
                            @foreach($recentTx as $t)
                                @include('owner.sms.partials.tx-row', ['t' => $t, 'compact' => true])
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ─────── RIGHT: low balance + Rassel provider ─────── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            <div class="sx-card">
                <div class="sx-card-head">
                    <h2 class="sx-card-title">{{ __('Low balance') }}</h2>
                    <span class="sx-chip"><i data-feather="alert-triangle"></i>{{ $lowWallets->count() }}</span>
                </div>
                @if($lowWallets->isEmpty())
                    <div class="sx-card-pad">
                        <div class="sx-note sx-note-info">
                            <i data-feather="check-circle"></i>
                            <span>{{ __('No wallets are below their low-balance threshold.') }}</span>
                        </div>
                    </div>
                @else
                    <div class="sx-table-scroll">
                        <table class="sx-table" style="min-width:0;">
                            <tbody>
                            @foreach($lowWallets as $w)
                                <tr>
                                    <td>
                                        <div class="sx-name">{{ $w->company?->localizedName() }}</div>
                                        <div class="sx-sub">{{ $w->branch?->localizedName() ?? __('Company pool') }}</div>
                                    </td>
                                    <td class="num"><span class="sx-pill sx-pill-skipped">{{ number_format($w->remaining()) }} {{ __('left') }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Rassel provider — reference / monitoring ONLY --}}
            <div class="sx-provider">
                <div class="sx-card-head">
                    <div>
                        <h2 class="sx-card-title">{{ __('Rassel provider') }}</h2>
                        <p class="sx-card-note">{{ __('The sending provider\'s own account') }}</p>
                    </div>
                    <span class="sx-ref-tag"><i data-feather="eye"></i>{{ __('Reference only') }}</span>
                </div>

                @if(!($provider['configured'] ?? false))
                    <div class="sx-card-pad">
                        <div class="sx-note sx-note-warn">
                            <i data-feather="alert-circle"></i>
                            <span>{{ __('Rassel API key is not configured, so provider status is unavailable.') }}</span>
                        </div>
                    </div>
                @elseif(!($provider['ok'] ?? false))
                    <div class="sx-card-pad">
                        <div class="sx-note sx-note-danger">
                            <i data-feather="wifi-off"></i>
                            <span>{{ __('Could not reach Rassel right now. This does not affect GlowRez credits.') }}</span>
                        </div>
                    </div>
                @else
                    <div class="sx-prov-grid">
                        <div class="sx-prov-cell">
                            <span class="sx-prov-label">{{ __('Remaining segments') }}</span>
                            <span class="sx-prov-value">{{ number_format($provider['remaining_segments']) }}</span>
                        </div>
                        <div class="sx-prov-cell">
                            <span class="sx-prov-label">{{ __('Effective limit') }}</span>
                            <span class="sx-prov-value">{{ number_format($provider['effective_limit']) }}
                                <small>({{ number_format($provider['period_limit']) }}+{{ number_format($provider['bonus']) }})</small></span>
                        </div>
                        <div class="sx-prov-cell">
                            <span class="sx-prov-label">{{ __('Free grant left') }}</span>
                            <span class="sx-prov-value">{{ number_format($provider['free_grant']['remaining']) }}
                                <small>/ {{ number_format($provider['free_grant']['granted']) }}</small></span>
                        </div>
                        <div class="sx-prov-cell">
                            <span class="sx-prov-label">{{ __('Plan') }}</span>
                            <span class="sx-prov-value" style="font-size:1.05rem; line-height:1.3;">{{ $provider['plan_name'] ?? '—' }}
                                <small style="display:block;">{{ $provider['can_send'] ? __('Active') : __('Inactive') }}</small></span>
                        </div>
                    </div>

                    {{-- Free message grants from Rassel — per-grant detail --}}
                    @if(!empty($provider['free_grants']))
                        @php
                            $chLabel = ['sms_syria' => __('SMS Syria'), 'sms_local' => __('Local SMS'), 'whatsapp' => 'WhatsApp'];
                            $pkLabel = ['sms_mtn' => 'MTN', 'sms_syriatel' => 'Syriatel'];
                        @endphp
                        <div class="sx-card-pad" style="padding-bottom:6px;">
                            <div class="sx-name" style="margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                                <i data-feather="gift" style="width:15px;height:15px; color:var(--bk-gold-strong);"></i>{{ __('Free message grants') }}
                            </div>
                            @foreach($provider['free_grants'] as $g)
                                @php $gpct = $g['granted'] > 0 ? min(100, round($g['consumed'] / $g['granted'] * 100)) : 0; @endphp
                                <div style="padding:12px 0; border-bottom:1px solid var(--bk-border);">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <span class="sx-chip">{{ $chLabel[$g['channel']] ?? $g['channel'] }}</span>
                                            @if($g['provider_key'])<span class="sx-sub">{{ $pkLabel[$g['provider_key']] ?? strtoupper(str_replace('sms_','',$g['provider_key'])) }}</span>@endif
                                            @if($g['status'] === 'active')<span class="sx-pill sx-pill-sent" style="padding:2px 8px;">{{ __('Active') }}</span>@endif
                                        </div>
                                        <div class="sx-sub sx-mono">
                                            <strong style="color:var(--bk-text); font-size:1rem;">{{ number_format($g['remaining']) }}</strong> {{ __('left') }} / {{ number_format($g['granted']) }}
                                        </div>
                                    </div>
                                    <div class="sx-meter" style="margin-top:8px; height:8px;">
                                        <span class="sx-meter-fill is-gold" style="width:{{ $gpct }}%"></span>
                                    </div>
                                    <div class="sx-meter-legend" style="margin-top:8px; gap:14px;">
                                        <span class="sx-legend" style="font-size:.76rem;">{{ __('Used') }} <strong class="sx-mono">&nbsp;{{ number_format($g['consumed']) }}</strong></span>
                                        @if($g['expires_at'])
                                            <span class="sx-legend" style="font-size:.76rem;"><i data-feather="clock" style="width:12px;height:12px;"></i>{{ __('Expires') }} {{ \Illuminate\Support\Carbon::parse($g['expires_at'])->translatedFormat('d M Y') }}</span>
                                        @endif
                                        @if($g['reason'])<span class="sx-legend" style="font-size:.76rem; color:var(--bk-text-muted);">{{ $g['reason'] }}</span>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="sx-card-pad" style="padding-top:14px;">
                        <div class="sx-note sx-note-info">
                            <i data-feather="info"></i>
                            <span>{{ __('This is Rassel\'s balance for monitoring. Branch balances are GlowRez credits, managed independently.') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('owner.sms.partials.grant-modal')

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@include('owner.sms.partials.grant-script')

@endsection
