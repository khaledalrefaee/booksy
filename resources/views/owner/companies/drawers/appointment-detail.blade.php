{{-- Appointment detail drawer --}}
@php $tz = config('app.timezone'); @endphp
<div class="mb-3"><x-appointment-status :status="$appointment->status" /></div>

<dl class="bk-dl mb-3">
    <dt>{{ __('When') }}</dt><dd>{{ $appointment->start_time?->timezone($tz)->format('Y-m-d H:i') }}</dd>
    <dt>{{ __('Branch') }}</dt><dd>{{ $appointment->branch?->localizedName() ?? '—' }}</dd>
    <dt>{{ __('Service') }}</dt><dd>{{ $appointment->service?->localizedName() ?? '—' }}</dd>
    <dt>{{ __('Staff') }}</dt><dd>{{ $appointment->employee?->localizedName() ?? '—' }}</dd>
    <dt>{{ __('Customer') }}</dt><dd>{{ $appointment->customer?->name ?? $appointment->customer_name ?? '—' }}</dd>
    <dt>{{ __('Phone') }}</dt><dd>{{ $appointment->customer?->phone ?? $appointment->customer_phone ?? '—' }}</dd>
    <dt>{{ __('Total') }}</dt><dd>{{ $ws->money($appointment->total_price) }}</dd>
    <dt>{{ __('Payment') }}</dt><dd>{{ __($appointment->payment_status ?? '—') }}</dd>
</dl>

@if ($appointment->notes)
    <p class="text-muted small">{{ $appointment->notes }}</p>
@endif

@can('owner-can', 'appointments.manage')
<div class="bk-field mt-3">
    <label>{{ __('Change status') }}</label>
    <form action="{{ $ws->url('appointments').'/'.$appointment->id.'/status' }}" method="post" data-ws-action>
        @csrf @method('PATCH')
        <div class="d-flex flex-wrap gap-2">
            @foreach ($safeTransitions as $s)
                <button name="status" value="{{ $s }}" class="bk-btn bk-btn--sm {{ $s === 'cancelled_by_customer' ? 'bk-btn--danger' : ($s === 'confirmed' ? 'bk-btn--primary' : '') }}">
                    {{ __($s) }}
                </button>
            @endforeach
        </div>
    </form>
    <p class="text-muted small mt-2">{{ __('Complex checkout & payment run in the full editor.') }}</p>
</div>
@endcan

<div class="d-flex gap-2 mt-3">
    <a href="{{ route('owner.appointments.show', $appointment) }}" class="bk-btn bk-btn--ghost bk-btn--sm"><i data-feather="external-link"></i> {{ __('Full page') }}</a>
    <form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
        @csrf<button type="submit" class="bk-btn bk-btn--gold bk-btn--sm"><i data-feather="edit"></i> {{ __('Open full editor') }}</button>
    </form>
</div>
