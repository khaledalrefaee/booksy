@extends('owner.dashboard')
@section('content')

@php
    // Privacy: never show the full number to the owner. Keep the dial prefix and
    // last 3 digits, mask the middle (e.g. 963•••••373).
    $mask = function (?string $phone) {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if ($d === '') return '—';
        if (strlen($d) <= 6) return substr($d, 0, 2) . str_repeat('•', max(1, strlen($d) - 2));
        return substr($d, 0, 3) . str_repeat('•', 4) . substr($d, -3);
    };
    $typeMeta = [
        'confirmation' => ['icon' => 'check-circle', 'cls' => 'sx-type-confirmation', 'label' => __('Confirmation')],
        'reminder'     => ['icon' => 'clock',        'cls' => 'sx-type-reminder',     'label' => __('Reminder')],
        'followup'     => ['icon' => 'refresh-cw',   'cls' => 'sx-type-followup',     'label' => __('Follow-up')],
        'manual'       => ['icon' => 'edit-3',       'cls' => '',                     'label' => __('Manual')],
    ];
    $statusLabels = ['sent' => __('Sent'), 'failed' => __('Failed'), 'skipped' => __('Skipped'), 'queued' => __('Queued')];
    $reasonLabels = ['insufficient_credits' => __('No credits'), 'provider timeout' => __('Provider timeout')];
@endphp

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Tracking') }}
            </div>
            <h1 class="sx-title">{{ __('Message logs') }}</h1>
            <p class="sx-subtitle">{{ __('Every SMS recorded in GlowRez — company, branch, type, status, segments. Numbers are masked for privacy.') }}</p>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="GET" class="sx-toolbar sx-reveal">
        <select name="status" class="sx-select" onchange="this.form.submit()">
            <option value="">{{ __('All statuses') }}</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $statusLabels[$s] ?? ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="type" class="sx-select" onchange="this.form.submit()">
            <option value="">{{ __('All types') }}</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $typeMeta[$t]['label'] ?? ucfirst($t) }}</option>
            @endforeach
        </select>
        @if($status !== '' || $type !== '')
            <a href="{{ route('owner.sms.logs') }}" class="sx-btn sx-btn-ghost sx-btn-sm"><i data-feather="x"></i>{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="sx-card sx-reveal">
        @if($messages->isEmpty())
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="inbox"></i></span>
                <h3 class="sx-empty-title">{{ __('No messages') }}</h3>
                <p class="sx-empty-text">{{ __('No SMS have been recorded for this filter yet.') }}</p>
            </div>
        @else
            <div class="sx-table-scroll">
                <table class="sx-table">
                    <thead><tr>
                        <th>{{ __('Recipient') }}</th>
                        <th>{{ __('Company / Branch') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="num">{{ __('Segments') }}</th>
                        <th>{{ __('When') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($messages as $m)
                        @php $tm = $typeMeta[$m->message_type] ?? ['icon' => 'message-square', 'cls' => '']; @endphp
                        <tr>
                            <td>
                                <div class="sx-name sx-mono">{{ $mask($m->phone) }}</div>
                                <div class="sx-sub">{{ $m->customer?->name ?? __('Guest') }}</div>
                            </td>
                            <td>
                                <div class="sx-name" style="font-size:.86rem;">{{ $m->company?->localizedName() ?? '—' }}</div>
                                <div class="sx-sub">{{ $m->branch?->localizedName() ?? __('Company pool') }}</div>
                            </td>
                            <td><span class="sx-type {{ $tm['cls'] }}"><i data-feather="{{ $tm['icon'] }}"></i>{{ $tm['label'] ?? ucfirst($m->message_type) }}</span></td>
                            <td>
                                <span class="sx-pill sx-pill-{{ $m->status }}">{{ $statusLabels[$m->status] ?? ucfirst($m->status) }}</span>
                                @if($m->failure_reason)
                                    <div class="sx-sub" style="max-width:22ch;" title="{{ $m->failure_reason }}">{{ $reasonLabels[$m->failure_reason] ?? \Illuminate\Support\Str::limit($m->failure_reason, 32) }}</div>
                                @endif
                            </td>
                            <td class="num sx-mono">{{ $m->segments }}@if($m->credits_used) · {{ $m->credits_used }} {{ __('cr') }}@endif</td>
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

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@endsection
