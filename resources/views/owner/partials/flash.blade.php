@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center" role="alert">
        <i data-feather="check-circle" class="text-success me-2 icon-md"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (session('warning'))
    <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center" role="alert">
        <i data-feather="alert-triangle" class="text-warning me-2 icon-md"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center" role="alert">
        <i data-feather="alert-circle" class="text-danger me-2 icon-md"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4" role="alert">
        <div class="fw-semibold mb-2">{{ __('Please fix the following:') }}</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
