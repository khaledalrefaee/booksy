@php
    // Shared count from the layout when available (avoids a duplicate query)
    $bnPending = $bkPendingCount ?? 0;
    if (!isset($bkPendingCount) && ($bnCompany = Auth::guard('company')->user())) {
        try {
            $bnPending = (int) $bnCompany->branches()
                ->withCount(['appointments as pc' => fn($q) => $q->where('status', 'pending')])
                ->get()->sum('pc');
        } catch (\Throwable $e) {}
    }
@endphp

{{-- Mobile bottom tab bar (hidden ≥992px where the sidebar is visible) --}}
<nav class="bk-bottom-nav d-lg-none" aria-label="{{ __('Main') }}">
    <a href="{{ route('company.dashboard') }}"
       class="bk-bn-item {{ request()->routeIs('company.dashboard') ? 'active' : '' }}">
        <i data-feather="home"></i>
        <span>{{ __('Main') }}</span>
    </a>
    @feature('leaves')
    <a href="{{ route('company.team-calendar') }}"
       class="bk-bn-item {{ request()->routeIs('company.team-calendar') ? 'active' : '' }}">
        <i data-feather="calendar"></i>
        <span>{{ __('Calendar') }}</span>
    </a>
    @endfeature
    <a href="{{ route('company.appointments.create') }}" class="bk-bn-fab" aria-label="{{ __('New booking') }}">
        <i data-feather="plus"></i>
    </a>
    <a href="{{ route('company.appointments.index') }}"
       class="bk-bn-item {{ request()->routeIs('company.appointments.*') && !request()->routeIs('company.appointments.create') ? 'active' : '' }}">
        <span class="bk-bn-ic">
            <i data-feather="check-square"></i>
            @if($bnPending > 0)<span class="bk-bn-badge">{{ $bnPending > 9 ? '9+' : $bnPending }}</span>@endif
        </span>
        <span>{{ __('Appointments') }}</span>
    </a>
    {{-- .sidebar-toggler: reuses the template's own handler to open the drawer --}}
    <a href="#" class="bk-bn-item sidebar-toggler">
        <i data-feather="menu"></i>
        <span>{{ __('More') }}</span>
    </a>
</nav>
