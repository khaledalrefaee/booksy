@extends('company.dashboard')

@push('company-styles')
<style>
.pl-hero {
    background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.pl-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.05); pointer-events:none;
}
.pl-card { border-radius:14px; border:1px solid var(--bs-border-color); padding:20px; text-align:center; }
.pl-card .num { font-size:1.6rem; font-weight:800; }
.pl-card .label { font-size:12px; color:var(--bs-secondary-color); margin-top:4px; }
.change-up { color:#22c55e; font-size:11px; font-weight:700; }
.change-down { color:#ef4444; font-size:11px; font-weight:700; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="pl-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">📊 {{ __('Profit & Loss Report') }}</h3>
                <p class="mb-0" style="opacity:.75;font-size:13px;">
                    {{ $from->translatedFormat('F Y') }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('company.reports.employee-performance', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm btn-outline-light">
                    👥 {{ __('Employee Performance') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="d-flex gap-2 mb-4 flex-wrap">
        <select name="month" class="form-select form-select-sm" style="max-width:130px;">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($month == $m)>{{ Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
            @endfor
        </select>
        <input type="number" name="year" value="{{ $year }}" class="form-control form-control-sm" style="max-width:90px;">
        <select name="branch_id" class="form-select form-select-sm" style="max-width:180px;">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->localizedName() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-primary">{{ __('View') }}</button>
    </form>

    {{-- KPI Cards --}}
    @php
        $changeIncome = $prevData['income'] > 0 ? round((($currentData['income'] - $prevData['income']) / $prevData['income']) * 100, 1) : null;
        $changeExpense = $prevData['expense'] > 0 ? round((($currentData['expense'] - $prevData['expense']) / $prevData['expense']) * 100, 1) : null;
        $changeNet = $prevData['net'] != 0 ? round((($currentData['net'] - $prevData['net']) / abs($prevData['net'])) * 100, 1) : null;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="pl-card">
                <div class="num text-success">{{ number_format($currentData['income'], 0) }}</div>
                <div class="label">{{ __('Total Income') }}</div>
                @if($changeIncome !== null)
                    <div class="{{ $changeIncome >= 0 ? 'change-up' : 'change-down' }}">
                        {{ $changeIncome >= 0 ? '▲' : '▼' }} {{ abs($changeIncome) }}%
                    </div>
                @endif
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pl-card">
                <div class="num text-danger">{{ number_format($currentData['expense'], 0) }}</div>
                <div class="label">{{ __('Total Expenses') }}</div>
                @if($changeExpense !== null)
                    <div class="{{ $changeExpense <= 0 ? 'change-up' : 'change-down' }}">
                        {{ $changeExpense <= 0 ? '▼' : '▲' }} {{ abs($changeExpense) }}%
                    </div>
                @endif
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pl-card">
                <div class="num {{ $currentData['net'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($currentData['net'], 0) }}</div>
                <div class="label">{{ __('Net Profit') }}</div>
                @if($changeNet !== null)
                    <div class="{{ $changeNet >= 0 ? 'change-up' : 'change-down' }}">
                        {{ $changeNet >= 0 ? '▲' : '▼' }} {{ abs($changeNet) }}%
                    </div>
                @endif
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pl-card">
                <div class="num">{{ $currentData['margin'] }}%</div>
                <div class="label">{{ __('Profit Margin') }}</div>
                <div class="text-muted" style="font-size:11px;">{{ $currentData['appointmentCount'] }} {{ __('appointments') }}</div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">{{ __('Daily Breakdown') }}</h6>
            <canvas id="plChart" height="100"></canvas>
        </div>
    </div>

    <div class="row g-4">
        {{-- Category Breakdown --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('By Category') }}</h6>
                    @php $cats = \App\Models\BranchPayment::CATEGORIES; @endphp
                    @foreach($currentData['byCategory'] as $catKey => $amount)
                        @php $catMeta = $cats[$catKey] ?? null; @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                {{ $catMeta['icon'] ?? '' }} {{ $catMeta ? __($catMeta['label_key']) : $catKey }}
                            </span>
                            <strong class="{{ ($catMeta['type'] ?? '') === 'income' ? 'text-success' : 'text-danger' }} small">
                                {{ number_format($amount, 0) }}
                            </strong>
                        </div>
                    @endforeach
                    @if($currentData['payrollTotal'] > 0)
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="small">💰 {{ __('Payroll Total') }}</span>
                            <strong class="text-danger small">{{ number_format($currentData['payrollTotal'], 0) }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top Services --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">🏆 {{ __('Top Services') }}</h6>
                    @forelse($topServices as $i => $svc)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                <span class="text-muted">{{ $i + 1 }}.</span> {{ $svc['name'] }}
                                <small class="text-muted">({{ $svc['count'] }})</small>
                            </span>
                            <strong class="small text-success">{{ number_format($svc['revenue'], 0) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted text-center small">{{ __('No data') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top Employees --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">⭐ {{ __('Top Employees') }}</h6>
                    @forelse($topEmployees as $i => $emp)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                <span class="text-muted">{{ $i + 1 }}.</span> {{ $emp['name'] }}
                                <small class="text-muted">({{ $emp['count'] }})</small>
                            </span>
                            <strong class="small text-success">{{ number_format($emp['revenue'], 0) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted text-center small">{{ __('No data') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Comparison --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">📈 {{ __('Month-over-Month Comparison') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm text-center">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ $from->copy()->subMonth()->translatedFormat('F Y') }}</th>
                                    <th>{{ $from->translatedFormat('F Y') }}</th>
                                    <th>{{ __('Change') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-start fw-semibold">{{ __('Income') }}</td>
                                    <td>{{ number_format($prevData['income'], 0) }}</td>
                                    <td class="fw-bold">{{ number_format($currentData['income'], 0) }}</td>
                                    <td>
                                        @if($changeIncome !== null)
                                            <span class="{{ $changeIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $changeIncome >= 0 ? '+' : '' }}{{ $changeIncome }}%
                                            </span>
                                        @else —
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-start fw-semibold">{{ __('Expenses') }}</td>
                                    <td>{{ number_format($prevData['expense'], 0) }}</td>
                                    <td class="fw-bold">{{ number_format($currentData['expense'], 0) }}</td>
                                    <td>
                                        @if($changeExpense !== null)
                                            <span class="{{ $changeExpense <= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $changeExpense >= 0 ? '+' : '' }}{{ $changeExpense }}%
                                            </span>
                                        @else —
                                        @endif
                                    </td>
                                </tr>
                                <tr class="fw-bold">
                                    <td class="text-start">{{ __('Net Profit') }}</td>
                                    <td class="{{ $prevData['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($prevData['net'], 0) }}</td>
                                    <td class="{{ $currentData['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($currentData['net'], 0) }}</td>
                                    <td>
                                        @if($changeNet !== null)
                                            <span class="{{ $changeNet >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $changeNet >= 0 ? '+' : '' }}{{ $changeNet }}%
                                            </span>
                                        @else —
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-start fw-semibold">{{ __('Appointments') }}</td>
                                    <td>{{ $prevData['appointmentCount'] }}</td>
                                    <td class="fw-bold">{{ $currentData['appointmentCount'] }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('plChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: '{{ __("Income") }}', data: @json($chartIncome), backgroundColor: 'rgba(34,197,94,.6)', borderRadius: 4 },
                { label: '{{ __("Expenses") }}', data: @json($chartExpense), backgroundColor: 'rgba(239,68,68,.6)', borderRadius: 4 },
                { label: '{{ __("Net Profit") }}', data: @json($chartProfit), type: 'line', borderColor: '#6366f1', borderWidth: 2, pointRadius: 0, fill: false }
            ]
        },
        options: {
            responsive: true,
            interaction: { intersect: false, mode: 'index' },
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
@endpush
@endsection
