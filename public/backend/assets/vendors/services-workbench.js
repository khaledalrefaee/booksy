/* ══════════════════════════════════════════════════════════════════════
   Services Workbench — client-side controller
   Instant, optimistic, reload-free management of a branch's service catalog.
   ══════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';
    var B = window.WB_BOOT;
    if (!B) return;

    // ── tiny utils ────────────────────────────────────────────────────
    var $  = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var feather = function () { if (window.feather) window.feather.replace(); };
    function esc(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function nf(n) { return Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
    var TX = Object.assign({
        internal: 'Internal', popular: 'Popular', recommended: 'Rec', sale: 'Sale',
        edit: 'Edit', duplicate: 'Duplicate', copyTo: 'Copy to branches…', del: 'Delete',
        showOnline: 'Show in online booking', hideOnline: 'Make internal only',
        h: 'h', m: 'm', standard: 'Standard', package: 'Package', membership: 'Membership',
        addon: 'Add-on', consultation: 'Consultation', optional: 'Optional', remove: 'Remove',
        undone: 'Restored.', changesApplied: 'Changes applied.',
        pickBranch: 'Pick at least one branch', chooseFile: 'Choose a file',
        categorySaved: 'Category saved', nameRequired: 'Name is required', noMatches: 'No matches',
        newCategory: 'New category', editCategory: 'Edit category',
        moveToCategory: 'Move to category', changePrice: 'Change price', changeDuration: 'Change duration',
        copied: 'copied', added: 'added', refreshed: 'refreshed', skipped: 'skipped', refreshing: 'Refreshing…',
        copyFailed: 'Copy failed. Refresh the page and try again.', nothingCopied: 'Nothing new to copy',
        mostRequested: 'Most requested', badgeNew: 'New', specialOffer: 'Special offer', premium: 'Premium'
    }, B.t || {});
    var T = function (k) { return TX[k] || k; };
    var curSym = function (code) { return (B.currencies[code] && B.currencies[code].symbol) || code; };

    var CAT_PALETTE = ['#0E7C82', '#3dbbd4', '#2bcf7e', '#f4a642', '#ec4899', '#8b5cf6', '#14b8a6', '#e53935'];

    // ── state ─────────────────────────────────────────────────────────
    var services   = (B.services || []).slice();
    var categories = (B.categories || []).slice();      // {id,name,sort}
    var employees  = B.employees || [];
    var resources  = B.resources || [];
    var branches   = B.branches || [];
    var state = { q: '', cat: '', type: '', status: '', view: 'list', collapsed: {} };
    var selection = {};                                 // id -> true
    var drawer = { mode: 'add', id: null, type: 'standard', emp: {}, res: {}, pkg: [], badges: {}, addonParents: {}, currency: B.defaultCurrency };
    var pendingDelete = null;                            // {svc, timer}

    // ── computed helpers ──────────────────────────────────────────────
    function catColor(id) {
        if (id == null) return '#8a94a6';
        var i = categories.findIndex(function (c) { return String(c.id) === String(id); });
        return CAT_PALETTE[(i < 0 ? 0 : i) % CAT_PALETTE.length];
    }
    function catName(id) {
        if (id == null) return TX.uncategorized || 'Uncategorized';
        var c = categories.find(function (x) { return String(x.id) === String(id); });
        return c ? c.name : (TX.uncategorized || 'Uncategorized');
    }
    function durLabel(min) {
        min = +min || 0; var h = Math.floor(min / 60), mm = min % 60, s = '';
        if (h > 0) s += h + TX.h + ' ';
        if (mm > 0) s += mm + TX.m;
        s = s.trim();
        return s || (min + ' ' + (TX.min || 'min'));
    }
    function priceLabel(s) {
        var from = nf(Math.round(s.price));
        if (s.price_type === 'from') return TX.from + ' ' + from;
        if (s.price_type === 'range') return from + ' – ' + nf(Math.round(s.price_to != null ? s.price_to : s.price));
        return from;
    }
    function hasDiscount(s) {
        if (!s.discount_type || !s.discount_value) return false;
        var now = Date.now();
        if (s.discount_starts_at && now < Date.parse(s.discount_starts_at)) return false;
        if (s.discount_ends_at && now > Date.parse(s.discount_ends_at)) return false;
        return true;
    }
    function finalPrice(s) {
        if (!hasDiscount(s)) return +s.price;
        var fp = s.discount_type === 'percent' ? s.price * (1 - s.discount_value / 100) : s.price - s.discount_value;
        return Math.max(0, fp);
    }
    function typeTag(t) {
        var map = { standard: ['tag-type', T('standard')], package: ['tag-pkg', T('package')], membership: ['tag-mem', T('membership')], addon: ['tag-add', T('addon')], consultation: ['tag-con', T('consultation')] };
        var e = map[t] || map.standard;
        if (t === 'standard') return '';   // don't clutter rows with the default type
        return '<span class="wb-tag ' + e[0] + '">' + esc(e[1]) + '</span>';
    }
    function svcById(id) { return services.find(function (s) { return String(s.id) === String(id); }); }

    // ── filtering ─────────────────────────────────────────────────────
    function filtered() {
        var q = state.q.trim().toLowerCase();
        return services.filter(function (s) {
            if (state.cat === 'none') { if (s.category_id != null) return false; }
            else if (state.cat !== '') { if (String(s.category_id) !== state.cat) return false; }
            if (state.type && s.service_type !== state.type) return false;
            if (state.status === 'active' && !s.is_active) return false;
            if (state.status === 'inactive' && s.is_active) return false;
            if (state.status === 'online' && !s.is_bookable_online) return false;
            if (state.status === 'sale' && !hasDiscount(s)) return false;
            if (q) {
                var hay = [s.name_en, s.name_ar, catName(s.category_id), priceLabel(s), durLabel(s.duration_minutes), s.service_type].join(' ').toLowerCase();
                if (hay.indexOf(q) < 0) return false;
            }
            return true;
        });
    }

    // group filtered services by category, ordered by category sort then uncategorized
    function grouped(list) {
        var groups = [];
        categories.slice().sort(function (a, b) { return (a.sort - b.sort) || a.name.localeCompare(b.name); })
            .forEach(function (c) {
                var items = list.filter(function (s) { return String(s.category_id) === String(c.id); })
                    .sort(function (a, b) { return (a.sort_order - b.sort_order) || (a.id - b.id); });
                if (items.length) groups.push({ key: String(c.id), id: c.id, name: c.name, items: items });
            });
        var un = list.filter(function (s) { return s.category_id == null; })
            .sort(function (a, b) { return (a.sort_order - b.sort_order) || (a.id - b.id); });
        if (un.length) groups.push({ key: 'none', id: null, name: TX.uncategorized || 'Uncategorized', items: un });
        return groups;
    }

    // ── rail ──────────────────────────────────────────────────────────
    function renderRail() {
        var el = $('#wb-rail-list'); if (!el) return;
        var counts = {}; var uncat = 0;
        services.forEach(function (s) { if (s.category_id == null) uncat++; else counts[s.category_id] = (counts[s.category_id] || 0) + 1; });
        var html = '';
        html += '<div class="wb-cat ' + (state.cat === '' ? 'active' : '') + '" data-cat=""><span class="dot" style="background:#8a94a6"></span><span class="nm">' + esc(T('all')) + '</span><span class="ct">' + services.length + '</span></div>';
        var sorted = categories.slice().sort(function (a, b) { return (a.sort - b.sort) || a.name.localeCompare(b.name); });
        sorted.forEach(function (c) {
            html += '<div class="wb-cat ' + (state.cat === String(c.id) ? 'active' : '') + '" data-cat="' + c.id + '" data-catrow="' + c.id + '">' +
                '<i data-feather="menu" class="grip"></i>' +
                '<span class="dot" style="background:' + catColor(c.id) + '"></span>' +
                '<span class="nm">' + esc(c.name) + '</span>' +
                '<span class="ct">' + (counts[c.id] || 0) + '</span>' +
                '<i data-feather="edit-2" class="wb-cat-edit" data-catedit="' + c.id + '" style="width:12px;height:12px;opacity:.4;margin-inline-start:2px;"></i>' +
                '</div>';
        });
        if (uncat) html += '<div class="wb-cat ' + (state.cat === 'none' ? 'active' : '') + '" data-cat="none"><span class="dot" style="background:#8a94a6"></span><span class="nm">' + esc(TX.uncategorized || 'Uncategorized') + '</span><span class="ct">' + uncat + '</span></div>';
        el.innerHTML = html;
        feather();
        initRailSort();
    }

    // ── list ──────────────────────────────────────────────────────────
    // Icons replace per-service images: one glyph per service type, tinted by category colour.
    var TYPE_ICON = { standard: 'scissors', package: 'gift', membership: 'award', addon: 'plus-circle', consultation: 'message-circle' };
    var BADGE_META = { most_requested: ['tag-pop', 'trending-up'], new: ['tag-mem', 'zap'], special_offer: ['tag-sale', 'tag'], premium: ['tag-rec', 'award'] };
    function typeIcon(t) { return TYPE_ICON[t] || 'scissors'; }
    function badgeLabel(b) { return T({ most_requested: 'mostRequested', new: 'badgeNew', special_offer: 'specialOffer', premium: 'premium' }[b] || b); }
    function badgeTags(s) {
        var out = typeTag(s.service_type)
            + (!s.is_bookable_online ? '<span class="wb-tag tag-off">' + esc(T('internal')) + '</span>' : '');
        (s.badges || []).forEach(function (b) { var m = BADGE_META[b]; if (m) out += '<span class="wb-tag ' + m[0] + '">' + esc(badgeLabel(b)) + '</span>'; });
        if (hasDiscount(s)) out += '<span class="wb-tag tag-sale">' + esc(T('sale')) + '</span>';
        return out;
    }
    function typeTile(s, size) {
        var col = catColor(s.category_id);
        return '<span class="wb-typeicon" style="background:' + col + '22;color:' + col + '">' +
            '<i data-feather="' + typeIcon(s.service_type) + '" style="width:' + (size || 15) + 'px;height:' + (size || 15) + 'px;"></i></span>';
    }
    function rowHtml(s) {
        var disc = hasDiscount(s), fp = finalPrice(s), sel = !!selection[s.id];
        var badges = badgeTags(s);
        var priceCell = disc
            ? '<span class="wb-price sale">' + nf(Math.round(fp)) + ' <span class="cur">' + esc(curSym(s.currency)) + '</span><span class="orig">' + nf(Math.round(s.price)) + '</span></span>'
            : '<span class="wb-price"><span class="' + (s.price_type === 'fixed' ? 'wb-cell-edit' : '') + '" data-edit="price" data-id="' + s.id + '">' + esc(priceLabel(s)) + '</span> <span class="cur">' + esc(curSym(s.currency)) + '</span></span>';
        return '<div class="wb-row' + (sel ? ' sel' : '') + '" data-id="' + s.id + '" data-catkey="' + (s.category_id == null ? 'none' : s.category_id) + '">' +
            '<span class="c-check"><input type="checkbox" class="wb-check" data-id="' + s.id + '"' + (sel ? ' checked' : '') + '></span>' +
            '<span class="c-grip"><i data-feather="menu" style="width:14px;height:14px;"></i></span>' +
            '<div class="c-name wb-name"><div class="t1">' +
                typeTile(s) +
                '<span class="nm wb-cell-edit" data-edit="name" data-id="' + s.id + '">' + esc(s.name_en || s.name_ar || '—') + '</span>' +
                '<span class="wb-badges">' + badges + '</span></div>' +
                (s.name_ar && s.name_en ? '<div class="ar">' + esc(s.name_ar) + '</div>' : '') +
            '</div>' +
            '<div class="c-cat wb-cat-cell">' + esc(catName(s.category_id)) + '</div>' +
            '<div class="c-price">' + priceCell + '</div>' +
            '<div class="c-dur"><span class="wb-dur wb-cell-edit" data-edit="duration" data-id="' + s.id + '"><i data-feather="clock" style="width:12px;height:12px;"></i>' + esc(durLabel(s.duration_minutes)) + '</span></div>' +
            '<span class="c-active"><span class="form-check form-switch mb-0"><input class="form-check-input wb-active" type="checkbox" data-id="' + s.id + '"' + (s.is_active ? ' checked' : '') + '></span></span>' +
            '<div class="c-actions"><div class="dropdown"><button class="wb-iconbtn" type="button" data-bs-toggle="dropdown"><i data-feather="more-vertical" style="width:17px;height:17px;"></i></button>' +
                '<ul class="dropdown-menu dropdown-menu-end shadow">' +
                    '<li><a class="dropdown-item" href="#" data-act="edit"><i data-feather="edit-2" style="width:14px;height:14px;" class="me-2"></i>' + esc(T('edit')) + '</a></li>' +
                    '<li><a class="dropdown-item" href="#" data-act="duplicate"><i data-feather="copy" style="width:14px;height:14px;" class="me-2"></i>' + esc(T('duplicate')) + '</a></li>' +
                    '<li><a class="dropdown-item" href="#" data-act="copy"><i data-feather="map-pin" style="width:14px;height:14px;" class="me-2"></i>' + esc(T('copyTo')) + '</a></li>' +
                    '<li><a class="dropdown-item" href="#" data-act="online">' + (s.is_bookable_online ? '<i data-feather="eye-off" style="width:14px;height:14px;" class="me-2"></i>' + esc(T('hideOnline')) : '<i data-feather="globe" style="width:14px;height:14px;" class="me-2"></i>' + esc(T('showOnline'))) + '</a></li>' +
                    '<li><hr class="dropdown-divider"></li>' +
                    '<li><a class="dropdown-item text-danger" href="#" data-act="delete"><i data-feather="trash-2" style="width:14px;height:14px;" class="me-2"></i>' + esc(T('del')) + '</a></li>' +
                '</ul></div></div>' +
        '</div>';
    }

    function renderList() {
        var list = filtered();
        var groups = grouped(list);
        var host = $('#wb-list'), cards = $('#wb-cards'), empty = $('#wb-empty');
        if (state.view === 'cards') { host.classList.add('wb-hide'); cards.classList.remove('wb-hide'); renderCards(list); }
        else { cards.classList.add('wb-hide'); host.classList.remove('wb-hide'); }

        if (!groups.length) { host.innerHTML = ''; if (cards) cards.innerHTML = ''; empty.classList.remove('wb-hide'); updateStats(); return; }
        empty.classList.add('wb-hide');

        if (state.view === 'list') {
            host.innerHTML = groups.map(function (g) {
                var collapsed = !!state.collapsed[g.key];
                return '<div class="wb-catblock" data-catblock="' + g.key + '">' +
                    '<div class="wb-cathead' + (collapsed ? ' collapsed' : '') + '" data-catkey="' + g.key + '">' +
                        '<span class="caret" data-caret="' + g.key + '"><i data-feather="chevron-down" style="width:16px;height:16px;"></i></span>' +
                        '<span class="dot" style="background:' + catColor(g.id) + '"></span>' +
                        '<span class="nm">' + esc(g.name) + '</span><span class="ct">' + g.items.length + '</span>' +
                    '</div>' +
                    '<div class="wb-rows" data-rows="' + g.key + '"' + (collapsed ? ' style="display:none"' : '') + '>' +
                        g.items.map(rowHtml).join('') +
                    '</div></div>';
            }).join('');
            feather();
            initListSort();
        }
        updateStats();
    }

    function cardHtml(s) {
        var disc = hasDiscount(s), fp = finalPrice(s), sel = !!selection[s.id];
        var col = catColor(s.category_id);
        return '<div class="wb-card' + (sel ? ' sel' : '') + '" data-id="' + s.id + '">' +
            '<div class="wb-card-icon" style="background:' + col + '18;color:' + col + '">' +
                '<i data-feather="' + typeIcon(s.service_type) + '" style="width:34px;height:34px;"></i>' +
                '<span style="position:absolute;top:8px;inset-inline-start:8px;"><input type="checkbox" class="wb-check" data-id="' + s.id + '"' + (sel ? ' checked' : '') + '></span>' +
                '<span style="position:absolute;top:8px;inset-inline-end:8px;display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;max-width:70%;">' + badgeTags(s) + '</span>' +
            '</div>' +
            '<div class="wb-card-body">' +
                '<div class="nm">' + esc(s.name_en || s.name_ar || '—') + '</div>' +
                '<div class="d-flex align-items-center justify-content-between">' +
                    '<span class="wb-price' + (disc ? ' sale' : '') + '">' + (disc ? nf(Math.round(fp)) : esc(priceLabel(s))) + ' <span class="cur">' + esc(curSym(s.currency)) + '</span></span>' +
                    '<span class="wb-dur"><i data-feather="clock" style="width:12px;height:12px;"></i>' + esc(durLabel(s.duration_minutes)) + '</span>' +
                '</div>' +
                '<div class="d-flex gap-2 mt-1">' +
                    '<button class="btn btn-sm btn-outline-primary rounded-pill flex-fill" data-act="edit" data-id="' + s.id + '"><i data-feather="edit-2" style="width:13px;height:13px;"></i></button>' +
                    '<button class="btn btn-sm btn-outline-secondary rounded-pill" data-act="duplicate" data-id="' + s.id + '"><i data-feather="copy" style="width:13px;height:13px;"></i></button>' +
                    '<button class="btn btn-sm btn-outline-danger rounded-pill" data-act="delete" data-id="' + s.id + '"><i data-feather="trash-2" style="width:13px;height:13px;"></i></button>' +
                '</div>' +
            '</div></div>';
    }
    function renderCards(list) {
        var cards = $('#wb-cards');
        cards.innerHTML = list.slice().sort(function (a, b) { return (a.sort_order - b.sort_order) || (a.id - b.id); }).map(cardHtml).join('');
        feather();
    }

    // ── stats ─────────────────────────────────────────────────────────
    function updateStats() {
        var total = services.length,
            active = services.filter(function (s) { return s.is_active; }).length,
            online = services.filter(function (s) { return s.is_bookable_online; }).length,
            sale = services.filter(hasDiscount).length;
        set('#wb-stat-total', total); set('#wb-stat-active', active); set('#wb-stat-online', online); set('#wb-stat-sale', sale);
        bar('#wb-stat-active-bar', active, total); bar('#wb-stat-online-bar', online, total); bar('#wb-stat-sale-bar', sale, total);
        function set(sel, v) { var e = $(sel); if (e) e.textContent = v; }
        function bar(sel, v, t) { var e = $(sel); if (e) e.style.width = (t > 0 ? Math.round(v / t * 100) : 0) + '%'; }
    }

    // ── toast ─────────────────────────────────────────────────────────
    var toastTimer;
    function toast(msg, type, undo) {
        var t = $('#wb-toast'), inner = $('#wb-toast-inner'), msgEl = $('#wb-toast-msg'), ic = $('#wb-toast-icon'), ub = $('#wb-toast-undo');
        if (!t) return;
        var pal = { success: ['#2bcf7e', 'check-circle'], danger: ['#e53935', 'trash-2'], warning: ['#f4a642', 'alert-circle'], info: ['#3dbbd4', 'info'] }[type] || ['#2bcf7e', 'check-circle'];
        inner.style.background = pal[0]; inner.style.color = '#fff';
        msgEl.textContent = msg;
        ic.setAttribute('data-feather', pal[1]); ic.style.color = '#fff';
        if (undo) { ub.classList.remove('wb-hide'); ub.onclick = function () { undo(); t.style.opacity = '0'; }; }
        else ub.classList.add('wb-hide');
        feather();
        t.style.display = 'block';
        clearTimeout(toastTimer); requestAnimationFrame(function () { t.style.opacity = '1'; });
        toastTimer = setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.style.display = 'none'; }, 260); }, undo ? 5200 : 3000);
    }

    // ── network ───────────────────────────────────────────────────────
    function toFD(obj) {
        var fd = new FormData();
        function add(k, v) {
            if (v === null || v === undefined) return;
            if (v instanceof File) fd.append(k, v);
            else if (Array.isArray(v)) v.forEach(function (iv, i) { add(k + '[' + i + ']', iv); });
            else if (typeof v === 'object') Object.keys(v).forEach(function (kk) { add(k + '[' + kk + ']', v[kk]); });
            else fd.append(k, v);
        }
        Object.keys(obj).forEach(function (k) { add(k, obj[k]); });
        return fd;
    }
    function post(url, obj) {
        return fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': B.csrf, 'Accept': 'application/json' }, body: obj instanceof FormData ? obj : toFD(obj) })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, status: r.status, data: d }; }); });
    }

    // ── model mutations + optimistic patching ─────────────────────────
    function upsert(svc) {
        var i = services.findIndex(function (s) { return String(s.id) === String(svc.id); });
        if (i < 0) services.push(svc); else services[i] = svc;
    }
    function removeLocal(id) { services = services.filter(function (s) { return String(s.id) !== String(id); }); delete selection[id]; }
    function flashRow(id) { var r = $('.wb-row[data-id="' + id + '"]'); if (r) { r.classList.remove('flash'); void r.offsetWidth; r.classList.add('flash'); } }

    function rerender() { renderRail(); renderList(); syncBulkBar(); }

    // ── optimistic: active / online toggles ───────────────────────────
    function toggleField(id, field) {
        var s = svcById(id); if (!s) return;
        var prev = s[field]; s[field] = !s[field];
        renderList();
        post(B.urls.updateBase + '/' + id + '/toggle-active', { _method: 'PATCH', field: field })
            .then(function (res) {
                if (!res.ok) throw 0;
                s.is_active = res.data.is_active; s.is_bookable_online = res.data.is_bookable_online;
                renderList(); renderRail();
            })
            .catch(function () { s[field] = prev; renderList(); toast(TX.genericError, 'danger'); });
    }

    // ── duplicate ─────────────────────────────────────────────────────
    function duplicate(id) {
        post(B.urls.updateBase + '/' + id + '/duplicate', {})
            .then(function (res) {
                if (!res.ok || !res.data.service) throw 0;
                upsert(res.data.service); rerender(); flashRow(res.data.service.id);
                toast(TX.duplicated, 'success');
            })
            .catch(function () { toast(TX.genericError, 'danger'); });
    }

    // ── delete (with undo) ────────────────────────────────────────────
    function askDelete(id) {
        var s = svcById(id); if (!s) return;
        $('#wb-delete-name').textContent = '"' + (s.name_en || s.name_ar || '') + '"';
        cleanupOverlays();
        var m = bootstrap.Modal.getOrCreateInstance($('#wb-delete-modal'));
        var btn = $('#wb-delete-confirm');
        btn.onclick = function () { m.hide(); doDelete(id); };
        m.show();
    }
    function doDelete(id) {
        var s = svcById(id); if (!s) return;
        removeLocal(id); rerender();
        var timer = setTimeout(function () {
            post(B.urls.updateBase + '/' + id, { _method: 'DELETE' }).catch(function () {});
            pendingDelete = null;
        }, 5000);
        pendingDelete = { svc: s, timer: timer };
        toast(TX.deleted, 'danger', function () {
            clearTimeout(timer); pendingDelete = null; upsert(s); rerender(); flashRow(id); toast(TX.undone, 'info');
        });
    }

    // ── inline edit ───────────────────────────────────────────────────
    function startInline(cell) {
        var id = cell.getAttribute('data-id'), field = cell.getAttribute('data-edit');
        var s = svcById(id); if (!s) return;
        if (cell.querySelector('input')) return;
        var val = field === 'name' ? (s.name_en || '') : field === 'price' ? s.price : s.duration_minutes;
        var input = document.createElement('input');
        input.className = 'wb-inline-input';
        input.type = field === 'name' ? 'text' : 'number';
        if (field !== 'name') { input.min = field === 'duration' ? 1 : 0; input.step = field === 'duration' ? 1 : 0.01; }
        input.value = val;
        var orig = cell.innerHTML; cell.innerHTML = ''; cell.appendChild(input); input.focus(); input.select();
        var done = false;
        function commit(save) {
            if (done) return; done = true;
            var nv = input.value;
            if (!save) { renderList(); return; }
            if (field === 'name') { if (!nv.trim()) { renderList(); return; } s.name_en = nv.trim(); }
            else if (field === 'price') { s.price = Math.max(0, parseFloat(nv) || 0); s.price_label = priceLabel(s); }
            else { s.duration_minutes = Math.max(1, Math.min(1440, parseInt(nv, 10) || 1)); }
            renderList(); flashRow(id);
            saveScalar(s);
        }
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); commit(true); } else if (e.key === 'Escape') { commit(false); } });
        input.addEventListener('blur', function () { commit(true); });
    }
    // Inline edit: resend the full current state so nothing is wiped. Relations
    // (employees/resources/package/add-on) are left out on purpose — the controller
    // only touches those when their keys are present, so they stay intact.
    function saveScalar(s) {
        var payload = {
            _method: 'PUT', service_type: s.service_type, service_category_id: s.category_id || '',
            name_en: s.name_en || '', name_ar: s.name_ar || '', description: s.description || '',
            price: s.price, price_type: s.price_type, price_to: s.price_to != null ? s.price_to : '',
            currency: s.currency, duration_minutes: s.duration_minutes,
            is_active: s.is_active ? 1 : 0, is_bookable_online: s.is_bookable_online ? 1 : 0,
            badges: s.badges || [], is_free: s.is_free ? 1 : 0, requires_approval: s.requires_approval ? 1 : 0,
            membership_validity_days: s.membership_validity_days || '', membership_sessions: s.membership_sessions || '',
            discount_type: s.discount_type || '', discount_value: s.discount_value != null ? s.discount_value : '',
            discount_starts_at: s.discount_starts_at || '', discount_ends_at: s.discount_ends_at || ''
        };
        post(B.urls.updateBase + '/' + s.id, payload)
            .then(function (res) { if (res.ok && res.data.service) { upsert(res.data.service); } })
            .catch(function () { toast(TX.genericError, 'danger'); });
    }

    // ── selection + bulk bar ──────────────────────────────────────────
    function toggleSelect(id, on) { if (on) selection[id] = true; else delete selection[id]; }
    function selectedIds() { return Object.keys(selection); }
    function syncBulkBar() {
        var ids = selectedIds(), bar = $('#wb-bulkbar');
        $('#wb-bulk-count').textContent = ids.length;
        if (ids.length) bar.classList.add('show'); else bar.classList.remove('show');
    }
    function clearSelection() { selection = {}; renderList(); syncBulkBar(); }

    function runBulk(action, extra) {
        var ids = selectedIds(); if (!ids.length) return;
        var body = Object.assign({ action: action, ids: ids }, extra || {});
        post(B.urls.bulk, body).then(function (res) {
            if (!res.ok) { toast(TX.genericError, 'danger'); return; }
            // reflect changes locally without a reload
            if (action === 'delete') { ids.forEach(removeLocal); }
            else if (action === 'duplicate') { (res.data.created || []).forEach(upsert); }
            else {
                ids.forEach(function (id) {
                    var s = svcById(id); if (!s) return;
                    if (action === 'activate') s.is_active = true;
                    if (action === 'deactivate') s.is_active = false;
                    if (action === 'show_online') s.is_bookable_online = true;
                    if (action === 'hide_online') s.is_bookable_online = false;
                    if (action === 'move_category') s.category_id = extra.category_id ? +extra.category_id : null;
                    if (action === 'price') s.price = applyPriceLocal(s, extra);
                    if (action === 'duration') s.duration_minutes = applyDurationLocal(s, extra);
                });
            }
            clearSelection(); rerender();
            toast(T('changesApplied'), 'success');
        });
    }
    function applyPriceLocal(s, e) {
        var v = +e.price_value || 0, b = +s.price;
        var out = e.price_mode === 'increase_percent' ? b * (1 + v / 100) : e.price_mode === 'decrease_percent' ? b * (1 - v / 100) : e.price_mode === 'increase_amount' ? b + v : e.price_mode === 'decrease_amount' ? b - v : v;
        return Math.max(0, Math.round(out * 100) / 100);
    }
    function applyDurationLocal(s, e) {
        var v = +e.duration_value || 0;
        var out = e.duration_mode === 'increase' ? s.duration_minutes + v : e.duration_mode === 'decrease' ? s.duration_minutes - v : v;
        return Math.max(1, Math.min(1440, out));
    }

    // ══════ DRAWER ════════════════════════════════════════════════════
    // Custom drawer controller — the app's Bootstrap build omits the offcanvas
    // component, so we drive open/close ourselves with a .wb-open class + backdrop.
    function ensureBackdrop() {
        var bd = $('#wb-drawer-backdrop');
        if (!bd) { bd = document.createElement('div'); bd.id = 'wb-drawer-backdrop'; document.body.appendChild(bd); bd.addEventListener('click', closeDrawer); }
        return bd;
    }
    function openDrawer() {
        var d = $('#wb-drawer'); if (!d) return;
        ensureBackdrop().classList.add('wb-open');
        d.classList.add('wb-open');
        d.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        var d = $('#wb-drawer'); if (!d) return;
        d.classList.remove('wb-open');
        d.setAttribute('aria-hidden', 'true');
        var bd = $('#wb-drawer-backdrop'); if (bd) bd.classList.remove('wb-open');
        document.body.style.overflow = '';
    }
    function openAdd(type) {
        drawer = { mode: 'add', id: null, type: type || 'standard', emp: {}, res: {}, pkg: [], badges: {}, addonParents: {}, currency: B.defaultCurrency };
        fillDrawer(null);
        $('#wb-drawer-title').textContent = TX.addService;
        $('#wb-save-label').textContent = TX.saveService;
        openDrawer();
    }
    function openEdit(id) {
        var s = svcById(id); if (!s) return;
        drawer = { mode: 'edit', id: id, type: s.service_type, emp: {}, res: {}, pkg: [], badges: {}, addonParents: {}, currency: s.currency };
        (s.employee_ids || []).forEach(function (i) { drawer.emp[i] = true; });
        (s.resource_ids || []).forEach(function (i) { drawer.res[i] = true; });
        (s.badges || []).forEach(function (b) { drawer.badges[b] = true; });
        (s.addon_parent_ids || []).forEach(function (i) { var p = svcById(i); drawer.addonParents[i] = p ? (p.name_en || p.name_ar) : ('#' + i); });
        drawer.pkg = (s.package_items || []).map(function (p) {
            var full = svcById(p.id) || {};
            return { id: p.id, name: p.name, quantity: p.quantity || 1, is_optional: !!p.is_optional, price: +full.price || 0, duration: +full.duration_minutes || 0 };
        });
        fillDrawer(s);
        $('#wb-drawer-title').textContent = TX.editService;
        $('#wb-save-label').textContent = TX.saveChanges;
        openDrawer();
    }
    function setType(t) {
        drawer.type = t; $('#wb-f-type').value = t;
        $$('#wb-typechooser .wb-typeopt').forEach(function (o) { o.classList.toggle('sel', o.getAttribute('data-type') === t); });
        // Packages and memberships both bundle "included services".
        $('#wb-pkg-sec').classList.toggle('wb-hide', !(t === 'package' || t === 'membership'));
        $('#wb-mem-sec').classList.toggle('wb-hide', t !== 'membership');
        $('#wb-consult-sec').classList.toggle('wb-hide', t !== 'consultation');
        $('#wb-addon-sec').classList.toggle('wb-hide', t !== 'addon');
    }
    function setPriceType(pt) {
        $('#wb-f-pricetype').value = pt;
        $$('#wb-pricetype button').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-pt') === pt); });
        $('#wb-priceto-col').classList.toggle('wb-hide', pt !== 'range');
        $('#wb-price-label').firstChild.textContent = (pt === 'fixed' ? (TX.price || 'Price') : pt === 'from' ? 'Starting price' : 'From') + ' ';
    }
    function setCurrency(code, sym) {
        drawer.currency = code; $('#wb-f-currency').value = code;
        $('#wb-cur-sym').textContent = sym || (B.currencies[code] ? B.currencies[code].symbol : code);
        $('#wb-cur-code').textContent = code;
        $$('.wb-cur-opt').forEach(function (o) { o.classList.toggle('active', o.getAttribute('data-code') === code); });
        discPreview();
    }
    function fillDrawer(s) {
        $('#wb-form-errors').classList.add('d-none');
        setType(drawer.type);
        setPriceType(s ? s.price_type : 'fixed');
        setCurrency(s ? s.currency : B.defaultCurrency);
        val('#wb-f-category', s ? (s.category_id || '') : '');
        val('#wb-f-name-en', s ? (s.name_en || '') : '');
        val('#wb-f-name-ar', s ? (s.name_ar || '') : '');
        val('#wb-f-desc', s ? (s.description || '') : '');
        val('#wb-f-price', s ? s.price : '');
        val('#wb-f-priceto', s && s.price_to != null ? s.price_to : '');
        val('#wb-f-duration', s ? s.duration_minutes : 30);
        val('#wb-f-mem-days', s ? (s.membership_validity_days || '') : '');
        val('#wb-f-mem-sessions', s ? (s.membership_sessions || '') : '');
        chk('#wb-f-active', s ? s.is_active : true);
        chk('#wb-f-online', s ? s.is_bookable_online : true);
        // consultation
        setFree(s ? !!s.is_free : false);
        chk('#wb-f-requires-approval', s ? !!s.requires_approval : false);
        // discount
        var hasD = s && s.discount_type && s.discount_value;
        $('#wb-disc-toggle').checked = !!hasD;
        $('#wb-disc-fields').style.display = hasD ? '' : 'none';
        val('#wb-f-disc-type', hasD ? s.discount_type : 'percent');
        val('#wb-f-disc-value', hasD ? s.discount_value : '');
        val('#wb-f-disc-start', s && s.discount_starts_at ? s.discount_starts_at : '');
        val('#wb-f-disc-end', s && s.discount_ends_at ? s.discount_ends_at : '');
        // chips / badges / add-on / package
        renderChips();
        renderBadges();
        renderAddonParents();
        renderPkg();
        discPreview();
        feather();
    }
    function renderChips() {
        $$('#wb-emp-chips .wb-chip').forEach(function (c) { c.classList.toggle('sel', !!drawer.emp[c.getAttribute('data-emp')]); });
        $$('#wb-res-chips .wb-chip').forEach(function (c) { c.classList.toggle('sel', !!drawer.res[c.getAttribute('data-res')]); });
    }
    function renderBadges() {
        $$('#wb-badge-chips .wb-chip').forEach(function (c) { c.classList.toggle('sel', !!drawer.badges[c.getAttribute('data-badge')]); });
    }
    function renderAddonParents() {
        var host = $('#wb-addon-parents'); if (!host) return;
        host.innerHTML = Object.keys(drawer.addonParents).map(function (id) {
            return '<span class="wb-chip sel" data-parent="' + id + '"><i data-feather="link" style="width:13px;height:13px;"></i>' + esc(drawer.addonParents[id]) +
                '<i data-feather="x" class="wb-addon-rm" data-parent="' + id + '" style="width:13px;height:13px;margin-inline-start:2px;cursor:pointer;"></i></span>';
        }).join('');
        feather();
    }
    // Free/paid consultation: a free consult zeroes and locks the price field.
    function setFree(free) {
        var hidden = $('#wb-f-is-free'); if (hidden) hidden.value = free ? '1' : '0';
        $$('#wb-freetype button').forEach(function (b) { b.classList.toggle('active', (b.getAttribute('data-free') === '1') === free); });
        var p = $('#wb-f-price');
        if (p) { if (free) { p.value = 0; p.setAttribute('disabled', 'disabled'); } else { p.removeAttribute('disabled'); } }
        discPreview();
    }
    function renderPkg() {
        var host = $('#wb-pkg-items'); if (!host) return;
        host.innerHTML = drawer.pkg.map(function (p, i) {
            return '<div class="wb-pkgitem" data-pi="' + i + '">' +
                '<i data-feather="' + (p.is_optional ? 'circle' : 'check-circle') + '" class="wb-pkg-opt" data-pi="' + i + '" style="width:16px;height:16px;cursor:pointer;color:' + (p.is_optional ? 'var(--wb-muted)' : '#2bcf7e') + ';" title="' + esc(T('optional')) + '"></i>' +
                '<span class="nm">' + esc(p.name) + '</span>' +
                '<input type="number" class="form-control form-control-sm wb-pkg-qty" data-pi="' + i + '" value="' + (p.quantity || 1) + '" min="1" max="99" style="width:58px;">' +
                '<i data-feather="x" class="wb-pkg-rm" data-pi="' + i + '" style="width:15px;height:15px;cursor:pointer;opacity:.55;"></i>' +
            '</div>';
        }).join('');
        var price = 0, dur = 0;
        drawer.pkg.forEach(function (p) { if (!p.is_optional) { price += (+p.price || 0) * (p.quantity || 1); dur += (+p.duration || 0) * (p.quantity || 1); } });
        $('#wb-pkg-count').textContent = drawer.pkg.length;
        $('#wb-pkg-price').textContent = nf(price) + ' ' + curSym(drawer.currency);
        $('#wb-pkg-dur').textContent = durLabel(dur);
        $('#wb-pkg-sum').dataset.price = price; $('#wb-pkg-sum').dataset.dur = dur;
        feather();
    }
    function discPreview() {
        var on = $('#wb-disc-toggle').checked, type = val('#wb-f-disc-type'), v = parseFloat(val('#wb-f-disc-value')) || 0, price = parseFloat(val('#wb-f-price')) || 0;
        var el = $('#wb-disc-preview'); if (!el) return;
        if (!on || !v || !price) { el.textContent = ''; return; }
        var fp = Math.max(0, type === 'percent' ? price * (1 - v / 100) : price - v);
        el.innerHTML = '<span style="text-decoration:line-through;opacity:.6">' + nf(price) + '</span> → <b style="color:#e53935">' + nf(fp) + '</b>';
    }

    function collectPayload() {
        var obj = {
            service_type: drawer.type,
            service_category_id: val('#wb-f-category') || '',
            name_en: val('#wb-f-name-en'), name_ar: val('#wb-f-name-ar'), description: val('#wb-f-desc'),
            price: val('#wb-f-price') || 0, price_type: val('#wb-f-pricetype'),
            price_to: val('#wb-f-pricetype') === 'range' ? (val('#wb-f-priceto') || 0) : '',
            currency: drawer.currency, duration_minutes: val('#wb-f-duration') || 1,
            is_active: $('#wb-f-active').checked ? 1 : 0,
            is_bookable_online: $('#wb-f-online').checked ? 1 : 0,
            badges: Object.keys(drawer.badges),
            is_free: (drawer.type === 'consultation' && $('#wb-f-is-free').value === '1') ? 1 : 0,
            requires_approval: (drawer.type === 'consultation' && $('#wb-f-requires-approval').checked) ? 1 : 0,
            membership_validity_days: val('#wb-f-mem-days') || '',
            membership_sessions: val('#wb-f-mem-sessions') || '',
            employee_ids: Object.keys(drawer.emp),
            resource_ids: Object.keys(drawer.res)
        };
        if ($('#wb-disc-toggle').checked && parseFloat(val('#wb-f-disc-value'))) {
            obj.discount_type = val('#wb-f-disc-type');
            obj.discount_value = val('#wb-f-disc-value');
            if (val('#wb-f-disc-start')) obj.discount_starts_at = val('#wb-f-disc-start');
            if (val('#wb-f-disc-end')) obj.discount_ends_at = val('#wb-f-disc-end');
        } else { obj.discount_value = ''; obj.discount_type = ''; }
        if (drawer.type === 'package' || drawer.type === 'membership') {
            obj.package_items = drawer.pkg.map(function (p) { return { service_id: p.id, is_optional: p.is_optional ? 1 : 0, quantity: p.quantity || 1 }; });
        }
        if (drawer.type === 'addon') {
            obj.addon_parent_ids = Object.keys(drawer.addonParents);
        }
        return obj;
    }
    function saveDrawer() {
        var btn = $('#wb-save-btn'); btn.disabled = true;
        var obj = collectPayload();
        var url, fd = toFD(obj);
        if (drawer.mode === 'edit') { fd.append('_method', 'PUT'); url = B.urls.updateBase + '/' + drawer.id; }
        else { url = B.urls.store; }
        post(url, fd).then(function (res) {
            btn.disabled = false;
            if (res.ok && res.data.service) {
                upsert(res.data.service); rerender(); flashRow(res.data.service.id);
                closeDrawer();
                toast(drawer.mode === 'edit' ? TX.updated : TX.created, 'success');
            } else if (res.data && res.data.errors) { showErrors(res.data.errors); }
            else { showErrors({ _: [(res.data && res.data.message) || TX.genericError] }); }
        }).catch(function () { btn.disabled = false; showErrors({ _: [TX.genericError] }); });
    }
    function showErrors(errors) {
        var box = $('#wb-form-errors'); if (!box) return;
        var msgs = [];
        Object.keys(errors).forEach(function (k) { (Array.isArray(errors[k]) ? errors[k] : [errors[k]]).forEach(function (m) { msgs.push(m); }); });
        box.innerHTML = msgs.map(function (m) { return '<div>• ' + esc(m) + '</div>'; }).join('');
        box.classList.remove('d-none');
        $('#wb-drawer .offcanvas-body').scrollTop = 0;
    }

    // Remove any orphaned Bootstrap modal backdrops / body lock that could sit on
    // top of the page and swallow clicks (the "Copy button does nothing" symptom).
    function cleanupOverlays() {
        if (!document.querySelector('.modal.show')) {
            $$('.modal-backdrop').forEach(function (b) { b.parentNode && b.parentNode.removeChild(b); });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
        var bd = $('#wb-drawer-backdrop');
        if (bd && !$('#wb-drawer').classList.contains('wb-open')) bd.classList.remove('wb-open');
    }
    function showModal(sel) { cleanupOverlays(); return bootstrap.Modal.getOrCreateInstance($(sel)).show(); }
    function hideModal(sel) { var m = bootstrap.Modal.getInstance($(sel)); if (m) m.hide(); }

    // ══════ COPY MODAL ════════════════════════════════════════════════
    var copyState = { branches: {}, strategy: 'skip', ids: [] };
    function openCopy(ids) {
        copyState = { branches: {}, strategy: 'skip', ids: ids };
        $('#wb-copy-count').textContent = ids.length;
        $$('#wb-copy-branches .wb-chip').forEach(function (c) { c.classList.remove('sel'); });
        $$('#wb-copy-strategy button').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-strategy') === 'skip'); });
        $('#wb-copy-preview').style.display = 'none';
        showModal('#wb-copy-modal');
    }
    function copyRun(dry) {
        var targets = Object.keys(copyState.branches);
        if (!targets.length) { toast(T('pickBranch'), 'warning'); return; }
        var btn = $(dry ? '#wb-copy-preview-btn' : '#wb-copy-confirm'); if (btn) btn.disabled = true;
        post(B.urls.copy, { service_ids: copyState.ids, target_branch_ids: targets, strategy: copyState.strategy, dry_run: dry ? 1 : 0 })
            .then(function (res) {
                if (btn) btn.disabled = false;
                if (!res.ok || !res.data || !res.data.success) { toast(T('copyFailed'), 'danger'); return; }
                if (dry) {
                    var box = $('#wb-copy-preview'); box.style.display = 'block';
                    box.innerHTML = (res.data.summary || []).map(function (r) {
                        return '<div class="d-flex justify-content-between"><b>' + esc(r.branch_name) + '</b><span>+' + r.created + ' ' + T('added') + ' · ' + r.updated + ' ' + T('refreshed') + ' · ' + r.skipped + ' ' + T('skipped') + '</span></div>';
                    }).join('') || ('<span class="text-muted">' + esc(T('noMatches')) + '</span>');
                } else {
                    var c = res.data.total_created || 0, u = res.data.total_updated || 0, sk = res.data.total_skipped || 0;
                    hideModal('#wb-copy-modal'); clearSelection();
                    if (c + u === 0) toast(T('nothingCopied') + (sk ? ' (' + sk + ' ' + T('skipped') + ')' : ''), 'warning');
                    else toast((c + u) + ' ' + T('services') + ' ' + T('copied') + (sk ? ' · ' + sk + ' ' + T('skipped') : ''), 'success');
                }
            })
            .catch(function () { if (btn) btn.disabled = false; toast(T('copyFailed'), 'danger'); });
    }

    // ══════ IMPORT MODAL ══════════════════════════════════════════════
    var importStrategy = 'skip';
    function importRun() {
        var f = $('#wb-import-file').files[0];
        if (!f) { toast(T('chooseFile'), 'warning'); return; }
        var fd = new FormData(); fd.append('file', f); fd.append('strategy', importStrategy);
        $('#wb-import-confirm').disabled = true;
        post(B.urls.import, fd).then(function (res) {
            $('#wb-import-confirm').disabled = false;
            var box = $('#wb-import-result');
            if (res.ok && res.data.success) {
                box.className = 'alert alert-success rounded-3 mt-3';
                box.textContent = '+' + res.data.created + ' ' + T('added') + ' · ' + res.data.updated + ' ' + T('refreshed') + ' · ' + res.data.skipped + ' ' + T('skipped') + ' · ' + T('refreshing');
                box.classList.remove('d-none');
                setTimeout(function () { window.location.reload(); }, 1200);
            } else {
                box.className = 'alert alert-danger rounded-3 mt-3';
                box.textContent = (res.data && res.data.message) || TX.genericError;
                box.classList.remove('d-none');
            }
        });
    }

    // ══════ CATEGORY CRUD ═════════════════════════════════════════════
    function openCat(id) {
        var c = id ? categories.find(function (x) { return String(x.id) === String(id); }) : null;
        $('#wb-cat-id').value = id || '';
        $('#wb-cat-modal-title').textContent = c ? (TX.editService && 'Edit category' || 'Edit category') : 'New category';
        $('#wb-cat-name-en').value = c ? c.name : '';
        $('#wb-cat-name-ar').value = '';
        $('#wb-cat-error').classList.add('d-none');
        showModal('#wb-cat-modal');
    }
    function saveCat() {
        var id = $('#wb-cat-id').value, en = $('#wb-cat-name-en').value.trim(), ar = $('#wb-cat-name-ar').value.trim();
        if (!en) { var e = $('#wb-cat-error'); e.textContent = T('nameRequired'); e.classList.remove('d-none'); return; }
        var url = id ? (B.urls.catBase + '/' + id) : B.urls.catStore;
        var body = { name_en: en, name_ar: ar }; if (id) body._method = 'PUT';
        post(url, body).then(function (res) {
            if (!res.ok || !res.data.category) { toast(TX.genericError, 'danger'); return; }
            var c = res.data.category;
            var i = categories.findIndex(function (x) { return String(x.id) === String(c.id); });
            if (i < 0) categories.push({ id: c.id, name: c.name, sort: c.sort_order || categories.length });
            else categories[i].name = c.name;
            // reflect on services whose category name shows
            bootstrap.Modal.getInstance($('#wb-cat-modal')).hide();
            addCatOption(c);
            rerender();
            toast(T('categorySaved'), 'success');
        });
    }
    function addCatOption(c) {
        $$('#wb-f-category, #wb-bulkedit-cat').forEach(function (sel) {
            if (sel && !sel.querySelector('option[value="' + c.id + '"]')) {
                var o = document.createElement('option'); o.value = c.id; o.textContent = c.name; sel.appendChild(o);
            }
        });
    }

    // ══════ DRAG & DROP ═══════════════════════════════════════════════
    var listSortables = [];
    function initListSort() {
        if (!window.Sortable) return;
        listSortables.forEach(function (s) { try { s.destroy(); } catch (e) {} });
        listSortables = [];
        $$('.wb-rows').forEach(function (container) {
            listSortables.push(new Sortable(container, {
                group: 'wb-services', handle: '.c-grip', animation: 150, ghostClass: 'wb-sortghost',
                onEnd: persistOrder
            }));
        });
    }
    function persistOrder() {
        var items = [];
        $$('.wb-catblock').forEach(function (block) {
            var key = block.getAttribute('data-catblock');
            var catId = key === 'none' ? null : parseInt(key, 10);
            $$('.wb-row', block).forEach(function (row, idx) {
                var id = row.getAttribute('data-id');
                var s = svcById(id);
                if (s) { s.sort_order = idx; s.category_id = catId; s.category_name = catName(catId); }
                items.push({ id: parseInt(id, 10), sort_order: idx, service_category_id: catId });
            });
        });
        post(B.urls.reorder, { items: items }).catch(function () {});
        renderRail();
    }
    var railSortable = null;
    function initRailSort() {
        if (!window.Sortable) return;
        if (railSortable) { try { railSortable.destroy(); } catch (e) {} }
        var el = $('#wb-rail-list');
        railSortable = new Sortable(el, {
            handle: '.grip', animation: 150, draggable: '.wb-cat[data-catrow]', ghostClass: 'wb-sortghost',
            onEnd: function () {
                var ids = $$('.wb-cat[data-catrow]', el).map(function (r) { return parseInt(r.getAttribute('data-catrow'), 10); });
                ids.forEach(function (id, i) { var c = categories.find(function (x) { return x.id === id; }); if (c) c.sort = i; });
                post(B.urls.catReorder, { ids: ids }).catch(function () {});
                renderList();
            }
        });
    }

    // ── dom helpers ───────────────────────────────────────────────────
    function val(sel, v) { var e = $(sel); if (!e) return ''; if (v !== undefined) { e.value = v; return; } return e.value; }
    function chk(sel, v) { var e = $(sel); if (e) e.checked = !!v; }

    // ══════ EVENT WIRING ══════════════════════════════════════════════
    function wire() {
        // search + filters
        var st;
        $('#wb-search').addEventListener('input', function () { clearTimeout(st); var v = this.value; st = setTimeout(function () { state.q = v; renderList(); }, 160); });
        $('#wb-type-filter').addEventListener('change', function () { state.type = this.value; renderList(); });
        $$('.bk-filter-tab[data-status]').forEach(function (b) {
            b.addEventListener('click', function () {
                $$('.bk-filter-tab[data-status]').forEach(function (x) { x.classList.remove('active'); });
                this.classList.add('active'); state.status = this.getAttribute('data-status'); renderList();
            });
        });
        $('#wb-view-list').addEventListener('click', function () { state.view = 'list'; this.classList.add('active'); $('#wb-view-cards').classList.remove('active'); renderList(); });
        $('#wb-view-cards').addEventListener('click', function () { state.view = 'cards'; this.classList.add('active'); $('#wb-view-list').classList.remove('active'); renderList(); });

        // add menu
        $$('.wb-add-type').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); openAdd(this.getAttribute('data-type')); }); });

        // rail interactions (delegated)
        $('#wb-rail-list').addEventListener('click', function (e) {
            var editIc = e.target.closest('[data-catedit]');
            if (editIc) { e.stopPropagation(); openCat(editIc.getAttribute('data-catedit')); return; }
            var row = e.target.closest('.wb-cat'); if (!row) return;
            state.cat = row.getAttribute('data-cat'); renderRail(); renderList();
        });
        $('#wb-cat-add').addEventListener('click', function () { openCat(null); });
        $('#wb-cat-save').addEventListener('click', saveCat);

        // list interactions (delegated)
        $('#wb-list').addEventListener('click', function (e) {
            var caret = e.target.closest('[data-caret]');
            if (caret) { var k = caret.getAttribute('data-caret'); state.collapsed[k] = !state.collapsed[k]; renderList(); return; }
            var act = e.target.closest('[data-act]');
            if (act) {
                e.preventDefault();
                var id = act.closest('.wb-row').getAttribute('data-id');
                handleAct(act.getAttribute('data-act'), id); return;
            }
            var cell = e.target.closest('.wb-cell-edit');
            if (cell) { startInline(cell); return; }
        });
        $('#wb-list').addEventListener('change', function (e) {
            var chk = e.target.closest('.wb-check');
            if (chk) { toggleSelect(chk.getAttribute('data-id'), chk.checked); var r = chk.closest('.wb-row'); if (r) r.classList.toggle('sel', chk.checked); syncBulkBar(); return; }
            var act = e.target.closest('.wb-active');
            if (act) { toggleField(act.getAttribute('data-id'), 'is_active'); return; }
        });
        // cards interactions
        $('#wb-cards').addEventListener('click', function (e) {
            var act = e.target.closest('[data-act]'); if (act) { handleAct(act.getAttribute('data-act'), act.getAttribute('data-id')); }
        });
        $('#wb-cards').addEventListener('change', function (e) {
            var chk = e.target.closest('.wb-check'); if (chk) { toggleSelect(chk.getAttribute('data-id'), chk.checked); chk.closest('.wb-card').classList.toggle('sel', chk.checked); syncBulkBar(); }
        });

        // drawer controls
        $$('#wb-typechooser .wb-typeopt').forEach(function (o) { o.addEventListener('click', function () { setType(this.getAttribute('data-type')); }); });
        $$('#wb-pricetype button').forEach(function (b) { b.addEventListener('click', function () { setPriceType(this.getAttribute('data-pt')); }); });
        $$('.wb-cur-opt').forEach(function (o) { o.addEventListener('click', function (e) { e.preventDefault(); setCurrency(this.getAttribute('data-code'), this.getAttribute('data-symbol')); }); });
        $('#wb-f-price').addEventListener('input', discPreview);
        $('#wb-f-disc-value').addEventListener('input', discPreview);
        $('#wb-f-disc-type').addEventListener('change', discPreview);
        $('#wb-disc-toggle').addEventListener('change', function () { $('#wb-disc-fields').style.display = this.checked ? '' : 'none'; discPreview(); });
        $('#wb-emp-chips').addEventListener('click', function (e) { var c = e.target.closest('.wb-chip'); if (!c) return; var id = c.getAttribute('data-emp'); if (drawer.emp[id]) delete drawer.emp[id]; else drawer.emp[id] = true; c.classList.toggle('sel'); });
        $('#wb-res-chips').addEventListener('click', function (e) { var c = e.target.closest('.wb-chip'); if (!c) return; var id = c.getAttribute('data-res'); if (drawer.res[id]) delete drawer.res[id]; else drawer.res[id] = true; c.classList.toggle('sel'); });
        // Badges multi-select
        $('#wb-badge-chips').addEventListener('click', function (e) { var c = e.target.closest('.wb-chip'); if (!c) return; var b = c.getAttribute('data-badge'); if (drawer.badges[b]) delete drawer.badges[b]; else drawer.badges[b] = true; c.classList.toggle('sel'); });
        // Consultation free/paid
        $$('#wb-freetype button').forEach(function (b) { b.addEventListener('click', function () { setFree(this.getAttribute('data-free') === '1'); }); });
        // Add-on parents search
        var ast;
        $('#wb-addon-search').addEventListener('input', function () { clearTimeout(ast); var v = this.value; ast = setTimeout(function () { addonSearch(v); }, 120); });
        $('#wb-addon-search').addEventListener('focus', function () { addonSearch(this.value); });
        $('#wb-addon-results').addEventListener('click', function (e) { var it = e.target.closest('[data-addparent]'); if (!it) return; e.preventDefault(); addonAdd(it.getAttribute('data-addparent')); this.classList.remove('show'); $('#wb-addon-search').value = ''; });
        $('#wb-addon-parents').addEventListener('click', function (e) { var rm = e.target.closest('.wb-addon-rm'); if (!rm) return; delete drawer.addonParents[rm.getAttribute('data-parent')]; renderAddonParents(); });
        document.addEventListener('click', function (e) { if (!e.target.closest('#wb-addon-search') && !e.target.closest('#wb-addon-results')) { var r = $('#wb-addon-results'); if (r) r.classList.remove('show'); } });
        $('#wb-form').addEventListener('submit', function (e) { e.preventDefault(); saveDrawer(); });

        // Close the drawer: header ✕, Cancel button, backdrop click, or Escape
        $$('#wb-drawer [data-wb-close]').forEach(function (b) {
            b.addEventListener('click', function (e) { e.preventDefault(); closeDrawer(); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && $('#wb-drawer').classList.contains('wb-open')) closeDrawer();
        });

        // package builder
        var pst;
        $('#wb-pkg-search').addEventListener('input', function () { clearTimeout(pst); var v = this.value; pst = setTimeout(function () { pkgSearch(v); }, 120); });
        $('#wb-pkg-search').addEventListener('focus', function () { pkgSearch(this.value); });
        $('#wb-pkg-results').addEventListener('click', function (e) { var it = e.target.closest('[data-add]'); if (!it) return; e.preventDefault(); pkgAdd(it.getAttribute('data-add')); this.classList.remove('show'); $('#wb-pkg-search').value = ''; });
        $('#wb-pkg-items').addEventListener('click', function (e) {
            var rm = e.target.closest('.wb-pkg-rm'); if (rm) { drawer.pkg.splice(+rm.getAttribute('data-pi'), 1); renderPkg(); return; }
            var op = e.target.closest('.wb-pkg-opt'); if (op) { var i = +op.getAttribute('data-pi'); drawer.pkg[i].is_optional = !drawer.pkg[i].is_optional; renderPkg(); return; }
        });
        $('#wb-pkg-items').addEventListener('change', function (e) { var q = e.target.closest('.wb-pkg-qty'); if (q) { var i = +q.getAttribute('data-pi'); drawer.pkg[i].quantity = Math.max(1, parseInt(q.value, 10) || 1); renderPkg(); } });
        $('#wb-pkg-apply').addEventListener('click', function (e) { e.preventDefault(); var sum = $('#wb-pkg-sum'); val('#wb-f-price', sum.dataset.price || 0); val('#wb-f-duration', sum.dataset.dur || 0); discPreview(); });
        document.addEventListener('click', function (e) { if (!e.target.closest('#wb-pkg-search') && !e.target.closest('#wb-pkg-results')) { var r = $('#wb-pkg-results'); if (r) r.classList.remove('show'); } });

        // bulk bar
        $$('#wb-bulkbar [data-bulk]').forEach(function (b) {
            b.addEventListener('click', function (e) { e.preventDefault(); handleBulk(this.getAttribute('data-bulk')); });
        });
        $('#wb-bulk-clear').addEventListener('click', clearSelection);
        $('#wb-bulkedit-apply').addEventListener('click', applyBulkEdit);

        // copy modal
        $('#wb-copy-branches').addEventListener('click', function (e) { var c = e.target.closest('.wb-chip'); if (!c) return; var id = c.getAttribute('data-branch'); if (copyState.branches[id]) delete copyState.branches[id]; else copyState.branches[id] = true; c.classList.toggle('sel'); });
        $$('#wb-copy-strategy button').forEach(function (b) { b.addEventListener('click', function () { copyState.strategy = this.getAttribute('data-strategy'); $$('#wb-copy-strategy button').forEach(function (x) { x.classList.remove('active'); }); this.classList.add('active'); }); });
        $('#wb-copy-preview-btn').addEventListener('click', function () { copyRun(true); });
        $('#wb-copy-confirm').addEventListener('click', function () { copyRun(false); });

        // import modal
        $('#wb-import-open').addEventListener('click', function (e) { e.preventDefault(); $('#wb-import-result').classList.add('d-none'); showModal('#wb-import-modal'); });
        $$('#wb-import-strategy button').forEach(function (b) { b.addEventListener('click', function () { importStrategy = this.getAttribute('data-strategy'); $$('#wb-import-strategy button').forEach(function (x) { x.classList.remove('active'); }); this.classList.add('active'); }); });
        $('#wb-import-confirm').addEventListener('click', importRun);
    }

    function handleAct(act, id) {
        if (act === 'edit') openEdit(id);
        else if (act === 'duplicate') duplicate(id);
        else if (act === 'copy') openCopy([id]);
        else if (act === 'online') toggleField(id, 'is_bookable_online');
        else if (act === 'delete') askDelete(id);
    }

    function handleBulk(kind) {
        var ids = selectedIds(); if (!ids.length) return;
        if (kind === 'copy') { openCopy(ids); return; }
        if (kind === 'delete') { if (confirm(ids.length + ' ' + (TX.services || 'services') + ' — ' + T('del') + '?')) runBulk('delete'); return; }
        if (kind === 'move' || kind === 'price' || kind === 'duration') { openBulkEdit(kind); return; }
        runBulk(kind);   // activate / deactivate / show_online / hide_online / duplicate
    }
    function openBulkEdit(kind) {
        $('#wb-bulkedit-count').textContent = selectedIds().length;
        $('#wb-bulkedit-move').classList.add('wb-hide'); $('#wb-bulkedit-price').classList.add('wb-hide'); $('#wb-bulkedit-duration').classList.add('wb-hide');
        var title = { move: T('moveToCategory'), price: T('changePrice'), duration: T('changeDuration') }[kind];
        $('#wb-bulkedit-title').textContent = title;
        $('#wb-bulkedit-' + kind).classList.remove('wb-hide');
        $('#wb-bulkedit-apply').setAttribute('data-kind', kind);
        showModal('#wb-bulkedit-modal');
    }
    function applyBulkEdit() {
        var kind = $('#wb-bulkedit-apply').getAttribute('data-kind');
        bootstrap.Modal.getInstance($('#wb-bulkedit-modal')).hide();
        if (kind === 'move') runBulk('move_category', { category_id: $('#wb-bulkedit-cat').value || '' });
        else if (kind === 'price') runBulk('price', { price_mode: $('#wb-bulkedit-price-mode').value, price_value: $('#wb-bulkedit-price-value').value || 0 });
        else if (kind === 'duration') runBulk('duration', { duration_mode: $('#wb-bulkedit-duration-mode').value, duration_value: $('#wb-bulkedit-duration-value').value || 0 });
    }

    // package / membership "included services" search (bundles can't nest bundles)
    function pkgSearch(q) {
        q = (q || '').trim().toLowerCase();
        var res = $('#wb-pkg-results');
        var pool = services.filter(function (s) { return s.id != drawer.id && s.service_type !== 'package' && s.service_type !== 'membership'; });
        if (q) pool = pool.filter(function (s) { return (s.name_en || '').toLowerCase().indexOf(q) >= 0 || (s.name_ar || '').toLowerCase().indexOf(q) >= 0; });
        pool = pool.filter(function (s) { return !drawer.pkg.some(function (p) { return String(p.id) === String(s.id); }); }).slice(0, 12);
        if (!pool.length) { res.innerHTML = '<span class="dropdown-item-text text-muted small">' + esc(T('noMatches')) + '</span>'; }
        else res.innerHTML = pool.map(function (s) { return '<a class="dropdown-item d-flex justify-content-between" href="#" data-add="' + s.id + '"><span>' + esc(s.name_en || s.name_ar) + '</span><span class="text-muted small">' + nf(Math.round(s.price)) + ' ' + esc(curSym(s.currency)) + '</span></a>'; }).join('');
        res.classList.add('show');
    }
    function pkgAdd(id) {
        var s = svcById(id); if (!s) return;
        drawer.pkg.push({ id: s.id, name: s.name_en || s.name_ar, quantity: 1, is_optional: false, price: +s.price || 0, duration: +s.duration_minutes || 0 });
        renderPkg();
    }

    // add-on → parent services search (a parent is any non-add-on service)
    function addonSearch(q) {
        q = (q || '').trim().toLowerCase();
        var res = $('#wb-addon-results');
        var pool = services.filter(function (s) { return s.id != drawer.id && s.service_type !== 'addon' && !drawer.addonParents[s.id]; });
        if (q) pool = pool.filter(function (s) { return (s.name_en || '').toLowerCase().indexOf(q) >= 0 || (s.name_ar || '').toLowerCase().indexOf(q) >= 0; });
        pool = pool.slice(0, 12);
        if (!pool.length) { res.innerHTML = '<span class="dropdown-item-text text-muted small">' + esc(T('noMatches')) + '</span>'; }
        else res.innerHTML = pool.map(function (s) { return '<a class="dropdown-item" href="#" data-addparent="' + s.id + '">' + esc(s.name_en || s.name_ar) + '</a>'; }).join('');
        res.classList.add('show');
    }
    function addonAdd(id) {
        var s = svcById(id); if (!s) return;
        drawer.addonParents[id] = s.name_en || s.name_ar;
        renderAddonParents();
    }

    // ── boot ──────────────────────────────────────────────────────────
    function init() {
        wire();
        renderRail();
        renderList();
        updateStats();
        feather();
        // Clear any overlay that survived a previous navigation, and after every
        // modal close, so a stray backdrop never blocks the page (e.g. Copy button).
        cleanupOverlays();
        document.addEventListener('hidden.bs.modal', function () { setTimeout(cleanupOverlays, 50); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
