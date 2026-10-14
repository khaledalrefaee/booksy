{{--
  Schema-driven settings input. `name` is the dotted key from config/platform-settings.php
  ("support.email"); label, type and length limit come from there. Posts as group[field].
  check = show the live format check + "Open" buttons (url / email / phone / wa).
--}}
@props(['name', 'hint' => null, 'placeholder' => null, 'check' => null, 'rows' => 3, 'dir' => null, 'disabled' => false, 'label' => null])
@php
    $def   = \App\Services\Platform\PlatformSettings::def($name);
    $type  = $def['type'];
    [$g, $k] = explode('.', $name, 2);
    $field = $g.'['.$k.']';
    $id    = 'f-'.str_replace(['.', '_'], '-', $name);
    $val   = old($name, app(\App\Services\Platform\PlatformSettings::class)->get($name));
    $val   = is_array($val) ? '' : (string) ($val ?? '');
    $max   = $def['max'] ?? null;
    $err   = $errors->first($name);
    $ltr   = $dir ?? (in_array($type, ['url', 'email', 'phone', 'version', 'int', 'time', 'date'], true) ? 'ltr' : null);
    $hosts = implode(',', $def['hosts'] ?? []);
    $label = $label ?? __($def['label']);
@endphp
<div {{ $attributes->class(['ps-field', 'has-error' => $err]) }}>
    <label class="ps-label" for="{{ $id }}">
        <span>{{ $label }}</span>
        @if($max && $type === 'text')<span class="ps-count" data-ps-count-for="{{ $id }}" data-max="{{ $max }}">0/{{ $max }}</span>@endif
    </label>

    @if($type === 'text')
        <textarea id="{{ $id }}" name="{{ $field }}" class="ps-textarea" rows="{{ $rows }}" maxlength="{{ $max }}"
                  @if($ltr) dir="{{ $ltr }}" @endif placeholder="{{ $placeholder }}" @disabled($disabled)
                  @if($err) aria-invalid="true" aria-describedby="{{ $id }}-err" @endif>{{ $val }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $field }}" class="ps-input"
               type="{{ match($type) { 'email' => 'email', 'phone' => 'tel', 'time' => 'time', 'date' => 'date', 'int' => 'number', default => 'text' } }}"
               value="{{ $val }}"
               @if($ltr) dir="{{ $ltr }}" @endif
               @if($max && $type === 'string') maxlength="{{ $max }}" @endif
               @if($type === 'int') min="{{ $def['min'] ?? 0 }}" step="1" inputmode="numeric" @endif
               @if($type === 'url') inputmode="url" autocapitalize="off" spellcheck="false" @endif
               @if($type === 'email') inputmode="email" autocomplete="off" spellcheck="false" @endif
               @if($type === 'phone') inputmode="tel" autocomplete="off" @endif
               @if($type === 'version') autocapitalize="off" spellcheck="false" inputmode="decimal" @endif
               placeholder="{{ $placeholder }}" @disabled($disabled)
               @if($check) data-ps-check="{{ $check }}" data-hosts="{{ $hosts }}" @if(!empty($def['https'])) data-https="1" @endif @if(!empty($def['relative'])) data-relative="1" @endif @endif
               @if($err) aria-invalid="true" aria-describedby="{{ $id }}-err" @endif>
    @endif

    @if($check)
        <div class="ps-check" data-ps-checkwrap="{{ $id }}">
            <span class="ps-check-state" data-ps-state aria-live="polite"></span>
            <button type="button" class="ps-mini" data-ps-verify="{{ $id }}"><i data-feather="check-circle"></i>{{ __('Check format') }}</button>
            <a class="ps-mini" data-ps-open="{{ $id }}" href="#" target="_blank" rel="noopener noreferrer" aria-disabled="true"><i data-feather="external-link"></i>{{ __('Open') }}</a>
        </div>
    @endif

    @if($hint)<div class="ps-help">{{ $hint }}</div>@endif
    @if($err)<div class="ps-err" id="{{ $id }}-err" role="alert"><i data-feather="alert-circle"></i><span>{{ $err }}</span></div>@endif
</div>
