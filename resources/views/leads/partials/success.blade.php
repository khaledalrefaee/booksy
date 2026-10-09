@php
    $inline    = $inline ?? false;          // true on /join: hidden until the AJAX submit succeeds
    $duplicate = $duplicate ?? false;
    $waHref    = $whatsapp_url ?? $waUrl;
@endphp
<div class="gl-success" id="gl-success" data-state="{{ $duplicate ? 'dup' : 'new' }}" tabindex="-1" @if($inline) hidden @endif>
  <div class="gl-success-mark" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
  </div>

  <h2 class="gl-h2" data-only="new">{{ $t('شكراً لاهتمامك بـ GlowRez.', 'Thank you for your interest in GlowRez.') }}</h2>
  <h2 class="gl-h2" data-only="dup">{{ $t('يبدو أن معلوماتك مسجلة لدينا مسبقاً.', 'It looks like we already have your details.') }}</h2>

  <p data-only="new">{{ $t('تم تسجيل معلومات منشأتك بنجاح.', 'Your business details were registered successfully.') }}<br>{{ $t('فريق GlowRez رح يتواصل معك بأقرب وقت.', 'The GlowRez team will contact you very soon.') }}</p>
  <p data-only="dup">{{ $t('سيتواصل معك فريق GlowRez قريباً.', 'The GlowRez team will contact you soon.') }}</p>

  @if($waHref)
    <div class="gl-success-wa">
      <p>{{ $t('تواصل معنا مباشرة عبر WhatsApp', 'Talk to us directly on WhatsApp') }}</p>
      <a href="{{ $waHref }}" class="gl-btn gl-btn--primary" target="_blank" rel="noopener" id="gl-success-wa" data-wa="success">
        <x-leads.icon name="whatsapp" :size="20"/>WhatsApp
      </a>
    </div>
  @endif

  @if($igUrl)
    <a href="{{ $igUrl }}" class="gl-success-ig" target="_blank" rel="noopener">
      <x-leads.icon name="instagram" :size="18"/>{{ $t('تابع GlowRez على Instagram', 'Follow GlowRez on Instagram') }}
    </a>
  @endif

  <a href="{{ route('leads.welcome') }}" class="gl-back" style="margin-top:6px">{{ $t('العودة إلى الصفحة الرئيسية', 'Back to the start') }}</a>
</div>
