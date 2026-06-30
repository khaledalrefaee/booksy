@extends('company.dashboard')

@push('company-styles')
<style>
.perf-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.perf-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="perf-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">👥 {{ __('Employee Performance') }}</h3>
                <p class="mb-0" style="opacity:.75;font-size:13px;">
                    {{ Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}
                </p>
            </div>
            <a href="{{ route('company.reports.profit-loss', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm btn-outline-light">
                📊 {{ __('Profit & Loss') }}
            </a>
        </div>
    </div>

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

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Employee') }}</th>
                    <th>{{ __('Branch') }}</th>
                    <th class="text-center">{{ __('Appointments') }}</th>
                    <th class="text-end">{{ __('Revenue') }}</th>
                    <th class="text-end">{{ __('Avg per Appointment') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $i => $emp)
                    <tr>
                        <td class="fw-bold text-muted">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $emp['name'] }}</strong>
                        </td>
                        <td class="small text-muted">{{ $emp['branch'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $emp['appointments'] }}</span>
                        </td>
                        <td class="text-end fw-bold text-success">{{ number_format($emp['revenue'], 0) }}</td>
                        <td class="text-end text-muted">{{ number_format($emp['avg'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
