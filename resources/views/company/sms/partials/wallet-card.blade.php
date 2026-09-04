@php
    $allocated = (int) $wallet->total_purchased;
    $used      = (int) $wallet->total_used;
    $remaining = $wallet->remaining();
    $pct       = $allocated > 0 ? min(100, round($used / $allocated * 100)) : 0;
    $isPool    = $branch === null;
    $zero      = $remaining <= 0 && $allocated > 0;
    $low       = $wallet->isLow();
    $accent    = $zero ? 'var(--bk-danger)' : ($low ? 'var(--bk-warning)' : 'var(--bk-accent)');
    $boxId     = 'alert-' . $wallet->id;
@endphp
<div class="sx-card" style="border-color:{{ ($zero||$low) ? 'color-mix(in srgb,'.$accent.' 40%, var(--bk-border))' : 'var(--bk-border)' }};">
    <div class="sx-card-pad">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="sx-stat-ic" style="{{ $isPool ? 'background:var(--bk-gold-soft); color:var(--bk-gold-strong);' : '' }}">
                    <i data-feather="{{ $isPool ? 'layers' : 'map-pin' }}"></i>
                </span>
                <div>
                    <div class="sx-name">{{ $isPool ? __('Company pool') : $branch->localizedName() }}</div>
                    <div class="sx-sub">{{ $isPool ? __('Shared across branches') : __('Branch wallet') }}</div>
                </div>
            </div>
            <button type="button" class="sx-btn sx-btn-ghost sx-btn-sm" data-alert-toggle="{{ $boxId }}" title="{{ __('Alert settings') }}"><i data-feather="bell"></i></button>
        </div>

        {{-- 200 / 63 / 137 --}}
        <div style="display:flex; align-items:baseline; gap:8px; margin-top:16px;">
            <span style="font-family:var(--sx-display); font-size:2.4rem; font-weight:600; color:var(--bk-text); line-height:1; font-variant-numeric:tabular-nums;">{{ number_format($remaining) }}</span>
            <span class="sx-sub">{{ __('remaining') }}</span>
        </div>
        <div class="sx-meter" style="margin-top:14px;">
            <span class="sx-meter-fill {{ $low||$zero ? 'is-gold' : '' }}" style="width:{{ $pct }}%"></span>
        </div>
        <div class="sx-meter-legend" style="margin-top:12px;">
            <span class="sx-legend"><span class="sx-dot" style="background:var(--bk-accent)"></span>{{ __('Allocated') }} <strong class="sx-mono">&nbsp;{{ number_format($allocated) }}</strong></span>
            <span class="sx-legend"><span class="sx-dot" style="background:var(--bk-gold-strong)"></span>{{ __('Used') }} <strong class="sx-mono">&nbsp;{{ number_format($used) }}</strong></span>
        </div>

        @if($zero)
            <a href="{{ route('company.sms.purchase') }}" class="sx-btn sx-btn-primary sx-btn-sm" style="margin-top:14px; width:100%; justify-content:center;"><i data-feather="shopping-bag"></i>{{ __('Purchase SMS') }}</a>
        @elseif($low)
            <div class="sx-note sx-note-warn" style="margin-top:14px; font-size:.8rem;"><i data-feather="alert-triangle"></i><span>{{ __('Running low — top up soon.') }}</span></div>
        @endif

        {{-- Inline low-balance alert settings --}}
        <div id="{{ $boxId }}" style="display:none; margin-top:14px; padding-top:14px; border-top:1px dashed var(--bk-border);">
            <form method="POST" action="{{ route('company.sms.threshold') }}">
                @csrf @method('PUT')
                <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
                <div class="sx-field" style="margin-bottom:10px;">
                    <label>{{ __('Alert me at (SMS remaining)') }}</label>
                    <input type="number" name="low_balance_threshold" class="sx-input" min="0" step="1" value="{{ $wallet->low_balance_threshold }}">
                </div>
                <label style="display:flex; align-items:center; gap:9px; cursor:pointer; font-size:.85rem; color:var(--bk-text-soft); margin-bottom:12px;">
                    <input type="checkbox" name="notify_low_balance" value="1" @checked($wallet->notify_low_balance)>{{ __('Notify me in-app') }}
                </label>
                <button type="submit" class="sx-btn sx-btn-ghost sx-btn-sm" style="width:100%; justify-content:center;"><i data-feather="save"></i>{{ __('Save alert') }}</button>
            </form>
        </div>
    </div>
</div>
