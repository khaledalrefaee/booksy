@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Employee Index ── */
.emp-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.emp-hero::before {
    content: '';
    position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.07);
    pointer-events: none;
}
[dir="rtl"] .emp-hero::before { right: auto; left: -50px; }

/* Employee list card uses the framework .card for theme awareness */
.emp-list-card { border-radius: 18px !important; overflow: hidden; }

.emp-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 22px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    transition: background .18s, transform .18s;
    cursor: default;
}
.bk-theme-light .emp-row { border-bottom-color: rgba(0,0,0,.05); }
.emp-row:last-child { border-bottom: none; }
.emp-row:hover { background: rgba(102,126,234,.08); }
[dir="ltr"] .emp-row:hover { transform: translateX(3px); }
[dir="rtl"] .emp-row:hover { transform: translateX(-3px); }
.emp-row:hover .emp-actions { opacity: 1; }

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

.emp-actions {
    display: flex; align-items: center; gap: 6px;
    flex-shrink: 0;
    opacity: 0; transition: opacity .2s;
}
/* Always show on mobile/touch */
@media (max-width: 768px) { .emp-actions { opacity: 1; } }

.btn-act {
    border: none; border-radius: 9px;
    font-size: 12px; font-weight: 600;
    padding: 5px 12px;
    cursor: pointer; transition: opacity .18s, transform .15s;
    display: inline-flex; align-items: center; gap: 4px;
    text-decoration: none;
}
.btn-act:hover { opacity: .85; transform: scale(.97); }
.btn-act-leave { background: linear-gradient(135deg,#f093fb,#f5576c); color:#fff !important; }
.btn-act-edit  { background: linear-gradient(135deg,#4facfe,#00f2fe); color:#fff !important; }
.btn-act-del   {
    background: transparent; color: rgba(255,255,255,.4) !important;
    border: 1.5px solid rgba(255,255,255,.15);
}
.btn-act-del:hover { border-color: #f5576c; color: #f5576c !important; }
.bk-theme-light .btn-act-del { color: rgba(0,0,0,.4) !important; border-color: rgba(0,0,0,.15); }
.bk-theme-light .btn-act-del:hover { border-color: #dc3545; color: #dc3545 !important; }

.bk-empty-emp {
    display: flex; flex-direction: column; align-items: center;
    padding: 60px 20px; gap: 12px; text-align: center;
}
.bk-empty-emp svg { opacity: .18; }
.bk-empty-emp p { font-size: 14px; color: rgba(255,255,255,.4); margin: 0; }
.bk-theme-light .bk-empty-emp p { color: rgba(0,0,0,.4); }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Hero --}}
    <div class="emp-hero bk-a1">
        <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.branches.index') }}" class="text-decoration-none" style="color:rgba(255,255,255,.6); font-size:13px;">{{ __('Branches') }}</a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(255,255,255,.4); font-size:13px;">{{ $branch->localizedName() }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Employees') }}</h3>
                <p class="mb-0" style="color:rgba(255,255,255,.65); font-size:13px;">
                    {{ $employees->count() }} {{ __('team member(s) in') }} {{ $branch->localizedName() }}
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('company.employee-leaves.index') }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.3); font-weight:600; font-size:13px; backdrop-filter:blur(4px);">
                    <i data-feather="calendar" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Leaves') }}</span>
                </a>
                <a href="{{ route('company.branches.employees.create', $branch) }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:#fff; color:#667eea; font-weight:700; font-size:13px;">
                    <i data-feather="plus" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Add Employee') }}</span>
                </a>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- List --}}
    <div class="card border-0 emp-list-card bk-a2">
        <div class="card-body p-0">
            @forelse($employees as $emp)
            @php
                $palette = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1','#fda085'];
                $bg = $palette[$emp->id % count($palette)];
                $initial = strtoupper(mb_substr($emp->name_en ?? $emp->name_ar ?? '?', 0, 1));
            @endphp
            <div class="emp-row">
                <div class="emp-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">{{ $initial }}</div>

                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="emp-name">
                            {{ app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}
                        </span>
                        @if($emp->role)
                            <span class="badge-role">
                                {{ app()->getLocale()==='ar' ? ($emp->role->label_ar ?: $emp->role->label_en) : ($emp->role->label_en ?: $emp->role->label_ar) }}
                            </span>
                        @endif
                        @if($emp->is_active)
                            <span class="d-flex align-items-center gap-1" style="font-size:11px; font-weight:600; color:#43e97b;">
                                <span class="status-dot" style="background:#43e97b;"></span>{{ __('Active') }}
                            </span>
                        @else
                            <span class="d-flex align-items-center gap-1" style="font-size:11px; color:#6c757d;">
                                <span class="status-dot" style="background:#6c757d;"></span>{{ __('Inactive') }}
                            </span>
                        @endif
                    </div>
                    <div class="emp-meta d-flex flex-wrap gap-3 mt-1">
                        @if($emp->email)
                        <span><i data-feather="mail" style="width:11px;height:11px;" class="{{ app()->getLocale()==='ar' ? 'ms-1' : 'me-1' }}"></i>{{ $emp->email }}</span>
                        @endif
                        @if($emp->phone)
                        <span><i data-feather="phone" style="width:11px;height:11px;" class="{{ app()->getLocale()==='ar' ? 'ms-1' : 'me-1' }}"></i>{{ $emp->phone }}</span>
                        @endif
                    </div>
                    {{-- Compensation --}}
                    @if($emp->compensation)
                    @php
                        $comp = $emp->compensation;
                        $revenue = (float)($emp->revenue_this_month ?? 0);
                        $commissionEarned = null;
                        if (in_array($comp->type, ['commission','mixed']) && $comp->commission_type === 'flat') {
                            $commissionEarned = $revenue * ($comp->commission_rate / 100);
                        }
                    @endphp
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @if(in_array($comp->type, ['salary','mixed']))
                            <span style="font-size:11px;background:rgba(43,207,126,.1);color:#2bcf7e;border-radius:6px;padding:2px 8px;font-weight:600;">
                                💰 {{ number_format($comp->base_amount,0) }} / {{ __($comp->pay_period) }}
                            </span>
                        @endif
                        @if($commissionEarned !== null)
                            <span style="font-size:11px;background:rgba(250,112,154,.1);color:#fa709a;border-radius:6px;padding:2px 8px;font-weight:600;">
                                📊 {{ number_format($commissionEarned,0) }} {{ __('commission this month') }}
                            </span>
                        @endif
                        @if(($emp->appointments_this_month ?? 0) > 0)
                            <span style="font-size:11px;background:rgba(102,126,234,.1);color:#a5b4fd;border-radius:6px;padding:2px 8px;font-weight:600;">
                                📅 {{ $emp->appointments_this_month }} {{ __('appts this month') }}
                            </span>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="emp-actions">
                    <a href="{{ route('company.employee-leaves.create', $emp) }}" class="btn-act btn-act-leave">
                        <i data-feather="calendar" style="width:11px;height:11px;"></i>{{ __('Leave') }}
                    </a>
                    <a href="{{ route('company.employees.deductions.index', $emp) }}" class="btn-act" style="background:rgba(245,87,108,.12);color:#f5576c;">
                        <i data-feather="minus-circle" style="width:11px;height:11px;"></i>{{ __('Deductions') }}
                    </a>
                    <a href="{{ route('company.employees.edit', $emp) }}" class="btn-act btn-act-edit">
                        <i data-feather="edit-2" style="width:11px;height:11px;"></i>{{ __('Edit') }}
                    </a>
                    <form action="{{ route('company.employees.destroy', $emp) }}" method="POST"
                          onsubmit="return confirm('{{ __('Delete this employee?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-act btn-act-del">
                            <i data-feather="trash-2" style="width:11px;height:11px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="bk-empty-emp">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <p>{{ __('No employees for this branch.') }}</p>
                <a href="{{ route('company.branches.employees.create', $branch) }}"
                   class="btn btn-primary rounded-pill px-4 mt-1">{{ __('Add Employee') }}</a>
            </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
