@extends('company.dashboard')

@section('content')
@php
    $editingId   = old('_edit_id');
    $typeMeta    = [
        'room'      => ['icon' => 'home',  'label' => __('Room')],
        'equipment' => ['icon' => 'tool',  'label' => __('Equipment')],
        'other'     => ['icon' => 'box',   'label' => __('Other')],
    ];
    $byBranch    = $resources->groupBy('branch_id');
    $multiBranch = $branches->count() > 1;
    $activeCount = $resources->where('is_active', true)->count();
    $idleCount   = $resources->count() - $activeCount;
    $defaultBranch = old('branch_id', request('branch_id') ?: ($multiBranch ? null : $branches->first()?->id));
@endphp

<div class="page-content rs">

    <x-section-head :title="__('Resources & rooms')"
        :subtitle="__('Rooms and devices that services need. A service linked to resources can only be booked while one of them is free.')">
        <x-slot:actions>
            @if(! ($branchContext ?? null) && $multiBranch)
                <form method="GET" class="rs-filter">
                    <label for="rs-branch-filter" class="visually-hidden">{{ __('Branch') }}</label>
                    <select id="rs-branch-filter" name="branch_id" class="rs-input rs-select" onchange="this.form.submit()">
                        <option value="">{{ __('All branches') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->localizedName() }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="#rs-add" class="ivh-btn ivh-btn-primary rs-jump">
                <i data-feather="plus"></i>{{ __('Add resource') }}
            </a>
        </x-slot:actions>
    </x-section-head>

    @include('company.partials.flash', ['inlineErrorKeys' => ['name_en', 'branch_id', 'quantity']])

    <div class="rs-layout">

        {{-- ───────────── List ───────────── --}}
        <section class="rs-main" aria-labelledby="rs-list-title">
            <h2 id="rs-list-title" class="visually-hidden">{{ __('Your resources') }}</h2>

            @if ($resources->isNotEmpty())
                <p class="rs-tally">
                    <span><b>{{ $resources->where('type', 'room')->count() }}</b> {{ __('Rooms') }}</span>
                    <span><b>{{ $resources->where('type', 'equipment')->count() }}</b> {{ __('Equipment') }}</span>
                    <span><b>{{ $resources->where('type', 'other')->count() }}</b> {{ __('Other') }}</span>
                    @if ($idleCount)
                        <span class="is-idle"><b>{{ $idleCount }}</b> {{ __('Inactive') }}</span>
                    @endif
                </p>
            @endif

            @forelse ($byBranch as $branchId => $group)
                <div class="rs-group">
                    @if ($multiBranch)
                        <h3 class="rs-group-title">
                            {{ $group->first()->branch?->localizedName() ?? '—' }}
                            <span class="rs-group-count">{{ $group->count() }}</span>
                        </h3>
                    @endif
                    <ul class="rs-list">
                        @foreach ($group as $resource)
                            @php($meta = $typeMeta[$resource->type] ?? $typeMeta['other'])
                            <li class="rs-row {{ $resource->is_active ? '' : 'is-off' }}" data-type="{{ $resource->type }}" style="--i:{{ $loop->index }}">
                                <span class="rs-glyph rs-glyph--{{ $resource->type }}" title="{{ $meta['label'] }}">
                                    <i data-feather="{{ $meta['icon'] }}"></i>
                                    <span class="visually-hidden">{{ $meta['label'] }}</span>
                                </span>

                                <div class="rs-ident">
                                    <div class="rs-name">
                                        {{ $resource->name_en }}
                                        @unless ($resource->is_active)
                                            <span class="rs-flag">{{ __('Inactive') }}</span>
                                        @endunless
                                    </div>
                                    @if ($resource->name_ar)
                                        <div class="rs-name-ar" dir="rtl">{{ $resource->name_ar }}</div>
                                    @endif
                                </div>

                                <div class="rs-applies">
                                    @foreach ($resource->serviceCategories as $cat)
                                        <span class="rs-chip">{{ $cat->localizedName() }}</span>
                                    @endforeach
                                    @if ($resource->services_count > 0)
                                        <span class="rs-chip rs-chip--svc">{{ $resource->services_count }} {{ __('services') }}</span>
                                    @endif
                                    @if ($resource->serviceCategories->isEmpty() && $resource->services_count === 0)
                                        <span class="rs-none">{{ __('Not linked to any service yet') }}</span>
                                    @endif
                                </div>

                                <div class="rs-actions">
                                    <button type="button" class="rs-icon-btn rs-edit"
                                        aria-label="{{ __('Edit resource') }}: {{ $resource->name_en }}"
                                        data-action="{{ route('company.resources.update', $resource) }}"
                                        data-resource="{{ json_encode([
                                            "id"         => $resource->id,
                                            "name_en"    => $resource->name_en,
                                            "name_ar"    => $resource->name_ar,
                                            "type"       => $resource->type,
                                            "sort_order" => $resource->sort_order,
                                            "is_active"  => (bool) $resource->is_active,
                                            "categories" => $resource->serviceCategories->pluck("id")->all(),
                                        ]) }}">
                                        <i data-feather="edit-2"></i>
                                    </button>
                                    <form method="POST" action="{{ route('company.resources.destroy', $resource) }}" class="d-inline"
                                          data-confirm="{{ __('Delete this resource? Linked services will no longer require it, and past appointments will lose their room link.') }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rs-icon-btn rs-icon-btn--danger" aria-label="{{ __('Delete resource') }}: {{ $resource->name_en }}">
                                            <i data-feather="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="rs-empty">
                    <span class="rs-empty-mark"><i data-feather="layers"></i></span>
                    @if (request()->filled('branch_id'))
                        <h3>{{ __('No resources in this branch') }}</h3>
                        <p>{{ __('Add a room or device for this branch, or pick another branch above.') }}</p>
                    @else
                        <h3>{{ __('No resources yet') }}</h3>
                        <p>{{ __('Add your first room or device. Services linked to it can only be booked while it is free, so two clients never get the same chair.') }}</p>
                    @endif
                    <a href="#rs-add" class="ivh-btn ivh-btn-primary"><i data-feather="plus"></i>{{ __('Add resource') }}</a>
                </div>
            @endforelse
        </section>

        {{-- ───────────── Add form ───────────── --}}
        <aside class="rs-side" id="rs-add">
            <form method="POST" action="{{ route('company.resources.store') }}" class="rs-panel" novalidate>
                @csrf
                <h2 class="rs-panel-title">{{ __('Add resource') }}</h2>

                @if ($multiBranch)
                    <div class="rs-field">
                        <label for="rs-branch" class="rs-label">{{ __('Branch') }} <span class="rs-req" aria-hidden="true">*</span></label>
                        <select id="rs-branch" name="branch_id" class="rs-input rs-select {{ ! $editingId && $errors->has('branch_id') ? 'is-invalid' : '' }}" required>
                            <option value="">{{ __('Choose branch') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($defaultBranch == $branch->id)>{{ $branch->localizedName() }}</option>
                            @endforeach
                        </select>
                        @if (! $editingId) @error('branch_id')<p class="rs-err" role="alert">{{ $message }}</p>@enderror @endif
                    </div>
                @else
                    <input type="hidden" name="branch_id" value="{{ $branches->first()?->id }}">
                @endif

                <div class="rs-field">
                    <label for="rs-name-en" class="rs-label">{{ __('Name (EN)') }} <span class="rs-req" aria-hidden="true">*</span></label>
                    <input type="text" id="rs-name-en" name="name_en" required maxlength="255"
                           class="rs-input {{ ! $editingId && $errors->has('name_en') ? 'is-invalid' : '' }}"
                           value="{{ $editingId ? '' : old('name_en') }}" placeholder="{{ __('e.g. Laser room, Massage chair') }}"
                           @if(! $editingId && $errors->has('name_en')) aria-invalid="true" aria-describedby="rs-name-err" @endif>
                    @if (! $editingId) @error('name_en')<p class="rs-err" id="rs-name-err" role="alert">{{ $message }}</p>@enderror @endif
                </div>

                <div class="rs-field">
                    <label for="rs-name-ar" class="rs-label">{{ __('Name (AR)') }}</label>
                    <input type="text" id="rs-name-ar" name="name_ar" dir="rtl" maxlength="255" class="rs-input"
                           value="{{ $editingId ? '' : old('name_ar') }}">
                </div>

                <fieldset class="rs-field">
                    <legend class="rs-label">{{ __('Type') }}</legend>
                    <div class="rs-seg">
                        @foreach ($typeMeta as $key => $meta)
                            <label class="rs-seg-opt">
                                <input type="radio" name="type" value="{{ $key }}" @checked(($editingId ? 'room' : old('type', 'room')) === $key)>
                                <span><i data-feather="{{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="rs-two">
                    <div class="rs-field">
                        <label for="rs-qty" class="rs-label">{{ __('Units') }}</label>
                        <input type="number" id="rs-qty" name="quantity" min="1" max="20" inputmode="numeric"
                               class="rs-input {{ ! $editingId && $errors->has('quantity') ? 'is-invalid' : '' }}"
                               value="{{ $editingId ? 1 : old('quantity', 1) }}">
                    </div>
                    <div class="rs-field">
                        <label for="rs-sort" class="rs-label">{{ __('Sort order') }}</label>
                        <input type="number" id="rs-sort" name="sort_order" min="0" inputmode="numeric" class="rs-input"
                               value="{{ $editingId ? 0 : old('sort_order', 0) }}">
                    </div>
                </div>
                @if (! $editingId) @error('quantity')<p class="rs-err" role="alert">{{ $message }}</p>@enderror @endif
                <p class="rs-hint" id="rs-units-hint" data-tpl="{{ __('Creates :n numbered resources: :first … :last') }}">
                    {{ __('Several identical items? Set the number of units and they are created already numbered.') }}
                </p>

                @if ($serviceCategories->isNotEmpty())
                    <fieldset class="rs-field">
                        <legend class="rs-label">{{ __('Applies to whole categories') }}</legend>
                        <p class="rs-hint">{{ __('Every service in the selected categories (in this branch) will require this resource — no need to link services one by one.') }}</p>
                        <div class="rs-chips">
                            @foreach ($serviceCategories as $cat)
                                <label class="rs-pick">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                                           @checked(! $editingId && in_array($cat->id, old('category_ids', [])))>
                                    <span>{{ $cat->localizedName() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <button type="submit" class="ivh-btn ivh-btn-primary rs-submit">
                    <i data-feather="plus"></i>{{ __('Add') }}
                </button>
            </form>
        </aside>
    </div>
</div>

{{-- ───────────── Edit modal (one shared instance) ───────────── --}}
<div class="modal fade" id="rs-edit-modal" tabindex="-1" aria-labelledby="rs-edit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="rs-edit-form" class="modal-content rs-modal" novalidate>
            @csrf @method('PUT')
            <input type="hidden" name="_edit_id" id="rs-edit-id" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="rs-edit-title">{{ __('Edit resource') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="rs-field">
                    <label for="rs-e-name-en" class="rs-label">{{ __('Name (EN)') }} <span class="rs-req" aria-hidden="true">*</span></label>
                    <input type="text" id="rs-e-name-en" name="name_en" required maxlength="255" class="rs-input">
                    <p class="rs-err" id="rs-e-name-err" role="alert" hidden></p>
                </div>
                <div class="rs-field">
                    <label for="rs-e-name-ar" class="rs-label">{{ __('Name (AR)') }}</label>
                    <input type="text" id="rs-e-name-ar" name="name_ar" dir="rtl" maxlength="255" class="rs-input">
                </div>

                <fieldset class="rs-field">
                    <legend class="rs-label">{{ __('Type') }}</legend>
                    <div class="rs-seg">
                        @foreach ($typeMeta as $key => $meta)
                            <label class="rs-seg-opt">
                                <input type="radio" name="type" value="{{ $key }}">
                                <span><i data-feather="{{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="rs-field">
                    <label for="rs-e-sort" class="rs-label">{{ __('Sort order') }}</label>
                    <input type="number" id="rs-e-sort" name="sort_order" min="0" inputmode="numeric" class="rs-input">
                </div>

                @if ($serviceCategories->isNotEmpty())
                    <fieldset class="rs-field">
                        <legend class="rs-label">{{ __('Applies to whole categories') }}</legend>
                        <div class="rs-chips">
                            @foreach ($serviceCategories as $cat)
                                <label class="rs-pick">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}">
                                    <span>{{ $cat->localizedName() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <label class="rs-switch">
                    <input type="checkbox" name="is_active" value="1" id="rs-e-active">
                    <span class="rs-switch-track" aria-hidden="true"></span>
                    <span class="rs-switch-text">
                        <b>{{ __('Active') }}</b>
                        <small>{{ __('Inactive resources are skipped when checking availability.') }}</small>
                    </span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="ivh-btn ivh-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="ivh-btn ivh-btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('company-styles')
<style>
.rs { --rs-r: 14px; --rs-ease: cubic-bezier(.22,1,.36,1); }
.rs-layout { display:grid; grid-template-columns:minmax(0,1fr) 380px; gap:24px; align-items:start; }
.rs-side { position:sticky; top:84px; }

/* ── Filter + tally ── */
.rs-filter { margin:0; }
.rs-filter .rs-input { height:42px; width:auto; min-width:170px; }
.rs-tally { display:flex; flex-wrap:wrap; gap:6px 18px; margin:0 0 16px; font-size:.86rem; color:var(--bk-text-muted); }
.rs-tally b { font-family:var(--bk-serif); font-size:1.15rem; font-weight:600; color:var(--bk-text); font-variant-numeric:tabular-nums lining-nums; margin-inline-end:3px; }
.rs-tally .is-idle b { color:var(--bk-warning); }

/* ── Groups & rows ── */
.rs-group + .rs-group { margin-top:26px; }
.rs-group-title { display:flex; align-items:center; gap:10px; margin:0 0 10px; font-family:var(--bk-serif); font-size:1.25rem; font-weight:600; color:var(--bk-text); }
.rs-group-count { font-family:inherit; font-size:.78rem; font-weight:700; padding:1px 9px; border-radius:20px; background:var(--bk-surface-2); color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.rs-list { list-style:none; margin:0; padding:0; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--rs-r); box-shadow:var(--bk-shadow); overflow:hidden; }
.rs-row { display:grid; grid-template-columns:44px minmax(150px,1.1fr) minmax(0,1.6fr) auto; gap:6px 16px; align-items:center; padding:14px 18px; border-bottom:1px solid var(--bk-border);
    transition:background .18s var(--rs-ease); animation:rs-in .5s var(--rs-ease) both; animation-delay:calc(var(--i, 0) * 35ms); }
.rs-row:last-child { border-bottom:0; }
.rs-row:hover { background:var(--bk-sidebar-hover); }
.rs-row.is-off .rs-glyph, .rs-row.is-off .rs-ident { opacity:.62; }

.rs-glyph { width:44px; height:44px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:var(--bk-surface-2); color:var(--bk-text-muted); }
.rs-glyph svg { width:20px; height:20px; stroke-width:1.9; }
.rs-glyph--room { background:var(--bk-accent-wash); color:var(--bk-accent); }
.rs-glyph--equipment { background:var(--bk-gold-soft); color:var(--bk-gold-strong); }

.rs-ident { min-width:0; }
.rs-name { display:flex; align-items:center; flex-wrap:wrap; gap:8px; font-size:.95rem; font-weight:650; color:var(--bk-text); overflow-wrap:anywhere; }
.rs-name-ar { margin-top:2px; font-size:.8rem; color:var(--bk-text-muted); }
.rs-flag { padding:1px 8px; border-radius:20px; background:var(--bk-warning-bg); color:var(--bk-warning); font-size:.7rem; font-weight:700; }

.rs-applies { display:flex; flex-wrap:wrap; gap:6px; min-width:0; }
.rs-chip { padding:3px 10px; border-radius:20px; background:var(--bk-accent-wash); color:var(--bk-accent); font-size:.76rem; font-weight:650; }
.rs-chip--svc { background:var(--bk-surface-2); color:var(--bk-text-soft); }
.rs-none { font-size:.8rem; color:var(--bk-text-muted); }

.rs-actions { display:flex; gap:6px; justify-content:flex-end; }
.rs-actions form { margin:0; }
.rs-icon-btn { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); cursor:pointer; transition:color .15s, border-color .15s, background .15s; }
.rs-icon-btn svg { width:16px; height:16px; stroke-width:2; }
.rs-icon-btn:hover { color:var(--bk-accent); border-color:var(--bk-accent); background:var(--bk-accent-wash); }
.rs-icon-btn--danger:hover { color:var(--bk-danger); border-color:var(--bk-danger); background:var(--bk-danger-bg); }
.rs-icon-btn:focus-visible, .rs-input:focus-visible { outline:2px solid var(--bk-accent); outline-offset:2px; }

/* ── Empty ── */
.rs-empty { text-align:center; padding:56px 24px; background:var(--bk-surface); border:1px dashed var(--bk-border-strong); border-radius:var(--rs-r); }
.rs-empty-mark { width:56px; height:56px; margin:0 auto 14px; display:flex; align-items:center; justify-content:center; border-radius:16px; background:var(--bk-accent-wash); color:var(--bk-accent); }
.rs-empty-mark svg { width:26px; height:26px; stroke-width:1.7; }
.rs-empty h3 { margin:0 0 6px; font-family:var(--bk-serif); font-size:1.35rem; font-weight:600; color:var(--bk-text); }
.rs-empty p { max-width:44ch; margin:0 auto 18px; color:var(--bk-text-muted); line-height:1.6; font-size:.92rem; }

/* ── Form panel ── */
.rs-panel { background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:var(--rs-r); box-shadow:var(--bk-shadow); padding:22px; }
.rs-panel-title { margin:0 0 18px; font-family:var(--bk-serif); font-size:1.3rem; font-weight:600; color:var(--bk-text); }
.rs-field { margin:0 0 16px; padding:0; border:0; min-width:0; }
.rs-label { display:block; float:none; width:auto; margin:0 0 6px; padding:0; font-size:.82rem; font-weight:650; color:var(--bk-text-soft); }
.rs-req { color:var(--bk-danger); }
.rs-hint { margin:0 0 10px; font-size:.78rem; line-height:1.5; color:var(--bk-text-muted); }
.rs-two { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.rs-input { width:100%; height:44px; padding:0 13px; border-radius:10px; border:1px solid var(--bk-border-strong); background:var(--bk-bg); color:var(--bk-text); font-size:.92rem; outline:none; transition:border-color .15s, box-shadow .15s; }
.rs-input::placeholder { color:var(--bk-text-muted); opacity:.8; }
.rs-input:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.rs-input[type=number] { font-variant-numeric:tabular-nums; }
.rs-select { padding-inline-end:34px; cursor:pointer; }
.rs-input.is-invalid { border-color:var(--bk-danger); box-shadow:0 0 0 3px var(--bk-danger-bg); }
.rs-err { display:flex; align-items:flex-start; gap:6px; margin:7px 0 0; font-size:.8rem; font-weight:600; color:var(--bk-danger); }
.rs-err[hidden] { display:none; }
.rs-err::before { content:"!"; flex:0 0 auto; width:16px; height:16px; margin-top:1px; border-radius:50%; background:var(--bk-danger); color:var(--bk-surface); font-size:.7rem; line-height:16px; text-align:center; font-weight:800; }

/* segmented type control */
.rs-seg { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; padding:4px; border-radius:12px; background:var(--bk-surface-2); }
.rs-seg-opt { margin:0; cursor:pointer; }
.rs-seg-opt input { position:absolute; opacity:0; pointer-events:none; }
.rs-seg-opt span { display:flex; flex-direction:column; align-items:center; gap:4px; padding:9px 4px; border-radius:9px; font-size:.78rem; font-weight:650; color:var(--bk-text-muted); transition:background .18s, color .18s, box-shadow .18s; }
.rs-seg-opt svg { width:18px; height:18px; stroke-width:1.9; }
.rs-seg-opt:hover span { color:var(--bk-text); }
.rs-seg-opt input:checked + span { background:var(--bk-surface); color:var(--bk-accent); box-shadow:var(--bk-shadow); }
.rs-seg-opt input:focus-visible + span { outline:2px solid var(--bk-accent); outline-offset:1px; }

/* category pills */
.rs-chips { display:flex; flex-wrap:wrap; gap:8px; }
.rs-pick { margin:0; cursor:pointer; }
.rs-pick input { position:absolute; opacity:0; pointer-events:none; }
.rs-pick span { display:inline-flex; align-items:center; gap:6px; min-height:36px; padding:0 14px; border-radius:20px; border:1px solid var(--bk-border-strong); background:var(--bk-bg); color:var(--bk-text-soft); font-size:.82rem; font-weight:600; transition:background .15s, border-color .15s, color .15s; }
.rs-pick:hover span { border-color:var(--bk-accent); }
.rs-pick input:checked + span { background:var(--bk-accent-fill); border-color:var(--bk-accent-fill); color:var(--bk-accent-ink); }
.rs-pick input:checked + span::before { content:"✓"; font-size:.75rem; }
.rs-pick input:focus-visible + span { outline:2px solid var(--bk-accent); outline-offset:2px; }

.rs-submit { width:100%; height:46px; margin-top:4px; }

/* switch */
.rs-switch { display:flex; align-items:flex-start; gap:12px; margin:0; cursor:pointer; }
.rs-switch input { position:absolute; opacity:0; pointer-events:none; }
.rs-switch-track { flex:0 0 auto; width:42px; height:24px; margin-top:1px; border-radius:20px; background:var(--bk-border-strong); position:relative; transition:background .18s; }
.rs-switch-track::after { content:""; position:absolute; top:3px; inset-inline-start:3px; width:18px; height:18px; border-radius:50%; background:var(--bk-surface); box-shadow:0 1px 2px rgba(0,0,0,.25); transition:transform .2s var(--rs-ease); }
.rs-switch input:checked + .rs-switch-track { background:var(--bk-accent-fill); }
.rs-switch input:checked + .rs-switch-track::after { transform:translateX(18px); }
[dir="rtl"] .rs-switch input:checked + .rs-switch-track::after { transform:translateX(-18px); }
.rs-switch input:focus-visible + .rs-switch-track { outline:2px solid var(--bk-accent); outline-offset:2px; }
.rs-switch-text b { display:block; font-size:.9rem; color:var(--bk-text); }
.rs-switch-text small { font-size:.78rem; color:var(--bk-text-muted); }

/* modal */
.rs-modal { background:var(--bk-surface); color:var(--bk-text); border:1px solid var(--bk-border); border-radius:16px; box-shadow:var(--bk-shadow-xl); }
.rs-modal .modal-header, .rs-modal .modal-footer { border-color:var(--bk-border); }
.rs-modal .modal-title { font-family:var(--bk-serif); font-weight:600; }
.rs-modal .btn-close { margin:0; }
[data-bk-theme="dark"] .rs-modal .btn-close { filter:invert(1) grayscale(1); }

.rs-jump { display:none; }

@keyframes rs-in { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

@media (max-width:1100px) { .rs-layout { grid-template-columns:minmax(0,1fr) 330px; } }
@media (max-width:991.98px) {
    .rs-layout { grid-template-columns:1fr; }
    .rs-side { position:static; }
    .rs-jump { display:inline-flex; }
}
@media (max-width:640px) {
    .rs-row { grid-template-columns:44px minmax(0,1fr) auto; padding:14px; }
    .rs-applies { grid-column:2 / -1; grid-row:2; }
    .rs-panel { padding:18px; }
}
@media (prefers-reduced-motion:reduce) {
    .rs-row { animation:none; }
    .rs-row, .rs-input, .rs-pick span, .rs-seg-opt span, .rs-switch-track, .rs-switch-track::after, .rs-icon-btn { transition:none; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var modalEl = document.getElementById('rs-edit-modal');
    if (!modalEl) return;
    var form = document.getElementById('rs-edit-form');
    var nameErr = document.getElementById('rs-e-name-err');
    var nameEn = document.getElementById('rs-e-name-en');
    var modal = null;
    function getModal() { return modal || (modal = bootstrap.Modal.getOrCreateInstance(modalEl)); }

    function fill(data, action) {
        form.action = action;
        document.getElementById('rs-edit-id').value = data.id;
        nameEn.value = data.name_en || '';
        document.getElementById('rs-e-name-ar').value = data.name_ar || '';
        document.getElementById('rs-e-sort').value = data.sort_order == null ? 0 : data.sort_order;
        document.getElementById('rs-e-active').checked = !!data.is_active;
        form.querySelectorAll('input[name=type]').forEach(function (r) { r.checked = r.value === data.type; });
        var cats = (data.categories || []).map(String);
        form.querySelectorAll('input[name="category_ids[]"]').forEach(function (c) { c.checked = cats.indexOf(c.value) !== -1; });
        setError('');
    }
    function setError(msg) {
        nameErr.textContent = msg;
        nameErr.hidden = !msg;
        nameEn.classList.toggle('is-invalid', !!msg);
        if (msg) { nameEn.setAttribute('aria-invalid', 'true'); nameEn.setAttribute('aria-describedby', 'rs-e-name-err'); }
        else { nameEn.removeAttribute('aria-invalid'); nameEn.removeAttribute('aria-describedby'); }
    }

    document.querySelectorAll('.rs-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fill(JSON.parse(btn.getAttribute('data-resource')), btn.dataset.action);
            getModal().show();
        });
    });
    modalEl.addEventListener('shown.bs.modal', function () { nameEn.focus(); nameEn.select(); });

    // Failed update → reopen the same modal with what the user typed + the error.
    var oldId = @json(old('_edit_id'));
    if (oldId) {
        var btn = Array.prototype.find.call(document.querySelectorAll('.rs-edit'), function (b) {
            return String(JSON.parse(b.getAttribute('data-resource')).id) === String(oldId);
        });
        if (btn) {
            var data = JSON.parse(btn.getAttribute('data-resource'));
            var old = @json(old());
            data.name_en = old.name_en || data.name_en;
            data.name_ar = old.name_ar;
            data.type = old.type || data.type;
            data.sort_order = old.sort_order;
            data.is_active = !!old.is_active;
            data.categories = old.category_ids || [];
            fill(data, btn.dataset.action);
            setError(@json($errors->first('name_en')));
            document.addEventListener('DOMContentLoaded', function () { getModal().show(); });
            if (document.readyState !== 'loading') getModal().show();
        }
    }

    // Live hint for "Units".
    var qty = document.getElementById('rs-qty'), hint = document.getElementById('rs-units-hint'),
        nm = document.getElementById('rs-name-en');
    if (qty && hint) {
        var base = hint.textContent.trim();
        function upd() {
            var n = parseInt(qty.value, 10) || 1, name = nm.value.trim() || '…';
            hint.textContent = n > 1
                ? hint.dataset.tpl.replace(':n', n).replace(':first', name + ' 1').replace(':last', name + ' ' + n)
                : base;
        }
        qty.addEventListener('input', upd); nm.addEventListener('input', upd); upd();
    }

    // Failed add → put the cursor on the offending field.
    @if (! old('_edit_id') && $errors->has('name_en'))
        document.getElementById('rs-name-en')?.focus();
    @endif
})();
</script>
@endpush
