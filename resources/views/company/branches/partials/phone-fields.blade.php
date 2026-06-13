@php
    $dialCodes   = config('booksy.dial_codes');
    $defaultDial = config('booksy.default_dial_code', '+963');
    $locale      = app()->getLocale();

    $parsePhone = function (string $raw) use ($dialCodes, $defaultDial): array {
        if (str_contains($raw, '|')) {
            [$code, $num] = explode('|', $raw, 2);
            return [trim($code), trim($num)];
        }
        foreach (array_keys($dialCodes) as $code) {
            $stripped     = ltrim($raw, '+');
            $codeStripped = ltrim($code, '+');
            if (str_starts_with($stripped, $codeStripped)) {
                return [$code, '+' . $stripped];
            }
        }
        return [$defaultDial, $raw];
    };

    /* أرقام الجوال */
    $allPhones = [];
    if (!empty($branch ?? null)) {
        if ($branch->phone) $allPhones[] = $branch->phone;
        foreach ((array)($branch->phones ?? []) as $p) { if (filled($p)) $allPhones[] = $p; }
    }
    if (is_array(old('phones'))) {
        $allPhones = array_values(array_filter(old('phones'), fn($v) => filled($v)));
    }
    if (empty($allPhones)) $allPhones = [''];

    /* أرقام الأرضي */
    $allLandlines = [];
    if (!empty($branch ?? null)) {
        if ($branch->landline_phone) $allLandlines[] = $branch->landline_phone;
        foreach ((array)($branch->landlines ?? []) as $p) { if (filled($p)) $allLandlines[] = $p; }
    }
    if (is_array(old('landlines'))) {
        $allLandlines = array_values(array_filter(old('landlines'), fn($v) => filled($v)));
    }
    if (empty($allLandlines)) $allLandlines = [''];

    /* بيانات JS */
    $jsDialData = [];
    foreach ($dialCodes as $code => $info) {
        $jsDialData[] = [
            'code'     => $code,
            'flag'     => $info['flag'],
            'flag_img' => $info['flag_img'] ?? null,
            'name_en'  => $info['name_en'],
            'name_ar'  => $info['name_ar'],
        ];
    }
    $jsLocale = $locale;
@endphp

@php
    /* بناء HTML الـ dropdown المخصص */
    function dialDropdown(string $name, string $selectedCode, array $dialCodes, string $locale): string {
        $sel      = $dialCodes[$selectedCode] ?? reset($dialCodes);
        $flagHtml = isset($sel['flag_img'])
            ? '<img src="'.$sel['flag_img'].'" style="width:22px;height:15px;object-fit:cover;border-radius:2px;vertical-align:middle;">'
            : '<span style="font-size:16px;line-height:1;vertical-align:middle;">'.$sel['flag'].'</span>';
        $btnLabel = $flagHtml . ' <span class="dial-code-text ms-1">' . $selectedCode . '</span>';

        $items = '';
        foreach ($dialCodes as $code => $info) {
            $country = $locale === 'ar' ? $info['name_ar'] : $info['name_en'];
            $fHtml   = isset($info['flag_img'])
                ? '<img src="'.$info['flag_img'].'" style="width:22px;height:15px;object-fit:cover;border-radius:2px;vertical-align:middle;" class="me-2">'
                : '<span style="font-size:16px;line-height:1;vertical-align:middle;" class="me-1">'.$info['flag'].'</span>';
            $items .= '<li><a class="dropdown-item dial-option py-1 d-flex align-items-center gap-1" href="#" data-code="'.$code.'">'
                    . $fHtml
                    . '<span class="flex-grow-1">' . $country . '</span>'
                    . '<span class="text-muted ms-2 small">' . $code . '</span>'
                    . '</a></li>';
        }

        return '<div class="dropdown flex-shrink-0">'
             . '<button type="button"'
             . ' class="btn btn-sm btn-outline-secondary dropdown-toggle dial-btn rounded-end-0 border-end-0 d-flex align-items-center gap-1 px-2"'
             . ' data-bs-toggle="dropdown"'
             . ' style="min-width:90px;font-size:12px;height:100%;" dir="ltr">'
             . $btnLabel
             . '</button>'
             . '<ul class="dropdown-menu shadow-sm" style="min-width:210px;max-height:230px;overflow-y:auto;">'
             . $items
             . '</ul>'
             . '<input type="hidden" name="'.$name.'[]" value="'.$selectedCode.'" class="dial-hidden-input">'
             . '</div>';
    }
@endphp

<div class="row g-3">

    {{-- ── الجوال ─────────────────────────────────── --}}
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0 d-flex align-items-center gap-1">
                <i data-feather="smartphone" style="width:14px;height:14px;"></i>
                {{ __('Mobile phones') }}
            </label>
            <button type="button"
                    class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 js-add-phone"
                    style="font-size:12px;line-height:1.9;"
                    data-list="phones-list" data-name="phones" data-type="mobile">
                <i data-feather="plus" style="width:12px;height:12px;"></i>
                {{ __('Add') }}
            </button>
        </div>

        <div id="phones-list" class="d-flex flex-column gap-2">
            @foreach ($allPhones as $i => $val)
            @php [$dialVal, $numVal] = $parsePhone((string)$val); @endphp
            <div class="phone-row d-flex align-items-center gap-2">
                <div class="d-flex flex-nowrap flex-grow-1">
                    {!! dialDropdown('phone_codes', $dialVal, $dialCodes, $locale) !!}
                    <input type="tel" name="phones[]"
                           value="{{ $numVal }}"
                           placeholder="{{ __('e.g. 912 345 678') }}"
                           class="form-control form-control-sm rounded-start-0 @error('phones.'.$i) is-invalid @enderror"
                           maxlength="30" dir="ltr">
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-danger rounded-circle p-0 js-remove-phone flex-shrink-0"
                        style="width:28px;height:28px;" title="{{ __('Remove') }}">
                    <i data-feather="x" style="width:12px;height:12px;"></i>
                </button>
            </div>
            @error('phones.'.$i)
                <div class="text-danger" style="font-size:11px;">{{ $message }}</div>
            @enderror
            @endforeach
        </div>
    </div>

    {{-- ── الأرضي (بدون كود دولة) ───────────────── --}}
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0 d-flex align-items-center gap-1">
                <i data-feather="phone" style="width:14px;height:14px;"></i>
                {{ __('Landline phones') }}
            </label>
            <button type="button"
                    class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 js-add-phone"
                    style="font-size:12px;line-height:1.9;"
                    data-list="landlines-list" data-name="landlines" data-type="landline">
                <i data-feather="plus" style="width:12px;height:12px;"></i>
                {{ __('Add') }}
            </button>
        </div>

        <div id="landlines-list" class="d-flex flex-column gap-2">
            @foreach ($allLandlines as $i => $val)
            <div class="phone-row d-flex align-items-center gap-2">
                <div class="input-group input-group-sm flex-grow-1">
                    <span class="input-group-text px-3">
                        <i data-feather="phone" style="width:13px;height:13px;"></i>
                    </span>
                    <input type="tel" name="landlines[]"
                           value="{{ $val }}"
                           placeholder="{{ __('e.g. 011 234 5678') }}"
                           class="form-control @error('landlines.'.$i) is-invalid @enderror"
                           maxlength="30" dir="ltr">
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-danger rounded-circle p-0 js-remove-phone flex-shrink-0"
                        style="width:28px;height:28px;" title="{{ __('Remove') }}">
                    <i data-feather="x" style="width:12px;height:12px;"></i>
                </button>
            </div>
            @error('landlines.'.$i)
                <div class="text-danger" style="font-size:11px;">{{ $message }}</div>
            @enderror
            @endforeach
        </div>
    </div>

</div>

@once
@push('scripts')
<script>
(function () {
    var dialData    = @json($jsDialData);
    var defaultDial = '{{ config('booksy.default_dial_code', '+963') }}';
    var locale      = '{{ $jsLocale }}';

    /* ── مساعدات ─────────────────────────────────── */
    function flagHtml(d) {
        if (d.flag_img) {
            return '<img src="' + d.flag_img + '" style="width:22px;height:15px;object-fit:cover;border-radius:2px;vertical-align:middle;">';
        }
        return '<span style="font-size:16px;line-height:1;vertical-align:middle;">' + d.flag + '</span>';
    }

    function buildDialDropdown(name) {
        var def = dialData.find(function(d){ return d.code === defaultDial; }) || dialData[0];

        var wrapper = document.createElement('div');
        wrapper.className = 'dropdown flex-shrink-0';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-secondary dropdown-toggle dial-btn rounded-end-0 border-end-0 d-flex align-items-center gap-1 px-2';
        btn.setAttribute('data-bs-toggle', 'dropdown');
        btn.style.cssText = 'min-width:90px;font-size:12px;height:100%;';
        btn.dir = 'ltr';
        btn.innerHTML = flagHtml(def) + ' <span class="dial-code-text ms-1">' + def.code + '</span>';

        var menu = document.createElement('ul');
        menu.className = 'dropdown-menu shadow-sm';
        menu.style.cssText = 'min-width:210px;max-height:230px;overflow-y:auto;';

        dialData.forEach(function(d) {
            var country = locale === 'ar' ? d.name_ar : d.name_en;
            var li = document.createElement('li');
            var a  = document.createElement('a');
            a.className = 'dropdown-item dial-option py-1 d-flex align-items-center gap-1';
            a.href = '#';
            a.dataset.code = d.code;
            a.innerHTML = flagHtml(d)
                + '<span class="flex-grow-1">' + country + '</span>'
                + '<span class="text-muted ms-2 small">' + d.code + '</span>';
            li.appendChild(a);
            menu.appendChild(li);
        });

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = name + '[]';
        hidden.value = def.code;
        hidden.className = 'dial-hidden-input';

        wrapper.appendChild(btn);
        wrapper.appendChild(menu);
        wrapper.appendChild(hidden);
        return wrapper;
    }

    function buildRow(name, type) {
        var row = document.createElement('div');
        row.className = 'phone-row d-flex align-items-center gap-2';

        var inp = document.createElement('input');
        inp.type      = 'tel';
        inp.name      = name + '[]';
        inp.maxLength = 30;
        inp.dir       = 'ltr';

        var outer;
        if (type === 'mobile') {
            outer = document.createElement('div');
            outer.className = 'd-flex flex-nowrap flex-grow-1';
            var dd = buildDialDropdown('phone_codes');
            inp.className   = 'form-control form-control-sm rounded-start-0';
            inp.placeholder = '{{ __('e.g. 912 345 678') }}';
            outer.appendChild(dd);
            outer.appendChild(inp);
        } else {
            outer = document.createElement('div');
            outer.className = 'input-group input-group-sm flex-grow-1';
            var span = document.createElement('span');
            span.className = 'input-group-text px-3';
            span.innerHTML = '<i data-feather="phone" style="width:13px;height:13px;"></i>';
            inp.className   = 'form-control';
            inp.placeholder = '{{ __('e.g. 011 234 5678') }}';
            outer.appendChild(span);
            outer.appendChild(inp);
        }

        var btn = document.createElement('button');
        btn.type      = 'button';
        btn.className = 'btn btn-sm btn-outline-danger rounded-circle p-0 js-remove-phone flex-shrink-0';
        btn.style.cssText = 'width:28px;height:28px;';
        btn.title     = '{{ __('Remove') }}';
        btn.innerHTML = '<i data-feather="x" style="width:12px;height:12px;"></i>';

        row.appendChild(outer);
        row.appendChild(btn);
        return row;
    }

    /* ── إزالة صف ──────────────────────────────── */
    function attachRemove(row) {
        row.querySelector('.js-remove-phone').addEventListener('click', function () {
            var list = row.closest('[id$="-list"]');
            if (list.querySelectorAll('.phone-row').length > 1) {
                row.remove();
            } else {
                var inp = row.querySelector('input[type="tel"]');
                if (inp) inp.value = '';
            }
            if (window.feather) feather.replace();
        });
    }

    document.querySelectorAll('.phone-row').forEach(attachRemove);

    /* ── إضافة صف ──────────────────────────────── */
    document.querySelectorAll('.js-add-phone').forEach(function (addBtn) {
        addBtn.addEventListener('click', function () {
            var list = document.getElementById(addBtn.dataset.list);
            var row  = buildRow(addBtn.dataset.name, addBtn.dataset.type);
            list.appendChild(row);
            attachRemove(row);
            if (window.feather) feather.replace();
            row.querySelector('input[type="tel"]').focus();
        });
    });

    /* ── اختيار من القائمة (event delegation) ────── */
    document.addEventListener('click', function (e) {
        var opt = e.target.closest('.dial-option');
        if (!opt) return;
        e.preventDefault();
        var code = opt.dataset.code;
        var d    = dialData.find(function(x){ return x.code === code; });
        if (!d) return;

        var dropdown = opt.closest('.dropdown');
        var btn      = dropdown.querySelector('.dial-btn');
        var hidden   = dropdown.querySelector('.dial-hidden-input');

        btn.innerHTML = flagHtml(d) + ' <span class="dial-code-text ms-1">' + code + '</span>';
        hidden.value  = code;

        /* مسح خطأ التحقق عند تغيير الكود */
        var row = dropdown.closest('.phone-row');
        if (row) clearPhoneError(row);

        var bsD = bootstrap.Dropdown.getInstance(btn);
        if (bsD) bsD.hide();
    });

    /* ── client-side validation ─────────────────── */
    function digitsOnly(str) { return (str || '').replace(/\D/g, ''); }

    function showPhoneError(row, msg) {
        clearPhoneError(row);
        var inp = row.querySelector('input[type="tel"]');
        if (inp) inp.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'phone-error text-danger mt-1';
        err.style.fontSize = '11px';
        err.textContent = msg;
        row.after(err);
    }

    function clearPhoneError(row) {
        var inp = row.querySelector('input[type="tel"]');
        if (inp) inp.classList.remove('is-invalid');
        var next = row.nextElementSibling;
        if (next && next.classList.contains('phone-error')) next.remove();
    }

    var form = document.getElementById('phones-list') && document.getElementById('phones-list').closest('form');
    if (form && !form._phoneValidBound) {
        form._phoneValidBound = true;
        form.addEventListener('submit', function (e) {
            var hasError = false;
            document.querySelectorAll('#phones-list .phone-row').forEach(function (row) {
                clearPhoneError(row);
                var inp    = row.querySelector('input[type="tel"]');
                var hidden = row.querySelector('.dial-hidden-input');
                if (!inp || !hidden) return;
                var num = inp.value.trim();
                if (!num) return;
                var code = hidden.value;
                var d    = dialData.find(function(x){ return x.code === code; });
                if (!d) return;
                var digs = digitsOnly(num);
                var min  = d.digits_min, max = d.digits_max;
                if (digs.length < min || digs.length > max) {
                    var country = locale === 'ar' ? d.name_ar : d.name_en;
                    var msg = min === max
                        ? '{{ __('Phone number for :country must be exactly :n digits.') }}'.replace(':country', country).replace(':n', min)
                        : '{{ __('Phone number for :country must be between :min and :max digits.') }}'.replace(':country', country).replace(':min', min).replace(':max', max);
                    showPhoneError(row, msg);
                    hasError = true;
                }
            });
            if (hasError) e.preventDefault();
        });
    }

    /* مسح الخطأ عند الكتابة */
    var phonesList = document.getElementById('phones-list');
    if (phonesList) {
        phonesList.addEventListener('input', function(e) {
            if (e.target.matches('input[type="tel"]')) {
                var row = e.target.closest('.phone-row');
                if (row) clearPhoneError(row);
            }
        });
    }

})();
</script>
@endpush
@endonce
