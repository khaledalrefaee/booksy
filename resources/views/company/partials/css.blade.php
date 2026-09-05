<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>GlowRez Business</title>
<link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
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
<link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.png') }}?v={{ @filemtime(public_path('backend/assets/images/favicon.png')) ?: '1' }}" />
<link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-custom.css') }}?v={{ @filemtime(public_path('backend/assets/css/booksy-custom.css')) ?: '1' }}">
@if(app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('backend/assets/css/booksy-arabic.css') }}">
@endif
