@extends('company.dashboard')

@push('company-styles')
<style>
    .bk-details > summary::-webkit-details-marker { display:none; }
    .bk-details[open] .bk-details-chevron { transform:rotate(180deg); }
    .bk-details-chevron { transition:transform .2s ease; color:var(--bk-text-muted); }
    .bk-scope-tile { cursor:pointer; transition:border-color .15s, background .15s; background:var(--bk-surface); }
    .bk-scope-tile:hover { border-color:var(--bk-accent) !important; }
    .bk-scope-tile input:checked ~ .bk-scope-body { color:var(--bk-text); }
    input.btn-check:checked + label { background:var(--bk-accent-fill); color:var(--bk-accent-ink); border-color:var(--bk-accent-fill); }
    .bk-scope-tile:has(input:checked) { border-color:var(--bk-accent) !important; background:var(--bk-accent-wash); box-shadow:0 0 0 1px var(--bk-accent) inset; }
    .bk-branch-pill { cursor:pointer; border:1px solid var(--bk-border); background:var(--bk-surface); }
    .bk-branch-pill.active { background:var(--bk-accent-fill); color:var(--bk-accent-ink); border-color:var(--bk-accent-fill); }
    .bk-radio-tile:has(input:checked) { border-color:var(--bk-accent) !important; background:var(--bk-accent-wash); }
    .bk-save-bar { position:sticky; top:0; z-index:20; backdrop-filter:blur(6px); }
</style>
@endpush

@section('content')
<div class="page-content">

    {{-- ── Sticky header + save ─────────────────────────────── --}}
    <div class="bk-save-bar d-flex justify-content-between align-items-center flex-wrap gap-2 grid-margin py-2"
         style="background:color-mix(in srgb,var(--bk-bg) 82%,transparent);">
        <div>
            <h4 class="bk-t-page mb-1">{{ __('Booking & Cancellation Policy') }}</h4>
            <p class="text-muted small mb-0">{{ __('Control how the system handles cancellations, lateness and no-shows.') }}</p>
        </div>
        <button type="submit" form="policy-form" class="btn btn-gold rounded-pill px-4">
            <i data-feather="check" style="width:15px;height:15px;" class="me-1"></i>{{ __('Save changes') }}
        </button>
    </div>

    @include('company.partials.flash')

    <form method="POST" action="{{ route('company.booking-policy.update') }}" id="policy-form">
        @csrf
        <input type="hidden" name="mode" id="policy-mode" value="{{ $mode }}">

        <div class="row justify-content-center">
            <div class="col-lg-9">

                {{-- ── Scope selector ───────────────────────────── --}}
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="bk-scope-tile d-flex gap-3 p-3 rounded-4 border shadow-sm h-100 mb-0">
                            <input type="radio" class="form-check-input mt-1 flex-shrink-0" name="scope_choice" value="unified" @checked($mode === 'unified')>
                            <span class="bk-scope-body">
                                <span class="fw-bold d-block mb-1"><i data-feather="layers" style="width:15px;height:15px;" class="me-1"></i>{{ __('One policy for all branches') }}</span>
                                <span class="text-muted small">{{ __('Simplest — set it once, applies everywhere.') }}</span>
                            </span>
                        </label>
                    </div>
                    <div class="col-sm-6">
                        <label class="bk-scope-tile d-flex gap-3 p-3 rounded-4 border shadow-sm h-100 mb-0">
                            <input type="radio" class="form-check-input mt-1 flex-shrink-0" name="scope_choice" value="per_branch" @checked($mode === 'per_branch') @if($branches->count() < 2) disabled @endif>
                            <span class="bk-scope-body">
                                <span class="fw-bold d-block mb-1"><i data-feather="git-branch" style="width:15px;height:15px;" class="me-1"></i>{{ __('Each branch has its own policy') }}</span>
                                <span class="text-muted small">
                                    @if($branches->count() < 2)
                                        {{ __('Add a second branch to unlock this.') }}
                                    @else
                                        {{ __('Fine-tune per location when they differ.') }}
                                    @endif
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- ── Unified editor ───────────────────────────── --}}
                <div id="panel-unified" @if($mode !== 'unified') hidden @endif>
                    @include('company.booking-policy._fields', ['prefix' => 'unified', 'idp' => 'u', 'p' => $companyPolicy])
                </div>

                {{-- ── Per-branch editor ────────────────────────── --}}
                <div id="panel-per-branch" @if($mode !== 'per_branch') hidden @endif>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                        <div class="d-flex gap-2 flex-wrap" id="branch-pills">
                            @foreach ($branches as $i => $branch)
                                <button type="button" class="bk-branch-pill rounded-pill px-3 py-2 small fw-semibold {{ $i === 0 ? 'active' : '' }}"
                                        data-branch="{{ $branch->id }}">
                                    <i data-feather="map-pin" style="width:13px;height:13px;" class="me-1"></i>{{ $branch->localizedName() }}
                                </button>
                            @endforeach
                        </div>
                        <button type="button" id="copy-to-all" class="btn btn-sm btn-outline-secondary rounded-pill px-3 ms-auto">
                            <i data-feather="copy" style="width:13px;height:13px;" class="me-1"></i>{{ __('Apply this branch to all') }}
                        </button>
                    </div>

                    @foreach ($branches as $i => $branch)
                        <div class="branch-panel" data-branch="{{ $branch->id }}" @if($i !== 0) hidden @endif>
                            @include('company.booking-policy._fields', [
                                'prefix' => 'branch['.$branch->id.']',
                                'idp'    => 'b'.$branch->id,
                                'p'      => $branchPolicies[$branch->id],
                            ])
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var modeInput   = document.getElementById('policy-mode');
    var panelUni    = document.getElementById('panel-unified');
    var panelPer    = document.getElementById('panel-per-branch');

    // Only ENABLED inputs are submitted. Visibility is independent — a hidden
    // branch panel must still submit, otherwise unvisited branches never save.
    function setEnabled(panel, enabled) {
        if (!panel) return;
        panel.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.disabled = !enabled;
        });
    }

    function branchPanels() {
        return document.querySelectorAll('#panel-per-branch .branch-panel');
    }

    function applyMode(mode) {
        modeInput.value = mode;
        var per = mode === 'per_branch';

        // Unified panel: enabled + visible only in unified mode.
        if (panelUni) { panelUni.hidden = per; setEnabled(panelUni, !per); }

        // Per-branch container visible only in per-branch mode.
        panelPer.hidden = !per;

        // In per-branch mode ALL branch panels stay enabled (so all save);
        // only the active one is visible. In unified mode they're all disabled.
        branchPanels().forEach(function (bp) {
            setEnabled(bp, per);
            bp.hidden = !per || bp.getAttribute('data-branch') !== activeBranch;
        });
    }

    // ── Scope radios ──
    var activeBranch = (document.querySelector('.bk-branch-pill') || {}).dataset
        ? document.querySelector('.bk-branch-pill').dataset.branch : null;

    document.querySelectorAll('input[name="scope_choice"]').forEach(function (r) {
        r.addEventListener('change', function () { applyMode(this.value); });
    });

    // ── Branch pills (visibility only — panels stay enabled) ──
    document.querySelectorAll('.bk-branch-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            activeBranch = this.dataset.branch;
            document.querySelectorAll('.bk-branch-pill').forEach(function (p) { p.classList.toggle('active', p === pill); });
            branchPanels().forEach(function (bp) {
                bp.hidden = bp.getAttribute('data-branch') !== activeBranch;
            });
        });
    });

    // ── Reveal-on-toggle (confirmation deadline, deposit, protection) ──
    function wireReveal(cb) {
        var target = document.getElementById(cb.getAttribute('data-reveal'));
        if (!target) return;
        var sync = function () { target.hidden = !cb.checked; };
        cb.addEventListener('change', sync);
        sync();
    }
    document.querySelectorAll('.js-reveal-toggle').forEach(wireReveal);

    // ── Grace-period range live label ──
    document.querySelectorAll('.js-grace').forEach(function (range) {
        var out = range.closest('.card-body').querySelector('.js-grace-out');
        range.addEventListener('input', function () { if (out) out.textContent = range.value; });
    });

    // ── Copy current branch → all branches ──
    var copyBtn = document.getElementById('copy-to-all');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var src = document.querySelector('#panel-per-branch .branch-panel[data-branch="' + activeBranch + '"]');
            if (!src) return;
            var strip = function (name) { return name.replace(/^branch\[\d+\]/, ''); };
            var srcFields = src.querySelectorAll('input[name], select[name], textarea[name]');
            document.querySelectorAll('#panel-per-branch .branch-panel').forEach(function (dst) {
                if (dst === src) return;
                srcFields.forEach(function (sf) {
                    var suffix = strip(sf.getAttribute('name'));
                    var match = dst.querySelector('[name="branch[' + dst.dataset.branch + ']' + cssEscape(suffix) + '"]');
                    if (!match) return;
                    if (sf.type === 'checkbox' || sf.type === 'radio') {
                        if (sf.type === 'radio' && !sf.checked) return;
                        match.checked = sf.checked;
                    } else {
                        match.value = sf.value;
                    }
                    match.dispatchEvent(new Event('change', { bubbles: true }));
                    match.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
            var original = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i data-feather="check" style="width:13px;height:13px;" class="me-1"></i>{{ __('Applied to all') }}';
            if (window.feather) window.feather.replace();
            setTimeout(function () { copyBtn.innerHTML = original; if (window.feather) window.feather.replace(); }, 2000);
        });
    }
    // Minimal CSS.escape fallback for [ ] characters in attribute selectors.
    function cssEscape(s) { return s.replace(/[\[\]]/g, '\\$&'); }

    // Initialise on load.
    applyMode(modeInput.value);
    if (window.feather) window.feather.replace();
})();
</script>
@endpush
