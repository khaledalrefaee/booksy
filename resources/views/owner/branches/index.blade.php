@extends('owner.dashboard')
@section('content')
<div class="page-content">

    {{-- ── Header ── --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Branches') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Branches') }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- View toggle --}}
            <div class="bk-view-toggle" id="bk-view-toggle">
                <button class="bk-vt-btn" data-view="table" title="{{ __('Table view') }}">
                    <i data-feather="list" style="width:15px;height:15px;"></i>
                </button>
                <button class="bk-vt-btn" data-view="card" title="{{ __('Card view') }}">
                    <i data-feather="grid" style="width:15px;height:15px;"></i>
                </button>
            </div>
            <a href="{{ route('owner.branches.create') }}" class="btn btn-primary btn-icon-text rounded-pill shadow-sm">
                <i class="btn-icon-prepend" data-feather="plus"></i>
                {{ __('Add branch') }}
            </a>
        </div>
    </div>

    @include('owner.partials.flash')

    @include('owner.partials._search-sort-bar', [
        'dtTableId'       => 'bk-table',
        'sortField'       => $sortField,
        'sortDir'         => $sortDir,
        'extraFilterKeys' => ['company_id'],
        'sortOptions'     => [
            ['field' => 'created_at',  'label' => __('تاريخ الإضافة')],
            ['field' => 'name',        'label' => __('الاسم')],
            ['field' => 'sort_order',  'label' => __('الترتيب')],
        ],
        'extraFilters' => '
            <select name="company_id" class="bk-ssb-select" style="min-width:160px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الشركات') . '</option>
                ' . $companies->map(fn($c) => '<option value="' . $c->id . '" ' . ((string)$filterCompanyId === (string)$c->id ? 'selected' : '') . '>' . e($c->localizedName()) . '</option>')->implode('') . '
            </select>
        ',
    ])

    {{-- ══════════ TABLE VIEW ══════════ --}}
    <div id="bk-view-table">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="bk-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('الشركة') }}</th>
                                <th>{{ __('اسم الفرع') }}</th>
                                <th>{{ __('الهاتف') }}</th>
                                <th>{{ __('المقر الرئيسي') }}</th>
                                <th class="text-center">{{ __('الخدمات') }}</th>
                                <th class="text-center">{{ __('الموظفون') }}</th>
                                <th class="text-end pe-4 no-export">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($branches as $branch)
                                <tr style="cursor:pointer;" onclick="location.href='{{ route('owner.branches.edit', $branch) }}'">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="wd-36 ht-36 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="background:rgba(201,162,39,.12);color:#C9A227;border:1px solid rgba(201,162,39,.2);">
                                                <i data-feather="map-pin" style="width:15px;height:15px;"></i>
                                            </div>
                                            <div>
                                                <p class="fw-semibold mb-0 tx-13">{{ $branch->company?->localizedName() ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="fw-semibold mb-0 tx-13">{{ $branch->name_en ?: $branch->name_ar ?: '—' }}</p>
                                        @if($branch->name_ar && $branch->name_en)
                                            <p class="tx-12 text-muted mb-0" dir="rtl" lang="ar">{{ $branch->name_ar }}</p>
                                        @endif
                                    </td>
                                    <td class="text-muted tx-13">{{ $branch->phone ?: '—' }}</td>
                                    <td>
                                        @if($branch->is_head_office)
                                            <span class="badge rounded-pill fw-semibold tx-11"
                                                  style="background:rgba(201,162,39,.15);color:#C9A227;border:1px solid rgba(201,162,39,.25);">
                                                <i data-feather="star" style="width:10px;height:10px;"></i> {{ __('نعم') }}
                                            </span>
                                        @else
                                            <span class="text-muted opacity-40 tx-12">{{ __('لا') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-muted border tx-12">{{ $branch->services->count() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-muted border tx-12">{{ $branch->employees->count() }}</span>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap" onclick="event.stopPropagation()">
                                        <a href="{{ route('owner.branches.edit', $branch) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                            <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                                        </a>
                                        <div class="btn-group">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle px-3"
                                                    data-bs-toggle="dropdown">
                                                {{ __('المزيد') }}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                       href="{{ route('owner.branches.working-hours.create', $branch) }}">
                                                        <i data-feather="clock" style="width:14px;height:14px;"></i>
                                                        {{ __('أوقات العمل') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                       href="{{ route('owner.branches.services.index', $branch) }}">
                                                        <i data-feather="scissors" style="width:14px;height:14px;"></i>
                                                        {{ __('الخدمات') }}
                                                        @if($branch->services->count())
                                                            <span class="ms-auto badge bg-secondary rounded-pill">{{ $branch->services->count() }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                       href="{{ route('owner.branches.employees.index', $branch) }}">
                                                        <i data-feather="users" style="width:14px;height:14px;"></i>
                                                        {{ __('الموظفون') }}
                                                        @if($branch->employees->count())
                                                            <span class="ms-auto badge bg-secondary rounded-pill">{{ $branch->employees->count() }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('owner.branches.destroy', $branch) }}"
                                                          method="post"
                                                          onsubmit="return confirm('{{ __('حذف هذا الفرع؟') }}');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                            <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                            {{ __('حذف') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <i data-feather="map-pin" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                            <p class="mb-0">{{ __('لا توجد فروع بعد.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($branches->hasPages())
                <div class="card-footer bg-transparent border-0 py-3">{{ $branches->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════ CARD VIEW ══════════ --}}
    <div id="bk-view-card" style="display:none;">
        @forelse($branches as $branch)
        <div class="bk-branch-card bk-a{{ ($loop->index % 6) + 1 }}">

            <div class="bk-branch-card-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="bk-branch-avatar">
                        <i data-feather="map-pin" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="bk-branch-name">{{ $branch->localizedName() }}</div>
                        <div class="bk-branch-company">
                            <i data-feather="briefcase" style="width:11px;height:11px;display:inline;"></i>
                            {{ $branch->company?->localizedName() ?? '—' }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($branch->is_head_office)
                        <span class="badge rounded-pill fw-semibold tx-11" style="background:rgba(201,162,39,.15);color:#C9A227;border:1px solid rgba(201,162,39,.25);">
                            <i data-feather="star" style="width:10px;height:10px;"></i> {{ __('المقر الرئيسي') }}
                        </span>
                    @endif
                    @if($branch->phone)
                        <span style="font-size:.75rem;color:rgba(255,255,255,.4);">
                            <i data-feather="phone" style="width:11px;height:11px;display:inline;"></i> {{ $branch->phone }}
                        </span>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle px-3" data-bs-toggle="dropdown">
                            {{ __('الإجراءات') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.edit', $branch) }}">
                                <i data-feather="edit-2" style="width:14px;height:14px;"></i>{{ __('تعديل') }}</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.working-hours.create', $branch) }}">
                                <i data-feather="clock" style="width:14px;height:14px;"></i>{{ __('أوقات العمل') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('owner.branches.destroy', $branch) }}" method="post" onsubmit="return confirm('{{ __('حذف هذا الفرع؟') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>{{ __('حذف') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            @php $bid = $branch->id; @endphp
            <ul class="bk-branch-tabs">
                <li class="bk-branch-tab active" data-tab="services-{{ $bid }}">
                    <i data-feather="scissors" style="width:13px;height:13px;"></i>
                    {{ __('الخدمات') }}
                    <span class="bk-branch-tab-count">{{ $branch->services->count() }}</span>
                </li>
                <li class="bk-branch-tab" data-tab="employees-{{ $bid }}">
                    <i data-feather="users" style="width:13px;height:13px;"></i>
                    {{ __('الموظفون') }}
                    <span class="bk-branch-tab-count">{{ $branch->employees->count() }}</span>
                </li>
                <li class="bk-branch-tab" data-tab="hours-{{ $bid }}">
                    <i data-feather="clock" style="width:13px;height:13px;"></i>
                    {{ __('أوقات العمل') }}
                    <span class="bk-branch-tab-count">{{ $branch->workingHours->count() }}</span>
                </li>
            </ul>

            {{-- Services Panel --}}
            <div class="bk-branch-panel active" id="services-{{ $bid }}">
                @if($branch->services->count())
                    <div class="bk-branch-grid">
                        @foreach($branch->services as $svc)
                        <div class="bk-branch-item">
                            <div class="bk-branch-item-icon" style="background:rgba(61,187,212,.12);color:#3dbbd4;">
                                <i data-feather="scissors" style="width:14px;height:14px;"></i>
                            </div>
                            <div class="bk-branch-item-body">
                                <div class="bk-branch-item-name">{{ $svc->localizedName() }}</div>
                                @if($svc->duration_minutes || $svc->price)
                                    <div class="bk-branch-item-meta">
                                        @if($svc->duration_minutes)<i data-feather="clock" style="width:10px;height:10px;"></i> {{ $svc->duration_minutes }} {{ __('د') }}@endif
                                        @if($svc->price) · {{ number_format($svc->price, 0) }} {{ config('app.currency','SAR') }}@endif
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('owner.branches.services.index', $branch) }}" class="bk-branch-item-action">
                                <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route('owner.branches.services.create', $branch) }}" class="bk-branch-add-btn mt-2">
                        <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('إضافة خدمة') }}
                    </a>
                @else
                    <div class="bk-branch-empty">
                        <i data-feather="scissors" style="width:22px;height:22px;opacity:.3;"></i>
                        <span>{{ __('لا توجد خدمات بعد') }}</span>
                        <a href="{{ route('owner.branches.services.create', $branch) }}" class="bk-branch-add-btn">
                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('إضافة خدمة') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Employees Panel --}}
            <div class="bk-branch-panel" id="employees-{{ $bid }}">
                @if($branch->employees->count())
                    <div class="bk-branch-grid">
                        @foreach($branch->employees as $emp)
                        <div class="bk-branch-item">
                            <div class="bk-branch-item-icon" style="background:rgba(43,207,126,.12);color:#2bcf7e;">
                                <span style="font-size:.7rem;font-weight:800;">{{ strtoupper(substr($emp->localizedName(),0,2)) }}</span>
                            </div>
                            <div class="bk-branch-item-body">
                                <div class="bk-branch-item-name">{{ $emp->localizedName() }}</div>
                            </div>
                            <a href="{{ route('owner.branches.employees.index', $branch) }}" class="bk-branch-item-action">
                                <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route('owner.branches.employees.create', $branch) }}" class="bk-branch-add-btn mt-2">
                        <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('إضافة موظف') }}
                    </a>
                @else
                    <div class="bk-branch-empty">
                        <i data-feather="users" style="width:22px;height:22px;opacity:.3;"></i>
                        <span>{{ __('لا يوجد موظفون بعد') }}</span>
                        <a href="{{ route('owner.branches.employees.create', $branch) }}" class="bk-branch-add-btn">
                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('إضافة موظف') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Working Hours Panel --}}
            <div class="bk-branch-panel" id="hours-{{ $bid }}">
                @if($branch->workingHours->count())
                    @php $days = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']; @endphp
                    <div class="bk-hours-grid">
                        @foreach($days as $di => $day)
                            @php $wh = $branch->workingHours->firstWhere('day_of_week', $di); @endphp
                            <div class="bk-hours-row {{ $wh && !$wh->is_closed ? 'open' : 'closed' }}">
                                <span class="bk-hours-day">{{ $day }}</span>
                                @if($wh && !$wh->is_closed)
                                    <span class="bk-hours-time">{{ substr($wh->open_time,0,5) }} — {{ substr($wh->close_time,0,5) }}</span>
                                @else
                                    <span class="bk-hours-closed">{{ __('مغلق') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="bk-branch-add-btn mt-3">
                        <i data-feather="edit-2" style="width:13px;height:13px;"></i> {{ __('تعديل الأوقات') }}
                    </a>
                @else
                    <div class="bk-branch-empty">
                        <i data-feather="clock" style="width:22px;height:22px;opacity:.3;"></i>
                        <span>{{ __('لم يتم تحديد أوقات العمل') }}</span>
                        <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="bk-branch-add-btn">
                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('تحديد الأوقات') }}
                        </a>
                    </div>
                @endif
            </div>

        </div>
        @empty
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <div class="d-flex flex-column align-items-center gap-2">
                    <i data-feather="map-pin" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                    <p class="mb-0 text-muted">{{ __('لا توجد فروع بعد.') }}</p>
                </div>
            </div>
        @endforelse

        @if($branches->hasPages())
            <div class="card border-0 shadow-sm rounded-4 mt-3 px-4 py-3">
                {{ $branches->links() }}
            </div>
        @endif
    </div>

</div>

@push('owner-styles')
<style>
/* ── View Toggle ── */
.bk-view-toggle {
    display:inline-flex; gap:3px; padding:4px;
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.1); border-radius:10px;
}
.bk-theme-light .bk-view-toggle { background:rgba(0,0,0,.04); border-color:rgba(0,0,0,.08); }
.bk-vt-btn {
    display:flex; align-items:center; justify-content:center;
    width:32px; height:32px; border-radius:7px;
    border:none; background:transparent;
    color:rgba(255,255,255,.4); cursor:pointer; transition:all .18s;
}
.bk-theme-light .bk-vt-btn { color:rgba(0,0,0,.4); }
.bk-vt-btn.active { background:#C9A227; color:#000 !important; }
.bk-vt-btn:hover:not(.active) { background:rgba(255,255,255,.08); color:rgba(255,255,255,.8); }
.bk-theme-light .bk-vt-btn:hover:not(.active) { background:rgba(0,0,0,.06); color:rgba(0,0,0,.8); }

/* ── Branch Card ── */
.bk-branch-card {
    background:var(--card-bg, #1a2234);
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px; margin-bottom:18px; overflow:hidden;
    transition:box-shadow .25s, border-color .25s;
}
.bk-theme-light .bk-branch-card { background:#fff; border-color:rgba(0,0,0,.07); box-shadow:0 2px 12px rgba(0,0,0,.06); }
.bk-branch-card:hover { box-shadow:0 8px 32px rgba(0,0,0,.2); border-color:rgba(201,162,39,.2); }
.bk-theme-light .bk-branch-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.1); border-color:rgba(201,162,39,.15); }

.bk-branch-card-header {
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
    padding:20px 24px;
    border-bottom:1px solid rgba(255,255,255,.06);
    background:linear-gradient(135deg,rgba(201,162,39,.05),transparent);
}
.bk-theme-light .bk-branch-card-header { border-bottom-color:rgba(0,0,0,.05); }

.bk-branch-avatar {
    width:46px; height:46px; border-radius:14px; flex-shrink:0;
    background:rgba(201,162,39,.15); color:#C9A227;
    display:flex; align-items:center; justify-content:center;
    border:1px solid rgba(201,162,39,.2);
}
.bk-branch-name { font-size:1rem; font-weight:800; font-family:'Poppins',sans-serif; }
.bk-branch-company { font-size:.73rem; color:#C9A227; margin-top:2px; display:flex; align-items:center; gap:4px; }

/* ── Branch Tabs ── */
.bk-branch-tabs {
    list-style:none; margin:0; padding:0 20px;
    display:flex; gap:2px; border-bottom:1px solid rgba(255,255,255,.06); overflow-x:auto;
}
.bk-theme-light .bk-branch-tabs { border-bottom-color:rgba(0,0,0,.06); }
.bk-branch-tab {
    display:flex; align-items:center; gap:6px; padding:12px 16px;
    font-size:.78rem; font-weight:600; color:rgba(255,255,255,.4);
    cursor:pointer; border-bottom:2px solid transparent; transition:all .18s; white-space:nowrap;
}
.bk-theme-light .bk-branch-tab { color:rgba(0,0,0,.45); }
.bk-branch-tab:hover { color:rgba(255,255,255,.8); }
.bk-theme-light .bk-branch-tab:hover { color:rgba(0,0,0,.8); }
.bk-branch-tab.active { color:#C9A227 !important; border-bottom-color:#C9A227; }
.bk-branch-tab-count {
    background:rgba(255,255,255,.08); border-radius:20px;
    padding:1px 7px; font-size:.65rem; font-weight:800;
}
.bk-theme-light .bk-branch-tab-count { background:rgba(0,0,0,.06); color:rgba(0,0,0,.6); }
.bk-branch-tab.active .bk-branch-tab-count { background:rgba(201,162,39,.2); color:#C9A227; }

.bk-branch-panel { display:none; padding:20px 24px; }
.bk-branch-panel.active { display:block; }

/* ── Branch Grid Items ── */
.bk-branch-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:10px; margin-bottom:14px; }
.bk-branch-item {
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    border-radius:10px; background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.06); transition:background .18s;
}
.bk-theme-light .bk-branch-item { background:rgba(0,0,0,.02); border-color:rgba(0,0,0,.06); }
.bk-branch-item:hover { background:rgba(255,255,255,.06); }
.bk-theme-light .bk-branch-item:hover { background:rgba(0,0,0,.04); }
.bk-branch-item-icon { width:34px; height:34px; border-radius:9px; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
.bk-branch-item-body { flex:1; min-width:0; }
.bk-branch-item-name { font-size:.82rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bk-branch-item-meta { font-size:.7rem; color:rgba(255,255,255,.4); margin-top:2px; display:flex; align-items:center; gap:4px; }
.bk-theme-light .bk-branch-item-meta { color:rgba(0,0,0,.4); }
.bk-branch-item-action {
    width:28px; height:28px; border-radius:8px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,.05); color:rgba(255,255,255,.3);
    text-decoration:none; transition:all .18s;
}
.bk-theme-light .bk-branch-item-action { background:rgba(0,0,0,.04); color:rgba(0,0,0,.35); }
.bk-branch-item-action:hover { background:#C9A227; color:#000 !important; }

.bk-branch-add-btn {
    display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:20px;
    font-size:.76rem; font-weight:700;
    background:rgba(201,162,39,.1); color:#C9A227 !important;
    border:1px solid rgba(201,162,39,.2); text-decoration:none; transition:all .2s;
}
.bk-branch-add-btn:hover { background:#C9A227; color:#000 !important; box-shadow:0 4px 14px rgba(201,162,39,.3); }

.bk-branch-empty {
    display:flex; flex-direction:column; align-items:center;
    gap:10px; padding:28px; color:rgba(255,255,255,.3); font-size:.82rem;
}
.bk-theme-light .bk-branch-empty { color:rgba(0,0,0,.3); }

/* ── Working Hours ── */
.bk-hours-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:8px; }
.bk-hours-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:9px 14px; border-radius:10px;
    border:1px solid rgba(255,255,255,.06); background:rgba(255,255,255,.02);
}
.bk-theme-light .bk-hours-row { background:rgba(0,0,0,.02); border-color:rgba(0,0,0,.06); }
.bk-hours-row.open { border-color:rgba(43,207,126,.2); background:rgba(43,207,126,.05); }
.bk-hours-day { font-size:.78rem; font-weight:700; }
.bk-hours-time { font-size:.75rem; color:#2bcf7e; font-weight:600; }
.bk-hours-closed { font-size:.72rem; color:rgba(255,255,255,.25); font-weight:600; }
.bk-theme-light .bk-hours-closed { color:rgba(0,0,0,.25); }
</style>
@endpush

@push('scripts')
<script>
(function(){
    /* ── View Toggle ── */
    function setView(v) {
        document.getElementById('bk-view-table').style.display = v === 'table' ? '' : 'none';
        document.getElementById('bk-view-card').style.display  = v === 'card'  ? '' : 'none';
        document.querySelectorAll('.bk-vt-btn').forEach(function(b){
            b.classList.toggle('active', b.dataset.view === v);
        });
        if (typeof feather !== 'undefined') feather.replace();
    }
    var saved = localStorage.getItem('bk_branch_view') || 'table';
    setView(saved);
    document.querySelectorAll('.bk-vt-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var v = this.dataset.view;
            localStorage.setItem('bk_branch_view', v);
            setView(v);
        });
    });

    /* ── Card Tabs ── */
    document.querySelectorAll('.bk-branch-tab').forEach(function(tab){
        tab.addEventListener('click', function(){
            var card = this.closest('.bk-branch-card');
            card.querySelectorAll('.bk-branch-tab').forEach(function(t){ t.classList.remove('active'); });
            card.querySelectorAll('.bk-branch-panel').forEach(function(p){ p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.getElementById(this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    setTimeout(function(){ if (typeof feather !== 'undefined') feather.replace(); }, 80);
})();
</script>
@endpush

@include('owner.partials._datatable', [
    'tableId'    => 'bk-table',
    'exportName' => 'Branches',
    'noSortCols' => [-1],
])

@endsection
