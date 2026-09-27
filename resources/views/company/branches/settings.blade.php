@extends('company.dashboard')

@php
    use App\Support\BranchSettings;

    $isAr = app()->getLocale() === 'ar';

    // Current values: the failed submission first, then the saved branch.
    $v = fn (string $key, $fallback = null) => old($key, $branch->{$key} ?? $fallback);

    $tz        = $v('timezone', config('app.timezone'));
    $fmt       = $v('time_format', '12h');
    $interval  = (int) $v('appointment_interval', 15);
    $firstDay  = (int) $v('first_day_of_week', 0);

    $hoursOpen  = $businessHours['open']  ? substr($businessHours['open'], 0, 5)  : null;
    $hoursClose = $businessHours['close'] ? substr($businessHours['close'], 0, 5) : null;

    $clockData = [
        'locale'   => $isAr ? 'ar' : 'en',
        'am'       => $isAr ? 'ص' : 'AM',
        'pm'       => $isAr ? 'م' : 'PM',
        'open'     => $hoursOpen,
        'close'    => $hoursClose,
        'others'   => $otherBranches->map(fn ($b) => ['name' => $b->localizedName(), 'tz' => $b->tz()])->values(),
        'strings'  => [
            'search'   => __('Search city or time zone'),
            'noMatch'  => __('No time zone matches'),
        ],
    ];
@endphp

@push('company-styles')
<style>
/* ── Branch Settings (bs-) — built on the shared bk-* tokens ─────────────── */
.bs-bar { position:sticky; top:0; z-index:20; background:color-mix(in srgb,var(--bk-bg) 86%,transparent); backdrop-filter:blur(6px); }
.bs-crumb { font-size:.8rem; color:var(--bk-text-muted); }
.bs-crumb a { color:inherit; text-decoration:none; }
.bs-crumb a:hover { color:var(--bk-accent); }

.bs-section { container-type:inline-size; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:18px; margin-bottom:16px; }
.bs-section-head { display:flex; align-items:center; gap:12px; padding:18px 22px 4px; }
.bs-section-head .bk-icon-gold { width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.bs-section-head h2 { font-size:1rem; font-weight:700; margin:0; color:var(--bk-text); }
.bs-section-head p { font-size:.8rem; color:var(--bk-text-muted); margin:2px 0 0; }

/* A settings row: explanation on one side, the control on the other */
.bs-row { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,300px); gap:10px 28px; align-items:center; padding:16px 22px; }
.bs-row + .bs-row { border-top:1px solid var(--bk-border); }
.bs-row-label { font-weight:600; font-size:.9rem; color:var(--bk-text); margin:0; }
.bs-row-help { font-size:.8rem; color:var(--bk-text-muted); margin:3px 0 0; line-height:1.5; max-width:52ch; }
.bs-control { justify-self:end; width:100%; }
.bs-control-end { justify-self:end; }
/* Stack by the width of the form column, not the viewport */
@container (max-width:620px) {
    .bs-row { grid-template-columns:1fr; }
    .bs-control, .bs-control-end { justify-self:stretch; }
}
.bs-seg input:focus-visible + label, .bs-tz-btn:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; box-shadow:none; }

/* Segmented choice (time format, first day) */
.bs-seg { display:grid; grid-auto-flow:column; grid-auto-columns:1fr; gap:4px; padding:4px; border-radius:12px; background:var(--bk-surface-2); border:1px solid var(--bk-border); }
.bs-seg input { position:absolute; opacity:0; pointer-events:none; }
.bs-seg label { text-align:center; padding:8px 10px; border-radius:9px; cursor:pointer; font-size:.84rem; font-weight:600; color:var(--bk-text-soft); transition:background .15s, color .15s, box-shadow .15s; margin:0; }
.bs-seg label small { display:block; font-weight:500; font-size:.72rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; direction:ltr; }
.bs-seg input:checked + label { background:var(--bk-surface); color:var(--bk-accent); box-shadow:0 1px 3px rgba(0,0,0,.12); }
.bs-seg input:checked + label small { color:var(--bk-text-soft); }

/* Searchable time-zone combobox (enhances a native <select>) */
.bs-tz { position:relative; }
.bs-tz-btn { width:100%; display:flex; align-items:center; justify-content:space-between; gap:8px; text-align:start; }
.bs-tz-btn .bs-tz-off { font-size:.75rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; direction:ltr; }
.bs-tz-pop { position:absolute; inset-inline:0; top:calc(100% + 6px); z-index:40; background:var(--bk-surface); border:1px solid var(--bk-border-strong); border-radius:14px; box-shadow:0 12px 32px -8px rgba(0,0,0,.28); overflow:hidden; }
.bs-tz-pop[hidden] { display:none; }
.bs-tz-search { border:0 !important; border-bottom:1px solid var(--bk-border) !important; border-radius:0 !important; padding:12px 14px !important; box-shadow:none !important; }
.bs-tz-list { max-height:300px; overflow-y:auto; margin:0; padding:6px; list-style:none; scrollbar-width:thin; scrollbar-color:var(--bk-border-strong) transparent; }
.bs-tz-group { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted); padding:10px 10px 4px; }
.bs-tz-opt { display:flex; justify-content:space-between; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; font-size:.85rem; color:var(--bk-text); }
.bs-tz-opt .id { font-size:.72rem; color:var(--bk-text-muted); direction:ltr; }
.bs-tz-opt .off { font-size:.72rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; direction:ltr; white-space:nowrap; }
.bs-tz-opt.is-active { background:var(--bk-accent-wash); }
.bs-tz-opt[aria-selected="true"] { color:var(--bk-accent); font-weight:600; }
.bs-tz-empty { padding:18px; text-align:center; font-size:.85rem; color:var(--bk-text-muted); }

/* Branch clock (live preview) */
.bs-clock { position:sticky; top:88px; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:18px; padding:22px; }
.bs-clock-label { font-size:.75rem; color:var(--bk-text-muted); margin:0 0 4px; }
.bs-clock-time { font-family:var(--bk-serif, inherit); font-size:2.6rem; font-weight:600; line-height:1; color:var(--bk-text); font-variant-numeric:tabular-nums; unicode-bidi:isolate; letter-spacing:-.02em; }
.bs-clock-time .ap { font-size:1rem; font-weight:600; color:var(--bk-text-soft); margin-inline-start:6px; letter-spacing:0; }
.bs-clock-date { font-size:.82rem; color:var(--bk-text-soft); margin-top:6px; }
.bs-clock dl { display:grid; grid-template-columns:auto 1fr; gap:8px 14px; margin:18px 0 0; padding-top:16px; border-top:1px solid var(--bk-border); font-size:.82rem; }
.bs-clock dt { color:var(--bk-text-muted); font-weight:500; }
.bs-clock dd { margin:0; color:var(--bk-text); font-weight:600; text-align:end; direction:ltr; font-variant-numeric:tabular-nums; }
[dir="rtl"] .bs-clock dd { text-align:start; }
.bs-slots-title { font-size:.75rem; color:var(--bk-text-muted); margin:18px 0 8px; padding-top:16px; border-top:1px solid var(--bk-border); }
.bs-slots { display:flex; flex-wrap:wrap; gap:6px; }
.bs-slot { padding:5px 10px; border-radius:8px; border:1px solid var(--bk-border); font-size:.78rem; font-weight:600; color:var(--bk-text-soft); font-variant-numeric:tabular-nums; unicode-bidi:isolate; }
.bs-others { margin:16px 0 0; padding:14px 0 0; border-top:1px solid var(--bk-border); list-style:none; font-size:.8rem; }
.bs-others li { display:flex; justify-content:space-between; gap:8px; padding:3px 0; color:var(--bk-text-soft); }
.bs-others li span:last-child { font-variant-numeric:tabular-nums; unicode-bidi:isolate; color:var(--bk-text); font-weight:600; }
/* Below desktop the preview sits above the form: keep it to one compact band */
@media (max-width:991.98px) {
    .bs-clock { position:static; margin-bottom:4px; padding:16px 18px; display:grid; grid-template-columns:auto 1fr; gap:4px 18px; align-items:center; }
    .bs-clock-label { grid-column:1; margin:0; }
    .bs-clock-time { grid-column:1; font-size:2rem; }
    .bs-clock-date { grid-column:1; margin-top:2px; }
    .bs-clock dl { grid-column:2; grid-row:1 / span 3; margin:0; padding:0 0 0 0; border:0; padding-inline-start:18px; border-inline-start:1px solid var(--bk-border); }
    .bs-slots-title, .bs-slots, .bs-others { display:none; }
}

.bs-hours { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
.bs-hours strong { font-variant-numeric:tabular-nums; unicode-bidi:isolate; }
.bs-note { font-size:.8rem; color:var(--bk-text-muted); padding:0 22px 18px; margin:0; }
.bs-note a { color:var(--bk-accent); font-weight:600; text-decoration:none; }
.bs-note a:hover { text-decoration:underline; text-underline-offset:3px; }
.bs-warn { font-size:.82rem; color:var(--bk-text); background:var(--bk-warning-bg); border-radius:10px; padding:8px 12px; }
.bs-footer { display:flex; justify-content:flex-end; align-items:center; gap:14px; padding:6px 0 24px; }
.bs-dirty { font-size:.8rem; color:var(--bk-text-muted); }
.bs-dirty[hidden] { display:none; }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- ── Title bar + save ──────────────────────────────────────── --}}
    <div class="bs-bar d-flex justify-content-between align-items-center flex-wrap gap-2 grid-margin py-2">
        <div>
            <h4 class="bk-t-page mb-1">{{ __('Branch Settings') }}</h4>
            <nav class="bs-crumb" aria-label="breadcrumb">
                <a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a>
                <span aria-hidden="true"> / </span>
                <span>{{ $branch->localizedName() }}</span>
            </nav>
        </div>
        <button type="submit" form="bs-form" class="btn btn-gold rounded-pill px-4 js-bs-save">
            <i data-feather="check" style="width:15px;height:15px;" class="me-1"></i>{{ __('Save changes') }}
        </button>
    </div>

    @include('company.partials.flash')

    <form method="POST" action="{{ route('company.branches.settings.update', $branch) }}" id="bs-form" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8 order-2 order-lg-1">

                {{-- ① Time & Region ─────────────────────────────── --}}
                <section class="bs-section" aria-labelledby="bs-h-time">
                    <div class="bs-section-head">
                        <span class="bk-icon-gold"><i data-feather="globe" style="width:16px;height:16px;"></i></span>
                        <div>
                            <h2 id="bs-h-time">{{ __('Time & Region') }}</h2>
                            <p>{{ __('Every time at this branch — bookings, calendar, reminders — follows its own clock.') }}</p>
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <label class="bs-row-label" for="bs-timezone">{{ __('Time zone') }}</label>
                            <p class="bs-row-help">{{ __('Pick the city the branch is in. Daylight-saving changes are handled automatically.') }}</p>
                            @error('timezone')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="bs-control bs-tz" data-bs-tz>
                            <select name="timezone" id="bs-timezone" class="form-select rounded-3 @error('timezone') is-invalid @enderror">
                                @foreach ($timezoneGroups as $region => $zones)
                                    <optgroup label="{{ $region }}">
                                        @foreach ($zones as $zone)
                                            <option value="{{ $zone['id'] }}" data-city="{{ $zone['city'] }}" data-offset="{{ $zone['offset'] }}" @selected($zone['id'] === $tz)>
                                                {{ $zone['city'] }} — {{ $zone['id'] }} ({{ $zone['offset'] }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <span class="bs-row-label" id="bs-l-format">{{ __('Time format') }}</span>
                            <p class="bs-row-help">{{ __('How times appear in the calendar, appointments and the booking page.') }}</p>
                        </div>
                        <div class="bs-control bs-seg" role="radiogroup" aria-labelledby="bs-l-format">
                            <input type="radio" name="time_format" id="bs-fmt-24" value="24h" @checked($fmt === '24h')>
                            <label for="bs-fmt-24">{{ __('24-hour') }}<small>14:30</small></label>
                            <input type="radio" name="time_format" id="bs-fmt-12" value="12h" @checked($fmt !== '24h')>
                            <label for="bs-fmt-12">{{ __('12-hour') }}<small>2:30 {{ $isAr ? 'م' : 'PM' }}</small></label>
                        </div>
                    </div>
                </section>

                {{-- ② Appointment schedule ──────────────────────── --}}
                <section class="bs-section" aria-labelledby="bs-h-schedule">
                    <div class="bs-section-head">
                        <span class="bk-icon-gold"><i data-feather="clock" style="width:16px;height:16px;"></i></span>
                        <div>
                            <h2 id="bs-h-schedule">{{ __('Appointment Schedule') }}</h2>
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <label class="bs-row-label" for="bs-interval">{{ __('Appointment interval') }}</label>
                            <p class="bs-row-help">{{ __('The gap between start times offered to customers. A 45-minute service still books for 45 minutes — the interval only decides when it can start.') }}</p>
                        </div>
                        <div class="bs-control">
                            <select name="appointment_interval" id="bs-interval" class="form-select rounded-3">
                                @foreach (BranchSettings::INTERVALS as $min)
                                    <option value="{{ $min }}" @selected($interval === $min)>{{ BranchSettings::durationLabel($min) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                {{-- ③ Booking window ────────────────────────────── --}}
                <section class="bs-section" aria-labelledby="bs-h-window">
                    <div class="bs-section-head">
                        <span class="bk-icon-gold"><i data-feather="calendar" style="width:16px;height:16px;"></i></span>
                        <div>
                            <h2 id="bs-h-window">{{ __('Booking Window') }}</h2>
                            <p>{{ __('Applies to online bookings. Your team can still book any time from the dashboard.') }}</p>
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <label class="bs-row-label" for="bs-min-notice">{{ __('Minimum advance booking') }}</label>
                            <p class="bs-row-help">{{ __('How close to the start time a customer can still book.') }}</p>
                        </div>
                        <div class="bs-control">
                            <select name="min_booking_notice" id="bs-min-notice" class="form-select rounded-3">
                                @foreach (BranchSettings::MIN_NOTICE as $min)
                                    <option value="{{ $min }}" @selected((int) $v('min_booking_notice', 0) === $min)>
                                        {{ $min === 0 ? __('No restriction') : BranchSettings::durationLabel($min) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <label class="bs-row-label" for="bs-max-days">{{ __('Maximum advance booking') }}</label>
                            <p class="bs-row-help">{{ __('How far ahead customers can book.') }}</p>
                        </div>
                        <div class="bs-control">
                            <select name="max_booking_days" id="bs-max-days" class="form-select rounded-3">
                                @foreach (BranchSettings::MAX_DAYS as $days)
                                    <option value="{{ $days }}" @selected((int) $v('max_booking_days', 365) === $days)>{{ BranchSettings::daysLabel($days) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="bs-note">
                        {{ __('Online booking on/off, same-day bookings, cancelling and rescheduling are set in') }}
                        <a href="{{ route('company.booking-policy.edit') }}">{{ __('Booking & Cancellation Policy') }}</a>.
                    </p>
                </section>

                {{-- ④ Calendar ──────────────────────────────────── --}}
                <section class="bs-section" aria-labelledby="bs-h-calendar">
                    <div class="bs-section-head">
                        <span class="bk-icon-gold"><i data-feather="grid" style="width:16px;height:16px;"></i></span>
                        <div>
                            <h2 id="bs-h-calendar">{{ __('Calendar') }}</h2>
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <span class="bs-row-label" id="bs-l-firstday">{{ __('First day of week') }}</span>
                            <p class="bs-row-help">{{ __('The day the week view starts on.') }}</p>
                        </div>
                        <div class="bs-control bs-seg" role="radiogroup" aria-labelledby="bs-l-firstday">
                            @foreach (BranchSettings::FIRST_DAYS as $dow)
                                <input type="radio" name="first_day_of_week" id="bs-fd-{{ $dow }}" value="{{ $dow }}" @checked($firstDay === $dow)>
                                <label for="bs-fd-{{ $dow }}">{{ BranchSettings::dayLabel($dow) }}</label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bs-row">
                        <div>
                            <span class="bs-row-label">{{ __('Business day') }}</span>
                            <p class="bs-row-help">{{ __('Comes from the branch’s working hours, so it’s always in sync with what customers can book.') }}</p>
                        </div>
                        <div class="bs-control-end bs-hours">
                            @if ($hoursOpen && $hoursClose)
                                <span>
                                    <strong data-bs-time="{{ $hoursOpen }}">{{ $branch->formatTime($hoursOpen) }}</strong>
                                    –
                                    <strong data-bs-time="{{ $hoursClose }}">{{ $branch->formatTime($hoursClose) }}</strong>
                                </span>
                                <a href="{{ route('company.branches.working-hours.edit', $branch) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Edit working hours') }}</a>
                            @else
                                <span class="bs-warn">{{ __('No working hours set yet — customers can’t book until you add them.') }}</span>
                                <a href="{{ route('company.branches.working-hours.edit', $branch) }}" class="btn btn-sm btn-primary rounded-pill px-3">{{ __('Set working hours') }}</a>
                            @endif
                        </div>
                    </div>
                </section>

                <div class="bs-footer">
                    <span class="bs-dirty" data-bs-dirty hidden>{{ __('You have unsaved changes') }}</span>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 js-bs-save">
                        <i data-feather="check" style="width:15px;height:15px;" class="me-1"></i>{{ __('Save changes') }}
                    </button>
                </div>
            </div>

            {{-- Branch clock — live preview of the settings above --}}
            <div class="col-lg-4 order-1 order-lg-2">
                <aside class="bs-clock" aria-live="polite">
                    <p class="bs-clock-label">{{ __('Current local time') }}</p>
                    <div class="bs-clock-time" data-bs-clock>{{ $branch->formatTime($branch->localNow()) }}</div>
                    <div class="bs-clock-date" data-bs-date></div>

                    <dl>
                        <dt>{{ __('Time zone') }}</dt>
                        <dd data-bs-zone>{{ $tz }}</dd>
                        <dt>{{ __('UTC offset') }}</dt>
                        <dd data-bs-offset>{{ BranchSettings::offsetLabel($tz) }}</dd>
                    </dl>

                    <p class="bs-slots-title">{{ __('Start times customers will see') }}</p>
                    <div class="bs-slots" data-bs-slots></div>

                    @if ($otherBranches->isNotEmpty())
                        <ul class="bs-others" data-bs-others>
                            @foreach ($otherBranches as $other)
                                <li><span>{{ $other->localizedName() }}</span><span data-bs-other-tz="{{ $other->tz() }}"></span></li>
                            @endforeach
                        </ul>
                    @endif
                </aside>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var CFG  = @json($clockData);
    var form = document.getElementById('bs-form');
    if (!form) return;

    var tzSelect = document.getElementById('bs-timezone');

    function checked(name) {
        var el = form.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : null;
    }

    /* ── Time helpers (Intl does the IANA math — no offsets hard-coded) ── */
    function wallParts(tz, date) {
        var parts = {};
        new Intl.DateTimeFormat('en-US', {
            timeZone: tz, hour12: false, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        }).formatToParts(date).forEach(function (p) { parts[p.type] = p.value; });
        return { h: parseInt(parts.hour, 10) % 24, m: parseInt(parts.minute, 10) };
    }

    function offsetLabel(tz, date) {
        // Wall clock in the zone vs. UTC, in minutes → "UTC+03:00"
        var d = date || new Date();
        var p = {};
        new Intl.DateTimeFormat('en-US', {
            timeZone: tz, hour12: false, year: 'numeric', month: 'numeric', day: 'numeric',
            hour: 'numeric', minute: 'numeric', second: 'numeric'
        }).formatToParts(d).forEach(function (x) { p[x.type] = x.value; });
        var asUtc = Date.UTC(+p.year, +p.month - 1, +p.day, (+p.hour) % 24, +p.minute, +p.second);
        var mins  = Math.round((asUtc - Math.floor(d.getTime() / 1000) * 1000) / 60000);
        var sign  = mins < 0 ? '-' : '+';
        mins = Math.abs(mins);
        return 'UTC' + sign + String(Math.floor(mins / 60)).padStart(2, '0') + ':' + String(mins % 60).padStart(2, '0');
    }

    function fmtHM(h, m, format, html) {
        var mm = String(m).padStart(2, '0');
        if (format === '24h') return String(h).padStart(2, '0') + ':' + mm;
        var ap = h < 12 ? CFG.am : CFG.pm;
        var t  = (h % 12 || 12) + ':' + mm;
        return html ? t + '<span class="ap">' + ap + '</span>' : t + ' ' + ap;
    }

    function toMin(hhmm) { var p = hhmm.split(':'); return (+p[0]) * 60 + (+p[1]); }

    /* ── Live preview ─────────────────────────────────────────────── */
    var elClock  = document.querySelector('[data-bs-clock]');
    var elDate   = document.querySelector('[data-bs-date]');
    var elZone   = document.querySelector('[data-bs-zone]');
    var elOffset = document.querySelector('[data-bs-offset]');
    var elSlots  = document.querySelector('[data-bs-slots]');

    function renderClock() {
        var tz = tzSelect.value, fmt = checked('time_format'), now = new Date();
        var w  = wallParts(tz, now);
        elClock.innerHTML = fmtHM(w.h, w.m, fmt, true);
        elDate.textContent = new Intl.DateTimeFormat(CFG.locale === 'ar' ? 'ar-SY' : 'en-GB', {
            timeZone: tz, weekday: 'long', day: 'numeric', month: 'long'
        }).format(now);
        elZone.textContent   = tz;
        elOffset.textContent = offsetLabel(tz, now);

        document.querySelectorAll('[data-bs-other-tz]').forEach(function (el) {
            var o = wallParts(el.getAttribute('data-bs-other-tz'), now);
            el.textContent = fmtHM(o.h, o.m, fmt, false);
        });
        document.querySelectorAll('[data-bs-time]').forEach(function (el) {
            var mins = toMin(el.getAttribute('data-bs-time'));
            el.textContent = fmtHM(Math.floor(mins / 60), mins % 60, fmt, false);
        });
    }

    function renderSlots() {
        var fmt  = checked('time_format');
        var step = parseInt(document.getElementById('bs-interval').value, 10) || 15;
        var from = CFG.open ? toMin(CFG.open) : 9 * 60;
        var out  = [];
        for (var t = from, i = 0; i < 6 && t < 24 * 60; t += step, i++) {
            out.push('<span class="bs-slot">' + fmtHM(Math.floor(t / 60), t % 60, fmt, false) + '</span>');
        }
        elSlots.innerHTML = out.join('');
    }

    function renderAll() { renderClock(); renderSlots(); }

    /* ── Searchable time-zone combobox ────────────────────────────── */
    (function enhanceTimezone() {
        var wrap = document.querySelector('[data-bs-tz]');
        if (!wrap || !tzSelect) return;

        var options = Array.prototype.map.call(tzSelect.options, function (o) {
            return {
                id: o.value, city: o.getAttribute('data-city'), offset: o.getAttribute('data-offset'),
                group: o.parentNode.label, hay: (o.value + ' ' + o.getAttribute('data-city')).toLowerCase().replace(/_/g, ' ')
            };
        });

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'form-select rounded-3 bs-tz-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        btn.id = 'bs-timezone-btn';

        var pop = document.createElement('div');
        pop.className = 'bs-tz-pop';
        pop.hidden = true;
        pop.innerHTML = '<input type="search" class="form-control bs-tz-search" autocomplete="off" role="combobox" aria-expanded="true" aria-controls="bs-tz-list" aria-autocomplete="list">'
                      + '<ul class="bs-tz-list" id="bs-tz-list" role="listbox"></ul>';
        var search = pop.querySelector('input');
        var list   = pop.querySelector('ul');
        search.placeholder = CFG.strings.search;
        search.setAttribute('aria-label', CFG.strings.search);

        tzSelect.hidden = true;
        tzSelect.setAttribute('tabindex', '-1');
        document.querySelector('label[for="bs-timezone"]').setAttribute('for', 'bs-timezone-btn');
        wrap.appendChild(btn);
        wrap.appendChild(pop);

        var visible = [], active = -1;

        function paintButton() {
            var cur = options.find(function (o) { return o.id === tzSelect.value; }) || options[0];
            btn.innerHTML = '<span>' + esc(cur.city) + ' <span class="text-muted small" dir="ltr">' + esc(cur.id) + '</span></span>'
                          + '<span class="bs-tz-off">' + esc(cur.offset) + '</span>';
        }

        function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); }

        function paintList() {
            var q = search.value.trim().toLowerCase().replace(/_/g, ' ');
            visible = q ? options.filter(function (o) { return o.hay.indexOf(q) !== -1; }) : options;
            if (!visible.length) {
                list.innerHTML = '<li class="bs-tz-empty">' + esc(CFG.strings.noMatch) + '</li>';
                active = -1;
                return;
            }
            var html = '', lastGroup = null;
            visible.forEach(function (o, i) {
                if (o.group !== lastGroup) { html += '<li class="bs-tz-group" role="presentation">' + esc(o.group) + '</li>'; lastGroup = o.group; }
                html += '<li class="bs-tz-opt" role="option" id="bs-tz-o' + i + '" data-i="' + i + '" aria-selected="' + (o.id === tzSelect.value) + '">'
                      + '<span>' + esc(o.city) + ' <span class="id">' + esc(o.id) + '</span></span><span class="off">' + esc(o.offset) + '</span></li>';
            });
            list.innerHTML = html;
            var sel = visible.findIndex(function (o) { return o.id === tzSelect.value; });
            setActive(sel >= 0 ? sel : 0, true);
        }

        function setActive(i, center) {
            var prev = list.querySelector('.is-active');
            if (prev) prev.classList.remove('is-active');
            active = i;
            var el = list.querySelector('[data-i="' + i + '"]');
            if (!el) return;
            el.classList.add('is-active');
            search.setAttribute('aria-activedescendant', el.id);
            el.scrollIntoView({ block: center ? 'center' : 'nearest' });
        }

        function open() {
            pop.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            search.value = '';
            paintList();
            search.focus();
        }

        function close(focusBtn) {
            pop.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
            if (focusBtn) btn.focus();
        }

        function choose(i) {
            var o = visible[i];
            if (!o) return;
            tzSelect.value = o.id;
            tzSelect.dispatchEvent(new Event('change', { bubbles: true }));
            paintButton();
            close(true);
        }

        btn.addEventListener('click', function () { pop.hidden ? open() : close(false); });
        search.addEventListener('input', paintList);
        search.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(active + 1, visible.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
            else if (e.key === 'Enter') { e.preventDefault(); choose(active); }
            else if (e.key === 'Escape') { e.preventDefault(); close(true); }
        });
        list.addEventListener('mousedown', function (e) { e.preventDefault(); });
        list.addEventListener('click', function (e) {
            var li = e.target.closest('.bs-tz-opt');
            if (li) choose(parseInt(li.getAttribute('data-i'), 10));
        });
        document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) close(false); });

        paintButton();
    })();

    /* ── Unsaved changes + saving state ───────────────────────────── */
    var dirtyNote = document.querySelector('[data-bs-dirty]');
    var saveBtns  = document.querySelectorAll('.js-bs-save');
    var initial   = new URLSearchParams(new FormData(form)).toString();
    var submitting = false;

    function isDirty() { return new URLSearchParams(new FormData(form)).toString() !== initial; }

    form.addEventListener('change', function () {
        dirtyNote.hidden = !isDirty();
        renderAll();
    });
    form.addEventListener('submit', function () {
        submitting = true;
        saveBtns.forEach(function (b) { b.classList.add('is-loading'); b.disabled = true; });
    });
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && isDirty()) { e.preventDefault(); e.returnValue = ''; }
    });

    renderAll();
    setInterval(renderClock, 1000 * 15);
    if (typeof feather !== 'undefined') feather.replace();
})();
</script>
@endpush
