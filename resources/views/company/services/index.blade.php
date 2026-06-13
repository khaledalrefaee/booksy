@extends('company.dashboard')
@section('content')
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin gap-3">
        <div>
            <h4 class="mb-1">{{ __('Services') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.branches.index') }}">{{ __('Branches') }}</a></li>
                    <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.branches.services.create', $branch) }}" class="btn btn-primary btn-icon-text rounded-pill">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add service') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- ── Toolbar ──────────────────────────────────────────────────── --}}
        <div class="card-header border-bottom bg-transparent px-4 py-3 d-flex flex-wrap align-items-center gap-3">

            {{-- Search --}}
            <div class="position-relative flex-grow-1" style="max-width:320px; min-width:180px;">
                <i data-feather="search"
                   class="position-absolute text-muted"
                   style="width:15px;height:15px;top:50%;transform:translateY(-50%);inset-inline-start:11px;pointer-events:none;"></i>
                <input type="search" id="svc-search"
                       class="form-control rounded-pill"
                       style="padding-inline-start:34px;"
                       placeholder="{{ __('Search services…') }}"
                       autocomplete="off">
            </div>

            {{-- Category filter --}}
            <select id="svc-cat-filter" class="form-select rounded-pill" style="width:auto;min-width:150px;">
                <option value="">{{ __('All categories') }}</option>
                @foreach ($services->pluck('serviceCategory')->filter()->unique('id') as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
                @endforeach
            </select>

            {{-- Status filter --}}
            <select id="svc-status-filter" class="form-select rounded-pill" style="width:auto;min-width:130px;">
                <option value="">{{ __('All statuses') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Inactive') }}</option>
            </select>

            {{-- Results count --}}
            <span id="svc-count" class="text-muted small ms-auto">
                {{ $services->count() }} {{ __('services') }}
            </span>
        </div>

        {{-- ── Table ────────────────────────────────────────────────────── --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="svc-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" data-col="name">
                                <button type="button" class="btn btn-link p-0 fw-semibold text-reset text-decoration-none svc-sort"
                                        data-col="name">
                                    {{ __('Service') }}
                                    <i data-feather="chevrons-up-down" style="width:12px;height:12px;" class="sort-icon ms-1"></i>
                                </button>
                            </th>
                            <th>{{ __('Category') }}</th>
                            <th>
                                <button type="button" class="btn btn-link p-0 fw-semibold text-reset text-decoration-none svc-sort"
                                        data-col="price">
                                    {{ __('Price') }}
                                    <i data-feather="chevrons-up-down" style="width:12px;height:12px;" class="sort-icon ms-1"></i>
                                </button>
                            </th>
                            <th>
                                <button type="button" class="btn btn-link p-0 fw-semibold text-reset text-decoration-none svc-sort"
                                        data-col="duration">
                                    {{ __('Duration') }}
                                    <i data-feather="chevrons-up-down" style="width:12px;height:12px;" class="sort-icon ms-1"></i>
                                </button>
                            </th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="svc-tbody">
                        @forelse ($services as $service)
                        <tr class="svc-row"
                            data-name="{{ strtolower(($service->name_en ?? '') . ' ' . ($service->name_ar ?? '')) }}"
                            data-cat="{{ $service->service_category_id ?? '' }}"
                            data-active="{{ $service->is_active ? '1' : '0' }}"
                            data-price="{{ $service->price }}"
                            data-duration="{{ $service->duration_minutes }}">

                            <td class="ps-4">
                                <div class="fw-medium">{{ $service->name_en ?: '—' }}</div>
                                @if($service->name_ar)
                                    <div class="text-muted tx-12" dir="rtl">{{ $service->name_ar }}</div>
                                @endif
                            </td>
                            <td class="text-muted">{{ $service->serviceCategory?->localizedName() ?? '—' }}</td>
                            <td class="fw-semibold">
                                {{ number_format($service->price, 2) }}
                                <span class="text-muted small">{{ $service->currency }}</span>
                            </td>
                            <td class="text-muted">{{ $service->duration_minutes }} {{ __('min') }}</td>
                            <td>
                                <form action="{{ route('company.services.toggle-active', $service) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm rounded-pill {{ $service->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                        {{ $service->is_active ? __('Active') : __('Inactive') }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('company.services.edit', $service) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill me-1">{{ __('Edit') }}</a>
                                <form action="{{ route('company.services.destroy', $service) }}" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('Delete this service?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr id="svc-empty-server">
                            <td colspan="6" class="text-center text-muted py-5">
                                {{ __('No services for this branch.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- No-results row (hidden until filter yields zero) --}}
                <div id="svc-no-results" class="d-none text-center text-muted py-5">
                    <i data-feather="search" style="width:32px;height:32px;" class="mb-2 d-block mx-auto"></i>
                    {{ __('No services match your search.') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var rows        = Array.from(document.querySelectorAll('.svc-row'));
    var searchInput = document.getElementById('svc-search');
    var catFilter   = document.getElementById('svc-cat-filter');
    var statusFilter= document.getElementById('svc-status-filter');
    var countEl     = document.getElementById('svc-count');
    var noResults   = document.getElementById('svc-no-results');
    var sortBtns    = document.querySelectorAll('.svc-sort');

    var state = { query: '', cat: '', status: '', sortCol: '', sortDir: 1 };

    /* ── filter + sort ─────────────────────────────────────────── */
    function applyFilter() {
        var q      = state.query.trim().toLowerCase();
        var cat    = state.cat;
        var status = state.status;
        var visible = 0;

        rows.forEach(function (row) {
            var nameMatch   = !q      || row.dataset.name.includes(q);
            var catMatch    = !cat    || row.dataset.cat === cat;
            var statusMatch = !status || row.dataset.active === status;
            var show = nameMatch && catMatch && statusMatch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        /* sort visible rows */
        if (state.sortCol) {
            var tbody = document.getElementById('svc-tbody');
            var visibleRows = rows.filter(function (r) { return r.style.display !== 'none'; });
            visibleRows.sort(function (a, b) {
                var av = a.dataset[state.sortCol];
                var bv = b.dataset[state.sortCol];
                var numA = parseFloat(av), numB = parseFloat(bv);
                var cmp = isNaN(numA) ? av.localeCompare(bv) : numA - numB;
                return cmp * state.sortDir;
            });
            visibleRows.forEach(function (r) { tbody.appendChild(r); });
        }

        countEl.textContent = visible + ' {{ __('services') }}';
        noResults.classList.toggle('d-none', visible > 0);
        if (window.feather) feather.replace();
    }

    /* ── debounce helper ───────────────────────────────────────── */
    function debounce(fn, ms) {
        var t;
        return function () { clearTimeout(t); t = setTimeout(fn, ms); };
    }

    /* ── events ────────────────────────────────────────────────── */
    searchInput.addEventListener('input', debounce(function () {
        state.query = searchInput.value;
        applyFilter();
    }, 150));

    catFilter.addEventListener('change', function () {
        state.cat = this.value;
        applyFilter();
    });

    statusFilter.addEventListener('change', function () {
        state.status = this.value;
        applyFilter();
    });

    sortBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var col = this.dataset.col;
            if (state.sortCol === col) {
                state.sortDir *= -1;
            } else {
                state.sortCol = col;
                state.sortDir = 1;
            }
            /* update icons */
            sortBtns.forEach(function (b) {
                var icon = b.querySelector('[data-feather]');
                if (!icon) return;
                if (b.dataset.col === state.sortCol) {
                    icon.setAttribute('data-feather', state.sortDir === 1 ? 'chevron-up' : 'chevron-down');
                } else {
                    icon.setAttribute('data-feather', 'chevrons-up-down');
                }
            });
            applyFilter();
        });
    });

    /* clear search on × */
    searchInput.addEventListener('search', function () {
        if (!this.value) { state.query = ''; applyFilter(); }
    });

    if (window.feather) feather.replace();
})();
</script>
@endpush
