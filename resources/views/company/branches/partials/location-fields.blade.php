@php
    $govUrl  = route('company.locations.governorates');
    $areaUrl = route('company.locations.areas');
    $isAr    = app()->getLocale() === 'ar';
    $lat = old('latitude', $latitude ?? '33.5104');
    $lng = old('longitude', $longitude ?? '36.2783');
@endphp

{{-- ── Country / Governorate / Area ─────────────────────────────── --}}
<div class="col-md-4">
    <label class="form-label fw-semibold" for="loc_country_id">
        {{ __('Country') }}
    </label>
    <select id="loc_country_id" name="country_id"
            class="form-select rounded-3 @error('country_id') is-invalid @enderror">
        <option value="">— {{ __('Select country') }} —</option>
        @foreach($countries as $c)
            <option value="{{ $c->id }}"
                    data-name="{{ $isAr ? ($c->name_ar ?: $c->name_en) : ($c->name_en ?: $c->name_ar) }}"
                    {{ (string)($selCountryId ?? '') === (string)$c->id ? 'selected' : '' }}>
                {{ $isAr ? ($c->name_ar ?: $c->name_en) : ($c->name_en ?: $c->name_ar) }}
            </option>
        @endforeach
    </select>
    @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="col-md-4">
    <label class="form-label fw-semibold" for="loc_governorate_id">
        {{ __('Governorate') }}
    </label>
    <select id="loc_governorate_id" name="governorate_id"
            class="form-select rounded-3 @error('governorate_id') is-invalid @enderror"
            {{ empty($selCountryId) ? 'disabled' : '' }}>
        <option value="">— {{ __('Select governorate') }} —</option>
        @foreach($governorates as $g)
            <option value="{{ $g->id }}"
                    data-name="{{ $isAr ? ($g->name_ar ?: $g->name_en) : ($g->name_en ?: $g->name_ar) }}"
                    {{ (string)($selGovernorateId ?? '') === (string)$g->id ? 'selected' : '' }}>
                {{ $isAr ? ($g->name_ar ?: $g->name_en) : ($g->name_en ?: $g->name_ar) }}
            </option>
        @endforeach
    </select>
    @error('governorate_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="col-md-4">
    <label class="form-label fw-semibold" for="loc_area_id">
        {{ __('Area') }}
    </label>
    <select id="loc_area_id" name="area_id"
            class="form-select rounded-3 @error('area_id') is-invalid @enderror"
            {{ empty($selGovernorateId) ? 'disabled' : '' }}>
        <option value="">— {{ __('Select area') }} —</option>
        @foreach($areas as $a)
            <option value="{{ $a->id }}"
                    data-name="{{ $isAr ? ($a->name_ar ?: $a->name_en) : ($a->name_en ?: $a->name_ar) }}"
                    {{ (string)($selAreaId ?? '') === (string)$a->id ? 'selected' : '' }}>
                {{ $isAr ? ($a->name_ar ?: $a->name_en) : ($a->name_en ?: $a->name_ar) }}
            </option>
        @endforeach
    </select>
    @error('area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- ── Street / Address ──────────────────────────────────────────── --}}
<div class="col-12">
    <label class="form-label fw-semibold" for="address">
        {{ __('Street / Address') }}
        <span class="text-muted fw-normal small ms-1">({{ __('building, street name…') }})</span>
    </label>
    <input type="text" id="address" name="address"
           value="{{ $selAddress ?? '' }}"
           class="form-control rounded-3 @error('address') is-invalid @enderror"
           placeholder="{{ __('e.g. Al-Thawra Street, Building 5') }}"
           maxlength="500">
    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- ── Map ──────────────────────────────────────────────────────── --}}
<div class="col-12 mt-2">
    <label class="form-label fw-semibold">{{ __('Location on map') }}</label>
    <p class="text-muted small mb-2">{{ __('Select country/governorate/area above to zoom in automatically, or search and drag the marker.') }}</p>

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
            role="listbox" aria-label="{{ __('Search suggestions') }}"></ul>
    </div>

    <div id="branch-map" class="rounded-3 border mb-2" style="height:320px;z-index:1;"></div>

    <input type="hidden" name="latitude"  id="branch-latitude"  value="{{ $lat }}">
    <input type="hidden" name="longitude" id="branch-longitude" value="{{ $lng }}">
</div>

@push('company-styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
#branch-map-search-results .list-group-item { cursor: pointer; }
#branch-map-search-results .list-group-item:hover,
#branch-map-search-results .list-group-item:focus {
    background-color: rgba(var(--bs-primary-rgb),.08);
    outline: none;
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Selects ──────────────────────────────────────────────────────
    var selCountry  = document.getElementById('loc_country_id');
    var selGov      = document.getElementById('loc_governorate_id');
    var selArea     = document.getElementById('loc_area_id');
    var govUrl      = @json($govUrl);
    var areaUrl     = @json($areaUrl);
    var uiLang      = document.documentElement.lang === 'ar' ? 'ar,en' : 'en,ar';

    function selectedLabel(sel) {
        var opt = sel.options[sel.selectedIndex];
        return (opt && opt.value) ? (opt.dataset.name || opt.text) : '';
    }

    function loadOptions(url, params, targetSel, keepVal) {
        targetSel.disabled = true;
        targetSel.innerHTML = '<option value="">{{ __("Loading…") }}</option>';
        fetch(url + '?' + new URLSearchParams(params), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (items) {
                var ph = targetSel === selGov ? '— {{ __("Select governorate") }} —' : '— {{ __("Select area") }} —';
                targetSel.innerHTML = '<option value="">' + ph + '</option>';
                items.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.label;
                    opt.dataset.name = item.label;
                    if (String(item.id) === String(keepVal)) opt.selected = true;
                    targetSel.appendChild(opt);
                });
                targetSel.disabled = items.length === 0;
            });
    }

    selCountry.addEventListener('change', function () {
        selGov.innerHTML  = '<option value="">— {{ __("Select governorate") }} —</option>';
        selGov.disabled   = true;
        selArea.innerHTML = '<option value="">— {{ __("Select area") }} —</option>';
        selArea.disabled  = true;
        if (this.value) {
            loadOptions(govUrl, { country_id: this.value }, selGov, null);
            geocodeAndFly(selectedLabel(this));
        }
    });

    selGov.addEventListener('change', function () {
        selArea.innerHTML = '<option value="">— {{ __("Select area") }} —</option>';
        selArea.disabled  = true;
        if (this.value) {
            loadOptions(areaUrl, { governorate_id: this.value }, selArea, null);
            var q = [selectedLabel(this), selectedLabel(selCountry)].filter(Boolean).join(', ');
            geocodeAndFly(q);
        }
    });

    selArea.addEventListener('change', function () {
        if (this.value) {
            var q = [selectedLabel(this), selectedLabel(selGov), selectedLabel(selCountry)].filter(Boolean).join(', ');
            geocodeAndFly(q);
        }
    });

    // ── Map ──────────────────────────────────────────────────────────
    var mapEl       = document.getElementById('branch-map');
    var latInput    = document.getElementById('branch-latitude');
    var lngInput    = document.getElementById('branch-longitude');
    var searchInput = document.getElementById('branch-map-search');
    var searchBtn   = document.getElementById('branch-map-search-btn');
    var statusEl    = document.getElementById('branch-map-search-status');
    var resultsEl   = document.getElementById('branch-map-search-results');

    if (!mapEl || typeof L === 'undefined') return;

    var startLat = parseFloat(latInput.value)  || 33.5104;
    var startLng = parseFloat(lngInput.value) || 36.2783;

    var map    = L.map('branch-map').setView([startLat, startLng], 13);
    var marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

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
        var lat = parseFloat(item.lat), lng = parseFloat(item.lon);
        if (isNaN(lat) || isNaN(lng)) return;
        if (item.boundingbox) {
            var bb = item.boundingbox;
            map.fitBounds([[+bb[0], +bb[2]], [+bb[1], +bb[3]]]);
            marker.setLatLng([lat, lng]);
            latInput.value = lat.toFixed(8);
            lngInput.value = lng.toFixed(8);
        } else {
            setCoords(lat, lng, (item.type === 'administrative' || item.class === 'boundary') ? 12 : 16);
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
        if      (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(+1); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); moveFocus(-1); }
        else if (e.key === 'Enter')     { e.preventDefault(); runSearch(); }
        else if (e.key === 'Escape')    { hideResults(); }
    });
    resultsEl.addEventListener('keydown', function (e) {
        if      (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(+1); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); activeIdx <= 0 ? (searchInput.focus(), activeIdx = -1) : moveFocus(-1); }
        else if (e.key === 'Escape')    { hideResults(); searchInput.focus(); }
    });

    var debounceTimer, lastQuery = '', busy = false, lastCallAt = 0;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = this.value.trim();
        if (q.length < 2) { hideResults(); setStatus('', false); return; }
        if (q === lastQuery) return;
        debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 400);
    });

    searchBtn && searchBtn.addEventListener('click', function () { clearTimeout(debounceTimer); runSearch(); });

    function nominatim(q, callback) {
        var now = Date.now();
        if (now - lastCallAt < 1050) {
            setTimeout(function () { nominatim(q, callback); }, 1100 - (now - lastCallAt));
            return;
        }
        lastCallAt = now;
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=6&addressdetails=1&q=' + encodeURIComponent(q),
            { headers: { 'Accept': 'application/json', 'Accept-Language': uiLang } })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(callback)
            .catch(function () {});
    }

    function fetchSuggestions(q) {
        if (busy) return;
        lastQuery = q; busy = true;
        setStatus('{{ __("Searching…") }}', false);
        nominatim(q, function (data) {
            busy = false;
            if (!data || !data.length) {
                hideResults();
                setStatus('{{ __("No results found. Try another name or area.") }}', true);
                return;
            }
            showResults(data);
            setStatus('{{ __("Select a result from the list.") }}', false);
        });
    }

    function runSearch() {
        var items = resultsEl.querySelectorAll('.list-group-item');
        if (!resultsEl.classList.contains('d-none') && items.length) { items[0].click(); return; }
        var q = searchInput.value.trim();
        if (q.length < 2) { setStatus('{{ __("Enter at least 2 characters to search.") }}', true); return; }
        fetchSuggestions(q);
    }

    // ── Geocode from select and fly ───────────────────────────────────
    function geocodeAndFly(q) {
        if (!q) return;
        nominatim(q, function (data) {
            if (!data || !data.length) return;
            var item = data[0];
            var lat = parseFloat(item.lat), lng = parseFloat(item.lon);
            if (isNaN(lat) || isNaN(lng)) return;
            if (item.boundingbox) {
                var bb = item.boundingbox;
                map.fitBounds([[+bb[0], +bb[2]], [+bb[1], +bb[3]]], { maxZoom: 14 });
            } else {
                map.setView([lat, lng], 12);
            }
            // Don't move the pin — user hasn't confirmed the exact spot yet
        });
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
