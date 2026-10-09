@php
    use Illuminate\Support\Facades\Gate;

    // Shared counts from the layout when available (avoids duplicate queries)
    $pendingAppts = $bkOwnerPendingAppts ?? null;
    if ($pendingAppts === null) {
        try { $pendingAppts = (int) \App\Models\Appointment::where('status', 'pending')->count(); }
        catch (\Throwable $e) { $pendingAppts = 0; }
    }
    $pendingCompanies = $bkOwnerPendingCompanies ?? null;
    if ($pendingCompanies === null) {
        try { $pendingCompanies = (int) \App\Models\Company::where('status', 'pending')->count(); }
        catch (\Throwable $e) { $pendingCompanies = 0; }
    }

    // Branch photos waiting for review (only queried when the viewer can review).
    $pendingPhotos = $bkOwnerPendingPhotos ?? null;
    if ($pendingPhotos === null) {
        try { $pendingPhotos = Gate::allows('owner-can', 'photos.review')
            ? (int) \App\Models\BranchImage::where('status', 'pending')->count() : 0; }
        catch (\Throwable $e) { $pendingPhotos = 0; }
    }

    // Pre-launch leads still marked "new" (only queried when the viewer can see leads).
    $newLeads = $bkOwnerNewLeads ?? null;
    if ($newLeads === null) {
        try { $newLeads = Gate::allows('owner-can', 'leads.view')
            ? (int) \App\Models\Lead::where('status', 'new')->count() : 0; }
        catch (\Throwable $e) { $newLeads = 0; }
    }

    $can = fn (string $k) => Gate::allows('owner-can', $k);

    // One row of the nav. `$on` is the route pattern(s) that mark it active.
    $row = fn (string $route, string $label, string $icon, $on, ?string $perm = null, int $badge = 0) => [
        'href'  => route($route),
        'label' => $label,
        'icon'  => $icon,
        'on'    => (array) $on,
        'perm'  => $perm,
        'badge' => $badge,
    ];

    // The whole console in one flat list. `open` = expanded on a first visit;
    // after that the person's own choice (localStorage) wins. A group that holds
    // the current page is always open, so you can never lose the active row.
    $groups = [
        ['key' => 'overview', 'title' => null, 'open' => true, 'items' => [
            $row('owner.dashboard',          __('Dashboard'),    'home',      'owner.dashboard'),
            $row('owner.appointments.index', __('Appointments'), 'calendar',  'owner.appointments.*', null, $pendingAppts),
            $row('owner.companies.index',    __('Companies'),    'briefcase', 'owner.companies.*',    null, $pendingCompanies),
            $row('owner.leads.index',        __('Leads'),        'user-plus', 'owner.leads.*',        'leads.view', $newLeads),
        ]],
        ['key' => 'operations', 'title' => __('Operations'), 'open' => true, 'items' => [
            $row('owner.branches.index',        __('Branches'),        'map-pin',   ['owner.branches.*', 'owner.services.*', 'owner.employees.*']),
            $row('owner.employee-leaves.index', __('Employee leaves'), 'user-x',    'owner.employee-leaves.*'),
            $row('owner.customers.index',       __('Customers'),       'users',     'owner.customers.*', 'operations.view'),
            $row('owner.invoices.index',        __('Invoices'),        'file-text', 'owner.invoices.*',  'operations.view'),
        ]],
        ['key' => 'content', 'title' => __('Content & messaging'), 'open' => false, 'items' => [
            $row('owner.reviews.index',       __('Reviews'),       'star',  'owner.reviews.*',       'reviews.moderate'),
            $row('owner.photo-reviews.index', __('Photo review'),  'image', 'owner.photo-reviews.*', 'photos.review', $pendingPhotos),
            $row('owner.announcements.index', __('Announcements'), 'bell',  'owner.announcements.*', 'notifications.send'),
            $row('owner.emails.index',        __('Emails'),        'mail',  'owner.emails.*',        'notifications.send'),
        ]],
        ['key' => 'billing', 'title' => __('Billing'), 'open' => false, 'items' => [
            $row('owner.plans.index',                 __('Plans'),         'package',    'owner.plans.*'),
            $row('owner.subscriptions.index',         __('Subscriptions'), 'refresh-cw', 'owner.subscriptions.*',         'billing.view'),
            $row('owner.subscription-payments.index', __('Payments'),      'dollar-sign','owner.subscription-payments.*', 'billing.view'),
            $row('owner.coupons.index',               __('Coupons'),       'tag',        'owner.coupons.*',               'coupons.manage'),
        ]],
        ['key' => 'sms', 'title' => __('SMS credits'), 'open' => false, 'items' => [
            $row('owner.sms.overview',     __('Overview'),         'layout',      'owner.sms.overview'),
            $row('owner.sms.analytics',    __('Analytics'),        'trending-up', 'owner.sms.analytics'),
            $row('owner.sms.companies',    __('Companies usage'),  'briefcase',   'owner.sms.companies'),
            $row('owner.sms.branches',     __('Branches usage'),   'map-pin',     'owner.sms.branches'),
            $row('owner.sms.packages',     __('Packages'),         'box',         'owner.sms.packages'),
            $row('owner.sms.templates',    __('Message templates'),'edit-3',      'owner.sms.templates'),
            $row('owner.sms.pricing',      __('Pricing'),          'tag',         'owner.sms.pricing'),
            $row('owner.sms.transactions', __('Transactions'),     'repeat',      'owner.sms.transactions'),
            $row('owner.sms.logs',         __('Message logs'),     'list',        'owner.sms.logs'),
        ]],
        ['key' => 'insights', 'title' => __('Insights'), 'open' => false, 'items' => [
            $row('owner.field-sales.index',   __('Field sales'),    'map-pin',     'owner.field-sales.*',   'field-visits.view.all'),
            $row('owner.reports.growth',      __('Growth report'),  'trending-up', 'owner.reports.growth',  'reports.view'),
            $row('owner.reports.revenue',     __('Revenue report'), 'bar-chart-2', 'owner.reports.revenue', 'reports.view'),
            $row('owner.audit-log.index',     __('Audit log'),      'shield',      'owner.audit-log.*',     'audit-log.view'),
        ]],
        ['key' => 'setup', 'title' => __('Platform setup'), 'open' => false, 'items' => [
            $row('owner.team.index',               __('Team'),               'users',  'owner.team.*', 'employees.manage'),
            $row('owner.categories.index',         __('Company categories'), 'layers', 'owner.categories.*'),
            $row('owner.service-categories.index', __('Service categories'), 'tag',    'owner.service-categories.*'),
            $row('owner.locations.index',          __('Locations'),          'map',    'owner.locations.*'),
        ]],
    ];

    // Resolve permissions, active state and per-group totals once.
    $navGroups = [];
    foreach ($groups as $g) {
        $items = [];
        foreach ($g['items'] as $it) {
            if ($it['perm'] && ! $can($it['perm'])) { continue; }
            $it['active'] = request()->routeIs(...$it['on']);
            $items[] = $it;
        }
        if (! $items) { continue; }
        $g['items']  = $items;
        $g['active'] = collect($items)->contains('active', true);
        $g['count']  = (int) collect($items)->sum('badge');
        $g['isOpen'] = $g['title'] === null || $g['active'] || $g['open'];
        $navGroups[] = $g;
    }
    $dashUrl = route('owner.dashboard');
@endphp

<nav class="sidebar bk-sidebar-owner" id="bkSidebar" aria-label="{{ __('Main') }}">
    <div class="sidebar-header">
        <a href="{{ $dashUrl }}" class="sidebar-brand sidebar-brand-logo" aria-label="GlowRez">
            <img class="bk-sb-logo bk-sb-logo--light" src="{{ asset('images/glowrez-logo-light_1.webp') }}" alt="GlowRez">
            <img class="bk-sb-logo bk-sb-logo--dark"  src="{{ asset('images/glowrez-logo-dark_1.webp') }}"  alt="" aria-hidden="true">
        </a>
        {{-- Compact mark: only visible when the desktop sidebar is folded --}}
        <a href="{{ $dashUrl }}" class="bk-sb-mark" tabindex="-1" aria-hidden="true">
            <img class="bk-sb-mark--light" src="{{ asset('images/logo-mark.png') }}" alt="">
            <img class="bk-sb-mark--dark"  src="{{ asset('images/logo-mark-dark.webp') }}" alt="">
        </a>
        <button type="button" class="bk-sb-close" data-bk-drawer-close aria-label="{{ __('Close menu') }}">
            <i data-feather="x"></i>
        </button>
    </div>

    <div class="sidebar-body bk-sbo">
        <div class="bk-nav">
            @foreach($navGroups as $g)
            <div class="bk-nav-group {{ $g['title'] ? 'is-collapsible' : '' }} {{ $g['isOpen'] ? 'is-open' : '' }} {{ $g['active'] ? 'has-active' : '' }}"
                 data-bk-group="{{ $g['key'] }}" @if($g['active']) data-bk-active @endif>
                @if($g['title'])
                <button type="button" class="bk-gh" data-bk-gh
                        aria-expanded="{{ $g['isOpen'] ? 'true' : 'false' }}"
                        aria-controls="bk-g-{{ $g['key'] }}">
                    <span class="bk-gh-t">{{ $g['title'] }}</span>
                    @if($g['count'] > 0)
                        <span class="bk-gh-dot" title="{{ $g['count'] }}"></span>
                        <span class="visually-hidden">{{ $g['count'] }}</span>
                    @endif
                    <i data-feather="chevron-down" class="bk-gh-caret"></i>
                </button>
                @endif
                <div class="bk-gb" id="bk-g-{{ $g['key'] }}">
                    <div class="bk-gb-in">
                        @foreach($g['items'] as $it)
                        <a href="{{ $it['href'] }}" title="{{ $it['label'] }}"
                           class="bk-nl {{ $it['active'] ? 'active' : '' }}"
                           @if($it['active']) aria-current="page" @endif>
                            <i data-feather="{{ $it['icon'] }}"></i>
                            <span>{{ $it['label'] }}</span>
                            @if($it['badge'] > 0)
                                <span class="bk-nl-badge">{{ $it['badge'] > 99 ? '99+' : $it['badge'] }}</span>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="bk-sb-foot">
            <a href="{{ route('front.index') }}" target="_blank" rel="noopener" class="bk-nl bk-nl-muted" title="{{ __('View website') }}">
                <i data-feather="external-link"></i><span>{{ __('View website') }}</span>
            </a>
        </div>
    </div>
</nav>
<div class="bk-scrim" data-bk-scrim aria-hidden="true"></div>

<script>
(function () {
    'use strict';
    var KEY   = 'bkOwnerNavOpen';
    var body  = document.body;
    var nav   = document.getElementById('bkSidebar');
    if (!nav) return;
    var mobile = function () { return window.matchMedia('(max-width: 991.98px)').matches; };
    var rtl    = document.documentElement.getAttribute('dir') === 'rtl';

    /* ── Accordion groups ─────────────────────────────────────────────
       The group that owns the current page is always open; every other
       group follows the person's last choice, then the server default. */
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
    function persist() { try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) {} }

    function setOpen(group, open) {
        group.classList.toggle('is-open', open);
        var btn = group.querySelector('[data-bk-gh]');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    nav.querySelectorAll('.bk-nav-group.is-collapsible').forEach(function (g) {
        var k = g.getAttribute('data-bk-group');
        if (!g.hasAttribute('data-bk-active') && typeof saved[k] === 'boolean') setOpen(g, saved[k]);
        var btn = g.querySelector('[data-bk-gh]');
        btn.addEventListener('click', function () {
            var open = !g.classList.contains('is-open');
            setOpen(g, open);
            saved[k] = open; persist();
        });
    });
    // Enable transitions only after the first paint so nothing animates on load.
    requestAnimationFrame(function () { requestAnimationFrame(function () { nav.classList.add('is-ready'); }); });

    // Keep the active row in view on long menus.
    var cur = nav.querySelector('.bk-nl.active');
    if (cur && cur.scrollIntoView) {
        var scroller = nav.querySelector('.bk-nav');
        var r = cur.getBoundingClientRect(), sr = scroller.getBoundingClientRect();
        if (r.top < sr.top || r.bottom > sr.bottom) scroller.scrollTop += r.top - sr.top - sr.height / 3;
    }

    /* ── Remember the folded state on desktop ─────────────────────────── */
    new MutationObserver(function () {
        if (mobile()) return;
        try { localStorage.setItem('bkOwnerFold', body.classList.contains('sidebar-folded') ? '1' : '0'); } catch (e) {}
    }).observe(body, { attributes: true, attributeFilter: ['class'] });

    /* ── Mobile drawer: scrim, Esc, focus, swipe-to-close ─────────────── */
    var scrim = document.querySelector('[data-bk-scrim]');
    var closeBtn = nav.querySelector('[data-bk-drawer-close]');
    var togglers = document.querySelectorAll('.sidebar-toggler');
    var lastToggler = null;
    function closeDrawer() { body.classList.remove('sidebar-open'); }
    if (scrim) scrim.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && body.classList.contains('sidebar-open')) closeDrawer();
    });
    togglers.forEach(function (t) { t.addEventListener('click', function () { lastToggler = t; }); });

    function syncAria() {
        var open = body.classList.contains('sidebar-open');
        togglers.forEach(function (t) { t.setAttribute('aria-expanded', open ? 'true' : 'false'); });
        if (!mobile()) return;
        if (open) { setTimeout(function () { if (closeBtn) closeBtn.focus({ preventScroll: true }); }, 60); }
        else if (lastToggler) { lastToggler.focus({ preventScroll: true }); lastToggler = null; }
    }
    new MutationObserver(syncAria).observe(body, { attributes: true, attributeFilter: ['class'] });
    syncAria();

    var sx = 0, sy = 0, dx = 0, drag = false, tracking = false;
    nav.addEventListener('touchstart', function (e) {
        if (!mobile() || !body.classList.contains('sidebar-open')) return;
        sx = e.touches[0].clientX; sy = e.touches[0].clientY; dx = 0; drag = false; tracking = true;
    }, { passive: true });
    nav.addEventListener('touchmove', function (e) {
        if (!tracking) return;
        var mx = e.touches[0].clientX - sx, my = e.touches[0].clientY - sy;
        var toward = rtl ? mx : -mx;                       // distance travelled toward the closing edge
        if (!drag) {
            if (Math.abs(my) > Math.abs(mx) || toward <= 6) return;   // vertical scroll or wrong way
            drag = true; nav.style.transition = 'none';
        }
        dx = Math.max(0, toward);
        nav.style.transform = 'translateX(' + (rtl ? dx : -dx) + 'px)';
        if (scrim) { scrim.style.transition = 'none'; scrim.style.opacity = String(Math.max(0, 1 - dx / nav.offsetWidth)); }
    }, { passive: true });
    function endDrag() {
        if (!tracking) return;
        tracking = false;
        if (!drag) return;
        drag = false;
        nav.style.transition = ''; if (scrim) { scrim.style.transition = ''; }
        void nav.offsetWidth;                                // commit the dragged position, then animate from it
        nav.style.transform = ''; if (scrim) scrim.style.opacity = '';
        if (dx > Math.min(90, nav.offsetWidth * .3)) closeDrawer();
        dx = 0;
    }
    nav.addEventListener('touchend', endDrag);
    nav.addEventListener('touchcancel', endDrag);

    // Leaving the mobile breakpoint with the drawer open must not leave scroll locked.
    window.addEventListener('resize', function () { if (!mobile()) closeDrawer(); });
})();
</script>
