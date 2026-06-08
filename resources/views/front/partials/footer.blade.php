
<footer id="footer" class="booksy-footer" style="padding:60px 0 0;">
    <div class="container">
        <div class="row g-5">

            {{-- Brand + description --}}
            <div class="col-lg-4">
                <span class="footer-brand">Booksy<span>.</span></span>
                <div class="divider-gold mt-2 mb-3"></div>
                <p>
                    {{ $isAr
                        ? 'بوكسي هي منصتك الأولى لحجز مواعيد صالونات التجميل، مراكز السبا، والعيادات التجميلية بسهولة وسرعة.'
                        : 'Booksy is your go-to platform for booking beauty salons, spas & aesthetic clinics instantly.' }}
                </p>
                <ul class="social-icons social-icons-clean social-icons-icon-light mt-3">
                    <li class="social-icons-instagram"><a href="#" title="Instagram"><i class="fab fa-instagram"></i></a></li>
                    <li class="social-icons-twitter mx-2"><a href="#" title="Twitter/X"><i class="fab fa-twitter"></i></a></li>
                    <li class="social-icons-facebook"><a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                    <li class="social-icons-tiktok mx-2"><a href="#" title="TikTok"><i class="fab fa-tiktok"></i></a></li>
                    <li class="social-icons-snapchat"><a href="#" title="Snapchat"><i class="fab fa-snapchat-ghost"></i></a></li>
                </ul>
            </div>

            {{-- Quick links --}}
            <div class="col-6 col-lg-2">
                <h6>{{ $isAr ? 'روابط سريعة' : 'Quick Links' }}</h6>
                <ul>
                    <li><a href="{{ route('front.index') }}">{{ $isAr ? 'الرئيسية' : 'Home' }}</a></li>
                    <li><a href="#categories-section">{{ $isAr ? 'التصنيفات' : 'Categories' }}</a></li>
                    <li><a href="#companies-section">{{ $isAr ? 'الأماكن' : 'Places' }}</a></li>
                    <li><a href="#services-section">{{ $isAr ? 'الخدمات' : 'Services' }}</a></li>
                    <li><a href="#how-section">{{ $isAr ? 'كيف يعمل' : 'How It Works' }}</a></li>
                </ul>
            </div>

            {{-- For businesses --}}
            <div class="col-6 col-lg-3">
                <h6>{{ $isAr ? 'للأعمال' : 'For Businesses' }}</h6>
                <ul>
                    <li><a href="{{ route('company.register') }}">{{ $isAr ? 'تسجيل نشاطك' : 'Register Business' }}</a></li>
                    <li><a href="{{ route('company.login') }}">{{ $isAr ? 'لوحة التحكم' : 'Business Dashboard' }}</a></li>
                    <li><a href="{{ route('owner.login') }}">{{ $isAr ? 'دخول الإدارة' : 'Admin Login' }}</a></li>
                </ul>
                <h6 class="mt-4">{{ $isAr ? 'اللغة' : 'Language' }}</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('locale.switch', 'ar') }}" class="btn-lang-switch"
                       style="{{ $isAr ? 'background:#C9A227; color:#1a1a2e;' : '' }}">عربي</a>
                    <a href="{{ route('locale.switch', 'en') }}" class="btn-lang-switch"
                       style="{{ !$isAr ? 'background:#C9A227; color:#1a1a2e;' : '' }}">EN</a>
                </div>
            </div>

            {{-- Contact --}}
            <div class="col-lg-3">
                <h6>{{ $isAr ? 'تواصل معنا' : 'Contact Us' }}</h6>
                <ul>
                    <li>
                        <a href="mailto:info@booksy.app">
                            <i class="far fa-envelope {{ $isAr ? 'ms-2' : 'me-2' }}" style="color:#C9A227;"></i>
                            info@booksy.app
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fab fa-whatsapp {{ $isAr ? 'ms-2' : 'me-2' }}" style="color:#C9A227;"></i>
                            WhatsApp
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    {{-- Copyright --}}
    <div class="footer-copyright" style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.07); padding:20px 0;">
        <div class="container">
            <p class="copy">
                &copy; {{ date('Y') }} Booksy &mdash;
                {{ $isAr ? 'جميع الحقوق محفوظة' : 'All Rights Reserved' }}
            </p>
        </div>
    </div>
</footer>