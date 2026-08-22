{{-- Company Workspace — Services & Pricing tab --}}
<div class="bk-card">
    <div class="bk-card-head">
        <h3 class="bk-card-title"><i data-feather="scissors"></i> {{ __('Services') }}
            <span class="bk-pill bk-pill--muted">{{ $services->count() }}</span></h3>
        <div class="d-flex gap-2 align-items-center">
            <div class="bk-search"><i data-feather="search"></i>
                <input type="text" placeholder="{{ __('Search services…') }}"
                       oninput="var t=this.value.toLowerCase();document.querySelectorAll('#bk-svc-rows tr').forEach(function(r){r.style.display=r.dataset.s.includes(t)?'':'none';});">
            </div>
            <form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
                @csrf<button class="bk-btn bk-btn--gold bk-btn--sm"><i data-feather="edit"></i> {{ __('Workbench') }}</button>
            </form>
        </div>
    </div>
    <div class="bk-card-body p0">
        @forelse ($services as $s)
            @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><thead><tr>
                <th>{{ __('Service') }}</th><th>{{ __('Category') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Type') }}</th>
                <th class="bk-tbl-num">{{ __('Price') }}</th><th class="bk-tbl-num">{{ __('Min') }}</th><th>{{ __('Active') }}</th><th class="bk-tbl-actions"></th>
            </tr></thead><tbody id="bk-svc-rows">@endif
            <tr data-s="{{ strtolower($s->localizedName()) }}">
                <td class="bk-tbl-strong">{{ $s->localizedName() }}</td>
                <td>{{ $s->serviceCategory?->localizedName() ?? '—' }}</td>
                <td>{{ $s->branch?->localizedName() ?? '—' }}</td>
                <td><span class="bk-pill bk-pill--muted">{{ __($s->service_type ?? 'standard') }}</span></td>
                <td class="bk-tbl-num">{{ $ws->money($s->price) }}</td>
                <td class="bk-tbl-num">{{ $s->duration_minutes ?? '—' }}</td>
                <td>
                    @can('owner-can', 'company-workspace.view')
                    <form action="{{ $ws->url('services').'/services/'.$s->id.'/toggle-active' }}" method="post" data-ws-action class="d-inline">
                        @csrf @method('PATCH')
                        <button class="bk-btn bk-btn--sm {{ $s->is_active ? '' : 'bk-btn--ghost' }}" style="padding:3px 9px;">
                            <span class="bk-pill bk-pill--{{ $s->is_active ? 'green' : 'muted' }}">{{ $s->is_active ? __('On') : __('Off') }}</span>
                        </button>
                    </form>
                    @endcan
                </td>
                <td class="bk-tbl-actions">
                    <button type="button" class="bk-btn bk-btn--sm bk-btn--ghost"
                            data-ws-drawer="#" onclick="event.preventDefault();this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none';">
                        <i data-feather="edit-2"></i>
                    </button>
                    <form action="{{ $ws->url('services').'/services/'.$s->id.'/price' }}" method="post" data-ws-action style="display:none;margin-top:6px;">
                        @csrf @method('PATCH')
                        <div class="d-flex gap-1">
                            <input type="number" step="0.01" min="0" name="price" value="{{ (float) $s->price }}" class="bk-input" style="width:100px;padding:5px 8px;">
                            <button class="bk-btn bk-btn--sm bk-btn--primary"><i data-feather="check"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
            @if ($loop->last)</tbody></table></div>@endif
        @empty
            <div class="bk-empty"><div class="bk-empty-ic"><i data-feather="scissors"></i></div><p>{{ __('No services yet.') }}</p></div>
        @endforelse
    </div>
</div>
