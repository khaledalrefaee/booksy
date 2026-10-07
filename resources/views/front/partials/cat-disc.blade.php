{{-- Category artwork for a round/rect holder: its uploaded image, else its icon (white, big, on the
     category tone), else its initial. Expects: $c (from VenueTypes::categories). --}}
@if($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy" decoding="async">
@elseif($c['icon'])<span class="cbig" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>
@else<b>{{ mb_substr($c['name'], 0, 1) }}</b>@endif
