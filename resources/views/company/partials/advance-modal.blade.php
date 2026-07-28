{{--
    Shared "Grant salary advance" modal + its JS.
    Params:
      $advEmployees          — collection of active employees (compensation loaded)
      $preselectedEmployeeId — optional employee id to preselect
      $autoOpen              — optional: open the modal on page load (default false)
--}}
@php
    $preselectedEmployeeId = $preselectedEmployeeId ?? null;
    $autoOpen = $autoOpen ?? false;
@endphp

<div class="modal fade" id="advanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content rounded-4" style="background:var(--card-bg, #1a1f2e);">
            <form method="POST" action="{{ route('company.advances.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0 px-4 pt-3">
                    <h6 class="modal-title fw-bold">💵 {{ __('Grant salary advance') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold tx-12">{{ __('Employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" id="adv-employee" class="form-select form-select-sm" required onchange="advSyncCurrency()">
                            <option value="">{{ __('Select employee…') }}</option>
                            @foreach($advEmployees as $e)
                            <option value="{{ $e->id }}" data-currency="{{ $e->compensation?->currency ?? config('booksy.default_currency', 'SYP') }}"
                                    {{ $preselectedEmployeeId === $e->id ? 'selected' : '' }}>
                                {{ $e->localizedName() }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold tx-12">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="adv-amount" class="form-control form-control-sm fw-bold"
                                   min="0.01" step="0.01" required oninput="advPreview()">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold tx-12">{{ __('Currency') }}</label>
                            <select name="currency" id="adv-currency" class="form-select form-select-sm" onchange="advPreview()">
                                @foreach(config('booksy.currencies', []) as $code => $cur)
                                <option value="{{ $code }}" {{ $code === config('booksy.default_currency', 'SYP') ? 'selected' : '' }}>
                                    {{ $cur['symbol'] }} {{ $code }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('Advance date') }}</label>
                            <input type="date" name="advance_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('Payment method') }}</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="cash">💵 {{ __('Cash') }}</option>
                                <option value="bank_transfer">🏦 {{ __('Bank transfer') }}</option>
                                <option value="card">💳 {{ __('Card') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('Installments') }} <span class="text-danger">*</span></label>
                            <select name="installments_count" id="adv-installments" class="form-select form-select-sm" onchange="advPreview()">
                                @foreach([1,2,3,4,6,8,10,12,18,24] as $n)
                                <option value="{{ $n }}" {{ $n === 1 ? 'selected' : '' }}>{{ $n }} {{ __('installment(s)') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold tx-12">{{ __('First deduction month') }}</label>
                            <input type="month" name="first_installment" id="adv-first-month" class="form-control form-control-sm"
                                   value="{{ now()->addMonth()->format('Y-m') }}" required onchange="advPreview()">
                        </div>
                    </div>

                    {{-- Live preview --}}
                    <div id="adv-preview" class="p-3 rounded-3 mb-3 tx-12" style="background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);display:none;">
                        <div class="fw-bold mb-1" style="color:#f59e0b;">📋 {{ __('Repayment plan') }}</div>
                        <div id="adv-preview-text" class="text-muted" style="line-height:1.8;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold tx-12">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('e.g. emergency, wedding…') }}" value="{{ old('notes') }}">
                    </div>

                    <div class="tx-11 text-muted mb-3" style="opacity:.75;">
                        ℹ️ {{ __('The amount is taken from the branch cash box now, and each installment is deducted automatically from payroll in its month.') }}
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.07);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm rounded-pill px-4 fw-bold" style="background:#f59e0b;color:#fff;border:none;">
                            💵 {{ __('Grant advance') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function advSyncCurrency() {
    var empSel = document.getElementById('adv-employee');
    var cur = empSel.options[empSel.selectedIndex]?.dataset.currency;
    if (cur) document.getElementById('adv-currency').value = cur;
    advPreview();
}

function advPreview() {
    var amount = parseFloat(document.getElementById('adv-amount').value) || 0;
    var count  = parseInt(document.getElementById('adv-installments').value) || 1;
    var month  = document.getElementById('adv-first-month').value;
    var cur    = document.getElementById('adv-currency').value;
    var box    = document.getElementById('adv-preview');
    var txt    = document.getElementById('adv-preview-text');

    if (amount <= 0 || !month) { box.style.display = 'none'; return; }

    var per = Math.round((amount / count) * 100) / 100;
    var start = new Date(month + '-01T00:00:00');
    var end = new Date(start); end.setMonth(end.getMonth() + count - 1);
    var locale = '{{ app()->getLocale() === "ar" ? "ar" : "en" }}';
    var fmt = function (d) { return d.toLocaleDateString(locale, { month: 'long', year: 'numeric' }); };

    txt.innerHTML = '{{ __("Monthly deduction") }}: <strong>' + per.toLocaleString() + ' ' + cur + '</strong>'
        + ' × ' + count + ' {{ __("installment(s)") }}'
        + '<br>{{ __("From") }} <strong>' + fmt(start) + '</strong>'
        + (count > 1 ? ' {{ __("until") }} <strong>' + fmt(end) + '</strong>' : '');
    box.style.display = 'block';
}

advSyncCurrency();
@if($autoOpen)
new bootstrap.Modal(document.getElementById('advanceModal')).show();
@endif
</script>
@endpush
