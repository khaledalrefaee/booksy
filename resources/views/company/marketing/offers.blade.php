@extends('company.dashboard')

@push('company-styles')
<style>
    .mk-fi { width:14px; height:14px; vertical-align:-2px; }
    .mk-badge { font-size:10px; font-weight:700; line-height:1; padding:4px 9px; border-radius:8px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
    .mk-badge-active   { background:rgba(16,185,129,.12);  color:#059669; }
    .mk-badge-sched    { background:rgba(59,130,246,.12);  color:#2563eb; }
    .mk-badge-expired  { background:rgba(100,116,139,.12); color:#64748b; }
    .mk-disc { font-size:11px; font-weight:700; color:#ea580c; background:rgba(234,88,12,.10); padding:3px 8px; border-radius:8px; white-space:nowrap; }
    .mk-old  { text-decoration:line-through; color:#94a3b8; font-size:12px; }
    .mk-new  { font-weight:700; }
    .mk-sec-title { font-size:13px; font-weight:700; }
    .mk-empty { padding:48px 20px; text-align:center; }
    .mk-empty i { width:34px; height:34px; color:#cbd5e1; }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Offers') }}</h4>
            <p class="text-muted mb-0 tx-13">{{ __('Services you have put on discount. Manage a discount from the service itself.') }}</p>
        </div>
        @if($branches->isNotEmpty())
        <a href="{{ route('company.branches.services.index', $branches->first()) }}" class="btn btn-primary rounded-pill px-3">
            <i data-feather="plus" style="width:14px;height:14px;margin-inline-end:4px;"></i>
            {{ __('Add offer from a service') }}
        </a>
        @endif
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3 bk-a1">
            <div class="bk-stat" data-accent="green">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-green"><i data-feather="tag"></i></div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Active offers') }}</div>
                        <div class="bk-stat-sub">{{ __('running now') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($stats['active']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 bk-a2">
            <div class="bk-stat" data-accent="blue">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-blue"><i data-feather="clock"></i></div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Scheduled') }}</div>
                        <div class="bk-stat-sub">{{ __('starts later') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($stats['scheduled']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 bk-a3">
            <div class="bk-stat" data-accent="gold">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-gold"><i data-feather="archive"></i></div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Expired') }}</div>
                        <div class="bk-stat-sub">{{ __('ended') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($stats['expired']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 bk-a4">
            <div class="bk-stat" data-accent="green">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-green"><i data-feather="layers"></i></div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Total') }}</div>
                        <div class="bk-stat-sub">{{ __('all offers') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    @if($stats['total'] === 0)
        <div class="card border-0 shadow-sm rounded-4">
            <div class="mk-empty">
                <i data-feather="tag"></i>
                <h6 class="fw-bold mt-3 mb-1">{{ __('No offers yet') }}</h6>
                <p class="text-muted tx-13 mb-3">{{ __('Open any service and enable a discount to create an offer.') }}</p>
                @if($branches->isNotEmpty())
                <a href="{{ route('company.branches.services.index', $branches->first()) }}" class="btn btn-outline-secondary rounded-pill px-3">
                    {{ __('Go to services') }}
                </a>
                @endif
            </div>
        </div>
    @else
        @foreach(['active' => $active, 'scheduled' => $scheduled, 'expired' => $expired] as $state => $group)
            @continue($group->isEmpty())
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 px-3">
                    <span class="mk-sec-title">
                        @if($state === 'active') {{ __('Active offers') }}
                        @elseif($state === 'scheduled') {{ __('Scheduled offers') }}
                        @else {{ __('Expired offers') }} @endif
                        <span class="text-muted fw-normal">({{ $group->count() }})</span>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.03em;">
                                <tr>
                                    <th class="ps-3">{{ __('Service') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Discount') }}</th>
                                    <th class="d-none d-md-table-cell">{{ __('Period') }}</th>
                                    <th class="text-end pe-3">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group as $s)
                                    @php
                                        $final = $s->discount_type === 'percent'
                                            ? max(0, (float) $s->price * (1 - (float) $s->discount_value / 100))
                                            : max(0, (float) $s->price - (float) $s->discount_value);
                                        $discLabel = $s->discount_type === 'percent'
                                            ? '-' . rtrim(rtrim(number_format((float) $s->discount_value, 2), '0'), '.') . '%'
                                            : '-' . number_format((float) $s->discount_value) . ' ' . $s->currency;
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold" style="font-size:13px;">{{ $s->localizedName() }}</div>
                                            <div class="text-muted" style="font-size:11px;">
                                                {{ $s->branch?->localizedName() }}
                                                @if($s->serviceCategory) · {{ $s->serviceCategory->localizedName() }} @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="mk-old">{{ number_format((float) $s->price) }} {{ $s->currency }}</span>
                                            <span class="mk-new ms-1">{{ number_format($final) }} {{ $s->currency }}</span>
                                        </td>
                                        <td><span class="mk-disc">{{ $discLabel }}</span></td>
                                        <td class="d-none d-md-table-cell text-muted" style="font-size:12px;">
                                            @if($s->discount_starts_at || $s->discount_ends_at)
                                                {{ $s->discount_starts_at?->translatedFormat('d M Y') ?? __('Now') }}
                                                &ndash;
                                                {{ $s->discount_ends_at?->translatedFormat('d M Y') ?? __('Open') }}
                                            @else
                                                {{ __('Always on') }}
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('company.services.edit', $s) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                                <i data-feather="edit-2" class="mk-fi" style="width:12px;height:12px;"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</div>
@endsection
