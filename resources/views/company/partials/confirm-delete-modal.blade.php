{{--
  Shared delete-confirmation modal.
  Include once per page, then call from any delete button:
      bkConfirmDelete('{{ route(...) }}', 'display name', '{{ __('optional custom message') }}')
--}}
<div class="modal fade" id="bkDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4">
            <div class="modal-body text-center p-4">
                <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                <h6 class="fw-bold mb-2">{{ __('Are you sure?') }}</h6>
                <p class="text-muted small mb-1 fw-semibold" id="bkDeleteName"></p>
                <p class="text-muted small mb-3" id="bkDeleteMsg">{{ __('This action cannot be undone.') }}</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form method="POST" id="bkDeleteForm">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bkConfirmDelete(actionUrl, name, message) {
    document.getElementById('bkDeleteForm').action = actionUrl;
    document.getElementById('bkDeleteName').textContent = name || '';
    document.getElementById('bkDeleteMsg').textContent = message || '{{ __('This action cannot be undone.') }}';
    new bootstrap.Modal(document.getElementById('bkDeleteModal')).show();
}
</script>
