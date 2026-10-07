@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Toolbar ── */
.pr-toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:10px; margin-bottom:18px; box-shadow:var(--bk-shadow); }
.pr-search { position:relative; flex:1 1 260px; min-width:190px; }
.pr-search svg { position:absolute; inset-inline-start:13px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:var(--bk-text-muted); pointer-events:none; }
.pr-search input, .pr-select { height:44px; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.pr-search input { width:100%; padding-inline:40px 14px; }
.pr-search input::placeholder { color:var(--bk-text-muted); opacity:1; }
.pr-select { padding-inline:12px 34px; min-width:160px; cursor:pointer; }
.pr-search input:focus, .pr-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.pr-count { margin-inline-start:auto; font-size:.84rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; white-space:nowrap; padding-inline:6px; }

/* ── Product card ── */
.pr-card { display:flex; flex-direction:column; height:100%; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; overflow:hidden; box-shadow:var(--bk-shadow); transition:border-color .15s, box-shadow .15s; }
.pr-card:hover { border-color:var(--bk-border-strong); box-shadow:var(--bk-shadow-lg); }
.pr-media { position:relative; height:132px; background:var(--bk-surface-2); overflow:hidden; }
.pr-media img { width:100%; height:100%; object-fit:cover; display:block; }
.pr-ph { height:100%; display:flex; align-items:center; justify-content:center; color:var(--bk-text-muted); }
.pr-ph svg { width:34px; height:34px; stroke-width:1.5; opacity:.7; }
.pr-stock { position:absolute; bottom:10px; inset-inline-start:10px; }
.pr-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:20px; font-size:.76rem; font-weight:700; font-variant-numeric:tabular-nums; background:var(--bk-surface); box-shadow:var(--bk-shadow); }
.pr-pill::before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; flex:0 0 auto; }
.pr-pill.ok   { color:var(--bk-success); }
.pr-pill.low  { color:var(--bk-danger); }
.pr-pill.zero { color:var(--bk-text-muted); }

.pr-body { padding:14px 16px 12px; flex:1; display:flex; flex-direction:column; gap:2px; }
.pr-name { color:var(--bk-text); font-weight:700; font-size:1rem; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pr-name:hover { color:var(--bk-accent); }
.pr-cat { color:var(--bk-text-muted); font-size:.82rem; }
.pr-price { margin-top:10px; display:flex; align-items:baseline; gap:6px; flex-wrap:wrap; }
.pr-price strong { font-family:var(--bk-serif); font-size:1.35rem; font-weight:600; color:var(--bk-text); font-variant-numeric:tabular-nums lining-nums; }
.pr-price small { color:var(--bk-text-muted); font-size:.78rem; }
.pr-profit { font-size:.74rem; font-weight:700; padding:2px 8px; border-radius:20px; background:var(--bk-success-bg); color:var(--bk-success); font-variant-numeric:tabular-nums; }

.pr-foot { display:flex; align-items:center; gap:6px; padding:10px 12px; border-top:1px solid var(--bk-border); }
.pr-foot .pr-spacer { flex:1; }
.pr-foot .bk-act { height:34px; }
.pr-foot .bk-act:not(.bk-act--icon) { padding:0 12px; font-weight:600; }

/* ── Empty ── */
.pr-empty { padding:64px 20px; text-align:center; }
.pr-empty-ic { width:56px; height:56px; border-radius:16px; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.pr-empty-ic svg { width:26px; height:26px; stroke-width:1.75; }
.pr-empty h2 { font-family:var(--bk-serif); font-size:1.35rem; font-weight:600; margin:0 0 6px; color:var(--bk-text); }
.pr-empty p { margin:0 auto 18px; max-width:44ch; color:var(--bk-text-muted); font-size:.9rem; line-height:1.6; }

/* ── Modals: quantity stepper ── */
.pr-modal-title { display:flex; align-items:center; gap:8px; font-weight:700; }
.pr-modal-title svg { width:18px; height:18px; stroke-width:2; color:var(--bk-accent); }

@media (prefers-reduced-motion:reduce) { .pr-card { transition:none; } }
</style>
@endpush

@section('content')
<div class="page-content">

    <x-inventory.head :title="__('Inventory')" active="products"
        :subtitle="$products->total() . ' ' . __('products')">
        <x-slot:actions>
            <a href="{{ route('company.inventory.create') }}" class="ivh-btn ivh-btn-primary">
                <i data-feather="plus" aria-hidden="true"></i>{{ __('Add Product') }}
            </a>
        </x-slot:actions>
    </x-inventory.head>


    {{-- Filters --}}
    <form method="GET" class="pr-toolbar" data-filter-sheet="{{ __('Filters') }}" role="search">
        <div class="pr-search">
            <i data-feather="search" aria-hidden="true"></i>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('Search products...') }}" aria-label="{{ __('Search products...') }}">
        </div>
        <select name="category_id" class="pr-select" aria-label="{{ __('Categories') }}" onchange="this.form.submit()">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->localizedName() }}</option>
            @endforeach
        </select>
        @if(! ($branchContext ?? null))
        <select name="branch_id" class="pr-select" aria-label="{{ __('Branch') }}" onchange="this.form.submit()">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->localizedName() }}</option>
            @endforeach
        </select>
        @endif
        <button class="ivh-btn ivh-btn-primary">{{ __('Filter') }}</button>
        @if(request()->hasAny(['search','category_id']) || (! ($branchContext ?? null) && request()->filled('branch_id')))
            <a href="{{ route('company.inventory.index') }}" class="ivh-btn ivh-btn-ghost">
                <i data-feather="x" aria-hidden="true"></i>{{ __('Clear filters') }}
            </a>
        @endif
    </form>

    {{-- Product Grid --}}
    <div class="row g-3">
        @forelse($products as $product)
            @php
                $totalStock = $product->totalStock();
                $canMove    = $product->track_stock && $totalStock > 0 && $branches->count();
                $stockClass = $totalStock <= 0 ? 'zero' : ($totalStock <= $product->low_stock_threshold ? 'low' : 'ok');
                $pname      = $product->localizedName();
            @endphp
            <div class="col-md-6 col-lg-4 col-xl-3">
                <article class="pr-card">

                    <div class="pr-media">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="" loading="lazy">
                        @else
                            <div class="pr-ph"><i data-feather="package" aria-hidden="true"></i></div>
                        @endif
                        @if($product->track_stock)
                            <div class="pr-stock">
                                <span class="pr-pill {{ $stockClass }}">{{ $totalStock }} {{ __($product->unit) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="pr-body">
                        <a href="{{ route('company.inventory.show', $product) }}" class="pr-name" title="{{ $pname }}">{{ $pname }}</a>
                        <span class="pr-cat">{{ $product->category?->localizedName() ?? __('No category') }}</span>
                        <div class="pr-price">
                            <strong>{{ number_format($product->price, 0) }}</strong>
                            <small>{{ $product->currency }}</small>
                            @if($product->profit() > 0)
                                <span class="pr-profit">+{{ number_format($product->profit(), 0) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="pr-foot">
                        @if($canMove)
                            <x-bk-action icon="minus-circle"
                                data-reduce-id="{{ $product->id }}"
                                data-reduce-name="{{ $pname }}"
                                data-reduce-stock="{{ $totalStock }}"
                                data-reduce-unit="{{ $product->unit }}">{{ __('Use') }}</x-bk-action>

                            @php
                                $branchStocks = [];
                                foreach($branches as $b) {
                                    $branchStocks[$b->id] = $product->branchStocks->firstWhere('branch_id', $b->id)?->quantity ?? 0;
                                }
                            @endphp
                            <x-bk-action icon="shopping-cart" variant="success"
                                data-sell-id="{{ $product->id }}"
                                data-sell-name="{{ $pname }}"
                                data-sell-price="{{ $product->price }}"
                                data-sell-currency="{{ $product->currency }}"
                                data-sell-stock="{{ $totalStock }}"
                                :data-sell-stocks="json_encode($branchStocks)"
                                data-sell-unit="{{ $product->unit }}">{{ __('Sell') }}</x-bk-action>
                        @endif
                        <span class="pr-spacer"></span>
                        <x-bk-action :href="route('company.inventory.edit', $product)" icon="edit-2" icon-only>{{ __('Edit') }}</x-bk-action>
                        <x-bk-action icon="trash-2" variant="danger" icon-only
                            data-delete-id="{{ $product->id }}"
                            data-delete-name="{{ $pname }}">{{ __('Delete') }}</x-bk-action>
                    </div>

                </article>
            </div>

            {{-- Hidden delete form --}}
            <form id="del-prod-{{ $product->id }}" method="POST" action="{{ route('company.inventory.destroy', $product) }}" class="d-none">
                @csrf @method('DELETE')
            </form>
        @empty
            <div class="col-12">
                @php $activeCat = request('category_id') ? $categories->firstWhere('id', (int) request('category_id')) : null; @endphp
                <div class="pr-empty">
                    <div class="pr-empty-ic"><i data-feather="package" aria-hidden="true"></i></div>
                    @if($activeCat)
                        <h2>{{ __('No products in :category', ['category' => $activeCat->localizedName()]) }}</h2>
                        <p>{{ __('Move products you already have in the inventory into this category, or add a new one.') }}</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="{{ route('company.product-categories.index', ['assign' => $activeCat->id]) }}" class="ivh-btn ivh-btn-primary">
                                <i data-feather="link" aria-hidden="true"></i>{{ __('Assign products') }}
                            </a>
                            <a href="{{ route('company.inventory.create') }}" class="ivh-btn ivh-btn-ghost">
                                <i data-feather="plus" aria-hidden="true"></i>{{ __('Add Product') }}
                            </a>
                        </div>
                    @else
                        <h2>{{ __('No products yet.') }}</h2>
                        <p>{{ __('Start by adding your first product to the inventory.') }}</p>
                        <a href="{{ route('company.inventory.create') }}" class="ivh-btn ivh-btn-primary">
                            <i data-feather="plus" aria-hidden="true"></i>{{ __('Add your first product') }}
                        </a>
                    @endif
                </div>
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
                <h6 class="modal-title pr-modal-title"><i data-feather="minus-circle" aria-hidden="true"></i>{{ __('Reduce Stock') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
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
                <button type="submit" class="btn btn-warning px-4 fw-bold">{{ __('Confirm') }}</button>
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
                <h6 class="modal-title pr-modal-title"><i data-feather="shopping-cart" aria-hidden="true"></i>{{ __('Sell Product') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
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
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="card">{{ __('Card') }}</option>
                        <option value="bank_transfer">{{ __('Bank transfer') }}</option>
                    </select>
                </div>

                {{-- Seller (for product sales commission) --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold tx-13">{{ __('Sold by') }} <span class="text-muted fw-normal tx-11">({{ __('for commission') }})</span></label>
                    <select name="sold_by_employee_id" class="form-select">
                        <option value="">{{ __('Not attributed') }}</option>
                        @foreach(\App\Models\Employee::where('company_id', auth()->guard('company')->id())->where('is_active', true)->orderBy('name_en')->get(['id','name_en','name_ar']) as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-2 rounded text-center fw-bold fs-5" style="background:var(--bs-tertiary-bg);" id="slTotal"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-success px-4 fw-bold">{{ __('Confirm Sale') }}</button>
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
