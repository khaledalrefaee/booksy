@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn($ar, $en) => $isAr ? $ar : $en;
    $currency = $isAr ? 'ل.س' : 'SYP';

    $brName  = $isAr ? ($branch->name_ar ?: $branch->name_en) : ($branch->name_en ?: $branch->name_ar);
    $coName  = $isAr ? ($company->name_ar ?: $company->name_en) : ($company->name_en ?: $company->name_ar);
    $catName = $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : null;
    $city    = $branch->governorate?->localizedName() ?? $branch->area?->localizedName();

    $totalRev  = $reviews->count();
    $avg       = $totalRev ? round($reviews->avg('rating'), 1) : null;
    $breakdown = [];
    for ($s = 5; $s >= 1; $s--) { $breakdown[$s] = $reviews->where('rating', $s)->count(); }

    $activeServices = $branch->services->where('is_active', true);
    $minPrice = $activeServices->pluck('price')->filter(fn($p) => $p > 0)->min();

    $empData = $branch->employees->map(fn($e) => [
        'id'    => $e->id,
        'name'  => $isAr ? ($e->name_ar ?: $e->name_en) : ($e->name_en ?: $e->name_ar),
        'image' => $e->image ? asset('storage/'.$e->image) : null,
        'cats'  => $e->serviceCategories->pluck('id')->toArray(),
    ])->values();

    // gallery
    $imgs = $allImages->map(fn($i) => asset('storage/'.$i->path))->values();
    if ($imgs->isEmpty() && $company->logo) { $imgs = collect([asset('storage/'.$company->logo)]); }

    // working hours
    $dayNames = $isAr ? ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']
                      : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $whByDay  = $branch->workingHours->groupBy('day_of_week');
    $todayDow = now()->dayOfWeek;
    $todayOpen = $whByDay->get($todayDow, collect())->where('is_open', true);
    $isOpenNow = false; $todayLabel = $t('مغلق اليوم', 'Closed today');
    if ($todayOpen->isNotEmpty()) {
        $wh = $todayOpen->first();
        $fmt = fn($v) => $v ? \Carbon\Carbon::createFromFormat('H:i:s', $v)->format('g:i A') : '';
        $todayLabel = $fmt($wh->open_time).' – '.$fmt($wh->close_time);
        $nowT = now()->format('H:i:s');
        $isOpenNow = $wh->open_time && $wh->close_time && $nowT >= $wh->open_time && $nowT <= $wh->close_time;
    }

    $catIcon = function ($slug) {
        $slug = strtolower($slug ?? '');
        $map = ['hair'=>'scissors','salon'=>'scissors','barber'=>'user','spa'=>'sparkles','massage'=>'sparkles','clinic'=>'shield','dental'=>'shield','skin'=>'sparkles','laser'=>'zap','beauty'=>'sparkles','makeup'=>'sparkles','nail'=>'heart','lash'=>'star','brow'=>'star','gym'=>'zap','tattoo'=>'award','wedding'=>'gift'];
        foreach ($map as $k => $v) { if (str_contains($slug, $k)) return $v; }
        return 'grid';
    };
@endphp

<x-front.layout
    variant="customer"
    :map-fab="false"
    ogType="business.business"
    :ogImage="$imgs->first()"
    :title="$brName.' — '.$coName.' | GlowRez'"
    :description="$city
        ? $t('احجز موعدك في '.$brName.' - '.$city.' عبر GlowRez — خدمات وأسعار وتقييمات وحجز فوري.', 'Book at '.$brName.' in '.$city.' on GlowRez — services, prices, reviews and instant booking.')
        : $t('احجز موعدك في '.$brName.' عبر GlowRez — خدمات وأسعار وتقييمات وحجز فوري.', 'Book at '.$brName.' on GlowRez — services, prices, reviews and instant booking.')">

<x-slot:head>
@php
    $schemaDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $openingSpec = [];
    foreach ($whByDay as $dow => $rows) {
        $open = $rows->where('is_open', true)->first();
        if ($open && $open->open_time && $open->close_time && isset($schemaDays[$dow])) {
            $openingSpec[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => 'https://schema.org/'.$schemaDays[$dow],
                'opens'     => substr($open->open_time, 0, 5),
                'closes'    => substr($open->close_time, 0, 5),
            ];
        }
    }
    $localBusiness = array_filter([
        '@type'       => 'HealthAndBeautyBusiness',
        '@id'         => url()->current().'#business',
        'name'        => $brName.($coName && $coName !== $brName ? ' — '.$coName : ''),
        'url'         => url()->current(),
        'image'       => $imgs->take(4)->all() ?: null,
        'description' => $isAr ? ('احجز موعدك في '.$brName.' عبر GlowRez.') : ('Book your appointment at '.$brName.' on GlowRez.'),
        'telephone'   => $branch->phone ?: null,
        'priceRange'  => $minPrice ? number_format((float)$minPrice).'+ SYP' : null,
        'currenciesAccepted' => 'SYP',
        'address'     => array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => $branch->address ?: null,
            'addressLocality' => $city ?: null,
            'addressCountry'  => 'SY',
        ]),
        'geo'         => ($branch->latitude && $branch->longitude) ? [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$branch->latitude,
            'longitude' => (float)$branch->longitude,
        ] : null,
        'openingHoursSpecification' => $openingSpec ?: null,
        'aggregateRating' => $totalRev > 0 ? [
            '@type'       => 'AggregateRating',
            'ratingValue' => (string)$avg,
            'reviewCount' => (string)$totalRev,
            'bestRating'  => '5', 'worstRating' => '1',
        ] : null,
    ], fn($v) => !is_null($v));

    $crumbs = [
        ['name' => $t('الرئيسية','Home'), 'url' => route('front.index')],
        ['name' => $t('الأماكن','Venues'), 'url' => route('front.venues')],
    ];
    if ($catName) { $crumbs[] = ['name' => $catName, 'url' => route('front.venues', ['category' => $company->category->slug ?? null])]; }
    $crumbs[] = ['name' => $brName, 'url' => url()->current()];
    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->values()->map(fn($c, $i) => [
            '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url'],
        ])->all(),
    ];
@endphp
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org'] + $localBusiness, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</x-slot:head>

<x-slot:styles>
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<style>
/* ── branch detail (page-scoped br-*) ── */
.br-wrap{ padding-top:calc(var(--bk-nav-h) + 20px); }
.br-crumb{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); margin-bottom:16px; }
.br-crumb a{ color:var(--bk-text-muted); } .br-crumb a:hover{ color:var(--bk-accent); }
.br-crumb svg{ width:14px; height:14px; opacity:.6; }

/* gallery */
.br-gallery{ display:grid; grid-template-columns:2fr 1fr 1fr; grid-template-rows:1fr 1fr; gap:10px; border-radius:var(--bk-r-lg); overflow:hidden; height:clamp(280px,42vw,460px); }
.br-gallery .g{ position:relative; overflow:hidden; background:var(--bk-surface-2); cursor:pointer; }
.br-gallery .g:first-child{ grid-row:1/3; }
.br-gallery .g img{ width:100%; height:100%; object-fit:cover; transition:transform var(--bk-t-slow) var(--bk-ease); }
.br-gallery .g:hover img{ transform:scale(1.05); }
.br-gallery .g-more{ position:absolute; inset:0; display:grid; place-items:center; background:color-mix(in srgb,#000 45%,transparent); color:#fff; font-family:var(--bk-font-ui); font-weight:700; font-size:1.1rem; }
.br-gallery-single{ grid-template-columns:1fr; grid-template-rows:1fr; }
.br-gallery-single .g:first-child{ grid-row:1; }
.br-gallery .g-ph{ width:100%; height:100%; display:grid; place-items:center; color:color-mix(in srgb,var(--bk-accent) 30%,transparent); }
@media (max-width:760px){ .br-gallery{ grid-template-columns:1fr 1fr; grid-template-rows:1fr; height:240px; } .br-gallery .g:first-child{ grid-row:1; grid-column:1/3; } .br-gallery .g:nth-child(n+3){ display:none; } }

/* head */
.br-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:20px; flex-wrap:wrap; margin:22px 0 4px; }
.br-title{ font-size:var(--bk-fs-h1); }
.br-head-meta{ display:flex; align-items:center; flex-wrap:wrap; gap:8px 16px; margin-top:12px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-soft); }
.br-head-meta .it{ display:inline-flex; align-items:center; gap:6px; }
.br-head-meta svg{ width:16px; height:16px; color:var(--bk-accent); }
.br-rate-pill{ display:inline-flex; align-items:center; gap:6px; font-weight:700; color:var(--bk-text); }
.br-rate-pill svg{ color:var(--bk-star); }
.br-open{ display:inline-flex; align-items:center; gap:6px; font-weight:600; }
.br-open.on{ color:var(--bk-success); } .br-open.off{ color:var(--bk-danger); }
.br-open .dot{ width:8px; height:8px; border-radius:50%; background:currentColor; }
.br-head-actions{ display:flex; gap:8px; }
.br-icon-btn{ width:44px; height:44px; border-radius:var(--bk-r-pill); border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); display:grid; place-items:center; cursor:pointer; transition:all var(--bk-t) ease; }
.br-icon-btn:hover{ color:var(--bk-accent); border-color:var(--bk-accent); }
.br-icon-btn.is-on{ color:var(--bk-danger); border-color:var(--bk-danger); }

/* layout */
.br-layout{ display:grid; grid-template-columns:1fr 380px; gap:var(--bk-s10); align-items:start; margin-top:var(--bk-s10); }
@media (max-width:980px){ .br-layout{ grid-template-columns:1fr; } .br-aside{ display:none; } }

/* sub-tabs */
.br-tabs{ position:sticky; top:calc(var(--bk-nav-h) - 2px); z-index:5; display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; padding:10px 0; margin-bottom:8px; background:color-mix(in srgb,var(--bk-bg) 90%,transparent); backdrop-filter:blur(10px); }
.br-tabs::-webkit-scrollbar{ display:none; }
.br-tab{ flex:0 0 auto; padding:9px 16px; border-radius:var(--bk-r-pill); border:1px solid transparent; background:transparent; color:var(--bk-text-soft); font-family:var(--bk-font-ui); font-weight:600; font-size:var(--bk-fs-sm); cursor:pointer; white-space:nowrap; transition:all var(--bk-t) ease; }
.br-tab:hover{ color:var(--bk-accent); }
.br-tab.is-active{ background:var(--bk-accent-wash); color:var(--bk-accent); }

.br-block{ scroll-margin-top:calc(var(--bk-nav-h) + 60px); margin-bottom:var(--bk-s12); }
.br-block-title{ font-family:var(--bk-font-display); font-weight:800; font-size:var(--bk-fs-h3); margin-bottom:var(--bk-s5); }
.br-desc{ font-family:var(--bk-font-ui); color:var(--bk-text-soft); line-height:1.8; }

/* services */
.br-svc-cat{ margin-bottom:var(--bk-s6); }
.br-svc-cat-h{ font-family:var(--bk-font-ui); font-weight:700; font-size:1rem; color:var(--bk-text); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.br-svc-cat-h svg{ width:18px; height:18px; color:var(--bk-accent); }
.br-svc{ display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px; border:1px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface); margin-bottom:10px; transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.br-svc:hover{ border-color:color-mix(in srgb,var(--bk-accent) 30%,var(--bk-border)); box-shadow:var(--bk-shadow-sm); }
.br-svc-info{ min-width:0; }
.br-svc-nm{ font-family:var(--bk-font-ui); font-weight:600; color:var(--bk-text); }
.br-svc-meta{ display:flex; align-items:center; gap:12px; margin-top:6px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); }
.br-svc-meta svg{ width:14px; height:14px; }
.br-svc-price{ color:var(--bk-gold-strong); font-weight:700; }
.br-svc-add{ flex-shrink:0; }
.br-svc-add.is-added{ background:var(--bk-accent-wash); color:var(--bk-accent); border-color:var(--bk-accent); }

/* team — horizontal rail of circular avatars (Fresha-style) */
.br-staff{ display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; padding-bottom:6px; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
.br-staff::-webkit-scrollbar{ display:none; }
.br-staff-card{ flex:0 0 auto; width:148px; scroll-snap-align:start; text-align:center; padding:22px 14px; border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); background:var(--bk-surface); transition:transform var(--bk-t) var(--bk-ease),box-shadow var(--bk-t) ease; }
.br-staff-card:hover{ transform:translateY(-4px); box-shadow:var(--bk-shadow); }
.br-staff-av{ width:76px; height:76px; border-radius:50%; margin:0 auto 12px; overflow:hidden; background:var(--bk-accent-wash); color:var(--bk-accent); display:grid; place-items:center; font-family:var(--bk-font-display); font-weight:800; font-size:1.5rem; }
.br-staff-av img{ width:100%; height:100%; object-fit:cover; }
.br-staff-nm{ font-family:var(--bk-font-ui); font-weight:700; color:var(--bk-text); }
.br-staff-rl{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); margin-top:2px; }

/* reviews */
.br-rev-summary{ display:flex; gap:32px; align-items:center; flex-wrap:wrap; padding:22px; border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); background:var(--bk-surface); margin-bottom:22px; }
.br-rev-big{ text-align:center; }
.br-rev-big .n{ font-family:var(--bk-font-display); font-weight:800; font-size:3rem; color:var(--bk-text); line-height:1; }
.br-rev-big .s{ color:var(--bk-star); display:flex; gap:2px; justify-content:center; margin:6px 0; }
.br-rev-big .s svg{ width:16px; height:16px; }
.br-rev-big .c{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); }
.br-rev-bars{ flex:1; min-width:200px; display:flex; flex-direction:column; gap:6px; }
.br-rev-bar{ display:flex; align-items:center; gap:10px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); }
.br-rev-bar .track{ flex:1; height:7px; border-radius:4px; background:var(--bk-surface-3); overflow:hidden; }
.br-rev-bar .fill{ height:100%; background:var(--bk-grad-gold); border-radius:4px; }
.br-rev{ padding:18px 0; border-bottom:1px solid var(--bk-border); }
.br-rev:last-child{ border-bottom:0; }
.br-rev-top{ display:flex; align-items:center; gap:12px; }
.br-rev-av{ width:42px; height:42px; border-radius:50%; background:var(--bk-accent-wash); color:var(--bk-accent); display:grid; place-items:center; font-weight:700; flex-shrink:0; }
.br-rev-nm{ font-family:var(--bk-font-ui); font-weight:600; color:var(--bk-text); }
.br-rev-dt{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); }
.br-rev-stars{ display:flex; gap:2px; color:var(--bk-star); margin-inline-start:auto; }
.br-rev-stars svg{ width:14px; height:14px; }
.br-rev p{ font-family:var(--bk-font-ui); color:var(--bk-text-soft); line-height:1.7; margin-top:10px; }

/* hours + map */
.br-hours{ display:flex; flex-direction:column; gap:2px; border:1px solid var(--bk-border); border-radius:var(--bk-r); overflow:hidden; }
.br-hours .row{ display:flex; align-items:center; justify-content:space-between; padding:11px 16px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); }
.br-hours .row:nth-child(odd){ background:var(--bk-surface); } .br-hours .row:nth-child(even){ background:var(--bk-surface-2); }
.br-hours .row.today{ background:var(--bk-accent-wash); color:var(--bk-accent); font-weight:700; }
.br-hours .closed{ color:var(--bk-danger); }
#br-map{ height:300px; width:100%; border-radius:var(--bk-r-lg); overflow:hidden; border:1px solid var(--bk-border); margin-top:16px; }

/* aside booking panel */
.br-aside-inner{ position:sticky; top:calc(var(--bk-nav-h) + 16px); }
.br-book{ border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); background:var(--bk-surface); box-shadow:var(--bk-shadow-sm); overflow:hidden; }
.br-book-head{ padding:18px 20px; border-bottom:1px solid var(--bk-border); display:flex; align-items:center; justify-content:space-between; }
.br-book-head h3{ font-family:var(--bk-font-ui); font-weight:700; font-size:1.02rem; }
.br-book-head .cnt{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); font-weight:700; color:var(--bk-accent-ink); background:var(--bk-accent-fill); border-radius:var(--bk-r-pill); padding:2px 9px; }
.br-book-body{ padding:16px 20px; max-height:46vh; overflow-y:auto; }
.br-book-empty{ text-align:center; color:var(--bk-text-muted); font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); padding:28px 8px; }
.br-book-empty svg{ width:34px; height:34px; color:color-mix(in srgb,var(--bk-accent) 40%,transparent); margin-bottom:10px; }
.br-bi{ padding:12px 0; border-bottom:1px solid var(--bk-border); }
.br-bi:last-child{ border-bottom:0; }
.br-bi-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.br-bi-nm{ font-family:var(--bk-font-ui); font-weight:600; font-size:.92rem; color:var(--bk-text); }
.br-bi-pr{ font-family:var(--bk-font-ui); font-weight:700; font-size:.9rem; color:var(--bk-gold-strong); white-space:nowrap; }
.br-bi-rm{ background:none; border:0; color:var(--bk-text-muted); cursor:pointer; padding:2px; display:grid; place-items:center; }
.br-bi-rm:hover{ color:var(--bk-danger); }
.br-bi-emp{ width:100%; margin-top:8px; padding:8px 12px; border:1px solid var(--bk-border); border-radius:var(--bk-r-sm); background:var(--bk-surface-2); color:var(--bk-text); font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); }
.br-book-foot{ padding:16px 20px; border-top:1px solid var(--bk-border); }
.br-book-tot{ display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; font-family:var(--bk-font-ui); }
.br-book-tot .l{ color:var(--bk-text-muted); font-size:var(--bk-fs-sm); }
.br-book-tot .p{ font-family:var(--bk-font-display); font-weight:800; font-size:1.3rem; color:var(--bk-text); }
.br-book-tot .d{ font-size:var(--bk-fs-xs); color:var(--bk-text-muted); }

/* mobile booking bar + sheet */
.br-bar{ position:fixed; inset-inline:0; inset-block-end:0; z-index:var(--bk-z-nav); display:none; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px calc(12px + env(safe-area-inset-bottom)); background:var(--bk-surface); border-top:1px solid var(--bk-border); box-shadow:0 -8px 24px rgba(0,0,0,.1); }
.br-bar.show{ display:flex; }
.br-bar-info .p{ font-family:var(--bk-font-display); font-weight:800; font-size:1.1rem; color:var(--bk-text); }
.br-bar-info .l{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); }
@media (max-width:980px){ .br-bar.has{ display:flex; } }
.br-sheet-ov{ position:fixed; inset:0; z-index:var(--bk-z-modal); display:none; background:color-mix(in srgb,#000 50%,transparent); backdrop-filter:blur(4px); }
.br-sheet-ov.open{ display:block; }
.br-sheet{ position:fixed; inset-inline:0; inset-block-end:0; z-index:calc(var(--bk-z-modal) + 1); transform:translateY(100%); transition:transform var(--bk-t) var(--bk-ease); background:var(--bk-surface); border-radius:var(--bk-r-xl) var(--bk-r-xl) 0 0; max-height:82vh; display:flex; flex-direction:column; }
.br-sheet.open{ transform:none; }
.br-sheet-h{ display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--bk-border); }
.br-sheet-b{ padding:16px 20px; overflow-y:auto; }

/* lightbox */
.br-lb{ position:fixed; inset:0; z-index:var(--bk-z-toast); display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.9); }
.br-lb.open{ display:flex; }
.br-lb img{ max-width:92vw; max-height:86vh; border-radius:var(--bk-r); }
.br-lb-x,.br-lb-nav{ position:absolute; background:rgba(255,255,255,.12); border:0; color:#fff; width:48px; height:48px; border-radius:50%; display:grid; place-items:center; cursor:pointer; }
.br-lb-x{ top:20px; inset-inline-end:20px; } .br-lb-nav.prev{ inset-inline-start:16px; } .br-lb-nav.next{ inset-inline-end:16px; }
.br-lb-nav{ top:50%; transform:translateY(-50%); }

/* booking modal → olive identity + our dark theme (higher specificity beats the partial's own rule) */
html #bk-modal{ --bk-sel:var(--bk-accent); --bk-sel-text:#fff; --bk-gold:#8A6317; }
html[data-bk-theme="dark"] #bk-modal{ --bk-bg:#252C1B; --bk-card:#2E3623; --bk-border:rgba(255,255,255,.09); --bk-text:#F0EEE3; --bk-text2:#8B9078; --bk-sel:#A6BC7E; --bk-sel-text:#121509; --bk-slot-bg:#2E3623; --bk-slot-border:rgba(255,255,255,.1); --bk-gold:#D8B873; }
.leaflet-container{ background:var(--bk-surface-2); font-family:var(--bk-font-ui); }

/* ── Fresha-style refinements (shorter, smoother, mobile-first) ── */
.br-hidden{ display:none !important; }
.br-svc-showall,.br-hours-toggle{ margin-top:10px; }

/* service category chips */
.br-svc-tabs{ display:flex; gap:8px; margin-bottom:20px; overflow-x:auto; scrollbar-width:none; padding-bottom:4px; scroll-snap-type:x proximity; -webkit-overflow-scrolling:touch; }
.br-svc-tabs::-webkit-scrollbar{ display:none; }
.br-svc-tab{ flex:0 0 auto; scroll-snap-align:start; padding:9px 16px; border-radius:var(--bk-r-pill); border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); font-family:var(--bk-font-ui); font-weight:600; font-size:var(--bk-fs-sm); white-space:nowrap; cursor:pointer; transition:all var(--bk-t) ease; }
.br-svc-tab:hover{ border-color:color-mix(in srgb,var(--bk-accent) 45%,transparent); color:var(--bk-accent); }
.br-svc-tab.is-active{ background:var(--bk-accent); color:var(--bk-accent-ink); border-color:var(--bk-accent); }

.br-gallery{ position:relative; }
.br-photos-badge{ position:absolute; inset-block-end:12px; inset-inline-end:12px; display:none; align-items:center; gap:6px; padding:8px 14px; border:0; border-radius:var(--bk-r-pill); background:color-mix(in srgb,#000 55%,transparent); color:#fff; font-family:var(--bk-font-ui); font-weight:600; font-size:var(--bk-fs-sm); backdrop-filter:blur(4px); cursor:pointer; z-index:2; }
.br-photos-badge svg{ width:16px; height:16px; }
.br-bar-btn-ic{ display:inline-flex; }

@media (max-width:760px){
  /* Swipeable image carousel on mobile (Fresha-like) */
  .br-gallery{ display:flex !important; grid-template:none !important; height:clamp(220px,60vw,320px); gap:8px; overflow-x:auto; scroll-snap-type:x mandatory; scrollbar-width:none; -webkit-overflow-scrolling:touch; }
  .br-gallery::-webkit-scrollbar{ display:none; }
  .br-gallery .g{ display:block !important; flex:0 0 90%; grid-row:auto !important; grid-column:auto !important; scroll-snap-align:center; border-radius:var(--bk-r-lg); }
  .br-gallery .g:first-child{ grid-row:auto !important; }
  .br-photos-badge{ display:inline-flex; }
  #br-map{ height:220px; }
  .br-title{ font-size:1.55rem; }
}
@media (max-width:980px){
  .br-bar{ display:flex; }                 /* persistent CTA bar on mobile */
  .br-block{ margin-bottom:30px; }          /* tighter vertical rhythm */
  .br-block-title{ margin-bottom:16px; }
  .br-wrap{ padding-bottom:calc(88px + env(safe-area-inset-bottom)); } /* clear the fixed bar */
}
body:has(.br-bar) .bkf-footer{ padding-bottom:calc(80px + env(safe-area-inset-bottom)); }
@media (min-width:981px){ body:has(.br-bar) .bkf-footer{ padding-bottom:0; } }
</style>
</x-slot:styles>

<div class="bkf-container-wide br-wrap">

  {{-- breadcrumb --}}
  <nav class="br-crumb" aria-label="breadcrumb">
    <a href="{{ route('front.index') }}">{{ $t('الرئيسية','Home') }}</a>
    <x-icon name="chevron-right" :size="14"/>
    @if($catName && $company->category)
      <a href="{{ route('front.category', $company->category->slug) }}">{{ $catName }}</a>
      <x-icon name="chevron-right" :size="14"/>
    @endif
    <span>{{ $brName }}</span>
  </nav>

  {{-- gallery --}}
  <div class="br-gallery {{ $imgs->count() < 2 ? 'br-gallery-single' : '' }}" id="br-gallery">
    @if($imgs->isNotEmpty())
      @foreach($imgs->take(5) as $i => $src)
        <div class="g" data-lb="{{ $i }}">
          <img src="{{ $src }}" alt="{{ $brName }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
          @if($i === 4 && $imgs->count() > 5)<div class="g-more">+{{ $imgs->count() - 5 }}</div>@endif
        </div>
      @endforeach
    @else
      <div class="g"><div class="g-ph"><x-icon name="{{ $catIcon($company->category->slug ?? '') }}" :size="48"/></div></div>
    @endif
    @if($imgs->count() > 1)
      <button type="button" class="br-photos-badge" onclick="brLb.open(0)"><x-icon name="grid" :size="16"/>{{ $imgs->count() }} {{ $t('صورة','photos') }}</button>
    @endif
  </div>

  {{-- head --}}
  <div class="br-head">
    <div>
      <h1 class="br-title">{{ $brName }}</h1>
      <div class="br-head-meta">
        @if($catName)<span class="bkf-chip"><x-icon name="{{ $catIcon($company->category->slug ?? '') }}" :size="14"/>{{ $catName }}</span>@endif
        @if($avg)<span class="br-rate-pill bkf-tnum"><x-icon name="star-fill" :size="16"/>{{ number_format($avg,1) }} <span style="color:var(--bk-text-muted);font-weight:500">· {{ $totalRev }} {{ $t('تقييم','reviews') }}</span></span>@endif
        @if($city || $branch->address)<span class="it"><x-icon name="map-pin" :size="16"/>{{ $city ?: Str::limit($branch->address, 40) }}</span>@endif
        <span class="br-open {{ $isOpenNow ? 'on' : 'off' }}"><span class="dot"></span>{{ $isOpenNow ? $t('مفتوح الآن','Open now') : $t('مغلق الآن','Closed') }} · {{ $todayLabel }}</span>
      </div>
    </div>
    <div class="br-head-actions">
      <button type="button" class="br-icon-btn" data-fav="{{ $branch->id }}" aria-label="{{ $t('حفظ','Save') }}">
        <x-icon name="heart" :size="20" class="heart-off"/><x-icon name="heart-fill" :size="20" class="heart-on" style="display:none"/>
      </button>
      @if($branch->latitude && $branch->longitude)
      <a class="br-icon-btn" href="https://www.google.com/maps/dir/?api=1&destination={{ $branch->latitude }},{{ $branch->longitude }}" target="_blank" rel="noopener" aria-label="{{ $t('الاتجاهات','Directions') }}"><x-icon name="navigation" :size="20"/></a>
      @endif
      @if($branch->phone)
      <a class="br-icon-btn" href="tel:{{ $branch->phone }}" aria-label="{{ $t('اتصال','Call') }}"><x-icon name="phone" :size="20"/></a>
      @endif
    </div>
  </div>

  {{-- tabs --}}
  <div class="br-tabs" id="br-tabs">
    <button class="br-tab is-active" data-target="br-services">{{ $t('الخدمات','Services') }}</button>
    @if($branch->description_en || $branch->description_ar)<button class="br-tab" data-target="br-about">{{ $t('نبذة','About') }}</button>@endif
    @if($employees->isNotEmpty())<button class="br-tab" data-target="br-team">{{ $t('الفريق','Team') }}</button>@endif
    @if($totalRev)<button class="br-tab" data-target="br-reviews">{{ $t('التقييمات','Reviews') }}</button>@endif
    <button class="br-tab" data-target="br-location">{{ $t('الموقع وأوقات العمل','Location & hours') }}</button>
  </div>

  <div class="br-layout">
    {{-- MAIN --}}
    <div class="br-main">

      {{-- services --}}
      <section class="br-block" id="br-services">
        <h2 class="br-block-title">{{ $t('الخدمات والأسعار','Services & prices') }}</h2>
        @if($servicesByCategory->count() > 1)
        <div class="br-svc-tabs" id="br-svc-tabs" role="tablist" aria-label="{{ $t('تصنيفات الخدمات','Service categories') }}">
          <button type="button" class="br-svc-tab is-active" data-cat="all">{{ $t('الكل','All') }}</button>
          @foreach($servicesByCategory as $catId => $services)
            @php $scName = $services->first()->serviceCategory?->localizedName() ?? $t('خدمات','Services'); @endphp
            <button type="button" class="br-svc-tab" data-cat="{{ $catId }}">{{ $scName }}</button>
          @endforeach
        </div>
        @endif
        @forelse($servicesByCategory as $catId => $services)
          @php $scName = $services->first()->serviceCategory?->localizedName() ?? $t('خدمات','Services'); @endphp
          <div class="br-svc-cat" data-cat="{{ $catId }}">
            <div class="br-svc-cat-h"><x-icon name="tag" :size="18"/>{{ $scName }}</div>
            @foreach($services->where('is_active', true) as $svc)
              @php $sName = $isAr ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar); @endphp
              <div class="br-svc">
                <div class="br-svc-info">
                  <div class="br-svc-nm">{{ $sName }}</div>
                  <div class="br-svc-meta">
                    @if($svc->duration_minutes)<span><x-icon name="clock" :size="14"/> {{ $svc->duration_minutes }} {{ $t('دقيقة','min') }}</span>@endif
                    <span class="br-svc-price bkf-tnum">{{ $svc->price ? number_format($svc->price,0).' '.$currency : $t('حسب الطلب','On request') }}</span>
                  </div>
                </div>
                <button type="button" class="br-svc-add bkf-btn bkf-btn-ghost bkf-btn-sm"
                        data-svc="{{ $svc->id }}" data-name="{{ $sName }}" data-price="{{ $svc->price ?: 0 }}"
                        data-duration="{{ $svc->duration_minutes ?: 0 }}" data-cat="{{ $svc->service_category_id ?: '' }}"
                        onclick="brToggle(this)">
                  <x-icon name="check" :size="16" class="ic-on" style="display:none"/><span class="lbl">{{ $t('أضف','Add') }}</span>
                </button>
              </div>
            @endforeach
          </div>
        @empty
          <div class="br-book-empty"><x-icon name="scissors" :size="34"/><div>{{ $t('لا توجد خدمات منشورة بعد.','No services listed yet.') }}</div></div>
        @endforelse
      </section>

      {{-- about --}}
      @if($branch->description_en || $branch->description_ar)
      <section class="br-block" id="br-about">
        <h2 class="br-block-title">{{ $t('نبذة عن '.$brName, 'About '.$brName) }}</h2>
        <p class="br-desc">{{ $isAr ? ($branch->description_ar ?: $branch->description_en) : ($branch->description_en ?: $branch->description_ar) }}</p>
      </section>
      @endif

      {{-- team --}}
      @if($employees->isNotEmpty())
      <section class="br-block" id="br-team">
        <h2 class="br-block-title">{{ $t('تعرّف على الفريق','Meet the team') }}</h2>
        <div class="br-staff bkf-rail">
          @foreach($employees as $emp)
            @php $eName = $isAr ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar); @endphp
            <div class="br-staff-card">
              <div class="br-staff-av">
                @if($emp->image)<img src="{{ asset('storage/'.$emp->image) }}" alt="{{ $eName }}" loading="lazy" decoding="async">@else{{ mb_substr($eName ?: 'S', 0, 1) }}@endif
              </div>
              <div class="br-staff-nm">{{ $eName }}</div>
              @if($emp->role)<div class="br-staff-rl">{{ $emp->role->localizedName() ?? ($isAr ? $emp->role->name_ar ?? '' : $emp->role->name_en ?? '') }}</div>@endif
            </div>
          @endforeach
        </div>
      </section>
      @endif

      {{-- reviews --}}
      @if($totalRev)
      <section class="br-block" id="br-reviews">
        <h2 class="br-block-title">{{ $t('آراء العملاء','Client reviews') }}</h2>
        <div class="br-rev-summary">
          <div class="br-rev-big">
            <div class="n bkf-tnum">{{ number_format($avg,1) }}</div>
            <div class="s">@for($s=1;$s<=5;$s++)<x-icon name="{{ $s <= round($avg) ? 'star-fill' : 'star' }}" :size="16"/>@endfor</div>
            <div class="c">{{ $totalRev }} {{ $t('تقييم','reviews') }}</div>
          </div>
          <div class="br-rev-bars">
            @foreach($breakdown as $star => $count)
              <div class="br-rev-bar"><span class="bkf-tnum">{{ $star }}★</span><span class="track"><span class="fill" style="width:{{ $totalRev ? round($count/$totalRev*100) : 0 }}%"></span></span><span class="bkf-tnum">{{ $count }}</span></div>
            @endforeach
          </div>
        </div>
        @foreach($reviews->take(8) as $rev)
          @php $cn = $isAr ? ($rev->customer->name_ar ?? $rev->customer->name ?? 'عميل') : ($rev->customer->name ?? 'Customer'); @endphp
          <div class="br-rev">
            <div class="br-rev-top">
              <div class="br-rev-av">{{ mb_substr($cn, 0, 1) }}</div>
              <div><div class="br-rev-nm">{{ $cn }}</div><div class="br-rev-dt">{{ $rev->created_at->diffForHumans() }}</div></div>
              <div class="br-rev-stars">@for($s=1;$s<=5;$s++)<x-icon name="{{ $s <= $rev->rating ? 'star-fill' : 'star' }}" :size="14"/>@endfor</div>
            </div>
            @if($rev->comment)<p>{{ $rev->comment }}</p>@endif
          </div>
        @endforeach
      </section>
      @endif

      {{-- location + hours --}}
      <section class="br-block" id="br-location">
        <h2 class="br-block-title">{{ $t('الموقع وأوقات العمل','Location & hours') }}</h2>
        @if($branch->address)<p class="br-desc" style="margin-bottom:14px"><x-icon name="map-pin" :size="16" style="color:var(--bk-accent);vertical-align:-2px"/> {{ $branch->fullAddress() ?: $branch->address }}</p>@endif
        @if($branch->workingHours->isNotEmpty())
        <div class="br-hours">
          @php
            $fmt = function ($v) {
                if (!$v) return '';
                return $v instanceof \DateTimeInterface ? $v->format('g:i A') : \Carbon\Carbon::parse($v)->format('g:i A');
            };
          @endphp
          @for($d = 0; $d <= 6; $d++)
            @php
              $dh = $whByDay->get($d, collect())->where('is_open', true);
              $w  = $dh->first();
            @endphp
            <div class="row {{ $d === $todayDow ? 'today' : '' }}">
              <span>{{ $dayNames[$d] }}{{ $d === $todayDow ? ' · '.$t('اليوم','Today') : '' }}</span>
              <span class="bkf-tnum">
                @if($dh->isEmpty() || !$w)<span class="closed">{{ $t('مغلق','Closed') }}</span>
                @else{{ $fmt($w->open_time) }} – {{ $fmt($w->close_time) }}@endif
              </span>
            </div>
          @endfor
        </div>
        @endif
        @if($branch->latitude && $branch->longitude)
          <div id="br-map" data-lat="{{ $branch->latitude }}" data-lng="{{ $branch->longitude }}" data-name="{{ $brName }}"></div>
        @endif
      </section>
    </div>

    {{-- ASIDE: booking cart --}}
    <aside class="br-aside" id="book">
      <div class="br-aside-inner">
        <div class="br-book">
          <div class="br-book-head">
            <h3>{{ $t('حجزك','Your booking') }}</h3>
            <span class="cnt" id="br-cart-count" style="display:none">0</span>
          </div>
          <div class="br-book-body">
            <div class="br-book-empty" id="br-cart-empty">
              <x-icon name="calendar" :size="34"/>
              <div>{{ $t('اختر خدمة أو أكثر لبدء الحجز.','Add one or more services to start booking.') }}</div>
            </div>
            <div id="br-cart-items"></div>
          </div>
          <div class="br-book-foot" id="br-cart-foot" style="display:none">
            <div class="br-book-tot">
              <div><div class="l">{{ $t('الإجمالي','Total') }}</div><div class="d" id="br-cart-dur"></div></div>
              <div class="p bkf-tnum" id="br-cart-price"></div>
            </div>
            <button type="button" class="bkf-btn bkf-btn-primary bkf-btn-block" onclick="brBook()"><x-icon name="calendar" :size="18"/>{{ $t('متابعة الحجز','Continue to booking') }}</button>
          </div>
        </div>
        @if($branch->phone)
        <a href="tel:{{ $branch->phone }}" class="bkf-btn bkf-btn-soft bkf-btn-block" style="margin-top:12px"><x-icon name="phone" :size="18"/>{{ $t('اتصل بالمكان','Call the venue') }}</a>
        @endif
      </div>
    </aside>
  </div>
</div>

{{-- mobile sticky booking bar (persistent — default = "Book now") --}}
<div class="br-bar" id="br-bar">
  <div class="br-bar-info">
    <div class="p bkf-tnum" id="br-bar-price">{{ $minPrice ? ($isAr ? 'من ' : 'from ').number_format($minPrice,0).' '.$currency : $t('احجز موعدك','Book a visit') }}</div>
    <div class="l" id="br-bar-label">{{ $t('اختر خدماتك','Pick your services') }}</div>
  </div>
  <button type="button" class="bkf-btn bkf-btn-primary" onclick="brBarAction()">
    <x-icon name="calendar" :size="18"/><span id="br-bar-btn-lbl">{{ $t('احجز الآن','Book now') }}</span>
  </button>
</div>

{{-- mobile cart sheet --}}
<div class="br-sheet-ov" id="br-sheet-ov" onclick="brCloseSheet()"></div>
<div class="br-sheet" id="br-sheet">
  <div class="br-sheet-h"><h3 style="font-family:var(--bk-font-ui);font-weight:700">{{ $t('حجزك','Your booking') }}</h3><button class="br-icon-btn" onclick="brCloseSheet()" aria-label="{{ $t('إغلاق','Close') }}"><x-icon name="x" :size="18"/></button></div>
  <div class="br-sheet-b"><div id="br-sheet-items"></div></div>
  <div class="br-book-foot">
    <div class="br-book-tot"><div class="l">{{ $t('الإجمالي','Total') }}</div><div class="p bkf-tnum" id="br-sheet-price">0 {{ $currency }}</div></div>
    <button type="button" class="bkf-btn bkf-btn-primary bkf-btn-block" onclick="brCloseSheet();brBook()"><x-icon name="calendar" :size="18"/>{{ $t('متابعة الحجز','Continue to booking') }}</button>
  </div>
</div>

{{-- lightbox --}}
<div class="br-lb" id="br-lb">
  <button class="br-lb-x" onclick="brLb.close()" aria-label="{{ $t('إغلاق','Close') }}"><x-icon name="x" :size="22"/></button>
  <button class="br-lb-nav prev" onclick="brLb.step(-1)" aria-label="prev"><x-icon name="chevron-right" :size="22" style="transform:scaleX(-1)"/></button>
  <img id="br-lb-img" src="" alt="">
  <button class="br-lb-nav next" onclick="brLb.step(1)" aria-label="next"><x-icon name="chevron-right" :size="22"/></button>
</div>

@include('front.partials.group-booking-modal')
{{-- customer-auth-modal is centralised in x-front.layout --}}

<x-slot:scripts>
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
<script>
(function () {
  'use strict';
  var AR = @json($isAr), CUR = @json($currency);
  var branchId = @json($branch->id), branchName = @json($brName);
  var EMPS = @json($empData);
  var GALLERY = @json($imgs->take(20)->values());
  var MINPRICE = @json($minPrice ?: 0);
  var cart = [];

  function money(n){ return (parseFloat(n)||0).toFixed(0) + ' ' + CUR; }
  function el(id){ return document.getElementById(id); }
  function toast(m){ var t=document.createElement('div'); t.textContent=m; t.style.cssText='position:fixed;left:50%;bottom:96px;transform:translateX(-50%);background:var(--bk-text);color:var(--bk-bg);padding:10px 18px;border-radius:999px;font-family:var(--bk-font-ui);font-size:.85rem;z-index:1300;box-shadow:var(--bk-shadow-lg);opacity:0;transition:opacity .3s'; document.body.appendChild(t); requestAnimationFrame(function(){t.style.opacity='1';}); setTimeout(function(){t.style.opacity='0';setTimeout(function(){t.remove();},300);},2400); }
  function matched(catId){ var m=EMPS.filter(function(e){return catId&&e.cats&&e.cats.indexOf(catId)>-1;}); return m.length?m:EMPS.slice(); }

  /* favorites */
  (function(){ var l; try{l=JSON.parse(localStorage.getItem('bk_favs')||'[]');}catch(e){l=[];}
    document.querySelectorAll('[data-fav]').forEach(function(b){ if(l.indexOf(+b.dataset.fav)>-1){ b.classList.add('is-on'); b.querySelector('.heart-off').style.display='none'; b.querySelector('.heart-on').style.display='block'; } });
    document.addEventListener('click',function(e){ var f=e.target.closest('[data-fav]'); if(!f)return; var id=+f.dataset.fav; var a; try{a=JSON.parse(localStorage.getItem('bk_favs')||'[]');}catch(x){a=[];} var i=a.indexOf(id); if(i>-1)a.splice(i,1); else a.push(id); try{localStorage.setItem('bk_favs',JSON.stringify(a));}catch(x){} var on=i===-1; f.classList.toggle('is-on',on); f.querySelector('.heart-off').style.display=on?'none':'block'; f.querySelector('.heart-on').style.display=on?'block':'none'; });
  })();

  /* ── cart ── */
  window.brToggle = function (btn) {
    var id = +btn.dataset.svc, i = cart.findIndex(function (x) { return x.serviceId === id; });
    if (i > -1) { cart.splice(i, 1); setBtn(btn, false); }
    else {
      var catId = btn.dataset.cat ? +btn.dataset.cat : null;
      cart.push({ serviceId:id, name:btn.dataset.name, price:+btn.dataset.price||0, duration:+btn.dataset.duration||0, catId:catId, employeeId:null, matchedEmps:matched(catId) });
      setBtn(btn, true);
    }
    render();
  };
  function setBtn(btn, on){ btn.classList.toggle('is-added', on); btn.querySelector('.lbl').textContent = on ? (AR?'أُضيف':'Added') : (AR?'أضف':'Add'); btn.querySelector('.ic-on').style.display = on?'':'none'; }
  window.brRemove = function (id) {
    cart = cart.filter(function (x) { return x.serviceId !== id; });
    var b = document.querySelector('.br-svc-add[data-svc="'+id+'"]'); if (b) setBtn(b, false);
    render();
  };
  window.brSetEmp = function (id, empId) { var it = cart.find(function (x) { return x.serviceId === id; }); if (it) it.employeeId = empId ? +empId : null; };

  function itemHTML(it, ctx){
    var opts = '<option value="">'+(AR?'أي موظف متاح':'Any available staff')+'</option>';
    it.matchedEmps.forEach(function(e){ opts += '<option value="'+e.id+'"'+(it.employeeId===e.id?' selected':'')+'>'+e.name+'</option>'; });
    var dur = it.duration ? '<span style="color:var(--bk-text-muted);font-size:.75rem">'+it.duration+(AR?' د':' min')+'</span>' : '';
    return '<div class="br-bi">'
      + '<div class="br-bi-top"><div><div class="br-bi-nm">'+it.name+'</div>'+dur+'</div>'
      + '<div style="display:flex;align-items:center;gap:8px"><span class="br-bi-pr bkf-tnum">'+money(it.price)+'</span>'
      + '<button class="br-bi-rm" onclick="brRemove('+it.serviceId+')" aria-label="remove">✕</button></div></div>'
      + (ctx==='aside' ? '<select class="br-bi-emp" onchange="brSetEmp('+it.serviceId+',this.value)">'+opts+'</select>' : '')
      + '</div>';
  }

  function render(){
    var count = cart.length;
    var price = cart.reduce(function(s,i){return s+(parseFloat(i.price)||0);},0);
    var dur   = cart.reduce(function(s,i){return s+(parseInt(i.duration)||0);},0);

    // aside
    var items = el('br-cart-items'), empty = el('br-cart-empty'), foot = el('br-cart-foot'), cnt = el('br-cart-count');
    if (items){
      if (count===0){ items.innerHTML=''; empty.style.display=''; foot.style.display='none'; cnt.style.display='none'; }
      else {
        empty.style.display='none'; foot.style.display=''; cnt.style.display=''; cnt.textContent=count;
        items.innerHTML = cart.map(function(it){return itemHTML(it,'aside');}).join('');
        el('br-cart-price').textContent = money(price);
        el('br-cart-dur').textContent = dur ? (dur+(AR?' دقيقة':' min')) : '';
      }
    }
    // mobile bar (persistent; content adapts to cart state)
    var barPrice = el('br-bar-price'), barLabel = el('br-bar-label'), barBtnLbl = el('br-bar-btn-lbl');
    if (count > 0){
      if (barPrice) barPrice.textContent = money(price);
      if (barLabel) barLabel.textContent = count + ' ' + (AR ? 'خدمة مختارة' : (count === 1 ? 'service' : 'services'));
      if (barBtnLbl) barBtnLbl.textContent = AR ? 'متابعة' : 'Continue';
    } else {
      if (barPrice) barPrice.textContent = MINPRICE ? ((AR ? 'من ' : 'from ') + money(MINPRICE)) : (AR ? 'احجز موعدك' : 'Book a visit');
      if (barLabel) barLabel.textContent = AR ? 'اختر خدماتك' : 'Pick your services';
      if (barBtnLbl) barBtnLbl.textContent = AR ? 'احجز الآن' : 'Book now';
    }
    var si = el('br-sheet-items'); if (si){ si.innerHTML = cart.map(function(it){return itemHTML(it,'sheet');}).join('') || '<div class="br-book-empty">'+(AR?'لا خدمات':'No services')+'</div>'; el('br-sheet-price').textContent = money(price); }
  }

  // Bar button: with items → open the cart sheet; empty → jump to services.
  window.brBarAction = function(){ if (cart.length) brOpenSheet(); else { var s = el('br-services'); if (s) s.scrollIntoView({ behavior:'smooth', block:'start' }); } };
  window.brOpenSheet = function(){ el('br-sheet-ov').classList.add('open'); el('br-sheet').classList.add('open'); document.body.style.overflow='hidden'; };
  window.brCloseSheet = function(){ el('br-sheet-ov').classList.remove('open'); el('br-sheet').classList.remove('open'); document.body.style.overflow=''; };

  /* ── booking → Fresha-style group modal (one time, all services/guests) ── */
  window.brBook = function(){
    if (!cart.length){ toast(AR?'اختر خدمة أولاً':'Add a service first'); return; }
    if (!window.GroupBookingModal){ toast(AR?'تعذّر فتح الحجز':'Booking unavailable'); return; }
    GroupBookingModal.open(cart.map(function(x){ return x.serviceId; }));
  };

  /* ── tabs / scroll-spy ── */
  var tabs = [].slice.call(document.querySelectorAll('.br-tab'));
  tabs.forEach(function(tb){ tb.addEventListener('click', function(){ var s=el(tb.dataset.target); if(s) s.scrollIntoView({behavior:'smooth',block:'start'}); }); });
  if ('IntersectionObserver' in window){
    var spy = new IntersectionObserver(function(ents){ ents.forEach(function(en){ if(en.isIntersecting){ tabs.forEach(function(t){ t.classList.toggle('is-active', t.dataset.target===en.target.id); }); } }); }, { rootMargin:'-40% 0px -55% 0px' });
    tabs.forEach(function(t){ var s=el(t.dataset.target); if(s) spy.observe(s); });
  }

  /* ── services: category chips filter (Fresha-style) ── */
  (function(){
    var tabs = [].slice.call(document.querySelectorAll('.br-svc-tab'));
    if(!tabs.length) return;
    var cats = [].slice.call(document.querySelectorAll('#br-services .br-svc-cat'));
    var tabsBar = el('br-svc-tabs');
    function apply(cat){ cats.forEach(function(c){ c.style.display = (cat==='all' || c.dataset.cat===cat) ? '' : 'none'; }); }
    tabs.forEach(function(t){ t.addEventListener('click', function(){
      tabs.forEach(function(x){ x.classList.remove('is-active'); x.setAttribute('aria-selected','false'); });
      t.classList.add('is-active'); t.setAttribute('aria-selected','true');
      apply(t.dataset.cat);
      // keep the chosen chip in view
      if (t.scrollIntoView) t.scrollIntoView({ inline:'center', block:'nearest', behavior:'smooth' });
    }); });
  })();

  /* ── hours: collapse to today on small screens ── */
  (function(){
    if(!window.matchMedia('(max-width:760px)').matches) return;
    var hours = document.querySelector('.br-hours'); if(!hours) return;
    var rows = [].slice.call(hours.querySelectorAll('.row')); if(rows.length <= 1) return;
    rows.forEach(function(r){ if(!r.classList.contains('today')) r.classList.add('br-hidden'); });
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'br-hours-toggle bkf-btn bkf-btn-ghost bkf-btn-sm bkf-btn-block';
    btn.textContent = AR ? 'عرض كل الأوقات' : 'Show all hours';
    btn.addEventListener('click', function(){ rows.forEach(function(r){ r.classList.remove('br-hidden'); }); btn.remove(); });
    hours.parentNode.insertBefore(btn, hours.nextSibling);
  })();

  /* ── lightbox ── */
  var lbIdx = 0;
  window.brLb = {
    open:function(i){ lbIdx=i; el('br-lb-img').src=GALLERY[i]; el('br-lb').classList.add('open'); document.body.style.overflow='hidden'; },
    close:function(){ el('br-lb').classList.remove('open'); document.body.style.overflow=''; },
    step:function(d){ lbIdx=(lbIdx+d+GALLERY.length)%GALLERY.length; el('br-lb-img').src=GALLERY[lbIdx]; }
  };
  document.querySelectorAll('#br-gallery [data-lb]').forEach(function(g){ g.addEventListener('click', function(){ brLb.open(+g.dataset.lb); }); });
  document.addEventListener('keydown', function(e){ if(!el('br-lb').classList.contains('open'))return; if(e.key==='Escape')brLb.close(); if(e.key==='ArrowRight')brLb.step(AR?-1:1); if(e.key==='ArrowLeft')brLb.step(AR?1:-1); });

  /* ── leaflet single-marker map ── */
  function initMap(){
    var m = el('br-map'); if(!m) return;
    if(!window.L){ window.addEventListener('load', initMap, {once:true}); return; }
    var lat=+m.dataset.lat, lng=+m.dataset.lng;
    var map = L.map('br-map',{scrollWheelZoom:false,zoomControl:true}).setView([lat,lng],15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);
    L.marker([lat,lng]).addTo(map).bindPopup('<b>'+m.dataset.name+'</b>').openPopup();
    setTimeout(function(){ map.invalidateSize(); }, 200);
  }
  initMap();
  window.addEventListener('resize', function(){ render(); });
})();
</script>
</x-slot:scripts>
</x-front.layout>
