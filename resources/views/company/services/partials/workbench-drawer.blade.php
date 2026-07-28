{{-- ═══════ Quick Add / Edit drawer ═══════ --}}
<div class="wb-drawer-panel" tabindex="-1" id="wb-drawer" role="dialog" aria-modal="true" aria-labelledby="wb-drawer-title" aria-hidden="true">
    <div class="wb-drawer-header border-bottom">
        <h5 class="wb-drawer-title fw-bold" id="wb-drawer-title">{{ __('Add service') }}</h5>
        <button type="button" class="btn-close" data-wb-close aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="wb-drawer-body">
        <form id="wb-form" novalidate>
            <div class="wb-drawer-scroll">

                <div id="wb-form-errors" class="alert alert-danger rounded-3 d-none mb-3" style="font-size:13px;"></div>

                {{-- Type chooser --}}
                <div class="wb-sec">
                    <label class="wb-label">{{ __('Service type') }}</label>
                    <div class="wb-typechooser" id="wb-typechooser">
                        <div class="wb-typeopt" data-type="standard"><div class="ic"><i data-feather="scissors" style="width:17px;height:17px;"></i></div><span class="lb">{{ __('Standard') }}</span></div>
                        <div class="wb-typeopt" data-type="package"><div class="ic"><i data-feather="gift" style="width:17px;height:17px;"></i></div><span class="lb">{{ __('Package') }}</span></div>
                        <div class="wb-typeopt" data-type="membership"><div class="ic"><i data-feather="award" style="width:17px;height:17px;"></i></div><span class="lb">{{ __('Membership') }}</span></div>
                        <div class="wb-typeopt" data-type="addon"><div class="ic"><i data-feather="plus-circle" style="width:17px;height:17px;"></i></div><span class="lb">{{ __('Add-on') }}</span></div>
                        <div class="wb-typeopt" data-type="consultation"><div class="ic"><i data-feather="message-circle" style="width:17px;height:17px;"></i></div><span class="lb">{{ __('Consultation') }}</span></div>
                    </div>
                    <input type="hidden" name="service_type" id="wb-f-type" value="standard">
                </div>

                {{-- Basics --}}
                <div class="wb-sec">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">{{ __('Category') }}</label>
                            <select name="service_category_id" id="wb-f-category" class="form-select rounded-3">
                                <option value="">{{ __('No category') }}</option>
                                @foreach($serviceCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_en" id="wb-f-name-en" class="form-control rounded-3" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">{{ __('Name (AR)') }}</label>
                            <input type="text" name="name_ar" id="wb-f-name-ar" class="form-control rounded-3" dir="rtl" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">{{ __('Description') }}</label>
                            <textarea name="description" id="wb-f-desc" class="form-control rounded-3" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Package builder --}}
                <div class="wb-sec wb-hide" id="wb-pkg-sec">
                    <label class="wb-label">{{ __('Included services') }}</label>
                    <div class="position-relative mb-2">
                        <input type="text" id="wb-pkg-search" class="form-control rounded-3" placeholder="{{ __('Search a service to add…') }}" autocomplete="off">
                        <div id="wb-pkg-results" class="dropdown-menu shadow w-100" style="max-height:220px;overflow:auto;"></div>
                    </div>
                    <div id="wb-pkg-items" class="d-flex flex-column gap-2"></div>
                    <div class="wb-pkgsum" id="wb-pkg-sum">
                        <span>{{ __('Items') }}: <b id="wb-pkg-count">0</b></span>
                        <span>{{ __('Sum price') }}: <b id="wb-pkg-price">0</b></span>
                        <span>{{ __('Sum duration') }}: <b id="wb-pkg-dur">0</b></span>
                        <a href="#" id="wb-pkg-apply" class="ms-auto">{{ __('Use these totals') }}</a>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="wb-sec">
                    <label class="wb-label">{{ __('Pricing') }}</label>
                    <div class="wb-seg mb-3" id="wb-pricetype">
                        <button type="button" data-pt="fixed" class="active">{{ __('Fixed') }}</button>
                        <button type="button" data-pt="from">{{ __('Starting from') }}</button>
                        <button type="button" data-pt="range">{{ __('Range') }}</button>
                    </div>
                    <input type="hidden" name="price_type" id="wb-f-pricetype" value="fixed">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label fw-semibold small" id="wb-price-label">{{ __('Price') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1 px-3" data-bs-toggle="dropdown" style="min-width:84px;">
                                    <span id="wb-cur-sym" class="fw-bold">{{ config('booksy.currencies')[config('booksy.default_currency')]['symbol'] }}</span>
                                    <span id="wb-cur-code" class="text-muted small">{{ config('booksy.default_currency') }}</span>
                                </button>
                                <ul class="dropdown-menu shadow" style="max-height:260px;overflow-y:auto;min-width:220px;">
                                    @foreach(config('booksy.currencies') as $code => $info)
                                    <li><a class="dropdown-item wb-cur-opt d-flex justify-content-between align-items-center py-2 {{ $code === config('booksy.default_currency') ? 'active' : '' }}" href="#" data-code="{{ $code }}" data-symbol="{{ $info['symbol'] }}">
                                        <span><span class="fw-semibold me-1">{{ $info['symbol'] }}</span>{{ app()->getLocale()==='ar' ? $info['name_ar'] : $info['name_en'] }}</span>
                                        <small class="text-muted">{{ $code }}</small>
                                    </a></li>
                                    @endforeach
                                </ul>
                                <input type="hidden" name="currency" id="wb-f-currency" value="{{ config('booksy.default_currency') }}">
                                <input type="number" name="price" id="wb-f-price" class="form-control" min="0" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col wb-hide" id="wb-priceto-col">
                            <label class="form-label fw-semibold small">{{ __('Up to') }}</label>
                            <input type="number" name="price_to" id="wb-f-priceto" class="form-control" min="0" step="0.01" placeholder="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">{{ __('Duration') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration_minutes" id="wb-f-duration" class="form-control" min="1" max="1440" value="30">
                                <span class="input-group-text">{{ __('min') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Membership --}}
                <div class="wb-sec wb-hide" id="wb-mem-sec">
                    <label class="wb-label">{{ __('Membership terms') }}</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">{{ __('Valid for (days)') }}</label>
                            <input type="number" name="membership_validity_days" id="wb-f-mem-days" class="form-control rounded-3" min="0" placeholder="{{ __('e.g. 30') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">{{ __('Included sessions') }}</label>
                            <input type="number" name="membership_sessions" id="wb-f-mem-sessions" class="form-control rounded-3" min="0" placeholder="{{ __('e.g. 10') }}">
                        </div>
                    </div>
                </div>

                {{-- Consultation settings --}}
                <div class="wb-sec wb-hide" id="wb-consult-sec">
                    <label class="wb-label">{{ __('Consultation settings') }}</label>
                    <div class="wb-seg mb-3" id="wb-freetype">
                        <button type="button" data-free="0" class="active">{{ __('Paid') }}</button>
                        <button type="button" data-free="1">{{ __('Free') }}</button>
                    </div>
                    <input type="hidden" name="is_free" id="wb-f-is-free" value="0">
                    <label class="d-flex align-items-center justify-content-between">
                        <span class="small"><i data-feather="check-circle" style="width:14px;height:14px;" class="me-1"></i>{{ __('Requires approval before booking') }}</span>
                        <span class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" name="requires_approval" id="wb-f-requires-approval" value="1"></span>
                    </label>
                    <p class="form-text mt-2 mb-0">{{ __('A free consultation ignores the price. “Requires approval” makes it a request the salon confirms before it becomes a booking.') }}</p>
                </div>

                {{-- Add-on: attach to parent services --}}
                <div class="wb-sec wb-hide" id="wb-addon-sec">
                    <label class="wb-label">{{ __('Attach to services') }}</label>
                    <p class="form-text mt-0 mb-2">{{ __('This add-on is offered alongside the selected services during booking — it is not shown on its own.') }}</p>
                    <div class="position-relative mb-2">
                        <input type="text" id="wb-addon-search" class="form-control rounded-3" placeholder="{{ __('Search a service…') }}" autocomplete="off">
                        <div id="wb-addon-results" class="dropdown-menu shadow w-100" style="max-height:220px;overflow:auto;"></div>
                    </div>
                    <div id="wb-addon-parents" class="wb-chips"></div>
                </div>

                {{-- Discount --}}
                <div class="wb-sec">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="wb-label mb-0">{{ __('Discount / Promotion') }}</label>
                        <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="wb-disc-toggle"></div>
                    </div>
                    <div id="wb-disc-fields" class="mt-3" style="display:none;">
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-5">
                                <select id="wb-f-disc-type" name="discount_type" class="form-select form-select-sm rounded-3">
                                    <option value="percent">{{ __('Percentage %') }}</option>
                                    <option value="fixed">{{ __('Fixed amount') }}</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" name="discount_value" id="wb-f-disc-value" class="form-control form-control-sm" min="0" step="0.01" placeholder="0">
                            </div>
                            <div class="col-4"><div id="wb-disc-preview" class="small text-muted"></div></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">{{ __('Starts at') }}</label>
                                <input type="datetime-local" name="discount_starts_at" id="wb-f-disc-start" class="form-control form-control-sm rounded-3">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">{{ __('Ends at') }}</label>
                                <input type="datetime-local" name="discount_ends_at" id="wb-f-disc-end" class="form-control form-control-sm rounded-3">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Visibility & badges --}}
                <div class="wb-sec">
                    <label class="wb-label">{{ __('Visibility') }}</label>
                    <div class="d-flex flex-column gap-2">
                        <label class="d-flex align-items-center justify-content-between">
                            <span class="small"><i data-feather="power" style="width:14px;height:14px;" class="me-1"></i>{{ __('Active') }}</span>
                            <span class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" name="is_active" id="wb-f-active" value="1" checked></span>
                        </label>
                        <label class="d-flex align-items-center justify-content-between">
                            <span class="small"><i data-feather="globe" style="width:14px;height:14px;" class="me-1"></i>{{ __('Visible in online booking') }}</span>
                            <span class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" name="is_bookable_online" id="wb-f-online" value="1" checked></span>
                        </label>
                    </div>
                </div>

                {{-- Badges (optional merchandising) --}}
                <div class="wb-sec">
                    <label class="wb-label">{{ __('Badges') }}</label>
                    <p class="form-text mt-0 mb-2">{{ __('Optional labels shown to customers in online booking. Pick any that apply.') }}</p>
                    <div class="wb-chips" id="wb-badge-chips">
                        <span class="wb-chip" data-badge="most_requested"><i data-feather="trending-up" style="width:13px;height:13px;"></i>{{ __('Most requested') }}</span>
                        <span class="wb-chip" data-badge="new"><i data-feather="zap" style="width:13px;height:13px;"></i>{{ __('New') }}</span>
                        <span class="wb-chip" data-badge="special_offer"><i data-feather="tag" style="width:13px;height:13px;"></i>{{ __('Special offer') }}</span>
                        <span class="wb-chip" data-badge="premium"><i data-feather="award" style="width:13px;height:13px;"></i>{{ __('Premium') }}</span>
                    </div>
                </div>

                {{-- Assignment --}}
                <div class="wb-sec" id="wb-assign-sec">
                    <label class="wb-label">{{ __('Assign staff') }}</label>
                    <div class="wb-chips mb-3" id="wb-emp-chips">
                        @forelse($branchEmployees as $emp)
                            <span class="wb-chip" data-emp="{{ $emp->id }}"><i data-feather="user" style="width:13px;height:13px;"></i>{{ $emp->localizedName() }}</span>
                        @empty
                            <span class="small text-muted">{{ __('No staff in this branch yet.') }}</span>
                        @endforelse
                    </div>
                    <label class="wb-label">{{ __('Assign rooms / equipment') }}</label>
                    <div class="wb-chips" id="wb-res-chips">
                        @forelse($branchResources as $res)
                            <span class="wb-chip" data-res="{{ $res->id }}"><i data-feather="box" style="width:13px;height:13px;"></i>{{ $res->localizedName() }}</span>
                        @empty
                            <span class="small text-muted">{{ __('No rooms/equipment in this branch yet.') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="wb-drawer-foot">
                <button type="button" class="btn btn-light rounded-pill px-4" data-wb-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" id="wb-save-btn">
                    <i data-feather="save" style="width:16px;height:16px;" class="me-1"></i><span id="wb-save-label">{{ __('Save service') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
