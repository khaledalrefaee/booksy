{{-- Create --}}
<div class="modal fade" id="modal-category-create" tabindex="-1" aria-labelledby="modal-category-create-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-category-create-label">{{ __('Add category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <form id="category-form-create-modal" action="{{ route('owner.categories.store') }}" method="post" enctype="multipart/form-data" novalidate>
                    @csrf

                    <input type="hidden" name="_modal" value="create">
            
                    <div class="row g-3">
            
                        {{-- English Name --}}
                        <div class="col-md-6">
                            <label for="modal-create-name-en" class="form-label fw-semibold star_mark_danger">
                                <span class="text-danger">*</span>
                                {{ __('Name (English)') }}
                            </label>
            
                            <input
                                required
                                type="text"
                                name="name_en"
                                id="modal-create-name-en"
                                maxlength="255"
                                autocomplete="off"
                                placeholder="{{ __('Enter name (English)') }}"
                                value="{{ old('_modal') === 'create' ? old('name_en') : '' }}"
                                class="form-control form-control-lg @if (old('_modal') === 'create' && ($errors->has('name_en') || $errors->has('name_ar'))) is-invalid @endif">
            
                        </div>
            
                        {{-- Arabic Name --}}
                        <div class="col-md-6">
                            <label for="modal-create-name-ar" class="form-label fw-semibold">
                                <span class="text-danger">*</span>
                                {{ __('Name (Arabic)') }}
                            </label>
            
                            <input
                                required
                                type="text"
                                name="name_ar"
                                id="modal-create-name-ar"
                                maxlength="255"
                                dir="rtl"
                                autocomplete="off"
                                placeholder="{{ __('Enter Name (Arabic)') }}"
                                value="{{ old('_modal') === 'create' ? old('name_ar') : '' }}"
                                class="form-control form-control-lg @if (old('_modal') === 'create' && ($errors->has('name_en') || $errors->has('name_ar'))) is-invalid @endif">
            
                          
            
                            @if (old('_modal') === 'create')
                                @error('name_en')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
            
                                @error('name_ar')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            @endif
                        </div>
            
                          {{-- sort_order --}}
                          <div class="col-md-12">
                            <label for="modal-create-name-ar" class="form-label fw-semibold">
                                <span class="text-danger">*</span>
                                {{ __('sort_order') }}
                            </label>
            
                            <input
                                
                                type="number"
                                name="sort_order"
                                id="modal-create-sort_order"
                                maxlength="255"
                                dir="rtl"
                                autocomplete="off"
                                placeholder=""
                                value="{{ old('_modal') === 'create' ? old('sort_order') : '' }}"
                                class="form-control form-control-lg">
            
                          
            
                            @if (old('_modal') === 'create')
                                @error('sort_order')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
            
                                @error('sort_order')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            @endif
                        </div>

                        {{-- Image Upload --}}
                        <div class="col-md-6">
                            <label for="modal-create-image" class="form-label fw-semibold">
                                {{ __('Category Image') }}
                            </label>
            
                            <div class="border rounded-3 p-3 bg-light">
            
                                <input
                                    type="file"
                                    name="image"
                                    id="modal-create-image"
                                    class="form-control js-category-thumb-input"
                                    accept="image/*"
                                    data-thumb-wrapper="#thumb-wrap-create-image">
            
                                <small class="text-muted d-block mt-2">
                                    {{ __('Recommended size: 1200x1200') }}
                                </small>
            
                                @if (old('_modal') === 'create')
                                    @error('image')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                @endif
            
                                <div id="thumb-wrap-create-image"
                                    class="category-thumb-wrap d-none mt-3 text-center">
            
                                    <img
                                        src=""
                                        alt=""
                                        class="rounded-3 border shadow-sm"
                                        width="100"
                                        height="100"
                                        style="object-fit:cover;">
                                </div>
                            </div>
                        </div>
            
                        {{-- Icon Upload --}}
                        <div class="col-md-6">
                            <label for="modal-create-icon" class="form-label fw-semibold">
                                {{ __('Category Icon') }}
                            </label>
            
                            <div class="border rounded-3 p-3 bg-light">
            
                                <input
                                    type="file"
                                    name="icon"
                                    id="modal-create-icon"
                                    class="form-control js-category-thumb-input"
                                    accept="image/*"
                                    data-thumb-wrapper="#thumb-wrap-create-icon">
            
                                <small class="text-muted d-block mt-2">
                                    {{ __('Transparent PNG recommended.') }}
                                </small>
            
                                @if (old('_modal') === 'create')
                                    @error('icon')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                @endif
            
                                <div id="thumb-wrap-create-icon"
                                    class="category-thumb-wrap d-none mt-3 text-center">
            
                                    <img
                                        src=""
                                        alt=""
                                        class="rounded-3 border shadow-sm p-1 bg-white"
                                        width="90"
                                        height="90"
                                        style="object-fit:contain;">
                                </div>
                            </div>
                        </div>
                    </div>
            
                    {{-- Footer --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            
                      
            
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
            
                                {{ __('Cancel') }}
                            </button>
            
                            <button
                                type="submit"
                                class="btn btn-primary px-4">
            
                                <i class="mdi mdi-content-save-outline me-1"></i>
                                {{ __('Save Category') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>