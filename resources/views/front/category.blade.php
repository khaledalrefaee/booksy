<!DOCTYPE html>
@php
    $isAr    = app()->getLocale() === 'ar';
    $dir     = $isAr ? 'rtl' : 'ltr';
    $lang    = $isAr ? 'ar' : 'en';
    $catName = $isAr ? $category->name_ar : $category->name_en;
    $catIcons = [
        'salon'=>'fas fa-cut','spa'=>'fas fa-spa','clinic'=>'fas fa-clinic-medical',
        'beauty'=>'fas fa-magic','nail'=>'fas fa-hand-sparkles','hair'=>'fas fa-cut',
        'skin'=>'fas fa-leaf','dental'=>'fas fa-tooth','gym'=>'fas fa-dumbbell',
        'massage'=>'fas fa-hot-tub','barber'=>'fas fa-user-tie','lash'=>'fas fa-eye',
        'brow'=>'fas fa-smile','tattoo'=>'fas fa-pen-nib','wedding'=>'fas fa-ring',
        'laser'=>'fas fa-bolt',
    ];
    $catGradients = [
        'salon'  => 'linear-gradient(135deg,#7f1d52,#4a0f30)',
        'hair'   => 'linear-gradient(135deg,#7f1d52,#4a0f30)',
        'barber' => 'linear-gradient(135deg,#1c1917,#0c0a09)',
        'spa'    => 'linear-gradient(135deg,#064e3b,#022c22)',
        'massage'=> 'linear-gradient(135deg,#064e3b,#022c22)',
        'clinic' => 'linear-gradient(135deg,#1e3a5f,#0c1f36)',
        'dental' => 'linear-gradient(135deg,#155e75,#083344)',
        'laser'  => 'linear-gradient(135deg,#1e3a5f,#172554)',
        'beauty' => 'linear-gradient(135deg,#5b21b6,#2e1065)',
        'makeup' => 'linear-gradient(135deg,#5b21b6,#2e1065)',
        'lash'   => 'linear-gradient(135deg,#831843,#4a044e)',
        'brow'   => 'linear-gradient(135deg,#831843,#4a044e)',
        'nail'   => 'linear-gradient(135deg,#9d174d,#500724)',
        'gym'    => 'linear-gradient(135deg,#92400e,#451a03)',
        'tattoo' => 'linear-gradient(135deg,#1f2937,#030712)',
        'wedding'=> 'linear-gradient(135deg,#7c2d12,#431407)',
    ];
    $sl  = strtolower($category->slug ?? '');
    $catIcon = $category->icon ?: 'fas fa-store';
    $catGradient = 'linear-gradient(135deg,#713f12,#3f1f07)';
    foreach($catIcons as $k=>$v){ if(str_contains($sl,$k)){ $catIcon=$v; break; } }
    foreach($catGradients as $k=>$v){ if(str_contains($sl,$k)){ $catGradient=$v; break; } }

    // Helper: is branch open now?
    function isBranchOpen($branch): bool {
        $now = \Carbon\Carbon::now();
        $dow = (int)$now->dayOfWeek; // 0=Sun..6=Sat
        $time = $now->format('H:i');
        foreach($branch->workingHours as $wh) {
            if((int)$wh->day_of_week === $dow && $wh->is_open) {
                if($wh->open_time && $wh->close_time) {
                    if($time >= $wh->open_time && $time <= $wh->close_time) return true;
                } else {
                    return true;
                }
            }
        }
        return false;
    }
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $catName }} — Booksy</title>
<meta name="description" content="{{ $isAr ? 'تصفح '.$catName.' على بوكسي واحجز موعدك الآن.' : 'Browse '.$catName.' on Booksy and book your appointment.' }}">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap' . ($isAr ? '.rtl' : '') . '.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.theme.default.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme-elements.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/skins/skin-booksy.css') }}">
<script src="{{ asset('frontend/vendor/modernizr/modernizr.min.js') }}"></script>

<style>
@if($isAr)
body,p,li,td,input,select,textarea,.form-control{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@endif
html,body{background:#0a0a0a!important;color:rgba(255,255,255,.82)!important;}
.section{background-color:transparent!important;}
.main,.body{background:#0a0a0a!important;}

/* ── Navbar ── */
#bk-navbar{background:#0a0a0a;border-bottom:1px solid rgba(201,162,39,.15);height:68px;z-index:1050;transition:box-shadow .3s;}
#bk-navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.6);border-bottom-color:rgba(201,162,39,.25);}
#bk-navbar .navbar-brand{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:900;color:#fff;letter-spacing:-1px;text-decoration:none;}
#bk-navbar .navbar-brand span{color:#C9A227;}
#bk-navbar .navbar-toggler{border:1px solid rgba(201,162,39,.35);padding:6px 10px;color:#C9A227;background:transparent;}
#bk-navbar .navbar-toggler:focus{box-shadow:none;}
#bk-navbar .nav-link{color:rgba(255,255,255,.7)!important;font-family:'Poppins',sans-serif;font-size:.86rem;font-weight:500;padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;}
#bk-navbar .nav-link:hover{color:#C9A227!important;background:rgba(201,162,39,.07);}
.bk-lang{color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;transition:all .2s;white-space:nowrap;}
.bk-lang:hover{background:#C9A227;color:#0a0a0a;}
.bk-login-link{color:rgba(255,255,255,.65);font-family:'Poppins',sans-serif;font-size:.84rem;font-weight:500;text-decoration:none;transition:color .2s;white-space:nowrap;}
.bk-login-link:hover{color:#C9A227;}
.bk-register-btn{background:#C9A227;color:#0a0a0a!important;border:none;border-radius:22px;padding:8px 20px;font-size:.83rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .22s;white-space:nowrap;}
.bk-register-btn:hover{background:#e8c84a;box-shadow:0 4px 18px rgba(201,162,39,.35);}
@media(max-width:991px){#bk-navbar .navbar-collapse{background:#111;border:1px solid rgba(201,162,39,.12);border-radius:12px;padding:16px;margin-top:10px;}}

/* ── Hero band ── */
.bk-cat-hero{
    padding:120px 0 60px;
    background: {{ $catGradient }};
    position:relative;overflow:hidden;
}
.bk-cat-hero::before{
    content:'';position:absolute;inset:0;
    background:radial-gradient(ellipse at 60% 40%,rgba(201,162,39,.12) 0%,transparent 65%),
               radial-gradient(ellipse at 20% 70%,rgba(255,255,255,.04) 0%,transparent 50%);
}
.bk-cat-hero::after{
    content:'';position:absolute;bottom:0;left:0;right:0;height:80px;
    background:linear-gradient(0deg,#0a0a0a,transparent);
}

/* ── Quick category strip ── */
.bk-qcat-strip{
    background:#111;border-bottom:1px solid rgba(201,162,39,.1);
    padding:16px 0;overflow-x:auto;
    scrollbar-width:thin;scrollbar-color:rgba(201,162,39,.2) transparent;
}
.bk-qcat-strip::-webkit-scrollbar{height:3px;}
.bk-qcat-strip::-webkit-scrollbar-thumb{background:rgba(201,162,39,.25);border-radius:2px;}
.bk-qcat-inner{display:flex;gap:10px;width:max-content;padding:0 16px;}
.bk-qcat-pill{
    display:inline-flex;align-items:center;gap:6px;
    padding:6px 16px;border-radius:20px;white-space:nowrap;text-decoration:none;
    font-size:.78rem;font-weight:600;font-family:'Poppins',sans-serif;
    border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);
    background:rgba(255,255,255,.04);transition:all .22s;
}
.bk-qcat-pill:hover,.bk-qcat-pill.active{
    background:rgba(201,162,39,.12);border-color:rgba(201,162,39,.4);color:#C9A227;
}

/* ── Search bar ── */
.bk-search-bar{
    display:flex;gap:0;
    background:#161616;border:1px solid rgba(201,162,39,.25);border-radius:12px;overflow:hidden;
    transition:border-color .2s;
}
.bk-search-bar:focus-within{border-color:#C9A227;}
.bk-search-bar input{
    flex:1;background:transparent;border:none;outline:none;padding:13px 18px;
    color:#fff;font-size:.9rem;font-family:'Poppins',sans-serif;
}
.bk-search-bar input::placeholder{color:rgba(255,255,255,.3);}
.bk-search-bar button{
    background:#C9A227;border:none;padding:0 24px;color:#0a0a0a;font-weight:700;
    font-size:.85rem;font-family:'Poppins',sans-serif;cursor:pointer;transition:background .2s;
    display:flex;align-items:center;gap:7px;white-space:nowrap;
}
.bk-search-bar button:hover{background:#e8c84a;}

/* ── Branch card ── */
.bk-branch-card{
    border-radius:16px;overflow:hidden;
    background:#141414;border:1px solid rgba(255,255,255,.06);
    display:flex;flex-direction:column;height:100%;
    transition:transform .35s cubic-bezier(.22,1,.36,1),box-shadow .35s,border-color .35s;
    position:relative;
}
.bk-branch-card:hover{
    transform:translateY(-8px);
    border-color:rgba(201,162,39,.4);
    box-shadow:0 24px 56px rgba(0,0,0,.55),0 0 0 1px rgba(201,162,39,.18);
}
.bk-bc-img{height:200px;overflow:hidden;position:relative;background:#1a1a1a;}
.bk-bc-img img{width:100%;height:100%;object-fit:cover;transition:transform .45s cubic-bezier(.22,1,.36,1);}
.bk-branch-card:hover .bk-bc-img img{transform:scale(1.07);}
.bk-bc-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.6) 100%);}
.bk-bc-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3.5rem;color:rgba(201,162,39,.15);}
.bk-bc-badge{
    position:absolute;top:10px;{{ $isAr ? 'right' : 'left' }}:10px;z-index:3;
    background:rgba(10,10,10,.88);color:#C9A227;font-size:.65rem;font-weight:700;
    padding:3px 11px;border-radius:20px;border:1px solid rgba(201,162,39,.3);backdrop-filter:blur(6px);
}
.bk-bc-open{
    position:absolute;top:10px;{{ $isAr ? 'left' : 'right' }}:10px;z-index:3;
    font-size:.65rem;font-weight:700;padding:4px 11px;border-radius:20px;backdrop-filter:blur(6px);
}
.bk-bc-open.open{background:rgba(5,150,105,.85);color:#d1fae5;border:1px solid rgba(5,150,105,.5);}
.bk-bc-open.closed{background:rgba(185,28,28,.85);color:#fee2e2;border:1px solid rgba(185,28,28,.5);}
.bk-bc-body{padding:15px;flex:1;display:flex;flex-direction:column;gap:7px;}
.bk-bc-name{font-size:.94rem;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;line-height:1.3;}
.bk-bc-company{font-size:.74rem;color:rgba(255,255,255,.4);font-family:'Poppins',sans-serif;}
.bk-bc-loc{font-size:.73rem;color:rgba(255,255,255,.4);display:flex;align-items:center;gap:5px;}
.bk-bc-loc i{color:#C9A227;font-size:.66rem;}
.bk-bc-chips{display:flex;flex-wrap:wrap;gap:4px;}
.bk-bc-chip{background:rgba(201,162,39,.07);border:1px solid rgba(201,162,39,.15);border-radius:20px;padding:3px 9px;font-size:.63rem;font-weight:600;color:rgba(201,162,39,.85);font-family:'Poppins',sans-serif;}
.bk-bc-stars{display:flex;align-items:center;gap:3px;}
.bk-bc-stars i{font-size:.6rem;color:#C9A227;}
.bk-bc-stars span{font-size:.68rem;color:rgba(255,255,255,.35);margin-{{ $isAr?'right':'left' }}:4px;}
.bk-bc-book{
    display:flex;align-items:center;justify-content:center;gap:7px;
    width:100%;padding:10px;border-radius:10px;
    border:1.5px solid rgba(201,162,39,.35);background:rgba(201,162,39,.05);
    color:#C9A227;font-size:.82rem;font-weight:700;font-family:'Poppins',sans-serif;
    text-decoration:none;transition:all .25s;margin-top:auto;
}
.bk-bc-book:hover,.bk-branch-card:hover .bk-bc-book{
    background:#C9A227;color:#0a0a0a;border-color:#C9A227;
    box-shadow:0 5px 18px rgba(201,162,39,.3);text-decoration:none;
}
.bk-empty{text-align:center;padding:70px 20px;color:rgba(255,255,255,.3);}
.bk-empty i{font-size:3.5rem;color:rgba(201,162,39,.15);display:block;margin-bottom:16px;}
.bk-empty h5{color:rgba(255,255,255,.5);font-family:'Poppins',sans-serif;}
</style>
</head>
<body data-plugin-scroll-spy data-plugin-options="{'target': '#header'}">
<div class="body">

{{-- ========== NAVBAR ========== --}}
<nav id="bk-navbar" class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a href="{{ route('front.index') }}" class="navbar-brand">Booksy<span>.</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bkNavMenu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="bkNavMenu">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('front.index') }}">{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل' : 'Contact' }}</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                @if($isAr)
                    <a href="{{ route('locale.switch','en') }}" class="bk-lang">EN</a>
                @else
                    <a href="{{ route('locale.switch','ar') }}" class="bk-lang">عربي</a>
                @endif
                <a href="{{ route('company.login') }}" class="bk-login-link d-none d-lg-inline">
                    {{ $isAr ? 'دخول الأعمال' : 'Business Login' }}
                </a>
                <a href="{{ route('company.register') }}" class="bk-register-btn">
                    <i class="fas fa-store"></i>
                    {{ $isAr ? 'سجّل نشاطك' : 'List Business' }}
                </a>
            </div>
        </div>
    </div>
</nav>

<div role="main" class="main">

{{-- ========== HERO BAND ========== --}}
<div class="bk-cat-hero">
    <div class="container position-relative" style="z-index:2;">
        <div class="d-flex align-items-center gap-3 mb-3">
            <a href="{{ route('front.index') }}"
               style="color:rgba(255,255,255,.5);font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:5px;">
                <i class="fas fa-home"></i> {{ $isAr ? 'الرئيسية' : 'Home' }}
            </a>
            <i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}" style="font-size:.65rem;color:rgba(255,255,255,.3);"></i>
            <span style="color:#C9A227;font-size:.8rem;font-weight:600;">{{ $catName }}</span>
        </div>
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div style="width:72px;height:72px;border-radius:18px;background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;backdrop-filter:blur(8px);flex-shrink:0;">
                @if($category->image)
                    <img src="{{ asset('storage/'.$category->image) }}" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:12px;">
                @else
                    <i class="{{ $catIcon }}"></i>
                @endif
            </div>
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:2.4rem;font-weight:900;color:#fff;margin:0;line-height:1.1;">
                    {{ $catName }}
                </h1>
                <p style="color:rgba(255,255,255,.7);margin:6px 0 0;font-family:'Poppins',sans-serif;font-size:.92rem;">
                    {{ $branches->total() }} {{ $isAr ? 'فرع متاح' : 'branches available' }}
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ========== QUICK CAT STRIP ========== --}}
<div class="bk-qcat-strip">
    <div class="bk-qcat-inner">
        @foreach($categories as $qc)
        @php
            $qsl  = strtolower($qc->slug ?? '');
            $qico = 'fas fa-store';
            foreach($catIcons as $k=>$v){ if(str_contains($qsl,$k)){ $qico=$v; break; } }
        @endphp
        <a href="{{ route('front.category', $qc->slug) }}"
           class="bk-qcat-pill {{ $qc->slug === $category->slug ? 'active' : '' }}">
            <i class="{{ $qico }}"></i>
            {{ $isAr ? $qc->name_ar : $qc->name_en }}
        </a>
        @endforeach
    </div>
</div>

{{-- ========== SEARCH + RESULTS ========== --}}
<section style="padding:48px 0 80px;background:#0a0a0a;">
    <div class="container">

        {{-- Search --}}
        <form method="GET" action="{{ route('front.category', $category->slug) }}" class="mb-5">
            <div class="row g-3 align-items-end">
                <div class="col-md-8 col-lg-7">
                    <div class="bk-search-bar">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ $isAr ? 'ابحث عن فرع أو خدمة أو موقع...' : 'Search branch, service or location...' }}">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                            {{ $isAr ? 'بحث' : 'Search' }}
                        </button>
                    </div>
                </div>
                @if(request('search'))
                <div class="col-auto">
                    <a href="{{ route('front.category', $category->slug) }}"
                       style="color:rgba(255,255,255,.45);font-size:.83rem;text-decoration:none;border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:12px 16px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;"
                       onmouseover="this.style.color='#C9A227'" onmouseout="this.style.color='rgba(255,255,255,.45)'">
                        <i class="fas fa-times"></i> {{ $isAr ? 'مسح' : 'Clear' }}
                    </a>
                </div>
                @endif
            </div>
            @if(request('search'))
            <p style="margin-top:12px;font-size:.82rem;color:rgba(255,255,255,.4);">
                {{ $isAr ? 'نتائج البحث عن:' : 'Results for:' }}
                <strong style="color:#C9A227;">"{{ request('search') }}"</strong>
                — {{ $branches->total() }} {{ $isAr ? 'نتيجة' : 'results' }}
            </p>
            @endif
        </form>

        {{-- Results heading --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.75rem;color:#fff;margin:0;">
                {{ $isAr ? 'الفروع المتاحة' : 'Available Branches' }}
                <span style="color:#C9A227;font-size:1.1rem;font-weight:400;"> ({{ $branches->total() }})</span>
            </h2>
        </div>

        {{-- Branch cards grid --}}
        @if($branches->isNotEmpty())
        <div class="row g-4">
            @foreach($branches as $branch)
            @php
                $brImg     = $branch->images->first();
                $revCount  = $branch->reviews->count();
                $avgRev    = $revCount ? round($branch->reviews->avg('rating'),1) : null;
                $svcCount  = $branch->services->count();
                $empCount  = $branch->employees->count();
                $isOpen    = isBranchOpen($branch);
                $compName  = $isAr ? ($branch->company->name_ar ?? $branch->company->name_en) : $branch->company->name_en;
                $brName    = $isAr ? ($branch->name_ar ?: $branch->name_en) : $branch->name_en;
            @endphp
            <div class="col-sm-6 col-lg-4 appear-animation"
                 data-appear-animation="fadeInUpShorter"
                 data-appear-animation-delay="{{ ($loop->index % 3) * 80 }}"
                 data-plugin-options="{'minWindowWidth':0}">
                <div class="bk-branch-card">
                    <div class="bk-bc-img">
                        @if($brImg)
                            <img src="{{ asset('storage/'.$brImg->path) }}" alt="{{ $brName }}" loading="lazy">
                        @elseif($branch->company->logo)
                            <img src="{{ asset('storage/'.$branch->company->logo) }}" alt="" loading="lazy">
                        @else
                            <div class="bk-bc-img-placeholder"><i class="{{ $catIcon }}"></i></div>
                        @endif

                        <span class="bk-bc-badge">{{ $catName }}</span>
                        <span class="bk-bc-open {{ $isOpen ? 'open' : 'closed' }}">
                            <i class="fas fa-circle" style="font-size:.45rem;vertical-align:middle;margin-{{ $isAr?'left':'right' }}:4px;"></i>
                            {{ $isOpen ? ($isAr ? 'مفتوح' : 'Open') : ($isAr ? 'مغلق' : 'Closed') }}
                        </span>
                    </div>
                    <div class="bk-bc-body">
                        <div class="bk-bc-name">{{ $brName }}</div>
                        @if($compName)
                        <div class="bk-bc-company"><i class="fas fa-building {{ $isAr?'ms-1':'me-1' }}" style="color:#C9A227;font-size:.6rem;"></i>{{ $compName }}</div>
                        @endif
                        @if($branch->address)
                        <div class="bk-bc-loc">
                            <i class="fas fa-map-marker-alt"></i>
                            {{ Str::limit($branch->address, 36) }}
                        </div>
                        @endif
                        @if($avgRev)
                        <div class="bk-bc-stars">
                            @for($s=1;$s<=5;$s++)
                                <i class="{{ $s<=$avgRev?'fas':'far' }} fa-star"></i>
                            @endfor
                            <span>({{ $revCount }})</span>
                        </div>
                        @endif
                        <div class="bk-bc-chips">
                            @if($svcCount)
                            <span class="bk-bc-chip"><i class="fas fa-cut {{ $isAr?'ms-1':'me-1' }}"></i>{{ $svcCount }} {{ $isAr?'خدمة':'services' }}</span>
                            @endif
                            @if($empCount)
                            <span class="bk-bc-chip"><i class="fas fa-user {{ $isAr?'ms-1':'me-1' }}"></i>{{ $empCount }} {{ $isAr?'موظف':'staff' }}</span>
                            @endif
                        </div>
                        <a href="{{ route('front.branch', $branch) }}" class="bk-bc-book">
                            <i class="far fa-calendar-check"></i>
                            {{ $isAr ? 'عرض وحجز' : 'View & Book' }}
                            <i class="fas fa-arrow-{{ $isAr?'left':'right' }}" style="font-size:.65rem;opacity:.7;margin-{{ $isAr?'right':'left' }}:auto;"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($branches->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $branches->links() }}
        </div>
        @endif

        @else
        <div class="bk-empty">
            <i class="{{ $catIcon }}"></i>
            <h5>{{ $isAr ? 'لا توجد نتائج' : 'No Results Found' }}</h5>
            <p>{{ $isAr ? 'جرّب بحثاً مختلفاً أو تصفّح تصنيفاً آخر.' : 'Try a different search or browse another category.' }}</p>
            <a href="{{ route('front.index') }}"
               style="display:inline-flex;align-items:center;gap:7px;margin-top:16px;padding:10px 28px;border-radius:10px;border:1.5px solid rgba(201,162,39,.4);color:#C9A227;text-decoration:none;font-family:'Poppins',sans-serif;font-weight:700;font-size:.85rem;transition:all .2s;"
               onmouseover="this.style.background='#C9A227';this.style.color='#0a0a0a'"
               onmouseout="this.style.background='transparent';this.style.color='#C9A227'">
                <i class="fas fa-home"></i> {{ $isAr ? 'العودة للرئيسية' : 'Back to Home' }}
            </a>
        </div>
        @endif
    </div>
</section>

@include('front.partials.footer')

<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.cookie/jquery.cookie.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/owl.carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/custom.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>
<script>
(function($){
    'use strict';
    $(document).ready(function(){
        $(window).on('scroll',function(){
            if($(this).scrollTop()>30){$('#bk-navbar').addClass('scrolled');}
            else{$('#bk-navbar').removeClass('scrolled');}
        });
    });
})(jQuery);
</script>
</div>
</body>
</html>
