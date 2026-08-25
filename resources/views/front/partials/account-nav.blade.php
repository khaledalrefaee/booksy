{{-- Account nav: guest → Log in / business ; signed-in → avatar + dropdown.
     Mobile-first: on the ≤1100px drawer the menu expands inline (full width);
     on desktop it's an anchored dropdown. Self-contained style + script. --}}
@php
    $isAr     = app()->getLocale() === 'ar';
    $customer = \App\Http\Controllers\CustomerAuthController::authCustomer();
    $fullName = $customer ? trim($customer->name) : '';
    $firstName= $fullName !== '' ? \Illuminate\Support\Str::of($fullName)->explode(' ')->first() : ($isAr ? 'حسابي' : 'Account');
    $initial  = mb_strtoupper(mb_substr($firstName, 0, 1));
    $favIds   = $customer ? $customer->favoriteBranches()->pluck('branches.id')->all() : [];
@endphp

{{-- Favourites are account-bound: expose auth + toggle endpoint + saved ids to the shared favourites JS. --}}
<script>
window.BK_FAV = {
  auth: {{ $customer ? 'true' : 'false' }},
  toggle: '{{ route('customer.favorites.toggle') }}',
  ids: @json($favIds)
};
</script>

@if(! $customer)
    <a href="{{ route('front.business') }}" class="bkf-btn bkf-btn-ghost bkf-nav-btn">{{ $isAr ? 'لأصحاب الأعمال' : 'For business' }}</a>
    <button type="button" class="bkf-btn bkf-btn-primary bkf-nav-btn" onclick="CustomerAuthModal.open(()=>location.reload())">
        {{ $isAr ? 'تسجيل الدخول' : 'Log in' }}<x-icon name="user" :size="18"/>
    </button>
@else
    <div class="bkf-acct" data-acct>
        <button type="button" class="bkf-acct-btn" data-acct-toggle aria-expanded="false" aria-haspopup="menu">
            <span class="bkf-acct-avatar">{{ $initial }}</span>
            <span class="bkf-acct-name">{{ $firstName }}</span>
            <x-icon name="chevron-down" :size="16" class="bkf-acct-caret"/>
        </button>

        <div class="bkf-acct-menu" data-acct-menu role="menu">
            <div class="bkf-acct-head">
                <span class="bkf-acct-avatar lg">{{ $initial }}</span>
                <span class="bkf-acct-id">
                    <b>{{ $firstName }}</b>
                    <small dir="ltr">{{ $customer->phone }}</small>
                </span>
            </div>

            <a role="menuitem" href="{{ route('account.appointments') }}" class="bkf-acct-item">
                <x-icon name="calendar-check" :size="19"/>{{ $isAr ? 'مواعيدي' : 'My appointments' }}
                <x-icon name="{{ $isAr ? 'chevron-left' : 'chevron-right' }}" :size="16" class="bkf-acct-go"/>
            </a>

            @if(\Illuminate\Support\Facades\Route::has('account.waitlist'))
            <a role="menuitem" href="{{ route('account.waitlist') }}" class="bkf-acct-item">
                <x-icon name="clock" :size="19"/>{{ $isAr ? 'قائمة الانتظار' : 'Waitlist' }}
                <x-icon name="{{ $isAr ? 'chevron-left' : 'chevron-right' }}" :size="16" class="bkf-acct-go"/>
            </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('account.favorites'))
            <a role="menuitem" href="{{ route('account.favorites') }}" class="bkf-acct-item">
                <x-icon name="heart" :size="19"/>{{ $isAr ? 'المفضلة' : 'Favorites' }}
                <x-icon name="{{ $isAr ? 'chevron-left' : 'chevron-right' }}" :size="16" class="bkf-acct-go"/>
            </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('account.profile'))
            <a role="menuitem" href="{{ route('account.profile') }}" class="bkf-acct-item">
                <x-icon name="user" :size="19"/>{{ $isAr ? 'الملف الشخصي' : 'Profile' }}
                <x-icon name="{{ $isAr ? 'chevron-left' : 'chevron-right' }}" :size="16" class="bkf-acct-go"/>
            </a>
            @endif

            <button type="button" role="menuitem" class="bkf-acct-item is-danger" data-acct-logout>
                <x-icon name="log-out" :size="19"/>{{ $isAr ? 'تسجيل الخروج' : 'Log out' }}
            </button>
        </div>
    </div>

    <style>
    .bkf-acct{ position:relative; }
    .bkf-acct-btn{
        display:inline-flex; align-items:center; gap:8px; cursor:pointer;
        padding:6px 12px 6px 6px; min-height:42px; border-radius:var(--bk-r-pill);
        border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text);
        font-family:var(--bk-font-ui); font-weight:600; font-size:.88rem;
        transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease,transform var(--bk-t) ease;
    }
    html[dir="rtl"] .bkf-acct-btn{ padding:6px 6px 6px 12px; }
    .bkf-acct-btn:hover{ border-color:var(--bk-accent); box-shadow:var(--bk-shadow-xs); }
    .bkf-acct-btn:active{ transform:translateY(1px); }
    .bkf-acct-avatar{
        display:grid; place-items:center; width:30px; height:30px; flex:0 0 auto;
        border-radius:var(--bk-r-pill); background:var(--bk-grad-accent); color:var(--bk-accent-ink);
        font-weight:800; font-size:.85rem; line-height:1;
    }
    .bkf-acct-avatar.lg{ width:44px; height:44px; font-size:1.15rem; }
    .bkf-acct-name{ max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bkf-acct-caret{ color:var(--bk-text-muted); transition:transform var(--bk-t) var(--bk-spring); }
    .bkf-acct.is-open .bkf-acct-caret{ transform:rotate(180deg); }

    .bkf-acct-menu{
        position:absolute; inset-inline-end:0; top:calc(100% + 10px); z-index:var(--bk-z-drop);
        width:264px; padding:8px; border-radius:var(--bk-r-lg);
        background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-lg);
        opacity:0; transform:translateY(-8px) scale(.98); transform-origin:top var(--bk-inline-end,right);
        pointer-events:none; transition:opacity var(--bk-t) ease,transform var(--bk-t) var(--bk-spring);
    }
    .bkf-acct.is-open .bkf-acct-menu{ opacity:1; transform:none; pointer-events:auto; }
    .bkf-acct-head{ display:flex; align-items:center; gap:12px; padding:10px 10px 12px; margin-bottom:6px; border-bottom:1px solid var(--bk-border); }
    .bkf-acct-id{ display:flex; flex-direction:column; min-width:0; }
    .bkf-acct-id b{ font-size:.95rem; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bkf-acct-id small{ font-size:.78rem; color:var(--bk-text-muted); }
    .bkf-acct-item{
        display:flex; align-items:center; gap:12px; width:100%;
        padding:12px 12px; min-height:48px; border-radius:var(--bk-r); border:none;
        background:transparent; color:var(--bk-text); cursor:pointer;
        font-family:var(--bk-font-ui); font-weight:600; font-size:.92rem; text-align:start;
        transition:background var(--bk-t) ease,color var(--bk-t) ease;
    }
    .bkf-acct-item:hover,.bkf-acct-item:focus-visible{ background:var(--bk-accent-wash); color:var(--bk-accent); outline:none; }
    .bkf-acct-item svg{ flex:0 0 auto; color:var(--bk-text-muted); }
    .bkf-acct-item:hover svg{ color:var(--bk-accent); }
    .bkf-acct-go{ margin-inline-start:auto; }
    .bkf-acct-item.is-danger{ color:var(--bk-danger); }
    .bkf-acct-item.is-danger svg{ color:var(--bk-danger); }
    .bkf-acct-item.is-danger:hover{ background:var(--bk-danger-bg); color:var(--bk-danger); }

    /* Mobile drawer (≤1100px): full-width button, menu expands inline. */
    @media (max-width:1100px){
        .bkf-acct{ flex:1 1 100%; }
        .bkf-acct-btn{ width:100%; justify-content:flex-start; min-height:52px; font-size:1rem; }
        .bkf-acct-caret{ margin-inline-start:auto; }
        .bkf-acct-menu{
            position:static; width:100%; margin-top:10px; box-shadow:none;
            opacity:1; transform:none; pointer-events:auto;
            max-height:0; padding-block:0; overflow:hidden; border-color:transparent;
            transition:max-height var(--bk-t-slow) var(--bk-ease),padding var(--bk-t) ease;
        }
        .bkf-acct.is-open .bkf-acct-menu{ max-height:420px; padding:8px; border-color:var(--bk-border); }
    }
    </style>

    <script>
    (function(){
        var wrap = document.querySelector('[data-acct]');
        if(!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        var btn = wrap.querySelector('[data-acct-toggle]');
        function setOpen(o){ wrap.classList.toggle('is-open', o); btn.setAttribute('aria-expanded', o?'true':'false'); }
        btn.addEventListener('click', function(e){ e.stopPropagation(); setOpen(!wrap.classList.contains('is-open')); });
        document.addEventListener('click', function(e){ if(!wrap.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') setOpen(false); });

        var out = wrap.querySelector('[data-acct-logout]');
        if(out) out.addEventListener('click', function(){
            out.disabled = true;
            fetch('{{ route('customer.logout') }}', {
                method:'POST',
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
            }).finally(function(){ window.location.href = '{{ route('front.index') }}'; });
        });
    })();
    </script>
@endif
