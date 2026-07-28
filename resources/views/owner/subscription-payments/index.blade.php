@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Subscription payments') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('owner.subscriptions.index') }}">{{ __('Subscriptions') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Payments') }}</li>
                </ol>
            </nav>
        </div>
        @can('owner-can', 'billing.record-payment')
        <button type="button" class="btn btn-primary btn-icon-text rounded-pill shadow-sm"
                data-bs-toggle="modal" data-bs-target="#modal-record-payment">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Record payment') }}
        </button>
        @endcan
    </div>

    @include('owner.partials.flash')

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('owner.subscription-payments.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label for="pay-company" class="form-label small fw-semibold mb-1">{{ __('Company') }}</label>
                    <select name="company_id" id="pay-company" class="form-select form-select-sm">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $companyOption)
                            <option value="{{ $companyOption->id }}" @selected((string) $filterCompanyId === (string) $companyOption->id)>{{ $companyOption->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="pay-method" class="form-label small fw-semibold mb-1">{{ __('Payment method') }}</label>
                    <select name="method" id="pay-method" class="form-select form-select-sm">
                        <option value="">{{ __('All methods') }}</option>
                        @foreach($methods as $key => $label)
                            <option value="{{ $key }}" @selected($filterMethod === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="pay-from" class="form-label small fw-semibold mb-1">{{ __('From date') }}</label>
                    <input type="date" name="from" id="pay-from" class="form-control form-control-sm" value="{{ $filterFrom }}">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="pay-to" class="form-label small fw-semibold mb-1">{{ __('To date') }}</label>
                    <input type="date" name="to" id="pay-to" class="form-control form-control-sm" value="{{ $filterTo }}">
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1">{{ __('Filter') }}</button>
                    @if($filterCompanyId !== '' || $filterMethod !== '' || $filterFrom !== '' || $filterTo !== '')
                        <a href="{{ route('owner.subscription-payments.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ── Totals for current filter ── --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-live="polite">
        <span class="text-muted tx-13">{{ __('Total for current filter:') }}</span>
        @forelse($totals as $currency => $total)
            <span class="badge rounded-pill bg-success-subtle text-success tx-13 px-3 py-2">
                {{ number_format($total, 2) }} {{ $currency }}
            </span>
        @empty
            <span class="badge rounded-pill bg-secondary-subtle text-secondary tx-13 px-3 py-2">0</span>
        @endforelse
        <span class="text-muted tx-13">({{ __(':count payment(s)', ['count' => $payments->total()]) }})</span>
    </div>

    {{-- ── Table ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Paid at') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Payment method') }}</th>
                            <th>{{ __('Extended') }}</th>
                            <th>{{ __('Recorded by') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr @class(['opacity-50' => $payment->isVoided()])>
                                <td class="ps-4 text-nowrap fw-semibold tx-13">
                                    {{ $payment->paid_at->format('Y-m-d') }}
                                    @if($payment->isVoided())
                                        <div>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger tx-11"
                                                  title="{{ $payment->void_reason }}">
                                                {{ __('Voided') }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->company)
                                        <a href="{{ route('owner.companies.show', $payment->company) }}" class="fw-semibold text-decoration-none">
                                            {{ $payment->company_label }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $payment->company_label }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary tx-11">{{ __('deleted') }}</span>
                                    @endif
                                </td>
                                <td>{{ $payment->plan_label ?? '—' }}</td>
                                <td class="text-nowrap fw-bold {{ $payment->isVoided() ? 'text-decoration-line-through' : '' }}">
                                    {{ number_format((float) $payment->amount, 2) }} <span class="tx-12 text-muted fw-normal">{{ $payment->currency }}</span>
                                    @if($payment->coupon_code)
                                        <div class="tx-12 fw-normal text-success" title="{{ __('Discount applied: :amount', ['amount' => number_format((float) $payment->discount_amount, 2).' '.$payment->currency]) }}">
                                            <i data-feather="tag" style="width:11px;height:11px;"></i>
                                            <span dir="ltr">{{ $payment->coupon_code }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-muted border">{{ $methods[$payment->method] ?? $payment->method }}</span>
                                </td>
                                <td class="text-nowrap tx-12">
                                    @if($payment->expires_after)
                                        <span dir="ltr">
                                            <span class="text-muted">{{ $payment->expires_before?->format('Y-m-d') ?? '—' }}</span>
                                            <span class="text-muted">→</span>
                                            <span class="fw-semibold">{{ $payment->expires_after->format('Y-m-d') }}</span>
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="tx-13">{{ $payment->owner?->name ?? '—' }}</td>
                                <td class="tx-13">
                                    @if($payment->reference || $payment->notes)
                                        <span title="{{ $payment->notes }}">{{ $payment->reference ?: str($payment->notes)->limit(30) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    @if(! $payment->isVoided())
                                        @can('owner-can', 'billing.record-payment')
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                data-bs-toggle="modal" data-bs-target="#modal-edit-payment"
                                                data-update-url="{{ route('owner.subscription-payments.update', $payment) }}"
                                                data-company="{{ $payment->company_label }}"
                                                data-amount="{{ $payment->amount }}"
                                                data-currency="{{ $payment->currency }}"
                                                data-method="{{ $payment->method }}"
                                                data-reference="{{ $payment->reference }}"
                                                data-paid-at="{{ $payment->paid_at->format('Y-m-d') }}"
                                                data-notes="{{ $payment->notes }}"
                                                aria-label="{{ __('Edit') }}">
                                            <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                                        </button>
                                        @endcan
                                        @can('owner-can', 'billing.void-payment')
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                data-bs-toggle="modal" data-bs-target="#modal-void-payment"
                                                data-void-url="{{ route('owner.subscription-payments.void', $payment) }}"
                                                data-summary="{{ $payment->company_label }} — {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}"
                                                aria-label="{{ __('Void payment') }}">
                                            <i data-feather="slash" style="width:13px;height:13px;"></i>
                                        </button>
                                        @endcan
                                    @else
                                        <span class="text-muted tx-12" title="{{ $payment->void_reason }}">{{ str($payment->void_reason)->limit(30) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="dollar-sign" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No payments recorded yet.') }}</p>
                                        @can('owner-can', 'billing.record-payment')
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill mt-1"
                                                data-bs-toggle="modal" data-bs-target="#modal-record-payment">
                                            {{ __('Record payment') }}
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $payments->links() }}</div>
        @endif
    </div>

    @can('owner-can', 'billing.record-payment')
        @include('owner.subscriptions._record-payment-modal')
    @endcan

    {{-- ── Edit payment modal ── --}}
    @can('owner-can', 'billing.record-payment')
    <div class="modal fade" id="modal-edit-payment" tabindex="-1"
         aria-labelledby="modal-edit-payment-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content border-0 shadow rounded-4" id="bk-edit-payment-form">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modal-edit-payment-title">{{ __('Edit payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted tx-13 mb-3" id="bk-edit-company"></p>
                    <div class="row g-3">
                        <div class="col-7">
                            <label for="bk-edit-amount" class="form-label fw-semibold">{{ __('Amount') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="number" name="amount" id="bk-edit-amount" class="form-control" min="0" step="0.01" required inputmode="decimal">
                        </div>
                        <div class="col-5">
                            <label for="bk-edit-currency" class="form-label fw-semibold">{{ __('Currency') }}</label>
                            <select name="currency" id="bk-edit-currency" class="form-select">
                                @foreach(['USD', 'SYP', 'EUR'] as $cur)
                                    <option value="{{ $cur }}">{{ $cur }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-7">
                            <label for="bk-edit-method" class="form-label fw-semibold">{{ __('Payment method') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <select name="method" id="bk-edit-method" class="form-select" required>
                                @foreach($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label for="bk-edit-date" class="form-label fw-semibold">{{ __('Paid at') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="date" name="paid_at" id="bk-edit-date" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="bk-edit-reference" class="form-label fw-semibold">{{ __('Reference') }}</label>
                            <input type="text" name="reference" id="bk-edit-reference" class="form-control" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label for="bk-edit-notes" class="form-label fw-semibold">{{ __('Notes') }}</label>
                            <textarea name="notes" id="bk-edit-notes" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-text">
                                {{ __('Company and plan cannot change — void the payment and record a new one instead.') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill">{{ __('Save changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- ── Void payment modal ── --}}
    @can('owner-can', 'billing.void-payment')
    <div class="modal fade" id="modal-void-payment" tabindex="-1"
         aria-labelledby="modal-void-payment-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content border-0 shadow rounded-4" id="bk-void-payment-form">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modal-void-payment-title">{{ __('Void payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="bk-void-summary"></p>
                    <div class="alert alert-warning border-0 rounded-3 py-2 tx-13 d-flex align-items-center gap-2">
                        <i data-feather="alert-triangle" style="width:16px;height:16px;" class="flex-shrink-0"></i>
                        <span>{{ __('The payment stays in the ledger but is excluded from totals. If it produced the current expiry date, the extension is reverted.') }}</span>
                    </div>
                    <label for="bk-void-reason" class="form-label fw-semibold">
                        {{ __('Reason') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <textarea name="void_reason" id="bk-void-reason" class="form-control" rows="3" maxlength="500" required></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger rounded-pill">{{ __('Void payment') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const editModal = document.getElementById('modal-edit-payment');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const btn  = event.relatedTarget;
            const form = document.getElementById('bk-edit-payment-form');
            if (!btn) return;

            form.action = btn.dataset.updateUrl;
            document.getElementById('bk-edit-company').textContent   = btn.dataset.company;
            document.getElementById('bk-edit-amount').value          = btn.dataset.amount;
            document.getElementById('bk-edit-currency').value        = btn.dataset.currency;
            document.getElementById('bk-edit-method').value          = btn.dataset.method;
            document.getElementById('bk-edit-reference').value       = btn.dataset.reference || '';
            document.getElementById('bk-edit-date').value            = btn.dataset.paidAt;
            document.getElementById('bk-edit-notes').value           = btn.dataset.notes || '';
        });
        editModal.addEventListener('shown.bs.modal', () => document.getElementById('bk-edit-amount').focus());
    }

    const voidModal = document.getElementById('modal-void-payment');
    if (voidModal) {
        voidModal.addEventListener('show.bs.modal', (event) => {
            const btn = event.relatedTarget;
            if (!btn) return;

            document.getElementById('bk-void-payment-form').action = btn.dataset.voidUrl;
            document.getElementById('bk-void-summary').textContent = btn.dataset.summary;
            document.getElementById('bk-void-reason').value = '';
        });
        voidModal.addEventListener('shown.bs.modal', () => document.getElementById('bk-void-reason').focus());
    }
})();
</script>
@endpush

@endsection
