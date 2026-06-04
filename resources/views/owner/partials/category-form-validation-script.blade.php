{{-- Requires jQuery + jquery.validate (loaded from owner.partials.js after core.js)

     Pass either:
       $formSelector => '#single-form-id'
       $formSelectors => ['#first', '#second']
--}}
@php
    $selectorsList =
        isset($formSelectors) && is_array($formSelectors)
            ? array_values(array_filter($formSelectors))
            : (isset($formSelector) && $formSelector
                ? [$formSelector]
                : []);
@endphp
@if ($selectorsList === [])
@else
@php
    $msgs = [
        'names_pair' => __('Either English or Arabic name must be provided.'),
        'name_maxlength' => __('The name field must not be greater than 255 characters.'),
        'image_must_be_image' => __('The image must be an image file.'),
        'image_max_kb' => __('The image field must not be greater than :max kilobytes.', ['max' => 4096]),
        'icon_must_be_image' => __('The icon must be an image file.'),
        'icon_max_kb' => __('The icon field must not be greater than :max kilobytes.', ['max' => 4096]),
    ];
@endphp
<script>
jQuery(function ($) {
    'use strict';

    var CATEGORY_MSG = @json($msgs);
    var selectors = @json($selectorsList);

    if (!$ || typeof $.fn.valid !== 'function') {
        return;
    }

    var allowedImgExt = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg', '.tif', '.tiff'];

    if (!$.validator.methods.catImageMime) {
        $.validator.addMethod('catImageMime', function (value, element) {
            if (!element.files || element.files.length === 0) {
                return true;
            }
            var type = (element.files[0].type || '').toLowerCase();
            if (type.indexOf('image/') === 0) {
                return true;
            }
            var basename = element.value.replace(/^.*(\\|\/)/, '').toLowerCase();
            var dot = basename.lastIndexOf('.');
            if (dot < 0) {
                return false;
            }
            var ext = basename.slice(dot);
            return allowedImgExt.indexOf(ext) !== -1;
        });
    }

    if (!$.validator.methods.catMaxFileKb) {
        $.validator.addMethod('catMaxFileKb', function (value, element, maxKb) {
            if (!element.files || element.files.length === 0) {
                return true;
            }
            var sizeKb = Math.ceil(element.files[0].size / 1024);
            return sizeKb <= maxKb;
        });
    }

    if (!$.validator.methods.catOneLocaleName) {
        $.validator.addMethod('catOneLocaleName', function (value, element) {
            var $form = $(element).closest('form');
            var en = String($form.find('input[name=name_en]').val() || '').trim();
            var ar = String($form.find('input[name=name_ar]').val() || '').trim();
            return en.length > 0 || ar.length > 0;
        });
    }

    var commonOpts = {
        rules: {
            name_en: {
                catOneLocaleName: true,
                maxlength: 255
            },
            name_ar: {
                catOneLocaleName: true,
                maxlength: 255
            },
            image: {
                catImageMime: true,
                catMaxFileKb: 4096
            },
            icon: {
                catImageMime: true,
                catMaxFileKb: 4096
            }
        },
        messages: {
            name_en: {
                catOneLocaleName: CATEGORY_MSG.names_pair,
                maxlength: CATEGORY_MSG.name_maxlength
            },
            name_ar: {
                catOneLocaleName: CATEGORY_MSG.names_pair,
                maxlength: CATEGORY_MSG.name_maxlength
            },
            image: {
                catImageMime: CATEGORY_MSG.image_must_be_image,
                catMaxFileKb: CATEGORY_MSG.image_max_kb
            },
            icon: {
                catImageMime: CATEGORY_MSG.icon_must_be_image,
                catMaxFileKb: CATEGORY_MSG.icon_max_kb
            }
        },
        onkeyup: function (element) {
            $(element).valid();
        },
        onfocusout: function (element) {
            $(element).valid();
        },
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback').removeClass('d-none');
            error.insertAfter(element);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        },
        ignore: '[type="hidden"], .no-validate'
    };

    selectors.forEach(function (sel) {
        var $form = $(sel);
        if (!$form.length || $form.data('validator')) {
            return;
        }
        $form.validate(commonOpts);

        $('input[type="file"]', $form).on('change input', function () {
            $(this).valid();
        });
    });
});
</script>
@endif
