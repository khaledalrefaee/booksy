{{-- A mobile app's release record. $p = android | ios --}}
@php
    $cur = old("release.{$p}_status", $v["release.{$p}_status"]) ?: 'in_development';
@endphp
<div class="ps-field" data-ps-status-group="{{ $p }}">
    <span class="ps-label" id="ps-{{ $p }}-status-l">{{ __($p === 'android' ? 'Android app status' : 'iOS app status') }}</span>
    <div class="ps-choices cols-3" role="radiogroup" aria-labelledby="ps-{{ $p }}-status-l">
        @foreach($statusOpts as $val => [$t, $d, $ic])
            <label class="ps-choice">
                <input type="radio" name="release[{{ $p }}_status]" value="{{ $val }}" @checked($cur === $val)>
                <span class="ps-choice-ic"><i data-feather="{{ $ic }}"></i></span>
                <span><span class="ps-choice-t d-block">{{ $t }}</span><span class="ps-choice-d d-block">{{ $d }}</span></span>
            </label>
        @endforeach
    </div>
    @error("release.{$p}_status")<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
</div>

<div class="ps-note" data-ps-store-note="{{ $p }}" @if($cur === 'available') hidden @endif>
    <i data-feather="eye-off"></i>
    <div>{{ __('Download buttons and store links stay hidden while the app is not marked Available. You can still prepare the links below in advance.') }}</div>
</div>

<div class="ps-grid">
    <x-ps.field :name="'release.'.$p.'_version'" placeholder="1.0.0" />
    <x-ps.field :name="'release.'.$p.'_build'" placeholder="1" />
    <x-ps.field :name="'release.'.$p.'_min_version'" placeholder="1.0.0" :hint="__('Optional — apps older than this are told to update.')" />
    <x-ps.field :name="'release.'.$p.'_updated_on'" />

    @if($p === 'android')
        <x-ps.field name="release.android_play_url" class="ps-span-2" check="url" placeholder="https://play.google.com/store/apps/details?id=…" :hint="__('Only fill this in once the app is published on Google Play.')" />
        <x-ps.field name="release.android_apk_url" class="ps-span-2" check="url" placeholder="https://…/app.apk" :hint="__('Optional direct download. Must use https://.')" />
    @else
        <x-ps.field name="release.ios_store_url" class="ps-span-2" check="url" placeholder="https://apps.apple.com/app/…" :hint="__('Only fill this in once the app is published on the App Store.')" />
    @endif

    <x-ps.field :name="'release.'.$p.'_notes_ar'" class="ps-span-2" dir="rtl" :rows="4" />
    <x-ps.field :name="'release.'.$p.'_notes_en'" class="ps-span-2" dir="ltr" :rows="4" />
</div>
