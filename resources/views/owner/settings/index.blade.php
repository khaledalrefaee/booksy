@extends('owner.dashboard')

@section('content')
@include('owner.settings.partials._styles')
@php
    // After a failed save the form that was submitted must be the one on screen.
    $tab = old('_tab', $tab);

    $tabMeta = [
        'general'    => ['layout',    __('General'),     __('Name, logo, official contact')],
        'support'    => ['life-buoy', __('Support'),     __('Customer service channels')],
        'links'      => ['link',      __('Links'),       __('Social & custom links')],
        'releases'   => ['package',   __('Releases'),    __('Website and app versions')],
        'visibility' => ['eye',       __('Public site'), __('Footer and what visitors see')],
        'system'     => ['sliders',   __('System'),      __('Maintenance and status')],
    ];
    $mode      = $maintenance['mode'];
    $pillClass = ['normal' => 'ps-pill-live', 'maintenance' => 'ps-pill-maint', 'paused' => 'ps-pill-pause'][$mode] ?? 'ps-pill-live';
    $pillLabel = ['normal' => __('Live'), 'maintenance' => __('Under maintenance'), 'paused' => __('Temporarily paused')][$mode] ?? __('Live');
@endphp

<div class="page-content">
<div class="ps" data-ps-root>

    <header class="ps-head">
        <div>
            <h1 class="ps-title">{{ __('Platform settings') }}</h1>
            <p class="ps-sub">{{ __('Contact details, official links, app releases and how the public site behaves — managed in one place.') }}</p>
        </div>
        <div class="ps-head-actions">
            <button type="button" class="ps-pill {{ $pillClass }}" data-ps-goto="system" title="{{ __('Open the System tab') }}">
                <i data-feather="{{ $mode === 'normal' ? 'check-circle' : ($mode === 'paused' ? 'pause-circle' : 'tool') }}"></i>{{ $pillLabel }}
            </button>
            <a href="{{ route('front.index') }}" target="_blank" rel="noopener" class="ps-btn ps-btn-ghost"><i data-feather="external-link"></i>{{ __('View website') }}</a>
        </div>
    </header>

    <div class="ps-shell">
        <nav class="ps-nav" role="tablist" aria-orientation="vertical" aria-label="{{ __('Settings sections') }}" data-ps-nav>
            @foreach($tabMeta as $id => [$icon, $name, $desc])
                <button type="button" class="ps-tab" role="tab" id="ps-tab-{{ $id }}" data-ps-tab="{{ $id }}"
                        aria-controls="ps-panel-{{ $id }}" aria-selected="{{ $tab === $id ? 'true' : 'false' }}" tabindex="{{ $tab === $id ? '0' : '-1' }}">
                    <span class="ps-tab-ic"><i data-feather="{{ $icon }}"></i></span>
                    <span class="ps-tab-txt"><span class="ps-tab-name">{{ $name }}</span><span class="ps-tab-desc">{{ $desc }}</span></span>
                    <span class="ps-dot" aria-hidden="true"></span>
                </button>
            @endforeach
        </nav>

        <div class="ps-main">
            @include('owner.settings.partials._general')
            @include('owner.settings.partials._support')
            @include('owner.settings.partials._links')
            @include('owner.settings.partials._releases')
            @include('owner.settings.partials._visibility')
            @include('owner.settings.partials._system')
        </div>
    </div>
</div>
</div>

@include('owner.settings.partials._scripts')
@endsection
