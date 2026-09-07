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

    // Client-side mirror of the server capacity guard: warn + block submit when
    // the requested credits exceed what Rasel can still deliver. The server
    // re-checks regardless, so this is UX only.
    var credits = document.getElementById('sxGrantCredits');
    var hint    = document.getElementById('sxGrantCreditsHint');
    var submit  = document.getElementById('sxGrantSubmit');
    if (credits && credits.dataset.available !== undefined) {
        var available = parseInt(credits.dataset.available, 10);
        var overMsg   = credits.dataset.overMsg || '';
        var check = function () {
            var n = parseInt(credits.value, 10);
            var over = !isNaN(n) && n > available;
            if (hint) { hint.textContent = over ? overMsg : ''; hint.hidden = !over; }
            if (submit) { submit.disabled = over; }
        };
        credits.addEventListener('input', check);
        check();
    }
})();
</script>
@endpush
