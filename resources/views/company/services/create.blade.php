@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">{{ __('Add service') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.services.index', $branch) }}">{{ $branch->localizedName() }}</a></li>
                    <li class="breadcrumb-item active">{{ __('New') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.branches.services.index', $branch) }}" class="btn btn-outline-secondary rounded-pill">
            <i data-feather="arrow-left" style="width:14px;"></i> {{ __('Back') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="row">
        <div class="col-md-8 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('company.branches.services.store', $branch) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="service_category_id" class="form-label fw-semibold">{{ __('Category') }}</label>
                            <select id="service_category_id" name="service_category_id" class="form-select">
                                <option value="">{{ __('No category') }}</option>
                                @foreach($serviceCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('service_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->localizedName() }}</option>
                                @endforeach
                            </select>
                            @if($serviceCategories->isEmpty())
                                <div class="form-text text-warning">
                                    {{ __('No service categories yet.') }}
                                    <a href="{{ route('company.service-categories.index') }}">{{ __('Create one') }}</a>
                                </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name_en" class="form-label fw-semibold">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                                <input type="text" id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}">
                                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name_ar" class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                                <input type="text" id="name_ar" name="name_ar" dir="rtl" class="form-control" value="{{ old('name_ar') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                            <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label fw-semibold">{{ __('Price') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="price" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', 0) }}" min="0" step="0.01">
                                    <span class="input-group-text">SAR</span>
                                </div>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="duration_minutes" class="form-label fw-semibold">{{ __('Duration (minutes)') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="duration_minutes" name="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', 30) }}" min="1">
                                    <span class="input-group-text">{{ __('min') }}</span>
                                </div>
                                @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">{{ __('Save service') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
