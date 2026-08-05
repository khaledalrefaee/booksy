@php($title = __('Verify your account'))
@extends('company.auth.layout')

@section('hero-icon')<i data-feather="check-circle"></i>@endsection
@section('hero-title'){{ __('One last step') }}@endsection
@section('hero-sub'){{ __('Confirm your account with the code we just sent — and you\'re all set to start taking bookings.') }}@endsection

@section('content')
    <h4 class="fw-bold mb-1">{{ __('Verify your account') }}</h4>
    <p class="text-muted mb-4">
        {{ __('We sent a 4-digit code to your WhatsApp') }}
        @if($phone)<strong dir="ltr">{{ $phone }}</strong>@endif
        {{ __('and email') }}
        @if($email)<strong dir="ltr">{{ $email }}</strong>@endif.
    </p>

    @if (session('status'))
        <div class="alert alert-success py-2 px-3 mb-3" role="alert" aria-live="polite">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" role="alert" aria-live="assertive">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.verify.attempt') }}" id="verifyForm" novalidate>
        @csrf
        <label class="form-label fw-semibold">{{ __('Verification code') }}</label>
        <div class="bk-otp-row">
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp0" autocomplete="one-time-code" autofocus>
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp1">
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp2">
            <input class="bk-otp-box" type="tel" inputmode="numeric" maxlength="1" id="otp3">
        </div>
        <input type="hidden" name="code" id="code">

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-3">{{ __('Verify') }}</button>
        </div>
    </form>

    <div class="mt-3 text-center">
        <span class="text-muted small">{{ __("Didn't get the code?") }}</span>
        <form method="POST" action="{{ route('company.verify.resend') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0 ms-1 fw-semibold text-decoration-none align-baseline">{{ __('Resend code') }}</button>
        </form>
    </div>

    <div class="mt-2 text-center">
        <a href="{{ route('company.dashboard') }}" class="small text-muted text-decoration-none">{{ __('Skip for now') }}</a>
    </div>
@endsection

@push('scripts')
<script>
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
                if (e.key === 'Enter') { e.preventDefault(); document.getElementById('verifyForm').requestSubmit(); }
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
</script>
@endpush
