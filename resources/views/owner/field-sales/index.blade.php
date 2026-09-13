@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

@php
    $bandMeta = [
        'high'    => ['label' => __('High'),    'cls' => 'bm-badge-active',      'icon' => 'shield'],
        'medium'  => ['label' => __('Medium'),  'cls' => 'bm-badge-maintenance', 'icon' => 'alert-circle'],
        'low'     => ['label' => __('Low'),     'cls' => 'bm-badge-inactive',    'icon' => 'alert-triangle'],
        'unknown' => ['label' => __('Unknown'), 'cls' => 'bm-badge-inactive',    'icon' => 'help-circle'],
    ];
    $reviewMeta = [
        'pending'  => ['label' => __('Pending'),  'cls' => 'bm-badge-maintenance'],
        'approved' => ['label' => __('Approved'), 'cls' => 'bm-badge-active'],
        'rejected' => ['label' => __('Rejected'), 'cls' => 'bm-badge-inactive'],
    ];
    $qs = array_filter(['q'=>$filters['q'],'rep'=>$filters['rep'],'flagged'=>$filters['flagged'],'review'=>$filters['review'],'date_from'=>$filters['date_from'],'date_to'=>$filters['date_to']]);
@endphp

<div class="page-content bm-wrap">
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a><span aria-hidden="true">·</span> {{ __('Platform') }}</div>
            <h1 class="bm-title">{{ __('Field sales') }}</h1>
            <p class="bm-subtitle">{{ __('Every visit your reps log in the field, with a silent confidence check so you can trust the numbers.') }}</p>
        </div>
        <div class="bm-head-actions">
            <a href="{{ route('owner.field-sales.export', $qs) }}" class="bm-btn bm-btn-primary"><i data-feather="download"></i>{{ __('Export') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <section class="bm-stats bm-reveal">
        <div class="bm-stat" style="--accent:var(--bk-accent);"><span class="bm-stat-label"><i data-feather="map-pin"></i>{{ __('Total visits') }}</span><span class="bm-stat-value">{{ number_format($stats['total']) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-info);"><span class="bm-stat-label"><i data-feather="sun"></i>{{ __('Today') }}</span><span class="bm-stat-value">{{ number_format($stats['today']) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-warning);"><span class="bm-stat-label"><i data-feather="flag"></i>{{ __('Flagged') }}</span><span class="bm-stat-value">{{ number_format($stats['flagged']) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-danger);"><span class="bm-stat-label"><i data-feather="clock"></i>{{ __('Pending review') }}</span><span class="bm-stat-value">{{ number_format($stats['pending']) }}</span></div>
    </section>

    {{-- Tabs --}}
    <div class="bm-viewtoggle bm-reveal" style="margin-bottom:16px;">
        <a href="{{ route('owner.field-sales.index') }}" class="bm-vt {{ $mode === 'all' ? 'is-active' : '' }}" style="width:auto;padding:0 14px;gap:6px;"><i data-feather="list"></i>{{ __('All visits') }}</a>
        <a href="{{ route('owner.field-sales.review') }}" class="bm-vt {{ $mode === 'review' ? 'is-active' : '' }}" style="width:auto;padding:0 14px;gap:6px;"><i data-feather="check-square"></i>{{ __('Review queue') }}@if($stats['pending'] || $stats['flagged'])<span class="bm-count">{{ $stats['pending'] + $stats['flagged'] }}</span>@endif</a>
        <a href="{{ route('owner.field-sales.map', $qs) }}" class="bm-vt" style="width:auto;padding:0 14px;gap:6px;"><i data-feather="map"></i>{{ __('Map') }}</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ url()->current() }}" class="bm-toolbar bm-reveal" id="fs-filter">
        <div class="bm-toolbar-row">
            <div class="bm-search">
                <button type="submit" class="bm-search-btn" tabindex="-1"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Search place, area or contact…') }}" autocomplete="off">
            </div>
            <select name="rep" class="bm-select" onchange="document.getElementById('fs-filter').submit()">
                <option value="">{{ __('All reps') }}</option>
                @foreach($reps as $r)<option value="{{ $r->id }}" @selected((string)$filters['rep'] === (string)$r->id)>{{ $r->name }}</option>@endforeach
            </select>
            <select name="review" class="bm-select" onchange="document.getElementById('fs-filter').submit()">
                <option value="">{{ __('Any review status') }}</option>
                <option value="pending" @selected($filters['review']==='pending')>{{ __('Pending') }}</option>
                <option value="approved" @selected($filters['review']==='approved')>{{ __('Approved') }}</option>
                <option value="rejected" @selected($filters['review']==='rejected')>{{ __('Rejected') }}</option>
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="bm-select" onchange="document.getElementById('fs-filter').submit()">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="bm-select" onchange="document.getElementById('fs-filter').submit()">
            @if($qs)<a href="{{ url()->current() }}" class="bm-clear"><i data-feather="x"></i>{{ __('Clear') }}</a>@endif
        </div>
    </form>

    <div class="bm-card bm-reveal">
        <div class="bm-table-scroll">
            <table class="bm-table">
                <thead>
                    <tr>
                        <th>{{ __('Place') }}</th>
                        <th>{{ __('Rep') }}</th>
                        <th class="bm-col-address">{{ __('When') }}</th>
                        <th class="bm-center">{{ __('Confidence') }}</th>
                        <th class="bm-center">{{ __('Review') }}</th>
                        <th class="bm-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $v)
                        @php $b = $bandMeta[$v->confidenceBand()]; $rv = $reviewMeta[$v->review_status] ?? $reviewMeta['pending']; @endphp
                        <tr class="bm-row-link" onclick="window.location='{{ route('owner.field-sales.show', $v) }}'">
                            <td>
                                <div class="bm-branch">
                                    <span class="bm-avatar" aria-hidden="true"><i data-feather="map-pin"></i></span>
                                    <div style="min-width:0;">
                                        <div class="bm-branch-name">{{ $v->place_name }} @if($v->flagged)<span class="bm-badge bm-badge-maintenance"><i data-feather="flag"></i>{{ __('Flagged') }}</span>@endif</div>
                                        <div class="bm-branch-meta">@if($v->area)<span class="bm-meta-line"><i data-feather="navigation"></i>{{ $v->area }}</span>@endif @if($v->contact_name)<span class="bm-meta-line"><i data-feather="user"></i>{{ $v->contact_name }}</span>@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="bm-chip"><i data-feather="user"></i><span>{{ $v->owner?->name ?? '—' }}</span></span></td>
                            <td class="bm-col-address"><span class="bm-meta-line">{{ $v->visited_at?->translatedFormat('M j, H:i') }}</span></td>
                            <td class="bm-center"><span class="bm-badge {{ $b['cls'] }}"><i data-feather="{{ $b['icon'] }}"></i>{{ $b['label'] }}@if($v->confidence_score !== null) · {{ $v->confidence_score }}@endif</span></td>
                            <td class="bm-center"><span class="bm-badge {{ $rv['cls'] }}">{{ $rv['label'] }}</span></td>
                            <td class="bm-end" onclick="event.stopPropagation()">
                                <a href="{{ route('owner.field-sales.show', $v) }}" class="bm-act bm-act-primary" title="{{ __('View') }}"><i data-feather="eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="bm-empty"><span class="bm-empty-ic"><i data-feather="map"></i></span><p class="bm-empty-title">{{ $mode === 'review' ? __('Nothing to review') : __('No visits yet') }}</p><p class="bm-empty-sub">{{ $mode === 'review' ? __('Flagged or pending visits will show up here.') : __('Visits logged by your field reps will appear here.') }}</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->hasPages())
            <div class="bm-pagination"><div class="bm-pagination-info">{{ __('Showing :from–:to of :total', ['from'=>$visits->firstItem(),'to'=>$visits->lastItem(),'total'=>$visits->total()]) }}</div>{{ $visits->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>

@push('scripts')<script>if(typeof feather!=='undefined')setTimeout(function(){feather.replace();},60);</script>@endpush
@endsection
