@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-1">{{ __('Employees') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.branches.employees.create', $branch) }}" class="btn btn-primary btn-icon-text rounded-pill">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add employee') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $emp)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-medium">{{ $emp->name_en ?: '—' }}</div>
                                    @if($emp->name_ar)<div class="text-muted tx-12" dir="rtl">{{ $emp->name_ar }}</div>@endif
                                </td>
                                <td class="text-muted">{{ $emp->email ?: '—' }}</td>
                                <td class="text-muted">{{ $emp->phone ?: '—' }}</td>
                                <td>
                                    @if($emp->role)
                                        <span class="badge bg-secondary rounded-pill">
                                            {{ app()->getLocale() === 'ar' ? ($emp->role->label_ar ?: $emp->role->label_en) : ($emp->role->label_en ?: $emp->role->label_ar) }}
                                        </span>
                                    @else —
                                    @endif
                                </td>
                                <td>
                                    @if($emp->is_active)
                                        <span class="badge rounded-pill bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('company.employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">{{ __('Edit') }}</a>
                                    <form action="{{ route('company.employees.destroy', $emp) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this employee?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">{{ __('No employees for this branch.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
