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
    $roleLabel = $employee->role
        ? ($isAr ? ($employee->role->label_ar ?: $employee->role->label_en) : ($employee->role->label_en ?: $employee->role->label_ar))
        : '—';
    $branchLabel = $employee->all_branches
        ? ($isAr ? 'كل الفروع' : 'All branches')
        : ($employee->branch?->localizedName() ?? '—');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isAr ? 'بوابة الموظف' : 'Staff portal' }} — GlowRez</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
    <style>
        *{box-sizing:border-box;} body{margin:0;background:{{ $bg }};color:{{ $text }};
            font-family:'Segoe UI',Tahoma,system-ui,sans-serif;min-height:100vh;
            display:flex;align-items:center;justify-content:center;padding:20px;}
        .sh-card{width:100%;max-width:480px;background:{{ $surf }};border:1.5px solid {{ $border }};
            border-radius:18px;padding:28px 26px;box-shadow:0 10px 40px rgba(0,0,0,.18);}
        .sh-top{display:flex;align-items:center;gap:14px;margin-bottom:20px;}
        .sh-avatar{width:54px;height:54px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;
            justify-content:center;font-size:22px;font-weight:800;background:rgba(92,112,56,.18);color:{{ $accent }};}
        .sh-logo{font-size:13px;font-weight:800;color:{{ $muted }};}
        .sh-logo span{color:{{ $accent }};}
        .sh-name{font-size:18px;font-weight:700;margin:1px 0 0;}
        .sh-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
        .sh-chip{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;
            padding:6px 12px;border-radius:999px;background:rgba(92,112,56,.12);color:{{ $accent }};}
        .sh-chip svg{width:13px;height:13px;}
        .sh-note{display:flex;gap:11px;align-items:flex-start;background:rgba(92,112,56,.08);
            border:1.5px solid rgba(92,112,56,.22);border-radius:13px;padding:14px 15px;font-size:13px;line-height:1.6;}
        .sh-note svg{width:18px;height:18px;color:{{ $accent }};flex-shrink:0;margin-top:1px;}
        .sh-actions{display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;}
        .sh-btn{flex:1;min-width:140px;display:inline-flex;align-items:center;justify-content:center;gap:7px;
            border-radius:11px;padding:10px 14px;font-size:13px;font-weight:700;cursor:pointer;border:1.5px solid {{ $border }};
            background:transparent;color:{{ $text }};text-decoration:none;transition:background .15s;}
        .sh-btn:hover{background:rgba(92,112,56,.08);}
        .sh-btn svg{width:14px;height:14px;}
    </style>
</head>
<body>
    <div class="sh-card">
        <div class="sh-top">
            <div class="sh-avatar">{{ strtoupper(mb_substr($employee->localizedName() ?: '?',0,1)) }}</div>
            <div>
                <div class="sh-logo">Glow<span>Rez</span></div>
                <div class="sh-name">{{ $isAr ? 'مرحباً، ' : 'Welcome, ' }}{{ $employee->localizedName() }}</div>
            </div>
        </div>

        <div class="sh-meta">
            <span class="sh-chip"><i data-feather="award"></i>{{ $roleLabel }}</span>
            <span class="sh-chip"><i data-feather="git-branch"></i>{{ $branchLabel }}</span>
        </div>

        <div class="sh-note">
            <i data-feather="tool"></i>
            <div>{{ $isAr
                ? 'تم تسجيل دخولك بنجاح. لوحة الموظف الخاصة بك (المواعيد، جدولك، والمهام حسب صلاحياتك) قيد التجهيز وستتوفر قريباً.'
                : 'You are signed in. Your staff dashboard (appointments, schedule, and tasks based on your permissions) is being prepared and will be available soon.' }}</div>
        </div>

        <div class="sh-actions">
            <a href="{{ route('staff.password.change') }}" class="sh-btn">
                <i data-feather="lock"></i>{{ $isAr ? 'تغيير كلمة المرور' : 'Change password' }}
            </a>
            <form method="POST" action="{{ route('staff.logout') }}" style="flex:1;min-width:140px;margin:0;">
                @csrf
                <button type="submit" class="sh-btn" style="width:100%;">
                    <i data-feather="log-out"></i>{{ $isAr ? 'تسجيل الخروج' : 'Log out' }}
                </button>
            </form>
        </div>
    </div>
    <script>feather.replace();</script>
</body>
</html>
