{{-- Renders a run of venue cards. Shared by front/venues.blade.php and the
     AJAX "load more" response so markup never drifts between them. --}}
@foreach($cards as $c)
    @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
@endforeach
