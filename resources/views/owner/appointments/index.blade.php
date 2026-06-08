@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-start flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-2">{{ __('Appointments') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Appointments') }}</li>
                </ol>
            </nav>
        </div>
        <form method="get" action="{{ route('owner.appointments.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <select name="company_id" class="form-select rounded-pill" style="min-width: 12rem;" onchange="this.form.submit()">
                <option value="">{{ __('All companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected($filterCompanyId === (int) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select rounded-pill" style="min-width: 11rem;" onchange="this.form.submit()">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['pending', 'confirmed', 'rejected', 'cancelled', 'completed', 'no_show'] as $st)
                    <option value="{{ $st }}" @selected($filterStatus === $st)>{{ __($st) }}</option>
                @endforeach
            </select>
            @if(request()->hasAny(['company_id', 'status']) && array_filter([request('company_id'), request('status')]))
                <a href="{{ route('owner.appointments.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('Clear filters') }}">
                    <i data-feather="x" style="width:14px;height:14px;"></i>
                    {{ __('Clear') }}
                </a>
            @endif
        </form>
    </div>

    @include('owner.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('When') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Service') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th class="text-end pe-4">{{ __('Details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $row)
                            <tr>
                                <td class="ps-4 text-nowrap">{{ $row->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                <td>{{ $row->branch?->localizedName() ?? '—' }}</td>
                                <td>{{ $row->service?->localizedName() ?? '—' }}</td>
                                <td>{{ $row->customer?->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $badge = match ($row->status) {
                                            'pending' => 'warning',
                                            'confirmed' => 'success',
                                            'completed' => 'primary',
                                            'cancelled', 'rejected', 'no_show' => 'secondary',
                                            default => 'info',
                                        };
                                    @endphp
                                    <span class="badge rounded-pill bg-{{ $badge }}">{{ __($row->status) }}</span>
                                </td>
                                <td><span class="text-muted">{{ __($row->payment_status) }}</span></td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('owner.appointments.show', $row) }}" class="btn btn-sm btn-outline-primary rounded-pill">{{ __('Details') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="calendar" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No appointments found.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($appointments->hasPages())
            <div class="card-footer bg-white border-0 py-3">{{ $appointments->links() }}</div>
        @endif
    </div>
</div>
@endsection
