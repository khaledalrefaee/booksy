@extends('company.dashboard')

@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <h4 class="mb-0">{{ __('Resources & rooms') }}</h4>

        <form method="GET" class="d-flex align-items-center gap-2">
            <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">{{ __('All branches') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->localizedName() }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @include('company.partials.flash')

    <div class="row">
        <div class="col-md-5 col-xl-4 grid-margin">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Add resource') }}</h6>
                    <p class="text-muted tx-12 mb-3">{{ __('Rooms and devices that services need. A service linked to resources can only be booked while one of them is free.') }}</p>
                    <form method="POST" action="{{ route('company.resources.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="branch_id" class="form-label fw-semibold">{{ __('Branch') }} <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">{{ __('Choose branch') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', request('branch_id')) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="name_en" class="form-label fw-semibold">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                            <input type="text" id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}">
                            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="name_ar" class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                            <input type="text" id="name_ar" name="name_ar" dir="rtl" class="form-control" value="{{ old('name_ar') }}">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-7">
                                <label for="type" class="form-label fw-semibold">{{ __('Type') }}</label>
                                <select id="type" name="type" class="form-select">
                                    <option value="room" {{ old('type') === 'room' ? 'selected' : '' }}>{{ __('Room') }}</option>
                                    <option value="equipment" {{ old('type') === 'equipment' ? 'selected' : '' }}>{{ __('Equipment') }}</option>
                                    <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-5">
                                <label for="sort_order" class="form-label fw-semibold">{{ __('Sort order') }}</label>
                                <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                            </div>
                        </div>
                        @if ($serviceCategories->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-1">{{ __('Applies to whole categories') }}</label>
                                <p class="form-text mt-0 mb-2">{{ __('Every service in the selected categories (in this branch) will require this resource — no need to link services one by one.') }}</p>
                                @foreach ($serviceCategories as $cat)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="category_ids[]"
                                               id="addCat{{ $cat->id }}" value="{{ $cat->id }}"
                                               {{ in_array($cat->id, old('category_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="addCat{{ $cat->id }}">{{ $cat->localizedName() }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <button type="submit" class="btn btn-primary w-100">{{ __('Add') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7 col-xl-8 grid-margin">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Your resources') }}</h6>
                    @if ($resources->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">{{ __('No resources yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Branch') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Applies to') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($resources as $resource)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $resource->name_en }}</div>
                                                @if ($resource->name_ar)
                                                    <div class="text-muted tx-12" dir="rtl">{{ $resource->name_ar }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $resource->branch?->localizedName() ?? '—' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $resource->type === 'room' ? 'info' : ($resource->type === 'equipment' ? 'warning' : 'secondary') }}-subtle text-{{ $resource->type === 'room' ? 'info' : ($resource->type === 'equipment' ? 'warning' : 'secondary') }}">
                                                    {{ $resource->typeLabel() }}
                                                </span>
                                            </td>
                                            <td>
                                                @foreach ($resource->serviceCategories as $cat)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $cat->localizedName() }}</span>
                                                @endforeach
                                                @if ($resource->services_count > 0)
                                                    <span class="badge bg-secondary">{{ $resource->services_count }} {{ __('services') }}</span>
                                                @endif
                                                @if ($resource->serviceCategories->isEmpty() && $resource->services_count === 0)
                                                    <span class="text-muted tx-12">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($resource->is_active)
                                                    <span class="badge bg-success-subtle text-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                                                    data-bs-toggle="modal" data-bs-target="#editResourceModal{{ $resource->id }}">
                                                    <i data-feather="edit-2" style="width:13px;"></i>
                                                </button>
                                                <form method="POST" action="{{ route('company.resources.destroy', $resource) }}" class="d-inline"
                                                    onsubmit="return confirm('{{ __('Delete this resource? Linked services will no longer require it.') }}')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
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
@foreach ($resources as $resource)
<div class="modal fade" id="editResourceModal{{ $resource->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.resources.update', $resource) }}">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit resource') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Name (EN)') }}</label>
                        <input type="text" name="name_en" class="form-control" value="{{ $resource->name_en }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Name (AR)') }}</label>
                        <input type="text" name="name_ar" dir="rtl" class="form-control" value="{{ $resource->name_ar }}">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">{{ __('Type') }}</label>
                            <select name="type" class="form-select">
                                <option value="room" {{ $resource->type === 'room' ? 'selected' : '' }}>{{ __('Room') }}</option>
                                <option value="equipment" {{ $resource->type === 'equipment' ? 'selected' : '' }}>{{ __('Equipment') }}</option>
                                <option value="other" {{ $resource->type === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">{{ __('Sort order') }}</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $resource->sort_order }}" min="0">
                        </div>
                    </div>
                    @if ($serviceCategories->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-1">{{ __('Applies to whole categories') }}</label>
                            <p class="form-text mt-0 mb-2">{{ __('Every service in the selected categories (in this branch) will require this resource — no need to link services one by one.') }}</p>
                            @foreach ($serviceCategories as $cat)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="category_ids[]"
                                           id="editCat{{ $resource->id }}_{{ $cat->id }}" value="{{ $cat->id }}"
                                           {{ $resource->serviceCategories->contains($cat->id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="editCat{{ $resource->id }}_{{ $cat->id }}">{{ $cat->localizedName() }}</label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="resActive{{ $resource->id }}"
                               name="is_active" value="1" {{ $resource->is_active ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="resActive{{ $resource->id }}">{{ __('Active') }}</label>
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
