<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Booksy Business</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('backend/assets/vendors/core/core.css') }}">
<link rel="stylesheet" href="{{ asset('backend/assets/vendors/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('backend/assets/fonts/feather-font/css/iconfont.css') }}">
<link rel="stylesheet" href="{{ asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
@php($companyLayoutTheme = $companyTheme ?? 'dark')
@if(app()->getLocale() === 'ar')
    @if($companyLayoutTheme === 'light')
        <link rel="stylesheet" href="{{ asset('backend/assets/css/demo1/style-rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('backend/assets/css/demo2/style-rtl.css') }}">
    @endif
@else
    @if($companyLayoutTheme === 'light')
        <link rel="stylesheet" href="{{ asset('backend/assets/css/demo1/style.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('backend/assets/css/demo2/style.css') }}">
    @endif
@endif
<link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}" />
<link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-custom.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
