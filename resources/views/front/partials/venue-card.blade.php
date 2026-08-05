@php
    /** Reusable venue card. Expects: $c (card object from FrontController), $currency, $isAr. */
    $isAr     = $isAr     ?? (app()->getLocale() === 'ar');
    $currency = $currency ?? ($isAr ? 'ل.س' : 'SYP');
    $badgeMap = [
        'hot' => ['icon' => 'flame',    'ar' => 'الأكثر حجزاً',  'en' => 'Most booked'],
        'top' => ['icon' => 'award',    'ar' => 'الأعلى تقييماً','en' => 'Top rated'],
        'rec' => ['icon' => 'sparkles', 'ar' => 'موصى به',       'en' => 'Recommended'],
        'new' => ['icon' => 'zap',      'ar' => 'جديد',          'en' => 'New'],
    ];
    $bd = $c->badge ?? null;

    // Category-aware placeholder — a distinct icon + tint per family so image-less
    // cards never look identical. Tints are muted, brand-adjacent tones (set in CSS).
    $catKey = function ($slug) {
        $slug = strtolower($slug ?? '');
        $rules = ['hair'=>'hair','salon'=>'hair','barber'=>'barber','men'=>'barber',
            'spa'=>'spa','massage'=>'spa','wellness'=>'spa','nail'=>'nail','lash'=>'lash',
            'brow'=>'lash','makeup'=>'beauty','beauty'=>'beauty','skin'=>'skin','laser'=>'skin',
            'clinic'=>'skin','dental'=>'skin'];
        foreach ($rules as $k => $v) { if (str_contains($slug, $k)) return $v; }
        return 'default';
    };
    $phMap = [
        'hair'=>'scissors','barber'=>'user','spa'=>'sparkles','nail'=>'heart',
        'lash'=>'star','beauty'=>'sparkles','skin'=>'zap','default'=>'grid',
    ];
    $ck   = $catKey($c->cat_slug ?? null);
    $phIc = $phMap[$ck] ?? 'grid';
    // Only surface the "New" text-pill when it isn't already the corner badge,
    // so a brand-new venue never shows "New" twice.
    $showNewPill = !$c->rating && $bd !== 'new';
@endphp
<article class="bkf-vp" data-venue
    data-id="{{ $c->id }}"
    data-rating="{{ $c->rating ?? 0 }}"
    data-reviews="{{ $c->reviews }}"
    data-bookings="{{ $c->bookings }}"
    data-score="{{ $c->score ?? 0 }}"
    data-created="{{ $c->created_ts }}"
    @if(!is_null($c->lat) && !is_null($c->lng)) data-lat="{{ $c->lat }}" data-lng="{{ $c->lng }}" @endif>

  <div class="bkf-vp-media">
    @if($c->image)
      <img src="{{ $c->image }}" alt="{{ $c->name }}" loading="lazy">
    @else
      <div class="bkf-vp-ph" data-cat="{{ $ck }}"></div>
    @endif

    @if($bd && isset($badgeMap[$bd]))
      <span class="bkf-vp-badge {{ $bd }}"><x-icon name="{{ $badgeMap[$bd]['icon'] }}" :size="13"/>{{ $isAr ? $badgeMap[$bd]['ar'] : $badgeMap[$bd]['en'] }}</span>
    @endif

    <button type="button" class="bkf-vp-fav" data-fav="{{ $c->id }}" aria-label="{{ $isAr ? 'حفظ المكان' : 'Save venue' }}">
      <x-icon name="heart" :size="18" class="heart-off"/>
      <x-icon name="heart-fill" :size="18" class="heart-on"/>
    </button>

    <div class="bkf-vp-logo">
      @if($c->logo)<img src="{{ $c->logo }}" alt="">@else<span class="fb">{{ mb_substr($c->company ?? 'B', 0, 1) }}</span>@endif
    </div>
  </div>

  <div class="bkf-vp-body">
    <div class="bkf-vp-top">
      <h3 class="bkf-vp-name">{{ $c->name }}</h3>
      @if($c->rating)
        <span class="bkf-vp-rate bkf-tnum"><x-icon name="star-fill" :size="14"/>{{ number_format($c->rating, 1) }}<span class="cnt">({{ $c->reviews }})</span></span>
      @elseif($showNewPill)
        <span class="bkf-vp-rate none">{{ $isAr ? 'جديد' : 'New' }}</span>
      @endif
    </div>

    <div class="bkf-vp-meta">
      @if($c->city)<x-icon name="map-pin" :size="14"/><span>{{ $c->city }}</span>@endif
      <span class="bkf-vp-dist bkf-tnum"><span class="bkf-vp-meta-dot"></span><x-icon name="navigation" :size="12"/><span class="dist-val"></span></span>
    </div>

    @if($c->services->isNotEmpty())
    <div class="bkf-vp-svcs">
      @foreach($c->services as $s)<span class="bkf-vp-svc">{{ $s }}</span>@endforeach
      @if(($c->svc_count ?? 0) > $c->services->count())<span class="bkf-vp-svc">+{{ $c->svc_count - $c->services->count() }}</span>@endif
    </div>
    @endif

    <div class="bkf-vp-foot">
      <div>
        @if($c->min_price)
          <div class="bkf-vp-price">{{ $isAr ? 'يبدأ من' : 'From' }}<b class="bkf-tnum">{{ number_format($c->min_price, 0) }} {{ $currency }}</b></div>
        @else
          <div class="bkf-vp-price"><b style="color:var(--bk-text)">{{ $isAr ? 'أسعار متنوعة' : 'Varied pricing' }}</b></div>
        @endif
        <span class="bkf-vp-slot">
          @if(($c->svc_count ?? 0) > 0)
            <x-icon name="clock" :size="13"/>{{ $c->svc_count }} {{ $isAr ? 'خدمة' : 'services' }}
          @else
            <x-icon name="clock" :size="13"/>{{ $isAr ? 'متاح للحجز' : 'Bookable' }}
          @endif
        </span>
      </div>
      <div class="bkf-vp-actions">
        <a href="{{ $c->url }}#book" class="bkf-btn bkf-btn-primary bkf-btn-sm bkf-vp-book">{{ $isAr ? 'احجز الآن' : 'Book' }}</a>
        <a href="{{ $c->url }}" class="bkf-btn bkf-btn-ghost bkf-btn-sm bkf-vp-details">{{ $isAr ? 'التفاصيل' : 'Details' }}</a>
      </div>
    </div>
  </div>
</article>
