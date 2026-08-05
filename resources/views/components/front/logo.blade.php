@props([
    'variant' => 'inline',   // inline · full  → bilingual lockup (GlowRez + غلوريز) · icon → mark only
])
{{-- Raster brand lockup from public/images (bg knocked-out + trimmed).
     Two theme variants swap automatically on <html data-bk-theme>. --}}
@once
<style>
  .bkf-logo{ display:block; width:auto; max-width:100%; }
  .bkf-logo.bkf-logo--dark{ display:none; }
  [data-bk-theme="dark"] .bkf-logo.bkf-logo--light{ display:none; }
  [data-bk-theme="dark"] .bkf-logo.bkf-logo--dark{ display:block; }
</style>
@endonce
@php
    $size = $variant === 'icon' ? 'bkf-logo-icon' : ($variant === 'full' ? 'bkf-logo-full' : 'bkf-logo-inline');
    // cache-bust on file change so overwritten logos never serve stale
    $ver = fn ($f) => asset('images/'.$f).'?v='.(@filemtime(public_path('images/'.$f)) ?: '1');
@endphp
@if($variant === 'icon')
  <img src="{{ $ver('logo-mark.png') }}" width="319" height="205"
       {{ $attributes->class('bkf-logo '.$size) }} alt="GlowRez">
@else
  <img src="{{ $ver('logo-light.png') }}" width="794" height="254"
       {{ $attributes->class('bkf-logo '.$size.' bkf-logo--light') }} alt="GlowRez غلوريز">
  <img src="{{ $ver('logo-dark.png') }}" width="444" height="162" aria-hidden="true"
       {{ $attributes->class('bkf-logo '.$size.' bkf-logo--dark') }} alt="">
@endif
