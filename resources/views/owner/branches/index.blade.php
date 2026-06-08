@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-2">{{ __('Branches') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Branches') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('owner.branches.create') }}" class="btn btn-primary btn-icon-text rounded-pill shadow-sm">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add branch') }}
        </a>
    </div>

    @include('owner.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Company') }}</th>
                            <th>{{ __('Name (English)') }}</th>
                            <th>{{ __('Name (Arabic)') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Head office') }}</th>
                            <th>{{ __('Sort') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr>
                                <td class="ps-4">
                                    <span class="text-muted tx-13">{{ $branch->company?->localizedName() ?? '—' }}</span>
                                </td>
                                <td class="fw-medium">{{ $branch->name_en ?: '—' }}</td>
                                <td lang="ar" dir="rtl">{{ $branch->name_ar ?: '—' }}</td>
                                <td class="text-muted">{{ $branch->phone ?: '—' }}</td>
                                <td>
                                    @if ($branch->is_head_office)
                                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-muted border">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $branch->sort_order }}</td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="{{ route('owner.branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                        <i data-feather="edit-2" style="width:13px;height:13px;"></i>
                                        {{ __('Edit') }}
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ __('More') }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.working-hours.create', $branch) }}">
                                                    <i data-feather="clock" style="width:14px;height:14px;"></i>
                                                    {{ __('Working hours') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.services.index', $branch) }}">
                                                    <i data-feather="package" style="width:14px;height:14px;"></i>
                                                    {{ __('Services') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('owner.branches.employees.index', $branch) }}">
                                                    <i data-feather="users" style="width:14px;height:14px;"></i>
                                                    {{ __('Employees') }}
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('owner.branches.destroy', $branch) }}" method="post" onsubmit="return confirm('{{ __('Delete this branch?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">{{ __('No branches yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
