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

        Route::bind('company', fn (string $value) => Company::query()->findOrFail($value));

          if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
