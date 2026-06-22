@extends('company.dashboard')

@push('company-styles')
<style>
.gl-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.gl-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(201,162,39,.08);
    pointer-events: none;
}
[dir="rtl"] .gl-hero::before { right: auto; left: -60px; }

.gl-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid rgba(255,255,255,.07);
    padding-bottom: 0;
}
.bk-theme-light .gl-tabs { border-bottom-color: rgba(0,0,0,.08); }
.gl-tab-btn {
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    background: none;
    cursor: pointer;
    color: #e2e8f0;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    border-radius: 6px 6px 0 0;
    transition: color .15s, border-color .15s;
    display: flex;
    align-items: center;
    gap: 7px;
    opacity: .5;
}
.bk-theme-light .gl-tab-btn { color: #1e293b; }
.gl-tab-btn.active { opacity: 1; }
.gl-tab-btn.place.active  { color: #4facfe; border-bottom-color: #4facfe; }
.gl-tab-btn.work.active   { color: #f093fb; border-bottom-color: #f093fb; }
.gl-tab-btn .tab-count {
    font-size: 10px;
    padding: 1px 6px;
    border-radius: 10px;
    font-weight: 700;
}
.gl-tab-btn.place .tab-count { background: rgba(79,172,254,.15); color: #4facfe; }
.gl-tab-btn.work  .tab-count { background: rgba(240,147,251,.15); color: #f093fb; }

.gl-panel { display: none; }
.gl-panel.active { display: block; }

.gl-drop-zone {
    border: 2px dashed rgba(255,255,255,.12);
    border-radius: 14px;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    margin-bottom: 20px;
}
.gl-drop-zone.place { border-color: rgba(79,172,254,.28); }
.gl-drop-zone.work  { border-color: rgba(240,147,251,.28); }
.gl-drop-zone.drag-over.place { border-color: #4facfe; background: rgba(79,172,254,.05); }
.gl-drop-zone.drag-over.work  { border-color: #f093fb; background: rgba(240,147,251,.05); }
.gl-drop-zone .dz-icon { opacity: .3; margin-bottom: 10px; }
.gl-drop-zone p  { margin: 0; font-size: 13px; }
.gl-drop-zone small { font-size: 11px; opacity: .35; }

.gl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 12px;
    margin-bottom: 14px;
    min-height: 40px;
}
.gl-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 4/3;
    background: rgba(255,255,255,.04);
    cursor: grab;
    transition: transform .15s, box-shadow .15s;
    border: 2px solid transparent;
}
.gl-item:hover { transform: scale(1.02); box-shadow: 0 8px 24px rgba(0,0,0,.25); }
.gl-item.dragging { opacity: .45; cursor: grabbing; }
.gl-item.drag-target.place { border-color: rgba(79,172,254,.7); }
.gl-item.drag-target.work  { border-color: rgba(240,147,251,.7); }
.gl-item img { width:100%; height:100%; object-fit:cover; pointer-events:none; user-select:none; }
.gl-item-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.6) 0%, transparent 55%);
    opacity: 0; transition: opacity .2s;
    display: flex; align-items: flex-end; justify-content: flex-end;
    padding: 8px;
}
.gl-item:hover .gl-item-overlay { opacity: 1; }
.gl-del-btn {
    width: 28px; height: 28px; border-radius: 50%;
    background: rgba(245,87,108,.88);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #fff; transition: transform .15s;
}
.gl-del-btn:hover { transform: scale(1.12); }
.gl-order-badge {
    position: absolute; top: 6px; left: 6px;
    background: rgba(0,0,0,.5);
    color: #fff; font-size: 10px; font-weight: 700;
    border-radius: 5px; padding: 1px 5px;
    backdrop-filter: blur(4px);
}
[dir="rtl"] .gl-order-badge { left: auto; right: 6px; }

.gl-upload-progress { display:none; margin-bottom:14px; }
.gl-upload-progress .progress { height: 4px; border-radius: 2px; }

.gl-empty { text-align:center; padding:40px 20px; opacity:.35; grid-column:1/-1; }
.gl-empty p { margin:4px 0 0; font-size:13px; }
.gl-hint { font-size:11px; opacity:.35; display:flex; align-items:center; gap:5px; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="gl-hero bk-a1">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.branches.index') }}" class="text-decoration-none" style="color:rgba(255,255,255,.55);font-size:13px;">{{ __('Branches') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('company.branches.show', $branch) }}" class="text-decoration-none" style="color:rgba(255,255,255,.55);font-size:13px;">{{ $branch->localizedName() }}</a>
                        </li>
                        <li class="breadcrumb-item active" style="color:rgba(255,255,255,.4);font-size:13px;">{{ __('Gallery') }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">
                    <i data-feather="image" style="width:20px;height:20px;margin-inline-end:8px;"></i>{{ __('Branch Gallery') }}
                </h3>
                <p class="mb-0" style="color:rgba(255,255,255,.5);font-size:13px;">
                    {{ $placeImages->count() + $workImages->count() }} {{ __('total photos') }} &middot; {{ $branch->localizedName() }}
                </p>
            </div>
            <a href="{{ route('company.branches.show', $branch) }}"
               class="btn btn-sm rounded-pill px-3"
               style="background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.2);font-weight:600;font-size:13px;">
                <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Back') }}</span>
            </a>
        </div>
    </div>

    @include('company.partials.flash')

    <div class="card border-0 bk-a2" style="border-radius:18px;">
        <div class="card-body p-4">

            <div class="gl-tabs">
                <button class="gl-tab-btn place active" onclick="switchTab('place')">
                    <i data-feather="map-pin" style="width:14px;height:14px;"></i>
                    {{ __('Place Photos') }}
                    <span class="tab-count" id="countPlace">{{ $placeImages->count() }}</span>
                </button>
                <button class="gl-tab-btn work" onclick="switchTab('work')">
                    <i data-feather="scissors" style="width:14px;height:14px;"></i>
                    {{ __('Work Samples') }}
                    <span class="tab-count" id="countWork">{{ $workImages->count() }}</span>
                </button>
            </div>

            {{-- PLACE --}}
            <div class="gl-panel active" id="panel-place">
                <div class="gl-drop-zone place" id="dz-place">
                    <div class="dz-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4facfe" stroke-width="1.4">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <p class="fw-bold mb-1" style="color:#4facfe;">{{ __('Drag and drop place photos') }}</p>
                    <p class="mb-2" style="opacity:.5;">{{ __('Interior, exterior, reception...') }}</p>
                    <small>{{ __('JPG, PNG, WEBP — max 20 MB — up to 20 files — converted to WebP') }}</small>
                    <input type="file" id="fi-place" multiple accept="image/*" style="display:none;">
                </div>
                <div class="gl-upload-progress" id="prog-place">
                    <div class="d-flex justify-content-between mb-1" style="font-size:11px;opacity:.5;">
                        <span>{{ __('Uploading...') }}</span><span id="pct-place">0%</span>
                    </div>
                    <div class="progress"><div class="progress-bar" id="bar-place" style="width:0%;background:#4facfe;"></div></div>
                </div>
                <div id="grid-place" class="gl-grid">
                    @forelse($placeImages as $img)
                    <div class="gl-item" data-id="{{ $img->id }}" data-type="place" draggable="true">
                        <img src="{{ asset('storage/'.$img->path) }}" alt="">
                        <div class="gl-order-badge">{{ $loop->iteration }}</div>
                        <div class="gl-item-overlay">
                            <button class="gl-del-btn" data-id="{{ $img->id }}">
                                <i data-feather="trash-2" style="width:12px;height:12px;pointer-events:none;"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="gl-empty" id="empty-place">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#4facfe" stroke-width="1.3">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <p>{{ __('No place photos yet') }}</p>
                    </div>
                    @endforelse
                </div>
                @if($placeImages->isNotEmpty())
                <p class="gl-hint"><i data-feather="move" style="width:12px;height:12px;"></i>{{ __('Drag to reorder') }}</p>
                @endif
            </div>

            {{-- WORK --}}
            <div class="gl-panel" id="panel-work">
                <div class="gl-drop-zone work" id="dz-work">
                    <div class="dz-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f093fb" stroke-width="1.4">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <p class="fw-bold mb-1" style="color:#f093fb;">{{ __('Drag and drop work samples') }}</p>
                    <p class="mb-2" style="opacity:.5;">{{ __('Haircuts, nail art, results...') }}</p>
                    <small>{{ __('JPG, PNG, WEBP — max 20 MB — up to 20 files — converted to WebP') }}</small>
                    <input type="file" id="fi-work" multiple accept="image/*" style="display:none;">
                </div>
                <div class="gl-upload-progress" id="prog-work">
                    <div class="d-flex justify-content-between mb-1" style="font-size:11px;opacity:.5;">
                        <span>{{ __('Uploading...') }}</span><span id="pct-work">0%</span>
                    </div>
                    <div class="progress"><div class="progress-bar" id="bar-work" style="width:0%;background:#f093fb;"></div></div>
                </div>
                <div id="grid-work" class="gl-grid">
                    @forelse($workImages as $img)
                    <div class="gl-item" data-id="{{ $img->id }}" data-type="work" draggable="true">
                        <img src="{{ asset('storage/'.$img->path) }}" alt="">
                        <div class="gl-order-badge">{{ $loop->iteration }}</div>
                        <div class="gl-item-overlay">
                            <button class="gl-del-btn" data-id="{{ $img->id }}">
                                <i data-feather="trash-2" style="width:12px;height:12px;pointer-events:none;"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="gl-empty" id="empty-work">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#f093fb" stroke-width="1.3">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <p>{{ __('No work samples yet') }}</p>
                    </div>
                    @endforelse
                </div>
                @if($workImages->isNotEmpty())
                <p class="gl-hint"><i data-feather="move" style="width:12px;height:12px;"></i>{{ __('Drag to reorder') }}</p>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var UPLOAD_URL  = @json(route('company.branches.gallery.upload', $branch));
    var DELETE_BASE = @json(route('company.branches.gallery.delete', ['branch' => $branch->id, 'image' => 0]));
    var REORDER_URL = @json(route('company.branches.gallery.reorder', $branch));
    var CSRF        = @json(csrf_token());
    var I18N = {
        confirmDelete  : @json(__('Delete this photo?')),
        uploadFailed   : @json(__('Upload failed. Check file size / type.')),
        networkError   : @json(__('Network error.')),
        noPlacePhotos  : @json(__('No place photos yet')),
        noWorkPhotos   : @json(__('No work samples yet')),
    };

    window.switchTab = function(type) {
        document.querySelectorAll('.gl-tab-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.gl-panel').forEach(function(p){ p.classList.remove('active'); });
        document.querySelector('.gl-tab-btn.' + type).classList.add('active');
        document.getElementById('panel-' + type).classList.add('active');
    };

    ['place', 'work'].forEach(function(type) {
        var dz   = document.getElementById('dz-' + type);
        var fi   = document.getElementById('fi-' + type);
        var grid = document.getElementById('grid-' + type);

        dz.addEventListener('click', function(){ fi.click(); });
        fi.addEventListener('change', function(){ uploadFiles(fi.files, type); });
        dz.addEventListener('dragover',  function(e){ e.preventDefault(); dz.classList.add('drag-over'); });
        dz.addEventListener('dragleave', function(){ dz.classList.remove('drag-over'); });
        dz.addEventListener('drop', function(e){
            e.preventDefault(); dz.classList.remove('drag-over');
            uploadFiles(e.dataTransfer.files, type);
        });

        grid.addEventListener('click', function(e) {
            var btn = e.target.closest('.gl-del-btn');
            if (!btn) return;
            if (!confirm(I18N.confirmDelete)) return;
            var id  = btn.dataset.id;
            var url = DELETE_BASE.replace('/0', '/' + id);
            var item = btn.closest('.gl-item');
            fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (d.ok) {
                        item.remove();
                        updateBadges(grid);
                        updateCount(type);
                        if (!grid.querySelector('.gl-item')) showEmpty(type);
                    }
                });
        });

        var dragSrc = null;
        grid.addEventListener('dragstart', function(e){
            var item = e.target.closest('.gl-item');
            if (!item) return;
            dragSrc = item;
            setTimeout(function(){ item.classList.add('dragging'); }, 0);
        });
        grid.addEventListener('dragend', function(e){
            var item = e.target.closest('.gl-item');
            if (item) item.classList.remove('dragging');
            grid.querySelectorAll('.gl-item').forEach(function(i){ i.classList.remove('drag-target'); });
            saveOrder(grid);
        });
        grid.addEventListener('dragover', function(e){
            e.preventDefault();
            var target = e.target.closest('.gl-item');
            if (!target || target === dragSrc) return;
            grid.querySelectorAll('.gl-item').forEach(function(i){ i.classList.remove('drag-target'); });
            target.classList.add('drag-target');
            var rect = target.getBoundingClientRect();
            grid.insertBefore(dragSrc, e.clientX < rect.left + rect.width / 2 ? target : target.nextSibling);
        });
    });

    var MAX_BYTES = 20 * 1024 * 1024; // 20 MB

    function uploadFiles(files, type) {
        if (!files.length) return;
        var tooBig = Array.from(files).filter(function(f){ return f.size > MAX_BYTES; });
        if (tooBig.length) {
            alert(tooBig.map(function(f){ return f.name + ' — ' + (f.size / 1048576).toFixed(1) + ' MB'; }).join('\n') + '\n\n{{ __("Max file size is 20 MB.") }}');
            return;
        }
        var fd = new FormData();
        Array.from(files).forEach(function(f){ fd.append('images[]', f); });
        fd.append('type', type);
        fd.append('_token', CSRF);

        var prog = document.getElementById('prog-' + type);
        var bar  = document.getElementById('bar-' + type);
        var pct  = document.getElementById('pct-' + type);
        var grid = document.getElementById('grid-' + type);

        prog.style.display = 'block';
        bar.style.width = '0%'; pct.textContent = '0%';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', UPLOAD_URL);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var p = Math.round(e.loaded / e.total * 100);
                bar.style.width = p + '%'; pct.textContent = p + '%';
            }
        };
        xhr.onload = function() {
            prog.style.display = 'none';
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                removeEmpty(type);
                data.images.forEach(function(img){ grid.appendChild(makeItem(img.id, img.url, type)); });
                updateBadges(grid);
                updateCount(type);
                if (window.feather) feather.replace();
            } else {
                alert(I18N.uploadFailed);
            }
        };
        xhr.onerror = function(){ prog.style.display = 'none'; alert(I18N.networkError); };
        xhr.send(fd);
    }

    function makeItem(id, url, type) {
        var div = document.createElement('div');
        div.className = 'gl-item';
        div.dataset.id   = id;
        div.dataset.type = type;
        div.draggable    = true;
        div.innerHTML    = '<img src="' + url + '" alt="">'
            + '<div class="gl-order-badge">-</div>'
            + '<div class="gl-item-overlay"><button class="gl-del-btn" data-id="' + id + '">'
            + '<i data-feather="trash-2" style="width:12px;height:12px;pointer-events:none;"></i></button></div>';
        return div;
    }

    function updateBadges(grid) {
        grid.querySelectorAll('.gl-item').forEach(function(el, i){
            var b = el.querySelector('.gl-order-badge');
            if (b) b.textContent = i + 1;
        });
    }

    function updateCount(type) {
        var n = document.getElementById('grid-' + type).querySelectorAll('.gl-item').length;
        var key = 'count' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById(key).textContent = n;
    }

    function removeEmpty(type) {
        var e = document.getElementById('empty-' + type);
        if (e) e.remove();
    }

    function showEmpty(type) {
        var color = type === 'place' ? '#4facfe' : '#f093fb';
        var label = type === 'place' ? I18N.noPlacePhotos : I18N.noWorkPhotos;
        var div = document.createElement('div');
        div.id = 'empty-' + type;
        div.className = 'gl-empty';
        div.innerHTML = '<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><p>' + label + '</p>';
        document.getElementById('grid-' + type).appendChild(div);
    }

    function saveOrder(grid) {
        var order = Array.from(grid.querySelectorAll('.gl-item')).map(function(el){ return el.dataset.id; });
        updateBadges(grid);
        fetch(REORDER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ order: order })
        });
    }
})();
</script>
@endpush
