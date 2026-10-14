@php
    $psI18n = [
        'saved'        => __('All changes saved'),
        'dirty'        => __('You have unsaved changes'),
        'saving'       => __('Saving…'),
        'empty'        => '',
        'ok'           => __('Looks valid'),
        'badFormat'    => __('Not a valid link — it must start with https://'),
        'badHttps'     => __('This link must use https://'),
        'badHost'      => __('This link should point to :hosts'),
        'badEmail'     => __('Not a valid email address'),
        'badPhone'     => __('Use the international format, e.g. +963 944 123 456'),
        'shown'        => __('Shown on the website'),
        'hOff'         => __('Hidden — turned off'),
        'hNoSite'      => __('Hidden — not enabled for the website'),
        'hNoUrl'       => __('Hidden — no link yet'),
        'hBad'         => __('Hidden — the link is not valid'),
        'count'        => __(':n of :max links'),
        'needUrl'      => __('Enter the link, or remove this row.'),
        'removeTitle'  => __('Remove this link?'),
        'removeText'   => __('It will disappear from the website once you save.'),
        'removeYes'    => __('Remove'),
        'cancel'       => __('Cancel'),
        'discardTitle' => __('Discard your changes?'),
        'discardText'  => __('Everything you changed in this tab since the last save will be lost.'),
        'discardYes'   => __('Discard'),
        'otherTitle'   => __('Other tabs have unsaved changes'),
        'otherText'    => __('Saving this tab reloads the page, so changes in other tabs will be lost. Save anyway?'),
        'otherYes'     => __('Save anyway'),
        'maintTitle'   => __('Close the platform to visitors?'),
        'maintText'    => __('Visitors will see the maintenance page right after you save. You and other signed-in staff keep full access.'),
        'pausedText'   => __('Visitors will see the paused notice right after you save. You and other signed-in staff keep full access.'),
        'maintYes'     => __('Yes, close it'),
        'logoType'     => __('The logo must be a PNG, JPG or WebP image.'),
        'logoSize'     => __('The logo must be 2 MB or smaller.'),
        'tagFallback'  => __('(the built-in footer wording is used)'),
        'copyFallback' => __('© :year GlowRez. All rights reserved.', ['year' => date('Y')]),
        'privacy'      => __('Privacy'),
        'terms'        => __('Terms'),
    ];
    $psIcons = [];
    foreach (array_merge(array_keys($presets), $customIcons) as $k) {
        $psIcons[$k] = \App\Support\Platform\PlatformIcons::svg($k, 20);
    }
    $psPresets = collect($presets)->map(fn ($m) => ['label' => $m['label'], 'hosts' => implode(',', $m['hosts'])])->all();
@endphp
@push('scripts')
<script>
window.PS = {
    i18n: @json($psI18n),
    icons: @json($psIcons),
    presets: @json($psPresets),
    maxLinks: {{ (int) $maxLinks }},
    socialOn: @json((bool) $v['visibility.show_social']),
    lang: @json(app()->getLocale())
};
</script>
<script>
(function () {
    'use strict';
    var PS = window.PS, T = PS.i18n;
    var root = document.querySelector('[data-ps-root]');
    if (!root) return;
    var $  = function (s, c) { return (c || root).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); };
    var submitting = false;
    function icons() { if (window.feather) window.feather.replace(); }
    function fmt(s, o) { return String(s).replace(/:(\w+)/g, function (_, k) { return o && k in o ? o[k] : ':' + k; }); }

    /* ───────────── Tabs ───────────── */
    var tabs   = $$('[data-ps-tab]');
    var panels = $$('[data-ps-panel]');
    function activate(id, focus) {
        tabs.forEach(function (t) {
            var on = t.getAttribute('data-ps-tab') === id;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            if (on && focus) t.focus();
            // On phones the tabs are a scrolling strip: keep the open one in view.
            if (on && window.matchMedia('(max-width: 991.98px)').matches && t.scrollIntoView) t.scrollIntoView({ block: 'nearest', inline: 'center' });
        });
        panels.forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-ps-panel') === id); });
        try { var u = new URL(location.href); u.searchParams.set('tab', id); history.replaceState(null, '', u); } catch (e) {}
    }
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { activate(t.getAttribute('data-ps-tab')); });
        t.addEventListener('keydown', function (e) {
            var horizontal = window.matchMedia('(max-width: 991.98px)').matches, rtl = document.documentElement.dir === 'rtl', n = null;
            var next = horizontal ? (rtl ? 'ArrowLeft' : 'ArrowRight') : 'ArrowDown', prev = horizontal ? (rtl ? 'ArrowRight' : 'ArrowLeft') : 'ArrowUp';
            if (e.key === next) n = (i + 1) % tabs.length; else if (e.key === prev) n = (i - 1 + tabs.length) % tabs.length;
            else if (e.key === 'Home') n = 0; else if (e.key === 'End') n = tabs.length - 1;
            if (n !== null) { e.preventDefault(); activate(tabs[n].getAttribute('data-ps-tab'), true); }
        });
    });
    root.addEventListener('click', function (e) {
        var g = e.target.closest('[data-ps-goto]');
        if (g) { activate(g.getAttribute('data-ps-goto')); root.scrollIntoView({ block: 'start' }); }
    });
    var first = $('[data-ps-tab][aria-selected="true"]');
    if (first) activate(first.getAttribute('data-ps-tab'));

    /* ───────────── Live format checks (url / email / phone / whatsapp) ───────────── */
    var DIG = { '٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9','۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9' };
    function hostIn(h, list) { return list.some(function (x) { return h === x || h.slice(-(x.length + 1)) === '.' + x; }); }
    function evaluate(inp) {
        var kind = inp.getAttribute('data-ps-check'), v = (inp.value || '').trim();
        if (!v) return { s: 'empty' };
        if (kind === 'email') {
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? { s: 'ok', href: 'mailto:' + v } : { s: 'bad', m: T.badEmail };
        }
        if (kind === 'phone' || kind === 'wa') {
            var raw = v.replace(/[٠-٩۰-۹]/g, function (c) { return DIG[c]; });
            if (!/^\+?[0-9\s\-().]+$/.test(raw)) return { s: 'bad', m: T.badPhone };
            var d = raw.replace(/\D/g, '');
            if (raw.charAt(0) !== '+') { if (d.slice(0, 2) !== '00') return { s: 'bad', m: T.badPhone }; d = d.slice(2); }
            if (!/^[1-9][0-9]{6,14}$/.test(d)) return { s: 'bad', m: T.badPhone };
            return { s: 'ok', href: kind === 'wa' ? 'https://wa.me/' + d : 'tel:+' + d };
        }
        /* url */
        if (/[\s\\]/.test(v)) return { s: 'bad', m: T.badFormat };
        if (v.charAt(0) === '/') {
            return (inp.hasAttribute('data-relative') && v.charAt(1) !== '/') ? { s: 'ok', href: location.origin + v } : { s: 'bad', m: T.badFormat };
        }
        var u; try { u = new URL(v); } catch (e) { return { s: 'bad', m: T.badFormat }; }
        if (u.protocol !== 'https:' && u.protocol !== 'http:') return { s: 'bad', m: T.badFormat };
        if (inp.hasAttribute('data-https') && u.protocol !== 'https:') return { s: 'bad', m: T.badHttps };
        if (u.username || u.password) return { s: 'bad', m: T.badFormat };
        if (!/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i.test(u.hostname)) return { s: 'bad', m: T.badFormat };
        var hosts = (inp.getAttribute('data-hosts') || '').split(',').filter(Boolean);
        if (hosts.length && !hostIn(u.hostname.toLowerCase(), hosts)) return { s: 'bad', m: fmt(T.badHost, { hosts: hosts.join(' / ') }) };
        return { s: 'ok', href: u.href };
    }
    function checkScope(inp) {
        var id = inp.id;
        return id ? $('[data-ps-checkwrap="' + id + '"]') : inp.closest('.ps-field').querySelector('.ps-check');
    }
    function showCheck(inp, announce) {
        var wrap = checkScope(inp); if (!wrap) return null;
        var r = evaluate(inp), st = wrap.querySelector('[data-ps-state]'), open = wrap.querySelector('[data-ps-open]');
        st.className = 'ps-check-state' + (r.s === 'ok' ? ' is-ok' : r.s === 'bad' ? ' is-bad' : '');
        st.innerHTML = '';
        if (r.s !== 'empty') {
            var i = document.createElement('i'); i.setAttribute('data-feather', r.s === 'ok' ? 'check' : 'alert-circle'); st.appendChild(i);
            st.appendChild(document.createTextNode(' ' + (r.s === 'ok' ? T.ok : r.m)));
        }
        if (r.s === 'ok') { open.href = r.href; open.removeAttribute('aria-disabled'); }
        else { open.href = '#'; open.setAttribute('aria-disabled', 'true'); }
        if (announce) icons();
        return r;
    }
    var timers = new WeakMap();
    $$('[data-ps-check]').forEach(function (inp) {
        showCheck(inp);
        inp.addEventListener('input', function () {
            clearTimeout(timers.get(inp));
            timers.set(inp, setTimeout(function () { showCheck(inp, true); }, 160));
        });
    });
    root.addEventListener('click', function (e) {
        var vb = e.target.closest('[data-ps-verify]');
        if (vb) { var wrap = vb.closest('.ps-field'); var inp = wrap.querySelector('[data-ps-check]'); showCheck(inp, true); inp.focus(); }
        var op = e.target.closest('[data-ps-open]');
        if (op && op.getAttribute('aria-disabled') === 'true') e.preventDefault();
    });

    /* ───────────── Character counters ───────────── */
    $$('[data-ps-count-for]').forEach(function (c) {
        var ta = document.getElementById(c.getAttribute('data-ps-count-for')), max = +c.getAttribute('data-max');
        function upd() { var n = ta.value.length; c.textContent = n + '/' + max; c.classList.toggle('is-near', n > max * 0.9); }
        ta.addEventListener('input', upd); ta.addEventListener('ps:refresh', upd); upd();
    });

    /* ───────────── Logo preview ───────────── */
    var logoIn = $('[data-ps-logo-input]');
    if (logoIn) {
        var box = $('[data-ps-logo-box]'), err = $('[data-ps-logo-err]'), rm = $('[data-ps-logo-remove]');
        var orig = box.innerHTML;
        function logoErr(m) { err.classList.toggle('d-none', !m); err.querySelector('span').textContent = m || ''; }
        logoIn.addEventListener('change', function () {
            logoErr('');
            var f = logoIn.files[0];
            if (!f) { box.innerHTML = orig; icons(); return; }
            if (['image/png', 'image/jpeg', 'image/webp'].indexOf(f.type) < 0) { logoErr(T.logoType); logoIn.value = ''; return; }
            if (f.size > 2 * 1024 * 1024) { logoErr(T.logoSize); logoIn.value = ''; return; }
            var img = new Image(); img.alt = ''; img.src = URL.createObjectURL(f);
            box.innerHTML = ''; box.appendChild(img);
            if (rm) rm.checked = false;
        });
        if (rm) rm.addEventListener('change', function () { box.style.opacity = rm.checked ? '.4' : ''; if (rm.checked) { logoIn.value = ''; logoIn.dispatchEvent(new Event('change')); } });
        logoIn.form.addEventListener('reset', function () { setTimeout(function () { logoErr(''); box.innerHTML = orig; box.style.opacity = ''; icons(); }, 0); });
    }

    /* ───────────── Footer preview ───────────── */
    var prevTag = $('[data-ps-prev-tag]');
    if (prevTag) {
        var L = PS.lang === 'ar' ? 'ar' : 'en';
        var tagIn = document.getElementById('f-visibility-footer-tagline-' + L), copyIn = document.getElementById('f-visibility-copyright-' + L);
        var pr = document.getElementById('f-visibility-privacy-url'), te = document.getElementById('f-visibility-terms-url');
        var upd = function () {
            var tag = (tagIn && tagIn.value.trim()) || '';
            prevTag.textContent = tag || T.tagFallback; prevTag.style.opacity = tag ? '' : '.6';
            var c = (copyIn && copyIn.value.trim()) || '';
            $('[data-ps-prev-copy]').textContent = c ? c.replace(/\{year\}/ig, new Date().getFullYear()) : T.copyFallback;
        };
        [tagIn, copyIn].forEach(function (i) { if (i) { i.addEventListener('input', upd); i.addEventListener('ps:refresh', upd); } });
        upd();
    }

    /* ───────────── System tab: mode detail + releases helpers ───────────── */
    var sys = $('[data-ps-panel="system"]');
    if (sys) {
        var detail = $('[data-ps-maint-detail]', sys);
        $$('input[name="system[mode]"]', sys).forEach(function (r) {
            r.addEventListener('change', function () { detail.hidden = r.value === 'normal' && r.checked; });
        });
    }
    $$('[data-ps-status-group]').forEach(function (g) {
        var p = g.getAttribute('data-ps-status-group'), note = $('[data-ps-store-note="' + p + '"]');
        $$('input[type="radio"]', g).forEach(function (r) { r.addEventListener('change', function () { note.hidden = r.value === 'available' && r.checked; }); });
    });
    var seg = $('[data-ps-seg]');
    if (seg) {
        var segTabs = $$('[role="tab"]', seg);
        var segGo = function (btn, focus) {
            segTabs.forEach(function (b) {
                var on = b === btn; b.setAttribute('aria-selected', on ? 'true' : 'false'); b.tabIndex = on ? 0 : -1;
                document.getElementById(b.getAttribute('aria-controls')).hidden = !on;
            });
            if (focus) btn.focus();
        };
        segTabs.forEach(function (b, i) {
            b.addEventListener('click', function () { segGo(b); });
            b.addEventListener('keydown', function (e) {
                var rtl = document.documentElement.dir === 'rtl', n = null;
                if (e.key === (rtl ? 'ArrowLeft' : 'ArrowRight')) n = (i + 1) % segTabs.length;
                else if (e.key === (rtl ? 'ArrowRight' : 'ArrowLeft')) n = (i - 1 + segTabs.length) % segTabs.length;
                if (n !== null) { e.preventDefault(); segGo(segTabs[n], true); }
            });
        });
        // Open on the product that has a validation error, if any.
        var bad = $$('.ps-sub-panel').find(function (p) { return p.querySelector('.has-error'); });
        if (bad) segGo(document.getElementById(bad.getAttribute('aria-labelledby')));
    }

    /* ───────────── Links manager ───────────── */
    var lp = $('[data-ps-panel="links"]');
    if (lp) {
        var list = $('[data-ps-linklist]', lp), empty = $('[data-ps-empty]', lp), tpl = $('[data-ps-template]', lp);
        var menu = $('[data-ps-add-menu]', lp), toggle = $('[data-ps-add-toggle]', lp), counter = $('[data-ps-link-count]', lp);
        var rows = function () { return $$('[data-ps-link]', list); };

        function rowState(row) {
            var url = $('[data-f="url"]', row), r = evaluate(url), active = $('[data-f="is_active"]', row).checked, site = $('[data-f="show_public"]', row).checked;
            var vis = $('[data-ps-vis]', row), msg, on = false;
            if (!url.value.trim()) msg = T.hNoUrl; else if (r.s === 'bad') msg = T.hBad; else if (!active) msg = T.hOff; else if (!site) msg = T.hNoSite; else { msg = T.shown; on = true; }
            vis.className = 'ps-link-vis ' + (on ? 'is-on' : 'is-off');
            vis.innerHTML = '<i data-feather="' + (on ? 'eye' : 'eye-off') + '"></i>' + msg;
        }
        function refresh() {
            var rs = rows();
            rs.forEach(function (r, i) {
                $$('[name^="links["]', r).forEach(function (el) { el.name = el.name.replace(/^links\[[^\]]*\]/, 'links[' + i + ']'); });
                $('[data-ps-move="-1"]', r).disabled = i === 0;
                $('[data-ps-move="1"]', r).disabled = i === rs.length - 1;
                rowState(r);
            });
            list.hidden = !rs.length; empty.hidden = !!rs.length;
            counter.textContent = fmt(T.count, { n: rs.length, max: PS.maxLinks });
            var present = rs.map(function (r) { return $('[data-f="kind"]', r).value; });
            $$('[data-ps-add]', menu).forEach(function (b) {
                var k = b.getAttribute('data-ps-add'), added = k !== 'custom' && present.indexOf(k) >= 0;
                b.disabled = added || rs.length >= PS.maxLinks;
                var tag = $('[data-ps-added]', b); if (tag) tag.hidden = !added;
            });
            icons();
        }
        function changed() { refresh(); lp.querySelector('form').dispatchEvent(new Event('input', { bubbles: true })); }

        function addRow(kind) {
            var html = tpl.innerHTML.replace(/__i__/g, rows().length);
            var wrap = document.createElement('div'); wrap.innerHTML = html.trim();
            var row = wrap.firstElementChild, preset = kind !== 'custom';
            $('[data-f="kind"]', row).value = kind; row.setAttribute('data-kind', kind);
            $('[data-ps-only="preset"]', row).hidden = !preset; $('[data-ps-only="custom"]', row).hidden = preset;
            $$('[data-ps-only="custom"] [data-f]', row).forEach(function (el) { el.disabled = preset; });
            if (preset) {
                $('[data-ps-label-text]', row).textContent = PS.presets[kind].label;
                $('[data-f="url"]', row).setAttribute('data-hosts', PS.presets[kind].hosts);
                $('[data-ps-icon]', row).innerHTML = PS.icons[kind];
            }
            list.appendChild(row); bindRow(row); changed();
            var url = $('[data-f="url"]', row); url.focus(); row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
        function bindRow(row) {
            var url = $('[data-f="url"]', row);
            showCheck(url);
            url.addEventListener('input', function () { clearTimeout(timers.get(url)); timers.set(url, setTimeout(function () { showCheck(url, true); rowState(row); icons(); }, 160)); });
            ['is_active', 'show_public'].forEach(function (f) { $('[data-f="' + f + '"]', row).addEventListener('change', function () { rowState(row); icons(); }); });
            var sel = $('[data-ps-icon-select]', row);
            if (sel) sel.addEventListener('change', function () { $('[data-ps-icon]', row).innerHTML = PS.icons[sel.value] || PS.icons.link; });
        }
        rows().forEach(bindRow);

        toggle.addEventListener('click', function (e) { e.stopPropagation(); var open = menu.hidden; menu.hidden = !open; toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); if (open) { var f = $('[data-ps-add]:not(:disabled)', menu); if (f) f.focus(); } });
        document.addEventListener('click', function (e) { if (!menu.hidden && !e.target.closest('.ps-add')) { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); } });
        menu.addEventListener('click', function (e) { var b = e.target.closest('[data-ps-add]'); if (!b || b.disabled) return; menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); addRow(b.getAttribute('data-ps-add')); });

        list.addEventListener('click', function (e) {
            var row = e.target.closest('[data-ps-link]'); if (!row) return;
            var mv = e.target.closest('[data-ps-move]');
            if (mv) {
                var d = +mv.getAttribute('data-ps-move');
                if (d < 0 && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
                else if (d > 0 && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
                changed(); var again = $('[data-ps-move="' + d + '"]', row); if (again && !again.disabled) again.focus(); else $('[data-ps-move="' + (-d) + '"]', row).focus();
                return;
            }
            if (e.target.closest('[data-ps-remove]')) {
                var go = function () { row.remove(); changed(); };
                (window.bkConfirm ? window.bkConfirm({ title: T.removeTitle, text: T.removeText, icon: 'warning', confirmText: T.removeYes, confirmColor: '#B23A48' })
                                  : Promise.resolve({ isConfirmed: window.confirm(T.removeText) }))
                    .then(function (r) { if (r && r.isConfirmed) go(); });
            }
        });

        /* drag to reorder (mouse); touch + keyboard use the arrow buttons */
        var dragging = null;
        list.addEventListener('pointerdown', function (e) { var g = e.target.closest('[data-ps-grip]'); if (g && e.pointerType === 'mouse') g.closest('[data-ps-link]').draggable = true; });
        list.addEventListener('dragstart', function (e) { dragging = e.target.closest ? e.target.closest('[data-ps-link]') : null; if (!dragging) return; dragging.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'x'); } catch (x) {} });
        list.addEventListener('dragover', function (e) {
            if (!dragging) return; e.preventDefault();
            var over = e.target.closest('[data-ps-link]'); if (!over || over === dragging) return;
            var rect = over.getBoundingClientRect(), after = e.clientY > rect.top + rect.height / 2;
            list.insertBefore(dragging, after ? over.nextElementSibling : over);
        });
        list.addEventListener('dragend', function () { if (dragging) { dragging.classList.remove('is-dragging'); dragging.draggable = false; dragging = null; changed(); } });

        refresh();
    }

    /* ───────────── Dirty tracking, discard, submit ───────────── */
    var forms = $$('[data-ps-form]');
    function snap(f) {
        var parts = [];
        new FormData(f).forEach(function (v, k) { if (k === '_token' || k === '_method') return; parts.push(k + '=' + (v instanceof File ? v.name + ':' + v.size : v)); });
        return parts.join('&');
    }
    var state = forms.map(function (f) {
        var s = { form: f, id: f.getAttribute('data-ps-form'), base: snap(f), force: !!f.querySelector('.has-error, [aria-invalid="true"]'), dirty: false };
        s.tab = $('[data-ps-tab="' + s.id + '"]'); s.submit = $('[data-ps-submit]', f); s.discard = $('[data-ps-discard]', f); s.line = $('[data-ps-state-line]', f);
        return s;
    });
    function render(s) {
        var d = s.force || snap(s.form) !== s.base;
        s.dirty = d;
        s.submit.disabled = !d || submitting; s.discard.disabled = !d || submitting;
        s.tab.classList.toggle('is-dirty', d);
        s.line.className = 'ps-save-state ' + (d ? 'is-dirty' : 'is-saved');
        s.line.innerHTML = '<i data-feather="' + (d ? 'edit-3' : 'check') + '"></i><span>' + (d ? T.dirty : T.saved) + '</span>';
    }
    state.forEach(function (s) {
        var tick = function () { render(s); icons(); };
        s.form.addEventListener('input', tick); s.form.addEventListener('change', tick);
        render(s);
        s.discard.addEventListener('click', function () {
            var ask = window.bkConfirm ? window.bkConfirm({ title: T.discardTitle, text: T.discardText, icon: 'warning', confirmText: T.discardYes })
                                       : Promise.resolve({ isConfirmed: window.confirm(T.discardText) });
            ask.then(function (r) {
                if (!r || !r.isConfirmed) return;
                if (s.id === 'links') { submitting = true; location.href = location.pathname + '?tab=links'; return; }
                s.form.reset(); s.force = false;
                $$('[data-ps-check]', s.form).forEach(function (i) { showCheck(i); });
                $$('textarea,input', s.form).forEach(function (i) { i.dispatchEvent(new Event('ps:refresh')); });
                var checked = $('input[name="system[mode]"]:checked', s.form); if (checked) checked.dispatchEvent(new Event('change'));
                $$('[data-ps-status-group] input:checked', s.form).forEach(function (i) { i.dispatchEvent(new Event('change')); });
                render(s); icons();
            });
        });
        s.form.addEventListener('submit', function (e) {
            if (s.form._ok) return;
            e.preventDefault();
            if (!s.dirty) return;

            if (s.id === 'links') {                      // every row needs a link; the server re-checks too
                var missing = $$('[data-ps-link] [data-f="url"]', s.form).filter(function (i) { return !i.value.trim(); })[0];
                if (missing) {
                    var fld = missing.closest('.ps-field'); fld.classList.add('has-error');
                    if (!fld.querySelector('.ps-err.js')) { var d = document.createElement('div'); d.className = 'ps-err js'; d.setAttribute('role', 'alert'); d.textContent = T.needUrl; fld.appendChild(d); }
                    missing.focus(); return;
                }
            }

            var asks = [];
            var modeNow = $('input[name="system[mode]"]:checked', s.form), modeWas = s.form.getAttribute('data-ps-confirm');
            if (modeNow && !modeNow.disabled && modeNow.value !== 'normal' && modeNow.value !== modeWas) {
                asks.push({ title: T.maintTitle, text: modeNow.value === 'paused' ? T.pausedText : T.maintText, icon: 'warning', confirmText: T.maintYes, confirmColor: '#B45309' });
            }
            if (state.some(function (o) { return o !== s && o.dirty; })) {
                asks.push({ title: T.otherTitle, text: T.otherText, icon: 'warning', confirmText: T.otherYes });
            }
            var go = function () {
                submitting = true; s.form._ok = true; s.submit.classList.add('is-loading');
                state.forEach(render); s.form.submit();
            };
            (function next(i) {
                if (i >= asks.length) return go();
                (window.bkConfirm ? window.bkConfirm(asks[i]) : Promise.resolve({ isConfirmed: window.confirm(asks[i].text) }))
                    .then(function (r) { if (r && r.isConfirmed) next(i + 1); });
            })(0);
        });
    });
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && state.some(function (s) { return s.dirty; })) { e.preventDefault(); e.returnValue = ''; }
    });
    window.addEventListener('pageshow', function (e) { if (e.persisted) { submitting = false; state.forEach(function (s) { s.form._ok = false; s.submit.classList.remove('is-loading'); render(s); }); } });
    icons();
})();
</script>
@endpush
