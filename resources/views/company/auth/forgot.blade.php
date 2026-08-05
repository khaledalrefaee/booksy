@php($title = __('Reset password'))
@extends('company.auth.layout')

@section('hero-icon')<i data-feather="lock"></i>@endsection
@section('hero-title'){{ __('Forgot your password?') }}@endsection
@section('hero-sub'){{ __('No worries — we\'ll send you a verification code to get you back in, by WhatsApp or email.') }}@endsection

@section('content')
    <a href="{{ route('company.login') }}" class="noble-ui-logo d-md-none d-inline-flex align-items-center mb-3" style="gap:.5rem;text-decoration:none">
        <x-front.logo variant="full" style="height:42px;width:auto"/>
        <small class="text-muted fw-normal fs-6">Business</small>
    </a>

    <h4 class="fw-bold mb-1">{{ __('Reset your password') }}</h4>
    <p class="text-muted mb-4">{{ __('Choose how you\'d like to receive your verification code.') }}</p>

    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" role="alert" aria-live="assertive">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.password.send') }}" id="forgotForm" novalidate>
        @csrf

        {{-- Channel picker --}}
        <div class="bk-seg" role="tablist">
            <label>
                <input type="radio" name="channel" value="whatsapp" checked>
                <span class="bk-seg-btn"><i data-feather="message-circle"></i>{{ __('WhatsApp') }}</span>
            </label>
            <label>
                <input type="radio" name="channel" value="email">
                <span class="bk-seg-btn"><i data-feather="mail"></i>{{ __('Email') }}</span>
            </label>
        </div>

        {{-- WhatsApp / phone --}}
        <div class="mb-3" id="field-phone">
            <label for="phone" class="form-label fw-semibold">{{ __('Phone') }}</label>
            <input type="tel" id="phone" dir="ltr" class="form-control" autocomplete="tel">
            <input type="hidden" id="phone_full" name="phone" value="{{ old('phone') }}">
            <div class="form-hint">{{ __('Enter the phone number linked to your business account.') }}</div>
        </div>

        {{-- Email --}}
        <div class="mb-3 d-none" id="field-email">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i data-feather="mail"></i></span>
                <input type="email" id="email" name="email" dir="ltr" class="form-control"
                       value="{{ old('email') }}" placeholder="business@example.com" autocomplete="email">
            </div>
            <div class="form-hint">{{ __('Enter the email you use to sign in.') }}</div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Send code') }}</button>
        </div>

        <div class="mt-3 text-center">
            <a href="{{ route('company.login') }}" class="small fw-semibold text-decoration-none">
                <i data-feather="arrow-left" style="width:15px;height:15px;vertical-align:-2px"></i>
                {{ __('Back to sign in') }}
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const radios   = document.querySelectorAll('input[name="channel"]');
        const fPhone   = document.getElementById('field-phone');
        const fEmail   = document.getElementById('field-email');
        const phoneIn  = document.getElementById('phone');
        const emailIn  = document.getElementById('email');
        const hidden   = document.getElementById('phone_full');
        const form     = document.getElementById('forgotForm');

        let iti = null;
        if (window.intlTelInput && phoneIn) {
            iti = window.intlTelInput(phoneIn, {
                initialCountry: 'sy',
                countryOrder: ['sy', 'lb', 'jo', 'iq', 'tr', 'sa', 'ae', 'eg'],
                separateDialCode: true,
                strictMode: true,
                utilsScript: '{{ asset('backend/assets/vendors/intl-tel-input/js/utils.js') }}',
            });
        }

        function channel() { return document.querySelector('input[name="channel"]:checked').value; }
        function sync() {
            const isPhone = channel() === 'whatsapp';
            fPhone.classList.toggle('d-none', !isPhone);
            fEmail.classList.toggle('d-none', isPhone);
        }
        radios.forEach(r => r.addEventListener('change', sync));
        sync();

        form.addEventListener('submit', function (e) {
            if (channel() === 'whatsapp') {
                if (iti && !iti.isValidNumber()) { e.preventDefault(); phoneIn.classList.add('is-invalid'); phoneIn.focus(); return; }
                hidden.value = iti ? iti.getNumber() : phoneIn.value;
            }
        });
    })();
</script>
@endpush
