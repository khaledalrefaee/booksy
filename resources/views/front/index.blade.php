<!DOCTYPE html>
@php
    $isAr = app()->getLocale() === 'ar';
    $dir  = $isAr ? 'rtl' : 'ltr';
    $lang = $isAr ? 'ar' : 'en';
    $catIcons = [
        'salon'=>'fas fa-cut','spa'=>'fas fa-spa','clinic'=>'fas fa-clinic-medical',
        'beauty'=>'fas fa-magic','nail'=>'fas fa-hand-sparkles','hair'=>'fas fa-cut',
        'skin'=>'fas fa-leaf','dental'=>'fas fa-tooth','gym'=>'fas fa-dumbbell',
        'massage'=>'fas fa-hot-tub','barber'=>'fas fa-user-tie','lash'=>'fas fa-eye',
        'brow'=>'fas fa-smile','tattoo'=>'fas fa-pen-nib','wedding'=>'fas fa-ring',
        'laser'=>'fas fa-bolt',
    ];
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $isAr ? 'بوكسي – احجز موعدك في أفضل الصالونات والعيادات' : 'Booksy – Book Top Salons, Spas & Clinics' }}</title>
<meta name="description" content="{{ $isAr ? 'بوكسي: منصة حجز مواعيد صالونات التجميل، السبا، العيادات.' : 'Booksy: instant booking for beauty salons, spas & clinics.' }}">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Rouge+Script&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

<!-- Bootstrap RTL/LTR -->
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap' . ($isAr ? '.rtl' : '') . '.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/simple-line-icons/css/simple-line-icons.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.theme.default.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/magnific-popup/magnific-popup.min.css') }}">

<!-- Porto Theme -->
<link rel="stylesheet" href="{{ asset('frontend/css/theme.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme-elements.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/demos/demo-beauty-salon.css') }}">

<!-- Booksy Skin -->
<link rel="stylesheet" href="{{ asset('frontend/css/skins/skin-booksy.css') }}">

<script src="{{ asset('frontend/vendor/modernizr/modernizr.min.js') }}"></script>

<style>
@if($isAr)
body,p,li,td,input,select,textarea,.form-control{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@endif
html,body{background:#0a0a0a!important;color:rgba(255,255,255,.82)!important;}
html{scroll-behavior:smooth;}
.section{background-color:transparent!important;}
.main{background:#0a0a0a!important;}
.body{background:#0a0a0a!important;}

/* ═══════════════════════════════════════
   CARD HOVER
   ═══════════════════════════════════════ */
.bk-card-dark{
    position:relative;
    overflow:hidden;
    transition:transform .32s cubic-bezier(.25,.8,.25,1),
               box-shadow .32s cubic-bezier(.25,.8,.25,1),
               border-color .32s;
}
.bk-card-dark::before{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,transparent 40%,rgba(201,162,39,.13) 100%);
    opacity:0;
    transition:opacity .32s;
    z-index:1;
    pointer-events:none;
}
.bk-card-dark:hover{
    transform:translateY(-9px);
    border-color:rgba(201,162,39,.55)!important;
    box-shadow:0 18px 50px rgba(0,0,0,.55), 0 0 0 1px rgba(201,162,39,.2)!important;
}
.bk-card-dark:hover::before{opacity:1;}

.bk-card-dark .bk-btn-book{
    transform:translateY(6px);
    opacity:.85;
    transition:all .28s;
}
.bk-card-dark:hover .bk-btn-book{
    transform:translateY(0);
    opacity:1;
    background:#C9A227;
    color:#0a0a0a;
    border-color:#C9A227;
    box-shadow:0 4px 20px rgba(201,162,39,.35);
}

.bk-co-img{overflow:hidden;}
.bk-co-img img{transition:transform .45s cubic-bezier(.25,.8,.25,1);}
.bk-card-dark:hover .bk-co-img img{transform:scale(1.07);}

/* ═══════════════════════════════════════
   LOGO MARQUEE
   ═══════════════════════════════════════ */
.bk-marquee-wrap{
    overflow:hidden;
    position:relative;
    padding:18px 0;
    mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);
    -webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);
}
.bk-marquee-track{
    display:flex;
    gap:48px;
    width:max-content;
    animation:marqueeScroll 28s linear infinite;
}
@keyframes marqueeScroll{
    0%{transform:translateX(0);}
    100%{transform:translateX(-50%);}
}
.bk-marquee-wrap:hover .bk-marquee-track{animation-play-state:paused;}
.bk-logo-item{
    display:flex;align-items:center;justify-content:center;
    width:130px;height:64px;border-radius:10px;
    background:rgba(255,255,255,.04);border:1px solid rgba(201,162,39,.1);
    padding:10px 16px;flex-shrink:0;transition:all .28s;
}
.bk-logo-item img{
    max-width:100%;max-height:40px;object-fit:contain;
    filter:grayscale(1) brightness(.6);transition:filter .28s, transform .28s;
}
.bk-logo-item:hover img{filter:grayscale(0) brightness(1);transform:scale(1.1);}
.bk-logo-item:hover{
    background:rgba(201,162,39,.08);border-color:rgba(201,162,39,.35);
    box-shadow:0 4px 20px rgba(201,162,39,.1);
}
.bk-logo-text{
    font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:700;
    color:rgba(255,255,255,.3);text-align:center;transition:color .28s;letter-spacing:.5px;
}
.bk-logo-item:hover .bk-logo-text{color:#C9A227;}

/* ═══════════════════════════════
   COMPANY CARDS — premium style
═══════════════════════════════ */

/* section divider */
.bk-section-divider{
    height:1px;
    background:linear-gradient(90deg,transparent,rgba(201,162,39,.2) 30%,rgba(201,162,39,.2) 70%,transparent);
    margin:0;
}

/* card wrapper */
.bk-company-card{
    border-radius:18px;overflow:hidden;
    background:#141414;
    border:1px solid rgba(255,255,255,.06);
    display:flex;flex-direction:column;
    transition:transform .38s cubic-bezier(.22,1,.36,1),
               box-shadow .38s,border-color .38s;
    position:relative;cursor:pointer;
    height:100%;
}
.bk-company-card:hover{
    transform:translateY(-10px);
    border-color:rgba(201,162,39,.45);
    box-shadow:0 28px 60px rgba(0,0,0,.55),0 0 0 1px rgba(201,162,39,.2);
}

/* image area */
.bk-cc-img{
    height:220px;position:relative;overflow:hidden;
    background:#1a1a1a;
}
.bk-cc-img img{
    width:100%;height:100%;object-fit:cover;
    transition:transform .5s cubic-bezier(.22,1,.36,1);
}
.bk-company-card:hover .bk-cc-img img{transform:scale(1.08);}
.bk-cc-img::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.05) 0%,rgba(0,0,0,.55) 100%);
    pointer-events:none;
}
.bk-cc-img-placeholder{
    width:100%;height:100%;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#1a1a1a,#111);
    font-size:3.5rem;color:rgba(201,162,39,.15);
}

/* badge top-left */
.bk-cc-badge{
    position:absolute;top:12px;{{ $isAr ? 'right' : 'left' }}:12px;z-index:3;
    background:rgba(10,10,10,.85);color:#C9A227;
    font-size:.65rem;font-weight:700;padding:4px 12px;
    border-radius:20px;border:1px solid rgba(201,162,39,.3);
    backdrop-filter:blur(8px);letter-spacing:.2px;
    font-family:'Poppins',sans-serif;
}

/* rating badge top-right */
.bk-cc-rating{
    position:absolute;top:12px;{{ $isAr ? 'left' : 'right' }}:12px;z-index:3;
    background:rgba(10,10,10,.85);
    font-size:.72rem;font-weight:700;padding:4px 10px;
    border-radius:20px;border:1px solid rgba(201,162,39,.25);
    backdrop-filter:blur(8px);
    display:flex;align-items:center;gap:4px;color:#fff;
    font-family:'Poppins',sans-serif;
}
.bk-cc-rating i{color:#C9A227;font-size:.65rem;}

/* like btn */
.bk-cc-like{
    position:absolute;bottom:12px;{{ $isAr ? 'left' : 'right' }}:12px;z-index:3;
    width:36px;height:36px;border-radius:50%;border:none;
    background:rgba(10,10,10,.75);backdrop-filter:blur(6px);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all .25s;
}
.bk-cc-like:hover{background:rgba(231,76,60,.2);border:1px solid rgba(231,76,60,.4);}

/* card body */
.bk-cc-body{
    padding:16px;flex:1;display:flex;flex-direction:column;gap:8px;
}
.bk-cc-name{
    font-size:.97rem;font-weight:700;color:#ffffff;
    font-family:'Poppins',sans-serif;line-height:1.25;
    overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;
}
.bk-cc-location{
    font-size:.74rem;color:rgba(255,255,255,.4);
    display:flex;align-items:center;gap:5px;
}
.bk-cc-location i{color:#C9A227;font-size:.68rem;flex-shrink:0;}
.bk-cc-chips{display:flex;flex-wrap:wrap;gap:5px;}
.bk-cc-chip{
    background:rgba(201,162,39,.07);
    border:1px solid rgba(201,162,39,.18);
    border-radius:20px;padding:3px 10px;
    font-size:.65rem;font-weight:600;color:rgba(201,162,39,.9);
    font-family:'Poppins',sans-serif;
    display:inline-flex;align-items:center;gap:4px;
}
.bk-cc-chip i{font-size:.6rem;}
.bk-cc-stars{display:flex;align-items:center;gap:3px;}
.bk-cc-stars i{font-size:.62rem;color:#C9A227;}
.bk-cc-stars span{font-size:.7rem;color:rgba(255,255,255,.35);margin-{{ $isAr?'right':'left' }}:3px;}

/* book button — always visible, gold fill on hover */
.bk-cc-book{
    display:flex;align-items:center;justify-content:center;gap:7px;
    width:100%;padding:11px;border-radius:10px;
    border:1.5px solid rgba(201,162,39,.35);
    background:rgba(201,162,39,.05);
    color:#C9A227;font-size:.83rem;font-weight:700;
    font-family:'Poppins',sans-serif;text-decoration:none;
    transition:all .28s;margin-top:auto;
}
.bk-cc-book:hover,
.bk-company-card:hover .bk-cc-book{
    background:#C9A227;color:#0a0a0a;
    border-color:#C9A227;
    box-shadow:0 6px 20px rgba(201,162,39,.35);
    text-decoration:none;
}

/* empty state */
.bk-empty{text-align:center;padding:70px 20px;color:rgba(255,255,255,.3);}
.bk-empty i{font-size:3.5rem;color:rgba(201,162,39,.15);display:block;margin-bottom:16px;}
.bk-empty h5{color:rgba(255,255,255,.5);font-family:'Poppins',sans-serif;}

/* Navbar ── */
#bk-navbar{
    background:#0a0a0a;border-bottom:1px solid rgba(201,162,39,.15);
    padding:0 0;height:68px;z-index:1050;transition:box-shadow .3s;
}
#bk-navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.6);border-bottom-color:rgba(201,162,39,.25);}
#bk-navbar .navbar-brand{
    font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:900;
    color:#fff;letter-spacing:-1px;text-decoration:none;
}
#bk-navbar .navbar-brand span{color:#C9A227;}
#bk-navbar .navbar-toggler{border:1px solid rgba(201,162,39,.35);padding:6px 10px;color:#C9A227;background:transparent;}
#bk-navbar .navbar-toggler:focus{box-shadow:none;}
#bk-navbar .navbar-collapse{align-items:center;}
#bk-navbar .nav-link{
    color:rgba(255,255,255,.7)!important;font-family:'Poppins',sans-serif;
    font-size:.86rem;font-weight:500;padding:.5rem .9rem!important;
    border-radius:6px;transition:all .2s;
}
#bk-navbar .nav-link:hover{color:#C9A227!important;background:rgba(201,162,39,.07);}
.bk-lang{
    color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:20px;
    padding:5px 14px;font-size:.8rem;font-weight:700;font-family:'Poppins',sans-serif;
    text-decoration:none;transition:all .2s;white-space:nowrap;
}
.bk-lang:hover{background:#C9A227;color:#0a0a0a;}
.bk-login-link{
    color:rgba(255,255,255,.65);font-family:'Poppins',sans-serif;font-size:.84rem;
    font-weight:500;text-decoration:none;transition:color .2s;white-space:nowrap;
}
.bk-login-link:hover{color:#C9A227;}
.bk-register-btn{
    background:#C9A227;color:#0a0a0a!important;border:none;border-radius:22px;
    padding:8px 20px;font-size:.83rem;font-weight:700;font-family:'Poppins',sans-serif;
    text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .22s;white-space:nowrap;
}
.bk-register-btn:hover{background:#e8c84a;box-shadow:0 4px 18px rgba(201,162,39,.35);}
@media(max-width:991px){
    #bk-navbar .navbar-collapse{
        background:#111;border:1px solid rgba(201,162,39,.12);
        border-radius:12px;padding:16px;margin-top:10px;
    }
}

/* ── Hero ── */
#bk-hero{
    background:url('https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1600&q=80') center/cover no-repeat fixed;
    min-height:100vh;
}
@media(max-width:767px){#bk-hero{background-attachment:scroll;}}
.bk-co-badge{position:absolute;top:10px;{{ $isAr ? 'right' : 'left' }}:10px;z-index:3;
    background:rgba(10,10,10,.88);color:#C9A227;font-size:.67rem;font-weight:700;
    padding:4px 11px;border-radius:20px;border:1px solid rgba(201,162,39,.3);backdrop-filter:blur(6px);}
.bk-step-num{position:absolute;top:-8px;{{ $isAr ? 'left' : 'right' }}:-8px;
    width:26px;height:26px;border-radius:50%;background:#C9A227;color:#0a0a0a;
    font-size:.74rem;font-weight:900;display:flex;align-items:center;justify-content:center;
    font-family:'Poppins',sans-serif;}

/* ═══════════════════════════════
   CATEGORIES — gradient cards
═══════════════════════════════ */
#bk-cats{padding:80px 0 60px;background:#080808;}
.bk-cats-heading{
    font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;color:#fff;
    margin-bottom:6px;
}
.bk-cats-heading span{color:#C9A227;}
.bk-cats-scroll-outer{
    overflow-x:auto;padding-bottom:12px;
    scrollbar-width:thin;scrollbar-color:rgba(201,162,39,.3) transparent;
}
.bk-cats-scroll-outer::-webkit-scrollbar{height:4px;}
.bk-cats-scroll-outer::-webkit-scrollbar-track{background:transparent;}
.bk-cats-scroll-outer::-webkit-scrollbar-thumb{background:rgba(201,162,39,.3);border-radius:2px;}
.bk-cats-row{
    display:flex;gap:14px;
    width:max-content;padding:6px 0 4px;
}
/* individual card */
.bk-cat-card{
    position:relative;
    width:140px;height:180px;
    border-radius:20px;overflow:hidden;
    text-decoration:none!important;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    flex-shrink:0;cursor:pointer;
    border:1.5px solid rgba(255,255,255,.07);
    transition:transform .35s cubic-bezier(.22,1,.36,1),
               box-shadow .35s,border-color .35s;
}
.bk-cat-card::before{
    content:'';position:absolute;inset:0;
    background:var(--cg,linear-gradient(135deg,#1a1a1a,#111));
    transition:opacity .35s;
}
.bk-cat-card::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.18) 0%,rgba(0,0,0,.65) 100%);
}
.bk-cat-card>*{position:relative;z-index:3;}
.bk-cat-card:hover{
    transform:translateY(-8px) scale(1.02);
    box-shadow:0 24px 48px rgba(0,0,0,.6),0 0 0 1.5px rgba(201,162,39,.5);
    border-color:rgba(201,162,39,.5);
}
.bk-cat-card.active{
    border-color:#C9A227;
    box-shadow:0 0 0 3px rgba(201,162,39,.2),0 16px 40px rgba(0,0,0,.5);
}
/* icon circle inside card */
.bk-cat-card-icon{
    width:64px;height:64px;border-radius:50%;
    background:rgba(255,255,255,.12);
    backdrop-filter:blur(6px);
    border:1.5px solid rgba(255,255,255,.2);
    display:flex;align-items:center;justify-content:center;
    font-size:1.6rem;color:#fff;
    margin-bottom:12px;
    transition:all .3s;
    box-shadow:0 4px 20px rgba(0,0,0,.3);
}
.bk-cat-card:hover .bk-cat-card-icon,
.bk-cat-card.active .bk-cat-card-icon{
    background:#C9A227;color:#0a0a0a;
    border-color:#C9A227;
    box-shadow:0 6px 24px rgba(201,162,39,.45);
    transform:scale(1.1);
}
.bk-cat-card-img-icon{
    width:64px;height:64px;border-radius:50%;
    overflow:hidden;margin-bottom:12px;
    border:2px solid rgba(255,255,255,.2);
    box-shadow:0 4px 20px rgba(0,0,0,.4);
    transition:transform .3s;
}
.bk-cat-card:hover .bk-cat-card-img-icon{transform:scale(1.1);}
.bk-cat-card-img-icon img{width:100%;height:100%;object-fit:cover;}
.bk-cat-card-name{
    font-size:.82rem;font-weight:700;color:#fff;
    font-family:'Poppins',sans-serif;text-align:center;
    line-height:1.3;padding:0 8px;
    text-shadow:0 1px 6px rgba(0,0,0,.6);
}
.bk-cat-card-count{
    font-size:.66rem;color:rgba(255,255,255,.6);
    font-family:'Poppins',sans-serif;margin-top:3px;
}
.bk-cat-card.active .bk-cat-card-name{color:#C9A227;}
@media(max-width:767px){
    .bk-cat-card{width:120px;height:156px;}
    .bk-cat-card-icon,.bk-cat-card-img-icon{width:54px;height:54px;font-size:1.3rem;}
}

/* "All" pill */
.bk-cat-all{
    --cg:linear-gradient(135deg,#1c1c1c,#111);
}

/* category gradient presets */
.bk-cg-salon  { --cg:linear-gradient(135deg,#7f1d52,#4a0f30); }
.bk-cg-spa    { --cg:linear-gradient(135deg,#064e3b,#022c22); }
.bk-cg-clinic { --cg:linear-gradient(135deg,#1e3a5f,#0c1f36); }
.bk-cg-beauty { --cg:linear-gradient(135deg,#5b21b6,#2e1065); }
.bk-cg-nail   { --cg:linear-gradient(135deg,#9d174d,#500724); }
.bk-cg-gym    { --cg:linear-gradient(135deg,#92400e,#451a03); }
.bk-cg-dental { --cg:linear-gradient(135deg,#155e75,#083344); }
.bk-cg-laser  { --cg:linear-gradient(135deg,#1e3a5f,#172554); }
.bk-cg-tattoo { --cg:linear-gradient(135deg,#1f2937,#030712); }
.bk-cg-wedding{ --cg:linear-gradient(135deg,#7c2d12,#431407); }
.bk-cg-lash   { --cg:linear-gradient(135deg,#831843,#4a044e); }
.bk-cg-barber { --cg:linear-gradient(135deg,#1c1917,#0c0a09); }
.bk-cg-default{ --cg:linear-gradient(135deg,#713f12,#3f1f07); }
</style>
</head>
<body data-plugin-scroll-spy data-plugin-options="{'target': '#header'}">
<div class="body">

{{-- ========== HEADER ========== --}}
<nav id="bk-navbar" class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a href="{{ route('front.index') }}" class="navbar-brand">Booksy<span>.</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bkNavMenu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="bkNavMenu">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="#bk-cats">{{ $isAr ? 'التصنيفات' : 'Categories' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="#bk-companies">{{ $isAr ? 'الأماكن' : 'Places' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="#bk-services">{{ $isAr ? 'الخدمات' : 'Services' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="#bk-how">{{ $isAr ? 'كيف يعمل' : 'How It Works' }}</a></li>
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

{{-- ========== HERO ========== --}}
<section id="bk-hero" class="section overlay overlay-show overlay-op-8 border-0 m-0">
    <div class="container position-relative z-index-3" style="padding-top:130px;padding-bottom:90px;">
        <div class="row justify-content-center" style="min-height:calc(100vh - 220px);">
            <div class="col-lg-8 text-center">
                <p class="appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}"
                   style="font-size:.76rem;font-weight:700;color:#C9A227;text-transform:uppercase;letter-spacing:4px;font-family:'Poppins',sans-serif;margin-bottom:14px;">
                    ✦ {{ $isAr ? 'المنصة الأولى للحجز' : 'The #1 Booking Platform' }} ✦
                </p>
                <h1 class="text-color-light appear-animation font-weight-extra-bold"
                    style="font-size:3rem;line-height:1.15;margin-bottom:20px;"
                    data-appear-animation="blurIn" data-appear-animation-delay="300"
                    data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'احجز موعدك في' : 'Book Your Spot at' }}<br>
                    <span style="color:#C9A227;">{{ $isAr ? 'أفضل الصالونات والعيادات' : 'Top Salons & Clinics' }}</span>
                </h1>
                <p class="appear-animation" style="color:rgba(255,255,255,.78);font-size:1.05rem;font-family:'Poppins',sans-serif;margin-bottom:28px;"
                   data-appear-animation="fadeInUpShorter" data-appear-animation-delay="600"
                   data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'اكتشف صالونات التجميل، مراكز السبا، والعيادات التجميلية القريبة منك. احجز في ثوانٍ.' : 'Discover beauty salons, spas & aesthetic clinics near you. Book in seconds.' }}
                </p>
                <div class="appear-animation d-flex justify-content-center mb-4"
                     data-appear-animation="fadeInUpShorter" data-appear-animation-delay="800"
                     data-plugin-options="{'minWindowWidth':0}">
                    <form action="{{ route('front.index') }}" method="GET" style="width:100%;max-width:580px;">
                        <div class="bk-search {{ $isAr ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-search" style="color:#C9A227;font-size:.95rem;flex-shrink:0;"></i>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="{{ $isAr ? 'ابحث عن صالون، عيادة، سبا...' : 'Search salons, clinics, spas...' }}">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            <button type="submit">
                                <i class="fas fa-search {{ $isAr ? 'ms-1' : 'me-1' }}"></i>
                                {{ $isAr ? 'بحث' : 'Search' }}
                            </button>
                        </div>
                    </form>
                </div>
                <div class="appear-animation d-flex justify-content-center flex-wrap gap-3"
                     data-appear-animation="fadeInUpShorter" data-appear-animation-delay="1000"
                     data-plugin-options="{'minWindowWidth':0}">
                    <div class="bk-chip">
                        <span class="n">{{ App\Models\Company::where('status','active')->count() }}+</span>
                        <span class="l">{{ $isAr ? 'نشاط تجاري' : 'Businesses' }}</span>
                    </div>
                    <div class="bk-chip">
                        <span class="n">{{ App\Models\Appointment::count() }}+</span>
                        <span class="l">{{ $isAr ? 'حجز مكتمل' : 'Bookings' }}</span>
                    </div>
                    <div class="bk-chip">
                        <span class="n">{{ App\Models\Branch::count() }}</span>
                        <span class="l">{{ $isAr ? 'فرع' : 'Branches' }}</span>
                    </div>
                    <div class="bk-chip">
                        <span class="n">{{ App\Models\Category::count() }}</span>
                        <span class="l">{{ $isAr ? 'تصنيف' : 'Categories' }}</span>
                    </div>
                </div>
                <div class="position-absolute" style="bottom:28px;left:50%;transform:translateX(-50%);">
                    <a href="#bk-cats" data-hash data-hash-offset="80" class="text-decoration-none appear-animation"
                       data-appear-animation="fadeIn" data-appear-animation-delay="2000"
                       data-plugin-options="{'minWindowWidth':0}">
                        <i class="fas fa-chevron-down" style="font-size:1.4rem;color:rgba(201,162,39,.8);"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>



{{-- ========== CATEGORIES ========== --}}
<section id="bk-cats">
    @if($categories->isNotEmpty())
    <div class="container">
        {{-- Section header --}}
        <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <span class="section-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'تصفح حسب التصنيف' : 'Browse by Category' }}
                </span>
                <h2 class="bk-cats-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'كل ' : 'All ' }}<span>{{ $isAr ? 'التصنيفات' : 'Categories' }}</span>
                </h2>
            </div>
        </div>

        <div class="bk-cats-scroll-outer">
            <div class="bk-cats-row">
                @foreach($categories as $cat)
                @php
                    $sl = strtolower($cat->slug ?? $cat->name_en ?? '');
                    $catIcon = 'fas fa-store';
                    foreach($catIcons as $k => $v){ if(str_contains($sl,$k)){$catIcon=$v;break;} }
                    // gradient class
                    $cgClass = 'bk-cg-default';
                    $cgMap = ['salon'=>'salon','hair'=>'salon','barber'=>'barber','spa'=>'spa','massage'=>'spa',
                              'clinic'=>'clinic','dental'=>'dental','laser'=>'laser','beauty'=>'beauty',
                              'makeup'=>'beauty','lash'=>'lash','brow'=>'lash','nail'=>'nail','gym'=>'gym',
                              'tattoo'=>'tattoo','wedding'=>'wedding'];
                    foreach($cgMap as $k=>$v){ if(str_contains($sl,$k)){$cgClass='bk-cg-'.$v;break;} }
                @endphp
                <a href="{{ route('front.category', $cat->slug) }}"
                   class="bk-cat-card {{ $cgClass }}">

                    @if($cat->image)
                        <div class="bk-cat-card-img-icon">
                            <img src="{{ asset('storage/'.$cat->image) }}" alt="">
                        </div>
                    @else
                        <div class="bk-cat-card-icon"><i class="{{ $catIcon }}"></i></div>
                    @endif

                    <div class="bk-cat-card-name">{{ $isAr ? $cat->name_ar : $cat->name_en }}</div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="container">
        <div class="bk-empty"><i class="fas fa-th"></i><p>{{ $isAr ? 'لا توجد تصنيفات.' : 'No categories yet.' }}</p></div>
    </div>
    @endif
</section>






{{-- ── Divider ── --}}
<div class="bk-section-divider"></div>

{{-- ========== COMPANIES ========== --}}
<section id="bk-companies" class="section border-0 m-0" style="padding:80px 0;background:#0a0a0a;">
    <div class="container">

        {{-- Header --}}
        <div class="row align-items-end mb-5">
            <div class="col-lg-7 appear-animation" data-appear-animation="fadeInLeft" data-plugin-options="{'minWindowWidth':0}">
                <span class="section-label">
                    @if(request('search')) {{ $isAr ? 'نتائج البحث' : 'Search Results' }}
                    @elseif(request('category')) {{ $isAr ? 'تصنيف' : 'Category' }}
                    @else {{ $isAr ? 'الأماكن المميزة' : 'Featured Places' }}
                    @endif
                </span>
                <h2 class="section-heading" style="font-size:2.4rem;">
                    @if(request('search'))
                        <span>"{{ request('search') }}"</span>
                    @elseif(request('category'))
                        <span>{{ request('category') }}</span>
                    @else
                        {{ $isAr ? 'اكتشف ' : 'Discover ' }}<span>{{ $isAr ? 'أفضل الأماكن' : 'Top Places' }}</span>
                    @endif
                </h2>
                <div class="divider-gold"></div>
            </div>
            <div class="col-lg-5 appear-animation" data-appear-animation="fadeInRight" data-plugin-options="{'minWindowWidth':0}">
                <div class="d-flex align-items-center justify-content-lg-end gap-3 flex-wrap">
                    <div style="background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.2);border-radius:30px;padding:8px 20px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-store" style="color:#C9A227;font-size:.85rem;"></i>
                        <span style="font-size:.9rem;font-weight:700;color:#C9A227;font-family:'Poppins',sans-serif;">{{ $companies->total() }}</span>
                        <span style="font-size:.8rem;color:rgba(255,255,255,.45);font-family:'Poppins',sans-serif;">{{ $isAr ? 'مكان' : 'places' }}</span>
                    </div>
                    @if(request('search') || request('category'))
                    <a href="{{ route('front.index') }}"
                       style="font-size:.8rem;color:rgba(255,255,255,.45);text-decoration:none;border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:7px 14px;transition:all .2s;display:inline-flex;align-items:center;gap:5px;"
                       onmouseover="this.style.color='#C9A227';this.style.borderColor='rgba(201,162,39,.3)'"
                       onmouseout="this.style.color='rgba(255,255,255,.45)';this.style.borderColor='rgba(255,255,255,.1)'">
                        <i class="fas fa-times"></i> {{ $isAr ? 'مسح' : 'Clear' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>

        @if($companies->isNotEmpty())
        <div class="row g-4">
            @foreach($companies as $company)
            @php
                $firstBranch = $company->branches->first();
                $branchImg   = $firstBranch?->images?->first();
                $allReviews  = $company->branches->flatMap(fn($b) => $b->reviews);
                $reviewCount = $allReviews->count();
                $avgRating   = $reviewCount ? round($allReviews->avg('rating'),1) : null;
                $svcCount    = $company->branches->sum(fn($b) => $b->services->count());
                $branchCount = $company->branches->count();
            @endphp
            <div class="col-sm-6 col-lg-4 col-xl-3 appear-animation"
                 data-appear-animation="fadeInUpShorter"
                 data-appear-animation-delay="{{ ($loop->index % 4) * 80 }}"
                 data-plugin-options="{'minWindowWidth':0}">

                <div class="bk-company-card">
                    {{-- Image --}}
                    <div class="bk-cc-img">
                        @if($branchImg)
                            <img src="{{ asset('storage/'.$branchImg->path) }}" alt="{{ $isAr ? $company->name_ar : $company->name_en }}" loading="lazy">
                        @elseif($company->logo)
                            <img src="{{ asset('storage/'.$company->logo) }}" alt="" loading="lazy">
                        @else
                            <div class="bk-cc-img-placeholder"><i class="fas fa-store"></i></div>
                        @endif

                        @if($company->category)
                        <span class="bk-cc-badge">{{ $isAr ? $company->category->name_ar : $company->category->name_en }}</span>
                        @endif

                        @if($avgRating)
                        <span class="bk-cc-rating">
                            <i class="fas fa-star"></i>
                            {{ $avgRating }}
                            <span style="color:rgba(255,255,255,.4);font-weight:400;font-size:.65rem;">· {{ $reviewCount }}</span>
                        </span>
                        @endif

                        <button class="bk-cc-like bk-like-btn" data-id="{{ $company->id }}"
                                onclick="bkToggleLike(this,{{ $company->id }})">
                            <i class="far fa-heart" style="font-size:.9rem;color:rgba(255,255,255,.7);pointer-events:none;"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="bk-cc-body">
                        <div class="bk-cc-name">{{ $isAr ? $company->name_ar : $company->name_en }}</div>

                        <div class="bk-cc-location">
                            <i class="fas fa-map-marker-alt"></i>
                            @if($firstBranch?->address)
                                {{ Str::limit($firstBranch->address, 32) }}
                            @else
                                {{ $branchCount }} {{ $isAr ? ($branchCount>1?'فروع':'فرع') : 'branch'.($branchCount>1?'es':'') }}
                            @endif
                        </div>

                        @if($avgRating)
                        <div class="bk-cc-stars">
                            @for($s=1;$s<=5;$s++)
                                <i class="{{ $s<=round($avgRating)?'fas':'far' }} fa-star"></i>
                            @endfor
                            <span>({{ $reviewCount }})</span>
                        </div>
                        @endif

                        <div class="bk-cc-chips">
                            @if($svcCount>0)
                            <span class="bk-cc-chip"><i class="fas fa-cut"></i> {{ $svcCount }} {{ $isAr?'خدمة':'svcs' }}</span>
                            @endif
                            @if($branchCount>1)
                            <span class="bk-cc-chip"><i class="fas fa-map-marker-alt"></i> {{ $branchCount }} {{ $isAr?'فرع':'branches' }}</span>
                            @endif
                        </div>

                        <a href="{{ route('front.show', $company) }}#bk-services-tab" class="bk-cc-book">
                            <i class="far fa-calendar-check"></i>
                            {{ $isAr ? 'احجز الآن' : 'Book Now' }}
                            <i class="fas fa-arrow-{{ $isAr?'left':'right' }}" style="font-size:.7rem;opacity:.7;margin-{{ $isAr?'right':'left' }}:auto;"></i>
                        </a>
                    </div>
                </div>

            </div>
            @endforeach
        </div>

        @if($companies->hasPages())
        <div class="d-flex justify-content-center mt-6" style="margin-top:56px;">
            {{ $companies->links() }}
        </div>
        @endif

        @else
        <div class="bk-empty appear-animation" data-appear-animation="fadeInUp" data-plugin-options="{'minWindowWidth':0}">
            <i class="fas fa-store-slash"></i>
            <h5>{{ $isAr ? 'لا توجد نتائج' : 'No Results Found' }}</h5>
            <p>{{ $isAr ? 'جرّب بحثاً مختلفاً أو تصفّح تصنيفاً آخر.' : 'Try a different search or browse another category.' }}</p>
            <a href="{{ route('front.index') }}" class="bk-cc-book d-inline-flex mt-3" style="width:auto;padding:11px 32px;border-radius:12px;">
                {{ $isAr ? 'عرض الكل' : 'View All' }}
            </a>
        </div>
        @endif

    </div>
</section>





{{-- ========== SERVICES ========== --}}
<section id="bk-services"
    class="section overlay overlay-show overlay-op-8 border-0 m-0"
    style="padding:80px 0;background:url('https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=1600&q=80') center/cover no-repeat;">
    <div class="container position-relative z-index-3">
        <div class="text-center mb-5">
            <p class="section-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">{{ $isAr ? 'ما نقدمه' : 'What We Offer' }}</p>
            <h2 class="section-heading light appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                {{ $isAr ? 'خدمات' : 'Our' }} <span>{{ $isAr ? 'متنوعة' : 'Services' }}</span>
            </h2>
            <div class="divider-gold center"></div>
        </div>
        @php
            $svcs = [
                ['i'=>'fas fa-cut',           'ar'=>'صالونات الشعر',  'en'=>'Hair Salons',      'dar'=>'قصات، صبغات، تسريحات بأيدي محترفين.','den'=>'Professional cuts, color & styling.'],
                ['i'=>'fas fa-spa',            'ar'=>'سبا ومساج',      'en'=>'Spa & Massage',    'dar'=>'استرخِ واستعد توازنك في أفضل المراكز.','den'=>'Relax and recharge at premium spas.'],
                ['i'=>'fas fa-clinic-medical', 'ar'=>'عيادات تجميل',   'en'=>'Aesthetic Clinics','dar'=>'علاجات متقدمة بإشراف متخصصين.','den'=>'Advanced treatments by certified specialists.'],
                ['i'=>'fas fa-hand-sparkles',  'ar'=>'تجميل الأظافر',  'en'=>'Nail Art & Care',  'dar'=>'تصاميم وعناية احترافية للأظافر.','den'=>'Creative nail designs & expert care.'],
                ['i'=>'fas fa-magic',          'ar'=>'مكياج احترافي',  'en'=>'Makeup & Beauty',  'dar'=>'إطلالات مميزة لكل مناسبة.','den'=>'Stunning looks for every occasion.'],
            ];
        @endphp
        <div class="row g-4 justify-content-center">
            @foreach($svcs as $s)
            <div class="col-sm-6 col-lg-4 appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ $loop->index * 100 }}" data-plugin-options="{'minWindowWidth':0}">
                <div class="bk-svc">
                    <div class="bk-svc-icon"><i class="{{ $s['i'] }}"></i></div>
                    <h5>{{ $isAr ? $s['ar'] : $s['en'] }}</h5>
                    <p>{{ $isAr ? $s['dar'] : $s['den'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ========== COUNTERS ========== --}}
<div style="padding:60px 0;background:#050505;border-top:1px solid rgba(201,162,39,.12);border-bottom:1px solid rgba(201,162,39,.12);">
    <div class="container">
        <div class="row g-4 text-center justify-content-center appear-animation" data-appear-animation="fadeInUpShorter" data-plugin-options="{'minWindowWidth':0}">
            @php
                $counters = [
                    ['icon'=>'fas fa-store-alt',      'val'=>App\Models\Company::where('status','active')->count(), 'app'=>'+', 'ar'=>'شركة مسجلة',  'en'=>'Businesses'],
                    ['icon'=>'fas fa-calendar-check', 'val'=>App\Models\Appointment::count(),                       'app'=>'+', 'ar'=>'حجز مكتمل',   'en'=>'Bookings Done'],
                    ['icon'=>'fas fa-map-marker-alt', 'val'=>App\Models\Branch::count(),                            'app'=>'+', 'ar'=>'فرع متاح',    'en'=>'Active Branches'],
                    ['icon'=>'fas fa-star',            'val'=>App\Models\Category::count(),                          'app'=>'',  'ar'=>'تصنيف',       'en'=>'Categories'],
                ];
            @endphp
            @foreach($counters as $c)
            <div class="col-6 col-md-3">
                <div style="padding:24px 16px;">
                    <i class="{{ $c['icon'] }}" style="font-size:2rem;color:#C9A227;display:block;margin-bottom:10px;"></i>
                    <div class="bk-counter-num" data-to="{{ $c['val'] }}" data-append="{{ $c['app'] }}"
                         style="font-size:2.4rem;font-weight:800;color:#C9A227;line-height:1;font-family:'Poppins',sans-serif;text-shadow:0 0 25px rgba(201,162,39,.3);">
                        0{{ $c['app'] }}
                    </div>
                    <div style="font-size:.84rem;color:rgba(255,255,255,.5);margin-top:6px;font-family:'Poppins',sans-serif;">
                        {{ $isAr ? $c['ar'] : $c['en'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ========== HOW IT WORKS ========== --}}
<section id="bk-how" class="section border-0 m-0" style="padding:80px 0;background:#0a0a0a;">
    <div class="container">
        <div class="text-center mb-5">
            <p class="section-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">{{ $isAr ? 'بسيط وسريع' : 'Simple & Fast' }}</p>
            <h2 class="section-heading light appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                {{ $isAr ? 'كيف' : 'How' }} <span>{{ $isAr ? 'يعمل بوكسي؟' : 'Booksy Works?' }}</span>
            </h2>
            <div class="divider-gold center"></div>
        </div>
        @php
            $steps = [
                ['i'=>'fas fa-search',       'ar'=>'ابحث واختر',      'en'=>'Search & Choose',     'dar'=>'ابحث عن صالون أو عيادة في تصنيفك المفضل واختر الأنسب.','den'=>'Search for a salon or clinic in your preferred category.'],
                ['i'=>'fas fa-calendar-alt', 'ar'=>'حدد الموعد',      'en'=>'Pick a Time',         'dar'=>'اختر الخدمة والموظف والوقت المناسب من التقويم.','den'=>'Choose your service, staff member and a convenient time slot.'],
                ['i'=>'fas fa-check-circle', 'ar'=>'استمتع بالتجربة', 'en'=>'Enjoy',               'dar'=>'أكّد حجزك واستمتع بخدمة استثنائية دون انتظار.','den'=>'Confirm and enjoy a premium experience with no waiting.'],
            ];
        @endphp
        <div class="row justify-content-center align-items-center g-0">
            @foreach($steps as $i => $step)
            <div class="col-lg-3 appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ $i * 200 }}" data-plugin-options="{'minWindowWidth':0}">
                <div class="bk-step">
                    <div class="bk-step-icon">
                        <span class="bk-step-num">{{ $i+1 }}</span>
                        <i class="{{ $step['i'] }}"></i>
                    </div>
                    <h5>{{ $isAr ? $step['ar'] : $step['en'] }}</h5>
                    <p>{{ $isAr ? $step['dar'] : $step['den'] }}</p>
                </div>
            </div>
            @if(!$loop->last)
            <div class="col-lg-1 d-none d-lg-flex justify-content-center align-items-center">
                <i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}" style="font-size:1.4rem;color:rgba(201,162,39,.35);"></i>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</section>

{{-- ========== TESTIMONIALS ========== --}}
<section class="section overlay overlay-show overlay-op-8 border-0 m-0"
    style="padding:80px 0;background:url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1400&q=80') center/cover no-repeat;">
    <div class="container position-relative z-index-3">
        <div class="text-center mb-5">
            <p class="section-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">{{ $isAr ? 'آراء عملائنا' : 'What Clients Say' }}</p>
            <h2 class="section-heading light appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                {{ $isAr ? 'تجارب' : 'Real' }} <span>{{ $isAr ? 'حقيقية' : 'Experiences' }}</span>
            </h2>
            <div class="divider-gold center"></div>
        </div>
        @php
            $tests = [
                ['q_ar'=>'بوكسي غيّرت طريقتي في الحجز! سريعة وسهلة وموثوقة تماماً.','q_en'=>'Booksy changed how I book! Fast, easy, and totally reliable.','n_ar'=>'سارة م.','n_en'=>'Sarah M.','r_ar'=>'عميلة منتظمة','r_en'=>'Regular Client'],
                ['q_ar'=>'وجدت أفضل صالون في منطقتي عبر بوكسي. خدمة ممتازة جداً!','q_en'=>'Found the best salon in my area through Booksy. Excellent service!','n_ar'=>'نورة ع.','n_en'=>'Noura A.','r_ar'=>'عميلة','r_en'=>'Client'],
                ['q_ar'=>'سجّلت صالوني وتضاعفت حجوزاتي خلال أسبوعين فقط!','q_en'=>'Listed my salon and bookings doubled in just two weeks!','n_ar'=>'أميرة خ.','n_en'=>'Amira K.','r_ar'=>'صاحبة صالون','r_en'=>'Salon Owner'],
                ['q_ar'=>'أفضل منصة حجز على الإطلاق. التجربة سلسة ومريحة.','q_en'=>'Best booking platform ever. Smooth and comfortable experience.','n_ar'=>'ريم ز.','n_en'=>'Reem Z.','r_ar'=>'عميلة','r_en'=>'Client'],
            ];
        @endphp
        <div class="owl-carousel owl-theme appear-animation" id="bk-test-carousel" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="300" data-plugin-options="{'minWindowWidth':0}">
            @foreach($tests as $t)
            <div class="bk-test">
                <div class="q">"</div>
                <p>{{ $isAr ? $t['q_ar'] : $t['q_en'] }}</p>
                <hr style="border-color:rgba(201,162,39,.2);margin:14px 0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:38px;height:38px;border-radius:50%;background:rgba(201,162,39,.2);display:flex;align-items:center;justify-content:center;color:#C9A227;flex-shrink:0;"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="auth">{{ $isAr ? $t['n_ar'] : $t['n_en'] }}</div>
                        <div class="role">{{ $isAr ? $t['r_ar'] : $t['r_en'] }}</div>
                    </div>
                    <div class="{{ $isAr ? 'me-auto' : 'ms-auto' }}">
                        @for($s=0;$s<5;$s++)<i class="fas fa-star" style="color:#C9A227;font-size:.72rem;"></i>@endfor
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ========== CTA ========== --}}
<section class="section border-0 m-0" style="padding:80px 0;background:#111111;border-top:1px solid rgba(201,162,39,.1);">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 appear-animation" data-appear-animation="fadeInLeft" data-plugin-options="{'minWindowWidth':0}">
                <p style="font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:3px;color:#C9A227;font-family:'Poppins',sans-serif;margin-bottom:8px;">✦ {{ $isAr ? 'للأعمال التجارية' : 'For Businesses' }} ✦</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:2.1rem;font-weight:700;color:#fff;line-height:1.2;margin-bottom:16px;">
                    {{ $isAr ? 'هل تمتلك صالوناً أو عيادة أو' : 'Own a Salon, Clinic or' }}
                    <span style="color:#C9A227;">{{ $isAr ? 'مركز تجميل؟' : 'Beauty Center?' }}</span>
                </h2>
                <p style="color:rgba(255,255,255,.5);font-size:.96rem;margin-bottom:26px;font-family:'Poppins',sans-serif;line-height:1.7;">
                    {{ $isAr ? 'انضم إلى بوكسي وابدأ في قبول الحجوزات عبر الإنترنت.' : 'Join Booksy and start accepting online bookings today.' }}
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('company.register') }}" class="bk-cta-dark"><i class="fas fa-rocket {{ $isAr ? 'ms-2' : 'me-2' }}"></i>{{ $isAr ? 'سجّل نشاطك مجاناً' : 'Register Free' }}</a>
                    <a href="{{ route('company.login') }}" class="bk-cta-ol">{{ $isAr ? 'لديك حساب؟ ادخل' : 'Already have an account?' }}</a>
                </div>
            </div>
            <div class="col-lg-5 appear-animation" data-appear-animation="fadeInRight" data-appear-animation-delay="300" data-plugin-options="{'minWindowWidth':0}">
                <div style="background:#1a1a1a;border:1px solid rgba(201,162,39,.18);border-radius:18px;padding:32px;">
                    @php
                        $feats=[['i'=>'fas fa-calendar-alt','ar'=>'إدارة المواعيد بسهولة','en'=>'Easy Appointment Management'],['i'=>'fas fa-users','ar'=>'إدارة الموظفين والفروع','en'=>'Staff & Branch Management'],['i'=>'fas fa-chart-line','ar'=>'تقارير وإحصائيات تفصيلية','en'=>'Reports & Analytics'],['i'=>'fas fa-bell','ar'=>'إشعارات للعملاء تلقائياً','en'=>'Automated Notifications']];
                    @endphp
                    @foreach($feats as $f)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:42px;height:42px;border-radius:10px;background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.2);display:flex;align-items:center;justify-content:center;color:#C9A227;font-size:1.05rem;flex-shrink:0;"><i class="{{ $f['i'] }}"></i></div>
                        <span style="font-weight:600;color:rgba(255,255,255,.8);font-size:.9rem;font-family:'Poppins',sans-serif;flex:1;">{{ $isAr ? $f['ar'] : $f['en'] }}</span>
                        <i class="fas fa-check-circle" style="color:#C9A227;font-size:.9rem;"></i>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@include('front.partials.footer')

{{-- ========== SCRIPTS ========== --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.cookie/jquery.cookie.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easy-pie-chart/jquery.easypiechart.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/lazysizes/lazysizes.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/owl.carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/vivus/vivus.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/demos/demo-beauty-salon.js') }}"></script>
<script src="{{ asset('frontend/js/custom.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>

<script>
(function($){
    'use strict';
    $(document).ready(function(){
        var isRtl = {{ $isAr ? 'true' : 'false' }};

        /* Testimonials Carousel */
        $('#bk-test-carousel').owlCarousel({
            responsive:{ 0:{items:1}, 768:{items:2}, 992:{items:3} },
            autoplay:true, autoplayTimeout:4500, loop:true, dots:true, nav:false, rtl:isRtl, margin:8
        });

        /* Counter animation */
        $('.bk-counter-num[data-to]').each(function(){
            var $el=$(this), to=parseInt($el.data('to')), app=$el.data('append')||'';
            $el.appear(function(){
                $({c:0}).animate({c:to},{duration:2000,easing:'swing',step:function(){$el.text(Math.ceil(this.c)+app);},complete:function(){$el.text(to+app);}});
            },{accY:0});
        });

        /* Navbar scroll */
        $(window).on('scroll',function(){
            if($(this).scrollTop()>30){$('#bk-navbar').addClass('scrolled');}
            else{$('#bk-navbar').removeClass('scrolled');}
        });

        /* Init liked */
        var liked=JSON.parse(localStorage.getItem('bk_liked')||'[]');
        liked.forEach(function(id){
            var btn=document.querySelector('.bk-like-btn[data-id="'+id+'"]');
            if(btn){bkSetLiked(btn,true);}
        });
    });
})(jQuery);

function bkToggleLike(btn,id){
    var liked=JSON.parse(localStorage.getItem('bk_liked')||'[]');
    var idx=liked.indexOf(id);
    if(idx===-1){liked.push(id);bkSetLiked(btn,true);}
    else{liked.splice(idx,1);bkSetLiked(btn,false);}
    localStorage.setItem('bk_liked',JSON.stringify(liked));
}
function bkSetLiked(btn,isLiked){
    var icon=btn.querySelector('i');
    if(isLiked){icon.className='fas fa-heart';icon.style.color='#e74c3c';btn.style.background='rgba(231,76,60,.15)';btn.style.border='1px solid rgba(231,76,60,.4)';}
    else{icon.className='far fa-heart';icon.style.color='rgba(255,255,255,.7)';btn.style.background='rgba(10,10,10,.8)';btn.style.border='none';}
}
</script>

</div>
</body>
</html>
