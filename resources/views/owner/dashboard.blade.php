<!DOCTYPE html>


@php
    $ownerTheme = request()->cookie('owner_theme', 'dark');

    // Shared nav badge counts (computed once per request; sidebar falls back if unset)
    try { $bkOwnerPendingAppts = (int) \App\Models\Appointment::where('status', 'pending')->count(); }
    catch (\Throwable $e) { $bkOwnerPendingAppts = 0; }
    try { $bkOwnerPendingCompanies = (int) \App\Models\Company::where('status', 'pending')->count(); }
    catch (\Throwable $e) { $bkOwnerPendingCompanies = 0; }
    try { $bkOwnerPendingPhotos = \Illuminate\Support\Facades\Gate::allows('owner-can', 'photos.review')
            ? (int) \App\Models\BranchImage::where('status', 'pending')->count() : 0; }
    catch (\Throwable $e) { $bkOwnerPendingPhotos = 0; }
    try { $bkOwnerNewLeads = \Illuminate\Support\Facades\Gate::allows('owner-can', 'leads.view')
            ? (int) \App\Models\Lead::where('status', 'new')->count() : 0; }
    catch (\Throwable $e) { $bkOwnerNewLeads = 0; }
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-bk-theme="{{ $ownerTheme }}"
      class="bk-theme-{{ $ownerTheme }}">
<head>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	@include('partials.clarity')
	@include('owner.partials.css')
	@stack('owner-styles')
	{{-- Shared partials (e.g. social-links-form) push their CSS here; render it so
	     those components look identical on the owner dashboard. --}}
	@stack('company-styles')
</head>
{{-- data-clarity-mask: owner console shows companies' customer names, phones,
     emails, salaries & financial data — mask all rendered text in Clarity
     recordings. Heatmaps & click analytics still work; UI is unaffected. --}}
<body data-clarity-mask="true">
	{{-- Restore the folded sidebar before first paint so the layout never jumps (desktop only) --}}
	<script>try{if(localStorage.getItem('bkOwnerFold')==='1'&&matchMedia('(min-width:992px)').matches)document.body.classList.add('sidebar-folded')}catch(e){}</script>
	<div class="main-wrapper">

		<!-- partial:partials/_sidebar.html -->
      @include('owner.partials.sidebar')
		<!-- partial -->
	
		<div class="page-wrapper">
					
			<!-- partial:partials/_navbar.html -->
      @include('owner.partials.navbar')
			<!-- partial -->

        <span id="bkMain" tabindex="-1"></span>
        @yield('content')

			<!-- partial:partials/_footer.html -->
      @include('owner.partials.footer')
			<!-- partial -->

		</div>

		{{-- Phone tab bar --}}
		@include('owner.partials.bottom-nav')
	</div>

	<!-- core:js -->
    @include('owner.partials.js')

	{{-- Unified GlowRez toast engine + SweetAlert2 (powers bkConfirm / data-confirm) --}}
	<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
	<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
	@include('partials.glow-toast')

	@stack('scripts')
	@include('partials.keepalive', ['logoutUrl' => route('owner.logout')])
	<!-- End custom js for this page -->

</body>
</html>    