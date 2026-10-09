{{-- Status: an inline select (managers) or a read-only badge (viewers). --}}
@if($canManage)
    <select class="ld-status ld-tone-{{ $lead->statusTone() }}" data-status-url="{{ route('owner.leads.status', $lead) }}" data-current="{{ $lead->status }}"
            aria-label="{{ __('Status') }}: {{ $lead->business_name }}">
        @foreach(\App\Support\LeadCatalog::options('statuses') as $key => $label)
            <option value="{{ $key }}" @selected($lead->status === $key)>{{ $label }}</option>
        @endforeach
    </select>
@else
    <span class="ld-status ld-tone-{{ $lead->statusTone() }}" style="display:inline-flex;align-items:center;padding-inline:12px;cursor:default;">{{ $lead->statusLabel() }}</span>
@endif
