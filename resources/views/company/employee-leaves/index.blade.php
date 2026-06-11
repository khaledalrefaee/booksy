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

    {{-- Hero --}}
    <div class="leaves-hero bk-a1">
        <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">{{ __('Employee Leaves') }}</h3>
                <p class="mb-0" style="color:rgba(255,255,255,.65); font-size:13px;">{{ __('Manage leave requests across all employees') }}</p>
            </div>
            <div class="d-flex gap-2">
                <div class="stat-chip">
                    <div class="num">{{ $leaves->where('status','pending')->count() }}</div>
                    <div class="lbl">{{ __('Pending') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="num">{{ $leaves->where('status','approved')->count() }}</div>
                    <div class="lbl">{{ __('Approved') }}</div>
                </div>
                <div class="stat-chip">
                    <div class="num">{{ $leaves->where('status','rejected')->count() }}</div>
                    <div class="lbl">{{ __('Rejected') }}</div>
                </div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- Tabs --}}
    <div class="lv-tabs bk-a2" id="lvTabs">
        <button class="lv-tab active" data-filter="all">{{ __('All') }} ({{ $leaves->count() }})</button>
        <button class="lv-tab" data-filter="pending">{{ __('Pending') }} ({{ $leaves->where('status','pending')->count() }})</button>
        <button class="lv-tab" data-filter="approved">{{ __('Approved') }} ({{ $leaves->where('status','approved')->count() }})</button>
        <button class="lv-tab" data-filter="rejected">{{ __('Rejected') }} ({{ $leaves->where('status','rejected')->count() }})</button>
    </div>

    {{-- Cards --}}
    <div class="card border-0 bk-a3" style="border-radius:18px !important; overflow:hidden;">
        <div class="card-body p-0" id="lvList">
            @forelse($leaves as $leave)
            @php
                $palette = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a'];
                $bg = $palette[$leave->employee_id % count($palette)];
                $initial = strtoupper(mb_substr($leave->employee->name_en ?? $leave->employee->name_ar ?? '?', 0, 1));
            @endphp
            <div class="lv-card" data-status="{{ $leave->status }}">
                <div class="lv-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">{{ $initial }}</div>

                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <span class="lv-name">{{ $leave->employee->localizedName() }}</span>
                        <span class="lv-badge lv-badge-{{ $leave->status }}">
                            @if($leave->status==='approved') ✓ {{ __('Approved') }}
                            @elseif($leave->status==='rejected') ✗ {{ __('Rejected') }}
                            @else ⏳ {{ __('Pending') }}
                            @endif
                        </span>
                        <span class="days-pill">{{ $leave->daysCount() }} {{ __('day(s)') }}</span>
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
                    <form method="POST" action="{{ route('company.employee-leaves.destroy', $leave) }}"
                          onsubmit="return confirm('{{ __('Delete this leave request?') }}')">
                        @csrf @method('DELETE')
                        <button class="btn-lv btn-lv-del">
                            <i data-feather="trash-2" style="width:11px;height:11px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="bk-empty-lv">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p>{{ __('No leave requests yet.') }}</p>
            </div>
            @endforelse
        </div>
    </div>

</div>
@push('scripts')
<script>
document.querySelectorAll('#lvTabs .lv-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('#lvTabs .lv-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const f = this.dataset.filter;
        document.querySelectorAll('#lvList .lv-card').forEach(c => {
            c.style.display = (f === 'all' || c.dataset.status === f) ? 'flex' : 'none';
        });
    });
});
</script>
@endpush
@endsection
