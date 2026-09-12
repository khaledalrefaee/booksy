{{--
  Session Guardian — resilient + secure session handling for long-running
  dashboard pages (built for the company Appointments board, which can sit open
  for a full ~9-hour shift on a weak connection).

  Design goals:
   • SECURE   — the session is kept alive only while the operator is actually
                working. Genuine inactivity (default 60 min with no mouse/key/
                touch) triggers a countdown warning and then a real logout, so an
                unattended screen never stays signed in.
   • RESILIENT— a heartbeat keeps the session (and its CSRF token) fresh while the
                user is active; if the network drops it retries quickly and shows
                an honest "offline" pill so the operator knows not to trust an
                action until the connection is back. On reconnect it refreshes the
                token immediately (covering laptop sleep / long outages).
   • SAFE     — the CSRF token is written back into the page's <meta>, every
                _token field, jQuery's default header and window.BKGuardian.csrf()
                so existing forms and AJAX always send a valid token.

  Params (all optional):
    $logoutUrl    — POST logout route; enables the idle auto-logout.
    $idleMinutes  — minutes of no activity before logout (default 60).
    $warnSeconds  — countdown shown before logging out (default 60).

  Requires: the `csrf.token` route and a <meta name="csrf-token"> in the head.
--}}
@php
    $kaLogout   = $logoutUrl   ?? null;
    $kaIdleMin  = $idleMinutes ?? 60;
    $kaWarnSec  = $warnSeconds ?? 60;
    $kaLifetime = (int) config('session.lifetime', 120);
@endphp

<style>
/* Connection pill — hidden until the connection is lost / recovering */
#bk-conn-pill {
    position: fixed; inset-block-end: 18px; inset-inline-start: 18px; z-index: 2147483000;
    display: none; align-items: center; gap: 9px; max-width: min(360px, 92vw);
    padding: 11px 15px; border-radius: 12px; font-size: .85rem; font-weight: 600; line-height: 1.35;
    color: #fff; background: #B23A48; box-shadow: 0 10px 30px rgba(0,0,0,.28);
    animation: bkConnIn .25s ease both;
}
#bk-conn-pill.is-online { background: #3F6B2E; }
#bk-conn-pill.is-recon  { background: #B45309; }
#bk-conn-pill .bk-conn-dot { width: 9px; height: 9px; border-radius: 50%; background: #fff; flex-shrink: 0; }
#bk-conn-pill.is-recon .bk-conn-dot { animation: bkPulse 1s infinite; }
@keyframes bkConnIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
@keyframes bkPulse  { 0%,100% { opacity: 1; } 50% { opacity: .3; } }

/* Idle warning modal */
#bk-idle-scrim {
    position: fixed; inset: 0; z-index: 2147483001; display: none;
    align-items: center; justify-content: center; padding: 20px;
    background: rgba(10,12,8,.6); backdrop-filter: blur(3px);
}
#bk-idle-scrim.is-open { display: flex; }
#bk-idle-card {
    width: min(420px, 100%); background: var(--bk-surface, #fff); color: var(--bk-text, #22251D);
    border: 1px solid var(--bk-border, #E7E1D3); border-radius: 18px; padding: 26px 24px;
    box-shadow: 0 24px 60px rgba(0,0,0,.3); text-align: center;
}
#bk-idle-card .bk-idle-ic {
    width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 26px;
    background: var(--bk-warning-bg, #FBF0DD); color: var(--bk-warning, #B45309);
}
#bk-idle-card h3 { margin: 0 0 8px; font-size: 1.15rem; font-weight: 700; }
#bk-idle-card p  { margin: 0 0 18px; font-size: .9rem; color: var(--bk-text-muted, #7B7C6D); line-height: 1.5; }
#bk-idle-card .bk-idle-count { font-variant-numeric: tabular-nums; font-weight: 800; color: var(--bk-warning, #B45309); }
#bk-idle-actions { display: flex; gap: 10px; }
#bk-idle-actions button { flex: 1; height: 46px; border-radius: 12px; font-size: .9rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; }
#bk-idle-stay   { background: var(--bk-accent, #4B5D34); color: var(--bk-accent-ink, #fff); }
#bk-idle-logout { background: var(--bk-surface, #fff); color: var(--bk-danger, #B23A48); border-color: var(--bk-border, #E7E1D3); }
</style>

<div id="bk-conn-pill" role="status" aria-live="polite">
    <span class="bk-conn-dot"></span>
    <span id="bk-conn-text"></span>
</div>

<div id="bk-idle-scrim" role="dialog" aria-modal="true" aria-labelledby="bk-idle-title">
    <div id="bk-idle-card">
        <div class="bk-idle-ic">🔒</div>
        <h3 id="bk-idle-title">{{ __('Are you still there?') }}</h3>
        <p>{{ __('For your security, you will be signed out in') }}
           <span class="bk-idle-count" id="bk-idle-count">{{ $kaWarnSec }}</span>
           {{ __('seconds due to inactivity.') }}</p>
        <div id="bk-idle-actions">
            <button type="button" id="bk-idle-stay">{{ __('Stay signed in') }}</button>
            @if($kaLogout)
                <button type="button" id="bk-idle-logout">{{ __('Sign out now') }}</button>
            @endif
        </div>
    </div>
</div>

@if($kaLogout)
    <form id="bk-ka-logout" method="POST" action="{{ $kaLogout }}" style="display:none">@csrf</form>
@endif

<script>
(function () {
    'use strict';

    var CFG = {
        tokenUrl:   "{{ route('csrf.token') }}",
        logout:     @json($kaLogout),
        idleMs:     {{ (int) $kaIdleMin }} * 60000,
        warnMs:     {{ (int) $kaWarnSec }} * 1000,
        // ~3 heartbeats per session lifetime, at least one a minute.
        beatMs:     Math.max(60000, Math.floor({{ $kaLifetime }} * 60000 / 3)),
        retryMs:    20000
    };

    var token       = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var lastActive  = Date.now();
    var online      = navigator.onLine !== false;
    var beatTimer   = null;
    var warnTimer   = null;
    var warnLeft    = 0;

    // Expose a single source of truth for the current token.
    window.BKGuardian = {
        csrf:     function () { return token; },
        isOnline: function () { return online; },
        refresh:  ping
    };

    function apply(t) {
        if (!t) return;
        token = t;
        var m = document.querySelector('meta[name="csrf-token"]');
        if (m) m.setAttribute('content', t);
        document.querySelectorAll('input[name="_token"]').forEach(function (i) { i.value = t; });
        if (window.jQuery) { window.jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': t } }); }
    }

    /* ── Connection pill ── */
    var pill = document.getElementById('bk-conn-pill');
    var pillText = document.getElementById('bk-conn-text');
    var pillHideTimer = null;
    function showPill(state, msg) {
        clearTimeout(pillHideTimer);
        pill.className = state ? ('is-' + state) : '';
        pillText.textContent = msg;
        pill.style.display = 'flex';
    }
    function hidePill(afterMs) {
        clearTimeout(pillHideTimer);
        pillHideTimer = setTimeout(function () { pill.style.display = 'none'; }, afterMs || 0);
    }

    function setOnline(isOn, opts) {
        var was = online;
        online = isOn;
        if (isOn) {
            if (!was) { showPill('online', "{{ __('Connection restored.') }}"); hidePill(2500); }
            else if (!opts || !opts.silent) { hidePill(0); }
        } else {
            showPill('recon', "{{ __('Connection lost — do not close this page. Reconnecting automatically…') }}");
        }
    }

    /* ── Heartbeat ── */
    function ping() {
        return fetch(CFG.tokenUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (d && d.token) { apply(d.token); setOnline(true, { silent: true }); return true; }
                setOnline(false); return false;
            })
            .catch(function () { setOnline(false); return false; });
    }

    function scheduleBeat(delay) {
        clearTimeout(beatTimer);
        beatTimer = setTimeout(beat, delay);
    }
    function beat() {
        // Idle → stop keeping the session alive (security); the idle watcher handles logout.
        if (Date.now() - lastActive >= CFG.idleMs) { scheduleBeat(CFG.retryMs); return; }
        ping().then(function (ok) { scheduleBeat(ok ? CFG.beatMs : CFG.retryMs); });
    }

    /* ── Activity tracking ── */
    var actThrottle = false;
    function onActivity() {
        lastActive = Date.now();
        if (actThrottle) return;
        actThrottle = true;
        setTimeout(function () { actThrottle = false; }, 1000);
        if (warnTimer) cancelWarning();     // any interaction dismisses the idle warning
    }
    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function (ev) {
        document.addEventListener(ev, onActivity, { passive: true });
    });

    /* ── Idle security: warn then log out ── */
    var scrim   = document.getElementById('bk-idle-scrim');
    var countEl = document.getElementById('bk-idle-count');

    function startWarning() {
        warnLeft = Math.round(CFG.warnMs / 1000);
        countEl.textContent = warnLeft;
        scrim.classList.add('is-open');
        warnTimer = setInterval(function () {
            warnLeft -= 1;
            countEl.textContent = warnLeft;
            if (warnLeft <= 0) { doLogout(); }
        }, 1000);
    }
    function cancelWarning() {
        clearInterval(warnTimer); warnTimer = null;
        scrim.classList.remove('is-open');
        ping();                              // came back → keep the session alive
    }
    function doLogout() {
        clearInterval(warnTimer); warnTimer = null;
        var form = document.getElementById('bk-ka-logout');
        if (form) {
            var f = form.querySelector('input[name="_token"]'); if (f) f.value = token;
            form.submit();
        } else {
            window.location.reload();        // no logout route → let the guard redirect
        }
    }
    // Watch for genuine inactivity.
    setInterval(function () {
        if (!warnTimer && CFG.logout && Date.now() - lastActive >= CFG.idleMs) { startWarning(); }
    }, 5000);

    var stayBtn = document.getElementById('bk-idle-stay');
    if (stayBtn) stayBtn.addEventListener('click', function () { lastActive = Date.now(); cancelWarning(); });
    var outBtn = document.getElementById('bk-idle-logout');
    if (outBtn) outBtn.addEventListener('click', doLogout);

    /* ── Network events → react immediately ── */
    window.addEventListener('offline', function () { setOnline(false); });
    window.addEventListener('online',  function () { ping(); });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) ping(); });
    window.addEventListener('focus', ping);

    /* ── Go ── */
    scheduleBeat(CFG.beatMs);
})();
</script>
