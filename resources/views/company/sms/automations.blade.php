@extends('company.dashboard')
@section('content')

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('company.sms.overview') }}">{{ __('SMS') }}</a>
                <span aria-hidden="true">·</span> {{ __('Setup') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Automations') }}</h1>
            <p class="sx-subtitle">{{ __('Choose which SMS each branch sends automatically. Every option is independent — turn on only what you need.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('company.sms.templates') }}" class="sx-btn sx-btn-ghost"><i data-feather="edit-3"></i>{{ __('Templates') }}</a>
        </div>
    </header>

    @include('company.partials.flash')

    <div class="sx-note sx-note-info sx-reveal" style="margin-bottom:18px;">
        <i data-feather="info"></i>
        <span>{{ __('Automations only send to numbers on the local SMS network and only while the branch has credits. Confirmation and reminder are transactional; follow-up is marketing.') }}</span>
    </div>

    @if($branches->isEmpty())
        <div class="sx-card sx-reveal">
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="map-pin"></i></span>
                <h3 class="sx-empty-title">{{ __('No branches yet') }}</h3>
                <p class="sx-empty-text">{{ __('Create a branch first, then set up its SMS automations here.') }}</p>
            </div>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:16px;">
        @foreach($branches as $branch)
            @php $s = $settings[$branch->id] ?? null; @endphp
            <div class="sx-card sx-reveal">
                <form method="POST" action="{{ route('company.sms.automations.update', $branch) }}">
                    @csrf @method('PUT')
                    <div class="sx-card-head">
                        <div>
                            <h2 class="sx-card-title">{{ $branch->localizedName() }}</h2>
                            <p class="sx-card-note">{{ __('Automations for this branch') }}</p>
                        </div>
                        <button type="submit" class="sx-btn sx-btn-primary sx-btn-sm"><i data-feather="save"></i>{{ __('Save') }}</button>
                    </div>
                    <div class="sx-card-pad" style="padding-top:4px; padding-bottom:8px;">

                        {{-- Confirmation --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="check-circle"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Appointment Confirmation') }}</div>
                                <div class="sx-auto-desc">{{ __('Sent as soon as a booking is created.') }}</div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="confirmation_enabled" value="1" @checked($s?->confirmation_enabled)>
                                <span class="sx-slider"></span>
                            </label>
                        </div>

                        {{-- Reminder --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="clock"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Appointment Reminder') }}</div>
                                <div class="sx-auto-desc">{{ __('Sent before the appointment starts.') }}</div>
                                <div class="sx-auto-field">
                                    <input type="number" name="reminder_offset_minutes" min="5" max="1440" step="5" value="{{ $s?->reminder_offset_minutes ?? 60 }}">
                                    <label>{{ __('minutes before') }}</label>
                                </div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="reminder_enabled" value="1" @checked($s?->reminder_enabled)>
                                <span class="sx-slider"></span>
                            </label>
                        </div>

                        {{-- Follow-up --}}
                        <div class="sx-auto-row">
                            <span class="sx-auto-ic"><i data-feather="refresh-cw"></i></span>
                            <div class="sx-auto-body">
                                <div class="sx-auto-title">{{ __('Customer Follow-up') }}</div>
                                <div class="sx-auto-desc">{{ __('A win-back message after the last visit.') }}</div>
                                <div class="sx-auto-field">
                                    <input type="number" name="followup_days" min="1" max="365" step="1" value="{{ $s?->followup_days ?? 15 }}">
                                    <label>{{ __('days after last visit') }}</label>
                                </div>
                            </div>
                            <label class="sx-switch">
                                <input type="checkbox" name="followup_enabled" value="1" @checked($s?->followup_enabled)>
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
@endpush

@endsection
