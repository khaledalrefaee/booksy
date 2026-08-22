<?php

/*
 * Company Workspace — "services" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the services module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\ServicesController;
use Illuminate\Support\Facades\Route;

Route::get('services', [ServicesController::class, 'index'])->name('services');
Route::patch('services/services/{service}/toggle-active', [ServicesController::class, 'toggleActive'])->name('services.toggle-active');
Route::patch('services/services/{service}/price', [ServicesController::class, 'updatePrice'])->name('services.price');
