@php
    $isAr = $r['is_ar'] ?? true;
    $s = $r['summary'];
    $cur = $r['currency'];
    $money = fn ($n) => number_format((float) $n, 2) . ' ' . $cur;
    $dateLbl = $r['date']->format('Y-m-d');
@endphp
{{ $isAr ? 'ملخص الأعمال اليومي' : 'Daily Business Summary' }} — {{ $r['company']->localizedName() }} ({{ $dateLbl }})

{{ $isAr ? 'الحجوزات الفعلية' : 'Bookings' }}: {{ $s['total'] }}
{{ $isAr ? 'المكتملة' : 'Completed' }}: {{ $s['completed'] }}
{{ $isAr ? 'الملغاة' : 'Cancelled' }}: {{ $s['cancelled'] }}
{{ $isAr ? 'عملاء جدد' : 'New customers' }}: {{ $s['new_customers'] }}
{{ $isAr ? 'الإيرادات المحصّلة' : 'Revenue' }}: {{ $money($s['revenue']) }}
@if ($r['comparison'])

{{ $isAr ? 'مقارنة' : 'Comparison' }}: {{ $r['comparison']['diff_pct'] }}% {{ $isAr ? 'مقابل متوسط آخر 7 أيام' : 'vs last 7-day average' }}
@endif
@if ($r['has_multiple_branches'])

{{ $isAr ? 'أداء الفروع' : 'Branches' }}:
@foreach ($r['branches'] as $b)
- {{ $b['name'] }}: {{ $b['total'] }} / {{ $b['completed'] }} {{ $isAr ? 'مكتملة' : 'done' }} / {{ $b['cancelled'] }} {{ $isAr ? 'ملغاة' : 'cxl' }} — {{ $money($b['revenue']) }}
@endforeach
@endif
@if (count($r['top_services']))

{{ $isAr ? 'أكثر الخدمات حجزاً' : 'Top services' }}:
@foreach ($r['top_services'] as $i => $svc)
{{ $i + 1 }}. {{ $svc['name'] }} — {{ $svc['count'] }}
@endforeach
@endif

{{ $isAr ? 'الحجوزات القادمة' : 'Upcoming' }}: {{ $r['upcoming']['count'] }}@if ($r['upcoming']['next_at']) ({{ $isAr ? 'أقرب موعد' : 'next' }}: {{ $r['upcoming']['next_at']->format('Y-m-d H:i') }})@endif


GlowRez · © {{ date('Y') }}
