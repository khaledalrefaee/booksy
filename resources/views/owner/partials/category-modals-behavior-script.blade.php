<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    document.querySelectorAll('.js-category-thumb-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var sel = input.getAttribute('data-thumb-wrapper');
            if (!sel) {
                return;
            }
            var wrap = document.querySelector(sel);
            if (!wrap) {
                return;
            }
            var img = wrap.querySelector('img');
            if (!img) {
                return;
            }
            var file = input.files && input.files[0];
            if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                wrap.classList.add('d-none');
                img.removeAttribute('src');
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = String(e.target && e.target.result ? e.target.result : '');
                wrap.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    });

    var modalEditEl = document.getElementById('modal-category-edit');

    if (modalEditEl && typeof bootstrap !== 'undefined') {
        modalEditEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn || !(btn instanceof HTMLElement)) {
                return;
            }
            var form = document.getElementById('category-form-update-modal');
            var updateUrl = btn.getAttribute('data-update-url') || '';
            if (form && updateUrl) {
                form.setAttribute('action', updateUrl);
            }
            var idField = document.getElementById('modal-category-id-storage');
            if (idField) {
                idField.value = btn.getAttribute('data-category-id') || '';
            }
            var nameFieldEn = document.getElementById('modal-edit-name-en');
            var nameFieldAr = document.getElementById('modal-edit-name-ar');
            if (nameFieldEn) {
                nameFieldEn.value = btn.getAttribute('data-category-name-en') || '';
            }
            if (nameFieldAr) {
                nameFieldAr.value = btn.getAttribute('data-category-name-ar') || '';
            }
            var sortOrderField = document.getElementById('modal-edit-sort-order');
            if (sortOrderField) {
                sortOrderField.value = btn.getAttribute('data-category-sort-order') || '0';
            }
            ['image', 'icon'].forEach(function (kind) {
                var src = btn.getAttribute('data-' + kind + '-src') || '';
                var wrap = document.getElementById('thumb-wrap-edit-' + kind);
                var input = document.getElementById('modal-edit-' + kind);
                if (input) {
                    input.value = '';
                }
                if (wrap && wrap.querySelector('img')) {
                    var im = wrap.querySelector('img');
                    if (src) {
                        im.src = src;
                        wrap.classList.remove('d-none');
                    } else {
                        wrap.classList.add('d-none');
                        im.removeAttribute('src');
                    }
                }
            });
        });

        modalEditEl.addEventListener('hidden.bs.modal', function () {
            if (typeof jQuery !== 'undefined') {
                var $f = jQuery('#category-form-update-modal');
                var validator = $f.data('validator');
                if (validator) {
                    validator.resetForm();
                }
                var el = document.getElementById('category-form-update-modal');
                if (el) {
                    el.reset();
                }
            }
            var formEl = document.getElementById('category-form-update-modal');
            if (formEl) {
                formEl.setAttribute('action', '');
            }
            var idHidden = document.getElementById('modal-category-id-storage');
            if (idHidden) {
                idHidden.value = '';
            }
            resetThumbWraps('#thumb-wrap-edit-image, #thumb-wrap-edit-icon');
        });

        modalEditEl.addEventListener('shown.bs.modal', function () {
            if (typeof window.feather !== 'undefined') {
                window.feather.replace();
            }
        });
    }

    var modalDeleteEl = document.getElementById('modal-category-delete');
    if (modalDeleteEl && typeof bootstrap !== 'undefined') {
        modalDeleteEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var form = document.getElementById('form-category-delete');
            var span = document.getElementById('delete-category-name-display');
            if (form && btn) {
                var url = btn.getAttribute('data-delete-url') || '';
                form.setAttribute('action', url);
            }
            if (span && btn) {
                span.textContent = btn.getAttribute('data-category-display') || '';
            }
        });
        modalDeleteEl.addEventListener('hidden.bs.modal', function () {
            var form = document.getElementById('form-category-delete');
            if (form) {
                form.setAttribute('action', '');
            }
            var span = document.getElementById('delete-category-name-display');
            if (span) {
                span.textContent = '';
            }
        });
        modalDeleteEl.addEventListener('shown.bs.modal', function () {
            if (typeof window.feather !== 'undefined') {
                window.feather.replace();
            }
        });
    }

    function resetThumbWraps(sel) {
        document.querySelectorAll(sel).forEach(function (wrap) {
            wrap.classList.add('d-none');
            var im = wrap.querySelector('img');
            if (im) {
                im.removeAttribute('src');
            }
        });
    }

    var modalCreateEl = document.getElementById('modal-category-create');
    if (modalCreateEl) {
        modalCreateEl.addEventListener('hidden.bs.modal', function () {
            if (typeof jQuery !== 'undefined') {
                var $f = jQuery('#category-form-create-modal');
                var validator = $f.data('validator');
                if (validator) {
                    validator.resetForm();
                }
            }
            var f = document.getElementById('category-form-create-modal');
            if (f) {
                f.reset();
            }
            resetThumbWraps('#thumb-wrap-create-image, #thumb-wrap-create-icon');
        });
        modalCreateEl.addEventListener('shown.bs.modal', function () {
            if (typeof window.feather !== 'undefined') {
                window.feather.replace();
            }
        });
    }

    function openBootstrapModal(el) {
        if (!el || typeof bootstrap === 'undefined') {
            return;
        }
        try {
            var inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            inst.show();
        } catch (e) {}
    }

    @if (old('_modal') === 'create' && $errors->any())
    openBootstrapModal(modalCreateEl);
    @endif

    @if (old('_modal') === 'edit' && $errors->any())
    openBootstrapModal(modalEditEl);
    @endif
});
</script>
