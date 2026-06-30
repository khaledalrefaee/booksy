@extends('company.dashboard')

@push('company-styles')
<style>
.transfer-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.transfer-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="transfer-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">🔄 {{ __('Stock Transfers') }}</h3>
                <p class="mb-0" style="opacity:.75;font-size:13px;">{{ __('Transfer products between branches') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('company.inventory.index') }}" class="btn btn-sm btn-outline-light">← {{ __('Inventory') }}</a>
                <a href="{{ route('company.inventory.transfers.create') }}" class="btn btn-sm btn-light fw-semibold">➕ {{ __('New Transfer') }}</a>
            </div>
        </div>
    </div>

    <form method="GET" class="mb-3">
        <select name="status" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach(\App\Models\StockTransfer::STATUSES as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ __($s) }}</option>
            @endforeach
        </select>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('From') }} → {{ __('To') }}</th>
                    <th>{{ __('Items') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $t)
                    <tr>
                        <td class="fw-bold">{{ $t->id }}</td>
                        <td>
                            {{ $t->fromBranch?->localizedName() }}
                            <span class="text-muted mx-1">→</span>
                            {{ $t->toBranch?->localizedName() }}
                        </td>
                        <td>
                            @foreach($t->items as $item)
                                <div class="small">
                                    {{ $item->product?->localizedName() }}
                                    × <strong>{{ $item->quantity }}</strong>
                                    @if($item->received_quantity !== null && $item->received_quantity != $item->quantity)
                                        <span class="text-warning">({{ __('received') }}: {{ $item->received_quantity }})</span>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td>
                            @php
                                $colors = ['pending' => 'warning', 'shipped' => 'info', 'received' => 'success', 'cancelled' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $colors[$t->status] ?? 'secondary' }}">{{ __($t->status) }}</span>
                        </td>
                        <td class="small text-muted">
                            {{ $t->created_at->format('d/m/Y H:i') }}
                            <br>{{ $t->created_by_name }}
                        </td>
                        <td>
                            @if($t->status === 'shipped')
                                <form method="POST" action="{{ route('company.inventory.transfers.receive', $t) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm btn-success">✅ {{ __('Receive') }}</button>
                                </form>
                                <form method="POST" action="{{ route('company.inventory.transfers.cancel', $t) }}" class="d-inline"
                                    onsubmit="return confirm('{{ __('Cancel this transfer? Stock will be returned.') }}')">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Cancel') }}</button>
                                </form>
                            @elseif($t->status === 'pending')
                                <form method="POST" action="{{ route('company.inventory.transfers.cancel', $t) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Cancel') }}</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No transfers yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">{{ $transfers->links() }}</div>
</div>
@endsection
