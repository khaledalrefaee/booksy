<!DOCTYPE html>
@php
    $isAr   = app()->getLocale() === 'ar';
    $dir    = $isAr ? 'rtl' : 'ltr';
    $lang   = $isAr ? 'ar' : 'en';
    $name   = $isAr ? $company->name_ar : $company->name_en;
    $catName= $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : '';
    $stars  = round($avgRating * 2) / 2; // half-star rounding
    $totalRev = $reviews->count();
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,shrink-to-fit=no">
<title>{{ $name }} – Booksy</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap'.($isAr?'.rtl':'').'.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.theme.default.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/magnific-popup/magnific-popup.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap-star-rating/css/star-rating.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme-elements.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/skins/skin-booksy.css') }}">
<script src="{{ asset('frontend/vendor/modernizr/modernizr.min.js') }}"></script>

<style>
@if($isAr)
body,p,span,li,input,select,textarea,button{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@endif
html,body{background:#0a0a0a!important;color:rgba(255,255,255,.82)!important;scroll-behavior:smooth;}
.section{background-color:transparent!important;}
.main,.body{background:#0a0a0a!important;}

/* ── Navbar ── */
#bk-navbar{background:#0a0a0a;border-bottom:1px solid rgba(201,162,39,.15);height:68px;z-index:1050;transition:box-shadow .3s;}
#bk-navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.7);}
#bk-navbar .navbar-brand{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:900;color:#fff;letter-spacing:-1px;text-decoration:none;}
#bk-navbar .navbar-brand span{color:#C9A227;}
#bk-navbar .navbar-toggler{border:1px solid rgba(201,162,39,.35);padding:6px 10px;color:#C9A227;background:transparent;}
#bk-navbar .navbar-toggler:focus{box-shadow:none;}
#bk-navbar .nav-link{color:rgba(255,255,255,.7)!important;font-family:'Poppins',sans-serif;font-size:.86rem;font-weight:500;padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;}
#bk-navbar .nav-link:hover{color:#C9A227!important;background:rgba(201,162,39,.07);}
.bk-lang{color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;text-decoration:none;transition:all .2s;}
.bk-lang:hover{background:#C9A227;color:#0a0a0a;}
.bk-register-btn{background:#C9A227;color:#0a0a0a!important;border:none;border-radius:22px;padding:8px 20px;font-size:.83rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .22s;}
.bk-register-btn:hover{background:#e8c84a;box-shadow:0 4px 18px rgba(201,162,39,.35);}
@media(max-width:991px){#bk-navbar .navbar-collapse{background:#111;border:1px solid rgba(201,162,39,.12);border-radius:12px;padding:16px;margin-top:10px;}}

/* ════════════════════════
   COVER HERO
════════════════════════ */
#bk-cover{
    height:420px;position:relative;overflow:hidden;
    background:#111 url('') center/cover no-repeat;
    margin-top:68px;
}
#bk-cover::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(10,10,10,.3) 0%,rgba(10,10,10,.92) 100%);
}
.bk-cover-slides{position:absolute;inset:0;z-index:0;}
.bk-cover-slides img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;opacity:0;transition:opacity 1s;}
.bk-cover-slides img.active{opacity:1;}
.cover-nav{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);z-index:3;display:flex;gap:6px;}
.cover-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.35);border:none;padding:0;cursor:pointer;transition:all .3s;}
.cover-dot.active{background:#C9A227;width:22px;border-radius:4px;}

/* Profile info bar */
#bk-profile-bar{
    position:relative;z-index:5;
    background:rgba(10,10,10,.95);
    border-bottom:1px solid rgba(201,162,39,.12);
    backdrop-filter:blur(12px);
}
.bk-logo-wrap{
    width:90px;height:90px;border-radius:18px;
    border:3px solid rgba(201,162,39,.4);overflow:hidden;
    background:#1a1a1a;flex-shrink:0;
    box-shadow:0 8px 30px rgba(0,0,0,.5);
    margin-top:-36px;
}
.bk-logo-wrap img{width:100%;height:100%;object-fit:cover;}
.bk-logo-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#C9A227;}
.bk-biz-name{font-size:1.6rem;font-weight:800;color:#fff;line-height:1.15;font-family:'Playfair Display',serif;}
.bk-biz-cat{background:rgba(201,162,39,.12);color:#C9A227;border:1px solid rgba(201,162,39,.25);border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:700;font-family:'Poppins',sans-serif;display:inline-block;}
.bk-rating-wrap{display:flex;align-items:center;gap:8px;}
.bk-rating-num{font-size:1.1rem;font-weight:800;color:#C9A227;font-family:'Poppins',sans-serif;}
.bk-rating-count{font-size:.78rem;color:rgba(255,255,255,.4);}
.bk-stars{color:#C9A227;font-size:.8rem;letter-spacing:1px;}
.bk-book-hero{
    background:#C9A227;color:#0a0a0a;border:none;border-radius:12px;
    padding:12px 28px;font-size:.94rem;font-weight:700;font-family:'Poppins',sans-serif;
    text-decoration:none;display:inline-flex;align-items:center;gap:8px;
    transition:all .25s;white-space:nowrap;
}
.bk-book-hero:hover{background:#e8c84a;color:#0a0a0a;box-shadow:0 6px 24px rgba(201,162,39,.4);transform:translateY(-2px);}

/* ════════════════════════
   STICKY TABS
════════════════════════ */
#bk-tabs-bar{
    background:#0d0d0d;border-bottom:1px solid rgba(201,162,39,.1);
    position:sticky;top:68px;z-index:100;
}
.bk-tab-link{
    display:inline-flex;align-items:center;gap:7px;
    padding:14px 20px;color:rgba(255,255,255,.5);
    font-size:.84rem;font-weight:600;font-family:'Poppins',sans-serif;
    text-decoration:none;border-bottom:2px solid transparent;
    transition:all .22s;white-space:nowrap;
}
.bk-tab-link i{font-size:.85rem;}
.bk-tab-link:hover{color:#C9A227;}
.bk-tab-link.active{color:#C9A227;border-bottom-color:#C9A227;}

/* ════════════════════════
   SERVICES SECTION
════════════════════════ */
.bk-cat-header{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 0;cursor:pointer;border-bottom:1px solid rgba(201,162,39,.1);
    margin-bottom:16px;
}
.bk-cat-header h4{font-size:1rem;font-weight:700;color:#fff;margin:0;font-family:'Poppins',sans-serif;}
.bk-cat-count{background:rgba(201,162,39,.12);color:#C9A227;border-radius:20px;padding:2px 10px;font-size:.72rem;font-weight:700;font-family:'Poppins',sans-serif;}
.bk-cat-toggle{color:rgba(255,255,255,.3);font-size:.8rem;transition:transform .3s;}
.bk-cat-header.collapsed .bk-cat-toggle{transform:rotate(-90deg);}

/* Service card — Booksy style */
.bk-service-card{
    display:flex;align-items:stretch;gap:0;
    background:#111;border:1px solid rgba(201,162,39,.1);border-radius:14px;
    overflow:hidden;margin-bottom:12px;
    transition:all .28s cubic-bezier(.25,.8,.25,1);
    position:relative;
}
.bk-service-card:hover{
    border-color:rgba(201,162,39,.4);
    box-shadow:0 8px 32px rgba(0,0,0,.4);
    transform:translateX({{ $isAr ? '-' : '' }}4px);
}
.bk-service-card::before{
    content:'';position:absolute;{{ $isAr ? 'right' : 'left' }}:0;top:0;bottom:0;width:3px;
    background:#C9A227;transform:scaleY(0);transform-origin:bottom;transition:transform .3s;
}
.bk-service-card:hover::before{transform:scaleY(1);}

/* service image */
.bk-svc-img{
    width:110px;flex-shrink:0;position:relative;overflow:hidden;
    background:linear-gradient(135deg,#1a1a1a,#111);
    display:flex;align-items:center;justify-content:center;
}
.bk-svc-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.bk-service-card:hover .bk-svc-img img{transform:scale(1.08);}
.bk-svc-img-placeholder{font-size:2rem;color:rgba(201,162,39,.25);}

/* service body */
.bk-svc-body{flex:1;padding:16px 18px;display:flex;flex-direction:column;justify-content:center;}
.bk-svc-name{font-size:.95rem;font-weight:700;color:#fff;margin-bottom:4px;font-family:'Poppins',sans-serif;}
.bk-svc-desc{font-size:.8rem;color:rgba(255,255,255,.4);line-height:1.55;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.bk-svc-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.bk-svc-dur{font-size:.76rem;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:4px;}
.bk-svc-dur i{color:rgba(201,162,39,.5);}
.bk-svc-price{font-size:1rem;font-weight:800;color:#C9A227;font-family:'Poppins',sans-serif;}
.bk-svc-price-from{font-size:.68rem;color:rgba(201,162,39,.6);}

/* service action */
.bk-svc-action{
    display:flex;align-items:center;padding:0 16px;
    border-{{ $isAr ? 'right' : 'left' }}:1px solid rgba(201,162,39,.08);flex-shrink:0;
}
.bk-svc-book{
    background:transparent;border:1.5px solid rgba(201,162,39,.4);
    color:#C9A227;border-radius:10px;padding:9px 18px;
    font-size:.82rem;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;
    transition:all .22s;white-space:nowrap;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.bk-svc-book:hover{background:#C9A227;color:#0a0a0a;border-color:#C9A227;box-shadow:0 4px 18px rgba(201,162,39,.3);}

/* ════════════════════════
   GALLERY — Porto thumb-info
════════════════════════ */
.bk-gallery-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
}
@media(max-width:767px){.bk-gallery-grid{grid-template-columns:repeat(2,1fr);}}
.bk-gal-item{
    position:relative;border-radius:12px;overflow:hidden;
    aspect-ratio:1/.85;background:#1a1a1a;cursor:pointer;
}
.bk-gal-item img{width:100%;height:100%;object-fit:cover;transition:transform .45s cubic-bezier(.25,.8,.25,1),filter .45s;}
.bk-gal-item:hover img{transform:scale(1.08);filter:brightness(.7);}
.bk-gal-overlay{
    position:absolute;inset:0;
    display:flex;align-items:center;justify-content:center;
    background:rgba(10,10,10,.3);
    opacity:0;transition:opacity .3s;
}
.bk-gal-item:hover .bk-gal-overlay{opacity:1;}
.bk-gal-icon{
    width:48px;height:48px;border-radius:50%;
    background:rgba(201,162,39,.85);color:#0a0a0a;
    display:flex;align-items:center;justify-content:center;font-size:1.1rem;
    transform:scale(.6);transition:transform .3s;
}
.bk-gal-item:hover .bk-gal-icon{transform:scale(1);}
/* first item larger */
.bk-gal-item:first-child{grid-column:span 2;grid-row:span 2;aspect-ratio:auto;}
.bk-gal-item:first-child{aspect-ratio:auto;height:100%;}

/* ════════════════════════
   TEAM — Porto owl carousel
════════════════════════ */
.bk-emp-card{
    background:#111;border:1px solid rgba(201,162,39,.1);border-radius:16px;
    overflow:hidden;margin:8px;transition:all .3s;
}
.bk-emp-card:hover{border-color:rgba(201,162,39,.4);transform:translateY(-6px);box-shadow:0 16px 40px rgba(0,0,0,.45);}
.bk-emp-photo{
    height:180px;overflow:hidden;background:#1a1a1a;
    display:flex;align-items:center;justify-content:center;position:relative;
}
.bk-emp-photo img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.bk-emp-card:hover .bk-emp-photo img{transform:scale(1.07);}
.bk-emp-photo-placeholder{font-size:3rem;color:rgba(201,162,39,.2);}
.bk-emp-body{padding:16px;}
.bk-emp-name{font-size:.95rem;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;margin-bottom:3px;}
.bk-emp-role{font-size:.75rem;color:#C9A227;font-weight:600;}
.bk-emp-book{
    display:block;margin-top:12px;text-align:center;
    background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.2);
    border-radius:8px;padding:8px;color:#C9A227;font-size:.8rem;
    font-weight:700;text-decoration:none;font-family:'Poppins',sans-serif;
    transition:all .22s;
}
.bk-emp-book:hover{background:#C9A227;color:#0a0a0a;border-color:#C9A227;}

/* ════════════════════════
   REVIEWS
════════════════════════ */
.bk-rating-overview{
    background:#111;border:1px solid rgba(201,162,39,.12);border-radius:18px;
    padding:28px;display:flex;align-items:center;gap:32px;flex-wrap:wrap;
    margin-bottom:28px;
}
.bk-rating-big{text-align:center;min-width:90px;}
.bk-rating-score{font-size:3.5rem;font-weight:900;color:#C9A227;font-family:'Poppins',sans-serif;line-height:1;}
.bk-rating-stars-big{color:#C9A227;font-size:1.1rem;letter-spacing:2px;margin:4px 0;}
.bk-rating-total{font-size:.78rem;color:rgba(255,255,255,.4);}
.bk-rating-bars{flex:1;min-width:160px;}
.bk-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:7px;}
.bk-bar-lbl{font-size:.75rem;color:rgba(255,255,255,.5);width:14px;text-align:{{ $isAr ? 'left' : 'right' }};}
.bk-bar-track{flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden;}
.bk-bar-fill{height:100%;background:#C9A227;border-radius:3px;transition:width 1s ease;}
.bk-bar-cnt{font-size:.72rem;color:rgba(255,255,255,.3);width:20px;}

/* review card */
.bk-review-card{
    background:#111;border:1px solid rgba(201,162,39,.08);border-radius:14px;
    padding:20px;margin-bottom:14px;transition:border-color .25s;
}
.bk-review-card:hover{border-color:rgba(201,162,39,.3);}
.bk-rev-header{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
.bk-rev-avatar{
    width:42px;height:42px;border-radius:50%;
    background:rgba(201,162,39,.15);border:2px solid rgba(201,162,39,.25);
    display:flex;align-items:center;justify-content:center;color:#C9A227;font-size:1rem;flex-shrink:0;
}
.bk-rev-name{font-size:.88rem;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;}
.bk-rev-date{font-size:.72rem;color:rgba(255,255,255,.35);}
.bk-rev-stars{color:#C9A227;font-size:.76rem;letter-spacing:1px;margin-top:2px;}
.bk-rev-svc{font-size:.72rem;color:rgba(201,162,39,.7);font-style:italic;}
.bk-rev-text{font-size:.87rem;color:rgba(255,255,255,.6);line-height:1.7;margin:0;}
.bk-rev-badge{background:rgba(201,162,39,.1);color:#C9A227;border:1px solid rgba(201,162,39,.2);border-radius:20px;padding:2px 10px;font-size:.7rem;font-weight:700;}

/* ════════════════════════
   WORKING HOURS
════════════════════════ */
.bk-hours-table{width:100%;border-collapse:collapse;}
.bk-hours-table tr{border-bottom:1px solid rgba(255,255,255,.05);}
.bk-hours-table td{padding:9px 4px;font-size:.85rem;color:rgba(255,255,255,.6);}
.bk-hours-table td:first-child{font-weight:600;color:#fff;}
.bk-hours-table tr.today td{color:#C9A227!important;}
.bk-hours-table tr.today td:first-child::before{content:'• ';color:#C9A227;}
.bk-hours-closed{color:rgba(255,255,255,.25)!important;}

/* ════════════════════════
   SIDEBAR sticky card
════════════════════════ */
.bk-book-sidebar{
    background:#111;border:1px solid rgba(201,162,39,.15);border-radius:18px;
    padding:24px;position:sticky;top:130px;
}
.bk-book-sidebar h5{font-size:1rem;font-weight:700;color:#fff;margin-bottom:16px;font-family:'Poppins',sans-serif;}
.bk-info-row{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;}
.bk-info-row i{color:#C9A227;font-size:.95rem;margin-top:2px;flex-shrink:0;width:18px;text-align:center;}
.bk-info-row span{font-size:.84rem;color:rgba(255,255,255,.55);line-height:1.5;}
.bk-info-row a{color:rgba(255,255,255,.55);text-decoration:none;transition:color .2s;}
.bk-info-row a:hover{color:#C9A227;}
.bk-btn-book-big{
    width:100%;background:#C9A227;color:#0a0a0a;border:none;border-radius:12px;
    padding:14px;font-size:.95rem;font-weight:700;font-family:'Poppins',sans-serif;
    cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;
    text-decoration:none;
}
.bk-btn-book-big:hover{background:#e8c84a;box-shadow:0 6px 24px rgba(201,162,39,.35);transform:translateY(-2px);color:#0a0a0a;}

/* ════════════════════════
   SECTION HELPERS
════════════════════════ */
.bk-section{padding:60px 0;}
.bk-panel{background:#111;border:1px solid rgba(201,162,39,.1);border-radius:16px;padding:28px;}
.divider-gold{width:50px;height:3px;background:linear-gradient(90deg,#C9A227,rgba(201,162,39,.2));border-radius:2px;margin:10px 0 22px;}
.section-label{font-size:.72rem;font-weight:700;color:#C9A227;text-transform:uppercase;letter-spacing:3px;font-family:'Poppins',sans-serif;display:block;margin-bottom:6px;}
.section-heading{font-family:'Playfair Display',serif;font-size:1.8rem;color:#fff;font-weight:700;margin-bottom:0;}
.section-heading span{color:#C9A227;}

/* footer */
footer.booksy-footer{background:#050505;border-top:1px solid rgba(201,162,39,.1);padding:50px 0 0;}
footer.booksy-footer .footer-brand{font-size:1.6rem;font-weight:900;color:#fff;font-family:'Poppins',sans-serif;}
footer.booksy-footer .footer-brand span{color:#C9A227;}
footer.booksy-footer p{color:rgba(255,255,255,.4);font-size:.85rem;}
footer.booksy-footer h6{color:#C9A227;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:14px;font-family:'Poppins',sans-serif;}
footer.booksy-footer ul{list-style:none;padding:0;margin:0;}
footer.booksy-footer ul li a{color:rgba(255,255,255,.4);text-decoration:none;font-size:.84rem;display:block;padding:4px 0;transition:color .2s;}
footer.booksy-footer ul li a:hover{color:#C9A227;}
footer.booksy-footer .copy{font-size:.76rem;color:rgba(255,255,255,.2);text-align:center;}

/* Magnific popup dark override */
.mfp-bg{background:#000;opacity:.94!important;}
.mfp-arrow:before{border-right-color:#C9A227!important;}
.mfp-arrow-right:before{border-left-color:#C9A227!important;}
.mfp-close{color:#C9A227!important;}

/* owl carousel dark */
.owl-theme .owl-nav [class*=owl-]{background:rgba(201,162,39,.15)!important;color:#C9A227!important;border:1px solid rgba(201,162,39,.3)!important;border-radius:50%!important;width:36px;height:36px;line-height:36px;transition:all .2s;}
.owl-theme .owl-nav [class*=owl-]:hover{background:#C9A227!important;color:#0a0a0a!important;}
.owl-theme .owl-dots .owl-dot.active span{background:#C9A227!important;}
.owl-theme .owl-dots .owl-dot span{background:rgba(201,162,39,.25)!important;}
</style>
</head>
<body>
<div class="body">

{{-- ════ NAVBAR ════ --}}
<nav id="bk-navbar" class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a href="{{ route('front.index') }}" class="navbar-brand">Booksy<span>.</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bkNav">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="bkNav">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('front.index') }}">{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.index') }}#bk-companies">{{ $isAr ? 'الأماكن' : 'Places' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل' : 'Contact' }}</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                @if($isAr)
                    <a href="{{ route('locale.switch','en') }}" class="bk-lang">EN</a>
                @else
                    <a href="{{ route('locale.switch','ar') }}" class="bk-lang">عربي</a>
                @endif
                <a href="{{ route('company.register') }}" class="bk-register-btn">
                    <i class="fas fa-store"></i> {{ $isAr ? 'سجّل نشاطك' : 'List Business' }}
                </a>
            </div>
        </div>
    </div>
</nav>

<div role="main" class="main">

{{-- ════ COVER ════ --}}
<div id="bk-cover">
    <div class="bk-cover-slides">
        @forelse($allImages as $idx => $img)
        <img src="{{ asset('storage/'.$img->path) }}" alt="" class="{{ $idx === 0 ? 'active' : '' }}" data-slide="{{ $idx }}">
        @empty
        <div style="width:100%;height:100%;background:linear-gradient(135deg,#111,#0a0a0a);position:absolute;inset:0;"></div>
        @endforelse
    </div>
    @if($allImages->count() > 1)
    <div class="cover-nav">
        @foreach($allImages as $idx => $img)
        <button class="cover-dot {{ $idx===0?'active':'' }}" data-target="{{ $idx }}"></button>
        @endforeach
    </div>
    @endif
</div>

{{-- ════ PROFILE BAR ════ --}}
<div id="bk-profile-bar">
    <div class="container py-3">
        <div class="d-flex align-items-end gap-4 flex-wrap">
            {{-- Logo --}}
            <div class="bk-logo-wrap">
                @if($company->logo)
                    <img src="{{ asset('storage/'.$company->logo) }}" alt="{{ $name }}">
                @else
                    <div class="bk-logo-fallback"><i class="fas fa-store"></i></div>
                @endif
            </div>
            {{-- Info --}}
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h1 class="bk-biz-name mb-0">{{ $name }}</h1>
                    @if($catName)<span class="bk-biz-cat">{{ $catName }}</span>@endif
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    @if($totalRev > 0)
                    <div class="bk-rating-wrap">
                        <span class="bk-rating-num">{{ number_format($avgRating, 1) }}</span>
                        <span class="bk-stars">
                            @for($i=1;$i<=5;$i++)
                                @if($i <= floor($stars))<i class="fas fa-star"></i>
                                @elseif($i - 0.5 == $stars)<i class="fas fa-star-half-alt"></i>
                                @else<i class="far fa-star" style="opacity:.3"></i>
                                @endif
                            @endfor
                        </span>
                        <span class="bk-rating-count">({{ $totalRev }} {{ $isAr ? 'تقييم' : 'reviews' }})</span>
                    </div>
                    @endif
                    @if($branch?->address)
                    <span style="font-size:.8rem;color:rgba(255,255,255,.4);"><i class="fas fa-map-marker-alt" style="color:#C9A227;margin-{{ $isAr ? 'left' : 'right' }}:5px;"></i>{{ $branch->address }}</span>
                    @endif
                </div>
            </div>
            {{-- Book CTA --}}
            <a href="#bk-services-tab" class="bk-book-hero d-none d-md-inline-flex">
                <i class="far fa-calendar-check"></i>
                {{ $isAr ? 'احجز الآن' : 'Book Now' }}
            </a>
        </div>
    </div>
</div>

{{-- ════ STICKY TABS ════ --}}
<div id="bk-tabs-bar">
    <div class="container">
        <div class="d-flex overflow-auto" style="gap:0;">
            <a class="bk-tab-link active" href="#bk-services-tab" data-tab="services">
                <i class="fas fa-cut"></i> {{ $isAr ? 'الخدمات' : 'Services' }}
            </a>
            @if($allImages->isNotEmpty())
            <a class="bk-tab-link" href="#bk-gallery-tab" data-tab="gallery">
                <i class="fas fa-images"></i> {{ $isAr ? 'الصور' : 'Gallery' }}
                <span style="background:rgba(201,162,39,.15);color:#C9A227;border-radius:10px;padding:1px 7px;font-size:.7rem;">{{ $allImages->count() }}</span>
            </a>
            @endif
            @if($employees->isNotEmpty())
            <a class="bk-tab-link" href="#bk-team-tab" data-tab="team">
                <i class="fas fa-users"></i> {{ $isAr ? 'الفريق' : 'Team' }}
            </a>
            @endif
            @if($totalRev > 0)
            <a class="bk-tab-link" href="#bk-reviews-tab" data-tab="reviews">
                <i class="fas fa-star"></i> {{ $isAr ? 'التقييمات' : 'Reviews' }}
            </a>
            @endif
            <a class="bk-tab-link" href="#bk-info-tab" data-tab="info">
                <i class="fas fa-info-circle"></i> {{ $isAr ? 'المعلومات' : 'Info' }}
            </a>
        </div>
    </div>
</div>

{{-- ════ MAIN CONTENT ════ --}}
<div class="container" style="padding-top:40px;padding-bottom:60px;">
    <div class="row g-4">

        {{-- ── LEFT COLUMN ── --}}
        <div class="col-lg-8">

            {{-- ════ SERVICES ════ --}}
            <section id="bk-services-tab" class="bk-section" style="padding-top:0;">
                <p class="section-label">{{ $isAr ? 'احجز الآن' : 'Book Now' }}</p>
                <h2 class="section-heading">{{ $isAr ? 'الخدمات' : 'Our' }} <span>{{ $isAr ? 'المتاحة' : 'Services' }}</span></h2>
                <div class="divider-gold"></div>

                @if($serviceCategories->isNotEmpty())
                    @php
                        $catModels = $branch->services
                            ->where('is_active', true)
                            ->groupBy('service_category_id');
                    @endphp
                    @foreach($catModels as $catId => $services)
                    @php
                        $firstSvc = $services->first();
                        $catLabel = $firstSvc->serviceCategory
                            ? ($isAr ? $firstSvc->serviceCategory->name_ar : $firstSvc->serviceCategory->name_en)
                            : ($isAr ? 'خدمات عامة' : 'General Services');
                    @endphp
                    <div class="mb-4 bk-cat-group" data-cat="{{ $catId }}">
                        {{-- category header --}}
                        <div class="bk-cat-header" onclick="toggleCat(this)">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-chevron-down bk-cat-toggle" style="color:#C9A227;font-size:.8rem;transition:transform .3s;"></i>
                                <h4>{{ $catLabel }}</h4>
                                <span class="bk-cat-count">{{ $services->count() }}</span>
                            </div>
                        </div>

                        {{-- services list --}}
                        <div class="bk-cat-body">
                            @foreach($services as $svc)
                            <div class="bk-service-card appear-animation" data-appear-animation="fadeInLeft" data-appear-animation-delay="{{ $loop->index * 60 }}" data-plugin-options="{'minWindowWidth':0}">

                                {{-- Service Image --}}
                                <div class="bk-svc-img">
                                    @php
                                        $svcImg = $allImages->first();
                                    @endphp
                                    @if($svcImg)
                                        <img src="{{ asset('storage/'.$svcImg->path) }}" alt="{{ $isAr ? $svc->name_ar : $svc->name_en }}" loading="lazy">
                                    @else
                                        <div class="bk-svc-img-placeholder">
                                            <i class="fas fa-spa"></i>
                                        </div>
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="bk-svc-body">
                                    <div class="bk-svc-name">{{ $isAr ? $svc->name_ar : $svc->name_en }}</div>
                                    @if($svc->description)
                                    <div class="bk-svc-desc">{{ $svc->description }}</div>
                                    @endif
                                    <div class="bk-svc-meta">
                                        @if($svc->duration_minutes)
                                        <span class="bk-svc-dur">
                                            <i class="far fa-clock"></i>
                                            {{ $svc->duration_minutes >= 60
                                                ? floor($svc->duration_minutes/60).'h '.($svc->duration_minutes%60 ? ($svc->duration_minutes%60).'m' : '')
                                                : $svc->duration_minutes.'min'
                                            }}
                                        </span>
                                        @endif
                                        <span class="bk-svc-price">
                                            <span class="bk-svc-price-from">{{ $isAr ? 'من ' : 'from ' }}</span>
                                            {{ number_format($svc->price, 0) }}
                                            <span style="font-size:.7rem;font-weight:500;"> {{ $isAr ? 'ر.س' : 'SAR' }}</span>
                                        </span>
                                    </div>
                                </div>

                                {{-- Book Button --}}
                                <div class="bk-svc-action">
                                    <a href="{{ route('company.appointments.create') }}"
                                       class="bk-svc-book">
                                        <i class="far fa-calendar-plus"></i>
                                        {{ $isAr ? 'احجز' : 'Book' }}
                                    </a>
                                </div>

                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @else
                <div style="text-align:center;padding:50px 20px;color:rgba(255,255,255,.3);">
                    <i class="fas fa-cut" style="font-size:3rem;display:block;margin-bottom:12px;color:rgba(201,162,39,.2);"></i>
                    <p>{{ $isAr ? 'لا توجد خدمات مضافة بعد.' : 'No services added yet.' }}</p>
                </div>
                @endif
            </section>

            {{-- ════ GALLERY ════ --}}
            @if($allImages->isNotEmpty())
            <section id="bk-gallery-tab" class="bk-section">
                <p class="section-label">{{ $isAr ? 'معرض الصور' : 'Photo Gallery' }}</p>
                <h2 class="section-heading">{{ $isAr ? 'صور' : 'Our' }} <span>{{ $isAr ? 'المكان' : 'Gallery' }}</span></h2>
                <div class="divider-gold"></div>

                <div class="bk-gallery-grid" id="bk-gallery">
                    @foreach($allImages as $idx => $img)
                    <a href="{{ asset('storage/'.$img->path) }}"
                       class="bk-gal-item appear-animation"
                       data-appear-animation="zoomIn"
                       data-appear-animation-delay="{{ $idx * 80 }}"
                       data-plugin-options="{'minWindowWidth':0}">
                        <img src="{{ asset('storage/'.$img->path) }}" alt="{{ $name }}" loading="lazy">
                        <div class="bk-gal-overlay">
                            <div class="bk-gal-icon"><i class="fas fa-expand"></i></div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- ════ TEAM ════ --}}
            @if($employees->isNotEmpty())
            <section id="bk-team-tab" class="bk-section">
                <p class="section-label">{{ $isAr ? 'الفريق' : 'Meet Our Team' }}</p>
                <h2 class="section-heading">{{ $isAr ? 'فريقنا' : 'Our' }} <span>{{ $isAr ? 'المحترف' : 'Professionals' }}</span></h2>
                <div class="divider-gold"></div>

                <div class="owl-carousel owl-theme" id="bk-team-carousel">
                    @foreach($employees as $emp)
                    <div class="bk-emp-card">
                        <div class="bk-emp-photo">
                            @if($emp->image)
                                <img src="{{ asset('storage/'.$emp->image) }}" alt="{{ $isAr ? $emp->name_ar : $emp->name_en }}">
                            @else
                                <div class="bk-emp-photo-placeholder"><i class="fas fa-user-circle"></i></div>
                            @endif
                        </div>
                        <div class="bk-emp-body">
                            <div class="bk-emp-name">{{ $isAr ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}</div>
                            @if($emp->role)
                            <div class="bk-emp-role">{{ $isAr ? ($emp->role->label_ar ?: $emp->role->label_en) : ($emp->role->label_en ?: $emp->role->label_ar) }}</div>
                            @endif
                            {{-- Service Categories chips --}}
                            @if($emp->serviceCategories->isNotEmpty())
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @foreach($emp->serviceCategories as $sc)
                                <span style="background:rgba(201,162,39,.1);color:#C9A227;border:1px solid rgba(201,162,39,.2);border-radius:20px;padding:2px 8px;font-size:.68rem;font-weight:600;">
                                    {{ $isAr ? ($sc->name_ar ?: $sc->name_en) : ($sc->name_en ?: $sc->name_ar) }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                            @if($emp->bio)
                            <p style="font-size:.78rem;color:rgba(255,255,255,.4);margin:8px 0 0;line-height:1.5;">{{ Str::limit($emp->bio, 80) }}</p>
                            @endif
                            <a href="{{ route('company.appointments.create') }}" class="bk-emp-book">
                                <i class="far fa-calendar-plus {{ $isAr ? 'ms-1' : 'me-1' }}"></i>{{ $isAr ? 'احجز معه' : 'Book Now' }}
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- ════ REVIEWS ════ --}}
            <section id="bk-reviews-tab" class="bk-section">
                <p class="section-label">{{ $isAr ? 'آراء العملاء' : 'Client Reviews' }}</p>
                <h2 class="section-heading">{{ $isAr ? 'التقييمات' : 'Reviews' }} <span>({{ $totalRev }})</span></h2>
                <div class="divider-gold"></div>

                @if($totalRev > 0)
                {{-- Rating overview --}}
                @php
                    $ratingDist = [5=>0,4=>0,3=>0,2=>0,1=>0];
                    foreach($reviews as $rv){ if(isset($ratingDist[$rv->rating])) $ratingDist[$rv->rating]++; }
                @endphp
                <div class="bk-rating-overview appear-animation" data-appear-animation="fadeInUp" data-plugin-options="{'minWindowWidth':0}">
                    <div class="bk-rating-big">
                        <div class="bk-rating-score">{{ number_format($avgRating,1) }}</div>
                        <div class="bk-rating-stars-big">
                            @for($i=1;$i<=5;$i++)<i class="{{ $i <= round($avgRating) ? 'fas' : 'far' }} fa-star"></i>@endfor
                        </div>
                        <div class="bk-rating-total">{{ $totalRev }} {{ $isAr ? 'تقييم' : 'reviews' }}</div>
                    </div>
                    <div class="bk-rating-bars">
                        @foreach([5,4,3,2,1] as $star)
                        @php $pct = $totalRev > 0 ? ($ratingDist[$star]/$totalRev)*100 : 0; @endphp
                        <div class="bk-bar-row">
                            <span class="bk-bar-lbl">{{ $star }}</span>
                            <i class="fas fa-star" style="color:#C9A227;font-size:.65rem;flex-shrink:0;"></i>
                            <div class="bk-bar-track"><div class="bk-bar-fill" style="width:{{ $pct }}%"></div></div>
                            <span class="bk-bar-cnt">{{ $ratingDist[$star] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Review cards --}}
                @foreach($reviews->take(10) as $rev)
                <div class="bk-review-card appear-animation" data-appear-animation="fadeInUp" data-appear-animation-delay="{{ $loop->index * 60 }}" data-plugin-options="{'minWindowWidth':0}">
                    <div class="bk-rev-header">
                        <div class="bk-rev-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="bk-rev-name">
                                {{ $rev->customer ? $rev->customer->name : ($isAr ? 'عميل' : 'Client') }}
                            </div>
                            <div class="bk-rev-stars">
                                @for($i=1;$i<=5;$i++)<i class="{{ $i <= $rev->rating ? 'fas' : 'far' }} fa-star" style="{{ $i > $rev->rating ? 'opacity:.2' : '' }}"></i>@endfor
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="bk-rev-date">{{ $rev->created_at->diffForHumans() }}</div>
                            <span class="bk-rev-badge">{{ $rev->rating }}/5</span>
                        </div>
                    </div>
                    @if($rev->comment)
                    <p class="bk-rev-text">"{{ $rev->comment }}"</p>
                    @endif
                </div>
                @endforeach

                @else
                <div style="text-align:center;padding:40px;color:rgba(255,255,255,.3);">
                    <i class="fas fa-star" style="font-size:2.5rem;color:rgba(201,162,39,.2);display:block;margin-bottom:10px;"></i>
                    <p>{{ $isAr ? 'لا توجد تقييمات بعد.' : 'No reviews yet.' }}</p>
                </div>
                @endif
            </section>

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="col-lg-4" id="bk-info-tab">
            <div class="bk-book-sidebar">
                {{-- Book button --}}
                <a href="{{ route('company.appointments.create') }}" class="bk-btn-book-big mb-4">
                    <i class="far fa-calendar-check"></i>
                    {{ $isAr ? 'احجز موعداً الآن' : 'Book an Appointment' }}
                </a>

                {{-- Info --}}
                <h5>{{ $isAr ? 'معلومات المكان' : 'Business Info' }}</h5>

                @if($branch?->phone || $company->phone)
                <div class="bk-info-row">
                    <i class="fas fa-phone-alt"></i>
                    <a href="tel:{{ $branch?->phone ?? $company->phone }}" class="bk-info-row" style="display:contents;">
                        <span>{{ $branch?->phone ?? $company->phone }}</span>
                    </a>
                </div>
                @endif

                @if($company->email)
                <div class="bk-info-row">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:{{ $company->email }}"><span>{{ $company->email }}</span></a>
                </div>
                @endif

                @if($branch?->address)
                <div class="bk-info-row">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ $branch->address }}</span>
                </div>
                @endif

                {{-- Social Links --}}
                @if($company->socialLinks->isNotEmpty())
                <div class="bk-info-row" style="flex-wrap:wrap;gap:8px;">
                    <i class="fas fa-share-alt" style="margin-top:0;"></i>
                    @foreach($company->socialLinks as $sl)
                    <a href="{{ $sl->url }}" target="_blank"
                       style="width:34px;height:34px;border-radius:8px;background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.2);
                              display:inline-flex;align-items:center;justify-content:center;color:#C9A227;font-size:.85rem;text-decoration:none;transition:all .2s;"
                       onmouseover="this.style.background='#C9A227';this.style.color='#0a0a0a'"
                       onmouseout="this.style.background='rgba(201,162,39,.08)';this.style.color='#C9A227'">
                        <i class="fab fa-{{ $sl->platform ?? 'link' }}"></i>
                    </a>
                    @endforeach
                </div>
                @endif

                {{-- Working Hours --}}
                @if($branch && $branch->workingHours->isNotEmpty())
                <div style="margin-top:20px;padding-top:18px;border-top:1px solid rgba(255,255,255,.06);">
                    <h6 style="color:#C9A227;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:14px;font-family:'Poppins',sans-serif;">
                        <i class="far fa-clock {{ $isAr ? 'ms-2' : 'me-2' }}"></i>{{ $isAr ? 'أوقات العمل' : 'Working Hours' }}
                    </h6>
                    @php
                        $days = $isAr
                            ? ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']
                            : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                        $todayNum = now()->dayOfWeek;
                    @endphp
                    <table class="bk-hours-table">
                        @foreach($branch->workingHours->sortBy('day_of_week') as $wh)
                        <tr class="{{ $wh->day_of_week == $todayNum ? 'today' : '' }}">
                            <td>{{ $days[$wh->day_of_week] ?? '' }}</td>
                            <td class="{{ $isAr ? 'text-start' : 'text-end' }}">
                                @if($wh->is_closed ?? false)
                                    <span class="bk-hours-closed">{{ $isAr ? 'مغلق' : 'Closed' }}</span>
                                @else
                                    {{ $wh->open_time ? \Carbon\Carbon::parse($wh->open_time)->format('h:i A') : '' }}
                                    –
                                    {{ $wh->close_time ? \Carbon\Carbon::parse($wh->close_time)->format('h:i A') : '' }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
                @endif

                {{-- Map --}}
                @if($branch?->latitude && $branch?->longitude)
                <div style="margin-top:20px;border-radius:12px;overflow:hidden;height:180px;">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d{{ $branch->longitude }}!3d{{ $branch->latitude }}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2ssa!4v1"
                        width="100%" height="100%" style="border:0;filter:grayscale(1) brightness(.4);"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                @endif

            </div>
        </div>

    </div>{{-- row --}}
</div>{{-- container --}}

</div>{{-- .main --}}

{{-- FOOTER --}}
<footer class="booksy-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <span class="footer-brand">Booksy<span>.</span></span>
                <div class="divider-gold mt-2 mb-2"></div>
                <p>{{ $isAr ? 'منصة حجز مواعيد صالونات التجميل والعيادات.' : 'Beauty salon & clinic booking platform.' }}</p>
            </div>
            <div class="col-6 col-lg-3">
                <h6>{{ $isAr ? 'روابط' : 'Links' }}</h6>
                <ul>
                    <li><a href="{{ route('front.index') }}">{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                    <li><a href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                    <li><a href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل' : 'Contact' }}</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-4">
                <h6>{{ $isAr ? 'اللغة' : 'Language' }}</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('locale.switch','ar') }}" class="bk-lang" style="{{ $isAr ? 'background:#C9A227;color:#0a0a0a;' : '' }}">عربي</a>
                    <a href="{{ route('locale.switch','en') }}" class="bk-lang" style="{{ !$isAr ? 'background:#C9A227;color:#0a0a0a;' : '' }}">EN</a>
                </div>
            </div>
        </div>
    </div>
    <div style="margin-top:36px;border-top:1px solid rgba(255,255,255,.06);padding:16px 0;">
        <div class="container"><p class="copy">&copy; {{ date('Y') }} Booksy</p></div>
    </div>
</footer>

{{-- ════ SCRIPTS ════ --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/owl.carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>

<script>
(function($){
    'use strict';
    var isRtl = {{ $isAr ? 'true' : 'false' }};

    $(document).ready(function(){

        /* ── Navbar scroll ── */
        $(window).on('scroll', function(){
            $('#bk-navbar').toggleClass('scrolled', $(this).scrollTop() > 30);
        });

        /* ── Cover slideshow ── */
        var slides = $('.bk-cover-slides img');
        var dots   = $('.cover-dot');
        var current = 0;
        if(slides.length > 1){
            setInterval(function(){
                slides.eq(current).removeClass('active');
                dots.eq(current).removeClass('active');
                current = (current + 1) % slides.length;
                slides.eq(current).addClass('active');
                dots.eq(current).addClass('active');
            }, 4000);
            dots.on('click', function(){
                var idx = $(this).data('target');
                slides.removeClass('active');
                dots.removeClass('active');
                slides.eq(idx).addClass('active');
                $(this).addClass('active');
                current = idx;
            });
        }

        /* ── Gallery Magnific Popup ── */
        $('#bk-gallery').magnificPopup({
            delegate: 'a.bk-gal-item',
            type: 'image',
            gallery: { enabled: true },
            image: {
                titleSrc: function(item){ return ''; }
            },
            closeBtnInside: false,
            mainClass: 'mfp-with-zoom',
            zoom: { enabled: true, duration: 300 }
        });

        /* ── Team Owl Carousel ── */
        $('#bk-team-carousel').owlCarousel({
            responsive:{ 0:{items:1}, 576:{items:2}, 992:{items:3} },
            autoplay: true, autoplayTimeout: 4000,
            loop: true, dots: true, nav: true, rtl: isRtl, margin: 12,
            navText: [
                '<i class="fas fa-chevron-'+(isRtl?'right':'left')+'"></i>',
                '<i class="fas fa-chevron-'+(isRtl?'left':'right')+'"></i>'
            ]
        });

        /* ── Sticky Tab Highlight ── */
        var sections = ['bk-services-tab','bk-gallery-tab','bk-team-tab','bk-reviews-tab','bk-info-tab'];
        var tabLinks = $('.bk-tab-link');

        $(window).on('scroll', function(){
            var scrollPos = $(this).scrollTop() + 160;
            sections.forEach(function(id){
                var el = $('#'+id);
                if(el.length && el.offset().top <= scrollPos && el.offset().top + el.outerHeight() > scrollPos){
                    tabLinks.removeClass('active');
                    tabLinks.filter('[data-tab="'+id.replace('bk-','').replace('-tab','')+'"]').addClass('active');
                }
            });
        });

        /* ── Smooth scroll for tabs ── */
        tabLinks.on('click', function(e){
            var href = $(this).attr('href');
            if(href && href.startsWith('#')){
                e.preventDefault();
                var target = $(href);
                if(target.length){
                    $('html,body').animate({ scrollTop: target.offset().top - 140 }, 600, 'easeInOutExpo');
                }
            }
        });

        /* ── Animate rating bars ── */
        $('.bk-bar-fill').each(function(){
            var $el = $(this);
            var w = $el.css('width');
            $el.css('width','0');
            $el.appear(function(){
                $el.css({transition:'width 1.2s ease', width: w});
            });
        });

    });
})(jQuery);

/* ── Category toggle ── */
function toggleCat(header){
    var body = $(header).next('.bk-cat-body');
    var icon = $(header).find('.bk-cat-toggle');
    if(body.is(':visible')){
        body.slideUp(300);
        icon.css('transform','rotate(-90deg)');
    } else {
        body.slideDown(300);
        icon.css('transform','rotate(0deg)');
    }
}
</script>

</div>{{-- .body --}}
</body>
</html>
