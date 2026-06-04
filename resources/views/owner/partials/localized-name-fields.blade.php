@php
    $nameEnId = $nameEnId ?? 'name-en';
    $nameArId = $nameArId ?? 'name-ar';
    $nameEnField = $nameEnField ?? 'name_en';
    $nameArField = $nameArField ?? 'name_ar';
    $nameEnValue = $nameEnValue ?? old('name_en', '');
    $nameArValue = $nameArValue ?? old('name_ar', '');
    $errorContext = $errorContext ?? null;
    $showErrors = $showErrors ?? true;
    $inputClass = ($size ?? 'lg') === 'lg' ? 'form-control form-control-lg' : 'form-control rounded-3';
    $invalidEn = $showErrors && ($errorContext ? $errors->has($errorContext.'.name_en') : $errors->has('name_en'));
    $invalidAr = $showErrors && ($errorContext ? $errors->has($errorContext.'.name_ar') : $errors->has('name_ar'));
@endphp
<div class="row g-3 {{ $wrapperClass ?? '' }}">
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="{{ $nameEnId }}">
            <span class="text-danger">*</span> {{ __('Name (English)') }}
        </label>
        <input type="text" name="{{ $nameEnField }}" id="{{ $nameEnId }}" maxlength="255" required
            value="{{ $nameEnValue }}"
            placeholder="{{ __('Enter name (English)') }}"
            class="{{ $inputClass }} @if($invalidEn) is-invalid @endif">
        @if ($showErrors)
            @if ($errorContext)
                @error($errorContext.'.name_en')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @else
                @error('name_en')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @endif
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="{{ $nameArId }}">
            <span class="text-danger">*</span> {{ __('Name (Arabic)') }}
        </label>
        <input type="text" name="{{ $nameArField }}" id="{{ $nameArId }}" maxlength="255" required
            value="{{ $nameArValue }}"
            dir="rtl" lang="ar"
            placeholder="{{ __('Enter Name (Arabic)') }}"
            class="{{ $inputClass }} @if($invalidAr) is-invalid @endif">
        @if ($showErrors)
            @if ($errorContext)
                @error($errorContext.'.name_ar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @else
                @error('name_ar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @endif
        @endif
    </div>
</div>
