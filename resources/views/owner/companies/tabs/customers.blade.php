{{-- Company Workspace — Customers tab --}}
<div data-ws-subnav-group>
    <div class="bk-subnav mb-3">
        <button type="button" class="bk-subnav-item active" data-ws-subnav="list">{{ __('Customers') }} <span class="bk-pill bk-pill--muted">{{ $customers->count() }}</span></button>
        <button type="button" class="bk-subnav-item" data-ws-subnav="debts">{{ __('Debts') }} <span class="bk-pill bk-pill--muted">{{ $debts->count() }}</span></button>
    </div>

    {{-- Customers --}}
    <div data-ws-panel="list">
        <div class="bk-card">
            <div class="bk-card-head">
                <h3 class="bk-card-title"><i data-feather="users"></i> {{ __('Customers') }}</h3>
                <div class="bk-search"><i data-feather="search"></i>
                    <input type="text" placeholder="{{ __('Search name or phone…') }}"
                           oninput="var t=this.value.toLowerCase();document.querySelectorAll('#bk-cust-rows tr').forEach(function(r){r.style.display=r.dataset.s.includes(t)?'':'none';});">
                </div>
            </div>
            <div class="bk-card-body p0">
                @forelse ($customers as $c)
                    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                        <th>{{ __('Name') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Tag') }}</th><th class="bk-tbl-num">{{ __('Visits') }}</th><th class="bk-tbl-num">{{ __('Spent') }}</th><th></th>
                    </tr></thead><tbody id="bk-cust-rows">@endif
                    <tr class="is-clickable" data-s="{{ strtolower($c->name.' '.$c->phone) }}"
                        data-ws-drawer="{{ $ws->url('customers').'/'.$c->id.'/profile' }}" data-ws-drawer-title="{{ $c->name }}">
                        <td class="bk-tbl-strong">{{ $c->name }} @if($c->is_banned)<span class="bk-pill bk-pill--red ms-1">{{ __('Banned') }}</span>@endif</td>
                        <td class="bk-tbl-num" dir="ltr">{{ $c->phone ?: '—' }}</td>
                        <td>@if($c->tag)<span class="bk-pill bk-pill--purple">{{ __($c->tag) }}</span>@else — @endif</td>
                        <td class="bk-tbl-num">{{ (int) $c->total_visits }}</td>
                        <td class="bk-tbl-num">{{ $ws->money($c->total_spent ?? 0) }}</td>
                        <td class="bk-tbl-actions"><i data-feather="chevron-right"></i></td>
                    </tr>
                    @if ($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="users"></i></div><p>{{ __('No customers yet.') }}</p></div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Debts --}}
    <div data-ws-panel="debts" style="display:none;">
        @unless ($ws->feature('finance'))
            <div class="bk-locked"><div class="bk-locked-ic"><i data-feather="lock"></i></div>
                <p>{{ __("This company's plan does not include :m.", ['m' => __('Finance')]) }}</p></div>
        @else
            <div class="bk-card"><div class="bk-card-body p0">
                @forelse ($debts as $d)
                    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                        <th>{{ __('Customer') }}</th><th>{{ __('Branch') }}</th><th class="bk-tbl-num">{{ __('Original') }}</th><th class="bk-tbl-num">{{ __('Remaining') }}</th><th>{{ __('Status') }}</th><th class="bk-tbl-actions"></th>
                    </tr></thead><tbody>@endif
                    <tr>
                        <td class="bk-tbl-strong">{{ $d->customer?->name ?? '—' }}</td>
                        <td>{{ $d->branch?->localizedName() ?? '—' }}</td>
                        <td class="bk-tbl-num">{{ $ws->money($d->original_amount) }}</td>
                        <td class="bk-tbl-num bk-tbl-strong">{{ $ws->money($d->remaining) }}</td>
                        <td><span class="bk-pill bk-pill--{{ $d->status === 'partial' ? 'orange' : 'red' }}">{{ __($d->status) }}</span></td>
                        <td class="bk-tbl-actions">
                            @can('owner-can', 'finance.manage')
                            <form action="{{ $ws->url('customers').'/debts/'.$d->id.'/waive' }}" method="post" data-ws-action class="d-inline"
                                  onsubmit="return confirm('{{ __('Waive this debt?') }}')">
                                @csrf @method('PATCH')
                                <button class="bk-btn bk-btn--sm bk-btn--ghost">{{ __('Waive') }}</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @if ($loop->last)</tbody></table></div>@endif
                @empty
                    <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="check-circle"></i></div><p>{{ __('No outstanding debts.') }}</p></div>
                @endforelse
            </div></div>
            <p class="text-muted small mt-2">{{ __('Recording debt payments runs in the full editor to keep the cash register in sync.') }}</p>
        @endunless
    </div>
</div>
