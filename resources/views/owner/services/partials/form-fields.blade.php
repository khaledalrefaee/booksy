{{--
    Owner service form — shared field body for create & edit.
    Uses the bm-* editorial system (see owner/branches/partials/_ui) with rounded
    fields, and exposes the full services-table schema: classification, pricing
    model + currency, discount, image and merchandising toggles.

    Expects: $branch, $serviceCategories, and optionally $service (edit mode).
--}}
@php
    $svc            = $service ?? null;
    $currencies     = config('booksy.currencies', ['SYP' => ['symbol' => 'ل.س']]);
    $defaultCur     = config('booksy.default_currency', 'SYP');
    $selCurrency    = old('currency', $svc->currency ?? $defaultCur);
    $selPriceType   = old('price_type', $svc->price_type ?? 'fixed');
    $selServiceType = old('service_type', $svc->service_type ?? 'standard');
    $locale         = app()->getLocale();

    $serviceTypeLabels = [
        'standard'     => __('Standard'),
        'package'      => __('Package'),
        'membership'   => __('Membership'),
        'addon'        => __('Add-on'),
        'consultation' => __('Consultation'),
    ];
    $priceTypeLabels = [
        'fixed' => __('Fixed price'),
        'from'  => __('Starting from'),
        'range' => __('Price range'),
    ];

    $dType  = old('discount_type',  $svc->discount_type ?? null);
    $dValue = old('discount_value', $svc->discount_value ?? '');
    $dStart = old('discount_starts_at', $svc && $svc->discount_starts_at ? $svc->discount_starts_at->format('Y-m-d\TH:i') : '');
    $dEnd   = old('discount_ends_at',   $svc && $svc->discount_ends_at ? $svc->discount_ends_at->format('Y-m-d\TH:i') : '');
    $hasDisc = $dType && $dValue !== '' && $dValue !== null;
@endphp

@once
@push('owner-styles')
<style>
/* Supplementary widgets for the owner service form (built on bm-*) */
.bm-currency-wrap { display:flex; gap:0; }
.bm-currency-btn { display:inline-flex; align-items:center; gap:6px; height:44px; padding:0 14px; border:1px solid var(--bk-border);
    border-inline-end:0; border-start-start-radius:11px; border-end-start-radius:11px; background:var(--bk-bg); color:var(--bk-text);
    font-weight:600; font-size:.85rem; cursor:pointer; white-space:nowrap; }
.bm-currency-wrap .form-control { border-start-start-radius:0 !important; border-end-start-radius:0 !important; }
.bm-currency-menu { min-width:230px; max-height:280px; overflow-y:auto; padding:6px; border-radius:12px; }
.bm-currency-menu .dropdown-item { display:flex; align-items:center; justify-content:space-between; gap:10px; border-radius:8px; padding:8px 10px; font-size:.85rem; }
.bm-seg { display:inline-flex; gap:4px; padding:4px; background:var(--bk-bg); border:1px solid var(--bk-border); border-radius:12px; flex-wrap:wrap; }
.bm-seg-opt { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:9px; font-size:.83rem; font-weight:600;
    color:var(--bk-text-muted); cursor:pointer; border:none; background:transparent; transition:all .15s; }
.bm-seg-opt input { position:absolute; opacity:0; pointer-events:none; }
.bm-seg-opt:hover { color:var(--bk-text); }
.bm-seg-opt.is-active { background:var(--bk-accent); color:var(--bk-accent-ink); box-shadow:var(--bk-shadow); }
.bm-toggle-card { display:flex; align-items:center; gap:12px; padding:13px 15px; border:1px solid var(--bk-border);
    border-radius:12px; background:var(--bk-bg); transition:border-color .15s, background .15s; }
.bm-toggle-card.is-on { border-color:color-mix(in srgb, var(--bk-accent) 40%, transparent); background:var(--bk-accent-wash); }
.bm-toggle-ic { width:36px; height:36px; border-radius:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-gold-strong); }
.bm-toggle-ic i, .bm-toggle-ic svg { width:17px; height:17px; }
.bm-toggle-body { flex:1; min-width:0; }
.bm-toggle-title { font-size:.86rem; font-weight:600; color:var(--bk-text); }
.bm-toggle-sub { font-size:.75rem; color:var(--bk-text-muted); margin-top:1px; }
.bm-img-drop { display:flex; align-items:center; gap:16px; padding:16px; border:1.5px dashed var(--bk-border); border-radius:14px; background:var(--bk-bg); }
.bm-img-thumb { width:80px; height:80px; border-radius:14px; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); border:1px solid var(--bk-border); color:var(--bk-accent); }
.bm-img-thumb img { width:100%; height:100%; object-fit:cover; }
.bm-disc-box { border:1px solid var(--bk-border); border-radius:14px; padding:16px 18px; background:var(--bk-bg); }
.bm-disc-preview { border-radius:11px; padding:10px 14px; background:color-mix(in srgb, var(--bk-gold) 10%, var(--bk-surface));
    border:1px dashed color-mix(in srgb, var(--bk-gold) 40%, transparent); }
</style>
@endpush
@endonce

{{-- ═══════════ Classification ═══════════ --}}
<div class="bm-section">
    <div class="bm-section-head"><i data-feather="layers"></i><h2 class="bm-section-title">{{ __('Classification') }}</h2></div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="bm-label" for="service_category_id">{{ __('Service category') }} <span class="bm-req">*</span></label>
            <select name="service_category_id" id="service_category_id" class="form-select @error('service_category_id') is-invalid @enderror" required>
                <option value="">{{ __('Select service category') }}</option>
                @foreach ($serviceCategories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) old('service_category_id', $svc->service_category_id ?? '') === (string) $cat->id)>{{ $cat->localizedName() }}</option>
                @endforeach
            </select>
            @error('service_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if ($serviceCategories->isEmpty())
                <p class="bm-help">{{ __('No service categories yet.') }} <a href="{{ route('owner.service-categories.index') }}">{{ __('Add service category') }}</a></p>
            @endif
        </div>
        <div class="col-md-6">
            <label class="bm-label" for="service_type">{{ __('Service type') }}</label>
            <select name="service_type" id="service_type" class="form-select @error('service_type') is-invalid @enderror">
                @foreach ($serviceTypeLabels as $val => $label)
                    <option value="{{ $val }}" @selected($selServiceType === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- ═══════════ Identity ═══════════ --}}
<div class="bm-section">
    <div class="bm-section-head"><i data-feather="tag"></i><h2 class="bm-section-title">{{ __('Service name') }}</h2></div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="bm-label" for="service-name-en">{{ __('Name (English)') }} <span class="bm-req">*</span></label>
            <input type="text" id="service-name-en" name="name_en" maxlength="255" required
                   value="{{ old('name_en', $svc->name_en ?? '') }}"
                   class="form-control @error('name_en') is-invalid @enderror" placeholder="{{ __('Enter name (English)') }}">
            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="bm-label" for="service-name-ar">{{ __('Name (Arabic)') }} <span class="bm-req">*</span></label>
            <input type="text" id="service-name-ar" name="name_ar" maxlength="255" required dir="rtl" lang="ar"
                   value="{{ old('name_ar', $svc->name_ar ?? '') }}"
                   class="form-control @error('name_ar') is-invalid @enderror" placeholder="{{ __('Enter Name (Arabic)') }}">
            @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="bm-label" for="service-description">{{ __('Description') }}</label>
            <textarea id="service-description" name="description" rows="3"
                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $svc->description ?? '') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- ═══════════ Pricing & duration ═══════════ --}}
<div class="bm-section">
    <div class="bm-section-head"><i data-feather="dollar-sign"></i><h2 class="bm-section-title">{{ __('Pricing & duration') }}</h2></div>

    <div class="mb-3">
        <label class="bm-label">{{ __('Pricing model') }}</label>
        <div class="bm-seg" id="price-type-seg">
            @foreach ($priceTypeLabels as $val => $label)
                <label class="bm-seg-opt {{ $selPriceType === $val ? 'is-active' : '' }}">
                    <input type="radio" name="price_type" value="{{ $val }}" @checked($selPriceType === $val)>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="bm-label" for="price"><span id="price-label">{{ __('Price') }}</span> <span class="bm-req">*</span></label>
            <div class="bm-currency-wrap">
                <button type="button" class="bm-currency-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="currency-btn">
                    <span id="currency-symbol">{{ $currencies[$selCurrency]['symbol'] ?? $selCurrency }}</span>
                    <span class="text-muted" style="font-size:.72rem;" id="currency-code">{{ $selCurrency }}</span>
                </button>
                <ul class="dropdown-menu bm-currency-menu shadow">
                    @foreach ($currencies as $code => $info)
                        <li>
                            <a class="dropdown-item currency-option {{ $code === $selCurrency ? 'active' : '' }}" href="#"
                               data-code="{{ $code }}" data-symbol="{{ $info['symbol'] }}">
                                <span><span class="fw-semibold me-1">{{ $info['symbol'] }}</span>{{ $locale === 'ar' ? $info['name_ar'] : $info['name_en'] }}</span>
                                <small class="text-muted">{{ $code }}</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <input type="hidden" name="currency" id="currency-input" value="{{ $selCurrency }}">
                <input type="number" id="price" name="price" step="0.01" min="0" required
                       value="{{ old('price', $svc->price ?? '') }}"
                       class="form-control @error('price') is-invalid @enderror">
            </div>
            @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4" id="price-to-col" style="{{ $selPriceType === 'range' ? '' : 'display:none;' }}">
            <label class="bm-label" for="price_to">{{ __('Up to') }}</label>
            <input type="number" id="price_to" name="price_to" step="0.01" min="0"
                   value="{{ old('price_to', $svc->price_to ?? '') }}"
                   class="form-control @error('price_to') is-invalid @enderror">
            @error('price_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="bm-label" for="duration_minutes">{{ __('Duration (minutes)') }} <span class="bm-req">*</span></label>
            <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="1440" required
                   value="{{ old('duration_minutes', $svc->duration_minutes ?? 30) }}"
                   class="form-control @error('duration_minutes') is-invalid @enderror">
            @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- ═══════════ Discount ═══════════ --}}
<div class="bm-section">
    <div class="bm-disc-box">
        <div class="d-flex align-items-center justify-content-between">
            <div class="bm-section-head mb-0"><i data-feather="percent"></i><h2 class="bm-section-title">{{ __('Discount / Promotion') }}</h2></div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="discount-toggle" @checked($hasDisc) style="cursor:pointer;">
            </div>
        </div>

        <div id="discount-fields" style="{{ $hasDisc ? '' : 'display:none;' }} margin-top:16px;">
            <div class="row g-3">
                <div class="col-12">
                    <label class="bm-label">{{ __('Discount type') }}</label>
                    <div class="bm-seg" id="discount-type-seg">
                        <label class="bm-seg-opt {{ (!$dType || $dType === 'percent') ? 'is-active' : '' }}">
                            <input type="radio" name="discount_type" value="percent" class="js-dtype" @checked(!$dType || $dType === 'percent')>
                            <span>{{ __('Percentage') }} (%)</span>
                        </label>
                        <label class="bm-seg-opt {{ $dType === 'fixed' ? 'is-active' : '' }}">
                            <input type="radio" name="discount_type" value="fixed" class="js-dtype" @checked($dType === 'fixed')>
                            <span>{{ __('Fixed amount') }}</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="bm-label" for="discount_value">{{ __('Discount value') }}</label>
                    <input type="number" id="discount_value" name="discount_value" min="0" step="0.01" placeholder="0"
                           value="{{ $dValue }}" class="form-control @error('discount_value') is-invalid @enderror">
                    @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="bm-disc-preview w-100" id="discount-preview" style="display:none;">
                        <span class="text-muted text-decoration-line-through me-2" id="dp-original"></span>
                        <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                        <span class="fw-bold ms-2" style="color:var(--bk-gold-strong);" id="dp-final"></span>
                        <span class="ms-2 bm-badge bm-badge-head" id="dp-badge"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="bm-label" for="discount_starts_at">{{ __('Starts at') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <input type="datetime-local" id="discount_starts_at" name="discount_starts_at" value="{{ $dStart }}"
                           class="form-control @error('discount_starts_at') is-invalid @enderror">
                    @error('discount_starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="bm-label" for="discount_ends_at">{{ __('Ends at') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <input type="datetime-local" id="discount_ends_at" name="discount_ends_at" value="{{ $dEnd }}"
                           class="form-control @error('discount_ends_at') is-invalid @enderror">
                    @error('discount_ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ Image ═══════════ --}}
<div class="bm-section">
    <div class="bm-section-head"><i data-feather="image"></i><h2 class="bm-section-title">{{ __('Service image') }}</h2></div>
    <div class="bm-img-drop">
        <div class="bm-img-thumb" id="service-image-thumb">
            @if ($svc && $svc->image_path)
                <img src="{{ asset('storage/'.$svc->image_path) }}" alt="" id="service-image-preview">
            @else
                <i data-feather="image" id="service-image-icon"></i>
                <img src="" alt="" id="service-image-preview" style="display:none;">
            @endif
        </div>
        <div class="flex-grow-1">
            <input type="file" name="image" accept="image/*" id="service-image-input"
                   class="form-control @error('image') is-invalid @enderror">
            <p class="bm-help">{{ __('JPG, PNG or WEBP — max 4 MB.') }}</p>
            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @if ($svc && $svc->image_path)
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                    <label class="form-check-label bm-help" for="remove_image">{{ __('Remove current image') }}</label>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════ Visibility & merchandising ═══════════ --}}
<div class="bm-section">
    <div class="bm-section-head"><i data-feather="eye"></i><h2 class="bm-section-title">{{ __('Visibility & merchandising') }}</h2></div>
    <div class="row g-3">
        @php
            $toggles = [
                ['name' => 'is_active',          'icon' => 'check-circle', 'title' => __('Active'),            'sub' => __('Service is bookable'),        'default' => true,  'value' => $svc->is_active ?? true],
                ['name' => 'is_bookable_online', 'icon' => 'globe',        'title' => __('Online booking'),    'sub' => __('Customers can book online'),   'default' => true,  'value' => $svc->is_bookable_online ?? true],
                ['name' => 'is_popular',         'icon' => 'trending-up',  'title' => __('Popular'),           'sub' => __('Highlight as popular'),        'default' => false, 'value' => $svc->is_popular ?? false],
                ['name' => 'is_recommended',     'icon' => 'star',         'title' => __('Recommended'),       'sub' => __('Mark as recommended'),         'default' => false, 'value' => $svc->is_recommended ?? false],
            ];
        @endphp
        @foreach ($toggles as $t)
            <div class="col-md-6">
                <label class="bm-toggle-card {{ old($t['name'], $t['value']) ? 'is-on' : '' }}" for="toggle-{{ $t['name'] }}">
                    <span class="bm-toggle-ic"><i data-feather="{{ $t['icon'] }}"></i></span>
                    <span class="bm-toggle-body">
                        <span class="bm-toggle-title d-block">{{ $t['title'] }}</span>
                        <span class="bm-toggle-sub d-block">{{ $t['sub'] }}</span>
                    </span>
                    <span class="form-check form-switch mb-0">
                        <input class="form-check-input js-toggle-card" type="checkbox" id="toggle-{{ $t['name'] }}"
                               name="{{ $t['name'] }}" value="1" @checked(old($t['name'], $t['value']))>
                    </span>
                </label>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.feather) window.feather.replace();

    // ── Currency picker ──
    document.querySelectorAll('.currency-option').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('currency-input').value = this.dataset.code;
            document.getElementById('currency-symbol').textContent = this.dataset.symbol;
            document.getElementById('currency-code').textContent = this.dataset.code;
            document.querySelectorAll('.currency-option').forEach(function (o) { o.classList.remove('active'); });
            this.classList.add('active');
            updateDiscountPreview();
        });
    });

    // ── Price type segmented control ──
    document.querySelectorAll('#price-type-seg input').forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('#price-type-seg .bm-seg-opt').forEach(function (o) { o.classList.remove('is-active'); });
            this.closest('.bm-seg-opt').classList.add('is-active');
            var isRange = this.value === 'range';
            document.getElementById('price-to-col').style.display = isRange ? '' : 'none';
            document.getElementById('price-label').textContent = this.value === 'from' ? @json(__('Starting price')) : @json(__('Price'));
        });
    });

    // ── Toggle cards ──
    document.querySelectorAll('.js-toggle-card').forEach(function (cb) {
        cb.addEventListener('change', function () {
            this.closest('.bm-toggle-card').classList.toggle('is-on', this.checked);
        });
    });

    // ── Discount ──
    var dToggle = document.getElementById('discount-toggle');
    var dFields = document.getElementById('discount-fields');
    var dValue  = document.getElementById('discount_value');
    if (dToggle) {
        dToggle.addEventListener('change', function () {
            dFields.style.display = this.checked ? '' : 'none';
            if (!this.checked && dValue) { dValue.value = ''; }
            updateDiscountPreview();
        });
    }
    document.querySelectorAll('#discount-type-seg input').forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('#discount-type-seg .bm-seg-opt').forEach(function (o) { o.classList.remove('is-active'); });
            this.closest('.bm-seg-opt').classList.add('is-active');
            updateDiscountPreview();
        });
    });
    if (dValue) dValue.addEventListener('input', updateDiscountPreview);
    var priceInp = document.getElementById('price');
    if (priceInp) priceInp.addEventListener('input', updateDiscountPreview);

    function updateDiscountPreview() {
        var preview = document.getElementById('discount-preview');
        if (!preview) return;
        var typeEl = document.querySelector('.js-dtype:checked');
        var type = typeEl ? typeEl.value : 'percent';
        var val = parseFloat(dValue ? dValue.value : 0) || 0;
        var price = parseFloat(priceInp ? priceInp.value : 0) || 0;
        var cur = document.getElementById('currency-input') ? document.getElementById('currency-input').value : 'SYP';
        if (!val || !price || !dToggle || !dToggle.checked) { preview.style.display = 'none'; return; }
        var finalP = type === 'percent' ? price * (1 - val / 100) : price - val;
        finalP = Math.max(0, finalP);
        document.getElementById('dp-original').textContent = price.toLocaleString() + ' ' + cur;
        document.getElementById('dp-final').textContent = finalP.toLocaleString(undefined, {maximumFractionDigits:2}) + ' ' + cur;
        document.getElementById('dp-badge').textContent = type === 'percent' ? '-' + val + '%' : '-' + val.toLocaleString() + ' ' + cur;
        preview.style.display = '';
        if (window.feather) window.feather.replace();
    }
    updateDiscountPreview();

    // ── Image preview ──
    var imgInput = document.getElementById('service-image-input');
    if (imgInput) {
        imgInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            var preview = document.getElementById('service-image-preview');
            var icon = document.getElementById('service-image-icon');
            if (!file || !file.type.startsWith('image/')) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = '';
                if (icon) icon.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>
@endpush
