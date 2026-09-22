@php
    $isAr   = $r['is_ar'] ?? true;
    $dir    = $isAr ? 'rtl' : 'ltr';
    $align  = $isAr ? 'right' : 'left';
    $company = $r['company'];
    $s       = $r['summary'];
    $cur     = $r['currency'];
    $fmt     = fn ($n) => number_format((float) $n, 0);
    $money   = fn ($n) => number_format((float) $n, 2) . ' ' . $cur;
    $dateLbl = $r['date']->locale($isAr ? 'ar' : 'en')->isoFormat('dddd، D MMMM YYYY');
    // Brand palette
    $olive = '#4B5D34'; $oliveDark = '#242c1a'; $gold = '#C7A24B';
    $ink = '#22251d'; $muted = '#8a8c7c'; $paper = '#f4f2ec'; $line = '#e7e1d3';
@endphp
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>{{ $isAr ? 'ملخص الأعمال اليومي' : 'Daily Business Summary' }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};font-family:'Segoe UI',Tahoma,Arial,Helvetica,sans-serif;color:{{ $ink }};">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};padding:28px 14px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid {{ $line }};box-shadow:0 8px 30px rgba(75,93,52,0.08);">

        {{-- ── Header ── --}}
        <tr>
          <td align="center" style="background:{{ $oliveDark }};padding:28px 24px 22px;">
            <img src="{{ $message->embed(public_path('images/glowrez-logo-light_1.webp')) }}" alt="GlowRez" width="150" style="display:block;max-width:150px;height:auto;margin:0 auto 14px;">
            <div style="color:#e9e6d8;font-size:13px;letter-spacing:.5px;text-transform:uppercase;">{{ $isAr ? 'ملخص الأعمال اليومي' : 'Daily Business Summary' }}</div>
          </td>
        </tr>
        <tr><td style="height:4px;background:{{ $olive }};line-height:4px;font-size:0;">&nbsp;</td></tr>
        <tr><td style="height:3px;background:{{ $gold }};line-height:3px;font-size:0;">&nbsp;</td></tr>

        {{-- ── Company + date ── --}}
        <tr>
          <td style="padding:26px 30px 6px;text-align:{{ $align }};">
            <h1 style="margin:0;font-size:21px;font-weight:bold;color:{{ $ink }};line-height:1.35;">{{ $company->localizedName() }}</h1>
            <p style="margin:6px 0 0;font-size:13px;color:{{ $muted }};">{{ $dateLbl }} · {{ $isAr ? 'حتى الساعة' : 'as of' }} {{ $r['generated_at']->format('H:i') }}</p>
          </td>
        </tr>

        {{-- ── KPI cards ── --}}
        <tr>
          <td style="padding:16px 22px 6px;">
            @php
              $cards = [
                ['label' => $isAr ? 'الحجوزات الفعلية' : 'Bookings',   'value' => $fmt($s['total']),         'accent' => $olive],
                ['label' => $isAr ? 'المكتملة' : 'Completed',           'value' => $fmt($s['completed']),     'accent' => '#2f7d4f'],
                ['label' => $isAr ? 'الملغاة' : 'Cancelled',            'value' => $fmt($s['cancelled']),     'accent' => '#c0492b'],
                ['label' => $isAr ? 'عملاء جدد' : 'New customers',      'value' => $fmt($s['new_customers']), 'accent' => $gold],
              ];
            @endphp
            @foreach ($cards as $c)
              <div style="display:inline-block;vertical-align:top;width:46%;min-width:130px;margin:0 1% 12px;padding:16px 14px;background:#faf9f4;border:1px solid {{ $line }};border-radius:14px;text-align:{{ $align }};box-sizing:border-box;">
                <div style="font-size:28px;font-weight:bold;color:{{ $c['accent'] }};line-height:1;">{{ $c['value'] }}</div>
                <div style="font-size:12px;color:{{ $muted }};margin-top:7px;">{{ $c['label'] }}</div>
              </div>
            @endforeach
          </td>
        </tr>

        {{-- ── Revenue banner ── --}}
        <tr>
          <td style="padding:0 22px 8px;">
            <div style="margin:0 1%;padding:18px 20px;background:{{ $oliveDark }};border-radius:14px;text-align:{{ $align }};">
              <span style="display:block;font-size:12px;color:#c9cbba;letter-spacing:.4px;">{{ $isAr ? 'الإيرادات المحصّلة اليوم' : 'Revenue collected today' }}</span>
              <span style="display:block;font-size:26px;font-weight:bold;color:{{ $gold }};margin-top:5px;">{{ $money($s['revenue']) }}</span>
            </div>
          </td>
        </tr>

        {{-- ── 7-day chart ── --}}
        @php $chart = $r['chart']; $max = max(1, $chart['max']); @endphp
        <tr>
          <td style="padding:22px 30px 6px;text-align:{{ $align }};">
            <h2 style="margin:0 0 4px;font-size:15px;color:{{ $ink }};">{{ $isAr ? 'الحجوزات خلال آخر 7 أيام' : 'Bookings — last 7 days' }}</h2>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 24px 18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
              <tr>
                @foreach ($chart['days'] as $d)
                  @php $h = $d['count'] > 0 ? max(6, (int) round($d['count'] / $max * 88)) : 2; @endphp
                  <td valign="bottom" align="center" style="height:104px;padding:0 2px;vertical-align:bottom;">
                    <div style="font-size:11px;font-weight:bold;color:{{ $d['is_today'] ? $gold : $muted }};margin-bottom:4px;">{{ $d['count'] }}</div>
                    <div style="height:{{ $h }}px;background:{{ $d['is_today'] ? $gold : '#cdd3bd' }};border-radius:5px 5px 0 0;"></div>
                  </td>
                @endforeach
              </tr>
              <tr>
                @foreach ($chart['days'] as $d)
                  <td align="center" style="padding-top:6px;font-size:10px;color:{{ $d['is_today'] ? $ink : $muted }};font-weight:{{ $d['is_today'] ? 'bold' : 'normal' }};">{{ $d['label'] }}</td>
                @endforeach
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Comparison ── --}}
        @if ($r['comparison'])
          @php
            $cmp = $r['comparison'];
            $up  = $cmp['direction'] === 'up';
            $flat = $cmp['direction'] === 'flat';
            $cmpColor = $flat ? $muted : ($up ? '#2f7d4f' : '#c0492b');
            $arrow = $flat ? '=' : ($up ? '▲' : '▼');
            $pct = abs($cmp['diff_pct']);
            $word = $up ? ($isAr ? 'أعلى' : 'higher') : ($isAr ? 'أقل' : 'lower');
          @endphp
          <tr>
            <td style="padding:2px 30px 14px;text-align:{{ $align }};">
              <div style="padding:12px 16px;background:#faf9f4;border:1px solid {{ $line }};border-radius:12px;font-size:13px;color:{{ $ink }};line-height:1.6;">
                <span style="color:{{ $cmpColor }};font-weight:bold;">{{ $arrow }}</span>
                @if ($flat)
                  {{ $isAr ? 'الحجوزات اليوم مطابقة لمتوسط آخر 7 أيام.' : 'Bookings today match the last 7-day average.' }}
                @else
                  {{ $isAr
                      ? "الحجوزات اليوم $word بنسبة $pct% من متوسط آخر 7 أيام."
                      : "Bookings today are $pct% $word than the last 7-day average." }}
                @endif
              </div>
            </td>
          </tr>
        @endif

        {{-- ── Branch performance ── --}}
        @if ($r['has_multiple_branches'])
          <tr>
            <td style="padding:12px 30px 4px;text-align:{{ $align }};">
              <h2 style="margin:0 0 8px;font-size:15px;color:{{ $ink }};">{{ $isAr ? 'أداء الفروع' : 'Branch performance' }}</h2>
            </td>
          </tr>
          <tr>
            <td style="padding:0 24px 16px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
                <tr style="background:{{ $olive }};color:#fff;">
                  <th style="padding:9px 10px;text-align:{{ $align }};font-weight:600;">{{ $isAr ? 'الفرع' : 'Branch' }}</th>
                  <th style="padding:9px 6px;text-align:center;font-weight:600;">{{ $isAr ? 'الحجوزات' : 'Bookings' }}</th>
                  <th style="padding:9px 6px;text-align:center;font-weight:600;">{{ $isAr ? 'مكتملة' : 'Done' }}</th>
                  <th style="padding:9px 6px;text-align:center;font-weight:600;">{{ $isAr ? 'ملغاة' : 'Cxl' }}</th>
                  <th style="padding:9px 10px;text-align:{{ $isAr ? 'left' : 'right' }};font-weight:600;">{{ $isAr ? 'الإيرادات' : 'Revenue' }}</th>
                </tr>
                <tr style="background:#f2efe6;font-weight:bold;">
                  <td style="padding:9px 10px;text-align:{{ $align }};">{{ $isAr ? 'إجمالي الشركة' : 'Company total' }}</td>
                  <td style="padding:9px 6px;text-align:center;">{{ $fmt($s['total']) }}</td>
                  <td style="padding:9px 6px;text-align:center;">{{ $fmt($s['completed']) }}</td>
                  <td style="padding:9px 6px;text-align:center;">{{ $fmt($s['cancelled']) }}</td>
                  <td style="padding:9px 10px;text-align:{{ $isAr ? 'left' : 'right' }};">{{ $money($s['revenue']) }}</td>
                </tr>
                @foreach ($r['branches'] as $b)
                  <tr style="border-bottom:1px solid {{ $line }};">
                    <td style="padding:9px 10px;text-align:{{ $align }};color:{{ $ink }};">{{ $b['name'] }}</td>
                    <td style="padding:9px 6px;text-align:center;color:{{ $muted }};">{{ $fmt($b['total']) }}</td>
                    <td style="padding:9px 6px;text-align:center;color:{{ $muted }};">{{ $fmt($b['completed']) }}</td>
                    <td style="padding:9px 6px;text-align:center;color:{{ $muted }};">{{ $fmt($b['cancelled']) }}</td>
                    <td style="padding:9px 10px;text-align:{{ $isAr ? 'left' : 'right' }};color:{{ $ink }};">{{ $money($b['revenue']) }}</td>
                  </tr>
                @endforeach
              </table>
            </td>
          </tr>
        @endif

        {{-- ── Top services ── --}}
        @if (count($r['top_services']))
          <tr>
            <td style="padding:12px 30px 4px;text-align:{{ $align }};">
              <h2 style="margin:0 0 8px;font-size:15px;color:{{ $ink }};">{{ $isAr ? 'أكثر الخدمات حجزاً اليوم' : 'Top services today' }}</h2>
            </td>
          </tr>
          <tr>
            <td style="padding:0 30px 16px;">
              @foreach ($r['top_services'] as $i => $svc)
                <div style="padding:11px 0;border-bottom:{{ $loop->last ? 'none' : '1px solid '.$line }};">
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td style="text-align:{{ $align }};font-size:14px;color:{{ $ink }};">
                      <span style="display:inline-block;width:22px;height:22px;line-height:22px;text-align:center;background:{{ $olive }};color:#fff;border-radius:50%;font-size:12px;font-weight:bold;{{ $isAr ? 'margin-left' : 'margin-right' }}:9px;">{{ $i + 1 }}</span>
                      {{ $svc['name'] }}
                    </td>
                    <td style="text-align:{{ $isAr ? 'left' : 'right' }};font-size:13px;color:{{ $muted }};white-space:nowrap;">{{ $svc['count'] }} {{ $isAr ? 'حجز' : ($svc['count'] == 1 ? 'booking' : 'bookings') }}</td>
                  </tr></table>
                </div>
              @endforeach
            </td>
          </tr>
        @endif

        {{-- ── Upcoming ── --}}
        <tr>
          <td style="padding:8px 22px 20px;">
            <div style="margin:0 1%;padding:16px 18px;background:#faf9f4;border:1px solid {{ $line }};border-radius:14px;text-align:{{ $align }};">
              <span style="font-size:13px;color:{{ $muted }};">{{ $isAr ? 'الحجوزات القادمة' : 'Upcoming bookings' }}</span>
              <span style="display:block;font-size:22px;font-weight:bold;color:{{ $olive }};margin-top:3px;">{{ $fmt($r['upcoming']['count']) }}</span>
              @if ($r['upcoming']['next_at'])
                <span style="display:block;font-size:12px;color:{{ $muted }};margin-top:8px;">
                  {{ $isAr ? 'أقرب موعد:' : 'Next:' }}
                  <strong style="color:{{ $ink }};">{{ $r['upcoming']['next_at']->locale($isAr ? 'ar' : 'en')->isoFormat('D MMM · H:mm') }}</strong>
                  @if ($r['upcoming']['next_customer']) — {{ $r['upcoming']['next_customer'] }} @endif
                  @if ($r['has_multiple_branches'] && $r['upcoming']['next_branch']) ({{ $r['upcoming']['next_branch'] }}) @endif
                </span>
              @endif
            </div>
          </td>
        </tr>

        {{-- ── Footer ── --}}
        <tr>
          <td style="background:#f7f5ef;padding:18px 34px;text-align:center;border-top:1px solid #ece6d9;">
            <p style="margin:0;font-size:12px;color:#a6a797;">
              <span style="font-weight:bold;color:{{ $olive }};">GlowRez</span> &nbsp;·&nbsp; © {{ date('Y') }}
            </p>
            <p style="margin:6px 0 0;font-size:11px;color:#b8b9a8;">{{ $isAr ? 'تقرير تلقائي يومي عن أداء نشاطك.' : 'Automated daily performance report.' }}</p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
