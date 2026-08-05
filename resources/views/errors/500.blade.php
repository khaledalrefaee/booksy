@php
    $isAr = app()->getLocale() === 'ar';
    $path = request()->path();
    if (str_starts_with($path, 'company')) {
        $homeUrl = \Illuminate\Support\Facades\Route::has('company.dashboard') ? route('company.dashboard') : url('/company/login');
    } elseif (str_starts_with($path, 'owner')) {
        $homeUrl = \Illuminate\Support\Facades\Route::has('owner.dashboard') ? route('owner.dashboard') : url('/owner/login');
    } else {
        $homeUrl = url('/');
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — {{ $isAr ? 'حدث خطأ ما' : 'Something went wrong' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0d0e12;
            color: #f5f5f5;
            font-family: {{ $isAr ? "'Cairo', 'Segoe UI', sans-serif" : "'Segoe UI', Roboto, sans-serif" }};
            text-align: center;
            padding: 24px;
        }
        .wrap { max-width: 480px; }
        .brand { font-size: 15px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #C9A227; margin-bottom: 40px; }
        .code {
            font-size: 110px;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #C9A227, #f0d878);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { font-size: 22px; margin: 16px 0 8px; }
        p { color: rgba(245,245,245,.6); font-size: 14px; margin: 0 0 32px; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px; border-radius: 999px; font-size: 14px; font-weight: 700;
            text-decoration: none; transition: transform .15s, opacity .15s;
        }
        .btn-primary { background: #C9A227; color: #14151a; }
        .btn-outline { border: 1px solid rgba(255,255,255,.2); color: #f5f5f5; }
        .btn:hover { opacity: .85; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand"><img src="{{ asset('images/logo-dark.png') }}" alt="GlowRez" style="height:44px;width:auto"></div>
        <div class="code">500</div>
        <h1>{{ $isAr ? 'حدث خطأ من جانبنا' : 'Something went wrong on our end' }}</h1>
        <p>
            {{ $isAr
                ? 'الفريق التقني تم إعلامه بالمشكلة. الرجاء المحاولة مجددًا خلال قليل.'
                : 'Our team has been notified. Please try again in a moment.' }}
        </p>
        <div class="actions">
            <a href="javascript:location.reload()" class="btn btn-outline">{{ $isAr ? 'إعادة المحاولة' : 'Try again' }}</a>
            <a href="{{ $homeUrl }}" class="btn btn-primary">{{ $isAr ? 'الصفحة الرئيسية' : 'Go home' }}</a>
        </div>
    </div>
</body>
</html>
