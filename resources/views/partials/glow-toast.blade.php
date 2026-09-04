{{--
  ┌─────────────────────────────────────────────────────────────────────────┐
  │  GlowRez — Unified Premium Toast + Sound Notification System              │
  │  Single source of truth for ALL in-app notifications (owner / company /   │
  │  front). Theme-aware (olive + gold, light/dark via --bk-* tokens with     │
  │  prefers-color-scheme fallback), Feather-style SVG icons (no emoji),      │
  │  soft WebAudio chime (throttled, mutable), reduced-motion aware.          │
  │                                                                           │
  │  Public API (all idempotent, safe to include once per page):             │
  │    GlowToast.success(msg, {title, duration})                              │
  │    GlowToast.error(msg, opts)  .warning(msg, opts)  .info(msg, opts)     │
  │    GlowToast.show(msg, type, opts)   GlowToast.dismiss(el)                │
  │    GlowToast.mute(bool)  GlowToast.isMuted()                              │
  │  Back-compat aliases: window.bkToast(msg,type), bkDismissToast,           │
  │    bkDismissCt  (existing call-sites keep working unchanged).             │
  └─────────────────────────────────────────────────────────────────────────┘
--}}
@once
@php $isAr = app()->getLocale() === 'ar'; @endphp
<style>
:root{
    --gt-radius:14px;
    --gt-accent-info:var(--bk-info,#3A5A8C);
    --gt-accent-success:var(--bk-success,#3F6B2E);
    --gt-accent-warning:var(--bk-warning,#B45309);
    --gt-accent-error:var(--bk-danger,#B23A48);
    --gt-accent-brand:var(--bk-gold-strong,#A17A2E);
}
.gt-stack{
    position:fixed; top:20px; {{ $isAr ? 'left' : 'right' }}:20px;
    z-index:2147483000;
    display:flex; flex-direction:column; gap:12px;
    width:380px; max-width:calc(100vw - 40px);
    pointer-events:none;
}
.gt{
    --gt-bg:var(--bk-surface,#FFFFFF);
    --gt-border:var(--bk-border,#E7E1D3);
    --gt-text:var(--bk-text,#22251D);
    --gt-text-soft:var(--bk-text-soft,#4B4E42);
    --gt-shadow:var(--bk-shadow-xl,0 24px 60px rgba(40,40,30,.18));
    --gt-accent:var(--gt-accent-info);
    position:relative; overflow:hidden;
    display:flex; align-items:flex-start; gap:13px;
    padding:15px 16px 15px 17px;
    background:var(--gt-bg);
    border:1px solid var(--gt-border);
    border-radius:var(--gt-radius);
    box-shadow:var(--gt-shadow);
    pointer-events:auto;
    will-change:transform,opacity;
    animation:gtIn .5s cubic-bezier(.16,1,.3,1) both;
}
/* Colored identity rail on the leading edge */
.gt::before{
    content:''; position:absolute; top:0; bottom:0;
    {{ $isAr ? 'right' : 'left' }}:0; width:4px;
    background:var(--gt-accent);
}
.gt.gt--success{ --gt-accent:var(--gt-accent-success); }
.gt.gt--warning{ --gt-accent:var(--gt-accent-warning); }
.gt.gt--error  { --gt-accent:var(--gt-accent-error);   }
.gt.gt--info   { --gt-accent:var(--gt-accent-info);    }
.gt.gt--brand  { --gt-accent:var(--gt-accent-brand);   }

.gt.gt--out{ animation:gtOut .3s cubic-bezier(.4,0,1,1) forwards; }

@keyframes gtIn{
    from{ opacity:0; transform:translateY(-14px) scale(.97); }
    to  { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes gtOut{
    to{ opacity:0; transform:translateX({{ $isAr ? '-' : '' }}44px) scale(.94); }
}

.gt-ic{
    flex-shrink:0; width:34px; height:34px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    color:var(--gt-accent);
    background:color-mix(in srgb, var(--gt-accent) 14%, transparent);
}
.gt-ic svg{ width:19px; height:19px; stroke:currentColor; stroke-width:2.4; fill:none;
    stroke-linecap:round; stroke-linejoin:round; }

.gt-body{ flex:1; min-width:0; padding-top:1px; }
.gt-title{
    font-size:14px; font-weight:700; line-height:1.3; color:var(--gt-text);
    letter-spacing:-.01em; margin:0 0 2px;
}
.gt-msg{
    font-size:12.75px; font-weight:450; line-height:1.5; color:var(--gt-text-soft);
    word-break:break-word; margin:0;
}
.gt-x{
    flex-shrink:0; width:26px; height:26px; margin:-2px -3px 0 0;
    border:none; background:transparent; border-radius:8px; cursor:pointer;
    color:var(--gt-text-soft); opacity:.6;
    display:flex; align-items:center; justify-content:center;
    transition:opacity .18s, background .18s;
}
.gt-x:hover{ opacity:1; background:color-mix(in srgb, var(--gt-text-soft) 12%, transparent); }
.gt-x svg{ width:15px; height:15px; stroke:currentColor; stroke-width:2.4; fill:none; stroke-linecap:round; }

.gt-prog{
    position:absolute; bottom:0; {{ $isAr ? 'right' : 'left' }}:0; height:2.5px;
    width:100%; transform-origin:{{ $isAr ? 'right' : 'left' }};
    background:var(--gt-accent); opacity:.55;
    animation:gtProg linear forwards;
}
@keyframes gtProg{ from{ transform:scaleX(1); } to{ transform:scaleX(0); } }

.gt:hover .gt-prog{ animation-play-state:paused; }

/* Dark fallback for surfaces WITHOUT the --bk-* token system (front/customer area) */
@media (prefers-color-scheme:dark){
    :root:not(.bk-theme-light) .gt{
        --gt-bg:#252C1B; --gt-border:#3A4330;
        --gt-text:#F0EEE3; --gt-text-soft:#BFC2AD;
        --gt-shadow:0 28px 64px rgba(0,0,0,.7);
    }
}
@media (prefers-reduced-motion:reduce){
    .gt{ animation:gtFade .2s ease both; }
    .gt.gt--out{ animation:gtFadeOut .18s ease forwards; }
    @keyframes gtFade{ from{opacity:0} to{opacity:1} }
    @keyframes gtFadeOut{ to{opacity:0} }
}
</style>

<div class="gt-stack" id="gt-stack" role="region" aria-label="{{ $isAr ? 'الإشعارات' : 'Notifications' }}"></div>

<script>
(function(){
    if (window.GlowToast) return;   // idempotent — first include wins

    var IS_AR = {{ $isAr ? 'true' : 'false' }};
    var DEFAULT_DURATION = 4400;
    var MAX_VISIBLE = 4;

    var LABELS = {
        success:{{ Illuminate\Support\Js::from($isAr ? 'تم بنجاح' : 'Success') }},
        error:  {{ Illuminate\Support\Js::from($isAr ? 'حدث خطأ' : 'Error') }},
        warning:{{ Illuminate\Support\Js::from($isAr ? 'تنبيه' : 'Notice') }},
        info:   {{ Illuminate\Support\Js::from($isAr ? 'معلومة' : 'Info') }},
        close:  {{ Illuminate\Support\Js::from($isAr ? 'إغلاق' : 'Close') }}
    };

    // Feather-style icon paths (inner SVG markup) — no emoji, matches app icon set
    var ICONS = {
        success:'<polyline points="20 6 9 17 4 12"/>',
        error:  '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        warning:'<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        info:   '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        brand:  '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
    };

    function esc(s){ var d=document.createElement('div'); d.textContent = (s==null?'':String(s)); return d.innerHTML; }

    // ── Sound engine ─────────────────────────────────────────────────────
    // Soft WebAudio chime. Throttled (min gap), mutable (persisted), and
    // unlocked on first user gesture so browsers don't block it.
    var Sound = (function(){
        var ctx = null, lastAt = 0, MIN_GAP = 1100, unlocked = false;
        // type → [freqs Hz], short & gentle
        var TONE = {
            success:[587.33, 880.00],
            info:   [523.25],
            warning:[493.88, 440.00],
            error:  [329.63, 246.94],
            brand:  [659.25, 987.77]
        };
        function muted(){
            try { return localStorage.getItem('glowSoundMuted') === '1'; } catch(e){ return false; }
        }
        function ensure(){
            if (ctx) return ctx;
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return null;
            try { ctx = new AC(); } catch(e){ ctx = null; }
            return ctx;
        }
        function unlock(){
            unlocked = true;
            var c = ensure();
            if (c && c.state === 'suspended') c.resume().catch(function(){});
            window.removeEventListener('pointerdown', unlock);
            window.removeEventListener('keydown', unlock);
        }
        window.addEventListener('pointerdown', unlock, {once:true});
        window.addEventListener('keydown', unlock, {once:true});

        function play(type){
            if (muted() || !unlocked) return;
            var now = Date.now();
            if (now - lastAt < MIN_GAP) return;   // don't spam on bursts
            var c = ensure();
            if (!c || c.state !== 'running') return;
            lastAt = now;
            var freqs = TONE[type] || TONE.info;
            var t0 = c.currentTime, step = 0.11;
            freqs.forEach(function(f, i){
                var osc = c.createOscillator(), g = c.createGain();
                osc.type = 'sine'; osc.frequency.value = f;
                var start = t0 + i*step*0.72;
                // gentle bell envelope, peak well below 1.0 so it stays soft
                g.gain.setValueAtTime(0.0001, start);
                g.gain.exponentialRampToValueAtTime(0.11, start + 0.015);
                g.gain.exponentialRampToValueAtTime(0.0001, start + step + 0.12);
                osc.connect(g); g.connect(c.destination);
                osc.start(start); osc.stop(start + step + 0.14);
            });
        }
        return { play:play, muted:muted };
    })();

    function stackEl(){ return document.getElementById('gt-stack'); }

    function dismiss(el){
        if (!el || el._gtRemoving) return;
        el._gtRemoving = true;
        clearTimeout(el._gtTimer);
        el.classList.add('gt--out');
        setTimeout(function(){ if (el.parentNode) el.parentNode.removeChild(el); }, 320);
    }

    function show(message, type, opts){
        opts = opts || {};
        type = (type && LABELS[type]) ? type : (type === 'brand' ? 'brand' : 'info');
        var stack = stackEl();
        if (!stack){ return; }

        var el = document.createElement('div');
        el.className = 'gt gt--' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

        var title = opts.title != null ? opts.title : LABELS[type];
        var duration = opts.duration != null ? opts.duration : DEFAULT_DURATION;

        el.innerHTML =
            '<span class="gt-ic"><svg viewBox="0 0 24 24">' + (ICONS[type]||ICONS.info) + '</svg></span>'
          + '<div class="gt-body">'
          +   (title ? '<p class="gt-title">' + esc(title) + '</p>' : '')
          +   '<p class="gt-msg">' + esc(message) + '</p>'
          + '</div>'
          + '<button class="gt-x" type="button" aria-label="' + esc(LABELS.close) + '">'
          +   '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
          + '</button>'
          + (duration > 0 ? '<span class="gt-prog" style="animation-duration:' + duration + 'ms"></span>' : '');

        el.querySelector('.gt-x').addEventListener('click', function(){ dismiss(el); });
        stack.appendChild(el);

        Sound.play(type);

        if (duration > 0){
            el._gtTimer = setTimeout(function(){ dismiss(el); }, duration);
            // pause countdown on hover
            el.addEventListener('mouseenter', function(){ clearTimeout(el._gtTimer); });
            el.addEventListener('mouseleave', function(){ el._gtTimer = setTimeout(function(){ dismiss(el); }, 1400); });
        }

        var all = stack.querySelectorAll('.gt');
        if (all.length > MAX_VISIBLE) dismiss(all[0]);
        return el;
    }

    window.GlowToast = {
        show: show,
        dismiss: dismiss,
        success:function(m,o){ return show(m,'success',o); },
        error:  function(m,o){ return show(m,'error',o); },
        warning:function(m,o){ return show(m,'warning',o); },
        info:   function(m,o){ return show(m,'info',o); },
        brand:  function(m,o){ return show(m,'brand',o); },
        mute:   function(v){ try{ localStorage.setItem('glowSoundMuted', v ? '1':'0'); }catch(e){} },
        isMuted:function(){ return Sound.muted(); }
    };

    // ── Back-compat shims so existing call-sites keep working ──────────────
    // Legacy bkToast auto-guessed a type from the message when none was given.
    function guessType(msg){
        if (!msg) return 'info';
        var m = String(msg).toLowerCase();
        if (/(delet|حذف|remov|إزالة|void|ملغ|فشل|error|خطأ)/.test(m)) return 'error';
        if (/(creat|إنشاء|add|إضافة|record|تسجيل|paid|صرف|saved|حفظ|success|نجاح|تم )/.test(m)) return 'success';
        if (/(updat|تحديث|edit|تعديل|chang|تغيير|adjust|reopen|toggle|تفعيل|تنبيه)/.test(m)) return 'warning';
        return 'info';
    }
    window.bkToast = function(message, type){ return show(message, type || guessType(message)); };
    window.bkDismissToast = dismiss;
    window.bkDismissCt = dismiss;

    // bkConfirm: SweetAlert2 when available, native confirm() fallback.
    if (!window.bkConfirm){
        window.bkConfirm = function(options){
            options = options || {};
            if (window.Swal){
                var isLight = document.documentElement.classList.contains('bk-theme-light')
                           || document.documentElement.dataset.bkTheme === 'light';
                return Swal.fire({
                    title: options.title || (IS_AR ? 'هل أنت متأكد؟' : 'Are you sure?'),
                    text: options.text || '',
                    icon: options.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonColor: options.confirmColor || '#B23A48',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: options.confirmText || (IS_AR ? 'نعم، متابعة' : 'Yes, continue'),
                    cancelButtonText: IS_AR ? 'إلغاء' : 'Cancel',
                    reverseButtons: IS_AR,
                    background: isLight ? '#fff' : '#252C1B',
                    color: isLight ? '#22251D' : '#F0EEE3'
                });
            }
            return Promise.resolve({ isConfirmed: window.confirm(options.text || options.title || (IS_AR ? 'هل أنت متأكد؟' : 'Are you sure?')) });
        };
    }
})();
</script>
@endonce
