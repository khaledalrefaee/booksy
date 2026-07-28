/**
 * Appointments workspace — calendar, staff view, list, quick-add, checkout,
 * offline outbox. Extracted from company/appointments/index.blade.php, where it
 * lived as ~3,800 inline lines that no browser could ever cache.
 *
 * Classic script, one IIFE, matching the convention in eslint.config.js. The
 * server hands it data through the global `BK` object (routes, translated
 * strings, status metadata) — data only, never behaviour.
 *
 * Publishes to window: bkCalendar, sfShowPopup, apOpen, switchView,
 * bkRefreshViews, bkQuickAdd. Inline HTML handlers depend on those names.
 */
(function () {
'use strict';

/* ══════════════════════════════════════════════════════════════════
   REQUEST LIFECYCLE

   Every view-scoped load goes through bkFetch(channel, …). Starting a
   request on a channel aborts the one before it, which fixes a real bug:
   clicking through three weeks fired three requests and rendered whichever
   came back last — not necessarily the week on screen.

   bkAbortSignal is exposed so cleanup can cancel everything at once.
   ══════════════════════════════════════════════════════════════════ */
var _controllers = Object.create(null);

function bkFetch(channel, url, options) {
    if (_controllers[channel]) {
        _controllers[channel].abort();
    }
    var ctrl = new AbortController();
    _controllers[channel] = ctrl;

    var opts = options || {};
    opts.signal = ctrl.signal;
    opts.headers = opts.headers || { 'X-Requested-With': 'XMLHttpRequest' };

    return fetch(url, opts).finally(function () {
        if (_controllers[channel] === ctrl) {
            delete _controllers[channel];
        }
    });
}

/** True when a rejection is just us cancelling — never show the user an error. */
function bkAborted(err) {
    return err && (err.name === 'AbortError' || err.code === 20);
}

function bkAbortAll() {
    Object.keys(_controllers).forEach(function (k) {
        _controllers[k].abort();
        delete _controllers[k];
    });
}

/* ── timers, tracked so they can be stopped ── */
var _timers = [];

function bkInterval(fn, ms) {
    var id = setInterval(fn, ms);
    _timers.push(id);
    return id;
}

function bkTeardown() {
    bkAbortAll();
    _timers.forEach(clearInterval);
    _timers.length = 0;
}

/* A page that is going away must not keep polling or hold open sockets. */
window.addEventListener('pagehide', bkTeardown);

var EVENTS_URL = BK.routes.appointmentsCalendarEvents;
var IS_RTL     = BK.isRtl;
var FC_LOCALE  = BK.fcLocale;
/* last-resort branch when nothing else gives context (single-branch companies) */
var FIRST_BRANCH = BK.firstBranch;

/* ── status metadata + legal moves, straight from the PHP enum ──
   Nothing here is hand-maintained: adding a status or changing a colour is a
   one-line edit in App\Enums\AppointmentStatus and this follows automatically. */
var STATUS_DEFS   = BK.statusDefs;
var ALLOWED_MOVES = BK.allowedMoves;

var STATUS_LABELS = {};
var EV_COLORS     = {};
Object.keys(STATUS_DEFS).forEach(function (k) {
    STATUS_LABELS[k] = STATUS_DEFS[k].label;
    EV_COLORS[k]     = STATUS_DEFS[k].color;
});

/* statuses that still occupy their slot — the same set the backend's
   conflict checks use, so client and server agree on what "busy" means */
var LIVE_STATUSES = BK.liveStatuses;

var EMP_COLORS = ['#7c3aed','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#f59e0b','#8b5cf6'];

var activeStatuses = Object.keys(STATUS_DEFS);

/* Legal next statuses for a salon user, per the server's state machine. */
function _nextMoves(from) {
    return ALLOWED_MOVES[from] || [];
}
var activeBranch   = '';

/* ════════════════════════════════
   FULLCALENDAR
════════════════════════════════ */
var calEl    = document.getElementById('booksy-calendar');
var calendar = new FullCalendar.Calendar(calEl, {
    locale:       FC_LOCALE,
    direction:    IS_RTL ? 'rtl' : 'ltr',
    initialView:  'timeGridWeek',
    height:       'auto',
    firstDay:     IS_RTL ? 0 : 1,
    nowIndicator: true,
    navLinks:     true,
    dayMaxEvents: true,
    scrollTime:   '08:00:00',
    slotMinTime:  '00:00:00',
    slotMaxTime:  '24:00:00',
    slotDuration: '00:30:00',
    expandRows:   false,

    headerToolbar: {
        start:  'prev,next today',
        center: 'title',
        end:    'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
    },
    buttonText: {
        today: IS_RTL ? 'اليوم'    : 'Today',
        month: IS_RTL ? 'الشهر'    : 'Month',
        week:  IS_RTL ? 'الأسبوع'  : 'Week',
        day:   IS_RTL ? 'اليوم'    : 'Day',
        list:  IS_RTL ? 'القائمة'  : 'List',
    },

    /* Custom day headers */
    dayHeaderContent: function (arg) {
        var abbrs_en = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
        var abbrs_ar = ['أحد','اثن','ثلث','أرب','خمس','جمع','سبت'];
        var abbrs = IS_RTL ? abbrs_ar : abbrs_en;
        return {
            html: '<div class="d-abbr">' + abbrs[arg.date.getDay()] + '</div>'
                + '<div class="d-num">'  + arg.date.getDate() + '</div>'
        };
    },

    /* Custom slot labels */
    slotLabelContent: function (arg) {
        var h    = arg.date.getHours();
        var h12  = h % 12 || 12;
        var ampm = h < 12
            ? (IS_RTL ? 'ص' : 'AM')
            : (IS_RTL ? 'م' : 'PM');
        return { html: '<span>' + h12 + '<br><small style="font-size:.55rem">' + ampm + '</small></span>' };
    },

    /* Event card content */
    eventContent: function (arg) {
        var props = arg.event.extendedProps;
        if (props.type === 'closed' || props.type === 'outside-hours') return;

        /* blocked-time window: lock icon + reason + who */
        if (props.type === 'blocked') {
            var bt = (arg.event.start ? _fmtTime(arg.event.start) : '')
                   + (arg.event.end ? ' – ' + _fmtTime(arg.event.end) : '');
            return {
                html: '<div class="ev-blocked">'
                    + '<div class="ev-blocked-head">'
                    + '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> '
                    + _esc(arg.event.title) + '</div>'
                    + '<div class="ev-blocked-sub">' + _esc(bt)
                    + (props.employee ? ' · ' + _esc(props.employee) : (IS_RTL ? ' · كل الفرع' : ' · whole branch'))
                    + '</div></div>'
            };
        }

        /* month view: one count chip per day */
        if (props.type === 'day-count') {
            var n   = props.count || 0;
            var lbl = IS_RTL
                ? (n === 1 ? 'موعد' : n === 2 ? 'موعدان' : 'مواعيد')
                : (n === 1 ? 'appointment' : 'appointments');
            return { html: '<div class="ev-daycount"><b>' + n + '</b> ' + lbl + '</div>' };
        }

        var parts   = arg.event.title.split(' · ');
        var name    = parts[0] || '';
        var service = parts.slice(1).join(' · ') || props.service || '';
        var emp     = props.employee || '';

        var tStart = arg.event.start ? _fmtTime(arg.event.start) : '';
        var tEnd   = arg.event.end   ? _fmtTime(arg.event.end)   : '';
        var tStr   = tStart + (tEnd ? ' – ' + tEnd : '');

        var groupBadge = props.group
            ? '<span class="ev-group-badge"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' + (IS_RTL ? 'جماعي' : 'group') + '</span>'
            : '';

        return {
            html: '<div class="ev-time">' + _esc(tStr) + '</div>'
                + '<div class="ev-name">' + _esc(name) + groupBadge + '</div>'
                + '<div class="ev-svc">'  + _esc(service) + '</div>'
                + (emp ? '<div class="ev-emp"><span class="ev-emp-dot"></span>' + _esc(emp) + '</div>' : '')
        };
    },

    /* Fetch events */
    events: function (info, ok, fail) {
        var p = new URLSearchParams({ start: info.startStr, end: info.endStr });
        if (activeBranch) p.set('branch_id', activeBranch);
        /* server-side status filter (pills) + month view gets day counts, not events */
        p.set('statuses', activeStatuses.join(','));
        var isMonth = calendar && calendar.view && calendar.view.type === 'dayGridMonth';
        if (isMonth) p.set('aggregate', '1');
        bkFetch('calendar', EVENTS_URL + '?' + p)
            .then(r => r.json())
            .then(function (data) {
                var filtered = data.filter(function (ev) {
                    var t = ev.extendedProps && ev.extendedProps.type;
                    if (t === 'closed' || t === 'outside-hours' || t === 'day-count' || t === 'blocked') return true;
                    return activeStatuses.includes(ev.extendedProps.status);
                });
                ok(filtered);
            })
            .catch(function (err) {
                /* A superseded request is not a failure — resolve empty so
                   FullCalendar does not paint an error over the newer view. */
                if (bkAborted(err)) return ok([]);
                fail(err);
            });
    },

    /* Empty slot → open the quick-add drawer right here (no page navigation).
       Month view has no meaningful time, so a day click drills into that day. */
    dateClick: function (info) {
        if (calendar.view.type === 'dayGridMonth') {
            calendar.changeView('timeGridDay', info.dateStr);
            return;
        }
        bkQuickAdd(info.date);
    },

    /* Click → popup (skip background events) */
    eventClick: function (info) {
        var t = info.event.extendedProps && info.event.extendedProps.type;
        if (t === 'closed' || t === 'outside-hours') return;
        info.jsEvent.preventDefault();

        /* month count chip → drill into that day */
        if (t === 'day-count') {
            calendar.changeView('timeGridDay', info.event.extendedProps.day);
            return;
        }

        /* blocked-time window → offer to unblock */
        if (t === 'blocked') {
            if (window.bkUnblock) window.bkUnblock(info.event.extendedProps.blockId, info.event.title);
            return;
        }

        var p    = info.event.extendedProps;
        var parts= info.event.title.split(' · ');
        showPopup({
            customer:       parts[0] || '',
            service:        p.service || parts.slice(1).join(' · '),
            branch:         p.branch,
            employee:       p.employee,
            employeeImage:  p.employeeImage,
            status:         p.status,
            color:          info.event.backgroundColor,
            price:          p.price,
            currency:       p.currency,
            showUrl:        p.showUrl,
            startLabel:     _fmtTime(info.event.start),
            endLabel:       _fmtTime(info.event.end),
            changedBy:      p.changedBy,
            changedAt:      p.changedAt,
            prevStatus:     p.prevStatus,
        }, info.jsEvent);
    },
});
/* Lazy render: FullCalendar only initializes (and fetches events) when its
   tab is first opened — the default view is Staff, so this saves a request
   and ~main-thread work on every page load. */
var calRendered = false;
function ensureCalRendered() {
    if (calRendered) return;
    calRendered = true;
    calendar.render();
}
function calRefetch() {
    if (calRendered) calendar.refetchEvents();
}
window.bkCalendar = calendar; // expose for real-time updates via Reverb

/* ════════════════════════════════
   POPUP
════════════════════════════════ */
var popup = document.getElementById('bk-popup');

function showPopup(a, ev) {
    var color   = a.color || '#0E7C82';
    var initials= _initials(a.employee);

    document.getElementById('bk-pp-hdr').style.background = color;
    document.getElementById('bk-pp-status').textContent   = STATUS_LABELS[a.status] || a.status;
    document.getElementById('bk-pp-title').textContent    = a.customer || '—';
    document.getElementById('bk-pp-time').textContent     = (a.startLabel || '') + ' – ' + (a.endLabel || '');
    var avEl = document.getElementById('bk-pp-emp-av');
    if (a.employeeImage) {
        avEl.innerHTML = '<img src="' + a.employeeImage + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        avEl.style.background = 'transparent';
    } else {
        avEl.innerHTML = '';
        avEl.textContent = initials;
        avEl.style.background = color;
    }
    document.getElementById('bk-pp-emp-name').textContent = a.employee || '—';
    document.getElementById('bk-pp-service').textContent  = a.service  || '—';
    document.getElementById('bk-pp-branch').textContent   = a.branch   || '—';
    document.getElementById('bk-pp-price').textContent    = (a.price || '0.00') + ' ' + (a.currency || 'SAR');
    document.getElementById('bk-pp-link').href            = a.showUrl  || '#';

    /* audit row */
    var auditRow = document.getElementById('bk-pp-audit');
    if (a.changedBy) {
        var dt = a.changedAt ? new Date(a.changedAt).toLocaleString(IS_RTL ? 'ar-SA' : 'en-US', {
            month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12: true
        }) : '';
        var prev = a.prevStatus ? (STATUS_LABELS[a.prevStatus] || a.prevStatus) + ' → ' : '';
        auditRow.innerHTML = '<svg style="flex-shrink:0;color:var(--bk-accent);" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'
            + '<span>' + (IS_RTL ? 'غُيِّر بواسطة: ' : 'Changed by: ') + '<b>' + _esc(a.changedBy) + '</b>'
            + (dt ? ' · ' + dt : '')
            + (prev ? '<br><small style="color:var(--cal-text-muted);">' + prev + (STATUS_LABELS[a.status]||a.status) + '</small>' : '')
            + '</span>';
        auditRow.style.display = 'flex';
    } else {
        auditRow.style.display = 'none';
    }

    popup.classList.remove('d-none');

    var pw = 292, ph = 290;
    var wx = window.innerWidth, wy = window.innerHeight;
    var left = ev.clientX + 16;
    var top  = ev.clientY + 16;
    if (left + pw > wx - 10) left = ev.clientX - pw - 16;
    if (top  + ph > wy - 10) top  = ev.clientY - ph - 16;
    popup.style.left = left + 'px';
    popup.style.top  = top  + 'px';
}

document.getElementById('bk-pp-close').addEventListener('click', function () {
    popup.classList.add('d-none');
});
document.addEventListener('click', function (e) {
    if (!popup.contains(e.target) && !e.target.closest('.fc-event')) {
        popup.classList.add('d-none');
    }
});

/* ════════════════════════════════
   LIST VIEW
════════════════════════════════ */
var listLoaded  = false;
var listAllData = []; /* raw appointment events cache */
var listSearch  = '';
var listSort    = 'closest';

/* ── Relative time helper ── */
function _relativeTime(dateStr) {
    if (!dateStr) return { text: '—', isPast: false, isNow: false, cls: '' };
    var now  = Date.now();
    var t    = new Date(dateStr).getTime();
    var diff = t - now;
    var abs  = Math.abs(diff);
    var isPast = diff < 0;

    var mins  = Math.floor(abs / 60000);
    var hours = Math.floor(abs / 3600000);
    var days  = Math.floor(abs / 86400000);

    if (abs < 60000) return { text: IS_RTL ? 'الآن' : 'Now', isPast: false, isNow: true, cls: 'bk-rel-now' };

    var label;
    if (IS_RTL) {
        if (days > 0)       label = days + (days === 1 ? ' يوم' : ' أيام');
        else if (hours > 0) label = hours + (hours === 1 ? ' ساعة' : ' ساعات');
        else                label = mins + ' د';
        label = isPast ? ('منذ ' + label) : ('بعد ' + label);
    } else {
        if (days > 0)       label = days + (days === 1 ? ' day' : ' days');
        else if (hours > 0) label = hours + (hours === 1 ? ' hr' : ' hrs');
        else                label = mins + ' min';
        label = isPast ? (label + ' ago') : ('in ' + label);
    }

    var cls = 'bk-rel-past';
    if (!isPast) {
        cls = mins <= 30 ? 'bk-rel-soon' : hours <= 2 ? 'bk-rel-upcoming' : 'bk-rel-future';
    }

    return { text: label, isPast: isPast, isNow: false, cls: cls };
}

function renderListRows() {
    var tbody = document.getElementById('list-tbody');
    var q = listSearch.trim().toLowerCase();

    var appts = listAllData.filter(function (ev) {
        var pr = ev.extendedProps || {};
        var t  = pr.type;
        if (t === 'closed' || t === 'outside-hours') return false;
        if (!pr.status) return false;
        if (!activeStatuses.includes(pr.status)) return false;
        if (q) {
            var title = (ev.title || '').toLowerCase();
            var br    = (pr.branch   || '').toLowerCase();
            var emp   = (pr.employee || '').toLowerCase();
            var svc   = (pr.service  || '').toLowerCase();
            var idStr = String(ev.id || '');
            if (title.indexOf(q) < 0 && br.indexOf(q) < 0 && emp.indexOf(q) < 0 && svc.indexOf(q) < 0 && idStr.indexOf(q) < 0) return false;
        }
        return true;
    });

    /* ── Sort ── */
    var now = Date.now();
    appts.sort(function(a, b) {
        var tA = a.start ? new Date(a.start).getTime() : 0;
        var tB = b.start ? new Date(b.start).getTime() : 0;
        var pA = parseFloat((a.extendedProps || {}).price || 0);
        var pB = parseFloat((b.extendedProps || {}).price || 0);
        switch (listSort) {
            case 'closest':  return Math.abs(tA - now) - Math.abs(tB - now);
            case 'farthest': return Math.abs(tB - now) - Math.abs(tA - now);
            case 'newest':   return tB - tA;
            case 'price-high': return pB - pA;
            case 'price-low':  return pA - pB;
            default: return 0;
        }
    });

    if (!appts.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5" style="color:var(--cal-text-muted);">' + BK.t.no_appointments_found + '</td></tr>';
        return;
    }

    tbody.innerHTML = appts.map(function (ev) {
                var pr     = ev.extendedProps || {};
                var title  = ev.title || '';
                var pts    = title.split(' · ');
                var cust   = _esc(pts[0] || '—');
                var svc    = _esc(pts.slice(1).join(' · ') || pr.service || '—');
                var col    = EV_COLORS[pr.status] || '#94a3b8';
                var init   = _initials(pr.employee || '');
                var empIdx = Math.abs(_hashStr(pr.employee || '')) % EMP_COLORS.length;
                var empCol = EMP_COLORS[empIdx];
                var empImg = pr.employeeImage || null;
                var dt     = ev.start ? new Date(ev.start).toLocaleString(IS_RTL ? 'ar-SA' : 'en-US', {
                    year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12: true
                }) : '—';
                var endDt  = ev.end ? new Date(ev.end).toLocaleTimeString(IS_RTL ? 'ar-SA' : 'en-US', {
                    hour:'2-digit', minute:'2-digit', hour12: true
                }) : '';

                /* relative time */
                var rel = _relativeTime(ev.start);

                /* customer phone sub-line */
                var phoneBadge = '';
                if (pr.customerPhone) {
                    var ph = _esc(pr.customerPhone);
                    phoneBadge = '<div style="display:flex;align-items:center;gap:6px;margin-top:3px;">'
                        + '<a href="tel:' + ph + '" onclick="event.stopPropagation();" title="' + BK.t.call + '" style="display:inline-flex;align-items:center;gap:3px;font-size:.68rem;color:var(--cal-text-muted);text-decoration:none;background:var(--cal-surface2);border-radius:8px;padding:2px 7px;border:1px solid var(--cal-border);">'
                        + '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.5 1.18 2 2 0 012.5 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.09a16 16 0 006 6l.75-.75a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>'
                        + ph + '</a>'
                        + '<a href="https://wa.me/' + ph.replace(/\D/g,'') + '" target="_blank" onclick="event.stopPropagation();" title="WhatsApp" style="display:inline-flex;align-items:center;gap:3px;font-size:.68rem;color:#25d366;text-decoration:none;background:rgba(37,211,102,.1);border-radius:8px;padding:2px 7px;border:1px solid rgba(37,211,102,.25);">'
                        + '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>'
                        + 'WhatsApp</a>'
                        + '</div>';
                }

                /* Quick action buttons. The list comes from the server's state
                   machine, so the UI can never offer a move the backend will
                   reject — the old hand-written table drifted from it. */
                var btns = _nextMoves(pr.status).map(function (to) {
                    var d = STATUS_DEFS[to];
                    /* data-* + one delegated listener, not inline onclick:
                       _quickStatus lives inside this IIFE and was never on
                       window, so the old onclick="_quickStatus(…)" threw a
                       ReferenceError and these buttons silently did nothing. */
                    return '<button data-quick-status="' + to + '" data-appt="' + ev.id + '" '
                        + 'title="' + _esc(d.label) + '" '
                        + 'style="border-radius:10px;padding:3px 10px;font-size:.68rem;font-weight:800;cursor:pointer;'
                        + 'background:' + d.color + '1a;color:var(--cal-text);border:1px solid ' + d.color + '59;'
                        + 'white-space:nowrap;transition:opacity .15s;">'
                        + _esc(d.label) + '</button>';
                }).join('');

                return '<tr id="appt-row-' + ev.id + '" style="cursor:pointer;" onclick="location.href=\'' + (pr.showUrl || '#') + '\'">'
                    /* # */
                    + '<td class="ps-4" style="color:var(--cal-text-muted);font-size:.76rem;font-weight:700;">#' + ev.id + '</td>'
                    /* Customer + phone */
                    + '<td><div style="font-weight:800;font-size:.84rem;">' + cust + '</div>' + phoneBadge + '</td>'
                    /* Service */
                    + '<td style="color:var(--cal-text-soft);font-size:.82rem;">' + svc + '</td>'
                    /* Employee */
                    + '<td><div style="display:flex;align-items:center;gap:7px;">'
                    +   (empImg
                        ? '<img src="' + empImg + '" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,.3);" loading="lazy">'
                        : '<div style="width:28px;height:28px;border-radius:50%;background:' + empCol + ';display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 2px 6px ' + empCol + '55;">' + _esc(init) + '</div>')
                    +   '<span style="font-size:.82rem;font-weight:600;">' + _esc(pr.employee || '—') + '</span>'
                    + '</div></td>'
                    /* Branch */
                    + '<td style="color:var(--cal-text-soft);font-size:.82rem;">' + _esc(pr.branch || '—') + '</td>'
                    /* Time + relative */
                    + '<td style="font-size:.8rem;white-space:nowrap;">'
                    +   '<div style="font-weight:700;">' + dt + '</div>'
                    +   (endDt ? '<div style="color:var(--cal-text-muted);font-size:.72rem;">← ' + endDt + '</div>' : '')
                    +   '<div class="bk-rel-badge ' + rel.cls + '">'
                    +     (rel.isNow ? '<span class="bk-rel-dot"></span>' : '')
                    +     rel.text
                    +   '</div>'
                    + '</td>'
                    /* Status */
                    + '<td><span id="appt-status-' + ev.id + '" style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;background:' + col + '20;color:' + col + ';font-size:.7rem;font-weight:800;border:1px solid ' + col + '40;">'
                    +   '<span style="width:6px;height:6px;border-radius:50%;background:' + col + ';display:inline-block;"></span>'
                    +   (STATUS_LABELS[pr.status] || pr.status)
                    + '</span></td>'
                    /* Price */
                    + '<td style="font-weight:800;font-size:.88rem;">' + (pr.price || '0.00') + ' ' + _esc(pr.currency || '') + '</td>'
                    /* Actions */
                    + '<td class="pe-4"><div style="display:flex;gap:4px;flex-wrap:wrap;">' + btns + '</div></td>'
                    + '</tr>';
            }).join('');
}

function loadListView() {
    if (listLoaded) { renderListRows(); return; }
    var tbody = document.getElementById('list-tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5" style="color:var(--cal-text-muted);"><div class="spinner-border spinner-border-sm me-2"></div>' + BK.t.loading + '</td></tr>';

    var p = new URLSearchParams();
    if (activeBranch) p.set('branch_id', activeBranch);

    bkFetch('list', EVENTS_URL + '?' + p)
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            listAllData = data;
            listLoaded  = true;
            renderListRows();
        })
        .catch(function (err) {
            if (bkAborted(err)) return;
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4" style="color:#ef4444;">⚠ ' + BK.t.error_loading_data + ': ' + err.message + '</td></tr>';
        });
}

/* ════════════════════════════════
   QUICK STATUS
════════════════════════════════ */
var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
var STATUS_ROUTE_BASE = BK.routes.appointmentsUpdateStatus;

function _quickStatus(id, newStatus, btn) {
    btn.disabled = true;
    btn.style.opacity = '.4';
    var url = STATUS_ROUTE_BASE.replace('__ID__', id);
    var body = new URLSearchParams({ _method: 'PATCH', _token: CSRF, status: newStatus });
    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: body })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (!json.ok) throw new Error('fail');
            /* update cached data */
            listAllData = listAllData.map(function(ev) {
                if (String(ev.id) === String(id)) {
                    ev = JSON.parse(JSON.stringify(ev)); /* clone */
                    ev.extendedProps.status = json.status;
                }
                return ev;
            });
            renderListRows();
            calRefetch();
        })
        .catch(function() { btn.disabled = false; btn.style.opacity = '1'; });
}

/* One listener for the whole table instead of one per button. Because it is
   bound to the tbody — which renderListRows only ever refills — it survives
   every re-render and needs no re-binding. */
document.getElementById('list-tbody').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-quick-status]');
    if (!btn) return;

    e.stopPropagation();          // don't also follow the row's link
    e.preventDefault();
    _quickStatus(btn.dataset.appt, btn.dataset.quickStatus, btn);
});

/* ════════════════════════════════
   FILTERS
════════════════════════════════ */
document.querySelectorAll('.bk-st-pill').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var st = this.dataset.status;
        if (activeStatuses.includes(st)) {
            activeStatuses = activeStatuses.filter(s => s !== st);
            this.classList.add('off');
        } else {
            activeStatuses.push(st);
            this.classList.remove('off');
        }
        calRefetch();
        if (!document.getElementById('view-list').classList.contains('d-none')) renderListRows();
    });
});

document.getElementById('filter-branch').addEventListener('change', function () {
    activeBranch = this.value;
    listLoaded   = false;
    listAllData  = [];
    calRefetch();
    if (!document.getElementById('view-list').classList.contains('d-none')) {
        loadListView();
    }
    sfLoaded = false;
    if (!document.getElementById('view-staff').classList.contains('d-none')) {
        loadStaffView();
    }
});

document.getElementById('filter-sort').addEventListener('change', function () {
    listSort = this.value;
    if (!document.getElementById('view-list').classList.contains('d-none')) {
        if (listLoaded) renderListRows(); else loadListView();
    }
});

var _searchTimer = null;
document.getElementById('bk-search').addEventListener('input', function () {
    listSearch = this.value;
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(function () {
        /* auto-switch to list view when user types in search */
        if (document.getElementById('view-list').classList.contains('d-none')) {
            switchView('list');
        }
        if (listLoaded) renderListRows(); else loadListView();
    }, 220);
});

/* ════════════════════════════════
   STAFF VIEW
════════════════════════════════ */
var STAFF_URL  = BK.routes.appointmentsStaffEvents;
var sfDate     = new Date();
var sfLoaded   = false;

var SF_EMP_COLORS = ['#7c3aed','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#f59e0b','#8b5cf6','#14b8a6','#a855f7'];
var HOUR_S = 0, HOUR_E = 24;
/* px per hour — user-adjustable from calendar settings (Fresha-style zoom) */
var SLOT_H = parseInt(localStorage.getItem('bk_sf_zoom'), 10) || 80;
/* quick actions on calendar (click an empty slot → add appointment) */
var sfQuickActions = localStorage.getItem('bk_sf_quick') !== '0';
var sfHourStart = 0; /* first visible hour of the current staff grid */
var sfDefaultBranch = null; /* fallback branch for quick-add drawer */

var DAY_AR  = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
var DAY_EN  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
var MON_AR  = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
var MON_EN  = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function sfDateStr(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
function sfFmtTitle(d) {
    var dn = IS_RTL ? DAY_AR[d.getDay()] : DAY_EN[d.getDay()];
    var mn = IS_RTL ? MON_AR[d.getMonth()] : MON_EN[d.getMonth()];
    return dn + '، ' + d.getDate() + ' ' + mn + ' ' + d.getFullYear();
}
function sfIsToday(d) {
    var t = new Date();
    return d.getDate()===t.getDate() && d.getMonth()===t.getMonth() && d.getFullYear()===t.getFullYear();
}
function sfSameDay(a, b) {
    return a.getDate()===b.getDate() && a.getMonth()===b.getMonth() && a.getFullYear()===b.getFullYear();
}
function sfAddDays(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }

var SF_FD = IS_RTL ? 6 : 1; /* first day of week: Sat (ar) / Mon (en) */
function sfStartOfWeek(d) {
    var x = new Date(d); x.setHours(0,0,0,0);
    x.setDate(x.getDate() - ((x.getDay() - SF_FD + 7) % 7));
    return x;
}
var DAY_S_AR = ['أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة','سبت'];
var DAY_S_EN = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
function _fmt24(d) { return String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0'); }
/* Fresha-style pastel chip: solid fallback, then color-mix when supported */
function sfChipBg(color) {
    return 'background:' + color + ';background:color-mix(in srgb, ' + color + ' 45%, #fff);';
}

/* ── View state: day | 3days | week | month ── */
var VIEW_LABELS = {
    day:     IS_RTL ? 'اليوم'   : 'Day',
    '3days': IS_RTL ? '3 أيام'  : '3 days',
    week:    IS_RTL ? 'الأسبوع' : 'Week',
    month:   IS_RTL ? 'الشهر'   : 'Month',
};
var sfView = localStorage.getItem('bk_sf_view') || 'day';
if (!VIEW_LABELS[sfView]) sfView = 'day';

var sfTeamSel    = null;   /* null = all; else array of employee-id strings */
var sfStaffList  = [];     /* last known staff (for team filter menu) */
var sfDayData    = null;   /* cached day payload */
var sfLastEvents = null;   /* cached mapped events for range/month */
var sfChipMap    = {};     /* chip id → appt for popups */

function sfTeamAllows(id) {
    return !sfTeamSel || sfTeamSel.indexOf(String(id == null ? 0 : id)) >= 0;
}

/* ── Dispatcher ── */
function loadStaffView() {
    document.getElementById('sf-view-label').textContent = VIEW_LABELS[sfView];
    if (sfView === 'day')   return loadDayView();
    if (sfView === 'month') return loadMonthView();
    return loadRangeView(sfView === 'week' ? 7 : 3);
}

/* ── Fetch + map events (range / month views) ── */
function evToAppt(ev) {
    var pr = ev.extendedProps || {};
    if (pr.type !== 'appointment') return null;
    var pts = (ev.title || '').split(' · ');
    var s = ev.start ? new Date(ev.start) : null;
    if (!s) return null;
    return {
        id: ev.id, customer: pts[0] || '', service: pr.service || pts.slice(1).join(' · '),
        branch: pr.branch, employee: pr.employee, employeeId: pr.employeeId,
        status: pr.status, color: ev.backgroundColor || EV_COLORS[pr.status] || '#64748b',
        price: pr.price, currency: pr.currency, showUrl: pr.showUrl,
        start: s, end: ev.end ? new Date(ev.end) : null,
    };
}
function sfFetchEvents(start, end, cb) {
    var grid = document.getElementById('sf-grid');
    grid.innerHTML = '<div class="text-center py-5 w-100" style="color:var(--cal-text-muted);"><div class="spinner-border spinner-border-sm"></div></div>';
    grid.style.minWidth = '0';
    var p = new URLSearchParams({ start: sfDateStr(start), end: sfDateStr(end) });
    if (activeBranch) p.set('branch_id', activeBranch);
    bkFetch('staffGrid', EVENTS_URL + '?' + p)
        .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(function(data){ cb((data || []).map(evToAppt).filter(Boolean)); })
        .catch(function(err){
            if (bkAborted(err)) return;
            grid.innerHTML = '<div class="text-center py-5 w-100" style="color:#ef4444;">⚠ ' + err.message + '</div>';
        });
}

/* ── RANGE VIEW (3 days / week) ── */
function loadRangeView(n) {
    var start = n === 7 ? sfStartOfWeek(sfDate) : new Date(sfDate);
    start.setHours(0,0,0,0);
    var last = sfAddDays(start, n - 1);
    var mn1 = IS_RTL ? MON_AR[start.getMonth()] : MON_EN[start.getMonth()];
    var mn2 = IS_RTL ? MON_AR[last.getMonth()]  : MON_EN[last.getMonth()];
    document.getElementById('sf-title').textContent = start.getMonth() === last.getMonth()
        ? start.getDate() + '–' + last.getDate() + ' ' + mn2 + ' ' + last.getFullYear()
        : start.getDate() + ' ' + mn1 + ' – ' + last.getDate() + ' ' + mn2 + ' ' + last.getFullYear();
    document.getElementById('sf-subtitle').textContent = VIEW_LABELS[sfView];

    sfEnsureStaff(function(){
        sfFetchEvents(start, sfAddDays(start, n), function(appts){
            sfLastEvents = { type: 'range', n: n, start: start, appts: appts };
            renderRangeGrid();
        });
    });
}
function renderRangeGrid() {
    var st = sfLastEvents;
    if (!st || st.type !== 'range') return;
    var appts = st.appts.filter(function(a){
        return activeStatuses.includes(a.status) && sfTeamAllows(a.employeeId);
    });
    var isMobile = window.innerWidth <= 768;
    document.getElementById('sf-grid').style.minWidth = isMobile && st.n === 7 ? '640px' : '0';
    sfChipMap = {};

    /* ── Lanes: one horizontal row per service provider (Fresha layout) ── */
    var lanes = sfStaffList.filter(function(s){ return s.id !== 0 && sfTeamAllows(s.id); });
    /* walk-in lane only when it has visible appointments */
    var hasWalkin = appts.some(function(a){ return !a.employeeId; });
    if (hasWalkin && sfTeamAllows(0)) {
        var wi = sfStaffList.find(function(s){ return s.id === 0; }) || { id: 0, name: IS_RTL ? 'حضور مباشر' : 'Walk-in', initials: 'WI' };
        lanes = [wi].concat(lanes);
    }

    var EMP_W = isMobile ? 62 : 92;
    var tpl = EMP_W + 'px repeat(' + st.n + ', minmax(' + (isMobile ? 88 : 120) + 'px, 1fr))';

    var html = '<div style="width:100%;">';

    /* ── Header row: corner + day headers ── */
    html += '<div class="sf-rg-hdrrow" style="grid-template-columns:' + tpl + ';">';
    html += '<div class="sf-rg-corner"></div>';
    for (var i = 0; i < st.n; i++) {
        var day = sfAddDays(st.start, i);
        var dn = IS_RTL ? DAY_S_AR[day.getDay()] : DAY_S_EN[day.getDay()];
        var cnt = appts.filter(function(a){ return sfSameDay(a.start, day); }).length;
        html += '<div class="sf-rg-day' + (sfIsToday(day) ? ' today' : '') + '">'
            + '<span>' + dn + '</span><span class="num">' + day.getDate() + '</span>'
            + (cnt ? '<span class="dcnt">' + cnt + '</span>' : '')
            + '</div>';
    }
    html += '</div>';

    if (!lanes.length) {
        html += '<div style="text-align:center;color:var(--cal-text-muted);font-size:.82rem;padding:46px 0;">'
              + (IS_RTL ? 'لا يوجد مقدمو خدمة' : 'No service providers') + '</div></div>';
        document.getElementById('sf-grid').innerHTML = html;
        return;
    }

    /* ── Employee lanes ── */
    lanes.forEach(function(emp, li){
        var col = SF_EMP_COLORS[li % SF_EMP_COLORS.length];
        var empAppts = appts.filter(function(a){
            return String(a.employeeId == null ? 0 : a.employeeId) === String(emp.id);
        });
        html += '<div class="sf-rg-lane" style="grid-template-columns:' + tpl + ';">';
        /* lane header: avatar + name + range count (click filters like the rail) */
        html += '<div class="sf-rg-emp sf-rail-emp' + (sfTeamAllows(emp.id) ? ' active' : '') + '" data-emp="' + emp.id + '" title="' + _esc(emp.name) + '">'
            + (emp.image
                ? '<img src="' + emp.image + '" class="av" style="border-color:' + col + '66;">'
                : '<span class="av" style="background:' + col + ';border-color:' + col + '66;">' + _esc(emp.initials || '') + '</span>')
            + '<span class="nm">' + _esc(emp.name) + '</span>'
            + (empAppts.length
                ? '<span class="cnt">' + empAppts.length + ' ' + (IS_RTL ? 'موعد' : 'appt') + '</span>'
                : '')
            + '</div>';
        /* day cells */
        for (var d = 0; d < st.n; d++) {
            var day2 = sfAddDays(st.start, d);
            var cellAppts = empAppts.filter(function(a){ return sfSameDay(a.start, day2); })
                .sort(function(a,b){ return a.start - b.start; });
            html += '<div class="sf-rg-cell' + (sfIsToday(day2) ? ' today' : '') + '" data-date="' + sfDateStr(day2) + '" data-emp="' + emp.id + '">';
            cellAppts.forEach(function(a, ci){
                sfChipMap[a.id] = a;
                var tRange = _fmt24(a.start) + (a.end ? ' – ' + _fmt24(a.end) : '');
                html += '<div class="sf-rg-chip" data-aid="' + a.id + '" style="' + sfChipBg(a.color)
                    + 'border-inline-start-color:' + a.color + ';animation-delay:' + Math.min(ci * 35, 240) + 'ms;">'
                    + '<span class="ct">' + tRange + '</span><span class="cn">' + _esc(a.customer) + '</span></div>';
            });
            /* hover hint for quick navigation to that day */
            html += '<div class="sf-rg-add">+</div>';
            html += '</div>';
        }
        html += '</div>';
    });

    html += '</div>';
    document.getElementById('sf-grid').innerHTML = html;
    document.getElementById('sf-grid-wrap').scrollTop = 0;
}

/* ── MONTH VIEW ── */
function loadMonthView() {
    var y = sfDate.getFullYear(), m = sfDate.getMonth();
    var first = new Date(y, m, 1);
    var gridStart = sfStartOfWeek(first);
    var daysInMonth = new Date(y, m + 1, 0).getDate();
    var lead = (first.getDay() - SF_FD + 7) % 7;
    var weeks = Math.ceil((lead + daysInMonth) / 7);
    document.getElementById('sf-title').textContent = (IS_RTL ? MON_AR[m] : MON_EN[m]) + ' ' + y;
    document.getElementById('sf-subtitle').textContent = VIEW_LABELS.month;

    sfFetchEvents(gridStart, sfAddDays(gridStart, weeks * 7), function(appts){
        sfLastEvents = { type: 'month', gridStart: gridStart, weeks: weeks, month: m, appts: appts };
        renderMonthGrid();
    });
}
function renderMonthGrid() {
    var st = sfLastEvents;
    if (!st || st.type !== 'month') return;
    var appts = st.appts.filter(function(a){
        return activeStatuses.includes(a.status) && sfTeamAllows(a.employeeId);
    });
    sfChipMap = {};
    var byDay = {};
    appts.forEach(function(a){
        var k = sfDateStr(a.start);
        (byDay[k] = byDay[k] || []).push(a);
        sfChipMap[a.id] = a;
    });
    Object.keys(byDay).forEach(function(k){ byDay[k].sort(function(a,b){ return a.start - b.start; }); });

    var grid = document.getElementById('sf-grid');
    grid.style.minWidth = '0';
    var html = '<div style="width:100%;">';
    /* day-of-week header */
    html += '<div class="sf-mn-dows">';
    for (var d = 0; d < 7; d++) {
        var di = (SF_FD + d) % 7;
        html += '<div class="sf-mn-dow">' + (IS_RTL ? DAY_AR[di] : DAY_EN[di]) + '</div>';
    }
    html += '</div><div class="sf-mn-grid">';
    for (var w = 0; w < st.weeks; w++) {
        for (var c = 0; c < 7; c++) {
            var day = sfAddDays(st.gridStart, w * 7 + c);
            var k2 = sfDateStr(day);
            var list = byDay[k2] || [];
            var cls = 'sf-mn-cell'
                + (day.getMonth() !== st.month ? ' other' : '')
                + (sfIsToday(day) ? ' today' : '');
            html += '<div class="' + cls + '" data-date="' + k2 + '">'
                  + '<div class="sf-mn-num">' + day.getDate() + '</div>';
            list.slice(0, 3).forEach(function(a){
                html += '<div class="sf-mn-chip sf-chip-click" data-aid="' + a.id + '" style="' + sfChipBg(a.color) + '">'
                      + '<span class="ct">' + _fmt24(a.start) + '</span><span class="cn">' + _esc(a.customer) + '</span></div>';
            });
            if (list.length > 3) {
                html += '<div class="sf-mn-more">+' + (list.length - 3) + ' ' + (IS_RTL ? 'المزيد' : 'more') + '</div>';
            }
            html += '</div>';
        }
    }
    html += '</div></div>';
    grid.innerHTML = html;
    document.getElementById('sf-grid-wrap').scrollTop = 0;
}

/* ── Day popup (month view) — Fresha style ── */
var sfDayPop = document.createElement('div');
sfDayPop.id = 'sf-daypop';
sfDayPop.className = 'd-none';
document.body.appendChild(sfDayPop);

function sfOpenDayPop(dateStr, ev) {
    var day = new Date(dateStr + 'T00:00:00');
    var appts = Object.values(sfChipMap).filter(function(a){ return sfDateStr(a.start) === dateStr; })
        .sort(function(a,b){ return a.start - b.start; });
    var html = '<div class="dp-hdr"><span>' + sfFmtTitle(day) + '</span><button class="dp-close">✕</button></div>';
    if (!appts.length) {
        html += '<div style="text-align:center;color:var(--cal-text-muted);font-size:.74rem;padding:14px 0;">'
              + (IS_RTL ? 'لا توجد مواعيد' : 'No appointments') + '</div>';
    }
    appts.forEach(function(a){
        html += '<div class="sf-chip sf-chip-click" data-aid="' + a.id + '" style="' + sfChipBg(a.color) + '">'
              + '<span class="ct">' + _fmt24(a.start) + '</span><span class="cn">' + _esc(a.customer) + '</span></div>';
    });
    html += '<button class="dp-open" data-date="' + dateStr + '">' + (IS_RTL ? 'فتح عرض اليوم' : 'Open day view') + '</button>';
    sfDayPop.innerHTML = html;
    sfDayPop.classList.remove('d-none');
    var pw = 250, phh = Math.min(340, 90 + appts.length * 28);
    var left = Math.min(Math.max(10, ev.clientX - pw / 2), window.innerWidth - pw - 10);
    var top  = Math.min(ev.clientY + 10, window.innerHeight - phh - 10);
    sfDayPop.style.left = left + 'px';
    sfDayPop.style.top  = top + 'px';
}
sfDayPop.addEventListener('click', function(e){
    e.stopPropagation();
    if (e.target.closest('.dp-close')) { sfDayPop.classList.add('d-none'); return; }
    var openBtn = e.target.closest('.dp-open');
    if (openBtn) {
        sfDate = new Date(openBtn.dataset.date + 'T00:00:00');
        sfView = 'day';
        localStorage.setItem('bk_sf_view', 'day');
        sfDayPop.classList.add('d-none');
        loadStaffView();
        return;
    }
    var chip = e.target.closest('.sf-chip-click');
    if (chip) sfShowChipPopup(chip.dataset.aid, e);
});
document.addEventListener('click', function(e){
    if (!sfDayPop.classList.contains('d-none') && !sfDayPop.contains(e.target) && !e.target.closest('.sf-mn-cell')) {
        sfDayPop.classList.add('d-none');
    }
});

function sfShowChipPopup(aid, e) {
    if (!sfChipMap[aid]) return;
    apOpen(aid);
}

/* ── View switcher menu ── */
var sfViewMenu = document.getElementById('sf-view-menu');
sfViewMenu.innerHTML = Object.keys(VIEW_LABELS).map(function(k){
    return '<div class="sf-mrow" data-view="' + k + '">' + VIEW_LABELS[k] + '</div>';
}).join('');
document.getElementById('sf-view-btn').addEventListener('click', function(e){
    e.stopPropagation();
    sfCloseAllPops('sf-view-menu');
    sfViewMenu.classList.toggle('d-none');
    if (!sfViewMenu.classList.contains('d-none')) sfPositionPop(sfViewMenu, this);
});
sfViewMenu.addEventListener('click', function(e){
    var row = e.target.closest('.sf-mrow');
    if (!row) return;
    sfView = row.dataset.view;
    localStorage.setItem('bk_sf_view', sfView);
    sfViewMenu.classList.add('d-none');
    loadStaffView();
});

/* ── Team filter ── */
function sfEnsureStaff(cb) {
    if (sfStaffList.length) return cb();
    var p = new URLSearchParams({ date: sfDateStr(new Date()) });
    if (activeBranch) p.set('branch_id', activeBranch);
    fetch(STAFF_URL + '?' + p, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(data){ sfStaffList = data.staff || []; cb(); })
        .catch(function(){ cb(); });
}
function sfRenderTeamMenu() {
    var q = (document.getElementById('sf-team-search').value || '').trim().toLowerCase();
    var rows = sfStaffList.filter(function(s){ return !q || (s.name || '').toLowerCase().indexOf(q) >= 0; });
    var html = '<div class="sf-mrow" data-team="all">'
        + '<span style="font-weight:800;">' + (IS_RTL ? 'جميع أعضاء الفريق' : 'All team members') + '</span>'
        + '<input type="checkbox" ' + (!sfTeamSel ? 'checked' : '') + '></div>';
    rows.forEach(function(s, i){
        var col = SF_EMP_COLORS[i % SF_EMP_COLORS.length];
        html += '<div class="sf-mrow" data-team="' + s.id + '">'
            + (s.image
                ? '<img src="' + s.image + '" class="mr-av" style="object-fit:cover;">'
                : '<span class="mr-av" style="background:' + col + ';">' + _esc(s.initials || '') + '</span>')
            + '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + _esc(s.name) + '</span>'
            + '<input type="checkbox" ' + (sfTeamAllows(s.id) ? 'checked' : '') + '></div>';
    });
    document.getElementById('sf-team-list').innerHTML = html;
}
function sfApplyTeamFilter() {
    if (!document.getElementById('sf-team-menu').classList.contains('d-none')) sfRenderTeamMenu();
    var lbl = document.getElementById('sf-team-label');
    lbl.textContent = !sfTeamSel
        ? (IS_RTL ? 'جميع الفريق' : 'All team')
        : sfTeamSel.length + ' ' + (IS_RTL ? 'من الفريق' : 'selected');
    /* re-render current view from cache when possible */
    if (sfView === 'day') {
        if (sfDayData) renderStaffGrid(sfDayData); else loadDayView();
    } else if (sfLastEvents && sfLastEvents.type === 'range' && sfView !== 'month') {
        renderRangeGrid();
    } else if (sfLastEvents && sfLastEvents.type === 'month' && sfView === 'month') {
        renderMonthGrid();
    } else {
        loadStaffView();
    }
}
document.getElementById('sf-team-btn').addEventListener('click', function(e){
    e.stopPropagation();
    sfCloseAllPops('sf-team-menu');
    var menu = document.getElementById('sf-team-menu');
    if (menu.classList.contains('d-none')) {
        sfEnsureStaff(function(){ sfRenderTeamMenu(); });
        menu.classList.remove('d-none');
        sfPositionPop(menu, this);
    } else {
        menu.classList.add('d-none');
    }
});
document.getElementById('sf-team-search').addEventListener('input', sfRenderTeamMenu);
document.getElementById('sf-team-list').addEventListener('click', function(e){
    var row = e.target.closest('.sf-mrow');
    if (!row) return;
    var allIds = sfStaffList.map(function(s){ return String(s.id); });
    if (row.dataset.team === 'all') {
        sfTeamSel = null;
    } else {
        var id = String(row.dataset.team);
        if (!sfTeamSel) {
            sfTeamSel = allIds.filter(function(x){ return x !== id; });
        } else if (sfTeamSel.indexOf(id) >= 0) {
            sfTeamSel = sfTeamSel.filter(function(x){ return x !== id; });
        } else {
            sfTeamSel.push(id);
        }
        if (sfTeamSel.length === allIds.length) sfTeamSel = null;
    }
    sfRenderTeamMenu();
    sfApplyTeamFilter();
});

/* ── Date picker (click on the title) ── */
var dpBase = new Date();
function sfBuildMiniMonth(y, m, extraCls) {
    var first = new Date(y, m, 1);
    var days  = new Date(y, m + 1, 0).getDate();
    var lead  = (first.getDay() - SF_FD + 7) % 7;
    var html  = '<div class="sf-dp-m ' + (extraCls || '') + '">'
        + '<div class="sf-dp-mtitle">' + (IS_RTL ? MON_AR[m] : MON_EN[m]) + ' ' + y + '</div>'
        + '<div class="sf-dp-grid">';
    for (var d = 0; d < 7; d++) {
        var di = (SF_FD + d) % 7;
        html += '<div class="sf-dp-dow">' + (IS_RTL ? DAY_S_AR[di] : DAY_S_EN[di]).slice(0,3) + '</div>';
    }
    for (var e2 = 0; e2 < lead; e2++) html += '<div class="sf-dp-day empty"></div>';
    for (var dd = 1; dd <= days; dd++) {
        var cur = new Date(y, m, dd);
        var cls = 'sf-dp-day' + (sfIsToday(cur) ? ' today' : '') + (sfSameDay(cur, sfDate) ? ' selected' : '');
        html += '<div class="' + cls + '" data-date="' + sfDateStr(cur) + '">' + dd + '</div>';
    }
    return html + '</div></div>';
}
function sfRenderDatePick() {
    var y1 = dpBase.getFullYear(), m1 = dpBase.getMonth();
    var d2 = new Date(y1, m1 + 1, 1);
    var wk = IS_RTL ? 'أسبوع' : 'week';
    var html = '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">'
        + '<button class="sf-dp-nav" id="sf-dp-prev">‹</button>'
        + '<button class="sf-dp-nav" id="sf-dp-next">›</button>'
        + '</div>'
        + '<div class="sf-dp-months">'
        + sfBuildMiniMonth(y1, m1, 'sf-dp-m1')
        + sfBuildMiniMonth(d2.getFullYear(), d2.getMonth(), 'sf-dp-m2')
        + '</div>'
        + '<div class="sf-dp-quick">'
        + '<button data-w="0">'  + (IS_RTL ? 'اليوم' : 'Today') + '</button>'
        + '<button data-w="1">'  + (IS_RTL ? 'بعد أسبوع 1'   : 'In 1 week')  + '</button>'
        + '<button data-w="2">'  + (IS_RTL ? 'بعد أسبوعين 2' : 'In 2 weeks') + '</button>'
        + '<button data-w="3">'  + (IS_RTL ? 'بعد 3 أسابيع'  : 'In 3 weeks') + '</button>'
        + '<button data-w="4">'  + (IS_RTL ? 'بعد 4 أسابيع'  : 'In 4 weeks') + '</button>'
        + '</div>';
    document.getElementById('sf-datepick').innerHTML = html;
}
document.getElementById('sf-title-wrap').addEventListener('click', function(e){
    e.stopPropagation();
    sfCloseAllPops('sf-datepick');
    var dp = document.getElementById('sf-datepick');
    if (dp.classList.contains('d-none')) {
        dpBase = new Date(sfDate.getFullYear(), sfDate.getMonth(), 1);
        sfRenderDatePick();
        dp.classList.remove('d-none');
        sfPositionPop(dp, this);
    } else {
        dp.classList.add('d-none');
    }
});
document.getElementById('sf-datepick').addEventListener('click', function(e){
    e.stopPropagation();
    if (e.target.closest('#sf-dp-prev')) { dpBase.setMonth(dpBase.getMonth() - 1); sfRenderDatePick(); return; }
    if (e.target.closest('#sf-dp-next')) { dpBase.setMonth(dpBase.getMonth() + 1); sfRenderDatePick(); return; }
    var qk = e.target.closest('[data-w]');
    if (qk) {
        sfDate = sfAddDays(new Date(), parseInt(qk.dataset.w, 10) * 7);
        document.getElementById('sf-datepick').classList.add('d-none');
        loadStaffView();
        return;
    }
    var dayEl = e.target.closest('.sf-dp-day[data-date]');
    if (dayEl) {
        sfDate = new Date(dayEl.dataset.date + 'T00:00:00');
        document.getElementById('sf-datepick').classList.add('d-none');
        loadStaffView();
    }
});

/* on mobile the popovers are fixed sheets — anchor them right under their button */
function sfPositionPop(pop, btn) {
    if (window.innerWidth > 768) { pop.style.top = ''; return; }
    var r = btn.getBoundingClientRect();
    pop.style.top = Math.min(r.bottom + 8, window.innerHeight * 0.45) + 'px';
}

/* close popovers on outside click */
function sfCloseAllPops(except) {
    ['sf-view-menu', 'sf-team-menu', 'sf-datepick'].forEach(function(id){
        if (id !== except) document.getElementById(id).classList.add('d-none');
    });
}
document.addEventListener('click', function(e){
    if (!e.target.closest('.sf-popwrap')) sfCloseAllPops();
});

function loadDayView() {
    document.getElementById('sf-title').textContent = sfIsToday(sfDate)
        ? (IS_RTL ? 'اليوم' : 'Today')
        : sfFmtTitle(sfDate);
    document.getElementById('sf-subtitle').textContent = sfFmtTitle(sfDate);
    var grid = document.getElementById('sf-grid');
    grid.innerHTML = '<div class="text-center py-5 w-100" style="color:var(--cal-text-muted);"><div class="spinner-border spinner-border-sm"></div></div>';

    var p = new URLSearchParams({ date: sfDateStr(sfDate) });
    if (activeBranch) p.set('branch_id', activeBranch);

    bkFetch('staffGrid', STAFF_URL + '?' + p)
        .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(function(data) { renderStaffGrid(data); })
        .catch(function(err) {
            if (bkAborted(err)) return;
            grid.innerHTML = '<div class="text-center py-5 w-100" style="color:#ef4444;">⚠ ' + err.message + '</div>';
        });
}

function renderStaffGrid(data, keepScroll) {
    var _prevScroll = keepScroll ? (document.getElementById('sf-grid-wrap').scrollTop || 0) : null;
    sfDayData = data;
    if (data.staff && data.staff.length) sfStaffList = data.staff;
    var staff = (data.staff || []).filter(function(s){ return sfTeamAllows(s.id); });
    var appts = (data.appointments || []).filter(function(a){ return activeStatuses.includes(a.status); });

    /* ── Compute visible hour range from staff working hours ── */
    var minHour = 24, maxHour = 0;
    staff.forEach(function(emp) {
        if (emp.closedSlots && emp.closedSlots.length) {
            /* infer open range: before first closed and after last closed */
            var allMins = emp.closedSlots.map(function(cs){ return [cs.from, cs.to]; }).flat();
            var empMin  = Math.min.apply(null, allMins) / 60;
            var empMax  = Math.max.apply(null, allMins) / 60;
            /* working hours = between the closed zones (simplification: just use boundaries) */
            if (empMin < minHour) minHour = empMin;
            if (empMax > maxHour) maxHour = empMax;
        }
    });
    /* fallback: if no closed slots info, show 8 AM – 10 PM */
    if (minHour >= maxHour) { minHour = 8; maxHour = 22; }
    /* pad slightly + clamp */
    minHour = Math.max(0,  Math.floor(minHour));
    maxHour = Math.min(24, Math.ceil(maxHour));

    var HOUR_S_LOCAL = minHour;
    var HOUR_E_LOCAL = maxHour;
    sfHourStart = HOUR_S_LOCAL;
    if (data.defaultBranchId) sfDefaultBranch = data.defaultBranchId;
    var totalH  = HOUR_E_LOCAL - HOUR_S_LOCAL;
    var totalPx = totalH * SLOT_H;
    var html = '';

    /* ── Responsive sizing (Fresha-style compact mobile) ── */
    var isMobile = window.innerWidth <= 768;
    var TIME_W   = isMobile ? 46 : 52;   /* time column width */
    var HDR_H    = isMobile ? 76 : 96;   /* employee header height (matches CSS) */
    var COL_MIN  = isMobile ? 104 : 145; /* min employee column width */
    document.getElementById('sf-grid').style.minWidth = isMobile ? '0' : '600px';

    /* Fresha mobile behavior: N whole columns fit the screen, time axis always pinned,
       swipe horizontally (snap per employee) to reach the rest */
    var wrapEl = document.getElementById('sf-grid-wrap');
    var colW = null;
    if (isMobile && staff.length) {
        var wrapW   = wrapEl.clientWidth || window.innerWidth;
        var visCols = Math.max(1, Math.min(staff.length, Math.floor((wrapW - TIME_W) / 112)));
        colW = Math.floor((wrapW - TIME_W - 2) / visCols);
    }
    wrapEl.style.scrollSnapType     = isMobile ? 'x proximity' : '';
    wrapEl.style.scrollPaddingInline = isMobile ? TIME_W + 'px' : '';

    /* ── Time column: hour label + silent sub-tick lines ── */
    /* Each hour = SLOT_H px, split into 4 × (SLOT_H/4) segments for :00/:15/:30/:45 */
    var Q = SLOT_H / 4; /* quarter-hour height in px */
    html += '<div style="flex-shrink:0;width:' + TIME_W + 'px;border-' + (IS_RTL?'left':'right') + ':1px solid var(--cal-border);background:var(--cal-hdr-bg);position:sticky;'
          + (IS_RTL ? 'right' : 'left') + ':0;z-index:6;'
          + (isMobile ? 'box-shadow:0 0 14px rgba(0,0,0,.4);' : '')
          + '">';
    html += '<div style="height:' + HDR_H + 'px;border-bottom:1px solid var(--cal-border);"></div>';
    for (var h = HOUR_S_LOCAL; h < HOUR_E_LOCAL; h++) {
        if (isMobile) {
            /* Fresha mobile: 24h labels on every quarter (or :00/:30 when zoomed out) */
            var hh24  = String(h).padStart(2, '0');
            var qLbls = ['00','15','30','45'];
            for (var qi = 0; qi < 4; qi++) {
                var showLbl = qi === 0 || qi === 2 || Q >= 16;
                var bTop = qi === 0 ? '1px solid var(--cal-border)'
                         : qi === 2 ? '1px dashed var(--cal-border)'
                         : '1px solid var(--cal-border2)';
                html += '<div style="height:' + Q + 'px;position:relative;border-top:' + bTop + ';">'
                    + (showLbl
                        ? '<span style="position:absolute;top:2px;left:0;right:0;text-align:center;'
                          + 'font-size:.55rem;font-weight:' + (qi === 0 ? '800' : '600') + ';'
                          + 'color:var(--cal-text-muted);direction:ltr;">' + hh24 + ':' + qLbls[qi] + '</span>'
                        : '')
                    + '</div>';
            }
        } else {
            var h12 = h % 12 || 12;
            var ap  = h < 12 ? (IS_RTL?'ص':'AM') : (IS_RTL?'م':'PM');
            /* :00 — hour label spans full Q, vertically centred */
            html += '<div style="height:' + Q + 'px;position:relative;border-top:1px solid var(--cal-border);">'
                  + '<span style="position:absolute;top:3px;' + (IS_RTL?'left':'right') + ':6px;'
                  + 'font-size:.62rem;font-weight:700;color:var(--cal-text-muted);line-height:1.15;text-align:' + (IS_RTL?'left':'right') + ';">'
                  + h12 + '<small style="font-size:.48rem;display:block;margin-top:1px;">' + ap + '</small></span>'
                  + '</div>';
            /* :15 — just a subtle line, no text */
            html += '<div style="height:' + Q + 'px;border-top:1px solid var(--cal-border2);"></div>';
            /* :30 — slightly more visible dashed, small "30" label */
            html += '<div style="height:' + Q + 'px;position:relative;border-top:1px dashed var(--cal-border);">'
                  + '<span style="position:absolute;top:2px;' + (IS_RTL?'left':'right') + ':6px;'
                  + 'font-size:.45rem;color:var(--cal-text-muted);opacity:.6;">30</span>'
                  + '</div>';
            /* :45 — subtle line, no text */
            html += '<div style="height:' + Q + 'px;border-top:1px solid var(--cal-border2);"></div>';
        }
    }
    /* closing hour border */
    html += '<div style="height:0;border-top:1px solid var(--cal-border);"></div>';
    html += '</div>';

    /* ── Employee columns ── */
    if (staff.length === 0) {
        html += '<div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--cal-text-muted);font-size:.85rem;padding:40px;">'
              + (IS_RTL ? 'لا يوجد موظفون في هذا اليوم' : 'No staff found for this day') + '</div>';
    }

    staff.forEach(function(emp, idx) {
        var eColor   = SF_EMP_COLORS[idx % SF_EMP_COLORS.length];
        var empAppts = appts.filter(function(a){ return a.employeeId === emp.id; });

        html += '<div style="' + (colW
                    ? 'flex:0 0 ' + colW + 'px;width:' + colW + 'px;scroll-snap-align:start;'
                    : 'flex:1;min-width:' + COL_MIN + 'px;')
              + 'border-' + (IS_RTL?'left':'right') + ':1px solid var(--cal-border);position:relative;">';

        /* ── Employee header (Fresha style: big avatar + colored ring + clear name) ── */
        var ring = 'box-shadow:0 0 0 2.5px var(--cal-hdr-bg), 0 0 0 5px ' + eColor + ';';
        html += '<div class="sf-emp-header">';
        if (emp.image) {
            html += '<img src="' + emp.image + '" class="sf-emp-av" style="' + ring + '" loading="lazy">';
        } else {
            html += '<div class="sf-emp-av" style="background:' + eColor + ';' + ring + '">'
                  + _esc(emp.initials) + '</div>';
        }
        html += '<div class="sf-emp-name" title="' + _esc(emp.name) + '">' + _esc(emp.name) + '</div>';
        html += '</div>';

        /* ── Grid body ── */
        html += '<div class="sf-col-body" data-emp-id="' + emp.id + '" data-emp-name="' + _esc(emp.name) + '" data-branch-id="' + (emp.branchId || '') + '" style="position:relative;height:' + totalPx + 'px;">';

        /* Grid lines: :00 solid, :30 dashed, :15/:45 faint — plus 5-min micro-ticks
           (05,10,20,25...) when zoomed enough, so 4:05 is visually targetable */
        var show5 = SLOT_H >= 64; /* 5-min lines need breathing room */
        var Pm = SLOT_H / 60;     /* px per minute */
        for (var hh = 0; hh < totalH; hh++) {
            var bt = hh * SLOT_H;
            for (var mm5 = 0; mm5 < 60; mm5 += 5) {
                var lineTop = bt + mm5 * Pm;
                var st;
                if (mm5 === 0)       st = 'border-top:1px solid var(--cal-border);';
                else if (mm5 === 30) st = 'border-top:1px dashed var(--cal-border);';
                else if (mm5 % 15 === 0) st = 'border-top:1px solid var(--cal-border2);';
                else if (show5)      st = 'border-top:1px dotted var(--cal-border2);opacity:.55;';
                else continue;
                html += '<div style="position:absolute;left:0;right:0;top:' + lineTop + 'px;' + st + 'pointer-events:none;z-index:1;"></div>';
            }
        }
        html += '<div style="position:absolute;left:0;right:0;top:' + totalPx + 'px;border-top:1px solid var(--cal-border);pointer-events:none;z-index:1;"></div>';

        /* ── Closed / outside-hours — Booksy-style diagonal stripes ── */
        if (emp.closedSlots) {
            emp.closedSlots.forEach(function(cs) {
                var topPx = (cs.from - HOUR_S_LOCAL * 60) * (SLOT_H / 60);
                var htPx  = (cs.to - cs.from) * (SLOT_H / 60);
                if (topPx < 0) { htPx += topPx; topPx = 0; }
                if (htPx > 0 && topPx < totalPx) {
                    html += '<div class="sf-closed-zone" style="top:' + topPx + 'px;height:' + htPx + 'px;"></div>';
                }
            });
        }

        /* ── Appointment blocks ── */
        empAppts.forEach(function(a) {
            var startD = a.startIso ? new Date(a.startIso) : null;
            var endD   = a.endIso   ? new Date(a.endIso)   : null;
            if (!startD) return;
            var startMin = startD.getHours() * 60 + startD.getMinutes();
            var endMin   = endD ? (endD.getHours() * 60 + endD.getMinutes()) : startMin + 30;
            a.startLabel = startD.toLocaleTimeString(IS_RTL?'ar-SA':'en-US',{hour:'2-digit',minute:'2-digit',hour12:true});
            a.endLabel   = endD ? endD.toLocaleTimeString(IS_RTL?'ar-SA':'en-US',{hour:'2-digit',minute:'2-digit',hour12:true}) : '';
            var topMin = startMin - HOUR_S_LOCAL * 60;
            var durMin = Math.max(endMin - startMin, 15); /* min 15min height so label is readable */
            var topPx2 = topMin * (SLOT_H / 60);
            var htPx2  = durMin * (SLOT_H / 60);
            if (topPx2 < 0 || topPx2 >= totalPx) return;
            htPx2 = Math.min(htPx2, totalPx - topPx2);

            var dataAttr = 'data-appt=\'' + JSON.stringify(a).replace(/\\/g,'\\\\').replace(/'/g,'&#39;') + '\'';

            /* Status color: fully opaque bg, white text, solid left accent */
            var timeLabel = a.startLabel + ' – ' + a.endLabel;

            html += '<div class="sf-appt-block" ' + dataAttr + ' onclick="sfShowPopup(this,event)"'
                  + ' data-id="' + a.id + '" data-dur="' + durMin + '" data-color="' + a.color + '"'
                  + ' style="position:absolute;left:2px;right:2px;top:' + topPx2 + 'px;height:' + (htPx2 - 2) + 'px;'
                  + 'background:' + a.color + ';border-radius:7px;padding:4px 7px 4px 9px;cursor:pointer;'
                  + 'box-shadow:0 2px 10px ' + a.color + '55;overflow:hidden;z-index:2;'
                  + 'border-left:3px solid rgba(255,255,255,.45);'
                  + 'display:flex;flex-direction:column;justify-content:center;">';

            /* ── Block content — always show time + name + service ── */
            /* Row 1: time range */
            html += '<div style="font-size:.55rem;font-weight:600;color:rgba(255,255,255,.82);'
                  + 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.25;margin-bottom:1px;">'
                  + timeLabel + '</div>';
            /* Row 2: customer name */
            html += '<div style="font-size:.72rem;font-weight:800;color:#fff;'
                  + 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3;">'
                  + _esc(a.customer) + '</div>';
            /* Row 3: service — shown unless block is too short */
            if (htPx2 >= 46) {
                html += '<div style="font-size:.61rem;color:rgba(255,255,255,.88);'
                      + 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3;margin-top:1px;">'
                      + _esc(a.service) + '</div>';
            }
            /* resize handle (drag bottom edge to change duration) */
            html += '<div class="sf-rz"></div>';
            html += '</div>';
        });

        /* ── Now indicator ── */
        if (sfIsToday(sfDate)) {
            var now2    = new Date();
            var nowMin2 = now2.getHours() * 60 + now2.getMinutes() - HOUR_S_LOCAL * 60;
            if (nowMin2 > 0 && nowMin2 < totalH * 60) {
                var nowPx2 = nowMin2 * (SLOT_H / 60);
                html += '<div style="position:absolute;left:0;right:0;top:' + nowPx2 + 'px;height:2px;'
                      + 'background:var(--cal-now-color);z-index:4;box-shadow:0 0 5px var(--cal-now-color);pointer-events:none;">'
                      + '<div style="position:absolute;left:-4px;top:-4px;width:10px;height:10px;border-radius:50%;background:var(--cal-now-color);"></div>'
                      + '</div>';
            }
        }

        html += '</div></div>';
    });

    document.getElementById('sf-grid').innerHTML = html;

    /* Update subtitle with business hours range */
    var subEl = document.getElementById('sf-subtitle');
    if (subEl) {
        var rLabel = _fmtMinutes(HOUR_S_LOCAL * 60) + ' – ' + _fmtMinutes(HOUR_E_LOCAL * 60);
        if (sfIsToday(sfDate)) {
            subEl.textContent = rLabel;
        } else {
            subEl.textContent = sfFmtTitle(sfDate);
        }
    }

    /* Scroll to business start or current time (or keep position on optimistic re-renders) */
    var container = document.getElementById('sf-grid-wrap');
    if (_prevScroll !== null) {
        container.scrollTop = _prevScroll;
    } else {
        var scrollTo = 0;
        if (sfIsToday(sfDate)) {
            var cur = new Date();
            scrollTo = Math.max(0, (cur.getHours() - HOUR_S_LOCAL - 1) * SLOT_H);
        }
        container.scrollTop = scrollTo;
    }
}

/* Day-view block click → full detail/checkout drawer (Fresha style) */
window.sfShowPopup = function(el, ev) {
    ev.stopPropagation();
    try {
        var a = JSON.parse(el.dataset.appt.replace(/&#39;/g,"'"));
        apOpen(a.id);
    } catch(e) { console.error(e); }
};

/* Nav buttons — step depends on the active view */
function sfNav(dir) {
    if (sfView === 'day')        sfDate.setDate(sfDate.getDate() + dir);
    else if (sfView === '3days') sfDate.setDate(sfDate.getDate() + 3 * dir);
    else if (sfView === 'week')  sfDate.setDate(sfDate.getDate() + 7 * dir);
    else                         sfDate.setMonth(sfDate.getMonth() + dir);
    loadStaffView();
}
document.getElementById('sf-prev').addEventListener('click', function(){ sfNav(-1); });
document.getElementById('sf-next').addEventListener('click', function(){ sfNav(1); });
document.getElementById('sf-today').addEventListener('click', function(){
    sfDate = new Date(); loadStaffView();
});

/* ════════════════════════════════
   CALENDAR SETTINGS DRAWER
════════════════════════════════ */
var sfOverlay = document.getElementById('sf-settings-overlay');
var sfDrawer  = document.getElementById('sf-settings-drawer');

function sfOpenSettings() {
    document.getElementById('sf-zoom-range').value = SLOT_H;
    document.getElementById('sf-quick-toggle').checked = sfQuickActions;
    sfOverlay.classList.remove('d-none');
    requestAnimationFrame(function(){
        sfOverlay.classList.add('show');
        sfDrawer.classList.add('show');
    });
}
function sfCloseSettings() {
    sfOverlay.classList.remove('show');
    sfDrawer.classList.remove('show');
    setTimeout(function(){ sfOverlay.classList.add('d-none'); }, 220);
}
document.getElementById('sf-settings-btn').addEventListener('click', sfOpenSettings);
document.getElementById('sf-settings-close').addEventListener('click', sfCloseSettings);
sfOverlay.addEventListener('click', sfCloseSettings);
document.getElementById('sf-settings-apply').addEventListener('click', function(){
    SLOT_H         = parseInt(document.getElementById('sf-zoom-range').value, 10) || 80;
    sfQuickActions = document.getElementById('sf-quick-toggle').checked;
    localStorage.setItem('bk_sf_zoom',  String(SLOT_H));
    localStorage.setItem('bk_sf_quick', sfQuickActions ? '1' : '0');
    sfCloseSettings();
    loadStaffView();
});

/* ════════════════════════════════
   DRAG & DROP — move appointment between staff / times
   (toasts use the global window.bkToast from company.partials.crud-toasts)
════════════════════════════════ */
var RESCHEDULE_URL = BK.routes.appointmentsReschedule;
var CREATE_URL     = BK.routes.appointmentsCreate;

var sfGridEl   = document.getElementById('sf-grid');
var sfDrag     = null;   /* active drag state */
var sfJustDragged = false;

function sfSnapMinutes(colEl, clientY) {
    var rect = colEl.getBoundingClientRect();
    var y    = Math.max(0, Math.min(clientY - rect.top, rect.height - 1));
    var mins = sfHourStart * 60 + y / (SLOT_H / 60);
    return Math.round(mins / 5) * 5; /* 5-minute precision (Fresha-like) */
}
function sfColUnderPoint(x, y) {
    var els = document.elementsFromPoint(x, y);
    for (var i = 0; i < els.length; i++) {
        if (els[i].classList && els[i].classList.contains('sf-col-body')) return els[i];
    }
    return null;
}
function sfClearDropUI() {
    document.querySelectorAll('.sf-drop-indicator').forEach(function(el){ el.remove(); });
    document.querySelectorAll('.sf-col-body.sf-drop-target').forEach(function(el){ el.classList.remove('sf-drop-target'); });
}

/* ── Optimistic updates: no calendar loading on move/resize ── */
function sfIsoAt(dateStr, minutes) {
    var h = Math.floor(minutes / 60), m = minutes % 60;
    return dateStr + 'T' + String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':00';
}
function sfMinOfIso(iso) {
    var d = new Date(iso);
    return d.getHours() * 60 + d.getMinutes();
}
function sfUpdateCache(id, changes) {
    /* keep the cached day payload in sync (no re-render — the DOM is updated in place) */
    if (!sfDayData) return;
    (sfDayData.appointments || []).forEach(function(a){
        if (String(a.id) === String(id)) Object.assign(a, changes);
    });
}
/* confirmation modal (SweetAlert via crud-toasts partial, with native fallback) */
function sfConfirm(opts) {
    if (window.bkConfirm) return bkConfirm(opts);
    return Promise.resolve({ isConfirmed: window.confirm(opts.title + '\n' + (opts.text || '')) });
}
function sfSendReschedule(id, params, successMsg, revertFn) {
    var body = new URLSearchParams(Object.assign({ _method: 'PATCH', _token: CSRF }, params));
    fetch(RESCHEDULE_URL.replace('__ID__', id), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
    })
    .then(function(r){
        /* read the body even on 4xx so server conflict messages reach the toast */
        return r.json().catch(function(){ return null; }).then(function(json){
            if (!r.ok || !json || !json.ok) {
                var err = new Error('fail');
                err.serverMessage = json && json.message;
                throw err;
            }
            return json;
        });
    })
    .then(function(json){
        bkToast(successMsg, 'warning');
        listLoaded = false;
        calRefetch();
    })
    .catch(function(err){
        bkToast((err && err.serverMessage) ||
            (IS_RTL ? 'تعذّر حفظ التعديل — أُعيد الموعد لوضعه السابق' : 'Could not save — reverted'), 'error');
        /* undo the optimistic DOM move in place — no calendar reload */
        if (revertFn) revertFn(); else loadStaffView();
    });
}

/* snapshot a block's state so a failed save can restore it without reloading */
function sfBlockSnapshot(block) {
    var timeRow = block.firstElementChild;
    return {
        parent:   block.parentElement,
        top:      block.style.top,
        height:   block.style.height,
        appt:     block.dataset.appt,
        dur:      block.dataset.dur,
        timeText: timeRow ? timeRow.textContent : null,
    };
}
function sfBlockRestore(block, snap, id) {
    block.style.top    = snap.top;
    block.style.height = snap.height;
    block.dataset.appt = snap.appt;
    if (snap.dur !== undefined) block.dataset.dur = snap.dur;
    if (snap.parent && block.parentElement !== snap.parent) snap.parent.appendChild(block);
    var timeRow = block.firstElementChild;
    if (timeRow && snap.timeText !== null) timeRow.textContent = snap.timeText;
    try {
        var ap = JSON.parse(snap.appt.replace(/&#39;/g,"'"));
        sfUpdateCache(id, { employeeId: ap.employeeId, employee: ap.employee, startIso: ap.startIso, endIso: ap.endIso });
    } catch(err) {}
}

sfGridEl.addEventListener('pointerdown', function(e) {
    var block = e.target.closest('.sf-appt-block');
    if (!block || e.button !== 0) return;
    sfDrag = {
        mode:    e.target.closest('.sf-rz') ? 'resize' : 'move',
        block:   block,
        started: false,
        startX:  e.clientX,
        startY:  e.clientY,
        id:      block.dataset.id,
        dur:     parseInt(block.dataset.dur, 10) || 30,
        color:   block.dataset.color || '#0E7C82',
        target:  null,
        minutes: null,
    };
    if (sfDrag.mode === 'resize') {
        var a = {};
        try { a = JSON.parse(block.dataset.appt.replace(/&#39;/g,"'")); } catch(err) {}
        var s = a.startIso ? new Date(a.startIso) : null;
        var en = a.endIso ? new Date(a.endIso) : null;
        if (!s) { sfDrag = null; return; }
        sfDrag.startMin = s.getHours() * 60 + s.getMinutes();
        sfDrag.origDur  = en ? Math.max(5, Math.round((en - s) / 60000)) : sfDrag.dur;
        sfDrag.startStr = a.startIso.slice(0, 10) + ' ' + a.startIso.slice(11, 16) + ':00';
        sfDrag.col      = block.closest('.sf-col-body');
        sfDrag.newDur   = null;
        sfDrag.origH    = block.style.height;
    }
});

/* ── Resize (change duration by dragging the bottom edge) ── */
function sfResizeMove(e) {
    if (!sfDrag.started) {
        if (Math.abs(e.clientY - sfDrag.startY) < 3) return;
        sfDrag.started = true;
        sfDrag.block.classList.add('sf-resizing');
        var ghost = document.createElement('div');
        ghost.id = 'sf-drag-ghost';
        ghost.style.background = sfDrag.color;
        document.body.appendChild(ghost);
        sfDrag.ghost = ghost;
        document.body.style.userSelect = 'none';
    }
    e.preventDefault();
    var mins = sfSnapMinutes(sfDrag.col, e.clientY);
    var dur  = Math.max(10, mins - sfDrag.startMin);
    dur = Math.min(dur, 24 * 60 - sfDrag.startMin);
    sfDrag.newDur = dur;
    sfDrag.block.style.height = Math.max(dur * (SLOT_H / 60) - 2, 12) + 'px';
    sfDrag.ghost.textContent = _fmtMinutes(sfDrag.startMin) + ' – ' + _fmtMinutes(sfDrag.startMin + dur)
        + ' (' + dur + ' ' + (IS_RTL ? 'د' : 'min') + ')';
    sfDrag.ghost.style.left = (e.clientX + 12) + 'px';
    sfDrag.ghost.style.top  = (e.clientY + 12) + 'px';
}
function sfResizeEnd(d) {
    if (d.ghost) d.ghost.remove();
    document.body.style.userSelect = '';
    d.block.classList.remove('sf-resizing');
    if (!d.started) return;
    sfJustDragged = true;
    setTimeout(function(){ sfJustDragged = false; }, 250);

    if (!d.newDur || d.newDur === d.origDur) { d.block.style.height = d.origH; return; }

    var newEndIso = sfIsoAt(d.startStr.slice(0, 10), Math.min(d.startMin + d.newDur, 1439));

    sfConfirm({
        title:        IS_RTL ? 'تعديل مدة الموعد؟' : 'Change appointment duration?',
        text:         _fmtMinutes(d.startMin) + ' – ' + _fmtMinutes(d.startMin + d.newDur)
                      + ' (' + d.newDur + ' ' + (IS_RTL ? 'دقيقة' : 'min') + ')',
        icon:         'question',
        confirmColor: '#0E7C82',
        confirmText:  IS_RTL ? 'نعم، عدّل' : 'Yes, update',
    }).then(function(res){
        if (!res.isConfirmed) { d.block.style.height = d.origH; return; } /* restore silently */

        var snap = sfBlockSnapshot(d.block);
        snap.height = d.origH; /* height was already resized during the drag */

        /* optimistic: keep the new height, sync dataset + cache, save in background */
        try {
            var ap = JSON.parse(d.block.dataset.appt.replace(/&#39;/g,"'"));
            ap.endIso   = newEndIso;
            ap.endLabel = _fmtMinutes(d.startMin + d.newDur);
            d.block.dataset.appt = JSON.stringify(ap).replace(/\\/g,'\\\\').replace(/'/g,'&#39;');
            var timeRow = d.block.firstElementChild;
            if (timeRow) timeRow.textContent = _fmtMinutes(d.startMin) + ' – ' + _fmtMinutes(d.startMin + d.newDur);
        } catch(err) {}
        d.block.dataset.dur = d.newDur;
        sfUpdateCache(d.id, { endIso: newEndIso });

        sfSendReschedule(d.id, {
            employee_id: (parseInt(d.col.dataset.empId, 10) || 0) ? d.col.dataset.empId : '',
            start_time:  d.startStr,
            duration:    String(d.newDur),
        }, IS_RTL ? 'تم تعديل مدة الموعد بنجاح' : 'Appointment duration updated',
        function(){ sfBlockRestore(d.block, snap, d.id); });
    });
}

document.addEventListener('pointermove', function(e) {
    if (!sfDrag) return;
    if (sfDrag.mode === 'resize') { sfResizeMove(e); return; }
    if (!sfDrag.started) {
        if (Math.abs(e.clientX - sfDrag.startX) < 6 && Math.abs(e.clientY - sfDrag.startY) < 6) return;
        sfDrag.started = true;
        sfDrag.block.classList.add('sf-dragging');
        var a = {};
        try { a = JSON.parse(sfDrag.block.dataset.appt.replace(/&#39;/g,"'")); } catch(err) {}
        var ghost = document.createElement('div');
        ghost.id = 'sf-drag-ghost';
        ghost.style.background = sfDrag.color;
        ghost.innerHTML = '<div>' + _esc(a.customer || '') + '</div><div class="gh-time"></div>';
        document.body.appendChild(ghost);
        sfDrag.ghost = ghost;
        document.body.style.userSelect = 'none';
    }
    e.preventDefault();

    sfDrag.ghost.style.left = (e.clientX + 12) + 'px';
    sfDrag.ghost.style.top  = (e.clientY + 12) + 'px';

    sfClearDropUI();
    var col = sfColUnderPoint(e.clientX, e.clientY);
    sfDrag.target  = col;
    sfDrag.minutes = null;
    if (!col) return;

    var mins = sfSnapMinutes(col, e.clientY);
    sfDrag.minutes = mins;
    col.classList.add('sf-drop-target');

    var topPx = (mins - sfHourStart * 60) * (SLOT_H / 60);
    var htPx  = sfDrag.dur * (SLOT_H / 60);
    var ind = document.createElement('div');
    ind.className = 'sf-drop-indicator';
    ind.style.top    = topPx + 'px';
    ind.style.height = Math.max(htPx - 2, 14) + 'px';
    ind.textContent  = _fmtMinutes(mins);
    col.appendChild(ind);

    sfDrag.ghost.querySelector('.gh-time').textContent =
        _fmtMinutes(mins) + ' · ' + (col.dataset.empName || '');
});

document.addEventListener('pointerup', function(e) {
    if (!sfDrag) return;
    var d = sfDrag;
    sfDrag = null;
    if (d.mode === 'resize') { sfResizeEnd(d); return; }
    if (!d.started) return;

    d.block.classList.remove('sf-dragging');
    if (d.ghost) d.ghost.remove();
    document.body.style.userSelect = '';
    sfClearDropUI();
    sfJustDragged = true;
    setTimeout(function(){ sfJustDragged = false; }, 250);

    if (!d.target || d.minutes === null) return;

    var col     = d.target;
    var newTop  = (d.minutes - sfHourStart * 60) * (SLOT_H / 60);
    var origCol = d.block.parentElement;
    var origTop = parseFloat(d.block.style.top) || 0;

    /* dropped in the same place — nothing to save */
    if (col === origCol && Math.abs(newTop - origTop) < 1) return;

    var empId    = parseInt(col.dataset.empId, 10) || 0;
    var empName  = col.dataset.empName || '';
    var startLbl = _fmtMinutes(d.minutes);
    var endLbl   = _fmtMinutes(d.minutes + d.dur);
    var custName = '';
    try { custName = (JSON.parse(d.block.dataset.appt.replace(/&#39;/g,"'")).customer) || ''; } catch(err) {}

    /* ── Confirm first, then move optimistically (no reload/spinner) ── */
    sfConfirm({
        title:        IS_RTL ? 'نقل الموعد؟' : 'Move appointment?',
        text:         custName + (custName ? ' — ' : '') + empName + ' · ' + startLbl,
        icon:         'question',
        confirmColor: '#0E7C82',
        confirmText:  IS_RTL ? 'نعم، انقل' : 'Yes, move',
    }).then(function(res){
        if (!res.isConfirmed) return; /* block never moved visually — nothing to restore */

        var snap = sfBlockSnapshot(d.block);

        d.block.style.top = newTop + 'px';
        if (col !== origCol) col.appendChild(d.block);

        var newStartIso = sfIsoAt(sfDateStr(sfDate), d.minutes);
        var newEndIso   = sfIsoAt(sfDateStr(sfDate), Math.min(d.minutes + d.dur, 1439));
        try {
            var ap = JSON.parse(d.block.dataset.appt.replace(/&#39;/g,"'"));
            ap.startLabel = startLbl;
            ap.endLabel   = endLbl;
            ap.startIso   = newStartIso;
            ap.endIso     = newEndIso;
            ap.employee   = empName || ap.employee;
            ap.employeeId = empId;
            d.block.dataset.appt = JSON.stringify(ap).replace(/\\/g,'\\\\').replace(/'/g,'&#39;');
        } catch(err) {}
        var timeRow = d.block.firstElementChild;
        if (timeRow) timeRow.textContent = startLbl + ' – ' + endLbl;
        sfUpdateCache(d.id, { employeeId: empId, employee: empName, startIso: newStartIso, endIso: newEndIso });

        sfSendReschedule(d.id, {
            employee_id: empId ? String(empId) : '',
            start_time:  newStartIso.replace('T', ' '),
        }, IS_RTL ? 'تم نقل الموعد بنجاح' : 'Appointment moved successfully',
        function(){ sfBlockRestore(d.block, snap, d.id); });
    });
});

/* suppress popup click right after a drag */
sfGridEl.addEventListener('click', function(e){
    if (sfJustDragged) { e.stopPropagation(); e.preventDefault(); }
}, true);

/* ════════════════════════════════
   QUICK ACTIONS — click empty slot → add appointment (Fresha style)
════════════════════════════════ */
function sfRemoveQuickStrip() {
    document.querySelectorAll('.sf-quick-strip').forEach(function(el){ el.remove(); });
}
/* chips (range/month views) → detail popup; lane header → filter; cells → navigate */
sfGridEl.addEventListener('click', function(e) {
    var chip = e.target.closest('.sf-chip, .sf-mn-chip, .sf-rg-chip');
    if (chip && chip.dataset.aid) {
        e.stopPropagation();
        sfShowChipPopup(chip.dataset.aid, e);
        return;
    }
    var railEmp = e.target.closest('.sf-rail-emp');
    if (railEmp) {
        e.stopPropagation();
        var id = String(railEmp.dataset.emp);
        /* toggle: only this member ↔ all team */
        sfTeamSel = (sfTeamSel && sfTeamSel.length === 1 && sfTeamSel[0] === id) ? null : [id];
        sfApplyTeamFilter();
        return;
    }
    var rgCell = e.target.closest('.sf-rg-cell');
    if (rgCell) {
        e.stopPropagation();
        /* jump to that day's detailed view */
        sfDate = new Date(rgCell.dataset.date + 'T00:00:00');
        sfView = 'day';
        localStorage.setItem('bk_sf_view', 'day');
        loadStaffView();
        return;
    }
    var cell = e.target.closest('.sf-mn-cell');
    if (cell) {
        e.stopPropagation();
        sfOpenDayPop(cell.dataset.date, e);
    }
});

sfGridEl.addEventListener('click', function(e) {
    if (sfJustDragged || !sfQuickActions) return;
    if (e.target.closest('.sf-appt-block') || e.target.closest('.sf-quick-strip')) return;
    var col = e.target.closest('.sf-col-body');
    if (!col) { sfRemoveQuickStrip(); return; }

    sfRemoveQuickStrip();
    var mins  = sfSnapMinutes(col, e.clientY);
    var empId = parseInt(col.dataset.empId, 10) || 0;

    var strip = document.createElement('div');
    strip.className = 'sf-quick-strip';
    strip.style.top = ((mins - sfHourStart * 60) * (SLOT_H / 60)) + 'px';
    strip.innerHTML =
        '<span class="qs-add"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
        + (IS_RTL ? 'موعد جديد' : 'New appointment') + '</span>'
        + '<span class="qs-time">' + _fmtMinutes(mins) + '</span>';
    strip.addEventListener('click', function(ev){
        ev.stopPropagation();
        sfRemoveQuickStrip();
        var d = new Date(sfDate);
        d.setHours(0, mins, 0, 0); /* minutes-from-midnight → wall clock */
        bkQuickAdd(d, empId, col.dataset.empName || '', col.dataset.branchId || '');
    });
    col.appendChild(strip);
});
document.addEventListener('click', function(e){
    if (!e.target.closest('#sf-grid')) sfRemoveQuickStrip();
});

/* ════════════════════════════════
   QUICK ADD DRAWER — Fresha-style booking
════════════════════════════════ */
var QUICK_STORE_URL = BK.routes.appointmentsQuickStore;
var QUICK_GROUP_URL = BK.routes.appointmentsQuickGroupStore;
var BRANCH_DATA_URL = BK.routes.appointmentsBranchData;
var CUST_SEARCH_URL = BK.routes.customersSearchJson;

var qaOverlay  = document.getElementById('qa-overlay');
var qaDrawer   = document.getElementById('qa-drawer');
var qaCtx      = null;   /* {empId, empName, branchId, minutes, dateStr} */
var qaCustomer = null;   /* null=walk-in | {id,name} | {name,phone,isNew:true} */
var qaServices = [];
var qaSvcCache = {};
var qaBooking  = false;

/* ── Guests ───────────────────────────────────────────────────
   A booking is always a list of guests; a normal single booking is
   just a party of one, so the group chrome stays hidden until a
   second guest exists. qaCustomer/qaCart are live views onto the
   active guest, which is why the rest of the drawer needs no changes.
─────────────────────────────────────────────────────────────── */
var qaGuests      = [{ customer: null, cart: [] }];
var qaActiveGuest = 0;

function qaGuest()      { return qaGuests[qaActiveGuest]; }
function qaIsGroup()    { return qaGuests.length > 1; }

/* point qaCart/qaCustomer at guest i and repaint everything that depends on them */
function qaUseGuest(i) {
    qaActiveGuest = Math.max(0, Math.min(i, qaGuests.length - 1));
    qaCart        = qaGuests[qaActiveGuest].cart;
    qaCustomer    = qaGuests[qaActiveGuest].customer;
    qaEditClose();
    qaRenderGuests();
    qaRenderCart();
    qaRenderMeta();
    if (typeof qaRenderClients === 'function') qaRenderClients();
}

/* single place that writes a customer, so the guest list stays in sync */
function qaSetCustomer(c) {
    qaCustomer = c;
    if (qaGuests[qaActiveGuest]) qaGuests[qaActiveGuest].customer = c;
    qaRenderGuests();
    qaTouch(); /* autosave */
}

function qaResetGuests() {
    qaGuests      = [{ customer: null, cart: [] }];
    qaActiveGuest = 0;
    qaCart        = qaGuests[0].cart;
    qaCustomer    = null;
}

var QA_TXT = {
    walkin:    BK.t.walk_in,
    addNew:    BK.t.add_new_client,
    client:    BK.t.client,
    name:      BK.t.client_name,
    phone:     BK.t.phone_number,
    save:      BK.t.save,
    none:      BK.t.no_results,
    other:     BK.t.other_services,
    minutes:   BK.t.min,
    hourOne:   BK.t.hr,
    hourMany:  BK.t.hrs,
    bookFail:  BK.t.could_not_book_the_appointment,
    loading:   BK.t.loading,
    /* group booking */
    guestN:      BK.t.guest_n,
    guestsN:     BK.t.n_guests,
    noSvcYet:    BK.t.no_service_yet,
    removeGuest: BK.t.remove_guest,
    maxGuests:   BK.t.up_to_12_guests_per_booking,
    saveOne:     BK.t.save_appointment,
    saveGroup:   BK.t.book_n_guests,
    saving:      BK.t.saving,
    needSvc:     BK.t.give_every_guest_a_service_first,
    groupBooked: BK.t.booked_n_guests,
    /* someone shared a stylist or a machine, so they run after their party-mate */
    groupMoved:  BK.t.booked_n_guests_who_follows_the_others_s,
};

function qaOpen(ctx, opts) {
    qaCtx      = ctx;
    qaBooking  = false;
    qaResetGuests();
    qaEditClose();

    /* Only rebuild from the draft when the user explicitly resumed it. Clicking a
       fresh slot must not silently inherit an old party — the resume banner is
       how an unfinished booking comes back. */
    if (opts && opts.restore) {
        var dd = bkStoreGet(BK_DRAFT_KEY, null);
        if (dd) {
            if (dd.guests && dd.guests.length) {
                qaGuests = dd.guests;
            } else if (dd.cart && dd.cart.length) {
                qaGuests = [{ customer: dd.customer || null, cart: dd.cart }]; /* pre-group draft */
            }
            if (dd.ctx && dd.ctx.branchId) qaCtx.branchId = dd.ctx.branchId;
            else if (dd.branchId)          qaCtx.branchId = dd.branchId;
            bkToast(BK.t.unfinished_booking_restored, 'info');
        }
    }

    /* opened straight from "+ Add → Group appointment" — start as a party of two */
    if (opts && opts.group && qaGuests.length < 2) qaGuests.push({ customer: null, cart: [] });

    qaUseGuest(0);
    document.getElementById('qa-svc-search').value    = '';
    document.getElementById('qa-client-search').value = '';
    document.getElementById('qa-client-collapsed').classList.remove('d-none');
    document.getElementById('qa-client-expanded').classList.add('d-none');
    qaRenderMeta();
    document.getElementById('qa-svc-list').innerHTML =
        '<div class="qa-empty"><div class="spinner-border spinner-border-sm"></div><div class="mt-2">' + QA_TXT.loading + '</div></div>';

    /* sync branch selector with context */
    var bSel = document.getElementById('qa-branch');
    if (bSel) {
        bSel.value = String(ctx.branchId);
        if (bSel.value !== String(ctx.branchId) && bSel.options.length) {
            /* branch not in list — fall back to first option */
            bSel.selectedIndex = 0;
            qaCtx.branchId = bSel.value;
        }
    }

    qaOverlay.classList.remove('d-none');
    requestAnimationFrame(function(){
        qaOverlay.classList.add('show');
        qaDrawer.classList.add('show');
    });
    qaLoadServices(qaCtx.branchId);
    qaLoadCustomers('');
}
/* switching the branch reloads its services */
document.getElementById('qa-branch').addEventListener('change', function(){
    if (!qaCtx) return;
    qaCtx.branchId = this.value;
    /* services belong to the previous branch — clear every guest's cart in place
       so qaCart keeps pointing at the active guest's array */
    qaGuests.forEach(function(g){ g.cart.length = 0; });
    qaRenderGuests();
    qaRenderCart();
    document.getElementById('qa-svc-list').innerHTML =
        '<div class="qa-empty"><div class="spinner-border spinner-border-sm"></div><div class="mt-2">' + QA_TXT.loading + '</div></div>';
    qaLoadServices(qaCtx.branchId);
});
function qaClose() {
    qaOverlay.classList.remove('show');
    qaDrawer.classList.remove('show');
    setTimeout(function(){ qaOverlay.classList.add('d-none'); }, 220);
}
/* The work is already autosaved by now — this only decides whether to KEEP it. */
function qaDraftSave() {
    bkDraftWrite();
    bkToast(BK.t.draft_saved, 'info');
    bkResumeShow(); /* leave a way back to it */
}
function qaDraftDiscard() {
    qaResetGuests();  /* clear first, or the autosave debounce would rewrite it */
    bkDraftClear();
}
function qaAttemptClose() {
    /* anything picked by anyone in the party counts as unsaved work */
    if (!bkDraftHasWork()) { qaClose(); return; }
    if (!window.Swal) {
        if (window.confirm(BK.t.discard_sale_draft)) { qaDraftDiscard(); qaClose(); }
        else { qaDraftSave(); qaClose(); }
        return;
    }
    var light = document.documentElement.dataset.bkTheme === 'light';
    Swal.fire({
        title:             BK.t.discard_sale_draft_2,
        text:              BK.t.your_cart_changes_will_be_lost_save_this,
        showDenyButton:    true,
        showCloseButton:   true,
        confirmButtonText: BK.t.discard,
        denyButtonText:    BK.t.save_as_draft,
        confirmButtonColor:'#ef4444',
        denyButtonColor:   '#6b7280',
        reverseButtons:    IS_RTL,
        background:        light ? '#fff' : '#22242F',
        color:             light ? '#333' : '#fff',
    }).then(function(r){
        if (r.isConfirmed)      { qaDraftDiscard(); qaClose(); }
        else if (r.isDenied)    { qaDraftSave(); qaClose(); }
        /* dismissed → keep the drawer open */
    });
}
document.getElementById('qa-close').addEventListener('click', qaAttemptClose);
qaOverlay.addEventListener('click', qaAttemptClose);
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
        if (qaDrawer.classList.contains('show')) qaAttemptClose();
        sfCloseSettings();
    }
});

function qaRenderMeta() {
    if (!qaCtx) return;
    var custLabel = qaCustomer
        ? '<b>' + _esc(qaCustomer.name) + '</b>'
        : QA_TXT.walkin;
    document.getElementById('qa-meta').innerHTML =
        '<span>📅 ' + sfFmtTitle(sfDate) + '</span>'
        + '<span>🕐 <b>' + _fmtMinutes(qaCtx.minutes) + '</b></span>'
        + (qaCtx.empName ? '<span>👤 <b>' + _esc(qaCtx.empName) + '</b></span>' : '')
        + '<span>' + QA_TXT.client + ': ' + custLabel + '</span>';
}

/* ── Services ── */
function qaLoadServices(branchId) {
    if (qaSvcCache[branchId]) {
        qaServices = qaSvcCache[branchId];
        qaRenderServices();
        return;
    }
    fetch(BRANCH_DATA_URL + '?branch_id=' + branchId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(function(data){
            qaSvcCache[branchId] = data.services || [];
            qaServices = qaSvcCache[branchId];
            qaRenderServices();
        })
        .catch(function(){
            document.getElementById('qa-svc-list').innerHTML = '<div class="qa-empty">⚠ ' + QA_TXT.bookFail + '</div>';
        });
}

function qaFmtDur(min) {
    min = parseInt(min, 10) || 0;
    var h = Math.floor(min / 60), m = min % 60, parts = [];
    if (h) parts.push(h + ' ' + (h === 1 ? QA_TXT.hourOne : QA_TXT.hourMany));
    if (m || !h) parts.push(m + ' ' + QA_TXT.minutes);
    return parts.join(' ');
}
function qaFmtPrice(p) {
    var n = parseFloat(p) || 0;
    return (n % 1 === 0 ? n.toLocaleString() : n.toFixed(2));
}

function qaRenderServices() {
    var q    = (document.getElementById('qa-svc-search').value || '').trim().toLowerCase();
    var list = qaServices.filter(function(s){
        return !q || (s.name || '').toLowerCase().indexOf(q) >= 0;
    });
    var box = document.getElementById('qa-svc-list');
    if (!list.length) { box.innerHTML = '<div class="qa-empty">' + QA_TXT.none + '</div>'; return; }

    /* group by category, keep insertion order */
    var groups = {}, order = [];
    list.forEach(function(s){
        var cat = s.category || QA_TXT.other;
        if (!groups[cat]) { groups[cat] = []; order.push(cat); }
        groups[cat].push(s);
    });

    var html = '';
    order.forEach(function(cat, ci){
        var accent    = SF_EMP_COLORS[ci % SF_EMP_COLORS.length];
        /* searching expands everything; otherwise honor the collapsed state */
        var collapsed = !q && qaCollapsed[cat];
        html += '<div class="qa-cat-hdr" data-cat="' + _esc(cat) + '">'
            + '<span class="qa-cat-caret' + (collapsed ? ' closed' : '') + '">▾</span>'
            + _esc(cat) + '<span class="qa-cat-count">' + groups[cat].length + '</span></div>';
        if (collapsed) return;
        groups[cat].forEach(function(s){
            html += '<div class="qa-svc-row" data-id="' + s.id + '" style="border-inline-start-color:' + accent + ';">'
                +   '<div><div class="qa-svc-name">' + _esc(s.name) + '</div>'
                +   '<div class="qa-svc-dur">' + qaFmtDur(s.duration) + '</div></div>'
                +   '<div class="qa-svc-price">' + qaFmtPrice(s.price) + ' ' + _esc(s.currency || '') + '</div>'
                + '</div>';
        });
    });
    box.innerHTML = html;
}
document.getElementById('qa-svc-search').addEventListener('input', qaRenderServices);

/* ── Cart (Fresha flow): click a service → add to selection, book on Save ── */
var qaCart = qaGuests[0].cart; /* live view onto the active guest — see qaUseGuest */

/* ── Guest strip ─────────────────────────────────────────────── */
function qaGuestName(g, i) {
    return (g.customer && g.customer.name) ? g.customer.name : (QA_TXT.guestN.replace(':n', i + 1));
}
function qaGuestTotals(g) {
    var price = 0, mins = 0;
    g.cart.forEach(function(s){ price += parseFloat(s.price) || 0; mins += parseInt(s.duration, 10) || 0; });
    return { price: price, mins: mins };
}
/* the whole party, used for the footer total */
function qaPartyTotals() {
    var price = 0, mins = 0;
    qaGuests.forEach(function(g){
        var t = qaGuestTotals(g);
        price += t.price;
        mins   = Math.max(mins, t.mins); /* guests run in parallel — the party takes the longest one */
    });
    return { price: price, mins: mins };
}

function qaRenderGuests() {
    var wrap = document.getElementById('qa-guests');
    var list = document.getElementById('qa-guest-list');
    var dup  = document.getElementById('qa-dup-guest');
    if (!wrap || !list) return;

    wrap.classList.toggle('d-none', !qaIsGroup());
    if (dup) dup.classList.toggle('d-none', !qaCart.length);

    var cnt = document.getElementById('qa-guests-count');
    if (cnt) cnt.textContent = QA_TXT.guestsN.replace(':n', qaGuests.length);

    if (!qaIsGroup()) { list.innerHTML = ''; return; }

    list.innerHTML = qaGuests.map(function(g, i){
        var t    = qaGuestTotals(g);
        var name = qaGuestName(g, i);
        var sub  = g.cart.length
            ? g.cart.length + ' · ' + qaFmtDur(t.mins)
            : QA_TXT.noSvcYet;
        return '<div class="qa-guest-row' + (i === qaActiveGuest ? ' active' : '') + (g.cart.length ? '' : ' empty') + '"'
            +  ' role="tab" aria-selected="' + (i === qaActiveGuest) + '" data-g="' + i + '" tabindex="0">'
            +    '<div class="qa-guest-av">' + _esc(_initials(name)) + '</div>'
            +    '<div class="qa-guest-txt">'
            +      '<div class="qa-guest-name">' + _esc(name) + '</div>'
            +      '<div class="qa-guest-sub">' + _esc(sub) + '</div>'
            +    '</div>'
            +    '<button type="button" class="qa-guest-x" data-rm="' + i + '" aria-label="' + QA_TXT.removeGuest + '">✕</button>'
            + '</div>';
    }).join('');
}

document.getElementById('qa-guest-list').addEventListener('click', function(e){
    var rm = e.target.closest('[data-rm]');
    if (rm) {
        e.stopPropagation();
        var ri = parseInt(rm.dataset.rm, 10);
        qaGuests.splice(ri, 1);
        if (!qaGuests.length) qaResetGuests();
        qaUseGuest(qaActiveGuest > ri ? qaActiveGuest - 1 : qaActiveGuest);
        return;
    }
    var row = e.target.closest('[data-g]');
    if (row) qaUseGuest(parseInt(row.dataset.g, 10));
});
document.getElementById('qa-guest-list').addEventListener('keydown', function(e){
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var row = e.target.closest('[data-g]');
    if (row) { e.preventDefault(); qaUseGuest(parseInt(row.dataset.g, 10)); }
});

document.getElementById('qa-add-guest').addEventListener('click', function(){
    if (qaGuests.length >= 12) { bkToast(QA_TXT.maxGuests, 'error'); return; }
    qaGuests.push({ customer: null, cart: [] });
    qaUseGuest(qaGuests.length - 1);
});

/* "Same again" — the common case: a family all booking the same thing */
document.getElementById('qa-dup-guest').addEventListener('click', function(){
    if (qaGuests.length >= 12) { bkToast(QA_TXT.maxGuests, 'error'); return; }
    var src = qaGuest();
    qaGuests.push({
        customer: null,
        cart: src.cart.map(function(s){ return Object.assign({}, s); }), /* copy, don't share refs */
    });
    qaUseGuest(qaGuests.length - 1);
});

function qaCartTotals() {
    var price = 0, mins = 0;
    qaCart.forEach(function(s){ price += parseFloat(s.price) || 0; mins += parseInt(s.duration, 10) || 0; });
    return { price: price, mins: mins };
}
function qaRenderCart() {
    var box  = document.getElementById('qa-cart-list');
    var wrap = document.getElementById('qa-cart');
    var save = document.getElementById('qa-save');
    var t    = qaCartTotals();

    /* the whole party has to be bookable — not just the guest being edited */
    var allReady = qaGuests.every(function(g){ return g.cart.length > 0; });
    save.disabled    = !allReady;
    save.textContent = qaIsGroup() ? QA_TXT.saveGroup.replace(':n', qaGuests.length) : QA_TXT.saveOne;

    if (!qaCart.length) {
        wrap.classList.add('d-none');
        document.getElementById('qa-total-val').textContent = '0';
        document.getElementById('qa-total-dur').textContent = '';
        qaRenderGuests();
        return;
    }
    wrap.classList.remove('d-none');

    var cursor = qaCtx ? qaCtx.minutes : 0;
    box.innerHTML = qaCart.map(function(s, i){
        var from = cursor;
        cursor += parseInt(s.duration, 10) || 0;
        var av = qaAvail(s.empId, from, parseInt(s.duration, 10) || 0);
        return '<div class="qa-cart-row" data-row="' + i + '">'
            + '<div class="qa-cart-info">'
            +   '<div class="qa-cart-name">' + _esc(s.name) + '</div>'
            +   '<div class="qa-cart-sub">' + _fmtMinutes(from) + ' · ' + qaFmtDur(s.duration)
            +     ' · ' + _esc(s.empName || QA_TXT.walkin) + '</div>'
            +   (!av.ok
                ? '<span class="qa-warn">⚠ ' + BK.t.team_member_unavailable + '</span>'
                : '')
            + '</div>'
            + '<div class="qa-cart-price">' + qaFmtPrice(s.price) + ' ' + _esc(s.currency || '') + '</div>'
            + '<button class="qa-cart-x" data-i="' + i + '" title="' + BK.t.remove + '">✕</button>'
            + '</div>';
    }).join('');

    /* footer shows the party, since that's what the customer pays */
    var pt = qaIsGroup() ? qaPartyTotals() : t;
    document.getElementById('qa-total-val').textContent = qaFmtPrice(pt.price) + ' ' + _esc(qaCart[0].currency || '');
    document.getElementById('qa-total-dur').textContent = qaFmtDur(pt.mins);
    qaRenderGuests();
    qaTouch(); /* every cart change is autosaved */
}

/* click a category header → collapse/expand it */
var qaCollapsed = {};

/* click a service → add it to the cart */
document.getElementById('qa-svc-list').addEventListener('click', function(e){
    var hdr = e.target.closest('.qa-cat-hdr');
    if (hdr) {
        var cat = hdr.dataset.cat;
        qaCollapsed[cat] = !qaCollapsed[cat];
        qaRenderServices();
        return;
    }
    var row = e.target.closest('.qa-svc-row');
    if (!row || !qaCtx) return;
    var svc = qaServices.find(function(s){ return String(s.id) === String(row.dataset.id); });
    if (!svc) return;
    /* clone per cart item so employee/price/duration can be edited independently */
    qaCart.push({
        id:        svc.id,
        name:      svc.name,
        price:     parseFloat(svc.price) || 0,
        origPrice: parseFloat(svc.price) || 0,
        duration:  parseInt(svc.duration, 10) || 30,
        currency:  svc.currency || '',
        empId:     qaCtx.empId || 0,
        empName:   qaCtx.empName || '',
    });
    qaRenderCart();
    /* tiny visual ack on the row */
    row.style.background = 'var(--cal-pill-active)';
    setTimeout(function(){ row.style.background = ''; }, 180);
});

/* remove from cart / open edit panel */
document.getElementById('qa-cart-list').addEventListener('click', function(e){
    var x = e.target.closest('.qa-cart-x');
    if (x) {
        qaCart.splice(parseInt(x.dataset.i, 10), 1);
        qaRenderCart();
        return;
    }
    var row = e.target.closest('.qa-cart-row');
    if (row) qaEditOpen(parseInt(row.dataset.row, 10));
});

/* ════════════════════════════════
   AVAILABILITY (working hours + existing appointments)
════════════════════════════════ */
function qaClosedSlots(empId) {
    var staff = (sfDayData && sfDayData.staff) || [];
    /* the selected employee's own schedule; walk-in falls back to branch hours */
    var emp = staff.find(function(s){ return String(s.id) === String(empId || 0); });
    if (emp && emp.closedSlots) return emp.closedSlots;
    var any = staff.find(function(s){ return s.id === 0; }) || staff[0];
    return (any && any.closedSlots) || [];
}
function qaOverlapsRange(aF, aT, bF, bT) { return aF < bT && bF < aT; }
function qaAvail(empId, fromMin, durMin) {
    var toMin = fromMin + durMin;
    if (fromMin < 0 || toMin > 1440) return { ok: false, reason: 'closed' };
    var closed = qaClosedSlots(empId);
    for (var i = 0; i < closed.length; i++) {
        if (qaOverlapsRange(fromMin, toMin, closed[i].from, closed[i].to)) return { ok: false, reason: 'closed' };
    }
    if (empId) {
        var busy = ((sfDayData && sfDayData.appointments) || []).some(function(a){
            if (String(a.employeeId == null ? 0 : a.employeeId) !== String(empId)) return false;
            /* only statuses that still hold the slot can make it look busy */
            if (LIVE_STATUSES.indexOf(a.status) < 0) return false;
            if (!a.startIso) return false;
            var s2 = sfMinOfIso(a.startIso);
            var e2 = a.endIso ? sfMinOfIso(a.endIso) : s2 + 30;
            return qaOverlapsRange(fromMin, toMin, s2, e2);
        });
        if (busy) return { ok: false, reason: 'busy' };
    }
    return { ok: true, reason: null };
}
function qaNextSlots(empId, fromMin, durMin) {
    var out = [];
    for (var t = Math.ceil(fromMin / 5) * 5; t <= 1440 - durMin && out.length < 3; t += 5) {
        if (qaAvail(empId, t, durMin).ok) out.push(t);
    }
    return out;
}
function qaItemStart(i) {
    var t = qaCtx ? qaCtx.minutes : 0;
    for (var k = 0; k < i; k++) t += parseInt(qaCart[k].duration, 10) || 0;
    return t;
}

/* ════════════════════════════════
   EDIT SERVICE PANEL (Fresha "تعديل الخدمة")
════════════════════════════════ */
var qaEditIdx = null;
var QA_ED = {
    panel:   document.getElementById('qa-edit'),
    emp:     document.getElementById('qa-edit-emp'),
    empNote: document.getElementById('qa-edit-empnote'),
    price:   document.getElementById('qa-edit-price'),
    priceNote: document.getElementById('qa-edit-pricenote'),
    dur:     document.getElementById('qa-edit-dur'),
    start:   document.getElementById('qa-edit-start'),
    startNote: document.getElementById('qa-edit-startnote'),
    next:    document.getElementById('qa-next'),
    chips:   document.getElementById('qa-next-chips'),
    total:   document.getElementById('qa-edit-total'),
};

function qaEditOpen(i) {
    if (!qaCart[i]) return;
    qaEditIdx = i;
    var it = qaCart[i];
    document.getElementById('qa-edit-svcname').textContent = it.name + '، ' + qaFmtDur(it.duration);

    /* team member options */
    var opts = '<option value="0">' + QA_TXT.walkin + '</option>';
    sfStaffList.filter(function(s){ return s.id !== 0; }).forEach(function(s){
        opts += '<option value="' + s.id + '">' + _esc(s.name) + '</option>';
    });
    QA_ED.emp.innerHTML = opts;
    QA_ED.emp.value = String(it.empId || 0);

    /* duration options: 5min → 6h */
    var dOpts = '';
    for (var t = 5; t <= 360; t += 5) dOpts += '<option value="' + t + '">' + qaFmtDur(t) + '</option>';
    QA_ED.dur.innerHTML = dOpts;
    QA_ED.dur.value = String(it.duration);

    /* start options every 5 min — editable for the first service only */
    var sOpts = '';
    for (var m = 0; m < 1440; m += 5) sOpts += '<option value="' + m + '">' + _fmtMinutes(m) + '</option>';
    QA_ED.start.innerHTML = sOpts;
    QA_ED.start.value = String(qaItemStart(i));
    QA_ED.start.disabled = i !== 0;

    QA_ED.price.value = it.price;
    qaEditRefresh();
    QA_ED.panel.classList.remove('d-none');
}
function qaEditClose() {
    qaEditIdx = null;
    QA_ED.panel.classList.add('d-none');
}
function qaEditRefresh() {
    var i = qaEditIdx;
    if (i === null || !qaCart[i]) return;
    var it    = qaCart[i];
    var empId = parseInt(QA_ED.emp.value, 10) || 0;
    var dur   = parseInt(QA_ED.dur.value, 10) || it.duration;
    var start = parseInt(QA_ED.start.value, 10) || 0;
    var av    = qaAvail(empId, start, dur);

    /* team member note */
    if (!empId) {
        QA_ED.empNote.textContent = '';
        QA_ED.empNote.className = 'qa-edit-note';
    } else if (av.ok) {
        QA_ED.empNote.textContent = '✓ ' + BK.t.available_at_this_time + '';
        QA_ED.empNote.className = 'qa-edit-note ok';
    } else {
        QA_ED.empNote.textContent = '⚠ ' + (av.reason === 'busy'
            ? BK.t.has_another_appointment_at_this_time
            : BK.t.has_no_shift_at_this_time);
        QA_ED.empNote.className = 'qa-edit-note warn';
    }

    /* start note + next-available suggestions */
    if (i !== 0) {
        QA_ED.startNote.textContent = BK.t.starts_right_after_the_previous_service;
        QA_ED.startNote.className = 'qa-edit-note';
        QA_ED.next.classList.add('d-none');
    } else if (!av.ok) {
        QA_ED.startNote.textContent = '⚠ ' + BK.t.unavailable_at + ' ' + _fmtMinutes(start);
        QA_ED.startNote.className = 'qa-edit-note warn';
        var slots = qaNextSlots(empId, start, dur);
        if (slots.length) {
            QA_ED.chips.innerHTML = slots.map(function(m){
                return '<button type="button" class="qa-next-chip" data-m="' + m + '">' + _fmtMinutes(m) + '</button>';
            }).join('');
            QA_ED.next.classList.remove('d-none');
        } else {
            QA_ED.next.classList.add('d-none');
        }
    } else {
        QA_ED.startNote.textContent = '';
        QA_ED.startNote.className = 'qa-edit-note';
        QA_ED.next.classList.add('d-none');
    }

    /* price note (manual discount detection, Fresha-style) */
    var p    = parseFloat(QA_ED.price.value) || 0;
    var diff = Math.round((it.origPrice - p) * 100) / 100;
    if (diff > 0) {
        QA_ED.priceNote.innerHTML = '' + BK.t.manual_discount_applied + ' '
            + qaFmtPrice(diff) + ' ' + _esc(it.currency) + ' · <a id="qa-price-reset">' + BK.t.reset + '</a>';
    } else if (diff < 0) {
        QA_ED.priceNote.innerHTML = '' + BK.t.manual_increase + ' '
            + qaFmtPrice(-diff) + ' ' + _esc(it.currency) + ' · <a id="qa-price-reset">' + BK.t.reset + '</a>';
    } else {
        QA_ED.priceNote.innerHTML = '';
    }

    QA_ED.total.textContent = qaFmtDur(dur) + ' · ' + qaFmtPrice(p) + ' ' + _esc(it.currency);
}

QA_ED.emp.addEventListener('change', qaEditRefresh);
QA_ED.dur.addEventListener('change', qaEditRefresh);
QA_ED.start.addEventListener('change', qaEditRefresh);
QA_ED.price.addEventListener('input', qaEditRefresh);
QA_ED.chips.addEventListener('click', function(e){
    var chip = e.target.closest('.qa-next-chip');
    if (!chip) return;
    QA_ED.start.value = chip.dataset.m;
    qaEditRefresh();
});
QA_ED.priceNote.addEventListener('click', function(e){
    if (!e.target.closest('#qa-price-reset') || qaEditIdx === null) return;
    QA_ED.price.value = qaCart[qaEditIdx].origPrice;
    qaEditRefresh();
});
document.getElementById('qa-edit-back').addEventListener('click', qaEditClose);
document.getElementById('qa-edit-del').addEventListener('click', function(){
    if (qaEditIdx === null) return;
    qaCart.splice(qaEditIdx, 1);
    qaEditClose();
    qaRenderCart();
});
document.getElementById('qa-edit-apply').addEventListener('click', function(){
    var i = qaEditIdx;
    if (i === null || !qaCart[i]) return;
    var it    = qaCart[i];
    var empId = parseInt(QA_ED.emp.value, 10) || 0;
    var found = sfStaffList.find(function(s){ return String(s.id) === String(empId); });
    it.empId    = empId;
    it.empName  = empId ? (found ? found.name : it.empName) : QA_TXT.walkin;
    it.duration = parseInt(QA_ED.dur.value, 10) || it.duration;
    it.price    = parseFloat(QA_ED.price.value) || 0;
    if (i === 0 && !QA_ED.start.disabled) {
        qaCtx.minutes = parseInt(QA_ED.start.value, 10) || qaCtx.minutes;
        qaRenderMeta();
    }
    qaEditClose();
    qaRenderCart();
});

/* The ONE place the save spinner is cleared. bkSubmit guarantees this runs on
   every outcome — success, rejection, network failure, or an unexpected throw —
   so "Saving…" can never outlive the request that caused it. */
function qaSaveSettle() {
    qaBooking = false;
    qaRenderCart(); /* restores the button's real label + disabled state */
}

/* Save → book everything in one appointment */
document.getElementById('qa-save').addEventListener('click', function(){
    if (qaBooking || !qaCtx) return;
    if (!qaGuests.every(function(g){ return g.cart.length; })) { bkToast(QA_TXT.needSvc, 'error'); return; }
    if (qaIsGroup()) { qaSaveGroup(this); return; }

    qaBooking = true;
    var btn = this;
    btn.disabled = true;
    btn.textContent = QA_TXT.saving;

    var hh = Math.floor(qaCtx.minutes / 60), mm = qaCtx.minutes % 60;
    var body = new URLSearchParams({
        _token:      CSRF,
        branch_id:   qaCtx.branchId,
        employee_id: qaCtx.empId ? String(qaCtx.empId) : '',
        start_time:  qaCtx.dateStr + ' ' + String(hh).padStart(2,'0') + ':' + String(mm).padStart(2,'0') + ':00',
    });
    qaCart.forEach(function(s){
        body.append('service_ids[]',  s.id);
        body.append('prices[]',       String(s.price));
        body.append('durations[]',    String(s.duration));
        body.append('employee_ids[]', s.empId ? String(s.empId) : '');
    });
    if (qaCustomer && qaCustomer.id)         body.set('customer_id', qaCustomer.id);
    else if (qaCustomer && qaCustomer.isNew) {
        body.set('customer_name',  qaCustomer.name  || '');
        body.set('customer_phone', qaCustomer.phone || '');
    }
    /* one key per booking attempt — a replay of this exact write is a no-op */
    var jobId = bkUuid();
    body.set('idempotency_key', jobId);

    /* snapshot what the optimistic row needs, before the drawer resets */
    var snap = {
        t:        qaCartTotals(),
        empId:    (qaCart[0] && qaCart[0].empId) || qaCtx.empId || 0,
        custName: qaCustomer ? qaCustomer.name : QA_TXT.walkin,
        svcNames: qaCart.map(function(s){ return s.name; }).join(' + '),
        empName:  qaCtx.empName || QA_TXT.walkin,
        dateStr:  qaCtx.dateStr,
        minutes:  qaCtx.minutes,
    };

    var job = {
        id: jobId, url: QUICK_STORE_URL, bodyType: 'form', body: body.toString(),
        label: snap.custName, tries: 0, nextAt: 0,
    };

    bkSubmit(job, function (json) {
        /* Let the user go FIRST. Everything below is presentation — none of it
           may stand between a saved appointment and a closed drawer. */
        qaClose();
        qaDraftDiscard();
        bkToast(BK.t.appointment_booked_successfully, 'success');

        /* optimistic: drop the new appointment onto the grid, no reload.
           Isolated, so a painting bug can't stop the calendar refresh below. */
        bkSafe(function () {
            if (sfDayData && sfView === 'day' && snap.dateStr === sfDateStr(sfDate)) {
                sfDayData.appointments.push({
                    id:         json.id,
                    employeeId: snap.empId,
                    customer:   snap.custName,
                    service:    snap.svcNames,
                    branch:     '',
                    employee:   snap.empName,
                    status:     'confirmed',
                    color:      EV_COLORS.confirmed,
                    price:      snap.t.price.toFixed(2),
                    startIso:   sfIsoAt(snap.dateStr, snap.minutes),
                    endIso:     sfIsoAt(snap.dateStr, Math.min(snap.minutes + snap.t.mins, 1439)),
                    showUrl:    json.showUrl,
                });
                renderStaffGrid(sfDayData, true); /* keep scroll position */
            } else {
                loadStaffView();
            }
        });
        listLoaded = false;
        bkSafe(calRefetch);
    }, function (msg, willRetry) {
        if (willRetry) {
            /* parked in the outbox — the work is safe, so let the drawer go */
            qaClose();
            qaDraftDiscard();
        } else {
            bkToast(msg || QA_TXT.bookFail, 'error');
        }
    }, qaSaveSettle);
});

/* ── Group save: one atomic request for the whole party ── */
function qaSaveGroup(btn) {
    qaBooking = true;
    btn.disabled = true;
    btn.textContent = QA_TXT.saving;

    var hh = Math.floor(qaCtx.minutes / 60), mm = qaCtx.minutes % 60;
    var jobId = bkUuid();
    var payload = {
        idempotency_key: jobId, /* the whole party books once, however often this replays */
        branch_id:  qaCtx.branchId,
        start_time: qaCtx.dateStr + ' ' + String(hh).padStart(2,'0') + ':' + String(mm).padStart(2,'0') + ':00',
        guests: qaGuests.map(function(g){
            var guest = { services: g.cart.map(function(s){
                return {
                    service_id:  s.id,
                    employee_id: s.empId || null,
                    price:       s.price,
                    duration:    s.duration,
                };
            }) };
            if (g.customer && g.customer.id)   guest.customer_id = g.customer.id;
            else if (g.customer)               { guest.name = g.customer.name || ''; guest.phone = g.customer.phone || ''; }
            return guest;
        }),
    };

    var job = {
        id: jobId, url: QUICK_GROUP_URL, bodyType: 'json', body: JSON.stringify(payload),
        label: QA_TXT.guestsN.replace(':n', qaGuests.length), tries: 0, nextAt: 0,
    };

    bkSubmit(job, function (json) {
        qaClose();
        qaDraftDiscard();

        /* Say so when a guest had to follow their party-mate rather than start
           with them — moving someone's time silently would be a nasty surprise. */
        bkSafe(function () {
            var moved = (json.guests || []).filter(function (g) { return g.moved; });
            if (moved.length) {
                var who = moved.map(function (g) {
                    return g.name + ' ' + (g.start || '').slice(11, 16);
                }).join('، ');
                bkToast(QA_TXT.groupMoved.replace(':n', json.count).replace(':who', who), 'info');
            } else {
                bkToast(QA_TXT.groupBooked.replace(':n', json.count), 'success');
            }
        });

        /* the party lands on several staff columns at once — a reload is simpler
           and cheaper than stitching N optimistic rows into the grid */
        bkSafe(loadStaffView);
        listLoaded = false;
        bkSafe(calRefetch);
    }, function (msg, willRetry) {
        if (willRetry) { qaClose(); qaDraftDiscard(); }
        else { bkToast(msg || QA_TXT.bookFail, 'error'); }
    }, qaSaveSettle);
}

/* ── Clients ── */
document.getElementById('qa-client-collapsed').addEventListener('click', function(){
    this.classList.add('d-none');
    document.getElementById('qa-client-expanded').classList.remove('d-none');
    document.getElementById('qa-client-search').focus();
});

var qaClients = [];
function qaLoadCustomers(q) {
    fetch(CUST_SEARCH_URL + '?q=' + encodeURIComponent(q || ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(data){ qaClients = data || []; qaRenderClients(); })
        .catch(function(){ qaClients = []; qaRenderClients(); });
}

function qaRenderClients() {
    var box  = document.getElementById('qa-client-list');
    var html = ''
        /* add new client */
        + '<div class="qa-cli-row" id="qa-cli-addnew">'
        +   '<div class="qa-cli-av"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>'
        +   '<div class="qa-cli-name">' + QA_TXT.addNew + '</div>'
        + '</div>'
        + '<div class="qa-newc d-none" id="qa-newc-form">'
        +   '<input id="qa-newc-name" type="text" placeholder="' + QA_TXT.name + '">'
        +   '<input id="qa-newc-phone" type="tel" placeholder="' + QA_TXT.phone + '" style="direction:ltr;text-align:start;">'
        +   '<button id="qa-newc-save">' + QA_TXT.save + '</button>'
        + '</div>'
        /* walk-in */
        + '<div class="qa-cli-row' + (!qaCustomer ? ' selected' : '') + '" id="qa-cli-walkin">'
        +   '<div class="qa-cli-av"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13" cy="4" r="2"/><path d="M10.5 8.5 8 21"/><path d="m13 8 2 4 4 1"/><path d="M13 8l-4 .5L7 12"/></svg></div>'
        +   '<div class="qa-cli-name">' + QA_TXT.walkin + '</div>'
        + '</div>';

    /* new client chip (if entered) */
    if (qaCustomer && qaCustomer.isNew) {
        html += '<div class="qa-cli-row selected">'
            +   '<div class="qa-cli-av">' + _esc(_initials(qaCustomer.name)) + '</div>'
            +   '<div><div class="qa-cli-name">' + _esc(qaCustomer.name) + '</div>'
            +   (qaCustomer.phone ? '<div class="qa-cli-sub">' + _esc(qaCustomer.phone) + '</div>' : '')
            +   '</div></div>';
    }

    qaClients.forEach(function(c){
        var sel = qaCustomer && qaCustomer.id && String(qaCustomer.id) === String(c.id);
        html += '<div class="qa-cli-row' + (sel ? ' selected' : '') + '" data-id="' + c.id + '" data-name="' + _esc(c.name) + '">'
            +   '<div class="qa-cli-av">' + _esc(_initials(c.name)) + '</div>'
            +   '<div><div class="qa-cli-name">' + _esc(c.name) + '</div>'
            +   (c.phone ? '<div class="qa-cli-sub">' + _esc(c.phone) + '</div>' : '')
            +   '</div></div>';
    });
    box.innerHTML = html;
}

document.getElementById('qa-client-list').addEventListener('click', function(e){
    /* add-new toggle */
    if (e.target.closest('#qa-cli-addnew')) {
        document.getElementById('qa-newc-form').classList.toggle('d-none');
        return;
    }
    /* save new client */
    if (e.target.closest('#qa-newc-save')) {
        var nm = (document.getElementById('qa-newc-name').value || '').trim();
        var ph = (document.getElementById('qa-newc-phone').value || '').trim();
        if (!nm) { document.getElementById('qa-newc-name').focus(); return; }
        qaSetCustomer({ name: nm, phone: ph, isNew: true });
        qaRenderMeta();
        qaRenderClients();
        return;
    }
    if (e.target.closest('.qa-newc')) return; /* clicks inside form inputs */

    /* walk-in */
    if (e.target.closest('#qa-cli-walkin')) {
        qaSetCustomer(null);
        qaRenderMeta();
        qaRenderClients();
        return;
    }
    /* existing customer */
    var row = e.target.closest('.qa-cli-row[data-id]');
    if (row) {
        qaSetCustomer({ id: row.dataset.id, name: row.dataset.name });
        qaRenderMeta();
        qaRenderClients();
    }
});

var _qaCliTimer = null;
document.getElementById('qa-client-search').addEventListener('input', function(){
    var q = this.value;
    clearTimeout(_qaCliTimer);
    _qaCliTimer = setTimeout(function(){ qaLoadCustomers(q); }, 250);
});

/* ════════════════════════════════
   APPOINTMENT DETAIL + CHECKOUT DRAWER (Fresha flow)
════════════════════════════════ */
var AP_DETAILS_URL  = BK.routes.appointmentsDetailsJson;
var AP_CHECKOUT_URL = BK.routes.appointmentsCheckout;
var AP_DATA_URL     = BK.routes.appointmentsCheckoutData;

var apOverlay = document.getElementById('ap-overlay');
var apDrawer  = document.getElementById('ap-drawer');
var apBody    = document.getElementById('ap-body');

var apData = null, apStep = 'main', apTip = 0, apTipSel = 'none',
    apCart = [], apPay = 'cash', apBusy = false, apExtra = null, apPicker = null,
    apCashRecv = null;

var AP_TXT = {
    services:  BK.t.services,
    checkout:  BK.t.checkout,
    details:   BK.t.view_details,
    profile:   BK.t.view_profile,
    total:     BK.t.total,
    tipTitle:  BK.t.select_tip,
    tipFor:    BK.t.amount_for,
    noTip:     BK.t.no_tip,
    custom:    BK.t.custom_tip,
    back:      BK.t.back,
    next:      BK.t.continue_to_payment,
    cartTitle: BK.t.cart,
    addSvc:    BK.t.service,
    addPrd:    BK.t.product,
    addAppt:   BK.t.appointment,
    tipLine:   BK.t.tip,
    cash:      BK.t.cash,
    card:      BK.t.card,
    payNow:    BK.t.complete_payment,
    paid:      BK.t.paid,
    stock:     BK.t.in_stock,
    empty:     BK.t.nothing_available,
    loading:   BK.t.loading,
    payDone:   BK.t.paid_completed_successfully,
    payFail:   BK.t.payment_failed,
    stUpdated: BK.t.status_updated,
    /* ── ERP checkout additions ── */
    alreadyPaid:  BK.t.already_checked_out_paid_cannot_charge_t,
    outOfStock:   BK.t.out_of_stock,
    lowStock:     BK.t.left,
    maxStock:     BK.t.cannot_add_more_than_available_stock,
    sumBase:      BK.t.appointment_services,
    sumExtra:     BK.t.extra_services,
    sumProducts:  BK.t.products,
    sumAppts:     BK.t.other_appointments,
    sumGrand:     BK.t.grand_total,
    cashRecv:     BK.t.amount_received,
    changeDue:    BK.t.change_due,
    shortBy:      BK.t.short_by,
    successTitle: BK.t.checkout_complete,
    successSub:   BK.t.appointment_completed_payment_recorded_i,
    viewInvoice:  BK.t.view_invoice,
    printInvoice: BK.t.print,
    closeWord:    BK.t.close,
    pickedMark:   BK.t.added,
};

function apStatusColor(s) { return EV_COLORS[s] || '#64748b'; }
function apCartTotal() {
    var t = apData ? apData.total : 0;
    apCart.forEach(function(l){ t += l.price * (l.qty || 1); });
    return t + (apTip || 0);
}
function apMoney(n) { return qaFmtPrice(n) + ' ' + _esc(apData ? apData.currency : ''); }

function apOpen(id) {
    apData = null; apStep = 'main'; apTip = 0; apTipSel = 'none';
    apCart = []; apPay = 'cash'; apBusy = false; apExtra = null; apPicker = null;
    apCashRecv = null;
    apBody.innerHTML = '<div class="qa-empty" style="margin:auto;"><div class="spinner-border spinner-border-sm"></div><div class="mt-2">' + AP_TXT.loading + '</div></div>';
    apOverlay.classList.remove('d-none');
    requestAnimationFrame(function(){ apOverlay.classList.add('show'); apDrawer.classList.add('show'); });
    fetch(AP_DETAILS_URL.replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(function(json){ apData = json; apRender(); })
        .catch(function(err){ apBody.innerHTML = '<div class="qa-empty" style="margin:auto;">⚠ ' + err.message + '</div>'; });
}
function apClose() {
    apOverlay.classList.remove('show');
    apDrawer.classList.remove('show');
    setTimeout(function(){ apOverlay.classList.add('d-none'); }, 220);
}
document.getElementById('ap-close').addEventListener('click', apClose);
apOverlay.addEventListener('click', apClose);
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') apClose(); });

function apRender() {
    if (!apData) return;
    if (apStep === 'main') return apRenderMain();
    if (apStep === 'tip')  return apRenderTip();
    return apRenderCart();
}

/* ── STEP: main details ── */
function apRenderMain() {
    var s = apData;
    /* Money can still be taken as long as the appointment has not settled. */
    var canPay = !STATUS_DEFS[s.status] || !STATUS_DEFS[s.status].terminal;

    /* Current status first, then only the moves the state machine permits. */
    var stOpts = [s.status].concat(_nextMoves(s.status));
    var optsHtml = stOpts.map(function(st){
        return '<option value="' + st + '"' + (st === s.status ? ' selected' : '') + '>' + _esc(STATUS_LABELS[st] || st) + '</option>';
    }).join('');

    var svcHtml = s.services.map(function(sv){
        return '<div class="qa-cart-row" style="border-inline-start-color:' + apStatusColor(s.status) + ';">'
            + '<div class="qa-cart-info"><div class="qa-cart-name">' + _esc(sv.name) + '</div>'
            + '<div class="qa-cart-sub">' + (sv.start || '') + (sv.duration ? ' · ' + qaFmtDur(sv.duration) : '') + ' · ' + _esc(sv.employee) + '</div></div>'
            + '<div class="qa-cart-price">' + qaFmtPrice(sv.price) + ' ' + _esc(s.currency) + '</div></div>';
    }).join('');

    var payChip = s.paymentStatus === 'paid'
        ? '<span class="ap-stock-b" style="font-size:.68rem;padding:3px 10px;">✓ ' + BK.t.paid_2 + '</span>'
        : (s.paymentStatus === 'partial'
            ? '<span class="ap-stock-b low" style="font-size:.68rem;padding:3px 10px;">' + BK.t.partially_paid + '</span>'
            : '<span class="ap-stock-b low" style="font-size:.68rem;padding:3px 10px;">' + BK.t.unpaid + '</span>');

    apBody.innerHTML =
        '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;padding-inline-end:46px;">'
        + '<select class="ap-status" id="ap-status" style="background-color:' + apStatusColor(s.status) + ';">' + optsHtml + '</select>'
        + payChip
        + '<div class="ap-when">' + _esc(s.dateIso || '') + ' · <span style="direction:ltr;display:inline-block;">' + (s.startLabel || '') + ' – ' + (s.endLabel || '') + '</span></div>'
        + '</div>'
        + '<div class="ap-client">'
        +   '<div class="av">' + _esc(_initials(s.customer.name)) + '</div>'
        +   '<div><div class="cn">' + _esc(s.customer.name) + '</div>'
        +   (s.customer.phone ? '<div class="cp">' + _esc(s.customer.phone) + '</div>' : '')
        +   '</div>'
        +   (s.customer.url ? '<a href="' + s.customer.url + '">' + AP_TXT.profile + '</a>' : '')
        + '</div>'
        + '<div class="ap-sec-title">' + AP_TXT.services + '</div>'
        + '<div class="ap-scroll">' + svcHtml + '</div>'
        + '<div class="qa-footer">'
        +   '<div class="qa-total"><span>' + AP_TXT.total + '</span><b>' + apMoney(s.total) + '</b>'
        +   (s.durationMin ? '<small>' + qaFmtDur(s.durationMin) + '</small>' : '') + '</div>'
        +   '<div style="display:flex;gap:8px;align-items:center;">'
        +     '<a class="ap-btn-ghost" href="' + s.showUrl + '">' + AP_TXT.details + '</a>'
        +     (s.paymentStatus === 'paid'
                ? '<span class="ap-paid-badge">' + AP_TXT.paid + '</span>'
                : (canPay ? '<button class="ap-btn-primary" data-ap="checkout">' + AP_TXT.checkout + '</button>' : ''))
        +   '</div>'
        + '</div>';
}

/* ── STEP: tip ── */
function apRenderTip() {
    var s = apData;
    var boxes = [
        { key: 'none', tp: AP_TXT.noTip, tv: '' },
        { key: 'p10',  tp: '10%', tv: apMoney(s.total * 0.10) },
        { key: 'p18',  tp: '18%', tv: apMoney(s.total * 0.18) },
        { key: 'p25',  tp: '25%', tv: apMoney(s.total * 0.25) },
        { key: 'custom', tp: '+ ' + AP_TXT.custom, tv: '' },
    ];
    apBody.innerHTML =
        '<div class="qa-title" style="margin-top:24px;">' + AP_TXT.tipTitle + '</div>'
        + '<div class="ap-when" style="margin-bottom:4px;">' + AP_TXT.tipFor + ' <b style="color:var(--cal-text);">' + _esc(s.employee) + '</b></div>'
        + '<div class="ap-scroll"><div class="ap-tip-grid">'
        + boxes.map(function(b){
            return '<div class="ap-tip-box' + (apTipSel === b.key ? ' selected' : '') + (b.key === 'custom' ? ' ap-tip-custom' : '') + '" data-tip="' + b.key + '">'
                + '<div class="tp">' + b.tp + '</div>'
                + (b.tv ? '<div class="tv">' + b.tv + '</div>' : '')
                + (b.key === 'custom' && apTipSel === 'custom'
                    ? '<input type="number" min="0" id="ap-tip-input" value="' + (apTip || '') + '" placeholder="0">' : '')
                + '</div>';
        }).join('')
        + '</div></div>'
        + '<div class="qa-footer">'
        +   '<button class="ap-btn-ghost" data-ap="back-main">' + AP_TXT.back + '</button>'
        +   '<button class="ap-btn-primary" data-ap="to-cart">' + AP_TXT.next + '</button>'
        + '</div>';
    var inp = document.getElementById('ap-tip-input');
    if (inp) inp.focus();
}

/* ── STEP: cart + payment ── */
function apRenderCart() {
    var s = apData;
    var lines = s.services.map(function(sv){
        return '<div class="qa-cart-row"><div class="qa-cart-info"><div class="qa-cart-name">' + _esc(sv.name) + '</div>'
            + '<div class="qa-cart-sub">' + _esc(sv.employee) + '</div></div>'
            + '<div class="qa-cart-price">' + qaFmtPrice(sv.price) + '</div></div>';
    }).join('');

    if (apTip > 0) {
        lines += '<div class="qa-cart-row" style="border-inline-start-color:#f472b6;">'
            + '<div class="qa-cart-info"><div class="qa-cart-name">💝 ' + AP_TXT.tipLine + '</div>'
            + '<div class="qa-cart-sub">' + _esc(s.employee) + '</div></div>'
            + '<div class="qa-cart-price">' + qaFmtPrice(apTip) + '</div>'
            + '<button class="qa-cart-x" data-rmtip="1">✕</button></div>';
    }
    apCart.forEach(function(l, i){
        var qtyCtl = '';
        var stockNote = '';
        if (l.type === 'product') {
            var maxQ = (l.stock === null || l.stock === undefined) ? 999 : l.stock;
            qtyCtl = '<span class="ap-qty">'
                + '<button type="button" data-qdec="' + i + '" aria-label="−"' + (l.qty <= 1 ? ' disabled' : '') + '>−</button>'
                + '<input type="number" inputmode="numeric" min="1" max="' + maxQ + '" value="' + l.qty + '" data-qinp="' + i + '" aria-label="' + BK.t.quantity + '">'
                + '<button type="button" data-qinc="' + i + '" aria-label="+"' + (l.qty >= maxQ ? ' disabled' : '') + '>+</button>'
                + '</span>';
            if (l.stock !== null && l.stock !== undefined) {
                stockNote = '<div class="qa-cart-sub">' + AP_TXT.lowStock + ' ' + Math.max(l.stock - l.qty, 0) + ' · ' + AP_TXT.stock + ' ' + l.stock + '</div>';
            }
        }
        lines += '<div class="qa-cart-row" style="border-inline-start-color:' + (l.type === 'product' ? '#06b6d4' : l.type === 'appt' ? '#f59e0b' : 'var(--cal-accent)') + ';">'
            + '<div class="qa-cart-info"><div class="qa-cart-name">' + _esc(l.name) + (l.type !== 'product' && l.qty > 1 ? ' × ' + l.qty : '') + '</div>' + stockNote + '</div>'
            + qtyCtl
            + '<div class="qa-cart-price">' + qaFmtPrice(l.price * l.qty) + '</div>'
            + '<button class="qa-cart-x" data-rmline="' + i + '">✕</button></div>';
    });

    var pickerHtml = '';
    if (apPicker) {
        pickerHtml = '<div class="ap-picker" id="ap-picker"><div class="qa-empty">' + AP_TXT.loading + '</div></div>';
    }

    apBody.innerHTML =
        '<div class="qa-title" style="margin-top:24px;">' + AP_TXT.cartTitle + '</div>'
        + '<div class="ap-scroll">' + lines
        + '<div class="ap-add-btns">'
        +   '<button data-pick="service"' + (apPicker === 'service' ? ' class="active"' : '') + '>+ ' + AP_TXT.addSvc + '</button>'
        +   '<button data-pick="product"' + (apPicker === 'product' ? ' class="active"' : '') + '>+ ' + AP_TXT.addPrd + '</button>'
        +   (s.customer.id ? '<button data-pick="appt"' + (apPicker === 'appt' ? ' class="active"' : '') + '>+ ' + AP_TXT.addAppt + '</button>' : '')
        + '</div>'
        + pickerHtml
        + apSummaryHtml()
        + '<div class="ap-sec-title" style="margin-top:12px;">' + BK.t.payment_method + '</div>'
        + '<div class="ap-pay-toggle">'
        +   '<button data-pay="cash"' + (apPay === 'cash' ? ' class="active"' : '') + '>💵 ' + AP_TXT.cash + '</button>'
        +   '<button data-pay="card"' + (apPay === 'card' ? ' class="active"' : '') + '>💳 ' + AP_TXT.card + '</button>'
        + '</div>'
        + apCashHtml()
        + '</div>'
        + '<div class="qa-footer">'
        +   '<div class="qa-total"><span>' + AP_TXT.total + '</span><b>' + apMoney(apCartTotal()) + '</b></div>'
        +   '<div style="display:flex;gap:8px;">'
        +     '<button class="ap-btn-ghost" data-ap="back-tip">' + AP_TXT.back + '</button>'
        +     '<button class="ap-btn-primary" data-ap="submit" id="ap-submit-btn">' + AP_TXT.payNow + '</button>'
        +   '</div>'
        + '</div>';

    if (apPicker) apLoadPicker();
}

/* ── ERP totals breakdown ── */
function apSummaryHtml() {
    var extras = 0, products = 0, appts = 0;
    apCart.forEach(function(l){
        var v = l.price * (l.qty || 1);
        if (l.type === 'product')   products += v;
        else if (l.type === 'appt') appts    += v;
        else                        extras   += v;
    });
    var row = function(lbl, v){
        if (!v) return '';
        return '<div class="ap-sum-row"><span>' + lbl + '</span><b>' + apMoney(v) + '</b></div>';
    };
    return '<div class="ap-sum">'
        + '<div class="ap-sum-row"><span>' + AP_TXT.sumBase + '</span><b>' + apMoney(apData.total) + '</b></div>'
        + row(AP_TXT.sumExtra,    extras)
        + row(AP_TXT.sumProducts, products)
        + row(AP_TXT.sumAppts,    appts)
        + row(AP_TXT.tipLine,     apTip)
        + '<div class="ap-sum-row total"><span>' + AP_TXT.sumGrand + '</span><b>' + apMoney(apCartTotal()) + '</b></div>'
        + '</div>';
}

/* ── cash tender: amount received + change due (UI aid only) ── */
function apCashHtml() {
    if (apPay !== 'cash') return '';
    var total  = apCartTotal();
    var recv   = apCashRecv;
    var chHtml = '';
    if (recv !== null && recv !== '' && !isNaN(recv)) {
        var diff = parseFloat(recv) - total;
        chHtml = diff >= 0
            ? '<span class="ap-change pos">' + AP_TXT.changeDue + ': ' + apMoney(diff) + '</span>'
            : '<span class="ap-change neg">' + AP_TXT.shortBy + ': ' + apMoney(-diff) + '</span>';
    }
    return '<div class="ap-cash">'
        + '<label for="ap-cash-in">' + AP_TXT.cashRecv + '</label>'
        + '<input id="ap-cash-in" type="number" inputmode="decimal" min="0" step="any" placeholder="' + qaFmtPrice(total) + '" value="' + (recv === null ? '' : recv) + '">'
        + chHtml
        + '</div>';
}

function apLoadPicker() {
    var box = document.getElementById('ap-picker');
    if (!box) return;
    function rows(list, kind) {
        if (!list.length) return '<div class="qa-empty">' + AP_TXT.empty + '</div>';
        return list.map(function(it){
            var tracked = it.stock !== null && it.stock !== undefined;
            var out     = tracked && it.stock <= 0;
            var inCart  = apCart.find(function(l){ return l.type === kind && String(l.id) === String(it.id); });
            var badge   = '';
            if (tracked) {
                var cls = out ? 'out' : (it.stock <= 3 ? 'low' : '');
                badge = ' <span class="ap-stock-b ' + cls + '">'
                    + (out ? AP_TXT.outOfStock : it.stock + ' ' + AP_TXT.stock) + '</span>';
            }
            var picked = '';
            if (inCart) {
                picked = kind === 'product'
                    ? '<span class="pq">' + inCart.qty + '</span>'
                    : ' <span class="ap-stock-b">' + AP_TXT.pickedMark + '</span>';
            }
            return '<div class="ap-pick-row' + (out ? ' is-out' : '') + (inCart ? ' is-picked' : '') + '"'
                + ' data-padd="' + kind + '" data-id="' + it.id + '" data-name="' + _esc(it.name) + '"'
                + ' data-price="' + it.price + '"' + (tracked ? ' data-stock="' + it.stock + '"' : '') + '>'
                + '<span>' + _esc(it.name) + badge + picked
                + '</span><span class="pp">' + qaFmtPrice(it.price) + '</span></div>';
        }).join('');
    }
    if (apPicker === 'service') {
        var render = function(){ box.innerHTML = rows(qaSvcCache[apData.branchId] || [], 'service'); };
        if (qaSvcCache[apData.branchId]) return render();
        fetch(BRANCH_DATA_URL + '?branch_id=' + apData.branchId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(d){ qaSvcCache[apData.branchId] = d.services || []; render(); });
        return;
    }
    var renderX = function(){
        box.innerHTML = rows(apPicker === 'product' ? apExtra.products : apExtra.appointments, apPicker);
    };
    if (apExtra) return renderX();
    var p = new URLSearchParams({ branch_id: apData.branchId, exclude: apData.id });
    if (apData.customer.id) p.set('customer_id', apData.customer.id);
    fetch(AP_DATA_URL + '?' + p, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(d){ apExtra = { products: d.products || [], appointments: d.appointments || [] }; renderX(); });
}

/* ── submit checkout ── */
function apSubmit() {
    if (apBusy || !apData) return;

    /* client-side stock sanity before hitting the server */
    for (var ci = 0; ci < apCart.length; ci++) {
        var cl = apCart[ci];
        if (cl.type === 'product' && cl.stock !== null && cl.stock !== undefined && cl.qty > cl.stock) {
            bkToast(AP_TXT.maxStock + ' — ' + cl.name + ' (' + cl.stock + ')', 'error');
            return;
        }
    }

    apBusy = true;
    var sb = document.getElementById('ap-submit-btn');
    if (sb) { sb.disabled = true; sb.innerHTML = '<span class="ap-spin"></span>' + AP_TXT.payNow; }

    var body = new URLSearchParams({ _token: CSRF, payment_method: apPay, tip_amount: String(apTip || 0) });
    var pi = 0;
    apCart.forEach(function(l){
        if (l.type === 'service') body.append('extra_service_ids[]', l.id);
        else if (l.type === 'product') {
            body.append('products[' + pi + '][id]', l.id);
            body.append('products[' + pi + '][qty]', l.qty);
            pi++;
        } else if (l.type === 'appt') body.append('other_appointment_ids[]', l.id);
    });
    fetch(AP_CHECKOUT_URL.replace('__ID__', apData.id), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
    })
    .then(function(r){ return r.json().then(function(j){ return { ok: r.ok, status: r.status, j: j }; }); })
    .then(function(res){
        if (!res.ok || !res.j.ok) {
            var err = new Error(res.j.message || 'fail');
            err.code = res.j.code || (res.status === 409 ? 'already_paid' : null);
            throw err;
        }
        bkToast(AP_TXT.payDone, 'success');
        sfUpdateCache(apData.id, { status: 'completed', color: EV_COLORS.completed });
        apCart.forEach(function(l){
            if (l.type === 'appt') sfUpdateCache(l.id, { status: 'completed', color: EV_COLORS.completed });
        });
        if (sfDayData && sfView === 'day') renderStaffGrid(sfDayData, true);
        listLoaded = false;
        calRefetch();
        apRenderSuccess(res.j);
    })
    .catch(function(err){
        if (err && err.code === 'already_paid') {
            bkToast(AP_TXT.alreadyPaid, 'error');
            apOpen(apData.id); /* refresh drawer → shows the "Paid ✓" badge */
            return;
        }
        bkToast(err.message !== 'fail' ? err.message : AP_TXT.payFail, 'error');
    })
    .finally(function(){
        apBusy = false;
        var sb2 = document.getElementById('ap-submit-btn');
        if (sb2) { sb2.disabled = false; sb2.textContent = AP_TXT.payNow; }
    });
}

/* ── success screen: receipt summary + invoice shortcuts ── */
function apRenderSuccess(resp) {
    apBody.innerHTML =
        '<div class="ap-success">'
        + '<div class="ok-ring"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>'
        + '<h4>' + AP_TXT.successTitle + '</h4>'
        + '<div class="sub">' + AP_TXT.successSub + '</div>'
        + '<div class="amount">' + apMoney(apCartTotal()) + '</div>'
        + '<div class="sub">' + _esc(apData.customer.name) + ' · ' + (apPay === 'cash' ? AP_TXT.cash : AP_TXT.card) + '</div>'
        + '<div class="btns">'
        + (resp.invoiceUrl ? '<a class="ap-btn-ghost" href="' + resp.invoiceUrl + '">' + AP_TXT.viewInvoice + '</a>' : '')
        + (resp.printUrl   ? '<a class="ap-btn-ghost" href="' + resp.printUrl + '" target="_blank" rel="noopener">🖨 ' + AP_TXT.printInvoice + '</a>' : '')
        + '<button class="ap-btn-primary" data-ap="close-success">' + AP_TXT.closeWord + '</button>'
        + '</div></div>';
}

/* ── change status from the pill ── */
function apChangeStatus(st) {
    if (apBusy || !apData || st === apData.status) return;
    apBusy = true;
    var body = new URLSearchParams({ _method: 'PATCH', _token: CSRF, status: st });
    fetch(STATUS_ROUTE_BASE.replace('__ID__', apData.id), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: body,
    })
    .then(function(r){ return r.json(); })
    .then(function(json){
        if (!json.ok) throw new Error('fail');
        apData.status = json.status;
        bkToast(AP_TXT.stUpdated, 'warning');
        sfUpdateCache(apData.id, { status: json.status, color: apStatusColor(json.status) });
        if (sfDayData && sfView === 'day') renderStaffGrid(sfDayData, true);
        listLoaded = false;
        calRefetch();
        apRender();
    })
    .catch(function(){ bkToast(QA_TXT.bookFail, 'error'); apRender(); })
    .finally(function(){ apBusy = false; });
}

/* ── delegated events inside the drawer ── */
apBody.addEventListener('click', function(e){
    var t;
    if ((t = e.target.closest('[data-ap]'))) {
        var act = t.dataset.ap;
        if (act === 'checkout')  { apStep = 'tip';  apRender(); }
        if (act === 'back-main') { apStep = 'main'; apRender(); }
        if (act === 'to-cart')   { apStep = 'cart'; apRender(); }
        if (act === 'back-tip')  { apStep = 'tip';  apRender(); }
        if (act === 'submit')    apSubmit();
        if (act === 'close-success') apClose();
        return;
    }
    if ((t = e.target.closest('.ap-tip-box'))) {
        if (e.target.id === 'ap-tip-input') return;
        apTipSel = t.dataset.tip;
        if (apTipSel === 'none')        apTip = 0;
        else if (apTipSel === 'p10')    apTip = Math.round(apData.total * 0.10 * 100) / 100;
        else if (apTipSel === 'p18')    apTip = Math.round(apData.total * 0.18 * 100) / 100;
        else if (apTipSel === 'p25')    apTip = Math.round(apData.total * 0.25 * 100) / 100;
        else                            apTip = apTip || 0;
        apRender();
        return;
    }
    if ((t = e.target.closest('[data-pick]'))) {
        apPicker = apPicker === t.dataset.pick ? null : t.dataset.pick;
        apRender();
        return;
    }
    if ((t = e.target.closest('.ap-pick-row'))) {
        var kind = t.dataset.padd, id = t.dataset.id;
        var stock = t.dataset.stock !== undefined ? parseInt(t.dataset.stock, 10) : null;
        if (kind === 'product') {
            if (stock !== null && stock <= 0) { bkToast(AP_TXT.outOfStock, 'error'); return; }
            var ex = apCart.find(function(l){ return l.type === 'product' && String(l.id) === String(id); });
            if (ex) {
                if (stock !== null && ex.qty >= stock) { bkToast(AP_TXT.maxStock + ' (' + stock + ')', 'error'); return; }
                ex.qty++;
            } else {
                apCart.push({ type: 'product', id: id, name: t.dataset.name, price: parseFloat(t.dataset.price) || 0, qty: 1, stock: stock });
            }
        } else if (kind === 'appt') {
            /* toggle: click again removes it from the sale */
            var ai = apCart.findIndex(function(l){ return l.type === 'appt' && String(l.id) === String(id); });
            if (ai >= 0) apCart.splice(ai, 1);
            else apCart.push({ type: 'appt', id: id, name: t.dataset.name, price: parseFloat(t.dataset.price) || 0, qty: 1 });
        } else {
            apCart.push({ type: 'service', id: id, name: t.dataset.name, price: parseFloat(t.dataset.price) || 0, qty: 1 });
        }
        apRender();
        return;
    }
    /* ── product qty stepper ── */
    if ((t = e.target.closest('[data-qinc]'))) {
        var li = apCart[parseInt(t.dataset.qinc, 10)];
        if (li) {
            var mx = (li.stock === null || li.stock === undefined) ? 999 : li.stock;
            if (li.qty >= mx) { bkToast(AP_TXT.maxStock + ' (' + mx + ')', 'error'); return; }
            li.qty++;
            apRender();
        }
        return;
    }
    if ((t = e.target.closest('[data-qdec]'))) {
        var ld = apCart[parseInt(t.dataset.qdec, 10)];
        if (ld && ld.qty > 1) { ld.qty--; apRender(); }
        return;
    }
    if ((t = e.target.closest('[data-rmline]'))) {
        apCart.splice(parseInt(t.dataset.rmline, 10), 1);
        apRender();
        return;
    }
    if ((t = e.target.closest('[data-rmtip]'))) {
        apTip = 0; apTipSel = 'none';
        apRender();
        return;
    }
    if ((t = e.target.closest('[data-pay]'))) {
        apPay = t.dataset.pay;
        apRender();
    }
});
apBody.addEventListener('change', function(e){
    if (e.target.id === 'ap-status') apChangeStatus(e.target.value);
});
apBody.addEventListener('input', function(e){
    if (e.target.id === 'ap-tip-input') apTip = parseFloat(e.target.value) || 0;
    if (e.target.id === 'ap-cash-in') {
        apCashRecv = e.target.value;
        /* live change-due refresh without rebuilding the input (keeps focus) */
        var cashBox = e.target.closest('.ap-cash');
        var old = cashBox.querySelector('.ap-change');
        if (old) old.remove();
        var v = parseFloat(e.target.value);
        if (!isNaN(v)) {
            var diff = v - apCartTotal();
            var span = document.createElement('span');
            span.className = 'ap-change ' + (diff >= 0 ? 'pos' : 'neg');
            span.textContent = (diff >= 0 ? AP_TXT.changeDue : AP_TXT.shortBy) + ': ' + apMoney(Math.abs(diff));
            cashBox.appendChild(span);
        }
    }
});
apBody.addEventListener('change', function(e){
    var qi = e.target.closest('[data-qinp]');
    if (qi) {
        var line = apCart[parseInt(qi.dataset.qinp, 10)];
        if (!line) return;
        var mx = (line.stock === null || line.stock === undefined) ? 999 : line.stock;
        var v  = parseInt(qi.value, 10);
        if (isNaN(v) || v < 1) v = 1;
        if (v > mx) { v = mx; bkToast(AP_TXT.maxStock + ' (' + mx + ')', 'error'); }
        line.qty = v;
        apRender();
    }
});
window.apOpen = apOpen;

/* ════════════════════════════════════════════════════════════════
   RELIABILITY — autosaved draft, offline outbox, sync status
   The rule: a write the user has committed to must survive a dropped
   connection, a refresh, a crash or a closed laptop, and must never
   land twice. Duplicate protection is the server's job (idempotency
   key); this side guarantees the attempt is not lost.
════════════════════════════════════════════════════════════════ */
var BK_DRAFT_KEY  = 'bk_qa_draft';
var BK_OUTBOX_KEY = 'bk_outbox';
var BK_MAX_TRIES  = 10;

var BK_SYNC_TXT = {
    saving:  BK.t.saving_2,
    saved:   BK.t.saved,
    offline: BK.t.offline_n_waiting_to_sync,
    offline0:BK.t.offline_changes_are_kept_on_this_device,
    syncing: BK.t.syncing_n,
    failed:  BK.t.n_could_not_sync_retry,
    queued:  BK.t.you_are_offline_this_booking_will_save_a,
    synced:  BK.t.pending_bookings_synced,
    dropped: BK.t.a_pending_booking_was_rejected_m,
    leave:   BK.t.you_have_unsaved_changes,
};

function bkUuid() {
    try { if (window.crypto && crypto.randomUUID) return crypto.randomUUID(); } catch (e) {}
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}
/* localStorage can throw: private mode, quota, disabled storage */
function bkStoreGet(k, fallback) {
    try { var v = localStorage.getItem(k); return v ? JSON.parse(v) : fallback; }
    catch (e) { return fallback; }
}
function bkStoreSet(k, v) {
    try { localStorage.setItem(k, JSON.stringify(v)); return true; }
    catch (e) { return false; }
}
function bkStoreDel(k) { try { localStorage.removeItem(k); } catch (e) {} }

/* ── sync status pill ─────────────────────────────────────────── */
var bkSyncHideT = null;
function bkSetSync(state, n) {
    var box = document.getElementById('bk-sync');
    var txt = document.getElementById('bk-sync-txt');
    var rty = document.getElementById('bk-sync-retry');
    if (!box || !txt || !rty) return; /* status chrome is cosmetic — never let it break a save */

    clearTimeout(bkSyncHideT);
    box.classList.remove('d-none', 'is-saving', 'is-saved', 'is-offline', 'is-syncing', 'is-failed');
    rty.classList.add('d-none');

    if (state === 'idle') { box.classList.add('d-none'); return; }

    box.classList.add('is-' + state);
    if (state === 'offline') txt.textContent = n ? BK_SYNC_TXT.offline.replace(':n', n) : BK_SYNC_TXT.offline0;
    else if (state === 'syncing') txt.textContent = BK_SYNC_TXT.syncing.replace(':n', n || 1);
    else if (state === 'failed')  { txt.textContent = BK_SYNC_TXT.failed.replace(':n', n || 1); rty.classList.remove('d-none'); }
    else txt.textContent = BK_SYNC_TXT[state] || state;

    /* "Saved" is transient — the rest stay until the situation changes */
    if (state === 'saved') bkSyncHideT = setTimeout(function () { bkSetSync('idle'); }, 2200);
}

/* ── outbox ───────────────────────────────────────────────────── */
function bkOutbox()        { var l = bkStoreGet(BK_OUTBOX_KEY, []); return Array.isArray(l) ? l : []; }
function bkOutboxSet(list) { bkStoreSet(BK_OUTBOX_KEY, list); }
function bkOutboxAdd(job) {
    var l = bkOutbox();
    l.push(job);
    bkOutboxSet(l);
    bkSyncRefreshBadge();
}
function bkOutboxDrop(id) {
    bkOutboxSet(bkOutbox().filter(function (j) { return j.id !== id; }));
    bkSyncRefreshBadge();
}
function bkOutboxUpdate(job) {
    bkOutboxSet(bkOutbox().map(function (j) { return j.id === job.id ? job : j; }));
}
function bkPendingCount() { return bkOutbox().length; }

function bkSyncRefreshBadge() {
    var n = bkPendingCount();
    if (!n) { bkSetSync('idle'); return; }
    var failed = bkOutbox().filter(function (j) { return (j.tries || 0) >= BK_MAX_TRIES; }).length;
    if (failed)          { bkSetSync('failed', failed); return; }
    if (bkIsOffline())   { bkSetSync('offline', n); return; }
    bkSetSync('syncing', n);
}

/* exponential backoff with jitter, capped at a minute */
function bkBackoff(tries) {
    return Math.min(1000 * Math.pow(2, tries), 60000) + Math.floor(Math.random() * 400);
}

/* ── connectivity ─────────────────────────────────────────────
   navigator.onLine is a hint, not proof: it reports "online" for a
   LAN with no internet, and can stay stale after a laptop wakes.
   The only proof of a working connection is a request that came
   back — ANY HTTP status counts, including 4xx and 5xx. So the
   offline state is driven by evidence, and any reply clears it. */
var BK_NET = { offlineEvidence: false };
function bkOnlineHint() { return navigator.onLine !== false; }
function bkIsOffline()  { return !bkOnlineHint() || BK_NET.offlineEvidence; }
function bkMarkOnline() {
    if (!BK_NET.offlineEvidence) return;
    BK_NET.offlineEvidence = false;
    bkSyncRefreshBadge(); /* the pill can never stay stuck on "offline" */
}
function bkMarkOffline() { BK_NET.offlineEvidence = true; }

/* Never let a bug inside a UI callback masquerade as a network failure. */
function bkSafe(fn, a, b) {
    try { if (fn) fn(a, b); }
    catch (e) { if (window.console && console.error) console.error('[booksy] callback failed', e); }
}

function bkSendJob(job) {
    var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF };
    headers['Content-Type'] = job.bodyType === 'json'
        ? 'application/json'
        : 'application/x-www-form-urlencoded;charset=UTF-8';

    /* Two-argument then(): the reject handler sees ONLY the fetch failure.
       An error thrown while handling the response cannot reach it. */
    return fetch(job.url, { method: 'POST', headers: headers, body: job.body })
        .then(function (r) {
            bkMarkOnline(); /* it replied — we are demonstrably connected */
            return r.json().catch(function () { return null; }).then(function (json) {
                return { status: r.status, ok: r.ok, json: json };
            });
        }, function (err) {
            bkMarkOffline();
            throw { network: true, cause: err };
        });
}

/* queue + always make sure something will come back for it */
function bkQueue(job) { bkOutboxAdd(job); bkScheduleSync(); }

var bkSyncTimer = null, bkSyncing = false;
function bkSyncNow() {
    if (bkSyncing) return;
    var jobs = bkOutbox();
    if (!jobs.length) { bkSetSync('idle'); return; }

    var now = Date.now();
    var due = jobs.filter(function (j) { return (j.nextAt || 0) <= now && (j.tries || 0) < BK_MAX_TRIES; });
    if (!due.length) { bkSyncRefreshBadge(); bkScheduleSync(); return; }

    bkSyncing = true;
    if (!bkIsOffline()) bkSetSync('syncing', jobs.length);
    var job = due[0];

    var landed = false; /* did this attempt actually book something? */
    bkSendJob(job)
        .then(function (res) {
            if (res.ok && res.json && res.json.ok) {
                bkOutboxDrop(job.id);            /* success (or a replay of it) */
                landed = true;
                return;
            }
            /* still processing on the server — retry this exact key shortly */
            if (res.status === 409 && res.json && res.json.code === 'in_progress') throw { retry: true };
            /* 4xx that will never succeed (busy slot, validation) — stop retrying,
               and say so rather than silently dropping the user's booking */
            if (res.status >= 400 && res.status < 500) {
                bkOutboxDrop(job.id);
                bkToast(BK_SYNC_TXT.dropped.replace(':m', (res.json && res.json.message) || ''), 'error');
                return;
            }
            throw { retry: true }; /* 5xx */
        }, function () {
            /* Network failure. Being offline is not the job's fault, so it must
               not eat the retry budget — otherwise a ten-minute coffee break
               would mark perfectly good bookings as "failed". Wait on a gentle
               fixed cadence instead and keep trying. */
            job.netTries = (job.netTries || 0) + 1;
            job.nextAt   = Date.now() + Math.min(5000 * job.netTries, 30000);
            bkOutboxUpdate(job);
            throw { handled: true };
        })
        .catch(function (e) {
            if (e && e.handled) return;      /* already accounted for above */
            /* a real attempt that the server rejected/stumbled on */
            job.tries  = (job.tries || 0) + 1;
            job.nextAt = Date.now() + bkBackoff(job.tries);
            bkOutboxUpdate(job);
        })
        .finally(function () {
            bkSyncing = false;
            var left = bkPendingCount();
            if (landed) { /* only repaint when the calendar actually changed */
                loadStaffView();
                listLoaded = false;
                calRefetch();
            }
            if (!left) {
                if (landed) { bkSetSync('saved'); bkToast(BK_SYNC_TXT.synced, 'success'); }
                else        { bkSetSync('idle'); }
            } else {
                bkSyncRefreshBadge();
                bkScheduleSync();
            }
        });
}
function bkScheduleSync() {
    clearTimeout(bkSyncTimer);
    var jobs = bkOutbox().filter(function (j) { return j.tries < BK_MAX_TRIES; });
    if (!jobs.length) return;
    var soonest = Math.min.apply(null, jobs.map(function (j) { return j.nextAt || 0; }));
    bkSyncTimer = setTimeout(bkSyncNow, Math.max(soonest - Date.now(), 800));
}

/* ── submit path shared by single + group booking ──────────────
   Returns true when the write was parked for later instead of sent. */
/**
 * Send a write and report the outcome.
 *
 * onSettle ALWAYS runs, exactly once, whatever happens — including when a
 * handler above it throws. The caller's spinner is reset from there and
 * nowhere else, so no code path can leave the UI stuck on "Saving…".
 */
function bkSubmit(job, onDone, onFail, onSettle) {
    /* Always try. navigator.onLine saying "false" is not trustworthy enough to
       skip a request the user asked for — if it really is down, fetch fails
       immediately anyway, and the outcome tells us the truth. */
    bkSetSync('saving');

    return bkSendJob(job).then(function (res) {
        /* success — a failure inside onDone is a UI bug, NOT a network problem,
           so it must not re-queue the booking or claim we are offline */
        if (res.ok && res.json && res.json.ok) {
            bkSafe(bkSetSync, 'saved');
            bkSafe(onDone, res.json);
            return;
        }
        /* the server is still finishing the original attempt — retry that key */
        if (res.status === 409 && res.json && res.json.code === 'in_progress') {
            bkQueue(job);
            bkSafe(bkSetSync, 'syncing', bkPendingCount());
            bkSafe(onFail, null, true);
            return;
        }
        /* a 4xx will never succeed on retry — surface it, don't queue it */
        if (res.status >= 400 && res.status < 500) {
            bkSafe(bkSetSync, 'idle');
            bkSafe(onFail, (res.json && res.json.message) || null, false);
            return;
        }
        /* 5xx — the server stumbled; that is not the user's connection */
        bkQueue(job);
        bkSafe(bkSetSync, 'syncing', bkPendingCount());
        bkSafe(onFail, null, true);
    }, function () {
        /* genuine network failure — the only case that means "offline".
           The idempotency key makes the replay safe even if the server
           actually did process the request we never heard back from. */
        bkQueue(job);
        bkSafe(bkSetSync, 'offline', bkPendingCount());
        bkToast(BK_SYNC_TXT.queued, 'info');
        bkSafe(onFail, null, true);
    })
    .catch(function (e) {
        /* Nothing above should throw — but if it ever does, the promise must not
           die silently and strand the caller mid-save. */
        if (window.console && console.error) console.error('[booksy] submit failed unexpectedly', e);
        bkSafe(onFail, null, false);
    })
    .then(function () {
        bkSafe(onSettle); /* the guarantee: runs on every path */
    });
}

/* ── draft autosave / restore ─────────────────────────────────── */
var bkDraftT = null;
function qaTouch() {
    clearTimeout(bkDraftT);
    bkDraftT = setTimeout(bkDraftWrite, 400);
}
function bkDraftHasWork() {
    return typeof qaGuests !== 'undefined' && qaGuests.some(function (g) { return g.cart.length; });
}
function bkDraftWrite() {
    if (!bkDraftHasWork()) { bkStoreDel(BK_DRAFT_KEY); return; }
    bkStoreSet(BK_DRAFT_KEY, { v: 2, ctx: qaCtx, guests: qaGuests, savedAt: Date.now() });
}
function bkDraftClear() { clearTimeout(bkDraftT); bkStoreDel(BK_DRAFT_KEY); bkResumeHide(); }

function bkResumeHide() { var b = document.getElementById('bk-resume'); if (b) b.classList.add('d-none'); }
function bkResumeShow() {
    var d = bkStoreGet(BK_DRAFT_KEY, null);
    if (!d || !d.guests || !d.guests.some(function (g) { return g.cart && g.cart.length; })) return;
    var box = document.getElementById('bk-resume');
    var sub = document.getElementById('bk-resume-sub');
    if (!box) return;
    var n = d.guests.reduce(function (a, g) { return a + (g.cart ? g.cart.length : 0); }, 0);
    var when = d.ctx ? (d.ctx.dateStr + ' ' + _fmtMinutes(d.ctx.minutes)) : '';
    sub.textContent = n + ' × ' + QA_TXT.client.toLowerCase() + (when ? ' · ' + when : '');
    box.classList.remove('d-none');
}

/* ── wiring ───────────────────────────────────────────────────── */
window.addEventListener('online',  function () {
    BK_NET.offlineEvidence = false; /* the browser says the interface is back */
    bkSyncRefreshBadge();
    bkSyncNow();
});
window.addEventListener('offline', function () {
    /* only a hint — but a request already in flight will settle it either way */
    bkSyncRefreshBadge();
});
document.addEventListener('visibilitychange', function () {
    if (document.hidden) return;
    /* back from sleep: navigator.onLine can still be stale here, so re-check by
       actually trying, and correct the pill from the outcome */
    if (bkPendingCount()) bkSyncNow();
    else if (!bkOnlineHint()) bkSyncRefreshBadge();
});
window.addEventListener('beforeunload', function (e) {
    if (!bkDraftHasWork() && !bkPendingCount()) return;
    bkDraftWrite();          /* last-moment flush of the debounce */
    e.preventDefault();
    e.returnValue = BK_SYNC_TXT.leave;
    return BK_SYNC_TXT.leave;
});
/* pagehide fires where beforeunload doesn't (mobile Safari, tab discard) */
window.addEventListener('pagehide', function () { if (bkDraftHasWork()) bkDraftWrite(); });

document.getElementById('bk-sync-retry').addEventListener('click', function () {
    bkOutboxSet(bkOutbox().map(function (j) { j.tries = 0; j.nextAt = 0; return j; }));
    bkSyncNow();
});
document.getElementById('bk-resume-go').addEventListener('click', function () {
    var d = bkStoreGet(BK_DRAFT_KEY, null);
    bkResumeHide();
    if (!d) return;
    if (d.ctx) qaOpen(d.ctx, { restore: true });
    else bkQuickAdd(null, null, '', '', { restore: true });
});
document.getElementById('bk-resume-x').addEventListener('click', bkDraftClear);

/* pick up anything left by a crash / close / dead connection.
   Nothing pending means nothing to say — the pill stays hidden rather than
   announcing a connection state the user did not ask about. */
bkResumeShow();
if (bkPendingCount()) { bkSyncRefreshBadge(); bkSyncNow(); }

/* ════════════════════════════════
   VIEW SWITCHING
════════════════════════════════ */
function switchView(name) {
    var views = { cal: 'view-cal', staff: 'view-staff', list: 'view-list' };
    Object.keys(views).forEach(function (k) {
        document.getElementById(views[k]).classList.toggle('d-none', k !== name);
        document.getElementById('tab-' + k).classList.toggle('active', k === name);
    });
    if (name === 'list')  { loadListView(); }
    if (name === 'staff') loadStaffView();
    if (name === 'cal') {
        ensureCalRendered();
        setTimeout(function () { calendar.updateSize(); }, 50);
    }
}
window.switchView = switchView;

/* Let the add-menu features (block time / waitlist) refresh whichever views exist */
window.bkRefreshViews = function () {
    calRefetch();
    if (!document.getElementById('view-staff').classList.contains('d-none')) loadStaffView();
};

/* ════════════════════════════════
   SHARED QUICK-ADD ENTRY POINT
   One opener behind every "book at this time" affordance — the calendar
   grid, the staff strip and the + Add menu — so they can never drift apart.
════════════════════════════════ */

/* When no slot was clicked, guess the most useful moment: the date the user
   is looking at, at the next quarter-hour if that's today, else 09:00. */
function bkQuickAddDefaultDate() {
    var base = new Date();
    try {
        if (calRendered && !document.getElementById('view-cal').classList.contains('d-none')) {
            base = calendar.getDate();
        } else if (sfDate) {
            base = new Date(sfDate);
        }
    } catch (e) { /* fall through to today */ }

    var now = new Date();
    var d   = new Date(base);
    if (d.toDateString() === now.toDateString()) {
        /* setMinutes(60) rolls into the next hour on its own */
        d.setHours(now.getHours(), Math.ceil(now.getMinutes() / 15) * 15, 0, 0);
    } else {
        d.setHours(9, 0, 0, 0);
    }
    return d;
}

function bkQuickAdd(date, empId, empName, colBranchId, opts) {
    var d        = date ? new Date(date) : bkQuickAddDefaultDate();
    var branchId = activeBranch || colBranchId || sfDefaultBranch || FIRST_BRANCH || '';

    /* No branch context at all — the full create page can still resolve it */
    if (!branchId) {
        var p = new URLSearchParams({ start_time: sfDateStr(d) + ' ' + _pad2(d.getHours()) + ':' + _pad2(d.getMinutes()) });
        if (empId) p.set('employee_id', empId);
        if (opts && opts.group) p.set('group', '1');
        location.href = CREATE_URL + '?' + p.toString();
        return;
    }

    qaOpen({
        empId:    empId || null,
        empName:  empName || '',
        branchId: branchId,
        minutes:  d.getHours() * 60 + d.getMinutes(),
        dateStr:  sfDateStr(d),
    }, opts);
}
function _pad2(n) { return String(n).padStart(2, '0'); }
window.bkQuickAdd = bkQuickAdd;

/* Staff (Fresha-style) view is the default */
switchView('staff');

/* Re-render the staff grid when crossing the mobile breakpoint */
var _lastMobile = window.innerWidth <= 768;
var _rszTimer = null;
window.addEventListener('resize', function(){
    clearTimeout(_rszTimer);
    _rszTimer = setTimeout(function(){
        var m = window.innerWidth <= 768;
        var crossed = m !== _lastMobile;
        _lastMobile = m;
        if (document.getElementById('view-staff').classList.contains('d-none')) return;
        if (crossed) { loadStaffView(); return; }
        /* on mobile, column widths depend on viewport → re-render from cache */
        if (m && sfView === 'day' && sfDayData) renderStaffGrid(sfDayData);
        else if (m && sfLastEvents) { if (sfView === 'month') renderMonthGrid(); else renderRangeGrid(); }
    }, 250);
});

/* Fold the sidebar while the calendar is displayed (Fresha-style full width) */
if (window.innerWidth >= 992) {
    document.body.classList.add('sidebar-folded');
    var _sbTog = document.querySelector('.sidebar-header .sidebar-toggler');
    if (_sbTog) { _sbTog.classList.add('active'); _sbTog.classList.remove('not-active'); }
}

/* ════════════════════════════════
   HELPERS
════════════════════════════════ */
function _esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function _initials(n) {
    return (n || '').trim().split(/\s+/).map(w => w[0] || '').join('').toUpperCase().slice(0, 2);
}
function _fmtTime(d) {
    if (!d) return '';
    return d.toLocaleTimeString(IS_RTL ? 'ar-SA' : 'en-US', { hour:'2-digit', minute:'2-digit', hour12:true });
}
function _hashStr(s) {
    var h = 0;
    for (var i = 0; i < s.length; i++) h = (Math.imul(31, h) + s.charCodeAt(i)) | 0;
    return h;
}
function _fmtMinutes(totalMin) {
    var h   = Math.floor(totalMin / 60);
    var m   = totalMin % 60;
    var h12 = h % 12 || 12;
    var ap  = h < 12 ? (IS_RTL ? 'ص' : 'AM') : (IS_RTL ? 'م' : 'PM');
    return h12 + (m ? ':' + String(m).padStart(2,'0') : '') + ' ' + ap;
}

/* Auto-refresh the "in 20 min" style labels.
   Registered through bkInterval so bkTeardown can stop it; it also stands down
   while the tab is hidden, where re-rendering rows nobody is looking at only
   burns battery. */
bkInterval(function() {
    if (document.hidden) return;
    if (!document.getElementById('view-list').classList.contains('d-none') && listLoaded) {
        renderListRows();
    }
}, 30000);

/* Coming back to a backgrounded tab should show fresh labels immediately
   rather than up to 30s of stale ones. */
document.addEventListener('visibilitychange', function () {
    if (document.hidden) return;
    if (!document.getElementById('view-list').classList.contains('d-none') && listLoaded) {
        renderListRows();
    }
});

})();

