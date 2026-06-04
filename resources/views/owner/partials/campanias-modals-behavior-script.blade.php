<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    document.querySelectorAll('.js-campania-thumb-input').forEach(function (input) {
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

    function resolveCampaniaEditTrigger(relatedTarget) {
        if (!relatedTarget || !(relatedTarget instanceof HTMLElement)) {
            return null;
        }
        return relatedTarget.closest('[data-update-url]');
    }

    function fillCampaniaEditForm(btn) {
            if (!btn) {
                return;
            }
            var form = document.getElementById('campania-form-update-modal');
            var updateUrl = btn.getAttribute('data-update-url') || '';
            if (form && updateUrl) {
                form.setAttribute('action', updateUrl);
            }
            var idField = document.getElementById('modal-company-id-storage');
            if (idField) {
                idField.value = btn.getAttribute('data-company-id') || '';
            }
            var nameEnField = document.getElementById('modal-edit-company-name-en');
            if (nameEnField) {
                nameEnField.value = btn.getAttribute('data-company-name-en') || '';
            }
            var nameArField = document.getElementById('modal-edit-company-name-ar');
            if (nameArField) {
                nameArField.value = btn.getAttribute('data-company-name-ar') || '';
            }
            var emailField = document.getElementById('modal-edit-company-email');
            if (emailField) {
                emailField.value = btn.getAttribute('data-company-email') || '';
            }
            var phoneField = document.getElementById('modal-edit-company-phone');
            if (phoneField) {
                phoneField.value = btn.getAttribute('data-company-phone') || '';
            }
            var categoryField = document.getElementById('modal-edit-company-category');
            if (categoryField) {
                categoryField.value = btn.getAttribute('data-company-category-id') || '';
            }
            var passwordField = document.getElementById('modal-edit-company-password');
            if (passwordField) {
                passwordField.value = '';
            }
            var logoSrc = btn.getAttribute('data-logo-src') || '';
            var wrap = document.getElementById('thumb-wrap-edit-logo');
            var input = document.getElementById('modal-edit-company-logo');
            if (input) {
                input.value = '';
            }
            if (wrap && wrap.querySelector('img')) {
                var im = wrap.querySelector('img');
                if (logoSrc) {
                    im.src = logoSrc;
                    wrap.classList.remove('d-none');
                } else {
                    wrap.classList.add('d-none');
                    im.removeAttribute('src');
                }
            }
    }

    var modalEditEl = document.getElementById('modal-campania-edit');
    var updateForm = document.getElementById('campania-form-update-modal');

    if (updateForm) {
        updateForm.addEventListener('submit', function (event) {
            var action = (updateForm.getAttribute('action') || '').trim();
            if (action === '' || action === window.location.pathname) {
                event.preventDefault();
                return;
            }
            var methodInput = updateForm.querySelector('input[name="_method"]');
            if (!methodInput || methodInput.value !== 'PUT') {
                event.preventDefault();
            }
        });
    }

    if (modalEditEl && typeof bootstrap !== 'undefined') {
        modalEditEl.addEventListener('show.bs.modal', function (event) {
            fillCampaniaEditForm(resolveCampaniaEditTrigger(event.relatedTarget));
        });

        modalEditEl.addEventListener('hidden.bs.modal', function () {
            if (typeof jQuery !== 'undefined') {
                var $f = jQuery('#campania-form-update-modal');
                var validator = $f.data('validator');
                if (validator) {
                    validator.resetForm();
                }
                var el = document.getElementById('campania-form-update-modal');
                if (el) {
                    el.reset();
                }
            }
            var formEl = document.getElementById('campania-form-update-modal');
            if (formEl) {
                formEl.setAttribute('action', '');
            }
            var idHidden = document.getElementById('modal-company-id-storage');
            if (idHidden) {
                idHidden.value = '';
            }
            resetCampaniaThumb('#thumb-wrap-edit-logo');
        });
    }

    function bindCampaniaDeleteForm(btn) {
        if (!btn) {
            return;
        }
        var form = document.getElementById('form-campania-delete');
        var span = document.getElementById('delete-company-name-display');
        var url = btn.getAttribute('data-delete-url') || '';

        if (form && url) {
            form.setAttribute('action', url);
        }

        if (span) {
            span.textContent = btn.getAttribute('data-company-display') || '';
        }
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-bs-target="#modal-campania-delete"][data-delete-url]');
        if (btn) {
            bindCampaniaDeleteForm(btn);
        }
    });

    var modalDeleteEl = document.getElementById('modal-campania-delete');
    if (modalDeleteEl && typeof bootstrap !== 'undefined') {
        modalDeleteEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (btn && btn instanceof HTMLElement) {
                btn = btn.closest('[data-delete-url]') || btn;
            }
            bindCampaniaDeleteForm(btn);
        });

        modalDeleteEl.addEventListener('hidden.bs.modal', function () {
            var form = document.getElementById('form-campania-delete');
            if (form) {
                form.setAttribute('action', '');
            }
            var span = document.getElementById('delete-company-name-display');
            if (span) {
                span.textContent = '';
            }
        });
    }

    var modalCreateEl = document.getElementById('modal-campania-create');
    if (modalCreateEl) {
        modalCreateEl.addEventListener('hidden.bs.modal', function () {
            if (typeof jQuery !== 'undefined') {
                var $f = jQuery('#campania-form-create-modal');
                var validator = $f.data('validator');
                if (validator) {
                    validator.resetForm();
                }
            }
            var f = document.getElementById('campania-form-create-modal');
            if (f) {
                f.reset();
            }
            resetCampaniaThumb('#thumb-wrap-create-logo');
        });
    }

    function resetCampaniaThumb(sel) {
        document.querySelectorAll(sel).forEach(function (wrap) {
            wrap.classList.add('d-none');
            var im = wrap.querySelector('img');
            if (im) {
                im.removeAttribute('src');
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
