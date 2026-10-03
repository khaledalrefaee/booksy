<!DOCTYPE html>
@php
    $theme         = request()->cookie('company_theme', 'dark') === 'light' ? 'light' : 'dark';
    $isAr          = app()->getLocale() === 'ar';
    $currentLocale = app()->getLocale();
    $asset         = fn ($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: '1');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}" data-theme="{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? __('Account') }} — GlowRez Business</title>

    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('fonts/glowrez-type.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ $asset('css/glowrez-auth.css') }}">
    <link rel="shortcut icon" href="{{ $asset('icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ $asset('icons/apple-touch-icon.png') }}">
    @stack('head')
</head>
<body>
<div class="ga-shell">

    {{-- ═════════ Brand stage ═════════ --}}
    <aside class="ga-stage" id="gaStage">
        <div class="ga-glow" aria-hidden="true"></div>
        <img class="ga-mark ga-mark--ghost" src="{{ $asset('images/logo-mark-dark.webp') }}" width="1400" height="1076" alt="" aria-hidden="true">
        <div class="ga-lit" aria-hidden="true">
            <img class="ga-mark" src="{{ $asset('images/logo-mark-dark.webp') }}" width="1400" height="1076" alt="">
        </div>

        <a class="ga-brand" href="{{ route('company.login') }}" aria-label="GlowRez Business">
            <img src="{{ $asset('images/glowrez-logo-dark_1.webp') }}" width="1500" height="424" alt="GlowRez غلو ريز">
        </a>

        <div class="ga-pitch">
            <h2>@yield('hero-title')</h2>
            <p>@yield('hero-sub')</p>
            @yield('hero-extra')
        </div>

        <div class="ga-legal @yield('hero-foot-class')">
            @hasSection('hero-foot') @yield('hero-foot') @else <span dir="ltr">© {{ date('Y') }} GlowRez</span> @endif
        </div>
    </aside>

    {{-- ═════════ Panel ═════════ --}}
    <main class="ga-panel">
        <div class="ga-bar">
            <nav class="ga-lang" aria-label="{{ __('Language') }}">
                <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" @if($currentLocale === 'en') aria-current="true" @endif>English</a>
                <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" lang="ar" @if($currentLocale === 'ar') aria-current="true" @endif>العربية</a>
            </nav>

            <a href="{{ route('company.theme', $theme === 'light' ? 'dark' : 'light') }}" class="ga-theme" id="gaTheme"
               data-theme-url="{{ url('company/theme/__MODE__') }}"
               aria-label="{{ __('Toggle theme') }}" title="{{ __('Toggle theme') }}">
                <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </a>
        </div>

        <div class="ga-main">
            <div class="ga-form @yield('form-class')">
                @yield('content')
            </div>
        </div>
    </main>
</div>

<script src="{{ $asset('js/glowrez-auth.js') }}"></script>
@stack('scripts')
</body>
</html>
