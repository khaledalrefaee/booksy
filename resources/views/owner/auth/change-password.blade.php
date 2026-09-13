<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Set your password') }} — GlowRez</title>

    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">

    @php($ownerTheme = request()->cookie('owner_theme', 'dark'))
    @if(app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('backend/assets/css/demo'.($ownerTheme === 'light' ? '1' : '2').'/style-rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('backend/assets/css/demo'.($ownerTheme === 'light' ? '1' : '2').'/style.css') }}">
    @endif

    <link rel="shortcut icon" href="{{ asset('icons/favicon-32.png') }}?v={{ @filemtime(public_path('icons/favicon-32.png')) ?: '1' }}" />
    @if(app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-arabic.css') }}">
    @endif
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
                                    <a href="#" class="noble-ui-logo d-inline-block mb-2" style="text-decoration:none">
                                        <img src="{{ asset('images/glowrez-logo-dark_1.webp') }}" alt="GlowRez" style="height:46px;width:auto">
                                    </a>

                                    @if($owner->must_change_password)
                                        <h5 class="text-muted fw-normal mb-1">{{ __('Welcome, :name 👋', ['name' => $owner->name]) }}</h5>
                                        <p class="text-muted mb-4" style="font-size:.9rem;">{{ __('For your security, please set a new password before continuing.') }}</p>
                                    @else
                                        <h5 class="text-muted fw-normal mb-4">{{ __('Change your password') }}</h5>
                                    @endif

                                    @if ($errors->any())
                                        <div class="alert alert-danger py-2 px-3 mb-3">{{ $errors->first() }}</div>
                                    @endif

                                    <form method="POST" action="{{ route('owner.password.update') }}" class="forms-sample">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-3">
                                            <label for="current_password" class="form-label fw-semibold">
                                                {{ $owner->must_change_password ? __('Temporary password') : __('Current password') }}
                                            </label>
                                            <div class="input-group">
                                                <input type="password" id="current_password" name="current_password"
                                                       class="form-control rounded-start-3 @error('current_password') is-invalid @enderror"
                                                       placeholder="••••••••" autocomplete="current-password" autofocus>
                                                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#current_password" tabindex="-1">
                                                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                                                </button>
                                            </div>
                                            @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label fw-semibold">{{ __('New password') }}</label>
                                            <div class="input-group">
                                                <input type="password" id="password" name="password"
                                                       class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                                                       placeholder="••••••••" autocomplete="new-password">
                                                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1">
                                                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                                                </button>
                                            </div>
                                            <div class="text-muted mt-1" style="font-size:.78rem;">{{ __('At least 8 characters.') }}</div>
                                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="mb-4">
                                            <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm new password') }}</label>
                                            <div class="input-group">
                                                <input type="password" id="password_confirmation" name="password_confirmation"
                                                       class="form-control rounded-start-3" placeholder="••••••••" autocomplete="new-password">
                                                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password_confirmation" tabindex="-1">
                                                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="d-grid mb-3">
                                            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Save password') }}</button>
                                        </div>
                                    </form>

                                    <div class="d-flex align-items-center justify-content-between">
                                        @unless($owner->must_change_password)
                                            <a href="{{ route('owner.profile') }}" class="text-muted small">{{ __('Back to profile') }}</a>
                                        @else
                                            <span class="text-muted small" dir="ltr">{{ $owner->email }}</span>
                                        @endunless
                                        <form method="POST" action="{{ route('owner.logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-link text-muted small p-0" style="text-decoration:none;">
                                                <i data-feather="log-out" style="width:14px;height:14px;"></i> {{ __('Sign out') }}
                                            </button>
                                        </form>
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
