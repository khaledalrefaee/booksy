{{-- Featured view A — "gallery": a leaf-shaped stage + a menu-style index (name ····· price).
     Hover / focus / autoplay swaps the stage. ≤1023px: the stage becomes a snap carousel and the
     index steps aside. Expects: $items, $eager, plus the hub's $fpBadge, $fpPrice, $fgOf, $t, $isAr. --}}
<div class="fp" data-fp>
  <div class="fp-grid">
    <div class="fp-side">
      <ul class="fp-index" role="list" data-fp-index>
        @foreach($items as $i => $c)
          @php $pr = $fpPrice($c); @endphp
          <li>
            <a class="fp-row {{ $i === 0 ? 'is-on' : '' }}" href="{{ $c->url }}" style="--i:{{ $i }}"
               data-fp-row="{{ $i }}" data-venue
               @if(!is_null($c->lat) && !is_null($c->lng)) data-lat="{{ $c->lat }}" data-lng="{{ $c->lng }}" @endif>
              <span class="fp-mark" aria-hidden="true"></span>
              <span class="fp-row-body">
                <span class="fp-row-line">
                  <span class="fp-row-name">{{ $c->name }}</span>
                  <span class="fp-leader" aria-hidden="true"></span>
                  <span class="fp-row-price bkf-tnum">{{ $pr ?: $t('أسعار متنوعة', 'Varied') }}</span>
                </span>
                <span class="fp-row-meta">
                  @include('front.partials.featured-chip', ['c' => $c])
                  @if(!empty($c->company) && $c->company !== $c->name)<span>{{ $c->company }}</span>@endif
                  @if($c->city)<span>{{ $c->city }}</span>@endif
                  @if($c->rating)<span class="bkf-tnum"><x-icon name="star-fill" :size="12"/>{{ number_format($c->rating, 1) }}</span>
                  @else<span>{{ $t('جديد', 'New') }}</span>@endif
                  @if(!is_null($c->is_open ?? null))<span class="fp-open {{ $c->is_open ? 'on' : 'off' }}"><i></i>{{ $c->is_open ? $t('مفتوح الآن', 'Open now') : $t('مغلق الآن', 'Closed') }}</span>@endif
                  <span class="fp-dist bkf-tnum"><x-icon name="navigation" :size="12"/><span class="dist-val"></span></span>
                </span>
              </span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>

    <div class="fp-stagebox" data-fp-stage>
      <div class="fp-stage" data-fp-panels>
        @foreach($items as $i => $c)
          @php
            $pr = $fpPrice($c); $bd = $c->badge ?? null;
            $svcs = $c->services->take(3)->implode(' · ');
            $more = max(0, ($c->svc_count ?? 0) - $c->services->take(3)->count());
          @endphp
          <article class="fp-panel {{ $i === 0 ? 'is-on' : '' }}" data-fp-panel="{{ $i }}">
            <div class="fp-art">@include('front.partials.featured-art', ['c' => $c, 'eager' => $eager && $i === 0, 'seal' => 'lg'])</div>
            <a class="fp-cover" href="{{ $c->url }}" aria-label="{{ $c->name }}" tabindex="-1"></a>

            @if($bd && isset($fpBadge[$bd]))
              <span class="fp-badge fp-badge-{{ $bd }}"><x-icon name="{{ $fpBadge[$bd][0] }}" :size="13"/>{{ $isAr ? $fpBadge[$bd][1] : $fpBadge[$bd][2] }}</span>
            @endif

            {{-- every word sits on a solid sheet, never on the photo --}}
            <div class="fp-info">
              @if($c->image)<div class="fp-logo" style="--k:0">@include('front.partials.featured-seal', ['c' => $c, 'size' => 'md'])</div>@endif
              <div class="fp-typerow" style="--k:0">
                @include('front.partials.featured-chip', ['c' => $c])
                <button type="button" class="fp-fav" data-fav="{{ $c->id }}" aria-label="{{ $t('حفظ المكان', 'Save venue') }}">
                  <x-icon name="heart" :size="20" class="heart-off"/><x-icon name="heart-fill" :size="20" class="heart-on"/>
                </button>
              </div>
              <h3 class="fp-name" style="--k:1">{{ $c->name }}</h3>
              <p class="fp-sub" style="--k:2">
                @if(!empty($c->company) && $c->company !== $c->name)<span>{{ $c->company }}</span>@endif
                @if($c->city)<span>{{ $c->city }}</span>@endif
                @if($c->rating)<span class="bkf-tnum"><x-icon name="star-fill" :size="13"/>{{ number_format($c->rating, 1) }} ({{ $c->reviews }})</span>
                @else<span>{{ $t('جديد على GlowRez', 'New on GlowRez') }}</span>@endif
                @if(!is_null($c->is_open ?? null))<span class="fp-open {{ $c->is_open ? 'on' : 'off' }}"><i></i>{{ $c->is_open ? $t('مفتوح الآن', 'Open now') : $t('مغلق الآن', 'Closed') }}</span>@endif
              </p>
              @if($svcs)<p class="fp-svcs" style="--k:3">{{ $svcs }}@if($more) <span class="bkf-tnum">+{{ $more }}</span>@endif</p>@endif
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
          </article>
        @endforeach
      </div>
    </div>
  </div>
</div>
