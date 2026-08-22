@php
    /** Global "Venues map" FAB + modal. Included once from the front layout so it
     *  appears on every customer-facing page. Leaflet is lazy-loaded on first open
     *  (nothing extra downloads until the user actually taps the button). */
    $isAr = $isAr ?? (app()->getLocale() === 'ar');
@endphp

<button type="button" class="bkf-fab" id="bkf-map-fab" aria-label="{{ $isAr ? 'خريطة الأماكن' : 'Venues map' }}">
  <x-icon name="map-pin" :size="20"/><span class="lbl">{{ $isAr ? 'الخريطة' : 'Map' }}</span>
</button>

<div class="bkf-map-modal" id="bkf-map-modal" aria-hidden="true">
  <div class="bkf-map-box" role="dialog" aria-modal="true" aria-label="{{ $isAr ? 'خريطة الأماكن' : 'Venues map' }}">
    <div class="bkf-map-head">
      <h3><x-icon name="map-pin" :size="18"/>{{ $isAr ? 'الأماكن على الخريطة' : 'Venues on the map' }}</h3>
      <button type="button" class="bkf-map-close" data-map-close aria-label="{{ $isAr ? 'إغلاق' : 'Close' }}"><x-icon name="x" :size="18"/></button>
    </div>
    <div id="bkf-leaflet"></div>
  </div>
</div>

<script>
(function () {
  'use strict';
  var AR = @json($isAr);
  var CSS = '{{ asset('vendor/leaflet/leaflet.css') }}';
  var JS  = '{{ asset('vendor/leaflet/leaflet.js') }}';
  var URL = '{{ route('front.map.branches') }}';
  var modal = document.getElementById('bkf-map-modal');
  var fab   = document.getElementById('bkf-map-fab');
  var mapInited = false, map;

  function loadLeaflet(cb) {
    if (window.L) { cb(); return; }
    if (!document.querySelector('link[data-leaflet]')) {
      var l = document.createElement('link');
      l.rel = 'stylesheet'; l.href = CSS; l.setAttribute('data-leaflet', '');
      document.head.appendChild(l);
    }
    var existing = document.querySelector('script[data-leaflet]');
    if (existing) { existing.addEventListener('load', cb); return; }
    var s = document.createElement('script');
    s.src = JS; s.defer = true; s.setAttribute('data-leaflet', '');
    s.addEventListener('load', cb);
    document.body.appendChild(s);
  }

  function initMap() {
    if (mapInited || !window.L) return; mapInited = true;
    map = L.map('bkf-leaflet', { scrollWheelZoom: false }).setView([33.5138, 36.2765], 11);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap, &copy; CARTO' }).addTo(map);
    fetch(URL).then(function (r) { return r.json(); }).then(function (list) {
      var pts = [];
      list.forEach(function (b) {
        if (b.lat == null || b.lng == null) return;
        var mk = L.marker([b.lat, b.lng]).addTo(map);
        mk.bindPopup('<b>' + (b.name || '') + '</b><br>' + (b.address || '') + '<br><a href="' + b.url + '">' + (AR ? 'عرض المكان' : 'View venue') + '</a>');
        pts.push([b.lat, b.lng]);
      });
      if (pts.length) map.fitBounds(pts, { padding: [40, 40], maxZoom: 13 });
      setTimeout(function () { map.invalidateSize(); }, 200);
    }).catch(function () {});
  }

  function openMap() {
    if (!modal) return;
    modal.classList.add('is-open'); document.body.style.overflow = 'hidden';
    loadLeaflet(function () { initMap(); setTimeout(function () { map && map.invalidateSize(); }, 220); });
  }
  function closeMap() { if (!modal) return; modal.classList.remove('is-open'); document.body.style.overflow = ''; }

  if (fab) fab.addEventListener('click', openMap);
  if (modal) modal.addEventListener('click', function (e) { if (e.target === modal || e.target.closest('[data-map-close]')) closeMap(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMap(); });
})();
</script>
