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
        ['q_ar'=>'هل استخدام بوكسي مجاني للعملاء؟','q_en'=>'Is Booksy free for customers?','a_ar'=>'نعم تمامًا. البحث عن الأماكن والاطلاع على الأسعار والتقييمات والحجز كلها مجانية بالكامل.','a_en'=>'Completely. Searching, viewing prices and reviews, and booking are all free.'],
        ['q_ar'=>'هل أحتاج حسابًا لأحجز؟','q_en'=>'Do I need an account to book?','a_ar'=>'تستطيع التصفّح بلا حساب. وعند تأكيد الحجز نتحقق من رقمك برمز سريع عبر الرسائل — بلا كلمات مرور.','a_en'=>'Browse without one. At booking we verify your number with a quick code — no passwords.'],
        ['q_ar'=>'كيف أجد الأقرب إليّ؟','q_en'=>'How do I find places near me?','a_ar'=>'اضغط «الأقرب إليك» واسمح بالوصول لموقعك، فنحسب المسافة ونرتّب النتائج من الأقرب إليك.','a_en'=>'Tap “Near you”, allow location access, and we sort results by distance from you.'],
        ['q_ar'=>'هل أستطيع تعديل موعدي أو إلغاؤه؟','q_en'=>'Can I reschedule or cancel?','a_ar'=>'نعم، من رابط التأكيد الذي يصلك عند الحجز يمكنك التعديل أو الإلغاء بسهولة.','a_en'=>'Yes — from the confirmation link you receive, you can reschedule or cancel in a tap.'],
        ['q_ar'=>'كيف أعرف جودة المكان قبل الحجز؟','q_en'=>'How do I judge quality before booking?','a_ar'=>'كل مكان يعرض تقييمًا وعدد المراجعات وآراء عملاء حقيقيين وصورًا وأهم الخدمات وأسعارها.','a_en'=>'Every venue shows its rating, review count, real client reviews, photos, top services and prices.'],
    ];
@endphp

<x-front.layout
    variant="customer"
    :title="$t('GlowRez — احجز في أفضل مراكز الجمال والعناية قربك', 'GlowRez — Book Top Beauty & Wellness Venues Near You')"
    :keywords="$t('حجز صالون, حجز مركز تجميل, حجز حلاقة, سبا, أظافر, عيادات تجميل, مواعيد الجمال, سوريا, دمشق', 'salon booking, beauty appointment, barber booking, spa, nails, beauty clinics, wellness, Syria, Damascus')"
    :description="$t('اكتشف واحجز في أفضل أماكن الجمال والعناية قربك: شعر، سبا، تجميل، حلاقة، أظافر وعيادات. تقييمات حقيقية، أسعار واضحة، وحجز فوري من هاتفك عبر GlowRez.', 'Discover and book the best beauty & wellness venues near you — hair, spa, beauty, barber, nails and clinics. Real reviews, clear prices and instant mobile booking with GlowRez.')">

{{-- ══════════════ 1 · HERO ══════════════ --}}
<section class="bkf-hero" id="top">
  <div class="bkf-container-wide bkf-hero-grid">
    <div class="bkf-hero-text">
      <span class="bkf-eyebrow bkf-hero-eyebrow bkf-reveal">{{ $t('منصّة الحجز الأولى في سوريا', 'Syria’s #1 booking platform') }}</span>
      <h1 class="bkf-hero-title bkf-reveal bkf-reveal-d1">
        {{ $t('احجز موعدك في', 'Book your spot at') }}<br>
        <span class="em">{{ $t('أرقى أماكن الجمال والعناية', 'the finest beauty places') }}</span>
      </h1>
      <p class="bkf-hero-lead bkf-reveal bkf-reveal-d2">
        {{ $t('اكتشف صالونات الشعر والسبا ومراكز التجميل والعيادات قربك — تقييمات حقيقية، أسعار واضحة، وحجز فوري من هاتفك.', 'Discover hair, spa, beauty and clinic venues near you — real reviews, clear prices, and instant booking from your phone.') }}
      </p>

      <form class="bkf-searchbar bkf-reveal bkf-reveal-d3" id="bkf-hero-search" action="{{ $venuesUrl }}" method="GET" role="search">
        <div class="bkf-sb-field">
          <x-icon name="search" :size="18"/>
          <input type="text" name="search" id="bkf-q" placeholder="{{ $t('خدمة أو اسم مكان…', 'Service or venue…') }}" autocomplete="off" aria-label="{{ $t('ابحث عن خدمة أو مكان', 'Search service or venue') }}">
        </div>
        <div class="bkf-sb-field">
          <x-icon name="map-pin" :size="18"/>
          <input type="text" name="city" id="bkf-city" list="bkf-cities" placeholder="{{ $t('المدينة', 'City') }}" autocomplete="off" aria-label="{{ $t('اختر المدينة', 'Choose city') }}">
          <datalist id="bkf-cities">
            @foreach($cities as $city)<option value="{{ $city }}"></option>@endforeach
          </datalist>
        </div>
        <button type="submit" class="bkf-btn bkf-btn-primary">
          <x-icon name="search" :size="18"/>{{ $t('ابحث', 'Search') }}
        </button>
      </form>

      <div class="bkf-hero-chips bkf-reveal bkf-reveal-d4">
        @foreach($categories->take(5) as $cat)
          <a href="#categories" class="bkf-chip"><x-icon name="{{ $catIcon($cat->slug) }}" :size="14"/>{{ $isAr ? $cat->name_ar : $cat->name_en }}</a>
        @endforeach
      </div>

      <div class="bkf-hero-proof bkf-reveal bkf-reveal-d5">
        <span class="bkf-hero-early"><span class="dot"></span>{{ $t('التسجيل المبكر مفتوح — الإطلاق الرسمي قريبًا', 'Early access is open — official launch soon') }}</span>
      </div>
    </div>

    <div class="bkf-hero-visual bkf-reveal bkf-reveal-d3" data-tilt="6">
      <div class="bkf-hero-photo">
        <img src="{{ asset('magnific/Behind the scenes.jpg') }}" alt="{{ $t('صالون تجميل', 'Beauty salon') }}" fetchpriority="high">
      </div>
      <div class="bkf-hero-float f1" data-tilt-layer="1.6">
        <span class="ic olive"><x-icon name="calendar" :size="18"/></span>
        <span><span class="lbl">{{ $t('موعدك القادم', 'Next appointment') }}</span><span class="val">{{ $t('قص + صبغة · غدًا ٣:٣٠م', 'Cut + Color · Tmrw 3:30') }}</span></span>
      </div>
      <div class="bkf-hero-float f2" data-tilt-layer="2.2">
        <span class="ic gold"><x-icon name="star-fill" :size="18"/></span>
        <span><span class="lbl">{{ $t('تقييم', 'Rating') }}</span><span class="val bkf-tnum">4.9 · {{ $t('صالون لمسة', 'Lamsa Salon') }}</span></span>
      </div>
      <div class="bkf-hero-float f3" data-tilt-layer="1.2" style="inset-block-end: 50%; !important">
        <span class="ic olive"><x-icon name="navigation" :size="18"/></span>
        <span><span class="lbl">{{ $t('قريب منك', 'Near you') }}</span><span class="val bkf-tnum">1.2 {{ $t('كم', 'km') }}</span></span>
      </div>
    </div>
  </div>
</section>

{{-- ══════════════ 2 · VALUE BAR (qualitative — no counts pre-launch) ══════════════ --}}
<div class="bkf-trust">
  <div class="bkf-container-wide bkf-trust-inner">
    <div class="bkf-trust-item is-qual">
      <span class="bkf-trust-ic"><x-icon name="calendar" :size="22"/></span>
      <span><span class="bkf-trust-n">{{ $t('حجز فوري', 'Instant booking') }}</span><span class="bkf-trust-l"></span></span>
    </div>
    <span class="bkf-trust-sep"></span>
    <div class="bkf-trust-item is-qual">
      <span class="bkf-trust-ic "><x-icon name="star-fill" :size="22"/></span>
      <span><span class="bkf-trust-n">{{ $t('تقييمات موثوقة', 'Real reviews') }}</span><span class="bkf-trust-l"></span></span>
    </div>
    <span class="bkf-trust-sep"></span>
    <div class="bkf-trust-item is-qual">
      <span class="bkf-trust-ic"><x-icon name="tag" :size="22"/></span>
      <span><span class="bkf-trust-n">{{ $t('أسعار واضحة', 'Clear pricing') }}</span><span class="bkf-trust-l"></span></span>
    </div>
    <span class="bkf-trust-sep"></span>
    <div class="bkf-trust-item is-qual">
      <span class="bkf-trust-ic"><x-icon name="check-circle" :size="22"/></span>
      <span><span class="bkf-trust-n">{{ $t('بلا مكالمات', 'No phone calls') }}</span><span class="bkf-trust-l"></span></span>
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

    <div class="bkf-grid bkf-grid-auto" id="bkf-grid">
      @foreach($featured as $c)
        @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
      @endforeach
    </div>

    <div class="bkf-center" style="margin-top:44px">
      <a href="{{ $venuesUrl }}" class="bkf-btn bkf-btn-soft bkf-btn-lg" data-venues-cta>
        {{ $t('عرض جميع الأماكن', 'View all venues') }}<x-icon name="arrow-right" :size="18"/>
      </a>
    </div>
  </div>
</section>

{{-- wave: cream page → surface band --}}
<x-front.wave top="var(--bk-bg)" bottom="var(--bk-surface)" tint />

{{-- ══════════════ 5 · HOW IT WORKS (breaks the venue wall, reassures) ══════════════ --}}
<section class="bkf-section" id="how" style="background:var(--bk-surface);padding-top:clamp(24px,4vw,56px)">
  <div class="bkf-container">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('بسيط وسريع', 'Simple & fast') }}</span>
      <h2 class="bkf-title bkf-mt-0">{{ $t('كيف', 'How') }} <span class="em">{{ $t('يعمل بوكسي', 'Booksy works') }}</span></h2>
      <p class="bkf-lead">{{ $t('من البحث إلى الحجز في أقل من دقيقة.', 'From search to booking in under a minute.') }}</p>
    </div>
    <div class="bkf-steps" style="margin-top:56px">
      <div class="bkf-step bkf-reveal">
        <div class="bkf-step-ic"><span class="bkf-step-n">1</span><x-icon name="search" :size="30"/></div>
        <h3>{{ $t('ابحث', 'Search') }}</h3>
        <p>{{ $t('ابحث عن خدمة أو مكان، أو دع موقعك يعرض الأقرب إليك.', 'Search a service or venue, or let your location surface what’s nearest.') }}</p>
      </div>
      <div class="bkf-step bkf-reveal bkf-reveal-d1">
        <div class="bkf-step-ic"><span class="bkf-step-n">2</span><x-icon name="calendar" :size="30"/></div>
        <h3>{{ $t('اختر', 'Choose') }}</h3>
        <p>{{ $t('قارن التقييمات والأسعار والخدمات، واختر الوقت المناسب لك.', 'Compare ratings, prices and services, then pick the time that suits you.') }}</p>
      </div>
      <div class="bkf-step bkf-reveal bkf-reveal-d2">
        <div class="bkf-step-ic"><span class="bkf-step-n">3</span><x-icon name="check-circle" :size="30"/></div>
        <h3>{{ $t('احجز', 'Book') }}</h3>
        <p>{{ $t('أكّد بنقرة واحدة، ويصلك تذكير قبل موعدك. بلا مكالمات.', 'Confirm in one tap and get a reminder before your visit. No phone calls.') }}</p>
      </div>
    </div>
  </div>
</section>

{{-- wave: surface band → cream page --}}
<x-front.wave top="var(--bk-surface)" bottom="var(--bk-bg)" flip tint />

{{-- ══════════════ 6 · TOP RATED (rail) — proof of quality ══════════════ --}}
@if($topRated->isNotEmpty())
<section class="bkf-section" style="padding-top:clamp(16px,3vw,40px)">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <span class="bkf-eyebrow">{{ $t('الأفضل ثقةً', 'Most trusted') }}</span>
        <h2 class="bkf-title bkf-mt-0">{{ $t('الأعلى', 'Top') }} <span class="em">{{ $t('تقييماً', 'rated') }}</span></h2>
      </div>
      <a href="#discover" class="bkf-seeall">{{ $t('عرض الكل', 'See all') }}<x-icon name="arrow-right" :size="16"/></a>
    </div>
    <div class="bkf-rail">
      @foreach($topRated as $c)
        @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ══════════════ 7 · NEAR YOU (gated rail) ══════════════ --}}
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

{{-- ══════════════ 8 · REVIEWS — social proof ══════════════ --}}
<section class="bkf-section">
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

{{-- ══════════════ 11 · FAQ ══════════════ --}}
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

{{-- ══════════════ 12 · FINAL CTA ══════════════ --}}
<section class="bkf-section" style="padding-block:clamp(32px,5vw,64px)">
  <div class="bkf-container-wide">
    <div class="bkf-cta bkf-reveal">
      <span class="bkf-cta-blob a"></span><span class="bkf-cta-blob b"></span>
      <div class="bkf-cta-grid">
        <div>
          <span class="eyebrow">{{ $t('للعملاء', 'For customers') }}</span>
          <h2 style="font-size:var(--bk-fs-h2);margin-top:8px">{{ $t('جاهز لحجز موعدك؟', 'Ready to book?') }}</h2>
          <p>{{ $t('اكتشف أفضل الأماكن قربك واحجز في ثوانٍ.', 'Discover the best places near you and book in seconds.') }}</p>
          <a href="#discover" class="bkf-btn bkf-btn-primary bkf-btn-lg" style="margin-top:20px"><x-icon name="search" :size="18"/>{{ $t('ابحث واحجز الآن', 'Search & book now') }}</a>
        </div>
        <div class="bkf-cta-div"></div>
        <div>
          <span class="eyebrow">{{ $t('لأصحاب الأعمال', 'For business') }}</span>
          <h3 style="font-size:var(--bk-fs-h3);margin-top:8px">{{ $t('تملك نشاطاً في مجال الجمال والعناية؟', 'Own a beauty or wellness business?') }}</h3>
          <p>{{ $t('انضم إلى بوكسي وابدأ باستقبال الحجوزات مجانًا اليوم.', 'Join Booksy and start taking bookings for free today.') }}</p>
          <a href="{{ route('company.register') }}" class="bkf-btn bkf-btn-soft bkf-btn-lg" style="margin-top:20px"><x-icon name="building" :size="18"/>{{ $t('انضم إلينا', 'Join us') }}</a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- customer-auth-modal is centralised in x-front.layout --}}

<x-slot:scripts>
<script>
(function () {
  'use strict';
  var AR = @json($isAr);
  var km = function (d) { return d < 1 ? Math.round(d * 1000) + (AR ? ' م' : ' m') : d.toFixed(1) + (AR ? ' كم' : ' km'); };

  /* favourites now handled globally in booksy-front.js (initFavorites) */

  /* ── toast ── */
  function toast(msg) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:var(--bk-text);color:var(--bk-bg);padding:10px 18px;border-radius:999px;font-family:var(--bk-font-ui);font-size:.85rem;z-index:1300;box-shadow:var(--bk-shadow-lg);opacity:0;transition:opacity .3s';
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.style.opacity = '1'; });
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 2600);
  }

  /* ── geolocation + distance ── */
  var userPos = null, geoBusy = false;
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
    if (geoBusy) return; geoBusy = true;
    navigator.geolocation.getCurrentPosition(function (p) {
      geoBusy = false; userPos = { lat: p.coords.latitude, lng: p.coords.longitude };
      applyDistances(document);
      toast(AR ? 'رُتّبت النتائج حسب موقعك' : 'Results sorted by your location');
      cb && cb(true);
    }, function () {
      geoBusy = false; toast(AR ? 'تعذّر الوصول إلى موقعك' : 'Couldn’t access your location'); cb && cb(false);
    }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 });
  }

  /* ── featured grid (curated) + forward-ready search/CTA ──
     The homepage is a curated landing page; full search + filtering live on the
     dedicated /venues page (next phase). Until it ships we intercept the hand-off
     so users never hit a dead link — swap `VENUES_READY` to true once /venues is live. */
  var VENUES_READY = true;
  var grid = document.getElementById('bkf-grid');

  if (grid) [].slice.call(grid.querySelectorAll('[data-venue]')).forEach(function (c) {
    var name = (c.querySelector('.bkf-vp-name') || {}).textContent || '';
    var meta = (c.querySelector('.bkf-vp-meta') || {}).textContent || '';
    var svc = (c.querySelector('.bkf-vp-svcs') || {}).textContent || '';
    c.dataset.text = (name + ' ' + meta + ' ' + svc).toLowerCase();
  });

  var heroForm = document.getElementById('bkf-hero-search');
  var qInput = document.getElementById('bkf-q'), cityInput = document.getElementById('bkf-city');

  if (heroForm) heroForm.addEventListener('submit', function (e) {
    if (VENUES_READY) return; // let it navigate to /venues?search=&city=
    e.preventDefault();
    var query = ((qInput ? qInput.value : '') + ' ' + (cityInput ? cityInput.value : '')).trim().toLowerCase();
    var d = document.getElementById('discover');
    if (!query) { if (d) d.scrollIntoView({ behavior: 'smooth', block: 'start' }); return; }
    // Highlight matching featured cards; if none match, nudge toward the (coming) full page.
    var hit = 0;
    if (grid) [].slice.call(grid.querySelectorAll('[data-venue]')).forEach(function (c) {
      var ok = (c.dataset.text || '').indexOf(query) > -1;
      c.style.opacity = ok ? '' : '.32';
      if (ok) hit++;
    });
    if (d) d.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (!hit) toast(AR ? 'صفحة نتائج البحث الكاملة قريبًا' : 'Full search results coming soon');
  });
  // Clear the dim state when the user edits the query again
  [qInput, cityInput].forEach(function (el) {
    if (el) el.addEventListener('input', function () {
      if (grid) [].slice.call(grid.querySelectorAll('[data-venue]')).forEach(function (c) { c.style.opacity = ''; });
    });
  });

  // "View all venues" CTAs — graceful until /venues ships
  document.querySelectorAll('[data-venues-cta]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (VENUES_READY) return;
      e.preventDefault();
      toast(AR ? 'صفحة استكشاف كل الأماكن قريبًا' : 'Full explore page coming soon');
    });
  });

  /* ── nearby gated rail ── */
  function revealNearby() {
    var sec = document.getElementById('nearby'); if (!sec) return;
    var gate = document.getElementById('bkf-geo-gate'), rail = sec.querySelector('[data-nearby-rail]');
    requestGeo(function (ok) {
      if (!ok) return;
      applyDistances(sec);
      if (rail) { sortCards(rail, 'distance', 'asc'); rail.style.display = ''; }
      if (gate) gate.style.display = 'none';
      if (window.bkRefreshRails) window.bkRefreshRails(); // enable drag on the now-visible rail
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

  /* map FAB + modal now live globally in front/partials/map-fab.blade.php */
})();
</script>
</x-slot:scripts>
</x-front.layout>
