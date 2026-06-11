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
    $cgMap = ['salon'=>'#7f1d52','hair'=>'#7f1d52','barber'=>'#1c1917','spa'=>'#064e3b',
              'massage'=>'#064e3b','clinic'=>'#1e3a5f','dental'=>'#155e75','laser'=>'#1e3a5f',
              'beauty'=>'#5b21b6','makeup'=>'#5b21b6','lash'=>'#831843','brow'=>'#831843',
              'nail'=>'#9d174d','gym'=>'#92400e','tattoo'=>'#1f2937','wedding'=>'#7c2d12'];
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $isAr ? 'بوكسي – احجز موعدك' : 'Booksy – Book Top Salons & Clinics' }} (V2)</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
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
*{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-weight:800!important;}
@endif
:root{
    --gold:#C9A227;
    --gold-dim:rgba(201,162,39,.12);
    --gold-border:rgba(201,162,39,.25);
    --bg:#080808;
    --bg2:#101010;
    --bg3:#141414;
    --text:rgba(255,255,255,.85);
    --text-dim:rgba(255,255,255,.45);
}
*{box-sizing:border-box;}
html,body{background:var(--bg)!important;color:var(--text)!important;scroll-behavior:smooth;}
.body,.main,.section{background:transparent!important;}

/* ── NAVBAR ── */
#v2-nav{
    position:fixed;top:0;left:0;right:0;z-index:1050;
    height:70px;display:flex;align-items:center;
    background:rgba(8,8,8,.85);backdrop-filter:blur(16px);
    border-bottom:1px solid rgba(201,162,39,.12);
    transition:box-shadow .3s;
}
#v2-nav.scrolled{box-shadow:0 4px 40px rgba(0,0,0,.7);}
#v2-nav .brand{
    font-size:1.8rem;font-weight:900;color:#fff;
    font-family:'Poppins',sans-serif;text-decoration:none;letter-spacing:-1px;
}
#v2-nav .brand span{color:var(--gold);}
#v2-nav .nav-link{
    color:rgba(255,255,255,.65)!important;font-size:.84rem;
    font-weight:500;padding:.45rem .85rem!important;border-radius:6px;
    transition:all .2s;font-family:'Poppins',sans-serif;
}
#v2-nav .nav-link:hover{color:var(--gold)!important;background:var(--gold-dim);}
.v2-btn-gold{
    background:var(--gold);color:#0a0a0a!important;border:none;
    border-radius:30px;padding:9px 22px;font-size:.82rem;font-weight:700;
    font-family:'Poppins',sans-serif;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;transition:all .22s;
}
.v2-btn-gold:hover{background:#e8c84a;box-shadow:0 4px 20px rgba(201,162,39,.4);}
.v2-btn-outline{
    color:var(--gold);border:1px solid var(--gold-border);
    border-radius:30px;padding:8px 20px;font-size:.8rem;font-weight:600;
    font-family:'Poppins',sans-serif;text-decoration:none;transition:all .2s;
}
.v2-btn-outline:hover{background:var(--gold);color:#0a0a0a;}

/* ── HERO ── */
#v2-hero{
    min-height:100vh;position:relative;overflow:hidden;
    display:flex;align-items:center;
    background:#060606;
}
.v2-hero-bg{
    position:absolute;inset:0;
    background:url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1800&q=80') center/cover no-repeat;
    transform:scale(1.05);
    transition:transform 8s ease;
}
#v2-hero:hover .v2-hero-bg{transform:scale(1.08);}
.v2-hero-overlay{
    position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(0,0,0,.88) 0%,rgba(0,0,0,.55) 60%,rgba(201,162,39,.08) 100%);
}
.v2-hero-content{position:relative;z-index:3;}
.v2-eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    border-radius:30px;padding:6px 16px;margin-bottom:24px;
}
.v2-eyebrow span{color:var(--gold);font-size:.75rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;font-family:'Poppins',sans-serif;}
.v2-hero-title{
    font-family:'Playfair Display',serif;
    font-size:clamp(2.4rem,6vw,4.2rem);
    font-weight:900;color:#fff;line-height:1.1;margin-bottom:18px;
}
.v2-hero-title em{color:var(--gold);font-style:italic;}
.v2-hero-sub{
    color:rgba(255,255,255,.65);font-size:1.05rem;
    font-family:'Poppins',sans-serif;line-height:1.7;
    max-width:520px;margin-bottom:32px;
}
/* search bar */
.v2-search{
    display:flex;align-items:center;gap:0;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.12);
    border-radius:50px;overflow:hidden;
    max-width:580px;backdrop-filter:blur(10px);
    transition:border-color .25s,box-shadow .25s;
}
.v2-search:focus-within{
    border-color:rgba(201,162,39,.5);
    box-shadow:0 0 0 3px rgba(201,162,39,.1);
}
.v2-search input{
    flex:1;background:transparent;border:none;outline:none;
    padding:14px 20px;color:#fff;font-size:.94rem;
    font-family:'Poppins',sans-serif;
}
.v2-search input::placeholder{color:rgba(255,255,255,.35);}
.v2-search button{
    background:var(--gold);border:none;color:#0a0a0a;
    padding:0 24px;height:52px;font-weight:700;font-size:.85rem;
    font-family:'Poppins',sans-serif;cursor:pointer;
    display:flex;align-items:center;gap:6px;transition:background .2s;
    white-space:nowrap;
}
.v2-search button:hover{background:#e8c84a;}
/* stats strip */
.v2-stats{
    display:flex;flex-wrap:wrap;gap:28px;margin-top:36px;
}
.v2-stat{display:flex;align-items:center;gap:10px;}
.v2-stat-num{font-size:1.5rem;font-weight:800;color:var(--gold);font-family:'Poppins',sans-serif;line-height:1;}
.v2-stat-lbl{font-size:.72rem;color:rgba(255,255,255,.4);font-family:'Poppins',sans-serif;line-height:1.3;}
.v2-stat-icon{
    width:36px;height:36px;border-radius:50%;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    color:var(--gold);font-size:.85rem;flex-shrink:0;
}

/* ── SECTION LABELS ── */
.v2-label{
    display:inline-block;font-size:.68rem;font-weight:700;
    text-transform:uppercase;letter-spacing:3px;color:var(--gold);
    font-family:'Poppins',sans-serif;margin-bottom:8px;
}
.v2-heading{
    font-family:'Playfair Display',serif;
    font-size:clamp(1.6rem,3vw,2.4rem);
    font-weight:700;color:#fff;line-height:1.2;margin-bottom:6px;
}
.v2-heading span{color:var(--gold);font-style:italic;}
.v2-divider{
    width:48px;height:2px;margin:14px 0 0;
    background:linear-gradient(90deg,var(--gold),transparent);
}
.v2-divider.center{margin:14px auto 0;}

/* ── CATEGORIES — image tiles ── */
#v2-cats{padding:90px 0 70px;background:var(--bg);}
.v2-cat-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(160px,1fr));
    gap:16px;margin-top:40px;
}
.v2-cat-tile{
    position:relative;border-radius:20px;overflow:hidden;
    height:200px;text-decoration:none!important;display:block;
    border:1px solid rgba(255,255,255,.06);
    transition:transform .35s cubic-bezier(.22,1,.36,1),
               box-shadow .35s,border-color .35s;
    cursor:pointer;
}
.v2-cat-tile-bg{
    position:absolute;inset:0;
    background:var(--tile-color,linear-gradient(135deg,#1a1a1a,#111));
    transition:transform .5s cubic-bezier(.22,1,.36,1);
}
.v2-cat-tile:hover .v2-cat-tile-bg{transform:scale(1.06);}
.v2-cat-tile-overlay{
    position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.1) 0%,rgba(0,0,0,.7) 100%);
}
.v2-cat-tile-content{
    position:absolute;inset:0;z-index:3;
    display:flex;flex-direction:column;align-items:center;justify-content:flex-end;
    padding:18px 12px;
}
.v2-cat-icon-wrap{
    width:60px;height:60px;border-radius:50%;
    background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.25);
    display:flex;align-items:center;justify-content:center;
    font-size:1.5rem;color:#fff;margin-bottom:10px;
    backdrop-filter:blur(4px);
    transition:all .3s;
    box-shadow:0 4px 16px rgba(0,0,0,.3);
}
.v2-cat-tile:hover .v2-cat-icon-wrap{
    background:var(--gold);border-color:var(--gold);
    color:#0a0a0a;transform:scale(1.1);
    box-shadow:0 6px 24px rgba(201,162,39,.45);
}
.v2-cat-img-wrap{
    width:60px;height:60px;border-radius:50%;overflow:hidden;
    border:2px solid rgba(255,255,255,.25);margin-bottom:10px;
    transition:transform .3s;box-shadow:0 4px 16px rgba(0,0,0,.4);
}
.v2-cat-tile:hover .v2-cat-img-wrap{transform:scale(1.1);}
.v2-cat-img-wrap img{width:100%;height:100%;object-fit:cover;}
.v2-cat-name{
    font-size:.82rem;font-weight:700;color:#fff;text-align:center;
    font-family:'Poppins',sans-serif;text-shadow:0 2px 8px rgba(0,0,0,.7);
}
.v2-cat-count{
    font-size:.65rem;color:rgba(255,255,255,.55);
    font-family:'Poppins',sans-serif;margin-top:2px;
}
.v2-cat-tile:hover{
    transform:translateY(-8px);
    box-shadow:0 24px 48px rgba(0,0,0,.6),0 0 0 1.5px rgba(201,162,39,.5);
    border-color:rgba(201,162,39,.5);
}
@media(max-width:640px){
    .v2-cat-grid{grid-template-columns:repeat(2,1fr);}
    .v2-cat-tile{height:160px;}
}

/* ── BRANCHES — horizontal cards ── */
#v2-branches{padding:80px 0;background:var(--bg2);}
.v2-filter-bar{
    display:flex;flex-wrap:wrap;gap:8px;margin-bottom:36px;align-items:center;
}
.v2-filter-bar .v2-fbtn{
    background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
    border-radius:30px;padding:7px 18px;font-size:.78rem;font-weight:600;
    color:rgba(255,255,255,.5);font-family:'Poppins',sans-serif;
    cursor:pointer;transition:all .2s;text-decoration:none;
    outline:none;
}
.v2-filter-bar .v2-fbtn:hover,
.v2-filter-bar .v2-fbtn.active{
    background:var(--gold-dim);border-color:var(--gold-border);
    color:var(--gold);
}
.v2-filter-bar .v2-fbtn:focus{outline:none;box-shadow:none;}
/* horizontal card */
.v2-hcard{
    display:flex;background:var(--bg3);border-radius:16px;
    border:1px solid rgba(255,255,255,.06);overflow:hidden;
    transition:transform .35s cubic-bezier(.22,1,.36,1),
               box-shadow .35s,border-color .35s;
    height:200px;position:relative;
}
.v2-hcard:hover{
    transform:translateY(-6px);
    border-color:rgba(201,162,39,.4);
    box-shadow:0 20px 50px rgba(0,0,0,.5),0 0 0 1px rgba(201,162,39,.15);
}
/* image side */
.v2-hcard-img{
    width:220px;min-width:220px;position:relative;overflow:hidden;
    background:#1a1a1a;flex-shrink:0;
}
.v2-hcard-img img{
    width:100%;height:100%;object-fit:cover;
    transition:transform .5s cubic-bezier(.22,1,.36,1);
}
.v2-hcard:hover .v2-hcard-img img{transform:scale(1.07);}
.v2-hcard-img-placeholder{
    width:100%;height:100%;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#1a1a1a,#111);
    font-size:3rem;color:rgba(201,162,39,.12);
}
.v2-hcard-img::after{
    content:'';position:absolute;
    inset:0;
    background:linear-gradient(90deg,transparent 70%,var(--bg3) 100%);
    pointer-events:none;
}
/* badge on image */
.v2-hcard-badge{
    position:absolute;top:10px;{{ $isAr ? 'right' : 'left' }}:10px;z-index:3;
    background:rgba(8,8,8,.88);color:var(--gold);font-size:.6rem;font-weight:700;
    padding:3px 10px;border-radius:20px;border:1px solid var(--gold-border);
    backdrop-filter:blur(6px);font-family:'Poppins',sans-serif;
}
.v2-hcard-rating{
    position:absolute;bottom:10px;{{ $isAr ? 'right' : 'left' }}:10px;z-index:3;
    background:rgba(8,8,8,.88);font-size:.7rem;font-weight:700;
    padding:4px 9px;border-radius:20px;border:1px solid var(--gold-border);
    backdrop-filter:blur(6px);display:flex;align-items:center;gap:4px;
    font-family:'Poppins',sans-serif;
}
.v2-hcard-rating i{color:var(--gold);font-size:.62rem;}
/* body side */
.v2-hcard-body{
    flex:1;padding:20px 22px;display:flex;flex-direction:column;
    justify-content:space-between;overflow:hidden;
    min-width:0;
}
/* company row */
.v2-hcard-company{
    display:flex;align-items:center;gap:8px;margin-bottom:6px;
}
.v2-hcard-logo{
    width:28px;height:28px;border-radius:7px;overflow:hidden;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.v2-hcard-logo img{width:100%;height:100%;object-fit:cover;}
.v2-hcard-logo i{font-size:.7rem;color:var(--gold);}
.v2-hcard-company-name{
    font-size:.7rem;font-weight:600;color:rgba(255,255,255,.4);
    font-family:'Poppins',sans-serif;transition:color .2s;
}
.v2-hcard:hover .v2-hcard-company-name{color:var(--gold);}
/* branch name */
.v2-hcard-name{
    font-size:1.05rem;font-weight:700;color:#fff;
    font-family:'Poppins',sans-serif;line-height:1.25;
    overflow:hidden;white-space:nowrap;text-overflow:ellipsis;
    margin-bottom:6px;
}
.v2-hcard-addr{
    font-size:.75rem;color:rgba(255,255,255,.38);
    display:flex;align-items:center;gap:5px;margin-bottom:8px;
}
.v2-hcard-addr i{color:var(--gold);font-size:.67rem;flex-shrink:0;}
.v2-hcard-stars{display:flex;align-items:center;gap:3px;margin-bottom:10px;}
.v2-hcard-stars i{font-size:.6rem;color:var(--gold);}
.v2-hcard-stars span{font-size:.68rem;color:rgba(255,255,255,.3);margin-{{ $isAr?'right':'left' }}:3px;}
/* chips */
.v2-hcard-chips{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px;}
.v2-chip{
    background:rgba(201,162,39,.06);border:1px solid rgba(201,162,39,.15);
    border-radius:20px;padding:3px 10px;font-size:.63rem;font-weight:600;
    color:rgba(201,162,39,.8);font-family:'Poppins',sans-serif;
    display:inline-flex;align-items:center;gap:3px;
}
.v2-chip i{font-size:.58rem;}
/* book button */
.v2-hcard-book{
    display:inline-flex;align-items:center;gap:6px;
    padding:9px 20px;border-radius:25px;width:fit-content;
    border:1.5px solid var(--gold-border);
    background:var(--gold-dim);color:var(--gold);
    font-size:.8rem;font-weight:700;font-family:'Poppins',sans-serif;
    text-decoration:none;transition:all .25s;
}
.v2-hcard-book:hover,
.v2-hcard:hover .v2-hcard-book{
    background:var(--gold);color:#0a0a0a;
    border-color:var(--gold);
    box-shadow:0 4px 18px rgba(201,162,39,.35);
}
@media(max-width:767px){
    .v2-hcard{flex-direction:column;height:auto;}
    .v2-hcard-img{width:100%;min-width:unset;height:180px;}
    .v2-hcard-img::after{
        background:linear-gradient(180deg,transparent 70%,var(--bg3) 100%);
    }
}

/* ── HOW IT WORKS ── */
#v2-how{
    padding:90px 0;
    background:url('https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=1600&q=80') center/cover no-repeat;
    position:relative;
}
#v2-how::before{
    content:'';position:absolute;inset:0;
    background:rgba(0,0,0,.88);
}
#v2-how .container{position:relative;z-index:2;}
.v2-step{
    text-align:center;padding:32px 20px;
    border-radius:20px;background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.06);
    transition:all .35s;position:relative;
    height:100%;
}
.v2-step:hover{
    background:rgba(201,162,39,.05);
    border-color:rgba(201,162,39,.25);
    transform:translateY(-6px);
    box-shadow:0 16px 40px rgba(0,0,0,.4);
}
.v2-step-num{
    position:absolute;top:-16px;left:50%;transform:translateX(-50%);
    width:32px;height:32px;border-radius:50%;
    background:var(--gold);color:#0a0a0a;
    font-size:.82rem;font-weight:900;display:flex;align-items:center;justify-content:center;
    font-family:'Poppins',sans-serif;
    box-shadow:0 4px 16px rgba(201,162,39,.4);
}
.v2-step-icon{
    width:72px;height:72px;border-radius:50%;margin:16px auto 18px;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    font-size:1.6rem;color:var(--gold);
    transition:all .3s;
}
.v2-step:hover .v2-step-icon{background:var(--gold);color:#0a0a0a;box-shadow:0 6px 24px rgba(201,162,39,.4);}
.v2-step h5{font-weight:700;color:#fff;margin-bottom:8px;font-family:'Poppins',sans-serif;font-size:1rem;}
.v2-step p{font-size:.83rem;color:rgba(255,255,255,.45);line-height:1.65;font-family:'Poppins',sans-serif;margin:0;}

/* ── SERVICES BAND ── */
#v2-services{padding:90px 0;background:var(--bg);}
.v2-svc-row{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
    gap:20px;margin-top:44px;
}
.v2-svc-card{
    background:var(--bg3);border-radius:18px;
    border:1px solid rgba(255,255,255,.05);
    padding:30px 20px 24px;text-align:center;
    transition:all .35s;position:relative;overflow:hidden;
}
.v2-svc-card::before{
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(201,162,39,.06),transparent);
    opacity:0;transition:opacity .35s;
}
.v2-svc-card:hover{
    transform:translateY(-8px);
    border-color:rgba(201,162,39,.3);
    box-shadow:0 20px 50px rgba(0,0,0,.45);
}
.v2-svc-card:hover::before{opacity:1;}
.v2-svc-card-icon{
    width:68px;height:68px;border-radius:50%;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    font-size:1.6rem;color:var(--gold);margin:0 auto 16px;
    transition:all .3s;
}
.v2-svc-card:hover .v2-svc-card-icon{
    background:var(--gold);color:#0a0a0a;
    box-shadow:0 6px 24px rgba(201,162,39,.4);
    transform:rotate(-5deg) scale(1.05);
}
.v2-svc-card h5{font-weight:700;color:#fff;font-size:.92rem;margin-bottom:8px;font-family:'Poppins',sans-serif;}
.v2-svc-card p{font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.6;font-family:'Poppins',sans-serif;margin:0;}

/* ── CTA BANNER ── */
#v2-cta{
    padding:100px 0;
    background:linear-gradient(135deg,#0a0a0a 0%,#111 50%,#0a0a0a 100%);
    border-top:1px solid rgba(201,162,39,.1);
    border-bottom:1px solid rgba(201,162,39,.1);
    position:relative;overflow:hidden;
}
#v2-cta::before{
    content:'Booksy';
    position:absolute;
    font-size:18vw;font-weight:900;color:rgba(201,162,39,.03);
    font-family:'Poppins',sans-serif;
    top:50%;left:50%;transform:translate(-50%,-50%);
    white-space:nowrap;pointer-events:none;
    letter-spacing:-5px;
}
.v2-cta-feat{
    display:flex;align-items:center;gap:14px;margin-bottom:18px;
}
.v2-cta-feat-icon{
    width:46px;height:46px;border-radius:12px;flex-shrink:0;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    color:var(--gold);font-size:1.1rem;
}
.v2-cta-feat-text h6{color:#fff;font-weight:700;font-size:.88rem;margin:0 0 2px;font-family:'Poppins',sans-serif;}
.v2-cta-feat-text p{color:rgba(255,255,255,.4);font-size:.76rem;margin:0;font-family:'Poppins',sans-serif;}

/* ── TESTIMONIALS ── */
#v2-reviews{
    padding:90px 0;
    background:var(--bg2);
}
.v2-review-card{
    background:var(--bg3);border-radius:18px;
    border:1px solid rgba(255,255,255,.06);
    padding:28px;position:relative;
    transition:all .3s;
}
.v2-review-card:hover{
    border-color:rgba(201,162,39,.2);
    box-shadow:0 12px 36px rgba(0,0,0,.4);
}
.v2-review-quote{
    font-size:4rem;line-height:1;color:rgba(201,162,39,.15);
    font-family:'Playfair Display',serif;margin-bottom:-12px;
}
.v2-review-text{
    font-size:.88rem;color:rgba(255,255,255,.65);
    line-height:1.75;font-family:'Poppins',sans-serif;
    margin-bottom:20px;
}
.v2-review-stars{display:flex;gap:3px;margin-bottom:14px;}
.v2-review-stars i{font-size:.65rem;color:var(--gold);}
.v2-review-author{display:flex;align-items:center;gap:10px;}
.v2-review-avatar{
    width:42px;height:42px;border-radius:50%;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    color:var(--gold);font-size:1rem;flex-shrink:0;
}
.v2-review-name{font-weight:700;color:#fff;font-size:.88rem;font-family:'Poppins',sans-serif;margin:0;}
.v2-review-role{color:rgba(255,255,255,.35);font-size:.72rem;font-family:'Poppins',sans-serif;margin:0;}

/* ── MISC ── */
.bk-section-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(201,162,39,.2) 30%,rgba(201,162,39,.2) 70%,transparent);}
.v2-empty{text-align:center;padding:70px 20px;color:rgba(255,255,255,.3);}
.v2-empty i{font-size:3.5rem;color:rgba(201,162,39,.12);display:block;margin-bottom:16px;}
.v2-empty h5{color:rgba(255,255,255,.5);font-family:'Poppins',sans-serif;}
.v2-counter-num{font-size:2.2rem;font-weight:800;color:var(--gold);font-family:'Poppins',sans-serif;line-height:1;}

/* ── ICON FIX — force FontAwesome to render ── */
.fas,.far,.fab,.fal{
    font-family:"Font Awesome 5 Free","Font Awesome 5 Brands" !important;
    -webkit-font-smoothing:antialiased;
    display:inline-block;font-style:normal;
    font-variant:normal;text-rendering:auto;line-height:1;
}
.fab{font-family:"Font Awesome 5 Brands"!important;font-weight:400!important;}
.far{font-weight:400!important;}
.fas{font-weight:900!important;}

/* compare switcher */
.v2-compare{
    position:fixed;bottom:88px;{{ $isAr ? 'left' : 'right' }}:24px;z-index:9999;
    background:rgba(20,20,20,.95);color:rgba(255,255,255,.7);border-radius:30px;
    border:1px solid rgba(201,162,39,.25);
    padding:9px 18px;font-weight:600;font-size:.78rem;
    font-family:'Poppins',sans-serif;text-decoration:none;
    box-shadow:0 4px 20px rgba(0,0,0,.4);
    display:flex;align-items:center;gap:7px;
    transition:all .22s;
}
.v2-compare:hover{background:var(--gold);color:#0a0a0a;border-color:var(--gold);}

/* ── FLOATING MAP BUTTON ── */
#v2-map-fab{
    position:fixed;bottom:24px;{{ $isAr ? 'left' : 'right' }}:24px;z-index:9999;
    width:56px;height:56px;border-radius:50%;
    background:var(--gold);color:#0a0a0a;
    border:none;cursor:pointer;
    box-shadow:0 6px 28px rgba(201,162,39,.55);
    display:flex;align-items:center;justify-content:center;
    font-size:1.3rem;
    transition:all .25s;
}
#v2-map-fab:hover{background:#e8c84a;transform:scale(1.1);}
#v2-map-fab .v2-fab-tooltip{
    position:absolute;{{ $isAr ? 'right' : 'left' }}:calc(100% + 10px);top:50%;transform:translateY(-50%);
    background:rgba(20,20,20,.95);border:1px solid rgba(201,162,39,.2);
    color:#fff;font-size:.72rem;font-weight:600;white-space:nowrap;
    padding:5px 12px;border-radius:20px;
    font-family:'Poppins',sans-serif;
    opacity:0;pointer-events:none;transition:opacity .2s;
}
#v2-map-fab:hover .v2-fab-tooltip{opacity:1;}

/* ── MAP MODAL ── */
#v2-map-modal{
    position:fixed;inset:0;z-index:10000;
    display:none;
    align-items:center;justify-content:center;
    background:rgba(0,0,0,.8);backdrop-filter:blur(8px);
    padding:20px;
    overflow-y:auto;
}
#v2-map-modal.open{display:flex;}
.v2-map-container{
    width:100%;max-width:1000px;
    background:#111;border-radius:20px;
    border:1px solid rgba(201,162,39,.2);
    overflow:visible;
    box-shadow:0 30px 80px rgba(0,0,0,.7);
}
.v2-map-header{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 22px;
    border-bottom:1px solid rgba(255,255,255,.06);
    background:#141414;
    border-radius:20px 20px 0 0;
}
.v2-map-header h5{
    margin:0;color:#fff;font-weight:700;font-size:1rem;
    font-family:'Poppins',sans-serif;
    display:flex;align-items:center;gap:8px;
}
.v2-map-header h5 i{color:var(--gold);}
.v2-map-close{
    background:rgba(255,255,255,.06);border:none;color:rgba(255,255,255,.6);
    width:34px;height:34px;border-radius:50%;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    font-size:.85rem;transition:all .2s;
}
.v2-map-close:hover{background:rgba(231,76,60,.2);color:#e74c3c;}
#v2-leaflet-map{
    height:500px;
    width:100%;
    border-radius:0 0 20px 20px;
    display:block;
    z-index:1;
}

/* Leaflet popup custom */
.v2-popup{font-family:'Poppins',sans-serif;min-width:220px;}
.v2-popup-img{width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:8px;}
.v2-popup-img-ph{width:100%;height:110px;background:#1a1a1a;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#555;margin-bottom:8px;}
.v2-popup-cat{font-size:.6rem;font-weight:700;color:#C9A227;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;}
.v2-popup-name{font-size:.92rem;font-weight:700;color:#1a1a1a;margin-bottom:2px;}
.v2-popup-company{font-size:.7rem;color:#666;margin-bottom:6px;}
.v2-popup-addr{font-size:.72rem;color:#555;margin-bottom:8px;display:flex;align-items:flex-start;gap:4px;}
.v2-popup-stars{display:flex;align-items:center;gap:2px;margin-bottom:10px;}
.v2-popup-stars i{font-size:.6rem;color:#C9A227;}
.v2-popup-stars span{font-size:.68rem;color:#888;margin-left:4px;}
.v2-popup-book{display:block;background:#C9A227;color:#0a0a0a;text-align:center;padding:8px;border-radius:8px;font-size:.78rem;font-weight:700;text-decoration:none;transition:background .2s;}
.v2-popup-book:hover{background:#e8c84a;color:#0a0a0a;}

/* branches loading state */
#v2-branches-grid.loading{opacity:.5;pointer-events:none;}
.v2-skeleton{
    background:linear-gradient(90deg,var(--bg3) 25%,rgba(255,255,255,.04) 50%,var(--bg3) 75%);
    background-size:200% 100%;
    animation:shimmer 1.4s infinite;
    border-radius:16px;height:200px;
}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>
{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
/* ── عزل خريطة Leaflet عن تأثيرات القالب ── */
#v2-leaflet-map { z-index:1 !important; position:relative !important; }
#v2-leaflet-map * { box-sizing:content-box !important; }

/* Tile images: ما يحتاج reset — Porto يكسر max-width وborder-radius */
.leaflet-tile {
    max-width:none !important;
    width:256px !important;
    height:256px !important;
    border:none !important;
    box-shadow:none !important;
    padding:0 !important;
    margin:0 !important;
    border-radius:0 !important;
    object-fit:unset !important;
    /* لا تضع transform:none هنا — Leaflet يحتاجه لتحريك الـ tiles */
    opacity: 1 !important;
    visibility: visible !important;
}
/* Porto قد يخفي images عبر appear-animation */
.leaflet-pane img,
.leaflet-tile-container img {
    max-width:none !important;
    border-radius:0 !important;
    box-shadow:none !important;
    opacity:1 !important;
    visibility:visible !important;
    animation:none !important;
    transition:opacity 0.2s linear !important;
}
.leaflet-container {
    background:#1a1a1a !important;
    font-family:'Poppins',sans-serif !important;
}
.leaflet-popup-content-wrapper {
    border-radius:12px !important;
    box-shadow:0 8px 32px rgba(0,0,0,.35) !important;
    padding:0 !important;
    overflow:hidden !important;
}
.leaflet-popup-content {
    margin:0 !important;
    width:240px !important;
}
.leaflet-popup-content img {
    max-width:100% !important;
    width:100% !important;
    height:110px !important;
    object-fit:cover !important;
    display:block !important;
    border-radius:0 !important;
}
.leaflet-popup-tip-container{ display:block !important; }
.leaflet-control-zoom a {
    background:#141414 !important;
    color:#C9A227 !important;
    border-color:#333 !important;
}
.leaflet-control-zoom a:hover{ background:#C9A227 !important; color:#0a0a0a !important; }
</style>

</head>
<body data-plugin-scroll-spy data-plugin-options="{'target': '#header'}">
<div class="body">

{{-- ── NAVBAR ── --}}
<nav id="v2-nav" class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between w-100">
        <a href="{{ route('front.index') }}" class="brand">Booksy<span>.</span></a>
        <div class="d-none d-lg-flex align-items-center gap-1">
            <a href="#v2-cats" class="nav-link">{{ $isAr ? 'التصنيفات' : 'Categories' }}</a>
            <a href="#v2-branches" class="nav-link">{{ $isAr ? 'الفروع' : 'Branches' }}</a>
            <a href="#v2-how" class="nav-link">{{ $isAr ? 'كيف يعمل' : 'How It Works' }}</a>
            <a href="{{ route('front.about') }}" class="nav-link">{{ $isAr ? 'من نحن' : 'About' }}</a>
            <a href="{{ route('front.contact') }}" class="nav-link">{{ $isAr ? 'تواصل' : 'Contact' }}</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if($isAr)
                <a href="{{ route('locale.switch','en') }}" class="v2-btn-outline">EN</a>
            @else
                <a href="{{ route('locale.switch','ar') }}" class="v2-btn-outline">عربي</a>
            @endif
            <a href="{{ route('company.register') }}" class="v2-btn-gold">
                <i class="fas fa-store"></i>
                {{ $isAr ? 'سجّل نشاطك' : 'List Business' }}
            </a>
        </div>
    </div>
</nav>

<div role="main" class="main">

{{-- ── HERO ── --}}
<section id="v2-hero">
    <div class="v2-hero-bg"></div>
    <div class="v2-hero-overlay"></div>
    <div class="container v2-hero-content" style="padding-top:100px;padding-bottom:80px;">
        <div class="row align-items-center" style="min-height:calc(100vh - 180px);">
            <div class="col-lg-7 appear-animation" data-appear-animation="fadeInLeft" data-plugin-options="{'minWindowWidth':0}">
                <div class="v2-eyebrow appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">
                    <i class="fas fa-star" style="color:var(--gold);font-size:.65rem;"></i>
                    <span>{{ $isAr ? 'المنصة الأولى للحجز في سوريا' : 'The #1 Booking Platform' }}</span>
                </div>
                <h1 class="v2-hero-title appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="200" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'احجز موعدك في' : 'Book Your Next' }}<br>
                    <em>{{ $isAr ? 'أفضل الصالونات' : 'Beauty Experience' }}</em><br>
                    {{ $isAr ? 'والعيادات' : 'In Seconds.' }}
                </h1>
                <p class="v2-hero-sub appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="400" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr
                        ? 'اكتشف صالونات التجميل، مراكز السبا، والعيادات الراقية القريبة منك. احجز موعدك بنقرة واحدة.'
                        : 'Discover top beauty salons, spas & aesthetic clinics near you. Book instantly with one tap.' }}
                </p>
                <form action="{{ route('front.index2') }}" method="GET" class="appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="600" data-plugin-options="{'minWindowWidth':0}">
                    <div class="v2-search {{ $isAr ? 'flex-row-reverse' : '' }}">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ $isAr ? 'ابحث عن صالون، عيادة، سبا...' : 'Search salons, clinics, spas...' }}">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                            {{ $isAr ? 'ابحث' : 'Search' }}
                        </button>
                    </div>
                </form>
                <div class="v2-stats appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="800" data-plugin-options="{'minWindowWidth':0}">
                    <div class="v2-stat">
                        <div class="v2-stat-icon"><i class="fas fa-store-alt"></i></div>
                        <div>
                            <div class="v2-stat-num">{{ App\Models\Company::where('status','active')->count() }}+</div>
                            <div class="v2-stat-lbl">{{ $isAr ? 'نشاط تجاري' : 'Businesses' }}</div>
                        </div>
                    </div>
                    <div class="v2-stat">
                        <div class="v2-stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div>
                            <div class="v2-stat-num">{{ App\Models\Appointment::count() }}+</div>
                            <div class="v2-stat-lbl">{{ $isAr ? 'حجز مكتمل' : 'Bookings' }}</div>
                        </div>
                    </div>
                    <div class="v2-stat">
                        <div class="v2-stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="v2-stat-num">{{ App\Models\Branch::count() }}</div>
                            <div class="v2-stat-lbl">{{ $isAr ? 'فرع' : 'Branches' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-end appear-animation" data-appear-animation="fadeInRight" data-appear-animation-delay="400" data-plugin-options="{'minWindowWidth':0}">
                {{-- Floating cards decoration --}}
                <div style="position:relative;width:320px;height:380px;">
                    <div style="position:absolute;top:0;{{ $isAr?'left':'right' }}:0;width:260px;background:rgba(20,20,20,.9);backdrop-filter:blur(12px);border:1px solid rgba(201,162,39,.2);border-radius:18px;padding:20px;">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                            <div style="width:44px;height:44px;border-radius:12px;background:rgba(201,162,39,.15);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:1.2rem;"><i class="fas fa-cut"></i></div>
                            <div>
                                <div style="font-weight:700;color:#fff;font-size:.88rem;">{{ $isAr ? 'صالون نجمة' : 'Star Salon' }}</div>
                                <div style="color:rgba(255,255,255,.4);font-size:.7rem;">{{ $isAr ? 'دمشق' : 'Damascus' }}</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:3px;margin-bottom:10px;">
                            @for($i=0;$i<5;$i++)<i class="fas fa-star" style="color:var(--gold);font-size:.62rem;"></i>@endfor
                            <span style="font-size:.68rem;color:rgba(255,255,255,.4);margin-{{ $isAr?'right':'left' }}:4px;">4.9 (128)</span>
                        </div>
                        <div style="background:var(--gold);color:#0a0a0a;border-radius:8px;padding:8px;text-align:center;font-size:.78rem;font-weight:700;font-family:'Poppins',sans-serif;">
                            {{ $isAr ? '✓ تم الحجز' : '✓ Booking Confirmed' }}
                        </div>
                    </div>
                    <div style="position:absolute;bottom:20px;{{ $isAr?'right':'left' }}:0;width:200px;background:rgba(20,20,20,.9);backdrop-filter:blur(12px);border:1px solid rgba(201,162,39,.15);border-radius:14px;padding:16px;">
                        <div style="font-size:.68rem;color:rgba(255,255,255,.4);margin-bottom:6px;font-family:'Poppins',sans-serif;">{{ $isAr ? 'موعدك القادم' : 'Next Appointment' }}</div>
                        <div style="font-weight:700;color:#fff;font-size:.9rem;">{{ $isAr ? 'قص + صبغة' : 'Cut + Color' }}</div>
                        <div style="font-size:.75rem;color:var(--gold);margin-top:4px;">{{ $isAr ? 'غداً – 3:00 م' : 'Tomorrow – 3:00 PM' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── CATEGORIES ── --}}
<section id="v2-cats" style="padding:90px 0 70px;background:var(--bg);">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-2">
            <div>
                <div class="v2-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'تصفح حسب النوع' : 'Browse by Type' }}
                </div>
                <h2 class="v2-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                    {{ $isAr ? 'كل ' : 'All ' }}<span>{{ $isAr ? 'التصنيفات' : 'Categories' }}</span>
                </h2>
                <div class="v2-divider"></div>
            </div>
        </div>

        @if($categories->isNotEmpty())
        <div class="v2-cat-grid appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="200" data-plugin-options="{'minWindowWidth':0}">
            {{-- "All" tile --}}
            <a href="{{ route('front.index2') }}" class="v2-cat-tile">
                <div class="v2-cat-tile-bg" style="--tile-color:linear-gradient(135deg,#1c1c1c,#111);"></div>
                <div class="v2-cat-tile-overlay"></div>
                <div class="v2-cat-tile-content">
                    <div class="v2-cat-icon-wrap"><i class="fas fa-th"></i></div>
                    <div class="v2-cat-name">{{ $isAr ? 'الكل' : 'All' }}</div>
                    <div class="v2-cat-count">{{ $categories->sum('companies_count') }}+ {{ $isAr ? 'مكان' : 'places' }}</div>
                </div>
            </a>

            @foreach($categories as $cat)
            @php
                $sl = strtolower($cat->slug ?? $cat->name_en ?? '');
                $catIcon = 'fas fa-store';
                foreach($catIcons as $k=>$v){ if(str_contains($sl,$k)){$catIcon=$v;break;} }
                $tileColor = 'linear-gradient(135deg,#1a1a1a,#111)';
                foreach($cgMap as $k=>$v){ if(str_contains($sl,$k)){$tileColor='linear-gradient(135deg,'.$v.', '.($v).'aa)';break;} }
            @endphp
            <a href="{{ route('front.category', $cat->slug) }}" class="v2-cat-tile">
                <div class="v2-cat-tile-bg" style="--tile-color:{{ $tileColor }};background:{{ $tileColor }};"></div>
                <div class="v2-cat-tile-overlay"></div>
                <div class="v2-cat-tile-content">
                    @if($cat->image)
                        <div class="v2-cat-img-wrap">
                            <img src="{{ asset('storage/'.$cat->image) }}" alt="">
                        </div>
                    @else
                        <div class="v2-cat-icon-wrap"><i class="{{ $catIcon }}"></i></div>
                    @endif
                    <div class="v2-cat-name">{{ $isAr ? $cat->name_ar : $cat->name_en }}</div>
                    @if($cat->companies_count)
                    <div class="v2-cat-count">{{ $cat->companies_count }} {{ $isAr ? 'مكان' : 'places' }}</div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="v2-empty"><i class="fas fa-th"></i><p>{{ $isAr ? 'لا توجد تصنيفات.' : 'No categories yet.' }}</p></div>
        @endif
    </div>
</section>

<div class="bk-section-divider"></div>

{{-- ── BRANCHES (AJAX) ── --}}
<section id="v2-branches" style="padding:80px 0;background:var(--bg2);">
    <div class="container">
        {{-- Header --}}
        <div class="row align-items-end mb-4">
            <div class="col-lg-6">
                <div class="v2-label">{{ $isAr ? 'الفروع المميزة' : 'Featured Branches' }}</div>
                <h2 class="v2-heading">
                    {{ $isAr ? 'اكتشف ' : 'Explore ' }}<span>{{ $isAr ? 'أقرب الفروع' : 'Nearby Branches' }}</span>
                </h2>
                <div class="v2-divider"></div>
            </div>
            <div class="col-lg-6 d-flex align-items-center justify-content-lg-end gap-3 flex-wrap mt-3 mt-lg-0">
                <div style="background:var(--gold-dim);border:1px solid var(--gold-border);border-radius:30px;padding:7px 18px;display:inline-flex;align-items:center;gap:7px;">
                    <i class="fas fa-code-branch" style="color:var(--gold);font-size:.8rem;font-family:'Font Awesome 5 Free';font-weight:900;"></i>
                    <span id="v2-branch-count" style="font-size:.88rem;font-weight:700;color:var(--gold);font-family:'Poppins',sans-serif;">{{ $branches->total() }}</span>
                    <span style="font-size:.78rem;color:rgba(255,255,255,.4);font-family:'Poppins',sans-serif;">{{ $isAr ? 'فرع' : 'branches' }}</span>
                </div>
            </div>
        </div>

        {{-- Category filter pills — AJAX --}}
        @if($categories->isNotEmpty())
        <div class="v2-filter-bar" id="v2-filter-bar">
            <button class="v2-fbtn active" data-slug="">
                {{ $isAr ? 'الكل' : 'All' }}
            </button>
            @foreach($categories->take(10) as $fcat)
            <button class="v2-fbtn" data-slug="{{ $fcat->slug }}">
                {{ $isAr ? $fcat->name_ar : $fcat->name_en }}
            </button>
            @endforeach
        </div>
        @endif

        {{-- Grid rendered by JS --}}
        <div class="row g-4" id="v2-branches-grid">
            @foreach($branches as $branch)
            @php
                $branchImg   = $branch->images->first();
                $reviewCount = $branch->reviews->count();
                $avgRating   = $reviewCount ? round($branch->reviews->avg('rating'),1) : null;
                $svcCount    = $branch->services->count();
                $company     = $branch->company;
                $branchName  = $isAr ? ($branch->name_ar ?? $branch->name_en) : ($branch->name_en ?? $branch->name_ar);
                $companyName = $isAr ? ($company->name_ar ?? $company->name_en) : ($company->name_en ?? $company->name_ar);
            @endphp
            <div class="col-lg-6">
                <div class="v2-hcard">
                    <div class="v2-hcard-img">
                        @if($branchImg)
                            <img src="{{ asset('storage/'.$branchImg->path) }}" alt="{{ $branchName }}" loading="lazy">
                        @elseif($company->logo)
                            <img src="{{ asset('storage/'.$company->logo) }}" alt="" loading="lazy">
                        @else
                            <div class="v2-hcard-img-placeholder" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:linear-gradient(135deg,#1a1a1a,#111);font-size:3rem;color:rgba(201,162,39,.12);">
                                <i class="fas fa-store" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                            </div>
                        @endif
                        @if($company->category)
                        <span class="v2-hcard-badge">{{ $isAr ? $company->category->name_ar : $company->category->name_en }}</span>
                        @endif
                        @if($avgRating)
                        <span class="v2-hcard-rating">
                            <i class="fas fa-star" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;color:var(--gold);font-size:.62rem;"></i>
                            {{ $avgRating }}
                            <span style="color:rgba(255,255,255,.35);font-weight:400;font-size:.6rem;">· {{ $reviewCount }}</span>
                        </span>
                        @endif
                    </div>
                    <div class="v2-hcard-body">
                        <div class="v2-hcard-company">
                            <div class="v2-hcard-logo">
                                @if($company->logo)
                                    <img src="{{ asset('storage/'.$company->logo) }}" alt="">
                                @else
                                    <i class="fas fa-store" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.7rem;color:var(--gold);"></i>
                                @endif
                            </div>
                            <span class="v2-hcard-company-name">{{ $companyName }}</span>
                        </div>
                        <div class="v2-hcard-name">{{ $branchName }}</div>
                        @if($branch->address)
                        <div class="v2-hcard-addr">
                            <i class="fas fa-map-marker-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;color:var(--gold);font-size:.67rem;"></i>
                            {{ Str::limit($branch->address, 40) }}
                        </div>
                        @endif
                        @if($avgRating)
                        <div class="v2-hcard-stars">
                            @for($s=1;$s<=5;$s++)
                            <i class="{{ $s<=round($avgRating)?'fas':'far' }} fa-star" style="font-family:'Font Awesome 5 Free'!important;font-weight:{{ $s<=round($avgRating)?'900':'400' }}!important;font-size:.6rem;color:var(--gold);"></i>
                            @endfor
                            <span style="font-size:.68rem;color:rgba(255,255,255,.3);margin-{{ $isAr?'right':'left' }}:3px;">({{ $reviewCount }})</span>
                        </div>
                        @endif
                        <div class="v2-hcard-chips">
                            @if($svcCount>0)
                            <span class="v2-chip">{{ $svcCount }} {{ $isAr?'خدمة':'svcs' }}</span>
                            @endif
                            <span class="v2-chip">{{ $isAr?'فرع':'Branch' }}</span>
                        </div>
                        <a href="{{ route('front.branch', $branch) }}" class="v2-hcard-book">
                            {{ $isAr ? 'احجز الآن' : 'Book Now' }}
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div id="v2-load-more-wrap" class="text-center" style="margin-top:40px;display:{{ $branches->hasMorePages()?'block':'none' }};">
            <button id="v2-load-more" class="v2-btn-outline" style="padding:12px 36px;">
                {{ $isAr ? 'تحميل المزيد' : 'Load More' }}
            </button>
        </div>

        <div id="v2-empty-state" class="v2-empty" style="display:none;">
            <div style="font-size:3.5rem;color:rgba(201,162,39,.12);margin-bottom:16px;">🏪</div>
            <h5>{{ $isAr ? 'لا توجد نتائج' : 'No Results Found' }}</h5>
            <p>{{ $isAr ? 'جرّب تصنيفاً مختلفاً.' : 'Try a different category.' }}</p>
        </div>

    </div>
</section>

<div class="bk-section-divider"></div>

{{-- ── SERVICES ── --}}
<section id="v2-services" style="padding:90px 0;background:var(--bg);">
    <div class="container">
        <div class="text-center mb-2">
            <div class="v2-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">{{ $isAr ? 'ما نقدمه' : 'What We Offer' }}</div>
            <h2 class="v2-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                {{ $isAr ? 'خدمات' : 'Our' }} <span>{{ $isAr ? 'متنوعة' : 'Services' }}</span>
            </h2>
            <div class="v2-divider center"></div>
        </div>
        @php
            $svcs2=[
                ['i'=>'fas fa-cut','ar'=>'صالونات الشعر','en'=>'Hair Salons','dar'=>'قصات، صبغات، تسريحات بأيدي محترفين.','den'=>'Professional cuts, color & styling.'],
                ['i'=>'fas fa-spa','ar'=>'سبا ومساج','en'=>'Spa & Massage','dar'=>'استرخِ واستعد توازنك.','den'=>'Relax and recharge.'],
                ['i'=>'fas fa-clinic-medical','ar'=>'عيادات تجميل','en'=>'Aesthetic Clinics','dar'=>'علاجات متقدمة بإشراف متخصصين.','den'=>'Advanced treatments by specialists.'],
                ['i'=>'fas fa-hand-sparkles','ar'=>'تجميل الأظافر','en'=>'Nail Care','dar'=>'تصاميم وعناية احترافية.','den'=>'Creative nail designs & care.'],
                ['i'=>'fas fa-magic','ar'=>'مكياج احترافي','en'=>'Makeup','dar'=>'إطلالات مميزة لكل مناسبة.','den'=>'Stunning looks for every occasion.'],
                ['i'=>'fas fa-tooth','ar'=>'عيادات الأسنان','en'=>'Dental Clinics','dar'=>'ابتسامة صحية ومضيئة.','den'=>'Healthy & bright smiles.'],
            ];
        @endphp
        <div class="v2-svc-row appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="200" data-plugin-options="{'minWindowWidth':0}">
            @foreach($svcs2 as $sv)
            <div class="v2-svc-card">
                <div class="v2-svc-card-icon"><i class="{{ $sv['i'] }}"></i></div>
                <h5>{{ $isAr ? $sv['ar'] : $sv['en'] }}</h5>
                <p>{{ $isAr ? $sv['dar'] : $sv['den'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── HOW IT WORKS ── --}}
<section id="v2-how" style="padding:90px 0;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="v2-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">{{ $isAr ? 'بسيط وسريع' : 'Simple & Fast' }}</div>
            <h2 class="v2-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                {{ $isAr ? 'كيف' : 'How' }} <span>{{ $isAr ? 'يعمل بوكسي؟' : 'Booksy Works?' }}</span>
            </h2>
            <div class="v2-divider center"></div>
        </div>
        @php
            $steps2=[
                ['i'=>'fas fa-search','ar'=>'ابحث واختر','en'=>'Search & Choose','dar'=>'ابحث في تصنيفك المفضل.','den'=>'Find the perfect salon or clinic.'],
                ['i'=>'fas fa-calendar-alt','ar'=>'حدد الموعد','en'=>'Pick a Time','dar'=>'اختر الخدمة والوقت المناسب.','den'=>'Choose your service & time slot.'],
                ['i'=>'fas fa-check-circle','ar'=>'استمتع','en'=>'Enjoy','dar'=>'أكّد وانتظر خدمتك الاستثنائية.','den'=>'Confirm and enjoy premium care.'],
            ];
        @endphp
        <div class="row g-4 justify-content-center">
            @foreach($steps2 as $idx => $step)
            <div class="col-md-4 appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ $idx*150 }}" data-plugin-options="{'minWindowWidth':0}">
                <div class="v2-step">
                    <div class="v2-step-num">{{ $idx+1 }}</div>
                    <div class="v2-step-icon"><i class="{{ $step['i'] }}"></i></div>
                    <h5>{{ $isAr ? $step['ar'] : $step['en'] }}</h5>
                    <p>{{ $isAr ? $step['dar'] : $step['den'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── TESTIMONIALS ── --}}
<section id="v2-reviews" style="padding:90px 0;background:var(--bg2);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="v2-label appear-animation" data-appear-animation="fadeInDown" data-plugin-options="{'minWindowWidth':0}">{{ $isAr ? 'آراء عملائنا' : 'Client Reviews' }}</div>
            <h2 class="v2-heading appear-animation" data-appear-animation="maskUp" data-plugin-options="{'minWindowWidth':0}">
                {{ $isAr ? 'تجارب' : 'Real' }} <span>{{ $isAr ? 'حقيقية' : 'Experiences' }}</span>
            </h2>
            <div class="v2-divider center"></div>
        </div>
        @php
            $revs=[
                ['q_ar'=>'بوكسي غيّرت طريقتي في الحجز! سريعة وسهلة وموثوقة.','q_en'=>'Booksy changed how I book! Fast, easy, and reliable.','n_ar'=>'سارة م.','n_en'=>'Sarah M.','r_ar'=>'عميلة منتظمة','r_en'=>'Regular Client'],
                ['q_ar'=>'وجدت أفضل صالون في منطقتي عبر بوكسي. ممتاز!','q_en'=>'Found the best salon in my area. Excellent!','n_ar'=>'نورة ع.','n_en'=>'Noura A.','r_ar'=>'عميلة','r_en'=>'Client'],
                ['q_ar'=>'سجّلت صالوني وتضاعفت حجوزاتي خلال أسبوعين!','q_en'=>'Bookings doubled in just two weeks!','n_ar'=>'أميرة خ.','n_en'=>'Amira K.','r_ar'=>'صاحبة صالون','r_en'=>'Salon Owner'],
            ];
        @endphp
        <div class="row g-4">
            @foreach($revs as $ri => $rev)
            <div class="col-md-4 appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ $ri*120 }}" data-plugin-options="{'minWindowWidth':0}">
                <div class="v2-review-card">
                    <div class="v2-review-quote">"</div>
                    <div class="v2-review-stars">
                        @for($s=0;$s<5;$s++)<i class="fas fa-star"></i>@endfor
                    </div>
                    <p class="v2-review-text">{{ $isAr ? $rev['q_ar'] : $rev['q_en'] }}</p>
                    <div class="v2-review-author">
                        <div class="v2-review-avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <p class="v2-review-name">{{ $isAr ? $rev['n_ar'] : $rev['n_en'] }}</p>
                            <p class="v2-review-role">{{ $isAr ? $rev['r_ar'] : $rev['r_en'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── CTA ── --}}
<section id="v2-cta" style="padding:100px 0;">
    <div class="container position-relative" style="z-index:2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 appear-animation" data-appear-animation="fadeInLeft" data-plugin-options="{'minWindowWidth':0}">
                <div class="v2-label">{{ $isAr ? 'للأعمال التجارية' : 'For Businesses' }}</div>
                <h2 class="v2-heading" style="font-size:2rem;">
                    {{ $isAr ? 'هل تمتلك' : 'Own a' }} <span>{{ $isAr ? 'صالوناً أو عيادة؟' : 'Salon or Clinic?' }}</span>
                </h2>
                <p style="color:rgba(255,255,255,.5);font-size:.94rem;margin:16px 0 28px;font-family:'Poppins',sans-serif;line-height:1.7;">
                    {{ $isAr ? 'انضم إلى بوكسي وابدأ في قبول الحجوزات عبر الإنترنت اليوم.' : 'Join Booksy and start accepting online bookings today.' }}
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('company.register') }}" class="v2-btn-gold">
                        <i class="fas fa-rocket"></i> {{ $isAr ? 'سجّل مجاناً' : 'Register Free' }}
                    </a>
                    <a href="{{ route('company.login') }}" class="v2-btn-outline">
                        {{ $isAr ? 'لديك حساب؟ ادخل' : 'Already have an account?' }}
                    </a>
                </div>
            </div>
            <div class="col-lg-6 appear-animation" data-appear-animation="fadeInRight" data-appear-animation-delay="200" data-plugin-options="{'minWindowWidth':0}">
                @php
                    $feats2=[['i'=>'fas fa-calendar-alt','ar'=>'إدارة المواعيد بسهولة','en'=>'Easy Appointment Management'],['i'=>'fas fa-users','ar'=>'إدارة الموظفين والفروع','en'=>'Staff & Branch Management'],['i'=>'fas fa-chart-line','ar'=>'تقارير وإحصائيات','en'=>'Reports & Analytics'],['i'=>'fas fa-bell','ar'=>'إشعارات تلقائية للعملاء','en'=>'Automated Notifications']];
                @endphp
                <div style="background:rgba(255,255,255,.03);border:1px solid rgba(201,162,39,.15);border-radius:20px;padding:32px;">
                    @foreach($feats2 as $ff)
                    <div class="v2-cta-feat">
                        <div class="v2-cta-feat-icon"><i class="{{ $ff['i'] }}"></i></div>
                        <div class="v2-cta-feat-text">
                            <h6>{{ $isAr ? $ff['ar'] : $ff['en'] }}</h6>
                            <p>{{ $isAr ? 'مدمج في المنصة' : 'Built into the platform' }}</p>
                        </div>
                        <i class="fas fa-check-circle ms-auto" style="color:var(--gold);"></i>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@include('front.partials.footer')

{{-- ── زر المقارنة ── --}}
<a href="{{ route('front.index') }}" class="v2-compare">
    <i class="fas fa-exchange-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
    {{ $isAr ? 'النسخة الأولى' : 'Version 1' }}
</a>

{{-- ── زر الخريطة العائم ── --}}
<button id="v2-map-fab" onclick="openMap()" title="{{ $isAr ? 'عرض الخريطة' : 'Show Map' }}">
    <i class="fas fa-map-marked-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
    <span class="v2-fab-tooltip">{{ $isAr ? 'خريطة الفروع' : 'Branches Map' }}</span>
</button>

{{-- ── مودال الخريطة ── --}}
<div id="v2-map-modal">
    <div class="v2-map-container">
        <div class="v2-map-header">
            <h5>
                <i class="fas fa-map-marked-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                {{ $isAr ? 'خريطة الفروع' : 'Branches Map' }}
            </h5>
            <button class="v2-map-close" onclick="closeMap()">
                <i class="fas fa-times" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            </button>
        </div>
        <div id="v2-leaflet-map" style="height:500px;width:100%;display:block;border-radius:0 0 20px 20px;"></div>
    </div>
</div>

{{-- SCRIPTS --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/custom.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const V2_IS_AR   = {{ $isAr ? 'true' : 'false' }};
const V2_API_URL = '{{ route('front.branches.json') }}';
const V2_MAP_URL = '{{ route('front.map.branches') }}';

let v2CurrentCategory = '';
let v2CurrentPage     = 1;
let v2Loading         = false;
let v2LeafletMap      = null;
let v2MapLoaded       = false;

/* ── NAVBAR SCROLL ── */
$(window).on('scroll',function(){
    $(this).scrollTop()>30 ? $('#v2-nav').addClass('scrolled') : $('#v2-nav').removeClass('scrolled');
});

/* ── CATEGORY FILTER ── */
document.getElementById('v2-filter-bar')?.addEventListener('click', function(e){
    const btn = e.target.closest('.v2-fbtn');
    if(!btn) return;
    this.querySelectorAll('.v2-fbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    v2CurrentCategory = btn.dataset.slug;
    v2CurrentPage = 1;
    loadBranches(true);

    // smooth scroll to branches
    document.getElementById('v2-branches').scrollIntoView({behavior:'smooth', block:'start'});
});

/* ── LOAD MORE ── */
document.getElementById('v2-load-more')?.addEventListener('click', function(){
    v2CurrentPage++;
    loadBranches(false);
});

/* ── AJAX LOADER ── */
function loadBranches(reset){
    if(v2Loading) return;
    v2Loading = true;
    const grid = document.getElementById('v2-branches-grid');

    if(reset){
        grid.innerHTML = skeletons(4);
        grid.style.opacity = '.5';
    }

    const params = new URLSearchParams({page: v2CurrentPage});
    if(v2CurrentCategory) params.set('category', v2CurrentCategory);

    fetch(V2_API_URL + '?' + params)
        .then(r => r.json())
        .then(data => {
            grid.style.opacity = '1';
            if(reset) grid.innerHTML = '';

            if(data.items.length === 0 && reset){
                grid.innerHTML = '';
                document.getElementById('v2-empty-state').style.display = 'block';
                document.getElementById('v2-load-more-wrap').style.display = 'none';
            } else {
                document.getElementById('v2-empty-state').style.display = 'none';
                data.items.forEach((b,i) => {
                    grid.insertAdjacentHTML('beforeend', buildCard(b));
                });
                document.getElementById('v2-branch-count').textContent = data.total;
                document.getElementById('v2-load-more-wrap').style.display = data.has_more ? 'block' : 'none';
            }
        })
        .catch(()=>{ grid.style.opacity='1'; })
        .finally(()=>{ v2Loading = false; });
}

function skeletons(n){
    let h='';
    for(let i=0;i<n;i++) h+=`<div class="col-lg-6"><div class="v2-skeleton"></div></div>`;
    return h;
}

function buildCard(b){
    const stars = b.avg_rating ? starsHtml(b.avg_rating, b.review_count) : '';
    const img   = b.image
        ? `<img src="${b.image}" alt="${esc(b.name)}" loading="lazy" style="width:100%;height:100%;object-fit:cover;">`
        : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a1a1a,#111);font-size:3rem;color:rgba(201,162,39,.12);">🏪</div>`;
    const logo = b.company_logo
        ? `<img src="${b.company_logo}" alt="" style="width:100%;height:100%;object-fit:cover;">`
        : `<span style="font-size:.7rem;color:var(--gold);">🏪</span>`;
    const addr = b.address
        ? `<div class="v2-hcard-addr"><span style="color:var(--gold);font-size:.67rem;">📍</span> ${esc(b.address.substring(0,40))}</div>`
        : '';
    const svc  = b.svc_count > 0
        ? `<span class="v2-chip">${b.svc_count} ${V2_IS_AR?'خدمة':'svcs'}</span>`
        : '';
    const badge= b.category ? `<span class="v2-hcard-badge">${esc(b.category)}</span>` : '';
    const rating = b.avg_rating
        ? `<span class="v2-hcard-rating">⭐ ${b.avg_rating} <span style="color:rgba(255,255,255,.35);font-size:.6rem;">· ${b.review_count}</span></span>`
        : '';

    return `<div class="col-lg-6">
        <div class="v2-hcard">
            <div class="v2-hcard-img">${img}${badge}${rating}</div>
            <div class="v2-hcard-body">
                <div class="v2-hcard-company">
                    <div class="v2-hcard-logo">${logo}</div>
                    <span class="v2-hcard-company-name">${esc(b.company_name)}</span>
                </div>
                <div class="v2-hcard-name">${esc(b.name)}</div>
                ${addr}
                ${stars}
                <div class="v2-hcard-chips">${svc}<span class="v2-chip">${V2_IS_AR?'فرع':'Branch'}</span></div>
                <a href="${b.url}" class="v2-hcard-book">${V2_IS_AR?'احجز الآن':'Book Now'}</a>
            </div>
        </div>
    </div>`;
}

function starsHtml(avg, count){
    let s = '<div class="v2-hcard-stars">';
    for(let i=1;i<=5;i++){
        const c = i<=Math.round(avg) ? 'var(--gold)' : 'rgba(255,255,255,.2)';
        s += `<span style="color:${c};font-size:.6rem;">★</span>`;
    }
    s += `<span style="font-size:.68rem;color:rgba(255,255,255,.3);margin-left:3px;">(${count})</span></div>`;
    return s;
}

function esc(str){ const d=document.createElement('div');d.textContent=str||'';return d.innerHTML; }

/* ── LEAFLET MAP ── */
function openMap(){
    document.getElementById('v2-map-modal').classList.add('open');
    document.body.style.overflow='hidden';
    if(!v2MapLoaded){
        setTimeout(initMap, 300);
    } else {
        setTimeout(()=>{ v2LeafletMap && v2LeafletMap.invalidateSize(); }, 300);
    }
}
function closeMap(){
    document.getElementById('v2-map-modal').classList.remove('open');
    document.body.style.overflow='';
}
document.getElementById('v2-map-modal')?.addEventListener('click',function(e){
    if(e.target===this) closeMap();
});
document.addEventListener('keydown', e => e.key==='Escape' && closeMap());

function initMap(){
    v2MapLoaded = true;
    const mapEl = document.getElementById('v2-leaflet-map');
    console.log('[Map] el size:', mapEl.offsetWidth, 'x', mapEl.offsetHeight, '| display:', getComputedStyle(mapEl).display);

    v2LeafletMap = L.map('v2-leaflet-map', {
        center: [33.51, 36.29],
        zoom: 12,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{
        attribution:'&copy; <a href="https://carto.com/">CARTO</a>',
        maxZoom:19
    }).addTo(v2LeafletMap);

    // Custom gold marker
    const goldIcon = L.divIcon({
        className:'',
        html:`<div style="width:32px;height:32px;border-radius:50% 50% 50% 0;background:#C9A227;border:2px solid #fff;transform:rotate(-45deg);box-shadow:0 3px 12px rgba(201,162,39,.6);display:flex;align-items:center;justify-content:center;">
                <span style="transform:rotate(45deg);font-size:.7rem;">✂</span>
              </div>`,
        iconSize:[32,32],
        iconAnchor:[16,32],
        popupAnchor:[0,-34],
    });

    fetch(V2_MAP_URL)
        .then(r=>r.json())
        .then(branches=>{
            if(!branches.length){
                // No coordinates — show demo marker
                L.marker([33.51,36.29],{icon:goldIcon})
                 .addTo(v2LeafletMap)
                 .bindPopup('<p style="font-family:Poppins,sans-serif;padding:8px;">'+(V2_IS_AR?'لا توجد فروع بإحداثيات بعد':'No branches with coordinates yet')+'</p>');
                return;
            }
            const bounds=[];
            branches.forEach(b=>{
                bounds.push([b.lat,b.lng]);
                const strs=b.avg_rating
                    ? `<div style="display:flex;gap:2px;margin-bottom:8px;">${'★'.repeat(Math.round(b.avg_rating))}${'☆'.repeat(5-Math.round(b.avg_rating))}<span style="font-size:.68rem;color:#888;margin-left:4px;">${b.avg_rating} (${b.review_count})</span></div>`
                    : '';
                const imgHtml = b.image
                    ? `<img src="${b.image}" style="width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:8px;">`
                    : `<div style="width:100%;height:80px;background:#f5f5f5;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:8px;">🏪</div>`;
                const popupHtml=`<div class="v2-popup">
                    ${imgHtml}
                    ${b.category?`<div class="v2-popup-cat">${b.category}</div>`:''}
                    <div class="v2-popup-name">${b.name}</div>
                    <div class="v2-popup-company">🏢 ${b.company_name}</div>
                    ${b.address?`<div class="v2-popup-addr">📍 ${b.address}</div>`:''}
                    ${strs}
                    <a href="${b.url}" class="v2-popup-book">${V2_IS_AR?'احجز الآن':'Book Now'}</a>
                </div>`;
                L.marker([b.lat,b.lng],{icon:goldIcon})
                 .addTo(v2LeafletMap)
                 .bindPopup(popupHtml, {maxWidth:260, minWidth:220});
            });
            if(bounds.length>1) v2LeafletMap.fitBounds(bounds,{padding:[40,40]});
            else v2LeafletMap.setView(bounds[0],15);
        });
}
</script>

</div>
</body>
</html>
