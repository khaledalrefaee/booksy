{{--
    Discount section — include in service create & edit forms.
    Variables resolved from old() first, then $service if present.
--}}
@php
    $dType   = old('discount_type',      isset($service) ? $service->discount_type      : null);
    $dValue  = old('discount_value',     isset($service) ? $service->discount_value     : '');
    $dStart  = old('discount_starts_at', isset($service) && $service->discount_starts_at
                    ? $service->discount_starts_at->format('Y-m-d\TH:i') : '');
    $dEnd    = old('discount_ends_at',   isset($service) && $service->discount_ends_at
                    ? $service->discount_ends_at->format('Y-m-d\TH:i') : '');
    $hasDisc = $dType && $dValue !== '';
@endphp

<div class="rounded-4 border p-4" id="discount-section"
     style="{{ $hasDisc ? '' : '' }}">

    {{-- Header toggle --}}
    <div class="d-flex align-items-center justify-content-between mb-0">
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill px-2 py-1" style="background:rgba(234,88,12,.12);color:#ea580c;font-size:11px;">
                <i data-feather="tag" style="width:11px;height:11px;"></i>
            </span>
            <h6 class="fw-semibold mb-0" style="font-size:13px;">{{ __('Discount / Promotion') }}</h6>
        </div>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="discount-toggle"
                   {{ $hasDisc ? 'checked' : '' }} style="cursor:pointer;">
        </div>
    </div>

    {{-- Fields (shown/hidden by toggle) --}}
    <div id="discount-fields" style="{{ $hasDisc ? '' : 'display:none;' }} margin-top:16px;">
        <div class="row g-3 mt-0">

            {{-- Type --}}
            <div class="col-12">
                <label class="form-label fw-semibold small">{{ __('Discount type') }}</label>
                <div class="d-flex gap-2 flex-wrap" id="dtype-btns">
                    <label class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border flex-fill"
                           style="cursor:pointer;min-width:140px;" id="dtype-lbl-percent">
                        <input type="radio" name="discount_type" value="percent"
                               class="form-check-input mt-0 flex-shrink-0 js-dtype"
                               {{ $dType === 'percent' || !$dType ? 'checked' : '' }}>
                        <div>
                            <div class="fw-semibold small">{{ __('Percentage') }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ __('e.g. 20% off') }}</div>
                        </div>
                    </label>
                    <label class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border flex-fill"
                           style="cursor:pointer;min-width:140px;" id="dtype-lbl-fixed">
                        <input type="radio" name="discount_type" value="fixed"
                               class="form-check-input mt-0 flex-shrink-0 js-dtype"
                               {{ $dType === 'fixed' ? 'checked' : '' }}>
                        <div>
                            <div class="fw-semibold small">{{ __('Fixed amount') }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ __('e.g. reduce by 25,000') }}</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Value --}}
            <div class="col-md-4">
                <label for="discount_value" class="form-label fw-semibold small">
                    {{ __('Discount value') }}
                </label>
                <div class="input-group">
                    <input type="number" id="discount_value" name="discount_value"
                           class="form-control rounded-start-3 @error('discount_value') is-invalid @enderror"
                           value="{{ $dValue }}" min="0" step="0.01"
                           placeholder="0">
                    <span class="input-group-text" id="discount-unit-label" style="min-width:50px;justify-content:center;">
                        {{ $dType === 'fixed' ? (isset($service) ? $service->currency : 'SYP') : '%' }}
                    </span>
                </div>
                @error('discount_value')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Live preview --}}
            <div class="col-md-8 d-flex align-items-end">
                <div class="rounded-3 px-3 py-2 w-100" id="discount-preview"
                     style="background:rgba(234,88,12,.07);border:1px dashed rgba(234,88,12,.3);display:none;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted small text-decoration-line-through" id="dp-original"></span>
                        <i data-feather="arrow-right" style="width:12px;height:12px;color:#ea580c;"></i>
                        <span class="fw-bold" style="color:#ea580c;" id="dp-final"></span>
                        <span class="badge ms-1" style="background:rgba(234,88,12,.15);color:#ea580c;font-size:10px;" id="dp-badge"></span>
                    </div>
                </div>
            </div>

            {{-- Date range --}}
            <div class="col-md-6">
                <label for="discount_starts_at" class="form-label fw-semibold small">
                    {{ __('Starts at') }} <span class="text-muted fw-normal">({{ __('optional') }})</span>
                </label>
                <input type="datetime-local" id="discount_starts_at" name="discount_starts_at"
                       class="form-control rounded-3 @error('discount_starts_at') is-invalid @enderror"
                       value="{{ $dStart }}">
                @error('discount_starts_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="discount_ends_at" class="form-label fw-semibold small">
                    {{ __('Ends at') }} <span class="text-muted fw-normal">({{ __('optional') }})</span>
                </label>
                <input type="datetime-local" id="discount_ends_at" name="discount_ends_at"
                       class="form-control rounded-3 @error('discount_ends_at') is-invalid @enderror"
                       value="{{ $dEnd }}">
                @error('discount_ends_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var toggle   = document.getElementById('discount-toggle');
    var fields   = document.getElementById('discount-fields');
    var valInput = document.getElementById('discount_value');
    var unitLbl  = document.getElementById('discount-unit-label');
    var preview  = document.getElementById('discount-preview');
    var dpOrig   = document.getElementById('dp-original');
    var dpFinal  = document.getElementById('dp-final');
    var dpBadge  = document.getElementById('dp-badge');

    // Show / hide fields on toggle
    if (toggle && fields) {
        toggle.addEventListener('change', function () {
            fields.style.display = this.checked ? '' : 'none';
            if (!this.checked) {
                // clear discount_value so the server nullifies the discount
                if (valInput) valInput.value = '';
                if (preview)  preview.style.display = 'none';
            }
        });
    }

    // Type radio → update unit label + preview
    document.querySelectorAll('.js-dtype').forEach(function (r) {
        r.addEventListener('change', updateUnit);
    });

    function updateUnit() {
        var type = document.querySelector('.js-dtype:checked');
        if (!type) return;
        if (unitLbl) unitLbl.textContent = type.value === 'percent' ? '%' : getCurrency();
        updatePreview();
        highlightDtype(type.value);
    }

    function highlightDtype(val) {
        ['percent','fixed'].forEach(function (v) {
            var lbl = document.getElementById('dtype-lbl-' + v);
            if (!lbl) return;
            if (v === val) {
                lbl.style.borderColor = '#ea580c';
                lbl.style.background  = 'rgba(234,88,12,.06)';
            } else {
                lbl.style.borderColor = '';
                lbl.style.background  = '';
            }
        });
    }

    // Live preview
    function getCurrency() {
        var inp = document.getElementById('currency-input');
        return inp ? inp.value : 'SYP';
    }

    function getPrice() {
        var inp = document.getElementById('price');
        return inp ? parseFloat(inp.value) || 0 : 0;
    }

    function updatePreview() {
        if (!preview) return;
        var typeEl = document.querySelector('.js-dtype:checked');
        var type   = typeEl ? typeEl.value : 'percent';
        var val    = parseFloat(valInput ? valInput.value : 0) || 0;
        var price  = getPrice();
        if (!val || !price || !toggle || !toggle.checked) {
            preview.style.display = 'none';
            return;
        }
        var finalP;
        if (type === 'percent') {
            finalP = price * (1 - val / 100);
        } else {
            finalP = price - val;
        }
        finalP = Math.max(0, finalP);
        var cur = getCurrency();
        dpOrig.textContent  = price.toLocaleString() + ' ' + cur;
        dpFinal.textContent = finalP.toLocaleString(undefined, {maximumFractionDigits:2}) + ' ' + cur;
        dpBadge.textContent = type === 'percent'
            ? '-' + val + '%'
            : '-' + val.toLocaleString() + ' ' + cur;
        preview.style.display = '';
        if (window.feather) feather.replace();
    }

    if (valInput) valInput.addEventListener('input', updatePreview);
    var priceInp = document.getElementById('price');
    if (priceInp) priceInp.addEventListener('input', updatePreview);
    var currencyInp = document.getElementById('currency-input');
    if (currencyInp) {
        var obs = new MutationObserver(updatePreview);
        obs.observe(currencyInp, { attributes: true, attributeFilter: ['value'] });
        document.querySelectorAll('.currency-option').forEach(function (el) {
            el.addEventListener('click', function () { setTimeout(updatePreview, 50); });
        });
    }

    // Init on load
    var initType = document.querySelector('.js-dtype:checked');
    if (initType) {
        highlightDtype(initType.value);
        updatePreview();
    }
})();
</script>
@endpush
@endonce
