@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Edit branch') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
            </ol>
        </nav>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="post" action="{{ route('owner.branches.update', $branch) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="company_id">{{ __('Company') }} <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-select form-select-lg rounded-3 @error('company_id') is-invalid @enderror" required>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((int) old('company_id', $branch->company_id) === (int) $company->id)>
                                        {{ $company->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'branch-edit-name-en',
                            'nameArId' => 'branch-edit-name-ar',
                            'nameEnValue' => old('name_en', $branch->name_en),
                            'nameArValue' => old('name_ar', $branch->name_ar),
                            'wrapperClass' => 'mb-3',
                        ])

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Mobile phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-control rounded-3 @error('phone') is-invalid @enderror">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Landline') }}</label>
                                <input type="text" name="landline_phone" value="{{ old('landline_phone', $branch->landline_phone) }}" class="form-control rounded-3 @error('landline_phone') is-invalid @enderror">
                                @error('landline_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">{{ __('Address') }}</label>
                            <textarea name="address" rows="2" class="form-control rounded-3 @error('address') is-invalid @enderror">{{ old('address', $branch->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @include('owner.branches.partials.map-picker', [
                            'latitude' => old('latitude', $branch->latitude),
                            'longitude' => old('longitude', $branch->longitude),
                        ])

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Sort order') }}</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', $branch->sort_order) }}" min="0" class="form-control rounded-3 @error('sort_order') is-invalid @enderror">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_head_office" id="is_head_office" value="1" @checked(old('is_head_office', $branch->is_head_office))>
                            <label class="form-check-label" for="is_head_office">{{ __('Mark as head office') }}</label>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">{{ __('Update') }}</button>
                            <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="btn btn-outline-secondary rounded-pill px-4">{{ __('Edit working hours') }}</a>
                            <a href="{{ route('owner.branches.index') }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
