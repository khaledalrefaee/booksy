@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-front.layout :title="($isAr ? 'المفضلة' : 'Favourites') . ' | GlowRez'" :mapFab="false" :noindex="true">
<x-slot:styles>
<style>
.bkf-fav{ padding:calc(var(--bk-nav-h) + var(--bk-s8)) 0 var(--bk-s20); }
.bkf-fav-wrap{ max-width:var(--bk-container); margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-fav-head{ margin-bottom:var(--bk-s6); }
.bkf-fav-head h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h2); color:var(--bk-text); margin:0 0 4px; display:flex; align-items:center; gap:10px; }
.bkf-fav-head h1 svg{ color:var(--bk-danger); }
.bkf-fav-head p{ color:var(--bk-text-muted); font-size:var(--bk-fs-sm); margin:0; }
.bkf-fav-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:var(--bk-s5); }
.bkf-fav .bkf-vp{ animation:bkfFavIn .4s var(--bk-ease) both; }
.bkf-fav .bkf-vp.is-removing{ opacity:0; transform:scale(.94); transition:opacity var(--bk-t) ease,transform var(--bk-t) ease; }
@keyframes bkfFavIn{ from{ opacity:0; transform:translateY(10px); } to{ opacity:1; transform:none; } }
.bkf-empty{ text-align:center; padding:var(--bk-s16) var(--bk-s6); max-width:420px; margin-inline:auto; }
.bkf-empty-ic{ width:66px; height:66px; margin:0 auto var(--bk-s5); border-radius:var(--bk-r-lg); display:grid; place-items:center; background:var(--bk-danger-bg); color:var(--bk-danger); }
.bkf-empty h3{ font-size:1.15rem; color:var(--bk-text); margin:0 0 6px; }
.bkf-empty p{ color:var(--bk-text-muted); font-size:.92rem; margin:0 auto var(--bk-s6); line-height:1.7; }
@media (prefers-reduced-motion:reduce){ .bkf-fav .bkf-vp{ animation:none; } }
</style>
</x-slot:styles>

<section class="bkf-fav">
  <div class="bkf-fav-wrap">
    <div class="bkf-fav-head">
      <h1><x-icon name="heart-fill" :size="26"/>{{ $isAr ? 'المفضلة' : 'Favourites' }}</h1>
      <p>{{ $isAr ? 'الأماكن التي حفظتها — احجز منها في أي وقت.' : 'The venues you saved — book from them anytime.' }}</p>
    </div>

    <div class="bkf-fav-grid" data-fav-grid @if($cards->isEmpty()) hidden @endif>
      @foreach($cards as $c)
        @include('front.partials.venue-card', ['c' => $c, 'currency' => $currency, 'isAr' => $isAr])
      @endforeach
    </div>

    <div class="bkf-empty" data-fav-empty @if($cards->isNotEmpty()) hidden @endif>
      <div class="bkf-empty-ic"><x-icon name="heart" :size="30"/></div>
      <h3>{{ $isAr ? 'لا مفضّلات بعد' : 'No favourites yet' }}</h3>
      <p>{{ $isAr ? 'اضغط على القلب في أي مكان يعجبك ليظهر هنا.' : 'Tap the heart on any venue you like and it will show up here.' }}</p>
      <a href="{{ route('front.venues') }}" class="bkf-btn bkf-btn-primary">{{ $isAr ? 'استكشف أماكن الجمال والعناية' : 'Explore Beauty & Wellness Venues' }}<x-icon name="arrow-right" :size="18"/></a>
    </div>
  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var grid = document.querySelector('[data-fav-grid]');
  var empty = document.querySelector('[data-fav-empty]');
  if(!grid) return;
  // When a card is un-saved here, remove it and reveal the empty state if last.
  document.addEventListener('bk:fav', function(e){
    if(e.detail.on) return;
    var card = grid.querySelector('.bkf-vp[data-id="'+e.detail.id+'"]');
    if(!card) return;
    card.classList.add('is-removing');
    setTimeout(function(){
      card.remove();
      if(!grid.querySelector('.bkf-vp')){ grid.hidden = true; if(empty) empty.hidden = false; }
    }, 260);
  });
})();
</script>
</x-slot:scripts>
</x-front.layout>
