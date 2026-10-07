{{-- Featured view C — "cards": photo beside the details, never under them. Two columns on desktop
     (so six picks stay about two screens tall), one swipeable column on phones.
     Expects: $items, $eager, plus the hub's $fpBadge, $fpPrice, $fgOf, $t, $isAr. --}}
<div class="fc">
  <div class="fc-grid">
    @foreach($items as $i => $c)
      @php
        $pr = $fpPrice($c); $bd = $c->badge ?? null;
        $svcs = $c->services->take(3)->implode(' · ');
        $more = max(0, ($c->svc_count ?? 0) - $c->services->take(3)->count());
      @endphp
      <article class="fc-card" style="--i:{{ $i }}" data-venue
               @if(!is_null($c->lat) && !is_null($c->lng)) data-lat="{{ $c->lat }}" data-lng="{{ $c->lng }}" @endif>
        <a class="fc-cover" href="{{ $c->url }}" aria-label="{{ $c->name }}" tabindex="-1"></a>

        <div class="fc-media">
          @include('front.partials.featured-art', ['c' => $c, 'eager' => $eager && $i < 2, 'seal' => 'lg'])
          @if($bd && isset($fpBadge[$bd]))
            <span class="fc-badge fc-badge-{{ $bd }}"><x-icon name="{{ $fpBadge[$bd][0] }}" :size="13"/>{{ $isAr ? $fpBadge[$bd][1] : $fpBadge[$bd][2] }}</span>
          @endif
          @if($c->image)<span class="fc-logo">@include('front.partials.featured-seal', ['c' => $c, 'size' => 'xl'])</span>@endif
        </div>

        <div class="fc-body">
          <div class="fc-top">
            @include('front.partials.featured-chip', ['c' => $c])
            <button type="button" class="fc-fav" data-fav="{{ $c->id }}" aria-label="{{ $t('حفظ المكان', 'Save venue') }}">
              <x-icon name="heart" :size="19" class="heart-off"/><x-icon name="heart-fill" :size="19" class="heart-on"/>
            </button>
          </div>
          <h3 class="fc-name">{{ $c->name }}</h3>
          <p class="fc-meta">
            @if(!empty($c->company) && $c->company !== $c->name)<span>{{ $c->company }}</span>@endif
            @if($c->city)<span><x-icon name="map-pin" :size="14"/>{{ $c->city }}</span>@endif
            <span class="fc-dist bkf-tnum"><x-icon name="navigation" :size="13"/><span class="dist-val"></span></span>
          </p>
          <p class="fc-status">
            @if($c->rating)<span class="fc-rate bkf-tnum"><x-icon name="star-fill" :size="14"/>{{ number_format($c->rating, 1) }}<small>({{ $c->reviews }})</small></span>
            @else<span class="fc-new">{{ $t('جديد على GlowRez', 'New on GlowRez') }}</span>@endif
            @if(!is_null($c->is_open ?? null))<span class="fp-open {{ $c->is_open ? 'on' : 'off' }}"><i></i>{{ $c->is_open ? $t('مفتوح الآن', 'Open now') : $t('مغلق الآن', 'Closed') }}</span>@endif
          </p>
          @if($svcs)<p class="fc-svcs">{{ $svcs }}@if($more) <span class="bkf-tnum">+{{ $more }}</span>@endif</p>@endif
          @if($c->has_offer ?? false)
            <div class="bkf-vp-offer fc-offer {{ ($c->offer_ends_ts ?? null) ? 'has-cd' : '' }}"
                 @if($c->offer_ends_ts ?? null) data-offer-ends="{{ $c->offer_ends_ts }}" @endif>
              <span class="bkf-vp-offer-lbl"><x-icon name="tag" :size="13"/>{{ $t('عرض خاص', 'Special offer') }}</span>
              @if($c->offer_ends_ts ?? null)<span class="bkf-vp-offer-cd"><x-icon name="clock" :size="12"/><span class="cd bkf-tnum" aria-hidden="true"></span></span>@endif
            </div>
          @endif
          <div class="fc-foot">
            <div class="fc-price">
              @if($pr)<small>{{ $t('يبدأ من', 'From') }}</small><b class="bkf-tnum">{{ $pr }}</b>
              @else<b class="is-text">{{ $t('أسعار متنوعة', 'Varied pricing') }}</b>@endif
            </div>
            <a href="{{ $c->url }}#book" class="bkf-btn bkf-btn-primary fc-book">{{ $t('احجز الآن', 'Book now') }}</a>
          </div>
        </div>
      </article>
    @endforeach
  </div>
</div>
