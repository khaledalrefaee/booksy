{{-- ══════════════ 3 · "ماذا تريد اليوم؟" — grouped directory (salons / spa / beauty / clinics, each with its colour) ══════════════
     Expects: $categories, $t, $isAr. --}}
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
@endphp
<section class="bkf-section ct" id="categories">
  <div class="bkf-container-wide">
    <div class="bkf-railhead bkf-reveal ct-headrow">
      <div>
        <h2 class="bkf-title fp-title bkf-mt-0">{{ $t('ماذا تريد', 'What are you') }} <span class="em">{{ $t('اليوم؟', 'looking for?') }}</span></h2>
        <p class="fp-lead">{{ $t('اختر نوع المكان وانتقل مباشرةً إلى أفضل ما يناسبك.', 'Pick a type of place and jump straight to the best fit.') }}</p>
      </div>
    </div>

    @if(count($ctGroups))

    <div>
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
    @endif
  </div>
</section>

<style>
.ct-headrow{align-items:flex-end;flex-wrap:wrap;gap:16px}
</style>
