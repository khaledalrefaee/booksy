{{-- Boolean setting as a switch row. Posts 0 when off (hidden input) and 1 when on. --}}
@props(['name', 'title' => null, 'desc' => null, 'disabled' => false, 'inline' => false])
@php
    $def   = \App\Services\Platform\PlatformSettings::def($name);
    [$g, $k] = explode('.', $name, 2);
    $on    = (bool) old($name, app(\App\Services\Platform\PlatformSettings::class)->get($name));
    $title = $title ?? __($def['label']);
@endphp
<label {{ $attributes->class(['ps-switch' => ! $inline, 'ps-inline-switch' => $inline]) }}>
    @if(! $inline)
        <span class="ps-switch-txt">
            <span class="ps-switch-title">{{ $title }}</span>
            @if($desc)<span class="ps-switch-desc d-block">{{ $desc }}</span>@endif
        </span>
    @else
        <span>{{ $title }}</span>
    @endif
    <span class="ps-toggle">
        <input type="hidden" name="{{ $g }}[{{ $k }}]" value="0">
        <input type="checkbox" role="switch" name="{{ $g }}[{{ $k }}]" value="1" @checked($on) @disabled($disabled)>
        <i aria-hidden="true"></i>
    </span>
</label>
