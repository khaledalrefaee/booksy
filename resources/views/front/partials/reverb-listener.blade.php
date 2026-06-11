{{--
  reverb-listener.blade.php
  Include in owner/employee dashboard pages to receive real-time appointment notifications.
  Usage: @include('front.partials.reverb-listener', ['channelType' => 'branch', 'channelId' => $branch->id])
--}}
@php $isAr = app()->getLocale() === 'ar'; @endphp

<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>

<style>
/* Toast notification */
.bk-rt-toast {
    position:fixed;top:80px;{{ $isAr ? 'left' : 'right' }}:20px;
    z-index:99999;
    background:var(--bg3,#1a1a1a);
    border:1.5px solid rgba(201,162,39,.35);
    border-radius:16px;
    padding:16px 20px;
    box-shadow:0 8px 40px rgba(0,0,0,.5);
    max-width:340px;
    display:flex;gap:12px;align-items:flex-start;
    animation:bkSlideIn .35s ease;
    cursor:pointer;
}
@keyframes bkSlideIn {
    from { opacity:0; transform:translateX({{ $isAr ? '-' : '' }}30px); }
    to   { opacity:1; transform:translateX(0); }
}
.bk-rt-icon {
    width:40px;height:40px;border-radius:50%;flex-shrink:0;
    background:rgba(201,162,39,.15);
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;
}
.bk-rt-body { flex:1; }
.bk-rt-title { font-size:.85rem;font-weight:700;color:var(--text,#111);margin-bottom:4px; }
.bk-rt-sub   { font-size:.75rem;color:var(--text-2,rgba(0,0,0,.55));line-height:1.5; }
.bk-rt-time  { font-size:.68rem;color:rgba(201,162,39,.8);margin-top:4px; }
.bk-rt-close { background:none;border:none;color:var(--text-2,rgba(0,0,0,.4));cursor:pointer;font-size:.75rem;padding:0; }
/* Notification bell badge */
.bk-notif-badge {
    display:inline-flex;align-items:center;justify-content:center;
    width:18px;height:18px;border-radius:50%;
    background:#ef4444;color:#fff;
    font-size:.6rem;font-weight:800;
    position:absolute;top:-4px;{{ $isAr ? 'left' : 'right' }}:-4px;
    animation:bkPulse 1.5s ease infinite;
}
@keyframes bkPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.2)} }
</style>

<div id="bk-toast-container"></div>

<script>
(function(){
    const IS_AR     = {{ $isAr ? 'true' : 'false' }};
    const CHAN_TYPE  = '{{ $channelType ?? "branch" }}';
    const CHAN_ID    = {{ $channelId ?? 0 }};
    const APP_KEY   = '{{ env("REVERB_APP_KEY","booksy-key-123") }}';
    const REVERB_HOST = '{{ env("REVERB_HOST","localhost") }}';
    const REVERB_PORT = {{ env("REVERB_PORT", 8080) }};

    if (!CHAN_ID) return;

    try {
        const echo = new LaravelEcho({
            broadcaster:  'reverb',
            key:          APP_KEY,
            wsHost:       REVERB_HOST,
            wsPort:       REVERB_PORT,
            wssPort:      REVERB_PORT,
            forceTLS:     false,
            enabledTransports: ['ws','wss'],
        });

        echo.private(`${CHAN_TYPE}.${CHAN_ID}`)
            .listen('.appointment.booked', (data) => {
                showToast(data);
                updateCalendarIfOpen(data);
                bumpNotifBadge();
            });

        console.log('[Reverb] Listening on', `${CHAN_TYPE}.${CHAN_ID}`);
    } catch(e) {
        console.warn('[Reverb] Not connected:', e.message);
    }

    function showToast(data) {
        const svc  = IS_AR ? data.service_name_ar : data.service_name_en;
        const emp  = IS_AR ? data.employee_name_ar : data.employee_name_en;
        const time = data.start_display;
        const customer = data.customer_name;

        const toast = document.createElement('div');
        toast.className = 'bk-rt-toast';
        toast.innerHTML = `
            <div class="bk-rt-icon">📅</div>
            <div class="bk-rt-body">
                <div class="bk-rt-title">${IS_AR ? '📌 حجز جديد!' : '📌 New Booking!'}</div>
                <div class="bk-rt-sub">
                    <strong>${customer}</strong><br>
                    ${svc} ${emp ? `· ${emp}` : ''}<br>
                    ${time}
                </div>
                <div class="bk-rt-time">● ${IS_AR ? 'الآن' : 'Just now'}</div>
            </div>
            <button class="bk-rt-close" onclick="this.closest('.bk-rt-toast').remove()">✕</button>
        `;
        document.getElementById('bk-toast-container').appendChild(toast);

        // Auto remove after 8s
        setTimeout(() => toast.remove(), 8000);

        // Browser notification
        if (Notification.permission === 'granted') {
            new Notification(IS_AR ? '📅 حجز جديد — بوكسي' : '📅 New Booking — Booksy', {
                body: `${customer} · ${svc} · ${time}`,
                icon: '/favicon.ico',
            });
        }
    }

    function updateCalendarIfOpen(data) {
        // If FullCalendar is loaded on the page, add the event without refresh
        if (window.bkCalendar && typeof window.bkCalendar.addEvent === 'function') {
            window.bkCalendar.addEvent({
                id:    data.id,
                title: (IS_AR ? data.service_name_ar : data.service_name_en) + ' · ' + data.customer_name,
                start: data.start_time,
                end:   data.end_time,
                backgroundColor: '#C9A227',
                borderColor:     '#C9A227',
                textColor:       '#0a0a0a',
                extendedProps:   data,
            });
        }
    }

    function bumpNotifBadge() {
        let badge = document.querySelector('.bk-notif-badge');
        if (!badge) {
            const bellWrap = document.querySelector('[data-notif-bell]');
            if (bellWrap) {
                bellWrap.style.position = 'relative';
                badge = document.createElement('span');
                badge.className = 'bk-notif-badge';
                badge.textContent = '0';
                bellWrap.appendChild(badge);
            }
        }
        if (badge) {
            const n = (parseInt(badge.textContent)||0) + 1;
            badge.textContent = n;
        }
    }

    // Request browser notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
})();
</script>
