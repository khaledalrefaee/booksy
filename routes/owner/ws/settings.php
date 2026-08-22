<?php

/*
 * Company Workspace — "settings" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the settings module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('settings', [SettingsController::class, 'index'])->name('settings');
