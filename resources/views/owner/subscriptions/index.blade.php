@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Subscriptions') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Subscriptions') }}</li>
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

    {{-- ── Stat cards ── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="trending-up" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('MRR (est.)') }}</div>
                        @forelse($stats['mrr'] as $currency => $value)
                            <div class="fw-bold tx-16 text-nowrap">{{ number_format($value, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                        @empty
                            <div class="fw-bold tx-16">0</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="dollar-sign" style="width:20px;height:20px;"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Payments this month') }}</div>
                        @forelse($stats['month_payments'] as $currency => $value)
                            <div class="fw-bold tx-16 text-nowrap">{{ number_format($value, 0) }} <span class="tx-12 text-muted">{{ $currency }}</span></div>
                        @empty
                            <div class="fw-bold tx-16">0</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="check-circle" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Active subscriptions') }}</div>
                        <div class="fw-bold tx-20">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="clock" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Expiring within 7 days') }}</div>
                        <div class="fw-bold tx-20">{{ $stats['expiring_soon'] }}</div>
                        @if($stats['expired'] + $stats['grace'] > 0)
                            <div class="tx-12 text-danger">{{ __(':count expired', ['count' => $stats['expired'] + $stats['grace']]) }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('owner.subscriptions.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-4">
                    <label for="sub-q" class="form-label small fw-semibold mb-1">{{ __('Search') }}</label>
                    <input type="search" name="q" id="sub-q" class="form-control form-control-sm"
                           value="{{ $q }}" placeholder="{{ __('Company name or email…') }}">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="sub-state" class="form-label small fw-semibold mb-1">{{ __('State') }}</label>
                    <select name="state" id="sub-state" class="form-select form-select-sm">
                        <option value="">{{ __('All states') }}</option>
                        <option value="active" @selected($filterState === 'active')>{{ __('Active') }}</option>
                        <option value="expiring_soon" @selected($filterState === 'expiring_soon')>{{ __('Expiring soon') }}</option>
                        <option value="grace" @selected($filterState === 'grace')>{{ __('In grace period') }}</option>
                        <option value="expired" @selected($filterState === 'expired')>{{ __('Expired') }}</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="sub-plan" class="form-label small fw-semibold mb-1">{{ __('Plan') }}</label>
                    <select name="plan_id" id="sub-plan" class="form-select form-select-sm">
                        <option value="">{{ __('All plans') }}</option>
                        @foreach($plans as $planOption)
                            <option value="{{ $planOption->id }}" @selected((string) $filterPlanId === (string) $planOption->id)>{{ $planOption->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1">{{ __('Filter') }}</button>
                    @if($q !== '' || $filterState !== '' || $filterPlanId !== '')
                        <a href="{{ route('owner.subscriptions.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Company') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Expires at') }}</th>
                            <th>{{ __('State') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            @php
                                $state = $company->subscription_state;
                                [$chipClass, $chipIcon, $chipLabel] = match ($state) {
                                    'active'        => ['bg-success-subtle text-success', 'check-circle', __('Active')],
                                    'expiring_soon' => ['bg-warning-subtle text-warning', 'clock', __('Expiring soon')],
                                    'grace'         => ['bg-warning-subtle text-warning', 'alert-triangle', __('In grace period')],
                                    'expired'       => ['bg-danger-subtle text-danger', 'x-circle', __('Expired')],
                                    default         => ['bg-secondary-subtle text-secondary', 'minus-circle', __('No plan')],
                                };
                                $daysLeft = $company->plan_expires_at?->startOfDay()->diffInDays(today(), false);
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('owner.companies.show', $company) }}" class="fw-semibold text-decoration-none">
                                        {{ $company->localizedName() }}
                                    </a>
                                    <div class="text-muted tx-12">{{ $company->email }}</div>
                                </td>
                                <td>{{ $company->plan?->localizedName() ?? '—' }}</td>
                                <td class="text-nowrap">
                                    @if($company->plan)
                                        {{ number_format((float) $company->plan->price, 0) }} <span class="tx-12 text-muted">{{ $company->plan->currency }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($company->plan_expires_at)
                                        <div class="fw-semibold tx-13">{{ $company->plan_expires_at->format('Y-m-d') }}</div>
                                        <div class="tx-12 {{ $daysLeft > 0 ? 'text-danger' : 'text-muted' }}">
                                            @if($daysLeft > 0)
                                                {{ __(':days day(s) ago', ['days' => (int) $daysLeft]) }}
                                            @else
                                                {{ __('in :days day(s)', ['days' => (int) abs($daysLeft)]) }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">{{ __('Unlimited') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $chipClass }} d-inline-flex align-items-center gap-1">
                                        <i data-feather="{{ $chipIcon }}" style="width:12px;height:12px;"></i>
                                        {{ $chipLabel }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    @can('owner-can', 'billing.record-payment')
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                            data-bs-toggle="modal" data-bs-target="#modal-record-payment"
                                            data-company-id="{{ $company->id }}">
                                        <i data-feather="dollar-sign" style="width:13px;height:13px;" class="me-1"></i>{{ __('Record payment') }}
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="credit-card" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No subscribed companies yet. Assign a plan from the company page.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('owner-can', 'billing.record-payment')
        @include('owner.subscriptions._record-payment-modal', ['companies' => $modalCompanies])
    @endcan
</div>

@endsection
