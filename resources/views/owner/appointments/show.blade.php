@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-2">{{ __('Appointment') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('owner.appointments.index') }}">{{ __('Appointments') }}</a></li>
                    <li class="breadcrumb-item active">#{{ $appointment->id }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('owner.appointments.index') }}" class="btn btn-outline-secondary rounded-pill">{{ __('Back to list') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <p class="text-muted text-uppercase tx-11 fw-semibold mb-1">{{ __('Scheduled') }}</p>
                            <h5 class="mb-0">{{ $appointment->start_time?->timezone(config('app.timezone'))->format('l, M j, Y · H:i') }}</h5>
                            @if ($appointment->end_time)
                                <p class="text-muted mb-0 tx-13 mt-1">{{ __('Ends') }} {{ $appointment->end_time->timezone(config('app.timezone'))->format('H:i') }}</p>
                            @endif
                        </div>
                        @php
                            $badge = match ($appointment->status) {
                                'pending' => 'warning',
                                'confirmed' => 'success',
                                'completed' => 'primary',
                                'cancelled', 'rejected', 'no_show' => 'secondary',
                                default => 'info',
                            };
                        @endphp
                        <span class="badge rounded-pill bg-{{ $badge }} px-3 py-2">{{ __($appointment->status) }}</span>
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">{{ __('Branch') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->branch?->localizedName() ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Service') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->service?->localizedName() ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Customer') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->customer?->name ?? '—' }} @if($appointment->customer?->email)<span class="text-muted">· {{ $appointment->customer->email }}</span>@endif</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Staff') }}</dt>
                        <dd class="col-sm-8">{{ $appointment->employee?->localizedName() ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Total') }}</dt>
                        <dd class="col-sm-8 fw-semibold">{{ number_format((float) $appointment->total_price, 2) }}</dd>
                        <dt class="col-sm-4 text-muted">{{ __('Payment') }}</dt>
                        <dd class="col-sm-8">{{ __($appointment->payment_status) }}</dd>
                    </dl>
                    @if ($appointment->notes)
                        <hr class="my-4">
                        <p class="text-muted text-uppercase tx-11 fw-semibold mb-2">{{ __('Notes') }}</p>
                        <p class="mb-0">{{ $appointment->notes }}</p>
                    @endif
                    @if ($appointment->rejection_reason)
                        <hr class="my-4">
                        <p class="text-danger text-uppercase tx-11 fw-semibold mb-2">{{ __('Rejection reason') }}</p>
                        <p class="mb-0">{{ $appointment->rejection_reason }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">{{ __('Handling') }}</h6>
                    <p class="mb-1"><span class="text-muted">{{ __('By') }}:</span> {{ $appointment->handledBy?->localizedName() ?? '—' }}</p>
                    <p class="mb-0"><span class="text-muted">{{ __('At') }}:</span> {{ $appointment->handled_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</p>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">{{ __('Review') }}</h6>
                    @if ($appointment->review)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark">{{ $appointment->review->rating }}/5</span>
                        </div>
                        <p class="text-muted mb-0">{{ $appointment->review->comment ?: __('No comment.') }}</p>
                    @else
                        <p class="text-muted mb-0">{{ __('No review for this appointment yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
