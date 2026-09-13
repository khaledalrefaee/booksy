{{--
  Shared create/edit form for a GlowRez internal team member.
  Expects: $roles (assignable role keys), $roleMeta, $catalog, $roleDefaults,
           and (on edit) $employee. Posts name/email/phone/role/permissions[]/is_active.
--}}
@php
    $emp          = $employee ?? null;
    $currentRole  = old('role', $emp->role ?? ($roles[0] ?? 'support'));
    $currentPerms = (array) old('permissions', $emp?->permissions ?? []);
    $isActive     = (bool) old('is_active', $emp->is_active ?? true);
    $isSelf       = $emp && auth('owner')->id() === $emp->id;

    $roleDef  = (array) ($roleDefaults[$currentRole] ?? []);
    $roleAll  = in_array('*', $roleDef, true);
@endphp

<div class="tm-form">
    {{-- Identity --}}
    <div class="bm-section">
        <div class="bm-section-head"><i data-feather="user"></i><h3 class="bm-section-title">{{ __('Member details') }}</h3></div>
        <p class="bm-section-sub">{{ __('Their name, contact and login email.') }}</p>

        <div class="tm-grid-2">
            <div>
                <label class="bm-label" for="tm-name">{{ __('Full name') }} <span class="bm-req">*</span></label>
                <input type="text" id="tm-name" name="name" class="form-control" value="{{ old('name', $emp->name ?? '') }}" required maxlength="120">
                @error('name')<div class="tm-err">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="bm-label" for="tm-email">{{ __('Email') }} <span class="bm-req">*</span></label>
                <input type="email" id="tm-email" name="email" class="form-control" value="{{ old('email', $emp->email ?? '') }}" required maxlength="190" dir="ltr">
                @error('email')<div class="tm-err">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="bm-label" for="tm-phone">{{ __('Phone') }}</label>
                <input type="text" id="tm-phone" name="phone" class="form-control" value="{{ old('phone', $emp->phone ?? '') }}" maxlength="40" dir="ltr">
                @error('phone')<div class="tm-err">{{ $message }}</div>@enderror
            </div>
            @unless($isSelf)
            <div>
                <label class="bm-label">{{ __('Account status') }}</label>
                <label class="tm-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ $isActive ? 'checked' : '' }}>
                    <span class="tm-switch-track"></span>
                    <span class="tm-switch-text">{{ __('Active — can sign in') }}</span>
                </label>
            </div>
            @endunless
        </div>
    </div>

    {{-- Password (create only) --}}
    @unless($emp)
    <div class="bm-section">
        <div class="bm-section-head"><i data-feather="lock"></i><h3 class="bm-section-title">{{ __('Password') }}</h3></div>
        <p class="bm-section-sub">{{ __('Set a password now, or leave both fields blank to auto-generate a one-time password shown after creation.') }}</p>

        <div class="tm-grid-2">
            <div>
                <label class="bm-label" for="tm-password">{{ __('Password') }}</label>
                <input type="password" id="tm-password" name="password" class="form-control" value="" minlength="8" maxlength="72" autocomplete="new-password" dir="ltr" placeholder="{{ __('Leave blank to auto-generate') }}">
                @error('password')<div class="tm-err">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="bm-label" for="tm-password-confirm">{{ __('Confirm password') }}</label>
                <input type="password" id="tm-password-confirm" name="password_confirmation" class="form-control" value="" minlength="8" maxlength="72" autocomplete="new-password" dir="ltr">
            </div>
        </div>

        <label class="tm-switch" style="margin-top:14px;">
            <input type="hidden" name="must_change_password" value="0">
            <input type="checkbox" name="must_change_password" value="1" {{ old('must_change_password') ? 'checked' : '' }}>
            <span class="tm-switch-track"></span>
            <span class="tm-switch-text">{{ __('Require them to change it on first sign-in') }}</span>
        </label>
    </div>
    @endunless

    {{-- Role --}}
    <div class="bm-section">
        <div class="bm-section-head"><i data-feather="shield"></i><h3 class="bm-section-title">{{ __('Role') }}</h3></div>
        <p class="bm-section-sub">{{ __('The role sets a baseline of permissions. You can grant extra ones below.') }}</p>

        <div class="tm-roles" role="radiogroup">
            @foreach($roles as $r)
                @php $meta = $roleMeta[$r] ?? ['label' => $r, 'desc' => '', 'icon' => 'user']; @endphp
                <label class="tm-role {{ $currentRole === $r ? 'is-active' : '' }}">
                    <input type="radio" name="role" value="{{ $r }}" {{ $currentRole === $r ? 'checked' : '' }} class="tm-role-input">
                    <span class="tm-role-ic"><i data-feather="{{ $meta['icon'] }}"></i></span>
                    <span class="tm-role-body">
                        <span class="tm-role-name">{{ __($meta['label']) }}</span>
                        <span class="tm-role-desc">{{ __($meta['desc']) }}</span>
                    </span>
                    <span class="tm-role-check"><i data-feather="check"></i></span>
                </label>
            @endforeach
        </div>
        @error('role')<div class="tm-err">{{ $message }}</div>@enderror
    </div>

    {{-- Permissions --}}
    <div class="bm-section">
        <div class="bm-section-head"><i data-feather="key"></i><h3 class="bm-section-title">{{ __('Permissions') }}</h3></div>
        <p class="bm-section-sub">{{ __('Checked items marked “from role” come with the role. Tick extra boxes to grant more.') }}</p>

        <div class="tm-perm-groups">
            @foreach($catalog as $groupKey => $group)
                <div class="tm-perm-group">
                    <div class="tm-perm-group-title">{{ __($group['label']) }}</div>
                    @foreach(($group['permissions'] ?? []) as $key => $label)
                        @php
                            $granted = $roleAll || in_array($key, $roleDef, true);
                            $checked = $granted || in_array($key, $currentPerms, true);
                        @endphp
                        <label class="tm-perm {{ $granted ? 'is-role' : '' }}" data-perm="{{ $key }}">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                   class="tm-perm-input" {{ $checked ? 'checked' : '' }} {{ $granted ? 'disabled' : '' }}>
                            <span class="tm-perm-box"><i data-feather="check"></i></span>
                            <span class="tm-perm-label">{{ __($label) }}</span>
                            <span class="tm-perm-tag" {{ $granted ? '' : 'hidden' }}>{{ __('from role') }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var ROLE_DEFAULTS = @json($roleDefaults);

    // The form is the one holding the role radios (this script is pushed to the
    // page footer, so we can't rely on document.currentScript being inside it).
    var anyRole = document.querySelector('input[name="role"]');
    var form = anyRole ? anyRole.closest('form') : null;
    if (!form) return;

    // Role card highlight + permission recompute on role change.
    function currentRole() {
        var r = form.querySelector('input[name="role"]:checked');
        return r ? r.value : null;
    }
    function grantedBy(role, key) {
        var def = ROLE_DEFAULTS[role] || [];
        return def.indexOf('*') !== -1 || def.indexOf(key) !== -1;
    }
    function applyRole() {
        var role = currentRole();
        form.querySelectorAll('.tm-role').forEach(function (c) {
            c.classList.toggle('is-active', c.querySelector('input').checked);
        });
        form.querySelectorAll('.tm-perm').forEach(function (row) {
            var key = row.getAttribute('data-perm');
            var input = row.querySelector('.tm-perm-input');
            var tag = row.querySelector('.tm-perm-tag');
            if (grantedBy(role, key)) {
                input.checked = true; input.disabled = true;
                row.classList.add('is-role'); if (tag) tag.hidden = false;
            } else {
                if (input.disabled) { input.disabled = false; input.checked = false; }
                row.classList.remove('is-role'); if (tag) tag.hidden = true;
            }
        });
    }
    form.querySelectorAll('input[name="role"]').forEach(function (r) {
        r.addEventListener('change', applyRole);
    });
})();
</script>
@endpush
