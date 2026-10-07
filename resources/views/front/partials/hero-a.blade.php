{{-- ══════════════ 1 · HERO (auto-rotating: image + copy + alignment) ══════════════ --}}
@php
  $heroSlides = [
    [
      'img'   => 'magnific/Behind the scenes.jpg',
      'align' => 'start',
      'eb'    => $t('منصّة اكتشاف وحجز الجمال', 'Beauty discovery & booking'),
      't1'    => $t('اكتشف مكانك المثالي', 'Discover your place,'),
      't2'    => $t('واحجز موعدك بسهولة', 'book your moment'),
      'lead'  => $t('ابحث عن الصالونات ومراكز التجميل والسبا والحلاقين قربك، استكشف الخدمات والمواعيد المتاحة، وقارن التقييمات والأسعار، واحجز مباشرةً.', 'Find salons, beauty centers, spas and barbers near you, explore services and availability, compare ratings and prices, and book directly.'),
    ],
    [
      'img'   => 'magnific/salon-wide.jpg',
      'align' => 'center',
      'eb'    => $t('كل الأماكن على خريطة واحدة', 'Every venue, on one map'),
      't1'    => $t('استكشف الأماكن', 'Explore the places'),
      't2'    => $t('من حولك', 'around you'),
      'lead'  => $t('تصفّح المراكز على الخريطة، قارن التقييمات والأسعار والخدمات، واختر الأنسب لك في ثوانٍ.', 'Browse venues on the map, compare ratings, prices and services, and choose what fits you in seconds.'),
    ],
    [
      'img'   => 'magnific/7740630607013162.jpg',
      'align' => 'start',
      'eb'    => $t('وقتٌ لنفسك', 'Time for yourself'),
      't1'    => $t('سبا، بشرة، شعر ومكياج', 'Spa, skin, hair & makeup'),
      't2'    => $t('بين أيدٍ محترفة', 'by trusted pros'),
      'lead'  => $t('احجز مع أفضل الاختصاصيين بأوقات تناسبك وأسعار واضحة، بلا مكالمات ولا انتظار.', 'Book with top specialists at times that suit you and clear prices, no calls, no waiting.'),
    ],
  ];
  $s0 = $heroSlides[0];
  $heroSlidesJs = array_map(fn($s) => [
      'align' => $s['align'], 'eb' => $s['eb'], 't1' => $s['t1'], 't2' => $s['t2'], 'lead' => $s['lead'],
  ], $heroSlides);
@endphp
<section class="bkf-hero bkf-hero-immersive" id="top" data-hero-rotator>
  <div class="bkf-hero-bg" aria-hidden="true">
    @foreach($heroSlides as $i => $s)
      <img src="{{ asset($s['img']) }}" alt="" class="bkf-hero-slide {{ $i === 0 ? 'is-active' : '' }}"
           @if($i === 0) fetchpriority="high" @else loading="lazy" @endif data-slide="{{ $i }}">
    @endforeach
  </div>
  <div class="bkf-hero-scrim" aria-hidden="true"></div>

  {{-- side arrows (manual control, easy to spot) --}}
  <button type="button" class="bkf-hero-arrow is-prev" data-hero-prev aria-label="{{ $t('السابق', 'Previous') }}"><x-icon name="{{ $isAr ? 'chevron-right' : 'chevron-left' }}" :size="24"/></button>
  <button type="button" class="bkf-hero-arrow is-next" data-hero-next aria-label="{{ $t('التالي', 'Next') }}"><x-icon name="{{ $isAr ? 'chevron-left' : 'chevron-right' }}" :size="24"/></button>

  <div class="bkf-container-wide bkf-hero-i-inner {{ $s0['align'] === 'center' ? 'is-center' : '' }}" data-hero-inner>
    <div class="bkf-hero-text bkf-hero-i-text">
      <div class="bkf-hero-copy" data-hero-copy>
        <span class="bkf-eyebrow bkf-hero-eyebrow" data-hero-eyebrow>{{ $s0['eb'] }}</span>
        <h1 class="bkf-hero-title" data-hero-title>
          <span class="l1">{{ $s0['t1'] }}</span><br>
          <span class="em l2">{{ $s0['t2'] }}</span>
        </h1>
        <p class="bkf-hero-lead" data-hero-lead>{{ $s0['lead'] }}</p>
      </div>

      {{-- ── premium 3-field search / booking bar (constant) ── --}}
      <form class="bkf-hsearch" id="bkf-hero-search" action="{{ $venuesUrl }}" method="GET" role="search">
        <div class="bkf-hsearch-field">
          <x-icon name="search" :size="18"/>
          <input type="text" name="search" id="bkf-q" placeholder="{{ $t('صالون، سبا، خدمة…', 'Salon, spa, service…') }}" autocomplete="off" aria-label="{{ $t('ابحث عن خدمة أو مكان', 'Search service or venue') }}">
        </div>
        <span class="bkf-hsearch-div"></span>
        <div class="bkf-hsearch-field">
          <x-icon name="grid" :size="18"/>
          <select name="category" id="bkf-cat" aria-label="{{ $t('نوع المكان', 'Venue type') }}">
            <option value="">{{ $t('كل الأنواع', 'All types') }}</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->slug }}">{{ $isAr ? $cat->name_ar : $cat->name_en }}</option>
            @endforeach
          </select>
        </div>
        <span class="bkf-hsearch-div"></span>
        <div class="bkf-hsearch-field">
          <x-icon name="map-pin" :size="18"/>
          <input type="text" name="city" id="bkf-city" list="bkf-cities" placeholder="{{ $t('أين؟ المدينة', 'Where? City') }}" autocomplete="off" aria-label="{{ $t('اختر المدينة', 'Choose city') }}">
          <datalist id="bkf-cities">
            @foreach($cities as $city)<option value="{{ $city }}"></option>@endforeach
          </datalist>
        </div>
        <button type="submit" class="bkf-btn bkf-btn-primary bkf-hsearch-go" aria-label="{{ $t('ابحث', 'Search') }}">
          <x-icon name="search" :size="18"/><span>{{ $t('اكتشف', 'Discover') }}</span>
        </button>
      </form>

      <div class="bkf-hero-chips">
        <span class="bkf-hero-chips-lbl">{{ $t('رائج:', 'Popular:') }}</span>
        @foreach($categories->take(5) as $cat)
          <a href="{{ route('front.category', $cat->slug) }}" class="bkf-chip">{{ $isAr ? $cat->name_ar : $cat->name_en }}</a>
        @endforeach
      </div>


      {{-- phones: category quick-picks right under the search (thumb zone) --}}
      <nav class="hq" aria-label="{{ $t('تصنيفات سريعة', 'Quick categories') }}">
        <a href="#nearby" class="hq-item is-near" data-geo-trigger>
          <span class="hq-ic"><x-icon name="navigation" :size="28"/></span><span class="hq-n">{{ $t('قربي', 'Near me') }}</span>
        </a>
        <a href="{{ $venuesUrl }}" class="hq-item is-all">
          <span class="hq-ic"><x-icon name="grid" :size="28"/></span><span class="hq-n">{{ $t('الكل', 'All') }}</span>
        </a>
        @foreach($categories as $cat)
          @php $hqImg = $cat->image ? asset('storage/'.ltrim($cat->image, '/')) : null; @endphp
          <a href="{{ route('front.category', $cat->slug) }}" class="hq-item">
            <span class="hq-ic">@if($hqImg)<img src="{{ $hqImg }}" alt="" loading="lazy">@else<b style="background:{{ ['#4a6a34','#b4502f','#7a4585','#1f7378','#b07a1c','#b04062','#3f52a3','#7a6244'][abs(crc32($cat->slug)) % 8] }}">{{ mb_substr($isAr ? $cat->name_ar : $cat->name_en, 0, 1) }}</b>@endif</span>
            <span class="hq-n">{{ $isAr ? $cat->name_ar : $cat->name_en }}</span>
          </a>
        @endforeach
      </nav>

      <div class="bkf-hero-proof">
        <span class="bkf-hero-early"><span class="dot"></span>{{ $t('التسجيل المبكر مفتوح، الإطلاق الرسمي قريبًا', 'Early access is open, official launch soon') }}</span>
      </div>

      {{-- slide dots (manual) --}}
      <div class="bkf-hero-dots">
        @foreach($heroSlides as $i => $s)
          <button type="button" class="bkf-hero-dot {{ $i === 0 ? 'is-on' : '' }}" data-hero-dot="{{ $i }}" aria-label="{{ $t('شريحة', 'Slide') }} {{ $i + 1 }}"></button>
        @endforeach
      </div>
    </div>
  </div>
</section>



<script>window.BK_HERO_SLIDES = @json($heroSlidesJs);</script>
