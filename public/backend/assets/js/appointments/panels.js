/**
 * Add-menu panels: block time, waitlist drawer, group booking entry point.
 *
 * A separate IIFE from appointments.js because it always was one — the two
 * blocks never shared a closure, only window exports.
 *
 * Publishes to window: bkUnblock.
 */
(function () {
'use strict';

var CSRF        = document.querySelector('meta[name="csrf-token"]').content;
var IS_RTL      = BK.isRtl;
var BLK_STORE   = BK.routes.blockedTimesStore;
var BLK_LIST    = BK.routes.blockedTimesIndex;
var BLK_DEL     = BK.routes.blockedTimesDestroy;
var WL_LIST     = BK.routes.waitlistIndex;
var WL_STORE    = BK.routes.waitlistStore;
var WL_RESOLVE  = BK.routes.waitlistResolve;
var BRANCH_DATA = BK.routes.appointmentsBranchData;
var CREATE_URL  = BK.routes.appointmentsCreate;

function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function jfetch(url, opts) {
    opts = opts || {};
    opts.headers = Object.assign({
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json'
    }, opts.headers || {});
    return fetch(url, opts).then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, data: j }; });
    });
}
function refreshViews() { if (window.bkRefreshViews) window.bkRefreshViews(); }

/* ════ Add menu ════ */
var addBtn  = document.getElementById('bk-add-btn');
var addMenu = document.getElementById('bk-add-menu');
addBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    var open = !addMenu.classList.contains('d-none');
    addMenu.classList.toggle('d-none', open);
    addBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
});
document.addEventListener('click', function (e) {
    if (!e.target.closest('.bk-add-wrap')) {
        addMenu.classList.add('d-none');
        addBtn.setAttribute('aria-expanded', 'false');
    }
});
addMenu.addEventListener('click', function (e) {
    var item = e.target.closest('[data-bk-add]');
    if (!item) return;
    addMenu.classList.add('d-none');
    if (item.dataset.bkAdd === 'block')    openBlockModal();
    if (item.dataset.bkAdd === 'waitlist') openWaitlist(true);
    if (item.dataset.bkAdd === 'booking' || item.dataset.bkAdd === 'group') {
        /* open the in-page drawer; only fall back to the full page if it's missing */
        var asGroup = item.dataset.bkAdd === 'group';
        if (window.bkQuickAdd) window.bkQuickAdd(null, null, '', '', { group: asGroup });
        else location.href = CREATE_URL + (asGroup ? '?group=1' : '');
    }
});

/* ════ Block-time modal ════ */
var blkOv = document.getElementById('bk-block-ov');

function openBlockModal() {
    blkOv.classList.remove('d-none');
    loadBlockEmployees();
    loadBlockList();
}
document.querySelectorAll('[data-bk-close]').forEach(function (b) {
    b.addEventListener('click', function () {
        document.getElementById(this.dataset.bkClose).classList.add('d-none');
    });
});
blkOv.addEventListener('click', function (e) { if (e.target === blkOv) blkOv.classList.add('d-none'); });

function loadBlockEmployees() {
    var sel = document.getElementById('blk-employee');
    var branchId = document.getElementById('blk-branch').value;
    jfetch(BRANCH_DATA + '?branch_id=' + branchId).then(function (res) {
        var opts = '<option value="">' + (IS_RTL ? 'كل الفرع' : 'Whole branch') + '</option>';
        (res.data.employees || []).forEach(function (e) {
            opts += '<option value="' + e.id + '">' + esc(e.name) + '</option>';
        });
        sel.innerHTML = opts;
    });
}
function loadBlockList() {
    var box = document.getElementById('blk-list');
    var p = new URLSearchParams({
        date: document.getElementById('blk-date').value,
        branch_id: document.getElementById('blk-branch').value
    });
    jfetch(BLK_LIST + '?' + p).then(function (res) {
        var rows = (res.data.blocks || []);
        if (!rows.length) {
            box.innerHTML = '<div class="bk-ov-empty">' + (IS_RTL ? 'لا يوجد أوقات محجوبة' : 'No blocked times') + '</div>';
            return;
        }
        box.innerHTML = rows.map(function (b) {
            return '<div class="bk-blk-row">'
                + '<div class="bk-blk-info">'
                + '<b>' + esc(b.from) + ' – ' + esc(b.to) + '</b>'
                + '<span>' + esc(b.employee || (IS_RTL ? 'كل الفرع' : 'Whole branch'))
                + (b.reason ? ' · ' + esc(b.reason) : '') + '</span>'
                + '</div>'
                + '<button type="button" class="bk-blk-del" data-id="' + b.id + '" aria-label="unblock">'
                + '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>'
                + '</button></div>';
        }).join('');
    });
}
document.getElementById('blk-branch').addEventListener('change', function () { loadBlockEmployees(); loadBlockList(); });
document.getElementById('blk-date').addEventListener('change', loadBlockList);

document.getElementById('blk-save').addEventListener('click', function () {
    var btn = this;
    var err = document.getElementById('blk-err');
    err.classList.add('d-none');
    btn.disabled = true;
    var body = new FormData();
    body.append('branch_id',   document.getElementById('blk-branch').value);
    body.append('employee_id', document.getElementById('blk-employee').value);
    body.append('date',        document.getElementById('blk-date').value);
    body.append('from',        document.getElementById('blk-from').value);
    body.append('to',          document.getElementById('blk-to').value);
    body.append('reason',      document.getElementById('blk-reason').value);
    jfetch(BLK_STORE, { method: 'POST', body: body }).then(function (res) {
        btn.disabled = false;
        if (!res.ok) {
            var msg = res.data.message || (IS_RTL ? 'تعذّر الحفظ' : 'Could not save');
            err.textContent = msg;
            err.classList.remove('d-none');
            return;
        }
        document.getElementById('blk-reason').value = '';
        loadBlockList();
        refreshViews();
    });
});

/* delete from modal list */
document.getElementById('blk-list').addEventListener('click', function (e) {
    var del = e.target.closest('.bk-blk-del');
    if (!del) return;
    unblock(del.dataset.id, null, loadBlockList);
});

function unblock(id, title, done) {
    var q = IS_RTL ? 'إلغاء حجب هذا الوقت؟' : 'Unblock this time?';
    if (title) q += '\n' + title;
    if (!confirm(q)) return;
    jfetch(BLK_DEL.replace('__ID__', id), { method: 'DELETE' }).then(function (res) {
        if (res.ok) { if (done) done(); refreshViews(); }
    });
}
/* calendar eventClick hook */
window.bkUnblock = function (id, title) { unblock(id, title); };

/* ════ Waitlist drawer ════ */
var wlDrawer = document.getElementById('bk-wl-drawer');
var wlOv     = document.getElementById('bk-wl-ov');

function openWaitlist(focusForm) {
    wlDrawer.classList.add('open');
    wlOv.classList.remove('d-none');
    wlDrawer.setAttribute('aria-hidden', 'false');
    loadWlServices();
    loadWaitlist();
    /* Focus the one field that starts the flow. 250ms lets the drawer finish
       sliding first — focusing mid-transition makes the caret jump. */
    if (focusForm) setTimeout(function () { wlSearch.focus(); }, 250);
}
function closeWaitlist() {
    wlDrawer.classList.remove('open');
    wlOv.classList.add('d-none');
    wlDrawer.setAttribute('aria-hidden', 'true');
}
document.getElementById('bk-wl-chip').addEventListener('click', function () { openWaitlist(false); });
document.getElementById('bk-wl-close').addEventListener('click', closeWaitlist);
wlOv.addEventListener('click', closeWaitlist);
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeWaitlist(); blkOv.classList.add('d-none'); }
});

function setWlCount(n) {
    document.getElementById('bk-wl-count').textContent  = n;
    document.getElementById('bk-wl-count2').textContent = n;
    document.getElementById('bk-wl-chip').classList.toggle('has-waiting', n > 0);
}

function loadWlServices() {
    var sel = document.getElementById('wl-service');
    jfetch(BRANCH_DATA + '?branch_id=' + document.getElementById('wl-branch').value).then(function (res) {
        var opts = '<option value="">' + (IS_RTL ? 'أي خدمة' : 'Any service') + '</option>';
        (res.data.services || []).forEach(function (s) {
            opts += '<option value="' + s.id + '">' + esc(s.name) + '</option>';
        });
        sel.innerHTML = opts;
    });
}
document.getElementById('wl-branch').addEventListener('change', loadWlServices);

/* ── tier badge ───────────────────────────────────────────────────────────
   Icon + text, never colour alone: a colour-blind user must still be able to
   tell a VIP from a new face. The SVG path comes from App\Enums\CustomerTier
   so PHP and JS can never disagree on what a tier looks like. */
function wlTierBadge(tier) {
    if (!tier) return '';
    return '<span class="wl-tier" style="--tier:' + tier.color + '">'
        + '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        + 'stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        + tier.icon + '</svg>'
        + esc(tier.label) + '</span>';
}

/** "waiting 12 min" / "waiting 2 h" — the number reception actually acts on. */
function wlWaited(mins) {
    if (mins >= 60) return BK.t.wl_waiting_hours.replace(':n', Math.floor(mins / 60));
    return BK.t.wl_waiting_for.replace(':n', Math.max(0, mins));
}

function loadWaitlist() {
    jfetch(WL_LIST).then(function (res) {
        var rows = res.data.entries || [];
        setWlCount(rows.length);
        var box = document.getElementById('bk-wl-list');

        if (!rows.length) {
            box.innerHTML = '<div class="wl-empty">'
                + '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">'
                + '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
                + '<b>' + esc(BK.t.wl_empty) + '</b>'
                + '<span>' + esc(BK.t.wl_empty_hint) + '</span>'
                + '</div>';
            return;
        }

        box.innerHTML = rows.map(function (w) {
            var prio = (BK.priorities || []).filter(function (p) { return p.value === w.priority; })[0];

            return '<article class="wl-item" data-id="' + w.id + '" data-prio="' + w.priority + '"'
                + (prio ? ' style="--prio:' + prio.color + '"' : '') + '>'

                + '<div class="wl-item-body">'
                +   '<div class="wl-item-top">'
                +     '<b class="wl-item-name">' + esc(w.name) + '</b>'
                +     wlTierBadge(w.tier)
                +   '</div>'
                +   '<div class="wl-item-meta">'
                +     esc(w.service || BK.t.wl_any_service)
                +     (w.minutes ? ' · ' + w.minutes + ' ' + esc(BK.t.min) : '')
                +     (w.employee ? ' · ' + esc(w.employee) : '')
                +   '</div>'
                +   '<div class="wl-item-sub">'
                +     '<span class="wl-waited">' + esc(wlWaited(w.waited)) + '</span>'
                +     (prio && w.priority !== 2 ? '<span class="wl-prio-tag">' + esc(prio.label) + '</span>' : '')
                +   '</div>'
                +   (w.notes ? '<div class="wl-item-notes">' + esc(w.notes) + '</div>' : '')
                + '</div>'

                + '<div class="wl-item-actions">'
                +   '<a class="wl-book" href="' + CREATE_URL + '?branch_id=' + w.branchId
                +     (w.serviceId ? '&service_id=' + w.serviceId : '')
                +     '&waitlist_id=' + w.id + '">' + esc(BK.t.wl_book_now) + '</a>'
                +   '<button type="button" class="wl-icon-btn" data-status="cancelled" '
                +     'aria-label="' + esc(BK.t.remove) + '" title="' + esc(BK.t.remove) + '">'
                +     '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">'
                +     '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                +   '</button>'
                + '</div></article>';
        }).join('');
    });
}

document.getElementById('bk-wl-list').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-status]');
    if (!btn) return;
    var item = btn.closest('.wl-item');
    var body = new FormData();
    body.append('_method', 'PATCH');
    body.append('status', btn.dataset.status);
    jfetch(WL_RESOLVE.replace('__ID__', item.dataset.id), { method: 'POST', body: body }).then(function (res) {
        if (res.ok) loadWaitlist();
    });
});

/* ══════════════════════════════════════════════════════════════════
   ADD FLOW

   One question at a time. "Who?" is the only thing on screen until it is
   answered, because priority, service and duration are all meaningless
   before you know whose they are.

   A name with no match is not a dead end — it becomes the quick-add path,
   so a walk-in never forces a detour through the customers page.
   ══════════════════════════════════════════════════════════════════ */
var wlSearch  = document.getElementById('wl-search');
var wlResults = document.getElementById('wl-results');
var wlPicked  = document.getElementById('wl-picked');
var wlNewBox  = document.getElementById('wl-newbox');
var wlRest    = document.getElementById('wl-rest');
var wlErr     = document.getElementById('wl-err');

/* null = nothing chosen; {id:null, name} = a new person being quick-added */
var wlChoice   = null;
var wlPriority = 2;
var wlSearchT  = null;
var wlSearchCtl = null;

function wlReset() {
    wlChoice = null;
    wlSearch.value = '';
    wlResults.classList.add('d-none');
    wlResults.innerHTML = '';
    wlSearch.setAttribute('aria-expanded', 'false');
    wlPicked.classList.add('d-none');
    wlPicked.innerHTML = '';
    wlNewBox.classList.add('d-none');
    document.getElementById('wl-clear').classList.add('d-none');
    document.getElementById('wl-search').closest('.wl-field').classList.remove('d-none');
    wlRest.hidden = true;
    wlErr.classList.add('d-none');
    ['wl-phone', 'wl-minutes', 'wl-notes'].forEach(function (id) {
        document.getElementById(id).value = '';
    });
    wlPriority = 2;
    wlRenderPriority();
}

/* ── priority: a segmented control, not a <select>.
   Three options the whole team must read the same way — showing them all at
   once costs one row and removes a tap plus a hidden list. ── */
function wlRenderPriority() {
    document.getElementById('wl-priority').innerHTML = (BK.priorities || []).map(function (p) {
        var on = p.value === wlPriority;
        return '<button type="button" role="radio" aria-checked="' + on + '" '
            + 'class="wl-seg-btn' + (on ? ' on' : '') + '" data-prio="' + p.value + '" '
            + 'style="--prio:' + p.color + '">'
            + '<span class="wl-seg-label">' + esc(p.label) + '</span>'
            + '<span class="wl-seg-hint">' + esc(p.hint) + '</span>'
            + '</button>';
    }).join('');
}

document.getElementById('wl-priority').addEventListener('click', function (e) {
    var b = e.target.closest('[data-prio]');
    if (!b) return;
    wlPriority = parseInt(b.dataset.prio, 10);
    wlRenderPriority();
});

/* ── search ── */
function wlRenderResults(list, typed) {
    var html = list.map(function (c) {
        return '<button type="button" class="wl-result" role="option" '
            + 'data-id="' + c.id + '" data-name="' + esc(c.name) + '" data-phone="' + esc(c.phone || '') + '" '
            + 'data-tier=\'' + JSON.stringify(c.tier).replace(/'/g, '&#39;') + '\'>'
            + '<span class="wl-result-main">'
            +   '<span class="wl-result-name">' + esc(c.name) + '</span>'
            +   (c.phone ? '<span class="wl-result-phone" dir="ltr">' + esc(c.phone) + '</span>' : '')
            + '</span>'
            + '<span class="wl-result-right">'
            +   wlTierBadge(c.tier)
            +   '<span class="wl-result-visits">'
            +     (c.visits ? BK.t.wl_visits.replace(':n', c.visits) : BK.t.wl_no_visits)
            +   '</span>'
            + '</span></button>';
    }).join('');

    /* No match is the common case for a walk-in — offer the shortcut instead
       of an apology. */
    if (typed) {
        html += '<button type="button" class="wl-result wl-result-new" data-new="1">'
            + '<span class="wl-result-plus">'
            + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">'
            + '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>'
            + esc(BK.t.wl_add_as_new.replace(':name', typed))
            + '</button>';
    }

    wlResults.innerHTML = html;
    wlResults.classList.remove('d-none');
    wlSearch.setAttribute('aria-expanded', 'true');
}

wlSearch.addEventListener('input', function () {
    var q = this.value.trim();
    document.getElementById('wl-clear').classList.toggle('d-none', q === '');

    clearTimeout(wlSearchT);
    if (wlSearchCtl) wlSearchCtl.abort();

    if (q.length < 2) {
        wlResults.classList.add('d-none');
        wlSearch.setAttribute('aria-expanded', 'false');
        return;
    }

    /* Debounced so typing a name is one request, not eight. */
    wlSearchT = setTimeout(function () {
        wlSearchCtl = new AbortController();
        fetch(BK.routes.customersSearchJson + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: wlSearchCtl.signal,
        })
            .then(function (r) { return r.json(); })
            .then(function (list) { wlRenderResults(list || [], q); })
            .catch(function (err) { if (err.name !== 'AbortError') wlRenderResults([], q); });
    }, 220);
});

document.getElementById('wl-clear').addEventListener('click', wlReset);

wlResults.addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b) return;

    if (b.dataset.new) {
        wlChoice = { id: null, name: wlSearch.value.trim(), phone: '', tier: null };
        wlNewBox.classList.remove('d-none');
        setTimeout(function () { document.getElementById('wl-phone').focus(); }, 120);
    } else {
        wlChoice = {
            id: b.dataset.id,
            name: b.dataset.name,
            phone: b.dataset.phone,
            tier: JSON.parse(b.dataset.tier),
        };
        wlNewBox.classList.add('d-none');
    }

    /* Collapse the search into a compact chip: the question is answered, so
       it should stop taking a whole field's worth of attention. */
    wlPicked.innerHTML = '<span class="wl-picked-who">'
        + '<b>' + esc(wlChoice.name) + '</b>'
        + (wlChoice.phone ? '<span dir="ltr">' + esc(wlChoice.phone) + '</span>' : '')
        + '</span>'
        + (wlChoice.tier ? wlTierBadge(wlChoice.tier) : '')
        + '<button type="button" class="wl-picked-change" id="wl-change">' + esc(BK.t.wl_change) + '</button>';
    wlPicked.classList.remove('d-none');
    wlSearch.closest('.wl-field').classList.add('d-none');
    wlResults.classList.add('d-none');

    wlRest.hidden = false;
    wlRenderPriority();
});

wlPicked.addEventListener('click', function (e) {
    if (e.target.closest('#wl-change')) wlReset();
});

document.getElementById('wl-save').addEventListener('click', function () {
    var btn = this;

    wlErr.classList.add('d-none');
    if (!wlChoice) {
        wlErr.textContent = BK.t.wl_pick_customer;
        wlErr.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.classList.add('busy');

    var body = new FormData();
    body.append('branch_id', document.getElementById('wl-branch').value);
    if (wlChoice.id) body.append('customer_id', wlChoice.id);
    body.append('customer_name',  wlChoice.name);
    body.append('customer_phone', wlChoice.phone || document.getElementById('wl-phone').value);
    body.append('service_id',     document.getElementById('wl-service').value);
    body.append('preferred_employee_id', document.getElementById('wl-employee').value);
    body.append('priority',       wlPriority);
    body.append('estimated_minutes', document.getElementById('wl-minutes').value);
    body.append('notes',          document.getElementById('wl-notes').value);

    jfetch(WL_STORE, { method: 'POST', body: body }).then(function (res) {
        btn.disabled = false;
        btn.classList.remove('busy');

        if (!res.ok) {
            wlErr.textContent = res.data.message || (IS_RTL ? 'تعذّر الحفظ' : 'Could not save');
            wlErr.classList.remove('d-none');
            return;
        }

        var msg = BK.t.wl_added + (res.data.createdCustomer ? ' ' + BK.t.wl_saved_customer : '');
        if (window.bkToast) window.bkToast(msg, 'success');

        wlReset();
        loadWaitlist();
        /* Straight back to the search field — reception usually has a queue,
           not a single person. */
        wlSearch.focus();
    });
});

/* count badge on page load (single light request) */
loadWaitlist();

})();

