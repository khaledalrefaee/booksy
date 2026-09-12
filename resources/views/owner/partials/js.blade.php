<script src="{{ asset('backend/assets/vendors/core/core.js') }}"></script>

{{-- DataTables 1.13 + Buttons + Responsive (local) --}}
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/buttons.bootstrap5.min.js') }}"></script>
<script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('vendor/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/buttons.print.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/responsive.bootstrap5.min.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/jquery-validation/jquery.validate.min.js') }}"></script>
@if (app()->getLocale() === 'ar')
{{-- Arabic defaults for jQuery Validation (per-field custom messages still override these). --}}
<script>
jQuery(function ($) {
    'use strict';
    if (!$ || !$.validator || !$.validator.messages) {
        return;
    }
    $.extend($.validator.messages, {
        required: "هذا الحقل إلزامي",
        remote: "يرجى تصحيح هذا الحقل للمتابعة",
        email: "رجاء إدخال عنوان بريد إلكتروني صحيح",
        url: "رجاء إدخال عنوان موقع إلكتروني صحيح",
        date: "رجاء إدخال تاريخ صحيح",
        dateISO: "رجاء إدخال تاريخ صحيح (ISO)",
        number: "رجاء إدخال عدد بطريقة صحيحة",
        digits: "رجاء إدخال أرقام فقط",
        creditcard: "رجاء إدخال رقم بطاقة ائتمان صحيح",
        equalTo: "رجاء إدخال نفس القيمة",
        extension: "رجاء إدخال ملف بامتداد موافق عليه",
        maxlength: $.validator.format("الحد الأقصى لعدد الحروف هو {0}"),
        minlength: $.validator.format("الحد الأدنى لعدد الحروف هو {0}"),
        rangelength: $.validator.format("عدد الحروف يجب أن يكون بين {0} و {1}"),
        range: $.validator.format("رجاء إدخال عدد قيمته بين {0} و {1}"),
        max: $.validator.format("رجاء إدخال عدد أقل من أو يساوي {0}"),
        min: $.validator.format("رجاء إدخال عدد أكبر من أو يساوي {0}")
    });
});
</script>
@endif

<script src="{{ asset('backend/assets/vendors/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/template.js') }}"></script>

{{-- Optional: flatpickr + ApexCharts + dashboard demo JS on pages that @push to this stack. --}}
@stack('owner-after-template')

{{-- Button loading state (opt-in): add data-loading to a submit button --}}
<script>
document.addEventListener('submit', function (e) {
    var btn = e.target.querySelector('button[type="submit"][data-loading], [data-loading].btn');
    if (btn && !btn.classList.contains('is-loading')) {
        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');
    }
}, true);
</script>
