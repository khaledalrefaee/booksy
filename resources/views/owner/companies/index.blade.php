@extends('owner.dashboard')
@section('content')

@php
    // Sort-link builder: toggles direction on the active column, resets to page 1.
    $sortUrl = function (string $field) use ($sortField, $sortDir) {
        $dir = ($sortField === $field && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $field, 'dir' => $dir, 'page' => 1]);
    };
    // Returns a VALID feather icon name for the active column, or null when the
    // column isn't the current sort (feather has no "chevrons-up-down").
    $sortCaret = function (string $field) use ($sortField, $sortDir) {
        if ($sortField !== $field) return null;
        return $sortDir === 'asc' ? 'chevron-up' : 'chevron-down';
    };
    $filtersOpen = ($filterCategoryId !== '' && $filterCategoryId !== null)
        || ($filterPlanId !== '' && $filterPlanId !== null)
        || ($filterDate !== '' && $filterDate !== null);

    $statusMeta = [
        'active'    => ['label' => __('Active'),    'cls' => 'cm-st-active'],
        'pending'   => ['label' => __('Pending'),   'cls' => 'cm-st-pending'],
        'suspended' => ['label' => __('Suspended'), 'cls' => 'cm-st-suspended'],
    ];
@endphp

<div class="page-content cm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="cm-head cm-reveal">
        <div>
            <div class="cm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('Platform') }}
            </div>
            <h1 class="cm-title">{{ __('Companies') }}</h1>
            <p class="cm-subtitle">{{ __('Manage and monitor every business registered on GlowRez.') }}</p>
        </div>
        <div class="cm-head-actions">
            <button type="button" class="cm-btn cm-btn-ghost"
                    data-bs-toggle="modal" data-bs-target="#modal-companies-import">
                <i data-feather="upload"></i>
                {{ __('Import Excel') }}
            </button>
            <a href="{{ route('owner.companies.export', request()->except('page')) }}"
               class="cm-btn cm-btn-excel" id="cm-export-btn" data-label="{{ __('Export Excel') }}">
                <i data-feather="download"></i>
                <span class="cm-export-text">{{ __('Export Excel') }}</span>
            </a>
            <button type="button" class="cm-btn cm-btn-primary"
                    data-bs-toggle="modal" data-bs-target="#modal-campania-create">
                <i data-feather="plus"></i>
                {{ __('Add company') }}
            </button>
        </div>
    </header>

    {{-- Flash + validation errors surface as GlowRez toasts (see owner/partials/flash) --}}
    @include('owner.partials.flash')

    {{-- ═══════════ OVERVIEW ═══════════ --}}
    <section class="cm-stats cm-reveal" aria-label="{{ __('Overview') }}">
        <div class="cm-stat" style="--accent:var(--bk-accent);">
            <span class="cm-stat-label">{{ __('Total companies') }}</span>
            <span class="cm-stat-value">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="cm-stat" style="--accent:var(--bk-success);">
            <span class="cm-stat-label">{{ __('Active') }}</span>
            <span class="cm-stat-value">{{ number_format($stats['active']) }}</span>
        </div>
        <div class="cm-stat" style="--accent:var(--bk-warning);">
            <span class="cm-stat-label">{{ __('Pending') }}</span>
            <span class="cm-stat-value">{{ number_format($stats['pending']) }}</span>
        </div>
        <div class="cm-stat" style="--accent:var(--bk-danger);">
            <span class="cm-stat-label">{{ __('Suspended') }}</span>
            <span class="cm-stat-value">{{ number_format($stats['suspended']) }}</span>
        </div>
        <div class="cm-stat" style="--accent:var(--bk-gold);">
            <span class="cm-stat-label">{{ __('New this month') }}</span>
            <span class="cm-stat-value">{{ number_format($stats['new_month']) }}</span>
        </div>
    </section>

    {{-- ═══════════ TOOLBAR + FILTERS ═══════════ --}}
    <form method="GET" action="{{ route('owner.companies.index') }}" class="cm-toolbar cm-reveal" id="cm-filter-form">
        {{-- keep active sort while filtering --}}
        <input type="hidden" name="sort" value="{{ $sortField }}">
        <input type="hidden" name="dir"  value="{{ $sortDir }}">

        <div class="cm-toolbar-row">
            <div class="cm-search">
                <button type="submit" class="cm-search-btn" aria-label="{{ __('Search companies') }}" tabindex="-1">
                    <i data-feather="search"></i>
                </button>
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="{{ __('Search by name, owner, email or phone…') }}"
                       autocomplete="off" aria-label="{{ __('Search companies') }}"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}">
            </div>

            <select name="status" class="cm-select" onchange="document.getElementById('cm-filter-form').submit()"
                    aria-label="{{ __('Status') }}">
                <option value="">{{ __('All statuses') }}</option>
                <option value="pending"   @selected($filterStatus === 'pending')>{{ __('Pending') }}</option>
                <option value="active"    @selected($filterStatus === 'active')>{{ __('Active') }}</option>
                <option value="suspended" @selected($filterStatus === 'suspended')>{{ __('Suspended') }}</option>
            </select>

            <button type="button" class="cm-btn cm-btn-ghost" id="cm-more-filters" aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}">
                <i data-feather="sliders"></i>
                {{ __('More filters') }}
                @if($activeFilters > 0)
                    <span class="cm-badge">{{ $activeFilters }}</span>
                @endif
            </button>

            @if($q !== '' || $activeFilters > 0)
                <a href="{{ route('owner.companies.index') }}" class="cm-clear">
                    <i data-feather="x"></i> {{ __('Clear all') }}
                </a>
            @endif
        </div>

        <div class="cm-filters {{ $filtersOpen ? 'open' : '' }}" id="cm-filters-panel">
            <div class="cm-filters-grid">
                <div class="cm-field">
                    <label for="cm-f-category">{{ __('Category') }}</label>
                    <select name="category_id" id="cm-f-category" class="cm-select w-100"
                            onchange="document.getElementById('cm-filter-form').submit()">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)$filterCategoryId === (string)$c->id)>{{ $c->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cm-field">
                    <label for="cm-f-plan">{{ __('Plan') }}</label>
                    <select name="plan_id" id="cm-f-plan" class="cm-select w-100"
                            onchange="document.getElementById('cm-filter-form').submit()">
                        <option value="">{{ __('All plans') }}</option>
                        <option value="none" @selected($filterPlanId === 'none')>{{ __('No plan') }}</option>
                        @foreach($plans as $p)
                            <option value="{{ $p->id }}" @selected((string)$filterPlanId === (string)$p->id)>{{ $p->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cm-field">
                    <label for="cm-f-date">{{ __('Registered') }}</label>
                    <select name="date" id="cm-f-date" class="cm-select w-100"
                            onchange="cmToggleCustomDate(this); if(this.value!=='custom') document.getElementById('cm-filter-form').submit();">
                        <option value="">{{ __('All time') }}</option>
                        <option value="today" @selected($filterDate === 'today')>{{ __('Today') }}</option>
                        <option value="week"  @selected($filterDate === 'week')>{{ __('This week') }}</option>
                        <option value="month" @selected($filterDate === 'month')>{{ __('This month') }}</option>
                        <option value="custom" @selected($filterDate === 'custom')>{{ __('Custom range') }}</option>
                    </select>
                </div>

                <div class="cm-field cm-custom-date {{ $filterDate === 'custom' ? '' : 'd-none' }}" id="cm-custom-date">
                    <label>{{ __('From – To') }}</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="cm-select w-100">
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="cm-select w-100">
                        <button type="submit" class="cm-btn cm-btn-primary" style="height:42px;">{{ __('Apply') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ═══════════ TABLE ═══════════ --}}
    <div class="cm-card cm-reveal">
        <div class="cm-table-scroll">
            <table class="cm-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ $sortUrl('name') }}" class="cm-sort {{ $sortField === 'name' ? 'is-active' : '' }}">
                                {{ __('Company') }}
                                @if($c = $sortCaret('name'))<i data-feather="{{ $c }}" class="cm-sort-caret"></i>@endif
                            </a>
                        </th>
                        <th class="cm-col-category">{{ __('Category') }}</th>
                        <th class="cm-col-plan">{{ __('Subscription') }}</th>
                        <th>
                            <a href="{{ $sortUrl('status') }}" class="cm-sort {{ $sortField === 'status' ? 'is-active' : '' }}">
                                {{ __('Status') }}
                                @if($c = $sortCaret('status'))<i data-feather="{{ $c }}" class="cm-sort-caret"></i>@endif
                            </a>
                        </th>
                        <th class="cm-col-created">
                            <a href="{{ $sortUrl('created_at') }}" class="cm-sort {{ $sortField === 'created_at' ? 'is-active' : '' }}">
                                {{ __('Created') }}
                                @if($c = $sortCaret('created_at'))<i data-feather="{{ $c }}" class="cm-sort-caret"></i>@endif
                            </a>
                        </th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        @php
                            $logoPreview = $company->logo ? asset('storage/'.$company->logo) : '';
                            $initials = mb_strtoupper(mb_substr($company->name_en ?: ($company->name_ar ?: '؟'), 0, 1));
                            $meta = $statusMeta[$company->status] ?? ['label' => $company->status, 'cls' => 'cm-st-pending'];
                        @endphp
                        <tr>
                            {{-- Company + contact --}}
                            <td>
                                <div class="cm-company">
                                    @if ($company->logo)
                                        <img loading="lazy" src="{{ asset('storage/'.$company->logo) }}" alt="" class="cm-avatar">
                                    @else
                                        <span class="cm-avatar-fallback" aria-hidden="true">{{ $initials }}</span>
                                    @endif
                                    <div class="cm-company-info">
                                        <div class="cm-company-name">{{ $company->name_en ?: ($company->name_ar ?: '—') }}</div>
                                        @if($company->name_ar && $company->name_en)
                                            <div class="cm-company-ar" lang="ar" dir="rtl">{{ $company->name_ar }}</div>
                                        @endif
                                        <div class="cm-contact-lines">
                                            @if($company->owner_name)
                                                <span class="cm-contact-line"><i data-feather="user"></i>{{ $company->owner_name }}</span>
                                            @endif
                                            <span class="cm-contact-line"><i data-feather="mail"></i>{{ $company->email }}</span>
                                            @if($company->phone)
                                                <span class="cm-contact-line" dir="ltr"><i data-feather="phone"></i>{{ $company->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Category --}}
                            <td class="cm-col-category">
                                <span class="cm-chip">{{ $company->category?->localizedName() ?? '—' }}</span>
                            </td>

                            {{-- Subscription / plan --}}
                            <td class="cm-col-plan">
                                @if($company->plan)
                                    <div class="cm-plan-name"><i data-feather="award"></i> {{ $company->plan->localizedName() }}</div>
                                    @if($company->plan_expires_at)
                                        <div class="cm-plan-exp">{{ __('Expires') }} {{ $company->plan_expires_at->format('Y-m-d') }}</div>
                                    @endif
                                @else
                                    <span class="cm-plan-none">{{ __('No plan') }}</span>
                                @endif
                            </td>

                            {{-- Status (inline change form — preserved behavior) --}}
                            <td>
                                <form method="post" action="{{ route('owner.companies.update-status', $company) }}" class="company-status-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="reason" value="">
                                    <select name="status"
                                            class="cm-status-select {{ $meta['cls'] }}"
                                            data-company-name="{{ $company->localizedName() }}"
                                            data-original-status="{{ $company->status }}"
                                            onchange="bkStatusChanged(this)">
                                        <option value="pending"   @selected($company->status === 'pending')>{{ __('Pending') }}</option>
                                        <option value="active"    @selected($company->status === 'active')>{{ __('Active') }}</option>
                                        <option value="suspended" @selected($company->status === 'suspended')>{{ __('Suspended') }}</option>
                                    </select>
                                </form>
                            </td>

                            {{-- Created --}}
                            <td class="cm-col-created">
                                <div class="cm-created-date">{{ $company->created_at?->format('Y-m-d') ?? '—' }}</div>
                                <div class="cm-created-ago">{{ $company->created_at?->diffForHumans() }}</div>
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="cm-actions">
                                    <a href="{{ route('owner.companies.show', $company) }}"
                                       class="cm-act" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <button type="button" class="cm-act" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"
                                        data-bs-toggle="modal" data-bs-target="#modal-campania-edit"
                                        data-company-id="{{ $company->id }}"
                                        data-company-name-en="{{ $company->name_en ?? '' }}"
                                        data-company-name-ar="{{ $company->name_ar ?? '' }}"
                                        data-company-email="{{ $company->email }}"
                                        data-company-phone="{{ $company->phone ?? '' }}"
                                        data-company-category-id="{{ $company->category_id }}"
                                        data-update-url="{{ route('owner.companies.update', $company) }}"
                                        data-logo-src="{{ $logoPreview }}">
                                        <i data-feather="edit-2"></i>
                                    </button>
                                    <button type="button" class="cm-act cm-act-danger" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"
                                        data-bs-toggle="modal" data-bs-target="#modal-campania-delete"
                                        data-delete-url="{{ route('owner.companies.destroy', $company) }}"
                                        data-company-display="{{ $company->localizedName() }}">
                                        <i data-feather="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="cm-empty">
                                    <i data-feather="inbox"></i>
                                    <p class="cm-empty-title">{{ __('No companies match your filters.') }}</p>
                                    @if($q !== '' || $activeFilters > 0)
                                        <a href="{{ route('owner.companies.index') }}" class="cm-btn cm-btn-ghost">{{ __('Clear all') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
            <div class="cm-pagination">
                <div class="cm-pagination-info">
                    {{ __('Showing :from–:to of :total', [
                        'from'  => $companies->firstItem(),
                        'to'    => $companies->lastItem(),
                        'total' => $companies->total(),
                    ]) }}
                </div>
                {{ $companies->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════ IMPORT MODAL ═══════════ --}}
    <div class="modal fade" id="modal-companies-import" tabindex="-1" aria-labelledby="modal-companies-import-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cm-modal">
                <div class="modal-header">
                    <div class="cm-modal-titlewrap">
                        <span class="cm-modal-ic" aria-hidden="true"><i data-feather="upload-cloud"></i></span>
                        <div>
                            <h5 class="modal-title" id="modal-companies-import-label">{{ __('Import companies') }}</h5>
                            <div class="cm-modal-sub">{{ __('Bulk-create companies from an Excel file.') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('owner.companies.import') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <ol class="cm-import-steps">
                            <li>
                                {{ __('Download the template and fill in one company per row.') }}
                                <a href="{{ route('owner.companies.import-template') }}" class="cm-import-tmpl">
                                    <i data-feather="download"></i> {{ __('Download template') }}
                                </a>
                            </li>
                            <li>{{ __('Required columns: name_en, name_ar, email, category. Optional: phone, plan, status, password.') }}</li>
                            <li>{{ __('Upload the completed file below — invalid rows are skipped and reported.') }}</li>
                        </ol>
                        <label class="form-label fw-semibold" for="cm-import-file">{{ __('Excel file') }} <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="cm-import-file" required accept=".xlsx,.xls,.csv" class="form-control form-control-lg">
                        <div class="cm-modal-foot">
                            <button type="button" class="cm-btn cm-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="cm-btn cm-btn-primary"><i data-feather="upload"></i> {{ __('Import') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODALS (preserved) ═══════════ --}}
    @include('owner.companies.create', ['categories' => $categories])
    @include('owner.companies.edit', ['categories' => $categories])
    @include('owner.companies.delete')

    {{-- Status-change reason modal --}}
    <div class="modal fade" id="modal-status-reason" tabindex="-1" aria-labelledby="modal-status-reason-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modal-status-reason-title">{{ __('Change company status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="bk-status-summary"></p>
                    <label for="bk-status-reason" class="form-label fw-semibold">
                        {{ __('Reason') }}
                        <span class="text-danger d-none" id="bk-status-reason-required" aria-hidden="true">*</span>
                    </label>
                    <textarea class="form-control" id="bk-status-reason" rows="3" maxlength="500"></textarea>
                    <div class="form-text" id="bk-status-reason-hint"></div>
                    <div class="invalid-feedback" id="bk-status-reason-error">{{ __('A reason is required when suspending a company.') }}</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn rounded-pill" id="bk-status-confirm"></button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('owner-styles')
{{-- Fraunces display serif — self-hosted (no external request; works offline) --}}
<link href="{{ asset('fonts/fraunces.css') }}" rel="stylesheet">
<style>
/* ═══════════════ Companies Management — Luxury SaaS ═══════════════ */
.cm-wrap { --cm-radius: 16px; }
.cm-wrap a { text-decoration: none; }

/* Reveal */
@keyframes cmReveal { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
.cm-reveal { animation: cmReveal .45s cubic-bezier(.2,.7,.3,1) both; }
.cm-reveal:nth-of-type(2){ animation-delay:.05s; }
.cm-reveal:nth-of-type(3){ animation-delay:.1s; }
.cm-reveal:nth-of-type(4){ animation-delay:.15s; }

/* ── Header ── */
.cm-head { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; flex-wrap:wrap; margin-bottom:24px; }
.cm-eyebrow { font-size:.72rem; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); font-weight:600; margin-bottom:8px; }
.cm-eyebrow a { color:var(--bk-gold-strong); }
.cm-eyebrow a:hover { color:var(--bk-gold); }
/* Fraunces for Latin; Tajawal falls in per-glyph for Arabic (Fraunces has no Arabic). !important beats the app's global RTL font rule. */
.cm-title { font-family:'Fraunces', 'Tajawal', Georgia, serif !important; font-size:2.1rem; font-weight:600; color:var(--bk-text); line-height:1.05; margin:0; letter-spacing:-.015em; }
.cm-subtitle { color:var(--bk-text-muted); font-size:.92rem; margin:8px 0 0; max-width:56ch; }
.cm-head-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

/* ── Buttons ── */
.cm-btn { display:inline-flex; align-items:center; gap:8px; height:44px; padding:0 18px; border-radius:12px;
    font-size:.87rem; font-weight:600; border:1px solid transparent; cursor:pointer; white-space:nowrap;
    transition:background .18s, border-color .18s, color .18s, transform .18s, box-shadow .18s; }
.cm-btn i, .cm-btn svg { width:16px; height:16px; }
.cm-btn-primary { background:var(--bk-accent); color:var(--bk-accent-ink); box-shadow:var(--bk-shadow); }
.cm-btn-primary:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); transform:translateY(-1px); box-shadow:var(--bk-shadow-lg); }
.cm-btn-ghost { background:var(--bk-surface); color:var(--bk-text-soft); border-color:var(--bk-border); }
.cm-btn-ghost:hover { border-color:var(--bk-gold); color:var(--bk-text); }
.cm-btn-excel { background:var(--bk-surface); color:var(--bk-success); border-color:var(--bk-border); }
.cm-btn-excel:hover { border-color:var(--bk-success); background:var(--bk-success-bg); color:var(--bk-success); }
.cm-btn-excel.is-loading { pointer-events:none; opacity:.75; }
.cm-badge { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px;
    border-radius:999px; background:var(--bk-gold); color:var(--bk-gold-ink); font-size:.68rem; font-weight:700; }
.cm-clear { display:inline-flex; align-items:center; gap:5px; height:44px; padding:0 12px; border-radius:12px;
    color:var(--bk-danger); font-size:.84rem; font-weight:600; border:1px solid transparent; transition:background .15s; }
.cm-clear i, .cm-clear svg { width:14px; height:14px; stroke-width:2; }
.cm-clear:hover { background:var(--bk-danger-bg); color:var(--bk-danger); }

/* ── Overview ── */
.cm-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:22px; }
.cm-stat { position:relative; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px;
    padding:15px 16px 15px 20px; overflow:hidden; box-shadow:var(--bk-shadow); }
.cm-stat::before { content:''; position:absolute; inset-inline-start:0; top:0; bottom:0; width:4px; background:var(--accent); }
.cm-stat-label { display:block; font-size:.71rem; text-transform:uppercase; letter-spacing:.07em; color:var(--bk-text-muted); font-weight:600; }
.cm-stat-value { display:block; margin-top:4px; font-family:'Fraunces', 'Tajawal', Georgia, serif !important; font-size:1.75rem; font-weight:600;
    color:var(--bk-text); line-height:1.1; font-variant-numeric:tabular-nums; }

/* ── Toolbar & filters ── */
.cm-toolbar { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:14px; padding:12px; margin-bottom:16px; box-shadow:var(--bk-shadow); }
.cm-toolbar-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.cm-search { position:relative; flex:1 1 280px; min-width:200px; display:flex; align-items:center; }
.cm-search-btn { position:absolute; inset-inline-start:6px; top:50%; transform:translateY(-50%); width:30px; height:30px;
    display:inline-flex; align-items:center; justify-content:center; padding:0; border:none; background:transparent;
    color:var(--bk-text-muted); cursor:pointer; border-radius:8px; transition:color .15s, background .15s; }
.cm-search-btn:hover { color:var(--bk-accent); background:var(--bk-accent-wash); }
.cm-search-btn i, .cm-search-btn svg { width:16px; height:16px; stroke-width:2; pointer-events:none; }
.cm-search input { width:100%; height:44px; padding-inline:42px 14px; border-radius:11px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.9rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.cm-search input::placeholder { color:var(--bk-text-muted); }
.cm-search input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cm-select { height:44px; padding-inline:14px 32px; border-radius:11px; border:1px solid var(--bk-border);
    background:var(--bk-bg); color:var(--bk-text); font-size:.87rem; cursor:pointer; outline:none; transition:border-color .15s, box-shadow .15s; }
.cm-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cm-select.w-100 { width:100%; }

.cm-filters { overflow:hidden; max-height:0; opacity:0; transition:max-height .32s ease, opacity .25s ease, margin-top .32s ease; }
.cm-filters.open { max-height:320px; opacity:1; margin-top:12px; }
.cm-filters-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:14px; padding-top:14px; border-top:1px dashed var(--bk-border); }
.cm-field label { display:block; font-size:.73rem; font-weight:600; color:var(--bk-text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.05em; }
.cm-custom-date { grid-column:1 / -1; }

/* ── Table ── */
.cm-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--cm-radius); overflow:hidden; box-shadow:var(--bk-shadow); }
.cm-table-scroll { overflow-x:auto; }
.cm-table { width:100%; border-collapse:collapse; min-width:640px; }
.cm-table thead th { font-size:.71rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-text-muted);
    font-weight:600; text-align:start; padding:13px 18px; background:var(--bk-bg); border-bottom:1px solid var(--bk-border); white-space:nowrap; }
.cm-table thead th.text-end { text-align:end; }
.cm-sort { color:inherit; display:inline-flex; align-items:center; gap:5px; }
.cm-sort:hover { color:var(--bk-text); }
.cm-sort.is-active { color:var(--bk-accent); }
.cm-sort-caret { width:13px; height:13px; color:var(--bk-accent); }
.cm-table tbody td { padding:14px 18px; border-bottom:1px solid var(--bk-border); vertical-align:middle; }
.cm-table tbody tr { transition:background .15s; }
.cm-table tbody tr:hover { background:var(--bk-accent-wash); }
.cm-table tbody tr:last-child td { border-bottom:none; }

.cm-company { display:flex; align-items:center; gap:13px; }
.cm-avatar, .cm-avatar-fallback { width:46px; height:46px; border-radius:13px; flex-shrink:0; }
.cm-avatar { object-fit:cover; border:1px solid var(--bk-border); }
.cm-avatar-fallback { display:flex; align-items:center; justify-content:center; font-family:'Fraunces', 'Tajawal', serif !important;
    font-weight:600; font-size:1.15rem; color:var(--bk-accent); background:var(--bk-accent-wash); border:1px solid var(--bk-border); }
.cm-company-name { font-weight:600; color:var(--bk-text); font-size:.94rem; line-height:1.25; }
.cm-company-ar { font-size:.82rem; color:var(--bk-text-soft); margin-top:1px; }
.cm-contact-lines { display:flex; flex-wrap:wrap; gap:4px 14px; margin-top:5px; }
.cm-contact-line { display:inline-flex; align-items:center; gap:6px; font-size:.78rem; color:var(--bk-text-muted); }
.cm-contact-line i, .cm-contact-line svg { width:13px; height:13px; opacity:.65; stroke-width:1.9; flex-shrink:0; }

.cm-chip { display:inline-block; padding:5px 12px; border-radius:999px; background:var(--bk-bg);
    border:1px solid var(--bk-border); color:var(--bk-text-soft); font-size:.78rem; font-weight:500; white-space:nowrap; }
.cm-plan-name { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:600; color:var(--bk-text); }
.cm-plan-name i, .cm-plan-name svg { width:14px; height:14px; color:var(--bk-gold-strong); stroke-width:1.9; }
.cm-plan-exp { font-size:.75rem; color:var(--bk-text-muted); margin-top:2px; }
.cm-plan-none { font-size:.82rem; color:var(--bk-text-muted); font-style:italic; }
.cm-created-date { font-size:.85rem; color:var(--bk-text); font-variant-numeric:tabular-nums; }
.cm-created-ago { font-size:.75rem; color:var(--bk-text-muted); margin-top:2px; }

/* Status pill select */
.cm-status-select { appearance:none; border:1px solid transparent; border-radius:999px; padding:7px 30px 7px 14px;
    font-size:.8rem; font-weight:600; cursor:pointer; outline:none; transition:filter .15s, box-shadow .15s;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 10px center; }
html[dir="rtl"] .cm-status-select { padding:7px 14px 7px 30px; background-position:left 10px center; }
.cm-status-select:focus { box-shadow:0 0 0 3px var(--bk-accent-wash); }
.cm-status-select:hover { filter:brightness(.97); }
.cm-st-active    { color:var(--bk-success); background-color:var(--bk-success-bg); border-color:color-mix(in srgb, var(--bk-success) 30%, transparent); }
.cm-st-pending   { color:var(--bk-warning); background-color:var(--bk-warning-bg); border-color:color-mix(in srgb, var(--bk-warning) 30%, transparent); }
.cm-st-suspended { color:var(--bk-danger);  background-color:var(--bk-danger-bg);  border-color:color-mix(in srgb, var(--bk-danger) 30%, transparent); }
.cm-status-select option { background:var(--bk-surface); color:var(--bk-text); }

/* Actions */
.cm-actions { display:flex; gap:6px; justify-content:flex-end; }
.cm-act { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:9px;
    border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-muted); cursor:pointer; transition:all .15s; }
.cm-act i, .cm-act svg { width:14px; height:14px; stroke-width:1.9; }
.cm-act:hover { border-color:var(--bk-gold); color:var(--bk-gold-strong); background:var(--bk-gold-soft); }
.cm-act-danger:hover { border-color:var(--bk-danger); color:var(--bk-danger); background:var(--bk-danger-bg); }

/* Empty */
.cm-empty { display:flex; flex-direction:column; align-items:center; gap:12px; padding:56px 20px; }
.cm-empty i, .cm-empty svg { width:40px; height:40px; color:var(--bk-text-muted); opacity:.5; stroke-width:1.5; }
.cm-empty-title { margin:0; color:var(--bk-text-muted); font-size:.95rem; }

/* Pagination */
.cm-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    padding:14px 18px; border-top:1px solid var(--bk-border); }
.cm-pagination-info { font-size:.8rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.cm-pagination .pagination { margin:0; }
.cm-pagination .page-link { color:var(--bk-text-soft); border-color:var(--bk-border); background:var(--bk-surface); }
.cm-pagination .page-item.active .page-link { background:var(--bk-accent); border-color:var(--bk-accent); color:var(--bk-accent-ink); }
.cm-pagination .page-link:hover { background:var(--bk-accent-wash); color:var(--bk-text); }

/* Responsive column hiding */
@media (max-width:1200px){ .cm-col-plan { display:none; } }
@media (max-width:992px){ .cm-col-category { display:none; } }
@media (max-width:768px){
    .cm-col-created { display:none; }
    .cm-title { font-size:1.7rem; }
    .cm-stats { grid-template-columns:repeat(2,1fr); }
    .cm-table { min-width:0; }
    .cm-table thead th, .cm-table tbody td { padding:12px 14px; }
    .cm-head-actions { width:100%; }
    .cm-head-actions .cm-btn { flex:1 1 auto; justify-content:center; }
}
@media (max-width:520px){ .cm-stats { grid-template-columns:repeat(2,1fr); } }

@media (prefers-reduced-motion:reduce){
    .cm-reveal { animation:none; }
    .cm-btn, .cm-table tbody tr, .cm-filters, .cm-act { transition:none; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    // ── More-filters toggle ──
    var moreBtn = document.getElementById('cm-more-filters');
    var panel   = document.getElementById('cm-filters-panel');
    if (moreBtn && panel) {
        moreBtn.addEventListener('click', function () {
            var open = panel.classList.toggle('open');
            moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ── Custom date visibility ──
    window.cmToggleCustomDate = function (sel) {
        var box = document.getElementById('cm-custom-date');
        if (box) box.classList.toggle('d-none', sel.value !== 'custom');
    };

    // ── Export loading state ──
    var exportBtn = document.getElementById('cm-export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var txt = exportBtn.querySelector('.cm-export-text');
            exportBtn.classList.add('is-loading');
            if (txt) txt.textContent = @json(__('Exporting…'));
            // The browser handles the file download; restore the label shortly after.
            setTimeout(function () {
                exportBtn.classList.remove('is-loading');
                if (txt) txt.textContent = exportBtn.dataset.label;
            }, 3500);
        });
    }
})();
</script>

<script>
/* ── Status-change confirm modal (preserved behavior) ── */
(function () {
    'use strict';

    const STATUS_LABELS = {
        pending:   @json(__('Pending')),
        active:    @json(__('Active')),
        suspended: @json(__('Suspended')),
    };
    const SUMMARY_TEMPLATE = @json(__('Change status of :company from ":from" to ":to".'));
    const HINT_REQUIRED    = @json(__('The reason is stored in the audit log and shown to the company.'));
    const HINT_OPTIONAL    = @json(__('Optional — stored in the audit log.'));
    const CONFIRM_SUSPEND  = @json(__('Suspend company'));
    const CONFIRM_DEFAULT  = @json(__('Confirm'));

    const modalEl    = document.getElementById('modal-status-reason');
    const summaryEl  = document.getElementById('bk-status-summary');
    const reasonEl   = document.getElementById('bk-status-reason');
    const requiredEl = document.getElementById('bk-status-reason-required');
    const hintEl     = document.getElementById('bk-status-reason-hint');
    const confirmBtn = document.getElementById('bk-status-confirm');
    const modal      = new bootstrap.Modal(modalEl);

    let activeSelect = null;
    let confirmed    = false;

    window.bkStatusChanged = function (select) {
        const from = select.dataset.originalStatus;
        const to   = select.value;
        if (from === to) return;

        activeSelect = select;
        confirmed    = false;
        const suspending = to === 'suspended';

        summaryEl.textContent = SUMMARY_TEMPLATE
            .replace(':company', select.dataset.companyName)
            .replace(':from', STATUS_LABELS[from] ?? from)
            .replace(':to', STATUS_LABELS[to] ?? to);

        requiredEl.classList.toggle('d-none', !suspending);
        hintEl.textContent = suspending ? HINT_REQUIRED : HINT_OPTIONAL;
        confirmBtn.textContent = suspending ? CONFIRM_SUSPEND : CONFIRM_DEFAULT;
        confirmBtn.classList.toggle('btn-danger', suspending);
        confirmBtn.classList.toggle('btn-primary', !suspending);

        reasonEl.value = '';
        reasonEl.classList.remove('is-invalid');
        modal.show();
    };

    modalEl.addEventListener('shown.bs.modal', () => reasonEl.focus());
    modalEl.addEventListener('hidden.bs.modal', () => {
        if (!confirmed && activeSelect) {
            activeSelect.value = activeSelect.dataset.originalStatus;
        }
        activeSelect = null;
    });
    reasonEl.addEventListener('input', () => reasonEl.classList.remove('is-invalid'));

    confirmBtn.addEventListener('click', () => {
        if (!activeSelect) return;
        const suspending = activeSelect.value === 'suspended';
        const reason     = reasonEl.value.trim();
        if (suspending && reason === '') {
            reasonEl.classList.add('is-invalid');
            reasonEl.focus();
            return;
        }
        confirmed = true;
        confirmBtn.disabled = true;
        activeSelect.form.querySelector('input[name="reason"]').value = reason;
        activeSelect.form.submit();
    });
})();
</script>

    @include('owner.partials.campanias-form-validation-script', [
        'formSelectors' => ['#campania-form-create-modal', '#campania-form-update-modal'],
    ])
    @include('owner.partials.campanias-modals-behavior-script')
@endpush

@endsection
