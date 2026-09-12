<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\AuthenticateCompany;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\AuthenticateOwner;
use App\Http\Middleware\AuthenticateStaff;
use App\Http\Middleware\EnsureStaffPasswordChanged;
use App\Http\Middleware\EnsureCompanyFeature;
use App\Http\Middleware\EnsureCompanyVerified;
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

        // SMS credit system
        $schedule->command('sms:send-reminders')->everyTenMinutes();
        $schedule->command('sms:send-followups')->dailyAt('10:00');
        $schedule->command('sms:expire-credits')->dailyAt('00:30');
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
            'company.auth'     => AuthenticateCompany::class,
            'company.verified' => EnsureCompanyVerified::class,
            'company.guest' => RedirectIfCompanyAuthenticated::class,
            'feature'       => EnsureCompanyFeature::class,
            'customer.auth' => AuthenticateCustomer::class,
            'staff.auth'       => AuthenticateStaff::class,
            'staff.mustchange' => EnsureStaffPasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A CSRF token goes stale when a page — most often a login form — is left
        // open longer than the session lifetime. Rather than showing the raw 419
        // "Page expired" screen, send the visitor back to the form they just
        // submitted (which re-renders with a fresh token), keep what they typed
        // (never the password) and tell them plainly what happened.
        //
        // Note: Laravel maps TokenMismatchException to an HttpException(419) before
        // render callbacks run, so we match on the 419 status, not the original
        // exception class. Returning null for any other status leaves the branded
        // 403/404/500/… error pages untouched.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your session expired. Please refresh the page and try again.'),
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->withErrors(['session' => __('Your session expired due to inactivity. Please try again.')]);
        });
    })->create();
