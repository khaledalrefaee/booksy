<?php

use App\Http\Controllers\Employee\FieldVisitController;
use Illuminate\Support\Facades\Route;

/*
| Employee area (guard: owner). This is the light, mobile-first home for
| GlowRez field staff. Login is the same owner login; a rep without
| owner-dashboard.view is routed here instead of the admin panel.
|
| Everything is owner-scoped in the controller: a rep only ever sees and
| edits their own field visits.
*/
Route::prefix('employee')->name('employee.')
    ->middleware(['owner.auth', 'owner.mustchange'])
    ->group(function () {

        Route::get('/', [FieldVisitController::class, 'home'])
            ->middleware('owner.can:field-visits.view.own')
            ->name('home');

        Route::prefix('field-visits')->name('field-visits.')->group(function () {
            Route::get('/', [FieldVisitController::class, 'index'])
                ->middleware('owner.can:field-visits.view.own')->name('index');
            Route::get('create', [FieldVisitController::class, 'create'])
                ->middleware('owner.can:field-visits.create')->name('create');
            Route::post('/', [FieldVisitController::class, 'store'])
                ->middleware('owner.can:field-visits.create')->name('store');
            Route::get('{visit}', [FieldVisitController::class, 'show'])
                ->middleware('owner.can:field-visits.view.own')->name('show');
            Route::patch('{visit}/checkout', [FieldVisitController::class, 'checkout'])
                ->middleware('owner.can:field-visits.create')->name('checkout');
        });
    });
