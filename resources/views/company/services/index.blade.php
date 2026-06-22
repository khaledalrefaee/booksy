@extends('company.dashboard')

@push('company-styles')
<style>
/* Ensure modal content uses theme background */
#svc-offcanvas .modal-content {
    background-color: var(--bs-body-bg, #1e2130);
    color: var(--bs-body-color, #e0e0e0);
}
#svc-offcanvas .modal-header { border-color: rgba(255,255,255,.08) !important; }
.bk-theme-light #svc-offcanvas .modal-content { background-color: #fff; color: #212529; }
.bk-theme-light #svc-offcanvas .modal-header  { border-color: rgba(0,0,0,.1) !important; }

/* ── Discount date/time picker ─────────────────────────────── */
.disc-dt-group {
    border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,.08);
    padding: 11px 12px 10px;
    transition: border-color .18s;
}
.bk-theme-light .disc-dt-group { border-color: rgba(0,0,0,.1); }
.disc-dt-group.has-error { border-color: #e53935 !important; }

.disc-dt-header {
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; margin-bottom: 9px;
    text-transform: uppercase; letter-spacing: .4px;
}

.disc-dt-field {
    display: flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 8px; padding: 5px 9px;
    margin-bottom: 6px;
}
.bk-theme-light .disc-dt-field {
    background: rgba(0,0,0,.03); border-color: rgba(0,0,0,.09);
}
.disc-dt-field:last-child { margin-bottom: 0; }
.disc-dt-field svg { flex-shrink: 0; opacity: .45; }

.disc-dt-field input[type="date"],
.disc-dt-field input[type="time"] {
    background: transparent !important;
    border: none !important; box-shadow: none !important;
    outline: none !important; padding: 0 !important;
    font-size: 12px; width: 100%; color: inherit;
    color-scheme: dark;
}
.bk-theme-light .disc-dt-field input[type="date"],
.bk-theme-light .disc-dt-field input[type="time"] { color-scheme: light; }

.disc-dt-err {
    display: none; align-items: center; gap: 4px;
    margin-top: 5px; font-size: 11px; color: #e53935;
}
.disc-dt-err.show { display: flex; }
/* ── Service card ─────────────────────────────────────── */
.svc-card {
    transition: transform .22s cubic-bezier(.22,1,.36,1), box-shadow .22s;
    height: 100%;
}
.svc-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(0,0,0,.25) !important;
}

/* Category dot */
.svc-cat-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    display: inline-block;
}

/* Price display */
.svc-price-final  { font-weight: 800; font-size: 16px; }
.svc-price-orig   { font-size: 12px; text-decoration: line-through; opacity: .45; }
.svc-sale-badge {
    display: inline-flex; align-items: center;
    font-size: 10px; font-weight: 700;
    padding: 2px 7px; border-radius: 20px;
    background: rgba(229,57,53,.15); color: #e53935;
}
.bk-theme-light .svc-sale-badge { background: rgba(229,57,53,.1); color: #c62828; }

/* Duration pill */
.svc-dur {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; border-radius: 20px;
    padding: 3px 9px;
    background: rgba(255,255,255,.07);
    color: rgba(255,255,255,.55);
}
.bk-theme-light .svc-dur {
    background: rgba(0,0,0,.06); color: rgba(0,0,0,.5);
}

/* Filter toolbar */
.svc-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
    margin-bottom: 24px;
}


/* Discount type labels */
.dtype-label {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    border: 1.5px solid rgba(255,255,255,.1);
    cursor: pointer; flex: 1; transition: all .18s;
    font-size: 12px;
}
.bk-theme-light .dtype-label { border-color: rgba(0,0,0,.12); }
.dtype-label.selected {
    border-color: #C9A227 !important;
    background: rgba(201,162,39,.1) !important;
}
.dtype-label .dtype-icon {
    width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.06);
}
.bk-theme-light .dtype-label .dtype-icon { background: rgba(0,0,0,.05); }

/* Discount preview */
#discount-preview {
    border-radius: 10px; padding: 8px 12px;
    background: rgba(229,57,53,.07);
    border: 1px dashed rgba(229,57,53,.3);
    font-size: 12px;
}

/* Hide filtered cards */
.svc-hide { display: none !important; }

/* Category section */
.svc-section-title {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 16px; padding-bottom: 10px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.bk-theme-light .svc-section-title { border-bottom-color: rgba(0,0,0,.08); }
.svc-section-title .cat-name { font-weight: 800; font-size: 14px; }
.svc-section-title .cat-count {
    font-size: 11px; font-weight: 700;
    padding: 1px 8px; border-radius: 20px;
    background: rgba(255,255,255,.08); color: rgba(255,255,255,.6);
}
.bk-theme-light .svc-section-title .cat-count {
    background: rgba(0,0,0,.07); color: rgba(0,0,0,.55);
}
/* Discount date range card */
.svc-discount-range {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(229,57,53,.07);
    border: 1px solid rgba(229,57,53,.18);
    border-radius: 10px;
    padding: 7px 10px;
}
.bk-theme-light .svc-discount-range {
    background: rgba(229,57,53,.05);
    border-color: rgba(229,57,53,.15);
}
.svc-discount-range-col {
    display: flex; flex-direction: column; flex: 1; min-width: 0;
}
.svc-drc-label {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: rgba(229,57,53,.7); margin-bottom: 1px;
}
.svc-drc-val {
    font-size: 11px; font-weight: 700; color: #e53935;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.svc-drc-time {
    font-size: 10px; color: rgba(229,57,53,.6); font-weight: 600; margin-top: 1px;
}
.svc-drc-arrow {
    flex-shrink: 0; color: rgba(229,57,53,.4); display: flex; align-items: center;
}
/* Card divider respects theme */
.svc-card-divider {
    border-top: 1px solid rgba(255,255,255,.07);
}
.bk-theme-light .svc-card-divider { border-top-color: rgba(0,0,0,.07); }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">{{ __('Services') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text rounded-pill"
                id="btn-add-service">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add service') }}
        </button>
    </div>

    @include('company.partials.flash')

    {{-- ── Stat cards ────────────────────────────────────────────── --}}
    @php
        $totalCount  = $services->count();
        $activeCount = $services->where('is_active', true)->count();
        $onSaleCount = $services->filter(fn($s) => $s->hasActiveDiscount())->count();
    @endphp
    <div class="row g-3 grid-margin bk-a1">
        <div class="col-sm-4 col-xl-3">
            <div class="bk-stat" data-accent="gold">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-gold">
                        <i data-feather="scissors" style="width:22px;height:22px;"></i>
                    </div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Total services') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num" id="stat-total">{{ $totalCount }}</div>
                <div class="bk-stat-bar"><div class="bk-stat-bar-fill" style="width:100%"></div></div>
            </div>
        </div>
        <div class="col-sm-4 col-xl-3">
            <div class="bk-stat" data-accent="green">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-green">
                        <i data-feather="check-circle" style="width:22px;height:22px;"></i>
                    </div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Active') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num" id="stat-active">{{ $activeCount }}</div>
                <div class="bk-stat-bar">
                    <div class="bk-stat-bar-fill" id="stat-active-bar" style="width:{{ $totalCount > 0 ? round($activeCount/$totalCount*100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @if($onSaleCount > 0)
        <div class="col-sm-4 col-xl-3">
            <div class="bk-stat" data-accent="red">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-red">
                        <i data-feather="tag" style="width:22px;height:22px;"></i>
                    </div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('On sale') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ $onSaleCount }}</div>
                <div class="bk-stat-bar">
                    <div class="bk-stat-bar-fill" style="width:{{ $totalCount > 0 ? round($onSaleCount/$totalCount*100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Filter toolbar ──────────────────────────────────────── --}}
    <div class="svc-toolbar bk-a2">
        <div class="position-relative" style="flex:1;max-width:280px;min-width:160px;">
            <i data-feather="search" class="position-absolute text-muted"
               style="width:14px;height:14px;top:50%;transform:translateY(-50%);
                      inset-inline-start:12px;pointer-events:none;"></i>
            <input type="search" id="svc-search" class="form-control rounded-pill"
                   style="padding-inline-start:36px;"
                   placeholder="{{ __('Search…') }}" autocomplete="off">
        </div>

        <select id="svc-cat-filter" class="form-select rounded-pill" style="width:auto;min-width:155px;">
            <option value="">{{ __('All categories') }}</option>
            @foreach($serviceCategories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
            @endforeach
        </select>

        <div class="bk-filter-tabs">
            <button class="bk-filter-tab active" data-status="">{{ __('All') }}</button>
            <button class="bk-filter-tab" data-status="1">{{ __('Active') }}</button>
            <button class="bk-filter-tab" data-status="0">{{ __('Inactive') }}</button>
            @if($onSaleCount > 0)
            <button class="bk-filter-tab" data-discount="1">🏷 {{ __('On sale') }}</button>
            @endif
        </div>

        <span id="svc-count" class="text-muted small ms-auto fw-semibold">
            {{ $totalCount }} {{ __('services') }}
        </span>
    </div>

    {{-- ── Services grouped by category ───────────────────────── --}}
    @php
        $grouped    = $services->groupBy('service_category_id');
        $catPalette = ['#C9A227','#3dbbd4','#2bcf7e','#f4a642','#ec4899','#8b5cf6','#14b8a6','#e53935'];
        $ci         = 0;
    @endphp

    @forelse($grouped as $catId => $group)
        @php
            $cat   = $group->first()->serviceCategory;
            $color = $catPalette[$ci % count($catPalette)];
            $ci++;
        @endphp

        <div class="svc-category-section grid-margin" data-cat-id="{{ $catId ?: '' }}">
            <div class="svc-section-title">
                <span class="svc-cat-dot"
                      style="background:{{ $color }};box-shadow:0 0 8px {{ $color }}66;"></span>
                <span class="cat-name">{{ $cat ? $cat->localizedName() : __('Uncategorized') }}</span>
                <span class="cat-count">{{ $group->count() }}</span>
            </div>

            <div class="row g-3">
                @foreach($group as $service)
                @php
                    $hasDiscount = $service->hasActiveDiscount();
                    $finalPrice  = $service->finalPrice();
                    $durH = intdiv($service->duration_minutes, 60);
                    $durM = $service->duration_minutes % 60;
                    $durStr = ($durH > 0 ? $durH . __('h') . ' ' : '') . ($durM > 0 ? $durM . __('m') : '');
                    $durStr = trim($durStr) ?: ($service->duration_minutes . ' ' . __('min'));
                @endphp
                <div class="col-md-6 col-xl-4 svc-card-wrap"
                     data-name="{{ strtolower(($service->name_en ?? '') . ' ' . ($service->name_ar ?? '')) }}"
                     data-cat="{{ $service->service_category_id ?? '' }}"
                     data-active="{{ $service->is_active ? '1' : '0' }}"
                     data-discount="{{ $hasDiscount ? '1' : '0' }}">

                    <div class="card border-0 svc-card">
                        <div class="card-body p-3 d-flex flex-column">

                            {{-- Name + toggle --}}
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-truncate" style="font-size:14px;">
                                        {{ $service->name_en ?: $service->name_ar ?: '—' }}
                                    </div>
                                    @if($service->name_ar && $service->name_en)
                                    <div class="text-muted text-truncate mt-1" dir="rtl" style="font-size:12px;">
                                        {{ $service->name_ar }}
                                    </div>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    @if($hasDiscount)
                                    <span class="svc-sale-badge">
                                        <i data-feather="tag" style="width:9px;height:9px;margin-inline-end:2px;"></i>
                                        {{ __('Sale') }}
                                    </span>
                                    @endif
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input svc-active-toggle" type="checkbox"
                                               {{ $service->is_active ? 'checked' : '' }}
                                               data-id="{{ $service->id }}"
                                               data-url="{{ route('company.services.toggle-active', $service) }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="d-flex align-items-baseline gap-2 flex-wrap mb-3">
                                @if($hasDiscount)
                                    <span class="svc-price-final" style="color:#e53935;">
                                        {{ number_format($finalPrice, 0) }}
                                        <span style="font-size:11px;font-weight:600;">{{ $service->currency }}</span>
                                    </span>
                                    <span class="svc-price-orig">
                                        {{ number_format($service->price, 0) }} {{ $service->currency }}
                                    </span>
                                    <span class="svc-sale-badge">
                                        @if($service->discount_type === 'percent')
                                            -{{ rtrim(rtrim(number_format($service->discount_value,1),'0'),'.') }}%
                                        @else
                                            -{{ number_format($service->discount_value,0) }}
                                        @endif
                                    </span>
                                @else
                                    <span class="svc-price-final" style="color:#C9A227;">
                                        {{ number_format($service->price, 0) }}
                                        <span style="font-size:11px;font-weight:600;color:inherit;">{{ $service->currency }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Duration --}}
                            <div class="d-flex align-items-center gap-2 flex-wrap mt-auto">
                                <span class="svc-dur">
                                    <i data-feather="clock" style="width:11px;height:11px;"></i>
                                    {{ $durStr }}
                                </span>
                            </div>

                            {{-- Discount date range card --}}
                            @if($hasDiscount && ($service->discount_starts_at || $service->discount_ends_at))
                            <div class="svc-discount-range mt-2">
                                <div class="svc-discount-range-col">
                                    <span class="svc-drc-label">{{ __('Starts') }}</span>
                                    <span class="svc-drc-val">
                                        {{ $service->discount_starts_at ? $service->discount_starts_at->format('d M Y') : '—' }}
                                    </span>
                                    @if($service->discount_starts_at)
                                    <span class="svc-drc-time">{{ $service->discount_starts_at->format('H:i') }}</span>
                                    @endif
                                </div>
                                <div class="svc-drc-arrow">
                                    <i data-feather="arrow-left" style="width:12px;height:12px;"></i>
                                </div>
                                <div class="svc-discount-range-col">
                                    <span class="svc-drc-label">{{ __('Ends') }}</span>
                                    <span class="svc-drc-val">
                                        {{ $service->discount_ends_at ? $service->discount_ends_at->format('d M Y') : '—' }}
                                    </span>
                                    @if($service->discount_ends_at)
                                    <span class="svc-drc-time">{{ $service->discount_ends_at->format('H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            {{-- Actions --}}
                            <div class="d-flex gap-2 mt-3 pt-3 svc-card-divider">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary rounded-pill flex-fill js-edit-btn"
                                        data-id="{{ $service->id }}"
                                        data-name-en="{{ $service->name_en }}"
                                        data-name-ar="{{ $service->name_ar }}"
                                        data-description="{{ $service->description }}"
                                        data-price="{{ $service->price }}"
                                        data-currency="{{ $service->currency }}"
                                        data-duration="{{ $service->duration_minutes }}"
                                        data-category="{{ $service->service_category_id }}"
                                        data-active="{{ $service->is_active ? '1' : '0' }}"
                                        data-discount-type="{{ $service->discount_type }}"
                                        data-discount-value="{{ $service->discount_value }}"
                                        data-discount-starts="{{ $service->discount_starts_at ? $service->discount_starts_at->format('Y-m-d\TH:i') : '' }}"
                                        data-discount-ends="{{ $service->discount_ends_at ? $service->discount_ends_at->format('Y-m-d\TH:i') : '' }}">
                                    <i data-feather="edit-2" style="width:13px;height:13px;margin-inline-end:4px;"></i>
                                    {{ __('Edit') }}
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger rounded-pill js-delete-btn"
                                        data-name="{{ $service->name_en ?: $service->name_ar }}"
                                        data-url="{{ route('company.services.destroy', $service) }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        title="{{ __('Delete') }}">
                                    <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card border-0 grid-margin">
            <div class="card-body">
                <div class="bk-empty">
                    <div class="bk-empty-ic">
                        <i data-feather="scissors" style="width:26px;height:26px;"></i>
                    </div>
                    <p>{{ __('No services for this branch.') }}</p>
                    <button type="button" class="btn btn-primary rounded-pill px-4 mt-2 js-open-add-modal">
                        <i data-feather="plus" style="width:14px;height:14px;" class="me-1"></i>
                        {{ __('Add your first service') }}
                    </button>
                </div>
            </div>
        </div>
    @endforelse

    <div id="svc-no-results" class="d-none card border-0">
        <div class="card-body">
            <div class="bk-empty">
                <div class="bk-empty-ic">
                    <i data-feather="search" style="width:24px;height:24px;"></i>
                </div>
                <p>{{ __('No services match your search.') }}</p>
            </div>
        </div>
    </div>

</div>

@endsection

@push('company-after-template')
{{-- ── Toast notification ──────────────────────────────────────── --}}
<div id="bk-toast" style="position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:9999;min-width:220px;pointer-events:none;opacity:0;transition:opacity .25s;">
    <div id="bk-toast-inner" class="d-flex align-items-center gap-2 px-4 py-3 rounded-pill shadow-lg">
        <i id="bk-toast-icon" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span id="bk-toast-msg" class="fw-semibold" style="font-size:14px;white-space:nowrap;"></span>
    </div>
</div>

{{-- ── Modal: Add / Edit ──────────────────────────────────────── --}}
<div class="modal fade" tabindex="-1" id="svc-offcanvas">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="svc-offcanvas-title">{{ __('Add service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:78vh;overflow-y:auto;">

        <div id="svc-errors" class="alert alert-danger rounded-3 d-none mb-3" style="font-size:13px;"></div>

        <form id="svc-form" novalidate>
            @csrf
            <input type="hidden" name="_method" id="svc-method" value="POST">
            <input type="hidden" id="svc-edit-id" value="">

            {{-- Category --}}
            <div class="mb-3">
                <label class="form-label fw-semibold small">{{ __('Category') }}</label>
                <select name="service_category_id" id="svc-f-category" class="form-select rounded-3">
                    <option value="">{{ __('No category') }}</option>
                    @foreach($serviceCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
                    @endforeach
                </select>
            </div>

            <hr class="my-3">

            {{-- Names --}}
            <div class="mb-3">
                <label class="form-label fw-semibold small">{{ __('Name (EN)') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_en" id="svc-f-name-en"
                       class="form-control rounded-3" maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">{{ __('Name (AR)') }}</label>
                <input type="text" name="name_ar" id="svc-f-name-ar"
                       class="form-control rounded-3" dir="rtl" maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">{{ __('Description') }}</label>
                <textarea name="description" id="svc-f-desc" class="form-control rounded-3" rows="2"></textarea>
            </div>

            <hr class="my-3">

            {{-- Price + Currency + Duration --}}
            <div class="row g-3 mb-3">
                <div class="col-8">
                    <label class="form-label fw-semibold small">{{ __('Price') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <button type="button"
                                class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1 px-3"
                                data-bs-toggle="dropdown" style="min-width:90px;">
                            <span id="currency-symbol" class="fw-bold">{{ config('booksy.currencies')[config('booksy.default_currency')]['symbol'] }}</span>
                            <span id="currency-code" class="text-muted small">{{ config('booksy.default_currency') }}</span>
                        </button>
                        <ul class="dropdown-menu shadow" style="max-height:260px;overflow-y:auto;min-width:220px;">
                            @foreach(config('booksy.currencies') as $code => $info)
                            <li>
                                <a class="dropdown-item currency-option d-flex justify-content-between align-items-center py-2
                                          {{ $code === config('booksy.default_currency') ? 'active' : '' }}"
                                   href="#" data-code="{{ $code }}" data-symbol="{{ $info['symbol'] }}">
                                    <span>
                                        <span class="fw-semibold me-1">{{ $info['symbol'] }}</span>
                                        {{ app()->getLocale()==='ar' ? $info['name_ar'] : $info['name_en'] }}
                                    </span>
                                    <small class="text-muted">{{ $code }}</small>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        <input type="hidden" name="currency" id="currency-input" value="{{ config('booksy.default_currency') }}">
                        <input type="number" name="price" id="svc-f-price"
                               class="form-control" min="0" step="0.01" placeholder="0">
                    </div>
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold small">{{ __('Duration') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="duration_minutes" id="svc-f-duration"
                               class="form-control" min="1" max="1440" value="30">
                        <span class="input-group-text">{{ __('min') }}</span>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- Discount --}}
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bk-qa-ic" style="width:30px;height:30px;border-radius:8px;">
                            <i data-feather="tag" style="width:14px;height:14px;"></i>
                        </div>
                        <span class="fw-bold" style="font-size:13px;">{{ __('Discount / Promotion') }}</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="discount-toggle">
                    </div>
                </div>

                <div id="discount-fields" style="display:none;">
                    {{-- Type --}}
                    <div class="d-flex gap-2 mb-3">
                        <label class="dtype-label selected" id="dtype-lbl-percent" style="user-select:none;">
                            <input type="radio" name="discount_type" value="percent"
                                   class="js-dtype" style="display:none;" checked>
                            <div class="dtype-icon">
                                <i data-feather="percent" style="width:14px;height:14px;color:#C9A227;"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ __('Percentage') }}</div>
                                <div class="text-muted" style="font-size:10px;">{{ __('e.g. 20% off') }}</div>
                            </div>
                        </label>
                        <label class="dtype-label" id="dtype-lbl-fixed" style="user-select:none;">
                            <input type="radio" name="discount_type" value="fixed"
                                   class="js-dtype" style="display:none;">
                            <div class="dtype-icon">
                                <i data-feather="minus-circle" style="width:14px;height:14px;color:#C9A227;"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ __('Fixed amount') }}</div>
                                <div class="text-muted" style="font-size:10px;">{{ __('e.g. reduce by 25,000') }}</div>
                            </div>
                        </label>
                    </div>

                    {{-- Value --}}
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <div class="input-group">
                                <input type="number" name="discount_value" id="discount_value"
                                       class="form-control form-control-sm" min="0" step="0.01" placeholder="0">
                                <span class="input-group-text" id="discount-unit-lbl"
                                      style="min-width:40px;justify-content:center;">%</span>
                            </div>
                        </div>
                        <div class="col-7 d-flex align-items-center">
                            <div id="discount-preview" class="d-none w-100">
                                <span class="text-muted text-decoration-line-through me-1" id="dp-original"></span>
                                →
                                <span class="fw-bold ms-1" style="color:#e53935;" id="dp-final"></span>
                                <span class="svc-sale-badge ms-1" id="dp-badge"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="row g-2 mb-2">
                        {{-- Start --}}
                        <div class="col-6">
                            <div class="disc-dt-group" id="disc-start-group">
                                <div class="disc-dt-header" style="color:#2bcf7e;">
                                    <i data-feather="play-circle" style="width:12px;height:12px;"></i>
                                    {{ __('Starts at') }}
                                </div>
                                <div class="disc-dt-field">
                                    <i data-feather="calendar" style="width:12px;height:12px;"></i>
                                    <input type="date" id="disc-start-date">
                                </div>
                                <div class="disc-dt-field">
                                    <i data-feather="clock" style="width:12px;height:12px;"></i>
                                    <input type="time" id="disc-start-time" value="00:00">
                                </div>
                            </div>
                            <div class="disc-dt-err" id="disc-start-err">
                                <i data-feather="alert-circle" style="width:11px;height:11px;flex-shrink:0;"></i>
                                <span id="disc-start-err-text"></span>
                            </div>
                            <input type="hidden" name="discount_starts_at" id="discount_starts_at">
                        </div>

                        {{-- End --}}
                        <div class="col-6">
                            <div class="disc-dt-group" id="disc-end-group">
                                <div class="disc-dt-header" style="color:#e53935;">
                                    <i data-feather="stop-circle" style="width:12px;height:12px;"></i>
                                    {{ __('Ends at') }}
                                </div>
                                <div class="disc-dt-field">
                                    <i data-feather="calendar" style="width:12px;height:12px;"></i>
                                    <input type="date" id="disc-end-date">
                                </div>
                                <div class="disc-dt-field">
                                    <i data-feather="clock" style="width:12px;height:12px;"></i>
                                    <input type="time" id="disc-end-time" value="23:59">
                                </div>
                            </div>
                            <div class="disc-dt-err" id="disc-end-err">
                                <i data-feather="alert-circle" style="width:11px;height:11px;flex-shrink:0;"></i>
                                <span id="disc-end-err-text"></span>
                            </div>
                            <input type="hidden" name="discount_ends_at" id="discount_ends_at">
                        </div>
                    </div>

                    {{-- Range display --}}
                    <div id="disc-range" class="d-none rounded-3 px-3 py-2" style="background:rgba(201,162,39,.09);border:1px solid rgba(201,162,39,.25);font-size:12px;color:#C9A227;">
                        <i data-feather="calendar" style="width:12px;height:12px;vertical-align:middle;margin-inline-end:6px;"></i>
                        <span id="disc-range-text" class="fw-semibold"></span>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- Active --}}
            <div class="form-check form-switch mb-4">
                <input type="checkbox" class="form-check-input" id="svc-f-active" name="is_active" value="1" checked>
                <label class="form-check-label fw-semibold small" for="svc-f-active">{{ __('Active') }}</label>
            </div>

            <div class="d-flex gap-2 pt-3 border-top">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary btn-icon-text rounded-pill flex-grow-1" id="svc-submit-btn">
                    <i class="btn-icon-prepend" data-feather="save"></i>
                    <span id="svc-submit-label">{{ __('Save service') }}</span>
                </button>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

{{-- ── Delete modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-body p-4 text-center">
                <div class="bk-empty-ic mx-auto mb-3"
                     style="background:rgba(229,57,53,.12);color:#e53935;animation:none;">
                    <i data-feather="trash-2" style="width:24px;height:24px;"></i>
                </div>
                <h6 class="fw-bold mb-1">{{ __('Delete service?') }}</h6>
                <p class="text-muted small mb-4" id="delete-modal-name"></p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-danger rounded-pill flex-fill" id="confirm-delete-btn">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    if (window.feather) feather.replace();

    var CSRF = @json(csrf_token());

    var STORE_URL  = @json(route('company.branches.services.store', $branch));
    var UPDATE_BASE = @json(url('company/services'));
    var DEFAULT_CUR = @json(config('booksy.default_currency'));
    var formMode = 'add';

    // ── Refs ──────────────────────────────────────────────────────
    var offcanvasEl  = document.getElementById('svc-offcanvas');
    var form         = document.getElementById('svc-form');
    var titleEl      = document.getElementById('svc-offcanvas-title');
    var methodInput  = document.getElementById('svc-method');
    var editIdInput  = document.getElementById('svc-edit-id');
    var submitLabel  = document.getElementById('svc-submit-label');
    var submitBtn    = document.getElementById('svc-submit-btn');
    // Discount date split inputs
    var discStartDate = document.getElementById('disc-start-date');
    var discStartTime = document.getElementById('disc-start-time');
    var discEndDate   = document.getElementById('disc-end-date');
    var discEndTime   = document.getElementById('disc-end-time');

    // ── Helper: open the add/edit modal ──────────────────────────
    function openSvcModal() {
        if (offcanvasEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(offcanvasEl).show();
        }
    }

    // ── Modal events ──────────────────────────────────────────────
    if (offcanvasEl) {
        offcanvasEl.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });
    }

    // Add buttons (header + empty-state)
    document.querySelectorAll('#btn-add-service, .js-open-add-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            formMode = 'add';
            resetForm();
            if (titleEl)     titleEl.textContent = @json(__('Add service'));
            if (submitLabel) submitLabel.textContent = @json(__('Save service'));
            openSvcModal();
        });
    });

    // Edit buttons: populate data then open modal
    document.querySelectorAll('.js-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            formMode = 'edit';
            if (titleEl)     titleEl.textContent = @json(__('Edit service'));
            if (submitLabel) submitLabel.textContent = @json(__('Save changes'));

            var d = this.dataset;
            if (editIdInput) editIdInput.value = d.id || '';
            if (methodInput) methodInput.value = 'PUT';

            setVal('svc-f-name-en',  d.nameEn);
            setVal('svc-f-name-ar',  d.nameAr);
            setVal('svc-f-desc',     d.description);
            setVal('svc-f-price',    d.price);
            setVal('svc-f-duration', d.duration);
            setVal('svc-f-category', d.category);
            setCurrency(d.currency);

            var actChk = document.getElementById('svc-f-active');
            if (actChk) actChk.checked = d.active === '1';

            populateDiscount(d.discountType, d.discountValue, d.discountStarts, d.discountEnds);
            clearErrors();
            openSvcModal();
            setTimeout(function () { if (window.feather) feather.replace(); }, 80);
        });
    });

    function resetForm() {
        if (!form) return;
        form.reset();
        if (editIdInput) editIdInput.value = '';
        if (methodInput) methodInput.value = 'POST';
        formMode = 'add';
        setCurrency(DEFAULT_CUR);
        var tog = document.getElementById('discount-toggle');
        var flds = document.getElementById('discount-fields');
        var dv  = document.getElementById('discount_value');
        var dp  = document.getElementById('discount-preview');
        var pr  = document.querySelector('.js-dtype[value="percent"]');
        if (tog)  tog.checked = false;
        if (flds) flds.style.display = 'none';
        if (dv)   dv.value = '';
        if (dp)   dp.classList.add('d-none');
        if (pr)   pr.checked = true;
        highlightDtype('percent');
        // Reset date/time split fields
        if (discStartDate) discStartDate.value = '';
        if (discStartTime) discStartTime.value = '00:00';
        if (discEndDate)   discEndDate.value   = '';
        if (discEndTime)   discEndTime.value   = '23:59';
        var sh = document.getElementById('discount_starts_at');
        var eh = document.getElementById('discount_ends_at');
        if (sh) sh.value = ''; if (eh) eh.value = '';
        var dr = document.getElementById('disc-range');
        if (dr) dr.classList.add('d-none');
        ['disc-start-group','disc-end-group'].forEach(function(id){
            var g = document.getElementById(id); if(g) g.classList.remove('has-error');
        });
        ['disc-start-err','disc-end-err'].forEach(function(id){
            var e = document.getElementById(id); if(e) e.classList.remove('show');
        });
        clearErrors();
    }

    // ── Discount date validation ──────────────────────────────────
    function syncDiscountHidden() {
        var sd = discStartDate ? discStartDate.value : '';
        var st = discStartTime ? (discStartTime.value || '00:00') : '00:00';
        var ed = discEndDate   ? discEndDate.value   : '';
        var et = discEndTime   ? (discEndTime.value   || '23:59') : '23:59';
        var sh = document.getElementById('discount_starts_at');
        var eh = document.getElementById('discount_ends_at');
        if (sh) sh.value = sd ? (sd + 'T' + st) : '';
        if (eh) eh.value = ed ? (ed + 'T' + et) : '';
        return { sd: sd, st: st, ed: ed, et: et };
    }

    function validateDiscountDates() {
        var v       = syncDiscountHidden();
        var sg      = document.getElementById('disc-start-group');
        var eg      = document.getElementById('disc-end-group');
        var seEl    = document.getElementById('disc-start-err');
        var eeEl    = document.getElementById('disc-end-err');
        var seTxt   = document.getElementById('disc-start-err-text');
        var eeTxt   = document.getElementById('disc-end-err-text');
        var rangeEl = document.getElementById('disc-range');
        var rangeTxt= document.getElementById('disc-range-text');

        // Reset
        [sg,eg].forEach(function(g){ if(g) g.classList.remove('has-error'); });
        [seEl,eeEl].forEach(function(e){ if(e) e.classList.remove('show'); });
        if (rangeEl) rangeEl.classList.add('d-none');

        var start  = v.sd ? new Date(v.sd + 'T' + v.st) : null;
        var end    = v.ed ? new Date(v.ed + 'T' + v.et) : null;
        var now    = new Date();
        var valid  = true;

        if (start && start < now) {
            if (sg)    sg.classList.add('has-error');
            if (seTxt) seTxt.textContent = 'لا يمكن أن يكون وقت البداية في الماضي';
            if (seEl)  seEl.classList.add('show');
            valid = false;
        }
        if (end && start && end <= start) {
            if (eg)    eg.classList.add('has-error');
            if (eeTxt) eeTxt.textContent = 'وقت الانتهاء يجب أن يكون بعد وقت البداية';
            if (eeEl)  eeEl.classList.add('show');
            valid = false;
        }

        // Range display
        if (start && end && valid && rangeTxt && rangeEl) {
            var lo  = 'ar';
            var fd2 = { day: 'numeric', month: 'short' };
            var ft  = { hour: '2-digit', minute: '2-digit' };
            rangeTxt.textContent =
                'من ' + start.toLocaleDateString(lo, fd2) + ' ' + start.toLocaleTimeString(lo, ft) +
                '  ←  ' + end.toLocaleDateString(lo, fd2) + ' ' + end.toLocaleTimeString(lo, ft);
            rangeEl.classList.remove('d-none');
            if (window.feather) feather.replace();
        }
        return valid;
    }

    [discStartDate, discStartTime, discEndDate, discEndTime].forEach(function(el) {
        if (el) el.addEventListener('change', validateDiscountDates);
    });

    // ── AJAX form submit ──────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors();

            var discTog = document.getElementById('discount-toggle');
            var discOn  = discTog && discTog.checked;

            // Client-side date validation
            if (discOn && !validateDiscountDates()) {
                return;
            }

            if (submitBtn) submitBtn.disabled = true;

            var url = formMode === 'add'
                ? STORE_URL
                : UPDATE_BASE + '/' + (editIdInput ? editIdInput.value : '');

            var fd = new FormData(form);
            if (!discOn) {
                fd.set('discount_value', '');
                fd.set('discount_type', '');
                fd.delete('discount_starts_at');
                fd.delete('discount_ends_at');
            }

            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            })
            .then(function (r) {
                return r.json().then(function (d) { return { ok: r.ok, status: r.status, data: d }; });
            })
            .then(function (res) {
                if (submitBtn) submitBtn.disabled = false;
                if (res.ok && res.data.success) {
                    if (offcanvasEl && window.bootstrap) {
                        var oc = bootstrap.Modal.getInstance(offcanvasEl);
                        if (oc) oc.hide();
                    }
                    window.location.reload();
                } else if (res.data && res.data.errors) {
                    showErrors(res.data.errors);
                } else {
                    showErrors({ _: [res.data && res.data.message ? res.data.message : 'حدث خطأ، يرجى المحاولة مجدداً'] });
                }
            })
            .catch(function () {
                if (submitBtn) submitBtn.disabled = false;
                showErrors({ _: ['تعذّر الاتصال بالخادم، يرجى المحاولة مجدداً'] });
            });
        });
    }

    // ── Toggle active (AJAX) ──────────────────────────────────────
    document.querySelectorAll('.svc-active-toggle').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var self = this;
            fetch(this.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=PATCH',
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                self.checked = !!d.is_active;
                var wrap = self.closest('.svc-card-wrap');
                if (wrap) wrap.dataset.active = d.is_active ? '1' : '0';
                updateStats(0, d.is_active ? 1 : -1);
                showToast(d.is_active ? @json(__('Service activated.')) : @json(__('Service deactivated.')), d.is_active ? 'success' : 'warning');
            })
            .catch(function () { self.checked = !self.checked; });
        });
    });

    // ── Delete modal ──────────────────────────────────────────────
    var deleteUrl  = '';
    var deleteWrap = null;
    var deleteModalEl = document.getElementById('deleteModal');

    if (deleteModalEl) {
        // Bootstrap passes relatedTarget = the button that triggered the modal
        deleteModalEl.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            deleteUrl  = btn.dataset.url  || '';
            deleteWrap = btn.closest('.svc-card-wrap');
            var nm = document.getElementById('delete-modal-name');
            if (nm) nm.textContent = '"' + (btn.dataset.name || '') + '"';
            setTimeout(function () { if (window.feather) feather.replace(); }, 50);
        });
    }

    var confirmBtn = document.getElementById('confirm-delete-btn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!deleteUrl) return;
            var self = this;
            var wasActive = deleteWrap && deleteWrap.dataset.active === '1';
            self.disabled = true;
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            })
            .then(function (r) {
                if (r.ok) {
                    if (deleteModalEl && window.bootstrap) {
                        var m = bootstrap.Modal.getInstance(deleteModalEl);
                        if (m) m.hide();
                    }
                    if (deleteWrap) {
                        deleteWrap.style.transition = 'opacity .3s, transform .3s';
                        deleteWrap.style.opacity    = '0';
                        deleteWrap.style.transform  = 'scale(.92)';
                        setTimeout(function () { deleteWrap.remove(); applyFilter(); }, 300);
                    }
                    updateStats(-1, wasActive ? -1 : 0);
                    showToast(@json(__('Service deleted successfully.')), 'danger');
                }
            })
            .finally(function () { self.disabled = false; });
        });
    }

    // ── Filters ───────────────────────────────────────────────────
    var countEl = document.getElementById('svc-count');
    var noRes   = document.getElementById('svc-no-results');
    var state   = { q: '', cat: '', status: '', discount: '' };

    function debounce(fn, ms) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function applyFilter() {
        var q   = state.q.trim().toLowerCase();
        var vis = 0;
        document.querySelectorAll('.svc-card-wrap').forEach(function (c) {
            var ok = (!q              || (c.dataset.name || '').includes(q))
                  && (!state.cat      || c.dataset.cat      === state.cat)
                  && (!state.status   || c.dataset.active   === state.status)
                  && (!state.discount || c.dataset.discount === state.discount);
            c.classList.toggle('svc-hide', !ok);
            if (ok) vis++;
        });
        document.querySelectorAll('.svc-category-section').forEach(function (sec) {
            sec.style.display = sec.querySelectorAll('.svc-card-wrap:not(.svc-hide)').length ? '' : 'none';
        });
        if (countEl) countEl.textContent = vis + ' ' + @json(__('services'));
        if (noRes)   noRes.classList.toggle('d-none', vis > 0);
    }

    var searchEl = document.getElementById('svc-search');
    if (searchEl) searchEl.addEventListener('input', debounce(function () { state.q = this.value; applyFilter(); }, 200));

    var catFilter = document.getElementById('svc-cat-filter');
    if (catFilter) catFilter.addEventListener('change', function () { state.cat = this.value; applyFilter(); });

    document.querySelectorAll('.bk-filter-tab[data-status]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.bk-filter-tab[data-status]').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            state.status = this.dataset.status;
            applyFilter();
        });
    });

    document.querySelectorAll('.bk-filter-tab[data-discount]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var nowActive = !this.classList.contains('active');
            document.querySelectorAll('.bk-filter-tab[data-discount]').forEach(function (b) { b.classList.remove('active'); });
            if (nowActive) this.classList.add('active');
            state.discount = nowActive ? this.dataset.discount : '';
            applyFilter();
        });
    });

    // ── Currency picker ───────────────────────────────────────────
    document.querySelectorAll('.currency-option').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            setCurrency(this.dataset.code, this.dataset.symbol);
            updateDiscountPreview();
        });
    });

    function setCurrency(code, symbol) {
        if (!code) return;
        var opt = document.querySelector('.currency-option[data-code="' + code + '"]');
        var sym = symbol || (opt ? opt.dataset.symbol : code);
        var ci = document.getElementById('currency-input');
        var cs = document.getElementById('currency-symbol');
        var cc = document.getElementById('currency-code');
        if (ci) ci.value = code;
        if (cs) cs.textContent = sym;
        if (cc) cc.textContent = code;
        document.querySelectorAll('.currency-option').forEach(function (o) { o.classList.remove('active'); });
        if (opt) opt.classList.add('active');
        updateDiscountUnit();
    }

    // ── Discount ──────────────────────────────────────────────────
    var discTog = document.getElementById('discount-toggle');
    if (discTog) {
        discTog.addEventListener('change', function () {
            var flds = document.getElementById('discount-fields');
            if (flds) flds.style.display = this.checked ? '' : 'none';
            if (!this.checked) {
                var dp = document.getElementById('discount-preview');
                if (dp) dp.classList.add('d-none');
            }
        });
    }

    document.querySelectorAll('.dtype-label').forEach(function (lbl) {
        lbl.addEventListener('click', function () {
            var radio = this.querySelector('.js-dtype');
            if (!radio) return;
            radio.checked = true;
            updateDiscountUnit();
            updateDiscountPreview();
            highlightDtype(radio.value);
        });
    });

    var dvEl = document.getElementById('discount_value');
    var prEl = document.getElementById('svc-f-price');
    if (dvEl) dvEl.addEventListener('input', updateDiscountPreview);
    if (prEl) prEl.addEventListener('input', updateDiscountPreview);

    function updateDiscountUnit() {
        var typeEl = document.querySelector('.js-dtype:checked');
        var unitEl = document.getElementById('discount-unit-lbl');
        if (!unitEl) return;
        var cur = (document.getElementById('currency-input') || {}).value || '';
        unitEl.textContent = (typeEl && typeEl.value === 'fixed') ? cur : '%';
    }

    function updateDiscountPreview() {
        var preview = document.getElementById('discount-preview');
        var tog     = document.getElementById('discount-toggle');
        if (!preview || !tog || !tog.checked) {
            if (preview) preview.classList.add('d-none');
            return;
        }
        var typeEl = document.querySelector('.js-dtype:checked');
        var type   = typeEl ? typeEl.value : 'percent';
        var dv     = document.getElementById('discount_value');
        var pv     = document.getElementById('svc-f-price');
        var val    = dv   ? parseFloat(dv.value)  || 0 : 0;
        var price  = pv   ? parseFloat(pv.value)  || 0 : 0;
        if (!val || !price) { preview.classList.add('d-none'); return; }
        var cur = (document.getElementById('currency-input') || {}).value || '';
        var fp  = Math.max(0, type === 'percent' ? price * (1 - val / 100) : price - val);
        var o = document.getElementById('dp-original');
        var f = document.getElementById('dp-final');
        var b = document.getElementById('dp-badge');
        if (o) o.textContent = price.toLocaleString() + ' ' + cur;
        if (f) f.textContent = fp.toLocaleString(undefined, {maximumFractionDigits: 2}) + ' ' + cur;
        if (b) b.textContent = type === 'percent' ? '-' + val + '%' : '-' + val.toLocaleString() + ' ' + cur;
        preview.classList.remove('d-none');
    }

    function highlightDtype(val) {
        var lp = document.getElementById('dtype-lbl-percent');
        var lf = document.getElementById('dtype-lbl-fixed');
        if (lp) lp.classList.toggle('selected', val === 'percent');
        if (lf) lf.classList.toggle('selected',   val === 'fixed');
    }

    function splitDT(dt) {
        if (!dt) return { d: '', t: '' };
        var p = dt.split('T');
        return { d: p[0] || '', t: p[1] ? p[1].slice(0, 5) : '' };
    }

    function populateDiscount(type, value, starts, ends) {
        var has  = !!(type && value);
        var tog  = document.getElementById('discount-toggle');
        var flds = document.getElementById('discount-fields');
        var dv   = document.getElementById('discount_value');
        if (tog)  tog.checked = has;
        if (flds) flds.style.display = has ? '' : 'none';
        if (has) {
            var radio = document.querySelector('.js-dtype[value="' + type + '"]');
            if (radio) { radio.checked = true; highlightDtype(type); updateDiscountUnit(); }
            if (dv) dv.value = value || '';
        } else {
            var pr = document.querySelector('.js-dtype[value="percent"]');
            if (pr) pr.checked = true;
            highlightDtype('percent');
        }
        // Populate split date/time fields
        var sp = splitDT(starts), ep = splitDT(ends);
        if (discStartDate) discStartDate.value = sp.d;
        if (discStartTime) discStartTime.value = sp.t || '00:00';
        if (discEndDate)   discEndDate.value   = ep.d;
        if (discEndTime)   discEndTime.value   = ep.t || '23:59';
        syncDiscountHidden();
        if (has) validateDiscountDates();
    }

    // ── Helpers ───────────────────────────────────────────────────
    function showErrors(errors) {
        var box  = document.getElementById('svc-errors');
        if (!box) return;
        var msgs = [];
        Object.values(errors).forEach(function (arr) {
            (Array.isArray(arr) ? arr : [arr]).forEach(function (m) { msgs.push(m); });
        });
        box.innerHTML = msgs.map(function (m) { return '<div>• ' + m + '</div>'; }).join('');
        box.classList.remove('d-none');
    }

    function clearErrors() {
        var box = document.getElementById('svc-errors');
        if (box) { box.innerHTML = ''; box.classList.add('d-none'); }
    }

    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = (val !== undefined && val !== null) ? val : '';
    }

    // ── Toast ─────────────────────────────────────────────────────
    function showToast(msg, type) {
        var toast = document.getElementById('bk-toast');
        var inner = document.getElementById('bk-toast-inner');
        var msgEl = document.getElementById('bk-toast-msg');
        var iconEl = document.getElementById('bk-toast-icon');
        if (!toast || !inner || !msgEl) return;
        var palette = {
            success: { bg: '#2bcf7e', icon: 'check-circle' },
            danger:  { bg: '#e53935', icon: 'trash-2'      },
            warning: { bg: '#f4a642', icon: 'alert-circle'  },
        };
        var c = palette[type] || palette.success;
        inner.style.background = c.bg;
        inner.style.color = '#fff';
        msgEl.textContent = msg;
        if (iconEl) {
            iconEl.setAttribute('data-feather', c.icon);
            iconEl.style.color = '#fff';
            if (window.feather) feather.replace();
        }
        toast.style.display = 'block';
        clearTimeout(toast._t1); clearTimeout(toast._t2);
        toast._t1 = setTimeout(function () { toast.style.opacity = '1'; }, 10);
        toast._t2 = setTimeout(function () {
            toast.style.opacity = '0';
            setTimeout(function () { toast.style.display = 'none'; }, 260);
        }, 3200);
    }

    // ── Live stat counters ────────────────────────────────────────
    function updateStats(deltaTotal, deltaActive) {
        var totalEl  = document.getElementById('stat-total');
        var activeEl = document.getElementById('stat-active');
        var barEl    = document.getElementById('stat-active-bar');
        var t = Math.max(0, parseInt(totalEl  ? totalEl.textContent  : 0) + deltaTotal);
        var a = Math.max(0, Math.min(parseInt(activeEl ? activeEl.textContent : 0) + deltaActive, t));
        if (totalEl)  totalEl.textContent  = t;
        if (activeEl) activeEl.textContent = a;
        if (barEl)    barEl.style.width    = (t > 0 ? Math.round(a / t * 100) : 0) + '%';
    }

}); // end DOMContentLoaded
</script>
@endpush
