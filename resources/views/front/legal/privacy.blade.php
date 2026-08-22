@php $isAr = app()->getLocale() === 'ar'; $updated = '2026-07-30'; @endphp
{{-- NOTE (owner): this is a solid launch baseline. Have it reviewed by local
     legal counsel before relying on it for compliance in your jurisdiction. --}}
<x-front.layout :title="($isAr ? 'سياسة الخصوصية' : 'Privacy Policy') . ' | GlowRez'"
                :mapFab="false"
                :description="$isAr ? 'سياسة الخصوصية في GlowRez — كيف نجمع بياناتك ونستخدمها ونحميها.' : 'GlowRez Privacy Policy — how we collect, use and protect your data.'">
    <x-slot:styles>@include('front.legal._css')</x-slot:styles>

    <section class="bkf-legal">
      <div class="bkf-legal-wrap">
        <div class="bkf-legal-eyebrow">{{ $isAr ? 'قانوني' : 'Legal' }}</div>
        <h1>{{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }}</h1>
        <p class="bkf-legal-updated">{{ $isAr ? 'آخر تحديث: ' : 'Last updated: ' }}{{ \Illuminate\Support\Carbon::parse($updated)->translatedFormat('d F Y') }}</p>

        <nav class="bkf-legal-toc">
          <a href="#collect">{{ $isAr ? 'ما نجمعه' : 'What we collect' }}</a>
          <a href="#use">{{ $isAr ? 'كيف نستخدمه' : 'How we use it' }}</a>
          <a href="#share">{{ $isAr ? 'المشاركة' : 'Sharing' }}</a>
          <a href="#cookies">{{ $isAr ? 'ملفات التعريف' : 'Cookies' }}</a>
          <a href="#rights">{{ $isAr ? 'حقوقك' : 'Your rights' }}</a>
          <a href="#contact">{{ $isAr ? 'تواصل' : 'Contact' }}</a>
        </nav>

        <div class="bkf-legal-body">
          <p>
            {{ $isAr
              ? 'في GlowRez نحترم خصوصيتك. توضّح هذه السياسة البيانات التي نجمعها عند استخدامك المنصّة لحجز مواعيد صالونات التجميل ومراكز العناية، وكيف نستخدمها ونحميها.'
              : 'At GlowRez we respect your privacy. This policy explains what data we collect when you use the platform to book beauty and wellness appointments, and how we use and protect it.' }}
          </p>

          <h2 id="collect">{{ $isAr ? '١. البيانات التي نجمعها' : '1. Information we collect' }}</h2>
          <ul>
            <li>{{ $isAr ? 'رقم هاتفك — لإنشاء حسابك والتحقق منه عبر رمز يُرسل لمرة واحدة (OTP).' : 'Your phone number — to create your account and verify it via a one-time code (OTP).' }}</li>
            <li>{{ $isAr ? 'اسمك، وعمرك اختيارياً — لتخصيص تجربتك وإتمام الحجز.' : 'Your name, and optionally your age — to personalise your experience and complete bookings.' }}</li>
            <li>{{ $isAr ? 'تفاصيل حجوزاتك — الصالون، الخدمة، الموظف، الوقت، والملاحظات.' : 'Your booking details — venue, service, staff member, time, and any notes.' }}</li>
            <li>{{ $isAr ? 'أماكنك المفضّلة وتفضيلات العرض (مثل اللغة والمظهر).' : 'Your favourite venues and display preferences (such as language and theme).' }}</li>
            <li>{{ $isAr ? 'بيانات تقنية أساسية للأمان ومنع إساءة الاستخدام.' : 'Basic technical data for security and abuse prevention.' }}</li>
          </ul>

          <h2 id="use">{{ $isAr ? '٢. كيف نستخدم بياناتك' : '2. How we use your information' }}</h2>
          <ul>
            <li>{{ $isAr ? 'لإتمام حجوزاتك وإدارتها وتذكيرك بها.' : 'To make, manage, and remind you of your bookings.' }}</li>
            <li>{{ $isAr ? 'لإرسال تأكيدات وتحديثات عبر الرسائل أو واتساب.' : 'To send confirmations and updates via SMS or WhatsApp.' }}</li>
            <li>{{ $isAr ? 'لحماية حسابك ومنع الاحتيال.' : 'To protect your account and prevent fraud.' }}</li>
            <li>{{ $isAr ? 'لا نبيع بياناتك الشخصية لأي طرف ثالث.' : 'We never sell your personal data to third parties.' }}</li>
          </ul>

          <h2 id="share">{{ $isAr ? '٣. مشاركة البيانات' : '3. Sharing your data' }}</h2>
          <p>
            {{ $isAr
              ? 'عند إجراء حجز، نشارك اسمك ورقم هاتفك وتفاصيل الموعد مع الصالون المعني فقط ليتمكّن من خدمتك. قد نستعين بمزوّدي خدمة (مثل إرسال الرسائل) ملتزمين بحماية بياناتك.'
              : 'When you book, we share your name, phone number and appointment details only with the relevant venue so it can serve you. We may use service providers (such as SMS delivery) bound to protect your data.' }}
          </p>

          <h2 id="cookies">{{ $isAr ? '٤. ملفات التعريف والتخزين المحلي' : '4. Cookies & local storage' }}</h2>
          <p>
            {{ $isAr
              ? 'نستخدم تخزيناً محلياً بسيطاً في متصفحك لحفظ تفضيلاتك (اللغة، المظهر الفاتح/الداكن، وجلسة الدخول). لا نستخدم أدوات تتبّع إعلانية.'
              : 'We use simple local storage in your browser to remember your preferences (language, light/dark theme, and your login session). We do not use advertising trackers.' }}
          </p>

          <h2 id="rights">{{ $isAr ? '٥. حقوقك' : '5. Your rights' }}</h2>
          <p>
            {{ $isAr
              ? 'يمكنك الوصول إلى بياناتك أو تصحيحها من صفحة حسابك، أو طلب حذف حسابك بالكامل بالتواصل معنا. سنستجيب لطلبك في أقرب وقت.'
              : 'You can access or correct your data from your account, or request full deletion of your account by contacting us. We will respond promptly.' }}
          </p>

          <h2 id="contact">{{ $isAr ? '٦. تواصل معنا' : '6. Contact us' }}</h2>
          <p>
            {{ $isAr ? 'لأي سؤال حول الخصوصية، ' : 'For any privacy question, ' }}
            <a href="{{ route('front.contact') }}">{{ $isAr ? 'راسلنا من هنا' : 'reach us here' }}</a>.
          </p>

          <div class="bkf-legal-note">
            {{ $isAr
              ? 'قد نُحدّث هذه السياسة من وقت لآخر، وسنعرض تاريخ آخر تحديث أعلى الصفحة.'
              : 'We may update this policy from time to time; the latest revision date is shown at the top of the page.' }}
          </div>
        </div>
      </div>
    </section>
</x-front.layout>
