@extends('company.dashboard')

@push('company-styles')
<style>

.cat-card {
    border-radius: 14px;
    border: 1.5px solid var(--bs-border-color);
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
}
.cat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.cat-card-stripe { height: 5px; }
.cat-card-body { padding: 14px 16px; }
.cat-count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 10px;
    font-weight: 800; font-size: 13px; color: #fff; flex-shrink: 0;
}
.search-bar { position: relative; }
.search-bar .search-icon {
    position: absolute; top: 50%; inset-inline-start: 12px;
    transform: translateY(-50%); pointer-events: none;
}
.search-bar input { padding-inline-start: 38px; }

/* ── Assign-products picker ── */
.as-tools { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:10px; }
.as-tools .form-control { flex:1 1 200px; }
.as-list { border:1px solid var(--bk-border); border-radius:12px; max-height:340px; overflow:auto; background:var(--bk-bg); }
.as-row { display:flex; align-items:center; gap:12px; padding:10px 14px; cursor:pointer; border-bottom:1px solid var(--bk-border); transition:background .12s; margin:0; }
.as-row:last-child { border-bottom:0; }
.as-row:hover { background:var(--bk-sidebar-hover); }
.as-row input { width:18px; height:18px; flex:0 0 auto; accent-color:var(--bk-accent-fill); cursor:pointer; }
.as-thumb { width:36px; height:36px; border-radius:8px; background:var(--bk-surface-2); color:var(--bk-text-muted); display:flex; align-items:center; justify-content:center; flex:0 0 auto; overflow:hidden; }
.as-thumb img { width:100%; height:100%; object-fit:cover; }
.as-thumb svg { width:16px; height:16px; }
.as-name { flex:1; min-width:0; font-weight:600; font-size:.9rem; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.as-cur { font-size:.74rem; font-weight:600; padding:2px 9px; border-radius:20px; background:var(--bk-surface-2); color:var(--bk-text-muted); white-space:nowrap; max-width:42%; overflow:hidden; text-overflow:ellipsis; }
.as-state { padding:36px 16px; text-align:center; color:var(--bk-text-muted); font-size:.9rem; }
.as-state a { font-weight:600; }
.as-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%; flex-wrap:wrap; }
.as-count { font-size:.85rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.cat-actions .bk-act { height:34px; }
</style>
@endpush

@section('content')
<div class="page-content">

    @if($errors->any())
        @push('scripts')
        <script>
        @foreach($errors->all() as $error)
            bkToast(@json($error), 'error');
        @endforeach
        </script>
        @endpush
    @endif

    <x-inventory.head :title="__('Product Categories')" active="categories"
        :subtitle="__('Organize your products into categories') . ' · ' . $stats['total'] . ' ' . __('Categories')">
        <x-slot:actions>
            <button type="button" class="ivh-btn ivh-btn-primary" data-bs-toggle="modal" data-bs-target="#addCatModal">
                <i data-feather="plus" aria-hidden="true"></i>{{ __('Add Category') }}
            </button>
        </x-slot:actions>
    </x-inventory.head>

    {{-- ── Search (client-side instant filter) ── --}}
    <div class="mb-4">
        <div class="search-bar" style="max-width:380px;">
            <span class="search-icon"><i data-feather="search" style="width:16px;height:16px;color:var(--bk-text-muted);" aria-hidden="true"></i></span>
            <input type="text" class="form-control"
                placeholder="{{ __('Search categories...') }}"
                autocomplete="off" id="catSearch">
        </div>
    </div>

    {{-- ── Cards ── --}}
    <div class="row g-3">
        @forelse($categories as $cat)
            @php $color = $cat->color ?? '#5C7038'; @endphp
            <div class="col-sm-6 col-md-4 col-lg-3 cat-filterable"
                 data-name="{{ strtolower($cat->name_en . ' ' . $cat->name_ar) }}">
                <div class="cat-card h-100 d-flex flex-column">
                    <div class="cat-card-stripe" style="background:{{ $color }};"></div>
                    <div class="cat-card-body flex-grow-1 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="cat-count-badge" style="background:{{ $color }};">
                                {{ $cat->products_count }}
                            </div>
                            <div style="min-width:0; flex:1;">
                                <h6 class="fw-bold mb-0 text-truncate">{{ $cat->localizedName() }}</h6>
                                <small class="text-muted">
                                    @if($cat->parent) ↳ {{ $cat->parent->localizedName() }}
                                    @else {{ __('Main category') }}
                                    @endif
                                </small>
                            </div>
                        </div>

                        <a href="{{ route('company.inventory.index', ['category_id' => $cat->id]) }}"
                            class="btn btn-sm w-100 mb-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2"
                            style="background:{{ $color }}18; color:{{ $color }}; border:1px solid {{ $color }}33;">
                            <i data-feather="package" style="width:14px;height:14px;" aria-hidden="true"></i>
                            {{ $cat->products_count }} {{ __('Products') }}
                        </a>

                        <div class="d-flex gap-1 mt-auto cat-actions">
                            <x-bk-action icon="link" class="flex-fill justify-content-center"
                                :variant="$cat->products_count ? 'default' : 'primary'"
                                data-assign-id="{{ $cat->id }}"
                                data-assign-name="{{ $cat->localizedName() }}">{{ __('Assign products') }}</x-bk-action>
                            <x-bk-action icon="edit-2" icon-only
                                data-edit-id="{{ $cat->id }}"
                                data-edit-name-en="{{ $cat->name_en }}"
                                data-edit-name-ar="{{ $cat->name_ar }}"
                                data-edit-parent="{{ $cat->parent_id }}"
                                data-edit-color="{{ $color }}">{{ __('Edit') }}</x-bk-action>
                            <x-bk-action icon="trash-2" variant="danger" icon-only
                                data-delete-id="{{ $cat->id }}"
                                data-delete-name="{{ $cat->localizedName() }}"
                                data-delete-count="{{ $cat->products_count }}">{{ __('Delete') }}</x-bk-action>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <div class="mb-1" style="color:var(--bk-accent);"><i data-feather="tag" style="width:44px;height:44px;stroke-width:1.5;" aria-hidden="true"></i></div>
                <p class="mt-2">
                    @if(request('search'))
                        {{ __('No categories found for') }} "<strong>{{ request('search') }}</strong>"
                        <br><a href="{{ route('company.product-categories.index') }}" class="btn btn-sm btn-outline-secondary mt-2">{{ __('Clear search') }}</a>
                    @else
                        {{ __('No categories yet.') }}
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- no-results for client search --}}
    <div id="catNoResults" class="col-12 text-center py-4 text-muted" style="display:none;">
        <div class="mb-1"><i data-feather="search" style="width:34px;height:34px;stroke-width:1.5;" aria-hidden="true"></i></div>
        <p class="mt-2">{{ __('No categories found.') }}</p>
    </div>

    {{-- ── Pagination ── --}}
    @if($categories->hasPages())
        <div id="catPagination" class="mt-4 d-flex justify-content-center">
            {{ $categories->links() }}
        </div>
    @endif

</div>

{{-- Delete forms --}}
@foreach($categories as $cat)
    <form id="delete-cat-{{ $cat->id }}" method="POST" action="{{ route('company.product-categories.destroy', $cat) }}" class="d-none">
        @csrf @method('DELETE')
    </form>
@endforeach

{{-- ── Assign existing products ── --}}
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" id="assignForm" class="modal-content">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="assignTitle"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="as-tools">
                    <input type="search" id="asSearch" class="form-control form-control-sm" autocomplete="off"
                           placeholder="{{ __('Search products...') }}" aria-label="{{ __('Search products...') }}">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="asUncat">
                        <label class="form-check-label small" for="asUncat">{{ __('Uncategorized only') }}</label>
                    </div>
                </div>
                <div class="as-list" id="asList" role="group" aria-label="{{ __('Products') }}">
                    <div class="as-state">{{ __('Loading…') }}</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <div class="as-foot">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-sm btn-link p-0 fw-semibold" id="asAll">{{ __('Select all shown') }}</button>
                        <span class="as-count"><span id="asN">0</span> {{ __('selected') }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-4" id="asSubmit" disabled>{{ __('Add to category') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Add Modal ── --}}
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" action="{{ route('company.product-categories.store') }}" class="modal-content" novalidate id="addCatForm">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">{{ __('Add Category') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name_en" id="addNameEn" class="form-control" required minlength="2" maxlength="255">
                    <div class="invalid-feedback">{{ __('This field is required (min 2 characters).') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Name (Arabic)') }}</label>
                    <input type="text" name="name_ar" class="form-control" dir="rtl" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Parent') }}</label>
                    <select name="parent_id" class="form-select">
                        <option value="">{{ __('None') }}</option>
                        @foreach($allCategories as $c)
                            <option value="{{ $c->id }}">{{ $c->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Color') }}</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" name="color" id="addColor" value="#5C7038"
                            class="form-control form-control-color" style="width:50px;height:38px;padding:2px;cursor:pointer;">
                        <div id="addColorPreview" class="px-3 py-1 rounded-pill text-white fw-semibold"
                            style="background:#5C7038;font-size:12px;transition:background .2s;">
                            {{ __('Preview') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary px-4">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" id="editCatForm" class="modal-content" novalidate>
            @csrf @method('PUT')
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">{{ __('Edit Category') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name_en" id="editNameEn" class="form-control" required minlength="2" maxlength="255">
                    <div class="invalid-feedback">{{ __('This field is required (min 2 characters).') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Name (Arabic)') }}</label>
                    <input type="text" name="name_ar" id="editNameAr" class="form-control" dir="rtl" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Parent') }}</label>
                    <select name="parent_id" id="editParent" class="form-select">
                        <option value="">{{ __('None') }}</option>
                        @foreach($allCategories as $c)
                            <option value="{{ $c->id }}">{{ $c->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Color') }}</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" name="color" id="editColor" value="#5C7038"
                            class="form-control form-control-color" style="width:50px;height:38px;padding:2px;cursor:pointer;">
                        <div id="editColorPreview" class="px-3 py-1 rounded-pill text-white fw-semibold"
                            style="background:#5C7038;font-size:12px;transition:background .2s;">
                            {{ __('Preview') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary px-4">{{ __('Update') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = new bootstrap.Modal(document.getElementById('editCatModal'));

    // Color live preview
    document.getElementById('addColor').addEventListener('input', function() {
        document.getElementById('addColorPreview').style.background = this.value;
    });
    document.getElementById('editColor').addEventListener('input', function() {
        document.getElementById('editColorPreview').style.background = this.value;
    });

    // Instant client-side search (no server round-trip)
    var searchInput = document.getElementById('catSearch');
    var noResults   = document.getElementById('catNoResults');
    var pagination  = document.getElementById('catPagination');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var cards = document.querySelectorAll('.cat-filterable');
            var visible = 0;
            cards.forEach(function(card) {
                var name = card.dataset.name.toLowerCase();
                var show = !q || name.includes(q);
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (noResults)  noResults.style.display  = (visible === 0 && q) ? '' : 'none';
            if (pagination) pagination.style.display = q ? 'none' : '';
        });
        // Clear on Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { this.value = ''; this.dispatchEvent(new Event('input')); }
        });
    }

    // ── Assign existing products to a category ──
    var asModal   = new bootstrap.Modal(document.getElementById('assignModal'));
    var asList    = document.getElementById('asList');
    var asForm    = document.getElementById('assignForm');
    var asSubmit  = document.getElementById('asSubmit');
    var asN       = document.getElementById('asN');
    var asProducts = [];
    var T = {
        none:    @json(__('No products to assign.')),
        add:     @json(__('Add a product')),
        fail:    @json(__('Could not load products. Please try again.')),
        title:   @json(__('Assign products')),
        noCat:   @json(__('No category')),
        empty:   @json(__('No matching products.')),
        loading: @json(__('Loading…'))
    };
    var addUrl  = @json(route('company.inventory.create'));
    var baseUrl = @json(url('company/product-categories'));

    function asCount() {
        var n = asList.querySelectorAll('input:checked').length;
        asN.textContent = n;
        asSubmit.disabled = n === 0;
    }
    function asState(html) { asList.innerHTML = '<div class="as-state">' + html + '</div>'; asCount(); }

    function asRender() {
        var q = document.getElementById('asSearch').value.trim().toLowerCase();
        var onlyUncat = document.getElementById('asUncat').checked;
        var ticked = {};
        asList.querySelectorAll('input:checked').forEach(function (i) { ticked[i.value] = true; });
        var rows = asProducts.filter(function (p) {
            return (!q || p.name.toLowerCase().indexOf(q) !== -1) && (!onlyUncat || !p.category);
        });
        if (!asProducts.length) { asState(T.none + ' <a href="' + addUrl + '">' + T.add + '</a>'); return; }
        if (!rows.length) { asState(T.empty); return; }
        asList.innerHTML = '';
        rows.forEach(function (p) {
            var row = document.createElement('label'); row.className = 'as-row';
            var cb = document.createElement('input'); cb.type = 'checkbox'; cb.name = 'product_ids[]'; cb.value = p.id;
            cb.checked = !!ticked[p.id]; cb.addEventListener('change', asCount);
            var th = document.createElement('span'); th.className = 'as-thumb';
            if (p.image) { var im = document.createElement('img'); im.src = p.image; im.alt = ''; im.loading = 'lazy'; th.appendChild(im); }
            else { th.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>'; }
            var nm = document.createElement('span'); nm.className = 'as-name'; nm.textContent = p.name; nm.title = p.name;
            var cur = document.createElement('span'); cur.className = 'as-cur'; cur.textContent = p.category || T.noCat;
            row.append(cb, th, nm, cur); asList.appendChild(row);
        });
        asCount();
    }

    function openAssign(id, name) {
        asForm.action = baseUrl + '/' + id + '/products';
        document.getElementById('assignTitle').textContent = T.title + ' — ' + name;
        document.getElementById('asSearch').value = '';
        document.getElementById('asUncat').checked = false;
        asProducts = [];
        asState(T.loading);
        asModal.show();
        fetch(baseUrl + '/' + id + '/products', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) { asProducts = d.products || []; asRender(); })
            .catch(function () { asState(T.fail); });
    }

    document.querySelectorAll('[data-assign-id]').forEach(function (btn) {
        btn.addEventListener('click', function () { openAssign(this.dataset.assignId, this.dataset.assignName); });
    });
    document.getElementById('asSearch').addEventListener('input', asRender);
    document.getElementById('asUncat').addEventListener('change', asRender);
    document.getElementById('asAll').addEventListener('click', function () {
        asList.querySelectorAll('input[type=checkbox]').forEach(function (i) { i.checked = true; });
        asCount();
    });
    // Deep link from an empty product list: ?assign=<category id>
    var want = new URLSearchParams(location.search).get('assign');
    if (want) { var wb = document.querySelector('[data-assign-id="' + want + '"]'); if (wb) wb.click(); }

    // Edit buttons
    document.querySelectorAll('[data-edit-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var color = this.dataset.editColor || '#5C7038';
            document.getElementById('editCatForm').action = "{{ url('company/product-categories') }}/" + this.dataset.editId;
            document.getElementById('editNameEn').value  = this.dataset.editNameEn || '';
            document.getElementById('editNameAr').value  = this.dataset.editNameAr || '';
            document.getElementById('editParent').value  = this.dataset.editParent  || '';
            document.getElementById('editColor').value   = color;
            document.getElementById('editColorPreview').style.background = color;
            document.getElementById('editCatForm').classList.remove('was-validated');
            editModal.show();
        });
    });

    // Delete buttons
    document.querySelectorAll('[data-delete-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id    = this.dataset.deleteId;
            var count = parseInt(this.dataset.deleteCount) || 0;
            var warn  = count > 0
                ? '{{ __("This category has") }} ' + count + ' {{ __("products") }}. {{ __("They will become uncategorized.") }}'
                : '{{ __("This action cannot be undone.") }}';
            bkConfirm({ title: '{{ __("Delete") }} "' + this.dataset.deleteName + '"?', text: warn })
                .then(function(r) { if (r.isConfirmed) document.getElementById('delete-cat-' + id).submit(); });
        });
    });

    // Real-time validation
    document.querySelectorAll('#addCatForm input[required], #editCatForm input[required]').forEach(function(inp) {
        inp.addEventListener('input', function() {
            var ok = this.value.trim().length >= 2;
            var has = this.value.trim().length > 0;
            this.classList.toggle('is-valid', ok);
            this.classList.toggle('is-invalid', has && !ok);
            if (!has) this.classList.remove('is-valid', 'is-invalid');
        });
        inp.addEventListener('blur', function() {
            if (this.value.trim().length < 2) { this.classList.remove('is-valid'); this.classList.add('is-invalid'); }
        });
    });

    document.querySelectorAll('#addCatForm, #editCatForm').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var n = form.querySelector('input[name="name_en"]');
            if (n.value.trim().length < 2) { e.preventDefault(); n.classList.add('is-invalid'); n.focus(); }
        });
    });
});
</script>
@endpush
@endsection
