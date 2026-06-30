@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-3 mb-1">
                <h4 class="fw-bold mb-0">{{ $customer->name }}</h4>
                @if($customer->isBirthday())
                    <span title="{{ __('Birthday today!') }}" style="font-size:20px;">🎂</span>
                @endif
                <button class="btn btn-sm p-1" style="opacity:.5;" title="{{ __('Edit') }}"
                        onclick="openEditCustomer({{ json_encode(['id'=>$customer->id,'name'=>$customer->name,'phone'=>$customer->phone,'age'=>$customer->age,'notes'=>$customer->notes,'date_of_birth'=>$customer->date_of_birth?->format('Y-m-d'),'source'=>$customer->source]) }})">
                    <i data-feather="edit-2" style="width:14px;height:14px;color:#667eea;"></i>
                </button>
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
        <button class="btn btn-sm btn-outline-danger rounded-pill px-3"
                onclick="openDeleteCustomer({{ $customer->id }}, '{{ e($customer->name) }}')">
            <i data-feather="trash-2" style="width:13px;height:13px;margin-inline-end:4px;"></i>
            {{ __('Delete') }}
        </button>
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
                    <div class="text-muted tx-13 mb-1" dir="ltr">{{ $customer->phone }}</div>

                    {{-- Source badge --}}
                    @if($customer->source)
                    <div class="mb-2">
                        <span style="font-size:11px;font-weight:700;background:rgba(102,126,234,.12);color:#667eea;padding:2px 10px;border-radius:12px;">
                            {{ __(ucfirst($customer->source)) }}
                        </span>
                    </div>
                    @endif

                    {{-- DOB + Age --}}
                    @if($customer->date_of_birth)
                    <div class="text-muted tx-12 mb-1">
                        🎂 {{ $customer->date_of_birth->format('d M Y') }}
                        @if($customer->calculated_age)
                            <span class="fw-semibold">({{ $customer->calculated_age }} {{ __('yrs') }})</span>
                        @endif
                    </div>
                    @endif

                    {{-- Loyalty & Debt row --}}
                    <div class="d-flex justify-content-center gap-3 flex-wrap mb-2">
                        <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:12px;">
                            ⭐ {{ number_format($customer->loyalty_points ?? 0) }} {{ __('pts') }}
                        </span>
                        @if($customer->debt > 0)
                        <span style="font-size:12px;font-weight:700;background:rgba(239,68,68,.12);color:#ef4444;padding:3px 10px;border-radius:12px;">
                            💳 {{ number_format($customer->debt, 2) }} {{ __('debt') }}
                        </span>
                        @endif
                    </div>

                    @if($customer->notes)
                    <div class="text-muted tx-12 mb-3 px-2" style="background:rgba(255,255,255,.03);border-radius:8px;padding:8px;">
                        📝 {{ $customer->notes }}
                    </div>
                    @endif

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

            {{-- Linked Branches --}}
            @if($customer->linkedBranches && $customer->linkedBranches->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <div class="tx-11 fw-bold text-muted text-uppercase mb-2" style="letter-spacing:.8px;">
                        {{ __('Linked Branches') }}
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($customer->linkedBranches as $lb)
                        <span style="font-size:11px;font-weight:600;background:rgba(201,162,39,.10);color:#C9A227;padding:2px 8px;border-radius:10px;">
                            {{ $lb->localizedName() }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

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

            {{-- Branch-specific notes --}}
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body p-3">
                    <div class="tx-11 fw-bold text-muted text-uppercase mb-3" style="letter-spacing:.8px;">
                        📋 {{ __('Branch Notes') }}
                    </div>
                    @foreach($branches as $br)
                    @php $brNote = $customer->branchNote($br->id); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="tx-12 fw-bold">{{ $br->localizedName() }}</span>
                            <button class="btn btn-sm p-0" style="opacity:.4;font-size:12px;" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#brNote-{{ $br->id }}">
                                ✏️
                            </button>
                        </div>
                        @if($brNote)
                        <div class="tx-12 text-muted p-2 rounded-3" style="background:rgba(255,255,255,.03);">
                            {{ $brNote->notes }}
                        </div>
                        @else
                        <div class="tx-11 text-muted" style="opacity:.3;">{{ __('No notes for this branch') }}</div>
                        @endif
                        <div class="collapse mt-2" id="brNote-{{ $br->id }}">
                            <form method="POST" action="{{ route('company.customers.branch-note', [$customer, $br]) }}" onsubmit="disableSubmit(this)">
                                @csrf @method('PUT')
                                <textarea name="notes" class="form-control form-control-sm mb-2" rows="2"
                                          placeholder="{{ __('e.g. prefers morning slots, likes product X...') }}">{{ $brNote?->notes }}</textarea>
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── RIGHT: History ── --}}
        <div class="col-lg-8">

            {{-- ─── TREATMENT PLANS ─────────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="d-flex align-items-center gap-2">
                            <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                                {{ __('Treatment Plans') }}
                            </div>
                            <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:20px;">
                                {{ $customer->treatmentPlans->count() }}
                            </span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                            <i data-feather="plus" style="width:13px;height:13px;margin-inline-end:4px;"></i>
                            {{ __('Add Plan') }}
                        </button>
                    </div>

                    @forelse($customer->treatmentPlans as $plan)
                    @php
                        $planPaid = $plan->sessions->sum('amount_paid');
                        $planRemaining = $plan->total_amount - $planPaid;
                        $statusColors = [
                            'active' => ['bg' => 'rgba(34,197,94,.12)', 'color' => '#22c55e'],
                            'completed' => ['bg' => 'rgba(102,126,234,.12)', 'color' => '#667eea'],
                            'cancelled' => ['bg' => 'rgba(239,68,68,.12)', 'color' => '#ef4444'],
                            'on_hold' => ['bg' => 'rgba(251,191,36,.12)', 'color' => '#fbbf24'],
                        ];
                        $sc = $statusColors[$plan->status] ?? ['bg' => 'rgba(255,255,255,.08)', 'color' => '#aaa'];
                    @endphp
                    <div class="px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.04);">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2 flex-wrap" role="button" data-bs-toggle="collapse" data-bs-target="#plan-{{ $plan->id }}">
                                <i data-feather="chevron-down" style="width:14px;height:14px;opacity:.4;"></i>
                                <span class="fw-semibold tx-13">{{ $plan->title }}</span>
                                <span class="text-muted tx-11">{{ $plan->branch?->localizedName() }}</span>
                                <span style="font-size:10px;font-weight:700;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:2px 8px;border-radius:10px;">
                                    {{ __(ucfirst(str_replace('_', ' ', $plan->status))) }}
                                </span>
                            </div>
                            <div class="text-end tx-12 flex-shrink-0">
                                <div class="fw-bold">{{ number_format($plan->total_amount, 2) }} <span class="text-muted tx-10">{{ $plan->currency }}</span></div>
                                <div class="text-muted tx-11">{{ __('Paid') }}: {{ number_format($planPaid, 2) }}</div>
                                @if($planRemaining > 0)
                                <div style="color:#ef4444;" class="tx-11 fw-semibold">{{ __('Remaining') }}: {{ number_format($planRemaining, 2) }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="collapse mt-3" id="plan-{{ $plan->id }}">
                            {{-- Sessions table --}}
                            <div class="table-responsive">
                                <table class="table table-sm mb-2" style="font-size:12px;">
                                    <thead>
                                        <tr class="text-muted">
                                            <th class="border-0 fw-semibold">#</th>
                                            <th class="border-0 fw-semibold">{{ __('Due') }}</th>
                                            <th class="border-0 fw-semibold">{{ __('Paid') }}</th>
                                            <th class="border-0 fw-semibold">{{ __('Status') }}</th>
                                            <th class="border-0 fw-semibold"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($plan->sessions as $session)
                                        <tr>
                                            <td class="border-0 align-middle">{{ $session->session_number }}</td>
                                            <td class="border-0 align-middle fw-semibold">{{ number_format($session->amount_due, 2) }}</td>
                                            <td class="border-0 align-middle">{{ number_format($session->amount_paid, 2) }}</td>
                                            <td class="border-0 align-middle">
                                                @php
                                                    $sesColors = [
                                                        'pending' => ['bg' => 'rgba(251,191,36,.12)', 'color' => '#fbbf24'],
                                                        'completed' => ['bg' => 'rgba(34,197,94,.12)', 'color' => '#22c55e'],
                                                        'cancelled' => ['bg' => 'rgba(239,68,68,.12)', 'color' => '#ef4444'],
                                                    ];
                                                    $sesC = $sesColors[$session->status] ?? ['bg' => 'rgba(255,255,255,.08)', 'color' => '#aaa'];
                                                @endphp
                                                <span style="font-size:10px;font-weight:700;background:{{ $sesC['bg'] }};color:{{ $sesC['color'] }};padding:2px 8px;border-radius:10px;">
                                                    {{ __(ucfirst($session->status)) }}
                                                </span>
                                            </td>
                                            <td class="border-0 align-middle text-end">
                                                @if($session->status === 'pending')
                                                <form method="POST" action="{{ route('company.treatment-plan-sessions.update', $session) }}"
                                                      class="d-inline-flex align-items-center gap-1" onsubmit="disableSubmit(this)">
                                                    @csrf @method('PUT')
                                                    <input type="number" name="amount_paid" step="0.01" min="0"
                                                           value="{{ $session->amount_paid }}"
                                                           class="form-control form-control-sm" style="width:80px;font-size:11px;" placeholder="{{ __('Paid') }}">
                                                    <select name="status" class="form-select form-select-sm" style="width:100px;font-size:11px;">
                                                        @foreach(\App\Models\TreatmentPlanSession::STATUSES as $sKey)
                                                        <option value="{{ $sKey }}" {{ $session->status === $sKey ? 'selected' : '' }}>{{ __(ucfirst($sKey)) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-2" style="font-size:11px;">
                                                        <i data-feather="check" style="width:12px;height:12px;"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($plan->notes)
                            <div class="text-muted tx-11 p-2 rounded-3 mb-2" style="background:rgba(255,255,255,.03);">
                                {{ $plan->notes }}
                            </div>
                            @endif

                            @if($plan->status === 'active')
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3 mt-1" data-bs-toggle="modal" data-bs-target="#cancelPlanModal-{{ $plan->id }}">
                                <i data-feather="x-circle" style="width:12px;height:12px;margin-inline-end:4px;"></i>
                                {{ __('Cancel Plan') }}
                            </button>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="bk-empty py-4">
                        <div class="bk-empty-ic mb-2">
                            <i data-feather="clipboard" style="width:22px;height:22px;"></i>
                        </div>
                        <p class="tx-12 text-muted">{{ __('No treatment plans yet.') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Visit history --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                            {{ __('Visit History') }}
                        </div>
                        <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:20px;">
                            {{ $appointments->total() }}
                        </span>
                    </div>

                    @forelse($appointments as $appt)
                    <div class="px-4 py-3 bk-table-row" style="border-bottom:1px solid rgba(255,255,255,.04);">
                        <div class="row gx-3 align-items-center">
                            <div class="col-6 col-sm-3">
                                <div class="fw-semibold tx-13">{{ $appt->start_time->translatedFormat('d M Y') }}</div>
                                <div class="text-muted tx-11">{{ $appt->start_time->format('H:i') }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="tx-13 fw-semibold">{{ $appt->service?->name ?? '—' }}</div>
                                <div class="text-muted tx-11">{{ $appt->employee?->name ?? '' }}</div>
                            </div>
                            <div class="col-6 col-sm-2 tx-12 text-muted">
                                {{ $appt->branch?->localizedName() ?? '—' }}
                            </div>
                            <div class="col-3 col-sm-2 text-end fw-bold tx-13">
                                @if($appt->total_price)
                                    {{ number_format((float)$appt->total_price, 0) }}
                                @else
                                    —
                                @endif
                            </div>
                            <div class="col-3 col-sm-2 text-end">
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

            @if($appointments->hasPages())
            <div class="mb-3">{{ $appointments->links() }}</div>
            @endif

            {{-- Payment history --}}
            @if($payments->total() > 0)
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                            {{ __('Payment History') }}
                        </div>
                        <span style="font-size:12px;font-weight:700;background:rgba(34,197,94,.12);color:#22c55e;padding:3px 10px;border-radius:20px;">
                            {{ $payments->total() }}
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
                            <div class="col-2 col-sm-1">
                                <span style="font-size:18px;">{{ $catMeta['icon'] }}</span>
                            </div>
                            <div class="col-10 col-sm-3">
                                <div class="tx-13 fw-semibold">{{ __($catMeta['label_key']) }}</div>
                                <div class="text-muted tx-11">{{ $pay->paid_at->translatedFormat('d M Y') }}</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                @if($pmMeta)
                                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:8px;background:{{ $pmMeta['color'] }}15;color:{{ $pmMeta['color'] }};">
                                    {{ $pmMeta['icon'] }} {{ __($pmMeta['label_key']) }}
                                </span>
                                @endif
                            </div>
                            <div class="col-6 col-sm-3 text-end">
                                @if($pay->notes)
                                <span class="text-muted tx-11">{{ Str::limit($pay->notes, 30) }}</span>
                                @endif
                            </div>
                            <div class="col-12 col-sm-2 text-end fw-bold tx-13 mt-1 mt-sm-0" style="color:{{ $isIncome ? '#22c55e' : '#ef4444' }};">
                                {{ $isIncome ? '+' : '-' }}{{ number_format($pay->amount, 2) }}
                                <span class="tx-10 text-muted fw-normal">{{ $sym }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($payments->hasPages())
            <div class="mt-3">{{ $payments->appends(['pay_page' => $payments->currentPage()])->links() }}</div>
            @endif
            @endif

            {{-- ─── LOYALTY POINTS ──────────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="d-flex align-items-center gap-2">
                            <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                                ⭐ {{ __('Loyalty Points') }}
                            </div>
                            <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:20px;">
                                {{ number_format($customer->loyalty_points ?? 0) }}
                            </span>
                        </div>
                        <button class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adjustPointsModal">
                            <i data-feather="plus-circle" style="width:13px;height:13px;margin-inline-end:4px;"></i>
                            {{ __('Adjust') }}
                        </button>
                    </div>

                    @if($loyaltyLogs && count($loyaltyLogs) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <thead>
                                <tr class="text-muted">
                                    <th class="border-0 fw-semibold px-4">{{ __('Date') }}</th>
                                    <th class="border-0 fw-semibold">{{ __('Points') }}</th>
                                    <th class="border-0 fw-semibold">{{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($loyaltyLogs as $log)
                                <tr>
                                    <td class="border-0 px-4 text-muted">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="border-0 fw-bold" style="color:{{ $log->points >= 0 ? '#22c55e' : '#ef4444' }};">
                                        {{ $log->points >= 0 ? '+' : '' }}{{ $log->points }}
                                    </td>
                                    <td class="border-0 text-muted">{{ $log->reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="bk-empty py-4">
                        <p class="tx-12 text-muted mb-0">{{ __('No loyalty activity yet.') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ─── COMMUNICATION LOG ───────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body p-0">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(255,255,255,.06);">
                        <div class="d-flex align-items-center gap-2">
                            <div class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">
                                💬 {{ __('Communication Log') }}
                            </div>
                            @if($communications->total() > 0)
                            <span style="font-size:12px;font-weight:700;background:rgba(102,126,234,.12);color:#667eea;padding:3px 10px;border-radius:20px;">
                                {{ $communications->total() }}
                            </span>
                            @endif
                        </div>
                        <button class="btn btn-sm p-1" style="opacity:.5;" type="button"
                                data-bs-toggle="collapse" data-bs-target="#addCommForm">
                            <i data-feather="plus" style="width:14px;height:14px;color:#667eea;"></i>
                        </button>
                    </div>

                    {{-- Inline add form --}}
                    <div class="collapse" id="addCommForm">
                        <div class="px-4 py-3" style="background:rgba(255,255,255,.02);border-bottom:1px solid rgba(255,255,255,.06);">
                            <form method="POST" action="{{ route('company.customers.communications.store', $customer) }}" onsubmit="disableSubmit(this)">
                                @csrf
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-4">
                                        <select name="type" class="form-select form-select-sm" style="font-size:12px;" required>
                                            <option value="">{{ __('Type') }}</option>
                                            @foreach(\App\Models\CustomerCommunication::TYPES as $cKey => $cMeta)
                                            <option value="{{ $cKey }}">{{ $cMeta['icon'] }} {{ __($cMeta['label_key']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <select name="direction" class="form-select form-select-sm" style="font-size:12px;" required>
                                            <option value="">{{ __('Direction') }}</option>
                                            @foreach(\App\Models\CustomerCommunication::DIRECTIONS as $dKey => $dMeta)
                                            <option value="{{ $dKey }}">{{ $dMeta['icon'] }} {{ __($dMeta['label_key']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4 text-end">
                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3" style="font-size:12px;">
                                            <i data-feather="send" style="width:12px;height:12px;margin-inline-end:4px;"></i>
                                            {{ __('Save') }}
                                        </button>
                                    </div>
                                </div>
                                <textarea name="message" class="form-control form-control-sm" rows="2" style="font-size:12px;"
                                          placeholder="{{ __('Message or note...') }}" required></textarea>
                            </form>
                        </div>
                    </div>

                    @forelse($communications as $comm)
                    @php
                        $commIcons = ['call'=>'phone','sms'=>'message-square','email'=>'mail','whatsapp'=>'message-circle','note'=>'file-text'];
                        $commIcon = $commIcons[$comm->type] ?? 'message-square';
                        $dirColor = $comm->direction === 'outgoing' ? '#667eea' : '#C9A227';
                    @endphp
                    <div class="px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,.04);">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <i data-feather="{{ $commIcon }}" style="width:14px;height:14px;color:{{ $dirColor }};"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <span class="fw-semibold tx-12">{{ __(ucfirst($comm->type)) }}</span>
                                        <span style="font-size:10px;font-weight:600;background:rgba(255,255,255,.06);padding:1px 6px;border-radius:8px;margin-inline-start:4px;">
                                            {{ __(ucfirst($comm->direction)) }}
                                        </span>
                                    </div>
                                    <span class="text-muted tx-11">{{ $comm->created_at->translatedFormat('d M Y H:i') }}</span>
                                </div>
                                <div class="text-muted tx-12">{{ $comm->message }}</div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bk-empty py-4">
                        <p class="tx-12 text-muted mb-0">{{ __('No communications logged.') }}</p>
                    </div>
                    @endforelse

                    @if($communications->hasPages())
                    <div class="px-4 py-2">{{ $communications->appends(['comm_page' => $communications->currentPage()])->links() }}</div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ─── ADD TREATMENT PLAN MODAL ──────────────────────────────────── --}}
    <div class="modal fade" id="addPlanModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <div class="modal-content rounded-4">
                <form method="POST" action="{{ route('company.customers.treatment-plans.store', $customer) }}" onsubmit="disableSubmit(this)">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">{{ __('New Treatment Plan') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="{{ __('e.g. Teeth whitening package') }}">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Branch') }} <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-select" required>
                                    <option value="">{{ __('Select branch') }}</option>
                                    @foreach($branches as $br)
                                    <option value="{{ $br->id }}">{{ $br->localizedName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Currency') }}</label>
                                <input type="text" name="currency" class="form-control" value="{{ config('booksy.default_currency', 'SAR') }}" maxlength="5">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Total Amount') }} <span class="text-danger">*</span></label>
                                <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Number of Sessions') }} <span class="text-danger">*</span></label>
                                <input type="number" name="session_count" class="form-control" min="1" max="100" required>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">{{ __('Create Plan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── CANCEL PLAN MODALS ────────────────────────────────────────── --}}
    @foreach($customer->treatmentPlans->where('status', 'active') as $plan)
    <div class="modal fade" id="cancelPlanModal-{{ $plan->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4">
                <div class="modal-body text-center p-4">
                    <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
                    <h6 class="fw-bold mb-2">{{ __('Cancel this plan?') }}</h6>
                    <p class="text-muted small mb-3">{{ $plan->title }}</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('No') }}</button>
                        <form method="POST" action="{{ route('company.treatment-plans.destroy', $plan) }}" onsubmit="disableSubmit(this)">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">{{ __('Cancel Plan') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ─── ADJUST LOYALTY POINTS MODAL ───────────────────────────────── --}}
    <div class="modal fade" id="adjustPointsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4">
                <form method="POST" action="{{ route('company.customers.loyalty.adjust', $customer) }}" onsubmit="disableSubmit(this)">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">{{ __('Adjust Points') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Points') }} <span class="text-danger">*</span></label>
                            <input type="number" name="points" class="form-control" required placeholder="{{ __('e.g. 50 or -20') }}">
                            <div class="form-text tx-11">{{ __('Use negative number to deduct points.') }}</div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Reason') }}</label>
                            <input type="text" name="reason" class="form-control" placeholder="{{ __('e.g. Birthday bonus') }}">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── EDIT CUSTOMER MODAL ────────────────────────────────────────── --}}
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content rounded-4">
                <form method="POST" id="editCustomerForm" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">{{ __('Edit Customer') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="editPhone" class="form-control" required dir="ltr">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Age') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <input type="number" name="age" id="editAge" class="form-control" min="1" max="120">
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── DELETE CUSTOMER MODAL ──────────────────────────────────────── --}}
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4">
                <div class="modal-body text-center p-4">
                    <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                    <h6 class="fw-bold mb-2">{{ __('Delete customer?') }}</h6>
                    <p class="text-muted small mb-1" id="deleteCustomerName"></p>
                    <p class="text-muted small mb-3">{{ __('This will permanently delete this customer and all their data.') }}</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <form method="POST" id="deleteCustomerForm" onsubmit="disableSubmit(this)">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var EDIT_BASE = '{{ route("company.customers.update", "__ID__") }}';
var DELETE_BASE = '{{ route("company.customers.destroy", "__ID__") }}';

window.disableSubmit = function(form) {
    form.querySelectorAll('button[type="submit"]').forEach(function(b) { b.disabled = true; b.style.opacity = '.5'; });
};

window.openEditCustomer = function(c) {
    document.getElementById('editCustomerForm').action = EDIT_BASE.replace('__ID__', c.id);
    document.getElementById('editName').value = c.name || '';
    document.getElementById('editPhone').value = c.phone || '';
    document.getElementById('editAge').value = c.age || '';
    document.getElementById('editNotes').value = c.notes || '';
    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
};

window.openDeleteCustomer = function(id, name) {
    document.getElementById('deleteCustomerForm').action = DELETE_BASE.replace('__ID__', id);
    document.getElementById('deleteCustomerName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
};
</script>
@endpush
@endsection
