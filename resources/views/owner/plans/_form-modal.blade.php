{{-- Plan create/edit modal. Expects: $modalId, $title, $action, $method, $plan (nullable), $featureCatalog --}}
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Name (English)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_en" class="form-control" required
                                   value="{{ $plan->name_en ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Name (Arabic)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_ar" class="form-control" required
                                   value="{{ $plan->name_ar ?? '' }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('Price') }} <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" required
                                   value="{{ $plan->price ?? '0' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Currency') }}</label>
                            <select name="currency" class="form-select">
                                @foreach (['USD', 'SYP', 'EUR'] as $cur)
                                    <option value="{{ $cur }}" {{ ($plan->currency ?? 'USD') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Duration (days)') }} <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" class="form-control" min="1" max="3650" required
                                   value="{{ $plan->duration_days ?? 30 }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('Grace period (days)') }}</label>
                            <input type="number" name="grace_days" class="form-control" min="0" max="365"
                                   value="{{ $plan->grace_days ?? 0 }}">
                            <div class="form-text">{{ __('Paid features stay on this many days after expiry.') }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('Branches limit') }}</label>
                            <input type="number" name="max_branches" class="form-control" min="1" max="999"
                                   placeholder="{{ __('Unlimited') }}"
                                   value="{{ $plan->max_branches ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Employees limit') }}</label>
                            <input type="number" name="max_employees" class="form-control" min="1" max="9999"
                                   placeholder="{{ __('Unlimited') }}"
                                   value="{{ $plan->max_employees ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('sort_order') }}</label>
                            <input type="number" name="sort_order" class="form-control" min="0" max="9999"
                                   value="{{ $plan->sort_order ?? 0 }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">{{ __('Included features') }}</label>
                            <p class="text-muted small mb-2">{{ __('Appointments, customers, branches, services and employees are always included.') }}</p>
                            <div class="row g-2">
                                @foreach ($featureCatalog as $key => $f)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="features[]" value="{{ $key }}"
                                                   id="{{ $modalId }}-feature-{{ $key }}"
                                                   {{ ($plan && $plan->hasFeature($key)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $modalId }}-feature-{{ $key }}">
                                                {{ $f['label'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="{{ $modalId }}-is-active"
                                       {{ ($plan->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $modalId }}-is-active">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
