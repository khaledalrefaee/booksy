<?php

/*
 * Company Workspace — "customers" tab routes.
 * Loaded inside routes/owner.php under:
 *   prefix  companies/{company}/ws   |   name  owner.companies.ws.
 *   gate    owner.can:company-workspace.view
 * Owned by the customers module agent — add this tab's action routes here.
 */

use App\Http\Controllers\Owner\Workspace\CustomersController;
use Illuminate\Support\Facades\Route;

Route::get('customers', [CustomersController::class, 'index'])->name('customers');
Route::get('customers/{customer}/profile', [CustomersController::class, 'profile'])->name('customers.profile');
Route::patch('customers/{customer}/ban', [CustomersController::class, 'toggleBan'])->name('customers.ban');
Route::patch('customers/debts/{debt}/waive', [CustomersController::class, 'waiveDebt'])->name('customers.debts.waive');
