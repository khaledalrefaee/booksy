{{-- Company Workspace — Team tab --}}
@php
    $roleName = fn ($e) => $e->role ? (app()->getLocale() === 'ar' ? ($e->role->label_ar ?: $e->role->label_en) : ($e->role->label_en ?: $e->role->label_ar)) : '—';
    $leaveTone = fn ($s) => match ($s) { 'approved' => 'green', 'rejected' => 'red', default => 'orange' };
    $attTone = fn ($s) => match ($s) { 'present' => 'green', 'late' => 'orange', 'absent' => 'red', default => 'muted' };
@endphp
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="staff">{{ __('Employees') }} <span class="bk-pill bk-pill--muted">{{ $employees->count() }}</span></button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="attendance">{{ __('Attendance') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="leaves">{{ __('Leaves') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="holidays">{{ __('Holidays') }}</button>
    </div>

    {{-- Employees --}}
    <div data-ws-panel="staff">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($employees as $e)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                    <th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Role') }}</th><th>{{ __('Active') }}</th><th class="bk-tbl-actions"></th>
                </tr></thead><tbody>@endif
                <tr class="is-clickable" data-ws-drawer="{{ $ws->url('team').'/employees/'.$e->id }}" data-ws-drawer-title="{{ $e->localizedName() }}">
                    <td class="bk-tbl-strong">{{ $e->localizedName() }}</td>
                    <td class="text-muted">{{ $e->email ?: '—' }}</td>
                    <td>{{ $e->branch?->localizedName() ?? __('Company-wide') }}</td>
                    <td>{{ $roleName($e) }}</td>
                    <td>@if($e->is_active)<span class="bk-pill bk-pill--green">{{ __('Yes') }}</span>@else<span class="bk-pill bk-pill--muted">{{ __('No') }}</span>@endif</td>
                    <td class="bk-tbl-actions"><i data-feather="chevron-right"></i></td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="users"></i></div><p>{{ __('No employees yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Attendance --}}
    <div data-ws-panel="attendance" style="display:none;">
        @unless ($ws->feature('attendance'))
            <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div><p>{{ __("This company's plan does not include :m.", ['m' => __('Attendance')]) }}</p></div>
        @else
            <div class="bk-card"><div class="bk-card-head"><h3 class="bk-card-title"><i data-feather="check-square"></i> {{ __("Today's attendance") }}</h3></div>
            <div class="bk-card-body p0">
                @forelse ($attendanceToday as $a)
                    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Check in') }}</th><th>{{ __('Check out') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>@endif
                    <tr>
                        <td class="bk-tbl-strong">{{ $a->employee?->localizedName() ?? '—' }}</td>
                        <td>{{ $a->branch?->localizedName() ?? '—' }}</td>
                        <td class="bk-tbl-num">{{ $a->check_in?->format('H:i') ?? '—' }}</td>
                        <td class="bk-tbl-num">{{ $a->check_out?->format('H:i') ?? '—' }}</td>
                        <td><span class="bk-pill bk-pill--{{ $attTone($a->status) }}">{{ __($a->status) }}</span></td>
                    </tr>
                    @if ($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="check-square"></i></div><p>{{ __('No attendance records today.') }}</p></div>
                @endforelse
            </div></div>
        @endunless
    </div>

    {{-- Leaves --}}
    <div data-ws-panel="leaves" style="display:none;">
        @unless ($ws->feature('leaves'))
            <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div><p>{{ __("This company's plan does not include :m.", ['m' => __('Leaves')]) }}</p></div>
        @else
            <div class="bk-card"><div class="bk-card-body p0">
                @forelse ($leaves as $l)
                    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Type') }}</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>@endif
                    <tr>
                        <td class="bk-tbl-strong">{{ $l->employee?->localizedName() ?? '—' }}</td>
                        <td>{{ __($l->type) }}</td>
                        <td class="bk-tbl-num">{{ $l->start_date?->format('Y-m-d') }}</td>
                        <td class="bk-tbl-num">{{ $l->end_date?->format('Y-m-d') }}</td>
                        <td><span class="bk-pill bk-pill--{{ $leaveTone($l->status) }}">{{ __($l->status) }}</span></td>
                    </tr>
                    @if ($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="sun"></i></div><p>{{ __('No leave requests.') }}</p></div>
                @endforelse
            </div></div>
            <p class="text-muted small mt-2">{{ __('Approving leaves runs in the full editor (payroll deductions sync).') }}</p>
        @endunless
    </div>

    {{-- Holidays --}}
    <div data-ws-panel="holidays" style="display:none;">
        @unless ($ws->feature('leaves'))
            <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div><p>{{ __("This company's plan does not include :m.", ['m' => __('Leaves')]) }}</p></div>
        @else
            <div class="bk-card"><div class="bk-card-body p0">
                @forelse ($holidays as $h)
                    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('Holiday') }}</th></tr></thead><tbody>@endif
                    <tr><td class="bk-tbl-num">{{ $h->start_date?->format('Y-m-d') }}</td><td class="bk-tbl-num">{{ $h->end_date?->format('Y-m-d') }}</td><td class="bk-tbl-strong">{{ $h->name ?? '—' }}</td></tr>
                    @if ($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="calendar"></i></div><p>{{ __('No holidays set.') }}</p></div>
                @endforelse
            </div></div>
        @endunless
    </div>
</div>
