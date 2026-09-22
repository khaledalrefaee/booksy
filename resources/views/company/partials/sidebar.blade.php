@php
    $authCompany = Auth::guard('company')->user();
    $branches    = $branchContextBranches ?? ($authCompany ? $authCompany->branches()->orderBy('sort_order')->get() : collect());
    $ctx         = $branchContext ?? null;      // Branch|null  (null = All Branches)
    $ctxId       = $branchContextId ?? null;    // int|null
    $firstBranch = $branches->first();
    $feat        = fn (string $k) => $authCompany?->hasFeature($k) ?? false;
    $fromPath    = request()->path();
    $ctxUrl      = fn ($to) => route('company.context.switch', ['to' => $to, 'from' => $fromPath]);

    // Shared pending-appointments badge (computed once in the layout)
    $pendingCount = $bkPendingCount ?? 0;

    // ── Context-aware link targets ──────────────────────────────────────────
    // Dashboard: aggregate for All Branches, the branch's own overview otherwise.
    $dashUrl    = $ctx ? route('company.branches.show', $ctx) : route('company.dashboard');
    $dashActive = request()->routeIs('company.dashboard') || request()->routeIs('company.branches.show');

    $servicesUrl = $ctx
        ? route('company.branches.services.index', $ctx)
        : ($firstBranch ? route('company.branches.services.index', $firstBranch) : route('company.branches.index'));

    $staffUrl    = $ctx ? route('company.branches.employees.index', $ctx) : route('company.staff.index');
    $staffActive = request()->routeIs('company.branches.employees.*') || request()->routeIs('company.staff.*');
@endphp

<nav class="sidebar bk-sidebar-v4">
    <div class="sidebar-header">
        <a href="{{ $dashUrl }}" class="sidebar-brand">GlowRez<span>.</span></a>
    </div>

    <div class="sidebar-body bk-sb4">

        {{-- ══ Branch context selector ══ --}}
        <div class="bk-ctx" data-bk-ctx>
            <button type="button" class="bk-ctx-btn" data-bk-ctx-toggle aria-expanded="false"
                    data-tour="rail-branch" title="{{ __('Switch branch') }}">
                <span class="bk-ctx-mark">
                    <i data-feather="{{ $ctx ? 'map-pin' : 'grid' }}"></i>
                </span>
                <span class="bk-ctx-text">
                    <span class="bk-ctx-co">{{ $authCompany?->localizedName() }}</span>
                    <span class="bk-ctx-cur">{{ $ctx?->localizedName() ?? __('All Branches') }}</span>
                </span>
                <i data-feather="chevron-down" class="bk-ctx-caret"></i>
            </button>

            <div class="bk-ctx-menu" data-bk-ctx-menu hidden>
                @if($branches->count() > 6)
                <div class="bk-ctx-search">
                    <i data-feather="search"></i>
                    <input type="text" data-bk-ctx-search placeholder="{{ __('Search branches') }}…" aria-label="{{ __('Search branches') }}">
                </div>
                @endif

                <div class="bk-ctx-list" data-bk-ctx-scroll>
                    <a href="{{ $ctxUrl('all') }}" class="bk-ctx-row {{ ! $ctx ? 'active' : '' }}" data-bk-ctx-name="{{ __('All Branches') }}">
                        <span class="bk-ctx-row-ic"><i data-feather="grid"></i></span>
                        <span class="bk-ctx-row-name">{{ __('All Branches') }}</span>
                        @unless($ctx)<i data-feather="check" class="bk-ctx-check"></i>@endunless
                    </a>

                    @foreach($branches as $b)
                    <a href="{{ $ctxUrl($b->id) }}" class="bk-ctx-row {{ $ctxId === $b->id ? 'active' : '' }}" data-bk-ctx-name="{{ $b->localizedName() }}">
                        <span class="bk-ctx-row-ic"><i data-feather="map-pin"></i></span>
                        <span class="bk-ctx-row-name">{{ $b->localizedName() }}</span>
                        @if($ctxId === $b->id)<i data-feather="check" class="bk-ctx-check"></i>@endif
                    </a>
                    @endforeach

                    <div class="bk-ctx-empty" data-bk-ctx-empty hidden>{{ __('No branches found') }}</div>
                </div>

                <a href="{{ route('company.branches.index') }}" class="bk-ctx-manage">
                    <i data-feather="settings"></i><span>{{ __('Manage branches') }}</span>
                </a>
            </div>
        </div>

        {{-- ══ Navigation ══ --}}
        <div class="bk-nav">

            {{-- MAIN --}}
            <div class="bk-nav-group">
                <div class="bk-nav-title">{{ __('Main') }}</div>
                <a href="{{ $dashUrl }}" class="bk-nl {{ $dashActive ? 'active' : '' }}">
                    <i data-feather="home"></i><span>{{ __('Dashboard') }}</span>
                </a>
                @feature('leaves')
                <a href="{{ route('company.team-calendar') }}" class="bk-nl {{ request()->routeIs('company.team-calendar') ? 'active' : '' }}">
                    <i data-feather="calendar"></i><span>{{ __('Calendar') }}</span>
                </a>
                @endfeature
                <a href="{{ route('company.appointments.index') }}" class="bk-nl {{ request()->routeIs('company.appointments.*') ? 'active' : '' }}">
                    <i data-feather="check-square"></i><span>{{ __('Appointments') }}</span>
                    @if($pendingCount > 0)<span class="bk-nl-badge">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>@endif
                </a>
                <a href="{{ route('company.customers.index') }}" class="bk-nl {{ request()->routeIs('company.customers.*') ? 'active' : '' }}">
                    <i data-feather="users"></i><span>{{ __('Customers') }}</span>
                </a>
            </div>

            {{-- OPERATIONS --}}
            <div class="bk-nav-group">
                <div class="bk-nav-title">{{ __('Operations') }}</div>
                <a href="{{ $servicesUrl }}" class="bk-nl {{ request()->routeIs('company.branches.services.*') ? 'active' : '' }}" data-tour="branch-services">
                    <i data-feather="scissors"></i><span>{{ __('Services') }}</span>
                </a>
                <a href="{{ $staffUrl }}" class="bk-nl {{ $staffActive ? 'active' : '' }}" data-tour="branch-employees">
                    <i data-feather="users"></i><span>{{ __('Staff') }}</span>
                </a>
                @feature('attendance')
                <a href="{{ route('company.attendance.index') }}" class="bk-nl {{ request()->routeIs('company.attendance.*') ? 'active' : '' }}">
                    <i data-feather="check-circle"></i><span>{{ __('Attendance') }}</span>
                </a>
                @endfeature
                @feature('leaves')
                <a href="{{ route('company.employee-leaves.index') }}" class="bk-nl {{ request()->routeIs('company.employee-leaves.*') ? 'active' : '' }}">
                    <i data-feather="user-x"></i><span>{{ __('Leaves') }}</span>
                </a>
                @endfeature
                @feature('payroll')
                <a href="{{ route('company.payroll.index') }}" class="bk-nl {{ request()->routeIs('company.payroll.*') || request()->routeIs('company.employees.payroll') ? 'active' : '' }}">
                    <i data-feather="dollar-sign"></i><span>{{ __('Payroll') }}</span>
                </a>
                <a href="{{ route('company.deductions.index') }}" class="bk-nl {{ request()->routeIs('company.deductions.*') && !request()->routeIs('company.employees.deductions.*') ? 'active' : '' }}">
                    <i data-feather="minus-circle"></i><span>{{ __('Deductions') }}</span>
                </a>
                @endfeature
            </div>

            {{-- BRANCH SETUP — only meaningful inside a single branch --}}
            @if($ctx)
            <div class="bk-nav-group">
                <div class="bk-nav-title">{{ __('Branch setup') }}</div>
                <a href="{{ route('company.branches.working-hours.edit', $ctx) }}" class="bk-nl {{ request()->routeIs('company.branches.working-hours.*') ? 'active' : '' }}">
                    <i data-feather="clock"></i><span>{{ __('Working Hours') }}</span>
                </a>
                <a href="{{ route('company.branches.gallery', $ctx) }}" class="bk-nl {{ request()->routeIs('company.branches.gallery') ? 'active' : '' }}">
                    <i data-feather="image"></i><span>{{ __('Gallery') }}</span>
                </a>
                <a href="{{ route('company.branches.loyalty.index', $ctx) }}" class="bk-nl {{ request()->routeIs('company.branches.loyalty.*') ? 'active' : '' }}">
                    <i data-feather="award"></i><span>{{ __('Loyalty') }}</span>
                </a>
                @feature('finance')
                <a href="{{ route('company.branches.cash.index', $ctx) }}" class="bk-nl {{ request()->routeIs('company.branches.cash.*') ? 'active' : '' }}">
                    <i data-feather="credit-card"></i><span>{{ __('Cash Register') }}</span>
                </a>
                @endfeature
            </div>
            @endif

            {{-- FINANCE (company-wide) --}}
            @if($feat('finance') || $feat('inventory'))
            <div class="bk-nav-group">
                <div class="bk-nav-title">{{ __('Finance') }}</div>
                @feature('finance')
                <a href="{{ route('company.cash.global') }}" class="bk-nl {{ request()->routeIs('company.cash.global') ? 'active' : '' }}">
                    <i data-feather="credit-card"></i><span>{{ __('Cash Registers') }}</span>
                </a>
                <a href="{{ route('company.invoices.index') }}" class="bk-nl {{ request()->routeIs('company.invoices.*') ? 'active' : '' }}">
                    <i data-feather="file-text"></i><span>{{ __('Invoices') }}</span>
                </a>
                <a href="{{ route('company.recurring-expenses.index') }}" class="bk-nl {{ request()->routeIs('company.recurring-expenses.*') ? 'active' : '' }}">
                    <i data-feather="repeat"></i><span>{{ __('Recurring Expenses') }}</span>
                </a>
                <a href="{{ route('company.debts.index') }}" class="bk-nl {{ request()->routeIs('company.debts.*') ? 'active' : '' }}">
                    <i data-feather="alert-circle"></i><span>{{ __('Customer Debts') }}</span>
                </a>
                @endfeature
                @feature('inventory')
                <a href="{{ route('company.inventory.index') }}" class="bk-nl {{ request()->routeIs('company.inventory.*') || request()->routeIs('company.product-categories.*') ? 'active' : '' }}">
                    <i data-feather="package"></i><span>{{ __('Inventory') }}</span>
                </a>
                @endfeature
            </div>
            @endif

            {{-- GROWTH --}}
            <div class="bk-nav-group">
                <div class="bk-nav-title">{{ __('Growth') }}</div>
                <a href="{{ route('company.marketing.offers') }}" class="bk-nl {{ request()->routeIs('company.marketing.offers') ? 'active' : '' }}">
                    <i data-feather="tag"></i><span>{{ __('Offers') }}</span>
                </a>
                <a href="{{ route('company.marketing.booking-sources') }}" class="bk-nl {{ request()->routeIs('company.marketing.booking-sources') ? 'active' : '' }}">
                    <i data-feather="share-2"></i><span>{{ __('Booking Sources') }}</span>
                </a>
                <a href="{{ route('company.sms.overview') }}" class="bk-nl {{ request()->routeIs('company.sms.*') ? 'active' : '' }}">
                    <i data-feather="message-square"></i><span>{{ __('SMS') }}</span>
                </a>
                @feature('reports')
                <a href="{{ route('company.reports.profit-loss') }}" class="bk-nl {{ request()->routeIs('company.reports.*') ? 'active' : '' }}">
                    <i data-feather="bar-chart-2"></i><span>{{ __('Reports') }}</span>
                </a>
                @endfeature
            </div>

            {{-- SYSTEM --}}
            <div class="bk-nav-group">
                <div class="bk-nav-title">{{ __('System') }}</div>
                <a href="{{ route('company.service-categories.index') }}" class="bk-nl {{ request()->routeIs('company.service-categories.*') ? 'active' : '' }}" data-tour="svc-cats">
                    <i data-feather="tag"></i><span>{{ __('Service categories') }}</span>
                </a>
                <a href="{{ route('company.resources.index') }}" class="bk-nl {{ request()->routeIs('company.resources.*') ? 'active' : '' }}">
                    <i data-feather="grid"></i><span>{{ __('Resources & rooms') }}</span>
                </a>
                <a href="{{ route('company.booking-policy.edit') }}" class="bk-nl {{ request()->routeIs('company.booking-policy.*') ? 'active' : '' }}">
                    <i data-feather="shield"></i><span>{{ __('Booking policy') }}</span>
                </a>
                <a href="{{ route('company.profile.show') }}" class="bk-nl {{ request()->routeIs('company.profile.*') ? 'active' : '' }}">
                    <i data-feather="settings"></i><span>{{ __('Settings') }}</span>
                </a>
                <a href="{{ $authCompany ? route('front.show', $authCompany) : '#' }}" target="_blank" class="bk-nl bk-nl-muted">
                    <i data-feather="external-link"></i><span>{{ __('View public page') }}</span>
                </a>
            </div>

        </div>
    </div>
</nav>

@include('company.partials.sidebar-styles')

<script>
(function () {
    // Tour compatibility: the guided tour still calls this; the flat nav has no
    // sections to switch, so it is a no-op.
    window.bkShowSection = window.bkShowSection || function () {};

    var root   = document.querySelector('[data-bk-ctx]');
    if (!root) return;
    var toggle = root.querySelector('[data-bk-ctx-toggle]');
    var menu   = root.querySelector('[data-bk-ctx-menu]');
    var search = root.querySelector('[data-bk-ctx-search]');
    var empty  = root.querySelector('[data-bk-ctx-empty]');

    function open()  { menu.hidden = false; toggle.setAttribute('aria-expanded', 'true');  root.classList.add('open'); if (search) setTimeout(function(){ search.focus(); }, 30); }
    function close() { menu.hidden = true;  toggle.setAttribute('aria-expanded', 'false'); root.classList.remove('open'); }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.hidden ? open() : close();
    });

    // Close on outside click / Escape
    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !menu.hidden) close();
    });

    // Filter branch rows
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var shown = 0;
            root.querySelectorAll('.bk-ctx-row').forEach(function (row) {
                var name = (row.getAttribute('data-bk-ctx-name') || '').toLowerCase();
                var hit  = name.indexOf(q) !== -1;
                row.style.display = hit ? '' : 'none';
                if (hit) shown++;
            });
            if (empty) empty.hidden = shown !== 0;
        });
    }
})();
</script>
