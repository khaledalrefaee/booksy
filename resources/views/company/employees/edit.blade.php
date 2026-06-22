@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Shared form styles ── */
.emp-form-hero {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #fff;
    position: relative; overflow: hidden;
}
.emp-form-hero::before {
    content: ''; position: absolute;
    top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.07); pointer-events: none;
}
[dir="rtl"] .emp-form-hero::before { right: auto; left: -50px; }
.emp-avatar-hero {
    width: 52px; height: 52px; border-radius: 15px;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 20px; color: #fff; flex-shrink: 0;
}

/* ── Tab bar ── */
.emp-tabs {
    display: flex; gap: 4px;
    background: rgba(255,255,255,.05);
    border-radius: 16px; padding: 5px;
    margin-bottom: 22px;
}
.bk-theme-light .emp-tabs { background: rgba(0,0,0,.04); }
.emp-tab-btn {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px;
    padding: 10px 12px; border-radius: 12px; border: none; cursor: pointer;
    font-size: 13px; font-weight: 600; color: rgba(255,255,255,.45);
    background: transparent; transition: all .2s;
}
.bk-theme-light .emp-tab-btn { color: rgba(0,0,0,.4); }
.emp-tab-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
.emp-tab-btn .tab-badge {
    width: 18px; height: 18px; border-radius: 50%;
    background: #f5576c; color: #fff;
    font-size: 10px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
}
.emp-tab-btn.active {
    background: rgba(14,165,233,.18); color: #0ea5e9;
    box-shadow: 0 2px 10px rgba(14,165,233,.15);
}
.bk-theme-light .emp-tab-btn.active { background: rgba(14,165,233,.12); }
.emp-tab-pane { display: none; }
.emp-tab-pane.active { display: block; }

/* ── Section cards ── */
.sec-card { border-radius: 16px !important; margin-bottom: 18px; }
.sec-header {
    padding: 14px 20px 13px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex; align-items: center; gap: 10px;
    border-radius: 16px 16px 0 0;
}
.bk-theme-light .sec-header { border-bottom-color: rgba(0,0,0,.07); }
.sec-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sec-body { padding: 18px 20px; }
.sec-title { font-weight: 700; font-size: 13px; }
.sec-sub { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 1px; }
.bk-theme-light .sec-sub { color: rgba(0,0,0,.45); }

/* ── Inputs ── */
.f-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: rgba(255,255,255,.5); margin-bottom: 5px; display: block;
}
.bk-theme-light .f-label { color: rgba(0,0,0,.5); }
.f-input {
    width: 100%;
    background: rgba(255,255,255,.05); border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 11px; padding: 9px 13px;
    font-size: 13px; color: inherit;
    transition: border-color .2s, background .2s, box-shadow .2s; outline: none;
}
.f-input::placeholder { color: rgba(255,255,255,.25); }
.f-input:focus { border-color: #0ea5e9; background: rgba(14,165,233,.08); box-shadow: 0 0 0 3px rgba(14,165,233,.15); }
.bk-theme-light .f-input { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .f-input::placeholder { color: rgba(0,0,0,.3); }
.bk-theme-light .f-input:focus { background: #fff; border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,.12); }
.f-input.is-invalid { border-color: #f5576c !important; }
/* Fix select arrow in dark theme */
.f-input.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba(255,255,255,0.5)' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-size: 16px 12px;
    background-repeat: no-repeat;
    background-position: left 12px center;
    padding-inline-end: 36px;
}
.bk-theme-light .f-input.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23555' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}
/* Photo preview */
#edit-photo-preview > div {
    width: 140px !important; height: 140px !important;
    border-radius: 20px !important;
}

/* ── Working hours ── */
.day-pill {
    border-radius: 12px; border: 1.5px solid rgba(255,255,255,.09);
    padding: 12px 14px; transition: border-color .2s, background .2s;
    background: rgba(255,255,255,.03);
}
.bk-theme-light .day-pill { border-color: #e8ecf1; background: #fafbfc; }
.day-pill.active { border-color: rgba(14,165,233,.5); background: rgba(14,165,233,.07); }
.bk-theme-light .day-pill.active { border-color: #0ea5e9; background: rgba(14,165,233,.06); }
.day-name { font-weight: 700; font-size: 13px; }
.day-times { display: flex; align-items: center; gap: 6px; margin-top: 10px; }
.day-times input {
    flex: 1; border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 9px; padding: 6px 8px;
    font-size: 12px; text-align: center;
    background: rgba(255,255,255,.05); color: inherit; outline: none;
    transition: border-color .2s;
}
.day-times input:focus { border-color: #0ea5e9; }
.day-times input:disabled { opacity: .3; cursor: not-allowed; }
.bk-theme-light .day-times input { background: #fff; border-color: #dee2e6; color: #212529; }
.day-times .sep { color: rgba(255,255,255,.3); font-size: 12px; flex-shrink: 0; }
.bk-theme-light .day-times .sep { color: rgba(0,0,0,.3); }

/* ── Service chips ── */
.svc-chip {
    display: inline-flex; align-items: center; gap: 7px;
    border: 1.5px solid rgba(255,255,255,.1); border-radius: 9px;
    padding: 7px 13px; font-size: 12px; font-weight: 500;
    cursor: pointer; transition: all .18s;
    background: rgba(255,255,255,.04); user-select: none;
}
.bk-theme-light .svc-chip { border-color: #dee2e6; background: #f8f9fa; }
.svc-chip.selected { border-color: rgba(67,233,123,.5); background: rgba(67,233,123,.09); color: #43e97b; }
.bk-theme-light .svc-chip.selected { border-color: #28a745; background: rgba(40,167,69,.08); color: #1a7a36; }
.svc-chip-icon { width: 14px; height: 14px; transition: transform .2s; }
.svc-chip:not(.selected) .svc-chip-icon { opacity: .3; }

/* ── Toggle row ── */
.toggle-row {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,.03); border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,.08); padding: 12px 14px; cursor: pointer;
}
.bk-theme-light .toggle-row { background: #f8f9fa; border-color: #dee2e6; }

/* ── Sticky save bar ── */
.emp-save-bar {
    position: sticky; bottom: 0; z-index: 20;
    padding: 14px 0 10px;
    border-top: 1px solid rgba(255,255,255,.07);
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,.3);
    margin-top: 8px;
}
.bk-theme-light .emp-save-bar {
    background: rgba(255,255,255,.85);
    border-top-color: rgba(0,0,0,.08);
}
.btn-submit-emp {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: #fff; border: none; border-radius: 13px;
    padding: 12px 36px; font-weight: 700; font-size: 14px;
    cursor: pointer; width: 100%;
    box-shadow: 0 4px 18px rgba(14,165,233,.3);
    transition: opacity .2s, transform .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-emp:hover { opacity: .9; transform: translateY(-1px); }
</style>
@endpush

@section('content')
@php
    /* Which tab to open on load — go to the tab that contains errors */
    $errKeys    = $errors->keys();
    $tab1Fields = ['name_en','name_ar','email','phone','role_id','password','bio','image'];
    $tab2Fields = ['comp_type','comp_base_amount','comp_commission_rate','service_ids'];
    $initTab    = 1;
    if (collect($errKeys)->some(fn($k) => str_starts_with($k,'working_hours') || str_starts_with($k,'social_links'))) $initTab = 3;
    elseif (collect($errKeys)->some(fn($k) => in_array($k,$tab2Fields) || str_starts_with($k,'comp_') || str_starts_with($k,'service_'))) $initTab = 2;
    $errTab1 = collect($errKeys)->some(fn($k) => in_array($k,$tab1Fields));
    $errTab2 = collect($errKeys)->some(fn($k) => in_array($k,$tab2Fields) || str_starts_with($k,'comp_') || str_starts_with($k,'service_'));
    $errTab3 = collect($errKeys)->some(fn($k) => str_starts_with($k,'working_hours') || str_starts_with($k,'social_links'));
@endphp

<div class="page-content">

    {{-- Hero --}}
    <div class="emp-form-hero bk-a1">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div class="d-flex align-items-center gap-3">
                <div class="emp-avatar-hero">
                    {{ strtoupper(mb_substr($employee->name_en ?? $employee->name_ar ?? '?', 0, 1)) }}
                </div>
                <div>
                    <nav aria-label="breadcrumb" class="mb-1">
                        <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4);">
                            <li class="breadcrumb-item">
                                <a href="{{ route('company.branches.employees.index', $branch) }}" class="text-decoration-none" style="color:rgba(255,255,255,.6);font-size:13px;">{{ $branch->localizedName() }}</a>
                            </li>
                            <li class="breadcrumb-item active" style="color:rgba(255,255,255,.4);font-size:13px;">{{ __('Edit') }}</li>
                        </ol>
                    </nav>
                    <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">{{ $employee->localizedName() }}</h3>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('company.employees.payroll', $employee) }}"
                   style="background:rgba(67,233,123,.2);color:#22c55e;border:1.5px solid rgba(67,233,123,.4);font-weight:600;font-size:13px;backdrop-filter:blur(4px);"
                   class="btn btn-sm rounded-pill px-3">
                    <i data-feather="dollar-sign" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Payroll') }}</span>
                </a>
                <a href="{{ route('company.employees.deductions.index', $employee) }}"
                   style="background:rgba(245,87,108,.15);color:#f5576c;border:1.5px solid rgba(245,87,108,.3);font-weight:600;font-size:13px;backdrop-filter:blur(4px);"
                   class="btn btn-sm rounded-pill px-3">
                    <i data-feather="minus-circle" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Deductions') }}</span>
                </a>
                <a href="{{ route('company.branches.employees.index', $branch) }}"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);font-weight:600;font-size:13px;backdrop-filter:blur(4px);"
                   class="btn btn-sm rounded-pill px-3">
                    <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                    <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Back') }}</span>
                </a>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    @if($errors->any())
    <div class="alert border-0 rounded-3 mb-3 d-flex gap-3 align-items-start"
         style="background:rgba(245,87,108,.12);color:#f5576c;font-size:13px;">
        <i data-feather="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;"></i>
        <div>
            <div class="fw-bold mb-1">{{ __('Please fix the following errors:') }}</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('company.employees.update', $employee) }}" enctype="multipart/form-data" id="emp-edit-form">
    @csrf @method('PUT')

        {{-- Tab bar --}}
        <div class="emp-tabs">
            <button type="button" class="emp-tab-btn{{ $initTab===1 ? ' active' : '' }}" data-tab="1">
                <i data-feather="user"></i>
                {{ __('Basic Info') }}
                @if($errTab1)<span class="tab-badge">!</span>@endif
            </button>
            <button type="button" class="emp-tab-btn{{ $initTab===2 ? ' active' : '' }}" data-tab="2">
                <i data-feather="scissors"></i>
                {{ __('Services') }}
                @if($errTab2)<span class="tab-badge">!</span>@endif
            </button>
            <button type="button" class="emp-tab-btn{{ $initTab===3 ? ' active' : '' }}" data-tab="3">
                <i data-feather="clock"></i>
                {{ __('Schedule') }}
                @if($errTab3)<span class="tab-badge">!</span>@endif
            </button>
        </div>

        {{-- ══ TAB 1 — Basic Info ══ --}}
        <div class="emp-tab-pane{{ $initTab===1 ? ' active' : '' }}" id="tab-pane-1">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 sec-card bk-a2">
                        <div class="card-body p-0">
                            <div class="sec-header">
                                <div class="sec-icon" style="background:rgba(14,165,233,.15);">
                                    <i data-feather="user" style="width:15px;height:15px;color:#7dd3fc;"></i>
                                </div>
                                <div>
                                    <div class="sec-title">{{ __('Basic Information') }}</div>
                                    <div class="sec-sub">{{ __('Name, contact & credentials') }}</div>
                                </div>
                            </div>
                            <div class="sec-body">
                                {{-- Profile Photo --}}
                                <div class="mb-4">
                                    <label class="f-label">{{ __('Profile Photo') }}</label>
                                    <div class="d-flex align-items-center gap-4">
                                        <div id="edit-photo-preview" style="position:relative;flex-shrink:0;">
                                            <div style="width:110px;height:110px;border-radius:18px;overflow:hidden;border:2px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);">
                                                @if($employee->image)
                                                    <img src="{{ asset('storage/'.$employee->image) }}" id="edit-photo-img" style="width:100%;height:100%;object-fit:cover;" alt="">
                                                @else
                                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                                        <i data-feather="user" style="width:36px;height:36px;opacity:.25;"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <label for="edit-photo-input"
                                                   style="position:absolute;bottom:-6px;inset-inline-end:-6px;width:30px;height:30px;border-radius:50%;background:#0ea5e9;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(14,165,233,.4);">
                                                <i data-feather="camera" style="width:14px;height:14px;color:#fff;"></i>
                                            </label>
                                        </div>
                                        <div class="flex-grow-1">
                                            <input type="file" name="image" id="edit-photo-input" accept="image/*"
                                                   class="f-input form-control" style="font-size:12px;padding:7px 10px;">
                                            <div class="mt-2" style="font-size:11px;opacity:.4;">
                                                {{ __('JPG, PNG or WEBP — max 2 MB') }}<br>
                                                <span style="color:#0ea5e9;opacity:.8;">{{ __('Will be saved as WebP automatically') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="f-label">{{ __('Name (English)') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name_en"
                                               class="f-input form-control @error('name_en') is-invalid @enderror"
                                               value="{{ old('name_en', $employee->name_en) }}">
                                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="f-label">{{ __('Name (Arabic)') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name_ar" dir="rtl"
                                               class="f-input form-control @error('name_ar') is-invalid @enderror"
                                               value="{{ old('name_ar', $employee->name_ar) }}">
                                        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="f-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                                        <input type="email" name="email"
                                               class="f-input form-control @error('email') is-invalid @enderror"
                                               value="{{ old('email', $employee->email) }}">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="f-label">{{ __('Phone') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="phone"
                                               class="f-input form-control @error('phone') is-invalid @enderror"
                                               value="{{ old('phone', $employee->phone) }}">
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="f-label">{{ __('Role') }} <span class="text-danger">*</span></label>
                                        <select name="role_id" class="f-input form-select @error('role_id') is-invalid @enderror">
                                            <option value="">{{ __('Select role…') }}</option>
                                            @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ old('role_id', $employee->role_id) == $role->id ? 'selected' : '' }}>
                                                {{ app()->getLocale()==='ar' ? ($role->label_ar ?: $role->label_en) : ($role->label_en ?: $role->label_ar) }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="f-label">
                                            {{ __('New password') }}
                                            <span style="font-weight:400;text-transform:none;letter-spacing:0;">({{ __('optional') }})</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" id="password" name="password"
                                                   class="f-input form-control" placeholder="••••••••"
                                                   style="border-radius:11px 0 0 11px;">
                                            <button class="btn js-toggle-pw" type="button" data-target="#password" tabindex="-1"
                                                    style="border-radius:0 11px 11px 0;border:1.5px solid rgba(255,255,255,.1);border-left:0;background:rgba(255,255,255,.04);">
                                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="f-label">{{ __('Bio') }}</label>
                                        <textarea name="bio" class="f-input form-control" rows="3">{{ old('bio', $employee->bio) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    {{-- Active Status --}}
                    <div class="card border-0 sec-card bk-a2">
                        <div class="card-body" style="padding:16px 20px;">
                            <label class="toggle-row" for="is_active">
                                <div class="flex-grow-1">
                                    <div class="sec-title">{{ __('Active Employee') }}</div>
                                    <div class="sec-sub">{{ __('Can receive bookings') }}</div>
                                </div>
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
                                       style="width:42px;height:22px;cursor:pointer;flex-shrink:0;">
                            </label>
                        </div>
                    </div>

                    {{-- Quick tips --}}
                    <div style="border-radius:14px;padding:16px 18px;background:rgba(14,165,233,.07);border:1.5px solid rgba(14,165,233,.18);">
                        <div style="font-size:12px;font-weight:700;color:#7dd3fc;margin-bottom:8px;">💡 {{ __('Tips') }}</div>
                        <ul style="font-size:12px;opacity:.7;margin:0;padding-inline-start:18px;line-height:2;">
                            <li>{{ app()->getLocale()==='ar' ? 'الحقول المميزة بـ * إلزامية' : 'Fields marked * are required' }}</li>
                            <li>{{ app()->getLocale()==='ar' ? 'كلمة المرور اختيارية هنا' : 'Password is optional here' }}</li>
                            <li>{{ app()->getLocale()==='ar' ? 'الصورة تُحفظ تلقائياً بصيغة WebP' : 'Photo is auto-converted to WebP' }}</li>
                            <li>{{ app()->getLocale()==='ar' ? 'الخدمات والدوام في التبويبات التالية' : 'Services & schedule in next tabs' }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            {{-- Next button --}}
            <div class="d-flex justify-content-end mt-2 mb-1">
                <button type="button" class="btn-tab-next" data-next="2"
                        style="display:flex;align-items:center;gap:7px;padding:10px 24px;border-radius:12px;border:none;background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 12px rgba(14,165,233,.3);">
                    {{ __('Next') }}
                    <i data-feather="arrow-left" style="width:14px;height:14px;"></i>
                </button>
            </div>
        </div>

        {{-- ══ TAB 2 — Services & Compensation ══ --}}
        <div class="emp-tab-pane{{ $initTab===2 ? ' active' : '' }}" id="tab-pane-2">

            {{-- Compensation --}}
            @include('company.employees.partials.compensation-form', [
                'services'           => $services,
                'compensation'       => $compensation,
                'serviceCommissions' => $serviceCommissions,
            ])

            {{-- is_bookable + Services --}}
            <div class="card border-0 sec-card bk-a3">
                <div class="card-body p-0">
                    <div class="sec-header">
                        <div class="sec-icon" style="background:rgba(67,233,123,.12);">
                            <i data-feather="scissors" style="width:15px;height:15px;color:#43e97b;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="sec-title">{{ __('Service Provider') }}</div>
                            <div class="sec-sub">{{ __('Does this employee offer services to customers?') }}</div>
                        </div>
                        <div class="form-check form-switch mb-0 ms-auto">
                            <input class="form-check-input" type="checkbox"
                                   id="is_bookable" name="is_bookable" value="1"
                                   style="width:42px;height:22px;cursor:pointer;"
                                   {{ old('is_bookable', $employee->is_bookable) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div id="services-panel" style="{{ old('is_bookable', $employee->is_bookable) ? '' : 'display:none;' }}">
                        @if($services->isNotEmpty())
                        <div class="sec-body">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <p class="sec-sub mb-0" style="font-size:12px;">
                                    {{ __('Select services and optionally set a custom price or duration for this employee.') }}
                                </p>
                                <div class="d-flex gap-2">
                                    <button type="button" id="btn-select-all"
                                            style="font-size:11px;font-weight:700;padding:5px 12px;border-radius:8px;border:1.5px solid rgba(67,233,123,.4);background:rgba(67,233,123,.08);color:#43e97b;cursor:pointer;">
                                        ✓ {{ __('Select all') }}
                                    </button>
                                    <button type="button" id="btn-deselect-all"
                                            style="font-size:11px;font-weight:700;padding:5px 12px;border-radius:8px;border:1.5px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);color:rgba(255,255,255,.5);cursor:pointer;">
                                        ✕ {{ __('Clear all') }}
                                    </button>
                                </div>
                            </div>
                            @foreach($services as $catId => $group)
                            @php $cat = $group->first()->serviceCategory; @endphp
                            <div class="mb-4">
                                <div class="f-label mb-2">
                                    {{ $cat ? (app()->getLocale()==='ar' ? ($cat->name_ar ?: $cat->name_en) : ($cat->name_en ?: $cat->name_ar)) : __('Uncategorized') }}
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($group as $svc)
                                    @php
                                        $checked  = in_array($svc->id, old('service_ids', $selectedServiceIds));
                                        $oldPrice = old("service_price.{$svc->id}", $servicePivot[$svc->id]['price'] ?? '');
                                        $oldDur   = old("service_duration.{$svc->id}", $servicePivot[$svc->id]['duration_minutes'] ?? '');
                                        $svcName  = app()->getLocale()==='ar' ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar);
                                    @endphp
                                    <div id="svc-row-{{ $svc->id }}">
                                        <div class="svc-chip w-100 mb-0 js-svc-chip {{ $checked ? 'selected' : '' }}"
                                             data-id="{{ $svc->id }}"
                                             style="border-radius:{{ $checked ? '9px 9px 0 0' : '9px' }};">
                                            <input type="checkbox" id="svc_{{ $svc->id }}"
                                                   name="service_ids[]" value="{{ $svc->id }}"
                                                   class="svc-check" style="display:none;"
                                                   data-id="{{ $svc->id }}"
                                                   {{ $checked ? 'checked' : '' }}>
                                            <i data-feather="check-circle" class="svc-chip-icon"></i>
                                            <span class="flex-grow-1">{{ $svcName }}</span>
                                            <span style="font-size:11px;opacity:.5;">
                                                {{ number_format($svc->price, 0) }} {{ $svc->currency }}
                                                · {{ $svc->duration_minutes }} {{ __('min') }}
                                            </span>
                                        </div>
                                        <div id="svc-overrides-{{ $svc->id }}"
                                             style="display:{{ $checked ? 'flex' : 'none' }};
                                                    border:1.5px solid rgba(67,233,123,.3);
                                                    border-top:none;border-radius:0 0 9px 9px;
                                                    padding:10px 13px;gap:12px;flex-wrap:wrap;
                                                    background:rgba(67,233,123,.05);">
                                            <div style="flex:1;min-width:130px;">
                                                <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.5;display:block;margin-bottom:4px;">{{ __('Custom price') }}</label>
                                                <input type="number" name="service_price[{{ $svc->id }}]"
                                                       value="{{ $oldPrice }}" class="f-input form-control"
                                                       style="font-size:12px;padding:6px 10px;" min="0" step="0.01"
                                                       placeholder="{{ __('Default') }}: {{ number_format($svc->price, 0) }}">
                                            </div>
                                            <div style="flex:1;min-width:130px;">
                                                <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.5;display:block;margin-bottom:4px;">{{ __('Custom duration') }} ({{ __('min') }})</label>
                                                <input type="number" name="service_duration[{{ $svc->id }}]"
                                                       value="{{ $oldDur }}" class="f-input form-control"
                                                       style="font-size:12px;padding:6px 10px;" min="1" max="1440"
                                                       placeholder="{{ __('Default') }}: {{ $svc->duration_minutes }}">
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="sec-body">
                            <p class="sec-sub" style="font-size:12px;">{{ __('No active services for this branch yet.') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Prev / Next --}}
            <div class="d-flex justify-content-between mt-2 mb-1">
                <button type="button" class="btn-tab-next" data-next="1"
                        style="display:flex;align-items:center;gap:7px;padding:10px 24px;border-radius:12px;border:1.5px solid rgba(255,255,255,.15);background:transparent;color:rgba(255,255,255,.6);font-size:13px;font-weight:700;cursor:pointer;">
                    <i data-feather="arrow-right" style="width:14px;height:14px;"></i>
                    {{ __('Previous') }}
                </button>
                <button type="button" class="btn-tab-next" data-next="3"
                        style="display:flex;align-items:center;gap:7px;padding:10px 24px;border-radius:12px;border:none;background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 3px 12px rgba(14,165,233,.3);">
                    {{ __('Next') }}
                    <i data-feather="arrow-left" style="width:14px;height:14px;"></i>
                </button>
            </div>
        </div>

        {{-- ══ TAB 3 — Schedule ══ --}}
        <div class="emp-tab-pane{{ $initTab===3 ? ' active' : '' }}" id="tab-pane-3">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 sec-card bk-a2">
                        <div class="card-body p-0">
                            <div class="sec-header">
                                <div class="sec-icon" style="background:rgba(250,112,154,.12);">
                                    <i data-feather="clock" style="width:15px;height:15px;color:#fa709a;"></i>
                                </div>
                                <div>
                                    <div class="sec-title">{{ __('Working Schedule') }}</div>
                                    <div class="sec-sub">{{ __('Toggle days on/off and set hours') }}</div>
                                </div>
                            </div>
                            <div class="sec-body">
                                @php
                                    $dayNames = \App\Models\EmployeeWorkingHour::$dayNames;
                                    $locale   = app()->getLocale();
                                @endphp
                                <div class="d-flex flex-column gap-2">
                                    @foreach($dayNames as $dayNum => $names)
                                    @php
                                        $saved     = $workingHours->get($dayNum);
                                        $oldRow    = old("working_hours.$dayNum");
                                        $isWorking = $oldRow ? !empty($oldRow['is_working']) : ($saved?->is_working ?? false);
                                        $startTime = substr($oldRow['start_time'] ?? ($saved?->start_time ?? '09:00'), 0, 5);
                                        $endTime   = substr($oldRow['end_time']   ?? ($saved?->end_time   ?? '17:00'), 0, 5);
                                    @endphp
                                    <div class="day-pill {{ $isWorking ? 'active' : '' }}" id="pill-{{ $dayNum }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="day-name">{{ $locale==='ar' ? $names['ar'] : $names['en'] }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input wh-toggle" type="checkbox"
                                                       name="working_hours[{{ $dayNum }}][is_working]"
                                                       value="1" id="wt_{{ $dayNum }}"
                                                       {{ $isWorking ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                        <div class="day-times" id="times-{{ $dayNum }}" style="{{ $isWorking ? '' : 'display:none;' }}">
                                            <input type="time" name="working_hours[{{ $dayNum }}][start_time]"
                                                   value="{{ $startTime }}" {{ !$isWorking ? 'disabled' : '' }}>
                                            <span class="sep">→</span>
                                            <input type="time" name="working_hours[{{ $dayNum }}][end_time]"
                                                   value="{{ $endTime }}" {{ !$isWorking ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 sec-card bk-a3">
                        <div class="card-body p-0">
                            <div class="sec-header">
                                <div class="sec-icon" style="background:rgba(99,102,241,.15);">
                                    <i data-feather="share-2" style="width:15px;height:15px;color:#6366f1;"></i>
                                </div>
                                <div>
                                    <div class="sec-title">{{ __('Social Media Links') }}</div>
                                    <div class="sec-sub">{{ __('Personal accounts (optional)') }}</div>
                                </div>
                            </div>
                            @include('partials.social-links-form', [
                                'savedLinks'       => $socialLinks,
                                'inputPrefix'      => 'social_links',
                                'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram'],
                            ])
                        </div>
                    </div>
                </div>
            </div>
            {{-- Prev button --}}
            <div class="d-flex justify-content-start mt-2 mb-1">
                <button type="button" class="btn-tab-next" data-next="2"
                        style="display:flex;align-items:center;gap:7px;padding:10px 24px;border-radius:12px;border:1.5px solid rgba(255,255,255,.15);background:transparent;color:rgba(255,255,255,.6);font-size:13px;font-weight:700;cursor:pointer;">
                    <i data-feather="arrow-right" style="width:14px;height:14px;"></i>
                    {{ __('Previous') }}
                </button>
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="emp-save-bar">
            <button type="submit" class="btn-submit-emp bk-a4">
                <i data-feather="save" style="width:15px;height:15px;"></i>
                {{ __('Update Employee') }}
            </button>
        </div>

    </form>
</div>

@push('scripts')
<script>
// ── Tabs ──
function switchTab(t) {
    document.querySelectorAll('.emp-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.emp-tab-pane').forEach(p => p.classList.remove('active'));
    const btn = document.querySelector('.emp-tab-btn[data-tab="' + t + '"]');
    if (btn) btn.classList.add('active');
    const pane = document.getElementById('tab-pane-' + t);
    if (pane) { pane.classList.add('active'); pane.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    feather.replace();
}
document.querySelectorAll('.emp-tab-btn').forEach(btn => {
    btn.addEventListener('click', function () { switchTab(this.dataset.tab); });
});
document.querySelectorAll('.btn-tab-next').forEach(btn => {
    btn.addEventListener('click', function () { switchTab(this.dataset.next); });
});

// ── Select All / Clear All ──
function toggleAllServices(select) {
    document.querySelectorAll('.svc-check').forEach(chk => {
        chk.checked = select;
        const chip = chk.closest('.js-svc-chip');
        if (chip) {
            chip.classList.toggle('selected', select);
            chip.style.borderRadius = select ? '9px 9px 0 0' : '9px';
            const id = chip.dataset.id;
            if (id) {
                const ov = document.getElementById('svc-overrides-' + id);
                if (ov) ov.style.display = select ? 'flex' : 'none';
            }
        }
    });
}
const btnSelAll = document.getElementById('btn-select-all');
const btnDeselAll = document.getElementById('btn-deselect-all');
if (btnSelAll) btnSelAll.addEventListener('click', () => toggleAllServices(true));
if (btnDeselAll) btnDeselAll.addEventListener('click', () => toggleAllServices(false));

// ── Photo preview ──
document.getElementById('edit-photo-input').addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
        const inner = document.querySelector('#edit-photo-preview > div');
        if (inner) inner.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
    };
    reader.readAsDataURL(file);
});

// ── Client-side validation ──
document.getElementById('emp-edit-form').addEventListener('submit', function (e) {
    const required = [
        { name: 'name_en', msg: '{{ app()->getLocale()==="ar" ? "الاسم بالإنجليزية مطلوب" : "Name (English) is required" }}' },
        { name: 'name_ar', msg: '{{ app()->getLocale()==="ar" ? "الاسم بالعربية مطلوب" : "Name (Arabic) is required" }}' },
        { name: 'email',   msg: '{{ app()->getLocale()==="ar" ? "البريد الإلكتروني مطلوب" : "Email is required" }}' },
        { name: 'phone',   msg: '{{ app()->getLocale()==="ar" ? "رقم الهاتف مطلوب" : "Phone is required" }}' },
        { name: 'role_id', msg: '{{ app()->getLocale()==="ar" ? "الدور مطلوب" : "Role is required" }}' },
    ];

    let hasError = false;

    required.forEach(({ name }) => {
        const el = document.querySelector('[name="' + name + '"]');
        if (!el) return;
        el.classList.remove('is-invalid');
        el.parentNode.querySelector('.js-val-err')?.remove();
    });

    required.forEach(({ name, msg }) => {
        const el = document.querySelector('[name="' + name + '"]');
        if (!el || el.value.trim()) return;
        el.classList.add('is-invalid');
        const err = document.createElement('div');
        err.className = 'js-val-err';
        err.style.cssText = 'color:#f5576c;font-size:11px;margin-top:4px;';
        err.textContent = msg;
        (el.closest('.input-group') || el).after(err);
        hasError = true;
    });

    if (hasError) {
        e.preventDefault();
        document.querySelector('[data-tab="1"]').click();
        setTimeout(() => {
            document.querySelector('.f-input.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }
});

// ── Working hours toggle ──
document.querySelectorAll('.wh-toggle').forEach(t => {
    t.addEventListener('change', function () {
        const n = this.id.split('_')[1];
        document.getElementById('pill-' + n).classList.toggle('active', this.checked);
        const times = document.getElementById('times-' + n);
        times.style.display = this.checked ? 'flex' : 'none';
        times.querySelectorAll('input').forEach(i => i.disabled = !this.checked);
    });
});

// ── Service chips (click on div, not label, to avoid double-toggle) ──
document.querySelectorAll('.js-svc-chip').forEach(chip => {
    chip.addEventListener('click', function () {
        const chk = this.querySelector('.svc-check');
        if (!chk) return;
        chk.checked = !chk.checked;
        this.classList.toggle('selected', chk.checked);
        const id = this.dataset.id;
        if (id) {
            const overrides = document.getElementById('svc-overrides-' + id);
            if (overrides) overrides.style.display = chk.checked ? 'flex' : 'none';
            this.style.borderRadius = chk.checked ? '9px 9px 0 0' : '9px';
        }
    });
});

// ── is_bookable toggle ──
const bookableToggle = document.getElementById('is_bookable');
const servicesPanel  = document.getElementById('services-panel');
if (bookableToggle && servicesPanel) {
    bookableToggle.addEventListener('change', function () {
        servicesPanel.style.display = this.checked ? '' : 'none';
        if (!this.checked) {
            servicesPanel.querySelectorAll('.svc-check').forEach(c => {
                c.checked = false;
                c.closest('.js-svc-chip')?.classList.remove('selected');
            });
        }
    });
}

// ── Password toggle ──
document.querySelectorAll('.js-toggle-pw').forEach(btn => {
    btn.addEventListener('click', function () {
        const inp = document.querySelector(this.dataset.target);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        this.querySelector('[data-feather]').setAttribute('data-feather', inp.type === 'password' ? 'eye' : 'eye-off');
        feather.replace();
    });
});
</script>
@endpush
@endsection
