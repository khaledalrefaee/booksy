@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')
@include('owner.team._styles')

<div class="page-content bm-wrap">
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.team.index') }}">{{ __('Team') }}</a>
                <span aria-hidden="true">·</span> {{ $employee->name }}
            </div>
            <h1 class="bm-title">{{ __('Edit team member') }}</h1>
            <p class="bm-subtitle">{{ __('Update their role, permissions and account status.') }}</p>
        </div>
        <div class="bm-head-actions">
            <form method="POST" action="{{ route('owner.team.reset-password', $employee) }}"
                  data-confirm="{{ __('Generate a new temporary password for this member?') }}">
                @csrf
                <button type="submit" class="bm-btn bm-btn-gold"><i data-feather="key"></i>{{ __('Reset password') }}</button>
            </form>
            <a href="{{ route('owner.team.index') }}" class="bm-btn bm-btn-ghost"><i data-feather="arrow-left"></i>{{ __('Back') }}</a>
        </div>
    </header>

    <form method="POST" action="{{ route('owner.team.update', $employee) }}" class="bm-form-card bm-reveal" style="max-width:920px;">
        @csrf @method('PUT')
        <div class="bm-form-body">
            @include('owner.team._form', ['employee' => $employee])
        </div>
        <div class="bm-form-foot">
            <a href="{{ route('owner.team.index') }}" class="bm-btn bm-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" class="bm-btn bm-btn-primary bm-spacer" data-loading><i data-feather="check"></i>{{ __('Save changes') }}</button>
        </div>
    </form>
</div>

@push('scripts')<script>if(typeof feather!=='undefined')setTimeout(function(){feather.replace();},60);</script>@endpush
@endsection
