<!DOCTYPE html>
@php
    $companyTheme = request()->cookie('company_theme', 'dark');
    // Pending-appointments badge — computed once, shared by sidebar + bottom nav
    $bkPendingCount = 0;
    $bkOnboarding = null;
    if ($bkAuthCompany = Auth::guard('company')->user()) {
        try {
            $bkPendingCount = (int) $bkAuthCompany->branches()
                ->withCount(['appointments as pc' => fn($q) => $q->where('status', 'pending')])
                ->get()->sum('pc');
        } catch (\Throwable $e) {}
        try {
            $bkOnboarding = \App\Services\OnboardingService::summary($bkAuthCompany);
        } catch (\Throwable $e) {}
    }
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-bk-theme="{{ $companyTheme }}"
      class="bk-theme-{{ $companyTheme }}">
<head>
    @include('partials.clarity')
    @include('company.partials.css')
    @stack('company-styles')
</head>
{{-- data-clarity-mask: dashboard shows customer names, phones, emails, salaries &
     financial figures — mask all rendered text in Clarity recordings. Heatmaps &
     click analytics still work; UI is unaffected. --}}
<body data-clarity-mask="true">
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
    @include('company.partials.filter-sheet')
    @include('company.partials.onboarding-tour')
    @include('company.partials.help-center')
    @include('company.partials.js')
    @stack('scripts')
    @stack('company-after-template')
    @include('company.partials.reverb-notifications')
    @include('company.partials.crud-toasts')
    @include('partials.keepalive', ['logoutUrl' => route('company.logout')])
    {{-- RTL fix: Firefox renders native <input type="time/date"> using the BROWSER'S
         locale (e.g. Arabic), which mirrors the fields to "00:09" instead of "09:00" —
         and it ignores CSS direction for these controls. Forcing the element's own
         lang to an LTR locale (en-GB → 24h time / dd-mm-yyyy) plus dir="ltr" makes the
         fields render left-to-right in every browser. Covers dynamically-added inputs. --}}
    <script>
    (function () {
        var SEL = 'input[type="time"],input[type="date"],input[type="datetime-local"],input[type="month"],input[type="week"]';
        function stamp(el) {
            if (el.getAttribute('dir')  !== 'ltr')   el.setAttribute('dir', 'ltr');
            if (el.getAttribute('lang') !== 'en-GB') el.setAttribute('lang', 'en-GB');
        }
        function fix(root) {
            if (root.nodeType !== 1) return;
            if (root.matches && root.matches(SEL)) stamp(root);
            if (root.querySelectorAll) root.querySelectorAll(SEL).forEach(stamp);
        }
        function run() { fix(document.body); }
        if (document.readyState !== 'loading') run(); else document.addEventListener('DOMContentLoaded', run);
        new MutationObserver(function (muts) {
            for (var i = 0; i < muts.length; i++) {
                for (var j = 0; j < muts[i].addedNodes.length; j++) fix(muts[i].addedNodes[j]);
            }
        }).observe(document.documentElement, { childList: true, subtree: true });
    })();
    </script>
</body>
</html>
