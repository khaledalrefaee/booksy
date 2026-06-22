@extends('owner.dashboard')
@section('content')
@php $isAr = app()->getLocale() === 'ar'; @endphp

<div class="page-content">

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <div>
        <h4 class="mb-1">{{ __('Locations') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Locations') }}</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-0" id="loc-tabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-countries" type="button">
            <i data-feather="globe" style="width:14px;height:14px;margin-inline-end:6px;"></i>
            {{ __('Countries') }}
            <span class="badge bg-secondary ms-1">{{ $countries->count() }}</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-governorates" type="button">
            <i data-feather="map" style="width:14px;height:14px;margin-inline-end:6px;"></i>
            {{ __('Governorates') }}
            <span class="badge bg-secondary ms-1">{{ $governorates->count() }}</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-areas" type="button">
            <i data-feather="map-pin" style="width:14px;height:14px;margin-inline-end:6px;"></i>
            {{ __('Areas') }}
            <span class="badge bg-secondary ms-1">{{ $areas->count() }}</span>
        </button>
    </li>
</ul>

<div class="tab-content">

{{-- ══════════════ TAB: COUNTRIES ══════════════ --}}
<div class="tab-pane fade show active" id="tab-countries" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:0 0 12px 12px;">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-country-add">
                    <i data-feather="plus" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                    {{ __('Add country') }}
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Name (EN)') }}</th>
                            <th>{{ __('Name (AR)') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Dial') }}</th>
                            <th>{{ __('Governorates') }}</th>
                            <th>{{ __('Sort') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($countries as $c)
                        <tr>
                            <td class="text-muted small">{{ $c->id }}</td>
                            <td>{{ $c->name_en }}</td>
                            <td dir="rtl">{{ $c->name_ar }}</td>
                            <td><span class="badge bg-secondary">{{ $c->code }}</span></td>
                            <td class="text-muted small">{{ $c->dial_code ?: '—' }}</td>
                            <td>{{ $c->governorates_count }}</td>
                            <td>{{ $c->sort_order }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal" data-bs-target="#modal-country-edit"
                                    data-id="{{ $c->id }}"
                                    data-name-en="{{ $c->name_en }}"
                                    data-name-ar="{{ $c->name_ar }}"
                                    data-code="{{ $c->code }}"
                                    data-dial="{{ $c->dial_code }}"
                                    data-sort="{{ $c->sort_order }}"
                                    data-url="{{ route('owner.locations.countries.update', $c) }}">
                                    {{ __('Edit') }}
                                </button>
                                <form method="POST" action="{{ route('owner.locations.countries.destroy', $c) }}" class="d-inline"
                                      onsubmit="return confirm('{{ __('Delete this country? All governorates and areas will be deleted too.') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No countries yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ TAB: GOVERNORATES ══════════════ --}}
<div class="tab-pane fade" id="tab-governorates" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:0 0 12px 12px;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                {{-- Filter by country --}}
                <select id="gov-filter-country" class="form-select form-select-sm w-auto">
                    <option value="">{{ __('All countries') }}</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $isAr ? $c->name_ar : $c->name_en }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-gov-add">
                    <i data-feather="plus" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                    {{ __('Add governorate') }}
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tbl-govs">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Country') }}</th>
                            <th>{{ __('Name (EN)') }}</th>
                            <th>{{ __('Name (AR)') }}</th>
                            <th>{{ __('Areas') }}</th>
                            <th>{{ __('Sort') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($governorates as $g)
                        <tr data-country-id="{{ $g->country_id }}">
                            <td class="text-muted small">{{ $g->id }}</td>
                            <td class="text-muted small">{{ $isAr ? $g->country?->name_ar : $g->country?->name_en }}</td>
                            <td>{{ $g->name_en }}</td>
                            <td dir="rtl">{{ $g->name_ar }}</td>
                            <td>{{ $g->areas_count }}</td>
                            <td>{{ $g->sort_order }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal" data-bs-target="#modal-gov-edit"
                                    data-id="{{ $g->id }}"
                                    data-country-id="{{ $g->country_id }}"
                                    data-name-en="{{ $g->name_en }}"
                                    data-name-ar="{{ $g->name_ar }}"
                                    data-sort="{{ $g->sort_order }}"
                                    data-url="{{ route('owner.locations.governorates.update', $g) }}">
                                    {{ __('Edit') }}
                                </button>
                                <form method="POST" action="{{ route('owner.locations.governorates.destroy', $g) }}" class="d-inline"
                                      onsubmit="return confirm('{{ __('Delete this governorate? All areas will be deleted too.') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No governorates yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ TAB: AREAS ══════════════ --}}
<div class="tab-pane fade" id="tab-areas" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:0 0 12px 12px;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                <div class="d-flex gap-2">
                    {{-- Filter by country then governorate --}}
                    <select id="area-filter-country" class="form-select form-select-sm w-auto">
                        <option value="">{{ __('All countries') }}</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $isAr ? $c->name_ar : $c->name_en }}</option>
                        @endforeach
                    </select>
                    <select id="area-filter-gov" class="form-select form-select-sm w-auto" disabled>
                        <option value="">{{ __('All governorates') }}</option>
                    </select>
                    {{-- Live search --}}
                    <input type="text" id="area-search" class="form-control form-control-sm" placeholder="{{ __('Search…') }}" style="width:160px;">
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-area-add">
                    <i data-feather="plus" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                    {{ __('Add area') }}
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tbl-areas">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Governorate') }}</th>
                            <th>{{ __('Name (EN)') }}</th>
                            <th>{{ __('Name (AR)') }}</th>
                            <th>{{ __('Sort') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($areas as $a)
                        <tr data-gov-id="{{ $a->governorate_id }}"
                            data-country-id="{{ $a->governorate?->country_id }}"
                            data-search="{{ strtolower($a->name_en . ' ' . $a->name_ar) }}">
                            <td class="text-muted small">{{ $a->id }}</td>
                            <td class="text-muted small">{{ $isAr ? $a->governorate?->name_ar : $a->governorate?->name_en }}</td>
                            <td>{{ $a->name_en }}</td>
                            <td dir="rtl">{{ $a->name_ar }}</td>
                            <td>{{ $a->sort_order }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal" data-bs-target="#modal-area-edit"
                                    data-id="{{ $a->id }}"
                                    data-gov-id="{{ $a->governorate_id }}"
                                    data-name-en="{{ $a->name_en }}"
                                    data-name-ar="{{ $a->name_ar }}"
                                    data-sort="{{ $a->sort_order }}"
                                    data-url="{{ route('owner.locations.areas.update', $a) }}">
                                    {{ __('Edit') }}
                                </button>
                                <form method="POST" action="{{ route('owner.locations.areas.destroy', $a) }}" class="d-inline"
                                      onsubmit="return confirm('{{ __('Delete this area?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No areas yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>{{-- tab-content --}}

</div>{{-- page-content --}}

{{-- ══════════════ MODALS ══════════════ --}}

{{-- Add Country --}}
<div class="modal fade" id="modal-country-add" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('owner.locations.countries.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('Add country') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('owner.locations.partials.country-fields')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Country --}}
<div class="modal fade" id="modal-country-edit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="form-country-edit" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ __('Edit country') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('owner.locations.partials.country-fields')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Governorate --}}
<div class="modal fade" id="modal-gov-add" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('owner.locations.governorates.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('Add governorate') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('owner.locations.partials.gov-fields', ['countries' => $countries, 'selCountryId' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Governorate --}}
<div class="modal fade" id="modal-gov-edit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="form-gov-edit" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ __('Edit governorate') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('owner.locations.partials.gov-fields', ['countries' => $countries, 'selCountryId' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Area --}}
<div class="modal fade" id="modal-area-add" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('owner.locations.areas.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('Add area') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('owner.locations.partials.area-fields', ['governorates' => $governorates, 'selGovId' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Area --}}
<div class="modal fade" id="modal-area-edit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="form-area-edit" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ __('Edit area') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('owner.locations.partials.area-fields', ['governorates' => $governorates, 'selGovId' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var GOV_BY_COUNTRY = @json(route('owner.locations.governorates-by-country'));
    var isAr = @json(app()->getLocale() === 'ar');

    // ── Edit Country modal ──────────────────────────────
    document.getElementById('modal-country-edit').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var form = document.getElementById('form-country-edit');
        form.action = btn.dataset.url;
        form.querySelector('[name="name_en"]').value   = btn.dataset.nameEn;
        form.querySelector('[name="name_ar"]').value   = btn.dataset.nameAr;
        form.querySelector('[name="code"]').value      = btn.dataset.code;
        form.querySelector('[name="dial_code"]').value = btn.dataset.dial;
        form.querySelector('[name="sort_order"]').value= btn.dataset.sort;
    });

    // ── Governorate filter ──────────────────────────────
    document.getElementById('gov-filter-country').addEventListener('change', function () {
        var cid = this.value;
        document.querySelectorAll('#tbl-govs tbody tr').forEach(function (tr) {
            tr.style.display = (!cid || tr.dataset.countryId === cid) ? '' : 'none';
        });
    });

    // ── Edit Governorate modal ──────────────────────────
    document.getElementById('modal-gov-edit').addEventListener('show.bs.modal', function (e) {
        var btn  = e.relatedTarget;
        var form = document.getElementById('form-gov-edit');
        form.action = btn.dataset.url;
        form.querySelector('[name="country_id"]').value  = btn.dataset.countryId;
        form.querySelector('[name="name_en"]').value     = btn.dataset.nameEn;
        form.querySelector('[name="name_ar"]').value     = btn.dataset.nameAr;
        form.querySelector('[name="sort_order"]').value  = btn.dataset.sort;
    });

    // ── Area filters ────────────────────────────────────
    var areaFilterCountry = document.getElementById('area-filter-country');
    var areaFilterGov     = document.getElementById('area-filter-gov');
    var areaSearch        = document.getElementById('area-search');

    function filterAreas() {
        var cid = areaFilterCountry.value;
        var gid = areaFilterGov.value;
        var q   = areaSearch.value.toLowerCase();
        document.querySelectorAll('#tbl-areas tbody tr').forEach(function (tr) {
            var matchC = !cid || tr.dataset.countryId === cid;
            var matchG = !gid || tr.dataset.govId === gid;
            var matchQ = !q   || (tr.dataset.search || '').includes(q);
            tr.style.display = (matchC && matchG && matchQ) ? '' : 'none';
        });
    }

    areaFilterCountry.addEventListener('change', function () {
        var cid = this.value;
        areaFilterGov.innerHTML = '<option value="">{{ __("All governorates") }}</option>';
        areaFilterGov.disabled  = !cid;

        if (cid) {
            fetch(GOV_BY_COUNTRY + '?country_id=' + cid, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (govs) {
                    govs.forEach(function (g) {
                        var opt = document.createElement('option');
                        opt.value = g.id;
                        opt.textContent = isAr ? g.name_ar : g.name_en;
                        areaFilterGov.appendChild(opt);
                    });
                    areaFilterGov.disabled = false;
                });
        }
        filterAreas();
    });

    areaFilterGov.addEventListener('change', filterAreas);
    areaSearch.addEventListener('input', filterAreas);

    // ── Edit Area modal ─────────────────────────────────
    document.getElementById('modal-area-edit').addEventListener('show.bs.modal', function (e) {
        var btn  = e.relatedTarget;
        var form = document.getElementById('form-area-edit');
        form.action = btn.dataset.url;
        form.querySelector('[name="governorate_id"]').value = btn.dataset.govId;
        form.querySelector('[name="name_en"]').value        = btn.dataset.nameEn;
        form.querySelector('[name="name_ar"]').value        = btn.dataset.nameAr;
        form.querySelector('[name="sort_order"]').value     = btn.dataset.sort;
    });

    // open the correct tab if there were validation errors
    @if(old('_tab') === 'governorates')
        new bootstrap.Tab(document.querySelector('[data-bs-target="#tab-governorates"]')).show();
    @elseif(old('_tab') === 'areas')
        new bootstrap.Tab(document.querySelector('[data-bs-target="#tab-areas"]')).show();
    @endif
})();
</script>
@endpush

@endsection
