@extends('company.dashboard')

@push('company-styles')
{{-- FullCalendar v6 injects its own CSS from the JS bundle — it ships no
     stylesheet (v5 did, as main.css). The old <link> here 404'd on every load. --}}
<link rel="stylesheet" href="{{ asset('backend/assets/css/appointments-page.css') }}?v={{ @filemtime(public_path('backend/assets/css/appointments-page.css')) ?: '1' }}">
@endpush

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';

    // Colours, labels and legal moves all come from the enum + state machine.
    // Nothing about status is defined in this template any more.
    $statusDefs   = \App\Enums\AppointmentStatus::forFrontend();
    $allowedMoves = \App\States\AppointmentStateMachine::allowedMapFor(\App\Enums\TransitionActor::Company);
    $liveStatuses = \App\Enums\AppointmentStatus::blockingValues();
@endphp

<div class="page-content bk-appt-page">

    {{-- ── Page header ── --}}
    <div class="bk-page-header">
        <div class="bk-header-left">
            <div class="bk-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div>
                <h4 class="mb-0 fw-bold" style="color:var(--cal-text);font-size:1.15rem;">{{ __('Appointments') }}</h4>
                <div class="bk-today-label">
                    {{ $isRtl ? now()->locale('ar')->translatedFormat('l، j F Y') : now()->format('l, F j, Y') }}
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- View tabs --}}
            <div class="bk-view-tabs">
                <button class="bk-vtab active" id="tab-cal" onclick="switchView('cal')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ $isRtl ? 'التقويم' : 'Calendar' }}
                </button>
                <button class="bk-vtab" id="tab-staff" onclick="switchView('staff')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    {{ $isRtl ? 'الموظفون' : 'Staff' }}
                </button>
                <button class="bk-vtab" id="tab-list" onclick="switchView('list')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1.5" fill="currentColor"/><circle cx="3" cy="12" r="1.5" fill="currentColor"/><circle cx="3" cy="18" r="1.5" fill="currentColor"/></svg>
                    {{ $isRtl ? 'القائمة' : 'List' }}
                </button>
            </div>

            {{-- Waitlist chip --}}
            <button type="button" class="bk-wl-chip" id="bk-wl-chip" aria-label="{{ __('Waitlist') }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ $isRtl ? 'الانتظار' : 'Waitlist' }}
                <span class="bk-wl-count" id="bk-wl-count">0</span>
            </button>

            {{-- Unified "+" add menu (booking / group / waitlist / block) --}}
            <div class="bk-add-wrap">
                <button type="button" class="bk-new-appt-btn" id="bk-add-btn" aria-expanded="false" aria-haspopup="menu">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ $isRtl ? 'جديد' : 'Add' }}
                    <svg class="bk-add-caret" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="bk-add-menu d-none" id="bk-add-menu" role="menu">
                    <button type="button" data-bk-add="booking" role="menuitem">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="12" y1="10" x2="12" y2="16"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
                        <div>
                            <b>{{ $isRtl ? 'موعد جديد' : 'New appointment' }}</b>
                            <small>{{ $isRtl ? 'حجز لزبون واحد' : 'Single customer booking' }}</small>
                        </div>
                    </button>
                    <button type="button" data-bk-add="group" role="menuitem">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <div>
                            <b>{{ $isRtl ? 'موعد جماعي' : 'Group appointment' }}</b>
                            <small>{{ $isRtl ? 'عدة زبائن معاً (عائلة، عرائس...)' : 'Several customers together' }}</small>
                        </div>
                    </button>
                    <button type="button" data-bk-add="waitlist" role="menuitem">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div>
                            <b>{{ $isRtl ? 'إضافة للانتظار' : 'Add to waitlist' }}</b>
                            <small>{{ $isRtl ? 'زبون ينتظر شاغراً' : 'Customer waiting for a slot' }}</small>
                        </div>
                    </button>
                    <button type="button" data-bk-add="block" role="menuitem">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <div>
                            <b>{{ $isRtl ? 'حجب وقت' : 'Block time' }}</b>
                            <small>{{ $isRtl ? 'استراحة، صيانة، مناسبة خاصة' : 'Break, maintenance, private event' }}</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- ══ Unfinished booking banner (inline, not a popup) ══ --}}
    <div id="bk-resume" class="d-none" role="region" aria-label="{{ $isRtl ? 'حجز غير مكتمل' : 'Unfinished booking' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
        <div class="bk-resume-txt">
            <b>{{ $isRtl ? 'لديك حجز لم يكتمل' : 'You have an unfinished booking' }}</b>
            <small id="bk-resume-sub"></small>
        </div>
        <button type="button" id="bk-resume-go">{{ $isRtl ? 'متابعة' : 'Resume' }}</button>
        <button type="button" id="bk-resume-x" class="ghost">{{ $isRtl ? 'تجاهل' : 'Discard' }}</button>
    </div>

    {{-- ══ Block-time modal ══ --}}
    <div class="bk-ov d-none" id="bk-block-ov">
        <div class="bk-ov-card" role="dialog" aria-modal="true" aria-labelledby="bk-block-title">
            <div class="bk-ov-head">
                <div class="bk-ov-title" id="bk-block-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    {{ $isRtl ? 'حجب وقت' : 'Block time' }}
                </div>
                <button type="button" class="bk-ov-x" data-bk-close="bk-block-ov" aria-label="{{ __('Close') }}">✕</button>
            </div>
            <div class="bk-ov-body">
                <div class="bk-ov-grid">
                    <label class="bk-ov-field">
                        <span>{{ $isRtl ? 'الفرع' : 'Branch' }}</span>
                        <select id="blk-branch">
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="bk-ov-field">
                        <span>{{ $isRtl ? 'الموظف' : 'Employee' }}</span>
                        <select id="blk-employee">
                            <option value="">{{ $isRtl ? 'كل الفرع' : 'Whole branch' }}</option>
                        </select>
                    </label>
                    <label class="bk-ov-field">
                        <span>{{ $isRtl ? 'التاريخ' : 'Date' }}</span>
                        <input type="date" id="blk-date" value="{{ now()->toDateString() }}">
                    </label>
                    <div class="bk-ov-2col">
                        <label class="bk-ov-field">
                            <span>{{ $isRtl ? 'من' : 'From' }}</span>
                            <input type="time" id="blk-from" value="13:00">
                        </label>
                        <label class="bk-ov-field">
                            <span>{{ $isRtl ? 'إلى' : 'To' }}</span>
                            <input type="time" id="blk-to" value="14:00">
                        </label>
                    </div>
                    <label class="bk-ov-field">
                        <span>{{ $isRtl ? 'السبب (اختياري)' : 'Reason (optional)' }}</span>
                        <input type="text" id="blk-reason" maxlength="190"
                               placeholder="{{ $isRtl ? 'استراحة غداء، صيانة، مناسبة خاصة…' : 'Lunch break, maintenance, private event…' }}">
                    </label>
                </div>
                <div class="bk-ov-err d-none" id="blk-err"></div>
                <button type="button" class="bk-ov-submit" id="blk-save">
                    {{ $isRtl ? 'حجب الوقت' : 'Block this time' }}
                </button>

                <div class="bk-ov-sep">
                    <span>{{ $isRtl ? 'الأوقات المحجوبة بهذا اليوم' : 'Blocked times on this day' }}</span>
                </div>
                <div id="blk-list" class="bk-blk-list">
                    <div class="bk-ov-empty">{{ $isRtl ? 'لا يوجد أوقات محجوبة' : 'No blocked times' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Waitlist drawer ══ --}}
    <div class="bk-drawer-ov d-none" id="bk-wl-ov"></div>
    <aside class="bk-drawer" id="bk-wl-drawer" aria-hidden="true" aria-label="{{ __('Waitlist') }}">
        <div class="bk-drawer-head">
            <div class="bk-ov-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ $isRtl ? 'قائمة الانتظار' : 'Waitlist' }}
                <span class="bk-wl-count" id="bk-wl-count2">0</span>
            </div>
            <button type="button" class="bk-ov-x" id="bk-wl-close" aria-label="{{ __('Close') }}">✕</button>
        </div>

        {{--
            Add flow, progressive: one field to start. The old form put six
            equal-weight inputs on screen at once, which made adding a walk-in
            feel like filing paperwork. Everything past "who" stays hidden until
            a customer is chosen, because none of it is answerable before then.
        --}}
        <div class="wl-add" id="wl-add">

            {{-- Step 1 — who --}}
            <div class="wl-field">
                <label class="wl-label" for="wl-search">{{ $isRtl ? 'الزبون' : 'Customer' }}</label>
                <div class="wl-search">
                    <svg class="wl-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input id="wl-search" type="text" autocomplete="off" maxlength="255"
                           role="combobox" aria-expanded="false" aria-controls="wl-results" aria-autocomplete="list"
                           placeholder="{{ $isRtl ? 'ابحث بالاسم أو الهاتف…' : 'Search name or phone…' }}">
                    <button type="button" class="wl-clear d-none" id="wl-clear"
                            aria-label="{{ $isRtl ? 'مسح' : 'Clear' }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="wl-results d-none" id="wl-results" role="listbox"
                     aria-label="{{ $isRtl ? 'نتائج البحث' : 'Search results' }}"></div>
            </div>

            {{-- Chosen customer, with their tier --}}
            <div class="wl-picked d-none" id="wl-picked"></div>

            {{-- Quick-add: only appears when the search finds nobody --}}
            <div class="wl-newbox d-none" id="wl-newbox">
                <label class="wl-label" for="wl-phone">
                    {{ $isRtl ? 'رقم الهاتف' : 'Phone number' }}
                    <span class="wl-optional">{{ $isRtl ? 'اختياري' : 'optional' }}</span>
                </label>
                <input id="wl-phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="30"
                       placeholder="{{ $isRtl ? '09xxxxxxxx' : '09xxxxxxxx' }}">
                <p class="wl-help">{{ $isRtl ? 'برقم الهاتف يُحفظ الزبون في السجل وتُحتسب زياراته تلقائياً.' : 'With a phone number the customer is saved and their visits start counting.' }}</p>
            </div>

            {{-- Step 2 — revealed once we know who --}}
            <div class="wl-rest" id="wl-rest" hidden>

                <div class="wl-field">
                    <span class="wl-label" id="wl-prio-label">{{ $isRtl ? 'الأولوية' : 'Priority' }}</span>
                    <div class="wl-seg" id="wl-priority" role="radiogroup" aria-labelledby="wl-prio-label"></div>
                </div>

                <div class="wl-row">
                    <div class="wl-field">
                        <label class="wl-label" for="wl-branch">{{ $isRtl ? 'الفرع' : 'Branch' }}</label>
                        <select id="wl-branch">
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wl-field">
                        <label class="wl-label" for="wl-service">{{ $isRtl ? 'الخدمة' : 'Service' }}</label>
                        <select id="wl-service">
                            <option value="">{{ $isRtl ? 'أي خدمة' : 'Any service' }}</option>
                        </select>
                    </div>
                </div>

                <details class="wl-more">
                    <summary>{{ $isRtl ? 'تفاصيل إضافية' : 'More details' }}</summary>
                    <div class="wl-row" style="margin-top:10px;">
                        <div class="wl-field">
                            <label class="wl-label" for="wl-minutes">{{ $isRtl ? 'المدة المتوقعة' : 'Expected duration' }}</label>
                            <div class="wl-suffix">
                                <input id="wl-minutes" type="number" inputmode="numeric" min="5" max="600" step="5"
                                       placeholder="{{ $isRtl ? 'من الخدمة' : 'from service' }}">
                                <span>{{ $isRtl ? 'دقيقة' : 'min' }}</span>
                            </div>
                        </div>
                        <div class="wl-field">
                            <label class="wl-label" for="wl-employee">{{ $isRtl ? 'الموظف المفضّل' : 'Preferred staff' }}</label>
                            <select id="wl-employee">
                                <option value="">{{ $isRtl ? 'أي موظف' : 'Anyone' }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="wl-field" style="margin-top:10px;">
                        <label class="wl-label" for="wl-notes">{{ $isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <input id="wl-notes" type="text" maxlength="500">
                    </div>
                </details>

                <div class="wl-err d-none" id="wl-err" role="alert" aria-live="polite"></div>

                <button type="button" class="wl-save" id="wl-save">
                    <span class="wl-save-txt">{{ $isRtl ? 'أضف للانتظار' : 'Add to waitlist' }}</span>
                </button>
            </div>
        </div>

        <div class="bk-wl-list" id="bk-wl-list">
            <div class="bk-ov-empty">{{ $isRtl ? 'لا يوجد أحد بالانتظار' : 'Nobody is waiting' }}</div>
        </div>
    </aside>

    {{-- ── Filters bar ── --}}
    <div class="bk-topbar">

        {{-- Branch selector --}}
        <div class="bk-filter-group">
            <div class="bk-filter-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <select id="filter-branch">
                <option value="">{{ __('All branches') }}</option>
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                        {{ $b->localizedName() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="bk-divider d-none d-md-block"></div>

        {{-- Search --}}
        <div class="bk-search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--cal-text-muted)" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="bk-search" type="text" placeholder="{{ $isRtl ? 'بحث باسم الزبون أو الخدمة...' : 'Search customer or service...' }}">
        </div>

        <div class="bk-divider d-none d-md-block"></div>

        {{-- Status pills --}}
        {{-- Label colour is the normal text colour, not the status colour: the
             old `color:#f59e0b` on a `#f59e0b22` background sat around 2:1
             contrast, well under the 4.5:1 WCAG AA floor. The dot carries the
             colour coding instead, where contrast does not apply. --}}
        <div class="d-flex flex-wrap gap-1" id="status-filters" role="group"
             aria-label="{{ $isRtl ? 'تصفية حسب الحالة' : 'Filter by status' }}">
            @foreach($statusDefs as $st => $sc)
                <button class="bk-st-pill" data-status="{{ $st }}" aria-pressed="false"
                    style="background:{{ $sc['color'] }}1a;color:var(--cal-text);border-color:{{ $sc['color'] }}59;">
                    <span class="dot" style="background:{{ $sc['color'] }};"></span>
                    {{ $sc['label'] }}
                </button>
            @endforeach
        </div>

        <div class="bk-divider d-none d-md-block"></div>

        {{-- Sort --}}
        <div class="bk-filter-group">
            <div class="bk-filter-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
            </div>
            <select id="filter-sort">
                <option value="closest">{{ $isRtl ? 'الأقرب أولاً' : 'Closest first' }}</option>
                <option value="farthest">{{ $isRtl ? 'الأبعد أولاً' : 'Farthest first' }}</option>
                <option value="newest">{{ $isRtl ? 'الأحدث أولاً' : 'Newest first' }}</option>
                <option value="price-high">{{ $isRtl ? 'السعر: الأعلى' : 'Price: High' }}</option>
                <option value="price-low">{{ $isRtl ? 'السعر: الأقل' : 'Price: Low' }}</option>
            </select>
        </div>
    </div>

    {{-- ══ STAFF VIEW ══ --}}
    <div id="view-staff" class="d-none">
        <div id="bk-staff-shell" style="background:var(--cal-surface);border-radius:var(--cal-radius);border:1px solid var(--cal-border);box-shadow:var(--cal-shadow);overflow:hidden;">

            {{-- Staff nav — Booksy style ── --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px 10px;border-bottom:1px solid var(--cal-border);background:var(--cal-toolbar-bg);flex-wrap:wrap;gap:8px;">
                <div class="sf-popwrap" style="display:flex;align-items:center;gap:8px;">
                    <button id="sf-prev" style="background:var(--cal-surface2);border:1px solid var(--cal-border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--cal-text);font-size:1.05rem;transition:background .12s;">‹</button>
                    <div id="sf-title-wrap" style="text-align:center;min-width:160px;">
                        <div id="sf-title" style="font-size:.96rem;font-weight:800;color:var(--cal-text);line-height:1.25;"></div>
                        <div id="sf-subtitle" style="font-size:.68rem;color:var(--cal-text-muted);font-weight:600;margin-top:1px;"></div>
                    </div>
                    <button id="sf-next" style="background:var(--cal-surface2);border:1px solid var(--cal-border);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--cal-text);font-size:1.05rem;transition:background .12s;">›</button>
                    {{-- Date picker popover --}}
                    <div id="sf-datepick" class="sf-pop sf-pop-start d-none"></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    {{-- Team filter --}}
                    <div class="sf-popwrap">
                        <button id="sf-team-btn" class="sf-pill-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span id="sf-team-label">{{ $isRtl ? 'جميع الفريق' : 'All team' }}</span>
                            <span class="caret">▾</span>
                        </button>
                        <div id="sf-team-menu" class="sf-pop sf-pop-end d-none" style="width:250px;">
                            <div class="qa-search" style="margin-bottom:8px;padding:7px 10px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input id="sf-team-search" type="text" placeholder="{{ $isRtl ? 'بحث' : 'Search' }}">
                            </div>
                            <div id="sf-team-list" style="max-height:270px;overflow-y:auto;"></div>
                        </div>
                    </div>
                    {{-- View switcher --}}
                    <div class="sf-popwrap">
                        <button id="sf-view-btn" class="sf-pill-btn">
                            <span id="sf-view-label"></span>
                            <span class="caret">▾</span>
                        </button>
                        <div id="sf-view-menu" class="sf-pop sf-pop-end d-none" style="min-width:150px;"></div>
                    </div>
                    <button id="sf-settings-btn" class="sf-tool-btn" title="{{ $isRtl ? 'إعدادات التقويم' : 'Calendar settings' }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </button>
                    <button id="sf-today" style="background:linear-gradient(135deg,#f97316,#ef4444);border:none;border-radius:18px;padding:6px 18px;font-size:.76rem;font-weight:800;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(249,115,22,.4);">
                        {{ $isRtl ? 'اليوم' : 'Today' }}
                    </button>
                </div>
            </div>

            {{-- Staff grid container --}}
            <div id="sf-grid-wrap" style="overflow:auto;max-height:680px;">
                <div id="sf-grid" style="display:flex;min-width:600px;">
                    <div class="text-center py-5 w-100" style="color:var(--cal-text-muted);">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ CALENDAR VIEW ══ --}}
    <div id="view-cal">
        <div id="bk-cal-shell">
            <div id="booksy-calendar"></div>
        </div>
    </div>

    {{-- ══ LIST VIEW ══ --}}
    <div id="view-list" class="d-none">
        <div class="bk-list-card">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>{{ $isRtl ? 'الزبون' : 'Customer' }}</th>
                        <th>{{ $isRtl ? 'الخدمة' : 'Service' }}</th>
                        <th>{{ $isRtl ? 'الموظف' : 'Employee' }}</th>
                        <th>{{ $isRtl ? 'الفرع' : 'Branch' }}</th>
                        <th>{{ $isRtl ? 'الوقت' : 'Time' }}</th>
                        <th>{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                        <th>{{ $isRtl ? 'السعر' : 'Price' }}</th>
                        <th class="pe-4">{{ $isRtl ? 'إجراء' : 'Action' }}</th>
                    </tr>
                </thead>
                <tbody id="list-tbody">
                    <tr><td colspan="9" class="text-center py-5" style="color:var(--cal-text-muted);">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        {{ __('Loading...') }}
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ── Popup ── --}}
<div id="bk-popup" class="d-none">
    <div class="bk-pp-hdr" id="bk-pp-hdr">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="bk-pp-badge" id="bk-pp-status"></span>
            <button class="bk-pp-close" id="bk-pp-close">✕</button>
        </div>
        <div style="font-size:.92rem;font-weight:800;color:#fff;" id="bk-pp-title"></div>
        <div style="font-size:.73rem;color:rgba(255,255,255,.78);margin-top:3px;" id="bk-pp-time"></div>
    </div>
    <div class="bk-pp-body">
        {{-- Employee (highlighted) --}}
        <div class="bk-pp-emp">
            <div class="bk-pp-emp-av" id="bk-pp-emp-av"></div>
            <div>
                <div class="bk-pp-emp-label">{{ __('Employee') }}</div>
                <div class="bk-pp-emp-name" id="bk-pp-emp-name"></div>
            </div>
        </div>
        <div class="bk-pp-row">
            <svg class="ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span id="bk-pp-service"></span>
        </div>
        <div class="bk-pp-row">
            <svg class="ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span id="bk-pp-branch"></span>
        </div>
        <div class="bk-pp-row">
            <svg class="ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span id="bk-pp-price" style="font-weight:800;"></span>
        </div>
        {{-- Audit trail row --}}
        <div id="bk-pp-audit" class="bk-pp-row" style="display:none;font-size:.75rem;color:var(--cal-text-soft);gap:6px;"></div>

        <a id="bk-pp-link" href="#" class="bk-pp-btn">{{ __('View details') }} ←</a>
    </div>
</div>

{{-- ── Calendar settings drawer (Fresha style) ── --}}
<div id="sf-settings-overlay" class="d-none"></div>
<div id="sf-settings-drawer">
    <div class="sf-drawer-hdr">
        <span>{{ $isRtl ? 'إعدادات تقويمك' : 'Your calendar settings' }}</span>
        <button class="sf-drawer-close" id="sf-settings-close">✕</button>
    </div>
    <div class="sf-drawer-body">
        <div class="sf-drawer-section">
            <div class="sf-drawer-label">{{ $isRtl ? 'تكبير/تصغير التقويم' : 'Calendar zoom' }}</div>
            <div class="sf-zoom-row">
                <span>{{ $isRtl ? 'صغير' : 'Small' }}</span>
                <input type="range" id="sf-zoom-range" min="48" max="160" step="16">
                <span>{{ $isRtl ? 'كبير' : 'Large' }}</span>
            </div>
        </div>
        <div class="sf-drawer-section" style="border-bottom:none;">
            <div class="sf-toggle-row">
                <div>
                    <div class="sf-toggle-txt">{{ $isRtl ? 'عرض الإجراءات السريعة على التقويم' : 'Show quick actions on calendar' }}</div>
                    <div class="sf-toggle-desc">{{ $isRtl ? 'إضافة المواعيد بسرعة بالنقر على خانة في التقويم' : 'Quickly add appointments by clicking a slot in the calendar' }}</div>
                </div>
                <label class="sf-switch">
                    <input type="checkbox" id="sf-quick-toggle">
                    <span class="track"></span>
                </label>
            </div>
        </div>
    </div>
    <div class="sf-drawer-ftr">
        <button class="sf-apply-btn" id="sf-settings-apply">{{ $isRtl ? 'تطبيق التغييرات' : 'Apply changes' }}</button>
    </div>
</div>

{{-- ── Quick add drawer (Fresha "new appointment") ── --}}
<div id="qa-overlay" class="d-none"></div>
<div id="qa-drawer">
    <button id="qa-close" title="{{ $isRtl ? 'إغلاق' : 'Close' }}">✕</button>

    {{-- Client panel --}}
    <div class="qa-panel qa-client">
        {{-- Guest switcher — only appears once a second guest is added --}}
        <div id="qa-guests" class="d-none">
            <div class="qa-guests-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span id="qa-guests-count"></span>
            </div>
            <div id="qa-guest-list" role="tablist"></div>
        </div>

        <div id="qa-client-collapsed">
            <div class="qa-cl-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
            </div>
            <div class="qa-cl-txt">
                <div class="qa-cl-title">{{ $isRtl ? 'إضافة عميل' : 'Add client' }}</div>
                <div class="qa-cl-desc">{{ $isRtl ? 'أو يمكن تركه فارغًا للزيارات بدون موعد' : 'Or leave empty for walk-ins' }}</div>
            </div>
        </div>
        <div id="qa-client-expanded" class="d-none" style="display:flex;flex-direction:column;flex:1;min-height:0;">
            <div class="qa-title" style="font-size:1.05rem;">{{ $isRtl ? 'تحديد عميل' : 'Select client' }}</div>
            <div class="qa-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input id="qa-client-search" type="text" placeholder="{{ $isRtl ? 'البحث عن العميل أو تركه فارغًا' : 'Search client or leave empty' }}">
            </div>
            <div class="qa-list" id="qa-client-list" style="overflow-y:auto;"></div>
        </div>

        {{-- Grow a single booking into a group without switching mode or page --}}
        <div class="qa-guest-actions">
            <button type="button" id="qa-add-guest">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ $isRtl ? 'إضافة ضيف' : 'Add guest' }}
            </button>
            <button type="button" id="qa-dup-guest" class="d-none"
                    title="{{ $isRtl ? 'ضيف جديد بنفس خدمات الضيف الحالي' : 'New guest with the same services' }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                {{ $isRtl ? 'نفس الخدمات' : 'Same again' }}
            </button>
        </div>
    </div>

    {{-- Service panel --}}
    <div class="qa-panel qa-service">
        <div class="qa-title">{{ $isRtl ? 'تحديد خدمة' : 'Select service' }}</div>
        {{-- Branch selector --}}
        <div class="qa-search" style="margin-bottom:10px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <select id="qa-branch">
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="qa-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="qa-svc-search" type="text" placeholder="{{ $isRtl ? 'البحث حسب اسم الخدمة' : 'Search by service name' }}">
        </div>
        <div class="qa-meta" id="qa-meta"></div>
        <div class="qa-list" id="qa-svc-list"></div>

        {{-- Selected services (cart) --}}
        <div id="qa-cart" class="d-none">
            <div class="qa-cart-title">{{ $isRtl ? 'الخدمات المحددة' : 'Selected services' }}</div>
            <div id="qa-cart-list"></div>
        </div>

        {{-- Footer: total + save --}}
        <div class="qa-footer">
            <div class="qa-total">
                <span>{{ $isRtl ? 'المجموع' : 'Total' }}</span>
                <b id="qa-total-val">0</b>
                <small id="qa-total-dur"></small>
            </div>
            <button id="qa-save" disabled>{{ $isRtl ? 'حفظ الموعد' : 'Save appointment' }}</button>
        </div>

        {{-- ── Edit service panel (Fresha "تعديل الخدمة") ── --}}
        <div id="qa-edit" class="d-none">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div class="qa-title" style="margin:0;">{{ $isRtl ? 'تعديل الخدمة' : 'Edit service' }}</div>
                <button type="button" id="qa-edit-back" class="sf-pill-btn">{{ $isRtl ? 'رجوع ←' : '→ Back' }}</button>
            </div>
            <div class="qa-edit-svc" id="qa-edit-svcname"></div>

            <label class="qa-edit-lbl">{{ $isRtl ? 'عضو الفريق' : 'Team member' }}</label>
            <select id="qa-edit-emp" class="qa-edit-inp"></select>
            <div class="qa-edit-note" id="qa-edit-empnote"></div>

            <div style="display:flex;gap:12px;">
                <div style="flex:1;">
                    <label class="qa-edit-lbl">{{ $isRtl ? 'سعر الخدمة' : 'Service price' }}</label>
                    <input type="number" min="0" step="any" id="qa-edit-price" class="qa-edit-inp" style="direction:ltr;text-align:start;">
                    <div class="qa-edit-note" id="qa-edit-pricenote"></div>
                </div>
                <div style="flex:1;">
                    <label class="qa-edit-lbl">{{ $isRtl ? 'المدة' : 'Duration' }}</label>
                    <select id="qa-edit-dur" class="qa-edit-inp"></select>
                </div>
            </div>

            <label class="qa-edit-lbl">{{ $isRtl ? 'وقت البدء' : 'Start time' }}</label>
            <select id="qa-edit-start" class="qa-edit-inp"></select>
            <div class="qa-edit-note" id="qa-edit-startnote"></div>

            <div class="qa-next d-none" id="qa-next">
                <span class="qa-next-lbl">{{ $isRtl ? 'الموعد التالي المتاح' : 'Next available' }}</span>
                <span id="qa-next-chips"></span>
            </div>

            <div class="qa-edit-ftr">
                <button type="button" id="qa-edit-del" title="{{ $isRtl ? 'حذف الخدمة' : 'Remove service' }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
                <div class="qa-total">
                    <span>{{ $isRtl ? 'المجموع' : 'Total' }}</span>
                    <b id="qa-edit-total"></b>
                </div>
                <button type="button" id="qa-edit-apply">{{ $isRtl ? 'تطبيق' : 'Apply' }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Appointment detail / checkout drawer (Fresha style) ── --}}
<div id="ap-overlay" class="d-none"></div>
<div id="ap-drawer">
    <button id="ap-close" title="{{ $isRtl ? 'إغلاق' : 'Close' }}">✕</button>
    <div id="ap-body"></div>
</div>

{{-- ══ Sync status pill — only visible when there's something to say ══ --}}
<div id="bk-sync" class="d-none" role="status" aria-live="polite">
    <span class="bk-sync-dot"></span>
    <span id="bk-sync-txt"></span>
    <button type="button" id="bk-sync-retry" class="d-none">{{ $isRtl ? 'إعادة المحاولة' : 'Retry' }}</button>
</div>

@endsection

@push('company-after-template')
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}"></script>

@php
    /*
     | Data bridge. The page's behaviour lives in
     | public/backend/assets/js/appointments/*.js — cacheable, lintable, diffable.
     | This block is the only thing the server still injects, and it is data only:
     | resolved routes, translated strings, and the status metadata that comes
     | from App\Enums\AppointmentStatus.
     |
     | Route templates carry a literal __ID__ the JS swaps for a real id, which
     | avoids calling route() thousands of times per response.
     */
    $fcLocale = app()->getLocale() === 'ar' ? 'ar' : 'en';

    $bkRoutes = [
        'appointmentsCalendarEvents'   => route('company.appointments.calendar-events'),
        'appointmentsUpdateStatus'     => route('company.appointments.update-status', '__ID__'),
        'appointmentsStaffEvents'      => route('company.appointments.staff-events'),
        'appointmentsReschedule'       => route('company.appointments.reschedule', '__ID__'),
        'appointmentsCreate'           => route('company.appointments.create'),
        'appointmentsQuickStore'       => route('company.appointments.quick-store'),
        'appointmentsQuickGroupStore'  => route('company.appointments.quick-group-store'),
        'appointmentsBranchData'       => route('company.appointments.branch-data'),
        'customersSearchJson'          => route('company.customers.search-json'),
        'appointmentsDetailsJson'      => route('company.appointments.details-json', '__ID__'),
        'appointmentsCheckout'         => route('company.appointments.checkout', '__ID__'),
        'appointmentsCheckoutData'     => route('company.appointments.checkout-data'),
        'blockedTimesStore'            => route('company.blocked-times.store'),
        'blockedTimesIndex'            => route('company.blocked-times.index'),
        'blockedTimesDestroy'          => route('company.blocked-times.destroy', '__ID__'),
        'waitlistIndex'                => route('company.waitlist.index'),
        'waitlistStore'                => route('company.waitlist.store'),
        'waitlistResolve'              => route('company.waitlist.resolve', '__ID__'),
    ];

    $bkStrings = [
        'no_appointments_found'                    => $isRtl ? 'لم يُعثر على مواعيد.' : 'No appointments found.',
        'call'                                     => $isRtl ? 'اتصال' : 'Call',
        'loading'                                  => $isRtl ? 'جارٍ التحميل...' : 'Loading...',
        'error_loading_data'                       => $isRtl ? 'خطأ في تحميل البيانات' : 'Error loading data',
        'walk_in'                                  => $isRtl ? 'دخول بدون موعد مسبق' : 'Walk-in',
        'add_new_client'                           => $isRtl ? 'إضافة عميل جديد' : 'Add new client',
        'client'                                   => $isRtl ? 'العميل' : 'Client',
        'client_name'                              => $isRtl ? 'اسم العميل' : 'Client name',
        'phone_number'                             => $isRtl ? 'رقم الهاتف' : 'Phone number',
        'save'                                     => $isRtl ? 'حفظ' : 'Save',
        'no_results'                               => $isRtl ? 'لا توجد نتائج' : 'No results',
        'other_services'                           => $isRtl ? 'خدمات أخرى' : 'Other services',
        'min'                                      => $isRtl ? 'دقيقة' : 'min',
        'hr'                                       => $isRtl ? 'ساعة' : 'hr',
        'hrs'                                      => $isRtl ? 'ساعات' : 'hrs',
        'could_not_book_the_appointment'           => $isRtl ? 'تعذّر حجز الموعد' : 'Could not book the appointment',
        'guest_n'                                  => $isRtl ? 'ضيف :n' : 'Guest :n',
        'n_guests'                                 => $isRtl ? ':n ضيوف' : ':n guests',
        'no_service_yet'                           => $isRtl ? 'لم تُختر خدمة بعد' : 'No service yet',
        'remove_guest'                             => $isRtl ? 'إزالة الضيف' : 'Remove guest',
        'up_to_12_guests_per_booking'              => $isRtl ? 'الحد الأقصى 12 ضيفاً في الحجز الواحد' : 'Up to 12 guests per booking',
        'save_appointment'                         => $isRtl ? 'حفظ الموعد' : 'Save appointment',
        'book_n_guests'                            => $isRtl ? 'حجز :n ضيوف' : 'Book :n guests',
        'saving'                                   => $isRtl ? 'جارٍ الحفظ...' : 'Saving...',
        'give_every_guest_a_service_first'         => $isRtl ? 'أضف خدمة لكل ضيف أولاً' : 'Give every guest a service first',
        'booked_n_guests'                          => $isRtl ? 'تم حجز :n ضيوف بنجاح' : 'Booked :n guests',
        'booked_n_guests_who_follows_the_others_s' => $isRtl ? 'تم حجز :n ضيوف — :who بعد الآخرين (نفس الموظف أو الجهاز)' : 'Booked :n guests — :who follows the others (shared staff or equipment)',
        'unfinished_booking_restored'              => $isRtl ? 'تمت استعادة الحجز غير المكتمل' : 'Unfinished booking restored',
        'draft_saved'                              => $isRtl ? 'تم حفظ المسودة' : 'Draft saved',
        'discard_sale_draft'                       => $isRtl ? 'تجاهل مسودة المبيعات؟' : 'Discard sale draft?',
        'discard_sale_draft_2'                     => $isRtl ? 'هل تريد تجاهل مسودة المبيعات؟' : 'Discard sale draft?',
        'your_cart_changes_will_be_lost_save_this' => $isRtl ? 'لن يتم حفظ التغييرات في سلة التسوق الخاصة بك. للحفاظ على التعديلات، يرجى حفظ هذا المبيع كمسودة' : 'Your cart changes will be lost. Save this sale as a draft to keep them.',
        'discard'                                  => $isRtl ? 'تجاهل' : 'Discard',
        'save_as_draft'                            => $isRtl ? 'حفظ كمسودة' : 'Save as draft',
        'team_member_unavailable'                  => $isRtl ? 'عضو الفريق غير متاح' : 'Team member unavailable',
        'remove'                                   => $isRtl ? 'إزالة' : 'Remove',
        'available_at_this_time'                   => $isRtl ? 'متاح في هذا التوقيت' : 'Available at this time',
        'has_another_appointment_at_this_time'     => $isRtl ? 'لديه موعد آخر في هذا التوقيت' : 'Has another appointment at this time',
        'has_no_shift_at_this_time'                => $isRtl ? 'ليس لديه مناوبة عمل في هذا التوقيت' : 'Has no shift at this time',
        'starts_right_after_the_previous_service'  => $isRtl ? 'تبدأ مباشرة بعد الخدمة السابقة' : 'Starts right after the previous service',
        'unavailable_at'                           => $isRtl ? 'غير متاح في' : 'Unavailable at',
        'manual_discount_applied'                  => $isRtl ? 'تم تطبيق خصم يدوي' : 'Manual discount applied',
        'reset'                                    => $isRtl ? 'إعادة تعيين' : 'Reset',
        'manual_increase'                          => $isRtl ? 'زيادة يدوية' : 'Manual increase',
        'appointment_booked_successfully'          => $isRtl ? 'تم حجز الموعد بنجاح' : 'Appointment booked successfully',
        'services'                                 => $isRtl ? 'الخدمات' : 'Services',
        'checkout'                                 => $isRtl ? 'المحاسبة' : 'Checkout',
        'view_details'                             => $isRtl ? 'عرض التفاصيل' : 'View details',
        'view_profile'                             => $isRtl ? 'عرض الملف الشخصي' : 'View profile',
        'total'                                    => $isRtl ? 'المجموع' : 'Total',
        'select_tip'                               => $isRtl ? 'تحديد الإكرامية' : 'Select tip',
        'amount_for'                               => $isRtl ? 'تحديد مبلغ لـ' : 'Amount for',
        'no_tip'                                   => $isRtl ? 'لا توجد إكرامية' : 'No tip',
        'custom_tip'                               => $isRtl ? 'إكرامية مخصصة' : 'Custom tip',
        'back'                                     => $isRtl ? 'رجوع' : 'Back',
        'continue_to_payment'                      => $isRtl ? 'المتابعة إلى الدفع' : 'Continue to payment',
        'cart'                                     => $isRtl ? 'سلة التسوق' : 'Cart',
        'service'                                  => $isRtl ? 'خدمة' : 'Service',
        'product'                                  => $isRtl ? 'منتج' : 'Product',
        'appointment'                              => $isRtl ? 'موعد آخر' : 'Appointment',
        'tip'                                      => $isRtl ? 'إكرامية' : 'Tip',
        'cash'                                     => $isRtl ? 'كاش' : 'Cash',
        'card'                                     => $isRtl ? 'بطاقة' : 'Card',
        'complete_payment'                         => $isRtl ? 'إتمام الدفع' : 'Complete payment',
        'paid'                                     => $isRtl ? 'مدفوع ✓' : 'Paid ✓',
        'in_stock'                                 => $isRtl ? 'متوفر' : 'in stock',
        'nothing_available'                        => $isRtl ? 'لا توجد عناصر' : 'Nothing available',
        'paid_completed_successfully'              => $isRtl ? 'تم الدفع وإكمال الموعد بنجاح' : 'Paid & completed successfully',
        'payment_failed'                           => $isRtl ? 'تعذّر إتمام الدفع' : 'Payment failed',
        'status_updated'                           => $isRtl ? 'تم تحديث حالة الموعد' : 'Status updated',
        'already_checked_out_paid_cannot_charge_t' => $isRtl ? 'هذا الموعد محاسَب ومدفوع مسبقاً — لا يمكن تكرار المحاسبة' : 'Already checked out & paid — cannot charge twice',
        'out_of_stock'                             => $isRtl ? 'نفد المخزون' : 'Out of stock',
        'left'                                     => $isRtl ? 'متبقي' : 'left',
        'cannot_add_more_than_available_stock'     => $isRtl ? 'لا يمكن إضافة أكثر من المخزون المتوفر' : 'Cannot add more than available stock',
        'appointment_services'                     => $isRtl ? 'خدمات الموعد' : 'Appointment services',
        'extra_services'                           => $isRtl ? 'خدمات إضافية' : 'Extra services',
        'products'                                 => $isRtl ? 'منتجات' : 'Products',
        'other_appointments'                       => $isRtl ? 'مواعيد أخرى' : 'Other appointments',
        'grand_total'                              => $isRtl ? 'الإجمالي المستحق' : 'Grand total',
        'amount_received'                          => $isRtl ? 'المبلغ المستلم' : 'Amount received',
        'change_due'                               => $isRtl ? 'الباقي للزبون' : 'Change due',
        'short_by'                                 => $isRtl ? 'ناقص' : 'Short by',
        'checkout_complete'                        => $isRtl ? 'تمت المحاسبة بنجاح' : 'Checkout complete',
        'appointment_completed_payment_recorded_i' => $isRtl ? 'اكتمل الموعد وسُجّل الدفع في الصندوق وأُنشئت الفاتورة' : 'Appointment completed, payment recorded, invoice created',
        'view_invoice'                             => $isRtl ? 'عرض الفاتورة' : 'View invoice',
        'print'                                    => $isRtl ? 'طباعة' : 'Print',
        'close'                                    => $isRtl ? 'إغلاق' : 'Close',
        'added'                                    => $isRtl ? 'مُضاف ✓' : 'Added ✓',
        'paid_2'                                   => $isRtl ? 'مدفوع' : 'Paid',
        'partially_paid'                           => $isRtl ? 'دفع جزئي' : 'Partially paid',
        'unpaid'                                   => $isRtl ? 'غير مدفوع' : 'Unpaid',
        'quantity'                                 => $isRtl ? 'الكمية' : 'Quantity',
        'payment_method'                           => $isRtl ? 'طريقة الدفع' : 'Payment method',
        'saving_2'                                 => $isRtl ? 'جارٍ الحفظ…' : 'Saving…',
        'saved'                                    => $isRtl ? 'تم الحفظ' : 'Saved',
        'offline_n_waiting_to_sync'                => $isRtl ? 'غير متصل — :n بانتظار المزامنة' : 'Offline — :n waiting to sync',
        'offline_changes_are_kept_on_this_device'  => $isRtl ? 'غير متصل — التغييرات ستُحفظ محلياً' : 'Offline — changes are kept on this device',
        'syncing_n'                                => $isRtl ? 'جارٍ المزامنة… (:n)' : 'Syncing… (:n)',
        'n_could_not_sync_retry'                   => $isRtl ? 'تعذّرت مزامنة :n — اضغط لإعادة المحاولة' : ':n could not sync — retry?',
        'you_are_offline_this_booking_will_save_a' => $isRtl ? 'أنت غير متصل — سيُحفظ الحجز تلقائياً عند عودة الاتصال' : 'You are offline — this booking will save automatically when you reconnect',
        'pending_bookings_synced'                  => $isRtl ? 'تمت مزامنة الحجوزات المعلّقة' : 'Pending bookings synced',
        'a_pending_booking_was_rejected_m'         => $isRtl ? 'رُفض حجز معلّق: :m' : 'A pending booking was rejected: :m',
        'you_have_unsaved_changes'                 => $isRtl ? 'لديك تغييرات لم تُحفظ.' : 'You have unsaved changes.',
    ];

    // Assembled here rather than inline in @json(): Blade's directive-argument
    // parser cannot handle a multi-line array literal.
    $bkStrings += [
        /* ── waitlist ── */
        'wl_search_min'      => $isRtl ? 'اكتب حرفين على الأقل' : 'Type at least 2 characters',
        'wl_no_match'        => $isRtl ? 'لا يوجد زبون بهذا الاسم' : 'No customer found',
        'wl_add_as_new'      => $isRtl ? 'إضافة :name كزبون جديد' : 'Add :name as a new customer',
        'wl_visits'          => $isRtl ? ':n زيارة' : ':n visits',
        'wl_no_visits'       => $isRtl ? 'لم يزر بعد' : 'No visits yet',
        'wl_change'          => $isRtl ? 'تغيير' : 'Change',
        'wl_added'           => $isRtl ? 'أُضيف لقائمة الانتظار' : 'Added to the waitlist',
        'wl_saved_customer'  => $isRtl ? 'وحُفظ في سجل الزبائن' : 'and saved to your customers',
        'wl_pick_customer'   => $isRtl ? 'اختر زبوناً أولاً' : 'Choose a customer first',
        'wl_waiting_for'     => $isRtl ? 'ينتظر منذ :n د' : 'waiting :n min',
        'wl_waiting_hours'   => $isRtl ? 'ينتظر منذ :n س' : 'waiting :n h',
        'wl_book_now'        => $isRtl ? 'احجز' : 'Book',
        'wl_any_service'     => $isRtl ? 'أي خدمة' : 'Any service',
        'wl_remove_confirm'  => $isRtl ? 'إزالة :name من الانتظار؟' : 'Remove :name from the waitlist?',
        'wl_empty'           => $isRtl ? 'لا يوجد أحد بالانتظار' : 'Nobody is waiting',
        'wl_empty_hint'      => $isRtl ? 'أضف زبوناً من الأعلى حين يطلب موعداً وليس هناك شاغر.' : 'Add someone above when they want a slot and nothing is free.',
    ];

    $bk = [
        'isRtl'        => $isRtl,
        'fcLocale'     => $fcLocale,
        'firstBranch'  => $branches->first()->id ?? '',
        'statusDefs'   => $statusDefs,
        'allowedMoves' => $allowedMoves,
        'liveStatuses' => $liveStatuses,
        'tiers'        => \App\Enums\CustomerTier::forFrontend(),
        'priorities'   => \App\Enums\WaitlistPriority::forFrontend(),
        'routes'       => $bkRoutes,
        't'            => $bkStrings,
    ];

    $bkJsDir = public_path('backend/assets/js/appointments');
@endphp

<script>window.BK = @json($bk);</script>

<script src="{{ asset('backend/assets/js/appointments/appointments.js') }}?v={{ @filemtime($bkJsDir.'/appointments.js') ?: '1' }}"></script>
<script src="{{ asset('backend/assets/js/appointments/panels.js') }}?v={{ @filemtime($bkJsDir.'/panels.js') ?: '1' }}"></script>
@endpush
