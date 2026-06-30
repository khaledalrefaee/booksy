@extends('company.dashboard')

@push('company-styles')
<style>
.emp-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px; padding: 24px 28px;
    margin-bottom: 20px; color: #fff;
    position: relative; overflow: hidden;
}
.emp-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
[dir="rtl"] .emp-hero::before { right: auto; left: -50px; }
.emp-list-card { border-radius: 18px !important; overflow: hidden; }
.emp-row {
    display: flex; align-items: center; gap: 14px;
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
    font-weight: 700; font-size: 16px; color: #fff; flex-shrink: 0;
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
    display: flex; align-items: center; gap: 6px; flex-shrink: 0;
    opacity: 0; transition: opacity .2s;
}
.emp-scroll-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.emp-scroll-wrap::-webkit-scrollbar { height: 4px; }
.emp-scroll-wrap::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }
.bk-theme-light .emp-scroll-wrap::-webkit-scrollbar-thumb { background: rgba(0,0,0,.12); }
.btn-act {
    border: none; border-radius: 9px;
    font-size: 12px; font-weight: 600; padding: 5px 12px;
    cursor: pointer; transition: opacity .18s, transform .15s;
    display: inline-flex; align-items: center; gap: 4px;
    text-decoration: none;
}
.btn-act:hover { opacity: .85; transform: scale(.97); }
.btn-act-leave { background: linear-gradient(135deg,#f093fb,#f5576c); color:#fff !important; }
.btn-act-edit  { background: linear-gradient(135deg,#4facfe,#00f2fe); color:#fff !important; }
.btn-act-del {
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
.emp-search-box {
    background: rgba(255,255,255,.08); border: 1.5px solid rgba(255,255,255,.12);
    border-radius: 12px; padding: 8px 14px 8px 36px;
    font-size: 13px; color: #fff; outline: none; width: 100%;
    transition: border-color .2s, background .2s;
}
.emp-search-box::placeholder { color: rgba(255,255,255,.35); }
.emp-search-box:focus { border-color: rgba(102,126,234,.5); background: rgba(102,126,234,.08); }
.bk-theme-light .emp-search-box { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .emp-search-box::placeholder { color: rgba(0,0,0,.3); }
.bk-theme-light .emp-search-box:focus { border-color: #667eea; background: #fff; }

.emp-filter-btn {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 600; padding: 5px 14px;
    border-radius: 20px; border: 1.5px solid rgba(255,255,255,.1);
    background: transparent; color: rgba(255,255,255,.5);
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.bk-theme-light .emp-filter-btn { border-color: rgba(0,0,0,.1); color: rgba(0,0,0,.5); }
.emp-filter-btn:hover { border-color: rgba(102,126,234,.3); color: rgba(255,255,255,.8); }
.bk-theme-light .emp-filter-btn:hover { color: rgba(0,0,0,.8); }
.emp-filter-btn.active {
    background: rgba(102,126,234,.15); border-color: rgba(102,126,234,.4); color: #a5b4fd;
}
.bk-theme-light .emp-filter-btn.active { background: rgba(102,126,234,.1); color: #667eea; }
.emp-filter-count {
    font-size: 10px; font-weight: 800; opacity: .5;
    background: rgba(255,255,255,.08); padding: 1px 6px; border-radius: 10px;
}

@media (max-width: 768px) {
    .emp-hero { padding: 18px 16px; border-radius: 16px; }
    .emp-actions { opacity: 1; }
    .emp-row { min-width: 580px; }
    .emp-filter-btn { font-size: 11px; padding: 4px 10px; }
}
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
                            <a href="{{ route('company.branches.index') }}" class="text-decoration-none" style="color:rgba(255,255,255,.6);font-size:13px;">{{ __('Branches') }}</a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(255,255,255,.4);font-size:13px;">{{ $branch->localizedName() }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Employees') }}</h3>
                @php
                    $activeCount = $employees->where('is_active', true)->count();
                    $inactiveCount = $employees->where('is_active', false)->count();
                    $roles = $employees->pluck('role')->filter()->unique('id');
                @endphp
                <p class="mb-0" style="color:rgba(255,255,255,.65);font-size:13px;">
                    <span style="color:#43e97b;font-weight:700;">{{ $activeCount }}</span> {{ __('active') }}
                    @if($inactiveCount > 0)
                        · <span style="color:#6c757d;font-weight:700;">{{ $inactiveCount }}</span> {{ __('inactive') }}
                    @endif
                    · {{ $employees->count() }} {{ __('total in') }} {{ $branch->localizedName() }}
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('company.employee-leaves.index') }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);font-weight:600;font-size:13px;backdrop-filter:blur(4px);">
                    <i data-feather="calendar" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Leaves') }}</span>
                </a>
                <a href="{{ route('company.branches.employees.create', $branch) }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:#fff;color:#667eea;font-weight:700;font-size:13px;">
                    <i data-feather="plus" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Add Employee') }}</span>
                </a>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- License & Contract alerts --}}
    @php
        $expiredLicenses = $alerts->filter(fn($e) => $e->license_expiry && $e->license_expiry->isPast());
        $expiringLicenses = $alerts->filter(fn($e) => $e->license_expiry && !$e->license_expiry->isPast() && $e->license_expiry->diffInDays(now()) <= 30);
        $expiredContracts = $alerts->filter(fn($e) => $e->contract_end_date && $e->contract_end_date->isPast());
        $expiringContracts = $alerts->filter(fn($e) => $e->contract_end_date && !$e->contract_end_date->isPast() && $e->contract_end_date->diffInDays(now()) <= 30);
    @endphp
    @if($expiredLicenses->isNotEmpty() || $expiringLicenses->isNotEmpty() || $expiredContracts->isNotEmpty() || $expiringContracts->isNotEmpty())
    <div class="mb-3 d-flex flex-column gap-2">
        @foreach($expiredLicenses as $emp)
        <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-4" style="background:rgba(239,68,68,.1);border:1.5px solid rgba(239,68,68,.25);">
            <span style="font-size:20px;">🚨</span>
            <div style="flex:1;min-width:0;">
                <div class="fw-bold tx-13" style="color:#ef4444;">{{ __('Expired License') }}</div>
                <div class="tx-12 text-muted">
                    {{ app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}
                    — {{ __('expired on') }} {{ $emp->license_expiry->translatedFormat('d M Y') }}
                </div>
            </div>
            <a href="{{ route('company.employees.edit', $emp) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:11px;flex-shrink:0;">{{ __('Update') }}</a>
        </div>
        @endforeach
        @foreach($expiringLicenses as $emp)
        <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-4" style="background:rgba(245,158,11,.08);border:1.5px solid rgba(245,158,11,.25);">
            <span style="font-size:20px;">⚠️</span>
            <div style="flex:1;min-width:0;">
                <div class="fw-bold tx-13" style="color:#f59e0b;">{{ __('License Expiring Soon') }}</div>
                <div class="tx-12 text-muted">
                    {{ app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}
                    — {{ __('expires on') }} {{ $emp->license_expiry->translatedFormat('d M Y') }}
                    ({{ (int) $emp->license_expiry->diffInDays(now()) }} {{ __('days left') }})
                </div>
            </div>
            <a href="{{ route('company.employees.edit', $emp) }}" class="btn btn-sm btn-outline-warning rounded-pill px-3" style="font-size:11px;flex-shrink:0;">{{ __('Renew') }}</a>
        </div>
        @endforeach
        @foreach($expiredContracts as $emp)
        <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-4" style="background:rgba(239,68,68,.1);border:1.5px solid rgba(239,68,68,.25);">
            <span style="font-size:20px;">📋</span>
            <div style="flex:1;min-width:0;">
                <div class="fw-bold tx-13" style="color:#ef4444;">{{ __('Expired Contract') }}</div>
                <div class="tx-12 text-muted">
                    {{ app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}
                    — {{ __('expired on') }} {{ $emp->contract_end_date->translatedFormat('d M Y') }}
                </div>
            </div>
            <a href="{{ route('company.employees.edit', $emp) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-size:11px;flex-shrink:0;">{{ __('Update') }}</a>
        </div>
        @endforeach
        @foreach($expiringContracts as $emp)
        <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-4" style="background:rgba(245,158,11,.08);border:1.5px solid rgba(245,158,11,.25);">
            <span style="font-size:20px;">📋</span>
            <div style="flex:1;min-width:0;">
                <div class="fw-bold tx-13" style="color:#f59e0b;">{{ __('Contract Expiring Soon') }}</div>
                <div class="tx-12 text-muted">
                    {{ app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar) }}
                    — {{ __('expires on') }} {{ $emp->contract_end_date->translatedFormat('d M Y') }}
                    ({{ (int) $emp->contract_end_date->diffInDays(now()) }} {{ __('days left') }})
                </div>
            </div>
            <a href="{{ route('company.employees.edit', $emp) }}" class="btn btn-sm btn-outline-warning rounded-pill px-3" style="font-size:11px;flex-shrink:0;">{{ __('Renew') }}</a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Search & Filters --}}
    <div class="card border-0 rounded-4 mb-3 shadow-sm" style="overflow:hidden;">
        <div class="d-flex align-items-center gap-3 px-4 py-2">
            <i data-feather="search" style="width:16px;height:16px;opacity:.3;flex-shrink:0;"></i>
            <input type="text" id="emp-search" class="flex-grow-1" placeholder="{{ __('Search for employee...') }}"
                   style="background:transparent;border:none;outline:none;font-size:14px;font-weight:500;color:inherit;padding:8px 0;"
                   oninput="applyFilters()">
            <span id="emp-search-count" style="font-size:11px;font-weight:700;color:rgba(255,255,255,.3);flex-shrink:0;display:none;"></span>
            <button type="button" id="emp-search-clear" style="display:none;background:none;border:none;cursor:pointer;opacity:.4;padding:4px;" onclick="clearAll()">
                <i data-feather="x" style="width:14px;height:14px;"></i>
            </button>
        </div>
        <div class="d-flex flex-wrap gap-2 px-4 pb-3" id="emp-filters">
            <button type="button" class="emp-filter-btn active" data-filter="all" onclick="setFilter('all')">
                {{ __('All') }} <span class="emp-filter-count">{{ $employees->count() }}</span>
            </button>
            <button type="button" class="emp-filter-btn" data-filter="active" onclick="setFilter('active')">
                <span class="status-dot" style="background:#43e97b;"></span> {{ __('Active') }} <span class="emp-filter-count">{{ $activeCount }}</span>
            </button>
            @if($inactiveCount > 0)
            <button type="button" class="emp-filter-btn" data-filter="inactive" onclick="setFilter('inactive')">
                <span class="status-dot" style="background:#6c757d;"></span> {{ __('Inactive') }} <span class="emp-filter-count">{{ $inactiveCount }}</span>
            </button>
            @endif
            @foreach($roles as $role)
            <button type="button" class="emp-filter-btn" data-filter="role-{{ $role->id }}" onclick="setFilter('role-{{ $role->id }}')">
                {{ app()->getLocale()==='ar' ? ($role->label_ar ?: $role->label_en) : ($role->label_en ?: $role->label_ar) }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- List --}}
    <div class="card border-0 emp-list-card bk-a2">
        <div class="card-body p-0 emp-scroll-wrap">
            @forelse($employees as $emp)
            @php
                $palette = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1','#fda085'];
                $bg = $palette[$emp->id % count($palette)];
                $initial = strtoupper(mb_substr($emp->name_en ?? $emp->name_ar ?? '?', 0, 1));
            @endphp
            <div class="emp-row js-emp-row"
                 data-search="{{ mb_strtolower(($emp->name_ar ?? '') . ' ' . ($emp->name_en ?? '') . ' ' . ($emp->email ?? '') . ' ' . ($emp->phone ?? '') . ' ' . ($emp->role ? ($emp->role->label_ar ?? '') . ' ' . ($emp->role->label_en ?? '') : '')) }}"
                 data-status="{{ $emp->is_active ? 'active' : 'inactive' }}"
                 data-role="{{ $emp->role_id }}">
                @if($emp->image)
                    <img src="{{ asset('storage/'.$emp->image) }}" class="emp-avatar" style="object-fit:cover;" alt="">
                @else
                    <div class="emp-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">{{ $initial }}</div>
                @endif

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
                            <span class="d-flex align-items-center gap-1" style="font-size:11px;font-weight:600;color:#43e97b;">
                                <span class="status-dot" style="background:#43e97b;"></span>{{ __('Active') }}
                            </span>
                        @else
                            <span class="d-flex align-items-center gap-1" style="font-size:11px;color:#6c757d;">
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
                    <a href="{{ route('company.employees.show', $emp) }}" class="btn-act" style="background:rgba(102,126,234,.18);color:#a5b4fd;">
                        <i data-feather="eye" style="width:11px;height:11px;"></i>{{ __('Show') }}
                    </a>
                    <a href="{{ route('company.employee-leaves.create', $emp) }}" class="btn-act btn-act-leave">
                        <i data-feather="calendar" style="width:11px;height:11px;"></i>{{ __('Leave') }}
                    </a>
                    <a href="{{ route('company.employees.deductions.index', $emp) }}" class="btn-act" style="background:rgba(245,87,108,.12);color:#f5576c;">
                        <i data-feather="minus-circle" style="width:11px;height:11px;"></i>{{ __('Deductions') }}
                    </a>
                    <a href="{{ route('company.employees.edit', $emp) }}" class="btn-act btn-act-edit">
                        <i data-feather="edit-2" style="width:11px;height:11px;"></i>{{ __('Edit') }}
                    </a>
                    <button type="button" class="btn-act btn-act-del" onclick="openDeleteModal({{ $emp->id }}, '{{ addslashes(app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar)) }}')">
                        <i data-feather="trash-2" style="width:11px;height:11px;"></i>
                    </button>
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

    {{-- No results --}}
    <div id="emp-no-results" class="d-none">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="bk-empty-emp">
                <i data-feather="search" style="width:40px;height:40px;opacity:.15;"></i>
                <p>{{ __('No employees match your search.') }}</p>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="modal fade" id="deleteEmpModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4">
                <div class="modal-body text-center p-4">
                    <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                    <h6 class="fw-bold mb-2">{{ __('Delete employee?') }}</h6>
                    <p class="text-muted small mb-1" id="deleteEmpName"></p>
                    <p class="text-muted small mb-3">{{ __('This will permanently delete this employee and all their data.') }}</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <form method="POST" id="deleteEmpForm">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
var DELETE_BASE = '{{ route("company.employees.destroy", "__ID__") }}';

var currentFilter = 'all';

function openDeleteModal(id, name) {
    document.getElementById('deleteEmpForm').action = DELETE_BASE.replace('__ID__', id);
    document.getElementById('deleteEmpName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteEmpModal')).show();
}

function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.emp-filter-btn').forEach(function(b) {
        b.classList.toggle('active', b.dataset.filter === filter);
    });
    applyFilters();
}

function applyFilters() {
    var q = (document.getElementById('emp-search').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('.js-emp-row');
    var total = rows.length, found = 0;

    rows.forEach(function(row) {
        var matchSearch = !q || (row.dataset.search || '').includes(q);
        var matchFilter = true;
        if (currentFilter === 'active') matchFilter = row.dataset.status === 'active';
        else if (currentFilter === 'inactive') matchFilter = row.dataset.status === 'inactive';
        else if (currentFilter.startsWith('role-')) matchFilter = row.dataset.role === currentFilter.replace('role-', '');

        var show = matchSearch && matchFilter;
        row.style.display = show ? '' : 'none';
        if (show) found++;
    });

    var countEl = document.getElementById('emp-search-count');
    var clearBtn = document.getElementById('emp-search-clear');
    var card = document.querySelector('.emp-list-card');
    var noResults = document.getElementById('emp-no-results');
    var hasFilter = q || currentFilter !== 'all';

    if (hasFilter) {
        countEl.style.display = '';
        countEl.textContent = found + ' / ' + total;
        clearBtn.style.display = '';
    } else {
        countEl.style.display = 'none';
        clearBtn.style.display = 'none';
    }

    if (found === 0 && hasFilter) {
        card.style.display = 'none';
        noResults.classList.remove('d-none');
    } else {
        card.style.display = '';
        noResults.classList.add('d-none');
    }
}

function clearAll() {
    document.getElementById('emp-search').value = '';
    setFilter('all');
    document.getElementById('emp-search').focus();
}
</script>
@endpush
