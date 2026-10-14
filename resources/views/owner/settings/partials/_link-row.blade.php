{{--
  One editable link row. Rendered for saved rows, for rows restored after a validation
  error, and (with $i = "__i__") as the template new rows are cloned from.
  $row: id, kind, label, url, icon, is_active, show_public
--}}
@php
    $kind     = $row['kind'] ?? 'custom';
    $isPreset = $kind !== 'custom' && isset($presets[$kind]);
    $iconKey  = $isPreset ? $kind : ($row['icon'] ?? 'link');
    $label    = $isPreset ? $presets[$kind]['label'] : ($row['label'] ?? '');
    $hosts    = $isPreset ? implode(',', $presets[$kind]['hosts']) : '';
    $pfx      = $errors->has("links.$i.url") || $errors->has("links.$i.label") || $errors->has("links.$i.kind");
@endphp
<div class="ps-link" data-ps-link data-kind="{{ $kind }}">
    <input type="hidden" name="links[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
    <input type="hidden" name="links[{{ $i }}][kind]" value="{{ $kind }}" data-f="kind">

    <button type="button" class="ps-grip" data-ps-grip aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>
    </button>

    <span class="ps-link-ic" data-ps-icon>{!! \App\Support\Platform\PlatformIcons::svg($iconKey, 20) !!}</span>

    <div class="ps-link-main">
        {{-- Name: fixed for platform presets, editable (with an icon choice) for custom links --}}
        <div data-ps-only="preset" @if(! $isPreset) hidden @endif>
            <div class="ps-link-name"><span data-ps-label-text>{{ $label }}</span></div>
        </div>
        <div data-ps-only="custom" class="ps-grid" @if($isPreset) hidden @endif style="gap:12px">
            <div class="ps-field @error("links.$i.label") has-error @enderror">
                <label class="ps-label">{{ __('Link name') }}</label>
                <input type="text" class="ps-input" name="links[{{ $i }}][label]" value="{{ $isPreset ? '' : $label }}" maxlength="60"
                       placeholder="{{ __('e.g. Blog') }}" data-f="label" @if($isPreset) disabled @endif>
                @error("links.$i.label")<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>
            <div class="ps-field">
                <label class="ps-label">{{ __('Icon') }}</label>
                <select class="ps-select" name="links[{{ $i }}][icon]" data-f="icon" data-ps-icon-select @if($isPreset) disabled @endif>
                    @foreach($customIcons as $ic)
                        <option value="{{ $ic }}" @selected($iconKey === $ic)>{{ __(ucfirst(str_replace('-', ' ', $ic))) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="ps-field @error("links.$i.url") has-error @enderror">
            <label class="ps-label">{{ __('Link') }}</label>
            <input type="text" class="ps-input" dir="ltr" name="links[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" maxlength="500"
                   inputmode="url" autocapitalize="off" spellcheck="false" placeholder="https://" data-f="url"
                   data-ps-check="url" data-hosts="{{ $hosts }}">
            <div class="ps-check">
                <span class="ps-check-state" data-ps-state aria-live="polite"></span>
                <button type="button" class="ps-mini" data-ps-verify><i data-feather="check-circle"></i>{{ __('Check format') }}</button>
                <a class="ps-mini" data-ps-open href="#" target="_blank" rel="noopener noreferrer" aria-disabled="true"><i data-feather="external-link"></i>{{ __('Open') }}</a>
            </div>
            @error("links.$i.url")<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
            @error("links.$i.kind")<div class="ps-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $message }}</span></div>@enderror
        </div>

        <div class="ps-link-opts">
            <label class="ps-inline-switch">
                <span class="ps-toggle">
                    <input type="hidden" name="links[{{ $i }}][is_active]" value="0">
                    <input type="checkbox" role="switch" name="links[{{ $i }}][is_active]" value="1" data-f="is_active" @checked($row['is_active'] ?? true)>
                    <i aria-hidden="true"></i>
                </span>
                <span>{{ __('Active') }}</span>
            </label>
            <label class="ps-inline-switch">
                <span class="ps-toggle">
                    <input type="hidden" name="links[{{ $i }}][show_public]" value="0">
                    <input type="checkbox" role="switch" name="links[{{ $i }}][show_public]" value="1" data-f="show_public" @checked($row['show_public'] ?? true)>
                    <i aria-hidden="true"></i>
                </span>
                <span>{{ __('Show on the website') }}</span>
            </label>
            <span class="ps-link-vis" data-ps-vis></span>
        </div>
    </div>

    <div class="ps-link-ctl">
        <button type="button" class="ps-iconbtn" data-ps-move="-1" aria-label="{{ __('Move up') }}" title="{{ __('Move up') }}"><i data-feather="arrow-up"></i></button>
        <button type="button" class="ps-iconbtn" data-ps-move="1" aria-label="{{ __('Move down') }}" title="{{ __('Move down') }}"><i data-feather="arrow-down"></i></button>
        <button type="button" class="ps-iconbtn is-danger" data-ps-remove aria-label="{{ __('Remove link') }}" title="{{ __('Remove link') }}"><i data-feather="trash-2"></i></button>
    </div>
</div>
