@php($title = __('Sign in'))
@extends('company.auth.layout')

@section('hero-icon')<i data-feather="log-in"></i>@endsection
@section('hero-title'){{ __('Welcome back') }}@endsection
@section('hero-sub'){{ __('Sign in to manage your bookings, staff and calendar — all in one place.') }}@endsection

@section('content')
    <h4 class="fw-bold mb-1">{{ __('Sign in to your account') }}</h4>
    <p class="text-muted mb-4">{{ __('Welcome back! Enter your details to continue.') }}</p>

    @if (session('status'))
        <div class="alert alert-success py-2 px-3 mb-3" role="alert" aria-live="polite">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('company.login.attempt') }}" class="forms-sample">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i data-feather="mail"></i></span>
                <input type="email" id="email" name="email" dir="ltr"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" placeholder="business@example.com" autofocus autocomplete="email">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i data-feather="lock"></i></span>
                <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="••••••••" autocomplete="current-password">
                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1" aria-label="{{ __('Show password') }}">
                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check mb-0">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label text-muted" for="remember">{{ __('Remember me') }}</label>
            </div>
            <a href="{{ route('company.password.forgot') }}" class="small fw-semibold text-decoration-none">{{ __('Forgot password?') }}</a>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Sign in') }}</button>
        </div>

        <div class="mt-3 text-center">
            <span class="text-muted small">{{ __("Don't have an account?") }}</span>
            <a href="{{ route('company.register') }}" class="small ms-1 fw-semibold">{{ __('Register') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.querySelector(this.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = this.querySelector('[data-feather]');
            icon.setAttribute('data-feather', input.type === 'password' ? 'eye' : 'eye-off');
            feather.replace();
        });
    });
</script>
@endpush
