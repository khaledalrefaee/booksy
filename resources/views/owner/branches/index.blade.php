@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

@php
    $currency   = config('app.currency', 'SAR');
    $dayLabels  = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];
    $statusMeta = [
        'active'      => ['label' => __('Active'),      'cls' => 'bm-badge-active',      'icon' => 'check-circle'],
        'inactive'    => ['label' => __('Inactive'),    'cls' => 'bm-badge-inactive',    'icon' => 'slash'],
        'maintenance' => ['label' => __('Maintenance'), 'cls' => 'bm-badge-maintenance', 'icon' => 'tool'],
    ];
    // Sort links preserve q + company filter, toggle direction on the active column.
    $sortUrl = function (string $field) use ($sortField, $sortDir) {
        $dir = ($sortField === $field && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $field, 'dir' => $dir, 'page' => 1]);
    };
    $sortCaret = fn (string $field) => $sortField === $field ? ($sortDir === 'asc' ? 'chevron-up' : 'chevron-down') : null;
@endphp

<div class="page-content bm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('Platform') }}
            </div>
            <h1 class="bm-title">{{ __('Branches') }}</h1>
            <p class="bm-subtitle">{{ __('Every location across all businesses on GlowRez — services, staff and opening hours in one place.') }}</p>
        </div>
        <div class="bm-head-actions">
            <div class="bm-viewtoggle" id="bm-view-toggle" role="group" aria-label="{{ __('View mode') }}">
                <button type="button" class="bm-vt" data-view="table" title="{{ __('Table view') }}" aria-label="{{ __('Table view') }}">
                    <i data-feather="list"></i>
                </button>
                <button type="button" class="bm-vt" data-view="card" title="{{ __('Card view') }}" aria-label="{{ __('Card view') }}">
                    <i data-feather="grid"></i>
                </button>
            </div>
            <a href="{{ route('owner.branches.create') }}" class="bm-btn bm-btn-primary">
                <i data-feather="plus"></i>
                {{ __('Add branch') }}
            </a>
        </div>
    </header>

    @include('owner.partials.flash')

    {{-- ═══════════ OVERVIEW ═══════════ --}}
    <section class="bm-stats bm-reveal" aria-label="{{ __('Overview') }}">
        <div class="bm-stat" style="--accent:var(--bk-accent);">
            <span class="bm-stat-label"><i data-feather="map-pin"></i>{{ __('Total branches') }}</span>
            <span class="bm-stat-value">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="bm-stat" style="--accent:var(--bk-success);">
            <span class="bm-stat-label"><i data-feather="check-circle"></i>{{ __('Active') }}</span>
            <span class="bm-stat-value">{{ number_format($stats['active']) }}</span>
        </div>
        <div class="bm-stat" style="--accent:var(--bk-gold);">
            <span class="bm-stat-label"><i data-feather="star"></i>{{ __('Head offices') }}</span>
            <span class="bm-stat-value">{{ number_format($stats['head_offices']) }}</span>
        </div>
        <div class="bm-stat" style="--accent:var(--bk-info);">
            <span class="bm-stat-label"><i data-feather="briefcase"></i>{{ __('Businesses') }}</span>
            <span class="bm-stat-value">{{ number_format($stats['companies']) }}</span>
        </div>
    </section>

    {{-- ═══════════ TOOLBAR ═══════════ --}}
    <form method="GET" action="{{ route('owner.branches.index') }}" class="bm-toolbar bm-reveal" id="bm-filter-form">
        <input type="hidden" name="sort" value="{{ $sortField }}">
        <input type="hidden" name="dir"  value="{{ $sortDir }}" id="bm-dir-input">

        <div class="bm-toolbar-row">
            <div class="bm-search">
                <button type="submit" class="bm-search-btn" aria-label="{{ __('Search') }}" tabindex="-1">
                    <i data-feather="search"></i>
                </button>
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="{{ __('Search by branch, company or phone…') }}"
                       autocomplete="off" aria-label="{{ __('Search branches') }}"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}">
            </div>

            <select name="company_id" class="bm-select" aria-label="{{ __('Company') }}"
                    onchange="document.getElementById('bm-filter-form').submit()">
                <option value="">{{ __('All companies') }}</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" @selected((string) $filterCompanyId === (string) $c->id)>{{ $c->localizedName() }}</option>
                @endforeach
            </select>

            <select name="sort" class="bm-select" aria-label="{{ __('Sort by') }}"
                    onchange="document.getElementById('bm-filter-form').submit()">
                <option value="created_at" @selected($sortField === 'created_at')>{{ __('Newest') }}</option>
                <option value="name"       @selected($sortField === 'name')>{{ __('Name') }}</option>
                <option value="sort_order" @selected($sortField === 'sort_order')>{{ __('Display order') }}</option>
            </select>

            <button type="button" class="bm-dir" id="bm-dir-btn"
                    title="{{ $sortDir === 'asc' ? __('Ascending') : __('Descending') }}"
                    aria-label="{{ $sortDir === 'asc' ? __('Ascending') : __('Descending') }}">
                <i data-feather="{{ $sortDir === 'asc' ? 'arrow-up' : 'arrow-down' }}"></i>
            </button>

            @if($search !== '' || $filterCompanyId !== '')
                <a href="{{ route('owner.branches.index') }}" class="bm-clear">
                    <i data-feather="x"></i> {{ __('Clear') }}
                </a>
            @endif
        </div>
    </form>

    {{-- ═══════════ TABLE VIEW ═══════════ --}}
    <div id="bm-view-table" class="bm-reveal">
        <div class="bm-card">
            <div class="bm-table-scroll">
                <table class="bm-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ $sortUrl('name') }}" class="bm-sort {{ $sortField === 'name' ? 'is-active' : '' }}">
                                    {{ __('Branch') }}
                                    @if($ic = $sortCaret('name'))<i data-feather="{{ $ic }}" class="bm-sort-caret"></i>@endif
                                </a>
                            </th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="bm-center bm-col-count">{{ __('Services') }}</th>
                            <th class="bm-center bm-col-count">{{ __('Staff') }}</th>
                            <th class="bm-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            @php
                                $st  = $statusMeta[$branch->status] ?? $statusMeta['inactive'];
                                $svc = $branch->services->count();
                                $emp = $branch->employees->count();
                                $editUrl = route('owner.branches.edit', $branch);
                            @endphp
                            <tr class="bm-row-link" onclick="window.location='{{ $editUrl }}'">
                                {{-- Branch identity --}}
                                <td>
                                    <div class="bm-branch">
                                        <span class="bm-avatar" aria-hidden="true"><i data-feather="map-pin"></i></span>
                                        <div style="min-width:0;">
                                            <div class="bm-branch-name">
                                                {{ $branch->name_en ?: $branch->name_ar ?: '—' }}
                                                @if($branch->is_head_office)
                                                    <span class="bm-badge bm-badge-head"><i data-feather="star"></i>{{ __('HQ') }}</span>
                                                @endif
                                            </div>
                                            @if($branch->name_ar && $branch->name_en)
                                                <div class="bm-branch-ar" lang="ar" dir="rtl">{{ $branch->name_ar }}</div>
                                            @endif
                                            <div class="bm-branch-meta">
                                                @if($branch->phone)
                                                    <span class="bm-meta-line" dir="ltr"><i data-feather="phone"></i>{{ $branch->phone }}</span>
                                                @endif
                                                @if($branch->address)
                                                    <span class="bm-meta-line bm-col-address"><i data-feather="navigation"></i>{{ Str::limit($branch->address, 42) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Company --}}
                                <td>
                                    <span class="bm-chip"><i data-feather="briefcase"></i><span>{{ $branch->company?->localizedName() ?? '—' }}</span></span>
                                </td>

                                {{-- Status (read-only badge) --}}
                                <td>
                                    <span class="bm-badge {{ $st['cls'] }}"><i data-feather="{{ $st['icon'] }}"></i>{{ $st['label'] }}</span>
                                </td>

                                {{-- Services count --}}
                                <td class="bm-center bm-col-count">
                                    <span class="bm-count {{ $svc ? '' : 'is-zero' }}">{{ $svc }}</span>
                                </td>

                                {{-- Staff count --}}
                                <td class="bm-center bm-col-count">
                                    <span class="bm-count {{ $emp ? '' : 'is-zero' }}">{{ $emp }}</span>
                                </td>

                                {{-- Actions --}}
                                <td class="bm-end" onclick="event.stopPropagation()">
                                    <div class="bm-actions">
                                        <a href="{{ $editUrl }}" class="bm-act bm-act-primary" title="{{ __('Edit') }}" aria-label="{{ __('Edit branch') }}">
                                            <i data-feather="edit-2"></i>
                                        </a>
                                        <button type="button" class="bm-act dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport"
                                                title="{{ __('More') }}" aria-label="{{ __('More actions') }}" aria-expanded="false">
                                            <i data-feather="more-horizontal"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end bm-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('owner.branches.working-hours.create', $branch) }}">
                                                    <i data-feather="clock"></i>{{ __('Working hours') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('owner.branches.services.index', $branch) }}">
                                                    <i data-feather="scissors"></i>{{ __('Services') }}
                                                    @if($svc)<span class="bm-count bm-menu-count">{{ $svc }}</span>@endif
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('owner.branches.employees.index', $branch) }}">
                                                    <i data-feather="users"></i>{{ __('Employees') }}
                                                    @if($emp)<span class="bm-count bm-menu-count">{{ $emp }}</span>@endif
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('owner.branches.destroy', $branch) }}" method="post"
                                                      onsubmit="return confirm('{{ __('Delete this branch? This cannot be undone.') }}');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i data-feather="trash-2"></i>{{ __('Delete branch') }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="bm-empty">
                                        <span class="bm-empty-ic"><i data-feather="map-pin"></i></span>
                                        <p class="bm-empty-title">{{ __('No branches found') }}</p>
                                        <p class="bm-empty-sub">
                                            @if($search !== '' || $filterCompanyId !== '')
                                                {{ __('No branches match your search or filters.') }}
                                            @else
                                                {{ __('Add your first branch to start managing services, staff and hours.') }}
                                            @endif
                                        </p>
                                        @if($search !== '' || $filterCompanyId !== '')
                                            <a href="{{ route('owner.branches.index') }}" class="bm-btn bm-btn-ghost">{{ __('Clear filters') }}</a>
                                        @else
                                            <a href="{{ route('owner.branches.create') }}" class="bm-btn bm-btn-primary"><i data-feather="plus"></i>{{ __('Add branch') }}</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($branches->hasPages())
                <div class="bm-pagination">
                    <div class="bm-pagination-info">
                        {{ __('Showing :from–:to of :total', ['from' => $branches->firstItem(), 'to' => $branches->lastItem(), 'total' => $branches->total()]) }}
                    </div>
                    {{ $branches->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════ CARD VIEW ═══════════ --}}
    <div id="bm-view-card" style="display:none;">
        @if($branches->count())
            <div class="bm-grid">
                @foreach($branches as $branch)
                    @php
                        $bid = $branch->id;
                        $st  = $statusMeta[$branch->status] ?? $statusMeta['inactive'];
                        $svc = $branch->services->count();
                        $emp = $branch->employees->count();
                    @endphp
                    <article class="bm-bcard bm-reveal">
                        <div class="bm-bcard-head">
                            <span class="bm-avatar" aria-hidden="true"><i data-feather="map-pin"></i></span>
                            <div class="bm-bcard-id">
                                <div class="bm-branch-name">
                                    {{ $branch->localizedName() }}
                                    @if($branch->is_head_office)
                                        <span class="bm-badge bm-badge-head"><i data-feather="star"></i>{{ __('HQ') }}</span>
                                    @endif
                                </div>
                                <div class="bm-meta-line" style="margin-top:4px;"><i data-feather="briefcase"></i>{{ $branch->company?->localizedName() ?? '—' }}</div>
                                <div style="margin-top:8px;"><span class="bm-badge {{ $st['cls'] }}"><i data-feather="{{ $st['icon'] }}"></i>{{ $st['label'] }}</span></div>
                            </div>
                            <div class="bm-actions">
                                <a href="{{ route('owner.branches.edit', $branch) }}" class="bm-act bm-act-primary" title="{{ __('Edit') }}" aria-label="{{ __('Edit branch') }}">
                                    <i data-feather="edit-2"></i>
                                </a>
                                <button type="button" class="bm-act dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport"
                                        title="{{ __('More') }}" aria-label="{{ __('More actions') }}" aria-expanded="false">
                                    <i data-feather="more-horizontal"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end bm-menu">
                                    <li><a class="dropdown-item" href="{{ route('owner.branches.working-hours.create', $branch) }}"><i data-feather="clock"></i>{{ __('Working hours') }}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('owner.branches.destroy', $branch) }}" method="post" onsubmit="return confirm('{{ __('Delete this branch? This cannot be undone.') }}');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i data-feather="trash-2"></i>{{ __('Delete branch') }}</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="bm-bcard-tabs" role="tablist">
                            <button type="button" class="bm-tab is-active" data-tab="svc-{{ $bid }}"><i data-feather="scissors"></i>{{ __('Services') }}<span class="bm-tab-count">{{ $svc }}</span></button>
                            <button type="button" class="bm-tab" data-tab="emp-{{ $bid }}"><i data-feather="users"></i>{{ __('Staff') }}<span class="bm-tab-count">{{ $emp }}</span></button>
                            <button type="button" class="bm-tab" data-tab="hrs-{{ $bid }}"><i data-feather="clock"></i>{{ __('Hours') }}<span class="bm-tab-count">{{ $branch->workingHours->where('is_open', true)->count() }}</span></button>
                        </div>

                        {{-- Services --}}
                        <div class="bm-panel is-active" id="svc-{{ $bid }}">
                            @forelse($branch->services->take(4) as $s)
                                <div class="bm-mini">
                                    <span class="bm-mini-ic"><i data-feather="scissors"></i></span>
                                    <div class="bm-mini-body">
                                        <div class="bm-mini-name">{{ $s->localizedName() }}</div>
                                        @if($s->duration_minutes || $s->price)
                                            <div class="bm-mini-meta">
                                                @if($s->duration_minutes){{ $s->duration_minutes }} {{ __('min') }}@endif
                                                @if($s->duration_minutes && $s->price) · @endif
                                                @if($s->price){{ number_format($s->price, 0) }} {{ $currency }}@endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="bm-panel-empty"><i data-feather="scissors"></i><span>{{ __('No services yet') }}</span></div>
                            @endforelse
                            <div class="bm-panel-foot">
                                <a href="{{ route('owner.branches.services.index', $branch) }}" class="bm-panel-add"><i data-feather="{{ $svc ? 'arrow-right' : 'plus' }}"></i>{{ $svc > 4 ? __('View all :n services', ['n' => $svc]) : ($svc ? __('Manage services') : __('Add service')) }}</a>
                            </div>
                        </div>

                        {{-- Staff --}}
                        <div class="bm-panel" id="emp-{{ $bid }}">
                            @forelse($branch->employees->take(4) as $e)
                                <div class="bm-mini">
                                    <span class="bm-mini-ic">{{ mb_strtoupper(mb_substr($e->localizedName(), 0, 2)) }}</span>
                                    <div class="bm-mini-body"><div class="bm-mini-name">{{ $e->localizedName() }}</div></div>
                                </div>
                            @empty
                                <div class="bm-panel-empty"><i data-feather="users"></i><span>{{ __('No staff yet') }}</span></div>
                            @endforelse
                            <div class="bm-panel-foot">
                                <a href="{{ route('owner.branches.employees.index', $branch) }}" class="bm-panel-add"><i data-feather="{{ $emp ? 'arrow-right' : 'plus' }}"></i>{{ $emp > 4 ? __('View all :n staff', ['n' => $emp]) : ($emp ? __('Manage staff') : __('Add employee')) }}</a>
                            </div>
                        </div>

                        {{-- Hours --}}
                        <div class="bm-panel" id="hrs-{{ $bid }}">
                            @php $openCount = $branch->workingHours->where('is_open', true)->count(); @endphp
                            @if($openCount)
                                <div class="bm-hours">
                                    @foreach($dayLabels as $di => $dl)
                                        @php $shifts = $branch->workingHours->where('day_of_week', $di)->where('is_open', true)->sortBy('shift_number'); @endphp
                                        <div class="bm-hrow {{ $shifts->count() ? 'is-open' : '' }}">
                                            <span class="bm-hday">{{ $dl }}</span>
                                            @if($shifts->count())
                                                <span class="bm-htime">{{ $shifts->map(fn($w) => substr($w->open_time, 0, 5).'–'.substr($w->close_time, 0, 5))->implode(' / ') }}</span>
                                            @else
                                                <span class="bm-hclosed">{{ __('Closed') }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bm-panel-empty"><i data-feather="clock"></i><span>{{ __('No working hours set') }}</span></div>
                            @endif
                            <div class="bm-panel-foot">
                                <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="bm-panel-add"><i data-feather="{{ $openCount ? 'edit-2' : 'plus' }}"></i>{{ $openCount ? __('Edit hours') : __('Set hours') }}</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($branches->hasPages())
                <div class="bm-card" style="margin-top:16px;">
                    <div class="bm-pagination" style="border-top:none;">
                        <div class="bm-pagination-info">
                            {{ __('Showing :from–:to of :total', ['from' => $branches->firstItem(), 'to' => $branches->lastItem(), 'total' => $branches->total()]) }}
                        </div>
                        {{ $branches->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="bm-card">
                <div class="bm-empty">
                    <span class="bm-empty-ic"><i data-feather="map-pin"></i></span>
                    <p class="bm-empty-title">{{ __('No branches found') }}</p>
                    <p class="bm-empty-sub">{{ __('Add your first branch to start managing services, staff and hours.') }}</p>
                    <a href="{{ route('owner.branches.create') }}" class="bm-btn bm-btn-primary"><i data-feather="plus"></i>{{ __('Add branch') }}</a>
                </div>
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Sort direction toggle ── */
    var dirBtn = document.getElementById('bm-dir-btn');
    var dirInp = document.getElementById('bm-dir-input');
    if (dirBtn && dirInp) {
        dirBtn.addEventListener('click', function () {
            dirInp.value = dirInp.value === 'asc' ? 'desc' : 'asc';
            document.getElementById('bm-filter-form').submit();
        });
    }

    /* ── View toggle (table / card), persisted ── */
    var tableView = document.getElementById('bm-view-table');
    var cardView  = document.getElementById('bm-view-card');
    function setView(v) {
        var isCard = v === 'card';
        if (tableView) tableView.style.display = isCard ? 'none' : '';
        if (cardView)  cardView.style.display  = isCard ? '' : 'none';
        document.querySelectorAll('#bm-view-toggle .bm-vt').forEach(function (b) {
            b.classList.toggle('is-active', b.dataset.view === v);
        });
        if (typeof feather !== 'undefined') feather.replace();
    }
    var saved = 'table';
    try { saved = localStorage.getItem('bm_branch_view') || 'table'; } catch (e) {}
    setView(saved);
    document.querySelectorAll('#bm-view-toggle .bm-vt').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var v = this.dataset.view;
            try { localStorage.setItem('bm_branch_view', v); } catch (e) {}
            setView(v);
        });
    });

    /* ── Card tabs ── */
    document.querySelectorAll('.bm-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var card = this.closest('.bm-bcard');
            card.querySelectorAll('.bm-tab').forEach(function (t) { t.classList.remove('is-active'); });
            card.querySelectorAll('.bm-panel').forEach(function (p) { p.classList.remove('is-active'); });
            this.classList.add('is-active');
            var panel = document.getElementById(this.dataset.tab);
            if (panel) panel.classList.add('is-active');
        });
    });

    if (typeof feather !== 'undefined') setTimeout(function () { feather.replace(); }, 60);
})();
</script>
@endpush
@endsection
