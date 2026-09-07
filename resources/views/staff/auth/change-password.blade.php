@php
    $theme = request()->cookie('company_theme', 'dark');
    $isAr  = app()->getLocale() === 'ar';
    $dark  = $theme !== 'light';
    $bg    = $dark ? '#1B2015' : '#F7F5EF';
    $surf  = $dark ? '#252C1B' : '#FFFFFF';
    $border= $dark ? '#3A4330' : '#E7E1D3';
    $text  = $dark ? '#F0EEE3' : '#22251D';
    $muted = $dark ? '#8B9078' : '#7B7C6D';
    $accent= '#5C7038';
    $accent2='#3C4B29';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isAr ? 'تعيين كلمة المرور' : 'Set your password' }} — GlowRez</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
    <style>
        *{box-sizing:border-box;} body{margin:0;background:{{ $bg }};color:{{ $text }};
            font-family:'Segoe UI',Tahoma,system-ui,sans-serif;min-height:100vh;
            display:flex;align-items:center;justify-content:center;padding:20px;}
        .sp-card{width:100%;max-width:430px;background:{{ $surf }};border:1.5px solid {{ $border }};
            border-radius:18px;padding:30px 28px;box-shadow:0 10px 40px rgba(0,0,0,.18);}
        .sp-logo{font-size:20px;font-weight:800;text-align:center;margin-bottom:2px;}
        .sp-logo span{color:{{ $accent }};}
        .sp-badge{width:52px;height:52px;border-radius:15px;margin:14px auto 12px;display:flex;
            align-items:center;justify-content:center;background:rgba(92,112,56,.15);color:{{ $accent }};}
        .sp-badge svg{width:24px;height:24px;}
        h1{font-size:18px;font-weight:700;text-align:center;margin:0 0 4px;}
        .sp-sub{font-size:12.5px;color:{{ $muted }};text-align:center;line-height:1.6;margin-bottom:20px;}
        label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:{{ $muted }};
            display:block;margin-bottom:5px;}
        .sp-field{margin-bottom:15px;}
        input[type=password]{width:100%;background:{{ $dark ? 'rgba(255,255,255,.05)' : '#fff' }};
            border:1.5px solid {{ $border }};border-radius:11px;padding:11px 13px;font-size:14px;color:{{ $text }};
            outline:none;transition:border-color .2s,box-shadow .2s;}
        input:focus{border-color:{{ $accent }};box-shadow:0 0 0 3px rgba(92,112,56,.18);}
        .sp-btn{width:100%;margin-top:6px;background:linear-gradient(135deg,{{ $accent }},{{ $accent2 }});
            color:#fff;border:none;border-radius:12px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;
            display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .2s,transform .15s;}
        .sp-btn:hover{opacity:.93;transform:translateY(-1px);}
        .sp-err{background:rgba(245,87,108,.12);color:#f5576c;border-radius:10px;padding:10px 13px;
            font-size:12.5px;margin-bottom:16px;}
        .sp-err ul{margin:0;padding-inline-start:18px;} .sp-err li{margin:2px 0;}
        .sp-logout{display:block;text-align:center;margin-top:16px;font-size:12px;color:{{ $muted }};
            background:none;border:none;cursor:pointer;text-decoration:underline;width:100%;}
    </style>
</head>
<body>
    <div class="sp-card">
        <div class="sp-logo">Glow<span>Rez</span></div>
        <div class="sp-badge"><i data-feather="lock"></i></div>
        <h1>{{ $isAr ? 'عيّن كلمة مرورك' : 'Set your password' }}</h1>
        <p class="sp-sub">
            {{ $isAr
                ? 'مرحباً '.$employee->localizedName().' — لأمان حسابك، عيّن كلمة مرور جديدة خاصة فيك قبل المتابعة.'
                : 'Hi '.$employee->localizedName().' — for your account security, set a new personal password before continuing.' }}
        </p>

        @if($errors->any())
            <div class="sp-err">
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.password.update') }}">
            @csrf @method('PUT')
            <div class="sp-field">
                <label>{{ $isAr ? 'كلمة المرور الحالية' : 'Current password' }}</label>
                <input type="password" name="current_password" autocomplete="current-password" required autofocus>
            </div>
            <div class="sp-field">
                <label>{{ $isAr ? 'كلمة المرور الجديدة' : 'New password' }}</label>
                <input type="password" name="password" autocomplete="new-password" required
                       placeholder="{{ $isAr ? '٨ أحرف على الأقل' : 'At least 8 characters' }}">
            </div>
            <div class="sp-field">
                <label>{{ $isAr ? 'تأكيد كلمة المرور' : 'Confirm password' }}</label>
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <button type="submit" class="sp-btn">
                <i data-feather="check"></i>{{ $isAr ? 'حفظ والمتابعة' : 'Save and continue' }}
            </button>
        </form>

        <form method="POST" action="{{ route('staff.logout') }}">
            @csrf
            <button type="submit" class="sp-logout">{{ $isAr ? 'تسجيل الخروج' : 'Log out' }}</button>
        </form>
    </div>
    <script>feather.replace();</script>
</body>
</html>
