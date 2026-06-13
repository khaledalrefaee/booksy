@extends('company.dashboard')

@push('company-styles')
<style>
.ded-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius:20px; padding:26px 30px; margin-bottom:24px;
    color:#fff; position:relative; overflow:hidden;
}
.ded-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none;
}
.type-card {
    border:2px solid rgba(255,255,255,.1); border-radius:14px;
    padding:16px 12px; text-align:center; cursor:pointer;
    transition:all .2s; background:rgba(255,255,255,.03);
    user-select:none;
}
.bk-theme-light .type-card { border-color:#e2e8f0; background:#f8fafc; }
.type-card.selected { border-color:#f5576c; background:rgba(245,87,108,.1); }
.type-card .tc-icon  { font-size:26px; margin-bottom:6px; display:block; }
.type-card .tc-label { font-size:12px; font-weight:700; }
.f-label {
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.5px; color:rgba(255,255,255,.5);
    margin-bottom:5px; display:block;
}
.bk-theme-light .f-label { color:rgba(0,0,0,.5); }
.f-input {
    width:100%; background:rgba(255,255,255,.05);
    border:1.5px solid rgba(255,255,255,.1); border-radius:11px;
    padding:9px 13px; font-size:13px; color:inherit;
    transition:border-color .2s; outline:none;
}
.f-input:focus { border-color:#f5576c; }
.bk-theme-light .f-input { background:#f8f9fa; border-color:#dee2e6; color:#212529; }
.bk-theme-light .f-input:focus { border-color:#f5576c; }

/* sick leave toggle */
.sick-banner {
    background:rgba(34,197,94,.1); border:1.5px solid rgba(34,197,94,.3);
    border-radius:12px; padding:12px 16px; display:none;
}
.sick-banner.show { display:flex; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="ded-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.employees.deductions.index', $employee) }}"
                               class="text-decoration-none" style="color:rgba(255,255,255,.65);font-size:13px;">
                                {{ $employee->localizedName() }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(255,255,255,.45);font-size:13px;">{{ __('New deduction') }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">{{ __('Record deduction') }}</h3>
            </div>
            <a href="{{ route('company.employees.deductions.index', $employee) }}"
               style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);font-size:13px;backdrop-filter:blur(4px);"
               class="btn btn-sm rounded-pill px-3">
                <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                {{ __('Back') }}
            </a>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('company.employees.deductions.store', $employee) }}">
            @csrf

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">

                    {{-- Type selector --}}
                    <div class="mb-4">
                        <label class="f-label mb-3">{{ __('Type') }}</label>
                        <div class="row g-2">
                            @foreach([
                                'absence'   => ['🚫', __('Absence'),   __('Full day off without excuse')],
                                'tardiness' => ['⏰', __('Tardiness'), __('Late arrival or early leave')],
                                'other'     => ['📌', __('Other'),     __('Custom deduction reason')],
                            ] as $val => [$icon, $lbl, $hint])
                            <div class="col-4">
                                <label class="type-card w-100 {{ old('type','absence') === $val ? 'selected' : '' }}"
                                       for="type_{{ $val }}" onclick="selectType('{{ $val }}')">
                                    <input type="radio" id="type_{{ $val }}" name="type" value="{{ $val }}"
                                           style="display:none;" {{ old('type','absence') === $val ? 'checked' : '' }}>
                                    <span class="tc-icon">{{ $icon }}</span>
                                    <div class="tc-label">{{ $lbl }}</div>
                                    <div style="font-size:10px;opacity:.45;margin-top:2px;">{{ $hint }}</div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Sick leave toggle (shown for absence) --}}
                    <div id="sick-wrap" class="{{ old('type','absence') === 'absence' ? '' : 'd-none' }} mb-4">
                        <div class="sick-banner {{ old('is_sick_leave') ? 'show' : '' }} align-items-center gap-3">
                            <span style="font-size:20px;">🤒</span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:13px;color:#22c55e;">{{ __('Sick leave') }}</div>
                                <div style="font-size:11px;opacity:.7;">{{ __('Absence is recorded but no amount is deducted') }}</div>
                            </div>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_sick_leave" name="is_sick_leave"
                                   value="1" {{ old('is_sick_leave') ? 'checked' : '' }}
                                   onchange="toggleSickBanner(this.checked)">
                            <label class="form-check-label small" for="is_sick_leave">
                                {{ __('This is a sick leave (no deduction)') }}
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Date --}}
                        <div class="col-md-6">
                            <label class="f-label">{{ __('Date') }}</label>
                            <input type="date" name="deduction_date" class="f-input form-control @error('deduction_date') is-invalid @enderror"
                                   value="{{ old('deduction_date', date('Y-m-d')) }}" required>
                            @error('deduction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Hours --}}
                        <div class="col-md-6">
                            <label class="f-label">{{ __('Hours (optional)') }}</label>
                            <input type="number" name="hours" class="f-input form-control"
                                   min="0" max="24" step="0.5"
                                   value="{{ old('hours') }}" placeholder="e.g. 2.5">
                        </div>

                        {{-- Amount --}}
                        <div class="col-12" id="amount-wrap">
                            <label class="f-label">
                                {{ __('Deduction amount') }}
                                ({{ config('booksy.currencies.'.config('booksy.default_currency').'.symbol','ل.س') }})
                            </label>
                            <input type="number" name="amount" class="f-input form-control"
                                   min="0" step="0.01"
                                   value="{{ old('amount') }}" placeholder="0.00">
                            <div class="mt-1" style="font-size:11px;opacity:.5;">{{ __('Leave empty if deduction is calculated from hourly rate.') }}</div>
                        </div>

                        {{-- Recorded by --}}
                        <div class="col-12">
                            <label class="f-label">{{ __('Recorded by') }} <span style="font-weight:400;text-transform:none;">({{ __('optional') }})</span></label>
                            <select name="recorded_by_employee_id" class="f-input form-select">
                                <option value="">— {{ __('Select…') }} —</option>
                                @foreach($recorders as $rec)
                                <option value="{{ $rec->id }}" {{ old('recorded_by_employee_id') == $rec->id ? 'selected' : '' }}>
                                    {{ $rec->localizedName() }}
                                    @if($rec->role) ({{ app()->getLocale()==='ar' ? ($rec->role->label_ar ?: $rec->role->label_en) : ($rec->role->label_en ?: $rec->role->label_ar) }}) @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="f-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="f-input form-control" rows="3"
                                      placeholder="{{ __('Optional reason or additional details…') }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('company.employees.deductions.index', $employee) }}"
                   class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="btn rounded-pill px-4 fw-bold"
                        style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;border:none;">
                    <i data-feather="save" style="width:14px;height:14px;" class="me-1"></i>
                    {{ __('Save deduction') }}
                </button>
            </div>

            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function selectType(val) {
    document.querySelectorAll('[name="type"]').forEach(function(r){ r.checked = r.value === val; });
    document.querySelectorAll('.type-card').forEach(function(c){
        c.classList.toggle('selected', c.querySelector('[name="type"]').value === val);
    });
    document.getElementById('sick-wrap').classList.toggle('d-none', val !== 'absence');
    if (val !== 'absence') {
        document.getElementById('is_sick_leave').checked = false;
        toggleSickBanner(false);
    }
}

function toggleSickBanner(show) {
    var banner     = document.querySelector('.sick-banner');
    var amountWrap = document.getElementById('amount-wrap');
    banner.classList.toggle('show', show);
    amountWrap.style.opacity = show ? '.4' : '1';
    amountWrap.querySelector('input').disabled = show;
}
</script>
@endpush
@endsection
