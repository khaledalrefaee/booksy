@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Company categories') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Company categories') }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#modal-category-create">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add category') }}
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @include('owner.partials._search-sort-bar', [
        'dtTableId'  => 'dt-categories',
        'sortField'  => $sortField,
        'sortDir'    => $sortDir,
        'sortOptions' => [
            ['field' => 'sort_order', 'label' => __('الترتيب')],
            ['field' => 'name',       'label' => __('الاسم')],
            ['field' => 'created_at', 'label' => __('تاريخ الإضافة')],
        ],
    ])

    <div class="row">
        <div class="col-md-12 stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="dt-categories">
                            <thead>
                                <tr>
                                    <th class="pt-0">#</th>
                                    <th class="pt-0">{{ __('Name (English)') }}</th>
                                    <th class="pt-0">{{ __('Name (Arabic)') }}</th>
                                    <th class="pt-0">{{ __('Image') }}</th>
                                    <th class="pt-0">{{ __('Icon') }}</th>
                                    <th class="pt-0">{{ __('sort_order') }}</th>
                                    <th class="pt-0 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    @php
                                        $imagePreview = $category->image ? asset('storage/'.$category->image) : '';
                                        $iconPreview = $category->icon ? asset('storage/'.$category->icon) : '';
                                    @endphp
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td>{{ $category->name_en ?: '—' }}</td>
                                        <td lang="ar" dir="rtl">{{ $category->name_ar ?: '—' }}</td>
                                        <td>
                                            @if ($category->image)
                                                <img  loading="lazy" src="{{ asset('storage/'.$category->image) }}" alt="" class="wd-40 ht-40 rounded" style="object-fit: cover;">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($category->icon)
                                                <img  loading="lazy" src="{{ asset('storage/'.$category->icon) }}" alt="" class="wd-40 ht-40 rounded" style="object-fit: cover;">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $category->sort_order }}</td>

                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary mb-2 mb-lg-0 me-lg-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-category-edit"
                                                data-category-id="{{ $category->id }}"
                                                data-category-name-en="{{ $category->name_en ?? '' }}"
                                                data-category-name-ar="{{ $category->name_ar ?? '' }}"
                                                data-category-sort-order="{{ $category->sort_order ?? '' }}"
                                                data-update-url="{{ route('owner.categories.update', $category) }}"
                                                data-image-src="{{ $imagePreview }}"
                                                data-icon-src="{{ $iconPreview }}">
                                                {{ __('Edit') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-category-delete"
                                                data-delete-url="{{ route('owner.categories.destroy', $category) }}"
                                                data-category-display="{{ $category->localizedName() }}">
                                                {{ __('Delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No categories yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($categories->hasPages())
                        <div class="py-3 px-2">{{ $categories->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('owner.category.create')
    @include('owner.category.edit')
    @include('owner.category.delete')
</div>

@include('owner.partials._datatable', [
    'tableId'    => 'dt-categories',
    'exportName' => 'Categories',
    'noSortCols' => [-1],
])

@push('scripts')
    @include('owner.partials.category-form-validation-script', [
        'formSelectors' => ['#category-form-create-modal', '#category-form-update-modal'],
    ])
    @include('owner.partials.category-modals-behavior-script')
@endpush

@endsection
