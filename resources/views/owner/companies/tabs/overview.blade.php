{{--
    Company Workspace — Overview tab (REFERENCE PARTIAL).
    Layout-less: injected by the shell's lazy loader. Built only from the shared
    bk- component layer (see booksy-custom.css "COMPANY WORKSPACE" block) so it
    themes light/dark and is responsive with zero extra CSS. Other tab agents
    should mirror this structure: $company + $ws are always injected.
--}}
@php $tz = config('app.timezone'); @endphp

{{-- KPI row --}}
<div class="bk-ws-kpis mb-3">
    <div class="bk-kpi" data-accent="blue">
        <div class="bk-kpi-label"><i data-feather="calendar"></i> {{ __('Today') }}</div>
        <div class="bk-kpi-num">{{ number_format($kpis['appointments_today']) }}</div>
        <div class="bk-kpi-sub">{{ __('appointments') }}</div>
    </div>
    <div class="bk-kpi" data-accent="green">
        <div class="bk-kpi-label"><i data-feather="clock"></i> {{ __('Upcoming') }}</div>
        <div class="bk-kpi-num">{{ number_format($kpis['appointments_upcoming']) }}</div>
        <div class="bk-kpi-sub">{{ __('scheduled ahead') }}</div>
    </div>
    <div class="bk-kpi" data-accent="orange">
        <div class="bk-kpi-label"><i data-feather="alert-circle"></i> {{ __('Pending') }}</div>
        <div class="bk-kpi-num">{{ number_format($kpis['appointments_pending']) }}</div>
        <div class="bk-kpi-sub">{{ __('need action') }}</div>
    </div>
    <div class="bk-kpi" data-accent="gold">
        <div class="bk-kpi-label"><i data-feather="hash"></i> {{ __('All time') }}</div>
        <div class="bk-kpi-num">{{ number_format($kpis['appointments_total']) }}</div>
        <div class="bk-kpi-sub">{{ __('appointments') }}</div>
    </div>
</div>

<div class="row g-3">
    {{-- Recent appointments --}}
    <div class="col-lg-7">
        <div class="bk-card h-100">
            <div class="bk-card-head">
                <h3 class="bk-card-title"><i data-feather="calendar"></i> {{ __('Recent appointments') }}</h3>
                <a href="{{ $ws->url('appointments') }}" class="bk-btn bk-btn--sm bk-btn--ghost" data-ws-tab-link="appointments">
                    {{ __('View all') }} <i data-feather="arrow-right"></i>
                </a>
            </div>
            <div class="bk-card-body p0">
                @forelse ($recentAppointments as $appt)
                    @if ($loop->first)
                        <div class="bk-tbl-wrap"><table class="bk-tbl">
                            <thead><tr>
                                <th>{{ __('When') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Service') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr></thead><tbody>
                    @endif
                    <tr>
                        <td class="bk-tbl-num">{{ $appt->start_time?->timezone($tz)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="bk-tbl-strong">{{ $appt->customer?->name ?? '—' }}</td>
                        <td>{{ $appt->service?->localizedName() ?? '—' }}</td>
                        <td><x-appointment-status :status="$appt->status" /></td>
                    </tr>
                    @if ($loop->last)
                        </tbody></table></div>
                    @endif
                @empty
                    <div class="bk-empty">
                        <div class="bk-empty-ic"><i data-feather="calendar"></i></div>
                        <p>{{ __('No appointments yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Branches summary --}}
    <div class="col-lg-5">
        <div class="bk-card h-100">
            <div class="bk-card-head">
                <h3 class="bk-card-title"><i data-feather="map-pin"></i> {{ __('Branches') }}</h3>
                <a href="{{ $ws->url('branches') }}" class="bk-btn bk-btn--sm bk-btn--ghost" data-ws-tab-link="branches">
                    {{ __('Manage') }} <i data-feather="arrow-right"></i>
                </a>
            </div>
            <div class="bk-card-body p0">
                @forelse ($branches as $branch)
                    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><tbody>@endif
                    <tr>
                        <td class="bk-tbl-strong">
                            {{ $branch->localizedName() }}
                            @if ($branch->is_head_office)
                                <span class="bk-pill bk-pill--gold ms-1">{{ __('HQ') }}</span>
                            @endif
                        </td>
                        <td class="bk-tbl-num text-muted">{{ $branch->employees_count }} {{ __('staff') }}</td>
                        <td class="bk-tbl-num text-muted">{{ $branch->services_count }} {{ __('services') }}</td>
                    </tr>
                    @if ($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="bk-empty">
                        <div class="bk-empty-ic"><i data-feather="map-pin"></i></div>
                        <p>{{ __('No branches yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
