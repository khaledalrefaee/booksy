@extends('employee.layout')
@section('title', __('Log a visit'))
@section('content')

<h1 class="ev-h">{{ __('Log a visit') }}</h1>
<p class="ev-sub">{{ __('Fill this in while you are at the place.') }}</p>

@if($errors->any())
    <div class="ev-flash err">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('employee.field-visits.store') }}" enctype="multipart/form-data" id="ev-form">
    @csrf

    {{-- Silent presence signals --}}
    <input type="hidden" name="lat" id="ev-lat">
    <input type="hidden" name="lng" id="ev-lng">
    <input type="hidden" name="gps_accuracy" id="ev-acc">
    <input type="hidden" name="gps_denied" id="ev-denied" value="0">
    <input type="hidden" name="source" id="ev-source" value="web">

    <div class="ev-gps" id="ev-gps"><i data-feather="loader"></i><span>{{ __('Getting your location…') }}</span></div>

    <div class="ev-field">
        <label class="ev-label" for="place_name">{{ __('Place name') }} *</label>
        <input type="text" id="place_name" name="place_name" class="ev-input" value="{{ old('place_name') }}" required autofocus placeholder="{{ __('e.g. Rose Beauty Salon') }}">
        @error('place_name')<div class="ev-err">{{ $message }}</div>@enderror
    </div>

    <div class="ev-field">
        <label class="ev-label">{{ __('Place type') }}</label>
        <div class="ev-chips">
            @foreach($placeTypes as $key => $label)
                <label class="ev-chip">
                    <input type="radio" name="place_type" value="{{ $key }}" @checked(old('place_type') === $key)>
                    <span>{{ __($label) }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="ev-field">
        <label class="ev-label" for="area">{{ __('Area / district') }}</label>
        <input type="text" id="area" name="area" class="ev-input" value="{{ old('area') }}" placeholder="{{ __('e.g. Al Malqa') }}">
    </div>

    <div class="ev-field">
        <label class="ev-label" for="contact_name">{{ __('Contact person') }}</label>
        <input type="text" id="contact_name" name="contact_name" class="ev-input" value="{{ old('contact_name') }}">
    </div>
    <div class="ev-field">
        <label class="ev-label" for="contact_phone">{{ __('Contact phone') }}</label>
        <input type="tel" id="contact_phone" name="contact_phone" class="ev-input" value="{{ old('contact_phone') }}" dir="ltr">
    </div>

    <div class="ev-field">
        <label class="ev-label">{{ __('Interest level') }}</label>
        <div class="ev-stars" id="ev-stars" role="radiogroup">
            @for($i = 1; $i <= 5; $i++)
                <button type="button" class="ev-star" data-v="{{ $i }}" aria-label="{{ $i }}">★</button>
            @endfor
        </div>
        <input type="hidden" name="interest_level" id="ev-interest" value="{{ old('interest_level') }}">
    </div>

    <div class="ev-field">
        <label class="ev-label">
            <input type="hidden" name="explained_glowrez" value="0">
            <input type="checkbox" name="explained_glowrez" value="1" @checked(old('explained_glowrez')) style="vertical-align:-2px;width:18px;height:18px;accent-color:var(--bk-accent);">
            {{ __('I explained GlowRez to them') }}
        </label>
    </div>

    <div class="ev-field">
        <label class="ev-label" for="current_system">{{ __('System they use now') }}</label>
        <input type="text" id="current_system" name="current_system" class="ev-input" value="{{ old('current_system') }}" placeholder="{{ __('Paper, WhatsApp, another app…') }}">
    </div>

    <div class="ev-field">
        <label class="ev-label" for="problems">{{ __('Problems they mentioned') }}</label>
        <textarea id="problems" name="problems" class="ev-textarea">{{ old('problems') }}</textarea>
    </div>

    <div class="ev-field">
        <label class="ev-label" for="opinion">{{ __('Their opinion') }}</label>
        <textarea id="opinion" name="opinion" class="ev-textarea">{{ old('opinion') }}</textarea>
    </div>

    <div class="ev-field">
        <label class="ev-label" for="follow_up_date">{{ __('Follow-up date') }}</label>
        <input type="date" id="follow_up_date" name="follow_up_date" class="ev-input" value="{{ old('follow_up_date') }}" min="{{ now()->toDateString() }}">
    </div>

    <div class="ev-field">
        <label class="ev-label" for="photo">{{ __('Photo (optional)') }}</label>
        <input type="file" id="photo" name="photo" class="ev-input" accept="image/*" capture="environment">
        <div class="ev-help">{{ __('A quick shot of the storefront helps confirm the visit.') }}</div>
    </div>

    <div class="ev-field">
        <label class="ev-label" for="notes">{{ __('Notes') }}</label>
        <textarea id="notes" name="notes" class="ev-textarea">{{ old('notes') }}</textarea>
    </div>

    <button type="submit" class="ev-btn ev-btn-primary"><i data-feather="check"></i>{{ __('Save visit') }}</button>
</form>

@push('scripts')
<script>
(function () {
    // Source (mobile vs desktop)
    document.getElementById('ev-source').value = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) ? 'mobile' : 'desktop';

    // Interest stars
    var stars = [].slice.call(document.querySelectorAll('#ev-stars .ev-star'));
    var interest = document.getElementById('ev-interest');
    function paint(v) { stars.forEach(function (s) { s.classList.toggle('on', +s.dataset.v <= v); }); }
    stars.forEach(function (s) { s.addEventListener('click', function () { interest.value = s.dataset.v; paint(+s.dataset.v); }); });
    if (interest.value) paint(+interest.value);

    // Geolocation — captured silently, best-effort.
    var gps = document.getElementById('ev-gps');
    function setGps(cls, icon, msg) { gps.className = 'ev-gps ' + cls; gps.innerHTML = '<i data-feather="' + icon + '"></i><span>' + msg + '</span>'; if (window.feather) feather.replace(); }
    if (!navigator.geolocation) {
        document.getElementById('ev-denied').value = '1';
        setGps('err', 'alert-triangle', @json(__('Location not available on this device.')));
    } else {
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('ev-lat').value = pos.coords.latitude.toFixed(7);
            document.getElementById('ev-lng').value = pos.coords.longitude.toFixed(7);
            document.getElementById('ev-acc').value = Math.round(pos.coords.accuracy);
            setGps('ok', 'check-circle', @json(__('Location captured')) + ' · ±' + Math.round(pos.coords.accuracy) + 'm');
        }, function () {
            document.getElementById('ev-denied').value = '1';
            setGps('err', 'alert-triangle', @json(__('Location off — you can still save the visit.')));
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    }
})();
</script>
@endpush
@endsection
