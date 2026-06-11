{{--
    Social Links Form Section
    -------------------------
    Props:
      $savedLinks  — Collection<SocialLink> keyed by 'platform'  (pass empty collection for create)
      $inputPrefix — string, default 'social_links'              (use 'employees[0][social_links]' for wizard)
      $accentColor — string hex, default '#6366f1'
--}}
@php
    $savedLinks   ??= collect();
    $inputPrefix  ??= 'social_links';
    $accentColor  ??= '#6366f1';
    $platforms = \App\Models\SocialLink::$platforms;
@endphp

@once
<style>
.sl-section-header {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px 13px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.bk-theme-light .sl-section-header { border-bottom-color: rgba(0,0,0,.07); }
.sl-section-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sl-section-title { font-weight: 700; font-size: 13px; }
.sl-section-sub   { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 1px; }
.bk-theme-light .sl-section-sub { color: rgba(0,0,0,.45); }

.sl-row {
    display: flex; align-items: center; gap: 10px; padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,.04);
}
.bk-theme-light .sl-row { border-bottom-color: rgba(0,0,0,.04); }
.sl-row:last-child { border-bottom: none; padding-bottom: 0; }

.sl-icon-wrap {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.sl-label { width: 90px; font-size: 12px; font-weight: 600; flex-shrink: 0; }

.sl-input {
    flex: 1; background: rgba(255,255,255,.04); border: 1.5px solid rgba(255,255,255,.08);
    border-radius: 9px; padding: 7px 11px; font-size: 12px; color: inherit;
    transition: border-color .18s, background .18s; outline: none;
}
.sl-input::placeholder { color: rgba(255,255,255,.2); font-size: 11px; }
.sl-input:focus { border-color: var(--sl-accent, #6366f1); background: rgba(99,102,241,.05); }
.sl-input:not(:placeholder-shown) { border-color: rgba(255,255,255,.18); }
.bk-theme-light .sl-input { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .sl-input::placeholder { color: rgba(0,0,0,.25); }
.bk-theme-light .sl-input:focus { background: #fff; border-color: var(--sl-accent, #6366f1); box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.bk-theme-light .sl-input:not(:placeholder-shown) { border-color: #ced4da; }
</style>
@endonce

<div style="padding: 18px 20px; --sl-accent: {{ $accentColor }};">
    <div class="d-flex flex-column" style="gap: 2px;">
        @foreach($platforms as $key => $meta)
        @php
            $saved = $savedLinks->get($key);
            $oldVal = old("{$inputPrefix}.{$key}") ?? ($saved?->url ?? '');
        @endphp
        <div class="sl-row">
            {{-- Color dot / icon --}}
            <div class="sl-icon-wrap" style="background:{{ $meta['color'] }}22;">
                @if($key === 'whatsapp')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] }}"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.115 1.516 5.842L0 24l6.316-1.496A11.96 11.96 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.79 9.79 0 01-4.988-1.367l-.357-.213-3.748.888.906-3.647-.233-.374A9.818 9.818 0 012.182 12C2.182 6.58 6.58 2.182 12 2.182S21.818 6.58 21.818 12 17.42 21.818 12 21.818z"/></svg>
                @elseif($key === 'instagram')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] }}"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                @elseif($key === 'facebook')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] }}"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                @elseif($key === 'twitter')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] === '#000000' ? 'currentColor' : $meta['color'] }}" style="opacity:.8;"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                @elseif($key === 'tiktok')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="opacity:.7;"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.94a8.22 8.22 0 004.83 1.56V7.07a4.86 4.86 0 01-1.06-.38z"/></svg>
                @elseif($key === 'snapchat')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] }}" style="filter:drop-shadow(0 0 1px rgba(0,0,0,.3));"><path d="M12.166.5c-.208 0-2.914.072-4.4 2.647-.527.915-.447 2.49-.403 3.494.016.353.03.663.022.913-.344.154-.743.258-1.153.258-.484 0-.868-.16-1.143-.474-.077-.087-.183-.132-.293-.132-.224 0-.407.193-.407.43 0 .163.083.307.21.386.053.033 1.32.82 1.32 2.29 0 .28-.042.534-.12.756-.478 1.354-2.12 2.26-3.342 2.74-.26.1-.428.35-.428.624 0 .097.02.19.057.275.133.302.44.504.786.504.106 0 .213-.02.315-.063.22-.087.453-.14.693-.14.32 0 .59.098.794.27.397.345.636 1.028.636 1.028.218.645.656 1.022 1.27 1.022.322 0 .64-.1.95-.3.535-.34 1.157-.515 1.797-.515.553 0 1.09.15 1.56.433.297.18.607.274.922.274.64 0 1.09-.387 1.286-1.005 0 0 .24-.67.637-1.015.204-.175.47-.27.793-.27.24 0 .473.053.693.14.1.042.208.063.314.063.35 0 .654-.2.787-.5.037-.087.057-.18.057-.276 0-.274-.168-.523-.427-.624-1.22-.48-2.864-1.386-3.343-2.74-.078-.22-.12-.475-.12-.756 0-1.47 1.268-2.257 1.32-2.29.128-.08.21-.223.21-.386 0-.237-.183-.43-.407-.43-.11 0-.216.045-.293.132-.275.314-.66.474-1.143.474-.41 0-.81-.104-1.153-.258-.008-.25.006-.56.022-.913.044-1.004.124-2.579-.403-3.494C15.08.572 12.374.5 12.166.5z"/></svg>
                @elseif($key === 'youtube')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] }}"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
                @elseif($key === 'linkedin')
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $meta['color'] }}"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                @else
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $meta['color'] }}" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                @endif
            </div>
            <span class="sl-label">{{ $meta['label'] }}</span>
            <input type="url" name="{{ $inputPrefix }}[{{ $key }}]"
                   class="sl-input"
                   value="{{ $oldVal }}"
                   placeholder="{{ $meta['placeholder'] }}"
                   autocomplete="off">
        </div>
        @endforeach
    </div>
</div>
