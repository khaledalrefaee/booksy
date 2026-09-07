<?php

use App\Http\Controllers\Staff\PortalController;
use Illuminate\Support\Facades\Route;

/*
| Staff portal (guard: staff). Login itself is unified with the company
| login (see Company\Auth\LoginController); these are the authenticated
| staff-only screens. Phase 1: password-change gate + a minimal landing.
*/
Route::prefix('staff')->name('staff.')->middleware('staff.auth')->group(function () {

    Route::post('/logout', [PortalController::class, 'logout'])->name('logout');

    // First-login forced change — reachable even while the change is still pending.
    Route::get('/password', [PortalController::class, 'showChangePassword'])->name('password.change');
    Route::put('/password', [PortalController::class, 'changePassword'])
        ->middleware('throttle:10,10')->name('password.update');

    // Everything past the password gate.
    Route::middleware('staff.mustchange')->group(function () {
        Route::get('/', [PortalController::class, 'home'])->name('home');
    });
});
