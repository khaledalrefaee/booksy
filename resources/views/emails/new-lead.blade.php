@php($dir = $isAr ? 'rtl' : 'ltr')
@php($align = $isAr ? 'right' : 'left')
@php($title = $repeat ? ($isAr ? 'عاد للتسجيل — ' : 'Returning lead — ') . $lead->business_name : ($isAr ? 'Lead جديد — ' : 'New lead — ') . $lead->business_name)
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ec;font-family:'Segoe UI',Tahoma,Arial,Helvetica,sans-serif;color:#22251d;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2ec;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:580px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e7e1d3;box-shadow:0 8px 30px rgba(75,93,52,0.08);">

          {{-- Header band (dark, so the white logo reads) --}}
          <tr>
            <td align="center" style="background:#242c1a;padding:28px 24px 24px;">
              <img src="{{ $message->embed(public_path('images/glowrez-logo-dark_1.webp')) }}" alt="GlowRez" width="170" style="display:block;max-width:170px;height:auto;margin:0 auto;">
            </td>
          </tr>
          <tr><td style="height:4px;background:#4B5D34;line-height:4px;font-size:0;">&nbsp;</td></tr>
          <tr><td style="height:3px;background:#C7A24B;line-height:3px;font-size:0;">&nbsp;</td></tr>

          {{-- Headline --}}
          <tr>
            <td style="padding:30px 34px 4px;text-align:{{ $align }};">
              <p style="margin:0 0 6px;font-size:13px;font-weight:bold;color:#8a6a1f;">
                {{ $repeat ? ($isAr ? 'تسجيل متكرر من عميل محتمل مسجّل مسبقاً' : 'A known lead registered again') : ($isAr ? 'Lead جديد' : 'New lead') }}
              </p>
              <h1 style="margin:0;font-size:22px;font-weight:bold;color:#22251d;line-height:1.35;">{{ $lead->business_name }}</h1>
              <p style="margin:6px 0 0;font-size:14px;color:#5a5c4e;line-height:1.6;">
                {{ \App\Support\LeadCatalog::label('business_types', $lead->business_type, $locale) }}
                &nbsp;·&nbsp; {{ \App\Support\LeadCatalog::label('cities', $lead->city, $locale) }}
                @if($lead->area)&nbsp;·&nbsp; {{ $lead->area }}@endif
              </p>
              @if($repeat)
                <p style="margin:12px 0 0;font-size:13px;line-height:1.7;color:#5a5c4e;">
                  {{ $isAr
                      ? 'هذه المرة رقم '.$lead->interaction_count.' التي يسجّل فيها. تم تحديث البيانات الموجودة ولم يُنشأ سجل مكرر.'
                      : 'This is interaction #'.$lead->interaction_count.'. The existing lead was updated — no duplicate was created.' }}
                </p>
              @endif
            </td>
          </tr>

          {{-- Details --}}
          <tr>
            <td style="padding:18px 34px 6px;text-align:{{ $align }};">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #ece6d9;">
                @foreach($rows as $label => $value)
                  <tr>
                    <td style="padding:11px 0;border-bottom:1px solid #f0ebdf;width:36%;font-size:13px;color:#8b8c7a;vertical-align:top;text-align:{{ $align }};">{{ $label }}</td>
                    <td style="padding:11px 0;border-bottom:1px solid #f0ebdf;font-size:15px;font-weight:600;color:#22251d;line-height:1.55;text-align:{{ $align }};">
                      <span dir="auto" style="unicode-bidi:plaintext;">{{ $value }}</span>
                    </td>
                  </tr>
                @endforeach
              </table>
            </td>
          </tr>

          {{-- CTA --}}
          <tr>
            <td align="center" style="padding:26px 34px 10px;">
              <a href="{{ $openUrl }}" style="display:inline-block;background:#C7A24B;color:#1c2213;font-weight:bold;font-size:15px;text-decoration:none;padding:14px 30px;border-radius:12px;">
                {{ $isAr ? 'فتح الـ Lead في لوحة التحكم' : 'Open this lead in the dashboard' }}
              </a>
            </td>
          </tr>
          @if($wa = $lead->whatsappUrl())
            <tr>
              <td align="center" style="padding:6px 34px 28px;">
                <a href="{{ $wa }}" style="font-size:13px;color:#4B5D34;font-weight:bold;text-decoration:underline;">
                  {{ $isAr ? 'أو تواصل مباشرة عبر واتساب' : 'or reach out directly on WhatsApp' }}
                </a>
              </td>
            </tr>
          @else
            <tr><td style="height:22px;font-size:0;line-height:0;">&nbsp;</td></tr>
          @endif

          {{-- Footer --}}
          <tr>
            <td style="background:#f7f5ef;padding:18px 34px;text-align:center;border-top:1px solid #ece6d9;">
              <p style="margin:0;font-size:12px;color:#a6a797;">
                <span style="font-weight:bold;color:#4B5D34;">GlowRez</span>
                &nbsp;·&nbsp; {{ $isAr ? 'إشعار تلقائي من نموذج الاهتمام' : 'Automatic notification from the interest form' }}
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
