<!DOCTYPE html>
@php
    $isAr = app()->getLocale() === 'ar';
    $dir  = $isAr ? 'rtl' : 'ltr';
    $lang = $isAr ? 'ar' : 'en';
    $catIcons = [
        'salon'=>'fa-cut','spa'=>'fa-spa','clinic'=>'fa-clinic-medical',
        'beauty'=>'fa-magic','nail'=>'fa-hand-sparkles','hair'=>'fa-cut',
        'skin'=>'fa-leaf','dental'=>'fa-tooth','gym'=>'fa-dumbbell',
        'massage'=>'fa-hot-tub','barber'=>'fa-user-tie','lash'=>'fa-eye',
        'brow'=>'fa-smile','tattoo'=>'fa-pen-nib','wedding'=>'fa-ring',
        'laser'=>'fa-bolt',
    ];
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>{{ $isAr ? 'بوكسي – احجز موعدك' : 'Booksy – Book Your Next Appointment' }} (V3)</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap' . ($isAr ? '.rtl' : '') . '.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="{{ asset('frontend/vendor/modernizr/modernizr.min.js') }}"></script>

<style>
/* ════════════════════════════════════════
   TOKENS — Dark (default) & Light
════════════════════════════════════════ */
:root,[data-theme="dark"]{
    --gold:#C9A227;
    --gold-h:#e8c84a;
    --gold-dim:rgba(201,162,39,.13);
    --gold-border:rgba(201,162,39,.22);

    --bg:#0c0c0c;
    --bg2:#111111;
    --bg3:#181818;
    --bg4:#1f1f1f;
    --card-bg:#161616;
    --card-border:rgba(255,255,255,.07);
    --card-hover-border:rgba(201,162,39,.35);

    --nav-bg:rgba(10,10,10,.92);
    --nav-border:rgba(255,255,255,.06);

    --text:#f0f0f0;
    --text-2:rgba(255,255,255,.6);
    --text-3:rgba(255,255,255,.35);

    --input-bg:#1a1a1a;
    --input-border:rgba(255,255,255,.1);
    --input-focus:rgba(201,162,39,.4);

    --pill-bg:#1e1e1e;
    --pill-border:rgba(255,255,255,.08);
    --pill-active-bg:rgba(201,162,39,.15);
    --pill-active-border:#C9A227;
    --pill-text:rgba(255,255,255,.7);
    --pill-active-text:#C9A227;

    --section-bg:var(--bg2);
    --section-alt:var(--bg3);

    --star:#C9A227;
    --shadow:0 4px 24px rgba(0,0,0,.5);
    --shadow-lg:0 12px 48px rgba(0,0,0,.6);
    --radius:14px;
    --radius-sm:8px;
}
[data-theme="light"]{
    --gold:#B8870E;
    --gold-h:#C9A227;
    --gold-dim:rgba(184,135,14,.1);
    --gold-border:rgba(184,135,14,.25);

    --bg:#f7f7f5;
    --bg2:#ffffff;
    --bg3:#f2f2f0;
    --bg4:#ebebea;
    --card-bg:#ffffff;
    --card-border:rgba(0,0,0,.08);
    --card-hover-border:rgba(184,135,14,.45);

    --nav-bg:rgba(255,255,255,.96);
    --nav-border:rgba(0,0,0,.08);

    --text:#111111;
    --text-2:rgba(0,0,0,.55);
    --text-3:rgba(0,0,0,.3);

    --input-bg:#ffffff;
    --input-border:rgba(0,0,0,.12);
    --input-focus:rgba(184,135,14,.3);

    --pill-bg:#f0f0ee;
    --pill-border:rgba(0,0,0,.08);
    --pill-active-bg:rgba(184,135,14,.12);
    --pill-active-border:#B8870E;
    --pill-text:rgba(0,0,0,.6);
    --pill-active-text:#B8870E;

    --section-bg:#ffffff;
    --section-alt:#f7f7f5;

    --star:#B8870E;
    --shadow:0 2px 16px rgba(0,0,0,.1);
    --shadow-lg:0 8px 40px rgba(0,0,0,.14);
}

/* ════ BASE ════ */
*{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
@if($isAr)
body,*{font-family:'Tajawal',sans-serif!important;}
@else
body{font-family:'Poppins',sans-serif;}
@endif
body{background:var(--bg);color:var(--text);transition:background .3s,color .3s;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}
img{max-width:100%;}
.v3-container{max-width:1200px;margin:0 auto;padding:0 20px;}
section{padding:72px 0;}

/* ════ NAVBAR ════ */
#v3-nav{
    position:fixed;top:0;left:0;right:0;z-index:1000;
    background:var(--nav-bg);
    border-bottom:1px solid var(--nav-border);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    transition:all .3s;
}
.v3-nav-inner{
    display:flex;align-items:center;justify-content:space-between;
    height:64px;max-width:1200px;margin:0 auto;padding:0 20px;
}
.v3-logo{display:flex;align-items:center;gap:8px;font-size:1.35rem;font-weight:800;color:var(--text);}
.v3-logo span{color:var(--gold);}
.v3-logo-dot{width:8px;height:8px;background:var(--gold);border-radius:50%;margin-bottom:6px;}
.v3-nav-links{display:flex;align-items:center;gap:6px;}
.v3-nav-links a{
    padding:7px 14px;border-radius:8px;font-size:.84rem;font-weight:500;
    color:var(--text-2);transition:all .2s;
}
.v3-nav-links a:hover{background:var(--gold-dim);color:var(--gold);}
.v3-nav-actions{display:flex;align-items:center;gap:10px;}

/* Theme toggle */
.v3-theme-btn{
    width:42px;height:24px;border-radius:12px;border:none;cursor:pointer;
    background:var(--pill-bg);border:1px solid var(--pill-border);
    position:relative;transition:all .3s;display:flex;align-items:center;padding:2px;
}
.v3-theme-btn-knob{
    width:18px;height:18px;border-radius:50%;background:var(--gold);
    transition:transform .3s;display:flex;align-items:center;justify-content:center;
    font-size:.55rem;
}
[data-theme="light"] .v3-theme-btn-knob{transform:translateX({{ $isAr ? '-' : '' }}18px);}
.v3-btn-outline{
    border:1.5px solid var(--gold-border);color:var(--gold);
    padding:8px 18px;border-radius:9px;font-size:.84rem;font-weight:600;
    background:transparent;cursor:pointer;transition:all .2s;
}
.v3-btn-outline:hover{background:var(--gold-dim);}
.v3-btn-solid{
    background:var(--gold);color:#0a0a0a;
    padding:8px 20px;border-radius:9px;font-size:.84rem;font-weight:700;
    border:none;cursor:pointer;transition:all .2s;
}
.v3-btn-solid:hover{background:var(--gold-h);transform:translateY(-1px);}

/* ════ HERO ════ */
#v3-hero{
    padding-top:100px;padding-bottom:0;
    background:var(--bg2);
    min-height:580px;
    position:relative;
    overflow:hidden;
}
.v3-hero-bg{
    position:absolute;inset:0;
    background:
        radial-gradient(ellipse 70% 60% at 50% 0%, rgba(201,162,39,.07) 0%, transparent 70%);
    pointer-events:none;
}
.v3-hero-inner{
    max-width:1200px;margin:0 auto;padding:0 20px;
    display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:60px;
    padding-bottom:60px;
}
.v3-hero-text{}
.v3-hero-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    color:var(--gold);padding:5px 14px;border-radius:30px;
    font-size:.75rem;font-weight:600;letter-spacing:.5px;text-transform:uppercase;
    margin-bottom:20px;
}
.v3-hero-title{
    font-size:clamp(2rem,4vw,3rem);font-weight:800;line-height:1.18;
    color:var(--text);margin-bottom:16px;
}
.v3-hero-title em{font-style:normal;color:var(--gold);}
.v3-hero-sub{
    font-size:1rem;color:var(--text-2);line-height:1.7;
    margin-bottom:32px;max-width:480px;font-weight:400;
}

/* Search box */
.v3-search-box{
    background:var(--card-bg);
    border:1.5px solid var(--card-border);
    border-radius:16px;
    padding:8px;
    display:flex;gap:0;
    box-shadow:var(--shadow-lg);
    transition:border-color .2s;
}
.v3-search-box:focus-within{border-color:var(--gold-border);}
.v3-search-field{
    flex:1;display:flex;align-items:center;gap:10px;
    padding:10px 14px;
}
.v3-search-field i{color:var(--gold);font-size:.9rem;flex-shrink:0;}
.v3-search-field input{
    border:none;background:transparent;outline:none;
    font-size:.9rem;color:var(--text);width:100%;
}
.v3-search-field input::placeholder{color:var(--text-3);}
.v3-search-divider{
    width:1px;background:var(--card-border);margin:8px 0;flex-shrink:0;
}
.v3-search-btn{
    background:var(--gold);color:#0a0a0a;
    border:none;border-radius:10px;padding:12px 22px;
    font-size:.88rem;font-weight:700;cursor:pointer;
    display:flex;align-items:center;gap:8px;
    transition:all .2s;white-space:nowrap;flex-shrink:0;
}
.v3-search-btn:hover{background:var(--gold-h);transform:scale(1.02);}

.v3-hero-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px;}
.v3-hero-tag{
    font-size:.75rem;color:var(--text-2);
    background:var(--pill-bg);border:1px solid var(--pill-border);
    padding:4px 12px;border-radius:20px;cursor:pointer;transition:all .2s;
}
.v3-hero-tag:hover{border-color:var(--gold-border);color:var(--gold);}

/* Hero visual */
.v3-hero-visual{position:relative;display:flex;justify-content:center;}
.v3-hero-img-wrap{
    width:100%;max-width:480px;aspect-ratio:4/3;
    border-radius:24px;overflow:hidden;
    box-shadow:var(--shadow-lg);
    border:1px solid var(--card-border);
    position:relative;
}
.v3-hero-img-wrap img{width:100%;height:100%;object-fit:cover;}
.v3-hero-img-overlay{
    position:absolute;inset:0;
    background:linear-gradient(180deg,transparent 50%,rgba(0,0,0,.5) 100%);
}
/* Floating cards on hero image */
.v3-hero-float{
    position:absolute;
    background:var(--card-bg);
    border:1px solid var(--card-border);
    border-radius:12px;
    padding:12px 16px;
    box-shadow:var(--shadow-lg);
    backdrop-filter:blur(12px);
    -webkit-backdrop-filter:blur(12px);
}
.v3-hero-float-1{
    bottom:-20px;
    {{ $isAr ? 'right:-24px' : 'left:-24px' }};
}
.v3-hero-float-2{
    top:20px;
    {{ $isAr ? 'left:-24px' : 'right:-24px' }};
}
.v3-hf-row{display:flex;align-items:center;gap:8px;}
.v3-hf-icon{
    width:36px;height:36px;border-radius:10px;
    background:var(--gold-dim);display:flex;align-items:center;justify-content:center;
}
.v3-hf-icon i{color:var(--gold);font-size:.85rem;}
.v3-hf-label{font-size:.68rem;color:var(--text-2);margin-bottom:1px;}
.v3-hf-val{font-size:.9rem;font-weight:700;color:var(--text);}
.v3-hf-stars{display:flex;gap:2px;margin-top:2px;}
.v3-hf-stars i{font-size:.5rem;color:var(--star);}

/* Wave divider */
.v3-hero-wave{
    height:60px;background:var(--bg);
    clip-path:ellipse(55% 100% at 50% 100%);
    margin-top:-2px;
}

/* ════ TRUST BAR ════ */
#v3-trust{
    padding:24px 0;
    background:var(--bg3);
    border-top:1px solid var(--card-border);
    border-bottom:1px solid var(--card-border);
}
.v3-trust-inner{
    max-width:1200px;margin:0 auto;padding:0 20px;
    display:flex;align-items:center;justify-content:center;
    flex-wrap:wrap;gap:32px;
}
.v3-trust-item{
    display:flex;align-items:center;gap:10px;
    color:var(--text-2);font-size:.82rem;
}
.v3-trust-item i{color:var(--gold);font-size:1rem;}
.v3-trust-item strong{color:var(--text);font-weight:700;}

/* ════ CATEGORIES ════ */
#v3-cats{background:var(--bg2);padding:60px 0;}
.v3-section-head{
    display:flex;align-items:flex-end;justify-content:space-between;
    margin-bottom:32px;
}
.v3-section-label{
    font-size:.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;
    color:var(--gold);margin-bottom:6px;
}
.v3-section-title{
    font-size:clamp(1.4rem,3vw,2rem);font-weight:800;color:var(--text);line-height:1.2;
}
.v3-section-sub{color:var(--text-2);font-size:.9rem;margin-top:6px;}
.v3-see-all{
    font-size:.82rem;font-weight:600;color:var(--gold);
    display:flex;align-items:center;gap:5px;white-space:nowrap;
    border:1px solid var(--gold-border);padding:7px 16px;border-radius:8px;
    transition:all .2s;
}
.v3-see-all:hover{background:var(--gold-dim);}

/* Horizontal scroll strip */
.v3-cats-strip{
    display:flex;gap:12px;
    overflow-x:auto;
    padding-bottom:8px;
    scrollbar-width:none;
    -ms-overflow-style:none;
}
.v3-cats-strip::-webkit-scrollbar{display:none;}

.v3-cat-pill{
    flex-shrink:0;
    display:flex;align-items:center;gap:10px;
    background:var(--pill-bg);
    border:1.5px solid var(--pill-border);
    border-radius:40px;
    padding:10px 20px;
    cursor:pointer;
    transition:all .22s;
    text-decoration:none;
}
.v3-cat-pill:hover,.v3-cat-pill.active{
    background:var(--pill-active-bg);
    border-color:var(--pill-active-border);
}
.v3-cat-pill-icon{
    width:32px;height:32px;border-radius:50%;
    background:var(--gold-dim);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;transition:background .22s;
}
.v3-cat-pill:hover .v3-cat-pill-icon,
.v3-cat-pill.active .v3-cat-pill-icon{background:var(--gold-dim);}
.v3-cat-pill-icon i{
    font-size:.8rem;color:var(--gold);
    font-family:'Font Awesome 5 Free'!important;font-weight:900!important;
}
.v3-cat-pill-name{font-size:.82rem;font-weight:600;color:var(--pill-text);white-space:nowrap;}
.v3-cat-pill:hover .v3-cat-pill-name,
.v3-cat-pill.active .v3-cat-pill-name{color:var(--pill-active-text);}
.v3-cat-pill-count{font-size:.68rem;color:var(--text-3);margin-top:1px;}

/* ════ BRANCHES (Fresha-style cards) ════ */
#v3-branches{background:var(--bg);padding:72px 0;}
.v3-filter-row{
    display:flex;align-items:center;gap:10px;
    flex-wrap:wrap;margin-bottom:32px;
}
.v3-filter-btn{
    background:var(--pill-bg);border:1.5px solid var(--pill-border);
    color:var(--pill-text);
    padding:8px 18px;border-radius:30px;font-size:.8rem;font-weight:600;
    cursor:pointer;transition:all .2s;
}
.v3-filter-btn.active,
.v3-filter-btn:hover{
    background:var(--pill-active-bg);
    border-color:var(--pill-active-border);
    color:var(--pill-active-text);
}
.v3-filter-spacer{flex:1;}
.v3-sort-select{
    background:var(--pill-bg);border:1.5px solid var(--pill-border);
    color:var(--text-2);padding:8px 14px;border-radius:8px;
    font-size:.8rem;cursor:pointer;outline:none;
    font-family:inherit;
}
/* Card grid — 3 col */
.v3-cards-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
}
/* Fresha-style card: image top, content below */
.v3-card{
    background:var(--card-bg);
    border:1.5px solid var(--card-border);
    border-radius:var(--radius);
    overflow:hidden;
    transition:all .25s;
    cursor:pointer;
    display:flex;flex-direction:column;
    text-decoration:none;color:var(--text);
}
.v3-card:hover{
    border-color:var(--card-hover-border);
    transform:translateY(-4px);
    box-shadow:var(--shadow-lg);
}
.v3-card-img{
    position:relative;width:100%;height:190px;overflow:hidden;background:var(--bg4);
}
.v3-card-img img{
    width:100%;height:100%;object-fit:cover;
    transition:transform .4s;
}
.v3-card:hover .v3-card-img img{transform:scale(1.06);}
.v3-card-badge{
    position:absolute;top:10px;{{ $isAr ? 'right' : 'left' }}:10px;
    background:rgba(0,0,0,.6);backdrop-filter:blur(6px);
    color:#fff;font-size:.65rem;font-weight:700;
    padding:3px 10px;border-radius:20px;
    text-transform:uppercase;letter-spacing:.5px;
}
.v3-card-fav{
    position:absolute;top:10px;{{ $isAr ? 'left' : 'right' }}:10px;
    width:32px;height:32px;border-radius:50%;
    background:rgba(0,0,0,.5);backdrop-filter:blur(6px);
    display:flex;align-items:center;justify-content:center;
    color:rgba(255,255,255,.7);font-size:.75rem;transition:all .2s;
}
.v3-card-fav:hover{background:rgba(201,162,39,.9);color:#fff;}
.v3-card-logo{
    position:absolute;bottom:10px;{{ $isAr ? 'right' : 'left' }}:10px;
    width:38px;height:38px;border-radius:10px;
    background:var(--card-bg);
    border:2px solid rgba(255,255,255,.15);
    overflow:hidden;display:flex;align-items:center;justify-content:center;
    font-size:.8rem;color:var(--gold);font-weight:800;
}
.v3-card-logo img{width:100%;height:100%;object-fit:cover;}
.v3-card-body{padding:14px 16px 16px;flex:1;display:flex;flex-direction:column;}
.v3-card-meta{display:flex;align-items:center;gap:6px;margin-bottom:6px;}
.v3-card-cat{
    font-size:.65rem;font-weight:700;color:var(--gold);
    text-transform:uppercase;letter-spacing:.8px;
}
.v3-card-dot{width:3px;height:3px;border-radius:50%;background:var(--text-3);}
.v3-card-status{
    font-size:.65rem;font-weight:600;
    color:#22c55e;
}
.v3-card-name{font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:2px;line-height:1.3;}
.v3-card-company{
    font-size:.75rem;color:var(--text-2);margin-bottom:10px;
    display:flex;align-items:center;gap:5px;
}
.v3-card-company i{font-size:.65rem;color:var(--text-3);}
.v3-card-footer{
    display:flex;align-items:center;justify-content:space-between;
    margin-top:auto;padding-top:12px;
    border-top:1px solid var(--card-border);
}
.v3-card-rating{display:flex;align-items:center;gap:5px;}
.v3-card-rating-stars{display:flex;gap:1px;}
.v3-card-rating-stars i{font-size:.6rem;color:var(--star);}
.v3-card-rating-num{font-size:.78rem;font-weight:700;color:var(--text);}
.v3-card-rating-cnt{font-size:.7rem;color:var(--text-3);}
.v3-card-book-btn{
    background:var(--gold);color:#0a0a0a;
    padding:7px 16px;border-radius:8px;
    font-size:.75rem;font-weight:700;
    transition:all .2s;border:none;
}
.v3-card:hover .v3-card-book-btn{background:var(--gold-h);}
.v3-card-img-ph{
    width:100%;height:100%;
    background:linear-gradient(135deg,var(--bg3),var(--bg4));
    display:flex;align-items:center;justify-content:center;
    font-size:2.5rem;color:var(--text-3);
}
/* No results */
.v3-empty{
    grid-column:1/-1;text-align:center;padding:80px 20px;color:var(--text-2);
}
.v3-empty-icon{font-size:3rem;color:var(--text-3);margin-bottom:16px;}
/* Skeleton */
.v3-skel{
    background:linear-gradient(90deg,var(--bg3) 25%,var(--bg4) 50%,var(--bg3) 75%);
    background-size:200% 100%;
    animation:v3shimmer 1.4s infinite;
    border-radius:var(--radius);
}
@keyframes v3shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
/* Load more */
.v3-load-more-wrap{text-align:center;margin-top:40px;}
.v3-load-more-btn{
    background:transparent;border:1.5px solid var(--gold-border);
    color:var(--gold);padding:12px 40px;border-radius:10px;
    font-size:.88rem;font-weight:600;cursor:pointer;transition:all .2s;
    font-family:inherit;
}
.v3-load-more-btn:hover{background:var(--gold-dim);}
.v3-load-more-btn:disabled{opacity:.4;cursor:not-allowed;}

/* ════ POPULAR — horizontal scroll ════ */
#v3-popular{background:var(--bg2);padding:72px 0;}
.v3-popular-row{
    display:flex;gap:18px;overflow-x:auto;padding-bottom:8px;
    scrollbar-width:none;
}
.v3-popular-row::-webkit-scrollbar{display:none;}
/* Wide horizontal card */
.v3-pop-card{
    flex-shrink:0;width:300px;
    background:var(--card-bg);
    border:1.5px solid var(--card-border);
    border-radius:var(--radius);
    overflow:hidden;transition:all .25s;
    text-decoration:none;color:var(--text);
}
.v3-pop-card:hover{border-color:var(--card-hover-border);transform:translateY(-3px);box-shadow:var(--shadow-lg);}
.v3-pop-img{position:relative;height:160px;overflow:hidden;background:var(--bg4);}
.v3-pop-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.v3-pop-card:hover .v3-pop-img img{transform:scale(1.06);}
.v3-pop-rank{
    position:absolute;top:10px;{{ $isAr ? 'right' : 'left' }}:10px;
    width:28px;height:28px;border-radius:50%;
    background:var(--gold);color:#0a0a0a;
    font-size:.75rem;font-weight:800;
    display:flex;align-items:center;justify-content:center;
}
.v3-pop-body{padding:14px;}
.v3-pop-name{font-size:.9rem;font-weight:700;margin-bottom:4px;}
.v3-pop-company{font-size:.72rem;color:var(--text-2);margin-bottom:10px;}
.v3-pop-footer{display:flex;align-items:center;justify-content:space-between;}
.v3-pop-rating{display:flex;align-items:center;gap:4px;font-size:.78rem;font-weight:700;}
.v3-pop-rating i{color:var(--star);font-size:.7rem;}
.v3-pop-svcs{font-size:.7rem;color:var(--text-3);}

/* ════ HOW IT WORKS ════ */
#v3-how{background:var(--bg3);padding:72px 0;}
.v3-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;position:relative;}
/* connecting line */
.v3-steps::before{
    content:'';
    position:absolute;top:28px;
    {{ $isAr ? 'right:14%;left:14%' : 'left:14%;right:14%' }};
    height:2px;
    background:linear-gradient(90deg,var(--gold),rgba(201,162,39,.2));
}
.v3-step{text-align:center;padding:32px 24px;position:relative;}
.v3-step-num{
    width:56px;height:56px;border-radius:50%;
    background:var(--gold-dim);border:2px solid var(--gold-border);
    color:var(--gold);font-size:1.2rem;font-weight:800;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 20px;
    position:relative;z-index:1;
    transition:all .3s;
}
.v3-step:hover .v3-step-num{background:var(--gold);color:#0a0a0a;}
.v3-step-icon{font-size:1.5rem;color:var(--gold);margin-bottom:12px;
    font-family:'Font Awesome 5 Free'!important;font-weight:900!important;}
.v3-step-title{font-size:1rem;font-weight:700;margin-bottom:8px;color:var(--text);}
.v3-step-desc{font-size:.84rem;color:var(--text-2);line-height:1.65;}

/* ════ APP BANNER ════ */
#v3-app{background:var(--bg2);padding:72px 0;}
.v3-app-inner{
    max-width:900px;margin:0 auto;
    background:linear-gradient(135deg,var(--bg3) 0%,var(--bg4) 100%);
    border:1.5px solid var(--gold-border);
    border-radius:24px;padding:48px;
    display:grid;grid-template-columns:1fr auto;align-items:center;gap:40px;
    position:relative;overflow:hidden;
}
.v3-app-inner::before{
    content:'';position:absolute;top:-80px;{{ $isAr ? 'left:-80px' : 'right:-80px' }};
    width:280px;height:280px;border-radius:50%;
    background:radial-gradient(circle,rgba(201,162,39,.12) 0%,transparent 70%);
    pointer-events:none;
}
.v3-app-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    color:var(--gold);padding:4px 12px;border-radius:20px;
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
    margin-bottom:14px;
}
.v3-app-title{font-size:1.6rem;font-weight:800;margin-bottom:10px;color:var(--text);}
.v3-app-sub{font-size:.9rem;color:var(--text-2);line-height:1.6;margin-bottom:24px;}
.v3-app-btns{display:flex;gap:12px;flex-wrap:wrap;}
.v3-app-btn{
    display:flex;align-items:center;gap:10px;
    background:var(--text);color:var(--bg);
    padding:12px 20px;border-radius:12px;
    font-size:.82rem;font-weight:700;
    transition:all .2s;
}
.v3-app-btn:hover{background:var(--gold);color:#0a0a0a;transform:translateY(-2px);}
.v3-app-btn i{font-size:1.3rem;}
.v3-app-btn small{display:block;font-size:.65rem;font-weight:400;opacity:.7;}
.v3-app-qr{
    width:120px;height:120px;border-radius:16px;overflow:hidden;
    background:var(--bg2);border:1px solid var(--card-border);
    display:flex;align-items:center;justify-content:center;
    font-size:4rem;color:var(--text-3);flex-shrink:0;
}

/* ════ TESTIMONIALS ════ */
#v3-reviews{background:var(--bg);padding:72px 0;}
.v3-rev-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;}
.v3-rev-card{
    background:var(--card-bg);border:1.5px solid var(--card-border);
    border-radius:var(--radius);padding:26px;transition:border-color .2s;
}
.v3-rev-card:hover{border-color:var(--gold-border);}
.v3-rev-top{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.v3-rev-avatar{
    width:44px;height:44px;border-radius:50%;
    background:var(--gold-dim);border:2px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;font-weight:800;color:var(--gold);flex-shrink:0;
}
.v3-rev-info{}
.v3-rev-name{font-size:.88rem;font-weight:700;color:var(--text);}
.v3-rev-branch{font-size:.72rem;color:var(--text-3);}
.v3-rev-stars{display:flex;gap:2px;margin-bottom:10px;}
.v3-rev-stars i{font-size:.65rem;color:var(--star);}
.v3-rev-text{font-size:.83rem;color:var(--text-2);line-height:1.7;}
.v3-rev-date{font-size:.68rem;color:var(--text-3);margin-top:12px;}

/* ════ FOOTER ════ */
#v3-footer{
    background:var(--bg3);
    border-top:1px solid var(--card-border);
    padding:48px 0 24px;
}
.v3-footer-grid{
    display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;
    margin-bottom:40px;
}
.v3-footer-brand .v3-logo{margin-bottom:14px;}
.v3-footer-desc{font-size:.82rem;color:var(--text-2);line-height:1.7;max-width:260px;}
.v3-footer-social{display:flex;gap:10px;margin-top:18px;}
.v3-footer-social a{
    width:36px;height:36px;border-radius:50%;
    background:var(--pill-bg);border:1px solid var(--pill-border);
    display:flex;align-items:center;justify-content:center;
    color:var(--text-2);font-size:.8rem;transition:all .2s;
}
.v3-footer-social a:hover{background:var(--gold-dim);border-color:var(--gold-border);color:var(--gold);}
.v3-footer-col-title{font-size:.8rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;}
.v3-footer-col ul{list-style:none;}
.v3-footer-col ul li{margin-bottom:10px;}
.v3-footer-col ul li a{font-size:.82rem;color:var(--text-2);transition:color .2s;}
.v3-footer-col ul li a:hover{color:var(--gold);}
.v3-footer-bottom{
    border-top:1px solid var(--card-border);padding-top:20px;
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
}
.v3-footer-bottom p{font-size:.78rem;color:var(--text-3);}
.v3-footer-bottom span{color:var(--gold);}

/* ════ FAB MAP ════ */
#v3-map-fab{
    position:fixed;bottom:28px;{{ $isAr ? 'left' : 'right' }}:28px;
    z-index:900;
    width:54px;height:54px;border-radius:50%;
    background:var(--gold);color:#0a0a0a;
    border:none;cursor:pointer;
    box-shadow:0 6px 28px rgba(201,162,39,.5);
    display:flex;align-items:center;justify-content:center;
    font-size:1.2rem;
    transition:all .25s;
}
#v3-map-fab:hover{transform:scale(1.1);box-shadow:0 8px 36px rgba(201,162,39,.7);}
.v3-fab-tip{
    position:absolute;
    {{ $isAr ? 'right:calc(100% + 10px)' : 'right:calc(100% + 10px)' }};
    background:var(--bg3);border:1px solid var(--card-border);
    color:var(--text);font-size:.72rem;font-weight:600;
    padding:5px 12px;border-radius:8px;white-space:nowrap;
    opacity:0;pointer-events:none;transition:opacity .2s;
}
#v3-map-fab:hover .v3-fab-tip{opacity:1;}

/* ════ MAP MODAL ════ */
#v3-map-modal{
    position:fixed;inset:0;z-index:9999;
    display:none;align-items:center;justify-content:center;
    background:rgba(0,0,0,.75);backdrop-filter:blur(8px);
    padding:20px;
}
#v3-map-modal.open{display:flex;}
.v3-map-wrap{
    width:100%;max-width:1000px;
    background:var(--card-bg);
    border:1px solid var(--card-border);
    border-radius:20px;overflow:hidden;
    box-shadow:var(--shadow-lg);
}
.v3-map-head{
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;
    background:var(--bg3);
    border-bottom:1px solid var(--card-border);
}
.v3-map-head h5{
    margin:0;font-size:.95rem;font-weight:700;color:var(--text);
    display:flex;align-items:center;gap:8px;
}
.v3-map-head h5 i{color:var(--gold);}
.v3-map-close-btn{
    width:32px;height:32px;border-radius:50%;border:none;cursor:pointer;
    background:var(--pill-bg);color:var(--text-2);
    display:flex;align-items:center;justify-content:center;
    font-size:.8rem;transition:all .2s;
}
.v3-map-close-btn:hover{background:rgba(231,76,60,.2);color:#e74c3c;}
#v3-leaflet-map{height:500px;width:100%;display:block;}

/* ════ COMPARE STRIP ════ */
#v3-compare{
    position:fixed;bottom:0;left:0;right:0;z-index:800;
    background:var(--bg3);border-top:1px solid var(--card-border);
    padding:8px 20px;
    display:flex;align-items:center;justify-content:center;gap:10px;
    font-size:.78rem;color:var(--text-2);
}
#v3-compare a{
    background:var(--gold-dim);border:1px solid var(--gold-border);
    color:var(--gold);padding:5px 14px;border-radius:6px;
    font-size:.75rem;font-weight:600;transition:all .2s;
}
#v3-compare a:hover{background:var(--gold);color:#0a0a0a;}

/* ════ LEAFLET CSS ISOLATION ════ */
#v3-leaflet-map * { box-sizing:content-box !important; }
.leaflet-tile{
    max-width:none !important;width:256px !important;height:256px !important;
    border:none !important;box-shadow:none !important;padding:0 !important;
    margin:0 !important;border-radius:0 !important;object-fit:unset !important;
    opacity:1 !important;visibility:visible !important;
}
.leaflet-pane img,.leaflet-tile-container img{
    max-width:none !important;border-radius:0 !important;
    box-shadow:none !important;opacity:1 !important;visibility:visible !important;
    animation:none !important;transition:opacity 0.2s linear !important;
}
.leaflet-container{background:var(--bg3) !important;font-family:'Poppins',sans-serif !important;}
.leaflet-popup-content-wrapper{border-radius:12px !important;box-shadow:var(--shadow-lg) !important;padding:0 !important;overflow:hidden !important;}
.leaflet-popup-content{margin:0 !important;width:240px !important;}
.leaflet-control-zoom a{background:var(--card-bg) !important;color:var(--gold) !important;border-color:var(--card-border) !important;}
.leaflet-control-zoom a:hover{background:var(--gold) !important;color:#0a0a0a !important;}

/* ════ RESPONSIVE ════ */
@media(max-width:991px){
    .v3-hero-inner{grid-template-columns:1fr;gap:40px;}
    .v3-hero-visual{display:none;}
    .v3-cards-grid{grid-template-columns:repeat(2,1fr);}
    .v3-steps{grid-template-columns:1fr;}
    .v3-steps::before{display:none;}
    .v3-rev-grid{grid-template-columns:1fr;}
    .v3-footer-grid{grid-template-columns:1fr 1fr;}
    .v3-app-inner{grid-template-columns:1fr;}
}
@media(max-width:600px){
    .v3-cards-grid{grid-template-columns:1fr;}
    .v3-search-box{flex-direction:column;gap:8px;}
    .v3-search-divider{display:none;}
    .v3-footer-grid{grid-template-columns:1fr;}
    .v3-nav-links{display:none;}
}
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

{{-- ══════════ NAVBAR ══════════ --}}
<nav id="v3-nav">
<div class="v3-nav-inner">
    <a href="{{ route('front.index') }}" class="v3-logo">
        <div class="v3-logo-dot"></div>
        book<span>sy</span>
    </a>
    <div class="v3-nav-links">
        <a href="#">{{ $isAr ? 'استكشف' : 'Explore' }}</a>
        <a href="#">{{ $isAr ? 'الفئات' : 'Categories' }}</a>
        <a href="{{ route('front.about') }}">{{ $isAr ? 'عن بوكسي' : 'About' }}</a>
        <a href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل معنا' : 'Contact' }}</a>
    </div>
    <div class="v3-nav-actions">
        {{-- Language --}}
        @if($isAr)
            <a href="{{ route('locale.switch','en') }}" class="v3-btn-outline" style="padding:6px 14px;font-size:.78rem;">EN</a>
        @else
            <a href="{{ route('locale.switch','ar') }}" class="v3-btn-outline" style="padding:6px 14px;font-size:.78rem;">ع</a>
        @endif
        {{-- Dark / Light toggle --}}
        <button class="v3-theme-btn" id="v3-theme-toggle" title="{{ $isAr ? 'تبديل المظهر' : 'Toggle theme' }}">
            <div class="v3-theme-btn-knob" id="v3-theme-knob">
                <i class="fas fa-moon" id="v3-theme-icon" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.55rem;"></i>
            </div>
        </button>
        <a href="#" class="v3-btn-solid">{{ $isAr ? 'سجّل نشاطك' : 'List Your Business' }}</a>
    </div>
</div>
</nav>

{{-- ══════════ HERO ══════════ --}}
<section id="v3-hero">
<div class="v3-hero-bg"></div>
<div class="v3-hero-inner">
    <div class="v3-hero-text">
        <div class="v3-hero-badge">
            <i class="fas fa-star" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.65rem;"></i>
            {{ $isAr ? 'أكثر من ١٠٠٠ موعد محجوز' : 'Over 1,000 appointments booked' }}
        </div>
        <h1 class="v3-hero-title">
            {{ $isAr ? 'اكتشف أفضل' : 'Discover the best' }}<br>
            <em>{{ $isAr ? 'صالونات وعيادات' : 'salons & clinics' }}</em><br>
            {{ $isAr ? 'بالقرب منك' : 'near you' }}
        </h1>
        <p class="v3-hero-sub">
            {{ $isAr
                ? 'احجز موعدك في ثوانٍ. اختر من بين أفضل الأماكن الموثوقة حولك وابدأ رحلتك نحو الجمال والاسترخاء.'
                : 'Book your next appointment in seconds. Choose from the best verified spots around you and start your beauty journey.' }}
        </p>
        {{-- Search --}}
        <div class="v3-search-box">
            <div class="v3-search-field">
                <i class="fas fa-search" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                <input type="text" id="v3-search-input"
                    placeholder="{{ $isAr ? 'صالون، عيادة، سبا...' : 'Salon, clinic, spa...' }}">
            </div>
            <div class="v3-search-divider"></div>
            <div class="v3-search-field">
                <i class="fas fa-map-marker-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                <input type="text" placeholder="{{ $isAr ? 'الموقع' : 'Location' }}">
            </div>
            <button class="v3-search-btn" onclick="handleSearch()">
                <i class="fas fa-search" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                {{ $isAr ? 'ابحث' : 'Search' }}
            </button>
        </div>
        {{-- Popular tags --}}
        <div class="v3-hero-tags">
            <span style="font-size:.72rem;color:var(--text-3);align-self:center;">{{ $isAr ? 'الأكثر بحثاً:' : 'Trending:' }}</span>
            @foreach($categories->take(5) as $cat)
            <span class="v3-hero-tag" onclick="filterByCategory('{{ $cat->slug }}')">
                {{ $isAr ? $cat->name_ar : $cat->name_en }}
            </span>
            @endforeach
        </div>
    </div>

    <div class="v3-hero-visual">
        <div class="v3-hero-img-wrap">
            <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=900&q=80&auto=format&fit=crop"
                 alt="salon" loading="lazy">
            <div class="v3-hero-img-overlay"></div>
        </div>
        {{-- Float 1: rating --}}
        <div class="v3-hero-float v3-hero-float-1">
            <div class="v3-hf-row">
                <div class="v3-hf-icon">
                    <i class="fas fa-star" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                </div>
                <div>
                    <div class="v3-hf-label">{{ $isAr ? 'متوسط التقييم' : 'Avg Rating' }}</div>
                    <div class="v3-hf-val">4.9</div>
                    <div class="v3-hf-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- Float 2: bookings --}}
        <div class="v3-hero-float v3-hero-float-2">
            <div class="v3-hf-row">
                <div class="v3-hf-icon">
                    <i class="fas fa-calendar-check" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                </div>
                <div>
                    <div class="v3-hf-label">{{ $isAr ? 'حجز اليوم' : "Today's Bookings" }}</div>
                    <div class="v3-hf-val">+247</div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

{{-- ══════════ TRUST BAR ══════════ --}}
<div id="v3-trust">
<div class="v3-trust-inner">
    <div class="v3-trust-item">
        <i class="fas fa-shield-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
        <span><strong>{{ $isAr ? 'مرافق موثّقة' : 'Verified venues' }}</strong> {{ $isAr ? '١٠٠٪ آمن' : '100% trusted' }}</span>
    </div>
    <div class="v3-trust-item">
        <i class="fas fa-bolt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
        <span><strong>{{ $isAr ? 'حجز فوري' : 'Instant booking' }}</strong> {{ $isAr ? 'بدون تأخير' : 'No waiting' }}</span>
    </div>
    <div class="v3-trust-item">
        <i class="fas fa-undo" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
        <span><strong>{{ $isAr ? 'إلغاء مجاني' : 'Free cancellation' }}</strong> {{ $isAr ? 'قبل ٢٤ ساعة' : 'Up to 24h before' }}</span>
    </div>
    <div class="v3-trust-item">
        <i class="fas fa-headset" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
        <span><strong>{{ $isAr ? 'دعم ٢٤/٧' : '24/7 support' }}</strong> {{ $isAr ? 'نحن هنا دائماً' : 'Always here for you' }}</span>
    </div>
</div>
</div>

{{-- ══════════ CATEGORIES ══════════ --}}
<section id="v3-cats">
<div class="v3-container">
    <div class="v3-section-head">
        <div>
            <div class="v3-section-label">{{ $isAr ? 'تصفّح' : 'Browse' }}</div>
            <div class="v3-section-title">{{ $isAr ? 'كل الفئات' : 'All Categories' }}</div>
        </div>
        <a href="#v3-branches" class="v3-see-all">
            {{ $isAr ? 'عرض الكل' : 'See all' }}
            <i class="fas fa-arrow-{{ $isAr ? 'left' : 'right' }}" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.7rem;"></i>
        </a>
    </div>
    <div class="v3-cats-strip" id="v3-cat-strip">
        <div class="v3-cat-pill active" data-slug="" onclick="filterByCategory('')">
            <div class="v3-cat-pill-icon">
                <i class="fas fa-th" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            </div>
            <div>
                <div class="v3-cat-pill-name">{{ $isAr ? 'الكل' : 'All' }}</div>
            </div>
        </div>
        @foreach($categories as $cat)
        @php
            $slug = $cat->slug ?? '';
            $icon = $catIcons[$slug] ?? 'fa-store';
        @endphp
        <div class="v3-cat-pill" data-slug="{{ $slug }}" onclick="filterByCategory('{{ $slug }}')">
            <div class="v3-cat-pill-icon">
                <i class="fas {{ $icon }}" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            </div>
            <div>
                <div class="v3-cat-pill-name">{{ $isAr ? $cat->name_ar : $cat->name_en }}</div>
                @if($cat->companies_count)
                <div class="v3-cat-pill-count">{{ $cat->companies_count }} {{ $isAr ? 'مكان' : 'places' }}</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
</section>

{{-- ══════════ BRANCHES ══════════ --}}
<section id="v3-branches">
<div class="v3-container">
    <div class="v3-section-head" style="margin-bottom:20px;">
        <div>
            <div class="v3-section-label">{{ $isAr ? 'اكتشف' : 'Explore' }}</div>
            <div class="v3-section-title" id="v3-branch-title">{{ $isAr ? 'جميع الفروع' : 'All Branches' }}</div>
            <div class="v3-section-sub" id="v3-branch-sub">
                <span id="v3-branch-count">{{ $branches->total() }}</span>
                {{ $isAr ? 'فرع متاح' : 'branches available' }}
            </div>
        </div>
    </div>
    {{-- Filter row --}}
    <div class="v3-filter-row" id="v3-filter-row">
        <button class="v3-filter-btn active" data-slug="" onclick="v3FilterClick(this,'')">
            {{ $isAr ? 'الكل' : 'All' }}
        </button>
        @foreach($categories as $cat)
        <button class="v3-filter-btn" data-slug="{{ $cat->slug }}" onclick="v3FilterClick(this,'{{ $cat->slug }}')">
            {{ $isAr ? $cat->name_ar : $cat->name_en }}
        </button>
        @endforeach
        <div class="v3-filter-spacer"></div>
        <select class="v3-sort-select" id="v3-sort">
            <option value="">{{ $isAr ? 'ترتيب حسب' : 'Sort by' }}</option>
            <option value="rating">{{ $isAr ? 'الأعلى تقييماً' : 'Top Rated' }}</option>
            <option value="reviews">{{ $isAr ? 'الأكثر تقييماً' : 'Most Reviewed' }}</option>
        </select>
    </div>

    {{-- Cards --}}
    <div class="v3-cards-grid" id="v3-cards-grid">
        @forelse($branches as $branch)
        @php
            $bName    = $isAr ? ($branch->name_ar ?? $branch->name_en) : ($branch->name_en ?? $branch->name_ar);
            $cName    = $isAr ? ($branch->company->name_ar ?? $branch->company->name_en) : ($branch->company->name_en ?? $branch->company->name_ar);
            $catName  = $branch->company->category ? ($isAr ? $branch->company->category->name_ar : $branch->company->category->name_en) : null;
            $img      = $branch->images->first();
            $reviews  = $branch->reviews;
            $avg      = $reviews->count() ? round($reviews->avg('rating'),1) : null;
        @endphp
        <a href="{{ route('front.branch', $branch) }}" class="v3-card">
            <div class="v3-card-img">
                @if($img)
                <img src="{{ asset('storage/'.$img->path) }}" alt="{{ $bName }}" loading="lazy">
                @elseif($branch->company->logo)
                <img src="{{ asset('storage/'.$branch->company->logo) }}" alt="{{ $bName }}" loading="lazy">
                @else
                <div class="v3-card-img-ph">✂</div>
                @endif
                @if($catName)
                <div class="v3-card-badge">{{ $catName }}</div>
                @endif
                <div class="v3-card-fav">
                    <i class="far fa-heart" style="font-family:'Font Awesome 5 Free'!important;font-weight:400!important;"></i>
                </div>
                @if($branch->company->logo)
                <div class="v3-card-logo">
                    <img src="{{ asset('storage/'.$branch->company->logo) }}" alt="">
                </div>
                @endif
            </div>
            <div class="v3-card-body">
                <div class="v3-card-meta">
                    @if($catName)<span class="v3-card-cat">{{ $catName }}</span>@endif
                    <div class="v3-card-dot"></div>
                    <span class="v3-card-status">{{ $isAr ? 'متاح' : 'Open' }}</span>
                </div>
                <div class="v3-card-name">{{ $bName }}</div>
                <div class="v3-card-company">
                    <i class="fas fa-building" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                    {{ $cName }}
                </div>
                @if($branch->address)
                <div style="font-size:.72rem;color:var(--text-3);margin-bottom:8px;display:flex;align-items:center;gap:4px;">
                    <i class="fas fa-map-marker-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.6rem;"></i>
                    {{ Str::limit($branch->address, 45) }}
                </div>
                @endif
                <div class="v3-card-footer">
                    <div class="v3-card-rating">
                        @if($avg)
                        <div class="v3-card-rating-stars">
                            @for($s=1;$s<=5;$s++)
                            <i class="{{ $s <= round($avg) ? 'fas' : 'far' }} fa-star"
                               style="font-family:'Font Awesome 5 Free'!important;font-weight:{{ $s<=round($avg)?'900':'400' }}!important;"></i>
                            @endfor
                        </div>
                        <span class="v3-card-rating-num">{{ $avg }}</span>
                        <span class="v3-card-rating-cnt">({{ $reviews->count() }})</span>
                        @else
                        <span class="v3-card-rating-cnt" style="font-size:.72rem;">{{ $isAr ? 'جديد' : 'New' }}</span>
                        @endif
                    </div>
                    <span class="v3-card-book-btn">{{ $isAr ? 'احجز' : 'Book' }}</span>
                </div>
            </div>
        </a>
        @empty
        <div class="v3-empty">
            <div class="v3-empty-icon">🔍</div>
            <p>{{ $isAr ? 'لا توجد فروع حالياً' : 'No branches available yet' }}</p>
        </div>
        @endforelse
    </div>

    <div class="v3-load-more-wrap" id="v3-load-more-wrap" {{ $branches->hasMorePages() ? '' : 'style=display:none' }}>
        <button class="v3-load-more-btn" id="v3-load-more" onclick="v3LoadMore()">
            {{ $isAr ? 'تحميل المزيد' : 'Load more' }}
        </button>
    </div>
</div>
</section>

{{-- ══════════ POPULAR (horizontal scroll) ══════════ --}}
<section id="v3-popular">
<div class="v3-container">
    <div class="v3-section-head">
        <div>
            <div class="v3-section-label">{{ $isAr ? 'الأعلى تقييماً' : 'Top Rated' }}</div>
            <div class="v3-section-title">{{ $isAr ? 'الفروع المميزة' : 'Featured Branches' }}</div>
        </div>
    </div>
    <div class="v3-popular-row">
        @foreach($branches->take(6) as $i => $branch)
        @php
            $bName = $isAr ? ($branch->name_ar ?? $branch->name_en) : ($branch->name_en ?? $branch->name_ar);
            $cName = $isAr ? ($branch->company->name_ar ?? $branch->company->name_en) : ($branch->company->name_en ?? $branch->company->name_ar);
            $img   = $branch->images->first();
            $avg   = $branch->reviews->count() ? round($branch->reviews->avg('rating'),1) : null;
        @endphp
        <a href="{{ route('front.branch', $branch) }}" class="v3-pop-card">
            <div class="v3-pop-img">
                @if($img)
                <img src="{{ asset('storage/'.$img->path) }}" alt="{{ $bName }}" loading="lazy">
                @elseif($branch->company->logo)
                <img src="{{ asset('storage/'.$branch->company->logo) }}" alt="" loading="lazy">
                @else
                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--bg3),var(--bg4));display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--text-3);">✂</div>
                @endif
                <div class="v3-pop-rank">{{ $i+1 }}</div>
            </div>
            <div class="v3-pop-body">
                <div class="v3-pop-name">{{ $bName }}</div>
                <div class="v3-pop-company">{{ $cName }}</div>
                <div class="v3-pop-footer">
                    @if($avg)
                    <div class="v3-pop-rating">
                        <i class="fas fa-star" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                        {{ $avg }}
                        <span style="font-size:.68rem;color:var(--text-3);font-weight:400;">({{ $branch->reviews->count() }})</span>
                    </div>
                    @endif
                    <div class="v3-pop-svcs">
                        {{ $branch->services->count() }} {{ $isAr ? 'خدمة' : 'services' }}
                    </div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
</section>

{{-- ══════════ HOW IT WORKS ══════════ --}}
<section id="v3-how">
<div class="v3-container">
    <div style="text-align:center;margin-bottom:48px;">
        <div class="v3-section-label" style="justify-content:center;display:flex;">{{ $isAr ? 'كيف يعمل' : 'How it works' }}</div>
        <div class="v3-section-title">{{ $isAr ? 'احجز في ٣ خطوات بسيطة' : '3 simple steps to book' }}</div>
    </div>
    <div class="v3-steps">
        <div class="v3-step">
            <div class="v3-step-num">1</div>
            <i class="fas fa-search v3-step-icon" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            <div class="v3-step-title">{{ $isAr ? 'ابحث وتصفّح' : 'Search & Browse' }}</div>
            <div class="v3-step-desc">{{ $isAr ? 'ابحث عن الخدمة التي تريدها وتصفح أفضل الأماكن القريبة منك' : 'Find the service you need and browse top venues near you.' }}</div>
        </div>
        <div class="v3-step">
            <div class="v3-step-num">2</div>
            <i class="fas fa-calendar-alt v3-step-icon" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            <div class="v3-step-title">{{ $isAr ? 'اختر الوقت' : 'Pick a Time' }}</div>
            <div class="v3-step-desc">{{ $isAr ? 'اختر التاريخ والوقت المناسب لك من التقويم المتاح' : 'Choose your preferred date and time from the available calendar.' }}</div>
        </div>
        <div class="v3-step">
            <div class="v3-step-num">3</div>
            <i class="fas fa-check-circle v3-step-icon" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            <div class="v3-step-title">{{ $isAr ? 'استمتع بتجربتك' : 'Enjoy Your Visit' }}</div>
            <div class="v3-step-desc">{{ $isAr ? 'تلقّ تأكيداً فورياً واستمتع بتجربتك المميزة' : 'Get instant confirmation and enjoy your premium experience.' }}</div>
        </div>
    </div>
</div>
</section>

{{-- ══════════ TESTIMONIALS ══════════ --}}
<section id="v3-reviews">
<div class="v3-container">
    <div style="text-align:center;margin-bottom:40px;">
        <div class="v3-section-label" style="justify-content:center;display:flex;">{{ $isAr ? 'آراء عملائنا' : 'Customer Reviews' }}</div>
        <div class="v3-section-title">{{ $isAr ? 'ماذا يقول عملاؤنا' : 'What our customers say' }}</div>
    </div>
    <div class="v3-rev-grid">
        @php
        $staticReviews = [
            ['name'=>$isAr?'سارة م.':'Sarah M.','branch'=>$isAr?'صالون نور':'Nour Salon','text'=>$isAr?'تجربة رائعة! الحجز كان سهلاً جداً والخدمة كانت احترافية بشكل مذهل. سأعود بالتأكيد.':'Amazing experience! Booking was super easy and the service was incredibly professional. Definitely coming back.','rating'=>5,'date'=>$isAr?'منذ يومين':'2 days ago','init'=>'S'],
            ['name'=>$isAr?'أحمد ك.':'Ahmed K.','branch'=>$isAr?'سبا سيرين':'Serene Spa','text'=>$isAr?'أفضل تطبيق للحجز في المدينة. وجدت مكاني المفضل من أول بحث وكان كل شيء مثالياً.':'Best booking app in the city. Found my favorite spot on the first search and everything was perfect.','rating'=>5,'date'=>$isAr?'منذ أسبوع':'1 week ago','init'=>'A'],
            ['name'=>$isAr?'لينا ح.':'Lina H.','branch'=>$isAr?'عيادة جلدية':'Skin Clinic','text'=>$isAr?'الموقع سهل الاستخدام والخيارات كثيرة ومتنوعة. أنصح به بشدة لكل من يبحث عن حجز موثوق.':'Easy to use and lots of great options. Highly recommend for anyone looking for reliable booking.','rating'=>4,'date'=>$isAr?'منذ أسبوعين':'2 weeks ago','init'=>'L'],
        ];
        @endphp
        @foreach($staticReviews as $rev)
        <div class="v3-rev-card">
            <div class="v3-rev-top">
                <div class="v3-rev-avatar">{{ $rev['init'] }}</div>
                <div class="v3-rev-info">
                    <div class="v3-rev-name">{{ $rev['name'] }}</div>
                    <div class="v3-rev-branch">{{ $rev['branch'] }}</div>
                </div>
            </div>
            <div class="v3-rev-stars">
                @for($s=1;$s<=5;$s++)
                <i class="{{ $s<=$rev['rating'] ? 'fas' : 'far' }} fa-star"
                   style="font-family:'Font Awesome 5 Free'!important;font-weight:{{ $s<=$rev['rating']?'900':'400' }}!important;"></i>
                @endfor
            </div>
            <div class="v3-rev-text">{{ $rev['text'] }}</div>
            <div class="v3-rev-date">{{ $rev['date'] }}</div>
        </div>
        @endforeach
    </div>
</div>
</section>

{{-- ══════════ APP BANNER ══════════ --}}
<section id="v3-app">
<div class="v3-container">
    <div class="v3-app-inner">
        <div>
            <div class="v3-app-badge">
                <i class="fas fa-mobile-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.7rem;"></i>
                {{ $isAr ? 'تطبيق بوكسي' : 'Booksy App' }}
            </div>
            <div class="v3-app-title">{{ $isAr ? 'احجز من هاتفك\nبنقرة واحدة' : "Book from your phone\nin one tap" }}</div>
            <p class="v3-app-sub">{{ $isAr ? 'حمّل تطبيق بوكسي الآن واحجز مواعيدك في أي وقت ومن أي مكان. متاح على iOS وAndroid.' : 'Download the Booksy app now and book your appointments anytime, anywhere. Available on iOS and Android.' }}</p>
            <div class="v3-app-btns">
                <a href="#" class="v3-app-btn">
                    <i class="fab fa-apple" style="font-family:'Font Awesome 5 Brands'!important;font-weight:400!important;"></i>
                    <div>
                        <small>{{ $isAr ? 'حمّل من' : 'Download on the' }}</small>
                        App Store
                    </div>
                </a>
                <a href="#" class="v3-app-btn">
                    <i class="fab fa-google-play" style="font-family:'Font Awesome 5 Brands'!important;font-weight:400!important;"></i>
                    <div>
                        <small>{{ $isAr ? 'احصل عليه من' : 'Get it on' }}</small>
                        Google Play
                    </div>
                </a>
            </div>
        </div>
        <div class="v3-app-qr">📱</div>
    </div>
</div>
</section>

{{-- ══════════ FOOTER ══════════ --}}
<footer id="v3-footer">
<div class="v3-container">
    <div class="v3-footer-grid">
        <div class="v3-footer-brand">
            <div class="v3-logo">
                <div class="v3-logo-dot"></div>
                book<span>sy</span>
            </div>
            <p class="v3-footer-desc">{{ $isAr ? 'منصة الحجز الأولى — اكتشف أفضل الصالونات والعيادات وامكانياتها بكل سهولة ويسر.' : 'The #1 booking platform — discover top salons, clinics and spas and book with ease.' }}</p>
            <div class="v3-footer-social">
                <a href="#"><i class="fab fa-instagram" style="font-family:'Font Awesome 5 Brands'!important;font-weight:400!important;"></i></a>
                <a href="#"><i class="fab fa-twitter" style="font-family:'Font Awesome 5 Brands'!important;font-weight:400!important;"></i></a>
                <a href="#"><i class="fab fa-facebook-f" style="font-family:'Font Awesome 5 Brands'!important;font-weight:400!important;"></i></a>
                <a href="#"><i class="fab fa-tiktok" style="font-family:'Font Awesome 5 Brands'!important;font-weight:400!important;"></i></a>
            </div>
        </div>
        <div class="v3-footer-col">
            <div class="v3-footer-col-title">{{ $isAr ? 'استكشف' : 'Explore' }}</div>
            <ul>
                <li><a href="#">{{ $isAr ? 'الصالونات' : 'Salons' }}</a></li>
                <li><a href="#">{{ $isAr ? 'العيادات' : 'Clinics' }}</a></li>
                <li><a href="#">{{ $isAr ? 'مراكز السبا' : 'Spas' }}</a></li>
                <li><a href="#">{{ $isAr ? 'صالونات رجالية' : 'Barbers' }}</a></li>
            </ul>
        </div>
        <div class="v3-footer-col">
            <div class="v3-footer-col-title">{{ $isAr ? 'بوكسي' : 'Booksy' }}</div>
            <ul>
                <li><a href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About Us' }}</a></li>
                <li><a href="#">{{ $isAr ? 'سجّل نشاطك' : 'List Your Business' }}</a></li>
                <li><a href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل معنا' : 'Contact' }}</a></li>
                <li><a href="#">{{ $isAr ? 'المدونة' : 'Blog' }}</a></li>
            </ul>
        </div>
        <div class="v3-footer-col">
            <div class="v3-footer-col-title">{{ $isAr ? 'الدعم' : 'Support' }}</div>
            <ul>
                <li><a href="#">{{ $isAr ? 'مركز المساعدة' : 'Help Center' }}</a></li>
                <li><a href="#">{{ $isAr ? 'الخصوصية' : 'Privacy Policy' }}</a></li>
                <li><a href="#">{{ $isAr ? 'الشروط' : 'Terms of Service' }}</a></li>
            </ul>
        </div>
    </div>
    <div class="v3-footer-bottom">
        <p>© {{ date('Y') }} <span>booksy</span>. {{ $isAr ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}</p>
        <p>{{ $isAr ? 'صُنع بـ' : 'Made with' }} <span>♥</span> {{ $isAr ? 'لمجتمعنا' : 'for our community' }}</p>
    </div>
</div>
</footer>

{{-- ══════════ FAB MAP ══════════ --}}
<button id="v3-map-fab" onclick="v3OpenMap()" title="{{ $isAr ? 'خريطة الفروع' : 'Branches Map' }}">
    <i class="fas fa-map-marked-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
    <span class="v3-fab-tip">{{ $isAr ? 'خريطة الفروع' : 'Branches Map' }}</span>
</button>

{{-- ══════════ MAP MODAL ══════════ --}}
<div id="v3-map-modal">
    <div class="v3-map-wrap">
        <div class="v3-map-head">
            <h5>
                <i class="fas fa-map-marked-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
                {{ $isAr ? 'خريطة الفروع' : 'Branches Map' }}
            </h5>
            <button class="v3-map-close-btn" onclick="v3CloseMap()">
                <i class="fas fa-times" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
            </button>
        </div>
        <div id="v3-leaflet-map" style="height:500px;width:100%;display:block;"></div>
    </div>
</div>

{{-- ══════════ COMPARE STRIP ══════════ --}}
<div id="v3-compare">
    <span>{{ $isAr ? 'قارن التصاميم:' : 'Compare designs:' }}</span>
    <a href="{{ route('front.index') }}">V1 {{ $isAr ? 'الأصلي' : 'Original' }}</a>
    <a href="{{ route('front.index2') }}">V2 {{ $isAr ? 'الداكن' : 'Dark' }}</a>
    <a href="{{ route('front.index3') }}" style="background:var(--gold);color:#0a0a0a;border-color:var(--gold);">V3 ← {{ $isAr ? 'أنت هنا' : 'You are here' }}</a>
</div>

{{-- SCRIPTS --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const V3_IS_AR   = {{ $isAr ? 'true' : 'false' }};
const V3_API     = '{{ route('front.branches.json') }}';
const V3_MAP_API = '{{ route('front.map.branches') }}';

let v3Cat      = '';
let v3Page     = 1;
let v3Loading  = false;
let v3HasMore  = {{ $branches->hasMorePages() ? 'true' : 'false' }};
let v3Map      = null;
let v3MapReady = false;

/* ── THEME TOGGLE ── */
const html  = document.documentElement;
const knob  = document.getElementById('v3-theme-knob');
const icon  = document.getElementById('v3-theme-icon');
let isDark  = true;

function applyTheme(dark){
    isDark = dark;
    html.setAttribute('data-theme', dark ? 'dark' : 'light');
    icon.className = dark
        ? 'fas fa-moon'
        : 'fas fa-sun';
    icon.style.cssText = "font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.55rem;";
    knob.style.transform = dark ? '' : 'translateX({{ $isAr ? "-" : "" }}18px)';
    try{ localStorage.setItem('v3theme', dark?'dark':'light'); }catch(e){}
}

document.getElementById('v3-theme-toggle').addEventListener('click', () => applyTheme(!isDark));

// restore saved preference
try{
    const saved = localStorage.getItem('v3theme');
    if(saved === 'light') applyTheme(false);
}catch(e){}

/* ── SEARCH ── */
function handleSearch(){
    const q = document.getElementById('v3-search-input').value.trim();
    if(q){ window.location.href = V3_API.replace('/api/branches-json','') + '?search=' + encodeURIComponent(q); }
}
document.getElementById('v3-search-input').addEventListener('keydown', e => { if(e.key==='Enter') handleSearch(); });

/* ── CATEGORY FILTER ── */
function filterByCategory(slug){
    // sync pills in cat strip
    document.querySelectorAll('#v3-cat-strip .v3-cat-pill').forEach(p => {
        p.classList.toggle('active', p.dataset.slug === slug);
    });
    // sync filter row buttons
    document.querySelectorAll('#v3-filter-row .v3-filter-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.slug === slug);
    });
    v3Cat  = slug;
    v3Page = 1;
    v3HasMore = true;
    v3LoadCards(true);
    document.getElementById('v3-branches').scrollIntoView({behavior:'smooth', block:'start'});
}

function v3FilterClick(btn, slug){
    filterByCategory(slug);
}

/* ── LOAD CARDS ── */
function v3LoadCards(reset){
    if(v3Loading) return;
    v3Loading = true;
    const grid = document.getElementById('v3-cards-grid');

    if(reset){
        grid.innerHTML = skeletons(6);
    }

    const params = new URLSearchParams();
    if(v3Cat) params.set('category', v3Cat);
    params.set('page', v3Page);

    fetch(V3_API + '?' + params)
        .then(r => r.json())
        .then(data => {
            v3Loading = false;
            v3HasMore = data.has_more;
            document.getElementById('v3-branch-count').textContent = data.total;

            const wrap = document.getElementById('v3-load-more-wrap');
            wrap.style.display = v3HasMore ? '' : 'none';

            if(reset) grid.innerHTML = '';

            if(!data.items.length && reset){
                grid.innerHTML = `<div class="v3-empty"><div class="v3-empty-icon">🔍</div><p>${V3_IS_AR?'لا توجد نتائج':'No results found'}</p></div>`;
                return;
            }

            data.items.forEach(b => {
                grid.insertAdjacentHTML('beforeend', buildCard(b));
            });
        })
        .catch(() => { v3Loading = false; });
}

function v3LoadMore(){
    if(!v3HasMore || v3Loading) return;
    v3Page++;
    v3LoadCards(false);
}

function buildCard(b){
    const ratingHtml = b.avg_rating
        ? `<div class="v3-card-rating">
            <div class="v3-card-rating-stars">${'<i class="fas fa-star" style="font-family:\'Font Awesome 5 Free\'!important;font-weight:900!important;font-size:.6rem;color:var(--star);"></i>'.repeat(Math.round(b.avg_rating))}${'<i class="far fa-star" style="font-family:\'Font Awesome 5 Free\'!important;font-weight:400!important;font-size:.6rem;color:var(--star);"></i>'.repeat(5-Math.round(b.avg_rating))}</div>
            <span class="v3-card-rating-num">${b.avg_rating}</span>
            <span class="v3-card-rating-cnt">(${b.review_count})</span>
           </div>`
        : `<span class="v3-card-rating-cnt">${V3_IS_AR?'جديد':'New'}</span>`;

    const imgHtml = b.image
        ? `<img src="${b.image}" alt="${esc(b.name)}" loading="lazy" style="width:100%;height:100%;object-fit:cover;transition:transform .4s;">`
        : `<div class="v3-card-img-ph">✂</div>`;

    const catHtml = b.category ? `<div class="v3-card-badge">${esc(b.category)}</div>` : '';
    const logoHtml = b.company_logo ? `<div class="v3-card-logo"><img src="${b.company_logo}" alt=""></div>` : '';
    const addrHtml = b.address ? `<div style="font-size:.72rem;color:var(--text-3);margin-bottom:8px;display:flex;align-items:center;gap:4px;"><i class="fas fa-map-marker-alt" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.6rem;"></i>${esc(b.address.substring(0,45))}</div>` : '';

    return `<a href="${b.url}" class="v3-card">
        <div class="v3-card-img">${imgHtml}${catHtml}<div class="v3-card-fav"><i class="far fa-heart" style="font-family:'Font Awesome 5 Free'!important;font-weight:400!important;"></i></div>${logoHtml}</div>
        <div class="v3-card-body">
            <div class="v3-card-meta">
                ${b.category?`<span class="v3-card-cat">${esc(b.category)}</span><div class="v3-card-dot"></div>`:''}
                <span class="v3-card-status">${V3_IS_AR?'متاح':'Open'}</span>
            </div>
            <div class="v3-card-name">${esc(b.name)}</div>
            <div class="v3-card-company"><i class="fas fa-building" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;font-size:.65rem;"></i>${esc(b.company_name)}</div>
            ${addrHtml}
            <div class="v3-card-footer">
                ${ratingHtml}
                <span class="v3-card-book-btn">${V3_IS_AR?'احجز':'Book'}</span>
            </div>
        </div>
    </a>`;
}

function skeletons(n){
    return Array.from({length:n},(_,i)=>`<div class="v3-skel" style="height:320px;"></div>`).join('');
}
function esc(s){ const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }

/* ── MAP ── */
function v3OpenMap(){
    document.getElementById('v3-map-modal').classList.add('open');
    document.body.style.overflow='hidden';
    if(!v3MapReady){
        setTimeout(v3InitMap, 300);
    } else {
        setTimeout(()=>{ v3Map && v3Map.invalidateSize(); }, 300);
    }
}
function v3CloseMap(){
    document.getElementById('v3-map-modal').classList.remove('open');
    document.body.style.overflow='';
}
document.getElementById('v3-map-modal').addEventListener('click', e => { if(e.target === e.currentTarget) v3CloseMap(); });
document.addEventListener('keydown', e => e.key === 'Escape' && v3CloseMap());

function v3InitMap(){
    v3MapReady = true;
    const el = document.getElementById('v3-leaflet-map');
    console.log('[V3 Map] size:', el.offsetWidth, 'x', el.offsetHeight);

    v3Map = L.map('v3-leaflet-map', { center:[33.51,36.29], zoom:12 });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{
        attribution:'&copy; CARTO', maxZoom:19
    }).addTo(v3Map);

    const goldIcon = L.divIcon({
        className:'',
        html:`<div style="width:34px;height:34px;border-radius:50% 50% 50% 0;background:#C9A227;border:2px solid #fff;transform:rotate(-45deg);box-shadow:0 3px 14px rgba(201,162,39,.65);display:flex;align-items:center;justify-content:center;">
                <span style="transform:rotate(45deg);font-size:.7rem;">✂</span>
              </div>`,
        iconSize:[34,34], iconAnchor:[17,34], popupAnchor:[0,-36],
    });

    fetch(V3_MAP_API)
        .then(r=>r.json())
        .then(list=>{
            if(!list.length){
                L.marker([33.51,36.29],{icon:goldIcon}).addTo(v3Map)
                 .bindPopup(`<p style="padding:10px;font-family:'Poppins',sans-serif;">${V3_IS_AR?'لا توجد فروع بإحداثيات':'No branches with coordinates yet'}</p>`);
                return;
            }
            const bounds=[];
            list.forEach(b=>{
                bounds.push([b.lat,b.lng]);
                const stars = b.avg_rating
                    ? `<div style="display:flex;gap:2px;margin-bottom:8px;">${'★'.repeat(Math.round(b.avg_rating))}${'☆'.repeat(5-Math.round(b.avg_rating))}<span style="font-size:.68rem;color:#888;margin-left:4px;">${b.avg_rating} (${b.review_count})</span></div>` : '';
                const imgH = b.image
                    ? `<img src="${b.image}" style="width:100%;height:110px;object-fit:cover;border-radius:0;">`
                    : `<div style="height:60px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏪</div>`;
                L.marker([b.lat,b.lng],{icon:goldIcon}).addTo(v3Map)
                 .bindPopup(`<div style="font-family:'Poppins',sans-serif;">
                    ${imgH}
                    <div style="padding:10px;">
                        ${b.category?`<div style="font-size:.6rem;font-weight:700;color:#C9A227;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">${b.category}</div>`:''}
                        <div style="font-size:.88rem;font-weight:700;color:#111;margin-bottom:2px;">${b.name}</div>
                        <div style="font-size:.7rem;color:#666;margin-bottom:6px;">🏢 ${b.company_name}</div>
                        ${b.address?`<div style="font-size:.7rem;color:#888;margin-bottom:8px;">📍 ${b.address}</div>`:''}
                        ${stars}
                        <a href="${b.url}" style="display:block;background:#C9A227;color:#0a0a0a;text-align:center;padding:8px;border-radius:8px;font-size:.78rem;font-weight:700;text-decoration:none;">${V3_IS_AR?'احجز الآن':'Book Now'}</a>
                    </div>
                 </div>`, {maxWidth:260,minWidth:220});
            });
            if(bounds.length>1) v3Map.fitBounds(bounds,{padding:[40,40]});
            else v3Map.setView(bounds[0],15);
        });
}
</script>
</body>
</html>
