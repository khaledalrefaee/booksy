{{-- Create company --}}
<div class="modal fade" id="modal-campania-create" tabindex="-1" aria-labelledby="modal-campania-create-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-campania-create-label">{{ __('Add company') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <form id="campania-form-create-modal" action="{{ route('owner.companies.store') }}" method="post" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="_modal" value="create">

                    <div class="row g-3">
                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'modal-create-company-name-en',
                            'nameArId' => 'modal-create-company-name-ar',
                            'nameEnValue' => old('_modal') === 'create' ? old('name_en') : '',
                            'nameArValue' => old('_modal') === 'create' ? old('name_ar') : '',
                            'showErrors' => old('_modal') === 'create',
                        ])

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-email">
                                <span class="text-danger">*</span> {{ __('Email') }}
                            </label>
                            <input type="email" name="email" id="modal-create-company-email" maxlength="255" required
                                value="{{ old('_modal') === 'create' ? old('email') : '' }}"
                                class="form-control form-control-lg @if (old('_modal') === 'create' && $errors->has('email')) is-invalid @endif">
                            @if (old('_modal') === 'create')
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-phone">{{ __('Phone') }}</label>
                            <input type="text" name="phone" id="modal-create-company-phone" maxlength="30"
                                value="{{ old('_modal') === 'create' ? old('phone') : '' }}"
                                class="form-control form-control-lg" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-category">
                                <span class="text-danger">*</span> {{ __('Category') }}
                            </label>
                            <select name="category_id" id="modal-create-company-category" required
                                class="form-select form-select-lg @if (old('_modal') === 'create' && $errors->has('category_id')) is-invalid @endif">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('_modal') === 'create' && (string) old('category_id') === (string) $category->id)>
                                        {{ $category->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @if (old('_modal') === 'create')
                                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-password">
                                <span class="text-danger">*</span> {{ __('Password') }}
                            </label>
                            <input type="password" name="password" id="modal-create-company-password" minlength="8" required
                                class="form-control form-control-lg @if (old('_modal') === 'create' && $errors->has('password')) is-invalid @endif">
                            @if (old('_modal') === 'create')
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-create-company-status">
                                <span class="text-danger">*</span> {{ __('Status') }}
                            </label>
                            <select name="status" id="modal-create-company-status" required class="form-select form-select-lg">
                                @foreach (['pending', 'active', 'suspended'] as $status)
                                    <option value="{{ $status }}" @selected(old('_modal') === 'create' ? old('status', 'pending') === $status : $status === 'pending')>
                                        {{ __($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="modal-create-company-logo">{{ __('Logo') }}</label>
                            <input type="file" name="logo" id="modal-create-company-logo"
                                class="form-control js-campania-thumb-input" accept="image/*"
                                data-thumb-wrapper="#thumb-wrap-create-logo">
                            @if (old('_modal') === 'create')
                                @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                            <div id="thumb-wrap-create-logo" class="campania-thumb-wrap d-none mt-3 text-center">
                                <img src="" alt="" class="rounded-3 border shadow-sm" width="100" height="100" style="object-fit:cover;">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="mdi mdi-content-save-outline me-1"></i>
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
