@php
    $title = $isAr ? 'يوم عطلة سعيد' : 'Enjoy your day off';
    if ($holidayName) {
        $lead = $isAr
            ? "بمناسبة «{$holidayName}»، نتمنّى لك ولفريقك عطلة هانئة."
            : "On the occasion of “{$holidayName},” we wish you and your team a happy holiday.";
    } else {
        $lead = $isAr
            ? 'مركزك مغلق اليوم — نتمنّى لك يوم راحة هانئاً. سيعود ملخّص أعمالك في يوم العمل القادم.'
            : 'Your business is closed today — enjoy a relaxing day off. Your daily summary returns on the next working day.';
    }
@endphp
{{ $title }} — {{ $company->localizedName() }}

{{ $lead }}

GlowRez · © {{ date('Y') }}
