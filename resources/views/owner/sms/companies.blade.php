@extends('owner.dashboard')
@section('content')

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Usage') }}
            </div>
            <h1 class="sx-title">{{ __('Companies usage') }}</h1>
            <p class="sx-subtitle">{{ __('Credits allocated, used and remaining for every company — all from the GlowRez ledger.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('owner.sms.branches') }}" class="sx-btn sx-btn-ghost"><i data-feather="map-pin"></i>{{ __('By branch') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="GET" class="sx-toolbar sx-reveal">
        <div class="sx-search">
            <i data-feather="search"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search companies…') }}">
        </div>
        @if($search !== '')
            <a href="{{ route('owner.sms.companies') }}" class="sx-btn sx-btn-ghost sx-btn-sm"><i data-feather="x"></i>{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="sx-card sx-reveal">
        @if($companies->isEmpty())
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="briefcase"></i></span>
                <h3 class="sx-empty-title">{{ $search !== '' ? __('No companies match') : __('No companies yet') }}</h3>
                <p class="sx-empty-text">{{ __('Companies with SMS activity will show their allocation and consumption here.') }}</p>
            </div>
        @else
            <div class="sx-table-scroll">
                <table class="sx-table">
                    <thead><tr>
                        <th>{{ __('Company') }}</th>
                        <th class="num">{{ __('Allocated') }}</th>
                        <th class="num">{{ __('Used') }}</th>
                        <th class="num">{{ __('Remaining') }}</th>
                        <th>{{ __('Consumption') }}</th>
                        <th class="num">{{ __('Sent') }}</th>
                        <th class="num">{{ __('Grant') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($companies as $c)
                        @php
                            $alloc = (int) $c->allocated; $used = (int) $c->used;
                            $pct = $alloc > 0 ? min(100, round($used / $alloc * 100)) : 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="sx-idcell">
                                    <span class="sx-ava">{{ mb_substr($c->localizedName() ?: 'G', 0, 1) }}</span>
                                    <div class="sx-name">{{ $c->localizedName() }}</div>
                                </div>
                            </td>
                            <td class="num sx-mono">{{ number_format($alloc) }}</td>
                            <td class="num sx-mono">{{ number_format($used) }}</td>
                            <td class="num sx-mono"><strong>{{ number_format((int) $c->remaining) }}</strong></td>
                            <td style="min-width:130px;">
                                <div class="sx-ubar"><span style="width:{{ $pct }}%"></span></div>
                                <div class="sx-sub sx-mono">{{ $pct }}%</div>
                            </td>
                            <td class="num sx-mono">{{ number_format((int) $c->sent_count) }}</td>
                            <td class="num">
                                <button type="button" class="sx-btn sx-btn-gold sx-btn-sm sx-grant-trigger"
                                        data-company="{{ $c->id }}" data-company-name="{{ $c->localizedName() }}"
                                        data-bs-toggle="modal" data-bs-target="#sxGrantModal">
                                    <i data-feather="plus"></i>{{ __('Add') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sx-pagination">
                <span class="sx-pagination-info">{{ __('Showing :from–:to of :total', ['from' => $companies->firstItem(), 'to' => $companies->lastItem(), 'total' => $companies->total()]) }}</span>
                {{ $companies->links() }}
            </div>
        @endif
    </div>
</div>

@include('owner.sms.partials.grant-modal')

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@include('owner.sms.partials.grant-script')
@push('scripts')
<script>
(function () {
    // Preselect the company when "Add" is clicked from a table row.
    document.querySelectorAll('.sx-grant-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var sel = document.getElementById('sxGrantCompany');
            if (sel) { sel.value = btn.getAttribute('data-company'); sel.dispatchEvent(new Event('change')); }
        });
    });
})();
</script>
@endpush

@endsection
