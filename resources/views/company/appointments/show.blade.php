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
        <a href="{{ route('company.appointments.index') }}" class="btn btn-outline-secondary rounded-pill">{{ __('Back to list') }}</a>
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
                            <h5 class="mb-0">{{ $appointment->start_time?->timezone(config('app.timezone'))->format('l, M j, Y · H:i') }}</h5>
                            @if($appointment->end_time)
                                <p class="text-muted mb-0 tx-13 mt-1">{{ __('Ends') }} {{ $appointment->end_time->timezone(config('app.timezone'))->format('H:i') }}</p>
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
                        <dd class="col-sm-8">{{ $appointment->service?->localizedName() ?? '—' }}</dd>
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
                        <dd class="col-sm-8 fw-semibold">{{ number_format((float)$appointment->total_price, 2) }} SAR</dd>
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
                            {{ $appointment->status_changed_at->timezone($tz)->format('Y-m-d H:i') }}
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
            // Clear active state
            statusBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            var status = btn.dataset.status;
            selectedInput.value = status;
            submitBtn.classList.remove('d-none');

            if (status === 'rejected') {
                rejectionWrap.classList.remove('d-none');
            } else {
                rejectionWrap.classList.add('d-none');
            }
        });
    });
})();
</script>
@endpush
@endsection
