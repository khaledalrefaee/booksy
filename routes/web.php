<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AppointmentConfirmController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', [FrontController::class, 'home'])->name('front.index');
Route::get('/api/map-branches', [FrontController::class, 'mapBranches'])->name('front.map.branches');

/* ── SEO: XML sitemap + robots (dynamic so URLs match any host) ── */
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /account/',
        'Disallow: /customer/',
        'Disallow: /company/',
        'Disallow: /owner/',
        'Disallow: /api/',
        'Disallow: /appointment/',
        'Disallow: /s/',
        'Disallow: /offline',
        'Disallow: /locale/',
        'Disallow: /*?*login=',
        'Disallow: /*?*return=',
        '',
        'Sitemap: '.url('/sitemap.xml'),
        '',
    ];
    return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('robots');
/* Business (SaaS) legal — must come BEFORE the /business/{company} wildcard
   below, otherwise "privacy"/"terms" get matched as a company slug. Structure
   only; needs local legal-counsel review before relying on it. */
Route::view('/business/privacy', 'front.legal.business-privacy')->name('front.business.privacy');
Route::view('/business/terms', 'front.legal.business-terms')->name('front.business.terms');
Route::get('/business/{company}', [FrontController::class, 'show'])->name('front.show');
Route::get('/venues', [FrontController::class, 'venues'])->name('front.venues');
Route::get('/category/{slug}', [FrontController::class, 'categoryPage'])->name('front.category');
Route::get('/branch/{branch}', [FrontController::class, 'branchShow'])->name('front.branch');
Route::get('/s/{slug}', [FrontController::class, 'privateBooking'])->name('front.private-booking');
Route::get('/for-business', [FrontController::class, 'business'])->name('front.business');
Route::get('/about', [FrontController::class, 'about'])->name('front.about');
Route::get('/appointment/{token}/confirm', [AppointmentConfirmController::class, 'confirm'])->name('appointment.confirm');
Route::get('/appointment/{token}/cancel',  [AppointmentConfirmController::class, 'cancelForm'])->name('appointment.cancel-form');
Route::post('/appointment/{token}/cancel', [AppointmentConfirmController::class, 'cancel'])->name('appointment.cancel-do');
Route::get('/contact', [FrontController::class, 'contact'])->name('front.contact');
Route::post('/contact', [FrontController::class, 'contactSend'])->name('front.contact.send');

/* ── Legal (static, bilingual) ── */
Route::view('/privacy', 'front.legal.privacy')->name('front.privacy');
Route::view('/terms', 'front.legal.terms')->name('front.terms');
Route::view('/help', 'front.help')->name('front.help');
Route::view('/offline', 'front.offline')->name('front.offline');

/* ── PWA manifest (dynamic so it is correct on any host / sub-path) ── */
Route::get('/manifest.webmanifest', function () {
    $isAr = app()->getLocale() === 'ar';
    $base = rtrim(url('/'), '/') . '/';

    return response()->json([
        'name'             => $isAr ? 'GlowRez — حجز الجمال والعناية' : 'GlowRez — Beauty & wellness booking',
        'short_name'       => 'GlowRez',
        'description'      => $isAr ? 'احجز مواعيد أماكن الجمال والعناية في ثوانٍ.' : 'Book beauty & wellness appointments in seconds.',
        'start_url'        => $base,
        'scope'            => $base,
        'display'          => 'standalone',
        'orientation'      => 'portrait',
        'background_color' => '#F7F5EF',
        'theme_color'      => '#4B5D34',
        'lang'             => $isAr ? 'ar' : 'en',
        'dir'              => $isAr ? 'rtl' : 'ltr',
        'categories'       => ['lifestyle', 'shopping'],
        'icons'            => [
            ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

/* ── Customer self-service area (phone+OTP session) ── */
Route::prefix('account')->name('account.')->middleware('customer.auth')->group(function () {
    Route::get('appointments', [CustomerAccountController::class, 'appointments'])->name('appointments');
    Route::get('appointments/{appointment}', [CustomerAccountController::class, 'show'])->name('appointment');
    Route::get('appointments/{appointment}/status', [CustomerAccountController::class, 'status'])->name('appointment.status');
    Route::post('appointments/{appointment}/cancel', [CustomerAccountController::class, 'cancel'])->name('appointment.cancel');
    Route::post('appointments/{appointment}/reschedule', [CustomerAccountController::class, 'reschedule'])->name('appointment.reschedule');
    Route::get('appointments/{appointment}/calendar', [CustomerAccountController::class, 'calendar'])->name('appointment.calendar');
    Route::post('appointments/{appointment}/review', [CustomerAccountController::class, 'storeReview'])->name('appointment.review');
    Route::get('waitlist', [\App\Http\Controllers\WaitlistController::class, 'index'])->name('waitlist');
    Route::delete('waitlist/{entry}', [\App\Http\Controllers\WaitlistController::class, 'destroy'])->name('waitlist.leave');
    Route::get('favorites', [CustomerAccountController::class, 'favorites'])->name('favorites');
    Route::get('profile', [CustomerAccountController::class, 'profile'])->name('profile');
    Route::post('profile', [CustomerAccountController::class, 'updateProfile'])->name('profile.update');
    Route::post('delete', [CustomerAccountController::class, 'deleteAccount'])->name('delete');
});

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
    Route::get('group-slots', [BookingController::class, 'groupSlots'])->name('group-slots');
    Route::post('group-book', [BookingController::class, 'groupBook'])->name('group-book');
});

/* ── Online waitlist (join is public; controller gates on customer session) ── */
Route::post('/api/waitlist/join', [\App\Http\Controllers\WaitlistController::class, 'join'])->name('waitlist.join');

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
