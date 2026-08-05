@props([
    'top'      => 'transparent',            // background of the area ABOVE the wave (matches previous section)
    'bottom'   => 'var(--bk-surface)',      // color the wave flows INTO below (matches next section)
    'animated' => true,                     // layered moving waves (set false for a single static curve)
    'flip'     => false,                    // mirror vertically (concave curve)
    'height'   => 'clamp(64px,9vw,120px)',
    'tint'     => false,                    // colourful olive/gold crests (signature moments) vs monochrome
])
@php
    // Unique id so multiple wave dividers on one page don't share a <path> reference.
    $wid = 'bkw-'.\Illuminate\Support\Str::random(6);
@endphp
<div {{ $attributes->class(['bkf-wavediv', 'is-anim' => $animated, 'is-flip' => $flip, 'is-tint' => $tint]) }}
     style="background:{{ $top }};--wf:{{ $bottom }};--wh:{{ $height }}" aria-hidden="true">
  <svg class="bkf-wave-svg" viewBox="0 24 150 28" preserveAspectRatio="none">
    <defs>
      <path id="{{ $wid }}" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"/>
    </defs>
    <g class="bkf-wave-layers">
      <use class="w1" href="#{{ $wid }}" x="48" y="0"/>
      <use class="w2" href="#{{ $wid }}" x="48" y="3"/>
      <use class="w3" href="#{{ $wid }}" x="48" y="5"/>
      <use class="w4" href="#{{ $wid }}" x="48" y="7"/>
    </g>
  </svg>
</div>
