@extends('owner.dashboard')

@push('owner-styles')
<style>
.leaves-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #fff;
    position: relative; overflow: hidden;
}
.leaves-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.1); pointer-events: none;
}
[dir="rtl"] .leaves-hero::before { right: auto; left: -50px; }

.stat-chip {
    background: rgba(255,255,255,.15); border-radius: 50px;
    padding: 5px 14px; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 6px;
    backdrop-filter: blur(4px);
}
.stat-chip .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

.filter-tabs {
    display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px;
}
.filter-tab {
    border: 1.5px solid rgba(255,255,255,.1); background: rgba(255,255,255,.03);
    border-radius: 50px; padding: 5px 16px; font-size: 12px; font-weight: 600;
    cursor: pointer; color: rgba(255,255,255,.5); transition: all .18s;
}
.bk-theme-light .filter-tab { border-color: #dee2e6; background: transparent; color: rgba(0,0,0,.4); }
.filter-tab.active { background: rgba(240,147,251,.15); border-color: rgba(240,147,251,.5); color: #f093fb; }
.bk-theme-light .filter-tab.active { background: rgba(240,147,251,.08); border-color: #f093fb; color: #c0209a; }

.leave-card {
    border-bottom: 1px solid rgba(255,255,255,.05);
    padding: 16px 20px; transition: background .18s;
}
.bk-theme-light .leave-card { border-bottom-color: rgba(0,0,0,.05); }
.leave-card:last-child { border-bottom: none; }
.leave-card:hover { background: rgba(255,255,255,.02); }
.bk-theme-light .leave-card:hover { background: rgba(0,0,0,.01); }

.leave-avatar {
    width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; color: #fff;
}
.leave-emp-name  { font-weight: 600; font-size: 13px; }
.leave-emp-meta  { font-size: 11px; color: rgba(255,255,255,.4); }
.bk-theme-light .leave-emp-meta { color: rgba(0,0,0,.4); }

.days-badge {
    border-radius: 8px; padding: 3px 9px; font-size: 11px; font-weight: 700;
    background: rgba(255,255,255,.07); flex-shrink: 0;
}
.bk-theme-light .days-badge { background: rgba(0,0,0,.05); }

.status-badge {
    border-radius: 50px; padding: 3px 11px; font-size: 11px; font-weight: 700;
}
.status-pending  { background: rgba(255,193,7,.15); color: #ffc107; }
.status-approved { background: rgba(67,233,122,.15); color: #43e97b; }
.status-rejected { background: rgba(245,87,108,.15); color: #f5576c; }
.bk-theme-light .status-pending  { background: rgba(255,193,7,.12); color: #b08700; }
.bk-theme-light .status-approved { background: rgba(25,135,84,.1);  color: #198754; }
.bk-theme-light .status-rejected { background: rgba(220,53,69,.12);  color: #dc3545; }

.action-row {
    display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 10px;
}
.btn-approve {
    border: none; background: rgba(67,233,122,.12); color: #43e97b;
    border-radius: 8px; padding: 4px 13px; font-size: 11px; font-weight: 700;
    cursor: pointer; transition: background .18s;
}
.btn-approve:hover { background: rgba(67,233,122,.22); }
.bk-theme-light .btn-approve { background: rgba(25,135,84,.08); color: #198754; }
.btn-reject {
    border: none; background: rgba(245,87,108,.1); color: #f5576c;
    border-radius: 8px; padding: 4px 13px; font-size: 11px; font-weight: 700;
    cursor: pointer; transition: background .18s;
}
.btn-reject:hover { background: rgba(245,87,108,.2); }
.bk-theme-light .btn-reject { background: rgba(220,53,69,.07); color: #dc3545; }
.btn-del {
    border: 1.5px solid rgba(255,255,255,.1); background: transparent;
    border-radius: 8px; padding: 4px 10px; font-size: 11px; color: rgba(255,255,255,.3);
    cursor: pointer; transition: all .18s;
}
.btn-del:hover { border-color: #f5576c; color: #f5576c; }
.bk-theme-light .btn-del { border-color: rgba(0,0,0,.12); color: rgba(0,0,0,.3); }
.bk-theme-light .btn-del:hover { border-color: #dc3545; color: #dc3545; }

.notes-input {
    border: 1.5px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04);
    border-radius: 8px; padding: 4px 10px; font-size: 11px;
    color: inherit; outline: none; flex: 1; min-width: 120px;
}
.notes-input:focus { border-color: #f093fb; }
.bk-theme-light .notes-input { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .notes-input:focus { border-color: #c0209a; }

.empty-leaves { display: flex; flex-direction: column; align-items: center; padding: 60px 20px; gap: 12px; }
.empty-leaves svg { opacity: .15; }
.empty-leaves p { font-size: 14px; color: rgba(255,255,255,.4); margin: 0; }
.bk-theme-light .empty-leaves p { color: rgba(0,0,0,.4); }

.company-tag {
    background: rgba(201,162,39,.12); color: #c9a227; border-radius: 6px;
    padding: 1px 7px; font-size: 10px; font-weight: 700;
}
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="leaves-hero">
        <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Employee Leaves') }}</h3>
                <p class="mb-2" style="color:rgba(255,255,255,.7);font-size:13px;">{{ __('Manage leave requests across all employees') }}</p>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="stat-chip">
                        <span class="dot" style="background:#ffc107;"></span>
                        {{ $statsPending }} {{ __('Pending') }}
                    </span>
                    <span class="stat-chip">
                        <span class="dot" style="background:#43e97b;"></span>
                        {{ $statsApproved }} {{ __('Approved') }}
                    </span>
                    <span class="stat-chip">
                        <span class="dot" style="background:#f5576c;"></span>
                        {{ $statsRejected }} {{ __('Rejected') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @include('owner.partials.flash')

    @include('owner.partials._search-sort-bar', [
        'sortField'       => $sortField,
        'sortDir'         => $sortDir,
        'extraFilterKeys' => ['status'],
        'sortOptions'     => [
            ['field' => 'start_date', 'label' => __('تاريخ البداية')],
            ['field' => 'created_at', 'label' => __('تاريخ الإضافة')],
        ],
        'extraFilters' => '
            <select name="status" class="bk-ssb-select" style="min-width:130px;" onchange="document.getElementById(\'bk-sf-form\').submit()">
                <option value="">' . __('كل الحالات') . '</option>
                <option value="pending"  ' . ($filterStatus === 'pending'  ? 'selected' : '') . '>' . __('قيد الانتظار') . '</option>
                <option value="approved" ' . ($filterStatus === 'approved' ? 'selected' : '') . '>' . __('موافق عليه')   . '</option>
                <option value="rejected" ' . ($filterStatus === 'rejected' ? 'selected' : '') . '>' . __('مرفوض')        . '</option>
            </select>
        ',
    ])

    {{-- Client-side filter tabs (keep for quick visual toggle, but also server-side filtering above) --}}
    <div class="filter-tabs" id="filter-tabs">
        <button class="filter-tab {{ $filterStatus === '' ? 'active' : '' }}" data-filter="all">{{ __('All Leaves') }}</button>
        <button class="filter-tab {{ $filterStatus === 'pending'  ? 'active' : '' }}" data-filter="pending">{{ __('Pending') }}</button>
        <button class="filter-tab {{ $filterStatus === 'approved' ? 'active' : '' }}" data-filter="approved">{{ __('Approved') }}</button>
        <button class="filter-tab {{ $filterStatus === 'rejected' ? 'active' : '' }}" data-filter="rejected">{{ __('Rejected') }}</button>
    </div>

    <div class="card border-0 bk-a2" style="border-radius:18px !important; overflow:hidden;">
        <div class="card-body p-0">
            @forelse($leaves as $leave)
            @php
                $palette  = ['#c9a227','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1'];
                $bg       = $palette[$leave->employee_id % count($palette)];
                $initial  = strtoupper(mb_substr($leave->employee->name_en ?? $leave->employee->name_ar ?? '?', 0, 1));
                $locale   = app()->getLocale();
            @endphp
            <div class="leave-card" data-status="{{ $leave->status }}">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="leave-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">{{ $initial }}</div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="leave-emp-name">{{ $locale==='ar' ? ($leave->employee->name_ar ?: $leave->employee->name_en) : ($leave->employee->name_en ?: $leave->employee->name_ar) }}</span>
                            @if($leave->company)
                                <span class="company-tag">{{ $leave->company->localizedName() }}</span>
                            @endif
                            <span class="status-badge status-{{ $leave->status }}">
                                {{ __($leave->status === 'pending' ? 'Pending' : ($leave->status === 'approved' ? 'Approved' : 'Rejected')) }}
                            </span>
                        </div>
                        <div class="leave-emp-meta d-flex flex-wrap gap-3 mt-1">
                            <span>
                                <i data-feather="calendar" style="width:11px;height:11px;" class="{{ $locale==='ar' ? 'ms-1' : 'me-1' }}"></i>
                                {{ $leave->start_date->format('d M Y') }} → {{ $leave->end_date->format('d M Y') }}
                            </span>
                            <span>
                                <span class="days-badge">{{ $leave->daysCount() }} {{ __('day(s)') }}</span>
                            </span>
                        </div>
                        @if($leave->reason)
                        <div class="leave-emp-meta mt-1" style="font-size:12px;">{{ $leave->reason }}</div>
                        @endif
                        @if($leave->notes)
                        <div class="leave-emp-meta mt-1" style="font-size:11px;font-style:italic;">
                            <i data-feather="message-square" style="width:10px;height:10px;" class="{{ $locale==='ar' ? 'ms-1' : 'me-1' }}"></i>
                            {{ $leave->notes }}
                        </div>
                        @endif
                    </div>
                </div>

                @if($leave->status === 'pending')
                <div class="action-row">
                    <form method="post" action="{{ route('owner.employee-leaves.update-status', $leave) }}" class="d-flex align-items-center gap-2 flex-wrap">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <input type="text" name="notes" class="notes-input" placeholder="{{ __('Notes (optional)') }}">
                        <button type="submit" class="btn-approve">
                            <i data-feather="check" style="width:10px;height:10px;" class="{{ $locale==='ar' ? 'ms-1' : 'me-1' }}"></i>{{ __('Approve') }}
                        </button>
                    </form>
                    <form method="post" action="{{ route('owner.employee-leaves.update-status', $leave) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn-reject">
                            <i data-feather="x" style="width:10px;height:10px;" class="{{ $locale==='ar' ? 'ms-1' : 'me-1' }}"></i>{{ __('Reject') }}
                        </button>
                    </form>
                    <form method="post" action="{{ route('owner.employee-leaves.destroy', $leave) }}"
                          onsubmit="return confirm('{{ __('Delete this leave request?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-del">
                            <i data-feather="trash-2" style="width:10px;height:10px;"></i>
                        </button>
                    </form>
                </div>
                @else
                <div class="action-row" style="margin-top:8px;">
                    <form method="post" action="{{ route('owner.employee-leaves.destroy', $leave) }}"
                          onsubmit="return confirm('{{ __('Delete this leave request?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-del">
                            <i data-feather="trash-2" style="width:10px;height:10px;" class="{{ $locale==='ar' ? 'ms-1' : 'me-1' }}"></i>{{ __('Delete') }}
                        </button>
                    </form>
                </div>
                @endif
            </div>
            @empty
            <div class="empty-leaves">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p>{{ __('No leave requests yet.') }}</p>
            </div>
            @endforelse
        </div>
        @if($leaves->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $leaves->links() }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('#filter-tabs .filter-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        const filter = this.dataset.filter;
        // Navigate with server-side status filter
        const url = new URL(window.location.href);
        if (filter === 'all') {
            url.searchParams.delete('status');
        } else {
            url.searchParams.set('status', filter);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
});
</script>
@endpush
@endsection
