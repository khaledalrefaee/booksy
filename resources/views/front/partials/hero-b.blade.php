{{-- ══════════════ 1 · HERO — variant B: "the doorway" ══════════════
     A light, editorial opening: big headline, one search pill, and the real categories as
     paper cards, each with a gold-ringed medallion of its uploaded image and an icon badge — name,
     image and icon all come from the categories table, so what the owner uploads is what visitors see.
     Phones: cards swipe sideways; desktop: a staggered 2×2.
     Expects: $categories, $venuesUrl, $t, $isAr. --}}
@php
  $hbCats  = \App\Support\VenueTypes::categories($categories, $isAr);
  $hbTotal = collect($hbCats)->sum('count');
@endphp
<section class="hb" id="top-b">
  <div class="hb-bg" aria-hidden="true"><i class="a"></i><i class="b"></i></div>
  <div class="bkf-container-wide hb-grid">

    <div class="hb-copy">
      <h1 class="hb-title"><span>{{ $t('اكتشف مكانك المثالي', 'Discover your place,') }}</span> <em>{{ $t('واحجز موعدك بسهولة', 'book it with ease') }}</em></h1>
      <p class="hb-lead">{{ $t('صالونات وسبا وتجميل وعيادات قريبة منك، بأسعار واضحة وتقييمات حقيقية، واحجز مباشرةً بلا مكالمات.', 'Salons, spas, beauty and clinics near you, with clear prices and real reviews. Book directly, no calls.') }}</p>

      <form class="hb-search" action="{{ $venuesUrl }}" method="GET" role="search">
        <label class="hb-field">
          <x-icon name="search" :size="20"/>
          <input type="search" name="search" placeholder="{{ $t('صالون، سبا، خدمة…', 'Salon, spa, service…') }}" autocomplete="off" aria-label="{{ $t('ابحث عن خدمة أو مكان', 'Search service or venue') }}">
          <button type="submit" class="hb-go" aria-label="{{ $t('ابحث', 'Search') }}"><x-icon name="arrow-right" :size="22"/></button>
        </label>
        <a href="#nearby" class="hb-near" data-geo-trigger aria-label="{{ $t('الأقرب إليّ', 'Near me') }}">
          <x-icon name="navigation" :size="22"/><span>{{ $t('قربي', 'Near me') }}</span>
        </a>
      </form>

      <p class="hb-early"><span class="dot"></span>{{ $t('التسجيل المبكر مفتوح، الإطلاق الرسمي قريبًا', 'Early access is open, official launch soon') }}</p>
    </div>

    @if(count($hbCats))
    <div class="hb-tiles" id="categories-tiles" data-cat-tiles role="list" aria-label="{{ $t('ماذا تريد اليوم؟', 'What are you looking for?') }}">
      @foreach($hbCats as $i => $c)
        <a class="hb-tile {{ $i > 2 ? 'is-extra' : '' }}" role="listitem" style="--i:{{ $i }};--g:{{ $c['tone'] }}" href="{{ $c['href'] }}">
          <span class="hb-medal">
            <span class="hb-disc">
              @if($c['image'])<img src="{{ $c['image'] }}" alt="" @if($i > 1) loading="lazy" @endif decoding="async">
              @elseif($c['icon'])<span class="hb-big" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>
              @else<b>{{ mb_substr($c['name'], 0, 1) }}</b>@endif
            </span>
            @if($c['icon'] && $c['image'])<span class="cbadge hb-ic" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>@endif
          </span>
          <span class="hb-foot">
            <span class="hb-txt">
              <span class="hb-name">{{ $c['name'] }}</span>
              <span class="hb-sub">{{ $c['count'] }} {{ $t('مكان', 'places') }}</span>
            </span>
            <span class="hb-arrow" aria-hidden="true"><x-icon name="arrow-right" :size="18"/></span>
          </span>
        </a>
      @endforeach
      <a class="hb-tile is-all" role="listitem" style="--i:{{ count($hbCats) }}" href="{{ $venuesUrl }}">
        <span class="hb-medal">
          <span class="hb-disc hb-disc-all"><x-icon name="grid" :size="44"/></span>
        </span>
        <span class="hb-foot">
          <span class="hb-txt">
            <span class="hb-name">{{ $t('كل الأنواع', 'All types') }}</span>
            <span class="hb-sub">{{ $hbTotal }} {{ $t('مكان', 'places') }}</span>
          </span>
          <span class="hb-arrow" aria-hidden="true"><x-icon name="arrow-right" :size="18"/></span>
        </span>
      </a>
    </div>
    @endif
  </div>
</section>

<style>
.hb{position:relative;isolation:isolate;overflow:hidden;background:var(--bk-bg);padding-block:calc(var(--bk-nav-h) + 20px) clamp(28px,5vw,64px)}
.hb::before{content:"";position:absolute;inset:0;z-index:-2;background:var(--bk-accent);opacity:.05;
  -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/72px 72px;
  mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/72px 72px}
.hb-bg i{position:absolute;z-index:-1;border-radius:50%;filter:blur(70px);opacity:.55;pointer-events:none}
.hb-bg .a{width:clamp(260px,40vw,560px);aspect-ratio:1;inset-block-start:-12%;inset-inline-end:-6%;background:radial-gradient(circle,rgba(199,161,90,.42),transparent 68%)}
.hb-bg .b{width:clamp(240px,34vw,480px);aspect-ratio:1;inset-block-end:-16%;inset-inline-start:-8%;background:radial-gradient(circle,rgba(75,93,52,.34),transparent 68%)}
[data-bk-theme="dark"] .hb-bg i{opacity:.4}
.hb-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:clamp(22px,4vw,56px);align-items:center}
.hb-copy,.hb-tiles{min-width:0}
.hb .hb-title{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:clamp(2.1rem,9.2vw,2.8rem);line-height:1.22;letter-spacing:0;color:var(--bk-text);text-wrap:balance}
.hb-title span{display:block}
.hb-title em{display:inline;font-style:normal;color:var(--bk-accent);padding:0 2px .22em;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 14' preserveAspectRatio='none'%3E%3Cpath d='M2 9C34 3 62 13 100 7S168 4 198 9' fill='none' stroke='%23C7A15A' stroke-width='4' stroke-linecap='round'/%3E%3C/svg%3E") 50% 100%/100% .3em no-repeat}
.hb-lead{display:none;margin:18px 0 0;max-width:44ch;font-family:var(--bk-font-ui);font-size:var(--bk-fs-lead);line-height:1.8;color:var(--bk-text-soft)}
.hb-search{display:flex;align-items:center;gap:10px;margin-top:20px;max-width:620px}
.hb-field{flex:1;min-width:0;display:flex;align-items:center;gap:10px;padding:6px 18px 6px 6px;padding-inline:18px 6px;background:var(--bk-surface);border:1.5px solid var(--bk-border);border-radius:var(--bk-r-pill);box-shadow:var(--bk-shadow);transition:border-color .25s,box-shadow .25s}
.hb-field:focus-within{border-color:var(--bk-accent);box-shadow:var(--bk-shadow-lg)}
.hb-field > svg{flex:0 0 auto;color:var(--bk-accent)}
.hb-field input{flex:1;min-width:0;border:0;outline:0;background:transparent;font-family:var(--bk-font-ui);font-size:1rem;padding:15px 0;color:var(--bk-text)}
.hb-field input::placeholder{color:var(--bk-text-muted)}
.hb-go{flex:0 0 52px;width:52px;height:52px;border:0;border-radius:50%;display:grid;place-items:center;cursor:pointer;background:var(--bk-grad-gold);color:var(--bk-gold-ink);box-shadow:var(--bk-shadow-accent);transition:transform .3s var(--bk-spring)}
.hb-go:hover{transform:scale(1.06)}
[dir=rtl] .hb-go svg{transform:scaleX(-1)}
.hb-near{flex:0 0 auto;min-width:56px;height:56px;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:var(--bk-r-pill);text-decoration:none;
  background:var(--bk-surface);color:var(--bk-accent-strong);border:1.5px solid var(--bk-border);font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.95rem;box-shadow:var(--bk-shadow-sm);transition:background .25s,border-color .25s}
.hb-near:hover{background:var(--bk-accent-wash);border-color:var(--bk-accent)}
.hb-near span{display:none}
.hb-early{display:none;align-items:center;gap:9px;margin:20px 0 0;font-family:var(--bk-font-ui);font-size:.9rem;font-weight:600;color:var(--bk-text-soft)}
.hb-early .dot{width:8px;height:8px;border-radius:50%;background:var(--bk-gold-strong);box-shadow:0 0 0 4px color-mix(in srgb,var(--bk-gold) 28%,transparent)}

/* paper cards with a medallion — phones: a sideways rail of every category */
.hb-tiles{display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;margin-inline:calc(var(--bk-gutter) * -1);padding:6px var(--bk-gutter) 24px;scroll-padding-inline:var(--bk-gutter);scrollbar-width:none;-webkit-overflow-scrolling:touch}
.hb-tiles::-webkit-scrollbar{display:none}
.hb-tile{position:relative;flex:0 0 min(46vw,176px);scroll-snap-align:start;display:flex;flex-direction:column;gap:12px;padding:16px 14px 14px;text-decoration:none;color:var(--bk-text);background:var(--bk-surface);border:1px solid var(--bk-border);border-radius:30px;
  box-shadow:0 22px 34px -24px rgba(28,36,14,.45);transition:transform .4s var(--bk-ease),box-shadow .4s,border-color .3s;animation:hb-in .7s var(--bk-ease) both;animation-delay:calc(var(--i,0)*80ms + 100ms);-webkit-tap-highlight-color:transparent}
.hb-tile:hover{box-shadow:0 30px 44px -24px rgba(28,36,14,.55);border-color:color-mix(in srgb,var(--bk-gold) 55%,var(--bk-border))}
.hb-tile:active{filter:brightness(.97)}
@keyframes hb-in{from{opacity:0;translate:0 26px}}
.hb-medal{position:relative;display:block;width:86%;aspect-ratio:1;margin-inline:auto}
.hb-medal::before{content:"";position:absolute;inset:-9%;border-radius:50%;z-index:0;background:radial-gradient(circle at 30% 22%,rgba(199,161,90,.38),rgba(75,93,52,.12) 58%,transparent 70%);transition:transform .6s var(--bk-ease)}
.hb-tile:hover .hb-medal::before{transform:scale(1.08)}
.hb-disc{position:absolute;inset:0;z-index:1;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:var(--bk-grad-accent);border:4px solid #fff;box-shadow:0 0 0 2px var(--bk-gold),0 20px 30px -16px rgba(28,36,14,.6)}
.hb-disc > img{position:absolute;inset:0;width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;transition:transform .8s var(--bk-ease)}
.hb-tile:hover .hb-disc > img{transform:scale(1.06)}
.hb-disc b{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:2.4rem;color:#fff}
.hb-disc-all{color:#fff}
.hb-big{display:grid;place-items:center;width:52%;height:52%;color:#fff}
.hb-ic{position:absolute;z-index:2;inset-block-start:2%;inset-inline-end:0;width:clamp(34px,28%,46px);aspect-ratio:1}
.hb-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px dashed color-mix(in srgb,var(--bk-gold) 55%,var(--bk-border))}
.hb-txt{display:flex;flex-direction:column;gap:2px;min-width:0}
.hb-name{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:1.08rem;line-height:1.25;color:var(--bk-text)}
.hb-sub{font-family:var(--bk-font-ui);font-size:.8rem;font-weight:700;color:var(--bk-gold-strong)}
.hb-arrow{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:var(--bk-accent-fill);color:var(--bk-accent-ink);transition:transform .35s var(--bk-ease),background .3s}
[dir=rtl] .hb-arrow svg{transform:scaleX(-1)}
.hb-tile:hover .hb-arrow{transform:translateX(4px);background:var(--bk-grad-gold);color:var(--bk-gold-ink)}
[dir=rtl] .hb-tile:hover .hb-arrow{transform:translateX(-4px)}
.fh{scroll-margin-top:84px}

@media (min-width:720px){
  .hb .hb-title{font-size:clamp(2.6rem,4.4vw,3.6rem)}
  .hb-lead{display:block}
  .hb-near span{display:inline}
  .hb-near{padding:0 20px}
  .hb-early{display:inline-flex}
  .hb-search{margin-top:28px}
  /* a staggered grid of the first three categories + "all types" */
  .hb-tiles{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;overflow:visible;margin-inline:0;padding:6px 0 34px}
  .hb-tile{flex:none;padding:20px 18px 16px}
  .hb-tile.is-extra{display:none}
  .hb-tile:nth-child(even){transform:translateY(28px)}
  .hb-name{font-size:1.25rem}
}
@media (min-width:1024px){
  .hb{padding-block:calc(var(--bk-nav-h) + 40px) 72px}
  .hb-grid{grid-template-columns:minmax(0,1fr) minmax(0,1.02fr);min-height:560px}
  .hb-tiles{gap:22px}
  .hb-tile:nth-child(even){transform:translateY(38px)}
}
@media (prefers-reduced-motion:reduce){ .hb-tile{animation:none} .hb *{transition:none!important} }
</style>
