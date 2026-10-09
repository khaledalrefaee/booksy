/* GlowRez · /join — four-step interest form (progressive enhancement over a normal POST) */
(function () {
  'use strict';

  var form = document.getElementById('gl-form');
  if (!form) return;

  var card = document.getElementById('gl-card');
  var T = {};
  try { T = JSON.parse(document.getElementById('gl-i18n').textContent); } catch (e) { /* defaults below */ }
  var L = T.labels || {};
  var G = T.groups || {};

  var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
  var progress = Array.prototype.slice.call(form.querySelectorAll('[data-prog]'));
  var nameEl = document.getElementById('gl-step-name');
  var numEl = document.getElementById('gl-step-n');
  var btnBack = document.getElementById('gl-back');
  var btnNext = document.getElementById('gl-next');
  var btnSubmit = document.getElementById('gl-submit');
  var formError = document.getElementById('gl-form-error');
  var summary = document.getElementById('gl-summary');
  var current = 1;

  /* which step owns which field (for jumping back to a server-side error) */
  var FIELD_STEP = {
    full_name: 1, phone: 1, whatsapp: 1, email: 1,
    business_name: 2, business_type: 2, city: 2, city_other: 2, area: 2, number_of_branches: 2,
    instagram: 2, facebook: 2, website: 2,
    interests: 3
  };

  /* ── helpers ─────────────────────────────────────────────── */
  var AR_DIGITS = { '٠': 0, '١': 1, '٢': 2, '٣': 3, '٤': 4, '٥': 5, '٦': 6, '٧': 7, '٨': 8, '٩': 9, '۰': 0, '۱': 1, '۲': 2, '۳': 3, '۴': 4, '۵': 5, '۶': 6, '۷': 7, '۸': 8, '۹': 9 };
  function latinDigits(s) { return String(s).replace(/[٠-٩۰-۹]/g, function (d) { return AR_DIGITS[d]; }); }
  function val(name) { var el = form.elements[name]; return el && el.value != null ? String(el.value).trim() : ''; }
  function wrap(name) { return form.querySelector('[data-field="' + name + '"]'); }
  function input(name) { var w = wrap(name); return w ? w.querySelector('input,select') : null; }

  function setError(name, msg) {
    var w = wrap(name); if (!w) return;
    w.classList.add('has-error');
    var span = w.querySelector('.gl-err span'); if (span) span.textContent = msg;
    var i = w.querySelector('input,select'); if (i) i.setAttribute('aria-invalid', 'true');
  }
  function clearError(name) {
    var w = wrap(name); if (!w) return;
    w.classList.remove('has-error');
    var i = w.querySelector('input,select'); if (i) i.removeAttribute('aria-invalid');
  }
  function clearAllErrors() {
    Array.prototype.forEach.call(form.querySelectorAll('.has-error'), function (w) { w.classList.remove('has-error'); });
    formError.classList.remove('is-on');
  }
  function validPhone(v) {
    var d = latinDigits(v).replace(/\D/g, '');
    if (d.indexOf('00') === 0) d = d.slice(2);
    return d.length >= 9 && d.length <= 15;
  }

  /* ── per-step validation (mirrors the server; the server stays the authority) ── */
  function validate(step) {
    var bad = [];
    function fail(name, msg) { setError(name, msg); bad.push(name); }

    if (step === 1) {
      if (val('full_name').length < 2) fail('full_name', T.required);
      var p = val('phone');
      if (!p) fail('phone', T.required); else if (!validPhone(p)) fail('phone', T.phone);
      var same = form.elements.whatsapp_same.checked, wa = val('whatsapp');
      if (!same && wa && !validPhone(wa)) fail('whatsapp', T.phone);
      var em = val('email');
      if (em && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(em)) fail('email', T.email);
    }
    if (step === 2) {
      if (val('business_name').length < 2) fail('business_name', T.required);
      if (!form.querySelector('input[name="business_type"]:checked')) fail('business_type', T.choose);
      if (!val('city')) fail('city', T.required);
      if (val('city') === 'other' && !val('city_other')) fail('city_other', T.required);
      var n = parseInt(latinDigits(val('number_of_branches')), 10);
      if (!(n >= 1 && n <= 99)) fail('number_of_branches', T.branches);
      var ig = val('instagram');
      if (ig && !/^(@?[A-Za-z0-9._]{1,60}|https?:\/\/\S+)$/.test(ig)) fail('instagram', T.instagram);
    }
    return bad;
  }

  /* ── navigation ──────────────────────────────────────────── */
  function goTo(n, opts) {
    opts = opts || {};
    current = n;
    steps.forEach(function (s) {
      var on = Number(s.getAttribute('data-step')) === n;
      s.hidden = !on;
      s.classList.remove('is-in');
      if (on) { void s.offsetWidth; s.classList.add('is-in'); }
    });
    progress.forEach(function (li) {
      var k = Number(li.getAttribute('data-prog'));
      li.className = k < n ? 'is-done' : (k === n ? 'is-current' : '');
    });
    if (nameEl && T.stepNames) nameEl.textContent = T.stepNames[n - 1];
    if (numEl) numEl.textContent = n;
    btnBack.hidden = n === 1;
    btnNext.hidden = n === 4;
    btnSubmit.hidden = n !== 4;
    if (n === 4) buildSummary();

    if (opts.push) history.pushState({ glStep: n }, '', '#step-' + n);

    if (opts.focus !== false) {
      var s = steps[n - 1], target;
      if (n === 4) { summary.setAttribute('tabindex', '-1'); target = summary; }
      else target = s.querySelector('input:not([type=hidden]):not([type=checkbox][name=whatsapp_same]), select');
      if (target && target.focus) target.focus({ preventScroll: true });
      var top = card.getBoundingClientRect().top;
      if (top < 0 || top > window.innerHeight * 0.4) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function next() {
    clearAllErrors();
    var bad = validate(current);
    if (bad.length) { var i = input(bad[0]); if (i) i.focus(); return; }
    goTo(current + 1, { push: true });
  }
  btnNext.addEventListener('click', next);
  btnBack.addEventListener('click', function () { goTo(Math.max(1, current - 1), { push: true }); });
  window.addEventListener('popstate', function (e) { goTo((e.state && e.state.glStep) || 1, { push: false, focus: false }); });
  history.replaceState({ glStep: 1 }, '', location.pathname + location.search);

  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
      e.preventDefault();
      if (current < 4) next();
    }
  });

  /* ── small behaviours ────────────────────────────────────── */
  form.elements.whatsapp_same.addEventListener('change', function () {
    var w = document.getElementById('wrap-whatsapp');
    w.hidden = this.checked;
    if (this.checked) clearError('whatsapp');
  });
  form.elements.city.addEventListener('change', function () {
    document.getElementById('wrap-city_other').hidden = this.value !== 'other';
  });
  form.addEventListener('click', function (e) {
    var b = e.target.closest('[data-step-by]'); if (!b) return;
    var inp = form.elements.number_of_branches;
    var n = parseInt(latinDigits(inp.value), 10); if (isNaN(n)) n = 1;
    inp.value = Math.min(99, Math.max(1, n + Number(b.getAttribute('data-step-by'))));
    clearError('number_of_branches'); inp.dispatchEvent(new Event('input', { bubbles: true }));
  });
  form.addEventListener('input', function (e) {
    var t = e.target;
    if (t.name === 'phone' || t.name === 'whatsapp' || t.name === 'number_of_branches') {
      var f = latinDigits(t.value); if (f !== t.value) t.value = f;   // Arabic-Indic digits → 0-9
    }
    var w = t.closest('[data-field]'); if (w) clearError(w.getAttribute('data-field'));
    saveDraft();
  });
  form.addEventListener('change', function (e) {
    var w = e.target.closest('[data-field]'); if (w) clearError(w.getAttribute('data-field'));
    saveDraft();
  });

  /* ── summary (step 4) ────────────────────────────────────── */
  function row(dl, k, v, ltr) {
    if (!v) return;
    var d = document.createElement('div'), dt = document.createElement('dt'), dd = document.createElement('dd');
    dt.textContent = k; dd.textContent = v; if (ltr) dd.className = 'is-ltr';
    d.appendChild(dt); d.appendChild(dd); dl.appendChild(d);
  }
  function group(title, step, build) {
    var g = document.createElement('div'); g.className = 'gl-sum-group';
    var head = document.createElement('div'); head.className = 'gl-sum-head';
    var h = document.createElement('h3'); h.textContent = title;
    var b = document.createElement('button'); b.type = 'button'; b.className = 'gl-edit'; b.textContent = T.edit || 'Edit';
    b.addEventListener('click', function () { goTo(step, { push: true }); });
    head.appendChild(h); head.appendChild(b);
    var dl = document.createElement('dl'); dl.className = 'gl-sum-list';
    build(dl);
    g.appendChild(head); g.appendChild(dl); summary.appendChild(g);
  }
  function buildSummary() {
    summary.textContent = '';
    group(G.contact, 1, function (dl) {
      row(dl, L.full_name, val('full_name'));
      row(dl, L.phone, val('phone'), true);
      row(dl, L.whatsapp, form.elements.whatsapp_same.checked ? L.sameWa : val('whatsapp'), !form.elements.whatsapp_same.checked);
      row(dl, L.email, val('email'), true);
    });
    group(G.business, 2, function (dl) {
      var type = form.querySelector('input[name="business_type"]:checked');
      var citySel = form.elements.city;
      var city = citySel.value === 'other' ? val('city_other') : (citySel.options[citySel.selectedIndex] || {}).text;
      row(dl, L.business_name, val('business_name'));
      row(dl, L.business_type, type ? type.parentNode.textContent.trim() : '');
      row(dl, L.city, city);
      row(dl, L.area, val('area'));
      row(dl, L.branches, val('number_of_branches'));
      row(dl, L.instagram, val('instagram'), true);
      row(dl, L.facebook, val('facebook'), true);
      row(dl, L.website, val('website'), true);
    });
    group(G.interests, 3, function (dl) {
      var picked = Array.prototype.map.call(form.querySelectorAll('input[name="interests[]"]:checked'), function (c) { return c.parentNode.textContent.trim(); });
      row(dl, L.interests, picked.length ? picked.join(' · ') : L.none);
    });
  }

  /* ── draft: survive an accidental refresh ────────────────── */
  var DRAFT = 'glJoinDraft', saveTimer;
  function saveDraft() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      try {
        var d = {};
        Array.prototype.forEach.call(form.elements, function (el) {
          if (!el.name || el.name === '_token' || el.name === '_t' || el.name === 'hp_extra') return;
          if (el.type === 'radio' || el.type === 'checkbox') { if (el.checked) (d[el.name] = d[el.name] || []).push(el.value); else if (el.type === 'checkbox' && el.name === 'whatsapp_same') d[el.name] = []; }
          else d[el.name] = el.value;
        });
        sessionStorage.setItem(DRAFT, JSON.stringify(d));
      } catch (e) { /* private mode: fine */ }
    }, 250);
  }
  function restoreDraft() {
    try {
      var d = JSON.parse(sessionStorage.getItem(DRAFT) || 'null'); if (!d) return;
      Array.prototype.forEach.call(form.elements, function (el) {
        if (!el.name || !(el.name in d)) return;
        if (el.type === 'radio') el.checked = d[el.name].indexOf(el.value) > -1;
        else if (el.type === 'checkbox') el.checked = d[el.name].indexOf(el.value) > -1;
        else if (typeof d[el.name] === 'string') el.value = d[el.name];
      });
      document.getElementById('wrap-whatsapp').hidden = form.elements.whatsapp_same.checked;
      document.getElementById('wrap-city_other').hidden = form.elements.city.value !== 'other';
    } catch (e) { /* ignore */ }
  }
  restoreDraft();

  /* ── submit ──────────────────────────────────────────────── */
  function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }
  function setBusy(on) { btnSubmit.setAttribute('aria-busy', on ? 'true' : 'false'); btnSubmit.disabled = on; }

  function send(retried) {
    if (form.elements.hp_extra) form.elements.hp_extra.value = '';   // a browser may autofill the hidden trap; a person never types in it
    var fd = new FormData(form);
    return fetch(form.action, {
      method: 'POST', credentials: 'same-origin', body: fd,
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() }
    }).then(function (res) {
      if (res.status === 419 && !retried) {          // session went stale while the form sat open: refresh the token once
        return fetch('/csrf-token', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            document.querySelector('meta[name="csrf-token"]').content = j.token;
            form.elements._token.value = j.token;
            return send(true);
          });
      }
      return res.json().catch(function () { return {}; }).then(function (body) { return { status: res.status, body: body }; });
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (current < 4) { next(); return; }
    clearAllErrors();
    // re-check steps 1-2 so a restored/edited draft can't slip through
    for (var s = 1; s <= 2; s++) { var bad = validate(s); if (bad.length) { goTo(s, { push: true }); var i = input(bad[0]); if (i) i.focus(); return; } }

    setBusy(true);
    send(false).then(function (r) {
      setBusy(false);
      if (r.status >= 200 && r.status < 300 && r.body && r.body.status) return showSuccess(r.body.data || {});
      if (r.status === 422 && r.body && r.body.errors) return showErrors(r.body.errors);
      var msg = r.status === 429 ? T.throttle : ((r.body && r.body.message) || T.generic);
      showFormError(msg);
    }).catch(function () { setBusy(false); showFormError(T.network); });
  });

  function showFormError(msg) {
    formError.querySelector('span').textContent = msg;
    formError.classList.add('is-on');
    formError.scrollIntoView({ block: 'center', behavior: 'smooth' });
  }

  function showErrors(errors) {
    var firstStep = 5, firstName = null;
    Object.keys(errors).forEach(function (key) {
      var name = key.split('.')[0];
      setError(name, errors[key][0]);
      var st = FIELD_STEP[name] || 4;
      if (st < firstStep) { firstStep = st; firstName = name; }
    });
    if (firstStep <= 3) { goTo(firstStep, { push: true }); var i = firstName && input(firstName); if (i) i.focus(); }
    else showFormError(T.generic);
  }

  function showSuccess(data) {
    try { sessionStorage.removeItem(DRAFT); } catch (e) { /* ignore */ }
    var ok = document.getElementById('gl-success');
    ok.setAttribute('data-state', data.duplicate ? 'dup' : 'new');
    var wa = document.getElementById('gl-success-wa');
    if (wa && data.whatsapp_url) wa.href = data.whatsapp_url;
    form.hidden = true;
    ok.hidden = false;
    ok.focus({ preventScroll: true });
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  if (location.hash && /^#step-[1-4]$/.test(location.hash)) goTo(1, { push: false, focus: false });   // a reload always restarts at step 1 (values are restored)
})();
