{{-- ══════════════ 3 · "ماذا تريد اليوم؟" — one section, four ways to browse the categories ══════════════
     a · مجموعات  grouped directory (salons / spa / beauty / clinics, each with its colour)
     b · دوائر    medallion rail — the category image as a gold-ringed circle, icon badge on its edge
     c · فسيفساء  bento mosaic — the busiest category leads, name always on a solid band, not on the photo
     d · قائمة    ledger — big one-tap rows, the most scannable when there are many categories
     b/c/d read straight from the categories table (name, image, icon, active-place count).
     The switcher below the title remembers the choice. Expects: $categories, $t, $isAr. --}}
@php
  $ctCfg    = config('venue_types');
  $ctTones  = ['#4a6a34', '#b4502f', '#7a4585', '#1f7378', '#b07a1c', '#b04062', '#3f52a3', '#7a6244'];
  $ctTone   = fn ($slug) => $ctTones[abs(crc32((string) $slug)) % count($ctTones)];
  $ctImg    = fn ($cat) => $cat->image ? asset('storage/' . ltrim($cat->image, '/')) : null;
  $ctGroups = [];
  foreach ($categories as $cat) {
      $gk = 'other';
      foreach ($ctCfg['groups'] as $k => $g) { if (in_array($cat->slug, $g['slugs'], true)) { $gk = $k; break; } }
      $ctGroups[$gk][] = $cat;
  }
  $ctLabels = $ctCfg['groups'] + ['other' => $ctCfg['other']];
  $ctCats   = \App\Support\VenueTypes::categories($categories, $isAr);
  $ctBusy   = collect($ctCats)->sortByDesc('count')->values()->all();
  $ctViews  = ['a' => $t('مجموعات', 'Groups'), 'b' => $t('دوائر', 'Circles'), 'c' => $t('فسيفساء', 'Mosaic'), 'd' => $t('قائمة', 'List')];
@endphp
<section class="bkf-section ct" id="categories" data-cat-dir data-hero-hide="b" data-ct>
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal ct-headrow">
      <div>
        <h2 class="bkf-title fp-title bkf-mt-0">{{ $t('ماذا تريد', 'What are you') }} <span class="em">{{ $t('اليوم؟', 'looking for?') }}</span></h2>
        <p class="fp-lead">{{ $t('اختر نوع المكان وانتقل مباشرةً إلى أفضل ما يناسبك.', 'Pick a type of place and jump straight to the best fit.') }}</p>
      </div>
      <div class="fh-views" role="group" aria-label="{{ $t('طريقة العرض', 'View as') }}">
        <span class="fh-views-l">{{ $t('عرض:', 'View:') }}</span>
        @foreach($ctViews as $vk => $vl)
          <button type="button" class="fh-view-btn" data-ct-view="{{ $vk }}" aria-pressed="{{ $vk === 'a' ? 'true' : 'false' }}">{{ $vl }}</button>
        @endforeach
      </div>
    </div>

    @if(count($ctGroups))

    {{-- a · grouped directory --}}
    <div data-ct-pane="a">
      <div class="ct-grid">
        @foreach($ctLabels as $gk => $g)
          @continue(empty($ctGroups[$gk]))
          @php $list = $ctGroups[$gk]; $total = collect($list)->sum('companies_count'); $lead = $list[0]; @endphp
          <section class="ct-card is-g-{{ $gk }} bkf-reveal" aria-labelledby="ct-h-{{ $gk }}">
            <header class="ct-head">
              <div>
                <h3 id="ct-h-{{ $gk }}">{{ $isAr ? $g['ar'] : $g['en'] }}</h3>
                <p>{{ $total }} {{ $t('مكان', 'places') }}</p>
              </div>
              <span class="ct-thumb" aria-hidden="true">
                @if($ctImg($lead))<img src="{{ $ctImg($lead) }}" alt="" loading="lazy">@else<b style="background:{{ $ctTone($lead->slug) }}">{{ mb_substr($isAr ? $lead->name_ar : $lead->name_en, 0, 1) }}</b>@endif
              </span>
            </header>
            <ul class="ct-list" role="list">
              @foreach($list as $cat)
                <li>
                  <a href="{{ route('front.category', $cat->slug) }}" class="ct-row">
                    <span class="ct-ic" aria-hidden="true">
                      @if($ctImg($cat))<img src="{{ $ctImg($cat) }}" alt="" loading="lazy">@else<b style="background:{{ $ctTone($cat->slug) }}">{{ mb_substr($isAr ? $cat->name_ar : $cat->name_en, 0, 1) }}</b>@endif
                    </span>
                    <span class="ct-n">{{ $isAr ? $cat->name_ar : $cat->name_en }}</span>
                    <span class="ct-c bkf-tnum">{{ $cat->companies_count }}</span>
                    <span class="ct-go" aria-hidden="true"><x-icon name="arrow-right" :size="16"/></span>
                  </a>
                </li>
              @endforeach
            </ul>
          </section>
        @endforeach
      </div>
    </div>

    {{-- b · medallion rail --}}
    <div data-ct-pane="b" hidden>
      <ul class="cm-rail" role="list">
        @foreach($ctCats as $c)
          <li>
            <a class="cm" href="{{ $c['href'] }}" style="--g:{{ $c['tone'] }}">
              <span class="cm-medal">
                <span class="cdisc">@include('front.partials.cat-disc', ['c' => $c])</span>
                @if($c['icon'] && $c['image'])<span class="cbadge cm-ic" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>@endif
              </span>
              <span class="cm-n">{{ $c['name'] }}</span>
              <span class="cm-c bkf-tnum">{{ $c['count'] }} {{ $t('مكان', 'places') }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>

    {{-- c · bento mosaic --}}
    <div data-ct-pane="c" hidden>
      <div class="cb-grid">
        @foreach($ctBusy as $i => $c)
          <a class="cb-tile {{ $i === 0 ? 'is-lead' : '' }}" href="{{ $c['href'] }}" style="--g:{{ $c['tone'] }}">
            <span class="cb-img">
              @include('front.partials.cat-disc', ['c' => $c])
              @if($c['icon'] && $c['image'])<span class="cbadge cb-ic" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>@endif
            </span>
            <span class="cb-foot">
              <span class="cb-txt">
                <span class="cb-n">{{ $c['name'] }}</span>
                <span class="cb-c bkf-tnum">{{ $c['count'] }} {{ $t('مكان', 'places') }}</span>
              </span>
              <span class="cb-go" aria-hidden="true"><x-icon name="arrow-right" :size="18"/></span>
            </span>
          </a>
        @endforeach
      </div>
    </div>

    {{-- d · ledger list --}}
    <div data-ct-pane="d" hidden>
      <ul class="cl-list" role="list">
        @foreach($ctCats as $c)
          <li>
            <a class="cl-row" href="{{ $c['href'] }}" style="--g:{{ $c['tone'] }}">
              <span class="cl-medal">
                <span class="cdisc">@include('front.partials.cat-disc', ['c' => $c])</span>
                @if($c['icon'] && $c['image'])<span class="cbadge cl-ic" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>@endif
              </span>
              <span class="cl-t">
                <span class="cl-n">{{ $c['name'] }}</span>
                <span class="cl-c bkf-tnum">{{ $c['count'] }} {{ $t('مكان', 'places') }}</span>
              </span>
              <span class="cl-go" aria-hidden="true"><x-icon name="arrow-right" :size="18"/></span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>
</section>

<style>
.ct-headrow{align-items:flex-end;flex-wrap:wrap;gap:16px}
[data-ct-pane][hidden]{display:none}

/* shared circle: image → icon (white, on the category tone) → initial */
.cdisc{position:absolute;inset:0;border-radius:50%;overflow:hidden;display:grid;place-items:center;border:4px solid #fff;box-shadow:0 0 0 2px var(--bk-gold),0 18px 28px -16px rgba(28,36,14,.55);
  background:radial-gradient(120% 90% at 20% 10%,color-mix(in srgb,var(--g,#4B5D34) 55%,#fff),transparent 60%),var(--g,#4B5D34)}
.cdisc > img{position:absolute;inset:0;width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;display:block;background:#fff}
.cdisc b,.cb-img > b{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:2rem;color:#fff}
.cbig{display:grid;place-items:center;width:52%;height:52%;color:#fff}

/* b · medallion rail */
.cm-rail{list-style:none;margin:clamp(22px,3vw,34px) 0 0;padding:10px var(--bk-gutter) 20px;margin-inline:calc(var(--bk-gutter) * -1);display:flex;gap:clamp(14px,2.4vw,30px);overflow-x:auto;scroll-snap-type:x proximity;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.cm-rail::-webkit-scrollbar{display:none}
.cm-rail > li{flex:0 0 auto;scroll-snap-align:start}
.cm{display:flex;flex-direction:column;align-items:center;gap:9px;width:116px;text-decoration:none;color:var(--bk-text);-webkit-tap-highlight-color:transparent}
.cm-medal{position:relative;width:100%;aspect-ratio:1;transition:transform .45s var(--bk-ease)}
.cm-medal::before{content:"";position:absolute;inset:-12%;border-radius:50%;background:radial-gradient(circle at 30% 22%,rgba(199,161,90,.36),rgba(75,93,52,.1) 58%,transparent 70%)}
.cm:hover .cm-medal{transform:translateY(-6px)}
.cm:active .cm-medal{transform:scale(.95)}
.cm-ic{position:absolute;z-index:2;inset-block-start:0;inset-inline-end:-2px;width:38%;aspect-ratio:1}
.cm-n{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.02rem;line-height:1.25;text-align:center}
.cm-c{font-family:var(--bk-font-ui);font-size:.78rem;font-weight:700;color:var(--bk-gold-strong)}
@media (min-width:900px){
  .cm-rail{flex-wrap:wrap;justify-content:center;overflow:visible;margin-inline:0;padding-inline:0;row-gap:34px}
  .cm{width:150px}
}

/* c · bento mosaic */
.cb-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:clamp(22px,3vw,34px)}
.cb-tile{display:flex;flex-direction:column;overflow:hidden;text-decoration:none;color:var(--bk-text);background:var(--bk-surface);border:1px solid var(--bk-border);border-radius:28px;box-shadow:var(--bk-shadow-sm);transition:transform .4s var(--bk-ease),box-shadow .4s,border-color .3s;-webkit-tap-highlight-color:transparent}
.cb-tile:hover{transform:translateY(-4px);box-shadow:var(--bk-shadow-lg);border-color:color-mix(in srgb,var(--bk-gold) 55%,var(--bk-border))}
.cb-tile.is-lead{grid-column:1/-1}
.cb-img{position:relative;flex:1;min-height:132px;aspect-ratio:1/.82;overflow:hidden;display:grid;place-items:center;background:radial-gradient(120% 90% at 20% 10%,color-mix(in srgb,var(--g) 55%,#fff),transparent 60%),var(--g)}
.cb-tile.is-lead .cb-img{aspect-ratio:16/8}
.cb-img > img{position:absolute;inset:0;width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;transition:transform .9s var(--bk-ease)}
.cb-tile:hover .cb-img > img{transform:scale(1.06)}
.cb-ic{position:absolute;z-index:2;inset-block-start:10px;inset-inline-start:10px;width:44px;aspect-ratio:1}
.cb-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px 14px;border-top:3px solid var(--bk-gold)}
.cb-txt{display:flex;flex-direction:column;gap:2px;min-width:0}
.cb-n{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.08rem;line-height:1.25}
.cb-tile.is-lead .cb-n{font-size:1.4rem}
.cb-c{font-family:var(--bk-font-ui);font-size:.8rem;font-weight:700;color:var(--bk-gold-strong)}
.cb-go{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:var(--bk-accent-fill);color:var(--bk-accent-ink);transition:transform .35s var(--bk-ease),background .3s}
[dir=rtl] .cb-go svg{transform:scaleX(-1)}
.cb-tile:hover .cb-go{background:var(--bk-grad-gold);color:var(--bk-gold-ink);transform:translateX(4px)}
[dir=rtl] .cb-tile:hover .cb-go{transform:translateX(-4px)}
@media (min-width:720px){
  .cb-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
  .cb-tile.is-lead{grid-column:span 2;grid-row:span 2}
  .cb-tile.is-lead .cb-img{aspect-ratio:auto;min-height:280px}
}

/* d · ledger list */
.cl-list{list-style:none;margin:clamp(20px,2.6vw,32px) 0 0;padding:0;display:grid;grid-template-columns:minmax(0,1fr);column-gap:clamp(28px,4vw,64px);border-top:1px solid var(--bk-border)}
@media (min-width:900px){ .cl-list{grid-template-columns:repeat(2,minmax(0,1fr))} }
.cl-row{display:flex;align-items:center;gap:16px;min-height:88px;padding:12px 10px;text-decoration:none;color:var(--bk-text);border-bottom:1px solid var(--bk-border);border-radius:0;transition:background .25s;-webkit-tap-highlight-color:transparent}
.cl-row:hover,.cl-row:focus-visible{background:var(--bk-accent-wash)}
.cl-row:active{background:color-mix(in srgb,var(--bk-accent-wash) 70%,var(--bk-gold) 12%)}
.cl-medal{position:relative;flex:0 0 66px;width:66px;height:66px}
.cl-ic{position:absolute;z-index:2;inset-block-end:-4px;inset-inline-end:-6px;width:28px;aspect-ratio:1}
.cl-t{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
.cl-n{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.3rem;line-height:1.25}
.cl-c{font-family:var(--bk-font-ui);font-size:.85rem;font-weight:700;color:var(--bk-gold-strong)}
.cl-go{flex:0 0 auto;color:var(--bk-text-muted);display:grid;transition:transform .3s var(--bk-ease),color .2s}
[dir=rtl] .cl-go svg{transform:scaleX(-1)}
.cl-row:hover .cl-go{color:var(--bk-accent);transform:translateX(4px)}
[dir=rtl] .cl-row:hover .cl-go{transform:translateX(-4px)}
@media (prefers-reduced-motion:reduce){ .cm-medal,.cb-tile,.cb-go,.cl-go{transition:none!important} }
</style>

<script>
(function () {
  'use strict';
  var root = document.querySelector('[data-ct]');
  if (!root) return;
  var panes = [].slice.call(root.querySelectorAll('[data-ct-pane]'));
  var btns  = [].slice.call(root.querySelectorAll('[data-ct-view]'));
  var KEY = 'bkf_cat_view', v = 'a';
  var m = /[?&]cats=([abcd])\b/.exec(location.search);
  if (m) v = m[1]; else { try { var s = localStorage.getItem(KEY); if (s && /^[abcd]$/.test(s)) v = s; } catch (e) {} }
  function apply(next, save) {
    v = next;
    panes.forEach(function (p) { p.hidden = p.getAttribute('data-ct-pane') !== v; });
    btns.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-ct-view') === v ? 'true' : 'false'); });
    // reveal-on-scroll items inside a pane that was hidden have not been observed yet
    [].slice.call(root.querySelectorAll('[data-ct-pane]:not([hidden]) .bkf-reveal')).forEach(function (el) { el.classList.add('is-in'); });
    if (save) { try { localStorage.setItem(KEY, v); } catch (e) {} }
  }
  btns.forEach(function (b) { b.addEventListener('click', function () { apply(b.getAttribute('data-ct-view'), true); }); });
  apply(v, false);
})();
</script>
