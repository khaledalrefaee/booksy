<?php

/*
 * Company Workspace — "insights" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the insights module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\InsightsController;
use Illuminate\Support\Facades\Route;

Route::get('insights', [InsightsController::class, 'index'])->name('insights');
