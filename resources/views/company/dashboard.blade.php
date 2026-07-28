<!DOCTYPE html>
@php
    $companyTheme = request()->cookie('company_theme', 'dark');
    // Pending-appointments badge — computed once, shared by sidebar + bottom nav
    $bkPendingCount = 0;
    if ($bkAuthCompany = Auth::guard('company')->user()) {
        try {
            $bkPendingCount = (int) $bkAuthCompany->branches()
                ->withCount(['appointments as pc' => fn($q) => $q->where('status', 'pending')])
                ->get()->sum('pc');
        } catch (\Throwable $e) {}
    }
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-bk-theme="{{ $companyTheme }}"
      class="bk-theme-{{ $companyTheme }}">
<head>
    @include('company.partials.css')
    @stack('company-styles')
</head>
<body>
    @include('company.partials.loading-skeleton')
    @include('company.partials.impersonation-banner')
    <div class="main-wrapper">
        @include('company.partials.sidebar')
        <div class="page-wrapper">
            @include('company.partials.navbar')
            @yield('content')
            @include('company.partials.footer')
        </div>
    </div>
    @include('company.partials.bottom-nav')
    @include('company.partials.onboarding-tour')
    @include('company.partials.js')
    @stack('scripts')
    @stack('company-after-template')
    @include('company.partials.reverb-notifications')
    @include('company.partials.crud-toasts')
</body>
</html>
