{{-- Featured venue artwork. Expects: $c (card), optional $eager (bool), $seal ('lg'|null).
     With a photo → the photo. Without → a per-venue colour field (stable pseudo-random from
     the venue id/name, never a category icon) with the venue's own logo / initial on it. --}}
@php
  $faPal = [
      ['#4a6a34', '#9bbd4f', '#e3c46a'],   // olive → gold
      ['#b4502f', '#f09a52', '#f7d08a'],   // terracotta
      ['#7a4585', '#c97fc0', '#f2b3c6'],   // plum
      ['#1f7378', '#52bdb4', '#c4e6a2'],   // teal
      ['#b07a1c', '#eab34a', '#f8df9a'],   // ochre
      ['#b04062', '#ee8aa0', '#fbccb0'],   // rose
      ['#3f52a3', '#7f97e6', '#add9ee'],   // indigo
      ['#7a6244', '#c3a26e', '#ecd9a8'],   // sand
  ];
  $faP = $faPal[abs(crc32($c->id . '|' . $c->name)) % count($faPal)];
  $faL = mb_substr(trim((string) ($c->company ?: $c->name)), 0, 1);
@endphp
@if($c->image)
  <img class="fa-img" src="{{ $c->image }}" alt="" decoding="async" @if(empty($eager)) loading="lazy" @endif>
@else
  <div class="fa-ph" style="--a:{{ $faP[0] }};--b:{{ $faP[1] }};--c:{{ $faP[2] }}">
    <span class="fa-ph-l" aria-hidden="true">{{ $faL }}</span>
    @if(($seal ?? null) === 'lg')@include('front.partials.featured-seal', ['c' => $c, 'size' => 'lg'])@endif
  </div>
@endif
