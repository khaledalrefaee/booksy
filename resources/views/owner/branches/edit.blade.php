@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')

@push('owner-styles')
<style>
/* ═══════════ Branch edit — elevated "editorial" form skin (bx-*) ═══════════ */
.bx-wrap { --bx-serif:'Fraunces', Georgia, 'Times New Roman', serif; max-width:1080px; margin-inline:auto; }

/* Hero band */
.bx-hero { position:relative; overflow:hidden; border-radius:24px; padding:30px 34px; margin-bottom:22px;
    background:linear-gradient(135deg, var(--bk-accent) 0%, var(--bk-gold-strong) 130%); color:#fff; box-shadow:var(--bk-shadow-lg); }
.bx-hero::before { content:''; position:absolute; inset-inline-end:-60px; top:-70px; width:230px; height:230px; border-radius:50%;
    background:rgba(255,255,255,.12); pointer-events:none; }
.bx-hero::after { content:''; position:absolute; inset-inline-end:60px; bottom:-90px; width:170px; height:170px; border-radius:50%;
    background:rgba(255,255,255,.08); pointer-events:none; }
.bx-hero-inner { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap; }
.bx-eyebrow { display:flex; align-items:center; gap:7px; flex-wrap:wrap; font-size:.72rem; font-weight:700; letter-spacing:.14em;
    text-transform:uppercase; color:rgba(255,255,255,.82); margin-bottom:10px; }
.bx-eyebrow a { color:rgba(255,255,255,.82); text-decoration:none; }
.bx-eyebrow a:hover { color:#fff; }
.bx-hero h1 { font-family:var(--bx-serif); font-size:2.15rem; font-weight:600; line-height:1.05; margin:0; letter-spacing:-.015em; color:#fff; }
.bx-hero-badges { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:12px; }
.bx-hero-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; font-size:.76rem; font-weight:600;
    background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.22); backdrop-filter:blur(4px); }
.bx-hero-badge i, .bx-hero-badge svg { width:13px; height:13px; }
.bx-hero-avatar { width:64px; height:64px; border-radius:18px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28); color:#fff; }
.bx-hero-avatar i, .bx-hero-avatar svg { width:28px; height:28px; }

/* Section cards */
.bx-card { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:20px; box-shadow:var(--bk-shadow);
    overflow:hidden; margin-bottom:18px; transition:box-shadow .25s, border-color .25s; }
.bx-card:hover { box-shadow:var(--bk-shadow-lg); border-color:color-mix(in srgb, var(--bk-accent) 22%, var(--bk-border)); }
.bx-card-head { display:flex; align-items:center; gap:14px; padding:19px 24px; border-bottom:1px solid var(--bk-border);
    background:linear-gradient(120deg, var(--bk-accent-wash), transparent 70%); }
.bx-card-ic { width:42px; height:42px; border-radius:13px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-gold-strong); box-shadow:var(--bk-shadow); }
.bx-card-ic i, .bx-card-ic svg { width:19px; height:19px; }
.bx-card-titles { min-width:0; }
.bx-card-title { font-family:var(--bx-serif); font-size:1.12rem; font-weight:600; color:var(--bk-text); margin:0; line-height:1.15; }
.bx-card-sub { font-size:.8rem; color:var(--bk-text-muted); margin:2px 0 0; }
.bx-card-body { padding:24px; }

/* Premium field skin (scoped, overrides bm defaults inside this page) */
.bx-wrap .bm-label { font-size:.78rem; letter-spacing:.02em; text-transform:uppercase; font-weight:700; color:var(--bk-text-muted); margin-bottom:8px; }
.bx-wrap .form-control,
.bx-wrap .form-select,
.bx-wrap textarea.form-control {
    border-radius:14px !important; min-height:50px; padding:13px 16px; font-size:.92rem;
    background:var(--bk-bg); border:1.5px solid var(--bk-border); color:var(--bk-text);
    transition:border-color .18s, box-shadow .18s, background .18s; box-shadow:inset 0 1px 2px rgba(0,0,0,.02);
}
.bx-wrap textarea.form-control { min-height:auto; line-height:1.6; }
.bx-wrap .form-control::placeholder { color:var(--bk-text-muted); }
.bx-wrap .form-control:focus,
.bx-wrap .form-select:focus {
    border-color:var(--bk-accent) !important; background:var(--bk-surface);
    box-shadow:0 0 0 4px var(--bk-accent-wash) !important; }
.bx-wrap .form-control:hover:not(:focus),
.bx-wrap .form-select:hover:not(:focus) { border-color:var(--bk-border-strong); }

/* Switch row → elevated pill */
.bx-switch { display:flex; align-items:center; gap:14px; padding:15px 18px; border:1.5px solid var(--bk-border);
    border-radius:16px; background:var(--bk-bg); transition:border-color .18s, background .18s; cursor:pointer; }
.bx-switch:has(.form-check-input:checked) { border-color:color-mix(in srgb, var(--bk-accent) 45%, transparent); background:var(--bk-accent-wash); }
.bx-switch-ic { width:40px; height:40px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:var(--bk-surface); border:1px solid var(--bk-border); color:var(--bk-gold-strong); }
.bx-switch-ic i, .bx-switch-ic svg { width:18px; height:18px; }
.bx-switch-body { flex:1; min-width:0; }
.bx-switch-title { font-size:.9rem; font-weight:600; color:var(--bk-text); }
.bx-switch-sub { font-size:.76rem; color:var(--bk-text-muted); margin-top:1px; }
.bx-switch .form-check-input { width:46px; height:24px; margin:0; cursor:pointer; flex-shrink:0; }
.bx-switch .form-check-input:checked { background-color:var(--bk-accent); border-color:var(--bk-accent); }

/* Sticky action footer */
.bx-foot { position:sticky; bottom:16px; z-index:5; display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    padding:16px 20px; margin-top:22px; border-radius:18px; background:color-mix(in srgb, var(--bk-surface) 92%, transparent);
    border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg); backdrop-filter:blur(10px); }
.bx-foot .bx-spacer { margin-inline-start:auto; }
.bx-foot .bm-btn { height:48px; padding:0 22px; border-radius:13px; }

/* Existing-image rows use the premium radius too */
.bx-wrap .existing-image-row, .bx-wrap .image-row { border-radius:14px !important; border-color:var(--bk-border) !important; background:var(--bk-bg); }

@media (max-width:768px){
    .bx-hero { padding:24px; }
    .bx-hero h1 { font-size:1.7rem; }
    .bx-card-body { padding:18px; }
    .bx-foot { bottom:8px; }
}
@media (prefers-reduced-motion:reduce){ .bx-card { transition:none; } }
</style>
@endpush

@php
    $statusMeta = [
        'active'      => ['label' => __('Active'),      'cls' => 'bm-badge-active',      'icon' => 'check-circle'],
        'inactive'    => ['label' => __('Inactive'),    'cls' => 'bm-badge-inactive',    'icon' => 'slash'],
        'maintenance' => ['label' => __('Maintenance'), 'cls' => 'bm-badge-maintenance', 'icon' => 'tool'],
    ];
    $st = $statusMeta[$branch->status] ?? $statusMeta['inactive'];
@endphp

<div class="page-content bm-wrap bx-wrap">

    {{-- ═══════════ HERO ═══════════ --}}
    <header class="bx-hero bm-reveal">
        <div class="bx-hero-inner">
            <div style="min-width:0;">
                <div class="bx-eyebrow">
                    <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a>
                    <span aria-hidden="true">/</span> {{ __('Edit') }}
                </div>
                <h1>{{ $branch->localizedName() }}</h1>
                <div class="bx-hero-badges">
                    <span class="bx-hero-badge"><i data-feather="briefcase"></i>{{ $branch->company?->localizedName() ?? '—' }}</span>
                    <span class="bx-hero-badge"><i data-feather="{{ $st['icon'] }}"></i>{{ $st['label'] }}</span>
                    @if($branch->is_head_office)<span class="bx-hero-badge"><i data-feather="star"></i>{{ __('Head office') }}</span>@endif
                </div>
            </div>
            <div class="bx-hero-avatar"><i data-feather="map-pin"></i></div>
        </div>
    </header>

    @include('owner.partials.flash')

    <form method="post" action="{{ route('owner.branches.update', $branch) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ─────────── Branch details ─────────── --}}
        <section class="bx-card bm-reveal">
            <div class="bx-card-head">
                <div class="bx-card-ic"><i data-feather="edit-3"></i></div>
                <div class="bx-card-titles">
                    <h2 class="bx-card-title">{{ __('Branch details') }}</h2>
                    <p class="bx-card-sub">{{ __('Identity, contact and status') }}</p>
                </div>
            </div>
            <div class="bx-card-body">
                <div class="mb-3">
                    <label class="bm-label" for="company_id">{{ __('Company') }} <span class="bm-req">*</span></label>
                    <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) old('company_id', $branch->company_id) === (int) $company->id)>{{ $company->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @include('owner.partials.localized-name-fields', [
                    'nameEnId' => 'branch-edit-name-en',
                    'nameArId' => 'branch-edit-name-ar',
                    'nameEnValue' => old('name_en', $branch->name_en),
                    'nameArValue' => old('name_ar', $branch->name_ar),
                    'wrapperClass' => 'mb-3',
                    'size' => 'sm',
                ])

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="bm-label">{{ __('Mobile phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="09xx xxx xxx">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bm-label">{{ __('Landline') }}</label>
                        <input type="text" name="landline_phone" value="{{ old('landline_phone', $branch->landline_phone) }}" class="form-control @error('landline_phone') is-invalid @enderror">
                        @error('landline_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label class="bm-label">{{ __('Address') }}</label>
                    <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $branch->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="bm-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach(['active' => __('Active'), 'inactive' => __('Inactive'), 'maintenance' => __('Maintenance')] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $branch->status) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bm-label">{{ __('Display order') }}</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $branch->sort_order) }}" min="0" class="form-control @error('sort_order') is-invalid @enderror">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="bm-label" for="description_en">{{ __('Description (English)') }}</label>
                        <textarea name="description_en" id="description_en" rows="3" class="form-control @error('description_en') is-invalid @enderror">{{ old('description_en', $branch->description_en) }}</textarea>
                        @error('description_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bm-label" for="description_ar">{{ __('Description (Arabic)') }}</label>
                        <textarea name="description_ar" id="description_ar" rows="3" dir="rtl" lang="ar" class="form-control @error('description_ar') is-invalid @enderror">{{ old('description_ar', $branch->description_ar) }}</textarea>
                        @error('description_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <label class="bx-switch mt-3" for="is_head_office">
                    <span class="bx-switch-ic"><i data-feather="star"></i></span>
                    <span class="bx-switch-body">
                        <span class="bx-switch-title d-block">{{ __('Mark as head office') }}</span>
                        <span class="bx-switch-sub d-block">{{ __('The main branch of this company') }}</span>
                    </span>
                    <span class="form-check form-switch mb-0 p-0">
                        <input class="form-check-input" type="checkbox" name="is_head_office" id="is_head_office" value="1" @checked(old('is_head_office', $branch->is_head_office))>
                    </span>
                </label>
            </div>
        </section>

        {{-- ─────────── Location ─────────── --}}
        <section class="bx-card bm-reveal">
            <div class="bx-card-head">
                <div class="bx-card-ic"><i data-feather="map"></i></div>
                <div class="bx-card-titles">
                    <h2 class="bx-card-title">{{ __('Location on map') }}</h2>
                    <p class="bx-card-sub">{{ __('Pin the exact spot customers will navigate to') }}</p>
                </div>
            </div>
            <div class="bx-card-body">
                @include('owner.branches.partials.map-picker', [
                    'latitude' => old('latitude', $branch->latitude),
                    'longitude' => old('longitude', $branch->longitude),
                ])
            </div>
        </section>

        {{-- ─────────── Images ─────────── --}}
        <section class="bx-card bm-reveal">
            <div class="bx-card-head">
                <div class="bx-card-ic"><i data-feather="image"></i></div>
                <div class="bx-card-titles">
                    <h2 class="bx-card-title">{{ __('Branch images') }}</h2>
                    <p class="bx-card-sub">{{ __('Images are displayed by sort order (lowest number first).') }}</p>
                </div>
                <button type="button" class="bm-btn bm-btn-gold ms-auto" id="add-image-btn" style="height:42px;">
                    <i data-feather="plus"></i>{{ __('Add image') }}
                </button>
            </div>
            <div class="bx-card-body">
                @error('images.*')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                @if ($branch->images->isNotEmpty())
                    <div class="mb-3" id="existing-images-list">
                        @foreach ($branch->images as $image)
                            <div class="existing-image-row d-flex align-items-center gap-3 mb-2 p-3 border" id="existing-row-{{ $image->id }}">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="" class="rounded-3 border shadow-sm flex-shrink-0" style="width:64px;height:64px;object-fit:cover;">
                                <div style="width:110px; flex-shrink:0;">
                                    <label class="bm-label" style="font-size:.72rem;">{{ __('Sort order') }}</label>
                                    <input type="number" name="existing_sort_orders[{{ $image->id }}]" value="{{ old('existing_sort_orders.'.$image->id, $image->sort_order) }}" min="0" class="form-control form-control-sm text-center">
                                </div>
                                <div class="flex-grow-1 text-muted small text-truncate">{{ basename($image->path) }}</div>
                                <div class="flex-shrink-0">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" id="del-img-{{ $image->id }}" class="btn-check delete-image-check">
                                    <label for="del-img-{{ $image->id }}" class="bm-btn bm-btn-danger" style="height:42px;">
                                        <i data-feather="trash-2"></i>{{ __('Delete') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div id="images-list"></div>

                <template id="image-row-template">
                    <div class="image-row d-flex align-items-start gap-3 mb-3 p-3 border">
                        <div class="flex-grow-1">
                            <input type="file" name="images[]" class="form-control image-file-input" accept="image/*">
                            <div class="image-preview-wrap d-none mt-2 text-center">
                                <img src="" alt="" class="rounded-3 border shadow-sm image-preview" style="max-height:120px; max-width:100%; object-fit:cover;">
                            </div>
                        </div>
                        <div style="width:110px; flex-shrink:0;">
                            <label class="bm-label" style="font-size:.72rem;">{{ __('Sort order') }}</label>
                            <input type="number" name="image_sort_orders[]" value="0" min="0" class="form-control text-center">
                        </div>
                        <button type="button" class="bm-act bm-act-danger remove-image-btn mt-4" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                            <i data-feather="trash-2"></i>
                        </button>
                    </div>
                </template>
            </div>
        </section>

        {{-- ─────────── Social links ─────────── --}}
        <section class="bx-card bm-reveal">
            <div class="bx-card-head">
                <div class="bx-card-ic"><i data-feather="share-2"></i></div>
                <div class="bx-card-titles">
                    <h2 class="bx-card-title">{{ __('Social Media Links') }}</h2>
                    <p class="bx-card-sub">{{ __('Add any social accounts you want customers to find you on.') }}</p>
                </div>
            </div>
            <div class="bx-card-body" style="padding:8px 10px;">
                @include('partials.social-links-form', [
                    'savedLinks'       => $socialLinks,
                    'inputPrefix'      => 'social_links',
                    'accentColor'      => '#5C7038',
                    'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram'],
                ])
            </div>
        </section>

        {{-- ─────────── Sticky footer ─────────── --}}
        <div class="bx-foot bm-reveal">
            <a href="{{ route('owner.branches.working-hours.create', $branch) }}" class="bm-btn bm-btn-gold"><i data-feather="clock"></i>{{ __('Working hours') }}</a>
            <a href="{{ route('owner.branches.index') }}" class="bm-btn bm-btn-ghost bx-spacer">{{ __('Cancel') }}</a>
            <button type="submit" class="bm-btn bm-btn-primary"><i data-feather="check"></i>{{ __('Save changes') }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') window.feather.replace();

    var list = document.getElementById('images-list');
    var template = document.getElementById('image-row-template');
    var addBtn = document.getElementById('add-image-btn');

    document.querySelectorAll('.delete-image-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var row = document.getElementById('existing-row-' + this.value);
            if (row) row.style.opacity = this.checked ? '0.4' : '1';
        });
    });

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

        if (typeof window.feather !== 'undefined') window.feather.replace();
    });
});
</script>
@endpush
@endsection
