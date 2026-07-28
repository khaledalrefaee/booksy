@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Customers') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Customers') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('owner.partials.flash')

    {{-- ── Stat cards ── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="users" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Total customers') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="check-circle" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Verified phones') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($stats['verified']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="user-x" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Banned') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($stats['banned']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('owner.customers.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-5">
                    <label for="cust-q" class="form-label small fw-semibold mb-1">{{ __('Search') }}</label>
                    <input type="search" name="q" id="cust-q" class="form-control form-control-sm"
                           value="{{ $q }}" placeholder="{{ __('Name or phone…') }}">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="cust-banned" class="form-label small fw-semibold mb-1">{{ __('State') }}</label>
                    <select name="banned" id="cust-banned" class="form-select form-select-sm">
                        <option value="">{{ __('All states') }}</option>
                        <option value="0" @selected($filterBanned === '0')>{{ __('Active') }}</option>
                        <option value="1" @selected($filterBanned === '1')>{{ __('Banned') }}</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1">{{ __('Filter') }}</button>
                    @if($q !== '' || $filterBanned !== '')
                        <a href="{{ route('owner.customers.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">{{ __('Reset') }}</a>
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
                            <th class="ps-4">{{ __('Customer') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Appointments') }}</th>
                            <th>{{ __('Loyalty points') }}</th>
                            <th>{{ __('Joined') }}</th>
                            <th class="pe-4">{{ __('State') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-semibold">{{ $customer->name ?: '—' }}</span>
                                    @if($customer->tag)
                                        <span class="badge rounded-pill bg-light text-muted border tx-11">{{ $customer->tag }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap" dir="ltr">
                                    {{ $customer->phone }}
                                    @if($customer->phone_verified_at)
                                        <i data-feather="check-circle" class="text-success" style="width:13px;height:13px;" title="{{ __('Verified phones') }}"></i>
                                    @endif
                                </td>
                                <td>{{ $customer->appointments_count }}</td>
                                <td>{{ $customer->loyalty_points ?? 0 }}</td>
                                <td class="text-muted tx-13">{{ $customer->created_at?->format('Y-m-d') }}</td>
                                <td class="pe-4">
                                    @if($customer->is_banned)
                                        <span class="badge rounded-pill bg-danger-subtle text-danger" title="{{ $customer->ban_reason }}">{{ __('Banned') }}</span>
                                    @else
                                        <span class="badge rounded-pill bg-success-subtle text-success">{{ __('Active') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="users" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No customers found.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($customers->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $customers->links() }}</div>
        @endif
    </div>
</div>

@endsection
