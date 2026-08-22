{{-- Employee detail drawer --}}
@php
    $roleName = $employee->role ? (app()->getLocale() === 'ar' ? ($employee->role->label_ar ?: $employee->role->label_en) : ($employee->role->label_en ?: $employee->role->label_ar)) : '—';
@endphp
<div class="mb-3">
    @if($employee->is_active)<span class="bk-pill bk-pill--green">{{ __('Active') }}</span>@else<span class="bk-pill bk-pill--muted">{{ __('Inactive') }}</span>@endif
</div>
<dl class="bk-dl mb-3">
    <dt>{{ __('Email') }}</dt><dd>{{ $employee->email ?: '—' }}</dd>
    <dt>{{ __('Phone') }}</dt><dd dir="ltr">{{ $employee->phone ?: '—' }}</dd>
    <dt>{{ __('Branch') }}</dt><dd>{{ $employee->branch?->localizedName() ?? __('Company-wide') }}</dd>
    <dt>{{ __('Role') }}</dt><dd>{{ $roleName }}</dd>
    @if ($employee->compensation)
        <dt>{{ __('Base salary') }}</dt><dd>{{ $ws->money($employee->compensation->base_amount ?? 0) }}</dd>
    @endif
</dl>
<form method="post" action="{{ $ws->fullEditorAction() }}" onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
    @csrf<button class="bk-btn bk-btn--gold"><i data-feather="external-link"></i> {{ __('Manage in full editor') }}</button>
</form>
