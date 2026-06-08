<!DOCTYPE html>


@php $ownerTheme = request()->cookie('owner_theme', 'dark'); @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-bk-theme="{{ $ownerTheme }}"
      class="bk-theme-{{ $ownerTheme }}">
<head>
	@include('owner.partials.css')
	@stack('owner-styles')
</head>
<body>
	<div class="main-wrapper">

		<!-- partial:partials/_sidebar.html -->
      @include('owner.partials.sidebar')
		<!-- partial -->
	
		<div class="page-wrapper">
					
			<!-- partial:partials/_navbar.html -->
      @include('owner.partials.navbar')
			<!-- partial -->

        @yield('content')

			<!-- partial:partials/_footer.html -->
      @include('owner.partials.footer')
			<!-- partial -->
		
		</div>
	</div>

	<!-- core:js -->
    @include('owner.partials.js')
	@stack('scripts')
	<!-- End custom js for this page -->

</body>
</html>    