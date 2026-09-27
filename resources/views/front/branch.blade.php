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

    // Staff photos are uploaded at full size (several MB) — a 192px copy is plenty.
    $avatar = fn($e) => $e->image ? \App\Support\ImageThumb::url($e->image, 192) : null;

    $empData = $branch->employees->map(fn($e) => [
        'id'    => $e->id,
        'name'  => $isAr ? ($e->name_ar ?: $e->name_en) : ($e->name_en ?: $e->name_ar),
        'image' => $avatar($e),
        'cats'  => $e->serviceCategories->pluck('id')->toArray(),
    ])->values();

    // Photos: "place" photos show the venue (hero); "work" photos are the
    // results gallery. Both only approved (FrontController::publicImages).
    $placeImages = $allImages->where('type', \App\Models\BranchImage::TYPE_PLACE)->values();
    $workImages  = $allImages->where('type', \App\Models\BranchImage::TYPE_WORK)->values();
    $heroImages  = $placeImages->isNotEmpty() ? $placeImages : $workImages;

    // hero gallery (≤1600px copies — the uploads can be 5000px+)
    $imgs = $heroImages->map(fn($i) => $i->largeUrl())->values();

    // The brand logo is identity, not a venue photo: shown on its own next to the
    // name and never mixed into the photo gallery (only the share preview falls back to it).
    $logoUrl = $company->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo)
        ? \App\Support\ImageThumb::url($company->logo, 320) : null;   // 2× the 112px tile → crisp on retina

    // work gallery: grid tile + large copy for the lightbox
    $workData = $workImages->map(fn($i) => ['grid' => $i->gridUrl(), 'large' => $i->largeUrl(), 'w' => $i->width, 'h' => $i->height])->values();

    // this branch's own contact channels (phones + its social links)
    $contacts = $branch->publicContacts();
    $whatsapp = collect($contacts)->firstWhere('platform', 'whatsapp');
    // direct channels (call / WhatsApp) vs. the branch's social profiles — both render as icons only
    $directContacts = collect($contacts)->filter(fn($c) => $c['kind'] === 'phone' || $c['platform'] === 'whatsapp')->values();
    $socialContacts = collect($contacts)->reject(fn($c) => $c['kind'] === 'phone' || $c['platform'] === 'whatsapp')->values();

    // working hours
    $dayNames = $isAr ? ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']
                      : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $whByDay  = $branch->workingHours->groupBy('day_of_week');
    // "Now" and "today" are the branch's own clock (Branch Settings timezone).
    $branchNow = $branch->localNow();
    $todayDow = $branchNow->dayOfWeek;
    $todayOpen = $whByDay->get($todayDow, collect())->where('is_open', true);
    $isOpenNow = false; $todayLabel = $t('مغلق اليوم', 'Closed today');
    if ($todayOpen->isNotEmpty()) {
        $wh = $todayOpen->first();
        $fmt = fn($v) => $v ? $branch->formatTime($v) : '';
        $todayLabel = $fmt($wh->open_time).' – '.$fmt($wh->close_time);
        $nowT = $branchNow->format('H:i:s');
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
    :ogImage="$imgs->first() ?? ($company->logo ? asset('storage/'.$company->logo) : null)"
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
        'logo'        => $company->logo ? asset('storage/'.$company->logo) : null,
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
.br-gallery .g-empty{ cursor:default; background:var(--bk-surface-2); }
@media (max-width:760px){ .br-gallery{ grid-template-columns:1fr 1fr; grid-template-rows:1fr; height:240px; } .br-gallery .g:first-child{ grid-row:1; grid-column:1/3; } .br-gallery .g:nth-child(n+3){ display:none; } }

/* head */
.br-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:20px; flex-wrap:wrap; margin:22px 0 4px; }
.br-title{ font-size:var(--bk-fs-h1); }
/* brand identity: logo tile beside the name (never part of the photo gallery) */
.br-head-id{ display:flex; align-items:center; gap:18px; min-width:0; }
.br-head-tx{ min-width:0; }
.br-logo{ flex:0 0 112px; width:112px; height:112px; border-radius:50%; background:#fff; border:1px solid var(--bk-border); box-shadow:0 0 0 4px var(--bk-bg),0 0 0 5px color-mix(in srgb,var(--bk-gold-strong) 40%,transparent),var(--bk-shadow-sm); display:grid; place-items:center; overflow:hidden; }
/* contain + inset: the whole mark stays visible inside the circle (wide wordmarks too) */
.br-logo img{ width:78%; height:78%; object-fit:contain; display:block; }
.br-head-co{ margin-top:2px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); font-weight:600; color:var(--bk-gold-strong); }
@media (max-width:560px){ .br-head-id{ gap:14px; } .br-logo{ flex-basis:84px; width:84px; height:84px; } }
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
.br-layout{ display:grid; grid-template-columns:minmax(0,1fr) 380px; gap:var(--bk-s10); align-items:start; margin-top:var(--bk-s10); }
.br-main{ min-width:0; }   /* let the main column shrink instead of overflowing the viewport → no horizontal scroll, nothing hidden on the right */
@media (max-width:980px){ .br-layout{ grid-template-columns:minmax(0,1fr); } .br-aside{ display:none; } }
/* belt-and-braces: the page itself never scrolls sideways on mobile */
@media (max-width:980px){ html,body{ overflow-x:clip; } }

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
/* collapsible category: the heading is the toggle (▼ open · ▲ collapsed) */
button.br-svc-cat-h{ width:100%; padding:4px 0; background:none; border:0; cursor:pointer; text-align:start; }
button.br-svc-cat-h:hover{ color:var(--bk-accent); }
button.br-svc-cat-h:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:3px; border-radius:var(--bk-r-sm); }
.br-svc-cat-n{ min-width:22px; padding:0 6px; border-radius:var(--bk-r-pill); background:var(--bk-accent-wash); color:var(--bk-accent); font-size:var(--bk-fs-xs); font-weight:700; line-height:1.7; text-align:center; }
.br-svc-cat-h .br-svc-cat-chev{ width:16px; height:16px; color:var(--bk-text-soft); transition:transform .3s var(--bk-ease); }
.br-svc-cat-h[aria-expanded="false"] .br-svc-cat-chev{ transform:rotate(180deg); }
.br-svc-list{ display:grid; grid-template-rows:1fr; opacity:1; transition:grid-template-rows .32s var(--bk-ease),opacity .25s ease; }
.br-svc-list-in{ min-height:0; overflow:hidden; }
.br-svc-cat.is-collapsed .br-svc-list{ grid-template-rows:0fr; opacity:0; }
.br-svc-cat.is-collapsed .br-svc-list-in{ visibility:hidden; transition:visibility 0s .32s; }
@media (prefers-reduced-motion:reduce){ .br-svc-list,.br-svc-cat-chev{ transition:none; } }
.br-svc{ display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px; border:1px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface); margin-bottom:10px; transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.br-svc-info{ flex:1 1 auto; min-width:0; }        /* name column takes the room, never collapses to 0 */
.br-svc-nm{ overflow-wrap:break-word; }            /* wrap between words only — never letter-by-letter */
/* higher specificity than the global mobile `.bkf-btn{width:100%}` so Add stays intrinsic */
.br-svc .br-svc-add{ flex:0 0 auto; width:auto; }
.br-svc:hover{ border-color:color-mix(in srgb,var(--bk-accent) 30%,var(--bk-border)); box-shadow:var(--bk-shadow-sm); }
.br-svc-info{ min-width:0; }
.br-svc-nm{ font-family:var(--bk-font-ui); font-weight:600; color:var(--bk-text); }
.br-svc-meta{ display:flex; align-items:center; flex-wrap:wrap; gap:8px 14px; margin-top:6px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); }
/* each meta item aligns its icon + text on one line, vertically centred */
.br-svc-meta > span{ display:inline-flex; align-items:center; gap:5px; white-space:nowrap; }
.br-svc-meta svg{ width:14px; height:14px; flex-shrink:0; }
.br-svc-price{ color:var(--bk-gold-strong); font-weight:700; }
/* merchandising badges — mirror the labels set in the services workbench */
.br-svc-tags{ display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
.br-svc-tag{ display:inline-flex; align-items:center; gap:4px; padding:2px 9px; border-radius:var(--bk-r-pill); font-family:var(--bk-font-ui); font-size:.6875rem; font-weight:700; line-height:1.5; letter-spacing:.01em; border:1px solid transparent; }
.br-svc-tag svg{ width:12px; height:12px; }
.br-svc-tag--popular{ color:var(--bk-gold-strong); background:color-mix(in srgb,var(--bk-gold-strong) 12%,transparent); border-color:color-mix(in srgb,var(--bk-gold-strong) 26%,transparent); }
.br-svc-tag--new{ color:var(--bk-accent); background:var(--bk-accent-wash); border-color:color-mix(in srgb,var(--bk-accent) 28%,transparent); }
.br-svc-tag--offer{ color:#c0392b; background:color-mix(in srgb,#e53935 12%,transparent); border-color:color-mix(in srgb,#e53935 26%,transparent); }
.br-svc-tag--premium{ color:var(--bk-text); background:color-mix(in srgb,var(--bk-text) 8%,transparent); border-color:color-mix(in srgb,var(--bk-text) 20%,transparent); }
.br-svc-add{ flex-shrink:0; }
.br-svc-add.is-added{ background:var(--bk-accent-wash); color:var(--bk-accent); border-color:var(--bk-accent); }
/* service name row: name + a small type chip for non-standard services */
.br-svc-nm-row{ display:flex; align-items:center; flex-wrap:wrap; gap:8px; }
.br-svc-type{ display:inline-flex; align-items:center; gap:4px; padding:1px 8px; border-radius:var(--bk-r-pill); font-family:var(--bk-font-ui); font-size:.6875rem; font-weight:700; line-height:1.6; color:var(--bk-accent); background:var(--bk-accent-wash); border:1px solid color-mix(in srgb,var(--bk-accent) 26%,transparent); }
.br-svc-type svg{ width:11px; height:11px; }
/* short customer-facing description under the name */
.br-svc-desc{ margin-top:5px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-soft); line-height:1.6; overflow-wrap:break-word; }
/* what a package bundles */
.br-svc-incl{ margin-top:6px; font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); line-height:1.6; }
.br-svc-incl b{ color:var(--bk-text-soft); font-weight:700; }
/* discounted pricing: struck original + accent final + savings chip */
.br-svc-price-was{ color:var(--bk-text-muted); font-weight:600; text-decoration:line-through; }
.br-svc-off{ display:inline-flex; align-items:center; padding:1px 7px; border-radius:var(--bk-r-pill); font-family:var(--bk-font-ui); font-size:.6875rem; font-weight:700; line-height:1.6; color:#c0392b; background:color-mix(in srgb,#e53935 12%,transparent); border:1px solid color-mix(in srgb,#e53935 26%,transparent); }

/* team — uniform portrait cards: photo (or neutral silhouette) · name · role · specialties */
.br-team{ list-style:none; margin:0; padding:0; display:grid; grid-template-columns:repeat(auto-fill,minmax(176px,1fr)); gap:12px; }
.br-member{ display:flex; flex-direction:column; align-items:center; text-align:center; gap:4px; padding:22px 14px 18px; border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); background:var(--bk-surface); min-width:0; transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.br-member:hover{ border-color:color-mix(in srgb,var(--bk-accent) 30%,var(--bk-border)); box-shadow:var(--bk-shadow-sm); }
.br-member-av{ flex:0 0 auto; width:80px; height:80px; margin-bottom:10px; border-radius:50%; overflow:hidden; display:grid; place-items:center; background:var(--bk-surface-2); box-shadow:0 0 0 3px var(--bk-surface),0 0 0 4px color-mix(in srgb,var(--bk-gold-strong) 45%,transparent); }
.br-member-av img{ width:100%; height:100%; object-fit:cover; display:block; }
.br-member-av.is-empty{ background:var(--bk-accent-wash); color:color-mix(in srgb,var(--bk-accent) 70%,transparent); }
.br-member-nm{ max-width:100%; font-family:var(--bk-font-ui); font-weight:700; color:var(--bk-text); line-height:1.35; overflow-wrap:anywhere; }
.br-member-rl{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-gold-strong); font-weight:600; letter-spacing:.01em; }
.br-member-sp{ display:flex; flex-wrap:wrap; justify-content:center; gap:4px; margin-top:8px; max-width:100%; }
.br-member-chip{ max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; padding:2px 9px; border-radius:var(--bk-r-pill); background:var(--bk-surface-2); color:var(--bk-text-soft); font-family:var(--bk-font-ui); font-size:.6875rem; font-weight:600; line-height:1.6; }
.br-member-chip.is-more{ background:var(--bk-accent-wash); color:var(--bk-accent); }
@media (max-width:560px){
  .br-team{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
  .br-member{ padding:18px 10px 14px; }
  .br-member-av{ width:64px; height:64px; margin-bottom:8px; }
}

/* service photo (only when the service has one) */
.br-svc-img{ flex:0 0 64px; width:64px; height:64px; border-radius:var(--bk-r-sm); object-fit:cover; background:var(--bk-surface-2); }
@media (max-width:560px){ .br-svc-img{ flex-basis:52px; width:52px; height:52px; } }

/* visit: [address → directions → map] card · contact icons · opening hours — stacked */
.br-visit-h{ font-family:var(--bk-font-ui); font-weight:700; font-size:var(--bk-fs-sm); color:var(--bk-text); margin:0 0 10px; }
.br-visit-sub{ margin-top:26px; }
.br-visit-addr{ font-family:var(--bk-font-ui); color:var(--bk-text-soft); line-height:1.7; margin:0; overflow-wrap:anywhere; }
.br-place{ border:1px solid var(--bk-border); border-radius:var(--bk-r-lg); background:var(--bk-surface); overflow:hidden; }
.br-place-top{ display:flex; align-items:center; gap:14px; padding:16px 18px; }
.br-place-ic{ flex:0 0 40px; width:40px; height:40px; border-radius:50%; display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.br-place-tx{ flex:1 1 auto; min-width:0; }
.br-place-tx .br-visit-h{ margin-bottom:2px; }
.br-place-dir{ flex:0 0 auto; display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:var(--bk-r-pill); border:1px solid color-mix(in srgb,var(--bk-accent) 35%,var(--bk-border)); color:var(--bk-accent); font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); font-weight:600; white-space:nowrap; transition:background var(--bk-t) ease,color var(--bk-t) ease; }
.br-place-dir:hover{ background:var(--bk-accent); color:var(--bk-accent-ink); }
.br-place-dir:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; }
@media (max-width:480px){
  .br-place-top{ flex-wrap:wrap; padding:14px; }
  .br-place-dir{ flex:1 1 100%; justify-content:center; }
}
/* icon-only channels (contact + social) */
.br-channels{ display:flex; flex-wrap:wrap; gap:10px; }
.br-channels--center{ justify-content:center; }
.br-center{ text-align:center; }
.br-channel{ width:48px; height:48px; border-radius:50%; display:grid; place-items:center; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); transition:transform var(--bk-t) ease,border-color var(--bk-t) ease,box-shadow var(--bk-t) ease; }
.br-channel:hover{ transform:translateY(-2px); border-color:currentColor; box-shadow:var(--bk-shadow-sm); }
.br-channel:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; }
.br-channel--phone{ color:var(--bk-accent); } .br-channel--whatsapp{ color:#1DA851; } .br-channel--instagram{ color:#D62976; }
.br-channel--facebook{ color:#1877F2; } .br-channel--linkedin{ color:#0A66C2; } .br-channel--youtube{ color:#E00000; }
.br-channel--twitter,.br-channel--tiktok{ color:var(--bk-text); } .br-channel--snapchat{ color:#E0B400; } .br-channel--website{ color:var(--bk-accent); }
.br-icon-btn--wa{ color:#1DA851; }

/* work gallery */
.br-block-head{ display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:var(--bk-s5); }
.br-block-head .br-block-title{ margin-bottom:0; }
.br-block-count{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); color:var(--bk-text-muted); }
.br-work{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
@media (max-width:760px){ .br-work{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; } }
.br-work-tile{ display:block; padding:0; border:0; border-radius:var(--bk-r-sm); overflow:hidden; aspect-ratio:1 / 1; background:var(--bk-surface-2); cursor:zoom-in; }
.br-work-tile img{ width:100%; height:100%; object-fit:cover; display:block; transition:opacity var(--bk-t) ease; }
.br-work-tile:hover img{ opacity:.9; }
.br-work-tile:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; }
.br-work-more{ margin-top:12px; }

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
/* isolation keeps Leaflet's internal z-indexes (400–1000) inside the map, so it can't
   paint over the sticky header, the tab bar or the photo viewer */
#br-map{ position:relative; z-index:0; isolation:isolate; height:300px; width:100%; overflow:hidden; border-top:1px solid var(--bk-border); }

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
.br-lb-count{ position:absolute; inset-block-end:18px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,.8); font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm); }
.br-lb-x:focus-visible,.br-lb-nav:focus-visible{ outline:2px solid #fff; outline-offset:2px; }
@media (max-width:560px){ .br-lb-nav{ width:40px; height:40px; } .br-lb img{ max-width:100vw; border-radius:0; } }

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
/* mouse users can grab-and-drag the rail (touch/trackpad keep native scrolling) */
@media (hover:hover) and (pointer:fine){ .br-svc-tabs{ cursor:grab; } }
.br-svc-tabs.is-dragging{ cursor:grabbing; scroll-snap-type:none; user-select:none; }

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
  /* single image (no swipe rail needed): fill the width, centred */
  .br-gallery-single .g{ flex:0 0 100% !important; }
  .br-photos-badge{ display:inline-flex; }
  #br-map{ height:220px; }
  .br-title{ font-size:1.55rem; }
}
@media (max-width:980px){
  .br-bar{ display:flex; }                 /* persistent CTA bar on mobile */
  .br-block{ margin-bottom:30px; }          /* tighter vertical rhythm */
  .br-block-title{ margin-bottom:16px; }
  .br-wrap{ padding-bottom:calc(88px + env(safe-area-inset-bottom)); } /* clear the fixed bar */
  /* sticky bar: text must never push the CTA off-screen */
  .br-bar-info{ min-width:0; flex:1 1 auto; }
  .br-bar-info .p,.br-bar-info .l{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .br-bar .bkf-btn{ flex:0 0 auto; }
}
/* service rows: keep name + meta + Add tidy on narrow phones */
@media (max-width:560px){
  .br-svc{ padding:13px 14px; gap:10px; }
  .br-svc-meta{ flex-wrap:wrap; gap:6px 12px; }
  .br-svc-add{ align-self:center; }
}
@media (max-width:380px){
  .br-bar{ padding-inline:12px; gap:8px; }
  .br-bar .bkf-btn{ padding-inline:15px; }
  .br-bar-info .p{ font-size:1rem; }
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
      {{-- no venue photos yet: a plain, quiet surface — no stock image, no icon --}}
      <div class="g g-empty" aria-hidden="true"></div>
    @endif
    @if($imgs->count() > 1)
      <button type="button" class="br-photos-badge" onclick="brLb.open(0)"><x-icon name="grid" :size="16"/>{{ $imgs->count() }} {{ $t('صورة','photos') }}</button>
    @endif
  </div>

  {{-- head --}}
  <div class="br-head">
    <div class="br-head-id">
      @if($logoUrl)
        <span class="br-logo"><img src="{{ $logoUrl }}" alt="{{ $coName }}" width="112" height="112" decoding="async"></span>
      @endif
    <div class="br-head-tx">
      <h1 class="br-title">{{ $brName }}</h1>
      @if($coName && $coName !== $brName)<div class="br-head-co">{{ $coName }}</div>@endif
      <div class="br-head-meta">
        @if($catName)<span class="bkf-chip"><x-icon name="{{ $catIcon($company->category->slug ?? '') }}" :size="14"/>{{ $catName }}</span>@endif
        @if($avg)<span class="br-rate-pill bkf-tnum"><x-icon name="star-fill" :size="16"/>{{ number_format($avg,1) }} <span style="color:var(--bk-text-muted);font-weight:500">· {{ $totalRev }} {{ $t('تقييم','reviews') }}</span></span>@endif
        @if($city || $branch->address)<span class="it"><x-icon name="map-pin" :size="16"/>{{ $city ?: Str::limit($branch->address, 40) }}</span>@endif
        <span class="br-open {{ $isOpenNow ? 'on' : 'off' }}"><span class="dot"></span>{{ $isOpenNow ? $t('مفتوح الآن','Open now') : $t('مغلق الآن','Closed') }} · {{ $todayLabel }}</span>
      </div>
    </div>
    </div>
    <div class="br-head-actions">
      <button type="button" class="br-icon-btn" data-fav="{{ $branch->id }}" aria-label="{{ $t('حفظ','Save') }}">
        <x-icon name="heart" :size="20" class="heart-off"/><x-icon name="heart-fill" :size="20" class="heart-on" style="display:none"/>
      </button>
      @if($branch->latitude && $branch->longitude)
      <a class="br-icon-btn" href="https://www.google.com/maps/dir/?api=1&destination={{ $branch->latitude }},{{ $branch->longitude }}" target="_blank" rel="noopener" aria-label="{{ $t('الاتجاهات','Directions') }}"><x-icon name="navigation" :size="20"/></a>
      @endif
      @if($whatsapp)
      <a class="br-icon-btn br-icon-btn--wa" href="{{ $whatsapp['href'] }}" target="_blank" rel="noopener" aria-label="WhatsApp">@include('partials.social-icon', ['platform' => 'whatsapp', 'size' => 19])</a>
      @endif
      @if($branch->phone)
      <a class="br-icon-btn" href="tel:{{ preg_replace('/[^\d+]/', '', $branch->phone) }}" aria-label="{{ $t('اتصال','Call') }}"><x-icon name="phone" :size="20"/></a>
      @endif
    </div>
  </div>

  {{-- tabs --}}
  <div class="br-tabs" id="br-tabs">
    <button class="br-tab is-active" data-target="br-services">{{ $t('الخدمات','Services') }}</button>
    @if($employees->isNotEmpty())<button class="br-tab" data-target="br-team">{{ $t('الفريق','Team') }}</button>@endif
    <button class="br-tab" data-target="br-location">{{ $t('الموقع وأوقات العمل','Location & hours') }}</button>
    @if($workImages->isNotEmpty())<button class="br-tab" data-target="br-work">{{ $t('معرض الأعمال','Our work') }}</button>@endif
    @if($totalRev)<button class="br-tab" data-target="br-reviews">{{ $t('التقييمات','Reviews') }}</button>@endif
    @if($branch->description_en || $branch->description_ar)<button class="br-tab" data-target="br-about">{{ $t('نبذة','About') }}</button>@endif
  </div>

  <div class="br-layout">
    {{-- MAIN --}}
    <div class="br-main">

      {{-- services --}}
      <section class="br-block" id="br-services">
        <h2 class="br-block-title">{{ $t('الخدمات والأسعار','Services & prices') }}</h2>
        @if($servicesByCategory->count() > 1)
        {{-- every category is rendered; the rail scrolls (wheel / touch / mouse-drag) --}}
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
            {{-- ▼ open · ▲ collapsed — folds this category's services --}}
            <button type="button" class="br-svc-cat-h" aria-expanded="true" aria-controls="br-svc-list-{{ $loop->index }}">
              <x-icon name="tag" :size="18"/><span>{{ $scName }}</span>
              <span class="br-svc-cat-n bkf-tnum">{{ $services->where('is_active', true)->count() }}</span>
              <x-icon name="chevron-down" :size="16" class="br-svc-cat-chev"/>
            </button>
            <div class="br-svc-list" id="br-svc-list-{{ $loop->index }}"><div class="br-svc-list-in">
            @foreach($services->where('is_active', true) as $svc)
              @php $sName = $isAr ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar); @endphp
              @php
                $svcBadges = array_values(array_intersect((array) ($svc->badges ?? []), \App\Models\Service::BADGES));
                $badgeMeta = [
                    'most_requested' => ['popular', 'trending-up', $t('الأكثر طلباً','Most requested')],
                    'new'            => ['new', 'sparkles', $t('جديد','New')],
                    'special_offer'  => ['offer', 'tag', $t('عرض خاص','Special offer')],
                    'premium'        => ['premium', 'star', $t('مميّزة','Premium')],
                ];
                // Non-standard classifications get a small chip so the customer sees
                // whether this is a package, membership, consultation or add-on.
                $typeMeta = [
                    'package'      => ['gift', $t('باقة','Package')],
                    'membership'   => ['award', $t('عضوية','Membership')],
                    'consultation' => ['message', $t('استشارة','Consultation')],
                    'addon'        => ['layers', $t('إضافة','Add-on')],
                ];
                // Service-level currency, localised for Arabic (matches the merchant setting).
                $svcCurrency = $svc->currency === 'SYP' ? $currency : $svc->currency;
                // Discount is only surfaced when actually active (respects start/end
                // window) and on a fixed price, where a strike-through reads cleanly.
                $hasOffer   = $svc->hasActiveDiscount() && $svc->price_type === 'fixed' && $svc->price > 0;
                $finalPrice = $svc->finalPrice();
                $cartPrice  = $hasOffer ? $finalPrice : ($svc->price ?: 0);
                $offerLabel = $svc->discount_type === 'percent'
                    ? '-'.rtrim(rtrim(number_format((float) $svc->discount_value, 2, '.', ''), '0'), '.').'%'
                    : '-'.number_format((float) $svc->discount_value, 0).' '.$svcCurrency;
              @endphp
              <div class="br-svc">
                @if($svc->image_path)
                  {{-- the service's own photo (160px copy of the upload); services without one simply have no image --}}
                  <img class="br-svc-img" src="{{ \App\Support\ImageThumb::url($svc->image_path, 160) }}" alt="{{ $sName }}" width="64" height="64" loading="lazy" decoding="async">
                @endif
                <div class="br-svc-info">
                  <div class="br-svc-nm-row">
                    <span class="br-svc-nm">{{ $sName }}</span>
                    @if(isset($typeMeta[$svc->service_type]))
                      @php [$tIcon, $tLabel] = $typeMeta[$svc->service_type]; @endphp
                      <span class="br-svc-type"><x-icon :name="$tIcon" :size="11"/>{{ $tLabel }}</span>
                    @endif
                  </div>
                  @if($svcBadges)
                  <div class="br-svc-tags">
                    @foreach($svcBadges as $b)
                      @php [$variant, $icon, $label] = $badgeMeta[$b]; @endphp
                      <span class="br-svc-tag br-svc-tag--{{ $variant }}"><x-icon :name="$icon" :size="12"/>{{ $label }}</span>
                    @endforeach
                  </div>
                  @endif
                  @if(filled($svc->description))
                    <div class="br-svc-desc">{{ $svc->description }}</div>
                  @endif
                  @if($svc->bundlesServices() && $svc->packageItems->isNotEmpty())
                    <div class="br-svc-incl"><b>{{ $t('يشمل','Includes') }}:</b>
                      {{ $svc->packageItems->map(fn($c) => $c->localizedName() . ($c->pivot->quantity > 1 ? ' ×'.$c->pivot->quantity : ''))->implode('، ') }}</div>
                  @endif
                  <div class="br-svc-meta">
                    @if($svc->duration_minutes)<span><x-icon name="clock" :size="14"/> {{ $svc->duration_minutes }} {{ $t('دقيقة','min') }}</span>@endif
                    @if($svc->service_type === 'consultation' && $svc->is_free)
                      <span class="br-svc-price bkf-tnum">{{ $t('مجانية','Free') }}</span>
                    @elseif($svc->price)
                      @if($hasOffer)
                        <span class="br-svc-price-was bkf-tnum">{{ number_format((float) $svc->price, 0).' '.$svcCurrency }}</span>
                        <span class="br-svc-price bkf-tnum">{{ number_format($finalPrice, 0).' '.$svcCurrency }}</span>
                        <span class="br-svc-off">{{ $offerLabel }}</span>
                      @else
                        <span class="br-svc-price bkf-tnum">{{ $svc->priceLabel().' '.$svcCurrency }}</span>
                      @endif
                    @else
                      <span class="br-svc-price bkf-tnum">{{ $t('حسب الطلب','On request') }}</span>
                    @endif
                    @if($svc->requires_approval)
                      <span><x-icon name="check-circle" :size="14"/> {{ $t('يتطلب تأكيد','Requires approval') }}</span>
                    @endif
                  </div>
                </div>
                <button type="button" class="br-svc-add bkf-btn bkf-btn-ghost bkf-btn-sm"
                        data-svc="{{ $svc->id }}" data-name="{{ $sName }}" data-price="{{ $cartPrice }}"
                        data-duration="{{ $svc->duration_minutes ?: 0 }}" data-cat="{{ $svc->service_category_id ?: '' }}"
                        onclick="brToggle(this)">
                  <x-icon name="check" :size="16" class="ic-on" style="display:none"/><span class="lbl">{{ $t('أضف','Add') }}</span>
                </button>
              </div>
            @endforeach
            </div></div>
          </div>
        @empty
          <div class="br-book-empty"><x-icon name="scissors" :size="34"/><div>{{ $t('لا توجد خدمات منشورة بعد.','No services listed yet.') }}</div></div>
        @endforelse
      </section>

      {{-- team --}}
      @if($employees->isNotEmpty())
      <section class="br-block" id="br-team">
        <h2 class="br-block-title">{{ $t('الفريق','Team') }}</h2>
        <ul class="br-team">
          @foreach($employees as $emp)
            @php
              $eName  = $isAr ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar);
              $eRole  = $emp->role?->localizedName();
              $eSpecs = $emp->serviceCategories->map(fn($c) => $c->localizedName())->filter()->values();
            @endphp
            <li class="br-member">
              <span class="br-member-av {{ $avatar($emp) ? '' : 'is-empty' }}" aria-hidden="true">
                {{-- only the member's own uploaded photo; otherwise a neutral silhouette --}}
                @if($src = $avatar($emp))<img src="{{ $src }}" alt="" width="80" height="80" loading="lazy" decoding="async">@else<x-icon name="user" :size="30"/>@endif
              </span>
              <span class="br-member-nm">{{ $eName }}</span>
              @if($eRole)<span class="br-member-rl">{{ $eRole }}</span>@endif
              @if($eSpecs->isNotEmpty())
                <span class="br-member-sp">
                  @foreach($eSpecs->take(2) as $sp)<span class="br-member-chip">{{ $sp }}</span>@endforeach
                  @if($eSpecs->count() > 2)<span class="br-member-chip is-more bkf-tnum">+{{ $eSpecs->count() - 2 }}</span>@endif
                </span>
              @endif
            </li>
          @endforeach
        </ul>
      </section>
      @endif

      {{-- location · contact · hours --}}
      <section class="br-block" id="br-location">
        <h2 class="br-block-title">{{ $t('الموقع وأوقات العمل','Location & hours') }}</h2>
        @php
          $hasGeo  = $branch->latitude && $branch->longitude;
          $addrTxt = $branch->fullAddress() ?: $branch->address;
        @endphp
        {{-- 1 · address → directions → map: one connected card --}}
        @if($addrTxt || $hasGeo)
        <div class="br-place">
          <div class="br-place-top">
            <span class="br-place-ic"><x-icon name="map-pin" :size="18"/></span>
            <div class="br-place-tx">
              <div class="br-visit-h">{{ $t('العنوان','Address') }}</div>
              <p class="br-visit-addr">{{ $addrTxt ?: ($city ?: $brName) }}</p>
            </div>
            @if($hasGeo)
              <a class="br-place-dir" href="https://www.google.com/maps/dir/?api=1&destination={{ $branch->latitude }},{{ $branch->longitude }}" target="_blank" rel="noopener">
                <x-icon name="navigation" :size="15"/><span>{{ $t('الاتجاهات','Directions') }}</span>
              </a>
            @endif
          </div>
          @if($hasGeo)
            <div id="br-map" data-lat="{{ $branch->latitude }}" data-lng="{{ $branch->longitude }}" data-name="{{ $brName }}"></div>
          @endif
        </div>
        @endif

        {{-- 2 · contact: icon buttons only — numbers stay behind the tap --}}
        @if($directContacts->isNotEmpty())
          <div class="br-visit-h br-visit-sub br-center">{{ $t('التواصل','Contact') }}</div>
          <div class="br-channels br-channels--center">
            @foreach($directContacts as $c)
              @php $cLabel = $c['kind'] === 'phone' ? $t('اتصال','Call') : $c['label']; @endphp
              <a class="br-channel br-channel--{{ $c['platform'] ?? 'phone' }}" href="{{ $c['href'] }}" @if($c['kind'] === 'social') target="_blank" rel="noopener" @endif aria-label="{{ $cLabel }}" title="{{ $cLabel }}">
                @if($c['kind'] === 'phone')<x-icon name="phone" :size="20"/>@else @include('partials.social-icon', ['platform' => $c['platform'], 'size' => 20])@endif
              </a>
            @endforeach
          </div>
        @endif

        <div class="br-visit-col">
            <div class="br-visit-h br-visit-sub">{{ $t('أوقات العمل','Opening hours') }}</div>
            @if($branch->workingHours->isNotEmpty())
            <div class="br-hours">
              @php
                $fmt = function ($v) use ($branch) {
                    if (!$v) return '';
                    return $branch->formatTime($v instanceof \DateTimeInterface ? $v : \Carbon\Carbon::parse($v));
                };
                // Start the week where the branch starts it (Branch Settings).
                $weekOrder = collect(range(0, 6))->map(fn($i) => ($i + (int) ($branch->first_day_of_week ?? 0)) % 7);
              @endphp
              @foreach($weekOrder as $d)
                @php
                  $shifts = $whByDay->get($d, collect())->where('is_open', true)->sortBy('shift_number');
                @endphp
                <div class="row {{ $d === $todayDow ? 'today' : '' }}">
                  <span>{{ $dayNames[$d] }}{{ $d === $todayDow ? ' · '.$t('اليوم','Today') : '' }}</span>
                  <span class="bkf-tnum" dir="ltr">
                    @if($shifts->isEmpty())<span class="closed">{{ $t('مغلق','Closed') }}</span>
                    @else{{ $shifts->map(fn($w) => $fmt($w->open_time).' – '.$fmt($w->close_time))->implode(', ') }}@endif
                  </span>
                </div>
              @endforeach
            </div>
            @else
              <p class="br-desc">{{ $t('لم يُحدَّد أوقات العمل بعد. تواصل مع المكان لمعرفة المواعيد.','Opening hours haven’t been added yet — contact the venue to check.') }}</p>
            @endif
        </div>
      </section>

      {{-- work gallery — results photos (type "work"), separate from the venue photos above --}}
      @if($workImages->isNotEmpty())
      <section class="br-block" id="br-work">
        <div class="br-block-head">
          <h2 class="br-block-title">{{ $t('معرض الأعمال','Our work') }}</h2>
          <span class="br-block-count bkf-tnum">{{ $workImages->count() }} {{ $t('صورة','photos') }}</span>
        </div>
        <div class="br-work" id="br-work-grid">
          @foreach($workData as $i => $w)
            <button type="button" class="br-work-tile {{ $i >= 9 ? 'br-hidden' : '' }}" data-work="{{ $i }}"
                    aria-label="{{ $t('عرض الصورة '.($i + 1).' من '.$workData->count(), 'View photo '.($i + 1).' of '.$workData->count()) }}">
              <img src="{{ $w['grid'] }}" alt="" loading="lazy" decoding="async"
                   @if($w['w'] && $w['h']) width="{{ min(640, $w['w']) }}" height="{{ (int) round(min(640, $w['w']) * $w['h'] / $w['w']) }}" @endif>
            </button>
          @endforeach
        </div>
        @if($workData->count() > 9)
          <button type="button" class="bkf-btn bkf-btn-ghost bkf-btn-sm br-work-more" id="br-work-more">
            {{ $t('عرض كل الصور ('.$workData->count().')', 'Show all '.$workData->count().' photos') }}
          </button>
        @endif
      </section>
      @endif

      {{-- the branch's social profiles — brand icons only --}}
      @if($socialContacts->isNotEmpty())
      <section class="br-block" id="br-social">
        <h2 class="br-block-title">{{ $t('تابعنا','Follow us') }}</h2>
        <div class="br-channels">
          @foreach($socialContacts as $c)
            <a class="br-channel br-channel--{{ $c['platform'] }}" href="{{ $c['href'] }}" target="_blank" rel="noopener" aria-label="{{ $c['label'] }}" title="{{ $c['label'] }}">
              @include('partials.social-icon', ['platform' => $c['platform'], 'size' => 20])
            </a>
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


      {{-- about --}}
      @if($branch->description_en || $branch->description_ar)
      <section class="br-block" id="br-about">
        <h2 class="br-block-title">{{ $t('نبذة عن '.$brName, 'About '.$brName) }}</h2>
        <p class="br-desc">{{ $isAr ? ($branch->description_ar ?: $branch->description_en) : ($branch->description_en ?: $branch->description_ar) }}</p>
      </section>
      @endif

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
<div class="br-lb" id="br-lb" role="dialog" aria-modal="true" aria-label="{{ $t('عارض الصور','Photo viewer') }}">
  <button class="br-lb-x" onclick="brLb.close()" aria-label="{{ $t('إغلاق','Close') }}"><x-icon name="x" :size="22"/></button>
  <button class="br-lb-nav prev" onclick="brLb.step(-1)" aria-label="{{ $t('السابقة','Previous') }}"><x-icon name="chevron-right" :size="22" style="transform:scaleX(-1)"/></button>
  <img id="br-lb-img" alt="">
  <button class="br-lb-nav next" onclick="brLb.step(1)" aria-label="{{ $t('التالية','Next') }}"><x-icon name="chevron-right" :size="22"/></button>
  <span class="br-lb-count bkf-tnum" id="br-lb-count" dir="ltr" aria-live="polite"></span>
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
  var WORK = @json($workData);
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

    // mouse drag-to-scroll (touch & trackpads already scroll natively)
    var down = false, dragged = false, x0 = 0, s0 = 0;
    tabsBar.addEventListener('pointerdown', function(e){
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; dragged = false; x0 = e.clientX; s0 = tabsBar.scrollLeft;
    });
    tabsBar.addEventListener('pointermove', function(e){
      if (!down) return;
      var dx = e.clientX - x0;
      if (!dragged && Math.abs(dx) > 5){ dragged = true; tabsBar.classList.add('is-dragging'); try { tabsBar.setPointerCapture(e.pointerId); } catch (x) {} }
      if (dragged){ tabsBar.scrollLeft = s0 - dx; e.preventDefault(); }
    });
    function endDrag(){ if (!down) return; down = false; tabsBar.classList.remove('is-dragging'); }
    tabsBar.addEventListener('pointerup', endDrag);
    tabsBar.addEventListener('pointercancel', endDrag);
    // a drag must not also "click" the chip it started on
    tabsBar.addEventListener('click', function(e){ if (dragged){ e.stopPropagation(); e.preventDefault(); dragged = false; } }, true);
    tabsBar.addEventListener('dragstart', function(e){ e.preventDefault(); });

  })();

  /* ── services: fold / unfold one category (▼ open · ▲ collapsed) ── */
  document.querySelectorAll('button.br-svc-cat-h').forEach(function(h){
    h.addEventListener('click', function(){
      var open = h.getAttribute('aria-expanded') !== 'false';
      h.setAttribute('aria-expanded', open ? 'false' : 'true');
      h.closest('.br-svc-cat').classList.toggle('is-collapsed', open);
    });
  });

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

  /* ── lightbox: one viewer, two photo sets (venue hero · work gallery) ── */
  var LB_SETS = { hero: GALLERY, work: WORK.map(function (w) { return w.large; }) };
  var lbSet = 'hero', lbIdx = 0, lbReturn = null;
  function lbShow(){
    var list = LB_SETS[lbSet] || [];
    el('br-lb-img').src = list[lbIdx] || '';
    el('br-lb-count').textContent = list.length > 1 ? (lbIdx + 1) + ' / ' + list.length : '';
    var multi = list.length > 1;
    document.querySelectorAll('.br-lb-nav').forEach(function (b) { b.style.display = multi ? '' : 'none'; });
  }
  window.brLb = {
    open:function(i, set){ lbSet = set || 'hero'; lbIdx = i; lbReturn = document.activeElement; lbShow(); el('br-lb').classList.add('open'); document.body.style.overflow='hidden'; el('br-lb').querySelector('.br-lb-x').focus(); },
    close:function(){ el('br-lb').classList.remove('open'); document.body.style.overflow=''; if (lbReturn && lbReturn.focus) lbReturn.focus(); },
    step:function(d){ var n = (LB_SETS[lbSet] || []).length; if (!n) return; lbIdx=(lbIdx+d+n)%n; lbShow(); }
  };
  document.querySelectorAll('#br-gallery [data-lb]').forEach(function(g){ g.addEventListener('click', function(){ brLb.open(+g.dataset.lb, 'hero'); }); });
  document.querySelectorAll('[data-work]').forEach(function(g){ g.addEventListener('click', function(){ brLb.open(+g.dataset.work, 'work'); }); });
  document.addEventListener('keydown', function(e){ if(!el('br-lb').classList.contains('open'))return; if(e.key==='Escape')brLb.close(); if(e.key==='ArrowRight')brLb.step(AR?-1:1); if(e.key==='ArrowLeft')brLb.step(AR?1:-1); });
  // swipe on touch screens
  (function(){ var x0 = null, lb = el('br-lb');
    lb.addEventListener('touchstart', function(e){ x0 = e.touches[0].clientX; }, { passive:true });
    lb.addEventListener('touchend', function(e){ if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) brLb.step((dx < 0) !== AR ? 1 : -1); }, { passive:true });
  })();

  /* ── work gallery: reveal the rest (images stay lazy-loaded) ── */
  (function(){ var more = el('br-work-more'); if (!more) return;
    more.addEventListener('click', function(){ document.querySelectorAll('#br-work-grid .br-hidden').forEach(function (t) { t.classList.remove('br-hidden'); }); more.remove(); });
  })();

  /* ── leaflet single-marker map ── */
  function initMap(){
    var m = el('br-map'); if(!m) return;
    if(!window.L){ window.addEventListener('load', initMap, {once:true}); return; }
    var lat=+m.dataset.lat, lng=+m.dataset.lng;
    var map = L.map('br-map',{scrollWheelZoom:false,zoomControl:true}).setView([lat,lng],15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
    L.marker([lat,lng]).addTo(map).bindPopup('<b>'+m.dataset.name+'</b>').openPopup();
    setTimeout(function(){ map.invalidateSize(); }, 200);
  }
  initMap();
  window.addEventListener('resize', function(){ render(); });
})();
</script>
</x-slot:scripts>
</x-front.layout>
