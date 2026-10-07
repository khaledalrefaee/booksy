@props(['title', 'subtitle' => null, 'tabs' => [], 'active' => null, 'actions' => null, 'navLabel' => null])

{{--
    Generic page header used by multi-page modules (Inventory, Reports …):
    title + page actions, then one section nav so sibling pages read as a single
    module. Token-driven (--bk-*), RTL-safe, both themes. Class prefix `ivh-*`
    is shared with the module wrappers (x-inventory.head, x-reports.head).

        <x-section-head :title="..." :tabs="[key => ['href'=>..,'icon'=>..,'label'=>..]]" active="key">
            <x-slot:actions> ... </x-slot:actions>
        </x-section-head>
--}}

@once
@push('company-styles')
<style>
.ivh { margin-bottom:22px; }
.ivh-top { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
.ivh-title { font-family:var(--bk-serif); font-size:2rem; font-weight:600; letter-spacing:-.01em; line-height:1.15; color:var(--bk-text); margin:0; }
.ivh-sub { margin:6px 0 0; color:var(--bk-text-muted); font-size:.92rem; line-height:1.5; }
.ivh-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

.ivh-nav { display:flex; gap:4px; overflow-x:auto; scrollbar-width:none; border-bottom:1px solid var(--bk-border); }
.ivh-nav::-webkit-scrollbar { display:none; }
.ivh-link { position:relative; flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; padding:11px 14px; margin-bottom:-1px;
    color:var(--bk-text-muted); font-size:.9rem; font-weight:600; text-decoration:none; border-bottom:2px solid transparent;
    transition:color .15s, border-color .15s, background .15s; border-radius:8px 8px 0 0; }
.ivh-link svg { width:16px; height:16px; stroke-width:2; }
.ivh-link:hover { color:var(--bk-text); background:var(--bk-surface-2); }
.ivh-link.is-on { color:var(--bk-accent); border-bottom-color:var(--bk-accent); }
.ivh-link:focus-visible { outline:2px solid var(--bk-accent); outline-offset:-2px; }

.ivh-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:42px; padding:0 18px; border-radius:10px;
    border:1px solid transparent; font-size:.88rem; font-weight:600; text-decoration:none; cursor:pointer; white-space:nowrap;
    transition:background .15s, border-color .15s, color .15s; }
.ivh-btn svg { width:16px; height:16px; stroke-width:2.2; }
.ivh-btn-primary { background:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.ivh-btn-primary:hover { background:var(--bk-accent-hover); color:var(--bk-accent-ink); }
.ivh-btn-ghost { background:var(--bk-surface); color:var(--bk-text); border-color:var(--bk-border); }
.ivh-btn-ghost:hover { border-color:var(--bk-accent); color:var(--bk-accent); }
.ivh-btn:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }

@media (max-width:767.98px) {
    .ivh-title { font-size:1.6rem; }
    .ivh-actions { width:100%; }
    .ivh-actions > * { flex:1 1 auto; }
}
@media (prefers-reduced-motion:reduce) { .ivh-link, .ivh-btn { transition:none; } }
</style>
@endpush
@endonce

<header class="ivh">
    <div class="ivh-top">
        <div>
            <h1 class="ivh-title">{{ $title }}</h1>
            @if($subtitle)<p class="ivh-sub">{{ $subtitle }}</p>@endif
        </div>
        @if($actions)<div class="ivh-actions">{{ $actions }}</div>@endif
    </div>

    @if(count($tabs))
    <nav class="ivh-nav" @if($navLabel) aria-label="{{ $navLabel }}" @endif>
        @foreach($tabs as $key => $t)
            <a href="{{ $t['href'] }}" class="ivh-link {{ $active === $key ? 'is-on' : '' }}" @if($active === $key) aria-current="page" @endif>
                <i data-feather="{{ $t['icon'] }}" aria-hidden="true"></i>{{ $t['label'] }}
            </a>
        @endforeach
    </nav>
    @endif
</header>
