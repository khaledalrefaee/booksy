@extends('company.dashboard')

@section('content')
<div class="page-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('company.inventory.transfers.index') }}" class="btn btn-sm btn-outline-secondary">←</a>
        <h4 class="fw-bold mb-0">🔄 {{ __('New Stock Transfer') }}</h4>
    </div>

    <form method="POST" action="{{ route('company.inventory.transfers.store') }}" id="transferForm">
        @csrf
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">{{ __('From Branch') }} *</label>
                <select name="from_branch_id" id="fromBranch" class="form-select @error('from_branch_id') is-invalid @enderror" required>
                    <option value="">{{ __('Select...') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" @selected(old('from_branch_id') == $b->id)>{{ $b->localizedName() }}</option>
                    @endforeach
                </select>
                @error('from_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2 d-flex align-items-end justify-content-center">
                <span style="font-size:24px;">→</span>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">{{ __('To Branch') }} *</label>
                <select name="to_branch_id" class="form-select @error('to_branch_id') is-invalid @enderror" required>
                    <option value="">{{ __('Select...') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" @selected(old('to_branch_id') == $b->id)>{{ $b->localizedName() }}</option>
                    @endforeach
                </select>
                @error('to_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="{{ __('Optional notes...') }}">
            </div>
        </div>

        <h6 class="fw-bold mt-4 mb-2">📦 {{ __('Items to Transfer') }}</h6>

        <div id="itemsContainer">
            <div class="row g-2 mb-2 item-row">
                <div class="col-md-7">
                    <select name="items[0][product_id]" class="form-select form-select-sm" required>
                        <option value="">{{ __('Select product...') }}</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-stocks="{{ $p->branchStocks->pluck('quantity', 'branch_id')->toJson() }}">
                                {{ $p->localizedName() }}
                                ({{ __('Total') }}: {{ $p->totalStock() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="items[0][quantity]" min="1" value="1" class="form-control form-control-sm" placeholder="{{ __('Qty') }}" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" style="display:none;">🗑️</button>
                </div>
            </div>
        </div>

        <button type="button" id="addItem" class="btn btn-sm btn-outline-primary mt-1">+ {{ __('Add item') }}</button>

        <div class="mt-4">
            <button class="btn btn-primary px-4">🚚 {{ __('Create & Ship Transfer') }}</button>
            <a href="{{ route('company.inventory.transfers.index') }}" class="btn btn-outline-secondary ms-2">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let idx = 1;
    const container = document.getElementById('itemsContainer');

    document.getElementById('addItem').addEventListener('click', function() {
        const first = container.querySelector('.item-row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('select, input').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, `[${idx}]`);
            if (el.tagName === 'INPUT') el.value = el.type === 'number' ? '1' : '';
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
        });
        clone.querySelector('.remove-item').style.display = '';
        container.appendChild(clone);
        idx++;
        updateRemoveButtons();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.item-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.item-row');
        rows.forEach((row, i) => {
            row.querySelector('.remove-item').style.display = rows.length > 1 ? '' : 'none';
        });
    }
});
</script>
@endpush
@endsection
