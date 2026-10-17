@extends('company.dashboard')

@php
    $isAr = app()->getLocale() === 'ar';
    $filterBase = array_filter(['employee_id' => $employeeId, 'branch_id' => $branchId, 'month' => $month]);
    $activeFilters = collect([$employeeId, $branchId, $month])->filter()->count();
    $hasFilters = $activeFilters > 0 || $status;

    // Per-type presentation (icon + theme-aware tone) — emoji replaced by vector icons
    $typeUi = [
        'annual' => ['icon' => 'sun',          'tone' => 'var(--bk-accent)'],
        'sick'   => ['icon' => 'thermometer',  'tone' => 'var(--bk-warning)'],
        'unpaid' => ['icon' => 'minus-circle', 'tone' => 'var(--bk-info)'],
        'other'  => ['icon' => 'file-text',    'tone' => 'var(--bk-gold-strong)'],
    ];
    $statusUi = [
        'pending'  => ['icon' => 'clock',        'label' => __('Pending')],
        'approved' => ['icon' => 'check-circle', 'label' => __('Approved')],
        'rejected' => ['icon' => 'x-circle',     'label' => __('Rejected')],
    ];
    $createUrlTpl = route('company.employee-leaves.create', ['employee' => '__ID__']);
@endphp

@push('company-styles')
<style>
/* ── Leaves index ─────────────────────────────────────────────────────── */
.lvi-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
.lvi-title { font-size:24px; font-weight:700; line-height:1.2; margin:0 0 4px; color:var(--bk-text); }
.lvi-sub { font-size:13.5px; color:var(--bk-text-muted); margin:0; }
.lvi-add {
    display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:0 20px;
    border:0; border-radius:12px; cursor:pointer; font-size:14px; font-weight:700; font-family:inherit; text-decoration:none;
    background:var(--bk-accent-fill); color:var(--bk-accent-ink);
    box-shadow:0 6px 16px color-mix(in srgb, var(--bk-accent-fill) 26%, transparent);
    transition:background .15s, transform .15s;
}
.lvi-add:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); transform:translateY(-1px); }
.lvi-add i, .lvi-add svg { width:17px; height:17px; }
@media (max-width: 575.98px) { .lvi-add { width:100%; } .lvi-title { font-size:22px; } }

/* Status tabs (scroll sideways on phones) */
.lvi-tabs { display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; margin:0 -2px 14px; padding:2px; -webkit-overflow-scrolling:touch; }
.lvi-tabs::-webkit-scrollbar { display:none; }
.lvi-tab {
    flex:0 0 auto; display:inline-flex; align-items:center; gap:6px; min-height:42px; padding:0 10px 0 12px; border-radius:999px;
    font-size:13.5px; font-weight:600; text-decoration:none; white-space:nowrap;
    color:var(--bk-text-soft); background:var(--bk-surface); border:1px solid var(--bk-border-strong);
    transition:border-color .15s, background .15s, color .15s;
}
.lvi-tab:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.lvi-tab .n {
    min-width:24px; height:22px; padding:0 7px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; background:var(--bk-surface-2); color:var(--bk-text-soft); font-variant-numeric:tabular-nums;
}
.lvi-tab.is-active { background:var(--bk-accent-fill); border-color:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.lvi-tab.is-active .n { background:rgba(255,255,255,.2); color:var(--bk-accent-ink); }
.lvi-tab.is-attn:not(.is-active) .n { background:var(--bk-warning-bg); color:var(--bk-warning); }

/* Filters */
.lvi-filterbar { display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.lvi-ftoggle {
    display:inline-flex; align-items:center; gap:8px; min-height:42px; padding:0 14px; border-radius:11px; cursor:pointer;
    font-size:13.5px; font-weight:600; font-family:inherit; color:var(--bk-text-soft);
    background:var(--bk-surface); border:1px solid var(--bk-border-strong);
}
.lvi-ftoggle:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.lvi-ftoggle i, .lvi-ftoggle svg { width:16px; height:16px; }
.lvi-ftoggle .n { min-width:20px; height:20px; border-radius:999px; font-size:11.5px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; background:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.lvi-clear { font-size:13px; font-weight:600; color:var(--bk-text-muted); text-decoration:none; padding:8px 4px; }
.lvi-clear:hover { color:var(--bk-danger); }
.lvi-fpanel { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:16px; margin-bottom:14px; box-shadow:var(--bk-shadow); }
.lvi-fgrid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
@media (max-width: 767.98px) { .lvi-fgrid { grid-template-columns:minmax(0,1fr); } }
.lvi-flabel { display:block; font-size:12.5px; font-weight:600; color:var(--bk-text-muted); margin-bottom:5px; }
.lvi-fhint { font-size:12px; line-height:1.5; color:var(--bk-text-muted); margin-top:6px; }
.lvi-fhint a { font-weight:700; color:var(--bk-accent); text-decoration:none; margin-inline-start:6px; }
.lvi-fhint a:hover { text-decoration:underline; }
/* Month picker */
.lvi-mp { position:relative; }
.lvi-mp-btn { display:flex; align-items:center; gap:10px; text-align:start; cursor:pointer; }
.lvi-mp-btn i, .lvi-mp-btn svg { width:16px; height:16px; flex-shrink:0; color:var(--bk-text-muted); }
.lvi-mp-label { flex:1; font-weight:600; }
.lvi-mp-label.is-empty { font-weight:400; color:var(--bk-text-muted); }
.lvi-mp-btn[aria-expanded="true"] .lvi-mp-caret { transform:rotate(180deg); }
.lvi-mp-pop {
    position:absolute; z-index:30; top:calc(100% + 6px); inset-inline-start:0; width:min(300px, calc(100vw - 32px));
    padding:12px; background:var(--bk-surface); border:1px solid var(--bk-border-strong); border-radius:14px; box-shadow:var(--bk-shadow-lg);
}
@media (max-width: 767.98px) { .lvi-mp-pop { position:static; width:auto; margin-top:8px; box-shadow:none; } }
.lvi-mp-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.lvi-mp-year { font-size:15px; font-weight:700; color:var(--bk-text); font-variant-numeric:tabular-nums; }
.lvi-mp-nav { width:40px; height:40px; border-radius:10px; border:1px solid var(--bk-border-strong); background:transparent; color:var(--bk-text-soft); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; }
.lvi-mp-nav:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.lvi-mp-nav i, .lvi-mp-nav svg { width:18px; height:18px; }
[dir="rtl"] .lvi-mp-nav i, [dir="rtl"] .lvi-mp-nav svg { transform:scaleX(-1); }
.lvi-mp-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:6px; }
.lvi-mp-m { min-height:44px; border-radius:10px; border:1px solid transparent; background:var(--bk-surface-2); color:var(--bk-text); font-size:14px; font-weight:600; font-family:inherit; cursor:pointer; }
.lvi-mp-m:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.lvi-mp-m.is-now { border-color:var(--bk-gold-strong); }
.lvi-mp-m.is-sel { background:var(--bk-accent-fill); color:var(--bk-accent-ink); border-color:var(--bk-accent-fill); }
.lvi-mp-foot { display:flex; justify-content:space-between; margin-top:10px; padding-top:8px; border-top:1px solid var(--bk-border); }
.lvi-mp-link { background:none; border:0; padding:8px 4px; min-height:40px; font-size:13px; font-weight:700; color:var(--bk-accent); cursor:pointer; font-family:inherit; }
.lvi-mp-link:hover { text-decoration:underline; }
.lvi-mp-btn:focus-visible, .lvi-mp-m:focus-visible, .lvi-mp-nav:focus-visible, .lvi-mp-link:focus-visible { outline:3px solid color-mix(in srgb, var(--bk-accent) 40%, transparent); outline-offset:2px; }
.lvi-fctl {
    width:100%; min-height:44px; padding:8px 12px; font-size:14.5px; font-family:inherit; color:var(--bk-text);
    background:var(--bk-bg); border:1px solid var(--bk-border-strong); border-radius:10px; outline:none;
}
.lvi-fctl:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px color-mix(in srgb, var(--bk-accent) 18%, transparent); }

/* Leave cards */
.lvi-list { display:flex; flex-direction:column; gap:12px; }
.lvi-card {
    --tc: var(--bk-accent);
    position:relative; display:flex; align-items:center; gap:16px; padding:16px 18px;
    background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:16px; box-shadow:var(--bk-shadow);
    border-inline-start:4px solid var(--tc);
    transition:box-shadow .2s, border-color .2s;
}
.lvi-card:hover { box-shadow:var(--bk-shadow-lg); }
.lvi-card.is-rejected { opacity:.82; }
.lvi-main { display:flex; align-items:flex-start; gap:14px; flex:1 1 auto; min-width:0; }
.lvi-avatar {
    width:46px; height:46px; border-radius:50%; flex-shrink:0; overflow:hidden; position:relative;
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:17px;
    background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid var(--bk-border);
}
.lvi-avatar img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.lvi-body { min-width:0; flex:1; }
.lvi-top { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.lvi-name { font-size:15.5px; font-weight:700; color:var(--bk-text); text-decoration:none; overflow-wrap:anywhere; }
.lvi-name:hover { color:var(--bk-accent); }

.lvi-status { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:700; padding:4px 10px; border-radius:999px; flex-shrink:0; }
.lvi-status i, .lvi-status svg { width:13px; height:13px; }
.lvi-status.s-pending  { background:var(--bk-warning-bg); color:var(--bk-warning); }
.lvi-status.s-approved { background:var(--bk-success-bg); color:var(--bk-success); }
.lvi-status.s-rejected { background:var(--bk-danger-bg);  color:var(--bk-danger); }

.lvi-meta { display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:8px; }
.lvi-type { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--tc); }
.lvi-type i, .lvi-type svg { width:15px; height:15px; }
.lvi-dot { width:3px; height:3px; border-radius:50%; background:var(--bk-border-strong); }
.lvi-dur { font-size:12.5px; font-weight:700; padding:3px 10px; border-radius:8px; background:var(--bk-surface-2); color:var(--bk-text-soft); font-variant-numeric:tabular-nums; }
.lvi-dates { display:flex; align-items:center; gap:6px; margin-top:8px; font-size:13.5px; color:var(--bk-text-soft); flex-wrap:wrap; }
.lvi-dates i, .lvi-dates svg { width:14px; height:14px; color:var(--bk-text-muted); flex-shrink:0; }
.lvi-time { font-variant-numeric:tabular-nums; direction:ltr; unicode-bidi:isolate; }
.lvi-reason { display:flex; gap:6px; margin-top:6px; font-size:13px; color:var(--bk-text-muted); line-height:1.5; }
.lvi-reason i, .lvi-reason svg { width:14px; height:14px; flex-shrink:0; margin-top:3px; }
.lvi-warn { display:inline-flex; align-items:center; gap:6px; margin-top:8px; font-size:12.5px; font-weight:600; padding:4px 10px; border-radius:8px; background:var(--bk-warning-bg); color:var(--bk-warning); }
.lvi-warn i, .lvi-warn svg { width:14px; height:14px; }

.lvi-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }
.lvi-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:44px; padding:0 16px; border-radius:11px;
    font-size:13.5px; font-weight:700; font-family:inherit; cursor:pointer; border:1px solid transparent; transition:background .15s, border-color .15s, color .15s;
}
.lvi-btn i, .lvi-btn svg { width:16px; height:16px; }
.lvi-btn.approve { background:var(--bk-success-bg); color:var(--bk-success); border-color:color-mix(in srgb, var(--bk-success) 30%, transparent); }
.lvi-btn.approve:hover { background:var(--bk-success); color:#fff; }
.lvi-btn.reject { background:transparent; color:var(--bk-danger); border-color:color-mix(in srgb, var(--bk-danger) 35%, transparent); }
.lvi-btn.reject:hover { background:var(--bk-danger); color:#fff; }
.lvi-icon-btn {
    width:44px; height:44px; padding:0; border-radius:11px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
    background:transparent; color:var(--bk-text-muted); border:1px solid var(--bk-border-strong); transition:border-color .15s, color .15s, background .15s;
}
.lvi-icon-btn i, .lvi-icon-btn svg { width:17px; height:17px; }
.lvi-icon-btn:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.lvi-icon-btn.del:hover { border-color:var(--bk-danger); color:var(--bk-danger); background:var(--bk-danger-bg); }
.lvi-btn:focus-visible, .lvi-icon-btn:focus-visible, .lvi-tab:focus-visible, .lvi-ftoggle:focus-visible { outline:3px solid color-mix(in srgb, var(--bk-accent) 40%, transparent); outline-offset:2px; }

/* Phones: stack the card, full-width thumb-sized actions */
@media (max-width: 767.98px) {
    .lvi-card { flex-direction:column; align-items:stretch; gap:14px; padding:14px; }
    .lvi-actions { width:100%; }
    .lvi-actions form { flex:1; }
    .lvi-actions form .lvi-btn { width:100%; }
    .lvi-actions .lvi-spacer { flex:1 1 auto; }
    .lvi-avatar { width:42px; height:42px; font-size:16px; }
}

/* Empty state */
.lvi-empty { display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center; padding:48px 20px; background:var(--bk-surface); border:1px dashed var(--bk-border-strong); border-radius:16px; }
.lvi-empty-ico { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.lvi-empty-ico i, .lvi-empty-ico svg { width:28px; height:28px; }
.lvi-empty h3 { font-size:17px; font-weight:700; margin:0; color:var(--bk-text); }
.lvi-empty p { font-size:14px; color:var(--bk-text-muted); margin:0; max-width:340px; }

/* Add-leave employee picker */
.lv-pick .modal-content { border-radius:18px; border:none; background:var(--bk-surface); color:var(--bk-text); }
.lv-pick .modal-header { border-bottom:1px solid var(--bk-border); padding:18px 22px 14px; }
.lv-pick .modal-title { font-size:17px; font-weight:700; }

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
.lv-modal .form-control, .lv-modal .form-select { border-radius: 10px; font-size: 14px; min-height: 42px; }
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
    border: none; border-radius: 10px; font-weight: 700; font-size: 14px; padding: 10px 26px; min-height: 44px;
    box-shadow: 0 4px 14px color-mix(in srgb, var(--bk-accent) 30%, transparent);
}
.lv-modal .btn-save:hover { background: var(--bk-accent-hover); color: var(--bk-accent-ink); }

@media (prefers-reduced-motion: reduce) { .lvi-card, .lvi-btn, .lvi-icon-btn, .lvi-tab, .lvi-add { transition:none !important; } }
</style>
@endpush

@section('content')
<div class="page-content">

    @include('company.partials.team-nav')

    {{-- Header --}}
    <div class="lvi-head bk-a1">
        <div>
            <h1 class="lvi-title">{{ __('Employee Leaves') }}</h1>
            <p class="lvi-sub">{{ __('Manage leave requests across all employees') }}</p>
        </div>
        @if($employeeId)
            <a href="{{ str_replace('__ID__', $employeeId, $createUrlTpl) }}" class="lvi-add">
                <i data-feather="plus"></i>{{ __('Add Leave') }}
            </a>
        @else
            <button type="button" class="lvi-add" data-bs-toggle="modal" data-bs-target="#leavePickModal">
                <i data-feather="plus"></i>{{ __('Add Leave') }}
            </button>
        @endif
    </div>

    @include('company.partials.flash')

    {{-- Status tabs (server-side) --}}
    <nav class="lvi-tabs bk-a2" aria-label="{{ __('Status') }}">
        @foreach([
            ['key' => null,       'label' => __('All'),      'n' => $counts['all']],
            ['key' => 'pending',  'label' => __('Pending'),  'n' => $counts['pending']],
            ['key' => 'approved', 'label' => __('Approved'), 'n' => $counts['approved']],
            ['key' => 'rejected', 'label' => __('Rejected'), 'n' => $counts['rejected']],
        ] as $tab)
            <a href="{{ route('company.employee-leaves.index', $filterBase + ($tab['key'] ? ['status' => $tab['key']] : [])) }}"
               class="lvi-tab {{ $status === $tab['key'] ? 'is-active' : '' }} {{ $tab['key'] === 'pending' && $tab['n'] > 0 ? 'is-attn' : '' }}"
               @if($status === $tab['key']) aria-current="page" @endif>
                {{ $tab['label'] }}<span class="n">{{ $tab['n'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Filters --}}
    <div class="lvi-filterbar bk-a2">
        <button type="button" class="lvi-ftoggle" id="lviFToggle" aria-expanded="{{ $activeFilters ? 'true' : 'false' }}" aria-controls="lviFPanel">
            <i data-feather="sliders"></i>{{ __('Filters') }}
            @if($activeFilters)<span class="n">{{ $activeFilters }}</span>@endif
        </button>
        @if($hasFilters)
            <a href="{{ route('company.employee-leaves.index') }}" class="lvi-clear">{{ __('Clear all') }}</a>
        @endif
    </div>
    <form method="GET" action="{{ route('company.employee-leaves.index') }}" class="lvi-fpanel" id="lviFPanel" {{ $activeFilters ? '' : 'hidden' }}>
        <div class="lvi-fgrid">
            <div>
                <label class="lvi-flabel" for="f-emp">{{ __('Employee') }}</label>
                <select name="employee_id" id="f-emp" class="lvi-fctl" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($employeeList as $e)
                        <option value="{{ $e->id }}" {{ $employeeId == $e->id ? 'selected' : '' }}>{{ $e->localizedName() }}</option>
                    @endforeach
                </select>
            </div>
            @if(! ($branchContext ?? null))
            <div>
                <label class="lvi-flabel" for="f-branch">{{ __('Branch') }}</label>
                <select name="branch_id" id="f-branch" class="lvi-fctl" onchange="this.form.submit()">
                    <option value="">{{ __('All branches') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->localizedName() }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="lvi-flabel" for="f-month">{{ __('Leaves during month') }}</label>
                {{-- Tap-to-pick month grid (no typing) → submits YYYY-MM like the old <input type="month"> --}}
                <div class="lvi-mp" id="lviMP" data-value="{{ $month }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="button" class="lvi-fctl lvi-mp-btn" id="f-month" aria-haspopup="dialog" aria-expanded="false" aria-describedby="f-month-hint">
                        <i data-feather="calendar"></i>
                        <span class="lvi-mp-label" data-empty="{{ __('Choose month') }}">{{ $month ? '' : __('Choose month') }}</span>
                        <i data-feather="chevron-down" class="lvi-mp-caret"></i>
                    </button>
                    <div class="lvi-mp-pop" id="lviMPPop" role="dialog" aria-label="{{ __('Choose month') }}" hidden>
                        <div class="lvi-mp-head">
                            <button type="button" class="lvi-mp-nav" data-dir="-1" aria-label="{{ __('Previous year') }}"><i data-feather="chevron-left"></i></button>
                            <strong class="lvi-mp-year" id="lviMPYear"></strong>
                            <button type="button" class="lvi-mp-nav" data-dir="1" aria-label="{{ __('Next year') }}"><i data-feather="chevron-right"></i></button>
                        </div>
                        <div class="lvi-mp-grid" id="lviMPGrid"></div>
                        <div class="lvi-mp-foot">
                            <button type="button" class="lvi-mp-link" id="lviMPNow">{{ __('This month') }}</button>
                        </div>
                    </div>
                </div>
                <div class="lvi-fhint" id="f-month-hint">
                    {{ __('Shows leaves that fall fully or partly in the selected month.') }}
                    @if($month)
                        <a href="{{ route('company.employee-leaves.index', array_filter(['employee_id' => $employeeId, 'branch_id' => $branchId, 'status' => $status])) }}">{{ __('Clear') }}</a>
                    @endif
                </div>
            </div>
        </div>
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    </form>

    {{-- Cards --}}
    <div class="lvi-list bk-a3" id="lvList">
        @forelse($leaves as $leave)
            @php
                $emp       = $leave->employee;
                $ui        = $typeUi[$leave->type] ?? $typeUi['other'];
                $stUi      = $statusUi[$leave->status] ?? $statusUi['pending'];
                $initial   = mb_strtoupper(mb_substr($isAr ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar), 0, 1));
                $remaining = $balances[$leave->employee_id] ?? null;
                $overBalance = $leave->status === 'pending'
                    && $leave->type === 'annual'
                    && ! $leave->is_hourly
                    && $remaining !== null
                    && $leave->daysCount() > $remaining;
                $sameDay  = $leave->start_date->isSameDay($leave->end_date);
                $sameYear = $leave->start_date->year === $leave->end_date->year;
                // Built here (not inline in @json) because Blade's directive
                // parser mis-compiles a multi-line array literal with nested
                // casts/calls, producing invalid PHP.
                $leaveEditData = [
                    "id"          => $leave->id,
                    "name"        => $emp->localizedName(),
                    "type"        => $leave->type,
                    "is_hourly"   => (bool) $leave->is_hourly,
                    "start_date"  => $leave->start_date->toDateString(),
                    "end_date"    => $leave->end_date->toDateString(),
                    "start_hour"  => $leave->start_hour ? substr($leave->start_hour, 0, 5) : "",
                    "end_hour"    => $leave->end_hour ? substr($leave->end_hour, 0, 5) : "",
                    "reason"      => $leave->reason ?? "",
                    "deduction_amount"   => $leave->deduction_amount ? (float) $leave->deduction_amount : "",
                    "deduction_currency" => $leave->deduction_currency ?? ($emp->compensation?->currency ?? config("booksy.default_currency", "SYP")),
                    "status"      => $leave->status,
                ];
            @endphp
            <article class="lvi-card is-{{ $leave->status }}" data-status="{{ $leave->status }}" style="--tc: {{ $ui['tone'] }};">
                <div class="lvi-main">
                    <div class="lvi-avatar" aria-hidden="true">{{ $initial }}
                        @if($emp->image)
                            <img src="{{ asset('storage/'.$emp->image) }}" alt="" loading="lazy" onerror="this.remove()">
                        @endif
                    </div>

                    <div class="lvi-body">
                        <div class="lvi-top">
                            <a href="{{ route('company.employees.show', $emp) }}" class="lvi-name">{{ $emp->localizedName() }}</a>
                            <span class="lvi-status s-{{ $leave->status }}"><i data-feather="{{ $stUi['icon'] }}"></i>{{ $stUi['label'] }}</span>
                        </div>

                        <div class="lvi-meta">
                            <span class="lvi-type"><i data-feather="{{ $ui['icon'] }}"></i>{{ __($leave->typeMeta()['label_key']) }}</span>
                            <span class="lvi-dot" aria-hidden="true"></span>
                            @if($leave->is_hourly)
                                <span class="lvi-dur">{{ $leave->hoursCount() }} {{ __('hour(s)') }}</span>
                            @else
                                <span class="lvi-dur">{{ $leave->daysCount() }} {{ __('day(s)') }}</span>
                            @endif
                        </div>

                        <div class="lvi-dates">
                            <i data-feather="calendar"></i>
                            @if($leave->is_hourly)
                                <span>{{ $leave->start_date->translatedFormat('D d M Y') }}</span>
                                <span class="lvi-time">{{ substr($leave->start_hour, 0, 5) }} – {{ substr($leave->end_hour, 0, 5) }}</span>
                            @elseif($sameDay)
                                <span>{{ $leave->start_date->translatedFormat('D d M Y') }}</span>
                            @else
                                <span>{{ $leave->start_date->translatedFormat($sameYear ? 'D d M' : 'D d M Y') }}</span>
                                <span aria-hidden="true">{{ $isAr ? '←' : '→' }}</span>
                                <span>{{ $leave->end_date->translatedFormat('D d M Y') }}</span>
                            @endif
                        </div>

                        @if($leave->reason)
                            <div class="lvi-reason"><i data-feather="message-circle"></i><span>{{ $leave->reason }}</span></div>
                        @endif

                        @if($overBalance)
                            <div class="lvi-warn" title="{{ __('Remaining balance') }}: {{ $remaining }} {{ __('day(s)') }}">
                                <i data-feather="alert-triangle"></i>{{ __('Exceeds balance') }} ({{ $remaining }})
                            </div>
                        @endif
                    </div>
                </div>

                <div class="lvi-actions">
                    @if($leave->status === 'pending')
                        <form method="POST" action="{{ route('company.employee-leaves.update-status', $leave) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="approved">
                            <button class="lvi-btn approve"><i data-feather="check"></i>{{ __('Approve') }}</button>
                        </form>
                        <form method="POST" action="{{ route('company.employee-leaves.update-status', $leave) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="rejected">
                            <button class="lvi-btn reject"><i data-feather="x"></i>{{ __('Reject') }}</button>
                        </form>
                    @else
                        <span class="lvi-spacer"></span>
                    @endif
                    <button type="button" class="lvi-icon-btn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"
                            onclick='openLeaveEdit(@json($leaveEditData, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT))'>
                        <i data-feather="edit-2"></i>
                    </button>
                    <button type="button" class="lvi-icon-btn del" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"
                            onclick="bkConfirmDelete('{{ route('company.employee-leaves.destroy', $leave) }}', '{{ addslashes($emp->localizedName()) }} — {{ $leave->start_date->format('d/m') }} → {{ $leave->end_date->format('d/m') }}', '{{ __('Delete this leave request?') }}')">
                        <i data-feather="trash-2"></i>
                    </button>
                </div>
            </article>
        @empty
            <div class="lvi-empty">
                <div class="lvi-empty-ico"><i data-feather="calendar"></i></div>
                <h3>{{ $hasFilters ? __('No leave requests match your filters.') : __('No leave requests yet.') }}</h3>
                @unless($hasFilters)
                    <p>{{ __('Record a leave for an employee and it will appear here.') }}</p>
                    <button type="button" class="lvi-add" data-bs-toggle="modal" data-bs-target="#leavePickModal">
                        <i data-feather="plus"></i>{{ __('Add Leave') }}
                    </button>
                @else
                    <a href="{{ route('company.employee-leaves.index') }}" class="lvi-clear">{{ __('Clear all') }}</a>
                @endunless
            </div>
        @endforelse
    </div>

    @if($leaves->hasPages())
        <div class="mt-3">{{ $leaves->links() }}</div>
    @endif

    @include('company.partials.confirm-delete-modal')

    {{-- Pick the employee, then go to the add-leave form --}}
    <div class="modal fade lv-pick" id="leavePickModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">{{ __('Who is taking leave?') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="lvi-flabel" for="pickEmp">{{ __('Employee') }}</label>
                    <select id="pickEmp" class="lvi-fctl">
                        <option value="">{{ __('Choose an employee') }}</option>
                        @foreach($employeeList as $e)
                            <option value="{{ $e->id }}">{{ $e->localizedName() }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="lvi-add mt-3 w-100" id="pickGo" disabled>
                        {{ __('Continue') }}<i data-feather="{{ $isAr ? 'arrow-left' : 'arrow-right' }}"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit leave modal --}}
    <div class="modal fade lv-modal" id="leaveEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px;">
            <div class="modal-content">
                <form method="POST" id="leaveEditForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold mb-0">
                            <i data-feather="edit-2" style="width:15px;height:15px;" class="{{ $isAr ? 'ms-1' : 'me-1' }}"></i>
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
                                        <option value="{{ $code }}">{{ $cur['symbol'] }} {{ $isAr ? $cur['name_ar'] : $cur['name_en'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="tx-11 mt-2" style="opacity:.65;">{{ __('The deduction is recorded on the employee only when the leave is approved, and appears in payroll.') }}</div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-sm rounded-3 px-4" style="background:var(--bk-surface-2);color:var(--bk-text-soft);font-weight:600;min-height:44px;" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
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

// Filters disclosure
(function () {
    var t = document.getElementById('lviFToggle'), p = document.getElementById('lviFPanel');
    if (t && p) t.addEventListener('click', function () {
        var open = t.getAttribute('aria-expanded') === 'true';
        t.setAttribute('aria-expanded', String(!open));
        p.hidden = open;
    });

    // Month picker: choose a month from a grid, no typing
    var mp = document.getElementById('lviMP');
    if (mp) {
        var loc = @json($isAr ? 'ar-u-ca-gregory-nu-latn' : 'en-GB');
        var hidden = mp.querySelector('input[name="month"]');
        var btn = document.getElementById('f-month'), pop = document.getElementById('lviMPPop');
        var grid = document.getElementById('lviMPGrid'), yearEl = document.getElementById('lviMPYear');
        var label = mp.querySelector('.lvi-mp-label');
        var now = new Date(), nowKey = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
        var msel = hidden.value || '';
        var year = msel ? +msel.slice(0, 4) : now.getFullYear();
        var monthName = function (y, m) { return new Date(y, m, 1).toLocaleDateString(loc, { month: 'long' }); };

        if (msel) label.textContent = monthName(+msel.slice(0, 4), +msel.slice(5) - 1) + ' ' + msel.slice(0, 4);
        else { label.textContent = label.dataset.empty; label.classList.add('is-empty'); }

        function render() {
            yearEl.textContent = year;
            grid.innerHTML = '';
            for (var m = 0; m < 12; m++) {
                var key = year + '-' + String(m + 1).padStart(2, '0');
                var b = document.createElement('button');
                b.type = 'button'; b.className = 'lvi-mp-m' + (key === msel ? ' is-msel' : '') + (key === nowKey ? ' is-now' : '');
                b.textContent = monthName(year, m); b.dataset.key = key;
                if (key === msel) b.setAttribute('aria-pressed', 'true');
                grid.appendChild(b);
            }
        }
        function setOpen(o) { pop.hidden = !o; btn.setAttribute('aria-expanded', String(o)); if (o) render(); }
        function pick(key) { hidden.value = key; btn.closest('form').submit(); }

        btn.addEventListener('click', function () { setOpen(pop.hidden); });
        pop.querySelectorAll('.lvi-mp-nav').forEach(function (n) {
            n.addEventListener('click', function () { year += +n.dataset.dir; render(); });
        });
        grid.addEventListener('click', function (e) { var t = e.target.closest('.lvi-mp-m'); if (t) pick(t.dataset.key); });
        document.getElementById('lviMPNow').addEventListener('click', function () { pick(nowKey); });
        document.addEventListener('click', function (e) { if (!pop.hidden && !mp.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !pop.hidden) { setOpen(false); btn.focus(); } });
    }

    // Employee picker → add-leave form
    var sel = document.getElementById('pickEmp'), go = document.getElementById('pickGo');
    var tpl = @json($createUrlTpl);
    if (sel && go) {
        sel.addEventListener('change', function () { go.disabled = !sel.value; });
        go.addEventListener('click', function () { if (sel.value) location.href = tpl.replace('__ID__', sel.value); });
    }
})();
</script>
@endpush
@endsection
