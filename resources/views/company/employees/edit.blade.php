@extends('company.dashboard')

@push('company-styles')
<style>
/* ── Edit Employee — same design as create, blue accent ── */
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

.toggle-row {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,.03); border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,.08); padding: 12px 14px; cursor: pointer;
}
.bk-theme-light .toggle-row { background: #f8f9fa; border-color: #dee2e6; }

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
            <a href="{{ route('company.branches.employees.index', $branch) }}"
               style="background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.3); font-weight:600; font-size:13px; backdrop-filter:blur(4px);"
               class="btn btn-sm rounded-pill px-3">
                <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Back') }}</span>
            </a>
        </div>
    </div>

    @include('company.partials.flash')

    <form method="POST" action="{{ route('company.employees.update', $employee) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row g-4">

        {{-- Left --}}
        <div class="col-lg-7">

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
                        <div class="mb-3">
                            <label class="f-label">{{ __('Profile Photo') }}</label>
                            <div class="d-flex align-items-center gap-3">
                                <div id="edit-photo-preview"
                                     style="width:64px;height:64px;border-radius:14px;overflow:hidden;flex-shrink:0;border:1.5px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);">
                                    @if($employee->image)
                                        <img src="{{ asset('storage/'.$employee->image) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                                    @else
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                            <i data-feather="user" style="width:24px;height:24px;opacity:.3;"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="image" id="edit-photo-input" accept="image/*"
                                           class="f-input form-control" style="font-size:12px;padding:7px 10px;">
                                    <div class="mt-1" style="font-size:10px;opacity:.4;">{{ __('JPG, PNG or WEBP — max 2 MB') }}</div>
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
                                <label class="f-label">{{ __('Name (Arabic)') }}</label>
                                <input type="text" name="name_ar" dir="rtl"
                                       class="f-input form-control"
                                       value="{{ old('name_ar', $employee->name_ar) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="f-label">{{ __('Email') }}</label>
                                <input type="email" name="email"
                                       class="f-input form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $employee->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="f-label">{{ __('Phone') }}</label>
                                <input type="text" name="phone" class="f-input form-control"
                                       value="{{ old('phone', $employee->phone) }}">
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
                                            style="border-radius:0 11px 11px 0; border:1.5px solid rgba(255,255,255,.1); border-left:0; background:rgba(255,255,255,.04);">
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
                            <p class="sec-sub mb-3" style="font-size:12px;">
                                {{ __('Select the specific services this employee can perform:') }}
                            </p>
                            @foreach($services as $catId => $group)
                            @php $cat = $group->first()->serviceCategory; @endphp
                            <div class="mb-3">
                                <div class="f-label mb-2">
                                    {{ $cat ? (app()->getLocale()==='ar' ? ($cat->name_ar ?: $cat->name_en) : ($cat->name_en ?: $cat->name_ar)) : __('Uncategorized') }}
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($group as $svc)
                                    @php $checked = in_array($svc->id, old('service_ids', $selectedServiceIds)); @endphp
                                    <label class="svc-chip {{ $checked ? 'selected' : '' }}" for="svc_{{ $svc->id }}">
                                        <input type="checkbox" id="svc_{{ $svc->id }}"
                                               name="service_ids[]" value="{{ $svc->id }}"
                                               class="svc-check" style="display:none;"
                                               {{ $checked ? 'checked' : '' }}>
                                        <i data-feather="check-circle" class="svc-chip-icon"></i>
                                        {{ app()->getLocale()==='ar' ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar) }}
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="sec-body">
                            <p class="sec-sub" style="font-size:12px;">
                                {{ __('No active services for this branch yet.') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Right --}}
        <div class="col-lg-5">

            <div class="card border-0 sec-card bk-a2">
                <div class="card-body p-0">
                    <div class="sec-header">
                        <div class="sec-icon" style="background:rgba(250,112,154,.12);">
                            <i data-feather="clock" style="width:15px;height:15px;color:#fa709a;"></i>
                        </div>
                        <div>
                            <div class="sec-title">{{ __('Working Schedule') }}</div>
                            <div class="sec-sub">{{ __('Set working days & hours') }}</div>
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
                                $startTime = $oldRow['start_time'] ?? ($saved?->start_time ?? '09:00');
                                $endTime   = $oldRow['end_time'] ?? ($saved?->end_time ?? '17:00');
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

            {{-- Social Links --}}
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

            <div class="card border-0 sec-card bk-a3">
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

            <button type="submit" class="btn-submit-emp bk-a4">
                <i data-feather="save" style="width:15px;height:15px;"></i>
                {{ __('Update Employee') }}
            </button>
        </div>

    </div>
    </form>
</div>

@push('scripts')
<script>
// Photo preview
document.getElementById('edit-photo-input').addEventListener('change', function () {
    const wrap = document.getElementById('edit-photo-preview');
    const file = this.files && this.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => { wrap.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">'; };
    reader.readAsDataURL(file);
});

document.querySelectorAll('.wh-toggle').forEach(t => {
    t.addEventListener('change', function () {
        const n = this.id.split('_')[1];
        document.getElementById('pill-' + n).classList.toggle('active', this.checked);
        const times = document.getElementById('times-' + n);
        times.style.display = this.checked ? 'flex' : 'none';
        times.querySelectorAll('input').forEach(i => i.disabled = !this.checked);
    });
});

document.querySelectorAll('.svc-chip').forEach(chip => {
    chip.addEventListener('click', function () {
        const chk = this.querySelector('.svc-check');
        if (!chk) return;
        chk.checked = !chk.checked;
        this.classList.toggle('selected', chk.checked);
    });
});

const bookableToggle = document.getElementById('is_bookable');
const servicesPanel  = document.getElementById('services-panel');
if (bookableToggle && servicesPanel) {
    bookableToggle.addEventListener('change', function () {
        servicesPanel.style.display = this.checked ? '' : 'none';
        if (!this.checked) {
            servicesPanel.querySelectorAll('.svc-check').forEach(c => {
                c.checked = false;
                c.closest('.svc-chip')?.classList.remove('selected');
            });
        }
    });
}

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
