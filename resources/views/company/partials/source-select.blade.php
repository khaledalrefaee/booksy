{{--
    Icon-aware source picker — a Bootstrap dropdown that shows brand logos /
    monotone glyphs (which a native <select><option> cannot render) while still
    submitting a plain value through a hidden input.

    Params:
      $name       : hidden input name (e.g. 'source')
      $id         : unique id for the widget
      $current    : selected key (default '')
      $includeAll : true → adds an "All sources" reset option (filter use)
--}}
@php
    $sources    = \App\Models\Customer::SOURCES;
    $current    = $current ?? '';
    $id         = $id ?? 'srcsel-'.uniqid();
    $includeAll = $includeAll ?? false;
    $curMeta    = $sources[$current] ?? null;
@endphp
<div class="bk-srcsel dropdown" id="{{ $id }}" data-src-select>
    <input type="hidden" name="{{ $name }}" value="{{ $current }}">
    <button class="form-select bk-srcsel-btn d-flex align-items-center gap-2 text-start" type="button"
            data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
        <span class="bk-srcsel-current d-flex align-items-center gap-2">
            @if($curMeta)
                <span class="bk-srcsel-ic d-inline-flex">@include('company.partials.source-icon', ['source' => $current, 'size' => 16])</span>
                <span class="text-truncate">{{ __($curMeta['label_key']) }}</span>
            @else
                <span class="bk-srcsel-ic d-inline-flex"><i data-feather="{{ $includeAll ? 'layers' : 'help-circle' }}" style="width:16px;height:16px;"></i></span>
                <span class="text-truncate text-muted">{{ $includeAll ? __('All sources') : __('Select source') }}</span>
            @endif
        </span>
    </button>
    <ul class="dropdown-menu bk-srcsel-menu w-100 shadow-sm">
        @if($includeAll)
        <li>
            <button type="button" class="dropdown-item bk-srcsel-item d-flex align-items-center gap-2" data-val="">
                <span class="bk-srcsel-ic d-inline-flex"><i data-feather="layers" style="width:16px;height:16px;"></i></span>
                <span>{{ __('All sources') }}</span>
            </button>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        @endif
        @foreach($sources as $sKey => $sMeta)
        <li>
            <button type="button" class="dropdown-item bk-srcsel-item d-flex align-items-center gap-2" data-val="{{ $sKey }}">
                <span class="bk-srcsel-ic d-inline-flex">@include('company.partials.source-icon', ['source' => $sKey, 'size' => 16])</span>
                <span>{{ __($sMeta['label_key']) }}</span>
            </button>
        </li>
        @endforeach
    </ul>
</div>
