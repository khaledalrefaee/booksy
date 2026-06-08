@php
    $authOwner     = Auth::guard('owner')->user();
    $currentLocale = app()->getLocale();
    $hour          = now()->hour;
    $greeting      = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
    $isAr          = $currentLocale === 'ar';
@endphp

<nav class="navbar">

    <a href="#" class="sidebar-toggler">
        <i data-feather="menu"></i>
    </a>

    <div class="navbar-content">

        {{-- Greeting --}}
        <div class="me-auto d-none d-xl-flex flex-column justify-content-center" style="line-height:1.3;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.4;">
                {{ $greeting }} 👋
            </div>
            <div style="font-size:.88rem;font-weight:700;color:#C9A227;">
                {{ $authOwner?->name ?? 'Admin' }}
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-none d-lg-flex align-items-center gap-2 me-2">
            <a href="{{ route('owner.companies.index') }}"
               class="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3">
                <i class="feather icon-briefcase" style="font-size:12px;line-height:1;"></i>
                {{ __('Companies') }}
            </a>
            <a href="{{ route('owner.appointments.index') }}"
               class="btn btn-outline-secondary btn-sm rounded-pill d-flex align-items-center gap-1 px-3">
                <i class="feather icon-calendar" style="font-size:12px;line-height:1;"></i>
                {{ __('Appointments') }}
            </a>
        </div>

        <ul class="navbar-nav">

            {{-- Language --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#"
                   data-bs-toggle="dropdown" style="font-size:.78rem;font-weight:600;padding:0 8px;">
                    @if($isAr)
                        <i class="flag-icon flag-icon-sa" style="border-radius:2px;font-size:14px;"></i>
                        <span class="d-none d-md-inline">AR</span>
                    @else
                        <i class="flag-icon flag-icon-us" style="border-radius:2px;font-size:14px;"></i>
                        <span class="d-none d-md-inline">EN</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="{{ route('locale.switch','en') }}" class="dropdown-item {{ $currentLocale==='en'?'active':'' }}">
                        <i class="flag-icon flag-icon-us me-2" style="border-radius:2px;"></i> English
                    </a>
                    <a href="{{ route('locale.switch','ar') }}" class="dropdown-item {{ $currentLocale==='ar'?'active':'' }}">
                        <i class="flag-icon flag-icon-sa me-2" style="border-radius:2px;"></i> العربية
                    </a>
                </div>
            </li>

            {{-- Profile --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($authOwner?->name ?? 'Owner') }}&size=32&background=C9A227&color=000&bold=true"
                         class="wd-32 ht-32 rounded-circle" style="border:2px solid rgba(201,162,39,.3);" alt="">
                    <div class="d-none d-md-block" style="line-height:1.2;text-align:{{ $isAr?'right':'left' }};">
                        <div style="font-size:.78rem;font-weight:700;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $authOwner?->name ?? 'Admin' }}
                        </div>
                        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.6px;opacity:.4;">Platform Owner</div>
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:220px;border-radius:12px;overflow:hidden;">
                    <div class="px-4 py-3 border-bottom" style="background:rgba(201,162,39,.07);">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($authOwner?->name ?? 'Owner') }}&size=42&background=C9A227&color=000&bold=true"
                                 style="width:42px;height:42px;border-radius:50%;flex-shrink:0;" alt="">
                            <div style="min-width:0;">
                                <div style="font-size:.84rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $authOwner?->name }}</div>
                                <div style="font-size:.7rem;margin-top:3px;">
                                    <span style="background:#C9A227;color:#000;border-radius:20px;padding:1px 8px;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">
                                        Platform Owner
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="list-unstyled p-2 mb-0">
                        <li>
                            <a href="{{ route('owner.profile') }}"
                               class="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2">
                                <i class="icon-sm feather icon-user"></i> {{ __('Profile') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('front.index') }}" target="_blank"
                               class="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2">
                                <i class="icon-sm feather icon-globe"></i> {{ __('View website') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="{{ route('owner.logout') }}">
                                @csrf
                                <button type="submit"
                                    class="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2 text-danger w-100 border-0 bg-transparent">
                                    <i class="icon-sm feather icon-log-out"></i> {{ __('Sign out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </li>

        </ul>
    </div>
</nav>
