{{--
  Shared delete confirmation — now powered by SweetAlert2 via the unified
  GlowRez engine (resources/views/partials/glow-toast.blade.php).
  Usage is UNCHANGED for call-sites:
      bkConfirmDelete('{{ route(...) }}', 'display name', '{{ __('optional message') }}')
  The old Bootstrap modal markup + local function were removed; window.bkConfirmDelete
  (defined in the engine) shows a branded SweetAlert2 and submits a DELETE form.
--}}
@include('partials.glow-toast')
