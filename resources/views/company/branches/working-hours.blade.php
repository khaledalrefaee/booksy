@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">{{ __('Working hours') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.branches.index') }}" class="btn btn-outline-secondary rounded-pill">
            <i data-feather="arrow-left" style="width:14px;"></i> {{ __('Back') }}
        </a>
    </div>

    @include('company.partials.flash')

    @if(session('branch_created'))
    <div class="alert border-0 rounded-4 mb-4 d-flex align-items-start gap-3"
         style="background:linear-gradient(135deg,rgba(43,207,126,.12),rgba(43,207,126,.05));border-inline-start:4px solid #2bcf7e !important;">
        <div style="width:38px;height:38px;border-radius:50%;background:rgba(43,207,126,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-feather="check-circle" style="width:18px;height:18px;color:#2bcf7e;"></i>
        </div>
        <div>
            <p class="fw-bold mb-1" style="color:#2bcf7e;">{{ __('Branch created successfully!') }}</p>
            <p class="mb-2 small text-muted">
                {{ __('Now set the working hours for') }}
                <strong>{{ session('branch_created') }}</strong>
                {{ __('so customers can book appointments.') }}
            </p>
            <a href="{{ route('company.branches.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                {{ __('Skip for now') }}
            </a>
        </div>
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- ── Quick Presets card ───────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold small me-1">
                            <i data-feather="zap" style="width:14px;height:14px;" class="me-1"></i>
                            {{ __('Quick presets') }}:
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <input type="time" id="preset-open"  class="form-control form-control-sm rounded-3" style="width:110px;" value="09:00">
                            <span class="text-muted small">–</span>
                            <input type="time" id="preset-close" class="form-control form-control-sm rounded-3" style="width:110px;" value="18:00">
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btn-apply-weekdays">
                            {{ __('Apply to weekdays (Mon–Fri)') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btn-apply-all">
                            {{ __('Apply to all days') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Main form ────────────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('company.branches.working-hours.update', $branch) }}" id="hours-form">
                        @csrf @method('POST')
                        <p class="text-muted mb-4">{{ __('Set opening hours for each day. You can add a second shift when the branch reopens later in the day.') }}</p>

                        <div class="working-hours-list">
                            @foreach ($weekDays as $dayNum => $dayLabel)
                                @php
                                    $s1 = $existingHours[$dayNum][1] ?? null;
                                    $s2 = $existingHours[$dayNum][2] ?? null;
                                    $oldDay    = old('hours.'.$dayNum, []);
                                    $isOpen    = isset($oldDay['is_open']) ? (bool)$oldDay['is_open'] : ($s1 ? $s1->is_open : ($dayNum >= 1 && $dayNum <= 5));
                                    $openTime  = $oldDay['open_time']  ?? ($s1?->open_time  ? substr($s1->open_time, 0, 5)  : '09:00');
                                    $closeTime = $oldDay['close_time'] ?? ($s1?->close_time ? substr($s1->close_time, 0, 5) : '18:00');
                                    $shift2On  = isset($oldDay['shift2_enabled']) ? (bool)$oldDay['shift2_enabled'] : ($s2 !== null);
                                    $s2Open    = $oldDay['shift2_open_time']  ?? ($s2?->open_time  ? substr($s2->open_time, 0, 5)  : '14:00');
                                    $s2Close   = $oldDay['shift2_close_time'] ?? ($s2?->close_time ? substr($s2->close_time, 0, 5) : '22:00');
                                    $isFirst   = ($dayNum === array_key_first($weekDays));
                                @endphp

                                <div class="day-block mb-3 border rounded-4 p-3 {{ $isOpen ? '' : 'opacity-75' }}"
                                     id="day-block-{{ $dayNum }}" data-day="{{ $dayNum }}">
                                    <input type="hidden" name="hours[{{ $dayNum }}][day_of_week]" value="{{ $dayNum }}">

                                    {{-- Toggle + times row --}}
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="form-check form-switch mb-0" style="min-width:130px;">
                                            <input class="form-check-input js-day-open"
                                                type="checkbox" id="day-open-{{ $dayNum }}"
                                                name="hours[{{ $dayNum }}][is_open]" value="1"
                                                data-day="{{ $dayNum }}" @checked($isOpen)>
                                            <label class="form-check-label fw-semibold" for="day-open-{{ $dayNum }}">{{ $dayLabel }}</label>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 shift1-times flex-wrap" id="shift1-times-{{ $dayNum }}">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2">{{ __('Shift 1') }}</span>
                                            <div class="d-flex align-items-center gap-1">
                                                <input type="time" name="hours[{{ $dayNum }}][open_time]"
                                                    class="form-control form-control-sm rounded-3 js-s1-open" style="width:110px;"
                                                    value="{{ $openTime }}" data-day="{{ $dayNum }}" @disabled(!$isOpen)>
                                                <span class="text-muted">–</span>
                                                <input type="time" name="hours[{{ $dayNum }}][close_time]"
                                                    class="form-control form-control-sm rounded-3 js-s1-close" style="width:110px;"
                                                    value="{{ $closeTime }}" data-day="{{ $dayNum }}" @disabled(!$isOpen)>
                                            </div>
                                        </div>

                                        @if(!$isFirst)
                                        <button type="button" class="btn btn-sm btn-link text-muted p-0 js-copy-above"
                                                data-day="{{ $dayNum }}"
                                                title="{{ __('Copy from day above') }}"
                                                style="opacity:.6;">
                                            <i data-feather="copy" style="width:14px;height:14px;"></i>
                                        </button>
                                        @endif

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary rounded-pill ms-auto js-add-shift2 {{ ($isOpen && !$shift2On) ? '' : 'd-none' }}"
                                            id="add-shift2-btn-{{ $dayNum }}" data-day="{{ $dayNum }}">
                                            <i data-feather="plus" style="width:13px;height:13px;"></i> {{ __('Add shift 2') }}
                                        </button>

                                        <span class="ms-auto text-muted small closed-label {{ $isOpen ? 'd-none' : '' }}" id="closed-label-{{ $dayNum }}">{{ __('Closed') }}</span>
                                    </div>

                                    {{-- Shift 2 --}}
                                    <div class="shift2-row mt-3 pt-3 border-top d-flex align-items-center gap-2 flex-wrap {{ $shift2On ? '' : 'd-none' }}"
                                        id="shift2-row-{{ $dayNum }}">
                                        <input type="hidden" name="hours[{{ $dayNum }}][shift2_enabled]" class="js-shift2-hidden" value="{{ $shift2On ? '1' : '' }}">
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2">{{ __('Shift 2') }}</span>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="time" name="hours[{{ $dayNum }}][shift2_open_time]"
                                                class="form-control form-control-sm rounded-3 js-s2-open" style="width:110px;" value="{{ $s2Open }}">
                                            <span class="text-muted">–</span>
                                            <input type="time" name="hours[{{ $dayNum }}][shift2_close_time]"
                                                class="form-control form-control-sm rounded-3 js-s2-close" style="width:110px;" value="{{ $s2Close }}">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 js-remove-shift2" data-day="{{ $dayNum }}">
                                            <i data-feather="x-circle" style="width:16px;height:16px;"></i>
                                        </button>
                                    </div>

                                    {{-- Visual time bar --}}
                                    <div class="time-bar-wrap mt-2 {{ $isOpen ? '' : 'd-none' }}" id="time-bar-{{ $dayNum }}">
                                        <div class="position-relative rounded-2 overflow-hidden"
                                             style="height:6px;background:rgba(128,128,128,.12);">
                                            <div class="position-absolute top-0 h-100 rounded-2"
                                                 id="bar-s1-{{ $dayNum }}"
                                                 style="background:rgba(var(--bs-primary-rgb),.5);left:0;width:0;transition:left .2s,width .2s;"></div>
                                            <div class="position-absolute top-0 h-100 rounded-2"
                                                 id="bar-s2-{{ $dayNum }}"
                                                 style="background:rgba(var(--bs-warning-rgb),.65);left:0;width:0;transition:left .2s,width .2s;"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1" style="font-size:.65rem;color:#aaa;">
                                            <span>0</span><span>6</span><span>12</span><span>18</span><span>24</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                                {{ __('Save working hours') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof feather !== 'undefined') feather.replace();

    var dayKeys = @json(array_keys($weekDays));

    // ── Time string → % on 24h scale ──────────────────────────────
    function timePct(t) {
        if (!t) return 0;
        var p = t.split(':');
        return Math.min(100, Math.max(0, (parseInt(p[0],10)*60 + parseInt(p[1]||0,10)) / 1440 * 100));
    }

    function updateBar(day) {
        var block = document.getElementById('day-block-' + day);
        if (!block) return;
        var s1o = block.querySelector('.js-s1-open');
        var s1c = block.querySelector('.js-s1-close');
        var b1  = document.getElementById('bar-s1-' + day);
        var b2  = document.getElementById('bar-s2-' + day);
        if (!b1) return;

        var p1s = timePct(s1o ? s1o.value : '');
        var p1e = timePct(s1c ? s1c.value : '');
        if (p1e > p1s) { b1.style.left = p1s + '%'; b1.style.width = (p1e - p1s) + '%'; }
        else { b1.style.width = '0'; }

        var s2Row = document.getElementById('shift2-row-' + day);
        if (b2 && s2Row && !s2Row.classList.contains('d-none')) {
            var s2o = block.querySelector('.js-s2-open');
            var s2c = block.querySelector('.js-s2-close');
            var p2s = timePct(s2o ? s2o.value : '');
            var p2e = timePct(s2c ? s2c.value : '');
            if (p2e > p2s) { b2.style.left = p2s + '%'; b2.style.width = (p2e - p2s) + '%'; }
            else { b2.style.width = '0'; }
        } else if (b2) { b2.style.width = '0'; }
    }

    // Init bars
    dayKeys.forEach(function (d) { updateBar(d); });

    // Update bar on any time change
    document.querySelectorAll('input[type="time"]').forEach(function (inp) {
        inp.addEventListener('change', function () { updateBar(this.getAttribute('data-day')); });
    });

    // ── Day open/close toggle ───────────────────────────────────────
    document.querySelectorAll('.js-day-open').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var day = this.getAttribute('data-day');
            var open = this.checked;
            var s1wrap  = document.getElementById('shift1-times-' + day);
            var addBtn  = document.getElementById('add-shift2-btn-' + day);
            var s2row   = document.getElementById('shift2-row-' + day);
            var closedL = document.getElementById('closed-label-' + day);
            var block   = document.getElementById('day-block-' + day);
            var bar     = document.getElementById('time-bar-' + day);

            s1wrap.querySelectorAll('input[type="time"]').forEach(function (i) { i.disabled = !open; });

            if (open) {
                block.classList.remove('opacity-75');
                closedL.classList.add('d-none');
                if (bar) bar.classList.remove('d-none');
                if (s2row.classList.contains('d-none')) addBtn.classList.remove('d-none');
                updateBar(day);
            } else {
                block.classList.add('opacity-75');
                closedL.classList.remove('d-none');
                if (bar) bar.classList.add('d-none');
                addBtn.classList.add('d-none');
                hideShift2(day);
            }
        });
    });

    // ── Shift 2 ────────────────────────────────────────────────────
    document.querySelectorAll('.js-add-shift2').forEach(function (btn) {
        btn.addEventListener('click', function () { showShift2(this.getAttribute('data-day')); });
    });
    document.querySelectorAll('.js-remove-shift2').forEach(function (btn) {
        btn.addEventListener('click', function () { hideShift2(this.getAttribute('data-day')); });
    });

    function showShift2(day) {
        var row    = document.getElementById('shift2-row-' + day);
        var addBtn = document.getElementById('add-shift2-btn-' + day);
        var hidden = row.querySelector('.js-shift2-hidden');
        row.classList.remove('d-none');
        addBtn.classList.add('d-none');
        if (hidden) hidden.value = '1';
        if (typeof feather !== 'undefined') feather.replace();
        updateBar(day);
    }

    function hideShift2(day) {
        var row    = document.getElementById('shift2-row-' + day);
        var addBtn = document.getElementById('add-shift2-btn-' + day);
        var hidden = row.querySelector('.js-shift2-hidden');
        var toggle = document.getElementById('day-open-' + day);
        row.classList.add('d-none');
        if (hidden) hidden.value = '';
        if (toggle && toggle.checked) addBtn.classList.remove('d-none');
        updateBar(day);
    }

    // ── Copy from day above ────────────────────────────────────────
    document.querySelectorAll('.js-copy-above').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var day = parseInt(this.getAttribute('data-day'), 10);
            var idx = dayKeys.indexOf(day);
            if (idx <= 0) return;
            var prev = dayKeys[idx - 1];

            var src = document.getElementById('day-block-' + prev);
            var dst = document.getElementById('day-block-' + day);
            if (!src || !dst) return;

            // open/closed state
            var srcT = document.getElementById('day-open-' + prev);
            var dstT = document.getElementById('day-open-' + day);
            if (srcT && dstT && dstT.checked !== srcT.checked) {
                dstT.checked = srcT.checked;
                dstT.dispatchEvent(new Event('change'));
            }

            // shift 1 times
            var s1op = dst.querySelector('.js-s1-open');
            var s1cl = dst.querySelector('.js-s1-close');
            var ss1o = src.querySelector('.js-s1-open');
            var ss1c = src.querySelector('.js-s1-close');
            if (ss1o && s1op) { s1op.disabled = false; s1op.value = ss1o.value; }
            if (ss1c && s1cl) { s1cl.disabled = false; s1cl.value = ss1c.value; }

            // shift 2
            var srcS2 = document.getElementById('shift2-row-' + prev);
            if (srcS2 && !srcS2.classList.contains('d-none')) {
                showShift2(day);
                var s2op = dst.querySelector('.js-s2-open');
                var s2cl = dst.querySelector('.js-s2-close');
                var ss2o = src.querySelector('.js-s2-open');
                var ss2c = src.querySelector('.js-s2-close');
                if (ss2o && s2op) s2op.value = ss2o.value;
                if (ss2c && s2cl) s2cl.value = ss2c.value;
            } else {
                hideShift2(day);
            }

            updateBar(day);
        });
    });

    // ── Quick presets ──────────────────────────────────────────────
    function applyPreset(days) {
        var open  = document.getElementById('preset-open').value  || '09:00';
        var close = document.getElementById('preset-close').value || '18:00';
        days.forEach(function (day) {
            var block  = document.getElementById('day-block-' + day);
            var toggle = document.getElementById('day-open-' + day);
            if (!block) return;
            if (toggle && !toggle.checked) {
                toggle.checked = true;
                toggle.dispatchEvent(new Event('change'));
            }
            var s1o = block.querySelector('.js-s1-open');
            var s1c = block.querySelector('.js-s1-close');
            if (s1o) { s1o.disabled = false; s1o.value = open; }
            if (s1c) { s1c.disabled = false; s1c.value = close; }
            updateBar(day);
        });
    }

    document.getElementById('btn-apply-weekdays').addEventListener('click', function () {
        applyPreset(dayKeys.filter(function (d) { return d >= 1 && d <= 5; }));
    });
    document.getElementById('btn-apply-all').addEventListener('click', function () {
        applyPreset(dayKeys);
    });

    // ── Enable disabled inputs before submit ───────────────────────
    var form = document.getElementById('hours-form');
    if (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('input[type="time"]').forEach(function (el) { el.disabled = false; });
        });
    }
});
</script>
@endpush
@endsection
