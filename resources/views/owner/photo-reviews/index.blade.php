@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

@php
    $typeMeta = [
        'place' => __('Place'),
        'work'  => __('Work'),
    ];
    $sourceMeta = [
        'business'     => __('Business owner'),
        'glowrez_team' => __('GlowRez team'),
    ];
    $flagMeta = [
        'blurry'  => __('Possibly blurry'),
        'dark'    => __('Low light'),
        'low_res' => __('Low resolution'),
    ];
    $tabs = [
        'pending'  => __('Under review'),
        'approved' => __('Approved'),
        'rejected' => __('Rejected'),
        'all'      => __('All'),
    ];
    $baseQuery = array_filter([
        'company' => $filters['companyId'],
        'type'    => $filters['type'],
        'source'  => $filters['source'],
        'q'       => $filters['search'] ?: null,
    ]);
@endphp

@push('owner-styles')
<style>
.pr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-top: 18px; }
.pr-card { border-radius: 14px; overflow: hidden; background: var(--bk-surface); box-shadow: inset 0 0 0 1px var(--bk-border); display: flex; flex-direction: column; }
.pr-card ::selection { background: var(--bk-accent-wash); color: var(--bk-accent); }
.pr-media { position: relative; aspect-ratio: 4/3; background: var(--bk-surface-2); overflow: hidden; }
.pr-media > img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pr-card.is-rejected .pr-media > img { opacity: .5; filter: grayscale(.5); }

.pr-badge { position: absolute; inset-block-start: 9px; inset-inline-start: 9px; font-size: .66rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
.pr-badge-pending  { background: color-mix(in srgb, var(--bk-warning-bg) 90%, transparent); color: var(--bk-warning); }
.pr-badge-approved { background: color-mix(in srgb, var(--bk-success-bg) 88%, transparent); color: var(--bk-success); }
.pr-badge-rejected { background: color-mix(in srgb, var(--bk-danger-bg) 90%, transparent);  color: var(--bk-danger); }

.pr-marks { position: absolute; inset-block-start: 9px; inset-inline-end: 9px; display: flex; gap: 5px; }
.pr-mark { width: 24px; height: 24px; border-radius: 7px; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 2px 6px -2px rgba(0,0,0,.4); }
.pr-mark-cover { background: var(--bk-gold-strong); }
.pr-mark-team  { background: var(--bk-accent); }

.pr-removal { position: absolute; inset-block-end: 0; inset-inline: 0; background: linear-gradient(to top, rgba(178,58,72,.92), transparent); color: #fff; font-size: .72rem; font-weight: 600; padding: 18px 10px 8px; display: flex; align-items: center; gap: 6px; }

.pr-body { padding: 13px 14px 14px; display: flex; flex-direction: column; gap: 9px; flex: 1; }
.pr-company { font-weight: 700; font-size: .9rem; color: var(--bk-text); line-height: 1.3; }
.pr-branch { font-size: .8rem; color: var(--bk-text-muted); margin-top: 1px; }
.pr-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.pr-chip { font-size: .68rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: var(--bk-surface-2); color: var(--bk-text-soft); box-shadow: inset 0 0 0 1px var(--bk-border); }
.pr-chip-place { color: var(--bk-accent); background: var(--bk-accent-wash); box-shadow: none; }
.pr-chip-work  { color: var(--bk-gold-strong); background: var(--bk-gold-soft); box-shadow: none; }
.pr-chip-team  { color: var(--bk-accent); }
.pr-chip-flag  { color: var(--bk-warning); background: var(--bk-warning-bg); box-shadow: none; }
.pr-date { font-size: .72rem; color: var(--bk-text-muted); font-variant-numeric: tabular-nums; }
.pr-reason { font-size: .76rem; color: var(--bk-danger); padding-inline-start: 9px; border-inline-start: 2px solid var(--bk-danger); line-height: 1.4; }

.pr-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 4px; }
.pr-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 10px; border-radius: 9px; border: none; cursor: pointer; font-weight: 650; font-size: .8rem; transition: background .14s, box-shadow .14s, transform .1s; }
.pr-btn:active { transform: translateY(1px); }
.pr-btn-approve { background: var(--bk-success); color: #fff; }
.pr-btn-approve:hover { filter: brightness(1.06); }
.pr-btn-reject { background: var(--bk-surface); color: var(--bk-danger); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--bk-danger) 45%, var(--bk-border)); }
.pr-btn-reject:hover { background: var(--bk-danger-bg); }
.pr-btn-cover { background: var(--bk-surface); color: var(--bk-gold-strong); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--bk-gold) 45%, var(--bk-border)); }
.pr-btn-cover:hover { background: var(--bk-gold-soft); }
.pr-btn :focus-visible { outline: 2px solid var(--bk-accent); outline-offset: 2px; }
.pr-del { width: 100%; margin-top: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px; border-radius: 8px; border: none; cursor: pointer; background: transparent; color: var(--bk-text-muted); font-size: .78rem; font-weight: 600; transition: background .14s, color .14s; }
.pr-del:hover { background: var(--bk-danger-bg); color: var(--bk-danger); }
.pr-del.is-requested { color: var(--bk-danger); background: var(--bk-danger-bg); }
.js-pr-delete-form { margin: 0; }

/* Reject modal */
.pr-modal-ov { position: fixed; inset: 0; z-index: 1090; background: var(--bk-scrim); display: none; align-items: center; justify-content: center; padding: 18px; -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px); }
.pr-modal-ov.open { display: flex; }
.pr-modal { width: 100%; max-width: 420px; background: var(--bk-surface); border-radius: 16px; box-shadow: var(--bk-shadow-xl); padding: 22px; }
.pr-modal h3 { font-size: 1.05rem; font-weight: 700; color: var(--bk-text); margin: 0 0 5px; }
.pr-modal p { font-size: .84rem; color: var(--bk-text-muted); margin: 0 0 16px; line-height: 1.5; }
.pr-modal label { display: block; font-size: .8rem; font-weight: 600; color: var(--bk-text-soft); margin-bottom: 7px; }
.pr-modal select { width: 100%; padding: 11px 12px; border-radius: 10px; background: var(--bk-bg); color: var(--bk-text); border: 1px solid var(--bk-border); font-size: .88rem; }
.pr-modal select:focus-visible { outline: 2px solid var(--bk-accent); outline-offset: 1px; }
.pr-modal-foot { display: flex; gap: 10px; margin-top: 20px; }
.pr-modal-foot button { flex: 1; padding: 11px; border-radius: 10px; border: none; cursor: pointer; font-weight: 650; font-size: .86rem; }
.pr-modal-cancel { background: var(--bk-surface-2); color: var(--bk-text-soft); }
.pr-modal-confirm { background: var(--bk-danger); color: #fff; }
</style>
@endpush

<div class="page-content bm-wrap">

    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('Growth & content') }}
            </div>
            <h1 class="bm-title">{{ __('Photo review') }}</h1>
            <p class="bm-subtitle">{{ __('Review the photos businesses add to their branches before they appear to customers. Approve the good ones, or reject with a reason the owner will see.') }}</p>
        </div>
        <div class="bm-head-actions">
            <button type="button" class="bm-btn bm-btn-primary" id="pr-team-open">
                <i data-feather="upload"></i>{{ __('Upload team photos') }}
            </button>
        </div>
    </header>

    @include('owner.partials.flash')

    {{-- Stats --}}
    <section class="bm-stats bm-reveal" aria-label="{{ __('Overview') }}">
        <div class="bm-stat" style="--accent:var(--bk-warning);"><span class="bm-stat-label"><i data-feather="clock"></i>{{ __('Under review') }}</span><span class="bm-stat-value">{{ number_format($counts['pending'] ?? 0) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-success);"><span class="bm-stat-label"><i data-feather="check-circle"></i>{{ __('Approved') }}</span><span class="bm-stat-value">{{ number_format($counts['approved'] ?? 0) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-danger);"><span class="bm-stat-label"><i data-feather="x-circle"></i>{{ __('Rejected') }}</span><span class="bm-stat-value">{{ number_format($counts['rejected'] ?? 0) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-gold);"><span class="bm-stat-label"><i data-feather="flag"></i>{{ __('Removal requests') }}</span><span class="bm-stat-value">{{ number_format($removalCount) }}</span></div>
    </section>

    {{-- Status tabs --}}
    <div class="bm-toolbar bm-reveal">
        <div class="bm-toolbar-row" style="gap:6px;flex-wrap:wrap;">
            @foreach($tabs as $key => $label)
                <a href="{{ route('owner.photo-reviews.index', array_merge($baseQuery, ['status' => $key])) }}"
                   class="bm-tab {{ $status === $key ? 'is-active' : '' }}">
                    {{ $label }}
                    @if(isset($counts[$key]))<span class="bm-tab-count">{{ $counts[$key] }}</span>@endif
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('owner.photo-reviews.index') }}" class="bm-toolbar-row" style="gap:10px;flex-wrap:wrap;margin-top:12px;">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="bm-search" style="flex:1;min-width:200px;">
                <button type="submit" class="bm-search-btn" tabindex="-1"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $filters['search'] }}" placeholder="{{ __('Search by company or branch…') }}" autocomplete="off">
            </div>
            <select name="company" class="bm-select" onchange="this.form.submit()">
                <option value="">{{ __('All companies') }}</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" @selected($filters['companyId'] == $c->id)>{{ $c->localizedName() }}</option>
                @endforeach
            </select>
            <select name="type" class="bm-select" onchange="this.form.submit()">
                <option value="">{{ __('All types') }}</option>
                <option value="place" @selected($filters['type']==='place')>{{ __('Place') }}</option>
                <option value="work" @selected($filters['type']==='work')>{{ __('Work') }}</option>
            </select>
            <select name="source" class="bm-select" onchange="this.form.submit()">
                <option value="">{{ __('All sources') }}</option>
                <option value="business" @selected($filters['source']==='business')>{{ __('Business owner') }}</option>
                <option value="glowrez_team" @selected($filters['source']==='glowrez_team')>{{ __('GlowRez team') }}</option>
            </select>
        </form>
    </div>

    @if($images->isEmpty())
        <div class="bm-empty bm-reveal">
            <div class="bm-empty-ic"><i data-feather="image"></i></div>
            <div class="bm-empty-title">{{ __('Nothing here') }}</div>
            <div class="bm-empty-sub">{{ __('No photos match these filters right now.') }}</div>
        </div>
    @else
        <div class="pr-grid bm-reveal">
            @foreach($images as $img)
                @php
                    $removalAt = $img->flags['removal_requested_at'] ?? null;
                    $qualityFlags = array_values(array_intersect(array_keys($flagMeta), $img->flags ?? []));
                @endphp
                <article class="pr-card is-{{ $img->status }}">
                    <div class="pr-media">
                        <img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">
                        <span class="pr-badge pr-badge-{{ $img->status }}">
                            @if($img->isApproved()) {{ __('Approved') }}
                            @elseif($img->isPending()) {{ __('Under review') }}
                            @else {{ __('Rejected') }}
                            @endif
                        </span>
                        <div class="pr-marks">
                            @if($img->is_cover)<span class="pr-mark pr-mark-cover" title="{{ __('Cover photo') }}"><i data-feather="star" style="width:13px;height:13px;"></i></span>@endif
                            @if($img->isTeam())<span class="pr-mark pr-mark-team" title="{{ __('GlowRez team') }}"><i data-feather="award" style="width:13px;height:13px;"></i></span>@endif
                        </div>
                        @if($removalAt)
                            <div class="pr-removal"><i data-feather="flag" style="width:13px;height:13px;"></i>{{ __('Removal requested by the business') }}</div>
                        @endif
                    </div>

                    <div class="pr-body">
                        <div>
                            <div class="pr-company">{{ $img->branch->company?->localizedName() ?? '—' }}</div>
                            <div class="pr-branch">{{ $img->branch?->localizedName() ?? '—' }}</div>
                        </div>

                        <div class="pr-chips">
                            <span class="pr-chip pr-chip-{{ $img->type }}">{{ $typeMeta[$img->type] ?? $img->type }}</span>
                            <span class="pr-chip {{ $img->isTeam() ? 'pr-chip-team' : '' }}">{{ $sourceMeta[$img->source] ?? $img->source }}</span>
                            @foreach($qualityFlags as $f)
                                <span class="pr-chip pr-chip-flag">{{ $flagMeta[$f] }}</span>
                            @endforeach
                        </div>

                        <span class="pr-date">{{ optional($img->created_at)->translatedFormat('M j, Y') }}</span>

                        @if($img->isRejected() && $img->rejection_reason)
                            <div class="pr-reason">{{ __($reasons[$img->rejection_reason] ?? $img->rejection_reason) }}</div>
                        @endif

                        <div class="pr-actions">
                            @unless($img->isApproved())
                                <form method="POST" action="{{ route('owner.photo-reviews.approve', $img) }}" style="flex:1;">
                                    @csrf
                                    <button type="submit" class="pr-btn pr-btn-approve"><i data-feather="check" style="width:15px;height:15px;"></i>{{ __('Approve') }}</button>
                                </form>
                            @endunless

                            @if($img->isApproved() && !$img->is_cover)
                                <form method="POST" action="{{ route('owner.photo-reviews.cover', $img) }}" style="flex:1;">
                                    @csrf
                                    <button type="submit" class="pr-btn pr-btn-cover"><i data-feather="star" style="width:15px;height:15px;"></i>{{ __('Set cover') }}</button>
                                </form>
                            @endif

                            @unless($img->isRejected())
                                <button type="button" class="pr-btn pr-btn-reject js-reject"
                                        data-action="{{ route('owner.photo-reviews.reject', $img) }}">
                                    <i data-feather="x" style="width:15px;height:15px;"></i>{{ __('Reject') }}
                                </button>
                            @endunless
                        </div>

                        <form method="POST" action="{{ route('owner.photo-reviews.destroy', $img) }}" class="js-pr-delete-form">
                            @csrf @method('DELETE')
                            <button type="submit" class="pr-del js-pr-delete {{ ($img->flags['removal_requested_at'] ?? null) ? 'is-requested' : '' }}">
                                <i data-feather="trash-2" style="width:13px;height:13px;"></i>{{ __('Delete permanently') }}
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="bm-pagination bm-reveal" style="margin-top:20px;">
            {{ $images->links() }}
        </div>
    @endif
</div>

{{-- Reject modal --}}
<div class="pr-modal-ov" id="pr-reject-modal">
    <div class="pr-modal" role="dialog" aria-modal="true">
        <h3>{{ __('Reject this photo') }}</h3>
        <p>{{ __('Choose a reason. The business owner will see it and can upload a replacement.') }}</p>
        <form method="POST" id="pr-reject-form">
            @csrf
            <label for="pr-reason">{{ __('Reason') }}</label>
            <select name="reason" id="pr-reason" required>
                @foreach($reasons as $code => $label)
                    <option value="{{ $code }}">{{ __($label) }}</option>
                @endforeach
            </select>
            <div class="pr-modal-foot">
                <button type="button" class="pr-modal-cancel" id="pr-reject-cancel">{{ __('Cancel') }}</button>
                <button type="submit" class="pr-modal-confirm">{{ __('Reject photo') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Team upload modal --}}
<div class="pr-modal-ov" id="pr-team-modal">
    <div class="pr-modal" role="dialog" aria-modal="true">
        <h3>{{ __('Upload team photos') }}</h3>
        <p>{{ __('Photos the GlowRez team took for a branch. They are approved immediately and the business cannot edit them.') }}</p>
        <form method="POST" action="{{ route('owner.photo-reviews.team-upload') }}" enctype="multipart/form-data">
            @csrf
            <label for="pr-team-branch">{{ __('Branch') }}</label>
            <select name="branch_id" id="pr-team-branch" required style="margin-bottom:14px;">
                <option value="" disabled selected>{{ __('Choose a branch…') }}</option>
                @foreach($companies as $c)
                    @if($branches->has($c->id))
                        <optgroup label="{{ $c->localizedName() }}">
                            @foreach($branches->get($c->id) as $b)
                                <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>

            <label for="pr-team-type">{{ __('Type') }}</label>
            <select name="type" id="pr-team-type" required style="margin-bottom:14px;">
                <option value="place">{{ __('Place photos') }}</option>
                <option value="work">{{ __('Work photos') }}</option>
            </select>

            <label for="pr-team-files">{{ __('Photos') }}</label>
            <input type="file" name="images[]" id="pr-team-files" accept="image/jpeg,image/png,image/webp" multiple required
                   style="width:100%;padding:10px;border-radius:10px;background:var(--bk-bg);color:var(--bk-text);border:1px solid var(--bk-border);font-size:.84rem;">

            <div class="pr-modal-foot">
                <button type="button" class="pr-modal-cancel" id="pr-team-cancel">{{ __('Cancel') }}</button>
                <button type="submit" class="pr-modal-confirm" style="background:var(--bk-accent);">{{ __('Upload') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof feather !== 'undefined') feather.replace();

    var modal = document.getElementById('pr-reject-modal');
    var form  = document.getElementById('pr-reject-form');

    document.querySelectorAll('.js-reject').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.setAttribute('action', this.getAttribute('data-action'));
            modal.classList.add('open');
        });
    });
    function close() { modal.classList.remove('open'); }
    document.getElementById('pr-reject-cancel').addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });

    // Team upload modal
    var teamModal = document.getElementById('pr-team-modal');
    var teamOpen  = document.getElementById('pr-team-open');
    function closeTeam() { teamModal.classList.remove('open'); }
    if (teamOpen) teamOpen.addEventListener('click', function () { teamModal.classList.add('open'); });
    document.getElementById('pr-team-cancel').addEventListener('click', closeTeam);
    teamModal.addEventListener('click', function (e) { if (e.target === teamModal) closeTeam(); });

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); closeTeam(); } });

    // Confirm permanent deletes.
    var DEL_MSG = @json(__('Permanently delete this photo? This cannot be undone.'));
    document.querySelectorAll('.js-pr-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) { if (!window.confirm(DEL_MSG)) e.preventDefault(); });
    });
});
</script>
@endpush
