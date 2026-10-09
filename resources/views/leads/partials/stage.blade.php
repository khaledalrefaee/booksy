{{-- Ambient light behind every funnel page: no photos — the page is lit, not illustrated. --}}
<div class="gw-stage" aria-hidden="true">
  <span class="gw-base"></span>
  <span class="gw-aura"></span>
  <svg class="gw-arcs" viewBox="0 0 1000 1000" focusable="false">
    @foreach([150, 235, 330, 435, 550, 675] as $k => $r)
      <circle cx="500" cy="500" r="{{ $r }}" pathLength="1" style="--k:{{ $k }};--o:{{ round(.9 - $k * .13, 2) }}"/>
    @endforeach
  </svg>
  <span class="gw-grain"></span>
</div>
