@props([
    'title',                 // full <title> (incl. brand)
    'description' => null,    // meta description
    'eyebrow',               // small label above H1
    'heading',               // H1
    'subtitle' => null,      // editorial one-liner under H1
    'updatedLabel',          // "Last updated:" / "آخر تحديث:"
    'updated',               // pre-formatted date string
    'tocLabel',              // "On this page" / "في هذه الصفحة"
    'toc' => [],             // [ ['id'=>.., 'label'=>..], .. ]
])
<x-front.layout :title="$title" :description="$description" :mapFab="false">
  <x-slot:styles>@include('front.legal._css')</x-slot:styles>

  <article class="bkf-legaldoc" data-legaldoc>
    <header class="bkf-legaldoc-head">
      <div class="bkf-legaldoc-eyebrow">{{ $eyebrow }}</div>
      <h1>{{ $heading }}</h1>
      @if($subtitle)<p class="bkf-legaldoc-sub">{{ $subtitle }}</p>@endif
      <p class="bkf-legaldoc-updated">{{ $updatedLabel }} <b>{{ $updated }}</b></p>
    </header>

    <div class="bkf-legaldoc-grid">
      {{-- Desktop sidebar --}}
      <aside class="bkf-legaldoc-aside">
        <nav aria-label="{{ $tocLabel }}">
          <p class="bkf-legaldoc-asidettl">{{ $tocLabel }}</p>
          <ul>
            @foreach($toc as $item)
              <li><a href="#{{ $item['id'] }}">{{ $item['label'] }}</a></li>
            @endforeach
          </ul>
        </nav>
      </aside>

      <div class="bkf-legaldoc-main">
        {{-- Mobile "On this page" disclosure --}}
        <details class="bkf-legaldoc-jump">
          <summary>
            {{ $tocLabel }}
            <svg class="bkf-legaldoc-chev" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </summary>
          <ul>
            @foreach($toc as $item)
              <li><a href="#{{ $item['id'] }}">{{ $item['label'] }}</a></li>
            @endforeach
          </ul>
        </details>

        <div class="bkf-legaldoc-body">
          {{ $slot }}
        </div>
      </div>
    </div>

    <button type="button" class="bkf-legaldoc-top" aria-label="{{ $tocLabel }}" data-legaldoc-top hidden>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
      </svg>
    </button>
  </article>

  <x-slot:scripts>
    <script>
    (function(){
      var doc = document.querySelector('[data-legaldoc]');
      if(!doc) return;
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var navH = parseInt(getComputedStyle(document.documentElement)
                 .getPropertyValue('--bk-nav-h')) || 72;
      var offset = navH + 72;

      /* Scrollspy: highlight the sidebar link for the section nearest the top. */
      var links = Array.prototype.slice.call(doc.querySelectorAll('.bkf-legaldoc-aside a'));
      var map = {}, heads = [];
      links.forEach(function(a){
        var id = a.getAttribute('href').slice(1);
        var h = document.getElementById(id);
        if(h){ map[id] = a; heads.push(h); }
      });
      var current = null;
      function setActive(id){
        if(current === id) return; current = id;
        links.forEach(function(a){ a.classList.remove('is-active'); a.removeAttribute('aria-current'); });
        if(map[id]){ map[id].classList.add('is-active'); map[id].setAttribute('aria-current','true'); }
      }

      /* Back-to-top */
      var top = doc.querySelector('[data-legaldoc-top]');
      if(top){
        top.removeAttribute('hidden');
        top.addEventListener('click', function(){
          window.scrollTo({ top:0, behavior: reduce ? 'auto' : 'smooth' });
        });
      }

      var ticking = false;
      function update(){
        ticking = false;
        if(heads.length){
          var mark = window.scrollY + offset, active = heads[0].id;
          for(var i=0;i<heads.length;i++){
            if(heads[i].getBoundingClientRect().top + window.scrollY - mark <= 0) active = heads[i].id;
          }
          setActive(active);
        }
        if(top){ top.classList.toggle('is-visible', window.scrollY > 600); }
      }
      window.addEventListener('scroll', function(){
        if(!ticking){ ticking = true; window.requestAnimationFrame(update); }
      }, { passive:true });
      update();

      /* Collapse the mobile disclosure after a jump. */
      var jump = doc.querySelector('.bkf-legaldoc-jump');
      if(jump){
        jump.querySelectorAll('a').forEach(function(a){
          a.addEventListener('click', function(){ jump.removeAttribute('open'); });
        });
      }
    })();
    </script>
  </x-slot:scripts>
</x-front.layout>
