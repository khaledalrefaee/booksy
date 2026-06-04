<nav class="sidebar">
    <div class="sidebar-header">
      <a href="{{ route('owner.dashboard') }}" class="sidebar-brand">
        Booksy<span>Owner</span>
      </a>
      <div class="sidebar-toggler not-active">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
    <div class="sidebar-body">
      <ul class="nav">
        <li class="nav-item nav-category">{{ __('Main') }}</li>
        <li class="nav-item">
          <a href="{{ route('owner.dashboard') }}" class="nav-link">
            <i class="link-icon" data-feather="home"></i>
            <span class="link-title">{{ __('Dashboard') }}</span>
          </a>
        </li>
          <li class="nav-item">
          <a href="{{ route('owner.categories.index') }}" class="nav-link">
            <i class="link-icon" data-feather="layers"></i>
            <span class="link-title">{{ __('Company categories') }}</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('owner.service-categories.index') }}" class="nav-link">
            <i class="link-icon" data-feather="grid"></i>
            <span class="link-title">{{ __('Service categories') }}</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('owner.campanias.index') }}" class="nav-link">
            <i class="link-icon" data-feather="briefcase"></i>
            <span class="link-title">{{ __('Companies') }}</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('owner.branches.index') }}" class="nav-link">
            <i class="link-icon" data-feather="map-pin"></i>
            <span class="link-title">{{ __('Branches') }}</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('owner.appointments.index') }}" class="nav-link">
            <i class="link-icon" data-feather="calendar"></i>
            <span class="link-title">{{ __('Appointments') }}</span>
          </a>
        </li>
 
      </ul>
    </div>
</nav>

<nav class="settings-sidebar" >
    <div class="sidebar-body" >
      <a href="#" class="settings-sidebar-toggler" >
        <i data-feather="settings"></i>
      </a>
      @php($ownerTheme = $ownerTheme ?? request()->cookie('owner_theme', 'dark'))
      <div class="theme-wrapper">
        <h6 class="text-muted mb-2">{{ __('Light theme') }}:</h6>
        <a class="theme-item{{ $ownerTheme === 'light' ? ' active' : '' }}" href="{{ route('owner.theme', ['mode' => 'light']) }}" role="button">
          <img src="{{ asset('backend/assets/images/screenshots/light.jpg') }}" alt="{{ __('Light theme preview') }}">
        </a>
        <h6 class="text-muted mb-2">{{ __('Dark theme') }}:</h6>
        <a class="theme-item{{ $ownerTheme === 'dark' ? ' active' : '' }}" href="{{ route('owner.theme', ['mode' => 'dark']) }}" role="button">
          <img src="{{ asset('backend/assets/images/screenshots/dark.jpg') }}" alt="{{ __('Dark theme preview') }}">
        </a>
      </div>
    </div>
  </nav>
