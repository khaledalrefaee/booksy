@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Audit log') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Audit log') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('owner.partials.flash')

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('owner.audit-log.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label for="filter-owner" class="form-label small fw-semibold mb-1">{{ __('Admin') }}</label>
                    <select name="owner_id" id="filter-owner" class="form-select form-select-sm">
                        <option value="">{{ __('All admins') }}</option>
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((string) $filterOwnerId === (string) $owner->id)>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="filter-action" class="form-label small fw-semibold mb-1">{{ __('Action') }}</label>
                    <select name="action" id="filter-action" class="form-select form-select-sm">
                        <option value="">{{ __('All actions') }}</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected($filterAction === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="filter-from" class="form-label small fw-semibold mb-1">{{ __('From date') }}</label>
                    <input type="date" name="from" id="filter-from" class="form-control form-control-sm" value="{{ $filterFrom }}">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="filter-to" class="form-label small fw-semibold mb-1">{{ __('To date') }}</label>
                    <input type="date" name="to" id="filter-to" class="form-control form-control-sm" value="{{ $filterTo }}">
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1">
                        {{ __('Filter') }}
                    </button>
                    @if($filterOwnerId !== '' || $filterAction !== '' || $filterFrom !== '' || $filterTo !== '')
                        <a href="{{ route('owner.audit-log.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                            {{ __('Reset') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Date') }}</th>
                            <th>{{ __('Admin') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Target') }}</th>
                            <th>{{ __('Changes') }}</th>
                            <th class="pe-4">{{ __('Reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $verb = str($log->action)->afterLast('.');
                                $badgeClass = match (true) {
                                    $verb->contains('delete')                          => 'bg-danger-subtle text-danger',
                                    $verb->contains('create')                          => 'bg-success-subtle text-success',
                                    $verb->contains('status'), $verb->contains('suspend') => 'bg-warning-subtle text-warning',
                                    default                                            => 'bg-info-subtle text-info',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4 text-nowrap">
                                    <div class="fw-semibold tx-13">{{ $log->created_at->format('Y-m-d') }}</div>
                                    <div class="text-muted tx-12">{{ $log->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold tx-13">{{ $log->owner?->name ?? __('Deleted admin') }}</div>
                                    @if($log->ip)
                                        <div class="text-muted tx-12" dir="ltr">{{ $log->ip }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $badgeClass }}">{{ $log->action }}</span>
                                </td>
                                <td>
                                    @if($log->auditable_label)
                                        <span class="fw-semibold tx-13">{{ $log->auditable_label }}</span>
                                    @elseif($log->auditable_id)
                                        <span class="text-muted tx-13">#{{ $log->auditable_id }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="max-width: 320px;">
                                    @if($log->new_values || $log->old_values)
                                        <ul class="list-unstyled mb-0 tx-12">
                                            @foreach(array_slice(array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? []))), 0, 4) as $key)
                                                <li class="text-truncate">
                                                    <span class="text-muted">{{ $key }}:</span>
                                                    @if(array_key_exists($key, $log->old_values ?? []))
                                                        <del class="text-muted">{{ is_scalar($log->old_values[$key]) ? $log->old_values[$key] : json_encode($log->old_values[$key], JSON_UNESCAPED_UNICODE) }}</del>
                                                    @endif
                                                    @if(array_key_exists($key, $log->new_values ?? []))
                                                        <span class="fw-semibold">{{ is_scalar($log->new_values[$key]) ? $log->new_values[$key] : json_encode($log->new_values[$key], JSON_UNESCAPED_UNICODE) }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="pe-4" style="max-width: 220px;">
                                    @if($log->reason)
                                        <span class="tx-13" title="{{ $log->reason }}">{{ str($log->reason)->limit(80) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="shield" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No audit entries yet. Sensitive owner actions will appear here.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>

@endsection
