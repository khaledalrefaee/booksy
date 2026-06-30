@extends('company.dashboard')

@push('company-styles')
<style>
.debt-hero {
    background: linear-gradient(135deg, #f5576c 0%, #ff6b6b 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.debt-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="debt-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">💳 {{ __('Customer Debts') }}</h3>
                @foreach($totals as $currency => $amount)
                    <span class="badge bg-white text-danger fw-bold me-1">
                        {{ number_format($amount, 0) }} {{ $currency }}
                    </span>
                @endforeach
                @if($totals->isEmpty())
                    <p class="mb-0" style="opacity:.75;font-size:13px;">{{ __('No debts found.') }}</p>
                @endif
            </div>
            <div>
                <button class="btn btn-sm btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#newDebtModal">
                    ➕ {{ __('Record Debt') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}"
            class="form-control form-control-sm" style="max-width:200px;">
        <select name="status" class="form-select form-select-sm" style="max-width:160px;" onchange="this.form.submit()">
            <option value="">{{ __('All debts') }}</option>
            <option value="unpaid" @selected(request('status') == 'unpaid')>{{ __('Unpaid only') }}</option>
            <option value="partial" @selected(request('status') == 'partial')>{{ __('Partial') }}</option>
            <option value="paid" @selected(request('status') == 'paid')>{{ __('paid') }}</option>
            <option value="waived" @selected(request('status') == 'waived')>{{ __('waived') }}</option>
        </select>
        <select name="branch_id" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->localizedName() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Branch') }}</th>
                    <th class="text-end">{{ __('Original Amount') }}</th>
                    <th class="text-end">{{ __('Paid Amount') }}</th>
                    <th class="text-end">{{ __('Remaining') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($debts as $debt)
                    <tr>
                        <td>
                            <strong>{{ $debt->customer?->name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $debt->customer?->phone }}</small>
                        </td>
                        <td class="small">{{ $debt->branch?->localizedName() }}</td>
                        <td class="text-end">{{ number_format($debt->original_amount, 0) }} {{ $debt->currency }}</td>
                        <td class="text-end text-success">{{ number_format($debt->paid_amount, 0) }}</td>
                        <td class="text-end fw-bold text-danger">{{ number_format($debt->remaining, 0) }}</td>
                        <td>
                            @php
                                $colors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success', 'waived' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $colors[$debt->status] ?? 'secondary' }}">{{ __($debt->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $debt->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('company.debts.show', $debt) }}" class="btn btn-sm btn-outline-primary">{{ __('Details') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No debts found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">{{ $debts->links() }}</div>
</div>

{{-- New Debt Modal --}}
<div class="modal fade" id="newDebtModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('company.debts.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Record Debt') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Customer') }} *</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">{{ __('Select...') }}</option>
                    </select>
                    <small class="text-muted">{{ __('Search by phone or name') }}</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Branch') }} *</label>
                    <select name="branch_id" class="form-select" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label fw-semibold">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" name="original_amount" class="form-control" required min="0.01">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">{{ __('Currency') }}</label>
                        <input type="text" name="currency" value="{{ config('booksy.default_currency', 'SYP') }}" class="form-control" maxlength="3">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Due Date') }}</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                    <input type="text" name="notes" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
