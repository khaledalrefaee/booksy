{{--
    Period bar shared by the report pages: step to the previous / next month,
    or jump to any month + year (+ branch when no branch context is active).
    Expects $month, $year, $branches, $branchId and the current route name.
--}}
@php
    $cur   = \Carbon\Carbon::create($year, $month, 1);
    $route = request()->route()->getName();
    $keep  = array_filter(['branch_id' => $branchId ?? null], fn ($v) => filled($v));
    $prev  = $cur->copy()->subMonth();
    $next  = $cur->copy()->addMonth();
@endphp

<div class="rp-bar">
    <div class="rp-step">
        <a href="{{ route($route, $keep + ['month' => $prev->month, 'year' => $prev->year]) }}"
           title="{{ __('Previous month') }}" aria-label="{{ __('Previous month') }}"><i data-feather="chevron-left" aria-hidden="true"></i></a>
        <span class="rp-period" aria-live="polite">{{ $cur->translatedFormat('F Y') }}</span>
        <a href="{{ route($route, $keep + ['month' => $next->month, 'year' => $next->year]) }}"
           title="{{ __('Next month') }}" aria-label="{{ __('Next month') }}"><i data-feather="chevron-right" aria-hidden="true"></i></a>
    </div>

    <form method="GET" class="rp-form" data-filter-sheet="{{ __('Filters') }}">
        <select name="month" class="rp-select" aria-label="{{ __('Month') }}">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
            @endfor
        </select>
        <input type="number" name="year" value="{{ $year }}" class="rp-year" aria-label="{{ __('Year') }}" min="2000" max="2100">
        @if(! ($branchContext ?? null))
            <select name="branch_id" class="rp-select" aria-label="{{ __('Branch') }}">
                <option value="">{{ __('All Branches') }}</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->localizedName() }}</option>
                @endforeach
            </select>
        @endif
        <button class="ivh-btn ivh-btn-primary">{{ __('View') }}</button>
    </form>
</div>
