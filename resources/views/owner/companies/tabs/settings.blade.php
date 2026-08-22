{{-- Company Workspace — Settings tab --}}
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="subscription">{{ __('Subscription') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="policy">{{ __('Booking policy') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="resources">{{ __('Resources') }}</button>
        @can('owner-can', 'billing.view')
        <button type="button" class="bk-subnav-item" data-ws-subnav="payments">{{ __('Payments') }}</button>
        @endcan
        @can('owner-can', 'audit-log.view')
        <button type="button" class="bk-subnav-item" data-ws-subnav="audit">{{ __('Audit log') }}</button>
        @endcan
    </div>

    {{-- ══ Subscription ══ --}}
    <div data-ws-panel="subscription">
        @can('owner-can', 'companies.manage')
        <form action="{{ route('owner.companies.update-subscription', $company) }}" method="POST">
            @csrf @method('PATCH')
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="bk-card h-100"><div class="bk-card-body">
                        <h3 class="bk-card-title mb-3"><i data-feather="package"></i> {{ __('Plan & billing') }}</h3>
                        <div class="bk-field">
                            <label>{{ __('Plan') }}</label>
                            <select name="plan_id" class="bk-select">
                                <option value="">{{ __('No plan — full access (legacy)') }}</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected($company->plan_id === $plan->id)>
                                        {{ $plan->localizedName() }}
                                        ({{ (float) $plan->price === 0.0 ? __('Free') : number_format((float) $plan->price, 2).' '.$plan->currency }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bk-field">
                            <label>{{ __('Expires at') }}</label>
                            <input type="date" name="plan_expires_at" class="bk-input" value="{{ $company->plan_expires_at?->format('Y-m-d') }}">
                        </div>
                        @if ($company->plan_id !== null)
                            <div class="bk-pill bk-pill--{{ $company->isSubscriptionActive() ? 'green' : 'red' }}">
                                {{ $company->isSubscriptionActive() ? __('Subscription is active.') : __('Subscription expired — gated features are disabled.') }}
                            </div>
                        @endif
                    </div></div>
                </div>
                <div class="col-lg-7">
                    <div class="bk-card h-100"><div class="bk-card-body">
                        <h3 class="bk-card-title mb-1"><i data-feather="sliders"></i> {{ __('Feature overrides') }}</h3>
                        <p class="text-muted small mb-3">{{ __('Overrides win over the plan — use them to grant or block a single feature for this company only.') }}</p>
                        @php $overrides = $company->feature_overrides ?? []; @endphp
                        <div class="row g-2">
                            @foreach ($featureCatalog as $key => $f)
                                @php $current = $overrides[$key] ?? null; $planHas = $company->plan?->hasFeature($key); @endphp
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center justify-content-between" style="border:1px solid var(--bk-border);border-radius:10px;padding:8px 12px;">
                                        <span class="small d-flex align-items-center gap-2"><i data-feather="{{ $f['icon'] }}" style="width:15px;height:15px;"></i>{{ $f['label'] }}</span>
                                        <select name="overrides[{{ $key }}]" class="bk-select" style="width:auto;">
                                            <option value="" @selected($current === null)>{{ __('Plan default') }}{{ $company->plan_id !== null ? ' ('.($planHas ? __('On') : __('Off')).')' : '' }}</option>
                                            <option value="1" @selected($current === true)>{{ __('Enabled') }}</option>
                                            <option value="0" @selected($current === false)>{{ __('Disabled') }}</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div></div>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="bk-btn bk-btn--primary"><i data-feather="save"></i> {{ __('Save subscription') }}</button>
                </div>
            </div>
        </form>
        @else
            <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div><p>{{ __('You do not have permission to manage subscriptions.') }}</p></div>
        @endcan
    </div>

    {{-- ══ Booking policy (read-only summary) ══ --}}
    <div data-ws-panel="policy" style="display:none;">
        <div class="bk-card"><div class="bk-card-body">
            <h3 class="bk-card-title mb-3"><i data-feather="shield"></i> {{ __('Booking & cancellation policy') }}</h3>
            <dl class="bk-dl">
                <dt>{{ __('Cancellation window') }}</dt><dd>{{ $companyPolicy->cancellation_window_hours }} {{ __('hours') }}</dd>
                <dt>{{ __('Late grace') }}</dt><dd>{{ $companyPolicy->late_grace_minutes }} {{ __('min') }}</dd>
                <dt>{{ __('Require confirmation') }}</dt><dd>{{ $companyPolicy->require_confirmation ? __('Yes') : __('No') }}</dd>
                <dt>{{ __('Reminder channel') }}</dt><dd>{{ __($companyPolicy->reminder_channel ?? '—') }}</dd>
                <dt>{{ __('No-show protection') }}</dt><dd>{{ $companyPolicy->protection_enabled ? __('Enabled') : __('Disabled') }}</dd>
                <dt>{{ __('Deposit required') }}</dt><dd>{{ $companyPolicy->deposit_enabled ? __('Yes') : __('No') }}</dd>
            </dl>
            <div class="mt-3">
                <form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
                    @csrf<button type="submit" class="bk-btn bk-btn--gold"><i data-feather="external-link"></i> {{ __('Edit in full editor') }}</button>
                </form>
            </div>
        </div></div>
    </div>

    {{-- ══ Resources ══ --}}
    <div data-ws-panel="resources" style="display:none;">
        <div class="bk-card"><div class="bk-card-head"><h3 class="bk-card-title"><i data-feather="box"></i> {{ __('Resources') }}</h3></div>
        <div class="bk-card-body p0">
            @forelse ($resources as $r)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Active') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $r->localizedName() }}</td>
                    <td>{{ __($r->type) }}</td>
                    <td>{{ $r->branch?->localizedName() ?? '—' }}</td>
                    <td>@if($r->is_active)<span class="bk-pill bk-pill--green">{{ __('Yes') }}</span>@else<span class="bk-pill bk-pill--muted">{{ __('No') }}</span>@endif</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="box"></i></div><p>{{ __('No resources yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- ══ Payments ══ --}}
    @can('owner-can', 'billing.view')
    <div data-ws-panel="payments" style="display:none;">
        <div class="bk-card"><div class="bk-card-head">
            <h3 class="bk-card-title"><i data-feather="dollar-sign"></i> {{ __('Subscription payments') }}</h3>
            <a href="{{ route('owner.subscription-payments.index', ['company_id' => $company->id]) }}" class="bk-btn bk-btn--sm bk-btn--ghost">{{ __('Full ledger') }} <i data-feather="arrow-right"></i></a>
        </div>
        <div class="bk-card-body p0">
            @forelse ($payments as $p)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Paid at') }}</th><th>{{ __('Plan') }}</th><th class="bk-tbl-num">{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Reference') }}</th></tr></thead><tbody>@endif
                <tr @class(['is-voided' => $p->isVoided()]) style="{{ $p->isVoided() ? 'opacity:.5' : '' }}">
                    <td class="bk-tbl-num">{{ $p->paid_at->format('Y-m-d') }}@if($p->isVoided())<span class="bk-pill bk-pill--red ms-1">{{ __('Voided') }}</span>@endif</td>
                    <td>{{ $p->plan_label ?? '—' }}</td>
                    <td class="bk-tbl-num bk-tbl-strong">{{ number_format((float) $p->amount, 2) }} <span class="text-muted">{{ $p->currency }}</span></td>
                    <td><span class="bk-pill bk-pill--muted">{{ \App\Models\SubscriptionPayment::methods()[$p->method] ?? $p->method }}</span></td>
                    <td>{{ $p->reference ?: '—' }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="dollar-sign"></i></div><p>{{ __('No payments recorded yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>
    @endcan

    {{-- ══ Audit ══ --}}
    @can('owner-can', 'audit-log.view')
    <div data-ws-panel="audit" style="display:none;">
        <div class="bk-card"><div class="bk-card-head"><h3 class="bk-card-title"><i data-feather="shield"></i> {{ __('Audit log') }}</h3></div>
        <div class="bk-card-body p0">
            @forelse ($auditLogs as $log)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Admin') }}</th><th>{{ __('Action') }}</th><th>{{ __('Reason') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-num">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->owner?->name ?? __('Deleted admin') }}</td>
                    <td><span class="bk-pill bk-pill--blue">{{ $log->action }}</span></td>
                    <td>{{ $log->reason ?: '—' }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="shield"></i></div><p>{{ __('No audit entries yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>
    @endcan
</div>
