{{--
  Filter + Sort bar (search is handled by DataTables client-side).
  Variables:
    $sortField       – current sort field
    $sortDir         – 'asc' or 'desc'
    $sortOptions     – array of ['field'=>'...','label'=>'...']
    $extraFilters    – raw HTML string for extra <select> elements (optional)
    $extraFilterKeys – field names in $extraFilters, excluded from hidden preservation
    $dtTableId       – if set, DataTables search box will be injected beside the filters
                       (pass the table id so the JS can wire it up)
--}}
@php
    $sortField       = $sortField       ?? '';
    $sortDir         = $sortDir         ?? 'desc';
    $sortOptions     = $sortOptions     ?? [];
    $extraFilters    = $extraFilters    ?? '';
    $extraFilterKeys = $extraFilterKeys ?? [];
    $dtTableId       = $dtTableId       ?? '';

    $alwaysExclude = array_merge(['q','sort','dir','page','_token'], $extraFilterKeys);

    // Any server-side filter active?
    $hasFilter = collect($extraFilterKeys)->contains(fn($k) => request()->input($k, '') !== '');
@endphp

<div class="bk-ssb-wrap mb-3">

    {{-- DataTables search input (rendered here, wired to DT via JS below) --}}
    @if($dtTableId)
    <div class="bk-ssb-dt-search mb-2" id="bk-ssb-dts-wrap-{{ $dtTableId }}">
        <div class="bk-ssb-search-inner">
            <svg class="bk-ssb-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
                type="text"
                id="bk-dts-{{ $dtTableId }}"
                placeholder="{{ __('بحث فوري...') }}"
                autocomplete="off"
                class="bk-ssb-input"
            >
            <button type="button" class="bk-ssb-clear" id="bk-dts-clear-{{ $dtTableId }}" style="display:none" title="{{ __('Clear') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{-- Server-side filters form (sort + dropdowns) --}}
    <form method="GET" action="{{ url()->current() }}" id="bk-sf-form">

        @foreach(request()->except($alwaysExclude) as $key => $val)
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endforeach

        <div class="d-flex flex-wrap align-items-center gap-2">

            {{-- Sort field + direction --}}
            @if(!empty($sortOptions))
            <div class="d-flex align-items-center gap-1">
                <select name="sort" class="bk-ssb-select" onchange="document.getElementById('bk-sf-form').submit()">
                    @foreach($sortOptions as $opt)
                        <option value="{{ $opt['field'] }}" @selected($sortField === $opt['field'])>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                <button type="submit" name="dir" value="{{ $sortDir === 'asc' ? 'desc' : 'asc' }}"
                        class="bk-ssb-dir-btn" id="bk-sf-dir-btn"
                        title="{{ $sortDir === 'asc' ? __('تنازلي') : __('تصاعدي') }}">
                    @if($sortDir === 'asc')
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                    @endif
                </button>
                <input type="hidden" name="dir" value="{{ $sortDir }}" id="bk-sf-dir">
            </div>
            @endif

            {{-- Extra filters --}}
            @if($extraFilters)
                {!! $extraFilters !!}
            @endif

            {{-- Reset server filters --}}
            @if($hasFilter)
            <a href="{{ url()->current() }}" class="bk-ssb-reset">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                {{ __('مسح الفلاتر') }}
            </a>
            @endif

        </div>
    </form>
</div>

@once
@push('owner-styles')
<style>
/* ── Filter/Sort Bar ─────────────────────────────────────── */
.bk-ssb-wrap {
    background: var(--bk-card-bg, #1e2a3b);
    border: 1px solid var(--bk-border, rgba(255,255,255,.08));
    border-radius: 14px;
    padding: 12px 16px;
}
.bk-theme-light .bk-ssb-wrap {
    background: #fff;
    border-color: rgba(0,0,0,.07);
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
}

/* DT search input wrapper */
.bk-ssb-dt-search { max-width: 400px; }
.bk-ssb-search-inner {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 10px;
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
}
.bk-theme-light .bk-ssb-search-inner {
    background: #f5f6fa;
    border-color: rgba(0,0,0,.1);
}
.bk-ssb-search-inner:focus-within {
    border-color: #C9A227;
    box-shadow: 0 0 0 3px rgba(201,162,39,.15);
}
.bk-ssb-icon {
    position: absolute; left: 10px;
    width: 14px; height: 14px;
    color: rgba(255,255,255,.35); pointer-events: none; flex-shrink: 0;
}
.bk-theme-light .bk-ssb-icon { color: rgba(0,0,0,.3); }
.bk-ssb-input {
    width: 100%; padding: 9px 36px 9px 34px;
    background: transparent; border: none; outline: none;
    font-size: .85rem; color: inherit;
}
.bk-ssb-input::placeholder { color: rgba(255,255,255,.3); }
.bk-theme-light .bk-ssb-input::placeholder { color: rgba(0,0,0,.3); }

.bk-ssb-clear {
    position: absolute; right: 8px;
    background: none; border: none; padding: 0; cursor: pointer;
    display: flex; align-items: center;
    color: rgba(255,255,255,.4); width: 16px; height: 16px;
}
.bk-ssb-clear svg { width: 14px; height: 14px; }
.bk-theme-light .bk-ssb-clear { color: rgba(0,0,0,.3); }
.bk-ssb-clear:hover { color: #ef4444; }

/* Select */
.bk-ssb-select {
    height: 36px; padding: 0 28px 0 10px;
    font-size: .84rem; border-radius: 10px;
    border: 1px solid rgba(255,255,255,.12);
    outline: none; cursor: pointer; appearance: auto;
    background-color: rgba(255,255,255,.07); color: #e2e8f0;
}
.bk-ssb-select:focus { border-color: #C9A227; box-shadow: 0 0 0 3px rgba(201,162,39,.15); }
.bk-theme-light .bk-ssb-select { background-color: #f5f6fa; color: #1e293b; border-color: rgba(0,0,0,.12); }
.bk-ssb-select option { background-color: #1e2a3b; color: #e2e8f0; }
.bk-theme-light .bk-ssb-select option { background-color: #fff; color: #1e293b; }

/* Direction button */
.bk-ssb-dir-btn {
    width: 36px; height: 36px; padding: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 10px; border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.07); color: #e2e8f0;
    cursor: pointer; transition: background .15s; flex-shrink: 0;
}
.bk-ssb-dir-btn svg { width: 14px; height: 14px; pointer-events: none; }
.bk-ssb-dir-btn:hover { background: rgba(255,255,255,.13); }
.bk-theme-light .bk-ssb-dir-btn { background: #f5f6fa; border-color: rgba(0,0,0,.12); color: #1e293b; }
.bk-theme-light .bk-ssb-dir-btn:hover { background: #eef0f6; }

/* Date input */
.bk-ssb-date {
    height: 36px; padding: 0 10px; font-size: .84rem;
    border-radius: 10px; border: 1px solid rgba(255,255,255,.12);
    outline: none; background-color: rgba(255,255,255,.07); color: #e2e8f0; color-scheme: dark;
}
.bk-ssb-date:focus { border-color: #C9A227; box-shadow: 0 0 0 3px rgba(201,162,39,.15); }
.bk-theme-light .bk-ssb-date { background-color: #f5f6fa; color: #1e293b; border-color: rgba(0,0,0,.12); color-scheme: light; }

/* Reset */
.bk-ssb-reset {
    display: inline-flex; align-items: center; gap: 5px;
    height: 36px; padding: 0 14px; border-radius: 10px;
    border: 1px solid rgba(239,68,68,.35); background: rgba(239,68,68,.08);
    color: #f87171; font-size: .83rem; text-decoration: none; white-space: nowrap;
    transition: background .15s;
}
.bk-ssb-reset svg { width: 13px; height: 13px; }
.bk-ssb-reset:hover { background: rgba(239,68,68,.16); color: #ef4444; }
.bk-theme-light .bk-ssb-reset { color: #dc2626; background: rgba(239,68,68,.06); border-color: rgba(239,68,68,.25); }
</style>
@endpush

@push('scripts')
<script>
(function(){
    // Direction toggle: disable hidden dir input so only button value is sent
    var dirBtn    = document.getElementById('bk-sf-dir-btn');
    var dirHidden = document.getElementById('bk-sf-dir');
    if (dirBtn && dirHidden) {
        dirBtn.addEventListener('click', function(){ dirHidden.disabled = true; });
    }
})();
</script>
@endpush
@endonce
