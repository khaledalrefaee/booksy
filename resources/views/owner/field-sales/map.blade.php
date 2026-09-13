@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')
@push('owner-styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" crossorigin="">
<style>
    #fs-map { height:min(70vh,640px); width:100%; border-radius:16px; z-index:1; }
    .fs-pop-title { font-weight:700; margin-bottom:2px; }
    .fs-pop-meta { font-size:.8rem; color:#666; }
    .fs-pop a { color:var(--bk-accent); font-weight:600; }
</style>
@endpush

<div class="page-content bm-wrap">
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow"><a href="{{ route('owner.field-sales.index') }}">{{ __('Field sales') }}</a><span aria-hidden="true">·</span> {{ __('Map') }}</div>
            <h1 class="bm-title">{{ __('Visits map') }}</h1>
            <p class="bm-subtitle">{{ __('Where your reps logged visits. Red pins were flagged for review.') }}</p>
        </div>
        <div class="bm-head-actions"><a href="{{ route('owner.field-sales.index') }}" class="bm-btn bm-btn-ghost"><i data-feather="list"></i>{{ __('List view') }}</a></div>
    </header>

    <div class="bm-card bm-reveal" style="padding:12px;">
        @if($points->isEmpty())
            <div class="bm-empty"><span class="bm-empty-ic"><i data-feather="map-pin"></i></span><p class="bm-empty-title">{{ __('No located visits') }}</p><p class="bm-empty-sub">{{ __('Visits with GPS will appear on the map here.') }}</p></div>
        @else
            <div id="fs-map"></div>
        @endif
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" crossorigin=""></script>
<script>
(function () {
    var points = @json($points);
    if (!points.length || !window.L) return;
    var map = L.map('fs-map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
    var group = [];
    points.forEach(function (p) {
        var color = p.flagged ? '#B23A48' : '#4B5D34';
        var m = L.circleMarker([p.lat, p.lng], { radius: 8, color: '#fff', weight: 2, fillColor: color, fillOpacity: .9 }).addTo(map);
        m.bindPopup('<div class="fs-pop"><div class="fs-pop-title">' + (p.name || '') + '</div>' +
            '<div class="fs-pop-meta">' + [p.rep, p.area, p.when].filter(Boolean).join(' · ') + '</div>' +
            '<a href="' + p.url + '">{{ __('Open visit') }}</a></div>');
        group.push([p.lat, p.lng]);
    });
    map.fitBounds(group, { padding: [40, 40], maxZoom: 15 });
})();
</script>
@endpush
@endsection
