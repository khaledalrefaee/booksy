{{-- Customer Auth Modal — phone → OTP → profile.
     Styled with the site's --bk-* tokens (dark-mode safe). Searchable country
     picker (by name or dial code) acts as the phone prefix; the user types only
     the local number. Syria uses the NEW flag (green / three red stars / black). --}}
@php
    $isAr = app()->getLocale() === 'ar';
    // [iso, dial, emoji, name_ar, name_en]. Syria renders a custom SVG flag.
    $countries = [
        ['sy','963','🇸🇾','سوريا','Syria'],
        ['lb','961','🇱🇧','لبنان','Lebanon'],
        ['jo','962','🇯🇴','الأردن','Jordan'],
        ['iq','964','🇮🇶','العراق','Iraq'],
        ['sa','966','🇸🇦','السعودية','Saudi Arabia'],
        ['ae','971','🇦🇪','الإمارات','UAE'],
        ['qa','974','🇶🇦','قطر','Qatar'],
        ['kw','965','🇰🇼','الكويت','Kuwait'],
        ['bh','973','🇧🇭','البحرين','Bahrain'],
        ['om','968','🇴🇲','عُمان','Oman'],
        ['ps','970','🇵🇸','فلسطين','Palestine'],
        ['eg','20','🇪🇬','مصر','Egypt'],
        ['ye','967','🇾🇪','اليمن','Yemen'],
        ['tr','90','🇹🇷','تركيا','Türkiye'],
        ['de','49','🇩🇪','ألمانيا','Germany'],
        ['se','46','🇸🇪','السويد','Sweden'],
        ['nl','31','🇳🇱','هولندا','Netherlands'],
        ['fr','33','🇫🇷','فرنسا','France'],
        ['gb','44','🇬🇧','بريطانيا','United Kingdom'],
        ['us','1','🇺🇸','أمريكا','USA'],
        ['ca','1','🇨🇦','كندا','Canada'],
        ['my','60','🇲🇾','ماليزيا','Malaysia'],
        ['id','62','🇮🇩','إندونيسيا','Indonesia'],
        ['in','91','🇮🇳','الهند','India'],
        ['pk','92','🇵🇰','باكستان','Pakistan'],
        ['au','61','🇦🇺','أستراليا','Australia'],
        ['ru','7','🇷🇺','روسيا','Russia'],
        ['ua','380','🇺🇦','أوكرانيا','Ukraine'],
        ['it','39','🇮🇹','إيطاليا','Italy'],
        ['es','34','🇪🇸','إسبانيا','Spain'],
        ['be','32','🇧🇪','بلجيكا','Belgium'],
        ['ch','41','🇨🇭','سويسرا','Switzerland'],
        ['at','43','🇦🇹','النمسا','Austria'],
        ['dk','45','🇩🇰','الدنمارك','Denmark'],
        ['no','47','🇳🇴','النرويج','Norway'],
        ['ma','212','🇲🇦','المغرب','Morocco'],
        ['dz','213','🇩🇿','الجزائر','Algeria'],
        ['tn','216','🇹🇳','تونس','Tunisia'],
        ['ly','218','🇱🇾','ليبيا','Libya'],
        ['sd','249','🇸🇩','السودان','Sudan'],
    ];
    $jsCountries = collect($countries)->map(fn($c) => [
        'iso' => $c[0], 'dial' => $c[1], 'flag' => $c[2],
        'ar' => $c[3], 'en' => $c[4],
    ])->values();
@endphp

<div id="cam-overlay" onclick="CustomerAuthModal.close()"></div>

<div id="cam-modal" role="dialog" aria-modal="true" aria-labelledby="cam-heading">
<style>
#cam-overlay{ display:none; position:fixed; inset:0; z-index:11000; background:rgba(20,24,14,.55); backdrop-filter:blur(3px); animation:camFade .2s ease; }
@keyframes camFade{ from{ opacity:0; } to{ opacity:1; } }
#cam-modal{
    display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
    z-index:11001; width:calc(100% - 32px); max-width:440px;
    background:var(--bk-surface); color:var(--bk-text);
    border:1px solid var(--bk-border);
    border-radius:var(--bk-r-2xl);
    padding:24px 22px 26px;
    box-shadow:0 30px 80px rgba(0,0,0,.42);
    font-family:'{{ $isAr ? "Tajawal" : "Poppins" }}', var(--bk-font-ui);
    animation:camIn .24s cubic-bezier(.16,1,.3,1);
}
@keyframes camIn{ from{ opacity:0; transform:translate(-50%,-46%) scale(.96); } to{ opacity:1; transform:translate(-50%,-50%) scale(1); } }
@media (prefers-reduced-motion: reduce){ #cam-modal, #cam-overlay{ animation:none; } }
#cam-modal *{ box-sizing:border-box; font-family:inherit; }
.cam-handle{ width:44px; height:5px; border-radius:99px; background:var(--bk-border-strong); opacity:.5; margin:0 auto 18px; }
.cam-x{ position:absolute; top:16px; inset-inline-end:16px; width:34px; height:34px; display:grid; place-items:center;
    border:none; border-radius:var(--bk-r-pill); background:var(--bk-surface-2); color:var(--bk-text-muted); cursor:pointer; font-size:1.3rem; line-height:1; transition:color var(--bk-t) ease; }
.cam-x:hover{ color:var(--bk-text); }
.cam-title{ font-size:1.2rem; font-weight:800; color:var(--bk-text); margin-bottom:6px; letter-spacing:-.01em; }
.cam-sub{ font-size:.86rem; color:var(--bk-text-muted); margin-bottom:22px; line-height:1.6; }
.cam-label{ font-size:.76rem; font-weight:700; color:var(--bk-text-soft); margin-bottom:8px; text-transform:uppercase; letter-spacing:.05em; }

/* Phone row with searchable country prefix (always LTR) */
.cam-cc{ position:relative; }
.cam-phone{ display:flex; align-items:stretch; direction:ltr; border:1.5px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface-2); overflow:visible; transition:border-color var(--bk-t) ease, box-shadow var(--bk-t) ease; }
.cam-phone:focus-within{ border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cam-cc-btn{ display:flex; align-items:center; gap:7px; padding:0 12px; min-height:50px; border:none; border-radius:var(--bk-r) 0 0 var(--bk-r); background:var(--bk-surface-3); border-inline-end:1px solid var(--bk-border); color:var(--bk-text); font-weight:700; font-size:.95rem; cursor:pointer; white-space:nowrap; }
.cam-cc-btn .chev{ width:16px; height:16px; color:var(--bk-text-muted); transition:transform var(--bk-t) var(--bk-spring); }
.cam-cc.open .cam-cc-btn .chev{ transform:rotate(180deg); }
.cam-flagsvg{ width:22px; height:15px; border-radius:2px; display:block; box-shadow:0 0 0 1px rgba(0,0,0,.10); }
.cam-emojiflag{ font-size:1.15rem; line-height:1; }
.cam-phone input{ flex:1; min-width:0; border:none; background:transparent; padding:14px; min-height:50px; color:var(--bk-text); font-size:1.02rem; letter-spacing:.02em; outline:none; }
.cam-phone input::placeholder{ color:var(--bk-text-muted); }

.cam-cc-panel{ position:absolute; top:calc(100% + 8px); inset-inline:0; z-index:6; direction:{{ $isAr ? 'rtl' : 'ltr' }};
    background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--bk-r); box-shadow:var(--bk-shadow-lg); overflow:hidden; }
.cam-cc-panel[hidden]{ display:none; }
.cam-cc-search{ padding:10px; border-bottom:1px solid var(--bk-border); }
.cam-cc-search input{ width:100%; padding:11px 13px; min-height:44px; border:1.5px solid var(--bk-border); border-radius:var(--bk-r-sm); background:var(--bk-surface-2); color:var(--bk-text); font-size:.9rem; font-family:inherit; outline:none; }
.cam-cc-search input:focus{ border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cam-cc-list{ list-style:none; margin:0; padding:6px; max-height:200px; overflow-y:auto; overscroll-behavior:contain; }
.cam-cc-item{ display:flex; align-items:center; gap:10px; width:100%; padding:10px 12px; min-height:46px; border:none; background:transparent; color:var(--bk-text); cursor:pointer; border-radius:var(--bk-r-sm); font-size:.9rem; text-align:start; font-family:inherit; }
.cam-cc-item:hover, .cam-cc-item.sel{ background:var(--bk-accent-wash); color:var(--bk-accent); }
.cam-cc-item .nm{ flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cam-cc-item .cd{ color:var(--bk-text-muted); font-weight:700; direction:ltr; }
.cam-cc-item:hover .cd, .cam-cc-item.sel .cd{ color:var(--bk-accent); }
.cam-cc-empty{ padding:18px; text-align:center; color:var(--bk-text-muted); font-size:.85rem; }

.cam-help{ font-size:.76rem; color:var(--bk-text-muted); margin-top:8px; }
.cam-err{ font-size:.8rem; color:var(--bk-danger); margin-top:8px; min-height:1em; }

.cam-btn{
    width:100%; padding:15px; margin-top:20px; min-height:54px; border:none; border-radius:var(--bk-r);
    background:var(--bk-grad-gold); color:var(--bk-gold-ink);
    font-size:.98rem; font-weight:800; cursor:pointer; box-shadow:var(--bk-shadow-accent);
    transition:filter var(--bk-t) ease, transform var(--bk-t) ease;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.cam-btn:hover{ filter:brightness(1.04); transform:translateY(-1px); }
.cam-btn:active{ transform:translateY(0); }
.cam-btn:disabled{ opacity:.5; cursor:not-allowed; box-shadow:none; transform:none; filter:none; }

.cam-input{ width:100%; padding:13px 14px; min-height:50px; border:1.5px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface-2); color:var(--bk-text); font-size:.95rem; font-family:inherit; outline:none; transition:border-color var(--bk-t) ease, box-shadow var(--bk-t) ease; }
.cam-input:focus{ border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cam-input + .cam-label{ margin-top:14px; }

.cam-otp-row{ display:flex; gap:10px; justify-content:center; margin-bottom:10px; direction:ltr; }
.cam-otp-box{ width:58px; height:64px; border-radius:var(--bk-r); text-align:center; font-size:1.5rem; font-weight:800; border:1.5px solid var(--bk-border); background:var(--bk-surface-2); color:var(--bk-text); outline:none; transition:border-color var(--bk-t) ease, box-shadow var(--bk-t) ease; }
.cam-otp-box:focus{ border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cam-back{ background:none; border:none; color:var(--bk-accent); font-size:.85rem; font-weight:600; cursor:pointer; padding:12px 0 0; }
.cam-back:hover{ text-decoration:underline; }
.cam-dev-code{ background:var(--bk-gold-soft); border:1px solid color-mix(in srgb,var(--bk-gold-strong) 35%,transparent); border-radius:var(--bk-r-sm); padding:10px 14px; margin:14px 0; font-size:.82rem; color:var(--bk-gold-strong); text-align:center; font-weight:700; }
.cam-timer{ font-size:.8rem; color:var(--bk-text-muted); text-align:center; margin-top:12px; }
.cam-spinner{ width:20px; height:20px; border:2.5px solid rgba(0,0,0,.2); border-top-color:var(--bk-gold-ink); border-radius:50%; animation:camspin .7s linear infinite; }
@keyframes camspin{ to{ transform:rotate(360deg); } }
</style>

  <button type="button" class="cam-x" onclick="CustomerAuthModal.close()" aria-label="{{ $isAr ? 'إغلاق' : 'Close' }}">&times;</button>
  <div class="cam-handle"></div>

  {{-- Step 1: Phone --}}
  <div id="cam-step-1">
    <div class="cam-title" id="cam-heading">{{ $isAr ? 'أدخل رقم جوالك' : 'Enter your phone number' }}</div>
    <div class="cam-sub">{{ $isAr ? 'سنرسل لك رمز تحقق مكوّن من 4 أرقام عبر رسالة.' : "We'll send you a 4-digit verification code by message." }}</div>

    <div class="cam-label">{{ $isAr ? 'رقم الجوال' : 'Phone number' }}</div>
    <div class="cam-cc" id="cam-cc" data-cc>
      <div class="cam-phone">
        <button type="button" class="cam-cc-btn" id="cam-cc-btn" aria-haspopup="listbox" aria-expanded="false">
          <span class="cam-flagslot" id="cam-cc-flag"></span>
          <span id="cam-cc-code">+963</span>
          <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <input type="tel" id="cam-phone-input" inputmode="tel" autocomplete="tel-national" placeholder="9XX XXX XXX">
      </div>

      <div class="cam-cc-panel" id="cam-cc-panel" hidden>
        <div class="cam-cc-search">
          <input type="text" id="cam-cc-search" autocomplete="off" placeholder="{{ $isAr ? 'ابحث بالدولة أو الرمز…' : 'Search country or code…' }}">
        </div>
        <ul class="cam-cc-list" id="cam-cc-list" role="listbox" aria-label="{{ $isAr ? 'الدول' : 'Countries' }}"></ul>
        <div class="cam-cc-empty" id="cam-cc-empty" hidden>{{ $isAr ? 'لا توجد نتائج' : 'No results' }}</div>
      </div>
    </div>

    <div class="cam-help">{{ $isAr ? 'اكتب رقمك بدون رمز الدولة، وبدون الصفر في البداية.' : 'Enter your number without the country code or a leading zero.' }}</div>
    <div class="cam-err" id="cam-phone-err"></div>

    <button id="cam-send-btn" class="cam-btn" onclick="CustomerAuthModal.sendOtp()" disabled>{{ $isAr ? 'إرسال الرمز' : 'Send code' }}</button>
  </div>

  {{-- Step 2: OTP --}}
  <div id="cam-step-2" style="display:none;">
    <div class="cam-title">{{ $isAr ? 'أدخل رمز التحقق' : 'Enter verification code' }}</div>
    <div class="cam-sub" id="cam-otp-sub">{{ $isAr ? 'أرسلنا رمزاً إلى رقمك' : 'Code sent to your number' }}</div>
    <div class="cam-otp-row" id="cam-otp-row">
      <input class="cam-otp-box" maxlength="1" type="tel" inputmode="numeric" id="otp0">
      <input class="cam-otp-box" maxlength="1" type="tel" inputmode="numeric" id="otp1">
      <input class="cam-otp-box" maxlength="1" type="tel" inputmode="numeric" id="otp2">
      <input class="cam-otp-box" maxlength="1" type="tel" inputmode="numeric" id="otp3">
    </div>
    <div id="cam-dev-hint" class="cam-dev-code" style="display:none;"></div>
    <div class="cam-timer" id="cam-timer"></div>
    <div class="cam-err" id="cam-otp-err"></div>
    <button class="cam-btn" id="cam-verify-btn" onclick="CustomerAuthModal.verifyOtp()">{{ $isAr ? 'تحقّق' : 'Verify' }}</button>
    <div style="text-align:center;">
      <button class="cam-back" onclick="CustomerAuthModal.goStep(1)">{{ $isAr ? 'تغيير الرقم' : 'Change number' }}</button>
    </div>
  </div>

  {{-- Step 3: Profile --}}
  <div id="cam-step-3" style="display:none;">
    <div class="cam-title">{{ $isAr ? 'أكمل ملفك الشخصي' : 'Complete your profile' }}</div>
    <div class="cam-sub">{{ $isAr ? 'خطوة واحدة وتنتهي!' : 'Just one step away!' }}</div>
    <div class="cam-label">{{ $isAr ? 'الاسم' : 'Name' }}</div>
    <input type="text" id="cam-name-input" class="cam-input" placeholder="{{ $isAr ? 'اسمك الكامل' : 'Your full name' }}" autocomplete="name">
    <div class="cam-label" style="margin-top:14px;">{{ $isAr ? 'العمر (اختياري)' : 'Age (optional)' }}</div>
    <input type="number" id="cam-age-input" class="cam-input" placeholder="{{ $isAr ? 'مثال: 25' : 'e.g. 25' }}" min="10" max="100" inputmode="numeric">
    <div class="cam-err" id="cam-name-err"></div>
    <button class="cam-btn" onclick="CustomerAuthModal.saveProfile()">{{ $isAr ? 'حفظ والمتابعة' : 'Save & Continue' }}</button>
  </div>

</div>

<script>
window.CustomerAuthModal = (function(){
    const IS_AR      = {{ $isAr ? 'true' : 'false' }};
    const SEND_URL   = '{{ route('customer.send-otp') }}';
    const VERIFY_URL = '{{ route('customer.verify-otp') }}';
    const PROFILE_URL= '{{ route('customer.save-profile') }}';
    const TOKEN      = '{{ csrf_token() }}';
    const MIN_LOCAL  = 6, MAX_LOCAL = 14;
    const COUNTRIES  = @json($jsCountries);
    // New Syrian flag: green / white (three red stars) / black.
    const SY_FLAG = '<svg class="cam-flagsvg" viewBox="0 0 24 16" aria-hidden="true">'
      + '<rect width="24" height="16" fill="#fff"/>'
      + '<rect width="24" height="5.34" fill="#007A3D"/>'
      + '<rect width="24" height="5.34" y="10.66" fill="#000"/>'
      + '<g fill="#CE1126">'
      + '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9L12 3Z" transform="translate(7 8) scale(0.16) translate(-12 -12.3)"/>'
      + '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9L12 3Z" transform="translate(12 8) scale(0.16) translate(-12 -12.3)"/>'
      + '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9L12 3Z" transform="translate(17 8) scale(0.16) translate(-12 -12.3)"/>'
      + '</g></svg>';

    let _callback = null, _phone = '', _timerInt = null;
    let _sel = COUNTRIES[0]; // Syria

    const $ = (id) => document.getElementById(id);
    const flagHtml = (c) => c.iso === 'sy' ? SY_FLAG : '<span class="cam-emojiflag">' + c.flag + '</span>';

    function open(callback) {
        _callback = callback;
        goStep(1);
        $('cam-overlay').style.display = 'block';
        $('cam-modal').style.display   = 'block';
        document.body.style.overflow = 'hidden';
        selectCountry(_sel, false);
        setTimeout(() => $('cam-phone-input').focus(), 100);
    }
    function close() {
        closePanel();
        $('cam-overlay').style.display = 'none';
        $('cam-modal').style.display   = 'none';
        document.body.style.overflow = '';
        clearInterval(_timerInt);
    }
    function goStep(n) {
        closePanel();
        [1,2,3].forEach(i => $('cam-step-' + i).style.display = i === n ? 'block' : 'none');
        ['cam-phone-err','cam-otp-err','cam-name-err'].forEach(id => { if($(id)) $(id).textContent=''; });
    }

    /* ── Country picker (searchable) ── */
    function renderList(q) {
        q = (q || '').trim().toLowerCase().replace(/^\+/, '');
        const list = $('cam-cc-list'); list.innerHTML = '';
        const matches = COUNTRIES.filter(c =>
            !q || c.ar.includes(q) || c.en.toLowerCase().includes(q) || c.dial.startsWith(q) || c.iso === q
        );
        $('cam-cc-empty').hidden = matches.length > 0;
        matches.forEach(c => {
            const li = document.createElement('li');
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'cam-cc-item' + (c.iso === _sel.iso ? ' sel' : '');
            b.setAttribute('role', 'option');
            b.innerHTML = flagHtml(c) + '<span class="nm">' + (IS_AR ? c.ar : c.en) + '</span><span class="cd">+' + c.dial + '</span>';
            b.addEventListener('click', () => { selectCountry(c, true); });
            li.appendChild(b); list.appendChild(li);
        });
    }
    function selectCountry(c, closeAfter) {
        _sel = c;
        $('cam-cc-flag').innerHTML = flagHtml(c);
        $('cam-cc-code').textContent = '+' + c.dial;
        validatePhone();
        if (closeAfter) { closePanel(); $('cam-phone-input').focus(); }
    }
    function openPanel() {
        $('cam-cc').classList.add('open');
        $('cam-cc-btn').setAttribute('aria-expanded', 'true');
        $('cam-cc-panel').hidden = false;
        $('cam-cc-search').value = ''; renderList('');
        setTimeout(() => $('cam-cc-search').focus(), 50);
    }
    function closePanel() {
        var cc = $('cam-cc'); if (!cc) return;
        cc.classList.remove('open');
        $('cam-cc-btn').setAttribute('aria-expanded', 'false');
        $('cam-cc-panel').hidden = true;
    }
    function panelOpen() { return !$('cam-cc-panel').hidden; }

    /* ── Phone helpers ── */
    function localDigits() { return $('cam-phone-input').value.replace(/\D/g, '').replace(/^0+/, ''); }
    function fullPhone()   { return '+' + _sel.dial + localDigits(); }
    function validatePhone() {
        const n = localDigits().length;
        const ok = n >= MIN_LOCAL && n <= MAX_LOCAL;
        $('cam-send-btn').disabled = !ok;
        $('cam-phone-err').textContent = (n > 0 && !ok) ? (IS_AR ? 'أدخل رقماً صحيحاً.' : 'Enter a valid number.') : '';
        return ok;
    }

    async function sendOtp() {
        if (!validatePhone()) return;
        _phone = fullPhone();
        const btn = $('cam-send-btn');
        btn.innerHTML = '<div class="cam-spinner"></div>'; btn.disabled = true;
        const res = await fetch(SEND_URL, {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN,'Accept':'application/json'},
            body: JSON.stringify({ phone: _phone })
        }).then(r => r.json()).catch(() => ({ error:true }));
        btn.innerHTML = IS_AR ? 'إرسال الرمز' : 'Send code'; btn.disabled = false;
        if (res.error || !res.sent) { $('cam-phone-err').textContent = res.message || (IS_AR ? 'فشل الإرسال. حاول مجدداً.' : 'Failed to send. Try again.'); return; }
        $('cam-otp-sub').textContent = (IS_AR ? 'أرسلنا رمزاً إلى ' : 'Code sent to ') + _phone;
        if (res.dev_code) { const h = $('cam-dev-hint'); h.style.display = 'block'; h.textContent = (IS_AR ? '🔑 رمز التطوير: ' : '🔑 Dev code: ') + res.dev_code; }
        goStep(2); $('otp0').focus(); setupOtpBoxes(); startTimer(240);
    }

    function setupOtpBoxes() {
        for (let i = 0; i < 4; i++) {
            const box = $('otp' + i); box.value = '';
            box.oninput = function(){ if (this.value && i < 3) $('otp' + (i+1)).focus(); };
            box.onkeydown = function(e){
                if (e.key === 'Backspace' && !this.value && i > 0) $('otp' + (i-1)).focus();
                if (e.key === 'Enter') { e.preventDefault(); verifyOtp(); }
            };
            box.onpaste = function(e){ const t = e.clipboardData.getData('text').replace(/\D/g,'').slice(0,4); for (let j=0;j<t.length;j++){ const b=$('otp'+j); if(b) b.value=t[j]; } e.preventDefault(); };
        }
    }
    function startTimer(seconds) {
        clearInterval(_timerInt);
        const el = $('cam-timer'); let r = seconds; el.textContent = fmt(r);
        _timerInt = setInterval(() => { r--; if (r <= 0) { clearInterval(_timerInt); el.textContent = IS_AR ? 'انتهت صلاحية الرمز' : 'Code expired'; return; } el.textContent = fmt(r); }, 1000);
    }
    function fmt(s){ const m = Math.floor(s/60), sec = s%60; return (IS_AR ? 'صالح لـ ' : 'Expires in ') + m + ':' + String(sec).padStart(2,'0'); }

    async function verifyOtp() {
        const code = [0,1,2,3].map(i => $('otp' + i).value).join('');
        if (code.length < 4) { $('cam-otp-err').textContent = IS_AR ? 'أدخل الرمز كاملاً.' : 'Enter the full code.'; return; }
        const btn = $('cam-verify-btn'); btn.innerHTML = '<div class="cam-spinner"></div>'; btn.disabled = true;
        const res = await fetch(VERIFY_URL, {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN,'Accept':'application/json'},
            body: JSON.stringify({ phone: _phone, code })
        }).then(r => r.json()).catch(() => ({ error:true }));
        btn.innerHTML = IS_AR ? 'تحقّق' : 'Verify'; btn.disabled = false;
        if (res.error || !res.verified) { $('cam-otp-err').textContent = IS_AR ? 'الرمز غير صحيح أو منتهي الصلاحية.' : 'Invalid or expired code.'; return; }
        clearInterval(_timerInt);
        if (res.needs_profile) { goStep(3); $('cam-name-input').focus(); }
        else { close(); if (_callback) _callback(); }
    }

    async function saveProfile() {
        const name = $('cam-name-input').value.trim();
        const age  = $('cam-age-input').value;
        if (!name) { $('cam-name-err').textContent = IS_AR ? 'الرجاء إدخال الاسم.' : 'Please enter your name.'; return; }
        const res = await fetch(PROFILE_URL, {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN,'Accept':'application/json'},
            body: JSON.stringify({ name, age: age || null })
        }).then(r => r.json()).catch(() => ({ error:true }));
        if (res.saved) { close(); if (_callback) _callback(); }
        else { $('cam-name-err').textContent = IS_AR ? 'تعذّر الحفظ. حاول مجدداً.' : 'Could not save. Try again.'; }
    }

    // Wire (elements already parsed above this script)
    (function wire(){
        $('cam-cc-btn').addEventListener('click', (e) => { e.stopPropagation(); panelOpen() ? closePanel() : openPanel(); });
        $('cam-cc-search').addEventListener('input', function(){ renderList(this.value); });
        $('cam-phone-input').addEventListener('input', validatePhone);
        $('cam-phone-input').addEventListener('keydown', (e) => { if (e.key === 'Enter' && !panelOpen()) { e.preventDefault(); sendOtp(); } });
        $('cam-name-input').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); saveProfile(); } });
        document.addEventListener('click', (e) => { if (!$('cam-cc').contains(e.target)) closePanel(); });
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            if (panelOpen()) { closePanel(); return; }
            if ($('cam-modal').style.display === 'block') close();
        });
        renderList('');
    })();

    return { open, close, goStep, sendOtp, verifyOtp, saveProfile };
})();
</script>
