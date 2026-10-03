@props(['name'])
{{-- Inline stroke icons for the auth screens (one family: 24px grid, 1.8 stroke). --}}
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
@switch($name)
    @case('mail')<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 7.5l8.5 6 8.5-6"/>@break
    @case('lock')<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>@break
    @case('user')<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c.8-3.6 3.8-5.5 7.5-5.5s6.7 1.9 7.5 5.5"/>@break
    @case('store')<path d="M4 9.5l1.6-5h12.8l1.6 5"/><path d="M4 9.5a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0 2.7 2.7 0 0 0 5.3 0"/><path d="M5.5 12.5v7h13v-7"/><path d="M10 19.5v-4h4v4"/>@break
    @case('phone')<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>@break
    @case('eye')<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>@break
    @case('eye-off')<path d="M3 3l18 18M10.6 6.1A10.4 10.4 0 0 1 12 6c6.4 0 10 6 10 6a17.6 17.6 0 0 1-3.2 3.9M6.7 7.7A17.5 17.5 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/>@break
    @case('arrow')<path d="M5 12h14M13 6l6 6-6 6"/>@break
    @case('back')<path d="M19 12H5M11 6l-6 6 6 6"/>@break
    @case('check')<path d="M5 12.5l4.5 4.5L19 7.5"/>@break
    @case('chevron')<path d="M6 9l6 6 6-6"/>@break
    @case('shield')<path d="M12 3l7.5 3v5.5c0 4.6-3.1 8.2-7.5 9.5-4.4-1.3-7.5-4.9-7.5-9.5V6z"/><path d="M9 12l2.2 2.2L15.5 10"/>@break
    @case('alert')<circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/>@break
    @case('ok')<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>@break
    @case('spin')<path d="M12 3a9 9 0 1 0 9 9"/>@break
@endswitch
</svg>
