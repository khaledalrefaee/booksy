@extends('company.dashboard')
@section('content')

@php
    $meta = [
        'confirmation' => ['icon' => 'check-circle', 'title' => __('Confirmation'), 'desc' => __('Sent when a booking is created.')],
        'reminder'     => ['icon' => 'clock',        'title' => __('Reminder'),     'desc' => __('Sent before the appointment.')],
        'followup'     => ['icon' => 'refresh-cw',   'title' => __('Follow-up'),    'desc' => __('Sent after the last visit.')],
    ];
    // Build "{{ var }}" without literal double-braces so Blade doesn't parse them.
    $wrap = fn ($v) => '{' . '{' . $v . '}' . '}';
@endphp

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('company.sms.overview') }}">{{ __('SMS') }}</a>
                <span aria-hidden="true">·</span> {{ __('Setup') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Templates') }}</h1>
            <p class="sx-subtitle">{{ __('Personalise each message with variables. The counter shows the length and how many SMS it will cost.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('company.sms.automations') }}" class="sx-btn sx-btn-ghost"><i data-feather="zap"></i>{{ __('Automations') }}</a>
        </div>
    </header>

    @include('company.partials.flash')

    {{-- Variable reference --}}
    <div class="sx-card sx-reveal" style="margin-bottom:18px;">
        <div class="sx-card-pad">
            <div class="sx-name" style="margin-bottom:10px;">{{ __('Available variables') }} <span class="sx-sub" style="font-weight:400;">— {{ __('click to insert') }}</span></div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @foreach($variables as $v)
                    <button type="button" class="sx-var" data-var="{{ $wrap($v) }}">{{ $wrap($v) }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:16px;">
    @foreach($keys as $key)
        <div class="sx-card sx-reveal">
            <form method="POST" action="{{ route('company.sms.templates.update') }}">
                @csrf @method('PUT')
                <input type="hidden" name="key" value="{{ $key }}">
                <div class="sx-card-head">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="sx-auto-ic"><i data-feather="{{ $meta[$key]['icon'] }}"></i></span>
                        <div>
                            <h2 class="sx-card-title" style="font-size:1.05rem;">{{ $meta[$key]['title'] }}</h2>
                            <p class="sx-card-note">{{ $meta[$key]['desc'] }}</p>
                        </div>
                    </div>
                    <button type="submit" class="sx-btn sx-btn-primary sx-btn-sm"><i data-feather="save"></i>{{ __('Save') }}</button>
                </div>
                <div class="sx-card-pad">
                    <textarea name="body" class="sx-input sx-tpl" data-key="{{ $key }}" rows="4" maxlength="1000" dir="auto">{{ $templates[$key] }}</textarea>
                    <div class="sx-counter" data-counter="{{ $key }}">
                        <span>{{ __('Characters') }}: <strong class="c-len">0</strong></span>
                        <span>{{ __('Encoding') }}: <strong class="c-enc">—</strong></span>
                        <span>{{ __('SMS count') }}: <span class="seg-pill c-seg">1</span></span>
                    </div>
                </div>
            </form>
        </div>
    @endforeach
    </div>
</div>

@push('company-styles')
    @include('company.sms.partials.styles')
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    // GSM 03.38 basic + extended, mirrors App\Services\Sms\SmsSegment.
    var GSM = "@\u00a3$\u00a5\u00e8\u00e9\u00f9\u00ec\u00f2\u00c7\n\u00d8\u00f8\r\u00c5\u00e5\u0394_\u03a6\u0393\u039b\u03a9\u03a0\u03a8\u03a3\u0398\u039e \u00c6\u00e6\u00df\u00c9 !\"#\u00a4%&'()*+,-./0123456789:;<=>?\u00a1ABCDEFGHIJKLMNOPQRSTUVWXYZ\u00c4\u00d6\u00d1\u00dc\u00a7\u00bfabcdefghijklmnopqrstuvwxyz\u00e4\u00f6\u00f1\u00fc\u00e0";
    var EXT = ['^','{','}','\\','[',']','~','|','\u20ac'];

    function isGsm(text) {
        for (var ch of text) { if (GSM.indexOf(ch) === -1 && EXT.indexOf(ch) === -1) return false; }
        return true;
    }
    function analyze(text) {
        var unicode = !isGsm(text), len = 0;
        for (var ch of text) {
            if (unicode) { len += (ch.codePointAt(0) > 0xFFFF) ? 2 : 1; }
            else { len += (EXT.indexOf(ch) !== -1) ? 2 : 1; }
        }
        var single = unicode ? 70 : 160, multi = unicode ? 67 : 153;
        var seg = len === 0 ? 0 : (len <= single ? 1 : Math.ceil(len / multi));
        return { len: len, seg: seg, enc: unicode ? '{{ __('Unicode') }}' : 'GSM-7' };
    }

    function refresh(area) {
        var box = document.querySelector('[data-counter="' + area.dataset.key + '"]');
        if (!box) return;
        var a = analyze(area.value);
        box.querySelector('.c-len').textContent = a.len;
        box.querySelector('.c-enc').textContent = a.enc;
        box.querySelector('.c-seg').textContent = Math.max(1, a.seg);
    }

    var areas = document.querySelectorAll('.sx-tpl');
    areas.forEach(function (a) { a.addEventListener('input', function () { refresh(a); }); refresh(a); });

    // Variable chips insert at the last-focused textarea's caret.
    var lastArea = areas[0] || null;
    areas.forEach(function (a) { a.addEventListener('focus', function () { lastArea = a; }); });
    document.querySelectorAll('.sx-var').forEach(function (chip) {
        chip.addEventListener('click', function () {
            if (!lastArea) return;
            var v = chip.dataset.var, s = lastArea.selectionStart, e = lastArea.selectionEnd;
            lastArea.value = lastArea.value.slice(0, s) + v + lastArea.value.slice(e);
            lastArea.focus();
            lastArea.selectionStart = lastArea.selectionEnd = s + v.length;
            refresh(lastArea);
        });
    });
})();
</script>
@endpush

@endsection
