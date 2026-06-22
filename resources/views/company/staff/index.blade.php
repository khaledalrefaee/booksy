@extends('company.dashboard')

@section('content')
@php $isAr = app()->getLocale() === 'ar'; @endphp

<div class="page-content">

{{-- ══ HERO ══ --}}
<div class="bk-hero bk-a1">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="bk-hero-title">{{ __('Staff') }} <span>&amp; {{ __('Services') }}</span></h2>
            <p class="bk-hero-sub">
                <i data-feather="layers" style="width:13px;height:13px;display:inline;margin-inline-end:5px;"></i>
                {{ __('All branches') }}
            </p>
        </div>
    </div>
</div>

@include('company.partials.flash')

{{-- ══ FILTER BAR ══ --}}
<div class="card shadow-sm mb-4 bk-a2">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('company.staff.index') }}" class="d-flex flex-wrap align-items-center gap-3">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            {{-- Branch filter --}}
            <div class="d-flex align-items-center gap-2">
                <i data-feather="map-pin" style="width:14px;height:14px;opacity:.5;"></i>
                <select name="branch_id" class="form-select form-select-sm rounded-pill" style="width:auto;min-width:160px;"
                        onchange="this.form.submit()">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches as $br)
                        <option value="{{ $br->id }}" {{ $filterBranchId === $br->id ? 'selected' : '' }}>
                            {{ $br->localizedName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Live search --}}
            <div class="position-relative flex-grow-1" style="max-width:280px;">
                <i data-feather="search" class="position-absolute text-muted"
                   style="width:13px;height:13px;top:50%;transform:translateY(-50%);inset-inline-start:11px;pointer-events:none;"></i>
                <input type="text" id="staff-search" class="form-control form-control-sm rounded-pill"
                       style="padding-inline-start:32px;" placeholder="{{ __('Search…') }}" autocomplete="off">
            </div>

            {{-- Summary badges --}}
            <div class="ms-auto d-flex gap-2 flex-wrap">
                <span class="badge rounded-pill px-3 py-2" style="background:rgba(102,126,234,.15);color:#a5b4fd;font-size:.72rem;">
                    <i data-feather="users" style="width:11px;height:11px;margin-inline-end:4px;"></i>
                    {{ $employees->count() }} {{ __('employees') }}
                </span>
                <span class="badge rounded-pill px-3 py-2" style="background:rgba(43,207,126,.12);color:#2bcf7e;font-size:.72rem;">
                    <i data-feather="scissors" style="width:11px;height:11px;margin-inline-end:4px;"></i>
                    {{ $services->count() }} {{ __('services') }}
                </span>
                <span class="badge rounded-pill px-3 py-2" style="background:rgba(201,162,39,.12);color:#C9A227;font-size:.72rem;">
                    <i data-feather="image" style="width:11px;height:11px;margin-inline-end:4px;"></i>
                    {{ $images->count() }} {{ __('photos') }}
                </span>
            </div>
        </form>
    </div>
</div>

{{-- ══ TABS ══ --}}
<ul class="nav nav-tabs mb-4" id="staffTabs" role="tablist" style="border-bottom:1px solid rgba(255,255,255,.08);">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'employees' ? 'active' : '' }} d-flex align-items-center gap-2"
                id="tab-employees" data-bs-toggle="tab" data-bs-target="#pane-employees"
                type="button" role="tab" onclick="setTab('employees')">
            <i data-feather="users" style="width:14px;height:14px;"></i>
            {{ __('Employees') }}
            <span class="badge rounded-pill ms-1" style="background:rgba(102,126,234,.2);color:#a5b4fd;font-size:.65rem;">
                {{ $employees->count() }}
            </span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'services' ? 'active' : '' }} d-flex align-items-center gap-2"
                id="tab-services" data-bs-toggle="tab" data-bs-target="#pane-services"
                type="button" role="tab" onclick="setTab('services')">
            <i data-feather="scissors" style="width:14px;height:14px;"></i>
            {{ __('Services') }}
            <span class="badge rounded-pill ms-1" style="background:rgba(43,207,126,.15);color:#2bcf7e;font-size:.65rem;">
                {{ $services->count() }}
            </span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'gallery' ? 'active' : '' }} d-flex align-items-center gap-2"
                id="tab-gallery" data-bs-toggle="tab" data-bs-target="#pane-gallery"
                type="button" role="tab" onclick="setTab('gallery')">
            <i data-feather="image" style="width:14px;height:14px;"></i>
            {{ __('Gallery') }}
            <span class="badge rounded-pill ms-1" style="background:rgba(201,162,39,.15);color:#C9A227;font-size:.65rem;">
                {{ $images->count() }}
            </span>
        </button>
    </li>
</ul>

<div class="tab-content" id="staffTabContent">

    {{-- ══════════════ TAB: EMPLOYEES ══════════════ --}}
    <div class="tab-pane fade {{ $activeTab === 'employees' ? 'show active' : '' }}" id="pane-employees" role="tabpanel">
        <div class="card shadow-sm bk-a2" style="border-radius:16px;overflow:hidden;">
            <div class="card-body p-0">
                @forelse($employees as $emp)
                @php
                    $palette = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1','#fda085'];
                    $bg      = $palette[$emp->id % count($palette)];
                    $initial = strtoupper(mb_substr($emp->name_en ?? $emp->name_ar ?? '?', 0, 1));
                    $name    = $isAr ? ($emp->name_ar ?: $emp->name_en) : ($emp->name_en ?: $emp->name_ar);
                @endphp
                <div class="staff-row d-flex align-items-center gap-3 px-4 py-3" data-search="{{ strtolower($name . ' ' . $emp->branch?->name_en . ' ' . $emp->branch?->name_ar . ' ' . $emp->role?->label_en) }}">

                    {{-- Avatar --}}
                    <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,{{ $bg }}bb,{{ $bg }});display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:#fff;flex-shrink:0;">
                        {{ $initial }}
                    </div>

                    {{-- Info --}}
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span style="font-weight:600;font-size:.875rem;">{{ $name }}</span>
                            @if($emp->role)
                                <span style="font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(102,126,234,.15);color:#a5b4fd;">
                                    {{ $isAr ? ($emp->role->label_ar ?: $emp->role->label_en) : ($emp->role->label_en ?: $emp->role->label_ar) }}
                                </span>
                            @endif
                            @if($emp->is_active)
                                <span style="font-size:.68rem;font-weight:700;color:#43e97b;">● {{ __('Active') }}</span>
                            @else
                                <span style="font-size:.68rem;color:#6c757d;">● {{ __('Inactive') }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-1" style="font-size:.72rem;opacity:.55;">
                            @if($emp->branch)
                                <span><i data-feather="map-pin" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ $emp->branch->localizedName() }}</span>
                            @endif
                            @if($emp->email)
                                <span><i data-feather="mail" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ $emp->email }}</span>
                            @endif
                            @if($emp->phone)
                                <span><i data-feather="phone" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ $emp->phone }}</span>
                            @endif
                            <span><i data-feather="calendar" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ $emp->appointments_this_month ?? 0 }} {{ __('appts this month') }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="{{ route('company.employees.edit', $emp) }}"
                           class="btn btn-sm rounded-pill px-3"
                           style="font-size:.72rem;font-weight:600;background:rgba(79,172,254,.12);color:#4facfe;border:none;">
                            <i data-feather="edit-2" style="width:11px;height:11px;margin-inline-end:4px;"></i>{{ __('Edit') }}
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center py-5" style="opacity:.4;">
                    <i data-feather="users" style="width:40px;height:40px;"></i>
                    <p class="mt-3 mb-0">{{ __('No employees found.') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══════════════ TAB: SERVICES ══════════════ --}}
    <div class="tab-pane fade {{ $activeTab === 'services' ? 'show active' : '' }}" id="pane-services" role="tabpanel">
        <div class="card shadow-sm bk-a2" style="border-radius:16px;overflow:hidden;">
            <div class="card-body p-0">
                @forelse($services as $svc)
                @php
                    $svcName = $isAr ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar);
                    $catName = $svc->serviceCategory ? ($isAr ? ($svc->serviceCategory->name_ar ?: $svc->serviceCategory->name_en) : ($svc->serviceCategory->name_en ?: $svc->serviceCategory->name_ar)) : null;
                @endphp
                <div class="staff-row d-flex align-items-center gap-3 px-4 py-3" data-search="{{ strtolower($svcName . ' ' . $svc->branch?->name_en . ' ' . $svc->branch?->name_ar . ' ' . $catName) }}">

                    {{-- Icon --}}
                    <div style="width:42px;height:42px;border-radius:12px;background:{{ $svc->is_active ? 'rgba(43,207,126,.12)' : 'rgba(108,117,125,.1)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-feather="scissors" style="width:18px;height:18px;color:{{ $svc->is_active ? '#2bcf7e' : '#6c757d' }};"></i>
                    </div>

                    {{-- Info --}}
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span style="font-weight:600;font-size:.875rem;">{{ $svcName }}</span>
                            @if($catName)
                                <span style="font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(201,162,39,.12);color:#C9A227;">
                                    {{ $catName }}
                                </span>
                            @endif
                            @if(!$svc->is_active)
                                <span style="font-size:.68rem;color:#6c757d;">{{ __('Inactive') }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-1" style="font-size:.72rem;opacity:.55;">
                            @if($svc->branch)
                                <span><i data-feather="map-pin" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ $svc->branch->localizedName() }}</span>
                            @endif
                            <span><i data-feather="clock" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ $svc->duration_minutes }} {{ __('min') }}</span>
                            <span><i data-feather="tag" style="width:10px;height:10px;margin-inline-end:3px;"></i>{{ number_format($svc->price, 0) }} {{ $svc->currency }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="{{ route('company.services.edit', $svc) }}"
                           class="btn btn-sm rounded-pill px-3"
                           style="font-size:.72rem;font-weight:600;background:rgba(79,172,254,.12);color:#4facfe;border:none;">
                            <i data-feather="edit-2" style="width:11px;height:11px;margin-inline-end:4px;"></i>{{ __('Edit') }}
                        </a>
                        <form method="POST" action="{{ route('company.services.toggle-active', $svc) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm rounded-pill px-3"
                                    style="font-size:.72rem;font-weight:600;background:{{ $svc->is_active ? 'rgba(245,87,108,.1)' : 'rgba(43,207,126,.1)' }};color:{{ $svc->is_active ? '#f5576c' : '#2bcf7e' }};border:none;">
                                {{ $svc->is_active ? __('Deactivate') : __('Activate') }}
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-5" style="opacity:.4;">
                    <i data-feather="scissors" style="width:40px;height:40px;"></i>
                    <p class="mt-3 mb-0">{{ __('No services found.') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══════════════ TAB: GALLERY ══════════════ --}}
    <div class="tab-pane fade {{ $activeTab === 'gallery' ? 'show active' : '' }}" id="pane-gallery" role="tabpanel">

        @if($images->isEmpty())
        <div class="text-center py-5" style="opacity:.4;">
            <i data-feather="image" style="width:40px;height:40px;"></i>
            <p class="mt-3 mb-0">{{ __('No photos yet.') }}</p>
        </div>
        @else

        {{-- Group by branch --}}
        @foreach($images->groupBy('branch_id') as $branchId => $branchImages)
        @php $branchName = $branchImages->first()->branch?->localizedName() ?? '—'; @endphp

        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i data-feather="map-pin" style="width:14px;height:14px;opacity:.5;"></i>
                    <span style="font-weight:700;font-size:.875rem;">{{ $branchName }}</span>
                    <span class="badge rounded-pill px-2" style="background:rgba(255,255,255,.08);font-size:.65rem;">
                        {{ $branchImages->count() }} {{ __('photos') }}
                    </span>
                </div>
                <a href="{{ route('company.branches.gallery', $branchId) }}"
                   class="btn btn-sm rounded-pill px-3"
                   style="font-size:.72rem;font-weight:600;background:rgba(201,162,39,.12);color:#C9A227;border:none;">
                    <i data-feather="external-link" style="width:11px;height:11px;margin-inline-end:4px;"></i>{{ __('Manage') }}
                </a>
            </div>

            {{-- Place photos --}}
            @php $place = $branchImages->where('type','place'); $work = $branchImages->where('type','work'); @endphp

            @if($place->isNotEmpty())
            <p class="mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;opacity:.45;">
                {{ __('Place Photos') }}
            </p>
            <div class="gallery-grid mb-3">
                @foreach($place as $img)
                <div class="gallery-thumb" onclick="openLightbox('{{ asset('storage/'.$img->path) }}')">
                    <img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">
                </div>
                @endforeach
            </div>
            @endif

            @if($work->isNotEmpty())
            <p class="mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;opacity:.45;">
                {{ __('Work Samples') }}
            </p>
            <div class="gallery-grid mb-3">
                @foreach($work as $img)
                <div class="gallery-thumb" onclick="openLightbox('{{ asset('storage/'.$img->path) }}')">
                    <img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">
                </div>
                @endforeach
            </div>
            @endif
        </div>

        @if(!$loop->last)
        <hr style="border-color:rgba(255,255,255,.07);margin:1.5rem 0;">
        @endif

        @endforeach
        @endif
    </div>

</div>{{-- tab-content --}}

{{-- Lightbox --}}
<div id="bk-lightbox" onclick="closeLightbox()"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);align-items:center;justify-content:center;cursor:zoom-out;">
    <img id="bk-lightbox-img" src="" alt=""
         style="max-width:92vw;max-height:90vh;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.6);object-fit:contain;">
</div>
</div>{{-- page-content --}}

<style>
.staff-row {
    border-bottom: 1px solid rgba(255,255,255,.05);
    transition: background .15s;
}
.bk-theme-light .staff-row { border-bottom-color: rgba(0,0,0,.05); }
.staff-row:last-child { border-bottom: none; }
.staff-row:hover { background: rgba(102,126,234,.05); }
.nav-tabs .nav-link {
    font-size: .82rem;
    font-weight: 600;
    padding: .55rem 1.1rem;
    border-radius: 10px 10px 0 0;
    color: inherit;
    opacity: .6;
    border: none;
    transition: opacity .15s, background .15s;
}
.nav-tabs .nav-link.active { opacity: 1; border-bottom: 2px solid #C9A227 !important; }
.nav-tabs .nav-link:hover:not(.active) { opacity: .85; background: rgba(255,255,255,.04); }

/* Gallery */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 8px;
}
.gallery-thumb {
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    cursor: zoom-in;
    background: rgba(255,255,255,.05);
    position: relative;
}
.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .25s, opacity .2s;
    display: block;
}
.gallery-thumb:hover img { transform: scale(1.07); opacity: .85; }
</style>

<script>
function setTab(tab) {
    var url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    history.replaceState(null, '', url.toString());
    document.querySelector('[name="tab"]').value = tab;
}

// Live search
document.getElementById('staff-search').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.staff-row').forEach(function(row) {
        var text = (row.dataset.search || '').toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});

// Lightbox
function openLightbox(src) {
    var lb = document.getElementById('bk-lightbox');
    document.getElementById('bk-lightbox-img').src = src;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('bk-lightbox').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>
@endsection
