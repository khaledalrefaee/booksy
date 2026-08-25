@php
    $isAr = app()->getLocale() === 'ar';
    $updated = '2026-07-30';
    $updatedFmt = \Illuminate\Support\Carbon::parse($updated)->translatedFormat('d F Y');
    $toc = [
        ['id' => 'service',   'label' => $isAr ? 'الخدمة'     : 'The service'],
        ['id' => 'account',   'label' => $isAr ? 'حسابك'      : 'Your account'],
        ['id' => 'booking',   'label' => $isAr ? 'الحجوزات'   : 'Bookings'],
        ['id' => 'cancel',    'label' => $isAr ? 'الإلغاء'    : 'Cancellation'],
        ['id' => 'conduct',   'label' => $isAr ? 'السلوك'     : 'Conduct'],
        ['id' => 'liability', 'label' => $isAr ? 'المسؤولية'  : 'Liability'],
    ];
@endphp
{{-- NOTE (owner): launch baseline. Have local legal counsel review before relying on it. --}}
<x-front.legal
    :title="($isAr ? 'الشروط والأحكام' : 'Terms of Service') . ' | GlowRez'"
    :description="$isAr ? 'الشروط والأحكام الخاصة باستخدام منصة GlowRez للحجز.' : 'Terms of Service governing the use of the GlowRez booking platform.'"
    :eyebrow="$isAr ? 'قانوني' : 'Legal'"
    :heading="$isAr ? 'الشروط والأحكام' : 'Terms of Service'"
    :subtitle="$isAr ? 'الشروط والأحكام التي تنظّم استخدامك لمنصّة GlowRez.' : 'The terms and conditions that govern your use of the GlowRez platform.'"
    :updatedLabel="$isAr ? 'آخر تحديث:' : 'Last updated:'"
    :updated="$updatedFmt"
    :tocLabel="$isAr ? 'في هذه الصفحة' : 'On this page'"
    :toc="$toc">

    <p>
      {{ $isAr
        ? 'باستخدامك منصّة GlowRez فإنك توافق على هذه الشروط. يُرجى قراءتها بعناية. إذا لم توافق عليها، فلا يمكنك استخدام المنصّة.'
        : 'By using the GlowRez platform you agree to these terms. Please read them carefully. If you do not agree, you may not use the platform.' }}
    </p>

    <h2 id="service">{{ $isAr ? '١. عن الخدمة' : '1. About the service' }}</h2>
    <p>
      {{ $isAr
        ? 'GlowRez منصّة تربط العملاء بصالونات التجميل ومراكز العناية لحجز المواعيد. نحن لسنا مقدّم الخدمة داخل الصالون؛ الصالون وحده مسؤول عن الخدمات التي يقدّمها.'
        : 'GlowRez is a platform connecting customers with beauty and wellness venues to book appointments. We are not the in-venue service provider; each venue is solely responsible for the services it delivers.' }}
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

    <div class="bkf-legaldoc-note">
      {{ $isAr
        ? 'قد نُعدّل هذه الشروط عند تطوّر المنصّة، وسنعرض تاريخ آخر تحديث أعلى الصفحة.'
        : 'We may amend these terms as the platform evolves; the latest revision date is shown at the top.' }}
    </div>
</x-front.legal>
