@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Invoices') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Invoices') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('owner.partials.flash')

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('owner.invoices.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label for="inv-company" class="form-label small fw-semibold mb-1">{{ __('Company') }}</label>
                    <select name="company_id" id="inv-company" class="form-select form-select-sm">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $companyOption)
                            <option value="{{ $companyOption->id }}" @selected((string) $filterCompanyId === (string) $companyOption->id)>{{ $companyOption->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="inv-status" class="form-label small fw-semibold mb-1">{{ __('Status') }}</label>
                    <select name="status" id="inv-status" class="form-select form-select-sm">
                        <option value="">{{ __('All states') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected($filterStatus === $status)>{{ __($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="inv-from" class="form-label small fw-semibold mb-1">{{ __('From date') }}</label>
                    <input type="date" name="from" id="inv-from" class="form-control form-control-sm" value="{{ $filterFrom }}">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="inv-to" class="form-label small fw-semibold mb-1">{{ __('To date') }}</label>
                    <input type="date" name="to" id="inv-to" class="form-control form-control-sm" value="{{ $filterTo }}">
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1">{{ __('Filter') }}</button>
                    @if($filterCompanyId !== '' || $filterStatus !== '' || $filterFrom !== '' || $filterTo !== '')
                        <a href="{{ route('owner.invoices.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ── GMV totals for current filter ── --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-live="polite">
        <span class="text-muted tx-13">{{ __('Total for current filter:') }}</span>
        @forelse($totals as $row)
            <span class="badge rounded-pill bg-primary-subtle text-primary tx-13 px-3 py-2">
                {{ __('GMV') }}: {{ number_format((float) $row->gmv, 0) }} {{ $row->currency }}
            </span>
            <span class="badge rounded-pill bg-success-subtle text-success tx-13 px-3 py-2">
                {{ __('Collected') }}: {{ number_format((float) $row->paid, 0) }} {{ $row->currency }}
            </span>
        @empty
            <span class="badge rounded-pill bg-secondary-subtle text-secondary tx-13 px-3 py-2">0</span>
        @endforelse
        <span class="text-muted tx-13">({{ __(':count invoice(s)', ['count' => $invoices->total()]) }})</span>
    </div>

    {{-- ── Table ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Invoice #') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Paid') }}</th>
                            <th class="pe-4">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php
                                $statusBadge = match ($invoice->status) {
                                    'paid'      => 'bg-success-subtle text-success',
                                    'partial'   => 'bg-warning-subtle text-warning',
                                    'unpaid'    => 'bg-danger-subtle text-danger',
                                    'refunded', 'voided', 'cancelled' => 'bg-secondary-subtle text-secondary',
                                    default     => 'bg-info-subtle text-info',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold tx-13">{{ $invoice->invoice_number }}</td>
                                <td class="text-nowrap text-muted tx-13">{{ $invoice->issued_at?->format('Y-m-d') ?? $invoice->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    @if($invoice->company)
                                        <a href="{{ route('owner.companies.show', $invoice->company) }}" class="text-decoration-none fw-semibold">{{ $invoice->company->localizedName() }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="tx-13">{{ $invoice->branch?->localizedName() ?? '—' }}</td>
                                <td class="tx-13">{{ $invoice->customer_name ?: '—' }}</td>
                                <td class="text-nowrap fw-bold">{{ number_format((float) $invoice->total, 0) }} <span class="tx-12 text-muted fw-normal">{{ $invoice->currency }}</span></td>
                                <td class="text-nowrap tx-13">{{ number_format((float) $invoice->amount_paid, 0) }}</td>
                                <td class="pe-4"><span class="badge rounded-pill {{ $statusBadge }}">{{ __($invoice->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="file-text" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No invoices found.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>

@endsection
