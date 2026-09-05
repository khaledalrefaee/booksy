@props([
    'title'       => 'GlowRez',
    'description' => null,
    'variant'     => 'customer',   // customer | business
    'bodyClass'   => '',
    'active'      => null,
    'mapFab'      => true,         // global "all venues" map FAB (off on single-venue pages)
    'keywords'    => null,         // optional comma-separated keywords
    'ogImage'     => null,         // absolute/relative share image; defaults to brand cover
    'ogType'      => 'website',    // og:type (website | article | product …)
    'noindex'     => false,        // true → keep this page out of search indexes
    'canonical'   => null,         // override canonical URL (defaults to current clean URL)
])
@php
    $isAr = app()->getLocale() === 'ar';
    $dir  = $isAr ? 'rtl' : 'ltr';
    $lang = $isAr ? 'ar' : 'en';
    $theme = request()->cookie('bk_front_theme', 'light');
    $theme = in_array($theme, ['light','dark'], true) ? $theme : 'light';

    $brand = 'GlowRez';

    /* ── SEO computed values ── */
    $siteName     = 'GlowRez';
    $metaDesc     = $description ?: ($isAr
        ? 'غلوريز (GlowRez) — منصة حجز وإدارة أماكن الجمال والعناية في سوريا. احجز موعدك في ثوانٍ وأدر عملك باحتراف.'
        : 'GlowRez — the beauty & wellness booking and management platform in Syria. Book in seconds, run your business like a pro.');

    /* Brand + core keywords always present (helps Arabic brand search: غلوريز / غلو ريز),
       merged ahead of any page-specific keywords passed in. */
    $brandKeywords = 'GlowRez, غلوريز, غلو ريز, Glow Rez, قلوريز';
    $baseKeywords  = $isAr
        ? $brandKeywords.', حجز صالون, حجز مركز تجميل, حجز حلاقة, مواعيد الجمال, سبا, أظافر, عيادات تجميل, سوريا, دمشق'
        : $brandKeywords.', salon booking, beauty appointment, barber booking, spa, nails, beauty clinics, wellness, Syria, Damascus';
    $allKeywords   = trim(($keywords ? $keywords.', ' : '').$baseKeywords, ', ');
    $canonicalUrl = $canonical ?: url()->current();
    $ogImageUrl   = $ogImage
        ? (\Illuminate\Support\Str::startsWith($ogImage, ['http://','https://']) ? $ogImage : asset(ltrim($ogImage,'/')))
        : asset('images/og-cover.jpg');
    $ogLocale     = $isAr ? 'ar_AR' : 'en_US';
    $robots       = $noindex
        ? 'noindex, nofollow'
        : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    /* Site-wide JSON-LD (Organization + WebSite). Built in PHP — NOT via @json —
       so Blade never tries to parse the '@type'/'@graph' keys as directives. */
    $siteSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'         => 'Organization',
                '@id'           => url('/').'#organization',
                'name'          => 'GlowRez',
                'alternateName' => ['غلوريز', 'غلو ريز', 'Glow Rez', 'قلوريز'],
                'url'           => url('/'),
                'logo'          => asset('images/logo-light.png'),
                'image'         => asset('images/og-cover.jpg'),
                'description'   => $metaDesc,
            ],
            [
                '@type'         => 'WebSite',
                '@id'           => url('/').'#website',
                'name'          => 'GlowRez',
                'alternateName' => ['غلوريز', 'غلو ريز'],
                'url'           => url('/'),
                'publisher' => ['@id' => url('/').'#organization'],
                'inLanguage'=> $lang,
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => url('/venues').'?search={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ];

    // Nav links per variant
    if ($variant === 'business') {
        $links = [
            ['label' => $isAr ? 'المزايا'        : 'Features',    'href' => '#features'],
            ['label' => $isAr ? 'قريبا'        : 'coming soon',    'href' => '#soon'],
            ['label' => $isAr ? 'لماذا غلوريز'    : 'Why GlowRez',  'href' => '#why'],
            ['label' => $isAr ? 'الأسئلة الشائعة': 'FAQ',         'href' => '#faq'],
            ['label' => $isAr ? 'مجاني'          : 'Free',        'href' => '#free'],
        ];
    } else {
        $home = route('front.index');
        $links = [
            ['label' => $isAr ? 'اكتشف'       : 'Discover',   'href' => $home.'#discover'],
            ['label' => $isAr ? 'التصنيفات'   : 'Categories', 'href' => $home.'#categories'],
            ['label' => $isAr ? 'الأقرب إليك' : 'Near you',   'href' => $home.'#nearby'],
            ['label' => $isAr ? 'كيف يعمل'    : 'How it works','href' => $home.'#how'],
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}" data-bk-theme="{{ $theme }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="referrer" content="strict-origin-when-cross-origin">

{{-- ══════════════  PRIMARY SEO  ══════════════ --}}
<title>{{ $title }}</title>
<meta name="description" content="{{ $metaDesc }}">
<meta name="keywords" content="{{ $allKeywords }}">
<meta name="robots" content="{{ $robots }}">
<meta name="googlebot" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="author" content="GlowRez">
<meta name="application-name" content="GlowRez">
<meta name="theme-color" content="{{ $theme === 'dark' ? '#0F1413' : '#F6F5F2' }}">

{{-- ══════════════  OPEN GRAPH (Facebook / WhatsApp / LinkedIn)  ══════════════ --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="GlowRez">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="{{ $ogLocale }}">
<meta property="og:locale:alternate" content="{{ $isAr ? 'en_US' : 'ar_AR' }}">

{{-- ══════════════  TWITTER CARDS  ══════════════ --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">

{{-- ══════════════  FAVICONS / APP ICONS  ══════════════ --}}
@php $iconV = fn($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: '1'); @endphp
<link rel="icon" href="{{ $iconV('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $iconV('icons/favicon-16.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $iconV('icons/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="48x48" href="{{ $iconV('icons/favicon-48.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $iconV('icons/apple-touch-icon.png') }}">

{{-- PWA: installable, app-like on mobile --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="GlowRez">

{{-- ══════════════  ORGANIZATION + WEBSITE (site-wide JSON-LD)  ══════════════ --}}
<script type="application/ld+json">{!! json_encode($siteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

{{-- Prevent theme flash: reconcile server cookie with client preference before paint --}}
<script>
(function(){try{var t=localStorage.getItem('bk_front_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-bk-theme',t);}}catch(e){}})();
</script>

<link rel="preconnect" href="{{ url('/') }}">
<link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
<link href="{{ asset('fonts/glowrez-type.css') }}?v={{ @filemtime(public_path('fonts/glowrez-type.css')) ?: '1' }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/css/booksy-front.css') }}?v={{ @filemtime(public_path('frontend/css/booksy-front.css')) ?: '1' }}">
{{-- If JS is unavailable, never hide content behind scroll-reveal --}}
<noscript>
<style>.bkf-reveal{opacity:1!important;transform:none!important}</style>
</noscript>
{{-- page-specific structured data / head extras --}}
{{ $head ?? '' }}
{{ $styles ?? '' }}
</head>
<body class="bkf {{ $bodyClass }}">

{{-- ══════════════  NAV  ══════════════ --}}
<nav class="bkf-nav" data-bkf-nav>
  <div class="bkf-container-wide bkf-nav-inner">
    <a href="{{ $variant === 'business' ? route('front.business') : route('front.index') }}" class="bkf-brand" aria-label="GlowRez">
      <x-front.logo variant="full"/>
      @if($variant === 'business')<span class="bkf-brand-sub">{{ $isAr ? 'للأعمال' : 'Business' }}</span>@endif
    </a>

    <div class="bkf-nav-menu" data-menu>
      <ul class="bkf-nav-links">
        @foreach($links as $l)
          <li><a href="{{ $l['href'] }}" @class(['is-active' => $active === $l['href']]) @if(\Illuminate\Support\Str::endsWith($l['href'] ?? '', '#nearby')) data-geo-trigger @endif>{{ $l['label'] }}</a></li>
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
          @include('front.partials.account-nav')
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
  <div class="bkf-footer-waves" aria-hidden="true">
    <svg viewBox="0 24 150 28" preserveAspectRatio="none">
      <defs><path id="bkf-footer-wp" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"/></defs>
      <use class="fw1" href="#bkf-footer-wp" x="48" y="0"/>
      <use class="fw2" href="#bkf-footer-wp" x="48" y="3"/>
      <use class="fw3" href="#bkf-footer-wp" x="48" y="5"/>
      <use class="fw4" href="#bkf-footer-wp" x="48" y="7"/>
    </svg>
  </div>
  <span class="bkf-footer-blob a"></span>
  <span class="bkf-footer-blob b"></span>
  <div class="bkf-container-wide">
    <div class="bkf-footer-grid">
      <div class="bkf-footer-brand">
        <a href="{{ route('front.index') }}" class="bkf-brand bkf-brand-lg" aria-label="GlowRez">
          <x-front.logo variant="full"/>
        </a>
        <p class="bkf-footer-tag">{{ $isAr ? 'منصّة حجز وإدارة أماكن الجمال والعناية — احجز موعدك في ثوانٍ، وأدر عملك باحتراف.' : 'The booking & management platform for beauty & wellness venues — book in seconds, run your business like a pro.' }}</p>
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
        <a href="{{ route('front.help') }}">{{ $isAr ? 'مركز المساعدة' : 'Help center' }}</a>
      </div>
      <div class="bkf-footer-col">
        <h4>{{ $isAr ? 'للأعمال' : 'For business' }}</h4>
        <a href="{{ route('front.business') }}#features">{{ $isAr ? 'المزايا' : 'Features' }}</a>
        <a href="{{ route('front.business') }}#free">{{ $isAr ? 'مجاني' : 'Free' }}</a>
        <a href="{{ route('company.register') }}">{{ $isAr ? 'سجّل نشاطك' : 'List your business' }}</a>
      </div>
      <div class="bkf-footer-col">
        <h4>{{ $isAr ? 'الشركة' : 'Company' }}</h4>
        <a href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a>
        <a href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل معنا' : 'Contact' }}</a>
        <a href="{{ route('company.login') }}">{{ $isAr ? 'دخول الشركات' : 'Business login' }}</a>
      </div>
    </div>
    <div class="bkf-footer-bar">
      <span>{{ $isAr
        ? 'جميع الحقوق محفوظة © '.date('Y').' لشركة GlowRez'
        : '© '.date('Y').' GlowRez. All rights reserved.' }}</span>
      <div class="bkf-footer-bar-links">
        <a href="{{ route('front.privacy') }}">{{ $isAr ? 'الخصوصية' : 'Privacy' }}</a>
        <a href="{{ route('front.terms') }}">{{ $isAr ? 'الشروط' : 'Terms' }}</a>
      </div>
    </div>
  </div>
</footer>

@if($variant === 'customer')
  @include('front.partials.customer-auth-modal')
  {{-- A guarded page (/account/*) bounces guests here with ?login=1&return=… --}}
  <script>
  (function(){
    var p = new URLSearchParams(location.search);
    if(p.get('login') === '1' && window.CustomerAuthModal){
      var ret = p.get('return');
      CustomerAuthModal.open(function(){ location.href = ret || '{{ route('account.appointments') }}'; });
    }
  })();
  </script>
  @if($mapFab)
    @include('front.partials.map-fab')
  @endif
@endif

<script>window.BK_SW = '{{ asset('sw.js') }}'; window.BK_ICON = '{{ asset('icons/icon-192.png') }}';</script>
<script src="{{ asset('frontend/js/booksy-front.js') }}?v={{ @filemtime(public_path('frontend/js/booksy-front.js')) ?: '1' }}" defer></script>
{{ $scripts ?? '' }}
</body>
</html>
