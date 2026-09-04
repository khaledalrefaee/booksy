<!DOCTYPE html>


@php
    $ownerTheme = request()->cookie('owner_theme', 'dark');

    // Shared nav badge counts (computed once per request; sidebar falls back if unset)
    try { $bkOwnerPendingAppts = (int) \App\Models\Appointment::where('status', 'pending')->count(); }
    catch (\Throwable $e) { $bkOwnerPendingAppts = 0; }
    try { $bkOwnerPendingCompanies = (int) \App\Models\Company::where('status', 'pending')->count(); }
    catch (\Throwable $e) { $bkOwnerPendingCompanies = 0; }
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-bk-theme="{{ $ownerTheme }}"
      class="bk-theme-{{ $ownerTheme }}">
<head>
	@include('owner.partials.css')
	@stack('owner-styles')
	{{-- Shared partials (e.g. social-links-form) push their CSS here; render it so
	     those components look identical on the owner dashboard. --}}
	@stack('company-styles')
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