@php $isAr = app()->getLocale() === 'ar'; $updated = '2026-08-01'; @endphp
{{-- ⚠️ STRUCTURE ONLY — NOT FINAL LEGAL TEXT.
     These are Business/SaaS Terms for salon & clinic owners who subscribe to
     the platform (distinct from the consumer terms at /terms). Each section is
     a placeholder outlining what the clause must cover. Have local legal
     counsel draft and review the binding text before launch. --}}
<x-front.layout :title="($isAr ? 'شروط خدمة الأعمال' : 'Business Terms of Service') . ' | GlowRez'"
                :mapFab="false"
                :description="$isAr ? 'شروط خدمة الأعمال في GlowRez للشركات والصالونات المشتركة.' : 'GlowRez Business Terms of Service for partner companies and salons.'">
    <x-slot:styles>@include('front.legal._css')</x-slot:styles>

    <section class="bkf-legal">
      <div class="bkf-legal-wrap">
        <div class="bkf-legal-eyebrow">{{ $isAr ? 'قانوني — للأعمال' : 'Legal — Business' }}</div>
        <h1>{{ $isAr ? 'شروط خدمة الأعمال' : 'Business Terms of Service' }}</h1>
        <p class="bkf-legal-updated">{{ $isAr ? 'آخر تحديث: ' : 'Last updated: ' }}{{ \Illuminate\Support\Carbon::parse($updated)->translatedFormat('d F Y') }}</p>

        <nav class="bkf-legal-toc">
          <a href="#service">{{ $isAr ? 'الخدمة والترخيص' : 'Service & licence' }}</a>
          <a href="#account">{{ $isAr ? 'الحساب والموظفون' : 'Account & staff' }}</a>
          <a href="#data">{{ $isAr ? 'بيانات العملاء' : 'Customer data' }}</a>
          <a href="#subscription">{{ $isAr ? 'الاشتراك والفوترة' : 'Subscription & billing' }}</a>
          <a href="#payments">{{ $isAr ? 'المدفوعات' : 'Payments' }}</a>
          <a href="#responsibilities">{{ $isAr ? 'مسؤولياتك' : 'Your responsibilities' }}</a>
          <a href="#ip">{{ $isAr ? 'الملكية الفكرية' : 'Intellectual property' }}</a>
          <a href="#availability">{{ $isAr ? 'التوفّر والنسخ الاحتياطي' : 'Availability & backups' }}</a>
          <a href="#liability">{{ $isAr ? 'حدود المسؤولية' : 'Liability' }}</a>
          <a href="#termination">{{ $isAr ? 'التعليق والإنهاء' : 'Suspension & termination' }}</a>
          <a href="#law">{{ $isAr ? 'القانون الحاكم' : 'Governing law' }}</a>
        </nav>

        <div class="bkf-legal-body">
          <p>
            {{ $isAr
              ? 'تنطبق هذه الشروط على النشاط التجاري (الصالون/المركز/العيادة) الذي يشترك في منصّة GlowRez لإدارة الحجوزات والأعمال. باستخدامك لوحة الأعمال فإنك توافق عليها نيابةً عن نشاطك.'
              : 'These terms apply to the business (salon / center / clinic) that subscribes to the GlowRez platform to manage bookings and operations. By using the business dashboard you accept them on behalf of your business.' }}
          </p>

          <h2 id="service">{{ $isAr ? '١. الخدمة وترخيص الاستخدام' : '1. The service & licence' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] GlowRez تُقدّم برمجية كخدمة (SaaS) لإدارة الحجوزات والفريق والعملاء. نمنحك ترخيصاً محدوداً وغير حصري وقابلاً للإلغاء لاستخدام المنصّة. لسنا شريكاً لك ولا صاحب عمل لموظفيك.'
            : '[Structure] GlowRez provides software-as-a-service (SaaS) to manage bookings, staff and customers. We grant you a limited, non-exclusive, revocable licence to use the platform. We are not your partner and not the employer of your staff.' }}</p>

          <h2 id="account">{{ $isAr ? '٢. الحساب والحسابات الفرعية للموظفين' : '2. Account & staff sub-accounts' }}</h2>
          <ul>
            <li>{{ $isAr ? '[هيكل] أنت مسؤول عن دقّة بيانات نشاطك والحفاظ على أمان الوصول.' : '[Structure] You are responsible for the accuracy of your business data and for keeping access secure.' }}</li>
            <li>{{ $isAr ? '[هيكل] أنت مسؤول عن كل حساب موظف تُنشئه وعن صلاحياته وسحبه عند انتهاء العلاقة.' : '[Structure] You are responsible for every staff account you create, its permissions, and revoking it when the relationship ends.' }}</li>
            <li>{{ $isAr ? '[هيكل] يجب أن تكون مخوّلاً قانونياً بتمثيل النشاط.' : '[Structure] You must be legally authorised to represent the business.' }}</li>
          </ul>

          <h2 id="data">{{ $isAr ? '٣. بيانات العملاء (المعالجة)' : '3. Customer data (processing)' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] أنت «المتحكّم» في بيانات عملائك، ونحن «المعالِج» الذي يعالجها بالنيابة عنك لتشغيل الخدمة. تُقرّ بأنك حصلت على الموافقات اللازمة من عملائك. قد نستعين بمعالِجين فرعيين (مثل مزوّدي الرسائل النصية/واتساب) ملتزمين بحماية البيانات. يجب صياغة اتفاقية معالجة بيانات (DPA) منفصلة.'
            : '[Structure] You are the “controller” of your customers’ data; we are the “processor” acting on your behalf to run the service. You confirm you have obtained the necessary consents from your customers. We may use sub-processors (e.g. SMS/WhatsApp providers) bound to protect the data. A separate Data Processing Agreement (DPA) should be drafted.' }}</p>

          <h2 id="subscription">{{ $isAr ? '٤. الاشتراك والفوترة' : '4. Subscription & billing' }}</h2>
          <ul>
            <li>{{ $isAr ? '[هيكل] الرسوم ودورة الفوترة والتجديد التلقائي والضرائب المطبّقة.' : '[Structure] Fees, billing cycle, auto-renewal, and applicable taxes.' }}</li>
            <li>{{ $isAr ? '[هيكل] سياسة التجربة المجانية والاسترجاع/الإلغاء (void).' : '[Structure] Free-trial, refund and void policy.' }}</li>
            <li>{{ $isAr ? '[هيكل] أثر عدم الدفع: تعليق الميزات المدفوعة بعد فترة سماح.' : '[Structure] Effect of non-payment: paid features suspended after a grace period.' }}</li>
          </ul>

          <h2 id="payments">{{ $isAr ? '٥. المدفوعات' : '5. Payments' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] إن جرى الدفع داخل الصالون نقداً، فالمنصّة لا تعالج مدفوعات العملاء. عند تفعيل الدفع الإلكتروني لاحقاً تُضاف بنود التسويات والاسترداد والـ chargebacks.'
            : '[Structure] Where payment happens in-venue (cash), the platform does not process customer payments. When online payments are enabled later, settlement, refund and chargeback terms will be added.' }}</p>

          <h2 id="responsibilities">{{ $isAr ? '٦. مسؤوليات النشاط' : '6. Business responsibilities' }}</h2>
          <ul>
            <li>{{ $isAr ? '[هيكل] دقّة الخدمات والأسعار وأوقات العمل المعروضة.' : '[Structure] Accuracy of listed services, prices and working hours.' }}</li>
            <li>{{ $isAr ? '[هيكل] أنت وحدك مسؤول عن الخدمات المقدَّمة داخل نشاطك وعن سياسات الإلغاء/عدم الحضور مع عملائك.' : '[Structure] You alone are responsible for services delivered in your venue and for your own cancellation / no-show policies with customers.' }}</li>
            <li>{{ $isAr ? '[هيكل] الاستخدام المشروع وعدم إساءة استخدام المنصّة (لا كشط بيانات ولا إعادة بيع).' : '[Structure] Lawful use and no misuse of the platform (no scraping, no reselling).' }}</li>
          </ul>

          <h2 id="ip">{{ $isAr ? '٧. الملكية الفكرية' : '7. Intellectual property' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] نملك المنصّة وبرمجياتها وعلامتها. تملك أنت محتواك وشعارك، وتمنحنا ترخيصاً لعرضهما ضمن السوق لتشغيل الخدمة.'
            : '[Structure] We own the platform, its software and brand. You own your content and logo, and grant us a licence to display them within the marketplace to operate the service.' }}</p>

          <h2 id="availability">{{ $isAr ? '٨. التوفّر والنسخ الاحتياطي' : '8. Availability & backups' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] تُقدَّم المنصّة «كما هي». نسعى لتوفّر عالٍ مع نوافذ صيانة، ونجري نسخاً احتياطياً دورياً، لكن يبقى على النشاط الاحتفاظ بسجلّاته الخاصة. حقّ تصدير البيانات متاح.'
            : '[Structure] The platform is provided “as is”. We target high availability with maintenance windows and perform regular backups, but the business should keep its own records. Data export is available on request.' }}</p>

          <h2 id="liability">{{ $isAr ? '٩. حدود المسؤولية والتعويض' : '9. Limitation of liability & indemnity' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] تُحدَّد مسؤوليتنا القصوى بما يعادل الرسوم المدفوعة خلال آخر ١٢ شهراً، ولا نتحمّل الأضرار غير المباشرة. تُعوّضنا عن أي مطالبات ناشئة عن سوء استخدامك أو نزاعاتك مع عملائك.'
            : '[Structure] Our maximum liability is capped at the fees paid in the last 12 months; we are not liable for indirect damages. You indemnify us against claims arising from your misuse or your disputes with customers.' }}</p>

          <h2 id="termination">{{ $isAr ? '١٠. التعليق والإنهاء' : '10. Suspension & termination' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] يجوز تعليق أو إنهاء الحساب عند عدم الدفع أو الإساءة، مع إشعار مسبق. بعد الإنهاء تُتاح مهلة لتصدير البيانات (مثلاً ٣٠ يوماً) ثم تُحذف وفق سياسة الاحتفاظ.'
            : '[Structure] The account may be suspended or terminated for non-payment or abuse, with prior notice. After termination a window is provided to export data (e.g. 30 days), after which it is deleted per the retention policy.' }}</p>

          <h2 id="law">{{ $isAr ? '١١. القانون الحاكم وحل النزاعات' : '11. Governing law & disputes' }}</h2>
          <p>{{ $isAr
            ? '[هيكل] تُحدَّد الجهة القضائية المختصة وآلية حل النزاعات (تحكيم/محاكم) والقانون الواجب التطبيق.'
            : '[Structure] The competent jurisdiction, dispute-resolution mechanism (arbitration / courts) and applicable law are to be specified.' }}</p>

          <div class="bkf-legal-note">
            {{ $isAr
              ? '⚠️ هذه الصفحة هيكل مبدئي وليست نصّاً قانونياً نهائياً. يجب أن يراجعها ويصيغها محامٍ مختص قبل الاعتماد عليها.'
              : '⚠️ This page is a preliminary structure, not final legal text. It must be drafted and reviewed by qualified legal counsel before you rely on it.' }}
          </div>
        </div>
      </div>
    </section>
</x-front.layout>
