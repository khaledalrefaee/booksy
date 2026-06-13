@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Branch Show ── */
.branch-hero {
    background: linear-gradient(135deg, #1a1f3a 0%, #0d1b2a 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(102,126,234,.2);
}
.branch-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(102,126,234,.08);
    pointer-events: none;
}
[dir="rtl"] .branch-hero::before { right: auto; left: -60px; }
.bk-theme-light .branch-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.stat-card {
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid rgba(255,255,255,.07);
}
.bk-theme-light .stat-card { border-color: rgba(0,0,0,.07); }
.stat-icon {
    width: 46px; height: 46px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.stat-value { font-size: 22px; font-weight: 700; line-height: 1.1; }
.stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; opacity: .5; margin-top: 2px; }

/* Employee list */
.emp-list-card { border-radius: 18px !important; overflow: hidden; }
.emp-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 22px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    transition: background .18s, transform .18s;
}
.bk-theme-light .emp-row { border-bottom-color: rgba(0,0,0,.05); }
.emp-row:last-child { border-bottom: none; }
.emp-row:hover { background: rgba(102,126,234,.08); }
[dir="ltr"] .emp-row:hover { transform: translateX(3px); }
[dir="rtl"] .emp-row:hover { transform: translateX(-3px); }

.emp-avatar {
    width: 44px; height: 44px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 16px; color: #fff;
    flex-shrink: 0;
}
.emp-name { font-weight: 600; font-size: 14px; }
.emp-meta { font-size: 12px; color: rgba(255,255,255,.45); margin-top: 2px; }
.bk-theme-light .emp-meta { color: rgba(0,0,0,.45); }

.badge-role {
    font-size: 11px; font-weight: 600;
    padding: 2px 9px; border-radius: 7px;
    background: rgba(102,126,234,.18); color: #a5b4fd;
}
.bk-theme-light .badge-role { background: rgba(102,126,234,.12); color: #4f46e5; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }

/* Appointments table */
.appt-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.appt-table th {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
    padding: 10px 16px; opacity: .5; white-space: nowrap;
}
.appt-table td { padding: 11px 16px; font-size: 13px; vertical-align: middle; }
.appt-table tbody tr { border-bottom: 1px solid rgba(255,255,255,.05); transition: background .15s; }
.bk-theme-light .appt-table tbody tr { border-bottom-color: rgba(0,0,0,.05); }
.appt-table tbody tr:last-child { border-bottom: none; }
.appt-table tbody tr:hover { background: rgba(102,126,234,.06); }

.status-pill {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    padding: 3px 9px; border-radius: 20px;
}
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Hero --}}
    <div class="branch-hero bk-a1">
        <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.branches.index') }}" class="text-decoration-none" style="color:rgba(255,255,255,.6);font-size:13px;">{{ __('Branches') }}</a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(255,255,255,.5);font-size:13px;">{{ $branch->localizedName() }}</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">{{ $branch->localizedName() }}</h3>
                    @if($branch->is_head_office)
                        <span class="badge rounded-pill" style="background:rgba(201,162,39,.2);color:#C9A227;font-size:10px;">{{ __('Head Office') }}</span>
                    @endif
                    <span class="badge rounded-pill bg-{{ $branch->statusColor() }}" style="font-size:10px;">{{ __($branch->statusLabel()) }}</span>
                </div>
                @if($branch->address)
                    <p class="mb-0" style="color:rgba(255,255,255,.55);font-size:13px;">
                        <i data-feather="map-pin" style="width:12px;height:12px;" class="me-1"></i>{{ $branch->address }}
                    </p>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('company.branches.employees.create', $branch) }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);font-weight:600;font-size:13px;backdrop-filter:blur(4px);">
                    <i data-feather="user-plus" style="width:13px;height:13px;"></i>
                    <span class="ms-1">{{ __('Add Employee') }}</span>
                </a>
                <a href="{{ route('company.branches.edit', $branch) }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:#fff;color:#667eea;font-weight:700;font-size:13px;">
                    <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                    <span class="ms-1">{{ __('Edit Branch') }}</span>
                </a>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="row g-3 mt-2 position-relative" style="z-index:1;">
            <div class="col-6 col-md-3">
                <div class="stat-card bk-a2">
                    <div class="stat-icon" style="background:rgba(102,126,234,.15);">
                        <i data-feather="users" style="width:20px;height:20px;color:#a5b4fd;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['employees'] }}</div>
                        <div class="stat-label">{{ __('Employees') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bk-a2">
                    <div class="stat-icon" style="background:rgba(43,207,126,.12);">
                        <i data-feather="user-check" style="width:20px;height:20px;color:#2bcf7e;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['active_employees'] }}</div>
                        <div class="stat-label">{{ __('Active') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bk-a2">
                    <div class="stat-icon" style="background:rgba(79,172,254,.12);">
                        <i data-feather="calendar" style="width:20px;height:20px;color:#4facfe;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['appointments_month'] }}</div>
                        <div class="stat-label">{{ __('Appts this month') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bk-a2">
                    <div class="stat-icon" style="background:rgba(250,112,154,.12);">
                        <i data-feather="trending-up" style="width:20px;height:20px;color:#fa709a;"></i>
                    </div>
                    <div>
                        <div class="stat-value" style="font-size:16px;">{{ number_format($stats['revenue_month'], 0) }}</div>
                        <div class="stat-label">{{ __('Revenue this month') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row g-4">

        {{-- Employees --}}
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="font-size:15px;">{{ __('Team') }}</h5>
                <a href="{{ route('company.branches.employees.index', $branch) }}" class="btn btn-sm rounded-pill px-3"
                   style="font-size:12px;font-weight:600;background:rgba(102,126,234,.12);color:#a5b4fd;border:none;">
                    {{ __('Manage all') }} →
                </a>
            </div>
            <div class="card border-0 emp-list-card bk-a2">
                <div class="card-body p-0">
                    @forelse($employees as $emp)
                    @php
                        $palette = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1','#fda085'];
                        $bg = $palette[$emp->id % count($palette)];
                        $initial = strtoupper(mb_substr($emp->name_en ?? $emp->name_ar ?? '?', 0, 1));

                        $comp = $emp->compensation;
                        $earned = null;
                        $salaryLabel = null;
                        if ($comp) {
                            if (in_array($comp->type, ['commission', 'mixed']) && $comp->commission_type === 'flat') {
                                $revenue = (float)($emp->revenue_this_month ?? 0);
                                $earned = $revenue * ((float)$comp->commission_rate / 100);
                            }
                            if (in_array($comp->type, ['salary', 'mixed'])) {
                                $salaryLabel = number_format((float)$comp->base_amount, 0) . ' / ' . __($comp->pay_period);
                            }
                        }
                        $currency = $comp->currency ?? 'SYP';
                    @endphp
                    <div class="emp-row">
                        <div class="emp-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">{{ $initial }}</div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="emp-name">
                                    {{ app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}
                                </span>
                                @if($emp->role)
                                    <span class="badge-role">{{ app()->getLocale()==='ar' ? ($emp->role->label_ar ?: $emp->role->label_en) : ($emp->role->label_en ?: $emp->role->label_ar) }}</span>
                                @endif
                                @if($emp->is_active)
                                    <span class="d-flex align-items-center gap-1" style="font-size:11px;font-weight:600;color:#43e97b;">
                                        <span class="status-dot" style="background:#43e97b;"></span>{{ __('Active') }}
                                    </span>
                                @else
                                    <span class="d-flex align-items-center gap-1" style="font-size:11px;color:#6c757d;">
                                        <span class="status-dot" style="background:#6c757d;"></span>{{ __('Inactive') }}
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @if($salaryLabel)
                                    <span style="font-size:11px;background:rgba(43,207,126,.1);color:#2bcf7e;border-radius:6px;padding:2px 8px;font-weight:600;">
                                        💰 {{ $salaryLabel }}
                                    </span>
                                @endif
                                @if($earned !== null)
                                    <span style="font-size:11px;background:rgba(250,112,154,.1);color:#fa709a;border-radius:6px;padding:2px 8px;font-weight:600;">
                                        📊 {{ number_format($earned, 0) }} {{ $currency }} {{ __('commission') }}
                                    </span>
                                @endif
                                @if(($emp->appointments_this_month ?? 0) > 0)
                                    <span style="font-size:11px;background:rgba(102,126,234,.1);color:#a5b4fd;border-radius:6px;padding:2px 8px;font-weight:600;">
                                        📅 {{ $emp->appointments_this_month }} {{ __('appts this month') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('company.employees.edit', $emp) }}"
                           style="flex-shrink:0;padding:6px 12px;border-radius:9px;font-size:12px;font-weight:600;background:rgba(79,172,254,.12);color:#4facfe;text-decoration:none;">
                            <i data-feather="edit-2" style="width:11px;height:11px;"></i>
                        </a>
                    </div>
                    @empty
                    <div class="text-center py-5" style="color:rgba(255,255,255,.3);">
                        <i data-feather="users" style="width:40px;height:40px;opacity:.2;"></i>
                        <p class="mt-2 mb-0" style="font-size:14px;">{{ __('No employees yet.') }}</p>
                        <a href="{{ route('company.branches.employees.create', $branch) }}" class="btn btn-sm btn-primary rounded-pill mt-2">{{ __('Add Employee') }}</a>
                    </div>
                    @endforelse
                </div>
            </div>
             {{-- Quick links --}}
            <div class="mb-3 my-4">
                <h5 class="fw-bold mb-3" style="font-size:15px;">{{ __('Quick Links') }}</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('company.branches.services.index', $branch) }}"
                       class="btn btn-sm rounded-pill px-3" style="font-size:12px;font-weight:600;background:rgba(102,126,234,.12);color:#a5b4fd;border:none;">
                        <i data-feather="scissors" style="width:12px;height:12px;" class="me-1"></i>{{ __('Services') }}
                    </a>
                    <a href="{{ route('company.branches.working-hours.edit', $branch) }}"
                       class="btn btn-sm rounded-pill px-3" style="font-size:12px;font-weight:600;background:rgba(79,172,254,.12);color:#4facfe;border:none;">
                        <i data-feather="clock" style="width:12px;height:12px;" class="me-1"></i>{{ __('Working Hours') }}
                    </a>
                    <a href="{{ route('company.branches.gallery', $branch) }}"
                       class="btn btn-sm rounded-pill px-3" style="font-size:12px;font-weight:600;background:rgba(201,162,39,.12);color:#C9A227;border:none;">
                        <i data-feather="image" style="width:12px;height:12px;" class="me-1"></i>{{ __('Gallery') }}
                    </a>
                    <a href="{{ route('company.appointments.index', ['branch_id' => $branch->id]) }}"
                       class="btn btn-sm rounded-pill px-3" style="font-size:12px;font-weight:600;background:rgba(43,207,126,.12);color:#2bcf7e;border:none;">
                        <i data-feather="calendar" style="width:12px;height:12px;" class="me-1"></i>{{ __('Appointments') }}
                    </a>
                    <a href="{{ route('company.branches.edit', $branch) }}"
                       class="btn btn-sm rounded-pill px-3" style="font-size:12px;font-weight:600;background:rgba(250,112,154,.12);color:#fa709a;border:none;">
                        <i data-feather="settings" style="width:12px;height:12px;" class="me-1"></i>{{ __('Settings') }}
                    </a>
                </div>
            </div>


             {{-- Recent Appointments --}}
            <h5 class="fw-bold mb-3" style="font-size:15px;">{{ __('Recent Appointments') }}</h5>
            <div class="card border-0 bk-a2" style="border-radius:18px;overflow:hidden;">
                <div class="card-body p-0">
                    @if($recentAppointments->isEmpty())
                        <div class="text-center py-5" style="color:rgba(255,255,255,.3);">
                            <i data-feather="calendar" style="width:36px;height:36px;opacity:.2;"></i>
                            <p class="mt-2 mb-0" style="font-size:13px;">{{ __('No appointments yet.') }}</p>
                        </div>
                    @else
                    <table class="appt-table">
                        <thead>
                            <tr>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Service') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAppointments as $appt)
                            @php
                                $statusColors = [
                                    'pending'   => ['rgba(255,193,7,.15)','#ffc107'],
                                    'confirmed' => ['rgba(79,172,254,.15)','#4facfe'],
                                    'completed' => ['rgba(43,207,126,.15)','#2bcf7e'],
                                    'cancelled' => ['rgba(245,87,108,.15)','#f5576c'],
                                    'no_show'   => ['rgba(108,117,125,.15)','#6c757d'],
                                ];
                                [$sbg, $scolor] = $statusColors[$appt->status] ?? ['rgba(108,117,125,.15)','#6c757d'];
                            @endphp
                            <tr>
                                <td style="font-weight:600;font-size:12px;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $appt->customer?->name ?? __('Guest') }}
                                </td>
                                <td style="font-size:12px;opacity:.7;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $appt->service?->localizedName() ?? '—' }}
                                </td>
                                <td style="font-size:11px;opacity:.55;white-space:nowrap;">
                                    {{ $appt->start_time?->format('M d, H:i') }}
                                </td>
                                <td>
                                    <span class="status-pill" style="background:{{ $sbg }};color:{{ $scolor }};">
                                        {{ __($appt->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="col-lg-5">

            {{-- QR Code card --}}
            <div class="card border-0 mb-4" style="border-radius:18px;overflow:hidden;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0" style="font-size:15px;">
                            <i data-feather="maximize" style="width:15px;height:15px;margin-inline-end:6px;opacity:.6;"></i>
                            {{ __('QR Code') }}
                        </h5>
                        <div class="d-flex gap-2">
                            @if($branch->qr_code)
                            <a href="{{ asset('storage/'.$branch->qr_code) }}" download="qr-{{ Str::slug($branch->localizedName()) }}.png"
                               class="btn btn-sm rounded-pill px-3"
                               style="font-size:11px;font-weight:600;background:rgba(43,207,126,.12);color:#2bcf7e;border:none;">
                                <i data-feather="download" style="width:11px;height:11px;margin-inline-end:4px;"></i>{{ __('Download') }}
                            </a>
                            @endif
                            <form method="POST" action="{{ route('company.branches.regenerate-qr', $branch) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm rounded-pill px-3"
                                        style="font-size:11px;font-weight:600;background:rgba(102,126,234,.12);color:#a5b4fd;border:none;">
                                    <i data-feather="refresh-cw" style="width:11px;height:11px;margin-inline-end:4px;"></i>{{ __('Regenerate') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    @if($branch->qr_code)
                        <div class="text-center">
                            <img src="{{ asset('storage/'.$branch->qr_code) }}"
                                 alt="QR"
                                 style="width:200px;height:auto;border-radius:12px;border:4px solid rgba(255,255,255,.08);">
                            <p class="mt-2 mb-0" style="font-size:11px;opacity:.4;">
                                {{ __('Scan to open branch booking page') }}
                            </p>
                        </div>
                    @else
                        <div class="text-center py-3" style="opacity:.35;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/>
                                <path d="M14 14h3v3m0 4h4m-4 0v-4m-3 4h-1m1-7h4"/>
                            </svg>
                            <p class="mt-2 mb-0" style="font-size:12px;">{{ __('No QR code yet.') }}</p>
                            <p style="font-size:11px;opacity:.6;">{{ __('Click Regenerate to create one.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

           

           

        </div>
    </div>

</div>
@endsection
