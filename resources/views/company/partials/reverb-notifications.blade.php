{{--
  reverb-notifications.blade.php
  Include once in company/dashboard.blade.php (before </body>).
  Listens on all branches of the logged-in company via Reverb WebSocket.
  Shows Facebook-style toast notifications + plays a soft ding sound.
--}}
@php
    $isAr        = app()->getLocale() === 'ar';
    $authCompany = Auth::guard('company')->user();
    $branchIds   = $authCompany?->branches()->pluck('id')->toArray() ?? [];
@endphp

@if(!empty($branchIds))
{{-- Load Echo + Pusher --}}
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>

<style>
/* ════════════════════════════════════════
   NOTIFICATION BELL (in navbar)
════════════════════════════════════════ */
#bk-notif-bell-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
}
#bk-notif-bell-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: transparent;
    border: 1.5px solid rgba(201,162,39,.2);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    position: relative;
    transition: all .2s;
    color: rgba(255,255,255,.6);
}
#bk-notif-bell-btn:hover {
    background: rgba(201,162,39,.1);
    border-color: rgba(201,162,39,.4);
    color: #C9A227;
}
#bk-notif-bell-btn svg { transition: transform .3s; }
#bk-notif-bell-btn.ringing svg { animation: bk-ring .5s ease infinite; }
@keyframes bk-ring {
    0%,100% { transform: rotate(0deg); }
    20%      { transform: rotate(-20deg); }
    60%      { transform: rotate(20deg); }
}
#bk-bell-badge {
    position: absolute;
    top: -3px; {{ $isAr ? 'left' : 'right' }}: -3px;
    background: #ef4444;
    color: #fff;
    font-size: .52rem;
    font-weight: 900;
    width: 17px; height: 17px;
    border-radius: 50%;
    display: none;
    align-items: center; justify-content: center;
    border: 2px solid var(--bk-navbar-bg, #151521);
    animation: bk-pulse-badge 1.8s ease infinite;
    font-family: 'Poppins', sans-serif;
}
@keyframes bk-pulse-badge {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.2); }
}

/* ════════════════════════════════════════
   NOTIFICATION PANEL (dropdown)
════════════════════════════════════════ */
#bk-notif-panel {
    position: absolute;
    top: calc(100% + 10px);
    {{ $isAr ? 'left' : 'right' }}: -8px;
    width: 340px;
    background: #1a1a2e;
    border: 1px solid rgba(201,162,39,.2);
    border-radius: 16px;
    box-shadow: 0 16px 50px rgba(0,0,0,.6);
    z-index: 10600;
    display: none;
    overflow: hidden;
    font-family: 'Poppins', sans-serif;
    max-height: 500px;
}
#bk-notif-panel.open { display: block; animation: bk-panel-in .22s ease; }
@keyframes bk-panel-in {
    from { opacity:0; transform: translateY(-8px) scale(.97); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}
.bk-notif-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px 10px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.bk-notif-panel-head h5 {
    font-size: .9rem; font-weight: 800; color: #fff; margin: 0;
}
.bk-notif-panel-head .mark-all {
    font-size: .7rem; color: #C9A227; cursor: pointer;
    background: none; border: none; font-family: inherit; font-weight: 600;
}
.bk-notif-list { overflow-y: auto; max-height: 400px; }
.bk-notif-list::-webkit-scrollbar { width: 3px; }
.bk-notif-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }
.bk-notif-empty {
    padding: 36px 20px;
    text-align: center;
    color: rgba(255,255,255,.3);
    font-size: .82rem;
}
.bk-notif-empty i { font-size: 2.4rem; color: rgba(201,162,39,.1); display: block; margin-bottom: 10px; }
.bk-notif-item {
    display: flex;
    gap: 12px;
    padding: 13px 18px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    cursor: pointer;
    transition: background .18s;
    position: relative;
}
.bk-notif-item:hover { background: rgba(201,162,39,.06); }
.bk-notif-item.unread { background: rgba(201,162,39,.04); }
.bk-notif-item.unread::before {
    content: '';
    position: absolute;
    {{ $isAr ? 'right' : 'left' }}: 0;
    top: 0; bottom: 0;
    width: 3px;
    background: #C9A227;
    border-radius: 0 3px 3px 0;
}
.bk-notif-av {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #C9A227, #e8c84a);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem; color: #0a0a0a;
    position: relative;
}
.bk-notif-av .bk-notif-av-badge {
    position: absolute;
    bottom: -2px; {{ $isAr ? 'left' : 'right' }}: -2px;
    width: 16px; height: 16px;
    background: #10b981;
    border-radius: 50%;
    border: 2px solid #1a1a2e;
    display: flex; align-items: center; justify-content: center;
    font-size: .5rem; color: #fff;
}
.bk-notif-content { flex: 1; min-width: 0; }
.bk-notif-title {
    font-size: .8rem; font-weight: 800; color: #fff; margin-bottom: 3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.bk-notif-body {
    font-size: .73rem; color: rgba(255,255,255,.55); line-height: 1.45;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.bk-notif-time { font-size: .63rem; color: #C9A227; margin-top: 4px; font-weight: 600; }

/* ════════════════════════════════════════
   TOAST NOTIFICATIONS (Facebook-style)
════════════════════════════════════════ */
#bk-toast-stack {
    position: fixed;
    top: 74px;
    {{ $isAr ? 'left' : 'right' }}: 20px;
    z-index: 10700;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}
.bk-fb-toast {
    width: 360px;
    background: #1e1e30;
    border: 1px solid rgba(201,162,39,.25);
    border-radius: 16px;
    box-shadow: 0 12px 50px rgba(0,0,0,.65), 0 0 0 1px rgba(201,162,39,.08);
    overflow: hidden;
    pointer-events: all;
    animation: bk-toast-in .35s cubic-bezier(.22,1,.36,1);
    position: relative;
    font-family: 'Poppins', sans-serif;
}
@keyframes bk-toast-in {
    from { opacity:0; transform: translateX({{ $isAr ? '-' : '' }}40px) scale(.95); }
    to   { opacity:1; transform: translateX(0) scale(1); }
}
.bk-fb-toast.removing {
    animation: bk-toast-out .3s ease forwards;
}
@keyframes bk-toast-out {
    to { opacity:0; transform: translateX({{ $isAr ? '-' : '' }}40px) scale(.95); height:0; margin:0; padding:0; }
}
.bk-toast-progress {
    height: 3px;
    background: linear-gradient(90deg, #C9A227, #e8c84a);
    width: 100%;
    transform-origin: {{ $isAr ? 'right' : 'left' }};
    animation: bk-progress-drain linear forwards;
}
.bk-toast-body {
    display: flex;
    gap: 13px;
    align-items: flex-start;
    padding: 14px 16px 14px;
}
.bk-toast-icon {
    width: 48px; height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #C9A227, #e8c84a);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #0a0a0a;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(201,162,39,.4);
    position: relative;
}
.bk-toast-icon::after {
    content: '✓';
    position: absolute;
    bottom: -2px; right: -2px;
    width: 18px; height: 18px;
    background: #10b981;
    border-radius: 50%;
    border: 2px solid #1e1e30;
    font-size: .55rem;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; color: #fff;
    display: flex; align-items: center; justify-content: center;
}
.bk-toast-content { flex: 1; min-width: 0; }
.bk-toast-heading {
    display: flex; align-items: center; gap: 6px;
    font-size: .75rem; font-weight: 900; color: #C9A227; margin-bottom: 4px;
}
.bk-toast-heading .bk-dot-live {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 6px #10b981;
    flex-shrink: 0;
    animation: bk-dot-pulse 1.5s ease infinite;
}
@keyframes bk-dot-pulse {
    0%,100% { opacity: 1; }
    50%      { opacity: .4; }
}
.bk-toast-customer { font-size: .88rem; font-weight: 800; color: #fff; margin-bottom: 3px; }
.bk-toast-detail   { font-size: .76rem; color: rgba(255,255,255,.55); line-height: 1.5; }
.bk-toast-time     { font-size: .68rem; color: #C9A227; margin-top: 5px; font-weight: 700; }
.bk-toast-close {
    position: absolute;
    top: 10px; {{ $isAr ? 'left' : 'right' }}: 12px;
    width: 24px; height: 24px;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    border: none; cursor: pointer;
    color: rgba(255,255,255,.5);
    font-size: .7rem;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.bk-toast-close:hover { background: rgba(255,255,255,.2); color: #fff; }

@keyframes bk-progress-drain {
    from { transform: scaleX(1); }
    to   { transform: scaleX(0); }
}
</style>

{{-- Notification bell container (injected into navbar via JS) --}}
<div id="bk-notif-bell-wrap" style="display:none;">
    <button id="bk-notif-bell-btn" onclick="bkToggleNotifPanel()" title="{{ $isAr ? 'الإشعارات' : 'Notifications' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span id="bk-bell-badge">0</span>
    </button>

    {{-- Notification dropdown panel --}}
    <div id="bk-notif-panel">
        <div class="bk-notif-panel-head">
            <h5>{{ $isAr ? '🔔 الإشعارات' : '🔔 Notifications' }}</h5>
            <button class="mark-all" onclick="bkMarkAllRead()">
                {{ $isAr ? 'تحديد الكل كمقروء' : 'Mark all read' }}
            </button>
        </div>
        <div class="bk-notif-list" id="bk-notif-list">
            <div class="bk-notif-empty" id="bk-notif-empty">
                <i class="fas fa-bell-slash"></i>
                {{ $isAr ? 'لا توجد إشعارات جديدة' : 'No notifications yet' }}
            </div>
        </div>
    </div>
</div>

{{-- Toast stack container --}}
<div id="bk-toast-stack"></div>

<script>
(function () {
    'use strict';
    var IS_AR    = {{ $isAr ? 'true' : 'false' }};
    var BRANCH_IDS = @json($branchIds);

    var unreadCount  = 0;
    var notifHistory = [];
    var TOAST_DURATION = 7000; // ms

    /* ────────────────────────────────────
       Inject bell into navbar
    ──────────────────────────────────── */
    window.addEventListener('DOMContentLoaded', function () {
        // Bell is now in navbar.blade.php — skip injection
        var wrap = document.getElementById('bk-notif-bell-wrap');
        if (wrap) wrap.remove();

        // Close panel on outside click
        document.addEventListener('click', function (e) {
            var panel = document.getElementById('bk-notif-panel');
            var bell  = document.getElementById('bk-notif-bell-wrap');
            if (panel && bell && !bell.contains(e.target)) {
                panel.classList.remove('open');
            }
        });
    });

    /* ────────────────────────────────────
       Bell + panel toggle
    ──────────────────────────────────── */
    window.bkToggleNotifPanel = function () {
        var panel = document.getElementById('bk-notif-panel');
        if (!panel) return;
        panel.classList.toggle('open');
    };

    window.bkMarkAllRead = function () {
        unreadCount = 0;
        _updateBadge();
        document.querySelectorAll('.bk-notif-item.unread').forEach(function (el) {
            el.classList.remove('unread');
        });
    };

    function _updateBadge() {
        var badge = document.getElementById('bk-bell-badge');
        var bell  = document.getElementById('bk-notif-bell-btn');
        if (!badge || !bell) return;

        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'flex';
            bell.classList.add('ringing');
            setTimeout(function () { bell.classList.remove('ringing'); }, 2000);
        } else {
            badge.style.display = 'none';
            bell.classList.remove('ringing');
        }
    }

    /* ────────────────────────────────────
       Sound: Web Audio API ding
    ──────────────────────────────────── */
    var _audioCtx = null;
    function _playDing() {
        try {
            if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)();

            // Main tone: G4 (392 Hz) → fades out in 600ms
            var osc1 = _audioCtx.createOscillator();
            var gain1 = _audioCtx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(784, _audioCtx.currentTime);       // G5
            osc1.frequency.exponentialRampToValueAtTime(392, _audioCtx.currentTime + 0.15); // → G4
            gain1.gain.setValueAtTime(0.35, _audioCtx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, _audioCtx.currentTime + 0.7);
            osc1.connect(gain1);
            gain1.connect(_audioCtx.destination);
            osc1.start(_audioCtx.currentTime);
            osc1.stop(_audioCtx.currentTime + 0.7);

            // Overtone: adds richness
            var osc2 = _audioCtx.createOscillator();
            var gain2 = _audioCtx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1568, _audioCtx.currentTime); // G6
            gain2.gain.setValueAtTime(0.12, _audioCtx.currentTime);
            gain2.gain.exponentialRampToValueAtTime(0.001, _audioCtx.currentTime + 0.4);
            osc2.connect(gain2);
            gain2.connect(_audioCtx.destination);
            osc2.start(_audioCtx.currentTime);
            osc2.stop(_audioCtx.currentTime + 0.4);

        } catch (e) { /* browser blocks audio without user interaction — silent fallback */ }
    }

    /* ────────────────────────────────────
       Add notification to panel list
    ──────────────────────────────────── */
    function _addToPanel(data) {
        var list  = document.getElementById('bk-notif-list');
        var empty = document.getElementById('bk-notif-empty');
        if (!list) return;

        var svc     = IS_AR ? (data.service_name_ar || data.service_name_en) : (data.service_name_en || data.service_name_ar);
        var empName = IS_AR ? (data.employee_name_ar || data.employee_name_en) : (data.employee_name_en || data.employee_name_ar);
        var branch  = IS_AR ? (data.branch_name_ar || data.branch_name_en) : (data.branch_name_en || data.branch_name_ar);
        var dt      = data.start_display || data.start_time || '';

        var item = document.createElement('div');
        item.className = 'bk-notif-item unread';
        item.innerHTML =
            '<div class="bk-notif-av">'
            + '📅'
            + '<div class="bk-notif-av-badge">✓</div>'
            + '</div>'
            + '<div class="bk-notif-content">'
            + '<div class="bk-notif-title">' + _esc(data.customer_name || '—') + '</div>'
            + '<div class="bk-notif-body">'
            + _esc(svc || '') + (empName ? ' · ' + _esc(empName) : '')
            + (branch ? '<br>' + _esc(branch) : '')
            + '</div>'
            + '<div class="bk-notif-time">⏰ ' + _esc(dt) + '</div>'
            + '</div>';

        item.addEventListener('click', function () {
            item.classList.remove('unread');
        });

        if (empty) empty.style.display = 'none';
        list.prepend(item);

        // Keep max 30 in panel
        var items = list.querySelectorAll('.bk-notif-item');
        if (items.length > 30) items[items.length - 1].remove();
    }

    /* ────────────────────────────────────
       Show Facebook-style toast
    ──────────────────────────────────── */
    function _showToast(data) {
        var svc     = IS_AR ? (data.service_name_ar || data.service_name_en) : (data.service_name_en || data.service_name_ar);
        var empName = IS_AR ? (data.employee_name_ar || data.employee_name_en) : (data.employee_name_en || data.employee_name_ar);
        var branch  = IS_AR ? (data.branch_name_ar || data.branch_name_en) : (data.branch_name_en || data.branch_name_ar);
        var dt      = data.start_display || '';

        var stack = document.getElementById('bk-toast-stack');
        if (!stack) return;

        var toast = document.createElement('div');
        toast.className = 'bk-fb-toast';
        toast.innerHTML =
            '<div class="bk-toast-progress" style="animation-duration:' + TOAST_DURATION + 'ms;"></div>'
            + '<div class="bk-toast-body">'
            + '<div class="bk-toast-icon">📅</div>'
            + '<div class="bk-toast-content">'
            + '<div class="bk-toast-heading">'
            + '<span class="bk-dot-live"></span>'
            + (IS_AR ? 'حجز جديد!' : 'New Booking!')
            + '</div>'
            + '<div class="bk-toast-customer">' + _esc(data.customer_name || '—') + '</div>'
            + '<div class="bk-toast-detail">'
            + _esc(svc || '') + (empName ? ' · ' + _esc(empName) : '')
            + (branch ? '<br>' + _esc(branch) : '')
            + '</div>'
            + '<div class="bk-toast-time">⏰ ' + _esc(dt) + '</div>'
            + '</div>'
            + '</div>'
            + '<button class="bk-toast-close" onclick="bkDismissToast(this.closest(\'.bk-fb-toast\'))">✕</button>';

        stack.appendChild(toast);

        // Auto-dismiss
        var timer = setTimeout(function () {
            window.bkDismissToast(toast);
        }, TOAST_DURATION);
        toast._timer = timer;

        // Limit to 4 toasts
        var toasts = stack.querySelectorAll('.bk-fb-toast');
        if (toasts.length > 4) {
            window.bkDismissToast(toasts[0]);
        }
    }

    window.bkDismissToast = function (toast) {
        if (!toast || toast._removing) return;
        toast._removing = true;
        clearTimeout(toast._timer);
        toast.classList.add('removing');
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
    };

    /* ────────────────────────────────────
       Update FullCalendar (if open)
    ──────────────────────────────────── */
    function _updateCalendar(data) {
        // Method 1: refetch (always works)
        if (window.bkCalendar && typeof window.bkCalendar.refetchEvents === 'function') {
            window.bkCalendar.refetchEvents();
            return;
        }
        // Method 2: direct addEvent (instant, no refetch needed)
        if (window.bkCalendar && typeof window.bkCalendar.addEvent === 'function') {
            var svc = IS_AR ? (data.service_name_ar || data.service_name_en) : (data.service_name_en || data.service_name_ar);
            var emp = IS_AR ? (data.employee_name_ar || data.employee_name_en) : (data.employee_name_en || data.employee_name_ar);
            window.bkCalendar.addEvent({
                id:              'rt-' + data.id,
                title:           (data.customer_name || '') + (svc ? ' · ' + svc : ''),
                start:           data.start_time,
                end:             data.end_time,
                backgroundColor: '#f59e0b',
                borderColor:     '#f59e0b',
                textColor:       '#fff',
                extendedProps: {
                    status:   'pending',
                    service:  svc,
                    employee: emp,
                    branch:   IS_AR ? (data.branch_name_ar || data.branch_name_en) : (data.branch_name_en || data.branch_name_ar),
                    price:    data.price,
                },
            });
        }
    }

    /* ────────────────────────────────────
       Browser push notification
    ──────────────────────────────────── */
    function _browserNotif(data) {
        if (!('Notification' in window)) return;
        var svc = IS_AR ? (data.service_name_ar || data.service_name_en) : (data.service_name_en || data.service_name_ar);
        var body = (data.customer_name || '') + ' · ' + (svc || '') + '\n' + (data.start_display || '');

        if (Notification.permission === 'granted') {
            new Notification(IS_AR ? '📅 حجز جديد' : '📅 New Booking', {
                body: body,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: 'bk-booking-' + data.id,
                renotify: true,
            });
        } else if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    /* ────────────────────────────────────
       Main handler — called for each booking event
    ──────────────────────────────────── */
    function _onBooking(data) {
        _playDing();
        _addToPanel(data);
        _showToast(data);
        _updateCalendar(data);
        _browserNotif(data);

        unreadCount++;
        _updateBadge();
    }

    /* ────────────────────────────────────
       Connect to Reverb via Echo
    ──────────────────────────────────── */
    try {
        var echo = new LaravelEcho({
            broadcaster:       'reverb',
            key:               '{{ config('broadcasting.connections.reverb.key', env('REVERB_APP_KEY', 'booksy-key-123')) }}',
            wsHost:            '{{ env('REVERB_HOST', 'localhost') }}',
            wsPort:            {{ env('REVERB_PORT', 8080) }},
            wssPort:           {{ env('REVERB_PORT', 8080) }},
            forceTLS:          false,
            enabledTransports: ['ws', 'wss'],
            authEndpoint:      '/broadcasting/auth',
        });

        BRANCH_IDS.forEach(function (branchId) {
            echo.private('branch.' + branchId)
                .listen('.appointment.booked', function (data) {
                    console.log('[Reverb] appointment.booked on branch.' + branchId, data);
                    _onBooking(data);
                });
        });

        console.log('[Reverb] Listening on branches:', BRANCH_IDS);
    } catch (e) {
        console.warn('[Reverb] Connection failed:', e.message);
    }

    /* ────────────────────────────────────
       Helpers
    ──────────────────────────────────── */
    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

})();
</script>
@endif
