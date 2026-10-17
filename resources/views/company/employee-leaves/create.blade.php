@extends('company.dashboard')

@php
    $isAr      = app()->getLocale() === 'ar';
    $minDate   = \App\Http\Controllers\Company\EmployeeLeaveController::minLeaveDate()->toDateString();
    $maxDate   = \App\Http\Controllers\Company\EmployeeLeaveController::maxLeaveDate()->toDateString();
    $totalDays = (int) $employee->annual_leave_days;
    $dailyRate = $employee->dailyRate();
    $curDef    = old('deduction_currency', $employee->compensation?->currency ?? config('booksy.default_currency', 'SYP'));
    $isHourly  = (bool) old('is_hourly', false);
    $oldType   = old('type', 'annual');
    $hasDeduct = old('deduction_amount') !== null && (float) old('deduction_amount') > 0;

    // Per-type presentation (kept in the view so the shared model constant stays untouched)
    $typeUi = [
        'annual' => ['icon' => 'sun',          'color' => 'var(--bk-accent)',       'hint' => __('Uses the annual balance')],
        'sick'   => ['icon' => 'thermometer',  'color' => 'var(--bk-warning)',      'hint' => __('Medical absence')],
        'unpaid' => ['icon' => 'minus-circle', 'color' => 'var(--bk-info)',         'hint' => __('Deducted from salary')],
        'other'  => ['icon' => 'file-text',    'color' => 'var(--bk-gold-strong)',  'hint' => __('Any other reason')],
    ];
    $jsL = [
        'locale'   => $isAr ? 'ar-u-ca-gregory-nu-latn' : 'en-GB',
        'day'      => __('day(s)'),
        'hour'     => __('hour(s)'),
        'none'     => __('None'),
        'pick'     => __('Select dates to preview'),
        'badDates' => __('End date must be after start date'),
        'badTime'  => __('End time must be after start time.'),
        'over'     => __('This request exceeds the remaining annual balance'),
        'noBal'    => __('Hourly and non-annual leave does not use the annual balance.'),
        'auto'     => __('Calculated automatically from the daily rate'),
        'types'    => collect(\App\Models\EmployeeLeave::LEAVE_TYPES)->map(fn ($m) => __($m['label_key'])),
        ];
@endphp

@push('company-styles')
<style>
/* ── Add Leave (owner records → approved immediately) ─────────────────── */
.lvx { max-width: 1080px; margin-inline: auto; }

.lvx-top { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:22px; }
.lvx-who { display:flex; align-items:center; gap:14px; min-width:0; }
.lvx-avatar {
    width:52px; height:52px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent);
    font-weight:700; font-size:20px; border:1px solid var(--bk-border);
}
.lvx-eyebrow { font-size:12px; font-weight:600; letter-spacing:.04em; color:var(--bk-text-muted); margin-bottom:2px; }
.lvx-title { font-size:22px; font-weight:700; line-height:1.25; margin:0; color:var(--bk-text); overflow-wrap:anywhere; }
.lvx-back {
    display:inline-flex; align-items:center; gap:6px; min-height:40px; padding:0 14px;
    border:1px solid var(--bk-border-strong); border-radius:10px;
    font-size:13px; font-weight:600; color:var(--bk-text-soft); text-decoration:none;
    background:var(--bk-surface); transition:border-color .15s, color .15s;
}
.lvx-back:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
[dir="rtl"] .lvx-back i { transform:scaleX(-1); }

.lvx-grid { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:22px; align-items:start; }
@media (max-width: 1199.98px) { .lvx-grid { grid-template-columns:minmax(0,1fr); } }

/* Sections */
.lvx-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:16px; box-shadow:var(--bk-shadow); }
.lvx-sec { padding:22px 24px; }
.lvx-sec + .lvx-sec { border-top:1px solid var(--bk-border); }
.lvx-sec-head { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.lvx-step {
    width:24px; height:24px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-fill); color:var(--bk-accent-ink); font-size:12px; font-weight:700;
}
.lvx-sec-title { font-size:15px; font-weight:700; margin:0; color:var(--bk-text); }
.lvx-sec-sub { font-size:12.5px; color:var(--bk-text-muted); margin-inline-start:auto; }
@media (max-width: 575.98px) { .lvx-sec { padding:18px 16px; } .lvx-sec-sub { display:none; } }

.lvx-label { display:block; font-size:13px; font-weight:600; color:var(--bk-text-soft); margin-bottom:6px; }
.lvx-req { color:var(--bk-danger); }
.lvx-input, .lvx-select, .lvx-textarea {
    width:100%; min-height:46px; padding:10px 14px; font-size:15px; font-family:inherit; line-height:1.4;
    color:var(--bk-text); background:var(--bk-bg); border:1px solid var(--bk-border-strong);
    border-radius:11px; outline:none; transition:border-color .15s, box-shadow .15s, background .15s;
}
.lvx-textarea { resize:vertical; min-height:92px; }
.lvx-input:focus, .lvx-select:focus, .lvx-textarea:focus {
    border-color:var(--bk-accent); background:var(--bk-surface);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--bk-accent) 18%, transparent);
}
.lvx-input.is-invalid, .lvx-textarea.is-invalid, .lvx-select.is-invalid { border-color:var(--bk-danger); }
.lvx-err { font-size:12.5px; color:var(--bk-danger); margin-top:5px; }
.lvx-help { font-size:12.5px; color:var(--bk-text-muted); margin-top:6px; line-height:1.5; }

/* Type cards */
.lvx-sec { container-type:inline-size; }
.lvx-types { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
@container (min-width: 500px) { .lvx-types { grid-template-columns:repeat(4,minmax(0,1fr)); } }
@media (max-width: 575.98px) { .lvx-type-card { padding:12px; gap:8px; } .lvx-type-hint { font-size:11.5px; } }
.lvx-type { position:relative; margin:0; }
.lvx-type input { position:absolute; inset:0; opacity:0; cursor:pointer; margin:0; z-index:1; }
.lvx-type-card {
    height:100%; display:flex; flex-direction:column; align-items:flex-start; gap:10px;
    padding:14px; border:1px solid var(--bk-border-strong); border-radius:13px;
    background:var(--bk-bg); transition:border-color .15s, background .15s, box-shadow .15s;
}
.lvx-type:hover .lvx-type-card { border-color:var(--t); }
.lvx-type input:focus-visible + .lvx-type-card { outline:2px solid var(--t); outline-offset:2px; }
.lvx-type-ico {
    width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center;
    color:var(--t); background:color-mix(in srgb, var(--t) 13%, transparent);
}
.lvx-type-ico i, .lvx-type-ico svg { width:17px; height:17px; }
.lvx-type-name { font-size:14px; font-weight:700; color:var(--bk-text); line-height:1.2; }
.lvx-type-hint { font-size:12px; color:var(--bk-text-muted); line-height:1.35; margin-top:2px; }
.lvx-type.is-selected .lvx-type-card, .lvx-type input:checked + .lvx-type-card {
    border-color:var(--t); background:color-mix(in srgb, var(--t) 8%, var(--bk-surface));
    box-shadow:0 0 0 3px color-mix(in srgb, var(--t) 16%, transparent);
}

/* Segmented control */
.lvx-seg { display:inline-flex; padding:4px; gap:4px; border-radius:12px; background:var(--bk-surface-2); border:1px solid var(--bk-border); max-width:100%; }
.lvx-seg label { margin:0; position:relative; }
.lvx-seg input { position:absolute; inset:0; opacity:0; cursor:pointer; margin:0; }
.lvx-seg span {
    display:flex; align-items:center; gap:7px; min-height:38px; padding:0 16px; border-radius:9px;
    font-size:13.5px; font-weight:600; color:var(--bk-text-soft); transition:background .15s, color .15s, box-shadow .15s;
}
.lvx-seg span i, .lvx-seg span svg { width:15px; height:15px; }
.lvx-seg label.is-selected span, .lvx-seg input:checked + span {
    background:var(--bk-surface); color:var(--bk-accent); box-shadow:var(--bk-shadow);
}
.lvx-seg input:focus-visible + span { outline:2px solid var(--bk-accent); outline-offset:1px; }

/* Quick picks */
.lvx-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; align-items:center; }
.lvx-chips-label { font-size:12.5px; color:var(--bk-text-muted); margin-inline-end:2px; }
.lvx-chip {
    min-height:34px; padding:0 13px; border-radius:999px; font-size:13px; font-weight:600; cursor:pointer;
    background:transparent; color:var(--bk-text-soft); border:1px solid var(--bk-border-strong);
    transition:border-color .15s, color .15s, background .15s;
}
.lvx-chip:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.lvx-chip.is-on { background:var(--bk-accent-wash); border-color:var(--bk-accent); color:var(--bk-accent); }

/* Deduction disclosure */
.lvx-disclose {
    display:flex; align-items:center; gap:12px; width:100%; text-align:start; cursor:pointer;
    padding:12px 14px; border-radius:12px; border:1px dashed var(--bk-border-strong);
    background:transparent; color:var(--bk-text-soft); transition:border-color .15s, background .15s;
}
.lvx-disclose:hover { border-color:var(--bk-gold-strong); background:var(--bk-gold-soft); }
.lvx-disclose-ico { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; background:var(--bk-gold-soft); color:var(--bk-gold-strong); flex-shrink:0; }
.lvx-disclose-ico i, .lvx-disclose-ico svg { width:16px; height:16px; }
.lvx-disclose b { display:block; font-size:14px; color:var(--bk-text); }
.lvx-disclose small { display:block; font-size:12.5px; color:var(--bk-text-muted); }
.lvx-disclose .lvx-caret { margin-inline-start:auto; transition:transform .2s; width:16px; height:16px; }
.lvx-disclose[aria-expanded="true"] .lvx-caret { transform:rotate(180deg); }
.lvx-deduct-body { margin-top:14px; }

/* Summary sidebar */
.lvx-aside { position:sticky; top:84px; }
@media (max-width: 1199.98px) { .lvx-aside { position:static; } }
.lvx-sum-head { padding:18px 20px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.lvx-sum-title { font-size:15px; font-weight:700; margin:0; color:var(--bk-text); }
.lvx-approved {
    display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:700;
    padding:4px 10px; border-radius:999px; background:var(--bk-success-bg); color:var(--bk-success);
}
.lvx-approved i, .lvx-approved svg { width:13px; height:13px; }
.lvx-sum-body { padding:0 20px 18px; }
.lvx-row { display:flex; justify-content:space-between; align-items:baseline; gap:12px; padding:11px 0; border-top:1px solid var(--bk-border); font-size:14px; }
.lvx-row dt { font-weight:500; color:var(--bk-text-muted); margin:0; flex-shrink:0; }
.lvx-row dd { margin:0; font-weight:600; color:var(--bk-text); text-align:end; overflow-wrap:anywhere; }
.lvx-row dd.is-empty { color:var(--bk-text-muted); font-weight:400; }
.lvx-big { font-size:26px; font-weight:700; line-height:1; color:var(--bk-accent); font-variant-numeric:tabular-nums; }
.lvx-big small { font-size:13px; font-weight:600; color:var(--bk-text-muted); margin-inline-start:4px; }

.lvx-bal { padding:14px 0 4px; border-top:1px solid var(--bk-border); }
.lvx-bal-top { display:flex; justify-content:space-between; align-items:baseline; font-size:13px; color:var(--bk-text-muted); margin-bottom:9px; gap:8px; }
.lvx-bal-top b { color:var(--bk-text); font-size:14px; font-variant-numeric:tabular-nums; }
.lvx-bar { display:flex; height:9px; border-radius:6px; overflow:hidden; background:var(--bk-surface-2); border:1px solid var(--bk-border); }
.lvx-bar i { display:block; height:100%; transition:width .3s; }
.lvx-bar .b-used { background:var(--bk-border-strong); }
.lvx-bar .b-this { background:var(--bk-accent-fill); }
.lvx-bar.is-over .b-this { background:var(--bk-danger); }
.lvx-legend { display:flex; gap:14px; flex-wrap:wrap; margin-top:9px; font-size:12px; color:var(--bk-text-muted); }
.lvx-legend span::before { content:""; display:inline-block; width:8px; height:8px; border-radius:50%; margin-inline-end:5px; background:var(--lg); }
.lvx-note { display:flex; gap:8px; align-items:flex-start; padding:10px 12px; margin-top:12px; border-radius:10px; font-size:12.5px; line-height:1.5; }
.lvx-note i, .lvx-note svg { width:15px; height:15px; flex-shrink:0; margin-top:1px; }
.lvx-note.warn { background:var(--bk-warning-bg); color:var(--bk-warning); }
.lvx-note.bad  { background:var(--bk-danger-bg);  color:var(--bk-danger); }
.lvx-note.info { background:var(--bk-info-bg);    color:var(--bk-info); }

.lvx-cta { padding:16px 20px 20px; border-top:1px solid var(--bk-border); }
.lvx-submit {
    width:100%; min-height:50px; display:flex; align-items:center; justify-content:center; gap:9px;
    border:0; border-radius:12px; cursor:pointer; font-size:15px; font-weight:700; font-family:inherit;
    background:var(--bk-accent-fill); color:var(--bk-accent-ink);
    box-shadow:0 6px 16px color-mix(in srgb, var(--bk-accent-fill) 28%, transparent);
    transition:background .15s, transform .15s, box-shadow .15s;
}
.lvx-submit:hover { background:var(--bk-accent-hover); transform:translateY(-1px); }
.lvx-submit:focus-visible { outline:3px solid color-mix(in srgb, var(--bk-accent) 40%, transparent); outline-offset:2px; }
.lvx-submit[disabled] { opacity:.6; cursor:progress; transform:none; }
.lvx-cancel { display:block; text-align:center; margin-top:10px; font-size:13px; font-weight:600; color:var(--bk-text-muted); text-decoration:none; padding:6px; }
.lvx-cancel:hover { color:var(--bk-text); }

@media (prefers-reduced-motion: reduce) { .lvx * { transition:none !important; } }
</style>
@endpush

@section('content')
<div class="page-content">
<div class="lvx">

    {{-- Header --}}
    <div class="lvx-top bk-a1">
        <div class="lvx-who">
            @php $initial = mb_strtoupper(mb_substr($isAr ? ($employee->name_ar ?: $employee->name_en) : ($employee->name_en ?: $employee->name_ar), 0, 1)); @endphp
            <div class="lvx-avatar" style="position:relative;overflow:hidden;" aria-hidden="true">{{ $initial }}
                @if($employee->image)
                    <img src="{{ asset('storage/'.$employee->image) }}" alt="" onerror="this.remove()"
                         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                @endif
            </div>
            <div style="min-width:0;">
                <div class="lvx-eyebrow">{{ __('Add Leave') }}</div>
                <h1 class="lvx-title">{{ $employee->localizedName() }}</h1>
            </div>
        </div>
        <a href="{{ route('company.employee-leaves.index') }}" class="lvx-back">
            <i data-feather="arrow-left" style="width:15px;height:15px;"></i>{{ __('All Leaves') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="lvx-grid">

        {{-- ═══ Form ═══ --}}
        <form method="POST" id="leaveForm" action="{{ route('company.employee-leaves.store', $employee) }}" class="lvx-card bk-a2" novalidate>
            @csrf

            {{-- 1 · Type --}}
            <section class="lvx-sec">
                <div class="lvx-sec-head">
                    <span class="lvx-step">1</span>
                    <h2 class="lvx-sec-title">{{ __('Leave Type') }}</h2>
                </div>
                <div class="lvx-types" role="radiogroup" aria-label="{{ __('Leave Type') }}">
                    @foreach(\App\Models\EmployeeLeave::LEAVE_TYPES as $key => $meta)
                        @php $ui = $typeUi[$key] ?? $typeUi['other']; @endphp
                        <label class="lvx-type {{ $oldType === $key ? 'is-selected' : '' }}" style="--t: {{ $ui['color'] }};">
                            <input type="radio" name="type" value="{{ $key }}" {{ $oldType === $key ? 'checked' : '' }}>
                            <span class="lvx-type-card">
                                <span class="lvx-type-ico"><i data-feather="{{ $ui['icon'] }}"></i></span>
                                <span>
                                    <span class="lvx-type-name d-block">{{ __($meta['label_key']) }}</span>
                                    <span class="lvx-type-hint d-block">{{ $ui['hint'] }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('type')<div class="lvx-err">{{ $message }}</div>@enderror
            </section>

            {{-- 2 · When --}}
            <section class="lvx-sec">
                <div class="lvx-sec-head">
                    <span class="lvx-step">2</span>
                    <h2 class="lvx-sec-title">{{ __('When') }}</h2>
                </div>

                <div class="lvx-seg mb-3" role="radiogroup" aria-label="{{ __('Duration') }}">
                    <label class="{{ ! $isHourly ? 'is-selected' : '' }}">
                        <input type="radio" name="is_hourly" value="0" {{ ! $isHourly ? 'checked' : '' }}>
                        <span><i data-feather="calendar"></i>{{ __('Full days') }}</span>
                    </label>
                    <label class="{{ $isHourly ? 'is-selected' : '' }}">
                        <input type="radio" name="is_hourly" value="1" {{ $isHourly ? 'checked' : '' }}>
                        <span><i data-feather="clock"></i>{{ __('Hourly permission') }}</span>
                    </label>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="lvx-label" for="start_date">{{ __('Start Date') }} <span class="lvx-req">*</span></label>
                        <input type="date" name="start_date" id="start_date"
                               class="lvx-input @error('start_date') is-invalid @enderror"
                               min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ old('start_date') }}" required>
                        @error('start_date')<div class="lvx-err">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-6" id="end-date-col">
                        <label class="lvx-label" for="end_date">{{ __('End Date') }} <span class="lvx-req">*</span></label>
                        <input type="date" name="end_date" id="end_date"
                               class="lvx-input @error('end_date') is-invalid @enderror"
                               min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ old('end_date') }}">
                        @error('end_date')<div class="lvx-err">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-3 col-6 d-none" id="start-hour-col">
                        <label class="lvx-label" for="start_hour">{{ __('From') }} <span class="lvx-req">*</span></label>
                        <input type="time" name="start_hour" id="start_hour"
                               class="lvx-input @error('start_hour') is-invalid @enderror"
                               value="{{ old('start_hour', '14:00') }}">
                        @error('start_hour')<div class="lvx-err">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-3 col-6 d-none" id="end-hour-col">
                        <label class="lvx-label" for="end_hour">{{ __('To') }} <span class="lvx-req">*</span></label>
                        <input type="time" name="end_hour" id="end_hour"
                               class="lvx-input @error('end_hour') is-invalid @enderror"
                               value="{{ old('end_hour', '16:00') }}">
                        @error('end_hour')<div class="lvx-err">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="lvx-chips" id="quick-days">
                    <span class="lvx-chips-label">{{ __('Quick pick') }}</span>
                    @foreach([1, 2, 3, 5, 7, 14] as $n)
                        <button type="button" class="lvx-chip" data-days="{{ $n }}">{{ $n }} {{ __('day(s)') }}</button>
                    @endforeach
                </div>
                <p class="lvx-help mb-0" id="hourly-help" hidden>{{ __('A few hours within a single day — does not consume annual leave days') }}</p>
            </section>

            {{-- 3 · Details --}}
            <section class="lvx-sec">
                <div class="lvx-sec-head">
                    <span class="lvx-step">3</span>
                    <h2 class="lvx-sec-title">{{ __('Details') }}</h2>
                    <span class="lvx-sec-sub">{{ __('optional') }}</span>
                </div>

                <label class="lvx-label" for="reason">{{ __('Reason') }}</label>
                <textarea name="reason" id="reason" class="lvx-textarea @error('reason') is-invalid @enderror"
                          maxlength="500" rows="3"
                          placeholder="{{ __('Describe the reason for leave…') }}">{{ old('reason') }}</textarea>
                @error('reason')<div class="lvx-err">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <button type="button" class="lvx-disclose" id="deduct-toggle" aria-expanded="{{ $hasDeduct ? 'true' : 'false' }}" aria-controls="deduct-body">
                        <span class="lvx-disclose-ico"><i data-feather="scissors"></i></span>
                        <span>
                            <b>{{ __('Salary deduction') }}</b>
                            <small>{{ __('Add an amount to deduct from payroll for this leave') }}</small>
                        </span>
                        <i data-feather="chevron-down" class="lvx-caret"></i>
                    </button>

                    <div class="lvx-deduct-body" id="deduct-body" {{ $hasDeduct ? '' : 'hidden' }}>
                        <div class="row g-3">
                            <div class="col-7">
                                <label class="lvx-label" for="deduction_amount">{{ __('Amount') }}</label>
                                <input type="number" name="deduction_amount" id="deduction_amount" min="0" step="0.01" inputmode="decimal"
                                       class="lvx-input @error('deduction_amount') is-invalid @enderror"
                                       value="{{ old('deduction_amount') }}" placeholder="0.00">
                                @error('deduction_amount')<div class="lvx-err">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-5">
                                <label class="lvx-label" for="deduction_currency">{{ __('Currency') }}</label>
                                <select name="deduction_currency" id="deduction_currency" class="lvx-select @error('deduction_currency') is-invalid @enderror">
                                    @foreach(config('booksy.currencies', []) as $code => $cur)
                                        <option value="{{ $code }}" {{ $curDef === $code ? 'selected' : '' }}>
                                            {{ $cur['symbol'] }} {{ $isAr ? $cur['name_ar'] : $cur['name_en'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('deduction_currency')<div class="lvx-err">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <p class="lvx-help mb-0">
                            {{ __('Recorded on the employee and shown in payroll.') }}
                            @if($dailyRate)
                                <span id="unpaid-auto-help">{{ __('For unpaid leave, leave this empty to calculate it automatically from the daily rate.') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </section>
        </form>

        {{-- ═══ Live summary ═══ --}}
        <aside class="lvx-aside bk-a3" aria-live="polite">
            <div class="lvx-card">
                <div class="lvx-sum-head">
                    <h2 class="lvx-sum-title">{{ __('Leave summary') }}</h2>
                    <span class="lvx-approved"><i data-feather="check-circle"></i>{{ __('Approved on save') }}</span>
                </div>

                <div class="lvx-sum-body">
                    <dl class="m-0">
                        <div class="lvx-row"><dt>{{ __('Type') }}</dt><dd id="s-type">—</dd></div>
                        <div class="lvx-row"><dt>{{ __('Period') }}</dt><dd id="s-period" class="is-empty">{{ __('Select dates to preview') }}</dd></div>
                        <div class="lvx-row"><dt>{{ __('Duration') }}</dt><dd id="s-duration" class="is-empty">—</dd></div>
                        <div class="lvx-row"><dt>{{ __('Salary deduction') }}</dt><dd id="s-deduct" class="is-empty">{{ __('None') }}</dd></div>
                    </dl>

                    <div class="lvx-bal" id="s-balance">
                        <div class="lvx-bal-top">
                            <span>{{ __('Annual leave balance') }} {{ now()->year }}</span>
                            <span><b id="s-rem-after">{{ $annualRemaining }}</b> / {{ $totalDays }} {{ __('remaining') }}</span>
                        </div>
                        @if($totalDays > 0)
                            <div class="lvx-bar" id="s-bar">
                                <i class="b-used" id="bar-used"></i><i class="b-this" id="bar-this"></i>
                            </div>
                            <div class="lvx-legend">
                                <span style="--lg:var(--bk-border-strong)">{{ __('used') }} <b id="lg-used">{{ $annualUsed }}</b></span>
                                <span style="--lg:var(--bk-accent-fill)">{{ __('This leave') }} <b id="lg-this">0</b></span>
                            </div>
                        @endif
                    </div>

                    <div id="s-notes"></div>
                </div>

                <div class="lvx-cta">
                    <button type="submit" form="leaveForm" class="lvx-submit" id="submitBtn">
                        <i data-feather="check" style="width:18px;height:18px;"></i>{{ __('Approve & save leave') }}
                    </button>
                    <a href="{{ route('company.employee-leaves.index') }}" class="lvx-cancel">{{ __('Cancel') }}</a>
                </div>
            </div>
        </aside>

    </div>
</div>
</div>

@push('scripts')
<script>
(function () {
    const $  = (s) => document.querySelector(s);
    const $$ = (s) => document.querySelectorAll(s);

    const L = @json($jsL);
    const TOTAL = {{ $totalDays }}, USED = {{ (int) $annualUsed }}, REMAINING = {{ (int) $annualRemaining }};
    const RATE  = @json($dailyRate ? (float) $dailyRate['amount'] : null);
    const RATE_CUR = @json($dailyRate['currency'] ?? null);

    const startEl = $('#start_date'), endEl = $('#end_date');
    const shEl = $('#start_hour'),    ehEl = $('#end_hour');
    const fmt  = (d) => d.toLocaleDateString(L.locale, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
    const fmtShort = (d) => d.toLocaleDateString(L.locale, { weekday: 'short', day: 'numeric', month: 'short' });
    const iso  = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const parse = (v) => v ? new Date(v + 'T00:00:00') : null;
    const money = (n) => Number(n).toLocaleString(L.locale, { maximumFractionDigits: 2 });

    const mode = () => $('input[name="is_hourly"]:checked').value === '1' ? 'hours' : 'days';
    const type = () => $('input[name="type"]:checked')?.value || 'annual';

    function setText(el, text, empty) { el.textContent = text; el.classList.toggle('is-empty', !!empty); }
    function note(kind, icon, text) {
        return '<div class="lvx-note ' + kind + '"><i data-feather="' + icon + '"></i><span>' + text + '</span></div>';
    }

    function update() {
        const hourly = mode() === 'hours';
        const t = type();

        $('#end-date-col').classList.toggle('d-none', hourly);
        $('#start-hour-col').classList.toggle('d-none', !hourly);
        $('#end-hour-col').classList.toggle('d-none', !hourly);
        $('#quick-days').hidden = hourly;
        $('#hourly-help').hidden = !hourly;

        const s = parse(startEl.value), e = parse(endEl.value);
        if (s) endEl.min = startEl.value;

        let days = 0, hours = 0, periodText = null, durText = null, problem = null;

        if (hourly) {
            const sh = shEl.value, eh = ehEl.value;
            if (s && sh && eh) {
                if (eh <= sh) { problem = L.badTime; }
                else {
                    hours = (new Date('2000-01-01T' + eh) - new Date('2000-01-01T' + sh)) / 3600000;
                    // LRI/PDI isolate the time range so RTL bidi doesn't swap the two times
                    periodText = fmtShort(s) + ' · ⁦' + sh + ' → ' + eh + '⁩';
                    durText = hours.toFixed(1).replace(/\.0$/, '') + ' ' + L.hour;
                }
            }
        } else if (s && e) {
            if (e < s) { problem = L.badDates; }
            else {
                days = Math.round((e - s) / 86400000) + 1;
                periodText = days === 1 ? fmt(s) : fmtShort(s) + ' → ' + fmtShort(e);
                durText = days + ' ' + L.day;
            }
        }

        setText($('#s-type'), L.types[t] || '—', false);
        setText($('#s-period'), periodText || L.pick, !periodText);
        setText($('#s-duration'), durText || '—', !durText);

        // Deduction line
        const amt = parseFloat($('#deduction_amount').value) || 0;
        const cur = $('#deduction_currency');
        const curTxt = cur.options[cur.selectedIndex]?.text.trim().split(' ')[0] || '';
        let deductText = L.none, deductEmpty = true;
        if (amt > 0) { deductText = money(amt) + ' ' + curTxt; deductEmpty = false; }
        else if (t === 'unpaid' && RATE && (days || hours)) {
            const est = hourly ? (RATE / 8) * hours : RATE * days;
            deductText = '≈ ' + money(est) + ' ' + RATE_CUR; deductEmpty = false;
        }
        setText($('#s-deduct'), deductText, deductEmpty);

        // Balance projection (annual, full days only)
        const usesBalance = t === 'annual' && !hourly;
        const balBox = $('#s-balance');
        balBox.style.display = usesBalance ? '' : 'none';
        const after = REMAINING - (usesBalance ? days : 0);
        $('#s-rem-after').textContent = after;
        $('#s-rem-after').style.color = after < 0 ? 'var(--bk-danger)' : '';
        if (TOTAL > 0 && $('#s-bar')) {
            const usedPct = Math.min(100, USED / TOTAL * 100);
            const thisPct = Math.min(100 - usedPct, (usesBalance ? days : 0) / TOTAL * 100);
            $('#bar-used').style.width = usedPct + '%';
            $('#bar-this').style.width = thisPct + '%';
            $('#s-bar').classList.toggle('is-over', after < 0);
            $('#lg-this').textContent = usesBalance ? days : 0;
        }

        // Notes
        let notes = '';
        if (problem) notes += note('bad', 'alert-circle', problem);
        else if (usesBalance && after < 0) notes += note('warn', 'alert-triangle', L.over + ' (' + REMAINING + ' ' + L.day + ')');
        else if (!usesBalance && (days || hours)) notes += note('info', 'info', L.noBal);
        if (t === 'unpaid' && amt <= 0 && RATE && (days || hours)) notes += note('info', 'zap', L.auto);
        $('#s-notes').innerHTML = notes;

        // Selected states (JS-driven; adjacent-sibling :checked is unreliable in this theme stack)
        $$('.lvx-type').forEach(l => l.classList.toggle('is-selected', l.querySelector('input').checked));
        $$('.lvx-seg label').forEach(l => l.classList.toggle('is-selected', l.querySelector('input').checked));

        // Quick-pick highlight
        $$('#quick-days .lvx-chip').forEach(c => {
            c.classList.toggle('is-on', !!(s && e && days === +c.dataset.days));
        });

        if (window.feather) feather.replace();
    }

    // Quick-pick: end = start + (n − 1); default start = today
    $$('#quick-days .lvx-chip').forEach(c => c.addEventListener('click', () => {
        if (!startEl.value) startEl.value = iso(new Date());
        const d = parse(startEl.value);
        d.setDate(d.getDate() + (+c.dataset.days - 1));
        endEl.value = iso(d);
        update();
    }));

    // Keep end ≥ start when start moves forward
    startEl.addEventListener('change', () => {
        if (endEl.value && endEl.value < startEl.value) endEl.value = startEl.value;
        update();
    });

    // Deduction disclosure
    const toggle = $('#deduct-toggle'), body = $('#deduct-body');
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!open));
        body.hidden = open;
    });

    ['change', 'input'].forEach(ev => {
        [endEl, shEl, ehEl, $('#deduction_amount'), $('#deduction_currency')].forEach(el => el.addEventListener(ev, update));
    });
    $$('input[name="type"], input[name="is_hourly"]').forEach(r => r.addEventListener('change', update));

    // Submit: guard double-click, light client check (server remains the authority)
    $('#leaveForm').addEventListener('submit', (ev) => {
        const hourly = mode() === 'hours';
        let bad = false;
        [startEl, hourly ? null : endEl].forEach(el => {
            if (!el) return;
            const miss = !el.value;
            el.classList.toggle('is-invalid', miss);
            bad = bad || miss;
        });
        if (!hourly && startEl.value && endEl.value && endEl.value < startEl.value) { endEl.classList.add('is-invalid'); bad = true; }
        if (hourly && ehEl.value <= shEl.value) { ehEl.classList.add('is-invalid'); bad = true; }
        if (bad) { ev.preventDefault(); (document.querySelector('.is-invalid') || startEl).focus(); return; }
        const btn = $('#submitBtn'); btn.disabled = true;
    });

    update();
})();
</script>
@endpush
@endsection
