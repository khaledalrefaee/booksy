@extends('owner.dashboard')
@section('content')

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Ledger') }}
            </div>
            <h1 class="sx-title">{{ __('Transactions') }}</h1>
            <p class="sx-subtitle">{{ __('Every credit movement across the platform — grants, purchases, consumption, refunds and expiries.') }}</p>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="GET" class="sx-toolbar sx-reveal">
        @php $txTypeLabels = ['grant' => __('Grant'), 'purchase' => __('Purchase'), 'consume' => __('Consume'), 'refund' => __('Refund'), 'expire' => __('Expire'), 'adjustment' => __('Adjustment')]; @endphp
        <select name="type" class="sx-select" onchange="this.form.submit()">
            <option value="">{{ __('All types') }}</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $txTypeLabels[$t] ?? ucfirst($t) }}</option>
            @endforeach
        </select>
        <select name="company" class="sx-select" onchange="this.form.submit()">
            <option value="">{{ __('All companies') }}</option>
            @foreach($companies as $co)
                <option value="{{ $co->id }}" @selected((string) $companyId === (string) $co->id)>{{ $co->localizedName() }}</option>
            @endforeach
        </select>
        @if($type !== '' || $companyId !== '')
            <a href="{{ route('owner.sms.transactions') }}" class="sx-btn sx-btn-ghost sx-btn-sm"><i data-feather="x"></i>{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="sx-card sx-reveal">
        @if($tx->isEmpty())
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="repeat"></i></span>
                <h3 class="sx-empty-title">{{ __('No transactions') }}</h3>
                <p class="sx-empty-text">{{ __('The credit ledger is empty for this filter.') }}</p>
            </div>
        @else
            <div class="sx-table-scroll">
                <table class="sx-table">
                    <thead><tr>
                        <th>{{ __('Type & scope') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="num">{{ __('Credits') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($tx as $t)
                        @include('owner.sms.partials.tx-row', ['t' => $t, 'compact' => false])
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sx-pagination">
                <span class="sx-pagination-info">{{ __('Showing :from–:to of :total', ['from' => $tx->firstItem(), 'to' => $tx->lastItem(), 'total' => $tx->total()]) }}</span>
                {{ $tx->links() }}
            </div>
        @endif
    </div>
</div>

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@endsection
