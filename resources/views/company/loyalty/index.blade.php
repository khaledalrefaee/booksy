@extends('company.dashboard')

@php
    $rtl = app()->getLocale() === 'ar';
    $typeLabels = [
        'free_service'    => __('Free service'),
        'percent_all'     => __('% off all services'),
        'percent_service' => __('% off a service'),
    ];
    $typeIcons = [
        'free_service'    => 'gift',
        'percent_all'     => 'percent',
        'percent_service' => 'percent',
    ];
@endphp

@section('content')
<div class="container-fluid py-3" style="max-width:960px;">

    @include('company.partials.flash')

    {{-- Hero --}}
    <div class="bk-hero bk-a1 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 class="bk-hero-title">{{ __('Loyalty') }} <span>{{ __('Rewards') }}</span></h2>
                <p class="bk-hero-sub">
                    <i data-feather="award" style="width:13px;height:13px;display:inline;margin-inline-end:5px;"></i>
                    {{ $branch->localizedName() }}
                </p>
            </div>
            <a href="{{ route('company.branches.services.index', $branch) }}"
               class="bk-navbar-action bk-navbar-action-ghost d-flex align-items-center gap-2">
                <i data-feather="scissors" style="width:14px;height:14px;"></i>
                {{ __('Services') }}
            </a>
        </div>
    </div>

    {{-- ── Earn settings ─────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i data-feather="trending-up" style="width:18px;height:18px;"></i>
                {{ __('How points are earned') }}
            </h5>
            <p class="text-muted tx-13 mb-3">{{ __('Customers earn points automatically when an appointment is completed.') }}</p>

            <form method="POST" action="{{ route('company.branches.loyalty.settings', $branch) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-sm-6">
                    <label class="form-label fw-semibold" for="lpv">{{ __('Points per visit') }}</label>
                    <input type="number" id="lpv" name="loyalty_points_per_visit" min="0" max="9999" inputmode="numeric"
                           class="form-control rounded-3 @error('loyalty_points_per_visit') is-invalid @enderror"
                           value="{{ old('loyalty_points_per_visit', $branch->loyalty_points_per_visit ?? 10) }}">
                    <small class="text-muted">{{ __('Fixed points for every completed appointment.') }}</small>
                    @error('loyalty_points_per_visit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold" for="lpc">{{ __('Spend amount per point') }}</label>
                    <input type="number" id="lpc" name="loyalty_points_per_currency_unit" min="0" inputmode="numeric"
                           class="form-control rounded-3 @error('loyalty_points_per_currency_unit') is-invalid @enderror"
                           value="{{ old('loyalty_points_per_currency_unit', $branch->loyalty_points_per_currency_unit ?? 10000) }}">
                    <small class="text-muted">{{ __('Amount a customer spends to earn 1 point (e.g. 10,000 → spending 55,000 = 5 points). 0 = disabled.') }}</small>
                    @error('loyalty_points_per_currency_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i data-feather="save" class="me-1" style="width:15px;height:15px;"></i>
                        {{ __('Save settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Rewards ───────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i data-feather="gift" style="width:18px;height:18px;"></i>
                {{ __('Rewards') }}
            </h5>
            <p class="text-muted tx-13 mb-3">{{ __('What customers can redeem their points for. Optional — leave empty and points simply accumulate.') }}</p>

            {{-- List --}}
            @if($rewards->isEmpty())
                <div class="text-center text-muted py-4 rounded-3" style="border:1px dashed var(--bk-border);">
                    <i data-feather="gift" style="width:26px;height:26px;opacity:.5;"></i>
                    <p class="mb-0 mt-2 tx-13">{{ __('No rewards yet. Add your first one below.') }}</p>
                </div>
            @else
                <div class="d-flex flex-column gap-2 mb-4">
                    @foreach($rewards as $r)
                        <div class="d-flex align-items-center gap-2 gap-sm-3 p-2 p-sm-3 rounded-3" style="border:1px solid var(--bk-border);">
                            <span class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-circle"
                                  style="width:38px;height:38px;background:var(--bk-accent-wash);color:var(--bk-accent);">
                                <i data-feather="{{ $typeIcons[$r->type] ?? 'gift' }}" style="width:17px;height:17px;"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $r->name }}</div>
                                <div class="tx-12 text-muted">
                                    {{ $typeLabels[$r->type] ?? $r->type }}
                                    @if($r->discount_percent) · {{ $r->discount_percent }}% @endif
                                    @if($r->service) · {{ $r->service->localizedName() }} @endif
                                </div>
                            </div>
                            <span class="badge rounded-pill flex-shrink-0" style="background:var(--bk-accent-wash);color:var(--bk-accent);font-weight:600;">
                                {{ number_format($r->points_cost) }} {{ __('pts') }}
                            </span>
                            <form method="POST" action="{{ route('company.loyalty.rewards.destroy', $r) }}" class="m-0 flex-shrink-0"
                                  onsubmit="return confirm('{{ __('Remove this reward?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-0 d-inline-flex align-items-center justify-content-center"
                                        style="width:34px;height:34px;" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                                    <i data-feather="trash-2" style="width:15px;height:15px;"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add reward --}}
            <div class="rounded-3 p-3" style="border:1px dashed var(--bk-border);">
                <div class="fw-semibold mb-2 tx-13">{{ __('Add a reward') }}</div>
                <form method="POST" action="{{ route('company.branches.loyalty.rewards.store', $branch) }}" class="row g-2" id="rewardForm">
                    @csrf
                    <div class="col-12 col-md">
                        <label class="form-label tx-12 mb-1" for="rw_name">{{ __('Reward name') }}</label>
                        <input type="text" id="rw_name" name="name" maxlength="255" required
                               class="form-control form-control-sm rounded-3 @error('name') is-invalid @enderror"
                               placeholder="{{ __('e.g. Free facial') }}" value="{{ old('name') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label tx-12 mb-1" for="rw_type">{{ __('Type') }}</label>
                        <select id="rw_type" name="type" class="form-select form-select-sm rounded-3">
                            <option value="free_service" @selected(old('type')==='free_service')>{{ __('Free service') }}</option>
                            <option value="percent_all" @selected(old('type')==='percent_all')>{{ __('% off all services') }}</option>
                            <option value="percent_service" @selected(old('type')==='percent_service')>{{ __('% off a service') }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3" id="rw_service_wrap">
                        <label class="form-label tx-12 mb-1" for="rw_service">{{ __('Service') }}</label>
                        <select id="rw_service" name="service_id" class="form-select form-select-sm rounded-3 @error('service_id') is-invalid @enderror">
                            <option value="">{{ __('Choose…') }}</option>
                            @foreach($services as $s)
                                <option value="{{ $s->id }}" @selected(old('service_id')==$s->id)>{{ $s->localizedName() }}</option>
                            @endforeach
                        </select>
                        @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-2 d-none" id="rw_percent_wrap">
                        <label class="form-label tx-12 mb-1" for="rw_percent">{{ __('Discount %') }}</label>
                        <input type="number" id="rw_percent" name="discount_percent" min="1" max="100" inputmode="numeric"
                               class="form-control form-control-sm rounded-3 @error('discount_percent') is-invalid @enderror"
                               placeholder="20" value="{{ old('discount_percent') }}">
                        @error('discount_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label tx-12 mb-1" for="rw_points">{{ __('Points') }}</label>
                        <input type="number" id="rw_points" name="points_cost" min="1" required inputmode="numeric"
                               class="form-control form-control-sm rounded-3 @error('points_cost') is-invalid @enderror"
                               placeholder="100" value="{{ old('points_cost') }}">
                        @error('points_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i data-feather="plus" class="me-1" style="width:14px;height:14px;"></i>
                            {{ __('Add reward') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var typeSel     = document.getElementById('rw_type');
    var serviceWrap = document.getElementById('rw_service_wrap');
    var percentWrap = document.getElementById('rw_percent_wrap');
    if (!typeSel) return;

    function sync() {
        var t = typeSel.value;
        // Service needed for free_service + percent_service; % needed for the two percent types.
        serviceWrap.classList.toggle('d-none', t === 'percent_all');
        percentWrap.classList.toggle('d-none', t === 'free_service');
        if (window.feather) feather.replace();
    }
    typeSel.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
