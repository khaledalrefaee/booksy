@php($title = __('Sign in'))
@extends('company.auth.layout')

@section('hero-title'){{ __('Welcome back') }}@endsection
@section('hero-sub'){{ __('Sign in to manage your bookings, staff and calendar — all in one place.') }}@endsection

@section('content')
    <h1>{{ __('Sign in to your account') }}</h1>
    <p class="ga-sub">{{ __('Welcome back! Enter your details to continue.') }}</p>

    @if (session('status'))
        <div class="ga-alert ga-alert--ok" role="status" aria-live="polite">
            <x-auth.icon name="ok" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="ga-alert" role="alert">
            <x-auth.icon name="alert" />
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('company.login.attempt') }}" id="loginForm" data-busy="{{ __('Signing in…') }}" novalidate>
        @csrf

        <div class="ga-field @error('email') has-error @enderror">
            <label for="email">{{ __('Email') }}</label>
            <div class="ga-input">
                <span class="ga-ico"><x-auth.icon name="mail" /></span>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       placeholder="business@example.com" autocomplete="email" inputmode="email" required autofocus
                       @error('email') aria-invalid="true" @enderror>
            </div>
            @error('email')@if($message !== $errors->first())<p class="ga-err">{{ $message }}</p>@endif @enderror
        </div>

        <div class="ga-field @error('password') has-error @enderror">
            <div class="ga-label-row">
                <label for="password">{{ __('Password') }}</label>
                <a href="{{ route('company.password.forgot') }}" class="ga-link" style="font-size:.875rem">{{ __('Forgot password?') }}</a>
            </div>
            <div class="ga-input ga-input--pw">
                <span class="ga-ico"><x-auth.icon name="lock" /></span>
                <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required
                       @error('password') aria-invalid="true" @enderror>
                <button type="button" class="ga-eye" aria-pressed="false" aria-controls="password"
                        aria-label="{{ __('Show password') }}" data-show="{{ __('Show password') }}" data-hide="{{ __('Hide password') }}">
                    <x-auth.icon name="eye" class="i-on" />
                    <x-auth.icon name="eye-off" class="i-off" />
                </button>
            </div>
            @error('password')@if($message !== $errors->first())<p class="ga-err">{{ $message }}</p>@endif @enderror
        </div>

        <label class="ga-check">
            <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
            <span>{{ __('Remember me') }}</span>
        </label>

        <button type="submit" class="ga-submit">
            <span class="ga-submit-label">{{ __('Sign in') }}</span>
            <x-auth.icon name="arrow" class="i-arrow" stroke-width="2" />
            <x-auth.icon name="spin" class="i-spin" stroke-width="2.4" />
        </button>

        <p class="ga-switch">
            {{ __("Don't have an account?") }}
            <a href="{{ route('company.register') }}" class="ga-link">{{ __('Register') }}</a>
        </p>
    </form>
@endsection
