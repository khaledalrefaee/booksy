@php
    $tz    = config('app.timezone');
    $color = $appointment->status?->color() ?? '#94a3b8';
    $company = $appointment->company ?? $appointment->branch?->company;
@endphp

<div class="am-drawer-head">
    <div>
        <div class="am-drawer-eyebrow">{{ __('Appointment') }}</div>
        <h2 class="am-drawer-title">{{ $appointment->reference ?? ('#'.$appointment->id) }}</h2>
    </div>
    <button type="button" class="am-drawer-close" data-close-detail aria-label="{{ __('Close') }}"><i data-feather="x"></i></button>
</div>

<div class="am-drawer-body">
    {{-- Hero: status + when --}}
    <div class="am-d-hero">
        <span class="am-d-hero-ic" aria-hidden="true"><i data-feather="{{ $appointment->status?->icon() ?? 'calendar' }}"></i></span>
        <div style="min-width:0;">
            @if($appointment->status)
                <span class="am-status" style="color:{{ $color }};background:color-mix(in srgb, {{ $color }} 13%, transparent);border-color:color-mix(in srgb, {{ $color }} 32%, transparent);">
                    <i data-feather="{{ $appointment->status->icon() }}"></i>{{ $appointment->status->label() }}
                </span>
            @endif
            <div class="am-d-hero-ref" style="margin-top:6px;">
                {{ $appointment->start_time?->timezone($tz)->format('l, M j, Y') ?? '—' }}
            </div>
            <div class="am-muted" style="font-size:.82rem;">
                {{ $appointment->start_time?->timezone($tz)->format('H:i') }}@if($appointment->end_time) – {{ $appointment->end_time->timezone($tz)->format('H:i') }}@endif
            </div>
        </div>
    </div>

    {{-- Business --}}
    <div class="am-d-section">
        <div class="am-d-section-title"><i data-feather="briefcase"></i>{{ __('Business') }}</div>
        <dl class="am-d-list">
            <dt class="am-d-dt">{{ __('Company') }}</dt>
            <dd class="am-d-dd">{{ $company?->localizedName() ?? '—' }}</dd>
            <dt class="am-d-dt">{{ __('Branch') }}</dt>
            <dd class="am-d-dd">{{ $appointment->branch?->localizedName() ?? '—' }}</dd>
        </dl>
    </div>

    {{-- Customer --}}
    <div class="am-d-section">
        <div class="am-d-section-title"><i data-feather="user"></i>{{ __('Customer') }}</div>
        <dl class="am-d-list">
            <dt class="am-d-dt">{{ __('Name') }}</dt>
            <dd class="am-d-dd">{{ $appointment->displayName() }}</dd>
            @if($appointment->customer_phone || $appointment->customer?->phone)
                <dt class="am-d-dt">{{ __('Phone') }}</dt>
                <dd class="am-d-dd" dir="ltr">{{ $appointment->customer_phone ?? $appointment->customer?->phone }}</dd>
            @endif
        </dl>
    </div>

    {{-- Service & staff --}}
    <div class="am-d-section">
        <div class="am-d-section-title"><i data-feather="scissors"></i>{{ __('Service') }}</div>
        <dl class="am-d-list">
            <dt class="am-d-dt">{{ __('Service') }}</dt>
            <dd class="am-d-dd">{{ $appointment->service?->localizedName() ?? '—' }}</dd>
            @if($appointment->service?->serviceCategory)
                <dt class="am-d-dt">{{ __('Category') }}</dt>
                <dd class="am-d-dd">{{ $appointment->service->serviceCategory->localizedName() }}</dd>
            @endif
            <dt class="am-d-dt">{{ __('Staff') }}</dt>
            <dd class="am-d-dd">
                {{ $appointment->employee?->localizedName() ?? __('Any available') }}
                @unless($appointment->employee_requested)
                    <span class="am-muted" style="font-size:.75rem;">({{ __('not requested') }})</span>
                @endunless
            </dd>
        </dl>
    </div>

    {{-- Payment --}}
    <div class="am-d-section">
        <div class="am-d-section-title"><i data-feather="credit-card"></i>{{ __('Payment') }}</div>
        <dl class="am-d-list">
            <dt class="am-d-dt">{{ __('Total') }}</dt>
            <dd class="am-d-dd">{{ number_format((float) $appointment->total_price, 2) }} {{ config('app.currency', 'SAR') }}</dd>
            @if((float) $appointment->tip_amount > 0)
                <dt class="am-d-dt">{{ __('Tip') }}</dt>
                <dd class="am-d-dd">{{ number_format((float) $appointment->tip_amount, 2) }}</dd>
            @endif
            <dt class="am-d-dt">{{ __('Payment status') }}</dt>
            <dd class="am-d-dd">{{ __(ucfirst($appointment->payment_status)) }}</dd>
        </dl>
    </div>

    {{-- Handling --}}
    @if($appointment->handledBy || $appointment->handled_at)
        <div class="am-d-section">
            <div class="am-d-section-title"><i data-feather="user-check"></i>{{ __('Handling') }}</div>
            <dl class="am-d-list">
                <dt class="am-d-dt">{{ __('By') }}</dt>
                <dd class="am-d-dd">{{ $appointment->handledBy?->localizedName() ?? '—' }}</dd>
                <dt class="am-d-dt">{{ __('At') }}</dt>
                <dd class="am-d-dd">{{ $appointment->handled_at?->timezone($tz)->format('Y-m-d H:i') ?? '—' }}</dd>
            </dl>
        </div>
    @endif

    {{-- Notes --}}
    @if($appointment->notes)
        <div class="am-d-section">
            <div class="am-d-section-title"><i data-feather="file-text"></i>{{ __('Notes') }}</div>
            <div class="am-d-note">{{ $appointment->notes }}</div>
        </div>
    @endif

    {{-- Rejection reason --}}
    @if($appointment->rejection_reason)
        <div class="am-d-section">
            <div class="am-d-section-title" style="color:var(--bk-danger);"><i data-feather="alert-triangle"></i>{{ __('Rejection reason') }}</div>
            <div class="am-d-note is-danger">{{ $appointment->rejection_reason }}</div>
        </div>
    @endif

    {{-- Review --}}
    @if($appointment->review)
        <div class="am-d-section">
            <div class="am-d-section-title"><i data-feather="star"></i>{{ __('Review') }}</div>
            <dl class="am-d-list">
                <dt class="am-d-dt">{{ __('Rating') }}</dt>
                <dd class="am-d-dd">{{ $appointment->review->rating }}/5</dd>
            </dl>
            @if($appointment->review->comment)
                <div class="am-d-note" style="margin-top:10px;">{{ $appointment->review->comment }}</div>
            @endif
        </div>
    @endif
</div>

<div class="am-drawer-foot">
    <a href="{{ route('owner.appointments.show', $appointment) }}" class="am-btn am-btn-primary">
        <i data-feather="external-link"></i>{{ __('Open full page') }}
    </a>
</div>
