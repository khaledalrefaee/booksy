<?php

/*
 * Company Workspace — "inventory" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the inventory module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\InventoryController;
use Illuminate\Support\Facades\Route;

Route::get('inventory', [InventoryController::class, 'index'])->name('inventory');
