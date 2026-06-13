@extends('company.dashboard')

@section('content')
<div class="page-content">

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Profile') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('company.dashboard') }}">{{ __('Dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Profile') }}</li>
            </ol>
        </nav>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">

                    {{-- Logo section --}}
                    <div class="d-flex align-items-center gap-4 mb-5 pb-4 border-bottom">
                        <div class="position-relative flex-shrink-0">
                            <img id="logo-preview"
                                 src="{{ $company->logo ? asset('storage/' . $company->logo) : 'https://ui-avatars.com/api/?name=' . urlencode($company->localizedName()) . '&size=96&background=C9A227&color=000&bold=true' }}"
                                 class="rounded-circle border shadow-sm"
                                 width="96" height="96"
                                 style="object-fit:cover;"
                                 alt="{{ $company->localizedName() }}">
                            <label for="logo-input"
                                   class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle p-1"
                                   style="width:28px;height:28px;cursor:pointer;" title="{{ __('Change logo') }}">
                                <i data-feather="camera" style="width:14px;height:14px;"></i>
                            </label>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $company->localizedName() }}</h5>
                            <p class="text-muted mb-0">{{ $company->email }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('company.profile.update') }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <input type="file" id="logo-input" name="logo" accept="image/*" class="d-none">

                        {{-- Company info --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">{{ __('Company information') }}</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="name_en">
                                    {{ __('Name (English)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name_en" name="name_en"
                                       value="{{ old('name_en', $company->name_en) }}"
                                       class="form-control rounded-3 @error('name_en') is-invalid @enderror"
                                       required maxlength="255">
                                @error('name_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="name_ar">
                                    {{ __('Name (Arabic)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name_ar" name="name_ar"
                                       value="{{ old('name_ar', $company->name_ar) }}"
                                       class="form-control rounded-3 @error('name_ar') is-invalid @enderror"
                                       required maxlength="255" dir="rtl">
                                @error('name_ar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="email">
                                    {{ __('Email') }} <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="email" name="email"
                                       value="{{ old('email', $company->email) }}"
                                       class="form-control rounded-3 @error('email') is-invalid @enderror"
                                       required maxlength="255">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="phone">{{ __('Phone') }}</label>
                                <input type="text" id="phone" name="phone"
                                       value="{{ old('phone', $company->phone) }}"
                                       class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                       maxlength="30">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Password section --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">{{ __('Change password') }}</h6>
                        <p class="text-muted small mb-3">{{ __('Leave blank to keep your current password.') }}</p>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="password">{{ __('New password') }}</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password"
                                           class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary js-toggle-password" type="button"
                                            data-target="#password" tabindex="-1">
                                        <i data-feather="eye" style="width:16px;height:16px;"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="password_confirmation">{{ __('Confirm password') }}</label>
                                <div class="input-group">
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                           class="form-control rounded-start-3"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary js-toggle-password" type="button"
                                            data-target="#password_confirmation" tabindex="-1">
                                        <i data-feather="eye" style="width:16px;height:16px;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4">
                                <i data-feather="save" class="me-1" style="width:16px;height:16px;"></i>
                                {{ __('Save changes') }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('logo-input').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => document.getElementById('logo-preview').src = e.target.result;
        reader.readAsDataURL(file);
    });

    document.querySelectorAll('.js-toggle-password').forEach(btn => {
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
