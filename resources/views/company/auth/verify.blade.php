@php($title = __('Verify your account'))
@extends('company.auth.layout')

@section('hero-title'){{ __('One last step') }}@endsection
@section('hero-sub'){{ __('Confirm your account with the code we just sent — and you\'re all set to start taking bookings.') }}@endsection

@section('content')
    <h1>{{ __('Verify your account') }}</h1>
    <p class="ga-sent">
        {{ __('We sent a 4-digit code to your phone') }}
        @if($phone)<b>{{ $phone }}</b>@endif
        {{ __('and email') }}
        @if($email)<b>{{ $email }}</b>@endif.
    </p>

    @if (session('status'))
        <div class="ga-alert ga-alert--ok" role="status" aria-live="polite">
            <x-auth.icon name="ok" />
            <span>{{ session('status') }}</span>
        </div>
    @endif
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

    <form method="POST" action="{{ route('company.verify.attempt') }}" id="verifyForm" data-busy="{{ __('Verifying…') }}" novalidate>
        @csrf
        <div class="ga-field" style="margin-bottom:1.75rem">
            <label id="otpLabel">{{ __('Verification code') }}</label>
            <div class="ga-otp @if($errors->has('code') || ($errors->any() && ! $errors->has('email') && ! $errors->has('phone'))) is-error @endif"
                 role="group" aria-labelledby="otpLabel" data-otp data-target="#code" data-autosubmit>
                <input type="tel" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="1" autofocus>
                <input type="tel" inputmode="numeric" maxlength="1" aria-label="2">
                <input type="tel" inputmode="numeric" maxlength="1" aria-label="3">
                <input type="tel" inputmode="numeric" maxlength="1" aria-label="4">
            </div>
            <input type="hidden" name="code" id="code">
        </div>

        <button type="submit" class="ga-submit">
            <span class="ga-submit-label">{{ __('Verify') }}</span>
            <x-auth.icon name="arrow" class="i-arrow" stroke-width="2" />
            <x-auth.icon name="spin" class="i-spin" stroke-width="2.4" />
        </button>
    </form>

    <div class="ga-resend">
        <span>{{ __("Didn't get the code?") }}</span>
        <form method="POST" action="{{ route('company.verify.resend') }}">
            @csrf
            <button type="submit" class="ga-link" data-cooldown="30" data-cooldown-key="company-verify" data-wait="{{ __('Resend in') }}">{{ __('Resend code') }}</button>
        </form>
    </div>

    <details class="ga-details" @if($errors->has('email') || $errors->has('phone')) open @endif>
        <summary>
            <span>{{ __('Wrong email or phone number? Edit it') }}</span>
            <x-auth.icon name="chevron" />
        </summary>
        <div class="ga-details-body">
            <form method="POST" action="{{ route('company.verify.contact') }}" data-busy="{{ __('Saving…') }}" novalidate>
                @csrf
                <div class="ga-field @error('email') has-error @enderror">
                    <label for="c_email">{{ __('Email') }}</label>
                    <div class="ga-input">
                        <span class="ga-ico"><x-auth.icon name="mail" /></span>
                        <input type="email" id="c_email" name="email" value="{{ old('email', $email) }}" autocomplete="email" inputmode="email" required>
                    </div>
                </div>
                <div class="ga-field @error('phone') has-error @enderror">
                    <label for="c_phone">{{ __('Phone') }}</label>
                    <div class="ga-input">
                        <span class="ga-ico"><x-auth.icon name="phone" /></span>
                        <input type="tel" id="c_phone" name="phone" dir="ltr" value="{{ old('phone', $phone) }}" placeholder="+9639xxxxxxxx" autocomplete="tel" inputmode="tel" required>
                    </div>
                </div>
                <button type="submit" class="ga-ghost">
                    <span class="ga-submit-label">{{ __('Save and resend code') }}</span>
                </button>
            </form>
        </div>
    </details>

    <form method="POST" action="{{ route('company.logout') }}">
        @csrf
        <button type="submit" class="ga-link ga-quiet" style="color:var(--muted);font-weight:500">{{ __('Log out / use another account') }}</button>
    </form>
@endsection
