<!DOCTYPE html>
@php
    $isAr    = app()->getLocale() === 'ar';
    $dir     = $isAr ? 'rtl' : 'ltr';
    $lang    = $isAr ? 'ar' : 'en';
    $success = session('success');
@endphp
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $isAr ? 'تواصل معنا – بوكسي' : 'Contact Us – Booksy' }}</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap/css/bootstrap' . ($isAr ? '.rtl' : '') . '.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/theme-elements.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/skins/skin-booksy.css') }}">
<script src="{{ asset('frontend/vendor/modernizr/modernizr.min.js') }}"></script>

<style>
@if($isAr)
body,p,span,li,a,input,textarea,select,label,button{font-family:'Tajawal',sans-serif!important;}
h1,h2,h3,h4,h5,h6{font-family:'Tajawal',sans-serif!important;font-weight:800;}
@endif
html,body{background:#0a0a0a!important;color:rgba(255,255,255,.82)!important;scroll-behavior:smooth;}
.section{background-color:transparent!important;}
.main,.body{background:#0a0a0a!important;}

/* Navbar */
#bk-navbar{background:#0a0a0a;border-bottom:1px solid rgba(201,162,39,.15);height:68px;z-index:1050;transition:box-shadow .3s;}
#bk-navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.6);}
#bk-navbar .navbar-brand{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:900;color:#fff;letter-spacing:-1px;text-decoration:none;}
#bk-navbar .navbar-brand span{color:#C9A227;}
#bk-navbar .navbar-toggler{border:1px solid rgba(201,162,39,.35);padding:6px 10px;color:#C9A227;background:transparent;}
#bk-navbar .navbar-toggler:focus{box-shadow:none;}
#bk-navbar .nav-link{color:rgba(255,255,255,.7)!important;font-family:'Poppins',sans-serif;font-size:.86rem;font-weight:500;padding:.5rem .9rem!important;border-radius:6px;transition:all .2s;}
#bk-navbar .nav-link:hover,#bk-navbar .nav-link.active-page{color:#C9A227!important;background:rgba(201,162,39,.07);}
.bk-lang{color:#C9A227;border:1px solid rgba(201,162,39,.4);border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;transition:all .2s;}
.bk-lang:hover{background:#C9A227;color:#0a0a0a;}
.bk-register-btn{background:#C9A227;color:#0a0a0a!important;border:none;border-radius:22px;padding:8px 20px;font-size:.83rem;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .22s;white-space:nowrap;}
.bk-register-btn:hover{background:#e8c84a;box-shadow:0 4px 18px rgba(201,162,39,.35);}
@media(max-width:991px){#bk-navbar .navbar-collapse{background:#111;border:1px solid rgba(201,162,39,.12);border-radius:12px;padding:16px;margin-top:10px;}}

/* PAGE HERO */
#contact-hero{
    padding:130px 0 70px;
    background:#0a0a0a;
    position:relative;overflow:hidden;
}
#contact-hero::before{
    content:'';position:absolute;inset:0;
    background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(201,162,39,.07) 0%,transparent 70%);
    pointer-events:none;
}
/* animated ring */
.ring-anim{
    position:absolute;border-radius:50%;border:1px solid rgba(201,162,39,.1);
    animation:ringPulse 4s ease-in-out infinite;pointer-events:none;
}
@keyframes ringPulse{
    0%,100%{transform:scale(1);opacity:.5;}
    50%{transform:scale(1.08);opacity:1;}
}

/* CONTACT INFO CARDS */
.bk-info-card{
    background:#111;border:1px solid rgba(201,162,39,.12);border-radius:18px;
    padding:28px 24px;display:flex;align-items:flex-start;gap:18px;
    transition:all .3s;position:relative;overflow:hidden;
}
.bk-info-card::before{
    content:'';position:absolute;{{ $isAr ? 'right' : 'left' }}:0;top:0;bottom:0;width:3px;
    background:linear-gradient(180deg,#C9A227,rgba(201,162,39,.2));
    transform:scaleY(0);transform-origin:top;transition:transform .35s cubic-bezier(.25,.8,.25,1);
}
.bk-info-card:hover{border-color:rgba(201,162,39,.35);transform:translateY(-4px);box-shadow:0 14px 40px rgba(0,0,0,.4);}
.bk-info-card:hover::before{transform:scaleY(1);}
.bk-info-icon{width:52px;height:52px;border-radius:14px;background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.2);
    display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#C9A227;flex-shrink:0;transition:all .28s;}
.bk-info-card:hover .bk-info-icon{background:#C9A227;color:#0a0a0a;border-color:#C9A227;}
.bk-info-card h6{font-size:.82rem;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;font-family:'Poppins',sans-serif;}
.bk-info-card p,.bk-info-card a{font-size:.95rem;color:#fff;font-weight:600;text-decoration:none;margin:0;transition:color .2s;}
.bk-info-card a:hover{color:#C9A227;}

/* FORM */
.bk-contact-form{
    background:#111;border:1px solid rgba(201,162,39,.12);border-radius:22px;
    padding:40px 36px;box-shadow:0 30px 80px rgba(0,0,0,.4);
}
.bk-form-group{margin-bottom:22px;position:relative;}
.bk-form-group label{
    display:block;font-size:.8rem;font-weight:700;color:#C9A227;
    text-transform:uppercase;letter-spacing:1.2px;font-family:'Poppins',sans-serif;margin-bottom:8px;
}
.bk-form-group .bk-input{
    width:100%;background:#1a1a1a;border:1px solid rgba(201,162,39,.2);
    border-radius:12px;padding:14px 18px;color:#fff;font-size:.92rem;
    font-family:inherit;outline:none;transition:all .25s;resize:none;
}
.bk-form-group .bk-input::placeholder{color:rgba(255,255,255,.3);}
.bk-form-group .bk-input:focus{border-color:#C9A227;box-shadow:0 0 0 3px rgba(201,162,39,.12);background:#1f1f1f;}
/* floating label line effect */
.bk-form-group::after{
    content:'';position:absolute;bottom:0;{{ $isAr ? 'right' : 'left' }}:0;height:2px;width:0;
    background:#C9A227;border-radius:1px;transition:width .35s cubic-bezier(.25,.8,.25,1);
}
.bk-form-group:focus-within::after{width:100%;}
/* error */
.bk-err{font-size:.77rem;color:#ff6b6b;margin-top:5px;font-family:'Poppins',sans-serif;}
/* submit */
.bk-submit{
    width:100%;background:#C9A227;color:#0a0a0a;border:none;border-radius:14px;
    padding:15px;font-size:.95rem;font-weight:700;font-family:'Poppins',sans-serif;
    cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:10px;
    position:relative;overflow:hidden;
}
.bk-submit::before{
    content:'';position:absolute;top:50%;left:50%;width:0;height:0;
    background:rgba(255,255,255,.2);border-radius:50%;transform:translate(-50%,-50%);
    transition:width .5s, height .5s;
}
.bk-submit:hover::before{width:300px;height:300px;}
.bk-submit:hover{background:#e8c84a;box-shadow:0 8px 28px rgba(201,162,39,.35);transform:translateY(-2px);}
.bk-submit:active{transform:translateY(0);}

/* SUCCESS */
.bk-success{
    background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.35);
    border-radius:14px;padding:20px 24px;margin-bottom:22px;
    display:flex;align-items:center;gap:14px;
    animation:slideDown .4s ease;
}
@keyframes slideDown{from{opacity:0;transform:translateY(-15px);}to{opacity:1;transform:none;}}
.bk-success i{font-size:1.5rem;color:#C9A227;}
.bk-success span{font-size:.92rem;color:rgba(255,255,255,.8);font-weight:600;}

/* MAP PLACEHOLDER */
.bk-map{
    background:#111;border:1px solid rgba(201,162,39,.12);border-radius:18px;
    overflow:hidden;height:320px;position:relative;
}
.bk-map-inner{width:100%;height:100%;object-fit:cover;filter:grayscale(1) brightness(.4) contrast(1.2);transition:filter .3s;}
.bk-map:hover .bk-map-inner{filter:grayscale(.4) brightness(.55);}
.bk-map-pin{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);
    width:50px;height:50px;border-radius:50%;background:#C9A227;
    display:flex;align-items:center;justify-content:center;color:#0a0a0a;font-size:1.4rem;
    box-shadow:0 0 0 8px rgba(201,162,39,.2);
    animation:pinBounce 1.5s ease-in-out infinite;
}
@keyframes pinBounce{
    0%,100%{transform:translate(-50%,-60%);}
    50%{transform:translate(-50%,-75%);}
}

/* FAQ */
.bk-faq-item{
    background:#111;border:1px solid rgba(201,162,39,.1);border-radius:14px;
    margin-bottom:12px;overflow:hidden;transition:border-color .25s;
}
.bk-faq-item.open{border-color:rgba(201,162,39,.4);}
.bk-faq-q{
    width:100%;background:transparent;border:none;padding:18px 22px;
    color:#fff;font-size:.94rem;font-weight:600;font-family:inherit;
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    cursor:pointer;text-align:{{ $isAr ? 'right' : 'left' }};
}
.bk-faq-q .bk-faq-icon{color:#C9A227;font-size:.8rem;transition:transform .3s;flex-shrink:0;}
.bk-faq-item.open .bk-faq-icon{transform:rotate(180deg);}
.bk-faq-a{max-height:0;overflow:hidden;transition:max-height .35s ease,padding .3s;}
.bk-faq-item.open .bk-faq-a{max-height:200px;}
.bk-faq-a p{padding:0 22px 18px;color:rgba(255,255,255,.5);font-size:.88rem;line-height:1.75;margin:0;}

/* Footer */
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
                <li class="nav-item"><a class="nav-link" href="{{ route('front.about') }}">{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                <li class="nav-item"><a class="nav-link active-page" href="{{ route('front.contact') }}">{{ $isAr ? 'تواصل معنا' : 'Contact' }}</a></li>
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

{{-- ═══ HERO ═══ --}}
<section id="contact-hero">
    <div class="ring-anim" style="width:500px;height:500px;top:-200px;{{ $isAr ? 'left' : 'right' }}:-150px;animation-delay:0s;"></div>
    <div class="ring-anim" style="width:280px;height:280px;bottom:-60px;{{ $isAr ? 'right' : 'left' }}:-60px;animation-delay:1.5s;animation-duration:5s;"></div>

    <div class="container position-relative" style="z-index:2;">
        <div class="text-center" data-aos="fade-up">
            <p style="font-size:.72rem;font-weight:700;color:#C9A227;text-transform:uppercase;letter-spacing:4px;font-family:'Poppins',sans-serif;margin-bottom:12px;">
                ✦ {{ $isAr ? 'نحن هنا لمساعدتك' : 'We Are Here to Help' }} ✦
            </p>
            <h1 style="font-size:3rem;font-weight:900;color:#fff;line-height:1.15;margin-bottom:16px;">
                {{ $isAr ? 'تواصل' : 'Get in' }} <span style="color:#C9A227;">{{ $isAr ? 'معنا' : 'Touch' }}</span>
            </h1>
            <p style="color:rgba(255,255,255,.5);font-size:1rem;max-width:520px;margin:0 auto 36px;line-height:1.75;">
                {{ $isAr ? 'لديك سؤال، اقتراح، أو تريد تسجيل نشاطك؟ فريقنا يرد خلال 24 ساعة.' : 'Have a question, suggestion, or want to list your business? Our team replies within 24 hours.' }}
            </p>

            {{-- info cards --}}
            <div class="row g-3 justify-content-center mt-2">
                @php
                    $infos = [
                        ['i'=>'fas fa-envelope','ar'=>'البريد الإلكتروني','en'=>'Email','val'=>'info@booksy.app','link'=>'mailto:info@booksy.app'],
                        ['i'=>'fab fa-whatsapp','ar'=>'واتساب','en'=>'WhatsApp','val'=>'+966 5X XXX XXXX','link'=>'#'],
                        ['i'=>'fas fa-map-marker-alt','ar'=>'الموقع','en'=>'Location','val'=>$isAr ? 'الرياض، المملكة العربية السعودية' : 'Riyadh, Saudi Arabia','link'=>'#'],
                        ['i'=>'fas fa-clock','ar'=>'أوقات العمل','en'=>'Working Hours','val'=>$isAr ? 'الأحد – الخميس، 9ص – 6م' : 'Sun – Thu, 9AM – 6PM','link'=>'#'],
                    ];
                @endphp
                @foreach($infos as $idx => $info)
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $idx * 80 }}">
                    <div class="bk-info-card">
                        <div class="bk-info-icon"><i class="{{ $info['i'] }}"></i></div>
                        <div>
                            <h6>{{ $isAr ? $info['ar'] : $info['en'] }}</h6>
                            <a href="{{ $info['link'] }}">{{ $info['val'] }}</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══ FORM + MAP ═══ --}}
<section style="padding:70px 0;background:#080808;">
    <div class="container">
        <div class="row g-5 align-items-start">

            {{-- FORM --}}
            <div class="col-lg-7" data-aos="fade-right">
                <div class="bk-contact-form">
                    <h3 style="color:#fff;font-size:1.4rem;font-weight:700;margin-bottom:6px;">
                        {{ $isAr ? 'أرسل لنا رسالة' : 'Send Us a Message' }}
                    </h3>
                    <p style="color:rgba(255,255,255,.4);font-size:.85rem;margin-bottom:28px;">
                        {{ $isAr ? 'سيصلك ردنا خلال 24 ساعة على بريدك الإلكتروني.' : 'You will receive our reply within 24 hours to your email.' }}
                    </p>

                    @if($success)
                    <div class="bk-success">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ $isAr ? 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.' : 'Your message has been sent! We will get back to you soon.' }}</span>
                    </div>
                    @endif

                    <form action="{{ route('front.contact.send') }}" method="POST" id="contactForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="bk-form-group">
                                    <label>{{ $isAr ? 'الاسم الكامل' : 'Full Name' }} <span style="color:#C9A227;">*</span></label>
                                    <input type="text" name="name" class="bk-input" value="{{ old('name') }}"
                                           placeholder="{{ $isAr ? 'اسمك الكامل' : 'Your full name' }}">
                                    @error('name')<div class="bk-err">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="bk-form-group">
                                    <label>{{ $isAr ? 'البريد الإلكتروني' : 'Email Address' }} <span style="color:#C9A227;">*</span></label>
                                    <input type="email" name="email" class="bk-input" value="{{ old('email') }}"
                                           placeholder="{{ $isAr ? 'your@email.com' : 'your@email.com' }}">
                                    @error('email')<div class="bk-err">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="bk-form-group">
                            <label>{{ $isAr ? 'الموضوع' : 'Subject' }} <span style="color:#C9A227;">*</span></label>
                            <input type="text" name="subject" class="bk-input" value="{{ old('subject') }}"
                                   placeholder="{{ $isAr ? 'موضوع رسالتك' : 'What is this about?' }}">
                            @error('subject')<div class="bk-err">{{ $message }}</div>@enderror
                        </div>
                        <div class="bk-form-group">
                            <label>{{ $isAr ? 'الرسالة' : 'Message' }} <span style="color:#C9A227;">*</span></label>
                            <textarea name="message" rows="5" class="bk-input"
                                      placeholder="{{ $isAr ? 'اكتب رسالتك هنا...' : 'Write your message here...' }}">{{ old('message') }}</textarea>
                            @error('message')<div class="bk-err">{{ $message }}</div>@enderror
                        </div>

                        {{-- type selector --}}
                        <div class="bk-form-group">
                            <label>{{ $isAr ? 'نوع الاستفسار' : 'Inquiry Type' }}</label>
                            <div class="d-flex flex-wrap gap-2" id="inquiryType">
                                @php
                                    $types = $isAr
                                        ? ['سؤال عام','تسجيل نشاط','شراكة','دعم فني','اقتراح']
                                        : ['General Question','Register Business','Partnership','Technical Support','Suggestion'];
                                @endphp
                                @foreach($types as $t)
                                <button type="button" class="bk-type-btn" onclick="selectType(this)">{{ $t }}</button>
                                @endforeach
                            </div>
                            <input type="hidden" name="inquiry_type" id="inquiryTypeVal">
                        </div>

                        <button type="submit" class="bk-submit" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            <span>{{ $isAr ? 'إرسال الرسالة' : 'Send Message' }}</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- SIDEBAR --}}
            <div class="col-lg-5" data-aos="fade-left" data-aos-delay="200">

                {{-- Map --}}
                <div class="bk-map mb-4">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3624.6746634843595!2d46.6752957!3d24.7135517!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2f03890d489399%3A0xba974d1c98e79fd5!2sRiyadh!5e0!3m2!1sen!2ssa!4v1699999999999!5m2!1sen!2ssa"
                        width="100%" height="100%" style="border:0;filter:grayscale(1) brightness(.4) contrast(1.2);"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        class="bk-map-inner">
                    </iframe>
                    <div class="bk-map-pin"><i class="fas fa-map-marker-alt"></i></div>
                </div>

                {{-- Social links --}}
                <div style="background:#111;border:1px solid rgba(201,162,39,.12);border-radius:16px;padding:24px;" class="mb-4">
                    <h6 style="color:#C9A227;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:16px;font-family:'Poppins',sans-serif;">
                        {{ $isAr ? 'تابعنا على' : 'Follow Us On' }}
                    </h6>
                    <div class="d-flex gap-3 flex-wrap">
                        @php
                            $socials = [
                                ['icon'=>'fab fa-instagram','label'=>'Instagram','color'=>'#E1306C'],
                                ['icon'=>'fab fa-twitter','label'=>'X / Twitter','color'=>'#1DA1F2'],
                                ['icon'=>'fab fa-tiktok','label'=>'TikTok','color'=>'#fff'],
                                ['icon'=>'fab fa-snapchat-ghost','label'=>'Snapchat','color'=>'#FFFC00'],
                                ['icon'=>'fab fa-linkedin-in','label'=>'LinkedIn','color'=>'#0A66C2'],
                            ];
                        @endphp
                        @foreach($socials as $s)
                        <a href="#" title="{{ $s['label'] }}"
                           style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,.05);
                                  border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;
                                  justify-content:center;color:rgba(255,255,255,.6);font-size:.95rem;
                                  text-decoration:none;transition:all .25s;"
                           onmouseover="this.style.borderColor='#C9A227';this.style.color='#C9A227';this.style.background='rgba(201,162,39,.08)'"
                           onmouseout="this.style.borderColor='rgba(255,255,255,.1)';this.style.color='rgba(255,255,255,.6)';this.style.background='rgba(255,255,255,.05)'">
                            <i class="{{ $s['icon'] }}"></i>
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Response time badge --}}
                <div style="background:rgba(201,162,39,.06);border:1px solid rgba(201,162,39,.2);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;">
                    <div style="width:46px;height:46px;border-radius:50%;background:#C9A227;display:flex;align-items:center;justify-content:center;color:#0a0a0a;font-size:1.1rem;flex-shrink:0;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <div style="font-size:.82rem;font-weight:700;color:#C9A227;font-family:'Poppins',sans-serif;">
                            {{ $isAr ? 'وقت الاستجابة' : 'Response Time' }}
                        </div>
                        <div style="font-size:.78rem;color:rgba(255,255,255,.5);margin-top:3px;">
                            {{ $isAr ? 'نرد عادةً خلال أقل من ساعة' : 'We usually reply in under 1 hour' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ═══ FAQ ═══ --}}
<section style="padding:70px 0;background:#0a0a0a;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="text-center mb-5" data-aos="fade-up">
                    <p class="section-label">{{ $isAr ? 'أسئلة شائعة' : 'FAQ' }}</p>
                    <h2 class="section-heading">{{ $isAr ? 'أسئلة' : 'Common' }} <span>{{ $isAr ? 'متكررة' : 'Questions' }}</span></h2>
                    <div class="divider-gold center"></div>
                </div>
                @php
                    $faqs = [
                        ['q_ar'=>'كيف أحجز موعداً؟','q_en'=>'How do I book an appointment?',
                         'a_ar'=>'ابحث عن المكان المناسب، اختر الخدمة والموعد، ثم أكّد حجزك. ستصلك رسالة تأكيد فوراً.',
                         'a_en'=>'Search for your preferred place, select service and time, then confirm your booking. A confirmation message will be sent instantly.'],
                        ['q_ar'=>'هل الحجز مجاني؟','q_en'=>'Is booking free?',
                         'a_ar'=>'نعم، الحجز عبر بوكسي مجاني تماماً للعملاء. تدفع فقط مقابل الخدمة في المكان.',
                         'a_en'=>'Yes, booking through Booksy is completely free for clients. You only pay for the service at the venue.'],
                        ['q_ar'=>'كيف أسجّل صالوني أو عيادتي؟','q_en'=>'How do I register my salon or clinic?',
                         'a_ar'=>'انقر على "سجّل نشاطك" في أعلى الصفحة، أكمل البيانات، وسيتواصل معك فريقنا خلال 24 ساعة.',
                         'a_en'=>'Click "List Business" at the top of the page, complete the details, and our team will contact you within 24 hours.'],
                        ['q_ar'=>'هل يمكنني إلغاء الحجز؟','q_en'=>'Can I cancel my booking?',
                         'a_ar'=>'نعم، يمكنك إلغاء حجزك قبل 24 ساعة من الموعد دون أي رسوم.',
                         'a_en'=>'Yes, you can cancel your booking up to 24 hours before the appointment without any fees.'],
                        ['q_ar'=>'في أي مناطق تتوفر بوكسي؟','q_en'=>'Which areas does Booksy cover?',
                         'a_ar'=>'نتوسع باستمرار! حالياً نغطي الرياض ونعمل على التوسع لمدن أخرى قريباً.',
                         'a_en'=>'We are constantly expanding! Currently covering Riyadh and working on expanding to other cities soon.'],
                    ];
                @endphp
                @foreach($faqs as $i => $faq)
                <div class="bk-faq-item" data-aos="fade-up" data-aos-delay="{{ $i * 60 }}">
                    <button class="bk-faq-q" onclick="toggleFaq(this)">
                        <span>{{ $isAr ? $faq['q_ar'] : $faq['q_en'] }}</span>
                        <i class="fas fa-chevron-down bk-faq-icon"></i>
                    </button>
                    <div class="bk-faq-a">
                        <p>{{ $isAr ? $faq['a_ar'] : $faq['a_en'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

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

{{-- TYPE BUTTON STYLE --}}
<style>
.bk-type-btn{
    background:rgba(201,162,39,.06);border:1px solid rgba(201,162,39,.2);
    border-radius:20px;padding:6px 16px;color:rgba(255,255,255,.6);
    font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit;
}
.bk-type-btn:hover,.bk-type-btn.selected{
    background:#C9A227;border-color:#C9A227;color:#0a0a0a;
}
</style>

<script src="{{ asset('frontend/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('frontend/vendor/jquery.appear/jquery.appear.min.js') }}"></script>
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

        /* AOS scroll animations */
        var observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    var el = entry.target;
                    var delay = parseInt(el.getAttribute('data-aos-delay')||0);
                    setTimeout(function(){
                        el.style.opacity = '1';
                        el.style.transform = 'none';
                        el.style.transition = 'opacity .7s ease, transform .7s ease';
                    }, delay);
                    observer.unobserve(el);
                }
            });
        }, {threshold: 0.1});

        $('[data-aos]').each(function(){
            var anim = $(this).attr('data-aos');
            $(this).css({opacity:0, transform:
                anim==='fade-up'    ? 'translateY(35px)'  :
                anim==='fade-down'  ? 'translateY(-35px)' :
                anim==='fade-left'  ? 'translateX(40px)'  :
                anim==='fade-right' ? 'translateX(-40px)' :
                'translateY(25px)'
            });
            observer.observe(this);
        });

        /* Form submit animation */
        $('#contactForm').on('submit', function(){
            var $btn = $('#submitBtn');
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>{{ $isAr ? "جاري الإرسال..." : "Sending..." }}</span>');
            $btn.css('opacity', '.8');
        });
    });
})(jQuery);

/* FAQ toggle */
function toggleFaq(btn){
    var item = btn.closest('.bk-faq-item');
    var isOpen = item.classList.contains('open');
    document.querySelectorAll('.bk-faq-item').forEach(function(el){ el.classList.remove('open'); });
    if(!isOpen) item.classList.add('open');
}

/* Inquiry type selector */
function selectType(btn){
    document.querySelectorAll('.bk-type-btn').forEach(function(b){ b.classList.remove('selected'); });
    btn.classList.add('selected');
    document.getElementById('inquiryTypeVal').value = btn.textContent.trim();
}
</script>
</div>{{-- .body --}}
</body>
</html>
