@extends('company.dashboard')

@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <h4 class="mb-0">{{ __('Service categories') }}</h4>
    </div>

    @include('company.partials.flash')

    <div class="row">
        <div class="col-md-5 col-xl-4 grid-margin">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Add category') }}</h6>
                    <form method="POST" action="{{ route('company.service-categories.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name_en" class="form-label fw-semibold">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                            <input type="text" id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}">
                            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="name_ar" class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                            <input type="text" id="name_ar" name="name_ar" dir="rtl" class="form-control" value="{{ old('name_ar') }}">
                        </div>
                        <div class="mb-3">
                            <label for="sort_order" class="form-label fw-semibold">{{ __('Sort order') }}</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('Add') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7 col-xl-8 grid-margin">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Your categories') }}</h6>
                    @if ($serviceCategories->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">{{ __('No categories yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Services') }}</th>
                                        <th>{{ __('Sort') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($serviceCategories as $cat)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $cat->name_en }}</div>
                                                @if ($cat->name_ar)
                                                    <div class="text-muted tx-12" dir="rtl">{{ $cat->name_ar }}</div>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-secondary">{{ $cat->services_count }}</span></td>
                                            <td>{{ $cat->sort_order }}</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                                                    data-bs-toggle="modal" data-bs-target="#editModal{{ $cat->id }}">
                                                    <i data-feather="edit-2" style="width:13px;"></i>
                                                </button>
                                                <form method="POST" action="{{ route('company.service-categories.destroy', $cat) }}" class="d-inline"
                                                    onsubmit="return confirm('{{ __('Delete this category?') }}')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" {{ $cat->services_count > 0 ? 'disabled' : '' }}>
                                                        <i data-feather="trash-2" style="width:13px;"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit modals --}}
@foreach ($serviceCategories as $cat)
<div class="modal fade" id="editModal{{ $cat->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.service-categories.update', $cat) }}">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Name (EN)') }}</label>
                        <input type="text" name="name_en" class="form-control" value="{{ $cat->name_en }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                        <input type="text" name="name_ar" dir="rtl" class="form-control" value="{{ $cat->name_ar }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Sort order') }}</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $cat->sort_order }}" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
