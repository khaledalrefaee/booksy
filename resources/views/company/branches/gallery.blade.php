@extends('company.dashboard')

@php
    $suggestedMin = (int) config('gallery.suggested_min', 2);
    $maxPerType   = (int) config('gallery.max_per_type', 20);
    $approvedPlace = $placeImages->where('status', 'approved')->count();
    $approvedWork  = $workImages->where('status', 'approved')->count();
    // Non-rejected photos count toward the per-type cap.
    $activePlace = $placeImages->where('status', '!=', 'rejected')->count();
    $activeWork  = $workImages->where('status', '!=', 'rejected')->count();
    $totalPhotos   = $placeImages->count() + $workImages->count();

    // Client-side messages for files the server rejected on upload.
    $pgUploadReasons = [
        'duplicate'   => __('This photo is already in this branch.'),
        'corrupt'     => __('The file is not a valid image.'),
        'unsupported' => __('Unsupported image format.'),
        'low_res'     => __('The image resolution is too low.'),
        'limit'       => __('The :max-photo limit for this section is reached.', ['max' => $maxPerType]),
    ];
@endphp

@push('company-styles')
<style>
/* ── Branch photos ─ built on the bk-* token system, tuned by hand ───────── */
.pg { max-width: 760px; margin-inline: auto; }
.pg ::selection { background: var(--bk-accent-wash); color: var(--bk-accent); }
.pg :focus-visible { outline: 2px solid var(--bk-accent); outline-offset: 2px; border-radius: 4px; }

/* Top bar */
.pg-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 22px; }
.pg-back { display: inline-flex; align-items: center; gap: 6px; color: var(--bk-text-muted); font-size: .82rem; font-weight: 600; text-decoration: none; }
.pg-back:hover { color: var(--bk-text); }

/* Intro */
.pg-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; color: var(--bk-text); margin: 0 0 6px; }
.pg-lede { color: var(--bk-text-soft); font-size: .92rem; line-height: 1.6; margin: 0; max-width: 52ch; }

/* Primary action */
.pg-cta-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin: 22px 0 6px; }
.pg-cta {
    display: inline-flex; align-items: center; gap: 9px; padding: 13px 22px; border: none; cursor: pointer;
    background: var(--bk-accent); color: var(--bk-accent-ink); border-radius: 12px;
    font-weight: 650; font-size: .92rem; box-shadow: 0 6px 18px -8px color-mix(in srgb, var(--bk-accent) 70%, transparent);
    transition: background .16s, transform .12s, box-shadow .16s;
}
.pg-cta:hover { background: var(--bk-accent-hover); box-shadow: 0 10px 24px -8px color-mix(in srgb, var(--bk-accent) 65%, transparent); }
.pg-cta:active { transform: translateY(1px); }
.pg-cta-note { color: var(--bk-text-muted); font-size: .82rem; line-height: 1.5; }
@media (max-width: 520px) {
    .pg-cta { width: 100%; justify-content: center; padding: 15px; }
    .pg-cta-note { width: 100%; }
}

/* Tips — quiet, foldable, not a card */
.pg-tips { margin-top: 26px; border-top: 1px solid var(--bk-border); }
.pg-tips > summary {
    list-style: none; cursor: pointer; padding: 15px 2px; display: flex; align-items: center; gap: 9px;
    font-weight: 650; font-size: .9rem; color: var(--bk-text);
}
.pg-tips > summary::-webkit-details-marker { display: none; }
.pg-tips > summary .pg-chev { margin-inline-start: auto; color: var(--bk-text-muted); transition: transform .2s; }
.pg-tips[open] > summary .pg-chev { transform: rotate(180deg); }
.pg-tips-body { padding: 2px 2px 18px; }
.pg-tips-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
.pg-tips-list li { display: flex; align-items: flex-start; gap: 10px; font-size: .86rem; color: var(--bk-text-soft); line-height: 1.5; }
.pg-tips-list li svg { color: var(--bk-accent); flex-shrink: 0; margin-top: 3px; }
.pg-tips-note { margin-top: 15px; font-size: .83rem; color: var(--bk-text-muted); line-height: 1.6; padding-inline-start: 12px; border-inline-start: 2px solid var(--bk-gold); }

/* Section */
.pg-sec { margin-top: 30px; }
.pg-sec-head { display: flex; align-items: baseline; gap: 10px; margin-bottom: 4px; }
.pg-sec-head h2 { font-size: 1.02rem; font-weight: 700; color: var(--bk-text); margin: 0; letter-spacing: -.01em; }
.pg-sec-head .pg-count { font-size: .8rem; font-weight: 600; color: var(--bk-text-muted); font-variant-numeric: tabular-nums; }
.pg-rule { height: 2px; border-radius: 2px; background: var(--bk-border); position: relative; margin-bottom: 16px; }
.pg-rule::before { content: ""; position: absolute; inset-block: 0; inset-inline-start: 0; width: 36px; border-radius: 2px; }
.pg-sec.place .pg-rule::before { background: var(--bk-accent); }
.pg-sec.work  .pg-rule::before { background: var(--bk-gold); }
.pg-nudge { display: flex; align-items: center; gap: 7px; font-size: .82rem; color: var(--bk-text-muted); margin: -6px 0 14px; }

/* Grid + photo tile */
/* One photo per row on phones (bigger, clearer); more columns as width grows. */
.pg-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
@media (min-width: 480px) { .pg-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 768px) { .pg-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .pg-grid { grid-template-columns: repeat(4, 1fr); } }

.pg-item {
    position: relative; aspect-ratio: 1/1; border-radius: 12px; overflow: hidden;
    background: var(--bk-surface-2); box-shadow: inset 0 0 0 1px var(--bk-border);
}
.pg-item.is-cover { box-shadow: inset 0 0 0 2px var(--bk-gold); }
.pg-item.status-pending  { box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--bk-warning) 55%, var(--bk-border)); }
.pg-item.status-rejected { box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--bk-danger) 55%, var(--bk-border)); }
.pg-item > img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pg-item.status-rejected > img { opacity: .45; filter: grayscale(.5); }

.pg-badge {
    position: absolute; inset-block-start: 8px; inset-inline-start: 8px; z-index: 2;
    font-size: .66rem; font-weight: 700; padding: 3px 9px; border-radius: 999px;
    -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
}
.pg-badge-approved { background: color-mix(in srgb, var(--bk-success-bg) 88%, transparent); color: var(--bk-success); }
.pg-badge-pending  { background: color-mix(in srgb, var(--bk-warning-bg) 90%, transparent); color: var(--bk-warning); }
.pg-badge-rejected { background: color-mix(in srgb, var(--bk-danger-bg) 90%, transparent);  color: var(--bk-danger); }

.pg-marks { position: absolute; inset-block-start: 8px; inset-inline-end: 8px; z-index: 2; display: flex; gap: 5px; }
.pg-mark { width: 23px; height: 23px; border-radius: 7px; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 2px 6px -2px rgba(0,0,0,.4); }
.pg-mark-cover { background: var(--bk-gold-strong); }
.pg-mark-team  { background: var(--bk-accent); }

.pg-reason {
    position: absolute; inset-block-end: 0; inset-inline: 0; z-index: 2;
    background: linear-gradient(to top, rgba(20,18,10,.82), transparent);
    color: #fff; font-size: .72rem; line-height: 1.35; padding: 20px 9px 9px; font-weight: 600;
}

/* Small zoom affordance; the real actions live in the enlarged (lightbox) view. */
.pg-zoom {
    position: absolute; inset-block-end: 7px; inset-inline-end: 7px; z-index: 2;
    width: 24px; height: 24px; border-radius: 7px; display: flex; align-items: center; justify-content: center;
    background: rgba(20,18,10,.42); color: #fff; -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
    opacity: 0; transition: opacity .15s;
}
.pg-item:hover .pg-zoom { opacity: 1; }
@media (hover: none) { .pg-zoom { opacity: 1; } }

/* Add tile */
.pg-add-tile {
    aspect-ratio: 1/1; border-radius: 12px; cursor: pointer;
    background: var(--bk-surface); box-shadow: inset 0 0 0 1px var(--bk-border);
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 7px;
    color: var(--bk-text-muted); font-size: .8rem; font-weight: 600;
    transition: box-shadow .15s, color .15s, background .15s;
}
.pg-add-tile:hover { color: var(--bk-accent); box-shadow: inset 0 0 0 1.5px var(--bk-accent); background: var(--bk-accent-wash); }
.pg-add-tile:active { transform: translateY(1px); }
.pg-empty-line { grid-column: 1 / -1; color: var(--bk-text-muted); font-size: .82rem; padding: 2px 0 6px; }

/* ── Bottom sheet / dialog ───────────────────────────────────────────────── */
.pg-sheet-overlay {
    position: fixed; inset: 0; z-index: 1080; background: var(--bk-scrim);
    display: flex; align-items: flex-end; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity .22s;
    -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px);
}
.pg-sheet-overlay.open { opacity: 1; pointer-events: auto; }
.pg-sheet {
    width: 100%; max-width: 500px; background: var(--bk-surface);
    border-radius: 22px 22px 0 0; box-shadow: var(--bk-shadow-xl);
    padding: 8px 20px 24px; transform: translateY(100%);
    transition: transform .3s cubic-bezier(.22,.9,.28,1);
    max-height: 88vh; overflow-y: auto;
    scrollbar-width: thin; scrollbar-color: var(--bk-border-strong) transparent;
}
.pg-sheet::-webkit-scrollbar { width: 8px; }
.pg-sheet::-webkit-scrollbar-thumb { background: var(--bk-border-strong); border-radius: 999px; border: 2px solid var(--bk-surface); }
.pg-sheet-overlay.open .pg-sheet { transform: translateY(0); }
@media (min-width: 640px) {
    .pg-sheet-overlay { align-items: center; }
    .pg-sheet { border-radius: 18px; padding: 24px; transform: translateY(14px) scale(.985); }
    .pg-sheet-overlay.open .pg-sheet { transform: translateY(0) scale(1); }
}
.pg-grabber { width: 40px; height: 4px; border-radius: 999px; background: var(--bk-border-strong); margin: 6px auto 16px; }
@media (min-width: 640px) { .pg-grabber { display: none; } }
.pg-sheet-title { font-weight: 700; color: var(--bk-text); font-size: 1.08rem; margin-bottom: 5px; }
.pg-sheet-sub   { color: var(--bk-text-muted); font-size: .84rem; margin-bottom: 20px; line-height: 1.5; }

.pg-type-opt {
    display: flex; align-items: center; gap: 14px; width: 100%; text-align: start;
    padding: 15px; border-radius: 13px; margin-bottom: 11px; cursor: pointer;
    background: var(--bk-surface); box-shadow: inset 0 0 0 1px var(--bk-border);
    transition: box-shadow .15s, background .15s;
}
.pg-type-opt:hover { box-shadow: inset 0 0 0 1.5px var(--bk-accent); background: var(--bk-accent-wash); }
.pg-type-opt:active { transform: translateY(1px); }
.pg-type-ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pg-type-opt.place .pg-type-ic { background: var(--bk-accent-wash); color: var(--bk-accent); }
.pg-type-opt.work  .pg-type-ic { background: var(--bk-gold-soft);   color: var(--bk-gold-strong); }
.pg-type-name { font-weight: 700; color: var(--bk-text); font-size: .94rem; display: block; }
.pg-type-desc { color: var(--bk-text-muted); font-size: .79rem; line-height: 1.45; margin-top: 3px; display: block; }
.pg-type-arrow { margin-inline-start: auto; color: var(--bk-text-muted); flex-shrink: 0; }

/* Preview stage */
.pg-preview-head { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.pg-back-btn { background: none; border: none; cursor: pointer; color: var(--bk-text-muted); display: flex; align-items: center; padding: 4px; border-radius: 8px; }
.pg-back-btn:hover { color: var(--bk-text); }
.pg-preview-title { font-weight: 700; color: var(--bk-text); font-size: .96rem; }
.pg-preview-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; margin-bottom: 18px; }
@media (min-width: 480px) { .pg-preview-grid { grid-template-columns: repeat(4, 1fr); } }
.pg-prev-item { position: relative; aspect-ratio: 1/1; border-radius: 11px; overflow: hidden; background: var(--bk-surface-2); box-shadow: inset 0 0 0 1px var(--bk-border); }
.pg-prev-item img { width: 100%; height: 100%; object-fit: cover; }
.pg-prev-remove {
    position: absolute; inset-block-start: 5px; inset-inline-end: 5px; width: 22px; height: 22px; border-radius: 50%;
    background: rgba(20,18,10,.62); color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.pg-prev-add {
    aspect-ratio: 1/1; border-radius: 11px; box-shadow: inset 0 0 0 1px var(--bk-border-strong); background: var(--bk-surface);
    display: flex; align-items: center; justify-content: center; color: var(--bk-text-muted); cursor: pointer;
}
.pg-prev-add:hover { box-shadow: inset 0 0 0 1.5px var(--bk-accent); color: var(--bk-accent); }

.pg-progress { display: none; margin-bottom: 16px; }
.pg-progress .bar-track { height: 6px; border-radius: 999px; background: var(--bk-surface-2); overflow: hidden; }
.pg-progress .bar-fill { height: 100%; width: 100%; transform: scaleX(0); transform-origin: left center; background: var(--bk-accent); transition: transform .15s ease-out; }
[dir="rtl"] .pg-progress .bar-fill { transform-origin: right center; }
.pg-progress .bar-label { font-size: .78rem; color: var(--bk-text-muted); margin-bottom: 7px; display: flex; justify-content: space-between; font-variant-numeric: tabular-nums; }

.pg-send-btn {
    width: 100%; padding: 14px; border-radius: 12px; border: none; cursor: pointer;
    background: var(--bk-accent); color: var(--bk-accent-ink); font-weight: 650; font-size: .94rem;
    display: flex; align-items: center; justify-content: center; gap: 8px; transition: background .15s;
}
.pg-send-btn:hover { background: var(--bk-accent-hover); }
.pg-send-btn:active { transform: translateY(1px); }
.pg-send-btn:disabled { opacity: .55; cursor: default; }
.pg-cancel-btn { width: 100%; padding: 12px; margin-top: 10px; border-radius: 12px; border: none; cursor: pointer; background: var(--bk-surface-2); color: var(--bk-danger); font-weight: 650; font-size: .9rem; display: flex; align-items: center; justify-content: center; gap: 8px; }
.pg-cancel-btn:hover { background: var(--bk-danger-bg); }

/* Tapping a photo enlarges it; on touch devices the action bar stays visible. */
.pg-item > img { cursor: zoom-in; }
@media (hover: none) { .pg-item .pg-actions { opacity: 1; transform: none; } }

/* Lightbox */
.pg-lightbox { position: fixed; inset: 0; z-index: 1100; background: rgba(12,10,6,.92); display: none; align-items: center; justify-content: center; padding: 20px; -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
.pg-lightbox.open { display: flex; }
.pg-lb-inner { display: flex; flex-direction: column; align-items: center; gap: 16px; max-width: 96vw; }
.pg-lightbox img { max-width: 96vw; max-height: 74vh; border-radius: 14px; box-shadow: 0 24px 80px rgba(0,0,0,.6); object-fit: contain; }
.pg-lightbox-close { position: absolute; inset-block-start: 16px; inset-inline-end: 16px; width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer; background: rgba(255,255,255,.14); color: #fff; display: flex; align-items: center; justify-content: center; z-index: 2; }
.pg-lightbox-close:hover { background: rgba(255,255,255,.24); }
.pg-lb-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
.pg-lb-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 999px; border: none; cursor: pointer;
    background: rgba(255,255,255,.94); color: #22251D; font-weight: 650; font-size: .88rem; transition: transform .12s, filter .12s;
}
.pg-lb-btn:hover { transform: translateY(-1px); }
.pg-lb-btn:active { transform: translateY(0) scale(.97); }
.pg-lb-btn i { width: 16px; height: 16px; }
.pg-lb-btn.pg-lb-danger { background: var(--bk-danger); color: #fff; }
.pg-lb-btn.pg-lb-accent { background: var(--bk-accent); color: var(--bk-accent-ink); }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="pg">

        <div class="pg-top">
            <a href="{{ route('company.branches.show', $branch) }}" class="pg-back">
                <i data-feather="arrow-right" class="pg-arrow-rtl" style="width:15px;height:15px;"></i>
                {{ $branch->localizedName() }}
            </a>
        </div>

        <h1 class="pg-title">{{ __('Branch photos') }}</h1>
        <p class="pg-lede">{{ __('Add clear, real photos of your place so customers recognise it before visiting.') }}</p>

        @include('company.partials.flash')

        <div class="pg-cta-row">
            <button type="button" class="pg-cta" id="pg-open-sheet">
                <i data-feather="plus" style="width:17px;height:17px;"></i>
                {{ __('Add photos') }}
            </button>
            <span class="pg-cta-note">{{ __('Two or three clear photos are enough to start. You can add more later.') }}</span>
        </div>

        <details class="pg-tips">
            <summary>
                {{ __('Photography tips') }}
                <i data-feather="chevron-down" class="pg-chev" style="width:17px;height:17px;"></i>
            </summary>
            <div class="pg-tips-body">
                <ul class="pg-tips-list">
                    <li><i data-feather="check" style="width:15px;height:15px;"></i>{{ __('Use clear, good-quality photos.') }}</li>
                    <li><i data-feather="check" style="width:15px;height:15px;"></i>{{ __('Make sure the lighting is good.') }}</li>
                    <li><i data-feather="check" style="width:15px;height:15px;"></i>{{ __('Shoot the place from a clear angle and keep it straight.') }}</li>
                    <li><i data-feather="check" style="width:15px;height:15px;"></i>{{ __('Tidy up the place before taking the photo.') }}</li>
                    <li><i data-feather="check" style="width:15px;height:15px;"></i>{{ __('Photograph the place, not yourself.') }}</li>
                </ul>
                <p class="pg-tips-note">{{ __('Avoid selfies, screenshots and photos taken from the internet. A person in the photo is fine when the place or the work is the main subject.') }}</p>
            </div>
        </details>

        {{-- Place --}}
        <section class="pg-sec place">
            <div class="pg-sec-head">
                <h2>{{ __('Place photos') }}</h2>
                <span class="pg-count" id="pg-count-place">{{ $placeImages->count() }}</span>
            </div>
            <div class="pg-rule"></div>
            @if($approvedPlace > 0 && $approvedPlace < $suggestedMin)
                <p class="pg-nudge"><i data-feather="info" style="width:13px;height:13px;"></i>{{ __('Adding more photos can help customers recognise your place.') }}</p>
            @endif
            <div class="pg-grid" id="pg-grid-place" data-type="place">
                @foreach($placeImages as $img)
                    @include('company.branches.partials.gallery-item', ['img' => $img])
                @endforeach
                <div class="pg-add-tile js-add-tile" data-type="place">
                    <i data-feather="plus" style="width:20px;height:20px;"></i>
                    {{ __('Add') }}
                </div>
            </div>
        </section>

        {{-- Work --}}
        <section class="pg-sec work">
            <div class="pg-sec-head">
                <h2>{{ __('Work photos') }}</h2>
                <span class="pg-count" id="pg-count-work">{{ $workImages->count() }}</span>
            </div>
            <div class="pg-rule"></div>
            <div class="pg-grid" id="pg-grid-work" data-type="work">
                @foreach($workImages as $img)
                    @include('company.branches.partials.gallery-item', ['img' => $img])
                @endforeach
                <div class="pg-add-tile js-add-tile" data-type="work">
                    <i data-feather="plus" style="width:20px;height:20px;"></i>
                    {{ __('Add') }}
                </div>
            </div>
        </section>

    </div>
</div>

{{-- ── Bottom sheet ─────────────────────────────────────────────────────── --}}
<div class="pg-sheet-overlay" id="pg-sheet-overlay">
    <div class="pg-sheet" role="dialog" aria-modal="true">
        <div class="pg-grabber"></div>

        {{-- Stage 1: choose type --}}
        <div id="pg-stage-choose">
            <div class="pg-sheet-title">{{ __('What would you like to add?') }}</div>
            <div class="pg-sheet-sub">{{ __('You can select several photos at once.') }}</div>

            <button type="button" class="pg-type-opt place js-choose-type" data-type="place">
                <span class="pg-type-ic"><i data-feather="map-pin" style="width:20px;height:20px;"></i></span>
                <span>
                    <span class="pg-type-name">{{ __('Place photos') }}</span>
                    <span class="pg-type-desc">{{ __('The front, entrance, interior, décor, chairs or work area.') }}</span>
                </span>
                <i data-feather="chevron-left" class="pg-type-arrow pg-arrow-rtl" style="width:18px;height:18px;"></i>
            </button>

            <button type="button" class="pg-type-opt work js-choose-type" data-type="work">
                <span class="pg-type-ic"><i data-feather="scissors" style="width:20px;height:20px;"></i></span>
                <span>
                    <span class="pg-type-name">{{ __('Work photos') }}</span>
                    <span class="pg-type-desc">{{ __('The services or work you offer, like a haircut, styling, nails or makeup.') }}</span>
                </span>
                <i data-feather="chevron-left" class="pg-type-arrow pg-arrow-rtl" style="width:18px;height:18px;"></i>
            </button>
        </div>

        {{-- Stage 2: preview + send --}}
        <div id="pg-stage-preview" style="display:none;">
            <div class="pg-preview-head">
                <button type="button" class="pg-back-btn" id="pg-back"><i data-feather="arrow-right" class="pg-arrow-rtl" style="width:18px;height:18px;"></i></button>
                <span class="pg-preview-title" id="pg-preview-title"></span>
            </div>

            <div class="pg-preview-grid" id="pg-preview-grid"></div>

            <div class="pg-progress" id="pg-progress">
                <div class="bar-label"><span>{{ __('Uploading...') }}</span><span id="pg-pct">0%</span></div>
                <div class="bar-track"><div class="bar-fill" id="pg-bar"></div></div>
            </div>

            <button type="button" class="pg-send-btn" id="pg-send">
                <i data-feather="send" style="width:16px;height:16px;"></i>
                <span id="pg-send-label">{{ __('Send for review') }}</span>
            </button>
            <button type="button" class="pg-cancel-btn" id="pg-cancel" style="display:none;">
                <i data-feather="x" style="width:16px;height:16px;"></i>
                {{ __('Cancel upload') }}
            </button>
        </div>
    </div>
</div>

{{-- Lightbox --}}
<div class="pg-lightbox" id="pg-lightbox" aria-hidden="true">
    <button type="button" class="pg-lightbox-close" id="pg-lightbox-close" aria-label="{{ __('Close') }}">
        <i data-feather="x" style="width:22px;height:22px;"></i>
    </button>
    <div class="pg-lb-inner">
        <img alt="" id="pg-lightbox-img" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==">
        <div class="pg-lb-actions" id="pg-lb-actions"></div>
    </div>
</div>

{{-- Hidden picker: on phones the native picker offers the camera directly --}}
<input type="file" id="pg-file-input" accept="image/jpeg,image/png,image/webp" multiple style="display:none;">
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof feather !== 'undefined') feather.replace();

    // Mirror the arrow direction for LTR (icons default to an RTL layout).
    if (document.documentElement.getAttribute('dir') !== 'rtl') {
        document.querySelectorAll('.pg-arrow-rtl').forEach(function (el) { el.style.transform = 'scaleX(-1)'; });
    }

    var UPLOAD_URL   = @json(route('company.branches.gallery.upload', $branch));
    var COVER_TMPL   = @json(route('company.branches.gallery.cover', ['branch' => $branch->id, 'image' => 0]));
    var DELETE_TMPL  = @json(route('company.branches.gallery.delete', ['branch' => $branch->id, 'image' => 0]));
    var REMOVAL_TMPL = @json(route('company.branches.gallery.request-removal', ['branch' => $branch->id, 'image' => 0]));
    var CSRF         = @json(csrf_token());

    var I18N = {
        confirmDelete : @json(__('Delete this photo?')),
        confirmRemoval: @json(__('Send a removal request to the GlowRez team?')),
        deleted       : @json(__('Photo deleted')),
        coverSet      : @json(__('Photo set as cover')),
        removalSent   : @json(__('Removal request sent to the GlowRez team.')),
        netError      : @json(__('Network error. Please try again.')),
        selectFew     : @json(__('Select the photos you want to add.')),
        added         : @json(__(':n added')),
        underReview   : @json(__(':n under review')),
        titlePlace    : @json(__('Place photos')),
        titleWork     : @json(__('Work photos')),
        sendLabel     : @json(__('Send for review')),
        limitReached  : @json(__('You can add up to :max photos in this section.')),
        atLimit       : @json(__('This section already has the maximum :max photos.')),
        cancelled     : @json(__('Upload cancelled.')),
        cancelledSome : @json(__(':n photos were added before you cancelled.')),
    };
    var REASONS = @json($pgUploadReasons);
    var PG_MAX = {{ $maxPerType }};
    var PG_COUNTS = { place: {{ $activePlace }}, work: {{ $activeWork }} };

    var overlay   = document.getElementById('pg-sheet-overlay');
    var stChoose  = document.getElementById('pg-stage-choose');
    var stPreview = document.getElementById('pg-stage-preview');
    var fileInput = document.getElementById('pg-file-input');
    var prevGrid  = document.getElementById('pg-preview-grid');
    var prevTitle = document.getElementById('pg-preview-title');
    var sendBtn   = document.getElementById('pg-send');

    var currentType = null;
    var selected    = [];   // File[]

    function toast(msg, type) { (window.bkToast ? window.bkToast(msg, type || 'info') : alert(msg)); }
    function ask(opts) {
        return window.bkConfirm ? window.bkConfirm(opts)
                                : Promise.resolve({ isConfirmed: window.confirm(opts.text || '') });
    }

    /* ── Sheet open/close ──────────────────────────────────────────── */
    function openSheet(stage) {
        showStage(stage || 'choose');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSheet() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        resetSelection();
    }
    function showStage(stage) {
        stChoose.style.display  = (stage === 'choose')  ? '' : 'none';
        stPreview.style.display = (stage === 'preview') ? '' : 'none';
    }
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeSheet(); });

    document.getElementById('pg-open-sheet').addEventListener('click', function () { openSheet('choose'); });
    document.getElementById('pg-back').addEventListener('click', function () { resetSelection(); showStage('choose'); });

    // Section "+ Add" jumps straight to the picker for that type.
    document.querySelectorAll('.js-add-tile').forEach(function (t) {
        t.addEventListener('click', function () { startPick(this.getAttribute('data-type')); });
    });
    document.querySelectorAll('.js-choose-type').forEach(function (b) {
        b.addEventListener('click', function () { startPick(this.getAttribute('data-type')); });
    });

    function startPick(type) {
        currentType = type;
        var remaining = PG_MAX - (PG_COUNTS[type] || 0) - selected.length;
        if (remaining <= 0) { toast(I18N.atLimit.replace(':max', PG_MAX), 'warning'); return; }
        prevTitle.textContent = (type === 'place') ? I18N.titlePlace : I18N.titleWork;
        fileInput.value = '';
        fileInput.click();
    }

    fileInput.addEventListener('change', function () {
        var files = Array.from(fileInput.files || []);
        if (!files.length) return;
        files.forEach(function (f) { if (f.type.indexOf('image/') === 0) selected.push(f); });
        // Never let the selection exceed the remaining slots for this section.
        var allowed = Math.max(0, PG_MAX - (PG_COUNTS[currentType] || 0));
        if (selected.length > allowed) {
            selected = selected.slice(0, allowed);
            toast(I18N.limitReached.replace(':max', PG_MAX), 'warning');
        }
        renderPreview();
        openSheet('preview');
    });

    /* ── Preview stage ─────────────────────────────────────────────── */
    function renderPreview() {
        prevGrid.innerHTML = '';
        selected.forEach(function (file, idx) {
            var cell = document.createElement('div');
            cell.className = 'pg-prev-item';
            var img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.onload = function () { URL.revokeObjectURL(img.src); };
            var rm = document.createElement('button');
            rm.type = 'button'; rm.className = 'pg-prev-remove';
            rm.innerHTML = '<i data-feather="x" style="width:13px;height:13px;"></i>';
            rm.addEventListener('click', function () { selected.splice(idx, 1); renderPreview(); });
            cell.appendChild(img); cell.appendChild(rm);
            prevGrid.appendChild(cell);
        });
        var add = document.createElement('div');
        add.className = 'pg-prev-add';
        add.innerHTML = '<i data-feather="plus" style="width:20px;height:20px;"></i>';
        add.addEventListener('click', function () { fileInput.value = ''; fileInput.click(); });
        prevGrid.appendChild(add);

        var n = selected.length;
        document.getElementById('pg-send-label').textContent = I18N.sendLabel + (n ? ' (' + n + ')' : '');
        sendBtn.disabled = (n === 0);
        if (typeof feather !== 'undefined') feather.replace();
    }

    function resetSelection() { selected = []; prevGrid.innerHTML = ''; hideProgress(); }

    /* ── Upload ────────────────────────────────────────────────────── */
    var progress = document.getElementById('pg-progress');
    var bar = document.getElementById('pg-bar');
    var pct = document.getElementById('pg-pct');
    function showProgress() { progress.style.display = 'block'; bar.style.transform = 'scaleX(0)'; pct.textContent = '0%'; }
    function hideProgress() { progress.style.display = 'none'; }

    // Big selections are uploaded in small batches so a single request never
    // trips PHP's post_max_size / max_file_uploads or the app's per-request cap.
    var BATCH_MAX_COUNT = 10;
    var BATCH_MAX_BYTES = 28 * 1024 * 1024;
    var MSG = {
        tooLarge  : @json(__('Some photos are too large. Try fewer or smaller photos.')),
        checkFiles: @json(__('Please check the selected files and try again.')),
        someAdded : @json(__(':n photos were added before the upload stopped.')),
    };

    function buildBatches(files) {
        var batches = [], cur = [], bytes = 0;
        files.forEach(function (f) {
            if (cur.length && (cur.length >= BATCH_MAX_COUNT || (bytes + f.size) > BATCH_MAX_BYTES)) {
                batches.push(cur); cur = []; bytes = 0;
            }
            cur.push(f); bytes += f.size;
        });
        if (cur.length) batches.push(cur);
        return batches;
    }

    var cancelBtn = document.getElementById('pg-cancel');
    var activeXhr = null;
    var uploadCancelled = false;

    cancelBtn.addEventListener('click', function () {
        uploadCancelled = true;
        if (activeXhr) { try { activeXhr.abort(); } catch (e) {} }
    });

    function resetUploadUI() {
        sendBtn.disabled = false;
        cancelBtn.style.display = 'none';
        hideProgress();
        activeXhr = null;
    }

    sendBtn.addEventListener('click', function () {
        if (!selected.length) { toast(I18N.selectFew, 'warning'); return; }

        var batches = buildBatches(selected);
        var total   = selected.length;
        var agg     = { approved: 0, pending: 0, rejected: 0 };
        var rejects = [];
        var done    = 0; // files fully processed

        uploadCancelled = false;
        sendBtn.disabled = true;
        cancelBtn.style.display = 'flex';
        showProgress();

        function setOverall(frac) {
            bar.style.transform = 'scaleX(' + Math.min(1, frac) + ')';
            pct.textContent = Math.round(Math.min(1, frac) * 100) + '%';
        }

        function sendBatch(i) {
            if (uploadCancelled) { onCancelled(); return; }
            if (i >= batches.length) { finish(); return; }
            var batch = batches[i];

            var fd = new FormData();
            batch.forEach(function (f) { fd.append('images[]', f); });
            fd.append('type', currentType);
            fd.append('_token', CSRF);

            var xhr = new XMLHttpRequest();
            activeXhr = xhr;
            xhr.open('POST', UPLOAD_URL);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) { setOverall((done + (e.loaded / e.total) * batch.length) / total); }
            };
            xhr.onabort = function () { activeXhr = null; onCancelled(); };
            xhr.onload = function () {
                activeXhr = null;
                if (uploadCancelled) { onCancelled(); return; }
                if (xhr.status === 200) {
                    var data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (e) {}
                    var s = data.summary || {};
                    agg.approved += s.approved || 0;
                    agg.pending  += s.pending  || 0;
                    agg.rejected += s.rejected || 0;
                    (data.results || []).filter(function (r) { return !r.ok; }).forEach(function (r) { rejects.push(r); });
                    done += batch.length;
                    setOverall(done / total);
                    sendBatch(i + 1);
                } else {
                    stop(xhr.status);
                }
            };
            xhr.onerror = function () {
                activeXhr = null;
                if (uploadCancelled) { onCancelled(); return; }
                stop(0);
            };
            xhr.send(fd);
        }

        function onCancelled() {
            resetUploadUI();
            if (done > 0) {
                toast(I18N.cancelledSome.replace(':n', done), 'warning');
                setTimeout(function () { window.location.reload(); }, 900);
            } else {
                toast(I18N.cancelled, 'info');
            }
        }

        function stop(status) {
            resetUploadUI();
            var msg = (status === 413) ? MSG.tooLarge : (status === 422 ? MSG.checkFiles : I18N.netError);
            if (done > 0) {
                toast(msg + ' · ' + MSG.someAdded.replace(':n', done), 'warning');
                setTimeout(function () { window.location.reload(); }, 1000);
            } else {
                toast(msg, 'error');
            }
        }

        function finish() {
            resetUploadUI();
            rejects.forEach(function (r) { toast((r.name ? r.name + ': ' : '') + (REASONS[r.reason] || ''), 'error'); });

            var okCount = agg.approved + agg.pending;
            if (okCount > 0) {
                var parts = [];
                if (agg.approved) parts.push(I18N.added.replace(':n', agg.approved));
                if (agg.pending)  parts.push(I18N.underReview.replace(':n', agg.pending));
                toast(parts.join(' · '), agg.pending ? 'warning' : 'success');
                setTimeout(function () { window.location.reload(); }, 800);
                closeSheet();
            } else if (agg.rejected > 0) {
                resetSelection(); renderPreview();
            }
        }

        sendBatch(0);
    });

    /* ── Tap a photo → enlarge it, with its actions below ──────────── */
    ['pg-grid-place', 'pg-grid-work'].forEach(function (gid) {
        var grid = document.getElementById(gid);
        grid.addEventListener('click', function (e) {
            var item = e.target.closest('.pg-item');
            if (item) openLightbox(item);
        });
    });

    /* ── Lightbox ──────────────────────────────────────────────────── */
    var lb    = document.getElementById('pg-lightbox');
    var lbImg = document.getElementById('pg-lightbox-img');
    var lbActions = document.getElementById('pg-lb-actions');
    var LBL = {
        cover  : @json(__('Set as cover')),
        replace: @json(__('Replace photo')),
        del    : @json(__('Delete')),
        removal: @json(__('Request removal')),
    };

    function openLightbox(item) {
        var img = item.querySelector('img');
        lbImg.setAttribute('src', img ? img.getAttribute('src') : LB_BLANK);
        buildLbActions(item);
        lb.classList.add('open');
        lb.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function buildLbActions(item) {
        lbActions.innerHTML = '';
        var approved = item.getAttribute('data-approved') === '1';
        var status   = item.getAttribute('data-status');
        var locked   = item.getAttribute('data-locked') === '1';
        var isCover  = item.classList.contains('is-cover');

        function add(cls, icon, label, fn) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'pg-lb-btn ' + cls;
            b.innerHTML = '<i data-feather="' + icon + '"></i><span>' + label + '</span>';
            b.addEventListener('click', fn);
            lbActions.appendChild(b);
        }

        if (approved && !isCover) add('', 'star', LBL.cover, function () { doSetCover(item); closeLightbox(); });
        if (status === 'rejected' && !locked) add('pg-lb-accent', 'refresh-cw', LBL.replace, function () { closeLightbox(); doDelete(item, true); });
        if (!locked) add('pg-lb-danger', 'trash-2', LBL.del, function () { closeLightbox(); doDelete(item, false); });
        else add('', 'flag', LBL.removal, function () { closeLightbox(); doRequestRemoval(item); });

        if (typeof feather !== 'undefined') feather.replace();
    }

    var LB_BLANK = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
    function closeLightbox() {
        lb.classList.remove('open');
        lb.setAttribute('aria-hidden', 'true');
        lbImg.setAttribute('src', LB_BLANK);
        if (!overlay.classList.contains('open')) document.body.style.overflow = '';
    }
    document.getElementById('pg-lightbox-close').addEventListener('click', closeLightbox);
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLightbox(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeLightbox(); });

    function doSetCover(item) {
        var id = item.getAttribute('data-id');
        fetch(COVER_TMPL.replace('/0/cover', '/' + id + '/cover'), {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) return;
            document.querySelectorAll('.pg-item.is-cover').forEach(function (el) {
                el.classList.remove('is-cover');
                var m = el.querySelector('.pg-mark-cover'); if (m) m.remove();
            });
            item.classList.add('is-cover');
            var marks = item.querySelector('.pg-marks');
            if (marks && !marks.querySelector('.pg-mark-cover')) {
                marks.insertAdjacentHTML('afterbegin', '<span class="pg-mark pg-mark-cover"><i data-feather="star" style="width:12px;height:12px;"></i></span>');
            }
            if (typeof feather !== 'undefined') feather.replace();
            toast(I18N.coverSet, 'success');
        });
    }

    function doDelete(item, thenReplace) {
        ask({ text: I18N.confirmDelete, icon: 'warning' }).then(function (r) {
            if (!r || !r.isConfirmed) return;
            var id = item.getAttribute('data-id');
            var type = item.getAttribute('data-type');
            fetch(DELETE_TMPL.replace('/0', '/' + id), {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
              .then(function (res) {
                if (!res.ok || !res.d.ok) { toast(I18N.netError, 'error'); return; }
                var wasCover = item.classList.contains('is-cover');
                item.remove();
                updateCount(type);
                toast(I18N.deleted, 'error');
                if (wasCover) { setTimeout(function () { window.location.reload(); }, 600); return; }
                if (thenReplace) startPick(type);
            });
        });
    }

    function doRequestRemoval(item) {
        ask({ text: I18N.confirmRemoval, icon: 'question' }).then(function (r) {
            if (!r || !r.isConfirmed) return;
            var id = item.getAttribute('data-id');
            fetch(REMOVAL_TMPL.replace('/0/request-removal', '/' + id + '/request-removal'), {
                method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d.ok) toast(I18N.removalSent, 'success');
            });
        });
    }

    function updateCount(type) {
        var grid = document.getElementById('pg-grid-' + type);
        var n = grid.querySelectorAll('.pg-item').length;
        var el = document.getElementById('pg-count-' + type);
        if (el) el.textContent = n;
    }
});
</script>
@endpush
