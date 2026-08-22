@php
    $isAr = app()->getLocale() === 'ar';
    $branch = $appointment?->branch?->localizedName();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isAr ? 'إلغاء الموعد' : 'Cancel appointment' }} — GlowRez</title>
    <link rel="icon" href="{{ asset('images/logo-mark.png') }}">
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <style>
        :root{
            --olive:#6b8e23; --olive-deep:#556b2f; --gold:#c9a84a; --danger:#dc4b3e;
            --cream:#f7f4ec; --card:#fffdf8; --ink:#2f3320; --muted:#7c806e;
        }
        *{ margin:0; padding:0; box-sizing:border-box; }
        body{
            font-family:'{{ $isAr ? "Tajawal" : "Poppins" }}','Tajawal','Poppins',sans-serif;
            background:radial-gradient(120% 90% at 50% 0%, #efeadd 0%, var(--cream) 55%);
            color:var(--ink); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
        }
        .card{
            background:var(--card); border-radius:26px; padding:36px 30px 30px;
            max-width:460px; width:100%; box-shadow:0 24px 60px rgba(85,107,47,.16);
            border:1px solid rgba(107,142,35,.12); position:relative; overflow:hidden;
        }
        .card::before{ content:""; position:absolute; inset:0 0 auto 0; height:6px;
            background:linear-gradient(90deg, var(--olive), var(--gold)); }
        .logo{ height:40px; width:auto; margin:4px auto 22px; display:block; }
        h1{ font-size:21px; font-weight:800; color:var(--olive-deep); text-align:center; margin-bottom:6px; }
        .sub{ font-size:13.5px; color:var(--muted); text-align:center; line-height:1.6; margin-bottom:20px; }
        .summary{
            background:var(--cream); border-radius:16px; padding:14px 16px; margin-bottom:22px;
            border:1px solid rgba(107,142,35,.12);
        }
        .summary .row{ display:flex; gap:10px; align-items:center; font-size:13.5px; padding:4px 0; }
        .summary .row b{ color:var(--olive-deep); }
        .label{ font-size:13.5px; font-weight:700; color:var(--ink); margin:0 2px 10px; display:block; }
        .reasons{ display:flex; flex-direction:column; gap:9px; margin-bottom:18px; }
        .reason{
            display:flex; align-items:center; gap:11px; padding:13px 15px; border-radius:13px; cursor:pointer;
            border:1.5px solid rgba(107,142,35,.18); background:#fff; transition:border-color .15s, background .15s; font-size:14px;
        }
        .reason:hover{ border-color:var(--olive); }
        .reason input{ accent-color:var(--olive); width:18px; height:18px; flex-shrink:0; }
        .reason.sel{ border-color:var(--olive); background:color-mix(in srgb, var(--olive) 8%, #fff); }
        textarea{
            width:100%; border:1.5px solid rgba(107,142,35,.18); border-radius:13px; padding:12px 14px;
            font-family:inherit; font-size:14px; color:var(--ink); resize:vertical; min-height:74px; outline:none;
            background:#fff; transition:border-color .15s;
        }
        textarea:focus{ border-color:var(--olive); }
        .err{ color:var(--danger); font-size:12.5px; margin:8px 2px 0; min-height:16px; }
        .actions{ display:flex; gap:12px; margin-top:20px; }
        .btn{ flex:1; text-align:center; border:none; cursor:pointer; font-family:inherit; font-weight:700; font-size:14.5px;
            padding:14px; border-radius:999px; transition:transform .15s, box-shadow .15s, background .15s; }
        .btn-danger{ color:#fff; background:linear-gradient(135deg, #e0574a, var(--danger)); box-shadow:0 10px 22px rgba(220,75,62,.26); }
        .btn-danger:hover{ transform:translateY(-1px); }
        .btn-ghost{ color:var(--olive-deep); background:var(--cream); text-decoration:none; display:flex; align-items:center; justify-content:center; }
        .btn-ghost:hover{ background:#eee7d6; }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="{{ asset('images/logo-light.png') }}" alt="GlowRez">
        <h1>{{ $isAr ? 'إلغاء الموعد؟' : 'Cancel your appointment?' }}</h1>
        <p class="sub">{{ $isAr ? 'يؤسفنا ذلك. أخبرنا بالسبب حتى نتحسّن.' : 'Sorry to see you go. Let us know why so we can improve.' }}</p>

        <div class="summary">
            @if($branch)
                <div class="row"><span>📍</span> <b>{{ $branch }}</b></div>
            @endif
            <div class="row"><span>📅</span> {{ $appointment->start_time->translatedFormat('l d M Y') }} — ⏰ {{ $appointment->start_time->format('h:i A') }}</div>
        </div>

        <form method="POST" action="{{ route('appointment.cancel-do', ['token' => $token]) }}" id="cf">
            @csrf
            <span class="label">{{ $isAr ? 'سبب الإلغاء' : 'Reason for cancelling' }}</span>
            <div class="reasons">
                @foreach($reasons as $key => $label)
                    <label class="reason">
                        <input type="radio" name="reason" value="{{ $key }}" required>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <span class="label">{{ $isAr ? 'ملاحظة (اختياري)' : 'Note (optional)' }}</span>
            <textarea name="note" maxlength="400" placeholder="{{ $isAr ? 'أي تفاصيل إضافية…' : 'Any extra details…' }}"></textarea>
            <div class="err" id="err"></div>

            <div class="actions">
                <button type="submit" class="btn btn-danger">{{ $isAr ? 'تأكيد الإلغاء' : 'Confirm cancellation' }}</button>
                <a class="btn btn-ghost" href="{{ url('/') }}">{{ $isAr ? 'الاحتفاظ بالموعد' : 'Keep it' }}</a>
            </div>
        </form>
    </div>

    <script>
        (function(){
            var isAr = {{ $isAr ? 'true' : 'false' }};
            var radios = document.querySelectorAll('.reason input');
            radios.forEach(function(r){
                r.addEventListener('change', function(){
                    document.querySelectorAll('.reason').forEach(function(el){ el.classList.remove('sel'); });
                    if (r.checked) r.closest('.reason').classList.add('sel');
                });
            });
            document.getElementById('cf').addEventListener('submit', function(e){
                var chosen = document.querySelector('.reason input:checked');
                if (!chosen){
                    e.preventDefault();
                    document.getElementById('err').textContent = isAr ? 'الرجاء اختيار سبب.' : 'Please choose a reason.';
                }
            });
        })();
    </script>
</body>
</html>
