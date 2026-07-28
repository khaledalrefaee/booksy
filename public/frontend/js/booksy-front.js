/* ============================================================================
   BOOKSY — Public Front runtime (vanilla, dependency-free, ~3kb)
   Theme toggle · scroll reveal · sticky nav · mobile menu · count-up
   Performance-first: passive listeners, IntersectionObserver, rAF batching,
   full prefers-reduced-motion support.
   ========================================================================== */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
  var root = document.documentElement;

  /* ── Theme (persisted, shared vocabulary with dashboard) ── */
  var THEME_KEY = 'bk_front_theme';
  function applyTheme(t) {
    root.setAttribute('data-bk-theme', t);
    try { localStorage.setItem(THEME_KEY, t); } catch (e) {}
    document.cookie = THEME_KEY + '=' + t + ';path=/;max-age=31536000;samesite=lax';
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
      b.setAttribute('aria-pressed', t === 'dark');
    });
  }
  window.bkToggleTheme = function () {
    var cur = root.getAttribute('data-bk-theme') === 'dark' ? 'dark' : 'light';
    applyTheme(cur === 'dark' ? 'light' : 'dark');
  };
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-theme-toggle]');
    if (t) { e.preventDefault(); window.bkToggleTheme(); }
  });

  /* ── Scroll reveal ── */
  function initReveal() {
    var els = document.querySelectorAll('.bkf-reveal');
    if (!els.length) return;
    if (reduce || !('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    els.forEach(function (el) { io.observe(el); });
  }

  /* ── Sticky nav state ── */
  function initNav() {
    var nav = document.querySelector('[data-bkf-nav]');
    if (!nav) return;
    var ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        nav.classList.toggle('is-scrolled', window.scrollY > 12);
        ticking = false;
      });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* Mobile menu */
    var toggle = nav.querySelector('[data-menu-toggle]');
    var menu = nav.querySelector('[data-menu]');
    if (toggle && menu) {
      toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open);
        document.body.style.overflow = open ? 'hidden' : '';
      });
      menu.addEventListener('click', function (e) {
        if (e.target.closest('a')) {
          nav.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        }
      });
    }
  }

  /* ── Count-up (stats) ── */
  function initCounters() {
    var nums = document.querySelectorAll('[data-count]');
    if (!nums.length) return;
    if (reduce || !('IntersectionObserver' in window)) {
      nums.forEach(function (n) { n.textContent = n.getAttribute('data-count'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        var el = en.target;
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var suffix = el.getAttribute('data-suffix') || '';
        var dur = 1400, start = performance.now();
        function tick(now) {
          var p = Math.min((now - start) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);
          var val = target * eased;
          el.textContent = (target % 1 ? val.toFixed(1) : Math.round(val).toLocaleString()) + suffix;
          if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
      });
    }, { threshold: 0.5 });
    nums.forEach(function (n) { io.observe(n); });
  }

  /* ── Parallax (subtle, transform-only) ── */
  function initParallax() {
    var els = document.querySelectorAll('[data-parallax]');
    if (!els.length || reduce) return;
    var items = [].map.call(els, function (el) {
      return { el: el, speed: parseFloat(el.getAttribute('data-parallax')) || 0.15 };
    });
    var ticking = false;
    function update() {
      var vh = window.innerHeight;
      items.forEach(function (it) {
        var r = it.el.getBoundingClientRect();
        var mid = r.top + r.height / 2;
        var off = (mid - vh / 2) * -it.speed;
        it.el.style.transform = 'translate3d(0,' + off.toFixed(1) + 'px,0)';
      });
      ticking = false;
    }
    function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();
  }

  /* ── Pointer tilt / float (hero visuals) ── */
  function initTilt() {
    var scenes = document.querySelectorAll('[data-tilt]');
    if (!scenes.length || reduce || window.matchMedia('(hover:none)').matches) return;
    scenes.forEach(function (scene) {
      var max = parseFloat(scene.getAttribute('data-tilt')) || 8;
      var raf;
      scene.addEventListener('pointermove', function (e) {
        var r = scene.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width - 0.5;
        var py = (e.clientY - r.top) / r.height - 0.5;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(function () {
          [].forEach.call(scene.querySelectorAll('[data-tilt-layer]'), function (l) {
            var d = parseFloat(l.getAttribute('data-tilt-layer')) || 1;
            l.style.transform = 'translate3d(' + (px * max * d) + 'px,' + (py * max * d) + 'px,0)';
          });
        });
      });
      scene.addEventListener('pointerleave', function () {
        [].forEach.call(scene.querySelectorAll('[data-tilt-layer]'), function (l) {
          l.style.transform = '';
        });
      });
    });
  }

  function boot() { initReveal(); initNav(); initCounters(); initParallax(); initTilt(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
