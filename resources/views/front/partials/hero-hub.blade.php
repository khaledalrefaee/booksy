{{-- ══════════════ 1 · HERO — three designs, one switcher (comparison tool) ══════════════
     A = immersive photo · B = light editorial + photo tiles · C = olive poster + collage.
     All three render; the pill at the bottom flips between them (choice is remembered,
     ?hero=a|b|c also works). Delete the panes you don't pick, and this wrapper, to finish. --}}
@php
  $hvNames = ['a' => $t('داكن', 'Dark'), 'b' => $t('فاتح', 'Light'), 'c' => $t('زيتوني', 'Olive')];
@endphp
<div data-hero-hub data-initial="{{ $heroVariant }}">
  <div data-hero-pane="a" @if($heroVariant !== 'a') hidden @endif>@include('front.partials.hero-a')</div>
  <div data-hero-pane="b" @if($heroVariant !== 'b') hidden @endif>@include('front.partials.hero-b')</div>
  <div data-hero-pane="c" @if($heroVariant !== 'c') hidden @endif>@include('front.partials.hero-c')</div>
</div>

<div class="hv" role="group" aria-label="{{ $t('قارن تصاميم الواجهة', 'Compare hero designs') }}">
  <span class="hv-l">{{ $t('قارن:', 'Compare:') }}</span>
  @foreach($hvNames as $k => $nm)
    <button type="button" class="hv-b" data-hv="{{ $k }}" aria-pressed="{{ $k === $heroVariant ? 'true' : 'false' }}"><b>{{ ['a' => '١', 'b' => '٢', 'c' => '٣'][$k] }}</b> {{ $nm }}</button>
  @endforeach
</div>

<style>
/* category icon: one transparent file, tinted by context via CSS mask. olive on cream badges (best contrast),
   gold on olive surfaces, white on coloured fills — so the uploaded icon's own colour never matters */
.ci{display:block;width:100%;height:100%;background:currentColor;-webkit-mask:var(--ci) center/contain no-repeat;mask:var(--ci) center/contain no-repeat}
.cbadge{display:grid;place-items:center;border-radius:50%;background:#FBF8F0;color:#4B5D34;box-shadow:0 0 0 2px #C7A15A,0 8px 16px -6px rgba(0,0,0,.5)}
.cbadge .ci{width:62%;height:62%}
.hv{position:fixed;z-index:1150;inset-inline:0;margin-inline:auto;width:max-content;max-width:calc(100% - 24px);bottom:calc(14px + env(safe-area-inset-bottom));display:flex;align-items:center;gap:4px;padding:5px;
  background:#1B2013;border:1px solid rgba(244,238,220,.22);border-radius:999px;box-shadow:0 18px 34px -10px rgba(0,0,0,.6)}
.hv-l{padding-inline:12px 6px;font-family:var(--bk-font-ui);font-size:.8rem;font-weight:700;color:rgba(244,238,220,.7)}
.hv-b{min-height:44px;padding:0 16px;border:0;border-radius:999px;cursor:pointer;background:transparent;color:#F4EEDC;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.95rem;display:inline-flex;align-items:center;gap:7px;transition:background .25s,color .25s}
.hv-b b{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:.8rem;background:rgba(244,238,220,.16)}
.hv-b:hover{background:rgba(244,238,220,.12)}
.hv-b[aria-pressed="true"]{background:var(--bk-grad-gold);color:#2A2310}
.hv-b[aria-pressed="true"] b{background:rgba(42,35,16,.16)}
@media (max-width:420px){ .hv-l{display:none} .hv-b{padding:0 13px} }
</style>

<script>
(function () {
  'use strict';
  var hub = document.querySelector('[data-hero-hub]');
  if (!hub) return;
  var panes = [].slice.call(hub.querySelectorAll('[data-hero-pane]'));
  var btns  = [].slice.call(document.querySelectorAll('[data-hv]'));
  var KEY = 'bkf_hero_variant';
  var v = hub.getAttribute('data-initial') || 'a';
  var fromUrl = /[?&]hero=([abc])\b/.exec(location.search);
  if (fromUrl) v = fromUrl[1];
  else { try { var s = localStorage.getItem(KEY); if (s && /^[abc]$/.test(s)) v = s; } catch (e) {} }

  function apply(next, save) {
    v = next;
    panes.forEach(function (p) { p.hidden = p.getAttribute('data-hero-pane') !== v; });
    btns.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-hv') === v ? 'true' : 'false'); });
    // dark heroes (a, c) want the light-on-dark nav; the light hero (b) wants the normal one
    document.body.classList.toggle('bkf-has-hero', v !== 'b');
    // B's tiles already are the category picker, so the directory below is hidden for B
    [].slice.call(document.querySelectorAll('[data-hero-hide]')).forEach(function (el) {
      el.hidden = el.getAttribute('data-hero-hide').split(' ').indexOf(v) > -1;
    });
    var dir = document.querySelector('[data-cat-dir]'), tiles = document.querySelector('[data-cat-tiles]');
    if (dir) dir.id = v === 'b' ? 'categories-dir' : 'categories';
    if (tiles) tiles.id = v === 'b' ? 'categories' : 'categories-tiles';
    if (save) { try { localStorage.setItem(KEY, v); } catch (e) {} window.scrollTo(0, 0); }
  }
  btns.forEach(function (b) { b.addEventListener('click', function () { apply(b.getAttribute('data-hv'), true); }); });
  apply(v, false);
  // the sections below the hero are not parsed yet when this runs, so apply once more when the DOM is complete
  document.addEventListener('DOMContentLoaded', function () { apply(v, false); });
})();
</script>
