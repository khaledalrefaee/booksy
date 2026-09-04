{{--
  Owner flash → unified GlowRez toast engine (partials/glow-toast).
  Surfaces session success/error/warning/info AND validation errors as toasts.
--}}
@include('partials.glow-toast')

@if (session('success') || session('error') || session('warning') || session('info') || $errors->any())
<script>
(function () {
    function fire() {
        @if (session('success')) window.GlowToast.success(@json(session('success'))); @endif
        @if (session('warning')) window.GlowToast.warning(@json(session('warning'))); @endif
        @if (session('info'))    window.GlowToast.info(@json(session('info')));       @endif
        @if (session('error'))   window.GlowToast.error(@json(session('error')));     @endif
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                window.GlowToast.error(@json($error));
            @endforeach
        @endif
    }
    if (window.GlowToast) fire();
    else document.addEventListener('DOMContentLoaded', fire);
})();
</script>
@endif
