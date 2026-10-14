@php
    $mode  = old('system.mode', $v['system.mode']) ?: 'normal';
    $scope = old('system.scope', $v['system.scope']) ?: 'public';
    $off   = ! $canMaintenance;
    $modes = [
        'normal'      => [__('Live'),                __('The platform is open to everyone.'), 'check-circle'],
        'maintenance' => [__('Maintenance'),         __('Planned work. Visitors see a short maintenance page and can retry.'), 'tool'],
        'paused'      => [__('Temporarily paused'),  __('Service stopped for longer. Visitors see a paused notice.'), 'pause-circle'],
    ];
@endphp
<x-ps.panel id="system" :active="$tab === 'system'"
    :action="route('owner.settings.update', 'system')"
    :title="__('System & maintenance')"
    :desc="__('Open or close the platform to visitors and control the information shown about updates.')"
    :confirm="$canMaintenance ? $mode : null">

    <x-ps.section :title="__('Site mode')" :desc="__('The one switch for maintenance. The owner console is never blocked, so you can always sign in and reopen the site.')">
        @if($off)
            <div class="ps-note ps-note-warn"><i data-feather="lock"></i><div>{{ __('You can read the site mode but not change it. Switching maintenance on or off needs the permission "Switch maintenance / paused mode on or off".') }}</div></div>
        @endif

        <div class="ps-field">
            <span class="ps-label" id="ps-mode-l">{{ __('Site mode') }}</span>
            <div class="ps-choices cols-3" role="radiogroup" aria-labelledby="ps-mode-l">
                @foreach($modes as $val => [$t, $d, $ic])
                    <label class="ps-choice">
                        <input type="radio" name="system[mode]" value="{{ $val }}" @checked($mode === $val) @disabled($off)>
                        <span class="ps-choice-ic"><i data-feather="{{ $ic }}"></i></span>
                        <span><span class="ps-choice-t d-block">{{ $t }}</span><span class="ps-choice-d d-block">{{ $d }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('system.mode')<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>

        <div data-ps-maint-detail @if($mode === 'normal') hidden @endif>
            <div class="ps-field" style="margin-bottom:20px">
                <span class="ps-label" id="ps-scope-l">{{ __('What it closes') }}</span>
                <div class="ps-choices" role="radiogroup" aria-labelledby="ps-scope-l">
                    <label class="ps-choice">
                        <input type="radio" name="system[scope]" value="public" @checked($scope === 'public') @disabled($off)>
                        <span class="ps-choice-ic"><i data-feather="globe"></i></span>
                        <span><span class="ps-choice-t d-block">{{ __('Public website and customer apps') }}</span><span class="ps-choice-d d-block">{{ __('Company dashboards keep working.') }}</span></span>
                    </label>
                    <label class="ps-choice">
                        <input type="radio" name="system[scope]" value="all" @checked($scope === 'all') @disabled($off)>
                        <span class="ps-choice-ic"><i data-feather="briefcase"></i></span>
                        <span><span class="ps-choice-t d-block">{{ __('Everything except the owner console') }}</span><span class="ps-choice-d d-block">{{ __('Company dashboards and their API are closed too.') }}</span></span>
                    </label>
                </div>
            </div>
            <div class="ps-grid">
                <x-ps.field name="system.message_ar" dir="rtl" :rows="3" :disabled="$off" :hint="__('Shown instead of the default wording. Leave empty for the default.')" />
                <x-ps.field name="system.message_en" dir="ltr" :rows="3" :disabled="$off" />
            </div>
        </div>

        <div class="ps-note">
            <i data-feather="shield"></i>
            <div><b>{{ __('Who can still get in') }}</b><br>{{ __('Anyone signed in to the owner console with dashboard access — including on the public site, so you can check it. This is enforced on the server, not just by hiding buttons. The mobile apps can still read the status from the platform API.') }}</div>
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Status & change log')" :desc="__('Optional links shown on the maintenance page and in the API.')">
        <div class="ps-grid">
            <x-ps.field name="system.status_page_url" check="url" placeholder="https://status.…" />
            <x-ps.field name="system.changelog_url" check="url" placeholder="https://…" />
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Updates & version')" :desc="__('Control what users are told about versions.')">
        <div class="ps-switches">
            <x-ps.switch name="system.update_notice" :desc="__('The platform API tells an app when a newer version than its own is recorded. The app decides how to show the notice — this switch only lets the API say so.')" />
            <x-ps.switch name="system.show_version" :desc="__('Shows the website version from the Releases tab in the public footer.')" />
        </div>
    </x-ps.section>

    <x-ps.section :title="__('Platform API')" :desc="__('For the mobile apps.')">
        <div class="ps-note"><i data-feather="code"></i><div>{{ __('Apps read public settings, links, status and versions from this address. Internal support details are never included.') }} <code dir="ltr">{{ url('/api/platform/config') }}</code></div></div>
    </x-ps.section>
</x-ps.panel>
