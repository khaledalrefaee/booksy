@extends('company.dashboard')

@section('content')
<div class="page-content">

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Edit branch') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('company.dashboard') }}">{{ __('Dashboard') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">

                    <form method="POST" action="{{ route('company.branches.update', $branch) }}" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- ── Basic info ──────────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                            {{ __('Branch information') }}
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="name_en">
                                    {{ __('Branch name (EN)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name_en" name="name_en"
                                       value="{{ old('name_en', $branch->name_en) }}"
                                       class="form-control rounded-3 @error('name_en') is-invalid @enderror"
                                       required maxlength="255">
                                @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="name_ar">
                                    {{ __('Branch name (AR)') }}
                                </label>
                                <input type="text" id="name_ar" name="name_ar"
                                       value="{{ old('name_ar', $branch->name_ar) }}"
                                       class="form-control rounded-3 @error('name_ar') is-invalid @enderror"
                                       dir="rtl" maxlength="255">
                                @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold" for="sort_order">
                                    {{ __('Sort order') }}
                                </label>
                                <input type="number" id="sort_order" name="sort_order"
                                       value="{{ old('sort_order', $branch->sort_order) }}"
                                       class="form-control rounded-3 @error('sort_order') is-invalid @enderror"
                                       min="0" max="9999">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="description_en">
                                    {{ __('Description (EN)') }}
                                </label>
                                <textarea id="description_en" name="description_en" rows="3"
                                          class="form-control rounded-3 @error('description_en') is-invalid @enderror"
                                          maxlength="1000" placeholder="{{ __('Brief description of this branch…') }}">{{ old('description_en', $branch->description_en) }}</textarea>
                                @error('description_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="description_ar">
                                    {{ __('Description (AR)') }}
                                </label>
                                <textarea id="description_ar" name="description_ar" rows="3" dir="rtl"
                                          class="form-control rounded-3 @error('description_ar') is-invalid @enderror"
                                          maxlength="1000" placeholder="{{ __('وصف مختصر لهذا الفرع…') }}">{{ old('description_ar', $branch->description_ar) }}</textarea>
                                @error('description_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @include('company.branches.partials.location-fields', [
                                'countries'        => $countries,
                                'governorates'     => $governorates,
                                'areas'            => $areas,
                                'selCountryId'     => old('country_id', $branch->country_id),
                                'selGovernorateId' => old('governorate_id', $branch->governorate_id),
                                'selAreaId'        => old('area_id', $branch->area_id),
                                'selAddress'       => old('address', $branch->address),
                                'latitude'         => $branch->latitude,
                                'longitude'        => $branch->longitude,
                            ])

                            {{-- ── Address preview ────────────────────────────────── --}}
                            <div class="col-12" id="address-preview-wrap" style="display:none;">
                                <div class="rounded-3 px-3 py-2 d-flex align-items-center gap-2"
                                     style="background:rgba(201,162,39,.08);border:1px dashed rgba(201,162,39,.35);">
                                    <i data-feather="map-pin" style="width:14px;height:14px;color:#C9A227;flex-shrink:0;"></i>
                                    <span id="address-preview-text" class="small" style="color:#C9A227;font-weight:600;"></span>
                                </div>
                            </div>
                        </div>

                        {{-- ── أرقام الهاتف ─────────────────────────────────────── --}}
                        <div class="mb-4">
                            @include('company.branches.partials.phone-fields')
                        </div>

                        <hr class="my-4">

                        {{-- ── Status ───────────────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                            {{ __('Branch status') }}
                        </h6>

                        <div class="d-flex gap-3 flex-wrap mb-4">
                            @foreach([
                                'active'      => ['check-circle', 'success',   __('Active'),      __('Open & visible to customers')],
                                'inactive'    => ['x-circle',    'secondary',  __('Inactive'),    __('Hidden from public')],
                                'maintenance' => ['tool',         'warning',   __('Maintenance'), __('Visible but booking disabled')],
                            ] as $st => [$icon, $color, $lbl, $hint])
                            <label class="d-flex align-items-start gap-2 p-3 rounded-3 border flex-fill
                                       {{ old('status', $branch->status) === $st ? 'border-'.$color.' bg-'.$color.' bg-opacity-10' : '' }}"
                                   style="cursor:pointer;min-width:145px;" id="status-lbl-{{ $st }}">
                                <input type="radio" name="status" value="{{ $st }}"
                                       class="form-check-input mt-0 flex-shrink-0"
                                       {{ old('status', $branch->status) === $st ? 'checked' : '' }}
                                       onchange="highlightStatus()">
                                <div>
                                    <span class="fw-semibold text-{{ $color }} d-flex align-items-center gap-1">
                                        <i data-feather="{{ $icon }}" style="width:13px;height:13px;"></i>
                                        {{ $lbl }}
                                    </span>
                                    <small class="text-muted">{{ $hint }}</small>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('status')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                        {{-- ── Booking Mode ─────────────────────────────────── --}}
                        <label class="form-label fw-semibold mt-4 mb-2 d-flex align-items-center gap-2">
                            <i data-feather="globe" style="width:15px;height:15px;color:#7c3aed;"></i>
                            {{ __('Booking mode') }}
                        </label>
                        <p class="text-muted small mb-3">{{ __('Choose how customers discover and book this branch') }}</p>

                        <div class="d-flex gap-3 flex-wrap mb-3">
                            <label class="d-flex align-items-start gap-3 p-3 rounded-4 border flex-fill bk-mode-card"
                                   style="cursor:pointer;min-width:200px;position:relative;overflow:hidden;"
                                   id="mode-lbl-marketplace">
                                <input type="radio" name="booking_mode" value="marketplace"
                                       class="form-check-input mt-0 flex-shrink-0"
                                       {{ old('booking_mode', $branch->booking_mode) === 'marketplace' ? 'checked' : '' }}
                                       onchange="highlightMode()">
                                <div>
                                    <span class="fw-bold d-flex align-items-center gap-2" style="color:#10b981;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        {{ __('Marketplace') }}
                                    </span>
                                    <small class="text-muted d-block mt-1">{{ __('Branch appears in public directory. Customers can find you through search and browse.') }}</small>
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <span class="badge bg-success bg-opacity-10 text-success" style="font-size:.65rem;">{{ __('More visibility') }}</span>
                                        <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size:.65rem;">{{ __('Competitors visible') }}</span>
                                    </div>
                                </div>
                            </label>

                            <label class="d-flex align-items-start gap-3 p-3 rounded-4 border flex-fill bk-mode-card"
                                   style="cursor:pointer;min-width:200px;position:relative;overflow:hidden;"
                                   id="mode-lbl-private">
                                <input type="radio" name="booking_mode" value="private"
                                       class="form-check-input mt-0 flex-shrink-0"
                                       {{ old('booking_mode', $branch->booking_mode) === 'private' ? 'checked' : '' }}
                                       onchange="highlightMode()">
                                <div>
                                    <span class="fw-bold d-flex align-items-center gap-2" style="color:#7c3aed;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        {{ __('Private') }}
                                    </span>
                                    <small class="text-muted d-block mt-1">{{ __('Branch is hidden from directory. Only accessible via your private link or QR code. No competitors shown.') }}</small>
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.65rem;">{{ __('Your customers only') }}</span>
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.65rem;">{{ __('No competitors') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @error('booking_mode')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                        @if($branch->slug)
                        <div class="alert alert-light border rounded-3 mb-4 d-flex align-items-center gap-2" style="font-size:.82rem;">
                            <i data-feather="link" style="width:14px;height:14px;color:#7c3aed;"></i>
                            <span>{{ __('Private booking link') }}:</span>
                            <code class="ms-1 user-select-all" style="color:#7c3aed;">{{ $branch->privateBookingUrl() }}</code>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill ms-auto" style="font-size:.7rem;"
                                    onclick="navigator.clipboard.writeText('{{ $branch->privateBookingUrl() }}');this.textContent='{{ __('Copied!') }}';">
                                {{ __('Copy') }}
                            </button>
                        </div>
                        @endif

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox"
                                   name="is_head_office" id="is_head_office" value="1"
                                   @checked(old('is_head_office', $branch->is_head_office))>
                            <label class="form-check-label" for="is_head_office">
                                {{ __('Mark as head office') }}
                            </label>
                        </div>

                        <hr class="my-4">

                        {{-- ── Social links ───────────────────────────────────────── --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-1">
                            <i data-feather="share-2" style="width:14px;height:14px;" class="me-1"></i>
                            {{ __('Social Media Links') }}
                        </h6>
                        <p class="text-muted small mb-3">{{ __('Add any social accounts you want customers to find you on.') }}</p>

                        <div class="border rounded-3 mb-4">
                            @include('partials.social-links-form', [
                                'savedLinks'       => $socialLinks,
                                'inputPrefix'      => 'social_links',
                                'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram', 'linkedin'],
                            ])
                        </div>

                        {{-- ── Footer ────────────────────────────────────────────── --}}
                        <div class="d-flex justify-content-between gap-2 pt-3 border-top">
                            <a href="{{ route('company.branches.index') }}"
                               class="btn btn-light rounded-pill px-4">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i data-feather="save" class="me-1" style="width:16px;height:16px;"></i>
                                {{ __('Save changes') }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Live address preview ─────────────────────────────────────────
(function () {
    var wrap    = document.getElementById('address-preview-wrap');
    var preview = document.getElementById('address-preview-text');

    function updatePreview() {
        var parts = [];
        ['loc_country_id','loc_governorate_id','loc_area_id'].forEach(function (id) {
            var sel = document.getElementById(id);
            if (sel && sel.value) {
                var opt = sel.options[sel.selectedIndex];
                var label = (opt.dataset.name || opt.text).trim();
                if (label) parts.push(label);
            }
        });
        var street = (document.getElementById('address') || {}).value || '';
        if (street.trim()) parts.push(street.trim());

        if (parts.length) {
            preview.textContent = parts.join(' ← ');
            wrap.style.display = '';
            if (window.feather) feather.replace();
        } else {
            wrap.style.display = 'none';
        }
    }

    ['loc_country_id','loc_governorate_id','loc_area_id'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', updatePreview);
    });
    var addrEl = document.getElementById('address');
    if (addrEl) addrEl.addEventListener('input', updatePreview);

    updatePreview();
})();

// ── Status highlight ─────────────────────────────────────────────
const statusColors = { active: 'success', inactive: 'secondary', maintenance: 'warning' };

function highlightStatus() {
    document.querySelectorAll('[name="status"]').forEach(function (radio) {
        var lbl = document.getElementById('status-lbl-' + radio.value);
        if (!lbl) return;
        var c = statusColors[radio.value];
        if (radio.checked) {
            lbl.classList.add('border-' + c, 'bg-' + c, 'bg-opacity-10');
        } else {
            lbl.classList.remove(
                'border-success', 'border-secondary', 'border-warning',
                'bg-success', 'bg-secondary', 'bg-warning', 'bg-opacity-10'
            );
        }
    });
    if (window.feather) feather.replace();
}

const modeColors = { marketplace: '#10b981', private: '#7c3aed' };
function highlightMode() {
    document.querySelectorAll('[name="booking_mode"]').forEach(function (radio) {
        var lbl = document.getElementById('mode-lbl-' + radio.value);
        if (!lbl) return;
        if (radio.checked) {
            lbl.style.borderColor = modeColors[radio.value];
            lbl.style.background = modeColors[radio.value] + '0a';
            lbl.style.boxShadow = '0 0 0 3px ' + modeColors[radio.value] + '18';
        } else {
            lbl.style.borderColor = '';
            lbl.style.background = '';
            lbl.style.boxShadow = '';
        }
    });
}
highlightMode();
</script>
@endpush
