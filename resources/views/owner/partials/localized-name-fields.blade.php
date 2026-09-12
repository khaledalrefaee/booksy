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

    // Describe each language field once, then render them in an order that matches
    // the current UI language. Under RTL, Bootstrap flips the grid columns, so the
    // DOM-first field lands on the RIGHT (the reading start). Leading with the
    // current locale's field keeps the Arabic input on the right for an Arabic UI
    // (and English on the left for English) — otherwise the fields read reversed
    // and users type each name into the wrong box.
    $fields = [
        'en' => [
            'name'    => $nameEnField,
            'id'      => $nameEnId,
            'value'   => $nameEnValue,
            'label'   => __('Name (English)'),
            'holder'  => __('Enter name (English)'),
            'invalid' => $invalidEn,
            'errKey'  => 'name_en',
            'dir'     => null,
            'lang'    => null,
        ],
        'ar' => [
            'name'    => $nameArField,
            'id'      => $nameArId,
            'value'   => $nameArValue,
            'label'   => __('Name (Arabic)'),
            'holder'  => __('Enter Name (Arabic)'),
            'invalid' => $invalidAr,
            'errKey'  => 'name_ar',
            'dir'     => 'rtl',
            'lang'    => 'ar',
        ],
    ];
    $order = app()->getLocale() === 'ar' ? ['ar', 'en'] : ['en', 'ar'];
@endphp
<div class="row g-3 {{ $wrapperClass ?? '' }}">
    @foreach ($order as $key)
        @php($f = $fields[$key])
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="{{ $f['id'] }}">
                <span class="text-danger">*</span> {{ $f['label'] }}
            </label>
            <input type="text" name="{{ $f['name'] }}" id="{{ $f['id'] }}" maxlength="255" required
                value="{{ $f['value'] }}"
                @if ($f['dir']) dir="{{ $f['dir'] }}" @endif
                @if ($f['lang']) lang="{{ $f['lang'] }}" @endif
                placeholder="{{ $f['holder'] }}"
                class="{{ $inputClass }} @if($f['invalid']) is-invalid @endif">
            @if ($showErrors)
                @if ($errorContext)
                    @error($errorContext.'.'.$f['errKey'])<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @else
                    @error($f['errKey'])<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @endif
            @endif
        </div>
    @endforeach
</div>
