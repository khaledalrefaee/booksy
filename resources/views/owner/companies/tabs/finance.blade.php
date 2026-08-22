{{-- Company Workspace — Finance tab --}}
@if ($locked)
    <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div>
        <p>{{ __("This company's plan does not include :m.", ['m' => __('Finance')]) }}</p></div>
@else
@php $tz = config('app.timezone');
    $invTone = fn ($s) => match ($s) { 'paid' => 'green', 'partial' => 'orange', 'void','refunded' => 'red', 'issued' => 'blue', default => 'muted' };
@endphp
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="cash">{{ __('Cash box') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="invoices">{{ __('Invoices') }}</button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="expenses">{{ __('Expenses') }}</button>
    </div>

    {{-- Cash --}}
    <div data-ws-panel="cash">
        <div class="bk-ws-kpis mb-3">
            <div class="bk-kpi" data-accent="green"><div class="bk-kpi-label"><i data-feather="arrow-down-circle"></i> {{ __('Income (month)') }}</div><div class="bk-kpi-num">{{ $ws->money($income) }}</div></div>
            <div class="bk-kpi" data-accent="red"><div class="bk-kpi-label"><i data-feather="arrow-up-circle"></i> {{ __('Expenses (month)') }}</div><div class="bk-kpi-num">{{ $ws->money($expense) }}</div></div>
            <div class="bk-kpi" data-accent="gold"><div class="bk-kpi-label"><i data-feather="trending-up"></i> {{ __('Net') }}</div><div class="bk-kpi-num">{{ $ws->money($income - $expense) }}</div></div>
        </div>
        <div class="bk-card"><div class="bk-card-head"><h3 class="bk-card-title"><i data-feather="credit-card"></i> {{ __('Recent cash entries') }}</h3>
            <form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
                @csrf<button class="bk-btn bk-btn--gold bk-btn--sm"><i data-feather="edit"></i> {{ __('Manage cash') }}</button></form>
        </div>
        <div class="bk-card-body p0">
            @forelse ($cashEntries as $c)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Type') }}</th><th class="bk-tbl-num">{{ __('Amount') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-num">{{ $c->created_at?->timezone($tz)->format('Y-m-d H:i') }}</td>
                    <td>{{ $c->branch?->localizedName() ?? '—' }}</td>
                    <td><span class="bk-pill bk-pill--{{ in_array($c->type, ['expense','refund']) ? 'red' : 'green' }}">{{ __($c->type) }}</span></td>
                    <td class="bk-tbl-num bk-tbl-strong">{{ $ws->money($c->amount) }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="credit-card"></i></div><p>{{ __('No cash entries yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Invoices --}}
    <div data-ws-panel="invoices" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($invoices as $inv)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Branch') }}</th><th class="bk-tbl-num">{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $inv->invoice_number }}</td>
                    <td>{{ $inv->customer_name ?: '—' }}</td>
                    <td>{{ $inv->branch?->localizedName() ?? '—' }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($inv->total) }}</td>
                    <td><span class="bk-pill bk-pill--{{ $invTone($inv->status) }}">{{ __($inv->status) }}</span></td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="file-text"></i></div><p>{{ __('No invoices yet.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Expenses --}}
    <div data-ws-panel="expenses" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($expenses as $ex)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Title') }}</th><th>{{ __('Category') }}</th><th>{{ __('Branch') }}</th><th class="bk-tbl-num">{{ __('Amount') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Next due') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $ex->title }}</td>
                    <td>{{ __($ex->category ?? '—') }}</td>
                    <td>{{ $ex->branch?->localizedName() ?? '—' }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($ex->amount) }}</td>
                    <td>{{ __($ex->frequency) }}</td>
                    <td class="bk-tbl-num">{{ $ex->next_due_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="repeat"></i></div><p>{{ __('No recurring expenses.') }}</p></div>
            @endforelse
        </div></div>
    </div>
</div>
@endif
