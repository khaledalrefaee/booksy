{{-- Customer profile drawer --}}
@php $tz = config('app.timezone'); @endphp
<div class="mb-3">
    @if ($customer->is_banned)<span class="bk-pill bk-pill--red">{{ __('Banned') }}</span>@endif
    @if ($customer->tag)<span class="bk-pill bk-pill--purple">{{ __($customer->tag) }}</span>@endif
</div>

<dl class="bk-dl mb-3">
    <dt>{{ __('Phone') }}</dt><dd dir="ltr">{{ $customer->phone ?: '—' }}</dd>
    <dt>{{ __('Visits') }}</dt><dd>{{ $recent->count() }}+</dd>
    <dt>{{ __('Total spent') }}</dt><dd>{{ $ws->money($totalSpent) }}</dd>
    @if ($customer->notes)<dt>{{ __('Notes') }}</dt><dd>{{ $customer->notes }}</dd>@endif
</dl>

@can('owner-can', 'operations.view')
<div class="bk-field">
    <label>{{ $customer->is_banned ? __('Unban customer') : __('Ban customer') }}</label>
    <form action="{{ $ws->url('customers').'/'.$customer->id.'/ban' }}" method="post" data-ws-action>
        @csrf @method('PATCH')
        @unless ($customer->is_banned)
            <input type="text" name="ban_reason" class="bk-input mb-2" placeholder="{{ __('Reason') }}" required>
            <button class="bk-btn bk-btn--danger bk-btn--sm"><i data-feather="slash"></i> {{ __('Ban') }}</button>
        @else
            <button class="bk-btn bk-btn--sm"><i data-feather="check"></i> {{ __('Unban') }}</button>
        @endunless
    </form>
</div>
@endcan

<h4 class="bk-card-title mt-4 mb-2" style="font-size:.85rem;"><i data-feather="calendar"></i> {{ __('Recent visits') }}</h4>
@forelse ($recent as $a)
    @if ($loop->first)<div class="bk-tbl-wrap"><table class="bk-tbl"><tbody>@endif
    <tr>
        <td class="bk-tbl-num">{{ $a->start_time?->timezone($tz)->format('Y-m-d') }}</td>
        <td>{{ $a->service?->localizedName() ?? '—' }}</td>
        <td><x-appointment-status :status="$a->status" /></td>
    </tr>
    @if ($loop->last)</tbody></table></div>@endif
@empty
    <p class="text-muted small">{{ __('No visits yet.') }}</p>
@endforelse
