<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\AuthenticateCompany;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\AuthenticateOwner;
use App\Http\Middleware\EnsureCompanyFeature;
use App\Http\Middleware\EnsureOwnerPermission;
use App\Http\Middleware\RedirectIfCompanyAuthenticated;
use App\Http\Middleware\RedirectIfOwnerAuthenticated;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('appointments:send-reminders')->everyTenMinutes();
        $schedule->command('appointments:flag-no-shows')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('booksy:process-recurring-expenses')->dailyAt('06:00');
        $schedule->command('employees:license-reminders')->dailyAt('09:00');
        $schedule->command('subscriptions:expiry-reminders')->dailyAt('08:00');
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'owner.auth'    => AuthenticateOwner::class,
            'owner.guest'   => RedirectIfOwnerAuthenticated::class,
            'owner.can'     => EnsureOwnerPermission::class,
            'company.auth'  => AuthenticateCompany::class,
            'company.guest' => RedirectIfCompanyAuthenticated::class,
            'feature'       => EnsureCompanyFeature::class,
            'customer.auth' => AuthenticateCustomer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
