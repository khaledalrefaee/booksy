@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-2">{{ __('Employees') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('owner.branches.employees.create', $branch) }}" class="btn btn-primary btn-icon-text rounded-pill shadow-sm">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add employees') }}
        </a>
    </div>

    @include('owner.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-4 bg-light bg-opacity-50">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill">{{ $branch->localizedName() }}</span>
                <span class="text-muted tx-13">{{ __('Staff for this branch') }}</span>
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
                            <th class="ps-4">{{ __('Name (English)') }}</th>
                            <th>{{ __('Name (Arabic)') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="ps-4 fw-medium">{{ $employee->name_en ?: '—' }}</td>
                                <td lang="ar" dir="rtl">{{ $employee->name_ar ?: '—' }}</td>
                                <td class="text-muted">{{ $employee->email ?: '—' }}</td>
                                <td class="text-muted">{{ $employee->phone ?: '—' }}</td>
                                <td>
                                    @if ($employee->role)
                                        {{ app()->getLocale() === 'ar' ? ($employee->role->label_ar ?: $employee->role->label_en) : ($employee->role->label_en ?: $employee->role->label_ar) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($employee->is_active)
                                        <span class="badge rounded-pill bg-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('owner.employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">{{ __('Edit') }}</a>
                                    <form action="{{ route('owner.employees.destroy', $employee) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this employee?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">{{ __('No employees for this branch.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
