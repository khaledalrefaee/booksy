{{--
    International phone field (dial code + local number) — same UX as the customers modal.
    The controller must combine dial_code + phone_number (see CustomerController::buildPhone).

    Params:
      $value      — stored full phone incl. dial code digits, e.g. "963962812838" (nullable)
      $required   — default true
      $inputClass — extra CSS classes for both inputs, e.g. 'f-input' (default '')

    The selected country's flag is painted as an image overlay on the start of the
    select (config `flag_img`), so codes that have no accurate Unicode emoji — such
    as Syria's current flag — still show the real flag. Others fall back to emoji.
--}}
@php
    $dialCodes   = config('booksy.dial_codes', []);
    $defaultDial = config('booksy.default_dial_code', '+963');
    $required    = $required ?? true;
    $inputClass  = $inputClass ?? '';
    $isArLocale  = app()->getLocale() === 'ar';

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
    $selInfo = $dialCodes[$selectedDial] ?? [];
@endphp
<div class="js-phone-field">
    <div class="d-flex gap-2" dir="ltr">
        <div class="dial-wrap" style="position:relative;max-width:132px;flex-shrink:0;">
            <span class="dial-flag" aria-hidden="true">
                @if(!empty($selInfo['flag_img']))
                    <img src="{{ $selInfo['flag_img'] }}" alt="">
                @else
                    {{ $selInfo['flag'] ?? '' }}
                @endif
            </span>
            <select name="dial_code" class="form-select dial-code-select has-flag {{ $inputClass }}" style="width:132px;" onchange="updatePhoneValidation(this)">
                @foreach($dialCodes as $code => $info)
                <option value="{{ $code }}"
                        data-min="{{ $info['digits_min'] }}" data-max="{{ $info['digits_max'] }}"
                        data-flag-emoji="{{ $info['flag'] ?? '' }}"
                        @isset($info['flag_img']) data-flag-img="{{ $info['flag_img'] }}" @endisset
                        {{ $code === $selectedDial ? 'selected' : '' }}>
                    {{ $code }} · {{ $isArLocale ? ($info['name_ar'] ?? $code) : ($info['name_en'] ?? $code) }}
                </option>
                @endforeach
            </select>
        </div>
        <input type="tel" name="phone_number" class="form-control phone-input {{ $inputClass }} @error('phone_number') is-invalid @enderror"
               value="{{ $number }}" {{ $required ? 'required' : '' }}
               placeholder="{{ __('e.g.') }} 962812838"
               pattern="[0-9]*" inputmode="numeric">
    </div>
    <div class="phone-hint tx-11 text-muted mt-1"></div>
    @error('phone_number')<div style="color:#f5576c;font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
    @error('phone')<div style="color:#f5576c;font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
</div>
<style>
/* keep room for the flag overlay and hide the flag when a country without an
   image just falls back to an emoji glyph that already sits inside the option */
.dial-wrap .dial-code-select.has-flag { padding-inline-start: 34px; }
.dial-wrap .dial-flag {
    position: absolute; inset-inline-start: 10px; top: 50%; transform: translateY(-50%);
    width: 20px; height: 14px; display: inline-flex; align-items: center; justify-content: center;
    font-size: 15px; line-height: 1; pointer-events: none; z-index: 2;
}
.dial-wrap .dial-flag img {
    width: 20px; height: 14px; object-fit: cover;
    border-radius: 2px; box-shadow: 0 0 0 1px rgba(0,0,0,.12);
    display: block;
}
</style>
<script>
window.updateDialFlag = window.updateDialFlag || function (selectEl) {
    var wrap = selectEl.closest('.dial-wrap');
    if (!wrap) return;
    var flag = wrap.querySelector('.dial-flag');
    if (!flag) return;
    var opt = selectEl.options[selectEl.selectedIndex];
    var img = opt && opt.dataset.flagImg;
    if (img) {
        flag.innerHTML = '<img src="' + img + '" alt="">';
    } else {
        flag.textContent = (opt && opt.dataset.flagEmoji) || '';
    }
};
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
    updateDialFlag(selectEl);
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
