@php
    $isAr = app()->getLocale() === 'ar';
    $branch = $appointment?->branch?->localizedName();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — GlowRez</title>
    <link rel="icon" href="{{ asset('images/logo-mark.png') }}">
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <style>
        :root{
            --olive:#6b8e23; --olive-deep:#556b2f; --gold:#c9a84a;
            --cream:#f7f4ec; --card:#fffdf8; --ink:#2f3320; --muted:#7c806e;
            --accent:{{ $color }};
        }
        *{ margin:0; padding:0; box-sizing:border-box; }
        body{
            font-family:'{{ $isAr ? "Tajawal" : "Poppins" }}','Tajawal','Poppins',sans-serif;
            background:radial-gradient(120% 90% at 50% 0%, #efeadd 0%, var(--cream) 55%);
            color:var(--ink); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
        }
        .card{
            background:var(--card); border-radius:26px; padding:40px 34px 34px; text-align:center;
            max-width:440px; width:100%; box-shadow:0 24px 60px rgba(85,107,47,.16);
            border:1px solid rgba(107,142,35,.12); position:relative; overflow:hidden;
        }
        .card::before{ content:""; position:absolute; inset:0 0 auto 0; height:6px;
            background:linear-gradient(90deg, var(--olive), var(--gold)); }
        .logo{ height:44px; width:auto; margin:6px auto 26px; display:block; }
        .badge{
            width:92px; height:92px; border-radius:50%; margin:0 auto 22px;
            display:flex; align-items:center; justify-content:center; font-size:46px;
            background:color-mix(in srgb, var(--accent) 14%, #fff);
            box-shadow:0 0 0 8px color-mix(in srgb, var(--accent) 7%, transparent);
        }
        h1{ font-size:23px; font-weight:800; margin-bottom:10px; color:var(--olive-deep); }
        .msg{ font-size:14.5px; color:var(--muted); line-height:1.75; }
        .summary{
            margin-top:22px; text-align:start; background:var(--cream); border-radius:16px;
            padding:16px 18px; border:1px solid rgba(107,142,35,.12);
        }
        .summary .row{ display:flex; gap:10px; align-items:center; font-size:14px; color:var(--ink); padding:5px 0; }
        .summary .row b{ color:var(--olive-deep); font-weight:700; }
        .summary .ic{ font-size:16px; }
        .home{
            display:inline-block; margin-top:26px; text-decoration:none; font-weight:700; font-size:14px;
            color:#fff; background:linear-gradient(135deg, var(--olive), var(--olive-deep));
            padding:13px 30px; border-radius:999px; box-shadow:0 10px 22px rgba(85,107,47,.28);
            transition:transform .15s ease, box-shadow .15s ease;
        }
        .home:hover{ transform:translateY(-1px); box-shadow:0 14px 26px rgba(85,107,47,.34); }
        .bar{ width:56px; height:4px; border-radius:2px; margin:24px auto 0; background:var(--accent); opacity:.5; }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="{{ asset('images/logo-light.png') }}" alt="GlowRez">
        <div class="badge">{{ $icon }}</div>
        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>

        @if($appointment)
            <div class="summary">
                @if($branch)
                    <div class="row"><span class="ic">📍</span> <b>{{ $branch }}</b></div>
                @endif
                <div class="row"><span class="ic">📅</span> {{ $appointment->start_time->translatedFormat('l d M Y') }}</div>
                <div class="row"><span class="ic">⏰</span> {{ $appointment->start_time->format('h:i A') }}</div>
            </div>
        @endif

        <a class="home" href="{{ url('/') }}">{{ $isAr ? 'العودة إلى GlowRez' : 'Back to GlowRez' }}</a>
        <div class="bar"></div>
    </div>
</body>
</html>
