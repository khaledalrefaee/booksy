{{-- Customer Auth Modal — 3 steps: phone → otp → profile --}}
@php $isAr = app()->getLocale() === 'ar'; @endphp

<div id="cam-overlay" style="display:none;position:fixed;inset:0;z-index:10600;background:rgba(0,0,0,.6);" onclick="CustomerAuthModal.close()"></div>

<div id="cam-modal" style="
    display:none;position:fixed;
    bottom:0;left:50%;transform:translateX(-50%);
    z-index:10601;width:100%;max-width:440px;
    background:var(--bk-bg,#fff);
    border-radius:24px 24px 0 0;
    padding:24px 24px 32px;
    box-shadow:0 -8px 48px rgba(0,0,0,.3);
">
<style>
#cam-modal * { box-sizing:border-box; font-family: '{{ $isAr ? "Tajawal" : "Poppins" }}', sans-serif; }
.cam-handle { width:40px;height:4px;border-radius:4px;background:var(--bk-border,rgba(0,0,0,.1));margin:0 auto 20px; }
.cam-title { font-size:1.1rem;font-weight:800;color:var(--bk-text,#111);margin-bottom:6px; }
.cam-sub { font-size:.83rem;color:var(--bk-text2,rgba(0,0,0,.5));margin-bottom:24px; }
.cam-label { font-size:.78rem;font-weight:700;color:var(--bk-text2,rgba(0,0,0,.5));margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px; }
.cam-input {
    width:100%;padding:13px 16px;border-radius:12px;
    border:1.5px solid var(--bk-border,rgba(0,0,0,.1));
    background:var(--bk-card,#f7f7f5);
    color:var(--bk-text,#111);font-size:.95rem;
    outline:none;transition:border-color .2s;
    font-family:inherit;
}
.cam-input:focus { border-color:var(--bk-day-sel,#0a7aff); }
.cam-btn {
    width:100%;padding:14px;border-radius:14px;border:none;
    background:var(--bk-day-sel,#0a7aff);color:var(--bk-day-sel-text,#fff);
    font-size:.95rem;font-weight:700;cursor:pointer;margin-top:16px;
    transition:all .2s;font-family:inherit;
}
.cam-btn:hover { filter:brightness(1.08); }
.cam-btn:disabled { opacity:.5;cursor:not-allowed; }
.cam-otp-row { display:flex;gap:10px;justify-content:center;margin-bottom:8px; }
.cam-otp-box {
    width:56px;height:56px;border-radius:12px;text-align:center;
    font-size:1.4rem;font-weight:800;
    border:1.5px solid var(--bk-border,rgba(0,0,0,.1));
    background:var(--bk-card,#f7f7f5);
    color:var(--bk-text,#111);outline:none;
    transition:border-color .2s;
}
.cam-otp-box:focus { border-color:var(--bk-day-sel,#0a7aff); }
.cam-back { background:none;border:none;color:var(--bk-day-sel,#0a7aff);font-size:.82rem;cursor:pointer;padding:8px 0 0;font-family:inherit; }
.cam-dev-code { background:rgba(201,162,39,.12);border:1px solid rgba(201,162,39,.3);border-radius:8px;padding:10px 14px;margin:12px 0;font-size:.82rem;color:#C9A227;text-align:center;font-weight:600; }
.cam-timer { font-size:.78rem;color:var(--bk-text2,rgba(0,0,0,.5));text-align:center;margin-top:10px; }
.cam-spinner { width:20px;height:20px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:camspin .7s linear infinite;margin:0 auto; }
@keyframes camspin{to{transform:rotate(360deg)}}
</style>

  <div class="cam-handle"></div>

  {{-- Step 1: Phone --}}
  <div id="cam-step-1">
    <div class="cam-title">{{ $isAr ? 'أدخل رقم جوالك' : 'Enter your phone number' }}</div>
    <div class="cam-sub">{{ $isAr ? 'سنرسل لك رمز تحقق مكون من 4 أرقام' : "We'll send you a 4-digit verification code" }}</div>
    <div class="cam-label">{{ $isAr ? 'رقم الجوال' : 'Phone Number' }}</div>
    <input type="tel" id="cam-phone-input" class="cam-input"
           placeholder="{{ $isAr ? '+963 9XX XXX XXXX' : '+963 9XX XXX XXXX' }}"
           dir="ltr"
           oninput="document.getElementById('cam-send-btn').disabled = this.value.replace(/\s/g,'').length < 9">
    <button id="cam-send-btn" class="cam-btn" onclick="CustomerAuthModal.sendOtp()" disabled>
      {{ $isAr ? 'إرسال الرمز' : 'Send Code' }}
    </button>
  </div>

  {{-- Step 2: OTP --}}
  <div id="cam-step-2" style="display:none;">
    <div class="cam-title">{{ $isAr ? 'أدخل رمز التحقق' : 'Enter verification code' }}</div>
    <div class="cam-sub" id="cam-otp-sub">{{ $isAr ? 'أرسلنا رمزاً إلى رقمك' : 'Code sent to your number' }}</div>
    <div class="cam-otp-row" id="cam-otp-row">
      <input class="cam-otp-box" maxlength="1" type="tel" id="otp0">
      <input class="cam-otp-box" maxlength="1" type="tel" id="otp1">
      <input class="cam-otp-box" maxlength="1" type="tel" id="otp2">
      <input class="cam-otp-box" maxlength="1" type="tel" id="otp3">
    </div>
    <div id="cam-dev-hint" class="cam-dev-code" style="display:none;"></div>
    <div class="cam-timer" id="cam-timer"></div>
    <button class="cam-btn" id="cam-verify-btn" onclick="CustomerAuthModal.verifyOtp()">
      {{ $isAr ? 'تحقق' : 'Verify' }}
    </button>
    <div style="text-align:center;">
      <button class="cam-back" onclick="CustomerAuthModal.goStep(1)">
        {{ $isAr ? '← تغيير الرقم' : '← Change number' }}
      </button>
    </div>
  </div>

  {{-- Step 3: Profile --}}
  <div id="cam-step-3" style="display:none;">
    <div class="cam-title">{{ $isAr ? 'أكمل ملفك الشخصي' : 'Complete your profile' }}</div>
    <div class="cam-sub">{{ $isAr ? 'معلومة واحدة وتنتهي!' : 'Just one step away!' }}</div>
    <div class="cam-label">{{ $isAr ? 'الاسم' : 'Name' }}</div>
    <input type="text" id="cam-name-input" class="cam-input" placeholder="{{ $isAr ? 'اسمك الكامل' : 'Your full name' }}" style="margin-bottom:14px;">
    <div class="cam-label">{{ $isAr ? 'العمر (اختياري)' : 'Age (optional)' }}</div>
    <input type="number" id="cam-age-input" class="cam-input" placeholder="{{ $isAr ? 'مثال: 25' : 'e.g. 25' }}" min="10" max="100">
    <button class="cam-btn" onclick="CustomerAuthModal.saveProfile()">
      {{ $isAr ? 'حفظ والمتابعة' : 'Save & Continue' }}
    </button>
  </div>

</div>

<script>
window.CustomerAuthModal = (function(){
    const IS_AR      = {{ $isAr ? 'true' : 'false' }};
    const SEND_URL   = '{{ route('customer.send-otp') }}';
    const VERIFY_URL = '{{ route('customer.verify-otp') }}';
    const PROFILE_URL= '{{ route('customer.save-profile') }}';
    const TOKEN      = '{{ csrf_token() }}';

    let _callback   = null;
    let _phone      = '';
    let _timerInt   = null;

    function open(callback) {
        _callback = callback;
        goStep(1);
        document.getElementById('cam-overlay').style.display = 'block';
        document.getElementById('cam-modal').style.display   = 'block';
        document.body.style.overflow = 'hidden';
    }

    function close() {
        document.getElementById('cam-overlay').style.display = 'none';
        document.getElementById('cam-modal').style.display   = 'none';
        document.body.style.overflow = '';
        clearInterval(_timerInt);
    }

    function goStep(n) {
        [1,2,3].forEach(i => document.getElementById('cam-step-' + i).style.display = i===n?'block':'none');
    }

    async function sendOtp() {
        const phone = document.getElementById('cam-phone-input').value.trim();
        if (!phone) return;
        _phone = phone;

        const btn = document.getElementById('cam-send-btn');
        btn.innerHTML = `<div class="cam-spinner"></div>`;
        btn.disabled = true;

        const res = await fetch(SEND_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN},
            body: JSON.stringify({phone})
        }).then(r=>r.json()).catch(()=>({error:true}));

        btn.innerHTML = IS_AR ? 'إرسال الرمز' : 'Send Code';
        btn.disabled  = false;

        if (res.error || !res.sent) {
            alert(IS_AR ? 'فشل الإرسال. حاول مجدداً.' : 'Failed to send. Try again.');
            return;
        }

        document.getElementById('cam-otp-sub').textContent =
            (IS_AR ? 'أرسلنا رمزاً إلى ' : 'Code sent to ') + phone;

        // Dev hint
        if (res.dev_code) {
            const hint = document.getElementById('cam-dev-hint');
            hint.style.display = 'block';
            hint.textContent = (IS_AR ? '🔑 رمز التطوير: ' : '🔑 Dev code: ') + res.dev_code;
        }

        goStep(2);
        document.getElementById('otp0').focus();
        setupOtpBoxes();
        startTimer(240); // 4 min
    }

    function setupOtpBoxes() {
        for(let i=0;i<4;i++){
            const box = document.getElementById('otp'+i);
            box.value = '';
            box.oninput = function(){
                if(this.value && i<3) document.getElementById('otp'+(i+1)).focus();
            };
            box.onkeydown = function(e){
                if(e.key==='Backspace' && !this.value && i>0) document.getElementById('otp'+(i-1)).focus();
            };
            box.onpaste = function(e){
                const text = e.clipboardData.getData('text').replace(/\D/g,'').slice(0,4);
                for(let j=0;j<text.length;j++){
                    const b = document.getElementById('otp'+j);
                    if(b) b.value = text[j];
                }
                e.preventDefault();
            };
        }
    }

    function startTimer(seconds) {
        clearInterval(_timerInt);
        const el = document.getElementById('cam-timer');
        let remaining = seconds;
        el.textContent = formatTime(remaining);
        _timerInt = setInterval(() => {
            remaining--;
            if(remaining <= 0){
                clearInterval(_timerInt);
                el.textContent = IS_AR ? 'انتهت صلاحية الرمز' : 'Code expired';
                return;
            }
            el.textContent = formatTime(remaining);
        }, 1000);
    }

    function formatTime(s) {
        const m = Math.floor(s/60), sec = s%60;
        return `${IS_AR?'صالح لـ':'Expires in'} ${m}:${sec.toString().padStart(2,'0')}`;
    }

    async function verifyOtp() {
        const code = [0,1,2,3].map(i => document.getElementById('otp'+i).value).join('');
        if(code.length < 4){ alert(IS_AR?'أدخل الرمز كاملاً':'Enter full code'); return; }

        const btn = document.getElementById('cam-verify-btn');
        btn.innerHTML = `<div class="cam-spinner"></div>`;
        btn.disabled = true;

        const res = await fetch(VERIFY_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN},
            body: JSON.stringify({phone: _phone, code})
        }).then(r=>r.json()).catch(()=>({error:true}));

        btn.innerHTML = IS_AR ? 'تحقق' : 'Verify';
        btn.disabled  = false;

        if(res.error || !res.verified){
            alert(IS_AR ? 'الرمز غير صحيح أو منتهي الصلاحية' : 'Invalid or expired code');
            return;
        }

        clearInterval(_timerInt);

        if(res.needs_profile){
            goStep(3);
        } else {
            close();
            if(_callback) _callback();
        }
    }

    async function saveProfile() {
        const name = document.getElementById('cam-name-input').value.trim();
        const age  = document.getElementById('cam-age-input').value;
        if(!name){ alert(IS_AR?'الرجاء إدخال الاسم':'Please enter your name'); return; }

        const res = await fetch(PROFILE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN},
            body: JSON.stringify({name, age: age||null})
        }).then(r=>r.json()).catch(()=>({error:true}));

        if(res.saved){
            close();
            if(_callback) _callback();
        }
    }

    return { open, close, goStep, sendOtp, verifyOtp, saveProfile };
})();
</script>
