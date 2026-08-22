@php $isAr = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $isAr ? 'غير متصل' : 'Offline' }} — GlowRez</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:24px;
    font-family:'Poppins','Tajawal',system-ui,-apple-system,'Segoe UI',sans-serif;
    background:linear-gradient(160deg,#37471F,#4B5D34 60%,#5C7038);color:#F0EEE3;text-align:center}
  .wrap{max-width:400px}
  .disc{width:88px;height:88px;margin:0 auto 28px;border-radius:50%;
    background:linear-gradient(135deg,#DCC07E,#C7A15A);display:grid;place-items:center;
    box-shadow:0 18px 44px rgba(0,0,0,.35)}
  .disc svg{width:44px;height:44px;stroke:#37471F;stroke-width:2.4;fill:none;stroke-linecap:round;stroke-linejoin:round}
  h1{font-size:1.5rem;margin-bottom:10px;font-weight:800}
  p{opacity:.82;line-height:1.7;font-size:.98rem;margin-bottom:28px}
  button{appearance:none;border:none;cursor:pointer;font:inherit;font-weight:700;
    background:linear-gradient(135deg,#DCC07E,#C7A15A);color:#2A2310;
    padding:14px 28px;border-radius:999px;min-height:52px;font-size:1rem;
    box-shadow:0 10px 24px rgba(0,0,0,.25);transition:transform .2s,filter .2s}
  button:hover{filter:brightness(1.05);transform:translateY(-2px)}
  button:active{transform:translateY(0)}
</style>
</head>
<body>
  <div class="wrap">
    <div class="disc">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 1l22 22M16.7 16.7A11 11 0 0 1 5 12M8.5 8.5A6 6 0 0 1 12 7a6 6 0 0 1 5 3M12 20h.01"/></svg>
    </div>
    <h1>{{ $isAr ? 'لا يوجد اتصال بالإنترنت' : 'You’re offline' }}</h1>
    <p>{{ $isAr ? 'تحقّق من اتصالك ثم حاول مرة أخرى. الصفحات التي زرتها سابقاً تبقى متاحة.' : 'Check your connection and try again. Pages you’ve already visited stay available.' }}</p>
    <button onclick="location.reload()">{{ $isAr ? 'إعادة المحاولة' : 'Try again' }}</button>
  </div>
</body>
</html>
