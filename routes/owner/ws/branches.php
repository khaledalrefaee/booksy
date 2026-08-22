<?php

/*
 * Company Workspace — "branches" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the branches module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\BranchesController;
use Illuminate\Support\Facades\Route;

Route::get('branches', [BranchesController::class, 'index'])->name('branches');
Route::get('branches/{branch}/detail', [BranchesController::class, 'detail'])->name('branches.detail');
Route::patch('branches/{branch}/status', [BranchesController::class, 'toggleStatus'])->name('branches.status');
