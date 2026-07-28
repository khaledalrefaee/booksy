@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Subscription coupons') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Subscription coupons') }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text rounded-pill shadow-sm"
                data-bs-toggle="modal" data-bs-target="#modal-coupon" data-mode="create">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add coupon') }}
        </button>
    </div>

    @include('owner.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Code') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Companies') }}</th>
                            <th>{{ __('Plans') }}</th>
                            <th>{{ __('Usage') }}</th>
                            <th>{{ __('Expires at') }}</th>
                            <th>{{ __('State') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            @php
                                $companyNames = $coupon->company_ids === null
                                    ? null
                                    : $companies->whereIn('id', array_map('intval', $coupon->company_ids))->map->localizedName()->implode('، ');
                                $planNames = $coupon->plan_ids === null
                                    ? null
                                    : $plans->whereIn('id', array_map('intval', $coupon->plan_ids))->map->localizedName()->implode('، ');
                                $usable = $coupon->isUsable();
                                $couponJson = array_merge(
                                    $coupon->only(['code', 'description', 'type', 'value', 'currency', 'company_ids', 'plan_ids', 'max_uses', 'is_active']),
                                    ['expires_at' => $coupon->expires_at?->format('Y-m-d')]
                                );
                            @endphp
                            <tr @class(['opacity-50' => ! $usable])>
                                <td class="ps-4">
                                    <span class="fw-bold" dir="ltr">{{ $coupon->code }}</span>
                                    @if($coupon->description)
                                        <div class="text-muted tx-12">{{ $coupon->description }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap fw-semibold">
                                    @if($coupon->type === 'percent')
                                        {{ rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.') }}%
                                    @else
                                        {{ number_format((float) $coupon->value, 2) }} {{ $coupon->currency }}
                                    @endif
                                </td>
                                <td class="tx-13" style="max-width:200px;">
                                    @if($companyNames === null)
                                        <span class="badge rounded-pill bg-light text-muted border">{{ __('All companies') }}</span>
                                    @else
                                        <span class="text-truncate d-inline-block" style="max-width:190px;" title="{{ $companyNames }}">{{ $companyNames }}</span>
                                    @endif
                                </td>
                                <td class="tx-13" style="max-width:160px;">
                                    @if($planNames === null)
                                        <span class="badge rounded-pill bg-light text-muted border">{{ __('All plans') }}</span>
                                    @else
                                        <span class="text-truncate d-inline-block" style="max-width:150px;" title="{{ $planNames }}">{{ $planNames }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap tx-13">
                                    {{ $coupon->used_count }}{{ $coupon->max_uses !== null ? ' / '.$coupon->max_uses : '' }}
                                </td>
                                <td class="text-nowrap tx-13">{{ $coupon->expires_at?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if($usable)
                                        <span class="badge rounded-pill bg-success-subtle text-success">{{ __('Active') }}</span>
                                    @elseif(! $coupon->is_active)
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary">{{ __('Disabled') }}</span>
                                    @elseif($coupon->expires_at?->isPast())
                                        <span class="badge rounded-pill bg-danger-subtle text-danger">{{ __('Expired') }}</span>
                                    @else
                                        <span class="badge rounded-pill bg-warning-subtle text-warning">{{ __('Uses exhausted') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                            data-bs-toggle="modal" data-bs-target="#modal-coupon"
                                            data-mode="edit"
                                            data-update-url="{{ route('owner.coupons.update', $coupon) }}"
                                            data-coupon="{{ json_encode($couponJson) }}"
                                            aria-label="{{ __('Edit') }}">
                                        <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                                    </button>
                                    <form method="post" action="{{ route('owner.coupons.destroy', $coupon) }}" class="d-inline"
                                          onsubmit="return confirm('{{ $coupon->used_count > 0 ? __('This coupon was used — it will be deactivated. Continue?') : __('Delete this coupon?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" aria-label="{{ __('Delete') }}">
                                            <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="tag" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No coupons yet. Create one for a specific company or a plan offer.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Create / edit coupon modal ── --}}
    <div class="modal fade" id="modal-coupon" tabindex="-1" aria-labelledby="modal-coupon-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="post" class="modal-content border-0 shadow rounded-4" id="bk-coupon-form">
                @csrf
                <input type="hidden" name="_method" value="POST" id="bk-coupon-method">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modal-coupon-title">{{ __('Add coupon') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cp-code" class="form-label fw-semibold">{{ __('Code') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" name="code" id="cp-code" class="form-control" maxlength="40" required
                                   style="text-transform:uppercase;" dir="ltr" placeholder="WELCOME20">
                        </div>
                        <div class="col-md-6">
                            <label for="cp-description" class="form-label fw-semibold">{{ __('Description') }}</label>
                            <input type="text" name="description" id="cp-description" class="form-control" maxlength="255">
                        </div>

                        <div class="col-md-4">
                            <label for="cp-type" class="form-label fw-semibold">{{ __('Discount type') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <select name="type" id="cp-type" class="form-select" required>
                                <option value="percent">{{ __('Percentage %') }}</option>
                                <option value="fixed">{{ __('Fixed amount') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="cp-value" class="form-label fw-semibold">{{ __('Value') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="number" name="value" id="cp-value" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-4 d-none" id="cp-currency-wrap">
                            <label for="cp-currency" class="form-label fw-semibold">{{ __('Currency') }}</label>
                            <select name="currency" id="cp-currency" class="form-select">
                                @foreach(['USD', 'SYP', 'EUR'] as $cur)
                                    <option value="{{ $cur }}">{{ $cur }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Applies only to plans in this currency.') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="cp-companies" class="form-label fw-semibold">{{ __('Companies') }}</label>
                            <select name="company_ids[]" id="cp-companies" class="form-select" multiple size="6">
                                @foreach($companies as $companyOption)
                                    <option value="{{ $companyOption->id }}">{{ $companyOption->localizedName() }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Leave empty for every company. Ctrl/Cmd-click for multiple.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="cp-plans" class="form-label fw-semibold">{{ __('Plans') }}</label>
                            <select name="plan_ids[]" id="cp-plans" class="form-select" multiple size="6">
                                @foreach($plans as $planOption)
                                    <option value="{{ $planOption->id }}">{{ $planOption->localizedName() }} — {{ number_format((float) $planOption->price, 2) }} {{ $planOption->currency }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Leave empty for every plan (a plan offer targets specific plans).') }}</div>
                        </div>

                        <div class="col-md-4">
                            <label for="cp-max-uses" class="form-label fw-semibold">{{ __('Max uses') }}</label>
                            <input type="number" name="max_uses" id="cp-max-uses" class="form-control" min="1" max="100000"
                                   placeholder="{{ __('Unlimited') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="cp-expires" class="form-label fw-semibold">{{ __('Expires at') }}</label>
                            <input type="date" name="expires_at" id="cp-expires" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="cp-active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="cp-active">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const modal      = document.getElementById('modal-coupon');
    const form       = document.getElementById('bk-coupon-form');
    const methodEl   = document.getElementById('bk-coupon-method');
    const titleEl    = document.getElementById('modal-coupon-title');
    const typeEl     = document.getElementById('cp-type');
    const curWrap    = document.getElementById('cp-currency-wrap');
    const createUrl  = @json(route('owner.coupons.store'));
    const createTitle = @json(__('Add coupon'));
    const editTitle   = @json(__('Edit coupon'));

    function toggleCurrency() {
        curWrap.classList.toggle('d-none', typeEl.value !== 'fixed');
    }
    typeEl.addEventListener('change', toggleCurrency);

    function setMulti(selectId, values) {
        const sel = document.getElementById(selectId);
        const set = new Set((values || []).map(String));
        [...sel.options].forEach(o => { o.selected = set.has(o.value); });
    }

    modal.addEventListener('show.bs.modal', (event) => {
        const btn = event.relatedTarget;
        if (!btn) return;

        form.reset();

        if (btn.dataset.mode === 'edit') {
            const c = JSON.parse(btn.dataset.coupon);
            form.action = btn.dataset.updateUrl;
            methodEl.value = 'PUT';
            titleEl.textContent = editTitle;

            document.getElementById('cp-code').value        = c.code || '';
            document.getElementById('cp-description').value = c.description || '';
            typeEl.value                                    = c.type || 'percent';
            document.getElementById('cp-value').value       = c.value || '';
            document.getElementById('cp-currency').value    = c.currency || 'USD';
            document.getElementById('cp-max-uses').value    = c.max_uses || '';
            document.getElementById('cp-expires').value     = c.expires_at || '';
            document.getElementById('cp-active').checked    = !!c.is_active;
            setMulti('cp-companies', c.company_ids);
            setMulti('cp-plans', c.plan_ids);
        } else {
            form.action = createUrl;
            methodEl.value = 'POST';
            titleEl.textContent = createTitle;
            document.getElementById('cp-active').checked = true;
            setMulti('cp-companies', []);
            setMulti('cp-plans', []);
        }

        toggleCurrency();
    });

    modal.addEventListener('shown.bs.modal', () => document.getElementById('cp-code').focus());
})();
</script>
@endpush

@endsection
