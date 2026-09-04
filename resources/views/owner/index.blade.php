@extends('owner.dashboard')

@section('content')
@php
    $totalAppt      = (int)($stats['appointments_total']    ?? 0);
    $pendingAppt    = (int)($stats['appointments_pending']  ?? 0);
    $companiesCount = (int)($stats['companies']             ?? 0);
    $branchesCount  = (int)($stats['branches']              ?? 0);
    $servicesCount  = (int)($stats['services']              ?? 0);
    $waitlistWaiting= (int)($stats['waitlist_waiting']      ?? 0);
    $isAr = app()->getLocale() === 'ar';
@endphp

@push('owner-styles')
<style>
/* Client-side widget pagination */
.bk-pager-nav { display:flex; align-items:center; justify-content:center; gap:12px; padding-top:12px; margin-top:10px; border-top:1px solid var(--bk-border); }
.bk-pager-btn { width:32px; height:32px; border-radius:9px; border:1px solid var(--bk-border); background:var(--bk-surface);
    color:var(--bk-text-soft); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; }
.bk-pager-btn:hover:not(:disabled) { border-color:var(--bk-accent); color:var(--bk-accent); background:var(--bk-accent-wash); }
.bk-pager-btn:disabled { opacity:.4; cursor:not-allowed; }
.bk-pager-btn i, .bk-pager-btn svg { width:15px; height:15px; }
.bk-pager-info { font-size:.8rem; font-weight:600; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; min-width:52px; text-align:center; }
/* Beat Bootstrap's `.d-flex/.d-block { display:…!important }` on paged-out rows */
.bk-pager .bk-pager-hide { display:none !important; }
</style>
@endpush

<div class="page-content">

{{-- ════ HERO HEADER ════ --}}
<div class="bk-hero bk-a1">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="bk-hero-title">{{ __('Platform') }} <span>{{ __('Dashboard') }}</span></h2>
            <p class="bk-hero-sub">
                <i data-feather="activity" style="width:13px;height:13px;display:inline;margin-right:5px;"></i>
                {{ __('Manage all companies, branches, and bookings from one place.') }}
            </p>
        </div>
        <div class="bk-hero-actions">
            <a href="{{ route('owner.companies.index') }}"
               class="bk-navbar-action bk-navbar-action-ghost d-flex align-items-center gap-2">
                <i data-feather="briefcase" style="width:14px;height:14px;"></i>
                {{ __('Companies') }}
            </a>
            <a href="{{ route('owner.appointments.index') }}"
               class="bk-navbar-action bk-navbar-action-primary d-flex align-items-center gap-2">
                <i data-feather="calendar" style="width:14px;height:14px;"></i>
                {{ __('Appointments') }}
            </a>
        </div>
    </div>
</div>

@include('owner.partials.flash')

{{-- ════ ACTION NEEDED ════ --}}
@php $canBilling = \Illuminate\Support\Facades\Gate::allows('owner-can', 'billing.view'); @endphp
@if(($alerts['pending_companies'] ?? 0) > 0 || ($canBilling && (($alerts['expiring_soon'] ?? 0) + ($alerts['expired'] ?? 0)) > 0))
<div class="row g-3 mb-4">
    @if($alerts['pending_companies'] > 0)
    <div class="col-md-4">
        <a href="{{ route('owner.companies.index', ['status' => 'pending']) }}"
           class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none border-start border-warning border-4">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                    <i data-feather="user-plus" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div class="fw-bold tx-18">{{ $alerts['pending_companies'] }}</div>
                    <div class="text-muted tx-13">{{ __('Companies awaiting approval') }}</div>
                </div>
            </div>
        </a>
    </div>
    @endif
    @if($canBilling && $alerts['expiring_soon'] > 0)
    <div class="col-md-4">
        <a href="{{ route('owner.subscriptions.index', ['state' => 'expiring_soon']) }}"
           class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none border-start border-info border-4">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                    <i data-feather="clock" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div class="fw-bold tx-18">{{ $alerts['expiring_soon'] }}</div>
                    <div class="text-muted tx-13">{{ __('Subscriptions expiring within 7 days') }}</div>
                </div>
            </div>
        </a>
    </div>
    @endif
    @if($canBilling && $alerts['expired'] > 0)
    <div class="col-md-4">
        <a href="{{ route('owner.subscriptions.index', ['state' => 'expired']) }}"
           class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none border-start border-danger border-4">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                    <i data-feather="x-circle" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div class="fw-bold tx-18">{{ $alerts['expired'] }}</div>
                    <div class="text-muted tx-13">{{ __('Expired subscriptions to follow up') }}</div>
                </div>
            </div>
        </a>
    </div>
    @endif
</div>
@endif

{{-- ════ RECENT ACTIVITY + NEEDS HELP ════ --}}
<div class="row g-3 mb-4">
    {{-- Recent business activity --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i data-feather="activity" style="width:16px;height:16px;color:var(--bk-accent);"></i>
                        {{ __('Recent activity') }}
                    </h6>
                    <a href="{{ route('owner.companies.index') }}" class="tx-12 text-decoration-none" style="color:var(--bk-accent);">{{ __('All companies') }}</a>
                </div>
                <div class="bk-pager" data-page-size="5">
                    <div class="bk-pager-items">
                @forelse($recentActivity as $act)
                    @php $name = $act->company?->localizedName() ?? ($act->email_attempted ?? __('Unknown')); @endphp
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:34px;height:34px;background:{{ $act->successful ? 'var(--bk-success-bg)' : 'var(--bk-danger-bg)' }};color:{{ $act->successful ? 'var(--bk-success)' : 'var(--bk-danger)' }};">
                            <i data-feather="{{ $act->successful ? 'log-in' : 'alert-triangle' }}" style="width:15px;height:15px;"></i>
                        </div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="tx-13 fw-semibold text-truncate">
                                {{ $name }}
                                <span class="fw-normal text-muted">{{ $act->successful ? __('logged in') : __('failed to log in') }}</span>
                            </div>
                            <div class="tx-11 text-muted">{{ $act->created_at?->diffForHumans() }} · {{ $act->ip }}</div>
                        </div>
                        @if($act->company)
                            <a href="{{ route('owner.companies.show', $act->company_id) }}" class="tx-11 text-decoration-none flex-shrink-0" style="color:var(--bk-accent);">{{ __('View') }}</a>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-4 tx-13">{{ __('No activity yet') }}</div>
                @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Businesses needing help --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i data-feather="life-buoy" style="width:16px;height:16px;color:var(--bk-warning);"></i>
                    {{ __('Businesses needing help') }}
                </h6>
                <div class="bk-pager" data-page-size="5">
                    <div class="bk-pager-items">
                @forelse($needsHelp as $row)
                    <a href="{{ route('owner.companies.show', $row['company']->id) }}"
                       class="d-block text-decoration-none py-2 border-bottom">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="tx-13 fw-semibold text-truncate" style="color:var(--bk-text);max-width:60%;">{{ $row['company']->localizedName() }}</span>
                            <span class="tx-11 fw-bold" style="color:{{ $row['percent'] < 50 ? 'var(--bk-danger)' : 'var(--bk-warning)' }};">{{ $row['percent'] }}%</span>
                        </div>
                        <div class="progress" style="height:5px;background:var(--bk-border);">
                            <div class="progress-bar" role="progressbar"
                                 style="width:{{ $row['percent'] }}%;background:{{ $row['percent'] < 50 ? 'var(--bk-danger)' : 'var(--bk-warning)' }};"
                                 aria-valuenow="{{ $row['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="tx-11 text-muted mt-1">
                            {{ __('Signed up :days days ago', ['days' => $row['days_old']]) }}
                            @if($row['last_login'])
                                · {{ __('last seen :time', ['time' => $row['last_login']->diffForHumans()]) }}
                            @else
                                · {{ __('never logged in') }}
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="text-center text-muted py-4 tx-13">
                        <i data-feather="check-circle" style="width:22px;height:22px;color:var(--bk-success);"></i>
                        <div class="mt-2">{{ __('All recent businesses are set up') }}</div>
                    </div>
                @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ════ STAT CARDS — horizontal style ════ --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-xl bk-a1">
        <div class="bk-stat" data-accent="gold">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-gold">
                    <i data-feather="calendar" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Total Bookings') }}</div>
                    <div class="bk-stat-sub">{{ __('all time') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $totalAppt }}">{{ number_format($totalAppt) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:100%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a2">
        <div class="bk-stat" data-accent="orange">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-orange">
                    <i data-feather="clock" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Pending') }}</div>
                    <div class="bk-stat-sub">{{ __('need action') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $pendingAppt }}"
                 style="{{ $pendingAppt>0?'animation:bk-pulse 2s ease infinite;':'' }}">
                {{ number_format($pendingAppt) }}
            </div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ $totalAppt>0?round($pendingAppt/$totalAppt*100):0 }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a3">
        <div class="bk-stat" data-accent="gold">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-gold">
                    <i data-feather="briefcase" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Companies') }}</div>
                    <div class="bk-stat-sub">{{ $branchesCount }} {{ __('branches') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $companiesCount }}">{{ number_format($companiesCount) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($companiesCount*8,100) }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a4">
        <div class="bk-stat" data-accent="gold">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-gold">
                    <i data-feather="scissors" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Services') }}</div>
                    <div class="bk-stat-sub">{{ $servicesCount }} {{ __('total') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $servicesCount }}">{{ number_format($servicesCount) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($servicesCount*3,100) }}%;"></div></div>
        </div>
    </div>

    <div class="col-6 col-xl bk-a5">
        <div class="bk-stat" data-accent="gold">
            <div class="bk-stat-left">
                <div class="bk-stat-icon bk-icon-gold">
                    <i data-feather="users" style="width:22px;height:22px;"></i>
                </div>
                <div class="bk-stat-info">
                    <div class="bk-stat-label">{{ __('Waitlist') }}</div>
                    <div class="bk-stat-sub">{{ __('waiting customers') }}</div>
                </div>
            </div>
            <div class="bk-stat-num bk-counter" data-target="{{ $waitlistWaiting }}">{{ number_format($waitlistWaiting) }}</div>
            <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:{{ min($waitlistWaiting*10,100) }}%;"></div></div>
        </div>
    </div>

</div>

{{-- ════ QUICK ACTIONS ════ --}}
<div class="card shadow-sm mb-4 bk-a3">
    <div class="card-body py-3">
        <div class="bk-sh mb-3">
            <span class="bk-sh-title">{{ __('Quick Actions') }}</span>
        </div>
        <div class="bk-qa-grid">
            <a href="{{ route('owner.companies.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="briefcase" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Companies') }}</span>
            </a>
            <a href="{{ route('owner.branches.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="map-pin" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Branches') }}</span>
            </a>
            <a href="{{ route('owner.appointments.index') }}" class="bk-qa">
                <div class="bk-qa-ic">
                    <i data-feather="calendar" style="width:19px;height:19px;"></i>
                    @if($pendingAppt>0)<span class="bk-qa-dot"></span>@endif
                </div>
                <span class="bk-qa-lbl">{{ __('Appointments') }}</span>
            </a>
            <a href="{{ route('owner.categories.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="layers" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Categories') }}</span>
            </a>
            <a href="{{ route('owner.service-categories.index') }}" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="tag" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('Svc. Categories') }}</span>
            </a>
            <a href="{{ route('front.index') }}" target="_blank" class="bk-qa">
                <div class="bk-qa-ic"><i data-feather="globe" style="width:19px;height:19px;"></i></div>
                <span class="bk-qa-lbl">{{ __('View Site') }}</span>
            </a>
        </div>
    </div>
</div>

{{-- ════ CHARTS ════ --}}
<div class="row g-4">

    {{-- Activity Chart + filter --}}
    <div class="col-lg-8 bk-a4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Booking Activity') }}</span>
                    <div class="bk-filter-tabs" id="bk-chart-filter">
                        <button class="bk-filter-tab" data-range="today">{{ __('Today') }}</button>
                        <button class="bk-filter-tab" data-range="week">{{ __('Week') }}</button>
                        <button class="bk-filter-tab active" data-range="month">{{ __('Month') }}</button>
                        <button class="bk-filter-tab" data-range="year">{{ __('Year') }}</button>
                    </div>
                </div>
                <div id="bk-activity-chart" style="min-height:280px;"></div>
            </div>
        </div>
    </div>

    {{-- Donut + mini stats --}}
    <div class="col-lg-4 bk-a5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('By Status') }}</span>
                </div>
                <div id="storageChart"></div>
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="rounded-3 p-2 text-center"
                             style="background:rgba(244,166,66,.08);border:1px solid rgba(244,166,66,.15);">
                            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;color:#f4a642;letter-spacing:1px;">{{ __('Pending') }}</div>
                            <div style="font-size:1.4rem;font-weight:900;color:#f4a642;font-family:'Poppins',sans-serif;">{{ $pendingAppt }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-3 p-2 text-center"
                             style="background:rgba(43,207,126,.07);border:1px solid rgba(43,207,126,.15);">
                            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;color:#2bcf7e;letter-spacing:1px;">{{ __('Waitlist') }}</div>
                            <div style="font-size:1.4rem;font-weight:900;color:#2bcf7e;font-family:'Poppins',sans-serif;">{{ $waitlistWaiting }}</div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('owner.appointments.index') }}"
                   class="btn btn-primary w-100 rounded-pill mt-3 fw-bold">
                    {{ __('Manage Appointments') }}
                </a>
            </div>
        </div>
    </div>

</div>

{{-- ════ MONTHLY CHART ════ --}}
<div class="row g-4 mt-0">
    <div class="col-12 bk-a5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Monthly Appointments') }}</span>
                    <span style="font-size:.75rem;opacity:.35;">{{ __('Last 12 months — all companies') }}</span>
                </div>
                <div id="monthlySalesChart"></div>
            </div>
        </div>
    </div>
</div>

{{-- ════ RECENT APPOINTMENTS ════ --}}
<div class="row g-4 mt-0">

    {{-- Timeline --}}
    <div class="col-lg-4 bk-a5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Latest Activity') }}</span>
                    <a href="{{ route('owner.appointments.index') }}" class="bk-sh-link">
                        {{ __('All') }} <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                    </a>
                </div>
                <div class="bk-pager" data-page-size="5">
                    <div class="bk-pager-items">
                @forelse($recentAppointments as $row)
                @php
                    $ic  = $row->status->color();
                    $ini = strtoupper(substr($row->customer?->name ?? 'C', 0, 1));
                @endphp
                <a href="{{ route('owner.appointments.show', $row) }}"
                   style="display:flex;align-items:flex-start;gap:12px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.05);text-decoration:none;transition:background .15s;"
                   class="bk-appt-row">
                    <div style="width:38px;height:38px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;background:{{ $ic }}22;color:{{ $ic }};">{{ $ini }}</div>
                    <div style="flex:1;overflow:hidden;">
                        <div style="font-size:.84rem;font-weight:600;color:rgba(255,255,255,.8);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row->customer?->name ?? __('Customer') }}</div>
                        <div style="font-size:.73rem;color:rgba(255,255,255,.4);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row->service?->localizedName() ?? '—' }} · {{ $row->branch?->localizedName() ?? '—' }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:.7rem;color:rgba(255,255,255,.3);">{{ $row->start_time?->format('M j H:i') }}</div>
                        <x-appointment-status :status="$row->status" class="bk-badge mt-1 d-inline-flex" />
                    </div>
                </a>
                @empty
                <div class="bk-empty">
                    <div class="bk-empty-ic"><i data-feather="calendar" style="width:24px;height:24px;"></i></div>
                    <p>{{ __('No appointments yet.') }}</p>
                </div>
                @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="col-lg-8 bk-a6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="bk-sh">
                    <span class="bk-sh-title">{{ __('Upcoming & Recent') }}</span>
                    <a href="{{ route('owner.appointments.index') }}" class="bk-sh-link">
                        {{ __('Full list') }} <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                    </a>
                </div>

                @if($recentAppointments->count())
                @php $byStatus = $recentAppointments->countBy(fn ($a) => $a->status->value); @endphp
                <div class="bk-color-bar">
                    @foreach($byStatus as $st => $cnt)
                    <span style="flex:{{ $cnt }};background:{{ \App\Enums\AppointmentStatus::from($st)->color() }};"></span>
                    @endforeach
                </div>
                @endif

                <div class="bk-pager" data-page-size="5">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Service / Branch') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Customer') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bk-pager-items">
                            @forelse($recentAppointments as $row)
                            <tr class="bk-table-row"
                                onclick="location.href='{{ route('owner.appointments.show', $row) }}'">
                                <td class="text-muted tx-12 fw-semibold">#{{ $row->id }}</td>
                                <td>
                                    <div class="fw-semibold tx-13">{{ $row->service?->localizedName() ?? '—' }}</div>
                                    <small class="text-muted opacity-50">{{ $row->branch?->localizedName() ?? '—' }}</small>
                                </td>
                                <td class="text-muted tx-12 text-nowrap">
                                    <div>{{ $row->start_time?->format('d M') }}</div>
                                    <small class="opacity-50">{{ $row->start_time?->format('H:i') }}</small>
                                </td>
                                <td><x-appointment-status :status="$row->status" class="bk-badge" /></td>
                                <td class="tx-13">{{ $row->customer?->name ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="bk-empty">
                                        <div class="bk-empty-ic"><i data-feather="calendar" style="width:24px;height:24px;"></i></div>
                                        <p>{{ __('No rows yet.') }}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </div>{{-- .bk-pager --}}
            </div>
        </div>
    </div>

</div>

</div>{{-- .page-content --}}

@push('scripts')
<script>
(function () {
    'use strict';
    var rtl = document.documentElement.getAttribute('dir') === 'rtl';

    function buildPager(pager) {
        var size = parseInt(pager.getAttribute('data-page-size'), 10) || 6;
        var wrap = pager.querySelector('.bk-pager-items');
        if (!wrap) return;
        var items = Array.prototype.filter.call(wrap.children, function (el) { return el.nodeType === 1; });
        if (items.length <= size) return; // nothing to page

        var pageCount = Math.ceil(items.length / size);
        var current = 0;

        var nav = document.createElement('div');
        nav.className = 'bk-pager-nav';
        nav.innerHTML =
            '<button type="button" class="bk-pager-btn" data-dir="prev"><i data-feather="chevron-' + (rtl ? 'right' : 'left') + '"></i></button>' +
            '<span class="bk-pager-info"></span>' +
            '<button type="button" class="bk-pager-btn" data-dir="next"><i data-feather="chevron-' + (rtl ? 'left' : 'right') + '"></i></button>';
        pager.appendChild(nav);

        var info = nav.querySelector('.bk-pager-info');
        var prev = nav.querySelector('[data-dir="prev"]');
        var next = nav.querySelector('[data-dir="next"]');

        function render() {
            items.forEach(function (el, i) {
                el.classList.toggle('bk-pager-hide', Math.floor(i / size) !== current);
            });
            info.textContent = (current + 1) + ' / ' + pageCount;
            prev.disabled = current === 0;
            next.disabled = current === pageCount - 1;
        }
        prev.addEventListener('click', function () { if (current > 0) { current--; render(); } });
        next.addEventListener('click', function () { if (current < pageCount - 1) { current++; render(); } });
        render();
    }

    function init() {
        document.querySelectorAll('.bk-pager[data-page-size]').forEach(buildPager);
        if (typeof feather !== 'undefined') feather.replace();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush

@push('owner-after-template')
@php
    $booksyPayload = [
        'theme'  => request()->cookie('owner_theme','dark'),
        'rtl'    => $isAr,
        'charts' => $chartData,
        'labels' => [
            'appointments' => __('Appointments'),
            'revenue'      => __('Revenue'),
            'count'        => __('Count'),
            'total'        => __('Total'),
            'noData'       => __('No data yet.'),
            'currency'     => config('app.currency','SAR'),
        ],
    ];
@endphp
<script>window.booksyDashboard = @json($booksyPayload);</script>
<script src="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function(){
'use strict';
var p = window.booksyDashboard || {};
var isDark = p.theme !== 'light';
var isRtl  = p.rtl === true;
var charts  = p.charts || {};
var labels  = p.labels || {};
var gold = '{{ request()->cookie('owner_theme','dark') === 'light' ? '#4B5D34' : '#A6BC7E' }}';
var c = isDark
    ? {text:'#b8c3d9', grid:'rgba(255,255,255,.06)', card:'#0c1427', muted:'#7987a1'}
    : {text:'#333',    grid:'rgba(0,0,0,.07)',       card:'#fff',    muted:'#888'};

/* ── Counters ── */
function runCounters(){
    document.querySelectorAll('.bk-counter[data-target]').forEach(function(el){
        var to=parseInt(el.dataset.target)||0;
        if(!to) return;
        var cur=0, step=to/(1400/16);
        var t=setInterval(function(){ cur=Math.min(cur+step,to); el.textContent=Math.floor(cur).toLocaleString(); if(cur>=to)clearInterval(t); },16);
    });
}
setTimeout(runCounters, 250);

/* ── Progress bars ── */
document.querySelectorAll('.bk-stat-bar-fill').forEach(function(el){
    var w=el.style.width; el.style.width='0';
    setTimeout(function(){ el.style.width=w; }, 400);
});

/* ── Activity Chart (filterable) ── */
var activityChart = null;

function getSeriesForRange(range){
    var key = { today:'today', week:'week', month:'month', year:'year' }[range] || 'month';
    var d = charts[key] || charts.month || charts.daily || {};
    return { labels: d.labels||[], data: d.total||[], name: labels.appointments||'Appointments' };
}

function renderActivity(range){
    range = range || 'month';
    var s    = getSeriesForRange(range);
    var node = document.getElementById('bk-activity-chart');
    if(!node || typeof ApexCharts === 'undefined') return;
    var rotateAlways = s.labels.length > 8;

    if(activityChart){
        activityChart.updateOptions({
            series:[{name:s.name, data:s.data}],
            xaxis:{categories:s.labels, labels:{rotate:isRtl?30:-30, rotateAlways:rotateAlways, style:{fontSize:'11px',colors:c.muted}}},
            noData:{text:labels.noData||'No data yet.'}
        }, true, true);
        return;
    }
    activityChart = new ApexCharts(node,{
        chart:{type:'bar',height:280,background:'transparent',toolbar:{show:false},foreColor:c.text,
               animations:{enabled:true,easing:'easeinout',speed:500}},
        plotOptions:{bar:{columnWidth:'55%',borderRadius:4}},
        dataLabels:{enabled:false},
        colors:[gold],
        series:[{name:s.name, data:s.data}],
        xaxis:{categories:s.labels, labels:{style:{fontSize:'11px',colors:c.muted},rotate:isRtl?30:-30,rotateAlways:rotateAlways}, axisBorder:{color:c.grid}, axisTicks:{color:c.grid}},
        yaxis:{min:0,forceNiceScale:true, labels:{formatter:function(v){return Math.round(v);},style:{colors:c.muted}}},
        grid:{borderColor:c.grid, xaxis:{lines:{show:false}}},
        noData:{text:labels.noData||'No data yet.',style:{color:c.muted}},
        tooltip:{theme:isDark?'dark':'light'},
        theme:{mode:isDark?'dark':'light'},
    });
    activityChart.render();
}

/* Filter tabs */
document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('#bk-chart-filter .bk-filter-tab').forEach(function(b){b.classList.remove('active');});
        this.classList.add('active');
        renderActivity(this.dataset.range);
    });
});

/* ── Monthly chart ── */
function renderMonthly(){
    var node = document.getElementById('monthlySalesChart');
    if(!node || typeof ApexCharts==='undefined') return;
    var d = charts.monthly || {};
    new ApexCharts(node,{
        chart:{type:'bar',height:240,background:'transparent',toolbar:{show:false},foreColor:c.text},
        plotOptions:{bar:{columnWidth:'45%',borderRadius:4}},
        dataLabels:{enabled:false},
        colors:[gold],
        series:[{name:labels.appointments||'Appointments',data:d.total||[]}],
        xaxis:{categories:d.labels||[], labels:{style:{fontSize:'11px',colors:c.muted}}, axisBorder:{color:c.grid}, axisTicks:{color:c.grid}},
        yaxis:{min:0,forceNiceScale:true, labels:{formatter:function(v){return Math.round(v);},style:{colors:c.muted}}},
        grid:{borderColor:c.grid, xaxis:{lines:{show:false}}},
        tooltip:{theme:isDark?'dark':'light'},
        theme:{mode:isDark?'dark':'light'},
    }).render();
}

/* ── Donut ── */
function renderDonut(){
    var node = document.getElementById('storageChart');
    if(!node || typeof ApexCharts==='undefined') return;
    var st        = charts.by_status || {};
    var pending   = st.pending   || 0;
    var confirmed = st.confirmed || 0;
    var completed = st.completed || 0;
    var other     = (st.cancelled_total||0)+(st.no_show||0);
    var realTotal = pending + confirmed + completed + other;
    var isEmpty   = realTotal === 0;
    var series    = isEmpty ? [1] : [pending, confirmed, completed, other];
    var clrs      = isEmpty
        ? [isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.07)']
        : ['#f4a642','#2bcf7e','#3dbbd4', isDark ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.08)'];
    new ApexCharts(node,{
        chart:{type:'donut',height:200,background:'transparent',toolbar:{show:false}},
        series:series,
        labels: isEmpty ? ['—'] : ['Pending','Confirmed','Completed','Other'],
        colors:clrs,
        legend:{show:false},
        plotOptions:{pie:{donut:{size:'68%', labels:{show:!isEmpty, total:{
            show:true, label:'Total', color:c.muted, fontSize:'11px',
            formatter:function(){ return isEmpty?'0':String(realTotal); }
        }, value:{color:isDark?'#fff':'#333',fontSize:'18px',fontWeight:700,formatter:function(v){return String(parseInt(v)||0);}} }}}},
        dataLabels:{enabled:false},
        stroke:{width:0},
        theme:{mode:isDark?'dark':'light'},
        tooltip:{theme:isDark?'dark':'light', y:{formatter:function(v){return isEmpty?'0':String(v);}}},
    }).render();
}

renderActivity('month');
renderMonthly();
renderDonut();

setTimeout(function(){
    if(typeof feather !== 'undefined') feather.replace();
}, 50);

})();
</script>
@endpush
@endsection
