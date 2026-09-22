@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Leaves Index ── */
.leaves-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #fff;
    position: relative; overflow: hidden;
}
.leaves-hero::before {
    content: ''; position: absolute;
    top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
[dir="rtl"] .leaves-hero::before { right: auto; left: -50px; }

.stat-chip {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 12px; padding: 9px 18px;
    text-align: center; backdrop-filter: blur(6px);
}
.stat-chip .num { font-size: 20px; font-weight: 800; line-height: 1; }
.stat-chip .lbl { font-size: 11px; opacity: .75; margin-top: 2px; }

/* Filter tabs */
.lv-tabs { display: flex; gap: 6px; margin-bottom: 18px; flex-wrap: wrap; }
.lv-tab {
    border-radius: 10px; padding: 6px 16px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    border: 1.5px solid rgba(255,255,255,.1);
    background: rgba(255,255,255,.04); color: rgba(255,255,255,.6);
    transition: all .18s; user-select: none;
}
.bk-theme-light .lv-tab { border-color: #dee2e6; background: #f8f9fa; color: rgba(0,0,0,.5); }
.lv-tab:hover { color: #fff; border-color: rgba(255,255,255,.3); }
.bk-theme-light .lv-tab:hover { color: #212529; border-color: #adb5bd; }
.lv-tab.active { background: #f5576c; border-color: #f5576c; color: #fff; box-shadow: 0 3px 12px rgba(245,87,108,.35); }

/* Leave cards */
.lv-card {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    transition: background .18s;
}
.bk-theme-light .lv-card { border-bottom-color: rgba(0,0,0,.05); }
.lv-card:last-child { border-bottom: none; }
.lv-card:hover { background: rgba(245,87,108,.05); }

.lv-avatar {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; color: #fff; flex-shrink: 0;
}
.lv-name { font-weight: 600; font-size: 14px; }
.lv-dates { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 3px; display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
.bk-theme-light .lv-dates { color: rgba(0,0,0,.5); }
.lv-reason { font-size: 12px; color: rgba(255,255,255,.4); margin-top: 2px; }
.bk-theme-light .lv-reason { color: rgba(0,0,0,.4); }

.lv-badge {
    border-radius: 8px; padding: 3px 10px;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.lv-badge-pending  { background: rgba(251,191,36,.12); color: #fbbf24; }
.lv-badge-approved { background: rgba(52,211,153,.12); color: #34d399; }
.lv-badge-rejected { background: rgba(248,113,113,.12); color: #f87171; }
.bk-theme-light .lv-badge-pending  { background: rgba(217,119,6,.1);  color: #b45309; }
.bk-theme-light .lv-badge-approved { background: rgba(5,150,105,.1);  color: #047857; }
.bk-theme-light .lv-badge-rejected { background: rgba(185,28,28,.1);  color: #b91c1c; }

.days-pill {
    background: rgba(255,255,255,.08); border-radius: 7px;
    padding: 2px 8px; font-size: 11px; font-weight: 700;
    flex-shrink: 0;
}
.bk-theme-light .days-pill { background: rgba(0,0,0,.06); }

.btn-lv {
    border: none; border-radius: 8px;
    font-size: 11px; font-weight: 600; padding: 5px 11px;
    cursor: pointer; transition: opacity .18s; display: inline-flex; align-items: center; gap: 4px;
}
.btn-lv:hover { opacity: .85; }
.btn-lv-approve { background: linear-gradient(135deg,#34d399,#10b981); color: #fff; }
.btn-lv-reject  { background: linear-gradient(135deg,#f87171,#ef4444); color: #fff; }
.btn-lv-del {
    background: transparent; color: rgba(255,255,255,.3);
    border: 1.5px solid rgba(255,255,255,.1);
}
.btn-lv-del:hover { border-color: #f87171; color: #f87171; }
.bk-theme-light .btn-lv-del { color: rgba(0,0,0,.3); border-color: rgba(0,0,0,.15); }
.bk-theme-light .btn-lv-del:hover { border-color: #dc3545; color: #dc3545; }
.btn-lv-edit {
    background: transparent; color: var(--bk-text-muted);
    border: 1.5px solid var(--bk-border);
}
.btn-lv-edit:hover { border-color: var(--bk-accent); color: var(--bk-accent); opacity: 1; }

/* ── Edit-leave modal ── */
.lv-modal .modal-content {
    border-radius: 18px; overflow: hidden; border: none;
    background: var(--bk-surface); color: var(--bk-text);
}
.lv-modal .modal-header {
    background: linear-gradient(135deg, #4B5D34 0%, #5C7038 100%);
    color: #fff; border: none; padding: 16px 22px;
}
.lv-modal .form-label { font-size: 12px; font-weight: 700; color: var(--bk-text-soft); margin-bottom: 4px; }
.lv-modal .form-control, .lv-modal .form-select { border-radius: 10px; font-size: 13px; }
.lv-modal .form-control:focus, .lv-modal .form-select:focus {
    border-color: var(--bk-accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--bk-accent) 18%, transparent);
}
.lv-modal .lv-hourly-toggle {
    background: var(--bk-accent-wash); border: 1.5px solid color-mix(in srgb, var(--bk-accent) 25%, transparent);
    border-radius: 12px; padding: 12px 14px; cursor: pointer;
}
.lv-modal .lv-deduct-box {
    background: var(--bk-warning-bg); border: 1.5px solid color-mix(in srgb, var(--bk-warning) 25%, transparent);
    border-radius: 12px; padding: 12px 14px;
}
.lv-modal .btn-save {
    background: var(--bk-accent-fill); color: var(--bk-accent-ink);
    border: none; border-radius: 10px; font-weight: 700; font-size: 13px; padding: 9px 26px;
    box-shadow: 0 4px 14px color-mix(in srgb, var(--bk-accent) 30%, transparent);
}
.lv-modal .btn-save:hover { background: var(--bk-accent-hover); color: var(--bk-accent-ink); }

.bk-empty-lv {
    display: flex; flex-direction: column; align-items: center;
    padding: 60px 20px; gap: 10px; text-align: center;
}
.bk-empty-lv svg { opacity: .18; }
.bk-empty-lv p { font-size: 14px; color: rgba(255,255,255,.4); margin: 0; }
.bk-theme-light .bk-empty-lv p { color: rgba(0,0,0,.4); }
</style>
@endpush

@section('content')
<div class="page-content">

    @include('company.partials.team-nav')

    {{-- Hero --}}
    <div class="leaves-hero bk-a1">
        <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Employee Leaves') }}</h3>
                <p class="mb-0" style="color:rgba(255,255,255,.65); font-size:13px;">{{ __('Manage leave requests across all employees') }}</p>
            </div>
            <div class="d-flex gap-2">
                <div class="stat-chip">
                    <div class="num">{{ $counts['pending'] }}</div>
                    <div class="lbl">{{ __('Pending') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="num">{{ $counts['approved'] }}</div>
                    <div class="lbl">{{ __('Approved') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="num">{{ $counts['rejected'] }}</div>
                    <div class="lbl">{{ __('Rejected') }}</div>
                </div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- Filters --}}
    @php
        $filterBase = array_filter(['employee_id' => $employeeId, 'branch_id' => $branchId, 'month' => $month]);
        $hasFilters = $employeeId || $branchId || $month || $status;
    @endphp
    <form method="GET" action="{{ route('company.employee-leaves.index') }}"
          class="card border-0 rounded-4 shadow-sm mb-3">
        <div class="d-flex flex-wrap align-items-end gap-2 px-3 py-3">
            <div style="flex:1;min-width:160px;">
                <label class="tx-11 fw-bold text-muted d-block mb-1">{{ __('Employee') }}</label>
                <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($employeeList as $e)
                    <option value="{{ $e->id }}" {{ $employeeId == $e->id ? 'selected' : '' }}>{{ $e->localizedName() }}</option>
                    @endforeach
                </select>
            </div>
            @if(! ($branchContext ?? null))
            <div style="flex:1;min-width:140px;">
                <label class="tx-11 fw-bold text-muted d-block mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All branches') }}</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->localizedName() }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="flex:1;min-width:140px;">
                <label class="tx-11 fw-bold text-muted d-block mb-1">{{ __('Month') }}</label>
                <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            @if($hasFilters)
            <a href="{{ route('company.employee-leaves.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size:12px;">
                ✕ {{ __('Clear all') }}
            </a>
            @endif
        </div>
    </form>

    {{-- Status tabs (server-side) --}}
    <div class="lv-tabs bk-a2">
        <a href="{{ route('company.employee-leaves.index', $filterBase) }}"
           class="lv-tab {{ !$status ? 'active' : '' }}" style="text-decoration:none;">{{ __('All') }} ({{ $counts['all'] }})</a>
        <a href="{{ route('company.employee-leaves.index', $filterBase + ['status' => 'pending']) }}"
           class="lv-tab {{ $status === 'pending' ? 'active' : '' }}" style="text-decoration:none;">{{ __('Pending') }} ({{ $counts['pending'] }})</a>
        <a href="{{ route('company.employee-leaves.index', $filterBase + ['status' => 'approved']) }}"
           class="lv-tab {{ $status === 'approved' ? 'active' : '' }}" style="text-decoration:none;">{{ __('Approved') }} ({{ $counts['approved'] }})</a>
        <a href="{{ route('company.employee-leaves.index', $filterBase + ['status' => 'rejected']) }}"
           class="lv-tab {{ $status === 'rejected' ? 'active' : '' }}" style="text-decoration:none;">{{ __('Rejected') }} ({{ $counts['rejected'] }})</a>
    </div>

    {{-- Cards --}}
    <div class="card border-0 bk-a3" style="border-radius:18px !important; overflow:hidden;">
        <div class="card-body p-0" id="lvList">
            @forelse($leaves as $leave)
            @php
                $palette = ['#5C7038','#f093fb','#4facfe','#43e97b','#fa709a'];
                $bg = $palette[$leave->employee_id % count($palette)];
                $initial = strtoupper(mb_substr($leave->employee->name_en ?? $leave->employee->name_ar ?? '?', 0, 1));
            @endphp
            @php
                $typeMeta = $leave->typeMeta();
                $remaining = $balances[$leave->employee_id] ?? null;
                $overBalance = $leave->status === 'pending'
                    && $leave->type === 'annual'
                    && $remaining !== null
                    && $leave->daysCount() > $remaining;
            @endphp
            <div class="lv-card" data-status="{{ $leave->status }}">
                <div class="lv-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">{{ $initial }}</div>

                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <a href="{{ route('company.employees.show', $leave->employee) }}" class="lv-name"
                           style="color:inherit;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $leave->employee->localizedName() }}</a>
                        <span class="lv-badge" style="background:{{ $typeMeta['color'] }}1f;color:{{ $typeMeta['color'] }};">
                            {{ $typeMeta['icon'] }} {{ __($typeMeta['label_key']) }}
                        </span>
                        <span class="lv-badge lv-badge-{{ $leave->status }}">
                            @if($leave->status==='approved') ✓ {{ __('Approved') }}
                            @elseif($leave->status==='rejected') ✗ {{ __('Rejected') }}
                            @else ⏳ {{ __('Pending') }}
                            @endif
                        </span>
                        @if($leave->is_hourly)
                            <span class="days-pill" style="color:#4facfe;">⏱️ {{ substr($leave->start_hour, 0, 5) }}–{{ substr($leave->end_hour, 0, 5) }} ({{ $leave->hoursCount() }} {{ __('hour(s)') }})</span>
                        @else
                            <span class="days-pill">{{ $leave->daysCount() }} {{ __('day(s)') }}</span>
                        @endif
                        @if($overBalance)
                        <span class="lv-badge" style="background:rgba(245,158,11,.14);color:#f59e0b;"
                              title="{{ __('Remaining balance') }}: {{ $remaining }} {{ __('day(s)') }}">
                            ⚠️ {{ __('Exceeds balance') }} ({{ $remaining }})
                        </span>
                        @endif
                    </div>
                    <div class="lv-dates">
                        <i data-feather="calendar" style="width:11px;height:11px;opacity:.5;"></i>
                        {{ $leave->start_date->translatedFormat('D d M Y') }}
                        <span style="opacity:.4;">→</span>
                        {{ $leave->end_date->translatedFormat('D d M Y') }}
                    </div>
                    @if($leave->reason)
                    <div class="lv-reason">
                        <i data-feather="message-circle" style="width:11px;height:11px;" class="{{ app()->getLocale()==='ar' ? 'ms-1' : 'me-1' }}"></i>{{ $leave->reason }}
                    </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
                    @if($leave->status==='pending')
                    <form method="POST" action="{{ route('company.employee-leaves.update-status', $leave) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button class="btn-lv btn-lv-approve">
                            <i data-feather="check" style="width:11px;height:11px;"></i>{{ __('Approve') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('company.employee-leaves.update-status', $leave) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button class="btn-lv btn-lv-reject">
                            <i data-feather="x" style="width:11px;height:11px;"></i>{{ __('Reject') }}
                        </button>
                    </form>
                    @endif
                    @php
                        // Built here (not inline in @json) because Blade's directive
                        // parser mis-compiles a multi-line array literal with nested
                        // casts/calls, producing invalid PHP.
                        $leaveEditData = [
                            "id"          => $leave->id,
                            "name"        => $leave->employee->localizedName(),
                            "type"        => $leave->type,
                            "is_hourly"   => (bool) $leave->is_hourly,
                            "start_date"  => $leave->start_date->toDateString(),
                            "end_date"    => $leave->end_date->toDateString(),
                            "start_hour"  => $leave->start_hour ? substr($leave->start_hour, 0, 5) : "",
                            "end_hour"    => $leave->end_hour ? substr($leave->end_hour, 0, 5) : "",
                            "reason"      => $leave->reason ?? "",
                            "deduction_amount"   => $leave->deduction_amount ? (float) $leave->deduction_amount : "",
                            "deduction_currency" => $leave->deduction_currency ?? ($leave->employee->compensation?->currency ?? config("booksy.default_currency", "SYP")),
                            "status"      => $leave->status,
                        ];
                    @endphp
                    <button type="button" class="btn-lv btn-lv-edit"
                            title="{{ __('Edit') }}"
                            onclick='openLeaveEdit(@json($leaveEditData, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT))'>
                        <i data-feather="edit-2" style="width:11px;height:11px;"></i>
                    </button>
                    <button type="button" class="btn-lv btn-lv-del"
                            onclick="bkConfirmDelete('{{ route('company.employee-leaves.destroy', $leave) }}', '{{ addslashes($leave->employee->localizedName()) }} — {{ $leave->start_date->format('d/m') }} → {{ $leave->end_date->format('d/m') }}', '{{ __('Delete this leave request?') }}')">
                        <i data-feather="trash-2" style="width:11px;height:11px;"></i>
                    </button>
                </div>
            </div>
            @empty
            <div class="bk-empty-lv">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p>{{ $hasFilters ? __('No leave requests match your filters.') : __('No leave requests yet.') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    @if($leaves->hasPages())
    <div class="mt-3">{{ $leaves->links() }}</div>
    @endif

    @include('company.partials.confirm-delete-modal')

    {{-- Edit leave modal --}}
    <div class="modal fade lv-modal" id="leaveEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <div class="modal-content">
                <form method="POST" id="leaveEditForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold mb-0">
                            <i data-feather="edit-2" style="width:15px;height:15px;" class="{{ app()->getLocale()==='ar' ? 'ms-1' : 'me-1' }}"></i>
                            {{ __('Edit leave request') }}
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted tx-12 mb-3" id="lvEditName"></p>

                        {{-- Type --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Leave Type') }} <span class="text-danger">*</span></label>
                            <select name="type" id="lvEditType" class="form-select form-select-sm">
                                @foreach(\App\Models\EmployeeLeave::LEAVE_TYPES as $key => $meta)
                                <option value="{{ $key }}">{{ $meta['icon'] }} {{ __($meta['label_key']) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Hourly toggle --}}
                        <label class="lv-hourly-toggle d-flex align-items-center gap-3 mb-3">
                            <input type="checkbox" name="is_hourly" id="lvEditHourly" value="1" class="form-check-input mt-0"
                                   style="width:38px;height:20px;cursor:pointer;flex-shrink:0;">
                            <span>
                                <span class="d-block fw-bold" style="font-size:13px;">⏱️ {{ __('Hourly permission') }}</span>
                                <span class="d-block tx-11" style="opacity:.6;">{{ __('A few hours within a single day — does not consume annual leave days') }}</span>
                            </span>
                        </label>

                        {{-- Dates / hours --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="lvEditStart" class="form-control form-control-sm"
                                       min="{{ \App\Http\Controllers\Company\EmployeeLeaveController::minLeaveDate()->toDateString() }}"
                                       max="{{ \App\Http\Controllers\Company\EmployeeLeaveController::maxLeaveDate()->toDateString() }}">
                            </div>
                            <div class="col-6" id="lvEditEndCol">
                                <label class="form-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="lvEditEnd" class="form-control form-control-sm"
                                       min="{{ \App\Http\Controllers\Company\EmployeeLeaveController::minLeaveDate()->toDateString() }}"
                                       max="{{ \App\Http\Controllers\Company\EmployeeLeaveController::maxLeaveDate()->toDateString() }}">
                            </div>
                            <div class="col-6 d-none" id="lvEditFromCol">
                                <label class="form-label">{{ __('From') }} <span class="text-danger">*</span></label>
                                <input type="time" name="start_hour" id="lvEditFrom" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 d-none" id="lvEditToCol">
                                <label class="form-label">{{ __('To') }} <span class="text-danger">*</span></label>
                                <input type="time" name="end_hour" id="lvEditTo" class="form-control form-control-sm">
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label class="form-label">{{ __('Reason') }}</label>
                            <textarea name="reason" id="lvEditReason" class="form-control form-control-sm" rows="2"
                                      style="resize:none;" placeholder="{{ __('Describe the reason for leave…') }}"></textarea>
                        </div>

                        {{-- Deduction --}}
                        <div class="lv-deduct-box mb-3">
                            <label class="form-label mb-2" style="color:var(--bk-warning);">💸 {{ __('Salary deduction') }}
                                <span class="fw-normal text-muted">({{ __('optional') }})</span></label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <input type="number" name="deduction_amount" id="lvEditDeductAmount" min="0" step="0.01"
                                           class="form-control form-control-sm" placeholder="{{ __('Amount') }}">
                                </div>
                                <div class="col-5">
                                    <select name="deduction_currency" id="lvEditDeductCurrency" class="form-select form-select-sm">
                                        @foreach(config('booksy.currencies', []) as $code => $cur)
                                        <option value="{{ $code }}">{{ $cur['symbol'] }} {{ app()->getLocale() === 'ar' ? $cur['name_ar'] : $cur['name_en'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="tx-11 mt-2" style="opacity:.65;">{{ __('The deduction is recorded on the employee only when the leave is approved, and appears in payroll.') }}</div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-sm rounded-3 px-4" style="background:var(--bk-surface-2);color:var(--bk-text-soft);font-weight:600;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn-save">{{ __('Save Changes') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@push('scripts')
<script>
function openLeaveEdit(d) {
    var form = document.getElementById('leaveEditForm');
    form.action = '{{ url('company/employee-leaves') }}/' + d.id;

    document.getElementById('lvEditName').textContent = d.name;
    document.getElementById('lvEditType').value = d.type;
    document.getElementById('lvEditStart').value = d.start_date;
    document.getElementById('lvEditEnd').value = d.end_date;
    document.getElementById('lvEditFrom').value = d.start_hour || '';
    document.getElementById('lvEditTo').value = d.end_hour || '';
    document.getElementById('lvEditReason').value = d.reason || '';
    document.getElementById('lvEditDeductAmount').value = d.deduction_amount || '';
    if (d.deduction_currency) document.getElementById('lvEditDeductCurrency').value = d.deduction_currency;

    document.getElementById('lvEditHourly').checked = !!d.is_hourly;
    lvSyncHourly();

    new bootstrap.Modal(document.getElementById('leaveEditModal')).show();
}

function lvSyncHourly() {
    var hourly = document.getElementById('lvEditHourly').checked;
    document.getElementById('lvEditEndCol').classList.toggle('d-none', hourly);
    document.getElementById('lvEditFromCol').classList.toggle('d-none', !hourly);
    document.getElementById('lvEditToCol').classList.toggle('d-none', !hourly);
    // Only require the fields that are actually visible
    document.getElementById('lvEditEnd').required = !hourly;
    document.getElementById('lvEditFrom').required = hourly;
    document.getElementById('lvEditTo').required = hourly;
}
document.getElementById('lvEditHourly').addEventListener('change', lvSyncHourly);
</script>
@endpush
@endsection
