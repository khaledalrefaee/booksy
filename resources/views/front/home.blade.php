@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn($ar, $en) => $isAr ? $ar : $en;
    // hero look (initial; the on-page switcher can flip it): a = immersive photo · b = light + photo tiles · c = olive poster
    $heroVariant = in_array(request()->query('hero'), ['a', 'b', 'c'], true) ? request()->query('hero') : 'a';

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

    $ci = fn($cat) => str_starts_with((string) $cat->icon, 'cat-') ? $cat->icon : $catIcon($cat->slug);

    $reviews = [
        ['q_ar'=>'حجزت خلال دقيقة واحدة، ووجدت أقرب صالون لبيتي بسهولة. تجربة مريحة جدًا.','q_en'=>'Booked in a minute and found the closest salon to home. So smooth.','nm'=>$isAr?'لمى ح.':'Lama H.','rl'=>$isAr?'عميلة':'Client','in'=>$isAr?'ل':'L'],
        ['q_ar'=>'التقييمات والآراء ساعدتني أختار المكان المناسب، والأسعار واضحة قبل الحجز.','q_en'=>'Ratings and reviews helped me pick the right place, prices are clear upfront.','nm'=>$isAr?'رهف م.':'Rahaf M.','rl'=>$isAr?'عميلة':'Client','in'=>$isAr?'ر':'R'],
        ['q_ar'=>'أفضل بكثير من الاتصال والانتظار. كل شيء واضح وسريع من الهاتف.','q_en'=>'Far better than calling and waiting. Everything is clear and fast on mobile.','nm'=>$isAr?'كنان أ.':'Kenan A.','rl'=>$isAr?'عميل':'Client','in'=>$isAr?'ك':'K'],
    ];

    $faqs = [
        ['q_ar'=>'هل استخدام GlowRez مجاني للعملاء؟','q_en'=>'Is GlowRez free for customers?','a_ar'=>'نعم تمامًا. البحث عن الأماكن والاطلاع على الأسعار والتقييمات والحجز كلها مجانية بالكامل.','a_en'=>'Completely. Searching, viewing prices and reviews, and booking are all free.'],
        ['q_ar'=>'هل أحتاج حسابًا لأحجز؟','q_en'=>'Do I need an account to book?','a_ar'=>'تستطيع التصفّح بلا حساب. وعند تأكيد الحجز نتحقق من رقمك برمز سريع عبر الرسائل، بلا كلمات مرور.','a_en'=>'Browse without one. At booking we verify your number with a quick code, no passwords.'],
        ['q_ar'=>'كيف أجد الأقرب إليّ؟','q_en'=>'How do I find places near me?','a_ar'=>'اضغط «فعّل موقعي» على الخريطة أو في قسم «الأقرب إليك»، فنحسب المسافة ونرتّب النتائج من الأقرب.','a_en'=>'Tap “Use my location” on the map or in the “Near you” section, we sort results by distance from you.'],
        ['q_ar'=>'هل أستطيع تعديل موعدي أو إلغاؤه؟','q_en'=>'Can I reschedule or cancel?','a_ar'=>'نعم، من رابط التأكيد الذي يصلك عند الحجز يمكنك التعديل أو الإلغاء بسهولة.','a_en'=>'Yes, from the confirmation link you receive, you can reschedule or cancel in a tap.'],
        ['q_ar'=>'كيف أعرف جودة المكان قبل الحجز؟','q_en'=>'How do I judge quality before booking?','a_ar'=>'كل مكان يعرض تقييمًا وعدد المراجعات وآراء عملاء حقيقيين وصورًا وأهم الخدمات وأسعارها.','a_en'=>'Every venue shows its rating, review count, real client reviews, photos, top services and prices.'],
    ];
@endphp

<x-front.layout
    variant="customer"
    :mapFab="false"
    :bodyClass="$heroVariant === 'b' ? '' : 'bkf-has-hero'"
    :title="$t('GlowRez | اكتشف واحجز في أفضل مراكز الجمال والعناية قربك', 'GlowRez, Discover & Book Top Beauty & Wellness Venues Near You')"
    :keywords="$t('حجز صالون, حجز مركز تجميل, حجز حلاقة, سبا, أظافر, عيادات تجميل, مواعيد الجمال, خريطة الأماكن, سوريا, دمشق', 'salon booking, beauty appointment, barber booking, spa, nails, beauty clinics, wellness map, Syria, Damascus')"
    :description="$t('اكتشف واحجز في أفضل أماكن الجمال والعناية قربك: شعر، سبا، تجميل، حلاقة، أظافر وعيادات. استكشف على الخريطة، قارن التقييمات والأسعار، واحجز فورًا من هاتفك عبر GlowRez.', 'Discover and book the best beauty & wellness venues near you, explore them on a live map, compare ratings and prices, and book instantly from your phone with GlowRez.')">

@include('front.partials.hero-hub')

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

@include('front.partials.categories-hub')

@include('front.partials.featured-hub')

@include('front.partials.map-discovery')

{{-- ══════════════ 6 · HOW IT WORKS — 4-step customer journey ══════════════ --}}
<section class="bkf-section bkf-3d" id="how" style="padding-top:clamp(20px,3vw,44px);background:var(--bk-bg)">
  <div class="bkf-container">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('بسيط وسريع', 'Simple & fast') }}</span>
      <h2 class="bkf-title bkf-mt-0">{{ $t('احجز في', 'Book in') }} <span class="em">{{ $t('دقائق', 'minutes') }}</span></h2>
      <p class="bkf-lead">{{ $t('من البحث إلى تأكيد الموعد في أربع خطوات فقط.', 'From search to a confirmed appointment in just four steps.') }}</p>
    </div>
    <div class="bkf-steps bkf-steps-4" style="margin-top:52px">
      <div class="bkf-steps-line" aria-hidden="true"><i></i></div>
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

<x-slot:styles>
<style>
/* ═══ Hero · first-screen flow ═══
   Desktop keeps the immersive hero. On phones the headline, ONE search field and the category
   quick-picks all land in the lower half of the first screen (thumb zone), over the photo. */
.hq{display:none}
@media (max-width:720px){
  .bkf-hero-immersive{min-height:min(640px,84svh);align-items:flex-end;padding-block:calc(var(--bk-nav-h) + 10px) 22px}
  .bkf-hero-immersive .bkf-hero-slide{object-position:50% 24%}
  .bkf-hero-immersive .bkf-hero-scrim,html[dir="rtl"] .bkf-hero-immersive .bkf-hero-scrim,
  .bkf-hero-immersive.align-center .bkf-hero-scrim{background:linear-gradient(180deg,rgba(12,16,7,.62) 0%,rgba(12,16,7,.1) 24%,rgba(12,16,7,.5) 50%,rgba(12,16,7,.92) 100%)}
  .bkf-hero-immersive .bkf-hero-eyebrow,.bkf-hero-immersive .bkf-hero-lead,.bkf-hero-immersive .bkf-hero-chips,
  .bkf-hero-immersive .bkf-hero-proof,.bkf-hero-immersive .bkf-hero-dots{display:none}
  .bkf-hero-immersive .bkf-hero-i-text{max-width:none;align-items:stretch}
  html[lang="ar"] .bkf-hero-immersive .bkf-hero-title{font-size:clamp(1.75rem,7.4vw,2.2rem);line-height:1.3;margin-bottom:16px;text-align:start}
  /* one field + one round button */
  .bkf-hero-immersive .bkf-hsearch{flex-direction:row;align-items:center;gap:0;margin-top:0;padding:6px 6px 6px 8px;padding-inline:16px 6px;border-radius:var(--bk-r-pill);
    background:rgba(255,255,255,.96);border-color:transparent;box-shadow:0 18px 36px -14px rgba(0,0,0,.65)}
  .bkf-hero-immersive .bkf-hsearch-div,.bkf-hero-immersive .bkf-hsearch-field:nth-of-type(n+2){display:none}
  .bkf-hero-immersive .bkf-hsearch-field{padding-inline:0}
  .bkf-hero-immersive .bkf-hsearch-field input{font-size:1rem;padding-block:15px;color:#22251D}
  .bkf-hero-immersive .bkf-hsearch-field input::placeholder{color:#6C6D5E}
  .bkf-hero-immersive .bkf-hsearch-go{flex:0 0 52px;width:52px;height:52px;padding:0;margin:0;justify-content:center}
  .bkf-hero-immersive .bkf-hsearch-go span{display:none}
  .bkf-hero-immersive .bkf-hsearch-go svg{width:22px;height:22px}

  /* category quick-picks */
  .hq{display:flex;gap:14px;margin-top:22px;margin-inline:calc(var(--bk-gutter) * -1);padding:2px var(--bk-gutter) 4px;overflow-x:auto;scroll-snap-type:x proximity;scrollbar-width:none;-webkit-overflow-scrolling:touch;width:auto!important}
  .hq::-webkit-scrollbar{display:none}
  .hq-item{flex:0 0 auto;width:76px;display:flex;flex-direction:column;align-items:center;gap:8px;color:#fff;text-decoration:none;scroll-snap-align:start;-webkit-tap-highlight-color:transparent}
  .hq-ic{position:relative;width:66px;height:66px;border-radius:50%;overflow:hidden;display:grid;place-items:center;color:#fff;background:rgba(255,255,255,.16);border:2px solid rgba(255,255,255,.7);
    box-shadow:0 10px 20px -10px rgba(0,0,0,.7);transition:transform .25s var(--bk-ease)}
  .hq-item:active .hq-ic{transform:scale(.92)}
  .hq-ic img{width:100%!important;height:100%!important;max-width:none!important;object-fit:cover}
  .hq-ic b{width:100%;height:100%;display:grid;place-items:center;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.5rem}
  .hq-n{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.82rem;line-height:1.25;text-align:center;color:#fff;text-shadow:0 1px 10px rgba(0,0,0,.7)}
  .hq-item.is-near .hq-ic{background:var(--bk-grad-gold);border-color:rgba(255,255,255,.9);color:var(--bk-gold-ink)}
  .hq-item.is-all .hq-ic{background:rgba(255,255,255,.96);color:var(--bk-accent-strong);border-color:transparent}
}

/* ═══ Categories · grouped directory ═══ */
.ct{padding-top:clamp(36px,5vw,72px)}
.ct-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,300px));gap:clamp(14px,1.8vw,22px);justify-content:start;margin-top:clamp(20px,2.6vw,32px)}
.ct-card{display:flex;flex-direction:column;overflow:hidden;background:var(--bk-surface);border:1px solid var(--bk-border);border-radius:28px;box-shadow:var(--bk-shadow-sm)}
.ct-head{position:relative;isolation:isolate;display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:104px;padding:20px 22px;color:#fff;overflow:hidden;
  background:radial-gradient(120% 140% at 0% 0%,color-mix(in srgb,var(--g) 70%,#fff) 0,transparent 60%),var(--g)}
.ct-head::before{content:"";position:absolute;inset:0;z-index:-1;background:currentColor;opacity:.09;
  -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/64px 64px;
  mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/64px 64px}
.ct-card .ct-head h3{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.5rem;line-height:1.2;color:#fff}
.ct-head p{margin:6px 0 0;font-family:var(--bk-font-ui);font-size:.88rem;font-weight:700;color:rgba(255,255,255,.88)}
.ct-thumb{flex:0 0 auto;width:68px;height:68px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.9);box-shadow:0 12px 22px -10px rgba(0,0,0,.55);background:#fff}
.ct-thumb img{width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;display:block}
.ct-thumb b,.ct-ic b{width:100%;height:100%;display:grid;place-items:center;color:#fff;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.6rem}
.ct-list{list-style:none;margin:0;padding:8px}
.ct-row{display:flex;align-items:center;gap:14px;min-height:60px;padding:8px 12px;border-radius:18px;color:var(--bk-text);text-decoration:none;transition:background .25s,transform .25s var(--bk-ease)}
.ct-row:hover,.ct-row:focus-visible{background:var(--bk-accent-wash)}
.ct-row:active{transform:scale(.985)}
.ct-ic{flex:0 0 auto;width:44px;height:44px;border-radius:50%;overflow:hidden;background:var(--bk-surface-2);border:1px solid var(--bk-border)}
.ct-ic img{width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;display:block}
.ct-ic b{font-size:1.2rem}
.ct-n{flex:1;min-width:0;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.08rem;line-height:1.3}
.ct-c{min-width:28px;height:28px;padding:0 9px;border-radius:14px;display:grid;place-items:center;font-family:var(--bk-font-ui);font-size:.8rem;font-weight:800;background:var(--bk-surface-2);color:var(--bk-text-muted)}
.ct-go{color:var(--bk-text-muted);display:grid;transition:transform .3s var(--bk-ease),color .2s}
[dir=rtl] .ct-go svg{transform:scaleX(-1)}
.ct-row:hover .ct-go{color:var(--bk-accent);transform:translateX(4px)}
[dir=rtl] .ct-row:hover .ct-go{transform:translateX(-4px)}
@media (max-width:719px){
  .ct-grid{grid-template-columns:minmax(0,1fr)}
  .ct-head{min-height:88px;padding:16px 18px}
  .ct-thumb{width:58px;height:58px}
}
/* GSAP-enhanced "Book in minutes" */
#how .bkf-steps{position:relative}
#how .bkf-steps-line{position:absolute;top:45px;inset-inline:12.5%;height:2px;background:var(--bk-border,rgba(0,0,0,.1));border-radius:2px;overflow:hidden;z-index:0}
#how .bkf-steps-line i{display:block;height:100%;width:100%;background:var(--bk-grad-gold,#c9a24a);transform-origin:var(--steps-origin,right) center;transform:scaleX(0)}
#how .bkf-step{position:relative;z-index:1}
#how .bkf-step-ic{transition:box-shadow .3s,background .3s,color .3s}
#how .bkf-step.is-lit .bkf-step-ic{background:var(--bk-accent);color:#fff;box-shadow:var(--bk-shadow-accent)}
@media (max-width:900px){#how .bkf-steps-line{display:none}}
/* ═══ Featured · gallery arch + menu-style index ═══ */
.fp{--fp-t:5.6s;--fp-ease:cubic-bezier(.22,1,.36,1);--fp-ox:22px;--fp-arch:170px 36px 36px 36px}
[dir=rtl] .fp{--fp-ox:-22px}
.fp-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:clamp(32px,5vw,52px)}
.fp-side{min-width:0}
.fp-title{font-size:clamp(2.2rem,1.5rem + 3vw,3.7rem);line-height:1.1}
.fp-lead{margin-top:14px;max-width:38ch;font-family:var(--bk-font-ui);font-size:var(--bk-fs-lead);line-height:1.75;color:var(--bk-text-soft)}
.fp-empty{margin-top:24px;color:var(--bk-text-muted)}

/* index — reads like a menu: name ····· price */
.fp-index{list-style:none;margin:0;padding:0;border-top:1px solid var(--bk-border)}
.fp-row{position:relative;display:flex;align-items:flex-start;gap:14px;padding:17px 2px 18px;border-bottom:1px solid var(--bk-border);
  color:var(--bk-text-muted);text-decoration:none;transition:color .4s var(--fp-ease),opacity .7s var(--fp-ease) calc(.25s + var(--i,0)*.07s),transform .8s var(--fp-ease) calc(.25s + var(--i,0)*.07s)}
.fp-row::after{content:"";position:absolute;inset-inline:0;bottom:-1px;height:2px;background:var(--bk-grad-gold);transform:scaleX(0);transform-origin:left center;pointer-events:none}
[dir=rtl] .fp-row::after{transform-origin:right center}
.fp.is-playing .fp-row.is-on::after{animation:fp-prog var(--fp-t) linear forwards}
.fp:not(.is-playing) .fp-row.is-on::after{transform:scaleX(1)}
@keyframes fp-prog{to{transform:scaleX(1)}}

/* the mark: two squares, one turned 45° (the khatam star), grows in on the active row */
.fp-mark{position:relative;flex:0 0 16px;width:16px;height:16px;margin-top:12px}
.fp-mark::before,.fp-mark::after{content:"";position:absolute;inset:3px;background:var(--bk-gold);transform:scale(0) rotate(-45deg);transition:transform .55s var(--bk-spring)}
.fp-mark::after{transform:scale(0) rotate(0deg)}
.fp-row.is-on .fp-mark::before{transform:scale(1) rotate(0deg)}
.fp-row.is-on .fp-mark::after{transform:scale(1) rotate(45deg)}

.fp-row-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px}
.fp-row-line{display:flex;align-items:baseline;gap:12px;min-width:0}
.fp-row-name{flex:0 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:clamp(1.45rem,1.1rem + 1.2vw,2.1rem);line-height:1.3;color:inherit}
.fp-leader{flex:1 1 20px;min-width:16px;height:0;border-bottom:1.5px dotted color-mix(in srgb,currentColor 45%,transparent)}
.fp-row-price{flex:0 0 auto;font-family:var(--bk-font-display);font-weight:700;font-size:1.05rem;white-space:nowrap;transition:color .4s}
.fp-row.is-on{color:var(--bk-text)}
.fp-row.is-on .fp-row-price{color:var(--bk-gold-strong)}
.fp-row-meta{display:flex;flex-wrap:wrap;align-items:center;gap:4px 16px;font-family:var(--bk-font-ui);font-size:.86rem;color:var(--bk-text-muted)}
.fp-row.is-on .fp-row-meta{color:var(--bk-text-soft)}
.fp-row-meta > span,.fp-sub > span{display:inline-flex;align-items:center;gap:5px}
.fp-row-meta svg{color:var(--bk-gold-strong)}
.fp-open i{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px color-mix(in srgb,currentColor 20%,transparent)}
.fp-open.on{color:var(--bk-success)}
.fp-open.off{color:var(--bk-danger)}
.fp-dist{display:none!important;color:var(--bk-accent)}
.fp-row[data-has-distance] .fp-dist{display:inline-flex!important}

/* hand-off link */
.fp-all{margin-top:30px;display:inline-flex;align-items:center;gap:14px;padding:6px 22px 6px 6px;padding-inline:22px 6px;border-radius:var(--bk-r-pill);border:1px solid var(--bk-border-strong);
  font-family:var(--bk-font-ui);font-weight:700;font-size:.95rem;color:var(--bk-text);text-decoration:none;transition:background .3s,border-color .3s}
.fp-all-ic{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:var(--bk-accent-fill);color:var(--bk-accent-ink);transition:transform .4s var(--fp-ease),background .3s,color .3s}
[dir=rtl] .fp-all-ic svg{transform:scaleX(-1)}
.fp-all:hover{background:var(--bk-accent-wash);border-color:var(--bk-accent)}
.fp-all:hover .fp-all-ic{background:var(--bk-grad-gold);color:var(--bk-gold-ink);transform:translateX(4px)}
[dir=rtl] .fp-all:hover .fp-all-ic{transform:translateX(-4px)}

/* stage — an arch, with a second gold arch standing behind it */
.fp-stagebox{position:relative;isolation:isolate;width:100%;min-width:0;max-width:500px;margin-inline:auto}
.fp-stagebox::before,.fp-stagebox::after{content:"";position:absolute;inset:0;z-index:-1;border-radius:var(--fp-arch);
  transition:translate 1.2s var(--fp-ease) .45s,opacity .8s ease .45s}
.fp-stagebox::before{border:1.5px solid color-mix(in srgb,var(--bk-gold) 75%,transparent);translate:var(--fp-ox) 22px}
.fp-stagebox::after{z-index:-2;background:var(--bk-accent-wash);translate:calc(var(--fp-ox) * -.55) 14px}
.fp-stage{position:relative;aspect-ratio:4/5.15;border-radius:var(--fp-arch);overflow:hidden;background:#2a3320;
  box-shadow:0 34px 60px -30px rgba(28,36,14,.55),0 10px 24px -12px rgba(28,36,14,.3)}

.fp-panel{position:absolute;inset:0;visibility:hidden;color:#fff}
.fp-panel.is-on,.fp-panel.is-leaving{visibility:visible}
.fp-panel.is-leaving{z-index:1}
.fp-panel.is-on{z-index:2}
.fp.is-seen .fp-panel.is-on{animation:fp-wipe 1.05s var(--fp-ease) both}
@keyframes fp-wipe{from{clip-path:inset(100% 0 0 0)}to{clip-path:inset(0 0 0 0)}}
.fp-art{position:absolute;inset:0}
.fp-art .fa-img{transform:scale(1.12);transition:transform 2s var(--fp-ease)}
.fp-panel.is-on .fp-art .fa-img{transform:scale(1)}
.fp-shade{position:absolute;inset:0;background:linear-gradient(to top,rgba(14,18,8,.9) 0%,rgba(14,18,8,.6) 30%,rgba(14,18,8,.06) 60%,rgba(14,18,8,0) 100%)}
.fp-cover{position:absolute;inset:0;z-index:1}

/* no photo yet → a deep family tone with the lattice pattern, never a grey box */
/* artwork shared by every featured design: photo, or a per-venue colour field (no category icons) */
.fa-img{display:block;position:absolute;inset:0;width:100%!important;height:100%!important;max-width:none!important;object-fit:cover}
.fa-ph{position:absolute;inset:0;overflow:hidden;color:#FBF3DC;
  background:radial-gradient(120% 85% at 12% 8%,var(--b) 0,transparent 58%),radial-gradient(95% 80% at 92% 78%,var(--c) 0,transparent 62%),var(--a)}
.fa-ph::before{content:"";position:absolute;inset:0;background:currentColor;opacity:.08;
  -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/64px 64px;
  mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/64px 64px}
.fa-ph-l{position:absolute;inset-inline-end:-4%;bottom:-16%;font-family:var(--bk-font-display);font-weight:800;font-size:24rem;line-height:1;color:currentColor;opacity:.14;pointer-events:none;user-select:none}
.fa-seal{position:relative;flex:0 0 auto;display:grid;place-items:center;border-radius:50%;overflow:hidden;background:#FBF8F0;border:2px solid var(--bk-gold);box-shadow:0 10px 22px -10px rgba(0,0,0,.55)}
.fa-seal img{width:84%!important;height:84%!important;max-width:none!important;object-fit:contain}
.fa-seal b{font-family:var(--bk-font-display);font-weight:800;line-height:1;color:var(--bk-accent-strong)}
.fa-seal--xl{width:88px;height:88px;border-width:3px}.fa-seal--xl b{font-size:2.4rem}
.fa-seal--md{width:84px;height:84px;border-width:3px}.fa-seal--md b{font-size:2.3rem}
.fa-seal--sm{width:52px;height:52px}.fa-seal--sm b{font-size:1.5rem}
.fa-seal--lg{width:112px;height:112px;border-width:3px;box-shadow:0 0 0 9px rgba(255,255,255,.14),0 18px 34px -12px rgba(0,0,0,.5)}.fa-seal--lg b{font-size:2.8rem}
.fp-stage .fa-ph{display:grid;place-items:start center;padding-top:27%}
.fp-stage .fa-ph-l{display:none}
.fp-logo{align-self:flex-start;margin-top:calc(-42px - clamp(18px,2.4vw,26px))}
.fp-logo .fa-seal{border-color:#fff;box-shadow:0 10px 22px -8px rgba(0,0,0,.6),0 0 0 1.5px var(--bk-gold)}
.fp-typerow{display:flex;align-items:center;justify-content:space-between;gap:12px}
.fp-badge{position:absolute;z-index:2;top:30px;inset-inline:0;margin-inline:auto;width:max-content;pointer-events:none;display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:var(--bk-r-pill);
  font-family:var(--bk-font-ui);font-size:.78rem;font-weight:800;background:var(--bk-grad-gold);color:var(--bk-gold-ink);box-shadow:0 6px 16px rgba(0,0,0,.28)}
.fp-badge-top{background:var(--bk-accent-fill);color:var(--bk-accent-ink)}
.fp-badge-rec,.fp-badge-new{background:var(--bk-gold-soft);color:var(--bk-gold-strong)}

.fp-info{position:absolute;inset-inline:0;bottom:0;z-index:2;display:flex;flex-direction:column;gap:10px;padding:clamp(18px,2.4vw,26px) clamp(22px,3vw,32px) clamp(22px,3vw,28px);background:rgba(14,18,8,.94);border-radius:30px 30px 0 0;pointer-events:none}
.fp-info a,.fp-info button{pointer-events:auto}
.fp-info > *{opacity:0;translate:0 16px;transition:opacity .25s ease,translate .25s ease}
.fp-panel.is-on .fp-info > *{opacity:1;translate:0 0;transition:opacity .7s var(--fp-ease) calc(.4s + var(--k,0)*.08s),translate .9s var(--fp-ease) calc(.4s + var(--k,0)*.08s)}
.fp-namerow{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.fp-panel .fp-name{font-size:clamp(1.8rem,1.3rem + 1.4vw,2.5rem);line-height:1.2;color:#fff;text-wrap:balance}
.fp-fav{flex:0 0 auto;width:46px;height:46px;border-radius:50%;display:grid;place-items:center;border:1px solid rgba(255,255,255,.4);background:rgba(255,255,255,.14);color:#fff;cursor:pointer;
  transition:transform .3s var(--bk-spring),background .3s,color .3s;position:relative;z-index:3}
.fp-fav:hover{transform:scale(1.08);background:rgba(255,255,255,.26)}
.fp-fav .heart-on{display:none}
.fp-fav.is-on{color:#ff9aa5}
.fp-fav.is-on .heart-off{display:none}
.fp-fav.is-on .heart-on{display:block}
.fp-sub{display:flex;flex-wrap:wrap;gap:4px 16px;margin:0;font-family:var(--bk-font-ui);font-size:.92rem;color:rgba(255,255,255,.88)}
.fp-sub svg{color:var(--bk-gold)}
.fp-panel .fp-open.on{color:#BDE6A3}
.fp-panel .fp-open.off{color:#FFB8B0}
.fp-svcs{margin:0;font-family:var(--bk-font-ui);font-size:.88rem;color:rgba(255,255,255,.78);display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden}
.fp-offer{align-self:flex-start;position:relative;z-index:3}
.fp-foot{display:flex;align-items:flex-end;justify-content:space-between;gap:14px;margin-top:6px;padding-top:16px;border-top:1px solid rgba(255,255,255,.24)}
.fp-price{display:flex;flex-direction:column;line-height:1.25;font-family:var(--bk-font-ui)}
.fp-price small{font-size:.78rem;color:rgba(255,255,255,.72)}
.fp-price b{font-family:var(--bk-font-display);font-weight:700;font-size:1.55rem;color:#E9CD8E;white-space:nowrap}
.fp-price b.is-text{font-family:'Tajawal',var(--bk-font-ui);font-size:1.15rem}
.fp-book{position:relative;z-index:3;padding:13px 24px}

/* first-view reveal: the arch rises, the gold arch slides out, rows settle in */
.fp-js:not(.is-seen) .fp-stagebox{visibility:hidden}
.fp-js:not(.is-seen) .fp-stagebox::before,.fp-js:not(.is-seen) .fp-stagebox::after{translate:0 0;opacity:0}
.fp-js:not(.is-seen) .fp-row{opacity:0;transform:translateY(14px)}

/* ≤1023px: the index steps aside; the arches become a snap carousel */
@media (max-width:1023px){
  .fp-side{display:contents}
  .fp-head{order:1}
  .fp-stagebox{order:2;max-width:none;margin-inline:calc(var(--bk-gutter) * -1);width:auto}
  .fp-all{order:3;justify-self:start}
  .fp-index{display:none}
  .fp-stagebox::before,.fp-stagebox::after{display:none}
  .fp-stage{--fp-arch:110px 28px 28px 28px;display:flex;gap:14px;aspect-ratio:auto;border-radius:0;box-shadow:none;background:none;overflow-x:auto;overflow-y:hidden;
    scroll-snap-type:x mandatory;scroll-padding-inline:var(--bk-gutter);padding:6px var(--bk-gutter) 26px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
  .fp-stage::-webkit-scrollbar{display:none}
  .fp-panel{position:relative;inset:auto;visibility:visible;flex:0 0 min(78vw,340px);aspect-ratio:4/5.7;border-radius:var(--fp-arch);overflow:hidden;scroll-snap-align:start;
    box-shadow:0 22px 40px -24px rgba(28,36,14,.55);background:#2a3320}
  .fp.is-seen .fp-panel.is-on{animation:none}
  .fp-art .fa-img{transform:none}
  .fp-info > *{opacity:1;translate:none;transition:none}
  .fp-panel .fp-name{font-size:1.6rem}
  .fp-stage .fa-ph{padding-top:19%}
  .fp-stage .fa-seal--lg{width:80px;height:80px;border-width:2px;box-shadow:0 0 0 6px rgba(255,255,255,.14),0 14px 26px -10px rgba(0,0,0,.5)}
  .fp-stage .fa-seal--lg b{font-size:2rem}
  .fp-badge{top:22px}
  .fp-book{padding:12px 20px}
  .fp-price b{font-size:1.35rem}
}
@media (min-width:1024px){
  .fp-grid{grid-template-columns:minmax(0,1fr) minmax(360px,500px);gap:clamp(56px,7vw,116px);align-items:center}
}
@media (prefers-reduced-motion:reduce){
  .fp *,.fp *::before,.fp *::after{animation:none!important;transition:none!important}
  .fp-js:not(.is-seen) .fp-stagebox{visibility:visible}
  .fp-js:not(.is-seen) .fp-row{opacity:1;transform:none}
  .fp-info > *{opacity:1;translate:none}
}
</style>
</x-slot:styles>

<x-slot:scripts>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script>
window.BK_MAP = {
  url:      @json(route('front.map.branches')),
  css:      @json(asset('vendor/leaflet/leaflet.css')),
  js:       @json(asset('vendor/leaflet/leaflet.js')),
  currency: @json($currency),
  ar:       @json($isAr)
};
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

{{-- GSAP motion layer (additive; skipped if blocked or reduced-motion) --}}
<script>
(function () {
  'use strict';
  if (!window.gsap || !window.ScrollTrigger) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var g = gsap, isRtl = document.documentElement.dir === 'rtl';
  g.registerPlugin(ScrollTrigger);
  var q = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

  /* Hero: staged entrance + parallax */
  var hero = document.querySelector('[data-hero-rotator]');
  if (hero) {
    g.timeline({ defaults: { ease: 'power3.out' } })
      .from('.bkf-hero-eyebrow', { y: 24, opacity: 0, duration: .7 })
      .from('.bkf-hero-title .l1', { y: 50, opacity: 0, duration: .9 }, '-=.45')
      .from('.bkf-hero-title .l2', { y: 50, opacity: 0, duration: .9 }, '-=.7')
      .from('.bkf-hero-lead', { y: 26, opacity: 0, duration: .8 }, '-=.6')
      .from('.bkf-hsearch', { y: 34, opacity: 0, scale: .97, duration: .9 }, '-=.55')
      .from('.bkf-hero-chips > *', { y: 14, opacity: 0, stagger: .07, duration: .5 }, '-=.5')
      .from('.bkf-hero-early, .bkf-hero-dots, .bkf-hero-arrow', { opacity: 0, duration: .7 }, '-=.3');
  }

  g.from('.bkf-trust-item', { y: 20, opacity: 0, stagger: .08, duration: .7, ease: 'power2.out',
    scrollTrigger: { trigger: '.bkf-trust', start: 'top 92%', once: true } });

  /* Rails: staggered pop-in of pills / venue cards */
  q('.bkf-rail, .bkf-catgrid').forEach(function (rail) {
    var kids = q('.bkf-cat-card, [data-venue]', rail);
    if (!kids.length || rail.offsetParent === null) return;
    g.from(kids, { y: 36, opacity: 0, scale: .96, stagger: .06, duration: .7, ease: 'power3.out', clearProps: 'transform,opacity',
      scrollTrigger: { trigger: rail, start: 'top 88%', once: true } });
  });

  /* Title accent word */
  q('.bkf-title .em').forEach(function (el) {
    g.from(el, { y: 26, opacity: 0, duration: .8, ease: 'power3.out', clearProps: 'transform,opacity',
      scrollTrigger: { trigger: el, start: 'top 92%', once: true } });
  });

  /* Book in minutes: line draws on scroll, steps light up in order, icons pop */
  var how = document.getElementById('how');
  if (how) {
    var wrap = how.querySelector('.bkf-steps'), steps = q('.bkf-step', how), line = how.querySelector('.bkf-steps-line i');
    if (line) line.style.setProperty('--steps-origin', isRtl ? 'right' : 'left');
    g.from(q('.bkf-step-ic', how), { scale: .4, rotate: -12, opacity: 0, stagger: .18, duration: .8, ease: 'back.out(1.8)', clearProps: 'transform,opacity',
      scrollTrigger: { trigger: wrap, start: 'top 82%', once: true } });
    ScrollTrigger.create({ trigger: wrap, start: 'top 75%', end: 'bottom 60%',
      onUpdate: function (self) {
        steps.forEach(function (el, i) { el.classList.toggle('is-lit', self.progress >= i / steps.length + .04); });
      } });
    if (line) g.to(line, { scaleX: 1, ease: 'none',
      scrollTrigger: { trigger: wrap, start: 'top 75%', end: 'bottom 60%', scrub: true } });
    steps.forEach(function (el) {
      var ic = el.querySelector('.bkf-step-ic');
      el.addEventListener('mouseenter', function () { g.to(ic, { y: -6, duration: .3, ease: 'power2.out' }); });
      el.addEventListener('mouseleave', function () { g.to(ic, { y: 0, duration: .4, ease: 'power2.out' }); });
    });
  }

  g.from('.bkf-review', { y: 40, opacity: 0, stagger: .14, duration: .9, ease: 'power3.out', clearProps: 'transform,opacity',
    scrollTrigger: { trigger: '.bkf-review', start: 'top 88%', once: true } });
  g.from('.bkf-faq-item', { x: isRtl ? 30 : -30, opacity: 0, stagger: .08, duration: .6, ease: 'power2.out', clearProps: 'transform,opacity',
    scrollTrigger: { trigger: '.bkf-faq', start: 'top 88%', once: true } });
  g.to('.bkf-cta-blob.a', { y: -30, x: 20, duration: 5, ease: 'sine.inOut', repeat: -1, yoyo: true });
  g.to('.bkf-cta-blob.b', { y: 26, x: -24, duration: 6, ease: 'sine.inOut', repeat: -1, yoyo: true });

  window.addEventListener('load', function () { ScrollTrigger.refresh(); });
})();
</script>
</x-slot:scripts>
</x-front.layout>
