@php
    $authCompany = Auth::guard('company')->user();
@endphp

<nav class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('company.dashboard') }}" class="sidebar-brand">
            Booksy<span>.</span>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span><span></span><span></span>
        </div>
    </div>

    <div class="sidebar-body">
        <ul class="nav">

            {{-- ── Profile card (inside nav) ── --}}
            <li class="nav-item" style="padding:0 8px 8px;">
                <div style="
                    display:flex;align-items:center;gap:10px;
                    padding:12px 14px;border-radius:12px;
                    background:rgba(201,162,39,.08);
                    border:1px solid rgba(201,162,39,.15);
                    margin-bottom:4px;">
                    <div style="position:relative;flex-shrink:0;">
                        @if($authCompany?->logo)
                            <img src="{{ asset('storage/'.$authCompany->logo) }}"
                                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(201,162,39,.4);" alt="">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=36&background=C9A227&color=000&bold=true"
                                 style="width:36px;height:36px;border-radius:50%;" alt="">
                        @endif
                        <span style="position:absolute;bottom:1px;right:1px;width:8px;height:8px;background:#2bcf7e;border-radius:50%;border:2px solid var(--sidebar-bg,#0c1427);"></span>
                    </div>
                    <div style="overflow:hidden;min-width:0;">
                        <div style="font-size:.8rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $authCompany?->localizedName() }}</div>
                        <div style="font-size:.65rem;color:#C9A227;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Business</div>
                    </div>
                </div>
            </li>

            {{-- ── Main ── --}}
            <li class="nav-item nav-category">{{ __('Main') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.dashboard') }}"
                   class="nav-link {{ request()->routeIs('company.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="home"></i>
                    <span class="link-title">{{ __('Dashboard') }}</span>
                </a>
            </li>

            {{-- ── Management ── --}}
            <li class="nav-item nav-category">{{ __('Management') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.appointments.index') }}"
                   class="nav-link {{ request()->routeIs('company.appointments.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="calendar"></i>
                    <span class="link-title">{{ __('Appointments') }}</span>
                    @php
                        $pendingCount = 0;
                        if ($authCompany) {
                            try {
                                $pendingCount = (int) $authCompany->branches()
                                    ->withCount(['appointments as pc' => fn($q) => $q->where('status','pending')])
                                    ->get()->sum('pc');
                            } catch(\Throwable $e) {}
                        }
                    @endphp
                    @if($pendingCount > 0)
                        <span class="bk-nav-badge">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>
                    @endif
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.branches.index') }}"
                   class="nav-link {{ request()->routeIs('company.branches.*') || request()->routeIs('company.services.*') || request()->routeIs('company.employees.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="map-pin"></i>
                    <span class="link-title">{{ __('Branches') }}</span>
                </a>
            </li>

            {{-- ── Settings ── --}}
            <li class="nav-item nav-category">{{ __('Settings') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.service-categories.index') }}"
                   class="nav-link {{ request()->routeIs('company.service-categories.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="tag"></i>
                    <span class="link-title">{{ __('Service categories') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ $authCompany ? route('front.show', $authCompany) : '#' }}" target="_blank" class="nav-link" style="opacity:.55;">
                    <i class="link-icon" data-feather="external-link"></i>
                    <span class="link-title">{{ __('View public page') }}</span>
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
        @php($companyTheme = $companyTheme ?? request()->cookie('company_theme', 'dark'))
        <div class="theme-wrapper">
            <h6 class="text-muted mb-2">{{ __('Light') }}:</h6>
            <a class="theme-item{{ $companyTheme === 'light' ? ' active' : '' }}"
               href="{{ route('company.theme', ['mode' => 'light']) }}" role="button">
                <img src="{{ asset('backend/assets/images/screenshots/light.jpg') }}" alt="">
            </a>
            <h6 class="text-muted mb-2 mt-2">{{ __('Dark') }}:</h6>
            <a class="theme-item{{ $companyTheme === 'dark' ? ' active' : '' }}"
               href="{{ route('company.theme', ['mode' => 'dark']) }}" role="button">
                <img src="{{ asset('backend/assets/images/screenshots/dark.jpg') }}" alt="">
            </a>
        </div>
    </div>
</nav>
