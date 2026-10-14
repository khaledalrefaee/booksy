@php
    $statusOpts = [
        'in_development' => [__('In development'), __('Not published yet. No download button is shown anywhere.'), 'code'],
        'available'      => [__('Available'),      __('Published. Store links are offered to users.'),            'check-circle'],
        'unavailable'    => [__('Not available'),  __('Withdrawn or temporarily unavailable. No download button is shown.'), 'slash'],
    ];
    $modeLabel = ['normal' => __('Live'), 'maintenance' => __('Under maintenance'), 'paused' => __('Temporarily paused')][$maintenance['mode']] ?? __('Live');
    $webUpdated = $v['release.web_updated_on'];
@endphp
<x-ps.panel id="releases" :active="$tab === 'releases'"
    :action="route('owner.settings.update', 'releases')"
    :title="__('Website & app releases')"
    :desc="__('The version information you record here. It is a record for your team and the apps — it does not publish, deploy or update anything by itself.')">

    <x-ps.section :title="__('Recorded vs running')" :desc="__('Keep these two apart: what you typed in below, and what the server reports about itself.')">
        <div class="ps-twin">
            <div>
                <h4>{{ __('Recorded here') }}</h4>
                <div class="ps-kv"><span>{{ __('Website version') }}</span><span dir="ltr">{{ $v['release.web_version'] ?: '—' }}</span></div>
                <div class="ps-kv"><span>{{ __('Last updated') }}</span><span dir="ltr">{{ $webUpdated ?: '—' }}</span></div>
                <div class="ps-kv"><span>{{ __('Site status') }}</span><span>{{ $modeLabel }}</span></div>
            </div>
            <div>
                <h4>{{ __('Running now (detected)') }}</h4>
                <div class="ps-kv"><span>{{ __('Deployed release') }}</span><span dir="ltr">{{ $runtime['release'] ?: __('Not reported') }}</span></div>
                <div class="ps-kv"><span>Laravel</span><span dir="ltr">{{ $runtime['laravel'] }}</span></div>
                <div class="ps-kv"><span>PHP</span><span dir="ltr">{{ $runtime['php'] }}</span></div>
                <div class="ps-kv"><span>{{ __('Environment') }}</span><span dir="ltr">{{ $runtime['env'] }}</span></div>
            </div>
        </div>
        @unless($runtime['release'])
            <div class="ps-note"><i data-feather="info"></i><div>{{ __('The server does not report which release is deployed. To link the two, have your deploy process set the APP_RELEASE environment variable (for example to the git commit); it will then appear here automatically.') }}</div></div>
        @endunless
    </x-ps.section>

    <x-ps.section :title="__('Products')" :desc="__('Choose a product to edit its release record.')">
        <div class="ps-seg" role="tablist" aria-label="{{ __('Products') }}" data-ps-seg>
            <button type="button" role="tab" id="ps-rel-t-web" aria-controls="ps-rel-web" aria-selected="true"><i data-feather="globe"></i>{{ __('Website') }}</button>
            <button type="button" role="tab" id="ps-rel-t-android" aria-controls="ps-rel-android" aria-selected="false" tabindex="-1"><i data-feather="smartphone"></i>Android</button>
            <button type="button" role="tab" id="ps-rel-t-ios" aria-controls="ps-rel-ios" aria-selected="false" tabindex="-1"><i data-feather="smartphone"></i>iOS</button>
        </div>

        {{-- Website --}}
        <div class="ps-sub-panel ps-sec-body" id="ps-rel-web" role="tabpanel" aria-labelledby="ps-rel-t-web">
            <div class="ps-grid">
                <x-ps.field name="release.web_version" placeholder="1.0.0" />
                <x-ps.field name="release.web_min_version" placeholder="1.0.0" :hint="__('Optional — the oldest version still supported.')" />
                <x-ps.field name="release.web_updated_on" />
                <div class="ps-field">
                    <span class="ps-label">{{ __('Site status') }}</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap" style="min-height:44px">
                        <span class="ps-pill {{ ['normal' => 'ps-pill-live', 'maintenance' => 'ps-pill-maint', 'paused' => 'ps-pill-pause'][$maintenance['mode']] ?? 'ps-pill-live' }}">{{ $modeLabel }}</span>
                        <button type="button" class="ps-mini" data-ps-goto="system"><i data-feather="sliders"></i>{{ __('Manage in System') }}</button>
                    </div>
                </div>
                <x-ps.field name="release.web_notes_ar" class="ps-span-2" dir="rtl" :rows="4" />
                <x-ps.field name="release.web_notes_en" class="ps-span-2" dir="ltr" :rows="4" />
            </div>
        </div>

        {{-- Android --}}
        <div class="ps-sub-panel ps-sec-body" id="ps-rel-android" role="tabpanel" aria-labelledby="ps-rel-t-android" hidden>
            @include('owner.settings.partials._release-store', ['p' => 'android', 'statusOpts' => $statusOpts])
        </div>

        {{-- iOS --}}
        <div class="ps-sub-panel ps-sec-body" id="ps-rel-ios" role="tabpanel" aria-labelledby="ps-rel-t-ios" hidden>
            @include('owner.settings.partials._release-store', ['p' => 'ios', 'statusOpts' => $statusOpts])
        </div>
    </x-ps.section>
</x-ps.panel>
