@php($title = __('Verify code'))
@extends('company.auth.layout')

@section('hero-icon')<i data-feather="shield"></i>@endsection
@section('hero-title'){{ __('Almost there!') }}@endsection
@section('hero-sub'){{ __('Enter the verification code we sent you, then choose a new password.') }}@endsection

@section('content')
    <a href="{{ route('company.login') }}" class="noble-ui-logo d-md-none d-inline-flex align-items-center mb-3" style="gap:.5rem;text-decoration:none">
        <x-front.logo variant="full" style="height:42px;width:auto"/>
        <small class="text-muted fw-normal fs-6">Business</small>
    </a>

    <h4 class="fw-bold mb-1">{{ __('Enter verification code') }}</h4>
    <p class="text-muted mb-4">
        @if($identifier)
            {{ __('We sent a 4-digit code to') }} <strong dir="ltr">{{ $identifier }}</strong>
            @if($channel === 'whatsapp') <span class="text-success">({{ __('WhatsApp') }})</span> @else ({{ __('Email') }}) @endif
        @else
            {{ __('Enter the 4-digit code we sent you.') }}
        @endif
    </p>

    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" role="alert" aria-live="assertive">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.password.update') }}" id="resetForm" novalidate>
        @csrf

        <label class="form-label fw-semibold">{{ __('Verification code') }}</label>
        <div class="bk-otp-row">
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp0" autocomplete="one-time-code" autofocus>
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp1">
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp2">
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp3">
        </div>
        <input type="hidden" name="code" id="code">

        <div class="mb-3 mt-4">
            <label for="password" class="form-label fw-semibold">{{ __('New password') }} <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" id="password" name="password"
                       class="form-control rounded-start-3" placeholder="••••••••"
                       autocomplete="new-password" minlength="8" required>
                <button class="btn btn-outline-secondary js-toggle-pw" type="button" data-target="#password" tabindex="-1" aria-label="{{ __('Show password') }}">
                    <i data-feather="eye" style="width:15px;height:15px;"></i>
                </button>
            </div>
            <div class="bk-pw-meter" id="pwMeter" hidden aria-hidden="true">
                <div class="bk-pw-bars"><span></span><span></span><span></span><span></span></div>
                <div class="bk-pw-label" id="pwLabel"></div>
            </div>
            <div class="form-hint">{{ __('At least 8 characters.') }}</div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Reset password') }}</button>
        </div>

        <div class="mt-3 text-center">
            <span class="text-muted small">{{ __("Didn't get the code?") }}</span>
            <a href="{{ route('company.password.forgot') }}" class="small ms-1 fw-semibold text-decoration-none">{{ __('Try again') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // OTP boxes → hidden `code`
    (function () {
        const boxes = [0,1,2,3].map(i => document.getElementById('otp' + i));
        const code  = document.getElementById('code');
        function collect() { code.value = boxes.map(b => b.value).join(''); }
        boxes.forEach((box, i) => {
            box.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 1);
                if (this.value && i < 3) boxes[i + 1].focus();
                collect();
            });
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && i > 0) boxes[i - 1].focus();
                if (e.key === 'Enter') { e.preventDefault(); document.getElementById('resetForm').requestSubmit(); }
            });
            box.addEventListener('paste', function (e) {
                const t = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 4);
                for (let j = 0; j < t.length; j++) if (boxes[j]) boxes[j].value = t[j];
                collect();
                (boxes[Math.min(t.length, 3)] || boxes[3]).focus();
                e.preventDefault();
            });
        });
    })();

    // Password show/hide
    document.querySelectorAll('.js-toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.querySelector(this.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = this.querySelector('[data-feather]');
            icon.setAttribute('data-feather', input.type === 'password' ? 'eye' : 'eye-off');
            feather.replace();
        });
    });

    // Password strength meter
    (function () {
        const pw = document.getElementById('password');
        const meter = document.getElementById('pwMeter');
        const bars = meter ? meter.querySelectorAll('.bk-pw-bars span') : [];
        const label = document.getElementById('pwLabel');
        if (!pw || !meter) return;
        @php($pwLabels = [__('Weak'), __('Fair'), __('Good'), __('Strong')])
        const labels = @json($pwLabels);
        const colors = ['var(--bk-danger)', 'var(--bk-warning)', 'var(--bk-info)', 'var(--bk-success)'];
        function score(v){ let s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++; if(/\d/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++; return Math.min(s,4); }
        pw.addEventListener('input', function () {
            const v = pw.value;
            if (!v) { meter.hidden = true; return; }
            meter.hidden = false;
            const s = score(v);
            bars.forEach((bar, i) => { bar.style.background = i < s ? colors[Math.max(0, s - 1)] : 'var(--bk-border)'; });
            label.textContent = s > 0 ? labels[Math.max(0, s - 1)] : '';
            label.style.color = s > 0 ? colors[Math.max(0, s - 1)] : 'var(--bk-text-muted)';
        });
    })();
</script>
@endpush
