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
                @include('partials.social-icon', ['platform' => $key, 'size' => 18, 'color' => $meta['color']])
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
