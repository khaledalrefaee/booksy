@php($pwLabels = [__('Weak'), __('Fair'), __('Good'), __('Strong')])
@php($title = __('Register'))
@extends('company.auth.layout')

@section('hero-title'){{ __('Grow your business with GlowRez') }}@endsection
@section('hero-sub'){{ __('The all-in-one platform to manage bookings, staff and payments — built for salons and spas.') }}@endsection

@section('hero-extra')
    <ul class="ga-points">
        <li><b>{{ __('Online bookings 24/7') }}</b><span>{{ __('Let clients book anytime, from anywhere') }}</span></li>
        <li><b>{{ __('Manage staff & calendar') }}</b><span>{{ __('Schedules, shifts and services in one place') }}</span></li>
        <li><b>{{ __('Automated reminders') }}</b><span>{{ __('Cut no-shows with smart notifications') }}</span></li>
        <li><b>{{ __('Reports & insights') }}</b><span>{{ __('Track revenue and grow with data') }}</span></li>
    </ul>
@endsection

@section('hero-foot-class', 'ga-legal--note')
@section('hero-foot'){{ __('Free to start — no credit card required') }}@endsection

@section('form-class', 'ga-form--wide')

@push('head')
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/intl-tel-input/css/intlTelInput.min.css') }}">
    <style>
        /* New Syrian flag (green–white–black, 3 red stars) — override sprite */
        .iti__flag.iti__sy {
            --iti-flag-offset: 0;
            background-image: url("{{ asset('backend/assets/vendors/intl-tel-input/img/flag-sy-new.svg') }}") !important;
            background-position: center !important;
            background-size: 16px 12px !important;
        }
    </style>
@endpush

@section('content')
    <h1>{{ __('Create your business account') }}</h1>
    <p class="ga-sub">{{ __('It only takes a minute to get started.') }}</p>

    @if ($errors->any())
        <div class="ga-alert" role="alert">
            <x-auth.icon name="alert" />
            <div>
                @if ($errors->count() > 1)
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @else
                    {{ $errors->first() }}
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('company.register.attempt') }}" id="registerForm" data-busy="{{ __('Creating your account…') }}" novalidate>
        @csrf

        <h2 class="ga-group ga-group--first">{{ __('Your business') }}</h2>

        <div class="ga-grid">
            <div class="ga-field @error('name_en') has-error @enderror">
                <label for="name_en">{{ __('Business name (EN)') }}<span class="ga-req" aria-hidden="true">*</span></label>
                <div class="ga-input ga-input--plain">
                    <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" placeholder="My Salon" autocomplete="organization" required autofocus @error('name_en') aria-invalid="true" @enderror>
                </div>
            </div>
            <div class="ga-field @error('name_ar') has-error @enderror">
                <label for="name_ar">{{ __('Business name (AR)') }}</label>
                <div class="ga-input ga-input--plain">
                    <input type="text" id="name_ar" name="name_ar" dir="rtl" value="{{ old('name_ar') }}" placeholder="صالوني" @error('name_ar') aria-invalid="true" @enderror>
                </div>
            </div>
        </div>

        <div class="ga-field @error('category_id') has-error @enderror">
            <label for="category_id">{{ __('Business type') }}<span class="ga-req" aria-hidden="true">*</span></label>
            <div class="ga-input">
                <select id="category_id" name="category_id" required @error('category_id') aria-invalid="true" @enderror>
                    <option value="" disabled @selected(! old('category_id'))>{{ __('Select category') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->localizedName() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <h2 class="ga-group">{{ __('About you') }}</h2>

        <div class="ga-field @error('owner_name') has-error @enderror">
            <label for="owner_name">{{ __('Owner / Manager name') }}<span class="ga-req" aria-hidden="true">*</span></label>
            <div class="ga-input">
                <span class="ga-ico"><x-auth.icon name="user" /></span>
                <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" placeholder="{{ __('Full name') }}" autocomplete="name" required @error('owner_name') aria-invalid="true" @enderror>
            </div>
        </div>

        <div class="ga-grid">
            <div class="ga-field @error('email') has-error @enderror">
                <label for="email">{{ __('Email') }}<span class="ga-req" aria-hidden="true">*</span></label>
                <div class="ga-input">
                    <span class="ga-ico"><x-auth.icon name="mail" /></span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="business@example.com" autocomplete="email" inputmode="email" required @error('email') aria-invalid="true" @enderror>
                </div>
            </div>

            <div class="ga-field @error('phone') has-error @enderror">
                <label for="phone">{{ __('Phone') }}<span class="ga-req" aria-hidden="true">*</span></label>
                {{-- Visible input has NO name; the E.164 value is written to the hidden field live + on submit. --}}
                <div class="ga-phone" id="phoneWrap">
                    <input type="tel" id="phone" dir="ltr" autocomplete="tel" required>
                    <span class="ga-ok" aria-hidden="true"><x-auth.icon name="check" stroke-width="3" /></span>
                </div>
                <input type="hidden" id="phone_full" name="phone" value="{{ old('phone') }}">
                <p class="ga-field-err" id="phoneError">{{ __('Please enter a valid phone number.') }}</p>
            </div>
        </div>
        <p class="ga-hint" style="margin:-.55rem 0 1.15rem">{{ __('Used to sign in and receive booking notifications.') }} {{ __("We'll send a verification code to this number.") }}</p>

        <h2 class="ga-group">{{ __('Secure your account') }}</h2>

        <div class="ga-field @error('password') has-error @enderror">
            <label for="password">{{ __('Password') }}<span class="ga-req" aria-hidden="true">*</span></label>
            <div class="ga-input ga-input--pw">
                <span class="ga-ico"><x-auth.icon name="lock" /></span>
                <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="new-password" minlength="8" required @error('password') aria-invalid="true" @enderror>
                <button type="button" class="ga-eye" aria-pressed="false" aria-controls="password"
                        aria-label="{{ __('Show password') }}" data-show="{{ __('Show password') }}" data-hide="{{ __('Hide password') }}">
                    <x-auth.icon name="eye" class="i-on" />
                    <x-auth.icon name="eye-off" class="i-off" />
                </button>
            </div>
            <div class="ga-meter" data-meter data-for="#password" data-labels='@json($pwLabels)' hidden aria-hidden="true">
                <div class="ga-meter-bars"><span></span><span></span><span></span><span></span></div>
                <div class="ga-meter-label"></div>
            </div>
            <p class="ga-hint">{{ __('At least 8 characters.') }}</p>
        </div>

        <div class="ga-field @error('password') has-error @enderror" id="confirmField">
            <label for="password_confirmation">{{ __('Confirm password') }}<span class="ga-req" aria-hidden="true">*</span></label>
            <div class="ga-input ga-input--pw">
                <span class="ga-ico"><x-auth.icon name="lock" /></span>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" autocomplete="new-password" minlength="8" required
                       data-match="#password" aria-describedby="confirmErr">
                <button type="button" class="ga-eye" aria-pressed="false" aria-controls="password_confirmation"
                        aria-label="{{ __('Show password') }}" data-show="{{ __('Show password') }}" data-hide="{{ __('Hide password') }}">
                    <x-auth.icon name="eye" class="i-on" />
                    <x-auth.icon name="eye-off" class="i-off" />
                </button>
            </div>
            <p class="ga-field-err" id="confirmErr" data-mismatch role="alert">{{ __('The passwords do not match.') }}</p>
        </div>

        <label class="ga-check @error('terms') has-error @enderror" style="margin-top:1.4rem">
            <input type="checkbox" id="terms" name="terms" value="1" @checked(old('terms')) required @error('terms') aria-invalid="true" @enderror>
            <span>
                {!! __('I agree to the :terms and :privacy.', [
                    'terms'   => '<a href="'.route('front.business.terms').'" target="_blank" rel="noopener">'.__('Business Terms of Service').'</a>',
                    'privacy' => '<a href="'.route('front.business.privacy').'" target="_blank" rel="noopener">'.__('Business Privacy Policy').'</a>',
                ]) !!}
            </span>
        </label>

        <button type="submit" class="ga-submit" id="registerSubmit">
            <span class="ga-submit-label">{{ __('Create account') }}</span>
            <x-auth.icon name="arrow" class="i-arrow" stroke-width="2" />
            <x-auth.icon name="spin" class="i-spin" stroke-width="2.4" />
        </button>

        <p class="ga-switch">
            {{ __('Already have an account?') }}
            <a href="{{ route('company.login') }}" class="ga-link">{{ __('Sign in') }}</a>
        </p>
    </form>
@endsection

@push('scripts')
<script src="{{ asset('backend/assets/vendors/intl-tel-input/js/intlTelInput.min.js') }}"></script>
<script>
    // Phone: intl-tel-input with E.164 storage + validate on blur/submit
    (function () {
        const input   = document.getElementById('phone');
        const hidden  = document.getElementById('phone_full');
        const errorEl = document.getElementById('phoneError');
        const wrap    = document.getElementById('phoneWrap');
        const form    = document.getElementById('registerForm');
        if (!input || !window.intlTelInput) return;

        const iti = window.intlTelInput(input, {
            initialCountry: 'sy',
            countryOrder: ['sy', 'lb', 'jo', 'iq', 'tr', 'sa', 'ae', 'eg'],
            separateDialCode: true,
            strictMode: true,               // block non-digits + cap length as they type
            autoPlaceholder: 'aggressive',  // show a real example number to guide the user
            placeholderNumberType: 'MOBILE',
            formatOnDisplay: true,
            utilsScript: '{{ asset('backend/assets/vendors/intl-tel-input/js/utils.js') }}',
        });

        // Keep the hidden E.164 field + valid state in sync with the input.
        function refresh() {
            const has   = input.value.trim() !== '';
            const valid = has && iti.isValidNumber();
            wrap.classList.toggle('is-valid', valid);
            hidden.value = has ? iti.getNumber() : '';
            return valid;
        }
        function showError(show) {
            errorEl.classList.toggle('show', show);
            wrap.classList.toggle('is-invalid', show);
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
            if (e.defaultPrevented) return;
            if (!form.checkValidity()) { e.preventDefault(); form.reportValidity(); return; }
            if (!iti.isValidNumber()) { e.preventDefault(); showError(true); input.focus(); return; }
            hidden.value = iti.getNumber();
        });
        // An empty phone is "required": show our message and move focus there (the native bubble is suppressed).
        input.addEventListener('invalid', function (e) { e.preventDefault(); showError(true); input.focus(); });
    })();
</script>
@endpush
