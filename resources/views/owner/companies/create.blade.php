{{-- Create company --}}
<div class="modal fade" id="modal-campania-create" tabindex="-1" aria-labelledby="modal-campania-create-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content cm-modal">
            <div class="modal-header">
                <div class="cm-modal-titlewrap">
                    <span class="cm-modal-ic" aria-hidden="true"><i data-feather="plus-circle"></i></span>
                    <div>
                        <h5 class="modal-title" id="modal-campania-create-label">{{ __('Add company') }}</h5>
                        <div class="cm-modal-sub">{{ __('Register a new business on the platform.') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <form id="campania-form-create-modal" action="{{ route('owner.companies.store') }}" method="post" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="_modal" value="create">

                    <div class="row g-3">
                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'modal-create-company-name-en',
                            'nameArId' => 'modal-create-company-name-ar',
                            'nameEnValue' => old('_modal') === 'create' ? old('name_en') : '',
                            'nameArValue' => old('_modal') === 'create' ? old('name_ar') : '',
                            'showErrors' => old('_modal') === 'create',
                        ])

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-email">
                                <span class="text-danger">*</span> {{ __('Email') }}
                            </label>
                            <input type="email" name="email" id="modal-create-company-email" maxlength="255" required dir="ltr"
                                value="{{ old('_modal') === 'create' ? old('email') : '' }}"
                                placeholder="example@email.com"
                                class="form-control form-control-lg @if (old('_modal') === 'create' && $errors->has('email')) is-invalid @endif">
                            @if (old('_modal') === 'create')
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-phone">
                                <span class="text-danger">*</span> {{ __('Phone') }}
                            </label>
                            <input type="tel" id="modal-create-company-phone" autocomplete="tel" dir="ltr"
                                class="form-control form-control-lg js-cm-phone"
                                data-hidden="modal-create-company-phone-full" data-error="modal-create-phone-error">
                            <input type="hidden" name="phone" id="modal-create-company-phone-full"
                                value="{{ old('_modal') === 'create' ? old('phone') : '' }}">
                            <div class="cm-phone-error" id="modal-create-phone-error">{{ __('Enter a valid phone number.') }}</div>
                            @if (old('_modal') === 'create')
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-category">
                                <span class="text-danger">*</span> {{ __('Category') }}
                            </label>
                            <select name="category_id" id="modal-create-company-category" required
                                class="form-select form-select-lg @if (old('_modal') === 'create' && $errors->has('category_id')) is-invalid @endif">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('_modal') === 'create' && (string) old('category_id') === (string) $category->id)>
                                        {{ $category->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @if (old('_modal') === 'create')
                                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-status">
                                <span class="text-danger">*</span> {{ __('Status') }}
                            </label>
                            <select name="status" id="modal-create-company-status" required class="form-select form-select-lg">
                                @foreach (['pending', 'active', 'suspended'] as $status)
                                    <option value="{{ $status }}" @selected(old('_modal') === 'create' ? old('status', 'pending') === $status : $status === 'pending')>
                                        {{ __($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-password">
                                <span class="text-danger">*</span> {{ __('Password') }}
                            </label>
                            <input type="password" name="password" id="modal-create-company-password" minlength="8" required dir="ltr"
                                placeholder="••••••••"
                                class="form-control form-control-lg @if (old('_modal') === 'create' && $errors->has('password')) is-invalid @endif">
                            @if (old('_modal') === 'create')
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-logo">{{ __('Logo') }}</label>
                            <input type="file" name="logo" id="modal-create-company-logo"
                                class="form-control js-campania-thumb-input" accept="image/*"
                                data-thumb-wrapper="#thumb-wrap-create-logo">
                            @if (old('_modal') === 'create')
                                @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                            <div id="thumb-wrap-create-logo" class="campania-thumb-wrap d-none mt-3">
                                <img src="" alt="" class="cm-logo-preview" width="72" height="72">
                            </div>
                        </div>
                    </div>

                    <div class="cm-modal-foot">
                        <button type="button" class="cm-btn cm-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="cm-btn cm-btn-primary">
                            <i data-feather="check"></i> {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══════ Shared modal + phone styling / behavior (loaded once for both create & edit) ═══════ --}}
@push('owner-styles')
<link rel="stylesheet" href="{{ asset('backend/assets/vendors/intl-tel-input/css/intlTelInput.min.css') }}">
<style>
/* Modal shell */
/* overflow:visible so the inline country dropdown (anchored to the phone field) isn't clipped */
.modal-content.cm-modal { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:18px; overflow:visible; box-shadow:var(--bk-shadow-xl); }
.cm-modal .modal-header { border:none; padding:22px 24px 4px; position:relative; align-items:flex-start; }
.cm-modal-titlewrap { display:flex; align-items:center; gap:14px; }
.cm-modal-ic { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid var(--bk-border); flex-shrink:0; }
.cm-modal-ic i { width:22px; height:22px; }
.cm-modal-ic.cm-modal-ic-danger { background:var(--bk-danger-bg); color:var(--bk-danger); }
.cm-btn-danger { background:var(--bk-danger); color:#fff; box-shadow:var(--bk-shadow); }
.cm-btn-danger:hover { filter:brightness(.93); color:#fff; transform:translateY(-1px); }
.cm-modal .modal-title { font-family:'Fraunces', 'Tajawal', Georgia, serif !important; font-weight:600; font-size:1.35rem; color:var(--bk-text); margin:0; line-height:1.1; }
.cm-modal-sub { font-size:.82rem; color:var(--bk-text-muted); margin-top:3px; }
.cm-modal .btn-close { position:absolute; top:20px; inset-inline-end:20px; opacity:.6; filter:var(--bk-btnclose-filter, none); }
.cm-theme-dark-close .btn-close { filter:invert(1) grayscale(1); }
.cm-modal .modal-body { padding:18px 24px 24px; }

/* Inputs — retheme Bootstrap controls to the GlowRez identity */
.cm-modal .form-label { font-size:.8rem; font-weight:600; color:var(--bk-text-soft); margin-bottom:6px; }
.cm-modal .form-control, .cm-modal .form-select {
    background-color:var(--bk-bg); border:1px solid var(--bk-border); color:var(--bk-text);
    border-radius:11px; padding:.62rem .85rem; font-size:.9rem; height:auto; box-shadow:none;
    transition:border-color .15s, box-shadow .15s, background-color .15s; }
/* single clean chevron for selects (the shorthand above would otherwise tile the default arrow) */
.cm-modal .form-select {
    -webkit-appearance:none; -moz-appearance:none; appearance:none;
    padding-inline-end:2.3rem;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%237B7C6D' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right .9rem center; background-size:14px; }
html[dir="rtl"] .cm-modal .form-select { background-position:left .9rem center; }
.cm-modal .form-control-lg, .cm-modal .form-select-lg { min-height:46px; }
.cm-modal .form-control:focus, .cm-modal .form-select:focus {
    border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); background:var(--bk-surface); color:var(--bk-text); }
.cm-modal .form-control::placeholder { color:var(--bk-text-muted); opacity:.8; }
.cm-modal .form-control.is-invalid, .cm-modal .form-select.is-invalid { border-color:var(--bk-danger); }
.cm-modal .invalid-feedback { color:var(--bk-danger); font-size:.78rem; }
.cm-modal .text-danger { color:var(--bk-danger) !important; }
.cm-modal input[type="file"].form-control { padding:.5rem .85rem; cursor:pointer; }
.cm-modal input[type="file"]::file-selector-button {
    background:var(--bk-accent-wash); color:var(--bk-accent); border:none; border-radius:8px;
    padding:.35rem .8rem; margin-inline-end:.8rem; font-weight:600; font-size:.82rem; cursor:pointer; }
.cm-logo-preview { border-radius:12px; border:1px solid var(--bk-border); object-fit:cover; box-shadow:var(--bk-shadow); }

/* Footer */
.cm-modal-foot { display:flex; justify-content:flex-end; gap:10px; margin-top:24px; padding-top:18px; border-top:1px solid var(--bk-border); }

/* Import steps */
.cm-import-steps { margin:0 0 16px; padding-inline-start:1.15rem; color:var(--bk-text-soft); font-size:.86rem; line-height:1.65; }
.cm-import-steps li { margin-bottom:7px; }
.cm-import-steps li::marker { color:var(--bk-gold-strong); font-weight:700; }
.cm-import-tmpl { display:inline-flex; align-items:center; gap:5px; color:var(--bk-accent); font-weight:600; white-space:nowrap; }
.cm-import-tmpl i { width:14px; height:14px; }
.cm-import-tmpl:hover { color:var(--bk-gold-strong); }

/* intl-tel-input theming */
.cm-modal .iti { width:100%; }
.cm-modal .iti input.form-control { width:100%; }
.cm-modal .iti__dropdown-content, .cm-modal .iti__country-list { background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-text); border-radius:10px; box-shadow:var(--bk-shadow-lg); }
.iti__dropdown-content { z-index:2000 !important; }
.cm-modal .iti__country.iti__highlight, .cm-modal .iti__country:hover { background:var(--bk-accent-wash); }
.cm-modal .iti__dial-code { color:var(--bk-text-muted); }
.cm-modal .iti__search-input { background:var(--bk-bg); color:var(--bk-text); border-color:var(--bk-border); }
.cm-modal .iti--separate-dial-code .iti__selected-country { background:var(--bk-bg); border-radius:11px 0 0 11px; }
html[dir="rtl"] .cm-modal .iti--separate-dial-code .iti__selected-country { border-radius:0 11px 11px 0; }
.cm-modal .iti__selected-dial-code { color:var(--bk-text); font-weight:600; }
html[dir="rtl"] .cm-modal .iti__selected-dial-code, html[dir="rtl"] .cm-modal .iti__dial-code { direction:ltr; unicode-bidi:embed; }
.cm-phone-error { display:none; font-size:.78rem; color:var(--bk-danger); margin-top:6px; }
.cm-phone-error.show { display:block; }

/* New Syrian flag (green–white–black, 3 red stars). Global (not .cm-modal-scoped)
   because the country dropdown is appended to <body>, so it must be reachable for
   both the selected flag and the list. */
.iti__flag.iti__sy {
    background-image: url("{{ asset('backend/assets/vendors/intl-tel-input/img/flag-sy-new.svg') }}") !important;
    background-position: center !important;
    background-size: 16px 12px !important;
    background-repeat: no-repeat !important;
    box-shadow: none !important;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('backend/assets/vendors/intl-tel-input/js/intlTelInput.min.js') }}"></script>
<script>
(function () {
    'use strict';
    if (!window.intlTelInput) return;

    var OPTS = {
        initialCountry: 'sy',
        countryOrder: ['sy', 'lb', 'jo', 'iq', 'tr', 'sa', 'ae', 'eg'],
        separateDialCode: true,
        strictMode: true,
        autoPlaceholder: 'aggressive',
        placeholderNumberType: 'MOBILE',
        formatOnDisplay: true,
        useFullscreenPopup: false,   // desktop admin: anchor the list under the field, not a body-level fullscreen popup
        utilsScript: '{{ asset('backend/assets/vendors/intl-tel-input/js/utils.js') }}',
    };

    function setup(inputId) {
        var input = document.getElementById(inputId);
        if (!input) return null;
        var hidden = document.getElementById(input.dataset.hidden);
        var errEl  = document.getElementById(input.dataset.error);
        var form   = input.closest('form');
        var iti    = window.intlTelInput(input, OPTS);

        function sync() { hidden.value = input.value.trim() !== '' ? iti.getNumber() : ''; }

        input.addEventListener('input', function () { errEl && errEl.classList.remove('show'); input.classList.remove('is-invalid'); sync(); });
        input.addEventListener('countrychange', sync);
        input.addEventListener('blur', function () {
            if (input.value.trim() === '') { errEl && errEl.classList.remove('show'); input.classList.remove('is-invalid'); return; }
            var bad = !iti.isValidNumber();
            errEl && errEl.classList.toggle('show', bad);
            input.classList.toggle('is-invalid', bad);
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                if (input.value.trim() !== '' && !iti.isValidNumber()) {
                    e.preventDefault(); e.stopPropagation();
                    errEl && errEl.classList.add('show');
                    input.classList.add('is-invalid');
                    input.focus();
                    return;
                }
                sync();
            });
        }

        // Restore a previously-entered number (server error repopulation).
        iti.promise.then(function () { if (hidden.value) { iti.setNumber(hidden.value); } sync(); });
        return iti;
    }

    var createIti = setup('modal-create-company-phone');
    var editIti   = setup('modal-edit-company-phone');

    // Edit modal: the row's data-company-phone is dropped into the visible input
    // by the modal-behavior script on open — convert it into the flag/dial-code
    // control right after, and clear it cleanly on close.
    var editModal = document.getElementById('modal-campania-edit');
    if (editModal && editIti) {
        editModal.addEventListener('show.bs.modal', function () {
            setTimeout(function () {
                var input  = document.getElementById('modal-edit-company-phone');
                var hidden = document.getElementById('modal-edit-company-phone-full');
                var raw = (input.value || '').trim();
                if (raw) { editIti.setNumber(raw); hidden.value = editIti.getNumber(); }
                else     { editIti.setNumber(''); hidden.value = ''; }
            }, 0);
        });
        editModal.addEventListener('hidden.bs.modal', function () {
            editIti.setNumber('');
            var hidden = document.getElementById('modal-edit-company-phone-full');
            if (hidden) hidden.value = '';
        });
    }

    if (window.feather) window.feather.replace();
})();
</script>
@endpush
