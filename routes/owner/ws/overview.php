<?php

/*
 * Company Workspace — "overview" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the overview module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\OverviewController;
use Illuminate\Support\Facades\Route;

Route::get('overview', [OverviewController::class, 'index'])->name('overview');
