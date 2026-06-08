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

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                        <select name="status"
                                                class="form-select form-select-sm border-0 shadow-none fw-semibold
                                                @if($company->status === 'active') text-success bg-success-subtle
                                                @elseif($company->status === 'pending') text-warning bg-warning-subtle
                                                @elseif($company->status === 'suspended') text-danger bg-danger-subtle
                                                @endif"
                                                style="min-width: 130px; border-radius: 12px;"
                                                onchange="this.form.submit()">
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
    </div>

    @include('owner.companies.create', ['categories' => $categories])
    @include('owner.companies.edit', ['categories' => $categories])
    @include('owner.companies.delete')
</div>

@push('scripts')
    @include('owner.partials.campanias-form-validation-script', [
        'formSelectors' => ['#campania-form-create-modal', '#campania-form-update-modal'],
    ])
    @include('owner.partials.campanias-modals-behavior-script')
@endpush

@endsection
