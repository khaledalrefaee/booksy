{{--
    GlowRez — Unified error page shell.
    Consumed by 403/404/500/419/429/503 views via @include('errors.partials.shell', [...]).

    Expected vars:
      $code      (string|int)  HTTP status, e.g. 404  (required — selects the copy)
      $icon      (string)      key: 'compass' | 'lock' | 'alert' | 'clock' | 'gauge' | 'tools'
      $showRetry (bool)        optional — swaps "Go back" for "Try again" (reload)
      $title,$desc (string)    optional — override the centralized copy
--}}
@php
    // --- Locale ------------------------------------------------------------
    // Read the chosen locale straight from the session. A route-model-binding
    // 404 (e.g. /branch/{branch}) throws before the SetLocale middleware runs,
    // which would otherwise leave the app on the default locale (ar).
    $loc = app()->getLocale();
    try {
        if (request()->hasSession() && request()->session()->has('locale')) {
            // Matched-route 404s (e.g. /branch/{branch}) went through the web group.
            $s = request()->session()->get('locale');
            if (in_array($s, ['ar', 'en'], true)) { $loc = $s; }
        } else {
            // Unmatched-route 404s skip the web group (no session); fall back to the
            // browser's preferred language rather than forcing the app default.
            $p = request()->getPreferredLanguage(['en', 'ar']);
            if (in_array($p, ['ar', 'en'], true)) { $loc = $p; }
        }
    } catch (\Throwable $e) { /* keep current locale */ }
    if (in_array($loc, ['ar', 'en'], true)) { app()->setLocale($loc); }
    $isAr = app()->getLocale() === 'ar';

    // --- Context: home target, theme, logo label, favicon set -------------
    $path = request()->path();
    if (str_starts_with($path, 'company')) {
        $homeUrl   = \Illuminate\Support\Facades\Route::has('company.dashboard') ? route('company.dashboard') : url('/company/login');
        $theme     = request()->cookie('company_theme', 'dark');
        $logoLabel = 'Business';
        $isFront   = false;
    } elseif (str_starts_with($path, 'owner')) {
        $homeUrl   = \Illuminate\Support\Facades\Route::has('owner.dashboard') ? route('owner.dashboard') : url('/owner/login');
        $theme     = request()->cookie('owner_theme', 'dark');
        $logoLabel = 'Console';
        $isFront   = false;
    } else {
        $homeUrl   = url('/');
        $theme     = request()->cookie('bk_front_theme', 'light');
        $logoLabel = null;
        $isFront   = true;
    }
    $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'dark';

    // Theme-aware logo. File names follow the target mode:
    //   *-dark  = white wordmark (dark backgrounds)   ·   *-light = black wordmark (light backgrounds)
    $logoV     = fn ($f) => asset('images/'.$f).'?v='.(@filemtime(public_path('images/'.$f)) ?: '1');
    $logoLight = $logoV('glowrez-logo-light_1.webp');
    $logoDark  = $logoV('glowrez-logo-dark_1.webp');
    $logoSrc   = $theme === 'dark' ? $logoDark : $logoLight;

    $showRetry = $showRetry ?? false;

    // --- Centralized bilingual copy (keyed by status code) ----------------
    $copy = [
        403 => [
            'ar' => ['لا تملك صلاحية الوصول', 'هذه الصفحة محمية ولا تملك الصلاحية اللازمة لعرضها.'],
            'en' => ["You don't have access", "This page is restricted and you don't have permission to view it."],
        ],
        404 => [
            'ar' => ['هذه الصفحة غير موجودة', 'ربما تم نقل الرابط أو حذفه، أو أنك كتبت عنوانًا غير صحيح.'],
            'en' => ["This page doesn't exist", 'The link may have moved or been removed, or you typed an incorrect address.'],
        ],
        419 => [
            'ar' => ['انتهت صلاحية الجلسة', 'مرّ وقت طويل على الصفحة. الرجاء تحديث الصفحة والمحاولة مرة أخرى.'],
            'en' => ['Your session has expired', 'The page sat idle for too long. Please refresh and try again.'],
        ],
        429 => [
            'ar' => ['عدد كبير من الطلبات', 'لقد أرسلت طلبات كثيرة خلال فترة قصيرة. الرجاء الانتظار قليلًا ثم المحاولة مجددًا.'],
            'en' => ['Too many requests', "You've sent a lot of requests in a short time. Please wait a moment and try again."],
        ],
        500 => [
            'ar' => ['حدث خطأ من جانبنا', 'الفريق التقني تم إعلامه بالمشكلة. الرجاء المحاولة مجددًا خلال قليل.'],
            'en' => ['Something went wrong on our end', 'Our team has been notified. Please try again in a moment.'],
        ],
        503 => [
            'ar' => ['جارٍ إجراء صيانة قصيرة', 'الخدمة تحت الصيانة حاليًا وستعود خلال دقائق. شكرًا لتفهمك.'],
            'en' => ["We're doing quick maintenance", 'The service is briefly under maintenance and will be back in a few minutes. Thanks for your patience.'],
        ],
    ];
    $fallback = $isAr
        ? ['حدث خطأ', 'واجهنا مشكلة غير متوقعة. الرجاء المحاولة لاحقًا.']
        : ['Something went wrong', 'We hit an unexpected problem. Please try again later.'];
    [$copyTitle, $copyDesc] = $copy[(int) $code][$isAr ? 'ar' : 'en'] ?? $fallback;
    $title = $title ?? $copyTitle;
    $desc  = $desc  ?? $copyDesc;

    // Feather-style inline icons (match the project's feather icon set, zero external requests).
    $icons = [
        'compass' => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
        'lock'    => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'alert'   => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'clock'   => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'gauge'   => '<path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 12l6-6"/><circle cx="12" cy="12" r="1.6" fill="currentColor" stroke="none"/>',
        'tools'   => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.3L3 18v3h3l6.4-6.3a4 4 0 0 0 5.3-5.4l-2.7 2.7-2-2 2.7-2.7z"/>',
    ];
    $iconSvg = $icons[$icon ?? 'alert'] ?? $icons['alert'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} — {{ $title }}</title>
    @if($isFront)
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/favicon-16.png') }}">
    @else
        <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}">
    @endif
    {{-- Front theme is stored in localStorage; mirror it before first paint (server only sees the cookie). --}}
    @if($isFront)
        <script>(function(){try{var t=localStorage.getItem('bk_front_theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>
    @endif
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <style>
        :root{
            color-scheme: light;
            --bg:          #F6F5EF;
            --glow-olive:  rgba(75,93,52,.14);
            --glow-gold:   rgba(201,162,39,.16);
            --card:        #FFFFFF;
            --border:      rgba(43,53,29,.12);
            --ink:         #1E2417;
            --muted:       #5C6552;
            --gold:        #A9821F;
            --gold-1:      #C9A227;
            --gold-2:      #E4C766;
            --olive:       #4B5D34;
            --olive-2:     #55693B;
            --btn-ink:     #1B2110;
            --outline-bd:  rgba(43,53,29,.18);
            --outline-hv:  rgba(75,93,52,.06);
            --shadow:      0 24px 60px -28px rgba(30,36,23,.30), 0 4px 14px -8px rgba(30,36,23,.16);
        }
        html[data-theme="dark"]{
            color-scheme: dark;
            --bg:          #0F1115;
            --glow-olive:  rgba(85,105,59,.22);
            --glow-gold:   rgba(216,184,115,.14);
            --card:        #171A21;
            --border:      rgba(255,255,255,.08);
            --ink:         #F1EFE6;
            --muted:       rgba(241,239,230,.56);
            --gold:        #D8B873;
            --gold-1:      #D8B873;
            --gold-2:      #F0DCA0;
            --olive:       #7C9455;
            --olive-2:     #8FA968;
            --btn-ink:     #14150C;
            --outline-bd:  rgba(255,255,255,.16);
            --outline-hv:  rgba(255,255,255,.05);
            --shadow:      0 30px 70px -30px rgba(0,0,0,.66), 0 4px 16px -8px rgba(0,0,0,.5);
        }
        * { box-sizing: border-box; }
        body{
            margin:0; min-height:100vh; min-height:100dvh;
            display:flex; align-items:center; justify-content:center;
            padding:24px;
            background:
                radial-gradient(60% 55% at 12% 0%, var(--glow-olive) 0%, transparent 60%),
                radial-gradient(48% 48% at 100% 100%, var(--glow-gold) 0%, transparent 62%),
                var(--bg);
            color:var(--ink);
            font-family: {{ $isAr ? "'Tajawal','Segoe UI',sans-serif" : "'Poppins','Segoe UI',Roboto,sans-serif" }};
            -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility;
        }
        .card{
            width:100%; max-width:520px;
            background:var(--card);
            border:1px solid var(--border);
            border-radius:22px;
            box-shadow:var(--shadow);
            padding:44px 40px 40px;
            text-align:center;
            position:relative; overflow:hidden;
        }
        /* thin brand hairline along the top edge */
        .card::before{
            content:""; position:absolute; inset:0 0 auto 0; height:3px;
            background:linear-gradient(90deg, var(--olive) 0%, var(--gold-1) 55%, var(--gold-2) 100%);
            opacity:.9;
        }
        .logo{ height:40px; width:auto; margin:0 auto 26px; display:block; }
        /* Neutral olive badge — keeps gold reserved for the hero number */
        .badge{
            width:56px; height:56px; margin:0 auto 18px;
            border-radius:16px; display:grid; place-items:center;
            color:var(--olive);
            background:color-mix(in srgb, var(--olive) 9%, transparent);
            border:1px solid color-mix(in srgb, var(--olive) 26%, transparent);
        }
        .badge svg{ width:26px; height:26px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .code{
            font-size:clamp(66px, 16vw, 104px); font-weight:800; line-height:.95; letter-spacing:-2px;
            margin:0 0 6px;
            background:linear-gradient(135deg, var(--olive-2) 0%, var(--gold-1) 58%, var(--gold-2) 100%);
            -webkit-background-clip:text; background-clip:text; color:transparent;
        }
        h1{ font-size:clamp(20px, 4.5vw, 25px); font-weight:700; margin:2px 0 12px; letter-spacing:-.2px; }
        p.desc{ color:var(--muted); font-size:15px; line-height:1.75; margin:0 auto 30px; max-width:42ch; text-wrap:balance; }
        [dir="rtl"] p.desc{ font-size:15.5px; }
        .actions{ display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        .btn{
            display:inline-flex; align-items:center; gap:8px;
            padding:12px 24px; border-radius:12px; font-size:14.5px; font-weight:600;
            text-decoration:none; cursor:pointer; border:1px solid transparent;
            transition:transform .15s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
        }
        .btn svg{ width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
        .btn-primary{
            color:var(--btn-ink);
            background:linear-gradient(135deg, var(--gold-1), var(--gold-2));
            box-shadow:0 8px 20px -10px color-mix(in srgb, var(--gold-1) 70%, transparent);
        }
        .btn-primary:hover{ transform:translateY(-1px); box-shadow:0 12px 26px -10px color-mix(in srgb, var(--gold-1) 78%, transparent); }
        .btn-outline{ color:var(--ink); border-color:var(--outline-bd); background:transparent; }
        .btn-outline:hover{ transform:translateY(-1px); background:var(--outline-hv); }
        /* flip the directional back-arrow under RTL */
        [dir="rtl"] .btn .i-flip{ transform:scaleX(-1); }
        /* Staggered entrance — subtle, premium */
        @keyframes bkErrIn{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
        .card > *{ animation:bkErrIn .5s cubic-bezier(.2,.7,.2,1) both; }
        .card > .logo{ animation-delay:.02s; }
        .card > .badge{ animation-delay:.08s; }
        .card > .code{ animation-delay:.14s; }
        .card > h1{ animation-delay:.20s; }
        .card > .desc{ animation-delay:.26s; }
        .card > .actions{ animation-delay:.32s; }
        @media (max-width:480px){
            .card{ padding:34px 22px 30px; border-radius:18px; }
            .btn{ flex:1 1 auto; justify-content:center; }
        }
        @media (prefers-reduced-motion:reduce){
            .btn{ transition:none; } .btn:hover{ transform:none; }
            .card > *{ animation:none; }
        }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" id="bkLogo" src="{{ $logoSrc }}" data-light="{{ $logoLight }}" data-dark="{{ $logoDark }}" alt="GlowRez{{ $logoLabel ? ' '.$logoLabel : '' }}">
        <div class="badge" aria-hidden="true">
            <svg viewBox="0 0 24 24">{!! $iconSvg !!}</svg>
        </div>
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p class="desc">{{ $desc }}</p>
        <div class="actions">
            @if($showRetry)
                <a href="javascript:location.reload()" class="btn btn-outline">
                    <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    {{ $isAr ? 'إعادة المحاولة' : 'Try again' }}
                </a>
            @else
                <a href="javascript:history.back()" class="btn btn-outline">
                    <svg class="i-flip" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    {{ $isAr ? 'رجوع' : 'Go back' }}
                </a>
            @endif
            <a href="{{ $homeUrl }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/></svg>
                {{ $isAr ? 'الصفحة الرئيسية' : 'Go home' }}
            </a>
        </div>
    </main>
    @if($isFront)
    {{-- Keep the logo artwork in sync with the localStorage-resolved theme. --}}
    <script>document.addEventListener('DOMContentLoaded',function(){try{var el=document.getElementById('bkLogo'),t=document.documentElement.getAttribute('data-theme');if(el&&t){var src=(t==='dark')?el.getAttribute('data-dark'):el.getAttribute('data-light');if(src&&el.getAttribute('src')!==src){el.setAttribute('src',src);}}}catch(e){}});</script>
    @endif
</body>
</html>
