{{-- Company Workspace — Appointments tab --}}
@php $tz = config('app.timezone'); @endphp
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="appts">{{ __('Appointments') }} <span class="bk-pill bk-pill--muted">{{ $appointments->count() }}</span></button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="waitlist">{{ __('Waitlist') }} <span class="bk-pill bk-pill--muted">{{ $waitlist->count() }}</span></button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="blocked">{{ __('Blocked times') }}</button>
    </div>

    {{-- Appointments --}}
    <div data-ws-panel="appts">
        <div class="bk-toolbar">
            <div class="bk-filter-tabs" id="bk-appt-filter">
                @foreach (['all' => __('All'), 'pending' => __('Pending'), 'confirmed' => __('Confirmed'), 'completed' => __('Completed')] as $val => $lbl)
                    <button type="button" class="bk-filter-tab {{ $val === 'all' ? 'active' : '' }}"
                            onclick="(function(b){document.querySelectorAll('#bk-appt-filter .bk-filter-tab').forEach(x=>x.classList.remove('active'));b.classList.add('active');var f=b.dataset.f;document.querySelectorAll('#bk-appt-rows tr').forEach(function(r){r.style.display=(f==='all'||r.dataset.status===f)?'':'none';});})(this)"
                            data-f="{{ $val }}">{{ $lbl }}</button>
                @endforeach
            </div>
        </div>
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($appointments as $appt)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                    <th>{{ __('When') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Service') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Status') }}</th><th class="bk-tbl-actions"></th>
                </tr></thead><tbody id="bk-appt-rows">@endif
                <tr class="is-clickable" data-status="{{ $appt->status?->value ?? $appt->status }}"
                    data-ws-drawer="{{ $ws->url('appointments').'/'.$appt->id.'/detail' }}" data-ws-drawer-title="{{ __('Appointment') }} #{{ $appt->id }}">
                    <td class="bk-tbl-num">{{ $appt->start_time?->timezone($tz)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $appt->branch?->localizedName() ?? '—' }}</td>
                    <td>{{ $appt->service?->localizedName() ?? '—' }}</td>
                    <td class="bk-tbl-strong">{{ $appt->customer?->name ?? $appt->customer_name ?? '—' }}</td>
                    <td><x-appointment-status :status="$appt->status" /></td>
                    <td class="bk-tbl-actions"><i data-feather="chevron-right"></i></td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="calendar"></i></div><p>{{ __('No appointments yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Waitlist --}}
    <div data-ws-panel="waitlist" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($waitlist as $e)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                    <th>{{ __('Customer') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Service') }}</th><th>{{ __('Status') }}</th><th class="bk-tbl-actions">{{ __('Resolve') }}</th>
                </tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $e->customer?->name ?? $e->customer_name ?? '—' }}</td>
                    <td>{{ $e->branch?->localizedName() ?? '—' }}</td>
                    <td>{{ $e->service?->localizedName() ?? '—' }}</td>
                    <td><span class="bk-pill bk-pill--muted">{{ __($e->status) }}</span></td>
                    <td class="bk-tbl-actions">
                        @can('owner-can', 'appointments.manage')
                        <form action="{{ $ws->url('appointments').'/waitlist/'.$e->id.'/resolve' }}" method="post" data-ws-action class="d-inline-flex gap-1">
                            @csrf @method('PATCH')
                            <button name="status" value="booked" class="bk-btn bk-btn--sm bk-btn--primary">{{ __('Booked') }}</button>
                            <button name="status" value="contacted" class="bk-btn bk-btn--sm">{{ __('Contacted') }}</button>
                            <button name="status" value="cancelled" class="bk-btn bk-btn--sm bk-btn--danger">{{ __('Cancel') }}</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="clock"></i></div><p>{{ __('No waitlist entries.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Blocked times --}}
    <div data-ws-panel="blocked" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($blockedTimes as $b)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                    <th>{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Staff') }}</th><th>{{ __('Reason') }}</th><th class="bk-tbl-actions"></th>
                </tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-num">{{ $b->start_time?->timezone($tz)->format('Y-m-d H:i') }}</td>
                    <td class="bk-tbl-num">{{ $b->end_time?->timezone($tz)->format('Y-m-d H:i') }}</td>
                    <td>{{ $b->branch?->localizedName() ?? '—' }}</td>
                    <td>{{ $b->employee?->localizedName() ?? __('All') }}</td>
                    <td>{{ $b->reason ?: '—' }}</td>
                    <td class="bk-tbl-actions">
                        @can('owner-can', 'appointments.manage')
                        <form action="{{ $ws->url('appointments').'/blocked/'.$b->id }}" method="post" data-ws-action class="d-inline"
                              onsubmit="return confirm('{{ __('Remove this blocked time?') }}')">
                            @csrf @method('DELETE')
                            <button class="bk-btn bk-btn--sm bk-btn--danger bk-btn--icon"><i data-feather="trash-2"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="slash"></i></div><p>{{ __('No blocked times.') }}</p></div>
            @endforelse
        </div></div>
    </div>
</div>
