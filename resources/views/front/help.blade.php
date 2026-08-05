@php
    $isAr = app()->getLocale() === 'ar';
    // Bilingual FAQ — grouped. q/a per locale.
    $groups = [
        [
            'title' => $isAr ? 'الحجز' : 'Booking',
            'icon'  => 'calendar-check',
            'items' => [
                [$isAr ? 'كيف أحجز موعداً؟' : 'How do I book an appointment?',
                 $isAr ? 'اختر المكان، ثم الخدمة أو الخدمات، ثم الوقت المناسب، وأكّد الحجز. ستحتاج فقط لرقم هاتفك للتأكيد عبر رمز يصلك.' : 'Pick a venue, choose your service(s), select a time, and confirm. You only need your phone number, verified with a one-time code.'],
                [$isAr ? 'هل أحتاج حساباً؟' : 'Do I need an account?',
                 $isAr ? 'يكفي رقم هاتفك — ننشئ حسابك تلقائياً عند أول حجز، بدون كلمات مرور.' : 'Just your phone number — we create your account automatically on your first booking, with no passwords.'],
                [$isAr ? 'هل يمكنني حجز عدة خدمات أو لأكثر من شخص؟' : 'Can I book several services or for more than one person?',
                 $isAr ? 'نعم، يمكنك اختيار عدة خدمات في زيارة واحدة، وإضافة ضيوف لكل منهم خدماته في الوقت نفسه.' : 'Yes — you can pick several services in one visit, and add guests each with their own services at the same time.'],
            ],
        ],
        [
            'title' => $isAr ? 'المواعيد والإلغاء' : 'Appointments & cancellation',
            'icon'  => 'clock',
            'items' => [
                [$isAr ? 'أين أرى مواعيدي؟' : 'Where can I see my appointments?',
                 $isAr ? 'من قائمة الحساب أعلى الصفحة اختر «مواعيدي» لعرض القادمة والسابقة.' : 'Open the account menu at the top and choose “My appointments” to see upcoming and past visits.'],
                [$isAr ? 'كيف ألغي موعداً؟' : 'How do I cancel an appointment?',
                 $isAr ? 'افتح تفاصيل الموعد من «مواعيدي» واضغط «إلغاء الموعد». نرجو الإلغاء مبكراً احتراماً لوقت المكان.' : 'Open the appointment from “My appointments” and tap “Cancel appointment”. Please cancel early out of respect for the venue’s time.'],
                [$isAr ? 'هل يمكنني إعادة الحجز بسرعة؟' : 'Can I quickly rebook?',
                 $isAr ? 'نعم، من صفحة تفاصيل الموعد اضغط «احجز مجدداً» للعودة مباشرة إلى المكان.' : 'Yes — from an appointment’s details tap “Book again” to jump straight back to the venue.'],
            ],
        ],
        [
            'title' => $isAr ? 'الدفع والأسعار' : 'Payment & pricing',
            'icon'  => 'tag',
            'items' => [
                [$isAr ? 'كيف أدفع؟' : 'How do I pay?',
                 $isAr ? 'يتم الدفع عادةً في مقرّ المكان وفق أسعاره المعلنة. الأسعار الظاهرة تبدأ من أقل سعر للخدمة.' : 'Payment is normally made at the venue according to its listed prices. Prices shown start from the lowest service price.'],
                [$isAr ? 'هل الأسعار نهائية؟' : 'Are prices final?',
                 $isAr ? 'قد تختلف قليلاً حسب الخدمة الفعلية التي تختارها في المكان.' : 'They may vary slightly based on the actual service you choose at the venue.'],
            ],
        ],
        [
            'title' => $isAr ? 'التقييمات والمفضلة' : 'Reviews & favourites',
            'icon'  => 'star',
            'items' => [
                [$isAr ? 'كيف أترك تقييماً؟' : 'How do I leave a review?',
                 $isAr ? 'بعد اكتمال موعدك، افتح تفاصيله وقيّم زيارتك بالنجوم مع تعليق اختياري.' : 'After your visit is completed, open its details and rate it with stars and an optional comment.'],
                [$isAr ? 'كيف أحفظ مكاناً في المفضلة؟' : 'How do I save a venue to favourites?',
                 $isAr ? 'اضغط على أيقونة القلب على أي بطاقة مكان. تجد كل ما حفظته في «المفضلة» بحسابك.' : 'Tap the heart icon on any venue card. Everything you save appears under “Favourites” in your account.'],
            ],
        ],
    ];
@endphp
<x-front.layout :title="($isAr ? 'مركز المساعدة' : 'Help Center') . ' | GlowRez'" :mapFab="false"
    :description="$isAr ? 'أسئلة شائعة وإرشادات حول الحجز والمواعيد وإدارة حسابك على GlowRez.' : 'FAQs and guides about booking, appointments, and managing your GlowRez account.'">
<x-slot:head>
@php($faqLd = $isAr
    ? [['q'=>'كيف أحجز موعداً على GlowRez؟','a'=>'ابحث عن المكان المناسب، اختر الخدمة والوقت، ثم أكّد الحجز عبر رقم هاتفك في ثوانٍ.'],['q'=>'هل استخدام GlowRez مجاني للعملاء؟','a'=>'نعم، الحجز عبر GlowRez مجاني تماماً للعملاء.'],['q'=>'هل يمكنني إلغاء أو تعديل موعدي؟','a'=>'نعم، يمكنك إدارة مواعيدك وإلغاءها من صفحة «مواعيدي» في حسابك.']]
    : [['q'=>'How do I book an appointment on GlowRez?','a'=>'Find a venue, pick a service and time, then confirm with your phone number in seconds.'],['q'=>'Is GlowRez free for customers?','a'=>'Yes, booking through GlowRez is completely free for customers.'],['q'=>'Can I cancel or change my appointment?','a'=>'Yes, you can manage and cancel your appointments from the “My Appointments” page in your account.']])
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => collect($faqLd)->map(fn($f) => [
        '@type' => 'Question', 'name' => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ])->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</x-slot:head>
<x-slot:styles>
<style>
.bkf-help{ padding:calc(var(--bk-nav-h) + var(--bk-s10)) 0 var(--bk-s20); }
.bkf-help-wrap{ max-width:780px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-help-hero{ text-align:center; margin-bottom:var(--bk-s10); }
.bkf-help-hero .eyebrow{ font-size:var(--bk-eyebrow); font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); margin-bottom:12px; }
.bkf-help-hero h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h1); color:var(--bk-text); margin:0 0 10px; }
.bkf-help-hero p{ color:var(--bk-text-muted); font-size:var(--bk-fs-lead); margin:0; }
.bkf-help-group{ margin-bottom:var(--bk-s10); }
.bkf-help-group-title{ display:flex; align-items:center; gap:10px; font-size:1.15rem; font-weight:700; color:var(--bk-text); margin:0 0 var(--bk-s4); }
.bkf-help-group-title svg{ color:var(--bk-accent); }
.bkf-faq{ border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); overflow:hidden; background:var(--bk-surface); }
.bkf-faq details{ border-top:1px solid var(--bk-border); }
.bkf-faq details:first-child{ border-top:none; }
.bkf-faq summary{ display:flex; align-items:center; justify-content:space-between; gap:14px; cursor:pointer; list-style:none; padding:16px 18px; min-height:56px; font-weight:600; font-size:.98rem; color:var(--bk-text); }
.bkf-faq summary::-webkit-details-marker{ display:none; }
.bkf-faq summary .chev{ flex:0 0 auto; color:var(--bk-text-muted); transition:transform var(--bk-t) var(--bk-spring); }
.bkf-faq details[open] summary .chev{ transform:rotate(180deg); }
.bkf-faq details[open] summary{ color:var(--bk-accent); }
.bkf-faq .ans{ padding:0 18px 18px; color:var(--bk-text-soft); font-size:.94rem; line-height:1.8; }
.bkf-help-cta{ margin-top:var(--bk-s12); text-align:center; padding:var(--bk-s10) var(--bk-s6); border-radius:var(--bk-r-xl); background:var(--bk-accent-wash); }
.bkf-help-cta h3{ font-size:1.2rem; color:var(--bk-text); margin:0 0 6px; }
.bkf-help-cta p{ color:var(--bk-text-muted); margin:0 auto var(--bk-s5); max-width:400px; line-height:1.7; }
</style>
</x-slot:styles>

<section class="bkf-help">
  <div class="bkf-help-wrap">
    <div class="bkf-help-hero">
      <div class="eyebrow">{{ $isAr ? 'مركز المساعدة' : 'Help Center' }}</div>
      <h1>{{ $isAr ? 'كيف يمكننا مساعدتك؟' : 'How can we help?' }}</h1>
      <p>{{ $isAr ? 'إجابات سريعة لأكثر الأسئلة شيوعاً.' : 'Quick answers to the most common questions.' }}</p>
    </div>

    @foreach($groups as $g)
      <div class="bkf-help-group">
        <div class="bkf-help-group-title"><x-icon name="{{ $g['icon'] }}" :size="20"/>{{ $g['title'] }}</div>
        <div class="bkf-faq">
          @foreach($g['items'] as $qa)
            <details>
              <summary>{{ $qa[0] }}<x-icon name="chevron-down" :size="18" class="chev"/></summary>
              <div class="ans">{{ $qa[1] }}</div>
            </details>
          @endforeach
        </div>
      </div>
    @endforeach

    <div class="bkf-help-cta">
      <h3>{{ $isAr ? 'لم تجد ما تبحث عنه؟' : 'Didn’t find what you need?' }}</h3>
      <p>{{ $isAr ? 'فريقنا سعيد بمساعدتك في أي وقت.' : 'Our team is happy to help you anytime.' }}</p>
      <a href="{{ route('front.contact') }}" class="bkf-btn bkf-btn-primary">{{ $isAr ? 'تواصل معنا' : 'Contact us' }}<x-icon name="arrow-right" :size="18"/></a>
    </div>
  </div>
</section>
</x-front.layout>
