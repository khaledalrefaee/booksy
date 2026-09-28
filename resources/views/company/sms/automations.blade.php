@extends('company.dashboard')
@section('content')

{{-- Customer messages — the ONE place that decides which automatic messages a
     customer gets. The channel is never a setting: local numbers get SMS (from
     the branch's SMS balance), every other number gets WhatsApp. --}}
<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">{{ __('Settings') }}</div>
            <h1 class="sx-title">{{ __('Customer messages') }}</h1>
            <p class="sx-subtitle">{{ __('Choose which messages your customers receive automatically. The channel is picked for you: local numbers get SMS from your branch balance, other numbers get WhatsApp.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('company.sms.templates') }}" class="sx-btn sx-btn-ghost"><i data-feather="edit-3"></i>{{ __('Message templates') }}</a>
            <a href="{{ route('company.sms.overview') }}" class="sx-btn sx-btn-ghost"><i data-feather="credit-card"></i>{{ __('SMS balance') }}</a>
        </div>
    </header>

    @include('company.partials.flash')

    <div class="sx-note sx-note-info sx-reveal" style="margin-bottom:18px;">
        <i data-feather="info"></i>
        <span>{{ __('When you confirm, move or cancel a booking, the customer is always told — no setting needed.') }}</span>
    </div>

    @if($branches->isEmpty())
        <div class="sx-card sx-reveal">
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="map-pin"></i></span>
                <h3 class="sx-empty-title">{{ __('No branches yet') }}</h3>
                <p class="sx-empty-text">{{ __('Create a branch first, then choose its customer messages here.') }}</p>
            </div>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:16px;">
        @foreach($branches as $branch)
            @php
                $s       = $settings[$branch->id];
                $offsets = \App\Models\SmsAutomationSetting::REMINDER_OFFSETS;
                $cur     = (int) $s->reminder_offset_minutes;
                if (! in_array($cur, $offsets, true)) { $offsets[] = $cur; sort($offsets); }
            @endphp
            <div class="sx-card sx-reveal">
                <form method="POST" action="{{ route('company.sms.automations.update', $branch) }}">
                    @csrf @method('PUT')
                    <div class="sx-card-head">
                        <div>
                            <h2 class="sx-card-title">{{ $branch->localizedName() }}</h2>
                            <p class="sx-card-note">{{ __('Messages for this branch') }}</p>
                        </div>
                        <button type="submit" class="sx-btn sx-btn-primary sx-btn-sm"><i data-feather="save"></i>{{ __('Save') }}</button>
                    </div>
                    <div class="sx-card-pad" style="padding-top:4px; padding-bottom:8px;">

                        {{-- On booking --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="check-circle"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Booking message') }}</div>
                                <div class="sx-auto-desc">{{ __('Sent as soon as a booking is made, with its date and time.') }}</div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="confirmation_enabled" value="1" @checked($s->confirmation_enabled)>
                                <span class="sx-slider"></span>
                            </label>
                        </div>

                        {{-- Reminder --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="clock"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Reminder before the appointment') }}</div>
                                <div class="sx-auto-desc">{{ __('Sent once, at the time you choose.') }}</div>
                                <div class="sx-auto-field">
                                    <select name="reminder_offset_minutes" class="sx-auto-select" aria-label="{{ __('Reminder time') }}">
                                        @foreach($offsets as $m)
                                            <option value="{{ $m }}" @selected($m === $cur)>{{ __(':time before', ['time' => \App\Support\BranchSettings::durationLabel($m)]) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="reminder_enabled" value="1" @checked($s->reminder_enabled)>
                                <span class="sx-slider"></span>
                            </label>
                        </div>

                        {{-- Ask to confirm attendance --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="user-check"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Ask the customer to confirm attendance') }}</div>
                                <div class="sx-auto-desc">{{ __('Adds "confirm" and "cancel" links to the booking message and the reminder.') }}</div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="ask_confirmation" value="1" @checked($s->ask_confirmation)>
                                <span class="sx-slider"></span>
                            </label>
                        </div>

                        {{-- Follow-up --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="refresh-cw"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Follow-up after the visit') }}</div>
                                <div class="sx-auto-desc">{{ __('Invites the customer back if they haven\'t booked again.') }}</div>
                                <div class="sx-auto-field">
                                    <input type="number" name="followup_days" min="1" max="365" step="1" value="{{ $s->followup_days }}">
                                    <label>{{ __('days after last visit') }}</label>
                                </div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="followup_enabled" value="1" @checked($s->followup_enabled)>
                                <span class="sx-slider"></span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
        </div>
    @endif
</div>

@push('company-styles')
    @include('company.sms.partials.styles')
    <style>
        .sx-auto-select { height:38px; padding:0 12px; border-radius:10px; border:1px solid var(--bk-border);
            background:var(--bk-bg); color:var(--bk-text); font-size:.88rem; outline:none; min-width:170px; }
        .sx-auto-select:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
    </style>
@endpush

@endsection
