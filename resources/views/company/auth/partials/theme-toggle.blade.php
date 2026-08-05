{{-- Floating dark/light switch for the guest auth pages.
     Uses the server-side theme route so the `company_theme` cookie is written
     encrypted (Laravel encrypts cookies) and the correct stylesheet
     (demo1 = light / demo2 = dark) is served on the next load. --}}
@php($theme = request()->cookie('company_theme', 'dark'))
@php($next = $theme === 'light' ? 'dark' : 'light')
<a href="{{ route('company.theme', $next) }}" id="bkThemeToggle" class="bk-theme-toggle"
   aria-label="{{ __('Toggle theme') }}" title="{{ __('Toggle theme') }}">
    @if($theme === 'light')
        {{-- Currently light → offer moon (switch to dark) --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
    @else
        {{-- Currently dark → offer sun (switch to light) --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    @endif
</a>
<style>
    .bk-theme-toggle{
        position:fixed; top:18px; inset-inline-end:18px; z-index:1080;
        width:44px; height:44px; border-radius:50%;
        display:grid; place-items:center; cursor:pointer; text-decoration:none;
        border:1px solid var(--bk-border-strong, rgba(150,150,150,.35));
        background:var(--bk-surface, #1c1c22); color:var(--bk-text, #eaeaea);
        box-shadow:0 6px 20px rgba(0,0,0,.18);
        transition:transform .15s ease, background .2s ease, color .2s ease;
    }
    .bk-theme-toggle:hover{ transform:scale(1.07) rotate(-8deg); color:var(--bk-accent, #C9A227); }
    .bk-theme-toggle:active{ transform:scale(.96); }
    .bk-theme-toggle svg{ width:20px; height:20px; }
</style>
