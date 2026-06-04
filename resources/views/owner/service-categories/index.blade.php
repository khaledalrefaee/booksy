@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Service categories') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Service categories') }}</li>
                </ol>
            </nav>
            <p class="text-muted tx-13 mb-0">{{ __('Classify branch services (e.g. hair, nails). Each service must belong to one category.') }}</p>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#modal-service-category-create">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add service category') }}
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
                                    <th class="pt-0">{{ __('Name (English)') }}</th>
                                    <th class="pt-0">{{ __('Name (Arabic)') }}</th>
                                    <th class="pt-0">{{ __('Services') }}</th>
                                    <th class="pt-0">{{ __('sort_order') }}</th>
                                    <th class="pt-0 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($serviceCategories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td>{{ $category->name_en ?: '—' }}</td>
                                        <td lang="ar" dir="rtl">{{ $category->name_ar ?: '—' }}</td>
                                        <td>{{ $category->services_count }}</td>
                                        <td>{{ $category->sort_order }}</td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-service-category-edit"
                                                data-category-id="{{ $category->id }}"
                                                data-name-en="{{ $category->name_en ?? '' }}"
                                                data-name-ar="{{ $category->name_ar ?? '' }}"
                                                data-sort-order="{{ $category->sort_order }}"
                                                data-update-url="{{ route('owner.service-categories.update', $category) }}">
                                                {{ __('Edit') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-service-category-delete"
                                                data-delete-url="{{ route('owner.service-categories.destroy', $category) }}"
                                                data-category-display="{{ $category->localizedName() }}">
                                                {{ __('Delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">{{ __('No service categories yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('owner.service-categories.create')
@include('owner.service-categories.edit')
@include('owner.service-categories.delete')

@push('owner-after-template')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var editModal = document.getElementById('modal-service-category-edit');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            var form = document.getElementById('service-category-form-update');
            if (form) form.action = btn.getAttribute('data-update-url') || '';
            var en = document.getElementById('modal-edit-sc-name-en');
            var ar = document.getElementById('modal-edit-sc-name-ar');
            var sort = document.getElementById('modal-edit-sc-sort-order');
            if (en) en.value = btn.getAttribute('data-name-en') || '';
            if (ar) ar.value = btn.getAttribute('data-name-ar') || '';
            if (sort) sort.value = btn.getAttribute('data-sort-order') || '0';
        });
    }
    var deleteModal = document.getElementById('modal-service-category-delete');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            var form = document.getElementById('form-service-category-delete');
            var span = document.getElementById('delete-sc-name-display');
            if (form) form.action = btn.getAttribute('data-delete-url') || '';
            if (span) span.textContent = btn.getAttribute('data-category-display') || '';
        });
    }
});
</script>
@endpush
@endsection
