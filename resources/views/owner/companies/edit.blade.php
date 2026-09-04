{{-- Edit company --}}
<div class="modal fade" id="modal-campania-edit" tabindex="-1" aria-labelledby="modal-campania-edit-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content cm-modal">
            <div class="modal-header">
                <div class="cm-modal-titlewrap">
                    <span class="cm-modal-ic" aria-hidden="true"><i data-feather="edit-2"></i></span>
                    <div>
                        <h5 class="modal-title" id="modal-campania-edit-label">{{ __('Edit company') }}</h5>
                        <div class="cm-modal-sub">{{ __('Update this business profile.') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <form id="campania-form-update-modal"
                      action="{{ old('_modal') === 'edit' && old('modal_company_id') ? route('owner.companies.update', old('modal_company_id')) : '' }}"
                      method="post"
                      enctype="multipart/form-data"
                      novalidate>
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_modal" value="edit">
                    <input type="hidden" id="modal-company-id-storage" name="modal_company_id" value="{{ old('modal_company_id', '') }}">

                    <div class="row g-3">
                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'modal-edit-company-name-en',
                            'nameArId' => 'modal-edit-company-name-ar',
                            'nameEnValue' => old('_modal') === 'edit' ? old('name_en') : '',
                            'nameArValue' => old('_modal') === 'edit' ? old('name_ar') : '',
                            'showErrors' => old('_modal') === 'edit',
                        ])

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-edit-company-email">
                                <span class="text-danger">*</span> {{ __('Email') }}
                            </label>
                            <input type="email" name="email" id="modal-edit-company-email" maxlength="255" required dir="ltr"
                                value="{{ old('_modal') === 'edit' ? old('email') : '' }}"
                                placeholder="example@email.com"
                                class="form-control form-control-lg @if (old('_modal') === 'edit' && $errors->has('email')) is-invalid @endif">
                            @if (old('_modal') === 'edit')
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-edit-company-phone">{{ __('Phone') }}</label>
                            <input type="tel" id="modal-edit-company-phone" autocomplete="tel" dir="ltr"
                                class="form-control form-control-lg js-cm-phone"
                                data-hidden="modal-edit-company-phone-full" data-error="modal-edit-phone-error">
                            <input type="hidden" name="phone" id="modal-edit-company-phone-full"
                                value="{{ old('_modal') === 'edit' ? old('phone') : '' }}">
                            <div class="cm-phone-error" id="modal-edit-phone-error">{{ __('Enter a valid phone number.') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-edit-company-category">
                                <span class="text-danger">*</span> {{ __('Category') }}
                            </label>
                            <select name="category_id" id="modal-edit-company-category" required
                                class="form-select form-select-lg @if (old('_modal') === 'edit' && $errors->has('category_id')) is-invalid @endif">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @selected(old('_modal') === 'edit' && (string) old('category_id') === (string) $category->id)>
                                        {{ $category->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @if (old('_modal') === 'edit')
                                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="modal-edit-company-password">
                                {{ __('Password') }}
                                <small class="text-muted fw-normal">({{ __('leave blank to keep') }})</small>
                            </label>
                            <input type="password" name="password" id="modal-edit-company-password" minlength="8" dir="ltr"
                                placeholder="••••••••"
                                class="form-control form-control-lg @if (old('_modal') === 'edit' && $errors->has('password')) is-invalid @endif">
                            @if (old('_modal') === 'edit')
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="modal-edit-company-logo">{{ __('Logo') }}</label>
                            <input type="file" name="logo" id="modal-edit-company-logo"
                                class="form-control js-campania-thumb-input" accept="image/*"
                                data-thumb-wrapper="#thumb-wrap-edit-logo">
                            @if (old('_modal') === 'edit')
                                @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                            <div id="thumb-wrap-edit-logo" class="campania-thumb-wrap d-none mt-3">
                                <img src="" alt="" class="cm-logo-preview" width="72" height="72">
                            </div>
                        </div>
                    </div>

                    <div class="cm-modal-foot">
                        <button type="button" class="cm-btn cm-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="cm-btn cm-btn-primary">
                            <i data-feather="check"></i> {{ __('Update') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
