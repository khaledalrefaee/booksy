<?php

/*
 * Company Workspace — "payroll" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the payroll module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\PayrollController;
use Illuminate\Support\Facades\Route;

Route::get('payroll', [PayrollController::class, 'index'])->name('payroll');
