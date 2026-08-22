<?php

/*
 * Company Workspace — "finance" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the finance module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\FinanceController;
use Illuminate\Support\Facades\Route;

Route::get('finance', [FinanceController::class, 'index'])->name('finance');
