/* GlowRez auth screens — shared behaviour (theme, password reveal, OTP, strength meter, pointer glow).
   Markup contracts live in public/css/glowrez-auth.css. No dependencies. */
(function () {
    'use strict';
    var root = document.documentElement;

    /* ── Theme toggle ──
       data-theme-url (company): persist through the server cookie route so the dashboard shares it.
       Otherwise (owner): remember per viewer in localStorage. Without JS the anchor still navigates. */
    var themeBtn = document.getElementById('gaTheme');
    if (themeBtn) {
        themeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var next = root.dataset.theme === 'light' ? 'dark' : 'light';
            root.dataset.theme = next;
            var url = themeBtn.getAttribute('data-theme-url');
            if (url) {
                fetch(url.replace('__MODE__', next), { credentials: 'same-origin', redirect: 'manual' }).catch(function () {});
            } else {
                try { localStorage.setItem('ga_theme', next); } catch (err) {}
            }
        });
    }

    /* ── Password reveal ── */
    document.querySelectorAll('.ga-eye').forEach(function (btn) {
        var input = document.getElementById(btn.getAttribute('aria-controls'));
        if (!input) return;
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', btn.getAttribute(show ? 'data-hide' : 'data-show') || btn.getAttribute('aria-label'));
            input.focus({ preventScroll: true });
        });
    });

    /* ── One-time code: boxes -> hidden input (paste, backspace, optional auto-submit) ── */
    document.querySelectorAll('[data-otp]').forEach(function (wrap) {
        var boxes = [].slice.call(wrap.querySelectorAll('input'));
        var hidden = document.querySelector(wrap.getAttribute('data-target'));
        var form = wrap.closest('form');
        var last = boxes.length - 1;
        var auto = wrap.hasAttribute('data-autosubmit');
        var sent = false;
        if (!hidden) return;

        function collect() {
            hidden.value = boxes.map(function (b) { return b.value; }).join('');
            boxes.forEach(function (b) { b.classList.toggle('is-filled', !!b.value); });
            wrap.classList.remove('is-error');
            if (auto && !sent && hidden.value.length === boxes.length && form) {
                sent = true;
                form.requestSubmit();
            }
        }
        boxes.forEach(function (box, i) {
            box.addEventListener('focus', function () { box.select(); });
            box.addEventListener('input', function () {
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                if (box.value && i < last) boxes[i + 1].focus();
                collect();
            });
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !box.value && i > 0) { boxes[i - 1].focus(); boxes[i - 1].value = ''; collect(); }
                if (e.key === 'ArrowLeft' && i > 0) { e.preventDefault(); boxes[i - 1].focus(); }
                if (e.key === 'ArrowRight' && i < last) { e.preventDefault(); boxes[i + 1].focus(); }
                if (e.key === 'Enter' && form) { e.preventDefault(); form.requestSubmit(); }
            });
            box.addEventListener('paste', function (e) {
                var t = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '').slice(0, boxes.length);
                if (!t) return;
                e.preventDefault();
                for (var j = 0; j < t.length; j++) boxes[j].value = t[j];
                collect();
                boxes[Math.min(t.length, last)].focus();
            });
        });
        if (form) form.addEventListener('submit', function () { sent = true; });
        window.addEventListener('pageshow', function (e) { if (e.persisted) sent = false; });
    });

    /* ── Password strength meter ── */
    document.querySelectorAll('[data-meter]').forEach(function (meter) {
        var input = document.querySelector(meter.getAttribute('data-for'));
        var label = meter.querySelector('.ga-meter-label');
        var words = [];
        try { words = JSON.parse(meter.getAttribute('data-labels') || '[]'); } catch (err) {}
        if (!input) return;
        function score(v) {
            var s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            return s;
        }
        input.addEventListener('input', function () {
            var v = input.value;
            if (!v) { meter.hidden = true; meter.removeAttribute('data-score'); return; }
            var s = score(v);
            meter.hidden = false;
            meter.setAttribute('data-score', String(s || 1));
            if (label) label.textContent = words[Math.max(0, (s || 1) - 1)] || '';
        });
    });

    /* ── Confirm-password match ── */
    document.querySelectorAll('[data-match]').forEach(function (confirm) {
        var source = document.querySelector(confirm.getAttribute('data-match'));
        var field = confirm.closest('.ga-field');
        var msg = field && field.querySelector('[data-mismatch]');
        var form = confirm.form;
        var touched = false;
        if (!source) return;
        function mismatch() { return confirm.value !== '' && confirm.value !== source.value; }
        function paint() {
            var bad = touched && mismatch();
            if (field) field.classList.toggle('has-error', bad);
            if (msg) msg.classList.toggle('show', bad);
            confirm.setAttribute('aria-invalid', bad ? 'true' : 'false');
        }
        confirm.addEventListener('blur', function () { touched = confirm.value !== ''; paint(); });
        confirm.addEventListener('input', function () { if (touched || confirm.value.length >= source.value.length) touched = true; paint(); });
        source.addEventListener('input', function () { if (confirm.value !== '') { touched = true; paint(); } });
        if (form) form.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            if (confirm.value === '' || mismatch()) {
                e.preventDefault(); touched = true;
                if (confirm.value !== '') paint();
                else if (msg) { msg.classList.add('show'); field.classList.add('has-error'); }
                confirm.focus();
            }
        });
    });

    /* ── Busy state on submit (only if nothing blocked the submit) ── */
    document.querySelectorAll('form[data-busy]').forEach(function (form) {
        var btn = form.querySelector('.ga-submit');
        if (!btn) return;
        var label = btn.querySelector('.ga-submit-label');
        var idle = label ? label.textContent : '';
        form.addEventListener('submit', function (e) {
            setTimeout(function () {
                if (e.defaultPrevented) return;
                btn.setAttribute('aria-busy', 'true');
                if (label && form.getAttribute('data-busy')) label.textContent = form.getAttribute('data-busy');
            }, 0);
        });
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { btn.removeAttribute('aria-busy'); if (label) label.textContent = idle; }
        });
    });

    /* ── Resend cooldown (UI guard against repeated paid sends) ── */
    document.querySelectorAll('[data-cooldown]').forEach(function (btn) {
        var secs = parseInt(btn.getAttribute('data-cooldown'), 10) || 30;
        var key = 'ga_cd_' + (btn.getAttribute('data-cooldown-key') || 'x');
        var idle = btn.textContent.trim();
        var prefix = btn.getAttribute('data-wait') || '';
        var until = 0;
        try { until = parseInt(sessionStorage.getItem(key) || '0', 10); } catch (err) {}
        if (!until || until < Date.now()) {
            until = Date.now() + secs * 1000;
            try { sessionStorage.setItem(key, String(until)); } catch (err) {}
        }
        function fmt(n) { return '0:' + (n < 10 ? '0' : '') + n; }
        function tick() {
            var left = Math.ceil((until - Date.now()) / 1000);
            if (left <= 0) { btn.disabled = false; btn.textContent = idle; try { sessionStorage.removeItem(key); } catch (err) {} return; }
            btn.disabled = true; btn.textContent = prefix + ' ' + fmt(left);
            setTimeout(tick, 250);
        }
        tick();
        if (btn.form) btn.form.addEventListener('submit', function () { try { sessionStorage.removeItem(key); } catch (err) {} });
    });

    /* ── The glow: a warm light follows the pointer across the brand stage, drifting when idle ── */
    var stage = document.getElementById('gaStage');
    if (stage && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var rect = stage.getBoundingClientRect();
        var tx = rect.width * 0.62, ty = rect.height * 0.4, x = tx, y = ty;
        var pointer = false, lastMove = 0, raf = 0;
        window.addEventListener('resize', function () { rect = stage.getBoundingClientRect(); });
        window.addEventListener('pointermove', function (e) {
            rect = stage.getBoundingClientRect();
            if (e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom) {
                pointer = true; lastMove = performance.now();
                tx = e.clientX - rect.left; ty = e.clientY - rect.top;
            }
        }, { passive: true });
        var frame = function (t) {
            if (pointer && t - lastMove > 2600) pointer = false;
            if (!pointer) {
                tx = rect.width * (0.62 + Math.sin(t / 2600) * 0.12);
                ty = rect.height * (0.42 + Math.cos(t / 3400) * 0.10);
            }
            x += (tx - x) * (pointer ? 0.14 : 0.03);
            y += (ty - y) * (pointer ? 0.14 : 0.03);
            stage.style.setProperty('--mx', x.toFixed(1) + 'px');
            stage.style.setProperty('--my', y.toFixed(1) + 'px');
            raf = requestAnimationFrame(frame);
        };
        document.addEventListener('visibilitychange', function () {
            cancelAnimationFrame(raf);
            if (!document.hidden) raf = requestAnimationFrame(frame);
        });
        raf = requestAnimationFrame(frame);
    }
})();
