@php
    $dir   = $isAr ? 'rtl' : 'ltr';
    $align = $isAr ? 'right' : 'left';
    $olive = '#4B5D34'; $oliveDark = '#242c1a'; $gold = '#C7A24B';
    $ink = '#22251d'; $muted = '#8a8c7c'; $paper = '#f4f2ec'; $line = '#e7e1d3';
    $title = $isAr ? 'يوم عطلة سعيد' : 'Enjoy your day off';
    if ($holidayName) {
        $lead = $isAr
            ? "بمناسبة «{$holidayName}»، يسعد فريق GlowRez أن يتمنّى لك ولفريقك عطلة هانئة ومليئة بالراحة."
            : "On the occasion of “{$holidayName},” everyone at GlowRez wishes you and your team a restful, happy holiday.";
    } else {
        $lead = $isAr
            ? 'مركزك مغلق اليوم — نتمنّى لك ولفريقك يوم راحة هانئاً. سيعود ملخّص أعمالك اليومي في يوم العمل القادم.'
            : 'Your business is closed today — we wish you and your team a relaxing day off. Your daily summary returns on the next working day.';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};font-family:'Segoe UI',Tahoma,Arial,Helvetica,sans-serif;color:{{ $ink }};">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};padding:32px 16px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid {{ $line }};box-shadow:0 8px 30px rgba(75,93,52,0.08);">

        {{-- Header --}}
        <tr>
          <td align="center" style="background:{{ $oliveDark }};padding:30px 24px 26px;">
            <img src="{{ $message->embed(public_path('images/glowrez-logo-light_1.webp')) }}" alt="GlowRez" width="160" style="display:block;max-width:160px;height:auto;margin:0 auto;">
          </td>
        </tr>
        <tr><td style="height:4px;background:{{ $olive }};line-height:4px;font-size:0;">&nbsp;</td></tr>
        <tr><td style="height:3px;background:{{ $gold }};line-height:3px;font-size:0;">&nbsp;</td></tr>

        {{-- Greeting --}}
        <tr>
          <td style="padding:34px 34px 8px;text-align:center;">
            <div style="font-size:38px;line-height:1;">🌿</div>
            <h1 style="margin:16px 0 0;font-size:23px;font-weight:bold;color:{{ $ink }};">{{ $title }}</h1>
            <p style="margin:6px 0 0;font-size:14px;color:{{ $gold }};font-weight:bold;">{{ $company->localizedName() }}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:14px 38px 34px;text-align:{{ $align }};">
            <p style="margin:0;font-size:15px;line-height:1.9;color:#3a3c31;">{{ $lead }}</p>
          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background:#f7f5ef;padding:18px 34px;text-align:center;border-top:1px solid #ece6d9;">
            <p style="margin:0;font-size:12px;color:#a6a797;">
              <span style="font-weight:bold;color:{{ $olive }};">GlowRez</span> &nbsp;·&nbsp; © {{ date('Y') }}
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
