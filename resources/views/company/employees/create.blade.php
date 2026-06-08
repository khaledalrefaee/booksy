@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">{{ __('Add employee') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.employees.index', $branch) }}">{{ $branch->localizedName() }}</a></li>
                    <li class="breadcrumb-item active">{{ __('New') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.branches.employees.index', $branch) }}" class="btn btn-outline-secondary rounded-pill">
            <i data-feather="arrow-left" style="width:14px;"></i> {{ __('Back') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="row">
        <div class="col-md-8 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('company.branches.employees.store', $branch) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name_en" class="form-label fw-semibold">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                                <input type="text" id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}" autofocus>
                                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name_ar" class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                                <input type="text" id="name_ar" name="name_ar" dir="rtl" class="form-control" value="{{ old('name_ar') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label fw-semibold">{{ __('Phone') }}</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label fw-semibold">{{ __('Role') }} <span class="text-danger">*</span></label>
                            <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror">
                                <option value="">{{ __('Select role') }}</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ app()->getLocale() === 'ar' ? ($role->label_ar ?: $role->label_en) : ($role->label_en ?: $role->label_ar) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">{{ __('Password') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
                                    <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1">
                                        <i data-feather="eye" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label fw-semibold">{{ __('Bio') }}</label>
                            <textarea id="bio" name="bio" class="form-control" rows="3">{{ old('bio') }}</textarea>
                        </div>

                        {{-- Service Categories --}}
                        @if($serviceCategories->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                {{ __('Service Categories') }}
                                <span class="text-muted fw-normal small ms-1">({{ __('what this employee can provide') }})</span>
                            </label>
                            <div class="row g-2 mt-1">
                                @foreach($serviceCategories as $cat)
                                <div class="col-sm-6">
                                    <label class="d-flex align-items-center gap-2 p-2 rounded-3 border svc-cat-label"
                                           style="cursor:pointer;" for="scat_{{ $cat->id }}">
                                        <input type="checkbox"
                                               class="form-check-input flex-shrink-0 svc-cat-check"
                                               id="scat_{{ $cat->id }}"
                                               name="service_category_ids[]"
                                               value="{{ $cat->id }}"
                                               {{ in_array($cat->id, old('service_category_ids', [])) ? 'checked' : '' }}>
                                        <span class="fw-semibold small">
                                            {{ app()->getLocale() === 'ar' ? ($cat->name_ar ?: $cat->name_en) : ($cat->name_en ?: $cat->name_ar) }}
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">{{ __('Save employee') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
// Highlight checked service category labels
document.querySelectorAll('.svc-cat-check').forEach(chk => {
    const update = () => {
        chk.closest('.svc-cat-label').classList.toggle('border-primary', chk.checked);
        chk.closest('.svc-cat-label').classList.toggle('bg-primary', chk.checked);
        chk.closest('.svc-cat-label').classList.toggle('bg-opacity-10', chk.checked);
    };
    update();
    chk.addEventListener('change', update);
});

document.querySelectorAll('.js-toggle-pw').forEach(btn => {
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
@endsection
