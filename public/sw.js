/* Booksy service worker — path-agnostic (works under /booksy/public/ and at a root domain).
   Strategy: network-first for page navigations (with an offline fallback),
   cache-first for same-origin static assets. Keeps the app fast and usable on
   flaky connections — important for the target market. */
const VERSION = 'booksy-v1';
const BASE = new URL(self.registration.scope).pathname; // e.g. "/booksy/public/" or "/"
const OFFLINE_URL = BASE + 'offline';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(VERSION).then((cache) => cache.addAll([OFFLINE_URL])).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function isStaticAsset(url) {
  return /\.(?:css|js|woff2?|ttf|png|jpe?g|svg|webp|gif|ico)(?:\?|$)/i.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return; // never touch cross-origin

  // Page navigations: network-first, fall back to cache then the offline page.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(VERSION).then((c) => c.put(req, copy)).catch(() => {});
          return res;
        })
        .catch(() => caches.match(req).then((hit) => hit || caches.match(OFFLINE_URL)))
    );
    return;
  }

  // Static assets: cache-first, then network (and cache it).
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.match(req).then((hit) =>
        hit || fetch(req).then((res) => {
          const copy = res.clone();
          caches.open(VERSION).then((c) => c.put(req, copy)).catch(() => {});
          return res;
        }).catch(() => hit)
      )
    );
  }
});
