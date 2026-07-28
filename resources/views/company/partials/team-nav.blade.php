{{--
  Unified tab bar for the Team section — include at the top of
  Staff / Calendar / Leaves / Attendance / Deductions / Payroll pages.
--}}
@php
    $tnCompany = Auth::guard('company')->user();
    $tnFeat = fn (string $k) => $tnCompany?->hasFeature($k) ?? false;

    $teamTabs = [
        ['route' => 'company.staff.index',           'is' => 'company.staff.*',                                   'icon' => 'users',        'label' => 'Staff Overview'],
        ['route' => 'company.team-calendar',         'is' => 'company.team-calendar',                             'icon' => 'calendar',     'label' => 'Team Calendar',  'feature' => 'leaves'],
        ['route' => 'company.employee-leaves.index', 'is' => 'company.employee-leaves.*',                         'icon' => 'coffee',       'label' => 'Leaves',         'feature' => 'leaves'],
        ['route' => 'company.attendance.index',      'is' => 'company.attendance.*',                              'icon' => 'check-circle', 'label' => 'Attendance',     'feature' => 'attendance'],
        ['route' => 'company.holidays.index',        'is' => 'company.holidays.*',                                'icon' => 'sun',          'label' => 'Holidays',       'feature' => 'leaves'],
        ['route' => 'company.deductions.index',      'is' => ['company.deductions.*', 'company.employees.deductions.*'], 'icon' => 'minus-circle', 'label' => 'Deductions', 'feature' => 'payroll'],
        ['route' => 'company.advances.index',        'is' => 'company.advances.*',                                'icon' => 'credit-card',  'label' => 'Advances',       'feature' => 'payroll'],
        ['route' => 'company.payroll.index',         'is' => ['company.payroll.*', 'company.employees.payroll'],  'icon' => 'dollar-sign',  'label' => 'Payroll',        'feature' => 'payroll'],
    ];

    $teamTabs = array_filter($teamTabs, fn ($t) => !isset($t['feature']) || $tnFeat($t['feature']));
@endphp
<style>
.team-nav {
    display: flex; gap: 4px; flex-wrap: nowrap; overflow-x: auto;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px; padding: 4px; margin-bottom: 16px;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
}
.team-nav::-webkit-scrollbar { display: none; }
.bk-theme-light .team-nav { background: #f1f3f5; border-color: #e9ecef; }
.team-nav-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px; white-space: nowrap;
    font-size: 12px; font-weight: 700; text-decoration: none;
    color: rgba(255,255,255,.45); transition: all .15s; flex-shrink: 0;
}
.bk-theme-light .team-nav-tab { color: rgba(0,0,0,.45); }
.team-nav-tab:hover { color: rgba(255,255,255,.85); background: rgba(255,255,255,.05); }
.bk-theme-light .team-nav-tab:hover { color: rgba(0,0,0,.8); background: rgba(0,0,0,.04); }
.team-nav-tab.active {
    background: rgba(102,126,234,.18); color: #a5b4fd;
    box-shadow: 0 2px 8px rgba(102,126,234,.15);
}
.bk-theme-light .team-nav-tab.active { background: #fff; color: #667eea; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.team-nav-tab svg { width: 13px; height: 13px; }
</style>
<nav class="team-nav" aria-label="{{ __('Team') }}">
    @foreach($teamTabs as $tab)
    @php $isActive = collect((array) $tab['is'])->some(fn ($p) => request()->routeIs($p)); @endphp
    <a href="{{ route($tab['route']) }}" class="team-nav-tab {{ $isActive ? 'active' : '' }}">
        <i data-feather="{{ $tab['icon'] }}"></i>
        {{ __($tab['label']) }}
    </a>
    @endforeach
</nav>
