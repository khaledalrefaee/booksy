@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

@php
    $tz = config('app.timezone');
    $bandMeta = [
        'high'    => ['label' => __('High confidence'),   'color' => 'var(--bk-success)'],
        'medium'  => ['label' => __('Medium confidence'), 'color' => 'var(--bk-warning)'],
        'low'     => ['label' => __('Low confidence'),    'color' => 'var(--bk-danger)'],
        'unknown' => ['label' => __('Unknown'),           'color' => 'var(--bk-text-muted)'],
    ][$band];
    $reasons = (array) ($visit->flag_reasons ?? []);
@endphp

<div class="page-content bm-wrap">
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow"><a href="{{ route('owner.field-sales.index') }}">{{ __('Field sales') }}</a><span aria-hidden="true">·</span> {{ $visit->owner?->name }}</div>
            <h1 class="bm-title">{{ $visit->place_name }}</h1>
            <p class="bm-subtitle">{{ $visit->visited_at?->timezone($tz)->translatedFormat('l, M j, Y · H:i') }}@if($visit->place_type) · {{ __(\App\Models\FieldVisit::PLACE_TYPES[$visit->place_type] ?? $visit->place_type) }}@endif</p>
        </div>
        <div class="bm-head-actions"><a href="{{ route('owner.field-sales.index') }}" class="bm-btn bm-btn-ghost"><i data-feather="arrow-left"></i>{{ __('Back') }}</a></div>
    </header>

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Sales content --}}
            <div class="bm-card" style="padding:22px 24px;margin-bottom:16px;">
                <dl class="row mb-0" style="row-gap:12px;">
                    <dt class="col-sm-4 text-muted">{{ __('Rep') }}</dt><dd class="col-sm-8">{{ $visit->owner?->name ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">{{ __('Area') }}</dt><dd class="col-sm-8">{{ $visit->area ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">{{ __('Contact person') }}</dt><dd class="col-sm-8">{{ $visit->contact_name ?: '—' }} @if($visit->contact_phone)<span class="text-muted" dir="ltr">· {{ $visit->contact_phone }}</span>@endif</dd>
                    <dt class="col-sm-4 text-muted">{{ __('Interest level') }}</dt><dd class="col-sm-8">{{ $visit->interest_level ? str_repeat('★', $visit->interest_level).str_repeat('☆', 5 - $visit->interest_level) : '—' }}</dd>
                    <dt class="col-sm-4 text-muted">{{ __('Explained GlowRez') }}</dt><dd class="col-sm-8">{{ $visit->explained_glowrez === null ? '—' : ($visit->explained_glowrez ? __('Yes') : __('No')) }}</dd>
                    <dt class="col-sm-4 text-muted">{{ __('System they use now') }}</dt><dd class="col-sm-8">{{ $visit->current_system ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">{{ __('Follow-up date') }}</dt><dd class="col-sm-8">{{ $visit->follow_up_date?->translatedFormat('M j, Y') ?: '—' }}</dd>
                </dl>
                @foreach(['problems'=>__('Problems'),'opinion'=>__('Opinion'),'notes'=>__('Notes')] as $field=>$label)
                    @if($visit->$field)<hr class="my-3"><p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.72rem;letter-spacing:.05em;">{{ $label }}</p><p class="mb-0" dir="auto">{{ $visit->$field }}</p>@endif
                @endforeach
            </div>

            @if($visit->photo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($visit->photo_path) }}" alt="" style="width:100%;border-radius:16px;border:1px solid var(--bk-border);">
            @endif
        </div>

        <div class="col-lg-5">
            {{-- Verification (manager-only) --}}
            <div class="bm-card" style="padding:20px 22px;margin-bottom:16px;">
                <div class="bm-section-head" style="margin-bottom:14px;"><i data-feather="shield"></i><h3 class="bm-section-title">{{ __('Visit confidence') }}</h3></div>
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                    <div style="width:64px;height:64px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-size:1.4rem;font-weight:700;color:{{ $bandMeta['color'] }};border:3px solid {{ $bandMeta['color'] }};">{{ $visit->confidence_score ?? '—' }}</div>
                    <div><div style="font-weight:700;color:{{ $bandMeta['color'] }};">{{ $bandMeta['label'] }}</div><div class="text-muted" style="font-size:.82rem;">{{ __('Automated presence check') }}</div></div>
                </div>
                @if($reasons)
                    <p class="text-muted text-uppercase fw-semibold mb-2" style="font-size:.7rem;letter-spacing:.05em;">{{ __('Signals to check') }}</p>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:7px;">
                        @foreach($reasons as $r)
                            <li style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--bk-text-soft);"><i data-feather="alert-triangle" style="width:14px;height:14px;color:var(--bk-warning);flex-shrink:0;"></i>{{ __($reasonLabels[$r] ?? $r) }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mb-0" style="font-size:.85rem;color:var(--bk-success);"><i data-feather="check-circle" style="width:14px;height:14px;vertical-align:-2px;"></i> {{ __('No red flags.') }}</p>
                @endif
            </div>

            {{-- Presence --}}
            <div class="bm-card" style="padding:20px 22px;margin-bottom:16px;">
                <div class="bm-section-head" style="margin-bottom:14px;"><i data-feather="navigation"></i><h3 class="bm-section-title">{{ __('Presence') }}</h3></div>
                <dl class="row mb-0" style="row-gap:9px;font-size:.87rem;">
                    <dt class="col-6 text-muted">{{ __('Location') }}</dt>
                    <dd class="col-6 text-end">@if($visit->hasLocation())<a href="https://www.google.com/maps?q={{ $visit->lat }},{{ $visit->lng }}" target="_blank" rel="noopener">{{ __('Open map') }}</a>@if($visit->gps_accuracy) <span class="text-muted">±{{ round($visit->gps_accuracy) }}m</span>@endif @elseif($visit->gps_denied)<span style="color:var(--bk-danger);">{{ __('Turned off') }}</span>@else—@endif</dd>
                    <dt class="col-6 text-muted">{{ __('Dwell time') }}</dt>
                    <dd class="col-6 text-end">{{ $visit->dwell_seconds ? \Carbon\CarbonInterval::seconds($visit->dwell_seconds)->cascade()->forHumans(['short'=>true,'parts'=>2]) : '—' }}</dd>
                    <dt class="col-6 text-muted">{{ __('Checked out') }}</dt>
                    <dd class="col-6 text-end">{{ $visit->checked_out_at?->timezone($tz)->format('H:i') ?? '—' }}</dd>
                    <dt class="col-6 text-muted">{{ __('Source') }}</dt>
                    <dd class="col-6 text-end">{{ ucfirst($visit->source) }}</dd>
                </dl>
            </div>

            {{-- Review --}}
            <div class="bm-card" style="padding:20px 22px;">
                <div class="bm-section-head" style="margin-bottom:14px;"><i data-feather="check-square"></i><h3 class="bm-section-title">{{ __('Review') }}</h3></div>
                @if($visit->review_status !== 'pending')
                    <p class="mb-2"><span class="bm-badge {{ $visit->review_status === 'approved' ? 'bm-badge-active' : 'bm-badge-inactive' }}">{{ __(ucfirst($visit->review_status)) }}</span>
                        @if($visit->reviewer) <span class="text-muted" style="font-size:.82rem;">{{ __('by') }} {{ $visit->reviewer->name }} · {{ $visit->reviewed_at?->diffForHumans() }}</span>@endif</p>
                    @if($visit->review_note)<p class="text-muted mb-3" style="font-size:.85rem;">{{ $visit->review_note }}</p>@endif
                @endif

                @if($canReview)
                    <form method="POST" action="{{ route('owner.field-sales.visits.review', $visit) }}">
                        @csrf @method('PATCH')
                        <textarea name="review_note" class="form-control mb-2" rows="2" placeholder="{{ __('Optional note…') }}" style="border-radius:11px;">{{ $visit->review_note }}</textarea>
                        <div class="d-flex gap-2">
                            <button type="submit" name="review_status" value="approved" class="bm-btn bm-btn-primary" style="flex:1;justify-content:center;"><i data-feather="check"></i>{{ __('Approve') }}</button>
                            <button type="submit" name="review_status" value="rejected" class="bm-btn bm-btn-danger" style="flex:1;justify-content:center;"><i data-feather="x"></i>{{ __('Reject') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')<script>if(typeof feather!=='undefined')setTimeout(function(){feather.replace();},60);</script>@endpush
@endsection
