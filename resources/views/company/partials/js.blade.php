<script src="{{ asset('backend/assets/vendors/core/core.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/template.js') }}"></script>

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
