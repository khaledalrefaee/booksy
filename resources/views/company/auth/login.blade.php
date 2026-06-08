<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Sign in') }} — Booksy Business</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    @php($theme = request()->cookie('company_theme', 'dark'))
    @if(app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset($theme === 'light' ? 'backend/assets/css/demo1/style-rtl.css' : 'backend/assets/css/demo2/style-rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset($theme === 'light' ? 'backend/assets/css/demo1/style.css' : 'backend/assets/css/demo2/style.css') }}">
    @endif
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}" />
</head>
<body>
<div class="main-wrapper">
    <div class="page-wrapper full-page">
        <div class="page-content d-flex align-items-center justify-content-center">
            <div class="row w-100 mx-0 auth-page">
                <div class="col-md-8 col-xl-6 mx-auto">
                    <div class="card">
                        <div class="row">
                            <div class="col-md-4 pe-md-0">
                                <div class="auth-side-wrapper"></div>
                            </div>
                            <div class="col-md-8 ps-md-0">
                                <div class="auth-form-wrapper px-4 py-5">
                                    <a href="#" class="noble-ui-logo d-block mb-2">Book<span>sy</span> <small class="text-muted fw-normal fs-6">Business</small></a>
                                    <h5 class="text-muted fw-normal mb-4">{{ __('Welcome back! Sign in to continue.') }}</h5>

                                    @if ($errors->any())
                                        <div class="alert alert-danger py-2 px-3 mb-3">{{ $errors->first() }}</div>
                                    @endif

                                    <form method="POST" action="{{ route('company.login.attempt') }}" class="forms-sample">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
                                            <input type="email" id="email" name="email"
                                                class="form-control rounded-3 @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" placeholder="example@email.com" autofocus autocomplete="email">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                                            <div class="input-group">
                                                <input type="password" id="password" name="password"
                                                    class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                                                    placeholder="••••••••" autocomplete="current-password">
                                                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1">
                                                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                                <label class="form-check-label text-muted" for="remember">{{ __('Remember me') }}</label>
                                            </div>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Sign in') }}</button>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <span class="text-muted small">{{ __("Don't have an account?") }}</span>
                                            <a href="{{ route('company.register') }}" class="small ms-1">{{ __('Register') }}</a>
                                        </div>
                                    </form>
                                    <div class="mt-4 d-flex gap-2">
                                        @php($currentLocale = app()->getLocale())
                                        <a href="{{ route('locale.switch', ['locale' => 'en']) }}" class="text-muted small {{ $currentLocale === 'en' ? 'fw-bold text-primary' : '' }}">
                                            <i class="flag-icon flag-icon-us me-1"></i>English
                                        </a>
                                        <span class="text-muted small">|</span>
                                        <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" class="text-muted small {{ $currentLocale === 'ar' ? 'fw-bold text-primary' : '' }}">
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
</body>
</html>
