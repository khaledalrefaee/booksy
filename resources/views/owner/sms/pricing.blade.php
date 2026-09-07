@extends('owner.dashboard')
@section('content')

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Catalog') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Pricing') }}</h1>
            <p class="sx-subtitle">{{ __('The reference price of a single SMS credit. Used to value usage and as a baseline for packages.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('owner.sms.packages') }}" class="sx-btn sx-btn-ghost"><i data-feather="box"></i>{{ __('Packages') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    <div class="sx-grid sx-grid-2 sx-reveal">
        <div class="sx-card">
            <div class="sx-card-head"><h2 class="sx-card-title">{{ __('Price per SMS') }}</h2></div>
            <div class="sx-card-pad">
                <form method="POST" action="{{ route('owner.sms.pricing.update') }}">
                    @csrf @method('PUT')
                    <div class="sx-row">
                        <div class="sx-field">
                            <label>{{ __('Price per single SMS') }}</label>
                            <input type="number" name="price_per_sms" class="sx-input" min="0" step="0.01" value="{{ old('price_per_sms', $setting->price_per_sms) }}" required>
                            <p class="sx-hint">{{ __('One credit = one SMS segment.') }}</p>
                        </div>
                        <div class="sx-field">
                            <label>{{ __('Currency') }}</label>
                            <input type="text" name="currency" class="sx-input" maxlength="8" value="{{ old('currency', $setting->currency) }}" required>
                        </div>
                    </div>
                    <button type="submit" class="sx-btn sx-btn-primary"><i data-feather="save"></i>{{ __('Save pricing') }}</button>
                </form>
            </div>
        </div>

        <div class="sx-card">
            <div class="sx-card-head"><h2 class="sx-card-title">{{ __('Package value check') }}</h2></div>
            <div class="sx-card-pad">
                @if($packages->isEmpty())
                    <div class="sx-note sx-note-info"><i data-feather="info"></i><span>{{ __('No packages yet — create some to compare their effective per-SMS price against this baseline.') }}</span></div>
                @else
                    <div class="sx-table-scroll">
                        <table class="sx-table" style="min-width:0;">
                            <thead><tr><th>{{ __('Package') }}</th><th class="num">{{ __('Per SMS') }}</th><th class="num">{{ __('vs base') }}</th></tr></thead>
                            <tbody>
                            @foreach($packages as $p)
                                @php
                                    $per = $p->pricePerSms();
                                    $base = (float) $setting->price_per_sms;
                                    $diff = $base > 0 ? round(($per - $base) / $base * 100) : 0;
                                @endphp
                                <tr>
                                    <td><span class="sx-name">{{ $p->name }}</span><div class="sx-sub">{{ number_format($p->credits) }} {{ __('SMS') }}</div></td>
                                    <td class="num sx-mono">{{ number_format($per, 2) }}</td>
                                    <td class="num">
                                        <span class="sx-pill {{ $diff <= 0 ? 'sx-pill-sent' : 'sx-pill-skipped' }}">{{ $diff > 0 ? '+' : '' }}{{ $diff }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Rasel SMS sender name (platform-level). Local sends can go out under an
         APPROVED sender.id from Rasel; this is the provider transport identity,
         entirely separate from GlowRez credits/packages. --}}
    <div class="sx-card sx-reveal" style="margin-top:20px;">
        <div class="sx-card-head">
            <div>
                <h2 class="sx-card-title">{{ __('SMS sender name (Rasel)') }}</h2>
                <p class="sx-card-note">{{ __('The approved name local SMS is sent under. One choice for the whole platform.') }}</p>
            </div>
            <span class="sx-ref-tag"><i data-feather="send"></i>{{ __('Provider') }}</span>
        </div>
        <div class="sx-card-pad">
            @if(!($senders['ok'] ?? false))
                <div class="sx-note sx-note-warn">
                    <i data-feather="alert-circle"></i>
                    <span>{{ __('Approved senders are unavailable right now (Rasel not configured or unreachable). Sends will use the account default sender.') }}</span>
                </div>
                @if(!empty($setting->default_sender_id))
                    <div class="sx-sub" style="margin-top:10px;">
                        {{ __('Currently selected') }}: <strong>{{ $setting->default_sender_name ?: $setting->default_sender_id }}</strong>
                    </div>
                @endif
            @elseif(empty($senders['senders']))
                <div class="sx-note sx-note-info">
                    <i data-feather="info"></i>
                    <span>{{ __('No approved sender names yet. Request one below; sends use the account default until one is approved.') }}</span>
                </div>
            @else
                <form method="POST" action="{{ route('owner.sms.sender.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="default_sender_name" id="sx-sender-name" value="{{ $setting->default_sender_name }}">
                    <div class="sx-field">
                        <label>{{ __('Send under') }}</label>
                        <select name="default_sender_id" class="sx-input" onchange="document.getElementById('sx-sender-name').value = this.options[this.selectedIndex].dataset.name || '';">
                            <option value="" data-name="">{{ __('Account default sender') }}</option>
                            @foreach($senders['senders'] as $s)
                                <option value="{{ $s['id'] }}" data-name="{{ $s['name'] }}" @selected($setting->default_sender_id === $s['id'])>
                                    {{ $s['name'] }}@if($s['is_default']) · {{ __('default') }}@endif ({{ $s['status'] }})
                                </option>
                            @endforeach
                        </select>
                        <p class="sx-hint">{{ __('Only approved senders can deliver. Leaving this on the account default is fine.') }}</p>
                    </div>
                    <button type="submit" class="sx-btn sx-btn-primary"><i data-feather="save"></i>{{ __('Save sender') }}</button>
                </form>
            @endif

            @if(!empty($allowedTypes))
                <div class="sx-note sx-note-info" style="margin-top:14px;">
                    <i data-feather="check-circle"></i>
                    <span>{{ __('Local SMS allows these message types') }}: <strong>{{ implode(' · ', $allowedTypes) }}</strong></span>
                </div>
            @endif

            <hr style="border:none;border-top:1px solid var(--bk-border);margin:18px 0;">

            <form method="POST" action="{{ route('owner.sms.sender.request') }}">
                @csrf
                <div class="sx-field">
                    <label>{{ __('Request a new sender name') }}</label>
                    <input type="text" name="name" class="sx-input" maxlength="191" placeholder="MyBrand" required>
                    <p class="sx-hint">{{ __('Submitted to Rasel for review. Approval is handled on the Rasel side.') }}</p>
                </div>
                <button type="submit" class="sx-btn sx-btn-ghost"><i data-feather="plus"></i>{{ __('Submit for review') }}</button>
            </form>
        </div>
    </div>
</div>

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@endsection
