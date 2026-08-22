{{-- Company Workspace — Inventory tab --}}
@if ($locked)
    <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div>
        <p>{{ __("This company's plan does not include :m.", ['m' => __('Inventory')]) }}</p></div>
@else
@php $transferTone = fn ($s) => match ($s) { 'received' => 'green', 'shipped' => 'blue', 'cancelled' => 'red', default => 'orange' }; @endphp
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="products">{{ __('Products') }} <span class="bk-pill bk-pill--muted">{{ $products->count() }}</span></button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="transfers">{{ __('Transfers') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="categories">{{ __('Categories') }}</button>
    </div>

    {{-- Products --}}
    <div data-ws-panel="products">
        <div class="bk-card"><div class="bk-card-head"><h3 class="bk-card-title"><i data-feather="package"></i> {{ __('Products') }}</h3>
            <form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
                @csrf<button class="bk-btn bk-btn--gold bk-btn--sm"><i data-feather="edit"></i> {{ __('Manage stock') }}</button></form>
        </div>
        <div class="bk-card-body p0">
            @forelse ($products as $p)
                @php $stock = $p->totalStock(); $low = $p->track_stock && $p->low_stock_threshold !== null && $stock <= $p->low_stock_threshold; @endphp
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Category') }}</th><th>{{ __('Unit') }}</th><th class="bk-tbl-num">{{ __('Price') }}</th><th class="bk-tbl-num">{{ __('Stock') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $p->localizedName() }}</td>
                    <td>{{ $p->category?->localizedName() ?? '—' }}</td>
                    <td>{{ __($p->unit) }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($p->price) }}</td>
                    <td class="bk-tbl-num">
                        @if($p->track_stock)<span class="bk-pill bk-pill--{{ $low ? 'red' : 'green' }}">{{ $stock }}</span>@else<span class="text-muted">—</span>@endif
                    </td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="package"></i></div><p>{{ __('No products yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Transfers --}}
    <div data-ws-panel="transfers" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($transfers as $t)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>#</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('Status') }}</th><th class="bk-tbl-num">{{ __('Created') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">#{{ $t->id }}</td>
                    <td>{{ $t->fromBranch?->localizedName() ?? '—' }}</td>
                    <td>{{ $t->toBranch?->localizedName() ?? '—' }}</td>
                    <td><span class="bk-pill bk-pill--{{ $transferTone($t->status) }}">{{ __($t->status) }}</span></td>
                    <td class="bk-tbl-num">{{ $t->created_at?->format('Y-m-d') }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="repeat"></i></div><p>{{ __('No stock transfers.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Categories --}}
    <div data-ws-panel="categories" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($categories as $c)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Category') }}</th><th>{{ __('Color') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $c->localizedName() }}</td>
                    <td>@if($c->color)<span class="bk-pill" style="background:{{ $c->color }};color:#fff;">{{ $c->color }}</span>@else — @endif</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="grid"></i></div><p>{{ __('No product categories.') }}</p></div>
            @endforelse
        </div></div>
    </div>
</div>
@endif
