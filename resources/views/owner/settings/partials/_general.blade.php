@php
    $pillClass = ['normal' => 'ps-pill-live', 'maintenance' => 'ps-pill-maint', 'paused' => 'ps-pill-pause'][$maintenance['mode']] ?? 'ps-pill-live';
    $pillLabel = ['normal' => __('Live'), 'maintenance' => __('Under maintenance'), 'paused' => __('Temporarily paused')][$maintenance['mode']] ?? __('Live');
@endphp
<x-ps.panel id="general" :active="$tab === 'general'" :files="true"
    :action="route('owner.settings.update', 'general')"
    :title="__('General information')"
    :desc="__('How GlowRez identifies itself. Fields you leave empty are simply not shown — nothing is assumed.')">

    <x-ps.section :title="__('Platform identity')" :desc="__('The name used for the platform. The official company name is optional.')">
        <div class="ps-grid">
            <x-ps.field name="general.name_ar" dir="rtl" />
            <x-ps.field name="general.name_en" dir="ltr" />
            <x-ps.field name="general.company_name_ar" dir="rtl" :hint="__('Optional — only if it differs from the platform name.')" />
            <x-ps.field name="general.company_name_en" dir="ltr" />
            <x-ps.field name="general.description_ar" class="ps-span-2" dir="rtl" :rows="3" />
            <x-ps.field name="general.description_en" class="ps-span-2" dir="ltr" :rows="3" />
        </div>
        @php $dl = old('general.default_locale', $v['general.default_locale']); @endphp
        <div class="ps-field" style="max-width:320px">
            <label class="ps-label" for="f-general-default-locale"><span>{{ __('Default language') }}</span></label>
            <select id="f-general-default-locale" name="general[default_locale]" class="ps-select">
                <option value="" @selected(! $dl)>{{ __('System default') }}</option>
                <option value="ar" @selected($dl === 'ar')>{{ __('Arabic') }}</option>
                <option value="en" @selected($dl === 'en')>{{ __('English') }}</option>
            </select>
            <div class="ps-help">{{ __('Used for visitors who have not picked a language yet.') }}</div>
            @error('general.default_locale')<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Logo')" :desc="__('Used in the public API and structured data. It does not replace the logos built into the website pages.')">
        <div class="ps-logo">
            <div class="ps-logo-box" data-ps-logo-box>
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ __('Current logo') }}" data-ps-logo-img>
                @else
                    <i data-feather="image" data-ps-logo-empty></i>
                @endif
            </div>
            <div class="ps-logo-ctl">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="ps-file ps-btn ps-btn-ghost">
                        <i data-feather="upload"></i>{{ $logoUrl ? __('Replace logo') : __('Choose logo') }}
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" data-ps-logo-input aria-label="{{ __('Choose logo') }}">
                    </span>
                    @if($logoUrl)
                        <label class="ps-inline-switch" style="min-height:44px">
                            <span class="ps-toggle"><input type="checkbox" name="remove_logo" value="1" data-ps-logo-remove><i aria-hidden="true"></i></span>
                            <span>{{ __('Remove current logo') }}</span>
                        </label>
                    @endif
                </div>
                <div class="ps-help">{{ __('PNG, JPG or WebP · up to 2 MB · at least 64×64 px.') }}</div>
                <div class="ps-err d-none" data-ps-logo-err role="alert"><i data-feather="alert-circle"></i><span></span></div>
                @error('logo')<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Official contact')" :desc="__('GlowRez official details. Whether they appear on the website is decided in the Public site tab.')">
        <div class="ps-grid">
            <x-ps.field name="general.website_url" class="ps-span-2" check="url" placeholder="https://" />
            <x-ps.field name="general.email" check="email" placeholder="name@domain.com" />
            <x-ps.field name="general.phone" check="phone" placeholder="+963 944 123 456" :hint="__('Include the country code.')" />
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Platform status')" :desc="__('Whether the platform is open to visitors.')">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="ps-pill {{ $pillClass }}"><i data-feather="activity"></i>{{ $pillLabel }}</span>
            <button type="button" class="ps-btn ps-btn-ghost ps-btn-sm" data-ps-goto="system"><i data-feather="sliders"></i>{{ __('Manage in System') }}</button>
        </div>
        <div class="ps-help">{{ __('Maintenance is controlled in one place only — the System tab — so the status can never disagree with itself.') }}</div>
    </x-ps.section>
</x-ps.panel>
