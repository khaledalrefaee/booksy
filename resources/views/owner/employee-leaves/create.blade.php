@extends('owner.dashboard')

@push('owner-styles')
<style>
.leave-hero {
    background: linear-gradient(135deg, #f093fb 0%, #c9a227 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #fff;
    position: relative; overflow: hidden;
}
.leave-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.1); pointer-events: none;
}
[dir="rtl"] .leave-hero::before { right: auto; left: -50px; }
.leave-emp-avatar {
    width: 50px; height: 50px; border-radius: 14px;
    background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 20px; flex-shrink: 0;
}

.f-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: rgba(255,255,255,.5); margin-bottom: 5px; display: block;
}
.bk-theme-light .f-label { color: rgba(0,0,0,.5); }
.f-input {
    width: 100%; background: rgba(255,255,255,.05); border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 11px; padding: 9px 13px; font-size: 13px; color: inherit;
    transition: border-color .2s, background .2s; outline: none;
}
.f-input::placeholder { color: rgba(255,255,255,.25); }
.f-input:focus { border-color: #f093fb; background: rgba(240,147,251,.07); box-shadow: 0 0 0 3px rgba(240,147,251,.12); }
.bk-theme-light .f-input { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .f-input::placeholder { color: rgba(0,0,0,.3); }
.bk-theme-light .f-input:focus { background: #fff; border-color: #f093fb; box-shadow: 0 0 0 3px rgba(240,147,251,.12); }
.f-input.is-invalid { border-color: #f5576c !important; }

.preview-box {
    background: rgba(240,147,251,.07); border: 1.5px solid rgba(240,147,251,.2);
    border-radius: 14px; padding: 16px 20px; text-align: center;
}
.bk-theme-light .preview-box { background: rgba(240,147,251,.05); border-color: rgba(240,147,251,.3); }
.preview-days { font-size: 36px; font-weight: 900; color: #f093fb; line-height: 1; }
.bk-theme-light .preview-days { color: #c0209a; }
.preview-label { font-size: 12px; color: rgba(255,255,255,.4); margin-top: 4px; }
.bk-theme-light .preview-label { color: rgba(0,0,0,.4); }

.info-note {
    background: rgba(255,193,7,.08); border: 1.5px solid rgba(255,193,7,.2);
    border-radius: 11px; padding: 11px 14px;
    font-size: 12px; color: rgba(255,255,255,.6);
    display: flex; align-items: flex-start; gap: 8px;
}
.bk-theme-light .info-note { color: rgba(0,0,0,.55); background: rgba(255,193,7,.06); }

.btn-submit-leave {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: #fff; border: none; border-radius: 13px;
    padding: 12px 36px; font-weight: 700; font-size: 14px;
    cursor: pointer; width: 100%;
    box-shadow: 0 4px 18px rgba(240,147,251,.25);
    transition: opacity .2s, transform .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-leave:hover { opacity: .9; transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="leave-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div class="d-flex align-items-center gap-3">
                @php
                    $palette = ['#c9a227','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1'];
                    $bg = $palette[$employee->id % count($palette)];
                @endphp
                <div class="leave-emp-avatar" style="background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});">
                    {{ strtoupper(mb_substr($employee->name_en ?? $employee->name_ar ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="mb-1" style="color:rgba(255,255,255,.75);font-size:12px;font-weight:600;">{{ __('Leave Request for') }}</p>
                    <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">
                        {{ app()->getLocale()==='ar' ? ($employee->name_ar ?: $employee->name_en) : ($employee->name_en ?: $employee->name_ar) }}
                    </h3>
                </div>
            </div>
            <a href="{{ route('owner.employee-leaves.index') }}"
               style="background:rgba(255,255,255,.2); color:#fff; border:1.5px solid rgba(255,255,255,.3); font-weight:600; font-size:13px;"
               class="btn btn-sm rounded-pill px-3">
                <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Back') }}</span>
            </a>
        </div>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <form method="post" action="{{ route('owner.employee-leaves.store', $employee) }}">
            @csrf
            <div class="card border-0 bk-a2" style="border-radius:18px !important; overflow:hidden;">
                <div class="card-body" style="padding:24px;">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="f-label">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date"
                                   class="f-input form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date') }}">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="f-label">{{ __('End Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date"
                                   class="f-input form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date') }}">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="preview-box mb-4" id="days-preview" style="display:none;">
                        <div class="preview-days" id="days-count">0</div>
                        <div class="preview-label">{{ __('day(s)') }}</div>
                    </div>
                    <div class="preview-box mb-4 bk-a3" id="days-hint" style="text-align:center;color:rgba(255,255,255,.35);font-size:12px;">
                        {{ __('Select dates to preview') }}
                    </div>

                    <div class="mb-4">
                        <label class="f-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3"
                                  class="f-input form-control @error('reason') is-invalid @enderror"
                                  placeholder="{{ __('Reason for leave…') }}">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="info-note mb-4">
                        <i data-feather="info" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;color:#ffc107;"></i>
                        <span>{{ __('Leave requests are submitted with pending status. You can approve or reject them from the leaves list.') }}</span>
                    </div>

                    <button type="submit" class="btn-submit-leave">
                        <i data-feather="send" style="width:15px;height:15px;"></i>
                        {{ __('Submit Leave Request') }}
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const startEl = document.getElementById('start_date');
    const endEl   = document.getElementById('end_date');
    const preview = document.getElementById('days-preview');
    const hint    = document.getElementById('days-hint');
    const count   = document.getElementById('days-count');

    function update() {
        const s = startEl.value, e = endEl.value;
        if (s && e) {
            const diff = (new Date(e) - new Date(s)) / 86400000;
            if (diff < 0) {
                hint.style.display = 'block';
                hint.textContent   = '{{ __('End date must be after start date') }}';
                preview.style.display = 'none';
            } else {
                count.textContent     = diff + 1;
                preview.style.display = 'block';
                hint.style.display    = 'none';
            }
        } else {
            preview.style.display = 'none';
            hint.style.display    = 'block';
            hint.textContent      = '{{ __('Select dates to preview') }}';
        }
    }
    startEl.addEventListener('change', update);
    endEl.addEventListener('change', update);
    update();
})();
</script>
@endpush
@endsection
