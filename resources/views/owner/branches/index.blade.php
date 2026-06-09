@extends('owner.dashboard')
@section('content')
<div class="page-content">

    {{-- ── Header ── --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 grid-margin">
        <div>
            <h4 class="mb-2">{{ __('Branches') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Branches') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('owner.branches.create') }}" class="btn btn-primary btn-icon-text rounded-pill shadow-sm">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add branch') }}
        </a>
    </div>

    @include('owner.partials.flash')

    {{-- ── Toolbar: Search + View Toggle ── --}}
    <div class="bk-dt-toolbar">
        {{-- Search --}}
        <form method="GET" action="{{ route('owner.branches.index') }}" id="bk-search-form" class="bk-dt-search-wrap">
            <div class="bk-dt-search">
                <i data-feather="search" class="bk-dt-search-icon"></i>
                <input
                    type="text"
                    name="q"
                    id="bk-search-input"
                    value="{{ $search }}"
                    placeholder="{{ __('Search by name, phone, company…') }}"
                    autocomplete="off"
                    class="bk-dt-search-input"
                >
                @if($search)
                    <a href="{{ route('owner.branches.index') }}" class="bk-dt-search-clear" title="{{ __('Clear') }}">
                        <i data-feather="x" style="width:14px;height:14px;"></i>
                    </a>
                @endif
            </div>
        </form>

        {{-- Right: count + view toggle --}}
        <div class="d-flex align-items-center gap-3">
            <span class="bk-dt-count">
                {{ $branches->total() }} {{ __('branches') }}
                @if($search) &nbsp;·&nbsp; <span style="color:#C9A227;">{{ __('filtered') }}</span>@endif
            </span>

            <div class="bk-view-toggle" id="bk-view-toggle">
                <button class="bk-vt-btn" data-view="table" title="{{ __('Table view') }}">
                    <i data-feather="list" style="width:15px;height:15px;"></i>
                </button>
                <button class="bk-vt-btn" data-view="card" title="{{ __('Card view') }}">
                    <i data-feather="grid" style="width:15px;height:15px;"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════ TABLE VIEW ══════════ --}}
    <div id="bk-view-table">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="bk-table">
                        <thead>
                            <tr>
                                <th class="ps-4">{{ __('Company') }}</th>
                                <th>{{ __('Branch name') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Head office') }}</th>
                                <th class="text-center">{{ __('Services') }}</th>
                                <th class="text-center">{{ __('Employees') }}</th>
                                <th class="text-end pe-4">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($branches as $branch)
                                <tr class="bk-table-row" onclick="location.href='{{ route('owner.branches.edit', $branch) }}'">
                                    <td class="ps-4">
                                        <span class="text-muted tx-13">{{ $branch->company?->localizedName() ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold tx-13">{{ $branch->name_en ?: $branch->name_ar ?: '—' }}</div>
                                        @if($branch->name_ar && $branch->name_en)
                                            <small class="text-muted opacity-50" dir="rtl" lang="ar">{{ $branch->name_ar }}</small>
                                        @endif
                                    </td>
                                    <td class="text-muted tx-13">{{ $branch->phone ?: '—' }}</td>
                                    <td>
                                        @if ($branch->is_head_office)
                                            <span class="bk-badge" style="background:rgba(201,162,39,.15);color:#C9A227;">
                                                <i data-feather="star" style="width:10px;height:10px;"></i> {{ __('Yes') }}
                                            </span>
                                        @else
                                            <span class="text-muted opacity-40 tx-12">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="bk-dt-pill">{{ $branch->services->count() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="bk-dt-pill">{{ $branch->employees->count() }}</span>
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
                                                {{ __('More') }}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                       href="{{ route('owner.branches.working-hours.create', $branch) }}">
                                                        <i data-feather="clock" style="width:14px;height:14px;"></i>
                                                        {{ __('Working hours') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                       href="{{ route('owner.branches.services.index', $branch) }}">
                                                        <i data-feather="scissors" style="width:14px;height:14px;"></i>
                                                        {{ __('Services') }}
                                                        @if($branch->services->count())
                                                            <span class="ms-auto badge bg-secondary rounded-pill">{{ $branch->services->count() }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                       href="{{ route('owner.branches.employees.index', $branch) }}">
                                                        <i data-feather="users" style="width:14px;height:14px;"></i>
                                                        {{ __('Employees') }}
                                                        @if($branch->employees->count())
                                                            <span class="ms-auto badge bg-secondary rounded-pill">{{ $branch->employees->count() }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('owner.branches.destroy', $branch) }}"
                                                          method="post"
                                                          onsubmit="return confirm('{{ __('Delete this branch?') }}');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                            <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="bk-empty py-5">
                                            <div class="bk-empty-ic">
                                                <i data-feather="{{ $search ? 'search' : 'map-pin' }}" style="width:24px;height:24px;"></i>
                                            </div>
                                            <p>{{ $search ? __('No results for ":q"', ['q' => $search]) : __('No branches yet.') }}</p>
                                            @if(!$search)
                                                <a href="{{ route('owner.branches.create') }}" class="btn btn-primary rounded-pill px-4">
                                                    {{ __('Add first branch') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        @if($branches->hasPages())
            <div class="bk-pagination-wrap">
                {{-- Info --}}
                <div class="bk-pg-info">
                    {{ __('Showing') }}
                    <strong>{{ $branches->firstItem() }}–{{ $branches->lastItem() }}</strong>
                    {{ __('of') }}
                    <strong>{{ $branches->total() }}</strong>
                </div>

                {{-- Pages --}}
                <nav class="bk-pg-nav" aria-label="Pagination">
                    {{-- Prev --}}
                    @if($branches->onFirstPage())
                        <span class="bk-pg-btn disabled"><i data-feather="chevron-left" style="width:15px;height:15px;"></i></span>
                    @else
                        <a href="{{ $branches->previousPageUrl() }}" class="bk-pg-btn">
                            <i data-feather="chevron-left" style="width:15px;height:15px;"></i>
                        </a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($branches->getUrlRange(max(1, $branches->currentPage()-2), min($branches->lastPage(), $branches->currentPage()+2)) as $page => $url)
                        @if($page == $branches->currentPage())
                            <span class="bk-pg-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="bk-pg-btn">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($branches->hasMorePages())
                        <a href="{{ $branches->nextPageUrl() }}" class="bk-pg-btn">
                            <i data-feather="chevron-right" style="width:15px;height:15px;"></i>
                        </a>
                    @else
                        <span class="bk-pg-btn disabled"><i data-feather="chevron-right" style="width:15px;height:15px;"></i></span>
                    @endif
                </nav>
            </div>
        @endif
    </div>

    {{-- ══════════ CARD VIEW ══════════ --}}
    <div id="bk-view-card" style="display:none;">
        @forelse($branches as $branch)
        <div class="bk-branch-card bk-a{{ ($loop->index % 6) + 1 }}">

            {{-- Branch Header --}}
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
                        <span class="bk-badge" style="background:rgba(201,162,39,.15);color:#C9A227;">
                            <i data-feather="star" style="width:10px;height:10px;"></i>
                            {{ __('Head office') }}
                        </span>
                    @endif
                    @if($branch->phone)
                        <span style="font-size:.75rem;color:rgba(255,255,255,.4);">
                            <i data-feather="phone" style="width:11px;height:11px;display:inline;"></i>
                            {{ $branch->phone }}
                        </span>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle px-3" data-bs-toggle="dropdown">
                            {{ __('Actions') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.edit', $branch) }}">
                                <i data-feather="edit-2" style="width:14px;height:14px;"></i>{{ __('Edit') }}</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.working-hours.create', $branch) }}">
                                <i data-feather="clock" style="width:14px;height:14px;"></i>{{ __('Working hours') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('owner.branches.destroy', $branch) }}" method="post" onsubmit="return confirm('{{ __('Delete this branch?') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>{{ __('Delete') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            @php $bid = $branch->id; @endphp
            <ul class="bk-branch-tabs" id="tabs-{{ $bid }}">
                <li class="bk-branch-tab active" data-tab="services-{{ $bid }}">
                    <i data-feather="scissors" style="width:13px;height:13px;"></i>
                    {{ __('Services') }}
                    <span class="bk-branch-tab-count">{{ $branch->services->count() }}</span>
                </li>
                <li class="bk-branch-tab" data-tab="employees-{{ $bid }}">
                    <i data-feather="users" style="width:13px;height:13px;"></i>
                    {{ __('Employees') }}
                    <span class="bk-branch-tab-count">{{ $branch->employees->count() }}</span>
                </li>
                <li class="bk-branch-tab" data-tab="hours-{{ $bid }}">
                    <i data-feather="clock" style="width:13px;height:13px;"></i>
                    {{ __('Working hours') }}
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
                                        @if($svc->duration_minutes)
                                            <i data-feather="clock" style="width:10px;height:10px;"></i>
                                            {{ $svc->duration_minutes }} {{ __('min') }}
                                        @endif
                                        @if($svc->price) · {{ number_format($svc->price, 0) }} {{ config('app.currency','SAR') }} @endif
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('owner.branches.services.index', $branch) }}" class="bk-branch-item-action">
                                <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route('owner.branches.services.create', $branch) }}" class="bk-branch-add-btn">
                        <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('Add service') }}
                    </a>
                @else
                    <div class="bk-branch-empty">
                        <i data-feather="scissors" style="width:22px;height:22px;opacity:.3;"></i>
                        <span>{{ __('No services yet') }}</span>
                        <a href="{{ route('owner.branches.services.create', $branch) }}" class="bk-branch-add-btn">
                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('Add service') }}
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
                                @if(isset($emp->title) && $emp->title)
                                    <div class="bk-branch-item-meta">{{ $emp->title }}</div>
                                @endif
                            </div>
                            <a href="{{ route('owner.branches.employees.index', $branch) }}" class="bk-branch-item-action">
                                <i data-feather="arrow-right" style="width:12px;height:12px;"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route('owner.branches.employees.create', $branch) }}" class="bk-branch-add-btn">
                        <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('Add employee') }}
                    </a>
                @else
                    <div class="bk-branch-empty">
                        <i data-feather="users" style="width:22px;height:22px;opacity:.3;"></i>
                        <span>{{ __('No employees yet') }}</span>
                        <a href="{{ route('owner.branches.employees.create', $branch) }}" class="bk-branch-add-btn">
                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('Add employee') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Working Hours Panel --}}
            <div class="bk-branch-panel" id="hours-{{ $bid }}">
                @if($branch->workingHours->count())
                    @php $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; @endphp
                    <div class="bk-hours-grid">
                        @foreach($days as $di => $day)
                            @php $wh = $branch->workingHours->firstWhere('day_of_week', $di); @endphp
                            <div class="bk-hours-row {{ $wh && !$wh->is_closed ? 'open' : 'closed' }}">
                                <span class="bk-hours-day">{{ __($day) }}</span>
                                @if($wh && !$wh->is_closed)
                                    <span class="bk-hours-time">{{ substr($wh->open_time,0,5) }} — {{ substr($wh->close_time,0,5) }}</span>
                                @else
                                    <span class="bk-hours-closed">{{ __('Closed') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="bk-branch-add-btn mt-3">
                        <i data-feather="edit-2" style="width:13px;height:13px;"></i> {{ __('Edit hours') }}
                    </a>
                @else
                    <div class="bk-branch-empty">
                        <i data-feather="clock" style="width:22px;height:22px;opacity:.3;"></i>
                        <span>{{ __('No working hours set') }}</span>
                        <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="bk-branch-add-btn">
                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('Set hours') }}
                        </a>
                    </div>
                @endif
            </div>

        </div>
        @empty
            <div class="bk-empty py-5">
                <div class="bk-empty-ic"><i data-feather="map-pin" style="width:24px;height:24px;"></i></div>
                <p>{{ $search ? __('No results for ":q"', ['q' => $search]) : __('No branches yet.') }}</p>
            </div>
        @endforelse

        {{-- Card view pagination --}}
        @if($branches->hasPages())
            <div class="bk-pagination-wrap">
                <div class="bk-pg-info">
                    {{ __('Showing') }} <strong>{{ $branches->firstItem() }}–{{ $branches->lastItem() }}</strong>
                    {{ __('of') }} <strong>{{ $branches->total() }}</strong>
                </div>
                <nav class="bk-pg-nav">
                    @if($branches->onFirstPage())
                        <span class="bk-pg-btn disabled"><i data-feather="chevron-left" style="width:15px;height:15px;"></i></span>
                    @else
                        <a href="{{ $branches->previousPageUrl() }}" class="bk-pg-btn">
                            <i data-feather="chevron-left" style="width:15px;height:15px;"></i>
                        </a>
                    @endif
                    @foreach($branches->getUrlRange(max(1,$branches->currentPage()-2), min($branches->lastPage(),$branches->currentPage()+2)) as $page => $url)
                        <a href="{{ $url }}" class="bk-pg-btn {{ $page == $branches->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach
                    @if($branches->hasMorePages())
                        <a href="{{ $branches->nextPageUrl() }}" class="bk-pg-btn">
                            <i data-feather="chevron-right" style="width:15px;height:15px;"></i>
                        </a>
                    @else
                        <span class="bk-pg-btn disabled"><i data-feather="chevron-right" style="width:15px;height:15px;"></i></span>
                    @endif
                </nav>
            </div>
        @endif
    </div>

</div>

@push('owner-styles')
<style>
/* ── Toolbar ── */
.bk-dt-toolbar {
    display:flex;align-items:center;justify-content:space-between;
    flex-wrap:wrap;gap:12px;margin-bottom:18px;
}
.bk-dt-search-wrap { flex:1;min-width:220px;max-width:420px; }
.bk-dt-search {
    position:relative;display:flex;align-items:center;
    background:var(--card-bg,#1a2234);
    border:1px solid rgba(255,255,255,.1);
    border-radius:12px;overflow:hidden;
    transition:border-color .2s, box-shadow .2s;
}
.bk-dt-search:focus-within {
    border-color:#C9A227;
    box-shadow:0 0 0 3px rgba(201,162,39,.12);
}
.bk-theme-light .bk-dt-search { background:#fff;border-color:rgba(0,0,0,.1); }
.bk-dt-search-icon {
    position:absolute;left:14px;
    width:15px;height:15px;
    color:rgba(255,255,255,.3);pointer-events:none;
    flex-shrink:0;
}
.bk-theme-light .bk-dt-search-icon { color:rgba(0,0,0,.3); }
.bk-dt-search-input {
    width:100%;padding:10px 40px 10px 42px;
    background:transparent;border:none;outline:none;
    font-size:.84rem;color:inherit;
}
.bk-dt-search-clear {
    position:absolute;right:12px;
    color:rgba(255,255,255,.3);text-decoration:none;
    display:flex;align-items:center;
    transition:color .15s;
}
.bk-dt-search-clear:hover { color:#e53935; }
.bk-theme-light .bk-dt-search-clear { color:rgba(0,0,0,.3); }

.bk-dt-count { font-size:.78rem;color:rgba(255,255,255,.4);white-space:nowrap; }
.bk-theme-light .bk-dt-count { color:rgba(0,0,0,.4); }

/* ── View Toggle ── */
.bk-view-toggle {
    display:inline-flex;gap:3px;padding:4px;
    background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;
}
.bk-theme-light .bk-view-toggle { background:rgba(0,0,0,.04);border-color:rgba(0,0,0,.08); }
.bk-vt-btn {
    display:flex;align-items:center;justify-content:center;
    width:32px;height:32px;border-radius:7px;
    border:none;background:transparent;
    color:rgba(255,255,255,.4);cursor:pointer;transition:all .18s;
}
.bk-theme-light .bk-vt-btn { color:rgba(0,0,0,.4); }
.bk-vt-btn.active { background:#C9A227;color:#000 !important; }
.bk-vt-btn:hover:not(.active) { background:rgba(255,255,255,.08);color:rgba(255,255,255,.8); }
.bk-theme-light .bk-vt-btn:hover:not(.active) { background:rgba(0,0,0,.06);color:rgba(0,0,0,.8); }

/* ── Count pill ── */
.bk-dt-pill {
    display:inline-flex;align-items:center;justify-content:center;
    min-width:26px;height:22px;padding:0 8px;
    border-radius:20px;font-size:.68rem;font-weight:800;
    background:rgba(255,255,255,.07);color:rgba(255,255,255,.5);
}
.bk-theme-light .bk-dt-pill { background:rgba(0,0,0,.06);color:rgba(0,0,0,.5); }

/* ── Pagination ── */
.bk-pagination-wrap {
    display:flex;align-items:center;justify-content:space-between;
    flex-wrap:wrap;gap:12px;
    margin-top:20px;padding:16px 20px;
    background:var(--card-bg,#1a2234);
    border:1px solid rgba(255,255,255,.07);
    border-radius:14px;
}
.bk-theme-light .bk-pagination-wrap { background:#fff;border-color:rgba(0,0,0,.07);box-shadow:0 2px 8px rgba(0,0,0,.05); }

.bk-pg-info { font-size:.78rem;color:rgba(255,255,255,.4); }
.bk-theme-light .bk-pg-info { color:rgba(0,0,0,.4); }
.bk-pg-info strong { color:rgba(255,255,255,.8); }
.bk-theme-light .bk-pg-info strong { color:rgba(0,0,0,.8); }

.bk-pg-nav { display:flex;align-items:center;gap:4px; }
.bk-pg-btn {
    display:inline-flex;align-items:center;justify-content:center;
    min-width:34px;height:34px;padding:0 6px;
    border-radius:9px;
    font-size:.8rem;font-weight:600;
    color:rgba(255,255,255,.55);
    text-decoration:none;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.07);
    transition:all .18s;cursor:pointer;
}
.bk-theme-light .bk-pg-btn { color:rgba(0,0,0,.55);background:rgba(0,0,0,.03);border-color:rgba(0,0,0,.07); }
.bk-pg-btn:hover:not(.active):not(.disabled) {
    background:rgba(201,162,39,.1);border-color:rgba(201,162,39,.3);color:#C9A227;
}
.bk-pg-btn.active {
    background:#C9A227;border-color:#C9A227;color:#000 !important;
    box-shadow:0 3px 10px rgba(201,162,39,.35);font-weight:800;
}
.bk-pg-btn.disabled { opacity:.3;cursor:not-allowed; }

/* ── Branch Card (Card View) ── */
.bk-branch-card {
    background:var(--card-bg,#1a2234);
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;margin-bottom:18px;overflow:hidden;
    transition:box-shadow .25s,border-color .25s;
}
.bk-theme-light .bk-branch-card { background:#fff;border-color:rgba(0,0,0,.07);box-shadow:0 2px 12px rgba(0,0,0,.06); }
.bk-branch-card:hover { box-shadow:0 8px 32px rgba(0,0,0,.2);border-color:rgba(201,162,39,.2); }
.bk-theme-light .bk-branch-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.1);border-color:rgba(201,162,39,.15); }

.bk-branch-card-header {
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
    padding:20px 24px;
    border-bottom:1px solid rgba(255,255,255,.06);
    background:linear-gradient(135deg,rgba(201,162,39,.05),transparent);
}
.bk-theme-light .bk-branch-card-header { border-bottom-color:rgba(0,0,0,.05); }

.bk-branch-avatar {
    width:46px;height:46px;border-radius:14px;flex-shrink:0;
    background:rgba(201,162,39,.15);color:#C9A227;
    display:flex;align-items:center;justify-content:center;
    border:1px solid rgba(201,162,39,.2);
}
.bk-branch-name { font-size:1rem;font-weight:800;font-family:'Poppins',sans-serif; }
.bk-branch-company { font-size:.73rem;color:#C9A227;margin-top:2px;display:flex;align-items:center;gap:4px; }

.bk-branch-tabs {
    list-style:none;margin:0;padding:0 20px;
    display:flex;gap:2px;border-bottom:1px solid rgba(255,255,255,.06);overflow-x:auto;
}
.bk-theme-light .bk-branch-tabs { border-bottom-color:rgba(0,0,0,.06); }
.bk-branch-tab {
    display:flex;align-items:center;gap:6px;padding:12px 16px;
    font-size:.78rem;font-weight:600;color:rgba(255,255,255,.4);
    cursor:pointer;border-bottom:2px solid transparent;transition:all .18s;white-space:nowrap;
}
.bk-theme-light .bk-branch-tab { color:rgba(0,0,0,.45); }
.bk-branch-tab:hover { color:rgba(255,255,255,.8);background:rgba(255,255,255,.03); }
.bk-theme-light .bk-branch-tab:hover { color:rgba(0,0,0,.8); }
.bk-branch-tab.active { color:#C9A227 !important;border-bottom-color:#C9A227; }
.bk-branch-tab-count {
    background:rgba(255,255,255,.08);border-radius:20px;
    padding:1px 7px;font-size:.65rem;font-weight:800;
}
.bk-theme-light .bk-branch-tab-count { background:rgba(0,0,0,.06);color:rgba(0,0,0,.6); }
.bk-branch-tab.active .bk-branch-tab-count { background:rgba(201,162,39,.2);color:#C9A227; }

.bk-branch-panel { display:none;padding:20px 24px; }
.bk-branch-panel.active { display:block; }

.bk-branch-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;margin-bottom:14px; }
.bk-branch-item {
    display:flex;align-items:center;gap:10px;padding:10px 12px;
    border-radius:10px;background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.06);transition:background .18s;
}
.bk-theme-light .bk-branch-item { background:rgba(0,0,0,.02);border-color:rgba(0,0,0,.06); }
.bk-branch-item:hover { background:rgba(255,255,255,.06); }
.bk-theme-light .bk-branch-item:hover { background:rgba(0,0,0,.04); }
.bk-branch-item-icon { width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.bk-branch-item-body { flex:1;min-width:0; }
.bk-branch-item-name { font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.bk-branch-item-meta { font-size:.7rem;color:rgba(255,255,255,.4);margin-top:2px;display:flex;align-items:center;gap:4px; }
.bk-theme-light .bk-branch-item-meta { color:rgba(0,0,0,.4); }
.bk-branch-item-action {
    width:28px;height:28px;border-radius:8px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,.05);color:rgba(255,255,255,.3);
    text-decoration:none;transition:all .18s;
}
.bk-theme-light .bk-branch-item-action { background:rgba(0,0,0,.04);color:rgba(0,0,0,.35); }
.bk-branch-item-action:hover { background:#C9A227;color:#000 !important; }

.bk-branch-add-btn {
    display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:20px;
    font-size:.76rem;font-weight:700;
    background:rgba(201,162,39,.1);color:#C9A227 !important;
    border:1px solid rgba(201,162,39,.2);text-decoration:none;transition:all .2s;
}
.bk-branch-add-btn:hover { background:#C9A227;color:#000 !important;box-shadow:0 4px 14px rgba(201,162,39,.3); }

.bk-branch-empty {
    display:flex;flex-direction:column;align-items:center;
    gap:10px;padding:28px;color:rgba(255,255,255,.3);font-size:.82rem;
}
.bk-theme-light .bk-branch-empty { color:rgba(0,0,0,.3); }

.bk-hours-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px; }
.bk-hours-row {
    display:flex;justify-content:space-between;align-items:center;
    padding:9px 14px;border-radius:10px;
    border:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.02);
}
.bk-theme-light .bk-hours-row { background:rgba(0,0,0,.02);border-color:rgba(0,0,0,.06); }
.bk-hours-row.open { border-color:rgba(43,207,126,.2);background:rgba(43,207,126,.05); }
.bk-hours-day { font-size:.78rem;font-weight:700; }
.bk-hours-time { font-size:.75rem;color:#2bcf7e;font-weight:600; }
.bk-hours-closed { font-size:.72rem;color:rgba(255,255,255,.25);font-weight:600; }
.bk-theme-light .bk-hours-closed { color:rgba(0,0,0,.25); }
</style>
@endpush

@push('scripts')
<script>
(function(){

/* ── View Toggle (persisted) ── */
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

/* ── Search: debounce 350ms ── */
var searchInput = document.getElementById('bk-search-input');
var searchForm  = document.getElementById('bk-search-form');
var searchTimer;
if (searchInput) {
    searchInput.addEventListener('input', function(){
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function(){ searchForm.submit(); }, 350);
    });
}

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

@endsection
