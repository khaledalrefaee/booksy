{{ $repeat ? ($isAr ? 'عاد للتسجيل' : 'Returning lead') : ($isAr ? 'Lead جديد' : 'New lead') }} — {{ $lead->business_name }}

@foreach($rows as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ $isAr ? 'فتح الـ Lead في لوحة التحكم' : 'Open this lead in the dashboard' }}:
{{ $openUrl }}
@if($wa = $lead->whatsappUrl())

WhatsApp: {{ $wa }}
@endif
