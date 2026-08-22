<?php

/*
 * Company Workspace — "team" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the team module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('team', [TeamController::class, 'index'])->name('team');
Route::get('team/employees/{employee}', [TeamController::class, 'employee'])->name('team.employee');
