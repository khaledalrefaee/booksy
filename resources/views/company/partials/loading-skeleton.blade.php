{{--
  Loading Skeleton — shows animated placeholders until page content loads.
  Include in dashboard.blade.php. Auto-hides when DOM is ready.
--}}
<style>
#bk-skeleton-overlay {
    position: fixed;
    inset: 0;
    z-index: 9998;
    background: var(--bs-body-bg, #1a1a2e);
    display: flex;
    transition: opacity .3s ease;
}
#bk-skeleton-overlay.hiding {
    opacity: 0;
    pointer-events: none;
}

.sk-sidebar {
    width: 240px;
    background: rgba(255,255,255,.03);
    padding: 20px 16px;
    flex-shrink: 0;
}
.sk-main {
    flex: 1;
    padding: 24px 30px;
    overflow: hidden;
}

.sk-line {
    border-radius: 8px;
    background: linear-gradient(90deg, rgba(255,255,255,.04) 25%, rgba(255,255,255,.08) 50%, rgba(255,255,255,.04) 75%);
    background-size: 200% 100%;
    animation: sk-shimmer 1.5s ease-in-out infinite;
}

/* Light theme */
[data-bk-theme="light"] .sk-line {
    background: linear-gradient(90deg, rgba(0,0,0,.06) 25%, rgba(0,0,0,.1) 50%, rgba(0,0,0,.06) 75%);
    background-size: 200% 100%;
}
[data-bk-theme="light"] #bk-skeleton-overlay {
    background: #f8f9fa;
}
[data-bk-theme="light"] .sk-sidebar {
    background: rgba(0,0,0,.03);
}

@keyframes sk-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.sk-logo { width: 120px; height: 32px; margin-bottom: 30px; border-radius: 6px; }
.sk-nav-item { height: 14px; margin-bottom: 18px; border-radius: 6px; }
.sk-hero { height: 100px; border-radius: 20px; margin-bottom: 24px; }
.sk-filter { height: 36px; border-radius: 8px; margin-bottom: 20px; max-width: 500px; }
.sk-card-row { display: flex; gap: 16px; flex-wrap: wrap; }
.sk-card {
    width: calc(25% - 12px);
    min-width: 200px;
    height: 200px;
    border-radius: 14px;
}
.sk-table-row { height: 18px; margin-bottom: 14px; border-radius: 6px; }

@media (max-width: 768px) {
    .sk-sidebar { display: none; }
    .sk-card { width: calc(50% - 8px); }
}
</style>

<div id="bk-skeleton-overlay">
    {{-- Sidebar skeleton --}}
    <div class="sk-sidebar">
        <div class="sk-line sk-logo"></div>
        <div class="sk-line sk-nav-item" style="width:80%;"></div>
        <div class="sk-line sk-nav-item" style="width:65%;"></div>
        <div class="sk-line sk-nav-item" style="width:90%;"></div>
        <div class="sk-line sk-nav-item" style="width:55%;"></div>
        <div class="sk-line sk-nav-item" style="width:70%;"></div>
        <div style="height:20px;"></div>
        <div class="sk-line sk-nav-item" style="width:50%; opacity:.5;"></div>
        <div class="sk-line sk-nav-item" style="width:85%;"></div>
        <div class="sk-line sk-nav-item" style="width:60%;"></div>
        <div class="sk-line sk-nav-item" style="width:75%;"></div>
    </div>

    {{-- Main content skeleton --}}
    <div class="sk-main">
        {{-- Navbar --}}
        <div class="d-flex justify-content-between mb-4">
            <div class="sk-line" style="width:180px; height:24px;"></div>
            <div class="d-flex gap-2">
                <div class="sk-line" style="width:36px; height:36px; border-radius:50%;"></div>
                <div class="sk-line" style="width:36px; height:36px; border-radius:50%;"></div>
            </div>
        </div>

        {{-- Hero banner --}}
        <div class="sk-line sk-hero"></div>

        {{-- Filter bar --}}
        <div class="sk-line sk-filter"></div>

        {{-- Cards --}}
        <div class="sk-card-row">
            <div class="sk-line sk-card"></div>
            <div class="sk-line sk-card"></div>
            <div class="sk-line sk-card"></div>
            <div class="sk-line sk-card"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sk = document.getElementById('bk-skeleton-overlay');
    if (sk) {
        sk.classList.add('hiding');
        setTimeout(function() { sk.remove(); }, 350);
    }
});
</script>
