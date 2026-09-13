@extends('employee.layout')
@section('title', __('Field sales'))
@section('content')

<h1 class="ev-h">{{ __('Hi, :name 👋', ['name' => \Illuminate\Support\Str::before($owner->name, ' ')]) }}</h1>
<p class="ev-sub">{{ __('Log every visit while you are on site — it only takes a minute.') }}</p>

<div class="ev-stats">
    <div class="ev-stat" style="--accent:var(--bk-accent);--accent-wash:var(--bk-accent-wash);">
        <div class="ev-stat-ic"><i data-feather="sun"></i></div>
        <div class="ev-stat-v">{{ $today }}</div><div class="ev-stat-l">{{ __('Today') }}</div>
    </div>
    <div class="ev-stat" style="--accent:var(--bk-info);--accent-wash:var(--bk-info-bg);">
        <div class="ev-stat-ic"><i data-feather="calendar"></i></div>
        <div class="ev-stat-v">{{ $week }}</div><div class="ev-stat-l">{{ __('This week') }}</div>
    </div>
    <div class="ev-stat" style="--accent:var(--bk-gold-strong);--accent-wash:var(--bk-gold-soft);">
        <div class="ev-stat-ic"><i data-feather="map-pin"></i></div>
        <div class="ev-stat-v">{{ $total }}</div><div class="ev-stat-l">{{ __('Total') }}</div>
    </div>
</div>

<a href="{{ route('employee.field-visits.create') }}" class="ev-btn ev-btn-primary" style="margin-top:18px;">
    <i data-feather="map-pin"></i>{{ __('Log a visit') }}
</a>

@if($followUps->isNotEmpty())
    <div class="ev-sec"><span class="ev-sec-ic" style="background:var(--bk-warning-bg);color:var(--bk-warning);"><i data-feather="clock"></i></span><span class="ev-sec-title">{{ __('Follow-ups due') }}</span></div>
    <div class="ev-card">
        @foreach($followUps as $v)
            <a href="{{ route('employee.field-visits.show', $v) }}" class="ev-row">
                <span class="ev-row-ic" style="background:var(--bk-warning-bg);color:var(--bk-warning);"><i data-feather="bell"></i></span>
                <span class="ev-row-body">
                    <span class="ev-row-title">{{ $v->place_name }}</span>
                    <span class="ev-row-meta"><span>{{ $v->follow_up_date?->translatedFormat('M j') }}</span>@if($v->contact_name)<span>{{ $v->contact_name }}</span>@endif</span>
                </span>
                <span class="ev-chevron"><i data-feather="{{ app()->getLocale() === 'ar' ? 'chevron-left' : 'chevron-right' }}"></i></span>
            </a>
        @endforeach
    </div>
@endif

<div class="ev-sec">
    <span class="ev-sec-ic"><i data-feather="clock"></i></span>
    <span class="ev-sec-title">{{ __('Recent visits') }}</span>
    @if($recent->isNotEmpty())<a href="{{ route('employee.field-visits.index') }}" class="ev-sec-link">{{ __('View all') }}</a>@endif
</div>
<div class="ev-card">
    @forelse($recent as $v)
        <a href="{{ route('employee.field-visits.show', $v) }}" class="ev-row">
            <span class="ev-row-ic"><i data-feather="map-pin"></i></span>
            <span class="ev-row-body">
                <span class="ev-row-title">{{ $v->place_name }}</span>
                <span class="ev-row-meta">
                    <span>{{ $v->visited_at?->diffForHumans() }}</span>
                    @if($v->area)<span>{{ $v->area }}</span>@endif
                    @if($v->checked_out_at)<span style="color:var(--bk-success);">✓ {{ __('Done') }}</span>@endif
                </span>
            </span>
            <span class="ev-chevron"><i data-feather="{{ app()->getLocale() === 'ar' ? 'chevron-left' : 'chevron-right' }}"></i></span>
        </a>
    @empty
        <div class="ev-empty"><i data-feather="map"></i><p>{{ __('No visits yet. Tap “Log a visit” to start.') }}</p></div>
    @endforelse
</div>

@endsection
