@extends('owner.dashboard')
@section('content')
@include('owner.leads._styles')

@php
    use App\Support\LeadCatalog;

    $dash = '<span class="ld-none">—</span>';
    $waText = app()->getLocale() === 'ar'
        ? "مرحباً {$lead->full_name}، معك فريق GlowRez. وصلنا تسجيل اهتمامك بالانضمام عن منشأة {$lead->business_name}."
        : "Hello {$lead->full_name}, this is the GlowRez team. We received your interest for {$lead->business_name}.";
    $wa   = $lead->whatsappUrl($waText);
    $tel  = $lead->telUrl();
    $mail = $lead->email && filter_var($lead->email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$lead->email : null;

    $fieldLabels = [
        'full_name' => __('Name'), 'business_name' => __('Business'), 'business_type' => __('Business type'), 'city' => __('City'),
        'number_of_branches' => __('Branches'), 'whatsapp' => 'WhatsApp', 'email' => __('Email'), 'area' => __('Area'),
        'website' => __('Website'), 'instagram' => 'Instagram', 'facebook' => 'Facebook',
    ];
    $fmt = function (string $field, $v) {
        if ($v === null || $v === '') return '—';
        return match ($field) {
            'business_type' => LeadCatalog::label('business_types', $v),
            'city' => LeadCatalog::label('cities', $v),
            default => (string) $v,
        };
    };
    $iconFor = ['created' => 'user-plus', 'resubmitted' => 'repeat', 'reopened' => 'rotate-ccw', 'status_changed' => 'flag', 'note' => 'message-square'];
@endphp

<div class="page-content bm-wrap ld-wrap">
    <header class="bm-head bm-reveal">
        <div style="min-width:0;">
            <div class="bm-eyebrow"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a><span aria-hidden="true">·</span><a href="{{ route('owner.leads.index') }}">{{ __('Leads') }}</a></div>
            <h1 class="bm-title" style="overflow-wrap:anywhere;">{{ $lead->business_name }}</h1>
            <p class="bm-subtitle">{{ $lead->full_name }} · {{ $lead->businessTypeLabel() }} · {{ $lead->cityLabel() }}@if($lead->area) · {{ $lead->area }}@endif</p>
            <div class="ld-meta-strip">
                <span>{{ __('Registered') }}: <b>{{ $lead->created_at?->translatedFormat('d M Y, H:i') }}</b></span>
                @if($lead->interaction_count > 1)<span>{{ __('Interactions') }}: <b>{{ $lead->interaction_count }}</b></span>
                    <span>{{ __('Last activity') }}: <b>{{ $lead->last_interaction_at?->translatedFormat('d M Y, H:i') }}</b></span>@endif
                @if($lead->contacted_at)<span>{{ __('First contacted') }}: <b>{{ $lead->contacted_at->translatedFormat('d M Y') }}@if($lead->contactedBy) · {{ $lead->contactedBy->name }}@endif</b></span>@endif
            </div>
        </div>
        <div class="bm-head-actions" style="flex-direction:column;align-items:flex-end;gap:12px;">
            @include('owner.leads._status', ['lead' => $lead, 'canManage' => $canManage])
        </div>
    </header>

    @include('owner.partials.flash')

    <div class="ld-hero-actions bm-reveal" style="margin-bottom:18px;">
        @if($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="bm-btn ld-wa-btn"><i data-feather="message-circle"></i>{{ __('Contact via WhatsApp') }}</a>@endif
        @if($tel)<a href="{{ $tel }}" class="bm-btn bm-btn-ghost"><i data-feather="phone"></i>{{ __('Call') }}</a>@endif
        @if($mail)<a href="{{ $mail }}" class="bm-btn bm-btn-ghost"><i data-feather="mail"></i>{{ __('Send email') }}</a>@endif
        <a href="{{ route('owner.leads.index') }}" class="bm-btn bm-btn-ghost" style="margin-inline-start:auto;"><i data-feather="arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>{{ __('All leads') }}</a>
    </div>

    <div class="ld-detail">
        {{-- Left: what we know --}}
        <div>
            <section class="ld-box bm-reveal">
                <h2><i data-feather="user"></i>{{ __('Contact') }}</h2>
                <dl class="ld-dl">
                    <div><dt>{{ __('Name') }}</dt><dd>{{ $lead->full_name }}</dd></div>
                    <div><dt>{{ __('Phone') }}</dt><dd>@if($tel)<a href="{{ $tel }}" class="ld-ltr">{{ $lead->phone }}</a>@else<span class="ld-ltr">{{ $lead->phone }}</span>@endif</dd></div>
                    <div><dt>WhatsApp</dt><dd>@if($lead->whatsapp)@if($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="ld-ltr">{{ $lead->whatsapp }}</a>@else<span class="ld-ltr">{{ $lead->whatsapp }}</span> <span class="ld-none">({{ __('not a valid number') }})</span>@endif @else {!! $dash !!} @endif</dd></div>
                    <div><dt>{{ __('Email') }}</dt><dd>@if($mail)<a href="{{ $mail }}" class="ld-ltr">{{ $lead->email }}</a>@else {!! $dash !!} @endif</dd></div>
                </dl>
            </section>

            <section class="ld-box bm-reveal">
                <h2><i data-feather="briefcase"></i>{{ __('Business') }}</h2>
                <dl class="ld-dl">
                    <div><dt>{{ __('Business name') }}</dt><dd>{{ $lead->business_name }}</dd></div>
                    <div><dt>{{ __('Business type') }}</dt><dd>{{ $lead->businessTypeLabel() }}</dd></div>
                    <div><dt>{{ __('City') }}</dt><dd>{{ $lead->cityLabel() }}</dd></div>
                    <div><dt>{{ __('Area') }}</dt><dd>{!! $lead->area ? e($lead->area) : $dash !!}</dd></div>
                    <div><dt>{{ __('Branches') }}</dt><dd>{{ $lead->number_of_branches }}</dd></div>
                    <div><dt>Instagram</dt><dd>@if($u = $lead->instagramUrl())<a href="{{ $u }}" target="_blank" rel="noopener nofollow" class="ld-ltr">{{ $lead->instagram }}</a>@elseif($lead->instagram)<span class="ld-ltr">{{ $lead->instagram }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>Facebook</dt><dd>@if($u = $lead->facebookUrl())<a href="{{ $u }}" target="_blank" rel="noopener nofollow" class="ld-ltr">{{ $lead->facebook }}</a>@elseif($lead->facebook)<span class="ld-ltr">{{ $lead->facebook }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>{{ __('Website') }}</dt><dd>@if($u = $lead->websiteUrl())<a href="{{ $u }}" target="_blank" rel="noopener nofollow" class="ld-ltr">{{ $lead->website }}</a>@else {!! $dash !!} @endif</dd></div>
                </dl>
            </section>

            <section class="ld-box bm-reveal">
                <h2><i data-feather="heart"></i>{{ __('Interests') }}</h2>
                @if($lead->interests)
                    <div class="ld-tags">@foreach($lead->interestLabels() as $label)<span class="ld-tag"><i data-feather="check" style="width:12px;height:12px;"></i>{{ $label }}</span>@endforeach</div>
                @else
                    <p class="ld-empty-line">{{ __('No interests selected.') }}</p>
                @endif
            </section>

            <section class="ld-box bm-reveal">
                <h2><i data-feather="compass"></i>{{ __('Where they came from') }}</h2>
                <dl class="ld-dl">
                    <div><dt>{{ __('Source') }}</dt><dd>{{ $lead->sourceLabel() }}</dd></div>
                    <div><dt>{{ __('Campaign') }}</dt><dd>@if($lead->campaign)<span class="ld-ltr">{{ $lead->campaign }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>UTM source</dt><dd>@if($lead->utm_source)<span class="ld-ltr">{{ $lead->utm_source }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>UTM medium</dt><dd>@if($lead->utm_medium)<span class="ld-ltr">{{ $lead->utm_medium }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>UTM campaign</dt><dd>@if($lead->utm_campaign)<span class="ld-ltr">{{ $lead->utm_campaign }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>UTM content</dt><dd>@if($lead->utm_content)<span class="ld-ltr">{{ $lead->utm_content }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>{{ __('Landing page') }}</dt><dd>@if($lead->landing_page)<span class="ld-ltr">{{ $lead->landing_page }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>{{ __('Referred from') }}</dt><dd>@if($lead->referral)<span class="ld-ltr">{{ $lead->referral }}</span>@else {!! $dash !!} @endif</dd></div>
                    <div><dt>{{ __('Form language') }}</dt><dd>{{ $lead->locale === 'en' ? 'English' : 'العربية' }}</dd></div>
                </dl>
            </section>
        </div>

        {{-- Right: notes + timeline --}}
        <div>
            @if($canManage)
            <section class="ld-box bm-reveal">
                <h2><i data-feather="edit-3"></i>{{ __('Add note') }}</h2>
                <form method="POST" action="{{ route('owner.leads.notes.store', $lead) }}" class="ld-note-form">
                    @csrf
                    <label for="ld-note" class="visually-hidden" style="position:absolute;left:-9999px;">{{ __('Note') }}</label>
                    <textarea id="ld-note" name="body" maxlength="2000" required placeholder="{{ __('e.g. Called the owner, interested in bookings and staff. Wants a demo next week.') }}">{{ old('body') }}</textarea>
                    @error('body')<div class="ld-err" role="alert">{{ $message }}</div>@enderror
                    <div class="ld-note-foot">
                        <span class="bm-help" style="margin:0;">{{ __('Notes are visible to the whole team.') }}</span>
                        <button type="submit" class="bm-btn bm-btn-primary" data-loading>{{ __('Save note') }}</button>
                    </div>
                </form>
            </section>
            @endif

            <section class="ld-box bm-reveal">
                <h2><i data-feather="activity"></i>{{ __('Activity') }}</h2>
                <ol class="ld-timeline">
                    @foreach($lead->activities as $a)
                        @php $m = $a->meta ?? []; @endphp
                        <li class="ld-tl t-{{ $a->type }}">
                            <span class="ld-tl-ic"><i data-feather="{{ $iconFor[$a->type] ?? 'circle' }}"></i></span>
                            <div style="min-width:0;">
                                <div class="ld-tl-title">
                                    @switch($a->type)
                                        @case('created') {{ __('Lead created') }} @break
                                        @case('resubmitted') {{ __('Registered again') }} @break
                                        @case('reopened') {{ __('Lead re-opened') }} @break
                                        @case('status_changed') {{ __('Status changed') }}: {{ LeadCatalog::label('statuses', $m['from'] ?? '') }} → {{ LeadCatalog::label('statuses', $m['to'] ?? '') }} @break
                                        @case('note') {{ __('Note added') }} @break
                                        @default {{ $a->type }}
                                    @endswitch
                                </div>

                                @if($a->type === 'note' && $a->body)<div class="ld-tl-body">{{ $a->body }}</div>@endif

                                @if(in_array($a->type, ['created', 'resubmitted'], true) && (!empty($m['source']) || !empty($m['campaign'])))
                                    <div class="ld-tl-sub">{{ __('Source') }}: {{ LeadCatalog::label('sources', $m['source'] ?? '') }}@if(!empty($m['campaign'])) · <span class="ld-ltr">{{ $m['campaign'] }}</span>@endif</div>
                                @endif

                                @if($a->type === 'resubmitted')
                                    @if(!empty($m['changes']))
                                        <ul class="ld-tl-changes">
                                            @foreach($m['changes'] as $field => $pair)
                                                <li>{{ $fieldLabels[$field] ?? $field }}: <s>{{ $fmt($field, $pair[0] ?? null) }}</s> → <b>{{ $fmt($field, $pair[1] ?? null) }}</b></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if(!empty($m['added_interests']))
                                        <div class="ld-tl-sub">{{ __('New interests') }}: {{ implode(' · ', LeadCatalog::interestLabels($m['added_interests'])) }}</div>
                                    @endif
                                @endif

                                <div class="ld-tl-sub">
                                    {{ $a->owner?->name ?? ($a->type === 'note' || $a->type === 'status_changed' ? __('Team member') : __('Visitor form')) }}
                                    · {{ $a->created_at?->translatedFormat('d M Y, H:i') }}
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var fail = @json(__('Could not update the status. Please try again.'));
    document.addEventListener('change', function (e) {
        var sel = e.target.closest('select[data-status-url]'); if (!sel) return;
        var prev = sel.getAttribute('data-current'); sel.disabled = true;
        fetch(sel.getAttribute('data-status-url'), { method: 'PATCH', credentials: 'same-origin', body: JSON.stringify({ status: sel.value }),
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (r) {
                if (r.ok && r.body.status) { location.reload(); }           // reload so the timeline shows the change
                else { sel.disabled = false; sel.value = prev; if (window.GlowToast) GlowToast.error((r.body && r.body.message) || fail); }
            })
            .catch(function () { sel.disabled = false; sel.value = prev; if (window.GlowToast) GlowToast.error(fail); });
    });
    if (typeof feather !== 'undefined') setTimeout(function () { feather.replace(); }, 60);
})();
</script>
@endpush
@endsection
