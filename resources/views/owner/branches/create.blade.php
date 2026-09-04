@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

<div class="page-content bm-wrap">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a>
                <span aria-hidden="true">·</span> {{ __('New') }}
            </div>
            <h1 class="bm-title">{{ __('New branch') }}</h1>
            <p class="bm-subtitle">{{ __('Create a location, then set its working hours and staff in the next steps.') }}</p>
        </div>
    </header>

    @include('owner.partials.flash')

    <div class="bm-form-card bm-reveal">
        <form method="post" action="{{ route('owner.branches.store') }}" id="branch-step1-form" enctype="multipart/form-data">
            @csrf
            <div class="bm-form-body">
                @include('owner.branches.partials.wizard-steps', ['currentStep' => 1])

                {{-- Identity --}}
                <div class="bm-section">
                    <div class="bm-section-head"><i data-feather="map-pin"></i><h2 class="bm-section-title">{{ __('Branch details') }}</h2></div>
                    <p class="bm-section-sub">{{ __('The core information customers and staff will see.') }}</p>

                    <div class="mb-3">
                        <label class="bm-label" for="company_id">{{ __('Company') }} <span class="bm-req">*</span></label>
                        <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select company') }}</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
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
                            <label class="bm-label">{{ __('Mobile phone') }}</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="bm-label">{{ __('Landline') }}</label>
                            <input type="text" name="landline_phone" value="{{ old('landline_phone') }}" class="form-control @error('landline_phone') is-invalid @enderror">
                            @error('landline_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="bm-label">{{ __('Address') }}</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="bm-label">{{ __('Display order') }}</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="form-control @error('sort_order') is-invalid @enderror">
                            <p class="bm-help">{{ __('Lower numbers appear first.') }}</p>
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_head_office" id="is_head_office" value="1" @checked(old('is_head_office'))>
                                <label class="form-check-label" for="is_head_office">{{ __('Mark as head office') }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <div class="bm-section">
                    <div class="bm-section-head"><i data-feather="map"></i><h2 class="bm-section-title">{{ __('Location on map') }}</h2></div>
                    @include('owner.branches.partials.map-picker')
                </div>

                {{-- Images --}}
                <div class="bm-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="bm-section-head mb-0"><i data-feather="image"></i><h2 class="bm-section-title">{{ __('Branch images') }}</h2></div>
                        <button type="button" class="bm-btn bm-btn-ghost" id="add-image-btn" style="height:38px;">
                            <i data-feather="plus"></i>{{ __('Add image') }}
                        </button>
                    </div>
                    <p class="bm-section-sub" style="margin-top:8px;">{{ __('Images are displayed by sort order (lowest number first).') }}</p>

                    @error('images.*')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                    <div id="images-list"></div>

                    <template id="image-row-template">
                        <div class="image-row d-flex align-items-start gap-3 mb-3 p-3 border rounded-3">
                            <div class="flex-grow-1">
                                <input type="file" name="images[]" class="form-control image-file-input" accept="image/*">
                                <div class="image-preview-wrap d-none mt-2 text-center">
                                    <img src="" alt="" class="rounded-3 border shadow-sm image-preview" style="max-height:120px; max-width:100%; object-fit:cover;">
                                </div>
                            </div>
                            <div style="width:100px; flex-shrink:0;">
                                <label class="bm-label" style="font-size:.74rem;">{{ __('Sort order') }}</label>
                                <input type="number" name="image_sort_orders[]" value="0" min="0" class="form-control text-center">
                            </div>
                            <button type="button" class="bm-act bm-act-danger remove-image-btn mt-4" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                                <i data-feather="trash-2"></i>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Social links --}}
                <div class="bm-section">
                    <div class="bm-section-head"><i data-feather="share-2"></i><h2 class="bm-section-title">{{ __('Social Media Links') }}</h2></div>
                    <p class="bm-section-sub">{{ __('Add any social accounts you want customers to find you on.') }}</p>
                    <div class="border rounded-3" style="overflow:hidden;">
                        @include('partials.social-links-form', [
                            'savedLinks'       => collect(),
                            'inputPrefix'      => 'social_links',
                            'accentColor'      => '#5C7038',
                            'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram'],
                        ])
                    </div>
                </div>
            </div>

            <div class="bm-form-foot">
                <a href="{{ route('owner.branches.index') }}" class="bm-btn bm-btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="bm-btn bm-btn-primary bm-spacer">
                    {{ __('Continue') }}<i data-feather="arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') window.feather.replace();

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
        list.appendChild(template.content.cloneNode(true));
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
            reader.onload = function (e) { preview.src = e.target.result; wrap.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        });

        newRow.querySelector('.remove-image-btn').addEventListener('click', function () { newRow.remove(); });

        updateSortOrderDefaults();
        if (typeof window.feather !== 'undefined') window.feather.replace();
    });
});
</script>
@endpush
@endsection
