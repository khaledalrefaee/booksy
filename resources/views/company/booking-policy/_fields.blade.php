{{--
    Reusable booking-policy fieldset.
    @param string $prefix  name prefix, e.g. "unified" or "branch[5]"
    @param string $idp     unique id prefix for this instance, e.g. "u" or "b5"
    @param \App\Models\BookingPolicy $p  values to display
--}}
<div class="js-policy" data-idp="{{ $idp }}">

    {{-- ① Cancellation window ─────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="bk-icon-gold d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px;height:34px;">
                    <i data-feather="rotate-ccw" style="width:16px;height:16px;"></i>
                </span>
                <h6 class="bk-t-card mb-0">{{ __('Cancellation window') }}</h6>
            </div>

            <label class="form-label small fw-semibold mb-1" for="{{ $idp }}_cw">
                {{ __('Customer can cancel or reschedule on their own until') }}
            </label>
            <select class="form-select rounded-3" id="{{ $idp }}_cw" name="{{ $prefix }}[cancellation_window_hours]" style="max-width:280px;">
                @foreach ([48 => __(':n hours before', ['n' => 48]), 24 => __(':n hours before', ['n' => 24]), 12 => __(':n hours before', ['n' => 12]), 6 => __(':n hours before', ['n' => 6]), 2 => __(':n hours before', ['n' => 2]), 0 => __('Self-cancel not allowed')] as $val => $lbl)
                    <option value="{{ $val }}" @selected((int) $p->cancellation_window_hours === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <p class="text-muted small mb-0 mt-2">
                <i data-feather="info" style="width:13px;height:13px;" class="me-1"></i>
                {{ __('Cancelling after this point is recorded as a "late cancellation".') }}
            </p>
        </div>
    </div>

    {{-- ② Lateness & attendance ───────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="bk-icon-gold d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px;height:34px;">
                    <i data-feather="clock" style="width:16px;height:16px;"></i>
                </span>
                <h6 class="bk-t-card mb-0">{{ __('Lateness & attendance') }}</h6>
            </div>

            <label class="form-label small fw-semibold mb-1" for="{{ $idp }}_grace">
                {{ __('Late-arrival grace period') }}
                <span class="badge bk-badge-gold ms-1"><span class="js-grace-out" style="font-variant-numeric:tabular-nums;">{{ $p->late_grace_minutes }}</span> {{ __('minutes') }}</span>
            </label>
            <input type="range" class="form-range js-grace" id="{{ $idp }}_grace" min="5" max="30" step="5"
                   name="{{ $prefix }}[late_grace_minutes]" value="{{ $p->late_grace_minutes }}" style="max-width:340px;display:block;">

            <div class="mt-3">
                <span class="form-label small fw-semibold d-block mb-2">{{ __('After the grace period') }}</span>
                <div class="d-flex flex-column gap-2">
                    <label class="bk-radio-tile d-flex align-items-center gap-2 p-2 rounded-3 border">
                        <input class="form-check-input mt-0" type="radio" name="{{ $prefix }}[late_action]" value="staff_decides" @checked($p->late_action !== 'auto_cancel')>
                        <span class="small">{{ __('Staff decides (system suggests no-show)') }} <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ __('Recommended') }}</span></span>
                    </label>
                    <label class="bk-radio-tile d-flex align-items-center gap-2 p-2 rounded-3 border">
                        <input class="form-check-input mt-0" type="radio" name="{{ $prefix }}[late_action]" value="auto_cancel" @checked($p->late_action === 'auto_cancel')>
                        <span class="small">{{ __('Automatically suggest cancelling the slot') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- ③ Reminders ───────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="bk-icon-gold d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px;height:34px;">
                    <i data-feather="bell" style="width:16px;height:16px;"></i>
                </span>
                <h6 class="bk-t-card mb-0">{{ __('Reminders') }}</h6>
                <span class="badge bk-badge-gold ms-auto">{{ __('Biggest impact') }}</span>
            </div>

            <span class="form-label small fw-semibold d-block mb-2">{{ __('Channel') }}</span>
            <div class="btn-group mb-3" role="group">
                <input type="radio" class="btn-check" name="{{ $prefix }}[reminder_channel]" id="{{ $idp }}_ch_wa" value="whatsapp" @checked($p->reminder_channel !== 'sms')>
                <label class="btn btn-outline-primary rounded-start-3" for="{{ $idp }}_ch_wa">
                    <i data-feather="message-circle" style="width:14px;height:14px;" class="me-1"></i>{{ __('WhatsApp') }}
                </label>
                <input type="radio" class="btn-check" name="{{ $prefix }}[reminder_channel]" id="{{ $idp }}_ch_sms" value="sms" @checked($p->reminder_channel === 'sms')>
                <label class="btn btn-outline-primary rounded-end-3" for="{{ $idp }}_ch_sms">
                    <i data-feather="smartphone" style="width:14px;height:14px;" class="me-1"></i>{{ __('SMS') }}
                </label>
            </div>

            <span class="form-label small fw-semibold d-block mb-2">{{ __('Send a reminder') }}</span>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="{{ $idp }}_rb" name="{{ $prefix }}[reminder_on_booking]" value="1" @checked($p->reminder_on_booking)>
                    <label class="form-check-label small" for="{{ $idp }}_rb">{{ __('On booking') }}</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="{{ $idp }}_r24" name="{{ $prefix }}[reminder_24h]" value="1" @checked($p->reminder_24h)>
                    <label class="form-check-label small" for="{{ $idp }}_r24">{{ __('24 hours before') }}</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="{{ $idp }}_r3" name="{{ $prefix }}[reminder_3h]" value="1" @checked($p->reminder_3h)>
                    <label class="form-check-label small" for="{{ $idp }}_r3">{{ __('3 hours before') }}</label>
                </div>
            </div>

            <div class="form-check form-switch mb-2">
                <input class="form-check-input js-reveal-toggle" type="checkbox" role="switch"
                       id="{{ $idp }}_rc" name="{{ $prefix }}[require_confirmation]" value="1"
                       data-reveal="{{ $idp }}_rc_wrap" @checked($p->require_confirmation)>
                <label class="form-check-label small fw-semibold" for="{{ $idp }}_rc">
                    {{ __('Ask the customer to confirm attendance') }}
                </label>
            </div>
            <div id="{{ $idp }}_rc_wrap" class="ps-4 pt-1" @if(! $p->require_confirmation) hidden @endif>
                <label class="form-label small mb-1" for="{{ $idp }}_cd">{{ __("If not confirmed before") }}</label>
                <select class="form-select form-select-sm rounded-3" id="{{ $idp }}_cd" name="{{ $prefix }}[confirmation_deadline_hours]" style="max-width:240px;">
                    @foreach ([12 => __(':n hours', ['n' => 12]), 6 => __(':n hours', ['n' => 6]), 3 => __(':n hours', ['n' => 3]), 0 => __('Do not release')] as $val => $lbl)
                        <option value="{{ $val }}" @selected((int) $p->confirmation_deadline_hours === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <p class="text-muted small mb-0 mt-1">{{ __('The slot is then flagged for staff and offered to the waiting list.') }}</p>
            </div>
        </div>
    </div>

    {{-- ④ No-show protection (advanced, collapsed) ─────────── --}}
    <details class="card border-0 shadow-sm rounded-4 mb-3 bk-details">
        <summary class="card-body p-4 d-flex align-items-center gap-2" style="cursor:pointer;list-style:none;">
            <span class="bk-icon-gold d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px;height:34px;">
                <i data-feather="shield" style="width:16px;height:16px;"></i>
            </span>
            <span>
                <span class="bk-t-card d-block">{{ __('No-show protection') }}</span>
                <span class="text-muted small">{{ __('Smart rules for repeat no-shows — optional') }}</span>
            </span>
            <i data-feather="chevron-down" class="ms-auto bk-details-chevron" style="width:18px;height:18px;"></i>
        </summary>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input js-reveal-toggle" type="checkbox" role="switch"
                       id="{{ $idp }}_pe" name="{{ $prefix }}[protection_enabled]" value="1"
                       data-reveal="{{ $idp }}_pe_wrap" @checked($p->protection_enabled)>
                <label class="form-check-label small fw-semibold" for="{{ $idp }}_pe">{{ __('Enable no-show protection') }}</label>
            </div>

            <div id="{{ $idp }}_pe_wrap" @if(! $p->protection_enabled) hidden @endif>
                <div class="p-3 rounded-3 mb-3" style="background:var(--bk-surface-2);">
                    <span class="small d-block mb-2">{{ __('When a customer reaches') }}</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <select class="form-select form-select-sm rounded-3" name="{{ $prefix }}[offense_threshold]" style="width:auto;">
                            @foreach ([1, 2, 3, 4] as $t)
                                <option value="{{ $t }}" @selected((int) $p->offense_threshold === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                        <span class="small">{{ __('no-shows / late cancellations within') }}</span>
                        <select class="form-select form-select-sm rounded-3" name="{{ $prefix }}[offense_window_days]" style="width:auto;">
                            @foreach ([30 => __(':n days', ['n' => 30]), 60 => __(':n days', ['n' => 60]), 90 => __(':n days', ['n' => 90]), 180 => __(':n days', ['n' => 180])] as $val => $lbl)
                                <option value="{{ $val }}" @selected((int) $p->offense_window_days === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <span class="form-label small fw-semibold d-block mb-2">{{ __('Then, for their future bookings') }}</span>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="{{ $idp }}_as" name="{{ $prefix }}[action_alert_staff]" value="1" @checked($p->action_alert_staff)>
                    <label class="form-check-label small" for="{{ $idp }}_as">{{ __('Alert staff (shown only to your team)') }}</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="{{ $idp }}_mc" name="{{ $prefix }}[action_manual_confirm]" value="1" @checked($p->action_manual_confirm)>
                    <label class="form-check-label small" for="{{ $idp }}_mc">{{ __('Require manual approval before the booking is confirmed') }}</label>
                </div>

                {{-- Deposit (off by default) --}}
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input js-reveal-toggle" type="checkbox" role="switch"
                           id="{{ $idp }}_de" name="{{ $prefix }}[deposit_enabled]" value="1"
                           data-reveal="{{ $idp }}_de_wrap" @checked($p->deposit_enabled)>
                    <label class="form-check-label small" for="{{ $idp }}_de">
                        {{ __('Require a deposit') }}
                        <span class="text-muted">— {{ __('most cash-based salons leave this off') }}</span>
                    </label>
                </div>
                <div id="{{ $idp }}_de_wrap" class="ps-4 pt-1" @if(! $p->deposit_enabled) hidden @endif>
                    <div class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label small mb-1">{{ __('Amount') }}</label>
                            <div class="input-group input-group-sm" style="width:150px;">
                                <input type="number" min="0" step="1" class="form-control rounded-start-3" name="{{ $prefix }}[deposit_amount]" value="{{ (int) $p->deposit_amount }}">
                            </div>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="{{ $prefix }}[deposit_type]" id="{{ $idp }}_dt_fixed" value="fixed" @checked($p->deposit_type !== 'percent')>
                            <label class="btn btn-outline-secondary rounded-start-3" for="{{ $idp }}_dt_fixed">{{ __('Fixed') }}</label>
                            <input type="radio" class="btn-check" name="{{ $prefix }}[deposit_type]" id="{{ $idp }}_dt_pct" value="percent" @checked($p->deposit_type === 'percent')>
                            <label class="btn btn-outline-secondary rounded-end-3" for="{{ $idp }}_dt_pct">%</label>
                        </div>
                        <div>
                            <label class="form-label small mb-1">{{ __('Applies to') }}</label>
                            <select class="form-select form-select-sm rounded-3" name="{{ $prefix }}[deposit_scope]" style="width:auto;">
                                <option value="at_risk" @selected($p->deposit_scope === 'at_risk')>{{ __('At-risk customers only') }}</option>
                                <option value="new" @selected($p->deposit_scope === 'new')>{{ __('New customers') }}</option>
                                <option value="all" @selected($p->deposit_scope === 'all')>{{ __('All customers') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </details>

    {{-- ⑤ Message templates (advanced, collapsed) ──────────── --}}
    <details class="card border-0 shadow-sm rounded-4 mb-3 bk-details">
        <summary class="card-body p-4 d-flex align-items-center gap-2" style="cursor:pointer;list-style:none;">
            <span class="bk-icon-gold d-inline-flex align-items-center justify-content-center rounded-3" style="width:34px;height:34px;">
                <i data-feather="edit-3" style="width:16px;height:16px;"></i>
            </span>
            <span>
                <span class="bk-t-card d-block">{{ __('Message templates') }}</span>
                <span class="text-muted small">{{ __('Leave blank to use the friendly defaults') }}</span>
            </span>
            <i data-feather="chevron-down" class="ms-auto bk-details-chevron" style="width:18px;height:18px;"></i>
        </summary>
        <div class="card-body px-4 pb-4 pt-0">
            <p class="text-muted small mb-3">
                {{ __('Available variables:') }}
                <code class="bk-badge-gold px-2 py-1 rounded-2">{name}</code>
                <code class="bk-badge-gold px-2 py-1 rounded-2">{time}</code>
                <code class="bk-badge-gold px-2 py-1 rounded-2">{service}</code>
                <code class="bk-badge-gold px-2 py-1 rounded-2">{link}</code>
            </p>
            @foreach ([
                'msg_confirm'      => __('Booking confirmation'),
                'msg_reminder_24h' => __('Reminder — 24 hours before'),
                'msg_reminder_3h'  => __('Reminder — 3 hours before'),
                'msg_unconfirmed'  => __('Not confirmed in time'),
            ] as $field => $label)
                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
                    <textarea class="form-control rounded-3" rows="2" name="{{ $prefix }}[{{ $field }}]" placeholder="{{ __('Using default message…') }}">{{ $p->{$field} }}</textarea>
                </div>
            @endforeach
        </div>
    </details>

</div>
