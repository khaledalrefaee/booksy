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
    $catGrad = [
        'salon'=>'bk-cg-salon','spa'=>'bk-cg-spa','clinic'=>'bk-cg-clinic',
        'beauty'=>'bk-cg-beauty','nail'=>'bk-cg-nail','hair'=>'bk-cg-salon',
        'skin'=>'bk-cg-spa','dental'=>'bk-cg-dental','gym'=>'bk-cg-gym',
        'massage'=>'bk-cg-spa','barber'=>'bk-cg-barber','lash'=>'bk-cg-lash',
        'brow'=>'bk-cg-nail','tattoo'=>'bk-cg-tattoo','wedding'=>'bk-cg-wedding',
        'laser'=>'bk-cg-laser',
    ];
    $fallbacks = [
        'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=700&q=80',
        'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=700&q=80',
        'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=700&q=80',
        'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=700&q=80',
        'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?w=700&q=80',
        'https://images.unsplash.com/photo-1610025929883-b47b0d672660?w=700&q=80',
    ];
    $partners = ['Marriott','Four Seasons','Hilton','Hyatt','IHG','Accor','Radisson','Mövenpick'];
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $isAr ? 'بوكسي — احجز موعدك' : 'Booksy — Book Your Appointment' }}</title>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">

<!-- Porto Vendors CSS -->
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap' . ($isAr ? '.rtl' : '') . '.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/simple-line-icons/css/simple-line-icons.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.theme.default.min.css') }}">

<!-- Porto Theme -->
<link rel="stylesheet" href="{{ asset('frontend/css/theme.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme-elements.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/demos/demo-beauty-salon.css') }}">

<!-- Booksy Skin (black + gold) -->
<link rel="stylesheet" href="{{ asset('frontend/css/skins/skin-booksy.css') }}">

<script src="{{ asset('frontend/vendor/modernizr/modernizr.min.js') }}"></script>

<style>
/* ── RTL font ── */
@if($isAr)
body,p,li,td,input,select,textarea,.form-control,
.nav-link,.bk-cc-name,.bk-cat-card-name{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@else
body{font-family:'Poppins',sans-serif;}
@endif

/* ── Base dark ── */
html,body{background:#0a0a0a!important;color:rgba(255,255,255,.82)!important;overflow-x:hidden;}
html{scroll-behavior:auto;}
.section,.main,.body{background:#0a0a0a!important;}
body{cursor:none;}
@media(max-width:768px){body{cursor:auto;}}

/* ── Cursor ── */
#v4-dot,#v4-ring{position:fixed;top:0;left:0;z-index:9998;pointer-events:none;border-radius:50%;transform:translate(-50%,-50%);will-change:left,top;}
#v4-dot{width:7px;height:7px;background:#C9A227;}
#v4-ring{width:44px;height:44px;border:1.5px solid rgba(201,162,39,.5);transition:width .35s,height .35s,background .35s;}
body.v4-hov #v4-dot{opacity:0;}
body.v4-hov #v4-ring{width:74px;height:74px;background:rgba(201,162,39,.08);border-color:#C9A227;}
body.v4-clk #v4-ring{width:30px;height:30px;}
@media(max-width:768px){#v4-dot,#v4-ring{display:none;}}

/* ══════════════════════════════
   LOADER — Minimal Wipe (v5)
══════════════════════════════ */
#v4-loader{
    position:fixed;inset:0;z-index:9999;
    background:#0a0a0a;
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    overflow:hidden;
}
.v4-ld-welcome{
    font-size:.62rem;letter-spacing:6px;text-transform:uppercase;
    color:rgba(255,255,255,.18);margin-bottom:18px;
    opacity:0;transform:translateY(8px);
}
.v4-ld-brand{
    font-size:clamp(2rem,5vw,3.8rem);font-weight:900;
    letter-spacing:-3px;line-height:1;overflow:hidden;
    margin-bottom:48px;
    @if(!$isAr) font-family:'Poppins',sans-serif!important; @endif
}
.v4-ld-brand em{font-style:normal;color:#C9A227;}
.v4-ld-brand-inner{display:block;transform:translateY(110%);}
.v4-ld-track{
    width:clamp(160px,26vw,300px);height:1px;
    background:rgba(201,162,39,.1);position:relative;overflow:hidden;
    margin-bottom:16px;
}
.v4-ld-sweep{
    position:absolute;top:0;left:-60%;width:60%;height:100%;
    background:linear-gradient(90deg,transparent,#C9A227,transparent);
    opacity:0;
}
.v4-ld-fill{
    position:absolute;top:0;left:0;height:100%;width:0%;
    background:#C9A227;transition:width .12s linear;
}
.v4-ld-pct{font-size:.72rem;letter-spacing:3px;color:rgba(201,162,39,.45);}
.v4-ld-wipe{
    position:absolute;inset:0;background:#C9A227;
    transform:scaleY(0);transform-origin:bottom;pointer-events:none;
}

/* ══════════════════════════════
   NAVBAR
══════════════════════════════ */
#v4-nav{
    background:rgba(10,10,10,0);
    border-bottom:1px solid transparent;
    height:68px;z-index:1050;
    transition:background .4s,border-color .4s,box-shadow .4s;
    backdrop-filter:blur(0px);
}
#v4-nav.scrolled{
    background:rgba(10,10,10,.92)!important;
    border-bottom-color:rgba(201,162,39,.15)!important;
    box-shadow:0 4px 30px rgba(0,0,0,.6)!important;
    backdrop-filter:blur(20px);
}
#v4-nav .navbar-brand{
    font-family:'Poppins',sans-serif!important;
    font-size:1.2rem;font-weight:900;color:#fff;letter-spacing:-.5px;
}
#v4-nav .navbar-brand span{color:#C9A227;}
#v4-nav .nav-link{
    color:rgba(255,255,255,.7)!important;font-size:.85rem;font-weight:500;
    padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;
}
#v4-nav .nav-link:hover{color:#C9A227!important;background:rgba(201,162,39,.07);}

/* ══════════════════════════════
   HERO
══════════════════════════════ */
#v4-hero{
    position:relative;min-height:100vh;
    display:flex;align-items:center;
    overflow:hidden;
}
.v4-hero-video{position:absolute;inset:0;z-index:0;}
.v4-hero-video video{width:100%;height:100%;object-fit:cover;transform:scale(1.08);}
.v4-hero-ov{
    position:absolute;inset:0;z-index:1;
    background:linear-gradient(155deg,rgba(10,10,10,.82) 0%,rgba(10,10,10,.35) 55%,rgba(10,10,10,.88) 100%);
}
.v4-hero-content{position:relative;z-index:2;padding-top:100px;}
.v4-hero-eyebrow{
    display:inline-flex;align-items:center;gap:10px;
    font-size:.6rem;font-weight:700;letter-spacing:4px;text-transform:uppercase;
    color:#C9A227;margin-bottom:16px;opacity:0;
}
.v4-hero-eyebrow::before,.v4-hero-eyebrow::after{
    content:'';display:block;width:22px;height:1px;background:#C9A227;
}
.v4-split-line{overflow:hidden;display:block;}
.v4-split-inner{display:block;transform:translateY(110%);}
.v4-hero-title{
    font-size:clamp(1.5rem,2.8vw,2.6rem);font-weight:900;
    line-height:1.08;letter-spacing:-1px;color:#fff;margin-bottom:26px;
}
.v4-hero-title em{font-style:normal;color:#C9A227;}
.v4-fade-up{opacity:0;transform:translateY(24px);}
@media(max-width:576px){
    .v4-hero-title{font-size:1.4rem;letter-spacing:-.3px;}
    .v4-search{flex-direction:column;padding:8px;}
    .v4-search-f{padding:10px 14px;}
    .v4-search-btn{width:100%;justify-content:center;border-radius:9px;}
}

/* Search bar */
.v4-search{
    background:rgba(14,14,14,.85);
    border:1px solid rgba(255,255,255,.1);
    backdrop-filter:blur(20px);border-radius:14px;
    padding:7px;display:flex;max-width:540px;
    transition:border-color .3s,box-shadow .3s;
}
.v4-search:focus-within{
    border-color:rgba(201,162,39,.4);
    box-shadow:0 0 40px rgba(201,162,39,.15);
}
.v4-search-f{flex:1;display:flex;align-items:center;gap:10px;padding:11px 16px;}
.v4-search-f i{color:#C9A227;font-size:.85rem;}
.v4-search-f input{border:none;background:transparent;outline:none;font-size:.9rem;color:#f0f0f0;width:100%;}
.v4-search-f input::placeholder{color:rgba(255,255,255,.28);}
.v4-search-btn{
    background:#C9A227;color:#0a0a0a;border:none;border-radius:9px;
    padding:11px 22px;font-size:.85rem;font-weight:700;flex-shrink:0;
    cursor:none;transition:background .2s;
}
.v4-search-btn:hover{background:#e8c84a;}

/* ══════════════════════════════
   PORTO UTILITY OVERRIDES
══════════════════════════════ */
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

.bk-company-card{
    border-radius:18px;overflow:hidden;background:#141414;
    border:1px solid rgba(255,255,255,.06);display:flex;flex-direction:column;
    transition:transform .38s cubic-bezier(.22,1,.36,1),box-shadow .38s,border-color .38s;
    cursor:pointer;height:100%;
}
.bk-company-card:hover{
    transform:translateY(-10px);border-color:rgba(201,162,39,.45);
    box-shadow:0 28px 60px rgba(0,0,0,.55),0 0 0 1px rgba(201,162,39,.2);
}
.bk-cc-img{height:220px;position:relative;overflow:hidden;background:#1a1a1a;}
.bk-cc-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.22,1,.36,1);}
.bk-company-card:hover .bk-cc-img img{transform:scale(1.08);}
.bk-cc-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.05),rgba(0,0,0,.55));pointer-events:none;}
.bk-cc-badge{position:absolute;top:12px;{{ $isAr?'right':'left' }}:12px;z-index:3;background:rgba(10,10,10,.85);color:#C9A227;font-size:.65rem;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(201,162,39,.3);backdrop-filter:blur(8px);}
.bk-cc-rating{position:absolute;top:12px;{{ $isAr?'left':'right' }}:12px;z-index:3;background:rgba(10,10,10,.85);font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px;border:1px solid rgba(201,162,39,.25);backdrop-filter:blur(8px);display:flex;align-items:center;gap:4px;color:#fff;}
.bk-cc-rating i{color:#C9A227;font-size:.65rem;}
.bk-cc-body{padding:16px;flex:1;display:flex;flex-direction:column;gap:8px;}
.bk-cc-name{font-size:.97rem;font-weight:700;color:#fff;line-height:1.25;}
.bk-cc-location{font-size:.74rem;color:rgba(255,255,255,.4);display:flex;align-items:center;gap:5px;}
.bk-cc-location i{color:#C9A227;font-size:.68rem;}
.bk-cc-chips{display:flex;flex-wrap:wrap;gap:5px;}
.bk-cc-chip{background:rgba(201,162,39,.07);border:1px solid rgba(201,162,39,.18);border-radius:20px;padding:3px 10px;font-size:.65rem;font-weight:600;color:rgba(201,162,39,.9);}
.bk-cc-book{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;padding:11px;border-radius:10px;border:1.5px solid rgba(201,162,39,.35);background:rgba(201,162,39,.05);color:#C9A227;font-size:.83rem;font-weight:700;text-decoration:none;transition:all .28s;margin-top:auto;}
.bk-cc-book:hover,.bk-company-card:hover .bk-cc-book{background:#C9A227;color:#0a0a0a;border-color:#C9A227;box-shadow:0 6px 20px rgba(201,162,39,.35);}

/* Category cards */
.bk-cat-card{position:relative;width:140px;height:180px;border-radius:20px;overflow:hidden;text-decoration:none!important;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;border:1.5px solid rgba(255,255,255,.07);transition:transform .35s cubic-bezier(.22,1,.36,1),box-shadow .35s,border-color .35s;}
.bk-cat-card::before{content:'';position:absolute;inset:0;background:var(--cg,linear-gradient(135deg,#1a1a1a,#111));transition:opacity .35s;}
.bk-cat-card::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.18) 0%,rgba(0,0,0,.65) 100%);}
.bk-cat-card>*{position:relative;z-index:3;}
.bk-cat-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 24px 48px rgba(0,0,0,.6),0 0 0 1.5px rgba(201,162,39,.5);border-color:rgba(201,162,39,.5);}
.bk-cat-card-icon{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;margin-bottom:12px;transition:all .3s;}
.bk-cat-card:hover .bk-cat-card-icon{background:#C9A227;color:#0a0a0a;border-color:#C9A227;transform:scale(1.1);}
.bk-cat-card-name{font-size:.8rem;font-weight:700;color:#fff;text-align:center;line-height:1.3;padding:0 8px;}
.bk-cat-card-count{font-size:.64rem;color:rgba(255,255,255,.55);margin-top:3px;}

/* Section labels */
.v4-eyebrow{display:flex;align-items:center;gap:10px;font-size:.65rem;font-weight:700;letter-spacing:3.5px;text-transform:uppercase;color:#C9A227;margin-bottom:14px;}
.v4-eyebrow::before{content:'';width:28px;height:1px;background:#C9A227;flex-shrink:0;}
.v4-sec-title{font-size:clamp(1.3rem,2.4vw,2.1rem);font-weight:900;line-height:1.1;letter-spacing:-.8px;color:#fff;margin-bottom:14px;}
.v4-sec-title em{font-style:normal;color:#C9A227;}
.v4-sec-sub{font-size:.92rem;color:rgba(255,255,255,.52);line-height:1.75;max-width:500px;}

/* Magnetic wrapper */
.v4-mag{display:inline-block;position:relative;}

/* Marquee strip */
#v4-strip{background:#111;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);padding:16px 0;overflow:hidden;}
.v4-strip-track{display:flex;width:max-content;animation:v4mq 24s linear infinite;}
@if($isAr).v4-strip-track{animation-direction:reverse;}@endif
.v4-strip-item{display:flex;align-items:center;gap:10px;padding:0 32px;white-space:nowrap;font-size:.66rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.25);}
.v4-strip-dot{width:4px;height:4px;border-radius:50%;background:#C9A227;flex-shrink:0;}
.v4-strip-icon{
    width:22px;height:22px;border-radius:50%;
    background:rgba(201,162,39,.12);border:1px solid rgba(201,162,39,.2);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.v4-strip-icon i{font-size:.55rem;color:#C9A227;}
@keyframes v4mq{from{transform:translateX(0);}to{transform:translateX(-50%);}}
@keyframes hsp{0%,100%{opacity:.3}50%{opacity:1}}

/* ══════════════════════════════
   CIRCULAR SECTION
══════════════════════════════ */
#v4-circle{background:#111;padding:130px 0;position:relative;overflow:hidden;}
#v4-circle::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:800px;height:800px;border-radius:50%;background:radial-gradient(circle,rgba(201,162,39,.045) 0%,transparent 65%);pointer-events:none;}
.v4-circle-wrap{display:flex;align-items:center;justify-content:center;gap:90px;flex-wrap:wrap;}
.v4-circle-img-outer{position:relative;flex-shrink:0;width:400px;height:400px;}
.v4-circle-ring{position:absolute;inset:-22px;border-radius:50%;border:1px solid rgba(201,162,39,.16);animation:v4spin 18s linear infinite;}
.v4-circle-ring::before{content:'';position:absolute;top:-5px;left:50%;transform:translateX(-50%);width:10px;height:10px;border-radius:50%;background:#C9A227;box-shadow:0 0 16px #C9A227;}
.v4-circle-ring-2{position:absolute;inset:-46px;border-radius:50%;border:1px dashed rgba(201,162,39,.08);animation:v4spin 30s linear infinite reverse;}
@keyframes v4spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
.v4-circle-img{width:400px;height:400px;border-radius:50%;overflow:hidden;border:2px solid rgba(201,162,39,.2);box-shadow:0 0 70px rgba(201,162,39,.1),0 28px 70px rgba(0,0,0,.55);position:relative;z-index:1;}
.v4-circle-img img{width:100%;height:100%;object-fit:cover;transform:scale(1.05);transition:transform 8s linear;}
.v4-circle-img:hover img{transform:scale(1.13);}
.v4-circle-tag{position:absolute;z-index:2;background:rgba(14,14,14,.92);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:13px 17px;backdrop-filter:blur(12px);box-shadow:0 14px 36px rgba(0,0,0,.5);white-space:nowrap;}
.v4-ct-1{top:18px;{{ $isAr?'left':'right' }}:-36px;}
.v4-ct-2{bottom:56px;{{ $isAr?'right':'left' }}:-36px;}
.v4-ct-3{top:50%;{{ $isAr?'left':'right' }}:-56px;transform:translateY(-50%);}
.v4-ct-icon{width:34px;height:34px;border-radius:9px;background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.2);display:flex;align-items:center;justify-content:center;margin-bottom:7px;}
.v4-ct-icon i{color:#C9A227;font-size:.82rem;}
.v4-ct-val{font-size:1.05rem;font-weight:800;color:#fff;line-height:1;}
.v4-ct-lbl{font-size:.65rem;color:rgba(255,255,255,.45);margin-top:2px;}
.v4-circle-text{flex:1;min-width:280px;max-width:460px;}

/* ══════════════════════════════
   PINNED SCROLL SECTIONS
   (GSAP pin:true — most reliable with Porto)
══════════════════════════════ */
/* Prevent Porto overflow:hidden from breaking GSAP pin */
#v4-phone-outer, #v4-ow-outer{ overflow:visible !important; }
#v4-phone-outer{ background:#0a0a0a; }
#v4-ow-outer   { background:#111; }

#v4-phone-sticky,#v4-ow-sticky{
    height:100vh; overflow:hidden;
    display:flex; align-items:center; justify-content:center;
    gap:60px; padding:0 60px; width:100%; box-sizing:border-box;
    will-change:transform;
}
/* Phone: image RIGHT | Owners: image LEFT */
#v4-phone-sticky{ flex-direction:{{ $isAr ? 'row-reverse' : 'row' }}; }
#v4-ow-sticky   { flex-direction:{{ $isAr ? 'row' : 'row-reverse' }}; }

/* Steps */
#v4-psteps-wrap,#v4-owsteps-wrap{
    flex:1; max-width:420px; position:relative; min-height:300px;
}
.v4-pstep,.v4-owstep{
    position:absolute; top:50%; left:0; right:0;
    transform:translateY(-50%);
    opacity:0; transition:opacity .5s ease; pointer-events:none;
}
.v4-pstep.active,.v4-owstep.active{ opacity:1; pointer-events:auto; }

.v4-pl-eyebrow{
    font-size:.6rem; font-weight:700; letter-spacing:3px;
    text-transform:uppercase; color:#C9A227;
    margin-bottom:14px; display:flex; align-items:center; gap:8px;
}
.v4-pl-eyebrow::before{ content:''; width:20px; height:1px; background:#C9A227; flex-shrink:0; }
.v4-pl-title{
    font-size:clamp(1.3rem,2.4vw,2.1rem); font-weight:900;
    line-height:1.05; letter-spacing:-1.2px; color:#fff; margin-bottom:12px;
}
.v4-pl-title em{ font-style:normal; color:#C9A227; }
.v4-pl-desc{ font-size:.88rem; color:rgba(255,255,255,.5); line-height:1.75; max-width:340px; }
.v4-pl-feats{ display:flex; flex-direction:column; gap:9px; margin-top:18px; }
.v4-pl-feat{ display:flex; align-items:center; gap:9px; font-size:.8rem; color:rgba(255,255,255,.45); }
.v4-pl-feat i{ color:#C9A227; font-size:.7rem; }

/* Visual (phone / card) */
#v4-phone-cw,#v4-ow-cw{
    display:flex; flex-direction:column; align-items:center; gap:14px; flex-shrink:0;
}
#v4-phone-heading,#v4-ow-heading{ text-align:center; }
.v4-phone-screen{ transform-origin:center center; }

/* Dots */
.v4-psdots{ display:flex; gap:8px; justify-content:center; margin-top:16px; }
.v4-psdot{ width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,.2); transition:all .35s; }
.v4-psdot.active{ width:22px; border-radius:4px; background:#C9A227; }

/* Mobile */
@media(max-width:768px){
    #v4-phone-sticky,#v4-ow-sticky{
        flex-direction:column !important;
        gap:24px; padding:36px 20px; height:auto;
        align-items:center;
    }
    #v4-psteps-wrap,#v4-owsteps-wrap{ min-height:auto; width:100%; max-width:100%; }
    .v4-pstep,.v4-owstep{ position:relative; top:auto; transform:none; display:none; }
    .v4-pstep.active,.v4-owstep.active{ display:block; }
}

/* ══════════════════════════════
   WALASHI-STYLE PINNED SECTIONS
   نص يتصفح + صورة sticky
══════════════════════════════ */
.v4-pin-sec  { background:#0a0a0a; }
.v4-pin-sec-b{ background:#111; }

/* Layout: flex row, full width */
.v4-pin-layout{
    display:flex;
    flex-direction:row;
    align-items:flex-start;
    max-width:1100px;
    margin:0 auto;
    padding:0 32px;
    gap:20px;
}

/* ── Scrolling text column ── */
.v4-pin-steps{
    flex:1;
    padding:8vh 0 12vh;
}

/* Each step */
.v4-pin-step{
    min-height:82vh;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding:32px 40px 32px 0;
    opacity:.15;
    transition:opacity .55s ease;
}
.v4-pin-step.active{ opacity:1; }
{{ $isAr ? '.v4-pin-step{padding:32px 0 32px 40px;}' : '' }}

/* step eyebrow */
.v4-pin-eyebrow{
    font-size:.6rem;font-weight:700;letter-spacing:3px;
    text-transform:uppercase;color:#C9A227;
    margin-bottom:14px;
    display:flex;align-items:center;gap:8px;
}
.v4-pin-eyebrow::before{content:'';width:20px;height:1px;background:#C9A227;flex-shrink:0;}

/* step title — big and bold */
.v4-pin-h{
    font-size:clamp(1.5rem,3vw,2.4rem);
    font-weight:900;line-height:1.08;
    letter-spacing:-1.2px;color:#fff;
    margin-bottom:14px;
}
.v4-pin-h em{font-style:normal;color:#C9A227;}

.v4-pin-desc{font-size:.9rem;color:rgba(255,255,255,.5);line-height:1.75;max-width:360px;}

.v4-pin-feats{display:flex;flex-direction:column;gap:10px;margin-top:22px;}
.v4-pin-feat{display:flex;align-items:center;gap:9px;font-size:.82rem;color:rgba(255,255,255,.45);}
.v4-pin-feat i{color:#C9A227;font-size:.7rem;}
.v4-pin-step.active .v4-pin-feat{color:rgba(255,255,255,.65);}

/* ── Sticky visual column ── */
.v4-pin-visual{
    width:300px;
    flex-shrink:0;
    align-self:flex-start;
    position:sticky;
    top:0;
    height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:18px;
}

/* Visual heading (above phone/card) */
.v4-pin-vhd{text-align:center;}
.v4-pin-vhd .v4-eyebrow{justify-content:center;margin-bottom:6px;}
.v4-pin-vhd .v4-sec-title{margin:0;font-size:clamp(1rem,1.8vw,1.5rem);}

/* ── Phone frame (keep existing styles but tweak width) ── */
#v4-phone-mockup{width:230px;flex-shrink:0;position:relative;z-index:2;}

/* iPhone frame */
#v4-phone-mockup{width:260px;flex-shrink:0;position:relative;z-index:2;will-change:transform;}
.v4-phone-frame{background:#111;border-radius:42px;padding:13px;box-shadow:0 0 0 2px rgba(255,255,255,.07),0 55px 110px rgba(0,0,0,.8),0 0 55px rgba(201,162,39,.07);position:relative;}
.v4-phone-notch{width:90px;height:28px;background:#111;border-radius:0 0 18px 18px;position:absolute;top:13px;left:50%;transform:translateX(-50%);z-index:10;}
.v4-phone-screen{border-radius:30px;overflow:hidden;position:relative;background:#0d0d0d;aspect-ratio:9/19.5;}
.v4-pslide{position:absolute;inset:0;opacity:0;transition:opacity .4s;}
.v4-pslide.active{opacity:1;}
/* App screen 1 – Home */
.v4-ps-hd{background:#111;padding:44px 14px 14px;border-bottom:1px solid rgba(255,255,255,.05);}
.v4-ps-logo{font-size:1rem;font-weight:900;color:#fff;margin-bottom:1px;}
.v4-ps-logo em{font-style:normal;color:#C9A227;}
.v4-ps-tag{font-size:.6rem;color:rgba(255,255,255,.3);}
.v4-ps-search{background:#1a1a1a;border:1px solid rgba(255,255,255,.07);border-radius:9px;padding:9px 13px;margin:11px 14px;display:flex;align-items:center;gap:7px;font-size:.68rem;color:rgba(255,255,255,.3);}
.v4-ps-search i{color:#C9A227;font-size:.7rem;}
.v4-ps-cats{display:flex;gap:7px;padding:0 14px 10px;overflow-x:auto;}
.v4-ps-cat{background:#1a1a1a;border:1px solid rgba(255,255,255,.06);border-radius:18px;padding:5px 11px;white-space:nowrap;font-size:.62rem;color:rgba(255,255,255,.5);}
.v4-ps-cat.on{background:rgba(201,162,39,.1);border-color:rgba(201,162,39,.25);color:#C9A227;}
.v4-ps-items{padding:10px 14px;display:flex;flex-direction:column;gap:9px;}
.v4-ps-item{background:#1a1a1a;border-radius:11px;overflow:hidden;display:flex;gap:9px;align-items:center;padding:9px;}
.v4-ps-item-img{width:48px;height:48px;border-radius:9px;object-fit:cover;flex-shrink:0;}
.v4-ps-item-name{font-size:.68rem;font-weight:700;color:#fff;margin-bottom:2px;}
.v4-ps-item-cat{font-size:.58rem;color:rgba(255,255,255,.35);}
.v4-ps-item-rate{font-size:.58rem;color:#C9A227;}
/* Screen 2 – Book */
.v4-ps2-top{background:linear-gradient(180deg,#111,#0d0d0d);padding:44px 14px 16px;}
.v4-ps2-title{font-size:.9rem;font-weight:800;color:#fff;margin-bottom:3px;}
.v4-ps2-sub{font-size:.6rem;color:rgba(255,255,255,.3);}
.v4-ps2-img{width:100%;height:100px;object-fit:cover;border-radius:11px;margin:10px 0;}
.v4-ps2-slots{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;padding:0 14px;}
.v4-ps2-slot{background:#1a1a1a;border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:7px 4px;text-align:center;font-size:.58rem;color:rgba(255,255,255,.45);}
.v4-ps2-slot.on{background:rgba(201,162,39,.1);border-color:rgba(201,162,39,.25);color:#C9A227;font-weight:700;}
.v4-ps2-btn{margin:13px 14px 0;background:#C9A227;color:#0a0a0a;border:none;border-radius:11px;padding:13px;font-size:.74rem;font-weight:800;width:calc(100% - 28px);text-align:center;}
/* Screen 3 – Profile */
.v4-ps3-top{background:linear-gradient(180deg,rgba(201,162,39,.12),transparent);padding:44px 14px 20px;text-align:center;}
.v4-ps3-av{width:64px;height:64px;border-radius:50%;background:rgba(201,162,39,.1);border:2px solid rgba(201,162,39,.25);margin:0 auto 9px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:900;color:#C9A227;}
.v4-ps3-name{font-size:.9rem;font-weight:800;color:#fff;}
.v4-ps3-appts{display:flex;flex-direction:column;gap:9px;padding:14px;}
.v4-ps3-appt{background:#1a1a1a;border:1px solid rgba(255,255,255,.06);border-radius:11px;padding:11px;display:flex;justify-content:space-between;align-items:center;}
.v4-ps3-appt-name{font-size:.68rem;font-weight:700;color:#fff;margin-bottom:2px;}
.v4-ps3-appt-date{font-size:.58rem;color:rgba(255,255,255,.3);}
.v4-ps3-badge{background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.22);color:#C9A227;font-size:.56rem;font-weight:700;padding:4px 8px;border-radius:18px;}

/* ══════════════════════════════
   REVIEWS — walashi scattered layout
══════════════════════════════ */
#v4-reviews-outer{
    background:#080808; padding:110px 0 130px; position:relative; overflow:hidden;
}
/* big decorative rotating ring behind cards */
#v4-rv-ring{
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
    width:700px; height:700px; border-radius:50%;
    border:1px solid rgba(201,162,39,.07);
    pointer-events:none; will-change:transform;
}
#v4-rv-ring::before{
    content:''; position:absolute; inset:60px; border-radius:50%;
    border:1px solid rgba(201,162,39,.05);
}
#v4-rv-ring::after{
    content:''; position:absolute; inset:130px; border-radius:50%;
    border:1px solid rgba(201,162,39,.04);
}
/* heading */
.v4-rv-heading{ text-align:center; margin-bottom:60px; }

/* scattered grid */
#v4-rv-grid{
    display:grid;
    grid-template-columns: repeat(3,1fr);
    grid-template-rows: auto auto;
    gap:22px;
    max-width:1100px; margin:0 auto; padding:0 40px;
    position:relative; z-index:2;
}
/* CTA card in center col */
.v4-rv-cta-cell{
    display:flex; align-items:center; justify-content:center;
}
.v4-rv-cta{
    text-align:center; padding:32px 24px;
    background:rgba(201,162,39,.06);
    border:1px solid rgba(201,162,39,.18);
    border-radius:24px;
    display:flex; flex-direction:column; align-items:center; gap:14px;
}
.v4-rv-cta-dot{
    width:14px; height:14px; border-radius:50%;
    background:#C9A227; display:inline-block;
    box-shadow:0 0 0 6px rgba(201,162,39,.15), 0 0 0 12px rgba(201,162,39,.06);
    animation:v4ctapulse 2s ease infinite;
}
@keyframes v4ctapulse{
    0%,100%{ box-shadow:0 0 0 6px rgba(201,162,39,.15), 0 0 0 12px rgba(201,162,39,.06); }
    50%{     box-shadow:0 0 0 10px rgba(201,162,39,.22), 0 0 0 20px rgba(201,162,39,.06); }
}
.v4-rv-cta-text{
    font-size:clamp(.95rem,1.5vw,1.1rem); font-weight:800;
    color:#fff; line-height:1.35; max-width:180px;
}
.v4-rv-cta-text em{ font-style:normal; color:#C9A227; }
.v4-rv-cta-sub{
    font-size:.76rem; color:rgba(255,255,255,.35); max-width:160px; line-height:1.5;
}

/* review cards */
.v4-rv{
    background:rgba(255,255,255,.025);
    border:1px solid rgba(255,255,255,.07);
    border-radius:22px; padding:24px;
    transition:border-color .3s, transform .3s;
    will-change:transform,opacity;
}
.v4-rv:hover{ border-color:rgba(201,162,39,.22); transform:translateY(-4px); }
/* offset rows for scattered look */
.v4-rv:nth-child(1){ margin-top:40px; }
.v4-rv:nth-child(3){ margin-top:-20px; }
.v4-rv:nth-child(4){ margin-top:-10px; }
.v4-rv:nth-child(6){ margin-top:30px; }

.v4-rv-stars{ display:flex; gap:3px; margin-bottom:10px; }
.v4-rv-stars i{ color:#C9A227; font-size:.7rem; }
.v4-rv-q{
    font-size:.84rem; color:rgba(255,255,255,.52); line-height:1.7;
    margin-bottom:16px; padding-inline-start:14px; position:relative;
}
.v4-rv-q::before{
    content:'"'; position:absolute; {{ $isAr?'right':'left' }}:0; top:-4px;
    font-size:2.2rem; color:rgba(201,162,39,.14); line-height:1;
}
.v4-rv-au{ display:flex; align-items:center; gap:10px; }
.v4-rv-av{
    width:38px; height:38px; border-radius:50%;
    background:rgba(201,162,39,.1); border:1.5px solid rgba(201,162,39,.22);
    display:flex; align-items:center; justify-content:center;
    font-size:.95rem; font-weight:800; color:#C9A227; flex-shrink:0;
}
.v4-rv-name{ font-size:.82rem; font-weight:700; color:#fff; }
.v4-rv-role{ font-size:.66rem; color:rgba(255,255,255,.3); margin-top:1px; }

@media(max-width:900px){
    #v4-rv-grid{ grid-template-columns:repeat(2,1fr); gap:16px; padding:0 20px; }
    .v4-rv:nth-child(n){ margin-top:0; }
}
@media(max-width:576px){
    #v4-rv-grid{ grid-template-columns:1fr; }
    .v4-rv-cta-cell{ order:-1; }
}

/* ══════════════════════════════
   PARTNERS — sticky scroll
══════════════════════════════ */
#v4-partners-outer{height:190vh;position:relative;background:#111;}
#v4-partners-sticky{
    position:sticky;top:0;height:100vh;
    display:flex;align-items:center;overflow:hidden;
}
.v4-partners-inner{width:100%;}
.v4-partners-hd{text-align:center;margin-bottom:56px;}
.v4-prow{display:flex;overflow:hidden;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);padding:18px 0;margin-bottom:14px;}
.v4-prow-track{display:flex;width:max-content;animation:v4mq 22s linear infinite;}
.v4-prow-track-rev{display:flex;width:max-content;animation:v4mq 28s linear infinite reverse;}
.v4-prow:last-child{border-top:none;}
.v4-partner-item{display:flex;align-items:center;justify-content:center;padding:18px 50px;border-inline-end:1px solid rgba(255,255,255,.05);}
.v4-partner-name{font-size:.88rem;font-weight:700;color:rgba(255,255,255,.2);letter-spacing:1px;white-space:nowrap;transition:color .3s;}
.v4-partner-item:hover .v4-partner-name{color:#C9A227;}

/* ══════════════════════════════
   STATS
══════════════════════════════ */
#v4-stats{background:#0a0a0a;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);padding:70px 0;}
.v4-stats-row{display:grid;grid-template-columns:repeat(4,1fr);}
.v4-stat{text-align:center;padding:30px 20px;border-inline-end:1px solid rgba(255,255,255,.06);}
.v4-stat:last-child{border-inline-end:none;}
.v4-stat-num{font-size:clamp(1.6rem,2.8vw,2.4rem);font-weight:900;color:#C9A227;line-height:1;letter-spacing:-2px;margin-bottom:7px;}
.v4-stat-lbl{font-size:.78rem;color:rgba(255,255,255,.45);}
@media(max-width:576px){.v4-stats-row{grid-template-columns:repeat(2,1fr);}.v4-stat:nth-child(2){border-inline-end:none;}}

/* ══════════════════════════════
   CTA
══════════════════════════════ */
/* ══ FLOATING SHAPES ══ */
.v4-shape{
    position:absolute; pointer-events:none; will-change:transform;
    opacity:0; /* GSAP fades in */
}
.v4-shape-circle{
    border-radius:50%;
    border:1px solid rgba(201,162,39,.35);
    background:radial-gradient(circle at 30% 30%, rgba(201,162,39,.08), transparent 70%);
}
.v4-shape-ring{
    border-radius:50%;
    border:1px solid rgba(201,162,39,.2);
    background:transparent;
}
.v4-shape-tri{
    width:0; height:0;
    background:transparent !important;
    border:none !important;
}
.v4-shape-dot{
    border-radius:50%;
    background:rgba(201,162,39,.5);
    border:none;
}
.v4-shape-sq{
    border:1px solid rgba(255,255,255,.08);
    background:rgba(255,255,255,.02);
    transform-origin:center;
}
/* Each section needs position:relative for absolute children */
#v4-hero{ position:relative; }
#v4-phone-outer,#v4-ow-outer{ position:relative; }
#v4-cta{ position:relative; }

#v4-cta{background:#0a0a0a;padding:130px 0;position:relative;overflow:hidden;}
#v4-cta::before{content:'';position:absolute;top:-160px;left:50%;transform:translateX(-50%);width:650px;height:650px;border-radius:50%;background:radial-gradient(circle,rgba(201,162,39,.06) 0%,transparent 65%);pointer-events:none;}
.v4-cta-title{font-size:clamp(1.4rem,2.8vw,2.4rem);font-weight:900;line-height:1.05;letter-spacing:-2px;color:#fff;margin-bottom:20px;}
.v4-cta-title em{font-style:normal;color:#C9A227;}
.btn-v4-gold{background:#C9A227;color:#0a0a0a;padding:15px 38px;border-radius:10px;font-size:.92rem;font-weight:800;border:none;cursor:none;display:inline-flex;align-items:center;gap:9px;transition:all .2s;text-decoration:none;}
.btn-v4-gold:hover{background:#e8c84a;transform:translateY(-3px);color:#0a0a0a;}
.btn-v4-out{border:1.5px solid rgba(201,162,39,.3);color:#C9A227;padding:15px 38px;border-radius:10px;font-size:.92rem;font-weight:700;background:transparent;display:inline-flex;align-items:center;gap:9px;transition:all .2s;text-decoration:none;}
.btn-v4-out:hover{background:rgba(201,162,39,.08);border-color:#C9A227;transform:translateY(-3px);color:#C9A227;}

/* ══════════════════════════════
   DASHBOARD CARD (owners visual)
══════════════════════════════ */
.v4-dash{background:#0a0a0a;border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:22px;box-shadow:0 30px 80px rgba(0,0,0,.7);width:290px;position:relative;}
.v4-dash-slide{position:absolute;inset:0;padding:22px;opacity:0;transition:opacity .5s ease;border-radius:20px;background:#0a0a0a;}
.v4-dash-slide.active{opacity:1;position:relative;inset:auto;padding:0;}
.v4-dash-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.v4-dash-title{font-size:.88rem;font-weight:800;color:#fff;}
.v4-dash-sub{font-size:.6rem;color:rgba(255,255,255,.3);margin-top:2px;}
.v4-dash-badge{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.18);color:#4ade80;font-size:.56rem;font-weight:700;padding:3px 9px;border-radius:20px;display:flex;align-items:center;gap:4px;}
.v4-dash-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:#4ade80;animation:v4pulse 1.4s ease infinite;}
.v4-dash-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:18px;}
.v4-dash-stat{background:#1a1a1a;border-radius:10px;padding:11px 8px;text-align:center;border:1px solid rgba(255,255,255,.04);}
.v4-dash-stat-n{font-size:.9rem;font-weight:900;color:#C9A227;margin-bottom:3px;}
.v4-dash-stat-l{font-size:.52rem;color:rgba(255,255,255,.32);line-height:1.3;}
.v4-dash-sec-title{font-size:.58rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.28);margin-bottom:10px;}
.v4-dash-branches{display:flex;flex-direction:column;gap:10px;margin-bottom:18px;}
.v4-dash-branch{display:flex;align-items:center;gap:10px;}
.v4-dash-branch-dot{width:6px;height:6px;border-radius:50%;background:#C9A227;flex-shrink:0;}
.v4-dash-branch-name{font-size:.72rem;font-weight:700;color:#fff;margin-bottom:1px;}
.v4-dash-branch-sub{font-size:.58rem;color:rgba(255,255,255,.28);}
.v4-dash-branch-bar{height:3px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:4px;overflow:hidden;}
.v4-dash-branch-fill{height:100%;background:linear-gradient(90deg,#C9A227,rgba(201,162,39,.2));border-radius:2px;}
.v4-dash-branch-pct{font-size:.66rem;font-weight:700;color:#C9A227;flex-shrink:0;}
.v4-dash-chart-wrap{border-top:1px solid rgba(255,255,255,.05);padding-top:12px;}
.v4-dash-chart-lbl{font-size:.58rem;color:rgba(255,255,255,.28);margin-bottom:8px;}
.v4-dash-bars{display:flex;align-items:flex-end;gap:4px;height:48px;}
.v4-dash-bar-col{flex:1;height:100%;display:flex;align-items:flex-end;}
.v4-dash-bar{width:100%;background:linear-gradient(180deg,rgba(201,162,39,.6),rgba(201,162,39,.05));border-radius:3px 3px 0 0;min-height:3px;}
@keyframes v4pulse{0%,100%{opacity:1;}50%{opacity:.3;}}

/* ══════════════════════════════
   MOBILE
══════════════════════════════ */
@media(max-width:1024px){
    .v4-circle-wrap{flex-direction:column;gap:50px;}
    .v4-circle-img-outer,.v4-circle-img{width:300px;height:300px;}
    .v4-pin-visual{width:260px;}
    .v4-dash{width:260px;}
}
@media(max-width:768px){
    /* ── Pin sections: visual sticky top, steps scroll below ── */
    .v4-pin-layout{
        flex-direction:column;
        padding:0 20px;
        gap:0;
    }
    /* Phone: steps are first in HTML → reverse to put visual on top */
    #v4-phone-sec .v4-pin-layout{ flex-direction:column-reverse; }
    /* Owners: visual is first in HTML → column keeps visual on top */
    #v4-ow-sec .v4-pin-layout{ flex-direction:column; }

    .v4-pin-visual{
        width:100%;
        position:sticky;
        top:0;
        height:auto;
        padding:28px 0 16px;
        z-index:10;
        justify-content:center;
        gap:12px;
    }
    /* give visual a bg so it covers text while sticky */
    #v4-phone-sec .v4-pin-visual{ background:#0a0a0a; }
    #v4-ow-sec    .v4-pin-visual{ background:#111; }

    #v4-phone-mockup{ width:180px; }
    .v4-dash{ width:260px; }
    .v4-pin-vhd .v4-sec-title{ font-size:1rem; }
    .v4-pin-vhd .v4-eyebrow{ font-size:.52rem; }

    .v4-pin-steps{ padding:0 0 8vh; }
    .v4-pin-step{
        min-height:55vh;
        padding:24px 8px;
        opacity:.18;
    }
    .v4-pin-step.active{ opacity:1; }
    .v4-pin-h{ font-size:1.3rem; }
    .v4-pin-desc{ font-size:.82rem; }

    #v4-partners-outer{height:auto;}
    #v4-partners-sticky{position:relative;height:auto;padding:80px 0;}
    .v4-stats-row{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body>
<div class="body">

{{-- ══ CURSOR ══ --}}
<div id="v4-dot"></div>
<div id="v4-ring"></div>

{{-- ══ LOADER ══ --}}
<div id="v4-loader">
    <div class="v4-ld-welcome" id="ldWelcome">{{ $isAr ? 'مرحباً بك في' : 'welcome to' }}</div>
    <div class="v4-ld-brand"><span class="v4-ld-brand-inner" id="ldBrand">{{ $isAr ? 'بوكسي' : 'Book' }}<em>{{ $isAr ? '®' : 'sy' }}</em></span></div>
    <div class="v4-ld-track">
        <div class="v4-ld-sweep" id="ldSweep"></div>
        <div class="v4-ld-fill"  id="ldFill"></div>
    </div>
    <div class="v4-ld-pct" id="ldPct">0%</div>
    <div class="v4-ld-wipe" id="ldWipe"></div>
</div>

{{-- ══ NAVBAR ══ --}}
<nav id="v4-nav" class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a href="{{ route('front.index4') }}" class="navbar-brand" style="font-family:'Poppins',sans-serif!important;font-size:1.7rem;font-weight:900;color:#fff;letter-spacing:-1px;text-decoration:none;">
            {{ $isAr ? 'بوكسي' : 'Booksy' }}<span style="color:#C9A227;">.</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#v4NavMenu" style="border:1px solid rgba(201,162,39,.35);color:#C9A227;">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="v4NavMenu">
            <ul class="navbar-nav mx-auto gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="#v4-cats">{{ $isAr ? 'الفئات' : 'Categories' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="#v4-featured">{{ $isAr ? 'الأماكن' : 'Places' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل' : 'Contact' }}</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                @if($isAr)
                    <a href="{{ route('locale.switch','en') }}" class="bk-lang">EN</a>
                @else
                    <a href="{{ route('locale.switch','ar') }}" class="bk-lang">عربي</a>
                @endif
                <div class="v4-mag">
                    <a href="{{ route('front.index4') }}" class="bk-register-btn">
                        <i class="fas fa-calendar-check"></i>
                        {{ $isAr ? 'احجز الآن' : 'Book Now' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div role="main" class="main">

{{-- ══ HERO ══ --}}
<section id="v4-hero">
    {{-- Floating shapes: Hero --}}
    <div class="v4-shape v4-shape-circle" id="sh-h1" style="width:140px;height:140px;top:12%;left:6%;"></div>
    <div class="v4-shape v4-shape-ring"   id="sh-h2" style="width:260px;height:260px;top:55%;left:2%;"></div>
    <div class="v4-shape v4-shape-ring"   id="sh-h3" style="width:80px;height:80px;top:20%;right:8%;"></div>
    <div class="v4-shape v4-shape-circle" id="sh-h4" style="width:50px;height:50px;top:70%;right:12%;"></div>
    <div class="v4-shape v4-shape-sq"     id="sh-h5" style="width:60px;height:60px;top:38%;left:14%;transform:rotate(22deg);"></div>
    <div class="v4-shape v4-shape-sq"     id="sh-h6" style="width:90px;height:90px;top:30%;right:18%;transform:rotate(-15deg);"></div>
    <div class="v4-shape v4-shape-dot"    id="sh-h7" style="width:8px;height:8px;top:25%;left:30%;"></div>
    <div class="v4-shape v4-shape-dot"    id="sh-h8" style="width:5px;height:5px;top:65%;right:25%;"></div>
    <div class="v4-hero-video">
        <video autoplay muted loop playsinline>
            <source src="https://booksy-public.s3.amazonaws.com/horizontal_.webm" type="video/webm">
        </video>
    </div>
    <div class="v4-hero-ov"></div>
    <div class="v4-hero-content container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7 col-md-9 col-12">
                <div class="v4-hero-eyebrow justify-content-center" id="hEye">{{ $isAr ? 'منصة الحجز الذكي' : 'Smart Booking Platform' }}</div>
                <h1 class="v4-hero-title">
                    @if($isAr)
                        <span class="v4-split-line"><span class="v4-split-inner">احجز تجربتك</span></span>
                        <span class="v4-split-line"><span class="v4-split-inner">الجمالية <em>بكل سهولة</em></span></span>
                    @else
                        <span class="v4-split-line"><span class="v4-split-inner">Book Your Next</span></span>
                        <span class="v4-split-line"><span class="v4-split-inner"><em>Beauty</em> Experience</span></span>
                    @endif
                </h1>
                <form class="v4-search v4-fade-up mx-auto" id="hSearch" action="{{ route('front.index4') }}" method="GET">
                    <div class="v4-search-f">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'صالون، سبا، عيادة...' : 'Search salon, spa, clinic...' }}">
                    </div>
                    <button class="v4-search-btn" type="submit">
                        <i class="fas fa-arrow-{{ $isAr ? 'left' : 'right' }}"></i>
                        {{ $isAr ? 'ابحث' : 'Search' }}
                    </button>
                </form>
                <div class="d-flex flex-wrap gap-2 mt-4 v4-fade-up justify-content-center" id="hChips">
                    @php
                        $chips = $isAr
                            ? [['500+','صالون وعيادة'],['12K+','حجز ناجح'],['98%','رضا العملاء']]
                            : [['500+','Salons & Clinics'],['12K+','Bookings Made'],['98%','Happy Clients']];
                    @endphp
                    @foreach($chips as $c)
                        <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:30px;padding:6px 18px;display:flex;align-items:center;gap:7px;">
                            <span style="font-size:.88rem;font-weight:800;color:#C9A227;">{{ $c[0] }}</span>
                            <span style="font-size:.7rem;color:rgba(255,255,255,.4);">{{ $c[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div style="position:absolute;bottom:36px;{{ $isAr?'right':'left' }}:44px;z-index:3;display:flex;align-items:center;gap:12px;opacity:0;" id="hScroll">
        <div style="width:46px;height:1px;background:linear-gradient({{ $isAr?'left':'right' }},#C9A227,transparent);animation:hsp 2.5s ease-in-out infinite;"></div>
        <span style="font-size:.6rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.3);">{{ $isAr?'تصفح':'Scroll' }}</span>
    </div>
</section>

{{-- ══ STRIP — من قاعدة البيانات ══ --}}
<div id="v4-strip">
    <div class="v4-strip-track">
        @php
            /* كرر 3 مرات لضمان حركة لا نهائية */
            $stripCats = $categories->count() ? $categories : collect();
            $stripReps = $stripCats->count() < 4 ? 6 : 3;
        @endphp
        @for($r = 0; $r < $stripReps; $r++)
            @foreach($stripCats as $sc)
                @php
                    $scIco  = $sc->icon ?? null;
                    $scName = $isAr ? ($sc->name_ar ?? $sc->name_en) : ($sc->name_en ?? $sc->name_ar);
                @endphp
                <a href="{{ route('front.category', $sc->slug) }}" class="v4-strip-item">
                    <div class="v4-strip-item">
                        {{-- @if($scIco)
                            <span class="v4-strip-icon">
                                <img src="{{ asset('storage/'.$scIco) }}" alt="{{ $scName }}" style="width:14px;height:14px;object-fit:contain;filter:brightness(0) invert(1) sepia(1) saturate(3) hue-rotate(5deg);">
                            </span>
                        @endif --}}
                        {{ $scName }}
                    </div>
                </a>
            @endforeach
        @endfor
    </div>
</div>

{{-- ══ CIRCULAR SECTION ══ --}}
<section id="v4-circle">
    <div class="container">
        <div class="v4-circle-wrap">
            <div class="v4-circle-img-outer" id="v4CircleOuter">
                <div class="v4-circle-ring"></div>
                <div class="v4-circle-ring-2"></div>
                <div class="v4-circle-img">
                    <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=900&q=80" alt="salon">
                </div>
                <div class="v4-circle-tag v4-ct-1 appear-animation" data-appear-animation="fadeInRight">
                    <div class="v4-ct-icon"><i class="fas fa-star"></i></div>
                    <div class="v4-ct-val">4.9</div>
                    <div class="v4-ct-lbl">{{ $isAr ? 'متوسط التقييم' : 'Avg Rating' }}</div>
                </div>
                <div class="v4-circle-tag v4-ct-2 appear-animation" data-appear-animation="fadeInLeft" data-appear-animation-delay="150">
                    <div class="v4-ct-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="v4-ct-val">12K+</div>
                    <div class="v4-ct-lbl">{{ $isAr ? 'حجز ناجح' : 'Bookings' }}</div>
                </div>
                <div class="v4-circle-tag v4-ct-3 appear-animation" data-appear-animation="fadeInRight" data-appear-animation-delay="300">
                    <div class="v4-ct-icon"><i class="fas fa-store"></i></div>
                    <div class="v4-ct-val">500+</div>
                    <div class="v4-ct-lbl">{{ $isAr ? 'مكان' : 'Places' }}</div>
                </div>
            </div>

            <div class="v4-circle-text">
                <div class="v4-eyebrow appear-animation" data-appear-animation="fadeInUpShorter">{{ $isAr ? 'من نحن' : 'About Us' }}</div>
                <h2 class="v4-sec-title">
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'بوكسي — منصة' : 'Booksy —' }}</span></span>
                    <span class="v4-split-line"><span class="v4-split-inner"><em>{{ $isAr ? 'الحجز الذكي' : 'Smart Booking' }}</em></span></span>
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'للجمال والراحة' : 'For Beauty & Care' }}</span></span>
                </h2>
                <p class="v4-sec-sub appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="200">
                    {{ $isAr
                        ? 'بوكسي منصة متكاملة تربطك بأفضل صالونات التجميل والسبا والعيادات في مدينتك. احجز موعدك في ثوانٍ واستمتع بتجربة لا مثيل لها.'
                        : 'Booksy is a complete platform connecting you with the finest beauty salons, spas and clinics. Book your appointment in seconds.'
                    }}
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    @foreach(($isAr ? ['حجز فوري','بدون انتظار','تقييمات موثوقة','دعم 24/7'] : ['Instant Booking','No Waiting','Verified Reviews','24/7 Support']) as $tag)
                        <span class="appear-animation bk-cc-chip" data-appear-animation="fadeInUpShorter" style="font-size:.78rem;padding:7px 16px;border-radius:30px;">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ PHONE MOCKUP ══ --}}
<div id="v4-phone-outer">
    <div class="v4-shape v4-shape-ring"   id="sh-p1" style="width:320px;height:320px;top:-60px;left:-80px;"></div>
    <div class="v4-shape v4-shape-circle" id="sh-p2" style="width:70px;height:70px;bottom:10%;right:5%;"></div>
    <div class="v4-shape v4-shape-sq"     id="sh-p3" style="width:50px;height:50px;top:20%;right:4%;transform:rotate(30deg);"></div>
    <div class="v4-shape v4-shape-dot"    id="sh-p4" style="width:6px;height:6px;top:40%;left:5%;"></div>
    <div id="v4-phone-sticky">

        {{-- Step text panels --}}
        <div id="v4-psteps-wrap">
            <div class="v4-pstep active" data-step="0">
                <div class="v4-pl-eyebrow">{{ $isAr ? '01 — اكتشف' : '01 — Discover' }}</div>
                <h3 class="v4-pl-title">{!! $isAr ? 'ابحث عن <em>أفضل</em> الأماكن' : 'Find The <em>Best</em> Places' !!}</h3>
                <p class="v4-pl-desc">{{ $isAr ? 'تصفح مئات الصالونات والسبا والعيادات بكل سهولة.' : 'Browse hundreds of salons, spas and clinics easily.' }}</p>
                <div class="v4-pl-feats">
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'بحث بالخدمة أو الاسم' : 'Search by service or name' }}</div>
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'تقييمات حقيقية' : 'Real reviews' }}</div>
                </div>
            </div>

            <div class="v4-pstep" data-step="1">
                <div class="v4-pl-eyebrow">{{ $isAr ? '02 — احجز' : '02 — Book' }}</div>
                <h3 class="v4-pl-title">{!! $isAr ? '<em>احجز</em> في ثوانٍ' : '<em>Book</em> in Seconds' !!}</h3>
                <p class="v4-pl-desc">{{ $isAr ? 'اختر الموعد المناسب وأكّد حجزك فوراً بدون انتظار.' : 'Pick the right slot and confirm your booking instantly.' }}</p>
                <div class="v4-pl-feats">
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'تأكيد فوري' : 'Instant confirmation' }}</div>
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'تذكير تلقائي' : 'Auto reminder' }}</div>
                </div>
            </div>

            <div class="v4-pstep" data-step="2">
                <div class="v4-pl-eyebrow">{{ $isAr ? '03 — استمتع' : '03 — Enjoy' }}</div>
                <h3 class="v4-pl-title">{!! $isAr ? 'تتبّع <em>تجاربك</em>' : 'Track Your <em>History</em>' !!}</h3>
                <p class="v4-pl-desc">{{ $isAr ? 'راجع مواعيدك القادمة وقيّم تجربتك في كل مرة.' : 'Review upcoming appointments and rate past experiences.' }}</p>
                <div class="v4-pl-feats">
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'سجل كامل بكل الحجوزات' : 'Full booking history' }}</div>
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'نقاط مكافأة' : 'Reward points' }}</div>
                </div>
            </div>

            <div class="v4-psdots">
                <div class="v4-psdot active"></div>
                <div class="v4-psdot"></div>
                <div class="v4-psdot"></div>
            </div>
        </div>

        {{-- Center wrap: title above phone --}}
        <div id="v4-phone-cw">
            <div id="v4-phone-heading">
                <div class="v4-eyebrow" style="justify-content:center;margin-bottom:6px;">{{ $isAr ? 'كل ما تحتاجه في مكان واحد' : 'Everything in One Place' }}</div>
                <h2 class="v4-sec-title" style="margin:0;">{{ $isAr ? 'تطبيق' : 'One App,' }} <em>{{ $isAr ? 'واحد — كل شيء' : 'Everything' }}</em></h2>
            </div>
            <div id="v4-phone-mockup">
                <div class="v4-phone-frame">
                    <div class="v4-phone-notch"></div>
                    <div class="v4-phone-screen">
                        <div class="v4-pslide active" id="v4ps1">
                            <div class="v4-ps-hd">
                                <div class="v4-ps-logo">{{ $isAr ? 'بوكسي' : 'Book' }}<em>{{ $isAr ? '' : 'sy' }}</em></div>
                                <div class="v4-ps-tag">{{ $isAr ? 'احجز موعدك الآن' : 'Book your appointment' }}</div>
                            </div>
                            <div class="v4-ps-search"><i class="fas fa-search"></i>{{ $isAr ? 'ابحث...' : 'Search...' }}</div>
                            <div class="v4-ps-cats">
                                <div class="v4-ps-cat on">{{ $isAr ? 'الكل' : 'All' }}</div>
                                <div class="v4-ps-cat">{{ $isAr ? 'صالون' : 'Salon' }}</div>
                                <div class="v4-ps-cat">{{ $isAr ? 'سبا' : 'Spa' }}</div>
                            </div>
                            <div class="v4-ps-items">
                                <div class="v4-ps-item"><img class="v4-ps-item-img" src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=100&q=70" alt=""><div><div class="v4-ps-item-name">{{ $isAr ? 'صالون لوكس' : 'Luxe Salon' }}</div><div class="v4-ps-item-cat">{{ $isAr ? 'صالون' : 'Salon' }}</div><div class="v4-ps-item-rate">★ 4.9</div></div></div>
                                <div class="v4-ps-item"><img class="v4-ps-item-img" src="https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=100&q=70" alt=""><div><div class="v4-ps-item-name">{{ $isAr ? 'سبا رويال' : 'Royal Spa' }}</div><div class="v4-ps-item-cat">{{ $isAr ? 'سبا' : 'Spa' }}</div><div class="v4-ps-item-rate">★ 4.8</div></div></div>
                                <div class="v4-ps-item"><img class="v4-ps-item-img" src="https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=100&q=70" alt=""><div><div class="v4-ps-item-name">{{ $isAr ? 'عيادة جمال' : 'Skin Clinic' }}</div><div class="v4-ps-item-cat">{{ $isAr ? 'عيادة' : 'Clinic' }}</div><div class="v4-ps-item-rate">★ 4.7</div></div></div>
                            </div>
                        </div>
                        <div class="v4-pslide" id="v4ps2">
                            <div class="v4-ps2-top">
                                <div class="v4-ps2-title">{{ $isAr ? 'احجز موعدك' : 'Book Appointment' }}</div>
                                <div class="v4-ps2-sub">{{ $isAr ? 'صالون لوكس — قص + صبغة' : 'Luxe Salon — Cut + Color' }}</div>
                                <img class="v4-ps2-img" src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=400&q=70" alt="">
                            </div>
                            <div style="padding:10px 14px 6px;font-size:.62rem;font-weight:700;color:rgba(255,255,255,.4);">{{ $isAr ? 'اختر الوقت' : 'Choose Time' }}</div>
                            <div class="v4-ps2-slots">
                                <div class="v4-ps2-slot on">10:00</div><div class="v4-ps2-slot">11:00</div><div class="v4-ps2-slot">12:00</div>
                                <div class="v4-ps2-slot">14:00</div><div class="v4-ps2-slot">15:00</div><div class="v4-ps2-slot">16:00</div>
                            </div>
                            <div class="v4-ps2-btn">{{ $isAr ? 'تأكيد الحجز' : 'Confirm Booking' }}</div>
                        </div>
                        <div class="v4-pslide" id="v4ps3">
                            <div class="v4-ps3-top">
                                <div class="v4-ps3-av">{{ $isAr ? 'خ' : 'K' }}</div>
                                <div class="v4-ps3-name">{{ $isAr ? 'خالد' : 'Khaled' }}</div>
                            </div>
                            <div style="padding:10px 14px 5px;font-size:.62rem;font-weight:700;color:#C9A227;">{{ $isAr ? 'مواعيدي' : 'My Appointments' }}</div>
                            <div class="v4-ps3-appts">
                                <div class="v4-ps3-appt"><div><div class="v4-ps3-appt-name">{{ $isAr ? 'صالون لوكس' : 'Luxe Salon' }}</div><div class="v4-ps3-appt-date">{{ $isAr ? 'الجمعة، 20 يونيو' : 'Fri, Jun 20' }}</div></div><div class="v4-ps3-badge">{{ $isAr ? 'مؤكد' : 'Confirmed' }}</div></div>
                                <div class="v4-ps3-appt"><div><div class="v4-ps3-appt-name">{{ $isAr ? 'سبا رويال' : 'Royal Spa' }}</div><div class="v4-ps3-appt-date">{{ $isAr ? 'الأحد، 22 يونيو' : 'Sun, Jun 22' }}</div></div><div class="v4-ps3-badge">{{ $isAr ? 'قادم' : 'Upcoming' }}</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>{{-- /v4-phone-cw --}}
    </div>{{-- /v4-phone-sticky --}}
</div>{{-- /v4-phone-outer --}}

{{-- ══ SALON OWNERS — pinned scroll (mirror of phone) ══ --}}
<div id="v4-ow-outer">
    <div class="v4-shape v4-shape-ring"   id="sh-o1" style="width:280px;height:280px;bottom:-40px;right:-60px;"></div>
    <div class="v4-shape v4-shape-circle" id="sh-o2" style="width:90px;height:90px;top:8%;left:3%;"></div>
    <div class="v4-shape v4-shape-sq"     id="sh-o3" style="width:45px;height:45px;bottom:15%;left:6%;transform:rotate(-20deg);"></div>
    <div class="v4-shape v4-shape-dot"    id="sh-o4" style="width:7px;height:7px;top:55%;right:7%;"></div>
    <div id="v4-ow-sticky">

        {{-- Steps wrap — opposite side from phone --}}
        <div id="v4-owsteps-wrap">
            <div class="v4-owstep active" data-step="0">
                <div class="v4-pl-eyebrow">{{ $isAr ? '01 — الفروع' : '01 — Branches' }}</div>
                <h3 class="v4-pl-title">{!! $isAr ? 'أدِر <em>جميع</em> فروعك' : 'Manage <em>All</em> Branches' !!}</h3>
                <p class="v4-pl-desc">{{ $isAr ? 'لوحة تحكم موحدة لجميع فروعك — الحجوزات والأداء والإيرادات في مكان واحد.' : 'One unified dashboard for all branches — bookings, performance and revenue in one place.' }}</p>
                <div class="v4-pl-feats">
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'مقارنة الفروع لحظة بلحظة' : 'Real-time branch comparison' }}</div>
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'نقل الحجوزات بين الفروع' : 'Transfer bookings between branches' }}</div>
                </div>
            </div>
            <div class="v4-owstep" data-step="1">
                <div class="v4-pl-eyebrow">{{ $isAr ? '02 — الموظفون' : '02 — Staff' }}</div>
                <h3 class="v4-pl-title">{!! $isAr ? '<em>جداول</em> وصلاحيات' : '<em>Schedules</em> & Roles' !!}</h3>
                <p class="v4-pl-desc">{{ $isAr ? 'حدد جداول عمل الموظفين وصلاحيات كل شخص بسهولة تامة.' : 'Set staff schedules and role permissions with full control.' }}</p>
                <div class="v4-pl-feats">
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'جداول أسبوعية مرنة' : 'Flexible weekly schedules' }}</div>
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'صلاحيات مخصصة لكل دور' : 'Custom permissions per role' }}</div>
                </div>
            </div>
            <div class="v4-owstep" data-step="2">
                <div class="v4-pl-eyebrow">{{ $isAr ? '03 — التقارير' : '03 — Analytics' }}</div>
                <h3 class="v4-pl-title">{!! $isAr ? 'إحصاءات <em>تفصيلية</em>' : 'Detailed <em>Analytics</em>' !!}</h3>
                <p class="v4-pl-desc">{{ $isAr ? 'تقارير شاملة عن الإيرادات والخدمات الأكثر طلباً وأداء كل موظف.' : 'Full reports on revenue, top services and individual staff performance.' }}</p>
                <div class="v4-pl-feats">
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'رسوم بيانية تفاعلية' : 'Interactive charts' }}</div>
                    <div class="v4-pl-feat"><i class="fas fa-check-circle"></i> {{ $isAr ? 'تصدير التقارير بصيغة PDF' : 'Export reports as PDF' }}</div>
                </div>
            </div>
            <div class="v4-psdots" style="margin-top:32px;">
                <div class="v4-psdot active"></div>
                <div class="v4-psdot"></div>
                <div class="v4-psdot"></div>
            </div>
        </div>

        {{-- Center wrap: heading + dashboard card (mirrored direction) --}}
        <div id="v4-ow-cw">
            <div id="v4-ow-heading">
                <div class="v4-eyebrow" style="justify-content:center;margin-bottom:6px;">{{ $isAr ? 'لأصحاب الأعمال' : 'For Business Owners' }}</div>
                <h2 class="v4-sec-title" style="margin:0;">{!! $isAr ? 'أدِر <em>عملك</em> بذكاء' : 'Run Your <em>Business</em> Smart' !!}</h2>
            </div>

            {{-- Dashboard card with 3 slides --}}
            <div class="v4-dash" style="width:420px;max-width:90vw;position:relative;min-height:340px;">

                {{-- Slide 0: Branches --}}
                <div class="v4-dash-slide active" id="v4ds0">
                    <div class="v4-dash-hd">
                        <div>
                            <div class="v4-dash-title">{{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}</div>
                            <div class="v4-dash-sub">{{ $isAr ? 'صالون لوكس — جميع الفروع' : 'Luxe Salon — All Branches' }}</div>
                        </div>
                        <div class="v4-dash-badge">{{ $isAr ? 'مباشر' : 'Live' }}</div>
                    </div>
                    <div class="v4-dash-stats">
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n">248</div><div class="v4-dash-stat-l">{{ $isAr?'حجز اليوم':'Today' }}</div></div>
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n" style="color:#4ade80;">12</div><div class="v4-dash-stat-l">{{ $isAr?'فروع':'Branches' }}</div></div>
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n">4.9★</div><div class="v4-dash-stat-l">{{ $isAr?'التقييم':'Rating' }}</div></div>
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n" style="color:#4ade80;">+18%</div><div class="v4-dash-stat-l">{{ $isAr?'النمو':'Growth' }}</div></div>
                    </div>
                    <div class="v4-dash-sec-title">{{ $isAr ? 'الفروع' : 'Branches' }}</div>
                    <div class="v4-dash-branches">
                        @foreach([[$isAr?'فرع الرياض':'Riyadh','84','92%'],[$isAr?'فرع جدة':'Jeddah','67','78%'],[$isAr?'فرع الدمام':'Dammam','51','65%']] as [$bn,$bc,$bp])
                        <div class="v4-dash-branch">
                            <div class="v4-dash-branch-dot"></div>
                            <div style="flex:1">
                                <div class="v4-dash-branch-name">{{ $bn }}</div>
                                <div class="v4-dash-branch-bar"><div class="v4-dash-branch-fill" style="width:{{ $bp }}"></div></div>
                            </div>
                            <div class="v4-dash-branch-pct">{{ $bc }} {{ $isAr?'حجز':'bk' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Slide 1: Staff --}}
                <div class="v4-dash-slide" id="v4ds1">
                    <div class="v4-dash-hd">
                        <div>
                            <div class="v4-dash-title">{{ $isAr ? 'إدارة الموظفين' : 'Staff Management' }}</div>
                            <div class="v4-dash-sub">{{ $isAr ? 'فرع الرياض' : 'Riyadh Branch' }}</div>
                        </div>
                        <div class="v4-dash-badge">{{ $isAr ? '8 موظفين' : '8 Staff' }}</div>
                    </div>
                    <div class="v4-dash-sec-title">{{ $isAr ? 'جدول اليوم' : "Today's Schedule" }}</div>
                    @php $staff = $isAr
                        ? [['لينا','قص وتصفيف','09:00–17:00','#C9A227'],['ريم','تلوين','10:00–18:00','#4ade80'],['سارة','مانيكير','11:00–19:00','#C9A227'],['نور','سبا','08:00–16:00','#4ade80']]
                        : [['Lena','Cut & Style','09:00–17:00','#C9A227'],['Reem','Coloring','10:00–18:00','#4ade80'],['Sara','Manicure','11:00–19:00','#C9A227'],['Nour','Spa','08:00–16:00','#4ade80']]; @endphp
                    <div style="display:flex;flex-direction:column;gap:9px;margin-bottom:16px;">
                        @foreach($staff as [$sn,$ss,$sh,$sc])
                        <div style="display:flex;align-items:center;gap:10px;background:#1a1a1a;border-radius:10px;padding:10px 12px;border:1px solid rgba(255,255,255,.04);">
                            <div style="width:28px;height:28px;border-radius:50%;background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.2);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#C9A227;flex-shrink:0;">{{ mb_substr($sn,0,1) }}</div>
                            <div style="flex:1"><div style="font-size:.72rem;font-weight:700;color:#fff;">{{ $sn }}</div><div style="font-size:.6rem;color:rgba(255,255,255,.3);">{{ $ss }}</div></div>
                            <div style="font-size:.6rem;color:{{ $sc }};font-weight:600;">{{ $sh }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Slide 2: Analytics --}}
                <div class="v4-dash-slide" id="v4ds2">
                    <div class="v4-dash-hd">
                        <div>
                            <div class="v4-dash-title">{{ $isAr ? 'التقارير والإحصاءات' : 'Analytics & Reports' }}</div>
                            <div class="v4-dash-sub">{{ $isAr ? 'يونيو 2026' : 'June 2026' }}</div>
                        </div>
                        <div class="v4-dash-badge" style="color:#C9A227;background:rgba(201,162,39,.08);border-color:rgba(201,162,39,.18);">{{ $isAr ? '+23%' : '+23%' }}</div>
                    </div>
                    <div class="v4-dash-stats" style="margin-bottom:18px;">
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n">3.2K</div><div class="v4-dash-stat-l">{{ $isAr?'حجز هذا الشهر':'Bookings' }}</div></div>
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n" style="color:#4ade80;">18K</div><div class="v4-dash-stat-l">{{ $isAr?'الإيراد (ر.س)':'Revenue ($)' }}</div></div>
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n">94%</div><div class="v4-dash-stat-l">{{ $isAr?'رضا العملاء':'Satisfaction' }}</div></div>
                        <div class="v4-dash-stat"><div class="v4-dash-stat-n" style="color:#4ade80;">48</div><div class="v4-dash-stat-l">{{ $isAr?'عميل جديد':'New Clients' }}</div></div>
                    </div>
                    <div class="v4-dash-chart-wrap">
                        <div class="v4-dash-chart-lbl">{{ $isAr ? 'الإيرادات هذا الأسبوع' : 'Revenue this week' }}</div>
                        <div class="v4-dash-bars">
                            @foreach([38,55,48,72,88,65,95] as $h)
                            <div class="v4-dash-bar-col"><div class="v4-dash-bar" style="height:{{ $h }}%"></div></div>
                            @endforeach
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:14px;">
                        @foreach($isAr ? [['قص وتصفيف','68%'],['تلوين الشعر','22%'],['السبا والعناية','10%']] : [['Cut & Style','68%'],['Hair Coloring','22%'],['Spa & Care','10%']] as [$sn,$sp])
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="font-size:.7rem;color:rgba(255,255,255,.45);min-width:90px;">{{ $sn }}</div>
                            <div style="flex:1;height:4px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden;">
                                <div style="height:100%;width:{{ $sp }};background:linear-gradient(90deg,#C9A227,rgba(201,162,39,.3));border-radius:2px;"></div>
                            </div>
                            <div style="font-size:.7rem;color:#C9A227;font-weight:700;">{{ $sp }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>{{-- /v4-dash --}}

            <a href="#" class="btn-v4-gold" style="font-size:.82rem;padding:12px 28px;">
                {{ $isAr ? 'ابدأ مجاناً' : 'Start Free' }}
                <i class="fas fa-arrow-{{ $isAr ? 'left' : 'right' }}"></i>
            </a>
        </div>{{-- /v4-ow-cw --}}

    </div>{{-- /v4-ow-sticky --}}
</div>{{-- /v4-ow-outer --}}

{{-- ══ CATEGORIES ══ --}}
<section id="v4-cats" style="background:#111;padding:100px 0;">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-5 flex-wrap gap-3">
            <div>
                <div class="v4-eyebrow appear-animation" data-appear-animation="fadeInUpShorter">{{ $isAr ? 'الفئات' : 'Categories' }}</div>
                <h2 class="v4-sec-title mb-0">
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'ماذا تبحث' : 'What Are You' }}</span></span>
                    <span class="v4-split-line"><span class="v4-split-inner"><em>{{ $isAr ? 'عنه؟' : 'Looking For?' }}</em></span></span>
                </h2>
            </div>
        </div>
        <div style="overflow-x:auto;padding-bottom:8px;scrollbar-width:thin;scrollbar-color:rgba(201,162,39,.3) transparent;">
            <div style="display:flex;gap:14px;width:max-content;padding:4px 0;">
                @forelse($categories as $cat)
                    @php
                        $slug  = $cat->slug ?? 'salon';
                        $ico   = $catIcons[$slug]  ?? 'fas fa-star';
                        $grad  = $catGrad[$slug]   ?? 'bk-cg-default';
                        $cname = $isAr ? ($cat->name_ar ?? $cat->name_en) : ($cat->name_en ?? $cat->name_ar);
                    @endphp
                    <a href="{{ route('front.category', $cat->slug) }}" class="bk-cat-card {{ $grad }}">
                        <div class="bk-cat-card-icon"><i class="{{ $ico }}"></i></div>
                        <div class="bk-cat-card-name">{{ $cname }}</div>
                        <div class="bk-cat-card-count">{{ $cat->companies_count ?? 0 }} {{ $isAr ? 'مكان' : 'places' }}</div>
                    </a>
                @empty
                    @foreach(['fas fa-cut','fas fa-spa','fas fa-leaf','fas fa-tooth','fas fa-dumbbell','fas fa-eye'] as $ico)
                        <div class="bk-cat-card bk-cg-default">
                            <div class="bk-cat-card-icon"><i class="{{ $ico }}"></i></div>
                            <div class="bk-cat-card-name">{{ $isAr ? 'فئة' : 'Category' }}</div>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- ══ REVIEWS — walashi scattered ══ --}}
<div id="v4-reviews-outer">
    {{-- decorative ring (rotated by GSAP on scroll) --}}
    <div id="v4-rv-ring"></div>

    <div class="v4-rv-heading">
        <div class="v4-eyebrow" style="justify-content:center;margin-bottom:8px;">{{ $isAr ? 'آراء العملاء' : 'Testimonials' }}</div>
        <h2 class="v4-sec-title" style="text-align:center;margin:0;">
            <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'يثقون بنا' : 'What Our' }}</span></span>
            <span class="v4-split-line"><span class="v4-split-inner"><em>{{ $isAr ? 'آلاف العملاء' : 'Clients Say' }}</em></span></span>
        </h2>
    </div>

    @php
        $revs = $isAr ? [
            ['س','سارة المحمد','عميلة منتظمة','تجربة رائعة! حجزت موعدي في دقيقة واحدة والصالون كان على مستوى عالٍ جداً.'],
            ['أ','أحمد الزهراني','مستخدم جديد','المنصة سهلة الاستخدام وتوفر الكثير من الخيارات. أنصح بها بشدة.'],
            ['ن','نورة الشمري','عميلة دائمة','وفّرت على وقتي كثيراً. لا مزيد من الانتظار أو المكالمات.'],
            ['م','محمد العتيبي','مستخدم جديد','أفضل تطبيق حجز جربته. سريع، سهل، والخيارات كثيرة.'],
            ['ل','لمياء الحارثي','عميلة دائمة','خدمة ممتازة وحجز سهل. أنصح كل من يبحث عن جودة.'],
            ['ر','ريم الدوسري','عميلة منتظمة','تجربة لا تُنسى! الموقع جميل والخدمة استثنائية.'],
        ] : [
            ['E','Emma R.','Regular Client','Booking was incredibly easy. Found my salon in seconds, appointment was perfect.'],
            ['J','James K.','New User','Love how simple the platform is. So many great options. Highly recommend!'],
            ['N','Nadia T.','Loyal Customer','No more endless calls — just pick a slot and you\'re done. Life-changing.'],
            ['M','Mohammed A.','First Timer','Best booking app I\'ve ever used. Fast, simple, and so many choices.'],
            ['L','Laura S.','Regular Client','Excellent service and easy booking. Highly recommend to anyone.'],
            ['R','Rachel M.','Loyal Customer','An unforgettable experience! Beautiful platform and exceptional service.'],
        ];
    @endphp

    <div id="v4-rv-grid">
        {{-- Row 1: card · CTA · card --}}
        <div class="v4-rv" id="v4rv0">
            <div class="v4-rv-stars">@for($s=0;$s<5;$s++)<i class="fas fa-star"></i>@endfor</div>
            <p class="v4-rv-q">{{ $revs[0][3] }}</p>
            <div class="v4-rv-au">
                <div class="v4-rv-av">{{ $revs[0][0] }}</div>
                <div><div class="v4-rv-name">{{ $revs[0][1] }}</div><div class="v4-rv-role">{{ $revs[0][2] }}</div></div>
            </div>
        </div>

        <div class="v4-rv-cta-cell" id="v4rv-cta">
            <div class="v4-rv-cta">
                <span class="v4-rv-cta-dot"></span>
                <div class="v4-rv-cta-text">{!! $isAr ? 'انضم إلى <em>آلاف العملاء</em> السعداء' : 'Join <em>thousands</em> of happy clients' !!}</div>
                <div class="v4-rv-cta-sub">{{ $isAr ? 'حجوزات يومية، تقييمات حقيقية، خدمة استثنائية' : 'Daily bookings, real reviews, exceptional service' }}</div>
                <a href="#" class="btn-v4-gold" style="font-size:.78rem;padding:10px 22px;margin-top:4px;">
                    {{ $isAr ? 'ابدأ الآن' : 'Get Started' }}
                </a>
            </div>
        </div>

        <div class="v4-rv" id="v4rv1">
            <div class="v4-rv-stars">@for($s=0;$s<5;$s++)<i class="fas fa-star"></i>@endfor</div>
            <p class="v4-rv-q">{{ $revs[1][3] }}</p>
            <div class="v4-rv-au">
                <div class="v4-rv-av">{{ $revs[1][0] }}</div>
                <div><div class="v4-rv-name">{{ $revs[1][1] }}</div><div class="v4-rv-role">{{ $revs[1][2] }}</div></div>
            </div>
        </div>

        {{-- Row 2: card · card · card --}}
        <div class="v4-rv" id="v4rv2">
            <div class="v4-rv-stars">@for($s=0;$s<5;$s++)<i class="fas fa-star"></i>@endfor</div>
            <p class="v4-rv-q">{{ $revs[2][3] }}</p>
            <div class="v4-rv-au">
                <div class="v4-rv-av">{{ $revs[2][0] }}</div>
                <div><div class="v4-rv-name">{{ $revs[2][1] }}</div><div class="v4-rv-role">{{ $revs[2][2] }}</div></div>
            </div>
        </div>

        <div class="v4-rv" id="v4rv3">
            <div class="v4-rv-stars">@for($s=0;$s<5;$s++)<i class="fas fa-star"></i>@endfor</div>
            <p class="v4-rv-q">{{ $revs[3][3] }}</p>
            <div class="v4-rv-au">
                <div class="v4-rv-av">{{ $revs[3][0] }}</div>
                <div><div class="v4-rv-name">{{ $revs[3][1] }}</div><div class="v4-rv-role">{{ $revs[3][2] }}</div></div>
            </div>
        </div>

        <div class="v4-rv" id="v4rv4">
            <div class="v4-rv-stars">@for($s=0;$s<4;$s++)<i class="fas fa-star"></i>@endfor<i class="fas fa-star-half-alt"></i></div>
            <p class="v4-rv-q">{{ $revs[4][3] }}</p>
            <div class="v4-rv-au">
                <div class="v4-rv-av">{{ $revs[4][0] }}</div>
                <div><div class="v4-rv-name">{{ $revs[4][1] }}</div><div class="v4-rv-role">{{ $revs[4][2] }}</div></div>
            </div>
        </div>
    </div>
</div>

{{-- ══ PARTNERS ══ --}}
<div id="v4-partners-outer">
    <div id="v4-partners-sticky">
        <div class="v4-partners-inner container">
            <div class="v4-partners-hd">
                <div class="v4-eyebrow appear-animation" style="justify-content:center;" data-appear-animation="fadeInUpShorter">{{ $isAr ? 'شركاؤنا' : 'Our Partners' }}</div>
                <h2 class="v4-sec-title" style="text-align:center;margin:0;">
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'نفخر بشراكتنا' : 'Proud to Partner' }}</span></span>
                    <span class="v4-split-line"><span class="v4-split-inner"><em>{{ $isAr ? 'مع الأفضل' : 'With The Best' }}</em></span></span>
                </h2>
            </div>
            <div class="v4-prow">
                <div class="v4-prow-track" id="v4PRow1">
                    @foreach(array_merge($partners, $partners, $partners) as $p)
                        <div class="v4-partner-item"><span class="v4-partner-name">{{ $p }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="v4-prow">
                <div class="v4-prow-track-rev" id="v4PRow2">
                    @foreach(array_merge(array_reverse($partners), array_reverse($partners), array_reverse($partners)) as $p)
                        <div class="v4-partner-item"><span class="v4-partner-name">{{ $p }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ STATS ══ --}}
<div id="v4-stats">
    <div class="container">
        <div class="v4-stats-row">
            @php $sts = $isAr
                ? [['500+','صالون وعيادة'],['12K+','حجز ناجح'],['98%','رضا العملاء'],['50+','مدينة']]
                : [['500+','Salons & Clinics'],['12K+','Bookings Made'],['98%','Happy Clients'],['50+','Cities']];
            @endphp
            @foreach($sts as $i => $s)
                <div class="v4-stat appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ $i * 100 }}">
                    <div class="v4-stat-num" data-target="{{ $s[0] }}">0</div>
                    <div class="v4-stat-lbl">{{ $s[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══ FEATURED BRANCHES ══ --}}
<section id="v4-featured" style="background:#111;padding:110px 0;">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-5 flex-wrap gap-3">
            <div>
                <div class="v4-eyebrow appear-animation" data-appear-animation="fadeInUpShorter">{{ $isAr ? 'أماكن مميزة' : 'Featured Places' }}</div>
                <h2 class="v4-sec-title mb-0">
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'أفضل <em>الأماكن</em>' : 'Top <em>Picks</em>' }}</span></span>
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'بالقرب منك' : 'Near You' }}</span></span>
                </h2>
            </div>
            <a href="{{ route('front.index4') }}" class="bk-cc-book appear-animation" style="width:auto;padding:11px 28px;" data-appear-animation="fadeInUpShorter">
                {{ $isAr ? 'عرض الكل' : 'View All' }} <i class="fas fa-arrow-{{ $isAr ? 'left' : 'right' }}"></i>
            </a>
        </div>
        <div class="row g-4">
            @forelse($branches->take(6) as $i => $branch)
                @php
                    $img    = $branch->images->first();
                    $imgUrl = $img ? asset('storage/'.$img->path) : ($branch->company->logo ? asset('storage/'.$branch->company->logo) : null);
                    $company = $branch->company;
                    $bname  = $isAr ? ($branch->name_ar ?? $branch->name_en) : ($branch->name_en ?? $branch->name_ar);
                    $cat    = $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : '';
                    $reviews = $branch->reviews;
                    $avg    = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
                    $finalImg = $imgUrl ?: $fallbacks[$i % count($fallbacks)];
                @endphp
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('front.branch', $branch) }}" class="bk-company-card d-block text-decoration-none appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ ($i % 3) * 100 }}">
                        <div class="bk-cc-img">
                            <img src="{{ $finalImg }}" alt="{{ $bname }}" loading="lazy">
                            @if($cat)<div class="bk-cc-badge">{{ $cat }}</div>@endif
                            @if($avg)<div class="bk-cc-rating"><i class="fas fa-star"></i>{{ $avg }}</div>@endif
                        </div>
                        <div class="bk-cc-body">
                            <div class="bk-cc-name">{{ $bname }}</div>
                            <div class="bk-cc-location"><i class="fas fa-map-marker-alt"></i>{{ Str::limit($branch->address ?? ($isAr ? 'الموقع' : 'Location'), 38) }}</div>
                            <div class="bk-cc-chips">
                                @foreach($branch->services->take(3) as $svc)
                                    <span class="bk-cc-chip">{{ Str::limit($isAr ? ($svc->name_ar ?? $svc->name_en) : ($svc->name_en ?? $svc->name_ar), 18) }}</span>
                                @endforeach
                            </div>
                            <div class="bk-cc-book mt-2">
                                <i class="fas fa-calendar-check"></i>
                                {{ $isAr ? 'احجز الآن' : 'Book Now' }}
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                @for($i = 0; $i < 6; $i++)
                    <div class="col-md-6 col-lg-4">
                        <div class="bk-company-card appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ ($i % 3) * 100 }}">
                            <div class="bk-cc-img"><img src="{{ $fallbacks[$i] }}" alt="salon" loading="lazy"></div>
                            <div class="bk-cc-body">
                                <div class="bk-cc-name">{{ $isAr ? 'صالون '.($i+1) : 'Salon '.($i+1) }}</div>
                                <div class="bk-cc-location"><i class="fas fa-map-marker-alt"></i>{{ $isAr ? 'الرياض' : 'Riyadh' }}</div>
                                <div class="bk-cc-book mt-2"><i class="fas fa-calendar-check"></i>{{ $isAr ? 'احجز الآن' : 'Book Now' }}</div>
                            </div>
                        </div>
                    </div>
                @endfor
            @endforelse
        </div>
    </div>
</section>

{{-- ══ CTA ══ --}}
<section id="v4-cta">
    <div class="v4-shape v4-shape-ring"   id="sh-c1" style="width:400px;height:400px;top:50%;left:-120px;transform:translateY(-50%);"></div>
    <div class="v4-shape v4-shape-ring"   id="sh-c2" style="width:200px;height:200px;top:10%;right:8%;"></div>
    <div class="v4-shape v4-shape-circle" id="sh-c3" style="width:55px;height:55px;bottom:20%;right:15%;"></div>
    <div class="v4-shape v4-shape-sq"     id="sh-c4" style="width:40px;height:40px;top:15%;left:15%;transform:rotate(45deg);"></div>
    <div class="v4-shape v4-shape-dot"    id="sh-c5" style="width:9px;height:9px;bottom:30%;left:20%;"></div>
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7">
                <h2 class="v4-cta-title">
                    <span class="v4-split-line"><span class="v4-split-inner">{{ $isAr ? 'جاهز لحجز' : 'Ready For Your' }}</span></span>
                    <span class="v4-split-line"><span class="v4-split-inner"><em>{{ $isAr ? 'تجربتك التالية؟' : 'Next Experience?' }}</em></span></span>
                </h2>
                <p class="v4-sec-sub mx-auto appear-animation" data-appear-animation="fadeInUpShorter" style="max-width:440px;">
                    {{ $isAr ? 'انضم إلى آلاف العملاء الذين يثقون ببوكسي.' : 'Join thousands of happy clients discovering the best beauty services.' }}
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap mt-4 appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="150">
                    <div class="v4-mag"><a href="{{ route('front.index4') }}" class="btn-v4-gold"><i class="fas fa-calendar-check"></i>{{ $isAr ? 'احجز الآن' : 'Book Now' }}</a></div>
                    <div class="v4-mag"><a href="{{ route('front.about') }}" class="btn-v4-out">{{ $isAr ? 'اعرف أكثر' : 'Learn More' }} <i class="fas fa-arrow-{{ $isAr ? 'left' : 'right' }}"></i></a></div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('front.partials.footer')

</div>{{-- /main --}}

{{-- ══ PORTO SCRIPTS ══ --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.cookie/jquery.cookie.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easy-pie-chart/jquery.easypiechart.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/lazysizes/lazysizes.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/owl.carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/vivus/vivus.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/demos/demo-beauty-salon.js') }}"></script>
<script src="{{ asset('frontend/js/custom.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>

{{-- GSAP بعد Porto حتى لا تُكتب فوقه من theme.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="https://unpkg.com/lenis@1.1.14/dist/lenis.min.js"></script>

<script>
(function(){
gsap.registerPlugin(ScrollTrigger);
const isAr = {{ $isAr ? 'true' : 'false' }};

/* ── Lenis smooth scroll ── */
const lenis = new Lenis({ duration:1.3, easing:t=>Math.min(1,1.001-Math.pow(2,-10*t)), smoothTouch:false });
/* مطلوب لـ pin:true مع Lenis */
ScrollTrigger.scrollerProxy(document.body,{
    scrollTop(v){ return arguments.length ? lenis.scrollTo(v,{immediate:true}) : lenis.scroll; },
    getBoundingClientRect(){ return {top:0,left:0,width:window.innerWidth,height:window.innerHeight}; },
    pinType: document.body.style.transform ? 'transform' : 'fixed'
});
lenis.on('scroll', ScrollTrigger.update);
gsap.ticker.add(t => lenis.raf(t * 1000));
gsap.ticker.lagSmoothing(0);
ScrollTrigger.addEventListener('refresh', () => lenis.resize());

/* ── Loader ── */
const ldW  = document.getElementById('ldWelcome');
const ldB  = document.getElementById('ldBrand');
const ldSw = document.getElementById('ldSweep');
const ldF  = document.getElementById('ldFill');
const ldP  = document.getElementById('ldPct');
const ldWp = document.getElementById('ldWipe');
const ldr  = document.getElementById('v4-loader');
gsap.timeline()
    .to(ldW, {opacity:1, y:0, duration:.6, ease:'power2.out'})
    .to(ldB, {y:'0%',   duration:.9, ease:'power3.out'}, .15);
gsap.to(ldSw, {left:'100%', opacity:1, duration:1.4, ease:'power1.inOut', repeat:-1, repeatDelay:.3,
    onStart(){ ldSw.style.opacity='1'; }});
let cnt=0;
const ci=setInterval(()=>{
    cnt+=Math.floor(Math.random()*4)+2;
    if(cnt>=100){cnt=100;clearInterval(ci);startExit();}
    ldF.style.width=cnt+'%'; ldP.textContent=cnt+'%';
},28);
function startExit(){
    gsap.timeline()
        .to([ldW,ldP],{y:-18,opacity:0,duration:.4,ease:'power2.in',stagger:.06})
        .to(ldB,{y:'-110%',duration:.55,ease:'power3.in'},.1)
        .to(ldWp,{scaleY:1,duration:.65,ease:'power3.inOut'},.35)
        .to(ldr,{opacity:0,duration:.35,onComplete(){ldr.style.display='none';heroIn();}}, .9);
}

/* ── Hero in ── */
function heroIn(){
    const splits=document.querySelectorAll('#v4-hero .v4-split-inner');
    gsap.timeline({defaults:{ease:'power3.out'}})
        .to('#hEye',{opacity:1,duration:.7},0)
        .to(splits,{y:'0%',duration:1,stagger:.1},.1)
        .to('#hSearch',{opacity:1,y:0,duration:.8},.6)
        .to('#hChips',{opacity:1,y:0,duration:.7},.75)
        .to('#hScroll',{opacity:1,duration:.6},.9);
}

/* ── Custom cursor ── */
const dot=document.getElementById('v4-dot'), ring=document.getElementById('v4-ring');
let rx=0,ry=0,mx=0,my=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;dot.style.left=mx+'px';dot.style.top=my+'px';});
(function loop(){rx+=(mx-rx)*.1;ry+=(my-ry)*.1;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();
document.querySelectorAll('a,button,.bk-company-card,.bk-cat-card,.v4-rv').forEach(el=>{
    el.addEventListener('mouseenter',()=>document.body.classList.add('v4-hov'));
    el.addEventListener('mouseleave',()=>document.body.classList.remove('v4-hov'));
});
document.addEventListener('mousedown',()=>document.body.classList.add('v4-clk'));
document.addEventListener('mouseup',()=>document.body.classList.remove('v4-clk'));

/* ── Navbar scroll ── */
$(window).on('scroll',function(){
    if($(this).scrollTop()>40){$('#v4-nav').addClass('scrolled');}
    else{$('#v4-nav').removeClass('scrolled');}
});

/* ── Hero video parallax ── */
const heroVid = document.querySelector('.v4-hero-video video');
document.getElementById('v4-hero')?.addEventListener('mousemove',e=>{
    const rx2=(e.clientX/window.innerWidth-.5)*2, ry2=(e.clientY/window.innerHeight-.5)*2;
    gsap.to(heroVid,{x:rx2*-18,y:ry2*-10,duration:1.5,ease:'power2.out'});
    gsap.to('.v4-hero-content',{x:rx2*6,y:ry2*4,duration:1.8,ease:'power2.out'});
});

/* ── Magnetic buttons ── */
document.querySelectorAll('.v4-mag').forEach(w=>{
    const inner=w.firstElementChild;
    w.addEventListener('mousemove',e=>{
        const r=w.getBoundingClientRect();
        gsap.to(inner,{x:(e.clientX-r.left-r.width/2)*.3,y:(e.clientY-r.top-r.height/2)*.3,duration:.4,ease:'power2.out'});
    });
    w.addEventListener('mouseleave',()=>gsap.to(inner,{x:0,y:0,duration:.6,ease:'elastic.out(1,.4)'}));
});

/* ── Circular section: ring + parallax ── */
gsap.to('.v4-circle-ring',{rotation:360,ease:'none',scrollTrigger:{trigger:'#v4-circle',start:'top bottom',end:'bottom top',scrub:2}});
gsap.to('.v4-circle-ring-2',{rotation:-360,ease:'none',scrollTrigger:{trigger:'#v4-circle',start:'top bottom',end:'bottom top',scrub:3}});
gsap.to('.v4-circle-img',{y:-26,ease:'none',scrollTrigger:{trigger:'#v4-circle',start:'top bottom',end:'bottom top',scrub:1.5}});
document.getElementById('v4-circle')?.addEventListener('mousemove',e=>{
    const rx2=(e.clientX/window.innerWidth-.5)*2, ry2=(e.clientY/window.innerHeight-.5)*2;
    gsap.to('.v4-ct-1',{x:rx2*10,y:ry2*8,duration:1.2,ease:'power2.out'});
    gsap.to('.v4-ct-2',{x:rx2*-12,y:ry2*-8,duration:1.4,ease:'power2.out'});
    gsap.to('.v4-ct-3',{x:rx2*8,y:ry2*12,duration:1.6,ease:'power2.out'});
});

/* ── Split text reveal (non-hero) ── */
document.querySelectorAll('.v4-split-inner').forEach(el=>{
    if(el.closest('#v4-hero')||el.closest('#v4-cta')) return;
    gsap.to(el,{y:'0%',duration:1,ease:'power3.out',
        scrollTrigger:{trigger:el.closest('.v4-split-line'),start:'top 89%'}});
});
document.querySelectorAll('#v4-cta .v4-split-inner').forEach(el=>{
    gsap.to(el,{y:'0%',duration:1,ease:'power3.out',
        scrollTrigger:{trigger:el.closest('.v4-split-line'),start:'top 88%'}});
});
/* reviews heading */
document.querySelectorAll('.v4-rv-heading .v4-split-inner').forEach(el=>{
    gsap.to(el,{y:'0%',duration:1,ease:'power3.out',
        scrollTrigger:{trigger:el.closest('.v4-split-line'),start:'top 88%'}});
});

window.addEventListener('load', function(){

/* ══ PINNED SECTIONS — image centers then slides to its side ══ */
function buildPinSection({ stickyId, cwId, stepsWrapId, stepSel, dotSel, slideSel, screenSel }){
    const el        = document.getElementById(stickyId);
    const cwEl      = document.getElementById(cwId);
    const stepsWrap = document.getElementById(stepsWrapId);
    if(!el || !cwEl || !stepsWrap) return;

    const steps  = [...el.querySelectorAll(stepSel)];
    const dots   = [...el.querySelectorAll(dotSel)];
    const slides = slideSel ? [...el.querySelectorAll(slideSel)] : [];
    const screen = screenSel ? el.querySelector(screenSel) : null;

    if(!steps.length) return;

    /* Mobile: static, no pin */
    if(window.innerWidth <= 768){
        steps[0].classList.add('active');
        if(dots[0])   dots[0].classList.add('active');
        if(slides[0]) slides[0].classList.add('active');
        return;
    }

    /* ── Centering offset ── */
    const containerW = el.offsetWidth;
    const cwCenter   = cwEl.offsetLeft + cwEl.offsetWidth / 2;
    const xOffset    = (containerW / 2) - cwCenter;   // negative = cw is right of center
    const stepsXInit = xOffset > 0 ? -60 : 60;

    gsap.set(cwEl,      { x: xOffset });
    gsap.set(stepsWrap, { opacity: 0, x: stepsXInit });

    /* ── Slide switch ── */
    let curSlide = 0, busy = false;
    function switchSlide(i){
        if(i === curSlide || busy) return;
        busy = true; curSlide = i;
        const swap = () => {
            slides.forEach((s,j) => s.classList.toggle('active', j===i));
            dots.forEach((d,j)   => d.classList.toggle('active', j===i));
        };
        if(screen){
            gsap.timeline({ onComplete:()=>{ busy=false; } })
                .to(screen,{ scaleY:.03, filter:'brightness(0)', duration:.16, ease:'power3.in', transformOrigin:'center center' })
                .call(swap)
                .to(screen,{ scaleY:1,   filter:'brightness(1)', duration:.24, ease:'power2.out' });
        } else {
            const act = slides.filter((_,j)=>j===i);
            const oth = slides.filter((_,j)=>j!==i);
            gsap.timeline({ onComplete:()=>{ busy=false; } })
                .to(oth,{ opacity:0, duration:.14 })
                .call(swap)
                .fromTo(act,{ opacity:0 },{ opacity:1, duration:.2 });
        }
    }

    let curStep = -1;
    function setStep(s){
        if(s === curStep) return; curStep = s;
        steps.forEach((step,j) => step.classList.toggle('active', j===s));
        if(slides.length) switchSlide(s);
        else dots.forEach((d,j) => d.classList.toggle('active', j===s));
    }
    setStep(0);

    /* ── Timeline: 0–22% intro slide, 22–100% step cycling ── */
    const tl = gsap.timeline({ paused: true })
        .to(cwEl,      { x: 0, duration: .22, ease: 'power2.inOut' }, 0)
        .to(stepsWrap, { opacity: 1, x: 0, duration: .18, ease: 'power2.out' }, 0.05)
        .to({}, { duration: .78 });

    ScrollTrigger.create({
        trigger:    el,
        pin:        true,
        pinSpacing: true,
        start:      'top top',
        end:        '+=300%',
        scrub:      1.2,
        animation:  tl,
        onUpdate(self){
            const p = self.progress;
            if(p < 0.50)      setStep(0);
            else if(p < 0.74) setStep(1);
            else              setStep(2);
        }
    });
}

buildPinSection({ stickyId:'v4-phone-sticky', cwId:'v4-phone-cw', stepsWrapId:'v4-psteps-wrap',  stepSel:'.v4-pstep',  dotSel:'.v4-psdot', slideSel:'.v4-pslide',    screenSel:'.v4-phone-screen' });
buildPinSection({ stickyId:'v4-ow-sticky',    cwId:'v4-ow-cw',    stepsWrapId:'v4-owsteps-wrap', stepSel:'.v4-owstep', dotSel:'.v4-psdot', slideSel:'.v4-dash-slide', screenSel:null });

/* ══ SCROLLYTELLING — per-section entrance animations ══ */
(function initScrollytelling(){

    const ease  = 'power3.out';
    const isMob = window.innerWidth <= 768;

    /* ── helper: fade+slide up ── */
    function fadeUp(targets, opts={}){
        gsap.fromTo(targets,
            { opacity:0, y: opts.y ?? 50 },
            { opacity:1, y:0, duration: opts.dur ?? 1, ease,
              stagger: opts.stagger ?? 0,
              scrollTrigger:{
                  trigger: opts.trigger ?? targets,
                  start: opts.start ?? 'top 88%',
                  toggleActions:'play none none reverse',
                  ...( opts.st ?? {} )
              }
            }
        );
    }

    /* ── helper: fade+slide from side ── */
    function fadeSide(targets, fromX, opts={}){
        gsap.fromTo(targets,
            { opacity:0, x: fromX },
            { opacity:1, x:0, duration: opts.dur ?? 1, ease,
              stagger: opts.stagger ?? 0,
              scrollTrigger:{
                  trigger: opts.trigger ?? targets,
                  start: opts.start ?? 'top 88%',
                  toggleActions:'play none none reverse',
              }
            }
        );
    }

    /* ── CIRCLE SECTION ── */
    const circleImg = document.getElementById('v4CircleOuter');
    if(circleImg){
        gsap.fromTo(circleImg,
            { scale:.85, opacity:0, rotate:-8 },
            { scale:1,   opacity:1, rotate:0, duration:1.2, ease:'power2.out',
              scrollTrigger:{ trigger:'#v4-circle', start:'top 80%', toggleActions:'play none none reverse' }
            }
        );
        /* Parallax: image moves up slightly while scrolling past */
        gsap.to(circleImg,{
            y: isMob ? 0 : -60, ease:'none',
            scrollTrigger:{ trigger:'#v4-circle', start:'top bottom', end:'bottom top', scrub:1.5 }
        });
        fadeUp('.v4-circle-text', { trigger:'#v4-circle', stagger:.1, y:40 });
    }

    /* ── PHONE SECTION — heading + visual intro ── */
    const phoneHeading = document.getElementById('v4-phone-heading');
    if(phoneHeading){
        gsap.fromTo(phoneHeading,
            { opacity:0, y:30 },
            { opacity:1, y:0, duration:.9, ease,
              scrollTrigger:{ trigger:'#v4-phone-sticky', start:'top 80%', toggleActions:'play none none none' }
            }
        );
    }
    const phoneMockup = document.getElementById('v4-phone-mockup');
    if(phoneMockup){
        gsap.fromTo(phoneMockup,
            { opacity:0, y:60, scale:.9 },
            { opacity:1, y:0,  scale:1, duration:1, ease,
              scrollTrigger:{ trigger:'#v4-phone-sticky', start:'top 80%', toggleActions:'play none none none' }
            }
        );
    }

    /* ── OWNERS SECTION — heading + card intro ── */
    const owHeading = document.getElementById('v4-ow-heading');
    if(owHeading){
        gsap.fromTo(owHeading,
            { opacity:0, y:30 },
            { opacity:1, y:0, duration:.9, ease,
              scrollTrigger:{ trigger:'#v4-ow-sticky', start:'top 80%', toggleActions:'play none none none' }
            }
        );
    }
    const owDash = document.querySelector('#v4-ow-cw .v4-dash');
    if(owDash){
        gsap.fromTo(owDash,
            { opacity:0, y:60, scale:.92 },
            { opacity:1, y:0,  scale:1, duration:1, ease,
              scrollTrigger:{ trigger:'#v4-ow-sticky', start:'top 80%', toggleActions:'play none none none' }
            }
        );
    }

    /* ── CATEGORIES ── */
    const catCards = document.querySelectorAll('.bk-cat-card');
    if(catCards.length){
        gsap.fromTo(catCards,
            { opacity:0, y:50, scale:.9 },
            { opacity:1, y:0,  scale:1, duration:.7, ease, stagger:.06,
              scrollTrigger:{ trigger:'#v4-cats', start:'top 82%', toggleActions:'play none none reverse' }
            }
        );
    }

    /* ── COMPANY CARDS ── */
    const bizCards = document.querySelectorAll('.bk-company-card');
    if(bizCards.length){
        gsap.fromTo(bizCards,
            { opacity:0, y:40 },
            { opacity:1, y:0, duration:.65, ease, stagger:.07,
              scrollTrigger:{ trigger: bizCards[0], start:'top 88%', toggleActions:'play none none reverse' }
            }
        );
    }

    /* ── STATS section ── */
    fadeUp('#v4-stats .v4-stat', { stagger:.1, y:30, trigger:'#v4-stats' });

    /* ── CTA ── */
    const cta = document.getElementById('v4-cta');
    if(cta){
        gsap.fromTo(cta.querySelectorAll('.v4-cta-title, .v4-sec-sub, .d-flex'),
            { opacity:0, y:40 },
            { opacity:1, y:0, duration:.9, ease, stagger:.12,
              scrollTrigger:{ trigger:cta, start:'top 80%', toggleActions:'play none none reverse' }
            }
        );
    }

    /* ── Partners heading ── */
    fadeUp('#v4-partners-sticky .v4-partners-hd', { trigger:'#v4-partners-outer', y:30 });

    ScrollTrigger.refresh();
})();

/* ══ FLOATING SHAPES — parallax + entrance ══ */
(function(){
    /* [ id, triggerSection, yMove, xMove, rotation ] */
    const shapes = [
        /* Hero */
        ['sh-h1','#v4-hero',  -140,  30,  25],
        ['sh-h2','#v4-hero',  -80,  -20, -15],
        ['sh-h3','#v4-hero',  -110,  40,  30],
        ['sh-h4','#v4-hero',  -60,  -50, -20],
        ['sh-h5','#v4-hero',  -90,   15,  40],
        ['sh-h6','#v4-hero',  -130, -10, -35],
        ['sh-h7','#v4-hero',  -70,   25,   0],
        ['sh-h8','#v4-hero',  -50,  -30,   0],
        /* Phone */
        ['sh-p1','#v4-phone-outer', -100, -30,  20],
        ['sh-p2','#v4-phone-outer',  -80,  20, -25],
        ['sh-p3','#v4-phone-outer', -120,  10,  50],
        ['sh-p4','#v4-phone-outer',  -60, -15,   0],
        /* Owners */
        ['sh-o1','#v4-ow-outer',  -90,  25, -20],
        ['sh-o2','#v4-ow-outer', -110, -20,  30],
        ['sh-o3','#v4-ow-outer',  -70,  10, -45],
        ['sh-o4','#v4-ow-outer',  -50, -30,   0],
        /* CTA */
        ['sh-c1','#v4-cta', -80,   0, -10],
        ['sh-c2','#v4-cta', -120,  15,  25],
        ['sh-c3','#v4-cta', -60,  -20, -30],
        ['sh-c4','#v4-cta', -100,   8,  60],
        ['sh-c5','#v4-cta', -45,  -10,   0],
    ];

    shapes.forEach(([id, trigger, yMove, xMove, rot]) => {
        const el = document.getElementById(id);
        if(!el) return;

        /* Fade-in entrance */
        gsap.fromTo(el,
            { opacity:0, scale:.7 },
            { opacity:1, scale:1, duration:1.4, ease:'power2.out',
              scrollTrigger:{ trigger, start:'top 85%', toggleActions:'play none none reverse' }
            }
        );

        /* Parallax movement while scrolling past */
        gsap.to(el, {
            y: yMove, x: xMove, rotate: rot,
            ease: 'none',
            scrollTrigger:{ trigger, start:'top bottom', end:'bottom top', scrub:2 }
        });
    });
})();

/* ══ REVIEWS — Scrollytelling ══ */
(function(){
    const outer = document.getElementById('v4-reviews-outer');
    const ring  = document.getElementById('v4-rv-ring');
    if(!outer) return;

    /* ── Rotating ring tied to scroll ── */
    if(ring){
        gsap.to(ring, {
            rotation: 180,
            ease: 'none',
            scrollTrigger:{ trigger: outer, start:'top bottom', end:'bottom top', scrub:2 }
        });
    }

    /* ── Cards stagger-in as section enters viewport ── */
    const cards = [...outer.querySelectorAll('.v4-rv')];
    const cta   = outer.querySelector('.v4-rv-cta-cell');

    cards.forEach((card, i) => {
        /* alternating directions: odd from left, even from right */
        const xFrom = (i % 2 === 0) ? -50 : 50;
        gsap.fromTo(card,
            { opacity:0, y:60, x:xFrom, filter:'blur(6px)' },
            { opacity:1, y:0,  x:0,     filter:'blur(0px)',
              duration:1, ease:'power3.out',
              scrollTrigger:{ trigger: card, start:'top 88%', toggleActions:'play none none reverse' },
              delay: i * 0.07
            }
        );
    });

    if(cta){
        gsap.fromTo(cta,
            { opacity:0, scale:.85 },
            { opacity:1, scale:1, duration:.9, ease:'back.out(1.4)',
              scrollTrigger:{ trigger: cta, start:'top 85%', toggleActions:'play none none reverse' }
            }
        );
    }

    /* ── Heading split lines ── */
    outer.querySelectorAll('.v4-rv-heading .v4-split-inner').forEach(el => {
        gsap.to(el, { y:'0%', duration:1, ease:'power3.out',
            scrollTrigger:{ trigger:el.closest('.v4-split-line'), start:'top 88%' } });
    });
})();

/* ── PARTNERS scroll-speed ── */
gsap.to('#v4PRow1',{xPercent:-12,ease:'none',scrollTrigger:{trigger:'#v4-partners-outer',start:'top bottom',end:'bottom top',scrub:2}});
gsap.to('#v4PRow2',{xPercent:12, ease:'none',scrollTrigger:{trigger:'#v4-partners-outer',start:'top bottom',end:'bottom top',scrub:2}});

/* ── Stats count-up ── */
document.querySelectorAll('.v4-stat-num[data-target]').forEach(el=>{
    const raw=el.dataset.target, suffix=raw.replace(/[\d.]/g,''), num=parseFloat(raw);
    if(isNaN(num)) return;
    ScrollTrigger.create({trigger:el,start:'top 88%',once:true,
        onEnter:()=>gsap.fromTo({v:0},{v:num},{duration:1.8,ease:'power2.out',
            onUpdate:function(){el.textContent=Math.round(this.targets()[0].v)+suffix;}})
    });
});

}); // end window load

})();
</script>

</div>{{-- /body --}}
</body>
</html>
