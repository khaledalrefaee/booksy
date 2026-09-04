@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

@push('owner-styles')
<style>
/* Employee identity cell (built on bm-*) */
.bm-emp-avatar { width:46px; height:46px; border-radius:13px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:16px; color:#fff; overflow:hidden; }
.bm-emp-avatar img { width:100%; height:100%; object-fit:cover; }
.bm-status { display:inline-flex; align-items:center; gap:5px; font-size:.75rem; font-weight:600; }
.bm-status .dot { width:7px; height:7px; border-radius:50%; display:inline-block; }
</style>
@endpush

<div class="page-content bm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a>
                <span aria-hidden="true">·</span> {{ __('Employees') }}
            </div>
            <h1 class="bm-title">{{ __('Employees') }}</h1>
            <p class="bm-subtitle" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="bm-chip"><i data-feather="users"></i><span>{{ $employees->total() }} {{ __('team member(s)') }}</span></span>
                <span class="bm-chip"><i data-feather="map-pin"></i><span>{{ $branch->localizedName() }}</span></span>
                @if($branch->company)<span class="bm-chip"><i data-feather="briefcase"></i><span>{{ $branch->company->localizedName() }}</span></span>@endif
            </p>
        </div>
        <div class="bm-head-actions">
            <a href="{{ route('owner.employee-leaves.index') }}" class="bm-btn bm-btn-gold"><i data-feather="calendar"></i>{{ __('Leaves') }}</a>
            <a href="{{ route('owner.branches.employees.create', $branch) }}" class="bm-btn bm-btn-primary"><i data-feather="plus"></i>{{ __('Add Employee') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="GET" action="{{ route('owner.branches.employees.index', $branch) }}" class="bm-toolbar bm-reveal" id="bm-filter-form">
        <input type="hidden" name="dir" value="{{ $sortDir }}" id="bm-dir-input">
        <div class="bm-toolbar-row">
            <div class="bm-search">
                <button type="submit" class="bm-search-btn" aria-label="{{ __('Search') }}" tabindex="-1"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('Search by name, email or phone…') }}" autocomplete="off"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}">
            </div>
            <select name="is_active" class="bm-select" onchange="document.getElementById('bm-filter-form').submit()">
                <option value="">{{ __('All statuses') }}</option>
                <option value="1" @selected($isActive === '1')>{{ __('Active') }}</option>
                <option value="0" @selected($isActive === '0')>{{ __('Inactive') }}</option>
            </select>
            <select name="sort" class="bm-select" onchange="document.getElementById('bm-filter-form').submit()">
                <option value="name"       @selected($sortField === 'name')>{{ __('Name') }}</option>
                <option value="created_at" @selected($sortField === 'created_at')>{{ __('Newest') }}</option>
            </select>
            <button type="button" class="bm-dir" id="bm-dir-btn" title="{{ $sortDir === 'asc' ? __('Ascending') : __('Descending') }}">
                <i data-feather="{{ $sortDir === 'asc' ? 'arrow-up' : 'arrow-down' }}"></i>
            </button>
            @if($q !== '' || $isActive !== '')
                <a href="{{ route('owner.branches.employees.index', $branch) }}" class="bm-clear"><i data-feather="x"></i> {{ __('Clear') }}</a>
            @endif
        </div>
    </form>

    <div class="bm-card bm-reveal">
        <div class="bm-table-scroll">
            <table class="bm-table">
                <thead>
                    <tr>
                        <th>{{ __('Employee') }}</th>
                        <th class="bm-col-phone">{{ __('Contact') }}</th>
                        <th class="bm-center">{{ __('Status') }}</th>
                        <th class="bm-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        @php
                            $palette = ['#5C7038','#a07d10','#4facfe','#43a047','#c2185b','#6d4c41'];
                            $bg = $palette[$emp->id % count($palette)];
                            $initial = strtoupper(mb_substr($emp->name_en ?? $emp->name_ar ?? '?', 0, 1));
                            $name = app()->getLocale()==='ar' ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar);
                        @endphp
                        <tr>
                            <td>
                                <div class="bm-branch">
                                    <div class="bm-emp-avatar" style="background:linear-gradient(135deg,{{ $bg }}cc,{{ $bg }});">
                                        @if($emp->image)
                                            <img src="{{ asset('storage/'.$emp->image) }}" alt="">
                                        @else
                                            {{ $initial }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="bm-branch-name">{{ $name ?: '—' }}</div>
                                        @if($emp->name_ar && $emp->name_en)<div class="bm-branch-ar" dir="rtl" lang="ar">{{ $emp->name_ar }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="bm-col-phone">
                                <div class="bm-branch-meta">
                                    @if($emp->email)<span class="bm-meta-line"><i data-feather="mail"></i>{{ $emp->email }}</span>@endif
                                    @if($emp->phone)<span class="bm-meta-line"><i data-feather="phone"></i>{{ $emp->phone }}</span>@endif
                                    @if(!$emp->email && !$emp->phone)<span class="bm-dash">—</span>@endif
                                </div>
                            </td>
                            <td class="bm-center">
                                @if($emp->is_active)
                                    <span class="bm-badge bm-badge-active"><i data-feather="check-circle"></i>{{ __('Active') }}</span>
                                @else
                                    <span class="bm-badge bm-badge-inactive"><i data-feather="slash"></i>{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="bm-end">
                                <div class="bm-actions">
                                    <a href="{{ route('owner.employee-leaves.create', $emp) }}" class="bm-act" title="{{ __('Leave') }}"><i data-feather="calendar"></i></a>
                                    <a href="{{ route('owner.employees.edit', $emp) }}" class="bm-act bm-act-primary" title="{{ __('Edit') }}"><i data-feather="edit-2"></i></a>
                                    <form action="{{ route('owner.employees.destroy', $emp) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this employee?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bm-act bm-act-danger" title="{{ __('Delete') }}"><i data-feather="trash-2"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="bm-empty">
                                    <div class="bm-empty-ic"><i data-feather="users"></i></div>
                                    <h3 class="bm-empty-title">{{ __('No employees for this branch.') }}</h3>
                                    <a href="{{ route('owner.branches.employees.create', $branch) }}" class="bm-btn bm-btn-primary"><i data-feather="plus"></i>{{ __('Add Employee') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="bm-pagination">
                <span class="bm-pagination-info">{{ __('Showing') }} {{ $employees->firstItem() }}–{{ $employees->lastItem() }} {{ __('of') }} {{ $employees->total() }}</span>
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.feather) window.feather.replace();
    var dirBtn = document.getElementById('bm-dir-btn');
    var dirInp = document.getElementById('bm-dir-input');
    var form   = document.getElementById('bm-filter-form');
    if (dirBtn && dirInp && form) {
        dirBtn.addEventListener('click', function () {
            dirInp.value = dirInp.value === 'asc' ? 'desc' : 'asc';
            form.submit();
        });
    }
});
</script>
@endpush
@endsection
