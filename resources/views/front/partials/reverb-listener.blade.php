{{--
  reverb-listener.blade.php
  Include in owner/employee dashboard pages to receive real-time appointment notifications.
  Usage: @include('front.partials.reverb-listener', ['channelType' => 'branch', 'channelId' => $branch->id])
--}}
@php $isAr = app()->getLocale() === 'ar'; @endphp

<script src="{{ asset('vendor/echo/echo.iife.js') }}"></script>
<script src="{{ asset('vendor/echo/pusher.min.js') }}"></script>

{{-- Real-time booking alerts use the unified GlowRez toast engine --}}
@include('partials.glow-toast')

<style>
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

        // Unified GlowRez toast (brand chime) — one visual language everywhere
        const lines = [customer, [svc, emp].filter(Boolean).join(' · '), time].filter(Boolean).join('\n');
        if (window.GlowToast) {
            window.GlowToast.brand(lines, {
                title: IS_AR ? 'حجز جديد' : 'New booking',
                duration: 8000,
            });
        }

        // Browser notification
        if (Notification.permission === 'granted') {
            new Notification(IS_AR ? '📅 حجز جديد — غلوريز' : '📅 New Booking — GlowRez', {
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
