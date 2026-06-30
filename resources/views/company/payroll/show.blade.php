@extends('company.dashboard')

@push('company-styles')
<style>
.pay-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px; padding: 28px 32px; margin-bottom: 24px;
    color: #fff; position: relative; overflow: hidden;
}
.pay-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
.pay-stat-card {
    border-radius: 16px; padding: 20px 22px;
    border: 1.5px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.03);
    transition: transform .15s;
}
.bk-theme-light .pay-stat-card {
    border-color: #e2e8f0; background: #f8fafc;
}
.pay-stat-card:hover { transform: translateY(-2px); }
.pay-stat-icon {
    width: 40px; height: 40px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 12px;
}
.pay-stat-value { font-size: 22px; font-weight: 800; letter-spacing: -.5px; }
.pay-stat-label { font-size: 12px; opacity: .55; margin-top: 2px; }
.pay-section-title {
    font-size: 13px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; opacity: .45; margin-bottom: 12px;
}
.pay-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .5; }
.pay-table td { font-size: 13px; vertical-align: middle; }
.comm-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 700; padding: 2px 8px;
    border-radius: 20px; background: rgba(67,233,123,.12); color: #22c55e;
}
.ded-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.ded-badge.absence   { background: rgba(239,68,68,.12);  color: #ef4444; }
.ded-badge.tardiness { background: rgba(245,158,11,.12); color: #f59e0b; }
.ded-badge.other     { background: rgba(99,102,241,.12); color: #818cf8; }
.net-box {
    border-radius: 16px; padding: 20px 24px;
    background: linear-gradient(135deg,rgba(67,233,123,.08),rgba(102,126,234,.08));
    border: 1.5px solid rgba(67,233,123,.2);
}
.month-nav { display: flex; align-items: center; gap: 8px; }
.month-nav a {
    width: 32px; height: 32px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center;
    background: rgba(255,255,255,.15); color: #fff;
    text-decoration: none; transition: background .15s;
}
.month-nav a:hover { background: rgba(255,255,255,.3); }
</style>
@endpush

@section('content')
<div class="page-content">

@php
    $currency   = $salaryCurrency;
    $currSymbol = config("booksy.currencies.{$currency}.symbol", $currency);

    // Month navigation
    $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $nextMonth = \Carbon\Carbon::create($year, $month, 1)->addMonth();
    $canGoNext = $nextMonth->lessThanOrEqualTo(now());
    $monthName = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y');
@endphp

{{-- Hero ─────────────────────────────────────────────────────────────────── --}}
<div class="pay-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.35);">
                    <li class="breadcrumb-item">
                        <a href="{{ route('company.payroll.index') }}"
                           class="text-decoration-none" style="color:rgba(255,255,255,.65);font-size:13px;">
                            {{ __('Payroll') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" style="color:rgba(255,255,255,.45);font-size:13px;">
                        {{ $employee->localizedName() }}
                    </li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">
                {{ $employee->localizedName() }}
            </h3>
            <p class="mb-0" style="opacity:.7;font-size:13px;">{{ $employee->branch->localizedName() }}</p>
        </div>

        {{-- Month navigator --}}
        <div class="month-nav">
            <a href="{{ route('company.employees.payroll', [$employee, 'month' => $prevMonth->month, 'year' => $prevMonth->year]) }}">
                <i data-feather="chevron-right" style="width:14px;height:14px;"></i>
            </a>
            <span style="font-weight:700;font-size:15px;min-width:130px;text-align:center;">{{ $monthName }}</span>
            @if($canGoNext)
            <a href="{{ route('company.employees.payroll', [$employee, 'month' => $nextMonth->month, 'year' => $nextMonth->year]) }}">
                <i data-feather="chevron-left" style="width:14px;height:14px;"></i>
            </a>
            @else
            <span style="width:32px;height:32px;"></span>
            @endif
        </div>
    </div>
</div>

@include('company.partials.flash')

{{-- Summary cards ────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="pay-stat-card card border-0 h-100">
            <div class="card-body p-3">
                <div class="pay-stat-icon" style="background:rgba(102,126,234,.12);">💰</div>
                <div class="pay-stat-value">{{ number_format($baseSalary, 0) }}</div>
                <div class="pay-stat-label">{{ __('Base salary') }} ({{ $currSymbol }})</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="pay-stat-card card border-0 h-100">
            <div class="card-body p-3">
                <div class="pay-stat-icon" style="background:rgba(67,233,123,.12);">📊</div>
                @if($commissionsByCurrency->isEmpty())
                    <div class="pay-stat-value" style="color:#22c55e;">—</div>
                @else
                    @foreach($commissionsByCurrency as $cur => $amount)
                    @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                    <div class="pay-stat-value" style="color:#22c55e;font-size:{{ $commissionsByCurrency->count() > 1 ? '16px' : '22px' }};">
                        +{{ number_format($amount, 0) }} <span style="font-size:11px;opacity:.6;">{{ $sym }}</span>
                    </div>
                    @endforeach
                @endif
                <div class="pay-stat-label">{{ __('Commissions') }} — {{ $appointments->count() }} {{ __('appointments') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="pay-stat-card card border-0 h-100">
            <div class="card-body p-3">
                <div class="pay-stat-icon" style="background:rgba(239,68,68,.12);">📉</div>
                @if($deductionsByCurrency->isEmpty())
                    <div class="pay-stat-value" style="color:#ef4444;">0</div>
                @else
                    @foreach($deductionsByCurrency as $cur => $amt)
                    @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                    <div class="pay-stat-value" style="color:#ef4444;font-size:{{ $deductionsByCurrency->count() > 1 ? '16px' : '22px' }};">
                        -{{ number_format($amt, 0) }} <span style="font-size:11px;opacity:.6;">{{ $sym }}</span>
                    </div>
                    @endforeach
                @endif
                <div class="pay-stat-label">{{ __('Deductions') }} — {{ $deductions->count() }} {{ __('records') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="pay-stat-card card border-0 h-100" style="border-color:rgba(67,233,123,.25) !important;">
            <div class="card-body p-3">
                <div class="pay-stat-icon" style="background:rgba(67,233,123,.12);">✅</div>
                <div class="pay-stat-value" style="color:#22c55e;">{{ number_format($netPay, 0) }}</div>
                <div class="pay-stat-label">{{ __('Net pay') }} ({{ $currSymbol }})</div>
            </div>
        </div>
    </div>
</div>

{{-- Compensation type info ───────────────────────────────────────────────── --}}
@if($compensation)
<div class="card border-0 mb-4" style="border-radius:14px;background:rgba(102,126,234,.06);border:1.5px solid rgba(102,126,234,.12) !important;">
    <div class="card-body py-3 px-4 d-flex flex-wrap gap-3 align-items-center">
        <span style="font-size:12px;font-weight:700;opacity:.5;text-transform:uppercase;letter-spacing:.5px;">{{ __('Compensation setup') }}</span>
        @if(in_array($compensation->type, ['salary','mixed']))
        <span class="badge" style="background:rgba(102,126,234,.15);color:#667eea;font-weight:600;font-size:11px;">
            💰 {{ __('Fixed salary') }}: {{ number_format($compensation->base_amount,0) }} {{ $currSymbol }} / {{ __($compensation->pay_period) }}
        </span>
        @endif
        @if(in_array($compensation->type, ['commission','mixed']))
            @if($compensation->commission_type === 'flat')
            <span class="badge" style="background:rgba(67,233,123,.12);color:#22c55e;font-weight:600;font-size:11px;">
                📊 {{ __('Flat commission') }}: {{ $compensation->commission_rate }}%
            </span>
            @elseif($compensation->commission_type === 'per_service')
            <span class="badge" style="background:rgba(67,233,123,.12);color:#22c55e;font-weight:600;font-size:11px;">
                ✂️ {{ __('Per-service commission') }}
            </span>
            @endif
        @endif
        @if(!$compensation->type)
        <span style="font-size:12px;opacity:.45;">{{ __('No compensation type set') }}</span>
        @endif
    </div>
</div>
@endif

{{-- Appointments ─────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden;">
    <div class="card-body" style="padding:20px 22px 8px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="pay-section-title mb-0">{{ __('Completed appointments') }}</div>
            <span style="font-size:12px;font-weight:700;background:rgba(67,233,123,.1);color:#22c55e;padding:3px 10px;border-radius:20px;">
                {{ $appointments->count() }} {{ __('appointments') }}
            </span>
        </div>
    </div>
    @if($appointments->isEmpty())
    <div class="text-center py-4" style="opacity:.45;">
        <div style="font-size:28px;margin-bottom:6px;">📅</div>
        <div style="font-size:13px;">{{ __('No completed appointments this month.') }}</div>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 pay-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">{{ __('Date') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Service') }}</th>
                    <th class="text-end">{{ __('Price') }}</th>
                    <th class="text-end">{{ __('Rate') }}</th>
                    <th class="text-end pe-4">{{ __('Commission') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $appt)
                <tr>
                    <td class="ps-4" style="font-size:12px;color:var(--muted);">
                        {{ $appt->start_time->format('d/m') }}
                        <div style="font-size:11px;opacity:.4;">{{ $appt->start_time->format('H:i') }}</div>
                    </td>
                    <td>
                        <span style="font-weight:600;font-size:13px;">
                            {{ $appt->customer?->name ?? __('Walk-in') }}
                        </span>
                    </td>
                    <td style="font-size:12px;">
                        {{ $appt->service
                            ? (app()->getLocale()==='ar'
                                ? ($appt->service->name_ar ?: $appt->service->name_en)
                                : ($appt->service->name_en ?: $appt->service->name_ar))
                            : '—' }}
                    </td>
                    <td class="text-end" style="font-weight:600;font-size:13px;">
                        @php $apptSym = config("booksy.currencies.{$appt->commission_currency}.symbol", $appt->commission_currency); @endphp
                        {{ number_format($appt->total_price, 2) }}
                        <span style="font-size:10px;opacity:.45;">{{ $apptSym }}</span>
                    </td>
                    <td class="text-end">
                        @if($appt->commission_rate > 0)
                        <span class="comm-badge">{{ $appt->commission_rate }}%</span>
                        @else
                        <span style="opacity:.3;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        @if($appt->commission_earned > 0)
                        <span style="font-weight:700;color:#22c55e;font-size:13px;">
                            +{{ number_format($appt->commission_earned, 2) }} <span style="font-size:10px;opacity:.6;">{{ $apptSym }}</span>
                        </span>
                        @else
                        <span style="opacity:.3;font-size:12px;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid rgba(255,255,255,.08);">
                    <td colspan="5" class="ps-4 py-3" style="font-weight:700;font-size:12px;opacity:.5;">{{ __('Total commissions') }}</td>
                    <td class="text-end pe-4 py-3">
                        @if($commissionsByCurrency->isEmpty())
                            <span style="opacity:.3;">—</span>
                        @else
                            @foreach($commissionsByCurrency as $cur => $amount)
                            @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                            <div style="font-weight:800;color:#22c55e;font-size:15px;">
                                +{{ number_format($amount, 2) }} <span style="font-size:11px;opacity:.6;">{{ $sym }}</span>
                            </div>
                            @endforeach
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

{{-- Deductions ───────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden;">
    <div class="card-body" style="padding:20px 22px 8px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="pay-section-title mb-0">{{ __('Deductions this month') }}</div>
            @if($deductionsByCurrency->isNotEmpty())
            <div class="d-flex gap-2 flex-wrap">
                @foreach($deductionsByCurrency as $cur => $amt)
                @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                <span style="font-size:12px;font-weight:700;background:rgba(239,68,68,.1);color:#ef4444;padding:3px 10px;border-radius:20px;">
                    -{{ number_format($amt, 0) }} {{ $sym }}
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @if($deductions->isEmpty())
    <div class="text-center py-4" style="opacity:.45;">
        <div style="font-size:28px;margin-bottom:6px;">✅</div>
        <div style="font-size:13px;">{{ __('No deductions this month.') }}</div>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 pay-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Reason / Notes') }}</th>
                    <th class="text-end pe-4">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deductions as $ded)
                <tr>
                    <td class="ps-4" style="font-size:12px;">{{ $ded->deduction_date->format('d/m/Y') }}</td>
                    <td>
                        <span class="ded-badge {{ $ded->type }}">
                            @if($ded->type === 'absence') 🚫 {{ __('Absence') }}
                            @elseif($ded->type === 'tardiness') ⏰ {{ __('Tardiness') }}
                            @else 📌 {{ __('Other') }}
                            @endif
                        </span>
                    </td>
                    <td style="max-width:220px;font-size:12px;color:var(--muted);">
                        {{ $ded->notes ?: '—' }}
                        @if($ded->hours)
                        <span style="opacity:.5;font-size:11px;">({{ $ded->hours }}h)</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        @php $dedSym = config("booksy.currencies.{$ded->currency}.symbol", $ded->currency ?? $currSymbol); @endphp
                        <span style="font-weight:700;color:#ef4444;font-size:13px;">
                            -{{ number_format($ded->amount, 2) }} {{ $dedSym }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Net pay box ──────────────────────────────────────────────────────────── --}}
<div class="net-box mb-4">
    <div class="row align-items-center g-3">
        <div class="col">
            <div style="font-size:13px;font-weight:700;opacity:.5;margin-bottom:10px;">{{ __('Payroll summary') }}</div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                {{-- Base salary --}}
                <div>
                    <div style="font-size:11px;opacity:.45;">{{ __('Base salary') }}</div>
                    <div style="font-size:16px;font-weight:700;">{{ number_format($baseSalary,0) }} {{ $currSymbol }}</div>
                </div>

                {{-- Commissions in salary currency --}}
                @if($commInSalaryCurrency > 0)
                <div style="opacity:.3;font-size:20px;">+</div>
                <div>
                    <div style="font-size:11px;opacity:.45;">{{ __('Commissions') }} ({{ $currency }})</div>
                    <div style="font-size:16px;font-weight:700;color:#22c55e;">{{ number_format($commInSalaryCurrency,0) }} {{ $currSymbol }}</div>
                </div>
                @endif

                {{-- Deductions in salary currency --}}
                @if($deductedInSalaryCurrency > 0)
                <div style="opacity:.3;font-size:20px;">−</div>
                <div>
                    <div style="font-size:11px;opacity:.45;">{{ __('Deductions') }} ({{ $currency }})</div>
                    <div style="font-size:16px;font-weight:700;color:#ef4444;">{{ number_format($deductedInSalaryCurrency,0) }} {{ $currSymbol }}</div>
                </div>
                @endif

                {{-- Net in salary currency --}}
                <div style="opacity:.3;font-size:20px;">=</div>
                <div>
                    <div style="font-size:11px;opacity:.45;">{{ __('Net pay') }} ({{ $currency }})</div>
                    <div style="font-size:22px;font-weight:900;color:#22c55e;">{{ number_format($netPay,0) }} {{ $currSymbol }}</div>
                </div>

                {{-- Other currency commissions --}}
                @foreach($otherCommissions as $cur => $amount)
                @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                <div style="border-inline-start:2px solid rgba(67,233,123,.3);padding-inline-start:16px;">
                    <div style="font-size:11px;opacity:.45;">+ {{ __('Commission') }} ({{ $cur }})</div>
                    <div style="font-size:18px;font-weight:800;color:#22c55e;">{{ number_format($amount,0) }} {{ $sym }}</div>
                </div>
                @endforeach

                {{-- Other currency deductions --}}
                @foreach($otherDeductions as $cur => $amount)
                @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                <div style="border-inline-start:2px solid rgba(239,68,68,.3);padding-inline-start:16px;">
                    <div style="font-size:11px;opacity:.45;">− {{ __('Deductions') }} ({{ $cur }})</div>
                    <div style="font-size:18px;font-weight:800;color:#ef4444;">{{ number_format($amount,0) }} {{ $sym }}</div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-sm rounded-pill px-4"
                    style="background:rgba(67,233,123,.15);color:#22c55e;border:1.5px solid rgba(67,233,123,.3);font-weight:700;">
                <i data-feather="printer" style="width:13px;height:13px;"></i>
                {{ __('Print') }}
            </button>
        </div>
    </div>
</div>

{{-- Payment status & periods --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
            <div class="d-flex align-items-center gap-2">
                <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">💰 {{ __('Salary Disbursement') }}</span>
                <span style="font-size:11px;font-weight:700;padding:2px 10px;border-radius:12px;background:rgba(102,126,234,.12);color:#667eea;">
                    {{ $payPeriod === 'weekly' ? __('Weekly') : ($payPeriod === 'daily' ? __('Daily') : __('Monthly')) }}
                </span>
            </div>
            <span class="tx-12 text-muted">
                {{ collect($periods)->where('paid', true)->count() }}/{{ count($periods) }} {{ __('paid') }}
            </span>
        </div>

        @foreach($periods as $period)
        <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom:1px solid rgba(255,255,255,.04);">
            <div class="d-flex align-items-center gap-3">
                @if($period['paid'])
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(34,197,94,.12);display:flex;align-items:center;justify-content:center;font-size:16px;">✅</div>
                @else
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(251,191,36,.12);display:flex;align-items:center;justify-content:center;font-size:16px;">⏳</div>
                @endif
                <div>
                    <div class="fw-semibold tx-13">{{ $period['label'] }}</div>
                    @if($period['paid'])
                        <div class="text-muted tx-11">
                            {{ $period['paidAt']->translatedFormat('d M Y — H:i') }}
                            · {{ number_format($period['amount'], 0) }} {{ $currSymbol }}
                        </div>
                    @else
                        <div class="text-muted tx-11">
                            {{ $period['apptsCount'] }} {{ __('appts') }}
                            @if(isset($period['commissionsByCurrency']))
                                @foreach($period['commissionsByCurrency'] as $cur => $amt)
                                @php $sym = config("booksy.currencies.{$cur}.symbol", $cur); @endphp
                                · <span style="color:#22c55e;">+{{ number_format($amt, 0) }} {{ $sym }}</span>
                                @endforeach
                            @endif
                            @if($period['deduction'] > 0)
                                · <span style="color:#ef4444;">-{{ number_format($period['deduction'], 0) }} {{ $currSymbol }}</span>
                            @endif
                            · {{ __('Net') }}: <span class="fw-bold">{{ number_format($period['netAmount'], 0) }} {{ $currSymbol }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @if(!$period['paid'] && $netPay > 0)
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold"
                        style="background:#22c55e;color:#fff;border:none;font-size:12px;"
                        data-bs-toggle="modal" data-bs-target="#payModal-{{ $period['type'] }}-{{ $period['key'] ?? 0 }}">
                    <i data-feather="check-circle" style="width:12px;height:12px;margin-inline-end:3px;"></i>
                    {{ __('Pay') }}
                </button>
            @elseif($period['paid'])
                <span style="font-size:11px;font-weight:700;color:#22c55e;">✓ {{ __('Paid') }}</span>
            @endif
        </div>
        @endforeach
    </div>
</div>

{{-- Pay modals for each unpaid period --}}
@foreach($periods as $period)
@if(!$period['paid'] && $netPay > 0)
@php $periodAmount = $period['netAmount']; @endphp
<div class="modal fade" id="payModal-{{ $period['type'] }}-{{ $period['key'] ?? 0 }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content rounded-4">
            <form method="POST" action="{{ route('company.employees.payroll.pay', $employee) }}">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="pay_period" value="{{ $payPeriod }}">
                @if($period['type'] === 'week')
                <input type="hidden" name="week_number" value="{{ $period['key'] }}">
                @elseif($period['type'] === 'day')
                <input type="hidden" name="day" value="{{ $period['key'] }}">
                @endif
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">💰 {{ __('Disburse Salary') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="p-3 rounded-3 mb-3" style="background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.15);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="tx-12 text-muted">{{ __('Employee') }}</span>
                            <span class="fw-bold tx-13">{{ $employee->localizedName() }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="tx-12 text-muted">{{ __('Period') }}</span>
                            <span class="fw-bold tx-13">{{ $period['label'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="tx-12 text-muted">{{ __('Net amount') }}</span>
                            <span class="fw-bold" style="font-size:18px;color:#22c55e;">{{ number_format($periodAmount, 0) }} {{ $currSymbol }}</span>
                        </div>
                    </div>
                    <p class="text-muted tx-12 mb-3">
                        {{ __('This will deduct :amount :currency from the branch cash box and record it as a salary expense.', ['amount' => number_format($periodAmount, 0), 'currency' => $currSymbol]) }}
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold tx-13">{{ __('Payment method') }}</label>
                        <select name="payment_method" class="form-select">
                            @foreach(\App\Models\BranchPayment::PAYMENT_METHODS as $pmKey => $pmMeta)
                                @if($pmKey !== 'later')
                                <option value="{{ $pmKey }}">{{ $pmMeta['icon'] }} {{ __($pmMeta['label_key']) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold tx-13">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" class="form-control" placeholder="{{ __('e.g. Paid via bank transfer ref #123') }}">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn rounded-pill px-4 fw-bold" style="background:#22c55e;color:#fff;border:none;">
                        ✓ {{ __('Confirm Payment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

</div>
@endsection
