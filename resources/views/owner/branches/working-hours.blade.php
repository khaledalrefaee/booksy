@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

<div class="page-content bm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a>
                <span aria-hidden="true">·</span> {{ __('Working hours') }}
            </div>
            <h1 class="bm-title">{{ __('Working hours') }}</h1>
            <p class="bm-subtitle" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="bm-chip"><i data-feather="map-pin"></i><span>{{ $branch->localizedName() }}</span></span>
                <span class="bm-chip"><i data-feather="briefcase"></i><span>{{ $branch->company?->localizedName() }}</span></span>
            </p>
        </div>
    </header>

    @include('owner.partials.flash')

    <div class="bm-form-card bm-reveal">
        <div class="bm-form-body">
            @include('owner.branches.partials.wizard-steps', ['currentStep' => 2])

            <form method="post" action="{{ route('owner.branches.working-hours.store', $branch) }}" id="branch-hours-form">
                @csrf
                <div class="bm-section">
                    <div class="bm-section-head"><i data-feather="clock"></i><h2 class="bm-section-title">{{ __('Opening hours') }}</h2></div>
                    <p class="bm-section-sub">{{ __('Set opening hours for each day. You can add a second shift when the branch reopens later in the day.') }}</p>

                    <div class="working-hours-list">
                        @foreach ($weekDays as $dayNum => $dayLabel)
                            @php
                                $s1 = $existingHours[$dayNum][1] ?? null;
                                $s2 = $existingHours[$dayNum][2] ?? null;

                                $oldDay      = old('hours.'.$dayNum, []);
                                $isOpen      = isset($oldDay['is_open'])
                                                ? (bool) $oldDay['is_open']
                                                : ($s1 ? $s1->is_open : ($dayNum >= 1 && $dayNum <= 5));

                                $openTime    = $oldDay['open_time']  ?? ($s1?->open_time  ? substr($s1->open_time,  0, 5) : '09:00');
                                $closeTime   = $oldDay['close_time'] ?? ($s1?->close_time ? substr($s1->close_time, 0, 5) : '18:00');

                                $shift2On    = isset($oldDay['shift2_enabled'])
                                                ? (bool) $oldDay['shift2_enabled']
                                                : ($s2 !== null);
                                $s2Open      = $oldDay['shift2_open_time']  ?? ($s2?->open_time  ? substr($s2->open_time,  0, 5) : '14:00');
                                $s2Close     = $oldDay['shift2_close_time'] ?? ($s2?->close_time ? substr($s2->close_time, 0, 5) : '22:00');
                            @endphp

                            <div class="day-block mb-3 border rounded-4 p-3 {{ $isOpen ? '' : 'opacity-75' }}" id="day-block-{{ $dayNum }}">

                                <input type="hidden" name="hours[{{ $dayNum }}][day_of_week]" value="{{ $dayNum }}">

                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div class="form-check form-switch mb-0" style="min-width:130px;">
                                        <input class="form-check-input js-day-open" type="checkbox"
                                               id="day-open-{{ $dayNum }}" name="hours[{{ $dayNum }}][is_open]" value="1"
                                               data-day="{{ $dayNum }}" @checked($isOpen)>
                                        <label class="form-check-label fw-semibold" for="day-open-{{ $dayNum }}">{{ $dayLabel }}</label>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 shift1-times flex-wrap" id="shift1-times-{{ $dayNum }}">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-2">{{ __('Shift 1') }}</span>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="time" name="hours[{{ $dayNum }}][open_time]" class="form-control form-control-sm js-day-open-time"
                                                   style="width:110px;" data-day="{{ $dayNum }}" value="{{ $openTime }}" @disabled(! $isOpen)>
                                            <span class="text-muted">–</span>
                                            <input type="time" name="hours[{{ $dayNum }}][close_time]" class="form-control form-control-sm js-day-close-time"
                                                   style="width:110px;" data-day="{{ $dayNum }}" value="{{ $closeTime }}" @disabled(! $isOpen)>
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary rounded-pill ms-auto js-add-shift2 {{ ($isOpen && ! $shift2On) ? '' : 'd-none' }}"
                                            id="add-shift2-btn-{{ $dayNum }}" data-day="{{ $dayNum }}">
                                        <i data-feather="plus" style="width:13px;height:13px;"></i>{{ __('Add shift 2') }}
                                    </button>

                                    <span class="ms-auto text-muted small closed-label {{ $isOpen ? 'd-none' : '' }}" id="closed-label-{{ $dayNum }}">{{ __('Closed') }}</span>
                                </div>

                                <div class="shift2-row mt-3 pt-3 border-top d-flex align-items-center gap-2 flex-wrap {{ $shift2On ? '' : 'd-none' }}" id="shift2-row-{{ $dayNum }}">
                                    <input type="hidden" name="hours[{{ $dayNum }}][shift2_enabled]" class="js-shift2-hidden" value="{{ $shift2On ? '1' : '' }}">
                                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2">{{ __('Shift 2') }}</span>
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="time" name="hours[{{ $dayNum }}][shift2_open_time]" class="form-control form-control-sm" style="width:110px;" value="{{ $s2Open }}">
                                        <span class="text-muted">–</span>
                                        <input type="time" name="hours[{{ $dayNum }}][shift2_close_time]" class="form-control form-control-sm" style="width:110px;" value="{{ $s2Close }}">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 js-remove-shift2" data-day="{{ $dayNum }}" title="{{ __('Remove shift 2') }}">
                                        <i data-feather="x-circle" style="width:16px;height:16px;"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('hours')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                </div>
            </form>
        </div>

        <div class="bm-form-foot">
            <form method="post" action="{{ route('owner.branches.working-hours.skip', $branch) }}" class="d-inline">
                @csrf
                <button type="submit" class="bm-btn bm-btn-ghost">{{ __('Skip for now') }}</button>
            </form>
            <button type="submit" form="branch-hours-form" class="bm-btn bm-btn-primary bm-spacer">
                <i data-feather="check"></i>{{ __('Save & finish') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') {
        window.feather.replace();
    }

    // Toggle day open/close
    document.querySelectorAll('.js-day-open').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var day = this.getAttribute('data-day');
            var open = this.checked;

            var shift1 = document.getElementById('shift1-times-' + day);
            var addBtn = document.getElementById('add-shift2-btn-' + day);
            var shift2Row = document.getElementById('shift2-row-' + day);
            var closedLabel = document.getElementById('closed-label-' + day);
            var block = document.getElementById('day-block-' + day);

            shift1.querySelectorAll('input[type="time"]').forEach(function (inp) {
                inp.disabled = !open;
            });

            if (open) {
                block.classList.remove('opacity-75');
                closedLabel.classList.add('d-none');
                if (shift2Row.classList.contains('d-none')) {
                    addBtn.classList.remove('d-none');
                }
            } else {
                block.classList.add('opacity-75');
                closedLabel.classList.remove('d-none');
                addBtn.classList.add('d-none');
                hideShift2(day);
            }
        });
    });

    document.querySelectorAll('.js-add-shift2').forEach(function (btn) {
        btn.addEventListener('click', function () { showShift2(this.getAttribute('data-day')); });
    });

    document.querySelectorAll('.js-remove-shift2').forEach(function (btn) {
        btn.addEventListener('click', function () { hideShift2(this.getAttribute('data-day')); });
    });

    function showShift2(day) {
        var row = document.getElementById('shift2-row-' + day);
        var addBtn = document.getElementById('add-shift2-btn-' + day);
        var hidden = row.querySelector('.js-shift2-hidden');
        row.classList.remove('d-none');
        addBtn.classList.add('d-none');
        if (hidden) { hidden.value = '1'; }
        if (typeof window.feather !== 'undefined') window.feather.replace();
    }

    function hideShift2(day) {
        var row = document.getElementById('shift2-row-' + day);
        var addBtn = document.getElementById('add-shift2-btn-' + day);
        var hidden = row.querySelector('.js-shift2-hidden');
        var dayOpen = document.getElementById('day-open-' + day);
        row.classList.add('d-none');
        if (hidden) { hidden.value = ''; }
        if (dayOpen && dayOpen.checked) { addBtn.classList.remove('d-none'); }
    }

    var hoursForm = document.getElementById('branch-hours-form');
    if (hoursForm) {
        hoursForm.addEventListener('submit', function () {
            hoursForm.querySelectorAll('input[type="time"]').forEach(function (el) { el.disabled = false; });
        });
    }
});
</script>
@endpush
@endsection
