@extends('owner.dashboard')

@section('content')
<div class="page-content">

    <div class="bk-hero bk-a1">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 class="bk-hero-title">{{ __('Notifications') }}</h2>
                <p class="bk-hero-sub">
                    <i data-feather="bell" style="width:13px;height:13px;display:inline;margin-right:5px;"></i>
                    {{ __('New business accounts and platform events.') }}
                </p>
            </div>
            @if($notifications->where('read_at', null)->count() > 0)
            <form method="POST" action="{{ route('owner.notifications.read-all') }}" class="bk-hero-actions">
                @csrf
                <button type="submit" class="bk-navbar-action bk-navbar-action-ghost d-flex align-items-center gap-2">
                    <i data-feather="check" style="width:14px;height:14px;"></i>
                    {{ __('Mark all read') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    @include('owner.partials.flash')

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @forelse($notifications as $n)
                <a href="{{ route('owner.notifications.read', $n->id) }}"
                   class="d-flex align-items-start gap-3 px-4 py-3 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}"
                   style="{{ $n->read_at ? '' : 'background:color-mix(in srgb,var(--bk-accent) 6%,transparent);' }}">
                    <span style="font-size:1.3rem;line-height:1;">{{ $n->icon }}</span>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="tx-14 fw-{{ $n->read_at ? 'semibold' : 'bold' }}" style="color:var(--bk-text);">{{ $n->title }}</div>
                        <div class="tx-13 text-muted">{{ $n->body }}</div>
                        <div class="tx-11 text-muted mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                    </div>
                    @unless($n->read_at)
                        <span class="rounded-circle flex-shrink-0" style="width:9px;height:9px;background:var(--bk-accent);margin-top:6px;"></span>
                    @endunless
                </a>
            @empty
                <div class="text-center text-muted py-5">
                    <i data-feather="bell-off" style="width:32px;height:32px;opacity:.4;"></i>
                    <div class="mt-2 tx-14">{{ __('No notifications yet') }}</div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>

</div>
@endsection
