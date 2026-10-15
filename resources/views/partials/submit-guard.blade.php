{{--
  Global double-submit guard + button loading state. Include ONCE per layout
  (right after <body>). Fully automatic — no per-button markup needed:

    • Every non-GET <form> submit: the submit button shows a spinner and the form
      is locked until the next page loads, so a double click / double Enter can
      never post twice.
    • Every mutating fetch()/XHR started by a click (POST/PUT/PATCH/DELETE): the
      clicked button shows a spinner and ignores clicks until the request settles.
    • Buttons that open a Bootstrap/Swal dialog, and clicks swallowed by a
      validation handler (preventDefault), are left alone.

  Opt out on a form or a button with  data-no-loading.
  API: window.bkBusy.lock(form, submitter) · window.bkBusy.mark(el) → release fn
--}}
@once
<style>
.bk-busy { position:relative !important; color:transparent !important; text-shadow:none !important; cursor:progress !important; }
.bk-busy > * { visibility:hidden; }
.bk-busy::after {
    content:""; position:absolute; left:50%; top:50%; translate:-50% -50%;
    width:1.05em; height:1.05em; min-width:14px; min-height:14px; border-radius:50%; box-sizing:border-box;
    border:2px solid color-mix(in srgb, var(--bk-busy-color, currentColor) 28%, transparent);
    border-top-color:var(--bk-busy-color, currentColor);
    animation:bk-busy-spin .65s linear infinite;
}
input.bk-busy { color:inherit !important; opacity:.6; }
.bk-busy-dim { opacity:.55; cursor:progress !important; }
@keyframes bk-busy-spin { to { transform:rotate(360deg); } }
@media (prefers-reduced-motion:reduce) { .bk-busy::after { animation-duration:1.6s; } }
</style>
<script>
(function () {
    if (window.bkBusy) return;

    var WINDOW_MS   = 1000;    // a request counts as "caused by" a click made this recently
    var FALLBACK_MS = 30000;   // never stay locked forever (downloads, dropped connections)
    var SKIP_CLICK  = '[data-bs-toggle],[data-bs-dismiss],[data-dismiss],.btn-close,.swal2-container *,[data-no-loading]';
    var BTN_SEL     = 'button, input[type="submit"], input[type="button"], input[type="image"], [role="button"], .btn';
    var last = { el: null, t: 0 };
    var active = [];           // release functions of everything currently busy

    // ── busy marker (ref-counted per element) ───────────────────────────────
    function mark(el, quiet) {
        if (!el || el.nodeType !== 1 || el.hasAttribute('data-no-loading')) return function () {};
        var s = el.__bkBusy;
        if (s) { s.n++; } else {
            s = el.__bkBusy = { n: 1, quiet: !!quiet };
            if (quiet) {
                el.classList.add('bk-busy-dim');
            } else {
                try { el.style.setProperty('--bk-busy-color', getComputedStyle(el).color); } catch (e) {}
                // legacy opt-in spinner already on it? keep that one.
                if (!el.classList.contains('is-loading')) { el.classList.add('bk-busy'); s.own = true; }
            }
            el.setAttribute('aria-busy', 'true');
        }
        var done = false;
        function release() {
            if (done) return; done = true;
            var i = active.indexOf(release); if (i > -1) active.splice(i, 1);
            if (--s.n > 0) return;
            el.classList.remove('bk-busy-dim');
            if (s.own) el.classList.remove('bk-busy');
            el.style.removeProperty('--bk-busy-color');
            el.removeAttribute('aria-busy');
            delete el.__bkBusy;
        }
        active.push(release);
        return release;
    }

    // ── form lock ────────────────────────────────────────────────────────────
    function submitButtons(form) {
        var out = [].slice.call(form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"], input[type="image"]'));
        if (form.id) out = out.concat([].slice.call(document.querySelectorAll('[form="' + form.id + '"]')));
        return out;
    }
    function lock(form, submitter) {
        if (!form || form.__bkLocked) return;
        var btns = submitButtons(form);
        var main = submitter && submitter.nodeType === 1 ? submitter : btns[0];
        form.__bkLocked = true;
        var rels = btns.map(function (b) { return b === main ? mark(b) : mark(b, true); });
        if (main && btns.indexOf(main) === -1) rels.push(mark(main));
        var timer = setTimeout(unlock, FALLBACK_MS);
        function unlock() {
            clearTimeout(timer);
            rels.forEach(function (r) { r(); });
            form.__bkLocked = false;
        }
        form.__bkUnlock = unlock;
    }

    function skipForm(form, sub) {
        if (!form || form.hasAttribute('data-no-loading')) return true;
        if (sub && sub.hasAttribute && sub.hasAttribute('data-no-loading')) return true;
        var method = ((sub && sub.formMethod) || form.getAttribute('method') || 'get').toLowerCase();
        if (method === 'get' || method === 'dialog') return true;                 // searches/filters: harmless to repeat
        var target = ((sub && sub.formTarget) || form.target || '').toLowerCase();
        return target === '_blank';
    }

    // ── submit: capture first (blocks repeats), decide after every handler ran ─
    window.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.nodeName !== 'FORM') return;
        if (form.__bkLocked) { e.preventDefault(); e.stopImmediatePropagation(); return; }
        var sub = e.submitter;
        last = { el: sub || form.querySelector('button[type="submit"],button:not([type])'), t: Date.now() };
        // Deferred: the submit's form-data is already built, so disabling-ish state can't
        // drop the submitter's name/value, and validators that preventDefault() are honoured.
        setTimeout(function () {
            if (e.defaultPrevented || skipForm(form, sub)) return;
            lock(form, sub);
        }, 0);
    }, true);

    // ── clicks: swallow clicks on a busy button, remember the last one ─────────
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest) return;
        var b = t.closest(BTN_SEL);
        if (!b) return;
        if (b.__bkBusy) { e.preventDefault(); e.stopImmediatePropagation(); return; }
        if (b.closest(SKIP_CLICK)) { last = { el: null, t: 0 }; return; }
        last = { el: b, t: Date.now() };
    }, true);

    function forRequest() {
        var el = last.el;
        if (!el || Date.now() - last.t > WINDOW_MS || !el.isConnected) return null;
        return mark(el);
    }

    // ── fetch / XHR (mutating only) ─────────────────────────────────────────
    var MUTATING = /^(POST|PUT|PATCH|DELETE)$/i;
    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            var m = (init && init.method) || (input && input.method) || 'GET';
            var rel = MUTATING.test(m) ? forRequest() : null;
            var p = nativeFetch.apply(this, arguments);
            if (rel) p.then(rel, rel);
            return p;
        };
    }
    if (window.XMLHttpRequest) {
        var proto = XMLHttpRequest.prototype, open = proto.open, send = proto.send;
        proto.open = function (method) { this.__bkM = method; return open.apply(this, arguments); };
        proto.send = function () {
            var rel = MUTATING.test(this.__bkM || '') ? forRequest() : null;
            if (rel) this.addEventListener('loadend', rel);
            return send.apply(this, arguments);
        };
    }

    // Back/forward cache restores the page exactly as it was left — unlock it.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        active.slice().forEach(function (r) { r(); });
        document.querySelectorAll('form').forEach(function (f) { if (f.__bkUnlock) f.__bkUnlock(); });
    });

    window.bkBusy = { lock: lock, mark: mark };
})();
</script>
@endonce
