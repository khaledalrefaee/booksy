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

            {{-- ══ My subscription ══ --}}
            @php
                $hasPlan   = $company->plan_id !== null;
                $active    = $company->isSubscriptionActive();
                $daysLeft  = $company->plan_expires_at ? (int) now()->startOfDay()->diffInDays($company->plan_expires_at, false) : null;
                $maxBr     = $company->maxBranches();
                $maxEmp    = $company->maxEmployees();
            @endphp
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h6 class="fw-semibold text-muted text-uppercase small mb-0">
                            <i data-feather="package" style="width:14px;height:14px;" class="me-1"></i>
                            {{ __('My subscription') }}
                        </h6>
                        @if (! $hasPlan)
                            <span class="badge rounded-pill bg-success">{{ __('Full access') }}</span>
                        @elseif ($active)
                            <span class="badge rounded-pill bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge rounded-pill bg-danger">{{ __('Expired') }}</span>
                        @endif
                    </div>

                    <div class="row g-3 mb-1">
                        <div class="col-sm-4">
                            <p class="text-muted small mb-1">{{ __('Plan') }}</p>
                            <p class="fw-bold mb-0">
                                {{ $hasPlan ? $company->plan?->localizedName() : __('Full access') }}
                                @if ($hasPlan && (float) $company->plan?->price > 0)
                                    <span class="text-muted fw-normal small">({{ number_format((float) $company->plan->price, 2) }} {{ $company->plan->currency }})</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted small mb-1">{{ __('Expires at') }}</p>
                            <p class="fw-bold mb-0">
                                @if ($company->plan_expires_at)
                                    {{ $company->plan_expires_at->format('Y-m-d') }}
                                    @if ($active && $daysLeft !== null)
                                        <span class="badge rounded-pill {{ $daysLeft <= 7 ? 'bg-warning text-dark' : 'bg-light text-dark border' }} ms-1">
                                            {{ __(':days day(s) left', ['days' => $daysLeft]) }}
                                        </span>
                                    @endif
                                @else
                                    {{ __('Never expires') }}
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted small mb-1">{{ __('Usage') }}</p>
                            <p class="fw-bold mb-0">
                                {{ __('Branches') }}: {{ $usage['branches'] }}{{ $maxBr !== null ? ' / '.$maxBr : '' }}
                                <span class="text-muted mx-1">·</span>
                                {{ __('Employees') }}: {{ $usage['employees'] }}{{ $maxEmp !== null ? ' / '.$maxEmp : '' }}
                            </p>
                        </div>
                    </div>

                    @if ($hasPlan && ! $active)
                        <div class="alert alert-danger py-2 mt-3 mb-3">
                            {{ __('Your subscription has expired — renew to restore the features below.') }}
                        </div>
                    @endif

                    <hr class="my-3">

                    <p class="text-muted small mb-2">{{ __('Included features') }}
                        <span class="text-muted">— {{ __('Appointments, customers, branches, services and employees are always included.') }}</span>
                    </p>
                    <div class="row g-2">
                        @foreach ($featureCatalog as $key => $f)
                            @php $on = $company->hasFeature($key); @endphp
                            <div class="col-md-4 col-sm-6">
                                <div class="d-flex align-items-center gap-2 small {{ $on ? '' : 'text-muted opacity-50' }}">
                                    <i data-feather="{{ $on ? 'check-circle' : 'x-circle' }}"
                                       class="{{ $on ? 'text-success' : '' }}" style="width:15px;height:15px;flex-shrink:0;"></i>
                                    <span>{{ $f['label'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($hasPlan)
                        <p class="text-muted small mt-3 mb-0">
                            <i data-feather="info" style="width:13px;height:13px;" class="me-1"></i>
                            {{ __('To upgrade your plan or renew, contact us.') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">

                    {{-- Logo section --}}
                    <div class="d-flex align-items-center gap-4 mb-5 pb-4 border-bottom">
                        <div class="position-relative flex-shrink-0">
                            <img id="logo-preview"
                                 src="{{ $company->logo ? asset('storage/' . $company->logo) : 'https://ui-avatars.com/api/?name=' . urlencode($company->localizedName()) . '&size=96&background=4B5D34&color=FFFFFF&bold=true' }}"
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
