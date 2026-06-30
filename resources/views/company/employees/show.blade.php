@extends('company.dashboard')

@push('company-styles')
<style>
.emp-show-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px; padding: 24px 24px 20px;
    margin-bottom: 24px; color: #fff;
    position: relative; overflow: hidden;
}
.emp-show-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
[dir="rtl"] .emp-show-hero::before { right: auto; left: -50px; }
.profile-avatar {
    width: 72px; height: 72px; border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 28px; color: #fff; flex-shrink: 0;
    border: 3px solid rgba(255,255,255,.3);
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 15px; }
.stat-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.stat-chip {
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: 10px 14px; text-align: center;
    flex: 1; min-width: 80px; backdrop-filter: blur(4px);
}
.stat-chip-num { font-size: 20px; font-weight: 900; font-family: 'Poppins', sans-serif; }
.stat-chip-lbl { font-size: 9px; opacity: .55; text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }
.info-card { border-radius: 16px !important; margin-bottom: 14px; }
.info-row {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,.05);
    font-size: 13px; overflow: hidden;
}
.bk-theme-light .info-row { border-bottom-color: rgba(0,0,0,.06); }
.info-row:last-child { border-bottom: none; }
.info-label {
    width: 100px; flex-shrink: 0; font-size: 11px; font-weight: 700;
    opacity: .45; display: flex; align-items: center; gap: 4px;
}
.info-val {
    flex: 1; font-weight: 500; min-width: 0;
    overflow: hidden; text-overflow: ellipsis; word-break: break-all;
}
.emp-tab-bar {
    display: flex; gap: 4px; background: rgba(255,255,255,.05);
    border-radius: 14px; padding: 4px; margin-bottom: 18px;
}
.bk-theme-light .emp-tab-bar { background: rgba(0,0,0,.04); }
.emp-show-tab {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px;
    padding: 9px 8px; border-radius: 10px; border: none; cursor: pointer;
    font-size: 12px; font-weight: 600; color: rgba(255,255,255,.4);
    background: transparent; transition: all .2s; white-space: nowrap;
}
.bk-theme-light .emp-show-tab { color: rgba(0,0,0,.4); }
.emp-show-tab.active {
    background: rgba(102,126,234,.2); color: #a5b4fd;
    box-shadow: 0 2px 8px rgba(102,126,234,.12);
}
.bk-theme-light .emp-show-tab.active { background: rgba(102,126,234,.1); color: #667eea; }
.emp-show-pane { display: none; }
.emp-show-pane.active { display: block; }

@media (max-width: 768px) {
    .emp-show-hero { padding: 18px 16px 16px; border-radius: 16px; }
    .profile-avatar { width: 56px; height: 56px; border-radius: 14px; font-size: 22px; }
    .stat-chip { padding: 8px 10px; min-width: 65px; }
    .stat-chip-num { font-size: 16px; }
    .stat-chip-lbl { font-size: 8px; }
    .info-label { width: 85px; font-size: 10px; }
    .info-row { padding: 9px 12px; font-size: 12px; }
    .emp-show-tab { font-size: 11px; padding: 8px 6px; gap: 3px; }
    .emp-show-tab svg { width: 11px !important; height: 11px !important; }
    .hero-actions { width: 100%; }
    .hero-actions .btn { flex: 1; justify-content: center; font-size: 11px !important; padding: 5px 8px !important; }
}
</style>
@endpush

@section('content')
@php
    $palette = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1','#fda085'];
    $bg = $palette[$employee->id % count($palette)];
    $locale = app()->getLocale();
    $comp = $employee->compensation;
    $contractMeta = \App\Models\Employee::CONTRACT_TYPES[$employee->contract_type] ?? null;
@endphp
<div class="page-content">

    {{-- Hero --}}
    <div class="emp-show-hero bk-a1">
        <div class="position-relative" style="z-index:1;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div class="d-flex align-items-center gap-3" style="min-width:0;">
                    <div class="profile-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">
                        @if($employee->image)
                            <img src="{{ asset('storage/'.$employee->image) }}" alt="">
                        @else
                            {{ strtoupper(mb_substr($employee->name_en ?? $employee->name_ar ?? '?', 0, 1)) }}
                        @endif
                    </div>
                    <div style="min-width:0;">
                        <nav aria-label="breadcrumb" class="mb-1">
                            <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('company.branches.employees.index', $employee->branch) }}" class="text-decoration-none" style="color:rgba(255,255,255,.6);font-size:12px;">{{ $employee->branch->localizedName() }}</a>
                                </li>
                                <li class="breadcrumb-item active" style="color:rgba(255,255,255,.4);font-size:12px;">{{ __('Profile') }}</li>
                            </ol>
                        </nav>
                        <h4 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">
                            {{ $locale === 'ar' ? ($employee->name_ar ?: $employee->name_en) : ($employee->name_en ?: $employee->name_ar) }}
                        </h4>
                        <div class="d-flex flex-wrap gap-2">
                            @if($employee->role)
                            <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:8px;background:rgba(255,255,255,.15);color:#fff;">
                                {{ $locale === 'ar' ? ($employee->role->label_ar ?: $employee->role->label_en) : ($employee->role->label_en ?: $employee->role->label_ar) }}
                            </span>
                            @endif
                            <span style="font-size:11px;font-weight:600;color:{{ $employee->is_active ? '#43e97b' : '#6c757d' }};">
                                ● {{ $employee->is_active ? __('Active') : __('Inactive') }}
                            </span>
                            @if($contractMeta)
                            <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:8px;background:{{ $contractMeta['color'] }}20;color:{{ $contractMeta['color'] }};">
                                {{ $contractMeta['icon'] }} {{ __($contractMeta['label_key']) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap hero-actions">
                    <a href="{{ route('company.employees.payroll', $employee) }}" class="btn btn-sm rounded-pill px-3" style="background:rgba(67,233,123,.2);color:#22c55e;border:1.5px solid rgba(67,233,123,.4);font-weight:600;font-size:12px;">
                        <i data-feather="dollar-sign" style="width:12px;height:12px;"></i> {{ __('Payroll') }}
                    </a>
                    <a href="{{ route('company.employees.edit', $employee) }}" class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);font-weight:600;font-size:12px;">
                        <i data-feather="edit-2" style="width:12px;height:12px;"></i> {{ __('Edit') }}
                    </a>
                    <a href="{{ route('company.branches.employees.index', $employee->branch) }}" class="btn btn-sm rounded-pill px-3" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);border:1.5px solid rgba(255,255,255,.2);font-weight:600;font-size:12px;">
                        <i data-feather="arrow-left" style="width:12px;height:12px;"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>

            {{-- Stats row --}}
            <div class="stat-chips">
                <div class="stat-chip">
                    <div class="stat-chip-num" style="color:#a5b4fd;">{{ $appointmentsThisMonth }}</div>
                    <div class="stat-chip-lbl">{{ __('Appts this month') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="stat-chip-num" style="color:#43e97b;">{{ number_format((float)$revenueThisMonth, 0) }}</div>
                    <div class="stat-chip-lbl">{{ __('Revenue this month') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="stat-chip-num" style="color:#fbbf24;">{{ $completedThisMonth }}</div>
                    <div class="stat-chip-lbl">{{ __('Completed') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="stat-chip-num" style="color:#22c55e;">{{ $attendanceStats['present'] }}</div>
                    <div class="stat-chip-lbl">{{ __('present_count') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="stat-chip-num" style="color:#f59e0b;">{{ $attendanceStats['late'] }}</div>
                    <div class="stat-chip-lbl">{{ __('late_count') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="stat-chip-num" style="color:#ef4444;">{{ $attendanceStats['absent'] }}</div>
                    <div class="stat-chip-lbl">{{ __('absent_count') }}</div>
                </div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row g-3">
        {{-- LEFT: Info cards --}}
        <div class="col-lg-4">

            {{-- Contact Info --}}
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Contact Information') }}</span>
                    </div>
                    @if($employee->email)
                    <div class="info-row">
                        <span class="info-label"><i data-feather="mail" style="width:12px;height:12px;"></i> {{ __('Email') }}</span>
                        <span class="info-val" dir="ltr" style="font-size:12px;">{{ $employee->email }}</span>
                    </div>
                    @endif
                    @if($employee->phone)
                    <div class="info-row">
                        <span class="info-label"><i data-feather="phone" style="width:12px;height:12px;"></i> {{ __('Phone') }}</span>
                        <span class="info-val" dir="ltr">{{ $employee->phone }}</span>
                    </div>
                    @endif
                    @if($employee->bio)
                    <div class="info-row" style="align-items:flex-start;">
                        <span class="info-label"><i data-feather="file-text" style="width:12px;height:12px;"></i> {{ __('Bio') }}</span>
                        <span class="info-val" style="white-space:pre-line;">{{ $employee->bio }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- HR Info --}}
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">📋 {{ __('HR Details') }}</span>
                    </div>
                    @if($employee->hire_date)
                    <div class="info-row">
                        <span class="info-label">📅 {{ __('Hire Date') }}</span>
                        <span class="info-val">{{ $employee->hire_date->translatedFormat('d M Y') }}</span>
                    </div>
                    @endif
                    @if($employee->contract_end_date)
                    <div class="info-row">
                        <span class="info-label">📅 {{ __('Contract End') }}</span>
                        <span class="info-val">
                            {{ $employee->contract_end_date->translatedFormat('d M Y') }}
                            @if($employee->contract_end_date->isPast())
                                <span style="font-size:10px;font-weight:700;background:rgba(239,68,68,.12);color:#ef4444;padding:1px 6px;border-radius:8px;margin-inline-start:4px;">{{ __('Expired') }}</span>
                            @elseif($employee->contract_end_date->diffInDays(now()) <= 30)
                                <span style="font-size:10px;font-weight:700;background:rgba(245,158,11,.12);color:#f59e0b;padding:1px 6px;border-radius:8px;margin-inline-start:4px;">{{ __('Expiring soon') }}</span>
                            @endif
                        </span>
                    </div>
                    @endif
                    @if($employee->national_id)
                    <div class="info-row">
                        <span class="info-label">🪪 {{ __('National ID') }}</span>
                        <span class="info-val" dir="ltr">{{ $employee->national_id }}</span>
                    </div>
                    @endif
                    @if($employee->license_number)
                    <div class="info-row">
                        <span class="info-label">📜 {{ __('License') }}</span>
                        <span class="info-val">
                            {{ $employee->license_number }}
                            @if($employee->license_expiry)
                                <span class="text-muted tx-11">({{ __('exp.') }} {{ $employee->license_expiry->translatedFormat('d/m/Y') }})</span>
                                @if($employee->license_expiry->isPast())
                                    <span style="font-size:10px;font-weight:700;background:rgba(239,68,68,.12);color:#ef4444;padding:1px 6px;border-radius:8px;">{{ __('Expired') }}</span>
                                @endif
                            @endif
                        </span>
                    </div>
                    @endif
                    @if($employee->qualifications)
                    <div class="info-row" style="align-items:flex-start;">
                        <span class="info-label">🎓 {{ __('Qualifications') }}</span>
                        <span class="info-val" style="white-space:pre-line;">{{ $employee->qualifications }}</span>
                    </div>
                    @endif
                    @if(!$employee->hire_date && !$employee->national_id && !$employee->license_number && !$employee->qualifications)
                    <div class="px-3 py-3">
                        <p class="text-muted tx-12 mb-0" style="opacity:.5;">{{ __('No HR details added yet.') }}
                            <a href="{{ route('company.employees.edit', $employee) }}">{{ __('Edit') }}</a>
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Bank Info --}}
            @if($employee->iban || $employee->bank_name)
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">🏦 {{ __('Bank Details') }}</span>
                    </div>
                    @if($employee->bank_name)
                    <div class="info-row">
                        <span class="info-label">🏦 {{ __('Bank') }}</span>
                        <span class="info-val">{{ $employee->bank_name }}</span>
                    </div>
                    @endif
                    @if($employee->iban)
                    <div class="info-row">
                        <span class="info-label">💳 {{ __('IBAN') }}</span>
                        <span class="info-val" dir="ltr" style="font-family:monospace;font-size:11px;">{{ $employee->iban }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Emergency Contact --}}
            @if($employee->emergency_contact_name)
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">🆘 {{ __('Emergency Contact') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">👤 {{ __('Name') }}</span>
                        <span class="info-val">{{ $employee->emergency_contact_name }}</span>
                    </div>
                    @if($employee->emergency_contact_phone)
                    <div class="info-row">
                        <span class="info-label">📞 {{ __('Phone') }}</span>
                        <span class="info-val" dir="ltr">{{ $employee->emergency_contact_phone }}</span>
                    </div>
                    @endif
                    @if($employee->emergency_contact_relation)
                    <div class="info-row">
                        <span class="info-label">👥 {{ __('Relation') }}</span>
                        <span class="info-val">{{ $employee->emergency_contact_relation }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Compensation --}}
            @if($comp)
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">💰 {{ __('Compensation') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📋 {{ __('Type') }}</span>
                        <span class="info-val">{{ __(ucfirst($comp->type)) }}</span>
                    </div>
                    @if(in_array($comp->type, ['salary', 'mixed']))
                    <div class="info-row">
                        <span class="info-label">💵 {{ __('Base') }}</span>
                        <span class="info-val fw-bold">{{ number_format($comp->base_amount, 0) }} {{ $comp->currency }} / {{ __($comp->pay_period) }}</span>
                    </div>
                    @endif
                    @if(in_array($comp->type, ['commission', 'mixed']) && $comp->commission_type === 'flat')
                    <div class="info-row">
                        <span class="info-label">📊 {{ __('Commission') }}</span>
                        <span class="info-val">{{ $comp->commission_rate }}%</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Services --}}
            @if($services->isNotEmpty())
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">✂️ {{ __('Services') }} ({{ $services->count() }})</span>
                    </div>
                    <div class="px-3 py-3">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($services as $svc)
                            @php $svcName = $locale === 'ar' ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar); @endphp
                            <span style="font-size:11px;font-weight:600;padding:4px 10px;border-radius:8px;background:rgba(67,233,123,.08);color:#43e97b;border:1px solid rgba(67,233,123,.2);">
                                {{ $svcName }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Working Hours --}}
            @if($employee->workingHours->isNotEmpty())
            <div class="card border-0 shadow-sm info-card">
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">🕐 {{ __('Working Hours') }}</span>
                    </div>
                    @php $dayNames = \App\Models\EmployeeWorkingHour::$dayNames; @endphp
                    @foreach($employee->workingHours as $wh)
                    <div class="info-row">
                        <span class="info-label" style="width:70px;">{{ $locale === 'ar' ? $dayNames[$wh->day_of_week]['ar'] : $dayNames[$wh->day_of_week]['en'] }}</span>
                        <span class="info-val">
                            @if($wh->is_working)
                                <span style="color:#43e97b;font-weight:600;">{{ substr($wh->start_time, 0, 5) }} — {{ substr($wh->end_time, 0, 5) }}</span>
                            @else
                                <span style="opacity:.3;">{{ __('Day Off') }}</span>
                            @endif
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- RIGHT: Tabs content --}}
        <div class="col-lg-8">
            <div class="emp-tab-bar">
                <button type="button" class="emp-show-tab active" data-tab="appointments">
                    <i data-feather="calendar" style="width:13px;height:13px;"></i> {{ __('Appointments') }}
                </button>
                <button type="button" class="emp-show-tab" data-tab="leaves">
                    <i data-feather="coffee" style="width:13px;height:13px;"></i> {{ __('Leaves') }}
                </button>
                <button type="button" class="emp-show-tab" data-tab="deductions">
                    <i data-feather="minus-circle" style="width:13px;height:13px;"></i> {{ __('Deductions') }}
                </button>
            </div>

            {{-- Appointments tab --}}
            <div class="emp-show-pane active" id="pane-appointments">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="px-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Recent Appointments') }}</span>
                                <span style="font-size:12px;font-weight:700;background:rgba(102,126,234,.12);color:#667eea;padding:3px 10px;border-radius:20px;">{{ $appointments->total() }}</span>
                            </div>
                            <div class="tx-12 text-muted">
                                {{ __('Total Revenue') }}: <span class="fw-bold" style="color:#43e97b;">{{ number_format((float)$totalRevenue, 0) }}</span>
                            </div>
                        </div>
                        @forelse($appointments as $appt)
                        <div class="px-4 py-3 bk-table-row" style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <div class="row gx-3 align-items-center">
                                <div class="col-6 col-sm-3">
                                    <div class="fw-semibold tx-13">{{ $appt->start_time->translatedFormat('d M Y') }}</div>
                                    <div class="text-muted tx-11">{{ $appt->start_time->format('H:i') }}</div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="tx-13 fw-semibold">{{ $appt->service?->name ?? '—' }}</div>
                                    <div class="text-muted tx-11">{{ $appt->customer?->name ?? '' }}</div>
                                </div>
                                <div class="col-6 col-sm-2 tx-12 text-muted d-none d-sm-block">{{ $appt->branch?->localizedName() ?? '—' }}</div>
                                <div class="col-3 col-sm-2 text-end fw-bold tx-13">
                                    @if($appt->total_price) {{ number_format((float)$appt->total_price, 0) }} @else — @endif
                                </div>
                                <div class="col-3 col-sm-2 text-end">
                                    <span class="bk-badge bk-badge-{{ $appt->status }}">{{ __(ucfirst($appt->status)) }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="bk-empty py-5">
                            <div class="bk-empty-ic mb-3"><i data-feather="calendar" style="width:24px;height:24px;"></i></div>
                            <p>{{ __('No appointments yet.') }}</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                @if($appointments->hasPages())
                <div class="mt-3">{{ $appointments->links() }}</div>
                @endif
            </div>

            {{-- Leaves tab --}}
            <div class="emp-show-pane" id="pane-leaves">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="px-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                            <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Recent Leaves') }}</span>
                            <a href="{{ route('company.employee-leaves.create', $employee) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size:11px;">
                                <i data-feather="plus" style="width:11px;height:11px;"></i> {{ __('Add Leave') }}
                            </a>
                        </div>
                        @forelse($leaves as $leave)
                        <div class="px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="fw-semibold tx-13">{{ __('Day Off') }}</span>
                                    <span class="text-muted tx-12 {{ $locale === 'ar' ? 'me-2' : 'ms-2' }}">
                                        {{ $leave->start_date->format('d/m') }} — {{ $leave->end_date->format('d/m/Y') }}
                                    </span>
                                </div>
                                <span class="bk-badge bk-badge-{{ $leave->status ?? 'pending' }}">{{ __(ucfirst($leave->status ?? 'Pending')) }}</span>
                            </div>
                            @if($leave->reason)
                            <div class="text-muted tx-11 mt-1">{{ $leave->reason }}</div>
                            @endif
                        </div>
                        @empty
                        <div class="bk-empty py-4">
                            <p class="tx-12 text-muted mb-0">{{ __('No leaves recorded.') }}</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Deductions tab --}}
            <div class="emp-show-pane" id="pane-deductions">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="px-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                            <span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Recent Deductions') }}</span>
                            <a href="{{ route('company.employees.deductions.index', $employee) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:11px;">
                                <i data-feather="list" style="width:11px;height:11px;"></i> {{ __('View All') }}
                            </a>
                        </div>
                        @forelse($deductions as $ded)
                        <div class="px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="min-width:0;flex:1;">
                                    <span class="fw-semibold tx-13">{{ $ded->notes ?: __(ucfirst($ded->type ?? 'Deduction')) }}</span>
                                    <span class="text-muted tx-12 {{ $locale === 'ar' ? 'me-2' : 'ms-2' }}">{{ ($ded->deduction_date ?? $ded->created_at)->translatedFormat('d M Y') }}</span>
                                </div>
                                <span class="fw-bold tx-13 flex-shrink-0" style="color:#ef4444;">-{{ number_format($ded->amount, 2) }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="bk-empty py-4">
                            <p class="tx-12 text-muted mb-0">{{ __('No deductions recorded.') }}</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.emp-show-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.emp-show-tab').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.emp-show-pane').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var pane = document.getElementById('pane-' + this.dataset.tab);
        if (pane) pane.classList.add('active');
    });
});
</script>
@endpush
@endsection
