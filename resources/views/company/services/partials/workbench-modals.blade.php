{{-- ═══════ Copy to branches ═══════ --}}
<div class="modal fade" id="wb-copy-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">{{ __('Copy to branches') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3"><span id="wb-copy-count">0</span> {{ __('services selected.') }}</p>

                <label class="wb-label">{{ __('Destination branches') }}</label>
                <div class="wb-chips mb-3" id="wb-copy-branches">
                    @forelse($siblingBranches as $b)
                        <span class="wb-chip" data-branch="{{ $b->id }}"><i data-feather="map-pin" style="width:13px;height:13px;"></i>{{ $b->localizedName() }}</span>
                    @empty
                        <span class="small text-muted">{{ __('No other branches to copy to.') }}</span>
                    @endforelse
                </div>

                <label class="wb-label">{{ __('If a service already exists') }}</label>
                <div class="wb-seg mb-3" id="wb-copy-strategy">
                    <button type="button" data-strategy="skip" class="active">{{ __('Skip') }}</button>
                    <button type="button" data-strategy="replace">{{ __('Replace') }}</button>
                    <button type="button" data-strategy="duplicate">{{ __('Duplicate') }}</button>
                </div>

                <div id="wb-copy-preview" class="rounded-3 p-3 mt-1" style="background:var(--wb-hover);font-size:12.5px;display:none;"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-outline-primary rounded-pill" id="wb-copy-preview-btn"><i data-feather="eye" style="width:15px;height:15px;" class="me-1"></i>{{ __('Preview') }}</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="wb-copy-confirm">{{ __('Copy') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════ Import ═══════ --}}
<div class="modal fade" id="wb-import-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">{{ __('Import services') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">{{ __('Upload a CSV or Excel file. Columns: type, category, name_en, name_ar, description, price_type, price, price_to, currency, duration_minutes.') }}</p>
                <input type="file" id="wb-import-file" accept=".csv,.xlsx,.xls,text/csv" class="form-control rounded-3 mb-3">
                <label class="wb-label">{{ __('If a service already exists') }}</label>
                <div class="wb-seg" id="wb-import-strategy">
                    <button type="button" data-strategy="skip" class="active">{{ __('Skip') }}</button>
                    <button type="button" data-strategy="replace">{{ __('Replace') }}</button>
                    <button type="button" data-strategy="duplicate">{{ __('Duplicate') }}</button>
                </div>
                <div id="wb-import-result" class="alert alert-success rounded-3 mt-3 d-none" style="font-size:13px;"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="wb-import-confirm">{{ __('Import') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════ Category editor ═══════ --}}
<div class="modal fade" id="wb-cat-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold" id="wb-cat-modal-title">{{ __('New category') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="wb-cat-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                    <input type="text" id="wb-cat-name-en" class="form-control rounded-3" maxlength="255">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold small">{{ __('Name (AR)') }}</label>
                    <input type="text" id="wb-cat-name-ar" class="form-control rounded-3" dir="rtl" maxlength="255">
                </div>
                <div id="wb-cat-error" class="text-danger small d-none"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary rounded-pill flex-fill" id="wb-cat-save">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════ Bulk edit (price / duration / move) ═══════ --}}
<div class="modal fade" id="wb-bulkedit-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold" id="wb-bulkedit-title">{{ __('Bulk edit') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3"><span id="wb-bulkedit-count">0</span> {{ __('services selected.') }}</p>

                {{-- Move to category --}}
                <div id="wb-bulkedit-move" class="wb-hide">
                    <label class="form-label fw-semibold small">{{ __('Category') }}</label>
                    <select id="wb-bulkedit-cat" class="form-select rounded-3">
                        <option value="">{{ __('Uncategorized') }}</option>
                        @foreach($serviceCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Change price --}}
                <div id="wb-bulkedit-price" class="wb-hide">
                    <label class="form-label fw-semibold small">{{ __('How to change the price') }}</label>
                    <select id="wb-bulkedit-price-mode" class="form-select rounded-3 mb-2">
                        <option value="increase_percent">{{ __('Increase by %') }}</option>
                        <option value="decrease_percent">{{ __('Decrease by %') }}</option>
                        <option value="increase_amount">{{ __('Increase by amount') }}</option>
                        <option value="decrease_amount">{{ __('Decrease by amount') }}</option>
                        <option value="set">{{ __('Set to') }}</option>
                    </select>
                    <input type="number" id="wb-bulkedit-price-value" class="form-control rounded-3" min="0" step="0.01" placeholder="0">
                </div>

                {{-- Change duration --}}
                <div id="wb-bulkedit-duration" class="wb-hide">
                    <label class="form-label fw-semibold small">{{ __('How to change duration') }}</label>
                    <select id="wb-bulkedit-duration-mode" class="form-select rounded-3 mb-2">
                        <option value="set">{{ __('Set to') }}</option>
                        <option value="increase">{{ __('Increase by (min)') }}</option>
                        <option value="decrease">{{ __('Decrease by (min)') }}</option>
                    </select>
                    <input type="number" id="wb-bulkedit-duration-value" class="form-control rounded-3" min="0" placeholder="0">
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary rounded-pill flex-fill" id="wb-bulkedit-apply">{{ __('Apply') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════ Delete confirm ═══════ --}}
<div class="modal fade" id="wb-delete-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-body p-4 text-center">
                <div class="bk-empty-ic mx-auto mb-3" style="background:rgba(229,57,53,.12);color:#e53935;animation:none;">
                    <i data-feather="trash-2" style="width:24px;height:24px;"></i>
                </div>
                <h6 class="fw-bold mb-1" id="wb-delete-title">{{ __('Delete service?') }}</h6>
                <p class="text-muted small mb-4" id="wb-delete-name"></p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-danger rounded-pill flex-fill" id="wb-delete-confirm">{{ __('Delete') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
