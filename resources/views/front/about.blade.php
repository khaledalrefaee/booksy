<!DOCTYPE html>
@php
    $isAr = app()->getLocale() === 'ar';
    $dir  = $isAr ? 'rtl' : 'ltr';
    $lang = $isAr ? 'ar' : 'en';
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $isAr ? 'من نحن – بوكسي' : 'About Us – Booksy' }}</title>

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
body,p,span,li,a,input,textarea,select{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@endif
html,body{background:#0a0a0a!important;color:rgba(255,255,255,.82)!important;scroll-behavior:smooth;}
.section{background-color:transparent!important;}
.main,.body{background:#0a0a0a!important;}

/* ── Navbar (shared) ── */
#bk-navbar{background:#0a0a0a;border-bottom:1px solid rgba(201,162,39,.15);height:68px;z-index:1050;transition:box-shadow .3s;}
#bk-navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.6);border-bottom-color:rgba(201,162,39,.25);}
#bk-navbar .navbar-brand{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:900;color:#fff;letter-spacing:-1px;text-decoration:none;}
#bk-navbar .navbar-brand span{color:#C9A227;}
#bk-navbar .navbar-toggler{border:1px solid rgba(201,162,39,.35);padding:6px 10px;color:#C9A227;background:transparent;}
#bk-navbar .navbar-toggler:focus{box-shadow:none;}
#bk-navbar .nav-link{color:rgba(255,255,255,.7)!important;font-family:'Poppins',sans-serif;font-size:.86rem;font-weight:500;padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;}
#bk-navbar .nav-link:hover,#bk-navbar .nav-link.active-page{color:#C9A227!important;background:rgba(201,162,39,.07);}
.bk-lang{color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;transition:all .2s;white-space:nowrap;}
.bk-lang:hover{background:#C9A227;color:#0a0a0a;}
.bk-login-link{color:rgba(255,255,255,.65);font-family:'Poppins',sans-serif;font-size:.84rem;font-weight:500;text-decoration:none;transition:color .2s;white-space:nowrap;}
.bk-login-link:hover{color:#C9A227;}
.bk-register-btn{background:#C9A227;color:#0a0a0a!important;border:none;border-radius:22px;padding:8px 20px;font-size:.83rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .22s;white-space:nowrap;}
.bk-register-btn:hover{background:#e8c84a;box-shadow:0 4px 18px rgba(201,162,39,.35);}
@media(max-width:991px){#bk-navbar .navbar-collapse{background:#111;border:1px solid rgba(201,162,39,.12);border-radius:12px;padding:16px;margin-top:10px;}}

/* ── PAGE HERO ── */
#about-hero{
    padding:140px 0 80px;
    background: linear-gradient(135deg,#0a0a0a 0%,#111 50%,#0a0a0a 100%);
    position:relative;
    overflow:hidden;
}
#about-hero::before{
    content:'';position:absolute;inset:0;
    background:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(201,162,39,.08) 0%,transparent 70%);
    pointer-events:none;
}
/* floating gold circles */
.hero-bubble{
    position:absolute;border-radius:50%;
    background:rgba(201,162,39,.06);
    border:1px solid rgba(201,162,39,.12);
    animation:floatBubble linear infinite;
    pointer-events:none;
}
@keyframes floatBubble{
    0%{transform:translateY(0) rotate(0deg);opacity:.6;}
    50%{opacity:1;}
    100%{transform:translateY(-80px) rotate(180deg);opacity:.6;}
}

/* ── STATS BAR ── */
.bk-stat-card{
    background:#111;border:1px solid rgba(201,162,39,.15);border-radius:16px;
    padding:28px 20px;text-align:center;transition:all .3s;position:relative;overflow:hidden;
}
.bk-stat-card::before{
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(201,162,39,.06),transparent);
    opacity:0;transition:opacity .3s;
}
.bk-stat-card:hover{transform:translateY(-5px);border-color:rgba(201,162,39,.45);box-shadow:0 12px 40px rgba(201,162,39,.1);}
.bk-stat-card:hover::before{opacity:1;}
.bk-stat-num{font-size:2.4rem;font-weight:800;color:#C9A227;font-family:'Poppins',sans-serif;line-height:1;text-shadow:0 0 20px rgba(201,162,39,.3);}
.bk-stat-lbl{font-size:.84rem;color:rgba(255,255,255,.5);margin-top:6px;font-family:'Poppins',sans-serif;}

/* ── MISSION / VISION cards ── */
.bk-mv-card{
    background:#111;border:1px solid rgba(201,162,39,.12);border-radius:18px;
    padding:36px 30px;height:100%;transition:all .3s;position:relative;overflow:hidden;
}
.bk-mv-card::after{
    content:'';position:absolute;bottom:0;{{ $isAr ? 'right' : 'left' }}:0;
    width:100%;height:3px;background:linear-gradient(90deg,#C9A227,transparent);
    transform:scaleX(0);transform-origin:{{ $isAr ? 'right' : 'left' }};
    transition:transform .4s cubic-bezier(.25,.8,.25,1);
}
.bk-mv-card:hover{border-color:rgba(201,162,39,.4);transform:translateY(-6px);box-shadow:0 16px 45px rgba(0,0,0,.4);}
.bk-mv-card:hover::after{transform:scaleX(1);}
.bk-mv-icon{width:68px;height:68px;border-radius:16px;background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.2);
    display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#C9A227;margin-bottom:20px;
    transition:all .3s;}
.bk-mv-card:hover .bk-mv-icon{background:#C9A227;color:#0a0a0a;border-color:#C9A227;}
.bk-mv-card h4{font-size:1.2rem;font-weight:700;color:#fff;margin-bottom:12px;}
.bk-mv-card p{font-size:.9rem;color:rgba(255,255,255,.5);line-height:1.75;}

/* ── TEAM CARDS ── */
.bk-team-card{
    background:#111;border:1px solid rgba(201,162,39,.1);border-radius:18px;
    overflow:hidden;transition:all .32s cubic-bezier(.25,.8,.25,1);
}
.bk-team-card:hover{transform:translateY(-8px);border-color:rgba(201,162,39,.4);box-shadow:0 20px 50px rgba(0,0,0,.5);}
.bk-team-img{
    height:220px;background:#1a1a1a;display:flex;align-items:center;justify-content:center;
    overflow:hidden;position:relative;
}
.bk-team-img img{width:100%;height:100%;object-fit:cover;transition:transform .45s;}
.bk-team-card:hover .bk-team-img img{transform:scale(1.08);}
.bk-team-img .bk-team-overlay{
    position:absolute;inset:0;
    background:linear-gradient(180deg,transparent 40%,rgba(10,10,10,.9) 100%);
    display:flex;align-items:flex-end;justify-content:center;padding-bottom:16px;
    opacity:0;transition:opacity .3s;
}
.bk-team-card:hover .bk-team-overlay{opacity:1;}
.bk-team-social a{width:34px;height:34px;border-radius:50%;background:rgba(201,162,39,.2);border:1px solid #C9A227;
    display:inline-flex;align-items:center;justify-content:center;color:#C9A227;font-size:.85rem;
    margin:0 3px;text-decoration:none;transition:all .2s;}
.bk-team-social a:hover{background:#C9A227;color:#0a0a0a;}
.bk-team-body{padding:20px;}
.bk-team-name{font-size:1rem;font-weight:700;color:#fff;margin-bottom:4px;font-family:'Poppins',sans-serif;}
.bk-team-role{font-size:.78rem;color:#C9A227;font-weight:600;font-family:'Poppins',sans-serif;}

/* ── VALUES ── */
.bk-val-item{
    display:flex;align-items:flex-start;gap:18px;padding:22px;
    background:#111;border:1px solid rgba(201,162,39,.1);border-radius:14px;
    transition:all .28s;
}
.bk-val-item:hover{border-color:rgba(201,162,39,.4);background:#151515;transform:translateX({{ $isAr ? '-' : '' }}5px);}
.bk-val-icon{width:50px;height:50px;border-radius:12px;background:rgba(201,162,39,.1);
    display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#C9A227;flex-shrink:0;transition:all .25s;}
.bk-val-item:hover .bk-val-icon{background:#C9A227;color:#0a0a0a;}
.bk-val-item h6{font-size:.94rem;font-weight:700;color:#fff;margin-bottom:5px;}
.bk-val-item p{font-size:.82rem;color:rgba(255,255,255,.45);line-height:1.6;margin:0;}

/* ── TIMELINE ── */
.bk-timeline{position:relative;padding:0 0 0 36px;}
.bk-timeline::before{
    content:'';position:absolute;top:0;bottom:0;{{ $isAr ? 'right' : 'left' }}:10px;
    width:2px;background:linear-gradient(180deg,#C9A227,rgba(201,162,39,.1));
}
.bk-tl-item{position:relative;margin-bottom:36px;}
.bk-tl-dot{
    position:absolute;{{ $isAr ? 'right' : 'left' }}:-26px;top:6px;
    width:14px;height:14px;border-radius:50%;background:#C9A227;
    box-shadow:0 0 0 4px rgba(201,162,39,.2);
    animation:pulseDot 2s ease-in-out infinite;
}
@keyframes pulseDot{
    0%,100%{box-shadow:0 0 0 4px rgba(201,162,39,.2);}
    50%{box-shadow:0 0 0 8px rgba(201,162,39,.08);}
}
.bk-tl-year{font-size:.73rem;font-weight:700;color:#C9A227;font-family:'Poppins',sans-serif;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;}
.bk-tl-item h6{font-size:.95rem;font-weight:700;color:#fff;margin-bottom:5px;}
.bk-tl-item p{font-size:.83rem;color:rgba(255,255,255,.45);line-height:1.65;margin:0;}

/* ── CTA ── */
.bk-cta-strip{
    background:linear-gradient(135deg,#111 0%,rgba(201,162,39,.08) 50%,#111 100%);
    border-top:1px solid rgba(201,162,39,.12);border-bottom:1px solid rgba(201,162,39,.12);
    padding:70px 0;text-align:center;
}

/* ── FOOTER ── */
footer.booksy-footer{background:#050505;border-top:1px solid rgba(201,162,39,.1);padding:60px 0 0;}
footer.booksy-footer .footer-brand{font-size:1.7rem;font-weight:900;color:#fff;font-family:'Poppins',sans-serif;}
footer.booksy-footer .footer-brand span{color:#C9A227;}
footer.booksy-footer p{color:rgba(255,255,255,.42);font-size:.87rem;}
footer.booksy-footer h6{color:#C9A227;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:16px;font-family:'Poppins',sans-serif;}
footer.booksy-footer ul{list-style:none;padding:0;margin:0;}
footer.booksy-footer ul li{margin-bottom:9px;}
footer.booksy-footer ul li a{color:rgba(255,255,255,.42);text-decoration:none;font-size:.86rem;transition:color .2s;}
footer.booksy-footer ul li a:hover{color:#C9A227;}
footer.booksy-footer .copy{font-size:.78rem;color:rgba(255,255,255,.2);text-align:center;}
footer.booksy-footer .social-icons li a{background:rgba(255,255,255,.04)!important;border:1px solid rgba(255,255,255,.1)!important;color:rgba(255,255,255,.55)!important;}
footer.booksy-footer .social-icons li a:hover{background:#C9A227!important;border-color:#C9A227!important;color:#0a0a0a!important;}

/* ── ANIMATE HELPERS ── */
[data-aos]{opacity:0;transition-property:opacity,transform;}
</style>
</head>
<body>
<div class="body">

{{-- NAVBAR --}}
<nav id="bk-navbar" class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a href="{{ route('front.index') }}" class="navbar-brand">Booksy<span>.</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bkNav">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="bkNav">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('front.index') }}">{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                <li class="nav-item"><a class="nav-link active-page" href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل معنا' : 'Contact' }}</a></li>
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

{{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
<section id="about-hero">
    {{-- floating bubbles --}}
    <div class="hero-bubble" style="width:300px;height:300px;top:-80px;{{ $isAr ? 'left' : 'right' }}:-60px;animation-duration:14s;"></div>
    <div class="hero-bubble" style="width:160px;height:160px;bottom:40px;{{ $isAr ? 'right' : 'left' }}:5%;animation-duration:9s;animation-delay:2s;"></div>
    <div class="hero-bubble" style="width:80px;height:80px;top:30%;{{ $isAr ? 'left' : 'right' }}:20%;animation-duration:7s;animation-delay:1s;"></div>

    <div class="container position-relative" style="z-index:2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <p style="font-size:.72rem;font-weight:700;color:#C9A227;text-transform:uppercase;letter-spacing:4px;font-family:'Poppins',sans-serif;margin-bottom:12px;">
                    ✦ {{ $isAr ? 'تعرّف علينا' : 'Get to Know Us' }} ✦
                </p>
                <h1 style="font-size:3rem;font-weight:900;color:#fff;line-height:1.15;margin-bottom:20px;">
                    {{ $isAr ? 'نحن' : 'We Are' }}
                    <span style="color:#C9A227;display:block;">{{ $isAr ? 'بوكسي للحجوزات' : 'Booksy Platform' }}</span>
                </h1>
                <p style="font-size:1rem;color:rgba(255,255,255,.58);line-height:1.8;margin-bottom:28px;">
                    {{ $isAr
                        ? 'بوكسي منصة رقمية متكاملة تربط عملاء التجميل والصحة بأفضل الصالونات والمراكز والعيادات. نؤمن بأن تجربة الحجز يجب أن تكون سهلة، سريعة، وممتعة.'
                        : 'Booksy is a comprehensive digital platform connecting beauty and health clients with the best salons, centers, and clinics. We believe the booking experience should be easy, fast, and enjoyable.'
                    }}
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('front.index') }}" class="bk-register-btn" style="border-radius:12px;padding:12px 28px;">
                        <i class="fas fa-search"></i> {{ $isAr ? 'استعرض الأماكن' : 'Browse Places' }}
                    </a>
                    <a href="{{ route('front.contact') }}" style="background:transparent;color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:12px;padding:12px 28px;font-weight:600;font-size:.88rem;font-family:'Poppins',sans-serif;text-decoration:none;transition:all .2s;">
                        {{ $isAr ? 'تواصل معنا' : 'Contact Us' }}
                    </a>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                <div style="position:relative;">
                    <div style="background:#111;border:1px solid rgba(201,162,39,.15);border-radius:24px;padding:36px;box-shadow:0 30px 80px rgba(0,0,0,.5);">
                        @php
                            $heroStats = [
                                ['n'=>App\Models\Company::where('status','active')->count().'+', 'ar'=>'شركة مسجّلة',  'en'=>'Registered Businesses', 'i'=>'fas fa-store'],
                                ['n'=>App\Models\Appointment::count().'+',                        'ar'=>'حجز مكتمل',    'en'=>'Completed Bookings',    'i'=>'fas fa-calendar-check'],
                                ['n'=>App\Models\Branch::count().'+',                             'ar'=>'فرع متاح',     'en'=>'Active Branches',        'i'=>'fas fa-map-marker-alt'],
                                ['n'=>App\Models\Category::count(),                               'ar'=>'تصنيف خدمات',  'en'=>'Service Categories',     'i'=>'fas fa-th-large'],
                            ];
                        @endphp
                        <div class="row g-3">
                            @foreach($heroStats as $s)
                            <div class="col-6">
                                <div style="background:#1a1a1a;border:1px solid rgba(201,162,39,.1);border-radius:14px;padding:20px 14px;text-align:center;">
                                    <i class="{{ $s['i'] }}" style="font-size:1.5rem;color:#C9A227;display:block;margin-bottom:8px;"></i>
                                    <div style="font-size:1.8rem;font-weight:800;color:#C9A227;font-family:'Poppins',sans-serif;line-height:1;">{{ $s['n'] }}</div>
                                    <div style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:5px;">{{ $isAr ? $s['ar'] : $s['en'] }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div style="margin-top:20px;padding:16px;background:rgba(201,162,39,.06);border-radius:12px;border:1px solid rgba(201,162,39,.12);text-align:center;">
                            <i class="fas fa-award" style="color:#C9A227;font-size:1.2rem;"></i>
                            <span style="color:rgba(255,255,255,.7);font-size:.85rem;margin-{{ $isAr ? 'right' : 'left' }}:10px;">
                                {{ $isAr ? 'المنصة الأولى للحجوزات في المنطقة' : '#1 Booking Platform in the Region' }}
                            </span>
                        </div>
                    </div>
                    {{-- decorative dots --}}
                    <div style="position:absolute;top:-15px;{{ $isAr ? 'left' : 'right' }}:-15px;width:80px;height:80px;
                        background-image:radial-gradient(rgba(201,162,39,.35) 1.5px,transparent 1.5px);
                        background-size:12px 12px;border-radius:8px;z-index:-1;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ MISSION / VISION / VALUES ═══════════════════════════════ --}}
<section style="padding:80px 0;background:#0a0a0a;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <p class="section-label">{{ $isAr ? 'هويتنا' : 'Our Identity' }}</p>
            <h2 class="section-heading">{{ $isAr ? 'رسالتنا' : 'Mission' }} &amp; <span>{{ $isAr ? 'رؤيتنا' : 'Vision' }}</span></h2>
            <div class="divider-gold center"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="bk-mv-card">
                    <div class="bk-mv-icon"><i class="fas fa-bullseye"></i></div>
                    <h4>{{ $isAr ? 'رسالتنا' : 'Our Mission' }}</h4>
                    <p>{{ $isAr ? 'تبسيط تجربة حجز المواعيد في قطاع التجميل والصحة من خلال منصة رقمية موثوقة تربط العملاء بمزودي الخدمات بسهولة وشفافية.' : 'Simplify the appointment booking experience in the beauty and health sector through a reliable digital platform that connects clients with service providers easily and transparently.' }}</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="bk-mv-card">
                    <div class="bk-mv-icon"><i class="fas fa-eye"></i></div>
                    <h4>{{ $isAr ? 'رؤيتنا' : 'Our Vision' }}</h4>
                    <p>{{ $isAr ? 'أن نكون المنصة الرائدة في حجوزات التجميل والرفاهية على مستوى المنطقة، ونُحدث ثورة في طريقة تعامل الناس مع خدمات العناية الشخصية.' : 'To be the leading platform for beauty and wellness bookings across the region, revolutionizing how people interact with personal care services.' }}</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="bk-mv-card">
                    <div class="bk-mv-icon"><i class="fas fa-heart"></i></div>
                    <h4>{{ $isAr ? 'قيمنا' : 'Our Values' }}</h4>
                    <p>{{ $isAr ? 'الثقة، الشفافية، الابتكار المستمر، وضع العميل في المقام الأول، والتعاون الوثيق مع شركائنا من أصحاب الأعمال.' : 'Trust, transparency, continuous innovation, putting the customer first, and close collaboration with our business partners.' }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ VALUES LIST ═══════════════════════════════ --}}
<section style="padding:70px 0;background:#080808;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <p class="section-label">{{ $isAr ? 'ما يميزنا' : 'What Sets Us Apart' }}</p>
                <h2 class="section-heading">{{ $isAr ? 'لماذا' : 'Why Choose' }} <span>{{ $isAr ? 'بوكسي؟' : 'Booksy?' }}</span></h2>
                <div class="divider-gold"></div>
                <p style="color:rgba(255,255,255,.5);font-size:.92rem;line-height:1.8;">
                    {{ $isAr ? 'بنينا بوكسي على أساس الاستماع لاحتياجات العملاء وأصحاب الصالونات على حدٍّ سواء.' : 'We built Booksy by listening to the needs of both clients and salon owners alike.' }}
                </p>
            </div>
            <div class="col-lg-7">
                @php
                    $vals = [
                        ['i'=>'fas fa-shield-alt',    'ar'=>'أمان وموثوقية',        'en'=>'Security & Trust',       'dar'=>'جميع الأماكن موثّقة ومراجَعة قبل الإدراج.','den'=>'All places are verified and reviewed before listing.'],
                        ['i'=>'fas fa-bolt',           'ar'=>'حجز فوري',             'en'=>'Instant Booking',        'dar'=>'احجز في ثوانٍ بدون مكالمات أو انتظار.','den'=>'Book in seconds without calls or waiting.'],
                        ['i'=>'fas fa-star',           'ar'=>'تقييمات حقيقية',       'en'=>'Real Reviews',           'dar'=>'آراء عملاء حقيقيين تساعدك على الاختيار.','den'=>'Real client reviews to help you choose.'],
                        ['i'=>'fas fa-bell',           'ar'=>'تذكيرات تلقائية',      'en'=>'Auto Reminders',         'dar'=>'تذكيرات بموعدك حتى لا تنسى أي حجز.','den'=>'Automatic reminders so you never miss an appointment.'],
                        ['i'=>'fas fa-chart-bar',      'ar'=>'لوحة تحكم ذكية',      'en'=>'Smart Dashboard',        'dar'=>'لأصحاب الأعمال: تقارير وإحصائيات مفصّلة.','den'=>'For business owners: detailed reports and analytics.'],
                        ['i'=>'fas fa-globe',          'ar'=>'دعم عربي وإنجليزي',   'en'=>'Arabic & English',       'dar'=>'المنصة تدعم اللغتين العربية والإنجليزية.','den'=>'The platform supports both Arabic and English.'],
                    ];
                @endphp
                <div class="row g-3">
                    @foreach($vals as $i => $v)
                    <div class="col-sm-6" data-aos="fade-up" data-aos-delay="{{ $i * 80 }}">
                        <div class="bk-val-item">
                            <div class="bk-val-icon"><i class="{{ $v['i'] }}"></i></div>
                            <div>
                                <h6>{{ $isAr ? $v['ar'] : $v['en'] }}</h6>
                                <p>{{ $isAr ? $v['dar'] : $v['den'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TIMELINE ═══════════════════════════════ --}}
<section style="padding:80px 0;background:#0a0a0a;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5" data-aos="fade-up">
                    <p class="section-label">{{ $isAr ? 'رحلتنا' : 'Our Journey' }}</p>
                    <h2 class="section-heading">{{ $isAr ? 'كيف' : 'How We' }} <span>{{ $isAr ? 'بدأنا؟' : 'Started' }}</span></h2>
                    <div class="divider-gold center"></div>
                </div>
                @php
                    $tl = [
                        ['y'=>'2022', 'ar'=>'الفكرة', 'en'=>'The Idea',
                         'dar'=>'بدأت الفكرة من معاناة حقيقية في إيجاد صالون والحجز بدون انتظار.',
                         'den'=>'The idea started from a real struggle of finding a salon and booking without waiting.'],
                        ['y'=>'2023', 'ar'=>'الإطلاق', 'en'=>'The Launch',
                         'dar'=>'أطلقنا النسخة الأولى من بوكسي مع 10 صالونات شريكة في المدينة.',
                         'den'=>'We launched the first version of Booksy with 10 partner salons in the city.'],
                        ['y'=>'2024', 'ar'=>'النمو', 'en'=>'The Growth',
                         'dar'=>'توسعنا لأكثر من 50 شريكاً وأضفنا العيادات ومراكز السبا.',
                         'den'=>'We expanded to 50+ partners and added clinics and spa centers.'],
                        ['y'=>'2025', 'ar'=>'المستقبل', 'en'=>'The Future',
                         'dar'=>'نطمح للتوسع الإقليمي وإضافة ميزات الذكاء الاصطناعي لتوصيات مخصصة.',
                         'den'=>'We aim for regional expansion and adding AI-powered personalized recommendations.'],
                    ];
                @endphp
                <div class="bk-timeline">
                    @foreach($tl as $i => $t)
                    <div class="bk-tl-item" data-aos="fade-{{ $isAr ? 'right' : 'left' }}" data-aos-delay="{{ $i * 120 }}">
                        <div class="bk-tl-dot"></div>
                        <div class="bk-tl-year">{{ $t['y'] }}</div>
                        <h6>{{ $isAr ? $t['ar'] : $t['en'] }}</h6>
                        <p>{{ $isAr ? $t['dar'] : $t['den'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TEAM ═══════════════════════════════ --}}
<section style="padding:80px 0;background:#080808;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <p class="section-label">{{ $isAr ? 'فريقنا' : 'Our Team' }}</p>
            <h2 class="section-heading">{{ $isAr ? 'الأشخاص' : 'The People' }} <span>{{ $isAr ? 'وراء بوكسي' : 'Behind Booksy' }}</span></h2>
            <div class="divider-gold center"></div>
        </div>
        @php
            $team = [
                ['ar'=>'أحمد الرشيد',   'en'=>'Ahmed Al-Rashid',  'rar'=>'المؤسس والرئيس التنفيذي',   'ren'=>'Founder & CEO',         'avatar'=>'https://ui-avatars.com/api/?name=Ahmed+Rashid&background=C9A227&color=0a0a0a&size=200&bold=true'],
                ['ar'=>'سارة المحمد',    'en'=>'Sarah Al-Mohammed','rar'=>'مديرة المنتج',               'ren'=>'Product Director',      'avatar'=>'https://ui-avatars.com/api/?name=Sarah+M&background=111111&color=C9A227&size=200&bold=true'],
                ['ar'=>'خالد العتيبي',   'en'=>'Khalid Al-Otaibi', 'rar'=>'مدير التقنية',              'ren'=>'CTO',                   'avatar'=>'https://ui-avatars.com/api/?name=Khalid+O&background=C9A227&color=0a0a0a&size=200&bold=true'],
                ['ar'=>'نورة الحربي',    'en'=>'Noura Al-Harbi',   'rar'=>'مديرة التسويق',             'ren'=>'Marketing Director',    'avatar'=>'https://ui-avatars.com/api/?name=Noura+H&background=111111&color=C9A227&size=200&bold=true'],
            ];
        @endphp
        <div class="row g-4 justify-content-center">
            @foreach($team as $i => $m)
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $i * 100 }}">
                <div class="bk-team-card">
                    <div class="bk-team-img">
                        <img src="{{ $m['avatar'] }}" alt="{{ $isAr ? $m['ar'] : $m['en'] }}">
                        <div class="bk-team-overlay">
                            <div class="bk-team-social">
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="bk-team-body">
                        <div class="bk-team-name">{{ $isAr ? $m['ar'] : $m['en'] }}</div>
                        <div class="bk-team-role">{{ $isAr ? $m['rar'] : $m['ren'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ CTA ═══════════════════════════════ --}}
<div class="bk-cta-strip" data-aos="zoom-in">
    <div class="container">
        <p class="section-label">{{ $isAr ? 'انضم إلينا' : 'Join Us' }}</p>
        <h2 class="section-heading" style="margin-bottom:16px;">
            {{ $isAr ? 'هل أنت صاحب' : 'Own a' }} <span>{{ $isAr ? 'صالون أو عيادة؟' : 'Salon or Clinic?' }}</span>
        </h2>
        <p style="color:rgba(255,255,255,.45);font-size:.95rem;max-width:500px;margin:0 auto 28px;">
            {{ $isAr ? 'سجّل نشاطك على بوكسي وابدأ في استقبال الحجوزات اليوم.' : 'List your business on Booksy and start receiving bookings today.' }}
        </p>
        <a href="{{ route('company.register') }}" class="bk-register-btn" style="font-size:.95rem;padding:14px 36px;border-radius:30px;">
            <i class="fas fa-rocket"></i> {{ $isAr ? 'سجّل مجاناً' : 'Register Free' }}
        </a>
    </div>
</div>

</div>{{-- .main --}}

{{-- FOOTER --}}
<footer class="booksy-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <span class="footer-brand">Booksy<span>.</span></span>
                <div class="divider-gold mt-2 mb-3"></div>
                <p>{{ $isAr ? 'منصتك الأولى لحجز مواعيد صالونات التجميل والعيادات.' : 'Your #1 platform for beauty salon & clinic bookings.' }}</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>{{ $isAr ? 'روابط' : 'Links' }}</h6>
                <ul>
                    <li><a href="{{ route('front.index') }}">{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                    <li><a href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                    <li><a href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل' : 'Contact' }}</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6>{{ $isAr ? 'للأعمال' : 'Business' }}</h6>
                <ul>
                    <li><a href="{{ route('company.register') }}">{{ $isAr ? 'تسجيل نشاط' : 'Register' }}</a></li>
                    <li><a href="{{ route('company.login') }}">{{ $isAr ? 'تسجيل دخول' : 'Login' }}</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6>{{ $isAr ? 'اللغة' : 'Language' }}</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('locale.switch','ar') }}" class="bk-lang" style="{{ $isAr ? 'background:#C9A227;color:#0a0a0a;' : '' }}">عربي</a>
                    <a href="{{ route('locale.switch','en') }}" class="bk-lang" style="{{ !$isAr ? 'background:#C9A227;color:#0a0a0a;' : '' }}">EN</a>
                </div>
            </div>
        </div>
    </div>
    <div style="margin-top:40px;border-top:1px solid rgba(255,255,255,.07);padding:20px 0;">
        <div class="container"><p class="copy">&copy; {{ date('Y') }} Booksy — {{ $isAr ? 'جميع الحقوق محفوظة' : 'All Rights Reserved' }}</p></div>
    </div>
</footer>

{{-- SCRIPTS --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>

<script>
(function($){
    'use strict';
    $(document).ready(function(){

        /* navbar scroll */
        $(window).on('scroll',function(){
            $('#bk-navbar').toggleClass('scrolled', $(this).scrollTop() > 30);
        });

        /* AOS-like scroll animations (using IntersectionObserver) */
        var observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    var el = entry.target;
                    var delay = parseInt(el.getAttribute('data-aos-delay')||0);
                    var anim  = el.getAttribute('data-aos') || 'fade-up';
                    setTimeout(function(){
                        el.style.opacity = '1';
                        el.style.transform = 'none';
                        el.style.transition = 'opacity .7s ease, transform .7s ease';
                    }, delay);
                    observer.unobserve(el);
                }
            });
        }, {threshold: 0.12});

        /* initial states */
        $('[data-aos]').each(function(){
            var anim = $(this).attr('data-aos');
            $(this).css({opacity:0, transform:
                anim==='fade-up'?'translateY(35px)':
                anim==='fade-down'?'translateY(-35px)':
                anim==='fade-left'?'translateX(40px)':
                anim==='fade-right'?'translateX(-40px)':
                anim==='zoom-in'?'scale(.88)':
                'translateY(25px)'
            });
            observer.observe(this);
        });

        /* pulsing timeline dots already CSS-animated */
    });
})(jQuery);
</script>
</div>{{-- .body --}}
</body>
</html>
