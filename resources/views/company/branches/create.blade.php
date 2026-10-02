@extends('company.dashboard')

@section('content')
<div class="page-content">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('company.branches.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">←</a>
        <div>
            <h4 class="fw-bold mb-0">{{ __('Add branch') }}</h4>
            <small class="text-muted">{{ __('Fill in the details below') }}</small>
        </div>
    </div>

    @include('company.partials.flash')

    <form method="POST" action="{{ route('company.branches.store') }}" novalidate>
        @csrf

        {{-- Tabs --}}
        <ul class="nav nav-pills gap-1 mb-4 flex-wrap" id="branchTabs">
            <li class="nav-item">
                <button class="nav-link active px-4" type="button" data-tab="tab-info">📝 {{ __('Basic Info') }}</button>
            </li>
            <li class="nav-item">
                <button class="nav-link px-4" type="button" data-tab="tab-location">📍 {{ __('Location') }}</button>
            </li>
            <li class="nav-item">
                <button class="nav-link px-4" type="button" data-tab="tab-settings">⚙️ {{ __('Settings') }}</button>
            </li>
            <li class="nav-item">
                <button class="nav-link px-4" type="button" data-tab="tab-social">🔗 {{ __('Social') }}</button>
            </li>
            <li class="nav-item">
            </li>
        </ul>

        {{-- Tab: Basic Info --}}
        <div class="tab-pane-bk" id="tab-info">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">{{ __('Branch name (EN)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name_en" value="{{ old('name_en') }}"
                            class="form-control rounded-3 @error('name_en') is-invalid @enderror" required maxlength="255">
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">{{ __('Branch name (AR)') }}</label>
                        <input type="text" name="name_ar" value="{{ old('name_ar') }}"
                            class="form-control rounded-3" dir="rtl" maxlength="255">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">{{ __('Sort order') }}</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                            class="form-control rounded-3" min="0" max="9999">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Description (EN)') }}</label>
                        <textarea name="description_en" rows="3" class="form-control rounded-3" maxlength="1000"
                            placeholder="{{ __('Brief description of this branch…') }}">{{ old('description_en') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Description (AR)') }}</label>
                        <textarea name="description_ar" rows="3" class="form-control rounded-3" dir="rtl" maxlength="1000"
                            placeholder="{{ __('وصف مختصر لهذا الفرع…') }}">{{ old('description_ar') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Location --}}
        <div class="tab-pane-bk d-none" id="tab-location">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                <div class="row g-3">
                    @include('company.branches.partials.location-fields', [
                        'countries'        => $countries,
                        'governorates'     => collect(),
                        'areas'            => collect(),
                        'selCountryId'     => old('country_id'),
                        'selGovernorateId' => old('governorate_id'),
                        'selAreaId'        => old('area_id'),
                        'selAddress'       => old('address'),
                    ])
                    <div class="col-12" id="address-preview-wrap" style="display:none;">
                        <div class="rounded-3 px-3 py-2 d-flex align-items-center gap-2"
                             style="background:rgba(75,93,52,.08);border:1px dashed rgba(75,93,52,.35);">
                            <i data-feather="map-pin" style="width:14px;height:14px;color:var(--bk-accent);flex-shrink:0;"></i>
                            <span id="address-preview-text" class="small fw-semibold" style="color:var(--bk-accent);"></span>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    @include('company.branches.partials.phone-fields')
                </div>
            </div>
        </div>

        {{-- Tab: Settings --}}
        <div class="tab-pane-bk d-none" id="tab-settings">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">

                <h6 class="fw-semibold text-muted text-uppercase small mb-3">{{ __('Branch status') }}</h6>
                <div class="d-flex gap-3 flex-wrap mb-4">
                    @foreach([
                        'active'      => ['check-circle', 'success',   __('Active'),      __('Open & visible to customers')],
                        'inactive'    => ['x-circle',    'secondary',  __('Inactive'),    __('Hidden from public')],
                        'maintenance' => ['tool',         'warning',   __('Maintenance'), __('Visible but booking disabled')],
                    ] as $st => [$icon, $color, $lbl, $hint])
                    <label class="d-flex align-items-start gap-2 p-3 rounded-3 border flex-fill
                               {{ old('status', 'active') === $st ? 'border-'.$color.' bg-'.$color.' bg-opacity-10' : '' }}"
                           style="cursor:pointer;min-width:145px;" id="status-lbl-{{ $st }}">
                        <input type="radio" name="status" value="{{ $st }}" class="form-check-input mt-0 flex-shrink-0"
                               {{ old('status', 'active') === $st ? 'checked' : '' }} onchange="highlightStatus()">
                        <div>
                            <span class="fw-semibold text-{{ $color }} d-flex align-items-center gap-1">
                                <i data-feather="{{ $icon }}" style="width:13px;height:13px;"></i> {{ $lbl }}
                            </span>
                            <small class="text-muted">{{ $hint }}</small>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_head_office" id="is_head_office"
                           value="1" @checked(old('is_head_office'))>
                    <label class="form-check-label" for="is_head_office">{{ __('Mark as head office') }}</label>
                </div>
            </div>
        </div>

        {{-- Tab: Social --}}
        <div class="tab-pane-bk d-none" id="tab-social">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                <h6 class="fw-semibold text-muted text-uppercase small mb-3">{{ __('Social Media Links') }}</h6>
                <div class="border rounded-3">
                    @include('partials.social-links-form', [
                        'savedLinks'       => collect(),
                        'inputPrefix'      => 'social_links',
                        'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram', 'linkedin'],
                    ])
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="d-flex justify-content-between gap-2 pt-3">
            @php $rtl = app()->getLocale() === 'ar'; @endphp
            <a href="{{ route('company.branches.index') }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
            <div class="d-flex gap-2">
                <button type="button" id="branchPrevBtn" class="btn btn-outline-secondary rounded-pill px-4 d-none">
                    <i data-feather="{{ $rtl ? 'arrow-right' : 'arrow-left' }}" class="me-1" style="width:16px;height:16px;"></i>
                    {{ __('Previous') }}
                </button>
                <button type="button" id="branchNextBtn" class="btn btn-outline-primary rounded-pill px-4">
                    {{ __('Next') }}
                    <i data-feather="{{ $rtl ? 'arrow-left' : 'arrow-right' }}" class="ms-1" style="width:16px;height:16px;"></i>
                </button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i data-feather="save" class="me-1" style="width:16px;height:16px;"></i>
                    {{ __('Save branch') }}
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
// ── Tabs ──────────────────────────────────────────────────────────
var tabButtons = Array.prototype.slice.call(document.querySelectorAll('#branchTabs [data-tab]'));
var nextBtn    = document.getElementById('branchNextBtn');
var prevBtn    = document.getElementById('branchPrevBtn');

function currentTabIndex() {
    for (var i = 0; i < tabButtons.length; i++) {
        if (tabButtons[i].classList.contains('active')) return i;
    }
    return 0;
}

function updateTabNav() {
    var i = currentTabIndex();
    // Prev hidden on the first tab, Next hidden on the last (only Save remains).
    if (prevBtn) prevBtn.classList.toggle('d-none', i <= 0);
    if (nextBtn) nextBtn.classList.toggle('d-none', i >= tabButtons.length - 1);
}

function activateTab(btn) {
    tabButtons.forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.tab-pane-bk').forEach(function(p) { p.classList.add('d-none'); });
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.remove('d-none');
    // Leaflet renders wrong when built in a hidden tab — fix it on reveal.
    if (btn.dataset.tab === 'tab-location' && window.__branchMapShow) window.__branchMapShow();
    updateTabNav();
    if (window.feather) feather.replace();
}

function stepTab(delta) {
    var i = currentTabIndex() + delta;
    if (i >= 0 && i < tabButtons.length) {
        activateTab(tabButtons[i]);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

tabButtons.forEach(function(btn) {
    btn.addEventListener('click', function() { activateTab(btn); });
});

nextBtn && nextBtn.addEventListener('click', function() { stepTab(+1); });
prevBtn && prevBtn.addEventListener('click', function() { stepTab(-1); });

updateTabNav();

// ── Address preview ───────────────────────────────────────────────
(function() {
    var wrap = document.getElementById('address-preview-wrap');
    var preview = document.getElementById('address-preview-text');
    function updatePreview() {
        var parts = [];
        ['loc_country_id','loc_governorate_id','loc_area_id'].forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && sel.value) {
                var label = (sel.options[sel.selectedIndex].dataset.name || sel.options[sel.selectedIndex].text).trim();
                if (label) parts.push(label);
            }
        });
        var street = (document.getElementById('address') || {}).value || '';
        if (street.trim()) parts.push(street.trim());
        if (parts.length) { preview.textContent = parts.join(' ← '); wrap.style.display = ''; if (window.feather) feather.replace(); }
        else { wrap.style.display = 'none'; }
    }
    ['loc_country_id','loc_governorate_id','loc_area_id'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', updatePreview);
    });
    var addrEl = document.getElementById('address');
    if (addrEl) addrEl.addEventListener('input', updatePreview);
    updatePreview();
})();

// ── Status highlight ──────────────────────────────────────────────
const statusColors = { active: 'success', inactive: 'secondary', maintenance: 'warning' };
function highlightStatus() {
    document.querySelectorAll('[name="status"]').forEach(function(radio) {
        var lbl = document.getElementById('status-lbl-' + radio.value);
        if (!lbl) return;
        var c = statusColors[radio.value];
        if (radio.checked) { lbl.classList.add('border-' + c, 'bg-' + c, 'bg-opacity-10'); }
        else { lbl.classList.remove('border-success','border-secondary','border-warning','bg-success','bg-secondary','bg-warning','bg-opacity-10'); }
    });
}

</script>
@endpush
