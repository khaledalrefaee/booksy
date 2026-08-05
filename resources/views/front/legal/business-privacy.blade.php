@php $isAr = app()->getLocale() === 'ar'; $updated = '2026-08-01'; @endphp
{{-- ⚠️ STRUCTURE ONLY — NOT FINAL LEGAL TEXT.
     Business/SaaS privacy notice for subscribing businesses (distinct from the
     consumer privacy notice at /privacy). Placeholders outline what each
     section must cover; have local legal counsel draft the binding text. --}}
<x-front.layout :title="($isAr ? 'خصوصية الأعمال' : 'Business Privacy') . ' | GlowRez'"
                :mapFab="false"
                :description="$isAr ? 'سياسة خصوصية الأعمال في GlowRez — معالجة بيانات الشركات المشتركة.' : 'GlowRez Business Privacy Policy — how we handle data for partner businesses.'">
    <x-slot:styles>@include('front.legal._css')</x-slot:styles>

    <section class="bkf-legal">
      <div class="bkf-legal-wrap">
        <div class="bkf-legal-eyebrow">{{ $isAr ? 'قانوني — للأعمال' : 'Legal — Business' }}</div>
        <h1>{{ $isAr ? 'سياسة خصوصية الأعمال' : 'Business Privacy Policy' }}</h1>
        <p class="bkf-legal-updated">{{ $isAr ? 'آخر تحديث: ' : 'Last updated: ' }}{{ \Illuminate\Support\Carbon::parse($updated)->translatedFormat('d F Y') }}</p>

        <nav class="bkf-legal-toc">
          <a href="#business-data">{{ $isAr ? 'بيانات نشاطك' : 'Your business data' }}</a>
          <a href="#roles">{{ $isAr ? 'الأدوار' : 'Data roles' }}</a>
          <a href="#customer-data">{{ $isAr ? 'بيانات عملائك' : 'Your customers’ data' }}</a>
          <a href="#subprocessors">{{ $isAr ? 'المعالِجون الفرعيون' : 'Sub-processors' }}</a>
          <a href="#security">{{ $isAr ? 'الأمان والاحتفاظ' : 'Security & retention' }}</a>
          <a href="#rights">{{ $isAr ? 'حقوقك' : 'Your rights' }}</a>
          <a href="#contact">{{ $isAr ? 'تواصل' : 'Contact' }}</a>
        </nav>

        <div class="bkf-legal-body">
          <p>
            {{ $isAr
              ? 'توضّح هذه السياسة كيف نعالج بيانات النشاط التجاري المشترك في المنصّة، وكيف نتعامل مع بيانات عملائه التي تُدار عبر المنصّة. لعملاء الحجز راجع سياسة الخصوصية العامة.'
              : 'This notice explains how we handle the data of businesses subscribing to the platform, and how we treat the customer data managed through it. Booking customers should see the general privacy policy.' }}
            <a href="{{ route('front.privacy') }}" target="_blank" rel="noopener">{{ $isAr ? 'سياسة خصوصية العملاء' : 'Consumer privacy policy' }}</a>.
          </p>

          <h2 id="business-data">{{ $isAr ? '١. بيانات نشاطك التي نجمعها' : '1. Business data we collect' }}</h2>
          <ul>
            <li>{{ $isAr ? '[هيكل] اسم المسؤول، البريد، الهاتف، اسم النشاط ونوعه.' : '[Structure] Owner name, email, phone, business name and type.' }}</li>
            <li>{{ $isAr ? '[هيكل] بيانات الاشتراك والفوترة.' : '[Structure] Subscription and billing data.' }}</li>
            <li>{{ $isAr ? '[هيكل] بيانات استخدام تقنية للأمان وتحسين الخدمة.' : '[Structure] Technical usage data for security and service improvement.' }}</li>
          </ul>

          <h2 id="roles">{{ $isAr ? '٢. أدوار البيانات (متحكّم/معالِج)' : '2. Data roles (controller / processor)' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] بالنسبة لبيانات نشاطك نحن «المتحكّم». أمّا بيانات عملائك التي تُدخلها للمنصّة فأنت «المتحكّم» ونحن «المعالِج».'
            : '[Structure] For your business data we are the “controller”. For your customers’ data that you enter into the platform, you are the “controller” and we are the “processor”.' }}</p>

          <h2 id="customer-data">{{ $isAr ? '٣. بيانات عملائك' : '3. Your customers’ data' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] نعالج بيانات عملائك (الاسم، الهاتف، الحجوزات، الملاحظات) فقط لتشغيل الخدمة بالنيابة عنك ووفق تعليماتك. لا نستخدمها لأغراضنا الخاصة ولا نبيعها.'
            : '[Structure] We process your customers’ data (name, phone, bookings, notes) only to run the service on your behalf and per your instructions. We do not use it for our own purposes and do not sell it.' }}</p>

          <h2 id="subprocessors">{{ $isAr ? '٤. المعالِجون الفرعيون' : '4. Sub-processors' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] قد نستعين بمزوّدي خدمة (استضافة، رسائل SMS/واتساب) ملتزمين تعاقدياً بحماية البيانات. تُدرَج قائمتهم وتُحدَّث عند تغييرها.'
            : '[Structure] We may use service providers (hosting, SMS/WhatsApp) contractually bound to protect the data. Their list is maintained and updated when it changes.' }}</p>

          <h2 id="security">{{ $isAr ? '٥. الأمان والاحتفاظ والإخطار بالاختراق' : '5. Security, retention & breach notice' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] إجراءات أمنية معقولة، مدد احتفاظ محدّدة، وحذف البيانات بعد إغلاق الحساب ضمن مهلة، مع الالتزام بإخطارك عند أي اختراق يؤثّر على بياناتك.'
            : '[Structure] Reasonable security measures, defined retention periods, deletion of data after account closure within a window, and a commitment to notify you of any breach affecting your data.' }}</p>

          <h2 id="rights">{{ $isAr ? '٦. حقوقك' : '6. Your rights' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] الوصول إلى بياناتك وتصحيحها وتصديرها وطلب حذف الحساب، من لوحة التحكم أو بالتواصل معنا.'
            : '[Structure] Access, correct, export your data, and request account deletion — from the dashboard or by contacting us.' }}</p>

          <h2 id="contact">{{ $isAr ? '٧. تواصل معنا' : '7. Contact us' }}</h2>
          <p>
            {{ $isAr ? 'لأي سؤال حول خصوصية الأعمال، ' : 'For any business-privacy question, ' }}
            <a href="{{ route('front.contact') }}">{{ $isAr ? 'راسلنا من هنا' : 'reach us here' }}</a>.
          </p>

          <div class="bkf-legal-note">
            {{ $isAr
              ? '⚠️ هذه الصفحة هيكل مبدئي وليست نصّاً قانونياً نهائياً. يجب أن يراجعها ويصيغها محامٍ مختص (ويُفضَّل إرفاق اتفاقية معالجة بيانات DPA) قبل الاعتماد عليها.'
              : '⚠️ This page is a preliminary structure, not final legal text. It must be drafted and reviewed by qualified legal counsel (ideally with a Data Processing Agreement) before you rely on it.' }}
          </div>
        </div>
      </div>
    </section>
</x-front.layout>
