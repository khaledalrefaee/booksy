@extends('owner.dashboard')
@section('content')
@include('owner.leads._styles')

@php
    use App\Support\LeadCatalog;

    $f = $filters;
    $byStatus = $report['by_status'];
    $st = fn (string $k) => (int) ($byStatus[$k] ?? 0);
    $cards = [
        ['key' => '',               'icon' => 'users',        'label' => __('Total leads'),     'value' => $report['total'], 'accent' => 'var(--bk-accent)'],
        ['key' => 'new',            'icon' => 'inbox',        'label' => LeadCatalog::label('statuses', 'new'),            'value' => $st('new'),            'accent' => 'var(--bk-gold)'],
        ['key' => 'contacted',      'icon' => 'phone-call',   'label' => LeadCatalog::label('statuses', 'contacted'),      'value' => $st('contacted'),      'accent' => 'var(--bk-info)'],
        ['key' => 'qualified',      'icon' => 'check-circle', 'label' => LeadCatalog::label('statuses', 'qualified'),      'value' => $st('qualified'),      'accent' => 'var(--bk-accent)'],
        ['key' => 'demo_scheduled', 'icon' => 'calendar',     'label' => LeadCatalog::label('statuses', 'demo_scheduled'), 'value' => $st('demo_scheduled'), 'accent' => 'var(--bk-accent)'],
        ['key' => 'converted',      'icon' => 'award',        'label' => LeadCatalog::label('statuses', 'converted'),      'value' => $st('converted'),      'accent' => 'var(--bk-success)'],
    ];
    $others = collect(['negotiation', 'not_interested', 'lost'])->filter(fn ($k) => $st($k) > 0)
        ->map(fn ($k) => LeadCatalog::label('statuses', $k).' '.number_format($st($k)))->implode(' · ');

    // query string that keeps date range but swaps the status (for the pipeline cards)
    $keep = array_filter(['date_from' => $f['date_from'], 'date_to' => $f['date_to']]);
    $baseQs = array_filter($f, fn ($v, $k) => $v !== '' && $v !== null && $k !== 'sort', ARRAY_FILTER_USE_BOTH);
    $moreOpen = collect($f)->except(['q', 'sort'])->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty();
    $sorts = [
        'newest'   => __('Newest first'),
        'oldest'   => __('Oldest first'),
        'branches' => __('Most branches'),
        'name'     => __('Name (A–Z)'),
        'status'   => __('Pipeline stage'),
    ];
    $joinBase = url('/join');
@endphp

<div class="page-content bm-wrap ld-wrap">
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a><span aria-hidden="true">·</span> {{ __('Pre-launch') }}</div>
            <h1 class="bm-title">{{ __('Leads') }}</h1>
            <p class="bm-subtitle">{{ __('Businesses that registered their interest in GlowRez, where they came from, and where each one is in your pipeline.') }}</p>
        </div>
        <div class="bm-head-actions">
            <a href="{{ route('owner.leads.export', $baseQs) }}" class="bm-btn bm-btn-ghost"><i data-feather="download"></i>{{ __('Export CSV') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    @unless($mailReady)
        <div class="bm-reveal" role="status" style="display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;padding:12px 16px;border-radius:12px;background:var(--bk-warning-bg);color:var(--bk-warning);border:1px solid color-mix(in srgb, var(--bk-warning) 30%, transparent);font-size:.85rem;font-weight:600;">
            <i data-feather="alert-triangle" style="width:16px;height:16px;flex:none;margin-top:2px;"></i>
            <span>{{ __('New-lead emails are off: set LEADS_NOTIFICATION_EMAIL in .env to receive an email for every registration.') }}</span>
        </div>
    @endunless

    {{-- Pipeline --}}
    <section class="ld-stats bm-reveal" aria-label="{{ __('Pipeline') }}">
        @foreach($cards as $c)
            @php $href = route('owner.leads.index', array_merge($keep, $c['key'] !== '' ? ['status' => $c['key']] : [])); @endphp
            <a href="{{ $href }}" class="ld-stat {{ $f['status'] === $c['key'] ? 'is-active' : '' }}" style="--accent:{{ $c['accent'] }};" @if($f['status'] === $c['key']) aria-current="true" @endif>
                <span class="ld-stat-label"><i data-feather="{{ $c['icon'] }}"></i>{{ $c['label'] }}</span>
                <span class="ld-stat-value">{{ number_format($c['value']) }}</span>
            </a>
        @endforeach
    </section>

    <div class="ld-funnel bm-reveal">
        <span>{{ __('Registered today') }}: <b>{{ number_format($report['today']) }}</b></span>
        <span>{{ __('Unique visitors') }}: <b>{{ number_format($report['visitors']) }}</b></span>
        <span>{{ __('Conversion rate') }}: <b><bdi>{{ $report['rate'] !== null ? $report['rate'].'%' : '—' }}</bdi></b>
            @if($report['rate'] === null)<span class="ld-hint">({{ __('no visits recorded yet') }})</span>@endif</span>
        @if($others)<span class="ld-hint">{{ $others }}</span>@endif
    </div>

    {{-- Market insights --}}
    <details class="ld-panel bm-reveal" @if($report['total'] > 0) open @endif>
        <summary><i data-feather="bar-chart-2" style="width:16px;height:16px;color:var(--bk-gold-strong);"></i>{{ __('Market insights') }}
            <span class="ld-sum-hint">{{ ($f['date_from'] || $f['date_to']) ? __('For the selected dates') : __('All time') }}</span>
            <i data-feather="chevron-down" class="ld-caret"></i></summary>
        <div class="ld-panel-body">
            @if($report['total'] === 0 && empty($report['sources']))
                <p class="ld-empty-line" style="padding-top:16px;">{{ __('Nothing to break down yet — insights appear as soon as the first lead registers.') }}</p>
            @else
            <div class="ld-insights">
                <section class="ld-ins">
                    <h3>{{ __('Leads by source') }}</h3>
                    <ul class="ld-bars">
                        @forelse($report['sources'] as $r)
                            @php $max = max(1, collect($report['sources'])->max('count')); @endphp
                            <li class="ld-bar">
                                <span class="ld-bar-name">{{ $r['label'] }}</span>
                                <span class="ld-bar-num">{{ number_format($r['count']) }}@if($r['visitors'] > 0)<small>{{ number_format($r['visitors']) }} {{ __('visitors') }} · <bdi>{{ $r['rate'] }}%</bdi></small>@endif</span>
                                <span class="ld-bar-track"><span class="ld-bar-fill" style="width:{{ round($r['count'] / $max * 100) }}%"></span></span>
                            </li>
                        @empty<li class="ld-empty-line">—</li>@endforelse
                    </ul>
                </section>
                <section class="ld-ins">
                    <h3>{{ __('Leads by business type') }}</h3>
                    <ul class="ld-bars">
                        @forelse($report['types'] as $r)
                            <li class="ld-bar"><span class="ld-bar-name">{{ $r['label'] }}</span><span class="ld-bar-num">{{ number_format($r['count']) }}<small><bdi>{{ $r['pct'] }}%</bdi></small></span>
                                <span class="ld-bar-track"><span class="ld-bar-fill" style="width:{{ max(2, $r['pct']) }}%"></span></span></li>
                        @empty<li class="ld-empty-line">—</li>@endforelse
                    </ul>
                </section>
                <section class="ld-ins">
                    <h3>{{ __('Leads by city') }}</h3>
                    <ul class="ld-bars">
                        @forelse($report['cities'] as $r)
                            <li class="ld-bar"><span class="ld-bar-name">{{ $r['label'] }}</span><span class="ld-bar-num">{{ number_format($r['count']) }}<small><bdi>{{ $r['pct'] }}%</bdi></small></span>
                                <span class="ld-bar-track"><span class="ld-bar-fill" style="width:{{ max(2, $r['pct']) }}%"></span></span></li>
                        @empty<li class="ld-empty-line">—</li>@endforelse
                    </ul>
                </section>
                <section class="ld-ins">
                    <h3>{{ __('Leads by campaign') }}</h3>
                    <ul class="ld-bars">
                        @php $cmax = max(1, collect($report['campaigns'])->max('count')); @endphp
                        @forelse($report['campaigns'] as $r)
                            <li class="ld-bar"><span class="ld-bar-name ld-ltr">{{ $r['label'] }}</span>
                                <span class="ld-bar-num">{{ number_format($r['count']) }}@if($r['visitors'] > 0)<small>{{ $r['visitors'] }} {{ __('visitors') }} · <bdi>{{ $r['rate'] }}%</bdi></small>@endif</span>
                                <span class="ld-bar-track"><span class="ld-bar-fill" style="width:{{ round($r['count'] / $cmax * 100) }}%"></span></span></li>
                        @empty<li class="ld-empty-line">{{ __('No campaign links used yet.') }}</li>@endforelse
                    </ul>
                </section>
                <section class="ld-ins">
                    <h3>{{ __('Leads by number of branches') }}</h3>
                    <ul class="ld-bars">
                        @foreach($report['branches'] as $r)
                            <li class="ld-bar"><span class="ld-bar-name">{{ $r['label'] }}</span><span class="ld-bar-num">{{ number_format($r['count']) }}<small><bdi>{{ $r['pct'] }}%</bdi></small></span>
                                <span class="ld-bar-track"><span class="ld-bar-fill" style="width:{{ $r['count'] ? max(2, $r['pct']) : 0 }}%"></span></span></li>
                        @endforeach
                    </ul>
                </section>
            </div>
            @endif
        </div>
    </details>

    {{-- Tracking links --}}
    <details class="ld-panel bm-reveal">
        <summary><i data-feather="link" style="width:16px;height:16px;color:var(--bk-gold-strong);"></i>{{ __('Tracking links') }}
            <span class="ld-sum-hint">{{ __('Paste into Instagram, WhatsApp, QR codes…') }}</span>
            <i data-feather="chevron-down" class="ld-caret"></i></summary>
        <div class="ld-panel-body">
            <div class="ld-links">
                <div class="ld-link-top">
                    <select class="bm-select" id="ld-link-page" aria-label="{{ __('Landing page') }}">
                        <option value="{{ url('/welcome') }}">{{ __('Welcome page (3 paths)') }}</option>
                        <option value="{{ url('/join') }}" selected>{{ __('Interest form (direct)') }}</option>
                        <option value="{{ url('/for-business') }}">{{ __('For business page') }}</option>
                    </select>
                    <input type="text" class="bm-select" id="ld-link-campaign" dir="ltr" placeholder="{{ __('Campaign name (optional), e.g. launch01') }}" maxlength="60" aria-label="{{ __('Campaign') }}" style="padding-inline:14px;">
                </div>
                <div class="ld-linkrows" id="ld-linkrows">
                    @foreach(['instagram' => 'Instagram', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'qr' => __('QR code'), 'field' => __('Field sales')] as $src => $label)
                        <div class="ld-linkrow" data-src="{{ $src }}">
                            <span class="ld-linkrow-src">{{ $label }}</span>
                            <code></code>
                            <button type="button" class="bm-act" data-copy title="{{ __('Copy link') }}" aria-label="{{ __('Copy link') }}"><i data-feather="copy"></i></button>
                        </div>
                    @endforeach
                </div>
                <p class="bm-help" style="margin:0;">{{ __('The source, campaign and any utm_* values on a link are saved with the lead automatically — visitors never type them.') }}</p>
            </div>
        </div>
    </details>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('owner.leads.index') }}" class="bm-toolbar bm-reveal" id="ld-filter">
        <div class="bm-toolbar-row">
            <div class="bm-search">
                <button type="submit" class="bm-search-btn" tabindex="-1" aria-label="{{ __('Search') }}"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $f['q'] }}" placeholder="{{ __('Search name, business, phone or email…') }}" autocomplete="off" aria-label="{{ __('Search') }}">
            </div>
            <select name="sort" class="bm-select" aria-label="{{ __('Sort') }}" onchange="this.form.submit()">
                @foreach($sorts as $k => $label)<option value="{{ $k }}" @selected($f['sort'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <button type="button" class="bm-btn bm-btn-ghost" id="ld-filter-toggle" aria-expanded="{{ $moreOpen ? 'true' : 'false' }}" aria-controls="ld-filters">
                <i data-feather="sliders"></i>{{ __('Filters') }}
                @if($activeCount - ($f['q'] !== '' ? 1 : 0) > 0)<span class="ld-filter-count">{{ $activeCount - ($f['q'] !== '' ? 1 : 0) }}</span>@endif
            </button>
            @if($activeCount > 0)<a href="{{ route('owner.leads.index', ['sort' => $f['sort']]) }}" class="bm-clear"><i data-feather="x"></i>{{ __('Clear') }}</a>@endif
        </div>

        <div class="ld-filters {{ $moreOpen ? 'is-open' : '' }}" id="ld-filters">
            <select name="business_type" class="bm-select" aria-label="{{ __('Business type') }}" onchange="this.form.submit()">
                <option value="">{{ __('Any business type') }}</option>
                @foreach(LeadCatalog::options('business_types') as $k => $label)<option value="{{ $k }}" @selected($f['business_type'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="city" class="bm-select" aria-label="{{ __('City') }}" onchange="this.form.submit()">
                <option value="">{{ __('Any city') }}</option>
                @foreach($options['cities'] as $k => $label)<option value="{{ $k }}" @selected($f['city'] === (string) $k)>{{ $label }}</option>@endforeach
            </select>
            <input type="text" name="area" value="{{ $f['area'] }}" list="ld-areas" class="bm-select" style="padding-inline:14px;" placeholder="{{ __('Area') }}" aria-label="{{ __('Area') }}" autocomplete="off">
            <datalist id="ld-areas">@foreach($options['areas'] as $a)<option value="{{ $a }}">@endforeach</datalist>
            <select name="branches" class="bm-select" aria-label="{{ __('Branches') }}" onchange="this.form.submit()">
                <option value="">{{ __('Any number of branches') }}</option>
                @foreach(['1' => __('1 branch'), '2' => __('2 branches'), '3' => __('3 branches'), '4' => __('4 branches'), '5+' => __('5 or more')] as $k => $label)<option value="{{ $k }}" @selected($f['branches'] === (string) $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="source" class="bm-select" aria-label="{{ __('Source') }}" onchange="this.form.submit()">
                <option value="">{{ __('Any source') }}</option>
                @foreach(LeadCatalog::options('sources') as $k => $label)<option value="{{ $k }}" @selected($f['source'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <select name="campaign" class="bm-select" aria-label="{{ __('Campaign') }}" onchange="this.form.submit()">
                <option value="">{{ __('Any campaign') }}</option>
                @foreach($options['campaigns'] as $c)<option value="{{ $c }}" @selected($f['campaign'] === $c)>{{ $c }}</option>@endforeach
            </select>
            <select name="status" class="bm-select" aria-label="{{ __('Status') }}" onchange="this.form.submit()">
                <option value="">{{ __('Any status') }}</option>
                @foreach(LeadCatalog::options('statuses') as $k => $label)<option value="{{ $k }}" @selected($f['status'] === $k)>{{ $label }}</option>@endforeach
            </select>
            <label class="bm-select" style="display:flex;align-items:center;gap:8px;padding-inline:12px;"><span style="font-size:.74rem;color:var(--bk-text-muted);white-space:nowrap;">{{ __('From') }}</span>
                <input type="date" name="date_from" value="{{ $f['date_from'] }}" style="border:0;background:transparent;color:inherit;min-width:0;width:100%;" onchange="this.form.submit()"></label>
            <label class="bm-select" style="display:flex;align-items:center;gap:8px;padding-inline:12px;"><span style="font-size:.74rem;color:var(--bk-text-muted);white-space:nowrap;">{{ __('To') }}</span>
                <input type="date" name="date_to" value="{{ $f['date_to'] }}" style="border:0;background:transparent;color:inherit;min-width:0;width:100%;" onchange="this.form.submit()"></label>
        </div>
    </form>

    {{-- Results --}}
    <div class="bm-card bm-reveal">
        @if($leads->isEmpty())
            <div class="bm-empty">
                <span class="bm-empty-ic"><i data-feather="{{ $activeCount ? 'search' : 'inbox' }}"></i></span>
                <p class="bm-empty-title">{{ $activeCount ? __('No leads match these filters') : __('No leads yet') }}</p>
                <p class="bm-empty-sub">{{ $activeCount ? __('Try removing a filter or widening the date range.') : __('Share your tracking link — every business that registers on /join shows up here instantly.') }}</p>
                @if($activeCount)<a href="{{ route('owner.leads.index') }}" class="bm-btn bm-btn-ghost">{{ __('Clear filters') }}</a>@endif
            </div>
        @else
            {{-- Desktop table --}}
            <div class="bm-table-scroll ld-table-wrap">
                <table class="bm-table ld-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Business') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Area') }}</th>
                            <th class="bm-center">{{ __('Branches') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Registered') }}</th>
                            <th class="bm-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                            <tr>
                                <td>
                                    <div class="ld-name">@if($lead->status === 'new')<span class="ld-new-dot" title="{{ __('New') }}"></span>@endif<a href="{{ route('owner.leads.show', $lead) }}" style="color:inherit;">{{ $lead->full_name }}</a>@if($lead->interaction_count > 1)<span class="ld-repeat" title="{{ __('Registered again') }}"><i data-feather="repeat" style="width:10px;height:10px;"></i>{{ $lead->interaction_count }}</span>@endif</div>
                                    <div class="ld-sub"><span class="ld-ltr">{{ $lead->phone }}</span></div>
                                </td>
                                <td><span class="ld-biz">{{ $lead->business_name }}</span></td>
                                <td><span class="ld-type">{{ $lead->businessTypeLabel() }}</span></td>
                                <td>
                                    <div class="ld-name" style="font-weight:500;">{{ $lead->area ?: $lead->cityLabel() }}</div>
                                    @if($lead->area)<div class="ld-sub">{{ $lead->cityLabel() }}</div>@endif
                                </td>
                                <td class="bm-center"><span class="bm-count">{{ $lead->number_of_branches }}</span></td>
                                <td>
                                    <div class="ld-name" style="font-weight:500;">{{ $lead->sourceLabel() }}</div>
                                    @if($lead->campaign)<div class="ld-sub"><span class="ld-ltr">{{ $lead->campaign }}</span></div>@endif
                                </td>
                                <td>@include('owner.leads._status', ['lead' => $lead, 'canManage' => $canManage])</td>
                                <td><div class="ld-date">{{ $lead->created_at?->translatedFormat('d M Y') }}<small>{{ $lead->created_at?->format('H:i') }}</small></div></td>
                                <td class="bm-end">@include('owner.leads._actions', ['lead' => $lead, 'canManage' => $canManage])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Phone / tablet cards --}}
            <div class="ld-cards">
                @foreach($leads as $lead)
                    <article class="ld-card">
                        <div class="ld-card-top">
                            <div style="min-width:0;">
                                <div class="ld-name">@if($lead->status === 'new')<span class="ld-new-dot" title="{{ __('New') }}"></span>@endif<a href="{{ route('owner.leads.show', $lead) }}" style="color:inherit;">{{ $lead->business_name }}</a></div>
                                <div class="ld-sub">{{ $lead->full_name }} · <span class="ld-ltr">{{ $lead->phone }}</span>@if($lead->interaction_count > 1)<span class="ld-repeat">{{ $lead->interaction_count }}×</span>@endif</div>
                            </div>
                            @include('owner.leads._status', ['lead' => $lead, 'canManage' => $canManage])
                        </div>
                        <div class="ld-card-meta">
                            <span>{{ $lead->businessTypeLabel() }}</span>
                            <span>{{ $lead->area ? $lead->area.' · ' : '' }}{{ $lead->cityLabel() }}</span>
                            <span>{{ __('Branches: :n', ['n' => $lead->number_of_branches]) }}</span>
                            <span>{{ $lead->sourceLabel() }}@if($lead->campaign) · <span class="ld-ltr">{{ $lead->campaign }}</span>@endif</span>
                        </div>
                        <div class="ld-card-foot">
                            <span class="ld-date" style="display:flex;gap:8px;align-items:baseline;">{{ $lead->created_at?->translatedFormat('d M Y') }}<small style="display:inline;">{{ $lead->created_at?->format('H:i') }}</small></span>
                            @include('owner.leads._actions', ['lead' => $lead, 'canManage' => $canManage])
                        </div>
                    </article>
                @endforeach
            </div>

            @if($leads->hasPages())
                <div class="bm-pagination">
                    <div class="bm-pagination-info">{{ __('Showing :from–:to of :total', ['from' => $leads->firstItem(), 'to' => $leads->lastItem(), 'total' => $leads->total()]) }}</div>
                    {{ $leads->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

@if($canManage)
{{-- One note dialog for the whole list --}}
<div class="modal fade" id="ld-note-modal" tabindex="-1" aria-labelledby="ld-note-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content ld-note-form" id="ld-note-form" style="border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid var(--bk-border);">
                <h5 class="modal-title" id="ld-note-title" style="font-size:1rem;font-weight:700;">{{ __('Add note') }} <span id="ld-note-name" style="color:var(--bk-text-muted);font-weight:500;"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <label for="ld-note-body" class="bm-label">{{ __('Note') }}</label>
                <textarea id="ld-note-body" name="body" maxlength="2000" required placeholder="{{ __('e.g. Called the owner, interested in bookings and staff. Wants a demo next week.') }}"></textarea>
                <div class="ld-err" id="ld-note-err" role="alert" hidden></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--bk-border);">
                <button type="button" class="bm-btn bm-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="bm-btn bm-btn-primary" id="ld-note-save">{{ __('Save note') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var T = {
        statusFail: @json(__('Could not update the status. Please try again.')),
        noteFail: @json(__('Could not save the note. Please try again.')),
        copied: @json(__('Link copied')),
    };
    function toast(type, msg) { if (window.GlowToast && GlowToast[type]) GlowToast[type](msg); }
    function api(url, method, body) {
        return fetch(url, { method: method, credentials: 'same-origin', body: JSON.stringify(body),
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, body: j }; }); });
    }

    /* phones: start with the insights folded so the list is one scroll away */
    if (window.matchMedia('(max-width: 767px)').matches) document.querySelectorAll('details.ld-panel[open]').forEach(function (d) { d.removeAttribute('open'); });

    /* filters panel */
    var tgl = document.getElementById('ld-filter-toggle'), panel = document.getElementById('ld-filters');
    if (tgl) tgl.addEventListener('click', function () {
        var open = panel.classList.toggle('is-open'); tgl.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    var dateInputs = document.querySelectorAll('#ld-filters input[type=text][name=area]');
    dateInputs.forEach(function (i) { i.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); i.form.submit(); } }); });

    /* inline status change */
    document.addEventListener('change', function (e) {
        var sel = e.target.closest('select[data-status-url]'); if (!sel) return;
        var prev = sel.getAttribute('data-current'); sel.disabled = true;
        api(sel.getAttribute('data-status-url'), 'PATCH', { status: sel.value }).then(function (r) {
            sel.disabled = false;
            if (r.ok && r.body.status) {
                sel.className = 'ld-status ld-tone-' + r.body.data.tone; sel.setAttribute('data-current', sel.value);
                toast('success', r.body.message);
                // keep the twin select (table + card render the same lead) in step
                var row = sel.closest('tr, article'); if (row) { var dot = row.querySelector('.ld-new-dot'); if (dot && sel.value !== 'new') dot.remove(); }
            } else { sel.value = prev; toast('error', (r.body && r.body.message) || T.statusFail); }
        }).catch(function () { sel.disabled = false; sel.value = prev; toast('error', T.statusFail); });
    });

    /* note dialog */
    var modalEl = document.getElementById('ld-note-modal');
    if (modalEl) {
        var form = document.getElementById('ld-note-form'), body = document.getElementById('ld-note-body'), err = document.getElementById('ld-note-err'), save = document.getElementById('ld-note-save');
        var url = null, modal = (window.bootstrap && bootstrap.Modal) ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-note-open]'); if (!b) return;
            url = b.getAttribute('data-note-url'); document.getElementById('ld-note-name').textContent = '— ' + b.getAttribute('data-note-name');
            body.value = ''; err.hidden = true; if (modal) modal.show();
            setTimeout(function () { body.focus(); }, 300);
        });
        form.addEventListener('submit', function (e) {
            e.preventDefault(); if (!url) return; err.hidden = true; save.disabled = true;
            api(url, 'POST', { body: body.value }).then(function (r) {
                save.disabled = false;
                if (r.ok && r.body.status) { if (modal) modal.hide(); toast('success', r.body.message); }
                else { err.textContent = (r.body && (r.body.message || T.noteFail)) || T.noteFail; err.hidden = false; }
            }).catch(function () { save.disabled = false; err.textContent = T.noteFail; err.hidden = false; });
        });
    }

    /* tracking links */
    var rows = document.getElementById('ld-linkrows');
    if (rows) {
        var pageSel = document.getElementById('ld-link-page'), camp = document.getElementById('ld-link-campaign');
        function build() {
            var c = (camp.value || '').toLowerCase().replace(/[^a-z0-9_\-.]/g, '').slice(0, 60);
            rows.querySelectorAll('.ld-linkrow').forEach(function (r) {
                r.querySelector('code').textContent = pageSel.value + '?source=' + r.getAttribute('data-src') + (c ? '&campaign=' + c : '');
            });
        }
        pageSel.addEventListener('change', build); camp.addEventListener('input', build); build();
        rows.addEventListener('click', function (e) {
            var b = e.target.closest('[data-copy]'); if (!b) return;
            var text = b.parentNode.querySelector('code').textContent;
            (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () { toast('success', T.copied); })
                .catch(function () { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); toast('success', T.copied); } catch (x) {} t.remove(); });
        });
    }

    if (typeof feather !== 'undefined') setTimeout(function () { feather.replace(); }, 60);
})();
</script>
@endpush
@endsection
