@extends('company.dashboard')

@push('company-styles')
<style>
.inv-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px; padding: 26px 30px; margin-bottom: 24px;
    color: #fff; position: relative; overflow: hidden;
}
.inv-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.08); pointer-events: none;
}

.product-card {
    border-radius: 14px; border: 1px solid var(--bs-border-color);
    transition: transform .15s, box-shadow .15s; overflow: hidden;
}
.product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }

.product-img-wrap { position: relative; height: 130px; overflow: hidden; }
.product-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.product-img-placeholder {
    height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 42px; opacity: .5;
}

.stock-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.stock-ok   { background: rgba(34,197,94,.15);  color: #16a34a; }
.stock-low  { background: rgba(239,68,68,.15);  color: #ef4444; }
.stock-zero { background: rgba(107,114,128,.15); color: #6b7280; }
.profit-tag { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px; background: rgba(34,197,94,.1); color: #16a34a; }

/* ── Action buttons strip at bottom of card ── */
.card-actions { display: flex; gap: 6px; padding: 10px 12px; border-top: 1px solid var(--bs-border-color); }
.card-actions .btn { font-size: 12px; font-weight: 600; padding: 5px 10px; border-radius: 8px; flex: 1; }
.btn-reduce {
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff; border: none; flex: 1.5 !important;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.btn-reduce:hover { opacity: .9; color: #fff; }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Hero --}}
    <div class="inv-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">📦 {{ __('Inventory') }}</h3>
                <p class="mb-0" style="opacity:.75; font-size:13px;">{{ $products->total() }} {{ __('products') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('company.inventory.create') }}" class="btn btn-sm btn-light fw-semibold">➕ {{ __('Add Product') }}</a>
                <a href="{{ route('company.inventory.stock') }}" class="btn btn-sm btn-outline-light">📊 {{ __('Stock Levels') }}</a>
                <a href="{{ route('company.inventory.movements') }}" class="btn btn-sm btn-outline-light">📋 {{ __('Movements') }}</a>
                <a href="{{ route('company.inventory.transfers.index') }}" class="btn btn-sm btn-outline-light">🔄 {{ __('Transfers') }}</a>
                <a href="{{ route('company.product-categories.index') }}" class="btn btn-sm btn-outline-light">🏷️ {{ __('Categories') }}</a>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="d-flex gap-2 mb-4 flex-wrap align-items-center">
        <div style="position:relative; flex:1; min-width:180px; max-width:260px;">
            <span style="position:absolute;top:50%;inset-inline-start:10px;transform:translateY(-50%);pointer-events:none;">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search products...') }}"
                class="form-control form-control-sm" style="padding-inline-start:32px;">
        </div>
        <select name="category_id" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->localizedName() }}</option>
            @endforeach
        </select>
        <select name="branch_id" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->localizedName() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-primary px-3">{{ __('Filter') }}</button>
        @if(request()->hasAny(['search','category_id','branch_id']))
            <a href="{{ route('company.inventory.index') }}" class="btn btn-sm btn-outline-secondary">✕</a>
        @endif
    </form>

    {{-- Product Grid --}}
    <div class="row g-3">
        @forelse($products as $product)
            @php
                $totalStock = $product->totalStock();
                $gradients = ['linear-gradient(135deg,#a18cd1,#fbc2eb)','linear-gradient(135deg,#a1c4fd,#c2e9fb)','linear-gradient(135deg,#d4fc79,#96e6a1)','linear-gradient(135deg,#ffecd2,#fcb69f)','linear-gradient(135deg,#89f7fe,#66a6ff)','linear-gradient(135deg,#fad0c4,#ffd1ff)'];
                $grad = $gradients[$product->id % count($gradients)];
            @endphp
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="product-card h-100 d-flex flex-column">

                    {{-- Image --}}
                    <div class="product-img-wrap">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="">
                        @else
                            <div class="product-img-placeholder" style="background:{{ $grad }};">
                                {{ $product->category?->name_en ? mb_substr($product->category->name_en,0,1) : '📦' }}
                            </div>
                        @endif
                        {{-- Stock badge --}}
                        @if($product->track_stock)
                            <div style="position:absolute;bottom:8px;inset-inline-start:8px;">
                                <span class="stock-badge {{ $totalStock <= 0 ? 'stock-zero' : ($totalStock <= $product->low_stock_threshold ? 'stock-low' : 'stock-ok') }}">
                                    {{ $totalStock }} {{ __($product->unit) }}
                                </span>
                            </div>
                        @endif
                        {{-- Edit hover --}}
                        <a href="{{ route('company.inventory.edit', $product) }}"
                           class="btn btn-sm btn-light shadow-sm"
                           style="position:absolute;top:8px;inset-inline-end:8px;border-radius:8px;">✏️</a>
                    </div>

                    {{-- Body --}}
                    <div class="p-3 flex-grow-1 d-flex flex-column">
                        <a href="{{ route('company.inventory.show', $product) }}" class="text-decoration-none">
                            <h6 class="fw-bold mb-0 text-truncate">{{ $product->localizedName() }}</h6>
                        </a>
                        <small class="text-muted">{{ $product->category?->localizedName() ?? __('No category') }}</small>

                        <div class="mt-2 d-flex align-items-center gap-2">
                            <strong style="font-size:1.1rem;">{{ number_format($product->price, 0) }}</strong>
                            <small class="text-muted">{{ $product->currency }}</small>
                            @if($product->profit() > 0)
                                <span class="profit-tag">+{{ number_format($product->profit(), 0) }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions strip --}}
                    <div class="card-actions">
                        {{-- Quick Reduce (prominent gradient button) --}}
                        @if($product->track_stock && $totalStock > 0 && $branches->count())
                            <button class="btn btn-reduce"
                                data-reduce-id="{{ $product->id }}"
                                data-reduce-name="{{ addslashes($product->localizedName()) }}"
                                data-reduce-stock="{{ $totalStock }}"
                                data-reduce-unit="{{ $product->unit }}">
                                ⬇ {{ __('Use') }}
                            </button>
                        @endif
                        {{-- Sell --}}
                        @if($product->track_stock && $totalStock > 0 && $branches->count())
                            @php
                                $branchStocks = [];
                                foreach($branches as $b) {
                                    $branchStocks[$b->id] = $product->branchStocks->firstWhere('branch_id', $b->id)?->quantity ?? 0;
                                }
                            @endphp
                            <button class="btn btn-outline-success"
                                data-sell-id="{{ $product->id }}"
                                data-sell-name="{{ addslashes($product->localizedName()) }}"
                                data-sell-price="{{ $product->price }}"
                                data-sell-currency="{{ $product->currency }}"
                                data-sell-stock="{{ $totalStock }}"
                                data-sell-stocks='@json($branchStocks)'
                                data-sell-unit="{{ $product->unit }}">
                                🛒
                            </button>
                        @endif
                        {{-- Edit --}}
                        <a href="{{ route('company.inventory.edit', $product) }}" class="btn btn-outline-primary">✏️</a>
                        {{-- Delete --}}
                        <button class="btn btn-outline-danger"
                            data-delete-id="{{ $product->id }}"
                            data-delete-name="{{ addslashes($product->localizedName()) }}">🗑️</button>
                    </div>

                </div>
            </div>

            {{-- Hidden delete form --}}
            <form id="del-prod-{{ $product->id }}" method="POST" action="{{ route('company.inventory.destroy', $product) }}" class="d-none">
                @csrf @method('DELETE')
            </form>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <div style="font-size:60px;">📦</div>
                <h5 class="fw-bold mt-3">{{ __('No products yet.') }}</h5>
                <p>{{ __('Start by adding your first product to the inventory.') }}</p>
                <a href="{{ route('company.inventory.create') }}" class="btn btn-primary">➕ {{ __('Add your first product') }}</a>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $products->links() }}</div>
</div>

{{-- ── Quick USE (Reduce) Modal ── --}}
<div class="modal fade" id="reduceModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" id="reduceForm" class="modal-content">
            @csrf
            <input type="hidden" name="product_id" id="rdProductId">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">⬇ {{ __('Reduce Stock') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-center mb-1" id="rdName"></p>
                <p class="text-muted text-center small mb-3" id="rdStock"></p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Branch') }}</label>
                    <select name="branch_id" id="rdBranch" class="form-select" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Quantity used') }}</label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-3" onclick="changeRdQty(-1)">−</button>
                        <input type="number" name="quantity" id="rdQty" min="1" value="1"
                            class="form-control text-center fw-bold" style="font-size:1.6rem;">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-3" onclick="changeRdQty(1)">+</button>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold">{{ __('Note') }} <small class="text-muted fw-normal">({{ __('optional') }})</small></label>
                    <input type="text" name="notes" class="form-control" placeholder="{{ __('e.g. used for appointment') }}">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-warning px-4 fw-bold">⬇ {{ __('Confirm') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Quick SELL Modal ── --}}
<div class="modal fade" id="sellModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" id="sellForm" class="modal-content">
            @csrf
            <input type="hidden" name="product_id" id="slProductId">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">🛒 {{ __('Sell Product') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-center mb-3" id="slName"></p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Branch') }}</label>
                    <select name="branch_id_select" id="slBranch" class="form-select" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" data-name="{{ $b->localizedName() }}">
                                {{ $b->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Stock indicator for selected branch --}}
                    <div id="slBranchStock" class="mt-2 px-3 py-2 rounded-3 d-flex justify-content-between align-items-center"
                        style="background:var(--bs-tertiary-bg); font-size:13px;">
                        <span class="text-muted">{{ __('Available in this branch') }}</span>
                        <strong id="slBranchQty" class="text-success">—</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Quantity') }}</label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-3" onclick="changeSlQty(-1)">−</button>
                        <input type="number" name="quantity" id="slQty" min="1" value="1"
                            class="form-control text-center fw-bold" style="font-size:1.6rem;">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-3" onclick="changeSlQty(1)">+</button>
                    </div>
                </div>

                <div class="mb-3">
                    <select name="payment_method" class="form-select">
                        <option value="cash">💵 {{ __('Cash') }}</option>
                        <option value="card">💳 {{ __('Card') }}</option>
                        <option value="bank_transfer">🏦 {{ __('Bank transfer') }}</option>
                    </select>
                </div>

                <div class="p-2 rounded text-center fw-bold fs-5" style="background:var(--bs-tertiary-bg);" id="slTotal"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-success px-4 fw-bold">💰 {{ __('Confirm Sale') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
var slPrice = 0, slCurrency = '', slStocks = {}, slUnit = '';
document.addEventListener('DOMContentLoaded', function() {
    var rdModal  = new bootstrap.Modal(document.getElementById('reduceModal'));
    var sellModal = new bootstrap.Modal(document.getElementById('sellModal'));
    var sellRouteBase = "{{ url('company/branches') }}";
    var reduceBase = "{{ url('company/branches') }}";

    // ── Reduce buttons ──
    document.querySelectorAll('[data-reduce-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var productId = this.dataset.reduceId;
            var stock     = this.dataset.reduceStock;
            var unit      = this.dataset.reduceUnit;
            // Use first branch by default — user can change in modal
            var firstBranch = document.getElementById('rdBranch').value;
            document.getElementById('reduceForm').action = reduceBase + '/' + firstBranch + '/stock/remove';
            document.getElementById('rdProductId').value = productId;
            document.getElementById('rdName').textContent  = this.dataset.reduceName;
            document.getElementById('rdStock').textContent = '{{ __("Available") }}: ' + stock + ' ' + unit;
            document.getElementById('rdQty').value = 1;
            document.getElementById('rdQty').max   = stock;
            rdModal.show();
        });
    });

    // Update form action when branch changes
    document.getElementById('rdBranch').addEventListener('change', function() {
        var form = document.getElementById('reduceForm');
        form.action = reduceBase + '/' + this.value + '/stock/remove';
    });

    // ── Sell branch stock indicator ──
    function updateSlBranchStock() {
        var branchId = document.getElementById('slBranch').value;
        var qty = slStocks[branchId] !== undefined ? slStocks[branchId] : 0;
        var qtyEl = document.getElementById('slBranchQty');
        qtyEl.textContent = qty + ' ' + slUnit;
        qtyEl.className = 'fw-bold ' + (qty <= 0 ? 'text-danger' : qty <= 5 ? 'text-warning' : 'text-success');
        document.getElementById('slQty').max = qty > 0 ? qty : 1;
        if (parseInt(document.getElementById('slQty').value) > qty) {
            document.getElementById('slQty').value = qty > 0 ? qty : 1;
        }
        updateSlTotal();
    }

    // ── Sell buttons ──
    document.querySelectorAll('[data-sell-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            slPrice    = parseFloat(this.dataset.sellPrice) || 0;
            slCurrency = this.dataset.sellCurrency;
            slStocks   = JSON.parse(this.dataset.sellStocks || '{}');
            slUnit     = this.dataset.sellUnit || '';
            document.getElementById('slProductId').value  = this.dataset.sellId;
            document.getElementById('slName').textContent = this.dataset.sellName;
            document.getElementById('slQty').value = 1;
            updateSlBranchStock();
            sellModal.show();
        });
    });

    document.getElementById('slBranch').addEventListener('change', updateSlBranchStock);
    document.getElementById('slQty').addEventListener('input', updateSlTotal);

    document.getElementById('sellForm').addEventListener('submit', function() {
        var branchId = document.getElementById('slBranch').value;
        this.action = sellRouteBase + '/' + branchId + '/stock/sell';
    });

    // ── Delete buttons ──
    document.querySelectorAll('[data-delete-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id   = this.dataset.deleteId;
            var name = this.dataset.deleteName;
            bkConfirm({ title: '{{ __("Delete") }} "' + name + '"?', text: '{{ __("This action cannot be undone.") }}' })
                .then(function(r) { if (r.isConfirmed) document.getElementById('del-prod-' + id).submit(); });
        });
    });
});

function changeRdQty(d) {
    var inp = document.getElementById('rdQty');
    inp.value = Math.max(1, Math.min(parseInt(inp.max)||999, (parseInt(inp.value)||1) + d));
}
function changeSlQty(d) {
    var inp = document.getElementById('slQty');
    inp.value = Math.max(1, Math.min(parseInt(inp.max)||999, (parseInt(inp.value)||1) + d));
    updateSlTotal();
}
function updateSlTotal() {
    var qty = parseInt(document.getElementById('slQty').value) || 0;
    document.getElementById('slTotal').innerHTML =
        '{{ __("Total") }}: <strong>' + (qty * slPrice).toLocaleString() + ' ' + slCurrency + '</strong>';
}
</script>
@endpush
@endsection
