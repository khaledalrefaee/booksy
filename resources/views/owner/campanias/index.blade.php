@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Campanias') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Campanias') }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#modal-campania-create">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add company') }}
        </button>
    </div>

    @include('owner.partials.flash')

    <div class="row">
        <div class="col-md-12 stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="pt-0">#</th>
                                    <th class="pt-0">{{ __('Logo') }}</th>
                                    <th class="pt-0">{{ __('Name (English)') }}</th>
                                    <th class="pt-0">{{ __('Name (Arabic)') }}</th>
                                    <th class="pt-0">{{ __('Email') }}</th>
                                    <th class="pt-0">{{ __('Phone') }}</th>
                                    <th class="pt-0">{{ __('Category') }}</th>
                                    <th class="pt-0">{{ __('Status') }}</th>
                                    <th class="pt-0 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companies as $company)
                                    @php
                                        $logoPreview = $company->logo ? asset('storage/'.$company->logo) : '';
                                        $categoryLabel = $company->category?->localizedName() ?? '—';
                                    @endphp
                                    <tr>
                                        <td>{{ $company->id }}</td>
                                        <td>
                                            @if ($company->logo)
                                                <img loading="lazy" src="{{ asset('storage/'.$company->logo) }}" alt="" class="wd-40 ht-40 rounded" style="object-fit: cover;">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $company->name_en ?: '—' }}</td>
                                        <td lang="ar" dir="rtl">{{ $company->name_ar ?: '—' }}</td>
                                        <td>{{ $company->email }}</td>
                                        <td>{{ $company->phone ?: '—' }}</td>
                                        <td>{{ $categoryLabel }}</td>
                                        <td>
                                            <form method="post"
                                                  action="{{ route('owner.campanias.update-status', $company) }}"
                                                  class="campania-status-form">
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
                                        
                                                    <option value="pending" @selected($company->status === 'pending')>
                                                        🟡 {{ __('Pending') }}
                                                    </option>
                                        
                                                    <option value="active" @selected($company->status === 'active')>
                                                        🟢 {{ __('Active') }}
                                                    </option>
                                        
                                                    <option value="suspended" @selected($company->status === 'suspended')>
                                                        🔴 {{ __('Suspended') }}
                                                    </option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('owner.campanias.show', $company) }}" class="btn btn-sm btn-outline-primary mb-2 mb-lg-0 me-lg-1">
                                                {{ __('Show') }}
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-warning mb-2 mb-lg-0 me-lg-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-campania-edit"
                                                data-company-id="{{ $company->id }}"
                                                data-company-name-en="{{ $company->name_en ?? '' }}"
                                                data-company-name-ar="{{ $company->name_ar ?? '' }}"
                                                data-company-email="{{ $company->email }}"
                                                data-company-phone="{{ $company->phone ?? '' }}"
                                                data-company-category-id="{{ $company->category_id }}"
                                                data-update-url="{{ route('owner.campanias.update', $company) }}"
                                                data-logo-src="{{ $logoPreview }}">
                                                {{ __('Edit') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-campania-delete"
                                                data-delete-url="{{ route('owner.campanias.destroy', $company) }}"
                                                data-company-display="{{ $company->localizedName() }}">
                                                {{ __('Delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">{{ __('No companies yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('owner.campanias.create', ['categories' => $categories])
    @include('owner.campanias.edit', ['categories' => $categories])
    @include('owner.campanias.delete')
</div>

@push('scripts')
    @include('owner.partials.campanias-form-validation-script', [
        'formSelectors' => ['#campania-form-create-modal', '#campania-form-update-modal'],
    ])
    @include('owner.partials.campanias-modals-behavior-script')
@endpush

@endsection
