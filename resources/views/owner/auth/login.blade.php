<!DOCTYPE html>
@php
    $isAr         = app()->getLocale() === 'ar';
    $ownerTheme   = request()->cookie('owner_theme', 'dark') === 'light' ? 'light' : 'dark';
    $currentLocale = app()->getLocale();
    $asset = fn ($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: '1');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}" data-theme="{{ $ownerTheme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('Sign in') }} — GlowRez</title>

    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('fonts/glowrez-type.css') }}" rel="stylesheet">
    <link rel="shortcut icon" href="{{ $asset('icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ $asset('icons/apple-touch-icon.png') }}">

    <script>
        /* Per-viewer theme preference (the owner cookie is only writable after sign-in). */
        try { var t = localStorage.getItem('ol_theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>

    <style>
        :root {
            --f-display: 'Fraunces', 'Tajawal', Georgia, serif;
            --f-body: 'Hanken Grotesk', 'Tajawal', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --ease: cubic-bezier(.16, 1, .3, 1);
            --gold-lit: #D8B873;
        }
        :root[data-theme="light"] {
            --bg: #F7F5EF;      --field: #FFFFFF;     --line: #E2DBCB;   --line-strong: #C9C0AA;
            --text: #22251D;    --text-soft: #4B5040; --muted: #666A57;
            --accent: #4B5D34;  --fill: #4B5D34;      --fill-hover: #3C4B29; --wash: #E9EEDC;
            --gold: #8F6A22;    --danger: #8F2A24;    --danger-bg: #FBEDEA;  --danger-line: #E9BDB7;
            color-scheme: light;
        }
        :root[data-theme="dark"] {
            --bg: #13160F;      --field: #1B2015;     --line: #2B3223;   --line-strong: #3C4631;
            --text: #F0EEE3;    --text-soft: #D3D1C2; --muted: #A0A590;
            --accent: #A6BC7E;  --fill: #5C7038;      --fill-hover: #6B8243; --wash: #232A19;
            --gold: #D8B873;    --danger: #F3A9A1;    --danger-bg: #2C1A16;  --danger-line: #5A322B;
            color-scheme: dark;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0; min-height: 100vh; min-height: 100dvh;
            background: var(--bg); color: var(--text);
            font-family: var(--f-body); font-size: 16px; line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            transition: background-color .35s ease, color .35s ease;
        }
        [dir="rtl"] body { line-height: 1.7; }
        ::selection { background: var(--wash); color: var(--text); }
        input { caret-color: var(--accent); }
        a { color: inherit; }
        * { scrollbar-width: thin; scrollbar-color: var(--line-strong) transparent; }

        .ol-shell {
            display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(0, 1fr);
            min-height: 100vh; min-height: 100dvh;
        }

        /* ───────────── Brand stage (always the deep olive field) ───────────── */
        .ol-stage {
            --mx: 62%; --my: 40%;
            position: relative; overflow: hidden; isolation: isolate;
            display: flex; flex-direction: column; justify-content: flex-start; gap: 2rem;
            padding: clamp(1.75rem, 3.4vw, 3.25rem);
            color: #F3EEE6;
            background:
                radial-gradient(90% 70% at 0% 0%, rgba(92, 112, 56, .55), transparent 62%),
                linear-gradient(160deg, #2F3B20 0%, #232C17 52%, #181E10 100%);
        }
        .ol-stage::before {              /* film grain, keeps the olive from reading as a flat gradient */
            content: ""; position: absolute; inset: 0; z-index: 3; pointer-events: none; opacity: .09; mix-blend-mode: soft-light;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='220' height='220'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 .9 0'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>");
        }
        .ol-glow {                        /* the "glow": a warm light that follows the pointer */
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(520px circle at var(--mx) var(--my), rgba(216, 184, 115, .20), transparent 70%);
        }
        .ol-mark {                        /* GR monogram, bleeding off the stage */
            position: absolute; z-index: 1; pointer-events: none; user-select: none;
            width: min(104%, 940px); height: auto;
            inset-inline-end: -9%; bottom: -5%;
            transform: translateZ(0);
        }
        .ol-mark--ghost { opacity: .085; }
        .ol-lit {                         /* same monogram, revealed only where the light falls */
            position: absolute; inset: 0; z-index: 2; pointer-events: none;
            -webkit-mask-image: radial-gradient(circle 300px at var(--mx) var(--my), #000 0%, rgba(0,0,0,.55) 45%, transparent 100%);
                    mask-image: radial-gradient(circle 300px at var(--mx) var(--my), #000 0%, rgba(0,0,0,.55) 45%, transparent 100%);
        }
        .ol-lit .ol-mark { opacity: .7; }

        .ol-brand, .ol-pitch, .ol-legal { position: relative; z-index: 4; }
        .ol-brand img { display: block; height: clamp(38px, 3.4vw, 50px); width: auto; }

        .ol-pitch { max-width: 30rem; margin-top: clamp(1.5rem, 7vh, 4.5rem); }
        .ol-pitch h2 {
            margin: 0 0 1rem; font-family: var(--f-display); font-weight: 500;
            font-size: clamp(2.1rem, 3.5vw, 3.5rem); line-height: 1.06; letter-spacing: -.02em;
            text-wrap: balance; color: #F6F1E7;
        }
        [dir="rtl"] .ol-pitch h2 { font-weight: 800; line-height: 1.3; letter-spacing: 0; }
        .ol-pitch p { margin: 0; max-width: 26rem; font-size: 1.02rem; color: rgba(243, 238, 230, .78); text-wrap: pretty; }
        .ol-legal { margin-top: auto; text-align: start; font-size: .8125rem; color: rgba(243, 238, 230, .62); }

        /* ───────────── Sign-in panel ───────────── */
        .ol-panel {
            position: relative; display: flex; flex-direction: column;
            padding: clamp(1.25rem, 2.6vw, 2rem) clamp(1.25rem, 4vw, 3.5rem) clamp(1.5rem, 3vw, 2.25rem);
        }
        .ol-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }

        .ol-lang { display: inline-flex; padding: 3px; border: 1px solid var(--line); border-radius: 999px; background: var(--field); }
        .ol-lang a {
            display: grid; place-items: center; min-height: 36px; padding: 0 .95rem; border-radius: 999px;
            font-size: .875rem; font-weight: 600; text-decoration: none; color: var(--muted);
            transition: background-color .2s ease, color .2s ease;
        }
        .ol-lang a:hover { color: var(--text); }
        .ol-lang a[aria-current="true"] { background: var(--fill); color: #fff; }

        .ol-theme {
            display: grid; place-items: center; width: 44px; height: 44px; border-radius: 50%;
            border: 1px solid var(--line); background: var(--field); color: var(--text-soft); cursor: pointer;
            transition: color .2s ease, border-color .2s ease, transform .3s var(--ease);
        }
        .ol-theme:hover { color: var(--accent); border-color: var(--line-strong); transform: rotate(-14deg); }
        .ol-theme svg { width: 19px; height: 19px; }
        :root[data-theme="dark"]  .ol-theme .i-moon,
        :root[data-theme="light"] .ol-theme .i-sun { display: none; }

        .ol-main { flex: 1; display: flex; align-items: center; justify-content: center; padding-block: 2.5rem; }
        .ol-form { width: 100%; max-width: 25rem; }

        .ol-form h1 {
            margin: 0 0 .6rem; font-family: var(--f-display); font-weight: 500;
            font-size: clamp(2.1rem, 3vw, 2.75rem); line-height: 1.1; letter-spacing: -.02em;
        }
        [dir="rtl"] .ol-form h1 { font-weight: 800; letter-spacing: 0; line-height: 1.3; }
        .ol-form .ol-sub { margin: 0 0 2rem; color: var(--muted); }

        .ol-alert {
            display: flex; gap: .65rem; align-items: flex-start; margin-bottom: 1.4rem; padding: .8rem 1rem;
            border: 1px solid var(--danger-line); border-radius: 12px; background: var(--danger-bg); color: var(--danger);
            font-size: .9375rem; line-height: 1.5;
        }
        .ol-alert svg { flex: none; width: 19px; height: 19px; margin-top: 2px; }

        .ol-field { margin-bottom: 1.15rem; }
        .ol-field label { display: block; margin-bottom: .45rem; font-size: .9rem; font-weight: 600; color: var(--text-soft); }
        .ol-input { position: relative; }
        .ol-input > .ol-ico {
            position: absolute; inset-block: 0; inset-inline-start: 1rem; display: grid; place-items: center; width: 20px;
            color: var(--muted); pointer-events: none; transition: color .2s ease;
        }
        .ol-ico svg { width: 19px; height: 19px; }
        .ol-input input {
            width: 100%; height: 54px; padding: 0 1rem; padding-inline-start: 2.9rem;
            font: inherit; color: var(--text); background: var(--field);
            border: 1px solid var(--line-strong); border-radius: 12px; outline: none;
            transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
        }
        .ol-input--pw input { padding-inline-end: 3.4rem; }
        .ol-input input::placeholder { color: var(--muted); opacity: 1; }
        .ol-input input:hover { border-color: var(--muted); }
        .ol-input input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--wash); }
        .ol-input:focus-within > .ol-ico { color: var(--accent); }
        .ol-input input:-webkit-autofill {
            -webkit-text-fill-color: var(--text);
            -webkit-box-shadow: 0 0 0 100px var(--field) inset;
            transition: background-color 9999s ease-out;
        }
        .ol-field.has-error input { border-color: var(--danger); }
        .ol-field.has-error input:focus { box-shadow: 0 0 0 4px var(--danger-bg); }
        .ol-err { margin: .45rem 0 0; font-size: .875rem; color: var(--danger); }

        .ol-eye {
            position: absolute; inset-block: 0; inset-inline-end: 0; display: grid; place-items: center; width: 52px;
            border: 0; background: none; color: var(--muted); cursor: pointer; border-radius: 0 12px 12px 0;
            transition: color .2s ease;
        }
        [dir="rtl"] .ol-eye { border-radius: 12px 0 0 12px; }
        .ol-eye:hover { color: var(--text); }
        .ol-eye:focus-visible { outline: 2px solid var(--accent); outline-offset: -4px; }
        .ol-eye svg { width: 19px; height: 19px; }
        .ol-eye .i-off { display: none; }
        .ol-eye[aria-pressed="true"] .i-on  { display: none; }
        .ol-eye[aria-pressed="true"] .i-off { display: block; }

        .ol-remember { display: inline-flex; align-items: center; gap: .65rem; margin: .35rem 0 1.6rem; cursor: pointer; color: var(--text-soft); font-size: .9375rem; }
        .ol-remember input {
            appearance: none; -webkit-appearance: none; flex: none; width: 22px; height: 22px; margin: 0; cursor: pointer;
            border: 1px solid var(--line-strong); border-radius: 7px; background: var(--field);
            transition: background-color .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .ol-remember input:hover { border-color: var(--muted); }
        .ol-remember input:checked {
            border-color: var(--fill); background: var(--fill) center / 14px no-repeat
                url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><path d='M5 12.5l4.5 4.5L19 7.5'/></svg>");
        }
        .ol-remember input:focus-visible { outline: none; box-shadow: 0 0 0 4px var(--wash); border-color: var(--accent); }

        .ol-submit {
            position: relative; display: flex; align-items: center; justify-content: center; gap: .7rem;
            width: 100%; height: 56px; border: 0; border-radius: 14px; cursor: pointer;
            font: inherit; font-weight: 700; font-size: 1.0625rem; letter-spacing: .01em; color: #fff; background: var(--fill);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 10px 24px -12px rgba(30, 40, 16, .55);
            transition: background-color .2s ease, transform .2s var(--ease), box-shadow .2s ease;
        }
        .ol-submit:hover { background: var(--fill-hover); transform: translateY(-1px); box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 14px 28px -12px rgba(30, 40, 16, .6); }
        .ol-submit:active { transform: translateY(0); }
        .ol-submit:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--bg), 0 0 0 5px var(--accent); }
        .ol-submit .i-arrow { width: 20px; height: 20px; transition: transform .25s var(--ease); }
        [dir="rtl"] .ol-submit .i-arrow { transform: scaleX(-1); }
        .ol-submit:hover .i-arrow { transform: translateX(3px); }
        [dir="rtl"] .ol-submit:hover .i-arrow { transform: scaleX(-1) translateX(3px); }
        .ol-submit .i-spin { display: none; width: 20px; height: 20px; animation: ol-spin .8s linear infinite; }
        .ol-submit[aria-busy="true"] { cursor: progress; pointer-events: none; opacity: .9; }
        .ol-submit[aria-busy="true"] .i-arrow { display: none; }
        .ol-submit[aria-busy="true"] .i-spin { display: block; }
        @keyframes ol-spin { to { transform: rotate(360deg); } }

        .ol-note { display: flex; align-items: center; justify-content: center; gap: .5rem; margin: 1.75rem 0 0; font-size: .875rem; color: var(--muted); }
        .ol-note svg { flex: none; width: 16px; height: 16px; color: var(--gold); }

        /* ───────────── One authored entrance ───────────── */
        @media (prefers-reduced-motion: no-preference) {
            .ol-brand, .ol-pitch, .ol-legal { animation: ol-rise .9s var(--ease) backwards; }
            .ol-pitch  { animation-delay: .12s; }
            .ol-legal  { animation-delay: .22s; }
            .ol-form   { animation: ol-rise .8s var(--ease) .1s backwards; }
            .ol-mark--ghost { animation: ol-fade 1.6s ease .1s backwards; }
        }
        @keyframes ol-rise { from { opacity: 0; transform: translateY(18px); } }
        @keyframes ol-fade { from { opacity: 0; } }

        /* ───────────── Compact: stage collapses into a brand band ───────────── */
        @media (max-width: 899.98px) {
            .ol-shell { grid-template-columns: 1fr; }
            .ol-stage { gap: 1.25rem; padding: 1.5rem 1.25rem 1.75rem; }
            .ol-pitch { margin-top: 0; }
            .ol-pitch h2 { font-size: 1.75rem; margin-bottom: 0; max-width: 16ch; }
            [dir="rtl"] .ol-pitch h2 { max-width: none; }
            .ol-pitch p, .ol-legal { display: none; }
            .ol-mark { width: 460px; inset-inline-end: -170px; bottom: -200px; }
            .ol-lit .ol-mark { opacity: .4; }
            .ol-main { padding-block: 2rem 1rem; align-items: flex-start; }
            .ol-panel { padding-top: 1rem; }
        }
        @media (max-width: 420px) { .ol-lang a { padding: 0 .75rem; } }
        @media (max-height: 640px) and (min-width: 900px) { .ol-main { align-items: flex-start; } }
    </style>
</head>
<body>
<div class="ol-shell">

    {{-- ═════════ Brand stage ═════════ --}}
    <aside class="ol-stage" id="olStage">
        <div class="ol-glow" aria-hidden="true"></div>
        <img class="ol-mark ol-mark--ghost" src="{{ $asset('images/logo-mark-dark.webp') }}" width="1400" height="1076" alt="" aria-hidden="true">
        <div class="ol-lit" aria-hidden="true">
            <img class="ol-mark" src="{{ $asset('images/logo-mark-dark.webp') }}" width="1400" height="1076" alt="">
        </div>

        <div class="ol-brand">
            <img src="{{ $asset('images/glowrez-logo-dark_1.webp') }}" width="1500" height="424" alt="GlowRez غلو ريز">
        </div>

        <div class="ol-pitch">
            <h2>{{ __('The whole platform, in one calm view.') }}</h2>
            <p>{{ __('Companies, branches, bookings and your team — managed from here.') }}</p>
        </div>

        <div class="ol-legal" dir="ltr">© {{ date('Y') }} GlowRez</div>
    </aside>

    {{-- ═════════ Sign-in ═════════ --}}
    <main class="ol-panel">
        <div class="ol-bar">
            <nav class="ol-lang" aria-label="{{ __('Language') }}">
                <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" @if($currentLocale === 'en') aria-current="true" @endif>English</a>
                <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" lang="ar" @if($currentLocale === 'ar') aria-current="true" @endif>العربية</a>
            </nav>

            <button type="button" class="ol-theme" id="olTheme" aria-label="{{ __('Toggle theme') }}" title="{{ __('Toggle theme') }}">
                <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
        </div>

        <div class="ol-main">
            <div class="ol-form">
                <h1>{{ __('Sign in') }}</h1>
                <p class="ol-sub">{{ __('Welcome back! Sign in to continue.') }}</p>

                @if ($errors->any())
                    <div class="ol-alert" role="alert">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('owner.login.attempt') }}" id="olForm" novalidate>
                    @csrf

                    <div class="ol-field @error('email') has-error @enderror">
                        <label for="email">{{ __('Email') }}</label>
                        <div class="ol-input">
                            <span class="ol-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 7.5l8.5 6 8.5-6"/></svg></span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                   placeholder="name@glowrez.com" autocomplete="email" inputmode="email" required autofocus
                                   @error('email') aria-invalid="true" aria-describedby="email-err" @enderror>
                        </div>
                        @error('email')@if($message !== $errors->first())<p class="ol-err" id="email-err">{{ $message }}</p>@endif @enderror
                    </div>

                    <div class="ol-field @error('password') has-error @enderror">
                        <label for="password">{{ __('Password') }}</label>
                        <div class="ol-input ol-input--pw">
                            <span class="ol-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg></span>
                            <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required
                                   @error('password') aria-invalid="true" aria-describedby="password-err" @enderror>
                            <button type="button" class="ol-eye" id="olEye" aria-pressed="false" aria-controls="password" aria-label="{{ __('Show password') }}">
                                <svg class="i-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="i-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 6.1A10.4 10.4 0 0 1 12 6c6.4 0 10 6 10 6a17.6 17.6 0 0 1-3.2 3.9M6.7 7.7A17.5 17.5 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </div>
                        @error('password')@if($message !== $errors->first())<p class="ol-err" id="password-err">{{ $message }}</p>@endif @enderror
                    </div>

                    <label class="ol-remember">
                        <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                        <span>{{ __('Remember me') }}</span>
                    </label>

                    <button type="submit" class="ol-submit" id="olSubmit">
                        <span class="ol-submit-label">{{ __('Sign in') }}</span>
                        <svg class="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        <svg class="i-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                    </button>
                </form>

                <p class="ol-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.6-3.1 8.2-7.5 9.5-4.4-1.3-7.5-4.9-7.5-9.5V6z"/><path d="M9 12l2.2 2.2L15.5 10"/></svg>
                    <span>{{ __('Restricted area — authorized staff only.') }}</span>
                </p>
            </div>
        </div>
    </main>
</div>

<script>
    (function () {
        var root = document.documentElement;

        /* Theme toggle (per-viewer; persisted locally) */
        document.getElementById('olTheme').addEventListener('click', function () {
            var next = root.dataset.theme === 'light' ? 'dark' : 'light';
            root.dataset.theme = next;
            try { localStorage.setItem('ol_theme', next); } catch (e) {}
        });

        /* Password reveal */
        var eye = document.getElementById('olEye'), pw = document.getElementById('password');
        var labelShow = @json(__('Show password')), labelHide = @json(__('Hide password'));
        eye.addEventListener('click', function () {
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            eye.setAttribute('aria-pressed', show ? 'true' : 'false');
            eye.setAttribute('aria-label', show ? labelHide : labelShow);
            pw.focus({ preventScroll: true });
        });

        /* The glow: a warm light that follows the pointer across the stage and drifts when idle. */
        var stage = document.getElementById('olStage');
        var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!still) {
            var rect = stage.getBoundingClientRect(), tx = rect.width * .62, ty = rect.height * .4;
            var x = tx, y = ty, pointer = false, lastMove = 0, raf = 0;
            window.addEventListener('resize', function () { rect = stage.getBoundingClientRect(); });
            window.addEventListener('pointermove', function (e) {
                rect = stage.getBoundingClientRect();
                if (e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom) {
                    pointer = true; lastMove = performance.now();
                    tx = e.clientX - rect.left; ty = e.clientY - rect.top;
                }
            }, { passive: true });
            function frame(t) {
                if (pointer && t - lastMove > 2600) pointer = false;
                if (!pointer) {
                    tx = rect.width  * (.62 + Math.sin(t / 2600) * .12);
                    ty = rect.height * (.42 + Math.cos(t / 3400) * .10);
                }
                x += (tx - x) * (pointer ? .14 : .03);
                y += (ty - y) * (pointer ? .14 : .03);
                stage.style.setProperty('--mx', x.toFixed(1) + 'px');
                stage.style.setProperty('--my', y.toFixed(1) + 'px');
                raf = requestAnimationFrame(frame);
            }
            document.addEventListener('visibilitychange', function () {
                cancelAnimationFrame(raf); if (!document.hidden) raf = requestAnimationFrame(frame);
            });
            raf = requestAnimationFrame(frame);
        }
    })();

    /* ── Keep the CSRF token fresh so a long-idle login never returns 419 ──
       Re-fetches the token every few minutes (which also keeps the session
       alive), and guarantees a fresh token at the moment of submit. If the
       network call fails, the form still submits and the server-side handler
       falls back to a friendly "session expired, try again" redirect. */
    (function () {
        const form = document.querySelector('form[action*="login"]');
        if (!form) return;
        const field  = form.querySelector('input[name="_token"]');
        const meta   = document.querySelector('meta[name="csrf-token"]');
        const submit = document.getElementById('olSubmit');
        const label  = submit.querySelector('.ol-submit-label');
        const busyText = @json(__('Signing in…'));
        const url    = "{{ route('csrf.token') }}";

        function refresh() {
            return fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                .then(r => r.ok ? r.json() : null)
                .then(d => { if (d && d.token) { if (field) field.value = d.token; if (meta) meta.content = d.token; } })
                .catch(() => {});
        }

        // Refresh while the page sits open (also resets the session timer).
        setInterval(refresh, 5 * 60 * 1000);

        // Guarantee a valid token at submit time.
        let ready = false;
        form.addEventListener('submit', function (e) {
            if (ready) return;               // second pass — let it submit for real
            e.preventDefault();
            submit.setAttribute('aria-busy', 'true');
            label.textContent = busyText;
            refresh().finally(() => { ready = true; form.submit(); });
        });
    })();
</script>
</body>
</html>
