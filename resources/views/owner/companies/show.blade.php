@extends('owner.dashboard')
@section('content')
@php
    $statusTone = match ($company->status) {
        'active'    => 'green',
        'suspended' => 'red',
        default     => 'orange',
    };
    $can = fn (string $k) => \Illuminate\Support\Facades\Gate::allows('owner-can', $k);
    // Tabs the current admin may see
    $visibleTabs = collect($tabs)->filter(fn ($t) => $can($t['permission']));
    $defaultTab  = $visibleTabs->keys()->first() ?? 'overview';
@endphp

<div class="page-content">
    @include('owner.partials.flash')

    <div class="bk-ws">

        {{-- ══════════ Company header ══════════ --}}
        <div class="bk-ws-head">
            @if ($company->logo)
                <img src="{{ asset('storage/'.$company->logo) }}" alt="" class="bk-ws-logo">
            @else
                <div class="bk-ws-logo"><i data-feather="briefcase"></i></div>
            @endif

            <div class="bk-ws-id">
                <h1 class="bk-ws-name">
                    {{ $company->localizedName() }}
                    <span class="bk-pill bk-pill--{{ $statusTone }}">{{ __($company->status) }}</span>
                    @if ($company->plan_id !== null && ! $company->isSubscriptionActive())
                        <span class="bk-pill bk-pill--red">{{ __('Expired') }}</span>
                    @endif
                </h1>
                <div class="bk-ws-meta">
                    <span><b>{{ __('Email') }}:</b> {{ $company->email }}</span>
                    <span><b>{{ __('Phone') }}:</b> {{ $company->phone ?: '—' }}</span>
                    <span><b>{{ __('Category') }}:</b> {{ $company->category?->localizedName() ?? '—' }}</span>
                    <span><b>{{ __('Plan') }}:</b> {{ $company->plan?->localizedName() ?? __('No plan') }}</span>
                </div>
            </div>

            <div class="bk-ws-head-actions">
                @can('owner-can', 'companies.impersonate')
                <form method="post" action="{{ route('owner.companies.impersonate', $company) }}" class="mb-0"
                      onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
                    @csrf
                    <button type="submit" class="bk-btn bk-btn--gold">
                        <i data-feather="log-in"></i> {{ __('Login as company') }}
                    </button>
                </form>
                @endcan
                <a href="{{ route('owner.companies.index') }}" class="bk-btn bk-btn--ghost">
                    <i data-feather="arrow-left"></i> {{ __('Back to list') }}
                </a>
            </div>
        </div>

        {{-- ══════════ Top KPI strip ══════════ --}}
        <div class="bk-ws-kpis">
            @foreach ([
                ['label' => __('Branches'),     'value' => $stats['branches'],     'icon' => 'map-pin',  'accent' => 'gold',   'tab' => 'branches'],
                ['label' => __('Employees'),    'value' => $stats['employees'],    'icon' => 'users',    'accent' => 'blue',   'tab' => 'team'],
                ['label' => __('Appointments'), 'value' => $stats['appointments'], 'icon' => 'calendar', 'accent' => 'green',  'tab' => 'appointments'],
                ['label' => __('Waitlist'),     'value' => $stats['waitlist'],     'icon' => 'clock',    'accent' => 'orange', 'tab' => 'appointments'],
            ] as $kpi)
                <div class="bk-kpi" data-accent="{{ $kpi['accent'] }}"
                     @if($visibleTabs->has($kpi['tab'])) role="button" data-ws-tab-link="{{ $kpi['tab'] }}" @endif>
                    <div class="bk-kpi-label"><i data-feather="{{ $kpi['icon'] }}"></i> {{ $kpi['label'] }}</div>
                    <div class="bk-kpi-num">{{ number_format($kpi['value']) }}</div>
                </div>
            @endforeach
        </div>

        {{-- ══════════ Tab nav ══════════ --}}
        <nav class="bk-ws-nav" role="tablist" aria-label="{{ __('Company modules') }}">
            @foreach ($visibleTabs as $key => $tab)
                <button type="button" class="bk-ws-tab {{ $key === $defaultTab ? 'active' : '' }}"
                        data-ws-tab="{{ $key }}" role="tab"
                        aria-selected="{{ $key === $defaultTab ? 'true' : 'false' }}">
                    <i data-feather="{{ $tab['icon'] }}"></i>
                    <span>{{ __($tab['label']) }}</span>
                </button>
            @endforeach
        </nav>

        {{-- ══════════ Lazy-loaded tab body ══════════ --}}
        <div class="bk-ws-body" id="bk-ws-body" aria-live="polite">
            <div class="bk-ws-loading">
                <div class="bk-ws-spin"></div>
                <span>{{ __('Loading…') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ══════════ Shared drawer (shell provides one for all tabs) ══════════ --}}
<div class="bk-drawer" id="bk-ws-drawer" aria-hidden="true">
    <div class="bk-drawer-scrim" data-ws-drawer-close></div>
    <div class="bk-drawer-panel" role="dialog" aria-modal="true">
        <div class="bk-drawer-head">
            <h2 class="bk-drawer-title" id="bk-ws-drawer-title">{{ __('Details') }}</h2>
            <button type="button" class="bk-btn bk-btn--icon bk-btn--ghost" data-ws-drawer-close aria-label="{{ __('Close') }}">
                <i data-feather="x"></i>
            </button>
        </div>
        <div class="bk-drawer-body" id="bk-ws-drawer-body"></div>
    </div>
</div>

@push('scripts')
<script>
/* ════════════════════════════════════════════════════════════════
   Company Workspace shell runtime — the contract every tab uses.
   Exposed as window.bkWs:
     bkWs.switchTab(key)      – activate + lazy-load a tab
     bkWs.reload()            – re-fetch the current tab (after an action)
     bkWs.openDrawer(url,ttl) – fetch a partial into the side drawer
     bkWs.closeDrawer()
     bkWs.toast(msg, ok)      – transient feedback
   Delegated attributes tabs can use in their HTML:
     data-ws-tab-link="key"   – switch to another tab
     data-ws-drawer="url"     – open drawer with fetched partial
       (+ data-ws-drawer-title="…")
     data-ws-action           – on a <form>: submit via fetch (JSON envelope),
                                toast + reload current tab on success
════════════════════════════════════════════════════════════════ */
(function () {
    var urls = @json($visibleTabs->keys()->mapWithKeys(fn ($k) => [$k => route("owner.companies.ws.$k", $company->id)]));
    var CSRF = '{{ csrf_token() }}';
    var body = document.getElementById('bk-ws-body');
    var cache = {};
    var current = '{{ $defaultTab }}';

    function feather() { if (window.feather) window.feather.replace(); }

    function render(html, tab) {
        body.innerHTML = html;
        feather();
        body.dispatchEvent(new CustomEvent('bk-ws:loaded', { bubbles: true, detail: { tab: tab } }));
    }

    function loading() {
        body.innerHTML = '<div class="bk-ws-loading"><div class="bk-ws-spin"></div><span>{{ __('Loading…') }}</span></div>';
    }

    function fetchTab(tab, force) {
        if (!urls[tab]) return;
        if (cache[tab] && !force) { render(cache[tab], tab); return; }
        loading();
        fetch(urls[tab], { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) { cache[tab] = html; render(html, tab); })
            .catch(function () { body.innerHTML = '<div class="bk-empty"><p>{{ __('Could not load this section.') }}</p></div>'; });
    }

    function switchTab(tab, push) {
        if (!urls[tab]) return;
        current = tab;
        document.querySelectorAll('.bk-ws-tab').forEach(function (b) {
            var on = b.dataset.wsTab === tab;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        fetchTab(tab, false);
        if (push !== false) {
            var u = new URL(window.location);
            u.searchParams.set('tab', tab);
            history.replaceState({}, '', u);
        }
    }

    /* Drawer */
    var drawer = document.getElementById('bk-ws-drawer');
    var drawerBody = document.getElementById('bk-ws-drawer-body');
    var drawerTitle = document.getElementById('bk-ws-drawer-title');
    function openDrawer(url, title) {
        drawerTitle.textContent = title || '{{ __('Details') }}';
        drawerBody.innerHTML = '<div class="bk-ws-loading"><div class="bk-ws-spin"></div></div>';
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) { drawerBody.innerHTML = html; feather(); })
            .catch(function () { drawerBody.innerHTML = '<div class="bk-empty"><p>{{ __('Could not load.') }}</p></div>'; });
    }
    function closeDrawer() { drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); }

    /* Toast */
    function toast(msg, ok) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;z-index:1090;bottom:22px;left:50%;transform:translateX(-50%);' +
            'padding:11px 20px;border-radius:12px;font-size:.85rem;font-weight:600;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.25);' +
            'background:' + (ok === false ? '#B23A48' : '#4B5D34') + ';opacity:0;transition:opacity .2s,transform .2s;';
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.style.opacity = '1'; });
        setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 250); }, 2600);
    }

    /* Nav clicks */
    document.querySelectorAll('.bk-ws-tab').forEach(function (b) {
        b.addEventListener('click', function () { switchTab(this.dataset.wsTab); });
    });

    /* Delegated interactions from injected tab/drawer HTML */
    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-ws-tab-link]');
        if (link && document.querySelector('.bk-ws-tab[data-ws-tab="' + link.dataset.wsTabLink + '"]')) {
            e.preventDefault(); switchTab(link.dataset.wsTabLink); return;
        }
        var dr = e.target.closest('[data-ws-drawer]');
        if (dr) { e.preventDefault(); openDrawer(dr.dataset.wsDrawer, dr.dataset.wsDrawerTitle); return; }
        if (e.target.closest('[data-ws-drawer-close]')) { closeDrawer(); return; }

        /* In-tab sub-navigation: [data-ws-subnav="key"] toggles [data-ws-panel="key"].
           Scoped to the nearest [data-ws-subnav-group] so multiple groups can coexist. */
        var sn = e.target.closest('[data-ws-subnav]');
        if (sn) {
            e.preventDefault();
            var group = sn.closest('[data-ws-subnav-group]') || body;
            var key = sn.dataset.wsSubnav;
            group.querySelectorAll('[data-ws-subnav]').forEach(function (b) {
                if (b.closest('[data-ws-subnav-group]') === group || (!b.closest('[data-ws-subnav-group]') && group === body))
                    b.classList.toggle('active', b.dataset.wsSubnav === key);
            });
            group.querySelectorAll('[data-ws-panel]').forEach(function (p) {
                if (p.closest('[data-ws-subnav-group]') === group || (!p.closest('[data-ws-subnav-group]') && group === body))
                    p.style.display = (p.dataset.wsPanel === key) ? '' : 'none';
            });
            return;
        }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });

    /* Delegated action-form submit (POST/PATCH/DELETE via fetch) */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-ws-action]')) return;
        e.preventDefault();
        var btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        var fd = new FormData(form);
        if (!fd.has('_token')) fd.append('_token', CSRF);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
            if (btn) btn.disabled = false;
            toast(res.d.message || (res.ok ? 'Done' : 'Error'), res.ok);
            if (res.ok) { closeDrawer(); reload(); }
        })
        .catch(function () { if (btn) btn.disabled = false; toast('{{ __('Something went wrong.') }}', false); });
    });

    function reload() { fetchTab(current, true); }

    window.bkWs = {
        switchTab: switchTab, reload: reload,
        openDrawer: openDrawer, closeDrawer: closeDrawer, toast: toast
    };

    /* Boot: honour ?tab= deep-link */
    var params = new URLSearchParams(window.location.search);
    var start = params.get('tab');
    if (start && urls[start]) switchTab(start, false); else fetchTab(current, false);
    feather();
})();
</script>
@endpush
@endsection
