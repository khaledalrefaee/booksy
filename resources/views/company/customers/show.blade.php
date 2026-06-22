@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-3 mb-1">
                <h4 class="fw-bold mb-0">{{ $customer->name }}</h4>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('company.customers.index') }}">{{ __('Customers') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $customer->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row g-4">

        {{-- ── LEFT: Customer Info ── --}}
        <div class="col-lg-4">

            {{-- Profile card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4 text-center">
                    @if($customer->avatar)
                        <img src="{{ asset('storage/' . $customer->avatar) }}" alt=""
                             style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:16px;border:3px solid rgba(201,162,39,.3);">
                    @else
                        <div style="width:80px;height:80px;border-radius:50%;background:rgba(201,162,39,.15);color:#C9A227;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:28px;margin:0 auto 16px;border:3px solid rgba(201,162,39,.3);">
                            {{ mb_substr($customer->name, 0, 1) }}
                        </div>
                    @endif

                    <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                    <div class="text-muted tx-13 mb-3" dir="ltr">{{ $customer->phone }}</div>

                    <div class="d-flex justify-content-center gap-4 flex-wrap" style="border-top:1px solid rgba(255,255,255,.06);padding-top:16px;">
                        @if($customer->age)
                        <div class="text-center">
                            <div class="fw-bold tx-13">{{ $customer->age }}</div>
                            <div class="text-muted tx-11">{{ __('Age') }}</div>
                        </div>
                        @endif
                        @if($memberSince)
                        <div class="text-center">
                            <div class="fw-bold tx-13">{{ $memberSince->translatedFormat('M Y') }}</div>
                            <div class="text-muted tx-11">{{ __('Member Since') }}</div>
                        </div>
                        @endif
                        @if($lastVisit)
                        <div class="text-center">
                            <div class="fw-bold tx-13">{{ $lastVisit->translatedFormat('d M') }}</div>
                            <div class="text-muted tx-11">{{ __('Last Visit') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="bk-stat" data-accent="gold" style="padding:14px 16px;">
                        <div class="bk-stat-left">
                            <div class="bk-stat-info">
                                <div class="bk-stat-label">{{ __('Total Visits') }}</div>
                            </div>
                        </div>
                        <div class="bk-stat-num" style="font-size:1.4rem;">{{ $totalVisits }}</div>
                    </div>
                </div>
                <div class="col-6">
                    @foreach($totalSpent as $ts)
                    <div class="bk-stat" data-accent="green" style="padding:14px 16px;{{ !$loop->last ? 'margin-bottom:8px;' : '' }}">
                        <div class="bk-stat-left">
                            <div class="bk-stat-info">
                                <div class="bk-stat-label">{{ __('Total Spent') }}</div>
                                <div class="bk-stat-sub">{{ $ts['currency'] }}</div>
                            </div>
                        </div>
                        <div class="bk-stat-num" style="font-size:1.2rem;">{{ number_format($ts['amount'], 0) }}</div>
                    </div>
                    @endforeach
                    @if($totalSpent->isEmpty())
                    <div class="bk-stat" data-accent="green" style="padding:14px 16px;">
                        <div class="bk-stat-left">
                            <div class="bk-stat-info">
                                <div class="bk-stat-label">{{ __('Total Spent') }}</div>
                            </div>
                        </div>
                        <div class="bk-stat-num" style="font-size:1.4rem;">0</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Top services --}}
            @if($topServices->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3">
                    <div class="tx-11 fw-bold text-muted text-uppercase mb-3" style="letter-spacing:.8px;">
                        {{ __('Top Services') }}
                    </div>
                    @foreach($topServices as $ts)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:8px;height:8px;border-radius:50%;background:#C9A227;flex-shrink:0;"></div>
                            <span class="tx-13 fw-semibold">{{ $ts['service']?->name ?? '—' }}</span>
                        </div>
                        <span style="font-size:11px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:2px 8px;border-radius:12px;">
                            {{ $ts['count'] }} {{ __('visits') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ── RIGHT: History ── --}}
        <div class="col-lg-8">

            {{-- Visit history --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                            {{ __('Visit History') }}
                        </div>
                        <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:20px;">
                            {{ $appointments->count() }}
                        </span>
                    </div>

                    @forelse($appointments as $appt)
                    <div class="px-4 py-3 bk-table-row" style="border-bottom:1px solid rgba(255,255,255,.04);">
                        <div class="row gx-3 align-items-center">
                            <div class="col-sm-3">
                                <div class="fw-semibold tx-13">{{ $appt->start_time->translatedFormat('d M Y') }}</div>
                                <div class="text-muted tx-11">{{ $appt->start_time->format('H:i') }}</div>
                            </div>
                            <div class="col-sm-3">
                                <div class="tx-13 fw-semibold">{{ $appt->service?->name ?? '—' }}</div>
                                <div class="text-muted tx-11">{{ $appt->employee?->name ?? '' }}</div>
                            </div>
                            <div class="col-sm-2 tx-12 text-muted">
                                {{ $appt->branch?->localizedName() ?? '—' }}
                            </div>
                            <div class="col-sm-2 text-end fw-bold tx-13">
                                @if($appt->total_price)
                                    {{ number_format((float)$appt->total_price, 0) }}
                                @else
                                    —
                                @endif
                            </div>
                            <div class="col-sm-2 text-end">
                                <span class="bk-badge bk-badge-{{ $appt->status }}">{{ __(ucfirst($appt->status)) }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bk-empty py-5">
                        <div class="bk-empty-ic mb-3">
                            <i data-feather="calendar" style="width:24px;height:24px;"></i>
                        </div>
                        <p>{{ __('No visits yet.') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Payment history --}}
            @if($payments->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                            {{ __('Payment History') }}
                        </div>
                        <span style="font-size:12px;font-weight:700;background:rgba(34,197,94,.12);color:#22c55e;padding:3px 10px;border-radius:20px;">
                            {{ $payments->count() }}
                        </span>
                    </div>

                    @php $pmMethods = \App\Models\BranchPayment::PAYMENT_METHODS; @endphp
                    @foreach($payments as $pay)
                    @php
                        $cats = \App\Models\BranchPayment::CATEGORIES;
                        $catMeta = $cats[$pay->category] ?? ['icon'=>'💵','label_key'=>$pay->category,'type'=>'income'];
                        $isIncome = ($catMeta['type'] ?? 'income') === 'income';
                        $pmMeta = $pmMethods[$pay->payment_method] ?? null;
                        $sym = config("booksy.currencies.{$pay->currency}.symbol", $pay->currency);
                    @endphp
                    <div class="px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.04);">
                        <div class="row gx-3 align-items-center">
                            <div class="col-sm-1">
                                <span style="font-size:18px;">{{ $catMeta['icon'] }}</span>
                            </div>
                            <div class="col-sm-3">
                                <div class="tx-13 fw-semibold">{{ __($catMeta['label_key']) }}</div>
                                <div class="text-muted tx-11">{{ $pay->paid_at->translatedFormat('d M Y') }}</div>
                            </div>
                            <div class="col-sm-3">
                                @if($pmMeta)
                                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:8px;background:{{ $pmMeta['color'] }}15;color:{{ $pmMeta['color'] }};">
                                    {{ $pmMeta['icon'] }} {{ __($pmMeta['label_key']) }}
                                </span>
                                @endif
                            </div>
                            <div class="col-sm-3 text-end">
                                @if($pay->notes)
                                <span class="text-muted tx-11">{{ Str::limit($pay->notes, 30) }}</span>
                                @endif
                            </div>
                            <div class="col-sm-2 text-end fw-bold tx-13" style="color:{{ $isIncome ? '#22c55e' : '#ef4444' }};">
                                {{ $isIncome ? '+' : '-' }}{{ number_format($pay->amount, 2) }}
                                <span class="tx-10 text-muted fw-normal">{{ $sym }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
