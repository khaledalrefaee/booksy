<!DOCTYPE html>
@php
    $isAr    = app()->getLocale() === 'ar';
    $dir     = $isAr ? 'rtl' : 'ltr';
    $lang    = $isAr ? 'ar' : 'en';
    $brName  = $isAr ? ($branch->name_ar ?: $branch->name_en) : $branch->name_en;
    $coName  = $isAr ? ($company->name_ar ?? $company->name_en) : ($company->name_en ?? '');
    $catName = $isAr ? ($company->category->name_ar ?? '') : ($company->category->name_en ?? '');

    // Day labels
    $dayNames = $isAr
        ? ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']
        : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $todayDow = (int)\Carbon\Carbon::now()->dayOfWeek;
    $nowTime  = \Carbon\Carbon::now()->format('H:i');

    // Is open now?
    $isOpenNow = false;
    foreach($branch->workingHours as $wh) {
        if((int)$wh->day_of_week === $todayDow && $wh->is_open) {
            if($wh->open_time && $wh->close_time) {
                if($nowTime >= $wh->open_time && $nowTime <= $wh->close_time) { $isOpenNow = true; break; }
            } else { $isOpenNow = true; break; }
        }
    }
    // Group working hours by day
    $whByDay = $branch->workingHours->groupBy('day_of_week');

    // Category icon/gradient
    $catIcons = [
        'salon'=>'fas fa-cut','spa'=>'fas fa-spa','clinic'=>'fas fa-clinic-medical',
        'beauty'=>'fas fa-magic','nail'=>'fas fa-hand-sparkles','hair'=>'fas fa-cut',
        'skin'=>'fas fa-leaf','dental'=>'fas fa-tooth','gym'=>'fas fa-dumbbell',
        'massage'=>'fas fa-hot-tub','barber'=>'fas fa-user-tie','lash'=>'fas fa-eye',
        'brow'=>'fas fa-smile','tattoo'=>'fas fa-pen-nib','wedding'=>'fas fa-ring',
        'laser'=>'fas fa-bolt',
    ];
    $catGradients = [
        'salon'=>'linear-gradient(135deg,#7f1d52,#4a0f30)',
        'hair'=>'linear-gradient(135deg,#7f1d52,#4a0f30)',
        'barber'=>'linear-gradient(135deg,#1c1917,#0c0a09)',
        'spa'=>'linear-gradient(135deg,#064e3b,#022c22)',
        'massage'=>'linear-gradient(135deg,#064e3b,#022c22)',
        'clinic'=>'linear-gradient(135deg,#1e3a5f,#0c1f36)',
        'dental'=>'linear-gradient(135deg,#155e75,#083344)',
        'laser'=>'linear-gradient(135deg,#1e3a5f,#172554)',
        'beauty'=>'linear-gradient(135deg,#5b21b6,#2e1065)',
        'makeup'=>'linear-gradient(135deg,#5b21b6,#2e1065)',
        'lash'=>'linear-gradient(135deg,#831843,#4a044e)',
        'brow'=>'linear-gradient(135deg,#831843,#4a044e)',
        'nail'=>'linear-gradient(135deg,#9d174d,#500724)',
        'gym'=>'linear-gradient(135deg,#92400e,#451a03)',
        'tattoo'=>'linear-gradient(135deg,#1f2937,#030712)',
        'wedding'=>'linear-gradient(135deg,#7c2d12,#431407)',
    ];
    $sl = strtolower($company->category->slug ?? '');
    $catIcon = 'fas fa-store';
    $catGradient = 'linear-gradient(135deg,#713f12,#3f1f07)';
    foreach($catIcons as $k=>$v){ if(str_contains($sl,$k)){ $catIcon=$v; break; } }
    foreach($catGradients as $k=>$v){ if(str_contains($sl,$k)){ $catGradient=$v; break; } }

    // Build employee data for JS
    $empData = $branch->employees->map(function($e) use ($isAr) {
        return [
            'id'   => $e->id,
            'name' => $isAr ? ($e->name_ar ?: $e->name_en) : $e->name_en,
            'cats' => $e->serviceCategories->pluck('id')->toArray(),
        ];
    })->toArray();

    // Build service data for JS
    $svcData = $branch->services->map(function($s) use ($isAr) {
        return [
            'id'       => $s->id,
            'name'     => $isAr ? ($s->name_ar ?: $s->name_en) : $s->name_en,
            'price'    => $s->price,
            'duration' => $s->duration_minutes,
            'catId'    => $s->service_category_id,
        ];
    })->toArray();
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<title>{{ $brName }} — {{ $coName }} | Booksy</title>
<meta name="description" content="{{ $isAr ? 'احجز موعدك في '.$brName.' على بوكسي.' : 'Book an appointment at '.$brName.' on Booksy.' }}">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap' . ($isAr ? '.rtl' : '') . '.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/owl.carousel/assets/owl.theme.default.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/magnific-popup/magnific-popup.min.css') }}">
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

/* ── Cover ── */
#bk-cover{height:500px;position:relative;background:#111;overflow:hidden;margin-top:68px;}
#bk-cover .cover-img{width:100%;height:100%;object-fit:cover;}
#bk-cover::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.2) 0%,rgba(10,10,10,.85) 100%);}
.bk-cover-no-img{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;color:rgba(201,162,39,.15);background:{{ $catGradient }};}

/* Cover slideshow */
.bk-cover-slide{position:absolute;inset:0;opacity:0;transition:opacity 1s ease;}
.bk-cover-slide.active{opacity:1;}
.bk-cover-slide img{width:100%;height:100%;object-fit:cover;}
.bk-cover-dots{position:absolute;bottom:24px;left:50%;transform:translateX(-50%);z-index:5;display:flex;gap:7px;}
.bk-cover-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.3);border:none;cursor:pointer;transition:all .25s;}
.bk-cover-dot.active{background:#C9A227;width:22px;border-radius:4px;}

/* ── Profile bar ── */
.bk-profile-bar{
    background:#111;border-bottom:1px solid rgba(201,162,39,.1);
    padding:22px 0;position:relative;z-index:10;
}
.bk-pb-logo{
    width:80px;height:80px;border-radius:16px;object-fit:cover;
    border:2px solid rgba(201,162,39,.4);background:#1a1a1a;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;overflow:hidden;
}
.bk-pb-logo img{width:100%;height:100%;object-fit:cover;}
.bk-pb-name{font-size:1.55rem;font-weight:800;color:#fff;font-family:'Poppins',sans-serif;line-height:1.2;}
.bk-pb-sub{font-size:.84rem;color:rgba(255,255,255,.45);font-family:'Poppins',sans-serif;}
.bk-pb-badge{
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 13px;border-radius:20px;font-size:.7rem;font-weight:700;
    font-family:'Poppins',sans-serif;
}
.bk-pb-badge.cat{background:rgba(201,162,39,.12);border:1px solid rgba(201,162,39,.3);color:#C9A227;}
.bk-pb-badge.open{background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.4);color:#34d399;}
.bk-pb-badge.closed{background:rgba(185,28,28,.15);border:1px solid rgba(185,28,28,.4);color:#f87171;}
.bk-pb-book-btn{
    background:#C9A227;color:#0a0a0a;border:none;border-radius:12px;
    padding:12px 28px;font-weight:800;font-size:.92rem;font-family:'Poppins',sans-serif;
    cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .22s;
    text-decoration:none;white-space:nowrap;
}
.bk-pb-book-btn:hover{background:#e8c84a;box-shadow:0 6px 24px rgba(201,162,39,.4);color:#0a0a0a;text-decoration:none;}

/* ── Sticky tabs ── */
#bk-tabs-bar{
    background:#0d0d0d;border-bottom:1px solid rgba(201,162,39,.12);
    position:sticky;top:68px;z-index:40;overflow-x:auto;
    scrollbar-width:none;
}
#bk-tabs-bar::-webkit-scrollbar{display:none;}
.bk-tabs-inner{display:flex;gap:0;width:max-content;min-width:100%;}
.bk-tab-btn{
    padding:16px 24px;font-size:.84rem;font-weight:600;
    color:rgba(255,255,255,.5);font-family:'Poppins',sans-serif;
    border:none;background:transparent;cursor:pointer;
    border-bottom:2.5px solid transparent;white-space:nowrap;
    transition:all .22s;display:flex;align-items:center;gap:7px;
}
.bk-tab-btn:hover{color:rgba(255,255,255,.8);}
.bk-tab-btn.active{color:#C9A227;border-bottom-color:#C9A227;}

/* ── Main layout ── */
.bk-main-grid{
    display:grid;
    grid-template-columns:1fr 380px;
    gap:28px;
    padding:32px 0 80px;
    align-items:start;
}
@media(max-width:991px){.bk-main-grid{grid-template-columns:1fr;}}

/* ── Content sections ── */
.bk-section-head{
    display:flex;align-items:center;gap:12px;margin-bottom:22px;
}
.bk-section-head h3{
    font-size:1.3rem;font-weight:800;color:#fff;font-family:'Poppins',sans-serif;margin:0;
}
.bk-section-head::after{
    content:'';flex:1;height:1px;
    background:linear-gradient({{ $isAr?'left':'right' }},rgba(201,162,39,.25),transparent);
}
.bk-cat-label{
    font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;
    color:#C9A227;font-family:'Poppins',sans-serif;margin-bottom:14px;
    display:flex;align-items:center;gap:7px;
}
.bk-cat-label::before{content:'';width:18px;height:2px;background:#C9A227;border-radius:1px;}

/* ── Service card ── */
.bk-svc-card{
    background:#141414;border:1px solid rgba(255,255,255,.06);border-radius:14px;
    padding:16px;display:flex;gap:14px;align-items:flex-start;
    transition:border-color .28s,box-shadow .28s;margin-bottom:12px;
    position:relative;
}
.bk-svc-card:hover{border-color:rgba(201,162,39,.3);box-shadow:0 8px 28px rgba(0,0,0,.35);}
.bk-svc-card .icon{
    width:50px;height:50px;border-radius:12px;flex-shrink:0;
    background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.2);
    display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#C9A227;
}
.bk-svc-card .info{flex:1;min-width:0;}
.bk-svc-card .info h5{font-size:.93rem;font-weight:700;color:#fff;margin:0 0 4px;font-family:'Poppins',sans-serif;}
.bk-svc-card .info .desc{font-size:.78rem;color:rgba(255,255,255,.4);margin:0 0 8px;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.bk-svc-card .meta{display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.bk-svc-card .price{font-size:.95rem;font-weight:800;color:#C9A227;font-family:'Poppins',sans-serif;}
.bk-svc-card .dur{font-size:.74rem;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:4px;}
.bk-svc-card .dur i{font-size:.65rem;}
.bk-svc-card .add-btn{
    background:transparent;border:1.5px solid rgba(201,162,39,.4);color:#C9A227;
    border-radius:8px;padding:7px 16px;font-size:.78rem;font-weight:700;
    font-family:'Poppins',sans-serif;cursor:pointer;transition:all .22s;white-space:nowrap;flex-shrink:0;
}
.bk-svc-card .add-btn:hover,.bk-svc-card .add-btn.added{
    background:#C9A227;color:#0a0a0a;border-color:#C9A227;
}
.bk-popular-badge{
    position:absolute;top:-1px;{{ $isAr?'left':'right' }}:12px;
    background:#C9A227;color:#0a0a0a;font-size:.6rem;font-weight:800;
    padding:3px 10px;border-radius:0 0 8px 8px;font-family:'Poppins',sans-serif;
}

/* ── Gallery ── */
.bk-gallery-grid{
    display:grid;grid-template-columns:repeat(3,1fr);gap:10px;
}
@media(max-width:576px){.bk-gallery-grid{grid-template-columns:repeat(2,1fr);}}
.bk-gallery-item{
    border-radius:12px;overflow:hidden;aspect-ratio:1;
    position:relative;cursor:pointer;background:#1a1a1a;
}
.bk-gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .4s,filter .4s;}
.bk-gallery-item:hover img{transform:scale(1.06);filter:brightness(1.1);}
.bk-gallery-item::after{
    content:'\f00e';font-family:'Font Awesome 5 Free';font-weight:900;
    position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    font-size:1.6rem;color:#fff;background:rgba(201,162,39,.35);
    opacity:0;transition:opacity .3s;
}
.bk-gallery-item:hover::after{opacity:1;}

/* ── Team cards ── */
.bk-emp-card{
    background:#141414;border:1px solid rgba(255,255,255,.06);border-radius:16px;
    padding:22px;text-align:center;transition:border-color .28s,transform .28s;
}
.bk-emp-card:hover{border-color:rgba(201,162,39,.3);transform:translateY(-5px);}
.bk-emp-card .photo{
    width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto 12px;
    border:2.5px solid rgba(201,162,39,.4);display:block;background:#1a1a1a;
    display:flex;align-items:center;justify-content:center;
}
.bk-emp-card .photo img{width:80px;height:80px;border-radius:50%;object-fit:cover;}
.bk-emp-card .photo-icon{width:80px;height:80px;border-radius:50%;margin:0 auto 12px;background:rgba(201,162,39,.1);border:2px solid rgba(201,162,39,.25);display:flex;align-items:center;justify-content:center;font-size:2rem;color:rgba(201,162,39,.5);}
.bk-emp-card h5{font-size:.92rem;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;margin:0 0 3px;}
.bk-emp-card .role{font-size:.74rem;color:rgba(255,255,255,.4);margin-bottom:10px;}
.bk-emp-card .spec-chips{display:flex;flex-wrap:wrap;gap:4px;justify-content:center;margin-bottom:14px;}
.bk-emp-card .spec-chip{background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.18);border-radius:20px;padding:3px 9px;font-size:.62rem;font-weight:600;color:rgba(201,162,39,.8);font-family:'Poppins',sans-serif;}
.bk-emp-book-btn{
    background:transparent;border:1.5px solid rgba(201,162,39,.35);color:#C9A227;
    border-radius:8px;padding:7px 18px;font-size:.78rem;font-weight:700;
    font-family:'Poppins',sans-serif;cursor:pointer;transition:all .22s;width:100%;
}
.bk-emp-book-btn:hover{background:#C9A227;color:#0a0a0a;border-color:#C9A227;}

/* ── Reviews ── */
.bk-rating-overview{
    background:#141414;border:1px solid rgba(201,162,39,.15);border-radius:16px;
    padding:24px;display:flex;gap:24px;align-items:center;flex-wrap:wrap;margin-bottom:24px;
}
.bk-rating-big{font-size:3.5rem;font-weight:900;color:#C9A227;font-family:'Poppins',sans-serif;line-height:1;}
.bk-rev-card{
    background:#141414;border:1px solid rgba(255,255,255,.06);border-radius:14px;
    padding:18px;margin-bottom:12px;
}
.bk-rev-card .auth-row{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.bk-rev-card .av{width:38px;height:38px;border-radius:50%;background:rgba(201,162,39,.15);border:1.5px solid rgba(201,162,39,.25);display:flex;align-items:center;justify-content:center;color:#C9A227;font-size:1rem;flex-shrink:0;}
.bk-rev-card .name{font-size:.88rem;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;}
.bk-rev-card .date{font-size:.7rem;color:rgba(255,255,255,.3);}
.bk-rev-card .stars{display:flex;gap:2px;margin-bottom:8px;}
.bk-rev-card .stars i{font-size:.7rem;color:#C9A227;}
.bk-rev-card p{font-size:.84rem;color:rgba(255,255,255,.6);margin:0;}

/* ── Booking cart (right sidebar) ── */
.bk-cart-sticky{
    position:sticky;top:calc(68px + 60px);
    background:#111;border:1px solid rgba(201,162,39,.2);border-radius:18px;
    overflow:hidden;
}
.bk-cart-head{
    background:linear-gradient(135deg,rgba(201,162,39,.15),rgba(201,162,39,.05));
    border-bottom:1px solid rgba(201,162,39,.15);padding:16px 20px;
    display:flex;align-items:center;gap:10px;
}
.bk-cart-head h4{font-size:1rem;font-weight:800;color:#fff;font-family:'Poppins',sans-serif;margin:0;}
.bk-cart-badge{background:#C9A227;color:#0a0a0a;border-radius:50%;width:22px;height:22px;font-size:.72rem;font-weight:900;display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;}
.bk-cart-body{padding:16px 20px;}
.bk-cart-empty{text-align:center;padding:28px 10px;color:rgba(255,255,255,.3);}
.bk-cart-empty i{font-size:2.2rem;color:rgba(201,162,39,.12);display:block;margin-bottom:10px;}
.bk-cart-empty p{font-size:.8rem;margin:0;}
.bk-cart-item{
    background:#181818;border:1px solid rgba(255,255,255,.06);border-radius:10px;
    padding:12px;margin-bottom:10px;position:relative;
}
.bk-cart-item .svc-name{font-size:.86rem;font-weight:700;color:#fff;font-family:'Poppins',sans-serif;margin-bottom:6px;}
.bk-cart-item .svc-meta{display:flex;gap:10px;font-size:.75rem;color:rgba(255,255,255,.4);}
.bk-cart-item .svc-price{color:#C9A227;font-weight:700;}
.bk-cart-item .remove-btn{
    position:absolute;top:8px;{{ $isAr?'left':'right' }}:8px;
    background:transparent;border:none;color:rgba(255,255,255,.3);cursor:pointer;font-size:.85rem;
    transition:color .2s;padding:3px;
}
.bk-cart-item .remove-btn:hover{color:#f87171;}
.bk-cart-item .emp-select{
    width:100%;margin-top:8px;padding:7px 10px;
    background:#141414;border:1px solid rgba(255,255,255,.1);border-radius:7px;
    color:rgba(255,255,255,.75);font-size:.77rem;font-family:'Poppins',sans-serif;
    cursor:pointer;outline:none;transition:border-color .2s;
}
.bk-cart-item .emp-select:focus{border-color:#C9A227;}
.bk-cart-total{
    border-top:1px solid rgba(201,162,39,.15);padding-top:14px;margin-top:4px;
}
.bk-cart-total .row-t{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-family:'Poppins',sans-serif;}
.bk-cart-total .lbl{font-size:.8rem;color:rgba(255,255,255,.45);}
.bk-cart-total .val{font-size:.9rem;font-weight:700;color:#fff;}
.bk-cart-total .val.gold{color:#C9A227;}
.bk-confirm-btn{
    width:100%;background:#C9A227;color:#0a0a0a;border:none;border-radius:12px;
    padding:14px;font-weight:800;font-size:.95rem;font-family:'Poppins',sans-serif;
    cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
    transition:all .22s;margin-top:14px;text-decoration:none;
}
.bk-confirm-btn:hover{background:#e8c84a;box-shadow:0 6px 24px rgba(201,162,39,.4);color:#0a0a0a;text-decoration:none;}
.bk-confirm-btn:disabled,.bk-confirm-btn[disabled]{opacity:.4;cursor:not-allowed;box-shadow:none;}

/* ── Info section ── */
.bk-info-row{display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;}
.bk-info-icon{width:36px;height:36px;border-radius:9px;background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.18);display:flex;align-items:center;justify-content:center;color:#C9A227;font-size:.9rem;flex-shrink:0;}
.bk-info-row .text{font-size:.84rem;color:rgba(255,255,255,.65);font-family:'Poppins',sans-serif;line-height:1.5;padding-top:6px;}
.bk-info-row .text a{color:#C9A227;text-decoration:none;}
.bk-wh-table{width:100%;border-collapse:collapse;font-size:.8rem;font-family:'Poppins',sans-serif;}
.bk-wh-table tr{border-bottom:1px solid rgba(255,255,255,.04);}
.bk-wh-table tr.today{background:rgba(201,162,39,.06);}
.bk-wh-table td{padding:7px 10px;color:rgba(255,255,255,.55);}
.bk-wh-table td:first-child{font-weight:600;color:rgba(255,255,255,.8);}
.bk-wh-table tr.today td:first-child{color:#C9A227;}
.bk-map-wrap{border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.07);}
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
                @if($company->category)
                <li class="nav-item"><a class="nav-link" href="{{ route('front.category', $company->category->slug) }}">{{ $catName }}</a></li>
                @endif
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

{{-- ========== COVER SLIDESHOW ========== --}}
<div id="bk-cover">
    @if($allImages->isNotEmpty())
        @foreach($allImages as $i => $img)
        <div class="bk-cover-slide {{ $i === 0 ? 'active' : '' }}">
            <img src="{{ asset('storage/'.$img->path) }}" alt="{{ $brName }}" loading="{{ $i > 0 ? 'lazy' : 'eager' }}">
        </div>
        @endforeach
        @if($allImages->count() > 1)
        <div class="bk-cover-dots">
            @foreach($allImages as $i => $img)
            <button class="bk-cover-dot {{ $i === 0 ? 'active' : '' }}" data-slide="{{ $i }}"></button>
            @endforeach
        </div>
        @endif
    @else
        <div class="bk-cover-no-img"><i class="{{ $catIcon }}"></i></div>
    @endif
    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.25) 0%,rgba(10,10,10,.8) 100%);z-index:2;"></div>
</div>

{{-- ========== PROFILE BAR ========== --}}
<div class="bk-profile-bar">
    <div class="container">
        <div class="d-flex align-items-center gap-4 flex-wrap justify-content-between">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                {{-- Logo --}}
                <div class="bk-pb-logo">
                    @if($company->logo)
                        <img src="{{ asset('storage/'.$company->logo) }}" alt="">
                    @else
                        <i class="{{ $catIcon }}" style="font-size:1.8rem;color:#C9A227;"></i>
                    @endif
                </div>
                <div>
                    <div class="bk-pb-name">{{ $brName }}</div>
                    <div class="bk-pb-sub">{{ $coName }}</div>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        @if($catName)
                        <span class="bk-pb-badge cat"><i class="{{ $catIcon }}"></i> {{ $catName }}</span>
                        @endif
                        <span class="bk-pb-badge {{ $isOpenNow ? 'open' : 'closed' }}">
                            <i class="fas fa-circle" style="font-size:.45rem;"></i>
                            {{ $isOpenNow ? ($isAr?'مفتوح الآن':'Open Now') : ($isAr?'مغلق الآن':'Closed Now') }}
                        </span>
                        @if($avgRating > 0)
                        <span class="bk-pb-badge cat">
                            <i class="fas fa-star"></i> {{ round($avgRating,1) }}
                            <span style="opacity:.6;">({{ $totalRev }})</span>
                        </span>
                        @endif
                        @if($branch->address)
                        <span class="bk-pb-badge cat" style="font-weight:400;"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($branch->address, 30) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <a href="#bk-cart" class="bk-pb-book-btn">
                <i class="far fa-calendar-check"></i>
                {{ $isAr ? 'احجز الآن' : 'Book Now' }}
            </a>
        </div>
    </div>
</div>

{{-- ========== STICKY TAB BAR ========== --}}
<div id="bk-tabs-bar">
    <div class="container">
        <div class="bk-tabs-inner">
            <button class="bk-tab-btn active" data-target="tab-services">
                <i class="fas fa-cut"></i> {{ $isAr ? 'الخدمات' : 'Services' }}
            </button>
            @if($allImages->isNotEmpty())
            <button class="bk-tab-btn" data-target="tab-gallery">
                <i class="fas fa-images"></i> {{ $isAr ? 'الصور' : 'Gallery' }}
            </button>
            @endif
            @if($employees->isNotEmpty())
            <button class="bk-tab-btn" data-target="tab-team">
                <i class="fas fa-users"></i> {{ $isAr ? 'الفريق' : 'Team' }}
            </button>
            @endif
            @if($reviews->isNotEmpty())
            <button class="bk-tab-btn" data-target="tab-reviews">
                <i class="fas fa-star"></i> {{ $isAr ? 'التقييمات' : 'Reviews' }}
            </button>
            @endif
            <button class="bk-tab-btn" data-target="tab-info">
                <i class="fas fa-info-circle"></i> {{ $isAr ? 'معلومات' : 'Info' }}
            </button>
        </div>
    </div>
</div>

{{-- ========== MAIN CONTENT ========== --}}
<div class="container">
    <div class="bk-main-grid">

        {{-- ===== LEFT COLUMN ===== --}}
        <div>

            {{-- SERVICES --}}
            <div id="tab-services" class="bk-tab-section">
                @if($servicesByCategory->isNotEmpty())
                @foreach($servicesByCategory as $catId => $services)
                @php
                    $firstSvc = $services->first();
                    $scName   = $isAr
                        ? ($firstSvc->serviceCategory->name_ar ?? $firstSvc->serviceCategory->name_en ?? '')
                        : ($firstSvc->serviceCategory->name_en ?? '');
                @endphp
                <div class="mb-5">
                    <div class="bk-cat-label">
                        <i class="{{ $catIcon }}"></i>
                        {{ $scName ?: ($isAr ? 'خدمات عامة' : 'General Services') }}
                    </div>
                    @foreach($services as $si => $svc)
                    @php
                        $svcName = $isAr ? ($svc->name_ar ?: $svc->name_en) : $svc->name_en;
                        $svcDesc = $isAr ? ($svc->description_ar ?? $svc->description ?? '') : ($svc->description ?? '');
                    @endphp
                    <div class="bk-svc-card" id="svc-wrap-{{ $svc->id }}">
                        @if($si === 0)
                        <span class="bk-popular-badge">
                            <i class="fas fa-fire {{ $isAr?'ms-1':'me-1' }}"></i>{{ $isAr?'الأكثر طلباً':'Popular' }}
                        </span>
                        @endif
                        <div class="icon"><i class="{{ $catIcon }}"></i></div>
                        <div class="info">
                            <h5>{{ $svcName }}</h5>
                            @if($svcDesc)
                            <p class="desc">{{ $svcDesc }}</p>
                            @endif
                            <div class="meta">
                                <span class="price">{{ number_format($svc->price,0) }} {{ $isAr?'ر.س':'SAR' }}</span>
                                @if($svc->duration_minutes)
                                <span class="dur"><i class="fas fa-clock"></i> {{ $svc->duration_minutes }} {{ $isAr?'دقيقة':'min' }}</span>
                                @endif
                            </div>
                        </div>
                        <button class="add-btn"
                                id="add-btn-{{ $svc->id }}"
                                onclick="bkAddToCart({{ $svc->id }}, {{ json_encode($svcName) }}, {{ $svc->price }}, {{ $svc->duration_minutes ?? 0 }}, {{ $catId ?? 'null' }})">
                            <i class="fas fa-plus {{ $isAr?'ms-1':'me-1' }}"></i>{{ $isAr?'أضف للحجز':'Add to Booking' }}
                        </button>
                    </div>
                    @endforeach
                </div>
                @endforeach
                @else
                <div style="text-align:center;padding:50px 20px;color:rgba(255,255,255,.3);">
                    <i class="fas fa-cut" style="font-size:3rem;color:rgba(201,162,39,.1);display:block;margin-bottom:14px;"></i>
                    <p>{{ $isAr ? 'لا توجد خدمات متاحة حالياً.' : 'No services available at the moment.' }}</p>
                </div>
                @endif
            </div>

            {{-- GALLERY --}}
            @if($allImages->isNotEmpty())
            <div id="tab-gallery" class="bk-tab-section" style="display:none;">
                <div class="bk-section-head">
                    <h3><i class="fas fa-images {{ $isAr?'ms-2':'me-2' }}" style="color:#C9A227;"></i>{{ $isAr ? 'معرض الصور' : 'Gallery' }}</h3>
                </div>
                <div class="bk-gallery-grid">
                    @foreach($allImages as $img)
                    <a href="{{ asset('storage/'.$img->path) }}" class="bk-gallery-item bk-lightbox" data-group="gallery">
                        <img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- TEAM --}}
            @if($employees->isNotEmpty())
            <div id="tab-team" class="bk-tab-section" style="display:none;">
                <div class="bk-section-head">
                    <h3><i class="fas fa-users {{ $isAr?'ms-2':'me-2' }}" style="color:#C9A227;"></i>{{ $isAr ? 'الفريق' : 'Our Team' }}</h3>
                </div>
                <div class="row g-3">
                    @foreach($employees as $emp)
                    @php
                        $empName  = $isAr ? ($emp->name_ar ?: $emp->name_en) : $emp->name_en;
                        $roleName = $isAr ? ($emp->role->name_ar ?? $emp->role->name_en ?? '') : ($emp->role->name_en ?? '');
                    @endphp
                    <div class="col-sm-6 col-md-4">
                        <div class="bk-emp-card">
                            @if($emp->image)
                            <div class="photo" style="width:auto;height:auto;background:none;border:none;display:block;">
                                <img src="{{ asset('storage/'.$emp->image) }}" alt="{{ $empName }}">
                            </div>
                            @else
                            <div class="photo-icon"><i class="fas fa-user"></i></div>
                            @endif
                            <h5>{{ $empName }}</h5>
                            @if($roleName)
                            <div class="role">{{ $roleName }}</div>
                            @endif
                            @if($emp->serviceCategories->isNotEmpty())
                            <div class="spec-chips">
                                @foreach($emp->serviceCategories->take(3) as $sc)
                                <span class="spec-chip">{{ $isAr ? ($sc->name_ar ?? $sc->name_en) : $sc->name_en }}</span>
                                @endforeach
                            </div>
                            @endif
                            <button class="bk-emp-book-btn" onclick="bkBookWithEmployee({{ $emp->id }}, {{ json_encode($empName) }})">
                                <i class="fas fa-calendar-plus {{ $isAr?'ms-1':'me-1' }}"></i>
                                {{ $isAr ? 'احجز معي' : 'Book with me' }}
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- REVIEWS --}}
            @if($reviews->isNotEmpty())
            <div id="tab-reviews" class="bk-tab-section" style="display:none;">
                <div class="bk-section-head">
                    <h3><i class="fas fa-star {{ $isAr?'ms-2':'me-2' }}" style="color:#C9A227;"></i>{{ $isAr ? 'التقييمات' : 'Reviews' }}</h3>
                </div>
                <div class="bk-rating-overview">
                    <div>
                        <div class="bk-rating-big">{{ number_format($avgRating,1) }}</div>
                        <div style="display:flex;gap:3px;margin:4px 0;">
                            @for($s=1;$s<=5;$s++)
                            @php $f = $s <= floor($stars) ? 'fas' : ($s <= $stars ? 'fas fa-star-half-alt' : 'far'); @endphp
                            <i class="{{ $f === 'fas fa-star-half-alt' ? $f : $f.' fa-star' }}" style="color:#C9A227;font-size:.9rem;"></i>
                            @endfor
                        </div>
                        <div style="font-size:.78rem;color:rgba(255,255,255,.4);font-family:'Poppins',sans-serif;">{{ $totalRev }} {{ $isAr?'تقييم':'reviews' }}</div>
                    </div>
                </div>
                @foreach($reviews->take(10) as $rev)
                @php
                    $custName = $isAr
                        ? ($rev->customer->name_ar ?? $rev->customer->name ?? 'عميل')
                        : ($rev->customer->name ?? 'Customer');
                @endphp
                <div class="bk-rev-card">
                    <div class="auth-row">
                        <div class="av"><i class="fas fa-user"></i></div>
                        <div>
                            <div class="name">{{ $custName }}</div>
                            <div class="date">{{ $rev->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    <div class="stars">
                        @for($s=1;$s<=5;$s++)
                        <i class="{{ $s<=$rev->rating?'fas':'far' }} fa-star"></i>
                        @endfor
                    </div>
                    @if($rev->comment)
                    <p>{{ $rev->comment }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- INFO --}}
            <div id="tab-info" class="bk-tab-section" style="display:none;">
                <div class="bk-section-head">
                    <h3><i class="fas fa-info-circle {{ $isAr?'ms-2':'me-2' }}" style="color:#C9A227;"></i>{{ $isAr ? 'معلومات الفرع' : 'Branch Info' }}</h3>
                </div>

                @if($branch->phone)
                <div class="bk-info-row">
                    <div class="bk-info-icon"><i class="fas fa-phone"></i></div>
                    <div class="text"><a href="tel:{{ $branch->phone }}">{{ $branch->phone }}</a></div>
                </div>
                @endif
                @if($company->email)
                <div class="bk-info-row">
                    <div class="bk-info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="text"><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></div>
                </div>
                @endif
                @if($branch->address)
                <div class="bk-info-row">
                    <div class="bk-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="text">{{ $branch->address }}</div>
                </div>
                @endif

                {{-- Social links --}}
                @if($company->socialLinks && $company->socialLinks->isNotEmpty())
                <div class="bk-info-row">
                    <div class="bk-info-icon"><i class="fas fa-share-alt"></i></div>
                    <div class="text d-flex gap-3 flex-wrap">
                        @foreach($company->socialLinks as $sl)
                        <a href="{{ $sl->url }}" target="_blank" style="color:#C9A227;text-decoration:none;font-size:.84rem;">
                            <i class="fab fa-{{ strtolower($sl->platform ?? 'link') }} {{ $isAr?'ms-1':'me-1' }}"></i>
                            {{ $sl->platform ?? 'Link' }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Working hours --}}
                @if($branch->workingHours->isNotEmpty())
                <div style="margin:24px 0 16px;">
                    <div class="bk-cat-label"><i class="fas fa-clock"></i> {{ $isAr?'أوقات العمل':'Working Hours' }}</div>
                    <div style="background:#141414;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden;">
                        <table class="bk-wh-table">
                            @for($d=0;$d<=6;$d++)
                            @php
                                $dayHours = $whByDay->get($d, collect());
                                $isToday  = $d === $todayDow;
                            @endphp
                            <tr class="{{ $isToday ? 'today' : '' }}">
                                <td>{{ $dayNames[$d] }}{{ $isToday ? ' ('.($isAr?'اليوم':'Today').')' : '' }}</td>
                                <td style="text-align:{{ $isAr?'left':'right' }};">
                                    @if($dayHours->isEmpty())
                                        <span style="color:rgba(255,255,255,.25);">{{ $isAr?'—':'—' }}</span>
                                    @elseif($dayHours->where('is_open',true)->isEmpty())
                                        <span style="color:#f87171;">{{ $isAr?'مغلق':'Closed' }}</span>
                                    @else
                                        @foreach($dayHours->where('is_open',true) as $wh)
                                        <span style="color:rgba(255,255,255,.7);">
                                            {{ $wh->open_time ? \Carbon\Carbon::createFromFormat('H:i:s',$wh->open_time)->format('h:i A') : '' }}
                                            –
                                            {{ $wh->close_time ? \Carbon\Carbon::createFromFormat('H:i:s',$wh->close_time)->format('h:i A') : '' }}
                                        </span>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                            @endfor
                        </table>
                    </div>
                </div>
                @endif

                {{-- Map --}}
                @if($branch->latitude && $branch->longitude)
                <div class="mt-4">
                    <div class="bk-cat-label"><i class="fas fa-map"></i> {{ $isAr?'الموقع على الخريطة':'Location' }}</div>
                    <div class="bk-map-wrap">
                        <iframe
                            src="https://maps.google.com/maps?q={{ $branch->latitude }},{{ $branch->longitude }}&z=15&output=embed"
                            width="100%" height="280" frameborder="0" style="display:block;border:0;"
                            allowfullscreen="" loading="lazy">
                        </iframe>
                    </div>
                </div>
                @endif
            </div>

        </div>{{-- end left column --}}

        {{-- ===== RIGHT COLUMN: BOOKING CART ===== --}}
        <div id="bk-cart">
            <div class="bk-cart-sticky">
                <div class="bk-cart-head">
                    <i class="far fa-calendar-check" style="color:#C9A227;font-size:1.1rem;"></i>
                    <h4>{{ $isAr ? 'سلة الحجز' : 'Booking Cart' }}</h4>
                    <span class="bk-cart-badge ms-auto" id="cart-count">0</span>
                </div>
                <div class="bk-cart-body">
                    <div id="cart-empty-msg" class="bk-cart-empty">
                        <i class="fas fa-calendar-plus"></i>
                        <p>{{ $isAr ? 'اختر خدمة من القائمة لإضافتها للحجز.' : 'Select services from the list to add them here.' }}</p>
                    </div>
                    <div id="cart-items"></div>
                    <div id="cart-total-section" class="bk-cart-total" style="display:none;">
                        <div class="row-t">
                            <span class="lbl">{{ $isAr ? 'إجمالي المدة' : 'Total Duration' }}</span>
                            <span class="val" id="cart-duration">0 {{ $isAr?'دقيقة':'min' }}</span>
                        </div>
                        <div class="row-t">
                            <span class="lbl">{{ $isAr ? 'إجمالي السعر' : 'Total Price' }}</span>
                            <span class="val gold" id="cart-price">0 {{ $isAr?'ر.س':'SAR' }}</span>
                        </div>
                        <a href="#" id="cart-confirm-btn" class="bk-confirm-btn">
                            <i class="far fa-calendar-check"></i>
                            {{ $isAr ? 'تأكيد الحجز' : 'Confirm Booking' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end grid --}}
</div>{{-- end container --}}

@include('front.partials.footer')

{{-- ========== SCRIPTS ========== --}}
<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.cookie/jquery.cookie.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/owl.carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('frontend/js/theme.js') }}"></script>
<script src="{{ asset('frontend/js/custom.js') }}"></script>
<script src="{{ asset('frontend/js/theme.init.js') }}"></script>

<script>
(function($){
    'use strict';

    var isAr  = {{ $isAr ? 'true' : 'false' }};
    var branchId = {{ $branch->id }};
    var companyId = {{ $company->id }};

    // Employee & service data from PHP
    var allEmployees = @json($empData);
    var allServices  = @json($svcData);

    // Cart state: array of { serviceId, serviceName, price, duration, catId, employeeId, employeeName }
    var cart = [];

    $(document).ready(function(){
        // Navbar scroll
        $(window).on('scroll', function(){
            $(this).scrollTop()>30 ? $('#bk-navbar').addClass('scrolled') : $('#bk-navbar').removeClass('scrolled');
        });

        // Tab switching
        $('.bk-tab-btn').on('click', function(){
            var target = $(this).data('target');
            $('.bk-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.bk-tab-section').hide();
            $('#'+target).show();
        });

        // Cover slideshow
        var slides = $('.bk-cover-slide');
        if(slides.length > 1){
            var cur = 0;
            function goSlide(n){
                slides.removeClass('active');
                $('.bk-cover-dot').removeClass('active');
                cur = (n + slides.length) % slides.length;
                slides.eq(cur).addClass('active');
                $('.bk-cover-dot').eq(cur).addClass('active');
            }
            $('.bk-cover-dot').on('click', function(){ goSlide($(this).data('slide')); });
            setInterval(function(){ goSlide(cur+1); }, 5000);
        }

        // Lightbox
        if($.fn.magnificPopup){
            $('.bk-lightbox').magnificPopup({
                type:'image', gallery:{enabled:true},
                image:{verticalFit:true},
                mainClass:'mfp-with-zoom'
            });
        }
    });

    // Add service to cart
    window.bkAddToCart = function(svcId, svcName, price, duration, catId){
        // Check if already added
        if(cart.find(function(i){ return i.serviceId===svcId; })){
            bkRemoveFromCart(svcId);
            return;
        }

        // Find matching employees for this service's category
        var matchedEmps = allEmployees.filter(function(e){
            return catId && e.cats && e.cats.indexOf(catId) !== -1;
        });
        if(matchedEmps.length === 0) matchedEmps = allEmployees.slice();

        var item = {
            serviceId: svcId,
            serviceName: svcName,
            price: price,
            duration: duration,
            catId: catId,
            employeeId: null,
            employeeName: isAr ? 'أي موظف' : 'Any Employee',
            matchedEmps: matchedEmps
        };
        cart.push(item);
        bkRenderCart();

        var btn = document.getElementById('add-btn-'+svcId);
        if(btn){
            btn.innerHTML = '<i class="fas fa-check '+(isAr?'ms-1':'me-1')+'"></i>'+(isAr?'تمت الإضافة ✓':'Added ✓');
            btn.classList.add('added');
        }
    };

    // Remove from cart
    window.bkRemoveFromCart = function(svcId){
        cart = cart.filter(function(i){ return i.serviceId !== svcId; });
        bkRenderCart();
        var btn = document.getElementById('add-btn-'+svcId);
        if(btn){
            btn.innerHTML = '<i class="fas fa-plus '+(isAr?'ms-1':'me-1')+'"></i>'+(isAr?'أضف للحجز':'Add to Booking');
            btn.classList.remove('added');
        }
    };

    // Update employee selection
    window.bkUpdateEmployee = function(svcId, empId, empName){
        var item = cart.find(function(i){ return i.serviceId===svcId; });
        if(item){
            item.employeeId   = empId ? parseInt(empId) : null;
            item.employeeName = empName;
        }
        bkUpdateConfirmLink();
    };

    // Book with employee (from team section)
    window.bkBookWithEmployee = function(empId, empName){
        // Switch to services tab
        $('.bk-tab-btn[data-target="tab-services"]').trigger('click');
        // Scroll to cart
        setTimeout(function(){
            var cartEl = document.getElementById('bk-cart');
            if(cartEl) cartEl.scrollIntoView({behavior:'smooth', block:'start'});
        }, 200);
        // Update all cart items to use this employee
        cart.forEach(function(item){
            item.employeeId   = empId;
            item.employeeName = empName;
        });
        bkRenderCart();
        // Show toast
        bkToast(isAr ? 'سيتم حجز كل الخدمات مع '+empName : 'All services will be booked with '+empName);
    };

    function bkRenderCart(){
        var $items = $('#cart-items');
        var $empty = $('#cart-empty-msg');
        var $total = $('#cart-total-section');
        var $count = $('#cart-count');
        $items.empty();

        if(cart.length === 0){
            $empty.show();
            $total.hide();
            $count.text('0');
            return;
        }
        $empty.hide();
        $count.text(cart.length);

        cart.forEach(function(item){
            var empOptions = '<option value="">'+(isAr?'أي موظف':'Any Employee')+'</option>';
            item.matchedEmps.forEach(function(e){
                var sel = item.employeeId === e.id ? ' selected' : '';
                empOptions += '<option value="'+e.id+'"'+sel+'>'+e.name+'</option>';
            });

            var html = '<div class="bk-cart-item" id="ci-'+item.serviceId+'">'
                +'<button class="remove-btn" onclick="bkRemoveFromCart('+item.serviceId+')" title="'+(isAr?'حذف':'Remove')+'"><i class="fas fa-times"></i></button>'
                +'<div class="svc-name">'+item.serviceName+'</div>'
                +'<div class="svc-meta">'
                +'<span class="svc-price">'+item.price+' '+(isAr?'ر.س':'SAR')+'</span>'
                +(item.duration ? '<span><i class="fas fa-clock" style="margin-'+(isAr?'left':'right')+':3px;"></i>'+item.duration+' '+(isAr?'د':'min')+'</span>' : '')
                +'</div>'
                +'<select class="emp-select" onchange="bkUpdateEmployee('+item.serviceId+', this.value, this.options[this.selectedIndex].text)">'+empOptions+'</select>'
                +'</div>';
            $items.append(html);
        });

        // Totals
        var totalPrice    = cart.reduce(function(s,i){ return s + (parseFloat(i.price)||0); }, 0);
        var totalDuration = cart.reduce(function(s,i){ return s + (parseInt(i.duration)||0); }, 0);
        $('#cart-price').text(totalPrice.toFixed(0)+' '+(isAr?'ر.س':'SAR'));
        $('#cart-duration').text(totalDuration+' '+(isAr?'دقيقة':'min'));
        $total.show();

        bkUpdateConfirmLink();
    }

    function bkUpdateConfirmLink(){
        if(cart.length === 0) return;
        // Build query string for booking page
        var params = [];
        params.push('branch_id='+branchId);
        cart.forEach(function(item, idx){
            params.push('services['+idx+']='+item.serviceId);
            if(item.employeeId) params.push('employees['+idx+']='+item.employeeId);
        });
        var url = '{{ route("company.appointments.create") }}?' + params.join('&');
        $('#cart-confirm-btn').attr('href', url);
    }

    function bkToast(msg){
        var t = $('<div>').css({
            position:'fixed', bottom:'24px', right: isAr ? 'auto' : '24px', left: isAr ? '24px' : 'auto',
            background:'#C9A227', color:'#0a0a0a', padding:'12px 22px',
            borderRadius:'10px', fontFamily:'Poppins,sans-serif', fontWeight:'700',
            fontSize:'.85rem', zIndex:9999, boxShadow:'0 6px 24px rgba(0,0,0,.4)',
            opacity:0
        }).text(msg);
        $('body').append(t);
        t.animate({opacity:1,bottom:'32px'},300).delay(2500).animate({opacity:0,bottom:'24px'},400,function(){ $(this).remove(); });
    }

})(jQuery);
</script>

</div>
</body>
</html>
