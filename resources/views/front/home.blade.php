@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn($ar, $en) => $isAr ? $ar : $en;

    // category slug → x-icon name
    $catIcon = function ($slug) {
        $slug = strtolower($slug ?? '');
        $map = ['hair'=>'scissors','salon'=>'scissors','barber'=>'user','spa'=>'sparkles','massage'=>'sparkles',
                'clinic'=>'shield','dental'=>'shield','skin'=>'sparkles','laser'=>'zap','beauty'=>'sparkles',
                'makeup'=>'sparkles','nail'=>'heart','lash'=>'star','brow'=>'star','gym'=>'zap',
                'tattoo'=>'award','wedding'=>'gift'];
        foreach ($map as $k => $v) { if (str_contains($slug, $k)) return $v; }
        return 'grid';
    };

    $reviews = [
        ['q_ar'=>'حجزت خلال دقيقة واحدة، ووجدت أقرب صالون لبيتي بسهولة. تجربة مريحة جدًا.','q_en'=>'Booked in a minute and found the closest salon to home. So smooth.','nm'=>$isAr?'لمى ح.':'Lama H.','rl'=>$isAr?'عميلة':'Client','in'=>$isAr?'ل':'L'],
        ['q_ar'=>'التقييمات والآراء ساعدتني أختار المكان المناسب، والأسعار واضحة قبل الحجز.','q_en'=>'Ratings and reviews helped me pick the right place — prices are clear upfront.','nm'=>$isAr?'رهف م.':'Rahaf M.','rl'=>$isAr?'عميلة':'Client','in'=>$isAr?'ر':'R'],
        ['q_ar'=>'أفضل بكثير من الاتصال والانتظار. كل شيء واضح وسريع من الهاتف.','q_en'=>'Far better than calling and waiting. Everything is clear and fast on mobile.','nm'=>$isAr?'كنان أ.':'Kenan A.','rl'=>$isAr?'عميل':'Client','in'=>$isAr?'ك':'K'],
    ];

    $faqs = [
        ['q_ar'=>'هل استخدام GlowRez مجاني للعملاء؟','q_en'=>'Is GlowRez free for customers?','a_ar'=>'نعم تمامًا. البحث عن الأماكن والاطلاع على الأسعار والتقييمات والحجز كلها مجانية بالكامل.','a_en'=>'Completely. Searching, viewing prices and reviews, and booking are all free.'],
        ['q_ar'=>'هل أحتاج حسابًا لأحجز؟','q_en'=>'Do I need an account to book?','a_ar'=>'تستطيع التصفّح بلا حساب. وعند تأكيد الحجز نتحقق من رقمك برمز سريع عبر الرسائل — بلا كلمات مرور.','a_en'=>'Browse without one. At booking we verify your number with a quick code — no passwords.'],
        ['q_ar'=>'كيف أجد الأقرب إليّ؟','q_en'=>'How do I find places near me?','a_ar'=>'اضغط «فعّل موقعي» على الخريطة أو في قسم «الأقرب إليك»، فنحسب المسافة ونرتّب النتائج من الأقرب.','a_en'=>'Tap “Use my location” on the map or in the “Near you” section — we sort results by distance from you.'],
        ['q_ar'=>'هل أستطيع تعديل موعدي أو إلغاؤه؟','q_en'=>'Can I reschedule or cancel?','a_ar'=>'نعم، من رابط التأكيد الذي يصلك عند الحجز يمكنك التعديل أو الإلغاء بسهولة.','a_en'=>'Yes — from the confirmation link you receive, you can reschedule or cancel in a tap.'],
        ['q_ar'=>'كيف أعرف جودة المكان قبل الحجز؟','q_en'=>'How do I judge quality before booking?','a_ar'=>'كل مكان يعرض تقييمًا وعدد المراجعات وآراء عملاء حقيقيين وصورًا وأهم الخدمات وأسعارها.','a_en'=>'Every venue shows its rating, review count, real client reviews, photos, top services and prices.'],
    ];
@endphp

<x-front.layout
    variant="customer"
    :mapFab="false"
    bodyClass="bkf-has-hero"
    :title="$t('GlowRez — اكتشف واحجز في أفضل مراكز الجمال والعناية قربك', 'GlowRez — Discover & Book Top Beauty & Wellness Venues Near You')"
    :keywords="$t('حجز صالون, حجز مركز تجميل, حجز حلاقة, سبا, أظافر, عيادات تجميل, مواعيد الجمال, خريطة الأماكن, سوريا, دمشق', 'salon booking, beauty appointment, barber booking, spa, nails, beauty clinics, wellness map, Syria, Damascus')"
    :description="$t('اكتشف واحجز في أفضل أماكن الجمال والعناية قربك: شعر، سبا، تجميل، حلاقة، أظافر وعيادات. استكشف على الخريطة، قارن التقييمات والأسعار، واحجز فورًا من هاتفك عبر GlowRez.', 'Discover and book the best beauty & wellness venues near you — explore them on a live map, compare ratings and prices, and book instantly from your phone with GlowRez.')">

{{-- ══════════════ 1 · HERO (auto-rotating: image + copy + alignment) ══════════════ --}}
@php
  $heroSlides = [
    [
      'img'   => 'magnific/Behind the scenes.jpg',
      'align' => 'start',
      'eb'    => $t('منصّة اكتشاف وحجز الجمال', 'Beauty discovery & booking'),
      't1'    => $t('اكتشف مكانك المثالي', 'Discover your place,'),
      't2'    => $t('واحجز موعدك بسهولة', 'book your moment'),
      'lead'  => $t('ابحث عن الصالونات ومراكز التجميل والسبا والحلاقين قربك — استكشف الخدمات والمواعيد المتاحة، وقارن التقييمات والأسعار، واحجز مباشرةً.', 'Find salons, beauty centers, spas and barbers near you — explore services and availability, compare ratings and prices, and book directly.'),
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
      'lead'  => $t('احجز مع أفضل الاختصاصيين بأوقات تناسبك وأسعار واضحة — بلا مكالمات ولا انتظار.', 'Book with top specialists at times that suit you and clear prices — no calls, no waiting.'),
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
        <button type="submit" class="bkf-btn bkf-btn-primary bkf-hsearch-go">
          <x-icon name="search" :size="18"/><span>{{ $t('اكتشف', 'Discover') }}</span>
        </button>
      </form>

      <div class="bkf-hero-chips">
        <span class="bkf-hero-chips-lbl">{{ $t('رائج:', 'Popular:') }}</span>
        @foreach($categories->take(5) as $cat)
          <a href="{{ route('front.category', $cat->slug) }}" class="bkf-chip"><x-icon name="{{ $catIcon($cat->slug) }}" :size="14"/>{{ $isAr ? $cat->name_ar : $cat->name_en }}</a>
        @endforeach
      </div>

      <div class="bkf-hero-proof">
        <span class="bkf-hero-early"><span class="dot"></span>{{ $t('التسجيل المبكر مفتوح — الإطلاق الرسمي قريبًا', 'Early access is open — official launch soon') }}</span>
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

{{-- ══════════════ 2 · VALUE BAR (qualitative — no counts pre-launch) ══════════════ --}}
@php
  $trust = [
    ['ic'=>'calendar',     'ar'=>'حجز فوري',       'en'=>'Instant booking'],
    ['ic'=>'star-fill',    'ar'=>'تقييمات موثوقة', 'en'=>'Real reviews'],
    ['ic'=>'tag',          'ar'=>'أسعار واضحة',    'en'=>'Clear pricing'],
    ['ic'=>'check-circle', 'ar'=>'بلا مكالمات',    'en'=>'No phone calls'],
  ];
@endphp
<div class="bkf-trust">
  <div class="bkf-container-wide bkf-trust-inner">
    <div class="bkf-trust-track">
      @for($rep = 0; $rep < 2; $rep++)
        <div class="bkf-trust-group" @if($rep) aria-hidden="true" @endif>
          @foreach($trust as $i => $tr)
            @if($i > 0)<span class="bkf-trust-sep"></span>@endif
            <div class="bkf-trust-item is-qual">
              <span class="bkf-trust-ic"><x-icon name="{{ $tr['ic'] }}" :size="22"/></span>
              <span class="bkf-trust-n">{{ $t($tr['ar'], $tr['en']) }}</span>
            </div>
          @endforeach
        </div>
      @endfor
    </div>
  </div>
</div>

{{-- ══════════════ 3 · CATEGORIES ══════════════ --}}
<section class="bkf-section" id="categories">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <span class="bkf-eyebrow">{{ $t('تصفّح حسب النوع', 'Browse by type') }}</span>
        <h2 class="bkf-title bkf-mt-0">{{ $t('ماذا تريد', 'What are you') }} <span class="em">{{ $t('اليوم؟', 'looking for?') }}</span></h2>
      </div>
    </div>
    @if($categories->isNotEmpty())
    <div class="bkf-rail bkf-reveal">
      @foreach($categories as $cat)
        <a href="{{ route('front.category', $cat->slug) }}" class="bkf-cat-pill">
          <span class="bkf-cat-pill-ic"><x-icon name="{{ $catIcon($cat->slug) }}" :size="18"/></span>
          <span>
            <span class="n">{{ $isAr ? $cat->name_ar : $cat->name_en }}</span>
            @if($cat->companies_count)<span class="c">{{ $cat->companies_count }} {{ $t('مكان', 'places') }}</span>@endif
          </span>
        </a>
      @endforeach
    </div>
    @endif
  </div>
</section>

{{-- ══════════════ 4 · FEATURED (curated grid → hands off to /venues) ══════════════ --}}
<section class="bkf-section" id="discover" style="padding-top:0">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <span class="bkf-eyebrow">{{ $t('مختارة بعناية', 'Handpicked') }}</span>
        <h2 class="bkf-title bkf-mt-0">{{ $t('أماكن', 'Featured') }} <span class="em">{{ $t('مميّزة', 'venues') }}</span></h2>
      </div>
      <a href="{{ $venuesUrl }}" class="bkf-seeall" data-venues-cta>{{ $t('عرض جميع الأماكن', 'View all venues') }}<x-icon name="arrow-right" :size="16"/></a>
    </div>

    <div class="bkf-railwrap" data-railwrap>
      <button type="button" class="bkf-rail-arrow is-prev" data-rail-prev aria-label="{{ $t('السابق', 'Previous') }}">
        <x-icon name="chevron-right" :size="22"/>
      </button>
      <div class="bkf-rail is-compact" id="bkf-grid" data-rail>
        @foreach($featured as $c)
          @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
        @endforeach
      </div>
      <button type="button" class="bkf-rail-arrow is-next" data-rail-next aria-label="{{ $t('التالي', 'Next') }}">
        <x-icon name="chevron-left" :size="22"/>
      </button>
    </div>

    <div class="bkf-center" style="margin-top:44px">
      <a href="{{ $venuesUrl }}" class="bkf-btn bkf-btn-soft bkf-btn-lg" data-venues-cta>
        {{ $t('عرض جميع الأماكن', 'View all venues') }}<x-icon name="arrow-right" :size="18"/>
      </a>
    </div>
  </div>
</section>

{{-- ══════════════ 5 · MAP DISCOVERY — core discovery experience ══════════════
     Sits on the page background; the map's own bordered card gives the separation
     (no surface band / waves → seamless colour harmony in light + dark). --}}
<section class="bkf-section bkf-mapsec" id="explore" style="padding-top:clamp(24px,4vw,56px)">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <span class="bkf-eyebrow">{{ $t('على الخريطة', 'On the map') }}</span>
        <h2 class="bkf-title bkf-mt-0">{{ $t('اكتشف الأماكن', 'Discover places') }} <span class="em">{{ $t('من حولك', 'around you') }}</span></h2>
        <p class="bkf-lead" style="margin-top:10px">{{ $t('تصفّح المراكز على الخريطة، اضغط أي علامة لرؤية التفاصيل، واحجز مباشرةً.', 'Browse venues on the map, tap any pin for details, and book right away.') }}</p>
      </div>
    </div>

    {{-- category filter chips (client-side) --}}
    <div class="bkf-mapfilters bkf-reveal" role="tablist" aria-label="{{ $t('تصفية حسب النوع', 'Filter by type') }}">
      <button type="button" class="bkf-mapchip is-on" data-mapfilter="">{{ $t('الكل', 'All') }}</button>
      @foreach($categories->take(8) as $cat)
        <button type="button" class="bkf-mapchip" data-mapfilter="{{ $cat->slug }}">
          <x-icon name="{{ $catIcon($cat->slug) }}" :size="14"/>{{ $isAr ? $cat->name_ar : $cat->name_en }}
        </button>
      @endforeach
      <button type="button" class="bkf-mapchip is-geo" data-map-locate><x-icon name="navigation" :size="14"/>{{ $t('قربي', 'Near me') }}</button>
    </div>

    <div class="bkf-mapdisco bkf-reveal" data-mapdisco>
      {{-- venue list (desktop: side column · mobile: bottom horizontal rail) --}}
      <div class="bkf-mapdisco-list" data-map-list>
        <div class="bkf-mapdisco-count" data-map-count></div>
        <div class="bkf-mapdisco-scroll" data-map-scroll>
          {{-- skeletons until data loads --}}
          @for($i=0;$i<4;$i++)<div class="bkf-mapcard is-skeleton"><div class="sk-img"></div><div class="sk-body"><span class="sk-l"></span><span class="sk-l sm"></span></div></div>@endfor
        </div>
      </div>
      {{-- map canvas --}}
      <div class="bkf-mapdisco-canvas">
        <div id="bkf-disco-map" data-disco-map></div>
        <div class="bkf-mapdisco-loading" data-map-loading>
          <span class="bkf-spin"></span>{{ $t('جارٍ تحميل الخريطة…', 'Loading map…') }}
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ══════════════ 6 · HOW IT WORKS — 4-step customer journey ══════════════ --}}
<section class="bkf-section bkf-3d" id="how" style="padding-top:clamp(20px,3vw,44px);background:var(--bk-bg)">
  <div class="bkf-container">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('بسيط وسريع', 'Simple & fast') }}</span>
      <h2 class="bkf-title bkf-mt-0">{{ $t('احجز في', 'Book in') }} <span class="em">{{ $t('دقائق', 'minutes') }}</span></h2>
      <p class="bkf-lead">{{ $t('من البحث إلى تأكيد الموعد في أربع خطوات فقط.', 'From search to a confirmed appointment in just four steps.') }}</p>
    </div>
    <div class="bkf-steps bkf-steps-4" style="margin-top:52px">
      <div class="bkf-step bkf-reveal">
        <div class="bkf-step-ic"><span class="bkf-step-n">01</span><x-icon name="search" :size="28"/></div>
        <h3>{{ $t('ابحث عن المكان', 'Find a place') }}</h3>
        <p>{{ $t('ابحث أو استكشف على الخريطة، أو دع موقعك يعرض الأقرب إليك.', 'Search or explore the map, or let your location surface what’s nearest.') }}</p>
      </div>
      <div class="bkf-step bkf-reveal bkf-reveal-d1">
        <div class="bkf-step-ic"><span class="bkf-step-n">02</span><x-icon name="sparkles" :size="28"/></div>
        <h3>{{ $t('اختر الخدمة', 'Choose a service') }}</h3>
        <p>{{ $t('قارن الخدمات والأسعار والتقييمات، واختر ما يناسبك.', 'Compare services, prices and reviews, then pick what suits you.') }}</p>
      </div>
      <div class="bkf-step bkf-reveal bkf-reveal-d2">
        <div class="bkf-step-ic"><span class="bkf-step-n">03</span><x-icon name="calendar" :size="28"/></div>
        <h3>{{ $t('اختر الموعد', 'Pick a time') }}</h3>
        <p>{{ $t('اطّلع على الأوقات المتاحة واختر ما يلائم جدولك.', 'See available slots and choose the one that fits your schedule.') }}</p>
      </div>
      <div class="bkf-step bkf-reveal bkf-reveal-d3">
        <div class="bkf-step-ic"><span class="bkf-step-n">04</span><x-icon name="check-circle" :size="28"/></div>
        <h3>{{ $t('أكّد الحجز', 'Confirm') }}</h3>
        <p>{{ $t('أكّد بنقرة واحدة، ويصلك تذكير قبل موعدك. بلا مكالمات.', 'Confirm in one tap and get a reminder before your visit. No calls.') }}</p>
      </div>
    </div>
  </div>
</section>

{{-- ══════════════ 7 · TOP RATED (rail) — proof of quality ══════════════ --}}
@if($topRated->isNotEmpty())
<section class="bkf-section" style="padding-top:clamp(16px,3vw,40px)">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <span class="bkf-eyebrow">{{ $t('الأفضل ثقةً', 'Most trusted') }}</span>
        <h2 class="bkf-title bkf-mt-0">{{ $t('الأعلى', 'Top') }} <span class="em">{{ $t('تقييماً', 'rated') }}</span></h2>
      </div>
      <a href="{{ $venuesUrl }}?sort=rating" class="bkf-seeall">{{ $t('عرض الكل', 'See all') }}<x-icon name="arrow-right" :size="16"/></a>
    </div>
    <div class="bkf-rail">
      @foreach($topRated as $c)
        @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ══════════════ 8 · NEAR YOU (gated rail) ══════════════ --}}
<section class="bkf-section" id="nearby" style="padding-top:0">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <span class="bkf-eyebrow">{{ $t('مصمّم لك', 'Made for you') }}</span>
        <h2 class="bkf-title bkf-mt-0">{{ $t('الأقرب', 'Nearest') }} <span class="em">{{ $t('إليك', 'to you') }}</span></h2>
      </div>
    </div>
    <div class="bkf-geo-gate bkf-reveal" id="bkf-geo-gate">
      <span class="ic"><x-icon name="navigation" :size="26"/></span>
      <div>
        <b>{{ $t('اعرف الأقرب إليك', 'Find what’s closest') }}</b>
        <div class="bkf-geo-note">{{ $t('فعّل موقعك لنرتّب الأماكن حسب المسافة ونعرض بُعد كل مكان عنك.', 'Enable your location to sort venues by distance and show how far each one is.') }}</div>
      </div>
      <button type="button" class="bkf-btn bkf-btn-primary" data-nearby-enable><x-icon name="navigation" :size="18"/>{{ $t('فعّل موقعي', 'Use my location') }}</button>
    </div>
    <div class="bkf-rail" data-nearby-rail style="display:none">
      @foreach($nearby as $c)
        @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
      @endforeach
    </div>
  </div>
</section>

{{-- ══════════════ 9 · REVIEWS — social proof ══════════════ --}}
<section class="bkf-section bkf-3d">
  <div class="bkf-container-wide">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('آراء عملائنا', 'What clients say') }}</span>
      <h2 class="bkf-title bkf-mt-0">{{ $t('تجارب', 'Real') }} <span class="em">{{ $t('حقيقية', 'experiences') }}</span></h2>
    </div>
    <div class="bkf-grid bkf-grid-3" style="margin-top:48px">
      @foreach($reviews as $r)
      <div class="bkf-review bkf-reveal">
        <span class="bkf-review-q"><x-icon name="quote" :size="32"/></span>
        <p class="bkf-review-text">{{ $isAr ? $r['q_ar'] : $r['q_en'] }}</p>
        <div class="bkf-review-stars">@for($i=0;$i<5;$i++)<x-icon name="star-fill" :size="15"/>@endfor</div>
        <div class="bkf-review-author">
          <span class="bkf-review-av">{{ $r['in'] }}</span>
          <span><span class="bkf-review-nm" style="display:block">{{ $r['nm'] }}</span><span class="bkf-review-rl">{{ $r['rl'] }}</span></span>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- wave: cream page → surface FAQ band --}}
<x-front.wave top="var(--bk-bg)" bottom="var(--bk-surface)" tint />

{{-- ══════════════ 10 · FAQ ══════════════ --}}
<section class="bkf-section" id="faq" style="background:var(--bk-surface);padding-top:clamp(24px,4vw,56px)">
  <div class="bkf-container">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('أسئلة شائعة', 'FAQ') }}</span>
      <h2 class="bkf-title bkf-mt-0">{{ $t('كل ما', 'Everything') }} <span class="em">{{ $t('تريد معرفته', 'you need to know') }}</span></h2>
    </div>
    <div class="bkf-faq" style="margin-top:44px">
      @foreach($faqs as $i => $f)
      <div class="bkf-faq-item {{ $i === 0 ? 'is-open' : '' }}">
        <button type="button" class="bkf-faq-q" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
          <span>{{ $isAr ? $f['q_ar'] : $f['q_en'] }}</span>
          <x-icon name="chevron-down" :size="20" class="chev"/>
        </button>
        <div class="bkf-faq-a" @if($i === 0) style="max-height:400px" @endif>
          <div class="bkf-faq-a-in">{{ $isAr ? $f['a_ar'] : $f['a_en'] }}</div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ══════════════ 11 · FINAL CTA ══════════════ --}}
<section class="bkf-section" style="padding-block:clamp(32px,5vw,64px)">
  <div class="bkf-container-wide">
    <div class="bkf-cta bkf-reveal">
      <span class="bkf-cta-blob a"></span><span class="bkf-cta-blob b"></span>
      <div class="bkf-cta-grid">
        <div>
          <span class="eyebrow">{{ $t('للعملاء', 'For customers') }}</span>
          <h2 style="font-size:var(--bk-fs-h2);margin-top:8px">{{ $t('جاهز لحجز موعدك؟', 'Ready to book?') }}</h2>
          <p>{{ $t('اكتشف أفضل الأماكن قربك واحجز في ثوانٍ.', 'Discover the best places near you and book in seconds.') }}</p>
          <a href="#explore" class="bkf-btn bkf-btn-primary bkf-btn-lg" style="margin-top:20px"><x-icon name="map-pin" :size="18"/>{{ $t('اكتشف على الخريطة', 'Explore the map') }}</a>
        </div>
        <div class="bkf-cta-div"></div>
        <div>
          <span class="eyebrow">{{ $t('لأصحاب الأعمال', 'For business') }}</span>
          <h3 style="font-size:var(--bk-fs-h3);margin-top:8px">{{ $t('تملك نشاطاً في مجال الجمال والعناية؟', 'Own a beauty or wellness business?') }}</h3>
          <p>{{ $t('انضم إلى GlowRez وابدأ باستقبال الحجوزات مجانًا اليوم.', 'Join GlowRez and start taking bookings for free today.') }}</p>
          <a href="{{ route('front.business') }}" class="bkf-btn bkf-btn-soft bkf-btn-lg" style="margin-top:20px"><x-icon name="building" :size="18"/>{{ $t('اعرف المزيد', 'Learn more') }}</a>
        </div>
      </div>
    </div>
  </div>
</section>

<x-slot:scripts>
<script>
window.BK_MAP = {
  url:      @json(route('front.map.branches')),
  css:      @json(asset('vendor/leaflet/leaflet.css')),
  js:       @json(asset('vendor/leaflet/leaflet.js')),
  currency: @json($currency),
  ar:       @json($isAr)
};
window.BK_HERO_SLIDES = @json($heroSlidesJs);
</script>

{{-- ── Hero slider: MANUAL control only (dots + arrows) · no auto-advance, no motion ── --}}
<script>
(function () {
  'use strict';
  var root = document.querySelector('[data-hero-rotator]');
  var slides = window.BK_HERO_SLIDES || [];
  if (!root || slides.length < 2) return;

  var imgs  = root.querySelectorAll('.bkf-hero-slide');
  var inner = root.querySelector('[data-hero-inner]');
  var copy  = root.querySelector('[data-hero-copy]');
  var eb    = root.querySelector('[data-hero-eyebrow]');
  var ttl   = root.querySelector('[data-hero-title]');
  var lead  = root.querySelector('[data-hero-lead]');
  var dots  = root.querySelectorAll('[data-hero-dot]');
  var prev  = root.querySelector('[data-hero-prev]');
  var next  = root.querySelector('[data-hero-next]');
  var i = 0;

  function esc(s){ var d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }

  function paint(n) {
    i = (n + slides.length) % slides.length;
    var s = slides[i];
    imgs.forEach(function (im, ix) { im.classList.toggle('is-active', ix === i); });
    dots.forEach(function (d, ix) { d.classList.toggle('is-on', ix === i); });
    copy.classList.add('is-swapping');
    setTimeout(function () {
      eb.textContent  = s.eb;
      ttl.innerHTML   = '<span class="l1">' + esc(s.t1) + '</span><br><span class="em l2">' + esc(s.t2) + '</span>';
      lead.textContent = s.lead;
      inner.classList.toggle('is-center', s.align === 'center');
      root.classList.toggle('align-center', s.align === 'center');
      copy.classList.remove('is-swapping');
    }, 320);
  }

  dots.forEach(function (d) { d.addEventListener('click', function () { paint(+d.getAttribute('data-hero-dot')); }); });
  if (prev) prev.addEventListener('click', function () { paint(i - 1); });
  if (next) next.addEventListener('click', function () { paint(i + 1); });
})();
</script>
<script>
(function () {
  'use strict';
  var AR = window.BK_MAP.ar;
  var km = function (d) { return d < 1 ? Math.round(d * 1000) + (AR ? ' م' : ' m') : d.toFixed(1) + (AR ? ' كم' : ' km'); };

  /* ── toast ── */
  function toast(msg) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:var(--bk-text);color:var(--bk-bg);padding:10px 18px;border-radius:999px;font-family:var(--bk-font-ui);font-size:.85rem;z-index:1300;box-shadow:var(--bk-shadow-lg);opacity:0;transition:opacity .3s';
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.style.opacity = '1'; });
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 2600);
  }

  /* ── geolocation + distance (shared across page) ── */
  var userPos = null, geoBusy = false, geoCbs = [];
  function haversine(la1, lo1, la2, lo2) {
    var R = 6371, p = Math.PI / 180;
    var dLa = (la2 - la1) * p, dLo = (lo2 - lo1) * p;
    var s = Math.sin(dLa / 2) ** 2 + Math.cos(la1 * p) * Math.cos(la2 * p) * Math.sin(dLo / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
  }
  function applyDistances(scope) {
    if (!userPos) return;
    (scope || document).querySelectorAll('[data-venue][data-lat]').forEach(function (card) {
      var d = haversine(userPos.lat, userPos.lng, +card.dataset.lat, +card.dataset.lng);
      card.dataset.distance = d.toFixed(3);
      card.setAttribute('data-has-distance', '');
      var v = card.querySelector('.dist-val'); if (v) v.textContent = km(d);
    });
  }
  function sortCards(container, key, dir) {
    if (!container) return;
    var cards = [].slice.call(container.querySelectorAll('[data-venue]'));
    cards.sort(function (x, y) {
      var a = parseFloat(x.dataset[key] || 0), b = parseFloat(y.dataset[key] || 0);
      return dir === 'asc' ? a - b : b - a;
    });
    cards.forEach(function (c) { container.appendChild(c); });
  }
  function requestGeo(cb) {
    if (userPos) { cb && cb(true); return; }
    if (!('geolocation' in navigator)) { toast(AR ? 'الموقع غير مدعوم على جهازك' : 'Location not supported'); cb && cb(false); return; }
    if (cb) geoCbs.push(cb);
    if (geoBusy) return; geoBusy = true;
    navigator.geolocation.getCurrentPosition(function (p) {
      geoBusy = false; userPos = { lat: p.coords.latitude, lng: p.coords.longitude };
      applyDistances(document);
      toast(AR ? 'رُتّبت النتائج حسب موقعك' : 'Results sorted by your location');
      geoCbs.splice(0).forEach(function (f) { f(true); });
    }, function () {
      geoBusy = false; toast(AR ? 'تعذّر الوصول إلى موقعك' : 'Couldn’t access your location');
      geoCbs.splice(0).forEach(function (f) { f(false); });
    }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 });
  }
  window.__bkRequestGeo = requestGeo;
  window.__bkUserPos = function () { return userPos; };
  window.__bkKm = km;

  /* ── featured grid text index + hero hand-off ── */
  var grid = document.getElementById('bkf-grid');
  var heroForm = document.getElementById('bkf-hero-search');
  var qInput = document.getElementById('bkf-q'), cityInput = document.getElementById('bkf-city');
  // heroForm submits natively to /venues?search=&category=&city=

  /* ── nearby gated rail ── */
  function revealNearby() {
    var sec = document.getElementById('nearby'); if (!sec) return;
    var gate = document.getElementById('bkf-geo-gate'), rail = sec.querySelector('[data-nearby-rail]');
    requestGeo(function (ok) {
      if (!ok) return;
      applyDistances(sec);
      if (rail) { sortCards(rail, 'distance', 'asc'); rail.style.display = ''; }
      if (gate) gate.style.display = 'none';
      if (window.bkRefreshRails) window.bkRefreshRails();
    });
  }
  var gateBtn = document.querySelector('[data-nearby-enable]');
  if (gateBtn) gateBtn.addEventListener('click', revealNearby);
  document.querySelectorAll('[data-geo-trigger]').forEach(function (el) { el.addEventListener('click', function () { setTimeout(revealNearby, 300); }); });

  /* ── FAQ accordion ── */
  document.querySelectorAll('.bkf-faq-q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.closest('.bkf-faq-item'), a = item.querySelector('.bkf-faq-a');
      var open = item.classList.toggle('is-open');
      q.setAttribute('aria-expanded', open);
      a.style.maxHeight = open ? a.scrollHeight + 'px' : '0';
    });
  });
})();
</script>

{{-- ── Map Discovery: lazy leaflet + branded popups + two-way list linking ── --}}
<script>
(function () {
  'use strict';
  var CFG = window.BK_MAP, AR = CFG.ar, CUR = CFG.currency;
  var section = document.getElementById('explore');
  var mapEl   = document.querySelector('[data-disco-map]');
  var listEl  = document.querySelector('[data-map-scroll]');
  var countEl = document.querySelector('[data-map-count]');
  var loadEl  = document.querySelector('[data-map-loading]');
  var filterBtns = document.querySelectorAll('[data-mapfilter]');
  var locateBtn  = document.querySelector('[data-map-locate]');
  if (!mapEl || !listEl) return;

  var DAMASCUS = [33.5138, 36.2765];
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
  var catFamily = function (slug) {
    slug = (slug || '').toLowerCase();
    var r = { hair:'hair', salon:'hair', barber:'barber', men:'barber', spa:'spa', massage:'spa', wellness:'spa',
              nail:'nail', lash:'lash', brow:'lash', makeup:'beauty', beauty:'beauty', skin:'skin', laser:'skin',
              clinic:'skin', dental:'skin' };
    for (var k in r) { if (slug.indexOf(k) > -1) return r[k]; }
    return 'default';
  };

  var map, L, data = [], markers = {}, cards = {}, activeId = null, activeFilter = '', inited = false, loaded = false;

  function loadLeaflet(cb) {
    if (window.L) { cb(window.L); return; }
    if (!document.querySelector('link[data-leaflet]')) {
      var l = document.createElement('link');
      l.rel = 'stylesheet'; l.href = CFG.css; l.setAttribute('data-leaflet', '');
      document.head.appendChild(l);
    }
    var ex = document.querySelector('script[data-leaflet]');
    if (ex) { ex.addEventListener('load', function () { cb(window.L); }); return; }
    var s = document.createElement('script');
    s.src = CFG.js; s.defer = true; s.setAttribute('data-leaflet', '');
    s.addEventListener('load', function () { cb(window.L); });
    document.body.appendChild(s);
  }

  function pinIcon(fam, active) {
    return L.divIcon({
      className: 'bkf-pin-wrap',
      html: '<span class="bkf-pin' + (active ? ' is-active' : '') + '" data-fam="' + fam + '"></span>',
      iconSize: [30, 38], iconAnchor: [15, 36], popupAnchor: [0, -34]
    });
  }

  function distText(b) {
    var up = window.__bkUserPos && window.__bkUserPos();
    if (!up || b.lat == null) return '';
    var R = 6371, p = Math.PI / 180;
    var dLa = (b.lat - up.lat) * p, dLo = (b.lng - up.lng) * p;
    var s = Math.sin(dLa / 2) ** 2 + Math.cos(up.lat * p) * Math.cos(b.lat * p) * Math.sin(dLo / 2) ** 2;
    var d = R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
    return window.__bkKm(d);
  }

  function popupHTML(b) {
    var fam = catFamily(b.cat_slug);
    var img = b.image
      ? '<div class="bkf-pop-media"><img src="' + esc(b.image) + '" alt="' + esc(b.name) + '" loading="lazy"></div>'
      : '<div class="bkf-pop-media bkf-pop-ph" data-fam="' + fam + '"></div>';
    var rating = b.avg_rating
      ? '<span class="bkf-pop-rate">★ ' + b.avg_rating + ' <i>(' + b.review_count + ')</i></span>'
      : '<span class="bkf-pop-rate none">' + (AR ? 'جديد' : 'New') + '</span>';
    var open = b.open_now === true ? '<span class="bkf-pop-open is-open">' + (AR ? 'مفتوح الآن' : 'Open now') + '</span>'
             : b.open_now === false ? '<span class="bkf-pop-open is-closed">' + (AR ? 'مغلق الآن' : 'Closed') + '</span>' : '';
    var dt = distText(b);
    var meta = [];
    if (b.category) meta.push(esc(b.category));
    if (b.city) meta.push(esc(b.city));
    if (dt) meta.push(dt);
    var svcs = (b.services || []).map(function (s) { return '<span class="bkf-pop-svc">' + esc(s) + '</span>'; }).join('');
    var price = b.min_price
      ? '<span class="bkf-pop-price">' + (AR ? 'يبدأ من ' : 'From ') + '<b>' + Math.round(b.min_price).toLocaleString() + ' ' + CUR + '</b></span>'
      : '';
    return '<div class="bkf-pop" data-fam="' + fam + '">' + img +
      '<div class="bkf-pop-body">' +
        '<div class="bkf-pop-top"><h4>' + esc(b.name) + '</h4>' + rating + '</div>' +
        '<div class="bkf-pop-meta">' + meta.join('<span class="dot"></span>') + ' ' + open + '</div>' +
        (svcs ? '<div class="bkf-pop-svcs">' + svcs + '</div>' : '') +
        '<div class="bkf-pop-foot">' + price +
          '<div class="bkf-pop-actions">' +
            '<a href="' + esc(b.url) + '" class="bkf-btn bkf-btn-ghost bkf-btn-sm">' + (AR ? 'عرض' : 'View') + '</a>' +
            '<a href="' + esc(b.book_url) + '" class="bkf-btn bkf-btn-primary bkf-btn-sm">' + (AR ? 'احجز' : 'Book') + '</a>' +
          '</div>' +
        '</div>' +
      '</div></div>';
  }

  function cardHTML(b) {
    var fam = catFamily(b.cat_slug);
    var img = b.image
      ? '<img src="' + esc(b.image) + '" alt="' + esc(b.name) + '" loading="lazy">'
      : '<span class="bkf-mapcard-ph" data-fam="' + fam + '"></span>';
    var rating = b.avg_rating ? '<span class="r">★ ' + b.avg_rating + '</span>' : '<span class="r none">' + (AR ? 'جديد' : 'New') + '</span>';
    var dt = distText(b);
    var meta = [b.category, b.city].filter(Boolean).map(esc).join(' · ');
    var price = b.min_price ? (AR ? 'من ' : 'From ') + Math.round(b.min_price).toLocaleString() + ' ' + CUR : (AR ? 'أسعار متنوعة' : 'Varied');
    return '<article class="bkf-mapcard" data-mapcard="' + b.id + '" data-fam="' + fam + '" tabindex="0">' +
      '<div class="bkf-mapcard-media">' + img + rating + (dt ? '<span class="d">' + dt + '</span>' : '') + '</div>' +
      '<div class="bkf-mapcard-body">' +
        '<h4>' + esc(b.name) + '</h4>' +
        '<div class="bkf-mapcard-meta">' + meta + '</div>' +
        '<div class="bkf-mapcard-foot"><span class="p">' + price + '</span>' +
          '<a href="' + esc(b.book_url) + '" class="bkf-btn bkf-btn-primary bkf-btn-xs">' + (AR ? 'احجز' : 'Book') + '</a>' +
        '</div>' +
      '</div></article>';
  }

  function setActive(id, fly) {
    if (activeId != null && cards[activeId]) cards[activeId].classList.remove('is-active');
    activeId = id;
    var b = data.find(function (x) { return x.id === id; });
    if (!b) return;
    if (cards[id]) {
      cards[id].classList.add('is-active');
      cards[id].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
    if (markers[id]) {
      Object.keys(markers).forEach(function (k) { markers[k].setIcon(pinIcon(markers[k].__fam, +k === id)); });
      if (fly) map.setView([b.lat, b.lng], Math.max(map.getZoom(), 14), { animate: true });
      markers[id].openPopup();
    }
  }

  function render() {
    var shown = data.filter(function (b) { return !activeFilter || catFamily(b.cat_slug) === activeFilter || b.cat_slug === activeFilter; });
    // list
    listEl.innerHTML = shown.map(cardHTML).join('') || '<div class="bkf-mapcard-empty">' + (AR ? 'لا توجد أماكن مطابقة' : 'No matching places') + '</div>';
    cards = {};
    shown.forEach(function (b) {
      var el = listEl.querySelector('[data-mapcard="' + b.id + '"]');
      if (!el) return;
      cards[b.id] = el;
      el.addEventListener('click', function (e) { if (e.target.closest('a')) return; setActive(b.id, true); });
      el.addEventListener('keydown', function (e) { if (e.key === 'Enter') setActive(b.id, true); });
    });
    if (countEl) countEl.textContent = shown.length + ' ' + (AR ? 'مكان' : 'places');
    // markers
    Object.keys(markers).forEach(function (k) { map.removeLayer(markers[k]); });
    markers = {};
    var pts = [];
    shown.forEach(function (b) {
      if (b.lat == null) return;
      var fam = catFamily(b.cat_slug);
      var mk = L.marker([b.lat, b.lng], { icon: pinIcon(fam, false) }).addTo(map);
      mk.__fam = fam;
      mk.bindPopup(popupHTML(b), { className: 'bkf-pop-shell', maxWidth: 300, minWidth: 260, closeButton: true, autoPanPadding: [24, 24] });
      mk.on('click', function () { setActive(b.id, false); });
      markers[b.id] = mk;
      pts.push([b.lat, b.lng]);
    });
    if (pts.length) map.fitBounds(pts, { padding: [50, 50], maxZoom: 14 });
  }

  function initMap() {
    if (inited) return; inited = true;
    loadLeaflet(function (Lref) {
      L = Lref;
      map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true }).setView(DAMASCUS, 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      fetch(CFG.url).then(function (r) { return r.json(); }).then(function (list) {
        data = list.filter(function (b) { return b.lat != null && b.lng != null; });
        loaded = true;
        if (loadEl) loadEl.style.display = 'none';
        render();
        setTimeout(function () { map.invalidateSize(); }, 200);
      }).catch(function () {
        if (loadEl) loadEl.innerHTML = AR ? 'تعذّر تحميل الخريطة' : 'Couldn’t load the map';
      });
    });
  }

  // Lazy-init when the section approaches the viewport (perf: nothing loads early).
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (ents) {
      ents.forEach(function (e) { if (e.isIntersecting) { initMap(); io.disconnect(); } });
    }, { rootMargin: '300px 0px' });
    io.observe(section);
  } else {
    initMap();
  }

  // category filters
  filterBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterBtns.forEach(function (b) { b.classList.remove('is-on'); });
      btn.classList.add('is-on');
      activeFilter = btn.getAttribute('data-mapfilter') || '';
      if (loaded) render();
    });
  });

  // "near me" — reuse shared geo, recolor distances, re-sort list by distance
  if (locateBtn) locateBtn.addEventListener('click', function () {
    if (!window.__bkRequestGeo) return;
    initMap();
    window.__bkRequestGeo(function (ok) {
      if (!ok || !loaded) return;
      data.sort(function (a, b) {
        var up = window.__bkUserPos();
        function dd(x) { var p = Math.PI / 180, dLa = (x.lat - up.lat) * p, dLo = (x.lng - up.lng) * p;
          var s = Math.sin(dLa / 2) ** 2 + Math.cos(up.lat * p) * Math.cos(x.lat * p) * Math.sin(dLo / 2) ** 2;
          return 6371 * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s)); }
        return dd(a) - dd(b);
      });
      render();
      var up = window.__bkUserPos();
      if (up && map) {
        if (L) L.circleMarker([up.lat, up.lng], { radius: 8, color: '#4B5D34', fillColor: '#4B5D34', fillOpacity: .6, weight: 3 }).addTo(map);
        map.setView([up.lat, up.lng], 13, { animate: true });
      }
    });
  });
})();
</script>
</x-slot:scripts>
</x-front.layout>
