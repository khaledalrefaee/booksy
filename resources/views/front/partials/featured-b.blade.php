{{-- Featured view B — "shelf": venues stand like tall spines; point at / tap / Tab to one and it opens
     wide while the others close into strips. ≤899px: vertical accordion.
     Expects: $items, $eager, plus the hub's $fpBadge, $fpPrice, $fgOf, $t, $isAr. --}}
<div class="fb" data-fb>
  <div class="fb-row" data-fb-row>
    @foreach($items as $i => $c)
      @php
        $pr = $fpPrice($c); $bd = $c->badge ?? null;
        $svcs = $c->services->take(3)->implode(' · ');
        $more = max(0, ($c->svc_count ?? 0) - $c->services->take(3)->count());
      @endphp
      <article class="fb-slat {{ $i === 0 ? 'is-on' : '' }} {{ $c->image ? 'has-img' : '' }}" data-fb-slat="{{ $i }}" style="--i:{{ $i }}" data-venue
               @if(!is_null($c->lat) && !is_null($c->lng)) data-lat="{{ $c->lat }}" data-lng="{{ $c->lng }}" @endif>
        <div class="fb-art">@include('front.partials.featured-art', ['c' => $c, 'eager' => $eager && $i === 0])</div>
        <div class="fb-shade"></div>

        {{-- closed state: a narrow spine --}}
        <button type="button" class="fb-tab" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-label="{{ $c->name }}{{ $c->category ? ' — '.$c->category : '' }}">
          @include('front.partials.featured-seal', ['c' => $c, 'size' => 'sm'])
          <span class="fb-tab-name">{{ $c->name }}</span>
          @if($c->category)<span class="fb-tab-type is-g-{{ $fgOf($c) }}">{{ $c->category }}</span>@endif
        </button>

        {{-- open state --}}
        <a class="fb-cover" href="{{ $c->url }}" aria-label="{{ $c->name }}" tabindex="-1"></a>
        <div class="fb-open">
          @if($bd && isset($fpBadge[$bd]))
            <span class="fp-badge fb-badge fp-badge-{{ $bd }}" style="--k:0"><x-icon name="{{ $fpBadge[$bd][0] }}" :size="13"/>{{ $isAr ? $fpBadge[$bd][1] : $fpBadge[$bd][2] }}</span>
          @endif
          <button type="button" class="fp-fav fb-fav" data-fav="{{ $c->id }}" aria-label="{{ $t('حفظ المكان', 'Save venue') }}">
            <x-icon name="heart" :size="20" class="heart-off"/><x-icon name="heart-fill" :size="20" class="heart-on"/>
          </button>

          <div class="fb-body">
            <div class="fb-who" style="--k:1">
              @include('front.partials.featured-seal', ['c' => $c, 'size' => 'md'])
              <div class="fb-who-txt">
                @include('front.partials.featured-chip', ['c' => $c])
                <p class="fp-sub">
                  @if(!empty($c->company) && $c->company !== $c->name)<span>{{ $c->company }}</span>@endif
                  @if($c->city)<span>{{ $c->city }}</span>@endif
                  @if($c->rating)<span class="bkf-tnum"><x-icon name="star-fill" :size="13"/>{{ number_format($c->rating, 1) }} ({{ $c->reviews }})</span>
                  @else<span>{{ $t('جديد على GlowRez', 'New on GlowRez') }}</span>@endif
                  @if(!is_null($c->is_open ?? null))<span class="fp-open {{ $c->is_open ? 'on' : 'off' }}"><i></i>{{ $c->is_open ? $t('مفتوح الآن', 'Open now') : $t('مغلق الآن', 'Closed') }}</span>@endif
                  <span class="fp-dist bkf-tnum"><x-icon name="navigation" :size="13"/><span class="dist-val"></span></span>
                </p>
              </div>
            </div>
            <h3 class="fb-name" style="--k:2">{{ $c->name }}</h3>
            @if($svcs)<p class="fp-svcs fb-svcs" style="--k:3">{{ $svcs }}@if($more) <span class="bkf-tnum">+{{ $more }}</span>@endif</p>@endif
            @if($c->has_offer ?? false)
              <div class="bkf-vp-offer fp-offer {{ ($c->offer_ends_ts ?? null) ? 'has-cd' : '' }}" style="--k:3"
                   @if($c->offer_ends_ts ?? null) data-offer-ends="{{ $c->offer_ends_ts }}" @endif>
                <span class="bkf-vp-offer-lbl"><x-icon name="tag" :size="13"/>{{ $t('عرض خاص', 'Special offer') }}</span>
                @if($c->offer_ends_ts ?? null)<span class="bkf-vp-offer-cd"><x-icon name="clock" :size="12"/><span class="cd bkf-tnum" aria-hidden="true"></span></span>@endif
              </div>
            @endif
            <div class="fp-foot" style="--k:4">
              <div class="fp-price">
                @if($pr)<small>{{ $t('يبدأ من', 'From') }}</small><b class="bkf-tnum">{{ $pr }}</b>
                @else<b class="is-text">{{ $t('أسعار متنوعة', 'Varied pricing') }}</b>@endif
              </div>
              <a href="{{ $c->url }}#book" class="bkf-btn bkf-btn-primary fp-book">{{ $t('احجز الآن', 'Book now') }}</a>
            </div>
          </div>
        </div>
      </article>
    @endforeach
  </div>
</div>
