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
    <a href="{{ route('company.appointments.index') }}"
       class="bk-bn-item {{ request()->routeIs('company.appointments.*') ? 'active' : '' }}">
        <span class="bk-bn-ic">
            <i data-feather="check-square"></i>
            @if($bnPending > 0)<span class="bk-bn-badge">{{ $bnPending > 9 ? '9+' : $bnPending }}</span>@endif
        </span>
        <span>{{ __('Appointments') }}</span>
    </a>
    {{-- Centre action. On the appointments page this opens that page's add sheet
         instead of reloading it (data-bk-add-trigger is read by appointments/panels.js) --}}
    <a href="{{ route('company.appointments.index', request()->routeIs('company.appointments.index') ? [] : ['add' => 1]) }}" class="bk-bn-fab" aria-label="{{ __('New booking') }}"
       @if(request()->routeIs('company.appointments.index')) data-bk-add-trigger aria-haspopup="menu" @endif>
        <i data-feather="plus"></i>
    </a>
    <a href="{{ route('company.customers.index') }}"
       class="bk-bn-item {{ request()->routeIs('company.customers.*') ? 'active' : '' }}">
        <i data-feather="users"></i>
        <span>{{ __('Customers') }}</span>
    </a>
    {{-- .sidebar-toggler: reuses the template's own handler to open the drawer --}}
    <a href="#" class="bk-bn-item sidebar-toggler">
        <i data-feather="menu"></i>
        <span>{{ __('More') }}</span>
    </a>
</nav>
