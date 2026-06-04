<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords" content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<title> Admin Dashboard Booksy</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <!-- End fonts -->

	<!-- core:css -->
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/core/core.css')}}">
	<!-- endinject -->

	<!-- Plugin css for this page -->
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/flatpickr/flatpickr.min.css')}}">
	<!-- End plugin css for this page -->

	<!-- inject:css -->
	<link rel="stylesheet" href="{{asset('backend/assets/fonts/feather-font/css/iconfont.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/flag-icon-css/css/flag-icon.min.css')}}">
	<!-- endinject -->

  <!-- Layout styles: load one stylesheet only — stacking LTR + RTL leaves conflicting rules (e.g. .settings-sidebar left/right). -->
	@php($ownerLayoutTheme = $ownerTheme ?? 'dark')
	@if(app()->getLocale() === 'ar')
		@if($ownerLayoutTheme === 'light')
		<link rel="stylesheet" href="{{ asset('backend/assets/css/demo1/style-rtl.css') }}">
		@else
		<link rel="stylesheet" href="{{ asset('backend/assets/css/demo2/style-rtl.css') }}">
		@endif
	@else
		@if($ownerLayoutTheme === 'light')
		<link rel="stylesheet" href="{{ asset('backend/assets/css/demo1/style.css') }}">
		@else
		<link rel="stylesheet" href="{{ asset('backend/assets/css/demo2/style.css') }}">
		@endif
	@endif
  <!-- End layout styles -->

  <link rel="shortcut icon" href="{{asset('backend/assets/images/favicon.png')}}" />


  	<!-- Plugin css for this page form -->
	{{-- <link rel="stylesheet" href="{{asset('backend/assets/vendors/select2/select2.min.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/jquery-tags-input/jquery.tagsinput.min.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/dropzone/dropzone.min.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/dropify/dist/dropify.min.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/pickr/themes/classic.min.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/font-awesome/css/font-awesome.min.css')}}">
	<link rel="stylesheet" href="{{asset('backend/assets/vendors/flatpickr/flatpickr.min.css')}}"> --}}
	<!-- End plugin css for this page form -->