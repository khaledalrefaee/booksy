@php
    $bnCan = fn (string $k) => \Illuminate\Support\Facades\Gate::allows('owner-can', $k);

    $bnAppts     = $bkOwnerPendingAppts ?? 0;
    $bnCompanies = $bkOwnerPendingCompanies ?? 0;
    $bnLeads     = $bkOwnerNewLeads ?? null;
    if ($bnLeads === null) {
        try { $bnLeads = $bnCan('leads.view') ? (int) \App\Models\Lead::where('status', 'new')->count() : 0; }
        catch (\Throwable $e) { $bnLeads = 0; }
    }

    $bnItems = [
        ['route' => 'owner.dashboard',          'on' => 'owner.dashboard',    'icon' => 'home',      'label' => __('Main'),         'badge' => 0],
        ['route' => 'owner.appointments.index', 'on' => 'owner.appointments.*','icon' => 'calendar',  'label' => __('Appointments'), 'badge' => $bnAppts],
        ['route' => 'owner.companies.index',    'on' => 'owner.companies.*',  'icon' => 'briefcase', 'label' => __('Companies'),    'badge' => $bnCompanies],
    ];
    if ($bnCan('leads.view')) {
        $bnItems[] = ['route' => 'owner.leads.index', 'on' => 'owner.leads.*', 'icon' => 'user-plus', 'label' => __('Leads'), 'badge' => $bnLeads];
    }
@endphp

{{-- Phone tab bar: the four daily destinations + the full menu (hidden ≥992px where the sidebar is visible) --}}
<nav class="bk-bottom-nav bk-bottom-nav--owner d-lg-none" aria-label="{{ __('Main') }}">
    @foreach($bnItems as $it)
    <a href="{{ route($it['route']) }}"
       class="bk-bn-item {{ request()->routeIs($it['on']) ? 'active' : '' }}"
       @if(request()->routeIs($it['on'])) aria-current="page" @endif>
        <span class="bk-bn-ic">
            <i data-feather="{{ $it['icon'] }}"></i>
            @if($it['badge'] > 0)<span class="bk-bn-badge">{{ $it['badge'] > 9 ? '9+' : $it['badge'] }}</span>@endif
        </span>
        <span>{{ $it['label'] }}</span>
    </a>
    @endforeach
    {{-- .sidebar-toggler: reuses the template's own handler to open the drawer --}}
    <button type="button" class="bk-bn-item sidebar-toggler" aria-controls="bkSidebar" aria-expanded="false">
        <span class="bk-bn-ic"><i data-feather="grid"></i></span>
        <span>{{ __('Menu') }}</span>
    </button>
</nav>
