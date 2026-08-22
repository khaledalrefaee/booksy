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

/* ── QR Code card · Design B — GlowRez Brand Gradient (theme-aware) ── */
/* The card itself is the gradient ring; .bk-qr-inner is the real surface. */
.bk-qr-card {
    position: relative;
    border-radius: 23px;
    padding: 1.5px;
    background: linear-gradient(135deg,
        var(--bk-accent) 0%, var(--bk-gold) 45%, var(--bk-gold-strong) 68%, var(--bk-accent) 100%);
    box-shadow: var(--bk-shadow-lg), 0 0 0 1px color-mix(in srgb, var(--bk-gold) 16%, transparent);
}
.bk-qr-inner {
    position: relative;
    border-radius: 21.5px;
    overflow: hidden;
    padding: 22px 22px 20px;
    background:
        radial-gradient(135% 95% at 100% -12%, color-mix(in srgb, var(--bk-gold) 15%, transparent) 0%, transparent 55%),
        radial-gradient(120% 80% at -8% 112%, color-mix(in srgb, var(--bk-accent) 14%, transparent) 0%, transparent 52%),
        linear-gradient(165deg, color-mix(in srgb, var(--bk-accent) 6%, var(--bk-surface)) 0%, var(--bk-surface) 58%);
}
.bk-qr-head {
    display: flex; align-items: flex-start; gap: 13px;
}
.bk-qr-head-icon {
    flex: none; width: 44px; height: 44px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    color: var(--bk-accent-ink);
    background: linear-gradient(135deg,
        var(--bk-accent-fill), color-mix(in srgb, var(--bk-gold-strong) 60%, var(--bk-accent-fill)));
    box-shadow: 0 6px 16px -7px color-mix(in srgb, var(--bk-gold) 55%, transparent),
                inset 0 1px 0 rgba(255,255,255,.22);
}
.bk-qr-head-icon svg { width: 22px; height: 22px; }
.bk-qr-title {
    font-size: 15.5px; font-weight: 800; letter-spacing: -.01em;
    margin: 2px 0 4px; color: var(--bk-text);
}
.bk-qr-sub {
    font-size: 12px; line-height: 1.5; margin: 0;
    color: var(--bk-text-muted); max-width: 32ch;
}

/* White plaque + blurred brand-gradient halo → premium, and the QR stays
   high-contrast & scannable in BOTH themes */
.bk-qr-plaque-wrap {
    position: relative; margin-top: 20px;
    display: flex; justify-content: center; isolation: isolate;
}
.bk-qr-plaque-wrap::before {
    content: ""; position: absolute; z-index: 0;
    width: 66%; height: 66%; top: 17%; left: 17%;
    border-radius: 34px; filter: blur(32px); opacity: .42;
    background: linear-gradient(135deg, var(--bk-accent), var(--bk-gold));
}
.bk-qr-plaque {
    position: relative; z-index: 1;
    background: #fff; border-radius: 18px; padding: 16px;
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--bk-gold) 34%, transparent),
                0 16px 36px -16px color-mix(in srgb, var(--bk-accent) 62%, transparent);
}
.bk-qr-img {
    width: 100%; max-width: 210px; height: auto;
    display: block; border-radius: 8px;
}
.bk-qr-actions {
    display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px;
}
.bk-qr-btn {
    flex: 1 1 auto; min-width: 92px; min-height: 44px;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    font-size: 13px; font-weight: 700; line-height: 1;
    border-radius: 13px; cursor: pointer; color: var(--bk-text-soft);
    border: 1px solid transparent;
    background:
        linear-gradient(var(--bk-surface-2), var(--bk-surface-2)) padding-box,
        linear-gradient(135deg, color-mix(in srgb, var(--bk-accent) 55%, transparent),
                                color-mix(in srgb, var(--bk-gold) 62%, transparent)) border-box;
    transition: transform .14s ease, filter .18s ease, box-shadow .18s ease, color .15s;
}
.bk-qr-btn svg { width: 15px; height: 15px; }
.bk-qr-btn:hover { transform: translateY(-1px); color: var(--bk-text); filter: brightness(1.04); }
.bk-qr-btn:active { transform: translateY(0); }
.bk-qr-btn:focus-visible { outline: 2px solid var(--bk-accent); outline-offset: 2px; }
.bk-qr-btn-primary {
    color: var(--bk-accent-ink); border-color: transparent;
    background: linear-gradient(135deg,
        var(--bk-accent-fill) 0%, color-mix(in srgb, var(--bk-gold-strong) 58%, var(--bk-accent-fill)) 100%);
    box-shadow: 0 8px 18px -9px color-mix(in srgb, var(--bk-gold) 60%, transparent),
                inset 0 1px 0 rgba(255,255,255,.20);
}
.bk-qr-btn-primary:hover {
    color: var(--bk-accent-ink);
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--bk-gold) 70%, transparent),
                inset 0 1px 0 rgba(255,255,255,.20);
}
.bk-qr-btn.is-copied {
    color: var(--bk-success);
    background:
        linear-gradient(var(--bk-success-bg), var(--bk-success-bg)) padding-box,
        linear-gradient(135deg, var(--bk-success), var(--bk-success)) border-box;
}
.bk-qr-regen {
    text-align: center; margin-top: 12px;
}
.bk-qr-regen-btn {
    display: inline-flex; align-items: center; gap: 5px;
    background: none; border: none; cursor: pointer;
    font-size: 11.5px; font-weight: 600; color: var(--bk-text-muted);
    padding: 6px 11px; border-radius: 9px;
    transition: color .15s, background .15s;
}
.bk-qr-regen-btn svg { width: 12px; height: 12px; }
.bk-qr-regen-btn:hover { color: var(--bk-gold-strong); background: var(--bk-gold-soft); }

/* Empty state */
.bk-qr-empty {
    margin-top: 20px; padding: 30px 20px; text-align: center;
    border-radius: 16px; border: 1px dashed color-mix(in srgb, var(--bk-gold) 40%, var(--bk-border));
    background: color-mix(in srgb, var(--bk-accent) 5%, var(--bk-surface-2)); color: var(--bk-text-muted);
}
.bk-qr-empty svg { color: var(--bk-accent); opacity: .7; }
.bk-qr-empty p { margin: 10px 0 0; font-size: 12.5px; }

@media (prefers-reduced-motion: reduce) {
    .bk-qr-btn, .bk-qr-regen-btn { transition: none; }
}
@media (max-width: 400px) {
    .bk-qr-btn { min-width: 0; flex-basis: calc(50% - 4px); }
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
                        <span class="badge rounded-pill" style="background:rgba(75,93,52,.2);color:var(--bk-accent);font-size:10px;">{{ __('Head Office') }}</span>
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
                       class="btn btn-sm rounded-pill px-3" style="font-size:12px;font-weight:600;background:rgba(75,93,52,.12);color:var(--bk-accent);border:none;">
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
                            @php $scolor = $appt->status->color(); @endphp
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
                                    <span class="status-pill" style="background:{{ $scolor }}26;color:{{ $scolor }};">
                                        {{ $appt->status->label() }}
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
            @php $qrVer = ($branch->qr_code ? @filemtime(public_path('storage/'.$branch->qr_code)) : null) ?: (optional($branch->updated_at)->timestamp ?: '1'); @endphp
            <div class="bk-qr-card mb-4">
              <div class="bk-qr-inner">
                <div class="bk-qr-head">
                    <div class="bk-qr-head-icon">
                        <i data-feather="maximize"></i>
                    </div>
                    <div>
                        <h5 class="bk-qr-title">{{ __('QR Code') }}</h5>
                        <p class="bk-qr-sub">{{ __('Share your branch with customers.') }}</p>
                    </div>
                </div>

                @if($branch->qr_code)
                    <div class="bk-qr-plaque-wrap">
                        <div class="bk-qr-plaque">
                            <img id="bkQrImg"
                                 class="bk-qr-img"
                                 src="{{ asset('storage/'.$branch->qr_code) }}?v={{ $qrVer }}"
                                 alt="{{ __('QR Code') }} — {{ $branch->localizedName() }}">
                        </div>
                    </div>

                    <div class="bk-qr-actions">
                        <a href="{{ asset('storage/'.$branch->qr_code) }}?v={{ $qrVer }}"
                           download="glowrez-qr-{{ Str::slug($branch->localizedName()) }}.png"
                           class="bk-qr-btn bk-qr-btn-primary">
                            <i data-feather="download"></i>{{ __('Download') }}
                        </a>
                        <button type="button" class="bk-qr-btn" onclick="bkPrintQr()">
                            <i data-feather="printer"></i>{{ __('Print') }}
                        </button>
                        <button type="button" class="bk-qr-btn" id="bkCopyLinkBtn"
                                data-url="{{ route('front.branch', $branch) }}"
                                data-copied="{{ __('Copied!') }}">
                            <i data-feather="link"></i><span>{{ __('Copy Link') }}</span>
                        </button>
                    </div>

                    <div class="bk-qr-regen">
                        <form method="POST" action="{{ route('company.branches.regenerate-qr', $branch) }}">
                            @csrf
                            <button type="submit" class="bk-qr-regen-btn">
                                <i data-feather="refresh-cw"></i>{{ __('Regenerate') }}
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bk-qr-empty">
                        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <path d="M14 14h3v3m0 4h4m-4 0v-4m-3 4h-1m1-7h4"/>
                        </svg>
                        <p>{{ __('No QR code yet.') }}</p>
                    </div>
                    <div class="bk-qr-regen">
                        <form method="POST" action="{{ route('company.branches.regenerate-qr', $branch) }}">
                            @csrf
                            <button type="submit" class="bk-qr-regen-btn">
                                <i data-feather="refresh-cw"></i>{{ __('Generate QR code') }}
                            </button>
                        </form>
                    </div>
                @endif
              </div>
            </div>

           

           

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // ── Copy branch booking link ──
    var copyBtn = document.getElementById('bkCopyLinkBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var url   = copyBtn.dataset.url;
            var label = copyBtn.querySelector('span');
            var done  = function () {
                var prev = label.textContent;
                copyBtn.classList.add('is-copied');
                label.textContent = copyBtn.dataset.copied || 'Copied!';
                setTimeout(function () {
                    copyBtn.classList.remove('is-copied');
                    label.textContent = prev;
                }, 1800);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done).catch(fallback);
            } else {
                fallback();
            }
            function fallback() {
                var ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus(); ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
                done();
            }
        });
    }
})();

// ── Print the QR code on a clean sheet ──
function bkPrintQr() {
    var img = document.getElementById('bkQrImg');
    if (!img) return;
    var title = @json($branch->localizedName());
    var url   = @json(route('front.branch', $branch));
    var w = window.open('', '_blank', 'width=560,height=680');
    if (!w) return;
    w.document.write(
        '<!doctype html><html><head><meta charset="utf-8"><title>' + title + '</title>' +
        '<style>' +
        'html,body{height:100%;margin:0}' +
        'body{display:flex;flex-direction:column;align-items:center;justify-content:center;' +
        'font-family:Poppins,Segoe UI,Arial,sans-serif;color:#22251D;padding:32px;box-sizing:border-box}' +
        'img{width:320px;max-width:80vw;height:auto}' +
        'h1{font-size:20px;font-weight:800;margin:20px 0 4px;text-align:center}' +
        'p{font-size:12px;color:#7B7C6D;margin:0;word-break:break-all;text-align:center;max-width:340px}' +
        '</style></head><body>' +
        '<img src="' + img.src + '" alt="QR">' +
        '<h1>' + title + '</h1><p>' + url + '</p>' +
        '</body></html>'
    );
    w.document.close();
    var run = function () { w.focus(); w.print(); };
    if (w.document.readyState === 'complete') { setTimeout(run, 250); }
    else { w.onload = run; }
}
</script>
@endpush
