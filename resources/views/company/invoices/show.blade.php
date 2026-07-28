@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-3 mb-1">
                <h4 class="fw-bold mb-0">{{ $invoice->invoice_number }}</h4>
                <span class="bk-badge bk-inv-status-{{ $invoice->status }}">
                    {{ __(ucfirst($invoice->status)) }}
                </span>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('company.invoices.index') }}">{{ __('Invoices') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('company.invoices.print', $invoice) }}" target="_blank"
               class="btn btn-outline-secondary rounded-pill px-4">
                <i data-feather="printer" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                {{ __('Print') }}
            </a>
            @if($invoice->appointment_id)
            <a href="{{ route('company.appointments.show', $invoice->appointment_id) }}"
               class="btn btn-outline-secondary rounded-pill px-4">
                <i data-feather="calendar" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                {{ __('Appointment') }} #{{ $invoice->appointment_id }}
            </a>
            @endif
            @if(!in_array($invoice->status, ['void']))
            <button class="btn btn-outline-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#voidModal">
                <i data-feather="slash" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                {{ __('Void') }}
            </button>
            @endif
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row g-4">

        {{-- ── Main Invoice ── --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                {{-- Invoice "stamp" header --}}
                <div class="p-4 pb-3" style="background:linear-gradient(135deg,rgba(75,93,52,.08),transparent);border-bottom:1px solid rgba(255,255,255,.06);">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <p class="text-muted tx-11 text-uppercase fw-bold mb-1" style="letter-spacing:.8px;">
                                {{ __('Billed to') }}
                            </p>
                            <h6 class="fw-bold mb-0">{{ $invoice->customer_name ?? '—' }}</h6>
                            @if($invoice->customer_phone)
                                <div class="text-muted tx-12 mt-1">
                                    <i data-feather="phone" style="width:11px;height:11px;margin-inline-end:4px;"></i>
                                    {{ $invoice->customer_phone }}
                                </div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="tx-11 text-muted">{{ __('Issued') }}</div>
                            <div class="fw-semibold tx-13">{{ $invoice->issued_at?->format('d M Y') ?? $invoice->created_at->format('d M Y') }}</div>
                            @if($invoice->paid_at)
                                <div class="tx-11 text-muted mt-1">{{ __('Paid') }}: {{ $invoice->paid_at->format('d M Y') }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Line items --}}
                <div class="p-4">
                    <div class="table-responsive">
                    <table class="table mb-0" style="font-size:.84rem;min-width:480px;">
                        <thead>
                            <tr>
                                <th class="ps-0">{{ __('Service / Item') }}</th>
                                <th>{{ __('Client') }}</th>
                                <th>{{ __('Staff') }}</th>
                                <th class="text-end pe-0">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td class="ps-0 fw-semibold">{{ $item->description }}</td>
                                <td class="text-muted">{{ $item->customer_name ?? '—' }}</td>
                                <td class="text-muted">{{ $item->employee_name ?? '—' }}</td>
                                <td class="text-end pe-0 fw-bold">
                                    {{ number_format((float)$item->total, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if($invoice->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end text-muted ps-0 border-0 pt-3">{{ __('Discount') }}</td>
                                <td class="text-end text-danger pe-0 border-0 pt-3 fw-semibold">
                                    − {{ number_format((float)$invoice->discount_amount, 2) }}
                                </td>
                            </tr>
                            @endif
                            @if($invoice->vat_rate > 0)
                            <tr>
                                <td colspan="3" class="text-end text-muted ps-0 border-0">
                                    {{ __('VAT') }} ({{ $invoice->vat_rate }}%)
                                </td>
                                <td class="text-end pe-0 border-0 fw-semibold">
                                    + {{ number_format((float)$invoice->vat_amount, 2) }}
                                </td>
                            </tr>
                            @endif
                            <tr class="border-top">
                                <td colspan="3" class="text-end ps-0 pt-3 fw-bold" style="font-size:1rem;">{{ __('Total') }}</td>
                                <td class="text-end pe-0 pt-3 fw-bold" style="font-size:1.1rem;color:var(--bk-accent);">
                                    {{ number_format((float)$invoice->total, 2) }}
                                    <span class="tx-13 fw-normal text-muted">{{ $invoice->currency }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
                </div>

                @if($invoice->notes)
                <div class="px-4 pb-4">
                    <div style="background:rgba(255,255,255,.03);border-radius:10px;padding:12px 16px;border:1px solid rgba(255,255,255,.06);">
                        <p class="tx-11 text-muted text-uppercase fw-bold mb-1" style="letter-spacing:.6px;">{{ __('Notes') }}</p>
                        <p class="tx-13 mb-0">{{ $invoice->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Sidebar ── --}}
        <div class="col-lg-4">

            {{-- Update status --}}
            @if(!in_array($invoice->status, ['void']))
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <p class="tx-11 text-muted text-uppercase fw-bold mb-3" style="letter-spacing:.8px;">
                        {{ __('Update Status') }}
                    </p>
                    <form method="POST" action="{{ route('company.invoices.update-status', $invoice) }}">
                        @csrf @method('PATCH')
                        <div class="mb-2">
                            <label class="form-label tx-12 fw-semibold">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                @foreach(['draft' => __('Draft'), 'issued' => __('Issued'), 'paid' => __('Paid'), 'partial' => __('Partial'), 'refunded' => __('Refunded')] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $invoice->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label tx-12 fw-semibold">{{ __('Payment method') }}</label>
                            <select name="payment_method" class="form-select">
                                <option value="">{{ __('Select…') }}</option>
                                @foreach(['cash' => __('Cash'), 'card' => __('Card'), 'transfer' => __('Transfer'), 'mixed' => __('Mixed')] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $invoice->payment_method === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary rounded-pill w-100 fw-semibold">
                            <i data-feather="save" style="width:13px;height:13px;margin-inline-end:4px;"></i>
                            {{ __('Update') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Details --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <p class="tx-11 text-muted text-uppercase fw-bold mb-3" style="letter-spacing:.8px;">
                        {{ __('Details') }}
                    </p>
                    <dl class="row mb-0 tx-13">
                        <dt class="col-5 text-muted fw-normal">{{ __('Branch') }}</dt>
                        <dd class="col-7 fw-semibold">{{ $invoice->branch?->localizedName() ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">{{ __('Currency') }}</dt>
                        <dd class="col-7 fw-semibold">{{ $invoice->currency }}</dd>

                        @if($invoice->payment_method)
                        <dt class="col-5 text-muted fw-normal">{{ __('Payment') }}</dt>
                        <dd class="col-7 fw-semibold">{{ __(ucfirst($invoice->payment_method)) }}</dd>
                        @endif

                        <dt class="col-5 text-muted fw-normal">{{ __('Created by') }}</dt>
                        <dd class="col-7">{{ $invoice->created_by_name ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">{{ __('Created') }}</dt>
                        <dd class="col-7 tx-12">{{ $invoice->created_at->format('d M Y · H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Tracking --}}
    <div class="row g-4 mt-0">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">💰 {{ __('Invoice Payments') }}</h6>

                    @if($invoice->remaining > 0 && !in_array($invoice->status, ['paid', 'void', 'refunded']))
                        <form method="POST" action="{{ route('company.invoices.pay', $invoice) }}" class="mb-3 p-3 rounded" style="background:var(--bs-tertiary-bg);">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-sm-4">
                                    <label class="form-label small fw-semibold">{{ __('Amount') }}</label>
                                    <input type="number" step="0.01" name="amount" value="{{ $invoice->remaining }}"
                                        max="{{ $invoice->remaining }}" min="0.01" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label small fw-semibold">{{ __('Method') }}</label>
                                    <select name="payment_method" class="form-select form-select-sm" required>
                                        <option value="cash">{{ __('Cash') }}</option>
                                        <option value="card">{{ __('Card') }}</option>
                                        <option value="transfer">{{ __('Bank transfer') }}</option>
                                    </select>
                                </div>
                                <div class="col-8 col-sm-3">
                                    <label class="form-label small fw-semibold">{{ __('Notes') }}</label>
                                    <input type="text" name="notes" class="form-control form-control-sm">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <button class="btn btn-sm btn-success w-100">{{ __('Pay') }}</button>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                {{ __('Remaining balance') }}: <strong>{{ number_format($invoice->remaining, 0) }} {{ $invoice->currency }}</strong>
                            </small>
                        </form>
                    @endif

                    @if($invoice->payments && $invoice->payments->count())
                        <div class="table-responsive">
                        <table class="table table-sm mb-0" style="min-width:420px;">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('By') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments->sortByDesc('created_at') as $pmt)
                                    <tr>
                                        <td class="small">{{ $pmt->created_at->format('d/m H:i') }}</td>
                                        <td class="fw-bold text-success">+{{ number_format($pmt->amount, 0) }}</td>
                                        <td class="small">{{ __($pmt->payment_method) }}</td>
                                        <td class="small">{{ $pmt->recorded_by_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @else
                        <p class="text-muted text-center small mb-0">{{ __('No payments yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-4">
                    <div class="mb-2 text-muted small">{{ __('Total') }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($invoice->total, 0) }} {{ $invoice->currency }}</div>
                    <div class="d-flex justify-content-center gap-4 mt-3">
                        <div>
                            <div class="text-success fs-5 fw-bold">{{ number_format($invoice->amount_paid, 0) }}</div>
                            <div class="text-muted small">{{ __('Paid Amount') }}</div>
                        </div>
                        <div>
                            <div class="text-danger fs-5 fw-bold">{{ number_format($invoice->remaining, 0) }}</div>
                            <div class="text-muted small">{{ __('Remaining') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Void Modal --}}
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">{{ __('Void invoice') }}?</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-muted tx-13 pt-2">
                {{ __('This will permanently mark the invoice as void. This action cannot be undone.') }}
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <form method="POST" action="{{ route('company.invoices.void', $invoice) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-danger rounded-pill px-4">{{ __('Void') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
