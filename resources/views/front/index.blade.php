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

/* ── Navbar ── */
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

/* ── Category Strip ── */
.bk-cats-strip{
    background:#0d0d0d;
    border-top:1px solid rgba(255,255,255,.05);
    border-bottom:1px solid rgba(255,255,255,.05);
    padding:14px 0 10px;
}
.bk-cats-scroll{
    display:flex;align-items:flex-start;gap:4px;overflow-x:auto;
    padding:0 12px 4px;scroll-snap-type:x mandatory;
    -webkit-overflow-scrolling:touch;scrollbar-width:none;
}
.bk-cats-scroll::-webkit-scrollbar{display:none;}
.bk-cat2{
    display:flex;flex-direction:column;align-items:center;
    text-decoration:none!important;flex-shrink:0;scroll-snap-align:start;
    width:66px;padding:4px 2px;border-radius:10px;transition:background .2s;
    cursor:pointer;border:2px solid transparent;
}
.bk-cat2:hover{background:rgba(255,255,255,.04);}
.bk-cat2.active{border-color:rgba(201,162,39,.5);}
.bk-cat2-circle{
    width:50px;height:50px;border-radius:50%;overflow:hidden;
    background:#1e2a28;border:1.5px solid rgba(255,255,255,.1);
    display:flex;align-items:center;justify-content:center;
    margin-bottom:6px;transition:border-color .2s, transform .2s;flex-shrink:0;
}
.bk-cat2:hover .bk-cat2-circle{border-color:rgba(201,162,39,.6);transform:scale(1.05);}
.bk-cat2.active .bk-cat2-circle{border-color:#C9A227;box-shadow:0 0 0 2px rgba(201,162,39,.2);}
.bk-cat2-circle img{width:100%;height:100%;object-fit:cover;}
.bk-cat2-circle i{font-size:1.1rem;color:rgba(255,255,255,.55);}
.bk-cat2.active .bk-cat2-circle i{color:#C9A227;}
.bk-cat2-label{
    font-size:.64rem;font-weight:500;color:rgba(255,255,255,.6);
    text-align:center;line-height:1.2;white-space:nowrap;
    overflow:hidden;text-overflow:ellipsis;max-width:64px;font-family:'Poppins',sans-serif;
}
.bk-cat2.active .bk-cat2-label{color:#C9A227;font-weight:700;}
@media(min-width:768px){
    .bk-cats-strip{padding:20px 0 16px;}
    .bk-cats-scroll{gap:8px;padding:0 24px 4px;}
    .bk-cat2{width:72px;}
    .bk-cat2-circle{width:54px;height:54px;}
    .bk-cat2-label{font-size:.68rem;max-width:70px;}
}
@media(min-width:1200px){.bk-cats-scroll{padding:0 48px 4px;}}
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
<section id="bk-cats" class="bk-cats-strip">
    @if($categories->isNotEmpty())
    <div class="bk-cats-scroll">
        <a href="{{ route('front.index', request()->except('category','page')) }}"
           class="bk-cat2 {{ !request('category') ? 'active' : '' }}">
            <div class="bk-cat2-circle"><i class="fas fa-th-large"></i></div>
            <span class="bk-cat2-label">{{ $isAr ? 'الكل' : 'All' }}</span>
        </a>
        @foreach($categories as $cat)
        @php
            $sl2 = strtolower($cat->slug ?? '');
            $ic2 = 'fas fa-store';
            foreach($catIcons as $k => $v){ if(str_contains($sl2,$k)){$ic2=$v;break;} }
        @endphp
        <a href="{{ route('front.index', array_merge(request()->except('category','page'), ['category' => $cat->slug])) }}"
           class="bk-cat2 {{ request('category') === $cat->slug ? 'active' : '' }}">
            <div class="bk-cat2-circle">
                @if($cat->image)
                    <img src="{{ asset('storage/'.$cat->image) }}" alt="{{ $isAr ? $cat->name_ar : $cat->name_en }}">
                @else
                    <i class="{{ $ic2 }}"></i>
                @endif
            </div>
            <span class="bk-cat2-label">{{ $isAr ? $cat->name_ar : $cat->name_en }}</span>
        </a>
        @endforeach
    </div>
    @else
    <div class="bk-empty"><i class="fas fa-th"></i><p>{{ $isAr ? 'لا توجد تصنيفات.' : 'No categories yet.' }}</p></div>
    @endif
</section>






{{-- ========== COMPANIES ========== --}}
<section id="bk-companies" class="section border-0 m-0" style="padding:70px 0;background:#0d0d0d;">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-7">
                <p class="section-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'الأماكن المميزة' : 'Featured Places' }}
                </p>
                <h2 class="section-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                    @if(request('search'))
                        {{ $isAr ? 'نتائج: ' : 'Results for: ' }}<span>{{ request('search') }}</span>
                    @elseif(request('category'))
                        {{ $isAr ? 'تصنيف: ' : 'Category: ' }}<span>{{ request('category') }}</span>
                    @else
                        {{ $isAr ? 'اكتشف ' : 'Discover ' }}<span>{{ $isAr ? 'أفضل الأماكن' : 'Top Places' }}</span>
                    @endif
                </h2>
                <div class="divider-gold"></div>
            </div>
            <div class="col-lg-5 text-lg-end appear-animation" data-appear-animation="fadeInRight" data-plugin-options="{'minWindowWidth':0}">
                <span style="background:rgba(201,162,39,.1);color:#C9A227;border:1px solid rgba(201,162,39,.3);border-radius:25px;padding:7px 18px;font-size:.86rem;font-weight:700;font-family:'Poppins',sans-serif;">
                    {{ $companies->total() }} {{ $isAr ? 'نتيجة' : 'results' }}
                </span>
                @if(request('search') || request('category'))
                <a href="{{ route('front.index') }}" style="display:inline-block;margin-{{ $isAr ? 'right' : 'left' }}:10px;font-size:.82rem;color:#C9A227;text-decoration:none;">
                    <i class="fas fa-times-circle {{ $isAr ? 'ms-1' : 'me-1' }}"></i>{{ $isAr ? 'مسح' : 'Clear' }}
                </a>
                @endif
            </div>
        </div>

        @if($companies->isNotEmpty())
        <div class="row g-4">
            @foreach($companies as $company)
            @php
                $firstBranch  = $company->branches->first();
                $branchImg    = $firstBranch?->images?->first();
                $allReviews   = $company->branches->flatMap(fn($b) => $b->reviews);
                $reviewCount  = $allReviews->count();
                $avgRating    = $reviewCount ? round($allReviews->avg('rating'), 1) : null;
            @endphp
            <div class="col-sm-6 col-lg-4 col-xl-3 appear-animation"
                 data-appear-animation="fadeInUpShorter"
                 data-appear-animation-delay="{{ ($loop->index % 4) * 100 }}"
                 data-plugin-options="{'minWindowWidth':0}">
                <div class="bk-card-dark" style="border-radius:14px;background:#161616;border:1px solid rgba(201,162,39,.12);">

                    <div class="bk-co-img" style="position:relative;">
                        @if($branchImg)
                            <img src="{{ asset('storage/'.$branchImg->path) }}" alt="{{ $isAr ? $company->name_ar : $company->name_en }}" loading="lazy">
                        @elseif($company->logo)
                            <img src="{{ asset('storage/'.$company->logo) }}" alt="" loading="lazy" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid rgba(201,162,39,.4);">
                        @else
                            <div class="bk-logo-fallback"><i class="fas fa-store"></i></div>
                        @endif

                        @if($company->category)
                        <span class="bk-co-badge">{{ $isAr ? $company->category->name_ar : $company->category->name_en }}</span>
                        @endif

                        @if($avgRating)
                        <span style="position:absolute;top:10px;{{ $isAr ? 'left' : 'right' }}:10px;
                              background:rgba(10,10,10,.88);color:#fff;font-size:.72rem;font-weight:700;
                              padding:4px 10px;border-radius:20px;border:1px solid rgba(201,162,39,.3);
                              backdrop-filter:blur(6px);display:flex;align-items:center;gap:4px;z-index:3;">
                            <i class="fas fa-star" style="color:#C9A227;font-size:.65rem;"></i>
                            {{ $avgRating }}
                            <span style="color:rgba(255,255,255,.45);font-weight:400;">· {{ $reviewCount }}</span>
                        </span>
                        @endif

                        <button class="bk-like-btn" data-id="{{ $company->id }}"
                                onclick="bkToggleLike(this, {{ $company->id }})"
                                style="position:absolute;bottom:10px;{{ $isAr ? 'left' : 'right' }}:10px;
                                       width:34px;height:34px;border-radius:50%;border:none;cursor:pointer;
                                       background:rgba(10,10,10,.8);display:flex;align-items:center;justify-content:center;
                                       z-index:3;transition:all .22s;backdrop-filter:blur(4px);">
                            <i class="far fa-heart" style="font-size:.85rem;color:rgba(255,255,255,.7);pointer-events:none;"></i>
                        </button>
                    </div>

                    <div class="bk-co-body">
                        <div class="bk-co-name mb-1">{{ $isAr ? $company->name_ar : $company->name_en }}</div>

                        <div class="bk-co-meta mb-2">
                            <i class="fas fa-map-marker-alt {{ $isAr ? 'ms-1' : 'me-1' }}"></i>
                            @if($firstBranch?->address)
                                {{ Str::limit($firstBranch->address, 30) }}
                            @else
                                {{ $company->branches->count() }} {{ $isAr ? 'فرع' : 'branches' }}
                            @endif
                        </div>

                        @if($avgRating)
                        <div class="d-flex align-items-center gap-1 mb-2">
                            @for($s = 1; $s <= 5; $s++)
                                <i class="{{ $s <= round($avgRating) ? 'fas' : 'far' }} fa-star" style="color:#C9A227;font-size:.6rem;"></i>
                            @endfor
                            <span style="font-size:.68rem;color:rgba(255,255,255,.45);margin-{{ $isAr?'right':'left' }}:2px;">
                                {{ $avgRating }} ({{ $reviewCount }})
                            </span>
                        </div>
                        @endif

                        <a href="{{ route('front.show', $company) }}#bk-services-tab" class="bk-btn-book mt-auto">
                            <i class="far fa-calendar-check {{ $isAr ? 'ms-1' : 'me-1' }}"></i>
                            {{ $isAr ? 'احجز الآن' : 'Book Now' }}
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($companies->hasPages())
        <div class="d-flex justify-content-center mt-5">{{ $companies->links() }}</div>
        @endif

        @else
        <div class="bk-empty appear-animation" data-appear-animation="fadeInUp" data-plugin-options="{'minWindowWidth':0}">
            <i class="fas fa-store-slash"></i>
            <h5>{{ $isAr ? 'لا توجد نتائج' : 'No Results Found' }}</h5>
            <p>{{ $isAr ? 'جرّب بحثاً آخر أو تصفّح تصنيفاً مختلفاً.' : 'Try a different search or browse another category.' }}</p>
            <a href="{{ route('front.index') }}" class="bk-btn-book d-inline-block mt-2" style="width:auto;padding:10px 28px;">{{ $isAr ? 'عرض الكل' : 'View All' }}</a>
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
