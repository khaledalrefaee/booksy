{{--
  One settings tab: a tabpanel holding its own <form> + sticky save bar.
  `action` = PUT target. Slot = the sections. `files` enables multipart (logo).
--}}
@props(['id', 'title', 'desc' => null, 'action', 'active' => false, 'files' => false, 'confirm' => null])
<div id="ps-panel-{{ $id }}" class="ps-panel {{ $active ? 'is-active' : '' }}" role="tabpanel" aria-labelledby="ps-tab-{{ $id }}" tabindex="-1" data-ps-panel="{{ $id }}">
    <form method="POST" action="{{ $action }}" @if($files) enctype="multipart/form-data" @endif novalidate data-ps-form="{{ $id }}" @if($confirm) data-ps-confirm="{{ $confirm }}" @endif>
        @csrf
        @method('PUT')
        <input type="hidden" name="_tab" value="{{ $id }}">
        <div class="ps-panel-head">
            <h2 class="ps-panel-title">{{ $title }}</h2>
            @if($desc)<p class="ps-panel-desc">{{ $desc }}</p>@endif
        </div>

        {{ $slot }}

        <div class="ps-save">
            <span class="ps-save-state" data-ps-state-line role="status" aria-live="polite"><i data-feather="check"></i><span>{{ __('All changes saved') }}</span></span>
            <div class="ps-save-btns">
                <button type="button" class="ps-btn ps-btn-ghost" data-ps-discard disabled>{{ __('Discard changes') }}</button>
                <button type="submit" class="ps-btn ps-btn-primary" data-ps-submit disabled><i data-feather="save"></i>{{ __('Save changes') }}</button>
            </div>
        </div>
    </form>
</div>
