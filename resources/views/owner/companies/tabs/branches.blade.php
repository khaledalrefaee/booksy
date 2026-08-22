{{-- Company Workspace — Branches tab --}}
@php
    $statusTone = fn ($s) => match ($s) {
        'active' => 'green', 'maintenance' => 'orange', default => 'muted',
    };
@endphp

<div class="bk-card">
    <div class="bk-card-head">
        <h3 class="bk-card-title"><i data-feather="map-pin"></i> {{ __('Branches') }}
            <span class="bk-pill bk-pill--muted">{{ $branches->count() }}</span>
        </h3>
        <form method="get" class="bk-search" onsubmit="return false;">
            <i data-feather="search"></i>
            <input type="text" value="{{ $q }}" placeholder="{{ __('Search branches…') }}"
                   oninput="const r=this.closest('tr'); const t=this.value.toLowerCase();
                            document.querySelectorAll('#bk-branch-rows tr').forEach(function(row){
                              row.style.display = row.dataset.search.includes(t) ? '' : 'none';
                            });">
        </form>
    </div>
    <div class="bk-card-body p0">
        @forelse ($branches as $branch)
            @if ($loop->first)
                <div class="bk-tbl-wrap"><table class="bk-tbl">
                    <thead><tr>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="bk-tbl-num">{{ __('Staff') }}</th>
                        <th class="bk-tbl-num">{{ __('Services') }}</th>
                        <th class="bk-tbl-actions">{{ __('Details') }}</th>
                    </tr></thead>
                    <tbody id="bk-branch-rows">
            @endif
            <tr class="is-clickable" data-search="{{ strtolower($branch->localizedName().' '.$branch->address) }}"
                data-ws-drawer="{{ $ws->url('branches').'/'.$branch->id.'/detail' }}"
                data-ws-drawer-title="{{ $branch->localizedName() }}">
                <td class="bk-tbl-strong">
                    {{ $branch->localizedName() }}
                    @if ($branch->is_head_office)
                        <span class="bk-pill bk-pill--gold ms-1">{{ __('HQ') }}</span>
                    @endif
                </td>
                <td class="text-muted">{{ $branch->address ?: '—' }}</td>
                <td><span class="bk-pill bk-pill--{{ $statusTone($branch->status) }}">{{ __($branch->status) }}</span></td>
                <td class="bk-tbl-num">{{ $branch->employees_count }}</td>
                <td class="bk-tbl-num">{{ $branch->services_count }}</td>
                <td class="bk-tbl-actions">
                    <button type="button" class="bk-btn bk-btn--sm bk-btn--ghost"
                            data-ws-drawer="{{ $ws->url('branches').'/'.$branch->id.'/detail' }}"
                            data-ws-drawer-title="{{ $branch->localizedName() }}">
                        <i data-feather="chevron-right"></i>
                    </button>
                </td>
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
