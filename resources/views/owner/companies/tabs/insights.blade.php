{{-- Company Workspace — Insights tab --}}
@if ($locked)
    <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div>
        <p>{{ __("This company's plan does not include :m.", ['m' => __('Reports')]) }}</p></div>
@else
<div id="bk-ins" data-ws-subnav-group>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="bk-subnav">
            <button type="button" class="bk-subnav-item active" data-ws-subnav="pl">{{ __('Profit & loss') }}</button>
            <button type="button" class="bk-subnav-item" data-ws-subnav="perf">{{ __('Performance') }}</button>
            <button type="button" class="bk-subnav-item" data-ws-subnav="activity">{{ __('Activity') }}</button>
        </div>
        <input type="month" value="{{ $monthValue }}" class="bk-input" style="width:auto;"
               onchange="fetch('{{ $ws->url('insights') }}?month='+this.value,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.text()).then(function(h){document.getElementById('bk-ins').outerHTML=h;if(window.feather)window.feather.replace();});">
    </div>

    {{-- P&L --}}
    <div data-ws-panel="pl">
        <div class="bk-ws-kpis mb-3">
            <div class="bk-kpi" data-accent="green"><div class="bk-kpi-label"><i data-feather="arrow-down-circle"></i> {{ __('Income') }}</div><div class="bk-kpi-num">{{ $ws->money($income) }}</div><div class="bk-kpi-sub">{{ $monthLabel }}</div></div>
            <div class="bk-kpi" data-accent="red"><div class="bk-kpi-label"><i data-feather="arrow-up-circle"></i> {{ __('Expenses') }}</div><div class="bk-kpi-num">{{ $ws->money($expense) }}</div></div>
            <div class="bk-kpi" data-accent="{{ $net >= 0 ? 'gold' : 'red' }}"><div class="bk-kpi-label"><i data-feather="trending-up"></i> {{ __('Net') }}</div><div class="bk-kpi-num">{{ $ws->money($net) }}</div></div>
        </div>
        <div class="bk-card"><div class="bk-card-body">
            @php $tot = max($income + $expense, 1); @endphp
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted small">{{ __('Income') }}</span><span class="bk-tbl-strong">{{ $ws->money($income) }}</span></div>
            <div class="bk-stat-bar" style="position:relative;height:8px;border-radius:4px;margin-bottom:14px;"><div class="bk-stat-bar-fill" style="width:{{ round($income / $tot * 100) }}%;background:var(--bk-success);"></div></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted small">{{ __('Expenses') }}</span><span class="bk-tbl-strong">{{ $ws->money($expense) }}</span></div>
            <div class="bk-stat-bar" style="position:relative;height:8px;border-radius:4px;"><div class="bk-stat-bar-fill" style="width:{{ round($expense / $tot * 100) }}%;background:var(--bk-danger);"></div></div>
        </div></div>
    </div>

    {{-- Performance --}}
    <div data-ws-panel="perf" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($performance as $p)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('Employee') }}</th><th class="bk-tbl-num">{{ __('Jobs') }}</th><th class="bk-tbl-num">{{ __('Revenue') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-strong">{{ $p->employee?->localizedName() ?? '—' }}</td>
                    <td class="bk-tbl-num">{{ $p->jobs }}</td>
                    <td class="bk-tbl-num">{{ $ws->money($p->revenue) }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="bar-chart-2"></i></div><p>{{ __('No completed jobs this month.') }}</p></div>
            @endforelse
        </div></div>
    </div>

    {{-- Activity --}}
    <div data-ws-panel="activity" style="display:none;">
        <div class="bk-card"><div class="bk-card-body p0">
            @forelse ($activity as $log)
                @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr><th>{{ __('When') }}</th><th>{{ __('By') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>@endif
                <tr>
                    <td class="bk-tbl-num">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->causer_name ?: '—' }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="activity"></i></div><p>{{ __('No activity recorded.') }}</p></div>
            @endforelse
        </div></div>
    </div>
</div>
@endif
