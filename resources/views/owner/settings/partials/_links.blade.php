@php
    // Rows: restored input after a validation error wins over the saved rows.
    $rows = old('links') !== null
        ? array_values((array) old('links'))
        : $links->map(fn ($l) => [
            'id' => $l->id, 'kind' => $l->kind, 'label' => $l->label, 'url' => $l->url, 'icon' => $l->icon,
            'is_active' => $l->is_active, 'show_public' => $l->show_public,
        ])->all();

    $pages = [
        ['label' => __('Official contact page'), 'key' => 'support.contact_url'],
        ['label' => __('Help request page'),     'key' => 'support.help_center_url'],
    ];
@endphp
<x-ps.panel id="links" :active="$tab === 'links'"
    :action="route('owner.settings.links')"
    :title="__('Platform links & social media')"
    :desc="__('The official GlowRez accounts. Only links that are active, switched on for the website and not empty are shown to visitors.')"
    :confirm="null">

    <x-ps.section :title="__('Links')" :desc="__('Add the accounts you actually have. Drag a row (or use the arrows) to set the order they appear in.')">
        <div class="ps-linkbar">
            <span class="ps-help" data-ps-link-count></span>
            <div class="ps-add">
                <button type="button" class="ps-btn ps-btn-primary" data-ps-add-toggle aria-haspopup="menu" aria-expanded="false"><i data-feather="plus"></i>{{ __('Add link') }}</button>
                <div class="ps-menu" role="menu" data-ps-add-menu hidden>
                    @foreach($presets as $key => $meta)
                        <button type="button" role="menuitem" data-ps-add="{{ $key }}">{!! \App\Support\Platform\PlatformIcons::svg($key, 18) !!}<span>{{ $meta['label'] }}</span><small data-ps-added hidden>{{ __('Added') }}</small></button>
                    @endforeach
                    <hr>
                    <button type="button" role="menuitem" data-ps-add="custom">{!! \App\Support\Platform\PlatformIcons::svg('link', 18) !!}<span>{{ __('Custom link') }}</span></button>
                </div>
            </div>
        </div>

        @unless($v['visibility.show_social'])
            <div class="ps-note ps-note-warn"><i data-feather="eye-off"></i><div>{{ __('Social links are currently hidden on the whole website. Turn them on in the Public site tab for them to appear.') }}</div></div>
        @endunless

        <div class="ps-linklist" data-ps-linklist @if(! count($rows)) hidden @endif>
            @foreach($rows as $i => $row)
                @include('owner.settings.partials._link-row', ['i' => $i, 'row' => $row])
            @endforeach
        </div>

        <div class="ps-linklist" data-ps-empty @if(count($rows)) hidden @endif>
            <div class="ps-empty">
                <i data-feather="link-2"></i>
                <b>{{ __('No links yet') }}</b>
                <p>{{ __('Nothing is shown on the website until you add a link. Choose Add link to start with a platform or a custom link.') }}</p>
            </div>
        </div>

        <template data-ps-template>
            @include('owner.settings.partials._link-row', ['i' => '__i__', 'row' => ['kind' => 'custom', 'is_active' => true, 'show_public' => true, 'icon' => 'link']])
        </template>
    </x-ps.section>

    <x-ps.section :title="__('Official pages')" :desc="__('These two pages are managed with the support channels, so they are stored only once.')">
        <div class="ps-pages">
            @foreach($pages as $p)
                @php $val = $v[$p['key']] ?? null; @endphp
                <div class="ps-page">
                    <div>
                        <div class="ps-page-t">{{ $p['label'] }}</div>
                        <div class="ps-page-v" dir="ltr">{{ $val ?: __('Not set') }}</div>
                    </div>
                    <button type="button" class="ps-btn ps-btn-ghost ps-btn-sm" data-ps-goto="support"><i data-feather="edit-2"></i>{{ __('Edit in Support') }}</button>
                </div>
            @endforeach
        </div>
    </x-ps.section>
</x-ps.panel>
