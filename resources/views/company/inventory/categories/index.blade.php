@extends('company.dashboard')

@push('company-styles')
<style>
.cat-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px; padding: 26px 30px; margin-bottom: 24px;
    color: #fff; position: relative; overflow: hidden;
}
.cat-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
.hero-stat {
    background: rgba(255,255,255,.15);
    border-radius: 12px; padding: 10px 18px; text-align: center;
    backdrop-filter: blur(8px); min-width: 80px;
}
.hero-stat-num { font-size: 22px; font-weight: 800; line-height: 1; }
.hero-stat-lbl { font-size: 11px; opacity: .8; margin-top: 2px; }

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

    {{-- ── Hero ── --}}
    <div class="cat-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 position-relative" style="z-index:1;">
            <div>
                <h3 class="fw-bold mb-1">🏷️ {{ __('Product Categories') }}</h3>
                <p class="mb-0" style="opacity:.75; font-size:13px;">{{ __('Organize your products into categories') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('company.inventory.index') }}" class="btn btn-sm btn-outline-light">← {{ __('Inventory') }}</a>
                <button class="btn btn-sm btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#addCatModal">
                    + {{ __('Add Category') }}
                </button>
            </div>
        </div>
        <div class="d-flex gap-3 flex-wrap position-relative" style="z-index:1;">
            <div class="hero-stat">
                <div class="hero-stat-num">{{ $stats['total'] }}</div>
                <div class="hero-stat-lbl">{{ __('Total') }}</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num">{{ $stats['parents'] }}</div>
                <div class="hero-stat-lbl">{{ __('Main') }}</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num">{{ $stats['subs'] }}</div>
                <div class="hero-stat-lbl">{{ __('Sub') }}</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num">{{ $stats['products'] }}</div>
                <div class="hero-stat-lbl">{{ __('Products') }}</div>
            </div>
        </div>
    </div>

    {{-- ── Search (client-side instant filter) ── --}}
    <div class="mb-4">
        <div class="search-bar" style="max-width:380px;">
            <span class="search-icon">🔍</span>
            <input type="text" class="form-control"
                placeholder="{{ __('Search categories...') }}"
                autocomplete="off" id="catSearch">
        </div>
    </div>

    {{-- ── Cards ── --}}
    <div class="row g-3">
        @forelse($categories as $cat)
            @php $color = $cat->color ?? '#667eea'; @endphp
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
                            class="btn btn-sm w-100 mb-2 fw-semibold"
                            style="background:{{ $color }}18; color:{{ $color }}; border:1px solid {{ $color }}33;">
                            📦 {{ $cat->products_count }} {{ __('Products') }}
                        </a>

                        <div class="d-flex gap-1 mt-auto">
                            <button class="btn btn-sm btn-outline-primary flex-fill"
                                data-edit-id="{{ $cat->id }}"
                                data-edit-name-en="{{ $cat->name_en }}"
                                data-edit-name-ar="{{ $cat->name_ar }}"
                                data-edit-parent="{{ $cat->parent_id }}"
                                data-edit-color="{{ $color }}">
                                ✏️ {{ __('Edit') }}
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                data-delete-id="{{ $cat->id }}"
                                data-delete-name="{{ $cat->localizedName() }}"
                                data-delete-count="{{ $cat->products_count }}">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <div style="font-size:48px;">🏷️</div>
                <p class="mt-2">
                    @if(request('search'))
                        {{ __('No categories found for') }} "<strong>{{ request('search') }}</strong>"
                        <br><a href="{{ route('company.product-categories.index') }}" class="btn btn-sm btn-outline-secondary mt-2">✕ {{ __('Clear search') }}</a>
                    @else
                        {{ __('No categories yet.') }}
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- no-results for client search --}}
    <div id="catNoResults" class="col-12 text-center py-4 text-muted" style="display:none;">
        <div style="font-size:36px;">🔍</div>
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

{{-- ── Add Modal ── --}}
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" action="{{ route('company.product-categories.store') }}" class="modal-content" novalidate id="addCatForm">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">➕ {{ __('Add Category') }}</h6>
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
                        <input type="color" name="color" id="addColor" value="#667eea"
                            class="form-control form-control-color" style="width:50px;height:38px;padding:2px;cursor:pointer;">
                        <div id="addColorPreview" class="px-3 py-1 rounded-pill text-white fw-semibold"
                            style="background:#667eea;font-size:12px;transition:background .2s;">
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
                <h6 class="modal-title fw-bold">✏️ {{ __('Edit Category') }}</h6>
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
                        <input type="color" name="color" id="editColor" value="#667eea"
                            class="form-control form-control-color" style="width:50px;height:38px;padding:2px;cursor:pointer;">
                        <div id="editColorPreview" class="px-3 py-1 rounded-pill text-white fw-semibold"
                            style="background:#667eea;font-size:12px;transition:background .2s;">
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

    // Edit buttons
    document.querySelectorAll('[data-edit-id]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var color = this.dataset.editColor || '#667eea';
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
