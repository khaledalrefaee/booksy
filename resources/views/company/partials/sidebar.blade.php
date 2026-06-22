@php
    $authCompany = Auth::guard('company')->user();
    $sidebarBranches = $authCompany ? $authCompany->branches()->orderBy('sort_order')->get() : collect();
    $currentBranchId = request()->route('branch')?->id;
    $onBranchRoute = request()->routeIs('company.branches.show')
        || request()->routeIs('company.branches.employees.*')
        || request()->routeIs('company.branches.services.*')
        || request()->routeIs('company.branches.working-hours.*')
        || request()->routeIs('company.branches.gallery');
    // Which branch sub-menu is open? The one matching current route, or none.
    $openBranchId = $currentBranchId ?? null;
@endphp

<style>
.sidebar .sidebar-body .nav .nav-item.nav-category:not(:first-child) { margin-top: 6px !important; }
.nav-link-sub {
    padding-top: 7px !important;
    padding-bottom: 7px !important;
    padding-inline-start: 36px !important;
    font-size: 13px !important;
}
.nav-link-sub .sub-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    flex-shrink: 0;
    transition: background .2s, transform .2s;
    margin-inline-end: 8px;
}
.nav-link-sub:hover .sub-dot,
.nav-link-sub.active .sub-dot {
    background: var(--primary, #667eea);
    transform: scale(1.3);
}
.branch-collapse-toggle {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 9px 20px;
    font-size: 13px;
    font-weight: 600;
    color: inherit;
    background: none;
    border: none;
    cursor: pointer;
    text-align: start;
    border-radius: 6px;
    transition: background .15s;
    gap: 8px;
    opacity: .75;
}
.branch-collapse-toggle:hover { background: rgba(255,255,255,.05); opacity:1; }
.branch-collapse-toggle .caret {
    margin-inline-start: auto;
    transition: transform .2s;
    opacity: .5;
    flex-shrink: 0;
}
.branch-collapse-toggle[aria-expanded="true"] .caret { transform: rotate(180deg); }
.branch-collapse-list { padding: 0; margin: 0; list-style: none; }
</style>

<nav class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('company.dashboard') }}" class="sidebar-brand">
            Booksy<span>.</span>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span><span></span><span></span>
        </div>
    </div>

    <div class="sidebar-body">
        <ul class="nav">

            {{-- ── Profile card ── --}}
            <li class="nav-item" style="padding:0 8px 8px;">
                <div style="
                    display:flex;align-items:center;gap:10px;
                    padding:12px 14px;border-radius:12px;
                    background:rgba(201,162,39,.08);
                    border:1px solid rgba(201,162,39,.15);
                    margin-bottom:4px;">
                    <div style="position:relative;flex-shrink:0;">
                        @if($authCompany?->logo)
                            <img src="{{ asset('storage/'.$authCompany->logo) }}"
                                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(201,162,39,.4);" alt="">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=36&background=C9A227&color=000&bold=true"
                                 style="width:36px;height:36px;border-radius:50%;" alt="">
                        @endif
                        <span style="position:absolute;bottom:1px;right:1px;width:8px;height:8px;background:#2bcf7e;border-radius:50%;border:2px solid var(--sidebar-bg,#0c1427);"></span>
                    </div>
                    <div style="overflow:hidden;min-width:0;">
                        <div style="font-size:.8rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $authCompany?->localizedName() }}</div>
                        <div style="font-size:.65rem;color:#C9A227;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Business</div>
                    </div>
                </div>
            </li>

            {{-- ══════════════════════════════════════ --}}
            {{-- ── DAILY OPERATIONS ── --}}
            {{-- ══════════════════════════════════════ --}}
            <li class="nav-item nav-category">{{ __('Daily Operations') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.dashboard') }}"
                   class="nav-link {{ request()->routeIs('company.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="home"></i>
                    <span class="link-title">{{ __('Dashboard') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.appointments.index') }}"
                   class="nav-link {{ request()->routeIs('company.appointments.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="calendar"></i>
                    <span class="link-title">{{ __('Appointments') }}</span>
                    @php
                        $pendingCount = 0;
                        if ($authCompany) {
                            try {
                                $pendingCount = (int) $authCompany->branches()
                                    ->withCount(['appointments as pc' => fn($q) => $q->where('status','pending')])
                                    ->get()->sum('pc');
                            } catch(\Throwable $e) {}
                        }
                    @endphp
                    @if($pendingCount > 0)
                        <span class="bk-nav-badge">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>
                    @endif
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.customers.index') }}"
                   class="nav-link {{ request()->routeIs('company.customers.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">{{ __('Customers') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" style="opacity:.45;cursor:not-allowed;" title="{{ __('Coming soon') }}">
                    <i class="link-icon" data-feather="clock"></i>
                    <span class="link-title">{{ __('Waitlist') }}</span>
                    <span style="font-size:9px;font-weight:700;background:rgba(201,162,39,.15);color:#C9A227;padding:2px 7px;border-radius:20px;margin-inline-start:auto;">
                        {{ __('Soon') }}
                    </span>
                </a>
            </li>

            {{-- ══════════════════════════════════════ --}}
            {{-- ── BRANCHES ── --}}
            {{-- ══════════════════════════════════════ --}}
            <li class="nav-item nav-category">{{ __('Branches') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.branches.index') }}"
                   class="nav-link {{ request()->routeIs('company.branches.index') || request()->routeIs('company.branches.create') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="grid"></i>
                    <span class="link-title">{{ __('All Branches') }}</span>
                </a>
            </li>

            @foreach($sidebarBranches as $br)
            @php
                $brOpen = $openBranchId === $br->id;
                $brId   = 'br-sub-' . $br->id;
            @endphp
            <li class="nav-item">
                <button class="branch-collapse-toggle bk-branch-toggle"
                        type="button"
                        data-target="{{ $brId }}"
                        aria-expanded="{{ $brOpen ? 'true' : 'false' }}">
                    <i data-feather="map-pin" style="width:14px;height:14px;flex-shrink:0;opacity:.6;"></i>
                    <span class="text-truncate">{{ $br->localizedName() }}</span>
                    <i data-feather="chevron-down" class="caret" style="width:12px;height:12px;"></i>
                </button>
                <ul class="branch-collapse-list" id="{{ $brId }}"
                    style="{{ $brOpen ? '' : 'display:none;' }}">
                    <li class="nav-item">
                        <a href="{{ route('company.branches.show', $br) }}"
                           class="nav-link nav-link-sub {{ request()->routeIs('company.branches.show') && $brOpen ? 'active' : '' }}">
                            <span class="sub-dot"></span>
                            <span class="link-title">{{ __('Overview') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('company.branches.services.index', $br) }}"
                           class="nav-link nav-link-sub {{ request()->routeIs('company.branches.services.*') && $brOpen ? 'active' : '' }}">
                            <span class="sub-dot"></span>
                            <span class="link-title">{{ __('Services') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('company.branches.employees.index', $br) }}"
                           class="nav-link nav-link-sub {{ request()->routeIs('company.branches.employees.*') && $brOpen ? 'active' : '' }}">
                            <span class="sub-dot"></span>
                            <span class="link-title">{{ __('Employees') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('company.branches.working-hours.edit', $br) }}"
                           class="nav-link nav-link-sub {{ request()->routeIs('company.branches.working-hours.*') && $brOpen ? 'active' : '' }}">
                            <span class="sub-dot"></span>
                            <span class="link-title">{{ __('Working Hours') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('company.branches.gallery', $br) }}"
                           class="nav-link nav-link-sub {{ request()->routeIs('company.branches.gallery') && $brOpen ? 'active' : '' }}">
                            <span class="sub-dot"></span>
                            <span class="link-title">{{ __('Gallery') }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('company.branches.cash.index', $br) }}"
                           class="nav-link nav-link-sub {{ request()->routeIs('company.branches.cash.*') && $brOpen ? 'active' : '' }}">
                            <span class="sub-dot"></span>
                            <span class="link-title">{{ __('Cash Register') }}</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endforeach

            {{-- ══════════════════════════════════════ --}}
            {{-- ── TEAM ── --}}
            {{-- ══════════════════════════════════════ --}}
            <li class="nav-item nav-category">{{ __('Team') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.staff.index') }}"
                   class="nav-link {{ request()->routeIs('company.staff.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">{{ __('Staff Overview') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.employee-leaves.index') }}"
                   class="nav-link {{ request()->routeIs('company.employee-leaves.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="user-x"></i>
                    <span class="link-title">{{ __('Leaves') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.attendance.index') }}"
                   class="nav-link {{ request()->routeIs('company.attendance.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="check-circle"></i>
                    <span class="link-title">{{ __('Attendance') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.deductions.index') }}"
                   class="nav-link {{ request()->routeIs('company.deductions.*') && !request()->routeIs('company.employees.deductions.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="minus-circle"></i>
                    <span class="link-title">{{ __('Deductions') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.payroll.index') }}"
                   class="nav-link {{ request()->routeIs('company.payroll.*') || request()->routeIs('company.employees.payroll') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="dollar-sign"></i>
                    <span class="link-title">{{ __('Payroll') }}</span>
                </a>
            </li>

            {{-- ══════════════════════════════════════ --}}
            {{-- ── FINANCE ── --}}
            {{-- ══════════════════════════════════════ --}}
            <li class="nav-item nav-category">{{ __('Finance') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.cash.global') }}"
                   class="nav-link {{ request()->routeIs('company.cash.global') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="credit-card"></i>
                    <span class="link-title">{{ __('Cash Registers') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.invoices.index') }}"
                   class="nav-link {{ request()->routeIs('company.invoices.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">{{ __('Invoices') }}</span>
                </a>
            </li>

            {{-- ══════════════════════════════════════ --}}
            {{-- ── REPORTS & SETTINGS ── --}}
            {{-- ══════════════════════════════════════ --}}
            <li class="nav-item nav-category">{{ __('Reports') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.activity-log.index') }}"
                   class="nav-link {{ request()->routeIs('company.activity-log.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="shield"></i>
                    <span class="link-title">{{ __('Activity Log') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" style="opacity:.45;cursor:not-allowed;" title="{{ __('Coming soon') }}">
                    <i class="link-icon" data-feather="bar-chart-2"></i>
                    <span class="link-title">{{ __('Analytics') }}</span>
                    <span style="font-size:9px;font-weight:700;background:rgba(102,126,234,.2);color:#667eea;padding:2px 7px;border-radius:20px;margin-inline-start:auto;">
                        {{ __('Soon') }}
                    </span>
                </a>
            </li>

            <li class="nav-item nav-category">{{ __('Settings') }}</li>

            <li class="nav-item">
                <a href="{{ route('company.service-categories.index') }}"
                   class="nav-link {{ request()->routeIs('company.service-categories.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="tag"></i>
                    <span class="link-title">{{ __('Service categories') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('company.profile.show') }}"
                   class="nav-link {{ request()->routeIs('company.profile.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="settings"></i>
                    <span class="link-title">{{ __('Profile') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ $authCompany ? route('front.show', $authCompany) : '#' }}" target="_blank" class="nav-link" style="opacity:.55;">
                    <i class="link-icon" data-feather="external-link"></i>
                    <span class="link-title">{{ __('View public page') }}</span>
                </a>
            </li>

        </ul>
    </div>
</nav>

<nav class="settings-sidebar">
    <div class="sidebar-body">
        <a href="#" class="settings-sidebar-toggler">
            <i data-feather="settings"></i>
        </a>
        @php($companyTheme = $companyTheme ?? request()->cookie('company_theme', 'dark'))
        <div class="theme-wrapper">
            <h6 class="text-muted mb-2">{{ __('Light') }}:</h6>
            <a class="theme-item{{ $companyTheme === 'light' ? ' active' : '' }}"
               href="{{ route('company.theme', ['mode' => 'light']) }}" role="button">
                <img src="{{ asset('backend/assets/images/screenshots/light.jpg') }}" alt="">
            </a>
            <h6 class="text-muted mb-2 mt-2">{{ __('Dark') }}:</h6>
            <a class="theme-item{{ $companyTheme === 'dark' ? ' active' : '' }}"
               href="{{ route('company.theme', ['mode' => 'dark']) }}" role="button">
                <img src="{{ asset('backend/assets/images/screenshots/dark.jpg') }}" alt="">
            </a>
        </div>
    </div>
</nav>

<script>
document.querySelectorAll('.bk-branch-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var targetId = this.dataset.target;
        var list = document.getElementById(targetId);
        var open = this.getAttribute('aria-expanded') === 'true';

        // Close all other branch sub-menus
        document.querySelectorAll('.bk-branch-toggle').forEach(function(b) {
            if (b !== btn) {
                b.setAttribute('aria-expanded', 'false');
                var otherList = document.getElementById(b.dataset.target);
                if (otherList) otherList.style.display = 'none';
            }
        });

        // Toggle this one
        this.setAttribute('aria-expanded', open ? 'false' : 'true');
        if (list) list.style.display = open ? 'none' : '';
    });
});
</script>
