@props([
    'title'       => 'Booksy',
    'description' => null,
    'variant'     => 'customer',   // customer | business
    'bodyClass'   => '',
    'active'      => null,
])
@php
    $isAr = app()->getLocale() === 'ar';
    $dir  = $isAr ? 'rtl' : 'ltr';
    $lang = $isAr ? 'ar' : 'en';
    $theme = request()->cookie('bk_front_theme', 'light');
    $theme = in_array($theme, ['light','dark'], true) ? $theme : 'light';

    $brand = $isAr ? 'بوكسي' : 'Booksy';

    // Nav links per variant
    if ($variant === 'business') {
        $links = [
            ['label' => $isAr ? 'المزايا'        : 'Features',    'href' => '#features'],
            ['label' => $isAr ? 'قريبا'        : 'coming soon',    'href' => '#soon'],
            ['label' => $isAr ? 'لماذا بوكسي'    : 'Why Booksy',  'href' => '#why'],
            ['label' => $isAr ? 'الأسئلة الشائعة': 'FAQ',         'href' => '#faq'],
            ['label' => $isAr ? 'مجاني'          : 'Free',        'href' => '#free'],
        ];
    } else {
        $links = [
            ['label' => $isAr ? 'الفئات'   : 'Categories', 'href' => '#categories'],
            ['label' => $isAr ? 'استكشف'   : 'Discover',   'href' => '#discover'],
            ['label' => $isAr ? 'كيف يعمل' : 'How it works','href' => '#how'],
            ['label' => $isAr ? 'للأعمال'  : 'For business','href' => route('front.business')],
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}" data-bk-theme="{{ $theme }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title }}</title>
@if($description)<meta name="description" content="{{ $description }}">@endif
<meta name="theme-color" content="{{ $theme === 'dark' ? '#0F1413' : '#F6F5F2' }}">

{{-- Prevent theme flash: reconcile server cookie with client preference before paint --}}
<script>
(function(){try{var t=localStorage.getItem('bk_front_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-bk-theme',t);}}catch(e){}})();
</script>

<link rel="preconnect" href="{{ url('/') }}">
<link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/css/booksy-front.css') }}?v={{ @filemtime(public_path('frontend/css/booksy-front.css')) ?: '1' }}">
<link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}">
{{-- If JS is unavailable, never hide content behind scroll-reveal --}}
<noscript><style>.bkf-reveal{opacity:1!important;transform:none!important}</style></noscript>
{{ $styles ?? '' }}
</head>
<body class="bkf {{ $bodyClass }}">

{{-- ══════════════  NAV  ══════════════ --}}
<nav class="bkf-nav" data-bkf-nav>
  <div class="bkf-container-wide bkf-nav-inner">
    <a href="{{ $variant === 'business' ? route('front.business') : route('front.index') }}" class="bkf-brand">
      {{ $brand }}<span class="bkf-brand-dot">.</span>
      @if($variant === 'business')<span class="bkf-brand-sub">{{ $isAr ? 'للأعمال' : 'Business' }}</span>@endif
    </a>

    <div class="bkf-nav-menu" data-menu>
      <ul class="bkf-nav-links">
        @foreach($links as $l)
          <li><a href="{{ $l['href'] }}" @class(['is-active' => $active === $l['href']])>{{ $l['label'] }}</a></li>
        @endforeach
      </ul>
      <div class="bkf-nav-actions">
        <a href="{{ route('locale.switch', $isAr ? 'en' : 'ar') }}" class="bkf-nav-lang">{{ $isAr ? 'EN' : 'عربي' }}</a>
        <button type="button" class="bkf-nav-theme" data-theme-toggle aria-label="{{ $isAr ? 'تبديل المظهر' : 'Toggle theme' }}" aria-pressed="{{ $theme === 'dark' ? 'true' : 'false' }}">
          <x-icon name="sun" :size="18" class="bkf-ic-sun"/>
          <x-icon name="moon" :size="18" class="bkf-ic-moon"/>
        </button>
        @if($variant === 'business')
          <a href="{{ route('company.login') }}" class="bkf-btn bkf-btn-ghost bkf-nav-btn">{{ $isAr ? 'تسجيل الدخول' : 'Log in' }}</a>
          <a href="{{ route('company.register') }}" class="bkf-btn bkf-btn-primary bkf-nav-btn">
            {{ $isAr ? 'ابدأ الآن' : 'Get started' }}<x-icon name="arrow-right" :size="18"/>
          </a>
        @else
          <a href="{{ route('front.business') }}" class="bkf-btn bkf-btn-ghost bkf-nav-btn">{{ $isAr ? 'أنا صاحب صالون' : 'For business' }}</a>
          <a href="#discover" class="bkf-btn bkf-btn-primary bkf-nav-btn">
            {{ $isAr ? 'احجز الآن' : 'Book now' }}<x-icon name="calendar" :size="18"/>
          </a>
        @endif
      </div>
    </div>

    <button type="button" class="bkf-nav-burger" data-menu-toggle aria-label="{{ $isAr ? 'القائمة' : 'Menu' }}" aria-expanded="false">
      <x-icon name="menu" :size="24" class="bkf-ic-menu"/>
      <x-icon name="x" :size="24" class="bkf-ic-close"/>
    </button>
  </div>
</nav>

{{-- ══════════════  CONTENT  ══════════════ --}}
<main>{{ $slot }}</main>

{{-- ══════════════  FOOTER  ══════════════ --}}
<footer class="bkf-footer">
  <div class="bkf-container-wide">
    <div class="bkf-footer-grid">
      <div class="bkf-footer-brand">
        <a href="{{ route('front.index') }}" class="bkf-brand bkf-brand-lg">{{ $brand }}<span class="bkf-brand-dot">.</span></a>
        <p class="bkf-footer-tag">{{ $isAr ? 'منصّة حجز وإدارة الصالونات ومراكز التجميل — احجز موعدك في ثوانٍ، وأدر عملك باحتراف.' : 'The booking & management platform for salons and beauty studios — book in seconds, run your business like a pro.' }}</p>
        <div class="bkf-footer-social">
          <a href="#" aria-label="Instagram" class="bkf-footer-soc"><x-icon name="globe" :size="18"/></a>
          <a href="#" aria-label="Facebook" class="bkf-footer-soc"><x-icon name="message" :size="18"/></a>
          <a href="#" aria-label="Phone" class="bkf-footer-soc"><x-icon name="phone" :size="18"/></a>
        </div>
      </div>
      <div class="bkf-footer-col">
        <h4>{{ $isAr ? 'للعملاء' : 'For customers' }}</h4>
        <a href="{{ route('front.index') }}#discover">{{ $isAr ? 'استكشف الأماكن' : 'Discover venues' }}</a>
        <a href="{{ route('front.index') }}#categories">{{ $isAr ? 'الفئات' : 'Categories' }}</a>
        <a href="{{ route('front.index') }}#how">{{ $isAr ? 'كيف يعمل' : 'How it works' }}</a>
      </div>
      <div class="bkf-footer-col">
        <h4>{{ $isAr ? 'للأعمال' : 'For business' }}</h4>
        <a href="{{ route('front.business') }}#features">{{ $isAr ? 'المزايا' : 'Features' }}</a>
        <a href="{{ route('front.business') }}#free">{{ $isAr ? 'مجاني' : 'Free' }}</a>
        <a href="{{ route('company.register') }}">{{ $isAr ? 'سجّل صالونك' : 'List your business' }}</a>
      </div>
      <div class="bkf-footer-col">
        <h4>{{ $isAr ? 'الشركة' : 'Company' }}</h4>
        <a href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a>
        <a href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل معنا' : 'Contact' }}</a>
        <a href="{{ route('company.login') }}">{{ $isAr ? 'دخول الشركات' : 'Business login' }}</a>
      </div>
    </div>
    <div class="bkf-footer-bar">
      <span>© {{ date('Y') }} {{ $brand }}. {{ $isAr ? 'كل الحقوق محفوظة.' : 'All rights reserved.' }}</span>
      <div class="bkf-footer-bar-links">
        <a href="#">{{ $isAr ? 'الخصوصية' : 'Privacy' }}</a>
        <a href="#">{{ $isAr ? 'الشروط' : 'Terms' }}</a>
      </div>
    </div>
  </div>
</footer>

<script src="{{ asset('frontend/js/booksy-front.js') }}?v={{ @filemtime(public_path('frontend/js/booksy-front.js')) ?: '1' }}" defer></script>
{{ $scripts ?? '' }}
</body>
</html>
