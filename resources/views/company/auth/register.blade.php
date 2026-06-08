<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Register') }} — Booksy Business</title>
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
        <div class="page-content d-flex align-items-center justify-content-center py-5">
            <div class="row w-100 mx-0 auth-page">
                <div class="col-md-9 col-xl-7 mx-auto">
                    <div class="card">
                        <div class="row">
                            <div class="col-md-4 pe-md-0">
                                <div class="auth-side-wrapper"></div>
                            </div>
                            <div class="col-md-8 ps-md-0">
                                <div class="auth-form-wrapper px-4 py-5">
                                    <a href="#" class="noble-ui-logo d-block mb-2">Book<span>sy</span> <small class="text-muted fw-normal fs-6">Business</small></a>
                                    <h5 class="text-muted fw-normal mb-4">{{ __('Create your business account') }}</h5>

                                    @if ($errors->any())
                                        <div class="alert alert-danger py-2 px-3 mb-3">
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('company.register.attempt') }}" class="forms-sample">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name_en" class="form-label fw-semibold">{{ __('Business name (EN)') }} <span class="text-danger">*</span></label>
                                                <input type="text" id="name_en" name="name_en"
                                                    class="form-control @error('name_en') is-invalid @enderror"
                                                    value="{{ old('name_en') }}" placeholder="My Salon" autofocus>
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
                                            <label for="email" class="form-label fw-semibold">{{ __('Email') }} <span class="text-danger">*</span></label>
                                            <input type="email" id="email" name="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" placeholder="business@example.com" autocomplete="email">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label fw-semibold">{{ __('Phone') }} <span class="text-danger">*</span></label>
                                            <input type="text" id="phone" name="phone"
                                                class="form-control @error('phone') is-invalid @enderror"
                                                value="{{ old('phone') }}" placeholder="+966500000000">
                                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label fw-semibold">{{ __('Business type') }} <span class="text-danger">*</span></label>
                                            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                                <option value="">{{ __('Select category') }}</option>
                                                @foreach ($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                        {{ $cat->localizedName() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="password" class="form-label fw-semibold">{{ __('Password') }} <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" id="password" name="password"
                                                        class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                                                        placeholder="••••••••" autocomplete="new-password">
                                                    <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1">
                                                        <i data-feather="eye" style="width:15px;height:15px;"></i>
                                                    </button>
                                                </div>
                                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
                                                <input type="password" id="password_confirmation" name="password_confirmation"
                                                    class="form-control" placeholder="••••••••" autocomplete="new-password">
                                            </div>
                                        </div>
                                        <div class="d-grid mt-2">
                                            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Create account') }}</button>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <span class="text-muted small">{{ __('Already have an account?') }}</span>
                                            <a href="{{ route('company.login') }}" class="small ms-1">{{ __('Sign in') }}</a>
                                        </div>
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
