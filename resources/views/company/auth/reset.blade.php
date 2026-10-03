@php($pwLabels = [__('Weak'), __('Fair'), __('Good'), __('Strong')])
@php($title = __('Verify code'))
@extends('company.auth.layout')

@section('hero-title'){{ __('Almost there!') }}@endsection
@section('hero-sub'){{ __('Enter the verification code we sent you, then choose a new password.') }}@endsection

@section('content')
    <a href="{{ route('company.password.forgot') }}" class="ga-back">
        <x-auth.icon name="back" />
        {{ __('Try again') }}
    </a>

    <h1>{{ __('Enter verification code') }}</h1>
    <p class="ga-sent">
        @if($identifier)
            {{ __('We sent a 4-digit code to') }} <b>{{ $identifier }}</b>
            @if(($delivery ?? $channel) === 'sms') <span class="ga-chan">({{ __('SMS') }})</span>
            @elseif(($delivery ?? $channel) === 'whatsapp') <span class="ga-chan">({{ __('WhatsApp') }})</span>
            @else ({{ __('Email') }}) @endif
        @else
            {{ __('Enter the 4-digit code we sent you.') }}
        @endif
    </p>

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

    <form method="POST" action="{{ route('company.password.update') }}" id="resetForm" data-busy="{{ __('Saving…') }}" novalidate>
        @csrf

        <div class="ga-field">
            <label id="otpLabel">{{ __('Verification code') }}</label>
            <div class="ga-otp @if($errors->any()) is-error @endif" role="group" aria-labelledby="otpLabel" data-otp data-target="#code">
                <input type="tel" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="1" autofocus>
                <input type="tel" inputmode="numeric" maxlength="1" aria-label="2">
                <input type="tel" inputmode="numeric" maxlength="1" aria-label="3">
                <input type="tel" inputmode="numeric" maxlength="1" aria-label="4">
            </div>
            <input type="hidden" name="code" id="code">
        </div>

        <div class="ga-field" style="margin-top:1.75rem">
            <label for="password">{{ __('New password') }}<span class="ga-req" aria-hidden="true">*</span></label>
            <div class="ga-input ga-input--pw">
                <span class="ga-ico"><x-auth.icon name="lock" /></span>
                <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="new-password" minlength="8" required>
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

        <button type="submit" class="ga-submit" style="margin-top:1.6rem">
            <span class="ga-submit-label">{{ __('Reset password') }}</span>
            <x-auth.icon name="arrow" class="i-arrow" stroke-width="2" />
            <x-auth.icon name="spin" class="i-spin" stroke-width="2.4" />
        </button>
    </form>
@endsection
