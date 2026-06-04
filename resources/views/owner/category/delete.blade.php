{{-- Delete confirmation --}}
<div class="modal fade" id="modal-category-delete" tabindex="-1" aria-labelledby="modal-category-delete-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-category-delete" method="post" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-category-delete-label">{{ __('Delete category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Are you sure you want to delete this category?') }}</p>
                    <p class="fw-semibold mb-0 mt-2 text-danger" id="delete-category-name-display"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>