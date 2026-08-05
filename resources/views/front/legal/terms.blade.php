@php $isAr = app()->getLocale() === 'ar'; $updated = '2026-07-30'; @endphp
{{-- NOTE (owner): launch baseline. Have local legal counsel review before relying on it. --}}
<x-front.layout :title="($isAr ? 'الشروط والأحكام' : 'Terms of Service') . ' | GlowRez'"
                :mapFab="false"
                :description="$isAr ? 'الشروط والأحكام الخاصة باستخدام منصة GlowRez للحجز.' : 'Terms of Service governing the use of the GlowRez booking platform.'">
    <x-slot:styles>@include('front.legal._css')</x-slot:styles>

    <section class="bkf-legal">
      <div class="bkf-legal-wrap">
        <div class="bkf-legal-eyebrow">{{ $isAr ? 'قانوني' : 'Legal' }}</div>
        <h1>{{ $isAr ? 'الشروط والأحكام' : 'Terms of Service' }}</h1>
        <p class="bkf-legal-updated">{{ $isAr ? 'آخر تحديث: ' : 'Last updated: ' }}{{ \Illuminate\Support\Carbon::parse($updated)->translatedFormat('d F Y') }}</p>

        <nav class="bkf-legal-toc">
          <a href="#service">{{ $isAr ? 'الخدمة' : 'The service' }}</a>
          <a href="#account">{{ $isAr ? 'حسابك' : 'Your account' }}</a>
          <a href="#booking">{{ $isAr ? 'الحجوزات' : 'Bookings' }}</a>
          <a href="#cancel">{{ $isAr ? 'الإلغاء' : 'Cancellation' }}</a>
          <a href="#conduct">{{ $isAr ? 'السلوك' : 'Conduct' }}</a>
          <a href="#liability">{{ $isAr ? 'المسؤولية' : 'Liability' }}</a>
        </nav>

        <div class="bkf-legal-body">
          <p>
            {{ $isAr
              ? 'باستخدامك منصّة Booksy فإنك توافق على هذه الشروط. يُرجى قراءتها بعناية. إذا لم توافق عليها، فلا يمكنك استخدام المنصّة.'
              : 'By using the Booksy platform you agree to these terms. Please read them carefully. If you do not agree, you may not use the platform.' }}
          </p>

          <h2 id="service">{{ $isAr ? '١. عن الخدمة' : '1. About the service' }}</h2>
          <p>
            {{ $isAr
              ? 'Booksy منصّة تربط العملاء بصالونات التجميل ومراكز العناية لحجز المواعيد. نحن لسنا مقدّم الخدمة داخل الصالون؛ الصالون وحده مسؤول عن الخدمات التي يقدّمها.'
              : 'Booksy is a platform connecting customers with beauty and wellness venues to book appointments. We are not the in-venue service provider; each venue is solely responsible for the services it delivers.' }}
          </p>

          <h2 id="account">{{ $isAr ? '٢. حسابك' : '2. Your account' }}</h2>
          <ul>
            <li>{{ $isAr ? 'يجب تقديم رقم هاتف صحيح تملكه للتحقّق عبر الرمز.' : 'You must provide a valid phone number you own for OTP verification.' }}</li>
            <li>{{ $isAr ? 'أنت مسؤول عن الحفاظ على سرّية وصولك لحسابك.' : 'You are responsible for keeping access to your account secure.' }}</li>
            <li>{{ $isAr ? 'يجب أن تكون بالسن القانونية أو بموافقة وليّ أمرك.' : 'You must be of legal age or have guardian consent.' }}</li>
          </ul>

          <h2 id="booking">{{ $isAr ? '٣. الحجوزات والدفع' : '3. Bookings & payment' }}</h2>
          <p>
            {{ $isAr
              ? 'عند تأكيد الحجز يُحجز لك الموعد لدى الصالون. يتم الدفع عادةً في مقرّ الصالون وفق أسعاره المعلنة. قد تختلف الأسعار الفعلية بحسب الخدمة المقدّمة.'
              : 'Confirming a booking reserves your slot at the venue. Payment is normally made at the venue according to its listed prices. Final prices may vary based on the actual service delivered.' }}
          </p>

          <h2 id="cancel">{{ $isAr ? '٤. الإلغاء وعدم الحضور' : '4. Cancellation & no-shows' }}</h2>
          <p>
            {{ $isAr
              ? 'يمكنك إلغاء موعدك من صفحة «مواعيدي» أو عبر رابط التأكيد. نرجو الإلغاء مبكراً احتراماً لوقت الصالون. قد يطبّق بعض الصالونات سياسة خاصة بعدم الحضور.'
              : 'You can cancel from the “My appointments” page or via your confirmation link. Please cancel early out of respect for the venue’s time. Some venues may apply their own no-show policy.' }}
          </p>

          <h2 id="conduct">{{ $isAr ? '٥. الاستخدام المقبول' : '5. Acceptable use' }}</h2>
          <p>
            {{ $isAr
              ? 'تلتزم بعدم إساءة استخدام المنصّة، ويشمل ذلك الحجوزات الوهمية أو المسيئة أو أي نشاط يضرّ بالصالونات أو المستخدمين الآخرين.'
              : 'You agree not to misuse the platform, including fake or abusive bookings or any activity harmful to venues or other users.' }}
          </p>

          <h2 id="liability">{{ $isAr ? '٦. حدود المسؤولية' : '6. Limitation of liability' }}</h2>
          <p>
            {{ $isAr
              ? 'نقدّم المنصّة «كما هي». لا نتحمّل مسؤولية جودة الخدمات المقدَّمة داخل الصالونات، لكننا نسعى دائماً لتحسين تجربتك ومساعدتك عند وجود مشكلة.'
              : 'The platform is provided “as is”. We are not responsible for the quality of services delivered inside venues, but we always strive to improve your experience and help if something goes wrong.' }}
          </p>

          <div class="bkf-legal-note">
            {{ $isAr
              ? 'قد نُعدّل هذه الشروط عند تطوّر المنصّة، وسنعرض تاريخ آخر تحديث أعلى الصفحة.'
              : 'We may amend these terms as the platform evolves; the latest revision date is shown at the top.' }}
          </div>
        </div>
      </div>
    </section>
</x-front.layout>
