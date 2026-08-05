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

        Route::bind('company', fn (string $value) => Company::query()->findOrFail($value));

          // URL::forceScheme('https');
    }
}
