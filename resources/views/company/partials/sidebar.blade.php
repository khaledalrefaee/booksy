@php
    $authCompany = Auth::guard('company')->user();
    $sidebarBranches = $authCompany ? $authCompany->branches()->orderBy('sort_order')->get() : collect();
    $routeBranch = request()->route('branch');
    $selBranch = $routeBranch ?? $sidebarBranches->first();

    // Which branch-scoped page is active (used for smart branch switching)
    $activeBranchPage = request()->routeIs('company.branches.show') ? 'overview'
        : (request()->routeIs('company.branches.services.*') ? 'services'
        : (request()->routeIs('company.branches.employees.*') ? 'employees'
        : (request()->routeIs('company.branches.working-hours.*') ? 'hours'
        : (request()->routeIs('company.branches.gallery') ? 'gallery'
        : (request()->routeIs('company.branches.cash.index') ? 'cash'
        : (request()->routeIs('company.branches.cash.drawer.archive') ? 'archive' : null))))));

    $branchData = $sidebarBranches->map(fn ($b) => [
        'id'   => $b->id,
        'name' => $b->localizedName(),
        'urls' => [
            'overview'  => route('company.branches.show', $b),
            'services'  => route('company.branches.services.index', $b),
            'employees' => route('company.branches.employees.index', $b),
            'hours'     => route('company.branches.working-hours.edit', $b),
            'gallery'   => route('company.branches.gallery', $b),
            'cash'      => route('company.branches.cash.index', $b),
            'archive'   => route('company.branches.cash.drawer.archive', $b),
        ],
    ])->values();

    // Shared count from the layout when available (avoids a duplicate query)
    $pendingCount = $bkPendingCount ?? 0;
    if (!isset($bkPendingCount) && $authCompany) {
        try {
            $pendingCount = (int) $authCompany->branches()
                ->withCount(['appointments as pc' => fn($q) => $q->where('status', 'pending')])
                ->get()->sum('pc');
        } catch (\Throwable $e) {}
    }

    // Which rail section owns the current route
    if (request()->routeIs('company.branches.*')) {
        $activeSection = 'branch';
    } elseif (request()->routeIs('company.staff.*') || request()->routeIs('company.employee-leaves.*')
        || request()->routeIs('company.attendance.*') || request()->routeIs('company.payroll.*')
        || request()->routeIs('company.employees.payroll')
        || (request()->routeIs('company.deductions.*') && !request()->routeIs('company.employees.deductions.*'))) {
        $activeSection = 'team';
    } elseif (request()->routeIs('company.cash.global') || request()->routeIs('company.invoices.*')
        || request()->routeIs('company.recurring-expenses.*') || request()->routeIs('company.debts.*')
        || request()->routeIs('company.inventory.*') || request()->routeIs('company.product-categories.*')) {
        $activeSection = 'finance';
    } elseif (request()->routeIs('company.reports.*') || request()->routeIs('company.activity-log.*')) {
        $activeSection = 'reports';
    } elseif (request()->routeIs('company.service-categories.*') || request()->routeIs('company.resources.*')
        || request()->routeIs('company.booking-policy.*') || request()->routeIs('company.profile.*')) {
        $activeSection = 'settings';
    } else {
        $activeSection = 'home';
    }

    // Plan feature shortcuts (owner-controlled subscriptions)
    $feat = fn (string $k) => $authCompany?->hasFeature($k) ?? false;

    $railSections = [
        'home'   => ['icon' => 'home',    'label' => __('Main')],
        'branch' => ['icon' => 'map-pin', 'label' => __('Branch')],
        'team'   => ['icon' => 'users',   'label' => __('Team')],
    ];
    if ($feat('finance') || $feat('inventory')) {
        $railSections['finance'] = ['icon' => 'credit-card', 'label' => __('Finance')];
    }
    if ($feat('reports')) {
        $railSections['reports'] = ['icon' => 'bar-chart-2', 'label' => __('Reports')];
    }
    $railSections['settings'] = ['icon' => 'settings', 'label' => __('Settings')];
@endphp

<nav class="sidebar bk-sidebar-v3">
    <div class="sidebar-header">
        <a href="{{ route('company.dashboard') }}" class="sidebar-brand">
            GlowRez<span>.</span>
        </a>
    </div>

    <div class="sidebar-body bk-sb">

        {{-- ══ Icon rail ══ --}}
        <div class="bk-rail" role="tablist" aria-label="{{ __('Main') }}">
            @foreach($railSections as $key => $sec)
            <button type="button"
                    class="bk-rail-item {{ $activeSection === $key ? 'active' : '' }}"
                    data-bk-section="{{ $key }}"
                    data-tour="rail-{{ $key }}"
                    role="tab" aria-selected="{{ $activeSection === $key ? 'true' : 'false' }}"
                    title="{{ $sec['label'] }}">
                <i data-feather="{{ $sec['icon'] }}"></i>
                <span class="bk-rail-label">{{ $sec['label'] }}</span>
                @if($key === 'home' && $pendingCount > 0)
                    <span class="bk-rail-dot" aria-hidden="true"></span>
                @endif
            </button>
            @endforeach

            <div class="bk-rail-spacer"></div>

            <a href="{{ route('company.profile.show') }}" class="bk-rail-me" title="{{ __('Profile') }}">
                @if($authCompany?->logo)
                    <img src="{{ asset('storage/'.$authCompany->logo) }}" alt="">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=34&background=4B5D34&color=FFFFFF&bold=true" alt="">
                @endif
            </a>
        </div>

        {{-- ══ Section panel ══ --}}
        <div class="bk-panel">

            {{-- Home --}}
            <div class="bk-panel-sec {{ $activeSection === 'home' ? 'active' : '' }}" data-bk-panel="home">
                <div class="bk-panel-title">{{ __('Daily Operations') }}</div>
                <a href="{{ route('company.dashboard') }}"
                   class="bk-pl {{ request()->routeIs('company.dashboard') ? 'active' : '' }}">
                    <i data-feather="home"></i><span>{{ __('Dashboard') }}</span>
                </a>
                @feature('leaves')
                <a href="{{ route('company.team-calendar') }}"
                   class="bk-pl {{ request()->routeIs('company.team-calendar') ? 'active' : '' }}">
                    <i data-feather="calendar"></i><span>{{ __('Calendar') }}</span>
                </a>
                @endfeature
                <a href="{{ route('company.appointments.index') }}"
                   class="bk-pl {{ request()->routeIs('company.appointments.*') ? 'active' : '' }}">
                    <i data-feather="check-square"></i><span>{{ __('Appointments') }}</span>
                    @if($pendingCount > 0)
                        <span class="bk-nav-badge">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('company.customers.index') }}"
                   class="bk-pl {{ request()->routeIs('company.customers.*') ? 'active' : '' }}">
                    <i data-feather="users"></i><span>{{ __('Customers') }}</span>
                </a>
            </div>

            {{-- Branch --}}
            <div class="bk-panel-sec {{ $activeSection === 'branch' ? 'active' : '' }}" data-bk-panel="branch">
                <div class="bk-panel-title">{{ __('Branch') }}</div>

                <button type="button" class="bk-branch-switcher" id="bkBranchSwitcher" aria-expanded="false"
                        aria-label="{{ __('Switch branch') }}">
                    <i data-feather="map-pin" class="bk-bs-pin"></i>
                    <span class="bk-bs-name" id="bkBranchName">{{ $selBranch?->localizedName() ?? __('All Branches') }}</span>
                    <i data-feather="chevron-down" class="caret"></i>
                </button>
                <ul class="bk-branch-list" id="bkBranchList" style="display:none;">
                    <li>
                        <a href="{{ route('company.branches.index') }}"
                           class="bk-branch-option bk-branch-option-all {{ request()->routeIs('company.branches.index') || request()->routeIs('company.branches.create') ? 'active' : '' }}">
                            <i data-feather="grid"></i>
                            <span>{{ __('All Branches') }}</span>
                        </a>
                    </li>
                    @foreach($sidebarBranches as $br)
                    <li>
                        <button type="button"
                                class="bk-branch-option {{ $selBranch && $selBranch->id === $br->id ? 'active' : '' }}"
                                data-branch-id="{{ $br->id }}">
                            <span class="bk-bo-dot"></span>
                            <span class="text-truncate">{{ $br->localizedName() }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>

                @if($selBranch)
                <a href="{{ route('company.branches.show', $selBranch) }}" data-bk-branch-link="overview"
                   class="bk-pl {{ $activeBranchPage === 'overview' ? 'active' : '' }}">
                    <i data-feather="layout"></i><span>{{ __('Overview') }}</span>
                </a>
                <a href="{{ route('company.branches.services.index', $selBranch) }}" data-bk-branch-link="services"
                   data-tour="branch-services"
                   class="bk-pl {{ $activeBranchPage === 'services' ? 'active' : '' }}">
                    <i data-feather="scissors"></i><span>{{ __('Services') }}</span>
                </a>
                <a href="{{ route('company.branches.employees.index', $selBranch) }}" data-bk-branch-link="employees"
                   data-tour="branch-employees"
                   class="bk-pl {{ $activeBranchPage === 'employees' ? 'active' : '' }}">
                    <i data-feather="users"></i><span>{{ __('Employees') }}</span>
                </a>
                <a href="{{ route('company.branches.working-hours.edit', $selBranch) }}" data-bk-branch-link="hours"
                   class="bk-pl {{ $activeBranchPage === 'hours' ? 'active' : '' }}">
                    <i data-feather="clock"></i><span>{{ __('Working Hours') }}</span>
                </a>
                <a href="{{ route('company.branches.gallery', $selBranch) }}" data-bk-branch-link="gallery"
                   class="bk-pl {{ $activeBranchPage === 'gallery' ? 'active' : '' }}">
                    <i data-feather="image"></i><span>{{ __('Gallery') }}</span>
                </a>
                @feature('finance')
                <a href="{{ route('company.branches.cash.index', $selBranch) }}" data-bk-branch-link="cash"
                   class="bk-pl {{ $activeBranchPage === 'cash' || $activeBranchPage === 'archive' ? 'active' : '' }}">
                    <i data-feather="credit-card"></i><span>{{ __('Cash Register') }}</span>
                </a>
                @endfeature
                @endif
            </div>

            {{-- Team --}}
            <div class="bk-panel-sec {{ $activeSection === 'team' ? 'active' : '' }}" data-bk-panel="team">
                <div class="bk-panel-title">{{ __('Team') }}</div>
                <a href="{{ route('company.staff.index') }}"
                   class="bk-pl {{ request()->routeIs('company.staff.*') ? 'active' : '' }}">
                    <i data-feather="users"></i><span>{{ __('Staff Overview') }}</span>
                </a>
                @feature('leaves')
                <a href="{{ route('company.employee-leaves.index') }}"
                   class="bk-pl {{ request()->routeIs('company.employee-leaves.*') ? 'active' : '' }}">
                    <i data-feather="user-x"></i><span>{{ __('Leaves') }}</span>
                </a>
                @endfeature
                @feature('attendance')
                <a href="{{ route('company.attendance.index') }}"
                   class="bk-pl {{ request()->routeIs('company.attendance.*') ? 'active' : '' }}">
                    <i data-feather="check-circle"></i><span>{{ __('Attendance') }}</span>
                </a>
                @endfeature
                @feature('payroll')
                <a href="{{ route('company.deductions.index') }}"
                   class="bk-pl {{ request()->routeIs('company.deductions.*') && !request()->routeIs('company.employees.deductions.*') ? 'active' : '' }}">
                    <i data-feather="minus-circle"></i><span>{{ __('Deductions') }}</span>
                </a>
                <a href="{{ route('company.payroll.index') }}"
                   class="bk-pl {{ request()->routeIs('company.payroll.*') || request()->routeIs('company.employees.payroll') ? 'active' : '' }}">
                    <i data-feather="dollar-sign"></i><span>{{ __('Payroll') }}</span>
                </a>
                @endfeature
            </div>

            {{-- Finance --}}
            <div class="bk-panel-sec {{ $activeSection === 'finance' ? 'active' : '' }}" data-bk-panel="finance">
                <div class="bk-panel-title">{{ __('Finance') }}</div>
                @feature('finance')
                <a href="{{ route('company.cash.global') }}"
                   class="bk-pl {{ request()->routeIs('company.cash.global') ? 'active' : '' }}">
                    <i data-feather="credit-card"></i><span>{{ __('Cash Registers') }}</span>
                </a>
                <a href="{{ route('company.invoices.index') }}"
                   class="bk-pl {{ request()->routeIs('company.invoices.*') ? 'active' : '' }}">
                    <i data-feather="file-text"></i><span>{{ __('Invoices') }}</span>
                </a>
                <a href="{{ route('company.recurring-expenses.index') }}"
                   class="bk-pl {{ request()->routeIs('company.recurring-expenses.*') ? 'active' : '' }}">
                    <i data-feather="repeat"></i><span>{{ __('Recurring Expenses') }}</span>
                </a>
                <a href="{{ route('company.debts.index') }}"
                   class="bk-pl {{ request()->routeIs('company.debts.*') ? 'active' : '' }}">
                    <i data-feather="alert-circle"></i><span>{{ __('Customer Debts') }}</span>
                </a>
                @endfeature
                @feature('inventory')
                <a href="{{ route('company.inventory.index') }}"
                   class="bk-pl {{ request()->routeIs('company.inventory.*') || request()->routeIs('company.product-categories.*') ? 'active' : '' }}">
                    <i data-feather="package"></i><span>{{ __('Inventory') }}</span>
                </a>
                @endfeature
            </div>

            {{-- Reports --}}
            <div class="bk-panel-sec {{ $activeSection === 'reports' ? 'active' : '' }}" data-bk-panel="reports">
                <div class="bk-panel-title">{{ __('Reports') }}</div>
                <a href="{{ route('company.reports.profit-loss') }}"
                   class="bk-pl {{ request()->routeIs('company.reports.*') ? 'active' : '' }}">
                    <i data-feather="bar-chart-2"></i><span>{{ __('Reports') }}</span>
                </a>
                <a href="{{ route('company.activity-log.index') }}"
                   class="bk-pl {{ request()->routeIs('company.activity-log.*') ? 'active' : '' }}">
                    <i data-feather="shield"></i><span>{{ __('Activity Log') }}</span>
                </a>
            </div>

            {{-- Settings --}}
            <div class="bk-panel-sec {{ $activeSection === 'settings' ? 'active' : '' }}" data-bk-panel="settings">
                <div class="bk-panel-title">{{ __('Settings') }}</div>
                <a href="{{ route('company.service-categories.index') }}" data-tour="svc-cats"
                   class="bk-pl {{ request()->routeIs('company.service-categories.*') ? 'active' : '' }}">
                    <i data-feather="tag"></i><span>{{ __('Service categories') }}</span>
                </a>
                <a href="{{ route('company.resources.index') }}"
                   class="bk-pl {{ request()->routeIs('company.resources.*') ? 'active' : '' }}">
                    <i data-feather="grid"></i><span>{{ __('Resources & rooms') }}</span>
                </a>
                <a href="{{ route('company.booking-policy.edit') }}"
                   class="bk-pl {{ request()->routeIs('company.booking-policy.*') ? 'active' : '' }}">
                    <i data-feather="shield"></i><span>{{ __('Booking policy') }}</span>
                </a>
                <a href="{{ route('company.profile.show') }}"
                   class="bk-pl {{ request()->routeIs('company.profile.*') ? 'active' : '' }}">
                    <i data-feather="settings"></i><span>{{ __('Profile') }}</span>
                </a>
                <div class="bk-panel-divider"></div>
                <a href="{{ $authCompany ? route('front.show', $authCompany) : '#' }}" target="_blank" class="bk-pl bk-pl-muted">
                    <i data-feather="external-link"></i><span>{{ __('View public page') }}</span>
                </a>
            </div>

        </div>
    </div>
</nav>

<script>
(function () {
    var BRANCHES    = @json($branchData);
    var ACTIVE_PAGE = @json($activeBranchPage);
    var ON_BRANCH_ROUTE = @json((bool) $routeBranch);
    var STORE_BRANCH = 'bk-current-branch';

    // ── Rail → panel switching ──
    function showSection(key) {
        document.querySelectorAll('.bk-rail-item').forEach(function (b) {
            var on = b.dataset.bkSection === key;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('.bk-panel-sec').forEach(function (p) {
            p.classList.toggle('active', p.dataset.bkPanel === key);
        });
    }
    window.bkShowSection = showSection;

    document.querySelectorAll('.bk-rail-item').forEach(function (btn) {
        btn.addEventListener('click', function () { showSection(this.dataset.bkSection); });
    });

    // ── Branch switcher ──
    function branchById(id) {
        for (var i = 0; i < BRANCHES.length; i++) {
            if (String(BRANCHES[i].id) === String(id)) return BRANCHES[i];
        }
        return null;
    }

    function applyBranch(br, clearActive) {
        var nameEl = document.getElementById('bkBranchName');
        if (nameEl) nameEl.textContent = br.name;
        document.querySelectorAll('[data-bk-branch-link]').forEach(function (a) {
            var key = a.getAttribute('data-bk-branch-link');
            if (br.urls[key]) a.href = br.urls[key];
            if (clearActive) a.classList.remove('active');
        });
        document.querySelectorAll('.bk-branch-option[data-branch-id]').forEach(function (b) {
            b.classList.toggle('active', String(b.dataset.branchId) === String(br.id));
        });
    }

    var switcher = document.getElementById('bkBranchSwitcher');
    var list     = document.getElementById('bkBranchList');
    if (switcher && list) {
        switcher.addEventListener('click', function () {
            var open = switcher.getAttribute('aria-expanded') === 'true';
            switcher.setAttribute('aria-expanded', open ? 'false' : 'true');
            list.style.display = open ? 'none' : '';
        });
        list.querySelectorAll('.bk-branch-option[data-branch-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var br = branchById(this.dataset.branchId);
                if (!br) return;
                try { localStorage.setItem(STORE_BRANCH, br.id); } catch (e) {}
                if (ACTIVE_PAGE && br.urls[ACTIVE_PAGE]) {
                    window.location.href = br.urls[ACTIVE_PAGE];
                    return;
                }
                applyBranch(br, true);
                switcher.setAttribute('aria-expanded', 'false');
                list.style.display = 'none';
            });
        });
        // Restore remembered branch (only when not already on a branch-scoped page)
        if (!ON_BRANCH_ROUTE) {
            try {
                var saved = localStorage.getItem(STORE_BRANCH);
                var br = saved && branchById(saved);
                if (br) applyBranch(br, false);
            } catch (e) {}
        }
    }
})();
</script>
