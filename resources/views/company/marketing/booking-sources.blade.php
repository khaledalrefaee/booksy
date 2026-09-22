@extends('company.dashboard')

@push('company-styles')
<style>
    .mk-wrap { max-width:900px; }

    /* Branch context pill */
    .mk-branchbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .mk-branchbar .mk-bb-label { font-size:12px; color:var(--bk-text-muted); }

    /* Source link rows */
    .mk-srcrow { padding:16px; border-bottom:1px solid var(--bk-border); }
    .mk-srcrow:last-child { border-bottom:0; }
    .mk-srchead { display:flex; align-items:center; gap:12px; }
    .mk-srcic { width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
    .mk-srcname { font-size:14px; font-weight:700; line-height:1.2; color:var(--bk-text); }
    .mk-srchint { font-size:11.5px; color:var(--bk-text-muted); margin-top:2px; }
    .mk-chip { margin-inline-start:auto; text-align:center; flex-shrink:0; }
    .mk-chip-num { font-size:16px; font-weight:800; line-height:1; color:var(--bk-text); }
    .mk-chip-lbl { font-size:10px; color:var(--bk-text-muted); margin-top:3px; }

    .mk-linkrow { display:flex; gap:8px; margin-top:12px; }
    .mk-linkin {
        flex:1; min-width:0; direction:ltr; text-align:left;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:12.5px;
        border:1px solid var(--bk-border); border-radius:11px; padding:9px 12px;
        background:var(--bk-surface-2); color:var(--bk-text) !important;
    }
    .mk-linkin:focus { outline:none; border-color:var(--bk-accent); box-shadow:0 0 0 3px rgba(166,188,126,.18); }

    .mk-copy { flex-shrink:0; white-space:nowrap; border-radius:11px; font-weight:600; font-size:12.5px; padding:0 16px; }
    .mk-copy.copied { background:#0f9d58 !important; border-color:#0f9d58 !important; color:#fff !important; }

    .mk-reception { background:var(--bk-gold-soft); }

    /* Range pills */
    .mk-range { display:inline-flex; gap:5px; flex-wrap:wrap; }
    .mk-range a { font-size:12px; font-weight:600; padding:6px 13px; border-radius:999px; border:1px solid var(--bk-border); color:var(--bk-text); text-decoration:none; transition:all .12s; }
    .mk-range a:hover { border-color:var(--bk-accent); }
    .mk-range a.active { background:var(--bk-accent-fill); color:#fff; border-color:var(--bk-accent-fill); }

    /* Analytics */
    .mk-bar-track { height:8px; border-radius:6px; background:var(--bk-border); overflow:hidden; }
    .mk-bar-fill { height:100%; border-radius:6px; transition:width .4s ease; }
    .mk-anrow { display:flex; align-items:center; gap:12px; padding:13px 16px; border-bottom:1px solid var(--bk-border); }
    .mk-anrow:last-child { border-bottom:0; }
    .mk-an-ic { width:26px; height:26px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
    .mk-an-name { font-size:13px; font-weight:600; min-width:96px; color:var(--bk-text); }
    .mk-an-bar { flex:1; min-width:60px; }
    .mk-an-num { font-weight:700; font-size:13px; min-width:54px; text-align:end; color:var(--bk-text); }
    .mk-an-pct { color:var(--bk-text-muted); font-size:12px; min-width:42px; text-align:end; }

    @media (max-width:575px){
        .mk-an-name { min-width:74px; font-size:12px; }
        .mk-chip-num { font-size:15px; }
    }
</style>
@endpush

@section('content')
<div class="page-content">
  <div class="mk-wrap">

    {{-- Header --}}
    <div class="mb-4">
        <h4 class="mb-1 fw-bold">{{ __('Social Media & Booking Sources') }}</h4>
        <p class="text-muted mb-0 tx-13">{{ __('Share a link for each channel and see where your bookings come from.') }}</p>
    </div>

    @include('company.partials.flash')

    @if(!$selBranch)
        <div class="card border-0 shadow-sm rounded-4">
            <div class="p-5 text-center">
                <i data-feather="map-pin" style="width:34px;height:34px;color:#cbd5e1;"></i>
                <h6 class="fw-bold mt-3 mb-1">{{ __('No branches yet') }}</h6>
                <p class="text-muted tx-13 mb-0">{{ __('Create a branch first to get its tracking links.') }}</p>
            </div>
        </div>
    @else

        {{-- Branch selector + note that each branch has its own links --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="mk-branchbar">
                    @if($branches->count() > 1 && ! ($branchContext ?? null))
                        <i data-feather="map-pin" style="width:16px;height:16px;color:var(--bk-olive,#4B5D34);"></i>
                        <span class="mk-bb-label">{{ __('Links for branch') }}:</span>
                        <form method="GET" class="m-0">
                            <input type="hidden" name="range" value="{{ $range }}">
                            <select name="branch" class="form-select form-select-sm rounded-3 fw-semibold" onchange="this.form.submit()" style="width:auto;min-width:180px;">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected($b->id === $selBranch->id)>{{ $b->localizedName() }}</option>
                                @endforeach
                            </select>
                        </form>
                        <span class="mk-bb-label d-none d-sm-inline">— {{ __('each branch has its own links') }}</span>
                    @else
                        <i data-feather="map-pin" style="width:16px;height:16px;color:var(--bk-olive,#4B5D34);"></i>
                        <span class="fw-semibold" style="font-size:13px;">{{ $selBranch->localizedName() }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tracking links --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-1 px-3">
                <span class="fw-bold" style="font-size:14px;">{{ __('Your tracking links') }}</span>
                <div class="text-muted mt-1" style="font-size:11.5px;">{{ __('Put each link where your customers see it — the bookings that come through it are counted here.') }}</div>
            </div>
            <div class="card-body p-0">
                @foreach($rows as $row)
                    @php $sv = $row['source']->value; @endphp
                    @if($sv === 'other') @continue @endif

                    @if($sv === 'reception')
                        <div class="mk-srcrow mk-reception">
                            <div class="mk-srchead">
                                <span class="mk-srcic" style="background:{{ $row['color'] }}1a;">@include('company.partials.source-icon', ['source' => $row['iconKey'], 'size' => 20])</span>
                                <div>
                                    <div class="mk-srcname">{{ $row['label'] }}</div>
                                    <div class="mk-srchint">{{ __('Bookings created at reception — no link needed') }}</div>
                                </div>
                                <div class="mk-chip">
                                    <div class="mk-chip-num">{{ number_format($row['count']) }}</div>
                                    <div class="mk-chip-lbl">{{ __('bookings') }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mk-srcrow">
                            <div class="mk-srchead">
                                <span class="mk-srcic" style="background:{{ $row['color'] }}1a;">@include('company.partials.source-icon', ['source' => $row['iconKey'], 'size' => 20])</span>
                                <div>
                                    <div class="mk-srcname">{{ $row['label'] }}</div>
                                    <div class="mk-srchint">
                                        @switch($sv)
                                            @case('instagram') {{ __('Copy this link into your Instagram bio') }} @break
                                            @case('facebook')  {{ __('Add it to your Facebook page') }} @break
                                            @case('whatsapp')  {{ __('Send it to customers on WhatsApp') }} @break
                                            @case('website')   {{ __('Add it to your website') }} @break
                                        @endswitch
                                    </div>
                                </div>
                                <div class="mk-chip">
                                    <div class="mk-chip-num">{{ number_format($row['count']) }}</div>
                                    <div class="mk-chip-lbl">{{ __('bookings') }}</div>
                                </div>
                            </div>
                            <div class="mk-linkrow">
                                <input class="mk-linkin" type="text" value="{{ $row['url'] }}" readonly onfocus="this.select();" aria-label="{{ $row['label'] }}">
                                <button type="button" class="btn btn-primary mk-copy" data-copy="{{ $row['url'] }}">
                                    <i data-feather="copy" style="width:13px;height:13px;vertical-align:-2px;"></i>
                                    <span class="mk-copy-text">{{ __('Copy') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Analytics --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span class="fw-bold" style="font-size:14px;">{{ __('Where bookings come from') }}</span>
                    <span class="text-muted d-block" style="font-size:11.5px;">{{ __('Total') }}: <strong>{{ number_format($total) }}</strong> {{ __('bookings') }}</span>
                </div>
                <div class="mk-range">
                    @foreach(['all' => __('All time'), 'today' => __('Today'), '7d' => __('Last 7 days'), '30d' => __('Last 30 days'), 'month' => __('This month')] as $rk => $rl)
                        <a href="{{ route('company.marketing.booking-sources', ['branch' => $selBranch->id, 'range' => $rk]) }}"
                           class="{{ $range === $rk ? 'active' : '' }}">{{ $rl }}</a>
                    @endforeach
                </div>
            </div>
            <div class="card-body p-0">
                @if($total === 0)
                    <div class="p-5 text-center">
                        <i data-feather="bar-chart-2" style="width:32px;height:32px;color:#cbd5e1;"></i>
                        <p class="text-muted tx-13 mb-0 mt-3">{{ __('No bookings in this period yet.') }}</p>
                    </div>
                @else
                    @foreach($rows as $row)
                        <div class="mk-anrow">
                            <span class="mk-an-ic" style="background:{{ $row['color'] }}1a;">@include('company.partials.source-icon', ['source' => $row['iconKey'], 'size' => 15])</span>
                            <div class="mk-an-name">
                                {{ $row['label'] }}
                                @if($row['source']->value === 'other')
                                    <i data-feather="help-circle" style="width:12px;height:12px;color:#cbd5e1;vertical-align:-1px;"
                                       title="{{ __('Bookings with no tracking link (direct or before tracking was set up)') }}"></i>
                                @endif
                            </div>
                            <div class="mk-an-bar">
                                <div class="mk-bar-track"><div class="mk-bar-fill" style="width:{{ $row['pct'] }}%;background:{{ $row['color'] }};"></div></div>
                            </div>
                            <div class="mk-an-num">{{ number_format($row['count']) }}</div>
                            <div class="mk-an-pct">{{ $row['pct'] }}%</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

    @endif

  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var done = @json(__('Copied'));

    document.querySelectorAll('.mk-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url  = btn.getAttribute('data-copy');
            var txt  = btn.querySelector('.mk-copy-text');
            var base = txt ? txt.textContent : '';

            var flash = function () {
                btn.classList.add('copied');
                if (txt) txt.textContent = done;
                setTimeout(function () {
                    btn.classList.remove('copied');
                    if (txt) txt.textContent = base;
                }, 1600);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(flash).catch(function () { legacy(url); flash(); });
            } else { legacy(url); flash(); }
        });
    });

    function legacy(text) {
        var ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
    }
})();
</script>
@endpush
