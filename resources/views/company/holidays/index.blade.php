@extends('company.dashboard')

@push('company-styles')
<style>
.hol-hero {
    background: linear-gradient(135deg, #f093fb 0%, #764ba2 100%);
    border-radius: 20px; padding: 26px 30px; margin-bottom: 24px;
    color: #fff; position: relative; overflow: hidden;
}
.hol-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.08); pointer-events: none;
}
.hol-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
.hol-row:last-child { border-bottom: none; }
.bk-theme-light .hol-row { border-bottom-color: rgba(0,0,0,.05); }
.hol-badge {
    font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
}
</style>
@endpush

@section('content')
<div class="page-content">

@include('company.partials.team-nav')

{{-- Hero --}}
<div class="hol-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
        <div>
            <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">🎉 {{ __('Public holidays') }}</h3>
            <p class="mb-0" style="opacity:.75;font-size:13px;">
                {{ $holidays->count() }} {{ __('holiday(s)') }} · {{ $totalDays }} {{ __('day(s)') }} — {{ $year }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('company.holidays.index', ['year' => $year - 1]) }}"
               class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.15);color:#fff;font-weight:700;">{{ $year - 1 }}</a>
            <span class="fw-bold" style="font-size:16px;">{{ $year }}</span>
            <a href="{{ route('company.holidays.index', ['year' => $year + 1]) }}"
               class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.15);color:#fff;font-weight:700;">{{ $year + 1 }}</a>
        </div>
    </div>
</div>

@include('company.partials.flash')

<div class="row g-4">
    {{-- Add form --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="fw-bold tx-13 mb-3">➕ {{ __('Add holiday') }}</div>
                <form method="POST" action="{{ route('company.holidays.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold tx-12">{{ __('Holiday name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="{{ __('e.g. Eid al-Fitr') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('End Date') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date') }}">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <label class="d-flex align-items-center gap-2 mb-3" style="cursor:pointer;">
                        <input type="checkbox" name="is_paid" value="1" class="form-check-input mt-0" checked>
                        <span class="tx-13 fw-semibold">{{ __('Paid holiday') }}</span>
                        <span class="tx-11 text-muted">({{ __('unpaid holidays reduce daily-paid salaries') }})</span>
                    </label>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">{{ __('Add holiday') }}</button>
                </form>
            </div>
        </div>

        <div class="tx-11 text-muted mt-3 px-2" style="line-height:1.8;">
            ℹ️ {{ __('On a holiday, employees are not counted as absent, the day is excluded from attendance reports, and check-in is not required.') }}
        </div>
    </div>

    {{-- List --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4" style="overflow:hidden;">
            <div class="card-body p-0">
                @forelse($holidays as $holiday)
                <div class="hol-row">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(240,147,251,.1);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
                        <div style="font-size:15px;font-weight:800;color:#f093fb;line-height:1;">{{ $holiday->start_date->format('d') }}</div>
                        <div style="font-size:9px;opacity:.6;">{{ $holiday->start_date->translatedFormat('M') }}</div>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-bold tx-13">{{ $holiday->name }}</div>
                        <div class="tx-11 text-muted">
                            {{ $holiday->start_date->translatedFormat('D d M') }}@if(!$holiday->start_date->isSameDay($holiday->end_date)) → {{ $holiday->end_date->translatedFormat('D d M') }}@endif
                            · {{ $holiday->daysCount() }} {{ __('day(s)') }}
                        </div>
                    </div>
                    <span class="hol-badge" style="background:{{ $holiday->is_paid ? 'rgba(34,197,94,.12)' : 'rgba(245,158,11,.12)' }};color:{{ $holiday->is_paid ? '#22c55e' : '#f59e0b' }};">
                        {{ $holiday->is_paid ? __('Paid') : __('Unpaid') }}
                    </span>
                    @if($holiday->end_date->isPast())
                    <span class="hol-badge" style="background:rgba(100,116,139,.12);color:#94a3b8;">{{ __('Passed') }}</span>
                    @endif
                    <form method="POST" action="{{ route('company.holidays.destroy', $holiday) }}"
                          onsubmit="return confirm('{{ __('Delete this holiday?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm rounded-pill" style="background:rgba(239,68,68,.08);color:#ef4444;font-size:11px;border:none;">
                            <i data-feather="trash-2" style="width:12px;height:12px;"></i>
                        </button>
                    </form>
                </div>
                @empty
                <div class="bk-empty py-5 text-center" style="opacity:.5;">
                    <div style="font-size:34px;margin-bottom:8px;">🗓️</div>
                    <p class="mb-0">{{ __('No holidays for :year yet — add the first one.', ['year' => $year]) }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

</div>
@endsection
