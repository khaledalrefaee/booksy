@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('New branch') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ol>
        </nav>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    @include('owner.branches.partials.wizard-steps', ['currentStep' => 1])

                    <form method="post" action="{{ route('owner.branches.store') }}" id="branch-step1-form" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="company_id">{{ __('Company') }} <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-select form-select-lg rounded-3 @error('company_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>
                                        {{ $company->localizedName() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @include('owner.partials.localized-name-fields', [
                            'nameEnId' => 'branch-create-name-en',
                            'nameArId' => 'branch-create-name-ar',
                            'wrapperClass' => 'mb-3',
                        ])

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Mobile phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control rounded-3 @error('phone') is-invalid @enderror">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Landline') }}</label>
                                <input type="text" name="landline_phone" value="{{ old('landline_phone') }}" class="form-control rounded-3 @error('landline_phone') is-invalid @enderror">
                                @error('landline_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">{{ __('Address') }}</label>
                            <textarea name="address" rows="2" class="form-control rounded-3 @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @include('owner.branches.partials.map-picker')

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Sort order') }}</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="form-control rounded-3 @error('sort_order') is-invalid @enderror">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_head_office" id="is_head_office" value="1" @checked(old('is_head_office'))>
                            <label class="form-check-label" for="is_head_office">{{ __('Mark as head office') }}</label>
                        </div>

                        {{-- Branch Images --}}
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">{{ __('Branch images') }}</label>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="add-image-btn">
                                    <i data-feather="plus" style="width:14px;height:14px;"></i>
                                    {{ __('Add image') }}
                                </button>
                            </div>
                            <p class="text-muted small mb-3">{{ __('Images are displayed by sort order (lowest number first).') }}</p>

                            @error('images.*')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror

                            <div id="images-list">
                                {{-- rows added dynamically --}}
                            </div>

                            <template id="image-row-template">
                                <div class="image-row d-flex align-items-start gap-3 mb-3 p-3 border rounded-3">
                                    <div class="flex-grow-1">
                                        <input type="file" name="images[]" class="form-control rounded-3 image-file-input" accept="image/*">
                                        <div class="image-preview-wrap d-none mt-2 text-center">
                                            <img src="" alt="" class="rounded-3 border shadow-sm image-preview" style="max-height:120px; max-width:100%; object-fit:cover;">
                                        </div>
                                    </div>
                                    <div style="width:100px; flex-shrink:0;">
                                        <label class="form-label small fw-semibold">{{ __('Sort order') }}</label>
                                        <input type="number" name="image_sort_orders[]" value="0" min="0" class="form-control rounded-3 text-center">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill remove-image-btn mt-4" title="{{ __('Remove') }}">
                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Social Links --}}
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i data-feather="share-2" style="width:15px;height:15px;"></i>
                                <span class="fw-semibold">{{ __('Social Media Links') }}</span>
                            </div>
                            <p class="text-muted small mb-2">{{ __('Add any social accounts you want customers to find you on.') }}</p>
                            <div class="border rounded-3" style="overflow:hidden;">
                                @include('partials.social-links-form', [
                                    'savedLinks'       => collect(),
                                    'inputPrefix'      => 'social_links',
                                    'accentColor'      => '#c9a227',
                                    'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram'],
                                ])
                            </div>
                        </div>

                        <div class="d-flex justify-content-between gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('owner.branches.index') }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                {{ __('Next') }}
                                <i data-feather="arrow-right" class="ms-1" style="width:16px;height:16px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') {
        window.feather.replace();
    }

    var list = document.getElementById('images-list');
    var template = document.getElementById('image-row-template');
    var addBtn = document.getElementById('add-image-btn');

    function updateSortOrderDefaults() {
        list.querySelectorAll('.image-row').forEach(function (row, i) {
            var input = row.querySelector('input[type="number"]');
            if (input && input.value === '' || input.getAttribute('data-auto') === '1') {
                input.value = i;
                input.setAttribute('data-auto', '1');
            }
        });
    }

    addBtn.addEventListener('click', function () {
        var clone = template.content.cloneNode(true);
        list.appendChild(clone);

        var newRow = list.lastElementChild;

        newRow.querySelector('.image-file-input').addEventListener('change', function () {
            var preview = newRow.querySelector('.image-preview');
            var wrap = newRow.querySelector('.image-preview-wrap');
            var file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                wrap.classList.add('d-none');
                preview.removeAttribute('src');
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                wrap.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });

        newRow.querySelector('.remove-image-btn').addEventListener('click', function () {
            newRow.remove();
        });

        updateSortOrderDefaults();

        if (typeof window.feather !== 'undefined') {
            window.feather.replace();
        }
    });
});
</script>
@endpush
@endsection
