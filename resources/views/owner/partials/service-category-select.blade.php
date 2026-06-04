@php
    $selectedId = $selectedId ?? old('service_category_id');
    $categories = $categories ?? collect();
@endphp
<div class="mb-3">
    <label class="form-label fw-semibold" for="{{ $selectId ?? 'service_category_id' }}">
        {{ __('Service category') }} <span class="text-danger">*</span>
    </label>
    <select name="service_category_id" id="{{ $selectId ?? 'service_category_id' }}" class="form-select form-select-lg rounded-3 @error('service_category_id') is-invalid @enderror" required>
        <option value="">{{ __('Select service category') }}</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) $selectedId === (string) $category->id)>
                {{ $category->localizedName() }}
            </option>
        @endforeach
    </select>
    @error('service_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($categories->isEmpty())
        <p class="text-warning tx-12 mt-2 mb-0">
            {{ __('No service categories yet.') }}
            <a href="{{ route('owner.service-categories.index') }}">{{ __('Add service category') }}</a>
        </p>
    @endif
</div>
