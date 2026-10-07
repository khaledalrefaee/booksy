{{-- Venue-type chip (what kind of place is this?). Colour follows the type group, so the same
     colour means the same thing on the filter tabs and on every card. Expects: $c, $fgOf. --}}
@if($c->category)
  <span class="ft-chip is-g-{{ $fgOf($c) }}">{{ $c->category }}</span>
@endif
