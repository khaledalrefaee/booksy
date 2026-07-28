{{--
    International phone field (dial code + local number) — same UX as the customers modal.
    The controller must combine dial_code + phone_number (see CustomerController::buildPhone).

    Params:
      $value      — stored full phone incl. dial code digits, e.g. "963962812838" (nullable)
      $required   — default true
      $inputClass — extra CSS classes for both inputs, e.g. 'f-input' (default '')
--}}
@php
    $dialCodes   = config('booksy.dial_codes', []);
    $defaultDial = config('booksy.default_dial_code', '+963');
    $required    = $required ?? true;
    $inputClass  = $inputClass ?? '';

    // Old input wins (validation redirect), otherwise split the stored value into dial code + number
    $selectedDial = old('dial_code', $defaultDial);
    $number       = old('phone_number');
    if ($number === null) {
        $digits = preg_replace('/\D/', '', (string) ($value ?? ''));
        $number = $digits;
        foreach (collect($dialCodes)->keys()->sortByDesc(fn ($c) => strlen($c)) as $code) {
            $codeDigits = ltrim($code, '+');
            if ($digits !== '' && str_starts_with($digits, $codeDigits)) {
                $selectedDial = $code;
                $number = substr($digits, strlen($codeDigits));
                break;
            }
        }
    }
@endphp
<div class="js-phone-field">
    <div class="d-flex gap-2" dir="ltr">
        <select name="dial_code" class="form-select dial-code-select {{ $inputClass }}" style="max-width:130px;flex-shrink:0;" onchange="updatePhoneValidation(this)">
            @foreach($dialCodes as $code => $info)
            <option value="{{ $code }}" data-min="{{ $info['digits_min'] }}" data-max="{{ $info['digits_max'] }}"
                    {{ $code === $selectedDial ? 'selected' : '' }}>
                {{ $info['flag'] }} {{ $code }}
            </option>
            @endforeach
        </select>
        <input type="tel" name="phone_number" class="form-control phone-input {{ $inputClass }} @error('phone_number') is-invalid @enderror"
               value="{{ $number }}" {{ $required ? 'required' : '' }}
               placeholder="{{ __('e.g.') }} 962812838"
               pattern="[0-9]*" inputmode="numeric">
    </div>
    <div class="phone-hint tx-11 text-muted mt-1"></div>
    @error('phone_number')<div style="color:#f5576c;font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
    @error('phone')<div style="color:#f5576c;font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
</div>
<script>
window.updatePhoneValidation = window.updatePhoneValidation || function (selectEl) {
    var wrap  = selectEl.closest('.js-phone-field') || selectEl.closest('.d-flex').parentNode;
    var opt   = selectEl.options[selectEl.selectedIndex];
    var min   = parseInt(opt.dataset.min || 7, 10);
    var max   = parseInt(opt.dataset.max || 15, 10);
    var input = wrap.querySelector('.phone-input');
    var hint  = wrap.querySelector('.phone-hint');
    input.minLength = min;
    input.maxLength = max;
    if (hint) {
        hint.textContent = min === max
            ? '{{ __("Must be exactly") }} ' + min + ' {{ __("digits") }}'
            : min + ' — ' + max + ' {{ __("digits") }}';
    }
    input.dispatchEvent(new Event('input'));
};
document.querySelectorAll('.js-phone-field').forEach(function (wrap) {
    if (wrap.dataset.bound) return;
    wrap.dataset.bound = '1';
    var input = wrap.querySelector('.phone-input');
    input.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.startsWith('0')) this.value = this.value.substring(1);
        var len = this.value.length;
        this.style.borderColor = (len > 0 && (len < this.minLength || len > this.maxLength)) ? '#ef4444' : '';
    });
    updatePhoneValidation(wrap.querySelector('.dial-code-select'));
});
</script>
