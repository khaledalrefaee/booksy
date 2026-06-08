@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <h4 class="mb-0">{{ __('Branches') }}</h4>
        <a href="{{ route('company.branches.create') }}" class="btn btn-primary btn-icon-text rounded-pill">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add branch') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="row">
        @forelse ($branches as $branch)
            <div class="col-md-6 col-xl-4 grid-margin stretch-card">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">{{ $branch->localizedName() }}</h6>
                                <div class="d-flex gap-1 flex-wrap mt-1">
                                    @if($branch->is_head_office)
                                        <span class="badge bg-primary rounded-pill">{{ __('Head office') }}</span>
                                    @endif
                                    <span class="badge bg-{{ $branch->statusColor() }} rounded-pill">
                                        <i data-feather="{{ $branch->status === 'active' ? 'check-circle' : ($branch->status === 'maintenance' ? 'tool' : 'x-circle') }}" style="width:10px;height:10px;" class="me-1"></i>
                                        {{ __($branch->statusLabel()) }}
                                    </span>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown"><i data-feather="more-vertical" style="width:16px;"></i></a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ route('company.branches.edit', $branch) }}" class="dropdown-item">
                                        <i data-feather="edit-2" class="icon-sm me-1"></i> {{ __('Edit') }}
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    {{-- Quick status change --}}
                                    @foreach(['active' => ['check-circle','success',__('Set Active')], 'inactive' => ['x-circle','secondary',__('Set Inactive')], 'maintenance' => ['tool','warning',__('Set Maintenance')]] as $st => [$icon,$color,$label])
                                        @if($branch->status !== $st)
                                        <form method="POST" action="{{ route('company.branches.status', $branch) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $st }}">
                                            <button type="submit" class="dropdown-item text-{{ $color }}">
                                                <i data-feather="{{ $icon }}" class="icon-sm me-1"></i> {{ $label }}
                                            </button>
                                        </form>
                                        @endif
                                    @endforeach
                                    <div class="dropdown-divider"></div>
                                    <form method="POST" action="{{ route('company.branches.destroy', $branch) }}" onsubmit="return confirm('{{ __('Delete this branch?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i data-feather="trash-2" class="icon-sm me-1"></i> {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @if($branch->address)
                            <p class="text-muted tx-13 mb-1">
                                <i data-feather="map-pin" style="width:13px;"></i> {{ $branch->address }}
                            </p>
                        @endif
                        @if($branch->phone)
                            <p class="text-muted tx-13 mb-3">
                                <i data-feather="phone" style="width:13px;"></i> {{ $branch->phone }}
                            </p>
                        @endif

                        <hr class="my-3">

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('company.branches.services.index', $branch) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                <i data-feather="scissors" style="width:13px;height:13px;" class="me-1"></i>
                                {{ __('Services') }}
                            </a>
                            <a href="{{ route('company.branches.employees.index', $branch) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                <i data-feather="users" style="width:13px;height:13px;" class="me-1"></i>
                                {{ __('Employees') }}
                            </a>
                            <a href="{{ route('company.branches.working-hours.edit', $branch) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                <i data-feather="clock" style="width:13px;height:13px;" class="me-1"></i>
                                {{ __('Hours') }}
                            </a>
                            <a href="{{ route('company.appointments.index', ['branch_id' => $branch->id]) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                <i data-feather="calendar" style="width:13px;height:13px;" class="me-1"></i>
                                {{ __('Appointments') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i data-feather="map-pin" style="width:40px;height:40px;" class="opacity-50 mb-3"></i>
                        <p>{{ __('No branches yet.') }}</p>
                        <a href="{{ route('company.branches.create') }}" class="btn btn-primary rounded-pill">{{ __('Add first branch') }}</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
