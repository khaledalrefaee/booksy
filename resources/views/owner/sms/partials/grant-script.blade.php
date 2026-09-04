@push('scripts')
<script>
(function () {
    'use strict';
    var company = document.getElementById('sxGrantCompany');
    var branch  = document.getElementById('sxGrantBranch');
    if (!company || !branch) return;

    var tpl = company.getAttribute('data-branches-url'); // .../companies/CID/branches
    var poolLabel = branch.options.length ? branch.options[0].textContent : 'Company pool';

    company.addEventListener('change', function () {
        branch.innerHTML = '<option value="">' + poolLabel + '</option>';
        if (!company.value) return;
        branch.disabled = true;
        fetch(tpl.replace('CID', company.value), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (list) {
                (list || []).forEach(function (b) {
                    var o = document.createElement('option');
                    o.value = b.id; o.textContent = b.name;
                    branch.appendChild(o);
                });
            })
            .catch(function () {})
            .finally(function () { branch.disabled = false; });
    });
})();
</script>
@endpush
