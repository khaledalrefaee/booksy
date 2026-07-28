@extends('company.dashboard')

@push('company-styles')
<style>
.adv-hero {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 60%, #92400e 100%);
    border-radius: 20px; padding: 26px 30px; margin-bottom: 24px;
    color: #fff; position: relative; overflow: hidden;
}
.adv-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.09); pointer-events: none;
}
.adv-row {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 16px 18px;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
.adv-row:last-child { border-bottom: none; }
.bk-theme-light .adv-row { border-bottom-color: rgba(0,0,0,.05); }
.adv-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 15px; flex-shrink: 0; color: #fff;
    object-fit: cover;
}
.adv-bar-bg {
    height: 7px; border-radius: 5px; background: rgba(255,255,255,.08);
    overflow: hidden; width: 100%;
}
.bk-theme-light .adv-bar-bg { background: rgba(0,0,0,.08); }
.adv-bar-fill { height: 100%; border-radius: 5px; background: linear-gradient(90deg,#f59e0b,#22c55e); transition: width .4s; }
.adv-pill { font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
@media (max-width: 576px) {
    .adv-hero { padding: 18px 16px; }
    .adv-row { padding: 12px 14px; }
}
</style>
@endpush

@section('content')
<div class="page-content">

@include('company.partials.team-nav')

@php $avatarColors = ['#5C7038','#667eea','#22c55e','#ef4444','#f59e0b','#a78bfa','#fb923c','#06b6d4']; @endphp

{{-- Hero --}}
<div class="adv-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
        <div>
            <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">💵 {{ __('Salary advances') }}</h3>
            <p class="mb-0" style="opacity:.8;font-size:13px;">
                @if($outstanding->isNotEmpty())
                    {{ __('Outstanding') }}:
                    <strong>{{ $outstanding->map(fn ($amt, $cur) => number_format($amt, 0) . ' ' . config("booksy.currencies.{$cur}.symbol", $cur))->implode(' + ') }}</strong>
                @else
                    {{ __('No outstanding advances — all settled.') }} ✅
                @endif
            </p>
        </div>
        <button type="button" class="btn rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#advanceModal"
                style="background:#fff;color:#d97706;border:none;font-size:13px;">
            ➕ {{ __('Grant advance') }}
        </button>
    </div>
</div>

@include('company.partials.flash')

{{-- Advances list --}}
<div class="card border-0 shadow-sm rounded-4" style="overflow:hidden;">
    <div class="card-body p-0">
        @forelse($advances as $advance)
        @php
            $emp       = $advance->employee;
            $color     = $avatarColors[$emp->id % count($avatarColors)];
            $sym       = config("booksy.currencies.{$advance->currency}.symbol", $advance->currency);
            $collected = $advance->collectedAmount();
            $remaining = $advance->remainingAmount();
            $pct       = $advance->progressPct();
            $nextInst  = $advance->installments->first(fn ($d) => $d->deduction_date->gt(now()->endOfMonth()));
        @endphp
        <div class="adv-row">
            @if($emp->image)
                <img src="{{ asset('storage/'.$emp->image) }}" class="adv-avatar">
            @else
                <div class="adv-avatar" style="background:{{ $color }}20;color:{{ $color }};">{{ mb_substr($emp->name_ar ?: $emp->name_en, 0, 1) }}</div>
            @endif

            <div style="flex:2 1 200px;min-width:0;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('company.employees.show', $emp) }}" class="fw-bold tx-13" style="color:inherit;text-decoration:none;">
                        {{ $emp->name_ar ?: $emp->name_en }}
                    </a>
                    @if($advance->isSettled())
                        <span class="adv-pill" style="background:rgba(34,197,94,.12);color:#22c55e;">✓ {{ __('Settled') }}</span>
                    @else
                        <span class="adv-pill" style="background:rgba(245,158,11,.12);color:#f59e0b;">{{ __('Active') }}</span>
                    @endif
                </div>
                <div class="tx-11 text-muted mt-1">
                    📅 {{ $advance->advance_date->format('d/m/Y') }}
                    · {{ $advance->installments_count }} {{ __('installment(s)') }} × {{ number_format($advance->installment_amount, 0) }} {{ $sym }}
                    @if($advance->notes) · 💬 {{ \Illuminate\Support\Str::limit($advance->notes, 40) }} @endif
                </div>
            </div>

            <div style="flex:1 1 160px;min-width:140px;">
                <div class="d-flex justify-content-between tx-11 mb-1">
                    <span class="text-muted">{{ __('Repaid') }} {{ number_format($collected, 0) }} {{ $sym }}</span>
                    <span class="fw-bold" style="color:{{ $remaining > 0 ? '#f59e0b' : '#22c55e' }};">{{ $pct }}%</span>
                </div>
                <div class="adv-bar-bg"><div class="adv-bar-fill" style="width:{{ $pct }}%;"></div></div>
                @if($nextInst)
                <div class="tx-10 text-muted mt-1" style="font-size:10px;">
                    ⏭️ {{ __('Next installment') }}: {{ $nextInst->deduction_date->translatedFormat('M Y') }}
                </div>
                @endif
            </div>

            <div class="text-end" style="min-width:90px;">
                <div style="font-size:16px;font-weight:900;color:#f59e0b;">{{ number_format($advance->amount, 0) }} <span style="font-size:10px;opacity:.6;">{{ $sym }}</span></div>
                <div style="font-size:10px;opacity:.5;">{{ __('Remaining') }}: {{ number_format($remaining, 0) }} {{ $sym }}</div>
            </div>

            @php $futureInstallments = $advance->installments->filter(fn ($d) => $d->deduction_date->gt(now()->endOfMonth()))->count(); @endphp
            <button type="button" class="btn btn-sm rounded-pill" style="background:rgba(239,68,68,.08);color:#ef4444;border:none;"
                    title="{{ __('Delete') }}"
                    onclick="openDeleteAdvanceModal({{ $advance->id }}, '{{ addslashes($emp->name_ar ?: $emp->name_en) }}', '{{ number_format($advance->amount, 0) }} {{ $sym }}', '{{ number_format($remaining, 0) }} {{ $sym }}', {{ $futureInstallments }})">
                <i data-feather="trash-2" style="width:12px;height:12px;"></i>
            </button>
        </div>
        @empty
        <div class="bk-empty py-5 text-center" style="opacity:.5;">
            <div style="font-size:36px;margin-bottom:8px;">💵</div>
            <p class="mb-0">{{ __('No advances yet — grant the first one from the button above.') }}</p>
        </div>
        @endforelse
    </div>
</div>

@if($advances->hasPages())
<div class="mt-3">{{ $advances->links() }}</div>
@endif

{{-- Delete Advance Modal --}}
<div class="modal fade" id="deleteAdvanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content rounded-4" style="background:var(--card-bg, #1a1f2e);">
            <form method="POST" id="deleteAdvanceForm">
                @csrf @method('DELETE')
                <div class="modal-body text-center p-4">
                    <div style="font-size:42px;margin-bottom:10px;">🗑️</div>
                    <h6 class="fw-bold mb-1">{{ __('Delete this advance?') }}</h6>
                    <p class="text-muted small mb-3" id="del-adv-emp"></p>

                    <div class="p-3 rounded-3 mb-3 text-start" style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.18);">
                        <div class="d-flex justify-content-between tx-12 mb-1">
                            <span class="text-muted">💵 {{ __('Advance amount') }}</span>
                            <strong id="del-adv-amount"></strong>
                        </div>
                        <div class="d-flex justify-content-between tx-12 mb-1">
                            <span class="text-muted">⏳ {{ __('Remaining') }}</span>
                            <strong id="del-adv-remaining" style="color:#f59e0b;"></strong>
                        </div>
                        <div class="d-flex justify-content-between tx-12">
                            <span class="text-muted">📅 {{ __('Installments to be removed') }}</span>
                            <strong id="del-adv-installments" style="color:#ef4444;"></strong>
                        </div>
                    </div>

                    <div class="tx-11 text-muted mb-3" style="opacity:.75;">
                        ⚠️ {{ __('Remaining installments will be removed from payroll.') }}
                    </div>

                    <label class="d-flex align-items-start gap-2 p-2 rounded-3 mb-3 text-start" style="background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.18);cursor:pointer;">
                        <input type="checkbox" name="delete_expense" value="1" class="form-check-input mt-1 flex-shrink-0" checked>
                        <span>
                            <span class="d-block tx-12 fw-semibold">🏦 {{ __('Also delete the cash-box expense record') }}</span>
                            <span class="d-block tx-11 text-muted">{{ __('Keep it unchecked if the money was really handed out and you only want to stop the installments.') }}</span>
                        </span>
                    </label>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">🗑️ {{ __('Delete') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('company.partials.advance-modal', [
    'advEmployees'          => $employees,
    'preselectedEmployeeId' => $preselectedEmployeeId,
    'autoOpen'              => (bool) $preselectedEmployeeId,
])

</div>
@endsection

@push('scripts')
<script>
function openDeleteAdvanceModal(id, empName, amount, remaining, installments) {
    document.getElementById('deleteAdvanceForm').action = '{{ url('company/advances') }}/' + id;
    document.getElementById('del-adv-emp').textContent = empName;
    document.getElementById('del-adv-amount').textContent = amount;
    document.getElementById('del-adv-remaining').textContent = remaining;
    document.getElementById('del-adv-installments').textContent = installments + ' {{ __('installment(s)') }}';
    new bootstrap.Modal(document.getElementById('deleteAdvanceModal')).show();
}


</script>
@endpush
