@php
    /* Self-contained card — used by index4 grid AND the load-more AJAX endpoint.
       Expects: $branch, $i (index for fallback image + stagger delay). */
    $isAr = app()->getLocale() === 'ar';
    $fallbacks = [
        asset('frontend/img/stock/1522337360788-8b13dee7a37e-w700.jpg'),
        asset('frontend/img/stock/1570172619644-dfd03ed5d881-w700.jpg'),
        asset('frontend/img/stock/1487412947147-5cebf100ffc2-w700.jpg'),
        asset('frontend/img/stock/1580618672591-eb180b1a973f-w700.jpg'),
        asset('frontend/img/stock/1516975080664-ed2fc6a32937-w700.jpg'),
        asset('frontend/img/stock/1600948836101-f9ffda59d250-w700.jpg'),
    ];
    $img     = $branch->images->first();
    $imgUrl  = $img ? asset('storage/'.$img->path) : ($branch->company && $branch->company->logo ? asset('storage/'.$branch->company->logo) : null);
    $company = $branch->company;
    $bname   = $isAr ? ($branch->name_ar ?? $branch->name_en) : ($branch->name_en ?? $branch->name_ar);
    $cat     = $company && $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : '';
    $reviews = $branch->reviews;
    $avg     = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
    $finalImg = $imgUrl ?: $fallbacks[$i % count($fallbacks)];
@endphp
<div class="col-md-6 col-lg-4">
    <a href="{{ route('front.branch', $branch) }}" class="bk-company-card d-block text-decoration-none appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="{{ ($i % 3) * 100 }}">
        <div class="bk-cc-img">
            <img src="{{ $finalImg }}" alt="{{ $bname }}" loading="lazy">
            @if($cat)<div class="bk-cc-badge">{{ $cat }}</div>@endif
            @if($avg)<div class="bk-cc-rating"><i class="fas fa-star"></i>{{ $avg }}</div>@endif
        </div>
        <div class="bk-cc-body">
            <div class="bk-cc-name">{{ $bname }}</div>
            <div class="bk-cc-location"><i class="fas fa-map-marker-alt"></i>{{ Str::limit($branch->address ?? ($isAr ? 'الموقع' : 'Location'), 38) }}</div>
            <div class="bk-cc-chips">
                @foreach($branch->services->take(3) as $svc)
                    <span class="bk-cc-chip">{{ Str::limit($isAr ? ($svc->name_ar ?? $svc->name_en) : ($svc->name_en ?? $svc->name_ar), 18) }}</span>
                @endforeach
            </div>
            <div class="bk-cc-book mt-2">
                <i class="fas fa-calendar-check"></i>
                {{ $isAr ? 'احجز الآن' : 'Book Now' }}
            </div>
        </div>
    </a>
</div>
