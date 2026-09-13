@extends('employee.layout')
@section('title', __('My visits'))
@section('content')

<h1 class="ev-h">{{ __('My visits') }}</h1>
<p class="ev-sub">{{ __('Every place you have logged.') }}</p>

<form method="GET" action="{{ route('employee.field-visits.index') }}" class="ev-field">
    <input type="search" name="q" value="{{ $q }}" class="ev-input" placeholder="{{ __('Search place, area or contact…') }}">
</form>

<div class="ev-card">
    @forelse($visits as $v)
        <a href="{{ route('employee.field-visits.show', $v) }}" class="ev-row">
            <span class="ev-row-ic"><i data-feather="map-pin"></i></span>
            <span class="ev-row-body">
                <span class="ev-row-title">{{ $v->place_name }}</span>
                <span class="ev-row-meta">
                    <span>{{ $v->visited_at?->translatedFormat('M j, H:i') }}</span>
                    @if($v->area)<span>{{ $v->area }}</span>@endif
                    @if($v->interest_level)<span>{{ str_repeat('★', $v->interest_level) }}</span>@endif
                    @if($v->checked_out_at)<span style="color:var(--bk-success);">✓</span>@endif
                </span>
            </span>
            <span class="ev-chevron"><i data-feather="{{ app()->getLocale() === 'ar' ? 'chevron-left' : 'chevron-right' }}"></i></span>
        </a>
    @empty
        <div class="ev-empty"><i data-feather="map"></i><p>{{ $q !== '' ? __('No matches.') : __('No visits yet.') }}</p></div>
    @endforelse
</div>

@if($visits->hasPages())
    <div style="margin-top:16px;">{{ $visits->links() }}</div>
@endif

@endsection
