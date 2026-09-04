@extends('owner.dashboard')
@section('content')

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Usage') }}
            </div>
            <h1 class="sx-title">{{ __('Branches usage') }}</h1>
            <p class="sx-subtitle">{{ __('Per-branch SMS credits and consumption. Branches without their own allocation draw from the company pool.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('owner.sms.companies') }}" class="sx-btn sx-btn-ghost"><i data-feather="briefcase"></i>{{ __('By company') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="GET" class="sx-toolbar sx-reveal">
        <div class="sx-search">
            <i data-feather="search"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search branches…') }}">
        </div>
        @if($search !== '')
            <a href="{{ route('owner.sms.branches') }}" class="sx-btn sx-btn-ghost sx-btn-sm"><i data-feather="x"></i>{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="sx-card sx-reveal">
        @if($branches->isEmpty())
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="map-pin"></i></span>
                <h3 class="sx-empty-title">{{ $search !== '' ? __('No branches match') : __('No branches yet') }}</h3>
                <p class="sx-empty-text">{{ __('Once branches have SMS allocations or activity, they appear here.') }}</p>
            </div>
        @else
            <div class="sx-table-scroll">
                <table class="sx-table">
                    <thead><tr>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Company') }}</th>
                        <th class="num">{{ __('Allocated') }}</th>
                        <th class="num">{{ __('Used') }}</th>
                        <th class="num">{{ __('Remaining') }}</th>
                        <th class="num">{{ __('Sent') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($branches as $b)
                        @php $rem = (int) $b->remaining; @endphp
                        <tr>
                            <td>
                                <div class="sx-idcell">
                                    <span class="sx-ava">{{ mb_substr($b->localizedName() ?: 'B', 0, 1) }}</span>
                                    <div class="sx-name">{{ $b->localizedName() }}</div>
                                </div>
                            </td>
                            <td class="sx-sub">{{ $b->company?->localizedName() }}</td>
                            <td class="num sx-mono">{{ number_format((int) $b->allocated) }}</td>
                            <td class="num sx-mono">{{ number_format((int) $b->used) }}</td>
                            <td class="num">
                                @if($rem <= 0)
                                    <span class="sx-pill sx-pill-failed">{{ __('Empty') }}</span>
                                @else
                                    <strong class="sx-mono">{{ number_format($rem) }}</strong>
                                @endif
                            </td>
                            <td class="num sx-mono">{{ number_format((int) $b->sent_count) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sx-pagination">
                <span class="sx-pagination-info">{{ __('Showing :from–:to of :total', ['from' => $branches->firstItem(), 'to' => $branches->lastItem(), 'total' => $branches->total()]) }}</span>
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</div>

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@endsection
