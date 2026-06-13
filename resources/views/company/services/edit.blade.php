@extends('company.dashboard')
@section('content')
<div class="page-content">

    <div class="mb-4">
        <h4 class="mb-2">{{ __('Edit service') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('company.branches.services.index', $service->branch) }}">{{ $service->branch->localizedName() }}</a></li>
                <li class="breadcrumb-item active">{{ $service->localizedName() }}</li>
            </ol>
        </nav>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">

                    <form method="POST" action="{{ route('company.services.update', $service) }}" novalidate>
                        @csrf @method('PUT')

                        {{-- Category --}}
                        <div class="mb-4">
                            <label for="service_category_id" class="form-label fw-semibold">{{ __('Category') }}</label>
                            <select id="service_category_id" name="service_category_id" class="form-select rounded-3">
                                <option value="">{{ __('No category') }}</option>
                                @foreach($serviceCategories as $cat)
                                    <option value="{{ $cat->id }}"
                                            {{ old('service_category_id', $service->service_category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-4">

                        {{-- Names --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">{{ __('Service name') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="name_en" class="form-label fw-semibold">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                                <input type="text" id="name_en" name="name_en"
                                       class="form-control rounded-3 @error('name_en') is-invalid @enderror"
                                       value="{{ old('name_en', $service->name_en) }}" required maxlength="255">
                                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="name_ar" class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                                <input type="text" id="name_ar" name="name_ar"
                                       class="form-control rounded-3"
                                       value="{{ old('name_ar', $service->name_ar) }}" dir="rtl" maxlength="255">
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                                <textarea id="description" name="description"
                                          class="form-control rounded-3" rows="3">{{ old('description', $service->description) }}</textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Price + Currency + Duration --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">{{ __('Pricing & duration') }}</h6>

                        @include('company.partials.price-currency', [
                            'currentCurrency' => $service->currency,
                            'currentPrice'    => $service->price,
                            'currentDuration' => $service->duration_minutes,
                        ])

                        <hr class="my-4">

                        {{-- Active --}}
                        <div class="form-check form-switch mb-4">
                            <input type="checkbox" class="form-check-input" id="is_active"
                                   name="is_active" value="1"
                                   {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">{{ __('Active') }}</label>
                            <div class="form-text">{{ __('Service is active') }}</div>
                        </div>

                        <div class="d-flex justify-content-between gap-2 pt-3 border-top">
                            <a href="{{ route('company.branches.services.index', $service->branch) }}"
                               class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
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
