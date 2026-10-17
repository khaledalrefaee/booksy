/**
 * Follow-up flow: what happens AFTER an appointment exists.
 *
 *   1. Next-step bar   — one obvious action for the appointment's current status
 *                        (accept → arrived → start → finish → checkout), plus
 *                        call / WhatsApp. Rendered inside the detail drawer.
 *   2. Requests inbox  — online bookings (GlowRez / tracking links) that are still
 *                        Pending, answerable in one tap from anywhere on the page.
 *   3. Decline sheet   — cancellation by the salon needs a reason (state machine).
 *   4. Booked card     — right after saving, offer "message the customer / view".
 *
 * Separate IIFE like panels.js. Publishes: window.bkFollowUp.
 */
(function () {
'use strict';

var FU       = BK.fu || {};
var IS_RTL   = BK.isRtl;
var CSRF     = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
var ST       = BK.statusDefs || {};
var MOVES    = BK.allowedMoves || {};
var REQ_URL  = BK.routes.appointmentsRequests;
var STATUS_URL = BK.routes.appointmentsUpdateStatus;

function esc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function toast(msg, type) { if (window.bkToast) window.bkToast(msg, type || 'success'); }

/* ── tiny icon set (feather paths) ── */
var IC = {
    check:   '<polyline points="20 6 9 17 4 12"/>',
    x:       '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
    phone:   '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
    chat:    '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
    login:   '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
    play:    '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>',
    flag:    '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
    pause:   '<circle cx="12" cy="12" r="10"/><line x1="10" y1="15" x2="10" y2="9"/><line x1="14" y1="15" x2="14" y2="9"/>',
    card:    '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
    userx:   '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/>',
    clock:   '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    eye:     '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
    bell:    '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
};
function ic(name, size) {
    size = size || 16;
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (IC[name] || '') + '</svg>';
}

/* ── phone helpers ── */
function digits(p) {
    var d = String(p || '').replace(/[٠-٩]/g, function (c) { return c.charCodeAt(0) - 1632; }).replace(/\D/g, '');
    if (d.indexOf('00') === 0) d = d.slice(2);
    return d;
}
function telHref(p)  { var d = digits(p); return d ? 'tel:+' + d : ''; }
/* a number that starts with 0 has no country code — wa.me would send it nowhere */
function waHref(p, text) {
    var d = digits(p);
    if (!d || d.charAt(0) === '0') return '';
    return 'https://wa.me/' + d + (text ? '?text=' + encodeURIComponent(text) : '');
}

/* ═════════════════════════════════════════════════════════
   1. NEXT-STEP BAR
═════════════════════════════════════════════════════════ */
function plan(s) {
    var st    = s.status;
    var moves = MOVES[st] || [];
    var can   = function (x) { return moves.indexOf(x) > -1; };
    var out   = { lead: '', sub: '', primary: null, secondary: [] };

    function move(to, label, icon)  { return { act: 'fu-move', to: to, label: label, icon: icon }; }
    function decl(label)            { return { act: 'fu-decline', label: label, danger: true }; }

    switch (st) {
    case 'pending':
        out.lead = FU.leadPending; out.sub = FU.leadPendingSub;
        if (can('confirmed')) out.primary = move('confirmed', FU.accept, 'check');
        if (can('cancelled_by_salon')) out.secondary.push(decl(FU.decline));
        break;
    case 'confirmed':
    case 'upcoming':
        out.lead = FU.leadConfirmed; out.sub = FU.leadConfirmedSub;
        if (can('arrived')) out.primary = move('arrived', FU.arrived, 'login');
        if (can('no_show')) out.secondary.push({ act: 'fu-move', to: 'no_show', label: FU.markNoShow, icon: 'userx' });
        if (can('cancelled_by_salon')) out.secondary.push(decl(FU.cancelAppt));
        break;
    case 'arrived':
        out.lead = FU.leadArrived; out.sub = FU.leadArrivedSub;
        if (can('in_progress')) out.primary = move('in_progress', FU.start, 'play');
        if (can('cancelled_by_salon')) out.secondary.push(decl(FU.cancelAppt));
        break;
    case 'in_progress':
        out.lead = FU.leadProgress; out.sub = FU.leadProgressSub;
        if (can('awaiting_payment')) out.primary = move('awaiting_payment', FU.finish, 'flag');
        if (can('paused')) out.secondary.push({ act: 'fu-move', to: 'paused', label: FU.pause, icon: 'pause' });
        break;
    case 'paused':
        out.lead = FU.leadPaused;
        if (can('in_progress')) out.primary = move('in_progress', FU.resume, 'play');
        break;
    case 'awaiting_payment':
        out.lead = FU.leadAwaiting; out.sub = FU.leadAwaitingSub;
        out.primary = { act: 'fu-checkout', label: FU.collect, icon: 'card' };
        break;
    case 'no_show':
        out.lead = FU.leadNoShow; out.sub = FU.leadNoShowSub;
        if (can('arrived')) out.primary = move('arrived', FU.lateArrival, 'login');
        break;
    case 'completed':
        out.lead = FU.leadDone; break;
    case 'cancelled_by_customer':
    case 'cancelled_by_salon':
        out.lead = FU.leadCancelled; break;
    default:
        return null;
    }
    return out;
}

function btn(a, cls) {
    return '<button type="button" class="' + cls + (a.danger ? ' is-danger' : '') + '" data-ap="' + a.act + '"'
        + (a.to ? ' data-to="' + esc(a.to) + '"' : '') + '>'
        + (a.icon ? ic(a.icon, cls === 'fu-primary' ? 18 : 15) : '') + '<span>' + esc(a.label) + '</span></button>';
}

/* HTML for the drawer. s = the appointment details payload (apData). */
function barHtml(s) {
    var p = plan(s);
    if (!p) return '';
    var col  = (ST[s.status] && ST[s.status].color) || '#64748b';
    var live = !(ST[s.status] && ST[s.status].terminal);
    var phone = s.customer && s.customer.phone;

    var meta = '';
    if (s.status === 'pending') {
        var bits = [];
        if (s.source && s.source.label) bits.push(FU.viaSource.replace(':s', s.source.label));
        if (s.createdMin != null)       bits.push(agoText(s.createdMin));
        if (bits.length) meta = '<span class="fu-meta">' + esc(bits.join(' · ')) + '</span>';
    }

    var contact = '';
    if (live && phone) {
        var wa = waHref(phone);
        contact = '<div class="fu-contact">'
            + '<a class="fu-chip" href="' + esc(telHref(phone)) + '">' + ic('phone', 15) + '<span>' + esc(FU.call) + '</span></a>'
            + (wa ? '<a class="fu-chip" href="' + esc(wa) + '" target="_blank" rel="noopener">' + ic('chat', 15) + '<span>' + esc(FU.whatsapp) + '</span></a>' : '')
            + '</div>';
    }

    return '<section class="fu-card' + (s.status === 'pending' ? ' is-attn' : '') + '" style="--st:' + esc(col) + ';">'
        + '<div class="fu-lead"><span class="fu-dot" aria-hidden="true"></span>'
        +   '<div class="fu-lead-t"><b>' + esc(p.lead) + '</b>'
        +   (p.sub ? '<small>' + esc(p.sub) + '</small>' : '') + meta + '</div></div>'
        + (p.primary ? btn(p.primary, 'fu-primary') : '')
        + (p.secondary.length ? '<div class="fu-sec">' + p.secondary.map(function (a) { return btn(a, 'fu-link'); }).join('') + '</div>' : '')
        + contact
        + '</section>';
}

/* ═════════════════════════════════════════════════════════
   3. DECLINE SHEET (needs a reason)
═════════════════════════════════════════════════════════ */
var dOv      = document.getElementById('bk-decline-ov');
/* .page-content is its own stacking context, which would trap this sheet UNDER the
   appointment drawer (a sibling of it). Re-parent to <body> so z-index is global. */
if (dOv) document.body.appendChild(dOv);
var dBox     = document.getElementById('fu-reasons');
var dOtherW  = document.getElementById('fu-reason-other-wrap');
var dOther   = document.getElementById('fu-reason-other');
var dErr     = document.getElementById('fu-decline-err');
var dCb      = null;
var dPicked  = -1;

function dRender() {
    var list = FU.reasons || [];
    dBox.innerHTML = list.map(function (r, i) {
        return '<button type="button" class="fu-reason' + (i === dPicked ? ' is-on' : '') + '" role="radio" aria-checked="' + (i === dPicked) + '" data-i="' + i + '">' + esc(r) + '</button>';
    }).join('');
    var other = dPicked === list.length - 1;
    dOtherW.hidden = !other;
    if (other) setTimeout(function () { dOther.focus(); }, 30);
}
function dOpen(cb) {
    dCb = cb; dPicked = -1; dOther.value = '';
    dErr.classList.add('d-none'); dRender();
    dOv.classList.remove('d-none');
}
function dClose() { dOv.classList.add('d-none'); dCb = null; }
if (dOv) {
    dBox.addEventListener('click', function (e) {
        var b = e.target.closest('.fu-reason');
        if (!b) return;
        dPicked = +b.dataset.i; dErr.classList.add('d-none'); dRender();
    });
    document.getElementById('fu-decline-x').addEventListener('click', dClose);
    dOv.addEventListener('click', function (e) { if (e.target === dOv) dClose(); });
    document.getElementById('fu-decline-go').addEventListener('click', function () {
        var list = FU.reasons || [];
        var reason = '';
        if (dPicked < 0) reason = '';
        else if (dPicked === list.length - 1) reason = (dOther.value || '').trim();
        else reason = list[dPicked];
        if (!reason) { dErr.textContent = FU.reasonPick; dErr.classList.remove('d-none'); return; }
        var cb = dCb; dClose();
        if (cb) cb(reason);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !dOv.classList.contains('d-none')) dClose(); });
}

/* ═════════════════════════════════════════════════════════
   2. REQUESTS INBOX
═════════════════════════════════════════════════════════ */
var rChip   = document.getElementById('bk-req-chip');
var rDrawer = document.getElementById('bk-req-drawer');
var rOv     = document.getElementById('bk-req-ov');
var rList   = document.getElementById('bk-req-list');
var rItems  = [];
var rTotal  = 0;

function branchParam() {
    var checks = [].slice.call(document.querySelectorAll('#ms-branch .bk-ms-check'));
    if (!checks.length) return (document.getElementById('filter-branch') || {}).value || '';
    var on = checks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
    return (on.length === 0 || on.length === checks.length) ? '' : on.join(',');
}

function setCount(n) {
    rTotal = n;
    var c1 = document.getElementById('bk-req-count'), c2 = document.getElementById('bk-req-count2');
    var label = n > 99 ? '99+' : String(n);
    if (c1) c1.textContent = label;
    if (c2) c2.textContent = label;
    if (rChip) rChip.classList.toggle('d-none', n < 1);
}

/* minutes → "5m / 3h / 2d" (EN) · "5 د / 3 س / 2 ي" (AR), always whole and short */
function agoText(min) {
    if (min == null) return '';
    var u = IS_RTL ? { m: ' د', h: ' س', d: ' ي' } : { m: 'm', h: 'h', d: 'd' };
    var t = min < 1 ? (IS_RTL ? 'لحظات' : 'just now')
        : min < 60 ? Math.round(min) + u.m
        : min < 1440 ? Math.round(min / 60) + u.h
        : Math.round(min / 1440) + u.d;
    return (min < 1) ? t : FU.ago.replace(':t', t);
}

function money(n, cur) {
    var v = Number(n || 0);
    return v.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' ' + esc(cur || '');
}

function rRender() {
    if (!rItems.length) {
        rList.innerHTML = '<div class="fu-req-empty"><div class="fu-req-empty-ic">' + ic('check', 26) + '</div>'
            + '<b>' + esc(FU.reqEmpty) + '</b><small>' + esc(FU.reqEmptySub) + '</small></div>';
        return;
    }
    rList.innerHTML = rItems.map(function (r) {
        var wa = waHref(r.phone);
        var meta = [];
        if (r.source)    meta.push(FU.viaSource.replace(':s', r.source));
        if (r.createdMin != null) meta.push(agoText(r.createdMin));
        return '<article class="fu-req' + (r.overdue ? ' is-overdue' : '') + '" data-id="' + r.id + '">'
            + '<header class="fu-req-top">'
            +   '<div class="fu-req-who"><b>' + esc(r.customer) + '</b>'
            +   (meta.length ? '<small>' + esc(meta.join(' · ')) + '</small>' : '') + '</div>'
            +   '<div class="fu-req-when"><b>' + esc(r.startLabel) + '</b><small>' + esc(r.dateLabel) + '</small></div>'
            + '</header>'
            + '<div class="fu-req-body">'
            +   '<div class="fu-req-svc">' + esc((r.services || []).join(' + ') || '—') + '</div>'
            +   '<div class="fu-req-sub">' + [r.employee, r.branch].filter(Boolean).map(esc).join(' · ')
            +     (r.total ? ' <span class="fu-req-price">' + money(r.total, r.currency) + '</span>' : '') + '</div>'
            +   (r.overdue ? '<div class="fu-req-late">' + ic('clock', 13) + esc(FU.reqOverdue) + '</div>' : '')
            + '</div>'
            + '<footer class="fu-req-act">'
            +   '<button type="button" class="fu-req-accept" data-r="accept">' + ic('check', 17) + '<span>' + esc(FU.accept) + '</span></button>'
            +   '<button type="button" class="fu-req-decline" data-r="decline">' + ic('x', 17) + '<span>' + esc(FU.decline) + '</span></button>'
            +   '<div class="fu-req-tools">'
            +     (r.phone ? '<a class="fu-icon-btn" href="' + esc(telHref(r.phone)) + '" aria-label="' + esc(FU.call) + '">' + ic('phone', 17) + '<span>' + esc(FU.call) + '</span></a>' : '')
            +     (wa ? '<a class="fu-icon-btn" href="' + esc(wa) + '" target="_blank" rel="noopener" aria-label="' + esc(FU.whatsapp) + '">' + ic('chat', 17) + '<span>' + esc(FU.whatsapp) + '</span></a>' : '')
            +     '<button type="button" class="fu-icon-btn" data-r="open" aria-label="' + esc(FU.bookedView) + '">' + ic('eye', 17) + '<span>' + esc(FU.bookedView) + '</span></button>'
            +   '</div>'
            + '</footer>'
            + '</article>';
    }).join('')
    + (rTotal > rItems.length ? '<p class="fu-req-more">+' + (rTotal - rItems.length) + '</p>' : '');
}

var rBusy = false;
function loadRequests() {
    if (!REQ_URL || rBusy) return;
    rBusy = true;
    var bp = branchParam();
    fetch(REQ_URL + (bp ? '?branch_id=' + encodeURIComponent(bp) : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
            if (!j || !j.ok) return;
            rItems = j.items || [];
            setCount(j.total || 0);
            if (!rDrawer.classList.contains('open')) return;
            rRender();
        })
        .catch(function () {})
        .then(function () { rBusy = false; });
}

function rOpen() {
    rDrawer.classList.add('open');
    rOv.classList.remove('d-none');
    rDrawer.setAttribute('aria-hidden', 'false');
    rRender();
    loadRequests();
}
function rClose() {
    rDrawer.classList.remove('open');
    rOv.classList.add('d-none');
    rDrawer.setAttribute('aria-hidden', 'true');
}

/* One status write, shared by accept / decline from the inbox */
function setStatus(id, status, reason) {
    var body = new URLSearchParams({ _method: 'PATCH', _token: CSRF, status: status });
    if (reason) body.set('reason', reason);
    return fetch(STATUS_URL.replace('__ID__', id), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok && j.ok, data: j }; }); });
}

function rResolve(id, status, reason, doneMsg, card) {
    if (card) card.classList.add('is-busy');
    setStatus(id, status, reason).then(function (res) {
        if (!res.ok) throw new Error((res.data && res.data.message) || 'fail');
        toast(doneMsg, status === 'confirmed' ? 'success' : 'warning');
        rItems = rItems.filter(function (x) { return String(x.id) !== String(id); });
        setCount(Math.max(0, rTotal - 1));
        if (card) { card.classList.add('is-gone'); setTimeout(rRender, 220); } else { rRender(); }
        if (window.bkRefreshViews) window.bkRefreshViews();
        if (!rItems.length && rTotal === 0) setTimeout(rClose, 900);
    }).catch(function (err) {
        if (card) card.classList.remove('is-busy');
        toast((err && err.message && err.message !== 'fail') ? err.message : FU.failed, 'error');
    });
}

if (rChip) {
    rChip.addEventListener('click', rOpen);
    document.getElementById('bk-req-close').addEventListener('click', rClose);
    rOv.addEventListener('click', rClose);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && rDrawer.classList.contains('open') && dOv.classList.contains('d-none')) rClose(); });

    rList.addEventListener('click', function (e) {
        var b = e.target.closest('[data-r]');
        if (!b) return;
        var card = b.closest('.fu-req');
        var id = card && card.dataset.id;
        if (!id) return;
        if (b.dataset.r === 'accept') rResolve(id, 'confirmed', null, FU.accepted, card);
        if (b.dataset.r === 'decline') dOpen(function (reason) { rResolve(id, 'cancelled_by_salon', reason, FU.declined, card); });
        if (b.dataset.r === 'open') { rClose(); if (window.apOpen) window.apOpen(id); }
    });
}

/* keep the count honest without hammering the server */
var rTimer = null;
function refreshSoon() { clearTimeout(rTimer); rTimer = setTimeout(loadRequests, 350); }
document.addEventListener('bk:appointments-changed', refreshSoon);
document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshSoon(); });
document.addEventListener('change', function (e) { if (e.target.closest && e.target.closest('#ms-branch')) refreshSoon(); });
setInterval(function () { if (!document.hidden) loadRequests(); }, 90000);
loadRequests();

/* ═════════════════════════════════════════════════════════
   4. BOOKED CARD (right after saving)
═════════════════════════════════════════════════════════ */
var bCard = document.getElementById('fu-booked');
if (bCard) document.body.appendChild(bCard);   /* same stacking-context reason as the decline sheet */
var bTimer = null;
function bHide() { if (!bCard) return; bCard.classList.add('d-none'); clearTimeout(bTimer); }

function booked(o) {
    if (!bCard || !o) return;
    var wa = waHref(o.phone, FU.bookedWaMsg.replace(':name', o.name || '').replace(':when', o.when || ''));
    bCard.innerHTML =
        '<div class="fu-booked-ic">' + ic('check', 20) + '</div>'
        + '<div class="fu-booked-t"><b>' + esc(FU.bookedTitle) + '</b>'
        +   '<small>' + esc([o.name, o.when].filter(Boolean).join(' · ')) + '</small></div>'
        + '<div class="fu-booked-act">'
        +   (wa ? '<a class="fu-booked-btn" href="' + esc(wa) + '" target="_blank" rel="noopener">' + ic('chat', 15) + '<span>' + esc(FU.whatsapp) + '</span></a>' : '')
        +   (o.id ? '<button type="button" class="fu-booked-btn is-main" data-b="view">' + ic('eye', 15) + '<span>' + esc(FU.bookedView) + '</span></button>' : '')
        + '</div>'
        + '<button type="button" class="fu-booked-x" data-b="x" aria-label="' + esc(window.BK.t.close || 'Close') + '">' + ic('x', 14) + '</button>';
    bCard.classList.remove('d-none');
    /* restart the entrance animation */
    bCard.style.animation = 'none'; void bCard.offsetWidth; bCard.style.animation = '';
    clearTimeout(bTimer); bTimer = setTimeout(bHide, 14000);
    bCard._id = o.id;
}
if (bCard) bCard.addEventListener('click', function (e) {
    var b = e.target.closest('[data-b]');
    if (!b) return;
    if (b.dataset.b === 'x') bHide();
    if (b.dataset.b === 'view') { var id = bCard._id; bHide(); if (id && window.apOpen) window.apOpen(id); }
});

window.bkFollowUp = {
    html: barHtml,
    decline: dOpen,
    booked: booked,
    refresh: refreshSoon,
};

})();
