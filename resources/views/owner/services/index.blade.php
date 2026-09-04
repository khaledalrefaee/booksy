@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

<div class="page-content bm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a>
                <span aria-hidden="true">·</span> {{ __('Services') }}
            </div>
            <h1 class="bm-title">{{ __('Services') }}</h1>
            <p class="bm-subtitle" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="bm-chip"><i data-feather="map-pin"></i><span>{{ $branch->localizedName() }}</span></span>
                @if($branch->company)<span class="bm-chip"><i data-feather="briefcase"></i><span>{{ $branch->company->localizedName() }}</span></span>@endif
            </p>
        </div>
        <div class="bm-head-actions">
            <a href="{{ route('owner.branches.index') }}" class="bm-btn bm-btn-ghost"><i data-feather="grid"></i>{{ __('All branches') }}</a>
            <a href="{{ route('owner.branches.services.create', $branch) }}" class="bm-btn bm-btn-primary"><i data-feather="plus"></i>{{ __('Add service') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="GET" action="{{ route('owner.branches.services.index', $branch) }}" class="bm-toolbar bm-reveal" id="bm-filter-form">
        <input type="hidden" name="dir" value="{{ $sortDir }}" id="bm-dir-input">
        <div class="bm-toolbar-row">
            <div class="bm-search">
                <button type="submit" class="bm-search-btn" aria-label="{{ __('Search') }}" tabindex="-1"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('Search services…') }}" autocomplete="off"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}">
            </div>
            <select name="service_category_id" class="bm-select" onchange="document.getElementById('bm-filter-form').submit()">
                <option value="">{{ __('All categories') }}</option>
                @foreach($serviceCategories as $c)
                    <option value="{{ $c->id }}" @selected((string) $filterServiceCatId === (string) $c->id)>{{ $c->localizedName() }}</option>
                @endforeach
            </select>
            <select name="is_active" class="bm-select" onchange="document.getElementById('bm-filter-form').submit()">
                <option value="">{{ __('All statuses') }}</option>
                <option value="1" @selected($filterIsActive === '1')>{{ __('Active') }}</option>
                <option value="0" @selected($filterIsActive === '0')>{{ __('Inactive') }}</option>
            </select>
            <select name="sort" class="bm-select" onchange="document.getElementById('bm-filter-form').submit()">
                <option value="name"             @selected($sortField === 'name')>{{ __('Name') }}</option>
                <option value="price"            @selected($sortField === 'price')>{{ __('Price') }}</option>
                <option value="duration_minutes" @selected($sortField === 'duration_minutes')>{{ __('Duration') }}</option>
                <option value="created_at"       @selected($sortField === 'created_at')>{{ __('Newest') }}</option>
            </select>
            <button type="button" class="bm-dir" id="bm-dir-btn" title="{{ $sortDir === 'asc' ? __('Ascending') : __('Descending') }}">
                <i data-feather="{{ $sortDir === 'asc' ? 'arrow-up' : 'arrow-down' }}"></i>
            </button>
            @if($q !== '' || $filterServiceCatId !== '' || $filterIsActive !== '')
                <a href="{{ route('owner.branches.services.index', $branch) }}" class="bm-clear"><i data-feather="x"></i> {{ __('Clear') }}</a>
            @endif
        </div>
    </form>

    <div class="bm-card bm-reveal">
        <div class="bm-table-scroll">
            <table class="bm-table" id="dt-services">
                <thead>
                    <tr>
                        <th>{{ __('Service') }}</th>
                        <th>{{ __('Service category') }}</th>
                        <th class="bm-center">{{ __('Duration') }}</th>
                        <th class="bm-end">{{ __('Price') }}</th>
                        <th class="bm-center">{{ __('Active') }}</th>
                        <th class="bm-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $cur = config('booksy.currencies')[$service->currency]['symbol'] ?? $service->currency;
                        @endphp
                        <tr>
                            <td>
                                <div class="bm-branch">
                                    <div class="bm-avatar" style="overflow:hidden;">
                                        @if($service->image_path)
                                            <img src="{{ asset('storage/'.$service->image_path) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                        @else
                                            <i data-feather="scissors"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="bm-branch-name">
                                            {{ $service->name_en ?: $service->name_ar ?: '—' }}
                                            @if($service->is_popular)<span class="bm-badge bm-badge-head"><i data-feather="trending-up"></i>{{ __('Popular') }}</span>@endif
                                            @if($service->is_recommended)<span class="bm-badge bm-badge-head"><i data-feather="star"></i>{{ __('Recommended') }}</span>@endif
                                        </div>
                                        @if($service->name_ar && $service->name_en)<div class="bm-branch-ar" dir="rtl" lang="ar">{{ $service->name_ar }}</div>@endif
                                        <div class="bm-branch-meta">
                                            @if($service->is_bookable_online)
                                                <span class="bm-meta-line"><i data-feather="globe"></i>{{ __('Online booking') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($service->serviceCategory)
                                    <span class="bm-chip"><i data-feather="folder"></i><span>{{ $service->serviceCategory->localizedName() }}</span></span>
                                @else
                                    <span class="bm-dash">—</span>
                                @endif
                            </td>
                            <td class="bm-center"><span class="bm-count"><i data-feather="clock"></i>{{ $service->duration_minutes }} {{ __('min') }}</span></td>
                            <td class="bm-end" style="white-space:nowrap;">
                                @if($service->price_type === 'from')<span class="bm-branch-ar">{{ __('from') }} </span>@endif
                                <span style="font-weight:600;">{{ number_format((float) $service->price, 2) }} {{ $cur }}</span>
                                @if($service->price_type === 'range' && $service->price_to)
                                    <span class="bm-branch-ar"> – {{ number_format((float) $service->price_to, 2) }}</span>
                                @endif
                                @if($service->discount_type && $service->discount_value)
                                    <div><span class="bm-badge bm-badge-maintenance"><i data-feather="percent"></i>{{ $service->discount_type === 'percent' ? '-'.rtrim(rtrim(number_format((float)$service->discount_value,2),'0'),'.').'%' : __('Discount') }}</span></div>
                                @endif
                            </td>
                            <td class="bm-center">
                                <form method="post" action="{{ route('owner.services.toggle-active', $service) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-check form-switch mb-0 d-inline-flex">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="service-active-{{ $service->id }}" onchange="this.form.submit()"
                                               @checked($service->is_active) aria-label="{{ __('Toggle active') }}">
                                    </div>
                                </form>
                            </td>
                            <td class="bm-end">
                                <div class="bm-actions">
                                    <a href="{{ route('owner.services.edit', $service) }}" class="bm-act bm-act-primary" title="{{ __('Edit') }}"><i data-feather="edit-2"></i></a>
                                    <form action="{{ route('owner.services.destroy', $service) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this service?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bm-act bm-act-danger" title="{{ __('Delete') }}"><i data-feather="trash-2"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="bm-empty">
                                    <div class="bm-empty-ic"><i data-feather="scissors"></i></div>
                                    <h3 class="bm-empty-title">{{ __('No services for this branch.') }}</h3>
                                    <a href="{{ route('owner.branches.services.create', $branch) }}" class="bm-btn bm-btn-primary"><i data-feather="plus"></i>{{ __('Add service') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
            <div class="bm-pagination">
                <span class="bm-pagination-info">{{ __('Showing') }} {{ $services->firstItem() }}–{{ $services->lastItem() }} {{ __('of') }} {{ $services->total() }}</span>
                {{ $services->links() }}
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
