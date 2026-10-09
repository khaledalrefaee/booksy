{{-- Quick actions for one lead: details · call · WhatsApp · email · note. Used by the table and the mobile cards. --}}
@php
    $tel = $lead->telUrl();
    $wa  = $lead->whatsappUrl();
    $mail = $lead->email && filter_var($lead->email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$lead->email : null;
@endphp
<div class="bm-actions">
    <a href="{{ route('owner.leads.show', $lead) }}" class="bm-act bm-act-primary" title="{{ __('Open lead') }}" aria-label="{{ __('Open lead') }}"><i data-feather="eye"></i></a>
    @if($tel)<a href="{{ $tel }}" class="bm-act" title="{{ __('Call') }}" aria-label="{{ __('Call') }}"><i data-feather="phone"></i></a>@endif
    @if($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="bm-act ld-wa" title="WhatsApp" aria-label="WhatsApp"><i data-feather="message-circle"></i></a>@endif
    @if($mail)<a href="{{ $mail }}" class="bm-act" title="{{ __('Send email') }}" aria-label="{{ __('Send email') }}"><i data-feather="mail"></i></a>@endif
    @if($canManage)
        <button type="button" class="bm-act" title="{{ __('Add note') }}" aria-label="{{ __('Add note') }}"
                data-note-open data-note-url="{{ route('owner.leads.notes.store', $lead) }}" data-note-name="{{ $lead->business_name }}"><i data-feather="edit-3"></i></button>
    @endif
</div>
