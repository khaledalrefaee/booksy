{{-- Delete company --}}
<div class="modal fade" id="modal-campania-delete" tabindex="-1" aria-labelledby="modal-campania-delete-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-campania-delete" method="post" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-campania-delete-label">{{ __('Delete company') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Are you sure you want to delete this company?') }}</p>
                    <p class="fw-semibold mb-0 mt-2 text-danger" id="delete-company-name-display"></p>
                    <p class="text-muted small mt-2 mb-0">{{ __('All branches, services, and appointments linked to this company will be removed.') }}</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
