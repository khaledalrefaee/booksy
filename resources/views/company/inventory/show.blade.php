@extends('company.dashboard')

@push('company-styles')
<style>
.show-hero {
    border-radius: 20px; overflow: hidden; margin-bottom: 24px;
    position: relative; min-height: 160px;
    display: flex; align-items: flex-end;
}
.show-hero-bg {
    position: absolute; inset: 0;
    background: linear-gradient(135deg,#667eea,#764ba2);
}
.show-hero-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%; object-fit: cover; opacity: .35;
}
.show-hero-content {
    position: relative; z-index: 1; padding: 24px 28px; color: #fff; width: 100%;
}
.info-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 16px; padding: 18px 20px; margin-bottom: 16px;
}
.info-card-title {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--bs-secondary-color); margin-bottom: 14px;
}
.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid var(--bs-border-color); font-size: 13px;
}
.info-row:last-child { border-bottom: none; }
.info-row .label { color: var(--bs-secondary-color); }
.info-row .value { font-weight: 600; }

.branch-bar {
    border: 1px solid var(--bs-border-color); border-radius: 12px; padding: 14px 16px; margin-bottom: 10px;
}
.branch-bar-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.branch-bar-name { font-weight: 600; font-size: 13px; }
.branch-bar-qty  { font-weight: 800; font-size: 18px; }
.stock-bar { height: 6px; border-radius: 10px; background: var(--bs-tertiary-bg); overflow: hidden; }
.stock-bar-fill { height: 100%; border-radius: 10px; transition: width .6s ease; }

.mv-row { display: flex; gap: 12px; align-items: flex-start; padding: 10px 0; border-bottom: 1px solid var(--bs-border-color); }
.mv-row:last-child { border-bottom: none; }
.mv-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.mv-add  { background: rgba(34,197,94,.15); color: #16a34a; }
.mv-remove{ background: rgba(239,68,68,.15); color: #ef4444; }
.mv-adjust{ background: rgba(59,130,246,.15); color: #3b82f6; }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Hero --}}
    <div class="show-hero">
        <div class="show-hero-bg"></div>
        @if($product->image)
            <img class="show-hero-img" src="{{ asset('storage/'.$product->image) }}" alt="">
        @endif
        <div class="show-hero-content">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                <div>
                    <div class="mb-1">
                        <a href="{{ route('company.inventory.index') }}" style="color:rgba(255,255,255,.7); font-size:13px; text-decoration:none;">
                            ← {{ __('Inventory') }}
                        </a>
                    </div>
                    <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ $product->localizedName() }}</h3>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if($product->category)
                            <span style="background:rgba(255,255,255,.2); padding:2px 10px; border-radius:20px; font-size:12px;">
                                🏷️ {{ $product->category->localizedName() }}
                            </span>
                        @endif
                        <span style="background:rgba(255,255,255,.2); padding:2px 10px; border-radius:20px; font-size:12px;">
                            {{ $product->is_active ? '✅ '.__('Active') : '⏸ '.__('Inactive') }}
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('company.inventory.edit', $product) }}" class="btn btn-light btn-sm fw-semibold">
                        ✏️ {{ __('Edit') }}
                    </a>
                    <form method="POST" action="{{ route('company.inventory.destroy', $product) }}" id="delForm" class="d-inline">
                        @csrf @method('DELETE')
                    </form>
                    <button class="btn btn-outline-light btn-sm" onclick="bkConfirm({title:'{{ __('Delete') }} ?', text:'{{ __('This action cannot be undone.') }}'}).then(function(r){if(r.isConfirmed)document.getElementById('delForm').submit()})">
                        🗑️
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- Left column --}}
        <div class="col-lg-8">

            {{-- Stock per branch --}}
            <div class="info-card">
                <div class="info-card-title">🏪 {{ __('Stock per Branch') }}</div>
                @php $maxStock = $branches->map(fn($b) => $stocks[$b->id]->quantity ?? 0)->max() ?: 1; @endphp
                @foreach($branches as $branch)
                    @php $qty = $stocks[$branch->id]->quantity ?? 0; $pct = min(100, round($qty / $maxStock * 100)); @endphp
                    <div class="branch-bar">
                        <div class="branch-bar-top">
                            <span class="branch-bar-name">{{ $branch->localizedName() }}</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="branch-bar-qty {{ $qty <= $product->low_stock_threshold ? 'text-danger' : 'text-success' }}">
                                    {{ $qty }}
                                </span>
                                <small class="text-muted">{{ __($product->unit) }}</small>
                                {{-- Quick reduce --}}
                                @if($product->track_stock && $qty > 0)
                                    <button class="btn btn-sm btn-outline-warning py-0 px-2"
                                        data-reduce-id="{{ $product->id }}"
                                        data-reduce-name="{{ addslashes($product->localizedName()) }}"
                                        data-reduce-stock="{{ $qty }}"
                                        data-reduce-unit="{{ $product->unit }}"
                                        data-reduce-branch="{{ $branch->id }}"
                                        style="font-size:12px; border-radius:8px;">
                                        − {{ __('Use') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="stock-bar">
                            <div class="stock-bar-fill" style="width:{{ $pct }}%; background:{{ $qty <= $product->low_stock_threshold ? '#ef4444' : '#22c55e' }};"></div>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                    <span class="text-muted" style="font-size:13px;">{{ __('Total Stock') }}</span>
                    <strong>{{ $product->totalStock() }} {{ __($product->unit) }}</strong>
                </div>
            </div>

            {{-- Movement history --}}
            <div class="info-card">
                <div class="info-card-title">📋 {{ __('Recent Movements') }}</div>
                @forelse($movements as $mv)
                    @php
                        $isAdd = $mv->quantity > 0;
                        $isAdj = $mv->type === 'adjustment';
                        $dotClass = $isAdj ? 'mv-adjust' : ($isAdd ? 'mv-add' : 'mv-remove');
                        $icon = $isAdj ? '⚡' : ($isAdd ? '⬆' : '⬇');
                    @endphp
                    <div class="mv-row">
                        <div class="mv-dot {{ $dotClass }}">{{ $icon }}</div>
                        <div style="flex:1; min-width:0;">
                            <div class="fw-semibold" style="font-size:13px;">
                                {{ __(ucfirst($mv->type)) }}
                                @if($mv->reference)
                                    <span class="text-muted fw-normal">— {{ $mv->reference }}</span>
                                @endif
                            </div>
                            @if($mv->notes)
                                <div class="text-muted" style="font-size:12px;">{{ $mv->notes }}</div>
                            @endif
                        </div>
                        <div class="text-end" style="flex-shrink:0;">
                            <div class="fw-bold {{ $mv->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $mv->quantity > 0 ? '+' : '' }}{{ $mv->quantity }}
                            </div>
                            <small class="text-muted">{{ $mv->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-3 mb-0">{{ __('No movements yet.') }}</p>
                @endforelse
                @if($movements->count())
                    <div class="text-center mt-2">
                        <a href="{{ route('company.inventory.movements', ['product_id' => $product->id]) }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('View all movements') }}
                        </a>
                    </div>
                @endif
            </div>

        </div>

        {{-- Right column --}}
        <div class="col-lg-4">

            {{-- Pricing --}}
            <div class="info-card">
                <div class="info-card-title">💰 {{ __('Pricing') }}</div>
                <div class="info-row">
                    <span class="label">{{ __('Sell Price') }}</span>
                    <span class="value">{{ number_format($product->price,0) }} {{ $product->currency }}</span>
                </div>
                <div class="info-row">
                    <span class="label">{{ __('Cost Price') }}</span>
                    <span class="value">{{ number_format($product->cost_price,0) }} {{ $product->currency }}</span>
                </div>
                <div class="info-row">
                    <span class="label">{{ __('Profit') }}</span>
                    <span class="value text-success">+{{ number_format($product->profit(),0) }} {{ $product->currency }}</span>
                </div>
            </div>

            {{-- Details --}}
            <div class="info-card">
                <div class="info-card-title">📝 {{ __('Details') }}</div>
                <div class="info-row">
                    <span class="label">{{ __('Unit') }}</span>
                    <span class="value">{{ __($product->unit) }}</span>
                </div>
                <div class="info-row">
                    <span class="label">{{ __('Low Stock Alert') }}</span>
                    <span class="value">{{ $product->low_stock_threshold }}</span>
                </div>
                <div class="info-row">
                    <span class="label">{{ __('Track Stock') }}</span>
                    <span class="value">{{ $product->track_stock ? __('Yes') : __('No') }}</span>
                </div>
                @if($product->description)
                    <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:4px;">
                        <span class="label">{{ __('Description') }}</span>
                        <span style="font-size:13px;">{{ $product->description }}</span>
                    </div>
                @endif
            </div>

            {{-- Quick actions --}}
            <div class="info-card">
                <div class="info-card-title">⚡ {{ __('Quick Actions') }}</div>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('company.inventory.edit', $product) }}" class="btn btn-outline-primary w-100" style="border-radius:10px;">
                        ✏️ {{ __('Edit Product') }}
                    </a>
                    <a href="{{ route('company.inventory.movements', ['product_id' => $product->id]) }}" class="btn btn-outline-secondary w-100" style="border-radius:10px;">
                        📋 {{ __('All Movements') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Quick Reduce Modal --}}
<div class="modal fade" id="reduceModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" id="reduceForm" class="modal-content">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">⬇ {{ __('Reduce Stock') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-center mb-1" id="rdName"></p>
                <p class="text-muted text-center small mb-3" id="rdStock"></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Quantity used') }}</label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-3" onclick="changeRdQty(-1)">−</button>
                        <input type="number" name="quantity" id="rdQty" min="1" value="1"
                            class="form-control text-center fw-bold" style="font-size:1.6rem;">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-3" onclick="changeRdQty(1)">+</button>
                    </div>
                </div>
                <div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var rdModal = new bootstrap.Modal(document.getElementById('reduceModal'));
    var reduceBase = "{{ url('company/branches') }}";

    document.querySelectorAll('[data-reduce-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var branchId = this.dataset.reduceBranch;
            var stock    = this.dataset.reduceStock;
            document.getElementById('reduceForm').action = reduceBase + '/' + branchId + '/stock/remove';
            document.getElementById('rdName').textContent  = this.dataset.reduceName;
            document.getElementById('rdStock').textContent = '{{ __("Available") }}: ' + stock + ' ' + this.dataset.reduceUnit;
            document.getElementById('rdQty').value = 1;
            document.getElementById('rdQty').max   = stock;
            rdModal.show();
        });
    });
});

function changeRdQty(d) {
    var inp = document.getElementById('rdQty');
    inp.value = Math.max(1, Math.min(parseInt(inp.max)||999, (parseInt(inp.value)||1) + d));
}
</script>
@endpush
@endsection
