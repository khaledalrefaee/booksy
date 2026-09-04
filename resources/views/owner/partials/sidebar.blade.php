@php
    use Illuminate\Support\Facades\Gate;

    $authOwner = Auth::guard('owner')->user();

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

    $can = fn (string $k) => Gate::allows('owner-can', $k);

    // Which rail section owns the current route
    if (request()->routeIs('owner.plans.*') || request()->routeIs('owner.subscriptions.*')
        || request()->routeIs('owner.subscription-payments.*') || request()->routeIs('owner.coupons.*')) {
        $activeSection = 'billing';
    } elseif (request()->routeIs('owner.sms.*')) {
        $activeSection = 'sms';
    } elseif (request()->routeIs('owner.reports.*') || request()->routeIs('owner.audit-log.*')) {
        $activeSection = 'insights';
    } elseif (request()->routeIs('owner.companies.*') || request()->routeIs('owner.branches.*')
        || request()->routeIs('owner.services.*') || request()->routeIs('owner.employees.*')
        || request()->routeIs('owner.employee-leaves.*') || request()->routeIs('owner.customers.*')
        || request()->routeIs('owner.invoices.*')) {
        $activeSection = 'tenants';
    } elseif (request()->routeIs('owner.categories.*') || request()->routeIs('owner.service-categories.*')
        || request()->routeIs('owner.locations.*') || request()->routeIs('owner.profile')) {
        $activeSection = 'settings';
    } else {
        $activeSection = 'home';
    }

    $railSections = [
        'home'    => ['icon' => 'home',      'label' => __('Main')],
        'tenants' => ['icon' => 'briefcase', 'label' => __('Companies')],
        'billing' => ['icon' => 'credit-card', 'label' => __('Billing')],
        'sms'     => ['icon' => 'message-square', 'label' => __('SMS')],
    ];
    if ($can('reports.view') || $can('audit-log.view')) {
        $railSections['insights'] = ['icon' => 'bar-chart-2', 'label' => __('Insights')];
    }
    $railSections['settings'] = ['icon' => 'settings', 'label' => __('Settings')];
@endphp

<nav class="sidebar bk-sidebar-v3">
    <div class="sidebar-header">
        <a href="{{ route('owner.dashboard') }}" class="sidebar-brand">
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
                    role="tab" aria-selected="{{ $activeSection === $key ? 'true' : 'false' }}"
                    title="{{ $sec['label'] }}">
                <i data-feather="{{ $sec['icon'] }}"></i>
                <span class="bk-rail-label">{{ $sec['label'] }}</span>
                @if($key === 'home' && $pendingAppts > 0)
                    <span class="bk-rail-dot" aria-hidden="true"></span>
                @endif
                @if($key === 'tenants' && $pendingCompanies > 0)
                    <span class="bk-rail-dot" aria-hidden="true"></span>
                @endif
            </button>
            @endforeach

            <div class="bk-rail-spacer"></div>

            <a href="{{ route('owner.profile') }}" class="bk-rail-me" title="{{ __('Profile') }}">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($authOwner?->name ?? 'Owner') }}&size=34&background=4B5D34&color=FFFFFF&bold=true" alt="{{ $authOwner?->name ?? __('Profile') }}">
            </a>
        </div>

        {{-- ══ Section panel ══ --}}
        <div class="bk-panel">

            {{-- Main --}}
            <div class="bk-panel-sec {{ $activeSection === 'home' ? 'active' : '' }}" data-bk-panel="home">
                <div class="bk-panel-title">{{ __('Platform monitoring') }}</div>
                <a href="{{ route('owner.dashboard') }}"
                   class="bk-pl {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                    <i data-feather="home"></i><span>{{ __('Dashboard') }}</span>
                </a>
                <a href="{{ route('owner.appointments.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.appointments.*') ? 'active' : '' }}">
                    <i data-feather="calendar"></i><span>{{ __('Appointments') }}</span>
                    @if($pendingAppts > 0)
                        <span class="bk-nav-badge">{{ $pendingAppts > 99 ? '99+' : $pendingAppts }}</span>
                    @endif
                </a>
                @can('owner-can', 'reviews.moderate')
                <a href="{{ route('owner.reviews.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.reviews.*') ? 'active' : '' }}">
                    <i data-feather="star"></i><span>{{ __('Reviews') }}</span>
                </a>
                @endcan
                @can('owner-can', 'notifications.send')
                <a href="{{ route('owner.announcements.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.announcements.*') ? 'active' : '' }}">
                    <i data-feather="bell"></i><span>{{ __('Announcements') }}</span>
                </a>
                @endcan
            </div>

            {{-- Companies & operations --}}
            <div class="bk-panel-sec {{ $activeSection === 'tenants' ? 'active' : '' }}" data-bk-panel="tenants">
                <div class="bk-panel-title">{{ __('Companies') }}</div>
                <a href="{{ route('owner.companies.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.companies.*') ? 'active' : '' }}">
                    <i data-feather="briefcase"></i><span>{{ __('Companies') }}</span>
                    @if($pendingCompanies > 0)
                        <span class="bk-nav-badge">{{ $pendingCompanies > 99 ? '99+' : $pendingCompanies }}</span>
                    @endif
                </a>
                <a href="{{ route('owner.branches.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.branches.*') || request()->routeIs('owner.services.*') || request()->routeIs('owner.employees.*') ? 'active' : '' }}">
                    <i data-feather="map-pin"></i><span>{{ __('Branches') }}</span>
                </a>
                <a href="{{ route('owner.employee-leaves.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.employee-leaves.*') ? 'active' : '' }}">
                    <i data-feather="user-x"></i><span>{{ __('Employee leaves') }}</span>
                </a>
                @can('owner-can', 'operations.view')
                <a href="{{ route('owner.customers.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.customers.*') ? 'active' : '' }}">
                    <i data-feather="users"></i><span>{{ __('Customers') }}</span>
                </a>
                <a href="{{ route('owner.invoices.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.invoices.*') ? 'active' : '' }}">
                    <i data-feather="file-text"></i><span>{{ __('Invoices') }}</span>
                </a>
                @endcan
            </div>

            {{-- Billing --}}
            <div class="bk-panel-sec {{ $activeSection === 'billing' ? 'active' : '' }}" data-bk-panel="billing">
                <div class="bk-panel-title">{{ __('Billing') }}</div>
                <a href="{{ route('owner.plans.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.plans.*') ? 'active' : '' }}">
                    <i data-feather="package"></i><span>{{ __('Plans') }}</span>
                </a>
                @can('owner-can', 'billing.view')
                <a href="{{ route('owner.subscriptions.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.subscriptions.*') ? 'active' : '' }}">
                    <i data-feather="refresh-cw"></i><span>{{ __('Subscriptions') }}</span>
                </a>
                <a href="{{ route('owner.subscription-payments.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.subscription-payments.*') ? 'active' : '' }}">
                    <i data-feather="dollar-sign"></i><span>{{ __('Payments') }}</span>
                </a>
                @endcan
                @can('owner-can', 'coupons.manage')
                <a href="{{ route('owner.coupons.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.coupons.*') ? 'active' : '' }}">
                    <i data-feather="tag"></i><span>{{ __('Coupons') }}</span>
                </a>
                @endcan
            </div>

            {{-- SMS credit system --}}
            <div class="bk-panel-sec {{ $activeSection === 'sms' ? 'active' : '' }}" data-bk-panel="sms">
                <div class="bk-panel-title">{{ __('SMS credits') }}</div>
                <a href="{{ route('owner.sms.overview') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.overview') ? 'active' : '' }}">
                    <i data-feather="layout"></i><span>{{ __('Overview') }}</span>
                </a>
                <a href="{{ route('owner.sms.analytics') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.analytics') ? 'active' : '' }}">
                    <i data-feather="trending-up"></i><span>{{ __('Analytics') }}</span>
                </a>
                <a href="{{ route('owner.sms.companies') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.companies') ? 'active' : '' }}">
                    <i data-feather="briefcase"></i><span>{{ __('Companies usage') }}</span>
                </a>
                <a href="{{ route('owner.sms.branches') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.branches') ? 'active' : '' }}">
                    <i data-feather="map-pin"></i><span>{{ __('Branches usage') }}</span>
                </a>
                <a href="{{ route('owner.sms.packages') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.packages') ? 'active' : '' }}">
                    <i data-feather="box"></i><span>{{ __('Packages') }}</span>
                </a>
                <a href="{{ route('owner.sms.pricing') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.pricing') ? 'active' : '' }}">
                    <i data-feather="tag"></i><span>{{ __('Pricing') }}</span>
                </a>
                <a href="{{ route('owner.sms.transactions') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.transactions') ? 'active' : '' }}">
                    <i data-feather="repeat"></i><span>{{ __('Transactions') }}</span>
                </a>
                <a href="{{ route('owner.sms.logs') }}"
                   class="bk-pl {{ request()->routeIs('owner.sms.logs') ? 'active' : '' }}">
                    <i data-feather="list"></i><span>{{ __('Message logs') }}</span>
                </a>
            </div>

            {{-- Insights --}}
            <div class="bk-panel-sec {{ $activeSection === 'insights' ? 'active' : '' }}" data-bk-panel="insights">
                <div class="bk-panel-title">{{ __('Insights') }}</div>
                @can('owner-can', 'reports.view')
                <a href="{{ route('owner.reports.growth') }}"
                   class="bk-pl {{ request()->routeIs('owner.reports.growth') ? 'active' : '' }}">
                    <i data-feather="trending-up"></i><span>{{ __('Growth report') }}</span>
                </a>
                <a href="{{ route('owner.reports.revenue') }}"
                   class="bk-pl {{ request()->routeIs('owner.reports.revenue') ? 'active' : '' }}">
                    <i data-feather="bar-chart-2"></i><span>{{ __('Revenue report') }}</span>
                </a>
                @endcan
                @can('owner-can', 'audit-log.view')
                <a href="{{ route('owner.audit-log.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.audit-log.*') ? 'active' : '' }}">
                    <i data-feather="shield"></i><span>{{ __('Audit log') }}</span>
                </a>
                @endcan
            </div>

            {{-- Settings --}}
            <div class="bk-panel-sec {{ $activeSection === 'settings' ? 'active' : '' }}" data-bk-panel="settings">
                <div class="bk-panel-title">{{ __('Settings') }}</div>
                <a href="{{ route('owner.categories.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.categories.*') ? 'active' : '' }}">
                    <i data-feather="layers"></i><span>{{ __('Company categories') }}</span>
                </a>
                <a href="{{ route('owner.service-categories.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.service-categories.*') ? 'active' : '' }}">
                    <i data-feather="tag"></i><span>{{ __('Service categories') }}</span>
                </a>
                <a href="{{ route('owner.locations.index') }}"
                   class="bk-pl {{ request()->routeIs('owner.locations.*') ? 'active' : '' }}">
                    <i data-feather="map"></i><span>{{ __('Locations') }}</span>
                </a>
                <a href="{{ route('owner.profile') }}"
                   class="bk-pl {{ request()->routeIs('owner.profile') ? 'active' : '' }}">
                    <i data-feather="user"></i><span>{{ __('Profile') }}</span>
                </a>
                <div class="bk-panel-divider"></div>
                <a href="{{ route('front.index') }}" target="_blank" class="bk-pl bk-pl-muted">
                    <i data-feather="external-link"></i><span>{{ __('View website') }}</span>
                </a>
            </div>

        </div>
    </div>
</nav>

<script>
(function () {
    // Rail → panel switching
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
    document.querySelectorAll('.bk-rail-item').forEach(function (btn) {
        btn.addEventListener('click', function () { showSection(this.dataset.bkSection); });
    });
})();
</script>
