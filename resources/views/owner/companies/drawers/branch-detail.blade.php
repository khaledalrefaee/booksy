{{-- Branch detail drawer --}}
@php
    $weekDays = [0=>__('Sunday'),1=>__('Monday'),2=>__('Tuesday'),3=>__('Wednesday'),4=>__('Thursday'),5=>__('Friday'),6=>__('Saturday')];
    $statusTone = fn ($s) => match ($s) { 'active' => 'green', 'maintenance' => 'orange', default => 'muted' };
@endphp

<div class="mb-3">
    <span class="bk-pill bk-pill--{{ $statusTone($branch->status) }}">{{ __($branch->status) }}</span>
    @if ($branch->is_head_office)<span class="bk-pill bk-pill--gold">{{ __('Head office') }}</span>@endif
</div>

<dl class="bk-dl mb-3">
    <dt>{{ __('Address') }}</dt><dd>{{ $branch->address ?: '—' }}</dd>
    <dt>{{ __('Phone') }}</dt><dd>{{ $branch->phone ?: '—' }}</dd>
    <dt>{{ __('Landline') }}</dt><dd>{{ $branch->landline_phone ?: '—' }}</dd>
    <dt>{{ __('Staff') }}</dt><dd>{{ $branch->employees_count }}</dd>
    <dt>{{ __('Services') }}</dt><dd>{{ $branch->services_count }}</dd>
</dl>

{{-- Inline status action --}}
<form action="{{ $ws->url('branches').'/'.$branch->id.'/status' }}" method="post" data-ws-action class="bk-field">
    @csrf @method('PATCH')
    <label>{{ __('Change status') }}</label>
    <div class="d-flex gap-2">
        <select name="status" class="bk-select">
            @foreach (['active','inactive','maintenance'] as $s)
                <option value="{{ $s }}" @selected($branch->status === $s)>{{ __($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bk-btn bk-btn--primary"><i data-feather="check"></i> {{ __('Save') }}</button>
    </div>
</form>

{{-- Working hours --}}
<h4 class="bk-card-title mt-4 mb-2" style="font-size:.85rem;"><i data-feather="clock"></i> {{ __('Working hours') }}</h4>
@if ($branch->workingHours->isEmpty())
    <p class="text-muted small">{{ __('No working hours set.') }}</p>
@else
    <div class="bk-tbl-wrap"><table class="bk-tbl">
        <tbody>
        @foreach ($branch->workingHours as $h)
            <tr>
                <td class="bk-tbl-strong">{{ $weekDays[$h->day_of_week] ?? $h->day_of_week }}</td>
                <td>@if ($h->is_open)<span class="bk-pill bk-pill--green">{{ __('Open') }}</span>@else<span class="bk-pill bk-pill--muted">{{ __('Closed') }}</span>@endif</td>
                <td class="bk-tbl-num text-muted">
                    @if ($h->is_open && $h->open_time && $h->close_time)
                        <span dir="ltr">{{ \Illuminate\Support\Str::of($h->open_time)->substr(0,5) }}–{{ \Illuminate\Support\Str::of($h->close_time)->substr(0,5) }}</span>
                    @else — @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif

{{-- Gallery --}}
@if ($branch->images->isNotEmpty())
    <h4 class="bk-card-title mt-4 mb-2" style="font-size:.85rem;"><i data-feather="image"></i> {{ __('Gallery') }}</h4>
    <div class="d-flex flex-wrap gap-2">
        @foreach ($branch->images->take(8) as $img)
            <img src="{{ asset('storage/'.($img->path ?? $img->image ?? '')) }}" alt=""
                 style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:1px solid var(--bk-border);">
        @endforeach
    </div>
@endif

{{-- QR --}}
@if ($branch->qr_code)
    <h4 class="bk-card-title mt-4 mb-2" style="font-size:.85rem;"><i data-feather="grid"></i> {{ __('Booking QR') }}</h4>
    <img src="{{ asset('storage/'.$branch->qr_code) }}" alt="QR" style="width:120px;height:120px;border-radius:10px;border:1px solid var(--bk-border);background:#fff;padding:6px;">
@endif

<div class="mt-4">
    <form method="post" action="{{ $ws->fullEditorAction() }}"
          onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
        @csrf
        <button type="submit" class="bk-btn bk-btn--gold"><i data-feather="external-link"></i> {{ __('Open full editor') }}</button>
    </form>
</div>
