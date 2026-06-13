@extends('company.dashboard')

@push('company-styles')
<style>
.ded-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.ded-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none;
}
.ded-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;
}
.ded-badge.absence     { background:rgba(239,68,68,.15);  color:#ef4444; }
.ded-badge.tardiness   { background:rgba(245,158,11,.15); color:#f59e0b; }
.ded-badge.other       { background:rgba(99,102,241,.15); color:#818cf8; }
.ded-badge.sick        { background:rgba(34,197,94,.15);  color:#22c55e; }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Hero --}}
    <div class="ded-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.branches.employees.index', $employee->branch) }}"
                               class="text-decoration-none" style="color:rgba(255,255,255,.65);font-size:13px;">
                                {{ $employee->branch->localizedName() }}
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.employees.edit', $employee) }}"
                               class="text-decoration-none" style="color:rgba(255,255,255,.65);font-size:13px;">
                                {{ $employee->localizedName() }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(255,255,255,.45);font-size:13px;">{{ __('Deductions') }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">
                    {{ __('Deductions') }} — {{ $employee->localizedName() }}
                </h3>
                <p class="mb-0" style="opacity:.75;font-size:13px;">
                    {{ __('Total deducted') }}:
                    <strong>{{ number_format($totalDeducted, 2) }}
                    {{ config('booksy.currencies.'.config('booksy.default_currency').'.symbol', 'ل.س') }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('company.employees.edit', $employee) }}"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);font-size:13px;backdrop-filter:blur(4px);"
                   class="btn btn-sm rounded-pill px-3">
                    <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                    {{ __('Back') }}
                </a>
                <a href="{{ route('company.employees.deductions.create', $employee) }}"
                   style="background:#fff;color:#f5576c;font-weight:700;font-size:13px;"
                   class="btn btn-sm rounded-pill px-3">
                    <i data-feather="plus" style="width:13px;height:13px;"></i>
                    {{ __('Add deduction') }}
                </a>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    @if($deductions->isEmpty())
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <i data-feather="check-circle" style="width:40px;height:40px;" class="text-success mb-3 d-block mx-auto"></i>
            <p class="text-muted">{{ __('No deductions recorded for this employee.') }}</p>
            <a href="{{ route('company.employees.deductions.create', $employee) }}" class="btn btn-outline-primary rounded-pill px-4">
                {{ __('Record first deduction') }}
            </a>
        </div>
    </div>
    @else

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        @php
            $countAbsence   = $deductions->where('type','absence')->where('is_sick_leave',false)->count();
            $countTardiness = $deductions->where('type','tardiness')->count();
            $countSick      = $deductions->where('is_sick_leave',true)->count();
        @endphp
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div style="font-size:22px;">🚫</div>
                    <div class="fw-bold fs-4 mt-1">{{ $countAbsence }}</div>
                    <div class="text-muted small">{{ __('Absences') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div style="font-size:22px;">⏰</div>
                    <div class="fw-bold fs-4 mt-1">{{ $countTardiness }}</div>
                    <div class="text-muted small">{{ __('Tardiness') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div style="font-size:22px;">🤒</div>
                    <div class="fw-bold fs-4 mt-1">{{ $countSick }}</div>
                    <div class="text-muted small">{{ __('Sick leave') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div style="font-size:22px;">💸</div>
                    <div class="fw-bold fs-4 mt-1">{{ number_format($totalDeducted, 0) }}</div>
                    <div class="text-muted small">{{ __('Total deducted') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Deductions table --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Hours') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Recorded by') }}</th>
                            <th>{{ __('Notes') }}</th>
                            <th class="pe-4 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deductions as $ded)
                        <tr class="{{ $ded->is_sick_leave ? 'table-success bg-opacity-25' : '' }}">
                            <td class="ps-4">
                                <span class="fw-semibold">{{ $ded->deduction_date->format('Y-m-d') }}</span>
                            </td>
                            <td>
                                @if($ded->is_sick_leave)
                                    <span class="ded-badge sick">🤒 {{ __('Sick leave') }}</span>
                                @else
                                    <span class="ded-badge {{ $ded->type }}">
                                        {{ $ded->type === 'absence' ? '🚫 '.__('Absence') : ($ded->type === 'tardiness' ? '⏰ '.__('Tardiness') : '📌 '.__('Other')) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted">
                                {{ $ded->hours ? number_format($ded->hours, 1).' '.__('h') : '—' }}
                            </td>
                            <td>
                                @if($ded->is_sick_leave)
                                    <span class="text-success small">{{ __('No deduction') }}</span>
                                @elseif($ded->amount)
                                    <span class="fw-semibold text-danger">
                                        {{ number_format($ded->amount, 2) }}
                                        {{ config('booksy.currencies.'.config('booksy.default_currency').'.symbol','ل.س') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $ded->recordedBy?->localizedName() ?? '—' }}
                            </td>
                            <td class="text-muted small" style="max-width:180px;">
                                <span class="text-truncate d-block" title="{{ $ded->notes }}">
                                    {{ $ded->notes ?: '—' }}
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <form action="{{ route('company.deductions.destroy', $ded) }}" method="POST"
                                      onsubmit="return confirm('{{ __('Delete this record?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
