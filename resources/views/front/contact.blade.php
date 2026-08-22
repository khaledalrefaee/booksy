@php
    $isAr    = app()->getLocale() === 'ar';
    $success = session('success');
@endphp
<x-front.layout :title="($isAr ? 'تواصل معنا' : 'Contact Us') . ' | GlowRez'" :mapFab="false"
    :description="$isAr ? 'تواصل مع فريق GlowRez — استفسارات، دعم فني، أو شراكة أعمال. نحن هنا لمساعدتك.' : 'Get in touch with the GlowRez team — questions, support, or business partnership. We are here to help.'">
<x-slot:styles>
<style>
/* NOTE: section class is .bkf-contact (NOT .bkf-ct) — .bkf-ct is the global
   welcome-toast class in customer-auth-modal.blade.php (width:360px, dark bg);
   sharing it squished this page. The .bkf-ct-* sub-classes below don't collide. */
.bkf-contact{ padding:calc(var(--bk-nav-h) + var(--bk-s10)) 0 var(--bk-s20); }
.bkf-ct-wrap{ max-width:var(--bk-container); margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-ct-head{ text-align:center; max-width:600px; margin:0 auto var(--bk-s10); }
.bkf-ct-head .eyebrow{ font-size:var(--bk-eyebrow); font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); margin-bottom:12px; }
.bkf-ct-head h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h1); color:var(--bk-text); margin:0 0 10px; }
.bkf-ct-head p{ color:var(--bk-text-muted); font-size:var(--bk-fs-lead); margin:0; line-height:1.7; }

.bkf-ct-grid{ display:grid; grid-template-columns:1.5fr 1fr; gap:var(--bk-s6); align-items:start; }
@media (max-width:820px){ .bkf-ct-grid{ grid-template-columns:1fr; } }

.bkf-ct-card{ background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r-xl); box-shadow:var(--bk-shadow-sm); padding:var(--bk-s8); }
.bkf-ct-card h2{ font-size:1.25rem; color:var(--bk-text); margin:0 0 4px; }
.bkf-ct-card .lead{ color:var(--bk-text-muted); font-size:.9rem; margin:0 0 var(--bk-s6); }

.bkf-field{ margin-bottom:var(--bk-s5); }
.bkf-field label{ display:block; font-size:.8rem; font-weight:700; color:var(--bk-text-soft); margin-bottom:7px; }
.bkf-field label .req{ color:var(--bk-gold-strong); }
.bkf-field input,.bkf-field textarea{ width:100%; padding:13px 15px; min-height:48px; border-radius:var(--bk-r); border:1.5px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-family:var(--bk-font-ui); font-size:.95rem; outline:none; transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.bkf-field textarea{ min-height:130px; resize:vertical; line-height:1.6; }
.bkf-field input:focus,.bkf-field textarea:focus{ border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.bkf-field .err{ font-size:.78rem; color:var(--bk-danger); margin-top:6px; }
.bkf-field-row{ display:grid; grid-template-columns:1fr 1fr; gap:var(--bk-s4); }
@media (max-width:520px){ .bkf-field-row{ grid-template-columns:1fr; gap:0; } }

.bkf-ct-ok{ display:flex; align-items:center; gap:11px; padding:14px 16px; border-radius:var(--bk-r); background:var(--bk-success-bg); color:var(--bk-success); font-weight:600; font-size:.92rem; margin-bottom:var(--bk-s5); }

.bkf-ct-info{ display:flex; flex-direction:column; gap:var(--bk-s3); }
.bkf-ct-item{ display:flex; align-items:center; gap:14px; padding:16px; border-radius:var(--bk-r-lg); background:var(--bk-surface); border:1px solid var(--bk-border); text-decoration:none; transition:border-color var(--bk-t) ease,transform var(--bk-t) ease; }
.bkf-ct-item:hover{ border-color:var(--bk-accent); transform:translateY(-2px); }
.bkf-ct-ic{ flex:0 0 auto; width:44px; height:44px; border-radius:var(--bk-r); display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.bkf-ct-item h4{ font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:var(--bk-text-muted); margin:0 0 2px; }
.bkf-ct-item p{ font-size:.95rem; font-weight:600; color:var(--bk-text); margin:0; }
.bkf-ct-note{ margin-top:var(--bk-s3); padding:16px; border-radius:var(--bk-r-lg); background:var(--bk-accent-wash); display:flex; align-items:center; gap:12px; }
.bkf-ct-note .bkf-ct-ic{ background:var(--bk-surface); }
.bkf-ct-note b{ display:block; color:var(--bk-text); font-size:.88rem; }
.bkf-ct-note small{ color:var(--bk-text-muted); font-size:.82rem; }
</style>
</x-slot:styles>

<section class="bkf-contact">
  <div class="bkf-ct-wrap">
    <div class="bkf-ct-head bkf-reveal">
      <div class="eyebrow">{{ $isAr ? 'نحن هنا لمساعدتك' : 'We’re here to help' }}</div>
      <h1>{{ $isAr ? 'تواصل معنا' : 'Get in touch' }}</h1>
      <p>{{ $isAr ? 'سؤال، اقتراح، أو تريد تسجيل نشاطك؟ فريقنا يسعد بالرد عليك.' : 'A question, a suggestion, or want to list your business? Our team is happy to help.' }}</p>
    </div>

    <div class="bkf-ct-grid">
      {{-- Form --}}
      <div class="bkf-ct-card bkf-reveal">
        <h2>{{ $isAr ? 'أرسل لنا رسالة' : 'Send us a message' }}</h2>
        <p class="lead">{{ $isAr ? 'سنعاود التواصل معك على بريدك في أقرب وقت.' : 'We’ll get back to you by email as soon as we can.' }}</p>

        @if($success)
          <div class="bkf-ct-ok"><x-icon name="check-circle" :size="19"/>{{ $isAr ? 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.' : 'Your message was sent! We’ll be in touch soon.' }}</div>
        @endif

        <form action="{{ route('front.contact.send') }}" method="POST" data-contact-form>
          @csrf
          <div class="bkf-field-row">
            <div class="bkf-field">
              <label for="ct-name">{{ $isAr ? 'الاسم الكامل' : 'Full name' }} <span class="req">*</span></label>
              <input id="ct-name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required placeholder="{{ $isAr ? 'اسمك' : 'Your name' }}">
              @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="bkf-field">
              <label for="ct-email">{{ $isAr ? 'البريد الإلكتروني' : 'Email' }} <span class="req">*</span></label>
              <input id="ct-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required placeholder="your@email.com">
              @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="bkf-field">
            <label for="ct-subject">{{ $isAr ? 'الموضوع' : 'Subject' }} <span class="req">*</span></label>
            <input id="ct-subject" type="text" name="subject" value="{{ old('subject') }}" required placeholder="{{ $isAr ? 'موضوع رسالتك' : 'What’s this about?' }}">
            @error('subject')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div class="bkf-field">
            <label for="ct-message">{{ $isAr ? 'الرسالة' : 'Message' }} <span class="req">*</span></label>
            <textarea id="ct-message" name="message" required placeholder="{{ $isAr ? 'اكتب رسالتك هنا…' : 'Write your message here…' }}">{{ old('message') }}</textarea>
            @error('message')<div class="err">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="bkf-btn bkf-btn-primary bkf-btn-block" data-contact-submit>
            <x-icon name="message" :size="18"/>{{ $isAr ? 'إرسال الرسالة' : 'Send message' }}
          </button>
        </form>
      </div>

      {{-- Info --}}
      <div class="bkf-reveal">
        <div class="bkf-ct-info">
          <a href="mailto:info@glowrez.com" class="bkf-ct-item">
            <span class="bkf-ct-ic"><x-icon name="message" :size="20"/></span>
            <div><h4>{{ $isAr ? 'البريد' : 'Email' }}</h4><p>info@glowrez.com</p></div>
          </a>
          <a href="{{ route('front.help') }}" class="bkf-ct-item">
            <span class="bkf-ct-ic"><x-icon name="sparkles" :size="20"/></span>
            <div><h4>{{ $isAr ? 'مركز المساعدة' : 'Help center' }}</h4><p>{{ $isAr ? 'إجابات فورية للأسئلة الشائعة' : 'Instant answers to common questions' }}</p></div>
          </a>
          <a href="{{ route('front.business') }}" class="bkf-ct-item">
            <span class="bkf-ct-ic"><x-icon name="building" :size="20"/></span>
            <div><h4>{{ $isAr ? 'لأصحاب الأعمال' : 'For business' }}</h4><p>{{ $isAr ? 'سجّل نشاطك على المنصّة' : 'List your venue on the platform' }}</p></div>
          </a>
        </div>
        <div class="bkf-ct-note">
          <span class="bkf-ct-ic"><x-icon name="zap" :size="20"/></span>
          <div>
            <b>{{ $isAr ? 'رد سريع' : 'Fast response' }}</b>
            <small>{{ $isAr ? 'نرد عادةً خلال أقل من 24 ساعة.' : 'We usually reply within 24 hours.' }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var f = document.querySelector('[data-contact-form]');
  if(!f) return;
  f.addEventListener('submit', function(){
    var b = f.querySelector('[data-contact-submit]');
    if(b){ b.disabled = true; b.style.opacity = '.75'; }
  });
})();
</script>
</x-slot:scripts>
</x-front.layout>
