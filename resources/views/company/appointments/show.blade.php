@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Appointment') }} #{{ $appointment->id }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.appointments.index') }}">{{ __('Appointments') }}</a></li>
                    <li class="breadcrumb-item active">#{{ $appointment->id }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($appointment->invoice)
                <a href="{{ route('company.invoices.show', $appointment->invoice) }}" class="btn btn-outline-success rounded-pill">
                    <i data-feather="file-text" style="width:14px;"></i> {{ __('Invoice') }}
                </a>
            @elseif($appointment->status === 'completed')
                <form method="POST" action="{{ route('company.appointments.invoice.store', $appointment) }}">
                    @csrf
                    <button class="btn btn-outline-primary rounded-pill">
                        <i data-feather="file-plus" style="width:14px;"></i> {{ __('Generate Invoice') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('company.appointments.index') }}" class="btn btn-outline-secondary rounded-pill">{{ __('Back to list') }}</a>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row g-4">
        {{-- Main details --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <p class="text-muted text-uppercase tx-11 fw-semibold mb-1">{{ __('Scheduled') }}</p>
                            <h5 class="mb-0">{{ $appointment->start_time?->format('l, M j, Y · H:i') }}</h5>
                            @if($appointment->end_time)
                                <p class="text-muted mb-0 tx-13 mt-1">{{ __('Ends') }} {{ $appointment->end_time->format('H:i') }}</p>
                            @endif
                        </div>
                        @php
                            $badge = match($appointment->status) {
                                'pending'   => 'warning',
                                'confirmed' => 'success',
                                'completed' => 'primary',
                                default     => 'secondary',
                            };
                        @endphp
                        <span class="badge rounded-pill bg-{{ $badge }} px-3 py-2 fs-6">{{ __($appointment->status) }}</span>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">{{ __('Branch') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->branch?->localizedName() ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Service') }}</dt>
                        <dd class="col-sm-8">
                            @if($appointment->appointmentServices->isNotEmpty())
                                @foreach($appointment->appointmentServices as $as)
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $as->service?->localizedName() ?? '—' }}</span>
                                        <span class="text-muted tx-12 ms-3">{{ $as->employee?->localizedName() ?? '—' }} · {{ number_format((float)$as->price,2) }}</span>
                                    </div>
                                @endforeach
                            @else
                                {{ $appointment->service?->localizedName() ?? '—' }}
                            @endif
                        </dd>
                        <dt class="col-sm-4 text-muted">{{ __('Customer') }}</dt>
                        <dd class="col-sm-8">
                            {{ $appointment->customer?->name ?? '—' }}
                            @if($appointment->customer?->email && !str_ends_with($appointment->customer->email, '@booksy.local'))
                                <span class="text-muted">· {{ $appointment->customer->email }}</span>
                            @endif
                        </dd>
                        <dt class="col-sm-4 text-muted">{{ __('Staff') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->employee?->localizedName() ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Total') }}</dt>
                        <dd class="col-sm-8 fw-semibold">
                            {{ number_format((float)$appointment->total_price, 2) }}
                            {{ config('booksy.currencies.'.($appointment->service?->currency ?? config('booksy.default_currency','SYP')).'.symbol', $appointment->service?->currency ?? 'SYP') }}
                        </dd>
                        <dt class="col-sm-4 text-muted">{{ __('Payment') }}</dt>
                        <dd class="col-sm-8">
                            @php
                                $payBadge = match($appointment->payment_status) {
                                    'paid'    => 'success',
                                    'partial' => 'warning',
                                    default   => 'secondary',
                                };
                            @endphp
                            <span class="badge rounded-pill bg-{{ $payBadge }}">{{ __($appointment->payment_status) }}</span>
                        </dd>
                    </dl>

                    @if($appointment->notes)
                        <hr class="my-4">
                        <p class="text-muted text-uppercase tx-11 fw-semibold mb-2">{{ __('Notes') }}</p>
                        <p class="mb-0">{{ $appointment->notes }}</p>
                    @endif

                    @if($appointment->rejection_reason)
                        <hr class="my-4">
                        <p class="text-danger text-uppercase tx-11 fw-semibold mb-2">{{ __('Rejection reason') }}</p>
                        <p class="mb-0">{{ $appointment->rejection_reason }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Side panel: actions + review --}}
        <div class="col-lg-5">

            {{-- Status change --}}
            @php
                $allowedTransitions = match($appointment->status) {
                    'pending'   => ['confirmed', 'cancelled', 'rejected'],
                    'confirmed' => ['completed', 'cancelled', 'no_show'],
                    default     => [],
                };
            @endphp

            @if(count($allowedTransitions) > 0)
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">{{ __('Change status') }}</h6>

                    <form method="POST" action="{{ route('company.appointments.update-status', $appointment) }}" id="status-form">
                        @csrf @method('PATCH')

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($allowedTransitions as $newStatus)
                                @php
                                    $btnClass = match($newStatus) {
                                        'confirmed' => 'btn-success',
                                        'completed' => 'btn-primary',
                                        'cancelled' => 'btn-outline-secondary',
                                        'rejected'  => 'btn-outline-danger',
                                        'no_show'   => 'btn-outline-warning',
                                        default     => 'btn-secondary',
                                    };
                                @endphp
                                <button type="button"
                                    class="btn btn-sm {{ $btnClass }} rounded-pill js-status-btn"
                                    data-status="{{ $newStatus }}">
                                    {{ __($newStatus) }}
                                </button>
                            @endforeach
                        </div>

                        <input type="hidden" name="status" id="selected-status">

                        {{-- Rejection reason (shown only when rejected selected) --}}
                        <div id="rejection-reason-wrap" class="d-none mb-3">
                            <label class="form-label fw-semibold">{{ __('Rejection reason') }} <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="{{ __('Reason for rejection...') }}"></textarea>
                        </div>

                        @error('status')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @error('rejection_reason')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                        <button type="submit" id="submit-status-btn" class="btn btn-primary rounded-pill px-4 d-none">
                            {{ __('Confirm change') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Status audit trail --}}
            @if($appointment->status_changed_at)
            @php
                $auditTypeMap = [
                    'company'  => ['icon'=>'🏢','label-ar'=>'مدير الشركة', 'label-en'=>'Company Admin'],
                    'employee' => ['icon'=>'👤','label-ar'=>'موظف',        'label-en'=>'Employee'],
                    'owner'    => ['icon'=>'👑','label-ar'=>'المالك',       'label-en'=>'Owner'],
                    'customer' => ['icon'=>'🙋','label-ar'=>'العميل',       'label-en'=>'Customer'],
                ];
                $auditType = $auditTypeMap[$appointment->status_changed_by_type] ?? ['icon'=>'❓','label-ar'=>'غير معروف','label-en'=>'Unknown'];
                $auditLabel = app()->getLocale() === 'ar' ? $auditType['label-ar'] : $auditType['label-en'];
                $tz = config('app.timezone');
            @endphp
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-left:3px solid #7c3aed !important;">
                <div class="card-body p-4">
                    <h6 class="mb-3 d-flex align-items-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        {{ app()->getLocale() === 'ar' ? 'سجل تغيير الحالة' : 'Status Change Audit' }}
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-2" style="font-size:.85rem;">
                            <span style="font-size:1.1rem;">{{ $auditType['icon'] }}</span>
                            <div>
                                <span class="text-muted">{{ app()->getLocale() === 'ar' ? 'تم بواسطة:' : 'Changed by:' }}</span>
                                <strong class="ms-1">{{ $appointment->status_changed_by_name ?? '—' }}</strong>
                                <span class="badge rounded-pill ms-1" style="background:#7c3aed22;color:#7c3aed;font-size:.68rem;font-weight:700;">{{ $auditLabel }}</span>
                            </div>
                        </div>
                        <div style="font-size:.82rem;color:var(--text-muted, #6c757d);">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $appointment->status_changed_at->format('Y-m-d H:i') }}
                        </div>
                        @if($appointment->status_previous)
                        <div style="font-size:.8rem;">
                            <span class="text-muted">{{ app()->getLocale() === 'ar' ? 'الحالة السابقة:' : 'Previous status:' }}</span>
                            @php
                                $prevColor = match($appointment->status_previous) {
                                    'pending'=>'#f59e0b','confirmed'=>'#10b981','completed'=>'#6366f1',
                                    'cancelled'=>'#6b7280','rejected'=>'#ef4444','no_show'=>'#94a3b8', default=>'#94a3b8'
                                };
                            @endphp
                            <span class="badge rounded-pill ms-1" style="background:{{ $prevColor }}22;color:{{ $prevColor }};font-size:.7rem;font-weight:700;">
                                {{ __($appointment->status_previous) }}
                            </span>
                            <span class="mx-1">→</span>
                            @php
                                $curColor = match($appointment->status) {
                                    'pending'=>'#f59e0b','confirmed'=>'#10b981','completed'=>'#6366f1',
                                    'cancelled'=>'#6b7280','rejected'=>'#ef4444','no_show'=>'#94a3b8', default=>'#94a3b8'
                                };
                            @endphp
                            <span class="badge rounded-pill" style="background:{{ $curColor }}22;color:{{ $curColor }};font-size:.7rem;font-weight:700;">
                                {{ __($appointment->status) }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Review --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">{{ __('Review') }}</h6>
                    @if($appointment->review)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark fs-6">⭐ {{ $appointment->review->rating }}/5</span>
                        </div>
                        <p class="text-muted mb-0">{{ $appointment->review->comment ?: __('No comment.') }}</p>
                    @else
                        <p class="text-muted mb-0">{{ __('No review yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Payment modal (shown when "completed" is clicked) ───────────────── --}}
@php
    $svcCurrency = $appointment->service?->currency ?? config('booksy.default_currency','SYP');
    $svcSymbol   = config("booksy.currencies.{$svcCurrency}.symbol", $svcCurrency);
    $apptPrice   = (float) $appointment->total_price;
    $overpayTo   = $appointment->branch?->overpayment_to ?? 'treasury';
    $overpayLbl  = $overpayTo === 'employee' ? __('goes to employee tip') : __('added to treasury');
@endphp

<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content" style="border:none;border-radius:20px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">
                        💳 {{ __('Collect Payment') }}
                    </h5>
                    <div style="font-size:12px;opacity:.5;margin-top:2px;">
                        {{ $appointment->customer?->name ?? __('Customer') }}
                        · {{ $appointment->service?->localizedName() ?? '' }}
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('company.appointments.update-status', $appointment) }}" id="payment-form">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="completed">

                <div class="modal-body px-4 py-3">

                    {{-- Price banner --}}
                    <div style="background:rgba(102,126,234,.08);border:1.5px solid rgba(102,126,234,.15);border-radius:14px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-size:12px;opacity:.5;">{{ __('Service price') }}</div>
                        <div style="font-size:22px;font-weight:900;color:#667eea;">
                            {{ number_format($apptPrice, 2) }}
                            <span style="font-size:13px;opacity:.6;">{{ $svcSymbol }}</span>
                        </div>
                    </div>

                    {{-- Amount paid --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            {{ __('Amount paid by customer') }}
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-weight:700;font-size:13px;">{{ $svcSymbol }}</span>
                            <input type="number" name="paid_amount" id="pay-amount"
                                   class="form-control form-control-lg fw-bold"
                                   style="font-size:20px;"
                                   value="{{ $apptPrice }}"
                                   min="0" step="0.01" required
                                   oninput="calcDiff()">
                        </div>

                        {{-- Diff hint --}}
                        <div id="pay-diff" style="display:none;margin-top:8px;padding:8px 12px;border-radius:10px;font-size:12px;font-weight:600;"></div>
                    </div>

                    {{-- Payment method --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Payment method') }}</label>
                        <div class="d-flex gap-2">
                            @foreach(['cash'=>['💵',__('Cash')],'card'=>['💳',__('Card')],'later'=>['📋',__('Pay later')]] as $val=>[$ico,$lbl])
                            <label style="flex:1;cursor:pointer;">
                                <input type="radio" name="payment_method" value="{{ $val }}"
                                       class="d-none pay-method-radio" {{ $val==='cash' ? 'checked' : '' }}>
                                <div class="pay-method-btn text-center {{ $val==='cash' ? 'active' : '' }}"
                                     style="border:2px solid rgba(255,255,255,.1);border-radius:12px;padding:10px 6px;font-size:11px;font-weight:700;transition:all .15s;cursor:pointer;background:rgba(255,255,255,.03);">
                                    <div style="font-size:20px;margin-bottom:3px;">{{ $ico }}</div>
                                    {{ $lbl }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            {{ __('Notes') }}
                            <span style="font-weight:400;opacity:.5;font-size:11px;">({{ __('optional') }})</span>
                        </label>
                        <input type="text" name="pay_notes" class="form-control"
                               placeholder="{{ __('e.g. paid extra 200 tip') }}">
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn rounded-pill px-4"
                            style="background:rgba(255,255,255,.07);font-size:13px;font-weight:600;"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn rounded-pill px-5 fw-bold"
                            style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;font-size:13px;">
                        ✅ {{ __('Complete & Record') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var statusBtns    = document.querySelectorAll('.js-status-btn');
    var selectedInput = document.getElementById('selected-status');
    var submitBtn     = document.getElementById('submit-status-btn');
    var rejectionWrap = document.getElementById('rejection-reason-wrap');

    if (!statusBtns.length) return;

    statusBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            statusBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            var status = btn.dataset.status;

            // ── Intercept "completed" → show payment modal ─────────────────
            if (status === 'completed') {
                var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
                modal.show();
                return; // don't show the regular submit button
            }

            selectedInput.value = status;
            submitBtn.classList.remove('d-none');

            if (status === 'rejected') {
                rejectionWrap.classList.remove('d-none');
            } else {
                rejectionWrap.classList.add('d-none');
            }
        });
    });

    // ── Payment method pill toggle ─────────────────────────────────────────
    document.querySelectorAll('.pay-method-radio').forEach(function(r) {
        r.addEventListener('change', function() {
            document.querySelectorAll('.pay-method-btn').forEach(function(b) {
                b.style.borderColor = 'rgba(255,255,255,.1)';
                b.style.background  = 'rgba(255,255,255,.03)';
                b.classList.remove('active');
            });
            var btn = this.closest('label').querySelector('.pay-method-btn');
            btn.style.borderColor = '#667eea';
            btn.style.background  = 'rgba(102,126,234,.12)';
            btn.classList.add('active');
        });
    });

    // ── Diff calculator ────────────────────────────────────────────────────
    var servicePrice = {{ $apptPrice }};
    var overpayLabel = '{{ $overpayLbl }}';
    var currency     = '{{ $svcSymbol }}';

    window.calcDiff = function() {
        var paid = parseFloat(document.getElementById('pay-amount').value) || 0;
        var diff = paid - servicePrice;
        var hint = document.getElementById('pay-diff');

        if (Math.abs(diff) < 0.01) { hint.style.display = 'none'; return; }

        hint.style.display = '';
        if (diff > 0) {
            hint.style.background = 'rgba(34,197,94,.1)';
            hint.style.color      = '#22c55e';
            hint.textContent = '⬆ {{ __("Overpayment") }}: +' + diff.toFixed(2) + ' ' + currency + ' — ' + overpayLabel;
        } else {
            hint.style.background = 'rgba(239,68,68,.1)';
            hint.style.color      = '#ef4444';
            hint.textContent = '⬇ {{ __("Underpayment") }}: ' + Math.abs(diff).toFixed(2) + ' ' + currency + ' {{ __("(debt recorded)") }}';
        }
    };
})();
</script>
@endpush
@endsection
