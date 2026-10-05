<?php

use App\Http\Controllers\Api\Companies\AppointmentController as CompanyAppointmentController;
use App\Http\Controllers\Api\Companies\AuthController as CompanyAuthController;
use App\Http\Controllers\Api\Companies\BranchController as CompanyBranchController;
use App\Http\Controllers\Api\Companies\CategoryController;
use App\Http\Controllers\Api\Customers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API routes
|--------------------------------------------------------------------------
| Stateless, JSON-only. Registered with the `/api` prefix (bootstrap/app.php).
| Every route runs `api.locale` so the caller's language (?lang / Accept-Language)
| drives both message bodies and JSON responses. Protected routes add
| `customer.api`, which resolves the bearer token issued by verify-code;
| company routes use `company.api` (token from company verify / login).
*/

// ->middleware('throttle:6,60,api-company-register')
// ->middleware('throttle:10,10,api-company-verify')
// ->middleware('throttle:6,10,api-company-resend')
// ->middleware('throttle:10,10,api-company-login')




Route::middleware('api.locale')->group(function () {

    // ── Customer authentication (phone + one-time code) ───────────────────────
    Route::prefix('customer')->name('api.customer.')->group(function () {
        // Public — start the sign-in flow.
        Route::post('send-code',   [AuthController::class, 'sendCode'])->name('send-code');
        Route::post('verify-code', [AuthController::class, 'verifyCode'])->name('verify-code');

        // Protected — require the bearer token from verify-code.
        Route::middleware('customer.api')->group(function () {
            Route::get('me',       [AuthController::class, 'me'])->name('me');
            Route::post('profile', [AuthController::class, 'updateProfile'])->name('profile');
            Route::post('avatar',  [AuthController::class, 'uploadAvatar'])->name('avatar');
            Route::post('logout',  [AuthController::class, 'logout'])->name('logout');
        });
    });

    // ── Company authentication (email + password, OTP-verified sign-up) ──────
    // Throttles mirror the web panel (routes/company.php): codes cost SMS money.
    // The 3rd throttle arg is a per-route key — without it every throttled route
    // shares ONE per-IP counter, so failed logins would also block forgot-password.
    
    Route::prefix('company')->name('api.company.')->group(function () {

        Route::get('categories', [CategoryController::class, 'index']);

        // Public — sign-up, sign-in and password recovery.
        Route::post('register', [CompanyAuthController::class, 'register'])->name('register');
        Route::post('verify',   [CompanyAuthController::class, 'verify'])->name('verify');
        Route::post('resend',   [CompanyAuthController::class, 'resend'])->name('resend');
        Route::post('login',    [CompanyAuthController::class, 'login'])->name('login');

        Route::prefix('password')->name('password.')->group(function () {
            Route::post('forgot', [CompanyAuthController::class, 'forgotPassword'])->middleware('throttle:4,10,api-company-pw-forgot')->name('forgot');
            Route::post('verify', [CompanyAuthController::class, 'verifyResetCode'])->middleware('throttle:10,10,api-company-pw-verify')->name('verify');
            Route::post('reset',  [CompanyAuthController::class, 'resetPassword'])->middleware('throttle:6,10,api-company-pw-reset')->name('reset');
        });

        // Protected — require the bearer token from verify / login.
        Route::middleware('company.api')->group(function () {
            Route::get('me',      [CompanyAuthController::class, 'me'])->name('me');
            Route::post('profile', [CompanyAuthController::class, 'updateProfile'])->name('profile');
            Route::post('logo',    [CompanyAuthController::class, 'uploadLogo'])->name('logo');
            Route::delete('logo',  [CompanyAuthController::class, 'deleteLogo'])->name('logo.delete');
            Route::post('logout', [CompanyAuthController::class, 'logout'])->name('logout');

            // Branches of the signed-in company — feeds the branch picker / branch_id.
            Route::get('branches', [CompanyBranchController::class, 'index'])->name('branches');

            // Calendar booking (drag & drop) — delegates to the web booking rules.
            Route::prefix('appointments')->name('appointments.')->controller(CompanyAppointmentController::class)->group(function () {
                Route::get('/',            'index')->name('index');
                Route::get('calendar',     'calendar')->name('calendar');
                Route::get('staff-events', 'staffEvents')->name('staff-events');
                Route::get('services',     'services')->name('services');
                Route::get('branch-data',  'branchData')->name('branch-data');
                Route::get('customers',    'customers')->name('customers');
                Route::post('/',           'store')->name('store');
                Route::post('group',       'storeGroup')->name('store-group');
                Route::patch('{appointment}/reschedule', 'reschedule')->name('reschedule');
            });
        });
    });

});
