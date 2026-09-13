@extends('employee.layout')
@section('title', $visit->place_name)
@section('content')

<a href="{{ url()->previous() }}" class="ev-btn ev-btn-ghost" style="width:auto;height:40px;padding:0 14px;margin-bottom:14px;">
    <i data-feather="{{ app()->getLocale() === 'ar' ? 'arrow-right' : 'arrow-left' }}"></i>{{ __('Back') }}
</a>

<h1 class="ev-h">{{ $visit->place_name }}</h1>
<p class="ev-sub">
    {{ $visit->visited_at?->translatedFormat('l, M j · H:i') }}
    @if($visit->place_type) · {{ __(\App\Models\FieldVisit::PLACE_TYPES[$visit->place_type] ?? $visit->place_type) }}@endif
</p>

@php
    $rows = array_filter([
        __('Area')            => $visit->area,
        __('Contact person')  => $visit->contact_name,
        __('Contact phone')   => $visit->contact_phone,
        __('Interest level')  => $visit->interest_level ? str_repeat('★', $visit->interest_level) : null,
        __('Explained GlowRez') => $visit->explained_glowrez === null ? null : ($visit->explained_glowrez ? __('Yes') : __('No')),
        __('System they use now') => $visit->current_system,
        __('Follow-up date')  => $visit->follow_up_date?->translatedFormat('M j, Y'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="ev-card" style="padding:6px 4px;margin-bottom:16px;">
    @foreach($rows as $k => $v)
        <div class="ev-row" style="padding:11px 14px;">
            <span class="ev-row-body"><span class="ev-row-meta" style="margin:0;">{{ $k }}</span><span class="ev-row-title" style="font-weight:600;font-size:.9rem;white-space:normal;" dir="auto">{{ $v }}</span></span>
        </div>
    @endforeach
</div>

@foreach(['problems' => __('Problems'), 'opinion' => __('Opinion'), 'notes' => __('Notes')] as $field => $label)
    @if($visit->$field)
        <div class="ev-label" style="margin-bottom:6px;">{{ $label }}</div>
        <div class="ev-card" style="padding:13px 15px;margin-bottom:14px;font-size:.9rem;line-height:1.55;color:var(--bk-text-soft);">{{ $visit->$field }}</div>
    @endif
@endforeach

@if($visit->photo_path)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($visit->photo_path) }}" alt="" style="width:100%;border-radius:14px;margin-bottom:16px;border:1px solid var(--bk-border);">
@endif

@if($visit->hasLocation())
    <a href="https://www.google.com/maps?q={{ $visit->lat }},{{ $visit->lng }}" target="_blank" rel="noopener"
       class="ev-btn ev-btn-ghost" style="margin-bottom:16px;"><i data-feather="map-pin"></i>{{ __('View on map') }}</a>
@endif

{{-- Check-out --}}
@if($visit->checked_out_at)
    <div class="ev-flash ok" style="text-align:center;">
        <i data-feather="check-circle" style="width:15px;height:15px;vertical-align:-2px;"></i>
        {{ __('Checked out :time', ['time' => $visit->checked_out_at->diffForHumans()]) }}
        @if($visit->dwell_seconds) · {{ \Carbon\CarbonInterval::seconds($visit->dwell_seconds)->cascade()->forHumans(['short' => true, 'parts' => 2]) }}@endif
    </div>
@else
    <form method="POST" action="{{ route('employee.field-visits.checkout', $visit) }}" id="ev-checkout">
        @csrf @method('PATCH')
        <input type="hidden" name="lat" id="co-lat"><input type="hidden" name="lng" id="co-lng"><input type="hidden" name="gps_accuracy" id="co-acc">
        <button type="submit" class="ev-btn ev-btn-primary"><i data-feather="log-out"></i>{{ __('Check out') }}</button>
    </form>
    @push('scripts')
    <script>
    (function () {
        var f = document.getElementById('ev-checkout');
        f.addEventListener('submit', function () {
            // best-effort checkout location; never blocks the submit
            if (navigator.geolocation) {
                try {
                    navigator.geolocation.getCurrentPosition(function (p) {
                        document.getElementById('co-lat').value = p.coords.latitude.toFixed(7);
                        document.getElementById('co-lng').value = p.coords.longitude.toFixed(7);
                        document.getElementById('co-acc').value = Math.round(p.coords.accuracy);
                    });
                } catch (e) {}
            }
        });
    })();
    </script>
    @endpush
@endif

@endsection
