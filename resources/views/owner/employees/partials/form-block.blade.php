@php
    $row = $row ?? [];
    $roleLabel = function ($role) {
        return app()->getLocale() === 'ar'
            ? ($role->label_ar ?: $role->label_en)
            : ($role->label_en ?: $role->label_ar);
    };
@endphp
<div class="employee-block card border rounded-4 mb-3" data-index="{{ $index }}">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-semibold">
                <span class="badge bg-primary rounded-pill me-2 js-employee-number">{{ (int) $index + 1 }}</span>
                {{ __('Employee') }}
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill js-remove-employee" @if($index === 0) hidden @endif>
                <i data-feather="x" style="width:14px;height:14px;"></i>
                {{ __('Remove') }}
            </button>
        </div>

        @include('owner.partials.localized-name-fields', [
            'nameEnId' => 'employee-name-en-'.$index,
            'nameArId' => 'employee-name-ar-'.$index,
            'nameEnField' => 'employees['.$index.'][name_en]',
            'nameArField' => 'employees['.$index.'][name_ar]',
            'nameEnValue' => $row['name_en'] ?? '',
            'nameArValue' => $row['name_ar'] ?? '',
            'errorContext' => 'employees.'.$index,
            'size' => 'md',
            'wrapperClass' => 'mb-3',
        ])

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Phone') }}</label>
                <input type="text" name="employees[{{ $index }}][phone]" value="{{ $row['phone'] ?? '' }}"
                    class="form-control rounded-3 @error('employees.'.$index.'.phone') is-invalid @enderror">
                @error('employees.'.$index.'.phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Email') }}</label>
                <input type="email" name="employees[{{ $index }}][email]" value="{{ $row['email'] ?? '' }}"
                    class="form-control rounded-3 @error('employees.'.$index.'.email') is-invalid @enderror">
                @error('employees.'.$index.'.email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Role') }} <span class="text-danger">*</span></label>
                <select name="employees[{{ $index }}][role_id]" class="form-select rounded-3 @error('employees.'.$index.'.role_id') is-invalid @enderror" required>
                    <option value="">{{ __('Select role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) ($row['role_id'] ?? '') === (string) $role->id)>
                            {{ $roleLabel($role) }}
                        </option>
                    @endforeach
                </select>
                @error('employees.'.$index.'.role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Password') }} <span class="text-danger">*</span></label>
                <input type="password" name="employees[{{ $index }}][password]" autocomplete="new-password"
                    class="form-control rounded-3 @error('employees.'.$index.'.password') is-invalid @enderror" required>
                @error('employees.'.$index.'.password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label fw-semibold">{{ __('Bio') }}</label>
            <textarea name="employees[{{ $index }}][bio]" rows="2" class="form-control rounded-3 @error('employees.'.$index.'.bio') is-invalid @enderror">{{ $row['bio'] ?? '' }}</textarea>
            @error('employees.'.$index.'.bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="employees[{{ $index }}][is_active]" value="1"
                id="employee-active-{{ $index }}"
                @checked(array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true)>
            <label class="form-check-label" for="employee-active-{{ $index }}">{{ __('Employee is active') }}</label>
        </div>
    </div>
</div>
