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
                email: { required: true, email: true, maxlength: 255 },
                category_id: { required: true },
                status: { required: true }
            }
        });
        if (sel === '#campania-form-create-modal') {
            $form.rules('add', { password: { required: true, minlength: 8 } });
        }
    });
});
</script>
@endif
