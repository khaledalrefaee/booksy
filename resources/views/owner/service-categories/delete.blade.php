<div class="modal fade" id="modal-service-category-delete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Delete service category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Are you sure you want to delete') }} <strong id="delete-sc-name-display"></strong>?</p>
                <form id="form-service-category-delete" method="post" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
