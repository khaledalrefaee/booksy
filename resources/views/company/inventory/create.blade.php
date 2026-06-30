@extends('company.dashboard')

@push('company-styles')
<style>
.form-section {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 22px 24px;
    margin-bottom: 16px;
}
.form-section-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--bs-secondary-color);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-control, .form-select {
    border-radius: 10px;
    font-size: 14px;
}
.form-label { font-size: 13px; font-weight: 600; margin-bottom: 5px; }
.required-star { color: #ef4444; }
.price-input-wrap { position: relative; }
.price-input-wrap input { padding-inline-end: 52px; }
.price-input-wrap .currency-badge {
    position: absolute; top: 50%; inset-inline-end: 12px;
    transform: translateY(-50%);
    font-size: 11px; font-weight: 700;
    color: var(--bs-secondary-color);
    pointer-events: none;
}
.branch-stock-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 12px; padding: 12px 16px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
}
.branch-stock-card input[type=number] {
    width: 90px; text-align: center; font-weight: 700;
    border-radius: 10px;
}
.toggle-card {
    border: 1.5px solid var(--bs-border-color);
    border-radius: 12px; padding: 12px 16px;
    cursor: pointer; transition: border-color .2s, background .2s;
    display: flex; align-items: center; gap: 12px;
}
.toggle-card.active { border-color: #667eea; background: rgba(102,126,234,.06); }
.toggle-card input { display: none; }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('company.inventory.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">←</a>
        <div>
            <h4 class="fw-bold mb-0">{{ __('Add Product') }}</h4>
            <small class="text-muted">{{ __('Fill in the product details below') }}</small>
        </div>
    </div>

    <form method="POST" action="{{ route('company.inventory.store') }}" enctype="multipart/form-data" id="createForm" novalidate>
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">

                {{-- Basic Info --}}
                <div class="form-section">
                    <div class="form-section-title">📝 {{ __('Basic Info') }}</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Name (English)') }} <span class="required-star">*</span></label>
                            <input type="text" name="name_en" id="name_en"
                                class="form-control @error('name_en') is-invalid @enderror"
                                value="{{ old('name_en') }}"
                                placeholder="e.g. Hair Wax" required minlength="2">
                            <div class="invalid-feedback">{{ __('Required, min 2 characters.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Name (Arabic)') }}</label>
                            <input type="text" name="name_ar"
                                class="form-control"
                                value="{{ old('name_ar') }}"
                                placeholder="مثال: شمع الشعر" dir="rtl">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="product_category_id" class="form-select">
                                <option value="">— {{ __('No category') }} —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('product_category_id') == $cat->id)>{{ $cat->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" rows="2" class="form-control"
                                placeholder="{{ __('Optional product description...') }}">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="form-section">
                    <div class="form-section-title">💰 {{ __('Pricing') }}</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Currency') }}</label>
                            <select name="currency" id="currencySelect" class="form-select">
                                @foreach($currencies as $code => $cur)
                                    <option value="{{ $code }}" @selected($code === config('booksy.default_currency'))>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Sell Price') }} <span class="required-star">*</span></label>
                            <div class="price-input-wrap">
                                <input type="number" step="0.01" name="price" id="price"
                                    class="form-control @error('price') is-invalid @enderror"
                                    value="{{ old('price', 0) }}" min="0" required>
                                <span class="currency-badge" id="priceCurrency">{{ config('booksy.default_currency') }}</span>
                            </div>
                            <div class="invalid-feedback">{{ __('Required.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Cost Price') }}</label>
                            <div class="price-input-wrap">
                                <input type="number" step="0.01" name="cost_price"
                                    class="form-control"
                                    value="{{ old('cost_price', 0) }}" min="0">
                                <span class="currency-badge" id="costCurrency">{{ config('booksy.default_currency') }}</span>
                            </div>
                            <small class="text-muted">{{ __('Used to calculate profit') }}</small>
                        </div>
                    </div>
                </div>

                {{-- Stock Settings --}}
                <div class="form-section">
                    <div class="form-section-title">📦 {{ __('Stock Settings') }}</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Unit') }}</label>
                            <select name="unit" class="form-select">
                                @foreach(\App\Models\Product::UNITS as $u)
                                    <option value="{{ $u }}" @selected(old('unit') === $u)>{{ __($u) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Low Stock Alert') }}</label>
                            <input type="number" name="low_stock_threshold"
                                class="form-control"
                                value="{{ old('low_stock_threshold', 5) }}" min="0">
                            <small class="text-muted">{{ __('Alert when stock reaches this number') }}</small>
                        </div>
                        <div class="col-md-4 d-flex flex-column gap-2 justify-content-end">
                            <label class="toggle-card active" id="toggleTrack">
                                <input type="hidden" name="track_stock" value="0">
                                <input type="checkbox" name="track_stock" value="1" id="trackStock" checked>
                                <div>
                                    <div class="fw-semibold" style="font-size:13px;">📊 {{ __('Track stock') }}</div>
                                    <small class="text-muted">{{ __('Monitor quantities') }}</small>
                                </div>
                            </label>
                            <label class="toggle-card active" id="toggleActive">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" id="isActive" checked>
                                <div>
                                    <div class="fw-semibold" style="font-size:13px;">✅ {{ __('Active') }}</div>
                                    <small class="text-muted">{{ __('Available for sale') }}</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Initial stock per branch --}}
                    <div id="stockPerBranch">
                        <div class="form-section-title mt-2">🏪 {{ __('Initial Stock per Branch') }}</div>
                        <div class="d-flex flex-column gap-2">
                            @foreach($branches as $i => $branch)
                                <div class="branch-stock-card">
                                    <div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $branch->localizedName() }}</div>
                                    </div>
                                    <input type="hidden" name="initial_stock[{{ $i }}][branch_id]" value="{{ $branch->id }}">
                                    <input type="number" name="initial_stock[{{ $i }}][quantity]"
                                        value="0" min="0" class="form-control form-control-sm">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Image --}}
                <div class="form-section">
                    <div class="form-section-title">🖼️ {{ __('Image') }}</div>
                    <div class="text-center">
                        <div id="imgPreviewWrap" style="display:none; margin-bottom:12px;">
                            <img id="imgPreview" src="" style="width:100%; max-height:160px; object-fit:cover; border-radius:10px;">
                        </div>
                        <label for="imageInput" class="btn btn-outline-secondary w-100" style="border-radius:10px; border-style:dashed; cursor:pointer;">
                            📷 {{ __('Choose Image') }}
                        </label>
                        <input type="file" name="image" id="imageInput" accept="image/*" class="d-none">
                        <small class="text-muted d-block mt-1">{{ __('Max 2MB') }}</small>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="form-section">
                    <div class="form-section-title">📊 {{ __('Summary') }}</div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted" style="font-size:13px;">{{ __('Sell Price') }}</span>
                        <strong id="summaryPrice">—</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted" style="font-size:13px;">{{ __('Cost Price') }}</span>
                        <strong id="summaryCost">—</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted" style="font-size:13px;">{{ __('Profit') }}</span>
                        <strong id="summaryProfit" style="color:#16a34a;">—</strong>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="form-section">
                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-bold py-2" style="border-radius:12px;">
                        ✅ {{ __('Save Product') }}
                    </button>
                    <a href="{{ route('company.inventory.index') }}" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px;">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Currency badge sync ──
    document.getElementById('currencySelect').addEventListener('change', function() {
        var c = this.value;
        document.getElementById('priceCurrency').textContent = c;
        document.getElementById('costCurrency').textContent  = c;
        updateSummary();
    });

    // ── Profit summary ──
    function updateSummary() {
        var price = parseFloat(document.getElementById('price').value) || 0;
        var cost  = parseFloat(document.querySelector('[name=cost_price]').value) || 0;
        var cur   = document.getElementById('currencySelect').value;
        document.getElementById('summaryPrice').textContent  = price ? price.toLocaleString() + ' ' + cur : '—';
        document.getElementById('summaryCost').textContent   = cost  ? cost.toLocaleString()  + ' ' + cur : '—';
        var profit = price - cost;
        document.getElementById('summaryProfit').textContent = profit > 0 ? '+' + profit.toLocaleString() + ' ' + cur : '—';
        document.getElementById('summaryProfit').style.color = profit > 0 ? '#16a34a' : '#9ca3af';
    }
    document.getElementById('price').addEventListener('input', updateSummary);
    document.querySelector('[name=cost_price]').addEventListener('input', updateSummary);

    // ── Toggle cards ──
    function bindToggle(card, checkbox) {
        card.addEventListener('click', function() {
            checkbox.checked = !checkbox.checked;
            card.classList.toggle('active', checkbox.checked);
            if (checkbox.id === 'trackStock') {
                document.getElementById('stockPerBranch').style.display = checkbox.checked ? '' : 'none';
            }
        });
    }
    bindToggle(document.getElementById('toggleTrack'), document.getElementById('trackStock'));
    bindToggle(document.getElementById('toggleActive'), document.getElementById('isActive'));

    // ── Image preview ──
    document.getElementById('imageInput').addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreviewWrap').style.display = '';
        };
        reader.readAsDataURL(file);
    });

    // ── Real-time validation ──
    var nameEn = document.getElementById('name_en');
    nameEn.addEventListener('input', function() {
        var ok = this.value.trim().length >= 2;
        this.classList.toggle('is-valid', ok);
        this.classList.toggle('is-invalid', !ok && this.value.length > 0);
    });

    var priceInp = document.getElementById('price');
    priceInp.addEventListener('input', function() {
        var ok = parseFloat(this.value) >= 0 && this.value !== '';
        this.classList.toggle('is-valid', ok);
        this.classList.toggle('is-invalid', !ok);
    });

    // ── Submit validation ──
    document.getElementById('createForm').addEventListener('submit', function(e) {
        var valid = true;
        if (nameEn.value.trim().length < 2) {
            nameEn.classList.add('is-invalid'); valid = false;
        }
        if (!priceInp.value || parseFloat(priceInp.value) < 0) {
            priceInp.classList.add('is-invalid'); valid = false;
        }
        if (!valid) {
            e.preventDefault();
            nameEn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    updateSummary();
});
</script>
@endpush
