{{--
    Mobile filter bottom-sheet — progressive enhancement.

    Any filter <form data-filter-sheet="Label"> becomes, on phones only, a single
    "Filters" button that opens the form as a bottom sheet with Apply / Reset.
    On desktop the form is untouched. If JS never runs, the form stays visible
    (the .fs-ready gate is added by JS), so nothing is ever unreachable.
--}}
<style>
.fs-trigger { display:none; }
.fs-backdrop { display:none; }

@media (max-width: 767.98px) {
    [data-filter-sheet].fs-ready { display:none !important; }

    [data-filter-sheet].fs-open {
        display:flex !important; flex-direction:column; gap:12px;
        position:fixed; inset-inline:0; bottom:0; z-index:1200;
        background:var(--bk-surface); color:var(--bk-text);
        border-radius:18px 18px 0 0;
        padding:14px 16px calc(16px + env(safe-area-inset-bottom, 0px));
        max-height:86vh; overflow:auto;
        box-shadow:0 -10px 40px rgba(0,0,0,.3);
        animation:fsUp .22s cubic-bezier(.22,1,.36,1);
    }
    /* Stack every field full-width inside the sheet */
    [data-filter-sheet].fs-open > *,
    [data-filter-sheet].fs-open .card-body > .row > [class*="col"] {
        width:100% !important; max-width:none !important; flex:0 0 100% !important;
    }
    [data-filter-sheet].fs-open .form-select,
    [data-filter-sheet].fs-open .form-control,
    [data-filter-sheet].fs-open .input-group { width:100% !important; max-width:none !important; }
    @keyframes fsUp { from { transform:translateY(100%); } to { transform:none; } }

    .fs-trigger {
        display:inline-flex; align-items:center; gap:7px; margin-bottom:14px;
        background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-text);
        border-radius:999px; padding:8px 15px; font-size:13px; font-weight:600; cursor:pointer;
    }
    .fs-trigger.has-active { border-color:var(--bk-accent); color:var(--bk-accent); }
    .fs-count {
        background:var(--bk-accent-fill); color:#fff; border-radius:999px;
        font-size:11px; font-weight:800; min-width:18px; height:18px;
        display:inline-flex; align-items:center; justify-content:center; padding:0 5px;
    }
    .fs-backdrop { display:block; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1199; }
    .fs-head { display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--bk-border); padding-bottom:10px; }
    .fs-title { font-size:15px; font-weight:800; }
    .fs-close { background:transparent; border:0; color:var(--bk-text-muted); cursor:pointer; padding:4px; }
    .fs-actions { display:flex; gap:10px; border-top:1px solid var(--bk-border); padding-top:12px; position:sticky; bottom:0; background:var(--bk-surface); }
    .fs-actions .btn { flex:1; }
    body.fs-lock { overflow:hidden; }

    @media (prefers-reduced-motion: reduce) { [data-filter-sheet].fs-open { animation:none; } }
}

@media (min-width: 768px) { .fs-trigger, .fs-backdrop { display:none !important; } }
</style>

<script>
(function () {
    var isAr = document.documentElement.lang === 'ar';
    var T = { filters: isAr ? 'الفلاتر' : 'Filters', apply: isAr ? 'تطبيق' : 'Apply', reset: isAr ? 'إعادة تعيين' : 'Reset', close: isAr ? 'إغلاق' : 'Close' };

    function isFilterField(el) {
        return !(el.type === 'hidden' || el.type === 'submit' || el.type === 'button' || el.name === 'tab');
    }

    function activeCount(form) {
        var n = 0;
        form.querySelectorAll('select, input').forEach(function (el) {
            if (isFilterField(el) && (el.value || '').trim() !== '') n++;
        });
        return n;
    }

    function setup(form) {
        if (form.__fsInit) return;

        // Skip enhancement when the form has no actual filter fields (e.g. its
        // only filter — the branch — is hidden because a branch context is active).
        var hasFilterField = false;
        form.querySelectorAll('select, input').forEach(function (el) { if (isFilterField(el)) hasFilterField = true; });
        if (! hasFilterField) return;

        form.__fsInit = true;
        var title = form.getAttribute('data-filter-sheet') || T.filters;

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'fs-trigger';
        trigger.innerHTML =
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>' +
            '<span>' + title + '</span><span class="fs-count" hidden></span>';
        form.parentNode.insertBefore(trigger, form);

        var backdrop = document.createElement('div');
        backdrop.className = 'fs-backdrop';
        backdrop.hidden = true;
        document.body.appendChild(backdrop);

        var head = document.createElement('div');
        head.className = 'fs-head';
        var titleEl = document.createElement('span');
        titleEl.className = 'fs-title';
        titleEl.textContent = title;
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'fs-close';
        closeBtn.setAttribute('aria-label', T.close);
        closeBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
        head.appendChild(titleEl);
        head.appendChild(closeBtn);

        var actions = document.createElement('div');
        actions.className = 'fs-actions';
        var resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'btn btn-outline-secondary rounded-pill fs-reset';
        resetBtn.textContent = T.reset;
        var applyBtn = document.createElement('button');
        applyBtn.type = 'submit';
        applyBtn.className = 'btn btn-primary rounded-pill fs-apply';
        applyBtn.textContent = T.apply;
        actions.appendChild(resetBtn);
        actions.appendChild(applyBtn);

        function open() {
            // Defer auto-submit so several filters can be set before Apply.
            form.querySelectorAll('[onchange]').forEach(function (el) {
                el.setAttribute('data-fs-onchange', el.getAttribute('onchange'));
                el.removeAttribute('onchange');
            });
            if (head.parentNode !== form) form.insertBefore(head, form.firstChild);
            if (actions.parentNode !== form) form.appendChild(actions);
            form.classList.add('fs-open');
            backdrop.hidden = false;
            document.body.classList.add('fs-lock');
        }
        function close() {
            form.classList.remove('fs-open');
            backdrop.hidden = true;
            document.body.classList.remove('fs-lock');
            form.querySelectorAll('[data-fs-onchange]').forEach(function (el) {
                el.setAttribute('onchange', el.getAttribute('data-fs-onchange'));
                el.removeAttribute('data-fs-onchange');
            });
        }

        trigger.addEventListener('click', open);
        backdrop.addEventListener('click', close);
        closeBtn.addEventListener('click', close);
        resetBtn.addEventListener('click', function () {
            form.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
            form.querySelectorAll('input').forEach(function (i) { if (isFilterField(i)) i.value = ''; });
            form.submit();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && form.classList.contains('fs-open')) close();
        });

        var count = activeCount(form);
        if (count > 0) {
            var badge = trigger.querySelector('.fs-count');
            badge.textContent = count;
            badge.hidden = false;
            trigger.classList.add('has-active');
        }

        form.classList.add('fs-ready');
    }

    function initAll() { document.querySelectorAll('[data-filter-sheet]').forEach(setup); }
    if (document.readyState !== 'loading') initAll();
    else document.addEventListener('DOMContentLoaded', initAll);
})();
</script>
