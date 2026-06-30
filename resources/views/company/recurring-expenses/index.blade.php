@extends('company.dashboard')

@push('company-styles')
<style>
.rec-hero {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#333; position:relative; overflow:hidden;
}
.rec-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.3); pointer-events:none;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="rec-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">🔄 {{ __('Recurring Expenses') }}</h3>
                <p class="mb-0" style="opacity:.75;font-size:13px;">
                    {{ __('Monthly Total') }}: <strong>{{ number_format($monthlyTotal, 0) }} {{ config('booksy.default_currency') }}</strong>
                </p>
            </div>
            <button class="btn btn-sm btn-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#addModal">
                ➕ {{ __('Add Recurring Expense') }}
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Branch') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th>{{ __('Frequency') }}</th>
                    <th>{{ __('Next Due') }}</th>
                    <th>{{ __('Last Run') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                    <tr class="{{ !$exp->is_active ? 'opacity-50' : '' }}">
                        <td class="fw-bold">{{ $exp->title }}</td>
                        <td class="small">{{ $exp->branch?->localizedName() }}</td>
                        <td class="text-end fw-bold">{{ number_format($exp->amount, 0) }} {{ $exp->currency }}</td>
                        <td>
                            <span class="badge bg-info">{{ __($exp->frequency) }}</span>
                        </td>
                        <td class="small {{ $exp->isDue() ? 'text-danger fw-bold' : '' }}">
                            {{ $exp->next_due_date->format('d/m/Y') }}
                            @if($exp->isDue()) ⚠️ @endif
                        </td>
                        <td class="small text-muted">{{ $exp->last_run_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $exp->is_active ? 'success' : 'secondary' }}">
                                {{ __($exp->is_active ? 'active' : 'inactive') }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <form method="POST" action="{{ route('company.recurring-expenses.toggle', $exp) }}">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm btn-outline-{{ $exp->is_active ? 'warning' : 'success' }}" title="{{ $exp->is_active ? __('Deactivate') : __('Activate') }}">
                                        {{ $exp->is_active ? '⏸️' : '▶️' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('company.recurring-expenses.destroy', $exp) }}"
                                    onsubmit="return confirm('{{ __('Delete this recurring expense?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No recurring expenses yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('company.recurring-expenses.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Recurring Expense') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Title') }} *</label>
                    <select name="title" class="form-select" id="presetTitle" onchange="if(this.value==='custom'){document.getElementById('customTitle').style.display='block';this.name=''}else{document.getElementById('customTitle').style.display='none';this.name='title';}">
                        @foreach(\App\Models\RecurringExpense::PRESET_TITLES as $key => $label)
                            <option value="{{ __($label) }}">{{ __($label) }}</option>
                        @endforeach
                        <option value="custom">{{ __('Other') }}...</option>
                    </select>
                    <input type="text" name="title" id="customTitle" class="form-control mt-2" style="display:none;" placeholder="{{ __('Enter title...') }}">
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
                    <div class="col-6">
                        <label class="form-label fw-semibold">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required min="0.01">
                    </div>
                    <div class="col-3">
                        <label class="form-label fw-semibold">{{ __('Currency') }}</label>
                        <input type="text" name="currency" value="{{ config('booksy.default_currency', 'SYP') }}" class="form-control" maxlength="3">
                    </div>
                    <div class="col-3">
                        <label class="form-label fw-semibold">{{ __('Category') }}</label>
                        <select name="category" class="form-select">
                            @php $cats = \App\Models\BranchPayment::CATEGORIES; @endphp
                            @foreach(collect($cats)->where('type', 'expense') as $key => $cat)
                                <option value="{{ $key }}" @selected($key === 'other_expense')>{{ __($cat['label_key']) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">{{ __('Frequency') }} *</label>
                        <select name="frequency" class="form-select" required>
                            @foreach(\App\Models\RecurringExpense::FREQUENCIES as $f)
                                <option value="{{ $f }}" @selected($f === 'monthly')>{{ __($f) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">{{ __('Next Due') }} *</label>
                        <input type="date" name="next_due_date" value="{{ now()->addMonth()->startOfMonth()->toDateString() }}" class="form-control" required>
                    </div>
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
