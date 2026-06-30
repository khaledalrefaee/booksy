@extends('company.dashboard')

@section('content')
<div class="page-content">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('company.inventory.index') }}" class="btn btn-sm btn-outline-secondary">←</a>
            <h4 class="fw-bold mb-0">📋 {{ __('Stock Movements') }}</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" id="btnDeleteSelected" class="btn btn-sm btn-outline-danger d-none" onclick="submitDelete(false)">
                🗑️ {{ __('Delete Selected') }} (<span id="selectedCount">0</span>)
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="submitDelete(true)">
                🗑️ {{ __('Delete All') }}
            </button>
        </div>
    </div>

    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
        <select name="branch_id" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->localizedName() }}</option>
            @endforeach
        </select>
        <select name="product_id" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
            <option value="">{{ __('All Products') }}</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->localizedName() }}</option>
            @endforeach
        </select>
        <select name="type" class="form-select form-select-sm" style="max-width:150px;" onchange="this.form.submit()">
            <option value="">{{ __('All Types') }}</option>
            @foreach(\App\Models\StockMovement::TYPES as $t)
                <option value="{{ $t }}" @selected(request('type') == $t)>{{ __($t) }}</option>
            @endforeach
        </select>
    </form>

    {{-- Hidden delete form --}}
    <form method="POST" id="deleteForm" action="{{ route('company.inventory.movements.delete') }}">
        @csrf @method('DELETE')
        <input type="hidden" name="delete_all" id="deleteAllFlag" value="0">
        <div id="selectedIdsContainer"></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" id="checkAll" class="form-check-input">
                    </th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Branch') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th class="text-center">{{ __('Qty') }}</th>
                    <th class="text-center">{{ __('Before') }} → {{ __('After') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('By') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input row-check" value="{{ $m->id }}">
                        </td>
                        <td class="text-nowrap small">{{ $m->created_at->format('d/m H:i') }}</td>
                        <td>{{ $m->product?->localizedName() ?? '—' }}</td>
                        <td>
                            {{ $m->branch?->localizedName() ?? '—' }}
                            @if($m->relatedBranch)
                                <br><small class="text-muted">
                                    {{ in_array($m->type, ['transfer_in']) ? '← ' : '→ ' }}{{ $m->relatedBranch->localizedName() }}
                                </small>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeColors = [
                                    'purchase' => 'success', 'sale' => 'primary',
                                    'transfer_in' => 'info', 'transfer_out' => 'warning',
                                    'adjustment' => 'secondary', 'return' => 'danger',
                                    'manual' => 'secondary',
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$m->type] ?? 'secondary' }}">{{ __($m->type) }}</span>
                        </td>
                        <td class="text-center fw-bold {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                        </td>
                        <td class="text-center small text-muted">{{ $m->quantity_before }} → {{ $m->quantity_after }}</td>
                        <td class="small">{{ $m->reference ?? '—' }}</td>
                        <td class="small">{{ $m->created_by_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No movements yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">{{ $movements->links() }}</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var checkAll = document.getElementById('checkAll');
    var btnDel   = document.getElementById('btnDeleteSelected');
    var countEl  = document.getElementById('selectedCount');

    function updateCount() {
        var checked = document.querySelectorAll('.row-check:checked');
        var n = checked.length;
        countEl.textContent = n;
        btnDel.classList.toggle('d-none', n === 0);
    }

    checkAll.addEventListener('change', function() {
        document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = checkAll.checked; });
        updateCount();
    });

    document.querySelectorAll('.row-check').forEach(function(cb) {
        cb.addEventListener('change', updateCount);
    });
});

function submitDelete(all) {
    if (all) {
        bkConfirm({ title: '{{ __("Delete All") }}?', text: '{{ __("This action cannot be undone.") }}' })
            .then(function(r) {
                if (!r.isConfirmed) return;
                document.getElementById('deleteAllFlag').value = '1';
                document.getElementById('deleteForm').submit();
            });
        return;
    }

    var ids = Array.from(document.querySelectorAll('.row-check:checked')).map(function(cb) { return cb.value; });
    if (!ids.length) return;

    bkConfirm({ title: '{{ __("Delete Selected") }}?', text: '{{ __("This action cannot be undone.") }}' })
        .then(function(r) {
            if (!r.isConfirmed) return;
            var container = document.getElementById('selectedIdsContainer');
            container.innerHTML = '';
            ids.forEach(function(id) {
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                container.appendChild(inp);
            });
            document.getElementById('deleteAllFlag').value = '0';
            document.getElementById('deleteForm').submit();
        });
}
</script>
@endpush
@endsection
