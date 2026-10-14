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

<div class="page-content dx">

{{-- ════ SUBSCRIPTION EXPIRY BANNER ════ --}}
@php
    $bkSubDays = $company->plan_id !== null && $company->plan_expires_at
        ? (int) now()->startOfDay()->diffInDays($company->plan_expires_at, false)
        : null;
@endphp
@if ($company->plan_id !== null && ! $company->isSubscriptionActive())
    <div class="alert alert-danger d-flex align-items-center gap-2 rounded-4 mb-3">
        <i data-feather="alert-triangle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <div>
            <strong>{{ __('Your subscription has expired.') }}</strong>
            {{ __('Some features are disabled until you renew.') }}
            <a href="{{ route('company.profile.show') }}" class="alert-link">{{ __('View my subscription') }}</a>
        </div>
    </div>
@elseif ($bkSubDays !== null && $bkSubDays <= 7)
    <div class="alert alert-warning d-flex align-items-center gap-2 rounded-4 mb-3">
        <i data-feather="clock" style="width:18px;height:18px;flex-shrink:0;"></i>
        <div>
            <strong>{{ __('Your subscription expires in :days day(s).', ['days' => $bkSubDays]) }}</strong>
            <a href="{{ route('company.profile.show') }}" class="alert-link">{{ __('View my subscription') }}</a>
        </div>
    </div>
@endif

{{-- ════ SETUP RAIL — unified approval status + onboarding ════ --}}
@php
    $obSteps      = $onboarding['steps'] ?? [];
    $obPct        = $onboarding['percent'] ?? 100;
    $obDismissed  = $onboarding['dismissed'] ?? false;
    $obPublished  = $onboarding['published'] ?? true;
    $obCanPublish = $onboarding['canPublish'] ?? false;
    $obHo         = $onboarding['headOfficeId'] ?? null;
    // Ordered flow: logo (soft quick-win) → location → hours → service.
    // The last three are required to go live.
    $obList = [
        ['key' => 'logo',          'icon' => 'image',    'title' => __('Add your logo'),            'required' => false,
            'url' => route('company.profile.show')],
        ['key' => 'location',      'icon' => 'map-pin',  'title' => __('Set your location'),        'required' => true,
            'url' => $obHo ? route('company.branches.edit', $obHo) : route('company.branches.index')],
        ['key' => 'working_hours', 'icon' => 'clock',    'title' => __('Set working hours'),        'required' => true,
            'url' => $obHo ? route('company.branches.working-hours.edit', $obHo) : route('company.branches.index')],
        ['key' => 'service',       'icon' => 'scissors', 'title' => __('Add your first service'),   'required' => true,
            'url' => $obHo ? route('company.branches.services.create', $obHo) : route('company.branches.index')],
    ];
    $obSubmitted = $onboarding['submittedForReview'] ?? false;
    // The rail is the path to going live, so it stays until the owner approves
    // the account (status → active). Collapsing (client-side) tucks it away
    // without losing the "submit for review" action.
    $showRail    = ! $obPublished;
    $obDone      = collect($obList)->filter(fn ($s) => (bool) ($obSteps[$s['key']] ?? false))->count();
    $obTotal     = count($obList);
@endphp
@if($showRail)
<section id="bkSetup" class="bk-setup" aria-label="{{ __('Business setup') }}">
    <header class="bk-setup-head">
        <div class="bk-setup-lead">
            <span class="bk-setup-ic" aria-hidden="true">
                <i data-feather="{{ $obSubmitted ? 'clock' : 'flag' }}"></i>
            </span>
            <div class="bk-setup-heading">
                <h2 class="bk-setup-title">
                    {{ $obSubmitted ? __('Your business is under review') : __('Complete your business setup') }}
                </h2>
                <div class="bk-setup-meta">
                    @if($obSubmitted)
                        <span class="bk-setup-chip"><span class="dot" aria-hidden="true"></span>{{ __('Under review') }}</span>
                    @endif
                    <span class="bk-setup-count"><b>{{ $obDone }}</b> / {{ $obTotal }} {{ __('steps') }}</span>
                </div>
            </div>
        </div>

        <div class="bk-setup-controls">
            <div class="bk-setup-meter" role="progressbar" aria-valuenow="{{ $obPct }}" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ $obPct }}%">
                <span style="width:{{ $obPct }}%;"></span>
            </div>
            <button type="button" class="bk-setup-toggle" aria-expanded="true" aria-controls="bkSetupBody">
                <span>{{ __('Steps') }}</span>
                <i data-feather="chevron-up"></i>
            </button>
        </div>
    </header>

    <div id="bkSetupBody" class="bk-setup-body">
        <div class="bk-setup-body-inner">
            <div class="bk-setup-body-pad">
                @if($obSubmitted)
                <p class="bk-setup-note">
                    {{ __('We are reviewing your account and will approve it shortly. Once approved, your business will be visible and ready to accept clients.') }}
                    <br>
                    <i data-feather="calendar" style="width:13px;height:13px;vertical-align:-2px;"></i>
                    {{ __('Created on') }} <b>{{ $company->created_at?->translatedFormat('d M Y') }}</b>
                </p>
                @endif

                <ol class="bk-setup-steps">
                    @foreach($obList as $s)
                        @php $done = (bool) ($obSteps[$s['key']] ?? false); @endphp
                        <li>
                            <a href="{{ $s['url'] }}" class="bk-setup-step {{ $done ? 'is-done' : '' }}">
                                <span class="bk-setup-node" aria-hidden="true">
                                    <i data-feather="{{ $done ? 'check' : $s['icon'] }}"></i>
                                </span>
                                <span class="bk-setup-step-body">
                                    <span class="bk-setup-step-label">{{ $s['title'] }}</span>
                                    <span class="bk-setup-step-status {{ $done ? 'st-done' : ($s['required'] ? 'st-required' : 'st-optional') }}">
                                        @if(!$done)<span class="d" aria-hidden="true"></span>@endif
                                        {{ $done ? __('Done') : ($s['required'] ? __('Required') : __('Optional')) }}
                                    </span>
                                </span>
                                <span class="bk-setup-step-arrow" aria-hidden="true">
                                    <i data-feather="chevron-{{ $isAr ? 'left' : 'right' }}"></i>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>

                <div class="bk-setup-foot">
                    @if($obSubmitted)
                        <span class="bk-setup-submitted">
                            <i data-feather="check-circle"></i>
                            {{ __('Submitted for review') }}
                        </span>
                        <span class="bk-setup-hint">{{ __('We will notify you once your business is approved.') }}</span>
                    @elseif($obCanPublish)
                        <form method="POST" action="{{ route('company.onboarding.submit-review') }}" class="m-0">
                            @csrf
                            <button type="submit" class="bk-setup-publish is-ready">
                                <i data-feather="send"></i>
                                {{ __('Submit for review') }}
                            </button>
                        </form>
                        <span class="bk-setup-hint">{{ __('Our team will review and approve your business.') }}</span>
                    @else
                        <button type="button" class="bk-setup-publish" disabled>
                            <i data-feather="lock"></i>
                            {{ __('Submit for review') }}
                        </button>
                        <span class="bk-setup-hint">{{ __('Finish the required steps above to submit.') }}</span>
                    @endif
                    <button type="button" class="bk-setup-help" data-bs-toggle="modal" data-bs-target="#bkHelpModal">
                        <i data-feather="help-circle"></i>
                        {{ __('Need help?') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
    var el = document.getElementById('bkSetup');
    if (!el) return;
    var KEY = 'bk_setup_collapsed';
    var toggle = el.querySelector('.bk-setup-toggle');
    var collapsed = false;
    try { collapsed = localStorage.getItem(KEY) === '1'; } catch(e){}
    if (collapsed) el.setAttribute('data-collapsed', '');
    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

    /* enable height transition only after the initial state is painted */
    requestAnimationFrame(function(){ el.classList.add('bk-setup--ready'); });

    if (toggle) {
        toggle.addEventListener('click', function(){
            var isCollapsed = el.toggleAttribute('data-collapsed');
            toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            try { localStorage.setItem(KEY, isCollapsed ? '1' : '0'); } catch(e){}
        });
    }

    /* fill the meter from zero on load (GPU-friendly scaleX) */
    var fill = el.querySelector('.bk-setup-meter span');
    if (fill) {
        setTimeout(function(){ fill.style.transform = 'scaleX(1)'; }, 300);
    }
})();
</script>
@endif

@push('company-styles')
<link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-dashboard.css') }}?v={{ @filemtime(public_path('backend/assets/css/booksy-dashboard.css')) ?: '1' }}">
@endpush

@include('company.partials.flash')

@php
    // ── view-model helpers (presentation only; every number comes from the service) ──
    $ins      = $insights;
    $k        = $ins['kpi'];
    $cu       = $ins['customers'];
    $td       = $ins['today'];
    $hl       = $ins['health'];
    $sym      = $ins['currency']['symbol'];
    $money    = fn ($v) => number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2);
    $hour     = (int) now(config('app.timezone'))->format('G');
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    $todayPct = $td['total'] > 0 ? round($td['done'] / $td['total'], 3) : 0;

    // % change chip → [css class, feather icon, text]
    $chip = function ($d) {
        if ($d === null) return ['flat', 'star', __('New')];
        if ($d > 0.05)   return ['up', 'trending-up', '+' . rtrim(rtrim(number_format($d, 1), '0'), '.') . '%'];
        if ($d < -0.05)  return ['down', 'trending-down', '−' . rtrim(rtrim(number_format(abs($d), 1), '0'), '.') . '%'];
        return ['flat', 'minus', '0%'];
    };

    // 14-day bar micro-chart; every bar carries its own label + value as a tooltip
    $bars = function (array $vals, array $labels, string $unit) {
        $n = max(1, count($vals));
        $max = max(1, max($vals ?: [0]));
        $svg = '<svg class="dx-bars" viewBox="0 0 140 46" preserveAspectRatio="none" role="img" aria-label="' . e(__('Last 14 days')) . '">';
        foreach ($vals as $i => $v) {
            $h = max(3, round(($v / $max) * 42));
            $x = round($i * (140 / $n) + 1, 2);
            $w = round((140 / $n) - 3.6, 2);
            $svg .= '<rect x="' . $x . '" y="' . (46 - $h) . '" width="' . $w . '" height="' . $h . '" rx="2"><title>'
                . e(($labels[$i] ?? '') . ' — ' . number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2) . ($unit ? ' ' . $unit : ''))
                . '</title></rect>';
        }
        return $svg . '</svg>';
    };
    $pctOf = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100) : 0;

    // ── quick actions ──
    $firstBranch = ($branchContext ?? null) ?: $company->branches->first();

    $publicUrl = $company->publicUrl($branchContext ?? null);
    $candidates = [];
    $candidates[] = ['url' => route('company.appointments.index', ['status' => 'pending']), 'icon' => 'bell',
        'label' => __('Pending requests'),
        'hint'  => $pendingAppt > 0 ? __(':count waiting for you', ['count' => $pendingAppt]) : __('Nothing waiting'),
        'badge' => $pendingAppt];
    if ($firstBranch) {
        $candidates[] = ['url' => route('company.branches.services.create', $firstBranch), 'icon' => 'scissors',
            'label' => __('Add service'), 'hint' => __(':count services live', ['count' => $servicesCount])];
        $candidates[] = ['url' => route('company.branches.employees.create', $firstBranch), 'icon' => 'user-plus',
            'label' => __('Add employee'), 'hint' => __(':count active', ['count' => $ins['team']['active']])];
    }
    $candidates[] = ['url' => route('company.customers.index'), 'icon' => 'users',
        'label' => __('Customers'), 'hint' => __(':count customers', ['count' => number_format($cu['total'])])];
    if ($company->hasFeature('finance')) {
        $candidates[] = ['url' => route('company.invoices.index'), 'icon' => 'file-text',
            'label' => __('Invoices'), 'hint' => __('Billing & payments')];
    }
    if ($company->hasFeature('reports')) {
        $candidates[] = ['url' => route('company.reports.profit-loss'), 'icon' => 'bar-chart-2',
            'label' => __('Reports'), 'hint' => __('Profit & performance')];
    }
    $candidates[] = ['url' => route('company.marketing.offers'), 'icon' => 'percent',
        'label' => __('Offers'), 'hint' => __('Discounts & campaigns')];
    $candidates[] = ['url' => route('company.service-categories.index'), 'icon' => 'tag',
        'label' => __('Categories'), 'hint' => __('Organise your menu')];
    $candidates[] = ['url' => route('company.branches.create'), 'icon' => 'map-pin',
        'label' => __('New branch'), 'hint' => __('Open another location')];
    $dock = array_slice($candidates, 0, 6);

    // ── needs attention ──
    $attn = [];
    if ($ins['attention']['pending'] > 0) {
        $attn[] = ['icon' => 'bell', 'tone' => '', 'n' => $ins['attention']['pending'], 'label' => __('Booking requests'),
            'hint' => __('Waiting for your confirmation'), 'url' => route('company.appointments.index', ['status' => 'pending'])];
    }
    if ($ins['attention']['awaiting'] > 0) {
        $attn[] = ['icon' => 'credit-card', 'tone' => 'gold', 'n' => $ins['attention']['awaiting'], 'label' => __('Awaiting payment'),
            'hint' => __('Service done, payment not collected'), 'url' => route('company.appointments.index', ['status' => 'awaiting_payment'])];
    }
    if ($waitlistWaiting > 0 && $company->hasFeature('waitlist')) {
        $attn[] = ['icon' => 'list', 'tone' => 'info', 'n' => $waitlistWaiting, 'label' => __('On the waitlist'),
            'hint' => __('Customers hoping for a slot'), 'url' => route('company.waitlist.index')];
    }
    if (($ins['finance']['debts_overdue'] ?? 0) > 0) {
        $attn[] = ['icon' => 'alert-triangle', 'tone' => 'danger', 'n' => $ins['finance']['debts_overdue'], 'label' => __('Overdue debts'),
            'hint' => __('Past their due date'), 'url' => route('company.debts.index')];
    }
    if (($ins['inventory']['low_stock'] ?? 0) > 0) {
        $attn[] = ['icon' => 'package', 'tone' => 'danger', 'n' => $ins['inventory']['low_stock'], 'label' => __('Low stock'),
            'hint' => __('Products at or below their threshold'), 'url' => route('company.inventory.index')];
    }

    // ── outcome ledger ──
    $outcomes = [
        ['k' => 'completed', 'label' => __('Completed'),         'n' => $hl['completed'], 'var' => '--bk-success'],
        ['k' => 'active',    'label' => __('Confirmed / in service'), 'n' => $hl['active'],    'var' => '--bk-info'],
        ['k' => 'pending',   'label' => __('Pending'),           'n' => $hl['pending'],   'var' => '--bk-warning'],
        ['k' => 'awaiting',  'label' => __('Awaiting payment'),  'n' => $hl['awaiting'],  'var' => '--bk-gold'],
        ['k' => 'cancelled', 'label' => __('Cancelled'),         'n' => $hl['cancelled'], 'var' => '--bk-danger'],
        ['k' => 'no_show',   'label' => __('No-show'),           'n' => $hl['no_show'],   'var' => '--bk-text-muted'],
    ];
    $channelIcons = ['instagram' => 'instagram', 'facebook' => 'facebook', 'whatsapp' => 'message-circle', 'glowrez' => 'globe', 'reception' => 'monitor', 'other' => 'compass'];
    $channelVars  = ['instagram' => '--bk-purple', 'facebook' => '--bk-info', 'whatsapp' => '--bk-success', 'glowrez' => '--bk-accent', 'reception' => '--bk-gold', 'other' => '--bk-border-strong'];
    $srcTotal     = max(1, collect($ins['sources'])->sum('count'));
    $hasBranches  = count($ins['branches']) > 0;
    $hm           = $ins['heatmap'];
@endphp

{{-- ════ MASTHEAD ════ --}}
<section class="dx-hero dx-rise" style="--i:0" aria-label="{{ __('Today') }}">
    <div class="dx-hero-top">
        <div class="dx-hero-copy">
            <h1 class="dx-hero-title">{{ $greeting }}{{ $isAr ? '،' : ',' }} <em>{{ $company->localizedName() }}</em></h1>
            <p class="dx-hero-line">
                @if($td['total'] > 0)
                    {{ __('You have :count appointments today — :left still ahead.', ['count' => $td['total'], 'left' => $td['left']]) }}
                    @if($td['next'])
                        <a href="{{ $td['next']['url'] }}">{{ __('Next up: :time with :name', ['time' => $td['next']['time'], 'name' => $td['next']['customer'] ?: '—']) }}</a>
                    @endif
                @else
                    {{ __('No appointments today yet — a good moment to set things up or share your page.') }}
                @endif
            </p>
        </div>
        <div class="dx-hero-side">
            <span class="dx-date"><i data-feather="calendar"></i>{{ now(config('app.timezone'))->translatedFormat('l j F Y') }}</span>
            <div class="dx-hero-actions">
                <a href="{{ route('company.appointments.index') }}" class="dx-btn dx-btn--gold"><i data-feather="plus"></i>{{ __('New booking') }}</a>
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="dx-btn dx-btn--ghost"><i data-feather="external-link"></i>{{ __('Public page') }}</a>
            </div>
        </div>
    </div>

    <div class="dx-today">
        <div class="dx-today-i">
            <div class="dx-today-k"><i data-feather="calendar"></i>{{ __('Today') }}</div>
            <div class="dx-today-v dx-count" data-to="{{ $td['total'] }}">{{ number_format($td['total']) }}</div>
        </div>
        <div class="dx-today-i">
            <div class="dx-today-k"><i data-feather="check-circle"></i>{{ __('Completed') }}</div>
            <div class="dx-today-v"><span class="dx-count" data-to="{{ $td['done'] }}">{{ number_format($td['done']) }}</span><small>/ {{ number_format($td['total']) }}</small></div>
        </div>
        <div class="dx-today-i">
            <div class="dx-today-k"><i data-feather="clock"></i>{{ __('Still ahead') }}</div>
            <div class="dx-today-v dx-count" data-to="{{ $td['left'] }}">{{ number_format($td['left']) }}</div>
        </div>
        <div class="dx-today-i">
            <div class="dx-today-k"><i data-feather="dollar-sign"></i>{{ __("Today's revenue") }}</div>
            <div class="dx-today-v">{{ $money($td['revenue']) }}<small>{{ $sym }}</small></div>
        </div>
        <div class="dx-today-bar" style="--p:{{ $todayPct }}" aria-hidden="true"><span></span></div>
    </div>
</section>

{{-- ════ BUSINESS PULSE — the month in four columns ════ --}}
<section class="dx-panel dx-pulse dx-rise" style="--i:1" aria-label="{{ __('This month') }}">

    @php [$rc, $ri, $rt] = $chip($k['revenue_delta']); @endphp
    <article class="dx-k" data-live-block="stats">
        <div class="dx-k-h"><span class="dx-k-ic dx-k-ic--gold"><i data-feather="dollar-sign"></i></span>{{ __('Revenue this month') }}</div>
        <div class="dx-k-v"><span class="dx-count" data-to="{{ $k['revenue'] }}" data-dec="{{ $k['revenue'] == floor($k['revenue']) ? 0 : 2 }}">{{ $money($k['revenue']) }}</span><small>{{ $sym }}</small></div>
        <span class="dx-delta {{ $rc }}"><i data-feather="{{ $ri }}"></i><bdi dir="ltr">{{ $rt }}</bdi> <span style="font-weight:600;opacity:.85">{{ __('vs same days last month') }}</span></span>
        <div class="dx-k-note">{{ __('Still expected this month') }}: <b>{{ $money($k['revenue_expected']) }} {{ $sym }}</b> · {{ __('Avg. ticket') }}: <b>{{ $money($k['avg_ticket']) }} {{ $sym }}</b></div>
        {!! $bars($ins['series']['spark']['revenue'], $ins['series']['spark']['labels'], $sym) !!}
    </article>

    @php [$bc, $bi, $bt] = $chip($k['bookings_delta']); @endphp
    <article class="dx-k">
        <div class="dx-k-h"><span class="dx-k-ic"><i data-feather="calendar"></i></span>{{ __('Bookings this month') }}</div>
        <div class="dx-k-v"><span class="dx-count" data-to="{{ $k['bookings'] }}">{{ number_format($k['bookings']) }}</span></div>
        <span class="dx-delta {{ $bc }}"><i data-feather="{{ $bi }}"></i><bdi dir="ltr">{{ $bt }}</bdi></span>
        <div class="dx-k-note">{{ __('Last month so far') }}: <b>{{ number_format($k['bookings_prev']) }}</b></div>
        {!! $bars($ins['series']['spark']['bookings'], $ins['series']['spark']['labels'], '') !!}
    </article>

    @php [$cc, $ci, $ct] = $chip($cu['new_prev'] > 0 ? (($cu['new_month'] - $cu['new_prev']) / $cu['new_prev'] * 100) : ($cu['new_month'] > 0 ? null : 0)); @endphp
    <article class="dx-k">
        <div class="dx-k-h"><span class="dx-k-ic"><i data-feather="users"></i></span>{{ __('Customers') }}</div>
        <div class="dx-k-v"><span class="dx-count" data-to="{{ $cu['total'] }}">{{ number_format($cu['total']) }}</span></div>
        <span class="dx-delta {{ $cc }}"><i data-feather="{{ $ci }}"></i>{{ number_format($cu['new_month']) }} {{ __('new this month') }}</span>
        <div class="dx-k-note">{{ __(':pct% came back for a second visit', ['pct' => rtrim(rtrim(number_format($cu['repeat_rate'], 1), '0'), '.')]) }}</div>
        <div class="dx-meter" role="img" aria-label="{{ __('Returning vs one-time customers') }}">
            <span class="dx-m-ok" style="flex:{{ max(1, $cu['returning']) }}"></span>
            <span class="dx-m-open" style="flex:{{ max(1, $cu['total'] - $cu['returning']) }}"></span>
        </div>
        <div class="dx-key">
            <span><i class="dx-m-ok"></i>{{ __('Returning') }} <b>{{ number_format($cu['returning']) }}</b></span>
            <span><i class="dx-m-open"></i>{{ __('One-time') }} <b>{{ number_format(max(0, $cu['total'] - $cu['returning'])) }}</b></span>
        </div>
    </article>

    <article class="dx-k">
        <div class="dx-k-h"><span class="dx-k-ic"><i data-feather="check-circle"></i></span>{{ __('Completion rate') }}</div>
        <div class="dx-k-v"><span class="dx-count" data-to="{{ $hl['completion_rate'] }}" data-dec="{{ $hl['completion_rate'] == floor($hl['completion_rate']) ? 0 : 1 }}">{{ rtrim(rtrim(number_format($hl['completion_rate'], 1), '0'), '.') }}</span><small>%</small></div>
        <div class="dx-k-note">{{ __('Of visits that were closed in the last :days days', ['days' => $hl['days']]) }}</div>
        <div class="dx-meter" role="img" aria-label="{{ __('Completed, cancelled and no-show share') }}">
            <span class="dx-m-ok" style="flex:{{ max(0.0001, $hl['completed']) }}"></span>
            <span class="dx-m-lost" style="flex:{{ max(0.0001, $hl['cancelled']) }}"></span>
            <span class="dx-m-no" style="flex:{{ max(0.0001, $hl['no_show']) }}"></span>
        </div>
        <div class="dx-key">
            <span><i class="dx-m-ok"></i>{{ __('Completed') }} <b>{{ number_format($hl['completed']) }}</b></span>
            <span><i class="dx-m-lost"></i>{{ __('Cancelled') }} <b>{{ $hl['cancel_rate'] + 0 }}%</b></span>
            <span><i class="dx-m-no"></i>{{ __('No-show') }} <b>{{ $hl['no_show_rate'] + 0 }}%</b></span>
        </div>
    </article>
</section>

{{-- ════ QUICK ACTIONS ════ --}}
<section class="dx-rise" style="--i:2" aria-labelledby="dxDockT">
    <div class="dx-ph" style="margin-bottom:14px">
        <h2 class="dx-pt" id="dxDockT">{{ __('Quick Actions') }}</h2>
    </div>
    <div class="dx-dock-grid">
        <a href="{{ route('company.appointments.index') }}" class="dx-act dx-act--lead">
            <span class="dx-act-ic"><i data-feather="plus"></i></span>
            <span class="dx-act-t">
                <span class="dx-act-l">{{ __('New booking') }}</span>
                <span class="dx-act-h">{{ __('Open the calendar and add a booking for a walk-in or a phone call.') }}</span>
            </span>
            <span class="dx-act-go"><i data-feather="arrow-up-right" class="dx-flip"></i></span>
        </a>
        @foreach($dock as $a)
        <a href="{{ $a['url'] }}" class="dx-act">
            <span class="dx-act-ic"><i data-feather="{{ $a['icon'] }}"></i>@if(($a['badge'] ?? 0) > 0)<span class="dx-badge">{{ $a['badge'] > 99 ? '99+' : $a['badge'] }}</span>@endif</span>
            <span class="dx-act-t">
                <span class="dx-act-l">{{ $a['label'] }}</span>
                <span class="dx-act-h">{{ $a['hint'] }}</span>
            </span>
            <span class="dx-act-go"><i data-feather="arrow-up-right" class="dx-flip"></i></span>
        </a>
        @endforeach
    </div>
</section>

{{-- ════ PERFORMANCE + ATTENTION ════ --}}
<div class="dx-main">
    <section class="dx-panel dx-rise" style="--i:3" aria-labelledby="dxPerfT">
        <div class="dx-ph">
            <div>
                <h2 class="dx-pt" id="dxPerfT">{{ __('Performance') }}</h2>
                <p class="dx-ps" id="dxPerfSub">{{ __('Bookings and realised revenue') }}</p>
            </div>
            <div class="dx-chart-bar">
                <select id="dxMonth" class="dx-select" aria-label="{{ __('Month') }}">
                    @foreach($monthOptions as $opt)
                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                <div class="dx-seg" id="dxRange" role="group" aria-label="{{ __('Range') }}">
                    <button type="button" data-range="today">{{ __('Today') }}</button>
                    <button type="button" data-range="week">{{ __('Week') }}</button>
                    <button type="button" data-range="month" class="is-on">{{ __('Month') }}</button>
                    <button type="button" data-range="year">{{ __('Year') }}</button>
                </div>
            </div>
        </div>
        <div class="dx-legend" id="dxLegend">
            <button type="button" data-series="0" aria-pressed="true" style="--c:var(--bk-accent-fill)"><i></i>{{ __('Bookings') }} <b id="dxSumB">0</b></button>
            <button type="button" data-series="1" aria-pressed="true" style="--c:var(--bk-gold)"><i></i>{{ __('Revenue') }} <b id="dxSumR">0</b></button>
        </div>
        <div id="dxChart" class="dx-chart"></div>
    </section>

    <section class="dx-panel dx-attn dx-rise" style="--i:4" aria-labelledby="dxAttnT">
        <div class="dx-ph">
            <div>
                <h2 class="dx-pt" id="dxAttnT">{{ __('Needs your attention') }}</h2>
                <p class="dx-ps">{{ count($attn) ? __('The things that move money or keep customers waiting.') : __('Nothing is waiting on you right now.') }}</p>
            </div>
        </div>
        @if(count($attn))
        <ul class="dx-list">
            @foreach($attn as $a)
            <li>
                <a href="{{ $a['url'] }}" class="dx-item">
                    <span class="dx-item-ic {{ $a['tone'] }}"><i data-feather="{{ $a['icon'] }}"></i></span>
                    <span class="dx-item-t"><span class="dx-item-l">{{ $a['label'] }}</span><span class="dx-item-h">{{ $a['hint'] }}</span></span>
                    <span class="dx-item-n">{{ number_format($a['n']) }}</span>
                    <i data-feather="chevron-right" class="dx-item-c dx-flip"></i>
                </a>
            </li>
            @endforeach
        </ul>
        @else
        <div class="dx-clear">
            <div class="dx-clear-ic"><i data-feather="check"></i></div>
            <b>{{ __('All caught up') }}</b>
            {{ __('New requests and payments will show up here.') }}
        </div>
        @endif
    </section>
</div>

{{-- ════ DEMAND SHAPE + OUTCOMES ════ --}}
<div class="dx-two">
    <section class="dx-panel dx-rise" style="--i:5" aria-labelledby="dxHmT">
        <div class="dx-ph">
            <div>
                <h2 class="dx-pt" id="dxHmT">{{ __('When you are busiest') }}</h2>
                <p class="dx-ps">{{ __('Bookings by weekday and hour, last :days days', ['days' => $hl['days']]) }}</p>
            </div>
        </div>
        @if($hm['max'] > 0)
        <div class="dx-hm-wrap">
            <div class="dx-hm" style="--n:{{ count($hm['hours']) }}" role="img"
                 aria-label="{{ __('Busiest slot: :day at :hour', ['day' => $hm['peak']['day'], 'hour' => $hm['peak']['hour']]) }}">
                <div class="dx-hm-row">
                    <span></span>
                    @foreach($hm['hours'] as $h)<span class="dx-hm-h">{{ $loop->index % 2 === 0 ? sprintf('%02d', $h) : '' }}</span>@endforeach
                </div>
                @php $peakDone = false; @endphp
                @foreach($hm['days'] as $day)
                <div class="dx-hm-row">
                    <span class="dx-hm-d">{{ $day['label'] }}</span>
                    @foreach($day['cells'] as $ci2 => $cnt)
                        @php
                            $isPeak = ! $peakDone && $cnt === $hm['max'];
                            if ($isPeak) { $peakDone = true; }
                        @endphp
                        <span class="dx-hm-c {{ $isPeak ? 'is-peak' : '' }}"
                              style="--l:{{ $cnt > 0 ? round(sqrt($cnt / $hm['max']), 3) : 0 }}"
                              title="{{ $day['label'] }} {{ sprintf('%02d:00', $hm['hours'][$ci2]) }} — {{ number_format($cnt) }} {{ __('bookings') }}"></span>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        <div class="dx-hm-foot">
            <span>{{ __('Peak') }}: <b>{{ $hm['peak']['day'] }} · {{ $hm['peak']['hour'] }}</b> ({{ number_format($hm['peak']['count']) }} {{ __('bookings') }})</span>
            <span class="dx-scale">{{ __('Quiet') }}<i></i>{{ __('Busy') }}</span>
        </div>
        @else
        <div class="dx-empty"><div class="dx-empty-ic"><i data-feather="clock"></i></div><p>{{ __('Your busiest hours will appear once you have a few weeks of bookings.') }}</p></div>
        @endif
    </section>

    <section class="dx-panel dx-rise" style="--i:6" aria-labelledby="dxOutT">
        <div class="dx-ph">
            <div>
                <h2 class="dx-pt" id="dxOutT">{{ __('How bookings end up') }}</h2>
                <p class="dx-ps">{{ __('Last :days days', ['days' => $hl['days']]) }}</p>
            </div>
        </div>
        <div class="dx-donut">
            <div id="dxDonut" style="width:100%"></div>
            <div class="dx-donut-c"><b>{{ number_format($hl['total']) }}</b><span>{{ __('Total') }}</span></div>
        </div>
        <ul class="dx-ledger">
            @foreach($outcomes as $o)
            <li><i style="background:var({{ $o['var'] }})"></i>{{ $o['label'] }}<span class="n">{{ number_format($o['n']) }}</span><span class="p">{{ $pctOf($o['n'], $hl['total']) }}%</span></li>
            @endforeach
        </ul>
    </section>
</div>

{{-- ════ LEADERBOARDS ════ --}}
<div class="dx-lb {{ $hasBranches ? 'dx-lb--4' : '' }}">
    @php
        $board = function (array $rows, string $empty) use ($money, $sym) {
            $max = max(1, collect($rows)->max('count') ?: 1);
            $out = '';
            if (! $rows) return '<div class="dx-empty" style="padding:24px 8px"><p style="margin:0">' . e($empty) . '</p></div>';
            $out .= '<ol class="dx-rank">';
            foreach ($rows as $i => $r) {
                $out .= '<li><span class="dx-rank-n">' . ($i + 1) . '</span><span class="dx-rank-name" title="' . e($r['name']) . '">' . e($r['name']) . '</span>'
                    . '<span class="dx-rank-v">' . number_format($r['count']) . '<small>' . $money($r['revenue']) . ' ' . e($sym) . '</small></span>'
                    . '<span class="dx-rank-bar"><span style="--w:' . round($r['count'] / $max * 100) . '%"></span></span></li>';
            }
            return $out . '</ol>';
        };
    @endphp

    <section class="dx-panel dx-rise" style="--i:7" aria-labelledby="dxSvcT">
        <div class="dx-ph"><div><h2 class="dx-pt" id="dxSvcT">{{ __('Top services') }}</h2><p class="dx-ps">{{ __('By bookings · last :days days', ['days' => $hl['days']]) }}</p></div></div>
        {!! $board($ins['services'], __('No services booked yet.')) !!}
    </section>

    <section class="dx-panel dx-rise" style="--i:8" aria-labelledby="dxTeamT">
        <div class="dx-ph"><div><h2 class="dx-pt" id="dxTeamT">{{ __('Team leaders') }}</h2><p class="dx-ps">{{ __('By bookings · last :days days', ['days' => $hl['days']]) }}</p></div></div>
        {!! $board($ins['staff'], __('No bookings assigned to staff yet.')) !!}
    </section>

    @if($hasBranches)
    <section class="dx-panel dx-rise" style="--i:9" aria-labelledby="dxBrT">
        <div class="dx-ph"><div><h2 class="dx-pt" id="dxBrT">{{ __('Branches compared') }}</h2><p class="dx-ps">{{ __('By bookings · last :days days', ['days' => $hl['days']]) }}</p></div></div>
        {!! $board($ins['branches'], '') !!}
    </section>
    @endif

    <section class="dx-panel dx-rise" style="--i:10" aria-labelledby="dxChT">
        <div class="dx-ph"><div><h2 class="dx-pt" id="dxChT">{{ __('Where bookings come from') }}</h2><p class="dx-ps">{{ __('Booking channels · last :days days', ['days' => $hl['days']]) }}</p></div></div>
        @if(count($ins['sources']))
        <div class="dx-stack" role="img" aria-label="{{ __('Booking channels') }}">
            @foreach($ins['sources'] as $s)
                <span style="--w:{{ max(1, round($s['count'] / $srcTotal * 100)) }}%;--c:var({{ $channelVars[$s['key']] ?? '--bk-border-strong' }})"></span>
            @endforeach
        </div>
        <ul class="dx-ledger dx-ledger--lg">
            @foreach($ins['sources'] as $s)
            <li><span class="ic"><i data-feather="{{ $channelIcons[$s['key']] ?? 'compass' }}"></i></span>{{ $s['label'] }}<span class="n">{{ number_format($s['count']) }}</span><span class="p">{{ $pctOf($s['count'], $srcTotal) }}%</span></li>
            @endforeach
        </ul>
        <a href="{{ route('company.marketing.booking-sources') }}" class="dx-link" style="margin-top:12px">{{ __('Track your channels') }}<i data-feather="chevron-right" class="dx-flip"></i></a>
        @else
        <div class="dx-empty" style="padding:24px 8px"><p style="margin:0">{{ __('No bookings yet.') }}</p></div>
        @endif
    </section>
</div>

{{-- ════ BUSINESS AT A GLANCE ════ --}}
<section class="dx-panel dx-rise" style="--i:11" aria-labelledby="dxSnapT">
    <div class="dx-ph"><div><h2 class="dx-pt" id="dxSnapT">{{ __('Your business at a glance') }}</h2></div></div>
    <div class="dx-snap-grid">
        <a class="dx-snap-i" href="{{ route('company.branches.index') }}">
            <span class="dx-snap-ic"><i data-feather="map-pin"></i></span>
            <span><span class="dx-snap-v">{{ number_format($branchesCount) }}</span><span class="dx-snap-l">{{ __('Branches') }}</span></span>
        </a>
        <a class="dx-snap-i" href="{{ $firstBranch ? route('company.branches.services.index', $firstBranch) : route('company.branches.index') }}">
            <span class="dx-snap-ic"><i data-feather="scissors"></i></span>
            <span><span class="dx-snap-v">{{ number_format($servicesCount) }}</span><span class="dx-snap-l">{{ __('Services') }}</span></span>
        </a>
        <a class="dx-snap-i" href="{{ route('company.staff.index') }}">
            <span class="dx-snap-ic"><i data-feather="briefcase"></i></span>
            <span><span class="dx-snap-v">{{ number_format($ins['team']['active']) }}</span><span class="dx-snap-l">{{ __('Active staff') }}</span></span>
        </a>
        <a class="dx-snap-i" href="{{ route('company.customers.index') }}">
            <span class="dx-snap-ic"><i data-feather="users"></i></span>
            <span><span class="dx-snap-v">{{ number_format($cu['total']) }}</span><span class="dx-snap-l">{{ __('Customers') }}</span></span>
        </a>
        <div class="dx-snap-i">
            <span class="dx-snap-ic gold"><i data-feather="star"></i></span>
            <span>
                <span class="dx-snap-v">@if($ins['reviews']['count'] > 0){{ number_format($ins['reviews']['avg'], 1) }}<small>/ 5</small>@else—@endif</span>
                <span class="dx-snap-l">{{ $ins['reviews']['count'] > 0 ? __(':count reviews', ['count' => number_format($ins['reviews']['count'])]) : __('No reviews yet') }}</span>
            </span>
        </div>
        <div class="dx-snap-i">
            <span class="dx-snap-ic gold"><i data-feather="award"></i></span>
            <span><span class="dx-snap-v">{{ $money($k['avg_ticket']) }}<small>{{ $sym }}</small></span><span class="dx-snap-l">{{ __('Avg. ticket this month') }}</span></span>
        </div>
        @if($company->hasFeature('waitlist'))
        <a class="dx-snap-i" href="{{ route('company.waitlist.index') }}">
            <span class="dx-snap-ic info"><i data-feather="list"></i></span>
            <span><span class="dx-snap-v">{{ number_format($waitlistWaiting) }}</span><span class="dx-snap-l">{{ __('On the waitlist') }}</span></span>
        </a>
        @endif
        @if($ins['finance'] !== null)
        <a class="dx-snap-i" href="{{ route('company.debts.index') }}">
            <span class="dx-snap-ic warn"><i data-feather="credit-card"></i></span>
            <span><span class="dx-snap-v">{{ $money($ins['finance']['debts_owed']) }}<small>{{ $sym }}</small></span><span class="dx-snap-l">{{ __('Owed by customers') }} · {{ number_format($ins['finance']['debts_count']) }}</span></span>
        </a>
        @endif
        @if($ins['inventory'] !== null)
        <a class="dx-snap-i" href="{{ route('company.inventory.index') }}">
            <span class="dx-snap-ic warn"><i data-feather="package"></i></span>
            <span><span class="dx-snap-v">{{ number_format($ins['inventory']['low_stock']) }}</span><span class="dx-snap-l">{{ __('Low-stock products') }}</span></span>
        </a>
        @endif
    </div>
</section>

{{-- ════ AGENDA ════ --}}
<section class="dx-panel dx-rise" style="--i:12" data-live-block="recent" aria-labelledby="dxAgT">
    <div class="dx-ph">
        <div><h2 class="dx-pt" id="dxAgT">{{ __('Recent Appointments') }}</h2><p class="dx-ps">{{ __('What is coming up next, then the latest visits') }}</p></div>
        <a href="{{ route('company.appointments.index') }}" class="dx-link">{{ __('View all') }}<i data-feather="chevron-right" class="dx-flip"></i></a>
    </div>

    @if($recentAppointments->count())
    <ul class="dx-agenda">
        @foreach($recentAppointments as $row)
        @php
            $ic      = $row->status->color();
            $initial = mb_strtoupper(mb_substr($row->customer?->name ?? 'C', 0, 1));
            $phone   = $row->customer?->phone ? preg_replace('/\D/', '', $row->customer->phone) : null;
        @endphp
        <li>
            <div class="dx-row">
                <a class="dx-row-link" href="{{ route('company.appointments.show', $row) }}" aria-label="{{ ($row->customer?->name ?? '—') . ' — ' . $row->start_time?->format('d/m H:i') }}"></a>
                <div class="dx-time">{{ $row->start_time?->format('H:i') }}<small>{{ $row->start_time?->translatedFormat('D j M') }} · <span class="bk-reltime" data-ts="{{ $row->start_time?->format('Y-m-d\TH:i:s') }}"></span></small></div>
                <div class="dx-who">
                    <span class="dx-av" style="--c:{{ $ic }}">{{ $initial }}</span>
                    <span class="dx-who-t">
                        <span class="dx-who-n">{{ $row->customer?->name ?? '—' }}</span>
                        @if($phone)
                        <span class="dx-who-p">
                            <span dir="ltr">{{ $row->customer->phone }}</span>
                            <a href="tel:{{ $row->customer->phone }}" title="{{ __('Call') }}" aria-label="{{ __('Call') }}"><i data-feather="phone"></i></a>
                            <a href="https://wa.me/{{ $phone }}" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp"><i data-feather="message-circle"></i></a>
                        </span>
                        @endif
                    </span>
                </div>
                <div class="dx-svc">
                    <b>{{ $row->service?->localizedName() ?? '—' }}</b>
                    @if($row->branch)<span>{{ $row->branch->localizedName() }}</span>@endif
                </div>
                <div class="dx-st"><x-appointment-status :status="$row->status" class="bk-badge" /></div>
                <i data-feather="chevron-right" class="dx-row-go dx-flip"></i>
            </div>
        </li>
        @endforeach
    </ul>
    @else
    <div class="dx-empty">
        <div class="dx-empty-ic"><i data-feather="calendar"></i></div>
        <p>{{ __('No appointments yet.') }}</p>
        <a href="{{ route('company.appointments.index') }}" class="dx-btn dx-btn--gold" style="background:var(--bk-accent-fill);color:var(--bk-accent-ink)!important">{{ __('Create first booking') }}</a>
    </div>
    @endif
</section>

<p class="dx-foot">{{ __('Figures refresh every few minutes.') }}</p>

</div>{{-- .page-content --}}

@push('company-after-template')
@php
    $dxPayload = [
        'theme'         => request()->cookie('company_theme', 'dark'),
        'rtl'           => $isAr,
        'series'        => ['today' => $ins['series']['today'], 'week' => $ins['series']['week'], 'month' => $ins['series']['month'], 'year' => $ins['series']['year']],
        'monthUrl'      => route('company.dashboard.chart.month'),
        'currency'      => $sym,
        'outcomes'      => collect($outcomes)->map(fn ($o) => ['label' => $o['label'], 'n' => $o['n'], 'var' => $o['var']])->values(),
        'labels'        => [
            'bookings' => __('Bookings'),
            'revenue'  => __('Revenue'),
            'noData'   => __('No data yet.'),
            'sub'      => [
                'today' => __('Today, hour by hour'),
                'week'  => __('Last 7 days'),
                'month' => __('Day by day'),
                'year'  => __('Last 12 months'),
            ],
        ],
    ];
@endphp
<script>window.dxDashboard = @json($dxPayload);</script>
<script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
'use strict';
var P = window.dxDashboard || {};
var L = P.labels || {};
var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
var root = getComputedStyle(document.documentElement);
function tok(n, fb) { return (root.getPropertyValue(n) || '').trim() || fb; }
var isDark = P.theme !== 'light';
var C = {
    text: tok('--bk-text-soft', '#888'), muted: tok('--bk-text-muted', '#888'),
    grid: tok('--bk-border', 'rgba(0,0,0,.08)'), accent: tok('--bk-accent-fill', '#4B5D34'),
    gold: tok('--bk-gold', '#C7A15A'), surface: tok('--bk-surface', '#fff')
};
var nf = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
function compact(v) {
    var a = Math.abs(v);
    if (a >= 1e6) return (v / 1e6).toFixed(a >= 1e7 ? 0 : 1).replace(/\.0$/, '') + 'M';
    if (a >= 1e3) return (v / 1e3).toFixed(a >= 1e4 ? 0 : 1).replace(/\.0$/, '') + 'K';
    return String(Math.round(v));
}
function sum(a) { var s = 0; for (var i = 0; i < a.length; i++) s += (+a[i] || 0); return s; }

/* ── count-up (the page's one scripted moment, with the CSS rise) ── */
function countUp() {
    document.querySelectorAll('.dx-count[data-to]').forEach(function (el) {
        var to = parseFloat(el.dataset.to) || 0, dec = parseInt(el.dataset.dec || '0', 10);
        if (!to || reduce) return;
        var t0 = null, dur = 1300;
        function fmt(v) { return v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
        function step(ts) {
            if (t0 === null) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1), e = 1 - Math.pow(2, -10 * p);
            el.textContent = fmt(to * (p >= 1 ? 1 : e));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        // never leave a figure stranded mid-count if frames are throttled
        setTimeout(function () { el.textContent = fmt(to); }, dur + 400);
    });
    var bar = document.querySelector('.dx-today-bar');
    if (bar) setTimeout(function () { bar.classList.add('is-in'); }, 120);
}
countUp();

/* ── performance chart ── */
var chart = null, range = 'month', current = P.series.month, hidden = [false, false];
var picker = document.getElementById('dxMonth');
var sumB = document.getElementById('dxSumB'), sumR = document.getElementById('dxSumR'), sub = document.getElementById('dxPerfSub');

function paintSums(s) {
    if (sumB) sumB.textContent = nf.format(sum(s.bookings));
    if (sumR) sumR.textContent = nf.format(sum(s.revenue)) + ' ' + (P.currency || '');
    if (sub && L.sub) sub.textContent = L.sub[range] || '';
}
function options(s) {
    var peak = Math.max.apply(null, s.bookings.concat([0]));
    var peakR = Math.max.apply(null, s.revenue.concat([0]));
    var ticks = Math.min(s.labels.length, { today: 8, week: 7, month: 10, year: 12 }[range] || 10);
    return {
        chart: { type: 'line', height: 330, background: 'transparent', toolbar: { show: false }, zoom: { enabled: false },
                 fontFamily: 'inherit', foreColor: C.muted, animations: { enabled: !reduce, speed: 650, easing: 'easeout' } },
        series: [{ name: L.bookings, type: 'column', data: s.bookings }, { name: L.revenue, type: 'area', data: s.revenue }],
        colors: [C.accent, C.gold],
        stroke: { width: [0, 2.6], curve: 'smooth' },
        fill: { type: ['solid', 'gradient'], opacity: [0.92, 1],
                gradient: { shadeIntensity: 1, opacityFrom: 0.34, opacityTo: 0.02, stops: [0, 95, 100] } },
        plotOptions: { bar: { columnWidth: '54%', borderRadius: 6, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        markers: { size: 0, strokeWidth: 2, strokeColors: C.surface, hover: { size: 6 } },
        xaxis: { categories: s.labels, tickAmount: ticks, axisBorder: { show: false }, axisTicks: { show: false },
                 tooltip: { enabled: false }, labels: { hideOverlappingLabels: true, rotate: 0, style: { fontSize: '11px', colors: C.muted } },
                 crosshairs: { show: true, stroke: { color: C.gold, width: 1, dashArray: 3 } } },
        yaxis: [
            { seriesName: L.bookings, min: 0, max: peak > 0 ? undefined : 4, forceNiceScale: true, tickAmount: 4,
              labels: { formatter: function (v) { return isFinite(v) ? Math.round(v) : 0; }, style: { colors: C.muted, fontSize: '11px' } } },
            { seriesName: L.revenue, opposite: true, min: 0, max: peakR > 0 ? undefined : 4, forceNiceScale: true, tickAmount: 4,
              labels: { formatter: function (v) { return isFinite(v) ? compact(v) : 0; }, style: { colors: C.muted, fontSize: '11px' } } }
        ],
        grid: { borderColor: C.grid, strokeDashArray: 4, xaxis: { lines: { show: false } }, padding: { left: 6, right: 6 } },
        legend: { show: false },
        noData: { text: L.noData, style: { color: C.muted } },
        tooltip: { shared: true, intersect: false, theme: isDark ? 'dark' : 'light',
                   y: [{ formatter: function (v) { return nf.format(v || 0); } },
                       { formatter: function (v) { return nf.format(v || 0) + ' ' + (P.currency || ''); } }] },
        theme: { mode: isDark ? 'dark' : 'light' }
    };
}
function applyHidden() {
    if (!chart) return;
    ['bookings', 'revenue'].forEach(function (k, i) {
        var name = L[k];
        if (hidden[i]) chart.hideSeries(name); else chart.showSeries(name);
    });
}
function draw(s) {
    current = s; paintSums(s);
    var node = document.getElementById('dxChart');
    if (!node || typeof ApexCharts === 'undefined') return;
    if (!chart) { chart = new ApexCharts(node, options(s)); chart.render().then(applyHidden); return; }
    var o = options(s);
    chart.updateOptions({ series: o.series, xaxis: o.xaxis, yaxis: o.yaxis }, false, true).then(applyHidden);
}
function loadMonth(ym) {
    var p = (ym || '').split('-');
    if (p.length < 2 || !P.monthUrl) return;
    fetch(P.monthUrl + '?year=' + p[0] + '&month=' + parseInt(p[1], 10), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { draw(d); })
        .catch(function () {});
}
if (picker) picker.addEventListener('change', function () { loadMonth(this.value); });
document.querySelectorAll('#dxRange button').forEach(function (b) {
    b.addEventListener('click', function () {
        document.querySelectorAll('#dxRange button').forEach(function (x) { x.classList.remove('is-on'); x.removeAttribute('aria-pressed'); });
        this.classList.add('is-on'); this.setAttribute('aria-pressed', 'true');
        range = this.dataset.range;
        if (picker) picker.style.display = range === 'month' ? '' : 'none';
        if (range === 'month') { picker && picker.value && picker.selectedIndex === 0 ? draw(P.series.month) : loadMonth(picker ? picker.value : ''); }
        else draw(P.series[range]);
    });
});
document.querySelectorAll('#dxLegend button').forEach(function (b) {
    b.addEventListener('click', function () {
        var i = parseInt(this.dataset.series, 10);
        hidden[i] = !hidden[i];
        this.setAttribute('aria-pressed', hidden[i] ? 'false' : 'true');
        applyHidden();
    });
});
draw(P.series.month);

/* ── outcomes donut ── */
(function () {
    var node = document.getElementById('dxDonut');
    if (!node || typeof ApexCharts === 'undefined') return;
    var rows = (P.outcomes || []).filter(function (o) { return o.n > 0; });
    var empty = rows.length === 0;
    var series = empty ? [1] : rows.map(function (o) { return o.n; });
    var colors = empty ? [C.grid] : rows.map(function (o) { return tok(o.var, '#888'); });
    new ApexCharts(node, {
        chart: { type: 'donut', height: 220, background: 'transparent', fontFamily: 'inherit', animations: { enabled: !reduce, speed: 700 } },
        series: series, labels: empty ? ['—'] : rows.map(function (o) { return o.label; }), colors: colors,
        legend: { show: false }, dataLabels: { enabled: false },
        stroke: { width: 3, colors: [C.surface] },
        plotOptions: { pie: { expandOnClick: false, donut: { size: '72%', labels: { show: false } } } },
        states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function (v) { return empty ? '0' : nf.format(v); } } }
    }).render();
})();

/* ── icons + relative time ── */
function icons() { if (window.feather) feather.replace(); }
icons(); setTimeout(icons, 60);

(function () {
    var ar = P.rtl === true;
    function unit(v, one, two, few, many) {
        if (!ar) return v === 1 ? one : many;
        if (v === 1) return one;
        if (v === 2) return two;
        return (v >= 3 && v <= 10) ? few : one;
    }
    function rel(ts) {
        var diff = Math.round((Date.now() - new Date(ts).getTime()) / 1000), abs = Math.abs(diff), past = diff >= 0, v, u;
        if (abs < 3600)         { v = Math.max(1, Math.round(abs / 60));      u = unit(v, ar ? 'دقيقة' : 'min', 'دقيقتان', 'دقائق', 'min'); }
        else if (abs < 86400)   { v = Math.round(abs / 3600);                 u = unit(v, ar ? 'ساعة' : 'hour', 'ساعتان', 'ساعات', 'hours'); }
        else if (abs < 2592000) { v = Math.round(abs / 86400);                u = unit(v, ar ? 'يوم' : 'day', 'يومان', 'أيام', 'days'); }
        else                    { v = Math.round(abs / 2592000);              u = unit(v, ar ? 'شهر' : 'month', 'شهران', 'أشهر', 'months'); }
        if (ar) { return (past ? 'منذ ' : 'بعد ') + (v === 2 ? u : v + ' ' + u); }
        return past ? v + ' ' + u + ' ago' : 'in ' + v + ' ' + u;
    }
    function all() { document.querySelectorAll('.bk-reltime[data-ts]').forEach(function (el) { if (el.dataset.ts) el.textContent = rel(el.dataset.ts); }); }
    all(); setInterval(all, 30000);
})();

})();
</script>
@endpush
@endsection
