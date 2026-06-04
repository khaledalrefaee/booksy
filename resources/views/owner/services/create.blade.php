@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('New service') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.services.index', $branch) }}">{{ $branch->localizedName() }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ol>
        </nav>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="post" action="{{ route('owner.branches.services.store', $branch) }}">
                        @csrf
                        @include('owner.partials.service-category-select', [
                            'categories' => $serviceCategories,
                            'selectId' => 'service-create-category',
                        ])
                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'service-create-name-en',
                            'nameArId' => 'service-create-name-ar',
                            'wrapperClass' => 'mb-3',
                        ])
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">{{ __('Description') }}</label>
                            <textarea name="description" rows="3" class="form-control rounded-3 @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Price') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="form-control rounded-3 @error('price') is-invalid @enderror" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Duration (minutes)') }} <span class="text-danger">*</span></label>
                                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 30) }}" min="1" class="form-control rounded-3 @error('duration_minutes') is-invalid @enderror" required>
                                @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">{{ __('Service is active') }}</label>
                        </div>
                        <div class="d-flex gap-2 mt-4 pt-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">{{ __('Save') }}</button>
                            <a href="{{ route('owner.branches.services.index', $branch) }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
