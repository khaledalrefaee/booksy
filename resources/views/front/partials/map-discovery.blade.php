{{-- ══════════════ 5 · MAP DISCOVERY ══════════════
     Phones: the map fills the card and a swipeable deck of venue cards docks over its lower edge —
     swipe the deck and the map follows, tap a pin and the deck jumps to it. No popup ever covers the map.
     Desktop: the deck becomes a scrolling side list.
     Every pin is a medallion of the venue-type's own image, ringed in the type's colour, and the
     filter chips above are the legend: same colour, same word.
     Expects: $categories, $currency, $t, $isAr. --}}
@php
  $mdTiles = \App\Support\VenueTypes::tiles($categories, $isAr);
  $mdCfg   = config('venue_types');
  $mdColors = ['other' => $mdCfg['other']['color'] ?? '#4B5D34'];
  $mdGroupOf = [];
  foreach ($mdCfg['groups'] as $k => $g) { $mdColors[$k] = $g['color'] ?? '#4B5D34'; foreach ($g['slugs'] as $sl) { $mdGroupOf[$sl] = $k; } }
@endphp
<section class="bkf-section md" id="explore" style="padding-top:clamp(24px,4vw,56px)" data-md>
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal">
      <div>
        <h2 class="bkf-title fp-title bkf-mt-0">{{ $t('اكتشف الأماكن', 'Discover places') }} <span class="em">{{ $t('من حولك', 'around you') }}</span></h2>
        <p class="fp-lead">{{ $t('كل لون نوعٌ من الأماكن. حرّك البطاقات أو اضغط أي علامة، واحجز مباشرةً.', 'Each colour is a type of place. Swipe the cards or tap a pin, then book.') }}</p>
      </div>
    </div>

    <div class="md-chips" role="group" aria-label="{{ $t('نوع المكان', 'Type of place') }}">
      <button type="button" class="md-chip is-on" data-md-filter="" aria-pressed="true"><span>{{ $t('الكل', 'All') }}</span><span class="n bkf-tnum" data-md-n="">–</span></button>
      @foreach($mdTiles as $tile)
        <button type="button" class="md-chip" data-md-filter="{{ $tile['key'] }}" aria-pressed="false" style="--g:{{ $mdColors[$tile['key']] ?? '#4B5D34' }}">
          <i aria-hidden="true"></i><span>{{ $tile['label'] }}</span><span class="n bkf-tnum" data-md-n="{{ $tile['key'] }}">–</span>
        </button>
      @endforeach
      <button type="button" class="md-chip is-near" data-md-locate><x-icon name="navigation" :size="16"/><span>{{ $t('قربي', 'Near me') }}</span></button>
    </div>

    <div class="md-stage bkf-reveal" data-md-stage>
      <div class="md-canvas">
        <div id="bkf-disco-map" data-disco-map></div>
        <div class="md-loading" data-md-loading><span class="bkf-spin"></span>{{ $t('جارٍ تحميل الخريطة…', 'Loading map…') }}</div>
      </div>
      <div class="md-dock">
        <div class="md-count" data-md-count></div>
        <div class="md-deck" data-md-deck>
          @for($i = 0; $i < 3; $i++)<div class="mc is-skeleton"><span class="sk a"></span><span class="sk b"></span><span class="sk c"></span></div>@endfor
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* ── legend chips ── */
.md-chips{display:flex;gap:8px;margin:clamp(18px,2.4vw,26px) calc(var(--bk-gutter) * -1) 14px;padding:2px var(--bk-gutter) 6px;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.md-chips::-webkit-scrollbar{display:none}
@media (min-width:900px){ .md-chips{margin-inline:0;padding-inline:0;flex-wrap:wrap;overflow:visible} }
.md-chip{flex:0 0 auto;display:inline-flex;align-items:center;gap:9px;min-height:46px;padding:0 8px 0 16px;padding-inline:14px 8px;border-radius:var(--bk-r-pill);cursor:pointer;white-space:nowrap;
  background:var(--bk-surface);color:var(--bk-text-soft);border:1.5px solid var(--bk-border);font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.98rem;transition:background .25s,color .25s,border-color .25s,transform .2s;-webkit-tap-highlight-color:transparent}
.md-chip:hover{border-color:var(--bk-border-strong);color:var(--bk-text)}
.md-chip:active{transform:scale(.97)}
.md-chip i{width:12px;height:12px;border-radius:3px;transform:rotate(45deg);background:var(--g);flex:0 0 auto;box-shadow:0 0 0 2px #fff,0 0 0 3px color-mix(in srgb,var(--g) 55%,transparent)}
.md-chip .n{min-width:26px;height:26px;padding:0 8px;border-radius:13px;display:grid;place-items:center;font-family:var(--bk-font-ui);font-size:.78rem;font-weight:800;background:var(--bk-surface-2);color:var(--bk-text-muted);transition:background .25s,color .25s}
.md-chip.is-on{background:var(--g,var(--bk-accent-fill));color:#fff;border-color:transparent;box-shadow:0 10px 18px -10px var(--g,rgba(46,58,31,.7))}
.md-chip.is-on i{box-shadow:0 0 0 2px #fff;background:#fff}
.md-chip.is-on .n{background:rgba(255,255,255,.22);color:#fff}
.md-chip:not([style]).is-on{background:var(--bk-accent-fill)}
.md-chip.is-near{padding-inline:14px;background:var(--bk-grad-gold);color:var(--bk-gold-ink);border-color:transparent;margin-inline-start:auto}
@media (max-width:899px){ .md-chip.is-near{margin-inline-start:0} }

/* ── stage ── */
.md-stage{position:relative;display:grid;grid-template-columns:minmax(0,1fr);border:1px solid var(--bk-border);border-radius:30px;overflow:hidden;background:var(--bk-surface);box-shadow:var(--bk-shadow);height:clamp(520px,calc(100svh - 150px),700px)}
.md-canvas{position:relative;isolation:isolate;min-height:0;grid-area:1/1}
#bkf-disco-map{position:absolute;inset:0;background:var(--bk-surface-2)}
.md-loading{position:absolute;inset:0;z-index:3;display:flex;align-items:center;justify-content:center;gap:10px;background:var(--bk-surface-2);color:var(--bk-text-muted);font-family:var(--bk-font-ui);font-weight:700}
.md-dock{grid-area:1/1;align-self:end;position:relative;z-index:5;pointer-events:none;padding:0 0 12px}
.md-count{display:none}
.md-deck{pointer-events:auto;display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;padding:6px 14px 8px;scroll-padding-inline:14px;scrollbar-width:none;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain}
.md-deck::-webkit-scrollbar{display:none}

@media (min-width:900px){
  .md-stage{grid-template-columns:400px minmax(0,1fr);height:min(660px,80vh)}
  .md-canvas{grid-area:1/2}
  .md-dock{grid-area:1/1;align-self:stretch;pointer-events:auto;display:flex;flex-direction:column;min-height:0;padding:0;border-inline-end:1px solid var(--bk-border);background:var(--bk-surface)}
  .md-count{display:block;flex:0 0 auto;padding:14px 18px;font-family:var(--bk-font-ui);font-weight:800;font-size:.85rem;color:var(--bk-text-muted);border-bottom:1px solid var(--bk-border)}
  .md-count:empty{display:none}
  .md-deck{flex:1 1 auto;flex-direction:column;overflow-x:hidden;overflow-y:auto;scroll-snap-type:none;padding:14px;gap:14px}
}

/* ── the card: tinted type header, a medallion straddling it, then the facts ── */
.mc{position:relative;flex:0 0 min(82vw,330px);scroll-snap-align:start;display:flex;flex-direction:column;border-radius:26px;overflow:hidden;background:var(--bk-surface);border:1.5px solid var(--bk-border);
  box-shadow:0 20px 32px -18px rgba(28,36,14,.55);transition:box-shadow .3s,border-color .3s,transform .3s var(--bk-ease)}
@media (min-width:900px){ .mc{flex:0 0 auto;box-shadow:var(--bk-shadow-sm)} }
.mc:hover{border-color:color-mix(in srgb,var(--g) 45%,var(--bk-border))}
.mc.is-active{border-color:var(--g);box-shadow:0 0 0 3px color-mix(in srgb,var(--g) 28%,transparent),0 24px 36px -16px rgba(28,36,14,.6)}
.mc-head{display:flex;align-items:center;gap:8px;padding:11px 14px 28px;background:color-mix(in srgb,var(--g) 16%,var(--bk-surface))}
.mc-type{display:inline-flex;align-items:center;gap:7px;padding:4px 11px;border-radius:999px;background:var(--g);color:#fff;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.8rem;white-space:nowrap}
.mc-type::before{content:"";width:6px;height:6px;background:currentColor;opacity:.85;transform:rotate(45deg)}
.mc-st{margin-inline-start:auto;display:inline-flex;align-items:center;gap:6px;font-family:var(--bk-font-ui);font-weight:800;font-size:.76rem}
.mc-st i{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px color-mix(in srgb,currentColor 20%,transparent)}
.mc-st.on{color:var(--bk-success)} .mc-st.off{color:var(--bk-danger)}
.mc-d{font-family:var(--bk-font-ui);font-weight:800;font-size:.76rem;color:var(--g)}
.mc-body{display:flex;align-items:flex-end;gap:12px;padding:0 14px;margin-top:-22px}
.mc-medal{position:relative;flex:0 0 66px;width:66px;height:66px;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:var(--g);border:3px solid #fff;box-shadow:0 0 0 2px var(--bk-gold),0 10px 18px -8px rgba(0,0,0,.5)}
.mc-medal img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#fff}
.mc-medal b{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.5rem;color:#fff}
.mc-t{flex:1;min-width:0;padding-bottom:2px}
.mc-t h4{margin:0;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.15rem;line-height:1.3;color:var(--bk-text);overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical}
.mc-meta{margin:2px 0 0;display:flex;flex-wrap:wrap;gap:2px 10px;font-family:var(--bk-font-ui);font-size:.82rem;color:var(--bk-text-muted)}
.mc-rate{font-weight:800;color:var(--bk-text)} .mc-rate i{font-style:normal;font-weight:500;color:var(--bk-text-muted)} .mc-rate.none{color:var(--bk-text-soft)}
.mc-svcs{display:flex;gap:6px;flex-wrap:wrap;padding:10px 14px 0;min-height:0}
.mc-svcs span{padding:3px 10px;border-radius:999px;background:var(--bk-surface-2);color:var(--bk-text-soft);font-family:var(--bk-font-ui);font-size:.76rem;font-weight:700;border:1px solid var(--bk-border)}
.mc-foot{margin-top:auto;display:flex;align-items:center;gap:8px;padding:12px 14px 14px}
.mc-price{flex:1;min-width:0;font-family:var(--bk-font-ui);font-size:.76rem;color:var(--bk-text-muted);line-height:1.25;white-space:nowrap}
.mc-price b{display:block;font-family:var(--bk-font-display);font-weight:700;font-size:1.12rem;color:var(--bk-gold-strong)}
.mc-foot a{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 16px;border-radius:999px;text-decoration:none;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.92rem;white-space:nowrap;transition:transform .2s,background .2s}
.mc-foot a:active{transform:scale(.96)}
.mc-view{color:var(--g);border:1.5px solid color-mix(in srgb,var(--g) 55%,transparent);background:transparent}
.mc-book{background:var(--bk-grad-gold);color:var(--bk-gold-ink);box-shadow:var(--bk-shadow-accent)}
.mc-empty{flex:1;padding:40px 16px;text-align:center;font-family:var(--bk-font-ui);font-weight:700;color:var(--bk-text-muted)}
.mc.is-skeleton{min-height:210px;padding:16px;gap:12px;pointer-events:none}
.mc.is-skeleton .sk{display:block;border-radius:10px;background:linear-gradient(90deg,var(--bk-surface-2),var(--bk-surface-3),var(--bk-surface-2));background-size:200% 100%;animation:md-sk 1.4s linear infinite}
.mc.is-skeleton .a{height:22px;width:40%} .mc.is-skeleton .b{height:54px;width:90%} .mc.is-skeleton .c{height:36px;width:70%}
@keyframes md-sk{to{background-position:-200% 0}}

/* ── pins: a medallion of the type's own image, ringed in the type's colour ── */
.md-pin-wrap{background:none;border:0}
.md-pin{position:relative;display:block;width:44px;height:54px;transform-origin:50% 92%;transition:transform .35s var(--bk-spring)}
.md-pin-face{position:absolute;left:1px;top:0;width:42px;height:42px;z-index:2;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:var(--g);border:3px solid #fff;
  box-shadow:0 0 0 2.5px var(--g),0 12px 16px -6px rgba(0,0,0,.55)}
.md-pin-face img{width:100%;height:100%;object-fit:cover;display:block;background:#fff}
.md-pin-face b{font-family:'Tajawal',system-ui,sans-serif;font-weight:800;font-size:1.05rem;color:#fff}
.md-pin-tail{position:absolute;left:50%;bottom:5px;width:12px;height:12px;margin-left:-6px;z-index:1;border-radius:2px;transform:rotate(45deg);background:var(--g);box-shadow:0 8px 8px -4px rgba(0,0,0,.4)}
.md-pin:hover{transform:scale(1.1)}
.md-pin.is-active{transform:scale(1.32)}
.md-pin.is-active .md-pin-face{box-shadow:0 0 0 3px #E6C77F,0 0 0 8px color-mix(in srgb,var(--g) 38%,transparent),0 16px 22px -8px rgba(0,0,0,.6)}
.md-pin.is-active::before{content:"";position:absolute;left:1px;top:0;width:42px;height:42px;border-radius:50%;border:2px solid var(--g);animation:md-pulse 1.8s ease-out infinite}
@keyframes md-pulse{from{transform:scale(1);opacity:.8}to{transform:scale(2);opacity:0}}
.md-me{width:18px;height:18px;border-radius:50%;background:#2F6FED;border:3px solid #fff;box-shadow:0 0 0 0 rgba(47,111,237,.5);animation:md-me 2s ease-out infinite}
@keyframes md-me{to{box-shadow:0 0 0 18px rgba(47,111,237,0)}}
/* tone the stock tiles into the brand: quieter, warmer, so the coloured pins carry the page */
.md-canvas .leaflet-tile-pane{filter:saturate(.45) sepia(.18) brightness(1.04) contrast(.94)}
[data-bk-theme="dark"] .md-canvas .leaflet-tile-pane{filter:invert(1) hue-rotate(180deg) saturate(.5) sepia(.25) brightness(.78) contrast(.92)}
.md-canvas .leaflet-control-attribution{font-size:10px;background:color-mix(in srgb,var(--bk-surface) 80%,transparent);color:var(--bk-text-muted)}
.md-canvas .leaflet-control-attribution a{color:var(--bk-text-soft)}
.md-canvas .leaflet-bar a{width:40px;height:40px;line-height:40px;color:var(--bk-text);background:var(--bk-surface);border-color:var(--bk-border)}
@media (prefers-reduced-motion:reduce){ .md-pin,.mc{transition:none} .md-pin.is-active::before,.md-me,.mc.is-skeleton .sk{animation:none} }
</style>

<script>
(function () {
  'use strict';
  var root = document.querySelector('[data-md]');
  var mapEl = document.querySelector('[data-disco-map]');
  var deck  = document.querySelector('[data-md-deck]');
  if (!root || !mapEl || !deck) return;

  var AR = @json($isAr), CUR = @json($currency);
  var COLORS = @json($mdColors), GROUP_OF = @json($mdGroupOf);
  var URLS = { data: @json(route('front.map.branches')), css: @json(asset('vendor/leaflet/leaflet.css')), js: @json(asset('vendor/leaflet/leaflet.js')) };
  var countEl = document.querySelector('[data-md-count]'), loadEl = document.querySelector('[data-md-loading]');
  var chips = [].slice.call(root.querySelectorAll('[data-md-filter]'));
  var locateBtn = root.querySelector('[data-md-locate]');
  var mobile = window.matchMedia('(max-width: 899px)');
  var DAMASCUS = [33.5138, 36.2765];

  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
  var groupOf = function (b) { return GROUP_OF[b.cat_slug] || 'other'; };
  var colorOf = function (b) { return COLORS[groupOf(b)] || COLORS.other; };
  var initial = function (b) { return esc(String(b.category || b.name || '·').charAt(0)); };

  var map, L, data = [], markers = {}, cards = {}, activeId = null, filter = '', loaded = false, inited = false, tiles, userMark, progScroll = 0;

  function loadLeaflet(cb) {
    if (window.L) { cb(window.L); return; }
    if (!document.querySelector('link[data-leaflet]')) {
      var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = URLS.css; l.setAttribute('data-leaflet', ''); document.head.appendChild(l);
    }
    var ex = document.querySelector('script[data-leaflet]');
    if (ex) { ex.addEventListener('load', function () { cb(window.L); }); return; }
    var s = document.createElement('script'); s.src = URLS.js; s.defer = true; s.setAttribute('data-leaflet', '');
    s.addEventListener('load', function () { cb(window.L); }); document.body.appendChild(s);
  }

  function distText(b) {
    var up = window.__bkUserPos && window.__bkUserPos(); if (!up || b.lat == null) return '';
    var R = 6371, p = Math.PI / 180, dLa = (b.lat - up.lat) * p, dLo = (b.lng - up.lng) * p;
    var s = Math.sin(dLa / 2) ** 2 + Math.cos(up.lat * p) * Math.cos(b.lat * p) * Math.sin(dLo / 2) ** 2;
    var d = R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
    return window.__bkKm ? window.__bkKm(d) : d.toFixed(1) + ' km';
  }

  function pinIcon(b, active) {
    var face = b.cat_image ? '<img src="' + esc(b.cat_image) + '" alt="">' : '<b>' + initial(b) + '</b>';
    return L.divIcon({
      className: 'md-pin-wrap',
      html: '<span class="md-pin' + (active ? ' is-active' : '') + '" style="--g:' + colorOf(b) + '"><span class="md-pin-face">' + face + '</span><i class="md-pin-tail"></i></span>',
      iconSize: [44, 54], iconAnchor: [22, 50]
    });
  }

  function cardHTML(b) {
    var src = b.image || b.company_logo || b.cat_image;
    var medal = src ? '<img src="' + esc(src) + '" alt="" loading="lazy">' : '<b>' + initial(b) + '</b>';
    var rate = b.avg_rating ? '<span class="mc-rate">★ ' + b.avg_rating + ' <i>(' + b.review_count + ')</i></span>' : '<span class="mc-rate none">' + (AR ? 'جديد على GlowRez' : 'New on GlowRez') + '</span>';
    var st = b.open_now === true ? '<span class="mc-st on"><i></i>' + (AR ? 'مفتوح الآن' : 'Open') + '</span>'
           : b.open_now === false ? '<span class="mc-st off"><i></i>' + (AR ? 'مغلق الآن' : 'Closed') + '</span>' : '';
    var dt = distText(b);
    var svcs = (b.services || []).slice(0, 2).map(function (s) { return '<span>' + esc(s) + '</span>'; }).join('');
    var more = (b.svc_count || 0) - Math.min(2, (b.services || []).length);
    if (more > 0) svcs += '<span class="bkf-tnum">+' + more + '</span>';
    var price = b.min_price ? (AR ? 'يبدأ من' : 'From') + '<b class="bkf-tnum">' + Math.round(b.min_price).toLocaleString() + ' ' + CUR + '</b>' : '<b style="font-family:inherit;font-size:.95rem;color:var(--bk-text)">' + (AR ? 'أسعار متنوعة' : 'Varied') + '</b>';
    var meta = [b.city ? esc(b.city) : '', ].filter(Boolean).join('');
    return '<article class="mc" data-mapcard="' + b.id + '" style="--g:' + colorOf(b) + '" tabindex="0">' +
      '<header class="mc-head">' + (b.category ? '<span class="mc-type">' + esc(b.category) + '</span>' : '') + st + (dt ? '<span class="mc-d bkf-tnum">' + dt + '</span>' : '') + '</header>' +
      '<div class="mc-body"><span class="mc-medal">' + medal + '</span><div class="mc-t"><h4>' + esc(b.name) + '</h4><p class="mc-meta">' + (meta ? '<span>' + meta + '</span>' : '') + rate + '</p></div></div>' +
      (svcs ? '<div class="mc-svcs">' + svcs + '</div>' : '') +
      '<footer class="mc-foot"><span class="mc-price">' + price + '</span>' +
        '<a href="' + esc(b.url) + '" class="mc-view">' + (AR ? 'عرض' : 'View') + '</a>' +
        '<a href="' + esc(b.book_url) + '" class="mc-book">' + (AR ? 'احجز' : 'Book') + '</a></footer></article>';
  }

  function dockH() { return mobile.matches ? (deck.offsetHeight + 24) : 0; }

  function centerOn(b, zoom) {
    map.setView([b.lat, b.lng], Math.max(map.getZoom(), zoom || 14), { animate: true });
    if (mobile.matches) setTimeout(function () { map.panBy([0, Math.round(dockH() / 2)], { animate: true }); }, 280);
  }

  function setActive(id, fly, scrollCard) {
    if (activeId != null) {
      if (cards[activeId]) cards[activeId].classList.remove('is-active');
      if (markers[activeId]) { markers[activeId].setIcon(pinIcon(markers[activeId].__b, false)); markers[activeId].setZIndexOffset(0); }
    }
    activeId = id;
    var b = data.find(function (x) { return x.id === id; }); if (!b) return;
    if (cards[id]) {
      cards[id].classList.add('is-active');
      if (scrollCard) {
        progScroll = Date.now();
        if (mobile.matches) deck.scrollTo({ left: cards[id].offsetLeft - (deck.offsetWidth - cards[id].offsetWidth) / 2 - 0, behavior: 'smooth' });
        else cards[id].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }
    if (markers[id]) { markers[id].setIcon(pinIcon(b, true)); markers[id].setZIndexOffset(1000); if (fly) centerOn(b); }
  }

  function render() {
    var shown = data.filter(function (b) { return !filter || groupOf(b) === filter; });
    deck.innerHTML = shown.map(cardHTML).join('') || '<div class="mc-empty">' + (AR ? 'لا توجد أماكن من هذا النوع بعد' : 'No places of this type yet') + '</div>';
    cards = {};
    shown.forEach(function (b) {
      var el = deck.querySelector('[data-mapcard="' + b.id + '"]'); if (!el) return;
      cards[b.id] = el;
      el.addEventListener('click', function (e) { if (e.target.closest('a')) return; setActive(b.id, true, false); });
      el.addEventListener('keydown', function (e) { if (e.key === 'Enter') setActive(b.id, true, false); });
    });
    if (countEl) countEl.textContent = shown.length + ' ' + (AR ? 'مكان' : 'places');

    Object.keys(markers).forEach(function (k) { map.removeLayer(markers[k]); });
    markers = {};
    var pts = [];
    shown.forEach(function (b) {
      if (b.lat == null) return;
      var mk = L.marker([b.lat, b.lng], { icon: pinIcon(b, false), keyboard: false, riseOnHover: true }).addTo(map);
      mk.__b = b;
      mk.on('click', function () { setActive(b.id, true, true); });
      markers[b.id] = mk; pts.push([b.lat, b.lng]);
    });
    activeId = null;
    if (pts.length) {
      var bottom = dockH() + 36;
      map.fitBounds(pts, { paddingTopLeft: [48, 48], paddingBottomRight: [48, bottom], maxZoom: 14 });
      if (shown[0]) setTimeout(function () { setActive(shown[0].id, false, false); }, 60);
    }
    deck.scrollLeft = 0;
  }

  function updateCounts() {
    var counts = { '': data.length };
    data.forEach(function (b) { var g = groupOf(b); counts[g] = (counts[g] || 0) + 1; });
    [].slice.call(root.querySelectorAll('[data-md-n]')).forEach(function (n) { n.textContent = counts[n.getAttribute('data-md-n')] || 0; });
  }

  // swipe the deck → the map follows
  var scT;
  deck.addEventListener('scroll', function () {
    if (!mobile.matches || Date.now() - progScroll < 700) return;
    clearTimeout(scT);
    scT = setTimeout(function () {
      var mid = deck.getBoundingClientRect().left + deck.offsetWidth / 2, best = null, bd = 1e9;
      Object.keys(cards).forEach(function (k) { var r = cards[k].getBoundingClientRect(), d = Math.abs(r.left + r.width / 2 - mid); if (d < bd) { bd = d; best = +k; } });
      if (best != null && best !== activeId) setActive(best, true, false);
    }, 140);
  }, { passive: true });


  function initMap() {
    if (inited) return; inited = true;
    loadLeaflet(function (Lref) {
      L = Lref;
      map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: false, attributionControl: false, tap: true }).setView(DAMASCUS, 12);
      L.control.attribution({ position: 'topright', prefix: false }).addTo(map);
      if (!mobile.matches) L.control.zoom({ position: 'topleft' }).addTo(map);
      tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      fetch(URLS.data).then(function (r) { return r.json(); }).then(function (list) {
        data = list.filter(function (b) { return b.lat != null && b.lng != null; });
        loaded = true; if (loadEl) loadEl.style.display = 'none';
        updateCounts(); render();
        setTimeout(function () { map.invalidateSize(); }, 200);
      }).catch(function () { if (loadEl) loadEl.textContent = AR ? 'تعذّر تحميل الخريطة' : 'Couldn’t load the map'; });
    });
  }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (ents) { ents.forEach(function (e) { if (e.isIntersecting) { initMap(); io.disconnect(); } }); }, { rootMargin: '300px 0px' });
    io.observe(root);
  } else { initMap(); }

  chips.forEach(function (c) {
    c.addEventListener('click', function () {
      chips.forEach(function (x) { x.classList.remove('is-on'); x.setAttribute('aria-pressed', 'false'); });
      c.classList.add('is-on'); c.setAttribute('aria-pressed', 'true');
      filter = c.getAttribute('data-md-filter') || '';
      if (loaded) render();
    });
  });

  if (locateBtn) locateBtn.addEventListener('click', function () {
    if (!window.__bkRequestGeo) return;
    initMap();
    window.__bkRequestGeo(function (ok) {
      if (!ok || !loaded) return;
      var up = window.__bkUserPos();
      var dd = function (x) { var p = Math.PI / 180, dLa = (x.lat - up.lat) * p, dLo = (x.lng - up.lng) * p, s = Math.sin(dLa / 2) ** 2 + Math.cos(up.lat * p) * Math.cos(x.lat * p) * Math.sin(dLo / 2) ** 2; return 6371 * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s)); };
      data.sort(function (a, b) { return dd(a) - dd(b); });
      render();
      if (userMark) map.removeLayer(userMark);
      userMark = L.marker([up.lat, up.lng], { icon: L.divIcon({ className: 'md-pin-wrap', html: '<span class="md-me"></span>', iconSize: [18, 18], iconAnchor: [9, 9] }), interactive: false }).addTo(map);
      var near = data.find(function (b) { return !filter || groupOf(b) === filter; });
      if (near) setActive(near.id, true, true);
    });
  });
})();
</script>
