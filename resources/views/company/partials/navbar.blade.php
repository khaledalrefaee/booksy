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

/* ── Notification bell (DB-backed, unified GlowRez style) ── */
.bk-notif-badge{
    position:absolute; top:-2px; inset-inline-end:2px;
    min-width:16px; height:16px; padding:0 4px; border-radius:8px;
    background:var(--bk-danger,#ef4444); color:#fff;
    font-size:9px; font-weight:800; line-height:1;
    display:flex; align-items:center; justify-content:center;
}
.bk-notif-menu{
    min-width:352px; max-width:calc(100vw - 24px);
    border-radius:16px; overflow:hidden; padding:0 !important;
    background:var(--bk-surface); border:1px solid var(--bk-border);
    box-shadow:var(--bk-shadow-xl);
}
.bk-notif-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px; border-bottom:1px solid var(--bk-border);
    background:var(--bk-accent-wash);
}
.bk-notif-h-title{ font-size:13px; font-weight:800; color:var(--bk-text); }
.bk-notif-readall{
    font-size:10.5px; font-weight:700; cursor:pointer;
    color:var(--bk-accent); background:transparent;
    border:1px solid var(--bk-border-strong); border-radius:20px;
    padding:4px 10px; transition:background .15s;
}
.bk-notif-readall:hover{ background:var(--bk-accent-wash); }
.bk-notif-list{ max-height:min(60vh,380px); overflow-y:auto; }
.bk-notif-item{
    display:flex; gap:12px; align-items:flex-start;
    padding:13px 18px; text-decoration:none;
    border-bottom:1px solid var(--bk-border);
    transition:background .12s;
}
.bk-notif-item:last-child{ border-bottom:none; }
.bk-notif-item:hover{ background:var(--bk-surface-2); }
.bk-notif-item.is-unread{ background:color-mix(in srgb, var(--bk-accent) 6%, transparent); }
.bk-notif-ic{
    flex-shrink:0; width:38px; height:38px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
    color:var(--acc); background:color-mix(in srgb, var(--acc) 14%, transparent);
}
.bk-notif-ic svg{ width:19px; height:19px; }
.bk-notif-txt{ min-width:0; flex:1; display:flex; flex-direction:column; gap:2px; }
.bk-notif-t{ font-size:12.5px; font-weight:700; color:var(--bk-text); line-height:1.3; }
.bk-notif-b{
    font-size:11.5px; color:var(--bk-text-soft); line-height:1.4;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.bk-notif-time{ font-size:10px; color:var(--bk-text-muted); margin-top:1px; }
.bk-notif-dot{
    flex-shrink:0; width:8px; height:8px; border-radius:50%;
    background:var(--bk-accent); margin-top:6px;
}
.bk-notif-empty{
    display:flex; flex-direction:column; align-items:center; gap:10px;
    padding:38px 18px; color:var(--bk-text-muted);
}
.bk-notif-empty svg{ width:30px; height:30px; opacity:.5; }
.bk-notif-empty span{ font-size:12px; font-weight:600; }
.bk-notif-all{
    display:block; text-align:center; padding:12px;
    font-size:12px; font-weight:700; color:var(--bk-accent);
    text-decoration:none; border-top:1px solid var(--bk-border);
    background:var(--bk-surface); transition:background .15s;
}
.bk-notif-all:hover{ background:var(--bk-accent-wash); color:var(--bk-accent-hover); }
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
            <div style="font-size:.88rem;font-weight:700;color:var(--bk-accent);">
                {{ $authCompany?->localizedName() }}
            </div>
        </div>

        {{-- Global search --}}
        <form method="GET" action="{{ route('company.search.index') }}"
              class="d-none d-md-flex align-items-center me-2" style="min-width:200px;max-width:260px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent border-end-0">
                    <i data-feather="search" style="width:13px;height:13px;opacity:.5;"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0"
                       placeholder="{{ __('Search') }}…" style="font-size:.8rem;">
            </div>
        </form>
        <a href="{{ route('company.search.index') }}" class="nav-link d-md-none" style="padding:0 8px;">
            <i data-feather="search" style="width:18px;height:18px;"></i>
        </a>

        {{-- Action Buttons --}}
        <div class="d-none d-lg-flex align-items-center gap-1 me-1">
            <a href="{{ route('company.appointments.create') }}" data-tour="new-booking"
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

            {{-- Help / getting-started (always available) --}}
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="#" style="padding:0 10px;"
                   data-bs-toggle="modal" data-bs-target="#bkHelpModal" aria-label="{{ __('Help') }}" title="{{ __('Help') }}">
                    <i data-feather="help-circle" style="width:18px;height:18px;"></i>
                </a>
            </li>

            {{-- Notifications bell — DB-backed (StaffNotification), unified GlowRez style --}}
            @php
                $recentNotifs = $authCompany
                    ? \App\Models\StaffNotification::where('company_id', $authCompany->id)->orderByDesc('created_at')->limit(8)->get()
                    : collect();
                $unreadCount = $authCompany
                    ? \App\Models\StaffNotification::where('company_id', $authCompany->id)->unread()->count()
                    : 0;
            @endphp
            <li class="nav-item dropdown bk-notif">
                <a class="nav-link d-flex align-items-center" href="#" data-bs-toggle="dropdown" data-notif-bell
                   style="padding:0 10px;position:relative;" aria-label="{{ __('Notifications') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    @if($unreadCount > 0)
                    <span class="bk-notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0 bk-notif-menu">
                    <div class="bk-notif-head">
                        <span class="bk-notif-h-title">{{ __('Notifications') }}</span>
                        @if($unreadCount > 0)
                        <form method="POST" action="{{ route('company.notifications.read-all') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="bk-notif-readall">{{ __('Mark all read') }}</button>
                        </form>
                        @endif
                    </div>
                    <div class="bk-notif-list">
                        @forelse($recentNotifs as $notif)
                        <a href="{{ $notif->link ?? '#' }}"
                           class="bk-notif-item {{ $notif->isRead() ? '' : 'is-unread' }}"
                           @unless($notif->isRead())
                           onclick="fetch('{{ route('company.notifications.read', $notif) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})"
                           @endunless>
                            <span class="bk-notif-ic" style="--acc:var({{ $notif->accentToken() }});">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $notif->iconSvg() !!}</svg>
                            </span>
                            <span class="bk-notif-txt">
                                <span class="bk-notif-t">{{ $notif->title }}</span>
                                @if($notif->body)<span class="bk-notif-b">{{ $notif->body }}</span>@endif
                                <span class="bk-notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                            </span>
                            @unless($notif->isRead())<span class="bk-notif-dot"></span>@endunless
                        </a>
                        @empty
                        <div class="bk-notif-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            <span>{{ __('No new notifications') }}</span>
                        </div>
                        @endforelse
                    </div>
                    <a href="{{ route('company.notifications.index') }}" class="bk-notif-all">{{ __('View all notifications') }}</a>
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
                             class="rounded-circle" style="width:34px;height:34px;object-fit:cover;flex-shrink:0;border:2px solid rgba(75,93,52,.35);" alt="">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=34&background=4B5D34&color=FFFFFF&bold=true"
                             class="rounded-circle" style="width:34px;height:34px;flex-shrink:0;border:2px solid rgba(75,93,52,.25);" alt="">
                    @endif
                    <div class="d-none d-md-block" style="line-height:1.2;text-align:{{ $isAr?'right':'left' }};">
                        <div style="font-size:.78rem;font-weight:700;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $authCompany?->localizedName() }}
                        </div>
                        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.6px;opacity:.4;">Business</div>
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:220px;border-radius:12px;overflow:hidden;">
                    <div class="px-4 py-3 border-bottom" style="background:rgba(75,93,52,.07);">
                        <a href="{{ route('company.profile.show') }}">
                            <div class="d-flex align-items-center gap-3">
                                @if($authCompany?->logo)
                                    <img src="{{ asset('storage/'.$authCompany->logo) }}"
                                        style="width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($authCompany?->localizedName() ?? 'Co') }}&size=42&background=4B5D34&color=FFFFFF&bold=true"
                                        style="width:42px;height:42px;border-radius:50%;flex-shrink:0;" alt="">
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-size:.84rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $authCompany?->localizedName() }}</div>
                                    <div style="font-size:.72rem;color:var(--bk-accent);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $authCompany?->email }}</div>
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
                            @php($navTheme = request()->cookie('company_theme', 'dark'))
                            <a href="{{ route('company.theme', ['mode' => $navTheme === 'dark' ? 'light' : 'dark']) }}"
                               class="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2">
                                <i class="icon-sm feather icon-{{ $navTheme === 'dark' ? 'sun' : 'moon' }}"></i>
                                {{ $navTheme === 'dark' ? __('Light mode') : __('Dark mode') }}
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
