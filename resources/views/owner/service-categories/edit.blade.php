<div class="modal fade" id="modal-service-category-edit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit service category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="service-category-form-update" method="post" action="">
                    @csrf
                    @method('PUT')
                    @include('owner.partials.localized-name-fields', [
                        'nameEnId' => 'modal-edit-sc-name-en',
                        'nameArId' => 'modal-edit-sc-name-ar',
                        'showErrors' => false,
                    ])
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="modal-edit-sc-sort-order">{{ __('sort_order') }}</label>
                        <input type="number" name="sort_order" id="modal-edit-sc-sort-order" min="0" class="form-control form-control-lg">
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
