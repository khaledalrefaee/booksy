@extends('company.dashboard')

@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <h4 class="mb-0">{{ __('Add branch') }}</h4>
        <a href="{{ route('company.branches.index') }}" class="btn btn-outline-secondary">
            <i data-feather="arrow-left" style="width:14px;"></i> {{ __('Back') }}
        </a>
    </div>

    @include('company.partials.flash')

    <div class="row">
        <div class="col-md-8 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('company.branches.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name_en" class="form-label fw-semibold">{{ __('Branch name (EN)') }} <span class="text-danger">*</span></label>
                            <input type="text" id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}">
                            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="name_ar" class="form-label fw-semibold">{{ __('Branch name (AR)') }}</label>
                            <input type="text" id="name_ar" name="name_ar" dir="rtl" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar') }}">
                            @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label fw-semibold">{{ __('Address') }}</label>
                            <input type="text" id="address" name="address" class="form-control" value="{{ old('address') }}">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">{{ __('Phone') }}</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        {{-- Status --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('Branch Status') }} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 flex-wrap mt-1">
                                @foreach(['active' => ['check-circle','success',__('Active'),__('Open & visible to customers')], 'inactive' => ['x-circle','secondary',__('Inactive'),__('Hidden from public')], 'maintenance' => ['tool','warning',__('Maintenance'),__('Visible but booking disabled')]] as $st => [$icon,$color,$lbl,$hint])
                                <label class="d-flex align-items-start gap-2 p-3 rounded-3 border cursor-pointer flex-fill
                                    {{ old('status','active') === $st ? 'border-'.$color.' bg-'.$color.' bg-opacity-10' : '' }}"
                                    style="cursor:pointer;min-width:140px;" id="status-lbl-{{ $st }}">
                                    <input type="radio" name="status" value="{{ $st }}" class="form-check-input mt-0 flex-shrink-0"
                                        {{ old('status','active') === $st ? 'checked' : '' }}
                                        onchange="highlightStatus()">
                                    <div>
                                        <span class="fw-semibold text-{{ $color }} d-flex align-items-center gap-1">
                                            <i data-feather="{{ $icon }}" style="width:13px;height:13px;"></i>
                                            {{ $lbl }}
                                        </span>
                                        <small class="text-muted">{{ $hint }}</small>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" id="is_head_office" name="is_head_office" value="1" class="form-check-input" {{ old('is_head_office') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_head_office">{{ __('Head office') }}</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save branch') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
const statusColors = {active:'success', inactive:'secondary', maintenance:'warning'};
function highlightStatus(){
    document.querySelectorAll('[name="status"]').forEach(radio => {
        const lbl = document.getElementById('status-lbl-' + radio.value);
        if(!lbl) return;
        const c = statusColors[radio.value];
        if(radio.checked){
            lbl.classList.add('border-' + c, 'bg-' + c, 'bg-opacity-10');
        } else {
            lbl.classList.remove('border-success','border-secondary','border-warning','bg-success','bg-secondary','bg-warning','bg-opacity-10');
        }
    });
    if(window.feather) feather.replace();
}
</script>
@endpush
@endsection
