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

  /* ── Drag-to-scroll for horizontal rails ──
     Touch & trackpad scroll natively; this adds click-drag for desktop mice,
     with light release inertia and snap that re-engages after the drag.
     bindRail() attaches once; refreshDrag() (re-runnable + on resize) toggles the
     grab affordance so rails revealed later (e.g. geo "Near you") pick it up. */
  function overflows(rail) { return rail.scrollWidth > rail.clientWidth + 4; }

  function bindRail(rail) {
    if (rail.__dragBound) return;
    rail.__dragBound = true;
    var down = false, moved = false, startX = 0, startLeft = 0;
    var lastX = 0, lastT = 0, vel = 0, raf = 0;

    rail.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return; // touch/pen scroll natively
      if (!overflows(rail)) return;
      down = true; moved = false;
      startX = e.clientX; startLeft = rail.scrollLeft;
      lastX = e.clientX; lastT = e.timeStamp; vel = 0;
      cancelAnimationFrame(raf);
    });

    rail.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      if (!moved && Math.abs(dx) < 5) return;       // drag threshold
      if (!moved) { moved = true; rail.classList.add('is-dragging'); try { rail.setPointerCapture(e.pointerId); } catch (_) {} }
      e.preventDefault();
      rail.scrollLeft = startLeft - dx;
      var dt = e.timeStamp - lastT;
      if (dt > 0) vel = (e.clientX - lastX) / dt;    // px per ms
      lastX = e.clientX; lastT = e.timeStamp;
    });

    function end(e) {
      if (!down) return;
      down = false;
      if (!moved) return;
      try { rail.releasePointerCapture(e.pointerId); } catch (_) {}
      // Inertia: keep gliding briefly, then let scroll-snap settle.
      if (!reduce && Math.abs(vel) > 0.05) {
        var v = vel * 16; // per-frame
        (function glide() {
          v *= 0.92;
          rail.scrollLeft -= v;
          if (Math.abs(v) > 0.4) raf = requestAnimationFrame(glide);
          else rail.classList.remove('is-dragging');
        })();
      } else {
        rail.classList.remove('is-dragging');
      }
    }
    rail.addEventListener('pointerup', end);
    rail.addEventListener('pointercancel', end);
    // Swallow the click that follows a real drag so cards don't navigate.
    rail.addEventListener('click', function (e) {
      if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
    }, true);
  }

  function refreshDrag() {
    document.querySelectorAll('.bkf-rail').forEach(function (rail) {
      bindRail(rail);
      rail.classList.toggle('is-draggable', overflows(rail));
    });
  }
  window.bkRefreshRails = refreshDrag; // let pages re-scan after revealing a rail
  var rzT;
  window.addEventListener('resize', function () { clearTimeout(rzT); rzT = setTimeout(refreshDrag, 200); }, { passive: true });

  /* ── Favourites (account-bound) — shared by home + venues + favourites page ──
     Source of truth is the server (window.BK_FAV.ids). Guests are prompted to
     log in, then the tapped venue is saved. Toggles are optimistic with revert,
     and emit `bk:fav` so the favourites page can drop an un-saved card. */
  function initFavorites() {
    var F = window.BK_FAV || { auth: false, ids: [], toggle: '' };
    var ids = (F.ids || []).map(Number);
    var TOKEN = (document.querySelector('meta[name=csrf-token]') || {}).content || '';

    function paint() {
      document.querySelectorAll('[data-fav]').forEach(function (b) {
        b.classList.toggle('is-on', ids.indexOf(+b.dataset.fav) > -1);
      });
    }
    window.bkPaintFavs = paint;

    function persist(id, cb) {
      if (!F.toggle) { cb && cb(false); return; }
      fetch(F.toggle, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' },
        body: JSON.stringify({ branch_id: id })
      }).then(function (r) { return r.ok ? r.json() : Promise.reject(); })
        .then(function (j) { cb && cb(true, !!j.favorited); })
        .catch(function () { cb && cb(false); });
    }

    document.addEventListener('click', function (e) {
      var f = e.target.closest('[data-fav]'); if (!f) return; e.preventDefault();
      var id = +f.dataset.fav;

      // Guest → open login, then save the venue they wanted and reflect it.
      if (!F.auth) {
        if (window.CustomerAuthModal) {
          CustomerAuthModal.open(function () { persist(id, function () { window.location.reload(); }); });
        }
        return;
      }

      var willOn = ids.indexOf(id) === -1;
      f.classList.toggle('is-on', willOn);                 // optimistic
      if (willOn) ids.push(id); else ids.splice(ids.indexOf(id), 1);

      persist(id, function (ok, serverOn) {
        if (!ok) {                                         // revert on failure
          f.classList.toggle('is-on', !willOn);
          if (willOn) ids.splice(ids.indexOf(id), 1); else ids.push(id);
          return;
        }
        document.dispatchEvent(new CustomEvent('bk:fav', { detail: { id: id, on: serverOn } }));
      });
    });

    paint();
  }

  /* ── PWA: register service worker + custom install prompt ── */
  function initPWA() {
    if ('serviceWorker' in navigator && window.BK_SW) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register(window.BK_SW).catch(function () {});
      });
    }

    var ar = document.documentElement.lang === 'ar';
    var DKEY = 'bk_pwa_dismissed';
    var deferred = null;

    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferred = e;
      try { if (localStorage.getItem(DKEY) === '1') return; } catch (x) {}
      showInstall();
    });
    window.addEventListener('appinstalled', function () { removeInstall(); try { localStorage.setItem(DKEY, '1'); } catch (x) {} });

    function showInstall() {
      if (document.querySelector('.bkf-install')) return;
      var bar = document.createElement('div');
      bar.className = 'bkf-install';
      bar.innerHTML =
        '<div class="bkf-install-ic"><img src="' + (window.BK_ICON || '') + '" alt=""></div>' +
        '<div class="bkf-install-tx"><b>' + (ar ? 'ثبّت بوكسي' : 'Install Booksy') + '</b>' +
        '<span>' + (ar ? 'وصول أسرع من شاشتك الرئيسية' : 'Faster access from your home screen') + '</span></div>' +
        '<button class="bkf-install-go" type="button">' + (ar ? 'تثبيت' : 'Install') + '</button>' +
        '<button class="bkf-install-x" type="button" aria-label="' + (ar ? 'إغلاق' : 'Dismiss') + '">&times;</button>';
      document.body.appendChild(bar);
      requestAnimationFrame(function () { bar.classList.add('is-in'); });
      bar.querySelector('.bkf-install-go').addEventListener('click', function () {
        if (!deferred) return;
        deferred.prompt();
        deferred.userChoice.finally(function () { deferred = null; removeInstall(); });
      });
      bar.querySelector('.bkf-install-x').addEventListener('click', function () {
        removeInstall(); try { localStorage.setItem(DKEY, '1'); } catch (x) {}
      });
    }
    function removeInstall() {
      var b = document.querySelector('.bkf-install');
      if (b) { b.classList.remove('is-in'); setTimeout(function () { b.remove(); }, 300); }
    }
  }

  function boot() { initReveal(); initNav(); initCounters(); initParallax(); initTilt(); refreshDrag(); initFavorites(); initPWA(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
