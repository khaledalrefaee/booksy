@php
    $dayNames = \App\Models\EmployeeWorkingHour::$dayNames;
    $locale   = app()->getLocale();
    $pfx      = "employees[$index]";
@endphp

<div class="employee-block emp-block" data-index="{{ $index }}">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="emp-block-num js-employee-number">{{ is_numeric($index) ? $index + 1 : 1 }}</div>
            <span style="font-weight:600;font-size:13px;">{{ __('Employee') }}</span>
        </div>
        <button type="button" class="btn-remove-emp js-remove-employee" hidden>
            <i data-feather="x" style="width:13px;height:13px;"></i> {{ __('Remove') }}
        </button>
    </div>

    {{-- Profile Photo --}}
    <div class="mb-3">
        <label class="f-label">{{ __('Profile Photo') }}</label>
        <div class="d-flex align-items-center gap-3">
            <div class="emp-photo-preview" id="photo-preview-{{ $index }}"
                 style="width:60px;height:60px;border-radius:14px;background:rgba(255,255,255,.08);border:1.5px dashed rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                <i data-feather="user" style="width:22px;height:22px;opacity:.3;" class="emp-photo-placeholder-{{ $index }}"></i>
            </div>
            <div class="flex-grow-1">
                <input type="file" name="{{ $pfx }}[image]" accept="image/*"
                       class="f-input form-control emp-photo-input"
                       data-preview="photo-preview-{{ $index }}"
                       data-placeholder="emp-photo-placeholder-{{ $index }}"
                       style="font-size:12px;padding:7px 10px;">
                <div class="mt-1" style="font-size:10px;opacity:.4;">{{ __('JPG, PNG or WEBP — max 2 MB') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="f-label">{{ __('Name (English)') }} <span class="text-danger">*</span></label>
            <input type="text" name="{{ $pfx }}[name_en]"
                   class="f-input form-control"
                   value="{{ $row['name_en'] ?? '' }}" placeholder="John Doe">
        </div>
        <div class="col-md-6">
            <label class="f-label">{{ __('Name (Arabic)') }}</label>
            <input type="text" name="{{ $pfx }}[name_ar]" dir="rtl"
                   class="f-input form-control"
                   value="{{ $row['name_ar'] ?? '' }}" placeholder="جون دو">
        </div>
        <div class="col-md-6">
            <label class="f-label">{{ __('Email') }}</label>
            <input type="email" name="{{ $pfx }}[email]"
                   class="f-input form-control"
                   value="{{ $row['email'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="f-label">{{ __('Phone') }}</label>
            <input type="text" name="{{ $pfx }}[phone]"
                   class="f-input form-control"
                   value="{{ $row['phone'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="f-label">{{ __('Password') }} <span class="text-danger">*</span></label>
            <input type="password" name="{{ $pfx }}[password]"
                   class="f-input form-control" placeholder="••••••••">
        </div>
        <div class="col-md-6">
            <label class="f-label">{{ __('Bio') }}</label>
            <input type="text" name="{{ $pfx }}[bio]"
                   class="f-input form-control"
                   value="{{ $row['bio'] ?? '' }}"
                   placeholder="{{ __('Short description about the employee…') }}">
        </div>
    </div>

    {{-- Working Schedule --}}
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:28px;height:28px;border-radius:8px;background:rgba(250,112,154,.12);display:flex;align-items:center;justify-content:center;">
                <i data-feather="clock" style="width:13px;height:13px;color:#fa709a;"></i>
            </div>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.5);">{{ __('Working Schedule') }}</span>
        </div>
        <div class="d-flex flex-column gap-2">
            @foreach($dayNames as $dayNum => $names)
            @php
                $wOld      = $row['working_hours'][$dayNum] ?? [];
                $isWorking = !empty($wOld['is_working']);
                $uid       = "wt_{$index}_{$dayNum}";
            @endphp
            <div class="day-pill {{ $isWorking ? 'active' : '' }}" id="pill-{{ $uid }}">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="day-name">{{ $locale === 'ar' ? $names['ar'] : $names['en'] }}</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input wh-toggle" type="checkbox"
                               name="{{ $pfx }}[working_hours][{{ $dayNum }}][is_working]"
                               value="1" id="{{ $uid }}"
                               {{ $isWorking ? 'checked' : '' }}>
                    </div>
                </div>
                <div class="day-times" id="times-{{ $uid }}" style="{{ $isWorking ? '' : 'display:none;' }}">
                    <input type="time" name="{{ $pfx }}[working_hours][{{ $dayNum }}][start_time]"
                           value="{{ $wOld['start_time'] ?? '09:00' }}"
                           {{ !$isWorking ? 'disabled' : '' }}>
                    <span class="sep">→</span>
                    <input type="time" name="{{ $pfx }}[working_hours][{{ $dayNum }}][end_time]"
                           value="{{ $wOld['end_time'] ?? '17:00' }}"
                           {{ !$isWorking ? 'disabled' : '' }}>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Social Links --}}
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:28px;height:28px;border-radius:8px;background:rgba(99,102,241,.15);display:flex;align-items:center;justify-content:center;">
                <i data-feather="share-2" style="width:13px;height:13px;color:#6366f1;"></i>
            </div>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.5);">{{ __('Social Media Links') }}</span>
        </div>
        <div class="border rounded-3" style="overflow:hidden;border-color:rgba(255,255,255,.09) !important;">
            @include('partials.social-links-form', [
                'savedLinks'       => collect(),
                'inputPrefix'      => "{$pfx}[social_links]",
                'accentColor'      => '#c9a227',
                'allowedPlatforms' => ['whatsapp', 'facebook', 'instagram'],
            ])
        </div>
    </div>

    {{-- Active --}}
    <label class="toggle-row" for="employee-active-{{ $index }}">
        <div class="flex-grow-1">
            <div style="font-weight:700;font-size:13px;">{{ __('Active Employee') }}</div>
            <div style="font-size:11px;color:rgba(255,255,255,.45);">{{ __('Can receive bookings') }}</div>
        </div>
        <input type="checkbox" class="form-check-input" id="employee-active-{{ $index }}"
               name="{{ $pfx }}[is_active]" value="1" checked
               style="width:42px;height:22px;cursor:pointer;flex-shrink:0;">
    </label>

</div>
