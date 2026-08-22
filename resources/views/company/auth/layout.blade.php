<!DOCTYPE html>
@php($theme = request()->cookie('company_theme', 'dark'))
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-bk-theme="{{ $theme }}" class="bk-theme-{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('Account') }} — GlowRez Business</title>
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/intl-tel-input/css/intlTelInput.min.css') }}">
    @if(app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset($theme === 'light' ? 'backend/assets/css/demo1/style-rtl.css' : 'backend/assets/css/demo2/style-rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset($theme === 'light' ? 'backend/assets/css/demo1/style.css' : 'backend/assets/css/demo2/style.css') }}">
    @endif
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}?v={{ @filemtime(public_path('backend/assets/images/favicon.png')) ?: '1' }}" />
    <link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-custom.css') }}?v={{ @filemtime(public_path('backend/assets/css/booksy-custom.css')) ?: '1' }}">
    @if(app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-arabic.css') }}">
    @endif
    <style>
        .noble-ui-logo, .noble-ui-logo:hover { color: {{ $theme === 'light' ? '#1a1a1a' : '#f5f5f5' }}; }
        .noble-ui-logo span { color: var(--bk-accent); font-weight: 700; }

        /* ===== Shared auth split layout ===== */
        .bk-auth .card { overflow:hidden; border-radius:18px; box-shadow:var(--bk-shadow-xl); }
        .bk-auth .card > .row { min-height:600px; }

        .bk-auth-hero{
            position:relative; height:100%;
            display:flex; flex-direction:column; justify-content:space-between;
            padding:2.5rem 2rem;
            background:
                radial-gradient(120% 90% at 100% 0%, rgba(215,184,115,.28) 0%, rgba(215,184,115,0) 55%),
                linear-gradient(165deg, #55693B 0%, #3C4B29 60%, #2E3A20 100%);
            color:#F3EFE4; overflow:hidden;
        }
        .bk-auth-hero::after{
            content:""; position:absolute; inset:auto -40px -60px auto;
            width:260px; height:260px; border-radius:50%;
            background:radial-gradient(circle, rgba(215,184,115,.22), transparent 70%);
            pointer-events:none;
        }
        .bk-auth-hero .bk-hero-logo{ font-size:1.5rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:.6rem; }
        .bk-auth-hero .bk-hero-logo small{ display:block; font-size:.72rem; font-weight:500; color:rgba(243,239,228,.7); letter-spacing:2px; text-transform:uppercase; margin-top:2px; }
        .bk-auth-hero h2{ color:#fff; font-weight:800; font-size:1.6rem; line-height:1.35; margin:1.75rem 0 .6rem; }
        .bk-auth-hero .bk-hero-sub{ color:rgba(243,239,228,.82); font-size:.95rem; line-height:1.65; }
        .bk-auth-hero .bk-hero-badge{
            width:64px; height:64px; border-radius:18px; display:grid; place-items:center;
            background:rgba(215,184,115,.16); border:1px solid rgba(215,184,115,.3); color:var(--bk-gold,#D8B873);
            margin-bottom:.5rem;
        }
        .bk-auth-hero .bk-hero-badge i{ width:30px; height:30px; }
        .bk-auth-hero .bk-hero-foot{ display:flex; align-items:center; gap:.55rem; font-size:.85rem; color:rgba(243,239,228,.88); }
        .bk-auth-hero .bk-hero-foot i{ width:18px; height:18px; color:var(--bk-gold,#D8B873); }

        .bk-auth .auth-form-wrapper{ display:flex; flex-direction:column; justify-content:center; }
        .bk-auth .input-group-text{ background:var(--bk-surface-2); border-color:var(--bk-border-strong); color:var(--bk-text-muted); padding-inline:.8rem; }
        .bk-auth .input-group-text i{ width:16px; height:16px; }
        .bk-auth .form-hint{ font-size:.76rem; color:var(--bk-text-muted); margin-top:.3rem; }
        .bk-auth .btn-primary{ background:var(--bk-accent-fill); border-color:var(--bk-accent-fill); color:var(--bk-accent-ink); font-weight:600; }
        .bk-auth .btn-primary:hover{ background:var(--bk-accent-hover); border-color:var(--bk-accent-hover); }
        .bk-auth-lang a.active{ color:var(--bk-accent); font-weight:700; }

        /* Channel segmented control */
        .bk-seg{ display:flex; gap:6px; padding:5px; border-radius:12px; background:var(--bk-surface-2); border:1px solid var(--bk-border); margin-bottom:1.25rem; }
        .bk-seg label{ flex:1; margin:0; }
        .bk-seg input{ position:absolute; opacity:0; pointer-events:none; }
        .bk-seg .bk-seg-btn{
            display:flex; align-items:center; justify-content:center; gap:.5rem;
            padding:.6rem .5rem; border-radius:9px; cursor:pointer; font-weight:600; font-size:.9rem;
            color:var(--bk-text-muted); transition:background .15s ease, color .15s ease;
        }
        .bk-seg .bk-seg-btn i{ width:16px; height:16px; }
        .bk-seg input:checked + .bk-seg-btn{ background:var(--bk-accent-fill); color:var(--bk-accent-ink); box-shadow:var(--bk-shadow-sm); }

        /* OTP boxes */
        .bk-otp-row{ display:flex; gap:10px; direction:ltr; margin-bottom:.5rem; }
        .bk-otp-box{
            width:100%; max-width:64px; height:60px; text-align:center; font-size:1.5rem; font-weight:700;
            border:1.5px solid var(--bk-border); border-radius:12px;
            background:var(--bk-surface-2); color:var(--bk-text);
            transition:border-color .15s ease, box-shadow .15s ease;
        }
        .bk-otp-box:focus{ outline:none; border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }

        /* Password strength meter */
        .bk-pw-meter{ margin-top:.5rem; }
        .bk-pw-bars{ display:flex; gap:5px; }
        .bk-pw-bars span{ flex:1; height:5px; border-radius:3px; background:var(--bk-border); transition:background .2s; }
        .bk-pw-label{ font-size:.74rem; margin-top:.3rem; color:var(--bk-text-muted); }

        /* intl-tel-input theming */
        .bk-auth .iti{ width:100%; }
        .bk-auth .iti input.form-control{ width:100%; }
        .bk-auth .iti__country-list{ background:var(--bk-surface); color:var(--bk-text); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg); }
        .bk-auth .iti__country.iti__highlight{ background:var(--bk-accent-wash); }
        .bk-auth .iti__dial-code{ color:var(--bk-text-muted); }
        .bk-auth .iti__search-input{ background:var(--bk-surface); color:var(--bk-text); border-color:var(--bk-border); }

        @media (max-width: 767.98px){
            .bk-auth .card > .row{ min-height:0; }
            .bk-auth .auth-form-wrapper{ padding-top:2rem !important; }
        }
    </style>
    @stack('head')
</head>
<body>
@include('company.auth.partials.theme-toggle')
<div class="main-wrapper">
    <div class="page-wrapper full-page">
        <div class="page-content d-flex align-items-center justify-content-center py-4 py-md-5">
            <div class="row w-100 mx-0 auth-page bk-auth">
                <div class="col-md-9 col-xl-8 col-xxl-7 mx-auto">
                    <div class="card">
                        <div class="row g-0">
                            {{-- Brand / value panel --}}
                            <div class="col-md-5 d-none d-md-block">
                                <div class="bk-auth-hero">
                                    <div>
                                        <div class="bk-hero-logo">
                                            <img src="{{ asset('images/logo-dark.png') }}" alt="GlowRez" style="height:46px;width:auto">
                                            <small>Business</small>
                                        </div>
                                        <div style="margin-top:2rem">
                                            <div class="bk-hero-badge">@yield('hero-icon')</div>
                                            <h2>@yield('hero-title')</h2>
                                            <p class="bk-hero-sub">@yield('hero-sub')</p>
                                        </div>
                                    </div>
                                    <div class="bk-hero-foot">
                                        <i data-feather="shield"></i>
                                        <span>{{ __('Your account is protected') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="col-md-7">
                                <div class="auth-form-wrapper px-4 px-lg-5 py-5">
                                    @yield('content')

                                    <div class="mt-4 pt-3 border-top d-flex gap-3 bk-auth-lang">
                                        @php($currentLocale = app()->getLocale())
                                        <a href="{{ route('locale.switch', ['locale' => 'en']) }}" class="text-muted small {{ $currentLocale === 'en' ? 'active' : '' }}">
                                            <i class="flag-icon flag-icon-us me-1"></i>English
                                        </a>
                                        <span class="text-muted small">|</span>
                                        <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" class="text-muted small {{ $currentLocale === 'ar' ? 'active' : '' }}">
                                            <i class="flag-icon flag-icon-sa me-1"></i>العربية
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('backend/assets/vendors/core/core.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/template.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/intl-tel-input/js/intlTelInput.min.js') }}"></script>
@stack('scripts')
<script>feather.replace();</script>
</body>
</html>
