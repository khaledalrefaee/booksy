@php
    // @section('title', '…') is already HTML-escaped by Blade, so these are printed raw below; defaults are escaped here.
    $pageTitle = trim($__env->yieldContent('title')) ?: 'GlowRez';
    $pageDesc  = trim($__env->yieldContent('description')) ?: e($t(
        'GlowRez يجمع الحجوزات والعملاء والموظفين والإدارة في تجربة واحدة لأعمال الجمال والرفاهية.',
        'GlowRez brings bookings, clients, staff and management into one place for beauty & wellness businesses.'
    ));
    $robots = trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large';
    $iconV  = fn ($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: '1');
    $ver    = fn ($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: '1');
@endphp
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="theme-color" content="#0F1209">
<meta name="color-scheme" content="dark light">

<title>{!! $pageTitle !!}</title>
<meta name="description" content="{!! $pageDesc !!}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ url()->current() }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="GlowRez">
<meta property="og:title" content="{!! $pageTitle !!}">
<meta property="og:description" content="{!! $pageDesc !!}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('images/og-cover.jpg') }}">
<meta property="og:locale" content="{{ $isAr ? 'ar_AR' : 'en_US' }}">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="{{ $iconV('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $iconV('icons/favicon-32.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $iconV('icons/apple-touch-icon.png') }}">

{{-- Theme before first paint (no flash). A first visit is always dark; only a choice made on THESE pages is remembered. --}}
<script>(function(){var t='dark';try{var s=localStorage.getItem('gl_theme');if(s==='light'||s==='dark')t=s;}catch(e){}document.documentElement.setAttribute('data-bk-theme',t);var m=document.querySelector('meta[name=theme-color]');if(m)m.content=t==='light'?'#F6F3EA':'#0F1209';})();</script>

@include('partials.clarity')

<link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
<link href="{{ asset('fonts/glowrez-type.css') }}?v={{ @filemtime(public_path('fonts/glowrez-type.css')) ?: '1' }}" rel="stylesheet">
<link href="{{ $ver('frontend/css/launch.css') }}" rel="stylesheet">
@stack('head')
</head>
<body class="gl @yield('body-class')">
@include('leads.partials.stage')

<a class="gl-skip" href="#main">{{ $t('تخطَّ إلى المحتوى', 'Skip to content') }}</a>

<header class="gl-top">
  <a href="{{ route('leads.welcome') }}" class="gl-logo" aria-label="GlowRez">
    <img class="gl-logo-d" src="{{ $ver('images/glowrez-logo-dark_1.webp') }}" width="1500" height="424" alt="GlowRez غلوريز">
    <img class="gl-logo-l" src="{{ $ver('images/glowrez-logo-light_1.webp') }}" width="1500" height="424" alt="" aria-hidden="true">
  </a>
  <div class="gl-top-actions">
    <a href="{{ route('locale.switch', $isAr ? 'en' : 'ar') }}" class="gl-pill" hreflang="{{ $isAr ? 'en' : 'ar' }}">{{ $isAr ? 'EN' : 'عربي' }}</a>
    <button type="button" class="gl-pill gl-pill--icon gl-theme" data-theme-toggle aria-label="{{ $t('تبديل المظهر: داكن / فاتح', 'Switch theme: dark / light') }}">
      <x-leads.icon name="sun" :size="20" class="gl-ic-sun"/><x-leads.icon name="moon" :size="20" class="gl-ic-moon"/>
    </button>
    @if($waUrl)
      <a href="{{ $waUrl }}" class="gl-pill gl-pill--wa" target="_blank" rel="noopener" data-wa="header" aria-label="WhatsApp">
        <x-leads.icon name="whatsapp" :size="18"/><span>WhatsApp</span>
      </a>
    @endif
  </div>
</header>

<main id="main" class="gl-main">
  @yield('content')
</main>

<footer class="gl-foot">
  <div class="gl-foot-in ">
    <p class="gl-foot-copy ">{{ $t('جميع الحقوق محفوظة لشركة غلوريز © 2026', 'All rights reserved to GlowRez © 2026') }}
    <nav class="gl-foot-links" aria-label="{{ $t('روابط', 'Links') }}">
      @if($igUrl)
        <a href="{{ $igUrl }}" target="_blank" rel="noopener" class="gl-foot-ig">
          <x-leads.icon name="instagram" :size="18"/><span>{{ $t('تابع GlowRez', 'Follow GlowRez') }}</span>
        </a>
      @endif
      {{-- <a href="{{ route('front.privacy') }}">{{ $t('الخصوصية', 'Privacy') }}</a>
      <a href="{{ route('front.terms') }}">{{ $t('الشروط', 'Terms') }}</a> --}}
    </nav>
  </div>
</footer>

<script>
(function(){var b=document.querySelector('[data-theme-toggle]');if(!b)return;b.addEventListener('click',function(){var d=document.documentElement,n=d.getAttribute('data-bk-theme')==='light'?'dark':'light';d.setAttribute('data-bk-theme',n);try{localStorage.setItem('gl_theme',n);}catch(e){}var m=document.querySelector('meta[name=theme-color]');if(m)m.content=n==='light'?'#F6F3EA':'#0F1209';});})();
</script>
@stack('scripts')
</body>
</html>
