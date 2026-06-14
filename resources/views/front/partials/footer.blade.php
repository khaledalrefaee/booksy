<style>
#booksy-footer{
    position:relative;
    background: linear-gradient(160deg, #0a0a0a 0%, #111008 40%, #1a1400 70%, #0d0a00 100%);
    overflow:hidden;
    padding: 80px 0 0;
    color:rgba(255,255,255,.55);
    font-size:.85rem;
}

/* Gold glow top border */
#booksy-footer::before{
    content:'';
    position:absolute; top:0; left:0; right:0; height:1px;
    background: linear-gradient(90deg, transparent, #C9A227 30%, #f5d06a 50%, #C9A227 70%, transparent);
}

/* Radial gold ambient */
#booksy-footer::after{
    content:'';
    position:absolute; top:-200px; left:50%; transform:translateX(-50%);
    width:800px; height:500px; border-radius:50%;
    background: radial-gradient(ellipse at center, rgba(201,162,39,.07) 0%, transparent 65%);
    pointer-events:none;
}

/* Decorative corner rings */
.ft-ring{
    position:absolute; border-radius:50%;
    border:1px solid rgba(201,162,39,.08);
    pointer-events:none;
}

.ft-brand{
    font-size:1.9rem; font-weight:900; letter-spacing:-1.5px;
    color:#fff; line-height:1;
    background: linear-gradient(135deg, #f5d06a 0%, #C9A227 60%, #8a6a0a 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text;
}
.ft-brand span{ opacity:.6; }

.ft-divider{
    width:48px; height:2px; margin:14px 0 18px;
    background: linear-gradient(90deg, #C9A227, transparent);
    border-radius:2px;
}

.ft-desc{ line-height:1.8; max-width:280px; }

/* Social */
.ft-social{ display:flex; gap:10px; margin-top:20px; flex-wrap:wrap; }
.ft-social a{
    width:36px; height:36px; border-radius:50%;
    border:1px solid rgba(201,162,39,.25);
    display:flex; align-items:center; justify-content:center;
    color:rgba(255,255,255,.5); font-size:.8rem;
    transition: all .3s;
    background: rgba(201,162,39,.04);
}
.ft-social a:hover{
    border-color:#C9A227; color:#C9A227;
    background:rgba(201,162,39,.1);
    transform:translateY(-2px);
}

/* Links */
.ft-col h6{
    font-size:.7rem; font-weight:700; letter-spacing:3px;
    text-transform:uppercase; color:#C9A227;
    margin-bottom:18px;
}
.ft-col ul{ list-style:none; padding:0; margin:0; }
.ft-col ul li{ margin-bottom:10px; }
.ft-col ul li a{
    color:rgba(255,255,255,.45); text-decoration:none;
    transition:color .25s, padding-inline-start .25s;
    display:inline-flex; align-items:center; gap:6px;
}
.ft-col ul li a:hover{ color:#C9A227; padding-inline-start:4px; }
.ft-col ul li a i{ font-size:.7rem; color:#C9A227; opacity:.7; }

/* Language switcher */
.ft-lang{ display:flex; gap:8px; margin-top:8px; }
.ft-lang a{
    padding:5px 16px; border-radius:20px; font-size:.75rem; font-weight:600;
    border:1px solid rgba(201,162,39,.3); color:rgba(255,255,255,.5);
    text-decoration:none; transition:all .25s;
}
.ft-lang a.active,
.ft-lang a:hover{
    background: linear-gradient(135deg,#C9A227,#8a6a0a);
    border-color:transparent; color:#0a0a0a;
}

/* App badges */
.ft-badge{
    display:inline-flex; align-items:center; gap:9px;
    padding:9px 18px; border-radius:12px;
    border:1px solid rgba(201,162,39,.2);
    background:rgba(201,162,39,.04);
    color:#fff; text-decoration:none;
    font-size:.78rem; font-weight:600;
    transition:all .3s; margin-bottom:10px;
}
.ft-badge i{ font-size:1.3rem; color:#C9A227; }
.ft-badge:hover{
    border-color:#C9A227; background:rgba(201,162,39,.1);
    transform:translateY(-2px); color:#fff;
}
.ft-badge small{ display:block; font-size:.65rem; color:rgba(255,255,255,.4); font-weight:400; }

/* Copyright bar */
.ft-copy{
    margin-top:60px;
    border-top:1px solid rgba(201,162,39,.08);
    padding:20px 0;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
}
.ft-copy p{ margin:0; font-size:.78rem; color:rgba(255,255,255,.28); }
.ft-copy .ft-copy-links{ display:flex; gap:20px; }
.ft-copy .ft-copy-links a{ color:rgba(255,255,255,.28); text-decoration:none; font-size:.75rem; transition:color .2s; }
.ft-copy .ft-copy-links a:hover{ color:#C9A227; }

@media(max-width:768px){
    .ft-copy{ flex-direction:column; text-align:center; }
    .ft-copy-links{ justify-content:center; }
}
</style>

<footer id="booksy-footer">

    {{-- Decorative rings --}}
    <div class="ft-ring" style="width:600px;height:600px;bottom:-300px;right:-200px;"></div>
    <div class="ft-ring" style="width:300px;height:300px;top:-100px;left:-80px;"></div>
    <div class="ft-ring" style="width:120px;height:120px;top:40px;right:15%;opacity:.5;"></div>

    <div class="container" style="position:relative;z-index:2;">
        <div class="row g-5">

            {{-- Brand --}}
            <div class="col-lg-4">
                <div class="ft-brand">Booksy<span>.</span></div>
                <div class="ft-divider"></div>
                <p class="ft-desc">
                    {{ $isAr
                        ? 'منصتك الأولى لحجز مواعيد صالونات التجميل، مراكز السبا، والعيادات بسهولة وسرعة.'
                        : 'Your go-to platform for booking beauty salons, spas & clinics instantly.' }}
                </p>
                <div class="ft-social">
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Twitter/X"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" title="Snapchat"><i class="fab fa-snapchat-ghost"></i></a>
                </div>
            </div>

            {{-- Quick links --}}
            <div class="col-6 col-lg-2 ft-col">
                <h6>{{ $isAr ? 'روابط سريعة' : 'Quick Links' }}</h6>
                <ul>
                    <li><a href="{{ route('front.index') }}"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                    <li><a href="#v4-cats"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'التصنيفات' : 'Categories' }}</a></li>
                    <li><a href="#v4-featured"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'الأماكن' : 'Places' }}</a></li>
                    <li><a href="{{ route('front.about') }}"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'من نحن' : 'About' }}</a></li>
                    <li><a href="{{ route('front.contact') }}"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'تواصل' : 'Contact' }}</a></li>
                </ul>
            </div>

            {{-- For businesses --}}
            <div class="col-6 col-lg-2 ft-col">
                <h6>{{ $isAr ? 'للأعمال' : 'For Business' }}</h6>
                <ul>
                    <li><a href="{{ route('company.register') }}"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'تسجيل نشاطك' : 'Register' }}</a></li>
                    <li><a href="{{ route('company.login') }}"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}</a></li>
                    <li><a href="{{ route('owner.login') }}"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>{{ $isAr ? 'دخول الإدارة' : 'Admin' }}</a></li>
                </ul>
                <h6 class="mt-4">{{ $isAr ? 'اللغة' : 'Language' }}</h6>
                <div class="ft-lang">
                    <a href="{{ route('locale.switch','ar') }}" class="{{ $isAr ? 'active' : '' }}">عربي</a>
                    <a href="{{ route('locale.switch','en') }}" class="{{ !$isAr ? 'active' : '' }}">EN</a>
                </div>
            </div>

            {{-- App download --}}
            <div class="col-lg-4 ft-col">
                <h6>{{ $isAr ? 'حمّل التطبيق' : 'Download App' }}</h6>
                <a href="#" class="ft-badge d-flex">
                    <i class="fab fa-apple"></i>
                    <div>
                        <small>{{ $isAr ? 'متوفر على' : 'Available on' }}</small>
                        App Store
                    </div>
                </a>
                <a href="#" class="ft-badge d-flex">
                    <i class="fab fa-google-play"></i>
                    <div>
                        <small>{{ $isAr ? 'متوفر على' : 'Get it on' }}</small>
                        Google Play
                    </div>
                </a>
                <div class="mt-3" style="font-size:.78rem;color:rgba(255,255,255,.3);">
                    <i class="far fa-envelope me-2" style="color:#C9A227;"></i>info@booksy.app
                </div>
            </div>

        </div>

        {{-- Copyright --}}
        <div class="ft-copy">
            <p>&copy; {{ date('Y') }} Booksy &mdash; {{ $isAr ? 'جميع الحقوق محفوظة' : 'All Rights Reserved' }}</p>
            <div class="ft-copy-links">
                <a href="#">{{ $isAr ? 'الخصوصية' : 'Privacy' }}</a>
                <a href="#">{{ $isAr ? 'الشروط' : 'Terms' }}</a>
                <a href="#">{{ $isAr ? 'الدعم' : 'Support' }}</a>
            </div>
        </div>

    </div>
</footer>
