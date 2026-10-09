{{-- ══════════════ 4 · FEATURED VENUES — shelf view with a type filter ══════════════
     Filter (top): الكل / صالونات / سبا … — each type has its own colour, reused on every chip.
     Each type is rendered server-side, so switching never reloads or re-queries.
     Expects from the page: $featured, $featuredPool, $categories, $venuesUrl, $t, $isAr. --}}
@php
  $fpBadge = [
      'hot' => ['flame',    'الأكثر حجزاً',   'Most booked'],
      'top' => ['award',    'الأعلى تقييماً', 'Top rated'],
      'rec' => ['sparkles', 'موصى به',        'Recommended'],
      'new' => ['zap',      'جديد',           'New'],
  ];
  $fpPrice = function ($c) use ($isAr) {
      if (!$c->min_price) return null;
      $cur = $c->currency ?? config('booksy.default_currency', 'SYP');
      $lbl = $isAr ? (config("booksy.currencies.$cur.symbol") ?? $cur) : $cur;
      return number_format($c->min_price, 0) . ' ' . $lbl;
  };

  // venue-type groups (what the user actually thinks in): keep spa apart from salons, clinics apart from both
  $fgGroups = config('venue_types.groups');
  $fgOf = function ($c) use ($fgGroups) {
      foreach ($fgGroups as $key => $g) { if (in_array($c->cat_slug, $g['slugs'], true)) return $key; }
      return 'other';
  };
  $fgPool = collect($featuredPool ?? $featured)->values();
  $fgCatName = $categories->mapWithKeys(fn ($cat) => [$cat->slug => $isAr ? $cat->name_ar : $cat->name_en]);

  $fgTabs = [['key' => 'all', 'label' => $t('الكل', 'All'), 'count' => $fgPool->count(), 'items' => $fgPool->take(6), 'note' => null]];
  foreach ($fgGroups as $key => $g) {
      $inGroup = $fgPool->filter(fn ($c) => in_array($c->cat_slug, $g['slugs'], true))->values();
      if ($inGroup->isEmpty()) continue;
      $names = collect($g['slugs'])->filter(fn ($s) => $inGroup->contains('cat_slug', $s))->map(fn ($s) => $fgCatName[$s] ?? $s)->implode(' · ');
      $fgTabs[] = ['key' => $key, 'label' => $isAr ? $g['ar'] : $g['en'], 'count' => $inGroup->count(), 'items' => $inGroup->take(6), 'note' => $names];
  }
  $fgShowTabs = count($fgTabs) > 2;
@endphp
<section class="bkf-section fh" id="discover" style="padding-top:0" data-fh>
  <div class="bkf-container-wide">

    <header class="fh-head bkf-reveal">
      <div>
        <h2 class="bkf-title fp-title bkf-mt-0">{{ $t('أماكن', 'Featured') }} <span class="em">{{ $t('مميّزة', 'venues') }}</span></h2>
        <p class="fp-lead">{{ $t('مختارة بعناية من الأماكن الأكثر حجزاً وتقييماً. اختر النوع لترى ما يهمّك فقط.', 'Handpicked from the most booked and best rated. Pick a type to see only what you need.') }}</p>
      </div>
    </header>

    @if($fgPool->isEmpty())
      <p class="fp-empty">{{ $t('ستظهر هنا الأماكن المميّزة فور انضمامها.', 'Featured venues will appear here as soon as they join.') }}</p>
    @else
      <div class="fh-bar">
        @if($fgShowTabs)
        <div class="fh-tabs" role="group" aria-label="{{ $t('نوع المكان', 'Type of place') }}" data-fh-tabs>
          @foreach($fgTabs as $k => $tab)
            <button type="button" class="fh-tab is-g-{{ $tab['key'] }}" data-fh-tab="{{ $tab['key'] }}" aria-pressed="{{ $k === 0 ? 'true' : 'false' }}">
              <span>{{ $tab['label'] }}</span><span class="n bkf-tnum">{{ $tab['count'] }}</span>
            </button>
          @endforeach
        </div>
        @endif
        <a href="{{ $venuesUrl }}" class="fp-all fh-all" data-venues-cta>
          <span>{{ $t('عرض جميع الأماكن', 'View all venues') }}</span>
          <span class="fp-all-ic"><x-icon name="arrow-right" :size="18"/></span>
        </a>
      </div>
      @foreach($fgTabs as $k => $tab)
        @if($tab['note'])<p class="fh-note" data-fh-note="{{ $tab['key'] }}" hidden>{{ $tab['note'] }}</p>@endif
      @endforeach

      <div class="fh-stage">
        @foreach($fgTabs as $k => $tab)
          <div class="fh-group" data-fh-group="{{ $tab['key'] }}" @if($k > 0) hidden @endif>
            @include('front.partials.featured-b', ['items' => $tab['items'], 'eager' => $k === 0])
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>

<style>
/* ── type filter ── */
.is-g-salons{--g:#8A6317}.is-g-spa{--g:#1F6B66}.is-g-beauty{--g:#9C3A68}.is-g-clinics{--g:#34559A}.is-g-other,.is-g-all{--g:#4B5D34}
.ft-chip{display:inline-flex;align-items:center;gap:7px;align-self:flex-start;padding:5px 12px;border-radius:var(--bk-r-pill);background:var(--g,#4B5D34);color:#fff;
  font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.82rem;line-height:1.3;white-space:nowrap}
.ft-chip::before{content:"";width:7px;height:7px;background:currentColor;opacity:.85;transform:rotate(45deg)}
.fh-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:clamp(18px,2.4vw,26px)}
.fh-bar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:clamp(14px,2vw,20px)}
.fh-all{margin-top:0}
.fh-tabs{display:flex;gap:6px;width:max-content;max-width:100%;padding:6px;overflow-x:auto;scrollbar-width:none;background:var(--bk-surface-2);border:1px solid var(--bk-border);border-radius:var(--bk-r-pill)}
.fh-tabs::-webkit-scrollbar{display:none}
.fh-tab{flex:0 0 auto;display:inline-flex;align-items:center;gap:10px;min-height:48px;padding-inline:18px 10px;border:0;border-radius:var(--bk-r-pill);background:transparent;cursor:pointer;
  font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.05rem;color:var(--bk-text-soft);white-space:nowrap;transition:background .3s var(--bk-ease),color .3s}
.fh-tab::before{content:"";width:10px;height:10px;background:var(--g);transform:rotate(45deg);border-radius:2px;flex:0 0 auto}
.fh-tab.is-g-all::before{background:var(--bk-grad-gold)}
.fh-tab:hover{color:var(--bk-text);background:color-mix(in srgb,var(--bk-surface) 70%,transparent)}
.fh-tab .n{min-width:28px;height:28px;padding:0 8px;border-radius:14px;display:grid;place-items:center;font-family:var(--bk-font-ui);font-size:.8rem;font-weight:800;background:var(--bk-surface);color:var(--bk-text-muted);transition:background .3s,color .3s}
.fh-tab[aria-pressed="true"]{background:var(--bk-accent-fill);color:var(--bk-accent-ink);box-shadow:0 8px 18px -8px rgba(46,58,31,.6)}
.fh-tab[aria-pressed="true"]::before{box-shadow:0 0 0 2px rgba(255,255,255,.9)}
.fh-tab[aria-pressed="true"] .n{background:var(--bk-gold);color:var(--bk-gold-ink)}
.fh-note{margin:0 0 clamp(14px,2vw,22px);font-family:var(--bk-font-ui);font-size:.95rem;color:var(--bk-text-muted)}
.fh-group[hidden],.fh-note[hidden]{display:none}
@media (max-width:719px){ .fh-all{display:none} }

/* ── view B: shelf ── */
.fb{--fb-ease:cubic-bezier(.22,1,.36,1)}
.fb-row{display:flex;gap:12px;height:clamp(500px,68vh,620px)}
.fb-slat{position:relative;flex:1 1 0;min-width:0;border-radius:42px;overflow:hidden;isolation:isolate;color:#fff;background:#2a3320;
  transition:flex-grow .9s var(--fb-ease),opacity .8s var(--fb-ease) calc(var(--i,0)*.07s),transform .9s var(--fb-ease) calc(var(--i,0)*.07s),height .7s var(--fb-ease);
  box-shadow:0 26px 44px -30px rgba(28,36,14,.6)}
.fb-slat.is-on{flex-grow:7}
.fb-art,.fb-art .fa-ph{position:absolute;inset:0}
.fb-art .fa-img{transform:scale(1.15);transition:transform 1.8s var(--fb-ease)}
.fb-slat.is-on .fb-art .fa-img{transform:scale(1)}
.fb-art .fa-seal{display:none}
.fb-shade{position:absolute;inset:0;z-index:1;pointer-events:none;background:linear-gradient(to top,rgba(14,18,8,.86) 0%,rgba(14,18,8,.42) 38%,rgba(14,18,8,0) 68%)}
.fb-slat::after{content:"";position:absolute;inset:0;z-index:1;pointer-events:none;background:rgba(14,18,8,.14);transition:opacity .6s ease}
.fb-slat.has-img::after{background:rgba(14,18,8,.5)}
.fb-slat.is-on::after{opacity:0}
.fb-slat:not(.is-on):hover::after{opacity:.5}
.fb-tab{position:absolute;inset:0;z-index:3;display:flex;flex-direction:column;align-items:center;justify-content:space-between;gap:12px;padding:20px 6px 20px;
  border:0;background:none;color:#fff;cursor:pointer;font:inherit;transition:opacity .4s ease}
.fb-slat.is-on .fb-tab{opacity:0;pointer-events:none}
.fb-tab-name{flex:1;min-height:0;display:flex;align-items:center;writing-mode:vertical-rl;transform:rotate(180deg);font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:clamp(1.2rem,.9rem + .9vw,1.7rem);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fb-tab-type{writing-mode:vertical-rl;transform:rotate(180deg);max-height:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:12px 6px;border-radius:var(--bk-r-pill);background:var(--g,#4B5D34);color:#fff;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.78rem}
.fb-slat:has(:focus-visible){outline:3px solid var(--bk-gold);outline-offset:3px}
.fb-tab:focus-visible{outline:none}
.fb-cover{position:absolute;inset:0;z-index:2;pointer-events:none}
.fb-slat.is-on .fb-cover{pointer-events:auto}
.fb-open{position:absolute;inset:0;z-index:3;min-width:min(460px,100%);padding:clamp(22px,3vw,42px);display:flex;flex-direction:column;justify-content:flex-end;visibility:hidden;pointer-events:none}
.fb-slat.is-on .fb-open{visibility:visible}
.fb-open a,.fb-open button{pointer-events:auto}
.fb-badge{top:clamp(22px,3vw,40px);inset-inline:auto;inset-inline-start:clamp(22px,3vw,42px);margin:0}
.fb-fav{position:absolute;top:clamp(20px,2.8vw,38px);inset-inline-end:clamp(20px,2.8vw,38px)}
.fb-body{display:flex;flex-direction:column;gap:12px;max-width:600px;padding:clamp(18px,2.2vw,28px);border-radius:28px;background:rgba(14,18,8,.9);box-shadow:0 20px 40px -20px rgba(0,0,0,.6)}
.fb-body > *,.fb-open > .fp-badge,.fb-open > .fp-fav{opacity:0;translate:0 18px;transition:opacity .2s ease,translate .2s ease}
.fb-slat.is-on .fb-body > *,.fb-slat.is-on .fb-open > .fp-badge,.fb-slat.is-on .fb-open > .fp-fav{opacity:1;translate:0 0;transition:opacity .7s var(--fb-ease) calc(.4s + var(--k,0)*.08s),translate .9s var(--fb-ease) calc(.4s + var(--k,0)*.08s)}
.fb-who{display:flex;align-items:center;gap:14px}
.fb-who-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:8px}
.fb-open .fb-name{font-size:clamp(2.2rem,1.5rem + 2.6vw,3.6rem);line-height:1.12;color:#fff;text-wrap:balance}
.fb-open .fp-foot{max-width:520px}
.fb-open .fp-price b{font-size:1.9rem}
.fb-open .fp-price b.is-text{font-size:1.25rem}
.fb-open .fp-book{padding:15px 30px;font-size:1rem}
.fb-slat[data-has-distance] .fp-dist{display:inline-flex!important}
.fb-js:not(.is-seen) .fb-slat{opacity:0;transform:translateY(46px)}
@media (max-width:899px){
  .fb-row{flex-direction:column;height:auto;gap:10px}
  .fb-slat{flex:0 0 auto;height:84px;border-radius:30px}
  .fb-slat.is-on{flex-grow:0;height:540px}
  .fb-tab{flex-direction:row;padding:0 14px;gap:12px}
  .fb-tab-name{writing-mode:horizontal-tb;transform:none;font-size:1.2rem;justify-content:flex-start}
  .fb-tab-type{writing-mode:horizontal-tb;transform:none;padding:5px 12px;max-height:none}
  .fb-open{min-width:0;padding:14px}
  .fb-badge{top:18px;inset-inline-start:20px}
  .fb-fav{top:16px;inset-inline-end:16px}
  .fb-open .fb-name{font-size:2rem}
  .fb-open .fp-price b{font-size:1.45rem}
  .fb-open .fp-book{padding:13px 22px}
  .fb-who .fa-seal{width:60px;height:60px}
  .fb-body{padding:16px;border-radius:24px}
}

@media (prefers-reduced-motion:reduce){
  .fb *,.fb *::before,.fb *::after{transition:none!important;animation:none!important}
  .fb-js:not(.is-seen) .fb-slat{opacity:1;transform:none}
  .fb-body > *,.fb-open > .fp-badge,.fb-open > .fp-fav{opacity:1;translate:none}
}
</style>

<script>
(function () {
  'use strict';
  var hub = document.querySelector('[data-fh]');
  if (!hub) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var rtl = getComputedStyle(hub).direction === 'rtl';
  var all = function (s, r) { return [].slice.call((r || hub).querySelectorAll(s)); };

  /* ── view B · shelf: point / tap / Tab to open a spine ── */
  function initB(root) {
    var slats = all('[data-fb-slat]', root), row = root.querySelector('[data-fb-row]');
    root.classList.add('fb-js');
    if (!slats.length) { root.classList.add('is-seen'); return; }
    var wide = window.matchMedia('(min-width: 900px)');
    var cur = 0, t;
    function set(n) {
      if (n === cur || !slats[n]) return;
      slats[cur].classList.remove('is-on'); slats[cur].querySelector('.fb-tab').setAttribute('aria-expanded', 'false');
      slats[n].classList.add('is-on'); slats[n].querySelector('.fb-tab').setAttribute('aria-expanded', 'true');
      cur = n;
    }
    slats.forEach(function (s, i) {
      var tab = s.querySelector('.fb-tab');
      s.addEventListener('mouseenter', function () { if (!wide.matches) return; clearTimeout(t); t = setTimeout(function () { set(i); }, 80); });
      s.addEventListener('mouseleave', function () { clearTimeout(t); });
      tab.addEventListener('click', function () { set(i); });
      tab.addEventListener('focus', function () { set(i); });
      tab.addEventListener('keydown', function (e) {
        var fwd = rtl ? 'ArrowLeft' : 'ArrowRight', back = rtl ? 'ArrowRight' : 'ArrowLeft';
        var to = e.key === fwd || e.key === 'ArrowDown' ? i + 1 : e.key === back || e.key === 'ArrowUp' ? i - 1 : null;
        if (to === null || !slats[to]) return;
        e.preventDefault(); slats[to].querySelector('.fb-tab').focus();
      });
    });
    if (reduce || !('IntersectionObserver' in window)) { root.classList.add('is-seen'); return; }
    new IntersectionObserver(function (ents, io) {
      ents.forEach(function (e) { if (e.isIntersecting) { root.classList.add('is-seen'); io.disconnect(); } });
    }, { threshold: 0.25 }).observe(row);
  }
  all('[data-fb]').forEach(initB);

  /* ── type filter ── */
  var tabs = all('[data-fh-tab]'), notes = all('[data-fh-note]'), groups = all('[data-fh-group]');
  tabs.forEach(function (b) {
    b.addEventListener('click', function () {
      var group = b.getAttribute('data-fh-tab');
      groups.forEach(function (g) { g.hidden = g.getAttribute('data-fh-group') !== group; });
      tabs.forEach(function (t) { t.setAttribute('aria-pressed', t === b ? 'true' : 'false'); });
      notes.forEach(function (n) { n.hidden = n.getAttribute('data-fh-note') !== group; });
      if (b.scrollIntoView) b.scrollIntoView({ block: 'nearest', inline: 'center' });
    });
  });
})();
</script>
