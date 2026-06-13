<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', [FrontController::class, 'index'])->name('front.index');
Route::get('/v2', [FrontController::class, 'index2'])->name('front.index2');
Route::get('/v3', [FrontController::class, 'index3'])->name('front.index3');
Route::get('/api/branches-json', [FrontController::class, 'branchesJson'])->name('front.branches.json');
Route::get('/api/map-branches', [FrontController::class, 'mapBranches'])->name('front.map.branches');
Route::get('/business/{company}', [FrontController::class, 'show'])->name('front.show');
Route::get('/category/{slug}', [FrontController::class, 'categoryPage'])->name('front.category');
Route::get('/branch/{branch}', [FrontController::class, 'branchShow'])->name('front.branch');
Route::get('/about', [FrontController::class, 'about'])->name('front.about');
Route::get('/contact', [FrontController::class, 'contact'])->name('front.contact');
Route::post('/contact', [FrontController::class, 'contactSend'])->name('front.contact.send');
Route::get('/test-gd', function () {
    return extension_loaded('gd') ? 'GD ON' : 'GD OFF';
});
Route::redirect('/dashboard', '/owner/dashboard');

/* ── Customer Auth (phone + OTP) ── */
Route::prefix('customer')->name('customer.')->group(function () {
    Route::post('send-otp',      [CustomerAuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('verify-otp',    [CustomerAuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('save-profile',  [CustomerAuthController::class, 'saveProfile'])->name('save-profile');
    Route::get('me',             [CustomerAuthController::class, 'me'])->name('me');
    Route::post('logout',        [CustomerAuthController::class, 'logout'])->name('logout');
    Route::post('favorites/toggle', [CustomerAuthController::class, 'toggleFavorite'])->name('favorites.toggle');
    Route::get('favorites',      [CustomerAuthController::class, 'favorites'])->name('favorites');
});

/* ── Booking ── */
Route::prefix('api/booking')->name('booking.')->group(function () {
    Route::get('slots',  [BookingController::class, 'slots'])->name('slots');
    Route::post('book',  [BookingController::class, 'book'])->name('book');
});

/* ── Broadcasting auth — support company + owner guards ── */
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    // Try company guard first
    if (Auth::guard('company')->check()) {
        Auth::setUser(Auth::guard('company')->user());
    } elseif (Auth::guard('owner')->check()) {
        Auth::setUser(Auth::guard('owner')->user());
    }
    return app(\Illuminate\Broadcasting\BroadcastController::class)->authenticate($request);
})->middleware('web');

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'ar'], true)) {
        abort(404);
    }
    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

require __DIR__.'/owner.php';
require __DIR__.'/company.php';
