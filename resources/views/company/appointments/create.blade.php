@extends('company.dashboard')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">{{ __('New appointment') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('company.appointments.index') }}">{{ __('Appointments') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('New') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('company.appointments.index') }}" class="btn btn-outline-secondary rounded-pill">
            <i data-feather="arrow-left" style="width:14px;"></i> {{ __('Back') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="row">
        <div class="col-md-9 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('company.appointments.store') }}" id="appt-form">
                        @csrf

                        {{-- Branch --}}
                        <div class="mb-3">
                            <label for="branch_id" class="form-label fw-semibold">{{ __('Branch') }} <span class="text-danger">*</span></label>
                            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">{{ __('Select branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $selectedBranchId) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Service --}}
                        <div class="mb-3">
                            <label for="service_id" class="form-label fw-semibold">{{ __('Service') }} <span class="text-danger">*</span></label>
                            <select id="service_id" name="service_id" class="form-select @error('service_id') is-invalid @enderror" disabled>
                                <option value="">{{ __('Select branch first') }}</option>
                            </select>
                            @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Employee --}}
                        <div class="mb-3">
                            <label for="employee_id" class="form-label fw-semibold">{{ __('Staff') }}</label>
                            <select id="employee_id" name="employee_id" class="form-select" disabled>
                                <option value="">{{ __('Any / no preference') }}</option>
                            </select>
                        </div>

                        {{-- Date & Time --}}
                        <div class="mb-3">
                            <label for="start_time" class="form-label fw-semibold">{{ __('Date & time') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="start_time" name="start_time"
                                class="form-control @error('start_time') is-invalid @enderror"
                                value="{{ old('start_time', now()->addHour()->format('Y-m-d\TH:i')) }}">
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="duration-hint"></div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3 text-muted text-uppercase fw-semibold" style="font-size:.7rem;">{{ __('Customer details') }}</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_name" class="form-label fw-semibold">{{ __('Customer name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="customer_name" name="customer_name"
                                    class="form-control @error('customer_name') is-invalid @enderror"
                                    value="{{ old('customer_name') }}">
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_phone" class="form-label fw-semibold">{{ __('Phone') }}</label>
                                <input type="text" id="customer_phone" name="customer_phone"
                                    class="form-control" value="{{ old('customer_phone') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="customer_email" class="form-label fw-semibold">{{ __('Email') }}</label>
                            <input type="email" id="customer_email" name="customer_email"
                                class="form-control @error('customer_email') is-invalid @enderror"
                                value="{{ old('customer_email') }}"
                                placeholder="{{ __('Used to find or create customer account') }}">
                            @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label for="payment_status" class="form-label fw-semibold">{{ __('Payment status') }}</label>
                            <select id="payment_status" name="payment_status" class="form-select">
                                <option value="pending" {{ old('payment_status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                <option value="paid"    {{ old('payment_status') === 'paid'    ? 'selected' : '' }}>{{ __('Paid') }}</option>
                                <option value="partial" {{ old('payment_status') === 'partial'  ? 'selected' : '' }}>{{ __('Partial') }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold">{{ __('Notes') }}</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i data-feather="check" class="me-1" style="width:15px;height:15px;"></i>
                            {{ __('Create appointment') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var branchSelect  = document.getElementById('branch_id');
    var serviceSelect = document.getElementById('service_id');
    var employeeSelect= document.getElementById('employee_id');
    var durationHint  = document.getElementById('duration-hint');
    var apiUrl        = '{{ route("company.appointments.branch-data") }}';

    function loadBranchData(branchId) {
        if (!branchId) {
            resetSelect(serviceSelect,  '{{ __("Select branch first") }}');
            resetSelect(employeeSelect, '{{ __("Any / no preference") }}');
            return;
        }

        fetch(apiUrl + '?branch_id=' + branchId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(function (data) {
            // Services
            serviceSelect.innerHTML = '<option value="">{{ __("Select service") }}</option>';
            data.services.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name + ' — ' + s.price + ' SAR (' + s.duration + ' {{ __("min") }})';
                opt.dataset.duration = s.duration;
                serviceSelect.appendChild(opt);
            });
            serviceSelect.disabled = false;

            // Employees
            employeeSelect.innerHTML = '<option value="">{{ __("Any / no preference") }}</option>';
            data.employees.forEach(function (e) {
                var opt = document.createElement('option');
                opt.value = e.id;
                opt.textContent = e.name;
                employeeSelect.appendChild(opt);
            });
            employeeSelect.disabled = false;

            updateDurationHint();
        });
    }

    function updateDurationHint() {
        var opt = serviceSelect.options[serviceSelect.selectedIndex];
        if (opt && opt.dataset.duration) {
            durationHint.textContent = '{{ __("Duration") }}: ' + opt.dataset.duration + ' {{ __("min") }}';
        } else {
            durationHint.textContent = '';
        }
    }

    function resetSelect(sel, placeholder) {
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        sel.disabled = true;
    }

    branchSelect.addEventListener('change', function () {
        loadBranchData(this.value);
    });

    serviceSelect.addEventListener('change', updateDurationHint);

    // Auto-load if branch pre-selected
    if (branchSelect.value) loadBranchData(branchSelect.value);
})();
</script>
@endpush
@endsection
