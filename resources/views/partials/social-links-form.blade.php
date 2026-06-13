{{--
    Social Links Form — professional single-column rows
    Props:
      $savedLinks       Collection<SocialLink> keyed by platform  (empty collection for create)
      $inputPrefix      string  default 'social_links'
      $allowedPlatforms array|null  null = show all
--}}
@php
    $savedLinks       ??= collect();
    $inputPrefix      ??= 'social_links';
    $allowedPlatforms ??= null;
    $allPlatforms      = \App\Models\SocialLink::$platforms;
    $platforms         = $allowedPlatforms
        ? array_intersect_key($allPlatforms, array_flip($allowedPlatforms))
        : $allPlatforms;
@endphp

@once
@push('company-styles')
<style>
/* ── Social Links Form ── */
.sl-rows { display: flex; flex-direction: column; }

.sl-row {
    display: flex; align-items: center; gap: 0;
    border-bottom: 1px solid rgba(255,255,255,.06);
    transition: background .18s;
    position: relative;
}
.bk-theme-light .sl-row { border-bottom-color: rgba(0,0,0,.06); }
.sl-row:last-child { border-bottom: none; }
.sl-row:focus-within { background: rgba(255,255,255,.03); }
.bk-theme-light .sl-row:focus-within { background: rgba(0,0,0,.015); }

/* Left accent bar — lights up on focus or when filled */
.sl-row::before {
    content: '';
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--sl-color, #6366f1);
    border-radius: 0 2px 2px 0;
    opacity: 0;
    transition: opacity .2s;
}
.sl-row:focus-within::before,
.sl-row.sl-has-value::before { opacity: 1; }

/* Platform icon block */
.sl-platform-icon {
    flex-shrink: 0;
    width: 56px; height: 56px;
    display: flex; align-items: center; justify-content: center;
}
.sl-platform-icon-inner {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    transition: transform .18s;
}
.sl-row:focus-within .sl-platform-icon-inner,
.sl-row.sl-has-value .sl-platform-icon-inner { transform: scale(1.08); }

/* Content area */
.sl-content {
    flex: 1; min-width: 0;
    padding: 10px 14px 10px 0;
    display: flex; flex-direction: column; gap: 3px;
}

.sl-platform-name {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: rgba(255,255,255,.4);
    line-height: 1;
    transition: color .18s;
}
.bk-theme-light .sl-platform-name { color: rgba(0,0,0,.4); }
.sl-row:focus-within .sl-platform-name,
.sl-row.sl-has-value .sl-platform-name { color: var(--sl-color); }

.sl-input-row {
    display: flex; align-items: center; gap: 0;
}
.sl-url-prefix {
    font-size: 12px; font-weight: 500;
    color: rgba(255,255,255,.25);
    white-space: nowrap; flex-shrink: 0;
    user-select: none; pointer-events: none;
    transition: color .18s;
}
.bk-theme-light .sl-url-prefix { color: rgba(0,0,0,.3); }
.sl-row:focus-within .sl-url-prefix { color: rgba(255,255,255,.45); }
.bk-theme-light .sl-row:focus-within .sl-url-prefix { color: rgba(0,0,0,.5); }

.sl-field {
    flex: 1; min-width: 0;
    background: transparent; border: none; outline: none;
    font-size: 13px; font-weight: 500;
    color: var(--bs-body-color);
    padding: 0;
    caret-color: var(--sl-color);
}
.sl-field::placeholder {
    color: rgba(255,255,255,.18);
    font-weight: 400;
}
.bk-theme-light .sl-field::placeholder { color: rgba(0,0,0,.25); }

/* Status dot — shown when filled */
.sl-status {
    flex-shrink: 0;
    width: 44px; height: 56px;
    display: flex; align-items: center; justify-content: center;
}
.sl-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--sl-color);
    opacity: 0;
    transition: opacity .2s, transform .2s;
    transform: scale(.5);
}
.sl-row.sl-has-value .sl-dot { opacity: 1; transform: scale(1); }
</style>
@endpush
@endonce

<div class="sl-rows">
    @foreach ($platforms as $key => $meta)
    @php
        $saved   = $savedLinks->get($key);
        $stored  = old("{$inputPrefix}.{$key}") ?? ($saved?->url ?? '');
        $handle  = $stored ? \App\Models\SocialLink::extractHandle($key, $stored) : '';
        $isUrl   = $meta['input_type'] === 'url';
        $isPhone = $meta['input_type'] === 'phone';
        $prefix  = match($key) {
            'whatsapp'  => 'wa.me/ ',
            'instagram' => 'instagram.com/ ',
            'facebook'  => 'facebook.com/ ',

            'linkedin'  => 'linkedin.com/in/ ',
            default     => '',
        };
        $hasValue = $handle !== '';
    @endphp

    <div class="sl-row {{ $hasValue ? 'sl-has-value' : '' }}"
         style="--sl-color: {{ $meta['color'] }}"
         data-platform="{{ $key }}">

        {{-- Icon --}}
        <div class="sl-platform-icon">
            <div class="sl-platform-icon-inner" style="background:{{ $meta['color'] }}1a;">
                @if($key === 'whatsapp')
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $meta['color'] }}">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.115 1.516 5.842L0 24l6.316-1.496A11.96 11.96 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.79 9.79 0 01-4.988-1.367l-.357-.213-3.748.888.906-3.647-.233-.374A9.818 9.818 0 012.182 12C2.182 6.58 6.58 2.182 12 2.182S21.818 6.58 21.818 12 17.42 21.818 12 21.818z"/>
                    </svg>
                @elseif($key === 'instagram')
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $meta['color'] }}">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                    </svg>
                @elseif($key === 'facebook')
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $meta['color'] }}">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                
                @elseif($key === 'linkedin')
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $meta['color'] }}">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                
                @endif
            </div>
        </div>

        {{-- Content --}}
        <div class="sl-content">
            <div class="sl-platform-name">{{ $meta['label'] }}</div>
            <div class="sl-input-row">
                @if(!$isUrl)
                    <span class="sl-url-prefix">{{ $prefix }}</span>
                @endif
                <input
                    type="{{ $isPhone ? 'tel' : ($isUrl ? 'url' : 'text') }}"
                    name="{{ $inputPrefix }}[{{ $key }}]"
                    class="sl-field"
                    value="{{ $handle }}"
                    placeholder="{{ $meta['placeholder'] }}"
                    dir="ltr"
                    autocomplete="off"
                    data-sl-row>
            </div>
        </div>

        {{-- Filled indicator --}}
        <div class="sl-status">
            <div class="sl-dot"></div>
        </div>

    </div>
    @endforeach
</div>

@once
@push('scripts')
<script>
(function () {
    function initSocialRows(root) {
        root = root || document;
        root.querySelectorAll('[data-sl-row]').forEach(function (input) {
            var row = input.closest('.sl-row');
            if (!row) return;
            function update() {
                row.classList.toggle('sl-has-value', input.value.trim() !== '');
            }
            input.addEventListener('input', update);
            update();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initSocialRows(); });
    } else {
        initSocialRows();
    }
    window.initSocialRows = initSocialRows;
})();
</script>
@endpush
@endonce
