@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')
@include('owner.team._styles')

<div class="page-content bm-wrap">
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.team.index') }}">{{ __('Team') }}</a>
                <span aria-hidden="true">·</span> {{ __('New member') }}
            </div>
            <h1 class="bm-title">{{ __('Add team member') }}</h1>
            <p class="bm-subtitle">{{ __('Create a GlowRez staff account, choose their role and fine-tune what they can access. Set a password or let one be generated for their first sign-in.') }}</p>
        </div>
        <div class="bm-head-actions">
            <a href="{{ route('owner.team.index') }}" class="bm-btn bm-btn-ghost"><i data-feather="arrow-left"></i>{{ __('Back') }}</a>
        </div>
    </header>

    <form method="POST" action="{{ route('owner.team.store') }}" class="bm-form-card bm-reveal" style="max-width:920px;">
        @csrf
        <div class="bm-form-body">
            @include('owner.team._form')
        </div>
        <div class="bm-form-foot">
            <a href="{{ route('owner.team.index') }}" class="bm-btn bm-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" class="bm-btn bm-btn-primary bm-spacer" data-loading><i data-feather="user-plus"></i>{{ __('Create member') }}</button>
        </div>
    </form>
</div>

@push('scripts')<script>if(typeof feather!=='undefined')setTimeout(function(){feather.replace();},60);</script>@endpush
@endsection
