@extends('company.dashboard')

@push('company-styles')
<style>
.form-section {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px; padding: 22px 24px; margin-bottom: 16px;
}
.form-section-title {
    font-size: 13px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--bs-secondary-color);
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}
.form-control, .form-select { border-radius: 10px; font-size: 14px; }
.form-label { font-size: 13px; font-weight: 600; margin-bottom: 5px; }
.required-star { color: #ef4444; }
.price-input-wrap { position: relative; }
.price-input-wrap input { padding-inline-end: 52px; }
.price-input-wrap .currency-badge {
    position: absolute; top: 50%; inset-inline-end: 12px;
    transform: translateY(-50%);
    font-size: 11px; font-weight: 700; color: var(--bs-secondary-color); pointer-events: none;
}
.branch-stock-card {
    border: 1px solid var(--bs-border-color); border-radius: 12px;
    padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.branch-stock-card .stock-num {
    font-size: 22px; font-weight: 800; min-width: 60px; text-align: center;
}
.branch-stock-card .adj-input {
    width: 90px; text-align: center; font-weight: 700; border-radius: 10px;
}
.stock-low-warn { font-size: 11px; color: #ef4444; }
.toggle-card {
    border: 1.5px solid var(--bs-border-color); border-radius: 12px;
    padding: 12px 16px; cursor: pointer; transition: border-color .2s, background .2s;
    display: flex; align-items: center; gap: 12px;
}
.toggle-card.active { border-color: #667eea; background: rgba(102,126,234,.06); }
.toggle-card input { display: none; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('company.inventory.show', $product) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">←</a>
        <div>
            <h4 class="fw-bold mb-0">{{ __('Edit Product') }}</h4>
            <small class="text-muted">{{ $product->localizedName() }}</small>
        </div>
    </div>

    <form method="POST" action="{{ route('company.inventory.update', $product) }}" enctype="multipart/form-data" id="editForm" novalidate>
        @csrf @method('PUT')

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
                                value="{{ old('name_en', $product->name_en) }}" required minlength="2">
                            <div class="invalid-feedback">{{ __('Required, min 2 characters.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Name (Arabic)') }}</label>
                            <input type="text" name="name_ar" class="form-control"
                                value="{{ old('name_ar', $product->name_ar) }}" dir="rtl">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="product_category_id" class="form-select">
                                <option value="">— {{ __('No category') }} —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected($product->product_category_id == $cat->id)>{{ $cat->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" rows="2" class="form-control">{{ old('description', $product->description) }}</textarea>
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
                                    <option value="{{ $code }}" @selected($product->currency === $code)>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Sell Price') }} <span class="required-star">*</span></label>
                            <div class="price-input-wrap">
                                <input type="number" step="0.01" name="price" id="price"
                                    class="form-control @error('price') is-invalid @enderror"
                                    value="{{ old('price', $product->price) }}" min="0" required>
                                <span class="currency-badge" id="priceCurrency">{{ $product->currency }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Cost Price') }}</label>
                            <div class="price-input-wrap">
                                <input type="number" step="0.01" name="cost_price" class="form-control"
                                    value="{{ old('cost_price', $product->cost_price) }}" min="0">
                                <span class="currency-badge" id="costCurrency">{{ $product->currency }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stock & Branches --}}
                <div class="form-section">
                    <div class="form-section-title">📦 {{ __('Stock Settings') }}</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Unit') }}</label>
                            <select name="unit" class="form-select">
                                @foreach(\App\Models\Product::UNITS as $u)
                                    <option value="{{ $u }}" @selected($product->unit === $u)>{{ __($u) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Low Stock Alert') }}</label>
                            <input type="number" name="low_stock_threshold" class="form-control"
                                value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" min="0">
                        </div>
                        <div class="col-md-4 d-flex flex-column gap-2 justify-content-end">
                            <label class="toggle-card {{ $product->track_stock ? 'active' : '' }}" id="toggleTrack">
                                <input type="hidden" name="track_stock" value="0">
                                <input type="checkbox" name="track_stock" value="1" id="trackStock" @checked($product->track_stock)>
                                <div>
                                    <div class="fw-semibold" style="font-size:13px;">📊 {{ __('Track stock') }}</div>
                                </div>
                            </label>
                            <label class="toggle-card {{ $product->is_active ? 'active' : '' }}" id="toggleActive">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" id="isActive" @checked($product->is_active)>
                                <div>
                                    <div class="fw-semibold" style="font-size:13px;">✅ {{ __('Active') }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Stock per branch (editable) --}}
                    @if($product->track_stock)
                    <div class="form-section-title mt-2">🏪 {{ __('Current Stock per Branch') }}</div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($branches as $branch)
                            @php $qty = $stocks[$branch->id]->quantity ?? 0; @endphp
                            <div class="branch-stock-card">
                                <div style="flex:1;">
                                    <div class="fw-semibold" style="font-size:13px;">{{ $branch->localizedName() }}</div>
                                    @if($qty <= $product->low_stock_threshold && $qty > 0)
                                        <span class="stock-low-warn">⚠ {{ __('Low stock') }}</span>
                                    @elseif($qty == 0)
                                        <span class="stock-low-warn">{{ __('Out of stock') }}</span>
                                    @endif
                                </div>
                                <div class="stock-num {{ $qty <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">
                                    {{ $qty }}
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted" style="font-size:12px;">→</span>
                                    <input type="number" name="stock[{{ $branch->id }}]"
                                        class="form-control adj-input form-control-sm"
                                        value="{{ $qty }}" min="0"
                                        title="{{ __('Set new quantity') }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2">💡 {{ __('Change the number on the right to adjust stock for that branch.') }}</small>
                    @endif
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Image --}}
                <div class="form-section">
                    <div class="form-section-title">🖼️ {{ __('Image') }}</div>
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}"
                            style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:10px;">
                    @else
                        <div id="imgPreviewWrap" style="display:none;margin-bottom:10px;">
                            <img id="imgPreview" src="" style="width:100%;max-height:140px;object-fit:cover;border-radius:10px;">
                        </div>
                    @endif
                    <label for="imageInput" class="btn btn-outline-secondary w-100" style="border-radius:10px;border-style:dashed;cursor:pointer;">
                        📷 {{ $product->image ? __('Change Image') : __('Choose Image') }}
                    </label>
                    <input type="file" name="image" id="imageInput" accept="image/*" class="d-none">
                </div>

                {{-- Summary --}}
                <div class="form-section">
                    <div class="form-section-title">📊 {{ __('Summary') }}</div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted" style="font-size:13px;">{{ __('Sell Price') }}</span>
                        <strong id="summaryPrice">{{ number_format($product->price,0) }} {{ $product->currency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted" style="font-size:13px;">{{ __('Cost Price') }}</span>
                        <strong id="summaryCost">{{ number_format($product->cost_price,0) }} {{ $product->currency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted" style="font-size:13px;">{{ __('Profit') }}</span>
                        <strong style="color:#16a34a;">{{ number_format($product->profit(),0) }} {{ $product->currency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted" style="font-size:13px;">{{ __('Total Stock') }}</span>
                        <strong>{{ $product->totalStock() }} {{ __($product->unit) }}</strong>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="form-section">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2" style="border-radius:12px;">
                        💾 {{ __('Update Product') }}
                    </button>
                    <a href="{{ route('company.inventory.show', $product) }}" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px;">
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
    document.getElementById('currencySelect').addEventListener('change', function() {
        var c = this.value;
        document.getElementById('priceCurrency').textContent = c;
        document.getElementById('costCurrency').textContent  = c;
    });

    function bindToggle(card, checkbox) {
        card.addEventListener('click', function() {
            checkbox.checked = !checkbox.checked;
            card.classList.toggle('active', checkbox.checked);
        });
    }
    bindToggle(document.getElementById('toggleTrack'), document.getElementById('trackStock'));
    bindToggle(document.getElementById('toggleActive'), document.getElementById('isActive'));

    @if(!$product->image)
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
    @endif
});
</script>
@endpush
