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
        {{-- venue type: custom listbox (value goes to the hidden input) --}}
        <div class="bkf-hsearch-field bkf-dd" data-dd>
          <x-icon name="grid" :size="18"/>
          <input type="hidden" name="category" id="bkf-cat" value="">
          <button type="button" class="bkf-dd-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ $t('نوع المكان', 'Venue type') }}">
            <span class="bkf-dd-val" data-dd-val>{{ $t('كل الأنواع', 'All types') }}</span>
            <x-icon name="chevron-down" :size="16"/>
          </button>
          <div class="bkf-dd-menu" role="listbox" tabindex="-1">
            <button type="button" role="option" class="bkf-dd-opt is-sel" aria-selected="true" data-value="">
              <span class="bkf-dd-ic is-all"><x-icon name="grid" :size="16"/></span>
              <span class="bkf-dd-n">{{ $t('كل الأنواع', 'All types') }}</span>
              <x-icon name="check" :size="16" class="bkf-dd-ck"/>
            </button>
            @foreach($categories as $cat)
              @php $ddImg = $cat->image ? asset('storage/'.ltrim($cat->image, '/')) : null; $ddName = $isAr ? $cat->name_ar : $cat->name_en; @endphp
              <button type="button" role="option" class="bkf-dd-opt" aria-selected="false" data-value="{{ $cat->slug }}">
                <span class="bkf-dd-ic">@if($ddImg)<img src="{{ $ddImg }}" alt="" loading="lazy">@else<b style="background:{{ ['#4a6a34','#b4502f','#7a4585','#1f7378','#b07a1c','#b04062','#3f52a3','#7a6244'][abs(crc32($cat->slug)) % 8] }}">{{ mb_substr($ddName, 0, 1) }}</b>@endif</span>
                <span class="bkf-dd-n">{{ $ddName }}</span>
                <span class="bkf-dd-c">{{ $cat->companies_count }}</span>
                <x-icon name="check" :size="16" class="bkf-dd-ck"/>
              </button>
            @endforeach
          </div>
        </div>
        @if($cities->isNotEmpty())
        <span class="bkf-hsearch-div"></span>
        {{-- city: only governorates that have live venues --}}
        <div class="bkf-hsearch-field bkf-dd" data-dd>
          <x-icon name="map-pin" :size="18"/>
          <input type="hidden" name="city" id="bkf-city" value="">
          <button type="button" class="bkf-dd-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ $t('اختر المدينة', 'Choose city') }}">
            <span class="bkf-dd-val" data-dd-val>{{ $t('كل المدن', 'All cities') }}</span>
            <x-icon name="chevron-down" :size="16"/>
          </button>
          <div class="bkf-dd-menu" role="listbox" tabindex="-1">
            <button type="button" role="option" class="bkf-dd-opt is-sel" aria-selected="true" data-value="">
              <span class="bkf-dd-ic is-all"><x-icon name="map-pin" :size="16"/></span>
              <span class="bkf-dd-n">{{ $t('كل المدن', 'All cities') }}</span>
              <x-icon name="check" :size="16" class="bkf-dd-ck"/>
            </button>
            @foreach($cities as $city)
              <button type="button" role="option" class="bkf-dd-opt" aria-selected="false" data-value="{{ $city['name'] }}">
                <span class="bkf-dd-ic is-city"><x-icon name="map-pin" :size="16"/></span>
                <span class="bkf-dd-n">{{ $city['name'] }}</span>
                <span class="bkf-dd-c">{{ $city['count'] }}</span>
                <x-icon name="check" :size="16" class="bkf-dd-ck"/>
              </button>
            @endforeach
          </div>
        </div>
        @endif
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
<script>
/* hero custom dropdowns: click / keyboard (↑ ↓ Enter Esc Home End) / outside-click close */
(function () {
  var dds = [].slice.call(document.querySelectorAll('[data-dd]'));
  if (!dds.length) return;
  function close(dd) { dd.classList.remove('is-open'); dd.querySelector('.bkf-dd-btn').setAttribute('aria-expanded', 'false'); }
  function closeAll(except) { dds.forEach(function (d) { if (d !== except) close(d); }); }
  dds.forEach(function (dd) {
    var btn = dd.querySelector('.bkf-dd-btn'), menu = dd.querySelector('.bkf-dd-menu'),
        input = dd.querySelector('input[type=hidden]'), val = dd.querySelector('[data-dd-val]'),
        opts = [].slice.call(dd.querySelectorAll('.bkf-dd-opt'));
    function open() {
      closeAll(dd);
      dd.classList.remove('is-up');
      dd.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      // not enough room below inside the hero → open upward
      var hero = dd.closest('.bkf-hero') || document.body, hb = hero.getBoundingClientRect(), bb = btn.getBoundingClientRect();
      var need = Math.min(menu.scrollHeight, 320) + 24;
      if (hb.bottom - bb.bottom < need && bb.top - hb.top > hb.bottom - bb.bottom) dd.classList.add('is-up');
      var sel = menu.querySelector('.is-sel'); if (sel) { menu.scrollTop = Math.max(0, sel.offsetTop - 60); }
    }
    function pick(opt) {
      opts.forEach(function (o) { var on = o === opt; o.classList.toggle('is-sel', on); o.setAttribute('aria-selected', on); });
      input.value = opt.dataset.value;
      val.textContent = opt.querySelector('.bkf-dd-n').textContent;
      close(dd); btn.focus();
    }
    btn.addEventListener('click', function () { dd.classList.contains('is-open') ? close(dd) : open(); });
    opts.forEach(function (o) { o.addEventListener('click', function () { pick(o); }); });
    function move(step) {
      var i = opts.indexOf(document.activeElement);
      if (i < 0) i = opts.findIndex(function (o) { return o.classList.contains('is-sel'); });
      i = Math.max(0, Math.min(opts.length - 1, i + step));
      opts[i].focus();
    }
    dd.addEventListener('keydown', function (e) {
      var isOpen = dd.classList.contains('is-open');
      if (e.key === 'Escape' && isOpen) { e.preventDefault(); close(dd); btn.focus(); }
      else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault(); if (!isOpen) open();
        move(e.key === 'ArrowDown' ? 1 : -1);
      }
      else if (isOpen && e.key === 'Home') { e.preventDefault(); opts[0].focus(); }
      else if (isOpen && e.key === 'End') { e.preventDefault(); opts[opts.length - 1].focus(); }
      else if (e.key === 'Tab') close(dd);
    });
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('[data-dd]')) closeAll(); });
})();
</script>
