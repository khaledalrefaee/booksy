<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Poppins',sans-serif;
            background:#0f0f17;
            color:#fff;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .card {
            background:#1a1f2e;
            border-radius:24px;
            padding:48px 40px;
            text-align:center;
            max-width:420px;
            width:100%;
            box-shadow:0 20px 60px rgba(0,0,0,.4);
        }
        .icon { font-size:64px; margin-bottom:20px; }
        h1 { font-size:24px; font-weight:800; margin-bottom:12px; }
        p { font-size:14px; opacity:.6; line-height:1.7; }
        .bar {
            width:60px; height:4px; border-radius:2px;
            margin:20px auto 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $icon }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="bar" style="background:{{ $color }};"></div>
    </div>
</body>
</html>
