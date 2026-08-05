@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-front.layout :title="($isAr ? 'من نحن' : 'About Us') . ' | GlowRez'" :mapFab="false"
    :description="$isAr ? 'تعرّف على GlowRez — منصة حجز وإدارة أماكن الجمال والعناية في سوريا، ورؤيتنا لتبسيط الحجز للعملاء وأصحاب الأعمال.' : 'Learn about GlowRez — the beauty & wellness booking & management platform in Syria, and our mission to simplify booking for clients and businesses.'">
<x-slot:styles>
<style>
.bkf-ab{ overflow:hidden; }
.bkf-ab-wrap{ max-width:var(--bk-container); margin-inline:auto; padding-inline:var(--bk-gutter); }

/* Hero */
.bkf-ab-hero{ padding:calc(var(--bk-nav-h) + var(--bk-s12)) 0 var(--bk-s12); text-align:center; position:relative; }
.bkf-ab-hero::before{ content:''; position:absolute; inset:0; background:var(--bk-grad-hero); pointer-events:none; z-index:-1; }
.bkf-ab-hero .eyebrow{ font-size:var(--bk-eyebrow); font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); margin-bottom:14px; }
.bkf-ab-hero h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-display); line-height:1.05; color:var(--bk-text); margin:0 0 18px; text-wrap:balance; }
.bkf-ab-hero h1 span{ color:var(--bk-accent); }
.bkf-ab-hero p{ max-width:600px; margin:0 auto var(--bk-s8); color:var(--bk-text-soft); font-size:var(--bk-fs-lead); line-height:1.8; }
.bkf-ab-hero .cta{ display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }

/* Section shell */
.bkf-ab-sec{ padding:var(--bk-s16) 0; }
.bkf-ab-sec.alt{ background:var(--bk-surface-2); }
.bkf-ab-sechead{ text-align:center; max-width:560px; margin:0 auto var(--bk-s10); }
.bkf-ab-sechead .eyebrow{ font-size:var(--bk-eyebrow); font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); margin-bottom:10px; }
.bkf-ab-sechead h2{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h2); color:var(--bk-text); margin:0; }
.bkf-ab-sechead h2 span{ color:var(--bk-accent); }

/* Mission / vision / values */
.bkf-ab-mv{ display:grid; grid-template-columns:repeat(3,1fr); gap:var(--bk-s5); }
@media (max-width:820px){ .bkf-ab-mv{ grid-template-columns:1fr; } }
.bkf-ab-card{ background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r-xl); padding:var(--bk-s8); box-shadow:var(--bk-shadow-xs); transition:transform var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.bkf-ab-card:hover{ transform:translateY(-4px); box-shadow:var(--bk-shadow); }
.bkf-ab-card .ic{ width:52px; height:52px; border-radius:var(--bk-r); display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); margin-bottom:16px; }
.bkf-ab-card h3{ font-size:1.15rem; color:var(--bk-text); margin:0 0 8px; }
.bkf-ab-card p{ color:var(--bk-text-soft); font-size:.94rem; line-height:1.8; margin:0; }

/* Why grid */
.bkf-ab-why{ display:grid; grid-template-columns:repeat(3,1fr); gap:var(--bk-s5); }
@media (max-width:900px){ .bkf-ab-why{ grid-template-columns:1fr 1fr; } }
@media (max-width:560px){ .bkf-ab-why{ grid-template-columns:1fr; } }
.bkf-ab-feat{ display:flex; gap:14px; align-items:flex-start; padding:18px; border-radius:var(--bk-r-lg); background:var(--bk-surface); border:1px solid var(--bk-border); }
.bkf-ab-feat .ic{ flex:0 0 auto; width:44px; height:44px; border-radius:var(--bk-r); display:grid; place-items:center; background:var(--bk-gold-soft); color:var(--bk-gold-strong); }
.bkf-ab-feat h4{ font-size:1rem; color:var(--bk-text); margin:0 0 4px; }
.bkf-ab-feat p{ font-size:.88rem; color:var(--bk-text-muted); line-height:1.7; margin:0; }

/* CTA */
.bkf-ab-cta{ text-align:center; padding:var(--bk-s16) var(--bk-gutter); }
.bkf-ab-cta-inner{ max-width:640px; margin:0 auto; padding:var(--bk-s12) var(--bk-s8); border-radius:var(--bk-r-2xl); background:var(--bk-grad-accent); color:#fff; }
.bkf-ab-cta-inner h2{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h2); margin:0 0 10px; color:#fff; }
.bkf-ab-cta-inner p{ opacity:.9; margin:0 0 var(--bk-s6); font-size:1.02rem; }
</style>
</x-slot:styles>

<div class="bkf-ab">
  {{-- Hero --}}
  <section class="bkf-ab-hero">
    <div class="bkf-ab-wrap bkf-reveal">
      <div class="eyebrow">{{ $isAr ? 'تعرّف علينا' : 'Get to know us' }}</div>
      <h1>{{ $isAr ? 'نُبسّط حجز' : 'We make booking' }} <span>{{ $isAr ? 'الجمال والعناية' : 'beauty & wellness' }}</span> {{ $isAr ? '' : 'effortless' }}</h1>
      <p>{{ $isAr
          ? 'بوكسي منصّة تربط عملاء الجمال والعناية بأفضل الأماكن والمراكز — نؤمن أن الحجز يجب أن يكون سهلاً وسريعاً وممتعاً.'
          : 'Booksy connects beauty & wellness customers with the best venues — we believe booking should be easy, fast, and enjoyable.' }}</p>
      <div class="cta">
        <a href="{{ route('front.venues') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg">{{ $isAr ? 'استكشف الأماكن' : 'Explore venues' }}<x-icon name="arrow-right" :size="18"/></a>
        <a href="{{ route('front.contact') }}" class="bkf-btn bkf-btn-ghost bkf-btn-lg">{{ $isAr ? 'تواصل معنا' : 'Contact us' }}</a>
      </div>
    </div>
  </section>

  {{-- Mission / Vision / Values --}}
  <section class="bkf-ab-sec alt">
    <div class="bkf-ab-wrap">
      <div class="bkf-ab-sechead bkf-reveal">
        <div class="eyebrow">{{ $isAr ? 'هويتنا' : 'Our identity' }}</div>
        <h2>{{ $isAr ? 'رسالتنا و' : 'Mission & ' }}<span>{{ $isAr ? 'رؤيتنا' : 'Vision' }}</span></h2>
      </div>
      <div class="bkf-ab-mv">
        <div class="bkf-ab-card bkf-reveal">
          <div class="ic"><x-icon name="award" :size="26"/></div>
          <h3>{{ $isAr ? 'رسالتنا' : 'Our mission' }}</h3>
          <p>{{ $isAr ? 'تبسيط حجز المواعيد في قطاع الجمال والعناية عبر منصّة موثوقة تربط العملاء بمزوّدي الخدمات بسهولة وشفافية.' : 'Simplify appointment booking in beauty & wellness through a trusted platform that connects customers with providers easily and transparently.' }}</p>
        </div>
        <div class="bkf-ab-card bkf-reveal">
          <div class="ic"><x-icon name="sparkles" :size="26"/></div>
          <h3>{{ $isAr ? 'رؤيتنا' : 'Our vision' }}</h3>
          <p>{{ $isAr ? 'أن نكون المنصّة الرائدة لحجوزات الجمال والعناية، ونغيّر طريقة تعامل الناس مع خدمات العناية الشخصية.' : 'To be the leading platform for beauty & wellness bookings, changing how people engage with personal-care services.' }}</p>
        </div>
        <div class="bkf-ab-card bkf-reveal">
          <div class="ic"><x-icon name="heart" :size="26"/></div>
          <h3>{{ $isAr ? 'قيمنا' : 'Our values' }}</h3>
          <p>{{ $isAr ? 'الثقة، الشفافية، الابتكار المستمر، ووضع العميل أولاً — مع تعاون وثيق مع شركائنا من أصحاب الأعمال.' : 'Trust, transparency, constant innovation, and putting the customer first — with close collaboration with our business partners.' }}</p>
        </div>
      </div>
    </div>
  </section>

  {{-- Why Booksy --}}
  <section class="bkf-ab-sec">
    <div class="bkf-ab-wrap">
      <div class="bkf-ab-sechead bkf-reveal">
        <div class="eyebrow">{{ $isAr ? 'ما يميّزنا' : 'What sets us apart' }}</div>
        <h2>{{ $isAr ? 'لماذا ' : 'Why ' }}<span>{{ $isAr ? 'بوكسي؟' : 'Booksy?' }}</span></h2>
      </div>
      @php
        $feats = [
          ['shield', $isAr?'أمان وموثوقية':'Verified & trusted', $isAr?'جميع الأماكن موثّقة قبل الإدراج.':'All venues are verified before listing.'],
          ['zap', $isAr?'حجز فوري':'Instant booking', $isAr?'احجز في ثوانٍ بدون مكالمات أو انتظار.':'Book in seconds — no calls, no waiting.'],
          ['star', $isAr?'تقييمات حقيقية':'Real reviews', $isAr?'آراء عملاء حقيقيين تساعدك على الاختيار.':'Real customer reviews to help you choose.'],
          ['bell', $isAr?'تذكيرات تلقائية':'Auto reminders', $isAr?'تذكير بموعدك حتى لا تنسى أي حجز.':'Reminders so you never miss an appointment.'],
          ['heart', $isAr?'المفضلة':'Save favourites', $isAr?'احفظ أماكنك المفضّلة واحجز منها بسرعة.':'Save the venues you love and rebook fast.'],
          ['globe', $isAr?'عربي وإنجليزي':'Arabic & English', $isAr?'المنصّة تدعم اللغتين بالكامل.':'The platform fully supports both languages.'],
        ];
      @endphp
      <div class="bkf-ab-why">
        @foreach($feats as $f)
          <div class="bkf-ab-feat bkf-reveal">
            <div class="ic"><x-icon name="{{ $f[0] }}" :size="22"/></div>
            <div><h4>{{ $f[1] }}</h4><p>{{ $f[2] }}</p></div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- CTA --}}
  <section class="bkf-ab-cta">
    <div class="bkf-ab-cta-inner bkf-reveal">
      <h2>{{ $isAr ? 'جاهز لحجز موعدك؟' : 'Ready to book?' }}</h2>
      <p>{{ $isAr ? 'اكتشف أفضل أماكن الجمال والعناية قربك واحجز في ثوانٍ.' : 'Discover the best beauty & wellness venues near you and book in seconds.' }}</p>
      <a href="{{ route('front.venues') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg">{{ $isAr ? 'ابدأ الآن' : 'Get started' }}<x-icon name="arrow-right" :size="18"/></a>
    </div>
  </section>
</div>
</x-front.layout>
