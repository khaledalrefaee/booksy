<?php

namespace App\Providers;

use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Models\Company;
use App\Repositories\EloquentBranchRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    


    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BranchRepositoryInterface::class, EloquentBranchRepository::class);

        // Branch-scoped permission engine — single source of truth for effective
        // permissions. Singleton so the slug→level/id map is resolved once per request.
        $this->app->singleton(\App\Support\Access\PermissionResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // @feature('payroll') ... @endfeature — true when the logged-in company has the module
        \Illuminate\Support\Facades\Blade::if('feature', function (string $key) {
            return \Illuminate\Support\Facades\Auth::guard('company')->user()?->hasFeature($key) ?? false;
        });

        // @can('owner-can', 'companies.suspend') — owner-panel permission check.
        // Default value on $user lets the gate run for guests of the default guard;
        // the admin is resolved from the 'owner' guard explicitly.
        \Illuminate\Support\Facades\Gate::define('owner-can', function ($user = null, string $permission = '') {
            $owner = $user instanceof \App\Models\Owner
                ? $user
                : \Illuminate\Support\Facades\Auth::guard('owner')->user();

            return $owner?->hasPermission($permission) ?? false;
        });

        // يحترم ->withTrashed() على الراوت: الراوتات العادية تستثني الشركات المغلقة
        // (Soft-deleted)، أما راوتات الاستعادة/الحذف النهائي (المعلّمة withTrashed)
        // فتقدر تصل للمغلقة أيضاً.
        Route::bind('company', function (string $value, $route) {
            $query = Company::query();

            if ($route instanceof \Illuminate\Routing\Route && $route->allowsTrashedBindings()) {
                $query->withTrashed();
            }

            return $query->findOrFail($value);
        });

        // Every leads.* view (welcome / join / thanks / layout) gets the locale helpers
        // and GlowRez's own contact links, so each page stays free of boilerplate.
        \Illuminate\Support\Facades\View::composer('leads.*', function ($view) {
            $isAr = app()->getLocale() === 'ar';
            $view->with([
                'isAr'  => $isAr,
                't'     => fn ($ar, $en) => $isAr ? $ar : $en,
                'waUrl' => \App\Support\LeadContact::whatsappUrl(),
                'igUrl' => \App\Support\LeadContact::instagramUrl(),
            ]);
        });

        // Pre-launch lead form: a person fills it once or twice, a script fills it
        // thousands of times. Cap per IP over 10 minutes AND per day.
        \Illuminate\Support\Facades\RateLimiter::for('lead-submit', function (\Illuminate\Http\Request $request) {
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinutes(10, (int) config('leads.rate_limit.per_ten_minutes', 5))->by('lead10:'.$request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perDay((int) config('leads.rate_limit.per_day', 30))->by('lead1d:'.$request->ip()),
            ];
        });

          // URL::forceScheme('https');
    }
}
