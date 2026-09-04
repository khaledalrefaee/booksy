@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

<div class="page-content bm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('owner.branches.services.index', $branch) }}">{{ $branch->localizedName() }}</a>
                <span aria-hidden="true">·</span> {{ __('New service') }}
            </div>
            <h1 class="bm-title">{{ __('New service') }}</h1>
            <p class="bm-subtitle" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="bm-chip"><i data-feather="briefcase"></i><span>{{ $branch->company?->localizedName() ?? '—' }}</span></span>
                <span class="bm-chip"><i data-feather="map-pin"></i><span>{{ $branch->localizedName() }}</span></span>
            </p>
        </div>
    </header>

    @include('owner.partials.flash')

    <div class="bm-form-card bm-reveal">
        <form method="post" action="{{ route('owner.branches.services.store', $branch) }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="bm-form-body">
                @include('owner.services.partials.form-fields')
            </div>
            <div class="bm-form-foot">
                <a href="{{ route('owner.branches.services.index', $branch) }}" class="bm-btn bm-btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="bm-btn bm-btn-primary bm-spacer"><i data-feather="check"></i>{{ __('Save service') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
