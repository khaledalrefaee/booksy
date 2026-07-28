@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Companies') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Companies') }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-campania-create">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add company') }}
        </button>
    </div>

    @include('owner.partials.flash')

    @include('owner.partials._search-sort-bar', [
        'dtTableId'       => 'dt-companies',
        'sortField'       => $sortField,
        'sortDir'         => $sortDir,
        'extraFilterKeys' => ['status', 'category_id'],
        'sortOptions'     => [
            ['field' => 'created_at', 'label' => __('تاريخ الإضافة')],
            ['field' => 'name',       'label' => __('الاسم')],
            ['field' => 'status',     'label' => __('الحالة')],
        ],
        'extraFilters' => '
            <select name="status" class="bk-ssb-select" style="min-width:130px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الحالات') . '</option>
                <option value="pending"   ' . ($filterStatus === 'pending'   ? 'selected' : '') . '>' . __('قيد الانتظار') . '</option>
                <option value="active"    ' . ($filterStatus === 'active'    ? 'selected' : '') . '>' . __('نشط')          . '</option>
                <option value="suspended" ' . ($filterStatus === 'suspended' ? 'selected' : '') . '>' . __('موقوف')        . '</option>
            </select>
            <select name="category_id" class="bk-ssb-select" style="min-width:140px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الفئات') . '</option>
                ' . $categories->map(fn($c) => '<option value="' . $c->id . '" ' . ((string)$filterCategoryId === (string)$c->id ? 'selected' : '') . '>' . e($c->localizedName()) . '</option>')->implode('') . '
            </select>
        ',
    ])

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dt-companies">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Company') }}</th>
                            <th>{{ __('Name (Arabic)') }}</th>
                            <th>{{ __('Contact') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            @php
                                $logoPreview = $company->logo ? asset('storage/'.$company->logo) : '';
                                $categoryLabel = $company->category?->localizedName() ?? '—';
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        @if ($company->logo)
                                            <img loading="lazy" src="{{ asset('storage/'.$company->logo) }}" alt="" class="wd-40 ht-40 rounded-3 flex-shrink-0" style="object-fit: cover;">
                                        @else
                                            <div class="wd-40 ht-40 rounded-3 bg-light d-flex align-items-center justify-content-center flex-shrink-0">
                                                <i data-feather="briefcase" style="width:18px;height:18px;" class="text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="fw-semibold mb-0">{{ $company->name_en ?: '—' }}</p>
                                            <p class="tx-12 text-muted mb-0">{{ $company->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td lang="ar" dir="rtl" class="text-muted">{{ $company->name_ar ?: '—' }}</td>
                                <td class="text-muted">{{ $company->phone ?: '—' }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-muted border tx-12">{{ $categoryLabel }}</span>
                                </td>
                                <td>
                                    <form method="post"
                                          action="{{ route('owner.companies.update-status', $company) }}"
                                          class="company-status-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason" value="">
                                        <select name="status"
                                                class="form-select form-select-sm border-0 shadow-none fw-semibold
                                                @if($company->status === 'active') text-success bg-success-subtle
                                                @elseif($company->status === 'pending') text-warning bg-warning-subtle
                                                @elseif($company->status === 'suspended') text-danger bg-danger-subtle
                                                @endif"
                                                style="min-width: 130px; border-radius: 12px;"
                                                data-company-name="{{ $company->localizedName() }}"
                                                data-original-status="{{ $company->status }}"
                                                onchange="bkStatusChanged(this)">
                                            <option value="pending" @selected($company->status === 'pending')>🟡 {{ __('Pending') }}</option>
                                            <option value="active" @selected($company->status === 'active')>🟢 {{ __('Active') }}</option>
                                            <option value="suspended" @selected($company->status === 'suspended')>🔴 {{ __('Suspended') }}</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="{{ route('owner.companies.show', $company) }}" class="btn btn-sm btn-outline-secondary rounded-pill me-1">
                                        <i data-feather="eye" style="width:13px;height:13px;"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-campania-edit"
                                        data-company-id="{{ $company->id }}"
                                        data-company-name-en="{{ $company->name_en ?? '' }}"
                                        data-company-name-ar="{{ $company->name_ar ?? '' }}"
                                        data-company-email="{{ $company->email }}"
                                        data-company-phone="{{ $company->phone ?? '' }}"
                                        data-company-category-id="{{ $company->category_id }}"
                                        data-update-url="{{ route('owner.companies.update', $company) }}"
                                        data-logo-src="{{ $logoPreview }}">
                                        <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-campania-delete"
                                        data-delete-url="{{ route('owner.companies.destroy', $company) }}"
                                        data-company-display="{{ $company->localizedName() }}">
                                        <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="briefcase" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No companies yet.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($companies->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $companies->links() }}</div>
        @endif
    </div>

    @include('owner.companies.create', ['categories' => $categories])
    @include('owner.companies.edit', ['categories' => $categories])
    @include('owner.companies.delete')

    {{-- ── Status-change reason modal ── --}}
    <div class="modal fade" id="modal-status-reason" tabindex="-1"
         aria-labelledby="modal-status-reason-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modal-status-reason-title">
                        {{ __('Change company status') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="bk-status-summary"></p>

                    <label for="bk-status-reason" class="form-label fw-semibold">
                        {{ __('Reason') }}
                        <span class="text-danger d-none" id="bk-status-reason-required" aria-hidden="true">*</span>
                    </label>
                    <textarea class="form-control" id="bk-status-reason" rows="3" maxlength="500"></textarea>
                    <div class="form-text" id="bk-status-reason-hint"></div>
                    <div class="invalid-feedback" id="bk-status-reason-error">
                        {{ __('A reason is required when suspending a company.') }}
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn rounded-pill" id="bk-status-confirm"></button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('owner.partials._datatable', [
    'tableId'    => 'dt-companies',
    'exportName' => 'Companies',
    'noSortCols' => [4, -1],
])

@push('scripts')
<script>
(function () {
    'use strict';

    const STATUS_LABELS = {
        pending:   @json(__('Pending')),
        active:    @json(__('Active')),
        suspended: @json(__('Suspended')),
    };
    const SUMMARY_TEMPLATE = @json(__('Change status of :company from ":from" to ":to".'));
    const HINT_REQUIRED    = @json(__('The reason is stored in the audit log and shown to the company.'));
    const HINT_OPTIONAL    = @json(__('Optional — stored in the audit log.'));
    const CONFIRM_SUSPEND  = @json(__('Suspend company'));
    const CONFIRM_DEFAULT  = @json(__('Confirm'));

    const modalEl    = document.getElementById('modal-status-reason');
    const summaryEl  = document.getElementById('bk-status-summary');
    const reasonEl   = document.getElementById('bk-status-reason');
    const requiredEl = document.getElementById('bk-status-reason-required');
    const hintEl     = document.getElementById('bk-status-reason-hint');
    const confirmBtn = document.getElementById('bk-status-confirm');
    const modal      = new bootstrap.Modal(modalEl);

    let activeSelect = null;
    let confirmed    = false;

    window.bkStatusChanged = function (select) {
        const from = select.dataset.originalStatus;
        const to   = select.value;
        if (from === to) return;

        activeSelect = select;
        confirmed    = false;

        const suspending = to === 'suspended';

        summaryEl.textContent = SUMMARY_TEMPLATE
            .replace(':company', select.dataset.companyName)
            .replace(':from', STATUS_LABELS[from] ?? from)
            .replace(':to', STATUS_LABELS[to] ?? to);

        requiredEl.classList.toggle('d-none', !suspending);
        hintEl.textContent = suspending ? HINT_REQUIRED : HINT_OPTIONAL;

        confirmBtn.textContent = suspending ? CONFIRM_SUSPEND : CONFIRM_DEFAULT;
        confirmBtn.classList.toggle('btn-danger', suspending);
        confirmBtn.classList.toggle('btn-primary', !suspending);

        reasonEl.value = '';
        reasonEl.classList.remove('is-invalid');
        modal.show();
    };

    modalEl.addEventListener('shown.bs.modal', () => reasonEl.focus());

    // Dismiss without confirming → revert the select to its original value
    modalEl.addEventListener('hidden.bs.modal', () => {
        if (!confirmed && activeSelect) {
            activeSelect.value = activeSelect.dataset.originalStatus;
        }
        activeSelect = null;
    });

    reasonEl.addEventListener('input', () => reasonEl.classList.remove('is-invalid'));

    confirmBtn.addEventListener('click', () => {
        if (!activeSelect) return;

        const suspending = activeSelect.value === 'suspended';
        const reason     = reasonEl.value.trim();

        if (suspending && reason === '') {
            reasonEl.classList.add('is-invalid');
            reasonEl.focus();
            return;
        }

        confirmed = true;
        confirmBtn.disabled = true;
        activeSelect.form.querySelector('input[name="reason"]').value = reason;
        activeSelect.form.submit();
    });
})();
</script>

    @include('owner.partials.campanias-form-validation-script', [
        'formSelectors' => ['#campania-form-create-modal', '#campania-form-update-modal'],
    ])
    @include('owner.partials.campanias-modals-behavior-script')
@endpush

@endsection
