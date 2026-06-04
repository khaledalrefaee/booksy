{{-- Edit --}}
<div class="modal fade" id="modal-category-edit" tabindex="-1" aria-labelledby="modal-category-edit-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-category-edit-label">{{ __('Edit category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <form id="category-form-update-modal"
                      action="{{ old('_modal') === 'edit' && old('modal_category_id') ? route('owner.categories.update', old('modal_category_id')) : '' }}"
                      method="post"
                      enctype="multipart/form-data"
                      novalidate>

                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_modal" value="edit">
                    <input type="hidden" id="modal-category-id-storage" name="modal_category_id" value="{{ old('modal_category_id', '') }}">

                    <div class="row g-3">

                        {{-- English Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <span class="text-danger">*</span>
                                {{ __('Name (English)') }}
                            </label>

                            <input
                                id="modal-edit-name-en"
                                type="text"
                                name="name_en"
                                maxlength="255"
                                autocomplete="off"
                                placeholder="{{ __('Enter name (English)') }}"
                                value="{{ old('_modal') === 'edit' ? old('name_en') : '' }}"
                                class="form-control form-control-lg
                                    @if (old('_modal') === 'edit' && ($errors->has('name_en') || $errors->has('name_ar'))) is-invalid @endif">
                        </div>

                        {{-- Arabic Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <span class="text-danger">*</span>
                                {{ __('Name (Arabic)') }}
                            </label>

                            <input
                                id="modal-edit-name-ar"
                                type="text"
                                name="name_ar"
                                dir="rtl"
                                maxlength="255"
                                autocomplete="off"
                                placeholder="{{ __('Enter Name (Arabic)') }}"
                                value="{{ old('_modal') === 'edit' ? old('name_ar') : '' }}"
                                class="form-control form-control-lg
                                    @if (old('_modal') === 'edit' && ($errors->has('name_en') || $errors->has('name_ar'))) is-invalid @endif">

                            @if (old('_modal') === 'edit')
                                @error('name_en')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                @error('name_ar')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        {{-- Sort Order --}}
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">
                                {{ __('Sort Order') }}
                            </label>

                            <input
                                id="modal-edit-sort-order"
                                type="number"
                                min="0"
                                name="sort_order"
                                value="{{ old('_modal') === 'edit' ? old('sort_order') : '' }}"
                                class="form-control form-control-lg
                                    @if (old('_modal') === 'edit' && $errors->has('sort_order')) is-invalid @endif">

                            @if (old('_modal') === 'edit')
                                @error('sort_order')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        {{-- Image --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                {{ __('Category Image') }}
                            </label>

                            <div class="border rounded-3 p-3 bg-light">

                                <input
                                    id="modal-edit-image"
                                    type="file"
                                    name="image"
                                    class="form-control js-category-thumb-input"
                                    accept="image/*"
                                    data-thumb-wrapper="#thumb-wrap-edit-image">

                                <small class="text-muted d-block mt-2">
                                    {{ __('Recommended size: 1200x1200') }}
                                </small>

                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <div id="thumb-wrap-edit-image" class="category-thumb-wrap mt-3 text-center d-none">
                                    <img
                                        src=""
                                        class="rounded-3 border shadow-sm"
                                        width="100"
                                        height="100"
                                        style="object-fit:cover;">
                                </div>
                            </div>
                        </div>

                        {{-- Icon --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                {{ __('Category Icon') }}
                            </label>

                            <div class="border rounded-3 p-3 bg-light">

                                <input
                                    id="modal-edit-icon"
                                    type="file"
                                    name="icon"
                                    class="form-control js-category-thumb-input"
                                    accept="image/*"
                                    data-thumb-wrapper="#thumb-wrap-edit-icon">

                                <small class="text-muted d-block mt-2">
                                    {{ __('Transparent PNG recommended.') }}
                                </small>

                                @error('icon')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <div id="thumb-wrap-edit-icon" class="category-thumb-wrap mt-3 text-center d-none">
                                    <img
                                        src=""
                                        class="rounded-3 border shadow-sm p-1 bg-white"
                                        width="90"
                                        height="90"
                                        style="object-fit:contain;">
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">

                        <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>

                        <button type="submit"
                                class="btn btn-primary px-4">
                            <i class="mdi mdi-content-save-outline me-1"></i>
                            {{ __('Update Category') }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>