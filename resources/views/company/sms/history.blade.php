@extends('company.dashboard')
@section('content')

@php
    $typeMeta = [
        'confirmation' => ['icon' => 'check-circle', 'cls' => 'sx-type-confirmation', 'label' => __('Confirmation')],
        'reminder'     => ['icon' => 'clock',        'cls' => 'sx-type-reminder',     'label' => __('Reminder')],
        'followup'     => ['icon' => 'refresh-cw',   'cls' => 'sx-type-followup',     'label' => __('Follow-up')],
        'manual'       => ['icon' => 'edit-3',       'cls' => '',                     'label' => __('Manual')],
    ];
    $statusLabels = ['sent' => __('Sent'), 'failed' => __('Failed'), 'skipped' => __('Skipped'), 'queued' => __('Queued')];
@endphp

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('company.sms.overview') }}">{{ __('SMS') }}</a>
                <span aria-hidden="true">·</span> {{ __('Tracking') }}
            </div>
            <h1 class="sx-title">{{ __('SMS History') }}</h1>
            <p class="sx-subtitle">{{ __('Every SMS your branches have sent, with type, status and credits used.') }}</p>
        </div>
    </header>

    @include('company.partials.flash')

    <form method="GET" class="sx-toolbar sx-reveal">
        <select name="type" class="sx-select" onchange="this.form.submit()">
            <option value="">{{ __('All types') }}</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $typeMeta[$t]['label'] ?? ucfirst($t) }}</option>
            @endforeach
        </select>
        <select name="status" class="sx-select" onchange="this.form.submit()">
            <option value="">{{ __('All statuses') }}</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $statusLabels[$s] ?? ucfirst($s) }}</option>
            @endforeach
        </select>
        @if($type !== '' || $status !== '')
            <a href="{{ route('company.sms.history') }}" class="sx-btn sx-btn-ghost sx-btn-sm"><i data-feather="x"></i>{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="sx-card sx-reveal">
        @if($messages->isEmpty())
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="inbox"></i></span>
                <h3 class="sx-empty-title">{{ __('No messages yet') }}</h3>
                <p class="sx-empty-text">{{ __('When your automations start sending, your SMS history will appear here.') }}</p>
                <a href="{{ route('company.sms.automations') }}" class="sx-btn sx-btn-primary"><i data-feather="zap"></i>{{ __('Set up automations') }}</a>
            </div>
        @else
            <div class="sx-table-scroll">
                <table class="sx-table">
                    <thead><tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="num">{{ __('SMS') }}</th>
                        <th>{{ __('When') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($messages as $m)
                        @php $tm = $typeMeta[$m->message_type] ?? ['icon' => 'message-square', 'cls' => '', 'label' => ucfirst($m->message_type)]; @endphp
                        <tr>
                            <td>
                                <div class="sx-name">{{ $m->customer?->name ?? __('Guest') }}</div>
                                <div class="sx-sub sx-mono">{{ $m->phone }}</div>
                            </td>
                            <td class="sx-sub">{{ $m->branch?->localizedName() ?? __('Company pool') }}</td>
                            <td><span class="sx-type {{ $tm['cls'] }}"><i data-feather="{{ $tm['icon'] }}"></i>{{ $tm['label'] }}</span></td>
                            <td>
                                <span class="sx-pill sx-pill-{{ $m->status }}">{{ $statusLabels[$m->status] ?? ucfirst($m->status) }}</span>
                            </td>
                            <td class="num sx-mono">{{ $m->credits_used ?: $m->segments }}</td>
                            <td class="sx-sub">{{ ($m->sent_at ?? $m->created_at)?->translatedFormat('d M · g:i A') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sx-pagination">
                <span class="sx-pagination-info">{{ __('Showing :from–:to of :total', ['from' => $messages->firstItem(), 'to' => $messages->lastItem(), 'total' => $messages->total()]) }}</span>
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>

@push('company-styles')
    @include('company.sms.partials.styles')
@endpush

@endsection
