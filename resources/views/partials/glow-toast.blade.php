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
    --gt-radius:16px;
    /* Vivid accents that pop on the dark card, colour-coded by action:
       delete=red, edit=orange, create=green, info/show=blue */
    --gt-accent-info:#5FA0E6;     /* blue  → show / info */
    --gt-accent-success:#54C06A;  /* green → create / add */
    --gt-accent-warning:#F0913E;  /* orange→ edit / update */
    --gt-accent-error:#E85462;    /* red   → delete / remove */
    --gt-accent-brand:#DAB85E;    /* gold  → brand / booking */
}
.gt-stack{
    position:fixed; top:20px; {{ $isAr ? 'left' : 'right' }}:20px;
    z-index:2147483000;
    display:flex; flex-direction:column; gap:12px;
    width:380px; max-width:calc(100vw - 40px);
    pointer-events:none;
}
.gt{
    /* Deep, self-contained dark card (theme-independent) — reference look */
    --gt-bg:#1F2617;
    --gt-text:#F4F2E8;
    --gt-text-soft:#C3C8B2;
    --gt-shadow:0 22px 55px rgba(8,12,4,.5);
    --gt-accent:var(--gt-accent-info);
    position:relative; overflow:hidden;
    display:flex; align-items:flex-start; gap:13px;
    padding:16px 17px 16px 18px;
    /* accent-tinted glow in the leading corner, fading into the dark base */
    background:
        radial-gradient(120% 140% at {{ $isAr ? '100%' : '0%' }} 0%,
            color-mix(in srgb, var(--gt-accent) 30%, transparent) 0%,
            transparent 58%),
        var(--gt-bg);
    border:1px solid color-mix(in srgb, var(--gt-accent) 30%, #303A24);
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
    flex-shrink:0; width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    color:#fff;
    background:var(--gt-accent);
    box-shadow:0 5px 14px color-mix(in srgb, var(--gt-accent) 40%, transparent);
}
.gt-ic svg{ width:19px; height:19px; stroke:#fff; stroke-width:2.6; fill:none;
    stroke-linecap:round; stroke-linejoin:round; }

.gt-body{ flex:1; min-width:0; padding-top:2px; }
.gt-title{
    font-size:14px; font-weight:700; line-height:1.3; color:var(--gt-accent);
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
        brand:  '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        // Action-specific icons (used by GlowToast.flash so a red delete-toast
        // shows a trash icon, an orange edit-toast a pencil — not an error ✗).
        trash:  '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
        edit:   '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        eye:    '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
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

    // Clear on-screen toasts when leaving, and again if the page is restored
    // from the back/forward cache — so a visible flash toast is never carried
    // back and shown again on Back navigation.
    function clearAll(){ var s = stackEl(); if (s) s.innerHTML = ''; }
    window.addEventListener('pagehide', clearAll);
    window.addEventListener('pageshow', function(e){ if (e.persisted) clearAll(); });

    // Dedupe guard: swallow an identical toast (same type+message) fired again
    // within a short window. Protects against any accidental double-fire.
    var _lastKey = '', _lastAt = 0, DEDUPE_MS = 900;

    function show(message, type, opts){
        opts = opts || {};
        type = (type && LABELS[type]) ? type : (type === 'brand' ? 'brand' : 'info');

        var key = type + '' + (message == null ? '' : String(message));
        var now = Date.now();
        if (key === _lastKey && (now - _lastAt) < DEDUPE_MS) return null;
        _lastKey = key; _lastAt = now;

        var stack = stackEl();
        if (!stack){ return; }

        var el = document.createElement('div');
        el.className = 'gt gt--' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

        var title = opts.title != null ? opts.title : LABELS[type];
        var duration = opts.duration != null ? opts.duration : DEFAULT_DURATION;

        var iconKey = opts.icon || type;
        el.innerHTML =
            '<span class="gt-ic"><svg viewBox="0 0 24 24">' + (ICONS[iconKey]||ICONS[type]||ICONS.info) + '</svg></span>'
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
        // flash(msg): auto colour + icon + title by the ACTION in the message.
        // Used for session('success') so a successful delete is red (trash),
        // an edit orange (pencil), a create green, everything else blue.
        flash:  function(m,o){
            var a = FLASH_MAP[guessAction(m)];
            return show(m, a.type, Object.assign({ title:a.title, icon:a.icon }, o||{}));
        },
        mute:   function(v){ try{ localStorage.setItem('glowSoundMuted', v ? '1':'0'); }catch(e){} },
        isMuted:function(){ return Sound.muted(); }
    };

    // ── Action-aware colouring ─────────────────────────────────────────────
    // The backend flashes session('success') for EVERY CRUD op, so every toast
    // used to be green. guessAction() reads the (Arabic/English) message and
    // classifies the operation so the toast is colour-coded:
    //   delete → red • edit → orange • create → green • else → blue
    // ORDER MATTERS: delete before edit before create ("تم حذف …" contains both
    // "حذف" and "تم", so delete must win).
    function guessAction(msg){
        var m = String(msg||'').toLowerCase();
        if (/(delet|حذف|احذف|remov|إزالة|ازالة|إلغاء|الغاء|ألغ|الغ|ملغ|void|أرشف|ارشف)/.test(m)) return 'delete';
        if (/(updat|حدّث|حدث|تحديث|edit|تعديل|عدّل|عدل|chang|تغيير|غيّر|adjust|reopen|إعادة فتح|toggle|تفعيل|تعطيل|activ|deactiv|approv|اعتماد|موافقة|رفض|reject)/.test(m)) return 'edit';
        if (/(عرض|show|view|عاين|preview|تفاصيل|details)/.test(m)) return 'info';
        if (/(creat|إنشاء|انشاء|أنشئ|أضيف|add|إضاف|اضاف|record|تسجيل|سجّل|سجل|paid|صرف|saved|حفظ|حُفظ|sent|أرسل|ارسل|success|نجاح|تم )/.test(m)) return 'create';
        return 'info';
    }
    // action → { colour type, icon, positive title }
    var FLASH_MAP = {
        'delete': { type:'error',   icon:'trash', title: IS_AR ? 'تم الحذف'  : 'Deleted' },
        'edit':   { type:'warning', icon:'edit',  title: IS_AR ? 'تم التعديل': 'Updated' },
        'create': { type:'success', icon:'success', title: IS_AR ? 'تمّت العملية' : 'Success' },
        'info':   { type:'info',    icon:'info',  title: IS_AR ? 'تم'        : 'Done' }
    };
    function guessType(msg){ return FLASH_MAP[guessAction(msg)].type; }  // back-compat (colour only)

    window.bkToast = function(message, type){ return show(message, type || guessType(message)); };
    window.bkDismissToast = dismiss;
    window.bkDismissCt = dismiss;

    // ── Global data-confirm → SweetAlert2 ─────────────────────────────────
    // Any <form data-confirm="msg"> shows a branded SweetAlert2 dialog instead
    // of the native confirm(), and only submits after the user approves.
    // Optional: data-confirm-title, data-confirm-icon, data-confirm-yes.
    if (!window._gtConfirmBound){
        window._gtConfirmBound = true;
        document.addEventListener('submit', function(e){
            var form = e.target;
            if (!form || form.nodeName !== 'FORM' || !form.hasAttribute('data-confirm')) return;
            if (form._gtOK) return;               // already approved → let it through
            e.preventDefault();
            var ask = (window.bkConfirm ? window.bkConfirm : function(o){
                return Promise.resolve({ isConfirmed: window.confirm(o.text || '') });
            });
            ask({
                title:       form.getAttribute('data-confirm-title') || (IS_AR ? 'تأكيد' : 'Confirm'),
                text:        form.getAttribute('data-confirm') || (IS_AR ? 'هل أنت متأكد؟' : 'Are you sure?'),
                icon:        form.getAttribute('data-confirm-icon') || 'warning',
                confirmText: form.getAttribute('data-confirm-yes') || undefined,
                confirmColor:form.getAttribute('data-confirm-color') || undefined
            }).then(function(r){
                if (r && r.isConfirmed){ form._gtOK = true; form.submit(); }  // form.submit() skips this listener
            });
        }, false);
    }

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

    // bkConfirmDelete: unified SweetAlert2 delete confirmation. Any delete
    // trigger calls bkConfirmDelete(actionUrl, name, message); on approval it
    // POSTs a DELETE form to actionUrl. Single source — replaces per-page
    // Bootstrap delete modals.
    window.bkConfirmDelete = function(actionUrl, name, message){
        var text = message || (IS_AR ? 'لا يمكن التراجع عن هذا الإجراء.' : 'This action cannot be undone.');
        return window.bkConfirm({
            title:       IS_AR ? 'تأكيد الحذف' : 'Delete?',
            text:        (name ? (name + ' — ') : '') + text,
            icon:        'warning',
            confirmText: IS_AR ? 'نعم، احذف' : 'Yes, delete',
            confirmColor:'#E85462'
        }).then(function(r){
            if (!r || !r.isConfirmed) return;
            var meta  = document.querySelector('meta[name="csrf-token"]');
            var input = document.querySelector('input[name="_token"]');
            var token = meta ? meta.getAttribute('content') : (input ? input.value : '');
            var f = document.createElement('form');
            f.method = 'POST'; f.action = actionUrl; f.style.display = 'none';
            f.innerHTML = '<input type="hidden" name="_token" value="' + token + '">'
                        + '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(f); f.submit();
        });
    };
})();
</script>

{{--
  Session + validation flash → fired HERE, once per request (inside @once).
  This is the SINGLE source of truth: crud-toasts/flash partials only include
  this engine and must NOT fire session toasts themselves, otherwise messages
  would appear twice (layout include + page include).
--}}
@if (session('success') || session('error') || session('warning') || session('info') || $errors->any())
<script>
(function () {
    // Fire-once guard: each render gets a unique NONCE. If the browser re-serves
    // this page from history/bfcache/HTTP-cache (e.g. user hits Back after the
    // action), the SAME nonce is already recorded and the flash is NOT re-fired.
    // A genuinely new action produces a fresh page with a new nonce → fires.
    var NONCE = @json((string) \Illuminate\Support\Str::uuid());
    try {
        var seen = JSON.parse(sessionStorage.getItem('gtFlashSeen') || '[]');
        if (seen.indexOf(NONCE) !== -1) return;           // already shown before
        seen.push(NONCE);
        if (seen.length > 30) seen = seen.slice(-30);      // keep the list small
        sessionStorage.setItem('gtFlashSeen', JSON.stringify(seen));
    } catch (e) { /* private mode / disabled storage → fall through and show once */ }

    function fire() {
        if (!window.GlowToast) return;
        @if (session('success')) window.GlowToast.flash(@json(session('success')));   @endif {{-- auto colour by action verb --}}
        @if (session('warning')) window.GlowToast.warning(@json(session('warning'))); @endif
        @if (session('info'))    window.GlowToast.info(@json(session('info')));       @endif
        @if (session('error'))   window.GlowToast.error(@json(session('error')));     @endif
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                window.GlowToast.error(@json($error));
            @endforeach
        @endif
    }
    if (window.GlowToast) fire();
    else document.addEventListener('DOMContentLoaded', fire);
})();
</script>
@endif
@endonce
