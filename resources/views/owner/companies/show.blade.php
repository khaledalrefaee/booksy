@extends('owner.dashboard')
@section('content')
@php
    $weekDays = [
        0 => __('Sunday'),
        1 => __('Monday'),
        2 => __('Tuesday'),
        3 => __('Wednesday'),
        4 => __('Thursday'),
        5 => __('Friday'),
        6 => __('Saturday'),
    ];
    $statusClass = match ($company->status) {
        'active' => 'success',
        'suspended' => 'danger',
        default => 'warning',
    };
@endphp
<div class="page-content">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-2">{{ $company->localizedName() }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('owner.companies.index') }}">{{ __('Campanias') }}</a></li>
                    <li class="breadcrumb-item active">{{ $company->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('owner.companies.index') }}" class="btn btn-outline-secondary rounded-pill">
            <i data-feather="arrow-left" class="me-1" style="width:16px;height:16px;"></i>
            {{ __('Back to list') }}
        </a>
    </div>

    @include('owner.partials.flash')

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-wrap gap-4 align-items-center">
                    @if ($company->logo)
                        <img src="{{ asset('storage/'.$company->logo) }}" alt="" class="rounded-3 border" width="80" height="80" style="object-fit:cover;">
                    @else
                        <div class="rounded-3 border bg-light d-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                            <i data-feather="briefcase" class="text-muted"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <h5 class="mb-0">{{ $company->localizedName() }}</h5>
                            <span class="badge rounded-pill bg-{{ $statusClass }}">{{ __($company->status) }}</span>
                        </div>
                        <p class="text-muted mb-1"><span class="fw-semibold text-body">{{ __('Email') }}:</span> {{ $company->email }}</p>
                        <p class="text-muted mb-1"><span class="fw-semibold text-body">{{ __('Phone') }}:</span> {{ $company->phone ?: '—' }}</p>
                        <p class="text-muted mb-0"><span class="fw-semibold text-body">{{ __('Category') }}:</span> {{ $company->category?->localizedName() ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row g-3">
                @foreach ([
                    ['label' => __('Branches'), 'value' => $stats['branches'], 'icon' => 'map-pin'],
                    ['label' => __('Employees'), 'value' => $stats['employees'], 'icon' => 'users'],
                    ['label' => __('Appointments'), 'value' => $stats['appointments'], 'icon' => 'calendar'],
                    ['label' => __('Waitlist'), 'value' => $stats['waitlist'], 'icon' => 'clock'],
                ] as $stat)
                    <div class="col-6">
                        <div class="card border-0 shadow-sm rounded-4 mb-0">
                            <div class="card-body p-3 text-center">
                                <i data-feather="{{ $stat['icon'] }}" class="text-primary mb-1" style="width:20px;height:20px;"></i>
                                <p class="text-muted tx-11 mb-0">{{ $stat['label'] }}</p>
                                <p class="fw-bold mb-0 fs-5">{{ $stat['value'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-tabs-line mb-3" id="company-detail-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-branches" type="button">{{ __('Branches') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-employees" type="button">{{ __('Employees') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button">{{ __('Appointments') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-waitlist" type="button">{{ __('Waitlist') }}</button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Branches + hours + services --}}
        <div class="tab-pane fade show active" id="tab-branches" role="tabpanel">
            @forelse ($company->branches as $branch)
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h6 class="mb-1">{{ $branch->localizedName() }}</h6>
                                <p class="text-muted small mb-0">{{ $branch->address ?: '—' }}</p>
                                @if ($branch->phone)
                                    <p class="text-muted small mb-0">{{ $branch->phone }}</p>
                                @endif
                            </div>
                            @if ($branch->is_head_office)
                                <span class="badge rounded-pill bg-primary">{{ __('Head office') }}</span>
                            @endif
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <p class="text-muted text-uppercase tx-11 fw-semibold mb-2">{{ __('Working hours') }}</p>
                                @if ($branch->workingHours->isEmpty())
                                    <p class="text-muted small mb-0">{{ __('No working hours set.') }}</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>{{ __('Day') }}</th>
                                                    <th>{{ __('Open') }}</th>
                                                    <th>{{ __('Hours') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($branch->workingHours as $hour)
                                                    <tr>
                                                        <td>{{ $weekDays[$hour->day_of_week] ?? $hour->day_of_week }}</td>
                                                        <td>
                                                            @if ($hour->is_open)
                                                                <span class="badge bg-success">{{ __('Yes') }}</span>
                                                            @else
                                                                <span class="badge bg-secondary">{{ __('Closed') }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted small">
                                                            @if ($hour->is_open && $hour->open_time && $hour->close_time)
                                                                {{ \Illuminate\Support\Str::of($hour->open_time)->substr(0, 5) }}
                                                                –
                                                                {{ \Illuminate\Support\Str::of($hour->close_time)->substr(0, 5) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted text-uppercase tx-11 fw-semibold mb-2">{{ __('Services') }}</p>
                                @if ($branch->services->isEmpty())
                                    <p class="text-muted small mb-0">{{ __('No services yet.') }}</p>
                                @else
                                    <ul class="list-group list-group-flush rounded-3 border">
                                        @foreach ($branch->services as $service)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                                <span>{{ $service->localizedName() }}</span>
                                                <span class="text-muted small">
                                                    {{ number_format((float) $service->price, 2) }}
                                                    · {{ $service->duration_minutes }} {{ __('min') }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center text-muted py-5">{{ __('No branches yet.') }}</div>
                </div>
            @endforelse
        </div>

        {{-- Employees --}}
        <div class="tab-pane fade" id="tab-employees" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Active') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($company->employees as $employee)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $employee->localizedName() }}</td>
                                    <td class="text-muted">{{ $employee->email ?: '—' }}</td>
                                    <td>{{ $employee->branch?->localizedName() ?? __('Company-wide') }}</td>
                                    <td>
                                        @if ($employee->role)
                                            {{ app()->getLocale() === 'ar' ? ($employee->role->label_ar ?: $employee->role->label_en) : ($employee->role->label_en ?: $employee->role->label_ar) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($employee->is_active)
                                            <span class="badge rounded-pill bg-success">{{ __('Yes') }}</span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">{{ __('No employees yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Appointments --}}
        <div class="tab-pane fade" id="tab-appointments" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('When') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Service') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end pe-4">{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($company->appointments as $appointment)
                                @php
                                    $badge = match ($appointment->status) {
                                        'pending' => 'warning',
                                        'confirmed' => 'success',
                                        'completed' => 'primary',
                                        'cancelled', 'rejected', 'no_show' => 'secondary',
                                        default => 'info',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-4 text-nowrap">{{ $appointment->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                    <td>{{ $appointment->branch?->localizedName() ?? '—' }}</td>
                                    <td>{{ $appointment->service?->localizedName() ?? '—' }}</td>
                                    <td>{{ $appointment->customer?->name ?? '—' }}</td>
                                    <td><span class="badge rounded-pill bg-{{ $badge }}">{{ __($appointment->status) }}</span></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('owner.appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary rounded-pill">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">{{ __('No appointments yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($stats['appointments'] > $company->appointments->count())
                    <div class="card-footer bg-transparent border-0 text-muted small">
                        {{ __('Showing latest :count of :total appointments.', ['count' => $company->appointments->count(), 'total' => $stats['appointments']]) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Waitlist --}}
        <div class="tab-pane fade" id="tab-waitlist" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('Customer') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Service') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Preferred time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($company->waitlistEntries as $entry)
                                <tr>
                                    <td class="ps-4">{{ $entry->customer?->name ?? '—' }}</td>
                                    <td>{{ $entry->branch?->localizedName() ?? '—' }}</td>
                                    <td>{{ $entry->service?->localizedName() ?? '—' }}</td>
                                    <td><span class="badge rounded-pill bg-light text-dark border">{{ __($entry->status) }}</span></td>
                                    <td class="text-muted">{{ $entry->preferred_start?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">{{ __('No waitlist entries.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') {
        window.feather.replace();
    }
});
</script>
@endpush
@endsection
