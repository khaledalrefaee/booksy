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
        {{-- filters moved below --}}
    </div>

    @include('owner.partials.flash')

    @php
        $apptExtraFilters = '
            <select name="company_id" class="bk-ssb-select" style="min-width:150px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الشركات') . '</option>
                ' . $companies->map(fn($c) => '<option value="' . $c->id . '" ' . ((string)$filterCompanyId === (string)$c->id ? 'selected' : '') . '>' . e($c->localizedName()) . '</option>')->implode('') . '
            </select>
            <select name="status" class="bk-ssb-select" style="min-width:130px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الحالات') . '</option>
                ' . collect([
                    'pending'   => 'قيد الانتظار',
                    'confirmed' => 'مؤكد',
                    'rejected'  => 'مرفوض',
                    'cancelled' => 'ملغى',
                    'completed' => 'مكتمل',
                    'no_show'   => 'لم يحضر',
                ])->map(fn($label, $st) => '<option value="' . $st . '" ' . ($filterStatus === $st ? 'selected' : '') . '>' . $label . '</option>')->implode('') . '
            </select>
            <input type="date" name="date_from" value="' . e($filterDateFrom) . '"
                   class="bk-ssb-date" style="min-width:130px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
            <input type="date" name="date_to" value="' . e($filterDateTo) . '"
                   class="bk-ssb-date" style="min-width:130px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
        ';
    @endphp

    @include('owner.partials._search-sort-bar', [
        'dtTableId'       => 'dt-appointments',
        'sortField'       => $sortField,
        'sortDir'         => $sortDir,
        'extraFilterKeys' => ['company_id', 'status', 'date_from', 'date_to'],
        'sortOptions'     => [
            ['field' => 'start_time',   'label' => __('وقت الموعد')],
            ['field' => 'created_at',   'label' => __('تاريخ الإضافة')],
            ['field' => 'total_price',  'label' => __('السعر')],
        ],
        'extraFilters' => $apptExtraFilters,
    ])

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dt-appointments">
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
@include('owner.partials._datatable', [
    'tableId'    => 'dt-appointments',
    'exportName' => 'Appointments',
    'noSortCols' => [4, 5, -1],
])

@endsection
