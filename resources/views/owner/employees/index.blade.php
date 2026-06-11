@extends('owner.dashboard')

@push('owner-styles')
<style>
.emp-hero {
    background: linear-gradient(135deg, #c9a227 0%, #a07d10 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #000;
    position: relative; overflow: hidden;
}
.emp-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.1); pointer-events: none;
}
[dir="rtl"] .emp-hero::before { right: auto; left: -50px; }

.emp-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 22px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    transition: background .18s, transform .18s;
}
.bk-theme-light .emp-row { border-bottom-color: rgba(0,0,0,.05); }
.emp-row:last-child { border-bottom: none; }
.emp-row:hover { background: rgba(201,162,39,.06); }
[dir="ltr"] .emp-row:hover { transform: translateX(3px); }
[dir="rtl"] .emp-row:hover { transform: translateX(-3px); }
.emp-row:hover .emp-actions { opacity: 1; }

.emp-avatar {
    width: 44px; height: 44px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 16px; color: #fff; flex-shrink: 0;
}
.emp-name  { font-weight: 600; font-size: 14px; }
.emp-meta  { font-size: 12px; color: rgba(255,255,255,.45); margin-top: 2px; }
.bk-theme-light .emp-meta { color: rgba(0,0,0,.45); }
.status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }

.emp-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; opacity: 0; transition: opacity .2s; }
@media (max-width: 768px) { .emp-actions { opacity: 1; } }

.btn-act {
    border: none; border-radius: 9px; font-size: 12px; font-weight: 600;
    padding: 5px 12px; cursor: pointer; transition: opacity .18s;
    display: inline-flex; align-items: center; gap: 4px; text-decoration: none;
}
.btn-act:hover { opacity: .85; }
.btn-act-leave { background: linear-gradient(135deg,#f093fb,#f5576c); color:#fff !important; }
.btn-act-edit  { background: linear-gradient(135deg,#c9a227,#f4a642); color:#000 !important; }
.btn-act-del   { background: transparent; color: rgba(255,255,255,.4) !important; border: 1.5px solid rgba(255,255,255,.15); }
.btn-act-del:hover { border-color: #f5576c; color: #f5576c !important; }
.bk-theme-light .btn-act-del { color: rgba(0,0,0,.4) !important; border-color: rgba(0,0,0,.15); }
.bk-theme-light .btn-act-del:hover { border-color: #dc3545; color: #dc3545 !important; }

.bk-empty-emp { display: flex; flex-direction: column; align-items: center; padding: 60px 20px; gap: 12px; text-align: center; }
.bk-empty-emp svg { opacity: .18; }
.bk-empty-emp p { font-size: 14px; color: rgba(255,255,255,.4); margin: 0; }
.bk-theme-light .bk-empty-emp p { color: rgba(0,0,0,.4); }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="emp-hero bk-a1">
        <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(0,0,0,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('owner.branches.index') }}" class="text-decoration-none" style="color:rgba(0,0,0,.6);font-size:13px;">{{ __('Branches') }}</a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(0,0,0,.5);font-size:13px;">{{ $branch->localizedName() }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Employees') }}</h3>
                <p class="mb-0" style="color:rgba(0,0,0,.6);font-size:13px;">
                    {{ $employees->total() }} {{ __('team member(s) in') }} {{ $branch->localizedName() }}
                    @if($branch->company)· <span style="font-weight:600;">{{ $branch->company->localizedName() }}</span>@endif
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('owner.employee-leaves.index') }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:rgba(0,0,0,.15); color:#000; border:1.5px solid rgba(0,0,0,.25); font-weight:600; font-size:13px;">
                    <i data-feather="calendar" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Leaves') }}</span>
                </a>
                <a href="{{ route('owner.branches.employees.create', $branch) }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="background:#fff; color:#a07d10; font-weight:700; font-size:13px;">
                    <i data-feather="plus" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Add Employee') }}</span>
                </a>
            </div>
        </div>
    </div>

    @include('owner.partials.flash')

    @include('owner.partials._search-sort-bar', [
        'sortField'       => $sortField,
        'sortDir'         => $sortDir,
        'extraFilterKeys' => ['is_active'],
        'sortOptions'     => [
            ['field' => 'name',       'label' => __('الاسم')],
            ['field' => 'created_at', 'label' => __('تاريخ الإضافة')],
        ],
        'extraFilters' => '
            <select name="is_active" class="bk-ssb-select" style="min-width:130px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الحالات') . '</option>
                <option value="1" ' . ($isActive === '1' ? 'selected' : '') . '>' . __('نشط')    . '</option>
                <option value="0" ' . ($isActive === '0' ? 'selected' : '') . '>' . __('غير نشط') . '</option>
            </select>
        ',
    ])

    <div class="card border-0 bk-a2" style="border-radius:18px !important; overflow:hidden;">
        <div class="card-body p-0">
            @forelse($employees as $emp)
            @php
                $palette = ['#c9a227','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1'];
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
                </div>
                <div class="emp-actions">
                    <a href="{{ route('owner.employee-leaves.create', $emp) }}" class="btn-act btn-act-leave">
                        <i data-feather="calendar" style="width:11px;height:11px;"></i>{{ __('Leave') }}
                    </a>
                    <a href="{{ route('owner.employees.edit', $emp) }}" class="btn-act btn-act-edit">
                        <i data-feather="edit-2" style="width:11px;height:11px;"></i>{{ __('Edit') }}
                    </a>
                    <form action="{{ route('owner.employees.destroy', $emp) }}" method="post"
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
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <p>{{ __('No employees for this branch.') }}</p>
                <a href="{{ route('owner.branches.employees.create', $branch) }}"
                   class="btn btn-primary rounded-pill px-4 mt-1">{{ __('Add Employee') }}</a>
            </div>
            @endforelse
        </div>
        @if($employees->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $employees->links() }}</div>
        @endif
    </div>
</div>
@endsection
