@php
    /** @var \App\Models\BranchImage $img */
    $reasons   = config('gallery.rejection_reasons', []);
    $canManage = $img->canBusinessManage();
    $isTeam    = $img->isTeam();
@endphp
<div class="pg-item status-{{ $img->status }} @if($img->is_cover) is-cover @endif @if(!$canManage) is-locked @endif"
     data-id="{{ $img->id }}"
     data-type="{{ $img->type }}"
     data-status="{{ $img->status }}"
     data-approved="{{ $img->isApproved() ? '1' : '0' }}"
     data-locked="{{ $canManage ? '0' : '1' }}">

    <img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">

    {{-- Status badge (top-start) --}}
    <span class="pg-badge pg-badge-{{ $img->status }}">
        @if($img->isApproved()) {{ __('Approved') }}
        @elseif($img->isPending()) {{ __('Under review') }}
        @else {{ __('Rejected') }}
        @endif
    </span>

    {{-- Cover / team marks (top-end) --}}
    <div class="pg-marks">
        @if($img->is_cover)
            <span class="pg-mark pg-mark-cover" title="{{ __('Cover photo') }}">
                <i data-feather="star" style="width:12px;height:12px;"></i>
            </span>
        @endif
        @if($isTeam)
            <span class="pg-mark pg-mark-team" title="{{ __('By GlowRez team') }}">
                <i data-feather="award" style="width:12px;height:12px;"></i>
            </span>
        @endif
    </div>

    {{-- Rejection reason --}}
    @if($img->isRejected() && $img->rejection_reason)
        <div class="pg-reason">{{ __($reasons[$img->rejection_reason] ?? $img->rejection_reason) }}</div>
    @endif

    {{-- A small hint that the tile opens larger; actions live in the enlarged view. --}}
    <span class="pg-zoom" aria-hidden="true"><i data-feather="maximize-2" style="width:13px;height:13px;"></i></span>
</div>
