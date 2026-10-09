@php
    $authOwner     = Auth::guard('owner')->user();
    $currentLocale = app()->getLocale();
    $isAr          = $currentLocale === 'ar';
    $navTheme      = request()->cookie('owner_theme', 'dark');
    $ownerName     = $authOwner?->name ?: 'Admin';
    // Initials avatar rendered locally (no third-party image request on every page).
    $ownerInitials = \Illuminate\Support\Str::of($ownerName)->trim()->explode(' ')->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'A';
    $ownerRole     = $authOwner ? $authOwner->roleLabel() : __('Platform Owner');

    try {
        $bkNotifUnread = (int) \App\Models\OwnerNotification::whereNull('read_at')->count();
        $bkNotifRecent = \App\Models\OwnerNotification::with('company:id,name_en,name_ar')
            ->latest()->limit(6)->get();
    } catch (\Throwable $e) { $bkNotifUnread = 0; $bkNotifRecent = collect(); }
@endphp

<a href="#bkMain" class="bk-skip">{{ __('Skip to content') }}</a>

<nav class="navbar bk-hd" id="bkHeader" aria-label="{{ __('Top bar') }}">
    <div class="bk-hd-in">

        {{-- Menu: folds the sidebar on desktop, opens the drawer on mobile --}}
        <a href="#" class="sidebar-toggler bk-hd-btn bk-hd-menu" role="button"
           aria-controls="bkSidebar" aria-expanded="false" aria-label="{{ __('Menu') }}">
            <i data-feather="menu"></i>
        </a>

        {{-- Brand: the sidebar carries it on desktop --}}
        <a href="{{ route('owner.dashboard') }}" class="bk-hd-brand" aria-label="GlowRez">
            <img class="bk-sb-logo bk-sb-logo--light" src="{{ asset('images/glowrez-logo-light_1.webp') }}" alt="GlowRez">
            <img class="bk-sb-logo bk-sb-logo--dark"  src="{{ asset('images/glowrez-logo-dark_1.webp') }}"  alt="" aria-hidden="true">
        </a>

        {{-- Global search --}}
        <form method="GET" action="{{ route('owner.search.index') }}" class="bk-hd-search" role="search" id="bkSearch">
            <i data-feather="search" class="bk-hd-search-ic"></i>
            <input type="search" name="q" id="bkSearchInput" autocomplete="off" enterkeyhint="search"
                   placeholder="{{ __('Search companies, branches, customers…') }}"
                   aria-label="{{ __('Search') }}">
            <kbd class="bk-hd-kbd" aria-hidden="true">/</kbd>
            <button type="button" class="bk-hd-btn bk-hd-search-x" data-bk-search-close aria-label="{{ __('Close') }}">
                <i data-feather="x"></i>
            </button>
        </form>

        <div class="bk-hd-end">

            {{-- Search (phones only: opens the full-width search bar) --}}
            <button type="button" class="bk-hd-btn bk-hd-search-open" data-bk-search-open aria-label="{{ __('Search') }}">
                <i data-feather="search"></i>
            </button>

            {{-- Notifications --}}
            <div class="dropdown">
                <button type="button" class="bk-hd-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-expanded="false" aria-label="{{ __('Notifications') }}{{ $bkNotifUnread > 0 ? ' ('.$bkNotifUnread.')' : '' }}">
                    <i data-feather="bell"></i>
                    @if($bkNotifUnread > 0)
                        <span class="bk-hd-badge" aria-hidden="true">{{ $bkNotifUnread > 9 ? '9+' : $bkNotifUnread }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end bk-menu bk-menu-notif">
                    <div class="bk-menu-head">
                        <span class="bk-menu-title">{{ __('Notifications') }}</span>
                        @if($bkNotifUnread > 0)
                            <form method="POST" action="{{ route('owner.notifications.read-all') }}" class="m-0">
                                @csrf
                                <button type="submit" class="bk-menu-link">{{ __('Mark all read') }}</button>
                            </form>
                        @endif
                    </div>
                    <div class="bk-notif-list">
                        @forelse($bkNotifRecent as $n)
                            <a href="{{ route('owner.notifications.read', $n->id) }}"
                               class="bk-notif {{ $n->read_at ? '' : 'is-unread' }}">
                                <span class="bk-notif-ic" aria-hidden="true">{{ $n->icon }}</span>
                                <span class="bk-notif-tx">
                                    <span class="bk-notif-t">{{ $n->title }}</span>
                                    <span class="bk-notif-b">{{ $n->body }}</span>
                                    <span class="bk-notif-d">{{ $n->created_at?->diffForHumans() }}</span>
                                </span>
                                @unless($n->read_at)<span class="bk-notif-dot" aria-hidden="true"></span>@endunless
                            </a>
                        @empty
                            <div class="bk-notif-empty">
                                <i data-feather="bell-off"></i>
                                <span>{{ __('No notifications yet') }}</span>
                            </div>
                        @endforelse
                    </div>
                    <a href="{{ route('owner.notifications.index') }}" class="bk-menu-foot">{{ __('View all') }}</a>
                </div>
            </div>

            {{-- Account: profile · appearance · language · sign out --}}
            <div class="dropdown">
                <button type="button" class="bk-hd-user" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="{{ __('Account') }}: {{ $ownerName }}">
                    <span class="bk-av" aria-hidden="true">{{ $ownerInitials }}</span>
                    <span class="bk-hd-user-tx">
                        <span class="bk-hd-user-name">{{ $ownerName }}</span>
                        <span class="bk-hd-user-role">{{ $ownerRole }}</span>
                    </span>
                    <i data-feather="chevron-down" class="bk-hd-user-caret"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end bk-menu bk-menu-user">
                    <div class="bk-menu-id">
                        <span class="bk-av bk-av-lg" aria-hidden="true">{{ $ownerInitials }}</span>
                        <span class="bk-menu-id-tx">
                            <span class="bk-menu-id-name">{{ $ownerName }}</span>
                            <span class="bk-menu-id-role">{{ $ownerRole }}</span>
                        </span>
                    </div>

                    <div class="bk-menu-sec">
                        <a href="{{ route('owner.profile') }}" class="bk-mi"><i data-feather="user"></i><span>{{ __('Profile') }}</span></a>
                        <a href="{{ route('front.index') }}" target="_blank" rel="noopener" class="bk-mi"><i data-feather="external-link"></i><span>{{ __('View website') }}</span></a>
                    </div>

                    <div class="bk-menu-sec">
                        <div class="bk-seg-row">
                            <span class="bk-seg-lbl"><i data-feather="{{ $navTheme === 'dark' ? 'moon' : 'sun' }}"></i>{{ __('Appearance') }}</span>
                            <span class="bk-seg" role="group" aria-label="{{ __('Appearance') }}">
                                <a href="{{ route('owner.theme', ['mode' => 'light']) }}" class="{{ $navTheme === 'light' ? 'is-on' : '' }}" @if($navTheme === 'light') aria-current="true" @endif>{{ __('Light') }}</a>
                                <a href="{{ route('owner.theme', ['mode' => 'dark']) }}"  class="{{ $navTheme !== 'light' ? 'is-on' : '' }}" @if($navTheme !== 'light') aria-current="true" @endif>{{ __('Dark') }}</a>
                            </span>
                        </div>
                        <div class="bk-seg-row">
                            <span class="bk-seg-lbl"><i data-feather="globe"></i>{{ __('Language') }}</span>
                            <span class="bk-seg" role="group" aria-label="{{ __('Language') }}">
                                <a href="{{ route('locale.switch', 'ar') }}" class="{{ $isAr ? 'is-on' : '' }}" lang="ar" hreflang="ar" @if($isAr) aria-current="true" @endif>العربية</a>
                                <a href="{{ route('locale.switch', 'en') }}" class="{{ !$isAr ? 'is-on' : '' }}" lang="en" hreflang="en" @if(!$isAr) aria-current="true" @endif>English</a>
                            </span>
                        </div>
                    </div>

                    <div class="bk-menu-sec">
                        <form method="POST" action="{{ route('owner.logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="bk-mi bk-mi-danger"><i data-feather="log-out"></i><span>{{ __('Sign out') }}</span></button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>

<script>
(function () {
    'use strict';
    var hd     = document.getElementById('bkHeader');
    var form   = document.getElementById('bkSearch');
    var input  = document.getElementById('bkSearchInput');
    if (!hd || !form || !input) return;

    /* Soft shadow only once the page has scrolled under the bar. */
    var ticking = false;
    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () {
            hd.classList.toggle('is-scrolled', (window.scrollY || document.documentElement.scrollTop) > 4);
            ticking = false;
        });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* Phones: the search icon swaps the bar for a full-width search field. */
    function openSearch()  { hd.classList.add('is-searching'); setTimeout(function () { input.focus(); }, 30); }
    function closeSearch() { hd.classList.remove('is-searching'); input.blur(); }
    var openBtn  = hd.querySelector('[data-bk-search-open]');
    var closeBtn = hd.querySelector('[data-bk-search-close]');
    if (openBtn)  openBtn.addEventListener('click', openSearch);
    if (closeBtn) closeBtn.addEventListener('click', closeSearch);

    /* "/" jumps to search from anywhere that is not a text field. */
    document.addEventListener('keydown', function (e) {
        var t = e.target, tag = t && t.tagName;
        if (e.key === '/' && !e.metaKey && !e.ctrlKey && !e.altKey
            && tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT' && !(t && t.isContentEditable)) {
            e.preventDefault();
            if (window.matchMedia('(max-width: 767.98px)').matches) openSearch(); else input.focus();
        } else if (e.key === 'Escape' && document.activeElement === input) {
            closeSearch();
        }
    });
})();
</script>
