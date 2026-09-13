<?php

use App\Http\Controllers\Api\Customers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API routes
|--------------------------------------------------------------------------
| Stateless, JSON-only. Registered with the `/api` prefix (bootstrap/app.php).
| Every route runs `api.locale` so the caller's language (?lang / Accept-Language)
| drives both message bodies and JSON responses. Protected routes add
| `customer.api`, which resolves the bearer token issued by verify-code.
*/

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

});
