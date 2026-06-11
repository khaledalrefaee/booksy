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
                        <form method="POST" action="{{ route('company.employee-leaves.store', $employee) }}">
                            @csrf

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="f-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date"
                                           class="f-input form-control @error('start_date') is-invalid @enderror"
                                           value="{{ old('start_date') }}">
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="f-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date"
                                           class="f-input form-control @error('end_date') is-invalid @enderror"
                                           value="{{ old('end_date') }}">
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Live preview --}}
                            <div class="date-preview" id="datePreview">
                                <span style="color:rgba(255,255,255,.35); font-weight:400;" id="previewPlaceholder">{{ __('Select dates to preview') }}</span>
                            </div>

                            <div class="mt-4 mb-4">
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
    box.innerHTML = `<span>${fmt(start)}</span>
        <i data-feather="arrow-right" style="width:13px;height:13px;opacity:.4;"></i>
        <span>${fmt(end)}</span>
        <span class="day-count-badge">${days} ${dayLabel}</span>`;
    feather.replace();
}
document.getElementById('start_date').addEventListener('change', updatePreview);
document.getElementById('end_date').addEventListener('change',   updatePreview);
updatePreview();
</script>
@endpush
@endsection
