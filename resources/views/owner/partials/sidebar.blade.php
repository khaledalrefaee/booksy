@php($authOwner = Auth::guard('owner')->user())
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('owner.dashboard') }}" class="sidebar-brand">
            Booksy<span>.</span>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span><span></span><span></span>
        </div>
    </div>

    <div class="sidebar-body">
        <ul class="nav">

            {{-- ── Profile card ── --}}
            <li class="nav-item" style="padding:0 8px 8px;">
                <div style="
                    display:flex;align-items:center;gap:10px;
                    padding:12px 14px;border-radius:12px;
                    background:rgba(201,162,39,.08);
                    border:1px solid rgba(201,162,39,.15);">
                    <div style="position:relative;flex-shrink:0;">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($authOwner?->name ?? 'Owner') }}&size=36&background=C9A227&color=000&bold=true"
                             style="width:36px;height:36px;border-radius:50%;" alt="">
                        <span style="position:absolute;bottom:1px;right:1px;width:8px;height:8px;background:#2bcf7e;border-radius:50%;border:2px solid var(--sidebar-bg,#0c1427);"></span>
                    </div>
                    <div style="overflow:hidden;min-width:0;">
                        <div style="font-size:.8rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $authOwner?->name ?? 'Admin' }}</div>
                        <div style="font-size:.65rem;color:#C9A227;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Platform Owner</div>
                    </div>
                </div>
            </li>

            {{-- ── Overview ── --}}
            <li class="nav-item nav-category">{{ __('Overview') }}</li>

            <li class="nav-item">
                <a href="{{ route('owner.dashboard') }}"
                   class="nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="home"></i>
                    <span class="link-title">{{ __('Dashboard') }}</span>
                </a>
            </li>

            {{-- ── Management ── --}}
            <li class="nav-item nav-category">{{ __('Management') }}</li>

            <li class="nav-item">
                <a href="{{ route('owner.companies.index') }}"
                   class="nav-link {{ request()->routeIs('owner.companies.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="briefcase"></i>
                    <span class="link-title">{{ __('Companies') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('owner.branches.index') }}"
                   class="nav-link {{ request()->routeIs('owner.branches.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="map-pin"></i>
                    <span class="link-title">{{ __('Branches') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('owner.appointments.index') }}"
                   class="nav-link {{ request()->routeIs('owner.appointments.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="calendar"></i>
                    <span class="link-title">{{ __('Appointments') }}</span>
                </a>
            </li>

            {{-- ── Configuration ── --}}
            <li class="nav-item nav-category">{{ __('Configuration') }}</li>

            <li class="nav-item">
                <a href="{{ route('owner.categories.index') }}"
                   class="nav-link {{ request()->routeIs('owner.categories.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="layers"></i>
                    <span class="link-title">{{ __('Company categories') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('owner.service-categories.index') }}"
                   class="nav-link {{ request()->routeIs('owner.service-categories.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="tag"></i>
                    <span class="link-title">{{ __('Service categories') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('owner.locations.index') }}"
                   class="nav-link {{ request()->routeIs('owner.locations.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="map"></i>
                    <span class="link-title">{{ __('Locations') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('front.index') }}" target="_blank" class="nav-link" style="opacity:.55;">
                    <i class="link-icon" data-feather="globe"></i>
                    <span class="link-title">{{ __('View website') }}</span>
                </a>
            </li>

        </ul>
    </div>
</nav>

<nav class="settings-sidebar">
    <div class="sidebar-body">
        <a href="#" class="settings-sidebar-toggler">
            <i data-feather="settings"></i>
        </a>
        @php($ownerTheme = $ownerTheme ?? request()->cookie('owner_theme', 'dark'))
        <div class="theme-wrapper">
            <h6 class="text-muted mb-2">{{ __('Light') }}:</h6>
            <a class="theme-item{{ $ownerTheme === 'light' ? ' active' : '' }}"
               href="{{ route('owner.theme', ['mode' => 'light']) }}" role="button">
                <img src="{{ asset('backend/assets/images/screenshots/light.jpg') }}" alt="">
            </a>
            <h6 class="text-muted mb-2 mt-2">{{ __('Dark') }}:</h6>
            <a class="theme-item{{ $ownerTheme === 'dark' ? ' active' : '' }}"
               href="{{ route('owner.theme', ['mode' => 'dark']) }}" role="button">
                <img src="{{ asset('backend/assets/images/screenshots/dark.jpg') }}" alt="">
            </a>
        </div>
    </div>
</nav>
