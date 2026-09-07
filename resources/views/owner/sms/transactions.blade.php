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

    {{-- Rasel provider wallet ledger — REFERENCE ONLY. This is what Rasel billed
         the platform account (USD, by segment). It is NOT a GlowRez credit
         movement and never affects any company's balance. --}}
    @if($providerLedger['configured'] ?? false)
        <div class="sx-card sx-reveal" style="margin-top:22px;">
            <div class="sx-card-head">
                <div>
                    <h2 class="sx-card-title">{{ __('Rasel provider ledger') }}</h2>
                    <p class="sx-card-note">{{ __('What the provider actually charged the platform account. Separate from GlowRez credits.') }}</p>
                </div>
                <span class="sx-ref-tag"><i data-feather="eye"></i>{{ __('Reference only') }}</span>
            </div>

            @if(!($providerLedger['ok'] ?? false))
                <div class="sx-card-pad">
                    <div class="sx-note sx-note-danger">
                        <i data-feather="wifi-off"></i>
                        <span>{{ __('Could not reach Rasel right now. This does not affect GlowRez credits.') }}</span>
                    </div>
                </div>
            @elseif(empty($providerLedger['rows']))
                <div class="sx-card-pad">
                    <div class="sx-note sx-note-info"><i data-feather="info"></i><span>{{ __('No provider transactions yet.') }}</span></div>
                </div>
            @else
                @if(isset($providerLedger['balance']))
                    <div class="sx-card-pad" style="padding-bottom:0;">
                        <div class="sx-sub">{{ __('Provider wallet balance') }}: <strong class="sx-mono">{{ number_format((float) $providerLedger['balance'], 2) }}</strong></div>
                    </div>
                @endif
                <div class="sx-table-scroll">
                    <table class="sx-table">
                        <thead><tr>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="num">{{ __('Segments') }}</th>
                            <th class="num">{{ __('Amount') }}</th>
                            <th class="num">{{ __('Balance after') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($providerLedger['rows'] as $r)
                            <tr>
                                <td><span class="sx-name">{{ $r['description'] ?? '—' }}</span>@if($r['source'])<div class="sx-sub">{{ $r['source'] }}</div>@endif</td>
                                <td><span class="sx-pill {{ ($r['type'] ?? '') === 'debit' ? 'sx-pill-skipped' : 'sx-pill-sent' }}">{{ $r['type'] ?? '—' }}</span></td>
                                <td class="num sx-mono">{{ $r['message_count'] !== null ? number_format((int) $r['message_count']) : '—' }}</td>
                                <td class="num sx-mono">{{ $r['amount'] !== null ? number_format((float) $r['amount'], 2) : '—' }}</td>
                                <td class="num sx-mono">{{ $r['balance_after'] !== null ? number_format((float) $r['balance_after'], 2) : '—' }}</td>
                                <td class="sx-sub">{{ $r['created_at'] ? \Illuminate\Support\Carbon::parse($r['created_at'])->translatedFormat('d M Y H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@endsection
