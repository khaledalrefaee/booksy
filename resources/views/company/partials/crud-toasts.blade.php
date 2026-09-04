{{--
  Company CRUD toasts → now powered by the unified GlowRez toast engine
  (resources/views/partials/glow-toast.blade.php). Keeps SweetAlert2 loaded
  for bkConfirm(); surfaces session success/error/warning/info as toasts.
  bkToast() / bkConfirm() / bkDismissCt() remain available (defined by engine).
--}}
{{-- SweetAlert2 for confirmation dialogs (bkConfirm) --}}
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>

@include('partials.glow-toast')

@if(session('success') || session('error') || session('warning') || session('info'))
<script>
(function(){
    function fire(){
        @if(session('success')) window.GlowToast.success(@json(session('success'))); @endif
        @if(session('warning')) window.GlowToast.warning(@json(session('warning'))); @endif
        @if(session('info'))    window.GlowToast.info(@json(session('info')));       @endif
        @if(session('error'))   window.GlowToast.error(@json(session('error')));     @endif
    }
    if (window.GlowToast) fire();
    else document.addEventListener('DOMContentLoaded', fire);
})();
</script>
@endif
