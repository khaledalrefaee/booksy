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
.bkf-help{ padding:calc(var(--bk-nav-h) + var(--bk-s10)) 0 var(--bk-s24); }
.bkf-help-head, .bkf-help-grid{ max-width:1080px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-help-head{ padding-bottom:var(--bk-s8); margin-bottom:var(--bk-s10); border-bottom:1px solid var(--bk-border); }
.bkf-help-head h1{ font-family:var(--bk-font-display); font-weight:600; font-size:var(--bk-fs-h1); line-height:1.08; letter-spacing:-.01em; color:var(--bk-text); margin:0; text-wrap:balance; }
html[lang="ar"] .bkf-help-head h1{ font-weight:800; letter-spacing:0; line-height:1.24; }
.bkf-help-head p{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-lead); line-height:1.6; color:var(--bk-text-soft); max-width:56ch; margin:16px 0 0; text-wrap:pretty; }

.bkf-help-search{ position:relative; margin-top:var(--bk-s6); max-width:560px; }
.bkf-help-search svg{ position:absolute; inset-inline-start:16px; top:50%; transform:translateY(-50%); color:var(--bk-text-muted); pointer-events:none; }
.bkf-help-search input{ width:100%; min-height:52px; padding:0 18px; padding-inline-start:48px; font-family:var(--bk-font-ui); font-size:1rem;
  color:var(--bk-text); background:var(--bk-surface); border:1px solid var(--bk-border-strong); border-radius:var(--bk-r-pill);
  transition:border-color var(--bk-t-fast) var(--bk-ease), box-shadow var(--bk-t-fast) var(--bk-ease); }
.bkf-help-search input::placeholder{ color:var(--bk-text-muted); }
.bkf-help-search input:focus{ outline:none; border-color:var(--bk-accent); box-shadow:0 0 0 3px color-mix(in srgb,var(--bk-accent) 22%,transparent); }

.bkf-help-grid{ display:grid; grid-template-columns:1fr; gap:var(--bk-s8); }
@media (min-width:960px){ .bkf-help-grid{ grid-template-columns:210px minmax(0,760px); gap:var(--bk-s16); justify-content:center; align-items:start; } }
.bkf-help-rail{ display:flex; flex-wrap:wrap; gap:8px; }
.bkf-help-rail a{ display:inline-flex; align-items:center; min-height:44px; padding:0 16px; border:1px solid var(--bk-border); border-radius:var(--bk-r-pill);
  background:var(--bk-surface); color:var(--bk-text-soft); font-family:var(--bk-font-ui); font-size:.92rem; font-weight:600; text-decoration:none;
  transition:color var(--bk-t-fast) var(--bk-ease), border-color var(--bk-t-fast) var(--bk-ease), background-color var(--bk-t-fast) var(--bk-ease); }
.bkf-help-rail a:hover{ color:var(--bk-accent-strong); border-color:var(--bk-accent); background:var(--bk-accent-wash); }
.bkf-help-rail a:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; }
@media (min-width:960px){
  .bkf-help-rail{ position:sticky; top:calc(var(--bk-nav-h) + var(--bk-s6)); flex-direction:column; flex-wrap:nowrap; gap:0; border-inline-start:1px solid var(--bk-border); }
  .bkf-help-rail a{ min-height:0; padding:9px 15px; margin-inline-start:-1px; border:0; border-radius:0; background:none; font-weight:500; color:var(--bk-text-muted); }
  .bkf-help-rail a:hover{ background:var(--bk-accent-wash); border-color:transparent; }
}

.bkf-help-group{ margin-bottom:var(--bk-s10); scroll-margin-top:calc(var(--bk-nav-h) + 24px); }
.bkf-help-group-title{ display:flex; align-items:center; gap:10px; font-family:var(--bk-font-ui); font-size:1.3rem; font-weight:700; color:var(--bk-text); margin:0 0 var(--bk-s4); }
html[lang="ar"] .bkf-help-group-title{ font-weight:800; font-size:1.4rem; }
.bkf-help-group-title svg{ color:var(--bk-accent); }
.bkf-faq{ border:1px solid var(--bk-border); border-radius:var(--bk-r); overflow:hidden; background:var(--bk-surface); }
.bkf-faq details{ border-top:1px solid var(--bk-border); }
.bkf-faq details:first-child{ border-top:none; }
.bkf-faq summary{ display:flex; align-items:center; justify-content:space-between; gap:14px; cursor:pointer; list-style:none; padding:16px 20px; min-height:56px;
  font-family:var(--bk-font-ui); font-weight:600; font-size:1rem; line-height:1.5; color:var(--bk-text); transition:background-color var(--bk-t-fast) var(--bk-ease); }
.bkf-faq summary:hover{ background:var(--bk-surface-2); }
.bkf-faq summary:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:-2px; }
.bkf-faq summary::-webkit-details-marker{ display:none; }
.bkf-faq summary .chev{ flex:0 0 auto; color:var(--bk-text-muted); transition:transform var(--bk-t) var(--bk-ease); }
.bkf-faq details[open] summary .chev{ transform:rotate(180deg); }
.bkf-faq details[open] summary{ color:var(--bk-accent-strong); }
.bkf-faq .ans{ padding:0 20px 20px; max-width:66ch; color:var(--bk-text-soft); font-size:1rem; line-height:1.85; text-wrap:pretty; }
.bkf-help-empty{ padding:var(--bk-s10) var(--bk-s6); text-align:center; color:var(--bk-text-muted); border:1px dashed var(--bk-border-strong); border-radius:var(--bk-r); }
.bkf-help-empty[hidden]{ display:none; }
.bkf-help-empty b{ display:block; color:var(--bk-text); font-size:1.05rem; margin-bottom:4px; }

.bkf-help-cta{ margin-top:var(--bk-s12); padding:var(--bk-s8); border-radius:var(--bk-r-lg); background:var(--bk-accent-wash); border:1px solid var(--bk-accent-soft);
  display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:var(--bk-s5); }
.bkf-help-cta h3{ font-family:var(--bk-font-ui); font-size:1.25rem; font-weight:700; color:var(--bk-text); margin:0 0 4px; }
.bkf-help-cta p{ color:var(--bk-text-soft); margin:0; line-height:1.7; max-width:44ch; }
.bkf-help ::selection{ background:var(--bk-accent-soft); color:var(--bk-text); }
html{ scroll-behavior:smooth; }
@media (prefers-reduced-motion:reduce){ html{ scroll-behavior:auto; } .bkf-faq summary .chev{ transition:none; } }
</style>
</x-slot:styles>

<section class="bkf-help">
  <div class="bkf-help-head">
    <h1>{{ $isAr ? 'كيف يمكننا مساعدتك؟' : 'How can we help?' }}</h1>
    <p>{{ $isAr ? 'إجابات سريعة لأكثر الأسئلة شيوعاً حول الحجز والمواعيد وحسابك.' : 'Quick answers about booking, appointments and your account.' }}</p>
    <div class="bkf-help-search">
      <x-icon name="search" :size="18"/>
      <input type="search" id="helpSearch" autocomplete="off" placeholder="{{ $isAr ? 'ابحث في الأسئلة…' : 'Search the questions…' }}" aria-label="{{ $isAr ? 'ابحث في الأسئلة' : 'Search the questions' }}">
    </div>
  </div>

  <div class="bkf-help-grid">
    <nav class="bkf-help-rail" aria-label="{{ $isAr ? 'أقسام المساعدة' : 'Help topics' }}">
      @foreach($groups as $i => $g)
        <a href="#g{{ $i }}">{{ $g['title'] }}</a>
      @endforeach
    </nav>

    <div id="helpList">
      @foreach($groups as $i => $g)
        <div class="bkf-help-group" id="g{{ $i }}" data-group>
          <h2 class="bkf-help-group-title"><x-icon name="{{ $g['icon'] }}" :size="20"/>{{ $g['title'] }}</h2>
          <div class="bkf-faq">
            @foreach($g['items'] as $qa)
              <details data-faq>
                <summary>{{ $qa[0] }}<x-icon name="chevron-down" :size="18" class="chev"/></summary>
                <div class="ans">{{ $qa[1] }}</div>
              </details>
            @endforeach
          </div>
        </div>
      @endforeach

      <div class="bkf-help-empty" id="helpEmpty" hidden>
        <b>{{ $isAr ? 'لا توجد نتائج مطابقة' : 'No matching questions' }}</b>
        {{ $isAr ? 'جرّب كلمات أبسط، أو تواصل معنا مباشرة.' : 'Try simpler words, or contact us directly.' }}
      </div>

      <div class="bkf-help-cta">
        <div>
          <h3>{{ $isAr ? 'لم تجد ما تبحث عنه؟' : 'Didn’t find what you need?' }}</h3>
          <p>{{ $isAr ? 'فريقنا سعيد بمساعدتك في أي وقت.' : 'Our team is happy to help you anytime.' }}</p>
        </div>
        <a href="{{ route('front.contact') }}" class="bkf-btn bkf-btn-primary">{{ $isAr ? 'تواصل معنا' : 'Contact us' }}<x-icon name="arrow-right" :size="18"/></a>
      </div>
    </div>
  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var input = document.getElementById('helpSearch');
  if(!input) return;
  var items = [].slice.call(document.querySelectorAll('[data-faq]'));
  var groups = [].slice.call(document.querySelectorAll('[data-group]'));
  var empty = document.getElementById('helpEmpty');
  var norm = function(t){ return (t||'').toLowerCase().replace(/[ً-ٟ]/g,'').replace(/[إأآ]/g,'ا').replace(/ة/g,'ه').replace(/ى/g,'ي'); };
  input.addEventListener('input', function(){
    var q = norm(input.value.trim());
    items.forEach(function(d){
      var hit = !q || norm(d.textContent).indexOf(q) > -1;
      d.hidden = !hit;
      if(q && hit) d.setAttribute('open',''); else d.removeAttribute('open');
    });
    var any = false;
    groups.forEach(function(g){
      var vis = g.querySelector('[data-faq]:not([hidden])');
      g.hidden = !vis; if(vis) any = true;
    });
    empty.hidden = any;
  });
})();
</script>
</x-slot:scripts>
</x-front.layout>
