@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Edit employee') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.employees.index', $branch) }}">{{ $branch->localizedName() }}</a></li>
                <li class="breadcrumb-item active">{{ $employee->localizedName() }}</li>
            </ol>
        </nav>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    @php
                        $roleLabel = function ($role) {
                            return app()->getLocale() === 'ar'
                                ? ($role->label_ar ?: $role->label_en)
                                : ($role->label_en ?: $role->label_ar);
                        };
                    @endphp
                    <form method="post" action="{{ route('owner.employees.update', $employee) }}">
                        @csrf
                        @method('PUT')
                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'employee-edit-name-en',
                            'nameArId' => 'employee-edit-name-ar',
                            'nameEnValue' => old('name_en', $employee->name_en),
                            'nameArValue' => old('name_ar', $employee->name_ar),
                            'wrapperClass' => 'mb-3',
                        ])
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control rounded-3 @error('phone') is-invalid @enderror">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Email') }}</label>
                                <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control rounded-3 @error('email') is-invalid @enderror">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Role') }} <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-select rounded-3 @error('role_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select role') }}</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected((string) old('role_id', $employee->role_id) === (string) $role->id)>
                                            {{ $roleLabel($role) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('New password') }}</label>
                                <input type="password" name="password" autocomplete="new-password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="{{ __('Leave blank to keep current') }}">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">{{ __('Bio') }}</label>
                            <textarea name="bio" rows="3" class="form-control rounded-3 @error('bio') is-invalid @enderror">{{ old('bio', $employee->bio) }}</textarea>
                            @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $employee->is_active))>
                            <label class="form-check-label" for="is_active">{{ __('Employee is active') }}</label>
                        </div>
                        <div class="d-flex gap-2 mt-4 pt-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">{{ __('Update') }}</button>
                            <a href="{{ route('owner.branches.employees.index', $branch) }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
