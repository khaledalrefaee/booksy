@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-2">{{ __('Services') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('owner.branches.services.create', $branch) }}" class="btn btn-primary btn-icon-text rounded-pill shadow-sm">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add service') }}
        </a>
    </div>

    @include('owner.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-4 bg-light bg-opacity-50">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill">{{ $branch->localizedName() }}</span>
                <span class="text-muted tx-13">{{ __('Service catalog for this branch') }}</span>
            </div>
            <a href="{{ route('owner.branches.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">{{ __('All branches') }}</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Service category') }}</th>
                            <th>{{ __('Name (English)') }}</th>
                            <th>{{ __('Name (Arabic)') }}</th>
                            <th>{{ __('Duration') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td class="ps-4 text-muted">{{ $service->serviceCategory?->localizedName() ?? '—' }}</td>
                                <td class="fw-medium">{{ $service->name_en ?: '—' }}</td>
                                <td lang="ar" dir="rtl">{{ $service->name_ar ?: '—' }}</td>
                                <td>{{ $service->duration_minutes }} {{ __('min') }}</td>
                                <td>{{ number_format((float) $service->price, 2) }}</td>
                                <td>
                                    <form method="post" action="{{ route('owner.services.toggle-active', $service) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-check form-switch mb-0">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                id="service-active-{{ $service->id }}"
                                                onchange="this.form.submit()"
                                                @checked($service->is_active)
                                                aria-label="{{ __('Toggle active') }}">
                                            <label class="form-check-label small text-muted" for="service-active-{{ $service->id }}">
                                                {{ $service->is_active ? __('Active') : __('Inactive') }}
                                            </label>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('owner.services.edit', $service) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">{{ __('Edit') }}</a>
                                    <form action="{{ route('owner.services.destroy', $service) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this service?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">{{ __('No services for this branch.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
