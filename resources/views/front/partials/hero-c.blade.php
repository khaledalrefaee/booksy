{{-- ══════════════ 1 · HERO — variant C: "the medallions" ══════════════
     Deep brand olive. The categories themselves are the artwork: each one's uploaded cover image
     becomes a gold-ringed medallion (name pill, and its icon when the file is a real icon), floating
     in a loose cluster beside a small "booking confirmed" ticket. Every category also sits in the
     chip rail under the search, so nothing is hidden behind the four that fit the cluster.
     Phones: headline → medallions → search → chips (controls in the thumb zone).
     Expects: $categories, $venuesUrl, $t, $isAr. --}}
@php
  $hcAll = \App\Support\VenueTypes::categories($categories, $isAr);
  // the cluster shows the four busiest categories (ties keep the owner's sort order)
  $hcTop = collect($hcAll)->sortByDesc('count')->take(4)->values();
@endphp
<section class="hc" id="top-c">
  <div class="hc-bg" aria-hidden="true"><i class="a"></i><i class="b"></i></div>
  <div class="bkf-container-wide hc-grid">

    <div class="hc-top">
      <h1 class="hc-title"><span>{{ $t('اكتشف مكانك المثالي', 'Discover your place,') }}</span> <em>{{ $t('واحجز موعدك بسهولة', 'book it with ease') }}</em></h1>
      <p class="hc-lead">{{ $t('صالونات وسبا وتجميل وعيادات قريبة منك، بأسعار واضحة وتقييمات حقيقية، واحجز مباشرةً بلا مكالمات.', 'Salons, spas, beauty and clinics near you, with clear prices and real reviews. Book directly, no calls.') }}</p>
    </div>

    <div class="hc-cluster n{{ count($hcTop) }}">
      <span class="hc-ring" aria-hidden="true"></span>
      @foreach($hcTop as $i => $c)
        <a class="hc-m m{{ $i + 1 }}" href="{{ $c['href'] }}" style="--g:{{ $c['tone'] }};--d:{{ $i * -1.3 }}s" aria-label="{{ $c['name'] }}">
          <span class="hc-m-img">
            @if($c['image'])<img src="{{ $c['image'] }}" alt="" decoding="async" @if($i > 1) loading="lazy" @endif>@elseif($c['icon'])<span class="hc-m-big"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>@else<b>{{ mb_substr($c['name'], 0, 1) }}</b>@endif
          </span>
          @if($c['icon'])<span class="cbadge hc-m-ic" aria-hidden="true"><i class="ci" style="--ci:url('{{ $c['icon'] }}')"></i></span>@endif
          <span class="hc-m-n">{{ $c['name'] }}</span>
        </a>
      @endforeach
      <div class="hc-ticket" aria-hidden="true">
        <span class="hc-tick"><x-icon name="check" :size="18"/></span>
        <span class="hc-tk-t">
          <b>{{ $t('تم تأكيد حجزك', 'Booking confirmed') }}</b>
          <small>{{ $t('ويصلك تذكير قبل موعدك', 'Reminder before your visit') }}</small>
        </span>
        <span class="hc-stub"><small>{{ $t('الخميس', 'Thu') }}</small><b class="bkf-tnum">{{ $t('٥:٣٠', '5:30') }}</b></span>
      </div>
    </div>

    <div class="hc-ctrl">
      <form class="hc-search" action="{{ $venuesUrl }}" method="GET" role="search">
        <label class="hc-field">
          <x-icon name="search" :size="20"/>
          <input type="search" name="search" placeholder="{{ $t('صالون، سبا، خدمة…', 'Salon, spa, service…') }}" autocomplete="off" aria-label="{{ $t('ابحث عن خدمة أو مكان', 'Search service or venue') }}">
          <button type="submit" class="hc-go" aria-label="{{ $t('ابحث', 'Search') }}"><x-icon name="arrow-right" :size="22"/></button>
        </label>
      </form>

      <nav class="hc-chips" aria-label="{{ $t('نوع المكان', 'Type of place') }}">
        <a href="#nearby" class="hc-chip is-near" data-geo-trigger><x-icon name="navigation" :size="18"/>{{ $t('قربي', 'Near me') }}</a>
        @foreach($hcAll as $c)
          <a href="{{ $c['href'] }}" class="hc-chip" style="--g:{{ $c['tone'] }}">
            <span class="th">@if($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy">@else<b>{{ mb_substr($c['name'], 0, 1) }}</b>@endif</span>
            {{ $c['name'] }}<span class="n bkf-tnum">{{ $c['count'] }}</span>
          </a>
        @endforeach
      </nav>
      <p class="hc-early"><span class="dot"></span>{{ $t('التسجيل المبكر مفتوح، الإطلاق الرسمي قريبًا', 'Early access is open, official launch soon') }}</p>
    </div>
  </div>
</section>

<style>
.hc{position:relative;isolation:isolate;overflow:hidden;color:#F4EEDC;background:#27301A;padding-block:calc(var(--bk-nav-h) + 8px) clamp(26px,5vw,60px)}
.hc::before{content:"";position:absolute;inset:0;z-index:-2;background:currentColor;opacity:.06;
  -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/72px 72px;
  mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.2'%3E%3Crect x='14' y='14' width='36' height='36'/%3E%3Crect x='14' y='14' width='36' height='36' transform='rotate(45 32 32)'/%3E%3Ccircle cx='32' cy='32' r='5'/%3E%3C/g%3E%3C/svg%3E") 0 0/72px 72px}
.hc-bg i{position:absolute;z-index:-1;border-radius:50%;filter:blur(80px);pointer-events:none}
.hc-bg .a{width:clamp(280px,40vw,560px);aspect-ratio:1;inset-block-start:-14%;inset-inline-end:-8%;background:radial-gradient(circle,rgba(216,184,115,.42),transparent 66%)}
.hc-bg .b{width:clamp(240px,34vw,480px);aspect-ratio:1;inset-block-end:-20%;inset-inline-start:-8%;background:radial-gradient(circle,rgba(134,164,71,.38),transparent 66%)}
.hc-grid{display:grid;gap:14px;grid-template-columns:minmax(0,1fr);grid-template-areas:"top" "cluster" "ctrl"}
.hc-top,.hc-cluster,.hc-ctrl{min-width:0}
.hc-top{grid-area:top}.hc-cluster{grid-area:cluster}.hc-ctrl{grid-area:ctrl}
.hc .hc-title{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:clamp(2rem,8.6vw,2.7rem);line-height:1.25;letter-spacing:0;color:#FBF5E3;text-wrap:balance}
.hc-title span{display:block}
.hc .hc-title em{font-style:normal;color:#E6C77F;padding-bottom:.22em;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 14' preserveAspectRatio='none'%3E%3Cpath d='M2 9C34 3 62 13 100 7S168 4 198 9' fill='none' stroke='%23E6C77F' stroke-opacity='.7' stroke-width='3.5' stroke-linecap='round'/%3E%3C/svg%3E") 50% 100%/100% .3em no-repeat}
.hc-lead{display:none;margin:18px 0 0;max-width:44ch;font-family:var(--bk-font-ui);font-size:var(--bk-fs-lead);line-height:1.8;color:rgba(244,238,220,.86)}

/* medallion cluster — the category images are the artwork */
.hc-cluster{position:relative;width:min(100%,460px);aspect-ratio:1/.8;margin-inline:auto}
.hc-cluster{--cx:50%;--cy:46%}
.hc-cluster.n1,.hc-cluster.n2{--cx:58%;--cy:56%}
/* a thin gold ring around the lead medallion with one bead that slowly orbits it */
.hc-ring{position:absolute;inset-inline-start:var(--cx);top:var(--cy);width:68%;aspect-ratio:1;translate:50% -50%;border-radius:50%;border:1.5px solid rgba(230,199,127,.5);animation:hc-spin 56s linear infinite}
[dir=ltr] .hc-ring{translate:-50% -50%}
.hc-ring::after{content:"";position:absolute;inset-block-start:-6px;inset-inline-start:50%;width:11px;height:11px;margin-inline-start:-5px;background:#E6C77F;transform:rotate(45deg);box-shadow:0 0 14px 3px rgba(230,199,127,.55)}
@keyframes hc-spin{to{rotate:360deg}}
.hc-m.m1::before{content:"";position:absolute;inset:-22%;z-index:-1;border-radius:50%;background:radial-gradient(circle,rgba(230,199,127,.34),transparent 64%)}
.hc-m{position:absolute;display:block;width:var(--s);aspect-ratio:1;translate:50% -50%;text-decoration:none;color:inherit;-webkit-tap-highlight-color:transparent;animation:hc-bob 7s ease-in-out infinite;animation-delay:var(--d,0s)}
[dir=rtl] .hc-m{translate:50% -50%}
[dir=ltr] .hc-m{translate:-50% -50%}
.hc-m-img{position:absolute;inset:0;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:radial-gradient(120% 90% at 20% 10%,color-mix(in srgb,var(--g) 55%,#fff),transparent 60%),var(--g);
  border:4px solid #F4EEDC;box-shadow:0 0 0 2px rgba(230,199,127,.7),0 24px 36px -18px rgba(0,0,0,.75);transition:transform .5s var(--bk-ease),box-shadow .5s}
.hc-m-img img{position:absolute;inset:0;width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;display:block}
.hc-m-img b{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:2.4rem;color:#fff}
.hc-m:hover .hc-m-img{transform:scale(1.05);box-shadow:0 0 0 3px #E6C77F,0 30px 40px -18px rgba(0,0,0,.8)}
.hc-m-ic{position:absolute;z-index:2;inset-block-start:4%;inset-inline-end:4%;width:clamp(30px,24%,44px);aspect-ratio:1}
.hc-m-big{width:52%;height:52%;color:#fff;display:block}
.hc-m-n{position:absolute;z-index:2;inset-inline-start:50%;bottom:-12px;translate:50% 0;white-space:nowrap;padding:6px 14px;border-radius:999px;background:#F4EEDC;color:#22251D;font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.86rem;box-shadow:0 10px 18px -8px rgba(0,0,0,.6)}
[dir=ltr] .hc-m-n{translate:-50% 0}
.hc-m.m1{--s:50%;inset-inline-start:50%;top:46%}
.hc-m.m2{--s:28%;inset-inline-start:86%;top:26%}
.hc-m.m3{--s:27%;inset-inline-start:13%;top:70%}
.hc-m.m4{--s:31%;inset-inline-start:80%;top:76%}
/* a young catalogue (≤2 categories): tighten the cluster so it never floats in empty space */
.hc-cluster.n1,.hc-cluster.n2{aspect-ratio:1/.62}
.hc-cluster.n1 .hc-m.m1,.hc-cluster.n2 .hc-m.m1{--s:52%;inset-inline-start:58%;top:56%}
.hc-cluster.n2 .hc-m.m2{--s:34%;inset-inline-start:17%;top:66%}
@keyframes hc-bob{0%,100%{margin-top:0}50%{margin-top:-7px}}
.hc-ticket{position:absolute;z-index:4;inset-block-start:0;inset-inline-start:0;max-width:62%;display:flex;align-items:center;gap:10px;padding:10px 14px 10px 12px;padding-inline:12px 14px;border-radius:20px;color:#22251D;background:#fff;box-shadow:0 20px 32px -14px rgba(0,0,0,.65);transform:rotate(-2deg)}
.hc-ticket{padding-inline:20px 18px;-webkit-mask:radial-gradient(circle 8px at 0 50%,#0000 97%,#000) left/51% 100% no-repeat,radial-gradient(circle 8px at 100% 50%,#0000 97%,#000) right/51% 100% no-repeat;mask:radial-gradient(circle 8px at 0 50%,#0000 97%,#000) left/51% 100% no-repeat,radial-gradient(circle 8px at 100% 50%,#0000 97%,#000) right/51% 100% no-repeat}
.hc-stub{display:flex;flex-direction:column;align-items:center;line-height:1.1;padding-inline-start:12px;margin-inline-start:2px;border-inline-start:2px dashed #D9D2BC}
.hc-stub small{font-family:var(--bk-font-ui);font-size:.66rem;font-weight:700;color:#8A6317}
.hc-stub b{font-family:var(--bk-font-display);font-weight:800;font-size:1.05rem;color:#22251D}
.hc-tick{flex:0 0 auto;width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#3F6B2E;color:#fff}
.hc-tk-t{display:flex;flex-direction:column;line-height:1.3}
.hc-tk-t b{font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.92rem}
.hc-tk-t small{font-family:var(--bk-font-ui);font-size:.72rem;color:#6C6D5E}

/* controls */
.hc-search{max-width:640px}
.hc-field{display:flex;align-items:center;gap:10px;padding:6px 18px 6px 6px;padding-inline:18px 6px;background:#FBF8F0;border-radius:var(--bk-r-pill);box-shadow:0 18px 32px -14px rgba(0,0,0,.6);border:2px solid transparent;transition:border-color .25s}
.hc-field:focus-within{border-color:#E6C77F}
.hc-field > svg{flex:0 0 auto;color:#4B5D34}
.hc-field input{flex:1;min-width:0;border:0;outline:0;background:transparent;font-family:var(--bk-font-ui);font-size:1rem;padding:15px 0;color:#22251D}
.hc-field input::placeholder{color:#6C6D5E}
.hc-go{flex:0 0 52px;width:52px;height:52px;border:0;border-radius:50%;display:grid;place-items:center;cursor:pointer;background:var(--bk-grad-gold);color:#2A2310;box-shadow:0 10px 20px -8px rgba(138,99,23,.6);transition:transform .3s var(--bk-spring)}
.hc-go:hover{transform:scale(1.06)}
[dir=rtl] .hc-go svg{transform:scaleX(-1)}
.hc-chips{display:flex;gap:10px;margin-top:14px;margin-inline:calc(var(--bk-gutter) * -1);padding:2px var(--bk-gutter) 4px;overflow-x:auto;scrollbar-width:none;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch}
.hc-chips::-webkit-scrollbar{display:none}
.hc-chip{flex:0 0 auto;scroll-snap-align:start;display:inline-flex;align-items:center;gap:9px;min-height:48px;padding:0 8px 0 16px;padding-inline:6px 8px;border-radius:var(--bk-r-pill);text-decoration:none;white-space:nowrap;
  font-family:'Tajawal',var(--bk-font-ui);font-weight:800;font-size:.98rem;color:#F4EEDC;background:rgba(255,255,255,.1);border:1.5px solid rgba(244,238,220,.34);transition:background .25s,border-color .25s,transform .25s;-webkit-tap-highlight-color:transparent}
.hc-chip:hover{background:rgba(255,255,255,.18);border-color:rgba(244,238,220,.6)}
.hc-chip:active{transform:scale(.97)}
.hc-chip .th{flex:0 0 auto;width:34px;height:34px;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:var(--g);border:2px solid rgba(244,238,220,.85)}
.hc-chip .th img{width:100%!important;height:100%!important;max-width:none!important;object-fit:cover;display:block}
.hc-chip .th b{font-size:.95rem;color:#fff}
.hc-chip .n{min-width:26px;height:26px;padding:0 8px;border-radius:13px;display:grid;place-items:center;font-family:var(--bk-font-ui);font-size:.78rem;font-weight:800;background:rgba(244,238,220,.16)}
.hc-chip.is-near{padding-inline:16px;background:var(--bk-grad-gold);color:#2A2310;border-color:transparent}
.hc-early{display:none;align-items:center;gap:9px;margin:18px 0 0;font-family:var(--bk-font-ui);font-size:.9rem;font-weight:600;color:rgba(244,238,220,.82)}
.hc-early .dot{width:8px;height:8px;border-radius:50%;background:#E6C77F;box-shadow:0 0 0 4px rgba(230,199,127,.28)}

@media (max-width:719px){ .hc-tk-t small{display:none} .hc-ticket{gap:8px;padding-block:8px} .hc-tick{width:30px;height:30px} }
@media (min-width:720px){
  .hc .hc-title{font-size:clamp(2.6rem,4.6vw,3.7rem)}
  .hc-lead{display:block}
  .hc-early{display:flex}
  .hc-chips{flex-wrap:wrap;overflow:visible;margin-inline:0;padding-inline:0}
}
@media (min-width:1024px){
  .hc{padding-block:calc(var(--bk-nav-h) + 36px) 72px}
  .hc-grid{grid-template-columns:minmax(0,1fr) minmax(0,.95fr);grid-template-areas:"top cluster" "ctrl cluster";grid-template-rows:auto 1fr;column-gap:clamp(40px,6vw,96px);row-gap:6px;align-items:start;min-height:560px}
  .hc-top{align-self:end}.hc-ctrl{align-self:start;padding-top:22px}
  .hc-cluster{width:100%;align-self:center;margin-inline:0}
}
@media (prefers-reduced-motion:reduce){ .hc *{transition:none!important} .hc-m,.hc-ring{animation:none} }
</style>
