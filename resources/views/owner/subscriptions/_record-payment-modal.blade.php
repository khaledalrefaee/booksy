{{-- Shared "record subscription payment" modal.
     Expects: $companies (id, name, plan_id, plan_expires_at), $plans, $methods --}}
<div class="modal fade" id="modal-record-payment" tabindex="-1"
     aria-labelledby="modal-record-payment-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('owner.subscription-payments.store') }}"
              class="modal-content border-0 shadow rounded-4" id="bk-payment-form">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="modal-record-payment-title">
                    {{ __('Record subscription payment') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="bk-pay-company" class="form-label fw-semibold">
                            {{ __('Company') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select name="company_id" id="bk-pay-company" class="form-select" required>
                            <option value="">{{ __('Choose a company…') }}</option>
                            @foreach($companies as $companyOption)
                                <option value="{{ $companyOption->id }}"
                                        data-plan-id="{{ $companyOption->plan_id }}"
                                        data-expires="{{ $companyOption->plan_expires_at?->format('Y-m-d') }}">
                                    {{ $companyOption->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="bk-pay-plan" class="form-label fw-semibold">
                            {{ __('Plan') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select name="plan_id" id="bk-pay-plan" class="form-select" required>
                            <option value="">{{ __('Choose a plan…') }}</option>
                            @foreach($plans as $planOption)
                                <option value="{{ $planOption->id }}"
                                        data-price="{{ $planOption->price }}"
                                        data-currency="{{ $planOption->currency }}"
                                        data-duration="{{ $planOption->duration_days }}">
                                    {{ $planOption->localizedName() }} — {{ number_format((float) $planOption->price, 2) }} {{ $planOption->currency }} / {{ $planOption->duration_days }} {{ __('days') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Amount and currency are filled from the plan — adjust for discounts.') }}</div>
                    </div>

                    @if(($modalCoupons ?? collect())->isNotEmpty())
                    <div class="col-12">
                        <label for="bk-pay-coupon" class="form-label fw-semibold">{{ __('Coupon') }}</label>
                        <select name="coupon_id" id="bk-pay-coupon" class="form-select">
                            <option value="">{{ __('No coupon') }}</option>
                            @foreach($modalCoupons as $coupon)
                                <option value="{{ $coupon->id }}"
                                        data-type="{{ $coupon->type }}"
                                        data-value="{{ $coupon->value }}"
                                        data-currency="{{ $coupon->currency }}"
                                        data-companies="{{ $coupon->company_ids ? implode(',', $coupon->company_ids) : '' }}"
                                        data-plans="{{ $coupon->plan_ids ? implode(',', $coupon->plan_ids) : '' }}">
                                    {{ $coupon->code }} —
                                    @if($coupon->type === 'percent')
                                        {{ rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.') }}%
                                    @else
                                        {{ number_format((float) $coupon->value, 2) }} {{ $coupon->currency }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="bk-pay-coupon-hint">{{ __('Only coupons valid for the selected company and plan can be applied.') }}</div>
                        <div class="invalid-feedback" id="bk-pay-coupon-error">
                            {{ __('This coupon cannot be applied to the selected company and plan.') }}
                        </div>
                    </div>
                    @endif

                    <div class="col-7">
                        <label for="bk-pay-amount" class="form-label fw-semibold">
                            {{ __('Amount') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="number" name="amount" id="bk-pay-amount" class="form-control"
                               min="0" step="0.01" required inputmode="decimal">
                        <div class="form-text text-success d-none" id="bk-pay-discount-hint"></div>
                    </div>
                    <div class="col-5">
                        <label for="bk-pay-currency" class="form-label fw-semibold">{{ __('Currency') }}</label>
                        <select name="currency" id="bk-pay-currency" class="form-select">
                            @foreach(['USD', 'SYP', 'EUR'] as $cur)
                                <option value="{{ $cur }}">{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-7">
                        <label for="bk-pay-method" class="form-label fw-semibold">
                            {{ __('Payment method') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select name="method" id="bk-pay-method" class="form-select" required>
                            @foreach($methods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-5">
                        <label for="bk-pay-date" class="form-label fw-semibold">
                            {{ __('Paid at') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="date" name="paid_at" id="bk-pay-date" class="form-control"
                               value="{{ now()->format('Y-m-d') }}" required>
                    </div>

                    <div class="col-12">
                        <label for="bk-pay-reference" class="form-label fw-semibold">{{ __('Reference') }}</label>
                        <input type="text" name="reference" id="bk-pay-reference" class="form-control"
                               maxlength="255" placeholder="{{ __('Transfer number, receipt…') }}">
                    </div>

                    <div class="col-12">
                        <label for="bk-pay-notes" class="form-label fw-semibold">{{ __('Notes') }}</label>
                        <textarea name="notes" id="bk-pay-notes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-2 mb-0 py-2 d-none"
                             id="bk-pay-expiry-hint" aria-live="polite">
                            <i data-feather="calendar" style="width:16px;height:16px;" class="flex-shrink-0"></i>
                            <span>{{ __('Subscription will be extended to:') }} <strong id="bk-pay-expiry-date" dir="ltr"></strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary rounded-pill" id="bk-pay-submit">
                    {{ __('Record payment') }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const modalEl    = document.getElementById('modal-record-payment');
    const companySel = document.getElementById('bk-pay-company');
    const planSel    = document.getElementById('bk-pay-plan');
    const amountEl   = document.getElementById('bk-pay-amount');
    const currencyEl = document.getElementById('bk-pay-currency');
    const hintEl     = document.getElementById('bk-pay-expiry-hint');
    const hintDateEl = document.getElementById('bk-pay-expiry-date');
    const submitBtn  = document.getElementById('bk-pay-submit');

    function refreshExpiryHint() {
        const planOpt = planSel.selectedOptions[0];
        const compOpt = companySel.selectedOptions[0];

        if (!planOpt || !planOpt.value || !compOpt || !compOpt.value) {
            hintEl.classList.add('d-none');
            return;
        }

        const duration = parseInt(planOpt.dataset.duration, 10) || 0;
        const today    = new Date();
        const expires  = compOpt.dataset.expires ? new Date(compOpt.dataset.expires + 'T00:00:00') : null;
        const base     = (expires && expires > today) ? expires : today;
        const result   = new Date(base.getTime() + duration * 86400000);

        hintDateEl.textContent = result.toISOString().slice(0, 10);
        hintEl.classList.remove('d-none');
    }

    companySel.addEventListener('change', () => {
        const opt = companySel.selectedOptions[0];
        if (opt && opt.dataset.planId) {
            planSel.value = opt.dataset.planId;
            planSel.dispatchEvent(new Event('change'));
        } else {
            refreshExpiryHint();
            applyCoupon();
        }
    });

    const couponSel     = document.getElementById('bk-pay-coupon');
    const couponError   = document.getElementById('bk-pay-coupon-error');
    const discountHint  = document.getElementById('bk-pay-discount-hint');
    const DISCOUNT_TEXT = @json(__('Discount applied: :amount'));

    function couponApplies(opt) {
        const companyId = companySel.value;
        const planOpt   = planSel.selectedOptions[0];
        if (!companyId || !planOpt || !planOpt.value) return false;

        const companies = opt.dataset.companies ? opt.dataset.companies.split(',') : null;
        const plans     = opt.dataset.plans ? opt.dataset.plans.split(',') : null;

        if (companies && !companies.includes(companyId)) return false;
        if (plans && !plans.includes(planOpt.value)) return false;
        if (opt.dataset.type === 'fixed' && opt.dataset.currency && opt.dataset.currency !== planOpt.dataset.currency) return false;

        return true;
    }

    function applyCoupon() {
        if (!couponSel) return;

        const planOpt = planSel.selectedOptions[0];
        const opt     = couponSel.selectedOptions[0];

        couponSel.classList.remove('is-invalid');
        discountHint.classList.add('d-none');
        amountEl.readOnly = false;

        if (!opt || !opt.value || !planOpt || !planOpt.value) return;

        if (!couponApplies(opt)) {
            couponSel.classList.add('is-invalid');
            couponSel.value = '';
            return;
        }

        const price    = parseFloat(planOpt.dataset.price) || 0;
        const discount = opt.dataset.type === 'percent'
            ? price * parseFloat(opt.dataset.value) / 100
            : Math.min(parseFloat(opt.dataset.value), price);

        amountEl.value    = (Math.round((price - discount) * 100) / 100).toFixed(2);
        amountEl.readOnly = true;
        currencyEl.value  = planOpt.dataset.currency;

        discountHint.textContent = DISCOUNT_TEXT.replace(':amount', discount.toFixed(2) + ' ' + planOpt.dataset.currency);
        discountHint.classList.remove('d-none');
    }

    if (couponSel) {
        couponSel.addEventListener('change', applyCoupon);
    }

    planSel.addEventListener('change', () => {
        const opt = planSel.selectedOptions[0];
        if (opt && opt.value) {
            amountEl.value   = opt.dataset.price;
            currencyEl.value = opt.dataset.currency;
        }
        refreshExpiryHint();
        applyCoupon();
    });

    // Rows can preselect a company: data-bs-target="#modal-record-payment" data-company-id="…"
    modalEl.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger && trigger.dataset.companyId) {
            companySel.value = trigger.dataset.companyId;
            companySel.dispatchEvent(new Event('change'));
        }
    });

    modalEl.addEventListener('shown.bs.modal', () => companySel.focus());

    // Prevent double submission
    document.getElementById('bk-payment-form').addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = '…';
    });
})();
</script>
@endpush
