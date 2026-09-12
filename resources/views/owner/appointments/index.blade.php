@extends('owner.dashboard')
@section('content')
@include('owner.appointments.partials._ui')

@php
    $tz       = config('app.timezone');
    $currency = config('app.currency', 'SAR');

    // Sort links preserve every active filter, toggling direction on the active column.
    $sortUrl = function (string $field) use ($sortField, $sortDir) {
        $dir = ($sortField === $field && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $field, 'dir' => $dir, 'page' => 1]);
    };
    $sortCaret = fn (string $field) => $sortField === $field ? ($sortDir === 'asc' ? 'chevron-up' : 'chevron-down') : null;

    // Summary cards: each is a quick filter that merges its params into the URL.
    $cardUrl = fn (array $params) => request()->fullUrlWithQuery(array_merge($params, ['page' => 1]));

    $paymentOptions = [
        'paid'    => ['label' => __('Paid'),    'icon' => 'check-circle'],
        'pending' => ['label' => __('Pending'), 'icon' => 'clock'],
        'unpaid'  => ['label' => __('Unpaid'),  'icon' => 'alert-circle'],
    ];

    $hasAnyFilter = $filters['q'] !== '' || $filters['status'] || $filters['company_id']
        || $filters['branch_id'] || $filters['employee_id'] || $filters['payment'] || $filters['range'];

    // Toggleable columns (key => label) — the identity + actions columns are fixed.
    $toggleCols = [
        'cb'      => __('Company / Branch'),
        'customer'=> __('Customer'),
        'service' => __('Service'),
        'staff'   => __('Staff'),
        'when'    => __('Date & time'),
        'status'  => __('Status'),
        'payment' => __('Payment'),
    ];
@endphp

<div class="page-content am-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="am-head am-reveal">
        <div>
            <div class="am-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('Platform') }}
            </div>
            <h1 class="am-title">{{ __('Appointments') }}</h1>
            <p class="am-subtitle">{{ __('Every booking across all businesses and branches on GlowRez — filter, track and inspect appointments from one place.') }}</p>
        </div>
        <div class="am-head-actions">
            <div class="dropdown">
                <button type="button" class="am-btn am-btn-ghost dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i data-feather="sliders"></i>{{ __('Columns') }}
                </button>
                <div class="dropdown-menu dropdown-menu-end am-colmenu">
                    <div class="am-colmenu-head">{{ __('Visible columns') }}</div>
                    @foreach($toggleCols as $key => $label)
                        <label class="am-colrow">
                            <input type="checkbox" class="am-coltoggle" data-col="{{ $key }}" checked>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="button" class="am-btn am-btn-ghost {{ $hasAnyFilter ? 'is-active' : '' }}" id="am-open-filters">
                <i data-feather="filter"></i>{{ __('Advanced filters') }}
            </button>
            <a href="{{ route('owner.appointments.export', request()->query()) }}" class="am-btn am-btn-primary">
                <i data-feather="download"></i>{{ __('Export') }}
            </a>
        </div>
    </header>

    @include('owner.partials.flash')

    {{-- ═══════════ SUMMARY CARDS ═══════════ --}}
    <section class="am-stats am-reveal" aria-label="{{ __('Overview') }}">
        <a href="{{ $cardUrl(['range' => 'today', 'status' => null, 'date_from' => null, 'date_to' => null]) }}"
           class="am-stat {{ $filters['range'] === 'today' ? 'is-active' : '' }}" style="--accent:var(--bk-accent);">
            <span class="am-stat-label"><i data-feather="sun"></i>{{ __('Today') }}</span>
            <span class="am-stat-value">{{ number_format($stats['today']) }}</span>
        </a>
        <a href="{{ $cardUrl(['range' => 'week', 'status' => null, 'date_from' => null, 'date_to' => null]) }}"
           class="am-stat" style="--accent:var(--bk-info);">
            <span class="am-stat-label"><i data-feather="calendar"></i>{{ __('Upcoming') }}</span>
            <span class="am-stat-value">{{ number_format($stats['upcoming']) }}</span>
        </a>
        <a href="{{ $cardUrl(['status' => 'pending', 'range' => null, 'date_from' => null, 'date_to' => null]) }}"
           class="am-stat {{ $filters['status'] === 'pending' ? 'is-active' : '' }}" style="--accent:var(--bk-warning);">
            <span class="am-stat-label"><i data-feather="clock"></i>{{ __('Pending') }}</span>
            <span class="am-stat-value">{{ number_format($stats['pending']) }}</span>
        </a>
        <a href="{{ $cardUrl(['status' => 'completed', 'range' => null, 'date_from' => null, 'date_to' => null]) }}"
           class="am-stat {{ $filters['status'] === 'completed' ? 'is-active' : '' }}" style="--accent:var(--bk-success);">
            <span class="am-stat-label"><i data-feather="check-square"></i>{{ __('Completed') }}</span>
            <span class="am-stat-value">{{ number_format($stats['completed']) }}</span>
        </a>
        <a href="{{ $cardUrl(['status' => 'no_show', 'range' => null, 'date_from' => null, 'date_to' => null]) }}"
           class="am-stat {{ $filters['status'] === 'no_show' ? 'is-active' : '' }}" style="--accent:var(--bk-danger);">
            <span class="am-stat-label"><i data-feather="user-x"></i>{{ __('No-show') }}</span>
            <span class="am-stat-value">{{ number_format($stats['no_show']) }}</span>
        </a>
    </section>

    {{-- ═══════════ TOOLBAR ═══════════ --}}
    <form method="GET" action="{{ route('owner.appointments.index') }}" class="am-toolbar am-reveal" id="am-filter-form">
        {{-- Advanced filters are carried forward as hidden inputs so a quick
             toolbar change never silently drops them. --}}
        <input type="hidden" name="status"      value="{{ $filters['status'] }}">
        <input type="hidden" name="employee_id" value="{{ $filters['employee_id'] }}">
        <input type="hidden" name="payment"     value="{{ $filters['payment'] }}">
        <input type="hidden" name="range"    id="am-range"     value="{{ $filters['range'] }}">
        <input type="hidden" name="date_from" id="am-date-from" value="{{ $filters['date_from'] }}">
        <input type="hidden" name="date_to"   id="am-date-to"   value="{{ $filters['date_to'] }}">
        <input type="hidden" name="dir" id="am-dir-input" value="{{ $sortDir }}">

        <div class="am-toolbar-row">
            <div class="am-search">
                <button type="submit" class="am-search-btn" aria-label="{{ __('Search') }}" tabindex="-1"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       placeholder="{{ __('Search by customer, phone or reference…') }}"
                       autocomplete="off" aria-label="{{ __('Search appointments') }}"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}">
            </div>

            <div class="am-select-icon">
                <i data-feather="briefcase"></i>
                <select name="company_id" class="am-select" data-am-company aria-label="{{ __('Company') }}"
                        onchange="document.getElementById('am-filter-form').submit()">
                    <option value="">{{ __('All companies') }}</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" @selected((string) $filters['company_id'] === (string) $c->id)>{{ $c->localizedName() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="am-select-icon">
                <i data-feather="map-pin"></i>
                <select name="branch_id" class="am-select" data-am-branch aria-label="{{ __('Branch') }}"
                        onchange="document.getElementById('am-filter-form').submit()">
                    <option value="">{{ __('All branches') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" data-company="{{ $b->company_id }}"
                                @selected((string) $filters['branch_id'] === (string) $b->id)>{{ $b->localizedName() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="am-toolbar-row">
            <div class="am-chips" role="group" aria-label="{{ __('Date range') }}">
                <button type="button" class="am-chip {{ $filters['range'] === '' && !$filters['date_from'] ? 'is-active' : '' }}" data-range="">{{ __('All dates') }}</button>
                <button type="button" class="am-chip {{ $filters['range'] === 'today' ? 'is-active' : '' }}" data-range="today"><i data-feather="sun"></i>{{ __('Today') }}</button>
                <button type="button" class="am-chip {{ $filters['range'] === 'tomorrow' ? 'is-active' : '' }}" data-range="tomorrow">{{ __('Tomorrow') }}</button>
                <button type="button" class="am-chip {{ $filters['range'] === 'week' ? 'is-active' : '' }}" data-range="week">{{ __('This week') }}</button>
                <button type="button" class="am-chip {{ $filters['range'] === 'custom' ? 'is-active' : '' }}" id="am-open-filters-2"><i data-feather="calendar"></i>{{ __('Custom') }}</button>
            </div>

            <div style="flex:1 1 auto;"></div>

            <select name="sort" class="am-select" aria-label="{{ __('Sort by') }}"
                    onchange="document.getElementById('am-filter-form').submit()">
                <option value="start_time"  @selected($sortField === 'start_time')>{{ __('Appointment time') }}</option>
                <option value="created_at"  @selected($sortField === 'created_at')>{{ __('Recently added') }}</option>
                <option value="total_price" @selected($sortField === 'total_price')>{{ __('Price') }}</option>
            </select>
            <button type="button" class="am-dir" id="am-dir-btn"
                    title="{{ $sortDir === 'asc' ? __('Ascending') : __('Descending') }}"
                    aria-label="{{ $sortDir === 'asc' ? __('Ascending') : __('Descending') }}">
                <i data-feather="{{ $sortDir === 'asc' ? 'arrow-up' : 'arrow-down' }}"></i>
            </button>

            @if($hasAnyFilter)
                <a href="{{ route('owner.appointments.index') }}" class="am-clear"><i data-feather="x"></i>{{ __('Clear') }}</a>
            @endif
        </div>
    </form>

    {{-- ═══════════ TABLE ═══════════ --}}
    <div class="am-card am-reveal">
        <div class="am-table-scroll">
            <table class="am-table" id="am-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ $sortUrl('created_at') }}" class="am-sort {{ $sortField === 'created_at' ? 'is-active' : '' }}">
                                {{ __('Appointment') }}
                                @if($ic = $sortCaret('created_at'))<i data-feather="{{ $ic }}" class="am-sort-caret"></i>@endif
                            </a>
                        </th>
                        <th class="am-c-cb">{{ __('Company / Branch') }}</th>
                        <th class="am-c-customer">{{ __('Customer') }}</th>
                        <th class="am-c-service">{{ __('Service') }}</th>
                        <th class="am-c-staff am-col-staff">{{ __('Staff') }}</th>
                        <th class="am-c-when">
                            <a href="{{ $sortUrl('start_time') }}" class="am-sort {{ $sortField === 'start_time' ? 'is-active' : '' }}">
                                {{ __('Date & time') }}
                                @if($ic = $sortCaret('start_time'))<i data-feather="{{ $ic }}" class="am-sort-caret"></i>@endif
                            </a>
                        </th>
                        <th class="am-c-status">{{ __('Status') }}</th>
                        <th class="am-c-payment am-col-payment">{{ __('Payment') }}</th>
                        <th class="am-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $a)
                        @php
                            $color   = $a->status?->color() ?? '#94a3b8';
                            $payKey  = in_array($a->payment_status, ['paid','pending','unpaid'], true) ? $a->payment_status : 'other';
                            $payMeta = ['paid' => ['cls' => 'am-pay-paid', 'icon' => 'check-circle'],
                                        'pending' => ['cls' => 'am-pay-pending', 'icon' => 'clock'],
                                        'unpaid' => ['cls' => 'am-pay-other', 'icon' => 'alert-circle'],
                                        'other' => ['cls' => 'am-pay-other', 'icon' => 'circle']][$payKey];
                        @endphp
                        <tr data-detail-url="{{ route('owner.appointments.detail', $a) }}" tabindex="0" role="button"
                            aria-label="{{ __('View appointment') }} {{ $a->reference ?? ('#'.$a->id) }}">
                            {{-- Appointment identity --}}
                            <td>
                                <div class="am-ref">
                                    <span class="am-ref-ic" aria-hidden="true"><i data-feather="calendar"></i></span>
                                    <div style="min-width:0;">
                                        <div class="am-ref-code">{{ $a->reference ?? ('#'.$a->id) }}</div>
                                        <div class="am-ref-sub">{{ __('Added') }} {{ $a->created_at?->timezone($tz)->format('Y-m-d') }}</div>
                                    </div>
                                </div>
                            </td>
                            {{-- Company / Branch --}}
                            <td class="am-c-cb">
                                <div class="am-cb-company"><i data-feather="briefcase"></i>{{ $a->company?->localizedName() ?? $a->branch?->company?->localizedName() ?? '—' }}</div>
                                <div class="am-cb-branch"><i data-feather="map-pin"></i>{{ $a->branch?->localizedName() ?? '—' }}</div>
                            </td>
                            {{-- Customer --}}
                            <td class="am-c-customer">
                                <div class="am-person">{{ $a->displayName() }}</div>
                                @if($a->customer_phone || $a->customer?->phone)
                                    <div class="am-person-sub" dir="ltr">{{ $a->customer_phone ?? $a->customer?->phone }}</div>
                                @endif
                            </td>
                            {{-- Service --}}
                            <td class="am-c-service">{{ $a->service?->localizedName() ?? '—' }}</td>
                            {{-- Staff --}}
                            <td class="am-c-staff am-col-staff">
                                @if($a->employee)
                                    <span class="am-person">{{ $a->employee->localizedName() }}</span>
                                @else
                                    <span class="am-dash">—</span>
                                @endif
                            </td>
                            {{-- Date & time --}}
                            <td class="am-c-when">
                                @if($a->start_time)
                                    <div class="am-when"><span class="am-when-date">{{ $a->start_time->timezone($tz)->format('M j, Y') }}</span></div>
                                    <div class="am-when am-when-time">{{ $a->start_time->timezone($tz)->format('H:i') }}</div>
                                @else
                                    <span class="am-dash">—</span>
                                @endif
                            </td>
                            {{-- Status --}}
                            <td class="am-c-status">
                                @if($a->status)
                                    <span class="am-status" style="color:{{ $color }};background:color-mix(in srgb, {{ $color }} 13%, transparent);border-color:color-mix(in srgb, {{ $color }} 32%, transparent);">
                                        <i data-feather="{{ $a->status->icon() }}"></i>{{ $a->status->label() }}
                                    </span>
                                @else
                                    <span class="am-dash">—</span>
                                @endif
                            </td>
                            {{-- Payment --}}
                            <td class="am-c-payment am-col-payment">
                                <span class="am-pay {{ $payMeta['cls'] }}"><i data-feather="{{ $payMeta['icon'] }}"></i>{{ __(ucfirst($a->payment_status)) }}</span>
                            </td>
                            {{-- Actions --}}
                            <td class="am-end" onclick="event.stopPropagation()">
                                <div class="am-actions">
                                    <button type="button" class="am-act am-act-primary am-open-detail" data-detail-url="{{ route('owner.appointments.detail', $a) }}"
                                            title="{{ __('Quick view') }}" aria-label="{{ __('Quick view') }}">
                                        <i data-feather="eye"></i>
                                    </button>
                                    <a href="{{ route('owner.appointments.show', $a) }}" class="am-act"
                                       title="{{ __('Full details') }}" aria-label="{{ __('Full details') }}">
                                        <i data-feather="external-link"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="am-empty">
                                    <span class="am-empty-ic"><i data-feather="calendar"></i></span>
                                    <p class="am-empty-title">{{ __('No appointments found') }}</p>
                                    <p class="am-empty-sub">
                                        @if($hasAnyFilter)
                                            {{ __('No appointments match your current filters. Try widening the date range or clearing filters.') }}
                                        @else
                                            {{ __('Appointments booked across your businesses will appear here.') }}
                                        @endif
                                    </p>
                                    @if($hasAnyFilter)
                                        <a href="{{ route('owner.appointments.index') }}" class="am-btn am-btn-ghost"><i data-feather="x"></i>{{ __('Clear filters') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($appointments->total() > 0)
            <div class="am-pagination">
                <div class="am-pagination-left">
                    <div class="am-pagination-info">
                        {{ __('Showing :from–:to of :total', ['from' => $appointments->firstItem(), 'to' => $appointments->lastItem(), 'total' => $appointments->total()]) }}
                    </div>
                    <label class="am-perpage">
                        <span>{{ __('Per page') }}</span>
                        <select class="am-select am-select-sm" aria-label="{{ __('Rows per page') }}"
                                onchange="window.location=this.value">
                            @foreach($perPageOptions as $opt)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $opt, 'page' => 1]) }}"
                                        @selected($filters['per_page'] === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                @if($appointments->hasPages())
                    {{ $appointments->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ═══════════ DETAIL DRAWER ═══════════ --}}
<div class="am-scrim" id="am-detail-scrim"></div>
<aside class="am-drawer" id="am-detail-drawer" role="dialog" aria-modal="true" aria-label="{{ __('Appointment details') }}" tabindex="-1">
    <div id="am-detail-content" style="display:flex; flex-direction:column; height:100%;">
        <div class="am-drawer-loading"><span class="am-spinner"></span><span>{{ __('Loading…') }}</span></div>
    </div>
</aside>

{{-- ═══════════ ADVANCED FILTERS DRAWER ═══════════ --}}
<div class="am-scrim" id="am-filters-scrim"></div>
<aside class="am-drawer" id="am-filters-drawer" role="dialog" aria-modal="true" aria-label="{{ __('Advanced filters') }}" tabindex="-1">
    <form method="GET" action="{{ route('owner.appointments.index') }}" style="display:flex; flex-direction:column; height:100%;">
        {{-- Carry forward search + sort so applying advanced filters keeps them. --}}
        <input type="hidden" name="q"    value="{{ $filters['q'] }}">
        <input type="hidden" name="sort" value="{{ $sortField }}">
        <input type="hidden" name="dir"  value="{{ $sortDir }}">

        <div class="am-drawer-head">
            <div>
                <div class="am-drawer-eyebrow">{{ __('Refine') }}</div>
                <h2 class="am-drawer-title">{{ __('Advanced filters') }}</h2>
            </div>
            <button type="button" class="am-drawer-close" data-close-filters aria-label="{{ __('Close') }}"><i data-feather="x"></i></button>
        </div>

        <div class="am-drawer-body">
            <div class="am-fform">
                <div class="am-field">
                    <label class="am-field-label">{{ __('Company') }}</label>
                    <select name="company_id" class="am-select" data-am-company>
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" @selected((string) $filters['company_id'] === (string) $c->id)>{{ $c->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="am-field">
                    <label class="am-field-label">{{ __('Branch') }}</label>
                    <select name="branch_id" class="am-select" data-am-branch>
                        <option value="">{{ __('All branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" data-company="{{ $b->company_id }}"
                                    @selected((string) $filters['branch_id'] === (string) $b->id)>{{ $b->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="am-field">
                    <label class="am-field-label">{{ __('Status') }}</label>
                    <select name="status" class="am-select">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" @selected($filters['status'] === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="am-field">
                    <label class="am-field-label">{{ __('Staff') }}</label>
                    <select name="employee_id" class="am-select" {{ $filters['company_id'] ? '' : 'disabled' }}>
                        @if($filters['company_id'])
                            <option value="">{{ __('All staff') }}</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" @selected((string) $filters['employee_id'] === (string) $e->id)>{{ $e->localizedName() }}</option>
                            @endforeach
                        @else
                            <option value="">{{ __('Choose a company first') }}</option>
                        @endif
                    </select>
                </div>

                <div class="am-field">
                    <label class="am-field-label">{{ __('Payment') }}</label>
                    <select name="payment" class="am-select">
                        <option value="">{{ __('Any payment') }}</option>
                        @foreach($paymentOptions as $val => $opt)
                            <option value="{{ $val }}" @selected($filters['payment'] === $val)>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="am-field">
                    <label class="am-field-label">{{ __('Date range') }}</label>
                    <select name="range" class="am-select" id="am-adv-range">
                        <option value=""         @selected($filters['range'] === '')>{{ __('All dates') }}</option>
                        <option value="today"    @selected($filters['range'] === 'today')>{{ __('Today') }}</option>
                        <option value="tomorrow" @selected($filters['range'] === 'tomorrow')>{{ __('Tomorrow') }}</option>
                        <option value="week"     @selected($filters['range'] === 'week')>{{ __('This week') }}</option>
                        <option value="custom"   @selected($filters['range'] === 'custom')>{{ __('Custom range') }}</option>
                    </select>
                </div>

                <div class="am-field" id="am-custom-dates" style="{{ $filters['range'] === 'custom' ? '' : 'display:none;' }}">
                    <label class="am-field-label">{{ __('Custom dates') }}</label>
                    <div class="am-field-2">
                        <input type="date" name="date_from" value="{{ $filters['range'] === 'custom' ? $filters['date_from'] : '' }}" aria-label="{{ __('From') }}">
                        <input type="date" name="date_to"   value="{{ $filters['range'] === 'custom' ? $filters['date_to'] : '' }}" aria-label="{{ __('To') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="am-drawer-foot">
            <a href="{{ route('owner.appointments.index') }}" class="am-btn am-btn-ghost"><i data-feather="rotate-ccw"></i>{{ __('Clear all') }}</a>
            <button type="submit" class="am-btn am-btn-primary"><i data-feather="check"></i>{{ __('Apply filters') }}</button>
        </div>
    </form>
</aside>

@push('scripts')
<script>
(function () {
    'use strict';
    var form = document.getElementById('am-filter-form');

    /* ── Branch cascade: each [data-am-branch] narrows to its [data-am-company] ── */
    function wireCascade(scope) {
        var company = scope.querySelector('[data-am-company]');
        var branch  = scope.querySelector('[data-am-branch]');
        if (!company || !branch) return;
        function sync(resetIfHidden) {
            var cid = company.value;
            Array.prototype.forEach.call(branch.options, function (opt) {
                if (!opt.value) return; // keep "All branches"
                var match = !cid || opt.getAttribute('data-company') === cid;
                opt.hidden = !match;
                if (!match && opt.selected && resetIfHidden) { branch.value = ''; }
            });
        }
        sync(false);
        company.addEventListener('change', function () { sync(true); });
    }
    if (form) wireCascade(form);
    var filtersForm = document.querySelector('#am-filters-drawer form');
    if (filtersForm) wireCascade(filtersForm);

    /* ── Date chips (toolbar) ── */
    document.querySelectorAll('.am-chips .am-chip[data-range]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.getElementById('am-range').value = this.getAttribute('data-range');
            document.getElementById('am-date-from').value = '';
            document.getElementById('am-date-to').value = '';
            form.submit();
        });
    });

    /* ── Sort direction toggle ── */
    var dirBtn = document.getElementById('am-dir-btn');
    var dirInp = document.getElementById('am-dir-input');
    if (dirBtn && dirInp) {
        dirBtn.addEventListener('click', function () {
            dirInp.value = dirInp.value === 'asc' ? 'desc' : 'asc';
            form.submit();
        });
    }

    /* ── Advanced-filters custom-date toggle ── */
    var advRange = document.getElementById('am-adv-range');
    var customBox = document.getElementById('am-custom-dates');
    if (advRange && customBox) {
        advRange.addEventListener('change', function () {
            customBox.style.display = this.value === 'custom' ? '' : 'none';
        });
    }

    /* ── Generic drawer open/close ── */
    function openDrawer(drawer, scrim) {
        scrim.classList.add('is-open');
        drawer.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        drawer.focus();
    }
    function closeDrawer(drawer, scrim) {
        scrim.classList.remove('is-open');
        drawer.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    /* Advanced filters drawer */
    var fDrawer = document.getElementById('am-filters-drawer');
    var fScrim  = document.getElementById('am-filters-scrim');
    function openFilters(range) {
        if (range && advRange) { advRange.value = range; advRange.dispatchEvent(new Event('change')); }
        openDrawer(fDrawer, fScrim);
    }
    var btnF = document.getElementById('am-open-filters');
    var btnF2 = document.getElementById('am-open-filters-2');
    if (btnF)  btnF.addEventListener('click', function () { openFilters(); });
    if (btnF2) btnF2.addEventListener('click', function () { openFilters('custom'); });
    fScrim.addEventListener('click', function () { closeDrawer(fDrawer, fScrim); });
    document.querySelectorAll('[data-close-filters]').forEach(function (b) {
        b.addEventListener('click', function () { closeDrawer(fDrawer, fScrim); });
    });

    /* Detail drawer (AJAX) */
    var dDrawer  = document.getElementById('am-detail-drawer');
    var dScrim   = document.getElementById('am-detail-scrim');
    var dContent = document.getElementById('am-detail-content');
    var loadingHtml = dContent.innerHTML;

    function openDetail(url) {
        dContent.innerHTML = loadingHtml;
        openDrawer(dDrawer, dScrim);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                dContent.innerHTML = html;
                if (typeof feather !== 'undefined') feather.replace();
                dContent.querySelectorAll('[data-close-detail]').forEach(function (b) {
                    b.addEventListener('click', function () { closeDrawer(dDrawer, dScrim); });
                });
            })
            .catch(function () {
                dContent.innerHTML = '<div class="am-drawer-loading"><span>{{ __('Could not load appointment.') }}</span></div>';
            });
    }
    dScrim.addEventListener('click', function () { closeDrawer(dDrawer, dScrim); });

    // Row + quick-view button open the detail drawer.
    document.querySelectorAll('#am-table tbody tr[data-detail-url]').forEach(function (tr) {
        tr.addEventListener('click', function () { openDetail(this.getAttribute('data-detail-url')); });
        tr.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDetail(this.getAttribute('data-detail-url')); }
        });
    });
    document.querySelectorAll('.am-open-detail').forEach(function (btn) {
        btn.addEventListener('click', function (e) { e.stopPropagation(); openDetail(this.getAttribute('data-detail-url')); });
    });

    /* Esc closes whichever drawer is open */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (dDrawer.classList.contains('is-open')) closeDrawer(dDrawer, dScrim);
        if (fDrawer.classList.contains('is-open')) closeDrawer(fDrawer, fScrim);
    });

    /* ── Column visibility (persisted per browser) ── */
    var STORE = 'am_appt_cols';
    function applyCol(key, visible) {
        document.querySelectorAll('.am-c-' + key).forEach(function (el) { el.hidden = !visible; });
    }
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(STORE) || '{}'); } catch (e) {}
    document.querySelectorAll('.am-coltoggle').forEach(function (cb) {
        var key = cb.getAttribute('data-col');
        if (saved[key] === false) { cb.checked = false; applyCol(key, false); }
        cb.addEventListener('change', function () {
            applyCol(key, cb.checked);
            saved[key] = cb.checked;
            try { localStorage.setItem(STORE, JSON.stringify(saved)); } catch (e) {}
        });
    });

    if (typeof feather !== 'undefined') setTimeout(function () { feather.replace(); }, 60);
})();
</script>
@endpush
@endsection
