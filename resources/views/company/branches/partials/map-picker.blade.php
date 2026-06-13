@php
    $lat = old('latitude', $latitude ?? '33.5104');
    $lng = old('longitude', $longitude ?? '36.2783');
@endphp

<label class="form-label fw-semibold">{{ __('Location on map') }}</label>
<p class="text-muted small mb-2">{{ __('Search by place or area name, or click the map and drag the marker.') }}</p>

<div class="mb-2">
    <label class="visually-hidden" for="branch-map-search">{{ __('Search location') }}</label>
    <div class="input-group">
        <input type="search" id="branch-map-search" class="form-control rounded-3 rounded-end-0"
            placeholder="{{ __('e.g. Damascus, Mazzeh, street name…') }}" autocomplete="off">
        <button type="button" id="branch-map-search-btn" class="btn btn-primary rounded-3 rounded-start-0 px-3">
            <i data-feather="search" style="width:16px;height:16px;"></i>
            <span class="ms-1 d-none d-sm-inline">{{ __('Search') }}</span>
        </button>
    </div>
    <div id="branch-map-search-status" class="small text-muted mt-1" aria-live="polite"></div>
    <ul id="branch-map-search-results"
        class="list-group list-group-flush rounded-3 border mt-1 shadow-sm d-none"
        role="listbox" aria-label="{{ __('Search suggestions') }}">
    </ul>
</div>

<div id="branch-map" class="rounded-3 border mb-2" style="height: 320px; z-index: 1;"></div>

<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small text-muted" for="branch-latitude">{{ __('Latitude') }}</label>
        <input type="text" name="latitude" id="branch-latitude" value="{{ $lat }}" readonly
            class="form-control form-control-sm rounded-3 bg-light @error('latitude') is-invalid @enderror">
        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label small text-muted" for="branch-longitude">{{ __('Longitude') }}</label>
        <input type="text" name="longitude" id="branch-longitude" value="{{ $lng }}" readonly
            class="form-control form-control-sm rounded-3 bg-light @error('longitude') is-invalid @enderror">
        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('company-styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    #branch-map-search-results .list-group-item { cursor: pointer; }
    #branch-map-search-results .list-group-item:hover,
    #branch-map-search-results .list-group-item:focus {
        background-color: rgba(var(--bs-primary-rgb), 0.08);
        outline: none;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var mapEl       = document.getElementById('branch-map');
    var latInput    = document.getElementById('branch-latitude');
    var lngInput    = document.getElementById('branch-longitude');
    var searchInput = document.getElementById('branch-map-search');
    var searchBtn   = document.getElementById('branch-map-search-btn');
    var statusEl    = document.getElementById('branch-map-search-status');
    var resultsEl   = document.getElementById('branch-map-search-results');

    if (!mapEl || !latInput || !lngInput || typeof L === 'undefined') return;

    var startLat = parseFloat(latInput.value)  || 33.5104;
    var startLng = parseFloat(lngInput.value) || 36.2783;
    var uiLang   = document.documentElement.lang === 'ar' ? 'ar,en' : 'en,ar';

    var map = L.map('branch-map').setView([startLat, startLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

    function setCoords(lat, lng, zoom) {
        latInput.value = lat.toFixed(8);
        lngInput.value = lng.toFixed(8);
        marker.setLatLng([lat, lng]);
        zoom ? map.setView([lat, lng], zoom) : map.panTo([lat, lng]);
    }

    function setStatus(msg, isErr) {
        statusEl.textContent = msg || '';
        statusEl.classList.toggle('text-danger', !!isErr);
        statusEl.classList.toggle('text-muted',  !isErr);
    }

    function hideResults() {
        resultsEl.innerHTML = '';
        resultsEl.classList.add('d-none');
        activeIdx = -1;
    }

    function showResults(items) {
        resultsEl.innerHTML = '';
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action small py-2';
            li.setAttribute('role', 'option');
            li.setAttribute('tabindex', '0');
            li.textContent = item.display_name;
            li.addEventListener('click',   function () { pick(item); });
            li.addEventListener('keydown', function (e) { if (e.key === 'Enter') pick(item); });
            resultsEl.appendChild(li);
        });
        resultsEl.classList.remove('d-none');
    }

    function pick(item) {
        var lat = parseFloat(item.lat);
        var lng = parseFloat(item.lon);
        if (isNaN(lat) || isNaN(lng)) return;
        if (item.boundingbox) {
            var bb = item.boundingbox;
            map.fitBounds([[+bb[0], +bb[2]], [+bb[1], +bb[3]]]);
            marker.setLatLng([lat, lng]);
            latInput.value = lat.toFixed(8);
            lngInput.value = lng.toFixed(8);
        } else {
            var zoom = (item.type === 'administrative' || item.class === 'boundary') ? 12 : 16;
            setCoords(lat, lng, zoom);
        }
        setStatus(item.display_name, false);
        searchInput.value = item.display_name;
        hideResults();
        searchInput.focus();
    }

    var activeIdx = -1;
    function moveFocus(dir) {
        var items = resultsEl.querySelectorAll('.list-group-item');
        if (!items.length) return;
        activeIdx = Math.max(0, Math.min(items.length - 1, activeIdx + dir));
        items[activeIdx].focus();
    }

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown')  { e.preventDefault(); moveFocus(+1); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); moveFocus(-1); }
        else if (e.key === 'Enter')     { e.preventDefault(); runSearch(); }
        else if (e.key === 'Escape')    { hideResults(); }
    });

    resultsEl.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(+1); }
        if (e.key === 'ArrowUp')   {
            e.preventDefault();
            if (activeIdx <= 0) { searchInput.focus(); activeIdx = -1; }
            else moveFocus(-1);
        }
        if (e.key === 'Escape') { hideResults(); searchInput.focus(); }
    });

    var debounceTimer = null;
    var lastQuery = '';
    var busy = false;
    var lastCallAt = 0;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = searchInput.value.trim();
        if (q.length < 2) { hideResults(); setStatus('', false); return; }
        if (q === lastQuery) return;
        debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 400);
    });

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            clearTimeout(debounceTimer);
            runSearch();
        });
    }

    function fetchSuggestions(q) {
        if (busy) return;
        var now = Date.now();
        if (now - lastCallAt < 1050) {
            debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 1100 - (now - lastCallAt));
            return;
        }
        lastQuery  = q;
        lastCallAt = now;
        busy = true;
        setStatus('{{ __('Searching…') }}', false);

        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=6&addressdetails=1&bounded=0&q=' + encodeURIComponent(q),
            { headers: { 'Accept': 'application/json', 'Accept-Language': uiLang } })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function (data) {
                if (!data || !data.length) {
                    hideResults();
                    setStatus('{{ __('No results found. Try another name or area.') }}', true);
                    return;
                }
                showResults(data);
                setStatus('{{ __('Select a result from the list.') }}', false);
            })
            .catch(function () {
                setStatus('{{ __('Search failed. Check your connection and try again.') }}', true);
            })
            .finally(function () {
                busy = false;
                if (typeof window.feather !== 'undefined') window.feather.replace();
            });
    }

    function runSearch() {
        var items = resultsEl.querySelectorAll('.list-group-item');
        if (!resultsEl.classList.contains('d-none') && items.length) {
            items[0].click();
            return;
        }
        var q = searchInput.value.trim();
        if (q.length < 2) { setStatus('{{ __('Enter at least 2 characters to search.') }}', true); return; }
        fetchSuggestions(q);
    }

    marker.on('dragend', function () {
        var p = marker.getLatLng();
        setCoords(p.lat, p.lng);
        hideResults();
    });

    map.on('click', function (e) {
        setCoords(e.latlng.lat, e.latlng.lng);
        hideResults();
    });

    document.addEventListener('click', function (e) {
        if (resultsEl.classList.contains('d-none')) return;
        if (e.target === searchInput || resultsEl.contains(e.target) || e.target === searchBtn) return;
        hideResults();
    });

    setTimeout(function () { map.invalidateSize(); }, 300);
    if (typeof window.feather !== 'undefined') window.feather.replace();
});
</script>
@endpush
