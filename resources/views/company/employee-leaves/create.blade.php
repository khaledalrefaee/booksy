@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Leave Request Form ── */
.leave-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #fff;
    position: relative; overflow: hidden;
}
.leave-hero::before {
    content: ''; position: absolute;
    top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.08); pointer-events: none;
}
[dir="rtl"] .leave-hero::before { right: auto; left: -50px; }
.leave-avatar {
    width: 50px; height: 50px; border-radius: 14px;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 20px; color: #fff; flex-shrink: 0;
}

/* Form card — use .card for dark/light */
.lv-form-card { border-radius: 18px !important; overflow: hidden; }
.lv-form-header {
    padding: 16px 22px 14px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex; align-items: center; gap: 10px;
}
.bk-theme-light .lv-form-header { border-bottom-color: rgba(0,0,0,.07); }
.lv-form-body { padding: 22px; }

.f-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: rgba(255,255,255,.5); margin-bottom: 5px; display: block;
}
.bk-theme-light .f-label { color: rgba(0,0,0,.5); }
.f-input {
    width: 100%;
    background: rgba(255,255,255,.05); border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 11px; padding: 9px 13px;
    font-size: 13px; color: inherit;
    transition: border-color .2s, background .2s, box-shadow .2s; outline: none;
}
.f-input::placeholder { color: rgba(255,255,255,.25); }
.f-input:focus { border-color: #f093fb; background: rgba(240,147,251,.07); box-shadow: 0 0 0 3px rgba(240,147,251,.15); }
.bk-theme-light .f-input { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .f-input::placeholder { color: rgba(0,0,0,.3); }
.bk-theme-light .f-input:focus { background: #fff; border-color: #f093fb; box-shadow: 0 0 0 3px rgba(240,147,251,.12); }
.f-input.is-invalid { border-color: #f5576c !important; }

/* Date preview box */
.date-preview {
    border-radius: 12px; margin-top: 14px;
    border: 1.5px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.04);
    padding: 14px 18px;
    display: flex; align-items: center; justify-content: center;
    gap: 10px; font-weight: 600; font-size: 13px;
    min-height: 52px; flex-wrap: wrap;
}
.bk-theme-light .date-preview { border-color: #dee2e6; background: #f8f9fa; }
.day-count-badge {
    background: linear-gradient(135deg,#f093fb,#f5576c);
    color: #fff; border-radius: 8px;
    padding: 3px 12px; font-size: 12px; font-weight: 700;
}

/* Info note */
.lv-info {
    background: rgba(240,147,251,.06);
    border: 1px solid rgba(240,147,251,.15);
    border-radius: 11px; padding: 12px 15px;
    font-size: 12px; color: rgba(255,255,255,.55);
    display: flex; align-items: flex-start; gap: 8px;
}
.bk-theme-light .lv-info { color: rgba(0,0,0,.5); background: rgba(240,147,251,.05); border-color: rgba(240,147,251,.2); }

/* Leave type selector */
.lv-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.lv-type-opt { position: relative; }
.lv-type-opt input { position: absolute; opacity: 0; pointer-events: none; }
.lv-type-card {
    display: flex; align-items: center; gap: 10px;
    border: 1.5px solid rgba(255,255,255,.1); border-radius: 12px;
    padding: 11px 14px; cursor: pointer; transition: all .15s;
    background: rgba(255,255,255,.04); font-size: 13px; font-weight: 600;
    margin-bottom: 0; width: 100%;
}
.bk-theme-light .lv-type-card { border-color: #dee2e6; background: #f8f9fa; }
.lv-type-card:hover { border-color: rgba(240,147,251,.4); }
.lv-type-opt input:checked + .lv-type-card {
    border-color: var(--lv-type-color, #f093fb);
    background: color-mix(in srgb, var(--lv-type-color, #f093fb) 10%, transparent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--lv-type-color, #f093fb) 15%, transparent);
}
.lv-type-icon { font-size: 18px; flex-shrink: 0; }

/* Balance chip */
.lv-balance {
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap;
    border-radius: 12px; padding: 12px 16px; margin-bottom: 18px;
    background: rgba(102,126,234,.07); border: 1.5px solid rgba(102,126,234,.18);
    font-size: 13px;
}
.lv-balance-num { font-size: 20px; font-weight: 800; }
.lv-balance-bar-bg {
    flex: 1 1 120px; height: 7px; border-radius: 5px;
    background: rgba(255,255,255,.08); overflow: hidden; min-width: 90px;
}
.bk-theme-light .lv-balance-bar-bg { background: rgba(0,0,0,.08); }
.lv-balance-bar-fill { height: 100%; border-radius: 5px; transition: width .3s; }

/* Submit button */
.btn-submit-leave {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: #fff; border: none; border-radius: 13px;
    padding: 12px 36px; font-weight: 700; font-size: 14px;
    cursor: pointer; width: 100%;
    box-shadow: 0 4px 18px rgba(245,87,108,.3);
    transition: opacity .2s, transform .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-leave:hover { opacity: .9; transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- Hero --}}
    <div class="leave-hero bk-a1">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div class="d-flex align-items-center gap-3">
                <div class="leave-avatar">
                    {{ strtoupper(mb_substr($employee->name_en ?? $employee->name_ar ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div style="color:rgba(255,255,255,.7); font-size:12px; margin-bottom:4px;">{{ __('Leave Request for') }}</div>
                    <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">{{ $employee->localizedName() }}</h3>
                </div>
            </div>
            <a href="{{ route('company.employee-leaves.index') }}"
               style="background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.3); font-weight:600; font-size:13px; backdrop-filter:blur(4px);"
               class="btn btn-sm rounded-pill px-3">
                <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('All Leaves') }}</span>
            </a>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 lv-form-card bk-a2">
                <div class="card-body p-0">
                    <div class="lv-form-header">
                        <div style="width:32px;height:32px; border-radius:10px; background:rgba(240,147,251,.12);
                                    display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i data-feather="calendar" style="width:15px;height:15px;color:#f093fb;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:13px;">{{ __('Leave Details') }}</div>
                            <div style="font-size:11px; color:rgba(255,255,255,.45);" class="bk-theme-light-sub">{{ __('Select the leave period and reason') }}</div>
                        </div>
                    </div>

                    <div class="lv-form-body">

                        {{-- Annual balance --}}
                        @php
                            $totalDays = (int) $employee->annual_leave_days;
                            $usedPct   = $totalDays > 0 ? min(100, round($annualUsed / $totalDays * 100)) : 0;
                            $balColor  = $annualRemaining <= 0 ? '#ef4444' : ($annualRemaining <= 5 ? '#f59e0b' : '#22c55e');
                        @endphp
                        <div class="lv-balance">
                            <div>
                                <div style="font-size:11px;font-weight:700;opacity:.5;text-transform:uppercase;letter-spacing:.5px;">🏖️ {{ __('Annual leave balance') }} {{ now()->year }}</div>
                                <div class="d-flex align-items-baseline gap-2 mt-1">
                                    <span class="lv-balance-num" style="color:{{ $balColor }};">{{ $annualRemaining }}</span>
                                    <span style="opacity:.5;font-size:12px;">/ {{ $totalDays }} {{ __('day(s)') }} {{ __('remaining') }}</span>
                                </div>
                            </div>
                            <div class="lv-balance-bar-bg">
                                <div class="lv-balance-bar-fill" style="width:{{ $usedPct }}%;background:{{ $balColor }};"></div>
                            </div>
                            <div style="font-size:12px;opacity:.55;">{{ $annualUsed }} {{ __('day(s)') }} {{ __('used') }}</div>
                        </div>

                        <form method="POST" action="{{ route('company.employee-leaves.store', $employee) }}">
                            @csrf

                            {{-- Leave type --}}
                            <div class="mb-3">
                                <label class="f-label">{{ __('Leave Type') }} <span class="text-danger">*</span></label>
                                <div class="lv-type-grid">
                                    @foreach(\App\Models\EmployeeLeave::LEAVE_TYPES as $key => $meta)
                                    <div class="lv-type-opt" style="--lv-type-color: {{ $meta['color'] }};">
                                        <input type="radio" name="type" id="type-{{ $key }}" value="{{ $key }}"
                                               {{ old('type', 'annual') === $key ? 'checked' : '' }}>
                                        <label class="lv-type-card" for="type-{{ $key }}">
                                            <span class="lv-type-icon">{{ $meta['icon'] }}</span>
                                            <span>{{ __($meta['label_key']) }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @error('type')<div class="text-danger tx-12 mt-1">{{ $message }}</div>@enderror
                            </div>

                            {{-- Hourly permission toggle --}}
                            <label class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:rgba(79,172,254,.06);border:1.5px solid rgba(79,172,254,.18);cursor:pointer;">
                                <input type="checkbox" name="is_hourly" id="is_hourly" value="1" class="form-check-input mt-0"
                                       style="width:38px;height:20px;cursor:pointer;flex-shrink:0;" {{ old('is_hourly') ? 'checked' : '' }}>
                                <span>
                                    <span class="d-block fw-bold" style="font-size:13px;">⏱️ {{ __('Hourly permission') }}</span>
                                    <span class="d-block tx-11" style="opacity:.55;">{{ __('A few hours within a single day — does not consume annual leave days') }}</span>
                                </span>
                            </label>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="f-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date"
                                           class="f-input form-control @error('start_date') is-invalid @enderror"
                                           value="{{ old('start_date') }}">
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6" id="end-date-col">
                                    <label class="f-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date"
                                           class="f-input form-control @error('end_date') is-invalid @enderror"
                                           value="{{ old('end_date') }}">
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-3 d-none" id="start-hour-col">
                                    <label class="f-label">{{ __('From') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="start_hour" id="start_hour"
                                           class="f-input form-control @error('start_hour') is-invalid @enderror"
                                           value="{{ old('start_hour', '14:00') }}">
                                    @error('start_hour')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-3 d-none" id="end-hour-col">
                                    <label class="f-label">{{ __('To') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="end_hour" id="end_hour"
                                           class="f-input form-control @error('end_hour') is-invalid @enderror"
                                           value="{{ old('end_hour', '16:00') }}">
                                    @error('end_hour')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Live preview --}}
                            <div class="date-preview" id="datePreview">
                                <span style="color:rgba(255,255,255,.35); font-weight:400;" id="previewPlaceholder">{{ __('Select dates to preview') }}</span>
                            </div>

                            {{-- Optional salary deduction --}}
                            <div class="mt-4 mb-3 p-3 rounded-3" style="background:rgba(245,158,11,.05);border:1.5px solid rgba(245,158,11,.18);">
                                <label class="f-label" style="color:#f59e0b;">💸 {{ __('Salary deduction') }} <span style="font-weight:400;text-transform:none;letter-spacing:0;">({{ __('optional') }})</span></label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="number" name="deduction_amount" min="0" step="0.01"
                                               class="f-input form-control @error('deduction_amount') is-invalid @enderror"
                                               value="{{ old('deduction_amount') }}" placeholder="{{ __('Amount') }}">
                                        @error('deduction_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-5">
                                        <select name="deduction_currency" class=" form-select @error('deduction_currency') is-invalid @enderror">
                                            @foreach(config('booksy.currencies', []) as $code => $cur)
                                            <option value="{{ $code }}" {{ old('deduction_currency', $employee->compensation?->currency ?? config('booksy.default_currency', 'SYP')) === $code ? 'selected' : '' }}>
                                                {{ $cur['symbol'] }} {{ app()->getLocale() === 'ar' ? $cur['name_ar'] : $cur['name_en'] }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('deduction_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="tx-11 mt-2" style="opacity:.55;">{{ __('The deduction is recorded on the employee only when the leave is approved, and appears in payroll.') }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="f-label">{{ __('Reason') }}</label>
                                <textarea name="reason" class="f-input form-control" rows="3"
                                          placeholder="{{ __('Describe the reason for leave…') }}"
                                          style="resize:none;">{{ old('reason') }}</textarea>
                            </div>

                            <div class="lv-info mb-4">
                                <i data-feather="info" style="width:14px;height:14px;color:#f093fb;flex-shrink:0;margin-top:1px;"></i>
                                <span>{{ __('This request will be marked as Pending until approved by a manager.') }}</span>
                            </div>

                            <button type="submit" class="btn-submit-leave">
                                <i data-feather="send" style="width:15px;height:15px;"></i>
                                {{ __('Submit Leave Request') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@push('scripts')
<script>
function updatePreview() {
    const s = document.getElementById('start_date').value;
    const e = document.getElementById('end_date').value;
    const box = document.getElementById('datePreview');
    const locale = '{{ app()->getLocale() === "ar" ? "ar-SA" : "en-GB" }}';
    const errMsg = '{{ __("End date must be after start date") }}';
    const dayLabel = '{{ __("day(s)") }}';
    const hourly = document.getElementById('is_hourly').checked;

    if (hourly) {
        const sh = document.getElementById('start_hour').value;
        const eh = document.getElementById('end_hour').value;
        if (!s || !sh || !eh) {
            box.innerHTML = '<span style="color:rgba(255,255,255,.35);font-weight:400;">{{ __("Select dates to preview") }}</span>';
            return;
        }
        if (eh <= sh) {
            box.innerHTML = '<span style="color:#f87171;font-weight:600;">{{ __("End time must be after start time.") }}</span>';
            return;
        }
        const hrs = ((new Date('2000-01-01T' + eh)) - (new Date('2000-01-01T' + sh))) / 3600000;
        const d = new Date(s);
        box.innerHTML = `<span>⏱️ ${d.toLocaleDateString(locale, {weekday:'short', month:'short', day:'numeric'})}</span>
            <span>${sh} → ${eh}</span>
            <span class="day-count-badge">${hrs.toFixed(1)} {{ __('hour(s)') }}</span>
            <span style="flex-basis:100%;text-align:center;color:#4facfe;font-size:11px;font-weight:600;">{{ __('Does not consume annual leave days') }}</span>`;
        return;
    }

    if (!s || !e) {
        box.innerHTML = '<span style="color:rgba(255,255,255,.35);font-weight:400;" id="previewPlaceholder">{{ __("Select dates to preview") }}</span>';
        return;
    }
    const start = new Date(s), end = new Date(e);
    if (end < start) {
        box.innerHTML = '<span style="color:#f87171;font-weight:600;">' + errMsg + '</span>';
        return;
    }
    const days = Math.round((end - start) / 86400000) + 1;
    const fmt  = d => d.toLocaleDateString(locale, {weekday:'short', month:'short', day:'numeric'});

    const remaining = {{ (int) $annualRemaining }};
    const isAnnual  = document.querySelector('input[name="type"]:checked')?.value === 'annual';
    let balanceNote = '';
    if (isAnnual && days > remaining) {
        balanceNote = `<span style="flex-basis:100%;text-align:center;color:#f59e0b;font-size:12px;font-weight:600;">⚠️ {{ __('This request exceeds the remaining annual balance') }} (${remaining} {{ __('day(s)') }})</span>`;
    }

    box.innerHTML = `<span>${fmt(start)}</span>
        <i data-feather="arrow-right" style="width:13px;height:13px;opacity:.4;"></i>
        <span>${fmt(end)}</span>
        <span class="day-count-badge">${days} ${dayLabel}</span>${balanceNote}`;
    feather.replace();
}
document.getElementById('start_date').addEventListener('change', updatePreview);
document.getElementById('end_date').addEventListener('change',   updatePreview);
document.getElementById('start_hour').addEventListener('change', updatePreview);
document.getElementById('end_hour').addEventListener('change',   updatePreview);
document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', updatePreview));

// Hourly toggle: swap end-date for time inputs
function syncHourlyUi() {
    const hourly = document.getElementById('is_hourly').checked;
    document.getElementById('end-date-col').classList.toggle('d-none', hourly);
    document.getElementById('start-hour-col').classList.toggle('d-none', !hourly);
    document.getElementById('end-hour-col').classList.toggle('d-none', !hourly);
    updatePreview();
}
document.getElementById('is_hourly').addEventListener('change', syncHourlyUi);
syncHourlyUi();
</script>
@endpush
@endsection
