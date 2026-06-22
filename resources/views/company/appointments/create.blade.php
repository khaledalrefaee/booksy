@extends('company.dashboard')

@php
$locale   = app()->getLocale();
$isRtl    = $locale === 'ar';
$isAr     = $locale === 'ar';
@endphp

@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('New Appointment') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('company.appointments.index') }}">{{ __('Appointments') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ __('New') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.appointments.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i data-feather="arrow-left" style="width:14px;height:14px;"></i>
            {{ __('Back') }}
        </a>
    </div>

    @include('company.partials.flash')

    <form method="POST" action="{{ route('company.appointments.store') }}" id="appt-form">
        @csrf

        <div class="row g-4 align-items-start">

            {{-- ── LEFT COLUMN ── --}}
            <div class="col-xl-8">

                {{-- Step indicator --}}
                <div class="bk-steps mb-4">
                    <div class="bk-step active" data-step="1">
                        <div class="bk-step-num">1</div>
                        <span>{{ __('Branch') }}</span>
                    </div>
                    <div class="bk-step-line"></div>
                    <div class="bk-step" data-step="2">
                        <div class="bk-step-num">2</div>
                        <span>{{ __('Customers & Services') }}</span>
                    </div>
                    <div class="bk-step-line"></div>
                    <div class="bk-step" data-step="3">
                        <div class="bk-step-num">3</div>
                        <span>{{ __('Details') }}</span>
                    </div>
                </div>

                {{-- Branch selector --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3 bk-create-card" id="step-branch">
                    <div class="card-body p-4">
                        <div class="bk-section-title">
                            <div class="bk-section-icon">
                                <i data-feather="map-pin" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ __('Select Branch') }}</h6>
                                <small class="text-muted">{{ __('Choose where the appointment will take place') }}</small>
                            </div>
                        </div>
                        <div class="bk-branch-grid mt-3" id="branch-grid">
                            @foreach($branches as $b)
                                <label class="bk-branch-card" data-branch-id="{{ $b->id }}">
                                    <input type="radio" name="branch_id" value="{{ $b->id }}"
                                        {{ old('branch_id', $selectedBranchId) == $b->id ? 'checked' : '' }}
                                        class="d-none">
                                    <div class="bk-branch-icon">
                                        <i data-feather="home" style="width:20px;height:20px;"></i>
                                    </div>
                                    <div class="bk-branch-name">{{ $b->localizedName() }}</div>
                                </label>
                            @endforeach
                        </div>
                        @error('branch_id')
                            <div class="text-danger tx-12 mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Persons container --}}
                <div id="persons-container"></div>

                {{-- Add person --}}
                <button type="button" id="add-person-btn"
                    class="bk-add-person-btn mb-4 w-100" disabled>
                    <div class="bk-add-person-inner">
                        <i data-feather="user-plus" style="width:18px;height:18px;"></i>
                        <span>{{ __('Add another person') }}</span>
                        <small class="text-muted d-block">{{ __('For group bookings') }}</small>
                    </div>
                </button>

                {{-- Session extras --}}
                <div class="card border-0 shadow-sm rounded-4 bk-create-card" id="step-details">
                    <div class="card-body p-4">
                        <div class="bk-section-title">
                            <div class="bk-section-icon" style="background:rgba(99,102,241,.15);color:#6366f1;">
                                <i data-feather="settings" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ __('Session details') }}</h6>
                                <small class="text-muted">{{ __('Payment and notes') }}</small>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-sm-5">
                                <label class="form-label fw-semibold tx-13">{{ __('Payment status') }}</label>
                                <div class="bk-payment-options">
                                    <label class="bk-pay-opt">
                                        <input type="radio" name="payment_status" value="pending" checked>
                                        <div class="bk-pay-card">
                                            <i data-feather="clock" style="width:16px;height:16px;color:#f59e0b;"></i>
                                            <span>{{ __('Pending') }}</span>
                                        </div>
                                    </label>
                                    <label class="bk-pay-opt">
                                        <input type="radio" name="payment_status" value="paid">
                                        <div class="bk-pay-card">
                                            <i data-feather="check-circle" style="width:16px;height:16px;color:#10b981;"></i>
                                            <span>{{ __('Paid') }}</span>
                                        </div>
                                    </label>
                                    <label class="bk-pay-opt">
                                        <input type="radio" name="payment_status" value="partial">
                                        <div class="bk-pay-card">
                                            <i data-feather="percent" style="width:16px;height:16px;color:#6366f1;"></i>
                                            <span>{{ __('Partial') }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label fw-semibold tx-13">{{ __('Notes') }}</label>
                                <textarea name="notes" class="form-control bk-textarea" rows="3"
                                    placeholder="{{ __('Internal notes (optional)') }}">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT COLUMN: Summary ── --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top bk-summary-card" style="top:80px;">
                    <div class="bk-summary-header">
                        <i data-feather="shopping-bag" style="width:18px;height:18px;"></i>
                        <span class="fw-bold">{{ __('Booking Summary') }}</span>
                    </div>
                    <div class="card-body p-4">
                        <div id="summary-list" class="mb-3 tx-13">
                            <div class="bk-empty-summary">
                                <div class="bk-empty-anim">
                                    <i data-feather="calendar" style="width:32px;height:32px;"></i>
                                </div>
                                <p class="mb-1 fw-semibold">{{ __('No services yet') }}</p>
                                <small class="text-muted">{{ __('Select a branch and add services.') }}</small>
                            </div>
                        </div>
                        <div class="bk-summary-total">
                            <span>{{ __('Total') }}</span>
                            <span id="summary-total">0.00</span>
                        </div>
                        <button type="submit" class="btn bk-submit-btn w-100 py-2 fw-bold mt-3" id="submit-btn" disabled>
                            <i data-feather="check-circle" style="width:16px;height:16px;margin-inline-end:6px;"></i>
                            {{ __('Create appointment') }}
                        </button>
                        <div class="text-center mt-2">
                            <small class="text-muted tx-11">
                                <i data-feather="shield" style="width:11px;height:11px;"></i>
                                {{ __('Appointment can be edited later') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Hidden person template --}}
<template id="person-tpl">
    <div class="bk-person-card card border-0 shadow-sm rounded-4 mb-3 bk-create-card" data-person-idx="__IDX__">
        <div class="card-body p-0">
            <div class="bk-person-header">
                <div class="bk-person-avatar">
                    <i data-feather="user" style="width:16px;height:16px;"></i>
                </div>
                <span class="fw-bold tx-14">{{ __('Person') }} <span class="person-number">__NUM__</span></span>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <span class="bk-person-services-count badge rounded-pill">0 {{ __('services') }}</span>
                    <button type="button" class="remove-person-btn btn btn-sm btn-outline-danger rounded-pill d-none" style="width:28px;height:28px;padding:0;display:flex;align-items:center;justify-content:center;">
                        <i data-feather="x" style="width:12px;height:12px;"></i>
                    </button>
                </div>
            </div>
            <div class="bk-person-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label fw-semibold tx-12 mb-1">
                            <i data-feather="user" style="width:12px;height:12px;color:#C9A227;"></i>
                            {{ __('Customer name') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="persons[__IDX__][name]"
                            class="form-control bk-input"
                            placeholder="{{ __('Full name') }}"
                            required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold tx-12 mb-1">
                            <i data-feather="phone" style="width:12px;height:12px;color:#C9A227;"></i>
                            {{ __('Phone') }}
                        </label>
                        <input type="tel"
                            name="persons[__IDX__][phone]"
                            class="form-control bk-input"
                            placeholder="{{ __('Phone (optional)') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label fw-semibold tx-12 mb-1">
                            <i data-feather="calendar" style="width:12px;height:12px;color:#C9A227;"></i>
                            {{ __('Age') }}
                        </label>
                        <input type="number"
                            name="persons[__IDX__][age]"
                            class="form-control bk-input"
                            placeholder="{{ __('Age') }}"
                            min="1" max="150">
                    </div>
                </div>
                <div class="services-list"></div>
                <button type="button" class="bk-add-svc-btn add-service-btn">
                    <i data-feather="plus-circle" style="width:14px;height:14px;"></i>
                    {{ __('Add service') }}
                </button>
            </div>
        </div>
    </div>
</template>

{{-- Hidden service-row template --}}
<template id="svc-tpl">
    <div class="bk-svc-row">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-4">
                <label class="form-label fw-semibold tx-11 mb-1">
                    <i data-feather="scissors" style="width:11px;height:11px;color:#7c3aed;"></i>
                    {{ __('Service') }}
                </label>
                <select name="persons[__PIDX__][services][__SIDX__][service_id]"
                    class="form-select form-select-sm bk-select svc-select" required>
                    <option value="">{{ __('Select service…') }}</option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <label class="form-label fw-semibold tx-11 mb-1">
                    <i data-feather="user-check" style="width:11px;height:11px;color:#10b981;"></i>
                    {{ __('Staff') }}
                </label>
                <select name="persons[__PIDX__][services][__SIDX__][employee_id]"
                    class="form-select form-select-sm bk-select emp-select">
                    <option value="">{{ __('Any') }}</option>
                </select>
            </div>
            <div class="col-6 col-sm-4">
                <label class="form-label fw-semibold tx-11 mb-1">
                    <i data-feather="clock" style="width:11px;height:11px;color:#f59e0b;"></i>
                    {{ __('Date & time') }}
                </label>
                <div class="fp-input-wrap">
                    <i data-feather="clock" class="fp-icon" style="width:14px;height:14px;"></i>
                    <input type="text"
                        name="persons[__PIDX__][services][__SIDX__][start_time]"
                        class="form-control form-control-sm fp-datetime bk-input"
                        placeholder="{{ __('Pick date & time') }}"
                        required>
                </div>
            </div>
            <div class="col-sm-1 d-flex align-items-end justify-content-end">
                <button type="button" class="remove-svc-btn btn btn-sm btn-outline-danger rounded-pill remove-svc-btn-visible d-none"
                    style="width:32px;height:32px;padding:0;">
                    <i data-feather="trash-2" style="width:12px;height:12px;"></i>
                </button>
            </div>
        </div>
        <div class="svc-hint mt-1"></div>
    </div>
</template>
@endsection

@push('scripts')
<link rel="stylesheet" href="{{ asset('backend/assets/vendors/select2/select2.min.css') }}">
<script src="{{ asset('backend/assets/vendors/select2/select2.min.js') }}"></script>
<script src="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
<script>
(function () {
    var T = {
        selectBranchFirst: @json(__('Select branch first')),
        anyStaff:          @json(__('Any staff')),
        person:            @json(__('Person')),
        addService:        @json(__('Add service')),
        noServices:        @json(__('Select a branch and add services.')),
        total:             @json(__('Total')),
        minutes:           @json(__('min')),
        services:          @json(__('services')),
    };

    var arLocale = {
        weekdays: {
            shorthand: ['أحد','اثن','ثلا','أربع','خمي','جمع','سبت'],
            longhand:  ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']
        },
        months: {
            shorthand: ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
            longhand:  ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
        },
        firstDayOfWeek: 0, time_24hr: true
    };

    var isAr  = {{ $isAr ? 'true' : 'false' }};
    var fpOpts = {
        enableTime:  true,
        dateFormat:  'Y-m-d H:i',
        time_24hr:   true,
        minDate:     'today',
        disableMobile: true,
        locale: isAr ? arLocale : undefined,
        onChange: function () { updateSummary(); }
    };

    var personsContainer = document.getElementById('persons-container');
    var addPersonBtn     = document.getElementById('add-person-btn');
    var summaryList      = document.getElementById('summary-list');
    var summaryTotal     = document.getElementById('summary-total');
    var submitBtn        = document.getElementById('submit-btn');
    var apiUrl           = @json(route('company.appointments.branch-data'));

    var branchServices  = [];
    var branchEmployees = [];
    var personCount     = 0;

    /* ── Step indicator update ── */
    function updateSteps() {
        var branchSelected = !!document.querySelector('input[name="branch_id"]:checked');
        var hasServices = personsContainer.querySelectorAll('.svc-select').length > 0;
        var hasFilledService = false;
        personsContainer.querySelectorAll('.svc-select').forEach(function(s) {
            if (s.value) hasFilledService = true;
        });

        document.querySelectorAll('.bk-step').forEach(function(s) {
            var step = parseInt(s.dataset.step);
            s.classList.toggle('active', step === 1 ? true : step === 2 ? branchSelected : hasFilledService);
            s.classList.toggle('completed', step === 1 ? branchSelected : step === 2 ? hasFilledService : false);
        });

        submitBtn.disabled = !(branchSelected && hasFilledService);
    }

    /* ── Branch selection via cards ── */
    document.querySelectorAll('.bk-branch-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.bk-branch-card').forEach(function(c) { c.classList.remove('selected'); });
            card.classList.add('selected');
            var input = card.querySelector('input');
            input.checked = true;
            loadBranchData(input.value);
        });
        if (card.querySelector('input').checked) {
            card.classList.add('selected');
        }
    });

    /* ── Load branch data ── */
    function loadBranchData(branchId) {
        if (!branchId) return;
        fetch(apiUrl + '?branch_id=' + branchId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(function (data) {
            branchServices  = data.services;
            branchEmployees = data.employees;
            personsContainer.innerHTML = '';
            personCount = 0;
            addPersonBtn.disabled = false;
            addPerson();
            updateSteps();
        });
    }

    /* ── Add a person card ── */
    function addPerson() {
        var idx = personCount++;
        var tpl = document.getElementById('person-tpl').innerHTML
            .replaceAll('__IDX__', idx)
            .replaceAll('__NUM__', idx + 1);

        var wrap = document.createElement('div');
        wrap.innerHTML = tpl;
        var card = wrap.firstElementChild;
        personsContainer.appendChild(card);

        if (idx > 0) {
            card.querySelector('.remove-person-btn').classList.remove('d-none');
            card.querySelector('.remove-person-btn').style.display = 'flex';
            card.querySelector('.remove-person-btn').addEventListener('click', function () {
                card.style.animation = 'bk-slideOut .2s ease';
                setTimeout(function() { card.remove(); updateSummary(); updateSteps(); }, 200);
            });
        }

        card.addEventListener('change',  function() { updateSummary(); updateSteps(); });
        card.addEventListener('input',   function() { updateSummary(); updateSteps(); });
        card.querySelector('.add-service-btn').addEventListener('click', function () {
            addServiceRow(card, idx);
        });

        addServiceRow(card, idx);
        card.style.animation = 'bk-slideIn .3s ease';
        feather.replace();
        updateSummary();
        updateSteps();
    }

    /* ── Add a service row ── */
    function addServiceRow(card, personIdx) {
        var list   = card.querySelector('.services-list');
        var svcIdx = list.querySelectorAll('.bk-svc-row').length;

        var tpl = document.getElementById('svc-tpl').innerHTML
            .replaceAll('__PIDX__', personIdx)
            .replaceAll('__SIDX__', svcIdx);

        var wrap = document.createElement('div');
        wrap.innerHTML = tpl;
        var row  = wrap.firstElementChild;
        list.appendChild(row);

        var svcSel = row.querySelector('.svc-select');
        var empSel = row.querySelector('.emp-select');

        branchServices.forEach(function (s) {
            var opt = new Option(
                s.name + ' (' + s.duration + ' ' + T.minutes + ' — ' + parseFloat(s.price).toFixed(2) + ')',
                s.id
            );
            opt.dataset.price    = s.price;
            opt.dataset.duration = s.duration;
            opt.dataset.name     = s.name;
            svcSel.appendChild(opt);
        });

        branchEmployees.forEach(function (e) {
            empSel.appendChild(new Option(e.name, e.id));
        });

        svcSel.addEventListener('change', function () {
            var opt = svcSel.options[svcSel.selectedIndex];
            var hint = row.querySelector('.svc-hint');
            if (opt && opt.value) {
                hint.innerHTML = '<span class="bk-svc-badge"><i data-feather="clock" style="width:10px;height:10px;"></i> ' + opt.dataset.duration + ' ' + T.minutes + '</span>'
                    + '<span class="bk-svc-badge bk-svc-price"><i data-feather="tag" style="width:10px;height:10px;"></i> ' + parseFloat(opt.dataset.price).toFixed(2) + '</span>';
                feather.replace();
            } else {
                hint.innerHTML = '';
            }
            updateSummary();
            updateSteps();
            updatePersonServiceCount(card);
        });

        if (svcIdx > 0) {
            row.querySelector('.remove-svc-btn').classList.remove('d-none');
            row.querySelector('.remove-svc-btn').addEventListener('click', function () {
                row.style.animation = 'bk-slideOut .2s ease';
                setTimeout(function() {
                    row.remove();
                    updateSummary();
                    updateSteps();
                    updatePersonServiceCount(card);
                }, 200);
            });
        }

        var fpInput = row.querySelector('.fp-datetime');
        flatpickr(fpInput, Object.assign({}, fpOpts, {
            defaultDate: new Date(Date.now() + 3600000)
        }));

        feather.replace();
        updateSummary();
        updatePersonServiceCount(card);
    }

    function updatePersonServiceCount(card) {
        var count = 0;
        card.querySelectorAll('.svc-select').forEach(function(s) { if (s.value) count++; });
        var badge = card.querySelector('.bk-person-services-count');
        if (badge) {
            badge.textContent = count + ' ' + T.services;
            badge.style.background = count > 0 ? 'rgba(201,162,39,.15)' : 'rgba(255,255,255,.08)';
            badge.style.color = count > 0 ? '#C9A227' : 'var(--cal-text-muted, #94a3b8)';
        }
    }

    /* ── Summary panel ── */
    function updateSummary() {
        var total = 0;
        var html  = '';
        var personNum = 0;

        personsContainer.querySelectorAll('.bk-person-card').forEach(function (card) {
            personNum++;
            var nameEl = card.querySelector('input[name$="[name]"]');
            var name   = nameEl && nameEl.value.trim() ? nameEl.value.trim() : T.person + ' ' + personNum;
            var rows   = card.querySelectorAll('.bk-svc-row');
            if (!rows.length) return;

            var personHasServices = false;
            var personHtml = '';

            rows.forEach(function (row) {
                var sel = row.querySelector('.svc-select');
                var opt = sel && sel.options[sel.selectedIndex];
                if (opt && opt.value) {
                    var price = parseFloat(opt.dataset.price || 0);
                    total += price;
                    personHasServices = true;
                    personHtml += '<div class="bk-summary-item">';
                    personHtml += '<span class="bk-summary-svc-name">' + escHtml(opt.dataset.name || opt.text.split('(')[0].trim()) + '</span>';
                    personHtml += '<span class="bk-summary-svc-price">' + price.toFixed(2) + '</span></div>';
                }
            });

            if (personHasServices) {
                html += '<div class="bk-summary-person">';
                html += '<div class="bk-summary-person-name">';
                html += '<div class="bk-summary-avatar">' + escHtml(name.charAt(0)) + '</div>';
                html += '<strong>' + escHtml(name) + '</strong></div>';
                html += personHtml;
                html += '</div>';
            }
        });

        if (!html) {
            html = '<div class="bk-empty-summary"><div class="bk-empty-anim"><i data-feather="calendar" style="width:32px;height:32px;"></i></div><p class="mb-1 fw-semibold">' + T.noServices + '</p></div>';
        }

        summaryList.innerHTML = html;
        summaryTotal.textContent = total.toFixed(2);
        feather.replace();
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    addPersonBtn.addEventListener('click', addPerson);

    var selectedBranch = document.querySelector('input[name="branch_id"]:checked');
    if (selectedBranch) loadBranchData(selectedBranch.value);

    /* ── Select2 for branch selector (fallback if branches > 5) ── */
    if (typeof $ !== 'undefined' && $.fn.select2 && document.querySelectorAll('.bk-branch-card').length > 8) {
        // For many branches, show a searchable dropdown too
    }
})();
</script>
<style>
/* ══════════════════════════════════
   CREATE APPOINTMENT — ENHANCED UI
══════════════════════════════════ */

/* Animations */
@keyframes bk-slideIn {
    from { opacity:0; transform:translateY(-12px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes bk-slideOut {
    from { opacity:1; transform:translateY(0); }
    to   { opacity:0; transform:translateY(-12px) scale(.96); }
}
@keyframes bk-pulse-soft {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Step indicator */
.bk-steps {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 16px 20px;
    background: var(--cal-surface, #1e1e2d);
    border-radius: 14px;
    border: 1px solid var(--cal-border, rgba(255,255,255,.06));
    box-shadow: 0 2px 12px rgba(0,0,0,.15);
}
.bk-step {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--cal-text-muted, #64748b);
    transition: all .25s;
}
.bk-step.active { color: #C9A227; }
.bk-step.completed { color: #10b981; }
.bk-step-num {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .72rem;
    font-weight: 800;
    background: rgba(255,255,255,.08);
    color: var(--cal-text-muted, #64748b);
    transition: all .25s;
}
.bk-step.active .bk-step-num {
    background: linear-gradient(135deg, #C9A227, #d4af37);
    color: #fff;
    box-shadow: 0 2px 8px rgba(201,162,39,.4);
}
.bk-step.completed .bk-step-num {
    background: #10b981;
    color: #fff;
}
.bk-step-line {
    flex: 1;
    height: 2px;
    background: rgba(255,255,255,.08);
    margin: 0 12px;
    border-radius: 2px;
    position: relative;
}

/* Card styling */
.bk-create-card {
    border: 1px solid var(--cal-border, rgba(255,255,255,.06)) !important;
    transition: border-color .2s, box-shadow .2s;
}
.bk-create-card:hover {
    border-color: rgba(201,162,39,.2) !important;
}

/* Section title */
.bk-section-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.bk-section-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(201,162,39,.12);
    color: #C9A227;
    flex-shrink: 0;
}

/* Branch grid */
.bk-branch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
}
.bk-branch-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    border-radius: 12px;
    border: 2px solid var(--cal-border, rgba(255,255,255,.08));
    background: var(--cal-surface2, rgba(255,255,255,.03));
    cursor: pointer;
    transition: all .2s;
    text-align: center;
}
.bk-branch-card:hover {
    border-color: rgba(201,162,39,.3);
    background: rgba(201,162,39,.06);
    transform: translateY(-2px);
}
.bk-branch-card.selected {
    border-color: #C9A227;
    background: rgba(201,162,39,.1);
    box-shadow: 0 4px 16px rgba(201,162,39,.25);
}
.bk-branch-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(201,162,39,.12);
    color: #C9A227;
}
.bk-branch-card.selected .bk-branch-icon {
    background: #C9A227;
    color: #fff;
}
.bk-branch-name {
    font-size: .82rem;
    font-weight: 700;
    color: var(--cal-text, #e2e8f0);
}

/* Person card */
.bk-person-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--cal-border, rgba(255,255,255,.06));
    background: var(--cal-surface2, rgba(255,255,255,.02));
    border-radius: 16px 16px 0 0;
}
.bk-person-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
    color: #fff;
}
.bk-person-services-count {
    font-size: .68rem;
    font-weight: 700;
    padding: 3px 10px;
    background: rgba(255,255,255,.08);
    color: var(--cal-text-muted, #94a3b8);
}

/* Input styling */
.bk-input {
    border-radius: 10px !important;
    transition: border-color .2s, box-shadow .2s;
}
.bk-input:focus {
    border-color: rgba(201,162,39,.4) !important;
    box-shadow: 0 0 0 3px rgba(201,162,39,.1) !important;
}
.bk-select {
    border-radius: 10px !important;
}
.bk-textarea {
    border-radius: 12px !important;
    resize: none;
}
.bk-textarea:focus {
    border-color: rgba(201,162,39,.4) !important;
    box-shadow: 0 0 0 3px rgba(201,162,39,.1) !important;
}

/* Add service button */
.bk-add-svc-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 10px;
    border: 1.5px dashed rgba(124,58,237,.3);
    background: transparent;
    color: #7c3aed;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    width: 100%;
    justify-content: center;
}
.bk-add-svc-btn:hover {
    background: rgba(124,58,237,.08);
    border-color: rgba(124,58,237,.5);
}

/* Add person button */
.bk-add-person-btn {
    background: transparent;
    border: 2px dashed var(--cal-border, rgba(255,255,255,.1));
    border-radius: 16px;
    padding: 0;
    cursor: pointer;
    transition: all .25s;
    color: var(--cal-text, #e2e8f0);
}
.bk-add-person-btn:disabled {
    opacity: .4;
    cursor: not-allowed;
}
.bk-add-person-btn:not(:disabled):hover {
    border-color: rgba(201,162,39,.3);
    background: rgba(201,162,39,.04);
    transform: translateY(-1px);
}
.bk-add-person-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 20px;
    font-size: .84rem;
    font-weight: 600;
}

/* Service row */
.bk-svc-row {
    padding: 12px 0;
    border-bottom: 1px solid var(--cal-border, rgba(255,255,255,.04));
    animation: bk-slideIn .25s ease;
}
.bk-svc-row:last-child { border-bottom: none; }

/* Service hint badges */
.bk-svc-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 8px;
    font-size: .7rem;
    font-weight: 600;
    background: rgba(255,255,255,.06);
    color: var(--cal-text-soft, #94a3b8);
    margin-inline-end: 6px;
}
.bk-svc-price {
    background: rgba(201,162,39,.1) !important;
    color: #C9A227 !important;
}

/* Payment options */
.bk-payment-options {
    display: flex;
    gap: 8px;
}
.bk-pay-opt input { display: none; }
.bk-pay-card {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 2px solid var(--cal-border, rgba(255,255,255,.08));
    background: var(--cal-surface2, rgba(255,255,255,.03));
    cursor: pointer;
    transition: all .2s;
    font-size: .78rem;
    font-weight: 600;
    color: var(--cal-text, #e2e8f0);
}
.bk-pay-opt input:checked + .bk-pay-card {
    border-color: #C9A227;
    background: rgba(201,162,39,.08);
    box-shadow: 0 2px 8px rgba(201,162,39,.15);
}
.bk-pay-card:hover {
    border-color: rgba(201,162,39,.25);
}

/* Summary card */
.bk-summary-card {
    border: 1px solid var(--cal-border, rgba(255,255,255,.06)) !important;
    overflow: hidden;
}
.bk-summary-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: linear-gradient(135deg, rgba(201,162,39,.08), rgba(201,162,39,.02));
    border-bottom: 1px solid var(--cal-border, rgba(255,255,255,.06));
    color: #C9A227;
    font-size: .88rem;
}
.bk-empty-summary {
    text-align: center;
    padding: 24px 0;
    color: var(--cal-text-muted, #64748b);
}
.bk-empty-anim {
    width: 56px;
    height: 56px;
    margin: 0 auto 12px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.04);
    color: var(--cal-text-muted, #64748b);
    animation: bk-pulse-soft 3s ease infinite;
}
.bk-summary-person {
    margin-bottom: 14px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--cal-border, rgba(255,255,255,.04));
}
.bk-summary-person:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.bk-summary-person-name {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: .82rem;
}
.bk-summary-avatar {
    width: 24px;
    height: 24px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
    color: #fff;
    font-size: .65rem;
    font-weight: 800;
    flex-shrink: 0;
}
.bk-summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0 4px 32px;
    font-size: .78rem;
}
.bk-summary-svc-name {
    color: var(--cal-text-soft, #94a3b8);
}
.bk-summary-svc-price {
    font-weight: 700;
    color: var(--cal-text, #e2e8f0);
}
.bk-summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-top: 2px solid var(--cal-border, rgba(255,255,255,.08));
    font-size: 1rem;
    font-weight: 800;
}
.bk-summary-total span:last-child {
    color: #C9A227;
    font-size: 1.15rem;
}

/* Submit button */
.bk-submit-btn {
    background: linear-gradient(135deg, #C9A227, #d4af37) !important;
    color: #fff !important;
    border: none !important;
    border-radius: 12px !important;
    font-size: .88rem !important;
    box-shadow: 0 4px 16px rgba(201,162,39,.35);
    transition: all .25s;
}
.bk-submit-btn:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(201,162,39,.45);
}
.bk-submit-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* Light theme overrides */
.bk-theme-light .bk-steps {
    background: #fff;
    border-color: #e5e7eb;
}
.bk-theme-light .bk-step-num {
    background: #f3f4f6;
    color: #6b7280;
}
.bk-theme-light .bk-branch-card {
    border-color: #e5e7eb;
    background: #f9fafb;
}
.bk-theme-light .bk-branch-card:hover {
    background: rgba(201,162,39,.04);
}
.bk-theme-light .bk-branch-name {
    color: #1e293b;
}
.bk-theme-light .bk-pay-card {
    border-color: #e5e7eb;
    background: #f9fafb;
    color: #1e293b;
}
.bk-theme-light .bk-person-header {
    background: #f9fafb;
}
.bk-theme-light .bk-svc-badge {
    background: #f3f4f6;
    color: #64748b;
}

/* Responsive */
@media (max-width: 575.98px) {
    .bk-branch-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .bk-payment-options {
        flex-direction: column;
    }
    .bk-steps span {
        display: none;
    }
}
</style>
@endpush
