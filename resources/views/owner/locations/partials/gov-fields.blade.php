@php $isAr = app()->getLocale() === 'ar'; @endphp
<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-semibold">{{ __('Country') }} <span class="text-danger">*</span></label>
        <select name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
            <option value="">— {{ __('Select country') }} —</option>
            @foreach($countries as $c)
                <option value="{{ $c->id }}" {{ old('country_id', $selCountryId) == $c->id ? 'selected' : '' }}>
                    {{ $isAr ? $c->name_ar : $c->name_en }}
                </option>
            @endforeach
        </select>
        @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
        <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en') }}" maxlength="100" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">{{ __('Name (AR)') }} <span class="text-danger">*</span></label>
        <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar') }}" maxlength="100" dir="rtl" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">{{ __('Sort') }}</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
    </div>
</div>
