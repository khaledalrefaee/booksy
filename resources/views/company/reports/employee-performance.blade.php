@extends('company.dashboard')

@section('content')
@php
    $period = \Carbon\Carbon::create($year, $month, 1);
    $maxRev = max(1, (float) ($employees->max('revenue') ?: 1));
    $totalRev  = (float) $employees->sum('revenue');
    $totalAppt = (int) $employees->sum('appointments');
@endphp

<div class="page-content">

    <x-reports.head :title="__('Employee Performance')" active="employee-performance" :subtitle="$period->translatedFormat('F Y')" />

    @include('company.reports.partials.period')

    <div class="rp-panel" style="height:auto;">
        <div class="table-responsive">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th scope="col" style="width:52px;">#</th>
                        <th scope="col">{{ __('Employee') }}</th>
                        <th scope="col">{{ __('Branch') }}</th>
                        <th scope="col" class="num">{{ __('Appointments') }}</th>
                        <th scope="col" class="num">{{ __('Revenue') }}</th>
                        <th scope="col" class="num">{{ __('Avg per Appointment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $i => $emp)
                        <tr>
                            <td class="rp-muted fw-bold">{{ $i + 1 }}</td>
                            <td style="min-width:180px;">
                                <div class="fw-semibold">{{ $emp['name'] }}</div>
                                <div class="rp-bar-track" style="margin-inline-start:0;max-width:220px;">
                                    <div class="rp-bar-fill" style="width:{{ max(2, round($emp['revenue'] / $maxRev * 100)) }}%"></div>
                                </div>
                            </td>
                            <td class="rp-muted">{{ $emp['branch'] }}</td>
                            <td class="num"><span class="rp-pill">{{ $emp['appointments'] }}</span></td>
                            <td class="num fw-bold">{{ number_format($emp['revenue'], 0) }}</td>
                            <td class="num rp-muted">{{ number_format($emp['avg'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="rp-empty">{{ __('No data') }}</td></tr>
                    @endforelse
                    @if($employees->count() > 1)
                        <tr class="is-total">
                            <td></td>
                            <td colspan="2">{{ __('Total') }}</td>
                            <td class="num">{{ $totalAppt }}</td>
                            <td class="num">{{ number_format($totalRev, 0) }}</td>
                            <td class="num">{{ $totalAppt > 0 ? number_format($totalRev / $totalAppt, 0) : '—' }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
