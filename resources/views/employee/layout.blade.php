<!DOCTYPE html>
@php $theme = request()->cookie('owner_theme', 'light'); $rep = Auth::guard('owner')->user(); @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      class="bk-theme-{{ $theme }}" data-bk-theme="{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Field sales')) — GlowRez</title>
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-custom.css') }}">
    <link rel="shortcut icon" href="{{ asset('icons/favicon-32.png') }}">
    <style>
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bk-bg); color:var(--bk-text); font-family:'Hanken Grotesk',system-ui,sans-serif;
            -webkit-font-smoothing:antialiased; padding-bottom:calc(72px + env(safe-area-inset-bottom)); }
        a { text-decoration:none; color:inherit; }
        .ev-wrap { max-width:640px; margin-inline:auto; padding:16px 16px 24px; }

        /* Top bar */
        .ev-top { position:sticky; top:0; z-index:20; display:flex; align-items:center; justify-content:space-between;
            gap:12px; padding:calc(10px + env(safe-area-inset-top)) 16px 10px; background:color-mix(in srgb, var(--bk-surface) 88%, transparent);
            backdrop-filter:saturate(1.4) blur(10px); border-bottom:1px solid var(--bk-border); }
        .ev-brand { display:inline-flex; align-items:center; }
        .ev-logo { height:42px; width:auto; display:block; }
        .ev-top-right { display:flex; align-items:center; gap:8px; }
        .ev-rep { display:inline-flex; align-items:center; gap:8px; padding:4px 10px 4px 4px; border-radius:999px;
            background:var(--bk-bg); border:1px solid var(--bk-border); }
        .ev-rep-name { font-size:.8rem; color:var(--bk-text-soft); font-weight:600; max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ev-rep-av { width:28px; height:28px; border-radius:50%; background:var(--bk-accent); color:var(--bk-accent-ink); display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:700; flex-shrink:0; }
        .ev-logout { width:40px; height:40px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text-muted); cursor:pointer;
            border-radius:12px; display:inline-flex; align-items:center; justify-content:center; transition:all .15s; }
        .ev-logout:active { transform:scale(.94); }
        .ev-logout:hover { border-color:var(--bk-danger); color:var(--bk-danger); background:var(--bk-danger-bg); }
        .ev-logout i { width:17px; height:17px; }
        @media (max-width:360px){ .ev-rep-name { display:none; } }

        /* Section header — consistent title rows across pages */
        .ev-sec { display:flex; align-items:center; gap:8px; margin:22px 2px 10px; }
        .ev-sec-ic { width:26px; height:26px; border-radius:8px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            background:var(--bk-gold-soft); color:var(--bk-gold-strong); }
        .ev-sec-ic i { width:14px; height:14px; }
        .ev-sec-title { font-size:.82rem; font-weight:700; color:var(--bk-text); letter-spacing:.01em; }
        .ev-sec-link { margin-inline-start:auto; font-size:.78rem; font-weight:600; color:var(--bk-accent); }

        /* Cards & bits */
        .ev-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:16px; box-shadow:var(--bk-shadow); overflow:hidden; }
        .ev-h { font-family:'Fraunces',Georgia,serif; font-size:1.6rem; font-weight:600; margin:6px 0 3px; letter-spacing:-.01em; }
        .ev-sub { color:var(--bk-text-muted); font-size:.9rem; margin:0 0 18px; line-height:1.5; }
        .ev-btn { display:inline-flex; align-items:center; justify-content:center; gap:9px; height:52px; padding:0 20px; border-radius:15px;
            font-size:.96rem; font-weight:700; border:1px solid transparent; cursor:pointer; width:100%; transition:transform .12s, box-shadow .15s, background .15s; }
        .ev-btn i { width:19px; height:19px; }
        .ev-btn-primary { background:var(--bk-accent); color:var(--bk-accent-ink); box-shadow:0 6px 16px color-mix(in srgb,var(--bk-accent) 28%,transparent); }
        .ev-btn-primary:active { transform:scale(.98); }
        .ev-btn-ghost { background:var(--bk-surface); color:var(--bk-text-soft); border-color:var(--bk-border); }
        .ev-btn-ghost:active { transform:scale(.98); }

        .ev-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:4px; }
        .ev-stat { position:relative; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:16px; padding:15px 12px 13px; text-align:center; box-shadow:var(--bk-shadow); }
        .ev-stat-ic { width:30px; height:30px; margin:0 auto 8px; border-radius:9px; display:flex; align-items:center; justify-content:center;
            background:var(--accent-wash,var(--bk-accent-wash)); color:var(--accent,var(--bk-accent)); }
        .ev-stat-ic i { width:15px; height:15px; }
        .ev-stat-v { font-family:'Fraunces',serif; font-size:1.55rem; font-weight:600; font-variant-numeric:tabular-nums; color:var(--bk-text); line-height:1; }
        .ev-stat-l { font-size:.7rem; color:var(--bk-text-muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; margin-top:5px; }

        .ev-flash { padding:12px 14px; border-radius:12px; margin-bottom:14px; font-size:.88rem; font-weight:600; }
        .ev-flash.ok { background:var(--bk-success-bg); color:var(--bk-success); border:1px solid color-mix(in srgb,var(--bk-success) 30%,transparent); }
        .ev-flash.info { background:var(--bk-info-bg); color:var(--bk-info); border:1px solid color-mix(in srgb,var(--bk-info) 30%,transparent); }
        .ev-flash.err { background:var(--bk-danger-bg); color:var(--bk-danger); border:1px solid color-mix(in srgb,var(--bk-danger) 30%,transparent); }

        /* Visit list rows */
        .ev-row { display:flex; align-items:center; gap:12px; padding:13px 14px; border-bottom:1px solid var(--bk-border); }
        .ev-row:last-child { border-bottom:none; }
        .ev-row-ic { width:42px; height:42px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            background:var(--bk-accent-wash); color:var(--bk-accent); }
        .ev-row-ic i { width:19px; height:19px; }
        .ev-row-body { flex:1; min-width:0; }
        .ev-row-title { font-weight:700; font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ev-row-meta { font-size:.78rem; color:var(--bk-text-muted); margin-top:2px; display:flex; gap:10px; flex-wrap:wrap; }
        .ev-chevron { color:var(--bk-text-muted); }
        .ev-chevron i { width:18px; height:18px; }

        /* Forms */
        .ev-field { margin-bottom:15px; }
        .ev-label { display:block; font-size:.85rem; font-weight:700; margin-bottom:7px; color:var(--bk-text-soft); }
        .ev-input, .ev-select, .ev-textarea { width:100%; min-height:48px; padding:12px 14px; border-radius:12px;
            border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-size:1rem; outline:none; }
        .ev-input:focus, .ev-select:focus, .ev-textarea:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
        .ev-textarea { min-height:90px; resize:vertical; }
        .ev-err { color:var(--bk-danger); font-size:.78rem; margin-top:5px; }
        .ev-help { color:var(--bk-text-muted); font-size:.78rem; margin-top:5px; }

        /* Chips (place type, interest) */
        .ev-chips { display:flex; flex-wrap:wrap; gap:8px; }
        .ev-chip { position:relative; }
        .ev-chip input { position:absolute; opacity:0; }
        .ev-chip span { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; border-radius:999px; border:1px solid var(--bk-border);
            background:var(--bk-surface); font-size:.85rem; font-weight:600; cursor:pointer; }
        .ev-chip input:checked + span { background:var(--bk-accent); color:var(--bk-accent-ink); border-color:var(--bk-accent); }
        .ev-stars { display:flex; gap:6px; }
        .ev-star { font-size:26px; cursor:pointer; color:var(--bk-border-strong); background:none; border:none; padding:0; line-height:1; }
        .ev-star.on { color:var(--bk-gold); }

        /* GPS status pill */
        .ev-gps { display:flex; align-items:center; gap:8px; padding:11px 13px; border-radius:12px; font-size:.83rem; font-weight:600;
            background:var(--bk-surface-2); border:1px solid var(--bk-border); color:var(--bk-text-soft); margin-bottom:15px; }
        .ev-gps i { width:16px; height:16px; }
        .ev-gps.ok { background:var(--bk-success-bg); color:var(--bk-success); border-color:color-mix(in srgb,var(--bk-success) 30%,transparent); }
        .ev-gps.err { background:var(--bk-warning-bg); color:var(--bk-warning); border-color:color-mix(in srgb,var(--bk-warning) 30%,transparent); }

        /* Bottom nav */
        .ev-nav { position:fixed; bottom:0; inset-inline:0; z-index:30; display:flex; background:var(--bk-surface);
            border-top:1px solid var(--bk-border); padding-bottom:env(safe-area-inset-bottom); }
        .ev-nav a { flex:1; display:flex; flex-direction:column; align-items:center; gap:3px; padding:10px 0; color:var(--bk-text-muted); font-size:.68rem; font-weight:700; }
        .ev-nav a i { width:22px; height:22px; }
        .ev-nav a.active { color:var(--bk-accent); }
        .ev-nav .ev-nav-cta { margin-top:-18px; }
        .ev-nav .ev-nav-cta .ev-nav-cta-btn { width:52px; height:52px; border-radius:50%; background:var(--bk-accent); color:var(--bk-accent-ink);
            display:flex; align-items:center; justify-content:center; box-shadow:0 6px 16px color-mix(in srgb,var(--bk-accent) 40%,transparent); }
        .ev-nav .ev-nav-cta .ev-nav-cta-btn i { width:24px; height:24px; }

        .ev-empty { text-align:center; padding:44px 16px; color:var(--bk-text-muted); }
        .ev-empty i { width:40px; height:40px; opacity:.4; }
        .ev-empty p { margin:10px 0 0; font-size:.9rem; }
    </style>
    @stack('head')
</head>
<body>
    <header class="ev-top">
        <a href="{{ route('employee.home') }}" class="ev-brand" aria-label="GlowRez">
            <img src="{{ asset('images/glowrez-logo-light_1.webp') }}" alt="GlowRez" class="ev-logo">
        </a>
        <div class="ev-top-right">
            <span class="ev-rep">
                <span class="ev-rep-av">{{ mb_strtoupper(mb_substr($rep->name ?? '?', 0, 2)) }}</span>
                <span class="ev-rep-name">{{ \Illuminate\Support\Str::limit($rep->name ?? '', 16) }}</span>
            </span>
            <form method="POST" action="{{ route('owner.logout') }}" class="m-0">@csrf
                <button type="submit" class="ev-logout" title="{{ __('Sign out') }}" aria-label="{{ __('Sign out') }}"><i data-feather="log-out"></i></button>
            </form>
        </div>
    </header>

    <main class="ev-wrap">
        @if(session('success'))<div class="ev-flash ok">{{ session('success') }}</div>@endif
        @if(session('info'))<div class="ev-flash info">{{ session('info') }}</div>@endif
        @if(session('error'))<div class="ev-flash err">{{ session('error') }}</div>@endif
        @yield('content')
    </main>

    <nav class="ev-nav">
        <a href="{{ route('employee.home') }}" class="{{ request()->routeIs('employee.home') ? 'active' : '' }}">
            <i data-feather="home"></i>{{ __('Home') }}
        </a>
        <a href="{{ route('employee.field-visits.create') }}" class="ev-nav-cta">
            <span class="ev-nav-cta-btn"><i data-feather="plus"></i></span>
        </a>
        <a href="{{ route('employee.field-visits.index') }}" class="{{ request()->routeIs('employee.field-visits.index') ? 'active' : '' }}">
            <i data-feather="list"></i>{{ __('My visits') }}
        </a>
    </nav>

    <script src="{{ asset('backend/assets/vendors/feather-icons/feather.min.js') }}"></script>
    <script>if (window.feather) feather.replace();</script>
    @stack('scripts')
</body>
</html>
