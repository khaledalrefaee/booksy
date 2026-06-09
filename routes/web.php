<?php

use App\Http\Controllers\FrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontController::class, 'index'])->name('front.index');
Route::get('/business/{company}', [FrontController::class, 'show'])->name('front.show');
Route::get('/category/{slug}', [FrontController::class, 'categoryPage'])->name('front.category');
Route::get('/branch/{branch}', [FrontController::class, 'branchShow'])->name('front.branch');
Route::get('/about', [FrontController::class, 'about'])->name('front.about');
Route::get('/contact', [FrontController::class, 'contact'])->name('front.contact');
Route::post('/contact', [FrontController::class, 'contactSend'])->name('front.contact.send');

Route::redirect('/dashboard', '/owner/dashboard');

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'ar'], true)) {
        abort(404);
    }
    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

require __DIR__.'/owner.php';
require __DIR__.'/company.php';
