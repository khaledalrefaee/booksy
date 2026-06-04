@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('New branch') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ol>
        </nav>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    @include('owner.branches.partials.wizard-steps', ['currentStep' => 1])

                    <form method="post" action="{{ route('owner.branches.store') }}" id="branch-step1-form">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="company_id">{{ __('Company') }} <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-select form-select-lg rounded-3 @error('company_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>
                                        {{ $company->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'branch-create-name-en',
                            'nameArId' => 'branch-create-name-ar',
                            'wrapperClass' => 'mb-3',
                        ])

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Mobile phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control rounded-3 @error('phone') is-invalid @enderror">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Landline') }}</label>
                                <input type="text" name="landline_phone" value="{{ old('landline_phone') }}" class="form-control rounded-3 @error('landline_phone') is-invalid @enderror">
                                @error('landline_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">{{ __('Address') }}</label>
                            <textarea name="address" rows="2" class="form-control rounded-3 @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @include('owner.branches.partials.map-picker')

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Sort order') }}</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="form-control rounded-3 @error('sort_order') is-invalid @enderror">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_head_office" id="is_head_office" value="1" @checked(old('is_head_office'))>
                            <label class="form-check-label" for="is_head_office">{{ __('Mark as head office') }}</label>
                        </div>

                        <div class="d-flex justify-content-between gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('owner.branches.index') }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                {{ __('Next') }}
                                <i data-feather="arrow-right" class="ms-1" style="width:16px;height:16px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') {
        window.feather.replace();
    }
});
</script>
@endpush
@endsection
