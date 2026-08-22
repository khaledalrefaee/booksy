{{-- Company Workspace — Payroll tab --}}
@if ($locked)
    <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div>
        <p>{{ __("This company's plan does not include :m.", ['m' => __('Payroll')]) }}</p></div>
@else
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="pay">{{ __('Payroll') }} <span class="bk-pill bk-pill--muted">{{ $periodLabel }}</span></button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="deductions">{{ __('Deductions') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="advances">{{ __('Advances') }}</button>
    </div>

    <div data-ws-panel="pay">
        <div class="bk-card"><div class="bk-card-head">
            <h3 class="bk-card-title"><i data-feather="dollar-sign"></i> {{ __('Payroll') }} · {{ $periodLabel }}</h3>
            <form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
                @csrf<button class="bk-btn bk-btn--gold bk-btn--sm"><i data-feather="edit"></i> {{ __('Run payroll') }}</button>
            </form>
        </div>
        <div class="bk-card-body p0">
            @forelse ($employees as $e)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Branch') }}</th><th class="bk-tbl-num">{{ __('Base') }}</th><th>{{ __('This month') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $e->localizedName() }}</td>
                    <td>{{ $e->branch?->localizedName() ?? '—' }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($e->compensation->base_amount ?? 0) }}</td>
                    <td>@if($paidMap->has($e->id))<span class="bk-pill bk-pill--green">{{ __('Paid') }} · {{ $ws->money($paidMap->get($e->id)) }}</span>@else<span class="bk-pill bk-pill--orange">{{ __('Unpaid') }}</span>@endif</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="users"></i></div><p>{{ __('No active employees.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    <div data-ws-panel="deductions" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($deductions as $d)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Type') }}</th><th>{{ __('Date') }}</th><th class="bk-tbl-num">{{ __('Amount') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $d->employee?->localizedName() ?? '—' }}</td>
                    <td>{{ __($d->type ?? '—') }}</td>
                    <td class="bk-tbl-num">{{ $d->deduction_date?->format('Y-m-d') }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($d->amount) }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="minus-circle"></i></div><p>{{ __('No deductions.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    <div data-ws-panel="advances" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($advances as $a)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Date') }}</th><th class="bk-tbl-num">{{ __('Amount') }}</th><th class="bk-tbl-num">{{ __('Installments') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $a->employee?->localizedName() ?? '—' }}</td>
                    <td class="bk-tbl-num">{{ $a->advance_date?->format('Y-m-d') }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($a->amount) }}</td>
                    <td class="bk-tbl-num">{{ $a->installments_count ?: '—' }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="trending-up"></i></div><p>{{ __('No advances.') }}</p></div>
            @endforelse
        </div></div>
    </div>
</div>
@endif
