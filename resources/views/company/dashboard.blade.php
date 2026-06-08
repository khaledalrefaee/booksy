<!DOCTYPE html>
@php $companyTheme = request()->cookie('company_theme', 'dark'); @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-bk-theme="{{ $companyTheme }}"
      class="bk-theme-{{ $companyTheme }}">
<head>
    @include('company.partials.css')
    @stack('company-styles')
</head>
<body>
    <div class="main-wrapper">
        @include('company.partials.sidebar')
        <div class="page-wrapper">
            @include('company.partials.navbar')
            @yield('content')
            @include('company.partials.footer')
        </div>
    </div>
    @include('company.partials.js')
    @stack('scripts')
</body>
</html>
