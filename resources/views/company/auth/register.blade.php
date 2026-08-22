<!DOCTYPE html>
@php($theme = request()->cookie('company_theme', 'dark'))
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-bk-theme="{{ $theme }}" class="bk-theme-{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Register') }} — GlowRez Business</title>
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/intl-tel-input/css/intlTelInput.min.css') }}">
    @php($theme = request()->cookie('company_theme', 'dark'))
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

        /* ===== Registration – branded split layout ===== */
        .bk-reg .card { overflow:hidden; border-radius:18px; box-shadow:var(--bk-shadow-xl); }
        .bk-reg .card > .row { min-height:660px; }

        /* Hero / value panel — brand olive in both themes */
        .bk-reg-hero{
            position:relative; height:100%;
            display:flex; flex-direction:column; justify-content:space-between;
            padding:2.5rem 2rem;
            background:
                radial-gradient(120% 90% at 100% 0%, rgba(215,184,115,.28) 0%, rgba(215,184,115,0) 55%),
                linear-gradient(165deg, #55693B 0%, #3C4B29 60%, #2E3A20 100%);
            color:#F3EFE4; overflow:hidden;
        }
        .bk-reg-hero::after{
            content:""; position:absolute; inset:auto -40px -60px auto;
            width:260px; height:260px; border-radius:50%;
            background:radial-gradient(circle, rgba(215,184,115,.22), transparent 70%);
            pointer-events:none;
        }
        .bk-reg-hero .bk-hero-logo{ font-size:1.5rem; font-weight:800; color:#fff; letter-spacing:.2px; }
        .bk-reg-hero .bk-hero-logo span{ color:var(--bk-gold, #D8B873); }
        .bk-reg-hero .bk-hero-logo small{ display:block; font-size:.72rem; font-weight:500; color:rgba(243,239,228,.7); letter-spacing:2px; text-transform:uppercase; margin-top:2px; }
        .bk-reg-hero h2{ color:#fff; font-weight:800; font-size:1.65rem; line-height:1.3; margin:1.75rem 0 .5rem; }
        .bk-reg-hero .bk-hero-sub{ color:rgba(243,239,228,.82); font-size:.95rem; line-height:1.6; margin-bottom:1.75rem; }
        .bk-hero-list{ list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:1rem; }
        .bk-hero-list li{ display:flex; align-items:flex-start; gap:.85rem; }
        .bk-hero-ic{
            flex:0 0 auto; width:38px; height:38px; border-radius:11px;
            display:flex; align-items:center; justify-content:center;
            background:rgba(215,184,115,.16); color:var(--bk-gold, #D8B873);
            border:1px solid rgba(215,184,115,.3);
        }
        .bk-hero-ic i{ width:18px; height:18px; }
        .bk-hero-list b{ display:block; color:#fff; font-size:.92rem; font-weight:600; }
        .bk-hero-list span{ color:rgba(243,239,228,.72); font-size:.8rem; }
        .bk-hero-foot{ margin-top:2rem; display:flex; align-items:center; gap:.55rem; font-size:.85rem; color:rgba(243,239,228,.88); }
        .bk-hero-foot i{ width:18px; height:18px; color:var(--bk-gold, #D8B873); }

        /* Form side */
        .bk-reg .auth-form-wrapper{ display:flex; flex-direction:column; justify-content:center; }
        .bk-reg .input-group-text{
            background:var(--bk-surface-2); border-color:var(--bk-border-strong);
            color:var(--bk-text-muted); padding-inline:.8rem;
        }
        .bk-reg .input-group-text i{ width:16px; height:16px; }
        .bk-reg .form-hint{ font-size:.76rem; color:var(--bk-text-muted); margin-top:.3rem; }
        .bk-reg .btn-primary{ background:var(--bk-accent-fill); border-color:var(--bk-accent-fill); color:var(--bk-accent-ink); font-weight:600; }
        .bk-reg .btn-primary:hover{ background:var(--bk-accent-hover); border-color:var(--bk-accent-hover); }

        /* Password strength meter */
        .bk-pw-meter{ margin-top:.5rem; }
        .bk-pw-bars{ display:flex; gap:5px; }
        .bk-pw-bars span{ flex:1; height:5px; border-radius:3px; background:var(--bk-border); transition:background .2s; }
        .bk-pw-label{ font-size:.74rem; margin-top:.3rem; color:var(--bk-text-muted); }

        .bk-reg .form-check-label a{ color:var(--bk-accent); font-weight:600; text-decoration:none; }
        .bk-reg .form-check-label a:hover{ text-decoration:underline; }
        .bk-reg-lang a.active{ color:var(--bk-accent); font-weight:700; }

        /* intl-tel-input theming to match bk tokens */
        .bk-reg .iti{ width:100%; }
        .bk-reg .iti input.form-control{ width:100%; }
        .bk-reg .iti__country-list, .bk-reg .iti__dropdown-content{
            background:var(--bk-surface); color:var(--bk-text);
            border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg);
            border-radius:.6rem; overflow:hidden;
        }
        .bk-reg .iti__country.iti__highlight, .bk-reg .iti__country:hover{ background:var(--bk-accent-wash); }
        .bk-reg .iti__dial-code{ color:var(--bk-text-muted); }
        .bk-reg .iti__search-input{ background:var(--bk-surface); color:var(--bk-text); border-color:var(--bk-border); }

        /* New Syrian flag (green–white–black, 3 red stars) — override sprite */
        .bk-reg .iti__flag.iti__sy{
            --iti-flag-offset:0;
            background-image:url("{{ asset('backend/assets/vendors/intl-tel-input/img/flag-sy-new.svg') }}") !important;
            background-position:center !important;
            background-size:16px 12px !important;
        }

        /* Friendlier, larger phone control */
        .bk-reg .iti input.form-control{ height:calc(1.5em + 1.1rem + 2px); }
        .bk-reg .iti--separate-dial-code .iti__selected-country{ background:var(--bk-surface-2); }
        .bk-reg .iti__selected-dial-code{ color:var(--bk-text); font-weight:600; }

        /* Live valid state — green check + success border */
        .bk-reg .bk-phone-wrap{ position:relative; }
        .bk-reg .bk-phone-ok{
            position:absolute; inset-inline-end:.7rem; top:calc((1.5em + 1.1rem + 2px)/2);
            transform:translateY(-50%); width:20px; height:20px; border-radius:50%;
            display:none; align-items:center; justify-content:center;
            background:var(--bk-success); color:#fff; pointer-events:none; z-index:2;
        }
        .bk-reg .bk-phone-ok i{ width:13px; height:13px; }
        .bk-reg .bk-phone-wrap.is-valid .bk-phone-ok{ display:flex; }
        .bk-reg .bk-phone-wrap.is-valid #phone{ border-color:var(--bk-success); padding-inline-end:2.4rem; }
        .bk-reg #phone.is-invalid{ border-color:var(--bk-danger); }
        .bk-reg .bk-phone-error{ display:none; font-size:.76rem; color:var(--bk-danger); margin-top:.3rem; }
        .bk-reg .bk-phone-error.show{ display:block; }
        .bk-reg .bk-phone-hint{ font-size:.76rem; color:var(--bk-text-muted); margin-top:.3rem; display:flex; align-items:center; gap:.35rem; }
        .bk-reg .bk-phone-hint i{ width:13px; height:13px; flex:0 0 auto; color:var(--bk-accent); }
        .bk-reg .bk-phone-wrap.is-valid ~ .bk-phone-hint{ color:var(--bk-success); }
        .bk-reg .bk-phone-wrap.is-valid ~ .bk-phone-hint i{ color:var(--bk-success); }
        /* RTL (Arabic): keep the .iti CONTAINER rtl (flag + dial code on the
           right), but force the number text and the dial code themselves to LTR
           so "944 567 890" / "+963" don't get bidi-reversed by the RTL stylesheet.
           text-align:right anchors the number to the dial-code side. */
        html[dir="rtl"] .bk-reg #phone{ direction:ltr; text-align:right; }
        html[dir="rtl"] .bk-reg .iti__selected-dial-code{ direction:ltr; }
        html[dir="rtl"] .bk-reg .iti__dial-code{ direction:ltr; unicode-bidi:embed; }

        @media (max-width: 767.98px){
            .bk-reg .card > .row{ min-height:0; }
            .bk-reg .auth-form-wrapper{ padding-top:2rem !important; }
        }
    </style>
</head>
<body>
@include('company.auth.partials.theme-toggle')
<div class="main-wrapper">
    <div class="page-wrapper full-page">
        <div class="page-content d-flex align-items-center justify-content-center py-4 py-md-5">
            <div class="row w-100 mx-0 auth-page bk-reg">
                <div class="col-md-10 col-xl-9 col-xxl-8 mx-auto">
                    <div class="card">
                        <div class="row g-0">
                            {{-- ===== Value / brand panel ===== --}}
                            <div class="col-md-5 d-none d-md-block">
                                <div class="bk-reg-hero">
                                    <div>
                                        <div class="bk-hero-logo" style="display:flex;align-items:center;gap:.6rem">
                                            <img src="{{ asset('images/logo-dark.png') }}" alt="GlowRez غلوريز" style="height:50px;width:auto">
                                            <small>Business</small>
                                        </div>
                                        <h2>{{ __('Grow your business with GlowRez') }}</h2>
                                        <p class="bk-hero-sub">{{ __('The all-in-one platform to manage bookings, staff and payments — built for salons and spas.') }}</p>
                                        <ul class="bk-hero-list">
                                            <li>
                                                <span class="bk-hero-ic"><i data-feather="calendar"></i></span>
                                                <div><b>{{ __('Online bookings 24/7') }}</b><span>{{ __('Let clients book anytime, from anywhere') }}</span></div>
                                            </li>
                                            <li>
                                                <span class="bk-hero-ic"><i data-feather="users"></i></span>
                                                <div><b>{{ __('Manage staff & calendar') }}</b><span>{{ __('Schedules, shifts and services in one place') }}</span></div>
                                            </li>
                                            <li>
                                                <span class="bk-hero-ic"><i data-feather="bell"></i></span>
                                                <div><b>{{ __('Automated reminders') }}</b><span>{{ __('Cut no-shows with smart notifications') }}</span></div>
                                            </li>
                                            <li>
                                                <span class="bk-hero-ic"><i data-feather="bar-chart-2"></i></span>
                                                <div><b>{{ __('Reports & insights') }}</b><span>{{ __('Track revenue and grow with data') }}</span></div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="bk-hero-foot">
                                        <i data-feather="gift"></i>
                                        <span>{{ __('Free to start — no credit card required') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- ===== Form ===== --}}
                            <div class="col-md-7">
                                <div class="auth-form-wrapper px-4 px-lg-5 py-5">
                                    <a href="{{ route('company.login') }}" class="noble-ui-logo d-md-none d-inline-flex align-items-center mb-2" style="gap:.5rem;text-decoration:none">
                                        <x-front.logo variant="full" style="height:42px;width:auto"/>
                                        <small class="text-muted fw-normal fs-6">Business</small>
                                    </a>
                                    <h4 class="fw-bold mb-1">{{ __('Create your business account') }}</h4>
                                    <p class="text-muted mb-4">{{ __('It only takes a minute to get started.') }}</p>

                                    @if ($errors->any())
                                        <div class="alert alert-danger py-2 px-3 mb-3" role="alert" aria-live="assertive">
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('company.register.attempt') }}" class="forms-sample" id="registerForm" novalidate>
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name_en" class="form-label fw-semibold">{{ __('Business name (EN)') }} <span class="text-danger">*</span></label>
                                                <input type="text" id="name_en" name="name_en"
                                                    class="form-control @error('name_en') is-invalid @enderror"
                                                    value="{{ old('name_en') }}" placeholder="My Salon" autocomplete="organization" autofocus required>
                                                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="name_ar" class="form-label fw-semibold">{{ __('Business name (AR)') }}</label>
                                                <input type="text" id="name_ar" name="name_ar" dir="rtl"
                                                    class="form-control @error('name_ar') is-invalid @enderror"
                                                    value="{{ old('name_ar') }}" placeholder="صالوني">
                                                @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="owner_name" class="form-label fw-semibold">{{ __('Owner / Manager name') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i data-feather="user"></i></span>
                                                <input type="text" id="owner_name" name="owner_name"
                                                    class="form-control @error('owner_name') is-invalid @enderror"
                                                    value="{{ old('owner_name') }}" placeholder="{{ __('Full name') }}" autocomplete="name" required>
                                                @error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label fw-semibold">{{ __('Email') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i data-feather="mail"></i></span>
                                                <input type="email" id="email" name="email" dir="ltr"
                                                    class="form-control @error('email') is-invalid @enderror"
                                                    value="{{ old('email') }}" placeholder="business@example.com" autocomplete="email" required>
                                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="form-hint">{{ __('Used to sign in and receive booking notifications.') }}</div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label fw-semibold">{{ __('Phone') }} <span class="text-danger">*</span></label>
                                                {{-- Visible input has NO name; the E.164 value is written to the hidden field live + on submit. --}}
                                                <div class="bk-phone-wrap">
                                                    <input type="tel" id="phone" dir="ltr"
                                                        class="form-control @error('phone') is-invalid @enderror"
                                                        autocomplete="tel">
                                                    <span class="bk-phone-ok" aria-hidden="true"><i data-feather="check"></i></span>
                                                </div>
                                                <input type="hidden" id="phone_full" name="phone" value="{{ old('phone') }}">
                                                <div class="bk-phone-error" id="phoneError">{{ __('Please enter a valid phone number.') }}</div>
                                                <div class="bk-phone-hint"><i data-feather="shield"></i><span>{{ __("We'll send a verification code to this number.") }}</span></div>
                                                @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="category_id" class="form-label fw-semibold">{{ __('Business type') }} <span class="text-danger">*</span></label>
                                                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                                    <option value="">{{ __('Select category') }}</option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                            {{ $cat->localizedName() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label fw-semibold">{{ __('Password') }} <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" id="password" name="password"
                                                    class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                                                    placeholder="••••••••" autocomplete="new-password" minlength="8" required>
                                                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1" aria-label="{{ __('Show password') }}">
                                                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                                                </button>
                                            </div>
                                            <div class="bk-pw-meter" id="pwMeter" hidden aria-hidden="true">
                                                <div class="bk-pw-bars"><span></span><span></span><span></span><span></span></div>
                                                <div class="bk-pw-label" id="pwLabel"></div>
                                            </div>
                                            <div class="form-hint">{{ __('At least 8 characters.') }}</div>
                                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-check mb-4">
                                            <input type="checkbox" class="form-check-input @error('terms') is-invalid @enderror" id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                                            <label class="form-check-label text-muted small" for="terms">
                                                {!! __('I agree to the :terms and :privacy.', [
                                                    'terms'   => '<a href="'.route('front.business.terms').'" target="_blank" rel="noopener">'.__('Business Terms of Service').'</a>',
                                                    'privacy' => '<a href="'.route('front.business.privacy').'" target="_blank" rel="noopener">'.__('Business Privacy Policy').'</a>',
                                                ]) !!}
                                            </label>
                                            @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Create account') }}</button>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <span class="text-muted small">{{ __('Already have an account?') }}</span>
                                            <a href="{{ route('company.login') }}" class="small ms-1 fw-semibold">{{ __('Sign in') }}</a>
                                        </div>
                                    </form>

                                    <div class="mt-4 pt-3 border-top d-flex gap-3 bk-reg-lang">
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
<script>
    // Password visibility toggle
    document.querySelectorAll('.js-toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.querySelector(this.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = this.querySelector('[data-feather]');
            icon.setAttribute('data-feather', input.type === 'password' ? 'eye' : 'eye-off');
            feather.replace();
        });
    });

    // Password strength meter
    (function () {
        const pw = document.getElementById('password');
        const meter = document.getElementById('pwMeter');
        const bars = meter ? meter.querySelectorAll('.bk-pw-bars span') : [];
        const label = document.getElementById('pwLabel');
        if (!pw || !meter) return;

        @php($pwLabels = [__('Weak'), __('Fair'), __('Good'), __('Strong')])
        const labels = @json($pwLabels);
        const colors = ['var(--bk-danger)', 'var(--bk-warning)', 'var(--bk-info)', 'var(--bk-success)'];

        function score(v) {
            let s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            return Math.min(s, 4);
        }

        pw.addEventListener('input', function () {
            const v = pw.value;
            if (!v) { meter.hidden = true; return; }
            meter.hidden = false;
            const s = score(v);
            bars.forEach((bar, i) => {
                bar.style.background = i < s ? colors[Math.max(0, s - 1)] : 'var(--bk-border)';
            });
            label.textContent = s > 0 ? labels[Math.max(0, s - 1)] : '';
            label.style.color = s > 0 ? colors[Math.max(0, s - 1)] : 'var(--bk-text-muted)';
        });
    })();

    // Phone: intl-tel-input with E.164 storage + validate on blur/submit
    (function () {
        const input = document.getElementById('phone');
        const hidden = document.getElementById('phone_full');
        const errorEl = document.getElementById('phoneError');
        const form = document.getElementById('registerForm');
        if (!input || !window.intlTelInput) return;

        const iti = window.intlTelInput(input, {
            initialCountry: 'sy',
            countryOrder: ['sy', 'lb', 'jo', 'iq', 'tr', 'sa', 'ae', 'eg'],
            separateDialCode: true,
            strictMode: true,               // block non-digits + cap length as they type
            autoPlaceholder: 'aggressive',  // show a real example number to guide the user
            placeholderNumberType: 'MOBILE',
            formatOnDisplay: true,          // pretty-format the number as it's entered
            utilsScript: '{{ asset('backend/assets/vendors/intl-tel-input/js/utils.js') }}',
        });

        const wrap = input.closest('.bk-phone-wrap');

        // Keep the hidden E.164 field + green-check state in sync with the input.
        function refresh() {
            const has   = input.value.trim() !== '';
            const valid = has && iti.isValidNumber();
            wrap.classList.toggle('is-valid', valid);
            hidden.value = has ? iti.getNumber() : '';
            return valid;
        }
        function showError(show) {
            errorEl.classList.toggle('show', show);
            input.classList.toggle('is-invalid', show);
        }

        // Once utils has loaded: repopulate a prior value (after a server error) and format it.
        iti.promise.then(function () {
            if (hidden.value) { iti.setNumber(hidden.value); }
            refresh();
        });

        input.addEventListener('input', function () { showError(false); refresh(); });
        input.addEventListener('countrychange', function () { showError(false); refresh(); });

        // Nag with the error only on blur (never per keystroke).
        input.addEventListener('blur', function () {
            if (input.value.trim() === '') { showError(false); return; }
            showError(!iti.isValidNumber());
        });

        form.addEventListener('submit', function (e) {
            if (input.value.trim() !== '' && !iti.isValidNumber()) {
                e.preventDefault();
                showError(true);
                input.focus();
                return;
            }
            // Write the full E.164 number to the submitted field.
            hidden.value = iti.getNumber();
        });
    })();

    feather.replace();
</script>
</body>
</html>
