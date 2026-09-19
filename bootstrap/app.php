<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\AuthenticateCompany;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\AuthenticateOwner;
use App\Http\Middleware\AuthenticateStaff;
use App\Http\Middleware\EnsureStaffPasswordChanged;
use App\Http\Middleware\EnsureCompanyFeature;
use App\Http\Middleware\EnsureCompanyVerified;
use App\Http\Middleware\EnsureOwnerDashboardAccess;
use App\Http\Middleware\EnsureOwnerPasswordChanged;
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
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'owner.auth'       => AuthenticateOwner::class,
            'owner.guest'      => RedirectIfOwnerAuthenticated::class,
            'owner.can'        => EnsureOwnerPermission::class,
            'owner.dashboard'  => EnsureOwnerDashboardAccess::class,
            'owner.mustchange' => EnsureOwnerPasswordChanged::class,
            'company.auth'     => AuthenticateCompany::class,
            'company.verified' => EnsureCompanyVerified::class,
            'company.guest' => RedirectIfCompanyAuthenticated::class,
            'feature'       => EnsureCompanyFeature::class,
            'customer.auth' => AuthenticateCustomer::class,
            'customer.api'  => \App\Http\Middleware\AuthenticateCustomerApi::class,
            'api.locale'    => \App\Http\Middleware\SetApiLocale::class,
            'staff.auth'       => AuthenticateStaff::class,
            'staff.mustchange' => EnsureStaffPasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Email the team a summary of every unhandled 500 (page URL + trace).
        // Laravel already filters out the "expected" exceptions (404/419/auth/
        // validation/…) before report callbacks run; ExceptionMailer adds its
        // own env gate, 4xx guard and de-duplication, and can never throw.
        $exceptions->report(function (\Throwable $e): void {
            \App\Support\ExceptionMailer::report($e);
        });

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

        // ── Unified API error envelope ────────────────────────────────────────
        // Every failure on an /api/* route (or any JSON request) comes back in
        // the SAME shape the controllers use for success:
        //   { "status": false, "message": "...", "data": null, "errors"?: {...} }
        // so the mobile app has one place to read errors. Add an endpoint and it
        // inherits this automatically — nothing extra to wire per controller.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null; // web requests keep the branded HTML error pages
            }

            [$code, $message, $errors] = match (true) {
                $e instanceof \Illuminate\Validation\ValidationException =>
                    [422, $e->validator->errors()->first() ?: __('The given data was invalid.'), $e->errors()],

                $e instanceof \Illuminate\Auth\AuthenticationException =>
                    [401, __('Please sign in to continue.'), null],

                $e instanceof \Illuminate\Auth\Access\AuthorizationException =>
                    [403, $e->getMessage() ?: __('You are not allowed to do this.'), null],

                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException =>
                    [404, __('Not found.'), null],

                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException =>
                    [404, __('Not found.'), null],

                $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException =>
                    [405, __('This action is not allowed.'), null],

                $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException =>
                    [429, __('Too many attempts. Please try again in a few minutes.'), null],

                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface =>
                    [$e->getStatusCode(), $e->getMessage() ?: __('Something went wrong.'), null],

                // Anything unexpected: hide internals in production, show in debug.
                default => [500, config('app.debug') ? $e->getMessage() : __('Something went wrong. Please try again.'), null],
            };

            $payload = [
                'status'  => false,
                'message' => $message,
                'data'    => null,
            ];

            if (! empty($errors)) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $code);
        });
    })->create();
