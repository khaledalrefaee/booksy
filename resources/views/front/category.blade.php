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
    $sl  = strtolower($category->slug ?? '');
    $catIcon = $category->icon ?: 'fas fa-store';
    foreach($catIcons as $k=>$v){ if(str_contains($sl,$k)){$catIcon=$v;break;} }
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $catName }} — Booksy</title>

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

/* ── Navbar (same as index) ── */
#bk-navbar{background:#0a0a0a;border-bottom:1px solid rgba(201,162,39,.15);height:68px;z-index:1050;transition:box-shadow .3s;}
#bk-navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.6);border-bottom-color:rgba(201,162,39,.25);}
#bk-navbar .navbar-brand{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:900;color:#fff;letter-spacing:-1px;text-decoration:none;}
#bk-navbar .navbar-brand span{color:#C9A227;}
#bk-navbar .navbar-toggler{border:1px solid rgba(201,162,39,.35);padding:6px 10px;color:#C9A227;background:transparent;}
#bk-navbar .navbar-toggler:focus{box-shadow:none;}
#bk-navbar .nav-link{color:rgba(255,255,255,.7)!important;font-family:'Poppins',sans-serif;font-size:.86rem;font-weight:500;padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;}
#bk-navbar .nav-link:hover,#bk-navbar .nav-link.active-link{color:#C9A227!important;background:rgba(201,162,39,.07);}
.bk-lang{color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;transition:all .2s;white-space:nowrap;}
.bk-lang:hover{background:#C9A227;color:#0a0a0a;}
.bk-login-link{color:rgba(255,255,255,.65);font-family:'Poppins',sans-serif;font-size:.84rem;font-weight:500;text-decoration:none;transition:color .2s;white-space:nowrap;}
.bk-login-link:hover{color:#C9A227;}
.bk-register-btn{background:#C9A227;color:#0a0a0a!important;border:none;border-radius:22px;padding:8px 20px;font-size:.83rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .22s;white-space:nowrap;}
.bk-register-btn:hover{background:#e8c84a;box-shadow:0 4px 18px rgba(201,162,39,.35);}
@media(max-width:991px){#bk-navbar .navbar-collapse{background:#111;border:1px solid rgba(201,162,39,.12);border-radius:12px;padding:16px;margin-top:10px;}}

/* ── Category Header Band ── */
.cat-hero-band{
    background:linear-gradient(135deg,#0f1a12 0%,#0a0a0a 60%,#1a100a 100%);
    border-bottom:1px solid rgba(201,162,39,.15);
    padding:52px 0 40px;
    margin-top:68px;
}
.cat-hero-icon-wrap{
    width:80px;height:80px;border-radius:20px;
    overflow:hidden;background:rgba(201,162,39,.07);
    border:2px solid rgba(201,162,39,.3);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.cat-hero-icon-wrap img{width:100%;height:100%;object-fit:cover;}
.cat-hero-icon-wrap i{font-size:2rem;color:#C9A227;}

/* ── Category Sub-strip ── */
.bk-cats-strip{background:#0d0d0d;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);padding:14px 0 10px;}
.bk-cats-scroll{display:flex;align-items:flex-start;gap:4px;overflow-x:auto;padding:0 12px 4px;-webkit-overflow-scrolling:touch;scrollbar-width:none;}
.bk-cats-scroll::-webkit-scrollbar{display:none;}
.bk-cat2{display:flex;flex-direction:column;align-items:center;text-decoration:none!important;flex-shrink:0;width:66px;padding:4px 2px;border-radius:10px;transition:background .2s;cursor:pointer;border:2px solid transparent;}
.bk-cat2:hover{background:rgba(255,255,255,.04);}
.bk-cat2.active{border-color:rgba(201,162,39,.5);}
.bk-cat2-circle{width:50px;height:50px;border-radius:50%;overflow:hidden;background:#1e2a28;border:1.5px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;margin-bottom:6px;transition:border-color .2s,transform .2s;flex-shrink:0;}
.bk-cat2:hover .bk-cat2-circle,.bk-cat2.active .bk-cat2-circle{border-color:#C9A227;transform:scale(1.05);}
.bk-cat2-circle img{width:100%;height:100%;object-fit:cover;}
.bk-cat2-circle i{font-size:1.1rem;color:rgba(255,255,255,.55);}
.bk-cat2.active .bk-cat2-circle i{color:#C9A227;}
.bk-cat2-label{font-size:.64rem;font-weight:500;color:rgba(255,255,255,.6);text-align:center;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:64px;font-family:'Poppins',sans-serif;}
.bk-cat2.active .bk-cat2-label{color:#C9A227;font-weight:700;}
@media(min-width:768px){.bk-cats-strip{padding:20px 0 16px;}.bk-cats-scroll{gap:8px;padding:0 24px 4px;}.bk-cat2{width:72px;}.bk-cat2-circle{width:54px;height:54px;}.bk-cat2-label{font-size:.68rem;}}

/* ── Cards (same as index) ── */
.bk-card-dark{position:relative;overflow:hidden;border-radius:14px;background:#161616;border:1px solid rgba(201,162,39,.12);transition:transform .32s cubic-bezier(.25,.8,.25,1),box-shadow .32s,border-color .32s;}
.bk-card-dark:hover{transform:translateY(-9px);border-color:rgba(201,162,39,.55)!important;box-shadow:0 18px 50px rgba(0,0,0,.55),0 0 0 1px rgba(201,162,39,.2)!important;}
.bk-co-img{overflow:hidden;height:155px;position:relative;background:#222;display:flex;align-items:center;justify-content:center;}
.bk-co-img img{width:100%;height:100%;object-fit:cover;transition:transform .45s;}
.bk-card-dark:hover .bk-co-img img{transform:scale(1.07);}
.bk-co-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.05) 0%,rgba(0,0,0,.45) 100%);pointer-events:none;}
.bk-co-badge{position:absolute;top:8px;{{ $isAr?'right':'left' }}:8px;z-index:3;background:rgba(10,10,10,.88);color:#C9A227;font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid rgba(201,162,39,.3);backdrop-filter:blur(6px);}
.bk-co-body{padding:11px 13px 13px;background:#161616;flex:1;display:flex;flex-direction:column;border-top:1px solid rgba(201,162,39,.07);}
.bk-co-name{font-size:.84rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px;font-family:'Poppins',sans-serif;}
.bk-co-meta{font-size:.72rem;color:rgba(255,255,255,.38);margin-bottom:6px;}
.bk-co-meta i{color:#C9A227;font-size:.65rem;}
.bk-btn-book{background:transparent;color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:8px;padding:7px 0;font-size:.78rem;font-weight:600;text-align:center;display:block;width:100%;text-decoration:none;transition:all .2s;font-family:'Poppins',sans-serif;margin-top:auto;}
.bk-btn-book:hover{background:#C9A227;color:#0a0a0a;border-color:#C9A227;text-decoration:none;}
.bk-logo-fallback{width:52px;height:52px;border-radius:50%;background:rgba(201,162,39,.07);border:1px solid rgba(201,162,39,.2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#C9A227;}
.bk-empty{text-align:center;padding:60px 20px;color:rgba(255,255,255,.35);}
.bk-empty i{font-size:3rem;color:rgba(201,162,39,.2);display:block;margin-bottom:14px;}
.bk-empty h5{color:rgba(255,255,255,.55);}

/* Like button */
.bk-like-btn{position:absolute;bottom:8px;{{ $isAr?'left':'right' }}:8px;width:32px;height:32px;border-radius:50%;border:none;cursor:pointer;background:rgba(10,10,10,.8);display:flex;align-items:center;justify-content:center;z-index:3;transition:all .22s;backdrop-filter:blur(4px);}
.bk-like-btn:hover{background:rgba(231,76,60,.2);}

/* Rating badge */
.bk-rating-badge{position:absolute;top:8px;{{ $isAr?'left':'right' }}:8px;background:rgba(10,10,10,.88);color:#fff;font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:20px;border:1px solid rgba(201,162,39,.3);backdrop-filter:blur(6px);display:flex;align-items:center;gap:3px;z-index:3;}
.bk-rating-badge i{color:#C9A227;font-size:.62rem;}

/* Pagination */
.pagination .page-link{background:#1a1a1a;border-color:rgba(201,162,39,.25);color:#C9A227;border-radius:8px;}
.pagination .page-link:hover{background:#C9A227;color:#0a0a0a;border-color:#C9A227;}
.pagination .page-item.active .page-link{background:#C9A227!important;border-color:#C9A227!important;color:#0a0a0a!important;font-weight:700;}
.pagination .page-item.disabled .page-link{background:#111;border-color:#222;color:#555;}
</style>
</head>
<body>
<div class="body">

{{-- ══ NAVBAR ══ --}}
<nav id="bk-navbar" class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a href="{{ route('front.index') }}" class="navbar-brand">Booksy<span>.</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bkNavMenu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="bkNavMenu">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('front.index') }}">{{ $isAr?'الرئيسية':'Home' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.about') }}">{{ $isAr?'من نحن':'About' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.contact') }}">{{ $isAr?'تواصل':'Contact' }}</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                @if($isAr)
                    <a href="{{ route('locale.switch','en') }}" class="bk-lang">EN</a>
                @else
                    <a href="{{ route('locale.switch','ar') }}" class="bk-lang">عربي</a>
                @endif
                <a href="{{ route('company.login') }}" class="bk-login-link d-none d-lg-inline">{{ $isAr?'دخول الأعمال':'Business Login' }}</a>
                <a href="{{ route('company.register') }}" class="bk-register-btn">
                    <i class="fas fa-store"></i>{{ $isAr?'سجّل نشاطك':'List Business' }}
                </a>
            </div>
        </div>
    </div>
</nav>

<div role="main" class="main">

{{-- ══ CATEGORY HERO ══ --}}
<div class="cat-hero-band">
    <div class="container">
        <div class="d-flex align-items-center gap-4">
            {{-- Back --}}
            <a href="{{ route('front.index') }}"
               style="width:40px;height:40px;border-radius:10px;background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.25);display:flex;align-items:center;justify-content:center;color:#C9A227;flex-shrink:0;transition:all .2s;"
               onmouseover="this.style.background='rgba(201,162,39,.18)'" onmouseout="this.style.background='rgba(201,162,39,.08)'">
                <i class="fas fa-arrow-{{ $isAr?'right':'left' }}"></i>
            </a>

            {{-- Icon + Name --}}
            <div class="cat-hero-icon-wrap">
                @if($category->image)
                    <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $catName }}">
                @else
                    <i class="{{ $catIcon }}"></i>
                @endif
            </div>
            <div>
                <p class="section-label mb-1">{{ $isAr?'تصنيف':'Category' }}</p>
                <h1 style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;color:#fff;line-height:1.15;margin:0;">
                    {{ $catName }}
                </h1>
                <div style="font-size:.82rem;color:rgba(255,255,255,.45);margin-top:4px;font-family:'Poppins',sans-serif;">
                    {{ $companies->total() }}
                    {{ $isAr?'مكان متاح':'places available' }}
                </div>
            </div>

            {{-- Search --}}
            <div class="{{ $isAr?'me-auto':'ms-auto' }} d-none d-md-block" style="max-width:320px;width:100%;">
                <form action="{{ route('front.category',$category->slug) }}" method="GET">
                    <div class="bk-search" style="max-width:100%;">
                        <i class="fas fa-search" style="color:#C9A227;font-size:.85rem;flex-shrink:0;"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ $isAr?'ابحث في '.$catName.'...':'Search in '.$catName.'...' }}">
                        <button type="submit">{{ $isAr?'بحث':'Go' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══ OTHER CATEGORIES STRIP ══ --}}
<div class="bk-cats-strip">
    <div class="bk-cats-scroll">
        <a href="{{ route('front.index') }}" class="bk-cat2">
            <div class="bk-cat2-circle"><i class="fas fa-th-large"></i></div>
            <span class="bk-cat2-label">{{ $isAr?'الكل':'All' }}</span>
        </a>
        @foreach($categories as $cat)
        @php
            $sl2=$cat->icon ?: 'fas fa-store';
            if(!$cat->icon){
                $sl3=strtolower($cat->slug??'');
                foreach($catIcons as $k=>$v){if(str_contains($sl3,$k)){$sl2=$v;break;}}
            }
        @endphp
        <a href="{{ route('front.category',$cat->slug) }}"
           class="bk-cat2 {{ $cat->slug===$category->slug?'active':'' }}">
            <div class="bk-cat2-circle">
                @if($cat->image)
                    <img src="{{ asset('storage/'.$cat->image) }}" alt="{{ $isAr?$cat->name_ar:$cat->name_en }}">
                @else
                    <i class="{{ $sl2 }}"></i>
                @endif
            </div>
            <span class="bk-cat2-label">{{ $isAr?$cat->name_ar:$cat->name_en }}</span>
        </a>
        @endforeach
    </div>
</div>

{{-- ══ COMPANIES GRID ══ --}}
<section class="section border-0 m-0" style="padding:50px 0 70px;background:#0d0d0d;">
    <div class="container">

        {{-- Header row --}}
        <div class="row align-items-end mb-4">
            <div class="col-lg-7">
                <p class="section-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr?'الأماكن المميزة':'Featured Places' }}
                </p>
                <h2 class="section-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                    @if(request('search'))
                        {{ $isAr?'نتائج: ':'Results: ' }}<span>{{ request('search') }}</span>
                    @else
                        {{ $isAr?'في تصنيف: ':'In Category: ' }}<span>{{ $catName }}</span>
                    @endif
                </h2>
                <div class="divider-gold"></div>
            </div>
            <div class="col-lg-5 text-lg-end">
                <span style="background:rgba(201,162,39,.1);color:#C9A227;border:1px solid rgba(201,162,39,.3);border-radius:25px;padding:7px 18px;font-size:.86rem;font-weight:700;font-family:'Poppins',sans-serif;">
                    {{ $companies->total() }} {{ $isAr?'نتيجة':'results' }}
                </span>
                @if(request('search'))
                <a href="{{ route('front.category',$category->slug) }}" style="display:inline-block;margin-{{ $isAr?'right':'left' }}:10px;font-size:.82rem;color:#C9A227;text-decoration:none;">
                    <i class="fas fa-times-circle {{ $isAr?'ms-1':'me-1' }}"></i>{{ $isAr?'مسح':'Clear' }}
                </a>
                @endif
            </div>
        </div>

        {{-- Mobile search --}}
        <div class="d-md-none mb-4">
            <form action="{{ route('front.category',$category->slug) }}" method="GET">
                <div class="bk-search">
                    <i class="fas fa-search" style="color:#C9A227;font-size:.85rem;flex-shrink:0;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr?'ابحث...':'Search...' }}">
                    <button type="submit">{{ $isAr?'بحث':'Go' }}</button>
                </div>
            </form>
        </div>

        @if($companies->isNotEmpty())
        <div class="row g-3">
            @foreach($companies as $company)
            @php
                $firstBranch = $company->branches->first();
                $branchImg   = $firstBranch?->images?->first();
                $allReviews  = $company->branches->flatMap(fn($b) => $b->reviews);
                $reviewCount = $allReviews->count();
                $avgRating   = $reviewCount ? round($allReviews->avg('rating'),1) : null;
            @endphp
            <div class="col-6 col-md-4 col-lg-3 appear-animation"
                 data-appear-animation="fadeInUpShorter"
                 data-appear-animation-delay="{{ ($loop->index % 4) * 80 }}"
                 data-plugin-options="{'minWindowWidth':0}">
                <div class="bk-card-dark d-flex flex-column h-100">

                    {{-- Image --}}
                    <div class="bk-co-img">
                        @if($branchImg)
                            <img src="{{ asset('storage/'.$branchImg->path) }}" alt="{{ $isAr?$company->name_ar:$company->name_en }}" loading="lazy">
                        @elseif($company->logo)
                            <img src="{{ asset('storage/'.$company->logo) }}" alt="" loading="lazy" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid rgba(201,162,39,.4);">
                        @else
                            <div class="bk-logo-fallback"><i class="fas fa-store"></i></div>
                        @endif

                        @if($company->category)
                        <span class="bk-co-badge">{{ $isAr?$company->category->name_ar:$company->category->name_en }}</span>
                        @endif

                        @if($avgRating)
                        <span class="bk-rating-badge">
                            <i class="fas fa-star"></i>{{ $avgRating }}
                            <span style="color:rgba(255,255,255,.42);font-weight:400;">· {{ $reviewCount }}</span>
                        </span>
                        @endif

                        <button class="bk-like-btn" data-id="{{ $company->id }}" onclick="bkToggleLike(this,{{ $company->id }})">
                            <i class="far fa-heart" style="font-size:.8rem;color:rgba(255,255,255,.7);pointer-events:none;"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="bk-co-body">
                        <div class="bk-co-name mb-1">{{ $isAr?$company->name_ar:$company->name_en }}</div>
                        <div class="bk-co-meta mb-2">
                            <i class="fas fa-map-marker-alt {{ $isAr?'ms-1':'me-1' }}"></i>
                            {{ $firstBranch?->address ? Str::limit($firstBranch->address,28) : ($company->branches->count().' '.($isAr?'فرع':'branches')) }}
                        </div>
                        @if($avgRating)
                        <div class="d-flex align-items-center gap-1 mb-2">
                            @for($s=1;$s<=5;$s++)<i class="{{ $s<=round($avgRating)?'fas':'far' }} fa-star" style="color:#C9A227;font-size:.58rem;"></i>@endfor
                            <span style="font-size:.65rem;color:rgba(255,255,255,.42);margin-{{ $isAr?'right':'left' }}:2px;">{{ $avgRating }} ({{ $reviewCount }})</span>
                        </div>
                        @endif
                        <a href="{{ route('front.show',$company) }}#bk-services-tab" class="bk-btn-book">
                            <i class="far fa-calendar-check {{ $isAr?'ms-1':'me-1' }}"></i>{{ $isAr?'احجز الآن':'Book Now' }}
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
            <h5>{{ $isAr?'لا توجد أماكن في هذا التصنيف بعد':'No places in this category yet' }}</h5>
            <p style="margin-bottom:16px;">{{ $isAr?'جرّب تصنيفاً آخر.':'Try browsing another category.' }}</p>
            <a href="{{ route('front.index') }}" class="bk-btn-book d-inline-block" style="width:auto;padding:10px 28px;">
                {{ $isAr?'عرض جميع الأماكن':'Browse All Places' }}
            </a>
        </div>
        @endif

    </div>
</section>

@include('front.partials.footer')

{{-- Scripts --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
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
            if($(this).scrollTop()>30)$('#bk-navbar').addClass('scrolled');
            else $('#bk-navbar').removeClass('scrolled');
        });
        var liked=JSON.parse(localStorage.getItem('bk_liked')||'[]');
        liked.forEach(function(id){
            var btn=document.querySelector('.bk-like-btn[data-id="'+id+'"]');
            if(btn) bkSetLiked(btn,true);
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
function bkSetLiked(btn,on){
    var i=btn.querySelector('i');
    if(on){i.className='fas fa-heart';i.style.color='#e74c3c';btn.style.background='rgba(231,76,60,.15)';btn.style.border='1px solid rgba(231,76,60,.4)';}
    else{i.className='far fa-heart';i.style.color='rgba(255,255,255,.7)';btn.style.background='rgba(10,10,10,.8)';btn.style.border='none';}
}
</script>

</div>
</body>
</html>
