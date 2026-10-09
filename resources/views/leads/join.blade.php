@extends('leads.layout')

@section('title', $t('كن من أوائل المنشآت على GlowRez', 'Be among the first businesses on GlowRez'))
@section('description', $t(
    'اترك معلومات منشأتك وسيتواصل معك فريق GlowRez عند الإطلاق. التسجيل يستغرق دقيقة ولا يحتاج حساباً.',
    'Leave your business details and the GlowRez team will reach out at launch. Takes a minute, no account needed.'))
@section('body-class', 'gl-page-join')

@php
    $steps = [
        1 => $t('معلومات التواصل', 'Contact'),
        2 => $t('معلومات المنشأة', 'Your business'),
        3 => $t('اهتماماتك', 'What matters to you'),
        4 => $t('التأكيد', 'Confirm'),
    ];
    $err = fn (string $name) => '<p class="gl-err" id="err-'.$name.'" role="alert"><svg class="gl-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span></span></p>';
@endphp

@section('content')
<div class="gl-join">

  {{-- ═════ Aside: promise + what happens next ═════ --}}
  <aside class="gl-join-aside">
    <a href="{{ route('leads.welcome') }}" class="gl-back gl-rise" style="--i:0">
      <x-leads.icon name="arrow-left" :size="18" class="gl-flip"/>{{ $t('رجوع', 'Back') }}
    </a>
    <h1 class="gl-h1 gl-rise" style="--i:1;margin-top:10px">{{ $t('كن من أوائل المنشآت على GlowRez.', 'Be among the first businesses on GlowRez.') }}</h1>
    <p class="gl-lead gl-rise" style="--i:2">{{ $t('اترك معلومات منشأتك وسيتواصل معك فريق GlowRez عند الإطلاق.', 'Leave your business details and the GlowRez team will reach out at launch.') }}</p>

  </aside>

  {{-- ═════ The form ═════ --}}
  <section class="gl-card gl-rise" style="--i:2" id="gl-card" aria-live="polite">

    <form id="gl-form" method="POST" action="{{ route('leads.store') }}" novalidate autocomplete="on">
      @csrf
      <input type="hidden" name="_t" value="{{ $formToken }}">
      <div class="gl-hp" aria-hidden="true"><input type="text" name="hp_extra" id="hp_extra" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-form-type="other"></div>

      {{-- progress --}}
      <div class="gl-progress-meta">
        <b id="gl-step-name">{{ $steps[1] }}</b>
        <span id="gl-step-count">{{ $t('الخطوة', 'Step') }} <bdi dir="ltr"><span id="gl-step-n">1</span> / 4</bdi></span>
      </div>
      <ol class="gl-progress" aria-hidden="true">
        @foreach($steps as $n => $label)<li data-prog="{{ $n }}" class="{{ $n === 1 ? 'is-current' : '' }}"></li>@endforeach
      </ol>

      {{-- ── Step 1 · contact ── --}}
      <fieldset class="gl-step is-in" data-step="1" style="border:0;padding:0;margin:22px 0 0;min-width:0">
        <legend class="gl-step-title" style="padding:0">{{ $t('كيف نتواصل معك؟', 'How can we reach you?') }}</legend>
        <div class="gl-fields">
          <div class="gl-field" data-field="full_name">
            <label class="gl-label" for="f-full_name">{{ $t('الاسم الكامل', 'Full name') }}<span class="gl-req" aria-hidden="true">*</span></label>
            <input class="gl-input" id="f-full_name" name="full_name" type="text" autocomplete="name" enterkeyhint="next" maxlength="120" required aria-describedby="err-full_name">
            {!! $err('full_name') !!}
          </div>

          <div class="gl-field" data-field="phone">
            <label class="gl-label" for="f-phone">{{ $t('رقم الهاتف', 'Phone number') }}<span class="gl-req" aria-hidden="true">*</span></label>
            <input class="gl-input is-ltr" id="f-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" enterkeyhint="next" maxlength="40" placeholder="09XX XXX XXX" required aria-describedby="err-phone help-phone">
            <p class="gl-help" id="help-phone">{{ $t('نتواصل معك على هذا الرقم.', 'We’ll contact you on this number.') }}</p>
            {!! $err('phone') !!}
          </div>

          <label class="gl-tick"><input type="checkbox" name="whatsapp_same" id="f-whatsapp_same" value="1" checked>{{ $t('نفس الرقم على واتساب', 'Same number on WhatsApp') }}</label>

          <div class="gl-field" data-field="whatsapp" id="wrap-whatsapp" hidden>
            <label class="gl-label" for="f-whatsapp">{{ $t('رقم واتساب', 'WhatsApp number') }}</label>
            <input class="gl-input is-ltr" id="f-whatsapp" name="whatsapp" type="tel" inputmode="tel" autocomplete="off" maxlength="40" placeholder="09XX XXX XXX" aria-describedby="err-whatsapp">
            {!! $err('whatsapp') !!}
          </div>

          <div class="gl-field" data-field="email">
            <label class="gl-label" for="f-email">{{ $t('البريد الإلكتروني', 'Email') }}<small>{{ $t('اختياري', 'optional') }}</small></label>
            <input class="gl-input is-ltr" id="f-email" name="email" type="email" inputmode="email" autocomplete="email" maxlength="150" aria-describedby="err-email">
            {!! $err('email') !!}
          </div>
        </div>
      </fieldset>

      {{-- ── Step 2 · business ── --}}
      <fieldset class="gl-step" data-step="2" hidden style="border:0;padding:0;margin:22px 0 0;min-width:0">
        <legend class="gl-step-title" style="padding:0">{{ $t('حدّثنا عن منشأتك', 'Tell us about your business') }}</legend>
        <div class="gl-fields">
          <div class="gl-field" data-field="business_name">
            <label class="gl-label" for="f-business_name">{{ $t('اسم المنشأة', 'Business name') }}<span class="gl-req" aria-hidden="true">*</span></label>
            <input class="gl-input" id="f-business_name" name="business_name" type="text" autocomplete="organization" enterkeyhint="next" maxlength="150" required aria-describedby="err-business_name">
            {!! $err('business_name') !!}
          </div>

          <div class="gl-field" data-field="business_type" role="radiogroup" aria-labelledby="lbl-type">
            <span class="gl-label" id="lbl-type">{{ $t('نوع المنشأة', 'Business type') }}<span class="gl-req" aria-hidden="true">*</span></span>
            <div class="gl-chips gl-chips--grid">
              @foreach($types as $key => $label)
                <label class="gl-chip"><input type="radio" name="business_type" value="{{ $key }}"><span>{{ $label }}</span></label>
              @endforeach
            </div>
            {!! $err('business_type') !!}
          </div>

          <div class="gl-row2">
            <div class="gl-field" data-field="city">
              <label class="gl-label" for="f-city">{{ $t('المحافظة', 'City / governorate') }}<span class="gl-req" aria-hidden="true">*</span></label>
              <select class="gl-select" id="f-city" name="city" required aria-describedby="err-city">
                <option value="">{{ $t('اختر المحافظة', 'Choose') }}</option>
                @foreach($cities as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                <option value="other">{{ $t('مدينة أخرى', 'Somewhere else') }}</option>
              </select>
              {!! $err('city') !!}
            </div>
            <div class="gl-field" data-field="area">
              <label class="gl-label" for="f-area">{{ $t('المنطقة', 'Area') }}<small>{{ $t('اختياري', 'optional') }}</small></label>
              <input class="gl-input" id="f-area" name="area" type="text" autocomplete="address-level3" maxlength="100" aria-describedby="err-area">
              {!! $err('area') !!}
            </div>
          </div>

          <div class="gl-field" data-field="city_other" id="wrap-city_other" hidden>
            <label class="gl-label" for="f-city_other">{{ $t('اسم المدينة', 'City name') }}<span class="gl-req" aria-hidden="true">*</span></label>
            <input class="gl-input" id="f-city_other" name="city_other" type="text" maxlength="80" aria-describedby="err-city_other">
            {!! $err('city_other') !!}
          </div>

          <div class="gl-field" data-field="number_of_branches">
            <span class="gl-label" id="lbl-branches">{{ $t('عدد الفروع', 'Number of branches') }}<span class="gl-req" aria-hidden="true">*</span></span>
            <div class="gl-stepper" role="group" aria-labelledby="lbl-branches">
              <button type="button" data-step-by="-1" aria-label="{{ $t('أنقص فرعاً', 'One fewer') }}"><x-leads.icon name="minus" :size="20"/></button>
              <input class="gl-input" id="f-branches" name="number_of_branches" type="number" inputmode="numeric" min="1" max="99" value="1" aria-labelledby="lbl-branches" aria-describedby="err-number_of_branches">
              <button type="button" data-step-by="1" aria-label="{{ $t('زد فرعاً', 'One more') }}"><x-leads.icon name="plus" :size="20"/></button>
            </div>
            {!! $err('number_of_branches') !!}
          </div>

          <details class="gl-more">
            <summary><span>{{ $t('روابط منشأتك', 'Your links') }} <small style="font-weight:500;color:var(--gl-ink-3)">{{ $t('اختياري', 'optional') }}</small></span><x-leads.icon name="chevron" :size="20"/></summary>
            <div class="gl-more-body">
              <div class="gl-field" data-field="instagram">
                <label class="gl-label" for="f-instagram">Instagram</label>
                <input class="gl-input is-ltr" id="f-instagram" name="instagram" type="text" inputmode="url" autocapitalize="none" autocomplete="off" maxlength="255" placeholder="@yourbusiness" aria-describedby="err-instagram">
                {!! $err('instagram') !!}
              </div>
              <div class="gl-field" data-field="facebook">
                <label class="gl-label" for="f-facebook">Facebook</label>
                <input class="gl-input is-ltr" id="f-facebook" name="facebook" type="text" inputmode="url" autocapitalize="none" autocomplete="off" maxlength="255" aria-describedby="err-facebook">
                {!! $err('facebook') !!}
              </div>
              <div class="gl-field" data-field="website">
                <label class="gl-label" for="f-website">{{ $t('الموقع الإلكتروني', 'Website') }}</label>
                <input class="gl-input is-ltr" id="f-website" name="website" type="text" inputmode="url" autocapitalize="none" autocomplete="off" maxlength="255" placeholder="example.com" aria-describedby="err-website">
                {!! $err('website') !!}
              </div>
            </div>
          </details>
        </div>
      </fieldset>

      {{-- ── Step 3 · interests ── --}}
      <fieldset class="gl-step" data-step="3" hidden style="border:0;padding:0;margin:22px 0 0;min-width:0">
        <legend class="gl-step-title" style="padding:0">{{ $t('ما الذي يهمّك في GlowRez؟', 'What matters to you in GlowRez?') }}</legend>
        <p class="gl-step-sub">{{ $t('اختر ما يناسبك — يمكنك اختيار أكثر من خيار، أو تخطّي هذه الخطوة.', 'Pick as many as you like — or skip this step.') }}</p>
        <div class="gl-fields">
          <div class="gl-field" data-field="interests">
            <div class="gl-chips gl-chips--pills">
              @foreach($interests as $key => $label)
                <label class="gl-chip"><input type="checkbox" name="interests[]" value="{{ $key }}"><span><x-leads.icon name="check" :size="16" :stroke="2.4"/>{{ $label }}</span></label>
              @endforeach
            </div>
            {!! $err('interests') !!}
          </div>
        </div>
      </fieldset>

      {{-- ── Step 4 · confirm ── --}}
      <fieldset class="gl-step" data-step="4" hidden style="border:0;padding:0;margin:22px 0 0;min-width:0">
        <legend class="gl-step-title" style="padding:0">{{ $t('راجع بياناتك', 'Review your details') }}</legend>
        <p class="gl-step-sub">{{ $t('تأكد من صحة المعلومات ثم أرسلها.', 'Check everything looks right, then send it.') }}</p>
        <div class="gl-summary" id="gl-summary"></div>
        <p class="gl-legal">{{ $t('بإرسال النموذج توافق على أن يتواصل معك فريق GlowRez بخصوص الإطلاق.', 'By sending this you agree that the GlowRez team may contact you about the launch.') }}
          <a href="{{ route('front.privacy') }}" target="_blank" rel="noopener">{{ $t('سياسة الخصوصية', 'Privacy policy') }}</a></p>
        <div class="gl-form-error" id="gl-form-error" role="alert"><x-leads.icon name="alert" :size="20"/><span></span></div>
      </fieldset>

      <div class="gl-actions">
        <button type="button" class="gl-btn gl-btn--ghost" id="gl-back" hidden>
          <x-leads.icon name="arrow-left" :size="18" class="gl-flip"/>{{ $t('رجوع', 'Back') }}
        </button>
        <button type="button" class="gl-btn gl-btn--primary" id="gl-next">
          <span>{{ $t('متابعة', 'Continue') }}</span><x-leads.icon name="arrow" :size="18" class="gl-flip"/>
        </button>
        <button type="submit" class="gl-btn gl-btn--gold" id="gl-submit" hidden>
          <span class="gl-spin" aria-hidden="true"></span><span>{{ $t('سجّل اهتمامي', 'Register my interest') }}</span>
        </button>
      </div>
    </form>

    {{-- ═════ Success (swapped in by JS; also rendered by /join/thanks) ═════ --}}
    @include('leads.partials.success', ['duplicate' => false, 'inline' => true])
  </section>

  {{-- ═════ What happens next (below the form on phones, beside it on desktop) ═════ --}}
  <div class="gl-join-more">
    <ol class="gl-after gl-rise" style="--i:3" aria-label="{{ $t('ماذا يحدث بعد التسجيل', 'What happens next') }}">
      <li><span class="gl-after-n">1</span><div><strong>{{ $t('تسجّل معلومات منشأتك', 'You share your business details') }}</strong><span class="d">{{ $t('أربع خطوات قصيرة، بدون حساب أو كلمة مرور.', 'Four short steps, no account or password.') }}</span></div></li>
      <li><span class="gl-after-n">2</span><div><strong>{{ $t('يتواصل معك فريقنا', 'Our team gets in touch') }}</strong><span class="d">{{ $t('عبر واتساب أو الهاتف، حسب ما يناسبك.', 'By WhatsApp or phone, whichever suits you.') }}</span></div></li>
      <li><span class="gl-after-n">3</span><div><strong>{{ $t('تكون من الأوائل عند الإطلاق', 'You’re among the first at launch') }}</strong><span class="d">{{ $t('منشأتك جاهزة قبل أن يصل إليها الجميع.', 'Your business is ready before everyone else’s.') }}</span></div></li>
    </ol>

    @if($waUrl)
      <p class="gl-note gl-rise" style="--i:4;margin-top:24px">
        <x-leads.icon name="whatsapp" :size="18"/>
        <span>{{ $t('تفضّل أن تتحدث مباشرة؟', 'Prefer to talk directly?') }}
          <a href="{{ $waUrl }}" target="_blank" rel="noopener" data-wa="join-aside">{{ $t('راسلنا على واتساب', 'Message us on WhatsApp') }}</a></span>
      </p>
    @endif

  </div>
</div>

<script type="application/json" id="gl-i18n">{!! json_encode([
    'required'   => $t('هذا الحقل مطلوب.', 'This field is required.'),
    'phone'      => $t('أدخل رقم هاتف صحيح، مثل 0944 123 456.', 'Enter a valid phone number, e.g. 0944 123 456.'),
    'email'      => $t('أدخل بريداً إلكترونياً صحيحاً.', 'Enter a valid email address.'),
    'instagram'  => $t('أدخل اسم المستخدم أو رابط الحساب.', 'Enter your username or the account link.'),
    'branches'   => $t('أدخل عدداً بين 1 و99.', 'Enter a number from 1 to 99.'),
    'choose'     => $t('اختر خياراً.', 'Please choose one.'),
    'network'    => $t('تعذّر الاتصال. تحقق من الإنترنت وحاول مرة أخرى.', 'Couldn’t connect. Check your internet and try again.'),
    'generic'    => $t('حدث خطأ غير متوقع. حاول مرة أخرى بعد قليل.', 'Something went wrong. Please try again in a moment.'),
    'throttle'   => $t('محاولات كثيرة. يرجى الانتظار قليلاً ثم المحاولة مجدداً.', 'Too many attempts. Please wait a bit and try again.'),
    'stepNames'  => array_values($steps),
    'edit'       => $t('تعديل', 'Edit'),
    'groups'     => [
        'contact'   => $t('معلومات التواصل', 'Contact'),
        'business'  => $t('معلومات المنشأة', 'Your business'),
        'interests' => $t('الاهتمامات', 'Interests'),
    ],
    'labels'     => [
        'full_name' => $t('الاسم', 'Name'), 'phone' => $t('الهاتف', 'Phone'), 'whatsapp' => 'WhatsApp', 'email' => $t('البريد', 'Email'),
        'business_name' => $t('المنشأة', 'Business'), 'business_type' => $t('النوع', 'Type'), 'city' => $t('المحافظة', 'City'),
        'area' => $t('المنطقة', 'Area'), 'branches' => $t('الفروع', 'Branches'), 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'website' => $t('الموقع', 'Website'),
        'interests' => $t('اخترت', 'Chosen'), 'none' => $t('لم تُحدَّد', 'None selected'), 'sameWa' => $t('نفس رقم الهاتف', 'Same as phone'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endsection

@push('scripts')
<script src="{{ asset('frontend/js/launch-join.js') }}?v={{ @filemtime(public_path('frontend/js/launch-join.js')) ?: '1' }}" defer></script>
@endpush
