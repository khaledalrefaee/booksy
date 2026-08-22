{{--
  GlowRez Toast Notifications — colored gradient card with glowing progress bar
--}}
@php $isAr = app()->getLocale() === 'ar'; @endphp

<style>
#bk-toast-crud-stack {
    position: fixed;
    top: 20px;
    {{ $isAr ? 'left' : 'right' }}: 20px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
    max-width: calc(100vw - 40px);
}

.bk-ct {
    --color: #60a5fa;
    width: 380px;
    max-width: 100%;
    border-radius: 10px;
    pointer-events: all;
    position: relative;
    overflow: hidden;
    animation: bkCtIn .45s cubic-bezier(.16,1,.3,1);
    background-color: #22242F;
    background-image: linear-gradient(to right, color-mix(in srgb, var(--color) 55%, transparent), #22242F 60%);
}

.bk-ct.removing { animation: bkCtOut .35s ease forwards; }

@keyframes bkCtIn {
    from { opacity:0; transform: translateY(-16px) scale(.96); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}
@keyframes bkCtOut {
    to { opacity:0; transform: translateX({{ $isAr ? '-' : '' }}60px) scale(.92); }
}

.bk-ct.t-success { --color: #22c55e; }
.bk-ct.t-warning { --color: #e9bd0c; }
.bk-ct.t-error   { --color: #f24d4c; }
.bk-ct.t-info    { --color: #3b82f6; }

/* ── Progress bar (glowing) ── */
.bk-ct-progress {
    position: absolute;
    bottom: 0; {{ $isAr ? 'right' : 'left' }}: 0;
    background-color: var(--color);
    width: 100%;
    height: 3px;
    box-shadow: 0 0 10px var(--color);
    animation: bkCtTimeout 4s linear 1 forwards;
}
@keyframes bkCtTimeout { to { width: 0; } }

/* ── Body ── */
.bk-ct-body {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
}

.bk-ct-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: var(--color);
    font-size: 17px;
    font-weight: 900;
}

.bk-ct-text { flex: 1; min-width: 0; }
.bk-ct-title {
    font-size: 15px;
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    margin-bottom: 2px;
}
.bk-ct-msg {
    font-size: 12.5px;
    color: rgba(255,255,255,.8);
    line-height: 1.4;
}

.bk-ct-close {
    align-self: flex-start;
    width: 26px; height: 26px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: rgba(255,255,255,.6);
    font-size: 18px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
    flex-shrink: 0;
}
.bk-ct-close:hover { color: #fff; background: rgba(255,255,255,.1); }
</style>

<div id="bk-toast-crud-stack"></div>

{{-- SweetAlert2 for confirmations --}}
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
(function() {
    var DURATION = 4000;
    var isAr = {{ $isAr ? 'true' : 'false' }};

    var typeCfg = {
        success: { title: 'Success', symbol: '✓' },
        warning: { title: 'Warning', symbol: '!' },
        error:   { title: 'Error',   symbol: '✕' },
        info:    { title: 'Info',    symbol: 'i' },
    };

    function guessType(msg) {
        if (!msg) return 'info';
        var m = msg.toLowerCase();
        if (m.indexOf('delet') !== -1 || m.indexOf('حذف') !== -1 || m.indexOf('remov') !== -1 || m.indexOf('إزالة') !== -1 || m.indexOf('void') !== -1 || m.indexOf('ملغ') !== -1) return 'error';
        if (m.indexOf('creat') !== -1 || m.indexOf('إنشاء') !== -1 || m.indexOf('add') !== -1 || m.indexOf('إضافة') !== -1 || m.indexOf('record') !== -1 || m.indexOf('تسجيل') !== -1 || m.indexOf('open') !== -1 || m.indexOf('فتح') !== -1 || m.indexOf('paid') !== -1 || m.indexOf('صرف') !== -1 || m.indexOf('saved') !== -1 || m.indexOf('حفظ') !== -1 || m.indexOf('success') !== -1) return 'success';
        if (m.indexOf('updat') !== -1 || m.indexOf('تحديث') !== -1 || m.indexOf('edit') !== -1 || m.indexOf('تعديل') !== -1 || m.indexOf('chang') !== -1 || m.indexOf('تغيير') !== -1 || m.indexOf('reconcil') !== -1 || m.indexOf('adjust') !== -1 || m.indexOf('reopen') !== -1 || m.indexOf('toggle') !== -1 || m.indexOf('تفعيل') !== -1) return 'warning';
        return 'info';
    }

    window.bkToast = function(message, type) {
        if (!type) type = guessType(message);
        var cfg = typeCfg[type] || typeCfg.info;
        var stack = document.getElementById('bk-toast-crud-stack');
        if (!stack) return;

        var el = document.createElement('div');
        el.className = 'bk-ct t-' + type;
        el.innerHTML =
            '<div class="bk-ct-body">'
            + '<div class="bk-ct-icon">' + cfg.symbol + '</div>'
            + '<div class="bk-ct-text">'
            + '<div class="bk-ct-title">' + cfg.title + '</div>'
            + '<div class="bk-ct-msg">' + message + '</div>'
            + '</div>'
            + '<button class="bk-ct-close" onclick="bkDismissCt(this.parentNode.parentNode)">&times;</button>'
            + '</div>'
            + '<div class="bk-ct-progress"></div>';

        stack.appendChild(el);

        var timer = setTimeout(function() { window.bkDismissCt(el); }, DURATION);
        el._timer = timer;

        var all = stack.querySelectorAll('.bk-ct');
        if (all.length > 4) window.bkDismissCt(all[0]);
    };

    window.bkDismissCt = function(el) {
        if (!el || el._rm) return;
        el._rm = true;
        clearTimeout(el._timer);
        el.classList.add('removing');
        setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 350);
    };

    window.bkConfirm = function(options) {
        return Swal.fire({
            title: options.title || (isAr ? 'هل أنت متأكد؟' : 'Are you sure?'),
            text: options.text || '',
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: options.confirmText || (isAr ? 'نعم، احذف' : 'Yes, delete'),
            cancelButtonText: isAr ? 'إلغاء' : 'Cancel',
            reverseButtons: isAr,
            background: document.documentElement.dataset.bkTheme === 'light' ? '#fff' : '#22242F',
            color: document.documentElement.dataset.bkTheme === 'light' ? '#333' : '#fff',
        });
    };

    @if(session('success'))
        bkToast(@json(session('success')));
    @endif
    @if(session('error'))
        bkToast(@json(session('error')), 'error');
    @endif
    @if(session('info'))
        bkToast(@json(session('info')), 'info');
    @endif
    @if(session('warning'))
        bkToast(@json(session('warning')), 'warning');
    @endif
})();
</script>
