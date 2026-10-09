@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn($ar, $en) => $isAr ? $ar : $en;
    $img = fn($f) => asset('magnific/'.$f);

    // Business types we serve (marquee band)
    $bizTypes = [
        ['icon' => 'sparkles', 'ar' => 'مراكز التجميل',        'en' => 'Beauty centres'],
        ['icon' => 'zap',      'ar' => 'مراكز الليزر',         'en' => 'Laser clinics'],
        ['icon' => 'flame',    'ar' => 'سبا ومنتجعات صحية',    'en' => 'Spa & wellness'],
        ['icon' => 'award',    'ar' => 'العناية بالبشرة',      'en' => 'Skincare & facials'],
        ['icon' => 'scissors', 'ar' => 'صالونات الشعر',        'en' => 'Hair salons'],
        ['icon' => 'user',     'ar' => 'صالونات الرجال',       'en' => 'Barbershops'],
        ['icon' => 'heart',    'ar' => 'العناية بالأظافر',     'en' => 'Nail studios'],
        ['icon' => 'star',     'ar' => 'الرموش والحواجب',      'en' => 'Lash & brow'],
    ];

    // Appointment deep-dives (3) — text + collage of real photo + live mock
    $deep = [
        [
            'eyebrow' => $t('التقويم','The calendar'),
            'ar' => 'يومك كله في نظرة واحدة', 'en' => 'Your whole day at a glance',
            'lead_ar' => 'شاشة واحدة تريك كل مواعيد اليوم لكل موظف. اسحب وأفلِت لتعديل الوقت، والنظام يمنع أي تعارض قبل حدوثه.',
            'lead_en' => 'One screen shows every appointment for every staffer. Drag to reschedule — conflicts are blocked before they happen.',
            'pts_ar' => ['عرض يومي وأسبوعي وشهري','جدولة عدة موظفين وكراسٍ في شاشة واحدة','منع الحجز المزدوج تلقائيًا','ألوان لكل خدمة وموظف'],
            'pts_en' => ['Day, week and month views','Multi-staff & multi-chair scheduling in one screen','Automatic double-booking protection','Colour-coded by service & staff'],
            'photo' => 'styling.jpg', 'rev' => false,
            'shot' => 'nails_image_2.png', 'sw' => 513, 'sh' => 1043,
            'alt_ar' => 'معاينة تقويم مواعيد غلوريز على الهاتف', 'alt_en' => 'GlowRez appointment calendar preview on a phone',
        ],
        [
            'eyebrow' => $t('الحجز الذاتي','Self-booking'),
            'ar' => 'يحجزون بأنفسهم، وأنت تعمل', 'en' => 'They book themselves while you work',
            'lead_ar' => 'صفحة حجز أنيقة ورمز QR خاصّان بمنشأتك. يختار العميل الخدمة والموظف والوقت المتاح ويؤكّد — بلا مكالمة واحدة.',
            'lead_en' => 'An elegant booking page and QR code for your business. Clients pick the service, staff and open slot and confirm — with zero phone calls.',
            'pts_ar' => ['حجز ذاتي ٢٤/٧','رابط ورمز QR جاهزان','سجل كل عميل وخدماته المفضّلة','تأكيد فوري للطرفين'],
            'pts_en' => ['24/7 self-booking','Ready link and QR code','Each client’s history & favourite services','Instant confirmation for both'],
            'photo' => 'makeup.jpg', 'rev' => true, 'big' => true,
            'shot' => 'chart_images.png', 'sw' => 1536, 'sh' => 1024,
            'alt_ar' => 'لوحة حجوزات ونمو الأعمال في غلوريز', 'alt_en' => 'GlowRez bookings and business-growth dashboard',
        ],
        [
            'eyebrow' => $t('التذكير','Reminders'),
            'ar' => 'تذكير تلقائي، غياب أقل', 'en' => 'Auto reminders, fewer no-shows',
            'lead_ar' => 'يرسل غلوريز تأكيدًا عند الحجز وتذكيرًا قبل الموعد عبر واتساب — القناة التي يفتحها عملاؤك فعلًا. فتبقى مواعيدك ممتلئة.',
            'lead_en' => 'GlowRez sends a confirmation on booking and a reminder before the appointment over WhatsApp — the channel your clients actually open. So your schedule stays full.',
            'pts_ar' => ['تأكيد فوري عند كل حجز','تذكير قبل الموعد','تعديل أو إلغاء برابط واحد'],
            'pts_en' => ['Instant confirmation on booking','A nudge before the appointment','Reschedule or cancel with one tap'],
            'photo' => 'facial.jpg', 'rev' => false,
            'shot' => 'tips-reviews-recreated@2x.png', 'sw' => 1080, 'sh' => 1080,
            'alt_ar' => 'تذكير تلقائي بالموعد عبر واتساب وتأكيد الحضور في غلوريز', 'alt_en' => 'Automatic WhatsApp appointment reminder and attendance confirmation in GlowRez',
        ],
    ];

    // Coming soon (modules after bookings)
    $soon = [
        ['icon' => 'wallet', 'ar' => 'المالية والأرباح', 'en' => 'Finance & profit', 'dar' => 'دخل ومصاريف وعمولات الموظفين في تقرير واضح.', 'den' => 'Income, expenses and staff commissions in one clear report.'],
        ['icon' => 'shield', 'ar' => 'الصلاحيات والفريق', 'en' => 'Roles & permissions', 'dar' => 'أدوار دقيقة لكل موظف وما يمكنه رؤيته وفعله.', 'den' => 'Granular roles for who sees and does what.'],
        ['icon' => 'box',   'ar' => 'المخزون', 'en' => 'Inventory', 'dar' => 'تتبّع المنتجات والتنبيه قبل نفادها.', 'den' => 'Track products and get low-stock alerts.'],
        ['icon' => 'chart', 'ar' => 'التحليلات', 'en' => 'Analytics', 'dar' => 'أكثر الخدمات ربحًا وأوقات الذروة ونمو الحجوزات.', 'den' => 'Top services, peak hours and booking growth.'],
    ];

    $whySwitch = [
        ['icon' => 'gift',    'ar' => 'مجاني الآن',        'en' => 'Free right now',     'dar' => 'ابدأ اليوم بلا بطاقة وبلا التزام — ندعمك ونحن ننمو معًا.', 'den' => 'Start today with no card and no commitment.'],
        ['icon' => 'globe',   'ar' => 'بالعربية أولًا',    'en' => 'Arabic-first',       'dar' => 'واجهة ودعم بالعربية بالكامل، مصمّمان لطريقة عمل منشأتك.', 'den' => 'A fully Arabic interface and support, built around how your business works.'],
        ['icon' => 'zap',     'ar' => 'إعداد بدقائق',      'en' => 'Ready in minutes',   'dar' => 'أضف خدماتك وموظفيك وابدأ باستقبال الحجوزات اليوم.', 'den' => 'Add your services and staff and start taking bookings today.'],
        ['icon' => 'layers',  'ar' => 'كل شيء بمكان واحد', 'en' => 'All in one place',   'dar' => 'لا تقفز بين خمسة تطبيقات — غلوريز يوحّدها.', 'den' => 'Stop juggling five apps — GlowRez unifies them.'],
    ];

    $testi = [
        ['q_ar' => 'صرت أعرف يومي قبل ما يبدأ، والعملاء يحجزون لحالهم من الرابط. ما عاد في تعارض مواعيد أبدًا.', 'q_en' => 'I know my day before it starts, and clients book themselves from the link. No more clashing appointments.', 'nm' => 'رنا خالد', 'nm_en' => 'Rana Khaled', 'rl_ar' => 'صالون لمسة', 'rl_en' => 'Lamsa Salon', 'in' => 'ر'],
        ['q_ar' => 'التذكير التلقائي بواتساب قلّل الغياب بشكل واضح. الكرسي ما عاد يضلّ فاضي بسبب النسيان.', 'q_en' => 'Automatic WhatsApp reminders visibly cut no-shows. The chair no longer sits empty because someone forgot.', 'nm' => 'أبو محمود', 'nm_en' => 'Abu Mahmoud', 'rl_ar' => 'باربر هاوس للرجال', 'rl_en' => 'Barber House', 'in' => 'م'],
        ['q_ar' => 'جلسات الليزر تحتاج مواعيد دقيقة ومتابعة، وغلوريز نظّم كل شيء. الإعداد أخذ نصف ساعة وبالعربي بالكامل.', 'q_en' => 'Laser sessions need precise scheduling and follow-up, and GlowRez organised all of it. Setup took half an hour, fully in Arabic.', 'nm' => 'سلمى العلي', 'nm_en' => 'Salma Al-Ali', 'rl_ar' => 'مركز غلو للتجميل والليزر', 'rl_en' => 'Glow Beauty & Laser', 'in' => 'س'],
    ];

    $faqs = [
        ['ar' => 'هل هو مجاني فعلًا؟', 'en' => 'Is it really free?', 'aar' => 'نعم، غلوريز مجاني بالكامل الآن بلا بطاقة ولا التزام. نطلق الميزات ميزة تلو الأخرى، ومن ينضمّ مبكرًا يحصل على كل جديد أولًا.', 'aen' => 'Yes — GlowRez is completely free right now, no card and no commitment. We roll features out one at a time, and early businesses get every new one first.'],
        ['ar' => 'هل أحتاج خبرة تقنية؟', 'en' => 'Do I need technical skills?', 'aar' => 'إطلاقًا. إن كنت تستخدم واتساب، تستطيع إدارة مواعيدك على غلوريز. ونساعدك في الإعداد بالكامل.', 'aen' => 'Not at all. If you can use WhatsApp, you can run your bookings on GlowRez — and we help you set everything up.'],
        ['ar' => 'كم يستغرق إعداد منشأتي؟', 'en' => 'How long does setup take?', 'aar' => 'أغلب المنشآت تضيف خدماتها وموظفيها وتبدأ باستقبال الحجوزات خلال أقل من ساعة.', 'aen' => 'Most businesses add their services and staff and start taking bookings in under an hour.'],
        ['ar' => 'هل يمكن للعملاء الحجز بأنفسهم؟', 'en' => 'Can clients book themselves?', 'aar' => 'نعم. تحصل على صفحة حجز عامة ورمز QR يحجز منهما العملاء ٢٤/٧ دون أي مكالمة.', 'aen' => 'Yes. You get a public booking page and a QR code so clients book 24/7 without a single call.'],
        ['ar' => 'ماذا عن المالية والمخزون والتقارير؟', 'en' => 'What about finance, inventory and reports?', 'aar' => 'قادمة قريبًا جدًا. نبدأ بالمواعيد لنتقنها أولًا، ثم نضيف المالية والصلاحيات والمخزون والتحليلات تباعًا.', 'aen' => 'Coming very soon. We start with bookings to perfect them first, then add finance, roles, inventory and analytics.'],
    ];
@endphp

<x-front.layout
    variant="business"
    :title="$t('GlowRez للأعمال — نظام حجوزات مجاني لمركزك', 'GlowRez for Business — Free Booking System for Your Beauty Business')"
    :keywords="$t('نظام حجوزات مركز تجميل, حجز ليزر, برنامج إدارة سبا, حجوزات مجانية, تذكير واتساب, إدارة مواعيد, سوريا', 'beauty centre booking software, laser clinic bookings, spa management, free bookings, WhatsApp reminders, appointment scheduling, Syria')"
    :description="$t('نظام حجوزات مجاني لمراكز التجميل والليزر والسبا وصالونات الشعر والرجال: صفحة حجز عامة، رمز QR، تقويم بلا تعارض، وتذكير واتساب تلقائي يقلّل الغياب. المالية والمخزون والتقارير قريبًا.', 'A free bookings platform for beauty centres, laser clinics, spas and hair salons: public booking page, QR code, conflict-free calendar and automatic WhatsApp reminders. Finance, inventory and reports coming soon.')">

<x-slot:head>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'SoftwareApplication',
    'name'     => 'GlowRez for Business',
    'operatingSystem' => 'Web, Android, iOS',
    'applicationCategory' => 'BusinessApplication',
    'url'      => route('front.business'),
    'description' => $t('نظام حجوزات وإدارة مجاني لأماكن الجمال والعناية: صفحة حجز عامة، رمز QR، تقويم بلا تعارض وتذكير واتساب تلقائي.', 'A free booking & management platform for beauty & wellness venues: public booking page, QR code, conflict-free calendar and automatic WhatsApp reminders.'),
    'offers'   => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'SYP'],
    'publisher'=> ['@type' => 'Organization', 'name' => 'GlowRez', 'url' => url('/')],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn($q) => [
        '@type' => 'Question',
        'name'  => $t($q['ar'], $q['en']),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $t($q['aar'], $q['aen'])],
    ])->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</x-slot:head>

<x-slot:styles>
<style>
/* ══════════════════════════════════════════════════════════════════
   BUSINESS LANDING — appointments edition · olive + gold · image-rich
   ══════════════════════════════════════════════════════════════════ */

/* safety: no horizontal scroll on any screen (decorative bleeds stay clipped) */
main{ overflow-x:clip; }

/* English typeface: modern sans (Poppins) instead of the Playfair serif — Arabic/Tajawal untouched.
   To use Roboto instead → set: 'Roboto',var(--bk-font-ui);  To restore serif → delete this rule. */
html[lang="en"]{ --bk-font-display:var(--bk-font-ui); }

/* ─── scroll progress ─── */
.biz-progress{ position:fixed; inset-block-start:0; inset-inline:0; height:3px; z-index:2000; background:var(--bk-grad-gold); transform:scaleX(0); transform-origin:0 50%; }
html[dir="rtl"] .biz-progress{ transform-origin:100% 50%; }

/* ─── shared mockup primitives ─── */
.biz-win{ background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); box-shadow:var(--bk-shadow-lg); overflow:hidden; }
.biz-win-bar{ display:flex; align-items:center; gap:6px; padding:11px 14px; border-bottom:1px solid var(--bk-border); background:var(--bk-surface-2); }
.biz-win-bar i{ width:10px; height:10px; border-radius:50%; background:var(--bk-border-strong); opacity:.6; display:block; }
.biz-win-bar .u{ margin-inline-start:auto; font-family:var(--bk-font-ui); font-size:.64rem; color:var(--bk-text-muted); letter-spacing:.03em; }
.biz-win-body{ padding:16px; }
.biz-mock-title{ font-family:var(--bk-font-ui); font-weight:700; font-size:.95rem; color:var(--bk-text); }
.biz-mock-title span{ display:block; font-weight:500; font-size:.66rem; color:var(--bk-text-muted); margin-top:2px; }
.biz-live{ display:inline-flex; align-items:center; gap:6px; font-family:var(--bk-font-ui); font-size:.62rem; font-weight:700; color:var(--bk-success); background:var(--bk-success-bg); padding:4px 10px; border-radius:var(--bk-r-pill); }
.biz-live::before{ content:""; width:6px; height:6px; border-radius:50%; background:var(--bk-success); animation:biz-pulse 1.6s ease infinite; }
@keyframes biz-pulse{ 0%,100%{opacity:1} 50%{opacity:.3} }
.biz-mrow{ display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }

.biz-list{ display:flex; flex-direction:column; gap:8px; }
.biz-lrow{ display:flex; align-items:center; gap:10px; padding:9px 11px; background:var(--bk-surface-2); border:1px solid var(--bk-border); border-radius:var(--bk-r-sm); }
.biz-av{ width:30px; height:30px; border-radius:50%; display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); font-family:var(--bk-font-ui); font-weight:700; font-size:.72rem; flex-shrink:0; }
.biz-lrow .nm{ font-family:var(--bk-font-ui); font-weight:600; font-size:.74rem; color:var(--bk-text); }
.biz-lrow .sv{ font-family:var(--bk-font-ui); font-size:.6rem; color:var(--bk-text-muted); }
.biz-lrow .tm{ margin-inline-start:auto; font-family:var(--bk-font-ui); font-size:.66rem; font-weight:700; color:var(--bk-gold-strong); }
.biz-time{ font-family:var(--bk-font-ui); font-size:.6rem; font-weight:700; color:var(--bk-text-muted); width:34px; flex-shrink:0; text-align:center; }

.biz-tiles{ display:grid; grid-template-columns:repeat(3,1fr); gap:9px; }
.biz-tile{ background:var(--bk-surface-2); border:1px solid var(--bk-border); border-radius:var(--bk-r-sm); padding:11px; }
.biz-tile .n{ font-family:var(--bk-font-display); font-weight:800; font-size:1.15rem; color:var(--bk-gold-strong); line-height:1; }
.biz-tile .l{ font-family:var(--bk-font-ui); font-size:.6rem; color:var(--bk-text-muted); margin-top:5px; }

/* photo frame (real imagery, always with a scrim so text stays legible) */
.biz-photo{ position:relative; border-radius:var(--bk-r-lg); overflow:hidden; border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg); background:var(--bk-surface-2); }
.biz-photo img{ width:100%; height:100%; object-fit:cover; display:block; }
.biz-photo::after{ content:""; position:absolute; inset:0; background:linear-gradient(180deg,rgba(30,36,20,.05) 0%,rgba(30,36,20,.55) 100%); pointer-events:none; }
.biz-photo-cap{ position:absolute; z-index:2; inset-block-end:0; inset-inline:0; padding:16px 18px; color:#fff; display:flex; align-items:center; gap:9px; font-family:var(--bk-font-ui); font-weight:600; font-size:.82rem; }
.biz-photo-cap svg{ width:17px; height:17px; color:var(--bk-gold); }

/* ══════════════  HERO  ══════════════ */
.biz-hero{ position:relative; overflow:hidden; min-height:clamp(540px,80vh,720px); display:flex; align-items:center; padding-block:calc(var(--bk-nav-h) + clamp(18px,4vw,40px)) clamp(40px,6vw,84px); }
/* bottom fade: melt the dark photo into the next section's surface so there's no hard dark→light edge (adapts to theme) */
.biz-hero::after{ content:""; position:absolute; left:0; right:0; bottom:0; height:clamp(90px,12vw,160px); z-index:2; pointer-events:none;
  background:linear-gradient(180deg,transparent 0%,color-mix(in srgb,var(--bk-surface) 55%,transparent) 55%,var(--bk-surface) 100%); }
.biz-hero-bg{ position:absolute; inset:0; z-index:0; pointer-events:none; }
.biz-hero-bg::before{ content:""; position:absolute; inset:0; background:var(--bk-grad-hero); }
.biz-aurora{ position:absolute; inset:-20%; z-index:0; pointer-events:none; opacity:.6;
  background:radial-gradient(40% 40% at 20% 20%,rgba(199,161,90,.20),transparent 60%),
            radial-gradient(45% 45% at 85% 15%,rgba(75,93,52,.22),transparent 60%),
            radial-gradient(50% 50% at 70% 90%,rgba(199,161,90,.14),transparent 60%);
  animation:biz-aurora 18s ease-in-out infinite alternate; }
@keyframes biz-aurora{ 0%{ transform:translate3d(0,0,0) scale(1);} 100%{ transform:translate3d(0,-3%,0) scale(1.06);} }
.biz-glow{ position:absolute; border-radius:50%; filter:blur(60px); opacity:.5; z-index:0; }
.biz-glow.g1{ width:520px; height:520px; background:radial-gradient(circle,rgba(199,161,90,.30),transparent 70%); top:-160px; inset-inline-end:-120px; }
.biz-glow.g2{ width:460px; height:460px; background:radial-gradient(circle,rgba(75,93,52,.28),transparent 70%); bottom:-160px; inset-inline-start:-120px; }
/* full-bleed photographic hero (GlowRez-style): the photo fills the whole band; a dark olive scrim keeps the copy legible */
.biz-hero-photo{ position:absolute; inset:0; z-index:0; }
.biz-hero-photo img{ width:100%; height:100%; object-fit:cover; object-position:center right; display:block; }
/* RTL: mirror the photo with CSS only so its built-in dark side sits under the copy — no crop, no resize */
html[dir="rtl"] .biz-hero-photo img{ transform:scaleX(-1); }
/* vertical scrim — darkens top (nav legibility) and base */
.biz-hero-photo::before{ content:""; position:absolute; inset:0; z-index:1;
  background:linear-gradient(180deg,rgba(16,20,10,.52),transparent 24%,transparent 58%,rgba(16,20,10,.66)); }
/* directional scrim — strongest on the copy side, easing to reveal the photo on the far side */
.biz-hero-photo::after{ content:""; position:absolute; inset:0; z-index:1; }
html[dir="ltr"] .biz-hero-photo::after{ background:linear-gradient(90deg,rgba(21,26,13,.94) 0%,rgba(27,34,17,.86) 34%,rgba(30,38,20,.52) 62%,rgba(30,38,20,.20) 100%); }
html[dir="rtl"] .biz-hero-photo::after{ background:linear-gradient(270deg,rgba(21,26,13,.94) 0%,rgba(27,34,17,.86) 34%,rgba(30,38,20,.52) 62%,rgba(30,38,20,.20) 100%); }
.biz-hero-grid{ position:relative; z-index:1; width:100%; display:grid; grid-template-columns:1.1fr .9fr; gap:clamp(8px,1.6vw,28px); align-items:center; }
.biz-hero h1{ font-size:clamp(2rem,1.2rem + 2.9vw,3.5rem); margin-top:var(--bk-s5); letter-spacing:-.035em; line-height:1.04; color:#fff; text-shadow:0 1px 20px rgba(10,14,6,.35); }
html[lang="ar"] .biz-hero h1{ letter-spacing:0; line-height:1.2; }
.biz-hero h1 .em{ color:#C6DC9C; font-style:normal; }
.biz-hero h1 .gold{ position:relative; white-space:nowrap; color:#EBCF8E; }
.biz-hero h1 .gold::after{ content:""; position:absolute; left:0; right:0; bottom:.05em; height:.16em; background:var(--bk-grad-gold); opacity:.92; border-radius:2px; z-index:-1; transform:scaleX(0); transform-origin:inherit; animation:biz-underline .9s var(--bk-ease) .6s forwards; }
html[dir="rtl"] .biz-hero h1 .gold::after{ transform-origin:100% 50%; }
html[dir="ltr"] .biz-hero h1 .gold::after{ transform-origin:0 50%; }
@keyframes biz-underline{ to{ transform:scaleX(1); } }
/* word-by-word reveal */
.biz-word{ display:inline-block; opacity:0; transform:translateY(24px); filter:blur(6px); animation:biz-word .7s var(--bk-ease) forwards; }
@keyframes biz-word{ to{ opacity:1; transform:none; filter:none; } }
.biz-hero-sub{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-lead); color:rgba(255,255,255,.9); line-height:1.72; max-width:52ch; margin-top:var(--bk-s6); }
.biz-hero-cta{ display:flex; flex-wrap:wrap; gap:var(--bk-s3); margin-top:var(--bk-s8); }
.biz-hero-note{ display:flex; flex-wrap:wrap; align-items:center; gap:6px 18px; margin-top:var(--bk-s5); font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:rgba(255,255,255,.84); }
.biz-hero-note span{ display:inline-flex; align-items:center; gap:7px; }
.biz-hero-note svg{ width:16px; height:16px; color:#E4C588; }
/* badge & secondary CTA tuned for the dark hero */
.biz-hero .bkf-badge-gold{ background:rgba(228,197,136,.16); color:#F1DCA6; border-color:rgba(228,197,136,.45); backdrop-filter:blur(4px); }
.biz-hero .bkf-btn-soft{ background:rgba(255,255,255,.12); color:#fff; border-color:rgba(255,255,255,.32); backdrop-filter:blur(6px); box-shadow:none; }
.biz-hero .bkf-btn-soft:hover{ background:rgba(255,255,255,.2); border-color:rgba(255,255,255,.55); }

/* ─── nav over the dark hero: light chrome until scrolled (then .is-scrolled restores the cream bar).
   This also fixes the previous light-mode issue where the header links washed out. ─── */
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-brand,
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-links a,
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-lang{ color:#fff; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-brand-sub{ color:rgba(255,255,255,.74); }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-brand-dot{ color:var(--bk-gold); }
/* the logo is a raster <img> (theme-swapped), so color:#fff no longer whitens it —
   force the white (dark-variant) lockup while the nav floats over the dark hero, in
   BOTH themes; once .is-scrolled restores the solid bar, the theme lockup returns. */
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-logo--light{ display:none; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-logo--dark{ display:block; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-links a:hover,
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-links a.is-active{ color:#fff; background:rgba(255,255,255,.16); }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-lang{ border-color:rgba(255,255,255,.45); }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-lang:hover{ color:#fff; border-color:#fff; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-theme{ color:#fff; border-color:rgba(255,255,255,.45); background:rgba(255,255,255,.1); }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-theme:hover{ border-color:#fff; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-btn.bkf-btn-ghost{ color:#fff; border-color:rgba(255,255,255,.5); background:transparent; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-btn.bkf-btn-ghost:hover{ background:rgba(255,255,255,.14); border-color:#fff; }
.bkf-nav:not(.is-scrolled):not(.is-open) .bkf-nav-burger{ color:#fff; border-color:rgba(255,255,255,.5); background:rgba(255,255,255,.1); }

/* ══════════════════════════════════════════════════════════════════════
   HERO STAGE — the calendar screenshot floating over the hero photo.
   Desktop: sits in the hero's inline-end column but pulled CLOSER to the copy
            (reduced grid gap + start-aligned within its column + a small logical nudge).
   Mobile/Tablet (see media query): centered, right under the copy.
   Offsets use clamp()/logical properties → responsive + RTL-safe. Image size unchanged.
   ══════════════════════════════════════════════════════════════════════ */
.biz-stage{
  position:relative;
  display:flex; align-items:center; justify-content:flex-start;   /* align image to the copy side of its column → closer to the text */
  min-height:clamp(420px,42vw,560px);
  padding-inline-start:clamp(0px,1vw,20px);   /* tiny breathing gap so the rotated card never touches the copy (RTL-safe) */
}

/* soft brand aura behind the image → depth, not a flat background (decorative, non-interactive) */
.biz-stage::before{
  content:""; position:absolute; z-index:0; pointer-events:none;
  inset-block:8% 2%; inset-inline:0; margin-inline:auto;
  width:min(84%,430px);
  border-radius:46% 46% 44% 44% / 54% 54% 46% 46%;
  background:
    radial-gradient(58% 46% at 50% 40%, rgba(199,161,90,.24), transparent 72%),
    linear-gradient(180deg, transparent 42%, color-mix(in srgb,var(--bk-accent) 30%,transparent) 100%);
  filter:blur(32px);
}

/* the floating calendar (size preserved via clamp — unchanged) + fade/rise on load */
.biz-stack{
  position:relative; z-index:1;
  width:clamp(300px,32vw,430px);
  animation:biz-stack-in 1s var(--bk-ease) .15s both;
}
.biz-stack-bg{ display:block; margin:0; }
.biz-stack-bg img{
  --rot:-2deg;
  display:block; width:100%; height:auto;                /* native aspect ratio kept, size unchanged */
  border-radius:clamp(16px,1.6vw,22px);
  box-shadow:0 44px 88px -30px rgba(8,11,5,.55), 0 16px 40px -18px rgba(8,11,5,.34);
  transform:rotate(var(--rot));
  animation:biz-stack-float 7s ease-in-out 1.1s infinite; /* very slow float */
}
html[dir="rtl"] .biz-stack-bg img{ --rot:2deg; }

@keyframes biz-stack-in{
  from{ opacity:0; transform:translateY(30px); }
  to  { opacity:1; transform:translateY(0); }
}
@keyframes biz-stack-float{                                /* rotation preserved, only Y oscillates */
  0%,100%{ transform:rotate(var(--rot)) translateY(0); }
  50%    { transform:rotate(var(--rot)) translateY(-10px); }
}

.biz-float{ position:absolute; z-index:4; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r-sm); box-shadow:var(--bk-shadow-lg); padding:11px 13px; display:flex; align-items:center; gap:11px; animation:biz-bob 6s ease-in-out infinite; }
.biz-float .fic{ width:34px; height:34px; border-radius:10px; display:grid; place-items:center; flex-shrink:0; }
.biz-float .fic svg{ width:19px; height:19px; }
.biz-float .fic.gold{ background:var(--bk-gold-soft); color:var(--bk-gold-strong); }
.biz-float .fic.olive{ background:var(--bk-accent-wash); color:var(--bk-accent); }
.biz-float .fn{ font-family:var(--bk-font-display); font-weight:800; font-size:1rem; color:var(--bk-text); line-height:1; }
.biz-float .fn.gold{ color:var(--bk-gold-strong); }
.biz-float .fl{ font-family:var(--bk-font-ui); font-size:.58rem; color:var(--bk-text-muted); margin-top:2px; }
.biz-f-book{ top:4%; inset-inline-end:-2%; animation-delay:-1s; }
.biz-f-rem{ top:42%; inset-inline-start:20%; animation-delay:-3s; }
.biz-f-full{ bottom:5%; inset-inline-end:3%; animation-delay:-2s; }
@keyframes biz-bob{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-10px); } }

/* ══════════════  MARQUEE TRUSTED  ══════════════ */
.biz-trusted{ border-block-end:1px solid var(--bk-border); background:var(--bk-surface); padding-block:clamp(26px,4vw,40px); overflow:hidden; }
.biz-trusted-lbl{ text-align:center; font-family:var(--bk-font-ui); font-size:.76rem; font-weight:600; letter-spacing:.16em; text-transform:uppercase; color:var(--bk-text-muted); margin-bottom:22px; }
.biz-marquee{ position:relative; display:flex; overflow:hidden; -webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent); mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent); }
.biz-marquee-track{ display:flex; gap:12px; padding-inline-start:12px; flex-shrink:0; animation:biz-marq 24s linear infinite; }
html[dir="rtl"] .biz-marquee-track{ animation-direction:reverse; }
@keyframes biz-marq{ to{ transform:translateX(-50%); } }
.biz-tt-pill{ display:inline-flex; align-items:center; gap:9px; padding:10px 18px; border-radius:var(--bk-r-pill); background:var(--bk-bg); border:1px solid var(--bk-border); font-family:var(--bk-font-ui); font-weight:600; font-size:.9rem; color:var(--bk-text-soft); white-space:nowrap; }
.biz-tt-pill svg{ width:18px; height:18px; color:var(--bk-gold-strong); }
.biz-trust-stats{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:38px; max-width:720px; margin-inline:auto; }
.biz-ts{ text-align:center; }
.biz-ts .n{ font-family:var(--bk-font-display); font-weight:800; font-size:clamp(1.7rem,3vw,2.6rem); color:var(--bk-gold-strong); line-height:1; }
.biz-ts .biz-unit{ margin-inline-start:.14em; font-size:.72em; font-weight:700; }
.biz-ts .l{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); margin-top:8px; }

/* ══════════════  PROBLEM  ══════════════ */
.biz-problem-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:var(--bk-s5); margin-top:var(--bk-s12); }
.biz-prob{ padding:26px 22px; border-radius:var(--bk-r-lg); background:var(--bk-surface); border:1px solid var(--bk-border); transition:transform var(--bk-t) var(--bk-ease),box-shadow var(--bk-t) ease; }
.biz-prob:hover{ transform:translateY(-5px); box-shadow:var(--bk-shadow); }
.biz-prob-ic{ width:48px; height:48px; border-radius:14px; display:grid; place-items:center; background:var(--bk-danger-bg); color:var(--bk-danger); margin-bottom:16px; }
.biz-prob-ic svg{ width:24px; height:24px; }
.biz-prob h3{ font-family:var(--bk-font-ui); font-size:1.05rem; font-weight:700; color:var(--bk-text); margin-bottom:8px; }
.biz-prob p{ font-family:var(--bk-font-ui); font-size:.9rem; color:var(--bk-text-muted); line-height:1.65; }

/* ══════════════  FEATURES BENTO  ══════════════ */
.biz-bento{ display:grid; grid-template-columns:repeat(3,1fr); gap:var(--bk-s5); margin-top:var(--bk-s12); }
.biz-cell{ padding:26px 24px; border-radius:var(--bk-r-lg); background:var(--bk-surface); border:1px solid var(--bk-border); transition:transform var(--bk-t) var(--bk-ease),box-shadow var(--bk-t) ease,border-color var(--bk-t) ease; position:relative; overflow:hidden; }
.biz-cell:hover{ transform:translateY(-6px); box-shadow:var(--bk-shadow-lg); border-color:color-mix(in srgb,var(--bk-gold) 34%,var(--bk-border)); }
.biz-cell.wide{ grid-column:span 2; }
.biz-cell-ic{ width:46px; height:46px; border-radius:13px; display:grid; place-items:center; background:var(--bk-gold-soft); color:var(--bk-gold-strong); margin-bottom:16px; transition:transform var(--bk-t) var(--bk-spring); }
.biz-cell:hover .biz-cell-ic{ transform:scale(1.1) rotate(-5deg); }
.biz-cell-ic svg{ width:23px; height:23px; }
.biz-cell h3{ font-family:var(--bk-font-ui); font-size:1.08rem; font-weight:700; color:var(--bk-text); margin-bottom:8px; }
.biz-cell p{ font-family:var(--bk-font-ui); font-size:.9rem; color:var(--bk-text-muted); line-height:1.66; }

/* ══════════════  DEEP-DIVE FEATURE  ══════════════ */
.biz-feature{ display:grid; grid-template-columns:1fr 1fr; gap:clamp(32px,5vw,80px); align-items:center; }
.biz-feature + .biz-feature{ margin-top:clamp(64px,9vw,128px); }
.biz-feature.rev .biz-feature-text{ order:2; }
.biz-feature-eyebrow{ font-family:var(--bk-font-ui); font-weight:700; font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; color:var(--bk-gold-strong); }
.biz-feature-text h2{ font-size:var(--bk-fs-h2); margin-top:var(--bk-s3); }
.biz-feature-text h2 .em{ color:var(--bk-accent); font-style:italic; }
.biz-feature-text .lead{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-lead); color:var(--bk-text-soft); line-height:1.7; margin-top:var(--bk-s4); }
.biz-feature-pts{ list-style:none; margin:var(--bk-s6) 0 0; padding:0; display:flex; flex-direction:column; gap:14px; }
.biz-feature-pts li{ display:flex; gap:12px; font-family:var(--bk-font-ui); font-size:.95rem; color:var(--bk-text-soft); line-height:1.5; align-items:center; }
.biz-feature-pts li .ck{ width:26px; height:26px; border-radius:8px; flex-shrink:0; display:grid; place-items:center; background:var(--bk-gold-soft); color:var(--bk-gold-strong); }
.biz-feature-pts li .ck svg{ width:15px; height:15px; }
.biz-feature-cta{ margin-top:var(--bk-s8); }

/* ── App-shot visual: real product mockup floating on an accent shape (GlowRez-style) ── */
.biz-features{ display:flex; flex-direction:column; margin-top:clamp(40px,6vw,72px); }
.biz-shot{ position:relative; display:grid; place-items:center; padding:clamp(14px,2.4vw,30px); isolation:isolate; }
/* soft rounded accent panel behind the mockup, offset toward the outer edge and gently floating */
.biz-shot-blob{ position:absolute; z-index:-2; width:74%; height:78%; border-radius:var(--bk-r-2xl);
  background:linear-gradient(150deg,var(--bk-accent-wash),var(--bk-gold-soft) 92%);
  animation:biz-shot-bob 7s ease-in-out infinite alternate; }
.biz-feature:not(.rev) .biz-shot-blob{ inset-block-end:6%; inset-inline-end:4%; }
.biz-feature.rev .biz-shot-blob{ inset-block-end:6%; inset-inline-start:4%; }
/* faint dashed ring for a touch of depth on the opposite corner */
.biz-shot-ring{ position:absolute; z-index:-2; width:120px; height:120px; border-radius:50%;
  border:1.5px dashed color-mix(in srgb,var(--bk-gold) 55%,transparent); opacity:.7;
  animation:biz-shot-bob 9s ease-in-out infinite alternate-reverse; }
.biz-feature:not(.rev) .biz-shot-ring{ inset-block-start:2%; inset-inline-start:6%; }
.biz-feature.rev .biz-shot-ring{ inset-block-start:2%; inset-inline-end:6%; }
/* Invisible wrapper — the product shots are transparent PNGs that already carry
   their own card + shadow, so they float over .biz-shot-blob (was a broken rule). */
.biz-shot-frame{ position:relative; z-index:1; width:min(100%,430px);
  transition:transform var(--bk-t) var(--bk-spring); }
/* a deliberately larger, more prominent mockup for its section */
.biz-shot-frame.is-big{ width:min(100%,560px); }
@media (min-width:901px){
  /* give the visual side more room so the enlarged mockup can actually grow */
  .biz-feature.is-big{ grid-template-columns:1.18fr .82fr; }
  .biz-feature.is-big:not(.rev){ grid-template-columns:.82fr 1.18fr; }
}
.biz-shot-frame img{ display:block; width:100%; height:auto;
  filter:drop-shadow(0 26px 52px rgba(8,11,5,.30)); }
.biz-shot-frame:hover{ transform:translateY(-8px) scale(1.015); }
@keyframes biz-shot-bob{ from{ transform:translateY(0); } to{ transform:translateY(-14px); } }

/* collage: mock in front, photo peeking behind */
.biz-collage{ position:relative; }
.biz-collage::before{ content:""; position:absolute; inset:-26px; z-index:0; background:var(--bk-grad-gold-soft); border-radius:var(--bk-r-2xl); }
.biz-collage .biz-win{ position:relative; z-index:2; }
.biz-collage-photo{ position:absolute; z-index:1; width:46%; aspect-ratio:3/4; border-radius:var(--bk-r-lg); overflow:hidden; box-shadow:var(--bk-shadow-lg); border:2px solid var(--bk-surface); bottom:-22px; }
.biz-feature:not(.rev) .biz-collage-photo{ inset-inline-end:-14px; }
.biz-feature.rev .biz-collage-photo{ inset-inline-start:-14px; }
.biz-collage-photo img{ width:100%; height:100%; object-fit:cover; display:block; }
.biz-collage-photo::after{ content:""; position:absolute; inset:0; background:linear-gradient(180deg,transparent,rgba(30,36,20,.35)); }

/* animated day-schedule rows */
.biz-slot{ opacity:0; transform:translateX(var(--sx,18px)); }
html[dir="rtl"] .biz-slot{ --sx:-18px; }
.bkf-reveal.is-in .biz-slot{ animation:biz-slot .55s var(--bk-ease) forwards; }
.bkf-reveal.is-in .biz-slot:nth-child(1){ animation-delay:.15s; }
.bkf-reveal.is-in .biz-slot:nth-child(2){ animation-delay:.28s; }
.bkf-reveal.is-in .biz-slot:nth-child(3){ animation-delay:.41s; }
.bkf-reveal.is-in .biz-slot:nth-child(4){ animation-delay:.54s; }
@keyframes biz-slot{ to{ opacity:1; transform:none; } }

/* whatsapp chat */
.biz-chat{ background:var(--bk-surface-2); border-radius:var(--bk-r-sm); padding:14px; display:flex; flex-direction:column; gap:9px; }
.biz-bubble{ max-width:82%; padding:9px 12px; border-radius:14px; font-family:var(--bk-font-ui); font-size:.72rem; line-height:1.45; opacity:0; transform:translateY(10px); }
.bkf-reveal.is-in .biz-bubble{ animation:biz-word .5s var(--bk-ease) forwards; }
.bkf-reveal.is-in .biz-bubble:nth-child(1){ animation-delay:.2s; }
.bkf-reveal.is-in .biz-bubble:nth-child(2){ animation-delay:.55s; }
.bkf-reveal.is-in .biz-bubble:nth-child(3){ animation-delay:.9s; }
.biz-bubble.in{ align-self:flex-start; background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-text); border-bottom-inline-start-radius:4px; }
.biz-bubble.out{ align-self:flex-end; background:var(--bk-accent-fill); color:#fff; border-bottom-inline-end-radius:4px; }
.biz-bubble .tm{ display:block; font-size:.52rem; opacity:.7; margin-top:3px; }

/* QR */
.biz-qr{ width:96px; height:96px; border-radius:12px; padding:9px; background:#fff; box-shadow:var(--bk-shadow-sm); flex-shrink:0; }
.biz-qr svg{ width:100%; height:100%; display:block; }

/* ══════════════  MOBILE STRIP  ══════════════ */
.biz-mobile{ display:grid; grid-template-columns:1.1fr 1fr; gap:clamp(28px,4vw,64px); align-items:center; }

/* ══════════════  COMING SOON — the street  ══════════════
   Shopfronts on one pavement. Bookings is open and lit; the rest wait behind roller shutters (point, tap or Tab to peek).
   Phones: the street scrolls sideways, one big shopfront at a time. */
.st-street{ --st-gap:clamp(12px,1.8vw,28px); list-style:none; margin:clamp(40px,5.5vw,72px) 0 0; padding:0; display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:clamp(30px,4vw,44px) var(--st-gap); align-items:start; }
.st-shop{ --aw:#A9A38D; --sign:#454A39; --wall:#E3DCC9; --frame:#868270; --glass:linear-gradient(180deg,#C9D0BC,#AEB8A0); --ink:#6B6F5C; display:flex; flex-direction:column; outline:none; }
.st-shop.is-live{ --aw:#47592F; --sign:#1D2614; --wall:#F7EED5; --frame:#2D381F; --glass:radial-gradient(90% 80% at 50% 78%,#FFEBB3,#EBC76B 58%,#C99A3E); --ink:#2B3519; }
.st-front{ position:relative; isolation:isolate; }
.st-shop.is-live .st-front::before{ content:""; position:absolute; z-index:-1; inset:6% -16% -6%; background:radial-gradient(58% 52% at 50% 62%,rgba(238,202,110,.34),transparent 72%); pointer-events:none; }

#soon{ padding-bottom:clamp(40px,5vw,72px); }
/* canopy: a shallow arch of plain pleated cloth with gold piping and a string of bulbs.
   Lit on the open shop; dark on the others until their shutter is lifted. */
.st-awning{ position:relative; z-index:2; height:clamp(40px,4.8vw,56px); border-radius:50% 50% 4px 4px / 80% 80% 4px 4px;
  background:linear-gradient(180deg,rgba(255,255,255,.22),rgba(255,255,255,0) 46%,rgba(0,0,0,.22)),repeating-linear-gradient(90deg,rgba(255,255,255,.09) 0 7px,rgba(0,0,0,.05) 7px 14px),var(--aw);
  box-shadow:inset 0 0 0 2px rgba(230,201,135,.55),inset 0 -4px 0 -1px rgba(230,201,135,.95);
  filter:drop-shadow(0 9px 6px rgba(24,30,14,.26)); }
.st-awning::before,.st-awning::after{ content:""; position:absolute; inset-inline:12px; bottom:-9px; height:16px; background-size:26px 16px; background-repeat:space no-repeat; background-position:center; pointer-events:none; }
.st-awning::before{ background-image:radial-gradient(circle,#DDD6C0 0 3px,#8E886F 3px 4.6px,transparent 5.2px); }
.st-awning::after{ background-image:radial-gradient(circle,#FFF7D4 0 3px,#F4C95F 3px 5px,transparent 6px); filter:drop-shadow(0 0 6px rgba(255,205,105,.95)); opacity:0; transition:opacity .7s ease; }
.st-shop.is-live .st-awning::after,.st-shop:not(.is-live):hover .st-awning::after,.st-shop:not(.is-live):focus .st-awning::after{ opacity:1; }
.st-sign{ position:relative; z-index:1; margin:-2px 8px 0; padding:20px 8px 10px; font-size:clamp(1rem,.82rem + .55vw,1.3rem); min-height:calc(2.5em + 24px); display:grid; place-items:center; text-align:center;
  background:linear-gradient(180deg,rgba(255,255,255,.07),transparent 50%),var(--sign); border-radius:0 0 6px 6px; box-shadow:inset 0 0 0 1px rgba(230,201,135,.5),inset 0 0 0 4px var(--sign),inset 0 0 0 5px rgba(230,201,135,.22); }
.st-shop.is-live .st-sign{ box-shadow:inset 0 0 0 1px rgba(230,201,135,.6),inset 0 0 0 4px var(--sign),inset 0 0 0 5px rgba(230,201,135,.3),0 12px 26px -10px rgba(238,202,110,.55); }
.bkf .st-sign h3{ margin:0; font-size:1em; font-weight:800; line-height:1.25; color:#F6EFD9; text-wrap:balance; }

/* the shop body: window, door, plant, shutter */
.st-body{ position:relative; overflow:hidden; display:grid; grid-template-columns:1.45fr 1fr; gap:clamp(8px,1vw,12px); align-items:end; margin:0 8px; padding:clamp(10px,1.2vw,14px) clamp(10px,1.2vw,14px) 0; aspect-ratio:1/1.04;
  background:repeating-linear-gradient(0deg,rgba(0,0,0,.035) 0 1px,transparent 1px 9px),var(--wall); }
.st-win{ position:relative; align-self:stretch; margin-bottom:clamp(28px,3.2vw,42px); display:grid; place-items:center; color:var(--ink); background:var(--glass); border-radius:6px; overflow:hidden;
  box-shadow:inset 0 0 0 4px var(--frame),inset 0 0 0 5px rgba(255,255,255,.18),0 5px 0 -1px var(--frame); transition:background .7s ease; }
.st-win::before{ content:""; position:absolute; inset:0; background:linear-gradient(118deg,rgba(255,255,255,.42) 0 14%,transparent 14% 24%,rgba(255,255,255,.2) 24% 29%,transparent 29%); pointer-events:none; }
.st-win svg{ position:relative; z-index:1; }
.st-door{ position:relative; height:80%; background:linear-gradient(90deg,rgba(255,255,255,.07),transparent 40%),var(--frame); border-radius:6px 6px 0 0; }
.st-door::before{ content:""; position:absolute; inset:9px 9px 44%; border-radius:3px 3px 0 0; background:var(--glass); box-shadow:inset 0 0 0 2px rgba(0,0,0,.18); }
.st-door::after{ content:""; position:absolute; inset-block-start:60%; inset-inline-end:9px; width:6px; height:6px; border-radius:50%; background:#E6C987; box-shadow:0 0 0 2px rgba(0,0,0,.18); }
.st-pot{ position:absolute; z-index:1; inset-block-end:0; inset-inline-start:clamp(2px,.6vw,8px); width:clamp(20px,2.6vw,30px); height:clamp(30px,3.8vw,44px); pointer-events:none; }
.st-pot::before{ content:""; position:absolute; inset:0 0 38%; border-radius:50% 50% 46% 46%; background:radial-gradient(circle at 35% 30%,#8FAE62,#4F6A33 70%); }
.st-pot::after{ content:""; position:absolute; inset:58% 14% 0; background:linear-gradient(90deg,#B6704B,#9A5A3B); clip-path:polygon(0 0,100% 0,84% 100%,16% 100%); }
.st-shop:nth-child(even) .st-pot{ inset-inline-start:auto; inset-inline-end:clamp(2px,.6vw,8px); }
.st-tag{ position:absolute; z-index:3; font-family:'Tajawal',var(--bk-font-ui); font-weight:800; font-size:.88rem; line-height:1; white-space:nowrap; padding:8px 15px; border-radius:6px; color:#2A2310; background:#FBF6E6; box-shadow:0 8px 14px -8px rgba(0,0,0,.5),inset 0 0 0 1px rgba(75,93,52,.28); }
.st-tag.is-open{ inset-inline:0; margin-inline:auto; width:max-content; inset-block-end:clamp(38px,4.2vw,54px); color:#27331A; display:inline-flex; align-items:center; gap:8px; }
.st-tag.is-open::before{ content:""; width:8px; height:8px; border-radius:50%; background:#4B5D34; box-shadow:0 0 0 3px rgba(75,93,52,.2); }

/* roller shutter */
.st-shutter{ position:absolute; inset:0; z-index:2; display:grid; place-items:center; background:linear-gradient(90deg,rgba(0,0,0,.14),transparent 9%,transparent 91%,rgba(0,0,0,.14)),repeating-linear-gradient(180deg,#C6BEA8 0 7px,#B1A993 7px 8px);
  box-shadow:inset 0 -12px 0 -2px #98917B,inset 0 0 24px rgba(60,56,40,.24); transition:transform .8s cubic-bezier(.22,1,.36,1); }
.st-shutter::after{ content:""; position:absolute; inset-inline:0; margin-inline:auto; inset-block-end:16px; width:30px; height:7px; border-radius:4px; background:#7E775F; box-shadow:0 1px 0 rgba(255,255,255,.35); }
.st-shutter .st-tag{ position:relative; transform:rotate(-3deg); }
.st-shutter .st-tag::before{ content:""; position:absolute; inset-block-start:-6px; inset-inline:0; margin-inline:auto; width:7px; height:7px; border-radius:50%; background:#8B8470; box-shadow:0 1px 0 rgba(255,255,255,.4); }
.st-shop:not(.is-live):hover .st-shutter,.st-shop:not(.is-live):focus .st-shutter{ transform:translateY(-40%); }
.st-shop:not(.is-live):hover .st-win,.st-shop:not(.is-live):focus .st-win{ background:radial-gradient(90% 80% at 50% 78%,#FFF0C9,#E8CD8A 60%,#C9A458); }
.bkf .st-shop:focus-visible{ outline:none; }
.st-shop:focus-visible .st-front{ outline:3px solid var(--bk-accent); outline-offset:6px; border-radius:6px; }

/* pavement, with a pool of light under the open shop */
.st-ground{ position:relative; height:14px; margin-inline:calc(var(--st-gap) / -2); background:repeating-linear-gradient(90deg,transparent 0 31px,rgba(0,0,0,.13) 31px 32px),linear-gradient(180deg,#BDB59E,#A39B84); border-top:3px solid #928B73; }
.st-shop.is-live .st-ground::before{ content:""; position:absolute; inset-block-start:-3px; inset-inline:14%; height:16px; background:radial-gradient(ellipse 50% 100% at 50% 0,rgba(255,232,160,.95),transparent 72%); filter:blur(2px); }
.st-desc{ margin:18px 6px 0; text-align:center; font-family:var(--bk-font-ui); font-size:.96rem; line-height:1.75; color:var(--bk-text-soft); text-wrap:pretty; }
.st-sr{ position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
.st-hint{ display:none; }
.st-foot{ margin-top:clamp(22px,2.6vw,32px); display:flex; flex-direction:column; align-items:center; gap:14px; text-align:center; }
.st-foot p{ max-width:46ch; font-family:var(--bk-font-ui); font-size:.95rem; line-height:1.7; color:var(--bk-text-muted); }

/* the one authored moment: the open shop's lights come on as the street scrolls in */
.st-shop.is-live .st-win::after{ content:""; position:absolute; inset:0; background:radial-gradient(110% 90% at 50% 100%,rgba(255,244,205,.9),transparent 70%); mix-blend-mode:soft-light; }
.st-street.is-in .st-shop.is-live .st-front{ animation:st-on 1.5s .5s both; }
@keyframes st-on{ 0%{ filter:brightness(.6) saturate(.6); } 14%{ filter:brightness(1.12); } 22%{ filter:brightness(.65) saturate(.7); } 38%,100%{ filter:none; } }

@media (max-width:1000px) and (min-width:761px){ .st-street{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media (max-width:760px){
  .st-street{ display:flex; gap:var(--st-gap); margin-inline:calc(var(--bk-gutter,16px) * -1); padding:10px var(--bk-gutter,16px) 8px; overflow-x:auto; overscroll-behavior-x:contain; scroll-snap-type:x mandatory; scroll-padding-inline:var(--bk-gutter,16px); scrollbar-width:none; -webkit-overflow-scrolling:touch; }
  .st-street::-webkit-scrollbar{ display:none; }
  .st-shop{ flex:0 0 min(76vw,280px); scroll-snap-align:start; }
  .st-desc{ font-size:1rem; }
  .st-hint{ display:flex; align-items:center; justify-content:center; gap:8px; margin:10px 0 0; font-family:var(--bk-font-ui); font-size:.88rem; font-weight:700; color:var(--bk-text-muted); }
  [dir=rtl] .st-hint svg{ transform:scaleX(-1); }
}
@media (prefers-reduced-motion:reduce){ .st-shutter,.st-win,.st-awning::after{ transition:none !important; } .st-street.is-in .st-shop.is-live .st-front{ animation:none !important; } }

/* ══════════════  WHY SWITCH  ══════════════ */
.biz-why{ background:var(--bk-surface); }
/* ── Faint line-art tool motifs framing the section header (premium salon/spa texture, zero weight) ── */
.bkf-head-center{ position:relative; }
.bkf-head-center > *:not(.biz-head-motifs){ position:relative; z-index:1; }
.biz-head-motifs{ position:absolute; z-index:0; inset:-34px -6% -22px; pointer-events:none; }
.biz-head-motifs .biz-motif{ position:absolute; color:var(--bk-accent); opacity:.07; animation:biz-motif-float 15s ease-in-out infinite alternate; }
.biz-head-motifs .biz-motif svg{ display:block; width:100%; height:100%; }
.biz-head-motifs .m1{ width:clamp(58px,7vw,88px); aspect-ratio:1; inset-block-start:-10%; inset-inline-start:2%; --r:-15deg; }
.biz-head-motifs .m2{ width:clamp(50px,6vw,74px); aspect-ratio:1; inset-block-end:-8%; inset-inline-end:4%; --r:13deg; color:var(--bk-gold-strong); opacity:.09; animation-delay:2.4s; }
.biz-head-motifs .m3{ width:clamp(40px,5vw,58px); aspect-ratio:1; inset-block-start:2%; inset-inline-end:15%; --r:8deg; animation-delay:4.6s; }
@keyframes biz-motif-float{ from{ transform:rotate(var(--r,0deg)) translateY(0); } to{ transform:rotate(var(--r,0deg)) translateY(-14px); } }
@media (max-width:560px){ .biz-head-motifs{ display:none; } }
.biz-why-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:var(--bk-s5); margin-top:var(--bk-s12); }
.biz-why-card{ position:relative; padding:28px 24px; border-radius:var(--bk-r-lg); background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-xs); overflow:hidden; transition:transform var(--bk-t) var(--bk-spring), box-shadow var(--bk-t) ease, border-color var(--bk-t) ease; }
.biz-why-card::before{ content:""; position:absolute; inset-block-start:0; inset-inline:0; height:3px; background:var(--bk-grad-gold); transform:scaleX(0); transform-origin:inline-start; transition:transform var(--bk-t) var(--bk-ease); }
.biz-why-card:hover{ transform:translateY(-6px); box-shadow:var(--bk-shadow-lg); border-color:color-mix(in srgb,var(--bk-gold) 34%,var(--bk-border)); }
.biz-why-card:hover::before{ transform:scaleX(1); }
.biz-why-ic{ width:50px; height:50px; border-radius:14px; display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); margin-bottom:16px; transition:transform var(--bk-t) var(--bk-spring), background var(--bk-t) ease; }
.biz-why-card:hover .biz-why-ic{ transform:scale(1.08) rotate(-4deg); background:var(--bk-gold-soft); color:var(--bk-gold-strong); }
.biz-why-ic svg{ width:24px; height:24px; }
.biz-why-card h3{ font-family:var(--bk-font-ui); font-size:1.02rem; font-weight:700; color:var(--bk-text); margin-bottom:8px; }
.biz-why-card p{ font-family:var(--bk-font-ui); font-size:.88rem; color:var(--bk-text-muted); line-height:1.65; }
.biz-why-cta{ display:flex; justify-content:center; margin-top:clamp(32px,5vw,52px); }

/* ══════════════  TESTIMONIALS  ══════════════ */
.biz-tt-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:var(--bk-s6); margin-top:var(--bk-s12); }
.biz-tcard{ padding:28px 24px; border-radius:var(--bk-r-lg); background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-sm); display:flex; flex-direction:column; position:relative; }
.biz-tcard .quo{ position:absolute; top:18px; inset-inline-end:22px; color:var(--bk-gold); opacity:.28; }
.biz-tcard .quo svg{ width:34px; height:34px; }
.biz-tcard .stars{ display:flex; gap:2px; color:var(--bk-gold); }
.biz-tcard .stars svg{ width:15px; height:15px; }
.biz-tcard-q{ font-family:var(--bk-font-ui); font-size:.96rem; color:var(--bk-text-soft); line-height:1.75; margin:14px 0 20px; flex:1; }
.biz-tcard-au{ display:flex; align-items:center; gap:12px; padding-top:16px; border-top:1px solid var(--bk-border); }
.biz-tcard-av{ width:44px; height:44px; border-radius:50%; display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); font-family:var(--bk-font-ui); font-weight:800; }
.biz-tcard-nm{ font-family:var(--bk-font-ui); font-weight:700; font-size:.92rem; color:var(--bk-text); }
.biz-tcard-rl{ font-family:var(--bk-font-ui); font-size:.74rem; color:var(--bk-text-muted); }

/* ══════════════  FREE (replaces pricing)  ══════════════ */
.biz-free-card{ position:relative; text-align:center; padding:clamp(44px,6vw,80px) clamp(24px,5vw,56px); border-radius:var(--bk-r-2xl); background:var(--bk-grad-accent); color:#fff; box-shadow:var(--bk-shadow-xl); overflow:hidden; max-width:920px; margin-inline:auto; }
.biz-free-card::before{ content:""; position:absolute; inset:0; background:radial-gradient(700px 360px at 50% -20%,rgba(216,184,115,.34),transparent 60%); }
.biz-free-card > *{ position:relative; z-index:1; }
.biz-free-pill{ display:inline-flex; align-items:center; gap:8px; font-family:var(--bk-font-ui); font-weight:700; font-size:.78rem; color:var(--bk-gold-ink); background:var(--bk-gold); padding:7px 16px; border-radius:var(--bk-r-pill); box-shadow:var(--bk-shadow-accent); }
.biz-free-pill svg{ width:15px; height:15px; }
.biz-free-price{ display:flex; align-items:baseline; justify-content:center; gap:10px; margin:var(--bk-s6) 0 var(--bk-s3); }
.biz-free-price .amt{ font-family:var(--bk-font-display); font-weight:800; font-size:clamp(3.4rem,9vw,6rem); line-height:.9; color:#fff; }
.biz-free-price .old{ font-family:var(--bk-font-ui); font-size:1.1rem; color:rgba(255,255,255,.6); text-decoration:line-through; }
.biz-free-card h2{ color:#fff; font-size:var(--bk-fs-h2); }
.biz-free-card p{ font-family:var(--bk-font-ui); color:rgba(255,255,255,.9); font-size:var(--bk-fs-lead); max-width:52ch; margin:14px auto 0; line-height:1.7; }
.biz-free-feats{ display:flex; flex-wrap:wrap; justify-content:center; gap:10px 14px; margin:var(--bk-s8) 0 0; }
.biz-free-feats span{ display:inline-flex; align-items:center; gap:7px; font-family:var(--bk-font-ui); font-size:.86rem; color:#fff; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); padding:8px 14px; border-radius:var(--bk-r-pill); }
.biz-free-feats svg{ width:15px; height:15px; color:var(--bk-gold); }
.biz-free-card .biz-free-cta{ margin-top:var(--bk-s8); display:flex; flex-wrap:wrap; gap:14px; justify-content:center; }
.biz-free-card .bkf-btn-primary{ background:var(--bk-grad-gold); color:var(--bk-gold-ink); }
.biz-free-card .bkf-btn-ghost{ color:#fff; border-color:rgba(255,255,255,.5); }
.biz-free-card .bkf-btn-ghost:hover{ background:rgba(255,255,255,.14); border-color:#fff; }
.biz-spark{ position:absolute; z-index:0; width:9px; height:9px; border-radius:50%; background:var(--bk-gold); box-shadow:0 0 12px 2px rgba(216,184,115,.8); opacity:0; animation:biz-spark 3s ease-in-out infinite; }
@keyframes biz-spark{ 0%,100%{ opacity:0; transform:scale(.4);} 50%{ opacity:1; transform:scale(1);} }

/* ══════════════  FAQ (modern accordion)  ══════════════ */
.biz-faq{ max-width:760px; margin:var(--bk-s12) auto 0; display:flex; flex-direction:column; gap:12px; }
.biz-q{ background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); overflow:hidden; transition:border-color var(--bk-t) ease, box-shadow var(--bk-t) ease, background var(--bk-t) ease; }
.biz-q:hover{ border-color:color-mix(in srgb,var(--bk-gold) 30%,var(--bk-border)); }
.biz-q[open]{ border-color:color-mix(in srgb,var(--bk-gold) 45%,var(--bk-border)); box-shadow:var(--bk-shadow-sm); }
.biz-q summary{ list-style:none; cursor:pointer; padding:20px 22px; display:flex; align-items:center; gap:14px; font-family:var(--bk-font-ui); font-weight:600; font-size:1.02rem; color:var(--bk-text); transition:color var(--bk-t) ease; }
.biz-q summary::-webkit-details-marker{ display:none; }
.biz-q summary:hover{ color:var(--bk-accent); }
.biz-q[open] summary{ color:var(--bk-accent); }
.biz-q summary .chev{ margin-inline-start:auto; display:grid; place-items:center; width:30px; height:30px; border-radius:50%; background:var(--bk-accent-wash); color:var(--bk-gold-strong); transition:transform var(--bk-t) var(--bk-spring), background var(--bk-t) ease; flex-shrink:0; }
.biz-q[open] summary .chev{ transform:rotate(180deg); background:var(--bk-gold-soft); }
.biz-q-a{ padding:0 22px 20px; font-family:var(--bk-font-ui); font-size:.92rem; color:var(--bk-text-muted); line-height:1.72; }
.biz-q[open] .biz-q-a{ animation:biz-faq-reveal .34s var(--bk-ease); }
@keyframes biz-faq-reveal{ from{ opacity:0; transform:translateY(-8px); } to{ opacity:1; transform:none; } }
@media (prefers-reduced-motion:reduce){ .biz-q[open] .biz-q-a{ animation:none; } }

/* ══════════════  FINAL CTA  ══════════════ */
/* Final offer card: photo on an accent panel + the free offer & CTA (GlowRez pricing-card layout, on-brand olive/gold) */
.biz-offer{ position:relative; display:grid; grid-template-columns:.92fr 1.08fr; gap:clamp(24px,4vw,56px); align-items:center;
  padding:clamp(24px,4vw,56px); border-radius:var(--bk-r-2xl); background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg); overflow:hidden; }
.biz-offer::before{ content:""; position:absolute; inset:0; background:var(--bk-grad-hero); pointer-events:none; }
.biz-offer > *{ position:relative; z-index:1; }
.biz-offer-visual{ position:relative; display:grid; place-items:center; padding:clamp(8px,1.6vw,22px); }
.biz-offer-panel{ position:absolute; z-index:0; width:84%; height:88%; border-radius:var(--bk-r-2xl);
  background:linear-gradient(150deg,var(--bk-accent-wash),var(--bk-gold-soft) 92%); inset-block-end:4%; inset-inline-start:4%; }
.biz-offer-photo{ position:relative; z-index:1; width:min(100%,320px); aspect-ratio:377/468; object-fit:cover;
  border-radius:var(--bk-r-xl); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-xl); }
.biz-offer-body h2{ font-size:var(--bk-fs-h2); line-height:1.12; }
.biz-offer-body h2 .em{ color:var(--bk-accent); font-style:italic; }
.biz-offer-price{ display:flex; align-items:baseline; gap:12px; flex-wrap:wrap; margin:14px 0 4px; }
.biz-offer-price .amt{ font-family:var(--bk-font-display); font-weight:800; font-style:italic; font-size:clamp(2.6rem,5vw,3.7rem); color:var(--bk-gold-strong); line-height:1; }
.biz-offer-price .per{ font-family:var(--bk-font-ui); font-size:.95rem; color:var(--bk-text-muted); }
.biz-offer-cta{ margin-top:22px; }
.biz-offer-fine{ list-style:none; margin:24px 0 0; padding:0; display:flex; flex-direction:column; gap:11px; }
.biz-offer-fine li{ display:flex; align-items:center; gap:10px; font-family:var(--bk-font-ui); font-size:.93rem; color:var(--bk-text-soft); }
.biz-offer-fine li .ck{ width:22px; height:22px; border-radius:7px; flex-shrink:0; display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.biz-offer-fine li .ck svg{ width:13px; height:13px; }
.biz-offer-note{ margin-top:18px; font-family:var(--bk-font-ui); font-size:.78rem; color:var(--bk-text-muted); }

/* ══════════════  BACK TO TOP  ══════════════ */
.biz-totop{ position:fixed; z-index:60; inset-block-end:clamp(18px,4vw,30px); inset-inline-end:clamp(18px,4vw,30px);
  width:46px; height:46px; border-radius:50%; display:grid; place-items:center; padding:0;
  background:var(--bk-accent); color:#fff; border:1px solid color-mix(in srgb,#000 8%,var(--bk-accent));
  box-shadow:var(--bk-shadow-lg); cursor:pointer;
  opacity:0; visibility:hidden; transform:translateY(14px) scale(.85);
  transition:opacity .28s var(--bk-ease), transform .28s var(--bk-spring), visibility .28s, background .2s ease; }
.biz-totop.is-show{ opacity:1; visibility:visible; transform:none; }
.biz-totop:hover{ background:var(--bk-accent-hover); transform:translateY(-3px); }
.biz-totop:focus-visible{ outline:3px solid var(--bk-gold); outline-offset:2px; }
.biz-totop svg{ width:22px; height:22px; }
@media (prefers-reduced-motion:reduce){ .biz-totop{ transition:opacity .2s ease, visibility .2s; transform:none; } .biz-totop.is-show{ transform:none; } }

/* ══════════════  RESPONSIVE  ══════════════ */
@media (max-width:1024px){
  .biz-problem-grid,.biz-why-grid{ grid-template-columns:repeat(2,1fr); }
  .biz-bento{ grid-template-columns:repeat(2,1fr); } .biz-cell.wide{ grid-column:span 2; }
}
@media (max-width:900px){
  /* GlowRez mobile: dark copy on top, backdrop melts into the warm base, a big phone bridges into the next section */
  /* reserve bottom room so the front card (which hangs below the back one) stays inside the hero */
  /* mobile: single column + dark brand base so the white copy stays readable (home-hero-img is hidden here) */
  .biz-hero{ min-height:auto; display:flex; flex-direction:column; position:relative; overflow:visible;
    padding-block:calc(var(--bk-nav-h) + 22px) clamp(30px,7vw,52px);
    background:linear-gradient(180deg,#141a0c 0%,#1b2113 60%,#232a17 100%); }
  .biz-hero-grid{ grid-template-columns:1fr; }
  /* hide home-hero-img entirely on tablet/mobile */
  .biz-hero-photo{ display:none !important; }
  /* calendar: centered, right under the copy — close, not far down */
  .biz-stage{ position:relative; z-index:3; justify-content:center; margin:clamp(6px,2.4vw,16px) auto 0; margin-inline:auto; min-height:auto; }
  .biz-stack{ width:min(300px,74vw); margin-inline:auto; }
  .biz-trusted{ padding-top:clamp(28px,6vw,44px); }
  .biz-float{ display:none; }   /* declutter hero on mobile */
  .biz-feature,.biz-feature.rev .biz-feature-text{ grid-template-columns:1fr; order:0; }
  .biz-feature-visual{ order:-1; }
  .biz-tt-grid{ grid-template-columns:1fr; }
  .biz-offer{ grid-template-columns:1fr; text-align:center; }
  .biz-offer-price{ justify-content:center; }
  .biz-offer-fine{ align-items:center; }
  .biz-offer-visual{ order:-1; }
  .biz-trust-stats{ gap:20px 12px; }
  .biz-collage-photo{ position:relative; width:70%; inset:auto; margin:16px auto -30px; }
  .biz-feature:not(.rev) .biz-collage-photo,.biz-feature.rev .biz-collage-photo{ inset-inline:auto; }
}
@media (max-width:560px){
  .biz-problem-grid,.biz-why-grid,.biz-bento{ grid-template-columns:1fr; } .biz-cell.wide{ grid-column:auto; }
  .biz-hero-note{ gap:6px 12px; }
}
@media (prefers-reduced-motion:reduce){
  .biz-aurora,.biz-float,.biz-marquee-track,.biz-spark,.biz-stack,.biz-stack-bg img,.biz-shot-blob,.biz-shot-ring,.biz-motif{ animation:none !important; }
  .biz-word,.biz-slot,.biz-bubble,.biz-stack{ opacity:1 !important; transform:none !important; filter:none !important; animation:none !important; }
  .biz-stack-bg img{ transform:rotate(var(--rot)) !important; }
  .biz-hero h1 .gold::after{ animation:none !important; transform:scaleX(1) !important; }
  .biz-progress{ display:none; }
}
</style>
</x-slot:styles>

<div class="biz-progress" data-biz-progress></div>

{{-- ═══════════════  1 · HERO  ═══════════════ --}}
<section class="biz-hero">
  <div class="biz-hero-bg"></div>
  <div class="biz-aurora" data-parallax="0.06"></div>
  <div class="biz-glow g1" data-parallax="0.10"></div>
  <div class="biz-glow g2" data-parallax="0.08"></div>

  <div class="biz-hero-photo">
    <img src="{{ $img('home-hero-img.JPG') }}" alt="{{ $t('سبا ومركز تجميل وصالون','Spa, beauty studio & salon') }}" width="800" height="1000" loading="eager" fetchpriority="high" decoding="async">
  </div>

  <div class="bkf-container-wide">
    <div class="biz-hero-grid">
      <div class="biz-hero-text">
        <span class="bkf-badge-gold"><x-icon name="gift" :size="14"/>{{ $t('مجاني بالكامل الآن', 'Completely free right now') }}</span>
        <h1>
          <span class="biz-word" style="animation-delay:.05s">{{ $t('مواعيدك','Your calendar,') }}</span>
          <span class="biz-word em" style="animation-delay:.18s">{{ $t('مرتّبة.','sorted.') }}</span><br>
          <span class="biz-word" style="animation-delay:.32s">{{ $t('يومك','Your day,') }}</span>
          <span class="biz-word gold" style="animation-delay:.46s">{{ $t('ممتلئ.','full.') }}</span>
        </h1>
        <p class="biz-hero-sub">{{ $t('استقبل الحجوزات على مدار الساعة عبر صفحة حجز ورمز QR خاصّين بمنشأتك، وذكّر عملائك تلقائيًا بواتساب فيقلّ الغياب. ودّع الدفاتر والمكالمات.', 'Take bookings around the clock through your own booking page and QR code, and auto-remind clients on WhatsApp so no-shows drop. Say goodbye to notebooks and phone calls.') }}</p>
        <div class="biz-hero-cta">
          @if(config('leads.browse_only'))<a href="{{ route('leads.join') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg" data-join-cta>{{ $t('سجّل اهتمامك','Register your interest') }}<x-icon name="arrow-right" :size="19"/></a>@else<a href="{{ route('company.register') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg">{{ $t('ابدأ مجانًا الآن','Start free now') }}<x-icon name="arrow-right" :size="19"/></a>@endif
          @unless(config('leads.browse_only'))<a href="{{ route('leads.join') }}" class="bkf-btn bkf-btn-soft bkf-btn-lg" data-join-cta="hero">{{ $t('مهتم بـ GlowRez؟ سجّل اهتمامك','Interested in GlowRez? Register your interest') }}</a>@endunless
          {{-- <a href="#solution" class="bkf-btn bkf-btn-soft bkf-btn-lg"><x-icon name="play" :size="18"/>{{ $t('شاهد كيف يعمل','See how it works') }}</a> --}}
        </div>
        <div class="biz-hero-note">
          <span><x-icon name="check-circle" :size="16"/>{{ $t('بدون بطاقة','No card needed') }}</span>
          <span><x-icon name="check-circle" :size="16"/>{{ $t('جاهز خلال دقائق','Ready in minutes') }}</span>
          <span><x-icon name="check-circle" :size="16"/>{{ $t('بالعربية بالكامل','Fully in Arabic') }}</span>
        </div>
      </div>

      <div class="biz-stage">
        <div class="biz-stack">
              <figure class="biz-stack-bg">
                  <img src="{{ $img('hero-calendar.JPG') }}"
                        alt="{{ $t('تقويم مواعيد GlowRez — يومك في نظرة واحدة','GlowRez appointments calendar — your day at a glance') }}"
                        width="653"
                        height="864"
                        loading="eager"
                        decoding="async">
              </figure>
        </div>
      </div>
    </div>
  </div>

  
</section>

{{-- ═══════════════  2 · TRUSTED MARQUEE + STATS  ═══════════════ --}}
<section class="biz-trusted">
  <div class="biz-trusted-lbl">{{ $t('صُمّم لكل أماكن الجمال والعناية', 'Built for every beauty & wellness business') }}</div>
  <div class="biz-marquee">
    <div class="biz-marquee-track">
      @foreach(array_merge($bizTypes, $bizTypes) as $b)
        <span class="biz-tt-pill"><x-icon :name="$b['icon']" :size="18"/>{{ $t($b['ar'], $b['en']) }}</span>
      @endforeach
    </div>
  </div>

  <div class="bkf-container-wide">
    <div class="biz-trust-stats bkf-reveal">
      <div class="biz-ts"><div class="n">24 / 7</div><div class="l">{{ $t('حجز ذاتي متاح دائمًا','Self-booking, always on') }}</div></div>
      <div class="biz-ts"><div class="n"><span data-count="60">60</span><span class="biz-unit">{{ $t('ث','s') }}</span></div><div class="l">{{ $t('لإتمام الحجز','to complete a booking') }}</div></div>
      <div class="biz-ts"><div class="n"><span data-count="40">40</span><span class="biz-unit">{{ $t('٪','%') }}</span></div><div class="l">{{ $t('غياب أقل مع التذكير','fewer no-shows with reminders') }}</div></div>
    </div>
  </div>
</section>

{{-- ═══════════════  4 · HOW BOOKSY WORKS · APPOINTMENT DEEP-DIVES  ═══════════════ --}}
<section class="bkf-section" id="features">
  <div class="bkf-container-wide">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('كيف يعمل غلوريز','How GlowRez works') }}</span>
      <h2 class="bkf-title">{{ $t('كل ما تحتاجه لإدارة','Everything you need to run') }} <span class="em">{{ $t('مواعيدك في مكان واحد','your bookings in one place') }}</span></h2>
      <p class="bkf-lead">{{ $t('من التقويم إلى صفحة الحجز إلى التذكير — ثلاث خطوات بسيطة تُبقي مواعيدك ممتلئة ويومك منظّمًا دون أي جهد يدوي.','From your calendar to your booking page to reminders — three simple steps that keep your schedule full and your day organised, with zero manual work.') }}</p>
    </div>

    <div class="biz-features">
      @foreach($deep as $i => $d)
      <div class="biz-feature{{ $d['rev'] ? ' rev' : '' }}{{ ($d['big'] ?? false) ? ' is-big' : '' }}">
        <div class="biz-feature-text bkf-reveal" data-reveal="{{ $d['rev'] ? 'right' : 'left' }}">
          <span class="biz-feature-eyebrow">{{ $d['eyebrow'] }}</span>
          <h2>{{ $t($d['ar'], $d['en']) }}</h2>
          <p class="lead">{{ $t($d['lead_ar'], $d['lead_en']) }}</p>
          <ul class="biz-feature-pts">
            @foreach(($isAr ? $d['pts_ar'] : $d['pts_en']) as $p)
              <li><span class="ck"><x-icon name="check" :size="15"/></span>{{ $p }}</li>
            @endforeach
          </ul>
        
        </div>

        <div class="biz-feature-visual bkf-reveal" data-reveal="{{ $d['rev'] ? 'left' : 'right' }}">
          <div class="biz-shot">
           
            <div class="biz-shot-frame{{ ($d['big'] ?? false) ? ' is-big' : '' }}">
              <img src="{{ $img($d['shot']) }}" alt="{{ $t($d['alt_ar'], $d['alt_en']) }}"
                   width="{{ $d['sw'] }}" height="{{ $d['sh'] }}" loading="lazy" decoding="async">
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════  7 · COMING SOON — the street  ═══════════════
     One shop is open (bookings); the others are about to open behind their shutters. --}}
@php
  $mods = array_merge([[
      'ar' => 'المواعيد والحجز', 'en' => 'Bookings', 'icon' => 'calendar', 'live' => true,
      'dar' => 'صفحة حجز عامة، رمز QR، تقويم بلا تعارض، وتذكير واتساب تلقائي.',
      'den' => 'A public booking page, QR code, conflict-free calendar and automatic WhatsApp reminders.',
  ]], $soon);
@endphp
<section class="bkf-section" id="soon">
  <div class="bkf-container-wide">
    <div class="bkf-head-center bkf-reveal">
      <h2 class="bkf-title bkf-mt-0">{{ $t('نبدأ بالمواعيد','We start with bookings') }} <span class="em">{{ $t('والباقي في الطريق','the rest is on the way') }}</span></h2>
      <p class="bkf-lead">{{ $t('قريبًا جدًا: نطلق غلوريز ميزة تلو الأخرى لنتقن كل واحدة.','Very soon: we release GlowRez one feature at a time, to perfect each one.') }}</p>
    </div>

    <ul class="st-street bkf-reveal">
      @foreach($mods as $i => $m)
      <li class="st-shop{{ !empty($m['live']) ? ' is-live' : '' }}" @if(empty($m['live'])) tabindex="0" @endif>
        <div class="st-front">
          <div class="st-awning" aria-hidden="true"></div>
          <div class="st-sign"><h3>{{ $t($m['ar'], $m['en']) }}</h3></div>
          <div class="st-body" aria-hidden="true">
            <div class="st-win"><x-icon :name="$m['icon']" :size="42" :stroke="1.5"/></div>
            <div class="st-door"></div>
            <span class="st-pot"></span>
            @if(!empty($m['live']))
              <span class="st-tag is-open">{{ $t('متاح الآن','Open now') }}</span>
            @else
              <div class="st-shutter"><span class="st-tag">{{ $t('قريبًا','Soon') }}</span></div>
            @endif
          </div>
          <div class="st-ground" aria-hidden="true"></div>
        </div>
        <p class="st-desc"><span class="st-sr">{{ !empty($m['live']) ? $t('متاح الآن. ','Open now. ') : $t('قريبًا. ','Coming soon. ') }}</span>{{ $t($m['dar'], $m['den']) }}</p>
      </li>
      @endforeach
    </ul>
    <p class="st-hint" aria-hidden="true"><x-icon name="arrow-right" :size="16"/>{{ $t('اسحب لترى الباقي','Swipe to see the rest') }}</p>

    <div class="st-foot bkf-reveal">
      @if(config('leads.browse_only'))<a href="{{ route('leads.join') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg" data-join-cta>{{ $t('سجّل اهتمامك','Register your interest') }}<x-icon name="arrow-right" :size="19"/></a>@else<a href="{{ route('company.register') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg">{{ $t('ابدأ الآن','Get started') }}<x-icon name="arrow-right" :size="19"/></a>@endif
      <p>{{ $t('مجاني لفترة محدودة، ومن ينضمّ الآن يحصل على الوحدات القادمة أولًا.','Free for a limited time. Join now and get the upcoming modules first.') }}</p>
    </div>
  </div>
</section>

{{-- ═══════════════  8 · WHY SWITCH  ═══════════════ --}}
<section class="biz-why bkf-section" id="why">
  <div class="bkf-container-wide">
    <div class="bkf-head-center bkf-reveal">
      <span class="biz-head-motifs" aria-hidden="true">
        <span class="biz-motif m1"><x-icon name="scissors" :size="88" :stroke="1.2"/></span>
        <span class="biz-motif m2"><x-icon name="sparkles" :size="74" :stroke="1.2"/></span>
        <span class="biz-motif m3"><x-icon name="star" :size="58" :stroke="1.2"/></span>
      </span>
      <span class="bkf-eyebrow is-center">{{ $t('لماذا غلوريز','Why GlowRez') }}</span>
      <h2 class="bkf-title">{{ $t('لماذا تبدأ','Why start') }} <span class="em">{{ $t('معنا اليوم','with us today') }}</span></h2>
      <p class="bkf-lead">{{ $t('أربعة أسباب تجعل غلوريز الخيار الأذكى لمنشأتك من اليوم الأول — بلا مخاطرة وبلا تكلفة.','Four reasons GlowRez is the smart choice for your business from day one — no risk, no cost.') }}</p>
    </div>
    <div class="biz-why-grid">
      @foreach($whySwitch as $i => $w)
      <div class="biz-why-card bkf-reveal bkf-reveal-d{{ $i+1 }}">
        <div class="biz-why-ic"><x-icon :name="$w['icon']" :size="22"/></div>
        <h3>{{ $t($w['ar'], $w['en']) }}</h3>
        <p>{{ $t($w['dar'], $w['den']) }}</p>
      </div>
      @endforeach
    </div>
    <div class="biz-why-cta bkf-reveal">
      @if(config('leads.browse_only'))<a href="{{ route('leads.join') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg" data-join-cta>{{ $t('سجّل اهتمامك','Register your interest') }}<x-icon name="arrow-right" :size="19"/></a>@else<a href="{{ route('company.register') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg">{{ $t('ابدأ معنا اليوم مجانًا','Start with us today — free') }}<x-icon name="arrow-right" :size="19"/></a>@endif
    </div>
  </div>
</section>

{{-- ═══════════════  9 · TESTIMONIALS  ═══════════════ --}}
<section class="bkf-section" id="testimonials">
  <div class="bkf-container-wide">
    <div class="bkf-head-center bkf-reveal">
      <span class="bkf-eyebrow is-center">{{ $t('قصص نجاح','Success stories') }}</span>
      <h2 class="bkf-title">{{ $t('أصحاب مراكز وصالونات','Beauty & wellness owners') }} <span class="em">{{ $t('مثلك تمامًا','just like you') }}</span></h2>
    </div>
    <div class="biz-tt-grid">
      @foreach($testi as $i => $c)
      <div class="biz-tcard bkf-reveal bkf-reveal-d{{ $i+1 }}">
        <div class="quo"><x-icon name="quote" :size="34"/></div>
        <div class="stars">@for($s=0;$s<5;$s++)<x-icon name="star-fill" :size="15"/>@endfor</div>
        <p class="biz-tcard-q">{{ $t($c['q_ar'], $c['q_en']) }}</p>
        <div class="biz-tcard-au">
          <div class="biz-tcard-av">{{ $c['in'] }}</div>
          <div>
            <div class="biz-tcard-nm">{{ $t($c['nm'], $c['nm_en']) }}</div>
            <div class="biz-tcard-rl">{{ $t($c['rl_ar'], $c['rl_en']) }}</div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════  11 · FAQ  ═══════════════ --}}
<section class="bkf-section" id="faq" style="background:var(--bk-surface)">
  <div class="bkf-container-wide">
    <div class="bkf-head-center bkf-reveal">
      <span class="biz-head-motifs" aria-hidden="true">
        <span class="biz-motif m1"><x-icon name="sparkles" :size="88" :stroke="1.2"/></span>
        <span class="biz-motif m2"><x-icon name="star" :size="74" :stroke="1.2"/></span>
        <span class="biz-motif m3"><x-icon name="scissors" :size="58" :stroke="1.2"/></span>
      </span>
      <span class="bkf-eyebrow is-center">{{ $t('أسئلة شائعة','FAQ') }}</span>
      <h2 class="bkf-title">{{ $t('كل ما تريد','Everything you') }} <span class="em">{{ $t('معرفته','need to know') }}</span></h2>
    </div>
    <div class="biz-faq">
      @foreach($faqs as $i => $q)
      <details class="biz-q bkf-reveal" {{ $i === 0 ? 'open' : '' }}>
        <summary>{{ $t($q['ar'], $q['en']) }}<span class="chev"><x-icon name="chevron-down" :size="20"/></span></summary>
        <div class="biz-q-a">{{ $t($q['aar'], $q['aen']) }}</div>
      </details>
      @endforeach
    </div>
  </div>
</section>

{{-- ═══════════════  12 · FINAL CTA · FREE OFFER  ═══════════════ --}}
<section class="bkf-section" id="free">
  <div class="bkf-container-wide">
    <div class="biz-offer bkf-reveal" data-reveal="scale">
      <div class="biz-offer-visual">
        <span class="biz-offer-panel" aria-hidden="true"></span>
        <img class="biz-offer-photo" src="{{ $img('65ee13e9c8f322f7a8ce7c37_0814f24f9b69707e5aba5a9928b038cc_prix.avif') }}"
             alt="{{ $t('صاحبة مركز تجميل تدير أعمالها مع غلوريز','Beauty centre owner running her business with GlowRez') }}"
             width="377" height="468" loading="lazy" decoding="async">
      </div>
      <div class="biz-offer-body">
        <h2>{{ $t('كل الميزات','Every feature') }} <span class="em">{{ $t('مشمولة','included') }}</span></h2>
        <div class="biz-offer-price">
          <span class="amt">{{ $t('مجانًا','Free') }}</span>
          <span class="per">{{ $t('الآن · بدون بطاقة','right now · no card') }}</span>
        </div>
        <div class="biz-offer-cta" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
          @if(config('leads.browse_only'))<a href="{{ route('leads.join') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg" data-join-cta>{{ $t('سجّل اهتمامك','Register your interest') }}<x-icon name="arrow-right" :size="19"/></a>@else<a href="{{ route('company.register') }}" class="bkf-btn bkf-btn-primary bkf-btn-lg">{{ $t('ابدأ مجانًا الآن','Start free now') }}<x-icon name="arrow-right" :size="19"/></a>@endif
          @unless(config('leads.browse_only'))<a href="{{ route('leads.join') }}" class="bkf-btn bkf-btn-soft bkf-btn-lg" data-join-cta="offer">{{ $t('مهتم بـ GlowRez؟ سجّل اهتمامك','Interested in GlowRez? Register your interest') }}</a>@endunless
          @if($waLead = \App\Support\LeadContact::whatsappUrl())
            <a href="{{ $waLead }}" target="_blank" rel="noopener" class="bkf-btn bkf-btn-ghost bkf-btn-lg" data-wa="business">{{ $t('تحدّث معنا على واتساب','Talk to us on WhatsApp') }}</a>
          @endif
        </div>
        <ul class="biz-offer-fine">
          <li><span class="ck"><x-icon name="check" :size="13"/></span>{{ $t('صفحة حجز ورمز QR','Booking page & QR') }}</li>
          <li><span class="ck"><x-icon name="check" :size="13"/></span>{{ $t('تقويم بلا تعارض','Conflict-free calendar') }}</li>
          <li><span class="ck"><x-icon name="check" :size="13"/></span>{{ $t('تذكير واتساب تلقائي','Automatic WhatsApp reminders') }}</li>
          <li><span class="ck"><x-icon name="check" :size="13"/></span>{{ $t('عدد مواعيد غير محدود','Unlimited bookings') }}</li>
        </ul>
        <p class="biz-offer-note">{{ $t('نحن في مرحلة الإطلاق، فالاستخدام مجاني بالكامل الآن. المالية والتقارير قريبًا.','We’re in launch, so it’s completely free right now. Finance and reports coming soon.') }}</p>
      </div>
    </div>
  </div>
</section>

<button type="button" class="biz-totop" data-totop aria-label="{{ $t('العودة إلى أعلى الصفحة','Back to top') }}">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<x-slot:scripts>
<script>
(function(){
  var bar = document.querySelector('[data-biz-progress]');
  if(!bar) return;
  var reduce = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
  if(reduce){ return; }
  var ticking = false;
  function update(){
    var h = document.documentElement.scrollHeight - window.innerHeight;
    var p = h > 0 ? Math.min(window.scrollY / h, 1) : 0;
    bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
    ticking = false;
  }
  window.addEventListener('scroll', function(){ if(!ticking){ ticking = true; requestAnimationFrame(update); } }, { passive:true });
  window.addEventListener('resize', update, { passive:true });
  update();
})();

// Back-to-top: show after scrolling down, smooth-scroll to top
(function(){
  var btn = document.querySelector('[data-totop]');
  if(!btn) return;
  var reduce = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
  function toggle(){ btn.classList.toggle('is-show', window.scrollY > 600); }
  window.addEventListener('scroll', toggle, { passive:true });
  toggle();
  btn.addEventListener('click', function(){ window.scrollTo({ top:0, behavior: reduce ? 'auto' : 'smooth' }); });
})();
</script>
</x-slot:scripts>

</x-front.layout>
