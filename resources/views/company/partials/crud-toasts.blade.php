{{--
  Company CRUD toasts → now powered by the unified GlowRez toast engine
  (resources/views/partials/glow-toast.blade.php). Keeps SweetAlert2 loaded
  for bkConfirm(); surfaces session success/error/warning/info as toasts.
  bkToast() / bkConfirm() / bkDismissCt() remain available (defined by engine).
--}}
{{-- SweetAlert2 for confirmation dialogs (bkConfirm) --}}
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>

{{-- Session/validation flash is fired inside the engine (@once) — see glow-toast.blade.php. --}}
@include('partials.glow-toast')
