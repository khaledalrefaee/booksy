@extends('company.dashboard')

@push('company-styles')
@include('company.cash._styles')
<style>
.archive-card {
    border-radius:14px; padding:16px; margin-bottom:12px;
    background:var(--card-bg, rgba(255,255,255,.03));
    border:1px solid rgba(255,255,255,.06);
    transition:all .15s;
}
.archive-card:hover { border-color:rgba(102,126,234,.3); }
.archive-card.voided { opacity:.5; border-color:rgba(239,68,68,.2); }
.status-pill {
    font-size:10px; font-weight:700; padding:2px 10px; border-radius:12px;
}
.status-open       { background:rgba(34,197,94,.15);  color:#22c55e; }
.status-closed     { background:rgba(245,158,11,.15); color:#f59e0b; }
.status-reconciled { background:rgba(102,126,234,.15); color:#667eea; }
.status-voided     { background:rgba(239,68,68,.15);  color:#ef4444; }
.action-btn {
    font-size:10px; font-weight:700; padding:3px 10px; border-radius:8px;
    border:none; cursor:pointer; transition:all .12s;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div style="font-size:11px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px;">
                {{ $branch->localizedName() }}
            </div>
            <h4 class="fw-bold mb-0">🗄 {{ __('Drawer Archive') }}</h4>
        </div>
        <a href="{{ route('company.branches.cash.index', $branch) }}"
           class="btn btn-sm rounded-pill px-3" style="background:rgba(102,126,234,.1);color:#667eea;font-weight:700;border:none;">
            ← {{ __('Back to Cash Register') }}
        </a>
    </div>

    {{-- Date filter --}}
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap mb-4">
        <input type="date" name="from" class="form-control form-control-sm rounded-pill" style="width:160px;"
               value="{{ request('from') }}" placeholder="{{ __('From') }}">
        <span style="opacity:.4;">—</span>
        <input type="date" name="to" class="form-control form-control-sm rounded-pill" style="width:160px;"
               value="{{ request('to') }}" placeholder="{{ __('To') }}">
        <button class="btn btn-sm rounded-pill px-3" style="background:#667eea;color:#fff;border:none;font-weight:600;">
            {{ __('Filter') }}
        </button>
        @if(request('from') || request('to'))
        <a href="{{ route('company.branches.cash.drawer.archive', $branch) }}" class="btn btn-sm rounded-pill px-3"
           style="background:rgba(255,255,255,.07);font-weight:600;">{{ __('Clear') }}</a>
        @endif
    </form>

    @include('company.partials.flash')

    @if($sessions->isEmpty())
    <div class="text-center py-5" style="opacity:.4;">
        <div style="font-size:40px;margin-bottom:12px;">🗄</div>
        <div style="font-size:14px;">{{ __('No drawer sessions found.') }}</div>
    </div>
    @else

    @foreach($sessions as $ds)
    <div class="archive-card {{ $ds->isVoided() ? 'voided' : '' }}">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="status-pill status-{{ $ds->status }}">
                        {{ __(ucfirst($ds->status)) }}
                    </span>
                    <span style="font-size:12px;font-weight:700;">
                        #{{ $ds->id }}
                    </span>
                </div>
                <div style="font-size:.78rem;opacity:.6;">
                    {{ $ds->opened_at?->translatedFormat('l، d F Y — H:i') }}
                    @if($ds->closed_at) → {{ $ds->closed_at->format('H:i') }} @endif
                </div>
            </div>

            {{-- Action buttons --}}
            @if(!$ds->isVoided())
            <div class="d-flex gap-2 flex-wrap">
                @if($ds->isClosed())
                <button class="action-btn" style="background:rgba(102,126,234,.1);color:#667eea;"
                        data-bs-toggle="modal" data-bs-target="#reconcileArchiveModal-{{ $ds->id }}">
                    📋 {{ __('Reconcile') }}
                </button>
                @endif

                @if($ds->isClosed() || $ds->isReconciled())
                <button class="action-btn" style="background:rgba(34,197,94,.1);color:#22c55e;"
                        data-bs-toggle="modal" data-bs-target="#reopenModal-{{ $ds->id }}">
                    🔓 {{ __('Reopen') }}
                </button>
                @endif

                <button class="action-btn" style="background:rgba(255,255,255,.05);color:var(--text-color);opacity:.7;"
                        data-bs-toggle="modal" data-bs-target="#editNotesModal-{{ $ds->id }}">
                    ✏️ {{ __('Edit notes') }}
                </button>

                <button class="action-btn" style="background:rgba(239,68,68,.1);color:#ef4444;"
                        data-bs-toggle="modal" data-bs-target="#voidModal-{{ $ds->id }}">
                    🚫 {{ __('Void') }}
                </button>
            </div>
            @endif
        </div>

        {{-- Voided banner --}}
        @if($ds->isVoided())
        <div class="p-2 rounded-3 mb-2 d-flex align-items-center gap-2" style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.15);font-size:.78rem;">
            <span style="color:#ef4444;font-weight:700;">🚫 {{ __('Voided') }}</span>
            @if($ds->void_reason)
            <span style="opacity:.6;">— {{ $ds->void_reason }}</span>
            @endif
            @if($ds->voided_by_name)
            <span style="opacity:.5;margin-inline-start:auto;">{{ $ds->voided_by_name }} · {{ $ds->voided_at?->diffForHumans() }}</span>
            @endif
        </div>
        @endif

        {{-- Balances --}}
        <div class="d-flex gap-3 flex-wrap mb-2">
            @foreach($ds->balances as $bal)
            <div class="p-2 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);min-width:140px;">
                <div style="font-size:10px;font-weight:700;opacity:.5;">{{ $bal->currency }}</div>
                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                    <span style="opacity:.5;">{{ __('Open') }}:</span>
                    <strong>{{ number_format($bal->opening_amount, 2) }}</strong>
                </div>
                @if($bal->closing_amount !== null)
                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                    <span style="opacity:.5;">{{ __('Close') }}:</span>
                    <strong>{{ number_format($bal->closing_amount, 2) }}</strong>
                </div>
                @if($bal->expected_amount !== null)
                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                    <span style="opacity:.5;">{{ __('Expected') }}:</span>
                    <span>{{ number_format($bal->expected_amount, 2) }}</span>
                </div>
                @endif
                @if($bal->variance !== null)
                <div class="d-flex justify-content-between" style="font-size:.78rem;font-weight:700;color:{{ $bal->variance == 0 ? '#22c55e' : ($bal->variance > 0 ? '#f59e0b' : '#ef4444') }};">
                    <span>{{ __('Variance') }}:</span>
                    <span>{{ $bal->variance > 0 ? '+' : '' }}{{ number_format($bal->variance, 2) }}</span>
                </div>
                @endif
                @endif
            </div>
            @endforeach
        </div>

        {{-- Operators --}}
        <div class="d-flex gap-4 flex-wrap" style="font-size:.75rem;opacity:.6;">
            @if($ds->opened_by_name)
            <span>🔓 {{ __('Opened by') }}: <strong>{{ $ds->opened_by_name }}</strong></span>
            @endif
            @if($ds->closed_by_name)
            <span>🔒 {{ __('Closed by') }}: <strong>{{ $ds->closed_by_name }}</strong></span>
            @endif
            @if($ds->reconciled_by_name)
            <span>📋 {{ __('Reconciled by') }}: <strong>{{ $ds->reconciled_by_name }}</strong></span>
            @endif
        </div>

        {{-- Reconcile info --}}
        @if($ds->isReconciled())
        @php $rMeta = \App\Models\CashDrawerSession::RECONCILE_REASONS[$ds->reconcile_reason] ?? null; @endphp
        <div class="mt-2 d-flex align-items-center gap-2" style="font-size:.75rem;">
            <span style="color:#22c55e;font-weight:700;">✓ {{ __('Reconciled') }}</span>
            @if($rMeta)
            <span style="color:{{ $rMeta['color'] }};font-weight:600;">{{ $rMeta['icon'] }} {{ __($rMeta['label_key']) }}</span>
            @endif
            @if($ds->reconcile_notes)
            <span style="opacity:.5;">— {{ $ds->reconcile_notes }}</span>
            @endif
        </div>
        @endif

        {{-- Notes --}}
        @if($ds->notes)
        <div class="mt-1" style="font-size:.72rem;opacity:.5;">📝 {{ $ds->notes }}</div>
        @endif
    </div>
    @endforeach

    {{ $sessions->links() }}

    {{-- ─── MODALS ─────────────────────────────────────────────────────────── --}}

    @foreach($sessions as $ds)
    @if($ds->isVoided()) @continue @endif

    {{-- Reopen Modal --}}
    @if($ds->isClosed() || $ds->isReconciled())
    <div class="modal fade tx-modal" id="reopenModal-{{ $ds->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <form method="POST" action="{{ route('company.branches.cash.drawer.reopen', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-body text-center p-4">
                        <div style="font-size:40px;margin-bottom:12px;">🔓</div>
                        <h6 class="fw-bold mb-2">{{ __('Reopen Drawer') }} #{{ $ds->id }}?</h6>
                        <p class="text-muted small mb-3">
                            {{ __('This will reset the closing data and set the drawer back to open. You can then close it again with the correct amounts.') }}
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-sm rounded-pill px-4"
                                    style="background:rgba(255,255,255,.07);font-weight:600;"
                                    data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-sm rounded-pill px-4 fw-bold"
                                    style="background:#22c55e;color:#fff;border:none;">
                                🔓 {{ __('Reopen') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Void Modal --}}
    <div class="modal fade tx-modal" id="voidModal-{{ $ds->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content">
                <form method="POST" action="{{ route('company.branches.cash.drawer.void', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">🚫 {{ __('Void Drawer') }} #{{ $ds->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="p-3 rounded-3 mb-3" style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.12);font-size:.82rem;">
                            ⚠️ {{ __('This session will be marked as voided and excluded from reports. The record will remain visible for audit purposes.') }}
                        </div>
                        <div class="mb-1">
                            <label class="f-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                            <input type="text" name="void_reason" class="f-input form-control" required
                                   placeholder="{{ __('e.g. Opened by mistake, duplicate session...') }}">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                            🚫 {{ __('Void') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Notes Modal --}}
    <div class="modal fade tx-modal" id="editNotesModal-{{ $ds->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content">
                <form method="POST" action="{{ route('company.branches.cash.drawer.update-notes', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">✏️ {{ __('Edit Notes') }} — #{{ $ds->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="f-label">{{ __('Session notes') }}</label>
                            <textarea name="notes" class="f-input form-control" rows="2"
                                      placeholder="{{ __('General notes about this session...') }}">{{ $ds->notes }}</textarea>
                        </div>
                        @if($ds->isReconciled())
                        <div class="mb-1">
                            <label class="f-label">{{ __('Reconciliation notes') }}</label>
                            <textarea name="reconcile_notes" class="f-input form-control" rows="2"
                                      placeholder="{{ __('Notes about the reconciliation...') }}">{{ $ds->reconcile_notes }}</textarea>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;">
                            ✔ {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reconcile Modal --}}
    @if($ds->isClosed())
    <div class="modal fade tx-modal" id="reconcileArchiveModal-{{ $ds->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content">
                <form method="POST" action="{{ route('company.branches.cash.drawer.reconcile', [$branch, $ds]) }}" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">📋 {{ __('Reconcile Drawer') }} #{{ $ds->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        @foreach($ds->balances as $bal)
                        @php $v = (float) ($bal->variance ?? 0); @endphp
                        <div class="p-3 rounded-3 mb-2 text-center" style="background:{{ $v == 0 ? 'rgba(34,197,94,.08)' : ($v > 0 ? 'rgba(245,158,11,.08)' : 'rgba(239,68,68,.08)') }};">
                            <div style="font-size:.72rem;opacity:.6;">{{ __('Variance') }} · {{ $bal->currency }}</div>
                            <div style="font-size:1.2rem;font-weight:800;color:{{ $v == 0 ? '#22c55e' : ($v > 0 ? '#f59e0b' : '#ef4444') }};">
                                {{ $v > 0 ? '+' : '' }}{{ number_format($v, 2) }} {{ $bal->currency }}
                            </div>
                        </div>
                        @endforeach

                        <div class="mb-3">
                            <label class="f-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(\App\Models\CashDrawerSession::RECONCILE_REASONS as $rKey => $rMeta)
                                <label style="cursor:pointer;">
                                    <input type="radio" name="reconcile_reason" value="{{ $rKey }}" class="d-none reconcile-reason-radio" required>
                                    <div class="pm-card" style="--pm-color:{{ $rMeta['color'] }};">
                                        <span>{{ $rMeta['icon'] }}</span>
                                        {{ __($rMeta['label_key']) }}
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="f-label">{{ __('Notes') }} <span style="font-weight:400;opacity:.5;">({{ __('optional') }})</span></label>
                            <textarea name="reconcile_notes" class="f-input form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm rounded-pill px-5 fw-bold" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;">
                            ✔ {{ __('Reconcile') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @endforeach

    @endif
</div>
@endsection

@push('scripts')
<script>
window.disableSubmit = function(form) {
    form.querySelectorAll('button[type="submit"]').forEach(function(b) { b.disabled = true; b.style.opacity = '.5'; });
};
document.querySelectorAll('.reconcile-reason-radio').forEach(function (radio) {
    radio.addEventListener('change', function () {
        var modal = this.closest('.modal');
        modal.querySelectorAll('.pm-card').forEach(function (c) { c.classList.remove('active'); });
        this.closest('label').querySelector('.pm-card').classList.add('active');
    });
});
</script>
@endpush
