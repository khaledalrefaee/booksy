@props([
    'href'    => null,      // renders an <a>; omit for a <button>
    'icon'    => null,      // feather icon name, e.g. "eye", "edit-2", "trash-2"
    'variant' => 'default', // default | primary | danger | success | warning
    'type'    => 'button',  // button type when no href
    'iconOnly'=> false,     // hide the label (icon-only); label still used for aria/title
])

{{--
    One row/action button, everywhere.

    Unifies the scattered per-page action chips (the old .btn-act blocks and the
    hand-styled "btn btn-sm rounded-pill" links) into a single olive/gold system.
    Every action reads as icon + label so the same action looks identical on every
    page. Colours come from the --bk-* tokens, so both themes are covered.

        <x-bk-action :href="route('company.employees.show', $emp)" icon="eye">{{ __('Show') }}</x-bk-action>
        <x-bk-action :href="route('company.employees.edit', $emp)" icon="edit-2" variant="primary">{{ __('Edit') }}</x-bk-action>
        <x-bk-action variant="danger" icon="trash-2" onclick="...">{{ __('Delete') }}</x-bk-action>
--}}

@php
    $label   = trim($slot);
    $classes = 'bk-act bk-act--' . $variant . ($iconOnly ? ' bk-act--icon' : '');
@endphp

@if($href)
<a href="{{ $href }}"
   {{ $attributes->merge(['class' => $classes] + ($iconOnly && $label ? ['title' => $label, 'aria-label' => $label] : [])) }}>
    @if($icon)<i data-feather="{{ $icon }}" aria-hidden="true"></i>@endif
    @unless($iconOnly)<span>{{ $slot }}</span>@endunless
</a>
@else
<button type="{{ $type }}"
   {{ $attributes->merge(['class' => $classes] + ($iconOnly && $label ? ['title' => $label, 'aria-label' => $label] : [])) }}>
    @if($icon)<i data-feather="{{ $icon }}" aria-hidden="true"></i>@endif
    @unless($iconOnly)<span>{{ $slot }}</span>@endunless
</button>
@endif
