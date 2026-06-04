@extends('owner.dashboard')

@section('content')
@php
    $totalAppt = (int) ($stats['appointments_total'] ?? 0);
    $pendingAppt = (int) ($stats['appointments_pending'] ?? 0);
    $companiesCount = (int) ($stats['companies'] ?? 0);
    $branchesCount = (int) ($stats['branches'] ?? 0);
    $servicesCount = (int) ($stats['services'] ?? 0);
    $waitlistWaiting = (int) ($stats['waitlist_waiting'] ?? 0);
    $pendingRatio = $totalAppt > 0 ? round(100 * $pendingAppt / $totalAppt, 1) : null;
@endphp

<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
      <div>
        <h4 class="mb-1 mb-md-0">{{ __('Platform dashboard') }}</h4>
        <p class="text-muted tx-13 mb-3 mb-md-0">{{ __('Manage all companies, branches, and bookings from one place.') }}</p>
      </div>
      <div class="d-flex align-items-center flex-wrap text-nowrap">
        <div class="input-group flatpickr wd-200 me-2 mb-2 mb-md-0" id="dashboardDate">
          <span class="input-group-text input-group-addon bg-transparent border-primary" data-toggle><i data-feather="calendar" class="text-primary"></i></span>
          <input type="text" class="form-control bg-transparent border-primary" placeholder="{{ __('Select date') }}" data-input>
        </div>
        <a href="{{ route('owner.appointments.index') }}" class="btn btn-outline-primary btn-icon-text me-2 mb-2 mb-md-0">
          <i class="btn-icon-prepend" data-feather="calendar"></i>
          {{ __('Appointments') }}
        </a>
        <a href="{{ route('owner.branches.index') }}" class="btn btn-primary btn-icon-text mb-2 mb-md-0">
          <i class="btn-icon-prepend" data-feather="map-pin"></i>
          {{ __('Branches') }}
        </a>
      </div>
    </div>

    @include('owner.partials.flash')

    <div class="row">
      <div class="col-12 col-xl-12 stretch-card">
        <div class="row flex-grow-1">
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">{{ __('All appointments') }}</h6>
                  <div class="dropdown mb-2">
                    <a type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.appointments.index') }}"><i data-feather="calendar" class="icon-sm me-2"></i> <span>{{ __('View appointments') }}</span></a>
                      <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.branches.index') }}"><i data-feather="map-pin" class="icon-sm me-2"></i> <span>{{ __('Branches') }}</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-5">
                    <h3 class="mb-2">{{ number_format($totalAppt) }}</h3>
                    <div class="d-flex align-items-baseline">
                        <p class="text-muted tx-13 mb-0">{{ __(':pending of :total pending', ['pending' => number_format($pendingAppt), 'total' => number_format($totalAppt)]) }}</p>
                    </div>
                  </div>
                  <div class="col-6 col-md-12 col-xl-7">
                    <div id="customersChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">{{ __('Pending appointments') }}</h6>
                  <div class="dropdown mb-2">
                    <a type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.appointments.index', ['status' => 'pending']) }}"><i data-feather="clock" class="icon-sm me-2"></i> <span>{{ __('Filter pending') }}</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-5">
                    <h3 class="mb-2">{{ number_format($pendingAppt) }}</h3>
                    <div class="d-flex align-items-baseline">
                        @if ($totalAppt > 0)
                            <p class="text-warning mb-0 tx-13">{{ $pendingRatio }}% {{ __('of total') }}</p>
                        @else
                            <p class="text-muted mb-0 tx-13">{{ __('No appointments yet.') }}</p>
                        @endif
                    </div>
                  </div>
                  <div class="col-6 col-md-12 col-xl-7">
                    <div id="ordersChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">{{ __('Companies & branches') }}</h6>
                  <div class="dropdown mb-2">
                    <a type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                      <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.branches.index') }}"><i data-feather="layers" class="icon-sm me-2"></i> <span>{{ __('Branches') }}</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-5">
                    <h3 class="mb-2">{{ number_format($companiesCount) }}<span class="tx-14 text-muted"> / </span>{{ number_format($branchesCount) }}</h3>
                    <div class="d-flex align-items-baseline">
                        <p class="text-success mb-0 tx-13">{{ __('companies / branches') }} · {{ number_format($servicesCount) }} {{ __('services') }}</p>
                    </div>
                  </div>
                  <div class="col-6 col-md-12 col-xl-7">
                    <div id="growthChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12 col-xl-12 grid-margin stretch-card">
        <div class="card overflow-hidden">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
              <h6 class="card-title mb-0">{{ __('Bookings overview') }}</h6>
              <div class="dropdown">
                <a type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                  <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.appointments.index') }}"><i data-feather="calendar" class="icon-sm me-2"></i> <span>{{ __('Appointments') }}</span></a>
                </div>
              </div>
            </div>
            <div class="row align-items-start">
              <div class="col-md-7">
                <p class="text-muted tx-13 mb-3 mb-md-0">
                  {{ __('Daily appointments for the last 30 days across the platform.') }}
                </p>
              </div>
              <div class="col-md-5 d-flex justify-content-md-end">
                <div class="btn-group mb-3 mb-md-0" role="group" aria-label="{{ __('Range') }}">
                  <button type="button" class="btn btn-outline-primary">{{ __('Today') }}</button>
                  <button type="button" class="btn btn-outline-primary d-none d-md-block">{{ __('Week') }}</button>
                  <button type="button" class="btn btn-primary">{{ __('Month') }}</button>
                  <button type="button" class="btn btn-outline-primary">{{ __('Year') }}</button>
                </div>
              </div>
            </div>
            <div id="revenueChart" ></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-7 col-xl-8 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
              <h6 class="card-title mb-0">{{ __('Monthly appointments') }}</h6>
              <div class="dropdown mb-2">
                <a type="button" id="dropdownMenuButton4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton4">
                  <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.appointments.index') }}"><i data-feather="eye" class="icon-sm me-2"></i> <span>{{ __('View') }}</span></a>
                </div>
              </div>
            </div>
            <p class="text-muted">{{ __('Bookings per month for the last 12 months.') }}</p>
            <div id="monthlySalesChart"></div>
          </div>
        </div>
      </div>
      <div class="col-lg-5 col-xl-4 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-baseline">
              <h6 class="card-title mb-0">{{ __('Appointments by status') }}</h6>
              <div class="dropdown mb-2">
                <a type="button" id="dropdownMenuButton5" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton5">
                  <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.branches.index') }}"><i data-feather="map-pin" class="icon-sm me-2"></i> <span>{{ __('Branches') }}</span></a>
                </div>
              </div>
            </div>
            <div id="storageChart"></div>
            <div class="row mb-3">
              <div class="col-6 d-flex justify-content-end">
                <div>
                  <label class="d-flex align-items-center justify-content-end tx-10 text-uppercase fw-bolder">{{ __('Waitlist waiting') }} <span class="p-1 ms-1 rounded-circle bg-secondary"></span></label>
                  <h5 class="fw-bolder mb-0 text-end">{{ number_format($waitlistWaiting) }}</h5>
                </div>
              </div>
              <div class="col-6">
                <div>
                  <label class="d-flex align-items-center tx-10 text-uppercase fw-bolder"><span class="p-1 me-1 rounded-circle bg-primary"></span> {{ __('Pending appts.') }}</label>
                  <h5 class="fw-bolder mb-0">{{ number_format($pendingAppt) }}</h5>
                </div>
              </div>
            </div>
            <div class="d-grid">
              <a href="{{ route('owner.appointments.index') }}" class="btn btn-primary">{{ __('Open appointments') }}</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-5 col-xl-4 grid-margin grid-margin-xl-0 stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
              <h6 class="card-title mb-0">{{ __('Recent appointments') }}</h6>
              <div class="dropdown mb-2">
                <a type="button" id="dropdownMenuButton6" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton6">
                  <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.appointments.index') }}"><i data-feather="list" class="icon-sm me-2"></i> <span>{{ __('All') }}</span></a>
                </div>
              </div>
            </div>
            <div class="d-flex flex-column">
              @forelse ($recentAppointments as $row)
                <a href="{{ route('owner.appointments.show', $row) }}" class="d-flex align-items-center border-bottom py-3 text-decoration-none text-body">
                  <div class="me-3">
                    <span class="rounded-circle wd-35 ht-35 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center">
                      <i data-feather="calendar" class="icon-sm"></i>
                    </span>
                  </div>
                  <div class="w-100">
                    <div class="d-flex justify-content-between">
                      <h6 class="text-body mb-2">{{ $row->customer?->name ?? __('Customer') }}</h6>
                      <p class="text-muted tx-12">{{ $row->start_time?->timezone(config('app.timezone'))->format('M j H:i') }}</p>
                    </div>
                    <p class="text-muted tx-13 mb-0">{{ $row->service?->localizedName() ?? '—' }} · {{ $row->branch?->localizedName() ?? '—' }} · <span class="badge bg-secondary">{{ __($row->status) }}</span></p>
                  </div>
                </a>
              @empty
                <p class="text-muted text-center py-4 mb-0">{{ __('No appointments to show yet.') }}</p>
              @endforelse
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-7 col-xl-8 stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
              <h6 class="card-title mb-0">{{ __('Upcoming & recent') }}</h6>
              <div class="dropdown mb-2">
                <a type="button" id="dropdownMenuButton7" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton7">
                  <a class="dropdown-item d-flex align-items-center" href="{{ route('owner.appointments.index') }}"><i data-feather="external-link" class="icon-sm me-2"></i> <span>{{ __('Full list') }}</span></a>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th class="pt-0">#</th>
                    <th class="pt-0">{{ __('Service / Branch') }}</th>
                    <th class="pt-0">{{ __('Start') }}</th>
                    <th class="pt-0">{{ __('Status') }}</th>
                    <th class="pt-0">{{ __('Customer') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($recentAppointments as $row)
                    <tr>
                      <td>{{ $row->id }}</td>
                      <td>{{ $row->service?->localizedName() ?? '—' }} <span class="text-muted">/</span> {{ $row->branch?->localizedName() ?? '—' }}</td>
                      <td>{{ $row->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                      <td><span class="badge bg-secondary">{{ __($row->status) }}</span></td>
                      <td>{{ $row->customer?->name ?? '—' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">{{ __('No rows yet.') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

</div>

@push('owner-after-template')
    @php
        $booksyDashboardPayload = [
            'theme' => request()->cookie('owner_theme', 'dark'),
            'rtl' => app()->getLocale() === 'ar',
            'charts' => $chartData,
            'labels' => [
                'appointments' => __('Appointments'),
                'count' => __('Count'),
                'total' => __('Total'),
                'noData' => __('No appointment data yet.'),
            ],
        ];
    @endphp
    <script>
        window.booksyDashboard = @json($booksyDashboardPayload);
    </script>
    <script src="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('backend/assets/js/owner-dashboard-charts.js') }}"></script>
@endpush

@endsection
