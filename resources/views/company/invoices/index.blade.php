@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Invoices') }}</h4>
            <p class="text-muted mb-0 tx-13">{{ __('All issued invoices for your business') }}</p>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- Filter bar --}}
    <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i data-feather="search" style="width:14px;height:14px;color:#C9A227;"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="{{ __('Invoice # / customer / phone…') }}"
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-sm-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach(['draft' => __('Draft'), 'issued' => __('Issued'), 'paid' => __('Paid'), 'partial' => __('Partial'), 'refunded' => __('Refunded'), 'void' => __('Void')] as $val => $lbl)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3">
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('All branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 d-flex gap-2">
                    <button class="btn btn-primary rounded-pill flex-fill">{{ __('Filter') }}</button>
                    @if(request()->hasAny(['search','status','branch_id']))
                        <a href="{{ route('company.invoices.index') }}" class="btn btn-outline-secondary rounded-pill px-3" title="{{ __('Clear') }}">
                            <i data-feather="x" style="width:14px;height:14px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- List --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            {{-- Column headers --}}
            <div class="px-4 py-2 border-bottom" style="border-color:rgba(255,255,255,.06)!important;">
                <div class="row gx-3 align-items-center">
                    <div class="col-2"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Invoice #') }}</span></div>
                    <div class="col-3"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Customer') }}</span></div>
                    <div class="col-2"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Branch') }}</span></div>
                    <div class="col-2 text-end"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Total') }}</span></div>
                    <div class="col-2"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Status') }}</span></div>
                    <div class="col-1"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Date') }}</span></div>
                </div>
            </div>

            @forelse($invoices as $inv)
            <a href="{{ route('company.invoices.show', $inv) }}"
               class="d-block px-4 py-3 bk-table-row text-decoration-none"
               style="border-bottom:1px solid rgba(255,255,255,.04);">
                <div class="row gx-3 align-items-center">
                    <div class="col-2">
                        <span class="fw-bold" style="color:#C9A227;font-size:.84rem;">{{ $inv->invoice_number }}</span>
                    </div>
                    <div class="col-3">
                        <div class="fw-semibold tx-13">{{ $inv->customer_name ?? '—' }}</div>
                        @if($inv->customer_phone)
                            <div class="text-muted tx-11">{{ $inv->customer_phone }}</div>
                        @endif
                    </div>
                    <div class="col-2 tx-13 text-muted">{{ $inv->branch?->localizedName() ?? '—' }}</div>
                    <div class="col-2 text-end fw-bold tx-13">
                        {{ number_format((float)$inv->total, 2) }}
                        <span class="tx-11 text-muted fw-normal">{{ $inv->currency }}</span>
                    </div>
                    <div class="col-2">
                        <span class="bk-badge bk-inv-status-{{ $inv->status }}">{{ __(ucfirst($inv->status)) }}</span>
                    </div>
                    <div class="col-1 tx-11 text-muted">
                        {{ $inv->created_at->format('d M') }}<br>
                        <span style="font-size:.68rem;">{{ $inv->created_at->format('Y') }}</span>
                    </div>
                </div>
            </a>
            @empty
            <div class="bk-empty py-5">
                <div class="bk-empty-ic mb-3">
                    <i data-feather="file-text" style="width:24px;height:24px;"></i>
                </div>
                <p>{{ __('No invoices found.') }}</p>
                <p class="tx-12" style="color:rgba(255,255,255,.2);">{{ __('Invoices are created automatically when an appointment is completed.') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
