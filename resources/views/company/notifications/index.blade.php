@extends('company.dashboard')

@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Notifications') }}</h4>
            <p class="text-muted mb-0 tx-13">
                {{ __('Everything that happened across your business') }}
                @if($unreadCount > 0)
                    · <span style="color:var(--bk-accent);font-weight:700;">{{ $unreadCount }} {{ __('unread') }}</span>
                @endif
            </p>
        </div>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('company.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary rounded-pill px-3">
                <i data-feather="check-circle" style="width:14px;height:14px;margin-inline-end:5px;"></i>
                {{ __('Mark all read') }}
            </button>
        </form>
        @endif
    </div>

    <div class="card shadow-sm" style="border-radius:16px;overflow:hidden;">
        <div class="bk-nlist">
            @forelse($notifications as $notif)
            <a href="{{ $notif->link ?? '#' }}"
               class="bk-nrow {{ $notif->isRead() ? '' : 'is-unread' }}"
               @unless($notif->isRead())
               onclick="fetch('{{ route('company.notifications.read', $notif) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})"
               @endunless>
                <span class="bk-nrow-ic" style="--acc:var({{ $notif->accentToken() }});">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $notif->iconSvg() !!}</svg>
                </span>
                <span class="bk-nrow-txt">
                    <span class="bk-nrow-t">{{ $notif->title }}</span>
                    @if($notif->body)<span class="bk-nrow-b">{{ $notif->body }}</span>@endif
                </span>
                <span class="bk-nrow-meta">
                    <span class="bk-nrow-time">{{ $notif->created_at->diffForHumans() }}</span>
                    @unless($notif->isRead())<span class="bk-nrow-dot"></span>@endunless
                </span>
            </a>
            @empty
            <div class="bk-nempty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <div class="bk-nempty-t">{{ __('No notifications yet') }}</div>
                <div class="bk-nempty-b">{{ __('New bookings and updates will show up here') }}</div>
            </div>
            @endforelse
        </div>
    </div>

    @if($notifications->hasPages())
    <div class="mt-3">{{ $notifications->links() }}</div>
    @endif

</div>

@push('company-styles')
<style>
.bk-nlist{ display:flex; flex-direction:column; }
.bk-nrow{
    display:flex; gap:14px; align-items:center;
    padding:16px 20px; text-decoration:none;
    border-bottom:1px solid var(--bk-border);
    transition:background .12s;
}
.bk-nrow:last-child{ border-bottom:none; }
.bk-nrow:hover{ background:var(--bk-surface-2); }
.bk-nrow.is-unread{ background:color-mix(in srgb, var(--bk-accent) 6%, transparent); }
.bk-nrow-ic{
    flex-shrink:0; width:44px; height:44px; border-radius:13px;
    display:flex; align-items:center; justify-content:center;
    color:var(--acc); background:color-mix(in srgb, var(--acc) 14%, transparent);
}
.bk-nrow-ic svg{ width:22px; height:22px; }
.bk-nrow-txt{ min-width:0; flex:1; display:flex; flex-direction:column; gap:3px; }
.bk-nrow-t{ font-size:13.5px; font-weight:700; color:var(--bk-text); line-height:1.3; }
.bk-nrow-b{ font-size:12.5px; color:var(--bk-text-soft); line-height:1.45; }
.bk-nrow-meta{ flex-shrink:0; display:flex; align-items:center; gap:10px; }
.bk-nrow-time{ font-size:11px; color:var(--bk-text-muted); white-space:nowrap; }
.bk-nrow-dot{ width:9px; height:9px; border-radius:50%; background:var(--bk-accent); }
.bk-nempty{
    display:flex; flex-direction:column; align-items:center; gap:8px;
    padding:64px 20px; color:var(--bk-text-muted); text-align:center;
}
.bk-nempty svg{ width:44px; height:44px; opacity:.45; margin-bottom:4px; }
.bk-nempty-t{ font-size:15px; font-weight:700; color:var(--bk-text-soft); }
.bk-nempty-b{ font-size:12.5px; }
</style>
@endpush
@endsection
