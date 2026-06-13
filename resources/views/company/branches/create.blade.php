@extends('company.dashboard')

@section('content')
<div class="page-content">

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Add branch') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('company.dashboard') }}">{{ __('Dashboard') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ol>
        </nav>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">

                    <form method="POST" action="{{ route('company.branches.store') }}" novalidate>
                        @csrf

                        {{-- ── Basic info ────────────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                            {{ __('Branch information') }}
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="name_en">
                                    {{ __('Branch name (EN)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name_en" name="name_en"
                                       value="{{ old('name_en') }}"
                                       class="form-control rounded-3 @error('name_en') is-invalid @enderror"
                                       required maxlength="255">
                                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="name_ar">
                                    {{ __('Branch name (AR)') }}
                                </label>
                                <input type="text" id="name_ar" name="name_ar"
                                       value="{{ old('name_ar') }}"
                                       class="form-control rounded-3 @error('name_ar') is-invalid @enderror"
                                       dir="rtl" maxlength="255">
                                @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="address">{{ __('Address') }}</label>
                                <input type="text" id="address" name="address"
                                       value="{{ old('address') }}"
                                       class="form-control rounded-3 @error('address') is-invalid @enderror"
                                       maxlength="500">
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- ── أرقام الهاتف ──────────────────────────────────── --}}
                            <div class="col-12">
                                @include('company.branches.partials.phone-fields')
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- ── Social Media ──────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-1">
                            <i data-feather="share-2" style="width:14px;height:14px;" class="me-1"></i>
                            {{ __('Social Media Links') }}
                        </h6>
                        <p class="text-muted small mb-3">{{ __('Add any social accounts you want customers to find you on.') }}</p>

                        <div class="border rounded-3 mb-4">
                            @include('partials.social-links-form', [
                                'savedLinks'       => collect(),
                                'inputPrefix'      => 'social_links',
                                'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram', 'linkedin'],
                            ])
                        </div>

                        <hr class="my-4">

                        {{-- ── Status ────────────────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                            {{ __('Branch status') }}
                        </h6>

                        <div class="d-flex gap-3 flex-wrap mb-4">
                            @foreach([
                                'active'      => ['check-circle', 'success',   __('Active'),      __('Open & visible to customers')],
                                'inactive'    => ['x-circle',    'secondary',  __('Inactive'),    __('Hidden from public')],
                                'maintenance' => ['tool',         'warning',   __('Maintenance'), __('Visible but booking disabled')],
                            ] as $st => [$icon, $color, $lbl, $hint])
                            <label class="d-flex align-items-start gap-2 p-3 rounded-3 border flex-fill
                                       {{ old('status', 'active') === $st ? 'border-'.$color.' bg-'.$color.' bg-opacity-10' : '' }}"
                                   style="cursor:pointer;min-width:145px;" id="status-lbl-{{ $st }}">
                                <input type="radio" name="status" value="{{ $st }}"
                                       class="form-check-input mt-0 flex-shrink-0"
                                       {{ old('status', 'active') === $st ? 'checked' : '' }}
                                       onchange="highlightStatus()">
                                <div>
                                    <span class="fw-semibold text-{{ $color }} d-flex align-items-center gap-1">
                                        <i data-feather="{{ $icon }}" style="width:13px;height:13px;"></i>
                                        {{ $lbl }}
                                    </span>
                                    <small class="text-muted">{{ $hint }}</small>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('status')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox"
                                   name="is_head_office" id="is_head_office" value="1"
                                   @checked(old('is_head_office'))>
                            <label class="form-check-label" for="is_head_office">
                                {{ __('Mark as head office') }}
                            </label>
                        </div>

                        <hr class="my-4">

                        {{-- ── Map ──────────────────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                            {{ __('Location') }}
                        </h6>

                        @include('company.branches.partials.map-picker')

                        {{-- ── Footer ───────────────────────────────────────────── --}}
                        <div class="d-flex justify-content-between gap-2 mt-5 pt-3 border-top">
                            <a href="{{ route('company.branches.index') }}"
                               class="btn btn-light rounded-pill px-4">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i data-feather="save" class="me-1" style="width:16px;height:16px;"></i>
                                {{ __('Save branch') }}
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
const statusColors = { active: 'success', inactive: 'secondary', maintenance: 'warning' };

function highlightStatus() {
    document.querySelectorAll('[name="status"]').forEach(function (radio) {
        var lbl = document.getElementById('status-lbl-' + radio.value);
        if (!lbl) return;
        var c = statusColors[radio.value];
        if (radio.checked) {
            lbl.classList.add('border-' + c, 'bg-' + c, 'bg-opacity-10');
        } else {
            lbl.classList.remove(
                'border-success', 'border-secondary', 'border-warning',
                'bg-success', 'bg-secondary', 'bg-warning', 'bg-opacity-10'
            );
        }
    });
    if (window.feather) feather.replace();
}
</script>
@endpush
