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

            {{-- Notifications bell --}}
            @php
                $unreadNotifs = $authCompany
                    ? \App\Models\StaffNotification::where('company_id', $authCompany->id)->unread()->orderByDesc('created_at')->limit(10)->get()
                    : collect();
                $unreadCount = $unreadNotifs->count();
            @endphp
            <li class="nav-item dropdown">
                <a class="nav-link d-flex align-items-center" href="#" data-bs-toggle="dropdown" style="padding:0 10px;position:relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    @if($unreadCount > 0)
                    <span style="position:absolute;top:-2px;inset-inline-end:2px;min-width:16px;height:16px;border-radius:8px;background:#ef4444;color:#fff;font-size:9px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 4px;line-height:1;">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:340px;max-height:440px;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,.08);box-shadow:0 12px 40px rgba(0,0,0,.4);">
                    <div class="px-4 py-3 d-flex justify-content-between align-items-center" style="background:rgba(201,162,39,.06);border-bottom:1px solid rgba(255,255,255,.06);">
                        <span class="fw-bold tx-13">🔔 {{ __('Notifications') }}</span>
                        @if($unreadCount > 0)
                        <form method="POST" action="{{ route('company.notifications.read-all') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm px-2 py-1 rounded-pill" style="font-size:10px;color:#C9A227;font-weight:700;background:rgba(201,162,39,.1);border:1px solid rgba(201,162,39,.2);">{{ __('Mark all read') }}</button>
                        </form>
                        @endif
                    </div>
                    <div style="max-height:370px;overflow-y:auto;">
                        @forelse($unreadNotifs as $notif)
                        <a href="{{ $notif->link ?? '#' }}"
                           class="d-flex gap-3 px-4 py-3 text-decoration-none notif-item"
                           style="border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s;"
                           onclick="fetch('{{ route('company.notifications.read', $notif) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})">
                            <div style="width:38px;height:38px;border-radius:12px;background:{{ $notif->color }}15;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                                {{ $notif->icon }}
                            </div>
                            <div style="min-width:0;flex:1;">
                                <div class="fw-bold tx-12" style="color:var(--text-color);margin-bottom:2px;">{{ $notif->title }}</div>
                                @if($notif->body)
                                <div class="tx-11 text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $notif->body }}</div>
                                @endif
                                <div class="tx-10 mt-1" style="opacity:.35;">{{ $notif->created_at->diffForHumans() }}</div>
                            </div>
                            <div style="width:8px;height:8px;border-radius:50%;background:#C9A227;flex-shrink:0;margin-top:6px;"></div>
                        </a>
                        @empty
                        <div class="text-center py-5" style="opacity:.35;">
                            <div style="font-size:28px;margin-bottom:8px;">🔔</div>
                            <div class="tx-12 fw-semibold">{{ __('No new notifications') }}</div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </li>

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
