@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Revenue report') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Revenue report') }}</li>
                </ol>
            </nav>
        </div>
        <span class="text-muted tx-13">{{ __('Last 12 months') }}</span>
    </div>

    @include('owner.partials.flash')

    {{-- ── Summary cards ── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="dollar-sign" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Revenue (12 mo)') }}</div>
                        @forelse($summary['revenue_12mo'] as $currency => $total)
                            <div class="fw-bold tx-16 text-nowrap">{{ number_format((float) $total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                        @empty
                            <div class="fw-bold tx-16">0</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="trending-up" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('This month') }}</div>
                        @forelse($summary['revenue_this'] as $currency => $total)
                            <div class="fw-bold tx-16 text-nowrap">{{ number_format((float) $total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                        @empty
                            <div class="fw-bold tx-16">0</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="hash" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Payments (12 mo)') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($summary['payments_12mo']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="refresh-cw" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Active subscriptions') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($summary['active_subs']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Monthly revenue table ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
            <h6 class="mb-0 fw-bold">{{ __('Monthly subscription revenue') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Month') }}</th>
                            <th style="min-width:220px;">{{ __('Payments') }}</th>
                            <th>{{ __('Revenue') }}</th>
                            <th class="pe-4">{{ __('Discounts given') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows->reverse() as $row)
                            <tr>
                                <td class="ps-4 fw-semibold text-nowrap tx-13">{{ $row['month']->translatedFormat('M Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;" role="progressbar"
                                             aria-valuenow="{{ $row['payments'] }}" aria-valuemin="0" aria-valuemax="{{ $maxPayments }}"
                                             aria-label="{{ __('Payments') }}: {{ $row['payments'] }}">
                                            <div class="progress-bar bg-warning" style="width: {{ (int) round($row['payments'] / $maxPayments * 100) }}%"></div>
                                        </div>
                                        <span class="fw-semibold tx-13" style="min-width:2ch;">{{ $row['payments'] }}</span>
                                    </div>
                                </td>
                                <td class="text-nowrap tx-13">
                                    @forelse($row['revenue'] as $currency => $total)
                                        <div>{{ number_format($total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td class="pe-4 text-nowrap tx-13">
                                    @forelse($row['discount']->filter() as $currency => $total)
                                        <div class="text-muted">−{{ number_format($total, 0) }} <span class="tx-12">{{ $currency }}</span></div>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ── Revenue by plan ── --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
                    <h6 class="mb-0 fw-bold">{{ __('Revenue by plan') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">{{ __('Plan') }}</th>
                                    <th>{{ __('Payments') }}</th>
                                    <th class="pe-4">{{ __('Revenue') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byPlan as $plan => $p)
                                    <tr>
                                        <td class="ps-4 fw-semibold tx-13">{{ $plan }}</td>
                                        <td class="tx-13">{{ number_format($p['payments']) }}</td>
                                        <td class="pe-4 text-nowrap tx-13">
                                            @foreach($p['revenue'] as $currency => $total)
                                                <div>{{ number_format($total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">{{ __('No payments recorded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Top companies ── --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
                    <h6 class="mb-0 fw-bold">{{ __('Top paying companies (12 mo)') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">{{ __('Company') }}</th>
                                    <th>{{ __('Payments') }}</th>
                                    <th>{{ __('Revenue') }}</th>
                                    <th class="pe-4">{{ __('Last payment') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCompanies as $c)
                                    <tr>
                                        <td class="ps-4 fw-semibold tx-13">
                                            @if($c['company_id'])
                                                <a href="{{ route('owner.companies.show', $c['company_id']) }}" class="text-decoration-none">{{ $c['company'] }}</a>
                                            @else
                                                {{ $c['company'] }}
                                            @endif
                                        </td>
                                        <td class="tx-13">{{ number_format($c['payments']) }}</td>
                                        <td class="text-nowrap tx-13">
                                            @foreach($c['revenue'] as $currency => $total)
                                                <div>{{ number_format($total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                                            @endforeach
                                        </td>
                                        <td class="pe-4 text-nowrap tx-13 text-muted">{{ \Illuminate\Support\Carbon::parse($c['last_paid'])->translatedFormat('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No payments recorded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
