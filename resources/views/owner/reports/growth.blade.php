@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Growth report') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Growth report') }}</li>
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
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="briefcase" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Companies (all time)') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($summary['companies_total']) }}</div>
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
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('New companies (12 mo)') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($summary['signups_12mo']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="users" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('New customers (12 mo)') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($summary['customers_12mo']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="dollar-sign" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Subscription revenue (12 mo)') }}</div>
                        @forelse($summary['revenue_12mo'] as $currency => $total)
                            <div class="fw-bold tx-16 text-nowrap">{{ number_format((float) $total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                        @empty
                            <div class="fw-bold tx-16">0</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Monthly table ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Month') }}</th>
                            <th style="min-width:220px;">{{ __('New companies') }}</th>
                            <th style="min-width:220px;">{{ __('New customers') }}</th>
                            <th>{{ __('Subscription revenue') }}</th>
                            <th class="pe-4">{{ __('GMV') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows->reverse() as $row)
                            <tr>
                                <td class="ps-4 fw-semibold text-nowrap tx-13">{{ $row['month']->translatedFormat('M Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;" role="progressbar"
                                             aria-valuenow="{{ $row['signups'] }}" aria-valuemin="0" aria-valuemax="{{ $maxSignups }}"
                                             aria-label="{{ __('New companies') }}: {{ $row['signups'] }}">
                                            <div class="progress-bar bg-primary" style="width: {{ (int) round($row['signups'] / $maxSignups * 100) }}%"></div>
                                        </div>
                                        <span class="fw-semibold tx-13" style="min-width:2ch;">{{ $row['signups'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;" role="progressbar"
                                             aria-valuenow="{{ $row['customers'] }}" aria-valuemin="0" aria-valuemax="{{ $maxCustomers }}"
                                             aria-label="{{ __('New customers') }}: {{ $row['customers'] }}">
                                            <div class="progress-bar bg-info" style="width: {{ (int) round($row['customers'] / $maxCustomers * 100) }}%"></div>
                                        </div>
                                        <span class="fw-semibold tx-13" style="min-width:2ch;">{{ $row['customers'] }}</span>
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
                                    @forelse($row['gmv'] as $currency => $total)
                                        <div>{{ number_format($total, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
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
</div>

@endsection
