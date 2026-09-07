@php($dir = $isAr ? 'rtl' : 'ltr')
@php($align = $isAr ? 'right' : 'left')
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>{{ $isAr ? 'رمز تأكيد الحساب' : 'Account verification code' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ec;font-family:'Segoe UI',Tahoma,Arial,Helvetica,sans-serif;color:#22251d;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2ec;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:520px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e7e1d3;box-shadow:0 8px 30px rgba(75,93,52,0.08);">

          {{-- ─── Header: logo on light ─── --}}
          <tr>
            <td align="center" style="background:#ffffff;padding:30px 24px 22px;">
              <img src="{{ asset('images/logo-light.png') }}" alt="GlowRez" width="170" style="display:block;max-width:170px;height:auto;margin:0 auto;">
            </td>
          </tr>

          {{-- olive → gold divider --}}
          <tr><td style="height:4px;background:#4B5D34;line-height:4px;font-size:0;">&nbsp;</td></tr>
          <tr><td style="height:3px;background:#C7A24B;line-height:3px;font-size:0;">&nbsp;</td></tr>

          {{-- ─── Body ─── --}}
          <tr>
            <td style="padding:34px 34px 8px;text-align:{{ $align }};">
              <p style="margin:0 0 6px;font-size:17px;font-weight:bold;color:#22251d;">
                {{ $isAr ? "مرحباً {$ownerName}،" : "Hello {$ownerName}," }}
              </p>
              <p style="margin:0;font-size:14px;line-height:1.7;color:#6c6d5e;">
                {{ $isAr
                    ? 'استخدم الرمز التالي لتأكيد حسابك في GlowRez:'
                    : 'Use the following code to verify your GlowRez account:' }}
              </p>
            </td>
          </tr>

          {{-- ─── Code box ─── --}}
          <tr>
            <td style="padding:22px 34px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#f7f5ef;border:1px solid #e0d9c8;border-radius:14px;padding:22px 12px;">
                    <div style="font-size:38px;font-weight:bold;letter-spacing:12px;color:#4B5D34;font-family:'Courier New',monospace;padding-{{ $isAr ? 'right' : 'left' }}:12px;">{{ $code }}</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- ─── Validity + security ─── --}}
          <tr>
            <td style="padding:0 34px 30px;text-align:{{ $align }};">
              <p style="margin:0 0 10px;font-size:14px;color:#4B5D34;font-weight:bold;">
                ⏱️ {{ $isAr ? "صالح لمدة {$minutes} دقائق" : "Valid for {$minutes} minutes" }}
              </p>
              <p style="margin:0;font-size:13px;line-height:1.7;color:#8a8b7c;">
                🔒 {{ $isAr
                    ? 'لا تُشارك هذا الرمز مع أي أحد. إذا لم تطلب هذا الرمز، تجاهل هذه الرسالة بأمان.'
                    : 'Never share this code with anyone. If you didn’t request it, you can safely ignore this email.' }}
              </p>
            </td>
          </tr>

          {{-- ─── Footer ─── --}}
          <tr>
            <td style="background:#f7f5ef;padding:18px 34px;text-align:center;border-top:1px solid #ece6d9;">
              <p style="margin:0 0 2px;font-size:13px;font-weight:bold;color:#4B5D34;">GlowRez</p>
              <p style="margin:0;font-size:11px;color:#a6a797;">
                {{ $isAr ? 'منصّة حجز خدمات الجمال والعافية' : 'Beauty & wellness booking platform' }}
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
