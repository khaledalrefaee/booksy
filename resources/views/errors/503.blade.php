@php
    // Set by App\Http\Middleware\PlatformMaintenance (Owner → Settings → System).
    // Absent for a plain `artisan down`, which keeps the stock maintenance copy.
    $m      = $maintenance ?? null;
    $isArM  = app()->getLocale() === 'ar';
    $extra  = [];
    if ($m) {
        $paused = $m['mode'] === 'paused';
        $extra  = [
            'title' => $paused ? ($isArM ? 'الخدمة متوقفة مؤقتًا' : 'Service temporarily paused') : null,
            'desc'  => $m['message'] ?: ($paused
                ? ($isArM ? 'أوقفنا الخدمة مؤقتًا. سنعود قريبًا — شكرًا لصبرك.' : 'We have paused the service for a while. We will be back soon — thank you for your patience.')
                : null),
            'icon'  => $paused ? 'clock' : 'tools',
        ];
        if (! empty($m['status_url'])) {
            $extra['extraUrl']   = $m['status_url'];
            $extra['extraLabel'] = $isArM ? 'تابع حالة الخدمة' : 'Check service status';
        }
    }
@endphp
@include('errors.partials.shell', array_filter(
    array_merge(['code' => 503, 'icon' => 'tools', 'showRetry' => true], $extra),
    fn ($v) => $v !== null
))
