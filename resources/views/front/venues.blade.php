@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn($ar, $en) => $isAr ? $ar : $en;

    $catIcon = function ($slug) {
        $slug = strtolower($slug ?? '');
        $map = ['hair'=>'scissors','salon'=>'scissors','barber'=>'user','spa'=>'sparkles','massage'=>'sparkles',
                'clinic'=>'shield','skin'=>'sparkles','laser'=>'zap','beauty'=>'sparkles',
                'makeup'=>'sparkles','nail'=>'heart','lash'=>'star','brow'=>'star'];
        foreach ($map as $k => $v) { if (str_contains($slug, $k)) return $v; }
        return 'grid';
    };

    // Page heading reflects context: category → its name, search → the query, else all.
    $catName = $category ? ($isAr ? $category->name_ar : $category->name_en) : null;
    $pageTitle = $catName
        ?: ($search !== '' ? $t('نتائج البحث', 'Search results') : $t('كل الأماكن', 'All venues'));

    // URL helpers that preserve the other active filters.
    $sortUrl = fn($s) => url()->current().'?'.http_build_query(array_merge(request()->except(['page','sort']), $s === 'featured' ? [] : ['sort' => $s]));
    $catUrl  = fn($slug) => ($slug
        ? route('front.category', $slug)
        : route('front.venues')).'?'.http_build_query(request()->only(['search','city','sort']));

    $sorts = [
        'featured' => ['ic'=>'sparkles', 'label'=>$t('مختارة',   'Featured')],
        'rating'   => ['ic'=>'star',     'label'=>$t('الأعلى تقييماً','Top rated')],
        'booked'   => ['ic'=>'flame',    'label'=>$t('الأكثر حجزاً',  'Most booked')],
        'new'      => ['ic'=>'zap',      'label'=>$t('الأحدث',        'Newest')],
    ];

    $total = $branches->total();
@endphp

<x-front.layout
    variant="customer"
    :title="($catName ?: $t('استكشف أماكن الجمال والعناية', 'Explore Beauty & Wellness Venues')).' | GlowRez'"
    :description="$catName
        ? $t('تصفّح واحجز أفضل أماكن '.$catName.' قربك على GlowRez — بحث، فلاتر، تقييمات وأسعار واضحة.', 'Browse and book the best '.$catName.' venues near you on GlowRez — search, filters, reviews and clear prices.')
        : $t('تصفّح واحجز في أفضل أماكن الجمال والعناية قربك على GlowRez — بحث، فلاتر، تقييمات وأسعار واضحة.', 'Browse and book the best beauty & wellness venues near you on GlowRez — search, filters, reviews and clear prices.')">

<x-slot:styles>
@if($branches->previousPageUrl())<link rel="prev" href="{{ $branches->previousPageUrl() }}">@endif
@if($branches->nextPageUrl())<link rel="next" href="{{ $branches->nextPageUrl() }}">@endif
</x-slot:styles>

{{-- ══════════════ HEADER + SEARCH ══════════════ --}}
<section class="bkf-vpage-head">
  <div class="bkf-container-wide">
    <nav class="bkf-crumbs" aria-label="{{ $t('مسار التنقل', 'Breadcrumb') }}">
      <a href="{{ route('front.index') }}">{{ $t('الرئيسية', 'Home') }}</a>
      <x-icon name="chevron-{{ $isAr ? 'right' : 'right' }}" :size="14"/>
      <a href="{{ route('front.venues') }}">{{ $t('الأماكن', 'Venues') }}</a>
      @if($catName)<x-icon name="chevron-right" :size="14"/><span>{{ $catName }}</span>@endif
    </nav>

    <div class="bkf-vpage-head-row">
      <div>
        <span class="bkf-eyebrow">{{ $catName ? $t('فئة', 'Category') : $t('استكشف أماكن الجمال والعناية', 'Explore Beauty & Wellness Venues') }}</span>
        <h1 class="bkf-vpage-title">{{ $pageTitle }}</h1>
        <p class="bkf-vpage-count bkf-tnum" aria-live="polite">
          {{ $total }} {{ trans_choice($isAr ? 'مكان|أماكن' : 'venue|venues', $total) }}
          @if($search !== '') · <span class="bkf-vpage-q">“{{ $search }}”</span>@endif
        </p>
      </div>
    </div>

    <form class="bkf-searchbar bkf-vpage-search" action="{{ url()->current() }}" method="GET" role="search">
      @if($sort !== 'featured')<input type="hidden" name="sort" value="{{ $sort }}">@endif
      <div class="bkf-sb-field">
        <x-icon name="search" :size="18"/>
        <input type="text" name="search" value="{{ $search }}" placeholder="{{ $t('خدمة أو اسم مكان…', 'Service or venue…') }}" autocomplete="off" aria-label="{{ $t('ابحث', 'Search') }}">
      </div>
      <div class="bkf-sb-field">
        <x-icon name="map-pin" :size="18"/>
        <input type="text" name="city" value="{{ $city }}" list="bkf-cities" placeholder="{{ $t('المدينة', 'City') }}" autocomplete="off" aria-label="{{ $t('المدينة', 'City') }}">
        <datalist id="bkf-cities">@foreach($cities as $c)<option value="{{ $c }}"></option>@endforeach</datalist>
      </div>
      <button type="submit" class="bkf-btn bkf-btn-primary"><x-icon name="search" :size="18"/>{{ $t('ابحث', 'Search') }}</button>
    </form>
  </div>
</section>

{{-- ══════════════ FILTERS ══════════════ --}}
<div class="bkf-vpage-filters">
  <div class="bkf-container-wide">
    {{-- Category chips --}}
    <div class="bkf-rail bkf-vpage-cats" aria-label="{{ $t('الفئات', 'Categories') }}">
      <a href="{{ $catUrl(null) }}" class="bkf-chip @if(!$activeCategory) is-active @endif">{{ $t('الكل', 'All') }}</a>
      @foreach($categories as $cat)
        @continue(!$cat->companies_count)
        <a href="{{ $catUrl($cat->slug) }}" class="bkf-chip @if($activeCategory === $cat->slug) is-active @endif">
          <x-icon name="{{ $catIcon($cat->slug) }}" :size="14"/>{{ $isAr ? $cat->name_ar : $cat->name_en }}
        </a>
      @endforeach
    </div>

    {{-- Sort --}}
    <div class="bkf-filterbar bkf-vpage-sort" role="tablist" aria-label="{{ $t('ترتيب', 'Sort') }}">
      @foreach($sorts as $key => $s)
        <a href="{{ $sortUrl($key) }}" class="bkf-fbtn @if($sort === $key) is-active @endif" @if($sort === $key) aria-current="true" @endif>
          <x-icon name="{{ $s['ic'] }}" :size="16"/>{{ $s['label'] }}
        </a>
      @endforeach
    </div>
  </div>
</div>

{{-- ══════════════ RESULTS ══════════════ --}}
<section class="bkf-section" style="padding-top:clamp(20px,3vw,32px)">
  <div class="bkf-container-wide">
    @if($total === 0)
      <div class="bkf-geo-gate" style="justify-content:center;text-align:center">
        <span class="ic"><x-icon name="search" :size="26"/></span>
        <div>
          <b>{{ $t('لا توجد نتائج مطابقة', 'No matching results') }}</b>
          <div class="bkf-geo-note">{{ $t('جرّب كلمة مختلفة أو أزل بعض الفلاتر.', 'Try a different keyword or clear some filters.') }}</div>
        </div>
        <a href="{{ route('front.venues') }}" class="bkf-btn bkf-btn-ghost bkf-btn-sm">{{ $t('عرض كل الأماكن', 'Show all venues') }}</a>
      </div>
    @else
      <div class="bkf-grid bkf-grid-auto" id="venues-grid">
        @include('front.partials.venue-cards-loop', ['cards' => $cards, 'currency' => $currency, 'isAr' => $isAr])
      </div>

      {{-- Hybrid pagination: numbered pager (no-JS + SEO) is replaced by a
           "load more" button when JS is available. --}}
      <div class="bkf-center" style="margin-top:44px">
        <button type="button" class="bkf-btn bkf-btn-soft bkf-btn-lg" id="bkf-loadmore"
                data-next="{{ $branches->currentPage() + 1 }}" hidden>
          {{ $t('عرض المزيد', 'Load more') }}<x-icon name="chevron-down" :size="18"/>
        </button>
      </div>

      @if($branches->hasPages())
      <nav class="bkf-pager" id="bkf-pager" aria-label="{{ $t('صفحات', 'Pagination') }}">
        @if($branches->onFirstPage())
          <span class="bkf-pager-btn is-disabled"><x-icon name="chevron-right" :size="16" class="bkf-flip"/>{{ $t('السابق', 'Prev') }}</span>
        @else
          <a class="bkf-pager-btn" href="{{ $branches->previousPageUrl() }}" rel="prev"><x-icon name="chevron-right" :size="16" class="bkf-flip"/>{{ $t('السابق', 'Prev') }}</a>
        @endif
        <span class="bkf-pager-info bkf-tnum">{{ $t('صفحة', 'Page') }} {{ $branches->currentPage() }} / {{ $branches->lastPage() }}</span>
        @if($branches->hasMorePages())
          <a class="bkf-pager-btn" href="{{ $branches->nextPageUrl() }}" rel="next">{{ $t('التالي', 'Next') }}<x-icon name="chevron-right" :size="16"/></a>
        @else
          <span class="bkf-pager-btn is-disabled">{{ $t('التالي', 'Next') }}<x-icon name="chevron-right" :size="16"/></span>
        @endif
      </nav>
      @endif
    @endif
  </div>
</section>

{{-- customer-auth-modal is centralised in x-front.layout --}}

<x-slot:scripts>
<script>
(function () {
  'use strict';
  var AR = @json($isAr);
  var grid = document.getElementById('venues-grid');
  var more = document.getElementById('bkf-loadmore');
  var pager = document.getElementById('bkf-pager');
  if (!grid || !more) return;

  // JS available → swap the numbered pager for the "load more" button.
  if (more.dataset.next) { more.hidden = false; if (pager) pager.style.display = 'none'; }

  var busy = false;
  more.addEventListener('click', function () {
    if (busy) return; busy = true;
    more.classList.add('is-loading'); more.disabled = true;
    var url = new URL(window.location.href);
    url.searchParams.set('partial', '1');
    url.searchParams.set('page', more.dataset.next);
    fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.text(); })
      .then(function (txt) {
        var data = JSON.parse(txt.replace(/^﻿/, '')); // tolerate a leading BOM
        var tmp = document.createElement('div');
        tmp.innerHTML = data.html;
        var added = [].slice.call(tmp.children);
        added.forEach(function (el) { el.classList.add('bkf-reveal'); grid.appendChild(el); });
        // Reveal + rebind favourites/drag on the new nodes.
        requestAnimationFrame(function () { added.forEach(function (el) { el.classList.add('is-in'); }); });
        if (window.bkPaintFavs) window.bkPaintFavs();
        if (data.hasMore) { more.dataset.next = data.nextPage; }
        else { more.remove(); }
        busy = false; more.classList.remove('is-loading'); more.disabled = false;
      })
      .catch(function () {
        busy = false; more.classList.remove('is-loading'); more.disabled = false;
        more.textContent = AR ? 'تعذّر التحميل — أعد المحاولة' : 'Failed — try again';
      });
  });
})();
</script>
</x-slot:scripts>
</x-front.layout>
