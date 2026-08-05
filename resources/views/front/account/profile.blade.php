@php
    $isAr    = app()->getLocale() === 'ar';
    $name    = trim($customer->name);
    $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '★';
@endphp
<x-front.layout :title="($isAr ? 'الملف الشخصي' : 'Profile') . ' | GlowRez'" :mapFab="false" :noindex="true">
<x-slot:styles>
<style>
.bkf-pf{ padding:calc(var(--bk-nav-h) + var(--bk-s8)) 0 var(--bk-s20); }
.bkf-pf-wrap{ max-width:600px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-pf-head h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h2); color:var(--bk-text); margin:0 0 var(--bk-s6); }

/* Membership card */
.bkf-mem{ position:relative; overflow:hidden; border-radius:var(--bk-r-xl); padding:var(--bk-s6); margin-bottom:var(--bk-s5);
  background:var(--bk-grad-accent); color:#fff; box-shadow:var(--bk-shadow); }
.bkf-mem::after{ content:''; position:absolute; inset-inline-end:-40px; top:-40px; width:160px; height:160px; border-radius:50%;
  background:radial-gradient(circle, color-mix(in srgb,var(--tier) 55%,transparent), transparent 70%); pointer-events:none; }
.bkf-mem-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; position:relative; z-index:1; }
.bkf-mem-badge{ display:inline-flex; align-items:center; gap:7px; font-weight:800; font-size:.92rem; padding:6px 13px; border-radius:var(--bk-r-pill);
  background:rgba(255,255,255,.16); color:#fff; backdrop-filter:blur(4px); }
.bkf-mem-badge svg{ color:#fff; }
.bkf-mem-pts{ display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:.88rem; color:#fff; opacity:.95; }
.bkf-mem-stat{ font-size:1.05rem; position:relative; z-index:1; }
.bkf-mem-stat b{ font-size:1.9rem; font-weight:800; line-height:1; }
.bkf-mem-bar{ height:7px; border-radius:99px; background:rgba(255,255,255,.22); margin:14px 0 8px; overflow:hidden; position:relative; z-index:1; }
.bkf-mem-bar span{ display:block; height:100%; border-radius:99px; background:linear-gradient(90deg,#DCC07E,#C7A15A); transition:width var(--bk-t-slow) var(--bk-ease); }
.bkf-mem-hint{ font-size:.82rem; opacity:.9; position:relative; z-index:1; }

.bkf-pf-card{ background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r-xl); box-shadow:var(--bk-shadow-xs); padding:var(--bk-s6); margin-bottom:var(--bk-s5); }
.bkf-pf-id{ display:flex; align-items:center; gap:16px; margin-bottom:var(--bk-s6); }
.bkf-pf-avatar{ flex:0 0 auto; width:64px; height:64px; border-radius:var(--bk-r-pill); display:grid; place-items:center; background:var(--bk-grad-accent); color:var(--bk-accent-ink); font-weight:800; font-size:1.6rem; }
.bkf-pf-id b{ display:block; font-size:1.1rem; color:var(--bk-text); }
.bkf-pf-id small{ color:var(--bk-text-muted); font-size:.88rem; }

.bkf-pf-sec-title{ font-size:.78rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--bk-text-muted); margin:0 0 14px; }
.bkf-field{ margin-bottom:var(--bk-s5); }
.bkf-field:last-of-type{ margin-bottom:0; }
.bkf-field label{ display:block; font-size:.8rem; font-weight:700; color:var(--bk-text-soft); margin-bottom:7px; }
.bkf-field input{ width:100%; padding:13px 15px; min-height:48px; border-radius:var(--bk-r); border:1.5px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-family:var(--bk-font-ui); font-size:.95rem; outline:none; transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.bkf-field input:focus{ border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.bkf-field input[readonly]{ background:var(--bk-surface-2); color:var(--bk-text-muted); cursor:not-allowed; }
.bkf-field .err{ font-size:.78rem; color:var(--bk-danger); margin-top:6px; }
.bkf-field .hint{ font-size:.78rem; color:var(--bk-text-muted); margin-top:6px; }

.bkf-lang-row{ display:flex; gap:10px; }
.bkf-lang-row a{ flex:1; text-align:center; padding:12px; min-height:48px; display:inline-flex; align-items:center; justify-content:center; gap:8px; border-radius:var(--bk-r); border:1.5px solid var(--bk-border); color:var(--bk-text-soft); text-decoration:none; font-weight:600; transition:all var(--bk-t) ease; }
.bkf-lang-row a.on{ border-color:var(--bk-accent); background:var(--bk-accent-wash); color:var(--bk-accent); }
.bkf-lang-row a:hover{ border-color:var(--bk-accent); }

.bkf-pf-danger{ border-color:color-mix(in srgb,var(--bk-danger) 28%,transparent); }
.bkf-pf-danger .bkf-pf-sec-title{ color:var(--bk-danger); }
.bkf-pf-danger p{ color:var(--bk-text-muted); font-size:.88rem; line-height:1.7; margin:0 0 var(--bk-s4); }
.bkf-btn-danger-soft{ background:var(--bk-danger-bg); color:var(--bk-danger); border-color:transparent; }
.bkf-btn-danger{ background:var(--bk-danger); color:#fff; border-color:transparent; }
.bkf-del-confirm{ margin-top:var(--bk-s4); padding:16px; border-radius:var(--bk-r); background:var(--bk-danger-bg); }
.bkf-del-confirm[hidden]{ display:none; }
.bkf-del-confirm p{ display:flex; gap:9px; align-items:flex-start; margin:0 0 14px; color:var(--bk-text); }
.bkf-del-confirm p svg{ flex:0 0 auto; color:var(--bk-danger); margin-top:1px; }
.bkf-del-row{ display:flex; gap:10px; }
.bkf-del-row > *{ flex:1; }

.bkf-flash{ display:flex; align-items:center; gap:10px; padding:13px 16px; border-radius:var(--bk-r); margin-bottom:var(--bk-s5); font-size:.9rem; font-weight:600; }
.bkf-flash.ok{ background:var(--bk-success-bg); color:var(--bk-success); }
</style>
</x-slot:styles>

<section class="bkf-pf">
  <div class="bkf-pf-wrap">
    <div class="bkf-pf-head"><h1>{{ $isAr ? 'الملف الشخصي' : 'Profile' }}</h1></div>

    @if(session('account_success'))
      <div class="bkf-flash ok"><x-icon name="check-circle" :size="18"/>{{ session('account_success') }}</div>
    @endif

    {{-- Membership (tier + points + visits) --}}
    @php
      $tc = $tier->color();
      $toNext = max(0, $loyalAt - $visits);
      $isMember = $tier->value !== 'new';
    @endphp
    <div class="bkf-mem" style="--tier:{{ $tc }}">
      <div class="bkf-mem-top">
        <span class="bkf-mem-badge">{!! $tier->svg(16) !!}{{ $tier->label() }}</span>
        <span class="bkf-mem-pts"><x-icon name="sparkles" :size="16"/>{{ number_format($points) }} {{ $isAr ? 'نقطة' : 'pts' }}</span>
      </div>
      <div class="bkf-mem-stat"><b>{{ $visits }}</b> {{ $isAr ? 'زيارة مكتملة' : ($visits === 1 ? 'completed visit' : 'completed visits') }}</div>
      @unless($isMember)
        <div class="bkf-mem-bar"><span style="width:{{ $loyalAt ? min(100, round($visits / $loyalAt * 100)) : 0 }}%"></span></div>
        <small class="bkf-mem-hint">{{ $isAr ? "زيارتان أو أكثر تقرّبك من مستوى «موالٍ» — باقٍ $toNext زيارة." : "$toNext more visit".($toNext === 1 ? '' : 's')." to reach Loyal." }}</small>
      @else
        <small class="bkf-mem-hint">{{ $isAr ? 'شكراً لولائك — استمتع بمزايا عضويتك.' : 'Thanks for your loyalty — enjoy your member perks.' }}</small>
      @endunless
    </div>

    {{-- Identity + edit --}}
    <div class="bkf-pf-card">
      <div class="bkf-pf-id">
        <span class="bkf-pf-avatar">{{ $initial }}</span>
        <div>
          <b>{{ $name !== '' ? $name : ($isAr ? 'مرحباً بك' : 'Welcome') }}</b>
          <small dir="ltr">{{ $customer->phone }}</small>
        </div>
      </div>

      <form method="POST" action="{{ route('account.profile.update') }}">
        @csrf
        <div class="bkf-field">
          <label for="pf-name">{{ $isAr ? 'الاسم' : 'Name' }}</label>
          <input id="pf-name" type="text" name="name" value="{{ old('name', $customer->name) }}" autocomplete="name" required maxlength="80">
          @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="bkf-field">
          <label for="pf-age">{{ $isAr ? 'العمر (اختياري)' : 'Age (optional)' }}</label>
          <input id="pf-age" type="number" name="age" value="{{ old('age', $customer->age) }}" inputmode="numeric" min="10" max="100" placeholder="{{ $isAr ? 'مثال: 25' : 'e.g. 25' }}">
          @error('age')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="bkf-field">
          <label for="pf-phone">{{ $isAr ? 'رقم الجوال' : 'Phone' }}</label>
          <input id="pf-phone" type="text" value="{{ $customer->phone }}" dir="ltr" readonly>
          <div class="hint">{{ $isAr ? 'رقمك مرتبط بحسابك ولا يمكن تغييره.' : 'Your number is tied to your account and can’t be changed.' }}</div>
        </div>
        <button type="submit" class="bkf-btn bkf-btn-primary bkf-btn-block" style="margin-top:var(--bk-s4);">{{ $isAr ? 'حفظ التغييرات' : 'Save changes' }}</button>
      </form>
    </div>

    {{-- Language --}}
    <div class="bkf-pf-card">
      <p class="bkf-pf-sec-title">{{ $isAr ? 'اللغة' : 'Language' }}</p>
      <div class="bkf-lang-row">
        <a href="{{ route('locale.switch', 'ar') }}" class="{{ $isAr ? 'on' : '' }}">العربية</a>
        <a href="{{ route('locale.switch', 'en') }}" class="{{ !$isAr ? 'on' : '' }}">English</a>
      </div>
    </div>

    {{-- Danger zone --}}
    <div class="bkf-pf-card bkf-pf-danger">
      <p class="bkf-pf-sec-title">{{ $isAr ? 'حذف الحساب' : 'Delete account' }}</p>
      <p>{{ $isAr ? 'سيتم حذف بياناتك الشخصية وإلغاء وصولك للحساب نهائياً. لا يمكن التراجع عن هذا الإجراء.' : 'Your personal data will be removed and your access permanently revoked. This cannot be undone.' }}</p>
      <button type="button" class="bkf-btn bkf-btn-danger-soft bkf-btn-block" data-del-open><x-icon name="alert" :size="18"/>{{ $isAr ? 'حذف حسابي' : 'Delete my account' }}</button>

      <div class="bkf-del-confirm" data-del-panel hidden>
        <p><x-icon name="alert" :size="20"/>{{ $isAr ? 'هل أنت متأكد؟ سيتم حذف بياناتك نهائياً.' : 'Are you sure? Your data will be permanently removed.' }}</p>
        <div class="bkf-del-row">
          <button type="button" class="bkf-btn bkf-btn-soft" data-del-close>{{ $isAr ? 'تراجع' : 'Keep account' }}</button>
          <form method="POST" action="{{ route('account.delete') }}">
            @csrf
            <button type="submit" class="bkf-btn bkf-btn-danger bkf-btn-block" data-del-submit>{{ $isAr ? 'نعم، احذف' : 'Yes, delete' }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var open = document.querySelector('[data-del-open]');
  var panel = document.querySelector('[data-del-panel]');
  var close = document.querySelector('[data-del-close]');
  if(open && panel) open.addEventListener('click', function(){ panel.hidden = false; open.style.display='none'; });
  if(close && panel && open) close.addEventListener('click', function(){ panel.hidden = true; open.style.display=''; });
  var sub = document.querySelector('[data-del-submit]');
  if(sub) sub.closest('form').addEventListener('submit', function(){ sub.disabled = true; sub.textContent = '…'; });
})();
</script>
</x-slot:scripts>
</x-front.layout>
