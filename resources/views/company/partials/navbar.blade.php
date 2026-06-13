@php
    $authCompany   = Auth::guard('company')->user();
    $currentLocale = app()->getLocale();
    $hour          = now()->hour;
    $greeting      = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
    $isAr          = $currentLocale === 'ar';
@endphp

<style>
.navbar .navbar-content { gap: 6px !important; }
.navbar .navbar-nav { gap: 0 !important; }
.navbar .navbar-nav .nav-link { padding-inline: 6px !important; }
</style>

<nav class="navbar">

    {{-- Hamburger toggler --}}
    <a href="#" class="sidebar-toggler">
        <i data-feather="menu"></i>
    </a>

    {{-- All content inside navbar-content to preserve NobleUI layout --}}
    <div class="navbar-content">

        {{-- Greeting (pushed to start, takes available space) --}}
        <div class="me-auto d-none d-xl-flex flex-column justify-content-center" style="line-height:1.3;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.4;">
                {{ $greeting }} 👋
            </div>
            <div style="font-size:.88rem;font-weight:700;color:#C9A227;">
                {{ $authCompany?->localizedName() }}
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-none d-lg-flex align-items-center gap-1 me-1">
            <a href="{{ route('company.appointments.create') }}"
               class="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3">
                <i class="feather icon-plus" style="font-size:13px;line-height:1;"></i>
                {{ __('New booking') }}
            </a>
            <a href="{{ route('company.branches.index') }}"
               class="btn btn-outline-secondary btn-sm rounded-pill d-flex align-items-center gap-1 px-3">
                <i class="feather icon-map-pin" style="font-size:12px;line-height:1;"></i>
                {{ __('Branches') }}
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
                    <a href="{{ route('locale.switch','en') }}"
                       class="dropdown-item {{ $currentLocale==='en'?'active':'' }}">
                        <i class="flag-icon flag-icon-us me-2" style="border-radius:2px;"></i> English
                    </a>
                    <a href="{{ route('locale.switch','ar') }}"
                       class="dropdown-item {{ $currentLocale==='ar'?'active':'' }}">
                        <i class="flag-icon flag-icon-sa me-2" style="border-radius:2px;"></i> العربية
                    </a>
                </div>
            </li>

            {{-- Profile --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#"
                   data-bs-toggle="dropdown">
                    @if($authCompany?->logo)
                        <img src="{{ asset('storage/'.$authCompany->logo) }}"
                             class="rounded-circle" style="width:34px;height:34px;object-fit:cover;flex-shrink:0;border:2px solid rgba(201,162,39,.35);" alt="">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=34&background=C9A227&color=000&bold=true"
                             class="rounded-circle" style="width:34px;height:34px;flex-shrink:0;border:2px solid rgba(201,162,39,.25);" alt="">
                    @endif
                    <div class="d-none d-md-block" style="line-height:1.2;text-align:{{ $isAr?'right':'left' }};">
                        <div style="font-size:.78rem;font-weight:700;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $authCompany?->localizedName() }}
                        </div>
                        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.6px;opacity:.4;">Business</div>
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:220px;border-radius:12px;overflow:hidden;">
                    <div class="px-4 py-3 border-bottom" style="background:rgba(201,162,39,.07);">
                        <a href="{{ route('company.profile.show') }}">
                            <div class="d-flex align-items-center gap-3">
                                @if($authCompany?->logo)
                                    <img src="{{ asset('storage/'.$authCompany->logo) }}"
                                        style="width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=42&background=C9A227&color=000&bold=true"
                                        style="width:42px;height:42px;border-radius:50%;flex-shrink:0;" alt="">
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-size:.84rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $authCompany?->localizedName() }}</div>
                                    <div style="font-size:.72rem;color:#C9A227;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $authCompany?->email }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <ul class="list-unstyled p-2 mb-0">
                         <li >
                            <a href="{{ route('company.profile.show') }}"
                            class="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2">
                                <i class="link-icon" data-feather="user"></i>
                                <span class="icon-sm link-title " >{{ __('Profile') }}</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a href="{{ $authCompany ? route('front.show', $authCompany) : '#' }}" target="_blank"
                               class="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2">
                                <i class="icon-sm feather icon-external-link"></i> {{ __('Public page') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="{{ route('company.logout') }}">
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
