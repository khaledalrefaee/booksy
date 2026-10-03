@php($title = __('Reset password'))
@extends('company.auth.layout')

@section('hero-title'){{ __('Forgot your password?') }}@endsection
@section('hero-sub'){{ __('No worries — we\'ll send you a verification code to get you back in, by phone or email.') }}@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/intl-tel-input/css/intlTelInput.min.css') }}">
    <style>
        .iti__flag.iti__sy {
            --iti-flag-offset: 0;
            background-image: url("{{ asset('backend/assets/vendors/intl-tel-input/img/flag-sy-new.svg') }}") !important;
            background-position: center !important;
            background-size: 16px 12px !important;
        }
        .ga-pane[hidden] { display: none; }
    </style>
@endpush

@section('content')
    <a href="{{ route('company.login') }}" class="ga-back">
        <x-auth.icon name="back" />
        {{ __('Back to sign in') }}
    </a>

    <h1>{{ __('Reset your password') }}</h1>
    <p class="ga-sub">{{ __('Choose how you\'d like to receive your verification code.') }}</p>

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

    <form method="POST" action="{{ route('company.password.send') }}" id="forgotForm" data-busy="{{ __('Sending…') }}" novalidate>
        @csrf

        <div class="ga-seg" role="radiogroup" aria-label="{{ __('Choose how you\'d like to receive your verification code.') }}">
            <label>
                <input type="radio" name="channel" value="whatsapp" @checked(old('channel', 'whatsapp') === 'whatsapp')>
                <span><x-auth.icon name="phone" />{{ __('Phone number') }}</span>
            </label>
            <label>
                <input type="radio" name="channel" value="email" @checked(old('channel') === 'email')>
                <span><x-auth.icon name="mail" />{{ __('Email') }}</span>
            </label>
        </div>

        {{-- Phone --}}
        <div class="ga-field ga-pane" id="field-phone">
            <label for="phone">{{ __('Phone') }}</label>
            <div class="ga-phone" id="phoneWrap">
                <input type="tel" id="phone" dir="ltr" autocomplete="tel">
                <span class="ga-ok" aria-hidden="true"><x-auth.icon name="check" stroke-width="3" /></span>
            </div>
            <input type="hidden" id="phone_full" name="phone" value="{{ old('phone') }}">
            <p class="ga-field-err" id="phoneError">{{ __('Please enter a valid phone number.') }}</p>
            <p class="ga-hint">{{ __('Enter the phone number linked to your business account.') }}</p>
        </div>

        {{-- Email --}}
        <div class="ga-field ga-pane" id="field-email" hidden>
            <label for="email">{{ __('Email') }}</label>
            <div class="ga-input">
                <span class="ga-ico"><x-auth.icon name="mail" /></span>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="business@example.com" autocomplete="email" inputmode="email">
            </div>
            <p class="ga-hint">{{ __('Enter the email you use to sign in.') }}</p>
        </div>

        <button type="submit" class="ga-submit" style="margin-top:1.6rem">
            <span class="ga-submit-label">{{ __('Send code') }}</span>
            <x-auth.icon name="arrow" class="i-arrow" stroke-width="2" />
            <x-auth.icon name="spin" class="i-spin" stroke-width="2.4" />
        </button>
    </form>
@endsection

@push('scripts')
<script src="{{ asset('backend/assets/vendors/intl-tel-input/js/intlTelInput.min.js') }}"></script>
<script>
    (function () {
        const radios  = document.querySelectorAll('input[name="channel"]');
        const fPhone  = document.getElementById('field-phone');
        const fEmail  = document.getElementById('field-email');
        const phoneIn = document.getElementById('phone');
        const emailIn = document.getElementById('email');
        const hidden  = document.getElementById('phone_full');
        const wrap    = document.getElementById('phoneWrap');
        const errorEl = document.getElementById('phoneError');
        const form    = document.getElementById('forgotForm');

        let iti = null;
        if (window.intlTelInput && phoneIn) {
            iti = window.intlTelInput(phoneIn, {
                initialCountry: 'sy',
                countryOrder: ['sy', 'lb', 'jo', 'iq', 'tr', 'sa', 'ae', 'eg'],
                separateDialCode: true,
                strictMode: true,
                autoPlaceholder: 'aggressive',
                placeholderNumberType: 'MOBILE',
                utilsScript: '{{ asset('backend/assets/vendors/intl-tel-input/js/utils.js') }}',
            });
            iti.promise.then(function () { if (hidden.value) iti.setNumber(hidden.value); refresh(); });
        }

        function refresh() {
            if (!iti) return false;
            const has = phoneIn.value.trim() !== '';
            const ok  = has && iti.isValidNumber();
            wrap.classList.toggle('is-valid', ok);
            return ok;
        }
        function showError(show) {
            errorEl.classList.toggle('show', show);
            wrap.classList.toggle('is-invalid', show);
        }
        phoneIn.addEventListener('input', function () { showError(false); refresh(); });
        phoneIn.addEventListener('countrychange', function () { showError(false); refresh(); });
        phoneIn.addEventListener('blur', function () {
            if (phoneIn.value.trim() === '') { showError(false); return; }
            showError(!(iti && iti.isValidNumber()));
        });

        function channel() { return document.querySelector('input[name="channel"]:checked').value; }
        function sync(focus) {
            const isPhone = channel() === 'whatsapp';
            fPhone.hidden = !isPhone;
            fEmail.hidden = isPhone;
            if (focus) (isPhone ? phoneIn : emailIn).focus({ preventScroll: true });
        }
        radios.forEach(r => r.addEventListener('change', () => sync(true)));
        sync(false);

        form.addEventListener('submit', function (e) {
            if (channel() === 'whatsapp') {
                if (iti && !iti.isValidNumber()) { e.preventDefault(); showError(true); phoneIn.focus(); return; }
                hidden.value = iti ? iti.getNumber() : phoneIn.value;
            } else if (!emailIn.value.trim()) {
                e.preventDefault(); emailIn.focus();
            }
        });
    })();
</script>
@endpush
