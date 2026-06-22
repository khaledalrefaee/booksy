@extends('company.dashboard')

@push('company-styles')
<style>
.pay-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px; padding: 28px 32px; margin-bottom: 24px;
    color: #fff; position: relative; overflow: hidden;
}
.pay-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(255,255,255,.07); pointer-events:none;
}
.emp-pay-row {
    border-radius: 14px; padding: 14px 18px;
    border: 1.5px solid rgba(255,255,255,.07);
    background: rgba(255,255,255,.02);
    display: flex; align-items: center; gap: 14px;
    transition: background .15s, border-color .15s;
    text-decoration: none; color: inherit;
}
.emp-pay-row:hover {
    background: rgba(102,126,234,.07);
    border-color: rgba(102,126,234,.25);
    color: inherit;
}
.bk-theme-light .emp-pay-row { border-color: #e2e8f0; background: #fafafa; }
.bk-theme-light .emp-pay-row:hover { background: #f1f5f9; border-color: #a5b4fc; }
.emp-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
}
.emp-avatar-placeholder {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg,#667eea,#764ba2);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800; color: #fff; flex-shrink: 0;
}
.pay-pill {
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.month-nav { display: flex; align-items: center; gap: 8px; }
.month-nav a {
    width: 32px; height: 32px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center;
    background: rgba(255,255,255,.15); color: #fff; text-decoration: none;
    transition: background .15s;
}
.month-nav a:hover { background: rgba(255,255,255,.3); }
</style>
@endpush

@section('content')
<div class="page-content">

@php
    $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $nextMonth = \Carbon\Carbon::create($year, $month, 1)->addMonth();
    $canGoNext = $nextMonth->lessThanOrEqualTo(now());
    $monthName = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y');

    $totalNet   = $rows->sum('netPay');
    $totalBase  = $rows->sum('baseSalary');
    $totalComm  = $rows->sum('commInSalaryCurrency');
    $totalDed   = $rows->sum('totalDeducted');
@endphp

{{-- Hero --}}
<div class="pay-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
        <div>
            <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Payroll') }}</h3>
            <p class="mb-0" style="opacity:.7;font-size:13px;">
                {{ $rows->count() }} {{ __('employees') }}
                &nbsp;·&nbsp;
                {{ __('Total net') }}: <strong>{{ number_format($totalNet, 0) }}</strong>
            </p>
        </div>
        <div class="month-nav">
            <a href="{{ route('company.payroll.index', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}">
                <i data-feather="chevron-right" style="width:14px;height:14px;"></i>
            </a>
            <span style="font-weight:700;font-size:15px;min-width:130px;text-align:center;">{{ $monthName }}</span>
            @if($canGoNext)
            <a href="{{ route('company.payroll.index', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}">
                <i data-feather="chevron-left" style="width:14px;height:14px;"></i>
            </a>
            @else
            <span style="width:32px;height:32px;"></span>
            @endif
        </div>
    </div>
</div>

{{-- Company totals --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-body text-center p-3">
                <div style="font-size:22px;margin-bottom:4px;">💰</div>
                <div style="font-size:18px;font-weight:800;">{{ number_format($totalBase,0) }}</div>
                <div style="font-size:11px;opacity:.45;">{{ __('Total salaries') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-body text-center p-3">
                <div style="font-size:22px;margin-bottom:4px;">📊</div>
                <div style="font-size:18px;font-weight:800;color:#22c55e;">{{ number_format($totalComm,0) }}</div>
                <div style="font-size:11px;opacity:.45;">{{ __('Total commissions') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-body text-center p-3">
                <div style="font-size:22px;margin-bottom:4px;">📉</div>
                <div style="font-size:18px;font-weight:800;color:#ef4444;">{{ number_format($totalDed,0) }}</div>
                <div style="font-size:11px;opacity:.45;">{{ __('Total deductions') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;border:1.5px solid rgba(67,233,123,.2) !important;">
            <div class="card-body text-center p-3">
                <div style="font-size:22px;margin-bottom:4px;">✅</div>
                <div style="font-size:18px;font-weight:800;color:#22c55e;">{{ number_format($totalNet,0) }}</div>
                <div style="font-size:11px;opacity:.45;">{{ __('Total net pay') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Employee rows --}}
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-3">
        @if($rows->isEmpty())
        <div class="text-center py-5" style="opacity:.45;">
            <div style="font-size:32px;margin-bottom:8px;">👥</div>
            <div>{{ __('No active employees found.') }}</div>
        </div>
        @else
        <div class="d-flex flex-column gap-2">
        @foreach($rows as $row)
        @php $emp = $row['employee']; $comp = $row['compensation']; @endphp
        <a href="{{ route('company.employees.payroll', [$emp, 'month' => $month, 'year' => $year]) }}"
           class="emp-pay-row">

            {{-- Avatar --}}
            @if($emp->image)
                <img src="{{ asset('storage/'.$emp->image) }}" class="emp-avatar" alt="">
            @else
                <div class="emp-avatar-placeholder">{{ mb_substr($emp->localizedName(), 0, 1) }}</div>
            @endif

            {{-- Name + branch --}}
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:14px;">{{ $emp->localizedName() }}</div>
                <div style="font-size:11px;opacity:.4;">{{ $emp->branch->localizedName() }}</div>
            </div>

            {{-- Appointments count --}}
            <div class="text-center d-none d-md-block" style="min-width:60px;">
                <div style="font-size:16px;font-weight:800;">{{ $row['appointments']->count() }}</div>
                <div style="font-size:10px;opacity:.4;">{{ __('appts') }}</div>
            </div>

            {{-- Base --}}
            <div class="text-center d-none d-lg-block" style="min-width:80px;">
                <div style="font-size:13px;font-weight:700;">{{ number_format($row['baseSalary'],0) }}</div>
                <div style="font-size:10px;opacity:.4;">{{ __('Base') }}</div>
            </div>

            {{-- Commissions --}}
            <div class="text-center" style="min-width:80px;">
                @if($row['commissionsByCurrency']->isNotEmpty())
                    @foreach($row['commissionsByCurrency'] as $cur => $amt)
                    @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                    <div style="font-size:12px;font-weight:700;color:#22c55e;line-height:1.3;">+{{ number_format($amt,0) }}<span style="font-size:9px;opacity:.6;"> {{ $sym }}</span></div>
                    @endforeach
                @else
                <div style="font-size:13px;opacity:.3;">—</div>
                @endif
                <div style="font-size:10px;opacity:.4;">{{ __('Comm.') }}</div>
            </div>

            {{-- Deductions --}}
            <div class="text-center" style="min-width:80px;">
                @if($row['totalDeducted'] > 0)
                <div style="font-size:13px;font-weight:700;color:#ef4444;">-{{ number_format($row['totalDeducted'],0) }}</div>
                @else
                <div style="font-size:13px;opacity:.3;">—</div>
                @endif
                <div style="font-size:10px;opacity:.4;">{{ __('Ded.') }}</div>
            </div>

            {{-- Net --}}
            <div class="text-end" style="min-width:90px;">
                <div style="font-size:17px;font-weight:900;color:#22c55e;">{{ number_format($row['netPay'],0) }}</div>
                <div style="font-size:10px;opacity:.4;">{{ __('Net') }}</div>
            </div>

            <i data-feather="chevron-left" style="width:14px;height:14px;opacity:.3;flex-shrink:0;"></i>
        </a>
        @endforeach
        </div>
        @endif
    </div>
</div>

</div>
@endsection
