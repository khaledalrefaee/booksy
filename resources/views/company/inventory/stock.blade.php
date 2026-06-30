@extends('company.dashboard')

@push('company-styles')
<style>
.stock-hero {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.stock-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none;
}
.stock-card {
    border-radius:14px; border:1px solid var(--bs-border-color);
    padding:16px; transition: transform .15s, box-shadow .15s;
}
.stock-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.08); }
.stock-card.low { border-color:#fca5a5; background:rgba(239,68,68,.03); }
.stock-qty {
    font-size:2rem; font-weight:800; line-height:1;
}
.stock-qty.ok { color:#16a34a; }
.stock-qty.warn { color:#ef4444; }
.stock-qty.zero { color:#9ca3af; }
.stock-actions-btn {
    width:36px; height:36px; border-radius:10px; display:inline-flex;
    align-items:center; justify-content:center; font-size:16px;
    border:1px solid var(--bs-border-color); background:var(--bs-body-bg);
    cursor:pointer; transition: all .15s;
}
.stock-actions-btn:hover { transform:scale(1.1); }
.stock-actions-btn.add { color:#16a34a; border-color:#bbf7d0; }
.stock-actions-btn.add:hover { background:#dcfce7; }
.stock-actions-btn.remove { color:#ef4444; border-color:#fecaca; }
.stock-actions-btn.remove:hover { background:#fee2e2; }
.stock-actions-btn.sell { color:#fff; background:#6366f1; border-color:#6366f1; }
.stock-actions-btn.sell:hover { background:#4f46e5; }
.stock-progress {
    height:6px; border-radius:3px; background:var(--bs-tertiary-bg);
    overflow:hidden; margin-top:8px;
}
.stock-progress-bar {
    height:100%; border-radius:3px; transition: width .3s;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="stock-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">📊 {{ __('Stock Levels') }}</h3>
                @if($lowStockCount > 0)
                    <p class="mb-0" style="opacity:.9;font-size:13px;">
                        ⚠️ <strong>{{ $lowStockCount }}</strong> {{ __('products with low stock') }}
                    </p>
                @endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('company.inventory.index') }}" class="btn btn-sm btn-light fw-semibold">← {{ __('Products') }}</a>
                <a href="{{ route('company.inventory.transfers.create') }}" class="btn btn-sm btn-outline-light fw-semibold">🔄 {{ __('New Transfer') }}</a>
            </div>
        </div>
    </div>

    {{-- Branch selector --}}
    <form method="GET" class="mb-4">
        <select name="branch_id" class="form-select form-select-sm" style="max-width:250px;" onchange="this.form.submit()">
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->localizedName() }}</option>
            @endforeach
        </select>
    </form>

    {{-- Stock Cards Grid --}}
    <div class="row g-3">
        @forelse($stocks as $stock)
            @php
                $product = $stock->product;
                $pct = $product->low_stock_threshold > 0
                    ? min(100, round(($stock->quantity / max($product->low_stock_threshold * 3, 1)) * 100))
                    : 100;
                $barColor = $stock->quantity <= 0 ? '#9ca3af'
                    : ($stock->isLow() ? '#ef4444' : '#22c55e');
                $qtyClass = $stock->quantity <= 0 ? 'zero' : ($stock->isLow() ? 'warn' : 'ok');
            @endphp
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="stock-card {{ $stock->isLow() ? 'low' : '' }} h-100">
                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div style="min-width:0;">
                            <h6 class="fw-bold mb-0 text-truncate">{{ $product->localizedName() }}</h6>
                            <small class="text-muted">{{ $product->category?->localizedName() ?? '—' }}</small>
                            @if($product->sku)
                                <small class="text-muted d-block">SKU: {{ $product->sku }}</small>
                            @endif
                        </div>
                        @if($stock->quantity <= 0)
                            <span class="badge bg-secondary">{{ __('Out of stock') }}</span>
                        @elseif($stock->isLow())
                            <span class="badge bg-danger">⚠️</span>
                        @else
                            <span class="badge bg-success">✓</span>
                        @endif
                    </div>

                    {{-- Quantity --}}
                    <div class="text-center py-2">
                        <div class="stock-qty {{ $qtyClass }}">{{ $stock->quantity }}</div>
                        <small class="text-muted">{{ __($product->unit) }} · {{ __('min') }}: {{ $product->low_stock_threshold }}</small>
                    </div>

                    {{-- Progress bar --}}
                    <div class="stock-progress">
                        <div class="stock-progress-bar" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                    </div>

                    {{-- Price info --}}
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">{{ __('Sell') }}: {{ number_format($product->price, 0) }}</small>
                        <small class="text-muted">{{ __('Cost') }}: {{ number_format($product->cost_price, 0) }}</small>
                    </div>

                    {{-- Action buttons --}}
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <button class="stock-actions-btn add" title="{{ __('Add') }}"
                            onclick="openStockModal('add', {{ $product->id }}, '{{ addslashes($product->localizedName()) }}', {{ $stock->quantity }})">
                            +
                        </button>
                        <button class="stock-actions-btn remove" title="{{ __('Remove') }}"
                            onclick="openStockModal('remove', {{ $product->id }}, '{{ addslashes($product->localizedName()) }}', {{ $stock->quantity }})">
                            −
                        </button>
                        <button class="stock-actions-btn sell" title="{{ __('Sell Product') }}"
                            onclick="openStockModal('sell', {{ $product->id }}, '{{ addslashes($product->localizedName()) }}', {{ $stock->quantity }}, {{ $product->price }}, '{{ $product->currency }}')">
                            🛒
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <div style="font-size:48px;">📊</div>
                <p>{{ __('No stock in this branch.') }}</p>
                <a href="{{ route('company.inventory.index') }}" class="btn btn-primary btn-sm">{{ __('Add your first product') }}</a>
            </div>
        @endforelse
    </div>
</div>

{{-- Stock Action Modal --}}
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" id="stockForm" class="modal-content">
            @csrf
            <input type="hidden" name="product_id" id="modalProductId">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="modalTitle"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted small mb-2" id="modalProductName"></p>
                <p class="small mb-3" id="modalCurrentStock"></p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Quantity') }}</label>
                    <input type="number" name="quantity" id="modalQty" min="1" value="1"
                        class="form-control form-control-lg text-center fw-bold" style="font-size:1.5rem; max-width:140px; margin:0 auto;">
                </div>

                {{-- Payment method (only for sell) --}}
                <div class="mb-3" id="modalPaymentGroup" style="display:none;">
                    <label class="form-label fw-semibold">{{ __('Payment Method') }}</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash">💵 {{ __('Cash') }}</option>
                        <option value="card">💳 {{ __('Card') }}</option>
                        <option value="bank_transfer">🏦 {{ __('Bank transfer') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('Optional notes...') }}">
                </div>

                {{-- Sell total preview --}}
                <div id="modalSellTotal" style="display:none;"
                    class="p-2 rounded text-center fw-bold fs-5" style="background:var(--bs-tertiary-bg);">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn px-4" id="modalSubmitBtn"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const branchId = {{ $branchId }};
const routes = {
    add: "{{ route('company.inventory.stock.add', '__BRANCH__') }}".replace('__BRANCH__', branchId),
    remove: "{{ route('company.inventory.stock.remove', '__BRANCH__') }}".replace('__BRANCH__', branchId),
    sell: "{{ route('company.inventory.stock.sell', '__BRANCH__') }}".replace('__BRANCH__', branchId),
};
const labels = {
    add:    { title: '➕ {{ __("Add Stock") }}',  btn: '{{ __("Add") }}',          btnClass: 'btn-success' },
    remove: { title: '➖ {{ __("Remove Stock") }}', btn: '{{ __("Remove") }}',      btnClass: 'btn-danger' },
    sell:   { title: '🛒 {{ __("Sell Product") }}', btn: '💰 {{ __("Confirm Sale") }}', btnClass: 'btn-primary' },
};

let sellPrice = 0, sellCurrency = '';

function openStockModal(type, productId, productName, currentQty, price, currency) {
    const form = document.getElementById('stockForm');
    const cfg = labels[type];

    form.action = routes[type];
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalTitle').textContent = cfg.title;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalCurrentStock').innerHTML =
        '{{ __("Current Stock") }}: <strong>' + currentQty + '</strong>';
    document.getElementById('modalQty').value = 1;
    document.getElementById('modalQty').max = type === 'remove' || type === 'sell' ? currentQty : 99999;

    const submitBtn = document.getElementById('modalSubmitBtn');
    submitBtn.textContent = cfg.btn;
    submitBtn.className = 'btn px-4 ' + cfg.btnClass;

    const payGroup = document.getElementById('modalPaymentGroup');
    const sellTotal = document.getElementById('modalSellTotal');
    payGroup.style.display = type === 'sell' ? '' : 'none';
    sellTotal.style.display = type === 'sell' ? '' : 'none';

    if (type === 'sell') {
        sellPrice = price || 0;
        sellCurrency = currency || 'SYP';
        updateSellTotal();
    }

    new bootstrap.Modal(document.getElementById('stockModal')).show();
}

function updateSellTotal() {
    const qty = parseInt(document.getElementById('modalQty').value) || 0;
    const total = qty * sellPrice;
    document.getElementById('modalSellTotal').innerHTML =
        '{{ __("Total") }}: <strong>' + total.toLocaleString() + ' ' + sellCurrency + '</strong>';
}

document.getElementById('modalQty').addEventListener('input', updateSellTotal);
</script>
@endpush
@endsection
