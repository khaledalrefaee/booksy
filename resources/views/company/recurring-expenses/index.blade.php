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

    @if($errors->any())
        @push('scripts')
        <script>
        @foreach($errors->all() as $error)
            bkToast(@json($error), 'error');
        @endforeach
        new bootstrap.Modal(document.getElementById('addModal')).show();
        </script>
        @endpush
    @endif

    <div class="rec-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">🔄 {{ __('Recurring Expenses') }}</h3>
                <p class="mb-0" style="opacity:.75;font-size:13px;">
                    {{ __('Monthly Total') }}: <strong>{{ number_format($monthlyTotal, 0) }} {{ config('booksy.default_currency') }}</strong>
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#addModal">
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
                @php $oldTitle = old('title'); $isCustomTitle = $oldTitle && !in_array($oldTitle, array_map('__', \App\Models\RecurringExpense::PRESET_TITLES)); @endphp
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Title') }} *</label>
                    <select class="form-select @error('title') is-invalid @enderror" id="presetTitle"
                        name="{{ $isCustomTitle ? '' : 'title' }}"
                        onchange="var ct=document.getElementById('customTitle'); if(this.value==='custom'){ct.style.display='block';ct.required=true;ct.name='title';this.name='';}else{ct.style.display='none';ct.required=false;ct.name='';this.name='title';}">
                        @foreach(\App\Models\RecurringExpense::PRESET_TITLES as $key => $label)
                            <option value="{{ __($label) }}" @selected($oldTitle === __($label))>{{ __($label) }}</option>
                        @endforeach
                        <option value="custom" @selected($isCustomTitle)>{{ __('Other') }}...</option>
                    </select>
                    <input type="text" name="{{ $isCustomTitle ? 'title' : '' }}" id="customTitle" value="{{ $isCustomTitle ? $oldTitle : '' }}"
                        class="form-control mt-2 @error('title') is-invalid @enderror"
                        style="display:{{ $isCustomTitle ? 'block' : 'none' }};" {{ $isCustomTitle ? 'required' : '' }}
                        placeholder="{{ __('Enter title...') }}">
                    @error('title') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Branch') }} *</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected((string) old('branch_id') === (string) $b->id)>{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required min="0.01">
                        @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-3">
                        <label class="form-label fw-semibold">{{ __('Currency') }}</label>
                        <select name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                            @foreach($currencies as $code => $cur)
                                <option value="{{ $code }}" @selected(old('currency', config('booksy.default_currency', 'SYP')) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                        @error('currency') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-3">
                        <label class="form-label fw-semibold">{{ __('Category') }}</label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror">
                            @php $cats = \App\Models\BranchPayment::CATEGORIES; @endphp
                            @foreach(collect($cats)->where('type', 'expense') as $key => $cat)
                                <option value="{{ $key }}" @selected(old('category', 'other_expense') === $key)>{{ __($cat['label_key']) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">{{ __('Frequency') }} *</label>
                        <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                            @foreach(\App\Models\RecurringExpense::FREQUENCIES as $f)
                                <option value="{{ $f }}" @selected(old('frequency', 'monthly') === $f)>{{ __($f) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">{{ __('Next Due') }} *</label>
                        <input type="date" name="next_due_date" value="{{ old('next_due_date', now()->addMonth()->startOfMonth()->toDateString()) }}" class="form-control @error('next_due_date') is-invalid @enderror" required>
                        @error('next_due_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
