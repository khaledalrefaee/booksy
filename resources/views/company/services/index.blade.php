@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-1">{{ __('Services') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.branches.services.create', $branch) }}" class="btn btn-primary btn-icon-text rounded-pill">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add service') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Service') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Duration') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-medium">{{ $service->name_en ?: '—' }}</div>
                                    @if($service->name_ar)<div class="text-muted tx-12" dir="rtl">{{ $service->name_ar }}</div>@endif
                                </td>
                                <td class="text-muted">{{ $service->serviceCategory?->localizedName() ?? '—' }}</td>
                                <td class="fw-semibold">{{ number_format($service->price, 2) }}</td>
                                <td class="text-muted">{{ $service->duration_minutes }} {{ __('min') }}</td>
                                <td>
                                    <form action="{{ route('company.services.toggle-active', $service) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm rounded-pill {{ $service->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                            {{ $service->is_active ? __('Active') : __('Inactive') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('company.services.edit', $service) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">{{ __('Edit') }}</a>
                                    <form action="{{ route('company.services.destroy', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this service?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">{{ __('No services for this branch.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
