@php($dir = $isAr ? 'rtl' : 'ltr')
@php($align = $isAr ? 'right' : 'left')
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ec;font-family:'Segoe UI',Tahoma,Arial,Helvetica,sans-serif;color:#22251d;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2ec;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e7e1d3;box-shadow:0 8px 30px rgba(75,93,52,0.08);">

          {{-- ─── Header: dark band so the white "dark-mode" logo stays visible.
               Embedded via CID (inline attachment) so it renders regardless of
               APP_URL. Note: webp isn't shown by every client (e.g. Outlook). ─── --}}
          <tr>
            <td align="center" style="background:#242c1a;padding:30px 24px 26px;">
              <img src="{{ $message->embed(public_path('images/glowrez-logo-light_1.webp')) }}" alt="GlowRez" width="180" style="display:block;max-width:180px;height:auto;margin:0 auto;">
            </td>
          </tr>

          {{-- olive → gold divider --}}
          <tr><td style="height:4px;background:#4B5D34;line-height:4px;font-size:0;">&nbsp;</td></tr>
          <tr><td style="height:3px;background:#C7A24B;line-height:3px;font-size:0;">&nbsp;</td></tr>

          {{-- ─── Subject heading ─── --}}
          <tr>
            <td style="padding:30px 34px 6px;text-align:{{ $align }};">
              <h1 style="margin:0;font-size:20px;font-weight:bold;color:#22251d;line-height:1.4;">{{ $subjectLine }}</h1>
            </td>
          </tr>

          {{-- ─── Body: owner's free text, line breaks preserved ─── --}}
          <tr>
            <td style="padding:14px 34px 30px;text-align:{{ $align }};">
              <div style="font-size:15px;line-height:1.85;color:#3a3c31;">{!! nl2br(e($bodyText)) !!}</div>
            </td>
          </tr>

          {{-- ─── Footer ─── --}}
          <tr>
            <td style="background:#f7f5ef;padding:18px 34px;text-align:center;border-top:1px solid #ece6d9;">
              <p style="margin:0;font-size:12px;color:#a6a797;">
                <span style="font-weight:bold;color:#4B5D34;">GlowRez</span>
                &nbsp;·&nbsp; © {{ date('Y') }}
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
