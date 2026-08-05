@props(['name' => 'circle', 'size' => 24, 'stroke' => 1.75])
@php
    // Lucide-style stroke icons, one consistent visual language across the product.
    $stroke_paths = [
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'map-pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'navigation'  => '<path d="M3 11l19-9-9 19-2-8-8-2Z"/>',
        'calendar'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'       => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m9 12 2 2 4-4"/>',
        'chevron-right'=> '<path d="m9 6 6 6-6 6"/>',
        'chevron-left'=> '<path d="m15 6-6 6 6 6"/>',
        'chevron-down'=> '<path d="m6 9 6 6 6-6"/>',
        'log-out'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'rotate'      => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/>',
        'calendar-check'=> '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18m-11 6 2 2 4-4"/>',
        'alert'       => '<path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up-right'=>'<path d="M7 17 17 7M8 7h9v9"/>',
        'menu'        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x'           => '<path d="M18 6 6 18M6 6l12 12"/>',
        'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'        => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
        'phone'       => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.1-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"/>',
        'users'       => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 4.5a4 4 0 0 1 0 7.5M22 21a7 7 0 0 0-5-6.7"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'shield'      => '<path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5l-8-3Z"/>',
        'wallet'      => '<path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2"/><path d="M3 7v10a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-3"/><path d="M21 11h-5a2 2 0 0 0 0 4h5Z"/>',
        'box'         => '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'bell'        => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
        'chart'       => '<path d="M3 3v18h18"/><path d="M7 15l3-4 3 2 4-6"/>',
        'trending-up' => '<path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/>',
        'sparkles'    => '<path d="M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3Z"/><path d="M19 14l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2Z"/>',
        'scissors'    => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.1 8.1 20 20M8.1 15.9 20 4"/>',
        'heart'       => '<path d="M12 20s-7-4.4-9.3-9A5 5 0 0 1 12 6a5 5 0 0 1 9.3 5c-2.3 4.6-9.3 9-9.3 9Z"/>',
        'star'        => '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9L12 3Z"/>',
        'quote'       => '<path d="M7 7H4v6h3l-1.5 4H8l2-4V7Zm10 0h-3v6h3l-1.5 4H18l2-4V7Z"/>',
        'play'        => '<path d="M6 4v16l14-8L6 4Z"/>',
        'flame'       => '<path d="M12 2s5 4 5 9a5 5 0 0 1-10 0c0-1 .3-1.8.8-2.6C8 10 9 11 9 12c0-3 3-4 3-10Z"/>',
        'award'       => '<circle cx="12" cy="9" r="6"/><path d="M8.2 13.9 7 22l5-3 5 3-1.2-8.1"/>',
        'layers'      => '<path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="m3 13 9 5 9-5M3 18l9 5 9-5"/>',
        'smartphone'  => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/>',
        'building'    => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M9 16h.01M15 16h.01"/>',
        'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'zap'         => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'message'     => '<path d="M21 12a8 8 0 0 1-11.3 7.3L3 21l1.7-6.7A8 8 0 1 1 21 12Z"/>',
        'gift'        => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v9h14v-9M12 8S10 3 7.5 3a2.5 2.5 0 0 0 0 5M12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'tag'         => '<path d="M20 12 12 20l-8-8V4h8l8 8Z"/><circle cx="8" cy="8" r="1.4"/>',
        'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/>',
        'clock-check' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l2.5 1.5"/>',
    ];
    $solid_paths = [
        'star-fill'  => '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9L12 3Z"/>',
        'heart-fill' => '<path d="M12 20s-7-4.4-9.3-9A5 5 0 0 1 12 6a5 5 0 0 1 9.3 5c-2.3 4.6-9.3 9-9.3 9Z"/>',
    ];
    $isSolid = array_key_exists($name, $solid_paths);
    $body = $isSolid ? $solid_paths[$name] : ($stroke_paths[$name] ?? $stroke_paths['search']);
@endphp
<svg {{ $attributes->merge(['class' => 'bkf-ic']) }} width="{{ $size }}" height="{{ $size }}"
     viewBox="0 0 24 24" fill="{{ $isSolid ? 'currentColor' : 'none' }}"
     @if(!$isSolid) stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" @endif
     aria-hidden="true" focusable="false">{!! $body !!}</svg>
