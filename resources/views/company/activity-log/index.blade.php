@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ __('Activity Log') }}</h4>
            <p class="text-muted mb-0 tx-13">
                {{ __('Every action performed on your account is recorded here.') }}
                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill ms-1" style="font-size:.7rem;">
                    {{ number_format($totalCount) }} {{ __('total') }}
                </span>
            </p>
        </div>
        @if($totalCount > 0)
        <button class="btn btn-sm btn-outline-danger rounded-pill px-3"
                data-bs-toggle="modal" data-bs-target="#deleteAllModal">
            <i data-feather="trash-2" style="width:13px;height:13px;"></i>
            {{ __('Delete all') }}
        </button>
        @endif
    </div>

    @include('company.partials.flash')

    {{-- Filter --}}
    <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i data-feather="search" style="width:14px;height:14px;color:#C9A227;"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="{{ __('Search action or user…') }}"
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-sm-4">
                    <select name="subject" class="form-select">
                        <option value="">{{ __('All types') }}</option>
                        <option value="Appointment"   {{ request('subject') === 'Appointment'   ? 'selected' : '' }}>📅 {{ __('Appointments') }}</option>
                        <option value="Invoice"       {{ request('subject') === 'Invoice'       ? 'selected' : '' }}>🧾 {{ __('Invoices') }}</option>
                        <option value="Service"       {{ request('subject') === 'Service'       ? 'selected' : '' }}>💆 {{ __('Services') }}</option>
                        <option value="BranchPayment" {{ request('subject') === 'BranchPayment' ? 'selected' : '' }}>💰 {{ __('Payments') }}</option>
                    </select>
                </div>
                <div class="col-sm-3 d-flex gap-2">
                    <button class="btn btn-primary rounded-pill flex-fill">{{ __('Filter') }}</button>
                    @if(request()->hasAny(['search','subject']))
                    <a href="{{ route('company.activity-log.index') }}" class="btn btn-outline-secondary rounded-pill px-3" title="{{ __('Clear') }}">
                        <i data-feather="x" style="width:14px;height:14px;"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Bulk action bar (hidden by default, shown via JS) --}}
    <div id="bulk-bar" class="card border-0 shadow-sm rounded-4 mb-3 d-none" style="border-left:3px solid #ef4444 !important;">
        <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
            <span style="font-size:.82rem;font-weight:700;">
                <span id="selected-count">0</span> {{ __('selected') }}
            </span>
            <button type="button" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold"
                    data-bs-toggle="modal" data-bs-target="#deleteSelectedModal">
                <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                {{ __('Delete selected') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                    onclick="uncheckAll()">
                {{ __('Cancel') }}
            </button>
        </div>
    </div>

    {{-- Timeline --}}
    <form id="bulk-form" method="POST" action="{{ route('company.activity-log.destroy-selected') }}">
        @csrf @method('DELETE')

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            {{-- Select all checkbox --}}
            @if($logs->isNotEmpty())
            <div class="d-flex align-items-center gap-2 mb-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,.06);">
                <input type="checkbox" id="select-all" class="form-check-input" style="cursor:pointer;"
                       onchange="toggleAll(this.checked)">
                <label for="select-all" class="form-check-label tx-12 text-muted fw-semibold" style="cursor:pointer;">
                    {{ __('Select all on this page') }}
                </label>
            </div>
            @endif

            @if($logs->isEmpty())
                <div class="bk-empty py-5">
                    <div class="bk-empty-ic mb-3">
                        <i data-feather="shield" style="width:24px;height:24px;"></i>
                    </div>
                    <p>{{ __('No activity found.') }}</p>
                    <p class="tx-12" style="color:rgba(255,255,255,.2);">{{ __('Actions like status changes and invoice creation will appear here.') }}</p>
                </div>
            @else
                <div class="bk-timeline">
                    @foreach($logs as $log)
                    @php
                        $subjectClass = $log->subject_type ? strtolower(class_basename($log->subject_type)) : 'default';
                        $dotClass = in_array($subjectClass, ['appointment','invoice','service','branchpayment'])
                            ? 'bk-tl-dot-' . $subjectClass : 'bk-tl-dot-default';
                        $icon = match($subjectClass) {
                            'appointment'   => '📅',
                            'invoice'       => '🧾',
                            'service'       => '💆',
                            'branchpayment' => '💰',
                            default         => '🔔',
                        };
                    @endphp
                    <div class="bk-tl-item">
                        <div class="d-flex align-items-start gap-2">
                            <input type="checkbox" name="ids[]" value="{{ $log->id }}"
                                   class="form-check-input log-check mt-1 flex-shrink-0"
                                   style="cursor:pointer;"
                                   onchange="updateBulkBar()">
                            <div class="bk-tl-dot {{ $dotClass }}"></div>
                            <div class="bk-tl-body flex-fill">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <div class="bk-tl-desc">
                                        <span class="me-2">{{ $icon }}</span>{{ $log->description }}
                                    </div>
                                    <span class="tx-11 text-muted text-nowrap">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="bk-tl-meta">
                                    <span>
                                        <i data-feather="user" style="width:11px;height:11px;margin-inline-end:3px;"></i>
                                        {{ $log->causer_name ?? '—' }}
                                        <span style="opacity:.6;">({{ $log->causer_type }})</span>
                                    </span>
                                    @if($log->ip_address)
                                    <span>
                                        <i data-feather="monitor" style="width:11px;height:11px;margin-inline-end:3px;"></i>
                                        {{ $log->ip_address }}
                                    </span>
                                    @endif
                                    @if($log->subject_id)
                                    <span>
                                        {{ class_basename($log->subject_type ?? '') }} #{{ $log->subject_id }}
                                    </span>
                                    @endif
                                    <span class="tx-11 text-muted">
                                        {{ $log->created_at->format('d M Y · H:i') }}
                                    </span>
                                </div>

                                {{-- Before / After diff --}}
                                @if($log->properties && (isset($log->properties['old']) || isset($log->properties['new'])))
                                <button class="btn btn-xs btn-outline-secondary rounded-pill tx-11 mt-2"
                                    style="padding:2px 10px;font-size:.7rem;"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#log-props-{{ $log->id }}">
                                    {{ __('Show changes') }}
                                </button>
                                <div class="collapse mt-2" id="log-props-{{ $log->id }}">
                                    <div class="row g-2">
                                        @if(!empty($log->properties['old']))
                                        <div class="col-md-6">
                                            <p class="tx-10 fw-bold text-muted mb-1 text-uppercase" style="letter-spacing:.6px;">{{ __('Before') }}</p>
                                            <div style="background:rgba(229,57,53,.06);border:1px solid rgba(229,57,53,.12);border-radius:8px;padding:8px 12px;">
                                                @foreach($log->properties['old'] as $k => $v)
                                                <div class="tx-12">
                                                    <span class="text-muted">{{ $k }}:</span>
                                                    <span class="ms-1" style="color:#f87171;">{{ is_string($v) ? $v : json_encode($v) }}</span>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        @if(!empty($log->properties['new']))
                                        <div class="col-md-6">
                                            <p class="tx-10 fw-bold text-muted mb-1 text-uppercase" style="letter-spacing:.6px;">{{ __('After') }}</p>
                                            <div style="background:rgba(43,207,126,.06);border:1px solid rgba(43,207,126,.12);border-radius:8px;padding:8px 12px;">
                                                @foreach($log->properties['new'] as $k => $v)
                                                <div class="tx-12">
                                                    <span class="text-muted">{{ $k }}:</span>
                                                    <span class="ms-1" style="color:#4ade80;">{{ is_string($v) ? $v : json_encode($v) }}</span>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    </form>

    <div class="mt-3">{{ $logs->links() }}</div>
</div>

{{-- Delete Selected Modal --}}
<div class="modal fade" id="deleteSelectedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-body text-center p-4">
                <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                <h6 class="fw-bold mb-2">{{ __('Delete selected logs?') }}</h6>
                <p class="text-muted tx-13 mb-3">
                    {{ __('This will permanently delete') }} <strong id="modal-selected-count">0</strong> {{ __('log(s). This cannot be undone.') }}
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold"
                            onclick="document.getElementById('bulk-form').submit();">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete All Modal --}}
<div class="modal fade" id="deleteAllModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-body text-center p-4">
                <div style="font-size:40px;margin-bottom:12px;">⚠️</div>
                <h6 class="fw-bold mb-2">{{ __('Delete ALL activity logs?') }}</h6>
                <p class="text-muted tx-13 mb-3">
                    {{ __('This will permanently delete all') }} <strong>{{ number_format($totalCount) }}</strong> {{ __('logs. This cannot be undone.') }}
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <form method="POST" action="{{ route('company.activity-log.destroy-all') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                            {{ __('Delete all') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateBulkBar() {
    var checked = document.querySelectorAll('.log-check:checked');
    var bar = document.getElementById('bulk-bar');
    var countEl = document.getElementById('selected-count');
    var modalCountEl = document.getElementById('modal-selected-count');

    countEl.textContent = checked.length;
    modalCountEl.textContent = checked.length;

    if (checked.length > 0) {
        bar.classList.remove('d-none');
    } else {
        bar.classList.add('d-none');
    }

    var selectAll = document.getElementById('select-all');
    var allChecks = document.querySelectorAll('.log-check');
    selectAll.checked = allChecks.length > 0 && checked.length === allChecks.length;
    selectAll.indeterminate = checked.length > 0 && checked.length < allChecks.length;
}

function toggleAll(checked) {
    document.querySelectorAll('.log-check').forEach(function(cb) { cb.checked = checked; });
    updateBulkBar();
}

function uncheckAll() {
    document.getElementById('select-all').checked = false;
    toggleAll(false);
}
</script>
@endpush
