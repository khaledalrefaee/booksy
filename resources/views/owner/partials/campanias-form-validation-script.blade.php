@php
    $selectorsList =
        isset($formSelectors) && is_array($formSelectors)
            ? array_values(array_filter($formSelectors))
            : [];
@endphp
@if ($selectorsList !== [])
<script>
jQuery(function ($) {
    'use strict';
    var selectors = @json($selectorsList);
    if (!$ || typeof $.fn.validate !== 'function') {
        return;
    }

    // Logo guard: reject an oversized or non-image file on the client, BEFORE the
    // multipart POST is sent. An image larger than the server's post_max_size makes
    // PHP discard the whole request body (so Laravel never runs its own validation)
    // and can reset an HTTP/2 connection — surfacing as a blank ERR_HTTP2 screen
    // instead of a friendly message. Blocking submit here avoids sending it at all.
    var LOGO_MAX_KB = 4096; // keep in sync with the `max:4096` rule on the server
    var allowedImgExt = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg', '.tif', '.tiff'];

    if (!$.validator.methods.campaniaImageMime) {
        $.validator.addMethod('campaniaImageMime', function (value, element) {
            if (!element.files || element.files.length === 0) {
                return true;
            }
            var type = (element.files[0].type || '').toLowerCase();
            if (type.indexOf('image/') === 0) {
                return true;
            }
            var basename = element.value.replace(/^.*(\\|\/)/, '').toLowerCase();
            var dot = basename.lastIndexOf('.');
            return dot >= 0 && allowedImgExt.indexOf(basename.slice(dot)) !== -1;
        }, @json(__('The logo must be an image file.')));
    }

    if (!$.validator.methods.campaniaMaxFileKb) {
        $.validator.addMethod('campaniaMaxFileKb', function (value, element, maxKb) {
            if (!element.files || element.files.length === 0) {
                return true;
            }
            return Math.ceil(element.files[0].size / 1024) <= maxKb;
        }, $.validator.format(@json(__('The logo must not be larger than :max MB.', ['max' => 4])) ));
    }

    selectors.forEach(function (sel) {
        var $form = $(sel);
        if (!$form.length || $form.data('validator')) {
            return;
        }
        $form.validate({
            ignore: [],
            errorElement: 'div',
            errorClass: 'invalid-feedback d-block',
            highlight: function (el) {
                $(el).addClass('is-invalid');
            },
            unhighlight: function (el) {
                $(el).removeClass('is-invalid');
            },
            rules: {
                name_en: { required: true, maxlength: 255 },
                name_ar: { required: true, maxlength: 255 },
                owner_name: { maxlength: 255 },
                email: { required: true, email: true, maxlength: 255 },
                category_id: { required: true },
                status: { required: true },
                logo: { campaniaImageMime: true, campaniaMaxFileKb: LOGO_MAX_KB }
            }
        });
        if (sel === '#campania-form-create-modal') {
            $form.rules('add', { password: { required: true, minlength: 8 } });
            $form.find('[name="owner_name"]').rules('add', { required: true });
        }

        // Re-check the logo the moment a file is picked, so the error shows before submit.
        $form.find('input[type="file"][name="logo"]').on('change', function () {
            $(this).valid();
        });
    });
});
</script>
@endif
