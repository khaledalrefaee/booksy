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
        asset('frontend/img/stock/1522337360788-8b13dee7a37e-w700.jpg'),
        asset('frontend/img/stock/1570172619644-dfd03ed5d881-w700.jpg'),
        asset('frontend/img/stock/1487412947147-5cebf100ffc2-w700.jpg'),
        asset('frontend/img/stock/1580618672591-eb180b1a973f-w700.jpg'),
        asset('frontend/img/stock/1516975080664-ed2fc6a32937-w700.jpg'),
        asset('frontend/img/stock/1600948836101-f9ffda59d250-w700.jpg'),
    ];
    $partners = ['Marriott','Four Seasons','Hilton','Hyatt','IHG','Accor','Radisson','Mövenpick'];
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<script>
/* Apply saved theme before first paint (no flash). Default: dark. */
(function(){try{var t=localStorage.getItem('bk-theme')||'dark';
document.documentElement.setAttribute('data-theme',t);}catch(e){
document.documentElement.setAttribute('data-theme','dark');}})();
</script>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $isAr ? 'بوكسي — احجز موعدك' : 'Booksy — Book Your Appointment' }}</title>

<!-- Fonts -->
<link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">

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
/* ═══════════════════════════════════════════
   THEME TOKENS — Olive · Cream · Gold  (dark ⇆ light)
═══════════════════════════════════════════ */
:root,:root[data-theme="dark"]{
    --bg:#0e1109;        --bg-rgb:14,17,9;
    --bg-2:#141810;      --surface:#171c11;   --surface-2:#1e2415;
    --fg:#f2eede;        --fg-rgb:242,238,222;
    --gold:#C9A227;      --gold-rgb:201,162,39;  --gold-hi:#e8c84a;
    --olive:#8a9a55;     --olive-rgb:138,154,85;
    --on-gold:#12140b;
    --deco:rgba(201,162,39,.55);
    --deco-2:rgba(138,154,85,.5);
    color-scheme:dark;
}
:root[data-theme="light"]{
    --bg:#f7f3e7;        --bg-rgb:247,243,231;
    --bg-2:#efe9d5;      --surface:#ffffff;   --surface-2:#f5f0e0;
    --fg:#2b3117;        --fg-rgb:43,49,23;
    --gold:#9a7c14;      --gold-rgb:154,124,20;  --gold-hi:#b8901f;
    --olive:#5a6b2f;     --olive-rgb:90,107,47;
    --on-gold:#fbf7ea;
    --deco:rgba(154,124,20,.45);
    --deco-2:rgba(90,107,47,.42);
    color-scheme:light;
}
/* Hero is over a dark video → keep it dark in BOTH themes by re-scoping its tokens */
:root[data-theme="light"] #v4-hero{
    --fg:#ffffff; --fg-rgb:255,255,255;
    --bg-rgb:10,10,10;
    --gold:#C9A227; --gold-rgb:201,162,39; --on-gold:#12140b;
}
html{transition:background-color .5s ease;}

/* ── RTL font ── */
@if($isAr)
body,p,li,td,input,select,textarea,.form-control,
.nav-link,.bk-cc-name,.bk-cat-card-name{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@else
body{font-family:'Poppins',sans-serif;}
@endif

/* ── Base dark ── */
html,body{background:var(--bg)!important;color:rgba(var(--fg-rgb),.82)!important;overflow-x:hidden;}
html{scroll-behavior:auto;}
.section,.main,.body{background:var(--bg)!important;}
body{cursor:none;}
@media(max-width:768px){body{cursor:auto;}}

/* ── Cursor ── */
#v4-dot,#v4-ring{position:fixed;top:0;left:0;z-index:9998;pointer-events:none;border-radius:50%;transform:translate(-50%,-50%);will-change:left,top;}
#v4-dot{width:7px;height:7px;background:var(--gold);}
#v4-ring{width:44px;height:44px;border:1.5px solid rgba(var(--gold-rgb),.5);transition:width .35s,height .35s,background .35s;}
body.v4-hov #v4-dot{opacity:0;}
body.v4-hov #v4-ring{width:74px;height:74px;background:rgba(var(--gold-rgb),.08);border-color:var(--gold);}
body.v4-clk #v4-ring{width:30px;height:30px;}
@media(max-width:768px){#v4-dot,#v4-ring{display:none;}}

/* ══════════════════════════════
   LOADER — Minimal Wipe (v5)
══════════════════════════════ */
#v4-loader{
    position:fixed;inset:0;z-index:9999;
    background:var(--bg);
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    overflow:hidden;
}
.v4-ld-welcome{
    font-size:.62rem;letter-spacing:6px;text-transform:uppercase;
    color:rgba(var(--fg-rgb),.18);margin-bottom:18px;
    opacity:0;transform:translateY(8px);
}
.v4-ld-brand{
    font-size:clamp(2rem,5vw,3.8rem);font-weight:900;
    letter-spacing:-3px;line-height:1;overflow:hidden;
    margin-bottom:48px;
    @if(!$isAr) font-family:'Poppins',sans-serif!important; @endif
}
.v4-ld-brand em{font-style:normal;color:var(--gold);}
.v4-ld-brand-inner{display:block;transform:translateY(110%);}
.v4-ld-track{
    width:clamp(160px,26vw,300px);height:1px;
    background:rgba(var(--gold-rgb),.1);position:relative;overflow:hidden;
    margin-bottom:16px;
}
.v4-ld-sweep{
    position:absolute;top:0;left:-60%;width:60%;height:100%;
    background:linear-gradient(90deg,transparent,var(--gold),transparent);
    opacity:0;
}
.v4-ld-fill{
    position:absolute;top:0;left:0;height:100%;width:0%;
    background:var(--gold);transition:width .12s linear;
}
.v4-ld-pct{font-size:.72rem;letter-spacing:3px;color:rgba(var(--gold-rgb),.45);}
.v4-ld-wipe{
    position:absolute;inset:0;background:var(--gold);
    transform:scaleY(0);transform-origin:bottom;pointer-events:none;
}

/* ══════════════════════════════
   NAVBAR
══════════════════════════════ */
#v4-nav{
    background:rgba(var(--bg-rgb),0);
    border-bottom:1px solid transparent;
    height:68px;z-index:1050;
    transition:background .4s,border-color .4s,box-shadow .4s;
    backdrop-filter:blur(0px);
}
#v4-nav.scrolled{
    background:rgba(var(--bg-rgb),.92)!important;
    border-bottom-color:rgba(var(--gold-rgb),.15)!important;
    box-shadow:0 4px 30px rgba(0,0,0,.6)!important;
    backdrop-filter:blur(20px);
}
#v4-nav .navbar-brand{
    font-family:'Poppins',sans-serif!important;
    font-size:1.2rem;font-weight:900;color:var(--fg);letter-spacing:-.5px;
}
#v4-nav .navbar-brand span{color:var(--gold);}
#v4-nav .nav-link{
    color:rgba(var(--fg-rgb),.7)!important;font-size:.85rem;font-weight:500;
    padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;
}
#v4-nav .nav-link:hover{color:var(--gold)!important;background:rgba(var(--gold-rgb),.07);}

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
    background:linear-gradient(155deg,rgba(var(--bg-rgb),.82) 0%,rgba(var(--bg-rgb),.35) 55%,rgba(var(--bg-rgb),.88) 100%);
}
.v4-hero-content{position:relative;z-index:2;padding-top:100px;}
.v4-hero-eyebrow{
    display:inline-flex;align-items:center;gap:10px;
    font-size:.6rem;font-weight:700;letter-spacing:4px;text-transform:uppercase;
    color:var(--gold);margin-bottom:16px;opacity:0;
}
.v4-hero-eyebrow::before,.v4-hero-eyebrow::after{
    content:'';display:block;width:22px;height:1px;background:var(--gold);
}
.v4-split-line{overflow:hidden;display:block;}
.v4-split-inner{display:block;transform:translateY(110%);}
.v4-hero-title{
    font-size:clamp(1.5rem,2.8vw,2.6rem);font-weight:900;
    line-height:1.08;letter-spacing:-1px;color:var(--fg);margin-bottom:26px;
}
.v4-hero-title em{font-style:normal;color:var(--gold);}
.v4-fade-up{opacity:0;transform:translateY(24px);}
@media(max-width:576px){
    .v4-hero-title{font-size:1.4rem;letter-spacing:-.3px;}
    .v4-search{flex-direction:column;padding:8px;}
    .v4-search-f{padding:10px 14px;}
    .v4-search-btn{width:100%;justify-content:center;border-radius:9px;}
}

/* Search bar */
.v4-search{
    background:rgba(var(--bg-rgb),.85);
    border:1px solid rgba(var(--fg-rgb),.1);
    backdrop-filter:blur(20px);border-radius:14px;
    padding:7px;display:flex;max-width:540px;
    transition:border-color .3s,box-shadow .3s;
}
.v4-search:focus-within{
    border-color:rgba(var(--gold-rgb),.4);
    box-shadow:0 0 40px rgba(var(--gold-rgb),.15);
}
.v4-search-f{flex:1;display:flex;align-items:center;gap:10px;padding:11px 16px;}
.v4-search-f i{color:var(--gold);font-size:.85rem;}
.v4-search-f input{border:none;background:transparent;outline:none;font-size:.9rem;color:var(--fg);width:100%;}
.v4-search-f input::placeholder{color:rgba(var(--fg-rgb),.28);}
.v4-search-btn{
    background:var(--gold);color:var(--on-gold);border:none;border-radius:9px;
    padding:11px 22px;font-size:.85rem;font-weight:700;flex-shrink:0;
    cursor:none;transition:background .2s;
}
.v4-search-btn:hover{background:var(--gold-hi);}

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
    border-radius:18px;overflow:hidden;background:var(--surface);
    border:1px solid rgba(var(--fg-rgb),.06);display:flex;flex-direction:column;
    transition:transform .38s cubic-bezier(.22,1,.36,1),box-shadow .38s,border-color .38s;
    cursor:pointer;height:100%;
}
.bk-company-card:hover{
    transform:translateY(-10px);border-color:rgba(var(--gold-rgb),.45);
    box-shadow:0 28px 60px rgba(0,0,0,.55),0 0 0 1px rgba(var(--gold-rgb),.2);
}
.bk-cc-img{height:220px;position:relative;overflow:hidden;background:var(--surface-2);}
.bk-cc-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.22,1,.36,1);}
.bk-company-card:hover .bk-cc-img img{transform:scale(1.08);}
.bk-cc-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.05),rgba(0,0,0,.55));pointer-events:none;}
.bk-cc-badge{position:absolute;top:12px;{{ $isAr?'right':'left' }}:12px;z-index:3;background:rgba(var(--bg-rgb),.85);color:var(--gold);font-size:.65rem;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(var(--gold-rgb),.3);backdrop-filter:blur(8px);}
.bk-cc-rating{position:absolute;top:12px;{{ $isAr?'left':'right' }}:12px;z-index:3;background:rgba(var(--bg-rgb),.85);font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px;border:1px solid rgba(var(--gold-rgb),.25);backdrop-filter:blur(8px);display:flex;align-items:center;gap:4px;color:var(--fg);}
.bk-cc-rating i{color:var(--gold);font-size:.65rem;}
.bk-cc-body{padding:16px;flex:1;display:flex;flex-direction:column;gap:8px;}
.bk-cc-name{font-size:.97rem;font-weight:700;color:var(--fg);line-height:1.25;}
.bk-cc-location{font-size:.74rem;color:rgba(var(--fg-rgb),.4);display:flex;align-items:center;gap:5px;}
.bk-cc-location i{color:var(--gold);font-size:.68rem;}
.bk-cc-chips{display:flex;flex-wrap:wrap;gap:5px;}
.bk-cc-chip{background:rgba(var(--gold-rgb),.07);border:1px solid rgba(var(--gold-rgb),.18);border-radius:20px;padding:3px 10px;font-size:.65rem;font-weight:600;color:rgba(var(--gold-rgb),.9);}
.bk-cc-book{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;padding:11px;border-radius:10px;border:1.5px solid rgba(var(--gold-rgb),.35);background:rgba(var(--gold-rgb),.05);color:var(--gold);font-size:.83rem;font-weight:700;text-decoration:none;transition:all .28s;margin-top:auto;}
.bk-cc-book:hover,.bk-company-card:hover .bk-cc-book{background:var(--gold);color:var(--on-gold);border-color:var(--gold);box-shadow:0 6px 20px rgba(var(--gold-rgb),.35);}

/* Category cards */
.bk-cat-card{position:relative;width:140px;height:180px;border-radius:20px;overflow:hidden;text-decoration:none!important;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;border:1.5px solid rgba(var(--fg-rgb),.07);transition:transform .35s cubic-bezier(.22,1,.36,1),box-shadow .35s,border-color .35s;}
.bk-cat-card::before{content:'';position:absolute;inset:0;background:var(--cg,linear-gradient(135deg,var(--surface-2),var(--bg-2)));transition:opacity .35s;}
.bk-cat-card::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.18) 0%,rgba(0,0,0,.65) 100%);}
.bk-cat-card>*{position:relative;z-index:3;}
.bk-cat-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 24px 48px rgba(0,0,0,.6),0 0 0 1.5px rgba(var(--gold-rgb),.5);border-color:rgba(var(--gold-rgb),.5);}
.bk-cat-card-icon{width:60px;height:60px;border-radius:50%;background:rgba(var(--fg-rgb),.12);border:1.5px solid rgba(var(--fg-rgb),.2);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--fg);margin-bottom:12px;transition:all .3s;}
.bk-cat-card:hover .bk-cat-card-icon{background:var(--gold);color:var(--on-gold);border-color:var(--gold);transform:scale(1.1);}
.bk-cat-card-name{font-size:.8rem;font-weight:700;color:var(--fg);text-align:center;line-height:1.3;padding:0 8px;}
.bk-cat-card-count{font-size:.64rem;color:rgba(var(--fg-rgb),.55);margin-top:3px;}

/* Section labels */
.v4-eyebrow{display:flex;align-items:center;gap:10px;font-size:.65rem;font-weight:700;letter-spacing:3.5px;text-transform:uppercase;color:var(--gold);margin-bottom:14px;}
.v4-eyebrow::before{content:'';width:28px;height:1px;background:var(--gold);flex-shrink:0;}
.v4-sec-title{font-size:clamp(1.3rem,2.4vw,2.1rem);font-weight:900;line-height:1.1;letter-spacing:-.8px;color:var(--fg);margin-bottom:14px;}
.v4-sec-title em{font-style:normal;color:var(--gold);}
.v4-sec-sub{font-size:.92rem;color:rgba(var(--fg-rgb),.52);line-height:1.75;max-width:500px;}

/* Magnetic wrapper */
.v4-mag{display:inline-block;position:relative;}

/* Marquee strip */
#v4-strip{background:var(--bg-2);border-top:1px solid rgba(var(--fg-rgb),.05);border-bottom:1px solid rgba(var(--fg-rgb),.05);padding:16px 0;overflow:hidden;}
.v4-strip-track{display:flex;width:max-content;animation:v4mq 24s linear infinite;}
@if($isAr).v4-strip-track{animation-direction:reverse;}@endif
.v4-strip-item{display:flex;align-items:center;gap:10px;padding:0 32px;white-space:nowrap;font-size:.66rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(var(--fg-rgb),.25);}
.v4-strip-dot{width:4px;height:4px;border-radius:50%;background:var(--gold);flex-shrink:0;}
.v4-strip-icon{
    width:22px;height:22px;border-radius:50%;
    background:rgba(var(--gold-rgb),.12);border:1px solid rgba(var(--gold-rgb),.2);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.v4-strip-icon i{font-size:.55rem;color:var(--gold);}
@keyframes v4mq{from{transform:translateX(0);}to{transform:translateX(-50%);}}
@keyframes hsp{0%,100%{opacity:.3}50%{opacity:1}}

/* ══════════════════════════════
   CIRCULAR SECTION
══════════════════════════════ */
#v4-circle{background:var(--bg-2);padding:130px 0;position:relative;overflow:hidden;}
#v4-circle::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:800px;height:800px;border-radius:50%;background:radial-gradient(circle,rgba(var(--gold-rgb),.045) 0%,transparent 65%);pointer-events:none;}
.v4-circle-wrap{display:flex;align-items:center;justify-content:center;gap:90px;flex-wrap:wrap;}
.v4-circle-img-outer{position:relative;flex-shrink:0;width:400px;height:400px;}
.v4-circle-ring{position:absolute;inset:-22px;border-radius:50%;border:1px solid rgba(var(--gold-rgb),.16);animation:v4spin 18s linear infinite;}
.v4-circle-ring::before{content:'';position:absolute;top:-5px;left:50%;transform:translateX(-50%);width:10px;height:10px;border-radius:50%;background:var(--gold);box-shadow:0 0 16px var(--gold);}
.v4-circle-ring-2{position:absolute;inset:-46px;border-radius:50%;border:1px dashed rgba(var(--gold-rgb),.08);animation:v4spin 30s linear infinite reverse;}
@keyframes v4spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
.v4-circle-img{width:400px;height:400px;border-radius:50%;overflow:hidden;border:2px solid rgba(var(--gold-rgb),.2);box-shadow:0 0 70px rgba(var(--gold-rgb),.1),0 28px 70px rgba(0,0,0,.55);position:relative;z-index:1;}
.v4-circle-img img{width:100%;height:100%;object-fit:cover;transform:scale(1.05);transition:transform 8s linear;}
.v4-circle-img:hover img{transform:scale(1.13);}
.v4-circle-tag{position:absolute;z-index:2;background:rgba(var(--bg-rgb),.92);border:1px solid rgba(var(--fg-rgb),.07);border-radius:12px;padding:13px 17px;backdrop-filter:blur(12px);box-shadow:0 14px 36px rgba(0,0,0,.5);white-space:nowrap;}
.v4-ct-1{top:18px;{{ $isAr?'left':'right' }}:-36px;}
.v4-ct-2{bottom:56px;{{ $isAr?'right':'left' }}:-36px;}
.v4-ct-3{top:50%;{{ $isAr?'left':'right' }}:-56px;transform:translateY(-50%);}
.v4-ct-icon{width:34px;height:34px;border-radius:9px;background:rgba(var(--gold-rgb),.1);border:1px solid rgba(var(--gold-rgb),.2);display:flex;align-items:center;justify-content:center;margin-bottom:7px;}
.v4-ct-icon i{color:var(--gold);font-size:.82rem;}
.v4-ct-val{font-size:1.05rem;font-weight:800;color:var(--fg);line-height:1;}
.v4-ct-lbl{font-size:.65rem;color:rgba(var(--fg-rgb),.45);margin-top:2px;}
.v4-circle-text{flex:1;min-width:280px;max-width:460px;}

/* ══════════════════════════════
   PINNED SCROLL SECTIONS
   (GSAP pin:true — most reliable with Porto)
══════════════════════════════ */
/* Prevent Porto overflow:hidden from breaking GSAP pin */
#v4-phone-outer, #v4-ow-outer{ overflow:visible !important; }
#v4-phone-outer{ background:var(--bg); }
#v4-ow-outer   { background:var(--bg-2); }

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
    text-transform:uppercase; color:var(--gold);
    margin-bottom:14px; display:flex; align-items:center; gap:8px;
}
.v4-pl-eyebrow::before{ content:''; width:20px; height:1px; background:var(--gold); flex-shrink:0; }
.v4-pl-title{
    font-size:clamp(1.3rem,2.4vw,2.1rem); font-weight:900;
    line-height:1.05; letter-spacing:-1.2px; color:var(--fg); margin-bottom:12px;
}
.v4-pl-title em{ font-style:normal; color:var(--gold); }
.v4-pl-desc{ font-size:.88rem; color:rgba(var(--fg-rgb),.5); line-height:1.75; max-width:340px; }
.v4-pl-feats{ display:flex; flex-direction:column; gap:9px; margin-top:18px; }
.v4-pl-feat{ display:flex; align-items:center; gap:9px; font-size:.8rem; color:rgba(var(--fg-rgb),.45); }
.v4-pl-feat i{ color:var(--gold); font-size:.7rem; }

/* Visual (phone / card) */
#v4-phone-cw,#v4-ow-cw{
    display:flex; flex-direction:column; align-items:center; gap:14px; flex-shrink:0;
}
#v4-phone-heading,#v4-ow-heading{ text-align:center; }
.v4-phone-screen{ transform-origin:center center; }

/* Dots */
.v4-psdots{ display:flex; gap:8px; justify-content:center; margin-top:16px; }
.v4-psdot{ width:6px; height:6px; border-radius:50%; background:rgba(var(--fg-rgb),.2); transition:all .35s; }
.v4-psdot.active{ width:22px; border-radius:4px; background:var(--gold); }

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
.v4-pin-sec  { background:var(--bg); }
.v4-pin-sec-b{ background:var(--bg-2); }

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
    text-transform:uppercase;color:var(--gold);
    margin-bottom:14px;
    display:flex;align-items:center;gap:8px;
}
.v4-pin-eyebrow::before{content:'';width:20px;height:1px;background:var(--gold);flex-shrink:0;}

/* step title — big and bold */
.v4-pin-h{
    font-size:clamp(1.5rem,3vw,2.4rem);
    font-weight:900;line-height:1.08;
    letter-spacing:-1.2px;color:var(--fg);
    margin-bottom:14px;
}
.v4-pin-h em{font-style:normal;color:var(--gold);}

.v4-pin-desc{font-size:.9rem;color:rgba(var(--fg-rgb),.5);line-height:1.75;max-width:360px;}

.v4-pin-feats{display:flex;flex-direction:column;gap:10px;margin-top:22px;}
.v4-pin-feat{display:flex;align-items:center;gap:9px;font-size:.82rem;color:rgba(var(--fg-rgb),.45);}
.v4-pin-feat i{color:var(--gold);font-size:.7rem;}
.v4-pin-step.active .v4-pin-feat{color:rgba(var(--fg-rgb),.65);}

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
.v4-phone-frame{background:var(--bg-2);border-radius:42px;padding:13px;box-shadow:0 0 0 2px rgba(var(--fg-rgb),.07),0 55px 110px rgba(0,0,0,.8),0 0 55px rgba(var(--gold-rgb),.07);position:relative;}
.v4-phone-notch{width:90px;height:28px;background:var(--bg-2);border-radius:0 0 18px 18px;position:absolute;top:13px;left:50%;transform:translateX(-50%);z-index:10;}
.v4-phone-screen{border-radius:30px;overflow:hidden;position:relative;background:#0d0d0d;aspect-ratio:9/19.5;}
.v4-pslide{position:absolute;inset:0;opacity:0;transition:opacity .4s;}
.v4-pslide.active{opacity:1;}
/* App screen 1 – Home */
.v4-ps-hd{background:var(--bg-2);padding:44px 14px 14px;border-bottom:1px solid rgba(var(--fg-rgb),.05);}
.v4-ps-logo{font-size:1rem;font-weight:900;color:var(--fg);margin-bottom:1px;}
.v4-ps-logo em{font-style:normal;color:var(--gold);}
.v4-ps-tag{font-size:.6rem;color:rgba(var(--fg-rgb),.3);}
.v4-ps-search{background:var(--surface-2);border:1px solid rgba(var(--fg-rgb),.07);border-radius:9px;padding:9px 13px;margin:11px 14px;display:flex;align-items:center;gap:7px;font-size:.68rem;color:rgba(var(--fg-rgb),.3);}
.v4-ps-search i{color:var(--gold);font-size:.7rem;}
.v4-ps-cats{display:flex;gap:7px;padding:0 14px 10px;overflow-x:auto;}
.v4-ps-cat{background:var(--surface-2);border:1px solid rgba(var(--fg-rgb),.06);border-radius:18px;padding:5px 11px;white-space:nowrap;font-size:.62rem;color:rgba(var(--fg-rgb),.5);}
.v4-ps-cat.on{background:rgba(var(--gold-rgb),.1);border-color:rgba(var(--gold-rgb),.25);color:var(--gold);}
.v4-ps-items{padding:10px 14px;display:flex;flex-direction:column;gap:9px;}
.v4-ps-item{background:var(--surface-2);border-radius:11px;overflow:hidden;display:flex;gap:9px;align-items:center;padding:9px;}
.v4-ps-item-img{width:48px;height:48px;border-radius:9px;object-fit:cover;flex-shrink:0;}
.v4-ps-item-name{font-size:.68rem;font-weight:700;color:var(--fg);margin-bottom:2px;}
.v4-ps-item-cat{font-size:.58rem;color:rgba(var(--fg-rgb),.35);}
.v4-ps-item-rate{font-size:.58rem;color:var(--gold);}
/* Screen 2 – Book */
.v4-ps2-top{background:linear-gradient(180deg,var(--bg-2),#0d0d0d);padding:44px 14px 16px;}
.v4-ps2-title{font-size:.9rem;font-weight:800;color:var(--fg);margin-bottom:3px;}
.v4-ps2-sub{font-size:.6rem;color:rgba(var(--fg-rgb),.3);}
.v4-ps2-img{width:100%;height:100px;object-fit:cover;border-radius:11px;margin:10px 0;}
.v4-ps2-slots{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;padding:0 14px;}
.v4-ps2-slot{background:var(--surface-2);border:1px solid rgba(var(--fg-rgb),.06);border-radius:8px;padding:7px 4px;text-align:center;font-size:.58rem;color:rgba(var(--fg-rgb),.45);}
.v4-ps2-slot.on{background:rgba(var(--gold-rgb),.1);border-color:rgba(var(--gold-rgb),.25);color:var(--gold);font-weight:700;}
.v4-ps2-btn{margin:13px 14px 0;background:var(--gold);color:var(--on-gold);border:none;border-radius:11px;padding:13px;font-size:.74rem;font-weight:800;width:calc(100% - 28px);text-align:center;}
/* Screen 3 – Profile */
.v4-ps3-top{background:linear-gradient(180deg,rgba(var(--gold-rgb),.12),transparent);padding:44px 14px 20px;text-align:center;}
.v4-ps3-av{width:64px;height:64px;border-radius:50%;background:rgba(var(--gold-rgb),.1);border:2px solid rgba(var(--gold-rgb),.25);margin:0 auto 9px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:900;color:var(--gold);}
.v4-ps3-name{font-size:.9rem;font-weight:800;color:var(--fg);}
.v4-ps3-appts{display:flex;flex-direction:column;gap:9px;padding:14px;}
.v4-ps3-appt{background:var(--surface-2);border:1px solid rgba(var(--fg-rgb),.06);border-radius:11px;padding:11px;display:flex;justify-content:space-between;align-items:center;}
.v4-ps3-appt-name{font-size:.68rem;font-weight:700;color:var(--fg);margin-bottom:2px;}
.v4-ps3-appt-date{font-size:.58rem;color:rgba(var(--fg-rgb),.3);}
.v4-ps3-badge{background:rgba(var(--gold-rgb),.1);border:1px solid rgba(var(--gold-rgb),.22);color:var(--gold);font-size:.56rem;font-weight:700;padding:4px 8px;border-radius:18px;}

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
    border:1px solid rgba(var(--gold-rgb),.07);
    pointer-events:none; will-change:transform;
}
#v4-rv-ring::before{
    content:''; position:absolute; inset:60px; border-radius:50%;
    border:1px solid rgba(var(--gold-rgb),.05);
}
#v4-rv-ring::after{
    content:''; position:absolute; inset:130px; border-radius:50%;
    border:1px solid rgba(var(--gold-rgb),.04);
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
    background:rgba(var(--gold-rgb),.06);
    border:1px solid rgba(var(--gold-rgb),.18);
    border-radius:24px;
    display:flex; flex-direction:column; align-items:center; gap:14px;
}
.v4-rv-cta-dot{
    width:14px; height:14px; border-radius:50%;
    background:var(--gold); display:inline-block;
    box-shadow:0 0 0 6px rgba(var(--gold-rgb),.15), 0 0 0 12px rgba(var(--gold-rgb),.06);
    animation:v4ctapulse 2s ease infinite;
}
@keyframes v4ctapulse{
    0%,100%{ box-shadow:0 0 0 6px rgba(var(--gold-rgb),.15), 0 0 0 12px rgba(var(--gold-rgb),.06); }
    50%{     box-shadow:0 0 0 10px rgba(var(--gold-rgb),.22), 0 0 0 20px rgba(var(--gold-rgb),.06); }
}
.v4-rv-cta-text{
    font-size:clamp(.95rem,1.5vw,1.1rem); font-weight:800;
    color:var(--fg); line-height:1.35; max-width:180px;
}
.v4-rv-cta-text em{ font-style:normal; color:var(--gold); }
.v4-rv-cta-sub{
    font-size:.76rem; color:rgba(var(--fg-rgb),.35); max-width:160px; line-height:1.5;
}

/* review cards */
.v4-rv{
    background:rgba(var(--fg-rgb),.025);
    border:1px solid rgba(var(--fg-rgb),.07);
    border-radius:22px; padding:24px;
    transition:border-color .3s, transform .3s;
    will-change:transform,opacity;
}
.v4-rv:hover{ border-color:rgba(var(--gold-rgb),.22); transform:translateY(-4px); }
/* offset rows for scattered look */
.v4-rv:nth-child(1){ margin-top:40px; }
.v4-rv:nth-child(3){ margin-top:-20px; }
.v4-rv:nth-child(4){ margin-top:-10px; }
.v4-rv:nth-child(6){ margin-top:30px; }

.v4-rv-stars{ display:flex; gap:3px; margin-bottom:10px; }
.v4-rv-stars i{ color:var(--gold); font-size:.7rem; }
.v4-rv-q{
    font-size:.84rem; color:rgba(var(--fg-rgb),.52); line-height:1.7;
    margin-bottom:16px; padding-inline-start:14px; position:relative;
}
.v4-rv-q::before{
    content:'"'; position:absolute; {{ $isAr?'right':'left' }}:0; top:-4px;
    font-size:2.2rem; color:rgba(var(--gold-rgb),.14); line-height:1;
}
.v4-rv-au{ display:flex; align-items:center; gap:10px; }
.v4-rv-av{
    width:38px; height:38px; border-radius:50%;
    background:rgba(var(--gold-rgb),.1); border:1.5px solid rgba(var(--gold-rgb),.22);
    display:flex; align-items:center; justify-content:center;
    font-size:.95rem; font-weight:800; color:var(--gold); flex-shrink:0;
}
.v4-rv-name{ font-size:.82rem; font-weight:700; color:var(--fg); }
.v4-rv-role{ font-size:.66rem; color:rgba(var(--fg-rgb),.3); margin-top:1px; }

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
#v4-partners-outer{height:190vh;position:relative;background:var(--bg-2);}
#v4-partners-sticky{
    position:sticky;top:0;height:100vh;
    display:flex;align-items:center;overflow:hidden;
}
.v4-partners-inner{width:100%;}
.v4-partners-hd{text-align:center;margin-bottom:56px;}
.v4-prow{display:flex;overflow:hidden;border-top:1px solid rgba(var(--fg-rgb),.05);border-bottom:1px solid rgba(var(--fg-rgb),.05);padding:18px 0;margin-bottom:14px;}
.v4-prow-track{display:flex;width:max-content;animation:v4mq 22s linear infinite;}
.v4-prow-track-rev{display:flex;width:max-content;animation:v4mq 28s linear infinite reverse;}
.v4-prow:last-child{border-top:none;}
.v4-partner-item{display:flex;align-items:center;justify-content:center;padding:18px 50px;border-inline-end:1px solid rgba(var(--fg-rgb),.05);}
.v4-partner-name{font-size:.88rem;font-weight:700;color:rgba(var(--fg-rgb),.2);letter-spacing:1px;white-space:nowrap;transition:color .3s;}
.v4-partner-item:hover .v4-partner-name{color:var(--gold);}

/* ══════════════════════════════
   STATS
══════════════════════════════ */
#v4-stats{background:var(--bg);border-top:1px solid rgba(var(--fg-rgb),.05);border-bottom:1px solid rgba(var(--fg-rgb),.05);padding:70px 0;}
.v4-stats-row{display:grid;grid-template-columns:repeat(4,1fr);}
.v4-stat{text-align:center;padding:30px 20px;border-inline-end:1px solid rgba(var(--fg-rgb),.06);}
.v4-stat:last-child{border-inline-end:none;}
.v4-stat-num{font-size:clamp(1.6rem,2.8vw,2.4rem);font-weight:900;color:var(--gold);line-height:1;letter-spacing:-2px;margin-bottom:7px;}
.v4-stat-lbl{font-size:.78rem;color:rgba(var(--fg-rgb),.45);}
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
    border:1px solid rgba(var(--gold-rgb),.35);
    background:radial-gradient(circle at 30% 30%, rgba(var(--gold-rgb),.08), transparent 70%);
}
.v4-shape-ring{
    border-radius:50%;
    border:1px solid rgba(var(--gold-rgb),.2);
    background:transparent;
}
.v4-shape-tri{
    width:0; height:0;
    background:transparent !important;
    border:none !important;
}
.v4-shape-dot{
    border-radius:50%;
    background:rgba(var(--gold-rgb),.5);
    border:none;
}
.v4-shape-sq{
    border:1px solid rgba(var(--fg-rgb),.08);
    background:rgba(var(--fg-rgb),.02);
    transform-origin:center;
}
/* Each section needs position:relative for absolute children */
#v4-hero{ position:relative; }
#v4-phone-outer,#v4-ow-outer{ position:relative; }
#v4-cta{ position:relative; }

#v4-cta{background:var(--bg);padding:130px 0;position:relative;overflow:hidden;}
#v4-cta::before{content:'';position:absolute;top:-160px;left:50%;transform:translateX(-50%);width:650px;height:650px;border-radius:50%;background:radial-gradient(circle,rgba(var(--gold-rgb),.06) 0%,transparent 65%);pointer-events:none;}
.v4-cta-title{font-size:clamp(1.4rem,2.8vw,2.4rem);font-weight:900;line-height:1.05;letter-spacing:-2px;color:var(--fg);margin-bottom:20px;}
.v4-cta-title em{font-style:normal;color:var(--gold);}
.btn-v4-gold{background:var(--gold);color:var(--on-gold);padding:15px 38px;border-radius:10px;font-size:.92rem;font-weight:800;border:none;cursor:none;display:inline-flex;align-items:center;gap:9px;transition:all .2s;text-decoration:none;}
.btn-v4-gold:hover{background:var(--gold-hi);transform:translateY(-3px);color:var(--on-gold);}
.btn-v4-out{border:1.5px solid rgba(var(--gold-rgb),.3);color:var(--gold);padding:15px 38px;border-radius:10px;font-size:.92rem;font-weight:700;background:transparent;display:inline-flex;align-items:center;gap:9px;transition:all .2s;text-decoration:none;}
.btn-v4-out:hover{background:rgba(var(--gold-rgb),.08);border-color:var(--gold);transform:translateY(-3px);color:var(--gold);}

/* ══════════════════════════════
   DASHBOARD CARD (owners visual)
══════════════════════════════ */
.v4-dash{background:var(--bg);border:1px solid rgba(var(--fg-rgb),.07);border-radius:20px;padding:22px;box-shadow:0 30px 80px rgba(0,0,0,.7);width:290px;position:relative;}
.v4-dash-slide{position:absolute;inset:0;padding:22px;opacity:0;transition:opacity .5s ease;border-radius:20px;background:var(--bg);}
.v4-dash-slide.active{opacity:1;position:relative;inset:auto;padding:0;}
.v4-dash-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.v4-dash-title{font-size:.88rem;font-weight:800;color:var(--fg);}
.v4-dash-sub{font-size:.6rem;color:rgba(var(--fg-rgb),.3);margin-top:2px;}
.v4-dash-badge{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.18);color:#4ade80;font-size:.56rem;font-weight:700;padding:3px 9px;border-radius:20px;display:flex;align-items:center;gap:4px;}
.v4-dash-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:#4ade80;animation:v4pulse 1.4s ease infinite;}
.v4-dash-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:18px;}
.v4-dash-stat{background:var(--surface-2);border-radius:10px;padding:11px 8px;text-align:center;border:1px solid rgba(var(--fg-rgb),.04);}
.v4-dash-stat-n{font-size:.9rem;font-weight:900;color:var(--gold);margin-bottom:3px;}
.v4-dash-stat-l{font-size:.52rem;color:rgba(var(--fg-rgb),.32);line-height:1.3;}
.v4-dash-sec-title{font-size:.58rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(var(--fg-rgb),.28);margin-bottom:10px;}
.v4-dash-branches{display:flex;flex-direction:column;gap:10px;margin-bottom:18px;}
.v4-dash-branch{display:flex;align-items:center;gap:10px;}
.v4-dash-branch-dot{width:6px;height:6px;border-radius:50%;background:var(--gold);flex-shrink:0;}
.v4-dash-branch-name{font-size:.72rem;font-weight:700;color:var(--fg);margin-bottom:1px;}
.v4-dash-branch-sub{font-size:.58rem;color:rgba(var(--fg-rgb),.28);}
.v4-dash-branch-bar{height:3px;background:rgba(var(--fg-rgb),.06);border-radius:2px;margin-top:4px;overflow:hidden;}
.v4-dash-branch-fill{height:100%;background:linear-gradient(90deg,var(--gold),rgba(var(--gold-rgb),.2));border-radius:2px;}
.v4-dash-branch-pct{font-size:.66rem;font-weight:700;color:var(--gold);flex-shrink:0;}
.v4-dash-chart-wrap{border-top:1px solid rgba(var(--fg-rgb),.05);padding-top:12px;}
.v4-dash-chart-lbl{font-size:.58rem;color:rgba(var(--fg-rgb),.28);margin-bottom:8px;}
.v4-dash-bars{display:flex;align-items:flex-end;gap:4px;height:48px;}
.v4-dash-bar-col{flex:1;height:100%;display:flex;align-items:flex-end;}
.v4-dash-bar{width:100%;background:linear-gradient(180deg,rgba(var(--gold-rgb),.6),rgba(var(--gold-rgb),.05));border-radius:3px 3px 0 0;min-height:3px;}
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
    #v4-phone-sec .v4-pin-visual{ background:var(--bg); }
    #v4-ow-sec    .v4-pin-visual{ background:var(--bg-2); }

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

/* ═══════════════════════════════════════════
   THEME TOGGLE BUTTON
═══════════════════════════════════════════ */
.v4-theme-toggle{
    width:40px;height:40px;border-radius:50%;flex-shrink:0;
    border:1px solid rgba(var(--gold-rgb),.35);
    background:rgba(var(--gold-rgb),.06);color:var(--gold);cursor:none;
    display:inline-flex;align-items:center;justify-content:center;
    position:relative;overflow:hidden;
    transition:border-color .3s,background .3s,transform .35s;
}
.v4-theme-toggle:hover{background:rgba(var(--gold-rgb),.15);border-color:var(--gold);transform:rotate(-18deg);}
.v4-theme-toggle i{position:absolute;font-size:.92rem;transition:transform .5s cubic-bezier(.22,1,.36,1),opacity .35s;}
.v4-theme-toggle i[data-icon="light"]{transform:translateY(150%) rotate(-90deg);opacity:0;}
:root[data-theme="light"] .v4-theme-toggle i[data-icon="dark"]{transform:translateY(-150%) rotate(90deg);opacity:0;}
:root[data-theme="light"] .v4-theme-toggle i[data-icon="light"]{transform:none;opacity:1;}
@media(max-width:768px){.v4-theme-toggle{cursor:pointer;}}

/* ═══════════════════════════════════════════
   SVG DECORATIONS — scissors & spa stones
   (drift continuously + parallax on scroll via GSAP)
═══════════════════════════════════════════ */
.v4-deco{opacity:0;}                 /* uses .v4-shape absolute positioning; GSAP fades in */
.v4-deco-float{display:block;width:100%;height:100%;will-change:transform;animation:v4float 7s ease-in-out infinite;}
.v4-deco svg{width:100%;height:100%;display:block;filter:drop-shadow(0 8px 20px rgba(0,0,0,.22));}
.v4-deco.is-scissors{color:var(--deco);}
.v4-deco.is-stones{color:var(--deco-2);}
@keyframes v4float{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-18px) rotate(7deg)}}
@media(max-width:820px){.v4-deco{display:none;}}   /* keep small screens clean & fast */

/* content sits above decorations */
#v4-circle,#v4-cats,#v4-featured,#v4-reviews-outer,#v4-phone-outer,#v4-ow-outer{position:relative;}
#v4-circle .container,#v4-cats .container,#v4-featured .container,#v4-cta .container{position:relative;z-index:2;}
#v4-reviews-outer .v4-rv-heading,#v4-rv-grid{position:relative;z-index:2;}
#v4-psteps-wrap,#v4-owsteps-wrap,#v4-phone-cw,#v4-ow-cw{position:relative;z-index:2;}

/* ═══════════════════════════════════════════
   LOAD MORE
═══════════════════════════════════════════ */
.v4-loadmore-wrap{display:flex;justify-content:center;margin-top:54px;}
.v4-loadmore{
    display:inline-flex;align-items:center;gap:12px;cursor:none;
    background:rgba(var(--gold-rgb),.06);color:var(--gold);
    border:1.5px solid rgba(var(--gold-rgb),.4);border-radius:40px;
    padding:15px 42px;font-size:.9rem;font-weight:800;letter-spacing:.3px;
    transition:background .3s,color .3s,border-color .3s,box-shadow .3s,transform .2s;
}
.v4-loadmore:hover{background:var(--gold);color:var(--on-gold);border-color:var(--gold);box-shadow:0 14px 34px rgba(var(--gold-rgb),.32);transform:translateY(-2px);}
.v4-loadmore:disabled{opacity:.5;cursor:default;transform:none;box-shadow:none;background:rgba(var(--gold-rgb),.06);color:var(--gold);}
.v4-lm-spin{width:16px;height:16px;border:2px solid rgba(var(--gold-rgb),.3);border-top-color:var(--gold);border-radius:50%;display:none;animation:v4spin2 .7s linear infinite;}
.v4-loadmore.loading .v4-lm-spin{display:block;}
.v4-loadmore.loading .v4-lm-ico{display:none;}
@keyframes v4spin2{to{transform:rotate(360deg)}}
@media(max-width:768px){.v4-loadmore{cursor:pointer;}}

/* ═══════════════════════════════════════════
   LIGHT-MODE EDGE FIXES
═══════════════════════════════════════════ */
:root[data-theme="light"] .bk-cat-card{--fg:#fff;--fg-rgb:255,255,255;}  /* vivid dark tiles keep light text */
:root[data-theme="light"] #v4-hero{--gold-hi:#C9A227;}
:root[data-theme="light"] .v4-hero-video video{filter:saturate(1.05) brightness(.92);}
/* Navbar is transparent over the dark hero video → keep its text light until scrolled */
:root[data-theme="light"] #v4-nav:not(.scrolled){--fg:#ffffff;--fg-rgb:255,255,255;}
:root[data-theme="light"] #v4-nav:not(.scrolled) .v4-theme-toggle{color:#fff;border-color:rgba(255,255,255,.45);}
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
        <a href="{{ route('front.index4') }}" class="navbar-brand" style="font-family:'Poppins',sans-serif!important;font-size:1.7rem;font-weight:900;color:var(--fg);letter-spacing:-1px;text-decoration:none;">
            {{ $isAr ? 'بوكسي' : 'Booksy' }}<span style="color:var(--gold);">.</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#v4NavMenu" style="border:1px solid rgba(var(--gold-rgb),.35);color:var(--gold);">
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
                <button type="button" id="v4-theme-toggle" class="v4-theme-toggle" aria-label="{{ $isAr ? 'تبديل الوضع الليلي/النهاري' : 'Toggle dark / light mode' }}" title="{{ $isAr ? 'الوضع الليلي/النهاري' : 'Dark / Light' }}">
                    <i class="fas fa-moon" data-icon="dark"></i>
                    <i class="fas fa-sun"  data-icon="light"></i>
                </button>
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
        {{-- No direct media URL on the page: the video is fetched as a blob by JS,
             so download-manager extensions (IDM etc.) don't detect it --}}
        <video autoplay muted loop playsinline preload="none" data-src="{{ asset('frontend/video/hero.bin') }}"></video>
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
                            ? [[number_format($stats['salons']),'صالون وعيادة'],[number_format($stats['bookings']),'حجز عبر المنصة'],[number_format($stats['services']),'خدمة متاحة']]
                            : [[number_format($stats['salons']),'Salons & Clinics'],[number_format($stats['bookings']),'Bookings Made'],[number_format($stats['services']),'Services Available']];
                    @endphp
                    @foreach($chips as $c)
                        <div style="background:rgba(var(--fg-rgb),.05);border:1px solid rgba(var(--fg-rgb),.08);border-radius:30px;padding:6px 18px;display:flex;align-items:center;gap:7px;">
                            <span style="font-size:.88rem;font-weight:800;color:var(--gold);">{{ $c[0] }}</span>
                            <span style="font-size:.7rem;color:rgba(var(--fg-rgb),.4);">{{ $c[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div style="position:absolute;bottom:36px;{{ $isAr?'right':'left' }}:44px;z-index:3;display:flex;align-items:center;gap:12px;opacity:0;" id="hScroll">
        <div style="width:46px;height:1px;background:linear-gradient({{ $isAr?'left':'right' }},var(--gold),transparent);animation:hsp 2.5s ease-in-out infinite;"></div>
        <span style="font-size:.6rem;letter-spacing:3px;text-transform:uppercase;color:rgba(var(--fg-rgb),.3);">{{ $isAr?'تصفح':'Scroll' }}</span>
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
                    <img src="{{ asset('frontend/img/stock/1560066984-138dadb4c035-w900.jpg') }}" alt="salon">
                </div>
                @if($stats['rating'] > 0)
                <div class="v4-circle-tag v4-ct-1 appear-animation" data-appear-animation="fadeInRight">
                    <div class="v4-ct-icon"><i class="fas fa-star"></i></div>
                    <div class="v4-ct-val">{{ number_format($stats['rating'], 1) }}</div>
                    <div class="v4-ct-lbl">{{ $isAr ? 'متوسط التقييم' : 'Avg Rating' }}</div>
                </div>
                @endif
                <div class="v4-circle-tag v4-ct-2 appear-animation" data-appear-animation="fadeInLeft" data-appear-animation-delay="150">
                    <div class="v4-ct-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="v4-ct-val">{{ number_format($stats['bookings']) }}</div>
                    <div class="v4-ct-lbl">{{ $isAr ? 'حجز عبر المنصة' : 'Bookings' }}</div>
                </div>
                <div class="v4-circle-tag v4-ct-3 appear-animation" data-appear-animation="fadeInRight" data-appear-animation-delay="300">
                    <div class="v4-ct-icon"><i class="fas fa-store"></i></div>
                    <div class="v4-ct-val">{{ number_format($stats['salons']) }}</div>
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
                                <div class="v4-ps-item"><img class="v4-ps-item-img" src="{{ asset('frontend/img/stock/1522337360788-8b13dee7a37e-w100.jpg') }}" alt=""><div><div class="v4-ps-item-name">{{ $isAr ? 'صالون لوكس' : 'Luxe Salon' }}</div><div class="v4-ps-item-cat">{{ $isAr ? 'صالون' : 'Salon' }}</div><div class="v4-ps-item-rate">★ 4.9</div></div></div>
                                <div class="v4-ps-item"><img class="v4-ps-item-img" src="{{ asset('frontend/img/stock/1570172619644-dfd03ed5d881-w100.jpg') }}" alt=""><div><div class="v4-ps-item-name">{{ $isAr ? 'سبا رويال' : 'Royal Spa' }}</div><div class="v4-ps-item-cat">{{ $isAr ? 'سبا' : 'Spa' }}</div><div class="v4-ps-item-rate">★ 4.8</div></div></div>
                                <div class="v4-ps-item"><img class="v4-ps-item-img" src="{{ asset('frontend/img/stock/1487412947147-5cebf100ffc2-w100.jpg') }}" alt=""><div><div class="v4-ps-item-name">{{ $isAr ? 'عيادة جمال' : 'Skin Clinic' }}</div><div class="v4-ps-item-cat">{{ $isAr ? 'عيادة' : 'Clinic' }}</div><div class="v4-ps-item-rate">★ 4.7</div></div></div>
                            </div>
                        </div>
                        <div class="v4-pslide" id="v4ps2">
                            <div class="v4-ps2-top">
                                <div class="v4-ps2-title">{{ $isAr ? 'احجز موعدك' : 'Book Appointment' }}</div>
                                <div class="v4-ps2-sub">{{ $isAr ? 'صالون لوكس — قص + صبغة' : 'Luxe Salon — Cut + Color' }}</div>
                                <img class="v4-ps2-img" src="{{ asset('frontend/img/stock/1522337360788-8b13dee7a37e-w400.jpg') }}" alt="">
                            </div>
                            <div style="padding:10px 14px 6px;font-size:.62rem;font-weight:700;color:rgba(var(--fg-rgb),.4);">{{ $isAr ? 'اختر الوقت' : 'Choose Time' }}</div>
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
                            <div style="padding:10px 14px 5px;font-size:.62rem;font-weight:700;color:var(--gold);">{{ $isAr ? 'مواعيدي' : 'My Appointments' }}</div>
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
                        ? [['لينا','قص وتصفيف','09:00–17:00','var(--gold)'],['ريم','تلوين','10:00–18:00','#4ade80'],['سارة','مانيكير','11:00–19:00','var(--gold)'],['نور','سبا','08:00–16:00','#4ade80']]
                        : [['Lena','Cut & Style','09:00–17:00','var(--gold)'],['Reem','Coloring','10:00–18:00','#4ade80'],['Sara','Manicure','11:00–19:00','var(--gold)'],['Nour','Spa','08:00–16:00','#4ade80']]; @endphp
                    <div style="display:flex;flex-direction:column;gap:9px;margin-bottom:16px;">
                        @foreach($staff as [$sn,$ss,$sh,$sc])
                        <div style="display:flex;align-items:center;gap:10px;background:var(--surface-2);border-radius:10px;padding:10px 12px;border:1px solid rgba(var(--fg-rgb),.04);">
                            <div style="width:28px;height:28px;border-radius:50%;background:rgba(var(--gold-rgb),.1);border:1px solid rgba(var(--gold-rgb),.2);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:var(--gold);flex-shrink:0;">{{ mb_substr($sn,0,1) }}</div>
                            <div style="flex:1"><div style="font-size:.72rem;font-weight:700;color:var(--fg);">{{ $sn }}</div><div style="font-size:.6rem;color:rgba(var(--fg-rgb),.3);">{{ $ss }}</div></div>
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
                        <div class="v4-dash-badge" style="color:var(--gold);background:rgba(var(--gold-rgb),.08);border-color:rgba(var(--gold-rgb),.18);">{{ $isAr ? '+23%' : '+23%' }}</div>
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
                            <div style="font-size:.7rem;color:rgba(var(--fg-rgb),.45);min-width:90px;">{{ $sn }}</div>
                            <div style="flex:1;height:4px;background:rgba(var(--fg-rgb),.06);border-radius:2px;overflow:hidden;">
                                <div style="height:100%;width:{{ $sp }};background:linear-gradient(90deg,var(--gold),rgba(var(--gold-rgb),.3));border-radius:2px;"></div>
                            </div>
                            <div style="font-size:.7rem;color:var(--gold);font-weight:700;">{{ $sp }}</div>
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
<section id="v4-cats" style="background:var(--bg-2);padding:100px 0;">
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
        <div style="overflow-x:auto;padding-bottom:8px;scrollbar-width:thin;scrollbar-color:rgba(var(--gold-rgb),.3) transparent;">
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
            @php $sts = collect($isAr
                ? [[$stats['salons'],'صالون وعيادة'],[$stats['bookings'],'حجز عبر المنصة'],[$stats['services'],'خدمة متاحة'],[$stats['cities'],'منطقة']]
                : [[$stats['salons'],'Salons & Clinics'],[$stats['bookings'],'Bookings Made'],[$stats['services'],'Services Available'],[$stats['cities'],'Areas']])
                ->filter(fn($s) => $s[0] > 0)->values();
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
<section id="v4-featured" style="background:var(--bg-2);padding:110px 0;">
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
        <div class="row g-4" id="v4-grid">
            @forelse($branches as $i => $branch)
                @include('front.partials._branch_card', ['branch' => $branch, 'i' => $i])
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

        @if($branches->hasMorePages())
        <div class="v4-loadmore-wrap">
            <button type="button" id="v4-loadmore" class="v4-loadmore"
                    data-url="{{ route('front.branches.more') }}"
                    data-next="{{ $branches->currentPage() + 1 }}">
                <span class="v4-lm-ico"><i class="fas fa-plus"></i></span>
                <span class="v4-lm-spin"></span>
                <span class="v4-lm-label">{{ $isAr ? 'تحميل المزيد' : 'Load More' }}</span>
            </button>
        </div>
        @endif
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
<script src="{{ asset('vendor/gsap/gsap.min.js') }}"></script>
<script src="{{ asset('vendor/gsap/ScrollTrigger.min.js') }}"></script>
<script src="{{ asset('vendor/lenis/lenis.min.js') }}"></script>

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
/* Real loader: the progress bar tracks actual readiness of the hero video,
   so the page is revealed only when the video can play — no fake waiting. */
const heroVideo = document.querySelector('.v4-hero-video video');
let assetsReady = false;
const markReady = () => { assetsReady = true; };
if (heroVideo && heroVideo.readyState < 3) {
    heroVideo.addEventListener('canplaythrough', markReady, { once: true });
    heroVideo.addEventListener('error', markReady, { once: true });
} else {
    markReady();
}
setTimeout(markReady, 7000); // never trap the visitor on a very slow connection

/* Fetch the video as a raw blob: no media URL exists in the DOM (defeats IDM-style
   download buttons) and the loader below shows the real download percentage. */
(async () => {
    if (!heroVideo || !heroVideo.dataset.src) return;
    try {
        const res = await fetch(heroVideo.dataset.src);
        const total = +res.headers.get('content-length') || 0;
        if (res.body && total) {
            const reader = res.body.getReader();
            const parts = [];
            let got = 0;
            for (;;) {
                const { done, value } = await reader.read();
                if (done) break;
                parts.push(value);
                got += value.length;
                window.__vidPct = Math.round(got / total * 100);
            }
            heroVideo.src = URL.createObjectURL(new Blob(parts, { type: 'video/webm' }));
        } else {
            heroVideo.src = URL.createObjectURL(await res.blob());
        }
        window.__vidPct = 100;
        heroVideo.play().catch(() => {});
    } catch (e) {
        markReady(); // video failed — reveal the page anyway
    }
})();

gsap.timeline()
    .to(ldW, {opacity:1, y:0, duration:.35, ease:'power2.out'})
    .to(ldB, {y:'0%',   duration:.5, ease:'power3.out'}, .1);
gsap.to(ldSw, {left:'100%', opacity:1, duration:.9, ease:'power1.inOut', repeat:-1, repeatDelay:.2,
    onStart(){ ldSw.style.opacity='1'; }});
let cnt=0;
const ci=setInterval(()=>{
    // follow the real video download percentage, then finish when it can play
    let cap;
    if (assetsReady) cap = 100;
    else if (typeof window.__vidPct === 'number') cap = Math.min(96, Math.max(cnt, window.__vidPct));
    else cap = 88;
    if (cnt < cap) cnt = Math.min(cap, cnt + (assetsReady ? 10 : 4));
    ldF.style.width=cnt+'%'; ldP.textContent=cnt+'%';
    if (cnt>=100){ clearInterval(ci); startExit(); }
},28);
function startExit(){
    // Hidden/background tab: rAF is paused so GSAP can't animate — just reveal the page
    if (document.hidden) { ldr.style.display='none'; heroIn(); return; }
    gsap.timeline()
        .to([ldW,ldP],{y:-18,opacity:0,duration:.25,ease:'power2.in',stagger:.04})
        .to(ldB,{y:'-110%',duration:.35,ease:'power3.in'},.05)
        .to(ldWp,{scaleY:1,duration:.4,ease:'power3.inOut'},.2)
        .to(ldr,{opacity:0,duration:.25,onComplete(){ldr.style.display='none';heroIn();}}, .5);
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

{{-- ══ THEME TOGGLE · DECORATIONS · LOAD-MORE ══ --}}
<script>
(function(){
/* ─── Theme toggle ─── */
var root=document.documentElement, tbtn=document.getElementById('v4-theme-toggle');
if(tbtn){ tbtn.addEventListener('click',function(){
    var next = root.getAttribute('data-theme')==='light' ? 'dark' : 'light';
    root.setAttribute('data-theme', next);
    try{ localStorage.setItem('bk-theme', next); }catch(e){}
    if(window.ScrollTrigger) ScrollTrigger.refresh();
}); }

/* ─── Reliable navbar solidify (Lenis can swallow native scroll events,
       so drive the .scrolled state from an IntersectionObserver sentinel) ─── */
(function(){
  var navEl=document.getElementById('v4-nav');
  if(!navEl || !('IntersectionObserver' in window)) return;
  var s=document.createElement('div');
  s.style.cssText='position:absolute;top:0;left:0;width:1px;height:48px;pointer-events:none;opacity:0;';
  document.body.appendChild(s);
  new IntersectionObserver(function(es){
    navEl.classList.toggle('scrolled', !es[0].isIntersecting);
  },{threshold:0}).observe(s);
})();

/* ─── Floating SVG decorations: scissors + spa stones ─── */
if(window.gsap && window.matchMedia('(min-width:821px)').matches){
  var SCISSORS='<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="14" cy="46" r="7"/><circle cx="27" cy="50" r="7"/><path d="M18.5 42 L53 13"/><path d="M22 45 L53 21"/><circle cx="33" cy="33" r="2" fill="currentColor" stroke="none"/></svg>';
  var STONES='<svg viewBox="0 0 64 64" fill="currentColor" stroke="none"><ellipse cx="32" cy="47" rx="20" ry="7"/><ellipse cx="32" cy="35" rx="15" ry="5.6" opacity=".82"/><ellipse cx="32" cy="25" rx="10.6" ry="4.6" opacity=".66"/><ellipse cx="32" cy="16.5" rx="6.6" ry="3.4" opacity=".52"/></svg>';
  /* [type, section, css(pos+size), yMove, xMove, rot, floatDelay] */
  var decos=[
    ['scissors','#v4-circle',       'top:11%;left:4%;width:88px;height:88px;',    -120, 26,  22, 0  ],
    ['stones',  '#v4-circle',       'bottom:12%;right:5%;width:76px;height:76px;', -90,-18, -12, .8 ],
    ['scissors','#v4-cats',         'top:16%;right:4%;width:72px;height:72px;',   -100, 20,  30, .4 ],
    ['stones',  '#v4-reviews-outer','top:14%;left:4%;width:82px;height:82px;',    -110, 22, -16, .6 ],
    ['scissors','#v4-featured',     'top:10%;left:3%;width:78px;height:78px;',     -90, 18, -24, .2 ],
    ['stones',  '#v4-featured',     'bottom:8%;right:4%;width:70px;height:70px;', -120,-14,  20, 1  ],
    ['stones',  '#v4-phone-outer',  'top:14%;right:6%;width:74px;height:74px;',    -90, 20, -25, .5 ],
    ['scissors','#v4-ow-outer',     'bottom:15%;left:5%;width:80px;height:80px;', -110,-20,  40, .3 ],
    ['scissors','#v4-cta',          'top:14%;right:9%;width:74px;height:74px;',    -80, 15, -18, .7 ],
    ['stones',  '#v4-cta',          'bottom:18%;left:9%;width:66px;height:66px;', -100, 10,  26, .9 ]
  ];
  decos.forEach(function(d){
    var sec=document.querySelector(d[1]); if(!sec) return;
    var el=document.createElement('div');
    el.className='v4-shape v4-deco is-'+d[0];
    el.style.cssText=d[2];
    var inner=document.createElement('span');
    inner.className='v4-deco-float';
    inner.style.animationDelay=(d[6]||0)+'s';
    inner.innerHTML = d[0]==='scissors' ? SCISSORS : STONES;
    el.appendChild(inner); sec.appendChild(el);
    gsap.fromTo(el,{opacity:0,scale:.6},{opacity:1,scale:1,duration:1.4,ease:'power2.out',
        scrollTrigger:{trigger:sec,start:'top 88%',toggleActions:'play none none reverse'}});
    gsap.to(el,{y:d[3],x:d[4],rotate:d[5],ease:'none',
        scrollTrigger:{trigger:sec,start:'top bottom',end:'bottom top',scrub:2}});
  });
  if(window.ScrollTrigger) ScrollTrigger.refresh();
}

/* ─── Load more (featured places) ─── */
var lm=document.getElementById('v4-loadmore');
if(lm){
  lm.addEventListener('click',function(){
    if(lm.classList.contains('loading')||lm.disabled) return;
    var next=lm.getAttribute('data-next'), grid=document.getElementById('v4-grid');
    lm.classList.add('loading');
    var start=grid.children.length;
    fetch(lm.getAttribute('data-url')+'?page='+next,{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();})
      .then(function(j){
        if(j.html){
          grid.insertAdjacentHTML('beforeend',j.html);
          /* jQuery.appear won't observe dynamically-added nodes → reveal them ourselves */
          var added=Array.prototype.slice.call(grid.children,start);
          added.forEach(function(col){
            var card=col.querySelector('.appear-animation');
            if(card){ card.classList.remove('appear-animation'); card.style.opacity=''; }
          });
          if(window.gsap){ gsap.from(added,{opacity:0,y:26,duration:.6,stagger:.07,ease:'power2.out'}); }
        }
        lm.classList.remove('loading');
        if(j.hasMore){ lm.setAttribute('data-next',j.nextPage); }
        else{ lm.disabled=true; lm.querySelector('.v4-lm-label').textContent=(document.documentElement.lang==='ar'?'عرضت كل الأماكن':'All places shown'); }
        if(window.ScrollTrigger) ScrollTrigger.refresh();
      })
      .catch(function(){ lm.classList.remove('loading'); });
  });
}
})();
</script>

</div>{{-- /body --}}
</body>
</html>
