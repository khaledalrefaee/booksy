{{--
  Owner flash → unified GlowRez toast engine (partials/glow-toast).
  Surfaces session success/error/warning/info AND validation errors as toasts.
--}}
{{-- Session/validation flash is fired inside the engine (@once) — see glow-toast.blade.php. --}}
@include('partials.glow-toast')
